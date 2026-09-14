<?php

if (!defined("IN_MYBB")) {
    die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// Sección de Afiliados en el índice. Ver docs/afiliados_diseno.md.
// sg_functions.php ya se carga globalmente en global.php, así que
// sg_afiliados_index_html() está disponible acá sin requires extra.
$plugins->add_hook('index_end', 'sg_afiliados_index_end');

function sg_afiliados_info() {
    return array(
        "name"          => "SG Afiliados",
        "description"   => "Sección de Afiliados (hermano/grandes/pequeños) al final del índice.",
        "website"       => "",
        "author"        => "Shinobi Gaiden",
        "authorsite"    => "https://www.shinobigaiden.net",
        "version"       => "1.0.0",
        "codename"      => "sg_afiliados",
        "compatibility" => "*",
    );
}

function sg_afiliados_index_end() {
    global $index_section_final;

    // Marcador de diagnóstico TEMPORAL: siempre deja algo en el HTML (aunque
    // sea un comentario) para poder distinguir, con "Ver código fuente", en
    // qué paso se corta si la sección no aparece. Se quita una vez resuelto.
    if (!function_exists('sg_afiliados_index_html')) {
        $index_section_final = '<!-- sg_afiliados: el hook index_end SÍ corrió, pero sg_afiliados_index_html() no existe (sg_functions.php no tiene la función cargada — revisar caché de opcode / que el archivo en el servidor tenga los cambios) -->';
        return;
    }

    $html = sg_afiliados_index_html();
    if (trim($html) === '') {
        $index_section_final = '<!-- sg_afiliados: el hook corrió y la función existe, pero devolvió HTML vacío -->';
        return;
    }

    $index_section_final = '<!-- sg_afiliados: OK, ' . strlen($html) . ' bytes -->' . $html;
}
