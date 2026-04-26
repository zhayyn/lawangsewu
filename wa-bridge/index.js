/**
 * wa-bridge — Adapter antara Laravel WaCarakaService dan runtime wa-api lama
 *
 * Laravel (WaCarakaService) memanggil:
 *   GET  /health        → { connected, status, hasQr, info }
 *   GET  /qr            → { qrDataUrl }
 *   POST /send-text     → { to, text }
 *   GET  /messages      → { messages: [...] }
 *   POST /restart
 *   POST /reconnect
 *   POST /disconnect
 *   GET  /history
 *
 * Runtime wa-api lama berjalan di port 8088:
 *   POST /get-state     → { success, state }
 *   POST /send-message  → { success, message }
 *   DELETE /session     → hapus session
 *   POST /session       → membuat session baru
 *   QR dikirim via Socket.IO event: {sessionId}_qr
 */

'use strict';

const http    = require('http');
const express = require('express');
const { io: ioClient } = require('socket.io-client');
const axios   = require('axios');
const dotenv  = require('dotenv');
const fs      = require('fs');
const path    = require('path');
const crypto  = require('crypto');

dotenv.config();

// ─── Konfigurasi ──────────────────────────────────────────────────────────────
const BRIDGE_PORT   = parseInt(process.env.BRIDGE_PORT   || '8790');
const RUNTIME_URL   = process.env.RUNTIME_URL   || 'http://127.0.0.1:8088';
const RUNTIME_TOKEN = process.env.RUNTIME_TOKEN || 'e1191a9cf5e26c2f8e68d45ca66a4908';
const SESSION_ID    = process.env.SESSION_ID    || 'QG9nI8-I_DELZWCKLxcmz';
const BRIDGE_TOKEN  = process.env.BRIDGE_TOKEN  || 'lawangsewu2026';
const LARAVEL_WEBHOOK_URL = process.env.LARAVEL_WEBHOOK_URL || 'https://lawangsewu.pa-semarang.go.id/api/wa-caraka/webhook/inbound';
const LID_MAP_FILE = process.env.LID_MAP_FILE || path.join(__dirname, 'lid-pn-map.json');
const INLINE_MEDIA_MAX_BYTES = parseInt(process.env.INLINE_MEDIA_MAX_BYTES || '2097152', 10);
const INTERNAL_MEDIA_TTL_MS = parseInt(process.env.INTERNAL_MEDIA_TTL_MS || '900000', 10);

// ─── State in-memory ──────────────────────────────────────────────────────────
let latestQrDataUrl = null;
let isConnected     = false;
let hasQr           = false;
let connectedInfo   = null;
let inboxMessages   = [];
let lastQrAt        = null;
let startedAt       = new Date().toISOString();
let lidToPnMap      = new Map();
let pnToLidMap      = new Map();
let lidMapDirty     = false;
let runtimeState    = 'disconnected';
const internalMediaStore = new Map();

function normalizeMediaType(value) {
    const type = String(value || '').toLowerCase().trim();
    if (!type) return 'text';
    if (type === 'chat' || type === 'text') return 'text';
    if (type === 'image') return 'image';
    if (type === 'video') return 'video';
    if (type === 'audio' || type === 'ptt' || type === 'voice') return 'audio';
    if (type === 'document') return 'document';
    if (type === 'sticker') return 'sticker';
    return type;
}

function persistInternalMedia(buffer, mimeType, fileName = null) {
    if (!Buffer.isBuffer(buffer) || buffer.length === 0) return null;

    const token = crypto.randomBytes(18).toString('hex');
    const expiresAt = Date.now() + INTERNAL_MEDIA_TTL_MS;

    // Tentukan extension dari MIME type jika fileName tidak ada
    let ext = '';
    if (!fileName) {
        const mimeMap = {
            'image/jpeg': '.jpg',
            'image/png': '.png',
            'image/gif': '.gif',
            'image/webp': '.webp',
            'audio/mpeg': '.mp3',
            'audio/ogg': '.ogg',
            'audio/wav': '.wav',
            'video/mp4': '.mp4',
            'video/quicktime': '.mov',
            'application/pdf': '.pdf',
        };
        ext = mimeMap[mimeType] || '.bin';
    }

    internalMediaStore.set(token, {
        buffer,
        mimeType: String(mimeType || 'application/octet-stream'),
        fileName: fileName ? String(fileName) : null,
        ext,
        expiresAt,
    });

    return token;
}

function purgeExpiredInternalMedia() {
    const now = Date.now();
    for (const [key, item] of internalMediaStore.entries()) {
        if (!item || item.expiresAt <= now) {
            internalMediaStore.delete(key);
        }
    }
}

function normalizeJid(value) {
    const raw = String(value || '').trim();
    if (!raw) return '';

    if (raw.endsWith('@c.us')) {
        return raw.replace('@c.us', '@s.whatsapp.net');
    }

    if (raw.endsWith('@s.whatsapp.net') || raw.endsWith('@g.us') || raw.endsWith('@lid')) {
        return raw;
    }

    if (/^\d+$/.test(raw)) {
        return `${raw}@s.whatsapp.net`;
    }

    return raw;
}

function isLidJid(value) {
    return normalizeJid(value).endsWith('@lid');
}

function isPnJid(value) {
    const jid = normalizeJid(value);
    return jid.endsWith('@s.whatsapp.net');
}

function jidBaseUser(value) {
    const jid = normalizeJid(value);
    if (!jid.includes('@')) return '';
    const local = jid.split('@')[0] || '';
    return local.split(':')[0] || '';
}

function withPnServer(userBase) {
    return userBase ? `${userBase}@s.whatsapp.net` : '';
}

function withLidServer(userBase) {
    return userBase ? `${userBase}@lid` : '';
}

function rememberLidPnPair(lidJid, pnJid, source = 'unknown') {
    const lidBase = jidBaseUser(lidJid);
    const pnBase  = jidBaseUser(pnJid);
    if (!lidBase || !pnBase) return;

    if (lidBase === pnBase) return;

    if (!isLidJid(withLidServer(lidBase)) || !isPnJid(withPnServer(pnBase))) return;

    const oldPn = lidToPnMap.get(lidBase);
    if (oldPn && oldPn !== pnBase) {
        console.warn(`[bridge] ⚠️ mapping conflict ${lidBase}: ${oldPn} -> ${pnBase} (${source})`);
    }

    lidToPnMap.set(lidBase, pnBase);
    pnToLidMap.set(pnBase, lidBase);
    lidMapDirty = true;
}

function learnPair(a, b, source = 'payload') {
    const left  = normalizeJid(a);
    const right = normalizeJid(b);
    if (!left || !right || left === right) return;

    if (isLidJid(left) && isPnJid(right)) {
        rememberLidPnPair(left, right, source);
    } else if (isPnJid(left) && isLidJid(right)) {
        rememberLidPnPair(right, left, source);
    }
}

function resolvePnFromJid(value) {
    const jid = normalizeJid(value);
    if (!jid || !isLidJid(jid)) return jid;

    const lidBase = jidBaseUser(jid);
    const pnBase  = lidToPnMap.get(lidBase);
    if (!pnBase) return jid;

    return withPnServer(pnBase);
}

function loadMappings() {
    if (!fs.existsSync(LID_MAP_FILE)) return;
    try {
        const parsed = JSON.parse(fs.readFileSync(LID_MAP_FILE, 'utf8'));
        const pairs = Array.isArray(parsed?.pairs) ? parsed.pairs : [];
        for (const pair of pairs) {
            learnPair(pair?.lid, pair?.pn, 'file.load');
        }
        if (pairs.length > 0) {
            console.log(`[bridge] loaded ${pairs.length} LID mappings`);
        }
    } catch (err) {
        console.error('[bridge] failed to load LID map:', err.message);
    }
}

function persistMappings() {
    if (!lidMapDirty) return;
    lidMapDirty = false;
    const pairs = Array.from(lidToPnMap.entries()).map(([lid, pn]) => ({
        lid: withLidServer(lid),
        pn: withPnServer(pn),
    }));

    try {
        fs.writeFileSync(LID_MAP_FILE, JSON.stringify({ updatedAt: new Date().toISOString(), pairs }, null, 2));
    } catch (err) {
        console.error('[bridge] failed to persist LID map:', err.message);
    }
}

function stripJidSuffix(value) {
    return String(value || '').replace(/@(s\.whatsapp\.net|g\.us|lid)$/i, '');
}

async function fetchRuntimeContactMeta(requestedJids = []) {
    const normalizedRequested = requestedJids
        .map((value) => normalizeJid(value))
        .filter((value) => value !== '');

    if (normalizedRequested.length === 0) {
        return new Map();
    }

    const runtimeLookupPairs = normalizedRequested.map((jid) => ({
        requestedJid: jid,
        runtimeJid: normalizeJid(resolvePnFromJid(jid) || jid),
    }));

    const runtimeJids = Array.from(new Set(runtimeLookupPairs.map((pair) => pair.runtimeJid).filter(Boolean)));
    if (runtimeJids.length === 0) {
        return new Map();
    }

    try {
        const response = await axios.post(`${RUNTIME_URL}/get-contacts`, {
            token: RUNTIME_TOKEN,
            id: SESSION_ID,
            includeDetails: true,
            jids: runtimeJids,
        }, { timeout: 30000 });

        const items = Array.isArray(response.data?.items) ? response.data.items : [];
        const runtimeMap = new Map(items.map((item) => [normalizeJid(item?.jid || item?.resolvedJid || ''), item]));
        const resolved = new Map();

        for (const pair of runtimeLookupPairs) {
            const runtimeItem = runtimeMap.get(pair.runtimeJid) || null;
            if (!runtimeItem) continue;

            resolved.set(pair.requestedJid, {
                jid: pair.requestedJid,
                requestedJid: pair.requestedJid,
                resolvedJid: normalizeJid(runtimeItem?.resolvedJid || pair.runtimeJid || pair.requestedJid),
                displayName: runtimeItem?.displayName || null,
                groupName: runtimeItem?.groupName || null,
                profilePhotoUrl: runtimeItem?.profilePhotoUrl || null,
            });
        }

        return resolved;
    } catch (err) {
        console.warn('[bridge] ⚠️ runtime contact meta fetch gagal:', err.message);
        return new Map();
    }
}

// ─── Sambungkan ke Socket.IO runtime lama ────────────────────────────────────
const socket = ioClient(RUNTIME_URL, {
    reconnection:      true,
    reconnectionDelay: 3000,
    transports:        ['websocket', 'polling'],
    path:              '/socket/',  // sesuai libs/web-socket.js di runtime
});

socket.on('connect', () => {
    console.log(`[bridge] ✅ Socket.IO terhubung ke runtime ${RUNTIME_URL}`);
    setTimeout(checkSessionState, 2000);
});

socket.on('disconnect', () => {
    console.log('[bridge] ⚠️  Socket.IO terputus dari runtime');
});

socket.on('connect_error', (err) => {
    console.error('[bridge] ❌ Socket.IO error:', err.message);
});

// Tangkap QR dari runtime via Socket.IO
socket.on(`${SESSION_ID}_qr`, (qrDataUrl) => {
    console.log('[bridge] 📱 QR diterima dari runtime');
    latestQrDataUrl = qrDataUrl;
    hasQr           = true;
    isConnected     = false;
    runtimeState    = 'PAIRING';
    lastQrAt        = new Date().toISOString();
});

// Tangkap event ready
socket.on(`${SESSION_ID}_ready`, (msg) => {
    console.log('[bridge] ✅ Session ready:', msg);
    isConnected     = true;
    hasQr           = false;
    latestQrDataUrl = null;
    runtimeState    = 'CONNECTED';
    connectedInfo   = { sessionId: SESSION_ID, connectedAt: new Date().toISOString() };

    // Auto-sync LID→PN mappings from runtime contacts on session ready
    setTimeout(async () => {
        try {
            const resp = await axios.post(`${RUNTIME_URL}/get-contacts`, {
                token: RUNTIME_TOKEN,
                id: SESSION_ID,
            }, { timeout: 30000 });
            const mappings = resp.data?.mappings || [];
            let learned = 0;
            for (const { lid, pn } of mappings) {
                if (lid && pn) {
                    const before = lidToPnMap.size;
                    learnPair(lid, pn, 'ready.auto-sync');
                    if (lidToPnMap.size > before) learned++;
                }
            }
            if (learned > 0) {
                persistMappings();
                console.log(`[bridge] 📇 Auto-sync: +${learned} mapping LID baru (total: ${lidToPnMap.size})`);
            }
        } catch (err) {
            console.warn('[bridge] ⚠️ Auto-sync kontak gagal:', err.message);
        }
    }, 5000); // Tunggu 5 detik setelah ready
});

// ─── Helper: cek state session ke runtime ────────────────────────────────────
async function checkSessionState() {
    try {
        const res = await axios.post(`${RUNTIME_URL}/get-state`, {
            token: RUNTIME_TOKEN,
            id:    SESSION_ID,
        }, { timeout: 5000 });

        const state = String(res.data?.state || '').toUpperCase();
        runtimeState = state || 'DISCONNECTED';
        if (state === 'CONNECTED') {
            if (!isConnected) {
                console.log('[bridge] ✅ State: CONNECTED (dari polling)');
            }
            isConnected = true;
            hasQr       = false;
            if (!connectedInfo) {
                connectedInfo = { sessionId: SESSION_ID, connectedAt: new Date().toISOString() };
            }
        } else if (state === 'OPENING' || state === 'PAIRING') {
            isConnected = false;
            connectedInfo = null;
            console.log('[bridge] ⏳ State runtime:', state);
        } else {
            isConnected = false;
            connectedInfo = null;
        }
    } catch (err) {
        runtimeState = 'DISCONNECTED';
        isConnected = false;
        connectedInfo = null;
        const errMsg = err?.response?.data?.message || err.message || '';
        // Session belum ada di runtime — auto-create
        if (errMsg.includes('tidak terdaftar') || errMsg.includes('tidak valid') || err?.response?.status === 404) {
            console.log('[bridge] 🔧 Session belum ada, auto-create...');
            await ensureSessionExists();
        }
    }
}

// ─── Auto-buat session jika belum ada ────────────────────────────────────────
async function ensureSessionExists() {
    try {
        await axios.post(`${RUNTIME_URL}/session`, {
            token: RUNTIME_TOKEN,
            id:    SESSION_ID,
            url:   `http://127.0.0.1:${BRIDGE_PORT}/internal/hook`,
        }, { timeout: 15000 });
        console.log('[bridge] ✅ Session berhasil dibuat, menunggu QR...');
    } catch (err) {
        console.error('[bridge] ❌ Gagal membuat session:', err?.response?.data || err.message);
    }
}


// ─── Helper: validasi token dari Laravel ─────────────────────────────────────
function validateToken(req, res) {
    if (!BRIDGE_TOKEN) return true; // token tidak dikonfig — skip validasi
    const token = req.headers['x-wa-v2-token'] || '';
    if (token !== BRIDGE_TOKEN) {
        res.status(401).json({ ok: false, error: 'Token tidak valid.' });
        return false;
    }
    return true;
}

// ─── Polling state secara periodik ───────────────────────────────────────────
setInterval(checkSessionState, 30_000);

// ─── Express App ─────────────────────────────────────────────────────────────
const app    = express();
const server = http.createServer(app);

app.disable('x-powered-by');
app.use(express.json({ limit: '50mb' }));
app.use(express.urlencoded({ extended: false, limit: '50mb' }));

// ─── CORS untuk lokal ─────────────────────────────────────────────────────────
app.use((req, res, next) => {
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type, X-WA-V2-Token');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, DELETE, OPTIONS');
    if (req.method === 'OPTIONS') return res.sendStatus(200);
    next();
});

// ── GET /ping — health check tanpa token ─────────────────────────────────────
app.get('/ping', (req, res) => {
    res.json({ pong: true, bridge: 'wa-bridge v1.0', uptime: process.uptime() });
});

app.get('/internal/media/:token(*)', (req, res) => {
    // URL bisa: /internal/media/token.ext atau /internal/media/token/filename
    let token = String(req.params.token || '').trim();
    
    // Jika path include filename, ambil token saja (bagian sebelum slash atau extension)
    if (token.includes('/')) {
        token = token.split('/')[0];
    } else if (token.includes('.')) {
        token = token.split('.')[0];
    }
    
    if (!token) {
        return res.status(404).send('Not found');
    }

    const media = internalMediaStore.get(token);
    if (!media || media.expiresAt <= Date.now()) {
        internalMediaStore.delete(token);
        return res.status(404).send('Not found');
    }

    res.setHeader('Content-Type', media.mimeType || 'application/octet-stream');
    if (media.fileName) {
        const safeName = media.fileName.replace(/[\r\n"]/g, '_');
        res.setHeader('Content-Disposition', `inline; filename="${safeName}"`);
    }
    return res.send(media.buffer);
});

// ── GET /health ───────────────────────────────────────────────────────────────
app.get('/health', (req, res) => {
    if (!validateToken(req, res)) return;
    const normalizedRuntimeState = String(runtimeState || '').toLowerCase();
    const status = isConnected
        ? 'connected'
        : (hasQr ? 'waiting_qr' : (normalizedRuntimeState === 'opening' || normalizedRuntimeState === 'pairing' ? normalizedRuntimeState : 'disconnected'));

    res.json({
        ok:         true,
        connected:  isConnected,
        status,
        hasQr:      hasQr,
        info:       connectedInfo,
        bridge:     'wa-bridge v1.0',
        sessionId:  SESSION_ID,
        runtimeUrl: RUNTIME_URL,
        lastQrAt:   lastQrAt,
        startedAt:  startedAt,
        lidMappings: lidToPnMap.size,
        runtimeState: normalizedRuntimeState || null,
    });
});

app.get('/lid-mappings', (req, res) => {
    if (!validateToken(req, res)) return;
    const pairs = Array.from(lidToPnMap.entries()).map(([lid, pn]) => ({
        lid: withLidServer(lid),
        pn: withPnServer(pn),
    }));
    res.json({ ok: true, total: pairs.length, pairs });
});

app.post('/lid-mappings/resolve', (req, res) => {
    if (!validateToken(req, res)) return;
    const lid = normalizeJid(req.body?.lid || '');
    if (!lid || !isLidJid(lid)) {
        return res.status(422).json({ ok: false, error: 'Field lid harus berformat @lid' });
    }
    const resolved = resolvePnFromJid(lid);
    res.json({ ok: true, lid, resolved: resolved !== lid ? resolved : null });
});

app.post('/contacts/resolve', async (req, res) => {
    if (!validateToken(req, res)) return;

    const jids = Array.isArray(req.body?.jids) ? req.body.jids : null;
    if (!jids) {
        return res.status(422).json({ ok: false, error: 'Field jids harus berupa array.' });
    }

    const normalizedRequestedJids = jids
        .map((value) => normalizeJid(value))
        .filter((jid) => jid !== '');
    const runtimeMeta = await fetchRuntimeContactMeta(normalizedRequestedJids);

    const items = normalizedRequestedJids
        .map((jid) => {
            const isGroup = jid.endsWith('@g.us');
            const candidates = inboxMessages.filter((m) => {
                const chatId = normalizeJid(m.chatId || m.raw?.chatId || '');
                const fromRaw = normalizeJid(m.fromRaw || m.raw?.fromRaw || '');
                if (isGroup) return chatId === jid || fromRaw === jid;
                return fromRaw === jid || normalizeJid(m.from || '') === jid;
            });

            let displayName = null;
            let groupName = null;
            let profilePhotoUrl = null;
            const runtimeItem = runtimeMeta.get(jid) || null;

            for (const msg of candidates) {
                const raw = msg.raw || {};
                if (!displayName) {
                    displayName = raw?.meta?.notifyName || raw?.meta?.senderName || raw?.senderName || raw?.pushName || null;
                }
                if (!groupName) {
                    groupName = raw?.groupName || raw?.groupSubject || raw?.meta?.subject || raw?.subject || null;
                }
                if (!profilePhotoUrl) {
                    profilePhotoUrl = raw?.profilePhotoUrl || raw?.avatarUrl || raw?.profilePicUrl || raw?.meta?.profilePicUrl || null;
                }
            }

            if (isGroup && !groupName) {
                groupName = `Group ${stripJidSuffix(jid)}`;
            }

            return {
                jid,
                displayName: runtimeItem?.displayName || displayName || null,
                groupName: runtimeItem?.groupName || groupName || null,
                profilePhotoUrl: runtimeItem?.profilePhotoUrl || profilePhotoUrl || null,
                resolvedJid: runtimeItem?.resolvedJid || jid,
            };
        });

    res.json({ ok: true, items });
});

// ── POST /lid-mappings/inject — isi mapping manual (Opsi B spare) ─────────────
// Body: { pairs: [ { lid: "xxx@lid", pn: "628xx@s.whatsapp.net" }, ... ] }
// atau singkat: { lid: "xxx@lid", pn: "628xx@s.whatsapp.net" }
app.post('/lid-mappings/inject', (req, res) => {
    if (!validateToken(req, res)) return;

    const body = req.body || {};
    let pairs = [];

    if (Array.isArray(body.pairs)) {
        pairs = body.pairs;
    } else if (body.lid && body.pn) {
        pairs = [{ lid: body.lid, pn: body.pn }];
    }

    if (pairs.length === 0) {
        return res.status(422).json({ ok: false, error: 'Butuh field "pairs" (array) atau "lid"+"pn".' });
    }

    let learned = 0;
    const results = pairs.map(p => {
        const lid = normalizeJid(p?.lid || '');
        const pn  = normalizeJid(p?.pn  || '');
        if (!lid || !pn || !isLidJid(lid) || !isPnJid(pn)) {
            return { lid: p?.lid, pn: p?.pn, ok: false, error: 'Format tidak valid' };
        }
        learnPair(lid, pn, 'manual.inject');
        learned++;
        return { lid, pn, ok: true };
    });

    if (learned > 0) {
        persistMappings();
        console.log(`[bridge] 💉 Manual inject: ${learned} mapping baru`);
    }

    res.json({ ok: true, learned, total: lidToPnMap.size, results });
});

// ── GET /qr ───────────────────────────────────────────────────────────────────
app.get('/qr', (req, res) => {
    if (!validateToken(req, res)) return;

    // Jika sudah terhubung, tidak perlu QR
    if (isConnected) {
        return res.json({
            ok:        true,
            qrDataUrl: null,
            connected: true,
            message:   '✅ Device sudah terhubung ke WhatsApp.',
        });
    }

    // QR sudah ada di memory
    if (latestQrDataUrl) {
        return res.json({
            ok:        true,
            qrDataUrl: latestQrDataUrl,
            hasQr:     true,
            lastQrAt:  lastQrAt,
        });
    }

    // QR belum ada — kembalikan null, frontend akan coba lagi
    return res.json({
        ok:        true,
        qrDataUrl: null,
        hasQr:     false,
        message:   '⏳ QR belum tersedia. Runtime sedang inisialisasi. Coba refresh dalam 5 detik.',
    });
});

// ── POST /refresh-qr — paksa regenerate QR ───────────────────────────────────
app.post('/refresh-qr', async (req, res) => {
    if (!validateToken(req, res)) return;

    console.log('[bridge] 🔄 refresh-qr diminta');
    latestQrDataUrl = null;
    hasQr           = false;

    try {
        // Hapus session lama
        await axios.delete(`${RUNTIME_URL}/session`,
            { data: { token: RUNTIME_TOKEN, id: SESSION_ID }, timeout: 8000 }
        ).catch(() => {});

        await new Promise(r => setTimeout(r, 2000));

        // Buat session baru → runtime akan emit QR via Socket.IO
        await axios.post(`${RUNTIME_URL}/session`, {
            token: RUNTIME_TOKEN,
            id:    SESSION_ID,
            url:   `http://127.0.0.1:${BRIDGE_PORT}/internal/hook`,
        }, { timeout: 15000 }).catch(() => {});

        setTimeout(() => checkSessionState(), 5000);
    } catch (err) {
        console.error('[bridge] refresh-qr error:', err.message);
    }

    res.json({
        ok:      true,
        message: 'Permintaan QR baru dikirim ke runtime. QR akan muncul dalam 10-30 detik.',
    });
});

// ── POST /send-text ───────────────────────────────────────────────────────────
app.post('/send-text', async (req, res) => {
    if (!validateToken(req, res)) return;

    const { to, text } = req.body;
    if (!to || !text) {
        return res.status(422).json({ ok: false, error: 'Parameter "to" dan "text" wajib diisi.' });
    }

    if (!isConnected) {
        return res.status(503).json({ ok: false, error: 'Device belum terhubung ke WhatsApp. Scan QR terlebih dahulu.' });
    }

    try {
        const resolvedTo = resolvePnFromJid(to);
        console.log(`[bridge] 📤 Mengirim pesan ke: ${to} (resolved: ${resolvedTo})`);

        const response = await axios.post(`${RUNTIME_URL}/send-message`, {
            token:   RUNTIME_TOKEN,
            id:      SESSION_ID,
            number:  resolvedTo,
            message: text,
        }, { timeout: 30000 });

        const success = response.data?.success === true;
        if (success) {
            res.json({ ok: true, messageId: `bridge_${Date.now()}`, message: 'Pesan terkirim.' });
        } else {
            res.status(502).json({ ok: false, error: response.data?.message || 'Runtime gagal kirim pesan.' });
        }
    } catch (err) {
        res.status(502).json({ ok: false, error: 'Gagal terhubung ke runtime: ' + err.message });
    }
});

// ── POST /send-media ─────────────────────────────────────────────────────────
app.post('/send-media', async (req, res) => {
    if (!validateToken(req, res)) return;

    const { to, media_url, caption, file_name, media_kind } = req.body || {};
    if (!to || !media_url) {
        return res.status(422).json({ ok: false, error: 'Parameter "to" dan "media_url" wajib diisi.' });
    }

    if (!isConnected) {
        return res.status(503).json({ ok: false, error: 'Device belum terhubung ke WhatsApp. Scan QR terlebih dahulu.' });
    }

    let fileUrl = String(media_url || '').trim();
    let mimeType = String(req.body?.mime_type || '').trim() || 'application/octet-stream';
    let byteLength = null;

    if (fileUrl.startsWith('data:')) {
        const match = fileUrl.match(/^data:([^;,]+);base64,(.+)$/i);
        if (!match) {
            return res.status(422).json({ ok: false, error: 'Format media_url data URL tidak valid.' });
        }

        try {
            const buffer = Buffer.from(match[2], 'base64');
            mimeType = mimeType || match[1] || 'application/octet-stream';
            byteLength = buffer.length;

            if (!buffer.length) {
                return res.status(422).json({ ok: false, error: 'Konten media kosong.' });
            }

            const token = persistInternalMedia(buffer, mimeType, file_name || null);
            if (!token) {
                return res.status(500).json({ ok: false, error: 'Gagal menyimpan media sementara.' });
            }

            const media = internalMediaStore.get(token);
            const urlPath = media.fileName ? 
                `${token}/${encodeURIComponent(media.fileName)}` :
                `${token}${media.ext}`;
            fileUrl = `http://127.0.0.1:${BRIDGE_PORT}/internal/media/${urlPath}`;
        } catch (err) {
            return res.status(422).json({ ok: false, error: `Media base64 tidak valid: ${err.message}` });
        }
    } else if (!/^https?:\/\//i.test(fileUrl)) {
        return res.status(422).json({ ok: false, error: 'media_url harus berupa URL http/https atau data URL base64.' });
    }

    try {
        const resolvedTo = resolvePnFromJid(to);
        console.log(`[bridge] 📤 Mengirim media ke: ${to} (resolved: ${resolvedTo})`);

        const response = await axios.post(`${RUNTIME_URL}/send-media`, {
            token: RUNTIME_TOKEN,
            id: SESSION_ID,
            number: resolvedTo,
            media_kind: normalizeMediaType(media_kind || ''),
            mime_type: mimeType,
            file_name: file_name || '',
            file: fileUrl,
            caption: caption || '',
        }, { timeout: 45000 });

        const success = response.data?.success === true;
        if (!success) {
            return res.status(502).json({ ok: false, error: response.data?.message || 'Runtime gagal kirim media.' });
        }

        return res.json({
            ok: true,
            messageId: `bridge_media_${Date.now()}`,
            message: 'Media terkirim.',
            media: {
                kind: normalizeMediaType(req.body?.media_kind || ''),
                mimetype: mimeType,
                fileName: file_name || null,
                caption: caption || '',
                byteLength,
                source: fileUrl,
            },
        });
    } catch (err) {
        return res.status(502).json({ ok: false, error: 'Gagal terhubung ke runtime media: ' + err.message });
    }
});

// ── GET /messages — inbox yang ditangkap dari hook ───────────────────────────
app.get('/messages', (req, res) => {
    if (!validateToken(req, res)) return;

    const since    = req.query.since ? new Date(req.query.since) : null;
    const filtered = since
        ? inboxMessages.filter(m => new Date(m.timestamp) > since)
        : inboxMessages;

    res.json({ ok: true, messages: filtered });
});

// ── POST /restart ─────────────────────────────────────────────────────────────
app.post('/restart', async (req, res) => {
    if (!validateToken(req, res)) return;
    console.log('[bridge] 🔄 Restart diminta');

    latestQrDataUrl = null;
    hasQr           = false;
    isConnected     = false;
    connectedInfo   = null;

    try {
        await axios.delete(`${RUNTIME_URL}/session`,
            { data: { token: RUNTIME_TOKEN, id: SESSION_ID }, timeout: 8000 }
        ).catch(() => {});

        await new Promise(r => setTimeout(r, 2000));

        await axios.post(`${RUNTIME_URL}/session`, {
            token: RUNTIME_TOKEN,
            id:    SESSION_ID,
            url:   `http://127.0.0.1:${BRIDGE_PORT}/internal/hook`,
        }, { timeout: 15000 }).catch(() => {});
    } catch (err) {
        console.error('[bridge] restart error:', err.message);
    }

    res.json({ ok: true, message: 'Restart diminta. QR baru akan muncul dalam beberapa detik.' });
});

// ── POST /reconnect ────────────────────────────────────────────────────────────
app.post('/reconnect', async (req, res) => {
    if (!validateToken(req, res)) return;
    await checkSessionState();
    res.json({ ok: true, message: 'Cek koneksi ulang...' });
});

// ── POST /disconnect ───────────────────────────────────────────────────────────
app.post('/disconnect', async (req, res) => {
    if (!validateToken(req, res)) return;

    try {
        await axios.delete(`${RUNTIME_URL}/session`,
            { data: { token: RUNTIME_TOKEN, id: SESSION_ID }, timeout: 8000 }
        );
        isConnected     = false;
        hasQr           = false;
        latestQrDataUrl = null;
        connectedInfo   = null;
        runtimeState    = 'DISCONNECTED';
        console.log('[bridge] Session disconnected');
    } catch (err) {
        console.error('[bridge] disconnect error:', err.message);
    }

    res.json({ ok: true, message: 'Session dihapus.' });
});

// ── GET /history ──────────────────────────────────────────────────────────────
app.get('/history', (req, res) => {
    if (!validateToken(req, res)) return;
    const items = [...inboxMessages].reverse().slice(0, 50);
    res.json({ ok: true, items });
});

// ── POST /history/clear ───────────────────────────────────────────────────────
app.post('/history/clear', (req, res) => {
    if (!validateToken(req, res)) return;
    inboxMessages = [];
    res.json({ ok: true, message: 'History dibersihkan.' });
});

// ── POST /contacts/sync — minta runtime dump kontak & perkaya lidToPnMap ─────
app.post('/contacts/sync', async (req, res) => {
    if (!validateToken(req, res)) return;

    try {
        const response = await axios.post(`${RUNTIME_URL}/get-contacts`, {
            token: RUNTIME_TOKEN,
            id: SESSION_ID,
        }, { timeout: 30000 });

        const mappings = response.data?.mappings || [];
        let learned = 0;

        for (const { lid, pn } of mappings) {
            if (lid && pn) {
                const before = lidToPnMap.size;
                learnPair(lid, pn, 'sync-contacts');
                if (lidToPnMap.size > before) learned++;
            }
        }

        persistMappings();

        return res.json({
            ok: true,
            total: mappings.length,
            learned,
            lidMappings: lidToPnMap.size,
        });
    } catch (err) {
        return res.status(502).json({ ok: false, error: 'Gagal sync kontak: ' + err.message });
    }
});

// ── POST /internal/hook — webhook dari runtime wa-api ────────────────────────
app.post('/internal/hook', (req, res) => {
    const { event, data, id } = req.body || {};

    if (event === 'message' && data) {
        const rawFrom = normalizeJid(data.fromRaw || data.number || '');
        const rawFromLid = normalizeJid(data.fromLid || '');
        let rawFromPn = normalizeJid(
            data.fromPn
            || data.resolvedFromJid
            || data.meta?.senderPn
            || data.meta?.authorPn
            || data.meta?.participantPn
            || '',
        );

        // Abaikan fromPn palsu (di mana digit PN sama persis dengan digit LID)
        if (jidBaseUser(rawFrom) === jidBaseUser(rawFromPn)) {
            rawFromPn = '';
        }

        learnPair(rawFromLid, rawFromPn, 'hook.direct');
        learnPair(rawFrom, rawFromPn, 'hook.from+pn');

        const effectiveFromJid = resolvePnFromJid(rawFrom || rawFromLid || rawFromPn);
        const effectiveFromPn = isPnJid(effectiveFromJid) ? effectiveFromJid : (isPnJid(rawFromPn) ? rawFromPn : '');
        const effectiveFromLid = isLidJid(rawFrom) ? rawFrom : (isLidJid(rawFromLid) ? rawFromLid : '');

        const normalizedType = normalizeMediaType(data.type || data.messageType || data.rawType || 'text');
        const inferredText = String(
            data.message
            || data.caption
            || data.title
            || data.filename
            || data.fileName
            || '',
        );

        let normalizedMedia = null;
        const mediaDataRaw = data.mediaData || data.dataUrl || '';
        if (typeof mediaDataRaw === 'string' && mediaDataRaw.startsWith('data:')) {
            const match = mediaDataRaw.match(/^data:([^;,]+);base64,(.+)$/i);
            if (match) {
                try {
                    const mediaBuffer = Buffer.from(match[2], 'base64');
                    const mediaMime = data.mimetype || data.mimeType || match[1] || 'application/octet-stream';
                    const fileName = data.filename || data.fileName || null;
                    const media = {
                        kind: normalizedType,
                        mimetype: mediaMime,
                        fileName,
                        caption: data.caption || '',
                        byteLength: mediaBuffer.length,
                        inline: false,
                        dataUrl: null,
                    };

                    if (mediaBuffer.length > 0 && mediaBuffer.length <= INLINE_MEDIA_MAX_BYTES) {
                        media.dataUrl = `data:${mediaMime};base64,${mediaBuffer.toString('base64')}`;
                        media.inline = true;
                    } else if (mediaBuffer.length > 0) {
                        const token = persistInternalMedia(mediaBuffer, mediaMime, fileName || null);
                        if (token) {
                            // Return public Laravel proxy URL instead of direct bridge localhost URL
                            const media_obj = internalMediaStore.get(token);
                            const urlPath = media_obj.fileName ? 
                                `${token}/${encodeURIComponent(media_obj.fileName)}` :
                                `${token}${media_obj.ext}`;
                            media.url = `https://lawangsewu.pa-semarang.go.id/wa-caraka/media/${urlPath}`;
                        }
                    }

                    normalizedMedia = media;
                } catch (err) {
                    console.warn('[bridge] gagal parsing mediaData dari runtime:', err.message);
                }
            }
        } else if (data.file || data.fileUrl || data.url) {
            normalizedMedia = {
                kind: normalizedType,
                mimetype: data.mimetype || data.mimeType || 'application/octet-stream',
                fileName: data.filename || data.fileName || null,
                caption: data.caption || '',
                url: data.file || data.fileUrl || data.url,
                inline: false,
                dataUrl: null,
            };
        }

        // Prefer PN over LID as canonical "from" so Laravel can show real phone numbers.
        // effectiveFromPn is the resolved PN JID; effectiveFromJid may still be LID if not in map.
        const canonicalFrom = effectiveFromPn || effectiveFromJid || rawFrom;

        const msg = {
            id:        `hook_${SESSION_ID}_${Date.now()}`,
            from:      canonicalFrom,
            fromRaw:   rawFrom || '',
            fromLid:   effectiveFromLid || null,
            fromPn:    effectiveFromPn || null,
            resolvedFromJid: canonicalFrom,
            resolvedFrom: stripJidSuffix(canonicalFrom),
            isLid: Boolean(effectiveFromLid),
            isGroup: Boolean(data.isGroup) || normalizeJid(data.chatId || '').endsWith('@g.us'),
            chatId: normalizeJid(data.chatId || ''),
            to:        SESSION_ID,
            text:      inferredText,
            type:      normalizedType,
            media:     normalizedMedia,
            timestamp: data.timestamp || new Date().toISOString(),
            raw:       data,
        };
        inboxMessages.push(msg);

        // Batasi buffer inbox (maks 500 pesan)
        if (inboxMessages.length > 500) {
            inboxMessages = inboxMessages.slice(-500);
        }

        console.log(`[bridge] 📨 Pesan masuk dari ${msg.from}: ${msg.text.substring(0, 60)}`);

        // Forward ke Laravel webhook secara async
        axios.post(LARAVEL_WEBHOOK_URL, msg, {
            headers: {
                'X-WA-V2-Token': BRIDGE_TOKEN,
                'Content-Type':  'application/json',
            },
            timeout: 10000,
        }).then(() => {
            console.log(`[bridge] ✅ Pesan ${msg.id} diteruskan ke Laravel`);
        }).catch(err => {
            console.error('[bridge] ❌ Forward ke Laravel gagal:', err.message);
        });
    }

    if (event === 'disconnected') {
        isConnected = false;
        hasQr       = false;
        runtimeState = 'DISCONNECTED';
        console.log('[bridge] ⚠️  Session terputus (event dari runtime)');
    }

    res.json({ ok: true });
});

// ─── Start server ─────────────────────────────────────────────────────────────
server.listen(BRIDGE_PORT, '0.0.0.0', () => {
    loadMappings();
    setInterval(persistMappings, 10_000);
    setInterval(purgeExpiredInternalMedia, 60_000);

    console.log('');
    console.log('┌─────────────────────────────────────────┐');
    console.log(`│       wa-bridge v1.0 — PORT ${BRIDGE_PORT}          │`);
    console.log('├─────────────────────────────────────────┤');
    console.log(`│ Runtime  : ${RUNTIME_URL.padEnd(29)} │`);
    console.log(`│ Session  : ${SESSION_ID.padEnd(29)} │`);
    console.log(`│ Webhook  : /internal/hook               │`);
    console.log('└─────────────────────────────────────────┘');
    console.log('');
});

process.on('SIGINT', () => {
    persistMappings();
    process.exit(0);
});

process.on('SIGTERM', () => {
    persistMappings();
    process.exit(0);
});
