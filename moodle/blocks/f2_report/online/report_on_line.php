<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - giugno 2015
 * 
 * GRFO - Gestione Report Formazione On-line
 * 
 * Pagina "iniziale" con l'elenco dei corsi in gestione
 * 
 * Per ogni corso è presente una linea con:
 *     codice corso
 *     titolo corso
 *     numero risorse monitorate
 *     numero edizioni monitorate
 *     le icone per:
 *         Cancellare il corso dal monitoraggio
 *         Modificare il corso
 *         Attivare il ricalcolo dei dati di monitoraggio per tutte le edizioni del corso
 * 
 * In fondo/testa alla pagina ci sono i pulsanti per:
 *     Inserire un nuovo corso in monitoraggio
 *     Attivare i report sulla fruizione formazione on-line
 *     Modificare i parametri di controllo
 */
// Inizializzazioni "varie" secondo standard Moodle
global $OUTPUT, $PAGE, $SITE;
require_once '../../../config.php';
require_login();
//$context = get_context_instance(CONTEXT_SYSTEM);
$context = context_system::instance();
require_capability('block/f2_report:online', $context);
$baseurl = new moodle_url('/blocks/f2_report/online/report_on_line.php');
$blockname = get_string('pluginname', 'block_f2_report');
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/f2_report/online/report_on_line.php');
$PAGE->set_title(get_string('grfo_title_report_on_line', 'block_f2_report'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('grfo_report_on_line', 'block_f2_report'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('grfo_header_report_on_line', 'block_f2_report'));
// Lettura e visualizzazione tabella con i corsi in gestione 
require_once "costanti.php";
require_once "costanti_db.php";
require_once "strutture_dati.php";
require_once "function_db.php";
require_once "lib_eml.php";
//global $mysqli;
// cancellazione dei "vecchi" messaggi di log
$ret_code = EML_Connetti_db();
$ret_code = EML_pulisci_log();
$numero_corsi = 0;
$corsi_in_gestione = EML_Get_elenco_corsi_in_gestione($numero_corsi);
if ($numero_corsi == 0) {
    echo $OUTPUT->heading(get_string('grfo_nessun_corso_in_gestione','block_f2_report'));
    $table = NULL;
} else {
    $url_modifica = new moodle_url('modifica_corso.php');
    $url_ricalcola = new moodle_url('ricalcola_corso.php');
    $url_cancella = new moodle_url('cancella_corso.php');
    $str_alt_modifica = get_string('grfo_alt_icona_modifica_corso', 'block_f2_report');
    $str_alt_ricalcola = get_string('grfo_alt_icona_ricalcola_corso', 'block_f2_report');
    $str_alt_cancella = get_string('grfo_alt_icona_cancella_corso', 'block_f2_report');
    $table = new html_table();
    $table->width = "100%";
    $table->head = array ();
    $table->align = array();
    $table->size[] = '8%';
    $table->head[] = get_string('grfo_etichetta_cod_corso', 'block_f2_report');
    $table->align[] = 'left';
    $table->size[] = '60%';
    $table->head[] = get_string('grfo_etichetta_titolo_corso', 'block_f2_report');
    $table->align[] = 'left';
    $table->size[] = '8%';
    $table->head[] = get_string('grfo_etichetta_risorse_monitorate', 'block_f2_report');
    $table->align[] = 'center';
    $table->size[] = '8%';
    $table->head[] = get_string('grfo_etichetta_edizioni_monitorate', 'block_f2_report');
    $table->align[] = 'center';
    $table->size[] = '8%';
    $table->head[] = get_string('grfo_etichetta_tracciato_completamento', 'block_f2_report');
    $table->align[] = 'center';
    $table->size[] = '8%';
    $table->head[] = get_string('grfo_etichetta_operazioni', 'block_f2_report');
    $table->align[] = 'center';
    for ($i = 1; $i <= $numero_corsi; $i++) {
        $buttons = array();
        $row = array ();
        $row[] = $corsi_in_gestione[$i]->cod_corso;
        $row[] = $corsi_in_gestione[$i]->titolo_corso;
        $row[] = $corsi_in_gestione[$i]->moduli_monitorati;
        $row[] = $corsi_in_gestione[$i]->edizioni_monitorate;
        $row[] = $corsi_in_gestione[$i]->tracciato_completamento;
// a.a. Inizio gestione link con icone, ecc.
        //$buttons[] = html_writer::link(new moodle_url($url_report, 
        //        array('id_corso'=>$corsi_in_gestione[$i]->id_corso)), 
        //        html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('i/report'), 
        //            'alt'=>$str_alt_report, 'class'=>'iconsmall')), array('title'=>$str_alt_report));
        $buttons[] = html_writer::link(new moodle_url($url_modifica, 
                array('id_corso'=>$corsi_in_gestione[$i]->id_corso)), 
                html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/edit'), 
                    'alt'=>$str_alt_modifica, 'class'=>'iconsmall')), array('title'=>$str_alt_modifica));
        $buttons[] = html_writer::link(new moodle_url($url_ricalcola, 
                array('id_corso'=>$corsi_in_gestione[$i]->id_corso)), 
                html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('i/calc'), 
                    'alt'=>$str_alt_ricalcola, 'class'=>'iconsmall')), array('title'=>$str_alt_ricalcola));
        $buttons[] = html_writer::link(new moodle_url($url_cancella, 
                array('id_corso'=>$corsi_in_gestione[$i]->id_corso)), 
                html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/delete'), 
                    'alt'=>$str_alt_cancella, 'class'=>'iconsmall')), array('title'=>$str_alt_cancella));
        $row[] = implode(' ', $buttons);
// a.a. Fine gestione link con icone, ecc.
        $table->data[] = $row;
    }
}
if (!empty($table)) {
    echo html_writer::table($table);
}
// Aggiungo a fondo pagina i pulsanti per 
//      Attiva report
//      Nuovo corso in monitoraggio
//      Modifica parametri
$pulsante_attiva_report = get_string('grfo_pulsante_attiva_report', 'block_f2_report');
$pulsante_nuovo_corso = get_string('grfo_pulsante_nuovo_corso', 'block_f2_report');
$pulsante_modifica_parametri = get_string('grfo_pulsante_modifica_parametri', 'block_f2_report');
$url_attiva_report = '../attiva_report_on_line.php';
//$url_attiva_report = 'attiva_report.php';
$url_nuovo_corso = 'nuovo_corso.php';
$url_modifica_parametri = 'modifica_parametri.php';
echo '<input type="button" value="'.$pulsante_attiva_report.'" onclick="parent.location=\''.$url_attiva_report.'\'">';
echo '&nbsp&nbsp&nbsp&nbsp';
echo '<input type="button" value="'.$pulsante_nuovo_corso.'" onclick="parent.location=\''.$url_nuovo_corso.'\'">';
echo '&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp';
echo '<input type="button" value="'.$pulsante_modifica_parametri.'" onclick="parent.location=\''.$url_modifica_parametri.'\'">';

// PROVVISORIO PROVVISORIO PROVVISORIO PROVVISORIO PROVVISORIO PROVVISORIO PROVVISORIO
/*
$url_provvisoria = '../gestione_report/grep_gestione_menu_report.php'; 
echo '&nbsp&nbsp&nbsp&nbsp';
echo '<input type="button" value="provvisorio (gestione report)" onclick="parent.location=\''.$url_provvisoria.'\'">';
*/
//  PROVVISORIO PROVVISORIO PROVVISORIO PROVVISORIO PROVVISORIO PROVVISORIO PROVVISORIO

// Chiusura pagina (secondo standard Moodle)
echo $OUTPUT->footer();
