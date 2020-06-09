<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - aprile 2015
 * 
 * GRFO - Gestione Report Formazione On-line
 * 
 * Pagina che permette di aggiungere un corso al monitoraggio
 * 
 * Principali passi:
 * 
 * Inizializzazioni "varie" secondo standard Moodle (pagina, header, ecc.)
 * Definizione di costanti, variabili, function
 * Form che gestisce l'inserimento di un corso in monitoraggio
 *     Se form cancellata 
 *         torno a maschera con elenco corsi in gestione
 *     Altrimenti
 *         attivo la function di inserimento corso in tabelle 
 *             tbl_eml_pent_moduli_corsi_on_line (default monitorate le orime 10 risorse)
 *             tbl_eml_pent_edizioni_corsi_on_line (default monitorate tutte le edizioni)
 *         Se il corso ha più di 10 risorse oppure il corso ha abilitato il monitoraggio completamento
 *             messaggio feed-back (necessario nodificare i dati inseriti)
 *             redirect alla pagina di feed-back (che rimanda alla pagina di modifica corso)
 *         altrimenti
 *             messaggio feed-back (tutto ok)
 *             redirect alla pagina di feed-back (che rimanda all'elenco corsi in monitoraggio)
 *         fine se
 *     fine se
 * fine form che gestisce un nuovo corso
 * Visualizzazione/gestione form
 * Pie-pagina (secondo standard Moodle)
 * 
 */
// Inizializzazioni "varie" secondo standard Moodle
global $OUTPUT, $PAGE, $SITE, $USER;
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
$PAGE->set_title(get_string('grfo_title_nuovo_corso', 'block_f2_report'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('grfo_nuovo_corso', 'block_f2_report'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('grfo_header_nuovo_corso', 'block_f2_report'));
// Definizione di costanti, variabili, function
require_once "costanti.php";
require_once "costanti_db.php";
require_once "strutture_dati.php";
require_once "lib_eml.php";
require_once "function_db.php";
require_once "form_definitions.php";
require_once "Inserisci_corso_in_monitoraggio.php";
$rec_tbl_eml_grfo_log = new EML_RECtbl_eml_grfo_log();
$rec_tbl_eml_grfo_feed_back = new EML_RECtbl_eml_grfo_feed_back();
$rec_stato_inserimento = new EML_Stato_inserimento();
$aus = EML_Connetti_db();
// Form che gestisce un nuovo collegamento
$mform = new form_nuovo_corso(NULL);
if ($mform->is_cancelled()) {
    // se form cancellata torno a maschera con elenco corsi in gestione
    $url_elenco_corsi = new moodle_url('report_on_line.php');
    $delay = 1;
    redirect($url_elenco_corsi, null, $delay);
} else if ($fromform = $mform->get_data()) {
    // inserisco in base dati i record relativi alla gestione monitoraggio
    $id_corso = (int) $fromform->id_corso;
    $ret_code = Inserisci_corso_in_monitoraggio($id_corso, $rec_stato_inserimento);
    // messaggio di inserimento effettuato
    $rec_tbl_eml_grfo_log->id = NULL;
    $rec_tbl_eml_grfo_log->data = NULL;
    $rec_tbl_eml_grfo_log->id_corso = $id_corso;
    $rec_tbl_eml_grfo_log->cod_corso = $rec_stato_inserimento->cod_corso;
    $rec_tbl_eml_grfo_log->titolo_corso = $rec_stato_inserimento->titolo_corso;
    $rec_tbl_eml_grfo_log->pagina = 'Nuovo_corso.php';
    $rec_tbl_eml_grfo_log->livello_msg = EML_MSG_OPERAZIONI_SUL_DB;
    $rec_tbl_eml_grfo_log->cod_msg = EML_MSG_INS_CORSO;
    $rec_tbl_eml_grfo_log->descr_msg = get_string('grfo_log_inserimento_corso', 'block_f2_report');
    $rec_tbl_eml_grfo_log->username = $USER->username;
    $aus = $USER->idnumber." - ".$USER->lastname.", ".$USER->firstname;
    $rec_tbl_eml_grfo_log->utente = $aus;  
    if($rec_stato_inserimento->numero_risorse_monitorabili <> $rec_stato_inserimento->numero_risorse_monitorate) {
        $flag_troppe_risorse = TRUE;
        $aus = get_string('grfo_troppe_risorse_monitorabili', 'block_f2_report');
        $nota = $aus;
    } else {
        $flag_troppe_risorse = FALSE;
        $nota = ' ';
    }
    if($rec_stato_inserimento->enablecompletion == 1) {
        $flag_enablecompletion = TRUE;
        $aus = get_string('grfo_richiesto_enablecompletion', 'block_f2_report');
        $nota .= ' '.$aus;
    } else {
        $flag_enablecompletion = FALSE;
    }
    $rec_tbl_eml_grfo_log->nota = $nota;
    $ret_code = EML_Ins_tbl_eml_grfo_log($rec_tbl_eml_grfo_log);
    // preparo il record per la pagina di feed-back
    // lo inserisco in base dati (tabella tbl_eml_grfo_feed_back)
    // e vado alla pagina di feed-back
    $rec_tbl_eml_grfo_feed_back->id = NULL;
    $rec_tbl_eml_grfo_feed_back->id_corso = $id_corso;
    $rec_tbl_eml_grfo_feed_back->cod_corso = $rec_stato_inserimento->cod_corso;
    $rec_tbl_eml_grfo_feed_back->titolo_corso = $rec_stato_inserimento->titolo_corso;
    $rec_tbl_eml_grfo_feed_back->operazione = get_string('grfo_log_inserimento_corso', 'block_f2_report');
    $rec_tbl_eml_grfo_feed_back->stato = get_string('grfo_feed_back_tutto_ok', 'block_f2_report');
    if($flag_troppe_risorse || $flag_enablecompletion) {
        // troppe risosorse monitorabili oppure richiesto monitoraggio completamento corso
        //      redirect alla pagina modifica_corso
        $rec_tbl_eml_grfo_feed_back->url = 'modifica_corso.php';
        $rec_tbl_eml_grfo_feed_back->flag_parametro_id_corso = get_string('grfo_feed_back_SI_parametro_id_corso', 'block_f2_report');
        $rec_tbl_eml_grfo_feed_back->nota = $nota;
    } else {
        // tutto ok
        //      redirect alla pagina con elenco corsi in monitoraggio
        $rec_tbl_eml_grfo_feed_back->url = 'report_on_line.php';
        $rec_tbl_eml_grfo_feed_back->flag_parametro_id_corso = get_string('grfo_feed_back_NO_parametro_id_corso', 'block_f2_report');
        $rec_tbl_eml_grfo_feed_back->nota = get_string('grfo_tutte_le_risorse_monitorabili', 'block_f2_report');
    }
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