<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'crear_objetos.lphp');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/sg_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$user_accion = $mybb->get_input('accion'); 
$objeto_id_input = $mybb->get_input('objeto_id'); 
$accion_post = $_POST["accion"];

$nombre = trim($_POST["nombre"]);
$objeto_id = trim($_POST["objeto_id"]);
$tipo = trim($_POST["tipo"]);
$categoria = $_POST["categoria"];
$coste = addslashes($_POST["coste"]);
$descripcion = addslashes($_POST["descripcion"]);

$staff = $_POST["staff"];
$razon = $_POST["razon"];

$reload_js = "<script>window.location.href = window.location.pathname;</script>";

if ($accion_post == 'Agregar' && $nombre && $objeto_id && $tipo && $descripcion && $staff && (is_mod($uid) || is_staff($uid))) {
    $log = "Nuevo objeto creado de objeto ID $objeto_id ($nombre). \nEl nuevo objeto posee: \noid=$objeto_id,\nnombre=$nombre,\ntipo=$tipo,\ncategoria=$categoria,\ncoste=$coste,\ndescripcion=$descripcion";

    $db->query(" 
        INSERT INTO `mybb_sg_sg_objetos` (`objeto_id`, `nombre`, `tipo`, `categoria`,`coste`, `descripcion`) VALUES 
        ('$objeto_id','$nombre','$tipo','$categoria','$coste','$descripcion');
    ");

    if (is_staff($uid)) {
        $db->query(" 
            INSERT INTO `mybb_sg_sg_audit_consola` (`staff`, `razon`, `log`) VALUES 
            ('$staff', '$razon', '$log');
        ");
    }

    if (is_mod($uid)) {
        $db->query(" 
            INSERT INTO `mybb_sg_sg_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
            ('$staff', '$username', '$razon', '$log');
        ");
    }

    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;'); 
}

if ($accion_post == 'Remover' && $objeto_id && $nombre && $staff && $razon && (is_mod($uid) || is_staff($uid))) {
    $log = "Remover técnica ID $objeto_id ($nombre). Nuevo ID de la técnica: BORR$objeto_id. Tipo: borrada.";
    
    $db->query(" 
        UPDATE `mybb_sg_sg_objetos` SET `objeto_id`='BORR$objeto_id', `tipo`='borrada' WHERE `tid`='$objeto_id';
    ");

    if (is_staff($uid)) {
        $db->query(" 
            INSERT INTO `mybb_sg_sg_audit_consola` (`staff`, `razon`, `log`) VALUES 
            ('$staff', '$razon', '$log');
        ");
    }

    if (is_mod($uid)) {
        $db->query(" 
            INSERT INTO `mybb_sg_sg_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
            ('$staff', '$username', '$razon', '$log');
        ");
    }

    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;'); 
}

if ($user_accion && $objeto_id_input) {
    $query_objetos = $db->query("
        SELECT * FROM `mybb_sg_sg_objetos` WHERE objeto_id='$objeto_id'
    ");
    while ($q = $db->fetch_array($query_objetos)) {
        eval('$objeto = $q;');
    }
}

if (is_mod($uid) || is_staff($uid)) { 
    eval('$accion = $user_accion;');
    eval('$oid = $objeto_id;');
    eval("\$page = \"".$templates->get("staff_crear_objetos")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
