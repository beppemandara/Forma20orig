<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - dicembre 2014
 * 
 * GRFO - Gestione Report Formazione On-line
 * 
 * Pagina che permette di modificare le risorse e le edizioni da monitorare (per un corso)
 * 
 * Principali passi:
 * 
 * Inizializzazioni "varie" secondo standard Moodle (pagina, header, ecc.)
 * Definizione di costanti, variabili, function
 * Form che gestisce la modifica del corso in monitoraggio
 *     Se form cancellata 
 *         torno a maschera con elenco corsi in gestione
 *     Altrimenti
 *         attivo la function di modifica corso in tabelle 
 *             tbl_eml_pent_moduli_corsi_on_line (default nessura risosrsa monitorata)
 *             tbl_eml_pent_edizioni_corsi_on_line (default nessuna edizione monitorata)
 *         redirect alla pagina modifica_corso_monitorate
 *     fine se
 * fine form che gestisce la modifica del corso
 * Visualizzazione/gestione form
 * Pie-pagina (secondo standard Moodle)
 * 
 * IN SOSPESO
 *  gestione moduli/risosrse aggiunte dopo l'inserimento del corso in monitoraggio (vedi form di modifica)
 */
// Inizializzazioni "varie" secondo standard Moodle
global $OUTPUT, $PAGE, $SITE, $USER;
//global $CFG, $DB;
require_once '../../../config.php';
require_login();
$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('block/f2_report:online', $context);
$baseurl = new moodle_url('/blocks/f2_report/online/modifica_corso_da_monitorare.php');
$blockname = get_string('pluginname', 'block_f2_report');
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/f2_report/online/modifica_corso_da_monitorare.php');
$PAGE->set_title(get_string('grfo_title_modifica_corso', 'block_f2_report'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('grfo_modifica_corso', 'block_f2_report'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('grfo_header_modifica_corso', 'block_f2_report'));
// Definizione di costanti, variabili, function
require_once "costanti.php";
require_once "costanti_db.php";
require_once "strutture_dati.php";
require_once "lib_eml.php";
require_once "function_db.php";
require_once "form_definitions.php";
$rec_tbl_eml_grfo_log = new EML_RECtbl_eml_grfo_log();
$rec_tbl_eml_grfo_feed_back = new EML_RECtbl_eml_grfo_feed_back();
global $mysqli;
$aus = EML_Connetti_db();
$id_corso = $_REQUEST['id_corso'];
// Form che gestisce la modifica corso monitorato
$mform = new form_modifica_corso();
if ($mform->is_cancelled()) {
    // se form cancellata torno a maschera con elenco corsi in gestione
    $url_elenco_corsi = new moodle_url('report_on_line.php');
    $delay = 1;
    redirect($url_elenco_corsi, null, $delay);
} else if ($formdata = $mform->get_data()) {
    $formdata = $_POST;
    $id_corso = (int) $formdata['id_corso'];
    $cod_corso = $formdata['cod_corso'];
    $titolo_corso = $formdata['titolo_corso'];
    $numero_moduli = (int) $formdata['numero_moduli'];
    $numero_edizioni = (int) $formdata['numero_edizioni'];
    // gestione dei moduli/risorse
    $contatore_posizione_in_report = 1;
    for ($i_loop=1 ; $i_loop<=$numero_moduli ; $i_loop++) {
        $nome_risorsa = 'risorsa_'.$i_loop;
        $esiste_risorsa = array_key_exists($nome_risorsa, $formdata);
        if ($esiste_risorsa) {
            if($contatore_posizione_in_report <= EML_PENT_MAX_RISORSE_IN_REPORT) {
                $posizione_in_report = $contatore_posizione_in_report;
                $contatore_posizione_in_report++;
            } else {
                $posizione_in_report = EML_PENT_MODULO_NON_MONITORATO;
            }
        } else {
            $posizione_in_report = EML_PENT_MODULO_NON_MONITORATO;
        }
        $nome_progressivo = 'progressivo_risorsa_'.$i_loop;
        $progressivo = $formdata[$nome_progressivo];
        $query = " UPDATE tbl_eml_pent_moduli_corsi_on_line "
                ." SET posizione_in_report = ".$posizione_in_report
                ." WHERE id_corso = ".$id_corso." AND progressivo = ".$progressivo;
        $mysqli->query($query);
    } // loop sulle risorse del corso
    // gestione delle edizioni
    for ($i_loop=1 ; $i_loop<=$numero_edizioni ; $i_loop++) {
        $nome_edizione = 'edizione_'.$i_loop;
        $esiste_edizione = array_key_exists($nome_edizione, $formdata);
        if ($esiste_edizione) {
            $flag_monitorata_S_N = EML_PENT_EDIZIONE_MONITORATA;
        } else {
            $flag_monitorata_S_N = EML_PENT_EDIZIONE_NON_MONITORATA;
        }
        $nome_id_edizione = 'id_edizione_'.$i_loop;
        $id_edizione = $formdata[$nome_id_edizione];
        $query = " UPDATE tbl_eml_pent_edizioni_corsi_on_line "
                ." SET flag_monitorata_S_N = '".$flag_monitorata_S_N."'"
                ." WHERE id_corso = ".$id_corso." AND id_edizione = ".$id_edizione;
        $mysqli->query($query);
    } // loop sulle edizioni
    //Gestione eventuale risorsa da usare per il punteggio finale
    //  Se esiste una risosrsa da usare per il punteggio
    //      estraggo l'identificativo della risorsa
    //      in tabella tbl_eml_pent_moduli_corsi_on_line (per il corso selezionato:
    //          pulisco (forzo a 0) flag_punteggio_finale per tutti i moduli
    //          imposto a 1 flag_punteggio_finale per la risorsa selezionata
    //  fine se
    $esiste_risorsa_punteggio = (int) $formdata['esiste_risorsa_punteggio'];
    if ($esiste_risorsa_punteggio == 1) {
        $risorsa_punteggio = (int) $formdata['risorsa_punteggio'];
    //      in tabella tbl_eml_pent_moduli_corsi_on_line (per il corso selezionato:
    //          pulisco (forzo a 0) flag_punteggio_finale per tutti i moduli
    //          imposto a 1 flag_punteggio_finale per la risorsa selezionata
        $query = " UPDATE tbl_eml_pent_moduli_corsi_on_line"
                ." SET flag_punteggio_finale = 0"
                ." WHERE id_corso = ".$id_corso;
        $mysqli->query($query);        
        $query = " UPDATE tbl_eml_pent_moduli_corsi_on_line"
                ." SET flag_punteggio_finale = 1"
                ." WHERE id_corso = ".$id_corso
                ." AND id_modulo = ".$risorsa_punteggio;
        $mysqli->query($query);        
    }
    // messaggio di modifica effettuata
    $rec_tbl_eml_grfo_log->id = NULL;
    $rec_tbl_eml_grfo_log->data = NULL;
    $rec_tbl_eml_grfo_log->id_corso = $id_corso;
    $rec_tbl_eml_grfo_log->cod_corso = $cod_corso;
    $rec_tbl_eml_grfo_log->titolo_corso = $titolo_corso;
    $rec_tbl_eml_grfo_log->pagina = 'Modifica_corso.php';
    $rec_tbl_eml_grfo_log->livello_msg = EML_MSG_OPERAZIONI_SUL_DB;
    $rec_tbl_eml_grfo_log->cod_msg = EML_MSG_UPD_CORSO;
    $rec_tbl_eml_grfo_log->descr_msg = get_string('grfo_log_modifica_corso', 'block_f2_report');
    $rec_tbl_eml_grfo_log->username = $USER->username;
    $aus = $USER->idnumber." - ".$USER->lastname.", ".$USER->firstname;
    $rec_tbl_eml_grfo_log->utente = $aus;
    if($contatore_posizione_in_report > EML_PENT_MAX_RISORSE_IN_REPORT) {
        $flag_troppe_risorse = TRUE;
        $aus = get_string('grfo_troppe_risorse_monitorabili', 'block_f2_report');
    } else {
        $flag_troppe_risorse = FALSE;
        $aus = ' ';
    }
    $rec_tbl_eml_grfo_log->nota = $aus;
    $ret_code = EML_Ins_tbl_eml_grfo_log($rec_tbl_eml_grfo_log);
    // preparo il record per la pagina di feed-back
    // lo inserisco in base dati (tabella tbl_eml_grfo_feed_back)
    // e vado alla pagina di feed-back
    $rec_tbl_eml_grfo_feed_back->id = NULL;
    $rec_tbl_eml_grfo_feed_back->id_corso = $id_corso;
    $rec_tbl_eml_grfo_feed_back->cod_corso = $cod_corso;
    $rec_tbl_eml_grfo_feed_back->titolo_corso = $titolo_corso;
    $rec_tbl_eml_grfo_feed_back->operazione = get_string('grfo_log_modifica_corso', 'block_f2_report');
    if($flag_troppe_risorse) {
        $rec_tbl_eml_grfo_feed_back->stato = get_string('grfo_feed_back_warning', 'block_f2_report');
        $rec_tbl_eml_grfo_feed_back->nota = get_string('grfo_piu_di_10_risorse_monitorate', 'block_f2_report');
    } else {
        $rec_tbl_eml_grfo_feed_back->stato = get_string('grfo_feed_back_tutto_ok', 'block_f2_report');
        $rec_tbl_eml_grfo_feed_back->nota = ' ';
    }
    $rec_tbl_eml_grfo_feed_back->url = 'report_on_line.php';
    $rec_tbl_eml_grfo_feed_back->flag_parametro_id_corso = get_string('grfo_feed_back_NO_parametro_id_corso', 'block_f2_report');
    $id_x_pagina_feed_back = EML_Ins_tbl_eml_grfo_feed_back($rec_tbl_eml_grfo_feed_back);
    $url_pagina_feed_back = new moodle_url('feed_back_page.php', array('id'=>$id_x_pagina_feed_back));
    $delay = 1;
    redirect($url_pagina_feed_back, null, $delay);
} 
// Visualizzazione/gestione form
$mform->display();
// Pie-pagina (secondo standard Moodle)
echo $OUTPUT->footer();