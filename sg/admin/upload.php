<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'upload.php');

global $templates, $mybb, $db;

require_once "./../../global.php";
require_once "./../functions/sg_functions.php";
require_once MYBB_ROOT."inc/functions_upload.php";

$uid = $mybb->user['uid'];

// $fileToUpload = $_POST["fileToUpload"];
$submit = $_POST["submit"];

if ($submit == 'Upload Image') {

	$uploadspath_abs = mk_path_abs($mybb->settings['uploadspath']);
    $uploadspath_abs = "/home4/shinobi9/public_html/./images/sg/uploads";
    $mensaje_redireccion = '';

    $uploadOk = 1;
    $target_file = "/images/sg/uploads/" . basename($_FILES["fileToUpload"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

    // Check file size
    if ($_FILES["fileToUpload"]["size"] > 5000000) {
        $mensaje_redireccion .= "<br>El archivo que has enviado supera los 5 MB.<br>";
        $uploadOk = 0;
      }

    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
        && $imageFileType != "gif" ) {
        $mensaje_redireccion .= "<br>Solo puedes subir archivos de tipo JPG, JPEG, PNG & GIF .<br>";
        $uploadOk = 0;
    }

    if ($uploadOk == 1) {
        upload_file($_FILES["fileToUpload"], $uploadspath_abs);
        $mensaje_redireccion .= "<br><br>El archivo se subió en esta dirección: <a target='_blank' href='https://shinobigaiden.net" . $target_file . "'>" . $target_file . "</a><br><br><br>";
        
    
    } else {
        $mensaje_redireccion .= "Hubo un error al subir el archivo, no se pudo.";
    }
    eval("\$page = \"".$templates->get("sg_redireccion")."\";");
    output_page($page);

} else if (is_staff($uid)) {
    eval("\$page = \"".$templates->get("sg_upload")."\";");
    output_page($page);
} else {
    $mensaje_redireccion = "Si no eres Staff, no tienes acceso a esta página.";
    eval("\$page = \"".$templates->get("sg_redireccion")."\";");
    output_page($page);
}