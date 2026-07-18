<?php

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
    return ($fuerza * 2) + ($destreza * 1) + ($cchakra * 1) + ($inteligencia * 2) + ($salud * 10) + ($velocidad * 0) + ($tenketsu * 5) + ($sigilo * 5);
}

function calculate_chakra2($fuerza, $destreza, $cchakra, $inteligencia, $salud, $velocidad, $tenketsu, $sigilo) {
    return ($fuerza * 1) + ($destreza * 2) + ($cchakra * 2) + ($inteligencia * 1) + ($salud * 0) + ($velocidad * 10) + ($tenketsu * 5) + ($sigilo * 5);
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

// Inserta una técnica aprendida (idempotente).
function sg_dojo_aprender($db, $uid, $tid) {
    $uid = (int) $uid;
    $tid_esc = $db->escape_string($tid);
    $db->query("INSERT IGNORE INTO mybb_sg_sg_tec_aprendidas (tid, uid) VALUES ('$tid_esc','$uid')");
}

// Persiste tobi + arboles_progreso (y opcionalmente slot_elementales) para una ficha.
function sg_dojo_guardar($db, $uid, $tobi, $progreso, $slot = null) {
    $uid = (int) $uid;
    $tobi = (int) $tobi;
    $prog_json = $db->escape_string(json_encode($progreso, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $set = "tobi='$tobi', arboles_progreso='$prog_json'";
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
                sg_dojo_aprender($db, $uid, $base_tid);
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
                sg_dojo_aprender($db, $uid, $base_tid);
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
                sg_dojo_aprender($db, $uid, $base_tid);
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
                sg_dojo_aprender($db, $uid, $rinfo['base']);
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
                    foreach ($mejoras_in as $mtid) { sg_dojo_aprender($db, $uid, $mtid); }
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
                foreach ($mejoras_in as $mtid) { sg_dojo_aprender($db, $uid, $mtid); }
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
                sg_dojo_aprender($db, $uid, $tid);
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
                sg_dojo_aprender($db, $uid, $rinfo['base']);
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
                sg_dojo_aprender($db, $uid, $rinfo['base']);
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
// que el helper comentado de recompensa_diaria.php.
function sg_inventario_dar_objeto($uid, $objeto_id) {
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
        return $cantidad_nueva;
    }

    $db->query("INSERT INTO `mybb_sg_sg_inventario` (objeto_id, uid, cantidad) VALUES ('$objeto_id_db', '$uid', '1')");
    return 1;
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