<?php
/**
 * MyBB 1.8
 *
 * Dojo Shinobi: el personaje gasta Tobis para desarrollar su árbol de técnicas.
 * Ver docs/arboles_instruciones.txt.
 *
 * La acción se procesa en sg_dojo_aplicar_accion() (sg_functions.php), que
 * valida y serializa con GET_LOCK. La página re-renderiza con sg_dojo_estado().
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'dojo.php');

global $templates, $mybb, $db;

require_once "./../global.php";
require_once "./functions/sg_functions.php";

$uid = intval($mybb->user['uid']);

if (!does_ficha_exist($uid)) {
    eval("\$page = \"".$templates->get("sg_ficha_no_existe")."\";");
    output_page($page);
    exit;
}

// ── Procesa la acción (POST) ──────────────────────────────────
// Post/Redirect/Get: el resultado viaja por querystring para que un
// refresh posterior sea un GET inofensivo y no reenvíe el POST (esto
// importa sobre todo para la ruleta, que sí puede repetirse de verdad
// si el mismo POST se reenvía con slots/Tobis suficientes).
$action = isset($_POST['action']) ? trim($_POST['action']) : '';
if ($action !== '' && $uid > 0) {
    $params = array(
        'arbol'   => isset($_POST['arbol']) ? $_POST['arbol'] : '',
        'rama'    => isset($_POST['rama'])  ? $_POST['rama']  : '',
        'tid'     => isset($_POST['tid'])   ? $_POST['tid']   : '',
        'mejoras' => (isset($_POST['mejoras']) && is_array($_POST['mejoras'])) ? $_POST['mejoras'] : array(),
    );
    $res = sg_dojo_aplicar_accion($db, $uid, $action, $params);

    $redir = "dojo.php?resultado=" . urlencode($res['tipo']) . "&texto=" . urlencode($res['msg']);
    if ($action === 'ruleta' && !empty($res['extra']['elemento'])) {
        $redir .= "&elemento=" . urlencode($res['extra']['elemento']);
    }
    header("Location: $redir");
    exit;
}

// ── Resultado de la acción anterior (llega por GET tras el redirect) ──
// Valores whitelisteados: 'resultado' y 'elemento' se interpolan en
// contextos sensibles (clase CSS / string JS) más abajo en el template.
$resultado_get = trim($mybb->get_input('resultado'));
$msg_tipo = in_array($resultado_get, array('ok', 'error'), true) ? $resultado_get : '';
$msg = ($msg_tipo !== '') ? htmlspecialchars(trim($mybb->get_input('texto')), ENT_QUOTES) : '';

$elemento_get = trim($mybb->get_input('elemento'));
$ruleta_elemento = in_array($elemento_get, sg_arboles_naturales(), true) ? $elemento_get : '';

// ── Estado FRESCO para render ─────────────────────────────────
$estado = sg_dojo_estado($db, $uid);
$prog   = $estado['progreso'];
$costos = $estado['costos'];
$tobi   = (int) $estado['tobi'];
$nivel  = (int) $estado['nivel'];

// Nombres de técnicas para mostrar (un solo query).
$tids_needed = array();
foreach ($estado['arboles'] as $arbol => $ainfo) {
    foreach ($ainfo['ramas'] as $rama => $r) {
        if ($r['base'] !== '' && $r['base'] !== null) { $tids_needed[$r['base']] = true; }
        foreach ($r['mejoras_libres'] as $m) { $tids_needed[$m] = true; }
    }
    foreach ($ainfo['especializaciones']['elegibles'] as $e) { $tids_needed[$e] = true; }
}
$nombres = array();
if (!empty($tids_needed)) {
    $in = array();
    foreach (array_keys($tids_needed) as $t) { $in[] = "'".$db->escape_string($t)."'"; }
    $qn = $db->query("SELECT tid, nombre FROM mybb_sg_sg_tecnicas WHERE tid IN (".implode(',', $in).")");
    while ($r = $db->fetch_array($qn)) { $nombres[$r['tid']] = $r['nombre']; }
}
$tecnombre = function ($tid) use ($nombres) {
    $n = isset($nombres[$tid]) ? $nombres[$tid] : $tid;
    return htmlspecialchars($n, ENT_QUOTES);
};
$esc = function ($s) { return htmlspecialchars($s, ENT_QUOTES); };

// ── Helpers de render ─────────────────────────────────────────
// Form de acción de un clic.
$form_accion = function ($action, $hidden, $label, $enabled, $variant = 'plum') use ($esc) {
    $h = '';
    foreach ($hidden as $k => $v) {
        $h .= "<input type=\"hidden\" name=\"".$esc($k)."\" value=\"".$esc($v)."\">";
    }
    if (!$enabled) {
        return "<button class=\"sg-dbtn sg-dbtn--off\" type=\"button\" disabled>".$label."</button>";
    }
    $cls = $variant === 'free' ? 'sg-dbtn sg-dbtn--free' : 'sg-dbtn';
    return "<form method=\"post\" action=\"/sg/dojo.php\" class=\"sg-dform\">"
        . $h
        . "<input type=\"hidden\" name=\"action\" value=\"".$esc($action)."\">"
        . "<button class=\"".$cls."\" type=\"submit\">".$label."</button>"
        . "</form>";
};

$dojo_html = '';

// ── Panel: árboles elementales (ruleta natural + directo yin/yang) ──
$nivel_req = sg_dojo_nivel_requerido_arbol($prog['desbloqueo_arboles']);
$costo_arbol_lbl = $costos['arbol']." Tobis · nivel ".$nivel_req;
$ruleta = $estado['ruleta'];

$dojo_html .= "<section class=\"sg-dpanel\">";
$dojo_html .= "<div class=\"sg-dpanel-head\"><h2 class=\"sg-dpanel-title\">Árboles elementales</h2>";
$dojo_html .= "<span class=\"sg-dtag\">".$estado['slot_elementales']." slots</span>";
$dojo_html .= "</div>";

// Elementos naturales: ruleta, o elección (Afinidad Elemental / requisito de clan).
$dojo_html .= "<div class=\"sg-ruleta-cta\">";
if (!empty($ruleta['puede_elegir'])) {
    // Elección directa (sin tirada): forzada por el clan o por Afinidad Elemental.
    $label = !empty($ruleta['restringido'])
        ? "Requisito de clan · elige tu elemento"
        : "Afinidad Elemental · elige tu primer elemento";
    $dojo_html .= "<div class=\"sg-direct-label\">".$label." <em>· ".$costo_arbol_lbl." · 1 slot</em></div>";
    $dojo_html .= "<div class=\"sg-dchips\">";
    foreach ($ruleta['pool'] as $el) {
        $dojo_html .= $form_accion('elemento', array('arbol' => $el), ucfirst($el), true);
    }
    $dojo_html .= "</div>";
} else if ($ruleta['disponible']) {
    $dojo_html .= "<form method=\"post\" action=\"/sg/dojo.php\" class=\"sg-dform\">"
        . "<input type=\"hidden\" name=\"action\" value=\"ruleta\">"
        . "<button class=\"sg-dbtn sg-dbtn--big\" type=\"submit\">Desbloquea un árbol elemental <em>· ".$costo_arbol_lbl." · 1 slot</em></button>"
        . "</form>";
    $pool_txt = array_map('ucfirst', $ruleta['pool']);
    $nota = !empty($ruleta['restringido'])
        ? "Requisito de clan · la tirada sale solo entre: ".implode(', ', $pool_txt)."."
        : "Naturales restantes: ".(int) $ruleta['naturales_restantes']." · tirada al azar entre los bloqueados.";
    $dojo_html .= "<div class=\"sg-dnote sg-dnote--soft\">".$esc($nota)."</div>";
} else {
    $dojo_html .= "<button class=\"sg-dbtn sg-dbtn--off sg-dbtn--big\" type=\"button\" disabled>Desbloquea un árbol elemental</button>";
    if (!empty($ruleta['razon'])) {
        $dojo_html .= "<div class=\"sg-dnote\">".$esc($ruleta['razon'])."</div>";
    }
}
$dojo_html .= "</div>";

// Selección directa (yin / yang)
$directos = $estado['adquiribles_directos'];
if (!empty($directos)) {
    $dojo_html .= "<div class=\"sg-direct\"><div class=\"sg-direct-label\">Selección directa</div><div class=\"sg-dchips\">";
    foreach ($directos as $el) {
        $hidden = array('arbol' => $el);
        if ($nivel < $nivel_req) {
            $btn = $form_accion('arbol', $hidden, ucfirst($el)." <em>· nivel ".$nivel_req."</em>", false);
        } else if ($tobi < $costos['arbol']) {
            $btn = $form_accion('arbol', $hidden, ucfirst($el)." <em>· ".$costos['arbol']." Tobis</em>", false);
        } else {
            $btn = $form_accion('arbol', $hidden, ucfirst($el)." <em>· ".$costos['arbol']." Tobis</em>", true);
        }
        $dojo_html .= $btn;
    }
    $dojo_html .= "</div></div>";
}
$dojo_html .= "</section>";

// ── Por cada árbol poseído ────────────────────────────────────
$nivelr_disp   = (int) $prog['nivel_rama_disponibles'];
$clan_arboles  = isset($estado['clan']['arboles']) ? $estado['clan']['arboles'] : array();
$clan_libre    = !empty($estado['clan']['rama_gratis_disponible']);
$es_hibrido    = !empty($estado['clan']['es_hibrido']);
$clan_ramas_n  = (int) $estado['clan']['ramas_desbloqueadas'];
$clan_ramas_mx = (int) $estado['clan']['ramas_max'];
$clan_espec_n  = (int) $estado['clan']['espec_aprendidas'];
$clan_espec_mx = (int) $estado['clan']['espec_max'];

foreach ($estado['arboles'] as $arbol => $ainfo) {
    $es_clan = in_array($arbol, $clan_arboles, true);
    $nivel_arbol = (int) $ainfo['nivel_arbol'];

    $dojo_html .= "<section class=\"sg-tree\">";
    $dojo_html .= "<div class=\"sg-tree-head\">";
    $eyebrow = "Árbol" . ($es_clan ? ($es_hibrido ? " · Clan (híbrido)" : " · Clan") : "");
    $dojo_html .= "<div class=\"sg-tree-titles\"><span class=\"sg-tree-eyebrow\">".$eyebrow."</span>";
    $dojo_html .= "<h2 class=\"sg-tree-name\">".ucfirst($esc($arbol))."</h2></div>";
    $dojo_html .= "<span class=\"sg-tree-lvl\">Nivel ".$nivel_arbol." / 9</span>";
    $dojo_html .= "</div>";

    // En híbrido, los dos árboles de clan comparten el tope de 3 ramas y 3 especialidades.
    if ($es_clan && $es_hibrido) {
        $dojo_html .= "<div class=\"sg-dnote sg-dnote--soft\">Clan compartido · Ramas ".$clan_ramas_n." / ".$clan_ramas_mx." · Especialidades ".$clan_espec_n." / ".$clan_espec_mx."</div>";
    }

    // Ramas
    $dojo_html .= "<div class=\"sg-ramas\">";
    foreach ($ainfo['ramas'] as $rama => $r) {
        $rama_label = ucfirst($esc($rama));
        $dojo_html .= "<div class=\"sg-rama\">";
        $dojo_html .= "<div class=\"sg-rama-head\"><span class=\"sg-rama-name\">".$rama_label."</span>";

        if (!empty($r['desbloqueada'])) {
            $dojo_html .= "<span class=\"sg-rama-lvl\">".$r['nivel']." / 3</span></div>";

            // Base aprendida
            $dojo_html .= "<div class=\"sg-rama-base\">".$tecnombre($r['base'])."</div>";

            if (!empty($r['puede_subir'])) {
                // Selección de 2 mejoras
                $box_id = "mb-".$esc($arbol)."-".preg_replace('/\s+/', '', $rama);
                $dojo_html .= "<form method=\"post\" action=\"/sg/dojo.php\" class=\"sg-mejoras\" onchange=\"sgDojoMejoras(this)\">";
                $dojo_html .= "<input type=\"hidden\" name=\"action\" value=\"nivel\">";
                $dojo_html .= "<input type=\"hidden\" name=\"arbol\" value=\"".$esc($arbol)."\">";
                $dojo_html .= "<input type=\"hidden\" name=\"rama\" value=\"".$esc($rama)."\">";
                $dojo_html .= "<div class=\"sg-mejoras-hint\">Elige 2 mejoras de esta rama:</div>";
                $dojo_html .= "<div class=\"sg-mejoras-list\">";
                foreach ($r['mejoras_libres'] as $mtid) {
                    $dojo_html .= "<label class=\"sg-mcheck\"><input type=\"checkbox\" name=\"mejoras[]\" value=\"".$esc($mtid)."\"><span>".$tecnombre($mtid)."</span></label>";
                }
                $dojo_html .= "</div>";
                $costo_lbl = $nivelr_disp > 0 ? "Subir nivel · gratis" : "Subir nivel · ".$costos['nivel']." Tobis";
                $puede_pagar = $nivelr_disp > 0 || $tobi >= $costos['nivel'];
                $dojo_html .= "<button class=\"sg-dbtn".($nivelr_disp > 0 ? " sg-dbtn--free" : "")."\" type=\"submit\" disabled data-needtobi=\"".($puede_pagar ? '1' : '0')."\">".$costo_lbl."</button>";
                if (!$puede_pagar) {
                    $dojo_html .= "<div class=\"sg-rama-note\">Tobis insuficientes.</div>";
                }
                $dojo_html .= "</form>";
            } else {
                $dojo_html .= "<div class=\"sg-rama-note\">Rama al máximo (6/6 mejoras).</div>";
            }
        } else if (!empty($r['desbloqueable'])) {
            $dojo_html .= "<span class=\"sg-rama-lvl sg-rama-lvl--lock\">Bloqueada</span></div>";
            $dojo_html .= "<div class=\"sg-rama-actions\">";
            // Mientras no se use la rama de clan gratis, ese árbol NO se puede
            // pagar con Tobis: primero hay que elegir la rama gratis.
            $clan_pendiente = ($es_clan && (int) $prog['clan_rama_usada'] === 0);
            $hidden = array('arbol' => $arbol, 'rama' => $rama);
            if (!$clan_pendiente) {
                if ($tobi >= $costos['rama']) {
                    $dojo_html .= $form_accion('rama', $hidden, "Desbloquear · ".$costos['rama']." Tobis", true);
                } else {
                    $dojo_html .= $form_accion('rama', $hidden, "Desbloquear · ".$costos['rama']." Tobis", false);
                }
            }
            // Rama de clan gratis (una vez). Sirve para cualquiera de los árboles de clan.
            if ($es_clan && $clan_libre) {
                $dojo_html .= $form_accion('rama_clan', array('arbol' => $arbol, 'rama' => $rama), "Rama de clan · gratis", true, 'free');
            }
            $dojo_html .= "</div>";
        }

        $dojo_html .= "</div>"; // .sg-rama
    }
    $dojo_html .= "</div>"; // .sg-ramas

    // Especializaciones
    $esp = $ainfo['especializaciones'];
    if ((int) $esp['cupo'] > 0) {
        $dojo_html .= "<div class=\"sg-espec\">";
        // En híbrido el cupo es compartido entre los dos clanes: muestra el total.
        $cupo_lbl = ($es_clan && $es_hibrido)
            ? $clan_espec_n." / ".$clan_espec_mx." (clan)"
            : $esp['aprendidas']." / ".$esp['cupo'];
        $dojo_html .= "<div class=\"sg-espec-head\"><span class=\"sg-espec-title\">Especializaciones</span>";
        $dojo_html .= "<span class=\"sg-espec-cupo\">".$cupo_lbl."</span></div>";
        if (!empty($esp['elegibles']) && (int) $esp['aprendidas'] < (int) $esp['cupo']) {
            $dojo_html .= "<div class=\"sg-dchips\">";
            foreach ($esp['elegibles'] as $etid) {
                $dojo_html .= $form_accion('especializacion', array('arbol' => $arbol, 'tid' => $etid), $tecnombre($etid)." <em>· gratis</em>", true, 'free');
            }
            $dojo_html .= "</div>";
        } else if ((int) $esp['aprendidas'] >= (int) $esp['cupo']) {
            $dojo_html .= "<p class=\"sg-dnote\">Sube más niveles de ramas para desbloquear otra especialización.</p>";
        }
        $dojo_html .= "</div>";
    }

    $dojo_html .= "</section>"; // .sg-tree
}

$tobi_label = number_format($tobi, 0, ',', '.');
$nivel_label = $nivel;

eval("\$page = \"".$templates->get("sg_dojo")."\";");
output_page($page);
