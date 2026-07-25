<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'misiones.php');
require_once "./../global.php";
require "./../inc/config.php";
require_once "./functions/sg_functions.php";

global $templates, $mybb;

$mid = $_POST["mid"];
$mid2 = $_POST["mid2"];
$mid3 = $_POST["mid3"];
$uid_post = $_POST["uid"];
$uid_post2 = $_POST["userid2"];
$uid = $mybb->user['uid'];
$entrenamiento_en_curso = false;
$f_var = null;
$efecto_coste = 1;

$reload_js = "<script>window.location.href = window.location.href;</script>";

// $query_user_entrenamiento = $db->query("
//     SELECT * FROM mybb_sg_sg_entrenamientos_usuarios WHERE uid='$uid'
// ");
// while ($e = $db->fetch_array($query_user_entrenamiento)) {
//     $entrenamiento_en_curso = true;
// }    

$time_now = time();
// $codigo_usuario = get_obj_from_query($db->query("
//     SELECT * FROM mybb_sg_sg_codigos_usuarios WHERE uid='$uid' AND expiracion > $time_now
// "));
// if ($codigo_usuario) {
//     $codigo_admin = select_one_query_with_id('mybb_sg_sg_codigos_admin', 'codigo', $codigo_usuario['codigo']);

//     $categoria = $codigo_admin['categoria'];

//     if ($categoria == 'menosCostePRX2') {
//         $efecto_coste = 2;
//     } else if ($categoria == 'menosCostePRX3') {
//         $efecto_coste = 3;
//     } else if ($categoria == 'menosCostePRX1.5') {
//         $efecto_coste = 1.5;
//     }
// }


if ($entrenamiento_en_curso) {
    echo "<script>alert('Tienes un entrenamiento de técnica en curso, así que no puedes entrenar misiones en este momento.'); window.location.href = 'entrenamientos.php';</script>";
}

if ($mid2) {
    $db->query("
        DELETE FROM mybb_sg_sg_misiones_usuarios WHERE uid='$uid'
    ");
    eval('$reload_script = $reload_js;');
}

if ($mid) {
    $query_ficha = $db->query("
        SELECT * FROM mybb_sg_sg_fichas WHERE fid='$uid'
    ");
    while ($f = $db->fetch_array($query_ficha)) {
        $f_var = $f;
    }
    $nombre = $f_var['nombre'];
    
    $query_mision_post = $db->query("
        SELECT * FROM mybb_sg_sg_misiones_lista WHERE cod='$mid'
    ");

    $tiempo_finaliza = 0;
    while ($m = $db->fetch_array($query_mision_post)) {
        $mision_duracion = $m['time'];
        $tiempo_iniciado = time();
        $tiempo_finaliza = $tiempo_iniciado + intval($mision_duracion);
        if ($tiempo_finaliza != 0) {
            $db->query(" 
                INSERT INTO `mybb_sg_sg_misiones_usuarios` (`cod`,`uid`,`nombre`,`tiempo_iniciado`,`tiempo_finaliza`, `mision_duracion`) VALUES ('$mid','$uid','$nombre','$tiempo_iniciado','$tiempo_finaliza', '$mision_duracion');
            ");
        }
    }
    eval('$reload_script = $reload_js;');
}

if ($mid3) {
    $log = '';
    $tiempo_iniciado = '';
    $tiempo_finaliza = '';

    // Datos de la misión activa (para el registro), leídos antes de borrar.
    $query_user_mision = $db->query("SELECT * FROM mybb_sg_sg_misiones_usuarios WHERE uid='$uid'");
    while ($q = $db->fetch_array($query_user_mision)) {
        $tiempo_iniciado = $q['tiempo_iniciado'];
        $tiempo_finaliza = $q['tiempo_finaliza'];
    }

    // Candado ATÓMICO contra doble reclamo (múltiples pestañas abiertas con el
    // botón disponible): el DELETE es la puerta. Solo el request que REALMENTE
    // borra la fila (affected_rows > 0) otorga la recompensa; el resto no borra
    // nada y no cobra. En MyISAM el DELETE toma lock de tabla, así que las
    // peticiones concurrentes se serializan y solo una gana.
    $db->query("DELETE FROM mybb_sg_sg_misiones_usuarios WHERE uid='$uid'");
    if ($db->affected_rows() > 0) {

        $query_mision = $db->query("
            SELECT * FROM mybb_sg_sg_misiones_lista WHERE id='$mid3'
        ");
        while ($m = $db->fetch_array($query_mision)) {
            $m_var = $m;
        }
        $query_ficha = $db->query("
            SELECT * FROM mybb_sg_sg_fichas WHERE fid='$uid'
        ");
        while ($f = $db->fetch_array($query_ficha)) {
            $f_var = $f;
        }
        $query_usuario = $db->query("
            SELECT * FROM mybb_sg_users WHERE uid='$uid'
        ");
        while ($u = $db->fetch_array($query_usuario)) {
            $u_var = $u;
        }

        $nombre = $f_var['nombre'];
        $old_ph = $f_var['puntos_habilidad'];
        $old_ryos = $f_var['ryos'];
        $old_pr = $u_var['newpoints'];
        // $new_ph = intval($old_ph) + intval($m_var['expt']);
        $new_ryos = intval($old_ryos) + intval($m_var['ryos']);
        $new_pr = floatval($old_pr) + (floatval($m_var['coste']));

        $grupo = uniqid();
        sg_ficha_set_campo($uid, 'ryos', $new_ryos, 'usuario', SG_ORIGEN_MISION_ENTRENAMIENTO, 'Misión de entrenamiento completada', $grupo);
        sg_usuario_set_campo($uid, 'newpoints', $new_pr, 'usuario', SG_ORIGEN_MISION_ENTRENAMIENTO, 'Misión de entrenamiento completada', $grupo);

        $db->query("
            INSERT INTO `mybb_sg_sg_audit_misiones` (`fid`, `nombre`, `mid`, `puntos_habilidad`, `ryos`, `pr`, `tiempo_iniciado`, `tiempo_finaliza`) VALUES 
            ('".$uid."', '$nombre', '".$mid3."', 'x->x', '$old_ryos->".$new_ryos."', '$old_pr->".$new_pr."', '$tiempo_iniciado', '$tiempo_finaliza');
        ");

        $log = "Misión finalizada. \nRyos: $old_ryos->$new_ryos\nPH: $old_ph->$new_ph\nPR: $old_pr->$new_pr\n";
    }

    
    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;');
}

$ficha_existe = false;
$moderated = false;

$query_ficha = $db->query("
    SELECT * FROM mybb_sg_sg_fichas WHERE fid='$uid'
");

while ($f = $db->fetch_array($query_ficha)) {
    $moderated = $f['moderated'] != 'no_moderacion';
    $ficha_existe = true;
    eval('$ficha = $f;');
}

if ($ficha_existe == true && $moderated == true) {
    
    $query_user_mision = $db->query("
        SELECT * FROM mybb_sg_sg_misiones_usuarios WHERE uid='$uid'
    ");
    $mision_en_curso = false;
    $mision_completa = false;
    $mid = 0;
    $tiempo = 0;

    while ($m = $db->fetch_array($query_user_mision)) {

        $efecto = 1;
        // if ($codigo_usuario) {
        //     $codigo_admin = select_one_query_with_id('mybb_sg_sg_codigos_admin', 'codigo', $codigo_usuario['codigo']);

        //     $categoria = $codigo_admin['categoria'];

        //     if ($categoria == 'entrenamientoX2') {
        //         $efecto = 2;
        //     } else if ($categoria == 'entrenamientoX3') {
        //         $efecto = 3;
        //     } else if ($categoria == 'entrenamientoX1.5') {
        //         $efecto = 1.5;
        //     } else if ($categoria == 'entrenamientoX1.02') {
        //         $efecto = 1.02;
        //     }
            
        // }

        $mision_duracion = intval($m['mision_duracion']);
        $extra_time = $mision_duracion - ($mision_duracion * (1 / $efecto)); 

        $tiempo = (intval($m['tiempo_finaliza']) - $extra_time) * 1000; // needed for template
        $mid = $m['cod'];

        if (time() > (intval($m['tiempo_finaliza']) - $extra_time)) {
            $mision_completa = true;
        } else {
            $mision_en_curso = true;
        }
    
    }    

    if ($mision_en_curso) {

        $query_mision = $db->query("
            SELECT * FROM mybb_sg_sg_misiones_lista WHERE id='$mid'
        ");

        while ($m = $db->fetch_array($query_mision)) {
            $m_var = $m;
            eval('$mision = $m_var;');
        }

        eval('$userid = $uid;');
        eval("\$page = \"".$templates->get("sg_mision_en_curso")."\";");
        output_page($page);
    } else {
        $new_cod = 1;

        $query_usuario = $db->query("
            SELECT * FROM mybb_sg_users WHERE uid='$uid'
        ");
        while ($u = $db->fetch_array($query_usuario)) {
            $pr_var = $u['newpoints'];
            eval('$pr = $pr_var;');
        }
        $query_ficha = $db->query("
            SELECT * FROM mybb_sg_sg_fichas WHERE fid='$uid'
        ");
        while ($f = $db->fetch_array($query_ficha)) {
            $f_var = $f;
            // $total_ph = $f['puntos_habilidad'] + $f['str'] + $f['res'] + $f['spd'] + $f['agi'] + $f['dex'] + $f['pres'] + $f['inte'] + $f['ctrl'];
            $nivel = intval($f['nivel']);

            if ($nivel >= 20) {
                $rango = 'S+';
            } else if ($nivel >= 17) {
                $rango = 'S';
            } else if ($nivel >= 15) {
                $rango = 'A+';
            } else if ($nivel >= 12) {
                $rango = 'A';
            } else if ($nivel >= 9) {
                $rango = 'B';
            } else if ($nivel >= 6) {
                $rango = 'C';
            } else if ($nivel >= 3) {
                $rango = 'D';
            } else {
                $rango = 'E';
            }

            $query_mision_rango = $db->query("
                SELECT * FROM mybb_sg_sg_misiones_lista WHERE rango='$rango' ORDER BY RAND() LIMIT 1
            ");
            
            while ($r = $db->fetch_array($query_mision_rango)) {
                $new_cod = $r['cod'];
            }
        }

        $query_mision = $db->query("
            SELECT * FROM mybb_sg_sg_misiones_lista WHERE id='$new_cod'
        ");

        while ($m = $db->fetch_array($query_mision)) {
            $m_var = $m;
            eval('$mision = $m_var;');
        }

        if ($mision_completa) {
            eval("\$page = \"".$templates->get("sg_mision_completa")."\";");
        } else {
            $comenzar_accion_var = "javascript: document.getElementById('misform').submit();";
            
            eval('$comenzar_accion = $comenzar_accion_var;');
            eval("\$page = \"".$templates->get("sg_misiones")."\";");
        }


        eval('$userid = $uid;');
        output_page($page);
    }

} else {
    eval("\$page = \"".$templates->get("sg_ficha_no_existe")."\";");
    output_page($page);
}

