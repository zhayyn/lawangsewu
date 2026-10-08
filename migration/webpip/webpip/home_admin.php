<?php
    include('_sys_header_admin.php');
    include('_menu_login.php');
    include('_sys_koneksi.php');
    
    function tanggal_indo($tgl)
    {
        $hari = array ( 1 =>    'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu');
        $num = date('N', strtotime($tgl)); 
    	$bulan = array (1 =>   'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember');
    	$split = explode('-', $tgl);
    	return $hari[$num].', '.$split[2] . ' ' . $bulan[ (int)$split[1] ] . ' ' . $split[0];
    }
    
    // $NM_JS="";
    // $JAB_JS="";
    // $WA_KEY="";
    // $STR_ADDR="";
    $sql="
        SELECT a.NM_JS, a.JAB_JS, a.WA_KEY, a.STR_ADDR, a.WA_SENDER, a.WA_REC
        FROM tbl_conf a
        WHERE a.id_conf = 1;
    ";
    $db = new Tampil(); 
    $arrayData = $db->tampil_data($sql); 
    $no=0;
    if (count($arrayData)){ 
        foreach ($arrayData as $data) { 
            foreach($data as $key=>$value) {$$key=$value;}
        }
    }     
?>

<div class="container" style="padding-top: 120px;">
    <h1>PIP WEB</h1>
    <p>Selamat datang, ADMINISTRATOR</p>
    <div class="btn-group" style="width:100%">
        <button type="button" onclick="new_pip();" class="btn btn-primary"><i class='fa fa-file'> Tambah PIP baru</i></button>
        <button type="button" onclick="location.reload();" class="btn btn-primary"><i class='fa fa-refresh'> Refresh</i></button>
        <button type="button" onclick="set_config();" class="btn btn-primary"><i class='fa fa-cog'> Setting</i></button>
    </div>
    <div>
        <table id="tabledata" class="w3-table-all">
            <thead>
                <tr><th colspan="6" style="vertical-align:middle; background-color: green;">Daftar PIP</th></tr>
                <tr>
                    <th style="vertical-align:middle"><i>NO</i></th>
                    <th style="vertical-align:middle"><i>NOMOR PERKARA</i></th>  
                    <th style="vertical-align:middle"><i>ISI</i></th>
                    <th style="vertical-align:middle"><i>PROSES</i></th>
                </tr>
            </thead>
            <tbody>         
                <?php
                   $total=0;
                    $sql="
                        SELECT a.`id`, a.`perkara_id`, a.`nomor_perkara`, a.`jurusita_nama`, a.`nama_pihak`, a.`umur`, 
                        a.`pekerjaan`, a.`agama`, a.`link_file`, date(a.`tgl_upload`) tgl_upload, 
                        IF(LENGTH(TRIM(a.`alamat`)) > 0 OR a.`alamat` IS NOT NULL, a.`alamat`,'<tidak diketahui>') alamat,
                        date(a.tgl_putusan) tgl_putusan, a.jenis_perkara, DATEDIFF(date(a.`tgl_upload`), NOW())+1 AS lama_tayang
                        FROM tbl_pip a
                        ORDER BY a.`id` DESC;
                    ";
                    $db = new Tampil(); 
                    $arrayData = $db->tampil_data($sql); 
                    $no=0;
                    if (count($arrayData)){ 
                        foreach ($arrayData as $data) { 
                            foreach($data as $key=>$value) {$$key=$value;}
                            $no++;
                            echo "<tr id=$id>";
                            echo "<td>".$no."</td>";
                            
                            echo "<td><b>".$nomor_perkara."</b><br>
                                Tgl.Upload: ".$tgl_upload."
                            </td>";
                            echo "<td>Pada hari ini ".tanggal_indo($tgl_upload)." saya, <br><b>".$NM_JS."</b> ".$JAB_JS." pada Pengadilan Agama Semarang <br>
                                    atas perintah Ketua Majelis Hakim tersebut;<br><br>
                                    <i><b>TELAH MEMBERITAHUKAN KEPADA:</b></i><br>
                                    <b>".$nama_pihak."</b><br>
                                    Umur ".$umur." tahun, agama ".$agama.", pekerjaan ".$pekerjaan.", alamat dahulu ".$alamat." 
                                    <b>".$STR_ADDR."</b><br><br>
                                    Tentang putusan Pengadilan Agama Semarang, ".tanggal_indo($tgl_putusan)." Nomor <b>".$nomor_perkara."</b>, dalam perkara ".$jenis_perkara."<br><hr>
                                    <p><a class='btn btn-success btn-large maxima-more' href='DOC/".$link_file."' target='_blank' rel='noopener noreferrer'>🗎 Lihat Dokumen PIP</a></p>
                                    </td>";  
                            echo "<td>";
                            $pesan = "*PENTING!!!* Pemberitahuan isi Putusan Nomor *".$nomor_perkara."*. Segera posting di website Pengumuman!";
                            echo "Kirim notifikasi ke Kesekretariatan!<p>";
                            ?>
                            
                            <button class='btn btn-warning btn-large maxima-more' onclick='sendWA("<?php echo $pesan;?>")'><i class='fa fa-whatsapp'> Kirim WA</i></button>
                            
                            <?php
                            echo "<div id='divResult'></div><hr>";
                            echo "Lama tayang:<br>";
                            echo $lama_tayang." hari<br>";
                            echo "<hr>";
                            echo "<b>Dokumen PIP</b><br>"; 
                            if(isset($link_file)){
                              echo "<a title='dok_pip' href='DOC/".$link_file."' target='blank'>
                                  <button class='w3-btn w3-round' style='color: black;'><i class='fa fa-paperclip'> ".$link_file."</i></button></a><br>";
                            } else{
                              echo '<p style="color:red"><i>[file masih kosong]</i></p>';
                            }
                            echo "<hr>";
                            echo "<button class='btn btn-danger btn-large maxima-more' onclick='myFunction(".$id.")'><i class='fa fa-trash'> Hapus $nomor_perkara</i></button>";
                            echo "</td>";
                            
                            echo "</tr>";
                        }
                    }
                ?>
            </tbody>
        </table>
        <br><br><br>
    </div>
</div>

<div id="modal_conf" class="modal">
    <form class="modal-content animate" id="config_form" method="post" action="_operasi_data.php" enctype="application/x-www-form-urlencoded">
        <div class="imgcontainer">
          <span onclick="document.getElementById('modal_conf').style.display='none'" class="close" title="Close Modal">&times;</span>
        </div> 
        
            <div class="w3-container">
                <h1>Config</h1>
                <!--<p>Rubaho sakarepmu cukkkkkkk</p>-->
                <hr>
                <label for="inmjs">Nama Jurusita Mass Media:</label><br>
                <input type="text" id="inmjs" name="inmjs" value="<?php echo $NM_JS; ?>"><br>
                <label for="ijabjs">Jabatan Jurusita tersebut:</label><br>
                <input type="text" id="ijabjs" name="ijabjs" value="<?php echo $JAB_JS; ?>"><br>
                <label for="istraddr">String alamat sekarang:</label><br>
                <input type="text" id="istraddr" name="istraddr" value="<?php echo $STR_ADDR; ?>"><br>        
                <label for="iwakey">WA KEY:</label><br>
                <input type="text" id="iwakey" name="iwakey" value="<?php echo $WA_KEY; ?>"><br>
                <label for="iwasender">NO.WA SENDER: (format: 62xxx)</label><br>
                <input type="text" id="iwasender" name="iwasender" value="<?php echo $WA_SENDER; ?>"><br>                
                <label for="iwarec">NO.WA Penerima Notif: (format: 62xxx)</label><br>
                <input type="text" id="iwarec" name="iwarec" value="<?php echo $WA_REC; ?>"><br> 
                
                <input type="hidden" id="aksi" name="aksi" value="simpan_config">
                <input type="hidden" id="iidconf" name="iidconf" value="1">
            <!--</div>-->
            <!--<div class="w3-container">-->
                
                <input type="submit" data-rel="back" name="kirim" id="kirim" class="btn btn-success btn-large maxima-more">
                <!--<button class="btn btn-warning btn-large maxima-more" onclick="simpan_config()"><i class="fa fa-save"> Simpan</i></button>-->
            </div>
        
        <div class="container" style="background-color:#f1f1f1">
            
          <button type="button" onclick="document.getElementById('modal_conf').style.display='none'" class="cancelbtn">Cancel</button>
        </div>    
    </form>
</div>

<div id="modal_pip" class="modal">
  <form id="pip_form" class="modal-content animate" method="post" action="_operasi_data.php" enctype="multipart/form-data">
    <div class="imgcontainer">
      <span onclick="document.getElementById('modal_pip').style.display='none'" class="close" title="Close Modal">&times;</span>
    </div>

    <div class="container" id="pilih_nomor_perkara">
        <div id="pilih_akta">
            <center>
            <h2><b>PILIH NOMOR PERKARA</b></h2>
            <p>Silahkan isikan Angka Nomor Perkara,<br>kemudian pilih nomor perkara yang sesuai</p>
            <div class="w3-row" id="input_akta">              
                <p><input id="noperk_akta"  class="w3-input  w3-border" style="max-width: 250px;" placeholder="Angka Nomor Perkara 5555"></p>               
            </div>
            </center>    
        </div> 
    </div>
    <div class="w3-container" id="pilih_pihak"  style="display: none">
        <center>
            <p><h2><b>CEK PIHAK</b></h2></p>
            Pastikan pihak benar yang akan menerima PIP 
            <div class="w3-row" id="pilihan_pihak">
            
            </div>
        </center>
    </div>   
    
    <div class="w3-container" id="pilih_pengambil"  style="display: none">
        <center>
            <p><h2><b>DATA PIP</b></h2></p> 
            Pastikan isian data benar. Karena data berikut akan diumumkan di website<br>
            <hr>
            <p>No.Perkara: <b><span name="snomor_perkara" id="snomor_perkara"></span></b><br>
            Jenis Perkara: <b><span name="sjenis_perkara" id="sjenis_perkara"></span></b><br>
            Tgl.Putus: <b><span name="stgl_putusan" id="stgl_putusan"></span></b></p><br>
            
            <p>Nama: <b><span name="nama_pengambil" id="nama_pengambil"></span></b><br>
            Umur: <b><span name="sumur" id="sumur"></span></b><br>
            Agama: <b><span name="sagama" id="sagama"></span></b><br>
            Pekerjaan: <b><span name="spekerjaan" id="spekerjaan"></span></b><br>
            Alamat: <b><span name="salamat" id="salamat"></span></b></p>
            <p>
            Jurusita: <b><span name="sjurusita" id="sjurusita"> <?php echo $NM_JS;?> </span></b></p>
            <hr>
                <input type="hidden" id="aksi" name="aksi" value="simpan_pip">
                <input type="hidden" id="nomor_perkara" name="nomor_perkara" required>
                <input type="hidden" id="perkara_id" name="perkara_id" required>
                <input type="hidden" id="nama" name="nama" required>
                <input type="hidden" id="jurusita" name="jurusita" value="<?php echo $NM_JS;?>" required>
                <input type="hidden" id="umur" name="umur" required>
                <input type="hidden" id="pekerjaan" name="pekerjaan" required>
                <input type="hidden" id="agama" name="agama" required>
                <input type="hidden" id="alamat" name="alamat" required>
                
                <input type="hidden" id="tgl_putusan" name="tgl_putusan" required>
                <input type="hidden" id="jenis_perkara" name="jenis_perkara" required>
                
            <p><label for="pip_file">lampiran dokumen PIP:  
                <input type="file" name="pip_file" id="pip_file" required></p>
            <p>
            <input type="submit" data-rel="back" name="kirim" id="kirim" class="w3-green w3-center w3-btn w3-ripple"></p>
            <!--<button id="mybt" onclik="pip_new()">Klik simpan</button>-->
        </center>
    </div>
    <div class="container" style="background-color:#f1f1f1">
      <button type="button" onclick="document.getElementById('modal_pip').style.display='none'" class="cancelbtn">Cancel</button>
    </div>
  </form>
  <p id="log"></p>
</div>

<script src="assets/plugin/jquery/jquery.js"></script>
<script src="assets/plugin/jquery-ui/jquery-ui.min.js"></script>
<script src="assets/plugin/jquery-ui/datepicker-id.js"></script>

<script type="text/javascript">
    function set_config(){
        document.getElementById('modal_conf').style.display='block';
        document.getElementById('modal_pip').style.display='none';
        document.getElementById('pilih_nomor_perkara').style.display='none';
        document.getElementById('pilih_pihak').style.display='none';
        document.getElementById('pilih_pengambil').style.display='none';            
    }
    
    function new_pip(){
        document.getElementById('noperk_akta').value="";
        document.getElementById('pip_file').value="";
        document.getElementById('modal_pip').style.display='block';
        document.getElementById('pilih_nomor_perkara').style.display='block';
        document.getElementById('pilih_pihak').style.display='none';
        document.getElementById('pilih_pengambil').style.display='none';
        document.getElementById('modal_conf').style.display='none';
    }
    
    function pilih_nama(isi){
        var isine = isi.split("^");
        document.getElementById('nama_pengambil').innerHTML=isine[0];
        document.getElementById('salamat').innerHTML=isine[1];
        document.getElementById('perkara_id').value=isine[2];
        // document.getElementById('sjurusita').innerHTML= isine[3]; 
        document.getElementById('sumur').innerHTML=isine[4];
        document.getElementById('spekerjaan').innerHTML=isine[5];
        document.getElementById('sagama').innerHTML=isine[6];
        document.getElementById('stgl_putusan').innerHTML=isine[7];
        document.getElementById('sjenis_perkara').innerHTML=isine[8];        
        document.getElementById('snomor_perkara').innerHTML=isine[9];
        document.getElementById('nama').value=isine[0];
        document.getElementById('alamat').value=isine[1];
        document.getElementById('perkara_id').value=isine[2];
        document.getElementById('jurusita').value=isine[3];
        document.getElementById('umur').value=isine[4];
        document.getElementById('pekerjaan').value=isine[5];
        document.getElementById('agama').value=isine[6];
        document.getElementById('tgl_putusan').value=isine[7];
        document.getElementById('jenis_perkara').value=isine[8];
        document.getElementById('pilih_pengambil').style.display='block';
        document.getElementById('pilihan_pihak').style.display='none';
        document.getElementById('pilih_pihak').style.display='none';
    } 
    
    function myFunction(id_pip) {
      let text = "Apakah Anda yakin ingin menghapus?\nEither OK or Cancel.";
      if (confirm(text) == true) {
        hapus_pip(id_pip);
      } else {
        text = "You canceled!"+id_pip;
      }
      alert(text);
    }
    
    function hapus_pip(id_pip){
        var jml_err = 0;
        
        if(jml_err == 0){        
          //UPDATE MT_PERMOHONAN STTAUS VALIDASI
          var xhr = new XMLHttpRequest();
          var url='_operasi_data.php';
          xhr.open("POST", url, true);
          xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
          xhr.onreadystatechange = function(){
              if(xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200){
                location.reload();
              }
          }
          xhr.send( 
              "&aksi=hapus_data_pip"+
              "&tabel=tbl_pip"+
              "&id_pip="+id_pip
          );
        
          alert("Data berhasil dihapus");
        } else {
          alert("Tolong lengkapi isian");
          exit();
        }
    }
    
    function simpan_config(){
      var nmjs = document.getElementById('inmjs').value;
      var jabjs =document.getElementById('ijabjs').value;
      var straddr = document.getElementById('istraddr').value;
      var wakey = document.getElementById('iwakey').value;
      var wasender = document.getElementById('iwasender').value;
      var warec = document.getElementById('iwarec').value;
      
      var xhr = new XMLHttpRequest();
      var url='_operasi_data';
      xhr.open("POST", url, true);
      xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
      xhr.onreadystatechange = function(){
          if(xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200){
            alert('awas kesimpen'); location.reload();
          }
      };
      xhr.send( 
          "aksi=simpan_config"+
          "&tabel=tbl_conf"+
          "&ID_CONF=1"+
          "&NM_JS="+encodeURIComponent(nmjs.value)+
          "&JAB_JS="+encodeURIComponent(jabjs.value)+
          "&WA_KEY="+encodeURIComponent(wakey.value)+
          "&WA_KEY="+encodeURIComponent(wasender.value)+
          "&WA_KEY="+encodeURIComponent(warec.value)+
          "&STR_ADDR="+encodeURIComponent(straddr.value)
      );
    }

    function sendWA(pesan) {
        var xhttp = new XMLHttpRequest();
        var wa_key = '<?php Print($WA_KEY); ?>';
        var wa_sender = '<?php Print($WA_SENDER); ?>';
        var wa_rec = '<?php Print($WA_REC); ?>';
        
        // var wa_key = 'XZxmhfNp8aqKVyyN214OH4HFvtDN5x';
        xhttp.open("GET", "https://pas.desablockchain.com/send-message?api_key="+wa_key+"&sender="+wa_sender+"&number="+wa_rec+"&message="+pesan, true);
        xhttp.onreadystatechange = function ()
        {
            alert('Pesan WA terkirim ke Kesekretariatan!');
            // $('#divResult').html('Pesan WA Terkirim');
        };
        xhttp.send();
    } 
</script>

<script type="text/javascript">
    $(document).ready(
    function(){
      $( "#noperk_akta" ).autocomplete({
        source: function( request, response ) {
          // Fetch data
          $.ajax({
            url: "__apipa.php",
            type: 'post',
            dataType: "json",
            data: {
              noperk: btoa(request.term),aksi:btoa('cari_nomor_perkara'),jenis:btoa('akta')
            },
            success: function( data ) {
              response( data );
            }
          });
          },
          select: function (event, ui) {
            $('#noperk_akta').val(ui.item.value);
            $('#nomor_perkara').val(ui.item.value);

            var pihak2_text=ui.item.pihak2_text;
            var pihak2_alamat=ui.item.pihak2_alamat;
            var pihak2_alamat=pihak2_alamat.replace("'", " ");
            var pihak2_alamat=pihak2_alamat.replace('"', " ");
            
            var perkara_id=ui.item.perkara_id.trim();
            
            var pac=ui.item.pac;
            var pac2=ui.item.pac2;
            var jurusita = ui.item.nm_jurusita.trim();
            var umur = ui.item.umur.trim();
            var agama = ui.item.agama.trim();
            var pekerjaan = ui.item.pekerjaan.trim();
            var jperkara = ui.item.jenis_perkara.trim();
            var tgl_putus = ui.item.tgl_putusan;
            var noperk = ui.item.value.trim();

            if(pac2){
              var pihak2="<a href='permohonan'><div class='w3-panel w3-red w3-card-8'><p><b>"+ui.item.pihak2_text+"</b><br>Akta Cerai sudah diambil pada tanggal "+pac2+"</p></div></a>";
                    document.getElementById('pilih_pengambil').style.display='block';
            }else
            {
              var pihak2="<a href='#' onclick='pilih_nama("+'"'+pihak2_text+'^'+pihak2_alamat+'^'+perkara_id+'^'+jurusita+'^'+umur+'^'+pekerjaan+'^'+agama+'^'+tgl_putus+'^'+jperkara+'^'+noperk+'"'+")'><div class='w3-panel w3-blue w3-card-8'><p><b>"+ui.item.pihak2_text+"</b></p></div></a>";
            }

            document.getElementById("pilihan_pihak").innerHTML=pihak2;
            document.getElementById('pilih_pihak').style.display='block';
            document.getElementById('pilih_nomor_perkara').style.display='none';
            document.getElementById('noperk_akta').value='';
            return false;
          }
      });
  });
</script>

<?php
    include('_sys_footer.php');
?>