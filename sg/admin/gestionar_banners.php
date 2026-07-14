<?php
/**
 * MyBB 1.8
 *
 * Gestión de banners rotativos del header (mybb_sg_sg_banners).
 * La imagen se sube antes en subir_imagenes.php; aquí solo se pega la URL.
 * Rotación real (cada 5 min) en sg_banner_rotativo() / header.html.
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'gestionar_banners.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/sg_functions.php";

global $templates, $mybb, $db;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$es_staff = (is_mod($uid) || is_staff($uid));

// Render de una tarjeta (se usa en la vista y en la respuesta AJAX).
function bn_card_html($b) {
    $id      = intval($b['id']);
    $imagen  = htmlspecialchars($b['imagen'], ENT_QUOTES);
    $titulo  = htmlspecialchars($b['titulo'], ENT_QUOTES);
    $activo  = intval($b['activo']) === 1;
    $cls     = $activo ? '' : ' is-inactive';
    $toggle_lbl = $activo ? 'Desactivar' : 'Activar';

    return "<div class=\"bn-card$cls\" data-id=\"$id\">"
        . "<div class=\"bn-card__thumb\"><img src=\"$imagen\" alt=\"$titulo\" loading=\"lazy\" onerror=\"this.style.opacity=0.25\"></div>"
        . "<div class=\"bn-card__body\">"
        . "<div class=\"bn-card__title\">" . ($titulo !== '' ? $titulo : "<em>Sin título</em>") . "</div>"
        . "<div class=\"bn-card__meta\">#$id · <span class=\"bn-card__status\">" . ($activo ? 'Activo' : 'Inactivo') . "</span></div>"
        . "</div>"
        . "<div class=\"bn-card__actions\">"
        . "<button type=\"button\" class=\"bn-btn bn-btn--ghost\" onclick=\"bnToggle(event,$id)\">$toggle_lbl</button>"
        . "<button type=\"button\" class=\"bn-btn bn-btn--danger\" onclick=\"bnEliminar(event,$id)\">Eliminar</button>"
        . "</div>"
        . "</div>";
}

$accion  = $mybb->get_input('accion');
$is_ajax = ($mybb->get_input('ajax') == '1');

// ── Endpoint AJAX (agregar / toggle / eliminar) ───────────────────────
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

    if ($accion === 'agregar') {
        $imagen = trim($mybb->get_input('imagen'));
        $titulo = trim($mybb->get_input('titulo'));
        if ($imagen === '' || mb_strlen($imagen) > 255) {
            $resp['mensaje'] = 'La URL de la imagen es obligatoria (máx. 255 caracteres).';
        } else if (mb_strlen($titulo) > 150) {
            $resp['mensaje'] = 'El título supera los 150 caracteres.';
        } else {
            $imagen_db = $db->escape_string($imagen);
            $titulo_db = $db->escape_string($titulo);
            $staff_db  = $db->escape_string($username);
            $db->query("INSERT INTO `mybb_sg_sg_banners` (`imagen`, `titulo`, `activo`, `agregado_por`) VALUES ('$imagen_db','$titulo_db','1','$staff_db')");
            $nuevo_id = $db->insert_id();
            $resp['ok'] = true;
            $resp['mensaje'] = 'Banner agregado.';
            $resp['card_html'] = bn_card_html(array('id' => $nuevo_id, 'imagen' => $imagen, 'titulo' => $titulo, 'activo' => 1));
        }
    } elseif ($accion === 'toggle') {
        $id = intval($mybb->get_input('id'));
        if ($id <= 0) {
            $resp['mensaje'] = 'ID inválido.';
        } else {
            $db->query("UPDATE `mybb_sg_sg_banners` SET `activo` = 1 - `activo` WHERE id='$id'");
            $activo_ahora = false;
            $q = $db->query("SELECT activo FROM `mybb_sg_sg_banners` WHERE id='$id'");
            while ($r = $db->fetch_array($q)) { $activo_ahora = ((int) $r['activo'] === 1); }
            $resp['ok'] = true;
            $resp['activo'] = $activo_ahora;
            $resp['mensaje'] = $activo_ahora ? "Banner #$id activado." : "Banner #$id desactivado.";
        }
    } elseif ($accion === 'eliminar') {
        $id = intval($mybb->get_input('id'));
        if ($id <= 0) {
            $resp['mensaje'] = 'ID inválido.';
        } else {
            $db->query("DELETE FROM `mybb_sg_sg_banners` WHERE id='$id'");
            $resp['ok'] = true;
            $resp['mensaje'] = "Banner #$id eliminado.";
        }
    } else {
        $resp['mensaje'] = 'Acción desconocida.';
    }

    $q_total = $db->query("SELECT COUNT(*) AS c, SUM(activo) AS a FROM `mybb_sg_sg_banners`");
    while ($r = $db->fetch_array($q_total)) {
        $resp['total']  = (int) $r['c'];
        $resp['activos'] = (int) $r['a'];
    }
    echo json_encode($resp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ── Datos para la vista ────────────────────────────────────────────────
$banners = array();
$query = $db->query("SELECT * FROM `mybb_sg_sg_banners` ORDER BY orden ASC, id ASC");
while ($b = $db->fetch_array($query)) { $banners[] = $b; }

$bn_total = count($banners);
$bn_activos = 0;
$bn_list_html = '';
foreach ($banners as $b) {
    if ((int) $b['activo'] === 1) { $bn_activos++; }
    $bn_list_html .= bn_card_html($b);
}
if ($bn_list_html === '') {
    $bn_list_html = "<div class=\"bn-empty\">Aún no hay banners agregados.</div>";
}

eval('$bnTotal = $bn_total;');
eval('$bnActivos = $bn_activos;');
eval('$bnListHtml = $bn_list_html;');
eval('$bnPostKey = $mybb->post_code;');

if ($es_staff) {
    eval("\$page = \"".$templates->get("staff_gestionar_banners")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
