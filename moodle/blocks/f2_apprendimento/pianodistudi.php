<?php

//$Id: pianodistudi.php 1241 2013-12-20 04:34:05Z l.moretto $
global $CFG, $USER, $OUTPUT, $PAGE, $SITE;

require_once '../../config.php';
require_once 'lib.php';
// require_once($CFG->dirroot.'/f2_lib/core.php');
// require_once($CFG->dirroot.'/f2_lib/management.php');

require_login();
//$context = get_context_instance(CONTEXT_SYSTEM);
//require_capability('block/f2_apprendimento:viewpianodistudi', $context);
//require_capability('block/f2_apprendimento:viewpianodistudi',get_context_instance(CONTEXT_COURSE, 1));
require_capability('block/f2_apprendimento:viewpianodistudi',context_course::instance(1));


$userID = optional_param('userid', intval($USER->id), PARAM_INT);

$blockname = get_string('pluginname', 'block_f2_apprendimento');

//$PAGE->set_context($context);
$PAGE->set_context(context_course::instance(1));
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/f2_apprendimento/pianodistudi.php');
$PAGE->set_title(get_string('pianodistudi', 'block_f2_apprendimento'));
$PAGE->settingsnav;

if ($userID != intval($USER->id))
{
	$navbar_pds_dipendenti = new moodle_url('/blocks/f2_apprendimento/pianodistudi_utenti.php');
	$PAGE->navbar->add(get_string('pianodistudidip', 'block_f2_apprendimento'), $navbar_pds_dipendenti);
}

$PAGE->navbar->add(get_string('pianodistudi', 'block_f2_apprendimento'),new moodle_url('/blocks/f2_apprendimento/pianodistudi.php?userid='.$userID));
$PAGE->set_heading($SITE->shortname.': '.$blockname);

echo $OUTPUT->header();

$currenttab = 'pianodistudi';
require('tabs_curriculum.php');

echo $OUTPUT->heading(get_string('pianodistudi', 'block_f2_apprendimento'));
echo $OUTPUT->box_start();
echo "<h3>".get_string('dettagli_utente', 'block_f2_apprendimento')."</h3>";

$userdata = get_user_data($userID);
$direzione = get_direzione_utente($userID);
$settore = get_settore_utente($userID);

echo 	"<div align='right'><a href=\"javascript:window.print()\">
		<input type='button' name='pianostudi' value='Stampa'/>
		</a></div>";

$table_anag = new html_table();
$table_anag->align = array('right', 'left');
$table_anag->data = array(
		array('Cognome Nome ','<b>'.$userdata->lastname.' '.$userdata->firstname.'</b>'),
		array('Matricola',''.$userdata->idnumber.''),
		array('Categoria',''.$userdata->category.''),
		array('Direzione / Ente',''.(is_null($direzione) ? '' : $direzione['shortname']." - ".$direzione['name'].'')),
		array('Settore',''.(is_null($settore) ? '' : $settore['shortname']." - ".$settore['name']))
);

echo html_writer::table($table_anag);

$lastupdate = get_user_last_update($userdata->idnumber);
$dates = get_obj_date_piano_di_studi();
$aSF = get_segmento_formativo();

$user_category = $userdata->category;
$cat_cf_necessari = get_user_totali_crediti($user_category);
$aSF_cp = get_user_crediti_for_settore($user_category);															// crediti richiesti per il superamento del piano
$aSF_ca_corrente = get_user_storico_crediti_attivi($userdata->idnumber, 'corrente', $dates, $user_category); 	// crediti attivi per SF dell'anno corrente
$aSF_ca_precedente = get_user_storico_crediti_attivi($userdata->idnumber, 'precedente', $dates, $user_category);// crediti attivi per SF dell'anno precedente
$aSF_cu_corrente = get_user_storico_crediti_utilizzabili($userdata->idnumber, $aSF_ca_corrente, $aSF_cp);		// crediti utilizzabili per SF dell'anno corrente																	
$aSF_cu_precedente = get_user_storico_crediti_utilizzabili($userdata->idnumber, $aSF_ca_precedente, $aSF_cp);	// crediti utilizzabili per SF dell'anno precedente

echo table_piano_studi(
		$lastupdate, $dates, $cat_cf_necessari, $aSF, $aSF_cp, $aSF_ca_corrente, $aSF_ca_precedente, $aSF_cu_corrente, $aSF_cu_precedente);

if ($userID != $USER->id)
	echo "<p style='text-align:center'><a href='".$CFG->wwwroot."/blocks/f2_apprendimento/pianodistudi_utenti.php'>Torna indietro</a></p>";

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
