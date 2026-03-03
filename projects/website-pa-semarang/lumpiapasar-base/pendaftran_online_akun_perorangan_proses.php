<?php	
// Output JSON
function outputJSON($msg, $status = 'Error'){
    header('Content-Type: application/json');
    die(json_encode(array(
        'data' => $msg,
        'status' => $status
    )));
}
function curl($url, $data){
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    $output = curl_exec($ch); 
    curl_close($ch);      
    return $output;
}
// Check for errors
if($_FILES['scan_ktp']['error'] > 0){
    outputJSON('An error ocurred when uploading.');
}

//if(!getimagesize($_FILES['scan_ktp']['tmp_name'])){
//    outputJSON('Please ensure you are uploading an image.');
//}

// Check filetype
if($_FILES['scan_ktp']['type'] != 'image/jpeg' && $_FILES['scan_ktp']['type'] != 'image/png' && $_FILES['scan_ktp']['type'] != 'application/pdf'  ){
    outputJSON('Silahkan Pilih Dengan Jenis Gambar atau pdf.');
}

// Check filesize
if($_FILES['scan_ktp']['size'] > 5000000000){
    outputJSON('File uploaded exceeds maximum upload size.');
}

// Check if the file exists
//if(file_exists('upload/' . $_FILES['scan_ktp']['name'])){
//    outputJSON('File with that name already exists.');
//}

// Upload file
foreach($_POST as $key=>$value) {$$key=$value;}
$nama_file=	str_replace("  ", " ", trim($_FILES['scan_ktp']['name']));
$nama_file=	str_replace(" ", "_", $nama_file);
$nama_file=$id_akun."_".ereg_replace("[^A-Za-z0-9._]", "", $nama_file);
$pesan="";
if(!move_uploaded_file($_FILES['scan_ktp']['tmp_name'], 'upload/' .$nama_file )){
	
    outputJSON('Error uploading file - check destination is writeable.');
}else
{ 
	$data = array(
				'id_akun'=>$id_akun,
				'nama'=>$nama,
				'tempat_lahir'=>$tempat_lahir,
				'tanggal_lahir'=>$tanggal_lahir,
				'nik'=>$nik,
				'bank'=>$bank,
				'nomor_rekening'=>$nomor_rekening,
				'bank_akun'=>$bank_akun,
				'nomor_telepon'=>$nomor_telepon,
				'email'=>$email,
				'alamat'=>$alamat,
				'agama'=>$agama,
				'pekerjaan'=>$pekerjaan,
				'pendidikan'=>$pendidikan,
				'kebutuhan_khusus'=>$kebutuhan_khusus,
				'status_kawin'=>$status_kawin,
				'scan_ktp'=>$nama_file,
				'diinput_tanggal'=>date("Y-m-d H:i:s")
	);

	//simpan
	include("_sys_koneksi.php");
	$InertKan = new Tambah_sekunder();
	$hasil=$InertKan->tambah_data_sekunder("pengguna_ecourt", $data) ;
	//simpan
	//kirim email
    $smtp=getenv('SMTP_HOST') ?: 'smtp.gmail.com';	
    $email_satker=getenv('SMTP_USER') ?: '';	
    $password=getenv('SMTP_PASS') ?: '';
    if ($email_satker === '' || $password === '') {
        outputJSON("Konfigurasi SMTP belum lengkap. Set SMTP_USER dan SMTP_PASS di environment server.");
    }
	$sebutan_email='Ecourt Pengadilan Agama Semarang';
	$nama=$nama;
	$email=$email;
	$subjek='Permohonan Akun E-Court Non Advokat bagi perorangan';
	$html_email="<center>
        <table border='0' cellpadding='3' cellspacing='3' style='max-width:500px'>
            <tr>
                <td style='background:#005291;color:white;text-align:center'><h3>Permohonan Akun E-Court bagi perorangan (bukan advokat)</h3></td>
            </tr>
            <tr>
                <td>
                    Berikut adalah Permohonan Akun E-Court bagi perorangan (bukan advokat) yang sudah diajukan ke Pengadilan Agama Semarang :
                    <table  border='0' cellpadding='3' cellspacing='3'>
                        <tr><td>Nama</td><td>:</td><td>$nama</td></tr>
                        <tr><td>Tempat Lahir</td><td>:</td><td>$tempat_lahir</td></tr>
                        <tr><td>Tanggal Lahir</td><td>:</td><td>$tanggal_lahir</td></tr>
                        <tr><td>NIK</td><td>:</td><td>$nik</td></tr>
                        <tr><td>Bank</td><td>:</td><td>$bank</td></tr>
                        <tr><td>Nomor Rekening</td><td>:</td><td>$nomor_rekening</td></tr>
                        <tr><td>Nama di buku rekening</td><td>:</td><td>$bank_akun</td></tr>
                        <tr><td>Nomor Telepon</td><td>:</td><td>$nomor_telepon</td></tr>
                        <tr><td>Email</td><td>:</td><td>$email</td></tr>
                        <tr><td>Alamat</td><td>:</td><td>$alamat</td></tr>
                        <tr><td>Agama</td><td>:</td><td>$agama</td></tr>
                        <tr><td>Pekerjaan</td><td>:</td><td>$pekerjaan</td></tr>
                        <tr><td>Pendidikan</td><td>:</td><td>$pendidikan</td></tr>
                        <tr><td>Berkebutuhan Khusus</td><td>:</td><td>$kebutuhan_khusus</td></tr>
                        <tr><td>Status Kawin</td><td>:</td><td>$status_kawin</td></tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td  style='background:#2196F3;color:white;text-align:center'>STATUS :<br> MENUNGGU DIPROSES
                </td>
            </tr>
            <tr>
                <td>Ini adalah pesan dari e-notif, tidak usah dibalas.</td>
            </tr>
        </table>
        </center>";
        $data["smtp"]='smtp.gmail.com';

    
    include('_kirim_email.php');
	//kirim email
}

// Success!
outputJSON($pesan, 'success');

?>
