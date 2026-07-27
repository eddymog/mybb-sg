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
$accion = $mybb->get_input('accion');
$peti_id = $mybb->get_input('peti_id');

function staff_admin_escape($value) {
    return htmlspecialchars_uni($value);
}

$reload_js = "<script>window.location.href = window.location.pathname;</script>";

if ($accion == 'resolver' && $peti_id) {

    $db->query(" 
        UPDATE `mybb_sg_sg_peticiones` SET `resuelto`=1, `mod_uid`='$uid', `mod_nombre`='$username' WHERE id='$peti_id'
    ");

    eval('$reload_script = $reload_js;');
} else if ($action == 'borrar' && $peti_id) {
    $db->query("
        DELETE FROM mybb_sg_sg_peticiones WHERE uid='$peti_id';
    ");
    eval('$reload_script = $reload_js;');
}

if (is_mod($uid) || is_staff($uid) || is_user($uid)) { 
    $peticiones_li = "";

    // $borrar_a = "$url_page?accion=borrar&peti_id=$pid";

    function print_peticion($nombre_categoria, $categoria, $uid) {
        global $db;
        $query_peticion = $db->query("
            SELECT * FROM mybb_sg_sg_peticiones
            WHERE resuelto=0 AND categoria='$categoria'
        ");

        $peticiones_li = "";
        while ($q = $db->fetch_array($query_peticion)) {
            $pid = $q['id'];
            $u_uid = $q['uid'];
            $categoria_actual = staff_admin_escape($q['categoria']);
            $resumen = staff_admin_escape($q['resumen']);
            $descripcion = nl2br(staff_admin_escape($q['descripcion']));
            $url = trim($q['url']);
            $url_html = "<span>Sin enlace</span>";
            if ($url !== "") {
                $url_segura = staff_admin_escape($url);
                $url_html = "<a href='$url_segura' target='_blank' rel='noopener noreferrer'>$url_segura</a>";
            }
            $nombre_texto = staff_admin_escape($q['nombre']);
            $nombre = "<a class='peti-user' href='/sg/ficha.php?uid=$u_uid' target='_blank' rel='noopener noreferrer'>$nombre_texto</a>";
            $fecha = staff_admin_escape($q['tiempo']);
            
            $peticiones_li .= "<article class='peti-card'>";
            $peticiones_li .= "<div class='peti-head'><h3 class='peti-title'>{$resumen}</h3></div>";
            $peticiones_li .= "<p class='peti-meta'><strong>Usuario:</strong> {$nombre} <strong>UID:</strong> {$u_uid}<br><strong>Categoria:</strong> {$categoria_actual}<br><strong>Fecha:</strong> {$fecha}<br><strong>URL:</strong> {$url_html}</p>";
            $peticiones_li .= "<p class='peti-description'>{$descripcion}</p>";
            
            if (is_mod($uid) || is_staff($uid)) {
                $url_page = "/sg/admin/peticiones_admin.php";
                $resolver_a = "$url_page?accion=resolver&peti_id=$pid";
                $peticiones_li .= "<div class='peti-actions'><a class='peti-action' href='$resolver_a'>Resolver</a></div>";
            }
            $peticiones_li .= "</article>";
        
        }

        if ($peticiones_li != "") {
            $nombre_categoria_seguro = staff_admin_escape($nombre_categoria);
            return "<section class='peti-section'><div class='peti-section-head'><h2 class='peti-section-title'>{$nombre_categoria_seguro}</h2></div><div class='peti-section-body'>{$peticiones_li}</div></section>";
        } else {
            return "";
        }
    }


    $peticiones_li .= print_peticion('Moderación de Ficha', 'ficha', $uid);
    $peticiones_li .= print_peticion('Moderación de Temas', 'tema', $uid);
    $peticiones_li .= print_peticion('Moderación de Combate', 'combate', $uid);
    $peticiones_li .= print_peticion('Moderación de Técnica', 'tecnica', $uid);
    $peticiones_li .= print_peticion('Solicitudes de Afiliación', 'afiliados', $uid);
    $peticiones_li .= print_peticion('Otras Moderaciones', 'otros', $uid);

    eval("\$page = \"".$templates->get("staff_peticiones_admin")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
