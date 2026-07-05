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

// Tarjeta de virtud/defecto asignado (mismo formato que sg_vt_card en
// registro_virtudes.php, con clases fx- para heredar el tema de la ficha).
function fx_virtud_card($r)
{
    $puntos    = intval($r['puntos']);
    $es_virtud = $puntos >= 0;

    $nombre = htmlspecialchars($r['nombre'], ENT_QUOTES);
    $vid    = htmlspecialchars($r['virtud_id'], ENT_QUOTES);
    $desc   = nl2br(htmlspecialchars($r['descripcion'], ENT_QUOTES));

    $abs       = abs($puntos);
    $costo_lbl = ($es_virtud ? '+' : '−') . $abs;
    $costo_cls = $es_virtud ? 'fx-vt-cost--v' : 'fx-vt-cost--d';

    $excl = intval($r['exclusivo']) === 1
        ? "<span class=\"fx-vt-badge fx-vt-badge--excl\">Exclusivo</span>"
        : '';

    return "<article class=\"fx-vt-item\">"
        . "<div class=\"fx-vt-head\">"
        . "<h4 class=\"fx-vt-name\">$nombre</h4>"
        . "<span class=\"fx-vt-cost $costo_cls\">$costo_lbl</span>"
        . "</div>"
        . "<div class=\"fx-vt-meta\"><span class=\"fx-vt-badge\">$vid</span>$excl</div>"
        . "<p class=\"fx-vt-desc\">$desc</p>"
        . "</article>";
}

$uid = $mybb->get_input('uid');
$action = $mybb->get_input('action');
$module = $mybb->get_input('module'); 
$s_uid = $mybb->user['uid'];

$cambiar_avatar1 = addslashes($_POST["cambiar_avatar1"]);
$cambiar_avatar2 = addslashes($_POST["cambiar_avatar2"]);

if ($cambiar_avatar1 != '') {
    $db->query(" UPDATE `mybb_sg_users` SET `avatar`='$cambiar_avatar1' WHERE `uid`='$s_uid'; ");
}

if ($cambiar_avatar2 != '') {
    $db->query(" UPDATE `mybb_sg_sg_fichas` SET `banner`='$cambiar_avatar2' WHERE `fid`='$s_uid'; ");
}

// Fotos por pestaña. Las columnas se agregan luego a mybb_sg_sg_fichas;
// se guarda solo si la columna existe, así no rompe mientras no estén.
$foto_fields = array(
    'foto_expediente'  => 'cambiar_foto_expediente',
    'foto_perfil'   => 'cambiar_foto_perfil',
);
$hay_foto_post = false;
foreach ($foto_fields as $postkey) {
    if (isset($_POST[$postkey]) && trim($_POST[$postkey]) !== '') { $hay_foto_post = true; }
}
if ($hay_foto_post) {
    $cols_fichas = array();
    $q_cols = $db->query("SHOW COLUMNS FROM mybb_sg_sg_fichas");
    while ($c = $db->fetch_array($q_cols)) { $cols_fichas[$c['Field']] = true; }
    foreach ($foto_fields as $col => $postkey) {
        if (isset($_POST[$postkey]) && trim($_POST[$postkey]) !== '' && isset($cols_fichas[$col])) {
            $val = addslashes(trim($_POST[$postkey]));
            $db->query("UPDATE `mybb_sg_sg_fichas` SET `$col`='$val' WHERE `fid`='$s_uid'");
        }
    }
}

$is_owner = $mybb->user['uid'] == $mybb->get_input('uid');

$ficha_existe = false;
$moderated = false;
eval('$action = $action;');
eval('$module = $module;');
eval('$userid = $uid;');

$query_ficha = $db->query("
    SELECT * FROM mybb_sg_sg_fichas WHERE fid='$uid'
");



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

        $avatar_img = $avatar;
        eval('$avatar = $avatar_img;');
        eval('$user_avatar = $avatar;');
        eval('$usuario = $u;');
        $puntos_rol = intval(floor($usuario['newpoints']));
    }

    // Actividad en el foro (mensajes, temas, fecha de ingreso) — ya vive en mybb_sg_users
    $sg_postnum     = isset($usuario['postnum']) ? intval($usuario['postnum']) : 0;
    $sg_threadnum   = isset($usuario['threadnum']) ? intval($usuario['threadnum']) : 0;
    $sg_regdate     = isset($usuario['regdate']) ? intval($usuario['regdate']) : 0;
    $sg_regdate_fmt = $sg_regdate > 0 ? date('d/m/Y', $sg_regdate) : '—';
    eval('$sgPostnum = $sg_postnum;');
    eval('$sgThreadnum = $sg_threadnum;');
    eval('$sgRegdateFmt = $sg_regdate_fmt;');

    // Semana actual (mismo epoch que newpoints) y timestamp de cierre para el contador
    $sg_exp_epoch  = 1721620800;
    $semana_actual = (int) ceil((time() - $sg_exp_epoch) / 604800);
    $fin_semana_ts = $sg_exp_epoch + ($semana_actual * 604800);
    $semana_display = $semana_actual - 100; // numeración mostrada (in-rol)

    // Experiencia semanal acumulada (tope 100) de la semana actual
    $exp_semanal = 0;
    $query_exp_sem = $db->query("SELECT experiencia_semanal FROM mybb_sg_sg_experiencia_limite WHERE uid='$uid' AND semana='$semana_actual'");
    while ($e = $db->fetch_array($query_exp_sem)) {
        $exp_semanal = $e['experiencia_semanal'];
    }
    $exp_semanal = intval(floor($exp_semanal));

    $query_ficha = $db->query("
        SELECT * FROM mybb_sg_sg_fichas WHERE fid='$uid'
    ");

    while ($f = $db->fetch_array($query_ficha)) {


        if ($f['moderated'] == 'no_moderacion') {
            $aprobada = false;
        } else {
            $aprobada = true;
        }

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
        $kosei1 = $f['kosei1'];
        $kosei2 = $f['kosei2'];

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
            $sombra1 = '';
            $sombra2 = '';
            $sg_ficha_theme = 'neutral';
            $sg_ficha_accent = '#b63d34';
            $sg_ficha_accent_strong = '#d45144';
            $sg_ficha_accent_soft = 'rgba(182, 61, 52, 0.16)';
            $sg_ficha_accent_glow = 'rgba(182, 61, 52, 0.24)';
            $sg_ficha_surface = 'rgba(24, 16, 22, 0.96)';
            $sg_ficha_surface2 = 'rgba(35, 22, 30, 0.96)';
            $sg_ficha_panel = 'rgba(26, 18, 24, 0.94)';
            $sg_ficha_card = 'rgba(31, 21, 29, 0.92)';
            $sg_ficha_card_soft = 'rgba(39, 27, 36, 0.78)';
            $sg_ficha_border = 'rgba(255, 255, 255, 0.08)';
            $sg_ficha_border_strong = 'rgba(255, 255, 255, 0.14)';
            $sg_ficha_paper = '#f3ece7';
            $sg_ficha_paper_text = '#2b221e';
            $sg_ficha_theme_label = $v['nombreVilla'];

            if ($villa == 1) { 
                $villa_color = 'fhko'; 
                $villa_var_color = '--konoha-group-color'; 
                $sombra1 = '#4b1822';
                $sombra2 = '#341019';
                $sg_ficha_theme = 'konoha';
                $sg_ficha_accent = '#8e3145';
                $sg_ficha_accent_strong = '#b24b63';
                $sg_ficha_accent_soft = 'rgba(142, 49, 69, 0.18)';
                $sg_ficha_accent_glow = 'rgba(178, 75, 99, 0.28)';
                $sg_ficha_surface = 'rgba(24, 12, 18, 0.97)';
                $sg_ficha_surface2 = 'rgba(34, 16, 24, 0.97)';
                $sg_ficha_panel = 'rgba(29, 14, 21, 0.94)';
                $sg_ficha_card = 'rgba(37, 18, 27, 0.92)';
                $sg_ficha_card_soft = 'rgba(47, 25, 35, 0.78)';
                $sg_ficha_theme_label = 'Konoha';
            }
               
            if ($villa == 3) { 
                $villa_color = 'fhki';
                $villa_var_color = '--kiri-group-color';  
                $sombra1 = '#1a2e4c';
                $sombra2 = '#122038';
                $sg_ficha_theme = 'kiri';
                $sg_ficha_accent = '#4c6fa8';
                $sg_ficha_accent_strong = '#7192c9';
                $sg_ficha_accent_soft = 'rgba(76, 111, 168, 0.18)';
                $sg_ficha_accent_glow = 'rgba(113, 146, 201, 0.28)';
                $sg_ficha_surface = 'rgba(12, 18, 30, 0.97)';
                $sg_ficha_surface2 = 'rgba(16, 24, 40, 0.97)';
                $sg_ficha_panel = 'rgba(14, 21, 35, 0.94)';
                $sg_ficha_card = 'rgba(19, 28, 45, 0.92)';
                $sg_ficha_card_soft = 'rgba(24, 37, 58, 0.78)';
                $sg_ficha_theme_label = 'Kiri';
            }
            // if ($villa == 4) { 
            //     $villa_color = 'fhiw'; 
            //     $villa_var_color = '--iwa-group-color';  
            //     $sombra1 = '#FFEAC6';
            //     $sombra2 = '#DAB067';
            // }
            if ($villa == 4) { 
                $villa_color = 'fhiw'; 
                $villa_var_color = '--iwa-group-color';  
                $sombra1 = '#6d4020';
                $sombra2 = '#533018';
                $sg_ficha_accent = '#ca8740';
                $sg_ficha_accent_strong = '#e4ab68';
                $sg_ficha_accent_soft = 'rgba(202, 135, 64, 0.18)';
                $sg_ficha_accent_glow = 'rgba(228, 171, 104, 0.28)';
                $sg_ficha_surface = 'rgba(28, 20, 14, 0.97)';
                $sg_ficha_surface2 = 'rgba(40, 28, 19, 0.97)';
                $sg_ficha_panel = 'rgba(34, 24, 16, 0.94)';
                $sg_ficha_card = 'rgba(44, 31, 21, 0.92)';
                $sg_ficha_card_soft = 'rgba(56, 40, 28, 0.78)';
            }
            if ($villa == 5) { 
                $villa_color = 'fhku'; 
                $villa_var_color = '--kumo-group-color';
                $sombra1 = '#7b6421';
                $sombra2 = '#5d4a18';
                $sg_ficha_accent = '#cfb04a';
                $sg_ficha_accent_strong = '#ead07a';
                $sg_ficha_accent_soft = 'rgba(207, 176, 74, 0.18)';
                $sg_ficha_accent_glow = 'rgba(234, 208, 122, 0.28)';
                $sg_ficha_surface = 'rgba(28, 24, 13, 0.97)';
                $sg_ficha_surface2 = 'rgba(39, 33, 17, 0.97)';
                $sg_ficha_panel = 'rgba(33, 28, 14, 0.94)';
                $sg_ficha_card = 'rgba(42, 36, 18, 0.92)';
                $sg_ficha_card_soft = 'rgba(54, 46, 24, 0.78)';
            }
            if ($villa == 6) { 
                $villa_color = 'fhre'; 
                $villa_var_color = '--nsa-group-color';  
                $sombra1 = '#6e1117';
                $sombra2 = '#530b10';
                $sg_ficha_accent = '#cb3f4c';
                $sg_ficha_accent_strong = '#e46672';
                $sg_ficha_accent_soft = 'rgba(203, 63, 76, 0.18)';
                $sg_ficha_accent_glow = 'rgba(228, 102, 114, 0.28)';
                $sg_ficha_surface = 'rgba(28, 13, 17, 0.97)';
                $sg_ficha_surface2 = 'rgba(40, 18, 24, 0.97)';
                $sg_ficha_panel = 'rgba(34, 15, 20, 0.94)';
                $sg_ficha_card = 'rgba(44, 19, 25, 0.92)';
                $sg_ficha_card_soft = 'rgba(56, 25, 32, 0.78)';
            }
            if ($villa == 7) { 
                $villa_color = 'fhnsa'; 
                $villa_var_color = '--renegados-group-color'; 
                $sombra1 = '#5a2f4f';
                $sombra2 = '#43233b'; 
                $sg_ficha_accent = '#b56aa3';
                $sg_ficha_accent_strong = '#d494c4';
                $sg_ficha_accent_soft = 'rgba(181, 106, 163, 0.18)';
                $sg_ficha_accent_glow = 'rgba(212, 148, 196, 0.28)';
                $sg_ficha_surface = 'rgba(25, 15, 24, 0.97)';
                $sg_ficha_surface2 = 'rgba(35, 20, 33, 0.97)';
                $sg_ficha_panel = 'rgba(30, 17, 28, 0.94)';
                $sg_ficha_card = 'rgba(38, 22, 36, 0.92)';
                $sg_ficha_card_soft = 'rgba(49, 30, 46, 0.78)';
            }

            eval('$villaVarColor = $villa_var_color;');
            eval('$villaColor = $villa_color;');
            eval('$sgFichaTheme = $sg_ficha_theme;');
            eval('$sgFichaAccent = $sg_ficha_accent;');
            eval('$sgFichaAccentStrong = $sg_ficha_accent_strong;');
            eval('$sgFichaAccentSoft = $sg_ficha_accent_soft;');
            eval('$sgFichaAccentGlow = $sg_ficha_accent_glow;');
            eval('$sgFichaSurface = $sg_ficha_surface;');
            eval('$sgFichaSurface2 = $sg_ficha_surface2;');
            eval('$sgFichaPanel = $sg_ficha_panel;');
            eval('$sgFichaCard = $sg_ficha_card;');
            eval('$sgFichaCardSoft = $sg_ficha_card_soft;');
            eval('$sgFichaBorder = $sg_ficha_border;');
            eval('$sgFichaBorderStrong = $sg_ficha_border_strong;');
            eval('$sgFichaPaper = $sg_ficha_paper;');
            eval('$sgFichaPaperText = $sg_ficha_paper_text;');
            eval('$sgFichaThemeLabel = $sg_ficha_theme_label;');
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
        $reg_c = $f['tenketsu'] * 4;
        $suma_stats_var = $f['str'] + $f['res'] + $f['spd'] + $f['agi'] + $f['dex'] + $f['pres'] + $f['inte'] + $f['ctrl'];
        $sg_vida_bar = min(100, max(8, round(($f['vida'] / max($v, 1)) * 100)));
        $sg_chakra_bar = min(100, max(8, round(($f['chakra'] / max($c, 1)) * 100)));

        $historia_var     = nl2br($ficha['historia']);
        $apariencia_var   = nl2br($ficha['apariencia']);
        $personalidad_var = nl2br($ficha['personalidad']);
        $virtudes_var     = nl2br($ficha['virtudes']);
        $defectos_var     = nl2br($ficha['defectos']);
        $extra_var        = nl2br($ficha['extra']);
        $frase_var        = nl2br($ficha['frase']);
        $limite_nivel = $ficha['limite_nivel'];
        $nivel = $ficha['nivel'];
        $puntos_estadistica = intval($ficha['puntos_estadistica']);
        $mejoras = intval($ficha['mejoras']);

        if ($puntos_rol >= 9800 && $nivel == '19') {
            $nivel = 20;
            $puntos_estadistica += 15; $mejoras += 1;
            $db->query("  UPDATE `mybb_sg_sg_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica',`mejoras`='$mejoras' WHERE `fid`='$uid'; ");

        } else if ($puntos_rol >= 8700 && $nivel == '18') {
            $nivel = 19;
            $puntos_estadistica += 15; $mejoras += 1;
            $db->query("  UPDATE `mybb_sg_sg_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica',`mejoras`='$mejoras' WHERE `fid`='$uid'; ");

        } else if ($puntos_rol >= 7700 && $nivel == '17') {
            $nivel = 18;
            $puntos_estadistica += 15; $mejoras += 1;
            $db->query("  UPDATE `mybb_sg_sg_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica',`mejoras`='$mejoras' WHERE `fid`='$uid'; ");

        } else if ($puntos_rol >= 6800 && $nivel == '16') {
            $nivel = 17;
            $puntos_estadistica += 15; $mejoras += 1;
            $db->query("  UPDATE `mybb_sg_sg_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica',`mejoras`='$mejoras' WHERE `fid`='$uid'; ");

        } else if ($puntos_rol >= 6000 && $nivel == '15') {
            $nivel = 16;
            $puntos_estadistica += 15; $mejoras += 1;
            $db->query("  UPDATE `mybb_sg_sg_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica',`mejoras`='$mejoras' WHERE `fid`='$uid'; ");

        } else if ($puntos_rol >= 5250 && $nivel == '14') {
            $nivel = 15;
            $puntos_estadistica += 15; $mejoras += 1;
            $db->query("  UPDATE `mybb_sg_sg_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica',`mejoras`='$mejoras' WHERE `fid`='$uid'; ");

        } else if ($puntos_rol >= 4550 && $nivel == '13') {
            $nivel = 14;
            $puntos_estadistica += 15; $mejoras += 1;
            $db->query("  UPDATE `mybb_sg_sg_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica',`mejoras`='$mejoras' WHERE `fid`='$uid'; ");

        } else if ($puntos_rol >= 3900 && $nivel == '12') {
            $nivel = 13;
            $puntos_estadistica += 15; $mejoras += 1;
            $db->query("  UPDATE `mybb_sg_sg_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica',`mejoras`='$mejoras' WHERE `fid`='$uid'; ");
        } else if ($puntos_rol >= 3300 && $nivel == '11') {
            $nivel = 12;
            $puntos_estadistica += 15; $mejoras += 1;
            $db->query("  UPDATE `mybb_sg_sg_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica',`mejoras`='$mejoras' WHERE `fid`='$uid'; ");
        } else if ($puntos_rol >= 2750 && $nivel == '10') {
            $nivel = 11;
            $puntos_estadistica += 15; $mejoras += 1;
            $db->query("  UPDATE `mybb_sg_sg_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica',`mejoras`='$mejoras' WHERE `fid`='$uid'; ");
        } else if ($puntos_rol >= 2250 && $nivel == '9') {
            $nivel = 10;
            $puntos_estadistica += 15; $mejoras += 1;
            $db->query("  UPDATE `mybb_sg_sg_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica',`mejoras`='$mejoras' WHERE `fid`='$uid'; ");
        } else if ($puntos_rol >= 1800 && $nivel == '8') {
            $nivel = 9;
            $puntos_estadistica += 15; $mejoras += 1;
            $db->query("  UPDATE `mybb_sg_sg_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica',`mejoras`='$mejoras' WHERE `fid`='$uid'; ");
        } else if ($puntos_rol >= 1400 && $nivel == '7') {
            $nivel = 8;
            $puntos_estadistica += 15; $mejoras += 1;
            $db->query("  UPDATE `mybb_sg_sg_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica',`mejoras`='$mejoras' WHERE `fid`='$uid'; ");
        } else if ($puntos_rol >= 1050 && $nivel == '6') {
            $nivel = 7;
            $puntos_estadistica += 15; $mejoras += 1;
            $db->query("  UPDATE `mybb_sg_sg_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica',`mejoras`='$mejoras' WHERE `fid`='$uid'; ");
        } else if ($puntos_rol >= 750 && $nivel == '5') {
            $nivel = 6;
            $puntos_estadistica += 15; $mejoras += 1;
            $db->query("  UPDATE `mybb_sg_sg_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica',`mejoras`='$mejoras' WHERE `fid`='$uid'; ");
        } else if ($puntos_rol >= 500 && $nivel == '4') {
            $nivel = 5;
            $puntos_estadistica += 15; $mejoras += 1;
            $db->query("  UPDATE `mybb_sg_sg_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica',`mejoras`='$mejoras' WHERE `fid`='$uid'; ");
        } else if ($puntos_rol >= 300 && $nivel == '3') {
            $nivel = 4;
            $puntos_estadistica += 15; $mejoras += 1;
            $db->query("  UPDATE `mybb_sg_sg_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica',`mejoras`='$mejoras' WHERE `fid`='$uid'; ");
        } else if ($puntos_rol >= 150 && $nivel == '2') {
            $nivel = 3;
            $puntos_estadistica += 15; $mejoras += 1;
            $db->query("  UPDATE `mybb_sg_sg_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica',`mejoras`='$mejoras' WHERE `fid`='$uid'; ");
        } else if ($puntos_rol >= 50 && $nivel == '1') {
            $nivel = 2;
            $puntos_estadistica += 15; $mejoras += 1;
            $db->query(" UPDATE `mybb_sg_sg_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica',`mejoras`='$mejoras',`mejoras`='$mejoras' WHERE `fid`='$uid'; ");
        }


        
        eval('$vida = $v;');
        eval('$aguante = $a;');
        eval('$chakra = $c;');
        eval('$regA = $reg_a;');
        eval('$regC = $reg_c;');
        eval('$historia = $historia_var;');
        eval('$apariencia = $apariencia_var;');
        eval('$personalidad = $personalidad_var;');
        eval('$virtudes = $virtudes_var;');
        eval('$defectos = $defectos_var;');
        // NOTA: $ficha['virtudes'] / $ficha['defectos'] (texto libre, arriba) quedan
        // en desuso — se eliminarán cuando la lista automática de abajo los reemplace.

        // Virtudes/defectos asignados de verdad (catálogo real, no texto libre)
        $virtudes_auto_html = '';
        $defectos_auto_html = '';
        $n_virtudes_auto = 0;
        $n_defectos_auto = 0;
        $query_vt_auto = $db->query("
            SELECT v.virtud_id, v.nombre, v.puntos, v.exclusivo, v.descripcion
            FROM mybb_sg_sg_virtudes_usuarios vu
            INNER JOIN mybb_sg_sg_virtudes v ON v.virtud_id = vu.virtud_id
            WHERE vu.uid='$uid'
            ORDER BY v.nombre ASC
        ");
        while ($vt = $db->fetch_array($query_vt_auto)) {
            if (intval($vt['puntos']) >= 0) {
                $virtudes_auto_html .= fx_virtud_card($vt);
                $n_virtudes_auto++;
            } else {
                $defectos_auto_html .= fx_virtud_card($vt);
                $n_defectos_auto++;
            }
        }
        if ($n_virtudes_auto === 0) { $virtudes_auto_html = "<div class=\"fx-vt-empty\">Sin virtudes asignadas.</div>"; }
        if ($n_defectos_auto === 0) { $defectos_auto_html = "<div class=\"fx-vt-empty\">Sin defectos asignados.</div>"; }
        eval('$virtudesAutoHtml = $virtudes_auto_html;');
        eval('$defectosAutoHtml = $defectos_auto_html;');
        eval('$nVirtudesAuto = $n_virtudes_auto;');
        eval('$nDefectosAuto = $n_defectos_auto;');
        eval('$extra = $extra_var;');
        eval('$frase = $frase_var;');
        eval('$sgVidaBar = $sg_vida_bar;');
        eval('$sgChakraBar = $sg_chakra_bar;');

        // Aviso al dueño: puntos de estadística / mejoras sin asignar
        $sg_puntos_aviso = 0;
        $sg_puntos_msg = '';
        if ($is_owner && ($puntos_estadistica > 0 || $mejoras > 0)) {
            $sg_puntos_aviso = 1;
            $partes = array();
            if ($puntos_estadistica > 0) { $partes[] = $puntos_estadistica . ' de estadística'; }
            if ($mejoras > 0) { $partes[] = $mejoras . ' mejora' . ($mejoras > 1 ? 's' : ''); }
            $sg_puntos_msg = 'Tienes ' . implode(' y ', $partes) . ' sin asignar';
        }
        eval('$sgPuntosAviso = $sg_puntos_aviso;');
        eval('$sgPuntosMsg = "'.addslashes($sg_puntos_msg).'";');

        // Fotos por pestaña (columnas opcionales; vacío si aún no existen)
        $sg_foto_expediente  = isset($f['foto_expediente'])  ? $f['foto_expediente']  : '';
        $sg_foto_perfil   = isset($f['foto_perfil'])   ? $f['foto_perfil']   : '';
        eval('$sgFotoExpediente = $sg_foto_expediente;');
        eval('$sgFotoPerfil = $sg_foto_perfil;');

        // Estado del sujeto para la cabecera de expediente (villa 7 = renegados)
        $sg_estado = (intval($f['villa']) == 7) ? 'Desertor' : 'Activo';
        eval('$sgEstado = $sg_estado;');

        // Progreso al siguiente nivel (mismos umbrales de XP que la subida de nivel de arriba)
        $xp_umbrales = array(1=>50,2=>150,3=>300,4=>500,5=>750,6=>1050,7=>1400,8=>1800,9=>2250,10=>2750,11=>3300,12=>3900,13=>4550,14=>5250,15=>6000,16=>6800,17=>7700,18=>8700,19=>9800);
        $niv = intval($nivel);
        $xp  = intval($puntos_rol);
        if ($niv >= 20 || !isset($xp_umbrales[$niv])) {
            $sg_nivel_pct = 100; $sg_xp_faltan = 0; $sg_nivel_max = 1;
        } else {
            $prev = ($niv >= 2 && isset($xp_umbrales[$niv - 1])) ? $xp_umbrales[$niv - 1] : 0;
            $next = $xp_umbrales[$niv];
            $span = max(1, $next - $prev);
            $sg_nivel_pct = max(0, min(100, round((($xp - $prev) / $span) * 100)));
            $sg_xp_faltan = max(0, $next - $xp);
            $sg_nivel_max = 0;
        }
        $sg_nivel_sig = $niv + 1;
        eval('$sgNivelPct = $sg_nivel_pct;');
        eval('$sgXpFaltan = $sg_xp_faltan;');
        eval('$sgNivelMax = $sg_nivel_max;');
        eval('$sgNivelSig = $sg_nivel_sig;');
    }
    $can_view_staff_notes = ($s_uid == $uid || is_staff($s_uid) || is_peti_mod($s_uid));
    $query_tec_aprendidas = $db->query("
        SELECT * FROM `mybb_sg_sg_tecnicas` 
        INNER JOIN `mybb_sg_sg_tec_aprendidas` 
        ON `mybb_sg_sg_tecnicas`.`tid`=`mybb_sg_sg_tec_aprendidas`.`tid` 
        WHERE `mybb_sg_sg_tec_aprendidas`.`uid`='$uid'
    ");

    $tec_aprendidas = array();
    
    while ($tec_aprendida = $db->fetch_array($query_tec_aprendidas)) {
        $tec_aprendida['descripcion'] = nl2br($tec_aprendida['descripcion']);
        $key = trim($tec_aprendida['arbol']);
        if ($key === '') { $key = 'Sin árbol'; }

        if (!$tec_aprendidas[$key]) {
            $tec_aprendidas[$key] = array();
        }
        array_push($tec_aprendidas[$key], $tec_aprendida);
    }
    $tec_aprendidas_json = json_encode($tec_aprendidas);
    
    eval('$tec_aprendidas = "'.addslashes($tec_aprendidas_json).'";');
    eval('$sgFichaCanViewStaffNotes = "'.$can_view_staff_notes.'";');
    eval("\$ficha_script = \"".$templates->get("sg_ficha_script")."\";");

    eval("\$page = \"".$templates->get("sg_ficha")."\";");
    output_page($page);

} else if ($ficha_existe == false && $mybb->user['uid'] != 0 && $mybb->user['uid'] == $uid) {
    $uid = $db->query("
    SELECT * FROM mybb_sg_sg_fichas WHERE fid='$uid'
    ");

    // clanes
    $query_clanes = $db->query("
        SELECT * FROM mybb_sg_sg_clanes WHERE activo='1'
    ");
    $clanes = array();
    while ($clan = $db->fetch_array($query_clanes)) {
        unset($clan['descripcion']);
        array_push($clanes, $clan);
    }
    $clanes_json = json_encode($clanes);

    // villas
    $query_villas = $db->query("
        SELECT * FROM mybb_sg_sg_villas WHERE activa='1'
    ");
    $villas = array();
    while ($villa = $db->fetch_array($query_villas)) {
        array_push($villas, $villa);
    }
    $villas_json = json_encode($villas);


    $sinClanKonoha = 1;
    $sinClanKiri = 1;
    $sinClanIwa = 1;
    $sinClanKumo = 1;
    $sinClanSinAldea = 1;

    function getVillasSinClan($villaId) {
        $last_two_weeks = time() - (14 * 24 * 3600);

        return "
            SELECT villa, nombreClan, count(nombreClan) as numeroPjs FROM (SELECT DISTINCT fichas.villa, clanes.nombreClan, p.username FROM mybb_sg_posts as p 
            INNER JOIN mybb_sg_threads as t ON p.tid = t.tid 
            INNER JOIN mybb_sg_forums as f ON t.fid = f.fid 
            INNER JOIN mybb_sg_sg_fichas as fichas ON p.uid = fichas.fid 
            INNER JOIN mybb_sg_sg_clanes as clanes ON clanes.cid = fichas.clan 
            WHERE p.dateline > '$last_two_weeks'
            AND villa=$villaId AND nombreClan='Sin Clan'                              
            AND f.parentlist LIKE '37,%') t
            GROUP BY nombreClan
            ORDER BY villa, nombreClan;
        ";


    }

    $querySinClanKonoha = $db->query(getVillasSinClan(1));
    $querySinClanKiri = $db->query(getVillasSinClan(3));
    // $querySinClanIwa = $db->query(getVillasSinClan(4)); 
    // $querySinClanKumo = $db->query(getVillasSinClan(5)); 
    // $querySinClanSinAldea = $db->query(getVillasSinClan(7)); 

    while ($q = $db->fetch_array($querySinClanKonoha)) {  if (intval($q['numeroPjs']) >= 2) {   $sinClanKonoha = 0;   } }
    while ($q = $db->fetch_array($querySinClanKiri)) {  if (intval($q['numeroPjs']) >= 2) {   $sinClanKiri = 0;   } }
    // while ($q = $db->fetch_array($querySinClanIwa)) {  if (intval($q['numeroPjs']) >= 2) {   $sinClanIwa = 0;   } }
    // while ($q = $db->fetch_array($querySinClanKumo)) {  if (intval($q['numeroPjs']) >= 2) {   $sinClanKumo = 0;   } }
    // while ($q = $db->fetch_array($querySinClanSinAldea)) {  if (intval($q['numeroPjs']) >= 2) {   $sinClanSinAldea = 0;   } }

    // create variables
    eval('$clanes = "'.addslashes($clanes_json).'";');
    eval('$villas = "'.addslashes($villas_json).'";');
    eval('$nueva_ficha_script = "'.$templates->get('sg_nueva_ficha_script').'";');

    eval("\$page = \"".$templates->get("sg_nueva_ficha")."\";");
    output_page($page);
} else if ($ficha_existe == true && $moderated == false && $mybb->user['uid'] == $uid && $mybb->user['uid'] != 0) {
    eval("\$page = \"".$templates->get("sg_ficha_en_moderacion")."\";");
    output_page($page); 
} else {
    eval("\$page = \"".$templates->get("sg_ficha_no_existe")."\";");
    output_page($page);
}
// eval("\$page = \"".$templates->get("ficha")."\";");
// output_page($page);
