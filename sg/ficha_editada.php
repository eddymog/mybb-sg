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
define('THIS_SCRIPT', 'nueva_ficha.php');
require_once "./../global.php";
require "./../inc/config.php";
require_once "./functions/sg_functions.php";
global $templates, $mybb;

$habilidad = $_POST["habilidad"];
$mejoras = $_POST["mejoras"];
$fuerza = $_POST["fuerza"];
$destreza = $_POST["destreza"];
$cchakra = $_POST["cchakra"];
$inteligencia = $_POST["inteligencia"];

$salud = $_POST["salud"];
$velocidad = $_POST["velocidad"];
$tenketsu = $_POST["tenketsu"];
$sigilo = $_POST["sigilo"];

$uid = $_POST["uid"];
$submit = $_POST["submit_edit"];

if (($habilidad || $habilidad == '0') && ($mejoras || $mejoras == '0') && 
    ($fuerza || $fuerza == '0') && ($destreza || $destreza == '0') && ($cchakra || $cchakra == '0') && ($inteligencia || $inteligencia == '0') && 
     ($salud || $salud == '0') && ($velocidad || $velocidad == '0') && ($tenketsu || $tenketsu == '0') && ($sigilo || $sigilo == '0') && $submit && $uid == $mybb->user['uid']) {
    
    $ficha = select_one_query_with_id('mybb_sg_sg_fichas', 'fid', $uid);

    function sum_stats($fuerza, $destreza, $cchakra, $inteligencia, $salud, $velocidad, $tenketsu, $sigilo) {
        $sum_stats = intval($fuerza) + intval($destreza) + intval($cchakra) + intval($inteligencia) + intval($salud) + intval($velocidad) + intval($tenketsu) + intval($sigilo);
        return $sum_stats;
    }

    // stat_modifier() vive ahora en sg/functions/sg_functions.php (global), para
    // que el mismo criterio (base + pasivas) se use al mostrar la ficha, el
    // postbit y el tag [personaje].

    $new_stats = sum_stats($fuerza, $destreza, $cchakra, $inteligencia, $salud, $velocidad, $tenketsu, $sigilo);
    $current_stats = sum_stats($ficha['fuerza'], $ficha['destreza'], $ficha['cchakra'], $ficha['inteligencia'], $ficha['salud'], $ficha['velocidad'], $ficha['tenketsu'], $ficha['sigilo']);

    if ((intval($ficha['puntos_estadistica']) + intval($ficha['mejoras']) + $current_stats) != (intval($habilidad) + intval($mejoras) + $new_stats)) {
        eval("\$page = \"".$templates->get("sg_ficha_no_editada")."\";");
        output_page($page);
    } else {

        $vida = calculate_vida2($fuerza, $destreza, $cchakra, $inteligencia, $salud, $velocidad, $tenketsu, $sigilo);
        $chakra = calculate_chakra2($fuerza, $destreza, $cchakra, $inteligencia, $salud, $velocidad, $tenketsu, $sigilo);

        $mfuerza = stat_modifier($fuerza);
        $mdestreza = stat_modifier($destreza);
        $mcchakra = stat_modifier($cchakra);
        $minteligencia = stat_modifier($inteligencia);

        $db->query("
            UPDATE `mybb_sg_sg_fichas` SET `vida`='$vida',`chakra`='$chakra',`puntos_estadistica`='$habilidad',`mejoras`='$mejoras',`fuerza`='$fuerza',`destreza`='$destreza',`cchakra`='$cchakra',`inteligencia`='$inteligencia',`mfuerza`='$mfuerza',`mdestreza`='$mdestreza',`mcchakra`='$mcchakra',`minteligencia`='$minteligencia',`salud`='$salud',`velocidad`='$velocidad',`tenketsu`='$tenketsu',`sigilo`='$sigilo' WHERE `fid`='$uid';
        ");

        eval("\$page = \"".$templates->get("sg_ficha_editada")."\";");
        output_page($page);
    }

} else  {
    eval("\$page = \"".$templates->get("sg_ficha_no_editada")."\";");
    output_page($page);
}
