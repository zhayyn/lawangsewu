<?php
include "_sys_admin_session.php";
$nama_halaman="jenisperkara";
include('_sys_header_admin.php');
include('_sys_koneksi.php');
?>
 
 
<style type="text/css">
  td{font-size: 14px;}
</style> 
 
<div class="w3-container" id="isi">
  <div class="w3-row">
    <div class="w3-half"><b> JENIS PERKARA</b></div>
    <div class="w3-half"><button class="w3-btn w3-green w3-right" onclick="buka_modal_jenis_perkara()">Tambah</button></div>    
  </div>
  <table id="tabledata" class="w3-table-all">
    <thead>
   <tr>
    <th style="vertical-align:middle">No</th>
    <th style="vertical-align:middle">Jenis Perkara</th> 
    <th style="vertical-align:middle">Sebutan P</th>
    <th style="vertical-align:middle">Sebutan T</th>
    <th style='vertical-align:middle'>Keterangan</th> 
    <th style='vertical-align:middle'> </th> 
   </tr>
   </thead>
   <tbody>
   <?php
   $total=0;
   $sql="SELECT * FROM panjar_jenis_pendaftaran ";
       //echo $sql;
    $db = new Tampil_sekunder(); 
    $arrayData = $db->tampil_data_sekunder($sql); 
    $no=0;
    if (count($arrayData)) 
    { 
     foreach ($arrayData as $data) 
     { 
      foreach($data as $key=>$value) {$$key=$value;}
      $no++;
      echo "<tr>";
      echo "<td>".$no."</td>"; 
      echo "<td>".$jenis_pendaftaran."</td>"; 
      echo "<td>".$sebutan_p."</td>"; 
      echo "<td>".$sebutan_t."</td>"; 
      echo "<td>".$keterangan."</td>"; 
      echo "<td style='width:130px ;text-align:center'><button class='w3-btn w3-blue' onclick='edit_jenis_perkara(".$id.")'>Edit</button> <button class='w3-btn w3-red' onclick='hapus_jenis_perkara(".$id.")'>X</button></td>"; 
      echo "</tr>";
     }
   }
   ?>
   </tbody>
 </table>

 <!-- Modal -->
  <div id="modal_jenis_perkara" class="w3-modal" style="padding-top: 0px">
    <div class="w3-modal-content w3-card-8">
      <header class="w3-container w3-teal"> 
        <span onclick="document.getElementById('modal_jenis_perkara').style.display='none'" 
        class="w3-closebtn">&times;</span>
        <h5>Jenis Perkara</h5>
      </header>
      <div class="w3-container" id="modal_jenis_perkara_isi">
        <p>
            Jenis Perkara :<br><input type="hidden" id="id">
            <input type="text" id="jenis_pendaftaran" class="w3-input w3-border">
        </p>
        <p>
            Keterangan :<br>
            <textarea class="w3-input w3-border" id="keterangan"></textarea>
        </p>
        <p>
            Sebutan P :<br>
            <input type="text" id="sebutan_p" class="w3-input w3-border">
        </p>
        <p>
            Sebutan T :<br>
            <input type="text" id="sebutan_t" class="w3-input w3-border">
        </p>
        <p>
            Ikon :<br>
            <select id="ikon" class="w3-select w3-white w3-border" onchange="pilih_gambar(this.value)">
              <option value="">Pilih ikon</option>
              <?php 
              $folder = "assets/images/";  
              $handle = opendir($folder);  
              $fileGambar = array('png'); 
              $fileGambar = array(''); 
              while(false !== ($file = readdir($handle)))
              { 
                $fileAndExt = explode('.', $file);
                if($fileAndExt[1]=="png")
                {
                  echo '<option>'.$file.'</option>'; 
                }                
              }  
              ?>  
            </select>
            <div id="tampilan_ikon"></div>
        </p>
        <p>
            Aktif :<br>
            <select id="aktif" class="w3-select w3-white w3-border">
              <option value="Y">Aktif</option>
              <option value="T">Tidak</option>
            </select>
        </p>
      </div>
      <footer class="w3-container ">
        <p class="w3-center">
          <button id="btn_jenisperkara_simpan" onclick="tambah_jenis_perkara()" class="w3-btn w3-green">Simpan</button>
          <button id="btn_jenisperkara_edit" class="w3-btn w3-green" onclick="edit_jenis_perkara_simpan()">Edit</button>
          <button class="w3-btn w3-red" onclick="document.getElementById('modal_jenis_perkara').style.display='none'">Batal</button>
        </p>
      </footer>
    </div>
  </div>
  <!-- Modal -->
</div>
<link href="assets/plugin/vanilla-dataTables/vanilla-dataTables.min.css" rel="stylesheet" type="text/css">
<script src="assets/plugin/vanilla-dataTables/vanilla-dataTables.min.js" type="text/javascript"></script>
<script src="assets/js/jenis_perkara.js" type="text/javascript"></script>
<?php
include("_sys_footer_admin.php");
?>