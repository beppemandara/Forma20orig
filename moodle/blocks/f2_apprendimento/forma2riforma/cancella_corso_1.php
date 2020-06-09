<?php
/* A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * CREG - Upload da Riforma
 * 
 * Pagina che gestisce la cancellazione di un corso
 * 
 * Parametri:
 *     $_REQUEST['id'] -- id del corso (in Forma)
 */
// Inizializzazioni "varie" secondo standard Moodle
global $OUTPUT, $PAGE, $SITE, $CFG, $DB;
require_once '../../../config.php';
require_login();
$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('block/f2_apprendimento:forma2riforma', $context);
$baseurl = new moodle_url('/blocks/f2_apprendimento/forma2riforma/elenco_corsi.php');
$blockname = get_string('pluginname', 'block_f2_apprendimento');
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/f2_apprendimento/forma2riforma/cancella_corso_1.php');
$PAGE->set_title(get_string('f2r_title_cancellacorso', 'block_f2_apprendimento'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('f2r_forma2riforma', 'block_f2_apprendimento'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('f2r_header_cancellacorso', 'block_f2_apprendimento'));
require_once "strutture_dati.php";
require_once "function_db.php";
require_once "form_definitions.php";
// Form che gestisce un nuovo collegamento
$mform = new form_cancella_corso(NULL);
if ($mform->is_cancelled()) {
    // se form cancellata torno a maschera con elenco corsi in gestione
    $elenco_corsi_url = new moodle_url('elenco_corsi.php');
    redirect($elenco_corsi_url, null);
} else if ($fromform = $mform->get_data()) {
    // acquisizione di id e shortname del corso
    $id_mapping = $fromform->id;
    $shortname = $fromform->shortname;
    // messaggio di log (inizio cancellazione corso) in tabella mdl_f2_forma2riforma_log
    $record_log = new stdClass();
    $record_log->shortname = $shortname;
    $record_log->data_ora = time();
    $record_log->codice = EML_RIFORMA_START_DEL_CORSO;
    $aus = get_string('f2r_start_delete_collegamento', 'block_f2_apprendimento');
    $record_log->descrizione = $aus;
    $lastinsertid = $DB->insert_record('f2_forma2riforma_log', $record_log);
    // cancello le partecipazioni associate al corso
    // con relativo messaggio di log
    $ret_code = Del_mdl_f2_forma2riforma_partecipazioni($id_mapping);
    $record_log->shortname = $shortname;
    $record_log->data_ora = time();
    $record_log->codice = EML_RIFORMA_DEL_PARTECIPAZIONI;
    $aus = get_string('f2r_delete_partecipazioni', 'block_f2_apprendimento').$ret_code;
    $record_log->descrizione = $aus;
    $lastinsertid = $DB->insert_record('f2_forma2riforma_log', $record_log);
    // cancello il collegamento Riforma--Forma
    // con relativo messaggio di log
    $ret_code = Del_mdl_f2_forma2riforma_mapping($id_mapping);
    $record_log->shortname = $shortname;
    $record_log->data_ora = time();
    $record_log->codice = EML_RIFORMA_DEL_MAPPING;
    $aus = get_string('f2r_delete_collegamento', 'block_f2_apprendimento');
    $record_log->descrizione = $aus;
    $lastinsertid = $DB->insert_record('f2_forma2riforma_log', $record_log);
    // messaggio di log (fine cancellazione corso) in tabella mdl_f2_forma2riforma_log
    $record_log->shortname = $shortname;
    $record_log->data_ora = time();
    $record_log->codice = EML_RIFORMA_END_DEL_CORSO;
    $aus = get_string('f2r_end_delete_collegamento', 'block_f2_apprendimento');
    $record_log->descrizione = $aus;
    $lastinsertid = $DB->insert_record('f2_forma2riforma_log', $record_log);
    // redirect alla pagina con elenco corsi in gestione
    $elenco_corsi_url = new moodle_url('elenco_corsi.php');
    redirect($elenco_corsi_url, null);
}
// Visualizzazione/gestione form
$mform->display();
// Chiusura pagina (secondo standard Moodle)
echo $OUTPUT->footer();
?>