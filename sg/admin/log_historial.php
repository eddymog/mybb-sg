<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 * Consola del HISTORIAL estructurado de cambios de ficha (mybb_sg_sg_historial_*).
 * A diferencia de log_consola / log_consola_mod (auditoría de texto libre de
 * Staff), esta consola lee las 3 tablas estructuradas (campos escalares,
 * técnicas, objetos) mezcladas cronológicamente, con filtros por usuario, tipo,
 * origen y dominio. Misma fuente de verdad que el tab "Historial" de la ficha.
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'log_historial.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/sg_functions.php";

global $templates, $mybb, $db;
$uid = $mybb->user['uid'];

if (!(is_mod($uid) || is_staff($uid))) {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
    exit;
}

// ── Filtros ───────────────────────────────────────────────────
$f_usuario = trim($mybb->get_input('f_usuario'));
$tipo      = trim($mybb->get_input('tipo'));
$origen    = trim($mybb->get_input('origen'));
$dominio   = trim($mybb->get_input('dominio'));
$page      = intval($mybb->get_input('page'));
if ($page < 1) { $page = 1; }
$perpage   = 30;

if ($tipo !== 'staff' && $tipo !== 'usuario') { $tipo = ''; }
if ($dominio !== 'ficha' && $dominio !== 'tecnica' && $dominio !== 'objeto') { $dominio = ''; }

// f_usuario -> lista de uids de fichas cuyo nombre o username coincide.
$uid_list = array();
$filtra_usuario = ($f_usuario !== '');
if ($filtra_usuario) {
    $e = $db->escape_string($f_usuario);
    $qu = $db->query("
        SELECT f.fid FROM mybb_sg_sg_fichas f
        LEFT JOIN mybb_sg_users u ON u.uid = f.fid
        WHERE f.nombre LIKE '%$e%' OR u.username LIKE '%$e%'
        LIMIT 500
    ");
    while ($r = $db->fetch_array($qu)) { $uid_list[] = (int) $r['fid']; }
}

// ── Consulta base (UNION de las 3 tablas normalizado) ─────────
$union = "
    SELECT 'ficha' AS dominio, id, uid, actor_uid, grupo, tipo, origen, tiempo,
           campo, valor_anterior, valor_nuevo, NULL AS ref_id, NULL AS cantidad, NULL AS accion
    FROM mybb_sg_sg_historial_ficha
    UNION ALL
    SELECT 'tecnica', id, uid, actor_uid, grupo, tipo, origen, tiempo,
           NULL, NULL, NULL, tecnica_id, NULL, accion
    FROM mybb_sg_sg_historial_tecnicas
    UNION ALL
    SELECT 'objeto', id, uid, actor_uid, grupo, tipo, origen, tiempo,
           NULL, NULL, NULL, objeto_id, cantidad, NULL
    FROM mybb_sg_sg_historial_objetos
";

$where = array();
if ($tipo    !== '') { $where[] = "tipo='" . $db->escape_string($tipo) . "'"; }
if ($dominio !== '') { $where[] = "dominio='" . $db->escape_string($dominio) . "'"; }
if ($origen  !== '') { $where[] = "origen='" . $db->escape_string($origen) . "'"; }
if ($filtra_usuario) {
    // Sin coincidencias -> forzar vacío.
    $where[] = empty($uid_list) ? "1=0" : ("uid IN (" . implode(',', $uid_list) . ")");
}
$where_sql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));
$base = "FROM ($union) t $where_sql";

// ── Total + paginación ────────────────────────────────────────
$total = 0;
$qc = $db->query("SELECT COUNT(*) AS c $base");
while ($r = $db->fetch_array($qc)) { $total = (int) $r['c']; }

$pages = (int) max(1, ceil($total / $perpage));
if ($page > $pages) { $page = $pages; }
$offset = ($page - 1) * $perpage;

// ── Filas de la página actual ─────────────────────────────────
$filas = array();
$qp = $db->query("SELECT * $base ORDER BY tiempo DESC, id DESC LIMIT $offset, $perpage");
while ($r = $db->fetch_array($qp)) { $filas[] = $r; }

// ── Resolver nombres (ficha, actor, técnica, objeto) ──────────
$uids = array(); $actores = array(); $tec_ids = array(); $obj_ids = array();
foreach ($filas as $r) {
    $uids[(int) $r['uid']] = true;
    $actores[(int) $r['actor_uid']] = true;
    if ($r['dominio'] === 'tecnica' && $r['ref_id'] !== null && $r['ref_id'] !== '') { $tec_ids[$r['ref_id']] = true; }
    if ($r['dominio'] === 'objeto'  && $r['ref_id'] !== null && $r['ref_id'] !== '') { $obj_ids[$r['ref_id']] = true; }
}

$nombres_ficha = array(); $nombres_actor = array();
$ids_todos = $uids + $actores;
if (!empty($ids_todos)) {
    $in = implode(',', array_map('intval', array_keys($ids_todos)));
    $qn = $db->query("
        SELECT f.fid, f.nombre, u.uid, u.username
        FROM mybb_sg_users u
        LEFT JOIN mybb_sg_sg_fichas f ON f.fid = u.uid
        WHERE u.uid IN ($in)
    ");
    while ($r = $db->fetch_array($qn)) {
        if ($r['nombre'] !== null && $r['nombre'] !== '') { $nombres_ficha[(int) $r['uid']] = $r['nombre']; }
        $nombres_actor[(int) $r['uid']] = $r['username'];
    }
}

$tec_nombres = array(); $obj_nombres = array();
if (!empty($tec_ids)) {
    $in = array();
    foreach (array_keys($tec_ids) as $x) { $in[] = "'" . $db->escape_string($x) . "'"; }
    $qt = $db->query("SELECT tid, nombre FROM mybb_sg_sg_tecnicas WHERE tid IN (" . implode(',', $in) . ")");
    while ($r = $db->fetch_array($qt)) { $tec_nombres[$r['tid']] = $r['nombre']; }
}
if (!empty($obj_ids)) {
    $in = array();
    foreach (array_keys($obj_ids) as $x) { $in[] = "'" . $db->escape_string($x) . "'"; }
    $qo = $db->query("SELECT objeto_id, nombre FROM mybb_sg_sg_objetos WHERE objeto_id IN (" . implode(',', $in) . ")");
    while ($r = $db->fetch_array($qo)) { $obj_nombres[$r['objeto_id']] = $r['nombre']; }
}

// ── Render de filas ───────────────────────────────────────────
$logs = "";
foreach ($filas as $r) {
    $fid_h    = (int) $r['uid'];
    $actor_h  = (int) $r['actor_uid'];
    $ficha_nom = isset($nombres_ficha[$fid_h]) ? $nombres_ficha[$fid_h] : ('UID ' . $fid_h);
    $actor_nom = isset($nombres_actor[$actor_h]) ? $nombres_actor[$actor_h] : ('UID ' . $actor_h);

    $tipo_h   = htmlspecialchars($r['tipo'], ENT_QUOTES);
    $origen_h = htmlspecialchars(sg_origen_label($r['origen']), ENT_QUOTES);
    $tiempo_h = htmlspecialchars($r['tiempo'], ENT_QUOTES);
    $fichaN   = htmlspecialchars($ficha_nom, ENT_QUOTES);
    $actorN   = htmlspecialchars($actor_nom, ENT_QUOTES);

    // Descripción del cambio según dominio.
    if ($r['dominio'] === 'ficha') {
        $camp = htmlspecialchars(sg_campo_label($r['campo']), ENT_QUOTES);
        $va = $r['valor_anterior']; $vn = $r['valor_nuevo'];
        $delta = '';
        if (is_numeric($va) && is_numeric($vn)) {
            $d = (float) $vn - (float) $va;
            if ($d != 0) { $delta = ' (' . ($d > 0 ? '+' : '') . rtrim(rtrim(sprintf('%.2f', $d), '0'), '.') . ')'; }
        }
        // Campos de trasfondo (texto largo): recorte más generoso que el resto,
        // porque acá no hay "Leer más" interactivo (eso vive en el tab de la ficha).
        $recorte = sg_campo_es_texto_largo($r['campo']) ? 220 : 60;
        $desc = $camp . ': ' . htmlspecialchars(sg_hist_trim($va, $recorte), ENT_QUOTES)
              . ' → ' . htmlspecialchars(sg_hist_trim($vn, $recorte), ENT_QUOTES) . htmlspecialchars($delta, ENT_QUOTES);
    } else if ($r['dominio'] === 'tecnica') {
        $nom = isset($tec_nombres[$r['ref_id']]) ? $tec_nombres[$r['ref_id']] : $r['ref_id'];
        $verbo = ($r['accion'] === 'quitar') ? 'Quitó técnica' : 'Aprendió técnica';
        $desc = $verbo . ': ' . htmlspecialchars($nom, ENT_QUOTES);
    } else {
        $nom = isset($obj_nombres[$r['ref_id']]) ? $obj_nombres[$r['ref_id']] : $r['ref_id'];
        $c = (int) $r['cantidad'];
        $desc = 'Objeto ' . ($c > 0 ? '+' : '') . $c . ': ' . htmlspecialchars($nom, ENT_QUOTES);
    }

    $dominio_h = htmlspecialchars($r['dominio'], ENT_QUOTES);

    $logs .= "<article class='sg-log'>
        <div class='sg-log__head'>
            <span class='sg-log__id'>#{$r['id']}</span>
            <span class='sg-log__meta'><span class='sg-log__label'>Ficha</span>{$fichaN}</span>
            <span class='sg-log__meta'><span class='sg-log__label'>Actor</span>{$actorN}</span>
            <span class='sg-log__meta'><span class='sg-log__label'>Tipo</span>{$tipo_h}</span>
            <span class='sg-log__meta'><span class='sg-log__label'>Origen</span>{$origen_h}</span>
            <span class='sg-log__time'>{$tiempo_h}</span>
        </div>
        <div class='sg-log__body'>{$desc}</div>
    </article>";
}
if ($logs === "") {
    $logs = "<div class='sg-log-empty'>No hay registros que coincidan con el filtro.</div>";
}

// ── Resumen y paginación ──────────────────────────────────────
$desde = ($total === 0) ? 0 : ($offset + 1);
$hasta = min($offset + $perpage, $total);
$resumen = "Mostrando {$desde}–{$hasta} de {$total}";

$qs = array();
if ($f_usuario !== '') { $qs[] = 'f_usuario=' . urlencode($f_usuario); }
if ($tipo      !== '') { $qs[] = 'tipo=' . urlencode($tipo); }
if ($origen    !== '') { $qs[] = 'origen=' . urlencode($origen); }
if ($dominio   !== '') { $qs[] = 'dominio=' . urlencode($dominio); }
$base_qs = implode('&', $qs);

$mk = function ($p, $label, $cls = '') use ($base_qs) {
    $q = 'page=' . $p . ($base_qs !== '' ? '&' . $base_qs : '');
    return "<a class='sg-page {$cls}' href='?{$q}'>{$label}</a>";
};

$pagination = '';
if ($pages > 1) {
    if ($page > 1) { $pagination .= $mk($page - 1, '‹'); }
    $start = max(1, $page - 2);
    $end   = min($pages, $page + 2);
    if ($start > 1) {
        $pagination .= $mk(1, '1');
        if ($start > 2) { $pagination .= "<span class='sg-page sg-page--gap'>…</span>"; }
    }
    for ($p = $start; $p <= $end; $p++) {
        $pagination .= $mk($p, $p, $p === $page ? 'sg-page--active' : '');
    }
    if ($end < $pages) {
        if ($end < $pages - 1) { $pagination .= "<span class='sg-page sg-page--gap'>…</span>"; }
        $pagination .= $mk($pages, $pages);
    }
    if ($page < $pages) { $pagination .= $mk($page + 1, '›'); }
}

// Valores para prellenar los filtros.
$f_usuario_val = htmlspecialchars($f_usuario, ENT_QUOTES);
$origen_val    = htmlspecialchars($origen, ENT_QUOTES);
$tipo_val      = htmlspecialchars($tipo, ENT_QUOTES);
$dominio_val   = htmlspecialchars($dominio, ENT_QUOTES);

eval('$logs_li = $logs;');
eval("\$page_html = \"".$templates->get("staff_log_historial")."\";");
output_page($page_html);
