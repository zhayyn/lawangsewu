<?php
include('_sys_header.php');
$menu_aktif="DataSidang Online ";
?>  
<div class="loading" id="loader">Loading&#8230;</div>
<!-- Header --> 
<div class="w3-row">
<?php
    $sql="SELECT *, DATE_FORMAT(left(diinput_tanggal,10),'%d/%m/%Y') AS tgl FROM antrian_sidang_data GROUP BY nomor_perkara, left(diinput_tanggal,10) ORDER BY diinput_tanggal DESC";
    $db = new Tampil_sekunder(); 
    $arrayData = $db->tampil_data_sekunder($sql);
    $no=0;
    $isi="<table class='w3-table-all' id='myTable'><thead>
    
    <tr>
      <th>No</th>
      <th>Tanggal</th>
      <th>Nomor Perkara</th>
      <th>Antrian</th>
    </tr></thead><tbody>";
    if (count($arrayData)){
    	foreach ($arrayData as $data){
    	    $no++;
            foreach($data as $key=>$value) {$$key=$value;}
	        $isi.="
    <tr>
      <td>$no</td>
      <td>$tgl</td>
      <td>$nomor_perkara</td>
      <td>$nomor_antrian</td>
    </tr>";
    	}
    }
    $isi.="</tbody></table>";
    echo $isi;
?>
<link href="https://unpkg.com/vanilla-datatables@latest/dist/vanilla-dataTables.min.css" rel="stylesheet" type="text/css">
<script src="https://unpkg.com/vanilla-datatables@latest/dist/vanilla-dataTables.min.js" type="text/javascript"></script>
<script>
var dataTable = new DataTable("#myTable", {
	perPageSelect: [ 50, 100, 200, 300, 400, 500, 1000],
	perPage: 50});
</script>
<script>
  document.getElementById("loader").style.display="none";
  function goBack(){
    window.history.back()
  }
</script>
</body> 
</html> 