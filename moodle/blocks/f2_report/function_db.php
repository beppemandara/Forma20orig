<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - luglio 2016
 * 
 * Funzioni di accesso al db
 * 
 *  NOTA IMPORTANTE: 
 *      La DB->get_records_sql (per motivi sconosciuti) restituisce i nomi dei campi TUTTI IN MINUSCOLO
 *      ad esempio bisogna usare $row->id_user_riforma e non $row->id_user_Riforma,. ecc.
 * 
 * Function presenti:
 *      EML_aggiorna_numero_esecuzioni_report
 *      EML_Get_elenco_report_selezionabili
 *      EML_Get_mdl_f2_csi_pent_report
 *      EML_Get_user_roleid
 *      EML_Get_parametri_report
*/
//AAAAAAAAAA da unificare col file function_db.php presente in on_line
//AAAAAAAAAA da unificare col file function_db.php presente in on_line
//AAAAAAAAAA da unificare col file function_db.php presente in on_line
//AAAAAAAAAA da unificare col file function_db.php presente in on_line
function EML_aggiorna_numero_esecuzioni_report($report_id) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - luglio 2015
 * 
 * Aggiorna il numero_esecuzioni e la data_ultima_esecuzione 
 * per il report ricevuto come parametro
 * 
 * Parametri
 *     $report_id - Identificativo del report da aggiornare
 */
    global $DB;
    $query = " UPDATE mdl_f2_csi_pent_report"
            ." SET numero_esecuzioni = numero_esecuzioni + 1"
            .", data_ultima_esecuzione = NOW()"
            ." WHERE id = ".$report_id;
    $DB->execute($query);
} //EML_aggiorna_numero_esecuzioni_report
function EML_Get_elenco_report_selezionabili($codice_voce_menu, $role_id, &$numero_report) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - giugno 2015
 * 
 * Estrae l'elenco dei report selezionabili (per una voce di menù report ED un ruolo utente)
 * 
 * Restituisce:
 *     $elenco_report_select - array da usare nella form (dati del campo select)
 * 
 * Parametri
 *     $codice_voce_menu - Codice che identifica la voce del Menù report (es. 'STAT', 'FIND', ...)
 *     $role_id - Identificativo del ruolo utente
 *     &$numero_report - numero di record presenti in $elenco_report_select
*/
    global $DB;
    $elenco_report_select = array();
    $query = " SELECT"
            ."      B.id as id_report, B.nome_report as nome_report"
            ." FROM mdl_f2_csi_pent_menu_report A"
            ."      JOIN mdl_f2_csi_pent_report B on A.id = B.id_menu_report"
            ."      JOIN mdl_f2_csi_pent_role_map C on B.id = C.id_report"
            ." WHERE A.codice = '".$codice_voce_menu."'"
            ." AND  B.attivo = 1"
            ." AND  C.id_role = ".$role_id
            ." ORDER BY B.posizione_in_elenco_report";
    $result = $DB->get_records_sql($query);
    $numero_report = 0;
    //$elenco_corsi_select = array();
    foreach($result as $row) {
        $numero_report++;
        $elenco_report_select[$row->id_report] = $row->nome_report;
    }
    return $elenco_report_select;
}// EML_Get_elenco_report_selezionabili
function EML_Get_mdl_f2_csi_pent_report($report_id, EML_RECmdl_f2_csi_pent_report $rec_mdl_f2_csi_pent_report) {
/*
* A. Albertin, G. Mandarà - CSI Piemonte - luglio 2015
* 
* Legge dalla tabella mdl_f2_csi_pent_report il record con id = $report_id
*
* Parametri:
*     $report_id -- Id del report da estrarre
*     $rec_mdl_f2_csi_pent_report -- Record con i dati letti (mappa la tabella mdl_f2_csi_pent_report)
* 
* Restituisce il numero di record letti 
*/
    global $DB;
    $ret_code = 0;
    $query = " SELECT id, id_menu_report, nome_report, nome_file_pentaho, formato_default"
            .", posizione_in_elenco_report, attivo, numero_esecuzioni, data_ultima_esecuzione"
            ." FROM mdl_f2_csi_pent_report"
            ." WHERE id = ".$report_id;
    $result = $DB->get_records_sql($query);
    foreach($result as $row) {
        $ret_code = 1;
        $rec_mdl_f2_csi_pent_report->id  = $row->id;
        $rec_mdl_f2_csi_pent_report->id_menu_report  = $row->id_menu_report;
        $rec_mdl_f2_csi_pent_report->nome_report  = $row->nome_report;
        $rec_mdl_f2_csi_pent_report->nome_file_pentaho  = $row->nome_file_pentaho;
        $rec_mdl_f2_csi_pent_report->formato_default  = $row->formato_default;
        $rec_mdl_f2_csi_pent_report->posizione_in_elenco_report  = $row->posizione_in_elenco_report;
        $rec_mdl_f2_csi_pent_report->attivo  = $row->attivo;
        $rec_mdl_f2_csi_pent_report->numero_esecuzioni  = $row->numero_esecuzioni;
        $rec_mdl_f2_csi_pent_report->data_ultima_esecuzione  = $row->data_ultima_esecuzione;
    }
    return $ret_code;
}//EML_Get_mdl_f2_csi_pent_report
function EML_Get_user_roleid($user_id, &$role_id, &$role_name) {
/*
* A. Albertin, G. Mandarà - CSI Piemonte - giugno 2015
* modificato luglio 2016 per estrarre il ruolo utente senza dover tener conto
* del contesto 
* 
* Legge dalla tabella mdl_role_assignments il ruolo dell'utente ricevuto come parametro
* (cerca il massimo mdl_role_assignments.id con mdl_role_assignments.id in (12 , 14)
*
* Parametri:
*     $user_id -- Id dell'utente
*     &$role_id -- id ruolo (dell'utente)
*     &$role_name -- nome ruolo
* 
* Restituisce il numero di record letti 
*/
    global $DB;
    $ret_code = 0;
/* old query
    $query = " SELECT B.id as role_id, B.name as role_name"
            ." FROM mdl_role_assignments A JOIN mdl_role B ON A.roleid = B.id"
            ." WHERE A.userid = ".$user_id." and contextid = 1";
 */
    $query = " SELECT B.id as role_id, B.name as role_name"
            ." FROM mdl_role_assignments A JOIN mdl_role B ON A.roleid = B.id"
            ." WHERE A.userid = ".$user_id
            ."       and B.id in (12 , 14)"
            ." order by B.id desc"
            ." limit 1";
    $result = $DB->get_records_sql($query);
    foreach($result as $row) {
        $ret_code++;
        $role_id = $row->role_id;
        $role_name = $row->role_name;
    }
    return $ret_code;
}//EML_Get_user_roleid
function EML_Get_parametri_report($report_id, $user_id, &$parametri_variabili) {
/*
* A. Albertin, G. Mandarà - CSI Piemonte - giugno 2015
* 
* Prepara la stringa con gli eventuali parametri "variabili" associati ad un report
*
* NOTA: è gestito unicamente il parametro dominio per i Referenti Formativi
*
* Parametri:
*     $report_id -- Id del report
*     $user_id -- id dell'utente
*     &$parametri_variabili -- Stringa con gli eventuali parametri 
* 
* Restituisce
*     0 --> Nessun parametro (&$parametri_variabili è restituita "vuota")
*     1 --> Trovato e valorizzato il dominio
*/
    global $DB;
    $ret_code = 0;
    $parametri_variabili = '';
    $query = " SELECT count(*) as contatore"
            ." FROM mdl_f2_csi_pent_param_map A JOIN mdl_f2_csi_pent_param B ON A.id_param = B.id"
            ." WHERE A.id_report = ".$report_id;
    $result = $DB->get_records_sql($query);
    foreach($result as $row) {
        $contatore = $row->contatore;
    }
    if($contatore ==1) {
        $query = " SELECT viewableorganisationid"
                ." FROM mdl_org_assignment"
                ." WHERE userid = ".$user_id;
        $result = $DB->get_records_sql($query);
        foreach($result as $row) {
            $dominio = $row->viewableorganisationid;
        }
        $parametri_variabili = '%26dominio='.$dominio;
        $ret_code = 1;
    }
    return $ret_code;
}//EML_Get_parametri_report