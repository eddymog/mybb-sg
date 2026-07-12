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

// Tarjeta SELECCIONABLE de virtud/defecto para la creación de ficha.
// Cada tarjeta lleva el valor en puntos y su lista de incompatibles para que
// el JS bloquee combinaciones inválidas y calcule el balance en vivo.
function nf_vd_card($r, $incompat_map)
{
    $puntos = intval($r['puntos']);
    $nombre = htmlspecialchars($r['nombre'], ENT_QUOTES);
    $vid    = htmlspecialchars($r['virtud_id'], ENT_QUOTES);
    $desc   = nl2br(htmlspecialchars($r['descripcion'], ENT_QUOTES));

    $costo_lbl = ($puntos >= 0 ? '+' : '−') . abs($puntos);
    $costo_cls = $puntos >= 0 ? 'nf-vd-pts--v' : 'nf-vd-pts--d';

    $inc = isset($incompat_map[$r['virtud_id']]) ? $incompat_map[$r['virtud_id']] : array();
    $inc_attr = htmlspecialchars(implode(',', $inc), ENT_QUOTES);

    return "<label class=\"nf-vd-card\" data-vid=\"$vid\" data-pts=\"$puntos\" data-incompat=\"$inc_attr\">"
        . "<input type=\"checkbox\" name=\"vd_sel[]\" value=\"$vid\">"
        . "<span class=\"nf-vd-card-head\">"
        . "<span class=\"nf-vd-card-name\">$nombre</span>"
        . "<span class=\"nf-vd-pts $costo_cls\">$costo_lbl</span>"
        . "</span>"
        . "<span class=\"nf-vd-card-id\">$vid</span>"
        . "<span class=\"nf-vd-card-desc\">$desc</span>"
        . "</label>";
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

// ── Edición inline de campos de trasfondo (dueño de la ficha o staff) ──
// El formulario del modal envía sg_edit_campo + sg_edit_valor por POST y apunta
// a ?uid=<fid de la ficha>. Se corre antes del SELECT de abajo para que el valor
// nuevo se muestre de inmediato al re-renderizar.
$campos_transfondo = array(
    'historia'     => true,
    'apariencia'   => true,
    'personalidad' => true,
    'frase'        => true,
    'extra'        => true,
);
$sg_edit_campo = $mybb->get_input('sg_edit_campo');
if ($sg_edit_campo !== '' && isset($campos_transfondo[$sg_edit_campo])) {
    $target_fid   = intval($mybb->get_input('uid'));
    $puede_editar = ($target_fid > 0 && $mybb->user['uid'] == $target_fid) || $g_is_staff;
    if ($puede_editar && $target_fid > 0) {
        $valor = $db->escape_string($mybb->get_input('sg_edit_valor'));
        $db->query("UPDATE `mybb_sg_sg_fichas` SET `$sg_edit_campo`='$valor' WHERE `fid`='$target_fid'");
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

// El dueño puede ver su propia ficha aunque esté en moderación (además de
// staff/mods). Sigue editable solo el trasfondo; la aprobación es aparte.
if ($ficha_existe == true && ($moderated == true || is_mod($s_uid) || is_staff($s_uid) || ($is_owner && intval($mybb->user['uid']) > 0))) {
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
        $nombreClan = '';
        while ($c = $db->fetch_array($query_clan)) {
            $nombreClan = ucwords($c['nombreClan']);
            eval('$nClan = $nombreClan;');
            eval('$clan = $c;');
        }

        // Clan Híbrido: si la ficha tiene clan2, se muestran ambos clanes.
        // (clan2 es solo para el display; el dojo no lo usa.)
        $sg_clan_label = $nombreClan;
        $clan2_id = isset($f['clan2']) ? trim($f['clan2']) : '';
        if ($clan2_id !== '' && $clan2_id !== '0' && $clan2_id !== '1001') {
            $clan2_esc = $db->escape_string($clan2_id);
            $q_clan2 = $db->query("SELECT nombreClan FROM mybb_sg_sg_clanes WHERE cid='$clan2_esc'");
            while ($c2 = $db->fetch_array($q_clan2)) {
                $nombre2 = ucwords($c2['nombreClan']);
                if ($nombre2 !== '') {
                    $sg_clan_label = ($sg_clan_label !== '') ? ($sg_clan_label . ' · ' . $nombre2) : $nombre2;
                }
            }
        }
        eval('$sgClanLabel = $sg_clan_label;');
    
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
        
        // Pasivas (invisibles): se suman a las estadisticas base para obtener las
        // efectivas y recalcular modificadores, vida, chakra y reg. de chakra.
        // Sobrescribe en $f: fuerza..sigilo, mfuerza..minteligencia, vida, chakra.
        sg_aplicar_pasivas($f);
        eval('$ficha = $f;');

        // Vida / Chakra / Reg. de chakra efectivos (ya incluyen las pasivas).
        $v = intval($f['vida']);
        $c = intval($f['chakra']);
        $a = 0;
        $reg_a = 0;
        $reg_c = intval($f['tenketsu']) * 4;
        $suma_stats_var = $f['str'] + $f['res'] + $f['spd'] + $f['agi'] + $f['dex'] + $f['pres'] + $f['inte'] + $f['ctrl'];
        $sg_vida_bar = min(100, max(8, round(($f['vida'] / max($v, 1)) * 100)));
        $sg_chakra_bar = min(100, max(8, round(($f['chakra'] / max($c, 1)) * 100)));

        $historia_var     = nl2br($ficha['historia']);
        $sg_historia_larga = (mb_strlen($ficha['historia']) > 600) ? 1 : 0;
        $apariencia_var   = nl2br($ficha['apariencia']);
        $personalidad_var = nl2br($ficha['personalidad']);
        $virtudes_var     = nl2br($ficha['virtudes']);
        $defectos_var     = nl2br($ficha['defectos']);
        $extra_var        = nl2br($ficha['extra']);
        $frase_var        = nl2br($ficha['frase']);
        $limite_nivel = $ficha['limite_nivel'];
        $nivel = intval($ficha['nivel']);
        $nivel_antes = $nivel;
        $puntos_estadistica = intval($ficha['puntos_estadistica']);
        $mejoras = intval($ficha['mejoras']);

        // PR (puntos de rol) necesarios para avanzar del nivel indicado al siguiente.
        $umbrales_nivel = array(
            1 => 50,    2 => 150,   3 => 300,   4 => 500,   5 => 750,
            6 => 1050,  7 => 1400,  8 => 1800,  9 => 2250,  10 => 2750,
            11 => 3300, 12 => 3900, 13 => 4550, 14 => 5250, 15 => 6000,
            16 => 6800, 17 => 7700, 18 => 8700, 19 => 9800,
        );

        // Subida de nivel automática BLOQUEADA. Mientras esté en false, la ficha
        // NO sube de nivel sola aunque el PR alcance el umbral (ni otorga puntos,
        // mejoras ni créditos de rama). Poner en true para reactivarla.
        $sg_subida_nivel_activa = false;

        // Sube todos los niveles a los que dé el PR acumulado (no solo uno por carga).
        while ($sg_subida_nivel_activa && isset($umbrales_nivel[$nivel]) && $puntos_rol >= $umbrales_nivel[$nivel]) {
            $nivel++;
            $puntos_estadistica += 15; // +15 puntos de estadística por nivel
            $mejoras += 1;             // +1 mejora por nivel
        }

        $niveles_ganados = $nivel - $nivel_antes;
        if ($niveles_ganados > 0) {
            $prog_lvl = sg_progreso_parse(isset($ficha['arboles_progreso']) ? $ficha['arboles_progreso'] : '');
            $prog_lvl['nivel_rama_disponibles'] = (int) $prog_lvl['nivel_rama_disponibles'] + $niveles_ganados;
            $prog_lvl_json = $db->escape_string(json_encode($prog_lvl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            // Un solo UPDATE con el resultado de la(s) subida(s) de nivel.
            $db->query("
                UPDATE `mybb_sg_sg_fichas`
                SET `nivel`='$nivel',
                    `puntos_estadistica`='$puntos_estadistica',
                    `mejoras`='$mejoras',
                    `arboles_progreso`='$prog_lvl_json'
                WHERE `fid`='$uid'
            ");
        }



        eval('$vida = $v;');
        eval('$aguante = $a;');
        eval('$chakra = $c;');
        eval('$regA = $reg_a;');
        eval('$regC = $reg_c;');
        eval('$historia = $historia_var;');
        eval('$sgHistoriaLarga = $sg_historia_larga;');
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
        $sg_puntos_virtudes = 0; // suma de puntos de virtudes (positivos)
        $sg_puntos_defectos = 0; // suma de puntos de defectos (negativos)
        $sg_bingo_mult = 1.0;    // Fama (+10%) / Impopularidad (−10%) sobre el Bingo
        $query_vt_auto = $db->query("
            SELECT v.virtud_id, v.nombre, v.puntos, v.exclusivo, v.descripcion
            FROM mybb_sg_sg_virtudes_usuarios vu
            INNER JOIN mybb_sg_sg_virtudes v ON v.virtud_id = vu.virtud_id
            WHERE vu.uid='$uid'
            ORDER BY v.nombre ASC
        ");
        while ($vt = $db->fetch_array($query_vt_auto)) {
            $pts = intval($vt['puntos']);
            if ($vt['virtud_id'] === 'VFAMA')  { $sg_bingo_mult = 1.10; }
            if ($vt['virtud_id'] === 'DIMPOP') { $sg_bingo_mult = 0.90; }
            if ($pts >= 0) {
                $virtudes_auto_html .= fx_virtud_card($vt);
                $n_virtudes_auto++;
                $sg_puntos_virtudes += $pts;
            } else {
                $defectos_auto_html .= fx_virtud_card($vt);
                $n_defectos_auto++;
                $sg_puntos_defectos += $pts;
            }
        }

        // Bingo mostrado = valor guardado ajustado por Fama/Impopularidad (relativo).
        $sg_bingo_show = (int) round(intval($f['bingo']) * $sg_bingo_mult);
        eval('$sgBingoShow = $sg_bingo_show;');
        if ($n_virtudes_auto === 0) { $virtudes_auto_html = "<div class=\"fx-vt-empty\">Sin virtudes asignadas.</div>"; }
        if ($n_defectos_auto === 0) { $defectos_auto_html = "<div class=\"fx-vt-empty\">Sin defectos asignados.</div>"; }
        eval('$virtudesAutoHtml = $virtudes_auto_html;');
        eval('$defectosAutoHtml = $defectos_auto_html;');
        eval('$nVirtudesAuto = $n_virtudes_auto;');
        eval('$nDefectosAuto = $n_defectos_auto;');

        // Balance neto entre virtudes y defectos (puntos de virtud - puntos de defecto)
        $sg_puntos_balance = $sg_puntos_virtudes + $sg_puntos_defectos;
        $sg_balance_v_fmt = '+' . $sg_puntos_virtudes;
        $sg_balance_d_fmt = ($sg_puntos_defectos < 0 ? '−' : '+') . abs($sg_puntos_defectos);
        $sg_balance_t_fmt = ($sg_puntos_balance < 0 ? '−' : '+') . abs($sg_puntos_balance);
        eval('$sgBalanceVFmt = $sg_balance_v_fmt;');
        eval('$sgBalanceDFmt = $sg_balance_d_fmt;');
        eval('$sgBalanceTFmt = $sg_balance_t_fmt;');
        eval('$extra = $extra_var;');
        eval('$frase = $frase_var;');
        eval('$sgVidaBar = $sg_vida_bar;');
        eval('$sgChakraBar = $sg_chakra_bar;');

        // Edición inline del trasfondo: quién puede editar + valores crudos
        // (sin nl2br ni escape) para precargar el textarea del modal.
        $sg_puede_editar = ((($is_owner && intval($mybb->user['uid']) > 0) || $g_is_staff) ? 1 : 0);
        // Aviso de ficha pendiente de aprobación (solo para el dueño)
        $sg_en_moderacion = ((!$aprobada && $is_owner && intval($mybb->user['uid']) > 0) ? 1 : 0);
        eval('$sgEnModeracion = $sg_en_moderacion;');
        $sg_raw_historia     = htmlspecialchars($ficha['historia'], ENT_QUOTES);
        $sg_raw_apariencia   = htmlspecialchars($ficha['apariencia'], ENT_QUOTES);
        $sg_raw_personalidad = htmlspecialchars($ficha['personalidad'], ENT_QUOTES);
        $sg_raw_frase        = htmlspecialchars($ficha['frase'], ENT_QUOTES);
        $sg_raw_extra        = htmlspecialchars($ficha['extra'], ENT_QUOTES);
        eval('$sgPuedeEditar = $sg_puede_editar;');
        eval('$sgRawHistoria = $sg_raw_historia;');
        eval('$sgRawApariencia = $sg_raw_apariencia;');
        eval('$sgRawPersonalidad = $sg_raw_personalidad;');
        eval('$sgRawFrase = $sg_raw_frase;');
        eval('$sgRawExtra = $sg_raw_extra;');

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
        eval('$sgPuntosMsg = $sg_puntos_msg;');

        // Fotos por pestaña (columnas opcionales; vacío si aún no existen)
        $sg_foto_perfil = isset($f['foto_perfil']) ? $f['foto_perfil'] : '';
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
    // Solo el dueño (viendo su propia ficha, logueado) puede vender del inventario.
    // Se define antes del script porque sg_ficha_script lo usa como bandera JS.
    $sgInvPuedeVender = ($is_owner && intval($mybb->user['uid']) > 0) ? 'true' : 'false';
    eval("\$ficha_script = \"".$templates->get("sg_ficha_script")."\";");

    // ── Inventario del personaje (pestaña Inventario) ─────────────
    $inv_default_img = '/images/sg/objeto_default.png';
    $inv_html = '';
    $inv_count = 0;
    $inv_tipos = array();   // tipos distintos, en orden, para los chips
    $inv_conteo = array();  // nº de objetos por tipo
    $q_inv = $db->query("
        SELECT o.objeto_id, o.nombre, o.tipo, o.tamano, o.municion, o.descripcion,
               o.efecto1, o.efecto2, o.efecto3, o.imagen, o.coste, i.cantidad
        FROM mybb_sg_sg_inventario i
        INNER JOIN mybb_sg_sg_objetos o ON o.objeto_id = i.objeto_id
        WHERE i.uid = '$uid' AND i.cantidad > 0
        ORDER BY o.tipo, o.nombre
    ");
    while ($it = $db->fetch_array($q_inv)) {
        $inv_count++;
        $i_tipo_disp = trim($it['tipo']) !== '' ? $it['tipo'] : 'Otros';
        $i_nombre    = htmlspecialchars($it['nombre'], ENT_QUOTES);
        $i_tipolabel = htmlspecialchars($i_tipo_disp, ENT_QUOTES);
        $i_data_tipo = htmlspecialchars(strtolower($i_tipo_disp), ENT_QUOTES);
        $i_tam    = htmlspecialchars($it['tamano'], ENT_QUOTES);
        $i_mun    = htmlspecialchars($it['municion'], ENT_QUOTES);
        $i_desc   = htmlspecialchars($it['descripcion'], ENT_QUOTES);
        $i_ef1    = htmlspecialchars($it['efecto1'], ENT_QUOTES);
        $i_ef2    = htmlspecialchars($it['efecto2'], ENT_QUOTES);
        $i_ef3    = htmlspecialchars($it['efecto3'], ENT_QUOTES);
        $i_cant   = intval($it['cantidad']);
        $i_oid    = htmlspecialchars($it['objeto_id'], ENT_QUOTES);
        $i_coste  = intval($it['coste']);
        $i_sell   = ($i_coste >= 99999) ? 0 : (int) floor($i_coste * 0.5); // venta = 50% del coste base
        $i_img    = trim($it['imagen']) !== '' ? htmlspecialchars($it['imagen'], ENT_QUOTES) : $inv_default_img;
        $i_search = htmlspecialchars(strtolower(
            $it['nombre'] . ' ' . $i_tipo_disp . ' ' . $it['tamano'] . ' ' . $it['descripcion'] . ' ' .
            $it['efecto1'] . ' ' . $it['efecto2'] . ' ' . $it['efecto3']
        ), ENT_QUOTES);

        if (!isset($inv_conteo[$i_data_tipo])) { $inv_conteo[$i_data_tipo] = 0; $inv_tipos[] = array($i_tipolabel, $i_data_tipo); }
        $inv_conteo[$i_data_tipo]++;

        $inv_html .= "<article class=\"fx-inv-tile\" tabindex=\"0\" role=\"button\" aria-label=\"$i_nombre\""
            . " data-nombre=\"$i_nombre\" data-img=\"$i_img\" data-cant=\"$i_cant\""
            . " data-oid=\"$i_oid\" data-sell=\"$i_sell\""
            . " data-tipo=\"$i_data_tipo\" data-tipolabel=\"$i_tipolabel\" data-search=\"$i_search\""
            . " data-tamano=\"$i_tam\" data-municion=\"$i_mun\" data-desc=\"$i_desc\""
            . " data-ef1=\"$i_ef1\" data-ef2=\"$i_ef2\" data-ef3=\"$i_ef3\">"
            . "<div class=\"fx-inv-thumb\"><img class=\"fx-inv-img\" src=\"$i_img\" alt=\"$i_nombre\" loading=\"lazy\" onerror=\"fxInvImgFallback(this)\">"
            . "<span class=\"fx-inv-qty\">&times;$i_cant</span></div>"
            . "<div class=\"fx-inv-main\"><div class=\"fx-inv-name\">$i_nombre</div><div class=\"fx-inv-detail\"></div></div>"
            . "</article>";
    }

    // Chips de filtro por categoría (Todos + cada tipo distinto), con su conteo.
    $inv_chips_html = "<button class=\"fx-inv-chip is-active\" type=\"button\" data-tipo=\"\" onclick=\"fxInvSetTipo(this)\">Todos <span class=\"fx-inv-chip-n\">$inv_count</span></button>";
    foreach ($inv_tipos as $t) {
        $tn = $inv_conteo[$t[1]];
        $inv_chips_html .= "<button class=\"fx-inv-chip\" type=\"button\" data-tipo=\"{$t[1]}\" onclick=\"fxInvSetTipo(this)\">{$t[0]} <span class=\"fx-inv-chip-n\">$tn</span></button>";
    }

    $sgInventarioHtml = $inv_html;
    $sgInventarioCount = $inv_count;
    $sgInvChipsHtml = $inv_chips_html;

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

    // Creación de ficha habilitada. (Para deshabilitarla, poner esto en 0 o
    // gatearlo por usuario, p. ej. $mybb->user['username'] === 'Testoman'.)
    $sg_puede_crear = 1;

    // Catálogo de virtudes/defectos seleccionables (solo si puede crear).
    $virtudes_cards = '';
    $defectos_cards = '';
    if ($sg_puede_crear === 1) {
        $incompat_map = sg_virtudes_incompatibilidades();
        $q_vd = $db->query("
            SELECT virtud_id, nombre, puntos, exclusivo, descripcion
            FROM mybb_sg_sg_virtudes
            ORDER BY nombre ASC
        ");
        while ($vd = $db->fetch_array($q_vd)) {
            if (intval($vd['puntos']) >= 0) {
                $virtudes_cards .= nf_vd_card($vd, $incompat_map);
            } else {
                $defectos_cards .= nf_vd_card($vd, $incompat_map);
            }
        }
        if ($virtudes_cards === '') { $virtudes_cards = "<div class=\"nf-vd-empty\">No hay virtudes registradas.</div>"; }
        if ($defectos_cards === '') { $defectos_cards = "<div class=\"nf-vd-empty\">No hay defectos registrados.</div>"; }
    }

    // create variables
    eval('$clanes = "'.addslashes($clanes_json).'";');
    eval('$villas = "'.addslashes($villas_json).'";');
    eval('$nueva_ficha_script = "'.$templates->get('sg_nueva_ficha_script').'";');
    eval('$sgPuedeCrear = $sg_puede_crear;');
    eval('$virtudesCards = $virtudes_cards;');
    eval('$defectosCards = $defectos_cards;');

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
