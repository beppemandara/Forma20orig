<?php
// $Id$
global $CFG,$DB,$OUTPUT;

require_once '../../../config.php';
require_once($CFG->dirroot.'/blocks/f2_gestione_risorse/lib.php');

$id_forn = $_POST[id_forn];

$context = get_context_instance(CONTEXT_SYSTEM);

$capability = has_capability('block/f2_gestione_risorse:add_fornitori', $context);
if(!$capability){
	print_error('nopermissions', 'error', '', 'fornitori');
}

$PAGE->set_context($context);
$PAGE->set_url('/blocks/f2_gestione_risorse/fornitori');
$PAGE->navbar->add(get_string('fornitori', 'block_f2_gestione_risorse'),new moodle_url('./anagrafica_fornitori.php'));
$PAGE->navbar->add(get_string('delete_fornitore', 'block_f2_gestione_risorse'), new moodle_url($url));
$PAGE->set_heading($SITE->shortname.': '.$blockname);
$PAGE->set_title(get_string('delete_fornitore', 'block_f2_gestione_risorse'));
$PAGE->set_pagelayout('standard');
$PAGE->settingsnav;


echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('delete_fornitore', 'block_f2_gestione_risorse'));
echo $OUTPUT->box_start();
echo '<div class="contenitoreglobale">';
//CONTROLLO SE E' STATO SELEZIONATO UN FORNITORE
if($id_forn){

	if(delete_fornitori($id_forn)){
		echo '<b>Fornitore\i eliminati correttamente.</b><br>';
		echo 'Seleziona il pulsante "Indietro" per tornare alla pagina dei fornitori.<br><br>';
		echo '<a href="anagrafica_fornitori.php"><button type="button">Indietro</button></a>';
	}
	else 
		echo 'Errore nell\'eliminazione!!';
}else{ 
		echo '<b>Non &egrave; stato selezionato nessun fornitore.</b><br>';
		echo '<a href="anagrafica_fornitori.php"><button type="button">Indietro</button></a>';
}

echo '</div>';

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
?>