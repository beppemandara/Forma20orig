<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - giugno 2015
 * 
 * Attivazione della pagina che segnala "Nessun report disponibile"
 * 
 */
// Inizializzazioni "varie" secondo standard Moodle
global $CFG, $OUTPUT, $PAGE, $SITE, $USER;
// leggo i parametri ricevuti in ingresso
if (isset($_REQUEST['codice_voce_menu'])) {
    $codice_voce_menu = $_REQUEST['codice_voce_menu'];
}
if (isset($_REQUEST['descrizione_voce_menu'])) {
    $descrizione_voce_menu = $_REQUEST['descrizione_voce_menu'];
}
if (isset($_REQUEST['url_pagina_di_ritorno'])) {
    $url_pagina_di_ritorno = $_REQUEST['url_pagina_di_ritorno'];
}
//verifico che l'utente sia loggato ed abbia la capability di visualizzazione report
require_once '../../config.php';
require_once 'report_form_definitions.php'; // AAAAAAAAAAAAAAAAAAAAAAA da cambiare con path più corretto quando si dovrà attivare anche da report online
require_once 'strutture_dati.php';
//$rec_mdl_f2_csi_pent_report = new EML_RECmdl_f2_csi_pent_report();
require_login();
$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('block/f2_report:viewreport', $context);
// Intestazione "standard" Moodle
$url = '/blocks/f2_report/'.$url_pagina_di_ritorno;
//$url = '/blocks/f2_report/';
$baseurl = new moodle_url($url);
$blockname = get_string('pluginname', 'block_f2_report');
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/f2_report/selezione_report.php');
$PAGE->set_title($descrizione_voce_menu);
$PAGE->settingsnav;
$PAGE->navbar->add($descrizione_voce_menu, $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('report_selezione_report_da_attivare', 'block_f2_report'));
// Form di segnalazione report non disponibili
$mform = new form_nessun_report_disponibile();
// Visualizzazione/gestione form
$mform->display();
// Pie-pagina (secondo standard Moodle)
echo $OUTPUT->footer();