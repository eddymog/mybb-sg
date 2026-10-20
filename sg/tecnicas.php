<?php
/**
 * MyBB 1.8
 * Shinobi Gaiden - Catálogo de Técnicas y Árboles de Habilidades
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'tecnicas.php');

global $templates, $mybb, $db;

require_once "./../global.php";
require_once "./functions/sg_functions.php";

$s_uid    = intval($mybb->user['uid']);
$es_staff = (is_staff($s_uid) || is_mod($s_uid));

// Procesar acciones de Staff (POST)
$staff_msg = '';
$staff_msg_tipo = '';

if ($es_staff && isset($_POST['action_tecnicas'])) {
    $action_tec = trim($_POST['action_tecnicas']);
    if ($action_tec === 'crear_arbol') {
        $res = sg_crear_arbol_rapido($db, $_POST, $s_uid);
        $staff_msg = $res['msg'];
        $staff_msg_tipo = $res['tipo'];
    } elseif ($action_tec === 'rebuild_cache') {
        sg_rebuild_catalogo_arboles($db);
        $staff_msg = "Caché de catálogo de árboles reconstruida correctamente.";
        $staff_msg_tipo = 'ok';
    } elseif ($action_tec === 'eliminar_arbol') {
        $arbol_del = isset($_POST['arbol_nombre']) ? trim($_POST['arbol_nombre']) : '';
        $res = sg_eliminar_arbol($db, $arbol_del, $s_uid);
        $staff_msg = $res['msg'];
        $staff_msg_tipo = $res['tipo'];
    }
}

// Toolbar de staff
$staff_toolbar_html = '';
if ($es_staff) {
    $staff_toolbar_html = sg_tecnicas_render_staff_toolbar($mybb->settings['bburl'], $staff_msg, $staff_msg_tipo);
}

// Grid dinámico de árboles agrupados por sección
$tecnicas_grid_html = sg_tecnicas_render_grid($db, $mybb->settings['bburl']);

eval("\$page = \"".$templates->get("sg_tecnicas")."\";");
output_page($page);
