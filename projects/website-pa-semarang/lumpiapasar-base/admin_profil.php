<?php
include "_sys_admin_session.php";
include('_sys_header_admin.php');
include('_sys_koneksi.php');
$nama_halaman="profil";
?>
 
 
 
 
<div class="w3-container" id="isi">
 

 <span class="w3-center"><b>PROFIL</b><br>Untuk mengubah Nama Lengkap dan Nama User silahkan langsung Edit</span> 
   <table class="w3-table-all">
    
   <?php
   $total=0;
   $sql="SELECT * FROM panjar_users WHERE ID=".$_SESSION['s14p_user_id'];
    //   echo $sql;
    $db = new Tampil_sekunder(); 
    $arrayData = $db->tampil_data_sekunder($sql); 
    $no=0;
    if (count($arrayData)) 
    { 
     foreach ($arrayData as $data) 
     { 
      foreach($data as $key=>$value) {$$key=$value;}
      $no++;
      echo "<input type='hidden' value='".$ID."' id='ID'>";
      ?>
        <tr>
              <td>Nama Lengkap</td>
               <td contenteditable="" title="Klik dan ubah untuk mengubah data" onblur="Edit_Isi(this.innerHTML,'panjar_users','display_name','ID',<?php echo $ID?>,'teks')"><?php echo  $display_name?></td> 
        </tr> 
        <tr>
              <td>Nama User</td>
               <td contenteditable="" title="Klik dan ubah untuk mengubah data" onblur="Edit_Isi(this.innerHTML,'panjar_users','user_login','ID',<?php echo $ID?>,'teks')"><?php echo  $user_login?></td> 
        </tr> 
        <tr>
              <td>Email</td>
               <td contenteditable="" title="Klik dan ubah untuk mengubah data" onblur="Edit_Isi(this.innerHTML,'panjar_users','user_email','ID',<?php echo $ID?>,'teks')"><?php echo  $user_email?></td> 
        </tr> 
        <tr>
              <td>Website</td>
               <td contenteditable="" title="Klik dan ubah untuk mengubah data" onblur="Edit_Isi(this.innerHTML,'panjar_users','user_url','ID',<?php echo $ID?>,'teks')"><?php echo  $user_url?></td> 
        </tr> 
        <tr>
              <td>Kata Sandi</td>
              <td><span style="cursor:pointer" class="w3-red w3-button"  onclick="tampil_modal_profil()">Ubah Kata Sandi</span><input type="hidden" id="user_activation_key" value="<?php echo $user_activation_key?>"></td>
        </tr> 
     <?php 
     }
   }
   ?>
 </table>

  
</div>
<!---- MODAL  !-->
<div id="modal_profil" class="w3-modal" style="padding-top: 40px;">
     <div class="w3-modal-content"  style="width: 50%">
        <div class="w3-row w3-red">
            <span onclick="document.getElementById('modal_profil').style.display='none'" 
            class="w3-button w3-display-topright">&times;</span>
            
        </div>
        <br>
      <div class="w3-container" id="isi">
        <div class="row">
          <h5>Untuk mengubah kata sandi, silahkan isikan form isian di bawah ini </h5>
          <input type="password" autocomplete="off" class="w3-input w3-border" onkeypress="document.getElementById('kata_sandi_error').innerHTML=''" id="kata_sandi" placeholder="Silahkan isikan Kata Sandi Baru "><span class="w3-text-red" id="kata_sandi_error"></span><br>
           <input type="password" onkeypress="document.getElementById('kata_sandi_lagi_error').innerHTML=''" autocomplete="off" class="w3-input w3-border" placeholder="Silahkan isikan Kata Sandi Baru lagi" id="kata_sandi_lagi"><span class="w3-text-red" id="kata_sandi_lagi_error"></span>
          <br>
          <button class="w3-button w3-block w3-green w3-section w3-padding" onclick="ubah_sandi()">Ubah Kata Sandi</button>
          <br>  
        </div>
      </div>
     </div> 
</div>
<!--- MODAL !-->

<script src="assets/js/admin_profil.js" type="text/javascript"></script>
<?php
include("_sys_footer_admin.php");
?>