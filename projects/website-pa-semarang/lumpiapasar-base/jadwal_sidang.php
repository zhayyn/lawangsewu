<?php
    include('_sys_header.php');
    $menu_aktif="jadwal";
?>
<div class="loading" id="loader">Loading&#8230;</div>
<!-- Header -->
<div class="w3-top w3-row w3-card-2 w3-padding w3-center w3-white">
  <div class="w3-col" style="width:30px">
    <a href="#" onclick="goBack()" class="headerButton goBack">
      <img src="assets/images/arrow_back-black-24dp.svg" class="w3-left w3-circle w3-margin-right" style="width:30px">
    </a>
  </div>
  <div class="w3-rest w3-cell-middle"><span style="font-size: 18px">JADWAL SIDANG</span></div>
</div> 
<!-- Header -->
<br>
<br>
<br>
<!-- Isi -->
<div class="w3-row">
    <div  class="w3-container" id="jadwal_sidang_input">
        <center>Untuk mendapatkan informasi jadwal sidang pada <?php echo ucwords(strtolower($namapa))?>, silahkan pilih <b>tanggal</b> dan Klik <b>Proses</b></center>
        <div class="w3-row"> 
            <table style="width: 200px" align="center" cellpadding="3">
            <tr>
            <td><input id="tanggal" type="date" class="w3-input w3-border" style="padding:6px 2px;" value="<?php echo date("Y-m-d") ?>"></td>
            <td><button class="w3-btn w3-btn-block w3-vivid-purplish-blue" id="jadwal_sidang_tombol_cek">Proses</button></td> 
            </tr>
            </table>
        </div>
    </div> 
</div>
<div id="jadwal_sidang_hasil"  class="w3-container"></div>
          

    <!-- * App Capsule -->

    <script type="text/javascript">
            jadwal_sidang_tombol_cek=document.getElementById("jadwal_sidang_tombol_cek");
            jadwal_sidang_tombol_cek.addEventListener('click', function(ev){
                jadwal_sidang_cek();
                ev.preventDefault();
            }, false);
            function jadwal_sidang_cek(){ 
                var tanggal=document.getElementById("tanggal").value; 
                //var os=document.getElementById("os").value;
                //var ip=document.getElementById("ip").value;
                document.getElementById("jadwal_sidang_hasil").innerHTML=""; 
                document.getElementById("loader").style.display=""; 
                var b=new XMLHttpRequest();
                b.open("POST","api",true);
                b.setRequestHeader("Content-type","application/x-www-form-urlencoded");
                b.onreadystatechange=function()
                {
                    if(b.readyState==XMLHttpRequest.DONE&&b.status==200)
                    {
                        var c=b.responseText;
                        document.getElementById("jadwal_sidang_hasil").innerHTML=c;
                        document.getElementById("loader").style.display="none";    
                    }
                };
                b.send("aksi="+btoa("informasi_jadwal_sidang")+"&tanggal="+encodeURIComponent(btoa(tanggal)));

            }
    </script>
<?php include('_sys_footer.php');?>