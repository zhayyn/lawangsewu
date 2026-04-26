/*
Whatsapp API untuk Pengadilan Agama Semarang
Dibuat oleh : Mohammad Roy Irawan
Hak Cipta (c) 2024 Mohammad Roy Irawan

Nama File  : libs/utils.js
Keterangan : Library untuk berbagai utilitas  yang dibutuhkan aplikasi
*/

// Melakukan setting header response
const setResponseHeader = (res, status, type) => {
    let utcStr = new Date().toUTCString();
    res.status(status);
    res.type(type);
    res.header("Cache-Control", "no-cache, no-store, must-revalidate");
    res.header("Pragma", "no-cache");
    res.header("Expires", utcStr);
    res.header("Last-Modified", utcStr);
}

// Melakukan validasi Security Token
const isValidToken = (token) => {
    let server_token = process.env.TOKEN || 0; // Apabila token tidak ditemukan, set  0 sebagai token
    if(token != server_token){
        return false;
    } else {
        return true;
    }
}

// Memastikan nomor terdaftar di whatsapp
// const checkRegisteredNumber = async function(client, number) {
//     const isRegistered = await client.isRegisteredUser(number);
//     return isRegistered;
// }

const checkRegisteredNumber = async function(client, number) {
    try {
        const isRegistered = await client.isRegisteredUser(number);
        return isRegistered;
    } catch (err) {
        console.error("Check number error:", err.message);
        return false;
    }
}

// Memformat nomor agar sesuai dengan standar penulisan nomor whatsapp
// const phoneNumberFormatter = (number) => {
//     // 1. Menghilangkan karakter selain angka
//     let formatted = number.replace(/\D/g, '');

//     // 2. Menghilangkan angka 0 di depan (prefix)
//     //    Kemudian diganti dengan 62
//     if (formatted.startsWith('0')) {
//       formatted = '62' + formatted.substr(1);
//     }

//     if (!formatted.endsWith('@c.us')) {
//       formatted += '@c.us';
//     }

//     return formatted;
// }

const phoneNumberFormatter = (number) => {
    const raw = String(number || '');

    // Pertahankan group JID apa adanya — jangan diubah ke @c.us
    if (raw.endsWith('@g.us')) {
        return raw;
    }

    // Pertahankan LID JID apa adanya untuk fallback pengiriman saat PN belum resolve
    if (raw.endsWith('@lid')) {
        return raw;
    }

    // Ambil hanya bagian sebelum @ kalau ada
    let formatted = raw.split('@')[0];

    // Hapus semua karakter non-digit
    formatted = formatted.replace(/\D/g, '');

    // Jika diawali 0 → ubah ke 62
    if (formatted.startsWith('0')) {
        formatted = '62' + formatted.substring(1);
    }

    // Jika diawali 8 (tanpa 0) → tambahkan 62
    else if (formatted.startsWith('8')) {
        formatted = '62' + formatted;
    }

    // Jika sudah diawali 62 → biarkan
 
    return formatted + '@c.us';
};


module.exports = { setResponseHeader, isValidToken, checkRegisteredNumber, phoneNumberFormatter };