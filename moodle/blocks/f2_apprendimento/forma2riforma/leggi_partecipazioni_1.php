<?php
/* A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * CREG - Upload da Riforma
 * 
 * Pagina che gestisce la lettura da Riforma delle partecipazioni ad un corso
 * 
 * Parametri:
 *     $_REQUEST['id']: id del corso (in Forma)
 */
// Inizializzazioni "varie" secondo standard Moodle
global $OUTPUT, $PAGE, $SITE, $CFG, $DB;
require_once '../../../config.php';
require_login();
$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('block/f2_apprendimento:forma2riforma', $context);
$baseurl = new moodle_url('/blocks/f2_apprendimento/forma2riforma/elenco_corsi.php');
$blockname = get_string('pluginname', 'block_f2_apprendimento');
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/f2_apprendimento/forma2riforma/leggi_partecipazioni_1.php');
$PAGE->set_title(get_string('f2r_title_leggipartecipazioni', 'block_f2_apprendimento'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('f2r_forma2riforma', 'block_f2_apprendimento'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('f2r_header_leggipartecipazioni', 'block_f2_apprendimento'));
require_once "strutture_dati.php";
require_once "function_db.php";
require_once "form_definitions.php";
// Preparo i valori da assegnare ai campi della Form che gestisce la lettura delle partecipazioni ad un corso
$id = $_REQUEST['id'];
$rec_mdl_f2_forma2riforma_mapping = new EML_RECmdl_f2_forma2riforma_mapping();
$rec_mdl_course = new EML_RECmdl_course();
$rec_Dati_utente = new EML_Dati_utente();
$ret_code = Get_mdl_f2_forma2riforma_mapping($id, $rec_mdl_f2_forma2riforma_mapping);
$id_forma20 = $rec_mdl_f2_forma2riforma_mapping->id_forma20;
$ret_code = Get_mdl_course_Forma20($id_forma20, $rec_mdl_course);
$valori_campi = array();
$valori_campi['id'] = $id;
$valori_campi['shortname'] = $rec_mdl_f2_forma2riforma_mapping->shortname;
$valori_campi['id_forma20'] = $rec_mdl_f2_forma2riforma_mapping->shortname." - ".$rec_mdl_course->fullname;
$valori_campi['perc_x_cfv'] = $rec_mdl_f2_forma2riforma_mapping->perc_x_cfv;
$valori_campi['va_default'] = $rec_mdl_f2_forma2riforma_mapping->va_default;
$valori_campi['id_riforma'] = $rec_mdl_f2_forma2riforma_mapping->id_riforma;
// Form che gestisce la modifica dei parametri di un corso
$mform = new form_leggi_partecipazioni(NULL, $valori_campi);
if ($mform->is_cancelled()) {
    // se form cancellata torno a maschera con elenco corsi in gestione
    $elenco_corsi_url = new moodle_url('elenco_corsi.php');
    redirect($elenco_corsi_url, null);
} else if ($fromform = $mform->get_data()) { 
    // carico in variabili locali i dati letti dalla form
    $id_mapping = $fromform->id;
    $shortname =  $fromform->shortname;
    $id_scorm = $fromform->id_scorm;
    //echo"<pre>id_scorm: ".var_dump($id_scorm)."</pre>";
    // leggo i dati del modulo Scorm
    $rec_mdl_scorm = new EML_RECmdl_scorm();
    $ret_code = Get_mdl_scorm_Riforma($id_scorm, $rec_mdl_scorm);   
    // messaggio di log di Inizio lettura
    $record_log = new stdClass();
    $record_log->shortname = $shortname;
    $record_log->data_ora = time();
    $record_log->codice = EML_RIFORMA_START_READ_PARTECIPAZIONI;
    $aus = get_string('f2r_start_lettura_partecipazioni', 'block_f2_apprendimento');
    $aus .= $rec_mdl_scorm->name;
    $record_log->descrizione = $aus;
    $lastinsertid = $DB->insert_record('f2_forma2riforma_log', $record_log);
    // Lettura delle partecipazioni allo Scorm selezionato e messaggio di log di avvenuta lettura
    $numero_partecipazioni = 0;
    $vet_mdl_scorm_scoes_track_and_user = Get_mdl_scorm_scoes_track_and_user_Riforma($id_scorm, $numero_partecipazioni);
    $record_log->shortname = $shortname;
    $record_log->data_ora = time();
    $record_log->codice = EML_RIFORMA_END_READ_PARTECIPAZIONI;
    $aus = get_string('f2r_end_lettura_partecipazioni', 'block_f2_apprendimento');
    $aus .= $numero_partecipazioni;
    $record_log->descrizione = $aus;
    $lastinsertid = $DB->insert_record('f2_forma2riforma_log', $record_log);
    // Messaggio di log di inizio lettura dati utenti
    $record_log->shortname = $shortname;
    $record_log->data_ora = time();
    $record_log->codice = EML_RIFORMA_START_READ_MAPPING;
    $aus = get_string('f2r_start_lettura_dati_utenti', 'block_f2_apprendimento');
    $record_log->descrizione = $aus;
    $lastinsertid = $DB->insert_record('f2_forma2riforma_log', $record_log);
    // Loop di lettura dei dati utenti in Forma
    $num_err_read = 0;
    $i = 1;
    while ($i <= $numero_partecipazioni) {  
        $vet_mdl_f2_forma2riforma_partecipazioni[$i] = new EML_RECmdl_f2_forma2riforma_partecipazioni();
        $matricola = $vet_mdl_scorm_scoes_track_and_user[$i]->username;        
        $vet_mdl_f2_forma2riforma_partecipazioni[$i]->id_mapping = $id_mapping;
        $vet_mdl_f2_forma2riforma_partecipazioni[$i]->matricola = $matricola;
        $vet_mdl_f2_forma2riforma_partecipazioni[$i]->id_user_Riforma =  $vet_mdl_scorm_scoes_track_and_user[$i]->userid;
        $vet_mdl_f2_forma2riforma_partecipazioni[$i]->cognome_Riforma = $vet_mdl_scorm_scoes_track_and_user[$i]->lastname;
        $vet_mdl_f2_forma2riforma_partecipazioni[$i]->nome_Riforma = $vet_mdl_scorm_scoes_track_and_user[$i]->firstname;
        $vet_mdl_f2_forma2riforma_partecipazioni[$i]->id_scorm_Riforma = $vet_mdl_scorm_scoes_track_and_user[$i]->scormid;
        $vet_mdl_f2_forma2riforma_partecipazioni[$i]->punteggio_Riforma = $vet_mdl_scorm_scoes_track_and_user[$i]->value;
        $ret_code = Get_dati_utente($matricola, $rec_Dati_utente);
        if ($ret_code <> 1) {
            // problemi in lettura dati utente
            //    incremento contatore di errori
            //    setto il codice di anomalia in record partecipazioni
            //    scrivo messaggio di log
            $num_err_read++;
            $vet_mdl_f2_forma2riforma_partecipazioni[$i]->stato = EML_RIFORMA_PARTECIPAZIONE_NON_OK;
            $aus = get_string('f2r_problemi_in_mapping_utente', 'block_f2_apprendimento');
            $aus .= 'Cod err: '.$ret_code;
            $vet_mdl_f2_forma2riforma_partecipazioni[$i]->nota = $aus;
            $record_log->shortname = $shortname;
            $record_log->data_ora = time();
            $record_log->codice = EML_RIFORMA_ERR_MAPPING_UTENTE;
            $aus = get_string('f2r_problemi_in_mapping_utente', 'block_f2_apprendimento');
            $aus .= 'matricola: '.$matricola;
            $aus .= ' userid: '.$vet_mdl_scorm_scoes_track_and_user[$i]->userid;
            $aus .= ' cognome: '.$vet_mdl_scorm_scoes_track_and_user[$i]->lastname;
            $aus .= ' nome: '.$vet_mdl_scorm_scoes_track_and_user[$i]->firstname;
            $record_log->descrizione = $aus;
            $lastinsertid = $DB->insert_record('f2_forma2riforma_log', $record_log);
        } else {
            // lettura dati utente OK
            //    completo la valorizzazione del record partecipazioni
            $vet_mdl_f2_forma2riforma_partecipazioni[$i]->stato = EML_RIFORMA_PARTECIPAZIONE_OK;
            $vet_mdl_f2_forma2riforma_partecipazioni[$i]->id_user_Forma = $rec_Dati_utente->id_utente_Forma;
            $vet_mdl_f2_forma2riforma_partecipazioni[$i]->cognome_Forma = $rec_Dati_utente->cognome;
            $vet_mdl_f2_forma2riforma_partecipazioni[$i]->nome_Forma = $rec_Dati_utente->nome;
            $vet_mdl_f2_forma2riforma_partecipazioni[$i]->codice_fiscale_Forma = $rec_Dati_utente->codice_fiscale;
            $vet_mdl_f2_forma2riforma_partecipazioni[$i]->sesso_Forma = $rec_Dati_utente->sesso;
            $vet_mdl_f2_forma2riforma_partecipazioni[$i]->email_Forma = $rec_Dati_utente->email;
            $vet_mdl_f2_forma2riforma_partecipazioni[$i]->categoria_Forma = $rec_Dati_utente->categoria;
            $vet_mdl_f2_forma2riforma_partecipazioni[$i]->ap_Forma = $rec_Dati_utente->ap;
            $vet_mdl_f2_forma2riforma_partecipazioni[$i]->cod_settore_Forma = $rec_Dati_utente->cod_settore;
            $vet_mdl_f2_forma2riforma_partecipazioni[$i]->settore_Forma = $rec_Dati_utente->settore;
            $vet_mdl_f2_forma2riforma_partecipazioni[$i]->cod_direzione_Forma = $rec_Dati_utente->cod_direzione;
            $vet_mdl_f2_forma2riforma_partecipazioni[$i]->direzione_Forma = $rec_Dati_utente->direzione;
            $vet_mdl_f2_forma2riforma_partecipazioni[$i]->nota = NULL;
        } // if ($ret_code <> 1)
        // incremento il contatore dei record elaborati
        $i++;
    } // while ($i <= $numero_partecipazioni)
    // Messaggio di log di fine lettura dati utenti
    $record_log->shortname = $shortname;
    $record_log->data_ora = time();
    $record_log->codice = EML_RIFORMA_END_READ_MAPPING;
    $aus = get_string('f2r_end_lettura_dati_utenti', 'block_f2_apprendimento');
    $aus .= $num_err_read;
    $record_log->descrizione = $aus;
    $lastinsertid = $DB->insert_record('f2_forma2riforma_log', $record_log);
    // Messaggio di log di inizio scrittura partecipazioni in Forma
    $record_log->shortname = $shortname;
    $record_log->data_ora = time();
    $record_log->codice = EML_RIFORMA_START_WRITE_PARTECIPAZIONI;
    $aus = get_string('f2r_start_scrittura_partecipazioni', 'block_f2_apprendimento');
    $record_log->descrizione = $aus;
    $lastinsertid = $DB->insert_record('f2_forma2riforma_log', $record_log);
    //loop di chiamata della function di insert in data base
    $i = 1;
    while ($i <= $numero_partecipazioni) {
        $ret_code = Ins_mdl_f2_forma2riforma_partecipazioni($vet_mdl_f2_forma2riforma_partecipazioni[$i]);
        $i++;
    }
    // Messaggio di log di fine scrittura partecipazioni in Forma
    $record_log->shortname = $shortname;
    $record_log->data_ora = time();
    $record_log->codice = EML_RIFORMA_END_READ_PARTECIPAZIONI;
    $aus = get_string('f2r_end_scrittura_partecipazioni', 'block_f2_apprendimento');
    $record_log->descrizione = $aus;
    $lastinsertid = $DB->insert_record('f2_forma2riforma_log', $record_log);
    // cambio lo stato al corso ed aggiorno il db
    $record_update = new stdClass();
    $record_update->id = $id_mapping;
    if ($num_err_read > 0) {
        $aus = get_string('f2r_lettura_warning', 'block_f2_apprendimento');
        $aus .= ' - Record letti: '.$numero_partecipazioni;
        $aus .= ' - Anomalie in lettura: '.$num_err_read;
        $record_update->nota = $aus;
        $record_update->stato = EML_RIFORMA_LETTURA_WARNING;
    } else {
        $aus = get_string('f2r_lettura_ok', 'block_f2_apprendimento');
        $aus .= ' - record letti: '.$numero_partecipazioni;
        $record_update->nota = $aus;
        $record_update->stato = EML_RIFORMA_LETTURA_OK;
    }
    $DB->update_record('f2_forma2riforma_mapping', $record_update);
    // redirect alla pagina con elenco corsi in gestione
    $elenco_corsi_url = new moodle_url('elenco_corsi.php');
    redirect($elenco_corsi_url, null);
}
// Visualizzazione/gestione form
$mform->display();
// Chiusura pagina (secondo standard Moodle)
echo $OUTPUT->footer()
?>