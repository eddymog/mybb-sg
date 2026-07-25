<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 * Tienda: catálogo comprable con validación de compra en el servidor.
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'tienda.php');

global $templates, $mybb, $db;

require_once "./../global.php";
require_once "./functions/sg_functions.php";

$uid = intval($mybb->user['uid']);
$default_img = '/images/sg/objeto_default.png';

$accion        = $_POST["accion"];
$objeto_post   = trim($_POST["objeto"]);
$cantidad_post = intval($_POST["cantidad"]);

$compra_ok = '';
$compra_error = '';

if (!does_ficha_exist($uid)) {
    eval("\$page = \"".$templates->get("sg_ficha_no_existe")."\";");
    output_page($page);
    exit;
}

$ficha = select_one_query_with_id('mybb_sg_sg_fichas', 'fid', $uid);
$ryos = intval($ficha['ryos']);
$precio_mult = sg_tienda_multiplicador($db, $uid);

// Stats efectivas del comprador, para resolver códigos [FUEx1]/[MDESx1]/etc.
// en los efectos de cada objeto. $ficha acá es la fila cruda (sin pasivas
// aplicadas), a diferencia de ficha.php -> sí hace falta sg_stats_efectivas().
$stats_ef_comprador = $ficha ? sg_stats_efectivas($ficha) : null;

// ── Compra (validada y serializada en el servidor) ────────────
if ($accion === 'comprar' && $objeto_post !== '' && $uid > 0) {
    $objeto_esc = $db->escape_string($objeto_post);
    $lock_name  = "sg_tienda_compra_$uid";

    // Lock por usuario: serializa compras concurrentes (evita doble-compra).
    // GET_LOCK funciona en MyISAM e InnoDB (las tablas usan motores mixtos).
    $got_lock = 0;
    $rl = $db->query("SELECT GET_LOCK('$lock_name', 5) AS l");
    while ($r = $db->fetch_array($rl)) {
        $got_lock = intval($r['l']);
    }

    if ($got_lock !== 1) {
        $compra_error = "No se pudo procesar la compra en este momento. Intenta de nuevo.";
    } else {
        // Todo lo que sigue corre dentro del lock con datos FRESCOS.
        $obj = null;
        $qo = $db->query("SELECT * FROM `mybb_sg_sg_objetos` WHERE objeto_id='$objeto_esc' AND en_tienda='1'");
        while ($o = $db->fetch_array($qo)) {
            $obj = $o;
        }

        if (!$obj) {
            $compra_error = "Ese objeto no está disponible en la tienda.";
        } else {
            // Saldo de ryos fresco (no el leído antes del lock)
            $ryos_actual = 0;
            $qr = $db->query("SELECT ryos FROM `mybb_sg_sg_fichas` WHERE fid='$uid'");
            while ($r = $db->fetch_array($qr)) {
                $ryos_actual = intval($r['ryos']);
            }

            $onombre   = htmlspecialchars($obj['nombre'], ENT_QUOTES);
            $coste_raw = intval($obj['coste']);
            $coste     = ($coste_raw >= 99999) ? $coste_raw : (int) round($coste_raw * $precio_mult);
            $maxq      = intval($obj['cantidadMaxima']);
            $n       = $cantidad_post > 0 ? $cantidad_post : 1;

            $actual = 0;
            $has = false;
            $qi = $db->query("SELECT cantidad FROM `mybb_sg_sg_inventario` WHERE uid='$uid' AND objeto_id='$objeto_esc'");
            while ($i = $db->fetch_array($qi)) {
                $has = true;
                $actual = intval($i['cantidad']);
            }

            $espacio = $maxq - $actual;

            if ($espacio <= 0) {
                $compra_error = "Ya tienes el máximo de \"$onombre\".";
            } else if ($n > $espacio) {
                $compra_error = "Solo puedes comprar $espacio más de \"$onombre\".";
            } else {
                $totalCoste = $coste * $n;
                if ($ryos_actual < $totalCoste) {
                    $compra_error = "Ryos insuficientes: necesitas " . number_format($totalCoste, 0, ',', '.') . " y tienes " . number_format($ryos_actual, 0, ',', '.') . ".";
                } else {
                    $nueva = $actual + $n;
                    $ryos_actual = $ryos_actual - $totalCoste;
                    $grupo = uniqid();
                    $detalle = "Compra en tienda: $n × " . $obj['nombre'];

                    // inventario + ryos son ambos InnoDB -> la transacción da atomicidad real.
                    $db->query("START TRANSACTION");
                    sg_inventario_set_cantidad($uid, $objeto_post, $nueva, 'usuario', SG_ORIGEN_TIENDA, $detalle, $grupo);
                    sg_ficha_set_campo($uid, 'ryos', $ryos_actual, 'usuario', SG_ORIGEN_TIENDA, $detalle, $grupo);
                    $db->query("COMMIT");

                    $compra_ok = "Compraste $n × \"$onombre\" por " . number_format($totalCoste, 0, ',', '.') . " ryos.";
                }
            }

            // Refleja el saldo (actualizado o sin cambios) para el render posterior
            $ryos = $ryos_actual;
        }

        $db->query("SELECT RELEASE_LOCK('$lock_name')");
    }
}

// ── Inventario actual del usuario ─────────────────────────────
$inv = array();
$qinv = $db->query("SELECT objeto_id, cantidad FROM `mybb_sg_sg_inventario` WHERE uid='$uid'");
while ($r = $db->fetch_array($qinv)) {
    $inv[$r['objeto_id']] = intval($r['cantidad']);
}

// ── Catálogo a la venta ───────────────────────────────────────
$es_staff = (is_mod($uid) || is_staff($uid));

$query_objetos = $db->query("
    SELECT * FROM `mybb_sg_sg_objetos`
    WHERE en_tienda='1'
    ORDER BY tipo, nombre
");

$objetos_html = '';
$tipoAnterior = null;
$total = 0;
$tipos_chips = array();
$conteo_tipos = array();

while ($q = $db->fetch_array($query_objetos)) {
    $total++;

    $oid_raw   = $q['objeto_id'];
    $oid       = htmlspecialchars($oid_raw, ENT_QUOTES);
    $nombre    = htmlspecialchars($q['nombre'], ENT_QUOTES);
    $tipo      = trim($q['tipo']) !== '' ? $q['tipo'] : 'Otros';
    $tipo_esc  = htmlspecialchars($tipo, ENT_QUOTES);
    $tamano    = htmlspecialchars($q['tamano'], ENT_QUOTES);
    $municion  = htmlspecialchars($q['municion'], ENT_QUOTES);
    $desc_attr = htmlspecialchars($q['descripcion'], ENT_QUOTES);
    // strip_tags: esto viaja por data-ef1 y el JS lo inserta como texto plano
    // (textContent), no como HTML, así que no puede llevar el <span> con tooltip.
    $ef1 = strip_tags(sg_parsear_codigos_stats(htmlspecialchars($q['efecto1'], ENT_QUOTES), $stats_ef_comprador));
    $ef2 = strip_tags(sg_parsear_codigos_stats(htmlspecialchars($q['efecto2'], ENT_QUOTES), $stats_ef_comprador));
    $ef3 = strip_tags(sg_parsear_codigos_stats(htmlspecialchars($q['efecto3'], ENT_QUOTES), $stats_ef_comprador));
    $coste_raw = intval($q['coste']);
    $coste     = ($coste_raw >= 99999) ? $coste_raw : (int) round($coste_raw * $precio_mult);
    $maxq      = intval($q['cantidadMaxima']);
    $img       = trim($q['imagen']) !== '' ? htmlspecialchars($q['imagen'], ENT_QUOTES) : $default_img;
    $data_name = htmlspecialchars(strtolower($q['nombre']), ENT_QUOTES);
    $data_tipo = htmlspecialchars(strtolower($tipo), ENT_QUOTES);
    $conteo_tipos[$data_tipo] = (isset($conteo_tipos[$data_tipo]) ? $conteo_tipos[$data_tipo] : 0) + 1;
    $data_search = htmlspecialchars(strtolower(
        $q['nombre'] . ' ' . $tipo . ' ' . $q['tamano'] . ' ' . $q['descripcion'] . ' ' .
        $q['efecto1'] . ' ' . $q['efecto2'] . ' ' . $q['efecto3']
    ), ENT_QUOTES);

    $coste_label = ($coste >= 99999) ? 'No comprable' : number_format($coste, 0, ',', '.') . ' ryos';

    $actual  = isset($inv[$oid_raw]) ? $inv[$oid_raw] : 0;
    $espacio = $maxq - $actual;
    $afford  = $coste > 0 ? intdiv($ryos, $coste) : $espacio;

    // Estado de compra. En el modal/vista detalle lo pinta el JS desde los data-*;
    // en la cuadrícula se muestra ya renderizado bajo la imagen ($gridbuy).
    if ($espacio <= 0) {
        $buystate = 'max'; $maxbuy = 0;
        $buy_html = "<div class=\"sg-buy-status sg-buy-status--max\">Máximo alcanzado</div>";
    } else if ($afford <= 0) {
        $buystate = 'sinryos'; $maxbuy = 0;
        $buy_html = "<div class=\"sg-buy-status sg-buy-status--no\">Ryos insuficientes</div>";
    } else {
        $buystate = 'ok'; $maxbuy = min($espacio, $afford);
        $buy_html = "<form method=\"post\" action=\"/sg/tienda.php\" class=\"sg-buy\" data-nombre=\"$nombre\" data-precio=\"$coste\" onclick=\"event.stopPropagation();\" onsubmit=\"return sgTiendaConfirm(this);\">"
            . "<input type=\"hidden\" name=\"accion\" value=\"comprar\">"
            . "<input type=\"hidden\" name=\"objeto\" value=\"$oid\">"
            . "<input class=\"sg-buy-qty\" type=\"number\" name=\"cantidad\" min=\"1\" max=\"$maxbuy\" value=\"1\" title=\"Cantidad (máx. $maxbuy)\">"
            . "<button class=\"sg-btn\" type=\"submit\">Comprar</button>"
            . "</form>";
    }
    // Precio + compra bajo la imagen (solo cuadrícula; se oculta en vista detalle).
    $gridbuy = "<div class=\"sg-obj-gridbuy\" onclick=\"event.stopPropagation();\">"
        . "<div class=\"sg-obj-gridprice\">$coste_label</div>"
        . $buy_html
        . "</div>";

    // Nuevo grupo por tipo
    if ($tipo !== $tipoAnterior) {
        if ($tipoAnterior !== null) {
            $objetos_html .= "</div></section>";
        }
        $objetos_html .= "<section class=\"sg-cat-group\" data-tipo=\"$data_tipo\"><h2 class=\"sg-cat-group-title\">$tipo_esc</h2><div class=\"sg-obj-grid\">";
        $tipos_chips[] = array($tipo_esc, $data_tipo);
        $tipoAnterior = $tipo;
    }

    // Miniatura 175×175 + nombre. El detalle (incl. la compra) lo pinta el JS
    // desde los data-*, para el modal (cuadrícula) y para la vista detalle.
    $objetos_html .= "<article class=\"sg-obj-tile\" tabindex=\"0\" role=\"button\" aria-label=\"$nombre\""
        . " data-name=\"$data_name\" data-tipo=\"$data_tipo\" data-search=\"$data_search\""
        . " data-nombre=\"$nombre\" data-img=\"$img\" data-coste=\"$coste_label\""
        . " data-oid=\"$oid\" data-tamano=\"$tamano\" data-tipolabel=\"$tipo_esc\" data-municion=\"$municion\""
        . " data-owned=\"$actual\" data-max=\"$maxq\" data-buystate=\"$buystate\" data-maxbuy=\"$maxbuy\" data-precionum=\"$coste\""
        . " data-desc=\"$desc_attr\" data-ef1=\"$ef1\" data-ef2=\"$ef2\" data-ef3=\"$ef3\">"
        . "<div class=\"sg-obj-thumb\"><img class=\"sg-obj-img\" src=\"$img\" alt=\"$nombre\" loading=\"lazy\" onerror=\"sgImgFallback(this)\"></div>"
        . "<div class=\"sg-obj-main\"><div class=\"sg-obj-name\">$nombre</div>$gridbuy<div class=\"sg-obj-detail\"></div></div>"
        . "</article>";
}
if ($tipoAnterior !== null) {
    $objetos_html .= "</div></section>";
}
if ($total === 0) {
    $objetos_html = "<div class=\"sg-cat-empty\">No hay objetos a la venta por ahora.</div>";
}

// Chips de filtro por categoría (Todos + cada tipo distinto), con su conteo.
$chips_html = "<button class=\"sg-cat-chip is-active\" type=\"button\" data-tipo=\"\" onclick=\"sgSetTipo(this)\">Todos <span class=\"sg-cat-chip-n\">$total</span></button>";
foreach ($tipos_chips as $t) {
    $n = isset($conteo_tipos[$t[1]]) ? $conteo_tipos[$t[1]] : 0;
    $chips_html .= "<button class=\"sg-cat-chip\" type=\"button\" data-tipo=\"{$t[1]}\" onclick=\"sgSetTipo(this)\">{$t[0]} <span class=\"sg-cat-chip-n\">$n</span></button>";
}
eval('$chipsHtml = $chips_html;');

$sg_es_staff = $es_staff ? 'true' : 'false';
eval('$sgEsStaff = $sg_es_staff;');

$ryos_label = number_format($ryos, 0, ',', '.');

eval("\$page = \"".$templates->get("sg_tienda")."\";");
output_page($page);
