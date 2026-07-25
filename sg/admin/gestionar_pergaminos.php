<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 * Gestión de premios de gacha por pergamino (crear / modificar / eliminar).
 * El Jackpot es un caso especial de premio (siempre 5 tiradas extra, sin
 * recompensas propias) y se administra en su propia sección, separado de
 * los premios normales. Las recompensas de un premio normal son filas
 * dinámicas (rec_tipo[]/rec_valor[]/rec_objeto[]/rec_cantidad[]): al guardar
 * se reemplazan por completo (más simple que diffear filas individuales).
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'gestionar_pergaminos.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/sg_functions.php";

global $templates, $mybb, $db;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$es_staff = (is_mod($uid) || is_staff($uid));

$TIPOS_MONEDA = array('ryos', 'rin', 'madara', 'tobi');
$JACKPOT_TIRADAS_FIJO = 5;

$pergamino_ids = sg_gacha_pergamino_ids();
$pergamino_id_input = trim($mybb->get_input('pergamino_id'));
if (!in_array($pergamino_id_input, $pergamino_ids, true)) {
    $pergamino_id_input = $pergamino_ids[0];
}
$premio_id_input = intval($mybb->get_input('premio_id'));

// Datos comunes del formulario
$accion_post    = $_POST["accion"];
$pergamino_post = trim($_POST["pergamino_id"]);
if (!in_array($pergamino_post, $pergamino_ids, true)) {
    $pergamino_post = $pergamino_ids[0];
}
$reload_js = "<script>window.location.href = window.location.pathname + '?pergamino_id=' + encodeURIComponent(" . json_encode($pergamino_post) . ");</script>";
$log = null;

// ── Guardar premio normal (crear o modificar) ──────────────────
if ($accion_post === 'Guardar' && in_array($pergamino_post, $pergamino_ids, true) && $es_staff) {
    $premio_id_old = intval($_POST["premio_id_old"]);
    $nombre        = trim($_POST["nombre"]);
    $probabilidad  = ($_POST["probabilidad"] === '' || !isset($_POST["probabilidad"])) ? 0 : (float) $_POST["probabilidad"];
    $activo        = isset($_POST["activo"]) ? intval($_POST["activo"]) : 1;

    if ($nombre !== '') {
        $pergamino_esc = $db->escape_string($pergamino_post);
        $nombre_esc    = $db->escape_string($nombre);

        if ($premio_id_old > 0) {
            $db->query("
                UPDATE `mybb_sg_sg_gacha_premios` SET
                    `nombre`='$nombre_esc', `probabilidad`='$probabilidad', `activo`='$activo'
                WHERE `id`='$premio_id_old' AND `pergamino_id`='$pergamino_esc' AND `es_jackpot`='0';
            ");
            $premio_id_actual = $premio_id_old;
            $log = "Modificar premio de gacha #$premio_id_old ($pergamino_post): $nombre, {$probabilidad}%, activo=$activo";
        } else {
            $db->query("
                INSERT INTO `mybb_sg_sg_gacha_premios` (`pergamino_id`, `nombre`, `probabilidad`, `es_jackpot`, `activo`) VALUES
                ('$pergamino_esc', '$nombre_esc', '$probabilidad', '0', '$activo');
            ");
            $premio_id_actual = $db->insert_id();
            $log = "Nuevo premio de gacha ($pergamino_post): $nombre, {$probabilidad}%, activo=$activo";
        }

        // Reemplaza por completo las recompensas del premio.
        $db->query("DELETE FROM `mybb_sg_sg_gacha_recompensas` WHERE premio_id='$premio_id_actual'");

        $rec_tipos      = isset($_POST['rec_tipo']) && is_array($_POST['rec_tipo']) ? $_POST['rec_tipo'] : array();
        $rec_valores    = isset($_POST['rec_valor']) && is_array($_POST['rec_valor']) ? $_POST['rec_valor'] : array();
        $rec_objetos    = isset($_POST['rec_objeto']) && is_array($_POST['rec_objeto']) ? $_POST['rec_objeto'] : array();
        $rec_cantidades = isset($_POST['rec_cantidad']) && is_array($_POST['rec_cantidad']) ? $_POST['rec_cantidad'] : array();

        $recompensas_log = array();
        $orden = 0;
        foreach ($rec_tipos as $i => $tipo) {
            $tipo = trim($tipo);
            if ($tipo === '') { continue; }
            $orden++;

            $cantidad = isset($rec_cantidades[$i]) && $rec_cantidades[$i] !== '' ? intval($rec_cantidades[$i]) : 1;
            $cantidad = max(1, $cantidad);

            if (in_array($tipo, $TIPOS_MONEDA, true)) {
                $valor = isset($rec_valores[$i]) ? intval($rec_valores[$i]) : 0;
                if ($valor <= 0) { continue; }
                $tipo_esc = $db->escape_string($tipo);
                $db->query("
                    INSERT INTO `mybb_sg_sg_gacha_recompensas` (`premio_id`, `tipo`, `valor`, `objeto_id`, `cantidad`, `orden`) VALUES
                    ('$premio_id_actual', '$tipo_esc', '$valor', NULL, '$cantidad', '$orden');
                ");
                $recompensas_log[] = "$tipo:$valor";
            } else if ($tipo === 'objeto') {
                $objeto_id = isset($rec_objetos[$i]) ? trim($rec_objetos[$i]) : '';
                if ($objeto_id === '') { continue; }
                $objeto_id_esc = $db->escape_string($objeto_id);
                $db->query("
                    INSERT INTO `mybb_sg_sg_gacha_recompensas` (`premio_id`, `tipo`, `valor`, `objeto_id`, `cantidad`, `orden`) VALUES
                    ('$premio_id_actual', 'objeto', NULL, '$objeto_id_esc', '$cantidad', '$orden');
                ");
                $recompensas_log[] = "objeto:$objeto_id x$cantidad";
            }
        }
        $log .= " | recompensas: " . (empty($recompensas_log) ? '(ninguna)' : implode(', ', $recompensas_log));
        $premio_id_input = $premio_id_actual;
    }
}

// ── Eliminar premio normal ──────────────────────────────────────
if ($accion_post === 'Eliminar' && $es_staff) {
    $premio_id_old = intval($_POST["premio_id_old"]);
    if ($premio_id_old > 0) {
        $db->query("DELETE FROM `mybb_sg_sg_gacha_recompensas` WHERE premio_id='$premio_id_old'");
        $db->query("DELETE FROM `mybb_sg_sg_gacha_premios` WHERE id='$premio_id_old' AND es_jackpot='0'");
        $log = "Eliminar premio de gacha #$premio_id_old ($pergamino_post)";
        $premio_id_input = 0;
    }
}

// ── Guardar Jackpot (upsert: un jackpot por pergamino) ──────────
if ($accion_post === 'GuardarJackpot' && in_array($pergamino_post, $pergamino_ids, true) && $es_staff) {
    $pergamino_esc = $db->escape_string($pergamino_post);
    $jackpot_prob = ($_POST["jackpot_probabilidad"] === '' || !isset($_POST["jackpot_probabilidad"])) ? 0 : (float) $_POST["jackpot_probabilidad"];

    $existing_id = 0;
    $qj = $db->query("SELECT id FROM `mybb_sg_sg_gacha_premios` WHERE pergamino_id='$pergamino_esc' AND es_jackpot='1' ORDER BY id LIMIT 1");
    while ($rj = $db->fetch_array($qj)) { $existing_id = (int) $rj['id']; }

    if ($existing_id > 0) {
        $db->query("UPDATE `mybb_sg_sg_gacha_premios` SET probabilidad='$jackpot_prob', activo='1' WHERE id='$existing_id'");
        $log = "Modificar Jackpot de gacha ($pergamino_post): {$jackpot_prob}%";
    } else {
        $db->query("
            INSERT INTO `mybb_sg_sg_gacha_premios` (`pergamino_id`, `nombre`, `probabilidad`, `es_jackpot`, `jackpot_tiradas`, `activo`) VALUES
            ('$pergamino_esc', 'JACKPOT', '$jackpot_prob', '1', '$JACKPOT_TIRADAS_FIJO', '1');
        ");
        $log = "Nuevo Jackpot de gacha ($pergamino_post): {$jackpot_prob}%";
    }
}

// ── Eliminar Jackpot ─────────────────────────────────────────────
if ($accion_post === 'EliminarJackpot' && $es_staff) {
    $jackpot_id_old = intval($_POST["jackpot_id_old"]);
    if ($jackpot_id_old > 0) {
        $db->query("DELETE FROM `mybb_sg_sg_gacha_recompensas` WHERE premio_id='$jackpot_id_old'");
        $db->query("DELETE FROM `mybb_sg_sg_gacha_premios` WHERE id='$jackpot_id_old' AND es_jackpot='1'");
        $log = "Eliminar Jackpot de gacha ($pergamino_post) #$jackpot_id_old";
    }
}

// ── Auditoría + recarga (común a las 4 acciones). El nombre de Staff ya no
// se pide a mano: se usa el username de la sesión. Tampoco se pide razón. ──
if ($log !== null && $es_staff) {
    $username_esc = $db->escape_string($username);
    $log_esc = $db->escape_string($log);
    $db->query("INSERT INTO `mybb_sg_sg_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES ('$username_esc', '$username_esc', '', '$log_esc');");

    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;');
}

if (!in_array($pergamino_id_input, $pergamino_ids, true)) {
    $pergamino_id_input = $pergamino_ids[0];
}

// ── Premio normal a editar (vía GET) — el Jackpot no pasa por acá,
// tiene su propia sección más abajo. ─────────────────────────────
$premio = null;
$recompensas_edit = array();
if ($premio_id_input > 0) {
    $qp = $db->query("SELECT * FROM `mybb_sg_sg_gacha_premios` WHERE id='$premio_id_input'");
    while ($p = $db->fetch_array($qp)) { $premio = $p; }

    if ($premio && (int) $premio['es_jackpot'] === 1) {
        // El Jackpot se edita en su propia sección; ignorar este id acá.
        $premio = null;
        $premio_id_input = 0;
    }

    if ($premio) {
        $qr = $db->query("SELECT tipo, valor, objeto_id, cantidad FROM `mybb_sg_sg_gacha_recompensas` WHERE premio_id='$premio_id_input' ORDER BY orden, id");
        while ($r = $db->fetch_array($qr)) {
            $recompensas_edit[] = array(
                'tipo' => $r['tipo'], 'valor' => $r['valor'], 'objeto_id' => $r['objeto_id'], 'cantidad' => (int) $r['cantidad'],
            );
        }
    }
}

// ── Jackpot actual del pergamino seleccionado (siempre fresco) ──
$jackpot_actual = null;
$pergamino_esc_lookup = $db->escape_string($pergamino_id_input);
$qj = $db->query("SELECT * FROM `mybb_sg_sg_gacha_premios` WHERE pergamino_id='$pergamino_esc_lookup' AND es_jackpot='1' ORDER BY id LIMIT 1");
while ($rj = $db->fetch_array($qj)) { $jackpot_actual = $rj; }

// ── Lista de premios del pergamino seleccionado (incluye inactivos y el
// Jackpot, para que Staff pueda reactivarlos/verlos) ─────────────
$premios_lista = sg_gacha_premios($pergamino_id_input, false);
// El total de probabilidad solo debe contar los que realmente entran al sorteo.
$total_probabilidad = 0;
foreach ($premios_lista as $p) {
    if ($p['activo']) { $total_probabilidad += (float) $p['probabilidad']; }
}

$labels_moneda = array('ryos' => 'Ryos', 'rin' => 'Rin', 'madara' => 'Madara', 'tobi' => 'Tobi');
$premios_html = '';
if (empty($premios_lista)) {
    $premios_html = '<div class="sg-gacha-empty">Este pergamino todavía no tiene premios configurados.</div>';
} else {
    foreach ($premios_lista as $p) {
        $rec_txt = array();
        foreach ($p['recompensas'] as $r) {
            if ($r['tipo'] === 'objeto') {
                $rec_txt[] = htmlspecialchars(($r['cantidad'] > 1 ? $r['cantidad'] . '× ' : '') . $r['objeto_id']);
            } else {
                $rec_txt[] = htmlspecialchars('+' . $r['valor'] . ' ' . (isset($labels_moneda[$r['tipo']]) ? $labels_moneda[$r['tipo']] : $r['tipo']));
            }
        }
        if ($p['es_jackpot']) { $rec_txt[] = htmlspecialchars('×' . intval($p['jackpot_tiradas']) . ' tiradas extra gratis'); }
        $rec_html = empty($rec_txt) ? '<span class="sg-gacha-rec--empty">(sin recompensas)</span>' : implode(', ', $rec_txt);

        $badges = '';
        if ($p['es_jackpot']) { $badges .= '<span class="sg-gacha-badge sg-gacha-badge--jackpot">Jackpot</span>'; }
        if (!$p['activo']) { $badges .= '<span class="sg-gacha-badge sg-gacha-badge--off">Inactivo</span>'; }

        // El Jackpot se edita en su propia sección (más abajo), no acá.
        $edit_link = $p['es_jackpot']
            ? ''
            : ('<a class="sg-gacha-premio__edit" href="?pergamino_id=' . urlencode($pergamino_id_input) . '&premio_id=' . intval($p['id']) . '">Editar</a>');

        $premios_html .= '<div class="sg-gacha-premio' . ($premio_id_input == $p['id'] ? ' is-editing' : '') . '">'
            . '<div class="sg-gacha-premio__head">'
            . '<span class="sg-gacha-premio__prob">' . htmlspecialchars(rtrim(rtrim(number_format($p['probabilidad'], 2, '.', ''), '0'), '.')) . '%</span>'
            . '<span class="sg-gacha-premio__nombre">' . htmlspecialchars($p['nombre']) . '</span>'
            . $badges
            . $edit_link
            . '</div>'
            . '<div class="sg-gacha-premio__rec">' . $rec_html . '</div>'
            . '</div>';
    }
}

$pergamino_opts = '';
foreach ($pergamino_ids as $pid) {
    $sel = ($pid === $pergamino_id_input) ? ' selected' : '';
    $pergamino_opts .= "<option value=\"$pid\"$sel>$pid</option>";
}

eval('$pergaminoOpts = $pergamino_opts;');
eval('$premiosHtml = $premios_html;');
eval('$pergaminoIdSel = $pergamino_id_input;');
eval('$premioId = $premio_id_input;');
eval('$totalProbabilidad = rtrim(rtrim(number_format($total_probabilidad, 2, ".", ""), "0"), ".");');
eval('$recompensasJson = json_encode($recompensas_edit, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);');
eval('$jackpotId = $jackpot_actual ? intval($jackpot_actual["id"]) : 0;');
eval('$jackpotProbabilidad = $jackpot_actual ? rtrim(rtrim(number_format($jackpot_actual["probabilidad"], 2, ".", ""), "0"), ".") : "";');

if ($es_staff) {
    eval("\$page = \"".$templates->get("staff_gestionar_pergaminos")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
