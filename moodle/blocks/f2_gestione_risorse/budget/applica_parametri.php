<?php
// $Id: applica_parametri.php 794 2012-12-03 10:51:58Z c.arnolfo $
global $CFG,$DB,$OUTPUT;

require_once '../../../config.php';
require_once($CFG->dirroot.'/blocks/f2_gestione_risorse/lib.php');

$context = get_context_instance(CONTEXT_SYSTEM);

$capability = has_capability('block/f2_gestione_risorse:budget_edit', $context);
if(!$capability){
	print_error('nopermissions', 'error', '', 'budget');
}

$button_budget = optional_param('button_budget', 0, PARAM_INT);

//if (button_budget==1) é stato cliccato il pulsante "Aggiorna Totali" della pagina configurazione_parametri.php
//if (button_budget==2) é stato cliccato il pulsante "Gestione Capitoli" della pagina configurazione_parametri.php
//if (button_budget==3) é stato cliccato il pulsante "Applica" della pagina configurazione_parametri.php

if($button_budget == 1){
	$anno_in_corso = get_anno_formativo_corrente();
	if(update_totali_partial_budget($anno_in_corso)){
		// header('Location: configurazione_parametri.php');
		$location_next = $CFG->wwwroot.'/blocks/f2_gestione_risorse/budget/configurazione_parametri.php';
		redirect(new moodle_url($location_next));
	}
	else echo 'Ci sono stati degli errori nell\'aggiornamento dei totali';
}

else if($button_budget == 2){
	// header('Location: inserisci_budget.php');
	$location_next = $CFG->wwwroot.'/blocks/f2_gestione_risorse/budget/inserisci_budget.php';
	redirect(new moodle_url($location_next));
}


//E' atato cliccato il pulsante Applica
else if($button_budget == 3){
	applica_partial_budget($_POST);
	// header('Location: configurazione_parametri.php');
	$location_next = $CFG->wwwroot.'/blocks/f2_gestione_risorse/budget/configurazione_parametri.php';
	redirect(new moodle_url($location_next));
}



?>