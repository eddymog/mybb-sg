<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 * Registro público de personajes: lista todos los personajes con ficha
 * APROBADA (moderated != 'no_moderacion'), su físico registrado (para que
 * nadie repita uno ya en uso) y si postearon en la zona de rol (parentlist
 * '37,%') en los últimos 60 días. Filtra client-side (JSON embebido), mismo
 * patrón que tecnicas_lista.php / la pestaña Inventario de ficha.php.
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'registro_personajes.php');
require_once "./../global.php";
require "./../inc/config.php";
require_once "./functions/sg_functions.php";

global $templates, $mybb, $db;

$sesenta_dias = 60 * 24 * 3600;
$umbral_actividad = time() - $sesenta_dias;

$query = $db->query("
    SELECT f.fid, f.nombre, f.apodo, f.rango, f.nivel, f.fisico_de_pj,
           v.nombreVilla, c.nombreClan, c2.nombreClan AS nombreClan2,
           u.avatar, ua.ultimo_post
    FROM mybb_sg_sg_fichas f
    LEFT JOIN mybb_sg_sg_villas v ON v.vid = f.villa
    LEFT JOIN mybb_sg_sg_clanes c ON c.cid = f.clan
    LEFT JOIN mybb_sg_sg_clanes c2 ON c2.cid = f.clan2
    LEFT JOIN mybb_sg_users u ON u.uid = f.fid
    LEFT JOIN (
        SELECT p.uid, MAX(p.dateline) AS ultimo_post
        FROM mybb_sg_posts p
        INNER JOIN mybb_sg_threads t ON p.tid = t.tid
        INNER JOIN mybb_sg_forums fo ON t.fid = fo.fid
        WHERE fo.parentlist LIKE '37,%' AND p.visible = 1
        GROUP BY p.uid
    ) ua ON ua.uid = f.fid
    WHERE f.moderated != 'no_moderacion'
    AND f.tiempo_creacion > '2026-06-01'
    AND f.nombre NOT LIKE '%Moderador%'
    AND f.nombre NOT LIKE '%Narrador%'
    AND f.rango != 'Staff'
    ORDER BY ua.ultimo_post IS NULL, ua.ultimo_post DESC, f.nombre ASC
");

$personajes  = array();
$villas_set  = array();
$clanes_set  = array();
$rangos_set  = array();

while ($f = $db->fetch_array($query)) {
    $ultimo_post = ($f['ultimo_post'] !== null) ? (int) $f['ultimo_post'] : 0;
    $activo = ($ultimo_post > 0 && $ultimo_post >= $umbral_actividad);

    $villa_nombre = ($f['nombreVilla'] !== null && $f['nombreVilla'] !== '') ? $f['nombreVilla'] : 'Sin aldea';
    $clan_nombre  = ($f['nombreClan']  !== null && $f['nombreClan']  !== '') ? $f['nombreClan']  : 'Sin clan';
    $clan2_nombre = ($f['nombreClan2'] !== null && $f['nombreClan2'] !== '') ? $f['nombreClan2'] : '';
    $rango        = ($f['rango'] !== null && $f['rango'] !== '') ? $f['rango'] : 'Sin rango';

    // Mismo ajuste de ruta que usa ficha.php: desde /sg/ hay que subir un
    // nivel para llegar a /uploads/avatars/ del root del foro.
    $avatar = ($f['avatar'] !== null) ? $f['avatar'] : '';
    if (substr($avatar, 0, 18) === './uploads/avatars/') {
        $avatar = './.' . $avatar;
    }

    $personajes[] = array(
        'fid'         => (int) $f['fid'],
        'nombre'      => $f['nombre'],
        'apodo'       => $f['apodo'],
        'villa'       => $villa_nombre,
        'clan'        => $clan_nombre,
        'clan2'       => $clan2_nombre,
        'rango'       => $rango,
        'nivel'       => (int) $f['nivel'],
        'fisico'      => $f['fisico_de_pj'],
        'avatar'      => $avatar,
        'ultimo_post' => $ultimo_post,
        'activo'      => $activo,
    );

    $villas_set[$villa_nombre] = true;
    $clanes_set[$clan_nombre]  = true;
    $rangos_set[$rango]        = true;
}

$villas_list = array_keys($villas_set); sort($villas_list);
$clanes_list = array_keys($clanes_set); sort($clanes_list);
$rangos_list = array_keys($rangos_set); sort($rangos_list);

$total_activos = 0;
foreach ($personajes as $p) { if ($p['activo']) { $total_activos++; } }

$personajes_json = json_encode($personajes, JSON_UNESCAPED_UNICODE);
$villas_json     = json_encode($villas_list, JSON_UNESCAPED_UNICODE);
$clanes_json     = json_encode($clanes_list, JSON_UNESCAPED_UNICODE);
$rangos_json     = json_encode($rangos_list, JSON_UNESCAPED_UNICODE);

eval('$sgRegistroPersonajesJson = $personajes_json;');
eval('$sgRegistroVillasJson = $villas_json;');
eval('$sgRegistroClanesJson = $clanes_json;');
eval('$sgRegistroRangosJson = $rangos_json;');
eval('$sgRegistroTotal = '.count($personajes).';');
eval('$sgRegistroActivos = '.$total_activos.';');

eval("\$page = \"".$templates->get("sg_registro_personajes")."\";");
output_page($page);
