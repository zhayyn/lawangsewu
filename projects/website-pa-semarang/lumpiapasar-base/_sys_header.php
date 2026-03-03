<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
include('_sys_config.php');?>
<!DOCTYPE html>
<html lang="id">
<title>LUMPIA-PASAR <?php echo ucwords(strtolower($namapa))?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="googlebot" content="noindex" />
<link rel="stylesheet" href="assets/css/w3-4.css">
<link rel="stylesheet" href="assets/css/w3-theme-green.css">
<link rel="stylesheet" href="assets/css/costum.css"> 
<link rel="stylesheet" href="assets/plugin/font-awesome-4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" type="text/css" href="assets/jquery-ui/jquery-ui.min.css">

<style type="text/css">
    body  {
    /*background-image: url("assets/images/steel.gif");*/
    /*background-color: #cccccc;*/
    font-family: Roboto, sans-serif;
    padding-bottom: 50px; /* Spacer untuk bottom bar */
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
    .ui-autocomplete {
        z-index: 9999999;
    }
    /* Bottom Bar Fixed */
    .w3-bottom {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 9999;
    }

</style>
<body>
    <!-- Top Bar Fixed - Tidak bisa digeser -->
    <div class="w3-top w3-bar w3-card w3-black" style="z-index: 9999; position: fixed; top: 0; left: 0; right: 0;">
        <div style="height: 8px; margin-top: 32px;"></div>
    </div>
    <!-- Spacer untuk konten agar tidak tertutup top bar -->
    <div style="height: 32px; margin-top: 32px;"></div>