import { makeWASocket, useMultiFileAuthState, DisconnectReason, Browsers, fetchLatestBaileysVersion, delay, downloadMediaMessage, isLidUser, isPnUser, jidDecode, proto } from '@whiskeysockets/baileys';
import express from 'express';
import bodyParser from 'body-parser';
import pino from 'pino';
import axios from 'axios';
import QRCode from 'qrcode';
import fs from 'fs';
import crypto from 'crypto';

const app = express();
app.use(bodyParser.json({ limit: '50mb' }));
app.use(bodyParser.urlencoded({ extended: false, limit: '50mb' }));

const PORT = 8790;
const AUTH_TOKEN = 'lawangsewu2026';
let WEBHOOK_URL = process.env.WEBHOOK_CALLBACK_URL || 'https://lawangsewu.pa-semarang.go.id/api/wa-caraka/webhook/inbound';
let HISTORY_SYNC_WEBHOOK_URL = process.env.WEBHOOK_HISTORY_SYNC_URL || 'https://lawangsewu.pa-semarang.go.id/api/wa-caraka/webhook/history-sync';
const LID_MAP_FILE = process.env.WA_LID_MAP_FILE || 'lid-pn-map.json';
const MAX_INLINE_MEDIA_BYTES = Number(process.env.WA_INLINE_MEDIA_MAX_BYTES || 2_000_000);
const MAX_UPLOAD_MEDIA_BYTES = Number(process.env.WA_MAX_UPLOAD_MEDIA_BYTES || 15 * 1024 * 1024);
const MEDIA_STORE_TTL_MS = Number(process.env.WA_MEDIA_STORE_TTL_MS || 3_600_000); // 1 hour
const HISTORY_SYNC_ENABLED = String(process.env.WA_HISTORY_SYNC_ENABLED || 'true') !== 'false';
const HISTORY_SYNC_MAX_DAYS = Number(process.env.WA_HISTORY_SYNC_MAX_DAYS || 45);
const HISTORY_SYNC_BATCH_SIZE = Math.max(25, Number(process.env.WA_HISTORY_SYNC_BATCH_SIZE || 75));

// In-memory store for media files that are too large to inline as base64
const mediaStore = new Map(); // token -> { buffer, mime, fileName, createdAt }

// Periodically remove expired media entries
setInterval(() => {
    const cutoff = Date.now() - MEDIA_STORE_TTL_MS;
    for (const [token, entry] of mediaStore) {
        if (entry.createdAt < cutoff) {
            mediaStore.delete(token);
        }
    }
}, 15 * 60 * 1000);

const logger = pino({ level: 'silent' });

let sock;
let currentQR = '';
let isConnected = false;
let user = null;
let messagesStore = [];
let lidToPnMap = new Map();
let pnToLidMap = new Map();
let lidMapDirty = false;
let historySyncState = {
    runKey: null,
    status: 'idle',
    progress: 0,
    startedAt: null,
    finishedAt: null,
    lastEventAt: null,
    received: 0,
    imported: 0,
    duplicates: 0,
    failed: 0,
};

const SUPPORTED_JID_SUFFIXES = [
    '@s.whatsapp.net',
    '@g.us',
    '@lid',
    '@broadcast',
    '@newsletter',
];

const normalizeOutgoingJid = (to) => {
    const input = String(to || '').trim();
    if (!input) {
        return '';
    }

    if (SUPPORTED_JID_SUFFIXES.some((suffix) => input.endsWith(suffix))) {
        return input;
    }

    if (input.includes('@')) {
        return input;
    }

    return `${input}@s.whatsapp.net`;
};

const normalizeMediaKind = (value) => {
    const kind = String(value || '').toLowerCase().trim();
    if (['image', 'video', 'audio', 'document', 'sticker'].includes(kind)) {
        return kind;
    }
    return null;
};

const parseDataUrl = (value) => {
    const raw = String(value || '').trim();
    const match = raw.match(/^data:([^;,]+);base64,(.+)$/i);
    if (!match) {
        return null;
    }

    try {
        return {
            mime: match[1],
            buffer: Buffer.from(match[2], 'base64'),
        };
    } catch {
        return null;
    }
};

const inferMimeType = (mimeType, fileName, mediaUrl, kind) => {
    const normalizedMime = String(mimeType || '').trim().toLowerCase();
    if (normalizedMime) {
        return normalizedMime;
    }

    const dataUrlMatch = String(mediaUrl || '').match(/^data:([^;,]+);base64,/i);
    if (dataUrlMatch?.[1]) {
        return String(dataUrlMatch[1]).trim().toLowerCase();
    }

    const candidateName = String(fileName || '').trim()
        || (() => {
            try {
                const path = new URL(String(mediaUrl || '')).pathname || '';
                return path.split('/').pop() || '';
            } catch {
                return '';
            }
        })();

    const extension = candidateName.includes('.') ? candidateName.split('.').pop().toLowerCase() : '';

    const byExtension = {
        jpg: 'image/jpeg',
        jpeg: 'image/jpeg',
        png: 'image/png',
        gif: 'image/gif',
        webp: 'image/webp',
        heic: 'image/heic',
        heif: 'image/heif',
        mp4: 'video/mp4',
        mov: 'video/quicktime',
        avi: 'video/x-msvideo',
        mp3: 'audio/mpeg',
        ogg: 'audio/ogg',
        oga: 'audio/ogg',
        wav: 'audio/wav',
        m4a: 'audio/mp4',
        pdf: 'application/pdf',
        rtf: 'application/rtf',
        doc: 'application/msword',
        docx: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        xls: 'application/vnd.ms-excel',
        xlsx: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ppt: 'application/vnd.ms-powerpoint',
        pptx: 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        csv: 'text/csv',
        txt: 'text/plain',
        json: 'application/json',
        xml: 'application/xml',
        zip: 'application/zip',
        rar: 'application/vnd.rar',
        '7z': 'application/x-7z-compressed',
    };

    if (byExtension[extension]) {
        return byExtension[extension];
    }

    return {
        image: 'image/jpeg',
        video: 'video/mp4',
        audio: 'audio/ogg',
        sticker: 'image/webp',
        document: 'application/octet-stream',
    }[kind] || 'application/octet-stream';
};

const isLidJid = (value) => Boolean(value && isLidUser(String(value)));
const isPnJid = (value) => Boolean(value && isPnUser(String(value)));

const decodeJidParts = (jid) => {
    const decoded = jidDecode(String(jid || '').trim());
    if (!decoded?.user) {
        return null;
    }

    const [baseUser, devicePart] = String(decoded.user).split(':');
    return {
        baseUser,
        device: devicePart || null,
        server: decoded.server,
    };
};

const withPnServer = (pnUser, device = null) => `${pnUser}${device ? `:${device}` : ''}@s.whatsapp.net`;
const withLidServer = (lidUser, device = null) => `${lidUser}${device ? `:${device}` : ''}@lid`;

const persistLidMappings = () => {
    if (!lidMapDirty) {
        return;
    }

    lidMapDirty = false;
    const payload = {
        updatedAt: new Date().toISOString(),
        pairs: Array.from(lidToPnMap.entries()).map(([lid, pn]) => ({ lid, pn })),
    };

    try {
        fs.writeFileSync(LID_MAP_FILE, JSON.stringify(payload, null, 2));
    } catch (err) {
        console.error('[WA Runtime] failed to persist LID mappings:', err?.message || err);
    }
};

const loadLidMappings = () => {
    if (!fs.existsSync(LID_MAP_FILE)) {
        return;
    }

    try {
        const raw = fs.readFileSync(LID_MAP_FILE, 'utf8');
        const parsed = JSON.parse(raw);
        const pairs = Array.isArray(parsed?.pairs) ? parsed.pairs : [];

        for (const pair of pairs) {
            const lidBase = String(pair?.lid || '').trim();
            const pnBase = String(pair?.pn || '').trim();
            if (!lidBase || !pnBase) {
                continue;
            }
            lidToPnMap.set(lidBase, pnBase);
            pnToLidMap.set(pnBase, lidBase);
        }

        if (pairs.length > 0) {
            console.log(`[WA Runtime] loaded ${pairs.length} persisted LID mappings`);
        }
    } catch (err) {
        console.error('[WA Runtime] failed to load LID mappings:', err?.message || err);
    }
};

const rememberLidPnPair = (lidJid, pnJid, source = 'unknown') => {
    const lidParts = decodeJidParts(lidJid);
    const pnParts = decodeJidParts(pnJid);
    if (!lidParts || !pnParts) {
        return;
    }

    const normalizedLidJid = withLidServer(lidParts.baseUser);
    const normalizedPnJid = withPnServer(pnParts.baseUser);

    if (!isLidJid(normalizedLidJid) || !isPnJid(normalizedPnJid)) {
        return;
    }

    const existingPn = lidToPnMap.get(lidParts.baseUser);
    if (existingPn && existingPn !== pnParts.baseUser) {
        console.warn(`[WA Runtime] LID mapping conflict ${lidParts.baseUser}: ${existingPn} -> ${pnParts.baseUser} (${source})`);
    }

    lidToPnMap.set(lidParts.baseUser, pnParts.baseUser);
    pnToLidMap.set(pnParts.baseUser, lidParts.baseUser);
    lidMapDirty = true;
};

const tryLearnLidPair = (leftJid, rightJid, source = 'message') => {
    const left = String(leftJid || '').trim();
    const right = String(rightJid || '').trim();
    if (!left || !right || left === right) {
        return;
    }

    if (isLidJid(left) && isPnJid(right)) {
        rememberLidPnPair(left, right, source);
        return;
    }

    if (isPnJid(left) && isLidJid(right)) {
        rememberLidPnPair(right, left, source);
    }
};

const resolvePnJidFromAnyJid = (jid) => {
    const input = String(jid || '').trim();
    if (!input) {
        return '';
    }

    if (!isLidJid(input)) {
        return input;
    }

    const parts = decodeJidParts(input);
    if (!parts) {
        return input;
    }

    const mappedPnUser = lidToPnMap.get(parts.baseUser);
    if (!mappedPnUser) {
        return input;
    }

    return withPnServer(mappedPnUser, parts.device);
};

const stripJidSuffix = (jid) => {
    const value = String(jid || '').trim();
    if (!value) {
        return '';
    }
    return value.replace(/@(s\.whatsapp\.net|g\.us|lid)$/i, '');
};

const normalizeTimestampSeconds = (value) => {
    if (value == null) {
        return null;
    }

    if (typeof value === 'object' && value !== null) {
        if (typeof value.low === 'number') {
            return value.low;
        }
        if (typeof value.toNumber === 'function') {
            try {
                return value.toNumber();
            } catch {
                return null;
            }
        }
    }

    const numeric = Number(value);
    if (!Number.isFinite(numeric) || numeric <= 0) {
        return null;
    }

    return numeric > 9999999999 ? Math.floor(numeric / 1000) : Math.floor(numeric);
};

const isWithinHistoryWindow = (timestampSeconds) => {
    if (!HISTORY_SYNC_MAX_DAYS || HISTORY_SYNC_MAX_DAYS <= 0) {
        return true;
    }

    if (!timestampSeconds) {
        return true;
    }

    const cutoffSeconds = Math.floor(Date.now() / 1000) - (HISTORY_SYNC_MAX_DAYS * 86400);
    return timestampSeconds >= cutoffSeconds;
};

const initHistorySyncRun = () => {
    historySyncState = {
        runKey: `history-${Date.now()}`,
        status: 'running',
        progress: 0,
        startedAt: new Date().toISOString(),
        finishedAt: null,
        lastEventAt: new Date().toISOString(),
        received: 0,
        imported: 0,
        duplicates: 0,
        failed: 0,
    };
};

const parseMedia = async (msg, options = {}) => {
    const { includeBinary = true } = options;
    const message = msg?.message || {};

    const readBufferAsDataUrl = (mime, bufferLike) => {
        if (!bufferLike) return null;

        let buff = null;
        if (Buffer.isBuffer(bufferLike)) {
            buff = bufferLike;
        } else if (Array.isArray(bufferLike?.data)) {
            buff = Buffer.from(bufferLike.data);
        } else if (Array.isArray(bufferLike)) {
            buff = Buffer.from(bufferLike);
        }

        if (!buff || buff.length === 0) {
            return null;
        }

        return `data:${mime};base64,${buff.toString('base64')}`;
    };

    const buildMediaPayload = (type, payload, fallbackMime) => {
        if (!payload) return null;

        const mime = payload.mimetype || fallbackMime || (type === 'sticker' ? 'image/webp' : 'image/jpeg');
        const thumb = payload.jpegThumbnail || payload.pngThumbnail || null;

        return {
            kind: type,
            mimetype: mime,
            caption: payload.caption || '',
            fileName: payload.fileName || (type === 'sticker' ? 'sticker.webp' : (payload.fileSha256?.toString?.() || null)),
            previewDataUrl: readBufferAsDataUrl(
                payload.jpegThumbnail ? 'image/jpeg' : (payload.pngThumbnail ? 'image/png' : mime),
                thumb,
            ),
            dataUrl: null,
        };
    };

    const enrichWithBinaryData = async (media, fallbackMime) => {
        try {
            const buffer = await downloadMediaMessage(
                msg,
                'buffer',
                {},
                { logger, reuploadRequest: sock.updateMediaMessage },
            );

            if (!buffer) {
                return media;
            }

            media.byteLength = buffer.length;
            if (buffer.length <= MAX_INLINE_MEDIA_BYTES) {
                media.dataUrl = `data:${media.mimetype || fallbackMime};base64,${buffer.toString('base64')}`;
                media.inline = true;
            } else {
                // File too large to inline — store in memory and return a download token
                const token = crypto.randomBytes(16).toString('hex');
                mediaStore.set(token, {
                    buffer,
                    mime: media.mimetype || fallbackMime,
                    fileName: media.fileName || 'media',
                    createdAt: Date.now(),
                });
                media.inline = false;
                media.dataUrl = null;
                media.mediaToken = token;
            }
        } catch (err) {
            console.warn('[WA Runtime] media download failed:', err?.message || err);
        }

        return media;
    };

    if (message.imageMessage) {
        const media = buildMediaPayload('image', message.imageMessage, 'image/jpeg');
        return includeBinary ? enrichWithBinaryData(media, 'image/jpeg') : media;
    }

    if (message.stickerMessage) {
        const media = buildMediaPayload('sticker', message.stickerMessage, 'image/webp');
        return includeBinary ? enrichWithBinaryData(media, 'image/webp') : media;
    }

    if (message.documentMessage) {
        const media = buildMediaPayload('document', message.documentMessage, 'application/octet-stream');
        return includeBinary ? enrichWithBinaryData(media, 'application/octet-stream') : media;
    }

    if (message.audioMessage) {
        const media = buildMediaPayload('audio', message.audioMessage, 'audio/ogg');
        return includeBinary ? enrichWithBinaryData(media, 'audio/ogg') : media;
    }

    if (message.videoMessage) {
        const media = buildMediaPayload('video', message.videoMessage, 'video/mp4');
        return includeBinary ? enrichWithBinaryData(media, 'video/mp4') : media;
    }

    return null;
};

const cleanDisplayName = (value) => {
    const text = String(value || '').trim();
    if (!text) return null;
    if (SUPPORTED_JID_SUFFIXES.some((suffix) => text.endsWith(suffix))) return null;
    return text;
};

const contactNameFromRecord = (contact) => {
    if (!contact || typeof contact !== 'object') return null;
    return cleanDisplayName(
        contact.name
        || contact.notify
        || contact.verifiedName
        || contact.pushName
        || contact.short
        || contact.subject
        || null,
    );
};

const resolveChatMeta = async (jid) => {
    const normalized = String(jid || '').trim();
    if (!normalized || !sock) {
        return { jid: normalized, displayName: null, groupName: null, profilePhotoUrl: null, resolvedJid: null };
    }

    const candidateJids = [normalized];
    const resolvedPnJid = resolvePnJidFromAnyJid(normalized);
    if (resolvedPnJid && !candidateJids.includes(resolvedPnJid)) {
        candidateJids.push(resolvedPnJid);
    }

    let displayName = null;
    let groupName = null;
    let profilePhotoUrl = null;

    if (normalized.endsWith('@g.us')) {
        try {
            const groupMeta = await sock.groupMetadata(normalized);
            groupName = cleanDisplayName(groupMeta?.subject || groupMeta?.name || null);
            displayName = groupName;
        } catch (err) {
            console.warn('[WA Runtime] groupMetadata failed:', err?.message || err);
        }
    }

    for (const candidateJid of candidateJids) {
        if (displayName) break;
        displayName = contactNameFromRecord(sock?.contacts?.[candidateJid]);
    }

    for (const candidateJid of candidateJids) {
        if (profilePhotoUrl) break;
        try {
            const url = await sock.profilePictureUrl(candidateJid, 'image');
            if (url) {
                profilePhotoUrl = url;
            }
        } catch (err) {
            // No profile photo or runtime cannot resolve it; keep fallback null.
        }
    }

    return {
        jid: normalized,
        displayName,
        groupName,
        profilePhotoUrl,
        resolvedJid: candidateJids[candidateJids.length - 1] || normalized,
    };
};

const extractTextAndType = (msg) => {
    if (msg.message?.conversation) {
        return { text: msg.message.conversation, type: 'text' };
    }
    if (msg.message?.extendedTextMessage) {
        return { text: msg.message.extendedTextMessage.text || '', type: 'text' };
    }
    if (msg.message?.imageMessage) {
        return { text: msg.message.imageMessage?.caption || '', type: 'image' };
    }
    if (msg.message?.stickerMessage) {
        return { text: '', type: 'sticker' };
    }
    if (msg.message?.documentMessage) {
        return { text: msg.message.documentMessage?.title || msg.message.documentMessage?.fileName || '', type: 'document' };
    }
    if (msg.message?.audioMessage) {
        return { text: '', type: 'audio' };
    }
    if (msg.message?.videoMessage) {
        return { text: msg.message.videoMessage?.caption || '', type: 'video' };
    }
    return { text: '', type: 'text' };
};

const buildWebhookPayloadFromMessage = async (msg, options = {}) => {
    const {
        syncSource = 'realtime',
        includeBinaryMedia = syncSource !== 'history',
        cachedMeta = null,
        syncMeta = {},
    } = options;

    const chatId = msg?.key?.remoteJid || '';
    const remoteJidAlt = msg?.key?.remoteJidAlt || '';
    const participant = msg?.key?.participant || '';
    const participantAlt = msg?.key?.participantAlt || '';
    const isGroup = chatId.endsWith('@g.us');
    const direction = msg?.key?.fromMe ? 'outbound' : 'inbound';
    const directSenderJid = isGroup
        ? (participant || participantAlt || chatId)
        : (direction === 'outbound' ? chatId : (participant || chatId || remoteJidAlt));
    const resolvedSenderJid = resolvePnJidFromAnyJid(directSenderJid);
    const timestampSeconds = normalizeTimestampSeconds(msg?.messageTimestamp);
    if (syncSource === 'history' && !isWithinHistoryWindow(timestampSeconds)) {
        return null;
    }

    tryLearnLidPair(chatId, remoteJidAlt, `${syncSource}.remote`);
    tryLearnLidPair(participant, participantAlt, `${syncSource}.participant`);

    const media = await parseMedia(msg, { includeBinary: includeBinaryMedia });
    const { text, type } = extractTextAndType(msg);
    const chatMeta = cachedMeta || await resolveChatMeta(isGroup ? chatId : (resolvedSenderJid || directSenderJid));
    const fromLid = [directSenderJid, remoteJidAlt, participantAlt].find((jid) => isLidJid(jid)) || null;
    const fromPn = [resolvedSenderJid, directSenderJid, remoteJidAlt, participant, participantAlt].find((jid) => isPnJid(jid)) || null;

    return {
        chatId,
        from: stripJidSuffix(resolvedSenderJid || directSenderJid),
        fromRaw: directSenderJid,
        fromLid,
        fromPn,
        resolvedFromJid: resolvedSenderJid || directSenderJid,
        isLid: Boolean(fromLid),
        to: user?.id?.split(':')[0] || '',
        text,
        type,
        id: msg?.key?.id,
        media,
        isGroup,
        participant,
        participantAlt,
        pushName: msg?.pushName || '',
        senderName: !isGroup ? (chatMeta?.displayName || msg?.pushName || '') : '',
        groupName: isGroup ? (chatMeta?.groupName || '') : '',
        profilePhotoUrl: chatMeta?.profilePhotoUrl || '',
        direction,
        fromMe: Boolean(msg?.key?.fromMe),
        timestamp: timestampSeconds,
        syncSource,
        historySync: syncSource === 'history',
        ...syncMeta,
        raw: msg,
    };
};

const appendToMessagesStore = (payload) => {
    messagesStore.push(payload);
    if (messagesStore.length > 500) {
        messagesStore.shift();
    }
};

const postWebhookPayload = async (payload) => {
    await axios.post(WEBHOOK_URL, payload, {
        headers: { 'X-WA-V2-Token': AUTH_TOKEN },
    });
};

const postHistorySyncBatch = async (payloads, syncMeta = {}) => {
    if (!payloads.length) {
        return;
    }

    const response = await axios.post(HISTORY_SYNC_WEBHOOK_URL, {
        runKey: historySyncState.runKey,
        startedAt: historySyncState.startedAt,
        connectionJid: user?.id || null,
        deviceLabel: 'Desktop',
        syncType: syncMeta.syncType || null,
        progress: syncMeta.progress ?? historySyncState.progress ?? 0,
        isLatest: Boolean(syncMeta.isLatest),
        chats: syncMeta.chats || [],
        contacts: syncMeta.contacts || [],
        messages: payloads,
    }, {
        headers: { 'X-WA-V2-Token': AUTH_TOKEN },
    });

    const data = response?.data || {};
    historySyncState.imported += Number(data.imported || 0);
    historySyncState.duplicates += Number(data.duplicates || 0);
    historySyncState.failed += Number(data.failed || 0);
    historySyncState.progress = Number(data.progress ?? syncMeta.progress ?? historySyncState.progress ?? 0);
    historySyncState.lastEventAt = new Date().toISOString();
    historySyncState.status = data.status || (syncMeta.isLatest ? 'completed' : 'running');

    if (syncMeta.isLatest) {
        historySyncState.finishedAt = new Date().toISOString();
    }
};

const startSock = async () => {
    const { state, saveCreds } = await useMultiFileAuthState('baileys_auth_info');
    const { version } = await fetchLatestBaileysVersion();
    
    sock = makeWASocket({
        version,
        logger,
        auth: state,
        browser: Browsers.macOS('Desktop'),
        syncFullHistory: HISTORY_SYNC_ENABLED,
        shouldSyncHistoryMessage: () => HISTORY_SYNC_ENABLED,
    });

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('lid-mapping.update', ({ lid, pn }) => {
        tryLearnLidPair(lid, pn, 'lid-mapping.update');
    });

    sock.ev.on('contacts.upsert', (contacts = []) => {
        for (const c of contacts) {
            tryLearnLidPair(c?.lid, c?.phoneNumber, 'contacts.upsert');
            tryLearnLidPair(c?.id, c?.phoneNumber, 'contacts.upsert');
            tryLearnLidPair(c?.id, c?.lid, 'contacts.upsert');
        }
    });

    sock.ev.on('contacts.update', (contacts = []) => {
        for (const c of contacts) {
            tryLearnLidPair(c?.lid, c?.phoneNumber, 'contacts.update');
            tryLearnLidPair(c?.id, c?.phoneNumber, 'contacts.update');
            tryLearnLidPair(c?.id, c?.lid, 'contacts.update');
        }
    });

    sock.ev.on('connection.update', (update) => {
        const { connection, lastDisconnect, qr } = update;
        
        if (qr) {
            currentQR = qr;
        }

        if (connection === 'close') {
            isConnected = false;
            user = null;
            const shouldReconnect = (lastDisconnect?.error)?.output?.statusCode !== DisconnectReason.loggedOut;
            console.log('connection closed due to ', lastDisconnect?.error, ', reconnecting ', shouldReconnect);
            if (shouldReconnect) {
                startSock();
            } else {
                currentQR = '';
                console.log('Connection closed. You need to restart/reconnect and scan QR again.');
            }
        } else if (connection === 'open') {
            isConnected = true;
            currentQR = '';
            user = sock.user;
            console.log('opened connection');
            if (HISTORY_SYNC_ENABLED) {
                initHistorySyncRun();
            }

            // Scan stored contacts to populate LID<->PN mappings
            setTimeout(() => {
                try {
                    const contacts = sock.contacts || {};
                    let learned = 0;
                    for (const [jid, c] of Object.entries(contacts)) {
                        if (c?.lid) { tryLearnLidPair(c.lid, jid, 'contacts.scan'); learned++; }
                        if (c?.phoneNumber) tryLearnLidPair(jid, c.phoneNumber, 'contacts.scan');
                    }
                    if (learned > 0) {
                        persistLidMappings();
                        console.log(`[WA Runtime] Scanned ${learned} contacts, LID mappings: ${lidToPnMap.size}`);
                    }
                } catch (e) {
                    console.warn('[WA Runtime] contacts scan failed:', e?.message);
                }
            }, 3000);
        }
    });

    sock.ev.on('messages.upsert', async m => {
        if (m.type !== 'notify') return;
        
        for (let msg of m.messages) {
            if (msg.key.fromMe) continue;
            const payload = await buildWebhookPayloadFromMessage(msg, { syncSource: 'realtime' });
            if (!payload) continue;
            appendToMessagesStore(payload);

            // Post to webhook
            try {
                await postWebhookPayload(payload);
                console.log('Webhook delivered successfully');
            } catch (err) {
                console.error('Failed to post to webhook', err.message);
            }
        }
    });

    sock.ev.on('messaging-history.set', async ({ chats = [], contacts = [], messages = [], syncType = null, progress = null, isLatest = false }) => {
        if (!HISTORY_SYNC_ENABLED || !Array.isArray(messages) || messages.length === 0) {
            return;
        }

        if (!historySyncState.runKey) {
            initHistorySyncRun();
        }

        historySyncState.status = 'running';
        historySyncState.progress = Number(progress ?? historySyncState.progress ?? 0);
        historySyncState.lastEventAt = new Date().toISOString();
        historySyncState.received += messages.length;

        const contactMap = new Map((contacts || []).map((item) => [item.id, item]));
        const chatMap = new Map((chats || []).map((item) => [item.id, item]));
        const payloads = [];

        for (const msg of messages) {
            const chatId = msg?.key?.remoteJid || '';
            const chatRecord = chatMap.get(chatId);
            const contactRecord = contactMap.get(chatId);
            const cachedMeta = {
                displayName: contactNameFromRecord(contactRecord) || cleanDisplayName(msg?.pushName || null),
                groupName: cleanDisplayName(chatRecord?.name || chatRecord?.subject || contactRecord?.subject || null),
                profilePhotoUrl: null,
            };

            const payload = await buildWebhookPayloadFromMessage(msg, {
                syncSource: 'history',
                includeBinaryMedia: false,
                cachedMeta,
                syncMeta: {
                    syncType: syncType != null ? String(syncType) : null,
                    progress: Number(progress ?? 0),
                    isLatest: Boolean(isLatest),
                },
            });

            if (!payload) continue;
            payloads.push(payload);
        }

        for (let i = 0; i < payloads.length; i += HISTORY_SYNC_BATCH_SIZE) {
            const batch = payloads.slice(i, i + HISTORY_SYNC_BATCH_SIZE);
            try {
                await postHistorySyncBatch(batch, {
                    syncType: syncType != null ? String(syncType) : null,
                    progress: Number(progress ?? 0),
                    isLatest: Boolean(isLatest) && (i + HISTORY_SYNC_BATCH_SIZE >= payloads.length),
                    chats: i === 0 ? chats : [],
                    contacts: i === 0 ? contacts : [],
                });
            } catch (err) {
                historySyncState.failed += batch.length;
                historySyncState.status = 'failed';
                console.error('[WA Runtime] history sync webhook failed:', err?.message || err);
            }
        }

        if (isLatest) {
            historySyncState.status = historySyncState.status === 'failed' ? 'failed' : 'completed';
            historySyncState.finishedAt = new Date().toISOString();
        }
    });
};

app.use((req, res, next) => {
    const token = req.header('X-WA-V2-Token');
    if (token !== AUTH_TOKEN && req.path !== '/health') {
        // We will allow health without token just in case or allow if it matches
        // Actually, let's enforce token on all except health
        if (req.path !== '/health' && req.path !== '/qr') {
             return res.status(401).json({ ok: false, error: 'Unauthorized payload' });
        }
    }
    next();
});

app.get('/health', (req, res) => {
    res.json({
        ok: true,
        status: isConnected ? 'connected' : 'disconnected',
        user: user,
        historySync: historySyncState,
    });
});

app.get('/qr', async (req, res) => {
    if (isConnected) {
        return res.json({ ok: false, error: 'Already connected' });
    }
    
    if (!currentQR) {
         return res.json({ ok: false, error: 'QR is not ready yet' });
    }

    try {
        const url = await QRCode.toDataURL(currentQR);
        res.json({ ok: true, qr: currentQR, qrDataUrl: url });
    } catch (err) {
        res.status(502).json({ ok: false, error: 'Failed to generate QR data URL' });
    }
});

app.post('/restart', async (req, res) => {
    if (sock) {
        sock.end(undefined);
    }
    await delay(1000);
    startSock();
    res.json({ ok: true });
});

app.post('/reconnect', async (req, res) => {
    if (sock) {
        sock.end(undefined);
    }
    await delay(1000);
    startSock();
    res.json({ ok: true });
});

app.post('/disconnect', async (req, res) => {
    if(sock) {
        await sock.logout();
        isConnected = false;
        user = null;
        res.json({ ok: true });
    } else {
        res.json({ ok: false, error: 'No socket attached' });
    }
});

app.get('/history', (req, res) => {
    res.json({ ok: true, history: messagesStore });
});

app.get('/history-sync/status', (req, res) => {
    res.json({ ok: true, historySync: historySyncState });
});

app.post('/contacts/sync', (req, res) => {
    if (!isConnected || !sock?.contacts) {
        return res.status(503).json({ ok: false, error: 'Device belum terhubung.' });
    }
    try {
        const contacts = sock.contacts || {};
        let learned = 0;
        for (const [jid, c] of Object.entries(contacts)) {
            if (c?.lid) { tryLearnLidPair(c.lid, jid, 'contacts.sync'); learned++; }
            if (c?.phoneNumber) tryLearnLidPair(jid, c.phoneNumber, 'contacts.sync');
        }
        if (learned > 0) persistLidMappings();
        return res.json({ ok: true, scanned: Object.keys(contacts).length, learned, lidMappings: lidToPnMap.size });
    } catch (e) {
        return res.status(500).json({ ok: false, error: e?.message });
    }
});

app.get('/lid-mappings', (req, res) => {
    const pairs = Array.from(lidToPnMap.entries()).map(([lid, pn]) => ({
        lid: withLidServer(lid),
        pn: withPnServer(pn),
    }));

    res.json({
        ok: true,
        total: pairs.length,
        pairs,
    });
});

app.post('/lid-mappings/resolve', (req, res) => {
    const { lid } = req.body || {};
    if (!lid || !isLidJid(String(lid))) {
        return res.status(422).json({ ok: false, error: 'Field lid harus JID @lid yang valid' });
    }

    const resolved = resolvePnJidFromAnyJid(String(lid));
    const resolvedOk = resolved && resolved !== String(lid);
    return res.json({ ok: true, lid, resolved: resolvedOk ? resolved : null });
});

app.post('/webhook/register', (req, res) => {
    const { url, historyUrl } = req.body;
    if (url) {
        WEBHOOK_URL = url;
    }
    if (historyUrl) {
        HISTORY_SYNC_WEBHOOK_URL = historyUrl;
    }
    if (url || historyUrl) {
        res.json({ ok: true, registered: true, callbackUrl: WEBHOOK_URL, historySyncUrl: HISTORY_SYNC_WEBHOOK_URL });
    } else {
        res.status(400).json({ ok: false, error: 'URL is required' });
    }
});

app.post('/contacts/resolve', async (req, res) => {
    const { jids } = req.body || {};

    if (!Array.isArray(jids)) {
        return res.status(422).json({ ok: false, error: 'Field jids harus berupa array.' });
    }

    if (!isConnected || !sock) {
        return res.status(503).json({ ok: false, error: 'Device belum terhubung.' });
    }

    try {
        const items = [];
        for (const jid of jids) {
            items.push(await resolveChatMeta(jid));
        }

        return res.json({ ok: true, items });
    } catch (err) {
        return res.status(500).json({ ok: false, error: err?.message || 'Gagal resolve kontak.' });
    }
});

app.post('/history/clear', (req, res) => {
    messagesStore = [];
    res.json({ ok: true });
});

app.post('/send-text', async (req, res) => {
    const { to, text } = req.body;
    if (!isConnected) {
        return res.status(502).json({ ok: false, error: 'Device disconnected' });
    }
    try {
        const jid = normalizeOutgoingJid(to);
        if (!jid) {
            return res.status(422).json({ ok: false, error: 'Destination number/JID is required' });
        }
        const sentMsg = await sock.sendMessage(jid, { text: text });
        res.json({ ok: true, messageId: sentMsg.key.id, data: sentMsg });
    } catch(err) {
        res.status(502).json({ ok: false, error: err.message });
    }
});

app.post('/send-media', async (req, res) => {
    const { to, media_kind, media_url, mime_type, file_name, caption, ptt } = req.body || {};

    if (!isConnected) {
        return res.status(502).json({ ok: false, error: 'Device disconnected' });
    }

    const kind = normalizeMediaKind(media_kind);
    if (!kind) {
        return res.status(422).json({ ok: false, error: 'media_kind wajib: image|video|audio|document|sticker' });
    }

    const jid = normalizeOutgoingJid(to);
    if (!jid) {
        return res.status(422).json({ ok: false, error: 'Destination number/JID is required' });
    }

    const mediaInput = String(media_url || '').trim();
    if (!mediaInput) {
        return res.status(422).json({ ok: false, error: 'media_url wajib diisi' });
    }

    let mediaSource;
    let resolvedMimeType = inferMimeType(mime_type, file_name, mediaInput, kind);

    if (mediaInput.startsWith('data:')) {
        const parsed = parseDataUrl(mediaInput);
        if (!parsed) {
            return res.status(422).json({ ok: false, error: 'Format data URL tidak valid' });
        }

        if (parsed.buffer.length > MAX_UPLOAD_MEDIA_BYTES) {
            return res.status(422).json({
                ok: false,
                error: `Ukuran file melebihi batas maksimum ${Math.round(MAX_UPLOAD_MEDIA_BYTES / (1024 * 1024))} MB`,
            });
        }

        mediaSource = parsed.buffer;
        resolvedMimeType = inferMimeType(resolvedMimeType || parsed.mime, file_name, mediaInput, kind);
        
        // For large files passed as base64, some Baileys operations may require
        // actual buffer instead of keeping as-is. This is already handled above.
        console.log(`[WA Runtime] Processing base64 media: kind=${kind}, size=${mediaSource?.length} bytes, jid=${jid}`);
    } else if (mediaInput.startsWith('http://') || mediaInput.startsWith('https://')) {
        mediaSource = { url: mediaInput };
    } else {
        return res.status(422).json({ ok: false, error: 'media_url harus data URL base64 atau URL http/https' });
    }

    const payload = {};
    if (kind === 'image') {
        payload.image = mediaSource;
        if (caption) payload.caption = String(caption);
        if (resolvedMimeType) payload.mimetype = resolvedMimeType;
        if (file_name) payload.fileName = String(file_name);
    } else if (kind === 'sticker') {
        payload.sticker = mediaSource;
        payload.mimetype = resolvedMimeType || 'image/webp';
    } else if (kind === 'video') {
        payload.video = mediaSource;
        if (caption) payload.caption = String(caption);
        if (resolvedMimeType) payload.mimetype = resolvedMimeType;
        if (file_name) payload.fileName = String(file_name);
    } else if (kind === 'audio') {
        payload.audio = mediaSource;
        if (resolvedMimeType) payload.mimetype = resolvedMimeType;
        payload.ptt = Boolean(ptt);
        if (file_name) payload.fileName = String(file_name);
    } else {
        payload.document = mediaSource;
        if (caption) payload.caption = String(caption);
        if (resolvedMimeType) payload.mimetype = resolvedMimeType;
        if (file_name) payload.fileName = String(file_name);
    }

    try {
        const sentMsg = await sock.sendMessage(jid, payload);
        return res.json({
            ok: true,
            messageId: sentMsg?.key?.id,
            data: sentMsg,
            media: {
                kind,
                mimetype: resolvedMimeType,
                fileName: file_name || null,
                caption: caption || '',
                inline: mediaInput.startsWith('data:'),
            },
        });
    } catch (err) {
        const errorMsg = err?.message || err?.toString?.() || 'Failed to send media';
        console.error(`[WA Runtime] send-media error for JID ${jid}, kind ${kind}:`, errorMsg);
        console.error('[WA Runtime] Full error:', err);
        return res.status(502).json({ 
            ok: false, 
            error: errorMsg,
            details: {
                jid,
                kind,
                hasMediaSource: !!mediaSource,
                payloadKeys: Object.keys(payload)
            }
        });
    }
});

app.get('/messages', (req, res) => {
    res.json({ ok: true, messages: messagesStore });
});

// Serve stored media (large files that couldn't be inlined as base64)
// Called by the Laravel proxy at /wa-caraka/media/{token}
app.get(/^\/internal\/media\/([a-f0-9]{32,})(?:\.[^/]+|\/.*)?$/, (req, res) => {
    const token = req.params[0];
    if (!/^[a-f0-9]{32,}$/.test(token)) {
        return res.status(400).json({ ok: false, error: 'Invalid token format' });
    }
    const entry = mediaStore.get(token);
    if (!entry) {
        return res.status(404).json({ ok: false, error: 'Media not found or expired' });
    }
    res.setHeader('Content-Type', entry.mime);
    res.setHeader('Content-Length', entry.buffer.length);
    res.setHeader('Content-Disposition', `attachment; filename="${entry.fileName}"`);
    res.setHeader('Cache-Control', 'private, max-age=3600');
    return res.send(entry.buffer);
});

app.listen(PORT, () => {
    console.log(`WA Runtime listening on port ${PORT}`);
    loadLidMappings();
    startSock();
    setInterval(persistLidMappings, 10_000);
});

process.on('SIGINT', () => {
    persistLidMappings();
    process.exit(0);
});

process.on('SIGTERM', () => {
    persistLidMappings();
    process.exit(0);
});
