// ─── WA Caraka Personal Runtime ──────────────────────────────────────────────
// Baileys-based WhatsApp bridge untuk nomor pribadi.
// Dijalankan di WSL, terhubung ke server Lawangsewu via Tailscale.
//
// Endpoint yang disediakan:
//   GET  /health              — Cek status koneksi WA
//   GET  /qr                  — Ambil QR code (base64 PNG)
//   GET  /contacts/resolve    — Resolve LID ke nomor telepon
//   POST /send-text           — Kirim pesan teks
//   POST /send-media          — Kirim pesan media
//   POST /unsend-message      — Recall pesan dari WA
//   GET  /messages            — Ambil pesan dari kontak tertentu
//
// developed by dbprakom™

import 'dotenv/config';
import express from 'express';
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';
import pino from 'pino';
import makeWASocket, {
    DisconnectReason,
    useMultiFileAuthState,
    fetchLatestBaileysVersion,
    makeCacheableSignalKeyStore,
    jidNormalizedUser,
    getContentType,
    downloadContentFromMessage,
    generateWAMessageFromContent,
    proto,
} from '@whiskeysockets/baileys';
import { Boom } from '@hapi/boom';
import axios from 'axios';

// ── Config ──────────────────────────────────────────────────────────────────
const __dirname = dirname(fileURLToPath(import.meta.url));

const PORT           = parseInt(process.env.PORT           || '8791', 10);
const API_TOKEN      = process.env.API_TOKEN               || '';
const WEBHOOK_URL    = process.env.WEBHOOK_URL             || ''; // Laravel webhook endpoint
const WEBHOOK_TOKEN  = process.env.WEBHOOK_TOKEN           || ''; // Token ke Laravel
const HISTORY_MAX_DAYS = parseInt(process.env.HISTORY_MAX_DAYS || '30', 10);
const AUTH_DIR       = join(__dirname, 'auth_info');
const LOG_LEVEL      = process.env.LOG_LEVEL               || 'info';

if (!existsSync(AUTH_DIR)) mkdirSync(AUTH_DIR, { recursive: true });

// ── Logger ───────────────────────────────────────────────────────────────────
const logger = pino({
    level: LOG_LEVEL,
    transport: process.env.NODE_ENV !== 'production'
        ? { target: 'pino-pretty', options: { colorize: true } }
        : undefined,
});

// ── State ────────────────────────────────────────────────────────────────────
let sock        = null;
let qrCode      = null; // base64 QR PNG
let connStatus  = 'disconnected'; // 'disconnected' | 'connecting' | 'qr_ready' | 'connected'
let connInfo    = null; // info device saat connected
let reconnectTimer = null;

// ── App ──────────────────────────────────────────────────────────────────────
const app = express();
app.use(express.json({ limit: '25mb' }));
app.use(express.urlencoded({ extended: true, limit: '25mb' }));

// ── Auth Middleware ───────────────────────────────────────────────────────────
const authenticate = (req, res, next) => {
    if (!API_TOKEN) return next(); // Token tidak dikonfigurasi = akses bebas (dev mode)
    const token = req.headers['x-wa-v2-token'] || req.query.token;
    if (token !== API_TOKEN) {
        return res.status(401).json({ ok: false, error: 'Unauthorized' });
    }
    next();
};

// ── Baileys: Connect WA ───────────────────────────────────────────────────────
async function connectWA() {
    const { state, saveCreds } = await useMultiFileAuthState(AUTH_DIR);
    const { version } = await fetchLatestBaileysVersion();

    logger.info({ version }, 'Connecting WA with Baileys version');

    connStatus = 'connecting';
    qrCode = null;

    sock = makeWASocket({
        version,
        logger: pino({ level: 'silent' }),
        auth: {
            creds: state.creds,
            keys: makeCacheableSignalKeyStore(state.keys, pino({ level: 'silent' })),
        },
        printQRInTerminal: false,
        syncFullHistory: false,
        markOnlineOnConnect: false,
        generateHighQualityLinkPreview: false,
        getMessage: async (key) => {
            // Provide message store if needed — minimal impl
            return { conversation: '' };
        },
    });

    // ── Events ──────────────────────────────────────────────────────────────
    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', async ({ connection, lastDisconnect, qr }) => {
        if (qr) {
            // Encode QR as base64 data URL menggunakan jimp
            try {
                const QRCode = await import('qrcode');
                qrCode = await QRCode.default.toDataURL(qr, {
                    errorCorrectionLevel: 'M',
                    type: 'image/png',
                    margin: 2,
                    width: 300,
                });
                connStatus = 'qr_ready';
                logger.info('QR code generated, scan from WaCaraka dashboard');
            } catch (err) {
                // Fallback: store raw QR string
                qrCode = 'data:text/plain;base64,' + Buffer.from(qr).toString('base64');
                connStatus = 'qr_ready';
            }
        }

        if (connection === 'close') {
            qrCode = null;
            const statusCode = lastDisconnect?.error?.output?.statusCode;
            const shouldReconnect = statusCode !== DisconnectReason.loggedOut;

            logger.warn({ statusCode, shouldReconnect }, 'WA connection closed');

            connStatus = 'disconnected';
            connInfo = null;

            if (shouldReconnect) {
                if (reconnectTimer) clearTimeout(reconnectTimer);
                reconnectTimer = setTimeout(() => connectWA(), 5000);
            }
        }

        if (connection === 'open') {
            connStatus = 'connected';
            qrCode = null;
            connInfo = {
                phone: sock.user?.id?.split(':')[0] || sock.user?.id || 'unknown',
                name: sock.user?.name || sock.user?.verifiedName || null,
                jid: sock.user?.id,
                platform: 'baileys',
            };
            logger.info({ connInfo }, 'WA connected!');
        }
    });

    sock.ev.on('messages.upsert', async ({ messages, type }) => {
        if (!WEBHOOK_URL) return;

        for (const msg of messages) {
            try {
                // Ignore initial history sync to avoid spamming the webhook
                if (msg.message?.protocolMessage?.type === 0 || msg.message?.protocolMessage?.type === 'HISTORY_SYNC_NOTIFICATION') continue;
                
                // Allow 'append' and 'notify'. 'append' is used when syncing sent messages from the phone.
                if (msg.key.fromMe && type !== 'notify' && type !== 'append') continue;
                
                await pushMessageToLaravel(msg);
            } catch (err) {
                logger.error({ err: err.message }, 'Failed to push message to Laravel');
            }
        }
    });
}

// ── Push pesan ke Laravel webhook ────────────────────────────────────────────
async function pushMessageToLaravel(msg) {
    if (!WEBHOOK_URL) return;

    try {
        const contentType = getContentType(msg.message);
        const body = msg.message?.conversation
            || msg.message?.extendedTextMessage?.text
            || null;

        const remoteJid = msg.key.remoteJid || '';
        const fromMe    = Boolean(msg.key.fromMe);
        const isGroup   = remoteJid.endsWith('@g.us');
        const participant = msg.key.participant || msg.participant || null;
        const pushName  = msg.pushName || null;

        // Media handling (async download jika perlu)
        let media = null;
        const mediaTypes = ['imageMessage', 'videoMessage', 'audioMessage', 'documentMessage', 'stickerMessage'];
        if (contentType && mediaTypes.includes(contentType)) {
            try {
                const mediaMsg  = msg.message[contentType];
                const stream    = await downloadContentFromMessage(mediaMsg, contentType.replace('Message', ''));
                const chunks    = [];
                for await (const chunk of stream) chunks.push(chunk);
                const buffer   = Buffer.concat(chunks);
                const mime      = mediaMsg.mimetype || 'application/octet-stream';
                const fileName  = mediaMsg.fileName || `media.${mime.split('/')[1] || 'bin'}`;
                const kind      = contentType.includes('image') || contentType.includes('sticker') ? 'image'
                                : contentType.includes('video') ? 'video'
                                : contentType.includes('audio') ? 'audio'
                                : 'document';

                media = {
                    kind,
                    mime,
                    fileName,
                    dataUrl: `data:${mime};base64,${buffer.toString('base64')}`,
                    size: buffer.length,
                    caption: mediaMsg.caption || null,
                };
            } catch (mediaErr) {
                logger.warn({ err: mediaErr.message }, 'Failed to download media');
            }
        }

        const payload = {
            from: fromMe ? (sock.user?.id || '') : remoteJid,
            to: fromMe ? remoteJid : (sock.user?.id || ''),
            id: msg.key.id,
            type: contentType || 'text',
            text: body,
            fromMe,
            timestamp: msg.messageTimestamp,
            pushName,
            raw: {
                remoteJid,
                fromMe,
                isGroup,
                participant,
                notifyName: pushName,
                messageType: contentType,
            },
            ...(media ? { media } : {}),
            ...(isGroup && participant ? { participant } : {}),
        };

        await axios.post(WEBHOOK_URL, payload, {
            headers: {
                'Content-Type': 'application/json',
                ...(WEBHOOK_TOKEN ? { 'X-WA-V2-Token': WEBHOOK_TOKEN } : {}),
            },
            timeout: 30000,
        });
    } catch (err) {
        logger.warn({ err: err?.response?.data || err.message }, 'Failed to forward message to Laravel');
    }
}

// ── Normalisasi JID ───────────────────────────────────────────────────────────
// Handles: plain number → @s.whatsapp.net
//          @c.us        → @s.whatsapp.net (alias)
//          @lid         → strip suffix, gunakan angkanya saja sebagai @s.whatsapp.net
//                         (Baileys tidak bisa kirim ke @lid secara langsung)
function normalizeJid(jid) {
    if (!jid) return null;

    // Jika tidak ada @, anggap sebagai nomor HP biasa
    if (!jid.includes('@')) {
        const digits = jid.replace(/\D/g, '');
        return digits ? digits + '@s.whatsapp.net' : null;
    }

    // @c.us adalah alias untuk @s.whatsapp.net
    if (jid.endsWith('@c.us')) {
        return jid.replace(/@c\.us$/, '@s.whatsapp.net');
    }

    // @lid: Baileys tidak bisa kirim langsung ke LID.
    // Ambil bagian angka saja dan konversi ke @s.whatsapp.net.
    // Ini best-effort — jika kontak tidak ada di contact store Baileys,
    // pengiriman mungkin gagal, tapi ini lebih baik dari gagal langsung.
    if (jid.endsWith('@lid')) {
        const base = jid.replace(/@lid$/, '');
        if (base && /^\d+$/.test(base)) {
            return base + '@s.whatsapp.net';
        }
        // Jika bukan angka murni, coba kirim apa adanya (mungkin sudah benar)
        return jid;
    }

    return jid;
}

// ────────────────────────────────────────────────────────────────────────────
// REST API Routes
// ────────────────────────────────────────────────────────────────────────────

// GET /health
app.get('/health', (req, res) => {
    res.json({
        ok: connStatus === 'connected',
        status: connStatus,
        connected: connStatus === 'connected',
        connInfo: connInfo || null,
        version: '1.0.0',
        runtime: 'personal',
    });
});

// GET /qr — Ambil QR code untuk scan
app.get('/qr', authenticate, (req, res) => {
    if (connStatus === 'connected') {
        return res.json({ ok: true, connected: true, qr: null });
    }
    if (connStatus === 'qr_ready' && qrCode) {
        return res.json({ ok: true, qr: qrCode, connected: false });
    }
    res.json({ ok: false, qr: null, connected: false, status: connStatus });
});

// POST /send-text — Kirim pesan teks
app.post('/send-text', authenticate, async (req, res) => {
    if (connStatus !== 'connected') {
        return res.status(503).json({ ok: false, error: 'WA not connected' });
    }

    const { to, text, quote_wa_id } = req.body || {};
    if (!to || !text) {
        return res.status(400).json({ ok: false, error: 'Missing required fields: to, text' });
    }

    const jid = normalizeJid(to);
    if (!jid) return res.status(400).json({ ok: false, error: 'Invalid recipient JID' });

    try {
        let msgOptions = { text };
        if (quote_wa_id) {
            // Quoted reply — butuh msg context (simplified)
            msgOptions.quoted = { key: { id: quote_wa_id } };
        }

        const sent = await sock.sendMessage(jid, msgOptions);

        res.json({
            ok: true,
            messageId: sent?.key?.id,
            waMessageId: sent?.key?.id,
            conversation: { conversationId: jid },
        });
    } catch (err) {
        logger.error({ err: err.message, to, jid }, 'Failed to send text');
        res.status(500).json({ ok: false, error: err.message });
    }
});

// POST /send-media — Kirim pesan media
// Mendukung dua format payload:
//   Format lama (legacy): { to, caption, media: { dataUrl, mime, kind, fileName } }
//   Format Laravel:       { to, media_kind, media_url (data URL), mime_type, file_name, caption }
app.post('/send-media', authenticate, async (req, res) => {
    if (connStatus !== 'connected') {
        return res.status(503).json({ ok: false, error: 'WA not connected' });
    }

    const body = req.body || {};
    const { to } = body;
    if (!to) {
        return res.status(400).json({ ok: false, error: 'Missing required field: to' });
    }

    // Normalisasi: dukung kedua format payload
    // Format Laravel: media_url, media_kind, mime_type, file_name, caption
    // Format lama:    media.dataUrl, media.mime, media.kind, media.fileName
    const dataUrl    = body.media_url   || body.media?.dataUrl || null;
    const kind       = body.media_kind  || body.media?.kind    || 'document';
    const mime       = body.mime_type   || body.media?.mime    || 'application/octet-stream';
    const fileName   = body.file_name   || body.media?.fileName || `file.${mime.split('/')[1] || 'bin'}`;
    const caption    = body.caption     || body.media?.caption || '';

    if (!dataUrl) {
        return res.status(400).json({ ok: false, error: 'Missing media URL (media_url or media.dataUrl)' });
    }

    const jid = normalizeJid(to);
    if (!jid) return res.status(400).json({ ok: false, error: 'Invalid recipient JID' });

    try {
        // Parse data URL
        let buffer;
        let resolvedMime = mime;
        if (dataUrl.startsWith('data:')) {
            const [header, b64] = dataUrl.split(',');
            const mimeMatch = header.match(/data:([^;]+)/);
            if (mimeMatch && mimeMatch[1] && resolvedMime === 'application/octet-stream') {
                resolvedMime = mimeMatch[1];
            }
            buffer = Buffer.from(b64, 'base64');
        } else if (dataUrl.startsWith('http')) {
            // URL external — download dulu
            const resp = await axios.get(dataUrl, { responseType: 'arraybuffer', timeout: 30000 });
            buffer = Buffer.from(resp.data);
            if (!resolvedMime || resolvedMime === 'application/octet-stream') {
                resolvedMime = resp.headers['content-type'] || 'application/octet-stream';
            }
        } else {
            return res.status(400).json({ ok: false, error: 'Invalid media URL format' });
        }

        let msgPayload = {};
        const effectiveKind = kind.toLowerCase();
        if (effectiveKind === 'image' || effectiveKind === 'sticker' || resolvedMime.startsWith('image/')) {
            msgPayload = { image: buffer, caption, mimetype: resolvedMime };
        } else if (effectiveKind === 'video' || resolvedMime.startsWith('video/')) {
            msgPayload = { video: buffer, caption, mimetype: resolvedMime };
        } else if (effectiveKind === 'audio' || resolvedMime.startsWith('audio/')) {
            const ptt = Boolean(body.ptt);
            msgPayload = { audio: buffer, mimetype: resolvedMime, ptt };
        } else {
            msgPayload = { document: buffer, mimetype: resolvedMime, fileName };
        }

        const sent = await sock.sendMessage(jid, msgPayload);

        res.json({
            ok: true,
            messageId: sent?.key?.id,
            waMessageId: sent?.key?.id,
            conversation: { conversationId: jid },
        });
    } catch (err) {
        logger.error({ err: err.message, to, jid }, 'Failed to send media');
        res.status(500).json({ ok: false, error: err.message });
    }
});

// POST /unsend-message — Recall pesan dari WA
app.post('/unsend-message', authenticate, async (req, res) => {
    if (connStatus !== 'connected') {
        return res.status(503).json({ ok: false, error: 'WA not connected' });
    }

    const { wa_message_id, jid: rawJid } = req.body || {};
    if (!wa_message_id || !rawJid) {
        return res.status(400).json({ ok: false, error: 'Missing wa_message_id or jid' });
    }

    const jid = normalizeJid(rawJid);
    try {
        await sock.sendMessage(jid, { delete: { id: wa_message_id, remoteJid: jid, fromMe: true } });
        res.json({ ok: true });
    } catch (err) {
        logger.error({ err: err.message }, 'Failed to unsend message');
        res.status(500).json({ ok: false, error: err.message });
    }
});

// GET /lid-mappings — Return empty mapping (runtime personal tidak punya LID store)
// Endpoint ini diperlukan agar Laravel tidak mendapat 404 saat fetch LID mappings
app.get('/lid-mappings', authenticate, (req, res) => {
    res.json({ ok: true, data: { pairs: [] } });
});

// POST /contacts/resolve — Resolve LID ke nomor telepon
// Runtime personal tidak menyimpan kontak store, jadi return null untuk semua
app.post('/contacts/resolve', authenticate, async (req, res) => {
    const { jids } = req.body || {};
    if (!Array.isArray(jids) || jids.length === 0) {
        return res.status(400).json({ ok: false, error: 'Missing jids array' });
    }

    // Coba resolve dari Baileys contact store jika tersedia
    const items = await Promise.all(jids.map(async (jid) => {
        let pn = null;
        try {
            if (sock && jid.endsWith('@lid')) {
                // Baileys mungkin punya info kontak via onWhatsApp
                const [result] = await sock.onWhatsApp(jid.replace(/@lid$/, '') + '@s.whatsapp.net').catch(() => [null]);
                if (result?.jid) pn = result.jid.replace(/@s\.whatsapp\.net$/, '');
            }
        } catch { /* silent */ }
        return { jid, pn };
    }));

    res.json({ ok: true, data: { items } });
});

// GET /messages — Ambil pesan dari nomor tertentu (proxy informasi)
app.get('/messages', authenticate, (req, res) => {
    // Baileys tidak menyimpan history lokal — arahkan ke DB Laravel
    res.json({ ok: true, messages: [], note: 'Use Laravel DB for message history' });
});

// POST /logout — Logout dari WA
app.post('/logout', authenticate, async (req, res) => {
    try {
        if (sock) await sock.logout();
        connStatus = 'disconnected';
        connInfo = null;
        qrCode = null;
        res.json({ ok: true });
    } catch (err) {
        res.status(500).json({ ok: false, error: err.message });
    }
});

// ── Start ────────────────────────────────────────────────────────────────────
app.listen(PORT, '0.0.0.0', async () => {
    logger.info(`WA Caraka Personal Runtime listening on port ${PORT}`);
    await connectWA();
});

process.on('SIGINT', async () => {
    logger.info('Shutting down WA Caraka Personal Runtime...');
    if (sock) await sock.end();
    process.exit(0);
});

// developed by dbprakom™
