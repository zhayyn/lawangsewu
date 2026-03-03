<?php
//update _fungsi 
//$nm_bulan=array('01'=>'Januari','02'=>'Pebruari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September',
//                    '10'=>'Oktober','11'=>'Nopember','12'=>'Desember','1'=>'Januari','2'=>'Pebruari','3'=>'Maret','4'=>'April','5'=>'Mei','6'=>'Juni',
 //                   '7'=>'Juli','8'=>'Agustus','9'=>'September',);

//update _fungsi 
include "_sys_admin_session.php";
$nama_halaman="Slide";
include('_sys_header_admin.php');
include('_sys_koneksi.php');
?>
 
 
 <link rel="stylesheet" href="assets/plugin/font-awesome-4.7.0/css/font-awesome.min.css">
 <div class="w3-row">
   <link href="assets/plugin/dropzone/dropzone.min.css" type="text/css" rel="stylesheet" />
      <script src="assets/plugin//dropzone/dropzone.js"></script>
       <form id="formx_upload" action="_admin_slide" class="dropzone"><input  name="aksi"  type="hidden" value="upload"></form>
 </div>
<div class="w3-container" id="isi">
  
</div> 
<script type="text/javascript">
Dropzone.autoDiscover = false;
var errors = false;
new Dropzone("#formx_upload", { 
    init: function() {
        this.on("success", function(file, responseText) {
          lihat();
        });
    }
});
  function lihat(){    
    var xhr = new XMLHttpRequest();
    xhr.open("POST", '_admin_slide', true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

    xhr.onreadystatechange = function() {
      if(xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200){
        document.getElementById("isi").innerHTML=xhr.responseText ;

      }
    }
    xhr.send("aksi=lihat"); 
  }
  function hapus(file,id){
    var r = confirm("Apakah Anda akan menghapus data? \nData yang dihapus tidak dapat dikembalikan" );
    if (r == true) {
      var b=new XMLHttpRequest();
      b.open("POST","_admin_slide",true);
      b.setRequestHeader("Content-type","application/x-www-form-urlencoded");
      b.onreadystatechange=function(){
        if(b.readyState==XMLHttpRequest.DONE&&b.status==200){
          lihat();
        }
      }
      b.send("aksi=hapus&file="+file+"&id="+id);
    }
  }

function edit_field(tabel, field, kunci, id, isi){
  var aksi="edit_field";
  var xhr = new XMLHttpRequest();
  var url='_admin_data_edit_field';
  xhr.open("POST", url, true);
  xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  xhr.onreadystatechange = function(){
    if(xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200){
      var hasi=xhr.responseText;     
    }
  }
  xhr.send("tabel="+encodeURIComponent(tabel)+"&field="+encodeURIComponent(field)+"&kunci="+encodeURIComponent(kunci)+"&id="+encodeURIComponent(id)+"&isi="+encodeURIComponent(isi)+"&aksi="+encodeURIComponent(aksi));
}
</script>
<script type="text/javascript">
document.addEventListener("DOMContentLoaded", function() {
  lihat();
});
</script>
  <?php
include("_sys_footer_admin.php");
?>