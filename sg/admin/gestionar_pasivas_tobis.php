<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 * Gestión de pasivas de Tobis (crear / modificar / eliminar).
 * Solo edita los campos de texto de la pasiva (nombre, descripción, imagen,
 * costo) — no programa el efecto real. Ver docs/tienda_tobis.md.
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'gestionar_pasivas_tobis.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/sg_functions.php";

global $templates, $mybb, $db;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$es_staff = (is_mod($uid) || is_staff($uid));

// Pasiva cargada para editar (vía GET)
$pasiva_id_input = trim($mybb->get_input('pasiva_id'));

// Datos del formulario
$accion_post        = $_POST["accion"];
$pasiva_id_old       = trim($_POST["pasiva_id_old"]);
$pasiva_id_post      = trim($_POST["pasiva_id"]);
$nombre              = trim($_POST["nombre"]);
$descripcion         = addslashes($_POST["descripcion"]);
$imagen              = trim($_POST["imagen"]);
$coste               = ($_POST["coste"] === '' || !isset($_POST["coste"])) ? 0 : intval($_POST["coste"]);
$requiere_programacion = intval($_POST["requiere_programacion"]);
$en_tienda           = intval($_POST["en_tienda"]);
$activo              = intval($_POST["activo"]);
$razon               = trim($_POST["razon"]);

$reload_js = "<script>window.location.href = window.location.pathname;</script>";
$log = null;

// ── Guardar (crear o modificar) ───────────────────────────────
if ($accion_post == 'Guardar' && $pasiva_id_post && $nombre && $descripcion && $razon && $es_staff) {
    $lookup_id = $pasiva_id_old !== '' ? $pasiva_id_old : $pasiva_id_post;

    $existe = null;
    $query_existe = $db->query("SELECT id FROM `mybb_sg_sg_pasivas_tobis` WHERE pasiva_id='$lookup_id'");
    while ($e = $db->fetch_array($query_existe)) {
        $existe = $e;
    }

    if ($existe) {
        // Modificar: si cambió el ID, actualiza también las pasivas ya compradas que lo referencian
        if ($pasiva_id_post != $pasiva_id_old && $pasiva_id_old !== '') {
            $db->query("UPDATE `mybb_sg_sg_ficha_pasivas` SET `pasiva_id`='$pasiva_id_post' WHERE pasiva_id='$pasiva_id_old'");
        }

        $db->query("
            UPDATE `mybb_sg_sg_pasivas_tobis` SET
                `pasiva_id`='$pasiva_id_post', `nombre`='$nombre', `descripcion`='$descripcion',
                `imagen`='$imagen', `coste`='$coste',
                `requiere_programacion`='$requiere_programacion',
                `en_tienda`='$en_tienda', `activo`='$activo'
            WHERE `pasiva_id`='$lookup_id';
        ");

        $log = "Modificar pasiva de Tobis ID $lookup_id -> $pasiva_id_post ($nombre).\ncoste=$coste,\nrequiere_programacion=$requiere_programacion,\nen_tienda=$en_tienda,\nactivo=$activo,\ndescripcion=$descripcion";
    } else {
        // Crear
        $db->query("
            INSERT INTO `mybb_sg_sg_pasivas_tobis`
            (`pasiva_id`, `nombre`, `descripcion`, `imagen`, `coste`, `requiere_programacion`, `en_tienda`, `activo`) VALUES
            ('$pasiva_id_post','$nombre','$descripcion','$imagen','$coste','$requiere_programacion','$en_tienda','$activo');
        ");

        $log = "Nueva pasiva de Tobis ID $pasiva_id_post ($nombre).\ncoste=$coste,\nrequiere_programacion=$requiere_programacion,\nen_tienda=$en_tienda,\nactivo=$activo,\ndescripcion=$descripcion";
    }
}

// ── Eliminar ──────────────────────────────────────────────────
if ($accion_post == 'Eliminar' && $pasiva_id_post && $razon && $es_staff) {
    $db->query("DELETE FROM `mybb_sg_sg_pasivas_tobis` WHERE `pasiva_id`='$pasiva_id_post';");
    $log = "Eliminar pasiva de Tobis ID $pasiva_id_post ($nombre).";
}

// ── Auditoría + recarga (común a Guardar/Eliminar) ────────────
if ($log !== null && $es_staff) {
    if (is_mod($uid) || is_staff($uid)) {
        $db->query("INSERT INTO `mybb_sg_sg_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES ('$username', '$username', '$razon', '$log');");
    }

    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;');
}

// ── Cargar pasiva a editar ─────────────────────────────────────
$pasiva = null;
if ($pasiva_id_input) {
    $query_pasivas = $db->query("SELECT * FROM `mybb_sg_sg_pasivas_tobis` WHERE pasiva_id='$pasiva_id_input'");
    while ($q = $db->fetch_array($query_pasivas)) {
        $pasiva = $q;
    }
}

// ── Select con todas las pasivas existentes (para elegir en vez de tipear el ID) ──
$pasivas_select_html = '<option value="">— Nueva pasiva —</option>';
if ($es_staff) {
    $query_todas = $db->query("SELECT pasiva_id, nombre, activo FROM `mybb_sg_sg_pasivas_tobis` ORDER BY nombre");
    while ($t = $db->fetch_array($query_todas)) {
        $opt_id     = htmlspecialchars($t['pasiva_id'], ENT_QUOTES);
        $opt_nombre = htmlspecialchars($t['nombre'], ENT_QUOTES);
        $opt_sufijo = intval($t['activo']) === 0 ? ' (inactiva)' : '';
        $opt_sel    = ($t['pasiva_id'] === $pasiva_id_input) ? ' selected' : '';
        $pasivas_select_html .= "<option value=\"$opt_id\"$opt_sel>$opt_nombre$opt_sufijo</option>";
    }
}

if ($es_staff) {
    eval('$pid = $pasiva_id_input;');
    eval('$pasivasSelectHtml = $pasivas_select_html;');
    eval("\$page = \"".$templates->get("staff_modificar_pasivas_tobis")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
