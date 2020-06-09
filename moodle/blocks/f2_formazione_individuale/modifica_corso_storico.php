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
$id_course_storico		= required_param('id_course',PARAM_TEXT);

$label_training = get_lable_training($training);

$baseurl = new moodle_url('/blocks/f2_formazione_individuale/modifica_corso_storico.php?&training='.$training.'&id_course='.$id_course_storico);
$blockname = get_string('pluginname', 'block_f2_formazione_individuale');
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/f2_formazione_individuale/modifica_corso_storico.php');
$PAGE->set_title(get_string('modifica_corso_storico', 'block_f2_formazione_individuale'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string($label_training, 'block_f2_formazione_individuale'));
$PAGE->navbar->add(get_string('modifica_corso_storico', 'block_f2_formazione_individuale'), $baseurl);
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
		$mform =& $this->_form;

		$mform->addElement('hidden', 'id_course', $this->_customdata['id_course_storico']);
		//$dati_table = get_corso_archiviato_by_id($this->_customdata['id_course_storico']);
		if (!$dati_table = get_corso_archiviato_by_id($this->_customdata['id_course_storico'])) {
			$dati_table = get_corso_senza_spesa_archiviato_by_id($this->_customdata['id_course_storico']);
		}
		//$dati_table_senza_spesa = get_corso_senza_spesa_archiviato_by_id($this->_customdata['id_course_storico']);
//echo "ID STORICO: ".$this->_customdata['id_course_storico'];
//print_r($dati_table);
//print_object($dati_table);
//print_object($dati_table_senza_spesa);
		$training = $this->_customdata['training'];
		$mform->addElement('text', 'presenza',get_string('presenza','block_f2_formazione_individuale').":", 'maxlength="254" size="20"');
		$mform->setDefault('presenza',$dati_table->presenza);
		$mform->addRule('presenza', null, 'required',null, 'client');
	//	$mform->addRule('presenza', get_string('error_value', 'local_f2_traduzioni'), 'regex','/^\d*$/', 'client');
		$mform->addRule('presenza', get_string('error_value', 'local_f2_traduzioni'), 'regex','/^\d{1,3}(\.\d{1,2})?$/', 'client');
		
		$mform->addElement('text', 'credito_formativo_valido',get_string('credito_formativo_valido','block_f2_formazione_individuale').":", 'maxlength="254" size="20"');
		$mform->setDefault('credito_formativo_valido',$dati_table->cfv);
		$mform->addRule('credito_formativo_valido', null, 'required',null, 'client');
		$mform->addRule('credito_formativo_valido', get_string('error_value', 'local_f2_traduzioni'), 'regex','/^\d{1,3}(\.\d{1,2})?$/', 'client');

		$partecipazioni = get_all_partecipazioni_values();
//print_r($partecipazioni);
		$array_partecipazione =  array();
		foreach($partecipazioni as $partecipazione){
			$array_partecipazione[$partecipazione->id] = $partecipazione->codpart.' - '.$partecipazione->descrpart.' '.$partecipazione->invalid;
		}
		$default_partecipazione = get_partecipazione_by_cod_desc($dati_table->codpart,$dati_table->descrpart);
		$select = $mform->addElement('select', 'partecipazione', get_string('partecipazione','block_f2_formazione_individuale').":", $array_partecipazione);
		$select->setMultiple(false);
		$mform->addRule('partecipazione', null, 'required',null, 'client');
		$mform->setDefault('partecipazione',$default_partecipazione->id);


		$dati_verifica_apprendimento = get_va_values();
		$array_dati_verifica_apprendimento =  array();

		foreach($dati_verifica_apprendimento as $verifica_apprendimento){
			$array_dati_verifica_apprendimento[$verifica_apprendimento->id] = $verifica_apprendimento->descrizione;
		}
		$select = $mform->addElement('select', 'verifica_apprendimento', get_string('verifica_apprendimento','block_f2_formazione_individuale').":", $array_dati_verifica_apprendimento);
		$select->setMultiple(false);
		$mform->addRule('verifica_apprendimento', null, 'required',null, 'client');
		$mform->setDefault('verifica_apprendimento',$dati_table->va);

		$url_ind = new moodle_url('/blocks/f2_formazione_individuale/modifica_storico.php?training='.$training);
		$mform->addElement('html', '<table align="center"><tr><td>');
		$mform->addElement('html', '<input type="submit" value="'.get_string("salva", "block_f2_formazione_individuale").'">');
		$mform->addElement('html', '</td><td>');
		$mform->addElement('html', '<input type="reset" value="'.get_string("pulisci", "block_f2_formazione_individuale").'">');
		$mform->addElement('html', '</td><td>');
		$mform->addElement('html', '<input type="button" value="'.get_string("indietro", "block_f2_formazione_individuale").'" onclick="if(confirm(\'Tornando indietro non verranno salvate le modifiche effettuate.\nProseguire?\')) parent.location=\''.$url_ind.'\'">');
		$mform->addElement('html', '</td></tr></table>');
	}
}
$mform = new archivia_corso_form('modifica_corso_storico.php?training='.$training,compact('id_course_storico','training'),'post','',array('onKeyPress'=>'return disableEnterKey(event)'));
//FINE FORM


if ($data = $mform->get_data()) {
		$arc_corso = update_archivia_corso($data);
	if($arc_corso){
		redirect($CFG->wwwroot."/blocks/f2_formazione_individuale/modifica_storico.php?training=".$training."&mod_arc=1");
	}else{
		redirect($CFG->wwwroot."/blocks/f2_formazione_individuale/modifica_storico.php?training=".$training."&mod_arc=0");
	}
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modifica_corso_archiviato', 'block_f2_formazione_individuale'));

//$dati_table = get_corso_archiviato_by_id($id_course_storico);
if (!$dati_table = get_corso_archiviato_by_id($id_course_storico)) {
	$dati_table = get_corso_senza_spesa_archiviato_by_id($id_course_storico);
}
//$marticola = get_forzatura_or_moodleuser($dati_table->username);
echo '<h3>'.get_string('riepilogo_informazioni_corso', 'block_f2_formazione_individuale').'</h3>';

echo '<table width="40%">';
echo '<tr><td>'.get_string('richiedente', 'block_f2_formazione_individuale').':</td><td>'.$dati_table->cognome.' '.$dati_table->nome.'</td>';
//echo '<tr><td>'.get_string('matricola', 'block_f2_formazione_individuale').':</td><td>'.$marticola->idnumber.'</td>';
echo '<tr><td>'.get_string('matricola', 'block_f2_formazione_individuale').':</td><td>'.$dati_table->matricola.'</td>';
echo '<tr><td>'.get_string('nome_corso', 'block_f2_formazione_individuale').':</td><td>'.$dati_table->titolo.'</td>';
echo '<tr><td>'.get_string('data_inizio', 'block_f2_formazione_individuale').':</td><td>'.date('d/m/Y',$dati_table->data_inizio).'</td>';
echo '<tr><td>'.get_string('durata_cors', 'block_f2_formazione_individuale').':</td><td>'.number_format($dati_table->durata,2,",",".").'</td>';
echo '<tr><td>'.get_string('credito_formativo', 'block_f2_formazione_individuale').':</td><td>'.number_format($dati_table->cf,2,",",".").'</td>';
if ($dati_table->codice_archiviazione == '') { $dati_table->codice_archiviazione = 'n.a.'; }
echo '<tr><td>'.get_string('codice_archiviazione', 'block_f2_formazione_individuale').':</td><td>'.$dati_table->codice_archiviazione.'</td>';
echo '<tr><td>'.get_string('ente', 'block_f2_formazione_individuale').':</td><td>'.$dati_table->ente.'</td>';
if ($dati_table->codice_determina == '') { $dati_table->codice_determina = 'n.a.'; }
echo '<tr><td>'.get_string('codice_determina', 'block_f2_formazione_individuale').':</td><td>'.$dati_table->codice_determina.'</td>';
if ($dati_table->data_determina == '') {
	//$dati_table->data_determina = 'n.a.';
	echo '<tr><td>'.get_string('data_determina', 'block_f2_formazione_individuale').':</td><td>n.a.</td>';
} else {
	echo '<tr><td>'.get_string('data_determina', 'block_f2_formazione_individuale').':</td><td>'.date('d/m/Y',$dati_table->data_determina).'</td>';
}
echo '</table>';
echo '<h3>'.get_string('dati_storico_corso', 'block_f2_formazione_individuale').'</h3>';

$mform->display();

echo $OUTPUT->footer();
