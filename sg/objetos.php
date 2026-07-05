<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 * Catálogo de objetos a la venta (solo listado; la compra la maneja tienda.php).
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'objetos.php');

global $templates, $mybb, $db;

require_once "./../global.php";
require_once "./functions/sg_functions.php";

$default_img = '/images/sg/objeto_default.png';

$uid = intval($mybb->user['uid']);
$es_staff = (is_mod($uid) || is_staff($uid));

$query_objetos = $db->query("
    SELECT * FROM `mybb_sg_sg_objetos`
    ORDER BY tipo, nombre
");

$objetos_html = '';
$tipoAnterior = null;
$total = 0;

while ($q = $db->fetch_array($query_objetos)) {
    $total++;

    $oid       = htmlspecialchars($q['objeto_id'], ENT_QUOTES);
    $nombre    = htmlspecialchars($q['nombre'], ENT_QUOTES);
    $tipo      = trim($q['tipo']) !== '' ? $q['tipo'] : 'Otros';
    $tipo_esc  = htmlspecialchars($tipo, ENT_QUOTES);
    $tamano    = htmlspecialchars($q['tamano'], ENT_QUOTES);
    $desc      = nl2br(htmlspecialchars($q['descripcion'], ENT_QUOTES));
    $efecto_items = '';
    foreach (array($q['efecto1'], $q['efecto2'], $q['efecto3']) as $ef) {
        if (trim($ef) !== '') {
            $efecto_items .= "<div class=\"sg-item-effect\"><span class=\"sg-item-eff-label\">Efecto</span> " . nl2br(htmlspecialchars($ef, ENT_QUOTES)) . "</div>";
        }
    }
    $coste     = intval($q['coste']);
    $maxq      = ($q['cantidadMaxima'] === null || $q['cantidadMaxima'] === '') ? '?' : intval($q['cantidadMaxima']);
    $img       = trim($q['imagen']) !== '' ? htmlspecialchars($q['imagen'], ENT_QUOTES) : $default_img;
    $data_name = htmlspecialchars(strtolower($q['nombre']), ENT_QUOTES);
    $data_tipo = htmlspecialchars(strtolower($tipo), ENT_QUOTES);

    $coste_label = ($coste >= 99999) ? '—' : number_format($coste, 0, ',', '.') . ' ryos';

    // Nuevo grupo por tipo
    if ($tipo !== $tipoAnterior) {
        if ($tipoAnterior !== null) {
            $objetos_html .= "</div></section>";
        }
        $objetos_html .= "<section class=\"sg-cat-group\"><h2 class=\"sg-cat-group-title\">$tipo_esc</h2><div class=\"sg-cat-grid\">";
        $tipoAnterior = $tipo;
    }

    $badges = "<span class=\"sg-item-badge\">$tipo_esc</span>";
    if ($tamano !== '') {
        $badges .= "<span class=\"sg-item-badge sg-item-badge--soft\">$tamano</span>";
    }

    $desc_html   = trim($q['descripcion']) !== '' ? "<p class=\"sg-item-desc\">$desc</p>" : '';
    $efecto_html = $efecto_items;

    if ($es_staff) {
        $id_badge = "<a class=\"sg-item-id\" href=\"/sg/admin/gestionar_objetos.php?objeto_id=$oid\" title=\"Gestionar objeto\">$oid</a>";
    } else {
        $id_badge = "<span class=\"sg-item-id\" title=\"Clic para seleccionar\" onclick=\"sgSelectText(this)\">$oid</span>";
    }

    $objetos_html .= "<article class=\"sg-item\" data-name=\"$data_name\" data-tipo=\"$data_tipo\">"
        . "<div class=\"sg-item-media\">"
        . "<img class=\"sg-item-img\" src=\"$img\" alt=\"$nombre\" loading=\"lazy\" onerror=\"sgImgFallback(this)\">"
        . $id_badge
        . "<span class=\"sg-item-cost\">$coste_label</span>"
        . "</div>"
        . "<div class=\"sg-item-body\">"
        . "<h3 class=\"sg-item-name\">$nombre</h3>"
        . "<div class=\"sg-item-badges\">$badges</div>"
        . $desc_html
        . $efecto_html
        . "<div class=\"sg-item-meta\">Límite por ficha: <strong>$maxq</strong></div>"
        . "</div>"
        . "</article>";
}
if ($tipoAnterior !== null) {
    $objetos_html .= "</div></section>";
}
if ($total === 0) {
    $objetos_html = "<div class=\"sg-cat-empty\">No hay objetos a la venta por ahora.</div>";
}

eval("\$page = \"".$templates->get("sg_objetos")."\";");
output_page($page);
