<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 * Tienda de Tobis: pasivas permanentes (Familia A) + pergaminos consumibles
 * (Familia B, objetos con en_tienda_tobis=1). Ver docs/tienda_tobis.md.
 * Fase actual: sin efectos mecánicos reales, solo compra + registro.
 * Toda compra se valida en el servidor y se serializa con GET_LOCK.
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'tienda_tobis.php');

global $templates, $mybb, $db;

require_once "./../global.php";
require_once "./functions/sg_functions.php";

$uid = intval($mybb->user['uid']);
$es_staff = (is_mod($uid) || is_staff($uid));
$default_img = '/images/sg/objeto_default.png';

$accion        = $_POST["accion"];
$pasiva_post   = trim($_POST["pasiva"]);
$objeto_post   = trim($_POST["objeto"]);
$cantidad_post = intval($_POST["cantidad"]);

$msg_ok = '';
$msg_error = '';

if (!does_ficha_exist($uid)) {
    eval("\$page = \"".$templates->get("sg_ficha_no_existe")."\";");
    output_page($page);
    exit;
}

$ficha = select_one_query_with_id('mybb_sg_sg_fichas', 'fid', $uid);
$tobi = intval($ficha['tobi']);

// ── Acciones (validadas y serializadas) ───────────────────────
if (in_array($accion, array('comprar_pasiva', 'comprar_pergamino'), true) && $uid > 0) {
    $lock_name = "sg_tienda_tobis_$uid";

    $got_lock = 0;
    $rl = $db->query("SELECT GET_LOCK('$lock_name', 5) AS l");
    while ($r = $db->fetch_array($rl)) {
        $got_lock = intval($r['l']);
    }

    if ($got_lock !== 1) {
        $msg_error = "No se pudo procesar la operación en este momento. Intenta de nuevo.";
    } else {
        // Saldo fresco dentro del lock
        $tobi_act = 0;
        $qf = $db->query("SELECT tobi FROM `mybb_sg_sg_fichas` WHERE fid='$uid'");
        while ($f = $db->fetch_array($qf)) {
            $tobi_act = intval($f['tobi']);
        }

        if ($accion === 'comprar_pasiva') {
            $pasiva_esc = $db->escape_string($pasiva_post);
            $pas = null;
            $qp = $db->query("SELECT * FROM `mybb_sg_sg_pasivas_tobis` WHERE pasiva_id='$pasiva_esc' AND activo='1' AND en_tienda='1'");
            while ($p = $db->fetch_array($qp)) {
                $pas = $p;
            }

            if (!$pas) {
                $msg_error = "Esa pasiva no está disponible en la tienda.";
            } else {
                $pnombre = htmlspecialchars($pas['nombre'], ENT_QUOTES);

                $ya_tiene = false;
                $qt = $db->query("SELECT id FROM `mybb_sg_sg_ficha_pasivas` WHERE uid='$uid' AND pasiva_id='$pasiva_esc'");
                while ($db->fetch_array($qt)) {
                    $ya_tiene = true;
                }

                if ($ya_tiene) {
                    $msg_error = "Ya tienes la pasiva \"$pnombre\".";
                } else {
                    $coste = intval($pas['coste']);
                    if ($tobi_act < $coste) {
                        $msg_error = "Tobis insuficientes: necesitas " . number_format($coste, 0, ',', '.') . " y tienes " . number_format($tobi_act, 0, ',', '.') . ".";
                    } else {
                        $tobi_act -= $coste;
                        $grupo = uniqid();
                        $detalle = "Compra de pasiva: " . $pas['nombre'];

                        $db->query("START TRANSACTION");
                        sg_ficha_pasiva_dar($uid, $pasiva_post, 'usuario', SG_ORIGEN_TIENDA_TOBIS, $detalle, $grupo);
                        sg_ficha_set_campo($uid, 'tobi', $tobi_act, 'usuario', SG_ORIGEN_TIENDA_TOBIS, $detalle, $grupo);
                        $db->query("COMMIT");

                        $msg_ok = "Adquiriste la pasiva \"$pnombre\" por " . number_format($coste, 0, ',', '.') . " Tobis.";
                    }
                }
            }
        } else if ($accion === 'comprar_pergamino') {
            $objeto_esc = $db->escape_string($objeto_post);
            $obj = null;
            $qo = $db->query("SELECT * FROM `mybb_sg_sg_objetos` WHERE objeto_id='$objeto_esc' AND en_tienda_tobis='1'");
            while ($o = $db->fetch_array($qo)) {
                $obj = $o;
            }

            if (!$obj) {
                $msg_error = "Ese objeto no está disponible en la tienda de Tobis.";
            } else {
                $onombre = htmlspecialchars($obj['nombre'], ENT_QUOTES);
                $coste   = intval($obj['coste_tobi']);
                $maxq    = intval($obj['cantidadMaxima']);
                $n       = $cantidad_post > 0 ? $cantidad_post : 1;

                $actual = 0;
                $qi = $db->query("SELECT cantidad FROM `mybb_sg_sg_inventario` WHERE uid='$uid' AND objeto_id='$objeto_esc'");
                while ($i = $db->fetch_array($qi)) {
                    $actual = intval($i['cantidad']);
                }

                $espacio = $maxq - $actual;

                if ($espacio <= 0) {
                    $msg_error = "Ya tienes el máximo de \"$onombre\".";
                } else if ($n > $espacio) {
                    $msg_error = "Solo puedes comprar $espacio más de \"$onombre\".";
                } else {
                    $total = $coste * $n;
                    if ($tobi_act < $total) {
                        $msg_error = "Tobis insuficientes: necesitas " . number_format($total, 0, ',', '.') . " y tienes " . number_format($tobi_act, 0, ',', '.') . ".";
                    } else {
                        $nueva = $actual + $n;
                        $tobi_act -= $total;
                        $grupo = uniqid();
                        $detalle = "Compra (tobis): $n × " . $obj['nombre'];

                        $db->query("START TRANSACTION");
                        sg_inventario_set_cantidad($uid, $objeto_post, $nueva, 'usuario', SG_ORIGEN_TIENDA_TOBIS, $detalle, $grupo);
                        sg_ficha_set_campo($uid, 'tobi', $tobi_act, 'usuario', SG_ORIGEN_TIENDA_TOBIS, $detalle, $grupo);
                        $db->query("COMMIT");

                        $msg_ok = "Compraste $n × \"$onombre\" por " . number_format($total, 0, ',', '.') . " Tobis.";
                    }
                }
            }
        }

        // Refleja el saldo para el render posterior
        $tobi = $tobi_act;

        $db->query("SELECT RELEASE_LOCK('$lock_name')");
    }
}

// ── Pasivas ya compradas por este personaje ───────────────────
$pasivas_tiene = array();
$qtp = $db->query("SELECT pasiva_id FROM `mybb_sg_sg_ficha_pasivas` WHERE uid='$uid'");
while ($r = $db->fetch_array($qtp)) {
    $pasivas_tiene[$r['pasiva_id']] = true;
}

// ── Catálogo combinado: pasivas + pergaminos ──────────────────
// Los tiles se arman con data-* (mismo patrón que sg/tienda.php / sg_tienda.html);
// el JS de la plantilla arma el modal y la vista detalle a partir de esos data-*.
// El Staff también ve las pasivas marcadas activo=0 (para revisarlas), pero no
// puede comprarlas: se les corta el botón de compra abajo, y el backend igual
// rechaza la compra (WHERE activo='1' en el bloque comprar_pasiva).
$pasivas_catalogo = sg_pasivas_tobis_catalogo(true, $es_staff);

$inv = array();
$qinv = $db->query("SELECT objeto_id, cantidad FROM `mybb_sg_sg_inventario` WHERE uid='$uid'");
while ($r = $db->fetch_array($qinv)) {
    $inv[$r['objeto_id']] = intval($r['cantidad']);
}

$tiles_html = '';
$total = 0;
$total_pasivas = 0;
$total_pergaminos = 0;

foreach ($pasivas_catalogo as $pid_raw => $p) {
    $total++;
    $total_pasivas++;

    $inactiva = intval($p['activo']) === 0;
    $img_raw  = (string) $p['imagen'];

    $oid         = htmlspecialchars($pid_raw, ENT_QUOTES);
    $nombre      = htmlspecialchars($p['nombre'], ENT_QUOTES);
    $img         = trim($img_raw) !== '' ? htmlspecialchars($img_raw, ENT_QUOTES) : $default_img;
    $data_name   = htmlspecialchars(strtolower($p['nombre']), ENT_QUOTES);
    $desc_attr   = htmlspecialchars($p['descripcion'], ENT_QUOTES);
    $data_search = htmlspecialchars(strtolower($p['nombre'] . ' ' . $p['descripcion']), ENT_QUOTES);

    $coste       = intval($p['coste']);
    $coste_label = number_format($coste, 0, ',', '.') . ' Tobis';

    if ($inactiva) {
        $buystate = 'inactiva';
        $buy_html = "<div class=\"sg-buy-status sg-buy-status--no\">Inactiva (Staff)</div>";
    } else if (isset($pasivas_tiene[$pid_raw])) {
        $buystate = 'have';
        $buy_html = "<div class=\"sg-buy-status sg-buy-status--max\">Ya la tienes</div>";
    } else if ($tobi < $coste) {
        $buystate = 'sintobis';
        $buy_html = "<div class=\"sg-buy-status sg-buy-status--no\">Tobis insuficientes</div>";
    } else {
        $buystate = 'ok';
        $buy_html = "<form method=\"post\" action=\"/sg/tienda_tobis.php\" class=\"sg-buy\" data-nombre=\"$nombre\" data-precio=\"$coste\" onclick=\"event.stopPropagation();\" onsubmit=\"return sgttConfirm(this, 'pasiva');\">"
            . "<input type=\"hidden\" name=\"accion\" value=\"comprar_pasiva\">"
            . "<input type=\"hidden\" name=\"pasiva\" value=\"$oid\">"
            . "<button class=\"sg-btn\" type=\"submit\">Adquirir</button>"
            . "</form>";
    }
    $gridbuy = "<div class=\"sg-obj-gridbuy\" onclick=\"event.stopPropagation();\">"
        . "<div class=\"sg-obj-gridprice\">$coste_label</div>"
        . $buy_html
        . "</div>";

    $tiles_html .= "<article class=\"sg-obj-tile\" tabindex=\"0\" role=\"button\" aria-label=\"$nombre\""
        . " data-kind=\"pasiva\" data-tipo=\"pasiva\" data-name=\"$data_name\" data-search=\"$data_search\""
        . " data-nombre=\"$nombre\" data-img=\"$img\" data-coste=\"$coste_label\""
        . " data-oid=\"$oid\" data-buystate=\"$buystate\" data-maxbuy=\"1\" data-precionum=\"$coste\""
        . " data-desc=\"$desc_attr\">"
        . "<div class=\"sg-obj-thumb\"><img class=\"sg-obj-img\" src=\"$img\" alt=\"$nombre\" loading=\"lazy\" onerror=\"sgImgFallback(this)\"></div>"
        . "<div class=\"sg-obj-main\"><div class=\"sg-obj-name\">$nombre</div>$gridbuy<div class=\"sg-obj-detail\"></div></div>"
        . "</article>";
}

$qo = $db->query("SELECT * FROM `mybb_sg_sg_objetos` WHERE en_tienda_tobis='1' ORDER BY coste_tobi, nombre");
while ($o = $db->fetch_array($qo)) {
    $total++;
    $total_pergaminos++;

    $oid_raw = $o['objeto_id'];

    $oid         = htmlspecialchars($oid_raw, ENT_QUOTES);
    $nombre      = htmlspecialchars($o['nombre'], ENT_QUOTES);
    $tamano      = htmlspecialchars($o['tamano'], ENT_QUOTES);
    $ef1         = strip_tags(htmlspecialchars($o['efecto1'], ENT_QUOTES));
    $maxq        = intval($o['cantidadMaxima']);
    $img         = trim($o['imagen']) !== '' ? htmlspecialchars($o['imagen'], ENT_QUOTES) : $default_img;
    $data_name   = htmlspecialchars(strtolower($o['nombre']), ENT_QUOTES);
    $desc_attr   = htmlspecialchars($o['descripcion'], ENT_QUOTES);
    $data_search = htmlspecialchars(strtolower($o['nombre'] . ' ' . $o['tamano'] . ' ' . $o['descripcion'] . ' ' . $o['efecto1']), ENT_QUOTES);

    $coste       = intval($o['coste_tobi']);
    $coste_label = number_format($coste, 0, ',', '.') . ' Tobis';

    $actual  = isset($inv[$oid_raw]) ? $inv[$oid_raw] : 0;
    $espacio = $maxq - $actual;
    $afford  = $coste > 0 ? intdiv($tobi, $coste) : $espacio;

    if ($espacio <= 0) {
        $buystate = 'max'; $maxbuy = 0;
        $buy_html = "<div class=\"sg-buy-status sg-buy-status--max\">Máximo alcanzado</div>";
    } else if ($afford <= 0) {
        $buystate = 'sintobis'; $maxbuy = 0;
        $buy_html = "<div class=\"sg-buy-status sg-buy-status--no\">Tobis insuficientes</div>";
    } else {
        $buystate = 'ok'; $maxbuy = min($espacio, $afford);
        $buy_html = "<form method=\"post\" action=\"/sg/tienda_tobis.php\" class=\"sg-buy\" data-nombre=\"$nombre\" data-precio=\"$coste\" onclick=\"event.stopPropagation();\" onsubmit=\"return sgttConfirm(this, 'pergamino');\">"
            . "<input type=\"hidden\" name=\"accion\" value=\"comprar_pergamino\">"
            . "<input type=\"hidden\" name=\"objeto\" value=\"$oid\">"
            . "<input class=\"sg-buy-qty\" type=\"number\" name=\"cantidad\" min=\"1\" max=\"$maxbuy\" value=\"1\" title=\"Cantidad (máx. $maxbuy)\">"
            . "<button class=\"sg-btn\" type=\"submit\">Comprar</button>"
            . "</form>";
    }
    $gridbuy = "<div class=\"sg-obj-gridbuy\" onclick=\"event.stopPropagation();\">"
        . "<div class=\"sg-obj-gridprice\">$coste_label</div>"
        . $buy_html
        . "</div>";

    $tiles_html .= "<article class=\"sg-obj-tile\" tabindex=\"0\" role=\"button\" aria-label=\"$nombre\""
        . " data-kind=\"pergamino\" data-tipo=\"pergamino\" data-name=\"$data_name\" data-search=\"$data_search\""
        . " data-nombre=\"$nombre\" data-img=\"$img\" data-coste=\"$coste_label\""
        . " data-oid=\"$oid\" data-tamano=\"$tamano\" data-ef1=\"$ef1\" data-owned=\"$actual\" data-max=\"$maxq\""
        . " data-buystate=\"$buystate\" data-maxbuy=\"$maxbuy\" data-precionum=\"$coste\""
        . " data-desc=\"$desc_attr\">"
        . "<div class=\"sg-obj-thumb\"><img class=\"sg-obj-img\" src=\"$img\" alt=\"$nombre\" loading=\"lazy\" onerror=\"sgImgFallback(this)\"></div>"
        . "<div class=\"sg-obj-main\"><div class=\"sg-obj-name\">$nombre</div>$gridbuy<div class=\"sg-obj-detail\"></div></div>"
        . "</article>";
}

$items_html = ($total > 0)
    ? "<div class=\"sg-obj-grid\">$tiles_html</div>"
    : "<div class=\"sg-cat-empty\">No hay pasivas ni pergaminos disponibles.</div>";
eval('$itemsHtml = $items_html;');

$sg_es_staff = $es_staff ? 'true' : 'false';
eval('$sgEsStaff = $sg_es_staff;');

$tobi_label = number_format($tobi, 0, ',', '.');

eval("\$page = \"".$templates->get("sg_tienda_tobis")."\";");
output_page($page);
