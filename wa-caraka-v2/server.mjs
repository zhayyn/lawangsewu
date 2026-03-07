import express from 'express';
import qrcode from 'qrcode';
import pino from 'pino';
import makeWASocket, { fetchLatestBaileysVersion, useMultiFileAuthState } from '@whiskeysockets/baileys';
import path from 'path';
import fs from 'fs';

const PORT = Number(process.env.WA_V2_PORT || 8790);
const HOST = process.env.WA_V2_HOST || '127.0.0.1';
const AUTH_DIR = process.env.WA_V2_AUTH_DIR || '/var/www/html/lawangsewu/logs/wa-caraka-v2-session';
const HISTORY_FILE = process.env.WA_V2_HISTORY_FILE || '/var/www/html/lawangsewu/logs/wa-caraka-v2-history.jsonl';

if (!fs.existsSync(AUTH_DIR)) {
  fs.mkdirSync(AUTH_DIR, { recursive: true });
}

if (!fs.existsSync(path.dirname(HISTORY_FILE))) {
  fs.mkdirSync(path.dirname(HISTORY_FILE), { recursive: true });
}

const app = express();
app.use(express.json({ limit: '1mb' }));

const state = {
  qr: null,
  connected: false,
  autoReconnect: true,
  lastConnectionUpdate: null,
  startedAt: new Date().toISOString(),
};

let sock = null;
let starting = false;

function normalizeJid(input) {
  const digits = String(input || '').replace(/\D/g, '');
  if (!digits) return '';
  return digits.endsWith('@s.whatsapp.net') ? digits : `${digits}@s.whatsapp.net`;
}

function extractMessageText(message) {
  if (!message || typeof message !== 'object') return '';
  if (typeof message.conversation === 'string') return message.conversation;
  if (message.extendedTextMessage?.text) return String(message.extendedTextMessage.text);
  if (message.imageMessage?.caption) return String(message.imageMessage.caption);
  if (message.videoMessage?.caption) return String(message.videoMessage.caption);
  if (message.documentMessage?.caption) return String(message.documentMessage.caption);
  if (message.ephemeralMessage?.message) return extractMessageText(message.ephemeralMessage.message);
  if (message.viewOnceMessage?.message) return extractMessageText(message.viewOnceMessage.message);
  if (message.viewOnceMessageV2?.message) return extractMessageText(message.viewOnceMessageV2.message);
  if (message.viewOnceMessageV2Extension?.message) return extractMessageText(message.viewOnceMessageV2Extension.message);
  if (message.protocolMessage?.type) return `[protocol:${message.protocolMessage.type}]`;
  return '[non-text-message]';
}

function appendHistory(entry) {
  const payload = {
    ts: new Date().toISOString(),
    ...entry,
  };
  try {
    fs.appendFileSync(HISTORY_FILE, `${JSON.stringify(payload)}\n`, 'utf8');
  } catch (_) {
    // History failure should not break WA service runtime.
  }
}

function readHistory(limit = 120) {
  if (!fs.existsSync(HISTORY_FILE)) return [];
  const max = Number.isFinite(limit) ? Math.max(1, Math.min(500, Number(limit))) : 120;

  try {
    const raw = fs.readFileSync(HISTORY_FILE, 'utf8');
    const rows = raw
      .split(/\r?\n/)
      .filter(Boolean)
      .map((line) => {
        try {
          return JSON.parse(line);
        } catch (_) {
          return null;
        }
      })
      .filter(Boolean);

    return rows.slice(-max).reverse();
  } catch (_) {
    return [];
  }
}

async function startSock() {
  if (starting || sock) return;
  starting = true;

  const logger = pino({ level: process.env.WA_V2_LOG_LEVEL || 'warn' });
  const { state: authState, saveCreds } = await useMultiFileAuthState(path.resolve(AUTH_DIR));
  const { version } = await fetchLatestBaileysVersion();

  try {
    sock = makeWASocket({
      auth: authState,
      version,
      logger,
      printQRInTerminal: false,
      syncFullHistory: false,
      markOnlineOnConnect: false,
    });

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', async (update) => {
      const { connection, lastDisconnect, qr } = update;
      state.lastConnectionUpdate = {
        connection: connection || null,
        ts: new Date().toISOString(),
        hasQr: Boolean(qr),
      };

      if (qr) {
        state.qr = qr;
        state.connected = false;
      }

      if (connection === 'open') {
        state.connected = true;
        state.qr = null;
        appendHistory({
          type: 'connection',
          event: 'open',
          detail: 'WA connected',
        });
      }

      if (connection === 'close') {
        state.connected = false;
        sock = null;
        const statusCode = lastDisconnect?.error?.output?.statusCode || 0;
        appendHistory({
          type: 'connection',
          event: 'close',
          statusCode,
          detail: String(lastDisconnect?.error?.message || ''),
        });
        // Restart for all disconnects except explicit logout.
        if (statusCode !== 401 && state.autoReconnect) {
          setTimeout(() => {
            startSock().catch(() => {});
          }, 2500);
        }
      }
    });

    sock.ev.on('messages.upsert', ({ messages }) => {
      if (!Array.isArray(messages)) return;
      for (const msg of messages) {
        const jid = msg?.key?.remoteJid || '';
        const fromMe = Boolean(msg?.key?.fromMe);
        const text = extractMessageText(msg?.message);
        const sender = msg?.pushName || msg?.verifiedBizName || '';
        appendHistory({
          type: 'message',
          direction: fromMe ? 'out' : 'in',
          jid,
          sender,
          text,
          id: msg?.key?.id || null,
        });
      }
    });
  } finally {
    starting = false;
  }
}

async function stopSock() {
  if (!sock) {
    state.connected = false;
    return;
  }

  const current = sock;
  sock = null;
  state.connected = false;
  state.qr = null;

  try {
    current.ws?.close?.();
  } catch (_) {
    // Ignore low-level close errors; socket is being force-stopped.
  }
}

app.get('/health', (_req, res) => {
  const startedAtMs = Date.parse(state.startedAt) || Date.now();
  const uptimeSec = Math.max(0, Math.floor((Date.now() - startedAtMs) / 1000));
  res.json({
    ok: true,
    connected: state.connected,
    hasQr: Boolean(state.qr),
    autoReconnect: state.autoReconnect,
    uptimeSec,
    startedAt: state.startedAt,
    lastConnectionUpdate: state.lastConnectionUpdate,
    service: 'wa-caraka-v2',
  });
});

app.get('/qr', async (_req, res) => {
  if (!state.qr) {
    return res.status(404).json({ ok: false, message: 'QR belum tersedia atau sudah tersambung.' });
  }

  const qrDataUrl = await qrcode.toDataURL(state.qr);
  return res.json({ ok: true, qr: state.qr, qrDataUrl });
});

app.post('/send-text', async (req, res) => {
  if (!sock || !state.connected) {
    return res.status(503).json({ ok: false, message: 'WA belum tersambung.' });
  }

  const to = normalizeJid(req.body?.to);
  const text = String(req.body?.text || '').trim();

  if (!to || !text) {
    return res.status(400).json({ ok: false, message: 'Parameter to dan text wajib diisi.' });
  }

  try {
    const sent = await sock.sendMessage(to, { text });
    appendHistory({
      type: 'message',
      direction: 'out',
      jid: to,
      text,
      id: sent?.key?.id || null,
      sender: 'api-send-text',
    });
    return res.json({ ok: true, id: sent?.key?.id || null, to });
  } catch (error) {
    return res.status(500).json({ ok: false, message: String(error?.message || error) });
  }
});

app.post('/reconnect', async (_req, res) => {
  try {
    state.autoReconnect = true;
    if (!sock) {
      await startSock();
    }
    appendHistory({ type: 'control', event: 'reconnect' });
    return res.json({ ok: true, message: 'Reconnect WA v2 diproses.' });
  } catch (error) {
    return res.status(500).json({ ok: false, message: String(error?.message || error) });
  }
});

app.post('/restart', async (_req, res) => {
  try {
    state.autoReconnect = true;
    await stopSock();
    await startSock();
    return res.json({ ok: true, message: 'Restart WA v2 diproses.' });
  } catch (error) {
    return res.status(500).json({ ok: false, message: String(error?.message || error) });
  }
});

app.post('/disconnect', async (_req, res) => {
  try {
    state.autoReconnect = false;
    await stopSock();
    return res.json({ ok: true, message: 'WA v2 diputus. Auto reconnect nonaktif.' });
  } catch (error) {
    return res.status(500).json({ ok: false, message: String(error?.message || error) });
  }
});

app.get('/history', (req, res) => {
  const limit = Number(req.query?.limit || 120);
  const rows = readHistory(limit);
  return res.json({
    ok: true,
    count: rows.length,
    items: rows,
  });
});

app.post('/history/clear', (_req, res) => {
  try {
    if (fs.existsSync(HISTORY_FILE)) {
      fs.writeFileSync(HISTORY_FILE, '', 'utf8');
    }
    appendHistory({ type: 'control', event: 'history_clear' });
    return res.json({ ok: true, message: 'History chat dibersihkan.' });
  } catch (error) {
    return res.status(500).json({ ok: false, message: String(error?.message || error) });
  }
});

startSock().catch((error) => {
  // Keep process running so health endpoint still indicates boot issue.
  state.lastConnectionUpdate = {
    connection: 'boot_failed',
    ts: new Date().toISOString(),
    hasQr: false,
    error: String(error?.message || error),
  };
});

app.listen(PORT, HOST, () => {
  console.log(`wa-caraka-v2 listening on http://${HOST}:${PORT}`);
});
