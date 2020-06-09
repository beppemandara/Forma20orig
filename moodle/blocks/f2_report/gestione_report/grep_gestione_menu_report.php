<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - giugno 2015
 * 
 * Gestione Report
 * 
 * Pagina "iniziale" con l'elenco delle voci del menù report
 * 
 * Per ogni voce di menù report una linea con:
 *     codice 
 *     descrizione
 *     flag attiva (Si/No)
 *     numero (totale) report
 *     numero report attivi
 *     le icone per:
 *         Modificare la voce di menù (attiva/disattiva)
 *         Andare alla pagina con i report associati alla voce
 *         Cancellare (dalla gestione) la voce di menù
 * 
 * In fondo alla pagina ci sono i pulsanti per:
 *     Inserire una nuova voce di menù
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
$PAGE->set_url('/blocks/f2_report/grep_gestione_menu_report.php');
$PAGE->set_title(get_string('grep_title_gestione_menu_report', 'block_f2_report'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('grep_gestione_menu_report', 'block_f2_report'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('grep_header_gestione_menu_report', 'block_f2_report'));
// Definizione di costanti, variabili, function
require_once "grep_costanti.php";
require_once "grep_strutture_dati.php";
require_once "grep_function_db.php";
// Lettura e visualizzazione tabella con i corsi in gestione 
$numero_voci_menu = 0;
$voci_menu_report = EML_Get_elenco_voci_menu_report($numero_voci_menu);
if ($numero_voci_menu == 0) {
    echo $OUTPUT->heading(get_string('grep_nessuna_voce_menu_report','block_f2_report'));
    $table = NULL;
} else {
    $url_modifica = new moodle_url('grep_modifica_voce_menu_report.php');
    $url_gestione_report= new moodle_url('grep_gestione_report.php');
    $url_cancella = new moodle_url('grep_cancella_voce_menu_report.php');
    $str_alt_modifica = get_string('grep_alt_icona_modifica_voce_menu_report', 'block_f2_report');
    $str_alt_gestione_report = get_string('grep_alt_icona_gestione_report', 'block_f2_report');
    $str_alt_cancella = get_string('grep_alt_icona_cancella_voce_menu_report', 'block_f2_report');
    $table = new html_table();
    $table->width = "100%";
    $table->head = array ();
    $table->align = array();
    $table->size[] = '8%';
    $table->head[] = get_string('grep_etichetta_codice_voce_menu', 'block_f2_report');
    $table->align[] = 'left';
    $table->size[] = '46%';
    $table->head[] = get_string('grep_etichetta_descrizione_voce_menu', 'block_f2_report');
    $table->align[] = 'left';
    $table->size[] = '8%';
    $table->head[] = get_string('grep_etichetta_flag_attiva', 'block_f2_report');
    $table->align[] = 'center';
    $table->size[] = '16%';
    $table->head[] = get_string('grep_etichetta_numero_totale_report', 'block_f2_report');
    $table->align[] = 'center';
    $table->size[] = '16%';
    $table->head[] = get_string('grep_etichetta_numero_report_attivi', 'block_f2_report');
    $table->align[] = 'center';
    $table->size[] = '8%';
    $table->head[] = get_string('grep_etichetta_operazioni', 'block_f2_report');
    $table->align[] = 'center';
    for ($i = 1; $i <= $numero_voci_menu; $i++) {
        $buttons = array();
        $row = array ();
        $row[] = $voci_menu_report[$i]->cod_voce;
        $row[] = $voci_menu_report[$i]->descr_voce;
        $row[] = $voci_menu_report[$i]->flag_attiva;
        $row[] = $voci_menu_report[$i]->numero_totale_report;
        $row[] = $voci_menu_report[$i]->numero_report_attivi;
// a.a. Inizio gestione link con icone, ecc.
        $buttons[] = html_writer::link(new moodle_url($url_modifica, 
                array('id_voce_menu'=>$voci_menu_report[$i]->id_voce)), 
                html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/edit'), 
                    'alt'=>$str_alt_modifica, 'class'=>'iconsmall')), array('title'=>$str_alt_modifica));
        $buttons[] = html_writer::link(new moodle_url($url_gestione_report, 
                array('id_voce_menu'=>$voci_menu_report[$i]->id_voce)), 
                html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('i/report'), 
                    'alt'=>$str_alt_gestione_report, 'class'=>'iconsmall')), array('title'=>$str_alt_gestione_report));
        if($voci_menu_report[$i]->numero_totale_report == 0) {
            $buttons[] = html_writer::link(new moodle_url($url_cancella, 
                    array('id_voce_menu'=>$voci_menu_report[$i]->id_voce)), 
                    html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/delete'), 
                        'alt'=>$str_alt_cancella, 'class'=>'iconsmall')), array('title'=>$str_alt_cancella));
        }
        $row[] = implode(' ', $buttons);
// a.a. Fine gestione link con icone, ecc.
        $table->data[] = $row;
    }
}
if (!empty($table)) {
    echo html_writer::table($table);
}
// Aggiungo a fondo pagina i pulsanti per 
//      Inserire una nuova voce di menù
//      Stampare
$pulsante_nuova_voce_menu = get_string('grep_pulsante_nuova_voce_menu', 'block_f2_report');
$pulsante_attiva_report = get_string('grep_pulsante_attiva_report', 'block_f2_report');
$url_nuova_voce_menu = 'grep_nuova_voce_menu_report.php';
$url_attiva_report = '../attiva_report_grep.php';
echo '<input type="button" value="'.$pulsante_attiva_report.'" onclick="parent.location=\''.$url_attiva_report.'\'">';
echo '&nbsp&nbsp&nbsp&nbsp';
echo '<input type="button" value="'.$pulsante_nuova_voce_menu.'" onclick="parent.location=\''.$url_nuova_voce_menu.'\'">';
// Chiusura pagina (secondo standard Moodle)
echo $OUTPUT->footer();