<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'tecnicas_show2.php');
require_once "./../global.php";
require "./../inc/config.php";
global $templates;

$arbol = $mybb->get_input('arbol');

$query_clan = $db->query(" SELECT * FROM mybb_sg_sg_clanes WHERE nombreClan='$arbol' ");
$query_tecnicas_base = $db->query(" SELECT * FROM mybb_sg_sg_tecnicas WHERE arbol='$arbol' AND rama='Base' ");
$query_tecnicas_rama1 = $db->query(" SELECT * FROM mybb_sg_sg_tecnicas WHERE arbol='$arbol' AND rama='Rama 1' ");
$query_tecnicas_rama2 = $db->query(" SELECT * FROM mybb_sg_sg_tecnicas WHERE arbol='$arbol' AND rama='Rama 2' ");
$query_tecnicas_rama3 = $db->query(" SELECT * FROM mybb_sg_sg_tecnicas WHERE arbol='$arbol' AND rama='Rama 3' ");

while ($clan = $db->fetch_array($query_clan)) {
    eval('$clan_desc = "'.nl2br($clan['descripcion']).'";');
}

$tecs_base = array();
$tecs_rama1 = array();
$tecs_rama2 = array();
$tecs_rama3 = array();

while ($tec = $db->fetch_array($query_tecnicas_base)) {
    array_push($tecs_base, $tec);
}



while ($tec = $db->fetch_array($query_tecnicas_rama1)) {
    $clean_categoria = join("_", explode(" ", $tec['categoria']));

    $key = strtolower($clean_categoria);

    if (!$tecs_rama1[$key]) {
        $tecs_rama1[$key] = array();
    }
    array_push($tecs_rama1[$key], $tec);
}

while ($tec = $db->fetch_array($query_tecnicas_rama2)) {
    $clean_categoria = join("_", explode(" ", $tec['categoria']));

    $key = strtolower($clean_categoria);

    if (!$tecs_rama2[$key]) {
        $tecs_rama2[$key] = array();
    }
    array_push($tecs_rama2[$key], $tec);
}

while ($tec = $db->fetch_array($query_tecnicas_rama3)) {
    $clean_categoria = join("_", explode(" ", $tec['categoria']));

    $key = strtolower($clean_categoria);

    if (!$tecs_rama3[$key]) {
        $tecs_rama3[$key] = array();
    }
    array_push($tecs_rama3[$key], $tec);
}

$tecs_base_json = addslashes(json_encode($tecs_base));
$tecs_rama1_json = addslashes(json_encode($tecs_rama1));
$tecs_rama2_json = addslashes(json_encode($tecs_rama2));
$tecs_rama3_json = addslashes(json_encode($tecs_rama3));

// create variables
// eval('$tecs = "'.addslashes($tecs_json).'";');


eval("\$page = \"".$templates->get("sg_tecnicas_show3")."\";");
output_page($page);


