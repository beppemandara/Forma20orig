<?php

//$Id: prenotazioni.php 173 2012-09-13 08:20:23Z g.nuzzolo $
global $USER,$DB;

require_once '../../config.php';
require_once 'lib.php';

require_login();
$blockid = get_block_id(get_string('pluginname_db','block_f2_prenotazioni'));
require_capability('block/f2_prenotazioni:editvalidazioni', get_context_instance(CONTEXT_BLOCK, $blockid));

$location_next = 'validazioni_altri.php';
$userid=$USER->id;

if($userid==0) $userid=$USER->id;
else if($userid!=0 && has_capability('block/f2_prenotazioni:editvalidazioni', get_context_instance(CONTEXT_BLOCK, $blockid)) && validate_own_dipendente($userid)) $userid=$userid;
// else header('location: '.$location_next);
else redirect(new moodle_url($location_next));

// get settore
$settore_id = required_param('settid', PARAM_INT);
$location_param = '?organisationid='.$settore_id;
$location_param .= '&br=1';

if (!canManageDomain($settore_id)) die();

$isOk = check_stati_validazione_per_utente($USER->id);
if ($isOk == true and isDirezione($settore_id))
{
	global $DB;
	$anno = get_anno_formativo_corrente();
	
	$extra_budget = get_extra_budget();

	$budget_aula = get_budget_totali_per_direzione($settore_id,$anno,'aula');
	
	$budget_online = get_budget_totali_per_direzione($settore_id,$anno,'online');
	
	//restituisce 1 solo record, mi ricavo i valori (globali) necessari
	$bdg_money_aula = $budget_aula[key($budget_aula)]->money_bdgt;
	$bdg_days_aula = $budget_aula[key($budget_aula)]->days_bdgt;
	
	$bdg_money_online = $budget_online[key($budget_online)]->money_bdgt;
	
// 	print_r($bdg_money_aula);echo ' aula_norm<br/>';
	$bdg_money_aula_extra = $bdg_money_aula + ($bdg_money_aula * $extra_budget /100);
	
	$bdg_money_online_extra = $bdg_money_online + ($bdg_money_online * $extra_budget /100);

// 	print_r($bdg_money_aula);echo ' aula_extra<br/>';
// 	exit;
// 	print_r($bdg_money_aula);echo '<br/>';
// 	print_r($bdg_days_aula);echo '<br/>';

	$validati_aula_costo_durata = get_valori_bdg_validati($settore_id,$anno,'aula');
	
	$validati_online_costo_durata = get_valori_bdg_validati($settore_id,$anno,'online');
	
	//restituisce 1 solo record, mi ricavo i valori delle validazioni
	$bdg_val_money_aula = $validati_aula_costo_durata->costo_tot;
	$bdg_val_days_aula = $validati_aula_costo_durata->durata_tot;
	
	$bdg_val_money_online = $validati_online_costo_durata->costo_tot;
	
// 	print_r($bdg_val_money_aula);echo '<br/>';
// 	print_r($bdg_val_days_aula);echo '<br/>';
// 	exit;
	
	$anomalie = array();
	if ($bdg_val_money_aula > $bdg_money_aula_extra)
	{
		$diff = $bdg_val_money_aula - $bdg_money_aula_extra;
		$anomalie['money_aula']['bdg_avail_money'] = round($bdg_money_aula,2,PHP_ROUND_HALF_UP);
// 		$anomalie['money_aula']['bdg_avail_money'] = $bdg_money_aula;
		$anomalie['money_aula']['bdg_avail_money_extra'] = round($bdg_money_aula_extra,2,PHP_ROUND_HALF_UP);
// 		$anomalie['money_aula']['bdg_avail_money_extra'] = $bdg_money_aula_extra;
		$anomalie['money_aula']['bdg_req_money'] = round($bdg_val_money_aula,2,PHP_ROUND_HALF_UP);
// 		$anomalie['money_aula']['bdg_req_money'] = $bdg_val_money_aula;
		$anomalie['money_aula']['bdg_diff_money'] = round($diff,2,PHP_ROUND_HALF_UP);
	}
	if ($bdg_val_days_aula > $bdg_days_aula)
	{
		$diff = $bdg_val_days_aula - $bdg_days_aula;
		$anomalie['days_aula']['bdg_avail_days'] = round($bdg_days_aula,2,PHP_ROUND_HALF_UP);
// 		$anomalie['days_aula']['bdg_avail_days'] = $bdg_days_aula;
		$anomalie['days_aula']['bdg_req_days'] = $bdg_val_days_aula;
		$anomalie['days_aula']['bdg_diff_days'] = round($diff,2,PHP_ROUND_HALF_UP);
// 		$anomalie['days']['bdg_diff_days'] = ceil($diff);
	}
	$diff = 0;
// 	print_r($bdg_val_money_online);echo '<br/>';
// 	print_r($bdg_money_online_extra);exit;
	if ($bdg_val_money_online > $bdg_money_online_extra)
	{
		$diff = $bdg_val_money_online - $bdg_money_online_extra;
		$anomalie['money_online']['bdg_avail_money'] = round($bdg_money_online,2,PHP_ROUND_HALF_UP);
// 		$anomalie['money_online']['bdg_avail_money'] = $bdg_money_online;
		$anomalie['money_online']['bdg_avail_money_extra'] = round($bdg_money_online_extra,2,PHP_ROUND_HALF_UP);
// 		$anomalie['money_online']['bdg_avail_money_extra'] = $bdg_money_online_extra;
		$anomalie['money_online']['bdg_req_money'] = round($bdg_val_money_online,2,PHP_ROUND_HALF_UP);
// 		$anomalie['money_online']['bdg_req_money'] = $bdg_val_money_online;
		$anomalie['money_online']['bdg_diff_money'] = round($diff,2,PHP_ROUND_HALF_UP);
	}

// 	print_r($diff);echo '<br/>';
// 	print_r($anomalie);exit;
		
	if (count($anomalie) == 0)
	{
		//update stato validazione direzione = E
		update_stato_validazione_dir_budget($settore_id,$anno,'E');
		/*
		$msg = get_string('bdg_rispettato','block_f2_prenotazioni');
		
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
			*/
		// header('location: '.$location_next.$location_param);
		redirect(new moodle_url($location_next.$location_param));
	}
	else // budget non rispettato
	{
		$_SESSION['anomalie_budget'] = json_encode($anomalie);
		//update stato validazione direzione = D
		update_stato_validazione_dir_budget($settore_id,$anno,'D');
		// header('location: '.$location_next.$location_param);
		redirect(new moodle_url($location_next.$location_param));
	}
}
else
{
	// header('location: '.$location_next.$location_param);
	redirect(new moodle_url($location_next.$location_param));
}