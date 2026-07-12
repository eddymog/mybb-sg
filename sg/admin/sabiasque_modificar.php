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

// Etiquetas legibles de cada tipo (el prefijo con el que se muestra la frase).
$sabiasque_tipos = array(
    1 => 'Sabías qué…',
    2 => 'Hay rumores sobre…',
    3 => 'En este foro…',
);
$SQ_TEXTO_MAX = 1000; // == varchar(1000) de la columna `texto`

// Render de una tarjeta del listado (se usa en la vista y en la respuesta AJAX).
function sq_card_html($s, $tipos, $active_id = '') {
    $sid    = intval($s['id']);
    $t      = intval($s['tipo']);
    $label  = isset($tipos[$t]) ? $tipos[$t] : ('Tipo ' . $t);
    $txt    = htmlspecialchars($s['texto'], ENT_QUOTES);
    $search = htmlspecialchars(strtolower($s['texto'] . ' ' . $label . ' ' . $sid), ENT_QUOTES);
    $active = ($active_id !== '' && intval($active_id) === $sid) ? ' is-active' : '';

    return "<div class=\"sq-card$active\" data-id=\"$sid\" data-tipo=\"$t\" data-search=\"$search\" onclick=\"sqOpen(event, $sid)\">"
        . "<div class=\"sq-card__top\">"
        . "<span class=\"sq-card__id\">#$sid</span>"
        . "<span class=\"sq-badge sq-badge--t$t\">$label</span>"
        . "<button type=\"button\" class=\"sq-card__del\" title=\"Eliminar\" aria-label=\"Eliminar entrada $sid\" onclick=\"sqDelete(event, $sid)\">&times;</button>"
        . "</div>"
        . "<p class=\"sq-card__text\">$txt</p>"
        . "</div>";
}

// Recuento total + próximo id disponible.
function sq_meta($db) {
    $count = 0; $next = 1;
    $q = $db->query("SELECT COUNT(*) AS c, MAX(id) AS m FROM `mybb_sg_sg_sabiasque`");
    while ($r = $db->fetch_array($q)) { $count = intval($r['c']); $next = intval($r['m']) + 1; }
    if ($next < 1) { $next = 1; }
    return array($count, $next);
}

// Entradas del POST (comunes a AJAX y al envío normal).
$id_raw    = isset($_POST['sabiasque_id']) ? intval($_POST['sabiasque_id']) : 0;
$tipo_raw  = isset($_POST['tipo'])  ? intval($_POST['tipo'])  : 0;
$texto_raw = isset($_POST['texto']) ? (string) $_POST['texto'] : '';
$accion    = $mybb->get_input('accion');
$is_ajax   = ($mybb->get_input('ajax') == '1');

// ── Endpoint AJAX (guardar / eliminar) ────────────────────────────────
if ($is_ajax) {
    header('Content-Type: application/json; charset=utf-8');
    $resp = array('ok' => false, 'mensaje' => '');

    if (!$es_staff) {
        $resp['mensaje'] = 'No tienes permisos para esta acción.';
        echo json_encode($resp); exit;
    }
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $resp['mensaje'] = 'Sesión caducada. Recarga la página e inténtalo de nuevo.';
        echo json_encode($resp); exit;
    }

    if ($accion === 'eliminar') {
        if ($id_raw <= 0) {
            $resp['mensaje'] = 'ID inválido.';
        } else {
            $db->query("DELETE FROM `mybb_sg_sg_sabiasque` WHERE id='$id_raw'");
            $resp['ok'] = true;
            $resp['id'] = $id_raw;
            $resp['mensaje'] = "Entrada #$id_raw eliminada.";
        }
    } elseif ($accion === 'guardar') {
        if ($id_raw <= 0) {
            $resp['mensaje'] = 'El ID debe ser un número mayor que 0.';
        } elseif (!isset($sabiasque_tipos[$tipo_raw])) {
            $resp['mensaje'] = 'Tipo inválido.';
        } elseif (trim($texto_raw) === '') {
            $resp['mensaje'] = 'El texto no puede estar vacío.';
        } elseif (mb_strlen($texto_raw) > $SQ_TEXTO_MAX) {
            $resp['mensaje'] = "El texto supera los $SQ_TEXTO_MAX caracteres (" . mb_strlen($texto_raw) . ").";
        } else {
            $texto_db = $db->escape_string($texto_raw);
            $ya_existe = false;
            $qc = $db->query("SELECT id FROM `mybb_sg_sg_sabiasque` WHERE id='$id_raw'");
            while ($c = $db->fetch_array($qc)) { $ya_existe = true; }

            if ($ya_existe) {
                $db->query("UPDATE `mybb_sg_sg_sabiasque` SET `tipo`='$tipo_raw',`texto`='$texto_db' WHERE `id`='$id_raw'");
                $resp['mensaje'] = "Entrada #$id_raw actualizada.";
            } else {
                $db->query("INSERT INTO `mybb_sg_sg_sabiasque`(`id`, `tipo`, `texto`) VALUES ('$id_raw','$tipo_raw','$texto_db')");
                $resp['mensaje'] = "Entrada #$id_raw creada.";
            }
            $resp['ok']        = true;
            $resp['existe']    = $ya_existe;
            $resp['id']        = $id_raw;
            $resp['tipo']      = $tipo_raw;
            $resp['card_html'] = sq_card_html(array('id' => $id_raw, 'tipo' => $tipo_raw, 'texto' => $texto_raw), $sabiasque_tipos);
        }
    } else {
        $resp['mensaje'] = 'Acción desconocida.';
    }

    list($resp['count'], $resp['next_id']) = sq_meta($db);
    echo json_encode($resp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ── Envío normal (fallback sin JS): crear / actualizar ────────────────
// Con JS, el guardado va por AJAX (más abajo) y esto no se ejecuta. Sin JS, el
// POST normal cae aquí y la página se vuelve a renderizar con el aviso.
$log = ''; $log_var = '';

if ($mybb->request_method === 'post' && $es_staff
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && $id_raw > 0 && isset($sabiasque_tipos[$tipo_raw])
    && trim($texto_raw) !== '' && mb_strlen($texto_raw) <= $SQ_TEXTO_MAX) {

    $texto_db = $db->escape_string($texto_raw);
    $ya_existe = false;
    $qc = $db->query("SELECT id FROM `mybb_sg_sg_sabiasque` WHERE id='$id_raw'");
    while ($c = $db->fetch_array($qc)) { $ya_existe = true; }

    if ($ya_existe) {
        $db->query("UPDATE `mybb_sg_sg_sabiasque` SET `tipo`='$tipo_raw',`texto`='$texto_db' WHERE `id`='$id_raw'");
        $log = "Entrada #$id_raw actualizada correctamente.";
    } else {
        $db->query("INSERT INTO `mybb_sg_sg_sabiasque`(`id`, `tipo`, `texto`) VALUES ('$id_raw','$tipo_raw','$texto_db')");
        $log = "Entrada #$id_raw creada correctamente.";
    }
    eval('$log_var = $log;');
}

// ── Datos para la vista ───────────────────────────────────────────────
$sabiasque_id = $mybb->get_input('sabiasque_id');

$sabiasques = array();
$query_sabiasques = $db->query("SELECT * FROM `mybb_sg_sg_sabiasque` ORDER BY id ASC");
while ($q = $db->fetch_array($query_sabiasques)) { $sabiasques[] = $q; }

list($sabiasque_count, $sabiasque_next_id) = sq_meta($db);
eval('$sabiasqueCount = $sabiasque_count;');
eval('$sabiasqueNextId = $sabiasque_next_id;');

// IDs existentes (para avisar en vivo si se va a sobrescribir al "crear")
$ids_existentes = array();
foreach ($sabiasques as $s) { $ids_existentes[] = intval($s['id']); }
$sabiasque_ids_json = json_encode($ids_existentes);
eval('$sabiasqueIdsJson = $sabiasque_ids_json;');
eval('$sabiasquePostKey = $mybb->post_code;');

// Entrada seleccionada para editar
$sabiasque = null;
if ($sabiasque_id !== '') {
    foreach ($sabiasques as $s) {
        if (intval($s['id']) === intval($sabiasque_id)) { $sabiasque = $s; }
    }
    if ($sabiasque === null) { $sabiasque_id = ''; } // id inexistente -> listado
}
$sabiasque_texto_esc = ($sabiasque !== null) ? htmlspecialchars($sabiasque['texto'], ENT_QUOTES) : '';
$sabiasque_texto_len = ($sabiasque !== null) ? mb_strlen($sabiasque['texto']) : 0;
eval('$sabiasqueTextoEsc = $sabiasque_texto_esc;');
eval('$sabiasqueTextoLen = $sabiasque_texto_len;');

// Tarjetas del listado (render en servidor)
$sabiasque_list_html = '';
foreach ($sabiasques as $s) {
    $sabiasque_list_html .= sq_card_html($s, $sabiasque_tipos, $sabiasque_id);
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
