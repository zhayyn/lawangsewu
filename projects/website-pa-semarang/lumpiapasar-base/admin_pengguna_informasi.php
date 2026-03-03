<?php
include "_sys_admin_session.php";
$nama_halaman="pengguna_informasi";
include('_sys_header_admin.php');
include('_sys_koneksi.php');
?>

<link href="assets/plugin/slim_select/slimselect.min.css" rel="stylesheet" /> 
 
 
<div class="w3-container">
<b>DAFTAR PENGGUNA INFORMASI PERKARA, JADWAL SIDANG, DAN STATISTIK PERKARA</b>
<div class="w3-row">
  <table cellpadding="3" cellspacing="3" border="0">
    <tr>
      <td> Periode
      </td>
      <td>
        <select style="padding: 4px"  id="bulan">
          <?php
            $nm_bulan=array('1'=>'Januari','2'=>'Pebruari','3'=>'Maret','4'=>'April','5'=>'Mei','6'=>'Juni','7'=>'Juli','8'=>'Agustus','9'=>'September',
              '10'=>'Oktober','11'=>'Nopember','12'=>'Desember');
            for($i=1;$i<=12;$i++){
              echo "<option value=".$i.">".$nm_bulan[$i]."</option>";
            }
          ?>
            <option value="all">Semua Bulan</option>
        </select>
      </td>
      <td>
        <select style="padding: 4px" id="tahun">
        <?php
          $sql="SELECT year(diinput_tanggal) as tahun FROM panjar_info_penguna GROUP BY year(diinput_tanggal) "; 
          $db = new Tampil_sekunder(); 
          $arrayData = $db->tampil_data_sekunder($sql);  
          if (count($arrayData)) 
          { 
            foreach ($arrayData as $data) 
            {  
              foreach($data as $key=>$value) {$$key=$value;}
              echo '<option>'.$tahun.'</option>';
            }
          }
        ?>
        </select>
      </td>
      <td><button style="padding: 4px" onclick="tampilkan_data_pengguna()">Tampilkan</button>
      </td>
    </tr>
  </table>
</div>
<hr>
<div class="w3-row" id="isi_pengguna_informasi"> 
</div> 
<br>
<br>


 <!-- Modal -->
  <div id="modal_detail" class="w3-modal"  style="padding-top: 0px">
    <div class="w3-modal-content w3-card-8">
      <header class="w3-container w3-teal"> 
        <span onclick="document.getElementById('modal_detail').style.display='none'" 
        class="w3-closebtn">&times;</span>
        <h5>DETAIL TAKSIRAN PANJAR</h5>
      </header>
      <div class="w3-container" id="modal_detail_isi">
         
      </div>
      <footer class="w3-container ">
        <p class="w3-center">
          <button class="w3-btn w3-red" onclick="document.getElementById('modal_detail').style.display='none'">Tutup</button>
        </p>
      </footer>
    </div>
  </div>
  <!-- Modal -->
</div>
<link href="assets/plugin/vanilla-dataTables/vanilla-dataTables.min.css" rel="stylesheet" type="text/css">
<script src="assets/plugin/vanilla-dataTables/vanilla-dataTables.min.js" type="text/javascript"></script>
<script src="assets/js/penggunainformasi.js" type="text/javascript"></script>
<script src="assets/plugin/slim_select/slimselect.min.js"></script>
<?php
include("_sys_footer_admin.php");
?>