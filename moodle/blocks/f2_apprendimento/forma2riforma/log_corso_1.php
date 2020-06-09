<?php
/*
 * A. Albertin, G. Mandarà  - CSI Piemonte - febbraio 2014
 * 
 * CREG - Upload da Riforma
 * 
 * Pagina che visualizza i messaggi di log associati ad un corso
 * 
 * Sono presentati i dati del corso ed una tabella con i messaggio
 * di log associati al corso stesso (a partire dai piÃ¹ recenti)
 */
// Inizializzazioni "varie" secondo standard Moodle
global $OUTPUT, $PAGE, $SITE, $CFG;
require_once '../../../config.php';
require_login();
$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('block/f2_apprendimento:forma2riforma', $context);
$baseurl = new moodle_url('/blocks/f2_apprendimento/forma2riforma/elenco_corsi.php');
$blockname = get_string('pluginname', 'block_f2_apprendimento');
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/f2_apprendimento/forma2riforma/log_corso_1.php');
$PAGE->set_title(get_string('f2r_title_logcorso', 'block_f2_apprendimento'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('f2r_forma2riforma', 'block_f2_apprendimento'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('f2r_header_logcorso', 'block_f2_apprendimento'));
require_once "strutture_dati.php";
require_once "function_db.php";
// Lettura e visualizzazione dei dati del corso
require "lettura_e_visualizzazione_corso.php";
// Lettura e visualizzazione della tabella con i messaggi di log
$shortname = $rec_mdl_f2_forma2riforma_mapping->shortname;
//$rec_mdl_f2_forma2riforma_log = new EML_RECmdl_f2_forma2riforma_log();
$vet_mdl_f2_forma2riforma_log = Get_log_corso($shortname, $numero_record_log);
if ($numero_record_log == 0) {
    echo $OUTPUT->heading(get_string('f2r_nologcorso','block_f2_apprendimento'));
    $table = NULL;
} else {
    // Visualizzazione del pulsante per tornare a pagina con Elenco corsi in gestione
    echo '<form id="pulsanti_log_corso" action="elenco_corsi.php" method="post">';
    echo '<table><tr><td>';
    $aus = get_string('f2r_pulsante_elencocorsi', 'block_f2_apprendimento');
    echo '<input type="submit" name="elenco_corsi" value="'.$aus.'"/>';
    echo '</td></tr></table>';
    echo '</form>';
    $table = new html_table();
    $table->width = "100%";
    $table->head = array ();
    $table->align = array();
    $table->size[] = '20%';
    $table->head[] = get_string('f2r_etichetta_dataora', 'block_f2_apprendimento');
    $table->align[] = 'left';
    $table->size[] = '80%';
    $table->head[] = get_string('f2r_etichetta_attivita', 'block_f2_apprendimento');
    for ($i = 1; $i <= $numero_record_log; $i++) {
        $row = array ();
        $row[] = gmdate("d-m-Y H:i:s",$vet_mdl_f2_forma2riforma_log[$i]->data_ora);
        $row[] = $vet_mdl_f2_forma2riforma_log[$i]->descrizione;
        $table->data[] = $row;
    }
}
if (!empty($table)) {
    echo html_writer::table($table);
}
// Visualizzazione del pulsante per tornare a pagina con Elenco corsi in gestione
echo '<form id="pulsanti_log_corso" action="elenco_corsi.php" method="post">';
echo '<table><tr><td>';
$aus = get_string('f2r_pulsante_elencocorsi', 'block_f2_apprendimento');
echo '<input type="submit" name="elenco_corsi" value="'.$aus.'"/>';
echo '</td></tr></table>';
echo '</form>';
// Chiusura pagina (secondo standard Moodle)
echo $OUTPUT->footer();
?>