<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - luglio 2015
 * 
 * GREP - Gestione Report
 * 
 * Pagina che permette di modificare i parametri ed i ruoli associati ad un report
 * 
 * Principali passi:
 * 
 * Inizializzazioni "varie" secondo standard Moodle (pagina, header, ecc.)
 * Definizione di costanti, variabili, function
 * Form che gestisce la modifica dei parametri e ruoli dl un report
 *     Se form cancellata 
 *         torno a pagina con elenco report associati alla voce di menù
 *     Altrimenti
 *         attivo le function di modifica delle tabelle
 *             mdl_f2_csi_pent_param_map (parametri del report)
 *             mdl_f2_csi_pent_role_map (ruoli associati al report)
 *         redirect alla pagina di feed back
 *     fine se
 * fine form che gestisce la modifica dei parametri e ruoli dl un report
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
$PAGE->set_title(get_string('grep_title_modifica_report', 'block_f2_report'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('grep_gestione_menu_report', 'block_f2_report'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('grep_header_modifica_report', 'block_f2_report'));
// Definizione di costanti, variabili, function
require_once "grep_costanti.php";
require_once "grep_costanti_db.php";
require_once "grep_strutture_dati.php";
require_once "grep_function_db.php";
require_once "grep_form_definitions.php";
require_once "../online/lib_eml.php";
// connessione al data-base
$aus = EML_Connetti_db();
//Form che gestisce la modifica di parametri e ruoli associati ad un Report
$mform = new form_modifica_parametri_report();
if ($mform->is_cancelled()) {
    // se form cancellata torno a maschera con elenco report associati alla voce
    $id_menu_report = $_REQUEST['id_menu_report'];
    $url_gestione_menu_report = new moodle_url('grep_gestione_report.php?id_voce_menu='.$id_menu_report);
    $delay = 1;
    redirect($url_gestione_menu_report, null, $delay);
} else if ($fromform = $mform->get_data()) {
    // carico in variabili locali i dati letti
    // ATTENZIONE -- Per motivi non ancora chiari (paturnie di Moodle) occorre:
    //  riassegnare i parametri ricevuti dalla form con una $_POST trasformado $fromform in un array
    //      (altrimenti le variabili cecked non si vedono)
    //  a questo punto l'accesso ai campi della maschera non si può più fare con delle
    //      $fromform->nome_del_campo   (che prevede che $fromform sia un object)
    //  ma bisogna usare (almeno per gli interi) 
    //      $fromform['nome_del_campo']
    $fromform = $_POST;
    $id_report = (int) $fromform['id_report'];
    $numero_parametri = 0;
    $elenco_parametri = EML_Get_elenco_parametri_report($id_report, $numero_parametri);
    $numero_ruoli = 0;
    $elenco_ruoli = EML_Get_elenco_ruoli_report($id_report, $numero_ruoli);      
    $rec_mdl_f2_csi_pent_report = new EML_RECmdl_f2_csi_pent_report();
    $rec_mdl_f2_csi_pent_report->id_menu_report = (int) $fromform['id_menu_report'];
    $rec_mdl_f2_csi_pent_report->nome_report = $fromform['nome_report'];
    $rec_mdl_f2_csi_pent_report->nome_file_pentaho = $fromform['nome_file_pentaho'];
    $rec_mdl_f2_csi_pent_report->posizione_in_elenco_report = (int) $fromform['posizione_in_elenco_report'];
    $rec_mdl_f2_csi_pent_report->attivo = (int) $fromform['flag_attivo'];
    $rec_mdl_f2_csi_pent_report->formato_default = $fromform['formato_default'];
    // gestione dei parametri
    // cancello tutti gli eventuali parametri del report
    $nome_tabella = 'mdl_f2_csi_pent_param_map';
    $clausola_where = ' where id_report = '.$id_report;
    EML_Del_xxx($nome_tabella, $clausola_where);
    for ($i_loop=1 ; $i_loop<=$numero_parametri ; $i_loop++) {
        $nome_parametro = 'parametro_'.$i_loop;
        $esiste_parametro = array_key_exists($nome_parametro, $fromform);
        if ($esiste_parametro) {
            $id_parametro = $elenco_parametri[$i_loop]->id_parametro;
            $query = " INSERT INTO mdl_f2_csi_pent_param_map (id_report, id_param) VALUES ("
                    .$id_report.", ".$id_parametro.")";
            $mysqli->query($query);
        }
    } // loop si parametri del report
    // gestione dei ruoli
    // cancello tutti gli eventuali ruoli associati al report
    $nome_tabella = 'mdl_f2_csi_pent_role_map';
    $clausola_where = ' where id_report = '.$id_report;
    EML_Del_xxx($nome_tabella, $clausola_where);
    for ($i_loop=1 ; $i_loop<=$numero_ruoli ; $i_loop++) {
        $nome_ruolo = 'ruolo_'.$i_loop;
        $esiste_ruolo = array_key_exists($nome_ruolo, $fromform);
        if ($esiste_ruolo) {
            $id_ruolo = $elenco_ruoli[$i_loop]->id_ruolo;
            $query = " INSERT INTO mdl_f2_csi_pent_role_map (id_report, id_role) VALUES ("
                    .$id_report.", ".$id_ruolo.")";
            $mysqli->query($query);
        }
    } // loop si parametri del report
    $rec_tbl_eml_grep_feed_back = new EML_RECtbl_eml_grep_feed_back();
    $rec_tbl_eml_grep_feed_back->operazione = 'Modifica Parametri e Ruoli associati ad un report';
    $rec_tbl_eml_grep_feed_back->stato = 'Operazione terminata';
    $rec_tbl_eml_grep_feed_back->url = 'grep_gestione_report.php?id_voce_menu='.$rec_mdl_f2_csi_pent_report->id_menu_report;
    $rec_tbl_eml_grep_feed_back->nota_1 = 'Report: '.$rec_mdl_f2_csi_pent_report->nome_report;
    $rec_tbl_eml_grep_feed_back->nota_2 = ' ';
    $rec_tbl_eml_grep_feed_back->nota_3 = ' ';
    $rec_tbl_eml_grep_feed_back->nota_4 = ' ';
    $id_x_pagina_feed_back = EML_Ins_tbl_eml_grep_feed_back($rec_tbl_eml_grep_feed_back);
    $url_pagina_feed_back = new moodle_url('grep_feed_back_page.php', array('id'=>$id_x_pagina_feed_back));
    $delay = 1;
    redirect($url_pagina_feed_back, null, $delay);
} 
// Visualizzazione/gestione form
$mform->display();
// Pie-pagina (secondo standard Moodle)
echo $OUTPUT->footer();