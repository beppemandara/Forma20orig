<?php
global $OUTPUT, $PAGE, $SITE, $CFG, $DB;

require_once '../../config.php';
require_once($CFG->dirroot.'/f2_lib/management.php');
require_once 'lib_ind_senza_determina.php';
require_once($CFG->dirroot.'/local/f2_support/lib.php');
require_once('gestionecorsi_senza_determina_form.php');
require_once($CFG->dirroot.'/blocks/f2_gestione_risorse/lib.php');

require_login();
$context = context_system::instance();
$userid   = required_param('userid', PARAM_INT);
$training = required_param('training', PARAM_ALPHA);
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
$PAGE->navbar->add(get_string('creazioneanagrafica', 'block_f2_formazione_individuale'));
$PAGE->navbar->add(get_string('daticorso', 'block_f2_formazione_individuale'));
$PAGE->set_heading($SITE->shortname.': '.$blockname);

$capability_giunta = has_capability('block/f2_formazione_individuale:individualigiunta', $context);
$capability_linguagiunta = has_capability('block/f2_formazione_individuale:individualilinguagiunta', $context);
$capability_consiglio = has_capability('block/f2_formazione_individuale:individualiconsiglio', $context);

if (!(($capability_giunta && $training == $param_CIG->val_char) || ($capability_linguagiunta && $training == $param_CIL->val_char) || ($capability_consiglio && $training == $param_CIC->val_char))) {
  print_error('nopermissions', 'error', '', 'formazione_individuale');
}

$PAGE->requires->js('/blocks/f2_formazione_individuale/js/module.js');
// inizio import per generazione tabella //
$PAGE->requires->css('/f2_lib/jquery/css/dataTable.css');
$PAGE->requires->css('/f2_lib/jquery/css/ui_custom.css');
$PAGE->requires->js('/f2_lib/jquery/jquery-1.7.1.min.js');
$PAGE->requires->js('/f2_lib/jquery/jquery.dataTables.js');
$PAGE->requires->js('/f2_lib/jquery/custom.js');
$PAGE->requires->js('/f2_lib/jquery/jquery.blockUI.js');
// fine import per generazione tabella //

$jsmodule = array(
		  'name'     =>  'f2_formazione_individuale',
		  'fullpath' =>  '/blocks/f2_formazione_individuale/js/module.js',
		  'requires' =>  array(
                                       'base', 
                                       'attribute', 
                                       'node', 
                                       'datasource-io', 
                                       'datasource-jsonschema', 
                                       'node-event-simulate', 
                                       'event-key'
                                      )
                 );
$jsdata = array(sesskey());
$PAGE->requires->js_init_call('M.f2_formazione_individuale.init', $jsdata, true, $jsmodule);
$jsmodulec = array(
		   'name'     =>  'f2_course',
	 	   'fullpath' =>  '/local/f2_course/js/module.js',
		   'requires' =>  array(
                                        'base', 
                                        'attribute', 
                                        'node', 
                                        'datasource-io', 
                                        'datasource-jsonschema', 
                                        'node-event-simulate', 
                                        'event-key'
                                       )
                  );
$jsdatac = array(sesskey());
$PAGE->requires->js_init_call('M.f2_course.init', $jsdatac, true, $jsmodulec);
$codice_fiscale = $DB->get_field('user', 'username', array('id' => $userid));
$user = get_forzatura_or_moodleuser_ind($codice_fiscale);

$str ='
<script type="text/javascript">
function validateInput () {
  var titolo = document.getElementById(\'id_titolo\').value;
  var durata = document.getElementById(\'id_durata\').value;
  //var costo = document.getElementById(\'id_costo\').value;
  var cf = document.getElementById(\'id_credito_formativo\').value;
  var ente = document.getElementById(\'id_ente\').value;
  var localita = document.getElementById(\'id_localita\').value;
  var id_sf = document.getElementById(\'id_sf\').value;
  var id_af = document.getElementById(\'id_af\').value;
  var id_subaf = document.getElementById(\'id_subaf\').value;
  var id_tipologia_organizzativa = document.getElementById(\'id_tipologia_organizzativa\').value;
  var id_tipo = document.getElementById(\'id_tipo\').value;

  //if (titolo=="" || durata==""  || costo=="" || cf=="" || ente=="" || durata=="" || localita=="" || id_sf==""  || id_af==""  || id_subaf ==""  || id_tipologia_organizzativa =="" || id_tipo ==""  ) {
  if (titolo=="" || durata==""  || cf=="" || ente=="" || durata=="" || localita=="" || id_sf==""  || id_af==""  || id_subaf ==""  || id_tipologia_organizzativa =="" || id_tipo ==""  ) {
    alert("Non tutti i campi obbligatori sono stati compilati.");
  }
/*
  if (costo>=1) {
    alert("Il costo dei corsi senza determina non deve essere superiore a zero");
    return false;
  }
*/
}
function confirm_back(value_alert) {
  var agree=confirm("Tornando indietro i dati inseriti non saranno salvati.\nProseguire?");
  if (agree)
    parent.location=\'user_senza_determina.php?training='.$training.'\';
  else
    return false ;
}
</script>';
echo $str;
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('datiutente', 'block_f2_formazione_individuale'));
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
print_row(get_string('sesso', 'block_f2_formazione_individuale') . ':', $user->sesso);
print_row(get_string('categoria', 'block_f2_formazione_individuale') . ':', $user->category);
print_row(get_string('direzione', 'block_f2_formazione_individuale') . ':', $user->cod_direzione.' - '.$user->direzione);
print_row(get_string('settore', 'block_f2_formazione_individuale') . ':', $user->cod_settore.' - '.$user->settore);
echo "</table></div></div></br>";
echo $OUTPUT->heading(get_string('dati_corso', 'block_f2_formazione_individuale'));

$form = new inserisci_daticorso_senza_determina_form(NULL, compact('gest_corsi_ind_senza_determina', 'training','userid'));

if ($form->is_cancelled()) {
  redirect($gestionecorsi_url);
} else if ($data = $form->get_data()) {
  $data = $form->get_data();
  if (!$data->cassa_economale)
    $cassa_econom = 0;
  else
    $cassa_econom = $data->cassa_economale;

  $corso_ind_new = new stdClass();
  $corso_ind_new->training = $data->training;
  $corso_ind_new->codice_fiscale = $data->codice_fiscale;
  $corso_ind_new->partita_iva = $data->partita_iva;
  $corso_ind_new->orgfk = $user->orgfk_direzione; // da forzatura
  $corso_ind_new->userid = $data->userid;
  $corso_ind_new->note = $data->note;
  $corso_ind_new->beneficiario_pagamento = $data->beneficiario_pagamento;
  $corso_ind_new->cassa_economale = $cassa_econom;
  $corso_ind_new->titolo = $data->titolo;
  //$corso_ind_new->costo = $data->costo;
  $corso_ind_new->costo = 0;
  $corso_ind_new->area_formativa = $data->af;
  $corso_ind_new->tipologia_organizzativa = $data->tipologia_organizzativa;
  $corso_ind_new->tipo = $data->tipo;
  $corso_ind_new->durata = $data->durata;
  $corso_ind_new->ente = $data->ente;
  $corso_ind_new->via = $data->via;
  $corso_ind_new->localita = $data->localita;
  $corso_ind_new->sotto_area_formativa = $data->subaf;
  $corso_ind_new->data_inizio = $data->data_inizio;
  $corso_ind_new->credito_formativo = $data->credito_formativo;
  $corso_ind_new->sesso = $user->username;
  $corso_ind_new->segmento_formativo = $data->sf;
  $corso_ind_new->modello_email = $data->modello_email;
  $corso_ind_new->codice_creditore = $data->codice_creditore;
  $corso_ind_new->codice_archiviazione = $data->codice_archiviazione;

  if ($data->training == $param_CIL->val_char) {
    $corso_ind_new->tipo_pianificazione = $param_param_corsi_lingua->val_char;
  } else {
    $corso_ind_new->tipo_pianificazione = $param_param_corsi_ind->val_char;
  }
  $ret = $DB->insert_record('f2_corsiind_senza_spesa', $corso_ind_new);

  if ($ret)
    redirect(new moodle_url("/blocks/f2_formazione_individuale/gest_corsi_ind_senza_determina.php?training=".$training."&ret=1"));
  else
    redirect(new moodle_url("/blocks/f2_formazione_individuale/gest_corsi_ind_senza_determina.php?training=".$training."&ret=-1"));
}

$form->display();
echo $OUTPUT->footer();
