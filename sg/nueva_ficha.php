<?php
define("IN_MYBB", 1);
define('THIS_SCRIPT', 'nueva_ficha.php');
require_once "./../global.php";
require "./../inc/config.php";
require_once "./functions/sg_functions.php";
global $templates, $mybb;

$name               = addslashes($_POST['name']);
$alias              = addslashes($_POST['alias']);
$age                = (int) $_POST['age'];
$season             = (int) $_POST['season'];
$villa              = addslashes($_POST['villa']);
$clan               = addslashes($_POST['clan']);
$peso               = (int) $_POST['peso'];
$altura             = (int) $_POST['altura'];
$sexo               = addslashes($_POST['sexo']);
$phi                = addslashes($_POST['phi']);
$psi                = addslashes($_POST['psi']);
$history            = addslashes($_POST['history']);
$extra              = addslashes($_POST['extra']);
$frase              = addslashes($_POST['frase']);
$fisico_de_pj       = addslashes($_POST['fisico_de_pj']);
$como_nos_conociste = addslashes($_POST['como_nos_conociste']);
$submit             = $_POST['submit'];
$uid                = (int) $_POST['uid'];

if ($name && $age && $season && $villa && $clan && $phi && $psi && $history && $submit && $uid == $mybb->user['uid']) {

    // ── Virtudes/defectos elegidos: validar antes de insertar nada ──
    $vd_sel = (isset($_POST['vd_sel']) && is_array($_POST['vd_sel'])) ? $_POST['vd_sel'] : array();
    $vd_sel = array_values(array_unique(array_filter(array_map('trim', $vd_sel))));

    $vd_puntos = array();   // virtud_id => puntos (solo las que existen en el catálogo)
    $vd_error  = '';
    if (!empty($vd_sel)) {
        $ids_esc = "'" . implode("','", array_map(array($db, 'escape_string'), $vd_sel)) . "'";
        $qv = $db->query("SELECT virtud_id, puntos FROM mybb_sg_sg_virtudes WHERE virtud_id IN ($ids_esc)");
        while ($v = $db->fetch_array($qv)) { $vd_puntos[$v['virtud_id']] = intval($v['puntos']); }
        foreach ($vd_sel as $vid) {
            if (!isset($vd_puntos[$vid])) { $vd_error = 'inexistente'; break; }
        }
    }
    // Incompatibilidades
    if ($vd_error === '' && sg_virtudes_conflicto(array_keys($vd_puntos)) !== null) {
        $vd_error = 'incompatibles';
    }
    // Balance: las virtudes no pueden superar a los defectos (neto <= 0)
    $vd_net = 0;
    foreach ($vd_puntos as $p) { $vd_net += $p; }
    if ($vd_error === '' && $vd_net > 0) { $vd_error = 'balance'; }

    if ($vd_error !== '') {
        eval("\$page = \"".$templates->get("sg_ficha_no_creada")."\";");
        output_page($page);
        exit;
    }

    // Ryos inicial según virtud/defecto de dinero (a lo sumo una, son excluyentes).
    $dinero = sg_virtudes_dinero();
    $ryos_inicial = 0;
    foreach (array_keys($vd_puntos) as $vid) {
        if (isset($dinero[$vid])) { $ryos_inicial += (int) $dinero[$vid]['ryos']; }
    }

    // Primer Acto (VPRACT): el ninja empieza con 100 puntos de Bingo.
    $bingo_inicial = isset($vd_puntos['VPRACT']) ? 100 : 0;

    // Las columnas `virtudes`/`defectos` (texto libre) quedaron obsoletas: las
    // virtudes/defectos reales viven en mybb_sg_sg_virtudes_usuarios. Se insertan
    // vacías para no romper el esquema.
    $db->query("
        INSERT INTO `mybb_sg_sg_fichas`
            (`fid`, `peso`, `altura`, `sexo`, `nombre`, `apodo`, `edad`, `temporada_nacimiento`, `virtudes`, `defectos`, `villa`, `clan`, `apariencia`, `personalidad`, `historia`, `moderated`, `extra`, `frase`, `fisico_de_pj`, `como_nos_conociste`, `ryos`, `bingo`)
        VALUES
            ('$uid', '$peso', '$altura', '$sexo', '$name', '$alias', '$age', '$season', '', '', '$villa', '$clan', '$phi', '$psi', '$history', 'no_moderacion', '$extra', '$frase', '$fisico_de_pj', '$como_nos_conociste', '$ryos_inicial', '$bingo_inicial')
    ");

    // Guarda las virtudes/defectos elegidos.
    foreach (array_keys($vd_puntos) as $vid) {
        $vid_esc = $db->escape_string($vid);
        $db->query("INSERT INTO `mybb_sg_sg_virtudes_usuarios` (`virtud_id`, `uid`) VALUES ('$vid_esc','$uid')");
    }

    // Por defecto el personaje desbloquea la BASE y las 3 ramas de cada árbol fijo
    $tecnicas_iniciales = array(
        'BUKI101B', 'BUKI001R', 'BUKI002R', 'BUKI003R',
        'DEFE101B', 'DEFE001R', 'DEFE002R', 'DEFE003R',
        'RESI101B', 'RESI001R', 'RESI002R', 'RESI003R',
        'TAIJ101B', 'TAIJ001R', 'TAIJ002R', 'TAIJ003R'
    );
    foreach ($tecnicas_iniciales as $tec_tid) {
        $db->query("INSERT IGNORE INTO `mybb_sg_sg_tec_aprendidas`(`tid`, `uid`) VALUES ('$tec_tid','$uid')");
    }

    // Técnica base según el clan elegido (su árbol de clan)
    $clan_tecnicas = array(
        101 => 'ABUR101B',
        102 => 'SENJ101B',
        103 => 'AKIM101B',
        104 => 'HYUG101B',
        105 => 'NARA101B',
        106 => 'UCHI101B',
        108 => 'YAMA101B',
        109 => 'INUZ101B',
        110 => 'SARU101B',
        301 => 'YUKI101B',
        302 => 'KAGU101B',
        304 => 'HOZU101B',
        305 => 'HOSH101B',
        306 => 'HEIZ101B',
        307 => 'TERU101B',
        308 => 'AKIZ101B',
        309 => 'FUNA101B',
        310 => 'KODO101B'
    );
    if (isset($clan_tecnicas[$clan])) {
        $tec_clan = $clan_tecnicas[$clan];
        $db->query("INSERT IGNORE INTO `mybb_sg_sg_tec_aprendidas`(`tid`, `uid`) VALUES ('$tec_clan','$uid')");
    }

    // Progreso/economía del Dojo por defecto (ver docs/arboles_instruciones.txt).
    // El estado del árbol se DERIVA de tec_aprendidas; ya no se guarda un espejo
    // en la columna `arboles` (queda obsoleta).
    $progreso_json = $db->escape_string(json_encode(sg_progreso_defaults(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $db->query("UPDATE `mybb_sg_sg_fichas` SET `arboles_progreso`='$progreso_json' WHERE `fid`='$uid'");

    eval("\$page = \"".$templates->get("sg_nueva_ficha_creada")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sg_ficha_no_creada")."\";");
    output_page($page);
}
