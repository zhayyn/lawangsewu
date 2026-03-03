<?php
if(!isset($_SESSION)){session_start();} 

 	include('_fungsi.php');
if (@$_REQUEST["aksi"] == "keluar") 
{
		  session_unset();
		  session_destroy();
		  lempar("index");
}
function arr2md5($arrinput){
   $hasil='';
    foreach($arrinput as $val){
        if($hasil==''){
            $hasil=md5($val);
        }
        else {
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
//$activation='1628788d34ecd0fc18004c282ea9e008';
//$code_activation='27bfa8507f9b56872772ea84f89cdfa6';
//$password='21c26236b1542a45b9d02faab032d0b1';
//$karakter='195803071988031003';
//$pase='195803071988031003';
//$password = antiInjections($_POST['password']); 
//echo arr2md5(array($code_activation,$password)); 

/*
if(isset($_COOKIE['login']))
{
	if($_COOKIE['login'] == 77)
	{
		echo "Stop";
		return false;
		exit(); 
	}		
}*/
//echo $_POST["pa_jakbar_username"];
//echo $_POST["pa_jakbar_password"];

if(isset($_POST['pa_jakbar_username']) && isset($_POST['pa_jakbar_username']) != "" && isset($_POST['pa_jakbar_password']) && isset($_POST['pa_jakbar_password']) != "")
{
	 
 	include('_sys_koneksi.php');
	$nm_table="panjar_users";
	$username = antiInjections($_POST['pa_jakbar_username']); 
	//echo $username ."<br>" ; 
	$password = antiInjections($_POST['pa_jakbar_password']); 
	//$test=arr2md5(array($code_activation,$pase));
	$sql_data="SELECT * FROM $nm_table where user_login='".$username."'"; 
	//echo $sql_data;exit;
	$db=new Tampil_sekunder();
     

	$arrayData = $db->tampil_data_sekunder($sql_data);
    if (count($arrayData)) 
    {
    	foreach ($arrayData as $data) 
		{
			$test=arr2md5(array($data["user_activation_key"],$password));
			//echo $test;exit;
			

			if($data["user_pass"]==$test)
			{
				
				$_SESSION['s14p_username'] = $data["user_login"];
				$_SESSION['s14p_user_email'] = $data["user_email"];
				$_SESSION['s14p_user_id'] = $data["ID"];
				$_SESSION['s14p_nama'] = $data["display_name"];
				$_SESSION['s14p_user_status'] = $data["user_status"];
				//echo "Berhasil";
                 lempar("admin_index");  
			}else
			{
				if(isset($_COOKIE['login'])){
					if($_COOKIE['login'] < 3){
						$attempts = $_COOKIE['login'] + 1;
						setcookie('login', $attempts, time()+60*10); //set the cookie for 10 minutes with the number of attempts stored
						echo "I'm sorry, but your username and password don't match. Please go back and enter the correct login details. You Can to try again.";
					} else{
						echo 'You\'ve had your 3 failed attempts at logging in and now are banned for 10 minutes. Try again later!';
					}
				} else {
					setcookie('login', 1, time()+60*10); //set the cookie for 10 minutes with the initial value of 1
				}
			}
		}
     
		 
		
        
	 
	}else
	{
		

		
		if(isset($_COOKIE['login']))
		{
			if($_COOKIE['login'] < 3)
			{
				$attempts = $_COOKIE['login'] + 1;
				setcookie('login', $attempts, time()+60*10); //set the cookie for 10 minutes with the number of attempts stored
				echo "I'm sorry, but your username and password don't match. Please go back and enter the correct login details. You can to try again.";
			} else
			{
				echo 'You\'ve had your 3 failed attempts at logging in and now are banned for 10 minutes. Try again later!';
			}
		} else 
		{
			setcookie('login', 1, time()+60*10); //set the cookie for 10 minutes with the initial value of 1
		}
		lempar('index');
	}	
}else
{
	if(isset($_COOKIE['login']))
		{
			if($_COOKIE['login'] < 3)
			{
				$attempts = $_COOKIE['login'] + 1;
				setcookie('login', $attempts, time()+60*10); //set the cookie for 10 minutes with the number of attempts stored
				echo "I'm sorry, but your username and password don't match. Please go back and enter the correct login details. You can to try again.";
			} else
			{
				echo 'You\'ve had your 3 failed attempts at logging in and now are banned for 10 minutes. Try again later!';
			}
		} else 
		{
			setcookie('login', 1, time()+60*10); //set the cookie for 10 minutes with the initial value of 1
		}
		lempar('index');
}	

//echo $karakter."<Br>";
//echo $test."<Br>";
//echo md5($pase);

//echo "<br>";

 
 

//$code_activation = md5(uniqid());
//echo $code_activation;

?>