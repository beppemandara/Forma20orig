<?php
global $OUTPUT, $PAGE, $SITE, $CFG, $DB;
require_once '../../config.php';
require_once 'lib_ind_senza_determina.php';
//require_once($CFG->dirroot.'/local/f2_notif/lib.php');
//require_once($CFG->dirroot.'/f2_lib/core.php');
//require_once($CFG->dirroot.'/lib/tcpdf/tcpdf.php');
require_once($CFG->dirroot.'/blocks/f2_gestione_risorse/lib.php');

require_login();
$context = context_system::instance();
$training	= required_param('training', PARAM_TEXT);
$id_corso = required_param('id_course', PARAM_INT);
$label_training = get_label_training($training);
$gestionecorsi_url = new moodle_url('/blocks/f2_formazione_individuale/gest_corsi_ind_senza_determina.php?training='.$training.'');
$blockname = get_string('pluginname', 'block_f2_formazione_individuale');
$param_CIG = get_parametro('p_f2_corsi_individuali_giunta');
$param_CIL = get_parametro('p_f2_corsi_individuali_lingua_giunta');
$param_CIC = get_parametro('p_f2_corsi_individuali_consiglio');
$param_param_corsi_lingua = get_parametro('p_f2_tipo_pianificazione_1'); // Corsi di lingua con insegnante
$param_param_corsi_ind = get_parametro('p_f2_tipo_pianificazione_2'); // Corsi Individuali

$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$url = new moodle_url("{$CFG->wwwroot}/blocks/f2_formazione_individuale/aggiungi_anagrafica_senza_determina.php", array('userid' => $userid));
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('daticorso', 'block_f2_formazione_individuale'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string($label_training, 'block_f2_formazione_individuale'));
$PAGE->navbar->add(get_string('gestionecorsigratis', 'block_f2_formazione_individuale'), $gestionecorsi_url);
$PAGE->navbar->add(get_string('dettaglio_mail', 'block_f2_formazione_individuale'));
$PAGE->set_heading($SITE->shortname.': '.$blockname);

$capability_giunta = has_capability('block/f2_formazione_individuale:individualigiunta', $context);
$capability_linguagiunta = has_capability('block/f2_formazione_individuale:individualilinguagiunta', $context);
$capability_consiglio = has_capability('block/f2_formazione_individuale:individualiconsiglio', $context);

if(!(($capability_giunta && $training == $param_CIG->val_char) || ($capability_linguagiunta && $training == $param_CIL->val_char) || ($capability_consiglio && $training == $param_CIC->val_char))){
	print_error('nopermissions', 'error', '', 'formazione_individuale');
}
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('dettaglio_mail', 'block_f2_formazione_individuale'));

// recupero dati del corso
if ($course_data = get_course_data_senza_determina($id_corso)) {
  $utente_corso = get_dati_utente_corso($course_data->userid);
  $codice_fiscale = $DB->get_field('user', 'username', array('id' => $course_data->userid));
  $user = get_forzatura_or_moodleuser_ind($codice_fiscale);
  echo '<div class="userprofile">';
  echo '<div class="userprofilebox clearfix"><div class="profilepicture">';
  echo $OUTPUT->user_picture($user, array('size'=>100));
  echo '</div>';
  // Print all the little details in a list
  echo '<table class="list" summary="">';
  $override = new stdClass();
  $override->firstname = 'firstname';
  $override->lastname = 'lastname';
  $fullnamelanguage = get_string('fullnamedisplay', '', $override);
  if (($CFG->fullnamedisplay == 'firstname lastname') or
      ($CFG->fullnamedisplay == 'firstname') or
      ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'firstname lastname' )) {
    $fullnamedisplay = get_string('firstname').' / '.get_string('lastname');
  } else {
    $fullnamedisplay = get_string('lastname').' / '.get_string('firstname');
  }
  print_row($fullnamedisplay . ':', fullname($user, true));
  print_row(get_string('matricola', 'block_f2_formazione_individuale') . ':', $user->idnumber);
  print_row(get_string('direzione', 'block_f2_formazione_individuale') . ':', $user->cod_direzione.' - '.$user->direzione);
  print_row(get_string('settore', 'block_f2_formazione_individuale') . ':', $user->cod_settore.' - '.$user->settore);
  print_row(get_string('E_mail', 'block_f2_formazione_individuale') . ':', $user->email);
  print_row(get_string('data_invio_mail', 'block_f2_formazione_individuale') . ':', date('d/m/Y',$course_data->data_invio_mail));
  print_row(get_string('Esito_invio_mail', 'block_f2_formazione_individuale') . ':', 'Inviata');
  echo "</table></div></div></br>";
} else {
  echo "<h2 style='color:red;text-align:center;'>".get_string('errore_dettaglio_mail','block_f2_formazione_individuale')."</h2>";
}
echo '<input type="button" value="'.get_string("indietro", "block_f2_formazione_individuale").'" onclick="parent.location=\''.$gestionecorsi_url.'\'">';

echo $OUTPUT->footer();
