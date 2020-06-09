<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - luglio 2015
 * 
 * GREP - Gestione Report
 * 
 * Pagina che permette di aggiungere una nuovo report agganciandolo ad una voce menù report
 * 
 * Principali passi:
 * 
 * Inizializzazioni "varie" secondo standard Moodle (pagina, header, ecc.)
 * Definizione di costanti, variabili, function
 * Form che gestisce l'inserimento di un nuovo report
 *     Se form cancellata 
 *         torno a pagina con elenco report associati alla voce di menù
 *     Altrimenti
 *         attivo la function di inserimento report in tabella mdl_f2_csi_pent_report
 *         redirect alla pagina di inserimento/modifica parametri/ruoli associati al report
 *     fine se
 * fine form che gestisce una nuovo report
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
$PAGE->set_title(get_string('grep_title_nuovo_report', 'block_f2_report'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('grep_gestione_menu_report', 'block_f2_report'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
echo $OUTPUT->header();
// Definizione di costanti, variabili, function
require_once "grep_costanti.php";
require_once "grep_costanti_db.php";
require_once "grep_strutture_dati.php";
require_once "grep_function_db.php";
require_once "grep_form_definitions.php";
require_once "../online/lib_eml.php";
// connessione al data-base
$aus = EML_Connetti_db();
$id_voce_menu = $_REQUEST['id_voce_menu'];
$rec_mdl_f2_csi_pent_menu_report = new EML_RECmdl_f2_csi_pent_menu_report();
$aus = EML_Get_mdl_f2_csi_pent_menu_report($id_voce_menu, $rec_mdl_f2_csi_pent_menu_report);
$aus = get_string('grep_header_nuovo_report', 'block_f2_report').$rec_mdl_f2_csi_pent_menu_report->descrizione;
echo $OUTPUT->heading($aus);
//Form che gestisce l'inserimento di un nuovo Report
$mform = new form_nuovo_report(NULL);
if ($mform->is_cancelled()) {
    // se form cancellata torno a maschera con elenco report associati alla voce
    $id_voce_menu = $_REQUEST['id_voce_menu'];
    $url_gestione_menu_report = new moodle_url('grep_gestione_report.php?id_voce_menu='.$id_voce_menu);
    $delay = 1;
    redirect($url_gestione_menu_report, null, $delay);
} else if ($fromform = $mform->get_data()) {
    // carico in variabili locali i dati letti
    $rec_mdl_f2_csi_pent_report = new EML_RECmdl_f2_csi_pent_report();
    $rec_mdl_f2_csi_pent_report->id_menu_report = $fromform->id_voce_menu;
    $rec_mdl_f2_csi_pent_report->nome_report = $fromform->nome_report;
    $rec_mdl_f2_csi_pent_report->nome_file_pentaho = $fromform->nome_file_pentaho;
    $rec_mdl_f2_csi_pent_report->posizione_in_elenco_report = $fromform->posizione_in_elenco_report;
    $rec_mdl_f2_csi_pent_report->attivo = $fromform->flag_attivo;
    $rec_mdl_f2_csi_pent_report->formato_default = $fromform->formato_default;
    // definisco ed inizializzo il record per la pagina di feed-back
    $rec_tbl_eml_grep_feed_back = new EML_RECtbl_eml_grep_feed_back();
    $rec_tbl_eml_grep_feed_back->operazione = 'Inserimento nuovo Report';
    $rec_tbl_eml_grep_feed_back->stato = ' ';
    $rec_tbl_eml_grep_feed_back->url = 'grep_gestione_report.php?id_voce_menu='.$rec_mdl_f2_csi_pent_report->id_menu_report;
    $rec_tbl_eml_grep_feed_back->nota_1 = ' ';
    $rec_tbl_eml_grep_feed_back->nota_2 = ' ';
    $rec_tbl_eml_grep_feed_back->nota_3 = ' ';
    $rec_tbl_eml_grep_feed_back->nota_4 = ' ';
    // verifico se possibile inserire in base dati il nuovo report
    // controllo di non duplicazione nome report (in questa voce di menù)
    $nome_tabella = 'mdl_f2_csi_pent_report';
    $clausola_where = " WHERE nome_report = '".$mysqli->real_escape_string($rec_mdl_f2_csi_pent_report->nome_report)."'"
                     ." AND id_menu_report = ".$rec_mdl_f2_csi_pent_report->id_menu_report;
    $numero_record = 0;
    $ret_code = EML_Get_Numero_record_in_tabella($nome_tabella, $clausola_where, $numero_record);
    if ($numero_record > 0) {
        $rec_tbl_eml_grep_feed_back->nota_1 = 'In tabella mdl_f2_csi_pent_report è già presente un record con nome = '
                                             .$rec_mdl_f2_csi_pent_report->nome_report;
        $flag_errore_1 = 1;
    } else {
        $flag_errore_1 = 0;
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
    if ($flag_errore_1 > 0) {
        $rec_tbl_eml_grep_feed_back->stato = "Impossibile procedere con l'inserimento";
        $rec_tbl_eml_grep_feed_back->url = 'grep_gestione_report.php?id_voce_menu='.$rec_mdl_f2_csi_pent_report->id_menu_report;
    } else {
        $ret_code = EML_Ins_mdl_f2_csi_pent_report($rec_mdl_f2_csi_pent_report);
        if ($ret_code > 0) {
            $rec_tbl_eml_grep_feed_back->stato = 'Inserimento effettuato correttamente';
            $rec_tbl_eml_grep_feed_back->nota_1 = 'Report inserito: '.$rec_mdl_f2_csi_pent_report->nome_report;
            $rec_tbl_eml_grep_feed_back->nota_2 = 'Occorre specificare i Ruoli abilitati e gli eventuali parametri';
            $rec_tbl_eml_grep_feed_back->url = 'grep_modifica_parametri_report.php?id_report='.$ret_code;
        } else {
            $rec_tbl_eml_grep_feed_back->stato = "Errori in inserimento. Error code mysql =".-$ret_code;
            $rec_tbl_eml_grep_feed_back->url = 'grep_gestione_report.php?id_voce_menu='.$rec_mdl_f2_csi_pent_report->id_menu_report;
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