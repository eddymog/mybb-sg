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
$tipos_chips = array();   // tipos distintos, en orden, para los chips de filtro
$conteo_tipos = array();  // nº de objetos por tipo (para el conteo en los chips)

while ($q = $db->fetch_array($query_objetos)) {
    $total++;

    $oid       = htmlspecialchars($q['objeto_id'], ENT_QUOTES);
    $nombre    = htmlspecialchars($q['nombre'], ENT_QUOTES);
    $tipo      = trim($q['tipo']) !== '' ? $q['tipo'] : 'Otros';
    $tipo_esc  = htmlspecialchars($tipo, ENT_QUOTES);
    $tamano    = htmlspecialchars($q['tamano'], ENT_QUOTES);
    $municion  = htmlspecialchars($q['municion'], ENT_QUOTES);
    // Los campos de texto se pasan crudos (escapados como atributo) para que el
    // modal los pinte con textContent y respete los saltos de línea (pre-wrap).
    $desc_attr = htmlspecialchars($q['descripcion'], ENT_QUOTES);
    $ef1 = htmlspecialchars($q['efecto1'], ENT_QUOTES);
    $ef2 = htmlspecialchars($q['efecto2'], ENT_QUOTES);
    $ef3 = htmlspecialchars($q['efecto3'], ENT_QUOTES);
    $coste     = intval($q['coste']);
    $maxq      = ($q['cantidadMaxima'] === null || $q['cantidadMaxima'] === '') ? '?' : intval($q['cantidadMaxima']);
    $img       = trim($q['imagen']) !== '' ? htmlspecialchars($q['imagen'], ENT_QUOTES) : $default_img;
    $en_tienda = intval($q['en_tienda']);
    $data_name = htmlspecialchars(strtolower($q['nombre']), ENT_QUOTES);
    $data_tipo = htmlspecialchars(strtolower($tipo), ENT_QUOTES);
    $conteo_tipos[$data_tipo] = (isset($conteo_tipos[$data_tipo]) ? $conteo_tipos[$data_tipo] : 0) + 1;
    // Búsqueda: nombre + tipo + tamaño + descripción + efectos.
    $data_search = htmlspecialchars(strtolower(
        $q['nombre'] . ' ' . $tipo . ' ' . $q['tamano'] . ' ' . $q['descripcion'] . ' ' .
        $q['efecto1'] . ' ' . $q['efecto2'] . ' ' . $q['efecto3']
    ), ENT_QUOTES);

    $coste_label = ($coste >= 99999) ? 'No comprable' : number_format($coste, 0, ',', '.') . ' ryos';

    // Nuevo grupo por tipo
    if ($tipo !== $tipoAnterior) {
        if ($tipoAnterior !== null) {
            $objetos_html .= "</div></section>";
        }
        $objetos_html .= "<section class=\"sg-cat-group\" data-tipo=\"$data_tipo\"><h2 class=\"sg-cat-group-title\">$tipo_esc</h2><div class=\"sg-obj-grid\">";
        $tipos_chips[] = array($tipo_esc, $data_tipo);
        $tipoAnterior = $tipo;
    }

    // Miniatura 175×175 + nombre. El detalle vive en los data-* y lo pintan el
    // modal (vista cuadrícula) o el bloque .sg-obj-detail (vista detalle).
    $objetos_html .= "<article class=\"sg-obj-tile\" tabindex=\"0\" role=\"button\" aria-label=\"$nombre\""
        . " data-name=\"$data_name\" data-tipo=\"$data_tipo\" data-search=\"$data_search\""
        . " data-nombre=\"$nombre\" data-img=\"$img\" data-coste=\"$coste_label\" data-max=\"$maxq\""
        . " data-id=\"$oid\" data-tamano=\"$tamano\" data-tipolabel=\"$tipo_esc\" data-entienda=\"$en_tienda\""
        . " data-municion=\"$municion\" data-desc=\"$desc_attr\" data-ef1=\"$ef1\" data-ef2=\"$ef2\" data-ef3=\"$ef3\">"
        . "<div class=\"sg-obj-thumb\"><img class=\"sg-obj-img\" src=\"$img\" alt=\"$nombre\" loading=\"lazy\" onerror=\"sgImgFallback(this)\"></div>"
        . "<div class=\"sg-obj-main\"><div class=\"sg-obj-name\">$nombre</div><div class=\"sg-obj-detail\"></div></div>"
        . "</article>";
}
if ($tipoAnterior !== null) {
    $objetos_html .= "</div></section>";
}
if ($total === 0) {
    $objetos_html = "<div class=\"sg-cat-empty\">No hay objetos en el catálogo por ahora.</div>";
}

// Chips de filtro por categoría (Todos + cada tipo distinto), con su conteo.
$chips_html = "<button class=\"sg-cat-chip is-active\" type=\"button\" data-tipo=\"\" onclick=\"sgSetTipo(this)\">Todos <span class=\"sg-cat-chip-n\">$total</span></button>";
foreach ($tipos_chips as $t) {
    $n = isset($conteo_tipos[$t[1]]) ? $conteo_tipos[$t[1]] : 0;
    $chips_html .= "<button class=\"sg-cat-chip\" type=\"button\" data-tipo=\"{$t[1]}\" onclick=\"sgSetTipo(this)\">{$t[0]} <span class=\"sg-cat-chip-n\">$n</span></button>";
}
eval('$chipsHtml = $chips_html;');

$sg_es_staff = $es_staff ? 'true' : 'false';
eval('$sgEsStaff = $sg_es_staff;');

eval("\$page = \"".$templates->get("sg_objetos")."\";");
output_page($page);
