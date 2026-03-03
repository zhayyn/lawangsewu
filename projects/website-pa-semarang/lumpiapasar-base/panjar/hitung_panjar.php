<?php
include('_sys_header.php');
if(!isset($_GET["jenis"]))
{
  exit;
}
///////////cekinfo////////////////

$jenis_pendaftaran_id=(int)$_GET["jenis"];
$sql="SELECT id AS jenis_pendaftaran_id, jenis_pendaftaran, keterangan, sebutan_p, sebutan_t FROM panjar_jenis_pendaftaran WHERE aktif='Y' AND id=$jenis_pendaftaran_id";
$db = new Tampil_sekunder(); 
$arrayData = $db->tampil_data_sekunder($sql);
if (count($arrayData)) 
{ 
  foreach ($arrayData as $data) 
  {
    foreach($data as $key=>$value) {$$key=$value;}
  }
}
///////////cekinfo////////////////
?>

<div class="loading" id="loader">Loading&#8230;</div>
<link href="assets/plugin/slim_select/slimselect.min.css" rel="stylesheet" />
<div style="position: fixed;top: 0px;right:0px;">
    <a href="index.php" class="w3-btn w3-teal">
    <i class="material-icons">arrow_back</i>
    </a>
</div>

<script src="assets/js/hitung_panjar.js"></script>
<div class="w3-card-4">
    <div class="w3-container w3-teal">
      <h5><?php echo $jenis_pendaftaran?></h5>
    </div>
    <div class="w3-container">
      <p><?php echo $keterangan?></p>
      <p>Untuk menambahkan Pihak, silahkan klik tombol di bawah ini</p>
      <button type="button" class="w3-btn w3-teal" id="tambah_pihak" onclick="isi_tambah_pihak()">Tambah Pihak</button>
      <br>
      <br>

      <!-- DATA PARA PIHAK -->
      <div class="row" id="data_pihak"></div> 
      <!-- DATA PARA PIHAK -->
    </div>
</div>
<div class="w3-container" id="hasil_hitung"></div>

<!-- Modal  -->
  <div id="tambahpemohonModal" class="w3-modal" style="padding-top: 0px">
    <div class="w3-modal-content" style="max-width: 350px">
      <div class="w3-container">   
        <div class="w3-row">Silahkan isikan data di bawah ini </div>
        <input type="hidden" id="id_panjar"  name="id_panjar" value="<?php echo time().rand(100, 999)?>">
        <input type="hidden" name="jenis_pendaftaran_id" id="jenis_pendaftaran_id" value="<?php echo $jenis_pendaftaran_id?>">
        <input type="hidden" name="jenis_pendaftaran" id="jenis_pendaftaran" value="<?php echo $jenis_pendaftaran?>">
        <input type="hidden" name="satker_code" id="satker_code">
        <input type="hidden" name="nilai" id="nilai">
        <input type="hidden" name="alamat" id="alamat"> 
        <input type="hidden" name="sebutan" id="sebutan"> 
        <div class="w3-row">
          NAMA LENGKAP :<br>
          <input autocomplete="off" class="w3-input" name="nama" id="nama" placeholder="isikan Nama Lengkap Pihak" style="width: 300px" required>
        </div>
        <br>
        <div class="w3-row">
          SEBAGAI :<br>
          <select class="w3-input w3-white" name="sebutan_id" id="sebutan_id" onchange="pilih_sebagai(this.value)" required style="width: 300px">
            <option value="" selected="selected" disabled="disabled">pilih pihak sebagai</option>
            <option value="1"><?php echo $sebutan_p?></option>
            <option value="2"><?php echo $sebutan_t?></option>
          </select>
        </div>

        <br>
        <div class="w3-row">
          TEMPAT TINGGAL :<br>
          <select class="w3-select w3-white" name="ghoib" id="ghoib"   onchange="pilih_ghoib(this.value)"  style="width: 300px">
            <option value="" selected="selected" disabled="disabled">Pilih tempat tinggal pihak</option>
            <option value="1">diketahui tempat tinggalnya</option>
            <option value="0">tidak diketahui tempat tinggalnya</option>
          </select>
        </div>
        <br>
        <div id="alamat_pihak">
          <div class="w3-row">
            Propinsi:<br> 
            <select name="provinsi" id="provinsi"  required style="width: 300px" onchange="pilih_daftar(this.value,'kota','id_provinces')">
              <option value=""></option>
              <?php
              $sql_propinsi="SELECT prop, prop_name FROM komdanas_area GROUP BY prop ORDER BY prop_name ASC";
              $db = new Tampil_sekunder(); 
              $arrayData = $db->tampil_data_sekunder($sql_propinsi);  
              if (count($arrayData)) 
              { 
                foreach ($arrayData as $data) 
                {  
                  foreach($data as $key=>$value) {$$key=$value;} 
                  echo '<option value="'.$prop.'">'.$prop_name.'</option>';
                }
              }                        
              ?>
            </select>
          </div>
          <br>
          <div class="w3-row">
            Kota/Kabupaten:<br> 
            <select name="kota" id="kota"  required style="width: 300px"  onchange="pilih_daftar(this.value,'kecamatan','id_regencies')" >
              <option></option>
            </select>
          </div>
          <br>
          <div class="w3-row">
            Kecamatan:<br>      
            <select name="kecamatan" id="kecamatan"  required style="width: 300px"onchange="pilih_daftar(this.value,'kelurahan','id_district')" >
              <option></option>
            </select>
          </div>
          <br>
          <div class="w3-row">
            Kelurahan/ Desa:<br >        
            <select name="kelurahan" id="kelurahan"  required style="width: 300px"  onchange="pilih_kelurahan(this.value)">
              <option></option>
            </select> 
          </div>
        </div>  
        <div class="w3-row" id="hasil_p"> 
        </div> 
        <br>
        <div class="w3-row w3-center">
            <button type="button" class="w3-btn w3-teal"  id="btn_kirim_pemohon" onclick="kirim_pemohon()">Tambah</button>
            <button type="button" class="w3-btn w3-grey" onclick='document.getElementById("tambahpemohonModal").style.display="none"'>batal</button> 
            <br>
            <br>
            <br><br>
            <br>
            <br><br>
            <br>
            <br><br>
            <br>
            <br>
        </div> 
      </div>
    </div>
  </div>
<!-- Modal  -->
<script type="text/javascript">
  
function pilih_sebagai(isi){
  if(isi==1)
  {
    document.getElementById("sebutan").value="<?php echo $sebutan_p?>";
    document.getElementById("ghoib").value=1;
    document.getElementById("ghoib").style.display="none";
    document.getElementById("alamat_pihak").style.display="block";
  }else
  {

    document.getElementById("sebutan").value="<?php echo $sebutan_t?>";
    document.getElementById("ghoib").value="";
    document.getElementById("ghoib").style.display="block";
    document.getElementById("alamat_pihak").style.display="none";
  }
}


</script>
<script src="assets/plugin/slim_select/slimselect.min.js"></script>
</body> </html>
