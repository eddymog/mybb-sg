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
define('THIS_SCRIPT', 'recompensa_diaria.php');
require_once "./../global.php";
require "./../inc/config.php";

global $templates, $mybb;

$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$recompensa_accepted = $mybb->get_input('rec_ready');
$reload_js = "<script>window.location.href = window.location.href;</script>";
$reload_script = '';
$log_var = '';

/* Grupos con al menos 1 post garantizado en la racha (staff/admin). */
function tiene_min_post_garantizado() {
    global $db, $uid;
    $garantizado = false;
    $query = $db->query("
        SELECT uid FROM `mybb_sg_users`
        WHERE uid='$uid' AND (additionalgroups LIKE '3%' OR additionalgroups LIKE '%,3' OR additionalgroups LIKE '%,3,%' OR additionalgroups LIKE '4%' OR additionalgroups LIKE '%,4' OR additionalgroups LIKE '%,4,%' OR usergroup = '3' OR usergroup = '4')
    ");
    while ($q = $db->fetch_array($query)) { $garantizado = true; }
    return $garantizado;
}

/* SG aún no tiene cofres en las recompensas. Cuando se añadan, define el helper
   y entrega objetos del inventario en los hitos de racha:

function darObjeto($objeto_id) {
    global $db, $uid;
    $cantidad_actual = 0;
    $tiene = false;
    $q = $db->query("SELECT * FROM mybb_sg_sg_inventario WHERE uid='$uid' AND objeto_id='$objeto_id'");
    while ($r = $db->fetch_array($q)) { $tiene = true; $cantidad_actual = $r['cantidad']; }
    $cantidad_nueva = intval($cantidad_actual) + 1;
    if ($tiene) {
        $db->query("UPDATE `mybb_sg_sg_inventario` SET cantidad='$cantidad_nueva' WHERE objeto_id='$objeto_id' AND uid='$uid'");
    } else {
        $db->query("INSERT INTO `mybb_sg_sg_inventario` (objeto_id, uid, cantidad) VALUES ('$objeto_id', '$uid', '1')");
    }
}
*/

$ficha_existe = false;
$moderated = false;
$should_accept = true;
$claimed_after_96_hours = false;
$claimed_after_48_hours = false;
$two_days = 2 * 24 * 3600;
$time_now = time();
$next_two_days = time() + $two_days;
$last_two_days = time() - $two_days;
$time_to_accept = 0;
$days_count = 0;
$days_season_count = 0;

/* ¿Hay una recompensa en curso? */
$query_recompensa_actual = $db->query("
    SELECT * FROM mybb_sg_sg_recompensas_usuarios WHERE uid='$uid'
");

while ($q = $db->fetch_array($query_recompensa_actual)) {
    $days_count = $q['dia'];
    $days_season_count = $q['season'];
    // base de las últimas 48h según la última vez que se reclamó
    $last_two_days = $q['tiempo'] - $two_days;
    // ya pasó el tiempo suficiente para reclamar
    $should_accept = time() > $q['tiempo'];
    // pasó demasiado tiempo (la recompensa expira)
    $claimed_after_96_hours = time() > ($q['tiempo'] + $two_days);
    $claimed_after_48_hours = time() > $q['tiempo'];
    // tiempo restante antes de poder reclamar otra vez
    $time_to_accept = $q['tiempo'] - time();
}

/* Racha máxima (dia consecutivo) y racha total acumulada (season). */
$recompensas_reclamadas = 0;
$recompensas_racha_maxima = 0;
$recompensas_temporada_maxima = 0;

$total_recompensas_query = $db->query("SELECT COUNT(*) as total FROM `mybb_sg_sg_audit_recompensas` WHERE uid='$uid'");
while ($q = $db->fetch_array($total_recompensas_query)) { $recompensas_reclamadas = $q['total']; }

$recompensas_maxima_query = $db->query("
    SELECT a.*
    FROM mybb_sg_sg_audit_recompensas a
    INNER JOIN (
        SELECT uid, MAX(dia) dia
        FROM mybb_sg_sg_audit_recompensas
        GROUP BY uid
    ) b ON a.uid = b.uid AND a.dia = b.dia
    WHERE a.uid=$uid
    ORDER BY dia DESC
    LIMIT 10
");
while ($q = $db->fetch_array($recompensas_maxima_query)) {
    $recompensas_racha_maxima = $q['dia'];
}

$recompensas_temporada_query = $db->query("
    SELECT a.*
    FROM mybb_sg_sg_audit_recompensas a
    INNER JOIN (
        SELECT uid, MAX(season) season
        FROM mybb_sg_sg_audit_recompensas
        GROUP BY uid
    ) b ON a.uid = b.uid AND a.season = b.season
    WHERE a.uid=$uid
    ORDER BY season DESC
    LIMIT 10
");
while ($q = $db->fetch_array($recompensas_temporada_query)) {
    $recompensas_temporada_maxima = $q['season'];
}

/* La recompensa fue aceptada y ya es momento de reclamar */
if ($recompensa_accepted == 'true' && $should_accept) {

    // Candado por-usuario contra doble reclamo concurrente (varias pestañas con el
    // botón disponible). El estado se RE-VALIDA con datos frescos DENTRO del lock:
    // solo el primer request cobra; el resto encuentra la racha ya avanzada
    // (tiempo actualizado) y no cobra. Mismo patrón que el Dojo.
    $lock_name = "sg_recompensa_" . (int) $uid;
    $got_lock = 0;
    $rl = $db->query("SELECT GET_LOCK('$lock_name', 5) AS l");
    while ($r = $db->fetch_array($rl)) { $got_lock = intval($r['l']); }

    $puede_reclamar = false;
    if ($got_lock === 1) {
        $puede_reclamar = true; // sin fila previa = primer reclamo de la racha
        $q_fresh = $db->query("SELECT tiempo, dia FROM mybb_sg_sg_recompensas_usuarios WHERE uid='$uid'");
        while ($r = $db->fetch_array($q_fresh)) {
            $days_count     = $r['dia'];           // usar el día FRESCO
            $puede_reclamar = time() > $r['tiempo'];
        }
    }

    if ($puede_reclamar) {

    $query_ficha = $db->query("SELECT * FROM mybb_sg_sg_fichas WHERE fid='$uid'");
    while ($f = $db->fetch_array($query_ficha)) { $f_var = $f; }
    $query_usuario = $db->query("SELECT * FROM mybb_sg_users WHERE uid='$uid'");
    while ($u = $db->fetch_array($query_usuario)) { $u_var = $u; }

    $nombre     = $f_var['nombre'];
    $ryos       = intval($f_var['ryos']);
    $tobi       = intval($f_var['tobi']);
    $experiencia = floatval($u_var['newpoints']);

    $new_ryos = $ryos;
    $new_tobi = $tobi;
    $new_exp  = $experiencia;
    $recompensa_items = '';

    if ($days_count == 0 && $recompensas_temporada_maxima < 40) {
        $recompensa_items = '10 Experiencia';
        $new_exp = $experiencia + 10;
    } else if ($days_count == 1 && $recompensas_temporada_maxima < 40) {
        $recompensa_items = '100 Ryos';
        $new_ryos = $ryos + 100;
    } else if ($days_count == 2 && $recompensas_temporada_maxima < 40) {
        $recompensa_items = '1 Tobi';
        $new_tobi = $tobi + 1;
    } else if ($days_count == 3 && $recompensas_temporada_maxima < 40) {
        $recompensa_items = '100 Ryos, 1 Tobi y 10 Experiencia';
        $new_ryos = $ryos + 100;
        $new_tobi = $tobi + 1;
        $new_exp  = $experiencia + 10;
    } else if ($days_count == 4 && $recompensas_temporada_maxima < 40) {
        $recompensa_items = '150 Ryos, 1 Tobi y 15 Experiencia';
        $new_ryos = $ryos + 150;
        $new_tobi = $tobi + 1;
        $new_exp  = $experiencia + 15;
    } else if ($days_count >= 5 && $recompensas_temporada_maxima < 40) {
        $recompensa_items = '200 Ryos, 1 Tobi y 20 Experiencia';
        $new_ryos = $ryos + 200;
        $new_tobi = $tobi + 1;
        $new_exp  = $experiencia + 20;
    } else if ($recompensas_temporada_maxima > 41 && (($recompensas_temporada_maxima + 1) % 5) == 0) {
        $recompensa_items = '800 Ryos, 4 Tobi y 50 Experiencia';
        $new_ryos = $ryos + 800;
        $new_tobi = $tobi + 4;
        $new_exp  = $experiencia + 50;
    } else if ($recompensas_temporada_maxima >= 40) {
        $recompensa_items = '400 Ryos, 2 Tobi y 30 Experiencia';
        $new_ryos = $ryos + 400;
        $new_tobi = $tobi + 2;
        $new_exp  = $experiencia + 30;
    }

    $log = "Ryos: $ryos->$new_ryos & Tobi: $tobi->$new_tobi & Exp: $experiencia->$new_exp";

    $grupo = uniqid();
    sg_ficha_set_campo($uid, 'ryos', $new_ryos, 'usuario', SG_ORIGEN_RECOMPENSA_DIARIA, $recompensa_items, $grupo);
    sg_ficha_set_campo($uid, 'tobi', $new_tobi, 'usuario', SG_ORIGEN_RECOMPENSA_DIARIA, $recompensa_items, $grupo);
    sg_usuario_set_campo($uid, 'newpoints', $new_exp, 'usuario', SG_ORIGEN_RECOMPENSA_DIARIA, $recompensa_items, $grupo);

    $days_count = intval($days_count) + 1;
    $days_season_count = intval($recompensas_temporada_maxima) + 1;

    /* Cofres por hito de racha (SG aún no los tiene; descomenta cuando existan):
    if ($days_season_count == 5)  { darObjeto('OBJ_X'); }
    else if ($days_season_count == 10) { darObjeto('OBJ_X'); }
    else if ($days_season_count == 15) { darObjeto('OBJ_X'); }
    else if ($days_season_count == 20) { darObjeto('OBJ_X'); }
    else if ($days_season_count == 25) { darObjeto('OBJ_X'); }
    else if ($days_season_count == 30) { darObjeto('OBJ_X'); }
    else if ($days_season_count == 35) { darObjeto('OBJ_X'); }
    else if ($days_season_count == 40) { darObjeto('OBJ_X'); }
    */

    $db->query("DELETE FROM mybb_sg_sg_recompensas_usuarios WHERE uid='$uid'");

    $db->query("
        INSERT INTO `mybb_sg_sg_recompensas_usuarios`(`uid`, `nombre`, `dia`, `season`, `tiempo`) VALUES ('$uid','$nombre','$days_count','$days_season_count','$next_two_days')
    ");

    $complete_log = "$nombre ($uid). Has ganado $recompensa_items en esta ronda. $log.";

    $db->query("
        INSERT INTO `mybb_sg_sg_audit_recompensas`(`tiempo_completado`, `tiempo_nuevo`, `dia`, `season`, `uid`, `nombre`, `audit`) VALUES ($time_now, $next_two_days, $days_count, '$days_season_count', '$uid','$nombre','$complete_log')
    ");

    eval('$log_var = $complete_log;');
    eval('$reload_script = $reload_js;');

    } // fin if ($puede_reclamar)

    if ($got_lock === 1) {
        $db->query("SELECT RELEASE_LOCK('$lock_name')");
    }
}

/* ¿Existe la ficha y está aprobada? */
$query_ficha = $db->query("SELECT * FROM mybb_sg_sg_fichas WHERE fid='$uid'");
$nombre = '';
while ($f = $db->fetch_array($query_ficha)) {
    $moderated = $f['moderated'] != 'no_moderacion';
    $ficha_existe = true;
    $nombre = $f['nombre'];
}

/* Render */
if ($ficha_existe == true && $moderated == true) {

    $num_posts = 0;
    $time_left = 0;

    // posts en las últimas 48h (foros de rol bajo parentlist 37)
    $query_posts = $db->query("
        SELECT p.dateline as post_date FROM mybb_sg_posts as p
        INNER JOIN mybb_sg_threads as t ON p.tid = t.tid
        INNER JOIN mybb_sg_forums as f ON t.fid = f.fid
        WHERE p.dateline > $last_two_days
        AND f.parentlist LIKE '37,%'
        AND p.uid = '$uid'
        AND p.visible = 1
        ORDER BY p.dateline ASC
    ");

    $dates_arr = array();
    while ($q = $db->fetch_array($query_posts)) {
        $time_left = $two_days - (time() - $q['post_date']);
        array_push($dates_arr, $time_left);
    }
    $num_posts = count($dates_arr);

    // staff: cuenta como mínimo 1 post aunque no haya posteado
    if (tiene_min_post_garantizado() && $num_posts == 0) {
        $num_posts = 1;
    }

    // la recompensa expira: pasaron 96h, o tras 48h no hubo al menos 1 post
    if ($claimed_after_96_hours || ($num_posts < 1 && $claimed_after_48_hours)) {
        $db->query("DELETE FROM mybb_sg_sg_recompensas_usuarios WHERE uid='$uid'");
        $days_count = 0;
        $last_two_days = time() - $two_days;

        $query_posts = $db->query("
            SELECT p.dateline as post_date FROM mybb_sg_posts as p
            INNER JOIN mybb_sg_threads as t ON p.tid = t.tid
            INNER JOIN mybb_sg_forums as f ON t.fid = f.fid
            WHERE p.dateline > $last_two_days
            AND f.parentlist LIKE '37,%'
            AND p.uid = '$uid'
            AND p.visible = 1
            ORDER BY p.dateline ASC
        ");

        $dates_arr = array();
        while ($q = $db->fetch_array($query_posts)) {
            $time_left = $two_days - (time() - $q['post_date']);
            array_push($dates_arr, $time_left);
        }
        $num_posts = count($dates_arr);
        if (tiene_min_post_garantizado() && $num_posts == 0) {
            $num_posts = 1;
        }
    }

    if ($num_posts >= 1 && $should_accept && $days_count > 0) {
        $time_left = $two_days + $time_to_accept;
    } else if ($num_posts >= 1 && $should_accept) {
        // dates_arr puede estar vacío si num_posts se forzó a 1 por grupo privilegiado
        $time_left = !empty($dates_arr) ? $dates_arr[count($dates_arr) - 1] : $two_days;
    } else if ($num_posts >= 1 && !$should_accept) {
        $time_left = $time_to_accept;
    } else if ($num_posts < 1 && $days_count > 0) {
        $time_left = $time_to_accept;
    }

    $recompensa_items = '';

    if ($days_count == 0 && $recompensas_temporada_maxima < 40) {
        $recompensa_items = '10 Experiencia';
    } else if ($days_count == 1 && $recompensas_temporada_maxima < 40) {
        $recompensa_items = '100 Ryos';
    } else if ($days_count == 2 && $recompensas_temporada_maxima < 40) {
        $recompensa_items = '1 Tobi';
    } else if ($days_count == 3 && $recompensas_temporada_maxima < 40) {
        $recompensa_items = '100 Ryos, 1 Tobi y 10 Experiencia';
    } else if ($days_count == 4 && $recompensas_temporada_maxima < 40) {
        $recompensa_items = '150 Ryos, 1 Tobi y 15 Experiencia';
    } else if ($days_count >= 5 && $recompensas_temporada_maxima < 40) {
        $recompensa_items = '200 Ryos, 1 Tobi y 20 Experiencia';
    } else if ($recompensas_temporada_maxima > 41 && (($recompensas_temporada_maxima + 1) % 5) == 0) {
        $recompensa_items = '800 Ryos, 4 Tobi y 50 Experiencia';
    } else if ($recompensas_temporada_maxima >= 40) {
        $recompensa_items = '400 Ryos, 2 Tobi y 30 Experiencia';
    }

    // progreso de la racha de temporada hacia el hito 40 (donde las recompensas escalan)
    $temporada_meta = 40;
    $temporada_pct = min(100, intval(round((min(intval($recompensas_temporada_maxima), $temporada_meta) / $temporada_meta) * 100)));

    eval("\$page = \"".$templates->get("sg_recompensa")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sg_ficha_no_existe")."\";");
    output_page($page);
}
