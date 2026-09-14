<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 * Optimiza en LOTE las imágenes ya subidas en images/sg/uploads/: las
 * redimensiona/recomprime con GD (JPEG/PNG/WebP) para reducir su peso.
 *
 * - Reemplaza el archivo EN EL SITIO (mismo nombre/URL), tras guardar un backup
 *   automático del original en images/sg/uploads/_originales/.
 * - Es IDEMPOTENTE: si un archivo ya tiene backup, se asume ya optimizado y se
 *   salta en corridas futuras (así solo procesa lo nuevo cada vez que se visita).
 * - No toca GIF (podrían ser animados; GD solo conserva el primer frame).
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'optimizar_imagenes.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/sg_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$es_staff = (is_mod($uid) || is_staff($uid));

$carpeta_fs  = MYBB_ROOT . 'images/sg/uploads/';
$backup_fs   = $carpeta_fs . '_originales/';
$allowed_opt = array('jpg', 'jpeg', 'png', 'webp'); // gif se deja intacto

$max_ancho = (int) $mybb->get_input('max_ancho');
if ($max_ancho <= 0) { $max_ancho = 1200; }
$calidad = (int) $mybb->get_input('calidad');
if ($calidad <= 0 || $calidad > 100) { $calidad = 78; }

$accion = $mybb->get_input('accion');
$reporte = array();
$ya_corrio = false;
$resumen = array('procesadas' => 0, 'omitidas' => 0, 'gif' => 0, 'errores' => 0, 'bytes_antes' => 0, 'bytes_despues' => 0);

if ($es_staff && $mybb->request_method === 'post' && ($accion === 'optimizar' || $accion === 'reprocesar_png')) {
    $ya_corrio = true;
    set_time_limit(120); // por si hay muchos archivos; inofensivo si el host lo ignora
    // 'reprocesar_png': re-aplica SOLO a los .png ya procesados (restaura desde su
    // backup y vuelve a optimizar). Es seguro repetirlo las veces que sea: PNG es
    // SIN PÉRDIDA, así que no hay degradación acumulada. NO toca JPEG/WebP (esos
    // sí son con pérdida: re-comprimirlos de nuevo perdería calidad sin necesidad,
    // ya que no tenían el bug que sí tenía la rama de PNG).
    $solo_reprocesar_png = ($accion === 'reprocesar_png');

    if (!is_dir($carpeta_fs)) {
        $reporte[] = array('nombre' => '(carpeta)', 'estado' => 'error', 'detalle' => 'No existe images/sg/uploads/.');
    } else {
        if (!is_dir($backup_fs)) { @mkdir($backup_fs, 0755, true); }

        foreach (scandir($carpeta_fs) as $nombre) {
            if ($nombre === '.' || $nombre === '..' || $nombre === '_originales') { continue; }
            $ruta = $carpeta_fs . $nombre;
            if (!is_file($ruta)) { continue; }

            $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
            $ruta_backup = $backup_fs . $nombre;

            if ($solo_reprocesar_png) {
                if ($ext !== 'png') { continue; } // esta acción no toca nada más
                if (!file_exists($ruta_backup)) { continue; } // nunca se procesó, nada que reintentar
                // Restaura el ORIGINAL pristino antes de reprocesar (por si alguna
                // vez sí se llegó a reemplazar bajo el bug viejo); PNG sin pérdida
                // hace que esto sea seguro repetirlo cuantas veces haga falta.
                if (!@copy($ruta_backup, $ruta)) {
                    $resumen['errores']++;
                    $reporte[] = array('nombre' => $nombre, 'estado' => 'error', 'detalle' => 'No se pudo restaurar desde el backup.');
                    continue;
                }
            } else {
                if ($ext === 'gif') {
                    $resumen['gif']++;
                    continue; // no se procesan ni se cuentan como omitidas por backup
                }
                if (!in_array($ext, $allowed_opt, true)) {
                    continue; // no es una imagen que sepamos optimizar
                }
                if (file_exists($ruta_backup)) {
                    // Ya se procesó en una corrida anterior (el backup es el marcador).
                    $resumen['omitidas']++;
                    continue;
                }
                // Backup ANTES de tocar el original.
                if (!@copy($ruta, $ruta_backup)) {
                    $resumen['errores']++;
                    $reporte[] = array('nombre' => $nombre, 'estado' => 'error', 'detalle' => 'No se pudo crear el backup (revisa permisos).');
                    continue;
                }
            }

            $res = sg_optimizar_imagen_archivo($ruta, $max_ancho, $calidad);

            if (!$res['ok']) {
                // Falló por una razón real (no "ya estaba optimizada").
                if (!$solo_reprocesar_png) {
                    // Modo normal: el archivo original sigue intacto (nunca se
                    // reemplazó); borra el backup para poder reintentar luego.
                    @unlink($ruta_backup);
                } // en reprocesar_png: ya se restauró el original arriba, se deja tal cual.
                $resumen['errores']++;
                $reporte[] = array('nombre' => $nombre, 'estado' => 'error', 'detalle' => $res['motivo']);
                continue;
            }

            $resumen['procesadas']++;
            $resumen['bytes_antes']   += $res['bytes_antes'];
            $resumen['bytes_despues'] += $res['bytes_despues'];

            $ahorro_pct = $res['bytes_antes'] > 0
                ? round((1 - ($res['bytes_despues'] / $res['bytes_antes'])) * 100)
                : 0;

            $reporte[] = array(
                'nombre'  => $nombre,
                'estado'  => 'ok',
                'detalle' => round($res['bytes_antes'] / 1024) . ' KB → ' . round($res['bytes_despues'] / 1024) . ' KB'
                    . ($ahorro_pct > 0 ? " (-$ahorro_pct%)" : '') . ' · ' . $res['ancho_antes'] . 'px → ' . $res['ancho_despues'] . 'px',
            );
        }
    }
}

// ── Estado actual de la carpeta (para mostrar antes de correr) ──
$total_actual = 0;
$peso_actual  = 0;
$pendientes   = 0;
$png_reprocesables = 0; // .png ya procesados (tienen backup) -> candidatos a "reprocesar_png"
if (is_dir($carpeta_fs)) {
    foreach (scandir($carpeta_fs) as $nombre) {
        if ($nombre === '.' || $nombre === '..' || $nombre === '_originales') { continue; }
        $ruta = $carpeta_fs . $nombre;
        if (!is_file($ruta)) { continue; }
        $total_actual++;
        $peso_actual += filesize($ruta);
        $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
        $tiene_backup = file_exists($backup_fs . $nombre);
        if (in_array($ext, $allowed_opt, true) && !$tiene_backup) {
            $pendientes++;
        }
        if ($ext === 'png' && $tiene_backup) {
            $png_reprocesables++;
        }
    }
}

// ── Render de la tabla-reporte ──
$reporte_html = '';
foreach ($reporte as $fila) {
    $cls = $fila['estado'] === 'ok' ? 'sg-opt-ok' : 'sg-opt-error';
    $nombre_esc  = htmlspecialchars($fila['nombre'], ENT_QUOTES);
    $detalle_esc = htmlspecialchars($fila['detalle'], ENT_QUOTES);
    $reporte_html .= "<tr class=\"$cls\"><td>$nombre_esc</td><td>$detalle_esc</td></tr>";
}
if ($reporte_html === '' && $ya_corrio) {
    $reporte_html = "<tr class=\"sg-opt-empty\"><td colspan=\"2\">No había imágenes nuevas por optimizar.</td></tr>";
}

eval('$totalActual = $total_actual;');
eval('$pesoActualMb = round($peso_actual / (1024*1024), 1);');
eval('$pendientes_v = $pendientes;');
eval('$pngReprocesables = $png_reprocesables;');
eval('$accionCorrida = $accion;');
eval('$maxAncho = $max_ancho;');
eval('$calidad_v = $calidad;');
eval('$yaCorrio = $ya_corrio ? 1 : 0;');
eval('$resumenProcesadas = $resumen["procesadas"];');
eval('$resumenOmitidas = $resumen["omitidas"];');
eval('$resumenGif = $resumen["gif"];');
eval('$resumenErrores = $resumen["errores"];');
eval('$resumenAhorroMb = round(($resumen["bytes_antes"] - $resumen["bytes_despues"]) / (1024*1024), 2);');
eval('$reporteHtml = $reporte_html;');

if ($es_staff) {
    eval("\$page = \"".$templates->get("staff_optimizar_imagenes")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
