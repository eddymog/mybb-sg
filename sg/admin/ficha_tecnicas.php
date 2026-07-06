<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 * Modificar técnicas aprendidas de una ficha específica.
 * Catálogo en mybb_sg_sg_tecnicas; asignación por ficha en mybb_sg_sg_tec_aprendidas (uid, tid).
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'ficha_tecnicas.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/sg_functions.php";

global $templates, $mybb, $db;
$uid      = $mybb->user['uid'];
$es_staff = (is_mod($uid) || is_staff($uid));

$user_fid = trim($mybb->get_input('fid'));

// Datos del formulario
$ficha_id     = trim($_POST["ficha_id"]);
$accion       = $_POST["accion"];
$quitar       = isset($_POST["quitar"]) && is_array($_POST["quitar"]) ? $_POST["quitar"] : array();
$tecnica_add  = trim($_POST["tecnica_add"]);
$tecnicas_bulk = trim($_POST["tecnicas_bulk"]);
$staff        = trim($mybb->user['username']);
$razon        = trim($_POST["razon"]);

$log_var = '';
$error_var = '';
$reload_script = '';
$log = null;
$error = '';

// Registra el cambio en los logs dedicados de técnicas (staff + mod)
function fx_tec_audit($db, $uid_actor, $staff, $razon, $log_texto) {
    $staff_db = addslashes($staff);
    $razon_db = addslashes($razon);
    $log_db   = addslashes($log_texto);
    if (is_staff($uid_actor)) {
        $db->query("INSERT INTO `mybb_sg_sg_audit_consola_tec` (`staff`, `razon`, `log`) VALUES ('$staff_db', '$razon_db', '$log_db')");
    }
    if (is_mod($uid_actor)) {
        $db->query("INSERT INTO `mybb_sg_sg_audit_consola_tec_mod` (`staff`, `razon`, `log`) VALUES ('$staff_db', '$razon_db', '$log_db')");
    }
}

if ($accion && $ficha_id && $staff && $razon && $es_staff) {

    $f_var = select_one_query_with_id('mybb_sg_sg_fichas', 'fid', $ficha_id);
    $ficha_nombre = $f_var ? $f_var['nombre'] : '';

    // ── Quitar técnicas marcadas ─────────────────────────────────
    if ($accion == 'Guardar') {
        $detalle = "";
        $cambios = 0;
        foreach ($quitar as $tid_raw) {
            $tid = $db->escape_string(trim($tid_raw));
            if ($tid === '') { continue; }

            $tinfo = select_one_query_with_id('mybb_sg_sg_tecnicas', 'tid', $tid);
            $db->query("DELETE FROM `mybb_sg_sg_tec_aprendidas` WHERE uid='$ficha_id' AND tid='$tid'");
            $detalle .= "-- Quitar: $tid" . ($tinfo ? " ({$tinfo['nombre']})" : "") . "\n";
            $cambios++;
        }
        if ($cambios > 0) {
            $log = "Técnicas de ficha UID $ficha_id ($ficha_nombre):\n" . $detalle;
        }
    }

    // ── Añadir una técnica del catálogo ──────────────────────────
    if ($accion == 'Añadir') {
        if ($tecnica_add === '') {
            $error = "Debes elegir una técnica para añadir.";
        } else {
            $tid_esc = $db->escape_string($tecnica_add);
            $tinfo = select_one_query_with_id('mybb_sg_sg_tecnicas', 'tid', $tid_esc);

            if (!$tinfo) {
                $error = "Esa técnica no existe en el catálogo.";
            } else {
                $ya_tiene = false;
                $q_check = $db->query("SELECT tid FROM mybb_sg_sg_tec_aprendidas WHERE uid='$ficha_id' AND tid='$tid_esc'");
                while ($c = $db->fetch_array($q_check)) { $ya_tiene = true; }

                if ($ya_tiene) {
                    $error = "La ficha ya tiene aprendida \"" . $tinfo['nombre'] . "\".";
                } else {
                    $db->query("INSERT INTO `mybb_sg_sg_tec_aprendidas` (`tid`, `uid`) VALUES ('$tid_esc', '$ficha_id')");
                    $log = "Técnicas de ficha UID $ficha_id ($ficha_nombre):\n-- Añadir: $tid_esc (" . $tinfo['nombre'] . ")\n";
                }
            }
        }
    }

    // ── Añadir varias técnicas por ID (para lotes conocidos) ─────
    if ($accion == 'AñadirLote') {
        if ($tecnicas_bulk === '') {
            $error = "Escribe al menos un ID de técnica.";
        } else {
            $tids_array = preg_split('/[^A-Za-z0-9_]+/', $tecnicas_bulk, -1, PREG_SPLIT_NO_EMPTY);
            $detalle = "";
            $cambios = 0;
            $omitidos = array();
            foreach ($tids_array as $tid_raw) {
                $tid = $db->escape_string(trim($tid_raw));
                if ($tid === '') { continue; }

                $tinfo = select_one_query_with_id('mybb_sg_sg_tecnicas', 'tid', $tid);
                if (!$tinfo) { $omitidos[] = $tid; continue; }

                $ya_tiene = false;
                $q_check = $db->query("SELECT tid FROM mybb_sg_sg_tec_aprendidas WHERE uid='$ficha_id' AND tid='$tid'");
                while ($c = $db->fetch_array($q_check)) { $ya_tiene = true; }
                if ($ya_tiene) { continue; }

                $db->query("INSERT INTO `mybb_sg_sg_tec_aprendidas` (`tid`, `uid`) VALUES ('$tid', '$ficha_id')");
                $detalle .= "-- Añadir: $tid ({$tinfo['nombre']})\n";
                $cambios++;
            }
            if ($cambios > 0) {
                $log = "Técnicas de ficha UID $ficha_id ($ficha_nombre):\n" . $detalle;
            }
            if (!empty($omitidos)) {
                $error = "IDs no encontrados en el catálogo (omitidos): " . implode(', ', $omitidos);
            }
        }
    }

    // ── Resultado ────────────────────────────────────────────────
    if ($log !== null) {
        fx_tec_audit($db, $uid, $staff, $razon, $log);
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
    $lista_tecnicas = '';
    $opciones_add = '';

    if ($user_fid != '') {
        $query_ficha = $db->query("SELECT * FROM mybb_sg_sg_fichas WHERE fid='$user_fid'");
        while ($f = $db->fetch_array($query_ficha)) {
            $ficha = $f;
        }

        // Aprendidas actualmente, agrupadas por árbol
        $aprendidas = array();
        $grupos = array();
        $orden_grupos = array();
        $query_apr = $db->query("
            SELECT ta.tid, t.nombre, t.arbol, t.rama
            FROM mybb_sg_sg_tec_aprendidas ta
            INNER JOIN mybb_sg_sg_tecnicas t ON t.tid = ta.tid
            WHERE ta.uid='$user_fid'
            ORDER BY t.arbol ASC, t.rama ASC, t.nombre ASC
        ");
        while ($r = $db->fetch_array($query_apr)) {
            $tid_r    = htmlspecialchars($r['tid'], ENT_QUOTES);
            $nombre_r = htmlspecialchars($r['nombre'], ENT_QUOTES);
            $arbol_r  = trim($r['arbol']) !== '' ? $r['arbol'] : 'Sin árbol';
            $arbol_key = htmlspecialchars($arbol_r, ENT_QUOTES);

            $row = "<div class=\"sg-vt-row\">"
                . "<span class=\"sg-vt-id\">$tid_r</span>"
                . "<span class=\"sg-vt-name\">$nombre_r</span>"
                . "<label class=\"sg-row-del\" title=\"Marcar para quitar\"><input type=\"checkbox\" name=\"quitar[]\" value=\"$tid_r\">&times;</label>"
                . "</div>";

            if (!isset($grupos[$arbol_key])) {
                $grupos[$arbol_key] = '';
                $orden_grupos[] = $arbol_key;
            }
            $grupos[$arbol_key] .= $row;
            $aprendidas[$r['tid']] = true;
        }
        if (empty($orden_grupos)) {
            $lista_tecnicas = "<div class=\"sg-vt-empty\">Sin técnicas aprendidas.</div>";
        } else {
            foreach ($orden_grupos as $arbol_key) {
                $lista_tecnicas .= "<div class=\"sg-vt-group\">"
                    . "<div class=\"sg-vt-group-head\">" . ucwords(str_replace('_', ' ', $arbol_key)) . "</div>"
                    . $grupos[$arbol_key]
                    . "</div>";
            }
        }

        // Catálogo disponible para añadir (excluye lo ya aprendido), agrupado por árbol
        $opt_grupos = array();
        $opt_orden = array();
        $query_cat = $db->query("SELECT tid, nombre, arbol, rama FROM mybb_sg_sg_tecnicas ORDER BY arbol ASC, rama ASC, nombre ASC");
        while ($c = $db->fetch_array($query_cat)) {
            if (isset($aprendidas[$c['tid']])) { continue; }
            $tid_c   = htmlspecialchars($c['tid'], ENT_QUOTES);
            $nom_c   = htmlspecialchars($c['nombre'], ENT_QUOTES);
            $arbol_c = trim($c['arbol']) !== '' ? $c['arbol'] : 'Sin árbol';
            $arbol_key = htmlspecialchars($arbol_c, ENT_QUOTES);

            if (!isset($opt_grupos[$arbol_key])) {
                $opt_grupos[$arbol_key] = '';
                $opt_orden[] = $arbol_key;
            }
            $opt_grupos[$arbol_key] .= "<option value=\"$tid_c\">$nom_c ($tid_c)</option>";
        }
        foreach ($opt_orden as $arbol_key) {
            $label = ucwords(str_replace('_', ' ', $arbol_key));
            $opciones_add .= "<optgroup label=\"$label\">" . $opt_grupos[$arbol_key] . "</optgroup>";
        }
    }

    eval('$fid = $user_fid;');
    eval('$listaTecnicas = $lista_tecnicas;');
    eval('$opcionesAdd = $opciones_add;');
    eval("\$page = \"".$templates->get("staff_ficha_tecnicas")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
