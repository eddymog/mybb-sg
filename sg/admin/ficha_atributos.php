<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'ficha_atributos.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/sg_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$user_fid = $mybb->get_input('fid'); 
$reload_js = "<script>window.location.href = window.location.pathname;</script>";

$puntos_habilidad = $_POST["puntos_habilidad"];
$puntos_rol = $_POST["puntos_rol"];
$ryos = $_POST["ryos"];
$puntos_experiencia = $_POST["puntos_experiencia"];
$reputacion = $_POST["reputacion"];
$staff = $_POST["staff"];
$razon = $_POST["razon"];
$ficha_id = $_POST["ficha_id"];

if ($staff && $razon && $ficha_id && (is_mod($uid) || is_staff($uid))) {

    $query_ficha = $db->query("
        SELECT * FROM mybb_sg_sg_fichas WHERE fid='$ficha_id'
    ");
    while ($f = $db->fetch_array($query_ficha)) {
        $f_var = $f;
    }
    $query_usuario = $db->query("
        SELECT * FROM mybb_sg_users WHERE uid='$ficha_id'
    ");
    while ($u = $db->fetch_array($query_usuario)) {
        $u_var = $u;
    }

    $grupo = uniqid();
    $log = "Cambios de atributos para ficha de UID: $ficha_id (" . $f_var['nombre'] . "):\n";

    // ryos/ph/pe/reputacion son InnoDB; newpoints (PR) es MyISAM. Se enlazan por
    // $grupo pero sin transacción (ver caveat MyISAM en docs/instrucciones_cambios.md).
    if ($ryos != $f_var['ryos']) {
        $log .= "-- De ".$f_var['ryos']." a $ryos ryos.\n";
        sg_ficha_set_campo($ficha_id, 'ryos', $ryos, 'staff', SG_ORIGEN_ATRIBUTOS, $razon, $grupo);
    }
    if ($puntos_habilidad != $f_var['puntos_habilidad']) {
        $log .= "-- De ".$f_var['puntos_habilidad']." a $puntos_habilidad PH.\n";
        sg_ficha_set_campo($ficha_id, 'puntos_habilidad', $puntos_habilidad, 'staff', SG_ORIGEN_ATRIBUTOS, $razon, $grupo);
    }

    if ($puntos_rol != $u_var['newpoints']) {
        $log .= "-- De ".$u_var['newpoints']." a $puntos_rol PR.\n";
        sg_usuario_set_campo($ficha_id, 'newpoints', $puntos_rol, 'staff', SG_ORIGEN_ATRIBUTOS, $razon, $grupo);
    }

    if ($puntos_experiencia != $f_var['pe']) {
        $log .= "-- De ".$f_var['pe']." a $puntos_experiencia PE.\n";
        sg_ficha_set_campo($ficha_id, 'pe', $puntos_experiencia, 'staff', SG_ORIGEN_ATRIBUTOS, $razon, $grupo);
    }

    if ($reputacion != $f_var['reputacion']) {
        $log .= "-- De ".$f_var['reputacion']." a $reputacion de reputación.\n";
        sg_ficha_set_campo($ficha_id, 'reputacion', $reputacion, 'staff', SG_ORIGEN_ATRIBUTOS, $razon, $grupo);
    }

    $db->query(" 
        INSERT INTO `mybb_sg_sg_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
        ('$staff', '$username', '$razon', '$log');
    ");


    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;');
}

if (is_mod($uid) || is_staff($uid)) { 
    if ($user_fid != '') {
        $query_ficha = $db->query("
            SELECT * FROM mybb_sg_sg_fichas WHERE fid='$user_fid'
        ");
    
        while ($f = $db->fetch_array($query_ficha)) {
            $f_var = $f;
            eval('$ficha = $f_var;');
        }

        $query_usuario = $db->query("
            SELECT * FROM mybb_sg_users WHERE uid='$user_fid'
        ");
        while ($u = $db->fetch_array($query_usuario)) {
            $newpoints = $u['newpoints'];
            eval('$puntos_rol = $newpoints;');
        }
    }

    eval('$fid = $user_fid;');
    eval("\$page = \"".$templates->get("staff_ficha_atributos")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
