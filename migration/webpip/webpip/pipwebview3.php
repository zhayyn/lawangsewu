<!DOCTYPE html>
<?php
    include('_sys_koneksi.php');
    function tanggal_indo($tgl) {
        $hari = [1 => 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
        $bulan = [1 => 'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $num = date('N', strtotime($tgl)); 
        $split = explode('-', $tgl);
        return $hari[$num].', '.$split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
    }
?>

<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>PIPWEB</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" href="profile.png">
  
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="assets/plugin/font-awesome-4.7.0/css/font-awesome.min.css">
<style>
    body{
        font-size: 0.8em;
    }
    #ghoib iframe {
        width: 100%;
        border: none;
        height: 100vh; /* mengikuti tinggi layar */
    }    
</style>
</head>    

<body class="bg-light text-dark card shadow">
    <!--<div class="container">-->
        <div class="container">
           


<!--<div class="tab-content">-->
<!--  <div class="tab-pane active" id="home" role="tabpanel" aria-labelledby="home-tab">...</div>-->
<!--  <div class="tab-pane" id="profile" role="tabpanel" aria-labelledby="profile-tab">...</div>-->
<!--</div>-->

           
            <!--<div id="pbt">-->
                <header class="bg-body-secondary text-dark text-center py-2 fixed-top card shadow" >
                    <div class="container">
                    <h3>DAFTAR PBT DAN RELAAS GHOIB</h3>

                    
                    </div>
                    <div class="row container px-4"><br>
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                          <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pbt-tab" data-bs-toggle="tab" data-bs-target="#pbt" type="button" role="tab" aria-controls="home" aria-selected="true">
                                <i class="fa fa-bullhorn"></i><strong> Pemberitahuan Isi Putusan</strong>
                            </button>
                          </li>
                          <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ghoib-tab" data-bs-toggle="tab" data-bs-target="#ghoib" type="button" role="tab" aria-controls="profile" aria-selected="false">
                                <i class="fa fa-file-text-o"></i><strong> Relaas Ghoib</strong>
                            </button>
                          </li>
                        </ul>                        
                    </div>
                </header>
                <div class="container" style="padding-top: 120px;">
                    <div class="tab-content">
                        <div class="container tab-pane active" role="tabpanel" id="pbt">
                            <div class="card">
                                <div class="card-body p-0">
                                    <button type="button" onclick="location.reload();" class="btn btn-primary btn-sm">
                                        <i class="fa fa-refresh"></i> <strong>Refresh</strong> | <small class="text-white">Update terakhir: <?php echo date("Y-m-d H:i:s");?></small>
                                    </button>                                    
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered table-hover mb-0">
                                            <thead class="table-secondary text-center">
                                                <tr>
                                                    <th>NO</th>
                                                    <!--<th>NOMOR PERKARA<br>Jurusita</th>-->
                                                    <th>DATA</th>
                                                    <th>PROSES</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
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
                                                $no = 0;
                                                if (count($arrayData)) { 
                                                    foreach ($arrayData as $data) { 
                                                        foreach($data as $key => $value) { $$key = $value; }
                                                        $no++;
                                                        echo "<tr id='$id'>";
                                                        echo "<td class='text-center'>$no</td>";
                                                        // echo "<td></td>";
                                                        echo "<td>
                                                                    <div class='container-fluid'>
                                                                        <!-- Baris pertama: Nomor Perkara -->
                                                                        <div id='col1' class='row mb-3'>
                                                                            <div class='col-12'>
                                                                                Nomor Perkara: <strong>$nomor_perkara</strong>
                                                                                <hr>
                                                                            </div>
                                                                        </div>
                                                                        <!-- Baris kedua: Icon dan Nama Pihak -->
                                                                        <div class='row mb-3'>
                                                                            <div id='col2' class='col-1 d-flex align-items-center justify-content-center'>
                                                                                <i class='fa fa-user-circle fa-2x text-secondary fs-1'></i>
                                                                            </div>
                                                                            <div id='col3' class='col-11 d-flex align-items-left'>
                                                                                <h3 class='mb-0'>$nama_pihak</h3>
                                                                            </div>
                                                                        </div>
                                                                        <!-- Baris ketiga: Informasi tambahan -->
                                                                        <div id='col4' class='row'>
                                                                            <div class='col-12'>
                                                                                Umur $umur tahun, agama $agama, pekerjaan $pekerjaan,<br>
                                                                                Alamat dahulu: $alamat<br>
                                                                                <strong class='text-danger'>Saat ini tidak diketahui alamat dan keberadaannya di seluruh wilayah Republik Indonesia.</strong>
                                                                                <hr>
                                                                                Jurusita/JSP: <strong>$jurusita_nama</strong>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                              </td>";
                                                        echo "<td>
                                                                <div><strong>Tgl. Upload:</strong><br>$tgl_upload</div>
                                                                <div>Terhitung hari sejak diumumkan: <br><strong>$lama_tayang hari</strong></div>
                                                                <hr>
                                                                <div><strong>Dokumen PIP</strong><br>";
                                                        if(isset($link_file)) {
                                                            echo "<a href='DOC/$link_file' target='_blank' class='btn btn-sm btn-outline-primary mt-1'>
                                                                    <i class='fa fa-paperclip'></i> Lihat
                                                                  </a>";
                                                        } else {
                                                            echo "<p class='text-danger'><i>[file masih kosong]</i></p>";
                                                        }
                                                        echo "</div></td>";
                                                        echo "</tr>";
                                                    }
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="container tab-pane" role="tabpanel" id="ghoib">
                            <iframe src="https://infoweb.pa-semarang.go.id/ghoib.php" width="100%" height="1200px"></iframe>
                        </div>
                    </div>                    
                </div>

                <footer class="bg-body-secondary text-dark text-center py-2 fixed-bottom  card shadow">
                    <small><strong>Pengadilan Agama Semarang Kelas IA</strong></small>
                    <!--<small><i class="fa fa-database"></i> <strong>Jumlah Data: <?php echo count($arrayData); ?> item</strong></small>-->
                </footer>
            <!--</div>-->

            
        </div>
    <!--</div>-->
<script>
  var firstTabEl = document.querySelector('#myTab li:last-child a')
  var firstTab = new bootstrap.Tab(firstTabEl)

  firstTab.show()
</script> 
    
</body>
</html>
