<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'npcs_modificar.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/sg_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$npc_id = $mybb->get_input('npc_id'); 

$npc_id_post = trim($_POST["npc_id"]);
$nuevo_npc_id = trim($_POST["nuevo_npc_id"]);

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
$npc = null;

if ($npc_id) {
    $query_npcs = $db->query("
        SELECT * FROM `mybb_sg_sg_npcs` WHERE npc_id='$npc_id'
    ");
    while ($t = $db->fetch_array($query_npcs)) {
        $npc = $t;
    }
}

if ($npc_id_post && $nuevo_npc_id && $nombre && (is_mod($uid) || is_staff($uid))) {
    $log = "Cambio a npc ID $npc_id ($nombre).";

    $db->query(" 
        UPDATE `mybb_sg_sg_npcs` SET 
        `npc_id`='$nuevo_npc_id',`nombre`='$nombre',`apodo`='$apodo',`faccion`='$faccion',`raza`='$raza',`edad`='$edad', `altura`='$altura', `peso`='$peso', `sexo`='$sexo', `temporada`='$temporada', `nivel`='$nivel',
        `fuerza`='$fuerza', `resistencia`='$resistencia', `destreza`='$destreza', `voluntad`='$voluntad', `punteria`='$punteria', `agilidad`='$agilidad', `reflejos`='$reflejos', `control_akuma`='$control_akuma',
        `rango`='$rango', `sangre`='$sangre', `akuma`='$akuma', `avatar1`='$avatar1', `avatar2`='$avatar2',
        `apariencia`='$apariencia', `personalidad`='$personalidad', `historia1`='$historia1', `historia2`='$historia2', `historia3`='$historia3', `extra`='$extra', `vitalidad`='$vitalidad', `energia`='$energia', `haki`='$haki', `notas`='$notas'
        WHERE `npc_id`='$npc_id_post';
    ");

    // $db->query(" 
    //     INSERT INTO `mybb_sg_sg_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
    //     ('$staff', '$username', '$razon', '$log');
    // ");

    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;');
}

if (is_mod($uid) || is_staff($uid)) { 
    eval("\$page = \"".$templates->get("staff_npcs_modificar")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
