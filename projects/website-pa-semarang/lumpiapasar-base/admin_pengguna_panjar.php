<?php
include "_sys_admin_session.php";
$nama_halaman="pengguna_panjar";
include('_sys_header_admin.php');
include('_sys_koneksi.php');
?>

<link href="assets/plugin/slim_select/slimselect.min.css" rel="stylesheet" /> 
 
 
<div class="w3-container">
<p> <b>DAFTAR PENGGUNA PERHITUNGAN PANJAR BIAYA PERKARA</b></span> </p> 
<p>Untuk menampilkan detail taksiran, klik pada Nominal Taksiran</span> </p> 
   <table class="w3-table-all">
   <thead> 
   <tr>
    <th>#</th>
    <th>Jenis Perkara</th>
    <th>Para Pihak</th> 
    <th>Takiran</th> 
    <th>Waktu</th> 
   </tr>
  </thead>
  <tbody id="isi_panjar">  
  
 </tbody>
 </table>

<div class="w3-row"  id="pagination">
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
<script src="assets/js/penggunapanjar.js" type="text/javascript"></script>
<script src="assets/plugin/slim_select/slimselect.min.js"></script>
<?php
include("_sys_footer_admin.php");
?>