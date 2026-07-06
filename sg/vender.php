<?php
/**
 * MyBB 1.8
 *
 * Venta de objetos del inventario (AJAX/JSON). El usuario solo puede vender
 * de SU propio inventario. Precio de venta = 50% del coste base de compra.
 * Los objetos no comprables (coste >= 99999) no se pueden vender.
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'vender.php');
require_once "./../global.php";
require_once "./functions/sg_functions.php";

global $mybb, $db;

function sg_vender_out($arr) {
    while (ob_get_level() > 0) { @ob_end_clean(); }
    @header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr);
    exit;
}

$uid      = intval($mybb->user['uid']);
$objeto   = isset($_POST['objeto']) ? trim($_POST['objeto']) : '';
$cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 0;

if ($uid <= 0)                        { sg_vender_out(array('ok' => false, 'msg' => 'Debes iniciar sesión.')); }
if ($objeto === '' || $cantidad < 1)  { sg_vender_out(array('ok' => false, 'msg' => 'Datos inválidos.')); }

$objeto_esc = $db->escape_string($objeto);
$lock = "sg_vender_$uid";

// Lock por usuario: serializa ventas concurrentes.
$got = 0;
$rl = $db->query("SELECT GET_LOCK('$lock', 5) AS l");
while ($r = $db->fetch_array($rl)) { $got = intval($r['l']); }
if ($got !== 1) { sg_vender_out(array('ok' => false, 'msg' => 'No se pudo procesar la venta. Intenta de nuevo.')); }

// Precio base del objeto.
$coste_base = null;
$qo = $db->query("SELECT coste FROM `mybb_sg_sg_objetos` WHERE objeto_id='$objeto_esc'");
while ($o = $db->fetch_array($qo)) { $coste_base = intval($o['coste']); }
if ($coste_base === null) {
    $db->query("SELECT RELEASE_LOCK('$lock')");
    sg_vender_out(array('ok' => false, 'msg' => 'El objeto no existe.'));
}
if ($coste_base >= 99999) {
    $db->query("SELECT RELEASE_LOCK('$lock')");
    sg_vender_out(array('ok' => false, 'msg' => 'Este objeto no se puede vender.'));
}
$precio_venta = (int) floor($coste_base * 0.5);

// Cantidad poseída, fresca dentro del lock.
$actual = 0; $has = false;
$qi = $db->query("SELECT cantidad FROM `mybb_sg_sg_inventario` WHERE uid='$uid' AND objeto_id='$objeto_esc'");
while ($i = $db->fetch_array($qi)) { $has = true; $actual = intval($i['cantidad']); }
if (!$has || $actual <= 0) {
    $db->query("SELECT RELEASE_LOCK('$lock')");
    sg_vender_out(array('ok' => false, 'msg' => 'No tienes ese objeto.'));
}
if ($cantidad > $actual) {
    $db->query("SELECT RELEASE_LOCK('$lock')");
    sg_vender_out(array('ok' => false, 'msg' => 'No tienes esa cantidad.'));
}

// Aplica la venta: descuenta inventario (borra si llega a 0) y suma ryos.
$nueva_cant = $actual - $cantidad;
if ($nueva_cant > 0) {
    $db->query("UPDATE `mybb_sg_sg_inventario` SET cantidad='$nueva_cant' WHERE uid='$uid' AND objeto_id='$objeto_esc'");
} else {
    $db->query("DELETE FROM `mybb_sg_sg_inventario` WHERE uid='$uid' AND objeto_id='$objeto_esc'");
}

$total_venta = $precio_venta * $cantidad;
$ryos_actual = 0;
$qr = $db->query("SELECT ryos FROM `mybb_sg_sg_fichas` WHERE fid='$uid'");
while ($r = $db->fetch_array($qr)) { $ryos_actual = intval($r['ryos']); }
$nuevo_ryos = $ryos_actual + $total_venta;
$db->query("UPDATE `mybb_sg_sg_fichas` SET ryos='$nuevo_ryos' WHERE fid='$uid'");

$db->query("SELECT RELEASE_LOCK('$lock')");

sg_vender_out(array(
    'ok'             => true,
    'nueva_cantidad' => $nueva_cant,
    'nuevos_ryos'    => $nuevo_ryos,
    'total'          => $total_venta,
    'precio_unit'    => $precio_venta
));
