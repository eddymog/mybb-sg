<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'ficha_objetos.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/sg_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$es_staff = (is_mod($uid) || is_staff($uid));

$user_fid = trim($mybb->get_input('fid'));

// Datos del formulario
$ficha_id        = trim($_POST["ficha_id"]);
$accion          = $_POST["accion"];
$cantidades      = isset($_POST["cant"]) && is_array($_POST["cant"]) ? $_POST["cant"] : array();
$objeto_input    = trim($_POST["objeto_input"]);
$objeto_cantidad = intval($_POST["objeto_cantidad"]);
$staff           = trim($_POST["staff"]);
$razon           = trim($_POST["razon"]);

$log_var = '';
$error_var = '';
$reload_script = '';
$log = null;
$error = '';

if ($accion && $ficha_id && $es_staff) {

    // Nombre de la ficha (para el log)
    $f_var = null;
    $query_ficha = $db->query("SELECT * FROM mybb_sg_sg_fichas WHERE fid='$ficha_id'");
    while ($f = $db->fetch_array($query_ficha)) {
        $f_var = $f;
    }
    $ficha_nombre = $f_var ? $f_var['nombre'] : '';

    // ── Guardar cambios de cantidades de la tabla ──────────────
    if ($accion == 'Guardar' && $staff && $razon) {
        // Cantidades actuales para detectar cambios reales
        $actuales = array();
        $query_actual = $db->query("SELECT objeto_id, cantidad FROM mybb_sg_sg_inventario WHERE uid='$ficha_id'");
        while ($a = $db->fetch_array($query_actual)) {
            $actuales[$a['objeto_id']] = intval($a['cantidad']);
        }

        $detalle = "";
        $cambios = 0;
        $grupo = uniqid();
        // inventario es InnoDB -> transacción real para todo el guardado.
        $db->query("START TRANSACTION");
        foreach ($cantidades as $oid_raw => $qty) {
            $oid_raw = trim($oid_raw);
            if ($oid_raw === '' || !array_key_exists($oid_raw, $actuales)) {
                continue;
            }
            $qty    = intval($qty);
            $actual = $actuales[$oid_raw];
            if ($qty < 0) { $qty = 0; }

            if ($qty != $actual) {
                sg_inventario_set_cantidad($ficha_id, $oid_raw, $qty, 'staff', SG_ORIGEN_FICHA_OBJETOS, $razon, $grupo);
                $detalle .= ($qty > 0) ? "-- $oid_raw: $actual -> $qty\n" : "-- $oid_raw = eliminado\n";
                $cambios++;
            }
        }
        $db->query("COMMIT");

        // Solo registrar/auditar/alertar si de verdad hubo cambios
        if ($cambios > 0) {
            $log = "Cambios de inventario para ficha UID $ficha_id ($ficha_nombre):\n" . $detalle;
        }
    }

    // ── Añadir un objeto por ID ────────────────────────────────
    if ($accion == 'Añadir' && $staff && $razon) {
        if ($objeto_input === '') {
            $error = "Debes indicar un ID de objeto.";
        } else {
            $objeto_esc = $db->escape_string($objeto_input);

            // Validar que el objeto exista en la base de datos
            $existe_obj = false;
            $query_obj = $db->query("SELECT id FROM mybb_sg_sg_objetos WHERE objeto_id='$objeto_esc'");
            while ($o = $db->fetch_array($query_obj)) {
                $existe_obj = true;
            }

            if (!$existe_obj) {
                $error = "El objeto '$objeto_input' no existe en la base de datos.";
            } else {
                $cant_add = $objeto_cantidad > 0 ? $objeto_cantidad : 1;

                $has = false;
                $actual = 0;
                $query_inv = $db->query("SELECT cantidad FROM mybb_sg_sg_inventario WHERE uid='$ficha_id' AND objeto_id='$objeto_esc'");
                while ($q = $db->fetch_array($query_inv)) {
                    $has = true;
                    $actual = intval($q['cantidad']);
                }

                $nueva = $actual + $cant_add;
                sg_inventario_set_cantidad($ficha_id, $objeto_input, $nueva, 'staff', SG_ORIGEN_FICHA_OBJETOS, $razon);
                $log = "Inventario ficha UID $ficha_id ($ficha_nombre):\n-- Añadir $cant_add x $objeto_input\n";
            }
        }
    }

    // ── Resultado ──────────────────────────────────────────────
    if ($log !== null) {
        if (is_staff($uid)) {
            $db->query("INSERT INTO `mybb_sg_sg_audit_consola` (`staff`, `razon`, `log`) VALUES ('$staff', '$razon', '$log');");
        }
        if (is_mod($uid)) {
            $db->query("INSERT INTO `mybb_sg_sg_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES ('$staff', '$username', '$razon', '$log');");
        }
        $reload_js = "<script>window.location.href = window.location.pathname + '?fid=$ficha_id';</script>";
        eval('$log_var = $log;');
        eval('$reload_script = $reload_js;');
    } else if ($error !== '') {
        eval('$error_var = $error;');
    }
}

// ── Render ────────────────────────────────────────────────────
if ($es_staff) {
    $ficha = null;
    $inventario_objetos = '';

    if ($user_fid != '') {
        $query_ficha = $db->query("SELECT * FROM mybb_sg_sg_fichas WHERE fid='$user_fid'");
        while ($f = $db->fetch_array($query_ficha)) {
            $ficha = $f;
        }

        $query_inv = $db->query("
            SELECT i.objeto_id AS oid, i.cantidad AS cantidad, o.nombre AS nombre, o.cantidadMaxima AS maxq
            FROM mybb_sg_sg_inventario i
            LEFT JOIN mybb_sg_sg_objetos o ON o.objeto_id = i.objeto_id
            WHERE i.uid='$user_fid'
            ORDER BY o.nombre
        ");
        while ($r = $db->fetch_array($query_inv)) {
            $oid_r    = $r['oid'];
            $nombre_r = ($r['nombre'] !== null && $r['nombre'] !== '') ? $r['nombre'] : '(desconocido)';
            $cant_r   = intval($r['cantidad']);
            $max_r    = ($r['maxq'] === null || $r['maxq'] === '') ? '?' : $r['maxq'];
            $inventario_objetos .= "<div class=\"sg-inv-row\">"
                . "<span class=\"sg-inv-id\">$oid_r</span>"
                . "<span class=\"sg-inv-name\">$nombre_r</span>"
                . "<span class=\"sg-qty-wrap\"><input class=\"sg-qty\" type=\"number\" min=\"0\" name=\"cant[$oid_r]\" value=\"$cant_r\"><span class=\"sg-qty-max\">/ $max_r</span></span>"
                . "<button type=\"button\" class=\"sg-row-del\" title=\"Vaciar (eliminar al guardar)\" onclick=\"sgZero(this)\">&times;</button>"
                . "</div>";
        }
        if ($inventario_objetos === '') {
            $inventario_objetos = "<div class=\"sg-inv-empty\">Sin objetos en el inventario.</div>";
        }
    }

    eval('$fid = $user_fid;');
    eval("\$page = \"".$templates->get("staff_ficha_objetos")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
