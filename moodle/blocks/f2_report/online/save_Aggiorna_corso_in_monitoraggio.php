<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - dicembre 2014
 * 
 * Function presenti:
 *      EML_Aggiorna_tbl_eml_pent_edizioni_corso
 *      EML_Aggiorna_tbl_eml_pent_moduli_corso
*/
function EML_Aggiorna_tbl_eml_pent_edizioni_corso ($id_corso, $flag_monitorato_S_N) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - dicembre 2014
 * 
 * Aggiorna la tabella tbl_eml_pent_edizioni_corso inserendo le eventuali "nuove" 
 * edizioni corso non ancora presenti in tabella
 * 
 * Restituisce:
 * 
 * Parametri
 *      $id_corso - identificativo del corso da elaborare
 *      $flag_monitorato_S_N - Valore da inserire nel campo omonimo
*/
    global $DB;
    //global $mysqli;
    $rec_mdl_course = new EML_RECmdl_course();
    $rec_tbl_eml_pent_edizioni_corsi_on_line = new EML_RECtbl_eml_pent_edizioni_corsi_on_line();
    // estraggo i dati del corso
    $iaus = EML_Get_mdl_course($id_corso, $rec_mdl_course);
    // estraggo l'elenco delle edizioni corso
    //      verifico se ci sono edizioni "nuove"
    //      se necessario le inserisco in tbl_eml_pent_edizioni_corsi_on_line
    $query = " SELECT D.id as id_edizione"
            .",       ( SELECT fsa.data"
            ."          FROM   mdl_facetoface_session_data fsa"
            ."          WHERE  fsa.fieldid = 9"
            ."            AND  fsa.sessionid = D.id) as edizione"
            .",       FROM_UNIXTIME(E.timestart) As data_inizio"
            ." FROM   mdl_facetoface C"
            ." JOIN   mdl_facetoface_sessions D ON C.id = D.facetoface"
            ." JOIN   mdl_facetoface_sessions_dates E ON D.id = E.sessionid"
            ." WHERE  C.course = ".$id_corso;
    $result = $DB->get_records_sql($query);
    foreach($result as $row) {
        $id_edizione = $row->id_edizione;
        // verifico se l'edizione è già presente in tbl_eml_pent_edizioni_corsi_on_line
        $nome_tabella = 'tbl_eml_pent_edizioni_corsi_on_line';
        $clausola_where = ' WHERE id_corso = '.$id_corso.' AND id_edizione = '.$id_edizione;
        $numero_record = 0;
        $iaus = EML_Get_Numero_record_in_tabella($nome_tabella, $clausola_where, $numero_record);
        if ($numero_record == 0) {
            // edizione "nuova" la inserisco in tbl_eml_pent_edizioni_corso
            $rec_tbl_eml_pent_edizioni_corsi_on_line->id_corso = $id_corso;
            $rec_tbl_eml_pent_edizioni_corsi_on_line->cod_corso = $rec_mdl_course->idnumber;
            $rec_tbl_eml_pent_edizioni_corsi_on_line->titolo_corso = $rec_mdl_course->fullname;
            $rec_tbl_eml_pent_edizioni_corsi_on_line->id_edizione = $row->id_edizione;
            $rec_tbl_eml_pent_edizioni_corsi_on_line->edizione = $row->edizione;
            $rec_tbl_eml_pent_edizioni_corsi_on_line->data_inizio = $row->data_inizio;
            $rec_tbl_eml_pent_edizioni_corsi_on_line->flag_monitorata_S_N = $flag_monitorato_S_N;
            $iaus = EML_Ins_tbl_eml_pent_edizioni_corsi_on_line($rec_tbl_eml_pent_edizioni_corsi_on_line);            
        }
    } // foreach($result as $row)
} //EML_Aggiorna_tbl_eml_pent_edizioni_corso 
function EML_Aggiorna_tbl_eml_pent_moduli_corso ($id_corso, $flag_monitorato_S_N) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - dicembre 2014
 * 
 * Aggiorna la tabella tbl_eml_pent_moduli_corso inserendo le eventuali "nuove" 
 * risorse del corso non ancora presenti in tabella
 * 
 * Restituisce:
 * 
 * Parametri
 *      $id_corso - identificativo del corso da elaborare
 *      $flag_monitorato_S_N - Valore da inserire nel campo posizione_in_report
 * 
 * NOTA: FUNZIONE NON ANCORA SVILUPPATA. Per il momento non fa assolutamente nulla
*/
/*
    global $DB;
    global $mysqli;
    $rec_mdl_course = new EML_RECmdl_course();
    $rec_tbl_eml_pent_moduli_corsi_on_line = new EML_RECtbl_eml_pent_moduli_corsi_on_line();
    // estraggo i dati del corso
    $iaus = EML_Get_mdl_course($id_corso, $rec_mdl_course);

 * IPOTESI DI GESTIONE:
 *  - leggo la situazione moduli.
 *  - leggo quali sarebbero i moduli se si partisse ora (sbaraccare Inserisci_corso_in_monitoraggio)
 *  - se il numero di moduli è cambiato
 *      - cancello i moduli dal monitoraggio
 *      - modifico il vettore con i moduli "nuovi" impostando la posizione in report "vecchia"
 *      - inserisco i moduli in tabella
 *  - fine se
 */   
    // estraggo l'elenco delle risorse/moduli del corso (visibili e monitorabili)
    //      verifico se ci sono risorse/moduli "nuovi"
    //      se necessario le inserisco in tbl_eml_pent_moduli_corsi_on_line
    
    /*
    
    $query = " SELECT D.id as id_edizione"
            .",       ( SELECT fsa.data"
            ."          FROM   mdl_facetoface_session_data fsa"
            ."          WHERE  fsa.fieldid = 9"
            ."            AND  fsa.sessionid = D.id) as edizione"
            .",       FROM_UNIXTIME(E.timestart) As data_inizio"
            ." FROM   mdl_facetoface C"
            ." JOIN   mdl_facetoface_sessions D ON C.id = D.facetoface"
            ." JOIN   mdl_facetoface_sessions_dates E ON D.id = E.sessionid"
            ." WHERE  C.course = ".$id_corso;
    $result = $DB->get_records_sql($query);
    foreach($result as $row) {
        $id_edizione = $row->id_edizione;
        // verifico se l'edizione è già presente in tbl_eml_pent_edizioni_corsi_on_line
        $nome_tabella = 'tbl_eml_pent_edizioni_corsi_on_line';
        $clausola_where = ' WHERE id_corso = '.$id_corso.' AND id_edizione = '.$id_edizione;
        $numero_record = 0;
        $iaus = EML_Get_Numero_record_in_tabella($nome_tabella, $clausola_where, $numero_record);
        if ($numero_record == 0) {
            // edizione "nuova" la inserisco in tbl_eml_pent_edizioni_corso
            $rec_tbl_eml_pent_edizioni_corsi_on_line->id_corso = $id_corso;
            $rec_tbl_eml_pent_edizioni_corsi_on_line->cod_corso = $rec_mdl_course->idnumber;
            $rec_tbl_eml_pent_edizioni_corsi_on_line->titolo_corso = $rec_mdl_course->fullname;
            $rec_tbl_eml_pent_edizioni_corsi_on_line->id_edizione = $row->id_edizione;
            $rec_tbl_eml_pent_edizioni_corsi_on_line->edizione = $row->edizione;
            $rec_tbl_eml_pent_edizioni_corsi_on_line->data_inizio = $row->data_inizio;
            $rec_tbl_eml_pent_edizioni_corsi_on_line->flag_monitorata_S_N = $flag_monitorato_S_N;
            $iaus = EML_Ins_tbl_eml_pent_edizioni_corsi_on_line($rec_tbl_eml_pent_edizioni_corsi_on_line);            
        }
    } // foreach($result as $row)

    */
} //EML_Aggiorna_tbl_eml_pent_moduli_corso
?>
