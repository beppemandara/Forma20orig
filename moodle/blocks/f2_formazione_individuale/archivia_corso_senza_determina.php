<?php
global $OUTPUT, $PAGE, $SITE, $CFG, $DB;

require_once '../../config.php';
require_once 'lib.php';
require_once 'lib_ind_senza_determina.php';
require_once 'archivia_corso_senza_determina_form.php';
require_login();
$context = context_system::instance();
require_capability('block/f2_apprendimento:leggistorico', $context);

$dato_ricercato = optional_param('dato_ricercato', '', PARAM_ALPHANUM);
$training       = required_param('training', PARAM_TEXT);
$id_course      = required_param('id_course',PARAM_TEXT);

$label_training = get_label_training($training);
$gestionecorsi_url = new moodle_url('/blocks/f2_formazione_individuale/gest_corsi_ind_senza_determina.php?training='.$training.'');
add_to_log_archiviazione_corsiind_senza_determina($id_course, 'START Archiviazione corso senza determina');
$baseurl = new moodle_url('/blocks/f2_formazione_individuale/archivia_corso_senza_determina.php?&training='.$training.'&dato_ricercato='.$dato_ricercato);
$blockname = get_string('pluginname', 'block_f2_formazione_individuale');
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/f2_formazione_individuale/archivia_corso_senza_determina.php');
$PAGE->set_title(get_string('archivia_corso', 'block_f2_formazione_individuale'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string($label_training, 'block_f2_formazione_individuale'));
$PAGE->navbar->add(get_string('gestionecorsigratis', 'block_f2_formazione_individuale'), $gestionecorsi_url);
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

$mform = new archivia_corso_senza_determina_form(NULL, array('training'=>$training, 'id_course'=>$id_course));

if ($mform->is_cancelled()) {
  redirect($gestionecorsi_url);
} else if ($data = $mform->get_data()) {
  add_to_log_archiviazione_corsiind_senza_determina($id_course, 'Richiamo archivia_corso_senza_determina');
  $data = $mform->get_data();
  $arc_corso = archivia_corso_senza_determina($data);
  if($arc_corso) {
    add_to_log_archiviazione_corsiind_senza_determina($id_course, 'Archiviazione eseguita: '.$arc_corso);
    redirect($CFG->wwwroot."/blocks/f2_formazione_individuale/gest_corsi_ind_senza_determina.php?training=".$training."&arc=1");
  } else {
    add_to_log_archiviazione_corsiind_senza_determina($id_course_ind, 'Archiviazione non eseguita: '.$arc_corso);
    redirect($CFG->wwwroot."/blocks/f2_formazione_individuale/gest_corsi_ind_senza_determina.php?training=".$training."&arc=0");
  }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('archivia_corso', 'block_f2_formazione_individuale'));

$data = new stdClass;
$data->id_corso_ind = $id_course;
$dati_table = get_scheda_descrittiva_archiviazione($data);
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
echo '</table>';

if(!($marticola->cod_settore || $marticola->cod_direzione)){
  echo '<div style="padding:5px; background-color: #DDD;width: 95%;">';
  echo get_string('no_forzatura', 'block_f2_formazione_individuale');
  echo '<br><br></div><br>';
  echo '<input type="button" value="'.get_string("indietro", "block_f2_formazione_individuale").'" onclick="parent.location=\''.$CFG->wwwroot.'/blocks/f2_formazione_individuale/gest_corsi_ind_senza_determina.php?training='.$training.'\'">';
} else {
  echo '<h3>'.get_string('dati_storico_corso', 'block_f2_formazione_individuale').'</h3>';
  $mform->display();
}

echo $OUTPUT->footer();
