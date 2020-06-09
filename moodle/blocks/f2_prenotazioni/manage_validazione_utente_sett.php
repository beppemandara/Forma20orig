<?php

//$Id: prenotazioni.php 173 2012-09-13 08:20:23Z g.nuzzolo $
global $USER,$DB,$CFG;

require_once '../../config.php';
require_once 'lib.php';
require_once $CFG->libdir . '/formslib.php';

require_login();
$blockid = get_block_id(get_string('pluginname_db','block_f2_prenotazioni'));
require_capability('block/f2_prenotazioni:editvalidazioni', get_context_instance(CONTEXT_BLOCK, $blockid));

$location_next = 'validazioni_altri.php';

$userid       = required_param('userid', PARAM_INT);
$settore_id       = required_param('settid', PARAM_INT);
$show_sf       = required_param('show_sf', PARAM_INT);
$prenotazioni_id_all = required_param_array('prenotazione_id_all', PARAM_INT);
$prenotazioni_id_sett_chk = optional_param_array('prenotazione_id_sett',array(), PARAM_INT);
$prenotazioni_id_dir_chk = optional_param_array('prenotazione_id_dir',array(), PARAM_INT);

if (!canManageDomain($settore_id)) die();

if($userid==0) $userid=$USER->id;
else if($userid!=0 && has_capability('block/f2_prenotazioni:editvalidazioni', get_context_instance(CONTEXT_BLOCK, $blockid)) && validate_own_dipendente($userid)) $userid=$userid;
// else header('location: '.$location_next);
else redirect(new moodle_url($location_next));

$isOk = check_stati_validazione_per_utente($USER->id);
$canReferente_sett = canView($USER->id,'sett');
$canReferente_dir = canView($USER->id,'dir');

$save = 0;

if ($isOk == true)
{
	$inconsistenti = array();
	foreach($prenotazioni_id_dir_chk as $pid)
	{
		if (!in_array($pid,$prenotazioni_id_sett_chk)) 
		{
			$inconsistenti[] = $pid;
		}
	}
	if (count($inconsistenti) > 0)
	{
		$_SESSION['pid_inconsistenti'] = json_encode($inconsistenti);
		$_SESSION['pid_sett_chk'] = json_encode($prenotazioni_id_sett_chk);
		$_SESSION['pid_dir_chk'] = json_encode($prenotazioni_id_dir_chk);
		redirect('validazioni_utente.php?userid='.$userid.'&settid='.$settore_id.'&show_sf='.$show_sf);
	}
	else 
	{

		$modificati_sett = 0;
		$modificati_dir = 0;
		
		foreach($prenotazioni_id_all as $pid)
		{
			if ($canReferente_sett and !in_array($pid,$prenotazioni_id_sett_chk)) // mettere val sett in => "da validare"
			{
				$sql = "select 1 from {f2_prenotati} p where p.id = ".$pid." and p.validato_sett > 0";
				$exists = $DB->record_exists_sql($sql);
				if ($exists == true)
				{
					$updt = new stdClass;
					$updt->id = $pid;
					$updt->validato_sett = 0; // da validare
					$updt->val_sett_by = $USER->id;
					$updt->val_sett_dt = time();
					$DB->update_record('f2_prenotati', $updt);
					$modificati_sett++;
				}
			}
		
			if ($canReferente_dir and !in_array($pid,$prenotazioni_id_dir_chk)) // mettere val dir in => "da validare"
			{
				$sql = "select 1 from {f2_prenotati} p where p.id = ".$pid." and p.validato_dir > 0";
				$exists = $DB->record_exists_sql($sql);
				if ($exists == true)
				{
					$updt = new stdClass;
					$updt->id = $pid;
					$updt->validato_dir = 0; // da validare
					$updt->val_dir_by = $USER->id;
					$updt->val_dir_dt = time();
					$DB->update_record('f2_prenotati', $updt);
					$modificati_dir++;
				}
			}
		}
		
		foreach($prenotazioni_id_sett_chk as $pid)
		{
			if ($canReferente_sett)
			{
				$sql = "select 1 from {f2_prenotati} p where p.id = ".$pid." and p.validato_sett <> 1";
				$exists = $DB->record_exists_sql($sql);
				if ($exists == true)
				{
					$updt = new stdClass;
					$updt->id = $pid;
					$updt->validato_sett = 1; //validata
					$updt->val_sett_by = $USER->id;
					$updt->val_sett_dt = time();
					$DB->update_record('f2_prenotati', $updt);
					$modificati_sett++;
				}
			}
		}
		
		foreach($prenotazioni_id_dir_chk as $pid)
		{
			if ($canReferente_dir)
			{
				$sql = "select 1 from {f2_prenotati} p where p.id = ".$pid." and p.validato_dir <> 1";
				$exists = $DB->record_exists_sql($sql);
				if ($exists == true)
				{
					$updt = new stdClass;
					$updt->id = $pid;
					$updt->validato_dir = 1; //validata
					$updt->val_dir_by = $USER->id;
					$updt->val_dir_dt = time();
					$DB->update_record('f2_prenotati', $updt);
					$modificati_dir++;
				}
			}
		}
		
		if ($modificati_dir > 0)
		{
			$stato_globale_validazioni_dir = new stdClass;
			$stato_globale_validazioni_dir->anno = $anno;
			$stato_globale_validazioni_dir->dominio = $settore_id;
			$stato_globale_validazioni_dir->nome_stato = 'stato_validaz_sett';
			$stato_globale_validazioni_dir->nuovo_stato = 'B';
			update_stati_validazioni_globali($stato_globale_validazioni_dir);
			
			$stato_globale_validazioni_dir = new stdClass;
			$stato_globale_validazioni_dir->anno = $anno;
			$stato_globale_validazioni_dir->dominio = $settore_id;
			$stato_globale_validazioni_dir->nome_stato = 'stato_validaz_dir';
			$stato_globale_validazioni_dir->nuovo_stato = 'B';
			update_stati_validazioni_globali($stato_globale_validazioni_dir);
			$save = 1;
		}
		
		if ($modificati_sett > 0)
		{
			$stato_globale_validazioni_sett = new stdClass;
			$stato_globale_validazioni_sett->anno = $anno;
			$stato_globale_validazioni_sett->dominio = $settore_id;
			$stato_globale_validazioni_sett->nome_stato = 'stato_validaz_sett';
			$stato_globale_validazioni_sett->nuovo_stato = 'A';
			update_stati_validazioni_globali($stato_globale_validazioni_sett);
			
			$stato_globale_validazioni_sett = new stdClass;
			$stato_globale_validazioni_sett->anno = $anno;
			$stato_globale_validazioni_sett->dominio = $settore_id;
			$stato_globale_validazioni_sett->nome_stato = 'stato_validaz_dir';
			$stato_globale_validazioni_sett->nuovo_stato = 'B';
			update_stati_validazioni_globali($stato_globale_validazioni_sett);
			$save = 1;
		}

		$location_next = 'validazioni_utente.php?userid='.$userid.'&settid='.$settore_id.'&show_sf='.$show_sf.'&save='.$save;
		// header('location: '.$location_next);
		redirect(new moodle_url($location_next));
	}
}
else
{
	redirect(new moodle_url('/'));
}
