<?php
include "_sys_admin_session.php";
$nama_halaman="komdanas";
include('_sys_header_admin.php');
include('_sys_koneksi.php');
?>

<link href="assets/plugin/slim_select/slimselect.min.css" rel="stylesheet" /> 
 
 
<div class="w3-container" id="isi">
 

 <span class="w3-center"> <b>DAFTAR RADIUS PANJAR BIAYA PERKARA</b></span> 
 <br>
 <!-- <a target="_blank" class="w3-red w3-btn" href="panjar_admin_komdanas" title="Update Data dari Komdanas">Update Data dari Komdanas</a> -->
 <div class="w3-row">
   <div class="w3-col w3-third w3-padding">
     <select id="propinsi_filter"  onchange="pilih_propinsi(this.value)"></select>
   </div>
   <div class="w3-col w3-third w3-padding">
     <select id="satker_filter"> </select>
   </div>
   <div class="w3-col w3-third w3-padding">
      <button onclick="tampilkan_data_radius()">Tampilkan</button>
   </div>
 </div>


 </p>
   <table id="tabledata" class="w3-table-all">
   <thead> 
   <tr>
    <th>#</th>
    <th>Nama Satker</th>
    <th>Propinsi</th> 
    <th>Kab/Kota</th> 
    <th>Kecamatan</th> 
    <th>Kel/Desa</th> 
    <th>Radius</th> 
    <th>Nilai</th> 
   </tr>
  </thead>
  <tbody id="isi_radius">  
  
 </tbody>
 </table>
<br>
<br>


 <!-- Modal -->
  <div id="modal_update_radius" class="w3-modal">
    <div class="w3-modal-content w3-card-8">
      <header class="w3-container w3-teal"> 
        <span onclick="document.getElementById('modal_update_radius').style.display='none'" 
        class="w3-closebtn">&times;</span>
        <h5>UPDATE NILAI RADIUS</h5>
      </header>
      <div class="w3-container" id="modal_update_radius_isi">
        <p>
            Nilai Radius :<br><input type="hidden" id="id">
            <input type="number" id="nilai" class="w3-input w3-border">
        </p>
      </div>
      <footer class="w3-container ">
        <p class="w3-center">
          <button id="btn_nilairadius_edit" class="w3-btn w3-green" onclick="edit_nilai_radius_simpan()">Edit</button>
          <button class="w3-btn w3-red" onclick="document.getElementById('modal_update_radius').style.display='none'">Batal</button>
        </p>
      </footer>
    </div>
  </div>
  <!-- Modal -->
</div>
<link href="assets/plugin/vanilla-dataTables/vanilla-dataTables.min.css" rel="stylesheet" type="text/css">
<script src="assets/plugin/vanilla-dataTables/vanilla-dataTables.min.js" type="text/javascript"></script>
<script src="assets/js/kelurahankomdanas.js" type="text/javascript"></script>
<script src="assets/plugin/slim_select/slimselect.min.js"></script>
<?php
include("_sys_footer_admin.php");
?>