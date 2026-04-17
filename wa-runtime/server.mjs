import { makeWASocket, useMultiFileAuthState, DisconnectReason, Browsers, fetchLatestBaileysVersion, delay } from '@whiskeysockets/baileys';
import express from 'express';
import bodyParser from 'body-parser';
import pino from 'pino';
import axios from 'axios';
import QRCode from 'qrcode';

const app = express();
app.use(bodyParser.json());

const PORT = 8790;
const AUTH_TOKEN = 'lawangsewu2026';
let WEBHOOK_URL = process.env.WEBHOOK_CALLBACK_URL || 'https://lawangsewu.pa-semarang.go.id/api/wa-caraka/webhook/inbound';

const logger = pino({ level: 'silent' });

let sock;
let currentQR = '';
let isConnected = false;
let user = null;
let messagesStore = [];

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
        }
    });

    sock.ev.on('messages.upsert', async m => {
        if (m.type !== 'notify') return;
        
        for (let msg of m.messages) {
            if (msg.key.fromMe) continue;
            
            const from = msg.key.remoteJid.replace('@s.whatsapp.net', '');
            const id = msg.key.id;
            let text = '';
            let type = 'text';

            if (msg.message?.conversation) {
                text = msg.message.conversation;
            } else if (msg.message?.extendedTextMessage) {
                text = msg.message.extendedTextMessage.text;
            } else if (msg.message?.imageMessage) {
                text = msg.message.imageMessage?.caption || '';
                type = 'image';
            } else if (msg.message?.documentMessage) {
                text = msg.message.documentMessage?.title || '';
                type = 'document';
            }

            const payload = {
                from: from,
                to: user?.id?.split(':')[0] || '',
                text: text,
                type: type,
                id: id,
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

app.post('/webhook/register', (req, res) => {
    const { url } = req.body;
    if (url) {
        WEBHOOK_URL = url;
        res.json({ ok: true, registered: true, callbackUrl: WEBHOOK_URL });
    } else {
        res.status(400).json({ ok: false, error: 'URL is required' });
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
        const jid = to + '@s.whatsapp.net';
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
    startSock();
});
