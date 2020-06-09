<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - luglio 2015
 * 
 * GREP - Gestione Report
 * 
 * Pagina che permette di cancellare un report
 * 
 * Principali passi:
 * 
 * Inizializzazioni "varie" secondo standard Moodle (pagina, header, ecc.)
 * Definizione di costanti, variabili, function
 * Form che gestisce la cancellazione del report
 *     Se form cancellata 
 *         torno a pagina con elenco report associati alla voce di menù
 *     Altrimenti
 *         attivo le function di cancellazione report dalle tabelle:
 *              mdl_f2_csi_pent_report
 *              mdl_f2_csi_pent_param_map
 *              mdl_f2_csi_pent_role_map
 *         redirect alla pagina di feed-back
 *     fine se
 * fine form che gestisce la cancellazione report
 * Visualizzazione/gestione form
 * Pie-pagina (secondo standard Moodle)
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
$PAGE->set_title(get_string('grep_title_cancella_report', 'block_f2_report'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('grep_gestione_menu_report', 'block_f2_report'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('grep_header_cancella_report', 'block_f2_report'));
// Definizione di costanti, variabili, function
require_once "grep_costanti.php";
require_once "grep_costanti_db.php";
require_once "grep_strutture_dati.php";
require_once "grep_function_db.php";
require_once "grep_form_definitions.php";
require_once "../online/lib_eml.php";
// connessione al data-base
$aus = EML_Connetti_db();
//Form che gestisce la cancellazione di un Report
$mform = new form_cancella_report(NULL);
if ($mform->is_cancelled()) {
    // se form cancellata torno a maschera con elenco report associati alla voce
    $id_menu_report = $_REQUEST['id_menu_report'];
    $url_gestione_menu_report = new moodle_url('grep_gestione_report.php?id_voce_menu='.$id_menu_report);
    $delay = 1;
    redirect($url_gestione_menu_report, null, $delay);
} else if ($fromform = $mform->get_data()) {
    // carico in variabili locali i dati letti
    $id_report = $fromform->id_report;
    $id_menu_report = $_REQUEST['id_menu_report'];
    $nome_report = $_REQUEST['nome_report'];
    // attivo la cancellazione del report dalle tabelle
    //      mdl_f2_csi_pent_report
    //      mdl_f2_csi_pent_param_map
    //      mdl_f2_csi_pent_role_map
    $nome_tabella = 'mdl_f2_csi_pent_report';      
    $clausola_where = ' where id = '.$id_report;      
    $ret_code = EML_Del_xxx($nome_tabella, $clausola_where);
    $nome_tabella = 'mdl_f2_csi_pent_param_map';
    $clausola_where = ' where id_report = '.$id_report;
    $ret_code = EML_Del_xxx($nome_tabella, $clausola_where);
    $nome_tabella = 'mdl_f2_csi_pent_role_map';
    $clausola_where = ' where id_report = '.$id_report;
    $ret_code = EML_Del_xxx($nome_tabella, $clausola_where);
    // definisco ed inizializzo il record per la pagina di feed-back
    $rec_tbl_eml_grep_feed_back = new EML_RECtbl_eml_grep_feed_back();
    $rec_tbl_eml_grep_feed_back->operazione = 'Cancellazione report';
    $rec_tbl_eml_grep_feed_back->stato = 'Cancellazione terminata';
    $rec_tbl_eml_grep_feed_back->url = 'grep_gestione_report.php?id_voce_menu='.$id_menu_report;
    $rec_tbl_eml_grep_feed_back->nota_1 = 'Report cancellato: '.$nome_report;
    $rec_tbl_eml_grep_feed_back->nota_2 = ' ';
    $rec_tbl_eml_grep_feed_back->nota_3 = ' ';
    $rec_tbl_eml_grep_feed_back->nota_4 = ' ';
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