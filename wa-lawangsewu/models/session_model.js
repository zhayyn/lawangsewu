/*
Whatsapp API untuk Pengadilan Agama Semarang
Dibuat oleh : Mohammad Roy Irawan
Hak Cipta (c) 2024 Mohammad Roy Irawan

Nama File  : libs/session_model.js
Keterangan : Model untuk melakukan query ke database
*/

// Membuat sesi whatsapp baru dan menyimpan ke database
const sessionDBNew = (db, id, url, callback) => {
    let strInsertSessionQuery = "INSERT INTO wa_session(session_id, url_api_hook) VALUES(?, ?)";
    db.query(strInsertSessionQuery, [id, url], (err, result) => {
        if(err) {
            callback(err, null);
        } else {
            callback(null, 'Data session dengan id : ' + id + ' telah berhasil ditambahkan');
        }
    });
}

// Menghapus sesi whatsapp dari database
const sessionDBDelete = (db, id, callback) => {
    let strDeleteSessionQuery = "DELETE FROM wa_session WHERE BINARY session_id = ?";
    db.query(strDeleteSessionQuery, id, (err, result) => {
        if(err){
            callback(err, false, null);
        } else if(result.affectedRows === 0) {
            callback(null, false, 'Tidak berhasil menghapus data session atau session id ' + id + 'tidak ditemukan')
        } else {
            callback(null, true, 'Data session dengan id : ' + id + ' telah berhasil dihapus')
        }
    })
}

// Membaca sesi whatsapp dari database
const sessionDBSelect = (db, callback) => {
    let strSelectSessionQuery = "SELECT session_id AS id, url_api_hook AS url FROM wa_session ORDER BY timestamp";
    db.query(strSelectSessionQuery, (err, result) => {
        if(err){
            callback(err, null);
        } else {
            callback(null, result);
        }
    })
}

module.exports = { sessionDBNew, sessionDBDelete, sessionDBSelect };