<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - aprile 2015
 * 
 * Funzioni di accesso al db
 * 
 *  NOTA IMPORTANTE: 
 *      La DB->get_records_sql (per motivi sconosciuti) restituisce i nomi dei campi TUTTI IN MINUSCOLO
 *      ad esempio bisogna usare $row->id_user_riforma e non $row->id_user_Riforma,. ecc.
 * 
 * Function presenti:
 *      EML_Get_elenco_corsi_in_gestione
 *      EML_Get_elenco_corsi_inseribili
 *      EML_Get_elenco_risorse_punteggio
 *      EML_Get_mdl_course
 *      EML_Get_tbl_eml_grfo_feed_back
 *      EML_Get_tbl_eml_pent_edizioni_corsi_on_line
 *      EML_Get_tbl_eml_pent_moduli_corsi_on_line
 *      EML_Ins_tbl_eml_grfo_feed_back
 *      EML_Ins_tbl_eml_grfo_log
 *      EML_Ins_tbl_eml_pent_edizioni_corsi_on_line
 *      EML_Ins_tbl_eml_pent_moduli_corsi_on_line
 *      EML_Pulisci_log
 *      EML_Upd_xxx
*/
function EML_Get_elenco_corsi_in_gestione (&$numero_corsi) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - aprile 2015
 * 
 * Estrae l'elenco dei corsi in gestione
 * 
 * Restituisce:
 *     $corsi_in_gestione - array globale di tipo EML_Corsi_in_gestione con i dati letti dal db
 * 
 * Parametri
 *     &$numero_corsi - numero di record presenti in $corsi_in_gestione
*/
    global $DB;
    $corsi_in_gestione = array();
    $query = "SELECT"
            ." A.id_corso as id_corso, A.cod_corso as cod_corso, A.titolo_corso as titolo_corso,"
            ." (SELECT COUNT(B.id_corso) FROM tbl_eml_pent_moduli_corsi_on_line B"
            ."  WHERE B.cod_corso = A.cod_corso AND B.posizione_in_report > 0"
            ." ) as moduli_monitorati,"		
            ." (SELECT COUNT(C.id_corso) FROM tbl_eml_pent_edizioni_corsi_on_line C"
            ."  WHERE C.cod_corso = A.cod_corso AND C.flag_monitorata_S_N = 'S'"
            ." ) as edizioni_monitorate,"
            ." D.enablecompletion as enablecompletion"
            ." FROM tbl_eml_pent_moduli_corsi_on_line A"
            ." JOIN mdl_course D ON D.id = A.id_corso"
            ." GROUP BY A.cod_corso, A.titolo_corso"
            ." ORDER BY A.cod_corso, A.titolo_corso";
    $result = $DB->get_records_sql($query);
    $numero_corsi = 0;
    foreach($result as $row) {
        $numero_corsi++;
        $corsi_in_gestione[$numero_corsi] = new EML_Corsi_in_gestione();
        $corsi_in_gestione[$numero_corsi]->id_corso = $row->id_corso;
        $corsi_in_gestione[$numero_corsi]->cod_corso = $row->cod_corso;
        $corsi_in_gestione[$numero_corsi]->titolo_corso = $row->titolo_corso;
        $corsi_in_gestione[$numero_corsi]->moduli_monitorati = $row->moduli_monitorati;
        $corsi_in_gestione[$numero_corsi]->edizioni_monitorate = $row->edizioni_monitorate;
        $enablecompletion = $row->enablecompletion;
        if ($enablecompletion == 1) {
            $corsi_in_gestione[$numero_corsi]->tracciato_completamento = EML_PENT_TRACCIATO_COMPLETAMENTO;
        } else {
            $corsi_in_gestione[$numero_corsi]->tracciato_completamento = EML_PENT_NON_TRACCIATO_COMPLETAMENTO;
        }
    }
    return $corsi_in_gestione;
} // EML_Get_elenco_corsi_in_gestione
function EML_Get_elenco_corsi_inseribili(&$numero_corsi) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - dicembre 2014
 * 
 * Estrae l'elenco dei corsi on-line che possono essere monitorati
 * 
 * Condizioni perché un corso possa essere monitorato:
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
    $elenco_corsi_select = array();
    $query = "("
            ." SELECT DISTINCT"
            ."       A.id as id_corso, left(concat(A.idnumber, ' - ', A.fullname), 150) as nome_corso"
            ." FROM  mdl_course A"
            ."  JOIN mdl_f2_anagrafica_corsi B ON A.id = B.courseid"
            ."  JOIN mdl_facetoface C ON A.id = C.course"
            ."  JOIN mdl_facetoface_sessions D ON C.id = D.facetoface"
            ."  JOIN mdl_facetoface_sessions_dates E ON D.id = E.sessionid"
            ." WHERE A.id NOT IN (SELECT DISTINCT id_corso FROM tbl_eml_pent_moduli_corsi_on_line)"
            ."   AND B.course_type = 1"
            ."   AND E.timefinish >= (SELECT F.val_int FROM mdl_f2_parametri F WHERE F.id = 'p_grfo_data_inizio_monitoraggio')"
            .")"
            ." UNION"
            ." ("
            ." SELECT DISTINCT"          
            ."       A.id as id_corso, left(concat(A.idnumber, ' - ', A.fullname), 150) as nome_corso"
            ." FROM  mdl_course A"
            ."  JOIN mdl_f2_anagrafica_corsi B ON A.id = B.courseid"
            ."  JOIN mdl_facetoface C ON A.id = C.course"
            ."  JOIN mdl_facetoface_sessions D ON C.id = D.facetoface"
            ."  JOIN mdl_facetoface_sessions_dates E ON D.id = E.sessionid"
            ." WHERE A.id NOT IN (SELECT DISTINCT id_corso FROM tbl_eml_pent_moduli_corsi_on_line)"
            ."   AND B.course_type = 2"
            ."   AND A.idnumber not like 'E%'"
            ."   AND E.timefinish >= (SELECT F.val_int FROM mdl_f2_parametri F WHERE F.id = 'p_grfo_data_inizio_monitoraggio')"
            .")"
            ." ORDER BY nome_corso";
    $result = $DB->get_records_sql($query);
    $numero_corsi = 0;
    //$elenco_corsi_select = array();
    foreach($result as $row) {
        $numero_corsi++;
        $elenco_corsi_select[$row->id_corso] = $row->nome_corso;
    }
    return $elenco_corsi_select;
} // EML_Get_elenco_corsi_inseribili
function EML_Get_elenco_risorse_punteggio($id_corso, &$numero_risorse_punteggio, &$risorsa_default) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - aprile 2015
 * 
 * Estrae dalla tabella tbl_eml_pent_moduli_corsi_on_line l'elenco delle risorse
 * (di un corso ricevuto come parametro) utilizzabili per il punteggio finale
 * 
 * Le risorse utilizzabili per il punteggio finale sono quelle di tipo: 'quiz', 'scorm', 'assignment'
 * 
 * Restituisce:
 *     $elenco_risorse_punteggio - array da usare nella form (dati del campo select)
 * 
 * Parametri
 *     $id_corso - (inputo) identificativo del corso da elaborare
 *     &$numero_risorse_punteggio - numero di record presenti in $elenco_risorse_punteggio
 *     &$risorsa_default - identificativo della risorsa selezionata
*/
    global $DB;
    $elenco_risorse_punteggio = array();
    $query = "SELECT id_modulo, tipo_modulo, nome_modulo, flag_punteggio_finale"
            ." FROM tbl_eml_pent_moduli_corsi_on_line"
            ." WHERE id_corso = ".$id_corso
            ." AND tipo_modulo IN ('quiz', 'scorm', 'assignment')"
            ." ORDER BY progressivo desc";
    $result = $DB->get_records_sql($query);
    $numero_risorse_punteggio = 0;
    //$elenco_risorse_punteggio = array();
    foreach($result as $row) {
        $numero_risorse_punteggio++;
        $aus = $row->tipo_modulo." - ".$row->nome_modulo;
        $elenco_risorse_punteggio[$row->id_modulo] = $aus;
        if ($row->flag_punteggio_finale == 1) {
            $risorsa_default = $row->id_modulo;
        }
    }
    return $elenco_risorse_punteggio;    
} // EML_Get_elenco_risorse_punteggio
function EML_Get_mdl_course($id, EML_RECmdl_course $rec_mdl_course) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - dicembre 2014
 * 
 * Legge dalla tabella mdl_course il record con id = $id 
 * se lo trova  valorizza il "record" $rec_mdl_course  con i campi letti
 * restituisce il numero di record trovati
 * 
 * Parametri:
 *    $id -- Identificativo del record da cercare 
 *    $rec_mdl_course -- Record con i dati letti (mappa la tabella mdl_course)
 * 
 * Codici restituiti:
 *     0 --> dati non trovati
 *     1 --> tutto ok
 */
    global $DB;
    $result = $DB->get_record('course', array('id' => $id));
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
} //function EML_Get_mdl_course
function EML_Get_tbl_eml_grfo_feed_back($id, EML_RECtbl_eml_grfo_feed_back $rec_tbl_eml_grfo_feed_back) {
/*
* A. Albertin, G. Mandarà - CSI Piemonte - dicembre 2014
* 
* Legge dalla tabella tbl_eml_grfo_feed_back il record con id = parametro in ingresso
* valorizzando i campi con quanto presente nel record ricevuto come parametro
* 
* Parametri:
*     $id -- Id del record da leggere
*     $rec_tbl_eml_grfo_feed_back -- Record con i dati letti (mappa la tabella tbl_eml_grfo_feed_back)
*
* Codici restituiti:
*   < 0 => error code della SELECT cambiato di segno (se operazione andata male)
*   > 0 => id del record inserito  
*/
    global $mysqli;
    $query = " SELECT"
            ." id, id_corso, cod_corso, titolo_corso"
            .", operazione, stato, url, flag_parametro_id_corso, nota"
            ." FROM tbl_eml_grfo_feed_back"
            ." WHERE id = ".$id;
    $res = $mysqli->query($query);
    if (!$res) {
        $ret_code = -$mysqli->errno;
    } else {
        $ret_code = 1;
        $row = $res->fetch_assoc();
        $rec_tbl_eml_grfo_feed_back->id = $row['id'];
        $rec_tbl_eml_grfo_feed_back->id_corso = $row['id_corso'];
        $rec_tbl_eml_grfo_feed_back->cod_corso = $row['cod_corso'];
        $rec_tbl_eml_grfo_feed_back->titolo_corso = $row['titolo_corso'];
        $rec_tbl_eml_grfo_feed_back->operazione = $row['operazione'];
        $rec_tbl_eml_grfo_feed_back->stato = $row['stato'];
        $rec_tbl_eml_grfo_feed_back->url = $row['url'];
        $rec_tbl_eml_grfo_feed_back->flag_parametro_id_corso = $row['flag_parametro_id_corso'];
        $rec_tbl_eml_grfo_feed_back->nota = $row['nota'];
    }
    return $ret_code;
} //EML_Get_tbl_eml_grfo__feed_back
function  EML_Get_tbl_eml_pent_edizioni_corsi_on_line($id_corso, &$numero_edizioni) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - dicembre 2014
 * 
 * Estrae dalla tabella tbl_eml_pent_edizioni_corsi_on_line le edizioni corso associate
 * al corso con id_corso ricevuto come parametro
 * 
 * Restituisce:
 *     $elenco_edizioni - vettore di record con i dati letti
 * 
 * Parametri
 *     $id_corso - identificativo del corso da cercare
 *     &$numero_edizioni - numero di record presenti in $elenco_edizioni
 * 
 * NOTA: l'ordine con cui sono estratti i dati dalla tabella è volutamente "anomalo"
 *          per evitare il messaggio di warning di Moodle che vuole come primo campo
 *          di una select un campo "non duplicato"
*/
    global $DB;
    $query = " SELECT"
            ."          id_edizione, edizione, data_inizio, flag_monitorata_S_N"
            .",         cod_corso, titolo_corso"
            ." FROM     tbl_eml_pent_edizioni_corsi_on_line"
            ." WHERE    id_corso = ".$id_corso
            ." ORDER BY data_inizio";
    $result = $DB->get_records_sql($query);
    $numero_edizioni = 0;
    $elenco_edizioni = array();
    foreach($result as $row) {
        $numero_edizioni++;
        $elenco_edizioni[$numero_edizioni] = new EML_RECtbl_eml_pent_edizioni_corsi_on_line();
        $elenco_edizioni[$numero_edizioni]->id_corso = $id_corso;
        $elenco_edizioni[$numero_edizioni]->cod_corso = $row->cod_corso;
        $elenco_edizioni[$numero_edizioni]->titolo_corso = $row->titolo_corso;
        $elenco_edizioni[$numero_edizioni]->id_edizione = $row->id_edizione;
        $elenco_edizioni[$numero_edizioni]->edizione = $row->edizione;
        $elenco_edizioni[$numero_edizioni]->data_inizio = $row->data_inizio;
        $elenco_edizioni[$numero_edizioni]->flag_monitorata_S_N = $row->flag_monitorata_s_n;
    }
    return $elenco_edizioni;
} //EML_Get_tbl_eml_pent_edizioni_corsi_on_line
function  EML_Get_tbl_eml_pent_moduli_corsi_on_line($id_corso, &$numero_moduli) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - dicembre 2014
 * 
 * Estrae dalla tabella tbl_eml_pent_moduli_corsi_on_line le edizioni corso associate
 * al corso con id_corso ricevuto come parametro
 * 
 * Restituisce:
 *     $elenco_moduli - vettore di record con i dati letti
 * 
 * Parametri
 *     $id_corso - identificativo del corso da cercare
 *     &$numero_moduli - numero di record presenti in $elenco_moduli
 * 
 * NOTA: l'ordine con cui sono estratti i dati dalla tabella è volutamente "anomalo"
 *          per evitare il messaggio di warning di Moodle che vuole come primo campo
 *          di una select un campo "non duplicato"
*/
    global $DB;
    $query = " SELECT"
            ."          id_modulo, tipo_modulo, istanza_modulo, nome_modulo"
            .",         cod_corso, titolo_corso, progressivo"
            .",         visibile, monitorabile, posizione_in_report"
            ." FROM     tbl_eml_pent_moduli_corsi_on_line"
            ." WHERE    id_corso = ".$id_corso
            ." ORDER BY progressivo";
    $result = $DB->get_records_sql($query);
    $numero_moduli = 0;
    $elenco_moduli = array();
    foreach($result as $row) {
        $numero_moduli++;
        $elenco_moduli[$numero_moduli] = new EML_RECtbl_eml_pent_moduli_corsi_on_line();
        $elenco_moduli[$numero_moduli]->id_corso = $id_corso;
        $elenco_moduli[$numero_moduli]->cod_corso = $row->cod_corso;
        $elenco_moduli[$numero_moduli]->titolo_corso = $row->titolo_corso;
        $elenco_moduli[$numero_moduli]->progressivo = $row->progressivo;
        $elenco_moduli[$numero_moduli]->id_modulo = $row->id_modulo;
        $elenco_moduli[$numero_moduli]->tipo_modulo = $row->tipo_modulo;
        $elenco_moduli[$numero_moduli]->istanza_modulo = $row->istanza_modulo;
        $elenco_moduli[$numero_moduli]->nome_modulo = $row->nome_modulo;
        $elenco_moduli[$numero_moduli]->visibile = $row->visibile;
        $elenco_moduli[$numero_moduli]->monitorabile = $row->monitorabile;
        $elenco_moduli[$numero_moduli]->posizione_in_report = $row->posizione_in_report;
    }
    return $elenco_moduli;
} //EML_Get_tbl_eml_pent_moduli_corsi_on_line
function EML_Ins_tbl_eml_grfo_feed_back(EML_RECtbl_eml_grfo_feed_back $rec_tbl_eml_grfo_feed_back) {
/*
* A. Albertin, G. Mandarà - CSI Piemonte - dicembre 2014
* 
* Inserisce un record in tabella tbl_eml_grfo_feed_back
* valorizzando i campi con quanto presente nel record ricevuto come parametro
* 
* Parametri:
*     $rec_tbl_eml_grfo_feed_back -- Record con i dati da inserire (mappa la tabella tbl_eml_grfo_feed_back)
*
* NOTA: la function prevede che tutti i campi del record $rec_tbl_eml_grfo_feed_back  siano valorizzati 
*       ad eccezione di:
*           - id (autoincrement)
*    
* Codici restituiti:
*   < 0 => error code della INSERT cambiato di segno (se operazione andata male)
*   > 0 => id del record inserito  
*/
    global $mysqli;
    $query = " INSERT INTO tbl_eml_grfo_feed_back"
            ." (id_corso, cod_corso, titolo_corso"
            .", operazione, stato, url, flag_parametro_id_corso, nota"
            ." ) VALUES ("
            .$rec_tbl_eml_grfo_feed_back->id_corso
            .", '".$rec_tbl_eml_grfo_feed_back->cod_corso."'"
            .", '".$mysqli->real_escape_string($rec_tbl_eml_grfo_feed_back->titolo_corso)."'"
            .", '".$rec_tbl_eml_grfo_feed_back->operazione."'"
            .", '".$mysqli->real_escape_string($rec_tbl_eml_grfo_feed_back->stato)."'"
            .", '".$mysqli->real_escape_string($rec_tbl_eml_grfo_feed_back->url)."'"
            .", '".$rec_tbl_eml_grfo_feed_back->flag_parametro_id_corso."'"
            .", '".$mysqli->real_escape_string($rec_tbl_eml_grfo_feed_back->nota)."'"
            .")";
    $mysqli->query($query);
    if ($mysqli->errno) {
        return -$mysqli->errno;
    } else {
        return $mysqli->insert_id;
    } 
} //EML_Ins_tbl_eml_grfo__feed_back
function EML_Ins_tbl_eml_grfo_log(EML_RECtbl_eml_grfo_log $rec_tbl_eml_grfo_log) {
/*
* A. Albertin, G. Mandarà - CSI Piemonte - dicembre 2014
* 
* Inserisce un record in tabella tbl_eml_grfo_log
* valorizzando i campi con quanto presente nel record ricevuto come parametro
* 
* Parametri:
*     $rec_tbl_eml_grfo_log -- Record con i dati da inserire (mappa la tabella tbl_eml_grfo_log)
*
* NOTA: la function prevede che tutti i campi del record $rec_tbl_eml_grfo_log  siano valorizzati 
*       ad eccezione di:
*           - id (autoincrement)
*           - data (valorizzato con NOW())
*    
* Codici restituiti:
*   < 0 => error code della INSERT (se operazione andata male)
*   > 0 => id del record inserito  
*/
    global $mysqli;
    $query = " INSERT INTO tbl_eml_grfo_log"
            ." (data, id_corso, cod_corso, titolo_corso, pagina"
            .", livello_msg, cod_msg, descr_msg"
            .", username, utente, nota"
            ." ) VALUES ("
            ." NOW()"
            .", ".$rec_tbl_eml_grfo_log->id_corso
            .", '".$rec_tbl_eml_grfo_log->cod_corso."'"
            .", '".$mysqli->real_escape_string($rec_tbl_eml_grfo_log->titolo_corso)."'"
            .", '".$rec_tbl_eml_grfo_log->pagina."'"
            .", ".$rec_tbl_eml_grfo_log->livello_msg
            .", ".$rec_tbl_eml_grfo_log->cod_msg
            .", '".$mysqli->real_escape_string($rec_tbl_eml_grfo_log->descr_msg)."'"
            .", '".$mysqli->real_escape_string($rec_tbl_eml_grfo_log->username)."'"
            .", '".$mysqli->real_escape_string($rec_tbl_eml_grfo_log->utente)."'"
            .", '".$mysqli->real_escape_string($rec_tbl_eml_grfo_log->nota)."'"
            .")";
    $mysqli->query($query);
    if ($mysqli->errno) {
        return -$mysqli->errno;
    } else {
        return $mysqli->insert_id;
    }        
} //EML_Ins_tbl_eml_grfo_log
function EML_Ins_tbl_eml_pent_edizioni_corsi_on_line(EML_RECtbl_eml_pent_edizioni_corsi_on_line $rec_tbl_eml_pent_edizioni_corsi_on_line) {
/*
* A. Albertin, G. Mandarà - CSI Piemonte - dicembre 2014
* 
* Inserisce un record in tabella tbl_eml_pent_edizioni_corsi_on_line
* valorizzando i campi con quanto presente nel record ricevuto come parametro
* 
* Parametri:
*     $rec_tbl_eml_pent_edizioni_corsi_on_line -- Record con i dati da inserire (mappa la tabella tbl_eml_pent_edizioni_corsi_on_line)
*
* Codici restituiti:
*/
    global $mysqli;
    $query = " INSERT INTO tbl_eml_pent_edizioni_corsi_on_line"
            ." (id_corso, cod_corso, titolo_corso"
            .", id_edizione, edizione, data_inizio, flag_monitorata_S_N"
            ." ) VALUES ("
            ." ".$rec_tbl_eml_pent_edizioni_corsi_on_line->id_corso
            .", '".$rec_tbl_eml_pent_edizioni_corsi_on_line->cod_corso."'"
            .", '".$mysqli->real_escape_string($rec_tbl_eml_pent_edizioni_corsi_on_line->titolo_corso)."'"
            .", ".$rec_tbl_eml_pent_edizioni_corsi_on_line->id_edizione
            .", '".$rec_tbl_eml_pent_edizioni_corsi_on_line->edizione."'"
            .", '".$rec_tbl_eml_pent_edizioni_corsi_on_line->data_inizio."'"
            .", '".$rec_tbl_eml_pent_edizioni_corsi_on_line->flag_monitorata_S_N."'"
            .")";
    $mysqli->query($query);
    if ($mysqli->errno) {
        return -$mysqli->errno;
    } else {
        return $mysqli->affected_rows;
    }   
} //EML_Ins_tbl_eml_pent_edizioni_corsi_on_line
function EML_Ins_tbl_eml_pent_moduli_corsi_on_line(EML_RECtbl_eml_pent_moduli_corsi_on_line $rec_tbl_eml_pent_moduli_corsi_on_line) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - dicembre 2014
 * 
 * Inserisce un record in tabella tbl_eml_pent_moduli_corsi_on_line
 * valorizzando i campi con quanto presente nel record ricevuto come parametro
* 
* Parametri:
*     $rec_tbl_eml_pent_moduli_corsi_on_line -- Record con i dati da inserire (mappa la tabella tbl_eml_pent_moduli_corsi_on_line)
*
* Codici restituiti:
*/
    global $mysqli;
    $query = " INSERT INTO tbl_eml_pent_moduli_corsi_on_line"
            ." (id_corso, cod_corso, titolo_corso, progressivo, id_modulo, tipo_modulo"
            .", istanza_modulo, nome_modulo, visibile, monitorabile, posizione_in_report"
            ." ) VALUES ("
            ." ".$rec_tbl_eml_pent_moduli_corsi_on_line->id_corso
            .", '".$rec_tbl_eml_pent_moduli_corsi_on_line->cod_corso."'"
            .", '".$mysqli->real_escape_string($rec_tbl_eml_pent_moduli_corsi_on_line->titolo_corso)."'"
            .", ".$rec_tbl_eml_pent_moduli_corsi_on_line->progressivo
            .", ".$rec_tbl_eml_pent_moduli_corsi_on_line->id_modulo
            .", '".$rec_tbl_eml_pent_moduli_corsi_on_line->tipo_modulo."'"
            .", ".$rec_tbl_eml_pent_moduli_corsi_on_line->istanza_modulo
            .", '".$mysqli->real_escape_string($rec_tbl_eml_pent_moduli_corsi_on_line->nome_modulo)."'"
            .", '".$rec_tbl_eml_pent_moduli_corsi_on_line->visibile."'"
            .", '".$rec_tbl_eml_pent_moduli_corsi_on_line->monitorabile."'"
            .", ".$rec_tbl_eml_pent_moduli_corsi_on_line->posizione_in_report
            .")";
    $mysqli->query($query);
    if ($mysqli->errno) {
        return -$mysqli->errno;
    } else {
        return $mysqli->affected_rows;
    }
} //EML_Ins_tbl_eml_pent_moduli_corsi_on_line
function EML_Pulisci_log() {
/*
* A. Albertin, G. Mandarà - CSI Piemonte - dicembre 2014
* 
* Cancella dalla tabella tbl_eml_grfo_log i record "vecchi"
* 
* Parametri:
*
* Codici restituiti:
*/
    //global $mysqli;
    $rec_mdl_f2_parametri = new EML_RECmdl_f2_parametri();
    // leggo il parametro p_grfo_giorni_matenìtenimento_log
    $id = 'p_grfo_giorni_mantenimento_log';
    $ret_code = EML_Get_mdl_f2_parametri($id, $rec_mdl_f2_parametri);
    $giorni_mantenimento_log = (int) $rec_mdl_f2_parametri->val_int;
    // Cancello i mnessaggi vecchi
    $nome_tabella = 'tbl_eml_grfo_log';
    $clausola_where = " WHERE data <= SUBDATE(CURDATE(),INTERVAL ".$giorni_mantenimento_log." DAY);";
    $ret_code = EML_Del_xxx($nome_tabella, $clausola_where);    
} //EML_Pulisci_log
function EML_Upd_xxx($nome_tabella, $clausola_update, $clausola_where) {
/*
* A. Albertin, G. Mandarà - CSI Piemonte - dicembre 2014
* 
* Esegue un generico comnado di Update
* 
* Parametri:
*     $nome_tabella    -- Tabella da modificare
*     $clausola_update -- UPDATE ......
*     $clausola_where  -- WHERE .......
*
* Codici restituiti:
*/
    global $mysqli;
    $query = ' UPDATE '.$nome_tabella.' '.$clausola_update.' '.$clausola_where;
    $mysqli->query($query);
    if ($mysqli->errno) {
        return -$mysqli->errno;
    } else {
        return $mysqli->affected_rows;
    }        
} //EML_Upd_xxx
?>