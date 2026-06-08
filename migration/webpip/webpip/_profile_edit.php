<?php
    if(!isset($_SESSION)){session_start();}
    if(isset($_SESSION['permohonanid'])){
        $_SESSION['permohonanid']=$_SESSION['permohonanid'];
    }else{
        $_SESSION['permohonanid']="";
    }
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(0);
    
    include('_sys_config.php');
    include('_sys_header_admin.php');
    include('_menu_login.php');
?>
<body id="profile_edit">
      <div class="w3-cell-row"  style="padding-top: 90px">
      </div>    
    <div class="w3-container w3-padding-16 w3-center" id="edit_profile">
      <h1>Profil Data</h1>
      <div>
        <?php
          $sql_data="SELECT * FROM `sys_users` where `userid`=".$_SESSION["sg_userid"].";";
          $db=new Tampil_sekunder();
          $arrayData = $db->tampil_data_sekunder($sql_data);
          if (count($arrayData)){
              foreach ($arrayData as $data){          
        ?>
        <table>
            <thead>
                <tr>
                    <th colspan="2">Profil Data</th>
                </tr>
            </thead>
        <tbody>
          <tr>
            <td align="left">Username</td>
            <td><b><input type="text" id="fname" name="fname" value="<?php echo $_SESSION['sg_username'];?>" disabled></b></td>
          </tr>
          <tr>
            <td align="left">Nama Lengkap</td>
            <td><b><input type="text" id="fname" name="fname" value="<?php echo $data["fullname"];?>"></b></td>
          </tr>
          <tr>
            <td align="left">Email Aktif</td>
            <td><b><input type="email" id="fmail" name="fmail" value="<?php echo $data["email"];?>"></b></td>
          </tr>          
          <tr>
            <td align="left">Password</td>
            <td><b><input type="password" id="fpsw" name="fpsw" placeholder="●●●●●●"></b></td>
          </tr>
          <tr>
            <td align="left">Re-Type Password</td>
            <td align="left"><b><input type="password" id="fpsw2" name="fpsw2" placeholder="●●●●●●"></b>
              <input type="checkbox" onclick="show_password()"> Perlihatkan Password
            </td>
          </tr>

        </tbody>
        </table>
        <?php
          }
        }
        ?>
      </div>
      <footer class="w3-container ">
        <p class="w3-center">          
          <button id="btn_simpan_validasi" class="w3-btn w3-teal w3-round" onclick="validate_input(<?php echo $_SESSION['sg_userid']?>)">Simpan</button>
          <button class="w3-btn w3-gray w3-round" onclick="history.back()">Kembali</button>
        </p>
      </footer>
    </div>
      


</body>

<script type="text/javascript">

  function show_password() {
    var x = document.getElementById("fpsw");
    var y = document.getElementById("fpsw2");
    if (x.type === "password") {
      x.type = "text";
      y.type = "text";
    } else {
      x.type = "password";
      y.type = "password";
    }
  }

  function validate_input(id_s5){
     var p1 = document.getElementById("fpsw").value;
     var p2 = document.getElementById("fpsw2").value;
     if(p1 == p2) {

      // if(jml_err == 0){        
        //UPDATE MT_PERMOHONAN STTAUS VALIDASI
        var xhr = new XMLHttpRequest();
        var url='./_operasi_data.php';
        xhr.open("POST", url, true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.onloadend = function() {
            if(xhr.status == 404) 
                alert(url + ' replied 404');
        }     
        xhr.onreadystatechange = function(){
            if(xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200){
              
              alert('Data berhasil disimpan');
              location.reload();
              exit();
            }
        }
        xhr.send( 
            "&aksi=edit_profile"+
            "&tabel=sys_users"+
            "&userid="+id_s5+
            "&password="+encodeURIComponent(fpsw2.value)+
            "&email="+encodeURIComponent(fmail.value)
        );

     }
      else {
         alert("password tidak sama");
         document.getElementById("fpsw").focus();
         
     } 

  }

    // var pacarbaru_id = 0;
    
  function edit_profile(id_s5){



    var jml_err = 0;
   
    if(jml_err == 0){        
      //UPDATE MT_PERMOHONAN STTAUS VALIDASI
      var xhr = new XMLHttpRequest();
      var url='./_operasi_data.php';
      xhr.open("POST", url, true);
      xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
      xhr.onloadend = function() {
          if(xhr.status == 404) 
              alert(url + ' replied 404');
      }     
      xhr.onreadystatechange = function(){
          if(xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200){
            
            alert('Data berhasil disimpan');
            location.reload();
            exit();
          }
      }
      xhr.send( 
          "&aksi=edit_profile"+
          "&tabel=sys_users"+
          "&userid="+id_s5+
          "&email="+encodeURIComponent(fmail.value)
      );

      // alert("Data "+id_s5+" berhasil disimpan");
      // history.back();
    } else {
      alert("Tolong lengkapi isian");
      // history.back();
      exit();
    }    

  }      
</script>


<?php
    include('_sys_footer.php');
?>
