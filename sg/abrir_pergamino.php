<?php
/**
 * MyBB 1.8
 *
 * Apertura de un pergamino de gacha (AJAX/JSON). El sorteo se decide 100% en
 * el servidor (sg_gacha_abrir en sg_functions.php); este archivo solo valida
 * la sesión y traduce la respuesta a JSON.
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'abrir_pergamino.php');
require_once "./../global.php";
require_once "./functions/sg_functions.php";

global $mybb, $db;

function sg_pergamino_out($arr) {
    while (ob_get_level() > 0) { @ob_end_clean(); }
    @header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr);
    exit;
}

$uid = intval($mybb->user['uid']);
$pergamino_id = isset($_POST['pergamino_id']) ? trim($_POST['pergamino_id']) : '';

if ($uid <= 0) { sg_pergamino_out(array('ok' => false, 'msg' => 'Debes iniciar sesión.')); }
if (!in_array($pergamino_id, sg_gacha_pergamino_ids(), true)) {
    sg_pergamino_out(array('ok' => false, 'msg' => 'Pergamino inválido.'));
}
if (!does_ficha_exist($uid)) {
    sg_pergamino_out(array('ok' => false, 'msg' => 'No tienes una ficha activa.'));
}

$resultado = sg_gacha_abrir($uid, $pergamino_id);
sg_pergamino_out($resultado);
