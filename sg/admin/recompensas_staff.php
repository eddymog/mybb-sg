<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 * Recompensas al staff:
 *   1) Recompensa de misión (rango C/D) a una lista de IDs — para todo el staff.
 *   2) Cobro de experiencia semanal — solo administradores (usergroup 24).
 *
 * Flujo: enviar el formulario muestra una pantalla de confirmación; solo al
 * confirmar se aplican los cambios. Cada tanda de IDs se registra en UN solo
 * row de mybb_sg_sg_audit_consola_mod.
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'recompensas_staff.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/sg_functions.php";

global $templates, $mybb, $db;
$uid      = $mybb->user['uid'];
$username = $mybb->user['username'];

$es_staff = (is_mod($uid) || is_staff($uid));

// Administrador = usergroup principal 24 o en grupos adicionales
$es_admin = false;
$q_adm = $db->query("
    SELECT uid FROM mybb_sg_users
    WHERE uid='$uid' AND (
        usergroup='24' OR additionalgroups='24'
        OR additionalgroups LIKE '24,%' OR additionalgroups LIKE '%,24'
        OR additionalgroups LIKE '%,24,%'
    )
");
while ($a = $db->fetch_array($q_adm)) { $es_admin = true; }

if (!$es_staff) {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
    exit;
}

// Semana del foro (mismo epoch que newpoints.php / ficha.php)
$sg_exp_epoch   = 1721620800;
$semana_actual  = (int) ceil((time() - $sg_exp_epoch) / 604800);
$semana_display = $semana_actual - 100; // numeración in-rol mostrada

// Presets de recompensa de misión
$presets_mision = array(
    'C' => array('ryos' => 1000, 'tobi' => 10, 'exp' => 90),
    'D' => array('ryos' => 500,  'tobi' => 5,  'exp' => 70),
);

// Razón con la que se etiquetan (y luego se filtran) estos cambios en el audit
$razon_reco = "[Recompensa de Staff]";
$razon_db   = addslashes($razon_reco);

// Extrae UIDs positivos únicos de un texto libre (comas, espacios, saltos de línea…)
function rw_parse_ids($raw) {
    $ids = array();
    foreach (preg_split('/[^0-9]+/', (string) $raw) as $p) {
        $n = intval($p);
        if ($n > 0 && !in_array($n, $ids, true)) { $ids[] = $n; }
    }
    return $ids;
}

// Separa los UIDs en fichas válidas (con nombre) e inválidas (sin ficha)
function rw_resolver($ids) {
    $validos = array();
    $invalidos = array();
    foreach ($ids as $fid) {
        $ficha = select_one_query_with_id('mybb_sg_sg_fichas', 'fid', $fid);
        if ($ficha) {
            $validos[] = array('fid' => $fid, 'nombre' => $ficha['nombre']);
        } else {
            $invalidos[] = $fid;
        }
    }
    return array($validos, $invalidos);
}

$exitos       = array();
$errores      = array();
$confirmacion = '';   // pantalla de confirmación (si aplica)

// Prefill de campos (se conservan tras enviar)
$ids_val    = htmlspecialchars(trim((string) $mybb->get_input('ids')), ENT_QUOTES);
$semana_val = (int) $mybb->get_input('semana');
if ($semana_val <= 0) { $semana_val = $semana_actual; }

$accion    = isset($_POST['accion']) ? $_POST['accion'] : '';
$confirmar = isset($_POST['confirmar']) && $_POST['confirmar'] == '1';

// ── 1) Recompensa de misión (todo el staff) ───────────────────
if ($accion === 'recompensa_mision' && $es_staff) {
    $rango = strtoupper(trim((string) $_POST['rango']));
    $ids   = rw_parse_ids($_POST['ids']);

    if (!isset($presets_mision[$rango])) {
        $errores[] = "Rango inválido.";
    } else if (empty($ids)) {
        $errores[] = "No se detectó ningún ID válido.";
    } else {
        $p = $presets_mision[$rango];
        list($validos, $invalidos) = rw_resolver($ids);
        $premio_txt = number_format($p['ryos'], 0, ',', '.')." ryos · {$p['tobi']} tobi · {$p['exp']} exp";

        if (empty($validos)) {
            $errores[] = "Ninguno de los IDs indicados tiene ficha.";
        } else if (!$confirmar) {
            // ── Pantalla de confirmación ──
            $lista = '';
            foreach ($validos as $v) {
                $lista .= "<li>".htmlspecialchars($v['nombre'], ENT_QUOTES)." <span class=\"rw-confirm__uid\">(UID {$v['fid']})</span></li>";
            }
            $aviso = '';
            if (!empty($invalidos)) {
                $aviso = "<p class=\"rw-confirm__warn\">Sin ficha (se omitirán): ".htmlspecialchars(implode(', ', $invalidos), ENT_QUOTES)."</p>";
            }
            $ids_hidden = htmlspecialchars(implode(',', $ids), ENT_QUOTES);
            $confirmacion = "
<section class=\"rw-card rw-confirm\">
    <div class=\"rw-card__head\">
        <div class=\"rw-eyebrow\">Confirmar</div>
        <h2 class=\"rw-title\">¿Dar recompensa de rango $rango?</h2>
        <p class=\"rw-desc\">Se sumará <strong>$premio_txt</strong> a cada una de las siguientes fichas:</p>
    </div>
    <div class=\"rw-confirm__body\">
        <ul class=\"rw-confirm__list\">$lista</ul>
        $aviso
        <div class=\"rw-confirm__actions\">
            <form method=\"post\" action=\"/sg/admin/recompensas_staff.php\">
                <input type=\"hidden\" name=\"accion\" value=\"recompensa_mision\">
                <input type=\"hidden\" name=\"rango\" value=\"$rango\">
                <input type=\"hidden\" name=\"ids\" value=\"$ids_hidden\">
                <input type=\"hidden\" name=\"confirmar\" value=\"1\">
                <button class=\"rw-btn\" type=\"submit\">Sí, aplicar</button>
            </form>
            <a class=\"rw-cancel\" href=\"/sg/admin/recompensas_staff.php\">Cancelar</a>
        </div>
    </div>
</section>";
        } else {
            // ── Aplicar ──
            $detalle = array();
            foreach ($validos as $v) {
                $fid    = $v['fid'];
                $ficha  = select_one_query_with_id('mybb_sg_sg_fichas', 'fid', $fid);
                $usuario = select_one_query_with_id('mybb_sg_users', 'uid', $fid);
                if (!$ficha) { continue; }

                $nombre   = $ficha['nombre'];
                $ryos_old = intval($ficha['ryos']);
                $tobi_old = intval($ficha['tobi']);
                $exp_old  = $usuario ? floatval($usuario['newpoints']) : 0;

                $ryos_new = $ryos_old + $p['ryos'];
                $tobi_new = $tobi_old + $p['tobi'];
                $exp_new  = $exp_old + $p['exp'];

                $db->query("UPDATE `mybb_sg_sg_fichas` SET `ryos`='$ryos_new', `tobi`='$tobi_new' WHERE `fid`='$fid'");
                $db->query("UPDATE `mybb_sg_users` SET `newpoints`='$exp_new' WHERE `uid`='$fid'");

                $detalle[] = "UID $fid ($nombre): ryos $ryos_old->$ryos_new (+{$p['ryos']}), "
                           . "tobi $tobi_old->$tobi_new (+{$p['tobi']}), "
                           . "exp $exp_old->$exp_new (+{$p['exp']}).";
                $exitos[] = "UID $fid ($nombre): +{$p['ryos']} ryos, +{$p['tobi']} tobi, +{$p['exp']} exp.";
            }

            if (!empty($detalle)) {
                $log = "Recompensa misión rango $rango a ".count($detalle)." ficha(s):\n- ".implode("\n- ", $detalle);
                $log_db  = addslashes($log);
                $user_db = addslashes($username);
                $db->query("
                    INSERT INTO `mybb_sg_sg_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES
                    ('$user_db', '$user_db', '$razon_db', '$log_db')
                ");

                // Redirige (Post/Redirect/Get) para que refrescar no repita la recompensa
                $msg = "Recompensa de rango $rango aplicada a ".count($detalle)." ficha(s).";
                if (!empty($invalidos)) { $msg .= " Omitidas (sin ficha): ".implode(', ', $invalidos)."."; }
                redirect($mybb->settings['bburl']."/sg/admin/recompensas_staff.php", $msg, "Recompensas aplicadas");
                exit;
            } else {
                $errores[] = "No se pudo aplicar la recompensa (fichas no encontradas).";
            }
        }
    }
}

// ── 2) Cobro de experiencia semanal (solo admins) ─────────────
if ($accion === 'recompensa_semanal') {
    if (!$es_admin) {
        $errores[] = "Solo un administrador puede usar el cobro de experiencia semanal.";
    } else {
        $semana = (int) $_POST['semana'];
        if ($semana <= 0) { $semana = $semana_actual; }
        $ids = rw_parse_ids($_POST['ids']);

        if (empty($ids)) {
            $errores[] = "No se detectó ningún ID válido.";
        } else {
            list($validos, $invalidos) = rw_resolver($ids);

            if (empty($validos)) {
                $errores[] = "Ninguno de los IDs indicados tiene ficha.";
            } else if (!$confirmar) {
                // ── Pantalla de confirmación ──
                $lista = '';
                foreach ($validos as $v) {
                    $lista .= "<li>".htmlspecialchars($v['nombre'], ENT_QUOTES)." <span class=\"rw-confirm__uid\">(UID {$v['fid']})</span></li>";
                }
                $aviso = '';
                if (!empty($invalidos)) {
                    $aviso = "<p class=\"rw-confirm__warn\">Sin ficha (se omitirán): ".htmlspecialchars(implode(', ', $invalidos), ENT_QUOTES)."</p>";
                }
                $ids_hidden = htmlspecialchars(implode(',', $ids), ENT_QUOTES);
                $confirmacion = "
<section class=\"rw-card rw-card--admin rw-confirm\">
    <div class=\"rw-card__head\">
        <div class=\"rw-eyebrow rw-eyebrow--admin\">Confirmar</div>
        <h2 class=\"rw-title\">¿Cobrar la semana $semana?</h2>
        <p class=\"rw-desc\">Se saldará la experiencia semanal (tope 100) de cada ficha: la parte restante se dará como experiencia y lo ya acumulado se convierte en rins. Fichas:</p>
    </div>
    <div class=\"rw-confirm__body\">
        <ul class=\"rw-confirm__list\">$lista</ul>
        $aviso
        <div class=\"rw-confirm__actions\">
            <form method=\"post\" action=\"/sg/admin/recompensas_staff.php\">
                <input type=\"hidden\" name=\"accion\" value=\"recompensa_semanal\">
                <input type=\"hidden\" name=\"semana\" value=\"$semana\">
                <input type=\"hidden\" name=\"ids\" value=\"$ids_hidden\">
                <input type=\"hidden\" name=\"confirmar\" value=\"1\">
                <button class=\"rw-btn rw-btn--admin\" type=\"submit\">Sí, cobrar</button>
            </form>
            <a class=\"rw-cancel\" href=\"/sg/admin/recompensas_staff.php\">Cancelar</a>
        </div>
    </div>
</section>";
            } else {
                // ── Aplicar ──
                $detalle = array();
                foreach ($validos as $v) {
                    $fid     = $v['fid'];
                    $ficha   = select_one_query_with_id('mybb_sg_sg_fichas', 'fid', $fid);
                    $usuario = select_one_query_with_id('mybb_sg_users', 'uid', $fid);
                    if (!$ficha) { continue; }
                    $nombre = $ficha['nombre'];

                    // Experiencia semanal actual (0..100) de la semana indicada
                    $existe = false;
                    $S = 0;
                    $q_lim = $db->query("SELECT experiencia_semanal FROM `mybb_sg_sg_experiencia_limite` WHERE uid='$fid' AND semana='$semana'");
                    while ($l = $db->fetch_array($q_lim)) {
                        $existe = true;
                        $S = (int) floor(floatval($l['experiencia_semanal']));
                    }
                    if ($S < 0)   { $S = 0; }
                    if ($S > 100) { $S = 100; }

                    $exp_gan = 100 - $S;   // diferencia como experiencia
                    $rin_gan = $S;         // lo acumulado se convierte en rins

                    // Experiencia (newpoints)
                    $exp_old = $usuario ? floatval($usuario['newpoints']) : 0;
                    $exp_new = $exp_old + $exp_gan;
                    if ($exp_gan > 0) {
                        $db->query("UPDATE `mybb_sg_users` SET `newpoints`='$exp_new' WHERE `uid`='$fid'");
                    }

                    // Rins
                    $rin_old = intval($ficha['rin']);
                    $rin_new = $rin_old + $rin_gan;
                    if ($rin_gan > 0) {
                        $db->query("UPDATE `mybb_sg_sg_fichas` SET `rin`='$rin_new' WHERE `fid`='$fid'");
                    }

                    // La semana queda saldada (tope 100)
                    if ($existe) {
                        $db->query("UPDATE `mybb_sg_sg_experiencia_limite` SET `experiencia_semanal`='100' WHERE uid='$fid' AND semana='$semana'");
                    } else {
                        $db->query("INSERT INTO `mybb_sg_sg_experiencia_limite` (`uid`, `semana`, `experiencia_semanal`) VALUES ('$fid', '$semana', '100')");
                    }

                    $detalle[] = "UID $fid ($nombre): exp $exp_old->$exp_new (+$exp_gan), "
                               . "rin $rin_old->$rin_new (+$rin_gan). Exp. semanal $S -> 100.";
                    $exitos[] = "UID $fid ($nombre): +$exp_gan exp, +$rin_gan rin (semana $semana).";
                }

                if (!empty($detalle)) {
                    $log = "Cobro semanal (diferencia) semana $semana a ".count($detalle)." ficha(s):\n- ".implode("\n- ", $detalle);
                    $log_db  = addslashes($log);
                    $user_db = addslashes($username);
                    $db->query("
                        INSERT INTO `mybb_sg_sg_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES
                        ('$user_db', '$user_db', '$razon_db', '$log_db')
                    ");

                    // Redirige (Post/Redirect/Get) para que refrescar no repita el cobro
                    $msg = "Cobro semanal (semana $semana) aplicado a ".count($detalle)." ficha(s).";
                    if (!empty($invalidos)) { $msg .= " Omitidas (sin ficha): ".implode(', ', $invalidos)."."; }
                    redirect($mybb->settings['bburl']."/sg/admin/recompensas_staff.php", $msg, "Cobro aplicado");
                    exit;
                } else {
                    $errores[] = "No se pudo aplicar el cobro (fichas no encontradas).";
                }
            }
        }
    }
}

// ── Bloque de resultados ──────────────────────────────────────
$resultado = '';
if (!empty($exitos) || !empty($errores)) {
    $resultado .= "<section class=\"rw-result\">";
    if (!empty($exitos)) {
        $resultado .= "<div class=\"rw-result__ok\"><div class=\"rw-result__title\">Aplicado (".count($exitos).")</div><ul>";
        foreach ($exitos as $e) {
            $resultado .= "<li>".htmlspecialchars($e, ENT_QUOTES)."</li>";
        }
        $resultado .= "</ul></div>";
    }
    if (!empty($errores)) {
        $resultado .= "<div class=\"rw-result__err\"><div class=\"rw-result__title\">Avisos (".count($errores).")</div><ul>";
        foreach ($errores as $e) {
            $resultado .= "<li>".htmlspecialchars($e, ENT_QUOTES)."</li>";
        }
        $resultado .= "</ul></div>";
    }
    $resultado .= "</section>";
}

// ── Formularios (se ocultan mientras se muestra la confirmación) ─
$form_misiones = '';
$form_semanal  = '';
if ($confirmacion === '') {
    $form_misiones = "
<section class=\"rw-card\">
    <div class=\"rw-card__head\">
        <div class=\"rw-eyebrow\">Staff</div>
        <h2 class=\"rw-title\">Recompensa de misión</h2>
        <p class=\"rw-desc\">Pega los IDs de usuario separados por coma, espacio o salto de línea (ej: <code>129, 178 240</code>) y elige el rango. Se suma ryos, tobi y experiencia a cada ficha.</p>
    </div>
    <form method=\"post\" action=\"/sg/admin/recompensas_staff.php\" class=\"rw-form\">
        <input type=\"hidden\" name=\"accion\" value=\"recompensa_mision\">
        <label class=\"rw-label\">IDs de usuario</label>
        <textarea class=\"rw-textarea\" name=\"ids\" rows=\"4\" placeholder=\"Ej: 129, 178 240&#10;331\">$ids_val</textarea>
        <div class=\"rw-options\">
            <label class=\"rw-radio\"><input type=\"radio\" name=\"rango\" value=\"C\" checked><span><strong>Rango C</strong><em>1.000 ryos · 10 tobi · 90 exp</em></span></label>
            <label class=\"rw-radio\"><input type=\"radio\" name=\"rango\" value=\"D\"><span><strong>Rango D</strong><em>500 ryos · 5 tobi · 70 exp</em></span></label>
        </div>
        <button class=\"rw-btn\" type=\"submit\">Continuar</button>
    </form>
</section>";

    if ($es_admin) {
        $form_semanal = "
<section class=\"rw-card rw-card--admin\">
    <div class=\"rw-card__head\">
        <div class=\"rw-eyebrow rw-eyebrow--admin\">Administración</div>
        <h2 class=\"rw-title\">Cobro de experiencia semanal</h2>
        <p class=\"rw-desc\">Salda la experiencia semanal (tope 100) de la semana indicada: da la diferencia como experiencia y convierte lo acumulado en rins. IDs separados por coma, espacio o salto de línea. Semana actual: <strong>$semana_actual</strong> (in-rol $semana_display).</p>
    </div>
    <form method=\"post\" action=\"/sg/admin/recompensas_staff.php\" class=\"rw-form\">
        <input type=\"hidden\" name=\"accion\" value=\"recompensa_semanal\">
        <label class=\"rw-label\">IDs de usuario</label>
        <textarea class=\"rw-textarea\" name=\"ids\" rows=\"4\" placeholder=\"Ej: 129, 178 240&#10;331\">$ids_val</textarea>
        <div class=\"rw-row\">
            <div class=\"rw-field rw-field--sm\"><label class=\"rw-label\">Semana</label><input class=\"rw-input\" type=\"number\" name=\"semana\" min=\"1\" value=\"$semana_val\"></div>
        </div>
        <button class=\"rw-btn rw-btn--admin\" type=\"submit\">Continuar</button>
    </form>
</section>";
    }
}

// ── Log: últimos 20 cambios (solo recompensas de staff, oculto por defecto) ─
$filas = '';
$q_log = $db->query("
    SELECT * FROM `mybb_sg_sg_audit_consola_mod`
    WHERE razon='$razon_db'
    ORDER BY id DESC
    LIMIT 20
");
while ($l = $db->fetch_array($q_log)) {
    $id     = intval($l['id']);
    $tiempo = htmlspecialchars($l['tiempo'], ENT_QUOTES);
    $staff  = htmlspecialchars($l['staff'], ENT_QUOTES);
    $log    = htmlspecialchars($l['log'], ENT_QUOTES);
    $filas .= "<article class=\"rw-logrow\">
        <div class=\"rw-logrow__head\"><span class=\"rw-logrow__id\">#$id</span><span class=\"rw-logrow__staff\">$staff</span><span class=\"rw-logrow__time\">$tiempo</span></div>
        <pre class=\"rw-logrow__body\">$log</pre>
    </article>";
}
if ($filas === '') {
    $filas = "<div class=\"rw-log-empty\">Aún no hay recompensas de staff registradas.</div>";
}
$log_reciente = "
<section class=\"rw-card\">
    <details class=\"rw-logbox\">
        <summary class=\"rw-logtoggle\">Ver últimos 20 cambios</summary>
        <div class=\"rw-logwrap\">$filas</div>
    </details>
</section>";

eval("\$page = \"".$templates->get("staff_recompensas_staff")."\";");
output_page($page);
