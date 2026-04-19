import { makeWASocket, useMultiFileAuthState, DisconnectReason, Browsers, fetchLatestBaileysVersion, delay, downloadMediaMessage, isLidUser, isPnUser, jidDecode } from '@whiskeysockets/baileys';
import express from 'express';
import bodyParser from 'body-parser';
import pino from 'pino';
import axios from 'axios';
import QRCode from 'qrcode';
import fs from 'fs';

const app = express();
app.use(bodyParser.json());

const PORT = 8790;
const AUTH_TOKEN = 'lawangsewu2026';
let WEBHOOK_URL = process.env.WEBHOOK_CALLBACK_URL || 'https://lawangsewu.pa-semarang.go.id/api/wa-caraka/webhook/inbound';
const LID_MAP_FILE = process.env.WA_LID_MAP_FILE || 'lid-pn-map.json';

const logger = pino({ level: 'silent' });

let sock;
let currentQR = '';
let isConnected = false;
let user = null;
let messagesStore = [];
let lidToPnMap = new Map();
let pnToLidMap = new Map();
let lidMapDirty = false;

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

const parseMedia = async (msg) => {
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
            fileName: payload.fileName || payload.fileSha256?.toString?.() || null,
            previewDataUrl: readBufferAsDataUrl(
                payload.jpegThumbnail ? 'image/jpeg' : (payload.pngThumbnail ? 'image/png' : mime),
                thumb,
            ),
            dataUrl: null,
        };
    };

    if (message.imageMessage) {
        const media = buildMediaPayload('image', message.imageMessage, 'image/jpeg');
        try {
            const buffer = await downloadMediaMessage(
                msg,
                'buffer',
                {},
                { logger, reuploadRequest: sock.updateMediaMessage },
            );
            if (buffer) {
                media.dataUrl = `data:${media.mimetype};base64,${buffer.toString('base64')}`;
            }
        } catch (err) {
            console.warn('[WA Runtime] image download failed:', err?.message || err);
        }

        return media;
    }

    if (message.stickerMessage) {
        const media = buildMediaPayload('sticker', message.stickerMessage, 'image/webp');
        try {
            const buffer = await downloadMediaMessage(
                msg,
                'buffer',
                {},
                { logger, reuploadRequest: sock.updateMediaMessage },
            );
            if (buffer) {
                media.dataUrl = `data:${media.mimetype || 'image/webp'};base64,${buffer.toString('base64')}`;
            }
        } catch (err) {
            console.warn('[WA Runtime] sticker download failed:', err?.message || err);
        }

        return media;
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

const startSock = async () => {
    const { state, saveCreds } = await useMultiFileAuthState('baileys_auth_info');
    const { version } = await fetchLatestBaileysVersion();
    
    sock = makeWASocket({
        version,
        logger,
        auth: state,
        browser: Browsers.macOS('Desktop'),
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
            
            const chatId = msg?.key?.remoteJid || '';
            const remoteJidAlt = msg?.key?.remoteJidAlt || '';
            const participant = msg?.key?.participant || '';
            const participantAlt = msg?.key?.participantAlt || '';

            tryLearnLidPair(chatId, remoteJidAlt, 'messages.upsert.remote');
            tryLearnLidPair(participant, participantAlt, 'messages.upsert.participant');

            const isGroup = chatId.endsWith('@g.us');
            const directSenderJid = isGroup ? (participant || participantAlt || chatId) : (chatId || remoteJidAlt);
            const resolvedSenderJid = resolvePnJidFromAnyJid(directSenderJid);
            const from = stripJidSuffix(resolvedSenderJid || directSenderJid);
            const id = msg.key.id;
            let text = '';
            let type = 'text';

            const media = await parseMedia(msg);

            if (msg.message?.conversation) {
                text = msg.message.conversation;
            } else if (msg.message?.extendedTextMessage) {
                text = msg.message.extendedTextMessage.text;
            } else if (msg.message?.imageMessage) {
                text = msg.message.imageMessage?.caption || '';
                type = 'image';
            } else if (msg.message?.stickerMessage) {
                text = '';
                type = 'sticker';
            } else if (msg.message?.documentMessage) {
                text = msg.message.documentMessage?.title || '';
                type = 'document';
            } else if (msg.message?.audioMessage) {
                text = '';
                type = 'audio';
            } else if (msg.message?.videoMessage) {
                text = msg.message.videoMessage?.caption || '';
                type = 'video';
            }

            const fromLid = [directSenderJid, remoteJidAlt, participantAlt].find((jid) => isLidJid(jid)) || null;
            const fromPn = [resolvedSenderJid, directSenderJid, remoteJidAlt, participant, participantAlt].find((jid) => isPnJid(jid)) || null;
            const chatMeta = await resolveChatMeta(isGroup ? chatId : (resolvedSenderJid || directSenderJid));

            const payload = {
                chatId,
                from,
                fromRaw: directSenderJid,
                fromLid,
                fromPn,
                resolvedFromJid: resolvedSenderJid || directSenderJid,
                isLid: Boolean(fromLid),
                to: user?.id?.split(':')[0] || '',
                text,
                type,
                id,
                media,
                isGroup,
                participant,
                participantAlt,
                pushName: msg?.pushName || '',
                senderName: !isGroup ? (chatMeta.displayName || msg?.pushName || '') : '',
                groupName: isGroup ? (chatMeta.groupName || '') : '',
                profilePhotoUrl: chatMeta.profilePhotoUrl || '',
                raw: msg
            };

            messagesStore.push(payload);
            if(messagesStore.length > 500) {
                messagesStore.shift();
            }

            // Post to webhook
            try {
                await axios.post(WEBHOOK_URL, payload, {
                    headers: {
                        'X-WA-V2-Token': AUTH_TOKEN
                    }
                });
                console.log('Webhook delivered successfully');
            } catch (err) {
                console.error('Failed to post to webhook', err.message);
            }
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
        user: user
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
    const { url } = req.body;
    if (url) {
        WEBHOOK_URL = url;
        res.json({ ok: true, registered: true, callbackUrl: WEBHOOK_URL });
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

app.get('/messages', (req, res) => {
    res.json({ ok: true, messages: messagesStore });
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
