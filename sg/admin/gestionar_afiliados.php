<?php
/**
 * MyBB 1.8
 *
 * Gestión de Afiliados (mybb_sg_sg_afiliados) — 3 niveles: hermano / grande /
 * pequeno. Ver docs/afiliados_diseno.md. Mismo patrón que gestionar_banners.php.
 * La imagen se sube antes en subir_imagenes.php; aquí solo se pega la URL.
 * El render público (índice) va directo en index.php (sin plugin).
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'gestionar_afiliados.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/sg_functions.php";

global $templates, $mybb, $db;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$es_staff = (is_mod($uid) || is_staff($uid));

$TIPOS_VALIDOS = array('hermano', 'grande', 'pequeno');

// Render de una tarjeta (se usa en la vista y en la respuesta AJAX). El
// thumbnail cambia de proporción según `$tipo` para previsualizar cómo se ve
// realmente en el índice: hermano/grande en 16:9, pequeño como cuadrito
// (ver sg_afil_card_html() en sg_functions.php, que es el render público).
// Los data-* llevan los valores crudos para poder precargar el form al Editar.
function afil_card_html($a, $tipo) {
    $id      = intval($a['id']);
    $imagen  = htmlspecialchars($a['imagen'], ENT_QUOTES);
    $nombre  = htmlspecialchars($a['nombre'], ENT_QUOTES);
    $url     = htmlspecialchars($a['url'], ENT_QUOTES);
    $desc    = htmlspecialchars(isset($a['descripcion']) ? (string) $a['descripcion'] : '', ENT_QUOTES);
    $orden   = intval($a['orden']);
    $activo  = intval($a['activo']) === 1;
    $cls     = $activo ? '' : ' is-inactive';
    $toggle_lbl = $activo ? 'Desactivar' : 'Activar';
    $tipo_esc = htmlspecialchars($tipo, ENT_QUOTES);

    return "<div class=\"afg-card afg-card--$tipo_esc$cls\" data-id=\"$id\" data-nombre=\"$nombre\" data-url=\"$url\" data-imagen=\"$imagen\" data-descripcion=\"$desc\" data-orden=\"$orden\">"
        . "<div class=\"afg-card__thumb\"><img src=\"$imagen\" alt=\"$nombre\" loading=\"lazy\" onerror=\"this.style.opacity=0.25\"></div>"
        . "<div class=\"afg-card__body\">"
        . "<div class=\"afg-card__title\">" . ($nombre !== '' ? $nombre : "<em>Sin nombre</em>") . "</div>"
        . "<div class=\"afg-card__meta\">#$id · orden $orden · <span class=\"afg-card__status\">" . ($activo ? 'Activo' : 'Inactivo') . "</span></div>"
        . "<div class=\"afg-card__url\"><a href=\"$url\" target=\"_blank\" rel=\"noopener noreferrer\">$url</a></div>"
        . "</div>"
        . "<div class=\"afg-card__actions\">"
        . "<button type=\"button\" class=\"afg-btn afg-btn--ghost\" onclick=\"afgEditar(event,$id)\">Editar</button>"
        . "<button type=\"button\" class=\"afg-btn afg-btn--ghost\" onclick=\"afgToggle(event,$id)\">$toggle_lbl</button>"
        . "<button type=\"button\" class=\"afg-btn afg-btn--danger\" onclick=\"afgEliminar(event,$id)\">Eliminar</button>"
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
        $tipo   = trim($mybb->get_input('tipo'));
        $nombre = trim($mybb->get_input('nombre'));
        $url    = trim($mybb->get_input('url'));
        $imagen = trim($mybb->get_input('imagen'));
        $desc   = trim($mybb->get_input('descripcion'));
        $orden  = intval($mybb->get_input('orden'));

        if (!in_array($tipo, $TIPOS_VALIDOS, true)) {
            $resp['mensaje'] = 'Nivel inválido.';
        } else if ($nombre === '' || mb_strlen($nombre) > 120) {
            $resp['mensaje'] = 'El nombre es obligatorio (máx. 120 caracteres).';
        } else if ($url === '' || mb_strlen($url) > 255) {
            $resp['mensaje'] = 'La URL es obligatoria (máx. 255 caracteres).';
        } else if ($imagen === '' || mb_strlen($imagen) > 255) {
            $resp['mensaje'] = 'La URL de la imagen es obligatoria (máx. 255 caracteres).';
        } else if (mb_strlen($desc) > 255) {
            $resp['mensaje'] = 'La descripción supera los 255 caracteres.';
        } else {
            $tipo_db    = $db->escape_string($tipo);
            $nombre_db  = $db->escape_string($nombre);
            $url_db     = $db->escape_string($url);
            $imagen_db  = $db->escape_string($imagen);
            $desc_db    = ($desc === '') ? 'NULL' : "'" . $db->escape_string($desc) . "'";
            $staff_db   = $db->escape_string($username);
            $db->query("
                INSERT INTO `mybb_sg_sg_afiliados` (`tipo`, `nombre`, `url`, `imagen`, `descripcion`, `orden`, `activo`, `agregado_por`)
                VALUES ('$tipo_db', '$nombre_db', '$url_db', '$imagen_db', $desc_db, '$orden', '1', '$staff_db')
            ");
            $nuevo_id = $db->insert_id();
            $resp['ok'] = true;
            $resp['mensaje'] = 'Afiliado agregado.';
            $resp['tipo'] = $tipo;
            $resp['card_html'] = afil_card_html(array(
                'id' => $nuevo_id, 'nombre' => $nombre, 'url' => $url, 'imagen' => $imagen,
                'descripcion' => $desc, 'orden' => $orden, 'activo' => 1,
            ), $tipo);
        }
    } elseif ($accion === 'editar') {
        $id     = intval($mybb->get_input('id'));
        $nombre = trim($mybb->get_input('nombre'));
        $url    = trim($mybb->get_input('url'));
        $imagen = trim($mybb->get_input('imagen'));
        $desc   = trim($mybb->get_input('descripcion'));
        $orden  = intval($mybb->get_input('orden'));

        if ($id <= 0) {
            $resp['mensaje'] = 'ID inválido.';
        } else if ($nombre === '' || mb_strlen($nombre) > 120) {
            $resp['mensaje'] = 'El nombre es obligatorio (máx. 120 caracteres).';
        } else if ($url === '' || mb_strlen($url) > 255) {
            $resp['mensaje'] = 'La URL es obligatoria (máx. 255 caracteres).';
        } else if ($imagen === '' || mb_strlen($imagen) > 255) {
            $resp['mensaje'] = 'La URL de la imagen es obligatoria (máx. 255 caracteres).';
        } else if (mb_strlen($desc) > 255) {
            $resp['mensaje'] = 'La descripción supera los 255 caracteres.';
        } else {
            $fila = null;
            $q = $db->query("SELECT tipo, activo FROM `mybb_sg_sg_afiliados` WHERE id='$id'");
            while ($r = $db->fetch_array($q)) { $fila = $r; }

            if (!$fila) {
                $resp['mensaje'] = 'Ese afiliado ya no existe.';
            } else {
                $nombre_db = $db->escape_string($nombre);
                $url_db    = $db->escape_string($url);
                $imagen_db = $db->escape_string($imagen);
                $desc_db   = ($desc === '') ? 'NULL' : "'" . $db->escape_string($desc) . "'";
                $db->query("
                    UPDATE `mybb_sg_sg_afiliados`
                    SET `nombre`='$nombre_db', `url`='$url_db', `imagen`='$imagen_db', `descripcion`=$desc_db, `orden`='$orden'
                    WHERE id='$id'
                ");
                $resp['ok'] = true;
                $resp['mensaje'] = "Afiliado #$id actualizado.";
                $resp['tipo'] = $fila['tipo'];
                $resp['card_html'] = afil_card_html(array(
                    'id' => $id, 'nombre' => $nombre, 'url' => $url, 'imagen' => $imagen,
                    'descripcion' => $desc, 'orden' => $orden, 'activo' => (int) $fila['activo'],
                ), $fila['tipo']);
            }
        }
    } elseif ($accion === 'toggle') {
        $id = intval($mybb->get_input('id'));
        if ($id <= 0) {
            $resp['mensaje'] = 'ID inválido.';
        } else {
            $db->query("UPDATE `mybb_sg_sg_afiliados` SET `activo` = 1 - `activo` WHERE id='$id'");
            $activo_ahora = false;
            $q = $db->query("SELECT activo FROM `mybb_sg_sg_afiliados` WHERE id='$id'");
            while ($r = $db->fetch_array($q)) { $activo_ahora = ((int) $r['activo'] === 1); }
            $resp['ok'] = true;
            $resp['activo'] = $activo_ahora;
            $resp['mensaje'] = $activo_ahora ? "Afiliado #$id activado." : "Afiliado #$id desactivado.";
        }
    } elseif ($accion === 'eliminar') {
        $id = intval($mybb->get_input('id'));
        if ($id <= 0) {
            $resp['mensaje'] = 'ID inválido.';
        } else {
            $db->query("DELETE FROM `mybb_sg_sg_afiliados` WHERE id='$id'");
            $resp['ok'] = true;
            $resp['mensaje'] = "Afiliado #$id eliminado.";
        }
    } else {
        $resp['mensaje'] = 'Acción desconocida.';
    }

    echo json_encode($resp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ── Datos para la vista (una lista por nivel) ──────────────────────────
$listas_html = array('hermano' => '', 'grande' => '', 'pequeno' => '');
$conteos     = array('hermano' => 0, 'grande' => 0, 'pequeno' => 0);

$query = $db->query("SELECT * FROM `mybb_sg_sg_afiliados` ORDER BY tipo, orden ASC, id ASC");
while ($a = $db->fetch_array($query)) {
    if (!isset($listas_html[$a['tipo']])) { continue; }
    $listas_html[$a['tipo']] .= afil_card_html($a, $a['tipo']);
    $conteos[$a['tipo']]++;
}
foreach ($listas_html as $tipo => $html) {
    if ($html === '') { $listas_html[$tipo] = "<div class=\"afg-empty\">Todavía no hay afiliados en este nivel.</div>"; }
}

eval('$afgHermanoHtml = $listas_html["hermano"];');
eval('$afgGrandeHtml = $listas_html["grande"];');
eval('$afgPequenoHtml = $listas_html["pequeno"];');
eval('$afgHermanoCount = $conteos["hermano"];');
eval('$afgGrandeCount = $conteos["grande"];');
eval('$afgPequenoCount = $conteos["pequeno"];');
eval('$afgPostKey = $mybb->post_code;');

if ($es_staff) {
    eval("\$page = \"".$templates->get("staff_gestionar_afiliados")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
