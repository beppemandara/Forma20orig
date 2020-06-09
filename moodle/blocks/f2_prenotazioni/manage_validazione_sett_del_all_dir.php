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
$show_sf = required_param('show_sf', PARAM_INT);
$location_next = 'validazioni_altri.php?organisationid='.$settore_id.'&show_sf='.$show_sf;

if($userid==0) $userid=$USER->id;
else if($userid!=0 && has_capability('block/f2_prenotazioni:editvalidazioni', get_context_instance(CONTEXT_BLOCK, $blockid)) && validate_own_dipendente($userid)) $userid=$userid;
// else header('location: '.$location_next);
else redirect(new moodle_url($location_next));

if (!canManageDomain($settore_id)) die();

$isOk = check_stati_validazione_per_utente($USER->id);
if ($isOk == true)
{	
	//invalida tutte le prenotazioni del settore dell'anno formativo in corso a livello di direzione
	validazione_direzione_su_settore_del_all($settore_id); 
	// header('location: '.$location_next);
	redirect(new moodle_url($location_next));
}
else
{
	redirect(new moodle_url('/'));
}