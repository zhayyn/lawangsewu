/*
Whatsapp API untuk Pengadilan Agama Semarang
Dibuat oleh : Mohammad Roy Irawan
Hak Cipta (c) 2024 Mohammad Roy Irawan

Nama File  : libs/logger.js
Keterangan : Library untuk mencatat pesan informasi dan pesan kesalahan
*/

// Load Library winston
const winston = require('winston');

// Membuat fungsi untuk mencatat pesan informasi maupun pesan kesalahan yang terjadi
const logger = winston.createLogger({
    level: 'info',
    format: winston.format.combine(
        winston.format.timestamp({format: 'YYYY-MM-DD HH:mm:ss'}),
        winston.format.json(),
    ),
    defaultMeta: {service: 'WA-API'},
    transports: [
        new winston.transports.File({filename: './logs/wapi.error.log', level: 'error'}),
        new winston.transports.File({filename: './logs/wapi.combine.log'})
    ],
});

// Ketika environment debug, menampilkan pesan informasi dan pesan kesalahan ke layar
if(process.env.ENV == 'debug'){
    logger.add(new winston.transports.Console({
        format: winston.format.json()
    }));
}

module.exports = logger;