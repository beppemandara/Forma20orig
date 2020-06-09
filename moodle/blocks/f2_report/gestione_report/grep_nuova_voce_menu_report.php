<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - luglio 2015
 * 
 * GREP - Gestione Report
 * 
 * Pagina che permette di aggiungere una nuova Voce menu Report
 * 
 * Principali passi:
 * 
 * Inizializzazioni "varie" secondo standard Moodle (pagina, header, ecc.)
 * Definizione di costanti, variabili, function
 * Form che gestisce l'inserimento di una nuova Voce in menu_report
 *     Se form cancellata 
 *         torno a pagina con elenco voci di menu report
 *     Altrimenti
 *         attivo la function di inserimento voce in tabella mdl_f2_csi_pent_menu_report
 *         redirect alla pagina con elenco voci menu report
 *     fine se
 * fine form che gestisce una nuova voce di menu report
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
/////////////////////////////////////////////////require_capability('block/f2_report:gestione', $context);
$baseurl = new moodle_url('/blocks/f2_report/grep_gestione_menu_report.php');
$blockname = get_string('grep_pluginname', 'block_f2_report');
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/f2_report/grep_gestione_menu_report.php');
$PAGE->set_title(get_string('grep_title_nuova_voce_menu_report', 'block_f2_report'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('grep_gestione_menu_report', 'block_f2_report'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('grep_header_nuova_voce_menu_report', 'block_f2_report'));
// Definizione di costanti, variabili, function
require_once "grep_costanti.php";
require_once "grep_costanti_db.php";
require_once "grep_strutture_dati.php";
require_once "grep_function_db.php";
require_once "grep_form_definitions.php";
require_once "../online/lib_eml.php";
// definisco ed inizializzo il record per la pagina di feed-back
$rec_tbl_eml_grep_feed_back = new EML_RECtbl_eml_grep_feed_back();
$rec_tbl_eml_grep_feed_back->operazione = ' ';
$rec_tbl_eml_grep_feed_back->stato = ' ';
$rec_tbl_eml_grep_feed_back->url = 'grep_gestione_menu_report.php';
$rec_tbl_eml_grep_feed_back->nota_1 = ' ';
$rec_tbl_eml_grep_feed_back->nota_2 = ' ';
$rec_tbl_eml_grep_feed_back->nota_3 = ' ';
$rec_tbl_eml_grep_feed_back->nota_4 = ' ';
// connessione al data-base
$aus = EML_Connetti_db();
//Form che gestisce l'inserimento di una nuova Voce in menu_report
$mform = new form_nuova_voce_menu_report(NULL);
if ($mform->is_cancelled()) {
    // se form cancellata torno a maschera con elenco voci di menù report
    $url_gestione_menu_report = new moodle_url('grep_gestione_menu_report.php');
    $delay = 1;
    redirect($url_gestione_menu_report, null, $delay);
} else if ($fromform = $mform->get_data()) {
    // carico in variabili locali i dati letti
    $rec_mdl_f2_csi_pent_menu_report = new EML_RECmdl_f2_csi_pent_menu_report();
    $rec_mdl_f2_csi_pent_menu_report->codice = $fromform->codice_voce_menu;
    $rec_mdl_f2_csi_pent_menu_report->descrizione = $fromform->descrizione_voce_menu;
    $rec_mdl_f2_csi_pent_menu_report->attiva = $fromform->flag_attiva;
    $rec_tbl_eml_grep_feed_back->operazione = 'Inserimento nuova voce in menù Report';
    // verifico se possibile inserire in base dati la nuova voce di menu report
    // controllo di non duplicazione codice voce
    $nome_tabella = 'mdl_f2_csi_pent_menu_report';
    $clausola_where = " WHERE codice = '".$rec_mdl_f2_csi_pent_menu_report->codice."'";
    $numero_record = 0;
    $ret_code = EML_Get_Numero_record_in_tabella($nome_tabella, $clausola_where, $numero_record);
    if ($numero_record > 0) {
        $rec_tbl_eml_grep_feed_back->nota_1 = 'In tabella mdl_f2_csi_pent_menu_report è già presente un record con codice = '
                                             .$rec_mdl_f2_csi_pent_menu_report->codice;
        $flag_errore_1 = 1;
    } else {
        $flag_errore_1 = 0;
    }
    // controllo di non duplicazione descrizione voce
    $nome_tabella = 'mdl_f2_csi_pent_menu_report';
    $clausola_where = " WHERE descrizione = '".$rec_mdl_f2_csi_pent_menu_report->descrizione."'";
    $numero_record = 0;
    $ret_code = EML_Get_Numero_record_in_tabella($nome_tabella, $clausola_where, $numero_record);
    if ($numero_record > 0) {
        $rec_tbl_eml_grep_feed_back->nota_2 = 'In tabella mdl_f2_csi_pent_menu_report è già presente un record con descrizione = '
                                             .$rec_mdl_f2_csi_pent_menu_report->descrizione;
        $flag_errore_2 = 1;
    } else {
        $flag_errore_2 = 0;
    }
    //  Se non possibile inserire la voce in base dati
    //      imposto messaggio di anomalia
    //  altrimenti (possibile inserire la voce in base dati)
    //      scrivo il record in tabella mdl_f2_csi_pent_menu_report
    //      se scrittura OK
    //          imposto messaggio di successo
    //      altrimenti
    //          imposto messaggio di anomalia
    //      fine se
    //  fine se
    if (($flag_errore_1+$flag_errore_2) > 0) {
        $rec_tbl_eml_grep_feed_back->stato = "Impossibile procedere con l'inserimento";
    } else {
        $ret_code = EML_Ins_mdl_f2_csi_pent_menu_report($rec_mdl_f2_csi_pent_menu_report);
        if ($ret_code > 0) {
            $rec_tbl_eml_grep_feed_back->stato = 'Inserimento effettuato correttamente';
            $rec_tbl_eml_grep_feed_back->nota_1 = 'Voce inserita: '.$rec_mdl_f2_csi_pent_menu_report->descrizione;
        } else {
            $rec_tbl_eml_grep_feed_back->stato = "Errori in inserimento. Error code mysql =".-$ret_code;
        }
    }
    // Redirect alla pagina di feed-back
    $id_x_pagina_feed_back = EML_Ins_tbl_eml_grep_feed_back($rec_tbl_eml_grep_feed_back);
    $url_pagina_feed_back = new moodle_url('grep_feed_back_page.php', array('id'=>$id_x_pagina_feed_back));
    $delay = 1;
    redirect($url_pagina_feed_back, null, $delay);
} 
// Visualizzazione/gestione form
$mform->display();
// Pie-pagina (secondo standard Moodle)
echo $OUTPUT->footer();