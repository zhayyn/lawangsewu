/*
Whatsapp API untuk Pengadilan Agama Semarang
Dibuat oleh : Mohammad Roy Irawan
Hak Cipta (c) 2024 Mohammad Roy Irawan

Nama File  : web-socket.js
Keterangan : Library untuk menjalankan websocket guna menampilkan qrcode dari client whatsapp
*/

const { Server } = require('socket.io'); // Load library socket.io
const logger = require('./logger.js'); // Load library logging

// Memulai websocket
const initiateSocket = (server) => {
    const io = new Server(server, {
        path: "/socket/"
    });

    io.on("connection", (socket) => {
        let ip = socket.request.headers['x-forwarded-for'] || socket.request.connection.remoteAddress;
        logger.info('Client IP ' + ip + ' terkoneksi web socket');
    })

    return io;
};

module.exports = initiateSocket;
