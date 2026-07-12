<?php
/**
 * MyBB 1.8
 *
 * Genera el JSON de árboles de técnicas a partir de mybb_sg_sg_tecnicas.
 *
 * Estructura por árbol:
 *   {
 *     "base": "<tid donde rama='base'>",
 *     "rama1": {
 *        "base": "<tid de la rama cuya categoria NO es mejora/especialidad>",
 *        "mejoras": ["<tids con categoria='mejora'>"],
 *        "especialidades": ["<tids con categoria='especialidad'>"]
 *     },
 *     ...
 *   }
 *
 * Uso:
 *   /sg/admin/generar_arboles.php            -> imprime el JSON (lee la BD, ignora caché)
 *   /sg/admin/generar_arboles.php?save=1     -> además lo guarda en docs/arboles_generado.json
 *   /sg/admin/generar_arboles.php?rebuild=1  -> RECONSTRUYE el caché 'sg_arboles' del dojo
 *                                               (úsalo si editaste técnicas por SQL y un
 *                                                árbol/clan no aparece en el Dojo)
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'generar_arboles.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/sg_functions.php";

global $db, $mybb;

$uid = (int) $mybb->user['uid'];
if (!(is_mod($uid) || is_staff($uid))) {
    die('Sin permisos.');
}

// Detecta automáticamente todos los árboles distintos de la tabla
$arboles = array();
$query_arboles = $db->query("
    SELECT DISTINCT LOWER(TRIM(arbol)) AS arbol
    FROM mybb_sg_sg_tecnicas
    WHERE TRIM(arbol) <> ''
    ORDER BY arbol
");
while ($r = $db->fetch_array($query_arboles)) {
    $arboles[] = $r['arbol'];
}

// sg_build_arbol() vive en sg/functions/sg_functions.php (compartida)

$out = array('arboles' => array());
foreach ($arboles as $a) {
    $out['arboles'][$a] = sg_build_arbol($db, $a);
}

// Reconstruye el caché 'sg_arboles' que usa el Dojo (sg_dojo_estado). Necesario
// cuando se agregan/editan técnicas por fuera del panel (p. ej. por SQL), porque
// el Dojo lee del caché y no vería el árbol nuevo hasta reconstruirlo.
if ($mybb->get_input('rebuild')) {
    sg_rebuild_catalogo_arboles($db);
    $out['cache_reconstruido'] = true;
}

$json = json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if ($mybb->get_input('save')) {
    @file_put_contents(MYBB_ROOT . 'docs/arboles_generado.json', $json);
}

header('Content-Type: application/json; charset=utf-8');
echo $json;
exit;
