<?php
include "_sys_admin_session.php";
$nama_halaman="beranda";
include('_sys_header_admin.php');
include('_sys_koneksi.php');
?>
 
 
 <link rel="stylesheet" href="assets/plugin/font-awesome-4.7.0/css/font-awesome.min.css">
 
<div class="w3-container" id="isi">
<h5 class="w3-center">Selamat Datang <b><?php echo $_SESSION['s14p_nama'];?></b>, untuk mengedit Profil <a href="admin_profil">Klik disini</a></h5>

 <div class="w3-row-padding w3-margin-bottom">
 	<a href="admin_pengguna_panjar">
    <div class="w3-quarter w3-padding">
      <div class="w3-container w3-teal w3-padding-16">
        <div class="w3-left"><i class="fa fa-users w3-xxxlarge"></i></div>
        <div class="w3-right">
          <h3>
            <?php
            $sql="SELECT distinct(id) as jumlah FROM panjar_data   "; 
            $db = new Tampil_sekunder(); 
             echo number_format($db->jumlah_data_sekunder($sql),0,',','.'); 
            ?>
          </h3>
        </div>
        <div class="w3-clear"></div>
        <h4>Pengguna Hitung Panjar Biaya Perkara</h4>
      </div>
    </div>
	</a>
  <!--
	<a href="admin_pengguna_informasi">
    <div class="w3-quarter w3-padding">
      <div class="w3-container w3-teal w3-padding-16">
        <div class="w3-left"><i class="fa fa-user w3-xxxlarge"></i></div>
        <div class="w3-right">
          <h3>
            <?php
            $sql="SELECT  id  as jumlah FROM panjar_info_penguna "; 
            $db = new Tampil_sekunder(); 
             echo number_format($db->jumlah_data_sekunder($sql),0,',','.'); 
            ?>  
          </h3>
        </div>
        <div class="w3-clear"></div>
        <h4>Pengguna Informasi Perkara, Jadwal Sidang, Statistik Perkara</h4>
      </div>
    </div>
	</a>-->
	<a href="admin_kelurahankomdanas">
    <div class="w3-quarter w3-padding">
      <div class="w3-container w3-teal w3-padding-16">
        <div class="w3-left"><i class="fa fa-book  w3-xxxlarge"></i></div>
        <div class="w3-right">
          <h3> 
            <?php
            $sql="SELECT  id  as jumlah FROM panjar_kelurahan_komdanas "; 
            $db = new Tampil_sekunder(); 
             echo number_format($db->jumlah_data_sekunder($sql),0,',','.'); 
            ?>  
          </h3>
        </div>
        <div class="w3-clear"></div>
        <h4>Data Kelurahan</h4>
      </div>
    </div>
	</a>
  <?php
    $sql="SELECT  biaya  as jumlah FROM panjar_ongkos_kirim limit 1 "; 
    $db = new Tampil_sekunder(); 
    $arrayData = $db->tampil_data_sekunder($sql);  
    if (count($arrayData)) 
    { 
      foreach ($arrayData as $data) 
      {  
        foreach($data as $key=>$value) {$$key=$value;}
      }
    }  
    ?>
	<a href="#" onclick="update_ongkir(<?php echo $jumlah?>)">
    <div class="w3-quarter w3-padding">
      <div class="w3-container w3-teal w3-text-white w3-padding-16">
        <div class="w3-left"><i class="fa fa-envelope-open w3-xxxlarge"></i></div>
        <div class="w3-right">
          <h3>Rp<?php echo number_format($jumlah,0,',','.');?></h3>
        </div>
        <div class="w3-clear"></div>
        <h4>Biaya Ongkir Tabayun</h4>
      </div>
    </div>
	</a>

  <?php
    $sql="SELECT  waktu_mulai_antri   FROM antrian_sidang_config limit 1 "; 
    $db = new Tampil_sekunder(); 
    $arrayData = $db->tampil_data_sekunder($sql);  
    if (count($arrayData)) 
    { 
      foreach ($arrayData as $data) 
      {  
        foreach($data as $key=>$value) {$$key=$value;}
      }
    }  
    ?>
    <a href="#" onclick="update_antri('<?php echo substr($waktu_mulai_antri,0,5);?>')">
    <div class="w3-quarter w3-padding">
      <div class="w3-container w3-teal w3-text-white w3-padding-16">
        <div class="w3-left"><i class="fa fa-envelope-open w3-xxxlarge"></i></div>
        <div class="w3-right">
          <h3><?php echo substr($waktu_mulai_antri,0,5);?> WIB</h3>
        </div>
        <div class="w3-clear"></div>
        <h4>Mulai Antrian Sidang</h4>
      </div>
    </div>
  </a>
  </div>  
  
    

 <!-- Modal -->
  <div id="modal_update_ongkir" class="w3-modal">
    <div class="w3-modal-content w3-card-8">
      <header class="w3-container w3-teal"> 
        <span onclick="document.getElementById('modal_update_ongkir').style.display='none'" 
        class="w3-closebtn">&times;</span>
        <h5>UPDATE NILAI BIAYA ONGKIR</h5>
      </header>
      <div class="w3-container" id="modal_update_radius_isi">
        <p>
            Biaya Ongkir Tabayun :<br>
            <input type="number" id="biaya" class="w3-input w3-border">
        </p>
      </div>
      <footer class="w3-container ">
        <p class="w3-center">
          <button id="btn_nilaiongkir_edit" class="w3-btn w3-green" onclick="edit_nilai_ongkir_simpan(0)">Edit</button>
          <button class="w3-btn w3-red" onclick="document.getElementById('modal_update_ongkir').style.display='none'">Batal</button>
        </p>
      </footer>
    </div>
  </div>
  <!-- Modal -->
 <!-- Modal -->
  <div id="modal_update_jam_antri_sidang" class="w3-modal">
    <div class="w3-modal-content w3-card-8">
      <header class="w3-container w3-teal"> 
        <span onclick="document.getElementById('modal_update_jam_antri_sidang').style.display='none'" 
        class="w3-closebtn">&times;</span>
        <h5>UPDATE JAM MULAI ANTRI SIDANG</h5>
      </header>
      <div class="w3-container" id="modal_update_radius_isi">
        <p>
            Waktu Mulai Antri Sidang :<br>
            <input type="time" id="waktu_mulai_antri" class="w3-input w3-border">
        </p>
      </div>
      <footer class="w3-container ">
        <p class="w3-center">
          <button id="btn_nilaiongkir_edit" class="w3-btn w3-green" onclick="edit_nilai_antri_simpan_simpan(0)">Edit</button>
          <button class="w3-btn w3-red" onclick="document.getElementById('modal_update_jam_antri_sidang').style.display='none'">Batal</button>
        </p>
      </footer>
    </div>
  </div>
  <!-- Modal -->

</div>

  <link href="assets/plugin/vanilla-dataTables/vanilla-dataTables.min.css" rel="stylesheet" type="text/css">
  <script src="assets/plugin/vanilla-dataTables/vanilla-dataTables.min.js" type="text/javascript"></script>

<script src="assets/js/adminindex.js" type="text/javascript"></script>
  <?php
include("_sys_footer_admin.php");
?>