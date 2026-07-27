<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 * Intercambios entre jugadores (regalo unilateral). Ver docs/intercambios_diseno.md.
 * El emisor le envía ryos y/o objetos a otro personaje. Requisitos: un TID de
 * contacto dentro de la zona de rol (foro 37) donde ambos hayan posteado, y que
 * ambos sean de la misma aldea. Toda operación se valida en el servidor y se
 * serializa con GET_LOCK.
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'intercambios.php');

global $templates, $mybb, $db;

require_once "./../global.php";
require_once "./functions/sg_functions.php";

$uid = intval($mybb->user['uid']);
$default_img = '/images/sg/objeto_default.png';

$accion       = isset($_POST['accion']) ? $_POST['accion'] : '';
$dest_input   = isset($_POST['destinatario']) ? trim($_POST['destinatario']) : '';
$tid_input    = isset($_POST['tid']) ? trim($_POST['tid']) : '';
$ryos_input   = isset($_POST['ryos']) ? intval($_POST['ryos']) : 0;
// Mapa objeto_id => cantidad (los que el emisor marcó para enviar).
$cant_input   = (isset($_POST['cantidad']) && is_array($_POST['cantidad'])) ? $_POST['cantidad'] : array();

$msg_ok = '';
$msg_error = '';

if (!does_ficha_exist($uid)) {
    eval("\$page = \"".$templates->get("sg_ficha_no_existe")."\";");
    output_page($page);
    exit;
}

$ficha = select_one_query_with_id('mybb_sg_sg_fichas', 'fid', $uid);
$ryos = intval($ficha['ryos']);

// El TID puede venir como número o como URL del tema (se extrae el tid).
$tid_num = 0;
if ($tid_input !== '') {
    if (preg_match('/tid=(\d+)/', $tid_input, $m)) {
        $tid_num = (int) $m[1];
    } else {
        $tid_num = (int) preg_replace('/\D/', '', $tid_input);
    }
}

// ── Envío (validado y serializado en el servidor) ─────────────
if ($accion === 'enviar' && $uid > 0) {
    // Normaliza los objetos pedidos: objeto_id => cantidad (>0), sumando repes.
    $objetos_pedidos = array();
    foreach ($cant_input as $oid => $qty) {
        $oid = trim((string) $oid);
        $qty = intval($qty);
        if ($oid === '' || $qty <= 0) { continue; }
        $objetos_pedidos[$oid] = (isset($objetos_pedidos[$oid]) ? $objetos_pedidos[$oid] : 0) + $qty;
    }
    $ryos_env = max(0, $ryos_input);

    // Resolver destinatario (username -> uid).
    $to_uid = 0; $to_username = '';
    if ($dest_input !== '') {
        // Acepta nombre de usuario O uid. Si es puramente numérico, se busca
        // también por uid (con prioridad); si no, solo por username.
        $dest_esc = $db->escape_string($dest_input);
        $dest_uid_maybe = ctype_digit($dest_input) ? (int) $dest_input : 0;
        $qd = $db->query("
            SELECT uid, username FROM `mybb_sg_users`
            WHERE uid='$dest_uid_maybe' OR username='$dest_esc'
            ORDER BY (uid='$dest_uid_maybe') DESC
            LIMIT 1
        ");
        while ($d = $db->fetch_array($qd)) { $to_uid = (int) $d['uid']; $to_username = $d['username']; }
    }

    if ($to_uid <= 0) {
        $msg_error = "No se encontró un usuario con ese nombre o UID.";
    } else if ($to_uid === $uid) {
        $msg_error = "No puedes enviarte un intercambio a ti mismo.";
    } else if (!does_ficha_exist($to_uid)) {
        $msg_error = "Ese usuario no tiene una ficha activa.";
    } else if ($ryos_env <= 0 && empty($objetos_pedidos)) {
        $msg_error = "Debes enviar al menos ryos u objetos.";
    } else if ($tid_num <= 0) {
        $msg_error = "Indica el tema (TID) de contacto.";
    } else if (!sg_tid_zona_rol($tid_num)) {
        $msg_error = "Ese tema no es válido para intercambios (debe ser un tema de rol).";
    } else if (!sg_ambos_en_tema($uid, $to_uid, $tid_num)) {
        $msg_error = "Tú y ese usuario deben haber posteado ambos en ese tema.";
    } else if (!sg_fichas_misma_aldea($uid, $to_uid)) {
        $msg_error = "Solo puedes intercambiar con alguien de tu misma aldea.";
    } else {
        $lock_name = "sg_intercambio_$uid";
        $got_lock = 0;
        $rl = $db->query("SELECT GET_LOCK('$lock_name', 5) AS l");
        while ($r = $db->fetch_array($rl)) { $got_lock = intval($r['l']); }

        if ($got_lock !== 1) {
            $msg_error = "No se pudo procesar el envío en este momento. Intenta de nuevo.";
        } else {
            // Saldos/inventario FRESCOS del emisor dentro del lock.
            $ryos_emisor = 0;
            $qr = $db->query("SELECT ryos FROM `mybb_sg_sg_fichas` WHERE fid='$uid'");
            while ($r = $db->fetch_array($qr)) { $ryos_emisor = intval($r['ryos']); }

            $inv_emisor = array(); // objeto_id => cantidad
            $qi = $db->query("SELECT objeto_id, cantidad FROM `mybb_sg_sg_inventario` WHERE uid='$uid'");
            while ($r = $db->fetch_array($qi)) { $inv_emisor[$r['objeto_id']] = intval($r['cantidad']); }

            // Validación final con datos frescos.
            $error_val = '';
            if ($ryos_env > $ryos_emisor) {
                $error_val = "Ryos insuficientes: necesitas " . number_format($ryos_env, 0, ',', '.') . " y tienes " . number_format($ryos_emisor, 0, ',', '.') . ".";
            } else {
                foreach ($objetos_pedidos as $oid => $qty) {
                    $tiene = isset($inv_emisor[$oid]) ? $inv_emisor[$oid] : 0;
                    if ($qty > $tiene) {
                        $error_val = "No tienes suficientes unidades de un objeto seleccionado.";
                        break;
                    }
                }
            }

            if ($error_val !== '') {
                $msg_error = $error_val;
            } else {
                // Saldo de ryos fresco del receptor (para el nuevo valor absoluto).
                $ryos_receptor = 0;
                $qrr = $db->query("SELECT ryos FROM `mybb_sg_sg_fichas` WHERE fid='$to_uid'");
                while ($r = $db->fetch_array($qrr)) { $ryos_receptor = intval($r['ryos']); }

                $grupo = uniqid();
                $detalle_emisor   = "Intercambio: envío a " . $to_username;
                $detalle_receptor = "Intercambio: recibido de " . $mybb->user['username'];

                $db->query("START TRANSACTION");

                // Emisor (A): resta ryos y objetos.
                if ($ryos_env > 0) {
                    sg_ficha_set_campo($uid, 'ryos', $ryos_emisor - $ryos_env, 'usuario', SG_ORIGEN_INTERCAMBIO, $detalle_emisor, $grupo);
                }
                foreach ($objetos_pedidos as $oid => $qty) {
                    sg_inventario_quitar_objeto($uid, $oid, $qty, 'usuario', SG_ORIGEN_INTERCAMBIO, $detalle_emisor, $grupo);
                }

                // Receptor (B): suma ryos y objetos. actor_uid = emisor.
                if ($ryos_env > 0) {
                    sg_ficha_set_campo($to_uid, 'ryos', $ryos_receptor + $ryos_env, 'usuario', SG_ORIGEN_INTERCAMBIO, $detalle_receptor, $grupo, $uid);
                }
                foreach ($objetos_pedidos as $oid => $qty) {
                    sg_inventario_dar_objeto_cantidad($to_uid, $oid, $qty, 'usuario', SG_ORIGEN_INTERCAMBIO, $detalle_receptor, $grupo, $uid);
                }

                // Registro visual (cabecera + items).
                $ryos_env_db = (int) $ryos_env;
                $tid_db = (int) $tid_num;
                $grupo_db = $db->escape_string($grupo);
                $db->query("
                    INSERT INTO `mybb_sg_sg_intercambios` (from_uid, to_uid, tid, ryos, grupo)
                    VALUES ('$uid', '$to_uid', '$tid_db', '$ryos_env_db', '$grupo_db')
                ");
                $intercambio_id = (int) $db->insert_id();
                foreach ($objetos_pedidos as $oid => $qty) {
                    $oid_db = $db->escape_string($oid);
                    $qty_db = (int) $qty;
                    $db->query("
                        INSERT INTO `mybb_sg_sg_intercambios_items` (intercambio_id, objeto_id, cantidad)
                        VALUES ('$intercambio_id', '$oid_db', '$qty_db')
                    ");
                }

                $db->query("COMMIT");

                $ryos = $ryos_emisor - $ryos_env; // refleja el saldo para el render
                $onombre = htmlspecialchars($to_username, ENT_QUOTES);
                $msg_ok = "Enviaste tu intercambio a \"$onombre\".";
            }

            $db->query("SELECT RELEASE_LOCK('$lock_name')");
        }
    }
}

// ── Inventario del emisor (para el formulario de envío) ────────
$inv_html = '';
$qinv = $db->query("
    SELECT o.objeto_id, o.nombre, o.tipo, o.imagen, i.cantidad
    FROM mybb_sg_sg_inventario i
    INNER JOIN mybb_sg_sg_objetos o ON o.objeto_id = i.objeto_id
    WHERE i.uid = '$uid' AND i.cantidad > 0
    ORDER BY o.tipo, o.nombre
");
$inv_count = 0;
$inv_tipos = array();   // tipos distintos, en orden de aparición: array(label, key)
$inv_conteo = array();  // nº de objetos por tipo (key)
while ($it = $db->fetch_array($qinv)) {
    $inv_count++;
    $i_oid    = htmlspecialchars($it['objeto_id'], ENT_QUOTES);
    $i_nombre = htmlspecialchars($it['nombre'], ENT_QUOTES);
    $i_tipo_disp = trim($it['tipo']) !== '' ? $it['tipo'] : 'Otros';
    $i_tipo   = htmlspecialchars($i_tipo_disp, ENT_QUOTES);
    $i_data_tipo = htmlspecialchars(strtolower($i_tipo_disp), ENT_QUOTES);
    $i_cant   = intval($it['cantidad']);
    $i_img    = trim($it['imagen']) !== '' ? htmlspecialchars($it['imagen'], ENT_QUOTES) : $default_img;
    $i_search = htmlspecialchars(strtolower($it['nombre'] . ' ' . $i_tipo_disp), ENT_QUOTES);

    if (!isset($inv_conteo[$i_data_tipo])) { $inv_conteo[$i_data_tipo] = 0; $inv_tipos[] = array($i_tipo, $i_data_tipo); }
    $inv_conteo[$i_data_tipo]++;

    $inv_html .= "<label class=\"ic-item\" data-search=\"$i_search\" data-tipo=\"$i_data_tipo\">"
        . "<div class=\"ic-item-thumb\"><img src=\"$i_img\" alt=\"$i_nombre\" loading=\"lazy\" onerror=\"icImgFallback(this)\"></div>"
        . "<div class=\"ic-item-body\">"
        . "<div class=\"ic-item-name\">$i_nombre</div>"
        . "<div class=\"ic-item-badges\"><span class=\"ic-item-badge\">$i_tipo</span><span class=\"ic-item-have\">Tienes $i_cant</span></div>"
        . "<div class=\"ic-item-qty\">"
        . "<span class=\"ic-item-qty-label\">Enviar</span>"
        . "<input class=\"ic-qty\" type=\"number\" name=\"cantidad[$i_oid]\" min=\"0\" max=\"$i_cant\" value=\"0\">"
        . "</div>"
        . "</div>"
        . "</label>";
}
if ($inv_html === '') {
    $inv_html = "<div class=\"ic-empty\">No tienes objetos en tu inventario para enviar.</div>";
}

// Chips de filtro por categoría (Todos + cada tipo distinto), con su conteo.
$inv_chips_html = '';
if ($inv_count > 0) {
    $inv_chips_html = "<button class=\"ic-chip is-active\" type=\"button\" data-tipo=\"\" onclick=\"icInvSetTipo(this)\">Todos <span class=\"ic-chip-n\">$inv_count</span></button>";
    foreach ($inv_tipos as $t) {
        $tn = $inv_conteo[$t[1]];
        $inv_chips_html .= "<button class=\"ic-chip\" type=\"button\" data-tipo=\"{$t[1]}\" onclick=\"icInvSetTipo(this)\">{$t[0]} <span class=\"ic-chip-n\">$tn</span></button>";
    }
}

// ── Registros (global + personal) ─────────────────────────────
function ic_ficha_link($uid, $username, $bburl) {
    $uid = intval($uid);
    $label = htmlspecialchars($username !== null ? $username : ('UID ' . $uid));
    $url = htmlspecialchars($bburl . '/sg/ficha.php?uid=' . $uid);
    return '<a class="ic-hist-ficha" href="' . $url . '">' . $label . '</a>';
}

function ic_intercambio_fila_html($row, $self_uid, $bburl) {
    $fecha = htmlspecialchars(substr($row['tiempo'], 0, 16));
    $from  = ic_ficha_link($row['from_uid'], $row['from_username'], $bburl);
    $to    = ic_ficha_link($row['to_uid'], $row['to_username'], $bburl);

    $partes = array();
    if (intval($row['ryos']) > 0) {
        $partes[] = number_format(intval($row['ryos']), 0, ',', '.') . ' Ryos';
    }
    foreach ($row['items'] as $it) {
        $nom = htmlspecialchars($it['nombre']);
        $partes[] = ($it['cantidad'] > 1 ? $it['cantidad'] . '× ' : '') . $nom;
    }
    $contenido = empty($partes) ? '<span class="ic-hist-nada">(nada)</span>' : implode(', ', $partes);

    $tid = intval($row['tid']);
    $enlace = $tid > 0
        ? ' · <a class="ic-hist-link" href="' . htmlspecialchars($bburl . '/showthread.php?tid=' . $tid) . '">tema</a>'
        : '';

    // Marca de dirección para el registro personal (enviado/recibido).
    $dir = '';
    if ($self_uid !== null) {
        if (intval($row['from_uid']) === $self_uid) {
            $dir = '<span class="ic-hist-dir ic-hist-dir--out">Enviado</span>';
        } else if (intval($row['to_uid']) === $self_uid) {
            $dir = '<span class="ic-hist-dir ic-hist-dir--in">Recibido</span>';
        }
    }

    return '<div class="ic-hist-row">'
        . '<span class="ic-hist-date">' . $fecha . '</span>'
        . $dir
        . '<span class="ic-hist-people">' . $from . ' → ' . $to . '</span>'
        . '<span class="ic-hist-content">' . $contenido . $enlace . '</span>'
        . '</div>';
}

$bburl = isset($mybb->settings['bburl']) ? $mybb->settings['bburl'] : '';

$hist_global_html = '';
foreach (sg_intercambios_global(100) as $row) { $hist_global_html .= ic_intercambio_fila_html($row, null, $bburl); }
if ($hist_global_html === '') { $hist_global_html = '<div class="ic-hist-empty">Todavía no se ha registrado ningún intercambio.</div>'; }

$hist_personal_html = '';
foreach (sg_intercambios_usuario($uid, 100) as $row) { $hist_personal_html .= ic_intercambio_fila_html($row, $uid, $bburl); }
if ($hist_personal_html === '') { $hist_personal_html = '<div class="ic-hist-empty">Todavía no enviaste ni recibiste intercambios.</div>'; }

$ryos_label = number_format($ryos, 0, ',', '.');

eval('$icInventarioHtml = $inv_html;');
eval('$icInvChipsHtml = $inv_chips_html;');
eval('$icHistGlobalHtml = $hist_global_html;');
eval('$icHistPersonalHtml = $hist_personal_html;');
eval("\$page = \"".$templates->get("sg_intercambios")."\";");
output_page($page);
