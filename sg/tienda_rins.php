<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 * Tienda de Rins: cambio de moneda (Ryos/Tobis) y compra de pergaminos.
 * Toda compra se valida en el servidor y se serializa con GET_LOCK.
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'tienda_rins.php');

global $templates, $mybb, $db;

require_once "./../global.php";
require_once "./functions/sg_functions.php";

$uid = intval($mybb->user['uid']);
$default_img = '/images/sg/objeto_default.png';

// Tasas de cambio
$RYOS_RIN  = 10;  $RYOS_GANA = 50;  // 10 Rin -> 50 Ryos
$TOBI_RIN  = 50;  $TOBI_GANA = 1;   // 50 Rin -> 1 Tobi

// Precio en Rins de los pergaminos (hardcode)
$precios_rin = array(
    'PERG001' => 100,
    'PERG002' => 200,
    'PERG003' => 300,
    'PERG004' => 500,
    'PERG005' => 1000,
    'PERG006' => 1500,
    'PERG007' => 3000,
);

$accion      = $_POST["accion"];
$cantidad    = intval($_POST["cantidad"]);
$objeto_post = trim($_POST["objeto"]);

$msg_ok = '';
$msg_error = '';

if (!does_ficha_exist($uid)) {
    eval("\$page = \"".$templates->get("sg_ficha_no_existe")."\";");
    output_page($page);
    exit;
}

$ficha = select_one_query_with_id('mybb_sg_sg_fichas', 'fid', $uid);
$rin  = intval($ficha['rin']);
$ryos = intval($ficha['ryos']);
$tobi = intval($ficha['tobi']);

// ── Acciones (validadas y serializadas) ───────────────────────
if (in_array($accion, array('cambiar_ryos', 'cambiar_tobi', 'comprar_pergamino'), true) && $uid > 0) {
    $lock_name = "sg_tienda_rins_$uid";

    $got_lock = 0;
    $rl = $db->query("SELECT GET_LOCK('$lock_name', 5) AS l");
    while ($r = $db->fetch_array($rl)) {
        $got_lock = intval($r['l']);
    }

    if ($got_lock !== 1) {
        $msg_error = "No se pudo procesar la operación en este momento. Intenta de nuevo.";
    } else {
        // Saldos frescos dentro del lock
        $rin_act = 0; $ryos_act = 0; $tobi_act = 0;
        $qf = $db->query("SELECT rin, ryos, tobi FROM `mybb_sg_sg_fichas` WHERE fid='$uid'");
        while ($f = $db->fetch_array($qf)) {
            $rin_act  = intval($f['rin']);
            $ryos_act = intval($f['ryos']);
            $tobi_act = intval($f['tobi']);
        }

        $n = $cantidad > 0 ? $cantidad : 1;

        if ($accion === 'cambiar_ryos') {
            $costo = $RYOS_RIN * $n;
            $gana  = $RYOS_GANA * $n;
            if ($rin_act < $costo) {
                $msg_error = "Rins insuficientes: necesitas " . number_format($costo, 0, ',', '.') . " y tienes " . number_format($rin_act, 0, ',', '.') . ".";
            } else {
                $rin_act  -= $costo;
                $ryos_act += $gana;
                $grupo = uniqid();
                $detalle = "Canje: " . number_format($costo, 0, ',', '.') . " rin -> " . number_format($gana, 0, ',', '.') . " ryos";
                $db->query("START TRANSACTION");
                sg_ficha_set_campo($uid, 'rin', $rin_act, 'usuario', SG_ORIGEN_TIENDA_RINS, $detalle, $grupo);
                sg_ficha_set_campo($uid, 'ryos', $ryos_act, 'usuario', SG_ORIGEN_TIENDA_RINS, $detalle, $grupo);
                $db->query("COMMIT");
                $msg_ok = "Cambiaste " . number_format($costo, 0, ',', '.') . " Rin por " . number_format($gana, 0, ',', '.') . " Ryos.";
            }
        } else if ($accion === 'cambiar_tobi') {
            $costo = $TOBI_RIN * $n;
            $gana  = $TOBI_GANA * $n;
            if ($rin_act < $costo) {
                $msg_error = "Rins insuficientes: necesitas " . number_format($costo, 0, ',', '.') . " y tienes " . number_format($rin_act, 0, ',', '.') . ".";
            } else {
                $rin_act  -= $costo;
                $tobi_act += $gana;
                $grupo = uniqid();
                $detalle = "Canje: " . number_format($costo, 0, ',', '.') . " rin -> $gana tobi";
                $db->query("START TRANSACTION");
                sg_ficha_set_campo($uid, 'rin', $rin_act, 'usuario', SG_ORIGEN_TIENDA_RINS, $detalle, $grupo);
                sg_ficha_set_campo($uid, 'tobi', $tobi_act, 'usuario', SG_ORIGEN_TIENDA_RINS, $detalle, $grupo);
                $db->query("COMMIT");
                $msg_ok = "Cambiaste " . number_format($costo, 0, ',', '.') . " Rin por $gana Tobi" . ($gana == 1 ? '' : 's') . ".";
            }
        } else if ($accion === 'comprar_pergamino') {
            if (!isset($precios_rin[$objeto_post])) {
                $msg_error = "Ese objeto no está disponible en la tienda de Rins.";
            } else {
                $objeto_esc = $db->escape_string($objeto_post);
                $obj = null;
                $qo = $db->query("SELECT * FROM `mybb_sg_sg_objetos` WHERE objeto_id='$objeto_esc'");
                while ($o = $db->fetch_array($qo)) {
                    $obj = $o;
                }

                if (!$obj) {
                    $msg_error = "Ese objeto ya no existe.";
                } else {
                    $onombre = htmlspecialchars($obj['nombre'], ENT_QUOTES);
                    $precio  = intval($precios_rin[$objeto_post]);
                    $maxq    = intval($obj['cantidadMaxima']);

                    $actual = 0; $has = false;
                    $qi = $db->query("SELECT cantidad FROM `mybb_sg_sg_inventario` WHERE uid='$uid' AND objeto_id='$objeto_esc'");
                    while ($i = $db->fetch_array($qi)) {
                        $has = true;
                        $actual = intval($i['cantidad']);
                    }

                    $espacio = $maxq - $actual;
                    if ($espacio <= 0) {
                        $msg_error = "Ya tienes el máximo de \"$onombre\".";
                    } else if ($n > $espacio) {
                        $msg_error = "Solo puedes comprar $espacio más de \"$onombre\".";
                    } else {
                        $total = $precio * $n;
                        if ($rin_act < $total) {
                            $msg_error = "Rins insuficientes: necesitas " . number_format($total, 0, ',', '.') . " y tienes " . number_format($rin_act, 0, ',', '.') . ".";
                        } else {
                            $nueva = $actual + $n;
                            $rin_act -= $total;
                            $grupo = uniqid();
                            $detalle = "Compra (rins): $n × " . $obj['nombre'];
                            $db->query("START TRANSACTION");
                            sg_inventario_set_cantidad($uid, $objeto_post, $nueva, 'usuario', SG_ORIGEN_TIENDA_RINS, $detalle, $grupo);
                            sg_ficha_set_campo($uid, 'rin', $rin_act, 'usuario', SG_ORIGEN_TIENDA_RINS, $detalle, $grupo);
                            $db->query("COMMIT");
                            $msg_ok = "Compraste $n × \"$onombre\" por " . number_format($total, 0, ',', '.') . " Rin.";
                        }
                    }
                }
            }
        }

        // Refleja saldos para el render
        $rin = $rin_act; $ryos = $ryos_act; $tobi = $tobi_act;

        $db->query("SELECT RELEASE_LOCK('$lock_name')");
    }
}

// ── Pergaminos disponibles (los del mapa de precios) ──────────
$ids = array_keys($precios_rin);
$ids_esc = array();
foreach ($ids as $id) {
    $ids_esc[] = "'" . $db->escape_string($id) . "'";
}
$in_list = implode(',', $ids_esc);

$objs = array();
if ($in_list !== '') {
    $qp = $db->query("SELECT * FROM `mybb_sg_sg_objetos` WHERE objeto_id IN ($in_list)");
    while ($o = $db->fetch_array($qp)) {
        $objs[$o['objeto_id']] = $o;
    }
}

// Inventario del usuario (cantidad por objeto)
$inv = array();
$qinv = $db->query("SELECT objeto_id, cantidad FROM `mybb_sg_sg_inventario` WHERE uid='$uid'");
while ($r = $db->fetch_array($qinv)) {
    $inv[$r['objeto_id']] = intval($r['cantidad']);
}

$pergaminos_html = '';
foreach ($precios_rin as $oid_raw => $precio) {
    if (!isset($objs[$oid_raw])) {
        continue;
    }
    $o = $objs[$oid_raw];

    $oid       = htmlspecialchars($oid_raw, ENT_QUOTES);
    $nombre    = htmlspecialchars($o['nombre'], ENT_QUOTES);
    $tamano    = htmlspecialchars($o['tamano'], ENT_QUOTES);
    $desc      = nl2br(htmlspecialchars($o['descripcion'], ENT_QUOTES));
    $efecto    = nl2br(htmlspecialchars($o['efecto'], ENT_QUOTES));
    $maxq      = intval($o['cantidadMaxima']);
    $img       = trim($o['imagen']) !== '' ? htmlspecialchars($o['imagen'], ENT_QUOTES) : $default_img;
    $data_name = htmlspecialchars(strtolower($o['nombre']), ENT_QUOTES);
    $precio_fmt = number_format(intval($precio), 0, ',', '.') . ' Rin';

    $actual  = isset($inv[$oid_raw]) ? $inv[$oid_raw] : 0;
    $espacio = $maxq - $actual;
    $afford  = $precio > 0 ? intdiv($rin, intval($precio)) : $espacio;

    if ($espacio <= 0) {
        $buy = "<div class=\"sg-buy-status sg-buy-status--max\">Máximo alcanzado</div>";
    } else if ($afford <= 0) {
        $buy = "<div class=\"sg-buy-status sg-buy-status--no\">Rins insuficientes</div>";
    } else {
        $maxbuy = min($espacio, $afford);
        $buy = "<form method=\"post\" action=\"/sg/tienda_rins.php\" class=\"sg-buy\">"
            . "<input type=\"hidden\" name=\"accion\" value=\"comprar_pergamino\">"
            . "<input type=\"hidden\" name=\"objeto\" value=\"$oid\">"
            . "<input class=\"sg-buy-qty\" type=\"number\" name=\"cantidad\" min=\"1\" max=\"$maxbuy\" value=\"1\" title=\"Cantidad (máx. $maxbuy)\">"
            . "<button class=\"sg-btn\" type=\"submit\">Comprar</button>"
            . "</form>";
    }

    $badges = "<span class=\"sg-item-badge\">Pergamino</span>";
    if ($tamano !== '') {
        $badges .= "<span class=\"sg-item-badge sg-item-badge--soft\">$tamano</span>";
    }

    $desc_html   = trim($o['descripcion']) !== '' ? "<p class=\"sg-item-desc\">$desc</p>" : '';
    $efecto_html = trim($o['efecto']) !== '' ? "<div class=\"sg-item-effect\"><span class=\"sg-item-eff-label\">Efecto</span> $efecto</div>" : '';

    $pergaminos_html .= "<article class=\"sg-item\" data-name=\"$data_name\" data-tipo=\"pergamino\">"
        . "<div class=\"sg-item-media\">"
        . "<img class=\"sg-item-img\" src=\"$img\" alt=\"$nombre\" loading=\"lazy\" onerror=\"sgImgFallback(this)\">"
        . "<span class=\"sg-item-cost\">$precio_fmt</span>"
        . "</div>"
        . "<div class=\"sg-item-body\">"
        . "<h3 class=\"sg-item-name\">$nombre</h3>"
        . "<div class=\"sg-item-badges\">$badges</div>"
        . $desc_html
        . $efecto_html
        . "<div class=\"sg-item-meta\">Tienes <strong>$actual</strong> / $maxq</div>"
        . $buy
        . "</div>"
        . "</article>";
}
if ($pergaminos_html === '') {
    $pergaminos_html = "<div class=\"sg-cat-empty\">No hay pergaminos disponibles.</div>";
}

$rin_label  = number_format($rin, 0, ',', '.');
$ryos_label = number_format($ryos, 0, ',', '.');
$tobi_label = number_format($tobi, 0, ',', '.');

eval("\$page = \"".$templates->get("sg_tienda_rins")."\";");
output_page($page);
