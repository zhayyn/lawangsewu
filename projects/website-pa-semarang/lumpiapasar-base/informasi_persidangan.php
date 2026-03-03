<?php
  include('_sys_header.php');
  $menu_aktif="antri";
 
?>  
<div class="loading" id="loader">Loading&#8230;</div>
<!-- Header -->
<div class="w3-top w3-row w3-card-2 w3-padding w3-center w3-white">
  <div class="w3-col" style="width:30px">
    <a href="#" onclick="goBack()" class="headerButton goBack">
      <img src="assets/images/arrow_back-black-24dp.svg" class="w3-left w3-circle w3-margin-right" style="width:30px">
    </a>
  </div>
  <div class="w3-rest w3-cell-middle"><span style="font-size: 18px">INFORMASI PERSIDANGAN<br>NOMOR ANTRIAN SIDANG</span></div>
</div> 
<!-- Header -->
<br>
<br>
<br>
<br>
<br>
<!-- Isi --> 
<div class="w3-row">
    <div class="w3-container" id="isi_informasi_persidangan"> 
</div>
</div>
<script type="text/javascript">
document.addEventListener("DOMContentLoaded", function() {
  info_persidangan();
});
function info_persidangan(){
	document.getElementById("loader").style.display="block";
var b=new XMLHttpRequest();
b.open("POST","api.php",true);
b.setRequestHeader("Content-type","application/x-www-form-urlencoded");
b.onreadystatechange=function()
{
  if(b.readyState==XMLHttpRequest.DONE&&b.status==200)
  {
    var c=JSON.parse(b.responseText); 
    txt='';
    for (x in c) {
      txt += '<b>'+c[x].nama_ruang +'</b><br>'+ c[x].kete+'<br><br>';
    }
     txt += '';
    document.getElementById("isi_informasi_persidangan").innerHTML=txt; 
    document.getElementById("loader").style.display="none"; 
  }
};
b.send("&aksi="+btoa("info_persidangan"));
	}
    </script> 
 
<?php include('_sys_footer.php');?>