<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'npcs_crear.lphp');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/sg_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$user_accion = $mybb->get_input('accion'); 
$npc_id = $mybb->get_input('npc_id'); 


$accion_post = $_POST["accion"];
$npc_id_post = $_POST["npc_id"];
$nombre = trim($_POST["nombre"]);
$apodo = trim($_POST["apodo"]);
$faccion = trim($_POST["faccion"]);
$raza = trim($_POST["raza"]);
$edad = trim($_POST["edad"]);
$altura = trim($_POST["altura"]);
$peso = trim($_POST["peso"]);
$sexo = trim($_POST["sexo"]);
$temporada = trim($_POST["temporada"]);
$nivel = trim($_POST["nivel"]);

$fuerza = trim($_POST["fuerza"]);
$resistencia = trim($_POST["resistencia"]);
$destreza = trim($_POST["destreza"]);
$voluntad = trim($_POST["voluntad"]);
$punteria = trim($_POST["punteria"]);
$agilidad = trim($_POST["agilidad"]);
$reflejos = trim($_POST["reflejos"]);
$control_akuma = trim($_POST["control_akuma"]);

$vitalidad = trim($_POST["vitalidad"]);
$energia = trim($_POST["energia"]);
$haki = trim($_POST["haki"]);

$rango = trim($_POST["rango"]);
$sangre = trim($_POST["sangre"]);
$akuma = trim($_POST["akuma"]);
$avatar1 = trim($_POST["avatar1"]);
$avatar2 = trim($_POST["avatar2"]);

$apariencia = addslashes($_POST["apariencia"]);
$personalidad = addslashes($_POST["personalidad"]);
$historia1 = addslashes($_POST["historia1"]);
$historia2 = addslashes($_POST["historia2"]);
$historia3 = addslashes($_POST["historia3"]);
$extra = addslashes($_POST["extra"]);
$notas = addslashes($_POST["notas"]);

$reload_js = "<script>window.location.href = window.location.pathname;</script>";

if ($accion_post == 'Agregar' && $nombre && (is_mod($uid) || is_staff($uid))) {
    $log = "Nuevo npc creado de ID ($nombre).";

    // echo("
    //     INSERT INTO `mybb_sg_sg_npcs` 
    //         (`npc_id`, `nombre`, `apodo`, `faccion`, `raza`, `edad`, `altura`, `peso`, `sexo`, `temporada`, `nivel`, 
    //         `fuerza`, `resistencia`, `destreza`, `voluntad`, `punteria`, `agilidad`, `reflejos`, `control_akuma`,
    //         `rango`, `sangre`, `akuma`, `avatar1`, `avatar2`,
    //         `apariencia`, `personalidad`, `historia1`, `historia2`, `historia3`, `extra`, `vitalidad`, `energia`, `haki`) VALUES 
    //         ('$npc_id_post', '$nombre','$apodo','$faccion','$raza','$edad', '$altura', '$peso', '$sexo', '$temporada', '$nivel',
    //         '$fuerza', '$resistencia', '$destreza', '$voluntad', '$punteria', '$agilidad', '$reflejos', '$control_akuma',
    //         '$rango', '$sangre', '$akuma', '$avatar1', '$avatar2',
    //         '$apariencia', '$personalidad', '$historia1', '$historia2', '$historia3', '$extra', '$vitalidad', '$energia', '$haki', '$notas'
    //     );
    // ");

    $db->query(" 
        INSERT INTO `mybb_sg_sg_npcs` 
            (`npc_id`, `nombre`, `apodo`, `faccion`, `raza`, `edad`, `altura`, `peso`, `sexo`, `temporada`, `nivel`, 
            `fuerza`, `resistencia`, `destreza`, `voluntad`, `punteria`, `agilidad`, `reflejos`, `control_akuma`,
            `rango`, `sangre`, `akuma`, `avatar1`, `avatar2`,
            `apariencia`, `personalidad`, `historia1`, `historia2`, `historia3`, `extra`, `vitalidad`, `energia`, `haki`, `notas`) VALUES 
            ('$npc_id_post', '$nombre','$apodo','$faccion','$raza','$edad', '$altura', '$peso', '$sexo', '$temporada', '$nivel',
            '$fuerza', '$resistencia', '$destreza', '$voluntad', '$punteria', '$agilidad', '$reflejos', '$control_akuma',
            '$rango', '$sangre', '$akuma', '$avatar1', '$avatar2',
            '$apariencia', '$personalidad', '$historia1', '$historia2', '$historia3', '$extra', '$vitalidad', '$energia', '$haki', '$notas'
        );
    ");

    // $db->query(" 
    //     INSERT INTO `mybb_sg_sg_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
    //     ('$staff', '$username', '$razon', '$log');
    // ");

    eval('$log_var = $log;');
    // eval('$reload_script = $reload_js;'); 
}

if ($accion_post == 'Remover' && $npc_id_post && $nombre && (is_mod($uid) || is_staff($uid))) {
    $log = "Remover técnica ID $npc_id_post ($nombre).";
    
    $db->query(" 
        DELETE FROM `mybb_sg_sg_npcs` WHERE `npc_id`='$npc_id_post';
    ");

    // $db->query(" 
    //     INSERT INTO `mybb_sg_sg_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
    //     ('$staff', '$username', '$razon', '$log');
    // ");

    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;'); 
}

if ($user_accion && $npc_id) {
    $query_npcs = $db->query("
        SELECT * FROM `mybb_sg_sg_npcs` WHERE npc_id='$npc_id'
    ");
    while ($t = $db->fetch_array($query_npcs)) {
        eval('$npc = $t;');
    }
}

if (is_mod($uid) || is_staff($uid)) { 
    eval('$accion = $user_accion;');
    eval("\$page = \"".$templates->get("staff_npcs_crear")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
