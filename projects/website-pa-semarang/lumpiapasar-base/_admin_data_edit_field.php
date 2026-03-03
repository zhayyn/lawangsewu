<?php
include("_sys_koneksi.php");
foreach($_POST as $key=>$value) {$$key=$value;}
if($aksi=='edit_field'){ 
      $data_edit=array(
                $field            => $isi
              ); 
          var_dump($data_edit);
          $db_edit = new Edit();
          $sisipkan = $db_edit->edit_data($tabel, $kunci, $id, $data_edit) ;
}
?>  