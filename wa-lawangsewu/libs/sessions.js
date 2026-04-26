/*
Whatsapp API untuk Pengadilan Agama Semarang
Dibuat oleh : Mohammad Roy Irawan
Hak Cipta (c) 2024 Mohammad Roy Irawan

Nama File  : libs/sessions.js
Keterangan : Library untuk mengelola sesi whatsapp 
*/

const {Client, LocalAuth, version} = require('whatsapp-web.js'); // modul whatsapp-web.js
//console.log(version);
const qrCodeTerminal = require('qrcode-terminal'); // modul qrcode-terminal
const qrCode = require('qrcode'); // modul qrcode
const axios = require('axios'); // modul axios untuk melakukan request ke url hookAPI dari client
const logger = require('./logger.js'); // Load library logging

// Membuka sesi whatsapp
const session = (id, hook, io) => {
    let sessionId = id; // ID sesi Whatsapp
    let hookApi = hook; // URL Hook API
    let ioSocket = io; // Websocket

    // Menggenerate User Agent secara random
    const generateRandomUA = () => {
        // Array of random user agents
        const userAgents = [
          'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36',
          'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36',
          'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
          'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
          'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
          'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36',
          'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36'
        ];
        // Get a random index based on the length of the user agents array 
        const randomUAIndex = Math.floor(Math.random() * userAgents.length);
        // Return a random user agent using the index above
        return userAgents[randomUAIndex];
    }

    // Mengirim request ke URL HookAPI
    const postData = (url, sessId, event, dataSend) => {
        let data = {id: sessId, event: event, data: dataSend};
        axios.post(url, data)
        .then(res => {
            let strHasil = {status: res.status, result: res.data};
            strHasil = JSON.stringify(strHasil);
            logger.info('Session ID ' + sessId + ' on ' + event + ' send to ' + url + ' with result ' + strHasil);
        })
        .catch(err => {
            logger.error(' Session ID ' + sessId + ' on ' + event + ' send to ' + url + ' with error ' + err);
        })
    }

    const normalizePhoneToCUsJid = (value) => {
        const digits = String(value || '').replace(/\D/g, '');
        if (!digits) {
            return '';
        }

        if (digits.startsWith('0')) {
            return `62${digits.substring(1)}@c.us`;
        }

        if (digits.startsWith('8')) {
            return `62${digits}@c.us`;
        }

        return `${digits}@c.us`;
    };

    const isLidJid = (value) => String(value || '').endsWith('@lid');

    // Membuat Object Client Whatsapp
    const client = new Client({
        puppeteer: {
            restartOnAuthFail: true,
	    //executablePath: '/usr/bin/chromium-browser',
            headless: true,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--no-first-run',
                '--no-zygote',
                '--disable-gpu',
            ],
        },
	userAgent: generateRandomUA(),
        authStrategy: new LocalAuth({ clientId: sessionId, dataPath: './sessions/' }),
        webVersionCache: {
            type: 'local',
        },
    });

    // Menginisialisasi object Client Whatsapp
    client.initialize().catch((err) => {
        logger.error('Session ID : ' + sessionId + ' gagal initialize: ' + err.message);
    });

    // Event saat client menampilkan qr code
    client.on('qr', code => {
        try{
            if(process.env.ENV === 'debug'){
                logger.info('Generating QR Code untuk session ID : ' + sessionId);
                qrCodeTerminal.generate(code,{small: true});
            }
            qrCode.toDataURL(code, (err, url) => {
                if(err) {
                    logger.error('Session ID : ' + sessionId + '; Error : ' + err.message);
                } else {
                    ioSocket.emit(sessionId + '_qr', url);
                    logger.info('Session ID : ' + sessionId + '; QR terkirim');
                }
            });
        } catch(error) {
            logger.error('Session ID : ' + sessionId + ' Error : ' + error.message + ' on session.qr');
        }
    });

    // Event saat client terotentifikasi
    client.on('authenticated', () => {
        try{
            logger.info('Session ID : ' + sessionId + ' is authenticated');
        } catch(error){
            logger.error('Session ID : ' + sessionId + ' Error : ' + error.message + ' on session.authenticated');
        }
    });

    // Event saat client siap
    client.on('ready', () => {
        try{
            ioSocket.emit(sessionId + '_ready', 'Sesi Whatsapp siap...');
            logger.info('Session ID : ' + sessionId + ' is ready');
        } catch(error) {
            logger.error('Session ID : ' + sessionId + ' Error : ' + error.message + ' on session.ready');
        }
    });

    // Event saat client menerima pesan
    client.on('message', async (message) => {
        try{
            if( ! message.isStatus){
                const rawFrom = String(message.from || '');
                const rawAuthor = String(message.author || '');
                const isGroup = rawFrom.endsWith('@g.us');
                const senderJid = isGroup ? (rawAuthor || rawFrom) : rawFrom;
                let groupName = '';

                if (isGroup) {
                    try {
                        const groupChat = await message.getChat();
                        groupName = String(
                            groupChat?.name
                            || groupChat?.formattedTitle
                            || groupChat?.subject
                            || '',
                        ).trim();
                    } catch (_) {
                        groupName = '';
                    }
                }

                const senderPnFromRaw = String(
                    message?._data?.senderPn
                    || message?._data?.authorPn
                    || message?._data?.participantPn
                    || message?._data?.fromPn
                    || message?._data?.sender?.pn
                    || message?._data?.author?.pn
                    || '',
                );

                let contactJid = '';
                try {
                    const contact = await message.getContact();
                    contactJid = normalizePhoneToCUsJid(contact?.number || '');

                    // Jika contact.number kosong (LID contact), coba alternatif lain
                    if (!contactJid && isLidJid(senderJid)) {
                        // 1) Coba contact.id.user jika servernya bukan 'lid' (berarti sudah PN)
                        if (contact?.id?.server !== 'lid' && contact?.id?.user) {
                            contactJid = normalizePhoneToCUsJid(contact.id.user);
                        }

                        // 2) Coba via WA web internal store (ContactCollection) — tersedia di pupPage
                        if (!contactJid) {
                            try {
                                const pnFromStore = await client.pupPage.evaluate(async (lid) => {
                                    try {
                                        const c = window.Store?.ContactCollection?.get?.(lid)
                                            || window.Store?.Contacts?.get?.(lid);
                                        if (c?.pn) return c.pn;
                                        if (c?.id?.server !== 'lid' && c?.id?.user) return c.id.user;
                                    } catch (_) {}
                                    return null;
                                }, senderJid);

                                if (pnFromStore) {
                                    contactJid = normalizePhoneToCUsJid(String(pnFromStore));
                                }
                            } catch (_) {}
                        }
                    }
                } catch (err) {
                    contactJid = '';
                }

                const fromPnCandidate = senderPnFromRaw || contactJid || '';
                const resolvedFromJid = (isLidJid(senderJid) && fromPnCandidate)
                    ? fromPnCandidate
                    : senderJid;

                // Download media jika pesan mengandung file (gambar, video, audio, dokumen)
                let mediaData = null;
                let mediaMimetype = null;
                let mediaFileName = null;
                if (message.hasMedia) {
                    try {
                        const downloaded = await message.downloadMedia();
                        if (downloaded && downloaded.data) {
                            mediaData = `data:${downloaded.mimetype};base64,${downloaded.data}`;
                            mediaMimetype = downloaded.mimetype || null;
                            mediaFileName = downloaded.filename || null;
                        }
                    } catch (mediaErr) {
                        logger.error('Session ID : ' + sessionId + ' Gagal download media: ' + mediaErr.message);
                    }
                }

                postData(hookApi, sessionId, 'message', {
                    number: senderJid,
                    message: message.body,
                    type: message.type || 'chat',
                    chatId: rawFrom,
                    groupName,
                    groupSubject: groupName,
                    participant: rawAuthor,
                    fromRaw: senderJid,
                    fromLid: isLidJid(senderJid) ? senderJid : '',
                    fromPn: fromPnCandidate,
                    resolvedFromJid,
                    isLid: isLidJid(senderJid),
                    isGroup,
                    messageId: message?.id?._serialized || '',
                    timestamp: message?.timestamp
                        ? new Date(message.timestamp * 1000).toISOString()
                        : new Date().toISOString(),
                    // Media payload (null jika bukan pesan media)
                    mediaData,
                    mimetype: mediaMimetype,
                    fileName: mediaFileName,
                    caption: message.hasMedia ? (message.body || '') : undefined,
                    meta: {
                        sender: message?._data?.sender || '',
                        senderPn: message?._data?.senderPn || '',
                        author: message?._data?.author || '',
                        authorPn: message?._data?.authorPn || '',
                        participant: message?._data?.participant || '',
                        participantPn: message?._data?.participantPn || '',
                        notifyName: message?._data?.notifyName || message?.notifyName || '',
                        subject: groupName || message?._data?.chat?.formattedTitle || message?._data?.chat?.name || '',
                    },
                });
                logger.info('Session ID : ' + sessionId + ' is receiving message' + (message.hasMedia ? ' [media]' : ''));
            }
        } catch(error) {
            logger.error('Session ID : ' + sessionId + ' Error : ' + error.message + ' on session.message');
        }
    });

    // Event saat client terputus dari whatsapp
    client.on('disconnected', async (reason) => {
        try{
            postData(hookApi, sessionId, 'disconnected', {"disconnected": true});
            logger.info('Session ID : ' + sessionId + ' is disconnected with reason : ' + reason);
            // Avoid immediate re-initialize loops here; session recovery is handled by bridge restart/refresh endpoints.
            client.destroy().catch(err => {
                logger.error(err.message);
            });
        } catch(error) {
            logger.error('Session ID : ' + sessionId + ' Error : ' + error.message + ' on session.disconnected');
        }
    });

    // Mengembalikan object 
    return {id: sessionId, obj: client};
}

module.exports = session;
