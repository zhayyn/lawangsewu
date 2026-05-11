<?php
    include('_fungsi.php');
    // include('_sys_header_admin.php');
    // include("_menu.php");    

    if(!isset($_SESSION)){session_start();}     
    if ($_REQUEST["aksi"] == "keluar"){
        session_unset();
        session_destroy();
        lempar("index");
    }
?>
<!DOCTYPE html>
<html>
<head>
  <title>SITIGAOL 2.0</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="assets/css/w3-4.css">
  <link rel="stylesheet" href="assets/css/w3-theme-green.css">
  <link rel="stylesheet" href="assets/css/costum.css">
  <link rel="stylesheet" href="assets/plugin/font-awesome-4.7.0/css/font-awesome.min.css">
  <link rel="stylesheet" type="text/css" href="assets/jquery-ui/jquery-ui.min.css">
  <link rel="icon" type="image/png" href="profile.png">
    
   
  <!--</head> <body>-->
    <!--<div class="loading" id="loader">Loading&#8230;</div>-->

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lobster&effect=shadow-multiple">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Allerta+Stencil">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Sofia">

  <style type="text/css">
    body  {
      /*background-image: url("assets/images/steel.gif");*/
      /*background-color: #cccccc;*/
      font-family: Roboto, sans-serif;
    }  
    h1, h2, h3, h4, h5, h6  {
      font-family: Roboto, sans-serif;
    }  
    .w3-lobster {
      font-family: "Lobster", Sans-serif;  
    }
    .w3-allerta {
      /*font-family: "Allerta Stencil", Sans-serif;*/
      font-family: Roboto, sans-serif;
    }  
    .w3-sofia {
      font-family: Sofia, sans-serif;
    }   


  /* Full-width input fields */
  input[type=text], input[type=password] {
    width: 100%;
    padding: 12px 20px;
    margin: 8px 0;
    display: inline-block;
    border: 1px solid #ccc;
    box-sizing: border-box;
  }

  /* Set a style for all buttons */
  button {
    background-color: #04AA6D;
    color: white;
    padding: 14px 20px;
    margin: 8px 0;
    border: none;
    cursor: pointer;
    width: 100%;
  }

  button:hover {
    opacity: 0.8;
  }

  /* Extra styles for the cancel button */
  .cancelbtn {
    width: auto;
    padding: 10px 18px;
    background-color: #f44336;
  }

  /* Center the image and position the close button */
  .imgcontainer {
    text-align: center;
    margin: 24px 0 12px 0;
    position: relative;
  }

  img.avatar {
    width: 40%;
    border-radius: 50%;
  }

  .container {
    padding: 16px;
  }

  span.psw {
    float: right;
    padding-top: 16px;
  }

  /* The Modal (background) */
  .modal {
    display: none; /* Hidden by default */
    position: fixed; /* Stay in place */
    z-index: 1; /* Sit on top */
    left: 0;
    top: 0;
    width: 100%; /* Full width */
    height: 100%; /* Full height */
    overflow: auto; /* Enable scroll if needed */
    background-color: rgb(0,0,0); /* Fallback color */
    background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
    padding-top: 0;
  }

  /* Modal Content/Box */
  .modal-content {
    background-color: #fefefe;
    margin: 5% auto 15% auto; /* 5% from the top, 15% from the bottom and centered */
    border: 1px solid #888;
    width: 80%; /* Could be more or less, depending on screen size */
  }

  /* The Close Button (x) */
  .close {
    position: absolute;
    right: 25px;
    top: 0;
    color: #000;
    font-size: 35px;
    font-weight: bold;
  }

  .close:hover,
  .close:focus {
    color: red;
    cursor: pointer;
  }

  /* Add Zoom Animation */
  .animate {
    -webkit-animation: animatezoom 0.6s;
    animation: animatezoom 0.6s
  }

  @-webkit-keyframes animatezoom {
    from {-webkit-transform: scale(0)} 
    to {-webkit-transform: scale(1)}
  }
    
  @keyframes animatezoom {
    from {transform: scale(0)} 
    to {transform: scale(1)}
  }

  /* Change styles for span and cancel button on extra small screens */
  @media screen and (max-width: 300px) {
    span.psw {
       display: block;
       float: none;
    }
    .cancelbtn {
       width: 100%;
    }
  }
</style>  
</head>
<body>
<?php
    
    function arr2md5($arrinput){
        $hasil='';
        foreach($arrinput as $val){
            if($hasil==''){
                $hasil=md5($val);
            } else {
                $code=md5($val);
                for($hit=0;$hit<min(array(strlen($code),strlen($hasil)));$hit++){
                    $hasil[$hit]=chr(ord($hasil[$hit]) ^ ord($code[$hit]));
                }
            }
        }
        return(md5($hasil));
    }
    
    function getPassword($pase){ 
        $pass = arr2md5($pase);
        return $pass;
    }
    
    echo '<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">';
    echo '<body><div class="w3-cell-row" style="padding-top: 120px"><div class="w3-container w3-cell w3-cell-middle" align="center">';


    if( isset($_POST['uname']) && 
        isset($_POST['uname']) != "" && 
        isset($_POST['psw']) && 
        isset($_POST['psw']) != ""){
            
        include('_sys_koneksi.php');
        $nm_table="sys_users";
        $username = antiInjections($_POST['uname']); 
        //echo $username ."<br>" ; 
        $password = antiInjections($_POST['psw']); 
        //$test=arr2md5(array($code_activation,$pase));
        //$sql_data="SELECT * FROM `sys_users` where `username`='".$username."'"; 
        $sql_data='CALL logid("'.$username.'")'; 
        //echo $sql_data;exit;
        
        $db=new Tampil();
        $arrayData = $db->tampil_data($sql_data);
        // JIKA DITEMUKAN USER
        if (count($arrayData)){
            foreach ($arrayData as $data){
                // $test=arr2md5(array($data["user_activation_key"],$password));
                $test = md5($password);
                // echo $sql_data."<br>".$data["username"]."<br>".$test." x ".$data["password"];

                //CEK PASSWORD, JIKA SAMA
                if($data["pass"]==$test){            
                    //USER SUKSES LOGIN                    
                    if($data["stat"]==1){
                        $_SESSION['sg_username'] = $data["username"];
                        $_SESSION['sg_nama'] = $data["nama"];
                        $_SESSION['sg_userid'] = $data["id_user"];
                        // $_SESSION['sg_fullname'] = $data["fullname"];
                        // $_SESSION['sg_pegawai_id'] = $data["pegawai_id"];
                        // $_SESSION['sg_atasan_id'] = $data["atasan_id"];
                        // $_SESSION['sg_nip'] = $data["nip"];



                        // $_SESSION['s14p_user_status'] = $data["user_status"];
                        // $_SESSION['s14p_user_lokasi'] = $data["user_lokasi"]; 
                        // if($data['id_user'] == 1){
                            lempar("home_admin.php");
                        // } else {
                            // lempar("home.php");
                        // }                        
                    }else {
                        echo "<div class='w3-panel w3-red'><h3>Maaf!</h3>";
                        echo "Akun Anda sudah diblokir!<br>Harap kontak Administrator<br><br>";
                        echo '</div><a href="index.php" class="w3-button w3-yellow">Kembali</a>';   
                    }
                //JIKA PASSWOR TIDAK COCOK
                }else{
                    if(isset($_COOKIE['login'])){
                        if($_COOKIE['login'] < 9999){
                            $attempts = $_COOKIE['login'] + 1;
                            setcookie('login', $attempts, time()+60*10); //set the cookie for 10 minutes with the number of attempts stored
                            echo "<div class='w3-panel w3-red'><h3>Maaf!</h3>";
                            echo "Password anda salah<br><br>";
                            echo '</div><a href="index.php" class="w3-button w3-yellow">Kembali</a>';
                        } else{
                            echo "<div class='w3-panel w3-red'><h3>Maaf!</h3>";
                            echo "Anda sudah mencoba 3x tunggu 10 menit lagi!<br><br>";
                            echo '</div><a href="index.php" class="w3-button w3-yellow">Kembali</a>';                            
                        }
                    } else{
                        setcookie('login', 1, time()+60*10); //set the cookie for 10 minutes with the initial value of 1
                    }
                    echo "<div class='w3-panel w3-red'><h3>Maaf!</h3>";
                    echo "Password anda salah<br><br>";
                    echo '</div><a href="index.php" class="w3-button w3-yellow">Kembali</a>';                  
                }
            }
        // JIKA USERNAME TIDAK DITEMUKAN
        }else{
            if(isset($_COOKIE['login'])){
                if($_COOKIE['login'] < 3){
                    $attempts = $_COOKIE['login'] + 1;
                    setcookie('login', $attempts, time()+60*10); //set the cookie for 10 minutes with the number of attempts stored
                    echo "Maaf user tidak ditemukan.<br>";
                    echo '<a href="index.php" class="w3-button w3-yellow">Kembali</a>';
                } else {
                    echo 'Maaf, anda sudah mencoba 3x tunggu 10 menit lagi!<br>';
                    echo '<a href="index.php" class="w3-button w3-yellow">Kembali</a>';
                }
            } else{
                setcookie('login', 1, time()+60*10); //set the cookie for 10 minutes with the initial value of 1
            }
            echo "<div class='w3-panel w3-red'><h3>Maaf!</h3>";
            echo "Username ".$_POST['uname']." tidak ditemukan.<br><br>";
            echo '</div><a href="index.php" class="w3-button w3-yellow">Kembali</a>';   
        }   
    } else{
        if(isset($_COOKIE['login'])){
            if($_COOKIE['login'] < 3){
                $attempts = $_COOKIE['login'] + 1;
                setcookie('login', $attempts, time()+60*10); //set the cookie for 10 minutes with the number of attempts stored
                echo "Maaf username password tidak boleh kosong";
            } else{
                echo 'Maaf, anda sudah mencoba 3x tunggu 10 menit lagi!<br><br>';
                echo '<a href="index.php" class="w3-button w3-yellow">Kembali</a>';
            }
        } else{
            setcookie('login', 1, time()+60*10); //set the cookie for 10 minutes with the initial value of 1
        }
        echo "<div class='w3-panel w3-red'><h3>Maaf!</h3>";
        echo "Harap lengkapi username dan passsword Anda<br><br>";
        echo '</div><a href="index.php" class="w3-button w3-yellow">Kembali</a>';
    }   
    echo   '</div></div>';
?>

<?php
    // include("_sys_footer.php");
?>