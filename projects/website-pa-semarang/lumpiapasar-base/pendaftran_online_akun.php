<?php
include('_sys_header.php');
$menu_aktif="akta_cerai";
?>
<div class="loading" id="loader">Loading&#8230;</div>
<!-- Header -->
<div class="w3-top w3-row w3-card-2 w3-padding w3-center w3-white">
    <div class="w3-col" style="width:30px">
        <a href="#" onclick="goBack()" class="headerButton goBack">
            <img src="assets/images/arrow_back-black-24dp.svg" class="w3-left w3-circle w3-margin-right" style="width:30px">
        </a>
    </div>
    <div class="w3-rest w3-cell-middle"><span style="font-size: 18px">PENDAFTARAN ONLINE</span></div>
</div> 
<!-- Header -->
<br>
<br>
<br>
<!-- Isi -->
<div class="w3-row">
    <div class="w3-container">
     <div class="w3-container w3-section w3-pale-yellow w3-card-8">
            <p><b>Pendaftaran Online</b> di Pengadilan Agama Semarang menggunakan e-Court.
            Namun bagi <b>Non Advokad</b>, untuk bisa <b>masuk/login</b> di e-COurt Mahkamah Agung harus terlebih dahulu melakukan <b>registrasi Akun</b> terlebih dahulu.</p> 
            <p>Registrasi Akun <b>berlaku</b> untuk <b>Perorangan</b>, <b>Pemerintah</b>, <b>Badan Hukum</b> dan <b>Kuasa Insidentil</b></p>
            <p>Syarat Pendaftaran Akun adalah email belum teregistrasi/ didaftarkan di ecourt Mahkamah Agung</p>
            <p>Apabila anda sudah <b>paham</b> silahkan pilih <b>jenis akun</b> yang akan didaftarkan :</p>
      </div>
    </div>
    <div class="w3-container"> 
        <ul class="w3-ul w3-card-4">
            <li class="w3-padding-16">
                <a href="pendaftran_online_akun_perorangan">
                    <div class="w3-row">
                        <div class="w3-col" style="width:55px">
                            <img src="assets/images/person-black-24dp.svg" class="w3-left w3-circle w3-margin-right" style="width:50px">
                        </div>
                        <div class="w3-rest">
                            <h3>Perorangan</h3>
                        </div>
                    </div>                    
                </a>
            </li>
            <li class="w3-padding-16">
                <a href="#pendaftran_online_akun_pemerintah">
                    <div class="w3-row">
                        <div class="w3-col" style="width:55px">
                            <img src="assets/images/account_balance-black-24dp.svg" class="w3-left w3-circle w3-margin-right" style="width:50px">
                        </div>
                        <div class="w3-rest">
                            <h3>Pemerintah</h3>
                        </div>
                    </div>                    
                </a>
            </li>
            <li class="w3-padding-16">
                <a href="#pendaftran_online_akun_badan_hukum">
                    <div class="w3-row">
                        <div class="w3-col" style="width:55px">
                            <img src="assets/images/store-black-24dp.svg" class="w3-left w3-circle w3-margin-right" style="width:50px">
                        </div>
                        <div class="w3-rest">
                            <h3>Badan Hukum</h3>
                        </div>
                    </div>                    
                </a>
            </li>
            <li class="w3-padding-16">
                <a href="#pendaftran_online_akun_kuasa_insidentil">
                    <div class="w3-row">
                        <div class="w3-col" style="width:55px">
                            <img src="assets/images/people_outline-black-24dp.svg" class="w3-left w3-circle w3-margin-right" style="width:50px">
                        </div>
                        <div class="w3-rest">
                            <h3>Kuasa Insidentil</h3>
                        </div>
                    </div>                    
                </a>
            </li>
        </ul>
    </div>

    <div class="w3-container">
     <div class="w3-container w3-section w3-pale-yellow w3-card-8">
            <p>Apabila anda sudah teregistrasi/ terdaftar yaitu telah mendapatkan email yang berisi informasi akun, silahkan buka <a href="https://ecourt.mahkamahagung.go.id/login" class="w3-green w3-btn">e-Court Mahkamah Agung</a> pada halaman tersebut silahkan isikan user dan password yang diterima melalui Email</p>
      </div>
    </div>
</div>
 


    <!-- * App Capsule -->

 
<?php include('_sys_footer.php');?>