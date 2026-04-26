/*
Whatsapp API untuk Pengadilan Agama Semarang
Dibuat oleh : Mohammad Roy Irawan
Hak Cipta (c) 2024 Mohammad Roy Irawan

Nama File  : index.js
Keterangan : Script utama untuk menjalankan webservice Whatsapp API PA Semarang
*/

// Periksa apakah file setting ada atau tidak
const fs = require('fs');
if(!fs.existsSync('.env')){
    // Jika file setting tidak ditemukan, hentikan aplikasi
    console.log('File setting tidak ditemukan, server tidak dapat dijalankan!');
    process.exit(0);``
}

// Membaca parameter dari file setting
const dotenv = require('dotenv');
dotenv.config(); // Membaca parameter environment dari file setting

process.on('uncaughtException', (err) => {
    console.error('[runtime] uncaughtException:', err?.stack || err?.message || err);
});

process.on('unhandledRejection', (reason) => {
    console.error('[runtime] unhandledRejection:', reason?.stack || reason?.message || reason);
});

// Membaca parameter server
const server_host = process.env.HOST || '0.0.0.0'; // Apabila parameter host tidak ditemukan, set 0.0.0.0 sebagai host
const server_port = process.env.PORT || 88888;  //Apabila parameter port tidak ditemukan, set 8888 sebagai port
const server_token = process.env.TOKEN || 0; // Apabila token tidak ditemukan, set  0 sebagai token

// Load modul Node.js web application framework
const express = require('express');
const app = express();

// Load modul Node.js untuk validasi
const isUrlHttp = require('is-url-http');

// Load modul Node.js HTTP Module
const http = require('http');
const server = http.createServer(app);

// Load modul Node.js Path Module
const path = require('path');

// Load module Node.js Cryptographic Module
const nanoid = require('nanoid');

const { MessageMedia } = require('whatsapp-web.js');
const axios = require('axios');

// Load library untuk menginisiasi web socket
const initiateSocket = require('./libs/web-socket');
const io = initiateSocket(server);

// Load library database connection
const db = require('./libs/dbconn');

// Load library database model
const sessionModel = require('./models/session_model');

// Load library utils
const utils = require('./libs/utils');

// Load library logging
const logger = require('./libs/logger');

// Load library WA session
const session = require('./libs/sessions');
let sessionStore = []; // WA Session Array

// Membaca data session dari database
sessionModel.sessionDBSelect(db, (err,data) => {
    if(err){
        logger.error(err.message);
    } else {
        for(var i=0; i < data.length; i++){
            logger.info('Load session id : ' + data[i].id + ' session hook url : ' + data[i].url);
            let client = session(data[i].id, data[i].url, io);
            sessionStore.push(client);
        }
    }
});

// Mengatur IP Forwarding dari Proxy
app.set('trust proxy', (ip) => {
    if(ip=='127.0.0.1'){
         return true;
    } else {
         return false;
    }
});

app.disable('x-powered-by'); // menghapus header x-powered-by
app.use(express.static(path.join(__dirname,'views'))); // menampilkan file statis di folder views
app.use(express.json());
app.use(express.urlencoded({extended: true}));
app.use((err, req, res, next) => {
    if (err instanceof SyntaxError && err.status === 400 && 'body' in err) {
        let strHasil = { status: 400, sucess: false, message: err.message};
        strHasil = JSON.stringify(strHasil);
        logger.error(strHasil);
        return res.status(400).send(strHasil); // Bad request
    }
    next();
});

// Menampilkan main.html ketika diakses di browser
app.get('/', (req, res) => {
    utils.setResponseHeader(res, 200, 'html');
    res.sendFile('./views/index.html', {root: __dirname});
});

// Menampilkan qr.html ketika diakses di browser
app.get('/qr', (req, res) => {
    utils.setResponseHeader(res, 200, 'html');
    res.sendFile('./views/qr.html', {root: __dirname});
});

// Membuat session whatsapp client baru
app.post('/session', async (req, res) => {
    let strHasil;
    // Allow external adapters (bridge) to pin a stable session id.
    let id = req.body.id || nanoid.nanoid();
    let url = req.body.url || '';
    if( ! utils.isValidToken(req.body.token) ) {
        strHasil = {success: false, message: "Security token tidak valid"};
        utils.setResponseHeader(res, 200, 'application/json');
        strHasil = JSON.stringify(strHasil);
        res.send(strHasil);
        logger.info('Client IP ' + req.ip + ', ' + strHasil);
    } else if( ! isUrlHttp(url)) {
        strHasil = {success: false, message: "URL harus diisi dengan format yang benar!"};
        utils.setResponseHeader(res, 200, 'application/json');
        strHasil = JSON.stringify(strHasil);
        res.send(strHasil);
        logger.info('Client IP ' + req.ip + ', ' + strHasil);
    } else {
        try {
            let client = session(id, url, io);
            sessionStore.push(client);
            sessionModel.sessionDBNew(db, id,url, function(err,data){
                if(err){
                    strHasil = {success: false, message: err.message};
                } else {
                    strHasil = {success: true, message: data, id: id};
                }
                utils.setResponseHeader(res, 200, 'application/json');
                strHasil = JSON.stringify(strHasil);
                res.send(strHasil);
                logger.info('Client IP ' + req.ip + ', ' + strHasil);
            });
        } catch(error) {
            strHasil = {success: false, message: "Tidak berhasil menambahkan session baru : " + error.message};
            utils.setResponseHeader(res, 200, 'application/json');
            strHasil =  JSON.stringify(strHasil);
            logger.info('Client IP ' + req.ip + ', ' + strHasil);
            res.send(strHasil);
        }
    }
});

// Menghapus session whatsapp client
app.delete('/session', async (req, res) => {
    let strHasil;
    let id = req.body.id || '';
    if( ! utils.isValidToken(req.body.token) ) {
        strHasil = {success: false, message: "Security token tidak valid"};
        utils.setResponseHeader(res, 200, 'application/json');
        strHasil = JSON.stringify(strHasil);
        res.send(strHasil);
        logger.info('Client IP ' + req.ip + ', ' + strHasil);
    } else {
        let deleteIndex = sessionStore.findIndex(object => {
            return object.id === id;
        });
        if(deleteIndex >= 0){
            const sessionEntry = sessionStore[deleteIndex];
            let client = sessionEntry?.obj || sessionEntry;
            try {
                if (client?.pupPage) {
                    await Promise.race([
                        client.logout().catch(() => {}),
                        new Promise(resolve => setTimeout(resolve, 3000)),
                    ]);
                }
            } catch (error) {
                logger.warn('Delete session logout warning: ' + error.message);
            }

            try {
                if (typeof client?.destroy === 'function') {
                    await Promise.race([
                        client.destroy().catch(() => {}),
                        new Promise(resolve => setTimeout(resolve, 3000)),
                    ]);
                }
            } catch (error) {
                logger.warn('Delete session destroy warning: ' + error.message);
            }

            if (client && typeof client === 'object') {
                Object.keys(client).forEach(key => {
                    try {
                        delete client[key];
                    } catch (_) {}
                });
            }
            sessionStore.splice(deleteIndex, 1);
        }
        sessionModel.sessionDBDelete(db, id, function(err, success, data){
            if(err){
                strHasil = {success: success, message: err.message};
            } else {
                strHasil = {success: success, message: data};
            }
            utils.setResponseHeader(res, 200, 'application/json');
            strHasil = JSON.stringify(strHasil);
            res.send(strHasil);
            logger.info('Client IP ' + req.ip + ', ' + strHasil);
        });
    }
});

const sendMessage = async (id, number, message, optional, req, res) => {
    let strHasil;
    logger.info('Number WA : ' + number);
    number2 = number;
    number = utils.phoneNumberFormatter(number);

    if( ! utils.isValidToken(req.body.token)) {
        strHasil = {success: false, message: "Security token tidak valid"};
        utils.setResponseHeader(res, 200, 'application/json');
        strHasil = JSON.stringify(strHasil);
        res.send(strHasil);
        logger.info('Client IP ' + req.ip + ', ' + strHasil);
    } else {
        let clientIndex = sessionStore.findIndex(object => {
            return object.id === id;
        })
    
        if(clientIndex >= 0){
            try{
                client = sessionStore[clientIndex].obj;
                //number3 = await client.getNumberId(number2);
                //logger.info('Number WA : ' + number3);
    
                let state = await client.getState();
    
                if(state !== 'CONNECTED'){
                    strHasil = {success: false, message: "Session ID belum terkoneksi!"};
                    utils.setResponseHeader(res, 200, 'application/json');
                    strHasil = JSON.stringify(strHasil);
                    res.send(strHasil);
                    logger.info('Client IP ' + req.ip + ', ' + strHasil);
                } else {
                    // try{
                        // Grup WA (@g.us) dan LID (@lid) tidak perlu cek isRegisteredUser
                        const isSpecialJid = number.endsWith('@g.us') || number.endsWith('@lid');
                        let isRegisteredNumber = isSpecialJid ? true : await utils.checkRegisteredNumber(client, number);

                        if( ! isRegisteredNumber) {
                            strHasil = {success: false, message: "Nomor tidak valid/tidak terdaftar"};
                            utils.setResponseHeader(res, 200, 'application/json');
                            strHasil = JSON.stringify(strHasil);
                            res.send(strHasil);
                            logger.info('('+number+') Client IP ' + req.ip + ', ' + strHasil);
                        } else {
                            client.sendMessage(number, message, optional).then(response => {
                                strHasil = {success: true, message: "Berhasil mengirimkan pesan"};
                                utils.setResponseHeader(res, 200, 'application/json');
                                strHasil =  JSON.stringify(strHasil);
                                logger.info('('+ number +') Client IP ' + req.ip + ', ' + strHasil);
                                res.send(strHasil);
                            }).catch(err => {
                                strHasil = {success: false, message: "Tidak berhasil mengirimkan pesan : " + err.message};
                                utils.setResponseHeader(res, 200, 'application/json');
                                strHasil =  JSON.stringify(strHasil);
                                logger.info('Client IP ' + req.ip + ', ' + strHasil);
                                res.send(strHasil);
                            });
                        }

                    // } catch(error) {
                    //     strHasil = {success: false, message: "ERROR cek isRegisteredNumber : " + error.message};
                    //     utils.setResponseHeader(res, 200, 'application/json');
                    //     strHasil =  JSON.stringify(strHasil);
                    //     logger.info('(ERROR) Client IP ' + req.ip + ', ' + strHasil);
                    //     res.send(strHasil);                        
                    // }
                    


                }
            } catch(error) {
                strHasil = {success: false, message: "Tidak berhasil mengirimkan pesan : " + error.message};
                utils.setResponseHeader(res, 200, 'application/json');
                strHasil =  JSON.stringify(strHasil);
                logger.info('Client IP ' + req.ip + ', ' + strHasil);
                res.send(strHasil);
            }
        } else {
            strHasil = {success: false, message: "Session ID tidak valid/tidak terdaftar"};
            utils.setResponseHeader(res, 200, 'application/json');
            strHasil = JSON.stringify(strHasil);
            res.send(strHasil);
            logger.info('Client IP ' + req.ip + ', ' + strHasil);
        }
    }
};

// Mengirim pesan
app.post('/send-message', async (req, res) => {
    let id = req.body.id || '';
    let number = req.body.number || '';
    let message = req.body.message || '';

    await sendMessage(id, number, message, {}, req, res);
});

// Mengirim pesan dengan media
app.post('/send-media', async (req, res) => {
    let id = req.body.id || '';
    let number = req.body.number || '';
    let caption = req.body.caption || '';
    let fileUrl = req.body.file || '';
    let mediaKind = String(req.body.media_kind || '').toLowerCase().trim();
    let mimeType = req.body.mime_type || '';
    let fileName = req.body.file_name || '';
    let strHasil;

    if( ! isUrlHttp(fileUrl)){
        strHasil = {success: false, message: "Alamat URL file harus diisi dengan format yang benar!"};
        utils.setResponseHeader(res, 200, 'application/json');
        strHasil = JSON.stringify(strHasil);
        res.send(strHasil);
        logger.info('Client IP ' + req.ip + ', ' + strHasil);
    } else {
        try{
            const response = await axios.get(fileUrl, { responseType: 'arraybuffer', timeout: 45000 });
            const resolvedMimeType = String(mimeType || response.headers['content-type'] || 'application/octet-stream');
            const base64Data = Buffer.from(response.data).toString('base64');

            if (!fileName) {
                try {
                    const disposition = String(response.headers['content-disposition'] || '');
                    const match = disposition.match(/filename="?([^"]+)"?/i);
                    if (match && match[1]) {
                        fileName = match[1];
                    }
                } catch(error) {}
            }

            if (!fileName) {
                fileName = mediaKind === 'document' ? 'document' : 'media';
            }

            const media = new MessageMedia(resolvedMimeType, base64Data, fileName);
            const sendOptions = {};

            if (caption) {
                sendOptions.caption = caption;
            }

            if (mediaKind === 'document') {
                sendOptions.sendMediaAsDocument = true;
            }

            await sendMessage(id, number, media, sendOptions, req, res);
        } catch(error){
            strHasil = {success: false, message: "Tidak berhasil mengirimkan pesan : " + error.message};
            utils.setResponseHeader(res, 200, 'application/json');
            strHasil =  JSON.stringify(strHasil);
            logger.info('Client IP ' + req.ip + ', ' + strHasil);
            res.send(strHasil);
        }
    }
});

// Check state whatsappp client
app.post('/get-state', async (req, res) => {
    let token = req.body.token || '';
    let id = req.body.id || '';

    if( ! utils.isValidToken(token)){
        strHasil = {success: false, message: "Security token tidak valid"};
        utils.setResponseHeader(res, 200, 'application/json');
        strHasil = JSON.stringify(strHasil);
        res.send(strHasil);
        logger.info('Client IP ' + req.ip + ', ' + strHasil);
    } else {
        let clientIndex = sessionStore.findIndex(object => {
            return object.id === id;
        })

        if(clientIndex >= 0) {
            try{
                const sessionEntry = sessionStore[clientIndex];
                let client = sessionEntry?.obj || sessionEntry;

                if (!client) {
                    strHasil = {success: false, message: "Client session tidak ditemukan"};
                    utils.setResponseHeader(res, 200, 'application/json');
                    strHasil = JSON.stringify(strHasil);
                    res.send(strHasil);
                    logger.info('Client IP ' + req.ip + ', ' + strHasil);
                    return;
                }

                if (!client.pupPage) {
                    strHasil = {success: true, message: "Client sedang inisialisasi", state: "OPENING"};
                    utils.setResponseHeader(res, 200, 'application/json');
                    strHasil = JSON.stringify(strHasil);
                    res.send(strHasil);
                    logger.info('Client IP ' + req.ip + ', ' + strHasil);
                    return;
                }

                let state;
                try {
                    state = await client.getState();
                } catch (error) {
                    if (String(error?.message || '').includes("reading 'evaluate'")) {
                        state = 'OPENING';
                    } else {
                        throw error;
                    }
                }

                strHasil = {success: true, message: "Check state client ID " + id + " berhasil", state: state};
                utils.setResponseHeader(res, 200, 'application/json');
                strHasil = JSON.stringify(strHasil);
                res.send(strHasil);
                logger.info('Client IP ' + req.ip + ', ' + strHasil);
            } catch(error) {
                strHasil = {success: false, message: "Tidak berhasil check state client dengan pesan : " + error.message};
                utils.setResponseHeader(res, 200, 'application/json');
                strHasil =  JSON.stringify(strHasil);
                logger.info('Client IP ' + req.ip + ', ' + strHasil);
                res.send(strHasil);
            }
        } else {
            strHasil = {success: false, message: "Session ID tidak valid/tidak terdaftar"};
            utils.setResponseHeader(res, 200, 'application/json');
            strHasil = JSON.stringify(strHasil);
            res.send(strHasil);
            logger.info('Client IP ' + req.ip + ', ' + strHasil);
        }
    }
})

// Ketika terjadi error
server.on('error', (e) => {
    logger.error(e.message);
});

// Dump LID→PN mappings dari daftar kontak (untuk bridge sync)
app.post('/get-contacts', async (req, res) => {
    let token = req.body.token || '';
    let id = req.body.id || '';
    const requestedJids = Array.isArray(req.body.jids) ? req.body.jids : [];
    const includeDetails = req.body.includeDetails === true || requestedJids.length > 0;

    if (!utils.isValidToken(token)) {
        return res.status(401).json({ success: false, message: 'Security token tidak valid' });
    }

    let clientIndex = sessionStore.findIndex(o => o.id === id);
    if (clientIndex < 0) {
        return res.status(404).json({ success: false, message: 'Session ID tidak ditemukan' });
    }

    try {
        const sessionEntry = sessionStore[clientIndex];
        const client = sessionEntry?.obj || sessionEntry;
        if (!client || typeof client.getContacts !== 'function') {
            return res.status(503).json({ success: false, message: 'Client session belum siap' });
        }
        const contacts = await client.getContacts();
        const mappings = [];
        const normalizedRequestedJids = requestedJids
            .map((value) => String(value || '').trim())
            .filter((value) => value !== '');
        const contactBySerialized = new Map();

        for (const c of contacts) {
            const serialized = String(c?.id?._serialized || '');
            const isLid = serialized.endsWith('@lid');
            // wwjs exposes c.number as digits-only (e.g. "6281234567890")
            const pnDigits = String(c?.number || '').replace(/\D/g, '');

             if (serialized) {
                contactBySerialized.set(serialized, c);
            }

            if (isLid && pnDigits) {
                mappings.push({ lid: serialized, pn: `${pnDigits}@s.whatsapp.net` });
            }
        }

        if (!includeDetails) {
            return res.json({ success: true, total: contacts.length, mappings });
        }

        const items = [];
        for (const requestedJid of normalizedRequestedJids) {
            const isGroup = requestedJid.endsWith('@g.us');
            const contact = contactBySerialized.get(requestedJid) || null;

            let displayName = null;
            let groupName = null;
            let profilePhotoUrl = null;
            let resolvedJid = requestedJid;

            if (isGroup) {
                try {
                    const chat = await client.getChatById(requestedJid);
                    groupName = chat?.name || chat?.formattedTitle || chat?.subject || null;
                    resolvedJid = String(chat?.id?._serialized || requestedJid);
                } catch (err) {
                    logger.warn('get-contacts group lookup warning: ' + err.message);
                }
            } else if (contact) {
                displayName = contact?.pushname || contact?.name || contact?.shortName || null;
                resolvedJid = String(contact?.id?._serialized || requestedJid);
            } else {
                try {
                    const contactById = await client.getContactById(requestedJid);
                    displayName = contactById?.pushname || contactById?.name || contactById?.shortName || null;
                    resolvedJid = String(contactById?.id?._serialized || requestedJid);
                } catch (err) {
                    logger.warn('get-contacts contact lookup warning: ' + err.message);
                }
            }

            try {
                profilePhotoUrl = await client.getProfilePicUrl(resolvedJid);
            } catch (err) {
                profilePhotoUrl = null;
            }

            items.push({
                jid: requestedJid,
                resolvedJid,
                displayName: displayName || null,
                groupName: groupName || null,
                profilePhotoUrl: profilePhotoUrl || null,
            });
        }

        return res.json({ success: true, total: contacts.length, mappings, items });
    } catch (err) {
        logger.error('get-contacts error: ' + err.message);
        return res.status(500).json({ success: false, message: err.message });
    }
});


// Menjalankan server
server.listen(server_port, server_host, () => {
    console.log('Server berjalan pada http://' + server_host + ':' + server_port + ' dengan Security Token : ' + server_token + ' dan pid : ' + process.pid);
    logger.info('Server berjalan pada http://' + server_host + ':' + server_port + ' dengan Security Token : ' + server_token + ' dan pid : ' + process.pid);
});

// Fungsi untuk menghandle signal mematikan server
const handleGracefullShutdown = (signal) => {
    console.log('Sinyal ' + signal + ' diterima, persiapan menutup koneksi database, koneksi website dan mematikan server dengan pid : ' + process.pid);
    logger.info('Sinyal ' + signal + ' diterima, persiapan menutup koneksi database, koneksi website dan mematikan server dengan pid : ' + process.pid);
    db.end(); // Menutup koneksi database
    server.closeAllConnections(); // Menutup semua koneksi
    io.close(); // Menutup koneksi websocket

    // Mematikan server
    server.close((err) => {
        if(err){
            process.exitCode = 1;
        } else {
            process.exitCode = 0;
        }
    })
}

// Menghentikan proses server saat server dimatikan
process.on('SIGTERM', handleGracefullShutdown);

// Menghentikan proses server saat tombol ctrl+c ditekan
process.on('SIGINT', handleGracefullShutdown);

// Redirect non existent url ke root url
app.use((_, res) => res.redirect("/"));
