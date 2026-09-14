<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'sabiasque_modificar.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/sg_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$es_staff = (is_mod($uid) || is_staff($uid));

$sabiasque_id = $mybb->get_input('sabiasque_id');

$sabiasque_id_post = isset($_POST['sabiasque_id']) ? trim($_POST['sabiasque_id']) : '';
$tipo  = isset($_POST['tipo'])  ? addslashes($_POST['tipo'])  : '';
$texto = isset($_POST['texto']) ? addslashes($_POST['texto']) : '';

// Etiquetas legibles de cada tipo (el prefijo con el que se muestra la frase).
$sabiasque_tipos = array(
    1 => 'Sabías qué…',
    2 => 'Hay rumores sobre…',
    3 => 'En este foro…',
);

$reload_js = "<script>window.location.href = window.location.pathname;</script>";
$log        = '';
$log_var    = '';
$reload_script = '';
$sabiasque  = null;

// ── Guardar (crear o actualizar) ──────────────────────────────────────
if ($sabiasque_id_post !== '' && $tipo !== '' && $texto !== '' && $es_staff) {
    $sid_int = intval($sabiasque_id_post);

    // Decidir INSERT/UPDATE por la existencia REAL del id (no por el modo de la
    // pantalla), asi crear con un id ya usado no rompe con clave duplicada.
    $ya_existe = false;
    $q_chk = $db->query("SELECT id FROM `mybb_sg_sg_sabiasque` WHERE id='$sid_int'");
    while ($c = $db->fetch_array($q_chk)) { $ya_existe = true; }

    if ($ya_existe) {
        $db->query("UPDATE `mybb_sg_sg_sabiasque` SET `tipo`='$tipo',`texto`='$texto' WHERE `id`='$sid_int'");
        $log = "Entrada #$sid_int actualizada correctamente.";
    } else {
        $db->query("INSERT INTO `mybb_sg_sg_sabiasque`(`id`, `tipo`, `texto`) VALUES ('$sid_int','$tipo','$texto')");
        $log = "Entrada #$sid_int creada correctamente.";
    }

    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;');
}

// ── Datos para la vista ───────────────────────────────────────────────
$sabiasques = array();
$query_sabiasques = $db->query("SELECT * FROM `mybb_sg_sg_sabiasque` ORDER BY id ASC");
while ($q = $db->fetch_array($query_sabiasques)) { $sabiasques[] = $q; }

$sabiasque_count   = count($sabiasques);
$sabiasque_next_id = 1;
foreach ($sabiasques as $s) {
    if (intval($s['id']) >= $sabiasque_next_id) { $sabiasque_next_id = intval($s['id']) + 1; }
}
eval('$sabiasqueCount = $sabiasque_count;');
eval('$sabiasqueNextId = $sabiasque_next_id;');

// Entrada seleccionada para editar
if ($sabiasque_id !== '') {
    foreach ($sabiasques as $s) {
        if (intval($s['id']) === intval($sabiasque_id)) { $sabiasque = $s; }
    }
    if ($sabiasque === null) { $sabiasque_id = ''; } // id inexistente -> volver al listado
}
$sabiasque_texto_esc = ($sabiasque !== null) ? htmlspecialchars($sabiasque['texto'], ENT_QUOTES) : '';
$sabiasque_tipo_sel  = ($sabiasque !== null) ? intval($sabiasque['tipo']) : 0;
eval('$sabiasqueTextoEsc = $sabiasque_texto_esc;');

// Tarjetas del listado (render en servidor: siempre visibles y buscables)
$sabiasque_list_html = '';
foreach ($sabiasques as $s) {
    $sid    = intval($s['id']);
    $t_int  = intval($s['tipo']);
    $tlabel = isset($sabiasque_tipos[$t_int]) ? $sabiasque_tipos[$t_int] : ('Tipo ' . $t_int);
    $txt    = htmlspecialchars($s['texto'], ENT_QUOTES);
    $search = htmlspecialchars(strtolower($s['texto'] . ' ' . $tlabel . ' ' . $sid), ENT_QUOTES);
    $active = ($sabiasque_id !== '' && intval($sabiasque_id) === $sid) ? ' is-active' : '';

    $sabiasque_list_html .= "<a class=\"sq-card$active\" href=\"?sabiasque_id=$sid\" data-search=\"$search\">"
        . "<div class=\"sq-card__top\"><span class=\"sq-card__id\">#$sid</span>"
        . "<span class=\"sq-badge sq-badge--t$t_int\">$tlabel</span></div>"
        . "<p class=\"sq-card__text\">$txt</p>"
        . "</a>";
}
if ($sabiasque_list_html === '') {
    $sabiasque_list_html = "<div class=\"sq-empty\">Aún no hay entradas registradas.</div>";
}
eval('$sabiasqueListHtml = $sabiasque_list_html;');

if ($es_staff) {
    eval("\$page = \"".$templates->get("staff_sabiasque_modificar")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
