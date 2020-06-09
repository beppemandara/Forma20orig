<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - dicembre 2014
 * 
 * GRFO - Gestione Report Formazione On-line
 * 
 * Pagina che permette di modificare i parametri di controllo della gestione report formazione on line
 *      p_grfo_data_inizio_monitoraggio_corsi_on_line - usato per filtrare i corsi inseribili in monitoraggio
 *          (quando si costruisce la lista di scelta per inserimento corso in monitoraggio si considerano
 *          solo i corsi con almeno una edizione che inizia o fuinisce dopo la data specificata dal parametro)
 *  
 * Principali passi:
 * 
 * IN SOSPESO:
 */
// Inizializzazioni "varie" secondo standard Moodle
global $OUTPUT, $PAGE, $SITE;
//global $CFG, $DB;
require_once '../../../config.php';
require_login();
$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('block/f2_report:online', $context);
$baseurl = new moodle_url('/blocks/f2_report/online/report_on_line.php');
$blockname = get_string('pluginname', 'block_f2_report');
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/f2_report/online/report_on_line.php');
$PAGE->set_title(get_string('grfo_title_modifica_parametri', 'block_f2_report'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('grfo_modifica_parametri', 'block_f2_report'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('grfo_header_modifica_parametri', 'block_f2_report'));
// Definizione di costanti, variabili, function
require_once "costanti.php";
require_once "costanti_db.php";
require_once "strutture_dati.php";
require_once "lib_eml.php";
require_once "function_db.php";
require_once "form_definitions.php";
$rec_tbl_eml_grfo_log = new EML_RECtbl_eml_grfo_log();
$rec_tbl_eml_grfo_feed_back = new EML_RECtbl_eml_grfo_feed_back();
$rec_mdl_f2_parametri = new EML_RECmdl_f2_parametri();
$aus = EML_Connetti_db();
// Leggo i parametri di controllo GRFO
$id = 'p_grfo_data_inizio_monitoraggio';
$ret_code = EML_Get_mdl_f2_parametri($id, $rec_mdl_f2_parametri);
$data_inizio_monitoraggio = (int) $rec_mdl_f2_parametri->val_int;
// chiamo la form passandogli i valori iniziali dei parametri
$parametri_form = array('data_inizio_monitoraggio'=>$data_inizio_monitoraggio);
$mform = new form_modifica_parametri(null, $parametri_form);
if ($mform->is_cancelled()) {
    // se form cancellata torno a maschera con elenco corsi in gestione
    $url_elenco_corsi = new moodle_url('report_on_line.php');
    $delay = 1;
    redirect($url_elenco_corsi, null, $delay);
} else if ($fromform = $mform->get_data()) {
    // aggiorno il parametro in tabella mdl_f2_parametri
    $data_ricevuta = (int) $fromform->data_inizio_monitoraggio;
    $nome_tabella = 'mdl_f2_parametri';
    $clausola_update = ' SET val_int = '.$data_ricevuta;
    $clausola_where = " WHERE id = 'p_grfo_data_inizio_monitoraggio'";
    $ret_code = EML_Upd_xxx($nome_tabella, $clausola_update, $clausola_where);
    // messaggio di aggiornamento effettuato
    $rec_tbl_eml_grfo_log->id = NULL;
    $rec_tbl_eml_grfo_log->data = NULL;
    $rec_tbl_eml_grfo_log->id_corso = 0;
    $rec_tbl_eml_grfo_log->cod_corso = ' ';
    $rec_tbl_eml_grfo_log->titolo_corso = ' ';
    $rec_tbl_eml_grfo_log->pagina = 'Modifica_parametri.php';
    $rec_tbl_eml_grfo_log->livello_msg = EML_MSG_OPERAZIONI_SUL_DB;
    $rec_tbl_eml_grfo_log->cod_msg = EML_MSG_UPD_PARAMETRI;
    $rec_tbl_eml_grfo_log->descr_msg = get_string('grfo_log_modifica_parametri', 'block_f2_report');
    $rec_tbl_eml_grfo_log->username = $USER->username;
    $aus = $USER->idnumber." - ".$USER->lastname.", ".$USER->firstname;
    $rec_tbl_eml_grfo_log->utente = $aus;
    $nota = 'p_grfo_data_inizio_monitoraggio: '.$data_ricevuta;
    $rec_tbl_eml_grfo_log->nota = $nota;
    $ret_code = EML_Ins_tbl_eml_grfo_log($rec_tbl_eml_grfo_log);
    // preparo il record per la pagina di feed-back
    // lo inserisco in base dati (tabella tbl_eml_grfo_feed_back)
    // e vado alla pagina di feed-back
    $rec_tbl_eml_grfo_feed_back->id = NULL;
    $rec_tbl_eml_grfo_feed_back->id_corso = 0;
    $rec_tbl_eml_grfo_feed_back->cod_corso = ' ';
    $rec_tbl_eml_grfo_feed_back->titolo_corso = ' ';
    $rec_tbl_eml_grfo_feed_back->operazione = get_string('grfo_log_modifica_parametri', 'block_f2_report');
    $rec_tbl_eml_grfo_feed_back->stato = get_string('grfo_feed_back_tutto_ok', 'block_f2_report');
    $rec_tbl_eml_grfo_feed_back->url = 'report_on_line.php';
    $rec_tbl_eml_grfo_feed_back->flag_parametro_id_corso = get_string('grfo_feed_back_NO_parametro_id_corso', 'block_f2_report');
    $rec_tbl_eml_grfo_feed_back->nota = $nota;
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