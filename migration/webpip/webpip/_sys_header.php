<?php
	if(!isset($_SESSION)){session_start();}
	if(isset($_SESSION['permohonanid']))
	{
		$_SESSION['permohonanid']=$_SESSION['permohonanid'];
	}else
	{
		$_SESSION['permohonanid']="";
	}
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	include('_sys_config.php');
?>



<!DOCTYPE html>
<html lang="id">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>SITIGAOL 2.0 <?php echo ucwords(strtolower($namapa))?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<meta name="googlebot" content="noindex" />
	<!-- <base href="http://192.168.88.10/sitigaol2/"> -->
	<link rel="stylesheet" href="assets/css/w3-4.css">
	<link rel="stylesheet" href="assets/css/w3-theme-green.css">
	<link rel="stylesheet" href="assets/css/costum.css">
	<link rel="stylesheet" href="assets/plugin/font-awesome-4.7.0/css/font-awesome.min.css">	
	
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>	
	<style>
	* {
	  box-sizing: border-box;
	}
	.menu {
	  float: left;
	  width: 20%;
	}
	.menuitem {
	  padding: 8px;
	  margin-top: 7px;
	  border-bottom: 1px solid #f1f1f1;
	}
	.main {
	  float: left;
	  width: 60%;
	  padding: 0 20px;
	  overflow: hidden;
	}
	.right {
	  background-color: lightblue;
	  float: left;
	  width: 20%;
	  padding: 10px 15px;
	  margin-top: 7px;
	}

	@media only screen and (max-width:800px) {
	  /* For tablets: */
	  .main {
	    width: 80%;
	    padding: 0;
	  }
	  .right {
	    width: 100%;
	  }
	}
	@media only screen and (max-width:500px) {
	  /* For mobile phones: */
	  .menu, .main, .right {
	    width: 100%;
	  }
	}
	</style>	
</head>