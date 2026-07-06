<?php

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
function sg_dojo_nivel_requerido_arbol($desbloqueo_arboles) {
    $d = (int) $desbloqueo_arboles;
    return $d <= 0 ? 1 : (5 * $d);
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
        $owned[$r['tid']] = true;
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

    // Árboles que el personaje posee (posee la base del árbol).
    $poseidos = array();
    foreach ($catalogo as $arbol => $cat) {
        $base = isset($cat['base']) ? $cat['base'] : null;
        if ($base !== null && isset($owned[$base])) {
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
    foreach ($poseidos as $arbol) {
        $cat = $catalogo[$arbol];
        $ramas_cat = sg_ramas_de_arbol($cat);

        $ramas_out = array();
        $nivel_arbol = 0;
        $espec_pool = array();
        $espec_aprendidas = 0;

        foreach ($ramas_cat as $rama => $rinfo) {
            $rama_base = isset($rinfo['base']) ? $rinfo['base'] : '';
            $desbloqueada = ($rama_base !== '' && $rama_base !== null && isset($owned[$rama_base]));

            $mejoras_cat   = isset($rinfo['mejoras']) ? $rinfo['mejoras'] : array();
            $mejoras_libres = array();
            $mejoras_owned  = 0;
            foreach ($mejoras_cat as $mtid) {
                if (isset($owned[$mtid])) {
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
                if (isset($owned[$etid])) {
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
        $espec_elegibles = array();
        if ($espec_aprendidas < $cupo) {
            foreach ($espec_pool as $etid) {
                if (!isset($owned[$etid])) {
                    $espec_elegibles[] = $etid;
                }
            }
        }

        $arboles[$arbol] = array(
            'nivel_arbol' => $nivel_arbol,
            'ramas'       => $ramas_out,
            'especializaciones' => array(
                'cupo'       => $cupo,
                'aprendidas' => $espec_aprendidas,
                'elegibles'  => array_values(array_unique($espec_elegibles)),
            ),
        );
    }

    // ── Clan Híbrido: topes COMBINADOS entre los dos árboles de clan ──
    // Los dos árboles de clan cuentan, para los límites, como si fueran UN
    // solo clan: 3 ramas y 3 especialidades en total (igual que un clan normal).
    // Solo aplica a híbridos; con un solo clan el comportamiento no cambia.
    $CLAN_RAMAS_MAX = 3;
    $CLAN_ESPEC_MAX = 3;
    $clan_ramas_desbloqueadas = 0;
    $clan_espec_aprendidas    = 0;
    foreach ($clan_arboles as $arbol) {
        if (!isset($arboles[$arbol])) { continue; }
        foreach ($arboles[$arbol]['ramas'] as $r) {
            if (!empty($r['desbloqueada'])) { $clan_ramas_desbloqueadas++; }
        }
        $clan_espec_aprendidas += (int) $arboles[$arbol]['especializaciones']['aprendidas'];
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
    $directos = array();
    foreach (sg_arboles_directos() as $el) {
        if (isset($catalogo[$el]) && !in_array($el, $poseidos, true)) {
            $directos[] = $el;
        }
    }

    // Elementos naturales aún bloqueados (pool de la ruleta).
    $naturales_bloqueados = array();
    foreach (sg_arboles_naturales() as $el) {
        if (isset($catalogo[$el]) && !in_array($el, $poseidos, true)) {
            $naturales_bloqueados[] = $el;
        }
    }

    // ¿Cumple el requisito para desbloquear un árbol? (nivel + Tobis; siempre cuesta)
    $nivel_req = sg_dojo_nivel_requerido_arbol($progreso['desbloqueo_arboles']);
    $puede_desbloquear_arbol = ($nivel >= $nivel_req && $tobi >= $costos['arbol']);

    // Estado de la ruleta elemental.
    $ruleta_razon = null;
    if (count($naturales_bloqueados) === 0) {
        $ruleta_razon = 'Ya desbloqueaste todos los elementos naturales.';
    } else if ($slot_elementales <= 0) {
        $ruleta_razon = 'No te quedan slots elementales.';
    } else if (!$puede_desbloquear_arbol) {
        $ruleta_razon = 'Aún no cumples el requisito para desbloquear un árbol.';
    }
    $ruleta = array(
        'disponible'          => ($slot_elementales > 0 && count($naturales_bloqueados) > 0 && $puede_desbloquear_arbol),
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
                $slots = (int) $estado['slot_elementales'];
                $pool  = $estado['naturales_bloqueados'];
                if (empty($pool)) {
                    $result = $err("Ya desbloqueaste todos los elementos naturales.");
                    break;
                }
                if ($slots <= 0) {
                    $result = $err("No te quedan slots elementales.");
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

            default:
                $result = $err("Acción no reconocida.");
                break;
        }
    }

    $db->query("SELECT RELEASE_LOCK('$lock_name')");
    return $result;
}