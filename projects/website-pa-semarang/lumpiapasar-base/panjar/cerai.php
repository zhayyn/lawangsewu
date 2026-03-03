<?php
  include('_sys_header.php');
if(isset($_GET["jenis"]))
{
  $jenis=$_GET["jenis"];
}else
{
  exit;
}

if($jenis==1)
{
  $jenis_perkara="Cerai Gugat";
  $pasangan="SUAMI";
  $keterangannya="adalah Perceraian yang diajukan oleh seorang istri";
  $sebutan_p="Penggugat";
  $sebutan_t="Tergugat";
}else
{
  $jenis_perkara="Permohonan Cerai Talak";
  $pasangan="ISTRI";
  $keterangannya="adalah Perceraian yang diajukan oleh seorang suami";
  $sebutan_p="Pemohon";
  $sebutan_t="Temohon";
}
?>

<div class="loading" id="loader">Loading&#8230;</div>
<link href="assets/plugin/slim_select/slimselect.min.css" rel="stylesheet" />
<div style="position: fixed;top: 0px;right:0px;">
    <a href="index.php" class="w3-btn w3-teal">
    <i class="material-icons">arrow_back</i>
    </a>
</div>

<h4 class="w3-center"><?php echo $jenis_perkara?></h4>
<div class="w3-row" id="hasil_cerai">  
<div class="w3-card-4">
    <div class="w3-container w3-teal">
        <h5>IDENTITAS ANDA :</h5>
    </div>
    <div class="w3-container">
        <p>      
            <label class="w3-text-brown"><b>NAMA LENGKAP ANDA:</b></label>
            <input class="w3-input w3-border" name="nama_p" id="nama_p" placeholder="isikan Nama Lengkap Anda" required>
        </p>
        <p>      
            <label class="w3-text-brown">TEMPAT TINGGAL ANDA<br>Apabila bertempat tinggal di <?php echo $default_kota?>, silahkan pilih kecamatan kemudian Desa<br>Propinsi:</label> 
                <select name="provinsi" id="provinsi" onchange="pilih_daftar(this.value,'kota','id_provinces')" required>
                    <option value="<?php echo $default_propinsi_id?>"><?php echo $default_propinsi?></option>
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
        <p>      
            <label class="w3-text-brown">Kota/Kabupaten:</label>
            <select name="kota" id="kota" onchange="pilih_daftar(this.value,'kecamatan','id_regencies')" required>
                <option value="<?php echo $default_kota?>"><?php echo $default_kota?></option>
                  <?php
                    //kabkota FROM panjar_kelurahan_komdanas WHERE prop='".$id_provinces."' 
                    $sql_propinsi="SELECT kabkota FROM komdanas_area  WHERE prop='".$default_propinsi_id."' GROUP BY kabkota ORDER BY kabkota ASC";
                    $db = new Tampil_sekunder(); 
                    $arrayData = $db->tampil_data_sekunder($sql_propinsi);  
                    if (count($arrayData)) 
                    { 
                      foreach ($arrayData as $data) 
                      {  
                        foreach($data as $key=>$value) {$$key=$value;}
                       
                        echo '<option value="'.$kabkota.'">'.$kabkota.'</option>';
                      }
                    }                        
                    ?> 
            </select>
        </p>
        <p>
            <label class="w3-text-brown">Kecamatan:</label>      
            <select name="kecamatan" id="kecamatan" onchange="pilih_daftar(this.value,'kelurahan','id_district')"  required>
                <option></option>

                  <?php
                    //kabkota FROM panjar_kelurahan_komdanas WHERE prop='".$id_provinces."' 
                    // $sql_propinsi="SELECT * FROM panjar_kelurahan_komdanas  WHERE kabkota='".$default_kota."' GROUP BY kec ORDER BY kec ASC";
                    $sql_kel="SELECT * FROM komdanas_area WHERE kabkota='".$default_kota."' AND prop = '".$default_propinsi_id."' GROUP BY kec ORDER BY kec ASC";
                    $db = new Tampil_sekunder(); 
                    $arrayData = $db->tampil_data_sekunder($sql_kel);  
                    if (count($arrayData)) 
                    { 
                      foreach ($arrayData as $data) 
                      {  
                        foreach($data as $key=>$value) {$$key=$value;}
                       
                        echo '<option value="'.$kec.'">'.$kec.'</option>';
                      }
                    }                        
                    ?> 
            </select>
        </p>
        <p>
            <label class="w3-text-brown">Kelurahan/ Desa:</label>        
                <select name="kelurahan" id="kelurahan" onchange="pilih_kelurahan(this.value)"  required>
                    <option></option>
                </select> 
        </p>
        <div class="w3-rows" id="hasil_p"></div> 
    </div>

      <input type="hidden" name="jenis_pendaftaran" id="jenis_pendaftaran" value="<?php echo $jenis_perkara?>">
      <input type="hidden" name="jenis_pendaftaran_id" id="jenis_pendaftaran_id" value="<?php echo $jenis?>">
      <input type="hidden" name="satker_code" id="satker_code">
      <input type="hidden" name="nilai" id="nilai">
      <input type="hidden" name="alamat" id="alamat">
      <input type="hidden" name="satker_code1" id="satker_code1">
      <input type="hidden" name="nilai1" id="nilai1">
      <input type="hidden" name="alamat1" id="alamat1">
      <input type="hidden" name="sebutan_p" id="sebutan_p" value="<?php echo $sebutan_p?>">
      <input type="hidden" name="sebutan_t" id="sebutan_t" value="<?php echo $sebutan_t?>">
      <input type="hidden" id="id_panjar"  name="id_panjar" value="<?php echo time().rand(100, 999)?>">
   
             
</div>
<div class="w3-card-4">
    <div class="w3-container w3-teal">
        <h5>IDENTITAS PASANGAN ANDA (<?php echo $pasangan?>) :</h5>
    </div>
    <div class="w3-container">
        <p>      
            <label class="w3-text-brown"><b>NAMA LENGKAP <?php echo $pasangan?>:</b></label>
            <input autocomplete="off" class="w3-input w3-border" name="nama_t" id="nama_t" placeholder="isikan nama lengkap <?php echo strtolower($pasangan)?>"  required>
        </p>
        <p>      
            <label class="w3-text-brown"><b>TEMPAT TINGGAL <?php echo $pasangan?>:</b></label>
            <select class="w3-select w3-border w3-white" name="ghoib" id="ghoib"   onchange="pilih_ghoib(this.value)">
                <option disabled="disabled" selected="selected">Pilih Keberadaan Pasangan</option>
                <option value="1">Diketahui tempat tinggalnya</option>
                <option value="0">tidak diketahui alamatnya</option>
              </select>
        </p>

            <div id="identitas_t" class="w3-row" style="display: none"> 
               <p>
                <label class="w3-text-brown">ALAMAT <?php echo $pasangan?><br>Apabila bertempat tinggal di <?php echo $default_kota?>, silahkan pilih kecamatan kemudian Desa <br>Propinsi:</label> 
                <select name="provinsi1" id="provinsi1" style="min-width: 300px"  onchange="pilih_daftar1(this.value,'kota','kota1','id_provinces')">
                  <option value="<?php echo $default_propinsi_id?>"><?php echo $default_propinsi?></option>
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
              </p> 
              <p>
                <label class="w3-text-brown">Kota/Kabupaten:</label> 
                <select name="kota1" id="kota1"   onchange="pilih_daftar1(this.value,'kecamatan','kecamatan1','id_regencies')">
                   <option value="<?php echo $default_kota?>"><?php echo $default_kota?></option>
                  <?php
                    //kabkota FROM panjar_kelurahan_komdanas WHERE prop='".$id_provinces."' 
                    $sql_propinsi="SELECT kabkota FROM komdanas_area  WHERE prop='".$default_propinsi_id."' GROUP BY kabkota ORDER BY kabkota ASC";
                    $db = new Tampil_sekunder(); 
                    $arrayData = $db->tampil_data_sekunder($sql_propinsi);  
                    if (count($arrayData)) 
                    { 
                      foreach ($arrayData as $data) 
                      {  
                        foreach($data as $key=>$value) {$$key=$value;}
                       
                        echo '<option value="'.$kabkota.'">'.$kabkota.'</option>';
                      }
                    }                        
                    ?> 
                </select>
                 
              </p>
              <p>
                <label class="w3-text-brown">Kecamatan:</label>      
                <select name="kecamatan1" id="kecamatan1"    onchange="pilih_daftar1(this.value,'kelurahan','kelurahan1','id_district')">
                  <option></option>
                  <?php
                    //kabkota FROM panjar_kelurahan_komdanas WHERE prop='".$id_provinces."' 
                    $sql_propinsi="SELECT kec FROM komdanas_area  WHERE kabkota='".$default_kota."' GROUP BY kec ORDER BY kec ASC";
                    $db = new Tampil_sekunder(); 
                    $arrayData = $db->tampil_data_sekunder($sql_propinsi);  
                    if (count($arrayData)) 
                    { 
                      foreach ($arrayData as $data) 
                      {  
                        foreach($data as $key=>$value) {$$key=$value;}
                       
                        echo '<option value="'.$kec.'">'.$kec.'</option>';
                      }
                    }                        
                    ?> 
                </select>
                </p>
              <p>
                <label class="w3-text-brown">Kelurahan/ Desa:</label>        
                <select name="kelurahan1" id="kelurahan1"  onchange="pilih_kelurahan1(this.value)" >
                  <option></option>
                </select> 
              </p>
              <div class="w3-rows" id="hasil_t"></div>
            </div>
    </div>
</div>

 
            <script type="text/javascript">
            </script>
            <br>
<div class="w3-container"> 
        <button  name="hitung" id="hitung" class="w3-btn w3-teal w3-block" onclick="kirim_data_cerai()">Hitung</button>
    </div>
      </form>
      </div>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    </div>  
    <script src="assets/plugin/slim_select/slimselect.min.js"></script>

<script src="assets/js/cerai.js"></script>
  </body> </html>