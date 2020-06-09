<?php

//$Id: prenotazioni.php 173 2012-09-13 08:20:23Z g.nuzzolo $
global $USER,$DB;

require_once '../../config.php';
require_once 'lib.php';

require_login();
$blockid = get_block_id(get_string('pluginname_db','block_f2_prenotazioni'));
require_capability('block/f2_prenotazioni:editvalidazioni', get_context_instance(CONTEXT_BLOCK, $blockid));

$userid=$USER->id;
// get settore
$settore_id = required_param('settid', PARAM_INT);
$location_next = 'validazioni_altri.php?organisationid='.$settore_id;

if($userid==0) $userid=$USER->id;
else if($userid!=0 && has_capability('block/f2_prenotazioni:editvalidazioni', get_context_instance(CONTEXT_BLOCK, $blockid)) && validate_own_dipendente($userid)) $userid=$userid;
// else header('location: '.$location_next);
else redirect(new moodle_url($location_next));

if (!canManageDomain($settore_id)) die();

$isOk = check_stati_validazione_per_utente($USER->id);
if ($isOk == true)
{	
	
	$settori = get_settori_by_direzione($settore_id);
	//include la direzione come settore fittizio
	$settori[] = get_organisation_info_by_id($settore_id);
	foreach ($settori as $s)
	{
		//invalida tutte le prenotazioni del settore dell'anno formativo in corso
		validazione_direzione_del_all($s->id);
        }
        
        //aggiusta stati validazioni
        $stato_globale_validazioni_sett = new stdClass;
        $stato_globale_validazioni_sett->anno = $anno;
        $stato_globale_validazioni_sett->nome_stato = 'stato_validaz_sett';
        $stato_globale_validazioni_sett->nuovo_stato = 'B';
        $stato_globale_validazioni_sett->dominio = $settore_id;

        $stato_globale_validazioni_dir = new stdClass;
        $stato_globale_validazioni_dir->anno = $anno;
        $stato_globale_validazioni_dir->nome_stato = 'stato_validaz_dir';
        $stato_globale_validazioni_dir->nuovo_stato = 'A';
        $stato_globale_validazioni_dir->dominio = $settore_id;

        update_stati_validazioni_globali($stato_globale_validazioni_sett);
        update_stati_validazioni_globali($stato_globale_validazioni_dir);
	
	// aggiusta stati validazioni direzione
// 	$stato_globale_validazioni_direz_padre = new stdClass;
// 	$stato_globale_validazioni_direz_padre->anno = $anno;
// 	$stato_globale_validazioni_direz_padre->nome_stato = 'stato_validaz_sett';
// 	$stato_globale_validazioni_direz_padre->nuovo_stato = 'A';
// 	$stato_globale_validazioni_direz_padre->dominio = $settore_id;
// 	update_stati_validazioni_globali($stato_globale_validazioni_direz_padre);
	
	$stato_globale_validazioni_direz_padre = new stdClass;
	$stato_globale_validazioni_direz_padre->anno = $anno;
	$stato_globale_validazioni_direz_padre->nome_stato = 'stato_validaz_dir';
	$stato_globale_validazioni_direz_padre->nuovo_stato = 'A';
	$stato_globale_validazioni_direz_padre->dominio = $settore_id;
	update_stati_validazioni_globali($stato_globale_validazioni_direz_padre);
	
	// header('location: '.$location_next);
	redirect(new moodle_url($location_next));
}
else
{
	redirect(new moodle_url('/'));
}