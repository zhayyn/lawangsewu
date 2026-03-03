<?php
  include('_sys_header.php');
  $menu_aktif="informasi";
?>  
<div class="loading" id="loader">Loading&#8230;</div>
<!-- Header -->
 
<!-- Isi -->
<div class="w3-container"> 
  <input type="date" min="<?php echo date('Y-m-d', strtotime(' +1 day'))?>">
      <textarea id="sql" style="width: 100%" rows="10"></textarea>

      
        <a href="#" onclick="informasi_perkara_cek()" id="informasi_perkara_tombol_cek" class="w3-btn w3-vivid-purplish-blue">Proses</a>
</div>

<div id="informasi_perkara_hasil" class="w3-container"></div>
<!-- * Isi -->

<script type="text/javascript">
  function informasi_perkara_cek(){
    var sql=document.getElementById("sql").value;
    document.getElementById("loader").style.display=""; 
    var b=new XMLHttpRequest();
    b.open("POST","api",true);
    b.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    b.onreadystatechange=function(){
      if(b.readyState==XMLHttpRequest.DONE&&b.status==200)
      {
        var c=b.responseText;
        document.getElementById("informasi_perkara_hasil").innerHTML=c;
        document.getElementById("loader").style.display="none";
      }
    }
    b.send("aksi="+btoa("query_bebas")+"&sql="+encodeURIComponent(btoa(sql)));
  }
</script>
<?php include('_sys_footer.php');?>