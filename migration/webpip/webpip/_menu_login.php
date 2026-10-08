    <!-- Navbar -->
    <div class="w3-top">
        <div class="w3-bar w3-green w3-left-align" style="height:auto; padding:0px; margin:0px;">
            <div class="w3-container w3-cell" style="padding: 2px 2px 2px 2px;">
                <a href='https:\\pa-semarang.go.id' target='blank'><img src="profile.png" class="responsive" alt="profile" style="width: 64px"></a>
            </div>
            <div class="w3-container w3-cell" style="padding: 2px 2px 2px 2px; font-size:80%">
                <p></p><b>Pengadilan Agama Semarang Kelas I-A</b><br>
                <i>Jl. Urip Sumoharjo No.5 Semarang Jawa Tengah (50244)</i><br>
                Website: <a href='https:\\pa-semarang.go.id' target='blank'><u>www.pa-semarang.go.id</u></a> Telp: (024) 7606741
            </div>    
        </div>  
        <div class="w3-bar w3-white w3-left-align w3-card">
            <a class="w3-bar-item w3-button w3-hide-medium w3-hide-large w3-right w3-hover-green w3-theme-d2" href="javascript:void(0);" onclick="openNav()"><i class="fa fa-bars"></i></a>
            <a href="#"         class="w3-bar-item w3-button w3-green"><i class="fa fa-home w3-margin-right"></i>Beranda</a>
            <a href="#" onclick="history.back()" class="w3-bar-item w3-button w3-left"><i class="fa fa-arrow-left w3-margin-right"></i>Kembali</a>
            <a href="_logout.php" class="w3-bar-item w3-button w3-right"><i class="fa fa-user-circle-o w3-margin-right"></i>Logout</a>
        </div>
        <!-- Navbar on small screens -->
        <div id="navDemo" class="w3-bar-block w3-green w3-hide w3-hide-large w3-hide-medium">
            <a href="index#tentang" class="w3-bar-item w3-button"><i class="fa fa-question-circle w3-margin-right"></i>Tentang</a>
            <a href="permohonan" class="w3-bar-item w3-button"><i class="fa fa-pencil-square w3-margin-right"></i>Permohonan</a>
            <a href="jadwal" class="w3-bar-item w3-button"><i class="fa fa-calendar w3-margin-right"></i>Jadwal</a>
            <a href="_profile_edit.php" class="w3-bar-item w3-button"><i class="fa fa-phone-square w3-margin-right"></i>Edit Profile</a>
            <a href="_logout.php" class="w3-bar-item w3-button w3-right"><i class="fa fa-user-circle-o w3-margin-right"></i>Logout</a>
        </div>   
    </div>
    <div class="w3-container" style="padding-top: 16px;"> </div>

<script>
    function openNav(){
      var x = document.getElementById("navDemo");
      if (x.className.indexOf("w3-show") == -1) {
        x.className += " w3-show";
      } else { 
        x.className = x.className.replace(" w3-show", "");
      }
    }
</script>
