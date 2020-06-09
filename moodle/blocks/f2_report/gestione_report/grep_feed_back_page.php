<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - giugno 2015
 * 
 * GREP - Gestione Report
 * 
 * Pagina di feed-back (messaggio su come è andata l'operazione appena effettuata)
 * 
 * Legge dalla tabella tbl_eml_grep_feed_back il record con id = parametro ricevuto
 * Cancella il record dalla tabella tbl_eml_grep_feed_back
 * Visualizza i dati letti (corso, operazione, stato, nota)
 * Va alla pagina indicata da tbl_eml_grep_feed_back.url
 * 
 * IN SOSPESO:
 *      Eliminare il pulsante annulla dalla pagina
 */
// Inizializzazioni "varie" secondo standard Moodle
global $OUTPUT, $PAGE, $SITE;
require_once '../../../config.php';
require_login();
$context = get_context_instance(CONTEXT_SYSTEM);
/////////////////////////////////////////////////require_capability('block/f2_report:gestione', $context);
$baseurl = new moodle_url('/blocks/f2_report/grep_feed_back_page.php');
$blockname = get_string('grep_pluginname', 'block_f2_report');
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/f2_report/grep_gestione_menu_report.php');
$PAGE->set_title(get_string('grep_title_feed_back_page', 'block_f2_report'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('grep_feed_back_page', 'block_f2_report'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('grep_header_feed_back_page', 'block_f2_report'));
// Definizione di costanti, variabili, function
require_once "grep_costanti.php";
require_once "grep_costanti_db.php";
require_once "grep_strutture_dati.php";
require_once "grep_function_db.php";
require_once "grep_form_definitions.php";
require_once "../online/lib_eml.php";
// definisco ed inizializzo il record per la pagina di feed-back
$rec_tbl_eml_grep_feed_back = new EML_RECtbl_eml_grep_feed_back();
$aus = EML_Connetti_db();
$id = $_REQUEST['id'];
$ret_code = EML_Get_tbl_eml_grep_feed_back($id, $rec_tbl_eml_grep_feed_back);
// Form che gestisce la pagina di feed-back
$mform = new form_feed_back_page(NULL);
if ($mform->is_cancelled()) {
    // se form cancellata torno a maschera con elenco corsi in gestione
    $nome_tabella = 'tbl_eml_grep_feed_back';
    $clausola_where = ' WHERE id = '.$id;
    EML_Del_xxx($nome_tabella, $clausola_where);
    $url = new moodle_url('grep_gestione_menu_report.php');
    $delay = 1;
    redirect($url, null, $delay);
} else if ($fromform = $mform->get_data()) {
    $id = (int) $fromform->id;
    $url_x_redirect = $fromform->url;
    $nome_tabella = 'tbl_eml_grfo_feed_back';
    $clausola_where = ' WHERE id = '.$id;
    EML_Del_xxx($nome_tabella, $clausola_where);
    $delay = 1;
    redirect($url_x_redirect, null, $delay);
}
// Visualizzazione/gestione form
$mform->display();
// Pie-pagina (secondo standard Moodle)
echo $OUTPUT->footer();