<?php
    include('_sys_header.php');
    foreach($_GET as $key=>$value) {$$key=$value;}
	if(isset($_GET['a'])){
	    $vara=$_GET['a'];
	}
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


    
    <div id="loader"></div>
    <div class="w3-top w3-row w3-card-2 w3-padding w3-center w3-white">
      <div class="w3-col w3-center" style="width:30px">
      </div>
      <div class="w3-rest w3-cell-middle"><span style="font-size: 18px"><i class="fa fa-certificate"></i> Pengambilan Produk</span></div>
    </div>
    
    <div id="awal" class="w3-container w3-center" style="padding-top: 60px; display: block;">
        <div class="w3-container w3-card">
            <img src="assets/images/white_list.png" style="width: 64px;">
            <p>Sebelum Anda melanjutkan, pastikan Anda telah menyiapkan data berupa bukti pembayaran PNBP</p>
        </div>
        <br>
        <button id="bt_cek_data" class="w3-btn w3-round-large w3-block w3-blue" onclick="cek_data(<?php echo $vara;?>)"><i class="fa fa-arrow-circle-right"></i> Lanjutkan</button>
        
    </div>
    
    <div id="akhir" class="w3-container w3-center" style="padding-top: 60px; display: none;">
        <div class="w3-container w3-card">
            <p>Harap dipastikan bahwa data dibawah ini benar data Anda. Kemudian upload bukti transfer pembayaran PNBP Anda</p>
            <form id="fuplaodbb" method="POST" enctype="multipart/form-data">
                <table class="w3-table" id="tbdm" name="tbdm" width="80%">
                    
                </table>
            </form>
    <div class="w3-container w3-center">
        <div class="loader w3-center" id="divmuter" style="display: none;"></div>    
    </div>            
        </div>
        
        <br>
        <button id="bt_kirim" class="w3-btn w3-round-large w3-block w3-green" ><span id="icosw" class="fa fa-paper-plane"></span> Kirimkan</button>;
        
    </div>
    
    <div id="bye" class="w3-container w3-center" style="padding-top: 60px; display: none;">
        <div class="w3-container w3-card">
            <img src="assets/images/security_policy.png" style="width: 64px;">
            <p><strong>Verifikasi Data OK</strong><br>Silahkan Anda datang langsung ke PTSP Pengadilan Agama Semarang untuk mengambil produk</p>
        </div>
    </div>

    <div id="kosong" class="w3-container w3-center" style="padding-top: 60px; display: none;">
        <div class="w3-container w3-card">
            <img src="assets/images/critical_zones.png" style="width: 64px;">
            <p><strong>Maaf</string>, Data Permohonan Anda tidak dapat kami temukan</p>
        </div>
    </div>

    
    
    
<script src="assets/plugin/jquery/jquery.js"></script>
<script src="assets/plugin/jquery-ui/jquery-ui.min.js"></script>
<script src="assets/plugin/jquery-ui/datepicker-id.js"></script>
<script type="text/javascript">

    // function uploadf(noid) {
    $("#bt_kirim").click(function(e) {
        document.getElementById('loader').style.display='block';
        e.preventDefault();
        
        
        
        var nid = <?php echo $vara;?>;
        var formData = new FormData();
        formData.append('file_bukti_bayar', document.getElementById('file_bukti_bayar').files[0]);
        formData.append('fbb_name', nid);
        formData.append('aksi', "upload_bb_escao");

        $.ajax({
            url: "https://escao.pa-semarang.go.id/upl66.php",
            type: 'post',
			data: formData,
			async: false,
			cache: false,
			contentType: false,
			enctype: 'multipart/form-data',
			processData: false,
            cache: false,
            beforeSend: function(){
                // document.getElementById('loader').style.display='block';
                $('#bt_kirim').html('<span id="icosw" class="fa fa-play"></span>  Mengirim...');
                $('#icosw').removeClass('fa fa-play').addClass('fa fa-spinner fa-spin');
                $('#bt_kirim').removeClass('btn-warning').addClass('btn-danger');              
            },            
            success: function(data){
                $('#bt_kirim').html('<span id="icos" class="fa fa-paper-plane"></span>  Kirim');
                $('#icos').removeClass('fa fa-spinner fa-spin').addClass('fa fa-paper-plane');
                $('#bt_kirim').removeClass('btn-danger').addClass('btn-primary');  
                
                document.getElementById('loader').style.display='none';
                document.getElementById('awal').style.display='none';
                document.getElementById('akhir').style.display='none';
                document.getElementById('bye').style.display='block';
            },
            error: function(d){
                /*console.log("error");*/
                document.getElementById('loader').style.display='none';
                $('#bt_kirim').html('<span id="icos" class="fa fa-paper-plane"></span>  Kirim');
                $('#icos').removeClass('fa fa-spinner fa-spin').addClass('fa fa-paper-plane');
                $('#bt_kirim').removeClass('btn-danger').addClass('btn-primary'); 
            }            

        });
    }    );
    
    function cek_data(noid){
        var nid = noid;
        $.ajax({
            url: "api_ngapak",
            type: 'post',
            dataType: "json",
            data: {
              aksi: btoa('cari_data_verif'),
              acid:btoa(nid)
            },
            cache: false,
            beforeSend: function(){
                $('#bt_cek_data').html('<span id="iconstopperempuan" class="fa fa-play"></span>  Proses...');
                $('#iconstopperempuan').removeClass('fa fa-play').addClass('fa fa-spinner fa-spin');
                $('#bt_cek_data').removeClass('btn-warning').addClass('btn-danger');
            },            
            success: function(data){
                var event_data = '';
                var event_data_kosong = '';
                var i = 0;
                var data_ada = 0;
                var sudah_upload = 0;

                if ($.trim(data) == '' ){
                	data_ada = 0;
                }else{
                	data_ada = 1;
                }
                
                $.each(data, function(index, value){
                    /*console.log(value);*/
                    event_data += '<tr><td>No.Perkara</td>';
                    event_data += '<td><b>'+value.nomor_perkara+'</b></td></tr>';
                    event_data += '<tr><td>Nama Pemohon</td>';
                    event_data += '<td><b>'+value.nama_pihak+'</b></td></tr>';                    
                    event_data += '<tr><td>No.WA</td>';
                    event_data += '<td><b>'+value.no_hp+'</b></td></tr>';
                    event_data += '<tr><td>Tgl.Permohonan</td>';
                    event_data += '<td><b>'+value.tgl_input+'</b></td></tr>';
                    event_data += '<tr><td>Tgl.Pengambilan</td>';
                    event_data += '<td><b>'+value.tgl_ambil+'</b></td></tr>';
                    
                    event_data += '<tr><td>Bukti Bayar</td><td></td></tr>';
                    event_data += '<tr><td colspan="2" align="left"><input class="w3-input" type="file" id="file_bukti_bayar" required></td></tr>';
                    event_data += '<tr><td colspan="2"></td></tr>';   
                    
                    i++;
                    if(value.bukti_bayar){
                    	sudah_upload = 1;
                    }                    
                });
                if(data_ada == 0){
	               // document.getElementById('divmuter').style.display='none';
	                document.getElementById('awal').style.display='none';
	                document.getElementById('akhir').style.display='none';
	                document.getElementById('bye').style.display='none';
	                document.getElementById('kosong').style.display='block';
                }else{
                	if(sudah_upload == 1){
	                    document.getElementById('tbdm').innerHTML="";
	                    $("#tbdm").append(event_data);  
		              //  document.getElementById('divmuter').style.display='none';
		                document.getElementById('awal').style.display='none';
		                document.getElementById('akhir').style.display='none';
		                document.getElementById('kosong').style.display='none';
		                document.getElementById('bye').style.display='block';	                
	            	}else{
	                    document.getElementById('tbdm').innerHTML="";
	                    $("#tbdm").append(event_data);  
		              //  document.getElementById('divmuter').style.display='none';
		                document.getElementById('awal').style.display='none';
		                document.getElementById('akhir').style.display='block';
		                document.getElementById('kosong').style.display='none';
		                document.getElementById('bye').style.display='none';	            		
	            	}
                }
                
                $('#bt_cek_data').html('<span id="iconstop" class="fa fa-arrow-circle-right"></span>  Refresh');
                $('#iconstop').removeClass('fa fa-spinner fa-spin').addClass('fa fa-arrow-circle-right');
                $('#bt_cek_data').removeClass('btn-danger').addClass('btn-primary');                
            },
            error: function(d){
                /*console.log("error");*/
                $('#bt_cek_data').html('<span id="iconstop" class="fa fa-arrow-circle-right"></span>  Refresh');
                $('#iconstop').removeClass('fa fa-spinner fa-spin').addClass('fa fa-arrow-circle-right');
                $('#bt_cek_data').removeClass('btn-danger').addClass('btn-primary'); 
            }            

        });
    }
</script>
    
<?php
    include('_sys_footer.php');
?>