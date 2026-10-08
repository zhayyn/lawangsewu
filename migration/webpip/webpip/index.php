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
  <div class="w3-cell-row"  style="padding-top: 100px">
    <!--<div class="w3-container w3-cell w3-cell-middle">-->
      <!-- <img src="assets/images/banner_depan.png" alt="boat" style="width:100%;"> -->
    <!--</div>-->
  </div>

  <!-- Tentang -->
  <div class="w3-container w3-padding-16 w3-center" id="tentang">
    <div class="jumbotron">
      <h1>LapisLegit</h1>
      <p>(layanan Pemberitahuan Isi Putusan melalui Website)<br>Pengadilan Agama Semarang Kelas I-A</p>
      
    </div>
    <div class="container w3-center">
      <button type="button" onclick="document.getElementById('id01').style.display='block'" class="btn btn-success btn-sm">Login</button>
    </div>
  </div>

<div id="id01" class="modal">
  
  <form class="modal-content animate" action="_kunci.php" method="post">
    <div class="imgcontainer">
      <span onclick="document.getElementById('id01').style.display='none'" class="close" title="Close Modal">&times;</span>
      <!-- <img src="img_avatar2.png" alt="Avatar" class="avatar"> -->
    </div>

    <div class="container">
      <label for="uname"><b>Username</b></label>
      <input type="text" placeholder="Enter Username" name="uname" required>

      <label for="psw"><b>Password</b></label>
      <input type="password" placeholder="Enter Password" name="psw" required>
        
      <button type="submit">Login</button>
      <label>
        <input type="checkbox" name="remember"> Ingat saya
      </label>
    </div>

    <div class="container" style="background-color:#f1f1f1">
      <button type="button" onclick="document.getElementById('id01').style.display='none'" class="cancelbtn">Cancel</button>
      <span class="psw">Forgot <a href="#">password?</a></span>
    </div>
  </form>
</div>

<script>
  // Get the modal
  var modal = document.getElementById('id01');

  // When the user clicks anywhere outside of the modal, close it
  window.onclick = function(event) {
      if (event.target == modal) {
          modal.style.display = "none";
      }
  }
</script>

<?php
    include("_sys_footer.php");
?>

<script src="assets/plugin/jquery/jquery.js"></script>
<script src="assets/plugin/jquery-ui/jquery-ui.min.js"></script>
<script src="assets/plugin/jquery-ui/datepicker-id.js"></script>
<!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script> -->

  
