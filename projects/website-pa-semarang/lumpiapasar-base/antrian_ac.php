<?php
    if(!isset($_SESSION)){session_start();}
    // if(isset($_SESSION['permohonanid'])){
    //     $_SESSION['permohonanid']=$_SESSION['permohonanid'];
    // }else{
    //     $_SESSION['permohonanid']="";
    // }
    // ini_set('display_errors', 1);
    // ini_set('display_startup_errors', 1);
    // error_reporting(0);
    
    // include('_sys_config.php');
    include('_sys_header.php');
    // include('_menu.php');
?>

<style type="text/css">
  .loader {
    border: 16px solid #f3f3f3;
    border-radius: 50%;
    border-top: 16px solid #3498db;
    width: 40px;
    height: 40px;
    -webkit-animation: spin 2s linear infinite; /* Safari */
    animation: spin 2s linear infinite;
  }

  /* Safari */
  @-webkit-keyframes spin {
    0% { -webkit-transform: rotate(0deg); }
    100% { -webkit-transform: rotate(360deg); }
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }
</style>

    <div class="loading" id="loader">Loading&#8230;</div>
    <!-- Header -->
    <div class="w3-top w3-row w3-card-2 w3-padding w3-center w3-white">
      <div class="w3-col" style="width:30px">
        <a href="https://lumpiapasar.pa-semarang.go.id/akta_cerai.php" class="headerButton goBack">
          <img src="assets/images/arrow_back-black-24dp.svg" class="w3-left w3-circle w3-margin-right" style="width:30px">
        </a>
      </div>
      <div class="w3-rest w3-cell-middle"><span style="font-size: 18px">ANTRIAN AKTA CERAI/E-AC</span></div>
    </div> 
    <!-- Header -->
    <br>
    <br>

    
    
    <div class="w3-container" id="permohonan">
      
        <div class="w3-row" >
            <center>
            <div class="w3-container" id="isi">
                <div class="w3-section ">
                    <div class="w3-panel w3-pale-red w3-border w3-center">
                        <h4><b>PERHATIAN</b></h4>
                        <p>Ini adalah form pengambilan Akta Cerai di Kantor Pengadilan Agama Semarang<br>Tujuan dengan mengisi form ini adalah untuk mempercepat waktu menyiapkan dokumen Akta Cerai oleh Petugas<br>
                        sehingga tidak terjadi antrian tunggu yang lama di PTSP<br>Jika Anda yakin ingin melanjutkan silahkan persiapkan data berikut:</p>
                        <ol style="margin-left:-20px;text-align: justify;">
                            <li>Pilih Produk Pengadilan yang akan diambil (<b>Akta Cerai</b>)</li>
                            <li><b>Nomor Perkara</b> (contoh <b>xxxx/Pdt.G/2021/PA.Smg</b>)</li>
                            <li><b>Tanggal Pengambilan</b> (Pastikan hadir di Kantor pada hari yang telah Anda tentukan)</li>
                        </ol>                        
                    </div>
                    <div class="w3-panel w3-pale-blue w3-border">
                        <p>
                        Untuk melanjutkan, silahkan klik <b>tombol</b> dibawah ini
                        </p>
                        <a href="#mulai" class="w3-btn w3-block w3-round-large w3-blue w3-section w3-padding" onclick="modal_input()"><i class="fa fa-edit"></i> ISI PERMOHONAN</a>
                    </div>
                    <div class="w3-panel w3-pale-green w3-border"> 
                        <h4><b>Alternatif Pengambilan Akta Cerai</b></h4>
                        <p>Pengadilan Agama Semarang memberikan Anda beberapa pilihan dalam pengambilan Akta Cerai<br>
                        Selain datang langsung ke Kantor, Anda juga bisa menggunakan salah satu layanan aplikasi berikut:</p>
                        <ol style="margin-left:-20px;text-align: justify;">
                            <li><a href="https://tahupetis.pa-semarang.go.id"><b><u>Layanan TahuPetis</u></b><a> yaitu pengambilan Akta Cerai dengan sistem COD (untuk domisili area Banyumanik dan Pedurungan)</li>
                            <li><a href="https://go-acorona.pa-semarang.go.id"><b><u>Layanan GO-aCorona</u></b><a> yaitu pengambilan Akta Cerai dengan menggunakan kurir/jasa antar GO-JEK</li>
                        </ol>
                    </div>                    
                </div>
            </div>
          </center>
        </div>
      
        <!-- modal -->
        <div id="modal_input" class="w3-modal" style="padding-top: 0px;">
            <div class="w3-modal-content" style="max-width:800px min ">
                <div class="w3-container w3-left">
                    <br>
                    <a href="https://lumpiapasar.pa-semarang.go.id/antrian_ac.php"><button class="w3-btn w3-small w3-round-large w3-red w3-center w3-ripple">Batal</button></a>
                </div>
                <hr>
                
                <div class="w3-container" id="pilih_produk" style="display: none">
                    <center>
                        <h2><b>PILIH PRODUK</b></h2>
                        <p>Silahkan pilih produk yang akan anda ambil</p>
                        <div class="w3-row"> 
                            <button class="w3-blue w3-center w3-block w3-btn w3-round-large w3-ripple" onclick="pilih_jenis_produk('Akta Cerai')">Akta Cerai</button><br>
                        </div>
                    </center>
                </div>
                
                <div class="w3-container" id="pilih_nomor_perkara"  style="display: none">
                    <div id="pilih_akta">
                        <center>
                        <h2><b>PILIH NOMOR PERKARA</b></h2>
                        <p>Silahkan isikan Angka Nomor Perkara,<br>kemudian pilih nomor perkara yang sesuai</p>
                        <div class="w3-row" id="input_akta">              
                            <p><input id="noperk_akta"  class="w3-input  w3-border" style="max-width: 250px;" placeholder="Angka Nomor Perkara 5555"></p>               
                        </div>
                        <div class="loader" id="divmuter" style="display: none;"></div>
                        </center>    
                    </div> 
                </div>
                
                
                <div class="w3-container" id="pilih_pihak"  style="display: none">
                    <center>
                        <p><h2><b>PILIH PIHAK</b></h2></p>
                        Silahkan pilih Pihak yang akan menerima Produk 
                        <div class="w3-row" id="pilihan_pihak">
                        
                        </div>
                    </center>
                </div>
                
                <form action="api_ngapak.php" onsubmit="return kirim_pendaftaran();" method="post" enctype="application/x-www-form-urlencoded">
                    
                    <div class="w3-container" id="pilih_pengambil"  style="display: none">
                        <center>
                            <p><h2><b>DATA PENERIMA</b></h2></p> 
                            Pastikan isian data benar. Karena data berikut akan dicek kembali ketika pengambilan Produk
                            <p>Pastikan <b>Nama</b> Anda benar sesuai KTP yang berlaku:* <br><input type="text" name="nama_pengambil" id="nama_pengambil" class="w3-input w3-border" disabled></p>
                            <p><b>Tanggal/Jam</b> pengambilan Akta Cerai:* <br>
                                <div class="w3-container w3-cell">
                                    <input type="date" name="tgl_ambil" id="tgl_ambil" 
                                        min="<?php echo date('Y-m-d', strtotime(date(). ' + 1 days')); ?>" 
                                        min="<?php echo date('Y-m-d', strtotime(date(). ' + 14 days')); ?>"
                                    class="w3-input w3-border" required>
                                </div>
                                <div class="w3-container w3-cell">
                                    <input type="time" class="w3-input w3-border" id="ijam" name="ijam" min="09:00" max="15:00" required>
                                </div>
                                <div class="w3-panel w3-yellow">
                                    <p>Permohonan akan diproses pada hari kerja:<br>
                                        <strong>Senin - Jumat Pkl.09:00 - 15:00 WIB</strong><br>
                                        Pastikan Anda memilih tanggal & jam sesuai jadwal di atas
                                    </p>
                                </div>
                            <p><b>Nomor Whatsapp* Aktif</b>:<br>
                                <input type="text" name="nomor_hp" id="nomor_hp" class="w3-input w3-border" placeholder="0821xxxxxxxx" required><br>
                                <i>*) Kami membutuhkan nomor WA aktif untuk menyampaikan notifikasi dan pemberitahuan terkait permohonan yang Anda ajukan.</i>
                            </p>
                            <hr>
                            <a href="#" onclick="pilih_waktu()" class="w3-green w3-center w3-round-large w3-btn w3-ripple">Berikutnya</a>
                        </center>
                    </div>
                    
                    <div class="w3-container" id="sudah_diambil"  style="display: none">
                        <div class="w3-panel w3-pale-red w3-border">
                            <center>
                            <p>
                                <b>AKTA CERAI SUDAH ADA YANG DIAMBIL</b>
                            </p>
                            <p>
                                Jika Anda merasa kehilangan dokumen Akta Cerai ASLI, pastikan anda membawa <b>Surat Keterangan Kehilangan</b> dari kepolisian.
                                Untuk kemudian bisa diproses ke Kantor Pengadilan Agama Semarang untuk kita buatkan Salinan.
                            </p>
                            <br>
                            <br>
                            </center>
                        </div>
                    </div>
                    
                    <div class="w3-container" id="pilih_waktu" style="display: none">          
                        <center>          
                            <p><h2><b>PILIH LOKASI & WAKTU</b></h2></p>
                            <p>Berikut adalah daftar lokasi dan waktu pengambilan. Cek menu <b>Jadwal</b> untuk kejelasan detail lokasi dan waktu pengambilan</p>
                        </center>
                        <hr>  
                        <?php
                            $sqla = //"call _get_jadwal();"; 
                                // "select 0 as id, '<b>Kantor Pengadilan Agama Semarang</b>, setiap Sabtu, Pukul 08:00-12:00' as jam union all 
                                "select j.* FROM (
                                    SELECT a.id, CONCAT('<b>',a.nama_lokasi,' ',b.nama_area, '</b> Tgl.',a.tanggal,', Pukul ',a.jam) as jam
                                    FROM dt_jadwal a
                                    LEFT OUTER JOIN m_area b ON b.id = a.id_area
                                    GROUP BY a.id order by a.tanggal ASC
                                ) j";
                            $db = new Tampil_sekunder(); 
                            $arrayData = $db->tampil_data_sekunder($sqla); 
                            
                            if (count($arrayData)){ 
                                foreach ($arrayData as $data){
                                    echo '<input onclick="pilih_foto(this.id)" id="'.$data["id"]."^".$data["jam"].'" class="w3-radio" type="radio" name="jam" value="'.$data["id"]."^".$data["jam"].'">
                                    <label for="'.$data["id"].'">'.$data["jam"].'</label><br>';
                                }
                            }
                        ?>
                        <hr>
                        <br><br>
                    </div>
                    
                    <div class="w3-container" id="pilih_foto"  style="display: none">
                        <center>
                            </h4><p><b><h2>RINGKASAN & PEMBAYARAN</h2></b></p>
                        </center>
                        Harap cek kembali data di bawah ini:
                        <br>
                        <div class="w3-panel w3-pale-red w3-border">
                            <ul style="margin-left:-20px;text-align: justify;">
                                <li>Nomor Perkara:      <b><span id="vno_perkara"></span></b></li>
                                <li>Nomor Akta Cerai:   <b><span id="vno_ac"></span></b></li>
                                <li>Nama:               <b><span id="vnama"></span></b></li>
                                <li>Tempat: <strong>PTSP</strong> Kantor Pengadilan Agama Semarang Kelas I-A</li>
                                <li>Tanggal:               <b><span id="vtgl"></span></b></li>
                                <li>Jam:               <b><span id="vjam"></span></b></li>
                                <li>Nomor WA:           <b><span id="vno_hp"></span></b></li>
                            </ul>              
                        </div>
                        <div>
                            <div class="w3-panel w3-yellow w3-topbar w3-bottombar w3-border-amber">
                              <p>
                                Berdasarkan Peraturan Pemerintah (PP) Nomor 5 Tahun 2019 tentang Jenis dan Tarif atas Jenis Penerimaan Negara Bukan Pajak yang Berlaku pada Mahkamah Agung dan Badan Peradilan yang Berada di Bawahnya, <strong>Pengambilan Akta Cerai</strong> akan dikenakan biaya. Biaya tersebut akan kami sampaikan setelah permohonan ini dibuat
                              </p>
                            </div
                            <br>
                            <i class="fa fa-money"></i> <strong>Pilihan Jenis Pembayaran:</strong><br>
                            <select class="w3-input" id="jenis_bayar" name="jenis_bayar" required>
                                <option value="0" selected>Pembayaran langsung di Kasir PTSP PA Semarang</option>
                                <option value="1" disabled>Transfer pada saat Hari Pengambilan</option>
                            </select>
                        </div>
                        <p>
                            <input type="checkbox" onchange="tampilkan_kirim(this.value)" id="cek" name="cek" required><label for="cek">
                                Saya menyetujui persyaratan di atas dan saya siap datang untuk mengambil Produk</label>
                        </p>
                        <input type="hidden" id="nomor_perkara" name="nomor_perkara" required>
                        <input type="hidden" id="perkara_id" name="perkara_id" required>
                        <input type="hidden" id="pihak_no" name="pihak_no" required>
                        <input type="hidden" id="produk" name="produk" required>
                        <input type="hidden" id="nama" name="nama" required>
                        <input type="hidden" id="nomor_akta_cerai" name="nomor_akta_cerai">
                        <input type="hidden" id="para_pihak" name="para_pihak" required>
                        <input type="hidden" id="jadwal" name="jadwal" required>
                        
                        <!--<input type="hidden" id="tgl_ambil" name="tgl_ambil" required>-->
                        <!--<input type="hidden" id="jam_ambil" name="jam_ambil" required>-->
                        <!--<input type="hidden" id="nowa" name="nowa" required>-->
                        
                        
                        
                        <input type="hidden" id="aksi" name="aksi" value="simpan_antrian_ac">
                        <hr>
                        <center>
                            <input type="submit" name="kirim" id="kirim" class="w3-green w3-center w3-btn w3-round-large w3-ripple">
                        </center>  
                    </div>
                    <div class="w3-container" id="proses"  style="display: none">
                          
                    </div>
                    <br>
                    <!--<hr>-->
                    <br><br>
                    <!--<br><br><br><br><br><br>-->
                </form>
            </div>
        </div> <!-- modal -->
        <br>
    </div> <!--Kontainer Permohonan-->



<script src="assets/plugin/jquery/jquery.js"></script>
<script src="assets/plugin/jquery-ui/jquery-ui.min.js"></script>
<script src="assets/plugin/jquery-ui/datepicker-id.js"></script>

<script type="text/javascript">
    $(document).ready(function(){
      $( "#noperk_akta" ).autocomplete({
        source: function( request, response ) {
          // Fetch data
          $.ajax({
            url: "api_ngapak.php",
            type: 'post',
            dataType: "json",
            data: {
              noperk: btoa(request.term),aksi:btoa('cari_nomor_perkara'),jenis:btoa('akta')
            },
            cache: false,
            beforeSend: function(){
              document.getElementById('divmuter').style.display='block';
            },            
            success: function( data ) {
              response( data );
              document.getElementById('divmuter').style.display='none';
            }
          });
          },
          select: function (event, ui) {
            $('#noperk_akta').val(ui.item.value);
            $('#nomor_perkara').val(ui.item.value);
            $('#nomor_akta_cerai').val(ui.item.nomor_akta_cerai);
            $('#para_pihak').val(ui.item.para_pihak);

            var pihak1_text=ui.item.pihak1_text.trim();
            var pihak2_text=ui.item.pihak2_text.trim();
            var pihak1_alamat=ui.item.pihak1_alamat.trim();
            var pihak1_alamat=pihak1_alamat.replace("'", " ");
            var pihak1_alamat=pihak1_alamat.replace('"', " ");
            var pihak2_alamat=ui.item.pihak2_alamat.trim();
            var pihak2_alamat=pihak2_alamat.replace("'", " ");
            var pihak2_alamat=pihak2_alamat.replace('"', " ");
            
            var perkara_id=ui.item.perkara_id.trim();
            
            var pac=ui.item.pac;
            var pac2=ui.item.pac2;

            if(pac){ 
              var pihak1="<a href='permohonan'><div class='w3-panel w3-red w3-card-8'><p><b>"+ui.item.pihak1_text+"</b><br>Akta Cerai sudah diambil pada tanggal "+pac+"</p></div></a>";
                    document.getElementById('sudah_diambil').style.display='block';           
     
            } else{
              var pihak1="<a href='#' onclick='pilih_nama("+'"'+pihak1_text+'^'+pihak1_alamat+'^'+perkara_id+'^'+"1"+'"'+")'><div class='w3-panel w3-blue w3-card-8'><p><b>"+ui.item.pihak1_text+"</b></p></div></a>";              
            }

            if(pac2){
              var pihak2="<a href='permohonan'><div class='w3-panel w3-red w3-card-8'><p><b>"+ui.item.pihak2_text+"</b><br>Akta Cerai sudah diambil pada tanggal "+pac2+"</p></div></a>";
                    document.getElementById('sudah_diambil').style.display='block';
            }else
            {
              var pihak2="<a href='#' onclick='pilih_nama("+'"'+pihak2_text+'^'+pihak2_alamat+'^'+perkara_id+'^'+"2"+'"'+")'><div class='w3-panel w3-blue w3-card-8'><p><b>"+ui.item.pihak2_text+"</b></p></div></a>";
            }

            document.getElementById('noperk_akta').value='';
            document.getElementById("pilihan_pihak").innerHTML=pihak1+pihak2;
            document.getElementById('pilih_pihak').style.display='block';
            document.getElementById('pilih_nomor_perkara').style.display='none';
            document.getElementById('nama_pengambil').focus();
            return false;
          }
      })
  });

</script>

<script type="text/javascript">

    function modal_input(){
        document.getElementById('modal_input').style.display="block";
        document.getElementById('pilih_produk').style.display="block";
        document.getElementById('pilih_nomor_perkara').style.display="none";
        document.getElementById('noperk_akta').value='';
        document.getElementById('pilih_pihak').style.display="none";
        document.getElementById('pilihan_pihak').innerHTML="";
        document.getElementById('pilih_waktu').style.display="none";
        document.getElementById('pilih_foto').style.display="none";
    }

    function pilih_jenis_produk(isi){
        document.getElementById('produk').value=isi;
        document.getElementById('pilih_produk').style.display="none";
        document.getElementById('pilih_nomor_perkara').style.display="block";
        
        if (isi != null && isi=="Akta Cerai"){
            document.getElementById('noperk_akta').style.display='block';
            document.getElementById('noperk_akta').focus();
        }else {
            document.getElementById('noperk_akta').style.display='none';
        }
    }

    function pilih_nama(isi){
        var isine = isi.split("^");
        document.getElementById('nama').value=isine[0];
        document.getElementById('nama_pengambil').value=isine[0];
        document.getElementById('perkara_id').value=isine[2];
        document.getElementById('pihak_no').value=isine[3];
        document.getElementById('pilih_pengambil').style.display='block';
        document.getElementById('pilihan_pihak').style.display='none';
        document.getElementById('pilih_pihak').style.display='none';
        document.getElementById('sudah_diambil').style.display='none';
        document.getElementById('tgl_ambil').value = '<?php echo date('Y-m-d', strtotime(date(). ' + 1 days')); ?>';
    }

    function pilih_waktu(){
        if(document.getElementById("nama_pengambil").value==null || document.getElementById("nama_pengambil").value==""){
            document.getElementById("nama_pengambil").focus();
            return false;
        } else if(document.getElementById("tgl_ambil").value==null || document.getElementById("tgl_ambil").value==""){
            document.getElementById("tgl_ambil").focus();
            return false;
        } else if(document.getElementById("ijam").value==null || document.getElementById("ijam").value==""){
            document.getElementById("ijam").focus();
            return false;
        } else if(document.getElementById("nomor_hp").value==null || document.getElementById("nomor_hp").value==""){
            document.getElementById("nomor_hp").focus();
            return false;
        }
        vno_perkara.innerHTML=document.getElementById('nomor_perkara').value;
        vno_ac.innerHTML=document.getElementById('nomor_akta_cerai').value;
        vnama.innerHTML=document.getElementById('nama_pengambil').value;
        vno_hp.innerHTML=document.getElementById('nomor_hp').value;
        vjam.innerHTML=document.getElementById('ijam').value;
        vtgl.innerHTML=document.getElementById('tgl_ambil').value;
        
        document.getElementById('pilih_foto').style.display='block';
        document.getElementById('pilih_pengambil').style.display='none';
        document.getElementById('kirim').style.display='none';
        document.getElementById('cek').focus();        
    }

    function tampilkan_kirim(isi){
        if(isi=="" || isi==null || isi == false){
            document.getElementById('kirim').style.display='none';
        }else{
            document.getElementById('kirim').style.display='block';
            document.getElementById('kirim').focus();
        }
    }

    function kirim_pendaftaran(){
        document.getElementById("kirim").style.display="none";
    }
</script>

<?php include('_sys_footer.php');?>