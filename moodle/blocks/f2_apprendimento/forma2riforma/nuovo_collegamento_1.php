<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * CREG - Upload da Riforma
 * 
 * Pagina che permette di collegare i corsi di Riforma -- FORMA20
 * 
 * Principali passi:
 * 
 * Inizializzazioni "varie" secondo standard Moodle (pagina, header, ecc.)
 * Definizione di costanti, variabili, function
 * Form che gestisce un nuovo collegamento
 *     Se form cancellata 
 *         torno a maschera con elenco corsi in gestione
 *     Altrimenti
 *         preparo il record da inserire in mdl_f2_forma2riforma_mapping
 *             lettura dei dati corso da Forma
 *             lettura dei dati corso da Riforma
 *             valorizzo lo stato (di gestione) del corso
 *         inserimento record in tabella mdl_f2_forma2riforma_mapping
 *         messaggio di log in tabella mdl_f2_forma2riforma_log
 *         redirect alla pagina con elenco corsi in gestione
 *     fine se
 * fine form che gestisce un nuovo collegamento
 * Visualizzazione/gestione form
 * Pie-pagina (secondo standard Moodle)
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
$PAGE->set_url('/blocks/f2_apprendimento/forma2riforma/nuovo_collegamento_1.php');
$PAGE->set_title(get_string('f2r_title_nuovocollegamento', 'block_f2_apprendimento'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('f2r_forma2riforma', 'block_f2_apprendimento'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('f2r_header_nuovocollegamento', 'block_f2_apprendimento'));
// Definizione di costanti, variabili, function
require_once "costanti.php";
require_once "costanti_db.php";
require_once "strutture_dati.php";
require_once "connessioni_al_db.php";
require_once "function_db.php";
require_once "form_definitions.php";
$rec_mdl_course_Forma = new EML_RECmdl_course();
$rec_mdl_course_Riforma = new EML_RECmdl_course();
global $mysqli_Riforma;
EML_Connetti_db_Riforma();
//echo "<pre>mysqli nuovo_collegamento_1.php v1: ".var_dump($mysqli_Riforma)."</pre>";die();
// Form che gestisce un nuovo collegamento
$mform = new form_nuovo_collegamento(NULL);
if ($mform->is_cancelled()) {
    // se form cancellata torno a maschera con elenco corsi in gestione
    $elenco_corsi_url = new moodle_url('elenco_corsi.php');
    $delay = 1;
    redirect($elenco_corsi_url, null, $delay);
} else if ($fromform = $mform->get_data()) {
    // preparo il record da inserire in mdl_f2_forma2riforma_mapping
    $record = new stdClass();
    $record->id_forma20 = (int) $fromform->id_forma20;
    $record->perc_x_cfv = (int) $fromform->perc_x_cfv;
    $record->va_default = $fromform->va_default;
    // lettura dei dati corso da Forma
    $ret_code = Get_mdl_course_Forma20($fromform->id_forma20, $rec_mdl_course_Forma);
    $record->shortname = $rec_mdl_course_Forma->shortname;
    //lettura dei dati corso da Riforma
    $ret_code = Get_mdl_course_Riforma($rec_mdl_course_Forma->shortname, $rec_mdl_course_Riforma);
    $record->id_riforma = (int) $rec_mdl_course_Riforma->id;
    $record->data_inizio = (int) $rec_mdl_course_Riforma->startdate;
    // valorizzo lo stato (di gestione) del corso
    $record->stato = EML_RIFORMA_MAPPING_OK;
    $aus = get_string('f2r_mapping_ok', 'block_f2_apprendimento');
    $record->nota = $aus;
    // inserimento in tabella mdl_f2_forma2riforma_mapping
    $lastinsertid = $DB->insert_record('f2_forma2riforma_mapping', $record);
    // messaggio di log in tabella mdl_f2_forma2riforma_log
    $record_log = new stdClass();
    $record_log->shortname = $rec_mdl_course_Forma->shortname;
    $record_log->data_ora = time();
    $record_log->codice = EML_RIFORMA_INS_MAPPING;
    $aus = get_string('f2r_inserito_collegamento', 'block_f2_apprendimento');
    $aus .= " - perc_x_cfv: ".$fromform->perc_x_cfv."% - va_default: ".$fromform->va_default;
    $record_log->descrizione = $aus;
    $lastinsertid = $DB->insert_record('f2_forma2riforma_log', $record_log);
    // redirect alla pagina con elenco corsi in gestione
    redirect(new moodle_url("/blocks/f2_apprendimento/forma2riforma/elenco_corsi.php"));
} 
// Visualizzazione/gestione form
$mform->display();
// Pie-pagina (secondo standard Moodle)
echo $OUTPUT->footer();
?>