<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'censo.php');
require_once "./../global.php";
require "./../inc/config.php";

global $templates, $mybb, $db;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];

function peticiones_escape($value) {
    return htmlspecialchars_uni($value);
}

function peticiones_categoria_label($categoria) {
    $labels = array(
        'ficha' => 'Moderacion de Ficha',
        'tema' => 'Moderacion de Tema',
        'combate' => 'Moderacion de Combate',
        'tecnica' => 'Moderacion de Tecnica',
        'afiliados' => 'Solicitud de Afiliación',
        'otros' => 'Otros'
    );

    if (isset($labels[$categoria])) {
        return $labels[$categoria];
    }

    return ucfirst($categoria);
}

$categoria = addslashes($_POST["categoria"]);
$resumen = addslashes($_POST["resumen"]);
$descripcion = addslashes($_POST["descripcion"]);
$url = addslashes($_POST["url"]);

if ($categoria && $resumen) {
    $mensaje_redireccion = "¡Tu petición ha sido enviada exitosamente y la estaremos revisando! Muchas gracias crack, nunca cambies.";

    $db->query(" 
        INSERT INTO `mybb_sg_sg_peticiones` (`uid`, `nombre`, `categoria`, `resumen`, `descripcion`, `url`) VALUES ('$uid','$username','$categoria','$resumen','$descripcion','$url')
    ");

    eval("\$page = \"".$templates->get("sg_redireccion")."\";");
    output_page($page);
} else {
    
    if ($uid == '0') {
        $mensaje_redireccion = "Tienes que estar logueado para enviar peticiones administrativas, crack.";
        eval("\$page = \"".$templates->get("sg_redireccion")."\";");
        output_page($page);
    } else {
        $peticiones_txt = "";
        $pet_counter = 1;
        $query_peticion = $db->query("
            SELECT * FROM mybb_sg_sg_peticiones
            WHERE uid='$uid'
            AND resuelto=0
        ");
    
        while ($q = $db->fetch_array($query_peticion)) {
            $resumen_seguro = peticiones_escape($q['resumen']);
            $descripcion_segura = nl2br(peticiones_escape($q['descripcion']));
            $categoria_label = peticiones_escape(peticiones_categoria_label($q['categoria']));
            $url_peticion = trim($q['url']);
            $url_html = "";

            if ($url_peticion !== "") {
                $url_segura = peticiones_escape($url_peticion);
                $url_html = "<p class='sg-peticion-url'><strong>Link:</strong> <a href='{$url_segura}' target='_blank' rel='noopener noreferrer'>{$url_segura}</a></p>";
            }

            $peticiones_txt .= "
                <article class='sg-peticion-card'>
                    <div class='sg-peticion-head'>
                        <h3 class='sg-peticion-index'>Peticion {$pet_counter}</h3>
                        <span class='sg-peticion-tag'>{$categoria_label}</span>
                    </div>
                    <p class='sg-peticion-resumen'>{$resumen_seguro}</p>
                    <div class='sg-empty'>{$descripcion_segura}</div>
                    {$url_html}
                </article>
            ";
            $pet_counter += 1;
        }
    
        eval("\$page = \"".$templates->get("sg_peticiones")."\";");
        output_page($page);
    }


}
