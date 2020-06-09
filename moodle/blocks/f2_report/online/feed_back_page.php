<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - dicembre 2014
 * 
 * GRFO - Gestione Report Formazione On-line
 * 
 * Pagina di feed-back (messaggio su come è andata l'operazione appena effettuata)
 * 
 * Legge dalla tabella tbl_eml_grfo_feed_back il record con id = parametro ricevuto
 * Cancella il record dalla tabella tbl_eml_grfo_feed_back
 * Visualizza i dati letti (corso, operazione, stato, nota)
 * Va alla pagina indicata da tbl_eml_grfo_feed_back.url (eventualmente passando il parametro id_corso)
 * 
 * IN SOSPESO:
 *      Eliminare il pulsante annulla dalla pagina
 */
// Inizializzazioni "varie" secondo standard Moodle
global $OUTPUT, $PAGE, $SITE;
//global $CFG, $USER;
require_once '../../../config.php';
require_login();
$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('block/f2_report:online', $context);
$baseurl = new moodle_url('/blocks/f2_report/online/feed_back_page.php');
$blockname = get_string('pluginname', 'block_f2_report');
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/f2_report/online/feed_back_page.php');
$PAGE->set_title(get_string('grfo_title_feed_back_page', 'block_f2_report'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('grfo_feed_back_page', 'block_f2_report'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('grfo_header_feed_back_page', 'block_f2_report'));
require_once "costanti.php";
require_once "costanti_db.php";
require_once "strutture_dati.php";
require_once "function_db.php";
require_once "lib_eml.php";
require_once "form_definitions.php";
//global $mysqli;
$aus = EML_Connetti_db();
$rec_tbl_eml_grfo_feed_back = new EML_RECtbl_eml_grfo_feed_back();
$id = $_REQUEST['id'];
$ret_code = EML_GET_tbl_eml_grfo_feed_back($id, $rec_tbl_eml_grfo_feed_back);
// Form che gestisce la pagina di feed-back
$mform = new form_feed_back_page(NULL);
if ($mform->is_cancelled()) {
    // se form cancellata torno a maschera con elenco corsi in gestione
    $nome_tabella = 'tbl_eml_grfo_feed_back';
    $clausola_where = ' WHERE id = '.$id;
    EML_Del_xxx($nome_tabella, $clausola_where);
    $url_elenco_corsi = new moodle_url('report_on_line.php');
    $delay = 1;
    redirect($url_elenco_corsi, null, $delay);
} else if ($fromform = $mform->get_data()) {
    //$fromform = $mform->get_data();
    $id = (int) $fromform->id;
    $id_corso = (int) $fromform->id_corso;
    $url = $fromform->url;
    $flag_parametro_id_corso = $fromform->flag_parametro_id_corso;
    $nome_tabella = 'tbl_eml_grfo_feed_back';
    $clausola_where = ' WHERE id = '.$id;
    EML_Del_xxx($nome_tabella, $clausola_where);
    $aus = get_string('grfo_feed_back_SI_parametro_id_corso', 'block_f2_report');
    if($flag_parametro_id_corso == $aus) {
        $url_x_redirect = new moodle_url($url, array('id_corso'=>$id_corso));
    } else {
        $url_x_redirect = new moodle_url($url);
    }
    $delay = 1;
    redirect($url_x_redirect, null, $delay);
}
// Visualizzazione/gestione form
$mform->display();
// Pie-pagina (secondo standard Moodle)
echo $OUTPUT->footer();