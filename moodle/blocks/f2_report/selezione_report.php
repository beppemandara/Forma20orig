<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - giugno 2015
 * 
 * Selezione (ed attivazione) di un report
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
require_once 'lib.php';
$rec_mdl_f2_csi_pent_report = new EML_RECmdl_f2_csi_pent_report();
require_login();
//$context = get_context_instance(CONTEXT_SYSTEM);
$context = context_system::instance();
//require_capability('block/f2_report:viewreport', $context);
//require_capability('block/f2_report:viewreport', $context, $USER->id);
// Intestazione "standard" Moodle
$url = '/blocks/f2_report/'.$url_pagina_di_ritorno;
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
// Definizione di costanti, variabili, function, ecc.
// Form che gestisce la selezione del report
//function moodleform($action=null, $customdata=null, $method='post', $target='', $attributes=null, $editable=true) {
$action = null;
$customdata = null;
$method = "post";
$target = "_blank";
$attributes = null; 
$editable = true;
$mform = new form_selezione_report($action, $customdata, $method, $target, $attributes, $editable);
if ($mform->is_cancelled()) {
    // se form cancellata torno alla pagina indicata dal parametro url_pagina_di_ritorno
    //$formdata = $_POST;
    //$url_pagina_di_ritorno = $formdata['url_pagina_di_ritorno'];
    //$url = new moodle_url($url_pagina_di_ritorno);
    //$delay = 1;
    //redirect($url, null, $delay);
} else if ($formdata = $mform->get_data()) {
    // leggo le "variabili" dalla maschera
    $formdata = $_POST;
    $codice_voce_menu = $formdata['codice_voce_menu'];
    $descrizione_voce_menu = $formdata['descrizione_voce_menu'];
    $url_pagina_di_ritorno = $formdata['url_pagina_di_ritorno'];
    $report_id = (int) $formdata['id_report'];
    // estraggo i dati del report
    $ret_code = EML_Get_mdl_f2_csi_pent_report($report_id, $rec_mdl_f2_csi_pent_report);
    // estraggo i parametri "variabili" del report
    // NOTA: è gestito unicamente il parametro dominio per i Referenti Formativi
    $user_id = intval($USER->id);
    $flag_anomalie = 0;
    $parametri_variabili = '';
    $ret_code = EML_Get_parametri_report($report_id, $user_id, $parametri_variabili);
    // preparo la stringa di attivazione report
    $cohort_id = get_user_cohort($user_id);
    $url_base = get_pentaho_new_url_base($cohort_id);
    $url_report = get_pentaho_url_report();
    $render_mode = '?renderMode=report';
    $output_target = '%26output-target='.$rec_mdl_f2_csi_pent_report->formato_default;
    $uid = '%26uid='.$user_id;
    $solution = '%26solution=forma20';
    $file_pentaho = '%26path=%26name='.$rec_mdl_f2_csi_pent_report->nome_file_pentaho;
    $lingua = '%26locale=it_IT';
    $reportURL = $url_base.$url_report.$render_mode.$output_target.$uid
                .$parametri_variabili.$solution.$file_pentaho.$lingua;
    // aggiorno il numero di esecuzioni e la data ultima esecuzione del report
    $ret_code = EML_aggiorna_numero_esecuzioni_report($report_id);
    // attivo il report ed esco
    // NOTA: causa problemi con la chiamata diretta di header("Location: $reportURL");
    //       che segnala header già inviati, si passa attraverso una pagina "fittizia"
    $redirect_url = 'pagina_attivazione_pentaho.php?aaa='.$reportURL;
    $delay = 1;
    redirect($redirect_url, null, $delay);
    exit();
//http://pentaho.forma20.it/pentaho/content/reporting/reportviewer/report.html?renderMode=report&output-target=pageable/pdf&uid=4062&dominio=18&anno_formativo=2013&corso=1076&solution=forma20&path=&name=dettaglio_prenotazioni_per_corso.prpt&locale=it_IT
}
// Visualizzazione/gestione form
$mform->display();
// Pie-pagina (secondo standard Moodle)
echo $OUTPUT->footer();
