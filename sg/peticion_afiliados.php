<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 * Solicitud pública de afiliación — sin necesidad de registrarse. Ver
 * docs/afiliados_diseno.md. Inserta en mybb_sg_sg_peticiones con
 * categoria='afiliados' para que Staff la revise desde la consola de
 * peticiones ya existente (sg/admin/peticiones_admin.php); no toca
 * mybb_sg_sg_afiliados directamente — es una petición normal más.
 *
 * No distingue "grande"/"pequeño": esa nomenclatura es solo para la
 * herramienta de Staff (gestionar_afiliados.php). Si alguien quiere un nivel
 * en particular, lo aclara en el mensaje.
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'peticion_afiliados.php');
require_once "./../global.php";
require_once "./functions/sg_functions.php";

global $templates, $mybb, $db;

$accion = isset($_POST['accion']) ? $_POST['accion'] : '';

if ($accion === 'enviar') {
    // Honeypot: campo oculto que un humano nunca completa (CSS lo esconde en
    // el template). Si viene lleno, es un bot: se responde éxito igual, sin
    // insertar nada, para no delatar el filtro.
    $honeypot    = isset($_POST['sitio_web']) ? trim($_POST['sitio_web']) : '';
    $nombre_foro = isset($_POST['nombre_foro']) ? trim($_POST['nombre_foro']) : '';
    $url_foro    = isset($_POST['url_foro']) ? trim($_POST['url_foro']) : '';
    $contacto    = isset($_POST['contacto']) ? trim($_POST['contacto']) : '';
    $mensaje     = isset($_POST['mensaje']) ? trim($_POST['mensaje']) : '';

    $ip = isset($_SERVER['REMOTE_ADDR']) ? substr($_SERVER['REMOTE_ADDR'], 0, 45) : '';

    $mensaje_exito = "¡Tu solicitud fue enviada! Para coordinar el logo y los detalles de la afiliación, pueden contactar a cualquiera de nuestros administradores (daimyo); por defecto, a @kurosame.";

    if ($honeypot !== '') {
        // Bot detectado por el honeypot: mismo mensaje de éxito, sin insertar nada.
        $mensaje_redireccion = $mensaje_exito;
        eval("\$page = \"".$templates->get("sg_redireccion")."\";");
        output_page($page);
        exit;
    }

    if ($nombre_foro === '' || $url_foro === '' || $contacto === '') {
        $mensaje_redireccion = "Debe completarse el nombre del foro, la URL y un medio de contacto para poder enviar la solicitud.";
        eval("\$page = \"".$templates->get("sg_redireccion")."\";");
        output_page($page);
        exit;
    }

    if (!sg_afiliados_rate_limit_ok($ip, 10)) {
        $mensaje_redireccion = "Ya se envió una solicitud hace poco. Debe esperarse unos minutos antes de volver a intentarlo.";
        eval("\$page = \"".$templates->get("sg_redireccion")."\";");
        output_page($page);
        exit;
    }

    $resumen = $nombre_foro . ' solicita afiliación';
    $descripcion = 'Contacto: ' . $contacto . "\n\n" . ($mensaje !== '' ? $mensaje : '(sin mensaje adicional)');

    $nombre_esc      = $db->escape_string($contacto);
    $resumen_esc     = $db->escape_string($resumen);
    $descripcion_esc = $db->escape_string($descripcion);
    $url_esc         = $db->escape_string($url_foro);

    $db->query("
        INSERT INTO `mybb_sg_sg_peticiones` (`uid`, `nombre`, `categoria`, `resumen`, `descripcion`, `url`)
        VALUES ('0', '$nombre_esc', 'afiliados', '$resumen_esc', '$descripcion_esc', '$url_esc')
    ");

    $mensaje_redireccion = $mensaje_exito;
    eval("\$page = \"".$templates->get("sg_redireccion")."\";");
    output_page($page);
    exit;
}

eval("\$page = \"".$templates->get("sg_peticion_afiliados")."\";");
output_page($page);
