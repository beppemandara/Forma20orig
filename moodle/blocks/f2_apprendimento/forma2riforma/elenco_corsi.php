<?php
/*
 * A. Albertin, G. Mandar� - CSI Piemonte - febbraio 2014
 * 
 * CREG - Upload da Riforma
 * 
 * Pagina "iniziale" con l'elenco dei corsi in gestione
 * 
 * Per ogni corso � presente una linea con i dati del corso
 * e, in funzione dello stato del corso, le icone per:
 *     modificare il corso
 *     scaricare/storicizzare
 *     cancellare il corso
 *     visualizzare il log delle operazioni
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
$PAGE->set_url('/blocks/f2_apprendimento/forma2riforma/elenco_corsi.php');
$PAGE->set_title(get_string('f2r_title_elencocorsi', 'block_f2_apprendimento'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('f2r_forma2riforma', 'block_f2_apprendimento'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('f2r_header_elencocorsi', 'block_f2_apprendimento'));
// Lettura e visualizzazione tabella con i corsi in gestione 
require_once "costanti.php";
require_once "strutture_dati.php";
require_once "function_db.php";
$corsi_in_gestione = Get_elenco_corsi_in_gestione($numero_corsi);
if ($numero_corsi == 0) {
    echo $OUTPUT->heading(get_string('f2r_nessuncorsoingestione','block_f2_apprendimento'));
    $table = NULL;
} else {
    // $returnurl = new moodle_url('/admin/user.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage, 'page'=>$page));
    $returnurlmod = new moodle_url('modifica_corso_1.php');
    $returnurlread = new moodle_url('leggi_partecipazioni_1.php');
    $returnurldel = new moodle_url('cancella_corso_1.php');
    $returnurllog = new moodle_url('log_corso_1.php');
    $returnurlstore = new moodle_url('archivia_corso_1.php');
    $stredit = get_string('f2r_alt_icona_modifica', 'block_f2_apprendimento');
    $strdelete = get_string('f2r_alt_icona_cancella', 'block_f2_apprendimento');
    $strreport = get_string('f2r_alt_icona_leggi', 'block_f2_apprendimento');
    $strlog = get_string('f2r_alt_icona_log', 'block_f2_apprendimento');
    $strstore = get_string('f2r_alt_icona_store', 'block_f2_apprendimento');
    $table = new html_table();
    $table->width = "100%";
    $table->head = array ();
    $table->align = array();
    $table->size[] = '8%';
    $table->head[] = get_string('f2r_etichetta_el_codice', 'block_f2_apprendimento');
    $table->align[] = 'left';
    $table->size[] = '35%';
    $table->head[] = get_string('f2r_etichetta_el_titolo', 'block_f2_apprendimento');
    $table->align[] = 'left';
    $table->size[] = '7%';
    $table->head[] = get_string('f2r_etichetta_el_datainizio', 'block_f2_apprendimento');
    $table->align[] = 'center';
    $table->size[] = '5%';
    $table->head[] = get_string('f2r_etichetta_el_perc_x_cfv', 'block_f2_apprendimento');
    $table->align[] = 'center';
    $table->size[] = '5%';
    $table->head[] = get_string('f2r_etichetta_el_va_default', 'block_f2_apprendimento');
    $table->align[] = 'center';
    $table->size[] = '31%';
    $table->head[] = get_string('f2r_etichetta_el_stato', 'block_f2_apprendimento');
    $table->align[] = 'left';
    $table->size[] = '9%';
    $table->head[] = get_string('f2r_etichetta_el_operazioni', 'block_f2_apprendimento');
    $table->align[] = 'left';
    for ($i = 1; $i <= $numero_corsi; $i++) {
        $buttons = array();
        $row = array ();
        $row[] = $corsi_in_gestione[$i]->shortname;
        $row[] = $corsi_in_gestione[$i]->titolo;
        $row[] = date('d-m-Y', $corsi_in_gestione[$i]->data_inizio);
        $row[] = $corsi_in_gestione[$i]->perc_x_cfv;
        $row[] = $corsi_in_gestione[$i]->va_default;
        $row[] = $corsi_in_gestione[$i]->nota;
// a.a. Inizio parte da modificare per gestione link con icone, ecc.
        switch ($corsi_in_gestione[$i]->stato) {
            case EML_RIFORMA_MAPPING_OK:
                // Corso collegato (partecipazioni non ancora lette ne archiviate).
                // Flag leciti: Update, Lettura presenze, Cancellazione, Log operazioni
                // $buttons[] = html_writer::link(new moodle_url($securewwwroot.'/user/editadvanced.php', array('id'=>$user->id, 'course'=>$site->id)), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/edit'), 'alt'=>$stredit, 'class'=>'iconsmall')), array('title'=>$stredit));
                $buttons[] = html_writer::link(new moodle_url($returnurlmod, array('id'=>$corsi_in_gestione[$i]->id_mapping)), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/edit'), 'alt'=>$stredit, 'class'=>'iconsmall')), array('title'=>$stredit));
                $buttons[] = html_writer::link(new moodle_url($returnurlread, array('id'=>$corsi_in_gestione[$i]->id_mapping)), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('i/report'), 'alt'=>$strreport, 'class'=>'iconsmall')), array('title'=>$strreport));
                $buttons[] = html_writer::link(new moodle_url($returnurldel, array('id'=>$corsi_in_gestione[$i]->id_mapping)), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/delete'), 'alt'=>$strdelete, 'class'=>'iconsmall')), array('title'=>$strdelete));
                $buttons[] = html_writer::link(new moodle_url($returnurllog, array('id'=>$corsi_in_gestione[$i]->id_mapping)), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/log'), 'alt'=>$strlog, 'class'=>'iconsmall')), array('title'=>$strlog));
                /*$aus = "&nbsp;<a href='modifica_corso_1.php?id=".$corsi_in_gestione[$i]->id_mapping."'>".EML_RIFORMA_FLAG_UPDATE."&nbsp;<a>".
                       "&nbsp;<a href='leggi_partecipazioni_1.php?id=".$corsi_in_gestione[$i]->id_mapping."'>".EML_RIFORMA_FLAG_READ."&nbsp;<a>".
                       "&nbsp;<a href='cancella_corso_1.php?id=".$corsi_in_gestione[$i]->id_mapping."'>".EML_RIFORMA_FLAG_DELETE."&nbsp;<a>".
                       "&nbsp;<a href='log_corso_1.php?id=".$corsi_in_gestione[$i]->id_mapping."'>".EML_RIFORMA_FLAG_LOG."&nbsp;<a>";
                $row[] = $aus;*/
                $row[] = implode(' ', $buttons);
                break;
            case EML_RIFORMA_LETTURA_OK:
            case EML_RIFORMA_LETTURA_WARNING:
                // Letti i dati di partecipazione (eventualmente con anomalie)
                // Flag leciti: Update, Archiviazione, Cancellazione, Log operazioni
                $buttons[] = html_writer::link(new moodle_url($returnurlmod, array('id'=>$corsi_in_gestione[$i]->id_mapping)), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/edit'), 'alt'=>$stredit, 'class'=>'iconsmall')), array('title'=>$stredit));
                $buttons[] = html_writer::link(new moodle_url($returnurlstore, array('id'=>$corsi_in_gestione[$i]->id_mapping)), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('i/backup'), 'alt'=>$strstore, 'class'=>'iconsmall')), array('title'=>$strstore));
                $buttons[] = html_writer::link(new moodle_url($returnurldel, array('id'=>$corsi_in_gestione[$i]->id_mapping)), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/delete'), 'alt'=>$strdelete, 'class'=>'iconsmall')), array('title'=>$strdelete));
                $buttons[] = html_writer::link(new moodle_url($returnurllog, array('id'=>$corsi_in_gestione[$i]->id_mapping)), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/log'), 'alt'=>$strlog, 'class'=>'iconsmall')), array('title'=>$strlog));
                /*$aus = "&nbsp;<a href='modifica_corso_1.php?id=".$corsi_in_gestione[$i]->id_mapping."'>".EML_RIFORMA_FLAG_UPDATE."&nbsp;<a>".
                       "&nbsp;<a href='archivia_corso_1.php?id=".$corsi_in_gestione[$i]->id_mapping."'>".EML_RIFORMA_FLAG_WRITE."&nbsp;<a>".
                       "&nbsp;<a href='cancella_corso_1.php?id=".$corsi_in_gestione[$i]->id_mapping."'>".EML_RIFORMA_FLAG_DELETE."&nbsp;<a>".
                       "&nbsp;<a href='log_corso_1.php?id=".$corsi_in_gestione[$i]->id_mapping."'>".EML_RIFORMA_FLAG_LOG."&nbsp;<a>";
                $row[] = $aus;*/
                $row[] = implode(' ', $buttons);
                break;
            case EML_RIFORMA_ARCHIVIAZIONE_OK:
            case EML_RIFORMA_ARCHIVIAZIONE_WARNING:
            // Archiviati i dati di partecipazione (eventualmente con anomalie)
            // Flag leciti: Log operazioni 
                //$aus = "&nbsp;<a href='log_corso_1.php?id=".$corsi_in_gestione[$i]->id_mapping."'>".EML_RIFORMA_FLAG_LOG."&nbsp;<a>";
                $buttons[] = html_writer::link(new moodle_url($returnurllog, array('id'=>$corsi_in_gestione[$i]->id_mapping)), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/log'), 'alt'=>$strlog, 'class'=>'iconsmall')), array('title'=>$strlog));
                //$row[] = $aus;
                $row[] = implode(' ', $buttons);
                break;
        }
// a.a. Fine parte da modificare per gestione link con icone, ecc.
        $table->data[] = $row;
    }
}
if (!empty($table)) {
    echo html_writer::table($table);
}
// Visualizzazione pulsante per gestione Nuovo collegamento
echo '<form id="pulsanti_elenco_corsi" action="nuovo_collegamento_1.php" method="post">';
echo '<table><tr><td>';
$aus = get_string('f2r_pulsante_nuovocollegamento', 'block_f2_apprendimento');
echo '<input type="submit" name="nuovo_collegamento" value="'.$aus.'"/>';
echo '</td></tr></table>';
echo '</form>';
// Chiusura pagina (secondo standard Moodle)
echo $OUTPUT->footer();
?>