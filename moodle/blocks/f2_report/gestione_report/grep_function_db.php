<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - giugno 2015
 * 
 * Funzioni di accesso al db
 * 
 *  NOTA IMPORTANTE: 
 *      La DB->get_records_sql (per motivi sconosciuti) restituisce i nomi dei campi TUTTI IN MINUSCOLO
 *      ad esempio bisogna usare $row->id_user_riforma e non $row->id_user_Riforma,. ecc.
 * 
 * Function presenti:
 *      EML_Get_elenco_parametri_report
 *      EML_Get_elenco_report
 *      EML_Get_elenco_ruoli_report
 *      EML_Get_elenco_voci_menu_report
 *      EML_Get_mdl_f2_csi_pent_menu_report
 *      EML_Get_tbl_eml_grep_feed_back
 *      EML_Ins_mdl_f2_csi_pent_menu_report
 *      EML_Ins_mdl_f2_csi_pent_report
 *      EML_Ins_tbl_eml_grep_feed_back
 *      EML_Upd_mdl_f2_csi_pent_menu_report
 *      EML_Upd_mdl_f2_csi_pent_report
*/
function EML_Get_elenco_parametri_report($id_report, &$numero_parametri) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - giugno 2015
 * 
 * Estrae l'elenco dei parametri associati o non associatio ad un report
 * 
 * Restituisce:
 *     $elenco_parametri - array di tipo EML_Elenco_parametri
 * 
 * Parametri
 *     $id_report - identificativo del di interesse
 *     &$numero_parametri - numero di record presenti in $elenco_report
 * 
 * Principali passi eseguiti
 *  Loop sui parametri (tabella mdl_f2_csi_pent_param)
 *      leggo il nome del parametro
 *      verifico se il parametro è associato al report
 *      se associato
 *          imposto flag_S_N a Si 
 *      altrimenti
 *          imposto flag_S_N a No
 *      fine se
 * fine loop
*/
    global $DB;
    $elenco_parametri = array();
    $query = "SELECT id as id_parametro, nome as nome_parametro"
            ." FROM mdl_f2_csi_pent_param"
            ." ORDER BY id";
    $result = $DB->get_records_sql($query);
    $numero_parametri = 0;
    foreach($result as $row) {
        $numero_parametri++;
        $elenco_parametri[$numero_parametri] = new EML_Elenco_parametri();
        $elenco_parametri[$numero_parametri]->id_parametro = $row->id_parametro;
        $elenco_parametri[$numero_parametri]->nome_parametro = $row->nome_parametro;
        $nome_tabella = 'mdl_f2_csi_pent_param_map';
        $clausola_where = ' where id_param = '.$elenco_parametri[$numero_parametri]->id_parametro.' and id_report = '.$id_report;
        $numero_record = 0;
        $ret_code = EML_Get_Numero_record_in_tabella($nome_tabella, $clausola_where, $numero_record);
        if ($numero_record == 0) {
            $elenco_parametri[$numero_parametri]->flag_S_N = EML_GREP_NO;
        } else {
            $elenco_parametri[$numero_parametri]->flag_S_N = EML_GREP_SI;
        }
    }
    return $elenco_parametri;
} //EML_Get_elenco_parametri_report
function EML_Get_elenco_report ($id_voce_menu, &$numero_report) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - giugno 2015
 * 
 * Estrae l'elenco dei report associati ad una voce di menù
 * 
 * Restituisce:
 *     $elenco_report - array di tipo EML_Elenco_report con i dati letti dal db
 * 
 * Parametri
 *     $id_voce_menu - identificativo della voce menù report di interesse
 *     &$numero_report - numero di record presenti in $elenco_report
*/
    global $DB;
    $elenco_report = array();
    $query = "SELECT"
            ."  A.id as id_report, A.id_menu_report, A.posizione_in_elenco_report, A.attivo as flag_attivo"
            .",	A.nome_report, A.nome_file_pentaho, A.formato_default"
            .",	(SELECT COUNT(*) FROM mdl_f2_csi_pent_param_map WHERE id_report = A.id) as numero_parametri"
            .",	(SELECT COUNT(*) FROM mdl_f2_csi_pent_role_map WHERE id_report = A.id) as numero_ruoli"
            ." FROM mdl_f2_csi_pent_report A"
            ." WHERE A.id_menu_report = ".$id_voce_menu
            ." ORDER BY A.posizione_in_elenco_report, A.nome_report";
    $result = $DB->get_records_sql($query);
    $numero_report = 0;
    foreach($result as $row) {
        $numero_report++;
        $elenco_report[$numero_report] = new EML_Elenco_report();
        $elenco_report[$numero_report]->id_menu_report = $row->id_menu_report;
        $elenco_report[$numero_report]->id_report = $row->id_report;
        $elenco_report[$numero_report]->posizione_in_elenco_report = $row->posizione_in_elenco_report;
        $elenco_report[$numero_report]->flag_attivo = $row->flag_attivo;
        $elenco_report[$numero_report]->nome_report = $row->nome_report;
        $elenco_report[$numero_report]->nome_file_pentaho = $row->nome_file_pentaho;
        $elenco_report[$numero_report]->formato_default = $row->formato_default;
        $elenco_report[$numero_report]->numero_parametri = $row->numero_parametri;
        $elenco_report[$numero_report]->numero_ruoli = $row->numero_ruoli;
    };
    return $elenco_report;
} //EML_Get_elenco_report
function EML_Get_elenco_ruoli_report($id_report, &$numero_ruoli) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - luglio 2015
 * 
 * Estrae l'elenco dei ruoli abilitati/non abilitati all'esecuzione di un report
 * 
 * Restituisce:
 *     $elenco_ruoli - array di tipo EML_Elenco_ruoli
 * 
 * Parametri
 *     $id_report - identificativo del di interesse
 *     &$numero_ruoli - numero di record presenti in $elenco_ruoli
 * 
 * Principali passi eseguiti
 *  Loop sui ruoli (tabella mdl_role)
 *      leggo il nome del ruolo
 *      verifico se il ruolo è associato al report
 *      se associato
 *          imposto flag_S_N a Si 
 *      altrimenti
 *          imposto flag_S_N a No
 *      fine se
 * fine loop
*/
    global $DB;
    $elenco_ruoli = array();
    $query = "SELECT id as id_ruolo, name as nome_ruolo"
            ." FROM mdl_role"
            ." ORDER BY id";
    $result = $DB->get_records_sql($query);
    $numero_ruoli = 0;
    foreach($result as $row) {
        $numero_ruoli++;
        $elenco_ruoli[$numero_ruoli] = new EML_Elenco_ruoli();
        $elenco_ruoli[$numero_ruoli]->id_ruolo = $row->id_ruolo;
        $elenco_ruoli[$numero_ruoli]->nome_ruolo = $row->nome_ruolo;
        $nome_tabella = 'mdl_f2_csi_pent_role_map';
        $clausola_where = ' where id_role = '.$elenco_ruoli[$numero_ruoli]->id_ruolo.' and id_report = '.$id_report;
        $numero_record = 0;
        $ret_code = EML_Get_Numero_record_in_tabella($nome_tabella, $clausola_where, $numero_record);
        if ($numero_record == 0) {
            $elenco_ruoli[$numero_ruoli]->flag_S_N = EML_GREP_NO;
        } else {
            $elenco_ruoli[$numero_ruoli]->flag_S_N = EML_GREP_SI;
        }
    }
    return $elenco_ruoli;
} //EML_Get_elenco_ruoli_report
function EML_Get_elenco_voci_menu_report (&$numero_voci_menu) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - giugno 2015
 * 
 * Estrae l'elenco delle Voci di menù Report
 * 
 * Restituisce:
 *     $voci_menu_report - array di tipo EML_Voci_menu_report con i dati letti dal db
 * 
 * Parametri
 *     &$numero_voci_menu - numero di record presenti in $voci_menu_report
*/
    global $DB;
    $voci_menu_report = array();
    $query = "SELECT"
            ."  A.id as id_voce, A.codice as cod_voce, A.descrizione as descr_voce, A.attiva as flag_attiva"
            .", (SELECT COUNT(*) FROM mdl_f2_csi_pent_report WHERE id_menu_report = A.id) as numero_totale_report"
            .", (SELECT COUNT(*) FROM mdl_f2_csi_pent_report WHERE id_menu_report = A.id AND attivo = 1) as numero_report_attivi"
            ." FROM mdl_f2_csi_pent_menu_report A"
            ." ORDER BY A.id";
    $result = $DB->get_records_sql($query);
    $numero_voci_menu = 0;
    foreach($result as $row) {
        $numero_voci_menu++;
        $voci_menu_report[$numero_voci_menu] = new EML_Voci_menu_report();
        $voci_menu_report[$numero_voci_menu]->id_voce = $row->id_voce;
        $voci_menu_report[$numero_voci_menu]->cod_voce = $row->cod_voce;
        $voci_menu_report[$numero_voci_menu]->descr_voce = $row->descr_voce;
        $flag_attiva = $row->flag_attiva;
        if ($flag_attiva == 1) {
            $voci_menu_report[$numero_voci_menu]->flag_attiva = EML_GREP_SI;
        } else {
            $voci_menu_report[$numero_voci_menu]->flag_attiva = EML_GREP_NO;
        }
        $voci_menu_report[$numero_voci_menu]->numero_totale_report = $row->numero_totale_report;
        $voci_menu_report[$numero_voci_menu]->numero_report_attivi = $row->numero_report_attivi;
    };
    return $voci_menu_report;
} //EML_Get_elenco_voci_menu_report
function EML_Get_mdl_f2_csi_pent_menu_report($id, EML_RECmdl_f2_csi_pent_menu_report $rec_mdl_f2_csi_pent_menu_report) {
/*
* A. Albertin, G. Mandarà - CSI Piemonte - giugno 2015
* 
* Legge un record da tabella mdl_f2_csi_pent_menu_report
* valorizzando il record ricevuto come parametro
* 
* Parametri:
*     $id -- Id del record da leggere
*     $rec_mdl_f2_csi_pent_menu_report -- Record con i dati da inserire (mappa la tabella mdl_f2_csi_pent_menu_report)
*
* Codici restituiti:
*   < 0 => error code della SELECT (se operazione andata male)
*     1 => lettura ok
*/
    global $mysqli;
    $query = " SELECT"
            ." id, codice, descrizione, attiva"
            ." FROM mdl_f2_csi_pent_menu_report"
            ." WHERE id = ".$id;
    $res = $mysqli->query($query);
    if (!$res) {
        $ret_code = -$mysqli->errno;
    } else {
        $ret_code = 1;
        $row = $res->fetch_assoc();
        $rec_mdl_f2_csi_pent_menu_report->id = $row['id'];
        $rec_mdl_f2_csi_pent_menu_report->codice = $row['codice'];
        $rec_mdl_f2_csi_pent_menu_report->descrizione = $row['descrizione'];
        $rec_mdl_f2_csi_pent_menu_report->attiva = $row['attiva'];
    }
    return $ret_code;
} //EML_Get_mdl_f2_csi_pent_menu_report
function EML_Get_mdl_f2_csi_pent_report($id, EML_RECmdl_f2_csi_pent_report $rec_mdl_f2_csi_pent_report) {
/*
* A. Albertin, G. Mandarà - CSI Piemonte - giugno 2015
* 
* Legge un record da tabella mdl_f2_csi_pent_report
* valorizzando il record ricevuto come parametro
* 
* Parametri:
*     $id -- Id del record da leggere
*     $rec_mdl_f2_csi_pent_report -- Record con i dati da inserire (mappa la tabella mdl_f2_csi_pent_report)
*
* Codici restituiti:
*   < 0 => error code della SELECT (se operazione andata male)
*     1 => lettura ok
*/
    global $mysqli;
    $query = " SELECT"
            ." id, id_menu_report, nome_report, nome_file_pentaho, posizione_in_elenco_report"
            .", attivo, formato_default, numero_esecuzioni, data_ultima_esecuzione"
            ." FROM mdl_f2_csi_pent_report"
            ." WHERE id = ".$id;
    $res = $mysqli->query($query);
    if (!$res) {
        $ret_code = -$mysqli->errno;
    } else {
        $ret_code = 1;
        $row = $res->fetch_assoc();
        $rec_mdl_f2_csi_pent_report->id = $row['id'];
        $rec_mdl_f2_csi_pent_report->id_menu_report = $row['id_menu_report'];
        $rec_mdl_f2_csi_pent_report->nome_report = $row['nome_report'];
        $rec_mdl_f2_csi_pent_report->nome_file_pentaho = $row['nome_file_pentaho'];
        $rec_mdl_f2_csi_pent_report->posizione_in_elenco_report = $row['posizione_in_elenco_report'];
        $rec_mdl_f2_csi_pent_report->attivo = $row['attivo'];
        $rec_mdl_f2_csi_pent_report->formato_default = $row['formato_default'];
        $rec_mdl_f2_csi_pent_report->numero_esecuzioni = $row['numero_esecuzioni'];
        $rec_mdl_f2_csi_pent_report->data_ultima_esecuzione = $row['data_ultima_esecuzione'];
    }
    return $ret_code;
} //EML_Get_mdl_f2_csi_pent_menu_report
function EML_Get_tbl_eml_grep_feed_back($id, EML_RECtbl_eml_grep_feed_back $rec_tbl_eml_grep_feed_back) {
/*
* A. Albertin, G. Mandarà - CSI Piemonte - giugno 2015
* 
* Legge un record da tabella tbl_eml_grep_feed_back
* valorizzando il record ricevuto come parametro
* 
* Parametri:
*     $id -- Id del record da leggere
*     $rec_tbl_eml_grep_feed_back -- Record con i dati da inserire (mappa la tabella tbl_eml_grep_feed_back)
*
* Codici restituiti:
*   < 0 => error code della SELECT (se operazione andata male)
*     1 => lettura ok
*/
    global $mysqli;
    $query = " SELECT"
            ." id, operazione, stato, url, nota_1, nota_2, nota_3, nota_4"
            ." FROM tbl_eml_grep_feed_back"
            ." WHERE id = ".$id;
    $res = $mysqli->query($query);
    if (!$res) {
        $ret_code = -$mysqli->errno;
    } else {
        $ret_code = 1;
        $row = $res->fetch_assoc();
        $rec_tbl_eml_grep_feed_back->id = $row['id'];
        $rec_tbl_eml_grep_feed_back->operazione = $row['operazione'];
        $rec_tbl_eml_grep_feed_back->stato = $row['stato'];
        $rec_tbl_eml_grep_feed_back->url = $row['url'];
        $rec_tbl_eml_grep_feed_back->nota_1 = $row['nota_1'];
        $rec_tbl_eml_grep_feed_back->nota_2 = $row['nota_2'];
        $rec_tbl_eml_grep_feed_back->nota_3 = $row['nota_3'];
        $rec_tbl_eml_grep_feed_back->nota_4 = $row['nota_4'];
    }
    return $ret_code;
} //EML_Get_tbl_eml_grep_feed_back
function EML_Ins_mdl_f2_csi_pent_menu_report(EML_RECmdl_f2_csi_pent_menu_report $rec_mdl_f2_csi_pent_menu_report) {
/*
* A. Albertin, G. Mandarà - CSI Piemonte - giugno 2015
* 
* Inserisce un record in tabella mdl_f2_csi_pent_menu_report
* valorizzando i campi con quanto presente nel record ricevuto come parametro
* 
* Parametri:
*     $rec_mdl_f2_csi_pent_menu_report -- Record con i dati da inserire (mappa la tabella mdl_f2_csi_pent_menu_report)
*
* NOTA: la function prevede che tutti i campi del record $rec_mdl_f2_csi_pent_menu_report  siano valorizzati 
*       ad eccezione di:
*           - id (autoincrement)
*    
* Codici restituiti:
*   < 0 => error code della INSERT (se operazione andata male)
*   > 0 => id del record inserito  
*/
    global $mysqli;
    $query = " INSERT INTO mdl_f2_csi_pent_menu_report"
            ." (codice, descrizione, attiva"
            ." ) VALUES ("
            ." '".$mysqli->real_escape_string($rec_mdl_f2_csi_pent_menu_report->codice)."'"
            .", '".$mysqli->real_escape_string($rec_mdl_f2_csi_pent_menu_report->descrizione)."'"
            .", '".$rec_mdl_f2_csi_pent_menu_report->attiva."'"
            .")";
    $mysqli->query($query);
    if ($mysqli->errno) {
        return -$mysqli->errno;
    } else {
        return $mysqli->insert_id;
    }        
} //EML_Ins_mdl_f2_csi_pent_menu_report
function EML_Ins_mdl_f2_csi_pent_report(EML_RECmdl_f2_csi_pent_report $rec_mdl_f2_csi_pent_report) {
/*
* A. Albertin, G. Mandarà - CSI Piemonte - giugno 2015
* 
* Inserisce un record in tabella mdl_f2_csi_pent_report
* valorizzando i campi con quanto presente nel record ricevuto come parametro
* 
* Parametri:
*     $rec_mdl_f2_csi_pent_report -- Record con i dati da inserire (mappa la tabella mdl_f2_csi_pent_report)
*
* NOTA: la function prevede che tutti i campi del record $rec_mdl_f2_csi_pent_report  siano valorizzati 
*       ad eccezione di:
*           - id (autoincrement)
*    
* Codici restituiti:
*   < 0 => error code della INSERT (se operazione andata male)
*   > 0 => id del record inserito  
*/
    global $mysqli;
    $query = " INSERT INTO mdl_f2_csi_pent_report"
            ." (id_menu_report, nome_report, nome_file_pentaho, posizione_in_elenco_report, attivo, formato_default"
            ." ) VALUES ("
            ." ".$mysqli->real_escape_string($rec_mdl_f2_csi_pent_report->id_menu_report)
            .", '".$mysqli->real_escape_string($rec_mdl_f2_csi_pent_report->nome_report)."'"
            .", '".$mysqli->real_escape_string($rec_mdl_f2_csi_pent_report->nome_file_pentaho)."'"
            .", ".$rec_mdl_f2_csi_pent_report->posizione_in_elenco_report
            .", ".$rec_mdl_f2_csi_pent_report->attivo
            .", '".$mysqli->real_escape_string($rec_mdl_f2_csi_pent_report->formato_default)."'"
            .")";
    $mysqli->query($query);
    if ($mysqli->errno) {
        return -$mysqli->errno;
    } else {
        return $mysqli->insert_id;
    }
} //EML_Ins_mdl_f2_csi_pent_report
function EML_Ins_tbl_eml_grep_feed_back(EML_RECtbl_eml_grep_feed_back $rec_tbl_eml_grep_feed_back) {
/*
* A. Albertin, G. Mandarà - CSI Piemonte - giugno 2015
* 
* Inserisce un record in tabella tbl_eml_grep_feed_back
* valorizzando i campi con quanto presente nel record ricevuto come parametro
* 
* Parametri:
*     $rec_tbl_eml_grep_feed_back -- Record con i dati da inserire (mappa la tabella tbl_eml_grep_feed_back)
*
* NOTA: la function prevede che tutti i campi del record $rec_tbl_eml_grep_feed_back  siano valorizzati 
*       ad eccezione di:
*           - id (autoincrement)
*    
* Codici restituiti:
*   < 0 => error code della INSERT (se operazione andata male)
*   > 0 => id del record inserito  
*/
    global $mysqli;
    $query = " INSERT INTO tbl_eml_grep_feed_back"
            ." (operazione, stato, url, nota_1, nota_2, nota_3, nota_4"
            ." ) VALUES ("
            ." '".$mysqli->real_escape_string($rec_tbl_eml_grep_feed_back->operazione)."'"
            .", '".$mysqli->real_escape_string($rec_tbl_eml_grep_feed_back->stato)."'"
            .", '".$mysqli->real_escape_string($rec_tbl_eml_grep_feed_back->url)."'"
            .", '".$mysqli->real_escape_string($rec_tbl_eml_grep_feed_back->nota_1)."'"
            .", '".$mysqli->real_escape_string($rec_tbl_eml_grep_feed_back->nota_2)."'"
            .", '".$mysqli->real_escape_string($rec_tbl_eml_grep_feed_back->nota_3)."'"
            .", '".$mysqli->real_escape_string($rec_tbl_eml_grep_feed_back->nota_4)."'"
            .")";
    $mysqli->query($query);
    if ($mysqli->errno) {
        return -$mysqli->errno;
    } else {
        return $mysqli->insert_id;
    } 
} //EML_Ins_tbl_eml_grep_feed_back
function EML_Upd_mdl_f2_csi_pent_menu_report($id, EML_RECmdl_f2_csi_pent_menu_report $rec_mdl_f2_csi_pent_menu_report) {
/*
* A. Albertin, G. Mandarà - CSI Piemonte - giugno 2015
* 
* Modifica un record in tabella mdl_f2_csi_pent_menu_report
* 
* Parametri:
 *    $id -- Identificativo del record da modificare
*     $rec_mdl_f2_csi_pent_menu_report -- Record con i dati da inserire (mappa la tabella mdl_f2_csi_pent_menu_report)
*
* NOTA: la function prevede che tutti i campi del record $rec_mdl_f2_csi_pent_menu_report siano valorizzati 
*       ad eccezione di:
*           - id
*    
* Codici restituiti:
*   < 0 => error code della UPDATE (se operazione andata male)
*     1 => tutto ok
*/
    global $mysqli;
    $query = " UPDATE mdl_f2_csi_pent_menu_report SET"
            ." codice = '".$mysqli->real_escape_string($rec_mdl_f2_csi_pent_menu_report->codice)."'"
            .", descrizione = '".$mysqli->real_escape_string($rec_mdl_f2_csi_pent_menu_report->descrizione)."'"
            .", attiva = '".$rec_mdl_f2_csi_pent_menu_report->attiva."'"
            ." WHERE id = ".$id;
    $mysqli->query($query);
    if ($mysqli->errno) {
        return -$mysqli->errno;
    } else {
        return 1;
    }        
} //EML_Upd_mdl_f2_csi_pent_report
function EML_Upd_mdl_f2_csi_pent_report($id, EML_RECmdl_f2_csi_pent_report $rec_mdl_f2_csi_pent_report) {
/*
* A. Albertin, G. Mandarà - CSI Piemonte - giugno 2015
* 
* Modifica un record in tabella mdl_f2_csi_pent_report
* 
* Parametri:
 *    $id -- Identificativo del record da modificare
*     $rec_mdl_f2_csi_pent_report -- Record con i dati da modificare (mappa la tabella mdl_f2_csi_pent_report)
*
* NOTA: la function prevede che tutti i campi del record $rec_mdl_f2_csi_pent_report siano valorizzati 
*       ad eccezione di:
*           - id
*    
* Codici restituiti:
*   < 0 => error code della UPDATE (se operazione andata male)
*     1 => tutto ok
*/
    global $mysqli;
    $query = " UPDATE mdl_f2_csi_pent_report SET"
            ." nome_report = '".$mysqli->real_escape_string($rec_mdl_f2_csi_pent_report->nome_report)."'"
            .", nome_file_pentaho = '".$mysqli->real_escape_string($rec_mdl_f2_csi_pent_report->nome_file_pentaho)."'"
            .", posizione_in_elenco_report = ".$rec_mdl_f2_csi_pent_report->posizione_in_elenco_report
            .", attivo = ".$rec_mdl_f2_csi_pent_report->attivo
            .", formato_default = '".$mysqli->real_escape_string($rec_mdl_f2_csi_pent_report->formato_default)."'"
            ." WHERE id = ".$id;
    $mysqli->query($query);
    if ($mysqli->errno) {
        return -$mysqli->errno;
    } else {
        return 1;
    }        
} //EML_Upd_mdl_f2_csi_pent_report
function EML_Get_massimo_posizione_in_elenco_report($id_voce_menu) {
/*
* A. Albertin, G. Mandarà - CSI Piemonte - giugno 2015
* 
* Restituisce il massimo valore di posizione_in_elenco_report (tabella mdl_f2_csi_pent_report)
* per un dato id_menu_report
* 
* Parametri:
*    $id_voce_menu 
*    
* Codici restituiti:
*   < 0 => error code della SELECT (se operazione andata male)
*   altrimenti il massimo valore cercato
*/
    global $mysqli;
    $query = "select MAX(posizione_in_elenco_report) as massimo from mdl_f2_csi_pent_report"
            ." WHERE id_menu_report = ".$id_voce_menu;
    $res = $mysqli->query($query);
    if (!$res) {
        $ret_code = -$mysqli->errno;
    } else {
        $row = $res->fetch_assoc();
        return $row['massimo'];
    }
} //EML_Get_massimo_posizione_in_elenco_report