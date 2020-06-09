<?php
// $Id$
global $CFG,$DB,$OUTPUT;

require_once '../../config.php';
require_once($CFG->dirroot.'/local/f2_notif/lib.php');

$id_temp = $_POST[id_temp];
require_login();
$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($context);
$PAGE->set_url('/local/f2_notif');
$PAGE->navbar->add(get_string('modelli_notifica', 'local_f2_notif'),new moodle_url('./templates.php'));
$PAGE->navbar->add(get_string('delete_notif', 'local_f2_notif'), new moodle_url($url));
$PAGE->set_heading($SITE->shortname.': '.$blockname);
$PAGE->set_title(get_string('delete_notif', 'local_f2_notif'));
$PAGE->set_pagelayout('standard');
$PAGE->settingsnav;


echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('delete_notif', 'local_f2_notif'));
echo $OUTPUT->box_start();
echo '<div class="contenitoreglobale">';
//CONTROLLO SE E' STATO SELEZIONATO UN FORNITORE
if($id_temp){

	if(delete_template($id_temp)){
		echo '<b>Template eliminato/i correttamente.</b><br>';
		echo 'Seleziona il pulsante "Indietro" per tornare alla pagina dei template.<br><br>';
		echo '<a href="templates.php"><button type="button">Indietro</button></a>';
	}
	else 
		echo 'Errore nell\'eliminazione!!';
}else{ 
		echo '<b>Non &egrave; stato selezionato nessun template.</b><br>';
		echo '<a href="templates.php"><button type="button">Indietro</button></a>';
}

echo '</div>';

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
?>