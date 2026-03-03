<?php
include "_sys_admin_session.php";
$nama_halaman="komponenbiaya";
include('_sys_header_admin.php');
include('_sys_koneksi.php');
?> 
<div class="w3-container" id="isi">
  <div class="w3-row">
    <div class="w3-half">
      <b> JENIS BIAYA</b><br>
      <form method="GET" action="admin_komponenbiaya" id="form_ku">
      <select name="id" onchange="document.getElementById('form_ku').submit();" class="w3-select w3-white w3-border">
        <option value='-'>Pilih Jenis Perkara</option>
        <?php
          if(isset($_GET["id"])){
            $id=$_GET["id"];
            //echo "<h4>".$id."</h4>";
          }
          $sql="SELECT * FROM panjar_jenis_pendaftaran";
          $db = new Tampil_sekunder(); 
          $arrayData = $db->tampil_data_sekunder($sql);
          if (count($arrayData)) 
          {
            foreach ($arrayData as $data1){
                //$id_conv=base64_encode($data1["id"]);
                //echo base64_decode($id). "X" .$data1["id"]."<br>";
                if((int)$id == (int)$data1["id"]){
                  $selec=" selected='selected' ";
                }else{
                  $selec="XX";
                }
                $isi.="<option ".$selec." value='".$data1["id"]."'>".$data1["jenis_pendaftaran"]."</option>";
                
            }
            //$isi=array_push($isi, 'respon');
            //$isi=array('status'=>'ok','respon'=>$isi);
            echo $isi;  
          }

        ?>
      </select>
    </form>
    </div>
    <div class="w3-half">
      <?php
        if(isset($_GET["id"]) AND $_GET["id"]<>'-'){
          echo '<button class="w3-btn w3-green w3-right" onclick="buka_modal_jenis_biaya('.$_GET["id"].')">Tambah</button>';
          }
      ?>
    </div>    
  </div>
 <span> <b> JENIS BIAYA</b></span>
  <br>
 <br>
   <table id="tabledata" class="w3-table-all">
   <thead>
   <tr>
    <th>No</th>
    <th>Nama Biaya</th>
    <th style="text-align: right;">Biaya</th> 
    <th style="text-align: center;">Perkalian</th> 
    <th>Pihak</th> 
    <th>Ghob</th> 
    <th></th> 
   </tr>
  </thead>
  <tbody id="isi_tabel">
    <?php
    if(isset($_GET["id"]) AND $_GET["id"]<>'-'){
        $sql="SELECT *,panjar_jenis_biaya.id AS idx,
                CASE 
                  WHEN pihak=1 THEN 'Pihak P'
                  WHEN pihak=2 THEN 'Pihak T'
                  ELSE 'Semua Pihak'
                 END AS pihaknya ,
                CASE 
                  WHEN ghoib=1 THEN 'Ya'
                  ELSE '-'
                 END AS ghoibnya 
               FROM panjar_jenis_biaya WHERE jenis_pendaftaran_id='".$_GET["id"]."'
               ORDER BY urutan ASC
               ";
        $no=0;
        $db = new Tampil_sekunder(); 
        $arrayData = $db->tampil_data_sekunder($sql); 
        if (count($arrayData)) 
        {
          foreach ($arrayData as $data){
            $no++;
            echo "<tr>";
            echo "<td>".$data["urutan"]."</td>";
            echo "<td>".$data["nama_biaya"]."</td>";
            echo "<td style='text-align:right'>".number_format($data["biaya"],0,',','.')."</td>";
            echo "<td style='text-align:center'>".$data["jumlah_dikalikan"]."</td>";
            echo "<td>".$data["pihaknya"]."</td>";
            echo "<td>".$data["ghoibnya"]."</td>";
            echo "<td style='width:130px ;text-align:center'><button class='w3-btn w3-blue' onclick='edit_jenis_biaya(".$data["idx"].")'>E</button> <button class='w3-btn w3-red' onclick='hapus_jenis_biaya(".$data["idx"].")'>X</button></td>"; 
            echo "</tr>";
          }
        }
      }
    ?>
  </tbody>
</table>
<br><br><br>
  
 <!-- Modal -->
  <div id="modal_jenis_biaya" class="w3-modal" style="padding-top: 0px">
    <div class="w3-modal-content w3-card-8">
      <header class="w3-container w3-teal"> 
        <span onclick="document.getElementById('modal_jenis_biaya').style.display='none'" 
        class="w3-closebtn">&times;</span>
        <h5>Jenis Biaya</h5>
      </header>
      <div class="w3-container" id="modal_jenis_biaya_isi">
        <p>
            Urutan :<br>
            <input type="number" id="urutan" type="number" class="w3-input w3-border">
        </p>
        <p>
            Nama Biaya :<br><input type="hidden" id="id"><input type="hidden" id="jenis_pendaftaran_id">
            <input type="text" id="nama_biaya" class="w3-input w3-border">
        </p>
        <p>
            Jumlah Biaya :<br>
            <input id="biaya" type="number" class="w3-input w3-border">
        </p>
        <p>
            Jumlah Perkalian :<br>
            <input id="jumlah_dikalikan" type="number" class="w3-input w3-border">
        </p>
        <p>
            Pihak :<br>
            <select id="pihak" class="w3-select w3-white w3-border">
              <option value="0">Semua Pihak</option>
              <option value="1">Pihak P</option>
              <option value="2">Pihak T</option>
            </select>
        </p>
        <p>
            Ghoib :<br>
            <select id="ghoib" class="w3-select w3-white w3-border">
              <option value="0">Tidak</option>
              <option value="1">Ya</option>
            </select>
        </p>
        <p>
            Aktif :<br>
            <select id="aktif" class="w3-select w3-white w3-border">
              <option value="Y">Aktif</option>
              <option value="T">Tidak</option>
            </select>
        </p>
      </div>
      <footer class="w3-container ">
        <p class="w3-center">
          <button id="btn_jenisbiaya_simpan" onclick="tambah_jenis_biaya()" class="w3-btn w3-green">Simpan</button>
          <button id="btn_jenisbiaya_edit" class="w3-btn w3-green" onclick="edit_jenis_biaya_simpan()">Edit</button>
          <button class="w3-btn w3-red" onclick="document.getElementById('modal_jenis_biaya').style.display='none'">Batal</button>
        </p>
      </footer>
    </div>
  </div>
  <!-- Modal -->
</div>



<script src="assets/js/komponen_biaya.js" type="text/javascript"></script>
<?php
include("_sys_footer_admin.php");
?>