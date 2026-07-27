<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 * Pergaminos: gacha de PERG001..PERG007. Muestra los pergaminos que el
 * usuario tiene en inventario; el sorteo real corre en abrir_pergamino.php.
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'pergaminos.php');

global $templates, $mybb, $db;

require_once "./../global.php";
require_once "./functions/sg_functions.php";

$uid = intval($mybb->user['uid']);
$default_img = '/images/sg/objeto_default.png';

// Grupo habilitado para el botón de regalo global (GID 24, ya sea como
// usergroup principal o dentro de additionalgroups). Se procesa ANTES del
// gate de ficha para que funcione aunque esa cuenta puntual no tenga una
// ficha activa.
$GID_REGALO_GLOBAL = 24;
$puede_regalar_global = sg_usuario_en_grupo($uid, $GID_REGALO_GLOBAL);
$regalo_msg = '';
$regalo_ok = false;

if ($puede_regalar_global && isset($_POST['accion_regalo']) && $_POST['accion_regalo'] === 'regalar_global') {
    $pergamino_regalo = trim($_POST['pergamino_regalo']);
    $staff_regalo = trim($_POST['staff_regalo']);
    $razon_regalo = trim($_POST['razon_regalo']);

    if (in_array($pergamino_regalo, sg_gacha_pergamino_ids(), true) && $staff_regalo !== '' && $razon_regalo !== '') {
        $afectadas = sg_gacha_regalar_global($pergamino_regalo, $uid);

        $log = "Regalo global de pergamino $pergamino_regalo a $afectadas fichas. Razón: $razon_regalo";
        $staff_esc = $db->escape_string($staff_regalo);
        $username_esc = $db->escape_string($mybb->user['username']);
        $razon_esc = $db->escape_string($razon_regalo);
        $log_esc = $db->escape_string($log);
        $db->query("INSERT INTO `mybb_sg_sg_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES ('$staff_esc', '$username_esc', '$razon_esc', '$log_esc');");

        $regalo_ok = true;
        $regalo_msg = "Se regaló $pergamino_regalo a $afectadas ficha(s).";
    } else {
        $regalo_msg = 'Completa el pergamino, tu nombre de Staff y la razón para poder regalar.';
    }
}

if (!does_ficha_exist($uid)) {
    eval("\$page = \"".$templates->get("sg_ficha_no_existe")."\";");
    output_page($page);
    exit;
}

// Se muestran los pergaminos SIEMPRE (aunque el usuario tenga 0), no solo
// los que están en su inventario. La lista ahora es dinámica (en_gacha=1) y
// puede venir vacía si Staff todavía no marcó ninguno; se agrega un sentinel
// que no matchea ningún objeto_id real para que el IN(...) siga siendo SQL
// válido en vez de romper con una lista vacía.
$perg_ids = sg_gacha_pergamino_ids();
$in = array();
foreach ($perg_ids as $pid) { $in[] = "'" . $db->escape_string($pid) . "'"; }
if (empty($in)) { $in[] = "''"; }

$inv = array();
$qinv = $db->query("SELECT objeto_id, cantidad FROM `mybb_sg_sg_inventario` WHERE uid='$uid' AND objeto_id IN (" . implode(',', $in) . ") AND cantidad > 0");
while ($r = $db->fetch_array($qinv)) {
    $inv[$r['objeto_id']] = intval($r['cantidad']);
}

// Cuáles de los 7 pergaminos tienen su probabilidad activa en EXACTAMENTE
// 100% (si no, el botón de abrir queda deshabilitado con aviso "Próximamente"
// — no alcanza con tener premios, tienen que sumar 100% para un sorteo válido).
$configurados = array();
$qc = $db->query("SELECT pergamino_id, SUM(probabilidad) AS total FROM `mybb_sg_sg_gacha_premios` WHERE activo='1' AND pergamino_id IN (" . implode(',', $in) . ") GROUP BY pergamino_id");
while ($r = $db->fetch_array($qc)) {
    if (abs((float) $r['total'] - 100) < 0.01) {
        $configurados[$r['pergamino_id']] = true;
    }
}

// Datos de catálogo (nombre/imagen/descripción) de los 7 pergaminos.
$catalogo = array();
$qo = $db->query("SELECT * FROM `mybb_sg_sg_objetos` WHERE objeto_id IN (" . implode(',', $in) . ")");
while ($o = $db->fetch_array($qo)) {
    $catalogo[$o['objeto_id']] = $o;
}

$pergaminos_html = '';
$premios_paneles_html = '';
$total = 0;
// Orden fijo E→S (PERG001..PERG007).
foreach ($perg_ids as $pid) {
    if (!isset($catalogo[$pid])) { continue; } // fallback defensivo: el objeto no existe en el catálogo
    $total++;

    $o = $catalogo[$pid];
    $nombre = htmlspecialchars($o['nombre'], ENT_QUOTES);
    $img    = trim($o['imagen']) !== '' ? htmlspecialchars($o['imagen'], ENT_QUOTES) : $default_img;
    $cant   = isset($inv[$pid]) ? intval($inv[$pid]) : 0;
    $listo  = isset($configurados[$pid]);
    $pid_esc = htmlspecialchars($pid, ENT_QUOTES);

    if ($cant <= 0) {
        $boton = "<div class=\"perg-soon perg-soon--notienes\">No tienes ninguno</div>";
    } else if ($listo) {
        $boton = "<button class=\"perg-btn\" type=\"button\" data-pid=\"$pid_esc\" data-nombre=\"$nombre\" onclick=\"pergConfirmar(this)\">Abrir Pergamino</button>";
    } else {
        $boton = "<div class=\"perg-soon\">Próximamente</div>";
    }

    // "Ver premios": tabla de probabilidades, solo tiene sentido si el
    // pergamino ya está configurado (probabilidad activa = 100%).
    $ver_premios_btn = '';
    if ($listo) {
        $ver_premios_btn = "<button class=\"perg-btn perg-btn--ghost\" type=\"button\" data-pid=\"$pid_esc\" data-nombre=\"$nombre\" onclick=\"pergVerPremios(this)\">Ver premios</button>";

        $filas_html = '';
        foreach (sg_gacha_premios_tabla($pid) as $fila) {
            $fnombre = htmlspecialchars($fila['nombre'], ENT_QUOTES);
            $fprob   = number_format($fila['probabilidad'], 2, ',', '.') . '%';
            $frecs   = htmlspecialchars(implode(', ', $fila['recompensas']), ENT_QUOTES);
            $fbadge  = $fila['es_jackpot'] ? '<span class="perg-ptable-jackpot">JACKPOT</span>' : '';
            $filas_html .= "<div class=\"perg-ptable-row\">"
                . "<div class=\"perg-ptable-prob\">$fprob</div>"
                . "<div class=\"perg-ptable-main\"><span class=\"perg-ptable-name\">$fnombre</span>$fbadge</div>"
                . "<div class=\"perg-ptable-rewards\">$frecs</div>"
                . "</div>";
        }
        if ($filas_html === '') {
            $filas_html = "<div class=\"perg-ptable-empty\">Sin premios configurados.</div>";
        }
        $premios_paneles_html .= "<div class=\"perg-ptable-data\" data-pid=\"$pid_esc\" style=\"display:none;\">$filas_html</div>";
    }

    $pergaminos_html .= "<article class=\"perg-card\" data-pid=\"$pid_esc\">"
        . "<div class=\"perg-card__thumb\"><img src=\"$img\" alt=\"$nombre\" onerror=\"this.src='$default_img'\"></div>"
        . "<div class=\"perg-card__body\">"
        . "<h2 class=\"perg-card__name\">$nombre</h2>"
        . "<div class=\"perg-card__qty\">Tienes <strong>$cant</strong></div>"
        . $ver_premios_btn
        . $boton
        . "</div>"
        . "</article>";
}

if ($total === 0) {
    $pergaminos_html = "<div class=\"perg-empty\">No hay pergaminos configurados en el catálogo todavía.</div>";
}

// ── Estadísticas (globales + personales) ─────────────────────────
// Dos columnas cada una: izquierda (total + jackpot), derecha (desglose por
// pergamino). Misma forma de armar el HTML para ambos paneles.
function sg_perg_build_stats_html($stats, $perg_ids, $id_prefix) {
    $main_html = "<div class=\"perg-stat\" id=\"$id_prefix-total\"><div class=\"perg-stat__v\">" . number_format($stats['total'], 0, ',', '.') . "</div><div class=\"perg-stat__k\">Pergaminos Abiertos</div></div>"
        . "<div class=\"perg-stat\" id=\"$id_prefix-jackpot\"><div class=\"perg-stat__v\">" . number_format($stats['jackpots'], 0, ',', '.') . "</div><div class=\"perg-stat__k\">¡JACKPOT!</div></div>";

    $perg_html = '';
    foreach ($perg_ids as $pid) {
        $rango = sg_gacha_pergamino_rango($pid);
        $etiqueta = $rango !== null ? "Pergamino $rango ($pid)" : $pid;
        $cnt = isset($stats['por_pergamino'][$pid]) ? $stats['por_pergamino'][$pid] : 0;
        $perg_html .= "<div class=\"perg-stat\" data-pid=\"" . htmlspecialchars($pid, ENT_QUOTES) . "\"><div class=\"perg-stat__v\">" . number_format($cnt, 0, ',', '.') . "</div><div class=\"perg-stat__k\">" . htmlspecialchars($etiqueta) . "</div></div>";
    }

    return array($main_html, $perg_html);
}

$stats = sg_gacha_estadisticas();
list($stats_main_html, $stats_perg_html) = sg_perg_build_stats_html($stats, $perg_ids, 'perg-stat');

$stats_personal = sg_gacha_estadisticas_usuario($uid);
list($stats_personal_main_html, $stats_personal_perg_html) = sg_perg_build_stats_html($stats_personal, $perg_ids, 'perg-stat-mio');

// ── Historial de tiradas (global + personal) ────────────────────
function sg_perg_fila_historial_html($row, $mostrar_usuario) {
    $fecha = htmlspecialchars(substr($row['tiempo'], 0, 16));
    $rango = sg_gacha_pergamino_rango($row['pergamino_id']);
    $perg_label = htmlspecialchars($rango !== null ? "Pergamino $rango ({$row['pergamino_id']})" : $row['pergamino_id']);
    $premio = htmlspecialchars($row['premio_nombre'] !== null ? $row['premio_nombre'] : '(premio eliminado)');
    $jackpot_badge = $row['es_jackpot'] ? '<span class="perg-hist-badge">JACKPOT</span>' : '';

    $usuario_html = '';
    if ($mostrar_usuario) {
        $usuario = $row['username'] !== null ? $row['username'] : ('UID ' . intval($row['uid']));
        $usuario_html = '<span class="perg-hist-user">' . htmlspecialchars($usuario) . '</span>';
    }

    return '<div class="perg-hist-row">'
        . '<span class="perg-hist-date">' . $fecha . '</span>'
        . $usuario_html
        . '<span class="perg-hist-perg">' . $perg_label . '</span>'
        . '<span class="perg-hist-premio">' . $premio . '</span>'
        . $jackpot_badge
        . '</div>';
}

$hist_global = sg_gacha_historial_global(100);
$hist_global_html = '';
foreach ($hist_global as $row) { $hist_global_html .= sg_perg_fila_historial_html($row, true); }
if (empty($hist_global_html)) { $hist_global_html = '<div class="perg-hist-empty">Todavía no se ha abierto ningún pergamino.</div>'; }

$hist_personal = sg_gacha_historial_usuario($uid, 100);
$hist_personal_html = '';
foreach ($hist_personal as $row) { $hist_personal_html .= sg_perg_fila_historial_html($row, false); }
if (empty($hist_personal_html)) { $hist_personal_html = '<div class="perg-hist-empty">Todavía no has abierto ningún pergamino.</div>'; }

// ── Botón de regalo global (solo grupo GID 24) ────────────────────
$regalo_html = '';
if ($puede_regalar_global) {
    $regalo_opts = '';
    foreach ($perg_ids as $pid) {
        $rango = sg_gacha_pergamino_rango($pid);
        $etiqueta = htmlspecialchars($rango !== null ? "Pergamino $rango ($pid)" : $pid);
        $regalo_opts .= "<option value=\"$pid\">$etiqueta</option>";
    }
    $regalo_msg_html = '';
    if ($regalo_msg !== '') {
        $regalo_msg_html = "<div class=\"perg-regalo__msg" . ($regalo_ok ? ' is-ok' : ' is-error') . "\">" . htmlspecialchars($regalo_msg) . "</div>";
    }

    $regalo_html = "<div class=\"perg-regalo\">"
        . "<div class=\"perg-regalo__title\">Regalo Global de Staff</div>"
        . "<p class=\"perg-regalo__desc\">Entrega 1 unidad del pergamino elegido a TODAS las cuentas con ficha activa. Esta acción no se puede deshacer.</p>"
        . $regalo_msg_html
        . "<form method=\"post\" action=\"/sg/pergaminos.php\" id=\"perg-regalo-form\" onsubmit=\"return pergConfirmarRegalo(this);\">"
        . "<input type=\"hidden\" name=\"accion_regalo\" value=\"regalar_global\">"
        . "<div class=\"perg-regalo__row\">"
        . "<select class=\"perg-regalo__select\" name=\"pergamino_regalo\" required>$regalo_opts</select>"
        . "<input class=\"perg-regalo__input\" type=\"text\" name=\"staff_regalo\" placeholder=\"Tu nombre de Staff\" required>"
        . "<input class=\"perg-regalo__input\" type=\"text\" name=\"razon_regalo\" placeholder=\"Razón del regalo\" required>"
        . "<button class=\"perg-regalo__btn\" type=\"submit\">Regalar a todas las fichas</button>"
        . "</div>"
        . "</form>"
        . "</div>";
}

eval('$pergHtml = $pergaminos_html;');
eval('$pergPremiosPanelesHtml = $premios_paneles_html;');
eval('$pergStatsMainHtml = $stats_main_html;');
eval('$pergStatsPergHtml = $stats_perg_html;');
eval('$pergStatsPersonalMainHtml = $stats_personal_main_html;');
eval('$pergStatsPersonalPergHtml = $stats_personal_perg_html;');
eval('$pergHistGlobalHtml = $hist_global_html;');
eval('$pergHistPersonalHtml = $hist_personal_html;');
eval('$pergRegaloHtml = $regalo_html;');
eval("\$page = \"".$templates->get("sg_pergaminos")."\";");
output_page($page);
