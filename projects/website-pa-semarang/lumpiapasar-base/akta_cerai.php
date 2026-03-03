<?php
include('_sys_header.php');
$menu_aktif="akta_cerai";
?>
<div class="loading" id="loader">Loading&#8230;</div>
<!-- Header -->
<div class="w3-top w3-row w3-card-2 w3-padding w3-center w3-white">
    <div class="w3-col" style="width:30px">
        <a href="https://lumpiapasar.pa-semarang.go.id" class="headerButton goBack">
            <img src="assets/images/arrow_back-black-24dp.svg" class="w3-left w3-circle w3-margin-right" style="width:30px">
        </a>
    </div>
    <div class="w3-rest w3-cell-middle"><span style="font-size: 18px">AKTA CERAI</span></div>
</div> 
<!-- Header -->
<br>
<br>
<br>
<!-- Isi -->
<div class="w3-row">
    <div class="w3-container" id="informasi_perkara_inputan"> 
        <ul class="w3-ul w3-card-2 w3-round-large">
            <li class="w3-padding-16">
                <a href="akta_cerai_info.php">
                    <img src="assets/images/card_004_ac.svg" class="w3-left w3-circle w3-margin-right" style="width:50px">
                    <span class="w3-large">INFORMASI AKTA CERAI</span><br>
                    <span>Mengecek penerbitan Akta Cerai</span>
                </a>
            </li>
            <li class="w3-padding-16">
                <a href="akta_cerai_validasi.php">
                    <img src="assets/images/card_004_val_ac.svg" class="w3-left w3-circle w3-margin-right" style="width:50px">
                    <span class="w3-large">VALIDASI AKTA CERAI</span><br>
                    <span>Mengecek validitas akta cerai berdasarkan Nomor Perkara dan Nomor Akta Cerai</span>
                </a>
            </li>
            <li class="w3-padding-16">
                <a href="antrian_ac.php">
                    <img src="assets/images/card_004_ac.svg" class="w3-left w3-circle w3-margin-right" style="width:50px">
                    <span class="w3-large">AMBIL AKTA CERAI</span><br>
                    <span>Pengambilan Akta Cerai</span>
                </a>
            </li>            
        </ul>
    </div>
</div>
     
<?php include('_sys_footer.php');?>