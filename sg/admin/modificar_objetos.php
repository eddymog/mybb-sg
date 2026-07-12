<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'modificar_objetos.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/sg_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$es_staff = (is_mod($uid) || is_staff($uid));

// Objeto cargado para editar (vía GET)
$objeto_id_input = trim($mybb->get_input('objeto_id'));

// Datos del formulario
$accion_post    = $_POST["accion"];
$objeto_id_old  = trim($_POST["objeto_id_old"]);
$objeto_id_post = trim($_POST["objeto_id"]);
$nombre         = trim($_POST["nombre"]);
$tipo           = trim($_POST["tipo"]);
$municion       = trim($_POST["municion"]);
$tamano         = trim($_POST["tamano"]);
$descripcion    = addslashes($_POST["descripcion"]);
$imagen         = trim($_POST["imagen"]);
$efecto         = addslashes($_POST["efecto"]);
$coste          = ($_POST["coste"] === '' || !isset($_POST["coste"])) ? 99999 : intval($_POST["coste"]);
$cantidadMaxima = ($_POST["cantidadMaxima"] === '' || !isset($_POST["cantidadMaxima"])) ? 99 : intval($_POST["cantidadMaxima"]);
$en_tienda      = intval($_POST["en_tienda"]);
$staff          = trim($_POST["staff"]);
$razon          = trim($_POST["razon"]);

$reload_js = "<script>window.location.href = window.location.pathname;</script>";
$log = null;

// ── Guardar (crear o modificar) ───────────────────────────────
if ($accion_post == 'Guardar' && $objeto_id_post && $nombre && $tipo && $descripcion && $staff && $razon && $es_staff) {
    $lookup_id = $objeto_id_old !== '' ? $objeto_id_old : $objeto_id_post;

    $existe = null;
    $query_existe = $db->query("SELECT id FROM `mybb_sg_sg_objetos` WHERE objeto_id='$lookup_id'");
    while ($e = $db->fetch_array($query_existe)) {
        $existe = $e;
    }

    if ($existe) {
        // Modificar: si cambió el ID, actualiza también el inventario que lo referencia
        if ($objeto_id_post != $objeto_id_old && $objeto_id_old !== '') {
            $db->query("UPDATE `mybb_sg_sg_inventario` SET `objeto_id`='$objeto_id_post' WHERE objeto_id='$objeto_id_old'");
        }

        $db->query("
            UPDATE `mybb_sg_sg_objetos` SET
                `objeto_id`='$objeto_id_post', `nombre`='$nombre', `tipo`='$tipo', `municion`='$municion',
                `tamano`='$tamano', `descripcion`='$descripcion', `coste`='$coste', `cantidadMaxima`='$cantidadMaxima',
                `imagen`='$imagen', `efecto`='$efecto', `en_tienda`='$en_tienda'
            WHERE `objeto_id`='$lookup_id';
        ");

        $log = "Modificar objeto ID $lookup_id -> $objeto_id_post ($nombre).\ntipo=$tipo,\nmunicion=$municion,\ntamano=$tamano,\ncoste=$coste,\ncantidadMaxima=$cantidadMaxima,\nen_tienda=$en_tienda,\nefecto=$efecto,\ndescripcion=$descripcion";
    } else {
        // Crear
        $db->query("
            INSERT INTO `mybb_sg_sg_objetos` (`objeto_id`, `nombre`, `tipo`, `municion`, `tamano`, `descripcion`, `coste`, `cantidadMaxima`, `imagen`, `efecto`, `en_tienda`) VALUES
            ('$objeto_id_post','$nombre','$tipo','$municion','$tamano','$descripcion','$coste','$cantidadMaxima','$imagen','$efecto','$en_tienda');
        ");

        $log = "Nuevo objeto ID $objeto_id_post ($nombre).\ntipo=$tipo,\nmunicion=$municion,\ntamano=$tamano,\ncoste=$coste,\ncantidadMaxima=$cantidadMaxima,\nen_tienda=$en_tienda,\nefecto=$efecto,\ndescripcion=$descripcion";
    }
}

// ── Eliminar ──────────────────────────────────────────────────
if ($accion_post == 'Eliminar' && $objeto_id_post && $staff && $razon && $es_staff) {
    $db->query("DELETE FROM `mybb_sg_sg_objetos` WHERE `objeto_id`='$objeto_id_post';");
    $log = "Eliminar objeto ID $objeto_id_post ($nombre).";
}

// ── Auditoría + recarga (común a Guardar/Eliminar) ────────────
if ($log !== null && $es_staff) {
    if (is_mod($uid) || is_staff($uid)) {
        $db->query("INSERT INTO `mybb_sg_sg_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES ('$staff', '$username', '$razon', '$log');");
    }

    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;');
}

// ── Cargar objeto a editar ────────────────────────────────────
$objeto = null;
if ($objeto_id_input) {
    $query_objetos = $db->query("SELECT * FROM `mybb_sg_sg_objetos` WHERE objeto_id='$objeto_id_input'");
    while ($q = $db->fetch_array($query_objetos)) {
        $objeto = $q;
    }
}

if ($es_staff) {
    eval('$oid = $objeto_id_input;');
    eval("\$page = \"".$templates->get("staff_modificar_objetos")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
