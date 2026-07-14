<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 * Subida de imágenes al servidor (images/sg/uploads).
 * - Solo formatos de imagen comunes.
 * - Nombres únicos: no se sobrescribe; si ya existe, avisa.
 * - No se permite borrar; solo agregar.
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'subir_imagenes.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/sg_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$es_staff = (is_mod($uid) || is_staff($uid));

$uploads_fs  = MYBB_ROOT . 'images/sg/uploads/';
$uploads_url = '/images/sg/uploads/';
$allowed     = array('jpg', 'jpeg', 'png', 'gif', 'webp');
$max_size    = 8 * 1024 * 1024; // 8 MB

$mensaje = '';
$mensaje_tipo = '';   // 'ok' | 'error'
$url_subida = '';

if ($es_staff && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] === UPLOAD_ERR_NO_FILE) {
        $mensaje = 'Selecciona una imagen para subir.';
        $mensaje_tipo = 'error';
    } else if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
        $mensaje = 'Error al subir el archivo (código ' . intval($_FILES['imagen']['error']) . ').';
        $mensaje_tipo = 'error';
    } else {
        $file = $_FILES['imagen'];

        // Nombre seguro a partir del nombre original elegido
        $nombre = strtolower(basename($file['name']));
        $nombre = preg_replace('/[^a-z0-9._-]/', '-', $nombre);
        $nombre = preg_replace('/-+/', '-', $nombre);
        $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));

        if ($nombre === '' || $ext === '') {
            $mensaje = 'Nombre de archivo inválido.';
            $mensaje_tipo = 'error';
        } else if (!in_array($ext, $allowed)) {
            $mensaje = 'Formato no permitido. Usa: ' . implode(', ', $allowed) . '.';
            $mensaje_tipo = 'error';
        } else if ($file['size'] > $max_size) {
            $mensaje = 'La imagen supera el tamaño máximo de 8 MB.';
            $mensaje_tipo = 'error';
        } else if (!is_uploaded_file($file['tmp_name']) || getimagesize($file['tmp_name']) === false) {
            $mensaje = 'El archivo no es una imagen válida.';
            $mensaje_tipo = 'error';
        } else {
            if (!is_dir($uploads_fs)) {
                @mkdir($uploads_fs, 0755, true);
            }

            $target = $uploads_fs . $nombre;

            if (file_exists($target)) {
                // Nombres únicos: no se sobrescribe
                $mensaje = "Ya existe una imagen con el nombre \"$nombre\". No se sobrescriben imágenes; renómbrala y vuelve a intentar.";
                $mensaje_tipo = 'error';
            } else if (move_uploaded_file($file['tmp_name'], $target)) {
                @chmod($target, 0644);
                $url_subida = $uploads_url . $nombre;

                // Auto-optimiza (redimensiona + recomprime) para que las imágenes
                // nunca lleguen pesadas al foro. Si falla (formato no soportado,
                // GIF, etc.) la subida igual se da por buena con el archivo tal cual.
                $opt = sg_optimizar_imagen_archivo($target, 1200, 78);
                if ($opt['ok'] && $opt['bytes_despues'] < $opt['bytes_antes']) {
                    $ahorro_pct = round((1 - ($opt['bytes_despues'] / $opt['bytes_antes'])) * 100);
                    $mensaje = "Imagen subida y optimizada como \"$nombre\" ("
                        . round($opt['bytes_antes'] / 1024) . " KB → " . round($opt['bytes_despues'] / 1024) . " KB, -$ahorro_pct%).";
                } else {
                    $mensaje = "Imagen subida correctamente como \"$nombre\".";
                }
                $mensaje_tipo = 'ok';
            } else {
                $mensaje = 'No se pudo guardar la imagen. Revisa los permisos de la carpeta de subidas.';
                $mensaje_tipo = 'error';
            }
        }
    }
}

// Galería de imágenes ya subidas (solo lectura)
$galeria = '';
if ($es_staff && is_dir($uploads_fs)) {
    $items = array();
    foreach (scandir($uploads_fs) as $f) {
        if ($f === '.' || $f === '..') {
            continue;
        }
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed) || !is_file($uploads_fs . $f)) {
            continue;
        }
        $items[$f] = filemtime($uploads_fs . $f);
    }
    arsort($items); // más recientes primero

    foreach ($items as $nombre_img => $mtime) {
        $url_img = $uploads_url . $nombre_img;
        $galeria .= "<figure class=\"sg-gal-item\">"
            . "<a href=\"$url_img\" target=\"_blank\"><img class=\"sg-gal-img\" src=\"$url_img\" alt=\"$nombre_img\" loading=\"lazy\"></a>"
            . "<input class=\"sg-gal-url\" type=\"text\" readonly value=\"$url_img\" onclick=\"this.select()\">"
            . "</figure>";
    }

    if ($galeria === '') {
        $galeria = "<div class=\"sg-gal-empty\">Aún no hay imágenes subidas.</div>";
    }
}

if ($es_staff) {
    eval("\$page = \"".$templates->get("staff_subir_imagenes")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
