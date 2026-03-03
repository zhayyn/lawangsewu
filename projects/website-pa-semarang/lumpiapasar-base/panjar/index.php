<?php
  include('_sys_header.php');
  $menu_aktif="panjar";
?>  
<div class="loading" id="loader">Loading&#8230;</div>
<!-- Header -->
<div class="w3-w3-row w3-card-2 w3-padding w3-center w3-white">
  <div class="w3-row">
     <div class="w3-col" style="width:50px"><a href="../index.php" class="headerButton goBack">
      <img src="../assets/images/arrow_back-black-24dp.svg" class="w3-left w3-circle w3-margin-right" style="width:30px">
    </a></div>
      <div class="w3-rest w3-cell-middle"><span style="font-size: 18px"> PANJAR BIAYA PERKARA</span></div>
  </div>
  
</div> 
<!-- Header --> 

<!-- Isi -->
<div class="w3-row">
    <div class="w3-container" id="perhitungan_panjar"> 
        <p class="w3-center">Untuk mendapatkan perkiraan Panjar Biaya Perkara, silahkan pilih jenis perkara yang akan dihitung panjar biaya perkara</p>
  <ul class="w3-ul w3-card-2">
    <li class="w3-bar"  style="cursor: pointer;">
      <a href="cerai.php?jenis=1" style="text-decoration: none">
      <div class="w3-col w3-center" style="width:100px">
        <img src="assets/images/cg.png" class="w3-bar-item " style="width:90px">
      </div>
      <div class="w3-rest">
        <span class="w3-large">CERAI GUGAT</span><br>
        <span>Perceraian yang diajukan oleh pihak isteri</span>
      </div>
      </a>
  </li>
  <li class="w3-bar" style="cursor: pointer;">
      <a href="cerai.php?jenis=2" style="text-decoration: none">
    <div class="w3-col w3-center" style="width:100px">
      <img src="assets/images/ct.png" class="w3-bar-item " style="width:90px">
    </div>
    <div class="w3-rest">
      <span class="w3-large">CERAI TALAK</span><br>
      <span>Perceraian yang diajukan oleh pihak suami</span>
    </div>
  </a>
  </li>
  <?php
      $sql="SELECT * FROM panjar_jenis_pendaftaran WHERE aktif='Y' AND id>=3";
      $db = new Tampil_sekunder(); 
      $arrayData = $db->tampil_data_sekunder($sql);
      if (count($arrayData)) 
      { 
        foreach ($arrayData as $data) 
        {
          foreach($data as $key=>$value) {$$key=$value;}
          echo '
            <li class="w3-bar" style="cursor: pointer;">
              <a href="hitung_panjar.php?jenis='.$id.'" style="text-decoration: none">
            <div class="w3-col w3-center" style="width:100px">
              <img src="assets/images/'.$ikon.'" class="w3-bar-item " style="width:90px">
            </div>
            <div class="w3-rest">
              <span class="w3-large">'.$jenis_pendaftaran.'</span><br>
              <span>'.$keterangan.'</span>
            </div>
          </a>
          </li>
          ';
        }
      }
  ?>  
 
</ul>
<br><br><br><br>
<br><br>
    </div>
</div>


<script>
    document.getElementById("loader").style.display="none";
    function goBack(){
        window.history.back()
    }
</script>
</body> 
</html>