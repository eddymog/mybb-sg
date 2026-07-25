<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 * Recompensas de misión: el staff carga un TID, asigna rol (participante /
 * narrador / autor) y % a cada persona, y confirma. El servidor SIEMPRE
 * recalcula los montos desde sg_recompensa_mision() — nunca confía en
 * cantidades enviadas por el cliente. Auditoría en la tabla existente
 * mybb_sg_sg_audit_consola_mod. Ver docs/recompensas_instrucciones.txt.
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'recompensas_mision.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/sg_functions.php";

global $templates, $mybb, $db;
$uid      = $mybb->user['uid'];
$username = $mybb->user['username'];
$es_staff = (is_mod($uid) || is_staff($uid));

$PCTS_VALIDOS = array(100, 80, 60, 40, 20, 0);
$RAZON_PREFIX = '[Recompensa de Misión]';
$RAZON_PREFIX_COMBATE = '[Recompensa de Combate]';

if (!$es_staff) {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
    exit;
}

$is_ajax = ($mybb->get_input('ajax') == '1');
$accion  = $mybb->get_input('accion');

function rm_ficha_row($fid) {
    $ficha = select_one_query_with_id('mybb_sg_sg_fichas', 'fid', $fid);
    if (!$ficha) { return null; }
    return array('fid' => intval($fid), 'nombre' => $ficha['nombre'], 'nivel' => intval($ficha['nivel']));
}

// Marca de doble-pago: busca en el log una tanda previa para este TID.
function rm_doble_pago($db, $razon_prefix, $tid) {
    $tid = intval($tid);
    $like = $db->escape_string("%{$razon_prefix} TID:{$tid}|%");
    $q = $db->query("
        SELECT tiempo, staff FROM `mybb_sg_sg_audit_consola_mod`
        WHERE razon LIKE '$like'
        ORDER BY id DESC LIMIT 1
    ");
    $row = null;
    while ($r = $db->fetch_array($q)) { $row = $r; }
    return $row;
}

// ── Endpoint AJAX ──────────────────────────────────────────────────────────
if ($is_ajax) {
    header('Content-Type: application/json; charset=utf-8');
    $resp = array('ok' => false, 'mensaje' => '');

    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $resp['mensaje'] = 'Sesión caducada. Recarga la página e inténtalo de nuevo.';
        echo json_encode($resp); exit;
    }

    $tipos = sg_mision_tipos();

    // ── Cargar TID: tema + participantes + aviso de doble-pago ────────────
    if ($accion === 'cargar_tid') {
        $tid  = intval($mybb->get_input('tid'));
        $tipo = (string) $mybb->get_input('tipo');

        if ($tid <= 0) {
            $resp['mensaje'] = 'TID inválido.';
        } else if (!isset($tipos[$tipo])) {
            $resp['mensaje'] = 'Tipo de misión inválido.';
        } else {
            $tema = select_one_query_with_id('mybb_sg_threads', 'tid', $tid);
            if (!$tema) {
                $resp['mensaje'] = "No existe ningún tema con TID $tid.";
            } else {
                $starter_uid = intval($tema['uid']);

                $participantes = array();
                $q = $db->query("
                    SELECT p.uid, u.username, COUNT(*) AS posts
                    FROM mybb_sg_posts p
                    INNER JOIN mybb_sg_users u ON u.uid = p.uid
                    WHERE p.tid = '$tid' AND p.visible = 1
                    GROUP BY p.uid, u.username
                    ORDER BY posts DESC
                ");
                while ($r = $db->fetch_array($q)) {
                    $pfid  = intval($r['uid']);
                    $ficha = rm_ficha_row($pfid);
                    $participantes[] = array(
                        'uid'        => $pfid,
                        'username'   => $r['username'],
                        'nombre'     => $ficha ? $ficha['nombre'] : null,
                        'nivel'      => $ficha ? $ficha['nivel'] : null,
                        'tiene_ficha'=> $ficha !== null,
                        'posts'      => intval($r['posts']),
                        'es_creador' => ($pfid === $starter_uid),
                    );
                }

                $razon_check = ($tipo === 'combate') ? $RAZON_PREFIX_COMBATE : $RAZON_PREFIX;
                $previo = rm_doble_pago($db, $razon_check, $tid);

                $resp['ok']            = true;
                $resp['tid']           = $tid;
                $resp['subject']       = $tema['subject'];
                $resp['starter_uid']   = $starter_uid;
                $resp['participantes'] = $participantes;
                $resp['ya_recompensado'] = $previo ? array('tiempo' => $previo['tiempo'], 'staff' => $previo['staff']) : null;
            }
        }
        echo json_encode($resp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // ── Buscar UID suelto (para "agregar por UID") ─────────────────────────
    if ($accion === 'buscar_uid') {
        $fid = intval($mybb->get_input('uid'));
        if ($fid <= 0) {
            $resp['mensaje'] = 'UID inválido.';
        } else {
            $usuario = select_one_query_with_id('mybb_sg_users', 'uid', $fid);
            $ficha   = rm_ficha_row($fid);
            if (!$usuario) {
                $resp['mensaje'] = "No existe el usuario UID $fid.";
            } else {
                $resp['ok']          = true;
                $resp['uid']         = $fid;
                $resp['username']    = $usuario['username'];
                $resp['nombre']      = $ficha ? $ficha['nombre'] : null;
                $resp['nivel']       = $ficha ? $ficha['nivel'] : null;
                $resp['tiene_ficha']  = $ficha !== null;
            }
        }
        echo json_encode($resp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // ── Confirmar y aplicar ─────────────────────────────────────────────────
    if ($accion === 'confirmar') {
        $tid   = intval($mybb->get_input('tid'));
        $tipo  = (string) $mybb->get_input('tipo');
        $rango = strtoupper(trim((string) $mybb->get_input('rango')));
        $publicar_post = ($mybb->get_input('publicar_post') == '1');
        // JSON crudo: [{uid,rol,oficial,pct,pergamino}, ...]. Se lee de $_POST
        // directo (no get_input) para no arriesgar que se altere la cadena JSON.
        $filas_raw = isset($_POST['filas']) ? (string) $_POST['filas'] : '';

        $filas = json_decode($filas_raw, true);

        if ($tid <= 0) {
            $resp['mensaje'] = 'TID inválido.';
        } else if (!isset($tipos[$tipo])) {
            $resp['mensaje'] = 'Tipo de misión inválido.';
        } else if ($tipo !== 'combate' && !in_array($rango, $tipos[$tipo]['rangos'], true)) {
            $resp['mensaje'] = 'Rango inválido para este tipo de misión.';
        } else if (!is_array($filas) || empty($filas)) {
            $resp['mensaje'] = 'No hay ningún recipiente para recompensar.';
        } else if ($tipo === 'combate') {
            // ── Combate (1 vs 1): resultado, sin rango/rol/%/pergamino ──────
            $activas = array();
            foreach ($filas as $fila) {
                $fuid = isset($fila['uid']) ? intval($fila['uid']) : 0;
                $resultado = isset($fila['resultado']) ? (string) $fila['resultado'] : '';
                if ($fuid > 0 && in_array($resultado, array('ganador', 'perdedor', 'empate'), true)) {
                    $activas[] = array('uid' => $fuid, 'resultado' => $resultado);
                }
            }

            if (count($activas) !== 2) {
                $resp['mensaje'] = 'Un combate debe tener exactamente 2 combatientes (ganador+perdedor, o ambos en empate).';
            } else if ($activas[0]['uid'] === $activas[1]['uid']) {
                $resp['mensaje'] = 'Los dos combatientes no pueden ser el mismo usuario.';
            } else {
                $r0 = $activas[0]['resultado']; $r1 = $activas[1]['resultado'];
                $par_ok = ($r0 === 'empate' && $r1 === 'empate')
                       || ($r0 === 'ganador' && $r1 === 'perdedor')
                       || ($r0 === 'perdedor' && $r1 === 'ganador');

                if (!$par_ok) {
                    $resp['mensaje'] = 'Los resultados no cuadran: debe ser un Ganador y un Perdedor, o ambos en Empate.';
                } else {
                    $lock_name = "sg_recompensa_mision_tid_" . $tid;
                    $got_lock = 0;
                    $rl = $db->query("SELECT GET_LOCK('$lock_name', 5) AS l");
                    while ($r = $db->fetch_array($rl)) { $got_lock = intval($r['l']); }

                    if ($got_lock !== 1) {
                        $resp['mensaje'] = 'Otro miembro del staff está procesando este mismo TID. Intenta de nuevo en unos segundos.';
                        echo json_encode($resp); exit;
                    }

                    $ficha_a = select_one_query_with_id('mybb_sg_sg_fichas', 'fid', $activas[0]['uid']);
                    $ficha_b = select_one_query_with_id('mybb_sg_sg_fichas', 'fid', $activas[1]['uid']);

                    if (!$ficha_a || !$ficha_b) {
                        $resp['mensaje'] = 'Uno de los dos combatientes no tiene ficha.';
                        $db->query("SELECT RELEASE_LOCK('$lock_name')");
                    } else {
                        $nivel_a = intval($ficha_a['nivel']);
                        $nivel_b = intval($ficha_b['nivel']);
                        $pares = array(
                            array('uid' => $activas[0]['uid'], 'ficha' => $ficha_a, 'resultado' => $activas[0]['resultado'], 'nivel_propio' => $nivel_a, 'nivel_oponente' => $nivel_b),
                            array('uid' => $activas[1]['uid'], 'ficha' => $ficha_b, 'resultado' => $activas[1]['resultado'], 'nivel_propio' => $nivel_b, 'nivel_oponente' => $nivel_a),
                        );

                        $detalle_log = array();
                        $aplicados   = array();
                        $total_tobi = 0; $total_exp = 0;

                        foreach ($pares as $p) {
                            $base = sg_recompensa_combate($p['resultado'], $p['nivel_propio'], $p['nivel_oponente']);
                            $usuario = select_one_query_with_id('mybb_sg_users', 'uid', $p['uid']);

                            $tobi_old = intval($p['ficha']['tobi']);
                            $tobi_new = $tobi_old + $base['tobi'];
                            $exp_old  = $usuario ? floatval($usuario['newpoints']) : 0;
                            $exp_new  = $exp_old + $base['exp'];

                            $grupo = uniqid();
                            sg_ficha_set_campo($p['uid'], 'tobi', $tobi_new, 'staff', SG_ORIGEN_RECOMPENSA_MISION, 'Recompensa combate 1v1', $grupo);
                            sg_usuario_set_campo($p['uid'], 'newpoints', $exp_new, 'staff', SG_ORIGEN_RECOMPENSA_MISION, 'Recompensa combate 1v1', $grupo);

                            // Historial de combates (metadata de referencia para el
                            // contador Combate 1v1 en la pestaña "Estadísticas" de la ficha).
                            sg_historial_combate_registrar($tid, $p['uid'], 'combate_1v1', $p['resultado'], $p['nivel_propio'], $p['nivel_oponente']);

                            $nombre = $p['ficha']['nombre'];
                            $detalle_log[] = "UID {$p['uid']} ($nombre) [{$p['resultado']}, nivel {$p['nivel_propio']} vs {$p['nivel_oponente']}]: "
                                           . "tobi $tobi_old->$tobi_new (+{$base['tobi']}), exp $exp_old->$exp_new (+{$base['exp']}).";
                            $aplicados[] = array(
                                'uid' => $p['uid'], 'nombre' => $nombre, 'rol' => $p['resultado'], 'oficial' => false, 'pct' => 100,
                                'ryos' => 0, 'tobi' => $base['tobi'], 'exp' => $base['exp'], 'pergamino' => null,
                            );
                            $total_tobi += $base['tobi']; $total_exp += $base['exp'];
                        }

                        $razon = "$RAZON_PREFIX_COMBATE TID:{$tid}|";
                        $razon_db = $db->escape_string($razon);
                        $log = "Recompensa de combate — TID $tid.\n"
                             . "Totales: $total_tobi tobi, $total_exp exp.\n"
                             . "- " . implode("\n- ", $detalle_log);
                        $log_db  = $db->escape_string($log);
                        $user_db = $db->escape_string($username);
                        $db->query("
                            INSERT INTO `mybb_sg_sg_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES
                            ('$user_db', '$user_db', '$razon_db', '$log_db')
                        ");

                        $post_resultado = null;
                        if ($publicar_post) {
                            $tema = select_one_query_with_id('mybb_sg_threads', 'tid', $tid);
                            if ($tema) {
                                $mensaje_bbcode = sg_post_combate_bbcode($tid, $tema['subject'], $aplicados);
                                $post_resultado = sg_publicar_post_recompensas($tid, $tema['fid'], $mensaje_bbcode);
                            } else {
                                $post_resultado = array('ok' => false, 'pid' => null, 'error' => 'No se encontró el tema para publicar el post.');
                            }
                        }

                        $db->query("SELECT RELEASE_LOCK('$lock_name')");

                        $resp['ok']        = true;
                        $resp['aplicados'] = $aplicados;
                        $resp['omitidos']  = array();
                        $resp['totales']   = array('ryos' => 0, 'tobi' => $total_tobi, 'exp' => $total_exp, 'pergaminos' => 0);
                        $resp['mensaje']   = 'Recompensa de combate aplicada.';
                        if ($post_resultado !== null) {
                            $resp['post_publicado'] = $post_resultado['ok'];
                            if (!$post_resultado['ok']) { $resp['post_error'] = $post_resultado['error']; }
                        }
                    }
                }
            }
        } else {

            $lock_name = "sg_recompensa_mision_tid_" . $tid;
            $got_lock = 0;
            $rl = $db->query("SELECT GET_LOCK('$lock_name', 5) AS l");
            while ($r = $db->fetch_array($rl)) { $got_lock = intval($r['l']); }

            if ($got_lock !== 1) {
                $resp['mensaje'] = 'Otro miembro del staff está procesando este mismo TID. Intenta de nuevo en unos segundos.';
                echo json_encode($resp); exit;
            }

            $detalle_log  = array();
            $aplicados    = array();
            $omitidos     = array();
            $vistos_uid   = array();
            $total_ryos = 0; $total_tobi = 0; $total_exp = 0; $total_perg = 0;

            foreach ($filas as $fila) {
                $fuid     = isset($fila['uid']) ? intval($fila['uid']) : 0;
                $rol      = isset($fila['rol']) ? (string) $fila['rol'] : '';
                $oficial  = !empty($fila['oficial']);
                $pct      = isset($fila['pct']) ? intval($fila['pct']) : 100;
                $quiere_perg = !empty($fila['pergamino']);

                if ($fuid <= 0 || $rol === 'excluir' || $rol === '') { continue; }

                if (isset($vistos_uid[$fuid])) {
                    $omitidos[] = "UID $fuid: duplicado en la tanda (solo se aplicó la primera aparición).";
                    continue;
                }
                $vistos_uid[$fuid] = true;

                if (!in_array($pct, $PCTS_VALIDOS, true)) {
                    $omitidos[] = "UID $fuid: porcentaje inválido ($pct).";
                    continue;
                }

                $ficha = select_one_query_with_id('mybb_sg_sg_fichas', 'fid', $fuid);
                if (!$ficha) {
                    $omitidos[] = "UID $fuid: sin ficha, se omite.";
                    continue;
                }

                $base = sg_recompensa_mision($rol, $tipo, $rango, $oficial);
                if ($base === false) {
                    $omitidos[] = "UID $fuid: rol/tipo/rango no forman una combinación válida.";
                    continue;
                }

                $usuario = select_one_query_with_id('mybb_sg_users', 'uid', $fuid);

                $ryos_add = (int) round($base['ryos'] * $pct / 100);
                $tobi_add = (int) round($base['tobi'] * $pct / 100);
                $exp_add  = (int) round($base['exp']  * $pct / 100);

                $ryos_old = intval($ficha['ryos']);
                $tobi_old = intval($ficha['tobi']);
                $exp_old  = $usuario ? floatval($usuario['newpoints']) : 0;

                $ryos_new = $ryos_old + $ryos_add;
                $tobi_new = $tobi_old + $tobi_add;
                $exp_new  = $exp_old + $exp_add;

                $grupo = uniqid();
                if ($ryos_add > 0) {
                    sg_ficha_set_campo($fuid, 'ryos', $ryos_new, 'staff', SG_ORIGEN_RECOMPENSA_MISION, 'Recompensa de misión', $grupo);
                }
                if ($tobi_add > 0) {
                    sg_ficha_set_campo($fuid, 'tobi', $tobi_new, 'staff', SG_ORIGEN_RECOMPENSA_MISION, 'Recompensa de misión', $grupo);
                }
                if ($exp_add > 0) {
                    sg_usuario_set_campo($fuid, 'newpoints', $exp_new, 'staff', SG_ORIGEN_RECOMPENSA_MISION, 'Recompensa de misión', $grupo);
                }

                $perg_txt = '';
                $dio_pergamino = false;
                if ($rol === 'narrador' && $quiere_perg && $base['pergamino']) {
                    sg_inventario_dar_objeto($fuid, $base['pergamino'], 'staff', SG_ORIGEN_RECOMPENSA_MISION, 'Pergamino de misión', $grupo);
                    $perg_txt = ", pergamino {$base['pergamino']}";
                    $dio_pergamino = true;
                }

                // Historial de misiones (metadata de referencia para la pestaña
                // "Estadísticas" de la ficha) — una fila por recipiente aplicado.
                sg_historial_mision_registrar($tid, $fuid, $rol, $tipo, $rango, $oficial);

                $nombre = $ficha['nombre'];
                $rol_txt = $rol === 'narrador' ? ('narrador ' . ($oficial ? 'oficial' : 'no oficial')) : $rol;
                $detalle_log[] = "UID $fuid ($nombre) [$rol_txt, $pct%]: ryos $ryos_old->$ryos_new (+$ryos_add), "
                               . "tobi $tobi_old->$tobi_new (+$tobi_add), exp $exp_old->$exp_new (+$exp_add)$perg_txt.";
                $aplicados[] = array(
                    'uid' => $fuid, 'nombre' => $nombre, 'rol' => $rol, 'oficial' => $oficial, 'pct' => $pct,
                    'ryos' => $ryos_add, 'tobi' => $tobi_add, 'exp' => $exp_add, 'pergamino' => $dio_pergamino ? $base['pergamino'] : null,
                );

                $total_ryos += $ryos_add; $total_tobi += $tobi_add; $total_exp += $exp_add;
                if ($dio_pergamino) { $total_perg++; }
            }

            if (!empty($detalle_log)) {
                $razon = "$RAZON_PREFIX TID:{$tid}|";
                $razon_db = $db->escape_string($razon);
                $log = "Recompensa de misión — tipo '{$tipo}', rango $rango, TID $tid.\n"
                     . "Totales: $total_ryos ryos, $total_tobi tobi, $total_exp exp, $total_perg pergamino(s).\n"
                     . "- " . implode("\n- ", $detalle_log);
                $log_db  = $db->escape_string($log);
                $user_db = $db->escape_string($username);
                $db->query("
                    INSERT INTO `mybb_sg_sg_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES
                    ('$user_db', '$user_db', '$razon_db', '$log_db')
                ");
            }

            // Post de felicitación en BBCode, firmado por el narrador (UID 2).
            // No revierte la recompensa si falla: solo se reporta el error.
            $post_resultado = null;
            if ($publicar_post && !empty($aplicados)) {
                $tema = select_one_query_with_id('mybb_sg_threads', 'tid', $tid);
                if ($tema) {
                    $mensaje_bbcode = sg_post_recompensas_bbcode($tid, $tema['subject'], $tipo, $rango, $aplicados);
                    $post_resultado = sg_publicar_post_recompensas($tid, $tema['fid'], $mensaje_bbcode);
                } else {
                    $post_resultado = array('ok' => false, 'pid' => null, 'error' => 'No se encontró el tema para publicar el post.');
                }
            }

            $db->query("SELECT RELEASE_LOCK('$lock_name')");

            $resp['ok']        = true;
            $resp['aplicados'] = $aplicados;
            $resp['omitidos']  = $omitidos;
            $resp['totales']   = array('ryos' => $total_ryos, 'tobi' => $total_tobi, 'exp' => $total_exp, 'pergaminos' => $total_perg);
            $resp['mensaje']   = empty($aplicados) ? 'No se aplicó ninguna recompensa.' : ('Recompensa aplicada a '.count($aplicados).' recipiente(s).');
            if ($post_resultado !== null) {
                $resp['post_publicado'] = $post_resultado['ok'];
                if (!$post_resultado['ok']) { $resp['post_error'] = $post_resultado['error']; }
            }
        }
        echo json_encode($resp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $resp['mensaje'] = 'Acción desconocida.';
    echo json_encode($resp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ── Render normal (GET): la página trae su propio JS/AJAX para todo el flujo ─
$tipos = sg_mision_tipos();
$tablas = sg_mision_tablas();

eval('$misionPostKey = $mybb->post_code;');
$mision_tipos_json  = json_encode($tipos, JSON_UNESCAPED_UNICODE);
$mision_tablas_json = json_encode($tablas, JSON_UNESCAPED_UNICODE);
$mision_pcts_json   = json_encode($PCTS_VALIDOS);
$mision_pergs_json  = json_encode(sg_mision_pergaminos());
eval('$misionTiposJson = $mision_tipos_json;');
eval('$misionTablasJson = $mision_tablas_json;');
eval('$misionPctsJson = $mision_pcts_json;');
eval('$misionPergsJson = $mision_pergs_json;');

eval("\$page = \"".$templates->get("staff_recompensas_mision")."\";");
output_page($page);
