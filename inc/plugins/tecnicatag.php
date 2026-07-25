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

// Construye el spoiler de ficha de un NPC (mybb_sg_sg_npcs): dossier (retrato,
// nombre, cargo, filiación, frase, descripción colapsable) + la misma grilla
// de estadísticas que sg_personaje_spoiler() — mismo lenguaje visual (paleta
// "Nocturnal Plum" / docs/STYLE.md), para que se sienta parte de la misma
// familia de tarjetas. $n: fila completa de mybb_sg_sg_npcs.
function sg_npc_spoiler($n)
{
	$nombre      = htmlspecialchars($n['nombre']);
	$cargo       = htmlspecialchars($n['cargo']);
	$clan_grupo  = htmlspecialchars($n['clan_grupo']);
	$afiliacion  = htmlspecialchars($n['afiliacion']);
	$edad        = htmlspecialchars($n['edad']);
	$rango       = htmlspecialchars($n['rango']);
	$nivel       = intval($n['nivel']);
	$frase       = htmlspecialchars($n['frase']);
	$descripcion_raw = trim($n['descripcion']);
	$descripcion = nl2br(htmlspecialchars($descripcion_raw));
	$imagen      = trim($n['imagen']);
	$npc_id      = intval($n['npc_id']);

	// ── Retrato + nombre + cargo + chips ──
	$img_html = '';
	if ($imagen !== '') {
		$img_esc = htmlspecialchars($imagen);
		$img_html = "<img src='$img_esc' alt='$nombre' style='width:76px;height:76px;object-fit:cover;border:0.5px solid rgba(156,107,204,0.25);flex-shrink:0;' />";
	}

	$chip = function ($label, $valor, $borde = 'rgba(156,107,204,0.25)', $color = '#e8dff8') {
		if (trim($valor) === '') { return ''; }
		$prefijo = ($label !== '') ? "<span style='opacity:0.6;'>$label</span> " : '';
		return "<span style='display:inline-flex;align-items:center;gap:4px;padding:3px 9px;background:rgba(0,0,0,0.25);border:0.5px solid $borde;font-family:Cinzel,serif;font-size:9px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;color:$color;white-space:nowrap;'>{$prefijo}{$valor}</span>";
	};

	$chips = '';
	if ($rango !== '')      { $chips .= $chip('', $rango, 'rgba(192,152,64,0.4)', '#c09840'); }
	if ($nivel > 0)         { $chips .= $chip('Nv.', $nivel, 'rgba(192,152,64,0.4)', '#c09840'); }
	if ($clan_grupo !== '') { $chips .= $chip('', $clan_grupo); }
	if ($afiliacion !== '') { $chips .= $chip('', $afiliacion); }
	if ($edad !== '')       { $chips .= $chip('Edad', $edad); }

	$header = "
			<div style='display:flex;gap:14px;align-items:flex-start;margin-bottom:14px;'>
				$img_html
				<div style='flex:1;min-width:0;'>
					<div style='font-family:Cinzel,serif;font-size:17px;font-weight:900;letter-spacing:1.5px;text-transform:uppercase;color:#e8dff8;'>$nombre</div>";
	if ($cargo !== '') {
		$header .= "<div style='font-family:\"Source Serif 4\",serif;font-style:italic;font-size:12.5px;color:#b890e0;margin-top:2px;'>$cargo</div>";
	}
	$header .= "
					<div style='display:flex;flex-wrap:wrap;gap:6px;margin-top:9px;'>$chips</div>
				</div>
			</div>";

	// ── Frase ──
	$frase_html = '';
	if ($frase !== '') {
		// Comillas tipográficas como carácter UTF-8 literal, no entidad con
		// nombre (&ldquo;/&rdquo;): el validador XML estricto de MyBB solo
		// acepta las 5 entidades XML básicas + referencias numéricas.
		// {$frase} con llaves (no $frase suelto): un byte UTF-8 >= 0x80 pegado
		// justo después de $frase (la comilla de cierre) hace que el tokenizer
		// de PHP lo trague como si fuera parte del nombre de la variable.
		$frase_html = "<div style='border-left:2px solid #7b4ab8;background:#171224;padding:9px 13px;margin-bottom:12px;font-family:\"Source Serif 4\",serif;font-style:italic;font-size:12.5px;line-height:1.6;color:#c4a0e0;'>“{$frase}”</div>";
	}

	// ── Descripción: colapsada por defecto si es larga (checkbox + label, sin
	// JS, mismo truco que el "Leer más" de la ficha del sitio) ──
	$desc_html = '';
	if ($descripcion_raw !== '') {
		$toggle_id = "npc_desc_$npc_id";
		$desc_html = "
			<input type='checkbox' id='$toggle_id' style='position:absolute;width:1px;height:1px;opacity:0;overflow:hidden;' />
			<div class='{$toggle_id}_box' style='max-height:108px;overflow:hidden;position:relative;font-family:\"Source Serif 4\",serif;font-size:13px;line-height:1.75;color:#c9bfe0;text-align:justify;'>
				$descripcion
				<div class='{$toggle_id}_fade' style='position:absolute;left:0;right:0;bottom:0;height:38px;background:linear-gradient(180deg,transparent,#130f1e);'></div>
			</div>
			<label for='$toggle_id' style='display:inline-flex;margin-top:8px;cursor:pointer;font-family:Cinzel,serif;font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#9c6bcc;'>Leer más ↓</label>
			<style>
				#$toggle_id:checked ~ .{$toggle_id}_box { max-height: none !important; }
				#$toggle_id:checked ~ .{$toggle_id}_box .{$toggle_id}_fade { display: none !important; }
				#$toggle_id:checked ~ label { display: none !important; }
			</style>";
	}

	// ── Estadísticas (misma grilla que sg_personaje_spoiler) ──
	$generales = array(
		'Fuerza'       => array(intval($n['fuerza']),       intval($n['mfuerza'])),
		'Inteligencia' => array(intval($n['inteligencia']), intval($n['minteligencia'])),
		'Destreza'     => array(intval($n['destreza']),      intval($n['mdestreza'])),
		'Ctrl. Chakra' => array(intval($n['cchakra']),       intval($n['mcchakra'])),
	);
	$especiales = array(
		'Salud'     => intval($n['salud']),
		'Tenketsu'  => intval($n['tenketsu']),
		'Sigilo'    => intval($n['sigilo']),
		'Velocidad' => intval($n['velocidad']),
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

	$vida = intval($n['vida']); $chakra = intval($n['chakra']); $regchakra = intval($n['regchakra']);

	return "[spoiler=Ficha de NPC: $nombre]
		<div style='border:0.5px solid rgba(156,107,204,0.18);background:#130f1e;padding:16px 18px;color:#e8dff8;'>
			$header
			$frase_html
			$desc_html

			<div style='font-family:Cinzel,serif;font-size:9px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#9c6bcc;margin:16px 0 8px;'>Estadísticas Generales</div>
			<div style='display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:6px;margin-bottom:16px;'>$gen_tiles
			</div>

			<div style='font-family:Cinzel,serif;font-size:9px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#9c6bcc;margin-bottom:8px;'>Estadísticas Especiales</div>
			<div style='display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:6px;margin-bottom:16px;'>$esp_tiles
			</div>

			<div style='display:flex;flex-wrap:wrap;gap:18px;border-top:0.5px solid rgba(156,107,204,0.12);padding-top:12px;'>
				<span style='font-family:Cinzel,serif;font-size:9px;letter-spacing:1.5px;text-transform:uppercase;color:#8a7a9a;'>Vida <strong style='font-size:13px;color:#e8dff8;'>$vida</strong></span>
				<span style='font-family:Cinzel,serif;font-size:9px;letter-spacing:1.5px;text-transform:uppercase;color:#8a7a9a;'>Chakra <strong style='font-size:13px;color:#e8dff8;'>$chakra</strong></span>
				<span style='font-family:Cinzel,serif;font-size:9px;letter-spacing:1.5px;text-transform:uppercase;color:#8a7a9a;'>Reg. Chakra <strong style='font-size:13px;color:#e8dff8;'>$regchakra</strong></span>
			</div>
		</div>
	[/spoiler]
	";
}

// Construye la tarjeta horizontal de un objeto (mybb_sg_sg_objetos), visible
// directo en el post (sin spoiler) — referencia visual de cómo se ve el
// objeto en el inventario/tienda. Mismo lenguaje visual que el resto de los
// tags (paleta "Nocturnal Plum" / docs/STYLE.md). $o: fila completa de
// mybb_sg_sg_objetos.
function sg_objeto_card($o, $consumido = false)
{
	global $post;
	$nombre   = htmlspecialchars($o['nombre']);
	$tipo     = htmlspecialchars(trim($o['tipo']) !== '' ? $o['tipo'] : 'Objeto');
	$tamano   = htmlspecialchars($o['tamano']);
	$municion = htmlspecialchars($o['municion']);
	$desc_raw = trim($o['descripcion']);
	$desc     = nl2br(htmlspecialchars($desc_raw));
	$coste    = intval($o['coste']);
	$imagen   = trim($o['imagen']);

	$img_src = ($imagen !== '') ? htmlspecialchars($imagen) : '/images/sg/objeto_default.png';
	// [consumir]/[consumido]: reemplaza el precio por una etiqueta de "usado",
	// en gris apagado en vez del dorado normal (ver más abajo, $coste_color).
	$coste_label = $consumido ? 'Consumido' : (($coste >= 99999) ? 'No comprable' : number_format($coste, 0, ',', '.') . ' ryos');
	$coste_color = $consumido ? '#8a7a9a' : '#c09840';

	// ── Chips: tipo, tamaño, munición ──
	$chip = function ($valor, $borde = 'rgba(156,107,204,0.25)', $color = '#e8dff8') {
		if (trim($valor) === '') { return ''; }
		return "<span style='display:inline-flex;align-items:center;padding:3px 9px;background:rgba(0,0,0,0.25);border:0.5px solid $borde;font-family:Cinzel,serif;font-size:9px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;color:$color;white-space:nowrap;'>$valor</span>";
	};
	$chips = $chip($tipo, 'rgba(192,152,64,0.4)', '#c09840');
	if ($tamano !== '')   { $chips .= $chip($tamano); }
	if ($municion !== '') { $chips .= $chip($municion); }

	// ── Efectos (hasta 3) ──
	// Códigos [FUEx1]/[MDES-10]/[CCKx2]/etc. se muestran SIEMPRE ya resueltos
	// (sin toggle: un post lo leen muchos usuarios distintos), usando las
	// stats del AUTOR del post — mismo criterio que ya usa [tecnica]. Se
	// escapa PRIMERO (htmlspecialchars no toca '[' ']', así que la regex de
	// códigos sigue matcheando igual) y recién después se resuelven los
	// códigos, para que el <span> que inserta sg_parsear_codigos_stats() no
	// termine él mismo escapado.
	$efectos_html = '';
	$tiene_algun_efecto = trim($o['efecto1']) !== '' || trim($o['efecto2']) !== '' || trim($o['efecto3']) !== '';
	$stats_ef_autor = $tiene_algun_efecto ? sg_resolver_stats_autor_post($post) : null;
	foreach (array('efecto1', 'efecto2', 'efecto3') as $campo) {
		$ef = isset($o[$campo]) ? trim($o[$campo]) : '';
		if ($ef === '') { continue; }
		$ef_parsed = nl2br(sg_parsear_codigos_stats(htmlspecialchars($ef), $stats_ef_autor));
		$efectos_html .= "<div style='margin-top:5px;font-family:\"Source Serif 4\",serif;font-size:12.5px;line-height:1.5;color:#c9bfe0;'>"
			. "<span style='font-family:Cinzel,serif;font-size:8px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:#9c6bcc;'>Efecto</span> $ef_parsed</div>";
	}

	// Descripción: si es larga, colapsada por defecto con "Leer más" (checkbox
	// + label, sin JS — mismo truco que [npc], con !important en el override
	// porque el clamp base va inline y una regla de <style> sin !important no
	// le gana a un estilo inline, sin importar qué tan específico sea el selector).
	$desc_html = '';
	if ($desc_raw !== '') {
		$umbral = 220;
		$es_larga = function_exists('mb_strlen') ? (mb_strlen($desc_raw, 'UTF-8') > $umbral) : (strlen($desc_raw) > $umbral);
		if (!$es_larga) {
			$desc_html = "<p style='margin:7px 0 0;font-family:\"Source Serif 4\",serif;font-size:12.5px;line-height:1.6;color:#c9bfe0;'>$desc</p>";
		} else {
			$toggle_id = 'obj_desc_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $o['objeto_id']);
			$desc_html = "
				<input type='checkbox' id='$toggle_id' style='position:absolute;width:1px;height:1px;opacity:0;overflow:hidden;' />
				<div class='{$toggle_id}_box' style='max-height:58px;overflow:hidden;position:relative;margin-top:7px;font-family:\"Source Serif 4\",serif;font-size:12.5px;line-height:1.6;color:#c9bfe0;'>
					$desc
					<div class='{$toggle_id}_fade' style='position:absolute;left:0;right:0;bottom:0;height:26px;background:linear-gradient(180deg,transparent,#130f1e);'></div>
				</div>
				<label for='$toggle_id' style='display:inline-flex;margin-top:5px;cursor:pointer;font-family:Cinzel,serif;font-size:9px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#9c6bcc;'>Leer más ↓</label>
				<style>
					#$toggle_id:checked ~ .{$toggle_id}_box { max-height: none !important; }
					#$toggle_id:checked ~ .{$toggle_id}_box .{$toggle_id}_fade { display: none !important; }
					#$toggle_id:checked ~ label { display: none !important; }
				</style>";
		}
	}

	return "
	<div style='display:flex;gap:14px;align-items:flex-start;border:0.5px solid rgba(156,107,204,0.18);border-left:2px solid #7b4ab8;background:#130f1e;padding:14px 16px;margin:10px 0;'>
		<div style='position:relative;width:84px;height:84px;flex-shrink:0;background:#171224;border:0.5px solid rgba(156,107,204,0.16);overflow:hidden;'>
			<img src='$img_src' alt='$nombre' style='width:100%;height:100%;object-fit:cover;' />
		</div>
		<div style='flex:1;min-width:0;color:#e8dff8;'>
			<div style='display:flex;align-items:baseline;justify-content:space-between;gap:10px;flex-wrap:wrap;'>
				<span style='font-family:Cinzel,serif;font-size:15px;font-weight:900;letter-spacing:1px;color:#e8dff8;'>$nombre</span>
				<span style='font-family:Cinzel,serif;font-size:10px;font-weight:700;letter-spacing:1px;color:{$coste_color};white-space:nowrap;'>$coste_label</span>
			</div>
			<div style='display:flex;flex-wrap:wrap;gap:6px;margin-top:7px;'>$chips</div>
			$desc_html
			$efectos_html
		</div>
	</div>
	";
}

function tecnicatag_run(&$message)
{
	global $db, $post;

	while(preg_match('#\[npc=(.*?)\]#si',$message,$matches))
	{
		$npc_codigo = $matches[1];
		$npc_data = null;

		// codigo es texto libre (slug), a diferencia de tid/uid en el resto del
		// archivo: se valida el formato Y se escapa antes de la query.
		if (preg_match('/^[a-zA-Z0-9_-]+$/', $npc_codigo)) {
			$npc_codigo_esc = $db->escape_string($npc_codigo);
			$query_npc = $db->query("SELECT * FROM mybb_sg_sg_npcs WHERE codigo='$npc_codigo_esc'");
			while ($q = $db->fetch_array($query_npc)) {
				$npc_data = $q;
			}
		}

		$npc_message = $npc_data ? sg_npc_spoiler($npc_data) : "[npcinvalido=$npc_codigo]";

		// str_replace, no preg_replace: la descripción/frase del NPC es texto
		// libre y podría contener un '$' (ej. "5000$"), que preg_replace
		// interpretaría como referencia de grupo capturado ($1, etc.) en el
		// string de reemplazo. $matches[0] ya es el tag completo matcheado.
		$message = str_replace($matches[0], $npc_message, $message);
	}

	while(preg_match('#\[objeto=(.*?)\]#si',$message,$matches))
	{
		$objeto_id_tag = $matches[1];
		$objeto_data = null;

		// objeto_id es texto libre (ej. "TTUN010"), mismo criterio que codigo
		// en [npc]: se valida el formato Y se escapa antes de la query.
		if (preg_match('/^[a-zA-Z0-9_-]+$/', $objeto_id_tag)) {
			$objeto_id_esc = $db->escape_string($objeto_id_tag);
			$query_objeto = $db->query("SELECT * FROM mybb_sg_sg_objetos WHERE objeto_id='$objeto_id_esc'");
			while ($q = $db->fetch_array($query_objeto)) {
				$objeto_data = $q;
			}
		}

		$objeto_message = $objeto_data ? sg_objeto_card($objeto_data) : "[objetoinvalido=$objeto_id_tag]";

		// str_replace (no preg_replace): la descripción/efectos del objeto son
		// texto libre y podrían contener un '$' (ej. "5000$"), que preg_replace
		// interpretaría como referencia de grupo capturado en el reemplazo.
		$message = str_replace($matches[0], $objeto_message, $message);
	}

	// [consumido=OBJETO_ID]: solo MUESTRA (ya se consumió una vez, en
	// dadotag_newpost, al crear el post). Misma tarjeta que [objeto], marcada
	// como consumida (ver sg_objeto_card($o, true)).
	while(preg_match('#\[consumido=(.*?)\]#si',$message,$matches))
	{
		$objeto_id_tag = $matches[1];
		$objeto_data = null;

		if (preg_match('/^[a-zA-Z0-9_-]+$/', $objeto_id_tag)) {
			$objeto_id_esc = $db->escape_string($objeto_id_tag);
			$query_objeto = $db->query("SELECT * FROM mybb_sg_sg_objetos WHERE objeto_id='$objeto_id_esc'");
			while ($q = $db->fetch_array($query_objeto)) {
				$objeto_data = $q;
			}
		}

		if ($objeto_data) {
			// $post['username'] es el autor de ESTE post (el mismo que lo
			// consumió al crearlo en dadotag_newpost) — no hace falta guardar
			// quién fue en el tag, ya lo sabemos por el post que lo contiene.
			$consumidor = htmlspecialchars(isset($post['username']) ? $post['username'] : 'Alguien');
			$aviso = "<div style='font-family:\"Source Serif 4\",serif;font-size:13px;color:#8a7a9a;margin:10px 0 6px;'><strong style='color:#e8dff8;'>$consumidor</strong> ha consumido el siguiente objeto:</div>";
			$objeto_message = $aviso . sg_objeto_card($objeto_data, true);
		} else {
			$objeto_message = "[objetoinvalido=$objeto_id_tag]";
		}
		$message = str_replace($matches[0], $objeto_message, $message);
	}

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

		// Mismo lenguaje visual que [tiempo]/[personaje] (paleta "washi" oscura,
		// Cinzel): solo se sustituye en memoria al mostrar el post, no se
		// guarda en ninguna tabla, así que no hay riesgo de romper SQL aquí.
		$cerrado_texto = "
			<div style='position:relative;overflow:hidden;text-align:center;margin:10px 0;padding:16px 18px;background:#130f1e;border:0.5px solid rgba(156,107,204,0.18);border-left:2px solid #b5544b;'>
				<span style='position:absolute;top:-12px;right:6px;font-size:60px;line-height:1;color:#b5544b;opacity:0.08;pointer-events:none;'>閉</span>
				<div style='position:relative;font-family:Cinzel,serif;font-size:9px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#b5544b;margin-bottom:8px;'>🔒 Tema cerrado</div>
				<div style='position:relative;font-family:Cinzel,serif;font-size:16px;font-weight:900;letter-spacing:1px;color:#e8dff8;'>Este tema ha sido cerrado.</div>
			</div>";
		$message = preg_replace('#\[cerrado\]#si',$cerrado_texto,$message);
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
			$totalSeconds = intval($tiempo) * 3600;

			// Mismo lenguaje visual que sg_personaje_spoiler() (paleta "washi"
			// oscura, tipografía Cinzel): estilos inline porque esto se pinta
			// dentro de un post del foro, sin acceso al CSS del sitio.
			if ($timeNow > $timeAfter) {
				$texto = "
					<div style='position:relative;overflow:hidden;text-align:center;margin:10px 0;padding:14px 18px;background:#130f1e;border:0.5px solid rgba(156,107,204,0.18);border-left:2px solid #b5544b;'>
						<span style='position:absolute;top:-10px;right:6px;font-size:56px;line-height:1;color:#b5544b;opacity:0.08;pointer-events:none;'>時</span>
						<div style='position:relative;font-family:Cinzel,serif;font-size:9px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#b5544b;margin-bottom:6px;'>⏳ Tiempo expirado</div>
						<div style='position:relative;font-size:13.5px;color:#e8dff8;'>El límite de <strong>$tiempo horas</strong> para postear ya se cumplió.</div>
					</div>";
			} else {
				$secondsLeft = $timeAfter - $timeNow;
				$hLeft = intdiv($secondsLeft, 3600);
				$mLeft = intdiv($secondsLeft % 3600, 60);
				$pct = max(0, min(100, round((($timeNow - $dateline) / $totalSeconds) * 100)));

				$texto = "
					<div style='position:relative;overflow:hidden;text-align:center;margin:10px 0;padding:14px 18px;background:#130f1e;border:0.5px solid rgba(156,107,204,0.18);border-left:2px solid #7b4ab8;'>
						<span style='position:absolute;top:-10px;right:6px;font-size:56px;line-height:1;color:#7b4ab8;opacity:0.08;pointer-events:none;'>時</span>
						<div style='position:relative;font-family:Cinzel,serif;font-size:9px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#9c6bcc;margin-bottom:6px;'>⏱ Límite de tiempo · $tiempo horas</div>
						<div style='position:relative;font-size:13.5px;color:#e8dff8;margin-bottom:10px;'>Quedan <strong style='color:#b890e0;'>{$hLeft}h {$mLeft}m</strong> para postear.</div>
						<div style='position:relative;height:12px;border-radius:6px;background:rgba(156,107,204,0.15);overflow:hidden;border:0.5px solid rgba(156,107,204,0.18);'>
							<div style='height:100%;width:{$pct}%;background:linear-gradient(90deg,#7b4ab8,#9c6bcc);border-radius:6px;'></div>
						</div>
						<div style='position:relative;display:flex;justify-content:space-between;margin-top:5px;font-family:Cinzel,serif;font-size:8px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#8a7a9a;'>
							<span>{$pct}% transcurrido</span>
							<span>" . (100 - $pct) . "% restante</span>
						</div>
					</div>";
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

				// Mismo lenguaje visual que el resto de los tags (paleta "washi",
				// Cinzel), y mismo ícono/color de acento que ya usa la tarjeta del
				// postbit para vida (postbit_classic.html / .health_bar_text svg).
				// Autocontenido (sin depender de .barrasVida, que apuntaba a una
				// imagen de fondo hosteada en Discord que puede caducar).
				$vida_pct = intval($vida_actual) > 0 ? min(100, round((intval($vida_actual) / intval($vida_max)) * 100)) : 0;
				$barra = "
			<div style='position:relative;max-width:300px;height:26px;overflow:hidden;background:#171224;border:0.5px solid rgba(156,107,204,0.18);margin:6px 0;'>
				<div style='position:absolute;top:0;left:0;height:100%;width:{$vida_pct}%;background:linear-gradient(90deg,rgba(192,88,42,0.55),rgba(192,88,42,0.85));'></div>
				<div style='position:relative;height:100%;display:flex;align-items:center;justify-content:center;gap:6px;font-family:Cinzel,serif;font-size:10px;font-weight:700;letter-spacing:1px;color:#e8dff8;'>
					<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='#c0582a'><path d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/></svg>
					Vida: $vida_actual/$vida_max
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
				
				$vida_pct = intval($vida_actual) > 0 ? min(100, round((intval($vida_actual) / intval($vida_max)) * 100)) : 0;
				$barra = "
			<div style='position:relative;max-width:300px;height:26px;overflow:hidden;background:#171224;border:0.5px solid rgba(156,107,204,0.18);margin:6px 0;'>
				<div style='position:absolute;top:0;left:0;height:100%;width:{$vida_pct}%;background:linear-gradient(90deg,rgba(192,88,42,0.55),rgba(192,88,42,0.85));'></div>
				<div style='position:relative;height:100%;display:flex;align-items:center;justify-content:center;gap:6px;font-family:Cinzel,serif;font-size:10px;font-weight:700;letter-spacing:1px;color:#e8dff8;'>
					<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='#c0582a'><path d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/></svg>
					Vida: $vida_actual/$vida_max
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

				// Mismo lenguaje visual/ícono que la tarjeta del postbit para
				// chakra (postbit_classic.html / .chakra_bar_text svg).
				$chakra_pct = intval($chakra_actual) > 0 ? min(100, round((intval($chakra_actual) / intval($chakra_max)) * 100)) : 0;
				$barra = "
			<div style='position:relative;max-width:300px;height:26px;overflow:hidden;background:#171224;border:0.5px solid rgba(156,107,204,0.18);margin:6px 0;'>
				<div style='position:absolute;top:0;left:0;height:100%;width:{$chakra_pct}%;background:linear-gradient(90deg,rgba(89,160,216,0.5),rgba(89,160,216,0.85));'></div>
				<div style='position:relative;height:100%;display:flex;align-items:center;justify-content:center;gap:6px;font-family:Cinzel,serif;font-size:10px;font-weight:700;letter-spacing:1px;color:#e8dff8;'>
					<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='#59a0d8'><path d='M12 2c.7 2.7 2.6 4.2 3.8 5.9C16.7 9.2 17 10.6 17 12a5 5 0 0 1-10 0c0-1.1.3-2 .8-2.9.4 1 1.2 1.6 2 1.6 1 0 1.6-.8 1.4-2.1C10.8 6.7 11.4 4 12 2z'/></svg>
					Chakra: $chakra_actual/$chakra_max
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
				
				$chakra_pct = intval($chakra_actual) > 0 ? min(100, round((intval($chakra_actual) / intval($chakra_max)) * 100)) : 0;
				$barra = "
			<div style='position:relative;max-width:300px;height:26px;overflow:hidden;background:#171224;border:0.5px solid rgba(156,107,204,0.18);margin:6px 0;'>
				<div style='position:absolute;top:0;left:0;height:100%;width:{$chakra_pct}%;background:linear-gradient(90deg,rgba(89,160,216,0.5),rgba(89,160,216,0.85));'></div>
				<div style='position:relative;height:100%;display:flex;align-items:center;justify-content:center;gap:6px;font-family:Cinzel,serif;font-size:10px;font-weight:700;letter-spacing:1px;color:#e8dff8;'>
					<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='#59a0d8'><path d='M12 2c.7 2.7 2.6 4.2 3.8 5.9C16.7 9.2 17 10.6 17 12a5 5 0 0 1-10 0c0-1.1.3-2 .8-2.9.4 1 1.2 1.6 2 1.6 1 0 1.6-.8 1.4-2.1C10.8 6.7 11.4 4 12 2z'/></svg>
					Chakra: $chakra_actual/$chakra_max
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
			$numDados = intval($dados);
			$numCaras = intval($caras);

			$suma = 0;
			$tiles = '';
			for ($x = 1; $x <= $numDados; $x++) {
				$dadoResultado = rand(1, $numCaras);
				$suma += $dadoResultado;

				// Resalta el máximo natural (crítico) y el mínimo natural (pifia).
				$esMax = ($dadoResultado == $numCaras);
				$esMin = ($dadoResultado == 1 && $numCaras > 1);
				$borde = $esMax ? '#c9a227' : ($esMin ? '#b5544b' : 'rgba(156,107,204,0.16)');
				$color = $esMax ? '#e8c766' : ($esMin ? '#e08a7a' : '#e8dff8');

				$tiles .= "<div style='border:0.5px solid $borde;background:#171224;padding:8px 4px;text-align:center;min-width:44px;'>"
					. "<div style='font-family:Cinzel,serif;font-size:7px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#8a7a9a;'>D$x</div>"
					. "<div style='font-size:17px;font-weight:700;color:$color;margin-top:2px;'>$dadoResultado</div>"
					. "</div>";
			}

			// Mismo lenguaje visual que sg_personaje_spoiler() / [tiempo] (paleta
			// "washi" oscura, Cinzel): estilos inline porque queda guardado tal
			// cual en el post y se muestra sin acceso al CSS del sitio.
			$outputText = "
				<div style='position:relative;overflow:hidden;margin:10px 0;padding:14px 18px;background:#130f1e;border:0.5px solid rgba(156,107,204,0.18);border-left:2px solid #7b4ab8;'>
					<span style='position:absolute;top:-10px;right:6px;font-size:56px;line-height:1;color:#7b4ab8;opacity:0.08;pointer-events:none;'>賽</span>
					<div style='position:relative;display:flex;align-items:center;gap:8px;margin-bottom:10px;'>
						<span style='display:inline-block;width:3px;height:16px;background:#7b4ab8;'></span>
						<span style='font-family:Cinzel,serif;font-size:9px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#9c6bcc;'>[ID $dado_counter] $username · {$numDados}d{$numCaras}</span>
					</div>
					<div style='position:relative;display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px;'>$tiles</div>
					<div style='position:relative;font-family:Cinzel,serif;font-size:9px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#8a7a9a;'>Total <strong style='font-size:13px;color:#e8dff8;'>$suma</strong></div>
				</div>";

			$message = preg_replace('#\[dado=(.*?)\]#si','[dado_guardado='.$dado_counter.']',$message, 1);
			$outputText_esc = $db->escape_string($outputText);
			$db->query("
				INSERT INTO `mybb_sg_sg_dados` (`tid`, `pid`, `uid`, `dado_counter`, `dado_content`) VALUES ('".$tid."','".$pid."','".$uid."', '".$dado_counter."', '".$outputText_esc."');
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

	// [consumir=OBJETO_ID]: se procesa UNA sola vez, acá (creación del post),
	// nunca en tecnicatag_run() (que corre en cada vista -> consumiría el
	// objeto cada vez que alguien mira el post). Igual que [dado], el tag se
	// reemplaza en el mensaje GUARDADO por un marcador permanente
	// ([consumido=ID] o [consumirinvalido=N]) para que futuras vistas solo
	// muestren, sin volver a tocar inventario/historial.
	$consumir_counter = 0;
	while(preg_match('#\[consumir=(.*?)\]#si',$message,$matches))
	{
		$objeto_id_tag = $matches[1];
		$es_valido = false;

		if (preg_match('/^[a-zA-Z0-9_-]+$/', $objeto_id_tag)) {
			$objeto_id_esc = $db->escape_string($objeto_id_tag);

			$existe_objeto = false;
			$qo = $db->query("SELECT objeto_id FROM `mybb_sg_sg_objetos` WHERE objeto_id='$objeto_id_esc'");
			while ($db->fetch_array($qo)) { $existe_objeto = true; }

			if ($existe_objeto) {
				$tiene_cantidad = 0;
				$qi = $db->query("SELECT cantidad FROM `mybb_sg_sg_inventario` WHERE uid='$my_uid' AND objeto_id='$objeto_id_esc'");
				while ($ri = $db->fetch_array($qi)) { $tiene_cantidad = (int) $ri['cantidad']; }
				$es_valido = ($tiene_cantidad > 0);
			}
		}

		$consumir_counter++;
		if ($es_valido) {
			$grupo = uniqid();
			sg_inventario_quitar_objeto($my_uid, $objeto_id_tag, 1, 'usuario', SG_ORIGEN_CONSUMIR, 'Consumido en post', $grupo, null, $pid, $tid);
			// $objeto_id_tag ya validado contra ^[a-zA-Z0-9_-]+$ arriba: no
			// puede traer comillas que rompan el UPDATE (sin escapar) de abajo.
			$resultado_tag = '[consumido='.$objeto_id_tag.']';
		} else {
			// Numérico, NUNCA el texto crudo del usuario (que en el caso
			// inválido no pasó la validación y podría traer comillas).
			$resultado_tag = '[consumirinvalido='.$consumir_counter.']';
		}

		// preg_quote: $objeto_id_tag es texto libre no validado en el caso
		// inválido, podría traer caracteres especiales de regex.
		$message = preg_replace('#\[consumir='.preg_quote($objeto_id_tag, '#').'\]#si', $resultado_tag, $message, 1);
	}

	if ($consumir_counter > 0) {
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