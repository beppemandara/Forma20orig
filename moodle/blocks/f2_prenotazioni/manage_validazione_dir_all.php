<?php

//$Id: manage_validazione_dir_all.php 1241 2013-12-20 04:34:05Z l.moretto $
global $USER,$DB;

require_once '../../config.php';
require_once 'lib.php';

require_login();
$blockid = get_block_id(get_string('pluginname_db','block_f2_prenotazioni'));
require_capability('block/f2_prenotazioni:editvalidazioni', get_context_instance(CONTEXT_BLOCK, $blockid));

$location_next = 'validazioni_altri.php';
$userid=$USER->id;
// print_r('aaaa');exit;
if($userid==0) $userid=$USER->id;
else if($userid!=0 && has_capability('block/f2_prenotazioni:editvalidazioni', get_context_instance(CONTEXT_BLOCK, $blockid)) && validate_own_dipendente($userid)) $userid=$userid;
// else header('location: '.$location_next);
else redirect(new moodle_url($location_next));

// get settore
$settore_id = required_param('settid', PARAM_INT);
$location_param = '?organisationid='.$settore_id;

if (!canManageDomain($settore_id)) die();

$isOk = check_stati_validazione_per_utente($USER->id);
if ($isOk == true)
{
	$num_settori_anomalie_validazioni = 0;
	$settori = get_settori_by_direzione($settore_id);
	//include la direzione come settore fittizio
	$settori[] = get_organisation_info_by_id($settore_id);
// 	print_r($settori);exit;
    $settori_anomali = array();
	foreach ($settori as $s)
	{
		$stato_validaz_dominio = get_stato_validazione_by_dominio($s->id);
		if (is_null($stato_validaz_dominio)) {
            $num_settori_anomalie_validazioni++;
            array_push($settori_anomali, $s->shortname);
        }
		elseif ($stato_validaz_dominio->stato_validaz_sett == 'A'
				or $stato_validaz_dominio->stato_validaz_sett == 'D')
        {
            $num_settori_anomalie_validazioni++;
            array_push($settori_anomali, $s->shortname);
        }
	}
	
	if ($num_settori_anomalie_validazioni == 0)
	{
		foreach ($settori as $s)
		{
			validazione_dir_all($s->id);
		}
		
		//update stati validazioni direzione padre
		$stato_globale_validazioni_direz_padre = new stdClass;
		$stato_globale_validazioni_direz_padre->anno = $anno;
		$stato_globale_validazioni_direz_padre->nome_stato = 'stato_validaz_sett';
		$stato_globale_validazioni_direz_padre->nuovo_stato = 'C';
		$stato_globale_validazioni_direz_padre->dominio = $settore_id;
		update_stati_validazioni_globali($stato_globale_validazioni_direz_padre);
		
		$stato_globale_validazioni_direz_padre = new stdClass;
		$stato_globale_validazioni_direz_padre->anno = $anno;
		$stato_globale_validazioni_direz_padre->nome_stato = 'stato_validaz_dir';
		$stato_globale_validazioni_direz_padre->nuovo_stato = 'C';
		$stato_globale_validazioni_direz_padre->dominio = $settore_id;
		update_stati_validazioni_globali($stato_globale_validazioni_direz_padre);
		
		$msg = get_string('effettuare_ver_budget','block_f2_prenotazioni');
		
		echo '<html><head><SCRIPT TYPE="text/javascript">
				function init(msg)
				{
					var b = setTimeout("apripopup()", 50);
				}
				function apripopup()
				{
					var a = alert(\''.$msg.'\');
					document.location.href = \''.$location_next.$location_param.'\';
				}
				</SCRIPT></head>
			<BODY onLoad="init()"></body></html>';
	}
	else 
	{
		$location_param .= '&anomalie=1';
        if(count($settori_anomali)) $location_param .= '&d='.json_encode($settori_anomali, JSON_HEX_TAG | JSON_HEX_AMP);
		// header('location: '.$location_next.$location_param);
		redirect(new moodle_url($location_next.$location_param));
	}
}
else
{
	redirect(new moodle_url('/'));
}