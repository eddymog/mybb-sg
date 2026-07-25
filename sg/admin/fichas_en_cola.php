<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'fichas_en_cola.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/sg_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$action = $mybb->get_input('action');
$fid = $mybb->get_input('fid');
$villa = $mybb->get_input('villa');

function staff_escape($value) {
    return htmlspecialchars_uni($value);
}

function villa_label($villa) {
    $labels = array(
        '1' => 'Konoha',
        '3' => 'Kiri',
        '4' => 'Iwa',
        '5' => 'Kumo',
        '7' => 'Sin Aldea'
    );

    if (isset($labels[$villa])) {
        return $labels[$villa];
    }

    return 'Sin asignar';
}

$reload_js = "<script>window.location.href = window.location.pathname;</script>";

if ($action == 'aprobar' && $fid && $villa) {
    $usergroup = '2';

    switch ($villa) {
        case '1':
            $usergroup = '9'; // konoha
            break;
        case '3':
            $usergroup = '8'; // kiri
            break;
        case '4':
            $usergroup = '14'; // iwa
            break;
        case '5':
            $usergroup = '15'; // kumo
            break;
        case '7':
            $usergroup = '12'; // sin aldea
            break;
        default:
            $usergroup = '2'; // registered
    }

    // Solo aprobar si sigue pendiente (preserva el guard WHERE moderated='no_moderacion').
    $mod_actual = null;
    $qm = $db->query("SELECT moderated FROM `mybb_sg_sg_fichas` WHERE fid='$fid'");
    while ($rm = $db->fetch_array($qm)) { $mod_actual = $rm['moderated']; }
    if ($mod_actual === 'no_moderacion') {
        sg_ficha_set_campo($fid, 'moderated', $username, 'staff', SG_ORIGEN_APROBACION, 'Aprobación de ficha');
    }
    $db->query("
        UPDATE `mybb_sg_users` SET `usergroup`='$usergroup' WHERE uid=$fid;
    ");
    eval('$reload_script = $reload_js;');
} else if ($action == 'borrar' && $fid) {
    $db->query("
        DELETE FROM mybb_sg_sg_fichas WHERE moderated='no_moderacion' AND fid='$fid';
    ");
    eval('$reload_script = $reload_js;');
    
}

if (is_mod($uid) || is_staff($uid) || is_user($uid)) { 
    $fichas_li = "";
    $query_fichas = $db->query("
        SELECT * FROM mybb_sg_sg_fichas WHERE moderated='no_moderacion'
    ");
    while ($f = $db->fetch_array($query_fichas)) {
        $fid = $f['fid'];
        $nombre = staff_escape($f['nombre']);
        $villa = $f['villa'];
        $villa_nombre = staff_escape(villa_label($villa));
        $url = "/sg/admin/fichas_en_cola.php";
        $aprobar_a = "$url?action=aprobar&fid=$fid&villa=$villa";
        $borrar_a = "$url?action=borrar&fid=$fid";
        $fichas_li .= "<article class='queue-card'>";
        $fichas_li .= "<div class='queue-head'><h3 class='queue-title'>Ficha de <span class='queue-uid'>{$nombre}</span></h3></div>";
        $fichas_li .= "<div class='queue-grid'>";
        $fichas_li .= "<div class='queue-meta'><strong>UID:</strong> <a href='/member.php?action=profile&uid=$fid' target='_blank' rel='noopener noreferrer'>$fid</a></div>";
        $fichas_li .= "<div class='queue-meta'><strong>Villa:</strong> {$villa_nombre}</div>";
        $fichas_li .= "<div class='queue-meta'><strong>Cuenta:</strong> {$nombre}</div>";
        $fichas_li .= "<div class='queue-meta'><strong>Ficha:</strong> <a href='/sg/ficha.php?uid=$fid' target='_blank' rel='noopener noreferrer'>Abrir ficha</a></div>";
        $fichas_li .= "</div>";
        if (is_mod($uid) || is_staff($uid)) {
            $fichas_li .= "<div class='queue-actions'>";
            $fichas_li .= "<a class='queue-action queue-action--primary' href='$aprobar_a'>Aprobar</a>";
            $fichas_li .= "<a class='queue-action queue-action--danger' href='$borrar_a'>Borrar</a>";
            $fichas_li .= "</div>";
        }
        $fichas_li .= "</article>";
    }
    eval('$li_fichas = $fichas_li;');
    eval("\$page = \"".$templates->get("staff_fichas_en_cola")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
