<div class="w3-card-4">
    <div class="w3-container w3-teal">
      <h5>HASIL</h5>
    </div>
    <div class="w3-container">
<?php
include('../_sys_koneksi.php');
foreach($_POST as $key=>$value) {$$key=$value;}
$db = new Hapus_sekunder(); 
$hapus = $db->hapus_data_kolom($nama_pihak, 'nama_pihak',$id_panjar, 'id_panjar', 'panjar_data'); 
include('_tampilkan_hasil.php');
?>
  </div>
</div>