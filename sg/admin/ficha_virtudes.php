<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 * Modificar virtudes y defectos asignados a una ficha específica.
 * Comparte el catálogo con mybb_sg_sg_virtudes (ver gestionar_virtudes.php);
 * la asignación por ficha vive en mybb_sg_sg_virtudes_usuarios (uid, virtud_id).
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'ficha_virtudes.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/sg_functions.php";

global $templates, $mybb, $db;
$uid      = $mybb->user['uid'];
$username = $mybb->user['username'];
$es_staff = (is_mod($uid) || is_staff($uid));

$user_fid = trim($mybb->get_input('fid'));

// Datos del formulario
$ficha_id  = trim($_POST["ficha_id"]);
$accion    = $_POST["accion"];
$quitar    = isset($_POST["quitar"]) && is_array($_POST["quitar"]) ? $_POST["quitar"] : array();
$virtud_add = trim($_POST["virtud_add"]);
$staff     = trim($_POST["staff"]);
$razon     = trim($_POST["razon"]);

$log_var = '';
$error_var = '';
$reload_script = '';
$log = null;
$error = '';

if ($accion && $ficha_id && $staff && $razon && $es_staff) {

    // Nombre de la ficha (para el log)
    $f_var = null;
    $query_ficha = $db->query("SELECT * FROM mybb_sg_sg_fichas WHERE fid='$ficha_id'");
    while ($f = $db->fetch_array($query_ficha)) {
        $f_var = $f;
    }
    $ficha_nombre = $f_var ? $f_var['nombre'] : '';

    // ── Quitar virtudes/defectos marcados ───────────────────────
    if ($accion == 'Guardar') {
        $detalle = "";
        $cambios = 0;
        foreach ($quitar as $vid_raw) {
            $vid = $db->escape_string(trim($vid_raw));
            if ($vid === '') { continue; }

            $vinfo = select_one_query_with_id('mybb_sg_sg_virtudes', 'virtud_id', $vid);
            $db->query("DELETE FROM `mybb_sg_sg_virtudes_usuarios` WHERE uid='$ficha_id' AND virtud_id='$vid'");
            $detalle .= "-- Quitar: $vid" . ($vinfo ? " ({$vinfo['nombre']})" : "") . "\n";
            $cambios++;
        }
        if ($cambios > 0) {
            $log = "Virtudes/defectos de ficha UID $ficha_id ($ficha_nombre):\n" . $detalle;
        }
    }

    // ── Añadir una virtud/defecto del catálogo ──────────────────
    if ($accion == 'Añadir') {
        if ($virtud_add === '') {
            $error = "Debes elegir una virtud o defecto para añadir.";
        } else {
            $vid_esc = $db->escape_string($virtud_add);
            $vinfo = select_one_query_with_id('mybb_sg_sg_virtudes', 'virtud_id', $vid_esc);

            if (!$vinfo) {
                $error = "Esa virtud/defecto no existe en el catálogo.";
            } else {
                $ya_tiene = false;
                $q_check = $db->query("SELECT id FROM mybb_sg_sg_virtudes_usuarios WHERE uid='$ficha_id' AND virtud_id='$vid_esc'");
                while ($c = $db->fetch_array($q_check)) { $ya_tiene = true; }

                if ($ya_tiene) {
                    $error = "La ficha ya tiene asignada \"" . $vinfo['nombre'] . "\".";
                } else {
                    $db->query("INSERT INTO `mybb_sg_sg_virtudes_usuarios` (`virtud_id`, `uid`) VALUES ('$vid_esc', '$ficha_id')");
                    $log = "Virtudes/defectos de ficha UID $ficha_id ($ficha_nombre):\n-- Añadir: $vid_esc (" . $vinfo['nombre'] . ")\n";
                }
            }
        }
    }

    // ── Resultado ────────────────────────────────────────────────
    if ($log !== null) {
        $log_db   = addslashes($log);
        $staff_db = addslashes($staff);
        $razon_db = addslashes($razon);
        $user_db  = addslashes($username);
        $db->query("
            INSERT INTO `mybb_sg_sg_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES
            ('$staff_db', '$user_db', '$razon_db', '$log_db')
        ");
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
    $lista_virtudes = '';
    $lista_defectos = '';
    $opciones_add = '';

    if ($user_fid != '') {
        $query_ficha = $db->query("SELECT * FROM mybb_sg_sg_fichas WHERE fid='$user_fid'");
        while ($f = $db->fetch_array($query_ficha)) {
            $ficha = $f;
        }

        // Asignadas actualmente
        $asignados = array();
        $query_asig = $db->query("
            SELECT v.virtud_id, v.nombre, v.puntos, v.exclusivo
            FROM mybb_sg_sg_virtudes_usuarios vu
            INNER JOIN mybb_sg_sg_virtudes v ON v.virtud_id = vu.virtud_id
            WHERE vu.uid='$user_fid'
            ORDER BY v.nombre ASC
        ");
        while ($r = $db->fetch_array($query_asig)) {
            $vid_r    = htmlspecialchars($r['virtud_id'], ENT_QUOTES);
            $nombre_r = htmlspecialchars($r['nombre'], ENT_QUOTES);
            $puntos_r = intval($r['puntos']);
            $costo_lbl = ($puntos_r >= 0 ? '+' : '−') . abs($puntos_r);
            $excl_r = intval($r['exclusivo']) === 1 ? "<span class=\"sg-vt-tag\">Exclusivo</span>" : '';

            $row = "<div class=\"sg-vt-row\">"
                . "<span class=\"sg-vt-id\">$vid_r</span>"
                . "<span class=\"sg-vt-name\">$nombre_r$excl_r</span>"
                . "<span class=\"sg-vt-cost\">$costo_lbl</span>"
                . "<label class=\"sg-row-del\" title=\"Marcar para quitar\"><input type=\"checkbox\" name=\"quitar[]\" value=\"$vid_r\">&times;</label>"
                . "</div>";

            $asignados[$r['virtud_id']] = true;
            if ($puntos_r >= 0) { $lista_virtudes .= $row; } else { $lista_defectos .= $row; }
        }
        if ($lista_virtudes === '') { $lista_virtudes = "<div class=\"sg-vt-empty\">Sin virtudes asignadas.</div>"; }
        if ($lista_defectos === '') { $lista_defectos = "<div class=\"sg-vt-empty\">Sin defectos asignados.</div>"; }

        // Catálogo disponible para añadir (excluye lo ya asignado)
        $opt_virtudes = '';
        $opt_defectos = '';
        $query_cat = $db->query("SELECT virtud_id, nombre, puntos FROM mybb_sg_sg_virtudes ORDER BY nombre ASC");
        while ($c = $db->fetch_array($query_cat)) {
            if (isset($asignados[$c['virtud_id']])) { continue; }
            $vid_c = htmlspecialchars($c['virtud_id'], ENT_QUOTES);
            $nom_c = htmlspecialchars($c['nombre'], ENT_QUOTES);
            $pts_c = intval($c['puntos']);
            $lbl_c = $nom_c . ' (' . ($pts_c >= 0 ? '+' : '−') . abs($pts_c) . ')';
            $opt = "<option value=\"$vid_c\">$lbl_c</option>";
            if ($pts_c >= 0) { $opt_virtudes .= $opt; } else { $opt_defectos .= $opt; }
        }
        $opciones_add = "<optgroup label=\"Virtudes\">$opt_virtudes</optgroup><optgroup label=\"Defectos\">$opt_defectos</optgroup>";
    }

    eval('$fid = $user_fid;');
    eval('$listaVirtudes = $lista_virtudes;');
    eval('$listaDefectos = $lista_defectos;');
    eval('$opcionesAdd = $opciones_add;');
    eval("\$page = \"".$templates->get("staff_ficha_virtudes")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
