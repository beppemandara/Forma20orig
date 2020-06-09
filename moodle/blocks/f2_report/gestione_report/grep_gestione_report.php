<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - luglio 2015
 * 
 * Gestione Report
 * 
 * Pagina con l'elenco dei report associati ad una Voce menu report
 * 
 * Viene visualizzata una linea con codice e descrizione della voce di menù report
 * una tabella con i report associati alla voce di menù
 * Per ogni report si visualizza una linea con:
 *     posizione in elenco
 *     flag attiva (Si/No)
 *     nome report
 *     nome file Pentaho
 *     formato default
 *     numero parametri del report
 *     numero ruoli abilitati
 *     le icone per:
 *         Modificare il report
 *         Mmodificare i parametri ed i ruoli del report
 *         Cancellare il report
 * 
 * In fondo alla pagina ci sono i pulsanti per:
 *     Inserire un nuovo report
 *     Stampare ???
 */
// Inizializzazioni "varie" secondo standard Moodle
global $OUTPUT, $PAGE, $SITE;
require_once '../../../config.php';
require_login();
$context = get_context_instance(CONTEXT_SYSTEM);
/////////////////////////////////////////////////require_capability('block/f2_report:gestione', $context);
$baseurl = new moodle_url('/blocks/f2_report/grep_gestione_menu_report.php');
$blockname = get_string('grep_pluginname', 'block_f2_report');
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/f2_report/grep_gestione_report.php');
$PAGE->set_title(get_string('grep_title_gestione_report', 'block_f2_report'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('grep_gestione_menu_report', 'block_f2_report'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
echo $OUTPUT->header();
//echo $OUTPUT->heading(get_string('grep_header_gestione_report', 'block_f2_report'));
// Definizione di costanti, variabili, function
require_once "grep_costanti.php";
require_once "grep_costanti_db.php";
require_once "grep_strutture_dati.php";
require_once "grep_function_db.php";
require_once "../online/lib_eml.php";
// estrazione parametri della pagina
$id_voce_menu = $_REQUEST['id_voce_menu'];
// connessione al data-base
$aus = EML_Connetti_db();
// lettura voce menù e visualizzazione codice - descrizione
$rec_mdl_f2_csi_pent_menu_report = new EML_RECmdl_f2_csi_pent_menu_report();
$ret_code = EML_Get_mdl_f2_csi_pent_menu_report($id_voce_menu, $rec_mdl_f2_csi_pent_menu_report);
$aus = $rec_mdl_f2_csi_pent_menu_report->codice.' - '.$rec_mdl_f2_csi_pent_menu_report->descrizione;
echo $OUTPUT->heading($aus);
// Lettura e visualizzazione tabella con i corsi in gestione 
$numero_report = 0;
$elenco_report = EML_Get_elenco_report($id_voce_menu, $numero_report);
if ($numero_report == 0) {
    echo $OUTPUT->heading(get_string('grep_nessun_report','block_f2_report'));
    $table = NULL;
} else {
    $url_modifica_report = new moodle_url('grep_modifica_report.php');
    $url_modifica_parametri_report = new moodle_url('grep_modifica_parametri_report.php');
    $url_cancella_report = new moodle_url('grep_cancella_report.php');
    $str_alt_modifica_report = get_string('grep_alt_icona_modifica_report', 'block_f2_report');
    $str_alt_modifica_parametri_report = get_string('grep_alt_icona_modifica_parametri_report', 'block_f2_report');
    $str_alt_cancella_report = get_string('grep_alt_icona_cancella_report', 'block_f2_report');
    $table = new html_table();
    $table->width = "100%";
    $table->head = array ();
    $table->align = array();
    
    $table->size[] = '4%';
    $table->head[] = get_string('grep_etichetta_posizione_report', 'block_f2_report');
    $table->align[] = 'center';
    
    $table->size[] = '4%';
    $table->head[] = get_string('grep_etichetta_flag_report_attivo', 'block_f2_report');
    $table->align[] = 'center';
    
    $table->size[] = '34%';
    $table->head[] = get_string('grep_etichetta_nome_report', 'block_f2_report');
    $table->align[] = 'left';
    
    $table->size[] = '34%';
    $table->head[] = get_string('grep_etichetta_file_Pentaho', 'block_f2_report');
    $table->align[] = 'left';
    
    $table->size[] = '8%';
    $table->head[] = get_string('grep_etichetta_formato_default', 'block_f2_report');
    $table->align[] = 'center';
    
    $table->size[] = '4%';
    $table->head[] = get_string('grep_etichetta_parametri', 'block_f2_report');
    $table->align[] = 'center';

    $table->size[] = '4%';
    $table->head[] = get_string('grep_etichetta_ruoli_abilitati', 'block_f2_report');
    $table->align[] = 'center';
    
    $table->size[] = '8%';
    $table->head[] = get_string('grep_etichetta_operazioni', 'block_f2_report');
    $table->align[] = 'center';
    
    for ($i = 1; $i <= $numero_report; $i++) {
        $buttons = array();
        $row = array ();
        $row[] = $elenco_report[$i]->posizione_in_elenco_report;
        if ($elenco_report[$i]->flag_attivo == 1) {
            $row[] = EML_GREP_SI;
        } else {
            $row[] =EML_GREP_NO;
        }
        $row[] = $elenco_report[$i]->nome_report;
        $row[] = $elenco_report[$i]->nome_file_pentaho;
        $row[] = $elenco_report[$i]->formato_default;
        $row[] = $elenco_report[$i]->numero_parametri;
        $row[] = $elenco_report[$i]->numero_ruoli;
// a.a. Inizio gestione link con icone, ecc.
        $buttons[] = html_writer::link(new moodle_url($url_modifica_report, 
                array('id_report'=>$elenco_report[$i]->id_report)), 
                html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('i/edit'), 
                    'alt'=>$str_alt_modifica_report, 'class'=>'iconsmall')), array('title'=>$str_alt_modifica_report));
        $buttons[] = html_writer::link(new moodle_url($url_modifica_parametri_report, 
                array('id_report'=>$elenco_report[$i]->id_report)), 
                html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('i/user'), 
                    'alt'=>$str_alt_modifica_parametri_report, 'class'=>'iconsmall')), array('title'=>$str_alt_modifica_parametri_report));
        $buttons[] = html_writer::link(new moodle_url($url_cancella_report, 
                array('id_report'=>$elenco_report[$i]->id_report)), 
                html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/delete'), 
                    'alt'=>$str_alt_cancella_report, 'class'=>'iconsmall')), array('title'=>$str_alt_cancella_report));
        $row[] = implode(' ', $buttons);
// a.a. Fine gestione link con icone, ecc.
        $table->data[] = $row;
    }
}
if (!empty($table)) {
    echo html_writer::table($table);
}
// Aggiungo a fondo pagina i pulsanti per 
//      Tornare a Elenco Voci Menu Report
//      Inserire un nuovo report
$pulsante_gestione_menu_report = get_string('grep_pulsante_gestione_menu_report', 'block_f2_report');
$pulsante_nuovo_report = get_string('grep_pulsante_nuovo_report', 'block_f2_report');
$url_gestione_menu_report = 'grep_gestione_menu_report.php';
$url_nuovo_report = 'grep_nuovo_report.php?id_voce_menu='.$id_voce_menu;
echo '<input type="button" value="'.$pulsante_gestione_menu_report.'" onclick="parent.location=\''.$url_gestione_menu_report.'\'">';
echo '&nbsp&nbsp&nbsp&nbsp';
echo '<input type="button" value="'.$pulsante_nuovo_report.'" onclick="parent.location=\''.$url_nuovo_report.'\'">';
// Chiusura pagina (secondo standard Moodle)
echo $OUTPUT->footer();