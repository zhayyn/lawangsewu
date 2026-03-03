<?php
    include('_sys_header.php');
?> 

<?php

?> <html lang="en"> <head> <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"> <meta name="robots" content="noindex, nofollow"> <meta name="googlebot" content="noindex" /> <meta name="viewport" content="width=device-width, initial-scale=1"> <title><?php echo $namapa?> MOBILE</title> 
  <link rel="stylesheet" href="assets/css/w3.css">
  
 
</head> <body>
 <div class="w3-container">
    <center>
      <div class="w3-row  w3-card-8" style="max-width: 300px;">
        <div class="w3-container" id="isi">
          <img src="assets/images/user.jpg" alt="Avatar" style="width: 120px" class="w3-circle w3-padding">
            <div class="w3-section ">
              <div class="w3-panel w3-pale-green w3-border">
                <h4>Pengaturan</h4>
                <p>Silahkan isikan Nama User dan Kata Sandi</p>
              </div>

              <form action="_kunci" method="POST">
              <input class="w3-input w3-border w3-margin-bottom" type="text" placeholder="Nama User" name="pa_jakbar_username" id="pa_jakbar_username" required>
              <input class="w3-input w3-border" type="text" placeholder="Kata Sandi" name="pa_jakbar_password" id="pa_jakbar_password" required><br>
              <input type="checkbox" id="tampil_sandi" onchange="tampil_sandinya()" name="tampil_sandi" checked="true">Tampilkan Kata Sandi
              <br>
              <button class="w3-btn w3-block w3-green w3-section w3-padding" >Masuk</button>
              
              <a href="index" class="w3-btn w3-block w3-red w3-section w3-padding" >Batal</a>
              </form>
            </div>
        </div>
      </div>
    </center>
</div>
</body> </html>