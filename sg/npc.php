<?php
/**
 * MyBB 1.8
 *
 * Ficha pública de un NPC (Bingo Book). Se accede por ?codigo=<slug>.
 * Oculta los campos vacíos y las estadísticas en 0.
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'npc.php');

global $templates, $mybb, $db;

require_once "./../global.php";
require_once "./functions/sg_functions.php";

$default_img = '/images/sg/objeto_default.png';

$uid = intval($mybb->user['uid']);
$es_staff = (is_mod($uid) || is_staff($uid));

$codigo = trim($mybb->get_input('codigo'));
$npc = null;
if ($codigo !== '') {
    $codigo_esc = $db->escape_string($codigo);
    $q = $db->query("SELECT * FROM `mybb_sg_sg_npcs` WHERE codigo='$codigo_esc'");
    while ($r = $db->fetch_array($q)) { $npc = $r; }
}

$npc_html = '';

if ($npc === null) {
    $npc_html = "<div class=\"sg-npc-empty\">Este NPC no existe o fue removido. <a href=\"/sg/npcs.php\">Volver al Bingo Book</a>.</div>";
} else {
    $color  = sg_npc_afiliacion_color($npc['afiliacion']);

    $nom    = htmlspecialchars($npc['nombre'], ENT_QUOTES);
    $afil   = trim($npc['afiliacion']);
    $afil_e = htmlspecialchars($afil, ENT_QUOTES);
    $clan   = htmlspecialchars($npc['clan_grupo'], ENT_QUOTES);
    $cargo  = htmlspecialchars($npc['cargo'], ENT_QUOTES);
    $edad   = htmlspecialchars($npc['edad'], ENT_QUOTES);
    $rango  = htmlspecialchars($npc['rango'], ENT_QUOTES);
    $nivel  = intval($npc['nivel']);
    $frase  = trim($npc['frase']);
    $desc   = trim($npc['descripcion']);
    $img    = trim($npc['imagen']) !== '' ? htmlspecialchars($npc['imagen'], ENT_QUOTES) : $default_img;

    // ── Tags (rango · nivel · edad) ──
    $tags = '';
    if ($rango !== '') { $tags .= "<span class=\"sg-npc-tag sg-npc-tag--accent\">$rango</span>"; }
    $tags .= "<span class=\"sg-npc-tag\">Nivel $nivel</span>";
    if ($edad !== '') { $tags .= "<span class=\"sg-npc-tag\">$edad años</span>"; }

    // ── Hero ──
    $npc_html .= "<div class=\"sg-npc-hero\" style=\"--accent: $color;\">";
    $npc_html .= "<div class=\"sg-npc-portrait\">"
        . "<img class=\"sg-npc-img\" src=\"$img\" alt=\"$nom\" onerror=\"sgImgFallback(this)\">"
        . ($afil_e !== '' ? "<span class=\"sg-npc-afil\">$afil_e</span>" : "")
        . "</div>";
    $npc_html .= "<div class=\"sg-npc-headinfo\">";
    if ($clan !== '') { $npc_html .= "<div class=\"sg-npc-eyebrow\">$clan</div>"; }
    if ($es_staff) {
        $npcid = intval($npc['npc_id']);
        $npc_html .= "<h1 class=\"sg-npc-name\"><a class=\"sg-npc-name-link\" href=\"/sg/admin/gestionar_npcs.php?npc_id=$npcid\" title=\"Gestionar NPC\">$nom</a></h1>";
    } else {
        $npc_html .= "<h1 class=\"sg-npc-name\">$nom</h1>";
    }
    if ($cargo !== '') { $npc_html .= "<div class=\"sg-npc-cargo\">$cargo</div>"; }
    $npc_html .= "<div class=\"sg-npc-tags\">$tags</div>";
    if ($frase !== '') {
        $npc_html .= "<blockquote class=\"sg-npc-frase\">" . htmlspecialchars($frase, ENT_QUOTES) . "</blockquote>";
    }
    $npc_html .= "</div></div>"; // headinfo, hero

    // ── Descripción ──
    if ($desc !== '') {
        $npc_html .= "<section class=\"sg-npc-block\">";
        $npc_html .= "<h2 class=\"sg-npc-block-title\">Descripción</h2>";
        $npc_html .= "<div class=\"sg-npc-desc\">" . nl2br(htmlspecialchars($desc, ENT_QUOTES)) . "</div>";
        $npc_html .= "</section>";
    }

    // ── Estadísticas (solo valores > 0) ──
    $grupos = array(
        'Generales'     => array('fuerza' => 'Fuerza', 'destreza' => 'Destreza', 'inteligencia' => 'Inteligencia', 'cchakra' => 'C. Chakra'),
        'Modificadores' => array('mfuerza' => 'M. Fuerza', 'mdestreza' => 'M. Destreza', 'minteligencia' => 'M. Inteligencia', 'mcchakra' => 'M. C. Chakra'),
        'Secundarias'   => array('salud' => 'Salud', 'velocidad' => 'Velocidad', 'tenketsu' => 'Tenketsu', 'sigilo' => 'Sigilo'),
        'Recursos'      => array('vida' => 'Vida', 'chakra' => 'Chakra', 'regchakra' => 'Reg. Chakra'),
    );

    $stats_html = '';
    foreach ($grupos as $titulo => $campos) {
        $items = '';
        foreach ($campos as $key => $label) {
            $v = intval($npc[$key]);
            if ($v > 0) {
                $items .= "<div class=\"sg-npc-stat\"><span class=\"sg-npc-stat-k\">$label</span><span class=\"sg-npc-stat-v\">$v</span></div>";
            }
        }
        if ($items !== '') {
            $stats_html .= "<div class=\"sg-npc-stat-group\"><div class=\"sg-npc-stat-group-t\">$titulo</div><div class=\"sg-npc-stat-grid\">$items</div></div>";
        }
    }

    if ($stats_html !== '') {
        $npc_html .= "<section class=\"sg-npc-block\">";
        $npc_html .= "<h2 class=\"sg-npc-block-title\">Estadísticas</h2>";
        $npc_html .= $stats_html;
        $npc_html .= "</section>";
    }
}

eval("\$page = \"".$templates->get("sg_npc")."\";");
output_page($page);
