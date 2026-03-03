<?php 
if(!isset($_SESSION)){session_start();} 
	include "_fungsi.php";
date_default_timezone_set("Asia/Jakarta"); 
 
 if(isset($_SESSION['s14p_user_status']) && isset($_SESSION['s14p_user_status']) != "" && isset($_SESSION['s14p_username']) && isset($_SESSION['s14p_username']) != "" && isset($_SESSION['s14p_user_id']) && isset($_SESSION['s14p_user_id']) != "" ) 
	{ 
		return true;
	}else
	{ 
		lempar('index');
		exit();
	}

$sekarang=date("Y-m-d H:i:S");
?>  