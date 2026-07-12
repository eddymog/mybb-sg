<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'modificar_ficha.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/sg_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$user_fid = $mybb->get_input('fid');

$es_staff = (is_mod($uid) || is_staff($uid));

$log = "";
$reload_script = '';
$reload_js = "<script>window.location.href = window.location.pathname;</script>";

/* Guardar cambios: solo se actualizan los campos que REALMENTE cambiaron */
if ($mybb->request_method === 'post' && $es_staff) {

    $ficha_id = (int) $_POST['ficha_id'];
    $staff    = addslashes($_POST['staff']);
    $razon    = addslashes($_POST['razon']);

    // Estado actual de la ficha (para comparar contra lo enviado)
    $actual = null;
    if ($ficha_id) {
        $q_actual = $db->query("SELECT * FROM mybb_sg_sg_fichas WHERE fid='$ficha_id'");
        while ($f = $db->fetch_array($q_actual)) { $actual = $f; }
    }

    if ($actual && $staff && $razon) {

        // Campos editables (el resto de la tabla no se toca)
        $campos_texto = array('nombre', 'apodo', 'villa', 'clan', 'clan2', 'sexo', 'rango', 'notas', 'extra', 'frase', 'virtudes', 'defectos');
        $campos_num   = array('ryos', 'bingo', 'edad', 'temporada_nacimiento', 'vida', 'chakra', 'regchakra',
                              'peso', 'altura', 'madara', 'tobi', 'rin', 'fuerza', 'destreza', 'cchakra', 'inteligencia',
                              'mfuerza', 'mdestreza', 'mcchakra', 'minteligencia', 'salud', 'velocidad', 'tenketsu',
                              'sigilo', 'puntos_estadistica', 'nivel',
                              // Pasivas invisibles (solo staff): se suman a las base al mostrar/combatir
                              'pas_fuerza', 'pas_destreza', 'pas_cchakra', 'pas_inteligencia',
                              'pas_salud', 'pas_velocidad', 'pas_tenketsu', 'pas_sigilo',
                              // Acceso al Dojo (0/1)
                              'puede_usar_dojo');

        $sets = array();
        $cambios = array();

        foreach ($campos_texto as $campo) {
            if (!isset($_POST[$campo])) { continue; }
            if ((string) $_POST[$campo] !== (string) $actual[$campo]) {
                $val = addslashes($_POST[$campo]);
                $sets[] = "`$campo`='$val'";
                $cambios[] = "$campo: '".$actual[$campo]."' -> '".$_POST[$campo]."'";
            }
        }

        foreach ($campos_num as $campo) {
            if (!isset($_POST[$campo])) { continue; }
            $nuevo = (int) $_POST[$campo];
            if ((int) $actual[$campo] !== $nuevo) {
                $sets[] = "`$campo`='$nuevo'";
                $cambios[] = "$campo: ".$actual[$campo]." -> ".$nuevo;
            }
        }

        // Contadores del Dojo: viven dentro del JSON `arboles_progreso`. Se editan
        // como enteros (clan_rama_usada solo 0/1; nada negativo) y se re-serializa
        // solo si alguno cambió. Se registran en el mismo log de auditoría.
        $prog_keys = array('desbloqueo_arboles', 'desbloqueo_ramas', 'desbloqueo_nivel_ramas', 'nivel_rama_disponibles', 'clan_rama_usada');
        $prog_actual = sg_progreso_parse(isset($actual['arboles_progreso']) ? $actual['arboles_progreso'] : '');
        $prog_nuevo  = $prog_actual;
        $prog_cambio = false;
        foreach ($prog_keys as $pk) {
            if (!isset($_POST[$pk])) { continue; }
            $nv = (int) $_POST[$pk];
            if ($pk === 'clan_rama_usada') { $nv = $nv ? 1 : 0; }
            if ($nv < 0) { $nv = 0; }
            if ((int) $prog_actual[$pk] !== $nv) {
                $prog_nuevo[$pk] = $nv;
                $prog_cambio = true;
                $cambios[] = "$pk: ".$prog_actual[$pk]." -> ".$nv;
            }
        }
        if ($prog_cambio) {
            $prog_json = $db->escape_string(json_encode($prog_nuevo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $sets[] = "`arboles_progreso`='$prog_json'";
        }

        // Experiencia (PR) vive en mybb_sg_users.newpoints — se trata aparte
        $exp_cambio = false;
        $exp_nueva = 0;
        if (isset($_POST['experiencia'])) {
            $exp_actual = null;
            $q_exp = $db->query("SELECT newpoints FROM mybb_sg_users WHERE uid='$ficha_id'");
            while ($u = $db->fetch_array($q_exp)) { $exp_actual = $u['newpoints']; }
            if ($exp_actual !== null && (float) $exp_actual !== (float) $_POST['experiencia']) {
                $exp_nueva = (float) $_POST['experiencia'];
                $exp_cambio = true;
                $cambios[] = "experiencia: ".$exp_actual." -> ".$exp_nueva;
            }
        }

        // Solo se actúa si hubo al menos un cambio (ficha o experiencia)
        if (!empty($cambios)) {

            if (!empty($sets)) {
                $set_clause = implode(', ', $sets);
                $db->query("UPDATE `mybb_sg_sg_fichas` SET $set_clause WHERE `fid`='$ficha_id'");
            }

            if ($exp_cambio) {
                $db->query("UPDATE `mybb_sg_users` SET newpoints='$exp_nueva' WHERE `uid`='$ficha_id'");
            }

            // Sincronizar usergroup SOLO si la villa cambió y mapea a un grupo conocido
            if (isset($_POST['villa']) && (string) $_POST['villa'] !== (string) $actual['villa']) {
                $villa_usergroup = array('1' => '9', '3' => '8', '4' => '14', '5' => '15', '6' => '13', '7' => '12');
                if (isset($villa_usergroup[$_POST['villa']])) {
                    $nuevo_grupo = $villa_usergroup[$_POST['villa']];
                    $db->query("UPDATE `mybb_sg_users` SET usergroup='$nuevo_grupo' WHERE `uid`='$ficha_id'");
                }
            }

            $log = "Ficha UID $ficha_id (".$actual['nombre']."). Cambios -> ".implode(' | ', $cambios);
            $log_db = addslashes($log);

            $db->query("
                INSERT INTO `mybb_sg_sg_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES
                ('$staff', '$username', '$razon', '$log_db')
            ");

            eval('$reload_script = $reload_js;');
        }
    }
}

/* Render */
if ($es_staff) {
    $ficha = null;
    $experiencia = 0;

    if ($user_fid) {
        $query_ficha = $db->query("SELECT * FROM mybb_sg_sg_fichas WHERE fid='$user_fid'");
        while ($f = $db->fetch_array($query_ficha)) {
            $ficha = $f;
        }

        // Los contadores del Dojo viven dentro del JSON arboles_progreso: los
        // exponemos como claves planas para poder pintarlos/editarlos en el form.
        if ($ficha) {
            $prog = sg_progreso_parse(isset($ficha['arboles_progreso']) ? $ficha['arboles_progreso'] : '');
            foreach ($prog as $pk => $pv) { $ficha[$pk] = (int) $pv; }
        }

        // La experiencia (PR) vive en mybb_sg_users.newpoints
        $query_exp = $db->query("SELECT newpoints FROM mybb_sg_users WHERE uid='$user_fid'");
        while ($u = $db->fetch_array($query_exp)) {
            $experiencia = $u['newpoints'];
        }
    }

    eval('$fid = $user_fid;');
    eval("\$page = \"".$templates->get("staff_modificar_ficha")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
