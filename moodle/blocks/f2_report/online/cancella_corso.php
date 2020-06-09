<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - aprile 2015
 * 
 * GRFO - Gestione Report Formazione On-line
 * 
 * Cancellazione corso dal monitoraggio
 * 
 * Visualizza i dati del corso (titolo, risorse, edizioni)
 * in fondo alla pagina ci sono i pulsanti per
 *     Confermate la cancellazione
 *     Annullare l'operazione
 */
// Inizializzazioni "varie" secondo standard Moodle
global $OUTPUT, $PAGE, $SITE, $CFG; $USER;
require_once '../../../config.php';
require_login();
$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('block/f2_report:online', $context);
$baseurl = new moodle_url('/blocks/f2_report/online/cancella_corso.php');
$blockname = get_string('pluginname', 'block_f2_report');
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/f2_report/online/cancella_corso.php');
$PAGE->set_title(get_string('grfo_title_cancella_corso', 'block_f2_report'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('grfo_cancella_corso', 'block_f2_report'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('grfo_header_cancella_corso', 'block_f2_report'));
require_once "costanti.php";
require_once "costanti_db.php";
require_once "strutture_dati.php";
require_once "function_db.php";
require_once "lib_eml.php";
require_once "form_definitions.php";
$rec_tbl_eml_grfo_log = new EML_RECtbl_eml_grfo_log();
$rec_tbl_eml_grfo_feed_back = new EML_RECtbl_eml_grfo_feed_back();
$aus = EML_Connetti_db();
// Form che gestisce la cancellazione
$mform = new form_cancella_corso(NULL);
if ($mform->is_cancelled()) {
    // se form cancellata torno a maschera con elenco corsi in gestione
    $url_elenco_corsi = new moodle_url('report_on_line.php');
    $delay = 1;
    redirect($url_elenco_corsi, null, $delay);
} else if ($fromform = $mform->get_data()) {
    // effettuo le cancellazioni 
    $id_corso = (int) $fromform->id_corso;
    $cod_corso = $fromform->cod_corso;
    $titolo_corso = $fromform->titolo_corso;
    // tabella tbl_eml_pent_moduli_corsi_on_line
    $nome_tabella = 'tbl_eml_pent_moduli_corsi_on_line';
    $clausola_where = ' WHERE id_corso = '.$id_corso;
    $iaus = EML_Del_xxx($nome_tabella, $clausola_where);
    // tabella tbl_eml_pent_edizioni_corsi_on_line
    $nome_tabella = 'tbl_eml_pent_edizioni_corsi_on_line';
    $clausola_where = ' WHERE id_corso = '.$id_corso;
    $iaus = EML_Del_xxx($nome_tabella, $clausola_where);
    // tabella tbl_eml_pent_monitoraggio_corsi_on_line
    $nome_tabella = 'tbl_eml_pent_monitoraggio_corsi_on_line';
    $clausola_where = ' WHERE id_corso = '.$id_corso;
    $iaus = EML_Del_xxx($nome_tabella, $clausola_where);
    // tabella tbl_eml_pent_completamento_corsi_on_line
    $nome_tabella = 'tbl_eml_pent_completamento_corsi_on_line';
    $clausola_where = ' WHERE id_corso = '.$id_corso;
    $iaus = EML_Del_xxx($nome_tabella, $clausola_where);
    // messaggio di cancellzione effettuata
    $rec_tbl_eml_grfo_log->id = NULL;
    $rec_tbl_eml_grfo_log->data = NULL;
    $rec_tbl_eml_grfo_log->id_corso = $id_corso;
    $rec_tbl_eml_grfo_log->cod_corso = $cod_corso;
    $rec_tbl_eml_grfo_log->titolo_corso = $titolo_corso;
    $rec_tbl_eml_grfo_log->pagina = 'Cancella_corso.php';
    $rec_tbl_eml_grfo_log->livello_msg = EML_MSG_OPERAZIONI_SUL_DB;
    $rec_tbl_eml_grfo_log->cod_msg = EML_MSG_DEL_CORSO;
    $rec_tbl_eml_grfo_log->descr_msg = get_string('grfo_log_cancellazione_corso', 'block_f2_report');
    $rec_tbl_eml_grfo_log->username = $USER->username;
    $aus = $USER->idnumber." - ".$USER->lastname.", ".$USER->firstname;
    $rec_tbl_eml_grfo_log->utente = $aus;
    $rec_tbl_eml_grfo_log->nota = ' ';
    $ret_code = EML_Ins_tbl_eml_grfo_log($rec_tbl_eml_grfo_log);
    // preparo il record per la pagina di feed-back
    // lo inserisco in base dati (tabella tbl_eml_grfo_feed_back)
    // e vado alla pagina di feed-back
    $rec_tbl_eml_grfo_feed_back->id = NULL;
    $rec_tbl_eml_grfo_feed_back->id_corso = $id_corso;
    $rec_tbl_eml_grfo_feed_back->cod_corso = $cod_corso;
    $rec_tbl_eml_grfo_feed_back->titolo_corso = $titolo_corso;
    $rec_tbl_eml_grfo_feed_back->operazione = get_string('grfo_log_cancellazione_corso', 'block_f2_report');
    $rec_tbl_eml_grfo_feed_back->stato = get_string('grfo_feed_back_tutto_ok', 'block_f2_report');
    $rec_tbl_eml_grfo_feed_back->url = 'report_on_line.php';
    $rec_tbl_eml_grfo_feed_back->flag_parametro_id_corso = get_string('grfo_feed_back_NO_parametro_id_corso', 'block_f2_report');
    $rec_tbl_eml_grfo_feed_back->nota = ' ';
    $id_x_pagina_feed_back = EML_Ins_tbl_eml_grfo_feed_back($rec_tbl_eml_grfo_feed_back);
    $url_pagina_feed_back = new moodle_url('feed_back_page.php', array('id'=>$id_x_pagina_feed_back));
    $delay = 1;
    redirect($url_pagina_feed_back, null, $delay);
}
// Visualizzazione/gestione form
$mform->display();
// Pie-pagina (secondo standard Moodle)
echo $OUTPUT->footer();
?>
