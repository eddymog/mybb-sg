<?php

// ============================================================================
// Historial estructurado de cambios de ficha (ver docs/instrucciones_cambios.md).
// `origen` es un vocabulario controlado: NUNCA se pasa un literal suelto, siempre
// una de estas constantes, para que el filtrado por origen no se rompa por typos.
// ============================================================================
define('SG_ORIGEN_MODIFICAR_FICHA',  'modificar_ficha');
define('SG_ORIGEN_ATRIBUTOS',        'ficha_atributos');
define('SG_ORIGEN_RECOMPENSA_STAFF', 'recompensa_staff');
define('SG_ORIGEN_RECOMPENSA_MISION','recompensa_mision');
define('SG_ORIGEN_APROBACION',       'aprobacion');
define('SG_ORIGEN_TIENDA',           'tienda');
define('SG_ORIGEN_TIENDA_RINS',      'tienda_rins');
define('SG_ORIGEN_TIENDA_TOBIS',     'tienda_tobis');
define('SG_ORIGEN_INTERCAMBIO',      'intercambio');
define('SG_ORIGEN_NUEVA_FICHA',      'nueva_ficha');
define('SG_ORIGEN_VENDER',           'vender');
define('SG_ORIGEN_DOJO',             'dojo');
define('SG_ORIGEN_FICHA_TECNICAS',   'ficha_tecnicas');
define('SG_ORIGEN_FICHA_OBJETOS',    'ficha_objetos');
define('SG_ORIGEN_MISION_ENTRENAMIENTO', 'mision_entrenamiento');
define('SG_ORIGEN_RECOMPENSA_DIARIA',    'recompensa_diaria');
define('SG_ORIGEN_FICHA_EDITADA',        'ficha_editada');
define('SG_ORIGEN_TRASFONDO',            'trasfondo');
define('SG_ORIGEN_CONSUMIR',             'consumir');
define('SG_ORIGEN_PERGAMINO',            'pergamino');
define('SG_ORIGEN_PERGAMINO_REGALO',     'pergamino_regalo');
// Eventos de foro que mueven XP/rin (todos vía newpoints_addpoints)
define('SG_ORIGEN_POST_NUEVO',       'post_nuevo');
define('SG_ORIGEN_POST_EDITADO',     'post_editado');
define('SG_ORIGEN_POST_BORRADO',     'post_borrado');
define('SG_ORIGEN_POST_APROBADO',    'post_aprobado');
define('SG_ORIGEN_POST_DESAPROBADO', 'post_desaprobado');
define('SG_ORIGEN_TEMA_NUEVO',       'tema_nuevo');
define('SG_ORIGEN_TEMA_BORRADO',     'tema_borrado');
define('SG_ORIGEN_TEMA_APROBADO',    'tema_aprobado');
define('SG_ORIGEN_TEMA_DESAPROBADO', 'tema_desaprobado');

// Redimensiona/recomprime UNA imagen en el disco (JPEG/PNG/WebP) usando GD.
// No agranda: si ya es más angosta que $max_ancho, solo recomprime con $calidad.
// No toca GIF (podría ser animado; GD solo conserva el primer frame).
// Reemplaza el archivo original SOLO si el resultado pesa menos.
// Devuelve: array('ok'=>bool, 'motivo'=>string, 'bytes_antes','bytes_despues','ancho_antes','ancho_despues')
function sg_optimizar_imagen_archivo($ruta_fs, $max_ancho = 1200, $calidad = 78) {
    $r = array(
        'ok' => false, 'motivo' => '',
        'bytes_antes' => 0, 'bytes_despues' => 0,
        'ancho_antes' => 0, 'ancho_despues' => 0,
    );

    if (!is_file($ruta_fs)) {
        $r['motivo'] = 'Archivo no encontrado.';
        return $r;
    }
    $r['bytes_antes'] = filesize($ruta_fs);

    $info = @getimagesize($ruta_fs);
    if ($info === false) {
        $r['motivo'] = 'No es una imagen válida.';
        return $r;
    }
    list($ancho, $alto, $tipo) = $info;
    $r['ancho_antes'] = $ancho;

    switch ($tipo) {
        case IMAGETYPE_JPEG:
            $src = @imagecreatefromjpeg($ruta_fs);
            break;
        case IMAGETYPE_PNG:
            $src = @imagecreatefrompng($ruta_fs);
            break;
        case IMAGETYPE_WEBP:
            if (!function_exists('imagecreatefromwebp')) {
                $r['motivo'] = 'El servidor no tiene soporte WebP en GD.';
                return $r;
            }
            $src = @imagecreatefromwebp($ruta_fs);
            break;
        default:
            $r['motivo'] = 'Formato no soportado (solo JPEG/PNG/WebP; los GIF no se tocan).';
            return $r;
    }
    if (!$src) {
        $r['motivo'] = 'No se pudo leer la imagen (¿archivo corrupto?).';
        return $r;
    }

    // Nunca agranda: si ya es más angosta que el máximo, se mantiene el ancho.
    $ancho_nuevo = $ancho;
    $alto_nuevo  = $alto;
    if ($ancho > $max_ancho) {
        $ancho_nuevo = $max_ancho;
        $alto_nuevo  = (int) round($alto * ($max_ancho / $ancho));
    }

    $dst = imagecreatetruecolor($ancho_nuevo, $alto_nuevo);

    // Conserva transparencia en PNG/WebP.
    if ($tipo === IMAGETYPE_PNG || $tipo === IMAGETYPE_WEBP) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparente = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $ancho_nuevo, $alto_nuevo, $transparente);
    }

    imagecopyresampled($dst, $src, 0, 0, 0, 0, $ancho_nuevo, $alto_nuevo, $ancho, $alto);
    // imagedestroy() es un no-op desde PHP 8.0 (y deprecated desde 8.5); solo hace
    // falta en versiones más viejas donde sí libera memoria explícitamente.
    if (PHP_VERSION_ID < 80000) { imagedestroy($src); }

    $tmp = $ruta_fs . '.tmp' . uniqid();
    $guardado = false;
    switch ($tipo) {
        case IMAGETYPE_JPEG:
            $guardado = imagejpeg($dst, $tmp, $calidad);
            break;
        case IMAGETYPE_PNG:
            // PNG es SIN PÉRDIDA: su parámetro 0-9 es solo esfuerzo de compresión,
            // no "calidad" visual (no hay tal trade-off). Nivel 9 siempre da el
            // archivo más chico posible con el MISMO resultado visual, así que se
            // usa siempre el máximo (el parámetro $calidad no aplica a PNG).
            $guardado = imagepng($dst, $tmp, 9);
            break;
        case IMAGETYPE_WEBP:
            $guardado = imagewebp($dst, $tmp, $calidad);
            break;
    }
    if (PHP_VERSION_ID < 80000) { imagedestroy($dst); }

    if (!$guardado || !is_file($tmp)) {
        @unlink($tmp);
        $r['motivo'] = 'No se pudo guardar la versión optimizada.';
        return $r;
    }

    $bytes_nuevo = filesize($tmp);

    // Si la "optimizada" no pesa menos (raro, pasa con imágenes ya muy comprimidas),
    // se descarta y se deja la original intacta.
    if ($bytes_nuevo >= $r['bytes_antes']) {
        @unlink($tmp);
        $r['ok'] = true;
        $r['motivo'] = 'Ya estaba optimizada; se mantuvo el archivo original.';
        $r['bytes_despues'] = $r['bytes_antes'];
        $r['ancho_despues'] = $ancho;
        return $r;
    }

    if (!@rename($tmp, $ruta_fs)) {
        @unlink($tmp);
        $r['motivo'] = 'No se pudo reemplazar el archivo original (revisa permisos).';
        return $r;
    }
    @chmod($ruta_fs, 0644);

    $r['ok'] = true;
    $r['bytes_despues'] = $bytes_nuevo;
    $r['ancho_despues'] = $ancho_nuevo;
    return $r;
}

// Banners rotativos del header (mybb_sg_sg_banners). Rotación DETERMINISTA por
// reloj: todos los visitantes ven el mismo banner dentro de la misma ventana de
// $duracion segundos, sin cron ni proceso en segundo plano — solo se deriva de
// TIME_NOW. El JS del header avanza al siguiente en vivo cada $duracion.
function sg_banner_rotativo($db, $duracion = 300) {
    $banners = array();
    $q = $db->query("SELECT id, imagen, titulo FROM mybb_sg_sg_banners WHERE activo=1 ORDER BY orden ASC, id ASC");
    while ($r = $db->fetch_array($q)) { $banners[] = $r; }

    $total = count($banners);
    if ($total === 0) {
        return array('banners' => array(), 'slot' => 0, 'actual' => null);
    }

    $slot = (int) floor(TIME_NOW / $duracion) % $total;
    return array('banners' => $banners, 'slot' => $slot, 'actual' => $banners[$slot]);
}

function does_ficha_exist($uid) {
    global $db;
    $ficha = select_one_query_with_id('mybb_sg_sg_fichas', 'fid', $uid);
    $moderada = $ficha['moderated']!= 'no_moderacion';

    return $ficha != null && $moderada;
}

// Sirve para ficha y tienda
function select_one_query_with_id($table_name, $id_name, $id) {
    global $db;

    $obj = null;

    $query = $db->query("
        SELECT * FROM $table_name WHERE $id_name='$id'
    ");

    while ($q = $db->fetch_array($query)) {
        $obj = $q;
    }

    return $obj;
}

function get_obj_from_query($query) {
    global $db;
    
    $obj = null;
    while ($q = $db->fetch_array($query)) {
        $obj = $q;
    }
    return $obj;
}

function calculate_vida($str, $res) {
    return ($str * 3) + ($res * 4);
}

function calculate_chakra($str, $res, $spd, $agi, $dex, $pres, $inte, $ctrl) {
    return round(($str * 1) + ($res * 0.5) + ($spd * 2) + ($agi * 0.5) + ($dex * 2) + ($pres * 2) + ($inte * 2) + ($ctrl * 2.5));
}

function calculate_vida2($fuerza, $destreza, $cchakra, $inteligencia, $salud, $velocidad, $tenketsu, $sigilo) {
    return ($fuerza * 2) + ($destreza * 1) + ($cchakra * 1) + ($inteligencia * 2) + ($salud * 10) + ($velocidad * 5) + ($tenketsu * 0) + ($sigilo * 5);
}

function calculate_chakra2($fuerza, $destreza, $cchakra, $inteligencia, $salud, $velocidad, $tenketsu, $sigilo) {
    return ($fuerza * 1) + ($destreza * 2) + ($cchakra * 2) + ($inteligencia * 1) + ($salud * 0) + ($velocidad * 5) + ($tenketsu * 10) + ($sigilo * 5);
}

function calculate_reg_chakra($str, $res, $spd, $agi, $dex, $pres, $inte, $ctrl) {
    return round(((($str + $res + $spd + $agi + $dex + $pres + $inte + $ctrl) * 2)) / 40) + 1;
}

// Modificador segun el valor (efectivo) de la estadistica:
// 0-24 => 1, 25-49 => 2, 50-74 => 3, 75-99 => 4, 100+ => 5
function stat_modifier($stat) {
    $stat = intval($stat);
    if ($stat >= 100) return 5;
    if ($stat >= 75)  return 4;
    if ($stat >= 50)  return 3;
    if ($stat >= 25)  return 2;
    return 1;
}

// ── Estadisticas pasivas ──────────────────────────────────────────────
// Las columnas pas_* (pas_fuerza, pas_destreza, pas_cchakra, pas_inteligencia,
// pas_salud, pas_velocidad, pas_tenketsu, pas_sigilo) son bonificadores
// INVISIBLES que SOLO modifica el staff o un proceso automatico. Se suman a las
// estadisticas base para el calculo real de modificadores, vida, chakra y
// regeneracion de chakra. Los usuarios nunca las editan.
function sg_stat_keys() {
    return array('fuerza','destreza','cchakra','inteligencia','salud','velocidad','tenketsu','sigilo');
}

function sg_pasiva($row, $key) {
    $pk = 'pas_' . $key;
    return isset($row[$pk]) ? intval($row[$pk]) : 0;
}

// Devuelve un array con las estadisticas EFECTIVAS (base + pasiva), los
// modificadores recalculados y vida/chakra/regchakra recalculados a partir de
// dichas efectivas. No modifica $row.
function sg_stats_efectivas($row) {
    $e = array();
    foreach (sg_stat_keys() as $k) {
        $e[$k] = intval(isset($row[$k]) ? $row[$k] : 0) + sg_pasiva($row, $k);
    }
    $e['mfuerza']       = stat_modifier($e['fuerza']);
    $e['mdestreza']     = stat_modifier($e['destreza']);
    $e['mcchakra']      = stat_modifier($e['cchakra']);
    $e['minteligencia'] = stat_modifier($e['inteligencia']);
    $e['vida']      = calculate_vida2($e['fuerza'], $e['destreza'], $e['cchakra'], $e['inteligencia'], $e['salud'], $e['velocidad'], $e['tenketsu'], $e['sigilo']);
    $e['chakra']    = calculate_chakra2($e['fuerza'], $e['destreza'], $e['cchakra'], $e['inteligencia'], $e['salud'], $e['velocidad'], $e['tenketsu'], $e['sigilo']);
    $e['regchakra'] = $e['tenketsu'] * 4;
    return $e;
}

// Sobrescribe IN-PLACE los campos de visualizacion de una fila (ficha o snapshot
// mybb_sg_sg_thread_personaje) con los valores efectivos. Idempotente: no vuelve
// a sumar las pasivas si ya se aplicaron sobre la misma fila.
function sg_aplicar_pasivas(&$row) {
    if (!is_array($row) || !empty($row['_pasivas_aplicadas'])) { return; }
    foreach (sg_stats_efectivas($row) as $k => $v) { $row[$k] = $v; }
    $row['_pasivas_aplicadas'] = 1;
}

// Códigos [CCKx<N>] / [MCCKx<N>] / [DESx<N>] / [FUEx<N>] / [INTx<N>] en el
// campo "efecto" de una técnica (tecnicas_lista.php). $stats_ef = salida de
// sg_stats_efectivas() (o null si el viewer no tiene ficha -> se deja el
// código intacto en vez de un valor engañoso).
function sg_parsear_codigos_stats($texto, $stats_ef) {
    if ($texto === null || $texto === '') { return $texto; }

    $map_base = array('CCK' => 'cchakra', 'DES' => 'destreza', 'FUE' => 'fuerza', 'INT' => 'inteligencia');
    $map_mod  = array('CCK' => 'mcchakra', 'DES' => 'mdestreza', 'FUE' => 'mfuerza', 'INT' => 'minteligencia');
    $nombres  = array('CCK' => 'Control de Chakra', 'DES' => 'Destreza', 'FUE' => 'Fuerza', 'INT' => 'Inteligencia');
    $simbolos = array('x' => '×', '+' => '+', '-' => '−', '/' => '÷');

    // El 2º operando puede ser un número (grupo 4) o OTRA estadística
    // (grupos 5-6, ej. [MDES+MINT]).
    return preg_replace_callback(
        '#\[(M?)(CCK|DES|FUE|INT)([x+\-/])(?:(-?\d+(?:\.\d+)?)|(M?)(CCK|DES|FUE|INT))\]#i',
        function ($m) use ($map_base, $map_mod, $nombres, $simbolos, $stats_ef) {
            $es_mod1 = (strtoupper($m[1]) === 'M');
            $abrev1  = strtoupper($m[2]);
            $op      = $m[3];
            $col1    = $es_mod1 ? $map_mod[$abrev1] : $map_base[$abrev1];

            if ($stats_ef === null || !isset($stats_ef[$col1])) {
                return $m[0]; // sin ficha: deja el código tal cual
            }

            $base1 = $stats_ef[$col1];
            $codigo1 = ($es_mod1 ? 'M' : '') . $abrev1;
            $etiqueta1 = $nombres[$abrev1] . ($es_mod1 ? ' (modificador)' : '');
            $simbolo = isset($simbolos[$op]) ? $simbolos[$op] : $op;

            $es_stat2 = isset($m[6]) && $m[6] !== '';

            if ($es_stat2) {
                $es_mod2 = (strtoupper($m[5]) === 'M');
                $abrev2  = strtoupper($m[6]);
                $col2    = $es_mod2 ? $map_mod[$abrev2] : $map_base[$abrev2];

                if (!isset($stats_ef[$col2])) {
                    return $m[0];
                }

                $base2 = $stats_ef[$col2];
                $codigo_corto = $codigo1 . $op . (($es_mod2 ? 'M' : '') . $abrev2);
                $etiqueta = $etiqueta1 . ' ' . $simbolo . ' ' . $nombres[$abrev2] . ($es_mod2 ? ' (modificador)' : '');
            } else {
                $base2 = floatval($m[4]);
                $codigo_corto = $codigo1;
                $etiqueta = $etiqueta1 . ' ' . $simbolo . ' ' . $m[4];
            }

            switch ($op) {
                case '+': $valor = $base1 + $base2; break;
                case '-': $valor = $base1 - $base2; break;
                case '/': $valor = ($base2 != 0) ? ($base1 / $base2) : 0; break;
                default:  $valor = $base1 * $base2; break; // 'x'
            }

            // Enteros sin decimales; no enteros con 1 decimal y coma (ej. 1/2 -> "0,5").
            $valor_fmt = (floor($valor) == $valor) ? (string) intval($valor) : number_format($valor, 1, ',', '');

            return '<span class="sg-tec-parsed" title="' . htmlspecialchars($etiqueta, ENT_QUOTES) . '">' . $valor_fmt . ' (' . $codigo_corto . ')</span>';
        },
        $texto
    );
}

// Resuelve las estadísticas EFECTIVAS (base + pasivas) del AUTOR de un post,
// para tags/parsers que necesitan las stats de quien ESCRIBIÓ el post (no del
// visitante que lo lee). Prioriza el snapshot congelado del personaje en ese
// tema (mybb_sg_sg_thread_personaje) si existe -- mismo criterio que ya usa
// el tag [personaje] en inc/plugins/tecnicatag.php -- y si no, cae a la
// ficha viva actual (mybb_sg_sg_fichas).
//
// A propósito, es de SOLO LECTURA: a diferencia de [personaje], NO crea un
// snapshot si no existe (esa responsabilidad de "congelar" el personaje al
// primer post en el hilo sigue siendo exclusiva del tag [personaje]; este
// helper solo lee lo que ya haya, para no duplicar ese efecto secundario en
// cada lugar que necesite consultar stats).
//
// $post: array con al menos 'uid' y 'tid' (ej. la variable global $post de
// MyBB dentro de un hook de parseo de mensajes). Devuelve el array de stats
// efectivas, o null si no se pudo resolver ninguna ficha.
function sg_resolver_stats_autor_post($post) {
    global $db;
    $uid = isset($post['uid']) ? intval($post['uid']) : 0;
    $tid = isset($post['tid']) ? intval($post['tid']) : 0;
    if ($uid <= 0) { return null; }

    $thread_ficha = null;
    if ($tid > 0) {
        $q = $db->query("SELECT * FROM mybb_sg_sg_thread_personaje WHERE tid='$tid' AND uid='$uid'");
        while ($r = $db->fetch_array($q)) { $thread_ficha = $r; }
    }

    if ($thread_ficha) {
        sg_aplicar_pasivas($thread_ficha); // idempotente, mismo patrón que [personaje]
        return $thread_ficha;
    }

    $ficha = select_one_query_with_id('mybb_sg_sg_fichas', 'fid', $uid);
    if (!$ficha) { return null; }

    return sg_stats_efectivas($ficha);
}

// function select_queries_with_id($table_name, $field, $value) {
//     global $db;

//     $obj = null;

//     $query = $db->query("
//         SELECT * FROM $table_name WHERE $field='$value'
//     ");

//     $values = array();
    
//     while ($q = $db->fetch_array($query)) {
//         $array_push($values, $q);
//     }

//     return $values;
// }

function is_staff($uid) {
    // return ($uid == '196' || $uid == '2' || $uid == '320' || $uid == '155' || $uid == '181' || $uid == '129' || $uid == '178' || $uid == '239');
    return is_user($uid);
}

function is_peti_mod($uid) {
    return is_user($uid);
}

function is_mod($uid) {
    return is_user($uid);
}

function is_user($uid) {
    global $db;

    $has_staff_role = false;

    // $query = $db->query(" SELECT * FROM `mybb_users` WHERE uid='$uid' AND (usergroup = '14' OR additionalgroups LIKE '%14%' OR usergroup = '6' OR additionalgroups LIKE '%6%' OR usergroup = '4' OR additionalgroups LIKE '%4%'); ");
    $query = $db->query(" SELECT * FROM `mybb_sg_users` WHERE uid='$uid' AND (additionalgroups LIKE '3%' OR additionalgroups LIKE '%,3' OR additionalgroups LIKE '%,3,%' OR usergroup = '3' OR usergroup = '4'); ");
    while ($q = $db->fetch_array($query)) { $has_staff_role = true; }

    return $has_staff_role;
}

// Chequeo genérico de pertenencia a un grupo (usergroup principal o
// additionalgroups), mismo patrón LIKE que is_user() usa para el grupo 3.
function sg_usuario_en_grupo($uid, $gid) {
    global $db;
    $uid = (int) $uid;
    $gid = (int) $gid;

    $en_grupo = false;
    $query = $db->query("
        SELECT uid FROM `mybb_sg_users`
        WHERE uid='$uid' AND (
            usergroup = '$gid'
            OR additionalgroups LIKE '$gid,%'
            OR additionalgroups LIKE '%,$gid'
            OR additionalgroups LIKE '%,$gid,%'
            OR additionalgroups = '$gid'
        )
    ");
    while ($db->fetch_array($query)) { $en_grupo = true; }

    return $en_grupo;
}

function is_user2($uid) {
    // Muki: 239
    // Aiko: 129
    // Izuku: 181
    // Killua: 178
    // Ryu/Kano: 217/192
    // Freeia: 239, 251
    // Centyman: 241
    // Senshi 158 Kiseki 347
    // Izanami 204
    // Musacus 234
    // Toji Juri Gojo  - 155 302 306
    // Yagami 383
    // Akami 355 416
    // Namida y Karai - 
    // Kaito - 356

    // Kyoshiro 521
    // Hades 477
    return ($uid == '129' || 
       
        
        $uid == '206' || $uid == '158' || $uid == '347' ||
        $uid == '181' || $uid == '178' || $g_uid == '204' || $g_uid == '312' || $g_uid == '342' ||
        $uid == '239' || $uid == '251' || $uid == '241' || $uid == '341' || $uid == '204' ||
        $uid == '155' || $uid == '302' || $uid == '306' || $uid == '383' || $uid == '355' || $uid == '320' || $uid == '335' || $uid == '312' || 
        // Akami
        $uid == '355' || $uid == '416' ||
        // Taco
        $uid == '318' || $uid == '372' || $uid == '449' || 
        // Taco
        $uid == '356' || $uid == '521' || $uid == '477' ||
        $uid == '299' || $uid == '574' || $uid == '561' || $uid == '486' || $uid == '477'
    );
}

function convertObjectEffects($efectoJsonStr) {

    $efecto_json = json_decode($efectoJsonStr);

    $efectos_str = "";
    $efectos_arr = array("golpe", "clavar", "cortar", "0 a 2 metros", "2 a 4 metros");
    
    foreach ($efectos_arr as $efectoNombre) {
        $efe = $efecto_json->{$efectoNombre};
        if ($efe) {
            $base = $efe->base;
            $efectoCaps = ucfirst($efectoNombre);
            $efectoCaps = "<strong>$efectoCaps</strong>";
            $efectos_str .= "- $efectoCaps: ($base Base)";
            
            $fuerza = $efe->fuerza;
            $destreza = $efe->destreza;
            $texto = $efe->texto;
            if ($fuerza) {
                $efectos_str .= " + ($fuerza * Fuerza)";
            }
            if ($destreza) {
                $efectos_str .= " + ($destreza * Destreza)";
            }
            
            $efectos_str .= "<br>";
            
            if ($texto) {
                $efectos_str .= "<br>Extra: $texto<br>";
            }
    
        }
    }

    return $efectos_str;
}

function convertObjectEffectsUsuario($efectoJsonStr, $destrezaUsuario, $fuerzaUsuario) {
    $efecto_json = json_decode($efectoJsonStr);

    $efectos_str = "";
    $efectos_arr = array("golpe", "clavar", "cortar");
    
    foreach ($efectos_arr as $efectoNombre) {
        $efe = $efecto_json->{$efectoNombre};
        if ($efe) {
            $base = $efe->base;
            $efectoCaps = ucfirst($efectoNombre);
            $efectoCaps = "<strong>- $efectoCaps</strong>";
            $efectos_str .= "$efectoCaps: ";

            $totalVida = floatval($base);
            
            $fuerza = $efe->fuerza;
            $destreza = $efe->destreza;
            $texto = $efe->texto;
            if ($fuerza) {
                $totalVida += (floatval($fuerza) * floatval($fuerzaUsuario));
            }
            if ($destreza) {
                $totalVida += (floatval($destreza) * floatval($destrezaUsuario));
            }
            
            $efectos_str .= $totalVida;
            
            if ($texto) {
                $efectos_str .= "<br><strong>Extra</strong>: $texto";
            }
            $efectos_str .= "<br>";
        }
    }

    return $efectos_str;
}

function convertObjectEffectsTecnica($efectoJsonStr, $destrezaUsuario, $fuerzaUsuario) {
    $efecto_json = json_decode($efectoJsonStr);

    $efectos_str = "";
    $efectos_arr = array("golpe", "clavar", "cortar", "0 a 2 metros", "2 a 4 metros");
    // $efectos_arr = array("golpe", "clavar", "cortar");
    
    foreach ($efectos_arr as $efectoNombre) {
        $efe = $efecto_json->{$efectoNombre};
        if ($efe) {
            $base = $efe->base;
            $efectoCaps = ucfirst($efectoNombre);
            $efectoCaps = "$efectoCaps";
            $efectos_str .= "$efectoCaps: ";

            $totalVida = floatval($base);
            
            $fuerza = $efe->fuerza;
            $destreza = $efe->destreza;
            $texto = $efe->texto;
            if ($fuerza) {
                $totalVida += (floatval($fuerza) * floatval($fuerzaUsuario));
            }
            if ($destreza) {
                $totalVida += (floatval($destreza) * floatval($destrezaUsuario));
            }
            
            $efectos_str .= $totalVida . " de vida | ";
            
            if ($texto) {
                $efectos_str .= "<strong>Extra</strong>: $texto | ";
            }
        }

    }

    return substr($efectos_str, 0, -3) . ".";
}


// function select_one_query_with_id('mybb_sg_sg_fichas', 'fid', $uid);

/**
 * Construye la estructura de un árbol de técnicas desde mybb_sg_sg_tecnicas.
 *
 * Devuelve:
 *   [
 *     "base" => "<tid donde rama='base'>",
 *     "rama1" => [
 *        "base" => "<tid de la rama cuya categoria NO es mejora/especialidad>",
 *        "mejoras" => ["<tids categoria='mejora'>"],
 *        "especialidades" => ["<tids categoria='especialidad'>"]
 *     ],
 *     ...
 *   ]
 */
function sg_build_arbol($db, $arbol)
{
    $arbol_esc = $db->escape_string(strtolower(trim($arbol)));
    $res = array();

    $rows = array();
    $query = $db->query("
        SELECT tid, rama, categoria
        FROM mybb_sg_sg_tecnicas
        WHERE LOWER(TRIM(arbol)) = '$arbol_esc'
        ORDER BY tid
    ");
    while ($t = $db->fetch_array($query)) {
        $rows[] = $t;
    }

    // Base del árbol: rama='base'
    $res['base'] = null;
    foreach ($rows as $r) {
        if (strtolower(trim($r['rama'])) === 'base') {
            $res['base'] = $r['tid'];
            break;
        }
    }

    // Solo se consideran exactamente estas ramas (la base del árbol se maneja arriba)
    $ramas_permitidas = array('rama 1', 'rama 2', 'rama 3');
    $ramas = array();
    foreach ($rows as $r) {
        $rama = trim($r['rama']);
        if (!in_array(strtolower($rama), $ramas_permitidas, true)) {
            continue;
        }
        $ramas[strtolower($rama)][] = $r; // clave en minúscula ("rama 1"), unifica "RAMA 1"/"Rama 1"
    }

    uksort($ramas, 'strnatcasecmp'); // "rama 1", "rama 2", "rama 3"

    foreach ($ramas as $ramaName => $techs) {
        $key = $ramaName; // ya en minúscula, ej. "rama 1"

        $base = null;
        $mejoras = array();
        $especialidades = array();

        foreach ($techs as $t) {
            $cat = strtolower(trim($t['categoria']));
            if ($cat === 'mejora') {
                $mejoras[] = $t['tid'];
            } else if ($cat === 'especialidad') {
                $especialidades[] = $t['tid'];
            } else if ($base === null) {
                $base = $t['tid'];
            }
        }

        $res[$key] = array(
            'base' => $base,
            'mejoras' => $mejoras,
            'especialidades' => $especialidades,
        );
    }

    return $res;
}

/**
 * Igual que sg_build_arbol() pero devuelve solo el ESQUELETO:
 * mantiene la base del árbol y las ramas, pero con la base de rama vacía
 * y mejoras/especialidades vacías (para una ficha nueva que aún no desbloqueó nada).
 *
 *   { "base": "BUKI101B", "rama1": {"base":"","mejoras":[],"especialidades":[]}, ... }
 */
function sg_build_arbol_skeleton($db, $arbol)
{
    $full = sg_build_arbol($db, $arbol);

    $skel = array('base' => isset($full['base']) ? $full['base'] : null);
    foreach ($full as $key => $val) {
        if ($key === 'base') {
            continue;
        }
        // cada rama queda vacía
        $skel[$key] = array(
            'base' => '',
            'mejoras' => array(),
            'especialidades' => array(),
        );
    }

    return $skel;
}

/* =====================================================================
 * VIRTUDES/DEFECTOS DE DINERO (Adinerado I-III / Comprador Impulsivo I-III)
 * Mutuamente excluyentes entre sí. Se eligen una sola vez, al crear la
 * ficha (nueva_ficha.php), y afectan el saldo inicial de Ryos y el precio
 * de los objetos en tienda.php. Ver docs/virtudes_inserts.sql / defectos_inserts.sql.
 * ===================================================================== */
function sg_virtudes_dinero() {
    return array(
        'VADIN1' => array('ryos' =>  1000, 'descuento' =>  0.05),
        'VADIN2' => array('ryos' =>  2000, 'descuento' =>  0.10),
        'VADIN3' => array('ryos' =>  3000, 'descuento' =>  0.15),
        'DCOMP1' => array('ryos' => -1000, 'descuento' => -0.10),
        'DCOMP2' => array('ryos' => -2000, 'descuento' => -0.20),
        'DCOMP3' => array('ryos' => -3000, 'descuento' => -0.30),
    );
}

// Multiplicador de precio en tienda para una ficha (1.0 = sin cambio).
function sg_tienda_multiplicador($db, $uid) {
    $uid = (int) $uid;
    $dinero = sg_virtudes_dinero();
    $ids = "'" . implode("','", array_map(array($db, 'escape_string'), array_keys($dinero))) . "'";
    $q = $db->query("SELECT virtud_id FROM mybb_sg_sg_virtudes_usuarios WHERE uid='$uid' AND virtud_id IN ($ids) LIMIT 1");
    while ($r = $db->fetch_array($q)) {
        return 1 - $dinero[$r['virtud_id']]['descuento'];
    }
    return 1.0;
}

/* =====================================================================
 * INCOMPATIBILIDADES DE VIRTUDES/DEFECTOS (ver docs/virtudes_defectos.txt)
 * Mapa SIMÉTRICO virtud_id => [ids incompatibles]. Se usa para bloquear
 * combinaciones en el selector de creación de ficha (cliente y servidor).
 * ===================================================================== */
function sg_virtudes_incompatibilidades() {
    static $mapa = null;
    if ($mapa !== null) {
        return $mapa;
    }

    // Grupos: cada miembro es incompatible con TODOS los demás del grupo.
    $grupos = array(
        array('VADIN1', 'VADIN2', 'VADIN3', 'DCOMP1', 'DCOMP2', 'DCOMP3'), // dinero
        array('VPINF1', 'VPINF2', 'DPOSC1', 'DPOSC2'),                     // pasado familiar
        array('DALER1', 'DALER2', 'DALER3'),                               // alergias
        array('DFOBI1', 'DFOBI2', 'DFOBI3'),                               // fobias
    );
    // Pares sueltos.
    $pares = array(
        array('VATRAC', 'DFEURA'), // Atractivo / Feúra
        array('VPROD1', 'VPROD2'), // Ninja Prodigio I / II
        array('VFAMA',  'DIMPOP'), // Fama / Impopularidad
        array('VINSUP', 'DVACIL'), // Instinto de Supervivencia / Voluntad Vacilante
        array('VCONT1', 'VCONT2'), // Contactos I / II
        array('VSENS1', 'VSENS2'), // Capacidad Sensorial I / II
        array('VRDIF1', 'VRDIF2'), // Rasgo Diferente I / II
        array('VSANRE', 'DSDILU'), // Sangre Resistente / Sangre Diluida
        array('DDESHO', 'DLEALT'), // Deshonor / Lealtad
        array('DVERBO', 'DMUDEZ'), // Verborrea Shinobi / Mudez
        array('DVERBO', 'DSILEN'), // Verborrea Shinobi / Voto de Silencio
    );

    $mapa = array();
    $add = function ($a, $b) use (&$mapa) {
        if ($a === $b) { return; }
        if (!isset($mapa[$a])) { $mapa[$a] = array(); }
        if (!in_array($b, $mapa[$a], true)) { $mapa[$a][] = $b; }
    };
    foreach ($grupos as $g) {
        foreach ($g as $a) {
            foreach ($g as $b) { $add($a, $b); }
        }
    }
    foreach ($pares as $p) {
        $add($p[0], $p[1]);
        $add($p[1], $p[0]);
    }
    return $mapa;
}

// Devuelve el primer par [a, b] incompatible dentro de un conjunto de ids,
// o null si no hay conflicto.
function sg_virtudes_conflicto($vids) {
    $mapa = sg_virtudes_incompatibilidades();
    $set  = array_flip($vids);
    foreach ($vids as $vid) {
        if (!isset($mapa[$vid])) { continue; }
        foreach ($mapa[$vid] as $inc) {
            if (isset($set[$inc])) {
                return array($vid, $inc);
            }
        }
    }
    return null;
}

/* =====================================================================
 * DOJO SHINOBI — ver docs/arboles_instruciones.txt
 * ===================================================================== */

// Árboles fijos que todo personaje tiene desde la creación de la ficha.
function sg_arboles_fijos() {
    return array('bukijutsu', 'defensivo', 'resistencia', 'taijutsu');
}

// Elementos NATURALES: solo se desbloquean por ruleta (gastan 1 slot_elementales).
function sg_arboles_naturales() {
    return array('katon', 'fuuton', 'suiton', 'doton', 'raiton');
}

// Elementos de SELECCIÓN DIRECTA en el Dojo (yin / yang).
function sg_arboles_directos() {
    return array('yin', 'yang');
}

// Todos los árboles elementales (naturales + directos).
function sg_arboles_elementales() {
    return array_merge(sg_arboles_naturales(), sg_arboles_directos());
}

// Valores por defecto de mybb_sg_sg_fichas.arboles_progreso.
function sg_progreso_defaults() {
    return array(
        'desbloqueo_arboles'     => 0,
        'desbloqueo_ramas'       => 0,
        'desbloqueo_nivel_ramas' => 0,
        'nivel_rama_disponibles' => 0,
        'clan_rama_usada'        => 0,
    );
}

// Decodifica arboles_progreso garantizando todas las claves.
function sg_progreso_parse($json) {
    $defaults = sg_progreso_defaults();
    $data = json_decode((string) $json, true);
    if (!is_array($data)) {
        $data = array();
    }
    foreach ($defaults as $k => $v) {
        $data[$k] = isset($data[$k]) ? (int) $data[$k] : $v;
    }
    return $data;
}

// Costos actuales (en Tobis) según los contadores de progreso.
function sg_dojo_costos($progreso) {
    return array(
        'arbol' => 15 + (10 * (int) $progreso['desbloqueo_arboles']),
        'rama'  => 10 + (5  * (int) $progreso['desbloqueo_ramas']),
        'nivel' => 5  + (2  * (int) $progreso['desbloqueo_nivel_ramas']),
    );
}

// Nivel del personaje requerido para desbloquear un árbol (feature A).
// 1º árbol -> nivel 1 (cuesta 15 Tobis); luego 2º->5, 3º->10, 4º->15, 5º->20.
// El 6º (solo con Ninja Prodigio II) también requiere nivel 20 (tope del juego).
function sg_dojo_nivel_requerido_arbol($desbloqueo_arboles) {
    $d = (int) $desbloqueo_arboles;
    return $d <= 0 ? 1 : min(5 * $d, 20);
}

// Requisito elemental por CLAN (cid del clan principal). Antes de la ruleta normal,
// ciertos clanes obligan a obtener un set de elementos naturales:
//   'elegir' -> se eligen a dedo todos los del set (sin tirada).
//   'ruleta' -> salen por tirada restringida SOLO a ese set.
// En ambos, tras completar el set se pasa a la ruleta normal (pool completo).
// Mismo coste/requisitos que la ruleta (1 slot + Tobis + nivel).
function sg_clan_elementos() {
    return array(
        102 => array('modo' => 'ruleta', 'req' => array('doton', 'suiton')),          // Senju
        106 => array('modo' => 'elegir', 'req' => array('katon')),                    // Uchiha
        110 => array('modo' => 'elegir', 'req' => array('katon', 'fuuton', 'doton')), // Sarutobi
        305 => array('modo' => 'elegir', 'req' => array('suiton')),                   // Hoshigaki
        304 => array('modo' => 'elegir', 'req' => array('suiton')),                   // Hozuki
        309 => array('modo' => 'elegir', 'req' => array('suiton')),                   // Funato
        307 => array('modo' => 'elegir', 'req' => array('suiton', 'katon', 'doton')), // Terumi
        301 => array('modo' => 'ruleta', 'req' => array('suiton', 'fuuton')),         // Yuki
    );
}

/**
 * Catálogo de árboles cacheado (datacache de MyBB, clave 'sg_arboles').
 * Estructura: catalogo[arbol] = sg_build_arbol() (base + rama 1/2/3 con
 * base/mejoras/especialidades). Es estático: solo cambia al editar técnicas.
 */
function sg_get_catalogo_arboles($db, $force = false) {
    global $cache;

    if (!$force && is_object($cache)) {
        $cached = $cache->read('sg_arboles');
        if (is_array($cached) && !empty($cached)) {
            return $cached;
        }
    }

    return sg_rebuild_catalogo_arboles($db);
}

// Reconstruye y guarda la cache del catálogo. Llamar al crear/editar técnicas.
function sg_rebuild_catalogo_arboles($db) {
    global $cache;

    $nombres = array();
    $query = $db->query("
        SELECT DISTINCT LOWER(TRIM(arbol)) AS arbol
        FROM mybb_sg_sg_tecnicas
        WHERE TRIM(arbol) <> ''
        ORDER BY arbol
    ");
    while ($r = $db->fetch_array($query)) {
        $nombres[] = $r['arbol'];
    }

    $catalogo = array();
    foreach ($nombres as $a) {
        $catalogo[$a] = sg_build_arbol($db, $a);
    }

    if (is_object($cache)) {
        $cache->update('sg_arboles', $catalogo);
    }

    return $catalogo;
}

// Conjunto de tids que el usuario posee (tec_aprendidas), como mapa tid=>true.
function sg_owned_tecnicas($db, $uid) {
    $uid = (int) $uid;
    $owned = array();
    $query = $db->query("SELECT tid FROM mybb_sg_sg_tec_aprendidas WHERE uid='$uid'");
    while ($r = $db->fetch_array($query)) {
        // Clave en MAYÚSCULAS: el cruce con el catálogo es insensible a mayúsculas
        // (los tid se teclean a mano y a veces varían: "SARU101B" vs "Saru101B").
        $owned[strtoupper($r['tid'])] = true;
    }
    return $owned;
}

// Devuelve solo las claves de rama ("rama 1/2/3") de un árbol del catálogo.
function sg_ramas_de_arbol($arbol_cat) {
    $ramas = array();
    foreach ($arbol_cat as $key => $val) {
        if ($key === 'base') {
            continue;
        }
        $ramas[$key] = $val;
    }
    return $ramas;
}

/**
 * Estado COMPLETO del Dojo para un usuario, derivado de tec_aprendidas + catálogo.
 * Lo usan tanto el render de la página como la validación del endpoint.
 * Ver docs/arboles_instruciones.txt secciones 6 y 10.
 */
function sg_dojo_estado($db, $uid) {
    $uid = (int) $uid;

    $ficha = select_one_query_with_id('mybb_sg_sg_fichas', 'fid', $uid);
    if ($ficha === null) {
        return null;
    }

    $progreso = sg_progreso_parse(isset($ficha['arboles_progreso']) ? $ficha['arboles_progreso'] : '');
    $tobi     = (int) (isset($ficha['tobi']) ? $ficha['tobi'] : 0);
    $nivel    = (int) (isset($ficha['nivel']) ? $ficha['nivel'] : 0);
    $slot_elementales = isset($ficha['slot_elementales']) ? (int) $ficha['slot_elementales'] : 5;
    $costos   = sg_dojo_costos($progreso);

    $owned    = sg_owned_tecnicas($db, $uid);
    $catalogo = sg_get_catalogo_arboles($db);

    $fijos       = sg_arboles_fijos();
    $elementales = sg_arboles_elementales();

    // Virtudes/defectos del usuario que afectan al Dojo (ver docs/virtudes_defectos.txt).
    $virt = array();
    $qv = $db->query("SELECT virtud_id FROM mybb_sg_sg_virtudes_usuarios WHERE uid='$uid'");
    while ($rv = $db->fetch_array($qv)) { $virt[$rv['virtud_id']] = true; }
    $sin_yin      = isset($virt['DINYIN']); // Incompatibilidad Yin: sin árbol Yin
    $sin_yang     = isset($virt['DINYAN']); // Incompatibilidad Yang: sin árbol Yang
    $sin_elemento = isset($virt['DINELE']); // Incompatibilidad Elemental: sin ruleta natural
    $afinidad     = isset($virt['VAFIEL']); // Afinidad Elemental: elige el 1er elemento (sin tirada)
    $prodigio1    = isset($virt['VPROD1']); // Ninja Prodigio I: +1 especialidad (una vez)
    $prodigio2    = isset($virt['VPROD2']); // Ninja Prodigio II: 6º árbol

    // Tope de árboles elementales/directos desbloqueables: 5 (6 con Ninja Prodigio II).
    // El 6º solo llega tras elegir los 5 (desbloqueo_arboles == 5) y nivel 20.
    $max_arboles  = $prodigio2 ? 6 : 5;
    $tope_arboles = ((int) $progreso['desbloqueo_arboles'] >= $max_arboles);

    // Árboles que el personaje posee (posee la base del árbol).
    $poseidos = array();
    foreach ($catalogo as $arbol => $cat) {
        $base = isset($cat['base']) ? $cat['base'] : null;
        if ($base !== null && isset($owned[strtoupper($base)])) {
            $poseidos[] = $arbol;
        }
    }

    // Árboles de clan = poseídos que no son ni fijos ni elementales.
    // Normalmente 1; con Clan Híbrido (VHIBRI + 2ª base de clan concedida) hay 2.
    // Ver docs/virtudes_defectos.txt (VHIBRI). El campo mybb_sg_sg_fichas.clan2 es
    // solo para el display de la ficha; el dojo deriva todo de tec_aprendidas.
    $clan_arboles = array();
    foreach ($poseidos as $arbol) {
        if (!in_array($arbol, $fijos, true) && !in_array($arbol, $elementales, true)) {
            $clan_arboles[] = $arbol;
        }
    }
    $clan_arbol = !empty($clan_arboles) ? $clan_arboles[0] : null; // compat (primer clan)
    $es_hibrido = (count($clan_arboles) >= 2);

    // Detalle por árbol poseído.
    $arboles = array();
    $prodigio1_over = false; // ¿algún árbol ya excede su cupo base de especialidades?
    foreach ($poseidos as $arbol) {
        $cat = $catalogo[$arbol];
        $ramas_cat = sg_ramas_de_arbol($cat);

        $ramas_out = array();
        $nivel_arbol = 0;
        $espec_pool = array();
        $espec_aprendidas = 0;

        foreach ($ramas_cat as $rama => $rinfo) {
            $rama_base = isset($rinfo['base']) ? $rinfo['base'] : '';
            $desbloqueada = ($rama_base !== '' && $rama_base !== null && isset($owned[strtoupper($rama_base)]));

            $mejoras_cat   = isset($rinfo['mejoras']) ? $rinfo['mejoras'] : array();
            $mejoras_libres = array();
            $mejoras_owned  = 0;
            foreach ($mejoras_cat as $mtid) {
                if (isset($owned[strtoupper($mtid)])) {
                    $mejoras_owned++;
                } else {
                    $mejoras_libres[] = $mtid;
                }
            }
            $nivel_rama = intval($mejoras_owned / 2);
            $nivel_arbol += $nivel_rama;

            // Especialidades de esta rama -> al pool del árbol.
            $espec_cat = isset($rinfo['especialidades']) ? $rinfo['especialidades'] : array();
            foreach ($espec_cat as $etid) {
                $espec_pool[] = $etid;
                if (isset($owned[strtoupper($etid)])) {
                    $espec_aprendidas++;
                }
            }

            $ramas_out[$rama] = array(
                'base'           => $rama_base,
                'desbloqueada'   => $desbloqueada,
                'desbloqueable'  => (!$desbloqueada && $rama_base !== '' && $rama_base !== null),
                'nivel'          => $nivel_rama,
                'mejoras_libres' => array_values($mejoras_libres),
                'puede_subir'    => ($desbloqueada && count($mejoras_libres) >= 2),
            );
        }

        // Especializaciones: cupo por nivel de árbol (3/6/9) menos lo aprendido.
        $cupo = min(intval($nivel_arbol / 3), 3);
        $espec_libres = array(); // del pool, aún no aprendidas
        foreach ($espec_pool as $etid) {
            if (!isset($owned[strtoupper($etid)])) { $espec_libres[] = $etid; }
        }
        $espec_libres = array_values(array_unique($espec_libres));
        $espec_elegibles = ($espec_aprendidas < $cupo) ? $espec_libres : array();
        // Ninja Prodigio I ya usado si algún árbol excede su cupo base.
        if ($espec_aprendidas > $cupo) { $prodigio1_over = true; }

        // Rama gratis por árbol comprado: cada árbol ELEMENTAL (naturales + directos)
        // regala su PRIMERA rama. Solo disponible si aún no tiene NINGUNA rama
        // desbloqueada; en cuanto se desbloquea una (gratis o con Tobis) deja de
        // aplicar, y no sube el costo de ramas. Los fijos ya vienen con sus ramas.
        $rama_gratis_disponible = false;
        if (in_array($arbol, $elementales, true)) {
            $ramas_desbloqueadas = 0;
            $tiene_desbloqueable = false;
            foreach ($ramas_out as $r) {
                if (!empty($r['desbloqueada']))  { $ramas_desbloqueadas++; }
                if (!empty($r['desbloqueable'])) { $tiene_desbloqueable = true; }
            }
            $rama_gratis_disponible = ($ramas_desbloqueadas === 0 && $tiene_desbloqueable);
        }

        $arboles[$arbol] = array(
            'nivel_arbol' => $nivel_arbol,
            'rama_gratis_disponible' => $rama_gratis_disponible,
            'ramas'       => $ramas_out,
            'especializaciones' => array(
                'cupo'       => $cupo,
                'aprendidas' => $espec_aprendidas,
                'elegibles'  => $espec_elegibles,
                'libres'     => $espec_libres,
            ),
        );
    }

    // ── Clan Híbrido: topes COMBINADOS entre los dos árboles de clan ──
    // Los dos árboles de clan cuentan, para los límites, como si fueran UN
    // solo clan: 3 ramas y 3 especialidades en total (igual que un clan normal).
    // Solo aplica a híbridos; con un solo clan el comportamiento no cambia.
    $CLAN_RAMAS_MAX = 3;
    $clan_ramas_desbloqueadas = 0;
    $clan_espec_aprendidas    = 0;
    foreach ($clan_arboles as $arbol) {
        if (!isset($arboles[$arbol])) { continue; }
        foreach ($arboles[$arbol]['ramas'] as $r) {
            if (!empty($r['desbloqueada'])) { $clan_ramas_desbloqueadas++; }
        }
        $clan_espec_aprendidas += (int) $arboles[$arbol]['especializaciones']['aprendidas'];
    }

    // ── Ninja Prodigio I: +1 especialidad, una sola vez ──
    // "Usado" si algún árbol excede su cupo base, o (híbrido) si el pool de clan
    // pasa de 3. Mientras esté disponible, sube en 1 el tope aplicable.
    $prodigio1_usado = $prodigio1_over || ($es_hibrido && $clan_espec_aprendidas > 3);
    $prodigio1_disponible = ($prodigio1 && !$prodigio1_usado);
    $CLAN_ESPEC_MAX = 3 + (($es_hibrido && $prodigio1_disponible) ? 1 : 0);

    // Bump del +1 en árboles NO-clan-híbrido que ya estén al máximo (cupo 3).
    // (Los árboles de clan de un híbrido se manejan por el tope compartido.)
    if ($prodigio1_disponible) {
        foreach ($arboles as $arbol_p => $ainfo_p) {
            if ($es_hibrido && in_array($arbol_p, $clan_arboles, true)) { continue; }
            $ep =& $arboles[$arbol_p]['especializaciones'];
            if ((int) $ep['cupo'] >= 3 && (int) $ep['aprendidas'] >= (int) $ep['cupo'] && !empty($ep['libres'])) {
                $ep['cupo']      = (int) $ep['cupo'] + 1;
                $ep['elegibles'] = $ep['libres'];
            }
            unset($ep);
        }
    }

    if ($es_hibrido) {
        $ramas_full = ($clan_ramas_desbloqueadas >= $CLAN_RAMAS_MAX);
        $espec_full = ($clan_espec_aprendidas >= $CLAN_ESPEC_MAX);
        $restante_espec = max(0, $CLAN_ESPEC_MAX - $clan_espec_aprendidas);
        foreach ($clan_arboles as $arbol) {
            if (!isset($arboles[$arbol])) { continue; }
            // Ramas: alcanzado el tope combinado, ninguna rama de clan más es desbloqueable.
            if ($ramas_full) {
                foreach ($arboles[$arbol]['ramas'] as $rama => $r) {
                    if (empty($r['desbloqueada'])) {
                        $arboles[$arbol]['ramas'][$rama]['desbloqueable'] = false;
                    }
                }
            }
            // Especialidades: cupo por nivel como hoy, pero el TOTAL aprendido se
            // capa en 3 entre ambos; sin restante global, no hay elegibles.
            $esp =& $arboles[$arbol]['especializaciones'];
            $esp['cupo'] = min((int) $esp['cupo'], (int) $esp['aprendidas'] + $restante_espec);
            if ($espec_full) {
                $esp['elegibles'] = array();
            }
            unset($esp);
        }
    }

    // Elementos de selección directa (yin/yang) adquiribles.
    // Incompatibilidad Yin/Yang bloquea ese árbol; el tope de árboles también.
    $directos = array();
    if (!$tope_arboles) {
        foreach (sg_arboles_directos() as $el) {
            if ($el === 'yin' && $sin_yin) { continue; }
            if ($el === 'yang' && $sin_yang) { continue; }
            if (isset($catalogo[$el]) && !in_array($el, $poseidos, true)) {
                $directos[] = $el;
            }
        }
    }

    // Elementos naturales aún bloqueados (pool de la ruleta) y cuántos ya posee.
    $naturales_bloqueados = array();
    $naturales_poseidos   = 0;
    foreach (sg_arboles_naturales() as $el) {
        if (!isset($catalogo[$el])) { continue; }
        if (in_array($el, $poseidos, true)) { $naturales_poseidos++; }
        else { $naturales_bloqueados[] = $el; }
    }

    // ── Requisito elemental por CLAN principal ──
    // Mientras el set del clan no esté completo, la adquisición de elementos se
    // restringe a ese set (por elección forzada o por ruleta restringida). Al
    // completarlo se pasa a la ruleta normal (pool completo).
    $clan_cid    = (int) (isset($ficha['clan']) ? $ficha['clan'] : 0);
    $clanes_elem = sg_clan_elementos();
    $clan_req    = isset($clanes_elem[$clan_cid]) ? $clanes_elem[$clan_cid] : null;

    $elem_pool         = $naturales_bloqueados; // pool efectivo (elegir/tirar)
    $forzar_eleccion   = false;                 // el clan obliga a elegir a dedo
    $clan_fase_activa  = false;                 // el set del clan aún no está completo
    if ($clan_req !== null) {
        $completa = true;
        $pend = array();
        foreach ($clan_req['req'] as $e) {
            if (!in_array($e, $poseidos, true)) { $completa = false; }
            if (in_array($e, $naturales_bloqueados, true)) { $pend[] = $e; }
        }
        if (!$completa) {
            $clan_fase_activa = true;
            $elem_pool = $pend;
            if ($clan_req['modo'] === 'elegir') { $forzar_eleccion = true; }
        }
    }

    // ¿Cumple el requisito para desbloquear un árbol? (nivel + Tobis; respeta el tope)
    $nivel_req = sg_dojo_nivel_requerido_arbol($progreso['desbloqueo_arboles']);
    $puede_desbloquear_arbol = (!$tope_arboles && $nivel >= $nivel_req && $tobi >= $costos['arbol']);

    // Condición base para obtener un elemento (por elección o por tirada).
    $elem_base_ok = (!$sin_elemento && !$tope_arboles && $slot_elementales > 0 && count($elem_pool) > 0 && $puede_desbloquear_arbol);

    // Razón cuando no se puede (Incompatibilidad Elemental la deshabilita).
    $ruleta_razon = null;
    if ($sin_elemento) {
        $ruleta_razon = 'Tienes Incompatibilidad Elemental: no puedes obtener elementos naturales.';
    } else if (count($naturales_bloqueados) === 0) {
        $ruleta_razon = 'Ya desbloqueaste todos los elementos naturales.';
    } else if ($tope_arboles) {
        $ruleta_razon = 'Alcanzaste el máximo de árboles.';
    } else if ($slot_elementales <= 0) {
        $ruleta_razon = 'No te quedan slots elementales.';
    } else if (!$puede_desbloquear_arbol) {
        // Detalla QUÉ falta (nivel y/o Tobis) usando los valores ya calculados.
        $faltan = array();
        if ($nivel < $nivel_req) { $faltan[] = 'Nivel ' . $nivel_req . ' (tienes ' . (int) $nivel . ')'; }
        if ($tobi < $costos['arbol']) { $faltan[] = $costos['arbol'] . ' Tobis (tienes ' . (int) $tobi . ')'; }
        $ruleta_razon = !empty($faltan)
            ? 'Para desbloquear tu próximo árbol necesitas ' . implode(' y ', $faltan) . '.'
            : 'Aún no cumples el requisito para desbloquear un árbol.';
    }

    // Elegir sin tirada: el clan lo fuerza ('elegir'), o Afinidad Elemental para el 1º.
    $puede_elegir = ($elem_base_ok && ($forzar_eleccion || ($afinidad && $naturales_poseidos === 0)));
    // Tirada aleatoria: disponible salvo que el clan obligue a elegir.
    $ruleta_disponible = ($elem_base_ok && !$forzar_eleccion);
    $ruleta = array(
        'disponible'          => $ruleta_disponible,
        'puede_elegir'        => $puede_elegir,
        'pool'                => array_values($elem_pool),
        'restringido'         => $clan_fase_activa,
        'slots'               => $slot_elementales,
        'naturales_restantes' => count($naturales_bloqueados),
        'pool_total'          => sg_arboles_naturales(),
        'razon'               => $ruleta_razon,
    );

    // ¿Hay rama de clan gratis disponible? (sirve para cualquiera de los árboles
    // de clan y cuenta dentro del tope de 3 ramas del híbrido).
    $clan_rama_disponible = false;
    if ((int) $progreso['clan_rama_usada'] === 0) {
        foreach ($clan_arboles as $arbol) {
            if (!isset($arboles[$arbol])) { continue; }
            foreach ($arboles[$arbol]['ramas'] as $r) {
                if (!empty($r['desbloqueable'])) { $clan_rama_disponible = true; break 2; }
            }
        }
    }

    return array(
        'uid'      => $uid,
        'tobi'     => $tobi,
        'nivel'    => $nivel,
        'slot_elementales'    => $slot_elementales,
        'progreso' => $progreso,
        'costos'   => $costos,
        'adquiribles_directos' => $directos,
        'naturales_bloqueados' => $naturales_bloqueados,
        'ruleta'   => $ruleta,
        'arboles'  => $arboles,
        'clan'     => array(
            'arbol'                  => $clan_arbol,     // primer clan (compat)
            'arboles'                => $clan_arboles,   // todos los árboles de clan
            'es_hibrido'             => $es_hibrido,
            'rama_gratis_disponible' => $clan_rama_disponible,
            'ramas_desbloqueadas'    => $clan_ramas_desbloqueadas,
            'ramas_max'              => $CLAN_RAMAS_MAX,
            'espec_aprendidas'       => $clan_espec_aprendidas,
            'espec_max'              => $CLAN_ESPEC_MAX,
        ),
        // Efectos de virtudes/defectos en el Dojo (para avisos en la UI).
        'restricciones' => array(
            'sin_yin'               => $sin_yin,
            'sin_yang'              => $sin_yang,
            'sin_elemento'          => $sin_elemento,
            'afinidad'              => $afinidad,
            'prodigio1'             => $prodigio1,
            'prodigio1_disponible'  => $prodigio1_disponible,
            'prodigio2'             => $prodigio2,
            'max_arboles'           => $max_arboles,
            'arboles_desbloqueados' => (int) $progreso['desbloqueo_arboles'],
        ),
    );
}

// Color de acento por afiliación de NPC (bingo book). Data-driven: agregar aldeas aquí.
function sg_npc_afiliacion_color($afiliacion) {
    $a = strtolower(trim($afiliacion));
    $map = array(
        'konoha' => '#c0582a', // rojo / óxido
        'kiri'   => '#3a8fb0', // azul / turquesa
    );
    return isset($map[$a]) ? $map[$a] : '#7b4ab8'; // plum por defecto
}

// Inserta una técnica aprendida (idempotente). Loguea en historial solo si
// realmente se agregó (INSERT IGNORE que sí insertó). Usa `global $db`.
function sg_dojo_aprender($uid, $tid, $tipo, $origen, $detalle = '', $grupo = null, $actor_uid = null) {
    global $db;
    $uid = (int) $uid;
    $tid_esc = $db->escape_string($tid);
    $db->query("INSERT IGNORE INTO mybb_sg_sg_tec_aprendidas (tid, uid) VALUES ('$tid_esc','$uid')");
    if ($db->affected_rows() > 0) {
        sg_historial_tecnica_log($uid, $tid, 'aprender', $tipo, $origen, $detalle, $grupo, $actor_uid);
    }
}

// Persiste tobi + arboles_progreso (y opcionalmente slot_elementales) para una ficha.
// El gasto de tobi (gasto de usuario en el dojo) se loguea vía sg_ficha_set_campo
// solo si realmente cambió; arboles_progreso/slot son estado interno derivado y se
// persisten directo, sin loguear. (El tobi queda como fila suelta, no comparte grupo
// con la técnica aprendida en la misma acción — refinamiento pendiente.)
function sg_dojo_guardar($db, $uid, $tobi, $progreso, $slot = null) {
    $uid = (int) $uid;
    $tobi = (int) $tobi;

    sg_ficha_set_campo($uid, 'tobi', $tobi, 'usuario', SG_ORIGEN_DOJO, 'Gasto en el dojo');

    $prog_json = $db->escape_string(json_encode($progreso, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $set = "arboles_progreso='$prog_json'";
    if ($slot !== null) {
        $set .= ", slot_elementales='" . (int) $slot . "'";
    }
    $db->query("UPDATE mybb_sg_sg_fichas SET $set WHERE fid='$uid'");
}

/**
 * Aplica la economía de "desbloquear un árbol" (feature A): SIEMPRE cuesta Tobis.
 * Requiere nivel >= nivel_requerido(desbloqueo_arboles) y Tobis suficientes.
 * Muta y devuelve progreso/tobi. NO escribe en BD ni aprende la técnica.
 * Devuelve ['ok'=>true,'tobi'=>int,'progreso'=>array] o ['ok'=>false,'msg'=>string].
 */
function sg_dojo_pagar_arbol($progreso, $tobi, $nivel, $costos) {
    $nivel_req = sg_dojo_nivel_requerido_arbol($progreso['desbloqueo_arboles']);
    if ($nivel < $nivel_req) {
        return array('ok' => false, 'msg' => "Necesitas nivel $nivel_req para desbloquear otro árbol.");
    }
    if ($tobi < $costos['arbol']) {
        return array('ok' => false, 'msg' => "Tobis insuficientes: necesitas {$costos['arbol']}.");
    }
    $tobi -= $costos['arbol'];
    $progreso['desbloqueo_arboles']++;
    return array('ok' => true, 'tobi' => $tobi, 'progreso' => $progreso);
}

/**
 * Aplica una acción del Dojo de forma atómica y validada en el servidor.
 * Ver docs/arboles_instruciones.txt secciones 7, 11 y 12.
 *
 * $action ∈ { arbol, rama, nivel, especializacion, rama_clan }
 * $params depende de la acción:
 *   arbol           -> ['arbol' => '<elemental>']
 *   rama            -> ['arbol' => '<arbol>', 'rama' => 'rama N']
 *   nivel           -> ['arbol' => '<arbol>', 'rama' => 'rama N', 'mejoras' => [tid, tid]]
 *   especializacion -> ['arbol' => '<arbol>', 'tid' => '<tid>']
 *   rama_clan       -> ['rama' => 'rama N']
 *
 * Devuelve ['ok' => bool, 'tipo' => 'ok'|'error', 'msg' => string].
 */
function sg_dojo_aplicar_accion($db, $uid, $action, $params) {
    $uid = (int) $uid;
    $err = function ($m) { return array('ok' => false, 'tipo' => 'error', 'msg' => $m); };
    $ok  = function ($m) { return array('ok' => true,  'tipo' => 'ok',    'msg' => $m); };

    $lock_name = "sg_dojo_$uid";
    $got_lock = 0;
    $rl = $db->query("SELECT GET_LOCK('$lock_name', 5) AS l");
    while ($r = $db->fetch_array($rl)) {
        $got_lock = intval($r['l']);
    }
    if ($got_lock !== 1) {
        return $err("No se pudo procesar la acción en este momento. Intenta de nuevo.");
    }

    $result = null;
    // Estado y catálogo FRESCOS dentro del lock.
    $estado   = sg_dojo_estado($db, $uid);
    $catalogo = sg_get_catalogo_arboles($db);

    if ($estado === null) {
        $result = $err("No existe la ficha.");
    } else {
        $progreso = $estado['progreso'];
        $tobi     = (int) $estado['tobi'];
        $nivel    = (int) $estado['nivel'];
        $costos   = $estado['costos'];

        $arbol = isset($params['arbol']) ? strtolower(trim($params['arbol'])) : '';
        $rama  = isset($params['rama'])  ? strtolower(trim($params['rama']))  : '';

        switch ($action) {

            // ── (A1) Desbloquear un árbol DIRECTO (yin / yang) ──────
            case 'arbol':
                if (!in_array($arbol, $estado['adquiribles_directos'], true)) {
                    $result = $err("Ese árbol no está disponible para adquirir.");
                    break;
                }
                $base_tid = isset($catalogo[$arbol]['base']) ? $catalogo[$arbol]['base'] : null;
                if ($base_tid === null) {
                    $result = $err("Ese árbol no tiene técnica base configurada.");
                    break;
                }
                $pago = sg_dojo_pagar_arbol($progreso, $tobi, $nivel, $costos);
                if (!$pago['ok']) {
                    $result = $err($pago['msg']);
                    break;
                }
                $progreso = $pago['progreso'];
                $tobi = $pago['tobi'];
                sg_dojo_aprender($uid, $base_tid, 'usuario', SG_ORIGEN_DOJO);
                sg_dojo_guardar($db, $uid, $tobi, $progreso);
                $result = $ok("Desbloqueaste el árbol " . ucfirst($arbol) . " por {$costos['arbol']} Tobis.");
                break;

            // ── (A2) Ruleta elemental (natural, aleatorio + 1 slot) ─
            case 'ruleta':
                // ruleta.disponible ya integra: Incompatibilidad Elemental, tope de
                // árboles, slots, pool restante y requisito de nivel + Tobis.
                if (empty($estado['ruleta']['disponible'])) {
                    $result = $err(!empty($estado['ruleta']['razon']) ? $estado['ruleta']['razon'] : "La ruleta no está disponible.");
                    break;
                }
                $slots = (int) $estado['slot_elementales'];
                $pool  = $estado['ruleta']['pool']; // restringido al set del clan si aplica
                if (empty($pool)) {
                    $result = $err("No hay elementos disponibles para la tirada.");
                    break;
                }
                $pago = sg_dojo_pagar_arbol($progreso, $tobi, $nivel, $costos);
                if (!$pago['ok']) {
                    $result = $err($pago['msg']);
                    break;
                }
                $progreso = $pago['progreso'];
                $tobi = $pago['tobi'];

                // Resultado ALEATORIO entre los naturales aún bloqueados.
                $elegido = $pool[array_rand($pool)];
                $base_tid = isset($catalogo[$elegido]['base']) ? $catalogo[$elegido]['base'] : null;
                if ($base_tid === null) {
                    $result = $err("El elemento obtenido no tiene técnica base configurada.");
                    break;
                }
                $slots_nuevo = $slots - 1;
                sg_dojo_aprender($uid, $base_tid, 'usuario', SG_ORIGEN_DOJO);
                sg_dojo_guardar($db, $uid, $tobi, $progreso, $slots_nuevo);

                $result = $ok("¡La ruleta te otorgó el árbol " . ucfirst($elegido) . "!");
                $result['extra'] = array('elemento' => $elegido);
                break;

            // ── (A3) Elegir elemento (Afinidad Elemental, sin tirada) ─
            case 'elemento':
                // puede_elegir ya integra: VAFIEL, que sea el 1er elemento natural,
                // no tener Incompatibilidad Elemental, slots, tope y nivel + Tobis.
                if (empty($estado['ruleta']['puede_elegir'])) {
                    $result = $err("No puedes elegir tu elemento en este momento.");
                    break;
                }
                if (!in_array($arbol, $estado['ruleta']['pool'], true)) {
                    $result = $err("Ese elemento no está disponible.");
                    break;
                }
                $base_tid = isset($catalogo[$arbol]['base']) ? $catalogo[$arbol]['base'] : null;
                if ($base_tid === null) {
                    $result = $err("Ese elemento no tiene técnica base configurada.");
                    break;
                }
                $pago = sg_dojo_pagar_arbol($progreso, $tobi, $nivel, $costos);
                if (!$pago['ok']) {
                    $result = $err($pago['msg']);
                    break;
                }
                $progreso = $pago['progreso'];
                $tobi = $pago['tobi'];
                $slots_nuevo = (int) $estado['slot_elementales'] - 1;
                sg_dojo_aprender($uid, $base_tid, 'usuario', SG_ORIGEN_DOJO);
                sg_dojo_guardar($db, $uid, $tobi, $progreso, $slots_nuevo);
                $result = $ok("Elegiste el elemento " . ucfirst($arbol) . " por {$costos['arbol']} Tobis.");
                break;

            // ── (B) Desbloquear una rama ────────────────────────────
            case 'rama':
                $rinfo = isset($estado['arboles'][$arbol]['ramas'][$rama]) ? $estado['arboles'][$arbol]['ramas'][$rama] : null;
                if ($rinfo === null || empty($rinfo['desbloqueable'])) {
                    $result = $err("Esa rama no está disponible para desbloquear.");
                    break;
                }
                if (in_array($arbol, $estado['clan']['arboles'], true) && (int) $progreso['clan_rama_usada'] === 0) {
                    $result = $err("Primero debes elegir tu rama de clan gratis antes de comprar otra rama de ese árbol.");
                    break;
                }
                if ($tobi < $costos['rama']) {
                    $result = $err("Tobis insuficientes: necesitas {$costos['rama']}.");
                    break;
                }
                $tobi -= $costos['rama'];
                $progreso['desbloqueo_ramas']++;
                sg_dojo_aprender($uid, $rinfo['base'], 'usuario', SG_ORIGEN_DOJO);
                sg_dojo_guardar($db, $uid, $tobi, $progreso);
                $result = $ok("Desbloqueaste una rama por {$costos['rama']} Tobis.");
                break;

            // ── (C) Subir nivel de una rama (2 mejoras) ─────────────
            case 'nivel':
                $rinfo = isset($estado['arboles'][$arbol]['ramas'][$rama]) ? $estado['arboles'][$arbol]['ramas'][$rama] : null;
                if ($rinfo === null || empty($rinfo['puede_subir'])) {
                    $result = $err("Esa rama no puede subir de nivel.");
                    break;
                }
                $mejoras_in = isset($params['mejoras']) && is_array($params['mejoras']) ? array_values(array_unique($params['mejoras'])) : array();
                if (count($mejoras_in) !== 2) {
                    $result = $err("Debes elegir exactamente 2 mejoras de la misma rama.");
                    break;
                }
                $libres = $rinfo['mejoras_libres'];
                foreach ($mejoras_in as $mtid) {
                    if (!in_array($mtid, $libres, true)) {
                        $result = $err("Una de las mejoras elegidas no está disponible.");
                        break 2;
                    }
                }
                if ((int) $progreso['nivel_rama_disponibles'] > 0) {
                    $progreso['nivel_rama_disponibles']--;
                    foreach ($mejoras_in as $mtid) { sg_dojo_aprender($uid, $mtid, 'usuario', SG_ORIGEN_DOJO); }
                    sg_dojo_guardar($db, $uid, $tobi, $progreso);
                    $result = $ok("Subiste de nivel la rama (gratis).");
                    break;
                }
                if ($tobi < $costos['nivel']) {
                    $result = $err("Tobis insuficientes: necesitas {$costos['nivel']}.");
                    break;
                }
                $tobi -= $costos['nivel'];
                $progreso['desbloqueo_nivel_ramas']++;
                foreach ($mejoras_in as $mtid) { sg_dojo_aprender($uid, $mtid, 'usuario', SG_ORIGEN_DOJO); }
                sg_dojo_guardar($db, $uid, $tobi, $progreso);
                $result = $ok("Subiste de nivel la rama por {$costos['nivel']} Tobis.");
                break;

            // ── (D) Aprender especialización (gratis) ───────────────
            case 'especializacion':
                $tid = isset($params['tid']) ? trim($params['tid']) : '';
                $esp = isset($estado['arboles'][$arbol]['especializaciones']) ? $estado['arboles'][$arbol]['especializaciones'] : null;
                if ($esp === null || $tid === '' || !in_array($tid, $esp['elegibles'], true)) {
                    $result = $err("Esa especialización no está disponible.");
                    break;
                }
                if ((int) $esp['aprendidas'] >= (int) $esp['cupo']) {
                    $result = $err("No tienes cupo de especializaciones todavía.");
                    break;
                }
                sg_dojo_aprender($uid, $tid, 'usuario', SG_ORIGEN_DOJO);
                $result = $ok("Aprendiste una especialización (gratis).");
                break;

            // ── (E) Rama de clan gratis (una sola vez) ──────────────
            case 'rama_clan':
                if (empty($estado['clan']['rama_gratis_disponible'])) {
                    $result = $err("No tienes una rama de clan gratis disponible.");
                    break;
                }
                // El árbol puede ser cualquiera de los de clan (híbrido). Si no se
                // especifica, se toma el primero (compat con el flujo de un clan).
                $clan_arbol = ($arbol !== '' && in_array($arbol, $estado['clan']['arboles'], true))
                    ? $arbol
                    : $estado['clan']['arbol'];
                if ($clan_arbol === null || !in_array($clan_arbol, $estado['clan']['arboles'], true)) {
                    $result = $err("Ese árbol no es de clan.");
                    break;
                }
                $rinfo = isset($estado['arboles'][$clan_arbol]['ramas'][$rama]) ? $estado['arboles'][$clan_arbol]['ramas'][$rama] : null;
                if ($rinfo === null || empty($rinfo['desbloqueable'])) {
                    $result = $err("Esa rama de clan no está disponible.");
                    break;
                }
                $progreso['clan_rama_usada'] = 1;
                sg_dojo_aprender($uid, $rinfo['base'], 'usuario', SG_ORIGEN_DOJO);
                sg_dojo_guardar($db, $uid, $tobi, $progreso);
                $result = $ok("Aprendiste una rama de tu clan (gratis).");
                break;

            // ── Rama gratis por árbol elemental comprado (1 por árbol) ──
            case 'rama_gratis':
                $ainfo = isset($estado['arboles'][$arbol]) ? $estado['arboles'][$arbol] : null;
                if ($ainfo === null || empty($ainfo['rama_gratis_disponible'])) {
                    $result = $err("No tienes una rama gratis disponible en ese árbol.");
                    break;
                }
                $rinfo = isset($ainfo['ramas'][$rama]) ? $ainfo['ramas'][$rama] : null;
                if ($rinfo === null || empty($rinfo['desbloqueable'])) {
                    $result = $err("Esa rama no está disponible.");
                    break;
                }
                // Es tu primera rama del árbol: gratis, sin Tobis y sin subir
                // desbloqueo_ramas. El estado "ya usada" se deriva solo (al quedar
                // 1 rama desbloqueada, deja de ofrecerse).
                sg_dojo_aprender($uid, $rinfo['base'], 'usuario', SG_ORIGEN_DOJO);
                $result = $ok("Elegiste una rama gratis en " . ucfirst($arbol) . ".");
                break;

            default:
                $result = $err("Acción no reconocida.");
                break;
        }
    }

    $db->query("SELECT RELEASE_LOCK('$lock_name')");
    return $result;
}

// ============================================================================
// Recompensas de misión (sg/admin/recompensas_mision.php)
// Fuente de verdad ÚNICA de las tablas T1..T6 — ver docs/recompensas_instrucciones.txt.
// Se serializa a JSON para la vista previa en el cliente, y el propio servidor
// la usa para RECALCULAR los montos al confirmar (nunca confía en el POST).
// ============================================================================

// 'combate' no tiene rango (la recompensa depende del nivel de cada
// combatiente, no del rango de una misión) — rangos vacío a propósito; el
// front y sg/admin/recompensas_mision.php ocultan ese selector para este tipo.
function sg_mision_tipos() {
    return array(
        'autonarrada' => array('label' => 'Misión Autonarrada', 'rangos' => array('E', 'D', 'C')),
        'normal'      => array('label' => 'Misión Normal',      'rangos' => array('E', 'D', 'C', 'B', 'A', 'A+', 'S')),
        'guerra'      => array('label' => 'Misión de Guerra',   'rangos' => array('B', 'A', 'A+', 'S')),
        'trama'       => array('label' => 'Trama Oficial',      'rangos' => array('E', 'D', 'C', 'B', 'A', 'A+', 'S')),
        'evento'      => array('label' => 'Evento',             'rangos' => array('E', 'D', 'C', 'B', 'A', 'A+', 'S')),
        'side'        => array('label' => 'Side Quest',         'rangos' => array('E', 'D', 'C', 'B', 'A', 'A+', 'S')),
        'combate'     => array('label' => 'Combate',            'rangos' => array()),
    );
}

function sg_mision_pergaminos() {
    return array('E' => 'PERG001', 'D' => 'PERG002', 'C' => 'PERG003', 'B' => 'PERG004', 'A' => 'PERG005', 'A+' => 'PERG006', 'S' => 'PERG007');
}

function sg_mision_pergamino_objeto($rango) {
    $map = sg_mision_pergaminos();
    return isset($map[$rango]) ? $map[$rango] : null;
}

function sg_mision_tablas() {
    return array(
        // T1 — Participante (cualquier tipo). Nunca da pergamino.
        'T1' => array(
            'E'  => array('ryos' => 250,  'tobi' => 2,  'exp' => 50,  'posts_min' => 3),
            'D'  => array('ryos' => 500,  'tobi' => 5,  'exp' => 70,  'posts_min' => 4),
            'C'  => array('ryos' => 1000, 'tobi' => 10, 'exp' => 90,  'posts_min' => 5),
            'B'  => array('ryos' => 2000, 'tobi' => 20, 'exp' => 110, 'posts_min' => 6),
            'A'  => array('ryos' => 3000, 'tobi' => 30, 'exp' => 130, 'posts_min' => 8),
            'A+' => array('ryos' => 4000, 'tobi' => 45, 'exp' => 150, 'posts_min' => 10),
            'S'  => array('ryos' => 5000, 'tobi' => 60, 'exp' => 200, 'posts_min' => 12),
        ),
        // T2 — Narrador no oficial (solo Misión Normal). Sin tobis. Pergamino recortado.
        'T2' => array(
            'E'  => array('ryos' => 250,  'tobi' => 0, 'exp' => 35,  'perg_rango' => 'E'),
            'D'  => array('ryos' => 500,  'tobi' => 0, 'exp' => 50,  'perg_rango' => 'E'),
            'C'  => array('ryos' => 1000, 'tobi' => 0, 'exp' => 65,  'perg_rango' => 'D'),
            'B'  => array('ryos' => 2000, 'tobi' => 0, 'exp' => 80,  'perg_rango' => 'D'),
            'A'  => array('ryos' => 3000, 'tobi' => 0, 'exp' => 90,  'perg_rango' => 'C'),
            'A+' => array('ryos' => 4000, 'tobi' => 0, 'exp' => 105, 'perg_rango' => 'C'),
            'S'  => array('ryos' => 5000, 'tobi' => 0, 'exp' => 140, 'perg_rango' => 'B'),
        ),
        // T3 — Narrador oficial de Misión Normal. Sin tobis. Pergamino del mismo rango.
        'T3' => array(
            'E'  => array('ryos' => 500,  'tobi' => 0, 'exp' => 35,  'perg_rango' => 'E'),
            'D'  => array('ryos' => 750,  'tobi' => 0, 'exp' => 50,  'perg_rango' => 'D'),
            'C'  => array('ryos' => 1500, 'tobi' => 0, 'exp' => 65,  'perg_rango' => 'C'),
            'B'  => array('ryos' => 2500, 'tobi' => 0, 'exp' => 80,  'perg_rango' => 'B'),
            'A'  => array('ryos' => 3500, 'tobi' => 0, 'exp' => 90,  'perg_rango' => 'A'),
            'A+' => array('ryos' => 4500, 'tobi' => 0, 'exp' => 105, 'perg_rango' => 'A+'),
            'S'  => array('ryos' => 5500, 'tobi' => 0, 'exp' => 140, 'perg_rango' => 'S'),
        ),
        // T4 — Narrador de Misión de Guerra (oficial o no; solo rango B+). Pergamino del mismo rango.
        'T4' => array(
            'B'  => array('ryos' => 3000, 'tobi' => 20, 'exp' => 100, 'perg_rango' => 'B'),
            'A'  => array('ryos' => 4000, 'tobi' => 30, 'exp' => 120, 'perg_rango' => 'A'),
            'A+' => array('ryos' => 5000, 'tobi' => 50, 'exp' => 130, 'perg_rango' => 'A+'),
            'S'  => array('ryos' => 6000, 'tobi' => 80, 'exp' => 160, 'perg_rango' => 'S'),
        ),
        // T6 — Autonarrada (solo E/D/C, un único recipiente). Sin pergamino.
        'T6' => array(
            'E' => array('ryos' => 250,  'tobi' => 2,  'exp' => 50),
            'D' => array('ryos' => 500,  'tobi' => 5,  'exp' => 70),
            'C' => array('ryos' => 1000, 'tobi' => 10, 'exp' => 90),
        ),
        // T5 — Narrador de Trama Oficial / Evento / Side Quest (oficial o no).
        // Recompensa PLANA: no depende del rango de la misión. Sin pergamino.
        'T5' => array('ryos' => 2500, 'tobi' => 20, 'exp' => 100),
    );
}

/**
 * Resuelve la recompensa base (sin aplicar %) para un rol dentro de un tipo/rango
 * de misión. $rol: 'participante' | 'narrador' | 'autor'. $oficial solo importa
 * cuando $tipo === 'normal' (T2 vs T3); en Guerra el narrador siempre usa T4 y en
 * Trama/Evento/Side siempre usa la recompensa plana T5 (no depende del rango),
 * sea oficial o no.
 * Devuelve array('ryos','tobi','exp','pergamino') o false si la combinación no es válida.
 */
function sg_recompensa_mision($rol, $tipo, $rango, $oficial = false) {
    $tablas = sg_mision_tablas();
    $tipos  = sg_mision_tipos();
    if (!isset($tipos[$tipo]) || !in_array($rango, $tipos[$tipo]['rangos'], true)) {
        return false;
    }

    if ($rol === 'participante') {
        if (!isset($tablas['T1'][$rango])) { return false; }
        $t = $tablas['T1'][$rango];
        return array('ryos' => $t['ryos'], 'tobi' => $t['tobi'], 'exp' => $t['exp'], 'pergamino' => null);
    }

    if ($rol === 'autor') {
        if ($tipo !== 'autonarrada' || !isset($tablas['T6'][$rango])) { return false; }
        $t = $tablas['T6'][$rango];
        return array('ryos' => $t['ryos'], 'tobi' => $t['tobi'], 'exp' => $t['exp'], 'pergamino' => null);
    }

    if ($rol === 'narrador') {
        if ($tipo === 'autonarrada') { return false; }

        if ($tipo === 'guerra') {
            if (!isset($tablas['T4'][$rango])) { return false; }
            $t = $tablas['T4'][$rango];
        } else if ($tipo === 'normal') {
            $tabla = $oficial ? 'T3' : 'T2';
            if (!isset($tablas[$tabla][$rango])) { return false; }
            $t = $tablas[$tabla][$rango];
        } else { // trama, evento, side: recompensa plana T5, oficial o no
            $t = $tablas['T5'];
        }

        $pergamino = isset($t['perg_rango']) ? sg_mision_pergamino_objeto($t['perg_rango']) : null;
        return array('ryos' => $t['ryos'], 'tobi' => $t['tobi'], 'exp' => $t['exp'], 'pergamino' => $pergamino);
    }

    return false;
}

// ============================================================================
// Recompensas de COMBATE (1 vs 1). Modelo distinto al de misiones: no hay
// rango, rol, % ni pergamino — solo el resultado (ganador/perdedor/empate) y
// un bonus si se derrota a alguien de nivel más alto. Los combates deben ser
// completos (nunca parciales), por eso no hay escalado por %.
// ============================================================================

/**
 * $resultado: 'ganador' | 'perdedor' | 'empate'. $nivel_propio/$nivel_oponente:
 * nivel de ficha (mybb_sg_sg_fichas.nivel) de cada combatiente. El bonus de
 * nivel SOLO aplica al ganador, y solo si el oponente derrotado tenía más
 * nivel (la diferencia nunca resta puntos).
 * Devuelve array('exp','tobi') o false si el resultado no es válido.
 */
function sg_recompensa_combate($resultado, $nivel_propio, $nivel_oponente) {
    $dif = max(0, intval($nivel_oponente) - intval($nivel_propio));

    if ($resultado === 'empate') {
        return array('exp' => 10, 'tobi' => 10); // solo participación
    }
    if ($resultado === 'ganador') {
        return array('exp' => 10 + 15 + $dif, 'tobi' => 10 + 20 + (2 * $dif));
    }
    if ($resultado === 'perdedor') {
        return array('exp' => 10 + 5, 'tobi' => 10 + 5); // sin bonus de nivel
    }
    return false;
}

// Entrega +1 de un objeto al inventario de un usuario (upsert). Mismo patrón
// que el helper comentado de recompensa_diaria.php. Loguea en historial (delta +1).
function sg_inventario_dar_objeto($uid, $objeto_id, $tipo, $origen, $detalle = '', $grupo = null, $actor_uid = null, $pid = null, $tid = null) {
    global $db;
    $uid = intval($uid);
    $objeto_id_db = $db->escape_string($objeto_id);

    $cantidad_actual = 0;
    $tiene = false;
    $q = $db->query("SELECT cantidad FROM `mybb_sg_sg_inventario` WHERE uid='$uid' AND objeto_id='$objeto_id_db'");
    while ($r = $db->fetch_array($q)) { $tiene = true; $cantidad_actual = intval($r['cantidad']); }

    if ($tiene) {
        $cantidad_nueva = $cantidad_actual + 1;
        $db->query("UPDATE `mybb_sg_sg_inventario` SET cantidad='$cantidad_nueva' WHERE objeto_id='$objeto_id_db' AND uid='$uid'");
    } else {
        $db->query("INSERT INTO `mybb_sg_sg_inventario` (objeto_id, uid, cantidad) VALUES ('$objeto_id_db', '$uid', '1')");
        $cantidad_nueva = 1;
    }

    sg_historial_objeto_log($uid, $objeto_id, 1, $tipo, $origen, $detalle, $grupo, $actor_uid, $pid, $tid);
    return $cantidad_nueva;
}

// Igual que sg_inventario_dar_objeto pero para cantidades > 1 en una sola fila
// de historial (ej. premios de gacha que dan varias unidades de un objeto).
function sg_inventario_dar_objeto_cantidad($uid, $objeto_id, $cantidad, $tipo, $origen, $detalle = '', $grupo = null, $actor_uid = null, $pid = null, $tid = null) {
    global $db;
    $uid = intval($uid);
    $cantidad = max(1, (int) $cantidad);
    $objeto_id_db = $db->escape_string($objeto_id);

    $cantidad_actual = 0;
    $tiene = false;
    $q = $db->query("SELECT cantidad FROM `mybb_sg_sg_inventario` WHERE uid='$uid' AND objeto_id='$objeto_id_db'");
    while ($r = $db->fetch_array($q)) { $tiene = true; $cantidad_actual = intval($r['cantidad']); }

    $cantidad_nueva = $cantidad_actual + $cantidad;
    if ($tiene) {
        $db->query("UPDATE `mybb_sg_sg_inventario` SET cantidad='$cantidad_nueva' WHERE objeto_id='$objeto_id_db' AND uid='$uid'");
    } else {
        $db->query("INSERT INTO `mybb_sg_sg_inventario` (objeto_id, uid, cantidad) VALUES ('$objeto_id_db', '$uid', '$cantidad_nueva')");
    }

    sg_historial_objeto_log($uid, $objeto_id, $cantidad, $tipo, $origen, $detalle, $grupo, $actor_uid, $pid, $tid);
    return $cantidad_nueva;
}

// ============================================================================
// Historial de misiones (metadata de referencia, pestaña "Estadísticas" de la
// ficha). Se llena desde sg/admin/recompensas_mision.php al confirmar una
// recompensa — una fila por recipiente. No es auditoría (eso sigue viviendo
// en mybb_sg_sg_audit_consola_mod); esta tabla es estructurada para poder
// agregar/contar por rango de forma eficiente.
// ============================================================================

function sg_mision_rangos_orden() {
    return array('E', 'D', 'C', 'B', 'A', 'A+', 'S');
}

function sg_historial_mision_registrar($tid, $uid, $rol, $tipo, $rango, $oficial = false) {
    global $db;
    $tid    = intval($tid);
    $uid    = intval($uid);
    $rol_db = $db->escape_string($rol);
    $tipo_db = $db->escape_string($tipo);
    $rango_db = $db->escape_string($rango);
    $oficial_db = $oficial ? 1 : 0;

    $db->query("
        INSERT INTO `mybb_sg_sg_historial_misiones` (`tid`, `uid`, `rol`, `tipo`, `rango`, `oficial`) VALUES
        ('$tid', '$uid', '$rol_db', '$tipo_db', '$rango_db', '$oficial_db')
    ");
}

/**
 * Estadísticas de misiones de un usuario para mostrar en su ficha: cuántas ha
 * jugado (participante + autor de autonarrada) y cuántas ha narrado, ambas
 * desglosadas por rango (solo rangos con al menos 1 registro) y con su total.
 */
function sg_historial_mision_stats($uid) {
    global $db;
    $uid = intval($uid);
    $orden = sg_mision_rangos_orden();

    $jugadas  = array();
    $narradas = array();

    $q = $db->query("
        SELECT rango, COUNT(*) AS c FROM `mybb_sg_sg_historial_misiones`
        WHERE uid='$uid' AND rol IN ('participante', 'autor')
        GROUP BY rango
    ");
    while ($r = $db->fetch_array($q)) { $jugadas[$r['rango']] = intval($r['c']); }

    $q = $db->query("
        SELECT rango, COUNT(*) AS c FROM `mybb_sg_sg_historial_misiones`
        WHERE uid='$uid' AND rol = 'narrador'
        GROUP BY rango
    ");
    while ($r = $db->fetch_array($q)) { $narradas[$r['rango']] = intval($r['c']); }

    // Reordenar según el orden canónico de rangos (E..S) en vez del orden de la BD.
    $jugadas_ord = array();
    $narradas_ord = array();
    foreach ($orden as $r) {
        if (isset($jugadas[$r]))  { $jugadas_ord[$r] = $jugadas[$r]; }
        if (isset($narradas[$r])) { $narradas_ord[$r] = $narradas[$r]; }
    }

    return array(
        'jugadas'        => $jugadas_ord,
        'jugadas_total'  => array_sum($jugadas_ord),
        'narradas'       => $narradas_ord,
        'narradas_total' => array_sum($narradas_ord),
    );
}

// ============================================================================
// Historial de combates 1v1 (metadata de referencia, pestaña "Estadísticas" de
// la ficha). Se llena desde sg/admin/recompensas_mision.php al confirmar una
// recompensa de combate — una fila por combatiente. `modo` identifica la
// variante ('combate_1v1' hoy; deja espacio para otras en el futuro sin
// cambiar el esquema). No es auditoría (eso sigue en
// mybb_sg_sg_audit_consola_mod); esta tabla es para poder contar
// victorias/empates/derrotas de forma eficiente.
// ============================================================================

function sg_historial_combate_registrar($tid, $uid, $modo, $resultado, $nivel_propio, $nivel_oponente) {
    global $db;
    $tid = intval($tid);
    $uid = intval($uid);
    $modo_db = $db->escape_string($modo);
    $resultado_db = $db->escape_string($resultado);
    $nivel_propio = intval($nivel_propio);
    $nivel_oponente = intval($nivel_oponente);

    $db->query("
        INSERT INTO `mybb_sg_sg_historial_combates` (`tid`, `uid`, `modo`, `resultado`, `nivel_propio`, `nivel_oponente`) VALUES
        ('$tid', '$uid', '$modo_db', '$resultado_db', '$nivel_propio', '$nivel_oponente')
    ");
}

/**
 * Cuenta victorias/empates/derrotas de un usuario para un modo de combate
 * dado (por defecto 'combate_1v1'). Devuelve
 * array('ganador','perdedor','empate','total').
 */
function sg_historial_combate_stats($uid, $modo = 'combate_1v1') {
    global $db;
    $uid = intval($uid);
    $modo_db = $db->escape_string($modo);

    $counts = array('ganador' => 0, 'perdedor' => 0, 'empate' => 0);
    $q = $db->query("
        SELECT resultado, COUNT(*) AS c FROM `mybb_sg_sg_historial_combates`
        WHERE uid='$uid' AND modo='$modo_db'
        GROUP BY resultado
    ");
    while ($r = $db->fetch_array($q)) {
        if (isset($counts[$r['resultado']])) { $counts[$r['resultado']] = intval($r['c']); }
    }
    $counts['total'] = $counts['ganador'] + $counts['perdedor'] + $counts['empate'];
    return $counts;
}

// ============================================================================
// Post automático de recompensas (sg/admin/recompensas_mision.php). Al
// confirmar, se publica una respuesta en el propio tema, en BBCode, firmada
// por el UID 2 (Masashi Kishimoto), resumiendo lo que recibió cada persona.
// ============================================================================

define('SG_RECOMPENSAS_AUTOR_UID', 2);

/**
 * Arma el mensaje en BBCode. $aplicados: lista de arrays con
 * uid,nombre,rol,oficial,pct,ryos,tobi,exp,pergamino (mismas claves que
 * construye recompensas_mision.php al aplicar). Debe generar EXACTAMENTE el
 * mismo texto que su espejo en JS (rmGenerarBBCode) para que la vista previa
 * coincida con lo que realmente se publica.
 */
function sg_post_recompensas_bbcode($tid, $subject, $tipo, $rango, $aplicados) {
    $tipos = sg_mision_tipos();
    $tipo_label = isset($tipos[$tipo]) ? $tipos[$tipo]['label'] : $tipo;
    $rol_labels = array('participante' => 'Participante', 'narrador' => 'Narrador', 'autor' => 'Autor');

    $lineas = array();
    foreach ($aplicados as $a) {
        $rol_txt = isset($rol_labels[$a['rol']]) ? $rol_labels[$a['rol']] : $a['rol'];
        if ($a['rol'] === 'narrador' && $tipo === 'normal') {
            $rol_txt .= !empty($a['oficial']) ? ' Oficial' : ' No Oficial';
        }
        $perg_txt = !empty($a['pergamino']) ? ", Pergamino {$a['pergamino']}" : '';
        $lineas[] = "[*][b]{$a['nombre']}[/b] — $rol_txt ({$a['pct']}%): {$a['ryos']} Ryos, {$a['tobi']} Tobis, {$a['exp']} Exp$perg_txt";
    }
    $lista = implode("\n", $lineas);

    return "[align=center][size=4][b]¡Recompensas de Misión Entregadas![/b][/size][/align]\n"
         . "[hr]\n"
         . "[b]Tema:[/b] $subject (TID $tid)\n"
         . "[b]Tipo:[/b] $tipo_label · [b]Rango:[/b] $rango\n\n"
         . "[list]\n$lista\n[/list]\n"
         . "[hr]\n"
         . "[b]¡Felicidades a todos los shinobi involucrados en esta misión! Que sigan forjando su leyenda.[/b]";
}

/**
 * Igual que sg_post_recompensas_bbcode() pero para combates: sin rango/tipo,
 * el rol de cada aplicado es su resultado ('ganador'|'perdedor'|'empate').
 */
function sg_post_combate_bbcode($tid, $subject, $aplicados) {
    $result_labels = array('ganador' => 'Ganador', 'perdedor' => 'Perdedor', 'empate' => 'Empate');

    $lineas = array();
    foreach ($aplicados as $a) {
        $result_txt = isset($result_labels[$a['rol']]) ? $result_labels[$a['rol']] : $a['rol'];
        $lineas[] = "[*][b]{$a['nombre']}[/b] — $result_txt: {$a['tobi']} Tobis, {$a['exp']} Exp";
    }
    $lista = implode("\n", $lineas);

    return "[align=center][size=4][b]¡Resultado del Combate![/b][/size][/align]\n"
         . "[hr]\n"
         . "[b]Tema:[/b] $subject (TID $tid)\n\n"
         . "[list]\n$lista\n[/list]\n"
         . "[hr]\n"
         . "[b]¡Buen combate a ambos! Que sigan puliendo su técnica ninja.[/b]";
}

/**
 * Publica $mensaje como respuesta del tema $tid, firmado por
 * SG_RECOMPENSAS_AUTOR_UID. Usa el PostDataHandler nativo de MyBB (igual que
 * newreply.php) para que repliquen contadores, último post del foro/tema, etc.
 * admin_override=true salta el chequeo de flood (es un post automatizado).
 * Devuelve array('ok'=>bool, 'pid'=>int|null, 'error'=>string).
 */
function sg_publicar_post_recompensas($tid, $fid, $mensaje) {
    $autor = select_one_query_with_id('mybb_sg_users', 'uid', SG_RECOMPENSAS_AUTOR_UID);
    if (!$autor) {
        return array('ok' => false, 'pid' => null, 'error' => 'No existe el usuario narrador de recompensas (UID ' . SG_RECOMPENSAS_AUTOR_UID . ').');
    }

    require_once MYBB_ROOT . 'inc/datahandlers/post.php';
    $posthandler = new PostDataHandler('insert');
    $posthandler->admin_override = true;

    $post = array(
        'tid'       => intval($tid),
        'replyto'   => 0,
        'fid'       => intval($fid),
        'subject'   => '',
        'icon'      => -1,
        'uid'       => SG_RECOMPENSAS_AUTOR_UID,
        'username'  => $autor['username'],
        'message'   => $mensaje,
        'ipaddress' => my_inet_pton(get_ip()),
        'posthash'  => md5(uniqid('', true)),
        'savedraft' => 0,
        'options'   => array('signature' => 0, 'subscriptionmethod' => 0, 'disablesmilies' => 0),
    );
    $posthandler->set_data($post);

    if (!$posthandler->validate_post()) {
        return array('ok' => false, 'pid' => null, 'error' => implode(' ', $posthandler->get_friendly_errors()));
    }

    $post_info = $posthandler->insert_post();
    return array('ok' => true, 'pid' => isset($post_info['pid']) ? $post_info['pid'] : null, 'error' => '');
}

// ============================================================================
// Historial estructurado de cambios de ficha — capa de escritura.
// Ver docs/instrucciones_cambios.md. Los call sites NO insertan a mano en las
// tablas de historial: usan sg_ficha_set_campo / sg_usuario_set_campo /
// sg_dojo_aprender / sg_tecnica_quitar / sg_inventario_* , que hacen el cambio
// Y el log en un solo lugar. Estas tres funciones sg_historial_*_log son
// helpers internos de inserción (no llamarlas directo desde los scripts).
// ============================================================================

// Compara dos valores tratándolos como número si ambos lo son (evita falsos
// cambios tipo '100' vs '100.00'); si no, como string exacto.
function sg_valores_iguales($a, $b) {
    if (is_numeric($a) && is_numeric($b)) {
        return (float) $a == (float) $b;
    }
    return (string) $a === (string) $b;
}

// Resuelve el actor: si no se pasó explícito, es el usuario logueado.
function sg_historial_actor($actor_uid) {
    global $mybb;
    if ($actor_uid !== null) { return (int) $actor_uid; }
    return isset($mybb->user['uid']) ? (int) $mybb->user['uid'] : 0;
}

// INSERT en historial_ficha (campos escalares de fichas / users).
function sg_historial_ficha_log($uid, $tabla, $campo, $valor_anterior, $valor_nuevo, $tipo, $origen, $detalle = '', $grupo = null, $actor_uid = null, $pid = null, $tid = null) {
    global $db;
    $uid       = (int) $uid;
    $actor     = sg_historial_actor($actor_uid);
    $tabla_db  = $db->escape_string($tabla);
    $campo_db  = $db->escape_string($campo);
    $tipo_db   = $db->escape_string($tipo);
    $origen_db = $db->escape_string($origen);
    $va = ($valor_anterior === null) ? 'NULL' : "'".$db->escape_string($valor_anterior)."'";
    $vn = ($valor_nuevo   === null) ? 'NULL' : "'".$db->escape_string($valor_nuevo)."'";
    $grupo_db   = ($grupo   === null || $grupo   === '') ? 'NULL' : "'".$db->escape_string($grupo)."'";
    $detalle_db = ($detalle === null || $detalle === '') ? 'NULL' : "'".$db->escape_string($detalle)."'";
    $pid_db = ($pid === null) ? 'NULL' : (string) (int) $pid;
    $tid_db = ($tid === null) ? 'NULL' : (string) (int) $tid;
    $db->query("
        INSERT INTO `mybb_sg_sg_historial_ficha`
        (`uid`, `actor_uid`, `grupo`, `tabla`, `campo`, `valor_anterior`, `valor_nuevo`, `tipo`, `origen`, `pid`, `tid`, `detalle`)
        VALUES ('$uid', '$actor', $grupo_db, '$tabla_db', '$campo_db', $va, $vn, '$tipo_db', '$origen_db', $pid_db, $tid_db, $detalle_db)
    ");
}

// INSERT en historial_tecnicas.
function sg_historial_tecnica_log($uid, $tecnica_id, $accion, $tipo, $origen, $detalle = '', $grupo = null, $actor_uid = null) {
    global $db;
    $uid        = (int) $uid;
    $actor      = sg_historial_actor($actor_uid);
    $tec_db     = $db->escape_string($tecnica_id);
    $accion_db  = ($accion === 'quitar') ? 'quitar' : 'aprender';
    $tipo_db    = $db->escape_string($tipo);
    $origen_db  = $db->escape_string($origen);
    $grupo_db   = ($grupo   === null || $grupo   === '') ? 'NULL' : "'".$db->escape_string($grupo)."'";
    $detalle_db = ($detalle === null || $detalle === '') ? 'NULL' : "'".$db->escape_string($detalle)."'";
    $db->query("
        INSERT INTO `mybb_sg_sg_historial_tecnicas`
        (`uid`, `actor_uid`, `grupo`, `tecnica_id`, `accion`, `tipo`, `origen`, `detalle`)
        VALUES ('$uid', '$actor', $grupo_db, '$tec_db', '$accion_db', '$tipo_db', '$origen_db', $detalle_db)
    ");
}

// INSERT en historial_objetos. $cantidad es el delta (+ ganado, - gastado).
function sg_historial_objeto_log($uid, $objeto_id, $cantidad, $tipo, $origen, $detalle = '', $grupo = null, $actor_uid = null, $pid = null, $tid = null) {
    global $db;
    $uid        = (int) $uid;
    $actor      = sg_historial_actor($actor_uid);
    $obj_db     = $db->escape_string($objeto_id);
    $cantidad   = (int) $cantidad;
    $tipo_db    = $db->escape_string($tipo);
    $origen_db  = $db->escape_string($origen);
    $grupo_db   = ($grupo   === null || $grupo   === '') ? 'NULL' : "'".$db->escape_string($grupo)."'";
    $detalle_db = ($detalle === null || $detalle === '') ? 'NULL' : "'".$db->escape_string($detalle)."'";
    $pid_db = ($pid === null) ? 'NULL' : (string) (int) $pid;
    $tid_db = ($tid === null) ? 'NULL' : (string) (int) $tid;
    $db->query("
        INSERT INTO `mybb_sg_sg_historial_objetos`
        (`uid`, `actor_uid`, `grupo`, `objeto_id`, `cantidad`, `tipo`, `origen`, `pid`, `tid`, `detalle`)
        VALUES ('$uid', '$actor', $grupo_db, '$obj_db', '$cantidad', '$tipo_db', '$origen_db', $pid_db, $tid_db, $detalle_db)
    ");
}

// INSERT en historial_pasivas.
function sg_historial_pasiva_log($uid, $pasiva_id, $accion, $tipo, $origen, $detalle = '', $grupo = null, $actor_uid = null) {
    global $db;
    $uid        = (int) $uid;
    $actor      = sg_historial_actor($actor_uid);
    $pas_db     = $db->escape_string($pasiva_id);
    $accion_db  = ($accion === 'quitar') ? 'quitar' : 'comprar';
    $tipo_db    = $db->escape_string($tipo);
    $origen_db  = $db->escape_string($origen);
    $grupo_db   = ($grupo   === null || $grupo   === '') ? 'NULL' : "'".$db->escape_string($grupo)."'";
    $detalle_db = ($detalle === null || $detalle === '') ? 'NULL' : "'".$db->escape_string($detalle)."'";
    $db->query("
        INSERT INTO `mybb_sg_sg_historial_pasivas`
        (`uid`, `actor_uid`, `grupo`, `pasiva_id`, `accion`, `tipo`, `origen`, `detalle`)
        VALUES ('$uid', '$actor', $grupo_db, '$pas_db', '$accion_db', '$tipo_db', '$origen_db', $detalle_db)
    ");
}

// ── Choke points públicos ───────────────────────────────────────────────────

// Setea un campo escalar de mybb_sg_sg_fichas: lee el valor actual, si cambió
// hace el UPDATE y loguea; si no cambió, no hace nada. Devuelve true si cambió.
// $campo debe ser un nombre de columna provisto por código (no input de usuario).
function sg_ficha_set_campo($fid, $campo, $valor_nuevo, $tipo, $origen, $detalle = '', $grupo = null, $actor_uid = null) {
    global $db;
    $fid = (int) $fid;
    $campo_db = $db->escape_string($campo);

    $actual = null;
    $q = $db->query("SELECT `$campo_db` AS v FROM `mybb_sg_sg_fichas` WHERE `fid`='$fid'");
    while ($r = $db->fetch_array($q)) { $actual = $r['v']; }

    if (sg_valores_iguales($actual, $valor_nuevo)) { return false; }

    $vn_db = $db->escape_string($valor_nuevo);
    $db->query("UPDATE `mybb_sg_sg_fichas` SET `$campo_db`='$vn_db' WHERE `fid`='$fid'");
    sg_historial_ficha_log($fid, 'fichas', $campo, $actual, $valor_nuevo, $tipo, $origen, $detalle, $grupo, $actor_uid);
    return true;
}

// Igual que sg_ficha_set_campo pero sobre mybb_sg_users (cubre newpoints). uid == fid.
function sg_usuario_set_campo($uid, $campo, $valor_nuevo, $tipo, $origen, $detalle = '', $grupo = null, $actor_uid = null) {
    global $db;
    $uid = (int) $uid;
    $campo_db = $db->escape_string($campo);

    $actual = null;
    $q = $db->query("SELECT `$campo_db` AS v FROM `mybb_sg_users` WHERE `uid`='$uid'");
    while ($r = $db->fetch_array($q)) { $actual = $r['v']; }

    if (sg_valores_iguales($actual, $valor_nuevo)) { return false; }

    $vn_db = $db->escape_string($valor_nuevo);
    $db->query("UPDATE `mybb_sg_users` SET `$campo_db`='$vn_db' WHERE `uid`='$uid'");
    sg_historial_ficha_log($uid, 'users', $campo, $actual, $valor_nuevo, $tipo, $origen, $detalle, $grupo, $actor_uid);
    return true;
}

// Quita una técnica aprendida; hermana de sg_dojo_aprender. Loguea solo si
// realmente existía (DELETE que afectó filas).
function sg_tecnica_quitar($uid, $tid, $tipo, $origen, $detalle = '', $grupo = null, $actor_uid = null) {
    global $db;
    $uid = (int) $uid;
    $tid_esc = $db->escape_string($tid);
    $db->query("DELETE FROM `mybb_sg_sg_tec_aprendidas` WHERE uid='$uid' AND tid='$tid_esc'");
    if ($db->affected_rows() > 0) {
        sg_historial_tecnica_log($uid, $tid, 'quitar', $tipo, $origen, $detalle, $grupo, $actor_uid);
    }
}

// Da una pasiva de Tobis a una ficha; hermana de sg_dojo_aprender. Única por
// personaje (INSERT IGNORE sobre la UNIQUE KEY uid_pasiva) — si ya la tenía,
// no hace nada y no loguea de nuevo.
function sg_ficha_pasiva_dar($uid, $pasiva_id, $tipo, $origen, $detalle = '', $grupo = null, $actor_uid = null) {
    global $db;
    $uid = (int) $uid;
    $pas_esc = $db->escape_string($pasiva_id);
    $db->query("INSERT IGNORE INTO mybb_sg_sg_ficha_pasivas (uid, pasiva_id) VALUES ('$uid','$pas_esc')");
    if ($db->affected_rows() > 0) {
        sg_historial_pasiva_log($uid, $pasiva_id, 'comprar', $tipo, $origen, $detalle, $grupo, $actor_uid);
    }
}

// Catálogo de pasivas de Tobis. $solo_visibles=true (uso público, tienda/ficha)
// trae solo las publicadas en tienda; false trae todo (uso Staff en el panel
// de edición). $incluir_inactivas permite que, dentro de las publicadas en
// tienda, el Staff vea también las marcadas activo=0 (para que las revise en
// la propia tienda, aunque no se puedan comprar — eso ya lo bloquea el
// backend de tienda_tobis.php al validar la compra).
function sg_pasivas_tobis_catalogo($solo_visibles = true, $incluir_inactivas = false) {
    global $db;
    $condiciones = array();
    if ($solo_visibles) {
        $condiciones[] = "en_tienda='1'";
        if (!$incluir_inactivas) {
            $condiciones[] = "activo='1'";
        }
    }
    $filtro = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';
    $catalogo = array();
    $q = $db->query("SELECT * FROM `mybb_sg_sg_pasivas_tobis` $filtro ORDER BY coste, nombre");
    while ($r = $db->fetch_array($q)) {
        $catalogo[$r['pasiva_id']] = $r;
    }
    return $catalogo;
}

// Pasivas de Tobis que ya tiene una ficha, con los datos del catálogo unidos
// (nombre/descripcion/imagen). Si la pasiva fue borrada del
// catálogo después de comprada, esos campos vuelven null (fallback al id crudo
// lo resuelve quien renderiza, igual que con técnicas/objetos del historial).
function sg_ficha_pasivas_compradas($uid) {
    global $db;
    $uid = (int) $uid;
    $compradas = array();
    $q = $db->query("
        SELECT p.*, fp.tiempo AS tiempo_compra
        FROM `mybb_sg_sg_ficha_pasivas` fp
        LEFT JOIN `mybb_sg_sg_pasivas_tobis` p ON p.pasiva_id = fp.pasiva_id
        WHERE fp.uid='$uid'
        ORDER BY fp.tiempo DESC
    ");
    while ($r = $db->fetch_array($q)) {
        $compradas[] = $r;
    }
    return $compradas;
}

// Resta $cantidad de un objeto del inventario (no baja de 0; borra la fila si
// llega a 0). Loguea el delta negativo realmente aplicado. Devuelve la cantidad
// restante.
function sg_inventario_quitar_objeto($uid, $objeto_id, $cantidad, $tipo, $origen, $detalle = '', $grupo = null, $actor_uid = null, $pid = null, $tid = null) {
    global $db;
    $uid = (int) $uid;
    $objeto_id_db = $db->escape_string($objeto_id);
    $cantidad = max(0, (int) $cantidad);

    $actual = 0;
    $q = $db->query("SELECT cantidad FROM `mybb_sg_sg_inventario` WHERE uid='$uid' AND objeto_id='$objeto_id_db'");
    while ($r = $db->fetch_array($q)) { $actual = (int) $r['cantidad']; }
    if ($actual <= 0 || $cantidad === 0) { return $actual; }

    $quitar = min($cantidad, $actual);
    $nueva = $actual - $quitar;
    if ($nueva > 0) {
        $db->query("UPDATE `mybb_sg_sg_inventario` SET cantidad='$nueva' WHERE uid='$uid' AND objeto_id='$objeto_id_db'");
    } else {
        $db->query("DELETE FROM `mybb_sg_sg_inventario` WHERE uid='$uid' AND objeto_id='$objeto_id_db'");
    }
    sg_historial_objeto_log($uid, $objeto_id, -$quitar, $tipo, $origen, $detalle, $grupo, $actor_uid, $pid, $tid);
    return $nueva;
}

// Setea la cantidad ABSOLUTA de un objeto (para el ajuste manual de Staff en
// ficha_objetos.php, que edita cantidades directas). Calcula y loguea el delta
// contra el valor actual; si no cambió, no hace nada. Devuelve la cantidad final.
function sg_inventario_set_cantidad($uid, $objeto_id, $cantidad, $tipo, $origen, $detalle = '', $grupo = null, $actor_uid = null, $pid = null, $tid = null) {
    global $db;
    $uid = (int) $uid;
    $objeto_id_db = $db->escape_string($objeto_id);
    $cantidad = max(0, (int) $cantidad);

    $actual = 0;
    $existe = false;
    $q = $db->query("SELECT cantidad FROM `mybb_sg_sg_inventario` WHERE uid='$uid' AND objeto_id='$objeto_id_db'");
    while ($r = $db->fetch_array($q)) { $existe = true; $actual = (int) $r['cantidad']; }

    if ($cantidad === $actual) { return $actual; }

    if ($cantidad === 0) {
        $db->query("DELETE FROM `mybb_sg_sg_inventario` WHERE uid='$uid' AND objeto_id='$objeto_id_db'");
    } else if ($existe) {
        $db->query("UPDATE `mybb_sg_sg_inventario` SET cantidad='$cantidad' WHERE uid='$uid' AND objeto_id='$objeto_id_db'");
    } else {
        $db->query("INSERT INTO `mybb_sg_sg_inventario` (objeto_id, uid, cantidad) VALUES ('$objeto_id_db', '$uid', '$cantidad')");
    }
    sg_historial_objeto_log($uid, $objeto_id, $cantidad - $actual, $tipo, $origen, $detalle, $grupo, $actor_uid, $pid, $tid);
    return $cantidad;
}

// ============================================================================
// Gacha de pergaminos (ver docs/pergaminos_diseno.md). PERG001..PERG007 son
// objetos normales de mybb_sg_sg_objetos; al "abrirse" se consumen del
// inventario y disparan un sorteo ponderado configurado por Staff en
// mybb_sg_sg_gacha_premios / mybb_sg_sg_gacha_recompensas.
// ============================================================================

// Únicos IDs de objeto válidos como pergamino de gacha (whitelist; se usa para
// validar entrada de usuario antes de tocar la tabla de premios). Staff los
// da de alta marcando `en_gacha=1` en un objeto tipo='Pergamino' desde
// gestionar_objetos.php — no hace falta tocar código para agregar uno nuevo.
function sg_gacha_pergamino_ids() {
    global $db;
    static $ids = null;
    if ($ids === null) {
        $ids = array();
        // Los 7 "de rango" (E..S, mismo orden que sg_mision_pergaminos) van
        // siempre primero y en ese orden fijo; cualquier pergamino nuevo que
        // Staff cree después entra al final, alfabéticamente por nombre.
        $in = array();
        foreach (array_values(sg_mision_pergaminos()) as $rid) { $in[] = "'" . $db->escape_string($rid) . "'"; }
        $rango_list = implode(',', $in);
        $q = $db->query("
            SELECT objeto_id FROM `mybb_sg_sg_objetos`
            WHERE tipo='Pergamino' AND en_gacha='1'
            ORDER BY (FIELD(objeto_id, $rango_list) = 0), FIELD(objeto_id, $rango_list), nombre
        ");
        while ($r = $db->fetch_array($q)) { $ids[] = $r['objeto_id']; }
    }
    return $ids;
}

// Etiqueta de rango (E/D/C/B/A/A+/S) de un pergamino, reutilizando el mismo
// mapeo que ya usan las misiones (sg_mision_pergaminos). Null si no matchea.
function sg_gacha_pergamino_rango($pergamino_id) {
    static $mapa = null;
    if ($mapa === null) { $mapa = array_flip(sg_mision_pergaminos()); }
    return isset($mapa[$pergamino_id]) ? $mapa[$pergamino_id] : null;
}

// Trae los premios de un pergamino (por defecto solo los ACTIVOS, que es lo
// que debe usar el sorteo público), cada uno con su lista de recompensas
// anidada. $solo_activos=false trae también los inactivos (para el panel de
// Staff, que necesita poder ver/reactivar premios apagados). Devuelve array
// de premios (vacío si no hay ninguno configurado todavía).
function sg_gacha_premios($pergamino_id, $solo_activos = true) {
    global $db;
    $pid_esc = $db->escape_string($pergamino_id);
    $filtro_activo = $solo_activos ? "AND activo='1'" : '';

    $premios = array();
    $q = $db->query("SELECT * FROM `mybb_sg_sg_gacha_premios` WHERE pergamino_id='$pid_esc' $filtro_activo ORDER BY orden, id");
    while ($p = $db->fetch_array($q)) {
        $p['recompensas'] = array();
        $premios[$p['id']] = $p;
    }
    if (empty($premios)) { return array(); }

    $ids = implode(',', array_map('intval', array_keys($premios)));
    $qr = $db->query("SELECT * FROM `mybb_sg_sg_gacha_recompensas` WHERE premio_id IN ($ids) ORDER BY orden, id");
    while ($r = $db->fetch_array($qr)) {
        $premios[$r['premio_id']]['recompensas'][] = $r;
    }

    return array_values($premios);
}

// Suma la probabilidad de los premios ACTIVOS de un pergamino (jackpot incluido).
function sg_gacha_probabilidad_total($pergamino_id) {
    global $db;
    $pid_esc = $db->escape_string($pergamino_id);
    $total = 0.0;
    $q = $db->query("SELECT SUM(probabilidad) AS total FROM `mybb_sg_sg_gacha_premios` WHERE pergamino_id='$pid_esc' AND activo='1'");
    while ($r = $db->fetch_array($q)) { $total = (float) $r['total']; }
    return $total;
}

// Un pergamino solo puede abrirse si sus premios activos suman EXACTAMENTE
// 100% (tolerancia de 0.01 por redondeo de punto flotante / decimal(5,2)).
function sg_gacha_probabilidad_lista($pergamino_id) {
    return abs(sg_gacha_probabilidad_total($pergamino_id) - 100) < 0.01;
}

// Texto legible de una recompensa individual (tabla de premios / historial).
// $obj_nombres debe venir con claves en MAYÚSCULAS (ver sg_gacha_abrir_bajo_lock:
// el objeto_id cargado en gacha_recompensas puede tener otro casing que el
// objeto_id real de mybb_sg_sg_objetos, así que la clave se normaliza siempre).
function sg_gacha_recompensa_texto($rec, $obj_nombres = array()) {
    $cant = (int) $rec['cantidad'];
    if ($rec['tipo'] === 'objeto') {
        $key = strtoupper((string) $rec['objeto_id']);
        $nombre = isset($obj_nombres[$key]) ? $obj_nombres[$key] : $rec['objeto_id'];
        return ($cant > 1 ? $cant . '× ' : '') . $nombre;
    }
    $labels = array('ryos' => 'Ryos', 'rin' => 'Rin', 'madara' => 'Madara', 'tobi' => 'Tobi');
    $label = isset($labels[$rec['tipo']]) ? $labels[$rec['tipo']] : $rec['tipo'];
    return '+' . (int) $rec['valor'] . ' ' . $label;
}

// Tabla de premios (nombre, recompensas ya resueltas a texto, probabilidad)
// de UN pergamino — para mostrarle al jugador qué puede ganar y con qué
// probabilidad antes de abrir. Solo premios activos (los mismos que entran
// al sorteo real de sg_gacha_abrir). Ordenada de mayor a menor probabilidad
// (orden de lectura, no afecta al sorteo real: eso usa sg_gacha_premios()
// directo, con su propio orden de acumulación).
function sg_gacha_premios_tabla($pergamino_id) {
    global $db;
    $premios = sg_gacha_premios($pergamino_id);

    $obj_ids = array();
    foreach ($premios as $p) {
        foreach ($p['recompensas'] as $r) {
            if ($r['tipo'] === 'objeto' && $r['objeto_id'] !== null && $r['objeto_id'] !== '') {
                $obj_ids[] = $r['objeto_id'];
            }
        }
    }
    $obj_nombres = array();
    if (!empty($obj_ids)) {
        $in = array();
        foreach (array_unique($obj_ids) as $x) { $in[] = "'" . $db->escape_string($x) . "'"; }
        $qn = $db->query("SELECT objeto_id, nombre FROM `mybb_sg_sg_objetos` WHERE objeto_id IN (" . implode(',', $in) . ")");
        while ($rn = $db->fetch_array($qn)) { $obj_nombres[strtoupper($rn['objeto_id'])] = $rn['nombre']; }
    }

    $filas = array();
    foreach ($premios as $p) {
        $recs = array();
        foreach ($p['recompensas'] as $r) {
            $recs[] = sg_gacha_recompensa_texto($r, $obj_nombres);
        }
        $filas[] = array(
            'nombre'       => $p['nombre'],
            'es_jackpot'   => (bool) $p['es_jackpot'],
            'probabilidad' => (float) $p['probabilidad'],
            'recompensas'  => $recs,
        );
    }
    usort($filas, function ($a, $b) { return $b['probabilidad'] <=> $a['probabilidad']; });
    return $filas;
}

// Sorteo ponderado: elige un premio de la lista según su `probabilidad`.
// Asume que la lista ya pasó sg_gacha_probabilidad_lista() (100% exacto);
// aun así normaliza sobre el total real por robustez.
// Devuelve null si la lista está vacía o el total de probabilidad es 0.
function sg_gacha_elegir_premio($premios) {
    $total = 0.0;
    foreach ($premios as $p) { $total += (float) $p['probabilidad']; }
    if (empty($premios) || $total <= 0) { return null; }

    // Precisión de centésimas (columna es decimal(5,2)).
    $r = mt_rand(1, (int) round($total * 100)) / 100;
    $acumulado = 0.0;
    foreach ($premios as $p) {
        $acumulado += (float) $p['probabilidad'];
        if ($r <= $acumulado) { return $p; }
    }
    return $premios[count($premios) - 1]; // fallback por redondeo
}

// Abre un pergamino para $uid: consume 1 del inventario y sortea premio(s).
// Todo el sorteo se decide en el servidor (nunca confiar en el cliente).
// Devuelve array('ok'=>bool, 'msg'=>string en error, 'premios'=>[...],
// 'restante'=>int) donde cada premio ganado es
// array('nombre'=>string, 'es_jackpot'=>bool, 'recompensas'=>[
//   array('tipo','valor','objeto_id','objeto_nombre','cantidad')
// ]).
function sg_gacha_abrir($uid, $pergamino_id) {
    global $db;
    $uid = (int) $uid;

    if (!in_array($pergamino_id, sg_gacha_pergamino_ids(), true)) {
        return array('ok' => false, 'msg' => 'Pergamino inválido.');
    }

    $lock = "sg_pergamino_$uid";
    $got = 0;
    $rl = $db->query("SELECT GET_LOCK('$lock', 5) AS l");
    while ($r = $db->fetch_array($rl)) { $got = (int) $r['l']; }
    if ($got !== 1) {
        return array('ok' => false, 'msg' => 'No se pudo procesar la apertura. Intenta de nuevo.');
    }

    $resultado = sg_gacha_abrir_bajo_lock($uid, $pergamino_id);

    $db->query("SELECT RELEASE_LOCK('$lock')");
    return $resultado;
}

// Cuerpo real de sg_gacha_abrir(); corre siempre dentro del candado.
function sg_gacha_abrir_bajo_lock($uid, $pergamino_id) {
    global $db;
    $pid_esc = $db->escape_string($pergamino_id);

    $tiene = 0;
    $q = $db->query("SELECT cantidad FROM `mybb_sg_sg_inventario` WHERE uid='$uid' AND objeto_id='$pid_esc'");
    while ($r = $db->fetch_array($q)) { $tiene = (int) $r['cantidad']; }
    if ($tiene <= 0) {
        return array('ok' => false, 'msg' => 'No tienes ese pergamino.');
    }

    $premios = sg_gacha_premios($pergamino_id);
    if (empty($premios)) {
        return array('ok' => false, 'msg' => 'Este pergamino todavía no tiene premios configurados.');
    }

    // La probabilidad activa debe sumar EXACTAMENTE 100% para poder abrir (si
    // no, el sorteo estaría mal calibrado). Se revalida acá server-side —no
    // basta con ocultar el botón en sg/pergaminos.php— por si se llama a este
    // endpoint directamente. Tolerancia de 0.01 por redondeo de punto flotante.
    if (!sg_gacha_probabilidad_lista($pergamino_id)) {
        return array('ok' => false, 'msg' => 'Este pergamino todavía no está listo para abrirse.');
    }

    $premios_sin_jackpot = array_values(array_filter($premios, function ($p) { return !$p['es_jackpot']; }));

    // Elige el premio principal, y si es jackpot, tiradas extra gratis sobre
    // el pool SIN jackpots (evita encadenar jackpots infinitos).
    $ganados = array();
    $principal = sg_gacha_elegir_premio($premios);
    if ($principal === null) {
        return array('ok' => false, 'msg' => 'Este pergamino todavía no tiene premios configurados.');
    }
    $ganados[] = $principal;

    if ((int) $principal['es_jackpot'] === 1) {
        $tiradas = max(1, (int) $principal['jackpot_tiradas']);
        for ($i = 0; $i < $tiradas; $i++) {
            $extra = sg_gacha_elegir_premio($premios_sin_jackpot);
            if ($extra !== null) { $ganados[] = $extra; }
        }
    }

    $grupo = uniqid();
    $detalle = "Abrió $pergamino_id";

    // Consume el pergamino (comparte $grupo con las recompensas de abajo, así
    // el tab Historial de la ficha muestra todo el evento agrupado).
    $db->query("START TRANSACTION");
    sg_inventario_quitar_objeto($uid, $pergamino_id, 1, 'usuario', SG_ORIGEN_PERGAMINO, $detalle, $grupo);

    // Una fila por apertura, para las estadísticas globales (sg_gacha_estadisticas).
    // Las tiradas extra de un jackpot no generan filas propias; quedan reflejadas
    // en el es_jackpot de esta fila principal.
    $db->query("
        INSERT INTO `mybb_sg_sg_gacha_log` (`uid`, `pergamino_id`, `premio_id`, `es_jackpot`) VALUES
        ('" . (int) $uid . "', '$pid_esc', '" . (int) $principal['id'] . "', '" . ((int) $principal['es_jackpot']) . "');
    ");

    // Acumula las recompensas de TODOS los premios ganados (principal + jackpot)
    // antes de aplicar, para que monedas repetidas entre tiradas se sumen en
    // un solo UPDATE/fila de historial por moneda.
    $deltas_moneda = array('ryos' => 0, 'rin' => 0, 'madara' => 0, 'tobi' => 0);
    $deltas_objeto = array();
    foreach ($ganados as $premio) {
        foreach ($premio['recompensas'] as $rec) {
            if ($rec['tipo'] === 'objeto') {
                $oid = $rec['objeto_id'];
                if ($oid === null || $oid === '') { continue; }
                $cant = max(1, (int) $rec['cantidad']);
                $deltas_objeto[$oid] = (isset($deltas_objeto[$oid]) ? $deltas_objeto[$oid] : 0) + $cant;
            } else if (isset($deltas_moneda[$rec['tipo']])) {
                $deltas_moneda[$rec['tipo']] += (int) $rec['valor'];
            }
        }
    }

    foreach ($deltas_moneda as $campo => $delta) {
        if ($delta === 0) { continue; }
        $actual = 0;
        $qf = $db->query("SELECT `$campo` AS v FROM `mybb_sg_sg_fichas` WHERE fid='$uid'");
        while ($rf = $db->fetch_array($qf)) { $actual = (int) $rf['v']; }
        sg_ficha_set_campo($uid, $campo, $actual + $delta, 'usuario', SG_ORIGEN_PERGAMINO, $detalle, $grupo);
    }

    foreach ($deltas_objeto as $oid => $cant) {
        sg_inventario_dar_objeto_cantidad($uid, $oid, $cant, 'usuario', SG_ORIGEN_PERGAMINO, $detalle, $grupo);
    }

    $db->query("COMMIT");

    // Resuelve nombres de objetos referenciados, para la respuesta al cliente.
    // Clave normalizada en mayúsculas: el objeto_id cargado en
    // mybb_sg_sg_gacha_recompensas puede tener otro casing que el objeto_id
    // real de mybb_sg_sg_objetos (el WHERE ... IN de abajo igual encuentra la
    // fila porque el collation de MySQL es case-insensitive, pero la clave de
    // ESTE array en PHP no lo es, así que sin normalizar el isset() de más
    // abajo fallaba y mostraba el ID crudo en vez del nombre).
    $obj_ids = array_keys($deltas_objeto);
    $obj_nombres = array();
    $obj_imagenes = array();
    if (!empty($obj_ids)) {
        $in = array();
        foreach ($obj_ids as $x) { $in[] = "'" . $db->escape_string($x) . "'"; }
        $qn = $db->query("SELECT objeto_id, nombre, imagen FROM `mybb_sg_sg_objetos` WHERE objeto_id IN (" . implode(',', $in) . ")");
        while ($rn = $db->fetch_array($qn)) {
            $key = strtoupper($rn['objeto_id']);
            $obj_nombres[$key] = $rn['nombre'];
            $obj_imagenes[$key] = trim($rn['imagen']);
        }
    }

    $premios_out = array();
    foreach ($ganados as $premio) {
        $recompensas_out = array();
        foreach ($premio['recompensas'] as $rec) {
            $obj_key = strtoupper((string) $rec['objeto_id']);
            $recompensas_out[] = array(
                'tipo'          => $rec['tipo'],
                'valor'         => $rec['valor'] !== null ? (int) $rec['valor'] : null,
                'objeto_id'     => $rec['objeto_id'],
                'objeto_nombre' => isset($obj_nombres[$obj_key]) ? $obj_nombres[$obj_key] : $rec['objeto_id'],
                'objeto_imagen' => (isset($obj_imagenes[$obj_key]) && $obj_imagenes[$obj_key] !== '') ? $obj_imagenes[$obj_key] : null,
                'cantidad'      => (int) $rec['cantidad'],
            );
        }
        $premios_out[] = array(
            'nombre'      => $premio['nombre'],
            'es_jackpot'  => (bool) $premio['es_jackpot'],
            'recompensas' => $recompensas_out,
        );
    }

    return array(
        'ok'       => true,
        'premios'  => $premios_out,
        'restante' => $tiene - 1,
    );
}

// Estadísticas globales de aperturas (panel público en sg/pergaminos.php).
// Devuelve array('total'=>int, 'jackpots'=>int, 'por_pergamino'=>array(pergamino_id=>int)).
function sg_gacha_estadisticas() {
    return sg_gacha_estadisticas_filtro(null);
}

// Mismo shape que sg_gacha_estadisticas() pero acotado a un usuario (panel
// "Mis Estadísticas" en sg/pergaminos.php).
function sg_gacha_estadisticas_usuario($uid) {
    return sg_gacha_estadisticas_filtro((int) $uid);
}

function sg_gacha_estadisticas_filtro($uid) {
    global $db;
    $where = ($uid !== null) ? "WHERE uid='" . (int) $uid . "'" : '';
    $where_and = ($uid !== null) ? "AND uid='" . (int) $uid . "'" : '';

    $total = 0;
    $q = $db->query("SELECT COUNT(*) AS c FROM `mybb_sg_sg_gacha_log` $where");
    while ($r = $db->fetch_array($q)) { $total = (int) $r['c']; }

    $jackpots = 0;
    $q = $db->query("SELECT COUNT(*) AS c FROM `mybb_sg_sg_gacha_log` WHERE es_jackpot='1' $where_and");
    while ($r = $db->fetch_array($q)) { $jackpots = (int) $r['c']; }

    $por_pergamino = array();
    foreach (sg_gacha_pergamino_ids() as $pid) { $por_pergamino[$pid] = 0; }
    $q = $db->query("SELECT pergamino_id, COUNT(*) AS c FROM `mybb_sg_sg_gacha_log` $where GROUP BY pergamino_id");
    while ($r = $db->fetch_array($q)) {
        if (isset($por_pergamino[$r['pergamino_id']])) { $por_pergamino[$r['pergamino_id']] = (int) $r['c']; }
    }

    return array('total' => $total, 'jackpots' => $jackpots, 'por_pergamino' => $por_pergamino);
}

// Últimas $limite aperturas de TODOS los usuarios (historial global). Cada
// fila trae ya resuelto el nombre del premio y el username, para no tener
// que hacer lookups aparte al armar el feed.
function sg_gacha_historial_global($limite = 100) {
    global $db;
    $limite = max(1, (int) $limite);

    $rows = array();
    $q = $db->query("
        SELECT l.uid, l.pergamino_id, l.es_jackpot, l.tiempo, p.nombre AS premio_nombre, u.username
        FROM `mybb_sg_sg_gacha_log` l
        LEFT JOIN `mybb_sg_sg_gacha_premios` p ON p.id = l.premio_id
        LEFT JOIN `mybb_sg_users` u ON u.uid = l.uid
        ORDER BY l.id DESC
        LIMIT $limite
    ");
    while ($r = $db->fetch_array($q)) { $rows[] = $r; }
    return $rows;
}

// Últimas $limite aperturas de UN usuario (historial personal).
function sg_gacha_historial_usuario($uid, $limite = 100) {
    global $db;
    $uid = (int) $uid;
    $limite = max(1, (int) $limite);

    $rows = array();
    $q = $db->query("
        SELECT l.pergamino_id, l.es_jackpot, l.tiempo, p.nombre AS premio_nombre
        FROM `mybb_sg_sg_gacha_log` l
        LEFT JOIN `mybb_sg_sg_gacha_premios` p ON p.id = l.premio_id
        WHERE l.uid='$uid'
        ORDER BY l.id DESC
        LIMIT $limite
    ");
    while ($r = $db->fetch_array($q)) { $rows[] = $r; }
    return $rows;
}

// Regala +1 de $pergamino_id a TODAS las cuentas con ficha (uso exclusivo del
// botón de Staff en sg/pergaminos.php, gateado a un uid fijo ahí). No pasa
// por sg_gacha_log (eso es solo para aperturas reales) — el rastro de este
// evento masivo queda en el Historial de cada ficha (origen=pergamino_regalo)
// y en la auditoría de consola. Devuelve la cantidad de fichas afectadas.
function sg_gacha_regalar_global($pergamino_id, $actor_uid) {
    global $db;
    if (!in_array($pergamino_id, sg_gacha_pergamino_ids(), true)) { return 0; }

    $fids = array();
    $q = $db->query("SELECT fid FROM `mybb_sg_sg_fichas`");
    while ($r = $db->fetch_array($q)) { $fids[] = (int) $r['fid']; }
    if (empty($fids)) { return 0; }

    $grupo = uniqid();
    $detalle = "Regalo global de Staff: $pergamino_id";

    $db->query("START TRANSACTION");
    foreach ($fids as $fid) {
        sg_inventario_dar_objeto($fid, $pergamino_id, 'staff', SG_ORIGEN_PERGAMINO_REGALO, $detalle, $grupo, $actor_uid);
    }
    $db->query("COMMIT");

    return count($fids);
}

// ── XP por actividad de foro (posts / temas) ────────────────────────────────
// newpoints_addpoints() (inc/plugins/newpoints.php) llama a sg_historial_post_xp
// con los valores de exp/rin YA calculados: esta función solo LOGUEA, no re-hace
// el UPDATE (newpoints ya actualiza fichas.rin y users.newpoints por su cuenta).
// El contexto (origen/pid/tid) llega por parámetros desde cada hook instrumentado,
// así no hay fugas de estado entre llamadas. Ver docs/instrucciones_cambios.md §2.1.
//
// Al CREAR un post/tema el pid aún no existe cuando se otorga la XP (se asigna
// después del hook): esos casos pasan $defer=true y se acumulan en un buffer que
// el hook datahandler_post_insert_post_end vuelca con el pid ya conocido, vía
// sg_historial_post_xp_flush().
$GLOBALS['sg_np_buffer'] = array();

function sg_historial_post_xp($uid, $exp_old, $exp_new, $rin_old, $rin_new, $origen, $pid = null, $tid = null, $defer = false) {
    $uid = (int) $uid;
    $grupo = uniqid();

    // Una fila por moneda que cambió; el actor de una ganancia de foro es el
    // propio usuario (su ficha cambia por su actividad).
    $filas = array();
    if ((float) $exp_old != (float) $exp_new) { $filas[] = array('users',  'newpoints', $exp_old, $exp_new); }
    if ((float) $rin_old != (float) $rin_new) { $filas[] = array('fichas', 'rin',       $rin_old, $rin_new); }
    if (empty($filas)) { return; }

    foreach ($filas as $f) {
        if ($defer) {
            $GLOBALS['sg_np_buffer'][] = array(
                'uid' => $uid, 'tabla' => $f[0], 'campo' => $f[1],
                'va' => $f[2], 'vn' => $f[3], 'origen' => $origen,
                'grupo' => $grupo, 'tid' => $tid,
            );
        } else {
            sg_historial_ficha_log($uid, $f[0], $f[1], $f[2], $f[3], 'usuario', $origen, '', $grupo, $uid, $pid, $tid);
        }
    }
}

// Vuelca el buffer diferido con el pid ya asignado (hook datahandler_post_insert_post_end).
function sg_historial_post_xp_flush($pid, $tid = null) {
    if (empty($GLOBALS['sg_np_buffer'])) { return; }
    foreach ($GLOBALS['sg_np_buffer'] as $e) {
        $use_tid = ($e['tid'] !== null) ? $e['tid'] : $tid;
        sg_historial_ficha_log($e['uid'], $e['tabla'], $e['campo'], $e['va'], $e['vn'], 'usuario', $e['origen'], '', $e['grupo'], $e['uid'], $pid, $use_tid);
    }
    $GLOBALS['sg_np_buffer'] = array();
}

// ── Feed del historial para el tab "Historial" de la ficha ──────────────────
// Etiqueta legible de un origen (SG_ORIGEN_*).
function sg_origen_label($origen) {
    $map = array(
        'modificar_ficha'   => 'Edición de ficha',
        'ficha_atributos'   => 'Atributos',
        'recompensa_staff'  => 'Recompensa de Staff',
        'recompensa_mision' => 'Recompensa de misión',
        'aprobacion'        => 'Aprobación de ficha',
        'tienda'            => 'Tienda',
        'tienda_rins'       => 'Tienda de Rins',
        'tienda_tobis'      => 'Tienda de Tobis',
        'intercambio'       => 'Intercambio',
        'nueva_ficha'       => 'Creación de ficha',
        'vender'            => 'Venta',
        'dojo'              => 'Dojo',
        'ficha_tecnicas'    => 'Técnicas (Staff)',
        'ficha_objetos'     => 'Objetos (Staff)',
        'mision_entrenamiento' => 'Misión de entrenamiento',
        'recompensa_diaria'    => 'Recompensa diaria',
        'ficha_editada'        => 'Reparto de estadísticas',
        'trasfondo'            => 'Edición de trasfondo',
        'consumir'             => 'Objeto consumido',
        'pergamino'            => 'Apertura de pergamino',
        'pergamino_regalo'     => 'Regalo de pergamino (Staff)',
        'post_nuevo'        => 'Nuevo post',
        'post_editado'      => 'Post editado',
        'post_borrado'      => 'Post borrado',
        'post_aprobado'     => 'Post aprobado',
        'post_desaprobado'  => 'Post desaprobado',
        'tema_nuevo'        => 'Nuevo tema',
        'tema_borrado'      => 'Tema borrado',
        'tema_aprobado'     => 'Tema aprobado',
        'tema_desaprobado'  => 'Tema desaprobado',
    );
    return isset($map[$origen]) ? $map[$origen] : $origen;
}

// Etiqueta legible de un campo escalar.
function sg_campo_label($campo) {
    $map = array(
        'ryos' => 'Ryos', 'tobi' => 'Tobi', 'rin' => 'Rin', 'madara' => 'Madara',
        'newpoints' => 'Experiencia', 'pe' => 'PE', 'reputacion' => 'Reputación',
        'puntos_habilidad' => 'Puntos de habilidad', 'nivel' => 'Nivel',
        'moderated' => 'Estado de moderación', 'rango' => 'Rango',
        'arboles_progreso' => 'Progreso de árboles',
        'vida' => 'Vida', 'chakra' => 'Chakra', 'puntos_estadistica' => 'Puntos de estadística',
        'mejoras' => 'Mejoras', 'fuerza' => 'Fuerza', 'destreza' => 'Destreza',
        'cchakra' => 'Control de chakra', 'inteligencia' => 'Inteligencia',
        'mfuerza' => 'Mod. fuerza', 'mdestreza' => 'Mod. destreza',
        'mcchakra' => 'Mod. control de chakra', 'minteligencia' => 'Mod. inteligencia',
        'salud' => 'Salud', 'velocidad' => 'Velocidad', 'tenketsu' => 'Tenketsu', 'sigilo' => 'Sigilo',
        'espe' => 'Especialización', 'espe_estilo' => 'Estilo',
        'historia' => 'Biografía', 'apariencia' => 'Apariencia', 'personalidad' => 'Personalidad',
        'frase' => 'Frase', 'extra' => 'Extra',
    );
    return isset($map[$campo]) ? $map[$campo] : ucfirst(str_replace('_', ' ', $campo));
}

// Recorta un string a $n caracteres (UTF-8 seguro) con elipsis.
function sg_hist_trim($s, $n = 44) {
    $s = (string) $s;
    if (function_exists('mb_strlen')) {
        return (mb_strlen($s, 'UTF-8') > $n) ? mb_substr($s, 0, $n, 'UTF-8') . '…' : $s;
    }
    return (strlen($s) > $n) ? substr($s, 0, $n) . '…' : $s;
}

// Campos de trasfondo: texto largo, se muestran completos con "Leer más" en
// vez de recortados con "…" (el usuario necesita poder revisar el texto real).
function sg_campo_es_texto_largo($campo) {
    return in_array($campo, array('historia', 'apariencia', 'personalidad', 'frase', 'extra'), true);
}

// Bloque de texto colapsable (mismo patrón CSS-only checkbox+label que la
// Biografía de la ficha, .fx-bio-*). $id_base debe ser único en la página
// (ej. "va-123" combinando lado + id de la fila del historial).
function sg_hist_texto_clamp_html($id_base, $texto, $umbral = 260) {
    $texto = (string) $texto;
    if ($texto === '') { return '<span class="fx-hist-text-empty">(vacío)</span>'; }
    $texto_html = nl2br(htmlspecialchars($texto));
    $largo = function_exists('mb_strlen') ? (mb_strlen($texto, 'UTF-8') > $umbral) : (strlen($texto) > $umbral);
    if (!$largo) {
        return '<div class="fx-hist-text">' . $texto_html . '</div>';
    }
    $cb_id = 'fx-hist-toggle-' . htmlspecialchars($id_base, ENT_QUOTES);
    return '<input type="checkbox" id="' . $cb_id . '" class="fx-hist-text-toggle-input">'
         . '<div class="fx-hist-text fx-hist-text--clamped">' . $texto_html . '</div>'
         . '<label class="fx-hist-text-more" for="' . $cb_id . '">'
         . '<span class="fx-hist-text-more__more">Leer más ↓</span>'
         . '<span class="fx-hist-text-more__less">Leer menos ↑</span>'
         . '</label>';
}

// Construye el HTML del feed de historial de una ficha (las 3 tablas mezcladas
// cronológicamente, agrupadas por `grupo`, con nombres de técnica/objeto
// resueltos). Devuelve array('html'=>string, 'count'=>int).
function sg_historial_ficha_feed_html($uid, $max_eventos = 150) {
    global $db, $mybb;
    $uid = (int) $uid;
    $bburl = isset($mybb->settings['bburl']) ? $mybb->settings['bburl'] : '';

    $rows = array();
    $q = $db->query("SELECT grupo, tabla, campo, valor_anterior, valor_nuevo, tipo, origen, pid, tid, actor_uid, detalle, tiempo
                     FROM `mybb_sg_sg_historial_ficha` WHERE uid='$uid' ORDER BY id DESC LIMIT 120");
    while ($r = $db->fetch_array($q)) { $r['dominio'] = 'ficha'; $rows[] = $r; }

    $q = $db->query("SELECT grupo, tecnica_id, accion, tipo, origen, actor_uid, tiempo
                     FROM `mybb_sg_sg_historial_tecnicas` WHERE uid='$uid' ORDER BY id DESC LIMIT 120");
    while ($r = $db->fetch_array($q)) { $r['dominio'] = 'tecnica'; $rows[] = $r; }

    $q = $db->query("SELECT grupo, objeto_id, cantidad, tipo, origen, pid, tid, actor_uid, detalle, tiempo
                     FROM `mybb_sg_sg_historial_objetos` WHERE uid='$uid' ORDER BY id DESC LIMIT 120");
    while ($r = $db->fetch_array($q)) { $r['dominio'] = 'objeto'; $rows[] = $r; }

    $q = $db->query("SELECT grupo, pasiva_id, accion, tipo, origen, actor_uid, tiempo
                     FROM `mybb_sg_sg_historial_pasivas` WHERE uid='$uid' ORDER BY id DESC LIMIT 120");
    while ($r = $db->fetch_array($q)) { $r['dominio'] = 'pasiva'; $rows[] = $r; }

    if (empty($rows)) { return array('html' => '', 'count' => 0, 'origenes' => array()); }

    // Resolver nombres de técnicas / objetos / pasivas referenciados.
    $tec_ids = array(); $obj_ids = array(); $pas_ids = array();
    foreach ($rows as $r) {
        if ($r['dominio'] === 'tecnica' && $r['tecnica_id'] !== '') { $tec_ids[$r['tecnica_id']] = true; }
        if ($r['dominio'] === 'objeto'  && $r['objeto_id']  !== '') { $obj_ids[$r['objeto_id']]  = true; }
        if ($r['dominio'] === 'pasiva'  && $r['pasiva_id']  !== '') { $pas_ids[$r['pasiva_id']]  = true; }
    }
    $tec_nombres = array(); $obj_nombres = array(); $pas_nombres = array();
    if (!empty($tec_ids)) {
        $in = array();
        foreach (array_keys($tec_ids) as $x) { $in[] = "'" . $db->escape_string($x) . "'"; }
        $q = $db->query("SELECT tid, nombre FROM `mybb_sg_sg_tecnicas` WHERE tid IN (" . implode(',', $in) . ")");
        while ($r = $db->fetch_array($q)) { $tec_nombres[$r['tid']] = $r['nombre']; }
    }
    if (!empty($obj_ids)) {
        $in = array();
        foreach (array_keys($obj_ids) as $x) { $in[] = "'" . $db->escape_string($x) . "'"; }
        $q = $db->query("SELECT objeto_id, nombre FROM `mybb_sg_sg_objetos` WHERE objeto_id IN (" . implode(',', $in) . ")");
        while ($r = $db->fetch_array($q)) { $obj_nombres[$r['objeto_id']] = $r['nombre']; }
    }
    if (!empty($pas_ids)) {
        $in = array();
        foreach (array_keys($pas_ids) as $x) { $in[] = "'" . $db->escape_string($x) . "'"; }
        $q = $db->query("SELECT pasiva_id, nombre FROM `mybb_sg_sg_pasivas_tobis` WHERE pasiva_id IN (" . implode(',', $in) . ")");
        while ($r = $db->fetch_array($q)) { $pas_nombres[$r['pasiva_id']] = $r['nombre']; }
    }

    // Orden cronológico descendente (por tiempo; estable por id vía orden previo).
    usort($rows, function ($a, $b) {
        $ta = strtotime($a['tiempo']); $tb = strtotime($b['tiempo']);
        if ($ta == $tb) { return 0; }
        return ($ta < $tb) ? 1 : -1;
    });

    // Agrupar por `grupo`; grupo vacío = evento propio (clave sintética).
    $eventos = array(); $orden = array(); $sint = 0;
    foreach ($rows as $r) {
        $g = ($r['grupo'] !== null && $r['grupo'] !== '') ? 'g:' . $r['grupo'] : 's:' . (++$sint);
        if (!isset($eventos[$g])) { $eventos[$g] = array(); $orden[] = $g; }
        $eventos[$g][] = $r;
    }

    $html = ''; $count = 0; $origenes = array();
    foreach ($orden as $g) {
        if ($count >= $max_eventos) { break; }
        $count++;
        $grp   = $eventos[$g];
        $first = $grp[0];
        $tipo  = ($first['tipo'] === 'staff') ? 'staff' : 'usuario';
        $origen_raw = $first['origen'];
        $fecha = htmlspecialchars(substr($first['tiempo'], 0, 16));
        $origen_lbl = htmlspecialchars(sg_origen_label($origen_raw));
        $origenes[$origen_raw] = sg_origen_label($origen_raw);

        $enlace = '';
        if (!empty($first['tid'])) {
            $tid = (int) $first['tid'];
            $pid = !empty($first['pid']) ? (int) $first['pid'] : 0;
            $url = $bburl . '/showthread.php?tid=' . $tid . ($pid ? '&pid=' . $pid . '#pid' . $pid : '');
            $enlace = ' <a class="fx-hist-event__link" href="' . htmlspecialchars($url) . '">ver post</a>';
        }

        // Contraparte del intercambio: el detalle ya dice "envío a X" /
        // "recibido de X". Solo se muestra para intercambios (no para otros
        // orígenes, cuyo detalle puede ser una razón de Staff privada).
        $contraparte = '';
        if ($origen_raw === 'intercambio' && isset($first['detalle']) && trim($first['detalle']) !== '') {
            $contraparte = '<span class="fx-hist-event__detalle">' . htmlspecialchars($first['detalle']) . '</span>';
        }

        $cambios = '';
        foreach ($grp as $r) {
            if ($r['dominio'] === 'ficha' && sg_campo_es_texto_largo($r['campo'])) {
                $camp = htmlspecialchars(sg_campo_label($r['campo']));
                $va_html = sg_hist_texto_clamp_html('va-' . $r['id'], $r['valor_anterior']);
                $vn_html = sg_hist_texto_clamp_html('vn-' . $r['id'], $r['valor_nuevo']);
                $cambios .= '<li class="fx-hist-change fx-hist-change--texto">'
                          . '<span class="fx-hist-change__k">' . $camp . '</span>'
                          . '<div class="fx-hist-text-block"><span class="fx-hist-text-label">Antes</span>' . $va_html . '</div>'
                          . '<div class="fx-hist-text-block"><span class="fx-hist-text-label">Después</span>' . $vn_html . '</div>'
                          . '</li>';
            } else if ($r['dominio'] === 'ficha') {
                $camp = htmlspecialchars(sg_campo_label($r['campo']));
                $va = $r['valor_anterior']; $vn = $r['valor_nuevo'];
                $delta = '';
                if (is_numeric($va) && is_numeric($vn)) {
                    $d = (float) $vn - (float) $va;
                    if ($d != 0) {
                        $sign = ($d > 0) ? 'pos' : 'neg';
                        $dtxt = ($d > 0 ? '+' : '') . rtrim(rtrim(sprintf('%.2f', $d), '0'), '.');
                        $delta = ' <span class="fx-hist-delta fx-hist-delta--' . $sign . '">' . $dtxt . '</span>';
                    }
                }
                $va_s = htmlspecialchars(sg_hist_trim($va));
                $vn_s = htmlspecialchars(sg_hist_trim($vn));
                $cambios .= '<li class="fx-hist-change"><span class="fx-hist-change__k">' . $camp . '</span> '
                          . '<span class="fx-hist-change__v">' . $va_s . ' → ' . $vn_s . '</span>' . $delta . '</li>';
            } else if ($r['dominio'] === 'tecnica') {
                $nom = isset($tec_nombres[$r['tecnica_id']]) ? $tec_nombres[$r['tecnica_id']] : $r['tecnica_id'];
                $nom = htmlspecialchars($nom);
                $es_quitar = ($r['accion'] === 'quitar');
                $verbo = $es_quitar ? 'Quitó técnica' : 'Aprendió técnica';
                $cls   = $es_quitar ? 'neg' : 'pos';
                $cambios .= '<li class="fx-hist-change"><span class="fx-hist-change__k fx-hist-change__k--' . $cls . '">' . $verbo . '</span> '
                          . '<span class="fx-hist-change__v">' . $nom . '</span></li>';
            } else if ($r['dominio'] === 'pasiva') {
                $nom = isset($pas_nombres[$r['pasiva_id']]) ? $pas_nombres[$r['pasiva_id']] : $r['pasiva_id'];
                $nom = htmlspecialchars($nom);
                $es_quitar = ($r['accion'] === 'quitar');
                $verbo = $es_quitar ? 'Quitó pasiva' : 'Compró pasiva';
                $cls   = $es_quitar ? 'neg' : 'pos';
                $cambios .= '<li class="fx-hist-change"><span class="fx-hist-change__k fx-hist-change__k--' . $cls . '">' . $verbo . '</span> '
                          . '<span class="fx-hist-change__v">' . $nom . '</span></li>';
            } else {
                $nom = isset($obj_nombres[$r['objeto_id']]) ? $obj_nombres[$r['objeto_id']] : $r['objeto_id'];
                $nom = htmlspecialchars($nom);
                $c = (int) $r['cantidad'];
                $sign = ($c >= 0) ? 'pos' : 'neg';
                $ctxt = ($c > 0 ? '+' : '') . $c;
                $cambios .= '<li class="fx-hist-change"><span class="fx-hist-change__k fx-hist-change__k--' . $sign . '">' . $ctxt . '</span> '
                          . '<span class="fx-hist-change__v">' . $nom . '</span></li>';
            }
        }

        $html .= '<article class="fx-hist-event" data-tipo="' . $tipo . '" data-origen="' . htmlspecialchars($origen_raw, ENT_QUOTES) . '">'
               . '<div class="fx-hist-event__head">'
               . '<span class="fx-hist-event__date">' . $fecha . '</span>'
               . '<span class="fx-hist-badge fx-hist-badge--' . $tipo . '">' . (($tipo === 'staff') ? 'Staff' : 'Usuario') . '</span>'
               . '<span class="fx-hist-event__origen">' . $origen_lbl . '</span>'
               . $contraparte
               . $enlace
               . '</div>'
               . '<ul class="fx-hist-changes">' . $cambios . '</ul>'
               . '</article>';
    }

    asort($origenes);
    return array('html' => $html, 'count' => $count, 'origenes' => $origenes);
}

// ============================================================================
// Intercambios entre jugadores (regalo unilateral). Ver docs/intercambios_diseno.md.
// El movimiento real de ryos/objetos va por los choke points del historial
// (sg_ficha_set_campo / sg_inventario_*) con origen SG_ORIGEN_INTERCAMBIO; estas
// funciones son las validaciones de elegibilidad y el registro visual.
// ============================================================================

// ¿El TID es un tema válido para intercambio? Existe, es visible, y su foro está
// dentro de la zona de rol: foro 37 o cualquier descendiente (subforo, subforo
// de subforo, etc.). Se usa el chequeo robusto por elemento sobre parentlist
// (que cubre también un tema DIRECTO en el foro 37), superset de la convención
// del repo `parentlist LIKE '37,%'` (censo.php / recompensa_diaria.php).
function sg_tid_zona_rol($tid) {
    global $db;
    $tid = (int) $tid;
    if ($tid <= 0) { return false; }
    $ok = false;
    $q = $db->query("
        SELECT 1 AS v
        FROM `mybb_sg_threads` t
        INNER JOIN `mybb_sg_forums` f ON f.fid = t.fid
        WHERE t.tid = '$tid' AND t.visible = 1
          AND (f.fid = 37 OR CONCAT(',', f.parentlist, ',') LIKE '%,37,%')
        LIMIT 1
    ");
    while ($db->fetch_array($q)) { $ok = true; }
    return $ok;
}

// ¿Ambos jugadores tienen posts visibles en ESE tid? (una sola pasada).
function sg_ambos_en_tema($uid_a, $uid_b, $tid) {
    global $db;
    $uid_a = (int) $uid_a;
    $uid_b = (int) $uid_b;
    $tid   = (int) $tid;
    if ($tid <= 0 || $uid_a <= 0 || $uid_b <= 0) { return false; }
    $a = 0; $b = 0;
    $q = $db->query("
        SELECT
            SUM(uid = '$uid_a') AS a,
            SUM(uid = '$uid_b') AS b
        FROM `mybb_sg_posts`
        WHERE tid = '$tid' AND visible = 1
    ");
    while ($r = $db->fetch_array($q)) { $a = (int) $r['a']; $b = (int) $r['b']; }
    return ($a > 0 && $b > 0);
}

// ¿Dos fichas son de la misma aldea? Compara mybb_sg_sg_fichas.villa (vid).
// Falso si a alguno le falta la ficha o el campo villa está vacío.
function sg_fichas_misma_aldea($uid_a, $uid_b) {
    global $db;
    $uid_a = (int) $uid_a;
    $uid_b = (int) $uid_b;
    $villa_a = null; $villa_b = null;
    $q = $db->query("SELECT fid, villa FROM `mybb_sg_sg_fichas` WHERE fid IN ('$uid_a','$uid_b')");
    while ($r = $db->fetch_array($q)) {
        if ((int) $r['fid'] === $uid_a) { $villa_a = trim($r['villa']); }
        if ((int) $r['fid'] === $uid_b) { $villa_b = trim($r['villa']); }
    }
    if ($villa_a === null || $villa_b === null || $villa_a === '' || $villa_b === '') {
        return false;
    }
    return $villa_a === $villa_b;
}

// Registro global de intercambios (últimos N), con nombres de usuarios y sus
// items (objetos) anidados. Mismo patrón que sg_gacha_historial_global().
function sg_intercambios_global($limite = 100) {
    global $db;
    $limite = max(1, (int) $limite);
    $rows = array();
    $q = $db->query("
        SELECT i.id, i.from_uid, i.to_uid, i.tid, i.ryos, i.tiempo,
               uf.username AS from_username, ut.username AS to_username
        FROM `mybb_sg_sg_intercambios` i
        LEFT JOIN `mybb_sg_users` uf ON uf.uid = i.from_uid
        LEFT JOIN `mybb_sg_users` ut ON ut.uid = i.to_uid
        ORDER BY i.id DESC
        LIMIT $limite
    ");
    while ($r = $db->fetch_array($q)) { $rows[$r['id']] = $r; $rows[$r['id']]['items'] = array(); }
    return sg_intercambios_adjuntar_items($rows);
}

// Registro personal: intercambios que este uid envió o recibió.
function sg_intercambios_usuario($uid, $limite = 100) {
    global $db;
    $uid = (int) $uid;
    $limite = max(1, (int) $limite);
    $rows = array();
    $q = $db->query("
        SELECT i.id, i.from_uid, i.to_uid, i.tid, i.ryos, i.tiempo,
               uf.username AS from_username, ut.username AS to_username
        FROM `mybb_sg_sg_intercambios` i
        LEFT JOIN `mybb_sg_users` uf ON uf.uid = i.from_uid
        LEFT JOIN `mybb_sg_users` ut ON ut.uid = i.to_uid
        WHERE i.from_uid = '$uid' OR i.to_uid = '$uid'
        ORDER BY i.id DESC
        LIMIT $limite
    ");
    while ($r = $db->fetch_array($q)) { $rows[$r['id']] = $r; $rows[$r['id']]['items'] = array(); }
    return sg_intercambios_adjuntar_items($rows);
}

// Anexa los objetos (items) a un set de intercambios ya traídos, resolviendo el
// nombre del objeto por join contra el catálogo (fallback al id crudo si fue
// borrado; clave normalizada en mayúsculas por el casing de gacha_recompensas).
// Devuelve el array como lista (values), preservando el orden por id DESC.
function sg_intercambios_adjuntar_items($rows) {
    global $db;
    if (empty($rows)) { return array(); }

    $ids = implode(',', array_map('intval', array_keys($rows)));
    $obj_ids = array();
    $items_raw = array();
    $q = $db->query("SELECT intercambio_id, objeto_id, cantidad FROM `mybb_sg_sg_intercambios_items` WHERE intercambio_id IN ($ids)");
    while ($r = $db->fetch_array($q)) {
        $items_raw[] = $r;
        if ($r['objeto_id'] !== '') { $obj_ids[] = $r['objeto_id']; }
    }

    $obj_nombres = array();
    if (!empty($obj_ids)) {
        $in = array();
        foreach (array_unique($obj_ids) as $x) { $in[] = "'" . $db->escape_string($x) . "'"; }
        $qn = $db->query("SELECT objeto_id, nombre FROM `mybb_sg_sg_objetos` WHERE objeto_id IN (" . implode(',', $in) . ")");
        while ($rn = $db->fetch_array($qn)) { $obj_nombres[strtoupper($rn['objeto_id'])] = $rn['nombre']; }
    }

    foreach ($items_raw as $r) {
        $iid = $r['intercambio_id'];
        if (!isset($rows[$iid])) { continue; }
        $key = strtoupper((string) $r['objeto_id']);
        $rows[$iid]['items'][] = array(
            'objeto_id' => $r['objeto_id'],
            'nombre'    => isset($obj_nombres[$key]) ? $obj_nombres[$key] : $r['objeto_id'],
            'cantidad'  => (int) $r['cantidad'],
        );
    }

    return array_values($rows);
}