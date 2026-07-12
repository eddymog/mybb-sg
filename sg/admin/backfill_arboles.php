<?php
/**
 * MyBB 1.8
 *
 * Backfill del campo `arboles` para fichas existentes.
 * Para cada ficha arma el espejo de los 5 árboles (4 fijos + el del clan)
 * usando sg_build_arbol(), y lo guarda en mybb_sg_sg_fichas.arboles.
 *
 * Uso:
 *   /sg/admin/backfill_arboles.php           -> solo rellena fichas con `arboles` vacío
 *   /sg/admin/backfill_arboles.php?force=1   -> sobrescribe TODAS las fichas
 *
 * Requiere antes:
 *   ALTER TABLE `mybb_sg_sg_fichas` ADD `arboles` MEDIUMTEXT NOT NULL AFTER `nivel`;
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'backfill_arboles.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/sg_functions.php";

global $db, $mybb;

$uid = (int) $mybb->user['uid'];
if (!(is_mod($uid) || is_staff($uid))) {
    die('Sin permisos.');
}

$force = (bool) $mybb->get_input('force');

// Mapa clan_id -> técnica base del clan (mismo que en nueva_ficha.php)
$clan_tecnicas = array(
    101 => 'ABUR101B', 102 => 'SENJ101B', 103 => 'AKIM101B', 104 => 'HYUG101B',
    105 => 'NARA101B', 106 => 'UCHI101B', 108 => 'YAMA101B', 109 => 'INUZ101B',
    110 => 'SARU101B', 301 => 'YUKI101B', 302 => 'KAGU101B', 304 => 'HOZU101B',
    305 => 'HOSH101B', 306 => 'HEIZ101B', 307 => 'TERU101B', 308 => 'AKIZ101B',
    309 => 'FUNA101B', 310 => 'KODO101B'
);

// Árboles fijos: se construyen una sola vez y se reutilizan
$arboles_fijos = array();
foreach (array('bukijutsu', 'defensivo', 'resistencia', 'taijutsu') as $a) {
    $arboles_fijos[$a] = sg_build_arbol($db, $a);
}

// Cache del árbol de cada clan (clan_id -> nombre de árbol)
$clan_arbol_cache = array();
function sg_arbol_de_clan($db, $clan_id, $clan_tecnicas, &$cache)
{
    if (array_key_exists($clan_id, $cache)) {
        return $cache[$clan_id];
    }
    $arbol = '';
    if (isset($clan_tecnicas[$clan_id])) {
        $tid = $db->escape_string($clan_tecnicas[$clan_id]);
        $q = $db->query("SELECT arbol FROM mybb_sg_sg_tecnicas WHERE tid='$tid'");
        while ($r = $db->fetch_array($q)) {
            $arbol = strtolower(trim($r['arbol']));
        }
    }
    $cache[$clan_id] = $arbol;
    return $arbol;
}

// Recolectar fichas primero (evita mezclar SELECT abierto con UPDATEs)
$fichas = array();
$qf = $db->query("SELECT fid, clan, arboles FROM mybb_sg_sg_fichas");
while ($f = $db->fetch_array($qf)) {
    $fichas[] = $f;
}

$actualizadas = 0;
$saltadas = 0;

foreach ($fichas as $f) {
    $fid = (int) $f['fid'];

    if (!$force && isset($f['arboles']) && trim($f['arboles']) !== '') {
        $saltadas++;
        continue;
    }

    $clan_id = (int) $f['clan'];
    $clan_arbol = sg_arbol_de_clan($db, $clan_id, $clan_tecnicas, $clan_arbol_cache);

    $arboles_ficha = $arboles_fijos; // copia de los 4 fijos
    if ($clan_arbol !== '') {
        $arboles_ficha[$clan_arbol] = sg_build_arbol($db, $clan_arbol);
    }

    $arboles_json = $db->escape_string(json_encode($arboles_ficha, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $db->query("UPDATE `mybb_sg_sg_fichas` SET `arboles`='$arboles_json' WHERE `fid`='$fid'");
    $actualizadas++;
}

header('Content-Type: text/plain; charset=utf-8');
echo "Backfill de `arboles` completado.\n";
echo "Fichas actualizadas: $actualizadas\n";
echo "Fichas saltadas (ya tenían datos): $saltadas\n";
echo $force ? "Modo: FORZADO (sobrescribió todo)\n" : "Modo: normal (solo vacías). Usa ?force=1 para rehacer todas.\n";
exit;
