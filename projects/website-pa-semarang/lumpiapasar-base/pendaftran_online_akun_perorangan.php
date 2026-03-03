<?php
include('_sys_header.php');
$menu_aktif="akta_cerai";
?>
<div class="loading" id="loader">Loading&#8230;</div>
<!-- Header -->
<div class="w3-row w3-card-2 w3-padding w3-center w3-white">
    <div class="w3-col" style="width:30px">
        <a href="#" onclick="goBack()" class="headerButton goBack">
            <img src="assets/images/arrow_back-black-24dp.svg" class="w3-left w3-circle w3-margin-right" style="width:30px">
        </a>
    </div>
    <div class="w3-rest w3-cell-middle"><span style="font-size: 18px">PENDAFTARAN AKUN PERORANGAN</span></div>
</div> 
<!-- Header -->
<br>
<!-- Isi -->
<div class="w3-row" id="inputan">
    <div class="w3-container">
        <div class="w3-card-4">
            <header class="w3-container w3-blue">
                <h5>Formulir Permohonan Akun E-Court <b>bagi perorangan</b></h5>
            </header>

            <input type="hidden" id="id_akun" name="id_akun" value="<?php echo time().rand(100, 999)?>">
            <div class="w3-container">
                <div class="w3-panel w3-pale-yellow w3-border">
                    <p style="text-align:justify">Bagi selain advokat pendaftaran Akun E-Court Mahkamah Agung RI, dibuat oleh Pengadilan dimana akan mendaftarkan perkara, bukan melalui menu register di Portal E-Court Mahkamah Agung RI</p>
                    <p style="text-align:justify">Untuk mendapatkan Akun E-Court Mahkamah Agung RI, silahkan isikan formulir di bawah ini, setelah divalidasi oleh petugas maka kata sandi akan dikirimkan melalui email</p>
                </div>


                <hr>
                <p>
                    <b>Nama yang akan melakukan pendaftaran perkara:</b> <br>
                    <input class="w3-input w3-border w3-pale-green" placeholder="nama pihak yang akan mendaftar perkara, contoh : Ana binti Fulan" id="nama" name="nama" required="">Penulisan nama tidak diperbolehkan ada tanda petik ('), karena akan bermasalah pada tahap ePayment

                </p>
                <p>
                    <b>Tempat Lahir :</b><br>
                    <input class="w3-input w3-border w3-pale-green" placeholder="tempat lahir" list="daftar_kota" id="tempat_lahir" name="tempat_lahir" autocomplete="off" required="">
                    <datalist id="daftar_kota">
                        <option>Banjarnegara</option>
                        <option>Batang</option>
                        <option>Blora</option>
                        <option>Boyolali</option>
                        <option>Brebes</option>
                        <option>Cilacap</option>
                        <option>Demak</option>
                        <option>Jepara</option>
                        <option>Kajen</option>
                        <option>Karanganyar</option>
                        <option>Kebumen</option>
                        <option>Kendal</option>
                        <option>Klaten</option>
                        <option>Kudus</option>
                        <option>Mungkid</option>
                        <option>Pati</option>
                        <option>Pemalang</option>
                        <option>Purbalingga</option>
                        <option>Purwodadi</option>
                        <option>Purwokerto</option>
                        <option>Purworejo</option>
                        <option></option>
                        <option>Rembang</option>
                        <option>Slawi</option>
                        <option>Sragen</option>
                        <option>Sukoharjo</option>
                        <option>Temanggung</option>
                        <option>Ungaran</option>
                        <option>Wonogiri</option>
                        <option>Wonosobo</option>

                    </datalist>
                </p>
                <p>
                    <b>Tanggal Lahir : </b><br>
                    <input class="w3-input w3-border w3-pale-green" type="date" id="tanggal_lahir" name="tanggal_lahir" required="">
                </p>
                <p>
                    <b>Nomor Induk Kependudukan :</b> <br>
                    <input class="w3-input w3-border w3-pale-green" id="nik" name="nik" placeholder="isikan Nomor Induk Kependudukan" required="">
                </p>

                <p>
                    <b>Informasi Rekening</b><br>dipergunakan untuk pengembalian dana secara otomatis, apabila terdapat permasalahan pembayaran)<br>
                    Bank: <br>
                    <select class="w3-input w3-border w3-pale-green w3-white" id="bank" name="bank" required="">
                        <option value="-">Silahkan Pilih Bank</option>
                        <option>BANK MANDIRI SYARIAH</option>
                        <option>BANK MANDIRI</option>
                        <option>BRI SYARIAH</option>
                        <option>BRI</option>
                        <option>BTN</option>
                        <option>BCA</option>
                    </select><br>
                    <b>Nomor Rekening :</b><br>
                    <input class="w3-input w3-border w3-pale-green" id="nomor_rekening" name="nomor_rekening" placeholder="nomor rekening" required="">
                </p>
                <p>
                    <b>Nama pada Buku Rekening :</b><br>
                    <input class="w3-input w3-border w3-pale-green" id="bank_akun" name="bank_akun" placeholder="harus sama persis dengan nama pada buku tabungan" required="">
                </p>
                <p>
                    <b>Nomor Telepon / HP :</b><br>
                    <input class="w3-input w3-border w3-pale-green" id="nomor_telepon" name="nomor_telepon" placeholder="isikan nomor telepon" required="">
                </p>
                <p>
                    <b>Alamat E-Mail :</b> (pastikan alamat E-mail belum pernah didaftarkan di E-Court Mahkamah Agung)<br>
                    <input class="w3-input w3-border w3-pale-green" type="email" id="email" name="email" placeholder="Email dipergunakan untuk mendapatkan kata sandi, dan menerima panggilan sidang" required="">
                </p>
                <p>
                    <b>Alamat :</b><br>
                    <input class="w3-input w3-border w3-pale-green" id="alamat" name="alamat" placeholder="Alamat lengkap" required="">
                </p>
                <p>
                    <b>Agama :</b><br>
                    <select class="w3-input w3-border w3-pale-green w3-white" id="agama" name="agama" required="">
                        <option value="-">Silahkan Pilih Agama</option>
                        <option>Islam</option>
                        <option>Protestan</option>
                        <option>Katolik</option>
                        <option>Budha</option>
                        <option>Hindu</option>
                    </select>
                </p>
                <p>
                    <b>Pendidikan :</b><br>
                    <select class="w3-input w3-border w3-pale-green w3-white" id="pendidikan" name="pendidikan" required="">
                        <option value="-">Pilih</option><option>Tidak Ada</option><option>TK</option><option>SD</option><option>SLTP</option><option>SMA</option><option>D1</option><option>D2</option><option>D3</option><option>D4</option><option>S1</option><option>S2</option><option>S3</option><option>Belum Sekolah</option>
                    </select>
                </p>

                <p>
                    <b>Pekerjaan :</b><br>
                    <input class="w3-input w3-border w3-pale-green" id="pekerjaan" name="pekerjaan" placeholder="isikan pekerjaan" required="">
                </p>
                <p>
                    <b>Berkebutuhan Khusus :</b><br>
                    adalah memiliki karakteristik khusus yang berbeda dengan orang pada umumnya, misalnya kurang pendengaran dan sebagainya 
                    <select class="w3-input w3-border w3-pale-green w3-white" id="kebutuhan_khusus" name="kebutuhan_khusus" required="">
                        <option>Tidak</option>
                        <option>Ya</option>
                    </select>
                </p>
                <p>
                    <b>Status Kawin :</b><br>

                    <select class="w3-input w3-border w3-pale-green w3-white" id="status_kawin" name="status_kawin" required="">
                        <option value="-">Pilih Status Kawin</option>
                        <option>Kawin</option>
                        <option>Belum Kawin</option>
                        <option>Janda</option>
                        <option>Duda</option>
                    </select>
                </p>

                <p>
                    <b>Scan KTP : (format gambar atau pdf)</b><br>
                    <input type="file" id="scan_ktp" name="scan_ktp" accept="image/jpeg,image/png,application/pdf" required="">
                </p>
                <hr>
                <p>Sebelum mengklik kirim pastikan data yang diisikan sudah sesuai</p>
                <p>
                    <button name="_submit" id="_submit" class="w3-btn w3-green">Kirim</button>
                </p>
            </div>
        </div>
    </div>

</div>
<div id="hasil" style="display: none"></div>
<!-- * App Capsule -->


<script>
    var _submit = document.getElementById('_submit');
    var nama=document.getElementById("nama");
    var tempat_lahir=document.getElementById("tempat_lahir");
    var tempat_lahir=document.getElementById("tempat_lahir");
    var tanggal_lahir=document.getElementById("tanggal_lahir");
    var nik=document.getElementById("nik");
    var bank=document.getElementById("bank");
    var bank_akun=document.getElementById("bank_akun");
    var nomor_rekening=document.getElementById("nomor_rekening");
    var nomor_telepon=document.getElementById("nomor_telepon");
    var email=document.getElementById("email");
    var alamat=document.getElementById("alamat");
    var agama=document.getElementById("agama");
    var pekerjaan=document.getElementById("pekerjaan");
    var pendidikan=document.getElementById("pendidikan");
    var status_kawin=document.getElementById("status_kawin");
    var scan_ktp=document.getElementById("scan_ktp"); 
    var id_akun=document.getElementById("id_akun"); 

    var upload = function(){ 
        if(nama.value==null || nama.value==""){
            nama.classList.remove("w3-pale-green");
            nama.classList.add("w3-pale-red");
            nama.focus();
            return false;
        }else
        if(tempat_lahir.value==null || tempat_lahir.value==""){
            tempat_lahir.classList.remove("w3-pale-green");
            tempat_lahir.classList.add("w3-pale-red");
            tempat_lahir.focus();
            return false;
        }else
        if(tanggal_lahir.value==null || tanggal_lahir.value==""){
            tanggal_lahir.classList.remove("w3-pale-green");
            tanggal_lahir.classList.add("w3-pale-red");
            tanggal_lahir.focus();
            return false;
        }else
        if(nik.value==null || nik.value==""){
            nik.classList.remove("w3-pale-green");
            nik.classList.add("w3-pale-red");
            nik.focus();
            return false;
        }else
        if(bank.value=="-"){
            bank.classList.remove("w3-pale-green");
            bank.classList.add("w3-pale-red");
            bank.focus();
            return false;
        }else
        if(nomor_rekening.value==null || nomor_rekening.value==""){
            nomor_rekening.classList.remove("w3-pale-green");
            nomor_rekening.classList.add("w3-pale-red");
            nomor_rekening.focus();
            return false;
        }else
        if(bank_akun.value==null || bank_akun.value==""){
            bank_akun.classList.remove("w3-pale-green");
            bank_akun.classList.add("w3-pale-red");
            bank_akun.focus();
            return false;
        }else
        if(nomor_telepon.value==null || nomor_telepon.value==""){
            nomor_telepon.classList.remove("w3-pale-green");
            nomor_telepon.classList.add("w3-pale-red");
            nomor_telepon.focus();
            return false;
        }else
        if(alamat.value==null || alamat.value==""){
            alamat.classList.remove("w3-pale-green");
            alamat.classList.add("w3-pale-red");
            alamat.focus();
            return false;
        }else
        if(email.value==null || email.value==""){
            email.classList.remove("w3-pale-green");
            email.classList.add("w3-pale-red");
            email.focus();
            return false;
        }else
        if(agama.value=="-"){
            agama.classList.remove("w3-pale-green");
            agama.classList.add("w3-pale-red");
            agama.focus();
            return false;
        }else
        if(pendidikan.value=="" || pendidikan.value==null){
            pekerjaan.classList.remove("w3-pale-green");
            pekerjaan.classList.add("w3-pale-red");
            pekerjaan.focus();
            return false;
        }else
        if(pekerjaan.value=="" || pekerjaan.value==null){
            pekerjaan.classList.remove("w3-pale-green");
            pekerjaan.classList.add("w3-pale-red");
            pekerjaan.focus();
            return false;
        }else
        if(status_kawin.value=="-"){
            status_kawin.classList.remove("w3-pale-green");
            status_kawin.classList.add("w3-pale-red");
            status_kawin.focus();
            return false;
        }else
        if(scan_ktp.files && scan_ktp.files.length == 0){
            notifier.show('<font color=red>Perhatian!</font>' , '<font color=red>Silahkan Pilih scan KTP</font>', '', 'assets/img/high_priority-48.png', 8000);
            return false;
        }else
        if(scan_ktp.files && scan_ktp.files.length == 1){
            var file = scan_ktp.files[0]
            var nama_file=file.name;
//var isi_file=file.content;
var mime_types = [ 'image/jpeg', 'image/png', 'application/pdf' ];

// validate MIME type
if(mime_types.indexOf(file.type) == -1){
    notifier.show('<font color=red>Perhatian!</font>' , '<font color=red>Silahkan Pilih File Scan KTP format gambar atau pdf</font>', '', 'assets/img/high_priority-48.png', 8000); 
    return false;
}else
if(file.size > 4*1024*1024){
    notifier.show('<font color=red>Perhatian!</font>' , '<font color=red>File Scan KTP Maksimal 4 Mb</font>', '', 'assets/img/high_priority-48.png', 8000); 
    return false;
}   
}  
var data = new FormData();
data.append('scan_ktp', scan_ktp.files[0]);
data.append('id_akun', id_akun.value);
data.append('nama', nama.value);
data.append('tempat_lahir', tempat_lahir.value);
data.append('tanggal_lahir', tanggal_lahir.value);
data.append('nik', nik.value);
data.append('bank', bank.value);
data.append('nomor_rekening', nomor_rekening.value);
data.append('bank_akun', bank_akun.value);
data.append('nomor_telepon', nomor_telepon.value);
data.append('email', email.value);
data.append('alamat', alamat.value);
data.append('agama', agama.value);
data.append('pekerjaan', pekerjaan.value);
data.append('pendidikan', pendidikan.value);
data.append('kebutuhan_khusus', kebutuhan_khusus.value);
data.append('status_kawin', status_kawin.value);


_submit.style.display="none";
document.getElementById("loader").style.display="block";
var request = new XMLHttpRequest();
request.onreadystatechange = function(){
    if(request.readyState == 4){ 
        var resp = JSON.parse(request.response);
        if(resp.status=='Error'){
            notifier.show('<font color=red>Perhatian!</font>' , '<font color=red>'+resp.data+'</font>', '', 'assets/img/high_priority-48.png', 8000);
            _submit.style.display="block";
            document.getElementById("loader").style.display="none";
        }else{
            document.getElementById("inputan").style.display="none";
            document.getElementById("hasil").innerHTML=resp.data;
            document.getElementById("hasil").style.display="block";
            document.getElementById("loader").style.display="none";
        }
    }
}
request.open('POST', 'pendaftran_online_akun_perorangan_proses');
request.send(data);

}

_submit.addEventListener('click', upload);
</script> 

<link rel="stylesheet" href="assets/plugin/notifier/css/notifier.min.css">
<script type="text/javascript" src="assets/plugin/notifier/js/notifier.min.js"></script>
<?php include('_sys_footer.php');?>