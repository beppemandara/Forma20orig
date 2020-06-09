<?php
/* A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * CREG - Upload da Riforma
 * 
 * Pagina che gestisce la storicizzazione in Forma delle partecipazioni ad un corso di Riforma
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
$PAGE->set_url('/blocks/f2_apprendimento/forma2riforma/archivia_corso_1.php');
$PAGE->set_title(get_string('f2r_title_archiviacorso', 'block_f2_apprendimento'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('f2r_forma2riforma', 'block_f2_apprendimento'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('f2r_header_archiviacorso', 'block_f2_apprendimento'));
require_once "strutture_dati.php";
require_once "function_db.php";
require_once "form_definitions.php";
// Preparo i valori da assegnare ai campi della Form che gestisce la lettura delle partecipazioni ad un corso
$id = $_REQUEST['id'];
$rec_mdl_f2_forma2riforma_mapping = new EML_RECmdl_f2_forma2riforma_mapping();
$rec_mdl_course = new EML_RECmdl_course();
$rec_Dati_utente = new EML_Dati_utente();
$rec_mdl_f2_anagrafica_corsi = new EML_RECmdl_f2_anagrafica_corsi();
$rec_mdl_f2_fornitori = new EML_RECmdl_f2_fornitori();
$rec_mdl_org = new EML_RECmdl_org();
$rec_mdl_f2_storico_corsi = new EML_RECmdl_f2_storico_corsi();
$vet_mdl_f2_forma2riforma_partecipazioni = array();
//
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
// Form che gestisce l'archiviazione in storico delle partecipazioni
$mform = new form_archivia_corso(NULL, $valori_campi);
if ($mform->is_cancelled()) {
    // se form cancellata torno a maschera con elenco corsi in gestione
    $elenco_corsi_url = new moodle_url('elenco_corsi.php');
    redirect($elenco_corsi_url, null);
} else if ($fromform = $mform->get_data()) {
    // carico in variabili locali i dati letti dalla maschera
    $id_mapping = $fromform->id;
    $shortname =  $fromform->shortname;
    // lettura dei dati del corso
    $ret_code = Get_mdl_f2_forma2riforma_mapping($id_mapping, $rec_mdl_f2_forma2riforma_mapping);
    $id_forma20 = $rec_mdl_f2_forma2riforma_mapping->id_forma20;
    $ret_code = Get_mdl_course_Forma20($id_forma20, $rec_mdl_course);
    $ret_code = Get_mdl_f2_anagrafica_corsi($id_forma20, $rec_mdl_f2_anagrafica_corsi);
    // estraggo scuola/direzione tenendo conto di flag_dir_scuola
    $flag_dir_scuola = $rec_mdl_f2_anagrafica_corsi->flag_dir_scuola;
    $id_dir_scuola = $rec_mdl_f2_anagrafica_corsi->id_dir_scuola;
    if ($flag_dir_scuola == "S") {
        $ret_code = Get_mdl_f2_fornitori($id_dir_scuola, $rec_mdl_f2_fornitori);
        $scuola_ente = $rec_mdl_f2_fornitori->denominazione;
    } else {
        $ret_code = Get_mdl_org($id_dir_scuola, $rec_mdl_org);
        $scuola_ente = $rec_mdl_org->fullname;
    }
    // Lettura delle partecipazioni
    $numero_partecipazioni = 0;
    $vet_mdl_f2_forma2riforma_partecipazioni = Get_mdl_f2_forma2riforma_partecipazioni($id_mapping, $numero_partecipazioni);
    // messaggio di log di Inizio archiviazione
    $record_log = new stdClass();
    $record_log->shortname = $shortname;
    $record_log->data_ora = time();
    $record_log->codice = EML_RIFORMA_START_WRITE_IN_STORICO;
    $aus = get_string('f2r_start_scrittura_in_storico', 'block_f2_apprendimento');
    $record_log->descrizione = $aus;
    $lastinsertid = $DB->insert_record('f2_forma2riforma_log', $record_log);
    // loop di archiviazione in storico delle partecipazioni
    $num_rec_skipped = 0;
    $i = 1;
    while ($i <= $numero_partecipazioni) {  
        if ($vet_mdl_f2_forma2riforma_partecipazioni[$i]->stato == EML_RIFORMA_PARTECIPAZIONE_NON_OK) {
        // dati di partecipazione non completi
        //   incremento contatore record ignorati
        //   messaggio di log
        $num_rec_skipped++;
        $record_log->shortname = $rec_mdl_course->shortname;
        $record_log->codice = EML_RIFORMA_NO_WRITE_IN_STORICO;
        $aus = get_string('f2r_non_archiviato1', 'block_f2_apprendimento');
        $aus .= $vet_mdl_f2_forma2riforma_partecipazioni[$i]->matricola;
        $aus .= get_string('f2r_non_archiviato2', 'block_f2_apprendimento');
        $aus .= $vet_mdl_f2_forma2riforma_partecipazioni[$i]->id_user_Riforma;
        $aus .= get_string('f2r_non_archiviato3', 'block_f2_apprendimento');
        $aus .= $vet_mdl_f2_forma2riforma_partecipazioni[$i]->cognome_Riforma;
        $aus .= get_string('f2r_non_archiviato4', 'block_f2_apprendimento');
        $aus .= $vet_mdl_f2_forma2riforma_partecipazioni[$i]->nome_Riforma;
        $record_log->descrizione = $aus;
        $lastinsertid = $DB->insert_record('f2_forma2riforma_log', $record_log);
        } else {
            // dati di partecipazione completi
            // calcolo i dati di presenza, partecipazione, ecc. in funzione del punteggio test
            if ($vet_mdl_f2_forma2riforma_partecipazioni[$i]->punteggio_Riforma < $rec_mdl_f2_forma2riforma_mapping->perc_x_cfv) {
                $presenza = 0;
                $codpart = EML_RIFORMA_CODPART_PARTECIPAZIONE_SENZA_VERIFICA;
                $descrpart = EML_RIFORMA_DESCRPART_PARTECIPAZIONE_SENZA_VERIFICA;
                $cfv = 0;
                $va = EML_RIFORMA_VA_PARTECIPAZIONE_SENZA_VERIFICA;
            } else {
                $presenza = $rec_mdl_f2_anagrafica_corsi->durata;
                $codpart = EML_RIFORMA_CODPART_ESECUTIVO_CON_VERIFICA;
                $descrpart = EML_RIFORMA_DESCRPART_ESECUTIVO_CON_VERIFICA;
                $cfv = $rec_mdl_f2_anagrafica_corsi->cf;
                $va = $rec_mdl_f2_forma2riforma_mapping->va_default;
            }        
            // preparo il record da inserire in storico
            $rec_mdl_f2_storico_corsi->matricola = strtoupper($vet_mdl_f2_forma2riforma_partecipazioni[$i]->matricola);
            $rec_mdl_f2_storico_corsi->cognome = $vet_mdl_f2_forma2riforma_partecipazioni[$i]->cognome_Forma;
            $rec_mdl_f2_storico_corsi->nome = $vet_mdl_f2_forma2riforma_partecipazioni[$i]->nome_Forma;
            $rec_mdl_f2_storico_corsi->sesso = $vet_mdl_f2_forma2riforma_partecipazioni[$i]->sesso_Forma;
            $rec_mdl_f2_storico_corsi->categoria = $vet_mdl_f2_forma2riforma_partecipazioni[$i]->categoria_Forma;
            $rec_mdl_f2_storico_corsi->ap = $vet_mdl_f2_forma2riforma_partecipazioni[$i]->ap_Forma;
            $rec_mdl_f2_storico_corsi->e_mail = $vet_mdl_f2_forma2riforma_partecipazioni[$i]->email_Forma;
            $rec_mdl_f2_storico_corsi->cod_direzione = $vet_mdl_f2_forma2riforma_partecipazioni[$i]->cod_direzione_Forma;
            $rec_mdl_f2_storico_corsi->direzione = $vet_mdl_f2_forma2riforma_partecipazioni[$i]->direzione_Forma;
            $rec_mdl_f2_storico_corsi->cod_settore = $vet_mdl_f2_forma2riforma_partecipazioni[$i]->cod_settore_Forma;
            $rec_mdl_f2_storico_corsi->settore = $vet_mdl_f2_forma2riforma_partecipazioni[$i]->settore_Forma;
            $rec_mdl_f2_storico_corsi->codcorso = $rec_mdl_course->idnumber;
            $rec_mdl_f2_storico_corsi->tipo_corso = EML_RIFORMA_TIPO_CORSO_OBIETTIVO;
            $rec_mdl_f2_storico_corsi->data_inizio = $rec_mdl_f2_forma2riforma_mapping->data_inizio;
            $rec_mdl_f2_storico_corsi->costo = $rec_mdl_f2_anagrafica_corsi->costo;
            $rec_mdl_f2_storico_corsi->af = $rec_mdl_f2_anagrafica_corsi->af;
            $rec_mdl_f2_storico_corsi->to_x = $rec_mdl_f2_anagrafica_corsi->to_x;
            $rec_mdl_f2_storico_corsi->orario = $rec_mdl_f2_anagrafica_corsi->orario;
            $rec_mdl_f2_storico_corsi->titolo = $rec_mdl_course->fullname;
            $rec_mdl_f2_storico_corsi->durata = $rec_mdl_f2_anagrafica_corsi->durata;
            $rec_mdl_f2_storico_corsi->scuola_ente = $scuola_ente;
            $aus = EML_RIFORMA_NOME_PROGRAMMA." - ".EML_RIFORMA_VERSIONE_PROGRAMMA;
            $rec_mdl_f2_storico_corsi->note = $aus;
            $rec_mdl_f2_storico_corsi->presenza = $presenza;
            $rec_mdl_f2_storico_corsi->codpart = $codpart;
            $rec_mdl_f2_storico_corsi->descrpart = $descrpart;
            $rec_mdl_f2_storico_corsi->sub_af = $rec_mdl_f2_anagrafica_corsi->subaf;
            $rec_mdl_f2_storico_corsi->cfv = $cfv;
            $rec_mdl_f2_storico_corsi->va = $va;
            $rec_mdl_f2_storico_corsi->cf = $rec_mdl_f2_anagrafica_corsi->cf;
            $rec_mdl_f2_storico_corsi->te = $rec_mdl_f2_anagrafica_corsi->te;
            $rec_mdl_f2_storico_corsi->sf = $rec_mdl_f2_anagrafica_corsi->sf;
            // scrivo in storico
            $ret_code = Ins_mdl_f2_storico_corsi($rec_mdl_f2_storico_corsi);
        } // dati di partecipazione completi
        // incremento il contatore dei record elaborati
        $i++;
    } // while ($i <= $numero_partecipazioni)
    // Messaggio di log di fine lettura dati utenti
    $record_scritti = $numero_partecipazioni - $num_rec_skipped;;
    $record_log->shortname = $shortname;
    $record_log->data_ora = time();
    $record_log->codice = EML_RIFORMA_END_WRITE_IN_STORICO;
    if ($num_rec_skipped > 0) {
        $aus = get_string('f2r_end_scrittura_in_storico_Anomalie', 'block_f2_apprendimento');
        $aus .= $record_scritti;
        $aus .= get_string('f2r_end_scrittura_in_storico_rec_skipped', 'block_f2_apprendimento');
        $aus .= $num_rec_skipped;        
    }  else {
        $aus = get_string('f2r_end_scrittura_in_storico_OK', 'block_f2_apprendimento');
        $aus .= $record_scritti;
    }
    $record_log->descrizione = $aus;
    $lastinsertid = $DB->insert_record('f2_forma2riforma_log', $record_log);
    // cambio lo stato al corso ed aggiorno il db
    $record_update = new stdClass();
    $record_update->id = $id_mapping;
    if ($num_rec_skipped  > 0) {
        $aus = get_string('f2r_archiviazione_warning', 'block_f2_apprendimento');
        $aus .= $record_scritti;
        $aus .= get_string('f2r_end_scrittura_in_storico_rec_skipped', 'block_f2_apprendimento');
        $aus .= $num_rec_skipped;        
        $record_update->stato = EML_RIFORMA_ARCHIVIAZIONE_WARNING;
    } else {
        $aus = get_string('f2r_archiviazione_ok', 'block_f2_apprendimento');
        $aus .= $record_scritti;
        $record_update->stato = EML_RIFORMA_ARCHIVIAZIONE_OK;
    }
    $record_update->nota = $aus;
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