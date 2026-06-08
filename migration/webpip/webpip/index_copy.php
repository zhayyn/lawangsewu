<?php
    if(!isset($_SESSION)){session_start();}
    if(isset($_SESSION['permohonanid'])){
        $_SESSION['permohonanid']=$_SESSION['permohonanid'];
    }else{
        $_SESSION['permohonanid']="";
    }
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    // error_reporting(E_ALL);
    error_reporting(0);
    include('_sys_config.php');
    include('_sys_header_admin.php');
    include("_menu.php");
?>
<body id="myPage">

<!-- Image tampil di beranda -->
<div class="w3-cell-row"  style="padding-top: 90px">
  <!--<div class="w3-container w3-cell w3-cell-middle">-->
    <!-- <img src="assets/images/banner_depan.png" alt="boat" style="width:100%;"> -->
  <!--</div>-->
</div>

<!-- Tentang -->
<div class="w3-container w3-padding-16 w3-center" id="tentang">
  <p align="Left">
  <h5><b><i>Data Saksi</i></b></h5>
  </p>
  <p>Input data Saksi
  </p>

  <!-- <div class="w3-container w3-padding-64 w3-center" id="menus"> -->
  <div class="w3-container w3-center">

    <div class="w3-half w3-margin-bottom w3-center">
      <ul class="w3-ul w3-border w3-hover-shadow">
        <li class="w3-theme-l5">
          <p class="w3-large">Input Data Saksi</p>
        </li>
        <li class="w3-padding-16"> 
        <a href="permohonan.php">
          <img src="assets/images/permohonan.png" alt="Boss" style="width:45%" class="w3-circle w3-hover-opacity">
        </a>          
          <p>
            Input data saksi            
          </p>
        </li>
      </ul>
    </div>
    <div class="w3-half w3-margin-bottom w3-center">
      <ul class="w3-ul w3-border w3-hover-shadow">
        <li class="w3-theme-l5">
          <p class="w3-large">Daftar Saksi</p>
        </li>
        <li class="w3-padding-16"> 
          <a href="#mulai" onclick="modal_input_noperkara()">
            <img src="assets/images/img_avatar.png" alt="Boss" style="width:45%" class="w3-circle w3-hover-opacity">
          </a>          
          <p>
            Lihat data saksi            
          </p>
        </li>
      </ul>
    </div>    
    <hr>
    <h1>Data Saksi Diinput Hari ini</h1>
    <table id="tabledata" class="table table-condensed">
      <thead>
        <tr>
          <th>No.Perkara</th>
          <th>Nama Saksi</th>
          <th>Urutan</th>
          <th>Nama Pihak</th>
        </tr>
      </thead>
      <tbody>  

    <?php
      $hostname = "localhost";
      $username = "assefio";
      $password = "284416";
      $database = "sipp"; 

      $Open = mysqli_connect($hostname,$username,$password);
      if (!$Open){
        die ("Koneksi ke Engine MySQL Gagal !<br>");
      }
      $Koneksi = mysqli_select_db($Open, $database);
      if (!$Koneksi){
        die ("Koneksi ke Database Gagal !");
      }

      $saksi = mysqli_query($Open, 
      "
        select b.nomor_perkara, a.nama as nama_saksi,
        a.urutan, 
        case 
        when a.saksi_pihak_ke = 1 then p1.nama
        when a.saksi_pihak_ke = 2 then p2.nama
        end as nama_pihak
        from perkara_pihak5 a
        left outer join perkara b on b.perkara_id = a.perkara_id
        left outer join perkara_pihak1 p1 on p1.perkara_id = a.perkara_id 
        left outer join perkara_pihak2 p2 on p2.perkara_id = a.perkara_id 
        where date(a.diinput_tanggal) = date(CURRENT_DATE()) 
        order by a.diinput_tanggal asc, a.urutan asc
      ");
      $totrec = mysqli_num_rows($saksi);
      $tot_data = 0;

          if ($totrec > 0){  
              // $totrec = mysqli_num_rows($ghoib);
            while($vsaksi=mysqli_fetch_array($saksi)){ 
              $tot_data = $tot_data + 1;                        
              ?>  
                <tr>
                  <td><b><?php echo $vsaksi['nomor_perkara'];?></b></td>                 
                  <td><b><?php echo $vsaksi['nama_saksi'];?></b></td>
                  <td><?php echo $vsaksi['urutan'];?></td>
                  <td><?php echo $vsaksi['nama_pihak'];?></td>
                </tr>
              <?php
            }
            ?>
                <tr>
                  <td><b><i><?php echo 'TOTAL '."$tot_data"." Data";?></i></b></td>
                  <td></td>
                  <td></td>
                  <td><b></b></td>
                  <td><b></b></td>
                  <td><b></b></td>
                  <td><b></b></td>
                </tr>                        
            <?php
          }

    ?>
        </tbody></table>
  </div>
</div>






<hr>
<div>
  <center>
    <!-- Copyright©️ Pengadilan Agama Semarang 2022 -->
  </center>
</div>   
<!-- Contact Container -->
<!-- <div class="w3-container w3-padding-64 w3-theme-l5" id="contact">
  <div class="w3-row">
    <div class="w3-col m5">
      <div class="w3-padding-16"><span class="w3-xlarge w3-border-teal w3-bottombar">Kontak Kami</span></div>
      <h3>Pengadilan Agama Semarang Kelas I-A</h3>
       
      <p><i class="fa fa-map-marker w3-text-teal w3-xlarge"></i>&nbsp;&nbsp;Jl. Urip Sumoharjo No. 5 Semarang - 50152  Kota Semarang - Jawa Tengah</p>
      <p><i class="fa fa-phone w3-text-teal w3-xlarge"> </i>&nbsp;&nbsp;Telp: (024) 7606741</p>
      <p><i class="fa fa-fax-o w3-text-teal w3-xlarge"></i>&nbsp;&nbsp;Fax: (024) 7622887</p>
      <p><i class="fa fa-envelope-o w3-text-teal w3-xlarge"></i>&nbsp;&nbsp;Email : pasmg6@gmail.com</p> 
        <p></p>
     
    </div>
    <div class="w3-col m7">
      <form class="w3-container w3-card-4 w3-padding-16 w3-white" action="/action_page.php" target="_blank">
        <div class="w3-section">      
          <label>Nama</label>
          <input class="w3-input" type="text" name="Name" required>
        </div>
        <div class="w3-section">      
          <label>Email</label>
          <input class="w3-input" type="text" name="Email" required>
        </div>
        <div class="w3-section">      
          <label>Pesan</label>
          <input class="w3-input" type="text" name="Message" required>
        </div>  
        <button type="submit" class="w3-button w3-right w3-theme">Kirim</button>
      </form>
    </div>
  </div>
</div> -->
<!-- <hr>  -->

<div class="w3-container" id="daftar">
        <!-- MODAL -->
        <div id="modal_daftar_saksi" class="w3-modal" style="padding-top: 0px;">
            <div class="w3-modal-content" style="max-width:800px min ">
                <div class="w3-container w3-left">
                    <a href="#"><button onclick="tutup_modal()" class="w3-red w3-center w3-btn w3-ripple">Tutup</button></a>
                </div>
                <hr>
                            
                <div class="w3-container" id="modal_pilih_noperk"  style="display: block">                    
                    <div class="w3-row">
                        <center>
                        <h2><b>PILIH NOMOR PERKARA</b></h2>
                        <p>Silahkan isikan Angka Nomor Perkara,<br>kemudian pilih nomor perkara yang sesuai</p>
                        
                        <div class="w3-row" id="input_noperk">              
                            <input id="daftar_saksi_perkara"  class="w3-input  w3-border" style="max-width: 250px;" placeholder="Angka Nomor Perkara 5555"> 
                            <input type="hidden" id="perkara_id" name="perkara_id">
                        </div>  
                        <br><br><br><br>
                      </center>                       
                    </div>
                    <br>                
                </div>   

                <div class="w3-container" id="daftar_saksi"  style="display: none">
                    <center>
                        <h1>Daftar Saksi</h1> 
                        Perkara Nomor
                        <h4><b><span id="noperkara" name="noperkara"></span></b></h4>
                        <div class="w3-row" id="pilihan_saksi">
                          <table id="myTable" border="1"  style="margin-left: auto; margin-right: auto;">
                              <tr>                                  
                                  <th>Nama</th>
                                  <th>Saksi Pihak Ke</th>
                                  <th>Urutan</th>
                                  <th>Alamat</th>                                  
                              </tr>
                          </table>   
                        </div>
                        <hr>
<!--                         <div> <center>
                            <a href='#' onclick='tutup_modal()' class="w3-red w3-center w3-btn w3-ripple">Selesai</a>
                            </center>
                        </div>   --> 
                    <br>                        
                    </center>
                    <br><br><br><br>
                </div>  

            </div>
        </div>
</div>

   
<!-- Footer -->
<!--<footer class="w3-container w3-padding-32 w3-theme-d1 w3-center">-->
  
<!--  <p>Pengadilan Agama Semarang @2021</p>-->

<!--  <div style="position:relative;bottom:100px;z-index:1;" class="w3-tooltip w3-right">-->
<!--    <span class="w3-text w3-padding w3-teal w3-hide-small">Go To Top</span>   -->
<!--    <a class="w3-button w3-red" href="#myPage"><span class="w3-xlarge">-->
<!--      <i class="fa fa-chevron-circle-up"></i></span></a>-->
<!--    </div>-->
<!--  </footer>-->

<?php
    include("_sys_footer.php");
?>

<script src="assets/plugin/jquery/jquery.js"></script>
<script src="assets/plugin/jquery-ui/jquery-ui.min.js"></script>
<script src="assets/plugin/jquery-ui/datepicker-id.js"></script>
<!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script> -->

<script type="text/javascript">
    $(document).ready(
    function(){
      $( "#daftar_saksi_perkara" ).autocomplete({
        source: function( request, response ) {
          // Fetch data
          $.ajax({
            url: "__apipa.php",
            type: 'post',
            dataType: "json",
            data: {
              noperk: btoa(request.term),aksi:btoa('cari_daftar_saksi'),jenis:btoa('daftar_saksi')
            },
            success: function( data) {
              

              if(data.length > 0){
                for (var i=0; i<data.length; i++) {
                    var row = $(
                        '<tr>'+
                        '<td>'+ data[i].nama+'</td>' + 
                        // '<td>'+ data[i].saksi_pihak_ke+'</td>' + 
                        // '<td>'+ data[i].urutan+'</td>' +
                        '<td>'+ data[i].alamat+'</td></tr>');
                    $('#myTable').append(row);
                } 
              }
     
response( data );

            }
          });
          },
          select: function (event, ui) {
            $('#nomor_perkara').val(ui.item.value.trim());
            $('#perkara_id').val(ui.item.perkara_id);


            var jml = document.getElementById("myTable").rows.length;
            if (jml > 0){
              var table = document.getElementById("myTable");
              for (var i = 0, row; row = table.rows[i]; i++) {
                 //iterate through rows
                 //rows would be accessed using the "row" variable assigned in the for loop
                  var x = document.getElementById("myTable").rows[i].cells;
                  if (x[0].innerHTML != ui.item.value){
                    document.getElementById("myTable").deleteRow(i);
                    i=0;
                  };
              }
            }

            document.getElementById("daftar_saksi").style.display="block";
            document.getElementById("modal_pilih_noperk").style.display="none";
            document.getElementById("noperkara").innerHTML=ui.item.value.trim();
            document.getElementById('daftar_saksi_perkara').value ='';
            return false;
          }
      });
  });
</script>  

     

<script type="text/javascript">

    function modal_input_noperkara()
    {
        document.getElementById('modal_daftar_saksi').style.display="block";
        document.getElementById('modal_pilih_noperk').style.display="block";
        document.getElementById('daftar_saksi_perkara').setfocus;

    }

    function lihat_daftar_saksi()
    {
        document.getElementById('modal_daftar_saksi').style.display="block";
        document.getElementById('modal_pilih_noperk').style.display="none";
        document.getElementById('daftar_saksi').style.display="block";
    }    

    function tutup_modal()
    {
        
        document.getElementById('modal_daftar_saksi').style.display="none";
        document.getElementById('modal_pilih_noperk').style.display="none";
        document.getElementById('daftar_saksi').style.display="none";
    }        
</script>    
