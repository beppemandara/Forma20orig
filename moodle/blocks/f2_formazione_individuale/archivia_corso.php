<?php
//$Id$ 
global $OUTPUT, $PAGE, $SITE;

require_once '../../config.php';
require_once 'lib.php';

require_login();
$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('block/f2_apprendimento:leggistorico', $context);

$dato_ricercato         = optional_param('dato_ricercato', '', PARAM_ALPHANUM);
$sort                   = optional_param('sort', 'ASC', PARAM_ALPHANUM);
$column   				= optional_param('column', 'cognome', PARAM_TEXT);
$dir                    = optional_param('dir', 'ASC', PARAM_ALPHA);
$page                   = optional_param('page', 0, PARAM_INT);
$perpage                = optional_param('perpage', 20, PARAM_INT);        // how many per page
$training	            = required_param('training', PARAM_TEXT);
$id_course_ind			= required_param('id_course',PARAM_TEXT);

$label_training = get_lable_training($training);

$baseurl = new moodle_url('/blocks/f2_formazione_individuale/archivia_corso.php?&training='.$training.'&dato_ricercato='.$dato_ricercato);
$blockname = get_string('pluginname', 'block_f2_formazione_individuale');
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/f2_formazione_individuale/archivia_corso.php');
$PAGE->set_title(get_string('archivia_corso', 'block_f2_formazione_individuale'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string($label_training, 'block_f2_formazione_individuale'));
$PAGE->navbar->add(get_string('archivia_corso', 'block_f2_formazione_individuale'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
$PAGE->requires->js('/blocks/f2_formazione_individuale/js/module.js');

$param_CIG = get_parametro('p_f2_corsi_individuali_giunta');
$param_CIL = get_parametro('p_f2_corsi_individuali_lingua_giunta');
$param_CIC = get_parametro('p_f2_corsi_individuali_consiglio');
$capability_giunta = has_capability('block/f2_formazione_individuale:individualigiunta', $context);
$capability_linguagiunta = has_capability('block/f2_formazione_individuale:individualilinguagiunta', $context);
$capability_consiglio = has_capability('block/f2_formazione_individuale:individualiconsiglio', $context);

if(!(($capability_giunta && $training == $param_CIG->val_char) || ($capability_linguagiunta && $training == $param_CIL->val_char) || ($capability_consiglio && $training == $param_CIC->val_char))){
	print_error('nopermissions', 'error', '', 'formazione_individuale');
}

//INIZIO FORM
class archivia_corso_form extends moodleform {
	public function definition() {
		global $CFG;
		
		$mform =& $this->_form;

		$mform->addElement('hidden', 'id_course', $this->_customdata['id_course_ind']);
		$training = $this->_customdata['training'];

		$mform->addElement('text', 'presenza',get_string('presenza','block_f2_formazione_individuale').":", 'maxlength="254" size="20"');
		$mform->setDefault('presenza',0);
		$mform->addRule('presenza', null, 'required',null, 'client');
	//	$mform->addRule('presenza', get_string('error_value', 'local_f2_traduzioni'), 'regex','/^\d*$/', 'client');
		$mform->addRule('presenza', get_string('error_value', 'local_f2_traduzioni'), 'regex','/^\d{1,3}(\.\d{1,2})?$/', 'client');
		
		$mform->addElement('text', 'credito_formativo_valido',get_string('credito_formativo_valido','block_f2_formazione_individuale').":", 'maxlength="254" size="20"');
		$mform->setDefault('credito_formativo_valido',0);
		$mform->addRule('credito_formativo_valido', null, 'required',null, 'client');
		$mform->addRule('credito_formativo_valido', get_string('error_value', 'local_f2_traduzioni'), 'regex','/^\d{1,3}(\.\d{1,2})?$/', 'client');

		$partecipazioni = get_partecipazioni_values();

		$array_partecipazione =  array();
		foreach($partecipazioni as $partecipazione){
			$array_partecipazione[$partecipazione->id] = $partecipazione->codpart.' - '.$partecipazione->descrpart;
		}

		$select = $mform->addElement('select', 'partecipazione', get_string('partecipazione','block_f2_formazione_individuale').":", $array_partecipazione);
		$select->setMultiple(false);
		$mform->addRule('partecipazione', null, 'required',null, 'client');
		$mform->setDefault('partecipazione',10);


		$dati_verifica_apprendimento = get_va_values();
		$array_dati_verifica_apprendimento =  array();

		foreach($dati_verifica_apprendimento as $verifica_apprendimento){
			$array_dati_verifica_apprendimento[$verifica_apprendimento->id] = $verifica_apprendimento->descrizione;
		}
		$select = $mform->addElement('select', 'verifica_apprendimento', get_string('verifica_apprendimento','block_f2_formazione_individuale').":", $array_dati_verifica_apprendimento);
		$select->setMultiple(false);
		$mform->addRule('verifica_apprendimento', null, 'required',null, 'client');
		$mform->setDefault('verifica_apprendimento',10);
		$url_ind = new moodle_url($CFG->wwwroot."/blocks/f2_formazione_individuale/archiviazione_storico.php?training=".$training."");
		$mform->addElement('html', '<table align="center"><tr><td>');
		$mform->addElement('html', '<input type="submit" value="'.get_string("salva", "block_f2_formazione_individuale").'">');
		$mform->addElement('html', '</td><td>');
		$mform->addElement('html', '<input type="reset" value="'.get_string("pulisci", "block_f2_formazione_individuale").'">');
		$mform->addElement('html', '</td><td>');
		$mform->addElement('html', '<input type="button" value="'.get_string("indietro", "block_f2_formazione_individuale").'" onclick="if(confirm(\'Tornando indietro non verrà archiviato nessun corso.\nProseguire?\')) parent.location=\''.$url_ind.'\'">');
		$mform->addElement('html', '</td></tr></table>');

	}
}
$mform = new archivia_corso_form('archivia_corso.php?training='.$training,compact('id_course_ind','training'),'post','',array('onKeyPress'=>'return disableEnterKey(event)'));
//FINE FORM


if ($data = $mform->get_data()) {
		$arc_corso = archivia_corso($data);
	if($arc_corso){
		redirect($CFG->wwwroot."/blocks/f2_formazione_individuale/archiviazione_storico.php?training=".$training."&arc=1");
	}else{
		redirect($CFG->wwwroot."/blocks/f2_formazione_individuale/archiviazione_storico.php?training=".$training."&arc=0");
	}
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('archivia_corso', 'block_f2_formazione_individuale'));

$data = new stdClass;
$data->id_corso_ind = $id_course_ind;
$dati_table = get_scheda_descrittiva_determine_by_id($data);
$marticola = get_forzatura_or_moodleuser($dati_table->username);
echo '<h3>'.get_string('riepilogo_informazioni_corso', 'block_f2_formazione_individuale').'</h3>';

echo '<table width="40%">';
echo '<tr><td>'.get_string('richiedente', 'block_f2_formazione_individuale').':</td><td>'.$dati_table->lastname.' '.$dati_table->firstname.'</td>';
echo '<tr><td>'.get_string('matricola', 'block_f2_formazione_individuale').':</td><td>'.$marticola->idnumber.'</td>';
echo '<tr><td>'.get_string('nome_corso', 'block_f2_formazione_individuale').':</td><td>'.$dati_table->titolo.'</td>';
echo '<tr><td>'.get_string('data_inizio', 'block_f2_formazione_individuale').':</td><td>'.date('d/m/Y',$dati_table->data_inizio).'</td>';
echo '<tr><td>'.get_string('durata_cors', 'block_f2_formazione_individuale').':</td><td>'.number_format($dati_table->durata,2,",",".").'</td>';
echo '<tr><td>'.get_string('credito_formativo', 'block_f2_formazione_individuale').':</td><td>'.number_format($dati_table->credito_formativo,2,",",".").'</td>';
echo '<tr><td>'.get_string('codice_archiviazione', 'block_f2_formazione_individuale').':</td><td>'.$dati_table->codice_archiviazione.'</td>';
echo '<tr><td>'.get_string('ente', 'block_f2_formazione_individuale').':</td><td>'.$dati_table->ente.'</td>';
echo '<tr><td>'.get_string('codice_determina', 'block_f2_formazione_individuale').':</td><td>'.$dati_table->codice_determina.'</td>';
echo '<tr><td>'.get_string('data_determina', 'block_f2_formazione_individuale').':</td><td>'.date('d/m/Y',$dati_table->data_determina).'</td>';
echo '</table>';
	

if(!($marticola->cod_settore || $marticola->cod_direzione)){
	echo '<div style="padding:5px; background-color: #DDD;width: 95%;">';
	echo get_string('no_forzatura', 'block_f2_formazione_individuale');
	//echo 'Non è presente nessuna direzione e/o settore per l\'utente e non è possibile procedere all\'archiviazione;
	//echo '<br><a href="'.$CFG->wwwroot.'/blocks/f2_formazione_individuale/mod_determina_prov.php?training='.$training.'"><input type="submit" name="assegna_codice_det_prov" value="Assegna codice determina provvisorio" /></a>';
	echo '<br><br>';
	echo '</div>';
	echo '<br>';
	echo '<input type="button" value="'.get_string("indietro", "block_f2_formazione_individuale").'" onclick="parent.location=\''.$CFG->wwwroot.'/blocks/f2_formazione_individuale/archiviazione_storico.php?training='.$training.'\'">';
}else{
	echo '<h3>'.get_string('dati_storico_corso', 'block_f2_formazione_individuale').'</h3>';
	$mform->display();
}

echo $OUTPUT->footer();