<!DOCTYPE html>

<?php
    // include('_sys_header_admin.php');
    include('_sys_koneksi.php');
    
    function tanggal_indo($tgl)
    {
        $hari = array ( 1 =>    'Senin',
        			'Selasa',
        			'Rabu',
        			'Kamis',
        			'Jumat',
        			'Sabtu',
        			'Minggu'
        		);
        $num = date('N', strtotime($tgl)); 
    	$bulan = array (1 =>   'Januari',
    				'Februari',
    				'Maret',
    				'April',
    				'Mei',
    				'Juni',
    				'Juli',
    				'Agustus',
    				'September',
    				'Oktober',
    				'November',
    				'Desember'
    			);
    	$split = explode('-', $tgl);
    	return $hari[$num].', '.$split[2] . ' ' . $bulan[ (int)$split[1] ] . ' ' . $split[0];
    }
?>


<html>
<head>
  <title>PIPWEB</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- <base href="http://192.168.88.10/sitigaol2"> -->
  <link rel="stylesheet" href="assets/css/w3-4.css">
  <link rel="stylesheet" href="assets/css/w3-theme-green.css">
  <link rel="stylesheet" href="assets/css/costum.css">
  <link rel="stylesheet" href="assets/plugin/font-awesome-4.7.0/css/font-awesome.min.css">
  <link rel="stylesheet" type="text/css" href="assets/jquery-ui/jquery-ui.min.css">
  <link rel="icon" type="image/png" href="profile.png">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lobster&effect=shadow-multiple">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Allerta+Stencil">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Sofia">

  <!--<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">-->
  <!--<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>-->
  
</head>    
<body>
        
<div class="container" style="position: absolute; width: 100%; left: 0;">
    <div class="btn-group" style="width:100%">
        <p><button type="button" onclick="location.reload();" class="btn btn-primary"><i class='fa fa-refresh'> Refresh</i></button>  Update terakhir <?php echo date("Y-m-d H:i:s");?></p>
    </div>
    <div>
        <font size="2" face="Arial" >
        <table id="tabledata" class="w3-table-all" width="100%">
            <thead>
                <tr><th colspan="6" style="vertical-align:middle; background-color: grey;">Daftar Pemberitahuan Isi Putusan Pengadilan Agama Semarang Kelas I-A</th></tr>
                <tr>
                    <th style="vertical-align:middle"><i>NO</i></th>
                    <th style="vertical-align:middle"><i>NOMOR PERKARA</i></th>  
                    <th style="vertical-align:middle"><i>PIHAK</i></th>
                    <th style="vertical-align:middle"><i>PROSES</i></th>
                </tr>
            </thead>
            <tbody>         
                <?php
                   $total=0;
                    $sql="
                        SELECT a.`id`, a.`perkara_id`, a.`nomor_perkara`, a.`jurusita_nama`, a.jabatan_jurusita, a.`nama_pihak`, a.`umur`, 
                        a.`pekerjaan`, a.`agama`, a.`link_file`, date(a.`tgl_upload`) tgl_upload, 
                        IF(LENGTH(TRIM(a.`alamat`)) > 0 OR a.`alamat` IS NOT NULL, a.`alamat`,'<tidak diketahui>') alamat,
                        date(a.tgl_putusan) tgl_putusan, a.jenis_perkara, DATEDIFF(date(a.`tgl_upload`), NOW())+1 AS lama_tayang
                        FROM tbl_pip a
                        WHERE a.aktif = 1
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
                                
                                echo "<td><b>".$nomor_perkara."</b><br><hr>".$jabatan_jurusita."<br><b>".$jurusita_nama."</b></td>";
                                echo "<td><b>".$nama_pihak."</b><br>
                                        Umur ".$umur." tahun, agama ".$agama.", pekerjaan ".$pekerjaan.", alamat dahulu ".$alamat." 
                                        <b>dan saat ini tidak diketahui alamat dan keberadaannya di seluruh wilayah Republik Indonesia</b><br><br></td>";  
                                echo "<td>Tgl.Upload: <br>".$tgl_upload."<br>";
                                echo "Terhitung hari sejak diumumkan:<br>";
                                echo $lama_tayang." hari<br>";
                                echo "<hr>";
                                echo "<b>Dokumen PIP</b><br>"; 
                                if(isset($link_file)){
                                  echo "<a title='dok_pip' href='DOC/".$link_file."' target='blank'>
                                      <button class='btn btn-primary rounded' style='color: black;'><i class='fa fa-paperclip'> Lihat</i></button></a><br>";
                                } else{
                                  echo '<p style="color:red"><i>[file masih kosong]</i></p>';
                                }
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
</body>
</html>