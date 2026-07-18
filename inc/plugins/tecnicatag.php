<?php

if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("parse_message_end", "tecnicatag_run");
$plugins->add_hook('datahandler_post_insert_post_end', 'dadotag_newpost');
$plugins->add_hook('datahandler_post_insert_thread_end', 'dadotag_newpost');


function tecnicatag_info()
{
global $mybb;
	return array(
		"name"				=> "Tecnica Tag",
		"description"		=> "Tag para Shinobi Gaiden.",
		"website"			=> "",
		"author"			=> "Kurosame",
		"authorsite"		=> "https://shinobigaiden.net",
		"version"			=> "1.0.0",
		"codename"			=> "tecnicatag",
		"compatibility"		=> "*",
	);
}


function tecnicatag_activate()
{
	global $db, $mybb;
	$query = $db->simple_select('themes', 'tid');
	while($theme = $db->fetch_array($query))
	{
		$estilo = array(
				'name'         => 'spoiler.css',
				'tid'          => $theme['tid'],
				'attachedto'   => 'showthread.php|newthread.php|newreply.php|editpost.php|private.php|announcements.php',
				'stylesheet'   => '.spoiler {background: #f5f5f5;border: 1px solid #bbb;margin-bottom: 5px;border-radius: 5px}
.spoiler_button {background-color: #bab7b7;border-radius: 4px 4px 0 0;border: 1px solid #c2bfbf;display: block;color: #605d5d;font-family: Tahoma;font-size: 11px;font-weight: bold;padding: 10px;text-align: center;text-shadow: 1px 1px 0px #b4b3b3;margin: auto auto;cursor: pointer}
.spoiler_title {text-align: center}
.spoiler_content_title{font-weight: bold;border-bottom:1px dashed #bab7b7}
.spoiler_content {padding: 5px;height: auto;overflow:hidden;width:95%;background: #f5f5f5;word-wrap: break-word}',
			'lastmodified' => TIME_NOW
		);
		$sid = $db->insert_query('themestylesheets', $estilo);
		$db->update_query('themestylesheets', array('cachefile' => "css.php?stylesheet={$sid}"), "sid='{$sid}'", 1);
		require_once MYBB_ADMIN_DIR.'inc/functions_themes.php';
		update_theme_stylesheet_list($theme['tid']);
	}
	
	require MYBB_ROOT.'inc/adminfunctions_templates.php';

    find_replace_templatesets("codebuttons", '#'.preg_quote('<script type="text/javascript">
var partialmode = {$mybb->settings[\'partialmode\']},').'#siU', '<script type="text/javascript" src="{$mybb->asset_url}/jscripts/spoiler.js?ver=1804"></script>
<script type="text/javascript">
var partialmode = {$mybb->settings[\'partialmode\']},');	
    find_replace_templatesets("codebuttons", '#'.preg_quote('{$link}').'#', '{$link},spoiler');
}


function tecnicatag_deactivate()
{
	global $db;
	$db->delete_query('themestylesheets', "name='spoiler.css'");
	$query = $db->simple_select('themes', 'tid');
	while($theme = $db->fetch_array($query))
	{
		require_once MYBB_ADMIN_DIR.'inc/functions_themes.php';
		update_theme_stylesheet_list($theme['tid']);
	}
   	require MYBB_ROOT.'inc/adminfunctions_templates.php';
    find_replace_templatesets("codebuttons", '#'.preg_quote('<script type="text/javascript" src="{$mybb->asset_url}/jscripts/spoiler.js?ver=1804"></script>').'#', '',0);
    find_replace_templatesets("codebuttons", '#'.preg_quote(',spoiler').'#', '',0);
}


// Construye el spoiler de estadísticas de personaje siguiendo STYLE.md (Washi & Plum).
// Separa estadísticas Generales (con modificador) de las Especiales (sin modificador).
function sg_personaje_spoiler($d)
{
	$generales = array(
		'Fuerza'       => array($d['fuerza'],       $d['mfuerza']),
		'Inteligencia' => array($d['inteligencia'], $d['minteligencia']),
		'Destreza'     => array($d['destreza'],     $d['mdestreza']),
		'Ctrl. Chakra' => array($d['cchakra'],      $d['mcchakra']),
	);
	$especiales = array(
		'Salud'     => $d['salud'],
		'Tenketsu'  => $d['tenketsu'],
		'Sigilo'    => $d['sigilo'],
		'Velocidad' => $d['velocidad'],
	);

	$gen_tiles = '';
	foreach ($generales as $label => $vals) {
		$val = $vals[0]; $mod = $vals[1];
		$gen_tiles .= "
				<div style='border:0.5px solid rgba(156,107,204,0.16);background:#171224;padding:9px 10px;'>
					<div style='font-family:Cinzel,serif;font-size:8px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#8a7a9a;'>$label</div>
					<div style='display:flex;align-items:baseline;gap:6px;margin-top:4px;'>
						<span style='font-size:19px;font-weight:700;color:#e8dff8;'>$val</span>
						<span style='font-family:Cinzel,serif;font-size:8px;font-weight:700;letter-spacing:1px;color:#b890e0;'>M: $mod</span>
					</div>
				</div>";
	}

	$esp_tiles = '';
	foreach ($especiales as $label => $val) {
		$esp_tiles .= "
				<div style='border:0.5px solid rgba(156,107,204,0.16);border-left:1.5px solid #7b4ab8;background:#1c162c;padding:9px 10px;'>
					<div style='font-family:Cinzel,serif;font-size:8px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#9c6bcc;'>$label</div>
					<div style='font-size:19px;font-weight:700;color:#e8dff8;margin-top:4px;'>$val</div>
				</div>";
	}

	$nombre = $d['nombre'];
	$vida = $d['vida']; $chakra = $d['chakra']; $regchakra = $d['regchakra'];

	return "[spoiler=Estadísticas de $nombre]
		<div style='border:0.5px solid rgba(156,107,204,0.18);background:#130f1e;padding:16px 18px;color:#e8dff8;'>
			<div style='display:flex;align-items:center;gap:10px;margin-bottom:16px;'>
				<span style='display:inline-block;width:3px;height:18px;background:#c0582a;'></span>
				<span style='font-family:Cinzel,serif;font-size:14px;font-weight:900;letter-spacing:2px;text-transform:uppercase;'>$nombre</span>
			</div>

			<div style='font-family:Cinzel,serif;font-size:9px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#9c6bcc;margin-bottom:8px;'>Estadísticas Generales</div>
			<div style='display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:6px;margin-bottom:16px;'>$gen_tiles
			</div>

			<div style='font-family:Cinzel,serif;font-size:9px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#9c6bcc;margin-bottom:8px;'>Estadísticas Especiales</div>
			<div style='display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:6px;margin-bottom:16px;'>$esp_tiles
			</div>

			<div style='display:flex;flex-wrap:wrap;gap:18px;border-top:0.5px solid rgba(156,107,204,0.12);padding-top:12px;'>
				<span style='font-family:Cinzel,serif;font-size:9px;letter-spacing:1.5px;text-transform:uppercase;color:#8a7a9a;'>Vida <strong class='personaje_vida' style='font-size:13px;color:#e8dff8;'>$vida</strong> [hp]</span>
				<span style='font-family:Cinzel,serif;font-size:9px;letter-spacing:1.5px;text-transform:uppercase;color:#8a7a9a;'>Chakra <strong class='personaje_chakra' style='font-size:13px;color:#e8dff8;'>$chakra</strong> [ch]</span>
				<span style='font-family:Cinzel,serif;font-size:9px;letter-spacing:1.5px;text-transform:uppercase;color:#8a7a9a;'>Reg. Chakra <strong style='font-size:13px;color:#e8dff8;'>$regchakra</strong></span>
			</div>
		</div>
	[/spoiler]
	";
}

function tecnicatag_run(&$message)
{
	global $db, $post;


	while(preg_match('#\[personaje=(.*?)\]#si',$message,$matches))
	{
		$uid = $post['uid'];
		$tid = $matches[1];
		$thread_ficha = null;
		$personaje_message = "[personajeinvalido=$tid]";

		$query_personaje = $db->query("
			SELECT * FROM mybb_sg_sg_thread_personaje WHERE tid='$tid' AND uid='$uid'
		");
		while ($q = $db->fetch_array($query_personaje)) {
			$thread_ficha = $q;
		}

		if (!$thread_ficha) {
			$message = preg_replace("#\[personaje=$tid\]#si","$personaje_message",$message);
		} else {
			// Pasivas: base + pas_* -> efectivas (mods/vida/chakra/reg recalculados)
			sg_aplicar_pasivas($thread_ficha);
			$nombre = $thread_ficha['nombre'];
			$fuerza = $thread_ficha['fuerza'];
			$destreza = $thread_ficha['destreza'];
			$cchakra = $thread_ficha['cchakra'];
			$inteligencia = $thread_ficha['inteligencia'];
			$salud = $thread_ficha['salud'];
			$velocidad = $thread_ficha['velocidad'];
			$tenketsu = $thread_ficha['tenketsu'];
			$sigilo = $thread_ficha['sigilo'];
			$mfuerza = $thread_ficha['mfuerza'];
			$mdestreza = $thread_ficha['mdestreza'];
			$mcchakra = $thread_ficha['mcchakra'];
			$minteligencia = $thread_ficha['minteligencia'];
			$vida = $thread_ficha['vida'];
			$chakra = $thread_ficha['chakra'];
			$regchakra = $thread_ficha['regchakra'];

			$personaje_message = sg_personaje_spoiler(array(
				'nombre' => $nombre,
				'fuerza' => $fuerza, 'destreza' => $destreza, 'cchakra' => $cchakra, 'inteligencia' => $inteligencia,
				'salud' => $salud, 'velocidad' => $velocidad, 'tenketsu' => $tenketsu, 'sigilo' => $sigilo,
				'mfuerza' => $mfuerza, 'mdestreza' => $mdestreza, 'mcchakra' => $mcchakra, 'minteligencia' => $minteligencia,
				'vida' => $vida, 'chakra' => $chakra, 'regchakra' => $regchakra,
			));

			$message = preg_replace("#\[personaje=$tid\]#si","$personaje_message",$message);
		}
	}

	while(preg_match('#\[mytest\]#si',$message))
	{
		$personaje_message = "[spoiler=Mytest]Mytest[/spoiler]";

		$message = preg_replace('#\[mytest\]#si',$personaje_message,$message);

	}


	while(preg_match('#\[personaje\]#si',$message))
	{
		$uid = $post['uid'];
		$tid = $post['tid'];
		$pid = $post['pid'];
		
		$ficha = null;
		$thread_ficha = null;
		$personaje_message = "";

		$query_personaje = $db->query("
			SELECT * FROM mybb_sg_sg_thread_personaje WHERE tid='$tid' AND uid='$uid'
		");

		while ($q = $db->fetch_array($query_personaje)) {
			$thread_ficha = $q;
		}

		if (!$thread_ficha) {

			$query_ficha = $db->query("
				SELECT * FROM mybb_sg_sg_fichas WHERE fid='$uid'
			");

			while ($q = $db->fetch_array($query_ficha)) {
				$ficha = $q;
			}

			$nombre = $ficha['nombre'];
			$espe = $ficha['espe'];
			$estilo = $ficha['espe_estilo'];
			$maestria = $ficha['maestria'];
			$maestria2 = $ficha['maestria_secundaria'];

			// Estadisticas base (de la ficha) y sus pasivas invisibles.
			$b_fuerza = $ficha['fuerza'];       $pas_fuerza       = sg_pasiva($ficha, 'fuerza');
			$b_destreza = $ficha['destreza'];   $pas_destreza     = sg_pasiva($ficha, 'destreza');
			$b_cchakra = $ficha['cchakra'];     $pas_cchakra      = sg_pasiva($ficha, 'cchakra');
			$b_inteligencia = $ficha['inteligencia']; $pas_inteligencia = sg_pasiva($ficha, 'inteligencia');
			$b_salud = $ficha['salud'];         $pas_salud        = sg_pasiva($ficha, 'salud');
			$b_velocidad = $ficha['velocidad']; $pas_velocidad    = sg_pasiva($ficha, 'velocidad');
			$b_tenketsu = $ficha['tenketsu'];   $pas_tenketsu     = sg_pasiva($ficha, 'tenketsu');
			$b_sigilo = $ficha['sigilo'];       $pas_sigilo       = sg_pasiva($ficha, 'sigilo');

			// Valores EFECTIVOS (base + pasiva) para mostrar y para los derivados.
			$eff = sg_stats_efectivas($ficha);
			$fuerza = $eff['fuerza'];       $mfuerza = $eff['mfuerza'];
			$destreza = $eff['destreza'];   $mdestreza = $eff['mdestreza'];
			$cchakra = $eff['cchakra'];     $mcchakra = $eff['mcchakra'];
			$inteligencia = $eff['inteligencia']; $minteligencia = $eff['minteligencia'];
			$salud = $eff['salud'];
			$velocidad = $eff['velocidad'];
			$tenketsu = $eff['tenketsu'];
			$sigilo = $eff['sigilo'];
			$vida = $eff['vida'];
			$chakra = $eff['chakra'];
			$regchakra = $eff['regchakra'];

			if ($tid && $pid) {
				// El snapshot congela BASE + pasivas (para no doblar el efecto al
				// re-mostrar) y guarda vida/chakra/reg/mods efectivos ya calculados.
				$db->query("
					INSERT INTO `mybb_sg_sg_thread_personaje` (`tid`, `pid`, `uid`, `nombre`,
						`vida`, `chakra`, `regchakra`,
						`fuerza`, `destreza`, `cchakra`, `inteligencia`, `salud`, `velocidad`, `tenketsu`, `sigilo`,
						`pas_fuerza`, `pas_destreza`, `pas_cchakra`, `pas_inteligencia`, `pas_salud`, `pas_velocidad`, `pas_tenketsu`, `pas_sigilo`,
						`mfuerza`, `mdestreza`, `mcchakra`, `minteligencia`,
						`espe`, `estilo`, `maestria`, `maestria2`)
					VALUES ('$tid', '$pid', '$uid', '$nombre',
						'$vida', '$chakra', '$regchakra',
						'$b_fuerza', '$b_destreza', '$b_cchakra', '$b_inteligencia', '$b_salud', '$b_velocidad', '$b_tenketsu', '$b_sigilo',
						'$pas_fuerza', '$pas_destreza', '$pas_cchakra', '$pas_inteligencia', '$pas_salud', '$pas_velocidad', '$pas_tenketsu', '$pas_sigilo',
						'$mfuerza', '$mdestreza', '$mcchakra', '$minteligencia',
						'$espe', '$estilo', '$maestria', '$maestria2');
				");
			}

		} else {
			// Pasivas: base + pas_* -> efectivas (mods/vida/chakra/reg recalculados)
			sg_aplicar_pasivas($thread_ficha);
			$nombre = $thread_ficha['nombre'];
			$fuerza = $thread_ficha['fuerza'];
			$destreza = $thread_ficha['destreza'];
			$cchakra = $thread_ficha['cchakra'];
			$inteligencia = $thread_ficha['inteligencia'];
			$salud = $thread_ficha['salud'];
			$velocidad = $thread_ficha['velocidad'];
			$tenketsu = $thread_ficha['tenketsu'];
			$sigilo = $thread_ficha['sigilo'];
			$mfuerza = $thread_ficha['mfuerza'];
			$mdestreza = $thread_ficha['mdestreza'];
			$mcchakra = $thread_ficha['mcchakra'];
			$minteligencia = $thread_ficha['minteligencia'];
			$vida = $thread_ficha['vida'];
			$chakra = $thread_ficha['chakra'];
			$regchakra = $thread_ficha['regchakra'];
		}

		$personaje_message = sg_personaje_spoiler(array(
			'nombre' => $nombre,
			'fuerza' => $fuerza, 'destreza' => $destreza, 'cchakra' => $cchakra, 'inteligencia' => $inteligencia,
			'salud' => $salud, 'velocidad' => $velocidad, 'tenketsu' => $tenketsu, 'sigilo' => $sigilo,
			'mfuerza' => $mfuerza, 'mdestreza' => $mdestreza, 'mcchakra' => $mcchakra, 'minteligencia' => $minteligencia,
			'vida' => $vida, 'chakra' => $chakra, 'regchakra' => $regchakra,
		));

		$message = preg_replace('#\[personaje\]#si',$personaje_message,$message);
	}

	while(preg_match('#\[cerrado\]#si',$message))
	{
		$tid = $post['tid'];
		$db->query("
			UPDATE mybb_sg_threads SET `closed`='1' WHERE tid='$tid'
		");
		$message = preg_replace('#\[cerrado\]#si','<div style=" text-align: center;border: 1px dotted #61567e; "><h1>Este tema ha sido cerrado.</h1></div>',$message);
	}

	while(preg_match('#\[hp\]#si',$message))
	{
		$hp = '<img title="Vida" style=" width: 13px; top: -1px; position: relative; " src="./images/sg/icons/vida.png" />';
		$message = preg_replace('#\[hp\]#si',$hp,$message);
	}

	while(preg_match('#\[ch\]#si',$message))
	{
		$ch = '<img title="Chakra" style=" width: 13px; top: -1px; position: relative; " src="./images/sg/icons/chakra.png" />';
		$message = preg_replace('#\[ch\]#si',$ch,$message);
	}
	
	while(preg_match('#\[tiempo=(.*?)\]#si',$message,$matches))
	{
		$dateline = $post['dateline'];
		$tiempo = $matches[1];

		if (intval($tiempo) > 0 && intval($tiempo) < 8760) {

			$timeNow = time();
			$timeAfter = intval($dateline) + (intval($tiempo) * 3600);

			// <div style=" text-align: center;border: 1px dotted #61567e; "><h1>Este tema ha sido cerrado.</h1></div>

			if ($timeNow > $timeAfter) {
				$texto = "<div style=' text-align: center;border: 1px dotted #61567e; '><h3>El tiempo para postear de $tiempo horas ya ha expirado.</h3></div>";
			} else {
				$timeLeft =  round(($timeAfter - $timeNow) / 3600, 2);
				$texto = "<div style=' text-align: center;border: 1px dotted #61567e; '><h3>Este post tiene un límite de $tiempo horas.</h3><h3>Quedan $timeLeft horas para postear.</h3></div>";
			}

			$message = preg_replace("#\[tiempo=$tiempo\]#si","$texto",$message);
		} else {
			$message = preg_replace("#\[tiempo=$tiempo\]#si","[tiempoinvalido=$tiempo]",$message);
		}
	}

	while(preg_match('#\[vida=(.*?)\]#si',$message,$matches))
	{
		$pid = $post['pid'];
		
		$vida = $matches[1];
		$vida_pt = explode(",", $vida);

		if (count($vida_pt) == 2) {
			$vida_actual = $vida_pt[0];
			$vida_max = trim($vida_pt[1]);

			if (intval($vida_max) > 0) {
				$max_width = 294;
				$max_width_avatar = 293;

				$actual_width = "0px";
				$remainingChakra = "293px";

				if (intval($vida_actual) <= intval($vida_max)) {
					if (intval($vida_actual) > 0) {
						$actual_width = strval(intval(($vida_actual / $vida_max) * $max_width)) . 'px';
						$remainingChakra = strval(intval(($vida_actual / $vida_max) * $max_width_avatar)) . 'px';
					} 
				} else if (intval($vida_actual) > intval($vida_max)) {
					$actual_width = "294px";
				}
				
				$vida_avatar = "<script>
					$('#post_$pid .personaje_vida')[0].innerText = '$vida_actual/$vida_max';
					$('#post_$pid .subBarraVida').css('width', '$remainingChakra');
				</script>";

				$barra = "
			<div class=\"vidaStatusBar\">
				<div class=\"barrasVida\">
					<div class=\"barraVidaRoja\" style=\"width: 294px\"></div>
					<div class=\"barraVidaVerde\" style=\"width: $actual_width\"></div>
					<span class=\"barraVidaText\">Vida: $vida_actual/$vida_max</span><br />
				</div>
			</div> $vida_avatar";
			} else {
				$message = preg_replace("#\[vida=$vida\]#si","[vidainvalida=$vida]",$message);
			}
	
			$message = preg_replace("#\[vida=$vida\]#si","$barra",$message);
		} else {
			$message = preg_replace("#\[vida=$vida\]#si","[vidainvalida=$vida]",$message);
		}
	}

	while(preg_match('#\[vidaextra=(.*?)\]#si',$message,$matches))
	{
		$vida = $matches[1];
		$vida_pt = explode(",", $vida);

		if (count($vida_pt) == 2) {
			$vida_actual = $vida_pt[0];
			$vida_max = trim($vida_pt[1]);

			if (intval($vida_max) > 0) {
				$max_width = 294;
				$actual_width = "0px";

				if (intval($vida_actual) <= intval($vida_max)) {
					if (intval($vida_actual) > 0) {
						$actual_width = strval(intval(($vida_actual / $vida_max) * $max_width)) . 'px';
					} 
				} else if (intval($vida_actual) > intval($vida_max)) {
					$actual_width = "294px";
				}
				
				$barra = "
			<div class=\"vidaStatusBar\">
				<div class=\"barrasVida\">
					<div class=\"barraVidaRoja\" style=\"width: 294px\"></div>
					<div class=\"barraVidaVerde\" style=\"width: $actual_width\"></div>
					<span class=\"barraVidaText\">Vida: $vida_actual/$vida_max</span><br />
				</div>
			</div>";
			} else {
				$message = preg_replace("#\[vidaextra=$vida\]#si","[vidainvalida=$vida]",$message);
			}
	
			$message = preg_replace("#\[vidaextra=$vida\]#si","$barra",$message);
		} else {
			$message = preg_replace("#\[vidaextra=$vida\]#si","[vidainvalida=$vida]",$message);
		}
	}
	
	while(preg_match('#\[chakra=(.*?)\]#si',$message,$matches))
	{
		$chakra = $matches[1];
		$chakra_pt = explode(",", $chakra);

		if (count($chakra_pt) == 2) {
			$chakra_actual = $chakra_pt[0];
			$chakra_max = trim($chakra_pt[1]);

			if (intval($chakra_max) > 0) {
				$max_width = 294;
				$max_width_avatar = 293;

				$actual_width = "0px";
				$remainingChakra = "293px";

				if (intval($chakra_actual) <= intval($chakra_max)) {
					if (intval($chakra_actual) > 0) {
						$actual_width = strval(intval(($chakra_actual / $chakra_max) * $max_width)) . 'px';
						$remainingChakra = strval(intval(($chakra_actual / $chakra_max) * $max_width_avatar)) . 'px';
					} 
				} else if (intval($chakra_actual) > intval($chakra_max)) {
					$actual_width = "294px";
				}

				$chakra_avatar = "<script>
					$('#post_$pid .personaje_chakra')[0].innerText = '$chakra_actual/$chakra_max';
					$('#post_$pid .subBarraChakra').css('width', '$remainingChakra');
				
					</script>";

				$barra = "
			<div class=\"chakraStatusBar\">
				<div class=\"barrasChakra\">
					<div class=\"barraChakraRoja\" style=\"width: 294px\"></div>
					<div class=\"barraChakraVerde\" style=\"width: $actual_width\"></div>
					<span class=\"barraChakraText\">Chakra: $chakra_actual/$chakra_max</span><br />
				</div>
			</div> $chakra_avatar";
			} else {
				$message = preg_replace("#\[chakra=$chakra\]#si","[chakrainvalido=$chakra]",$message);
			}
	
			$message = preg_replace("#\[chakra=$chakra\]#si","$barra",$message);
		} else {
			$message = preg_replace("#\[chakra=$chakra\]#si","[chakrainvalido=$chakra]",$message);
		}
	}

	while(preg_match('#\[chakraextra=(.*?)\]#si',$message,$matches))
	{
		$chakra = $matches[1];
		$chakra_pt = explode(",", $chakra);

		if (count($chakra_pt) == 2) {
			$chakra_actual = $chakra_pt[0];
			$chakra_max = trim($chakra_pt[1]);

			if (intval($chakra_max) > 0) {
				$max_width = 294;
				$actual_width = "0px";

				if (intval($chakra_actual) <= intval($chakra_max)) {
					if (intval($chakra_actual) > 0) {
						$actual_width = strval(intval(($chakra_actual / $chakra_max) * $max_width)) . 'px';
					} 
				} else if (intval($chakra_actual) > intval($chakra_max)) {
					$actual_width = "294px";
				}
				
				$barra = "
			<div class=\"chakraStatusBar\">
				<div class=\"barrasChakra\">
					<div class=\"barraChakraRoja\" style=\"width: 294px\"></div>
					<div class=\"barraChakraVerde\" style=\"width: $actual_width\"></div>
					<span class=\"barraChakraText\">Chakra: $chakra_actual/$chakra_max</span><br />
				</div>
			</div>";
			} else {
				$message = preg_replace("#\[chakraextra=$chakra\]#si","[chakrainvalido=$chakra]",$message);
			}
	
			$message = preg_replace("#\[chakraextra=$chakra\]#si","$barra",$message);
		} else {
			$message = preg_replace("#\[chakraextra=$chakra\]#si","[chakrainvalido=$chakra]",$message);
		}
	}

	while(preg_match('#\[senjutsu=(.*?)\]#si',$message,$matches))
	{
		$senjutsu = $matches[1];
		$senjutsu_pt = explode(",", $senjutsu);

		if (count($senjutsu_pt) == 2) {
			$senjutsu_actual = $senjutsu_pt[0];
			$senjutsu_max = $senjutsu_pt[1];

			if (intval($senjutsu_max) >= 0) {
				$max_width = 294;
				$actual_width = "0px";

				if (intval($senjutsu_actual) <= intval($senjutsu_max)) {
					if (intval($senjutsu_actual) > 0) {
						$actual_width = strval(intval(($senjutsu_actual / $senjutsu_max) * $max_width)) . 'px';
					} 
				} else if (intval($senjutsu_actual) > intval($senjutsu_max)) {
					$actual_width = "294px";
				}
				
				$barra = "
			<div class=\"senjutsuStatusBar\">
				<div class=\"barrasSenjutsu\">
					<div class=\"barraSenjutsuRoja\" style=\"width: 294px\"></div>
					<div class=\"barraSenjutsuVerde\" style=\"width: $actual_width\"></div>
					<span class=\"barraSenjutsuText\">Senjutsu: $senjutsu_actual/$senjutsu_max</span><br />
				</div>
			</div>";
			} else {
				$message = preg_replace("#\[senjutsu=$senjutsu\]#si","[senjutsuinvalido=$senjutsu]",$message);
			}
	
			$message = preg_replace("#\[senjutsu=$senjutsu\]#si","$barra",$message);
		} else {
			$message = preg_replace("#\[senjutsu=$senjutsu\]#si","[senjutsuinvalido=$senjutsu]",$message);
		}
	}

	while(preg_match('#\[kujaku=(.*?)\]#si',$message,$matches))
	{
		$kujaku = $matches[1];
		$kujaku_pt = explode(",", $kujaku);

		if (count($kujaku_pt) == 2) {
			$kujaku_actual = $kujaku_pt[0];
			$kujaku_max = $kujaku_pt[1];

			if (intval($kujaku_max) >= 0) {
				$max_width = 294;
				$actual_width = "0px";

				if (intval($kujaku_actual) <= intval($kujaku_max)) {
					if (intval($kujaku_actual) > 0) {
						$actual_width = strval(intval(($kujaku_actual / $kujaku_max) * $max_width)) . 'px';
					} 
				} else if (intval($kujaku_actual) > intval($kujaku_max)) {
					$actual_width = "294px";
				}
				
				$barra = "
			<div class=\"kujakuStatusBar\">
				<div class=\"barrasKujaku\">
					<div class=\"barraKujakuRoja\" style=\"width: 294px\"></div>
					<div class=\"barraKujakuVerde\" style=\"width: $actual_width\"></div>
					<span class=\"barraKujakuText\">Kujaku: $kujaku_actual/$kujaku_max</span><br />
				</div>
			</div>";
			} else {
				$message = preg_replace("#\[kujaku=$kujaku\]#si","[kujakuinvalido=$kujaku]",$message);
			}
	
			$message = preg_replace("#\[kujaku=$kujaku\]#si","$barra",$message);
		} else {
			$message = preg_replace("#\[kujaku=$kujaku\]#si","[kujakuinvalido=$kujaku]",$message);
		}
	}

	while(preg_match('#\[byakugo=(.*?)\]#si',$message,$matches))
	{
		$byakugo = $matches[1];
		$byakugo_pt = explode(",", $byakugo);

		if (count($byakugo_pt) == 2) {
			$byakugo_actual = $byakugo_pt[0];
			$byakugo_max = $byakugo_pt[1];

			if (intval($byakugo_max) >= 0) {
				$max_width = 294;
				$actual_width = "0px";

				if (intval($byakugo_actual) <= intval($byakugo_max)) {
					if (intval($byakugo_actual) > 0) {
						$actual_width = strval(intval(($byakugo_actual / $byakugo_max) * $max_width)) . 'px';
					} 
				} else if (intval($byakugo_actual) > intval($byakugo_max)) {
					$actual_width = "294px";
				}
				
				$barra = "
			<div class=\"byakugoStatusBar\">
				<div class=\"barrasByakugo\">
					<div class=\"barraByakugoRoja\" style=\"width: 294px\"></div>
					<div class=\"barraByakugoVerde\" style=\"width: $actual_width\"></div>
					<span class=\"barraByakugoText\">Byakugo no In: $byakugo_actual/$byakugo_max</span><br />
				</div>
			</div>";
			} else {
				$message = preg_replace("#\[byakugo=$byakugo\]#si","[byakugoinvalido=$byakugo]",$message);
			}
	
			$message = preg_replace("#\[byakugo=$byakugo\]#si","$barra",$message);
		} else {
			$message = preg_replace("#\[byakugo=$byakugo\]#si","[byakugoinvalido=$byakugo]",$message);
		}
	}

	while(preg_match('#\[tecnica=(.*?)\]#si',$message,$matches))
	{
		$tecnica = null;
		$tec_tid = strtoupper($matches[1]);

		$query_tecnica = $db->query("
			SELECT * FROM mybb_sg_sg_tecnicas WHERE tid='".$tec_tid."'
		");

		while ($tec = $db->fetch_array($query_tecnica)) {
			$tecnica = $tec;
		}

		if ($tecnica != null) {
			// Réplica de la técnica creada en sg_tecnicas_show3 (sgCreateTechniqueSpoiler + sgCreateTechniqueCard)
			$tecnica['tid'] = $tec_tid;

			// ¿El autor del post tiene la técnica aprendida?
			$tec_aprendida = false;
			$tec_aprendida_tiempo = '';
			$query_aprendida = $db->query("
				SELECT * FROM mybb_sg_sg_tec_aprendidas WHERE uid='".$post['uid']."' AND tid='".$tec_tid."'
			");
			while ($qa = $db->fetch_array($query_aprendida)) {
				$tec_aprendida = true;
				$tec_aprendida_tiempo = $qa['tiempo'];
			}

			// Helper de badge (equivale a sgCreateBadge)
			$badge = function($icon, $text, $accent) {
				if ($text === '' || $text === null) { return ''; }
				$accentClass = $accent ? ' sg-technique__badge--accent' : '';
				return '<div class="sg-technique__badge'.$accentClass.'"><span class="sg-technique__badge-icon">'.$icon.'</span>'.$text.'</div>';
			};

			$badges = '';
			if ($tec_aprendida) {
				$tec_aprendida_fecha = $tec_aprendida_tiempo ? date('d-m-Y', strtotime($tec_aprendida_tiempo)) : '';
				$badges .= $badge('✔', 'Aprendida'.($tec_aprendida_fecha ? ' · '.$tec_aprendida_fecha : ''), true);
			} else {
				$badges .= $badge('✘', 'No aprendida', false);
			}
			// if ($tecnica['rango'])     { $badges .= $badge('◆', 'Rango '.$tecnica['rango'], true); }
			if ($tecnica['requisito']) { $badges .= $badge('•', $tecnica['requisito'], false); }
			if ($tecnica['tipo'])      { $badges .= $badge('◌', $tecnica['tipo'], false); }
			if ($tecnica['categoria'] && $tecnica['categoria'] != $tecnica['tipo']) { $badges .= $badge('◈', $tecnica['categoria'], false); }
			if ($tecnica['nivel'])    { $badges .= $badge('✦', 'Nivel: '.$tecnica['nivel'], false); }
			if ($tecnica['tid']) {
				if ($g_is_staff) {
					$badges .= $badge('#', 'ID: <a href="/sg/admin/modificar_tecnicas.php?tecnica_id='.$tecnica['tid'].'">'.$tecnica['tid'].'</a>', false);
				} else {
					$badges .= $badge('#', 'ID: '.$tecnica['tid'], false);
				}
			}
			if ($tecnica['balance'] == 1) {
				$badges .= $badge('+', 'Balance positivo ✓', false);
			} elseif ($tecnica['balance'] == 2) {
				$badges .= $badge('-', 'Balance negativo ✖', false);
			}

			$badges_html = $badges !== '' ? '<div class="sg-technique__badges">'.$badges.'</div>' : '';
			$description = $tecnica['descripcion'] ? nl2br($tecnica['descripcion']) : 'Sin descripción disponible.';
			// Códigos [CCKx1]/[MDES-10]/etc. del efecto se muestran SIEMPRE ya
			// parseados (sin toggle: un post lo leen muchos usuarios distintos),
			// usando las stats del AUTOR del post (no de quien lee).
			$stats_ef_autor = sg_resolver_stats_autor_post($post);
			$effect_text = $tecnica['efecto'] ? nl2br(sg_parsear_codigos_stats($tecnica['efecto'], $stats_ef_autor)) : 'No tiene efecto adicional especificado.';
			$cost_text   = $tecnica['coste'] ? $tecnica['coste'] : 'Sin coste indicado';
			$effect_muted = $tecnica['efecto'] ? '' : ' sg-technique__text--muted';

			$tecnica_html = <<<TECHTML
<div class="sg-spoiler sg-spoiler--warm sg-tree-technique">
  <button class="sg-spoiler__toggle" type="button" onclick="sgToggleSpoiler(this)" aria-expanded="false">
    <span class="sg-spoiler__lead">
      <span class="sg-spoiler__icon">●</span>
      <span class="sg-spoiler__titles">
        <span class="sg-spoiler__eyebrow">Técnica</span>
        <span class="sg-spoiler__title">{$tecnica['nombre']}</span>
      </span>
    </span>
    <span class="sg-spoiler__aside">
      <span class="sg-spoiler__caret">+</span>
    </span>
  </button>
  <div class="sg-spoiler__panel">
    <div class="sg-spoiler__body">
      <div class="sg-spoiler__content">
        <div class="sg-technique">
          <div class="sg-technique__rail">
            {$badges_html}
            <div class="sg-technique__stat">
              <div class="sg-technique__stat-label"><span class="sg-technique__label-icon">◔</span>Coste</div>
              <div class="sg-technique__stat-value">{$cost_text}</div>
            </div>
          </div>
          <div class="sg-technique__main">
            <div class="sg-technique__section">
              <div class="sg-technique__section-label"><span class="sg-technique__label-icon">▣</span>Descripción</div>
              <p class="sg-technique__text">{$description}</p>
            </div>
            <div class="sg-technique__section">
              <div class="sg-technique__section-label"><span class="sg-technique__label-icon">✧</span>Efecto</div>
              <div class="sg-technique__text{$effect_muted}">{$effect_text}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
if (typeof window.sgToggleSpoiler !== 'function') {
  window.sgToggleSpoiler = function(button) {
    var wrapper = button.parentNode;
    if (!wrapper || !wrapper.classList.contains('sg-spoiler')) { return; }
    var isOpen = wrapper.classList.contains('is-open');
    wrapper.classList.toggle('is-open', !isOpen);
    button.setAttribute('aria-expanded', !isOpen ? 'true' : 'false');
  };
}
</script>
TECHTML;

			$message = str_replace($matches[0], $tecnica_html, $message);
		} else {
			$message = str_replace($matches[0], '[tecnicainvalida='.$matches[1].']', $message);
		}
	}

	while(preg_match('#\[dado_guardado=(.*?)\]#si',$message,$matches))
	{
		$dado_counter = $matches[1];

		$uid = $post['uid'];
		$pid = $post['pid'];
		$tid = $post['tid'];
		$is_edited = $post['edittime'];
		
		$post_editado = "";
		if ($is_edited) {
			$post_editado = "[[Pilas. Este post ha sido editado.]]<br />";
		}

		$query_dado = $db->query("
			SELECT * FROM mybb_sg_sg_dados WHERE pid='".$pid."' AND tid='".$tid."' AND dado_counter='".$dado_counter."'
		");

		$dado = null;
		while ($d = $db->fetch_array($query_dado)) {
			$dado = $d;
		}

		$dado_content = $dado['dado_content'];

		$message = preg_replace('#\[dado_guardado=(.*?)\]#si',"$post_editado $dado_content",$message, 1);
	}
	
	while(preg_match('#\[regalo_guardado=(.*?)\]#si',$message,$matches))
	{
		$dado_counter = $matches[1];

		$uid = $post['uid'];
		$pid = $post['pid'];
		$tid = $post['tid'];
		$is_edited = $post['edittime'];
		
		$post_editado = "";
		if ($is_edited) {
			$post_editado = "[[Pilas. Este post ha sido editado.]]<br />";
		}

		$query_dado = $db->query("
			SELECT * FROM mybb_sg_sg_dados WHERE pid='".$pid."' AND tid='".$tid."' AND dado_counter='".$dado_counter."'
		");

		$dado = null;
		while ($d = $db->fetch_array($query_dado)) {
			$dado = $d;
		}

		$dado_content = $dado['dado_content'];

		$message = preg_replace('#\[regalo_guardado=(.*?)\]#si',"$post_editado $dado_content",$message, 1);
	}
	
	return $message;
}

function dadotag_newpost(&$data)
{
	global $db, $mybb, $post;

	$uid = $data->post_insert_data['uid'];
	$pid = $data->return_values['pid'];
	$tid = $data->post_insert_data['tid'];
	$username = $data->post_insert_data['username'];
	$my_uid = $data->post_insert_data['uid'];

	$message = $data->post_insert_data['message'];
	$dado_counter = 0;

	while(preg_match('#\[dado=(.*?)\]#si',$message,$matches))
	{
		$dadosTexto = $matches[1];
		$dadosArr = explode("d", $dadosTexto);
		$dados = $dadosArr[0];
		$caras = $dadosArr[1];

		$outputText = "Dado inválido.";

		if (count($dadosArr) == 2 && is_int(intval($dados)) && is_int(intval($caras)) && intval($dados) <= 20 && intval($caras) <= 100000) {
			$dado_counter += 1;
			$outputText = "";
			$outputText .= "[ID $dado_counter] $username ha lanzado $dados dados de $caras caras. El resultado es: <br />";

			for ($x = 1; $x <= intval($dados); $x++) {
				$dadoResultado = rand(1, intval($caras));
				$outputText .= "- Dado $x: $dadoResultado<br />";
			}
			
			$message = preg_replace('#\[dado=(.*?)\]#si','[dado_guardado='.$dado_counter.']',$message, 1);
			$db->query(" 
				INSERT INTO `mybb_sg_sg_dados` (`tid`, `pid`, `uid`, `dado_counter`, `dado_content`) VALUES ('".$tid."','".$pid."','".$uid."', '".$dado_counter."', '".$outputText."');
			");
	
		} else {
			$message = preg_replace('#\[dado=(.*?)\]#si','[dadoinvalido='.$dado_counter.']',$message, 1);
			// $db->query(" 
			// 	INSERT INTO `mybb_sg_sg_dados` (`tid`, `pid`, `uid`, `dado_counter`, `dado_content`) VALUES ('".$tid."','".$pid."','".$uid."', '".$dado_counter."', 'Dado invalido.');
			// ");
		}

	}

	if ($dado_counter > 0) {
		$db->query(" 
			UPDATE `mybb_sg_posts` SET message='".$message."' WHERE pid='".$pid."';
		");
	}

	while(preg_match('#\[regalo_navidad\]#si',$message,$matches))
	{

		$regalo = '';
		$random_number = rand(1, 5);
		if ($random_number == 1) { $regalo = '20 PH'; }
		if ($random_number == 2) { $regalo = '25 PE'; }
		if ($random_number == 3) { $regalo = '20 PR'; }
		if ($random_number == 4) { $regalo = '5000 Ryos'; }
		if ($random_number == 5) { $regalo = '10 Bastones de caramelo'; }

		$dado_counter += 1;
		$outputText = "";
		
		if ($my_uid == '320') {
			$outputText = "
			<i><span style=\" color: #ffbbcc; \"><strong>Namida</strong></span> va al arbolito a buscar su regalo. Lo recoge y nota que la etiqueta dice <strong><i>De: Kurosame</i></strong>. Abre el lazo y lo destapa, y su regalo es... </i><br />
			<div style=\"display: flex;margin: auto;flex-direction: row;text-align: center;justify-content: center;margin-top: 15px;margin-bottom: 15px;\">
				<img src=\"https://cdn.discordapp.com/attachments/1185752167521468436/1187483438794674267/nami.png?\" height=\"100\">
				<img src=\"https://media1.giphy.com/media/l3vRebb6HyeIgvmQ8/giphy.gif?cid=6c09b952w79qjj7664x3pynb9gkwcc7x4xpiotk045rwoxtm&amp;ep=v1_gifs_search&amp;rid=giphy.gif&amp;ct=g\" width=\"100\" height=\"100\">
				<img src=\"https://cdn.discordapp.com/attachments/1185752167521468436/1187486634673967104/New_Project_1.png\" height=\"100\">
			</div><br />
			<div style=\" text-align: center; \">1 Boleto para el Sorteo, $regalo y un beso de navidad.</div>
			<div style=\" text-align: center; \"><img src=\"https://cdn.discordapp.com/attachments/884606837943582784/1187508526378127380/a9fd19f0b370766296e2f346dbf9a0f4e6d031d0.gif\"></div><br />
			";
		} else if ($my_uid == '335') {
			$outputText = "
			<i><strong>Karai</strong> va al arbolito a buscar su regalo de navidad. Lo recoge, abre el lazo y lo destapa, y su regalo es... </i><br />
			<div style=\"display: flex;margin: auto;flex-direction: row;text-align: center;justify-content: center;margin-top: 15px;margin-bottom: 15px;\">
				<img src=\"https://cdn.discordapp.com/attachments/1185752167521468436/1189005623883468830/250.png\" width=\"100\">
				<div style=\"margin-left: 10px;margin-top: 42px;\">Carbón. Por portarse muy mal y solo hacerle caso a la Karai diablita.<br /> ¡Por suerte, la Karai angelical recibe 1 Boleto para el Sorteo y $regalo! ¡Feliz Navidad!</div>
			</div>";
		} else {
			$outputText = "
			<i><strong>$username</strong> va al arbolito a buscar su regalo de navidad. Lo recoge, abre el lazo y lo destapa, y su regalo es... </i><br />
			<div style=\"display: flex;margin: auto;flex-direction: row;text-align: center;justify-content: center;margin-top: 15px;margin-bottom: 15px;\">
				<img src=\"https://ugokawaii.com/wp-content/uploads/2022/10/gift.gif\" width=\"100\" height=\"100\">
				<div style=\"margin-left: 10px;margin-top: 42px;\">¡1 Boleto para el Sorteo y $regalo! ¡Feliz Navidad!</div>
			</div>";
		}
		
		$message = preg_replace('#\[regalo_navidad\]#si','[regalo_guardado='.$dado_counter.']',$message, 1);
		$db->query(" 
			INSERT INTO `mybb_sg_sg_dados` (`tid`, `pid`, `uid`, `dado_counter`, `dado_content`) VALUES ('".$tid."','".$pid."','".$uid."', '".$dado_counter."', '$outputText');
		");
	}

	if ($dado_counter > 0) {
		$db->query(" 
			UPDATE `mybb_sg_posts` SET message='".$message."' WHERE pid='".$pid."';
		");
	}
}

/* 
<i><strong>Namida</strong> va al arbolito a buscar un árbol de navid. Lo recoge y nota que la etiqueta dice <i>De: Kurosame</i>. Abre el lazo y lo destapa, y su regalo es... </i>

<div style="display: flex;margin: auto;flex-direction: row;text-align: center;justify-content: center;margin-top: 15px;margin-bottom: 15px;">
<img src="https://cdn.discordapp.com/attachments/1185752167521468436/1187483438794674267/nami.png?" width="100" height="100"><img src="https://media1.giphy.com/media/l3vRebb6HyeIgvmQ8/giphy.gif?cid=6c09b952w79qjj7664x3pynb9gkwcc7x4xpiotk045rwoxtm&amp;ep=v1_gifs_search&amp;rid=giphy.gif&amp;ct=g" width="100" height="100"><div style="margin-left: 10px;margin-top: 42px;">¡1 Boleto para el Sorteo y 5000 Ryos! ¡Feliz Navidad!</div></div>

Feliz Namidad.

<div style="display: flex;margin: auto;flex-direction: row;text-align: center;justify-content: center;margin-top: 15px;margin-bottom: 15px;">
<img src="https://cdn.discordapp.com/attachments/1185752167521468436/1187483438794674267/nami.png?" height="100"><img src="https://media1.giphy.com/media/l3vRebb6HyeIgvmQ8/giphy.gif?cid=6c09b952w79qjj7664x3pynb9gkwcc7x4xpiotk045rwoxtm&amp;ep=v1_gifs_search&amp;rid=giphy.gif&amp;ct=g" width="100" height="100">
<img src="https://cdn.discordapp.com/attachments/1185752167521468436/1187486634673967104/New_Project_1.png" height="100">
</div>
<div style=" text-align: center; ">¡Feliz Namidad!</div>
<div style=" text-align: center; ">Tu regalo es 1 Boleto para el Sorteo, 5000 Ryos y un beso de navidad.</div>
<div style=" text-align: center; "><img src="https://cdn.discordapp.com/attachments/884606837943582784/1187508526378127380/a9fd19f0b370766296e2f346dbf9a0f4e6d031d0.gif"></div>

¡1 Boleto para el Sorteo, 5000 Ryos! Y un beso de Namidad.


*/