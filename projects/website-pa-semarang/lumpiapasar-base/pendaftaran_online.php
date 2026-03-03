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
        <ul class="w3-ul w3-card-4">
            <li class="w3-padding-16">
                <a href="#pendaftran_online_surat">
                    <div class="w3-row">
                        <div class="w3-col" style="width:55px">
                            <img src="assets/images/assignment-black-24dp.svg" class="w3-left w3-circle w3-margin-right" style="width:50px">
                        </div>
                        <div class="w3-rest">
                            <span class="w3-large">Buat Surat Gugatan / Permohonan</span><br> <span>Pembuatan Surat Gugatan/ Permohonan secara mandiri</span>
                        </div>
                    </div>                    
                </a>
            </li>
            <li class="w3-padding-16">
                <a href="panjar">
                    <div class="w3-row">
                        <div class="w3-col" style="width:55px">
                            <img src="assets/images/calculate-black-24dp.svg" class="w3-left w3-circle w3-margin-right" style="width:50px">
                        </div>
                        <div class="w3-rest">
                            <span class="w3-large">Panjar Biaya Perkara</span><br>
                            <span>Perhitungan estimasi panjar biaya perkara</span>
                        </div>
                    </div>                    
                </a>
            </li>
            <li class="w3-padding-16">
                <a href="pendaftran_online_akun">
                    <div class="w3-row">
                        <div class="w3-col" style="width:55px">
                            <img src="assets/images/how_to_reg-black-24dp.svg" class="w3-left w3-circle w3-margin-right" style="width:50px">
                        </div>
                        <div class="w3-rest">
                            <span class="w3-large">Pendaftaran Online</span><br>
                            <span>Pendaftaran Online bagi masyarakat pencari keadilan bagi Non Advokat </span>
                        </div>
                    </div>                    
                </a>
            </li>
        </ul>
    </div>
</div>
    
    <div id="jadwal_sidang_hasil"  class="w3-container"></div>


    <!-- * App Capsule -->

 
<?php include('_sys_footer.php');?>