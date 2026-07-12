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
define('THIS_SCRIPT', 'ficha.php');
require_once "./../global.php";
require "./../inc/config.php";
require_once "./functions/sg_functions.php";

global $templates, $mybb;

$uid = $mybb->get_input('uid'); 
$action = $mybb->get_input('action');
$module = $mybb->get_input('module'); 

$ficha_existe = false;
$moderated = false;
eval('$action = $action;');
eval('$module = $module;');
eval('$userid = $uid;');

$query_ficha = $db->query("
    SELECT * FROM mybb_sg_sg_fichas WHERE fid='$uid'
");

$s_uid = $mybb->user['uid'];

while ($f = $db->fetch_array($query_ficha)) {
    $moderated = $f['moderated'] != 'no_moderacion';
    $ficha_existe = true;
}

if ($ficha_existe == true && ($moderated == true || (is_mod($s_uid) || is_staff($s_uid)))) {
    $query_usuario = $db->query("
        SELECT * FROM mybb_sg_users WHERE uid='$uid'
    ");

    while ($u = $db->fetch_array($query_usuario)) {
        $avatar = $u['avatar'];
        if (substr($u['avatar'], 0, 18) === './uploads/avatars/') {
            $avatar = './.' . $u['avatar'];
        }

        eval('$user_avatar = $avatar;');
        eval('$usuario = $u;');
    }

    $query_ficha = $db->query("
        SELECT * FROM mybb_sg_sg_fichas WHERE fid='$uid'
    ");

    while ($f = $db->fetch_array($query_ficha)) {

        $villa = $f['villa'];
        $clan = $f['clan'];
        $el1 = ucfirst($f['elemento1']);
        $el2 = ucfirst($f['elemento2']);
        $el3 = ucfirst($f['elemento3']);
        $el4 = ucfirst($f['elemento4']);
        $el5 = ucfirst($f['elemento5']);
        $ma1 = $f['maestria'];
        $ma2 = $f['maestria_secundaria'];
        $in1 = $f['invocacion'];
        $in2 = $f['invocacion_secundaria'];
        eval('$elemento1 = $el1;');
        eval('$elemento2 = $el2;');
        eval('$elemento3 = $el3;');
        eval('$elemento4 = $el4;');
        eval('$elemento5 = $el5;');
        eval('$maestria1 = $ma1;');
        eval('$maestria2 = $ma2;');
        eval('$invo1 = $in1;');
        eval('$invo2 = $in2;');
        $pasivaSlot = $f['pasiva_slot'];

        $query_villa = $db->query("
            SELECT * FROM mybb_sg_sg_villas WHERE vid='$villa'
        ");
    
        $query_clan = $db->query("
            SELECT * FROM mybb_sg_sg_clanes WHERE cid='$clan'
        ");
    
        // clanes
        while ($c = $db->fetch_array($query_clan)) {
            $nombreClan = ucwords($c['nombreClan']);
            eval('$nClan = $nombreClan;');
            eval('$clan = $c;');
        }
    
        while ($v = $db->fetch_array($query_villa)) {
            $villa_color = '';
            $villa_var_color = '';
            if ($villa == 1) { 
                $villa_color = 'fhko'; 
                $villa_var_color = '--konoha-group-color'; };
            if ($villa == 3) { 
                $villa_color = 'fhki';
                $villa_var_color = '--kiri-group-color';  };
            if ($villa == 4) { 
                $villa_color = 'fhiw'; 
                $villa_var_color = '--iwa-group-color';  };
            if ($villa == 5) { 
                $villa_color = 'fhku'; 
                $villa_var_color = '--kumo-group-color';  };
            if ($villa == 6) { 
                $villa_color = 'fhre'; 
                $villa_var_color = '--renegados-group-color';  };
            if ($villa == 7) { 
                $villa_color = 'fhnsa'; 
                $villa_var_color = '--nsa-group-color';  };

            eval('$villaVarColor = $villa_var_color;');
            eval('$villaColor = $villa_color;');
            eval('$villa = $v;');
        }
    
        $agSum = 0;
        $ckSum = 0;
        $agNivSum = 1;
        $ckNivSum = 1;
    
        if ($f['espe'] == 'NIN' || $f['espe'] == 'GEN') {
            $ckSum = $f['inte'] + $f['ctrl'];
            // $ckNivSum = $f['nivel'];
            $ckNivSum = 2;
        }
        
        if ($f['espe'] == 'TAI') {
            $ckSum = $f['res'] + $f['agi'];
            // $agNivSum = $f['nivel'];
            $ckNivSum = 2;
        }
        
        eval('$ficha = $f;');
        // $v = $f['str'] * 3 + $f['res'] * 4;
        // $c = ($f['pres'] * 2) + ($f['inte'] * 2) + ($f['ctrl'] * 3) + $ckSum;
        // $a = round(($f['agi'] * 1/2) + (($f['str'] + $f['spd']) * 3/2) + ($f['res'] * 2) + ($f['dex'] * 9/4)) + $agSum;
        // $reg_a = round((($f['str'] + $f['str'] + $f['spd'] + $f['agi']) / 20) + 1 + $agNivSum);
        // $reg_c = round((($f['pres'] + $f['inte'] + $f['ctrl']) / 20) + 1 + $ckNivSum);

        $v = $f['str'] * 3 + $f['res'] * 4;
        $c = round(($f['str'] * 1) + ($f['res'] * 0.5) + ($f['spd'] * 2) + ($f['agi'] * 0.5) + ($f['dex'] * 2) + ($f['pres'] * 2) + ($f['inte'] * 2) + ($f['ctrl'] * 2.5));
        $a = 0;  
        $reg_a = 0;
        $reg_c = round(((($f['str'] + $f['res'] + $f['spd'] + $f['agi'] + $f['dex'] + $f['pres'] + $f['inte'] + $f['ctrl']) * 2)) / 40) + 1;
        $suma_stats_var = $f['str'] + $f['res'] + $f['spd'] + $f['agi'] + $f['dex'] + $f['pres'] + $f['inte'] + $f['ctrl'];

        $historia_var = nl2br($ficha['historia']);
        $apariencia_var = nl2br($ficha['apariencia']);
        $personalidad_var = nl2br($ficha['personalidad']);
        $extra_var = nl2br($ficha['extra']);
        $frase_var = nl2br($ficha['frase']);
        $limite_nivel = $ficha['limite_nivel'];
        
        eval('$vida = $v;');
        eval('$aguante = $a;');
        eval('$chakra = $c;');
        eval('$regA = $reg_a;');
        eval('$regC = $reg_c;');
        eval('$historia = $historia_var;');
        eval('$apariencia = $apariencia_var;');
        eval('$personalidad = $personalidad_var;');
        eval('$extra = $extra_var;');
        eval('$frase = $frase_var;');
    }
    eval("\$fichaleft = \"".$templates->get("sg_ficha_left")."\";");
    eval("\$ficharight = \"".$templates->get("sg_ficha_right")."\";");

    // zona privada, solo para admins
    if ($s_uid == $uid || is_staff($s_uid) || is_peti_mod($s_uid)) {
        $query_tec_aprendidas = $db->query("
            SELECT * FROM `mybb_sg_sg_tecnicas` 
            INNER JOIN `mybb_sg_sg_tec_aprendidas` 
            ON `mybb_sg_sg_tecnicas`.`tid`=`mybb_sg_sg_tec_aprendidas`.`tid` 
            WHERE `mybb_sg_sg_tec_aprendidas`.`uid`='$uid'
        ");

        $tec_aprendidas = array();
        
        while ($tec_aprendida = $db->fetch_array($query_tec_aprendidas)) {
            $tec_aprendida['descripcion'] = nl2br($tec_aprendida['descripcion']);
            $key = strtolower($tec_aprendida['tipo']) . '_' . strtolower($tec_aprendida['aldea']);

            if (!$tec_aprendidas[$key]) {
                $tec_aprendidas[$key] = array();
            }
            array_push($tec_aprendidas[$key], $tec_aprendida);
        }
        $tec_aprendidas_json = json_encode($tec_aprendidas);
        
        // create variables
        eval('$tec_aprendidas = "'.addslashes($tec_aprendidas_json).'";');
        eval("\$fichaprivate_script = \"".$templates->get("fichaprivate_script")."\";");
        eval("\$fichaprivate = \"".$templates->get("fichaprivate")."\";");
    }

    eval("\$page = \"".$templates->get("sg_ficha")."\";");
    output_page($page);

} else if ($ficha_existe == false && $mybb->user['uid'] != 0 && $mybb->user['uid'] == $uid) {
    $query_uid = $db->query("
    SELECT * FROM mybb_sg_sg_fichas WHERE fid='$uid'
    ");

    // clanes
    $query_clanes = $db->query("
    SELECT * FROM mybb_sg_sg_clanes
    ");
    $clanes = array();
    while ($clan = $db->fetch_array($query_clanes)) {
        unset($clan['descripcion']);
        array_push($clanes, $clan);
    }
    $clanes_json = json_encode($clanes);

    // villas
    $query_villas = $db->query("
    SELECT * FROM mybb_sg_sg_villas
    ");
    $villas = array();
    while ($villa = $db->fetch_array($query_villas)) {
        array_push($villas, $villa);
    }
    $villas_json = json_encode($villas);

    // create variables
    eval('$clanes = "'.addslashes($clanes_json).'";');
    eval('$villas = "'.addslashes($villas_json).'";');
    eval('$nueva_ficha_script = "'.$templates->get('nueva_ficha_script').'";');

    eval("\$page = \"".$templates->get("nueva_ficha")."\";");
    output_page($page);
} else if ($ficha_existe == true && $moderated == false && $mybb->user['uid'] == $uid && $mybb->user['uid'] != 0) {
    eval("\$page = \"".$templates->get("ficha_en_moderacion")."\";");
    output_page($page); 
} else {
    eval("\$page = \"".$templates->get("ficha_no_existe")."\";");
    output_page($page);
}
// eval("\$page = \"".$templates->get("ficha")."\";");
// output_page($page);



