/*
Whatsapp API untuk Pengadilan Agama Semarang
Dibuat oleh : Mohammad Roy Irawan
Hak Cipta (c) 2024 Mohammad Roy Irawan

Nama File  : libs/dbconn.js
Keterangan : Library untuk membuka koneksi pooling MySQL
*/

// Load library MySQL
const mysql = require('mysql2');

// Export modul untuk membuka MySQL Connection Pool
module.exports = mysql.createPool({
    connectionLimit : 10,
    host: process.env.DBHOST,
    port: process.env.DBPORT,
    user: process.env.DBUSER,
    password: process.env.DBPASS,
    database: process.env.DBNAME,
    timezone: process.env.DBTZ,
    debug: false
});
