<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * Funzioni di accesso al db
 * 
 * Situazione allineamento agli standard Moodle (uso di $DB->xxxxx)
 *     Del_mdl_f2_forma2riforma_mapping                 OK
 *     Del_mdl_f2_forma2riforma_partecipazioni          OK
 *     Get_dati_utente                                  OK
 *     Get_elenco_corsi_collegabili_1                   OK (usa mysqli_Riforma)
 *     Get_elenco_corsi_in_gestione                     OK
 *     Get_log_corso                                    OK
 *     Get_mdl_course_Forma20                           OK
 *     Get_mdl_f2_anagrafica_corsi                      OK
 *     Get_mdl_f2_forma2riforma_mapping                 OK
 *     Get_mdl_f2_forma2riforma_partecipazioni          OK
 *     Get_mdl_f2_fornitori                             OK
 *     Get_mdl_f2_va                                    OK
 *     Get_mdl_org                                      da testare
 *     Ins_mdl_f2_forma2riforma_mapping                 OK
 *     Ins_mdl_f2_forma2riforma_partecipazioni          OK
 *     Ins_mdl_f2_storico_corsi                         OK
 *
 *  NOTA IMPORTANTE: 
 *      La DB->get_records_sql (per motivi sconosciuti) restituisce i nomi dei campi TUTTI IN MINUSCOLO
 *      ad esempio bisogna usare $row->id_user_riforma e non $row->id_user_Riforma,. ecc.
 * 
 * Le seguenti function non saranno allineate agli standard Moodle (usano la base dati di Riforma)
 *     Get_elenco_scorm_Riforma_1                       OK
 *     Get_mdl_course_Riforma                           OK
 *     Get_mdl_scorm_Riforma                            OK
 *     Get_mdl_scorm_scoes_track_and_user_Riforma       OK
 * 
 * NOTA IMPORTANTE per le function che usano direttamente le function mysqli senza passare
 *      dalle function di Moodle (in pratica tutti gli accessi alla base dati di Riforma):
 *      Per motivi al momento sconosciuti in ambiente di Sviluppo le foreach($result as $row) 
 *      restituiscono degli oggetti vuoti.
 *      Occorre usare dei cicli While con un esplicito $result->fetch_object()
*/
function Del_mdl_f2_forma2riforma_mapping($id) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * Cancella dalla tabella mdl_f2_forma2riforma_mapping il record con id = $id
 * 
 * Restituisce:
 *     1 = cancellato il record
 *     0 = nessuna nacellazione
 * 
 * Parametri
 *     $id -- Identificativo del record da cancellare 
*/
    global $DB;
    if($DB->record_exists('f2_forma2riforma_mapping', array('id' => $id))) {
        $DB->delete_records('f2_forma2riforma_mapping', array('id' => $id));
        return 1;
    } else {
        return 0;
    }
} // Del_mdl_f2_forma2riforma_mapping
function Del_mdl_f2_forma2riforma_partecipazioni($id_mapping) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * Cancella dalla tabella mdl_f2_forma2riforma_partecipazioni i record con id_mapping = $id_mapping
 * 
 * Restituisce:
 *     Se tutto ok 
 *         il numero di record cancellati
 *     altrimenti
 *         0
 * 
 * Parametri
 *     $id_mapping Identifica i record da cancellare 
*/
    global $DB;
    $ret_code = $DB->count_records('f2_forma2riforma_partecipazioni', array('id_mapping' => $id_mapping));
    if($ret_code > 0) {
        $DB->delete_records('f2_forma2riforma_partecipazioni', array('id_mapping' => $id_mapping));
        return $ret_code;
    } else {
        return 0;
    }
} // Del_mdl_f2_forma2riforma_partecipazioni
function Get_dati_utente($matricola, EML_Dati_utente $rec_Dati_utente) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * Estrae da Forma i dati dell'utente identificato dal parametro $matricola
 * 
 * Restituisce:
 *     1    Utente trovato, valorizzato correttamente rec_Dati_utente
 *    -1    dati non trovati in mdl_user
 *    -2    dati non trovati in mdl_user_info_data
 *    -3    non trovati i dati di struttura utente (settore, direzione)
 *    -4   trovato dati multipli per struttura utente (settore, direzione)
 * 
 * Parametri
 *     $matricola -- Matricola del dipendente da cercare
 *     $rec_Dati_utente -- record con i dati dell'utente
*/
    global $DB;
    //  Leggo i dati utente presenti in mdl_user
    $result = $DB->get_record('user', array('idnumber' => $matricola));
    if ($result == false) {
        return -1;
    } else {
        $rec_Dati_utente->codice_fiscale = $result->username;
        $rec_Dati_utente->matricola = $result->idnumber;
        $rec_Dati_utente->cognome = $result->lastname;
        $rec_Dati_utente->nome = $result->firstname;
        $rec_Dati_utente->email = $result->email;
        $rec_Dati_utente->id_utente_Forma = $result->id;
    }
    //  Leggo i dati utente presenti in mdl_user_info_data (sesso, categoria, ap)
    $result = $DB->get_records('user_info_data', array('userid' => $rec_Dati_utente->id_utente_Forma));
    if ($result == false) {
        return -2;
    }
    foreach($result as $row) {
        $fieldid = $row->fieldid;
        $data = $row->data;
        switch ($fieldid) {
            case EML_RIFORMA_FIELDID_CATEGORIA:
                $rec_Dati_utente->categoria = $data;
                break;
            case EML_RIFORMA_FIELDID_AP:
                $rec_Dati_utente->ap = $data;
                break;
            case EML_RIFORMA_FIELDID_SESSO:
                $rec_Dati_utente->sesso = $data;
                break;
        }
    }
    //  Leggo i dati di struttura (settore, direzione)
    $query = "SELECT B.shortname as cod_settore, B.fullname as settore"
            .", C.direzione_mdl_org_shortname as cod_direzione, C.direzione_mdl_org_fullname as direzione"
            ." FROM mdl_org_assignment A JOIN mdl_org B ON A.organisationid = B.id"
            ." JOIN tbl_eml_mapping_org C ON B.shortname = C.mdl_org_shortname"
            ." WHERE A.userid = ".$rec_Dati_utente->id_utente_Forma
            ." LIMIT 1";
    $result = $DB->get_records_sql($query);
    $numero_record = 0;
    foreach($result as $row) {
        $numero_record++;
        $rec_Dati_utente->cod_settore = $row->cod_settore;
        $rec_Dati_utente->settore = $row->settore;
        $rec_Dati_utente->cod_direzione = $row->cod_direzione;
        $rec_Dati_utente->direzione = $row->direzione;        
    }
    if ($numero_record == 0) {
        return -3;
    } else if ($numero_record > 1) {
        return -4;
    }
    // se arrivato fino a qui posso uscire col codice di successo
    return 1;
} // Get_dati_utente
function Get_elenco_corsi_in_gestione (&$numero_corsi) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * Estrae l'elenco dei corsi in gestione (quelli presenti in tabella mdl_f2_forma2riforma_mapping)
 * 
 * Restituisce:
 *     $corsi_in_gestione - array globale di tipo EML_Corsi_in_gestione con i dati letti dal db
 * 
 * Parametri
 *     &$numero_corsi - numero di record presenti in $corsi_in_gestione
*/
    global $DB;
    $corsi_in_gestione = array();
    $query = "SELECT A.id, A.shortname, A.data_inizio, A.perc_x_cfv, A.va_default, A.stato, A.nota "
            .", B.fullname "
            ." FROM mdl_f2_forma2riforma_mapping A, mdl_course B"
            ." WHERE A.id_forma20 = B.id"
            ." ORDER BY data_inizio DESC";
    $result = $DB->get_records_sql($query);
    $numero_corsi = 0;
    foreach($result as $row) {
        $numero_corsi++;
        $corsi_in_gestione[$numero_corsi] = new EML_Corsi_in_gestione();
        $corsi_in_gestione[$numero_corsi]->id_mapping = $row->id;
        $corsi_in_gestione[$numero_corsi]->shortname = $row->shortname;
        $corsi_in_gestione[$numero_corsi]->titolo = $row->fullname;
        $corsi_in_gestione[$numero_corsi]->data_inizio = $row->data_inizio;
        $corsi_in_gestione[$numero_corsi]->stato = $row->stato;
        $corsi_in_gestione[$numero_corsi]->perc_x_cfv = $row->perc_x_cfv;
        $corsi_in_gestione[$numero_corsi]->va_default = $row->va_default;
        $corsi_in_gestione[$numero_corsi]->nota = $row->nota;
    };
    return $corsi_in_gestione;
} // Get_elenco_corsi_in_gestione
function Get_log_corso ($shortname, &$numero_record) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - novembre 2013
 * 
 * Estrae i record presenti in tabella mdl_f2_forma2riforma_log del corso specificato da $shortname
 * 
 * Restituisce:
 *     $vet_mdl_f2_forma2riforma_log -- array globale di tipo EML_RECmdl_f2_forma2riforma_log con i dati letti dal db
 * 
 * Parametri
 *     &$numero_record -- numero di record presenti in $vet_mdl_f2_forma2riforma_log
*/
    global $DB;
    $vet_mdl_f2_forma2riforma_log = array();
    $query = "SELECT id, shortname, data_ora, codice, descrizione"
            ." FROM mdl_f2_forma2riforma_log"
            ." WHERE shortname ='".$shortname."'"
            ." ORDER BY id DESC";
    $result = $DB->get_records_sql($query);
    $numero_record = 0;
    foreach($result as $row) {
        $numero_record++;
        $vet_mdl_f2_forma2riforma_log[$numero_record] = new EML_RECmdl_f2_forma2riforma_log();
        $vet_mdl_f2_forma2riforma_log[$numero_record]->id = $row->id;
        $vet_mdl_f2_forma2riforma_log[$numero_record]->shortname = $row->shortname;
        $vet_mdl_f2_forma2riforma_log[$numero_record]->data_ora = $row->data_ora;
        $vet_mdl_f2_forma2riforma_log[$numero_record]->codice = $row->codice;
        $vet_mdl_f2_forma2riforma_log[$numero_record]->descrizione = $row->descrizione;
    };
    return $vet_mdl_f2_forma2riforma_log;
} // Get_log_corso
function Get_mdl_f2_va() {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * Estrae i record presenti in tabella mdl_f2_va
 * 
 * Restituisce:
 *     $vet_mdl_f2_va -- array con i dati letti dal db
 * 
 * Parametri: nessuno
*/
    global $DB;
    $vet_mdl_f2_va = array();
    $query = "SELECT id, descrizione"
            ." FROM mdl_f2_va"
            ." WHERE stato ='A'"
            ." ORDER BY progr_displ DESC";
    $result = $DB->get_records_sql($query);
    foreach ($result as $row) {
        $vet_mdl_f2_va[$row->id] = $row->descrizione;
    }
    return $vet_mdl_f2_va;
} // Get_mdl_f2_va
function Get_mdl_f2_forma2riforma_mapping($id, EML_RECmdl_f2_forma2riforma_mapping $rec_mdl_f2_forma2riforma_mapping) {
 /*
 * A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * Legge dalla tabella mdl_f2_forma2riforma_mapping il record con id = $id 
 * se lo trova  valorizza $rec_mdl_f2_forma2riforma_mapping con i campi letti
 * 
 * Parametri:
 *    $id -- Identificativo del record da cercare 
 *    $rec_mdl_f2_forma2riforma_mapping -- Record con i dati letti (mappa la tabella mdl_f2_forma2riforma_mapping)
 *
 * Codici restituiti:
 *     0 --> dati non trovati
 *     1 --> tutto ok
 */
    global $DB;
    $result = $DB->get_record('f2_forma2riforma_mapping', array('id' => $id));
    if ($result == false) {
        return 0;
    } else {
        $rec_mdl_f2_forma2riforma_mapping->id = $result->id;
        $rec_mdl_f2_forma2riforma_mapping->shortname = $result->shortname;
        $rec_mdl_f2_forma2riforma_mapping->id_riforma = $result->id_riforma;
        $rec_mdl_f2_forma2riforma_mapping->id_forma20 = $result->id_forma20;
        $rec_mdl_f2_forma2riforma_mapping->perc_x_cfv = $result->perc_x_cfv;
        $rec_mdl_f2_forma2riforma_mapping->va_default = $result->va_default;
        $rec_mdl_f2_forma2riforma_mapping->data_inizio = $result->data_inizio;
        $rec_mdl_f2_forma2riforma_mapping->stato = $result->stato;
        $rec_mdl_f2_forma2riforma_mapping->nota = $result->nota;
        return 1;
    }
} //function Get_mdl_f2_forma2riforma_mapping
function Get_mdl_f2_forma2riforma_partecipazioni($id_mapping, &$numero_partecipazioni){
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * Legge dalla tabella mdl_f2_forma2riforma_partecipazioni i record con id_mapping = $id_mapping
 * restituisce il numero di record letti ($numero_partecipazioni) ed un vettore di record con
 * i dati letti ($vet_mdl_f2_forma2riforma_partecipazioni)
 * 
 * Restituisce:
 *     $vet_mdl_f2_forma2riforma_partecipazioni 
 *          array di tipo EML_RECmdl_f2_forma2riforma_partecipazioni con i dati letti dal db
 * 
 * Parametri
 *     $id_mapping - identifica il corso di interesse
 *     &$numero_partecipazioni - numero di record letti
*/
    global $DB;
/*
 * ATTENZIONE 
 *  La DB->get_records_sql (per motivi sconosciuti) restituisce i nomi dei campi TUTTI IN MINUSCOLO
 *  quindi bisogna usare $row->id_user_riforma e non $row->id_user_Riforma ecc. per tutti i campi
*/
    $query = "SELECT id, id_mapping, matricola, id_user_Riforma, cognome_Riforma, nome_Riforma"
            .", id_scorm_Riforma, punteggio_Riforma, id_user_Forma, cognome_Forma, nome_Forma"
            .", codice_fiscale_Forma, sesso_Forma, email_Forma, categoria_Forma, ap_Forma"
            .", cod_settore_Forma, settore_Forma, cod_direzione_Forma, direzione_Forma"
            .", stato, nota"
            ." FROM mdl_f2_forma2riforma_partecipazioni"
            ." WHERE id_mapping = ".$id_mapping
            ." ORDER BY id";   
    $result = $DB->get_records_sql($query);
    $numero_partecipazioni = 0;
    foreach($result as $row) {
        $numero_partecipazioni++;
        $vet_mdl_f2_forma2riforma_partecipazioni[$numero_partecipazioni] = new EML_RECmdl_f2_forma2riforma_partecipazioni();
        $vet_mdl_f2_forma2riforma_partecipazioni[$numero_partecipazioni]->id = $row->id;
        $vet_mdl_f2_forma2riforma_partecipazioni[$numero_partecipazioni]->id_mapping = $row->id_mapping;
        $vet_mdl_f2_forma2riforma_partecipazioni[$numero_partecipazioni]->matricola = $row->matricola;
        $vet_mdl_f2_forma2riforma_partecipazioni[$numero_partecipazioni]->id_user_Riforma = $row->id_user_riforma;
        $vet_mdl_f2_forma2riforma_partecipazioni[$numero_partecipazioni]->cognome_Riforma = $row->cognome_riforma;
        $vet_mdl_f2_forma2riforma_partecipazioni[$numero_partecipazioni]->nome_Riforma = $row->nome_riforma;
        $vet_mdl_f2_forma2riforma_partecipazioni[$numero_partecipazioni]->id_scorm_Riforma = $row->id_scorm_riforma;
        $vet_mdl_f2_forma2riforma_partecipazioni[$numero_partecipazioni]->punteggio_Riforma = $row->punteggio_riforma;
        $vet_mdl_f2_forma2riforma_partecipazioni[$numero_partecipazioni]->id_user_Forma = $row->id_user_forma;
        $vet_mdl_f2_forma2riforma_partecipazioni[$numero_partecipazioni]->cognome_Forma = $row->cognome_forma;
        $vet_mdl_f2_forma2riforma_partecipazioni[$numero_partecipazioni]->nome_Forma = $row->nome_forma;
        $vet_mdl_f2_forma2riforma_partecipazioni[$numero_partecipazioni]->codice_fiscale_Forma = $row->codice_fiscale_forma;
        $vet_mdl_f2_forma2riforma_partecipazioni[$numero_partecipazioni]->sesso_Forma = $row->sesso_forma;
        $vet_mdl_f2_forma2riforma_partecipazioni[$numero_partecipazioni]->email_Forma = $row->email_forma;
        $vet_mdl_f2_forma2riforma_partecipazioni[$numero_partecipazioni]->categoria_Forma = $row->categoria_forma;
        $vet_mdl_f2_forma2riforma_partecipazioni[$numero_partecipazioni]->ap_Forma = $row->ap_forma;
        $vet_mdl_f2_forma2riforma_partecipazioni[$numero_partecipazioni]->cod_settore_Forma = $row->cod_settore_forma;
        $vet_mdl_f2_forma2riforma_partecipazioni[$numero_partecipazioni]->settore_Forma = $row->settore_forma;
        $vet_mdl_f2_forma2riforma_partecipazioni[$numero_partecipazioni]->cod_direzione_Forma = $row->cod_direzione_forma;
        $vet_mdl_f2_forma2riforma_partecipazioni[$numero_partecipazioni]->direzione_Forma = $row->direzione_forma;
        $vet_mdl_f2_forma2riforma_partecipazioni[$numero_partecipazioni]->stato = $row->stato;
        $vet_mdl_f2_forma2riforma_partecipazioni[$numero_partecipazioni]->nota = $row->nota;
    }
    return $vet_mdl_f2_forma2riforma_partecipazioni;
} // function Get_mdl_f2_forma2riforma_partecipazioni
function Get_mdl_course_Forma20($id_forma20, EML_RECmdl_course $rec_mdl_course) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * Legge dalla tabella mdl_course di FORMA il record con id = $id_forma20 
 * se lo trova  valorizza il "record" $rec_mdl_course  con i campi letti
 * restituisce il numero di record trovati
 * 
 * Parametri:
 *    $id_forma20 -- Identificativo del record da cercare 
 *    $rec_mdl_course -- Record con i dati letti (mappa la tabella mdl_course)
 * 
 * Codici restituiti:
 *     0 --> dati non trovati
 *     1 --> tutto ok
 */
    global $DB;
    $result = $DB->get_record('course', array('id' => $id_forma20));
    if ($result == false) {
        return 0;
    } else { 
        $rec_mdl_course->id = $result->id;
        $rec_mdl_course->category = $result->category;
        $rec_mdl_course->sortorder = $result->sortorder;
        $rec_mdl_course->fullname = $result->fullname;
        $rec_mdl_course->shortname = $result->shortname;
        $rec_mdl_course->idnumber = $result->idnumber;
        $rec_mdl_course->summary = $result->summary;
        $rec_mdl_course->summaryformat = $result->summaryformat;
        $rec_mdl_course->format = $result->format;
        $rec_mdl_course->showgrades = $result->showgrades;
        $rec_mdl_course->modinfo = $result->modinfo;
        $rec_mdl_course->newsitems = $result->newsitems;
        $rec_mdl_course->startdate = $result->startdate;
        $rec_mdl_course->numsections = $result->numsections;
        $rec_mdl_course->marker = $result->marker;
        $rec_mdl_course->maxbytes = $result->maxbytes;
        $rec_mdl_course->legacyfiles = $result->legacyfiles;
        $rec_mdl_course->showreports = $result->showreports;
        $rec_mdl_course->visible = $result->visible;
        $rec_mdl_course->visibleold = $result->visibleold;
        $rec_mdl_course->hiddensections = $result->hiddensections;
        $rec_mdl_course->groupmode = $result->groupmode;
        $rec_mdl_course->groupmodeforce = $result->groupmodeforce;
        $rec_mdl_course->defaultgroupingid = $result->defaultgroupingid;
        $rec_mdl_course->lang = $result->lang;
        $rec_mdl_course->theme = $result->theme;
        $rec_mdl_course->timecreated = $result->timecreated;
        $rec_mdl_course->timemodified = $result->timemodified;
        $rec_mdl_course->requested = $result->requested;
        $rec_mdl_course->restrictmodules = $result->restrictmodules;
        $rec_mdl_course->enablecompletion = $result->enablecompletion;
        $rec_mdl_course->completionstartonenrol = $result->completionstartonenrol;
        $rec_mdl_course->completionnotify = $result->completionnotify;
        return 1;
    }
} //function EML_Get_mdl_course_Forma20
function Get_mdl_org($id, EML_RECmdl_org $rec_mdl_org) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * Legge dalla tabella mdl_org il record con id = $id 
 * se lo trova  valorizza il "record" $rec_mdl_org con i campi letti
 * restituisce il numero di record trovati
 * 
 * Parametri:
 *    $id -- Identificativo del record da cercare 
 *    $rec_mdl_org -- Record con i dati letti (mappa la tabella mdl_org)
 *
 * Codici restituiti:
 *     0 --> dati non trovati
 *     1 --> tutto ok
*/
    global $DB;
    $result = $DB->get_record('org', array('id' => $id));
    if ($result == false) {
        return 0;
    } else { 
        $rec_mdl_org->id = $result->id;
        $rec_mdl_org->fullname = $result->fullname;
        $rec_mdl_org->shortname = $result->shortname;
        $rec_mdl_org->description = $result->description;
        $rec_mdl_org->idnumber = $result->idnumber;
        $rec_mdl_org->frameworkid = $result->frameworkid;
        $rec_mdl_org->path = $result->path;
        $rec_mdl_org->depthid = $result->depthid;
        $rec_mdl_org->parentid = $result->parentid;
        $rec_mdl_org->sortorder = $result->sortorder;
        $rec_mdl_org->visible = $result->visible;
        $rec_mdl_org->timecreated = $result->timecreated;
        $rec_mdl_org->timemodified = $result->timemodified;
        $rec_mdl_org->usermodified = $result->usermodified;
        return 1;
    }
} // Get_mdl_org
function Get_mdl_f2_anagrafica_corsi($id, EML_RECmdl_f2_anagrafica_corsi $rec_mdl_f2_anagrafica_corsi) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * Legge dalla tabella mdl_f2_anagrafica_corsi il record con id = $id 
 * se lo trova  valorizza $rec_mdl_f2_anagrafica_corsi con i dati letti
 * restituisce il numero di record trovati
 * 
 * Parametri:
 *    $id -- Identificativo del record da cercare 
 *    $rec_mdl_f2_anagrafica_corsi -- Record con i dati letti (mappa la tabella mdl_f2_anagrafica_corsi)
 * 
 * Codici restituiti:
 *     0 --> dati non trovati
 *     1 --> tutto ok
*/
    global $DB;
    $result = $DB->get_record('f2_anagrafica_corsi', array('courseid' => $id));
    if ($result == false) {
        return 0;
    } else { 
        $rec_mdl_f2_anagrafica_corsi->id = $result->id;
        $rec_mdl_f2_anagrafica_corsi->courseid = $result->courseid;
        $rec_mdl_f2_anagrafica_corsi->cf = $result->cf;
        $rec_mdl_f2_anagrafica_corsi->course_type = $result->course_type;
        $rec_mdl_f2_anagrafica_corsi->tipo_budget = $result->tipo_budget;
        $rec_mdl_f2_anagrafica_corsi->af = $result->af;
        $rec_mdl_f2_anagrafica_corsi->subaf = $result->subaf;
        $rec_mdl_f2_anagrafica_corsi->to_x = $result->to_x;
        $rec_mdl_f2_anagrafica_corsi->flag_dir_scuola = $result->flag_dir_scuola;
        $rec_mdl_f2_anagrafica_corsi->id_dir_scuola = $result->id_dir_scuola;
        $rec_mdl_f2_anagrafica_corsi->te = $result->te;
        $rec_mdl_f2_anagrafica_corsi->sf = $result->sf;
        $rec_mdl_f2_anagrafica_corsi->orario = $result->orario;
        $rec_mdl_f2_anagrafica_corsi->viaente = $result->viaente;
        $rec_mdl_f2_anagrafica_corsi->localita = $result->localita;
        $rec_mdl_f2_anagrafica_corsi->anno = $result->anno;
        $rec_mdl_f2_anagrafica_corsi->note = $result->note;
        $rec_mdl_f2_anagrafica_corsi->determina = $result->determina;
        $rec_mdl_f2_anagrafica_corsi->costo = $result->costo;
        $rec_mdl_f2_anagrafica_corsi->durata = $result->durata;
        $rec_mdl_f2_anagrafica_corsi->num_min_all = $result->num_min_all;
        $rec_mdl_f2_anagrafica_corsi->num_norm_all = $result->num_norm_all;
        $rec_mdl_f2_anagrafica_corsi->num_max_all = $result->num_max_all;
        $rec_mdl_f2_anagrafica_corsi->dir_proponente = $result->dir_proponente;
        $rec_mdl_f2_anagrafica_corsi->timemodified = $result->timemodified;
        $rec_mdl_f2_anagrafica_corsi->usermodified = $result->usermodified;
        return 1;
    }
} // function Get_mdl_f2_anagrafica_corsi
function Get_mdl_f2_fornitori($id_fornitore, EML_RECmdl_f2_fornitori $rec_mdl_f2_fornitori) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * Legge dalla tabella mdl_f2_anagrafica_corsi il record con id = $id 
 * se lo trova  valorizza $rec_mdl_f2_anagrafica_corsi con i dati letti
 * restituisce il numero di record trovati
 * 
 * Parametri:
 *    $id -- Identificativo del record da cercare 
 *    $rec_mdl_f2_anagrafica_corsi -- Record con i dati letti (mappa la tabella mdl_f2_anagrafica_corsi)
 *
 * Codici restituiti:
 *     0 --> dati non trovati
 *     1 --> tutto ok
*/
    global $DB;
    $result = $DB->get_record('f2_fornitori', array('id' => $id_fornitore));
    if ($result == false) {
        return 0;
    } else { 
        $rec_mdl_f2_fornitori->id = $result->id;
        $rec_mdl_f2_fornitori->id_org = $result->id_org;
        $rec_mdl_f2_fornitori->denominazione = $result->denominazione;
        $rec_mdl_f2_fornitori->cognome = $result->cognome;
        $rec_mdl_f2_fornitori->nome = $result->nome;
        $rec_mdl_f2_fornitori->url = $result->url;
        $rec_mdl_f2_fornitori->partita_iva = $result->partita_iva;
        $rec_mdl_f2_fornitori->codice_fiscale = $result->codice_fiscale;
        $rec_mdl_f2_fornitori->codice_creditore = $result->codice_creditore;
        $rec_mdl_f2_fornitori->tipo_formazione = $result->tipo_formazione;
        $rec_mdl_f2_fornitori->stato = $result->stato;
        $rec_mdl_f2_fornitori->nota = $result->nota;
        $rec_mdl_f2_fornitori->indirizzo = $result->indirizzo;
        $rec_mdl_f2_fornitori->cap = $result->cap;
        $rec_mdl_f2_fornitori->citta = $result->citta;
        $rec_mdl_f2_fornitori->provincia = $result->provincia;
        $rec_mdl_f2_fornitori->paese = $result->paese;
        $rec_mdl_f2_fornitori->fax = $result->fax;
        $rec_mdl_f2_fornitori->telefono = $result->telefono;
        $rec_mdl_f2_fornitori->email = $result->email;
        $rec_mdl_f2_fornitori->preferiti = $result->preferiti;
        return 1;
    }
} // Get_mdl_f2_fornitori
function Ins_mdl_f2_storico_corsi(EML_RECmdl_f2_storico_corsi $rec_mdl_f2_storico_corsi) {
/*
* A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
* 
* Inserisce un record in tabella mdl_f2_storico_corsi valorizzando i campi con 
* quanto presente nel record ricevuto come parametro
* Restituisce l'id del record inserito
* 
* NOTA: la function valorizza il campo lstupd con la data di sistema
* 
* Parametri:
*     $rec_mdl_f2_storico_corsi -- Record con i dati da inserire (mappa parzialmente la tabella mdl_f2_storico_corsi)
*
* Codici restituiti:
*     0 --> inserimento non riuscito
*    >0 --> tutto ok
*/
    global $DB;
    $record = new stdClass();
    $record->matricola = $rec_mdl_f2_storico_corsi->matricola;
    $record->cognome = $rec_mdl_f2_storico_corsi->cognome;
    $record->nome = $rec_mdl_f2_storico_corsi->nome;
    $record->sesso = $rec_mdl_f2_storico_corsi->sesso;
    $record->categoria = $rec_mdl_f2_storico_corsi->categoria;
    $record->ap = $rec_mdl_f2_storico_corsi->ap;
    $record->e_mail = $rec_mdl_f2_storico_corsi->e_mail;
    $record->cod_direzione = $rec_mdl_f2_storico_corsi->cod_direzione;
    $record->direzione = $rec_mdl_f2_storico_corsi->direzione;
    $record->cod_settore = $rec_mdl_f2_storico_corsi->cod_settore;
    $record->settore = $rec_mdl_f2_storico_corsi->settore;
    $record->codcorso = $rec_mdl_f2_storico_corsi->codcorso;
    $record->tipo_corso = $rec_mdl_f2_storico_corsi->tipo_corso;
    $record->data_inizio = $rec_mdl_f2_storico_corsi->data_inizio;
    $record->costo = $rec_mdl_f2_storico_corsi->costo;
    $record->af = $rec_mdl_f2_storico_corsi->af;
    $record->to_x = $rec_mdl_f2_storico_corsi->to_x;
    $record->orario = $rec_mdl_f2_storico_corsi->orario;
    $record->titolo = $rec_mdl_f2_storico_corsi->titolo;
    $record->durata = $rec_mdl_f2_storico_corsi->durata;
    $record->scuola_ente = $rec_mdl_f2_storico_corsi->scuola_ente;
    $record->note = $rec_mdl_f2_storico_corsi->note;
    $record->presenza = $rec_mdl_f2_storico_corsi->presenza;
    $record->codpart = $rec_mdl_f2_storico_corsi->codpart;
    $record->descrpart = $rec_mdl_f2_storico_corsi->descrpart;
    $record->sub_af = $rec_mdl_f2_storico_corsi->sub_af;
    $record->cfv = $rec_mdl_f2_storico_corsi->cfv;
    $record->va = $rec_mdl_f2_storico_corsi->va;
    $record->cf = $rec_mdl_f2_storico_corsi->cf;
    $record->te = $rec_mdl_f2_storico_corsi->te;
    $record->sf = $rec_mdl_f2_storico_corsi->sf;
    $record->lstupd = time();
    $lastinsertid = $DB->insert_record('f2_storico_corsi', $record, true);
    return $lastinsertid;
} // Ins_mdl_f2_storico_corsi
function Ins_mdl_f2_forma2riforma_mapping(EML_RECmdl_f2_forma2riforma_mapping $rec_mdl_f2_forma2riforma_mapping) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * Inserisce un record in tabella mdl_f2_forma2riforma_mapping valorizzando i campi con 
 * quanto presente nel record ricevuto come parametro
 * 
 * Parametri:
 *     $rec_mdl_f2_forma2riforma_mapping -- Record con i dati da inserire (mappa la tabella mdl_f2_forma2riforma_mapping)
 * 
 * Codici restituiti:
 *      0 --> errori in inserimento
 *     >0 --> tutto ok
*/
    global $DB;
    $record = new stdClass();
    $record->shortname = $rec_mdl_f2_forma2riforma_mapping->shortname;
    $record->id_riforma = $rec_mdl_f2_forma2riforma_mapping->id_riforma;
    $record->id_forma20 = $rec_mdl_f2_forma2riforma_mapping->id_forma20;
    $record->perc_x_cfv = $rec_mdl_f2_forma2riforma_mapping->perc_x_cfv;
    $record->va_default = $rec_mdl_f2_forma2riforma_mapping->va_default;
    $record->data_inizio = $rec_mdl_f2_forma2riforma_mapping->data_inizio;
    $record->stato = $rec_mdl_f2_forma2riforma_mapping->stato;
    $record->nota = $rec_mdl_f2_forma2riforma_mapping->nota;
    $lastinsertid = $DB->insert_record('f2_forma2riforma_mapping', $record, true);
    return $lastinsertid; 
} //Ins_mdl_f2_forma2riforma_mapping
function Ins_mdl_f2_forma2riforma_partecipazioni(EML_RECmdl_f2_forma2riforma_partecipazioni $rec_mdl_f2_forma2riforma_partecipazioni) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * Inserisce un record in tabella mdl_f2_forma2riforma_partecipazioni valorizzando i campi con 
 * quanto presente nel record ricevuto come parametro
 * 
 * Parametri:
 *     $rec_mdl_f2_forma2riforma_partecipazioni -- Record con i dati da inserire (mappa la tabella mdl_f2_forma2riforma_partecipazioni)
 *
 * Codici restituiti:
 *     Identificativo dell'ultimo record inserito in base dati
*/
    global $DB;
    $record = new stdClass();
    $record->id_mapping = $rec_mdl_f2_forma2riforma_partecipazioni->id_mapping;
    $record->matricola = $rec_mdl_f2_forma2riforma_partecipazioni->matricola;
    $record->id_user_Riforma = $rec_mdl_f2_forma2riforma_partecipazioni->id_user_Riforma;
    $record->cognome_Riforma = $rec_mdl_f2_forma2riforma_partecipazioni->cognome_Riforma;
    $record->nome_Riforma = $rec_mdl_f2_forma2riforma_partecipazioni->nome_Riforma;
    $record->id_scorm_Riforma = $rec_mdl_f2_forma2riforma_partecipazioni->id_scorm_Riforma;
    $record->punteggio_Riforma = $rec_mdl_f2_forma2riforma_partecipazioni->punteggio_Riforma;
    $record->id_user_Forma = $rec_mdl_f2_forma2riforma_partecipazioni->id_user_Forma;
    $record->cognome_Forma = $rec_mdl_f2_forma2riforma_partecipazioni->cognome_Forma;
    $record->nome_Forma = $rec_mdl_f2_forma2riforma_partecipazioni->nome_Forma;
    $record->codice_fiscale_Forma = $rec_mdl_f2_forma2riforma_partecipazioni->codice_fiscale_Forma;
    $record->sesso_Forma = $rec_mdl_f2_forma2riforma_partecipazioni->sesso_Forma;
    $record->email_Forma = $rec_mdl_f2_forma2riforma_partecipazioni->email_Forma;
    $record->categoria_Forma = $rec_mdl_f2_forma2riforma_partecipazioni->categoria_Forma;
    $record->ap_Forma = $rec_mdl_f2_forma2riforma_partecipazioni->ap_Forma;
    $record->cod_settore_Forma = $rec_mdl_f2_forma2riforma_partecipazioni->cod_settore_Forma;
    $record->settore_Forma = $rec_mdl_f2_forma2riforma_partecipazioni->settore_Forma;
    $record->cod_direzione_Forma = $rec_mdl_f2_forma2riforma_partecipazioni->cod_direzione_Forma;
    $record->direzione_Forma = $rec_mdl_f2_forma2riforma_partecipazioni->direzione_Forma;
    $record->stato = $rec_mdl_f2_forma2riforma_partecipazioni->stato;
    $record->nota = $rec_mdl_f2_forma2riforma_partecipazioni->nota;
    $lastinsertid = $DB->insert_record('f2_forma2riforma_partecipazioni', $record, true);
    return $lastinsertid;
} // Ins_mdl_f2_forma2riforma_partecipazioni
/*
 * Function che non sono allineate agli standard Moodle (usano la base dati di Riforma)
 *     Get_elenco_scorm_Riforma_1
 *     Get_mdl_course_Riforma
 *     Get mdl_scorm_Riforma_1
 *     Get_mdl_scorm_scoes_track_and_user_Riforma
*/
function Get_elenco_scorm_Riforma_1($id_riforma, &$numero_record) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * Estrae (da Riforma) l'elenco degli Scorm associati ad un id_corso (in RIFORMA)
 * 
 * Principali passi eseguiti:
 *    - Lettura dalla tabella mdl_scorm dei record con course = $id_riforma
 *    - Loop sui record letti
 *        - incremento il numero record letti
 *        - valorizzo un nuovo record del vettore (di output) con i dati letti
 *    - fine loop
 *    - restituisco il vettore con i dati letti
 * 
 * Restituisce:
 *     $elenco_scorm_select -- array con i dati letti dal db
 * 
 * Parametri
 *     &$numero_record -- numero di record presenti in $vet_elenco_scorm
 * 
 * NOTA IMPORTANTE: in ambiente di sviluppo le foreach non funzionano, restituiscono oggetti vuoti
 *  devono essere sostituite da cicli while($row = $result->fetch_object()) 
*/
    global $mysqli_Riforma;
    $elenco_scorm_select = array();
    //$query = "select id as scorm_id, name as scorm_name from mdl_scorm where course = ".$id_riforma." order by id desc";
    //$query = "select id as scorm_id, name as scorm_name from mdl_scorm where course = ".$mysqli_Riforma->quote($id_riforma)." order by id desc";
    $sth = $mysqli_Riforma->prepare('select id as scorm_id, name as scorm_name from mdl_scorm where course = :id order by id desc');
    $sth->bindParam(':id', $id_riforma, PDO::PARAM_INT);
    $sth->execute();
    $result = $sth->fetchAll();
    //echo"<pre>id: ".$id_riforma." - res: ".var_dump($result)."</pre>";
    //$result = $mysqli_Riforma->query($query);
    $numero_record = 0;
    foreach($result as $row) {
    //while($row = $result->fetch_object()){
        $elenco_scorm_select[$row['scorm_id']] = $row['scorm_name'];
        $numero_record++;
    };
    return $elenco_scorm_select;
} // Get_elenco_scorm_Riforma_1
function Get_mdl_course_Riforma($shortname, EML_RECmdl_course $rec_mdl_course) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * Cerca nella tabella mdl_course di RIFORMA il record con shortname = $shortname 
 *     se lo trova  valorizza il "record" $rec_mdl_course con i campi letti
 * restituisce il numero di record trovati
 * 
 * Principali passi eseguiti:
 *    - Inizializzazione a NULL del record di output
 *    - Lettura dalla tabella mdl_course del record con shortname = $shortname
 *    - Se errori in lettura
 *        - esco con -Error Number di MySql
 *    - Se non trovato nessun record
 *        - uscita con codice 0
 *    - altrimenti
 *        - valorizzo $rec_mdl_course
 *        - uscita con codice 1
 *    - fine se
 * 
 * Parametri:
 *    $shortname -- Shortname del record da cercare 
 *    $rec_mdl_course -- Record con i dati letti (mappa la tabella mdl_course)
 * 
 * Codici restituiti:
 *    <0 --> errori (Error Number MySql cambiato di segno)
 *     0 --> dati non trovati
 *     1 --> tutto ok
 * 
 * NOTA IMPORTANTE: questa function valorizza unicamente i campi id e startdate. 
 */
    global $mysqli_Riforma;
    // Inizializzo a NULL il record di output
    $rec_mdl_course->id = NULL;
    $rec_mdl_course->startdate = NULL;
    // Effettuo la query
    //$query = "SELECT id, startdate FROM mdl_course WHERE shortname = '".$shortname."'";
    $array_shortname = split("_", $shortname);
    //$sn = $mysqli_Riforma->quote($array_shortname[0]);
    $sn = $array_shortname[0];
    $sth = $mysqli_Riforma->prepare('SELECT id, startdate FROM mdl_course WHERE shortname like ?');
    $sth->bindParam(1, $sn, PDO::PARAM_STR, 12);
    $sth->execute();
    $result = $sth->fetchAll();
    
    /*echo "<pre>sh: ".var_dump($sn)."</pre>";
    echo "<pre>result: ".var_dump($result)."</pre>";
    exit();*/
    
    //$res = $mysqli_Riforma->query($query);
    if($mysqli_Riforma->errorCode) {
        // errori nella Select: restituisco l'error code PDO cambiato di segno
        return $mysqli_Riforma->errorCode;
    }
    $num_elements = count($result);
    if ($num_elements >= 1) {
        // trovato i dati: valorizzo il record di output ed esco con il numero record trovati
        //$row = $res->fetch_assoc();
        $rec_mdl_course->id = $result[0]['id'];
        $rec_mdl_course->startdate = $result[0]['startdate'];
        return 1;
    } else {
        // non trovato i dati: esco con 0
        return 0;
    }  
} //function Get_mdl_course_Riforma
function Get_mdl_scorm_Riforma($id, EML_RECmdl_scorm $rec_mdl_scorm) {
 /*
 * A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * Cerca nella tabella mdl_scorm di RIFORMA il record con id = $id 
 *     se lo trova  valorizza il "record" $rec_mdl_scorm con i campi letti
 * restituisce il numero di record trovati
 * 
 * Principali passi eseguiti:
 *    - Inizializzazione a NULL del record di output
 *    - Lettura dalla tabella mdl_course del record con shortname = $shortname
 *    - Se errori in lettura
 *        - esco con -Error Number di MySql
 *    - Se non trovato nessun record
 *        - uscita con codice 0
 *    - altrimenti
 *        - valorizzo $rec_mdl_scorm
 *        - uscita con codice 1
 *    - fine se
 * 
 * Parametri:
 *    $id. Identificativo del record da cercare 
 *    $rec_mdl_scorm. Record con i dati letti (mappa la tabella mdl_scorm)
 * 
 * Codici restituiti:
 *    <0 --> errori (Error Number MySql cambiato di segno)
 *     0 --> dati non trovati
 *     1 --> tutto ok
 * 
 * NOTA IMPORTANTE: questa function valorizza unicamente il campo name. 
 */
    global $mysqli_Riforma;
    // Inizializzo a NULL il record di output
    $rec_mdl_scorm->name = NULL;
    // Effettuo la query
    //$query = "SELECT name FROM mdl_scorm WHERE id = ".$id;
    $sth = $mysqli_Riforma->prepare('SELECT name FROM mdl_scorm WHERE id = ?');
    $sth->bindParam(1, $id, PDO::PARAM_INT);
    $sth->execute();
    $result = $sth->fetchAll();
    //echo "<pre>result: ".var_dump($result)."</pre>";
    //$res = $mysqli_Riforma->query($query);
    if($mysqli_Riforma->errorCode) {
        // errori nella Select: restituisco l'error code PDO
        return $mysqli_Riforma->errorCode;
    }
    $num_elements = count($result);
    if ($num_elements >= 1) {
        // trovato i dati: valorizzo il record di output ed esco con il numero record trovati
        //$row = $res->fetch_assoc();
        //echo "<pre>nome: ".var_dump($result[0]['name'])."</pre>";
        $rec_mdl_scorm->name = $result[0]['name'];
        return 1;
    } else {
        // non trovato i dati: esco con 0
        return 0;
    }  
} // function Get_mdl_scorm_Riforma
function Get_mdl_scorm_scoes_track_and_user_Riforma($scormid, &$numero_record) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * Legge dalla tabella (di Riforma) mdl_scorm_scoes_track i record con 
 *     scormid = $scormid AND element = 'cmi.core.score.raw' 
 * Legge inoltre alcuni dati dalla tabella mdl_user
 *     (campi di Join mdl_scorm_scoes_track.userid = mdl_user.id)
 * 
 * Principali passi eseguiti:
 *    - Preparazione della query e lettura dal data base
 *    - Loop sui record letti
 *        - incremento il nomero record letti
 *        - valorizzo un nuovo record del vettore (di output) con i dati letti
 *    - fine loop
 *    - restituisco il vettore con i dati letti
 * 
 * Restituisce:
 *     $vet_mdl_scorm_scoes_track_and_user -- array globale di tipo EML_RECmdl_scorm_scoes_track_and_user con i dati letti dal db
 * 
 * Parametri:
 *     &$numero_record -- numero di record letti
 * 
 * NOTA IMPORTANTE: in ambiente di sviluppo le foreach non funzionano, restituiscono oggetti vuoti
 *  devono essere sostituite da cicli while($row = $result->fetch_object()) 
*/
    global $mysqli_Riforma;
    $vet_mdl_scorm_scoes_track_and_user = array();
    $query = "SELECT A.id as scoes_trackid, A.userid, A.scormid, A.scoid, A.attempt, A.element, A.value, A.timemodified"
            .", B.username, B.firstname, B.lastname, B.institution "
            ." FROM mdl_scorm_scoes_track A JOIN mdl_user B ON A.userid = B.id"
            ." WHERE A.scormid = ".$scormid
            ." AND A.element = 'cmi.core.score.raw'";
    $result = $mysqli_Riforma->query($query);
    
    $query = 'SELECT A.id as scoes_trackid, A.userid, A.scormid, A.scoid, A.attempt, A.element, A.value, A.timemodified'
            .', B.username, B.firstname, B.lastname, B.institution '
            .' FROM mdl_scorm_scoes_track A JOIN mdl_user B ON A.userid = B.id'
            .' WHERE A.scormid = :scormid  AND A.element = :cmi';
    $sth = $mysqli_Riforma->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
    $sth->execute(array(':scormid' => $scormid, ':cmi' => 'cmi.core.score.raw'));
    $result = $sth->fetchAll();
    $numero_record = 0;
    foreach($result as $row) {
        $numero_record++;
        $vet_mdl_scorm_scoes_track_and_user[$numero_record] = new EML_RECmdl_scorm_scoes_track_and_user();
        $vet_mdl_scorm_scoes_track_and_user[$numero_record]->scoes_trackid = $row['scoes_trackid'];
        $vet_mdl_scorm_scoes_track_and_user[$numero_record]->userid = $row['userid'];
        $vet_mdl_scorm_scoes_track_and_user[$numero_record]->scormid = $row['scormid'];
        $vet_mdl_scorm_scoes_track_and_user[$numero_record]->scoid = $row['scoid'];
        $vet_mdl_scorm_scoes_track_and_user[$numero_record]->attempt = $row['attempt'];
        $vet_mdl_scorm_scoes_track_and_user[$numero_record]->element = $row['element'];
        $vet_mdl_scorm_scoes_track_and_user[$numero_record]->value = $row['value'];
        $vet_mdl_scorm_scoes_track_and_user[$numero_record]->timemodified = $row['timemodified'];
        $vet_mdl_scorm_scoes_track_and_user[$numero_record]->username = $row['username'];
        $vet_mdl_scorm_scoes_track_and_user[$numero_record]->firstname = $row['firstname'];
        $vet_mdl_scorm_scoes_track_and_user[$numero_record]->lastname = $row['lastname'];
    };
    /*while($row = $result->fetch_object()){
        $numero_record++;
        $vet_mdl_scorm_scoes_track_and_user[$numero_record] = new EML_RECmdl_scorm_scoes_track_and_user();
        $vet_mdl_scorm_scoes_track_and_user[$numero_record]->scoes_trackid = $row->scoes_trackid;
        $vet_mdl_scorm_scoes_track_and_user[$numero_record]->userid = $row->userid;
        $vet_mdl_scorm_scoes_track_and_user[$numero_record]->scormid = $row->scormid;
        $vet_mdl_scorm_scoes_track_and_user[$numero_record]->scoid = $row->scoid;
        $vet_mdl_scorm_scoes_track_and_user[$numero_record]->attempt = $row->attempt;
        $vet_mdl_scorm_scoes_track_and_user[$numero_record]->element = $row->element;
        $vet_mdl_scorm_scoes_track_and_user[$numero_record]->value = $row->value;
        $vet_mdl_scorm_scoes_track_and_user[$numero_record]->timemodified = $row->timemodified;
        $vet_mdl_scorm_scoes_track_and_user[$numero_record]->username = $row->username;
        $vet_mdl_scorm_scoes_track_and_user[$numero_record]->firstname = $row->firstname;
        $vet_mdl_scorm_scoes_track_and_user[$numero_record]->lastname = $row->lastname;
    };*/
    return $vet_mdl_scorm_scoes_track_and_user;
} // Get_mdl_scorm_scoes_track_and_user_Riforma
function Get_elenco_corsi_collegabili_1(&$numero_corsi) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * Estrae l'elenco dei corsi di FORMA che possono essere collegati a corsi di Riforma.
 * 
 * Condizioni perché un corso possa essere collegato da FORMA a Riforma:
 *     - In FORMA deve esistere come corso Obiettivo senza edizioni
 *     - In FORMA non deve essere presente in tabella mdl_f2_forma2riforma_mapping (*)
 *     - In Riforma deve esistere il corso (*)
 * (*) il campo usato per il Join è shortname
 * 
 * Restituisce:
 *     $elenco_corsi_select - array da usare nella form (dati del campo select)
 * 
 * Parametri
 *     &$numero_corsi - numero di record presenti in $elenco_corsi_select
*/
    global $DB;
    global $mysqli_Riforma;
    //echo "<pre>mysqli 1: ".var_dump($mysqli_Riforma)."</pre>";
    //echo "<pre>DB 1: ".$DB."</pre>";die;
    $elenco_corsi_select = array();
    // query su Forma per ottenere l'elenco dei
    //     Corsi Obiettivo, senza edizioni e non presenti in mdl_f2_forma2riforma_mapping
    $query = "SELECT B.id AS id_forma20, B.fullname as titolo, B.shortname as shortname"
            ." FROM mdl_f2_anagrafica_corsi A"
            ." JOIN mdl_course B ON A.courseid = B.id"
            ." JOIN mdl_facetoface C ON B.id = C.course"
            ." LEFT JOIN mdl_facetoface_sessions D ON C.id = D.facetoface"
            ." WHERE A.course_type = 1"
            ." AND D.id IS NULL"
            ." AND B.shortname NOT IN (SELECT shortname FROM mdl_f2_forma2riforma_mapping)"
            ." ORDER BY B.shortname";
    $result = $DB->get_records_sql($query);
    $numero_corsi = 0;
    $elenco_corsi_select = array();
    // PDO prepare
    $sth = $mysqli_Riforma->prepare('SELECT id, startdate FROM mdl_course WHERE shortname = :name');
    foreach($result as $row) {
        // Verifico se il corso � presente in Riforma
        $codice_corso = $row->shortname;
        $array_shortname = split("_", $codice_corso);
        $sn = $array_shortname[0];
        //echo "<pre>SH: ".$sn."</pre>";
        //$query = "SELECT id, startdate FROM mdl_course WHERE shortname = '".$shortname."'";
        $sth->bindParam(':name', $sn, PDO::PARAM_STR); 
        $sth->execute();
        
        //echo "\nPDOStatement::errorInfo(): "; print $mysqli_Riforma->errorInfo();

        $result1 = $sth->fetchAll();
        //print_r($result1);
        $num_elements = count($result1);
        //echo "<pre>Elements: ".$num_elements."</pre>";
        /*echo "<pre>".$query."</pre>";
        echo "<pre>mysqli: ".var_dump($mysqli_Riforma)."</pre>";*/
        //$result1 = $mysqli_Riforma->query($query);
        //if ($result1->num_rows == 1) {
        if ($num_elements == 1) {
            // corso presente in Riforma aggiorno i parametri di output
            $numero_corsi++;
            $elenco_corsi_select[$row->id_forma20] = $row->titolo;
        }
    }
    //exit;
    return $elenco_corsi_select;
} // GetElenco_corsi_collegabili_1
?>