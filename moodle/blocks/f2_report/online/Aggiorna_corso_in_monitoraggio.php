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
function EML_Aggiorna_tbl_eml_pent_moduli_corso ($id_corso) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - maggio 2015
 *
 * Aggiorna la tabella tbl_eml_pent_moduli_corsi_on_line con i dati 
 * del corso ricevuto come parametro
 *  
 * Restituisce:
 *      0 -> tutto ok
 * 
 * Parametri
 *      $id_corso - identificativo del corso da elaborare
 * 
 * Principali passi
 *      - Estraggo i dati del corso
 *      - Loop sulle le section del corso
 *          - Estrazione e "split" delle risorse associate alla section
 *          - Loop sulle risorse associate alla section
 *              - Estraggo i dati della risorsa
 *              - Se la risorsa è visibile
 *                  - Verifico se il tipo_modulo è monitorabile
 *                  - Se la risorsa è monitorabile
 *                      - Estraggo il nome del modulo (dipende dal tipo modulo e dall'id dell'istanza_modulo)
 *                      - Aggiungo un record in vettore moduli del corso
 *                  - fine se (risorsa monitorabile)
 *              - fine se (risorsa visibile)
 *          - fine loop (sulle risorse asociate alla section)
 *      - fine loop (sulle sction del corso)
 *      - Valorizzo posizione_in_report e/o flag_punteggio_finale
 *          estranedoli (se presenti) da tbl_eml_pent_moduli_corsi_on_line
 *      - Cancello dalla tabella tbl_eml_pent_moduli_corsi_on_line i dati del corso
 *      - Loop di scrittura in tabella tbl_eml_pent_moduli_corsi_on_line dei moduli del corso
*/
    global $DB;
    $rec_mdl_course = new EML_RECmdl_course();
    $vet_tbl_eml_pent_moduli_corsi_on_line = array();
    // Inizializzazioni
    $numero_moduli = 0;
    // estraggo i dati del corso
    $ret_code = EML_Get_mdl_course($id_corso, $rec_mdl_course);
    // Loop sulle le section del corso
    $query = "SELECT id as sectionid, section, sequence"
            ." FROM mdl_course_sections"
            ." WHERE course = '".$id_corso."'"
            ." AND sequence IS NOT NULL"
            ." ORDER BY section";
    $result = $DB->get_records_sql($query);
    foreach($result as $row) {
        // Estrazione e "split" delle risorse associate alla section
        //$sectionid = $row->sectionid;
        //$section = $row->section;
        $sequence = $row->sequence;
        $vet_risorse = explode(',', $sequence);
        $num = count($vet_risorse);
        // Loop sulle risorse associate alla section
        for ($i_loop = 0; $i_loop < $num; $i_loop++) {
            // Estraggo i dati della risorsa
            $id_course_modules = $vet_risorse[$i_loop];
            $query = "SELECT"
                    ."  A.id as id_modulo, A.instance as istanza_modulo, A.visible"
                    .", B.name as tipo_modulo"
                    ." FROM mdl_course_modules A"
                    ." JOIN mdl_modules B ON A.module = B.id"
                    ." WHERE A.id = ".$id_course_modules;
            $result1 = $DB->get_records_sql($query);
            foreach($result1 as $row1) {
                $id_modulo = $row1->id_modulo;
                $istanza_modulo = $row1->istanza_modulo;
                $visible = $row1->visible;
                $tipo_modulo = $row1->tipo_modulo;                
                if ($visible == 1) {
                    // Se la risorsa è visibile
                    //      Verifico se il tipo_modulo è monitorabile
                    if (    ($tipo_modulo == EML_PENT_MODULO_FACETOFACE) 
                         or ($tipo_modulo == EML_PENT_MODULO_LABEL) ) {
                        $flag_monitorabile = 0;
                    } else {
                        $flag_monitorabile = 1;
                    } // fine verifica se il modulo è monitorabile
                    if($flag_monitorabile == 1) {
                        // Se la risorsa è monitorabile
                        // Estraggo il nome del modulo (dipende dal tipo modulo e dall'id dell'istanza_modulo)
                        $query = "SELECT name FROM mdl_".$tipo_modulo
                                ." WHERE id = ".$istanza_modulo;
                        $result2 = $DB->get_records_sql($query);
                        foreach($result2 as $row2) {
                            $nome_modulo = $row2->name;
                        }
                        //Aggiungo un record in vettore moduli del corso
                        $numero_moduli++;
                        $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli] = new EML_RECtbl_eml_pent_moduli_corsi_on_line();
                        $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->id_corso = $id_corso;
                        $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->cod_corso = $rec_mdl_course->idnumber;
                        $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->titolo_corso = $rec_mdl_course->fullname;
                        $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->progressivo = $numero_moduli;
                        $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->id_modulo = $id_modulo;
                        $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->tipo_modulo = $tipo_modulo;
                        $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->istanza_modulo = $istanza_modulo;
                        $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->nome_modulo = $nome_modulo ;
                        $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->visibile = EML_PENT_MODULO_VISIBILE;
                        $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->monitorabile = EML_PENT_MODULO_MONITORABILE;
                        $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->posizione_in_report = EML_PENT_MODULO_NON_MONITORATO;
                        $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->flag_punteggio_finale = 0;
                    } // fine se (risorsa monitorabile)
                } // fine se (risorsa visibile)
            } // fine loop sui dati della risorsa
        } //fine loop (sulle risorse associate alla section)
    } // fine Loop sulle section del corso
    // Valorizzo posizione_in_report e/o flag_punteggio_finale
    //      estranedoli (se presenti) da tbl_eml_pent_moduli_corsi_on_line
    $query = "SELECT id_modulo, istanza_modulo, posizione_in_report, flag_punteggio_finale"
            ." FROM tbl_eml_pent_moduli_corsi_on_line"
            ." WHERE ( (posizione_in_report > 0) OR (flag_punteggio_finale > 0) )"
            ." AND id_corso = ".$id_corso;
    $result = $DB->get_records_sql($query);
    foreach($result as $row) {
        $id_modulo = $row->id_modulo;
        $istanza_modulo = $row->istanza_modulo;
        $posizione_in_report = $row->posizione_in_report;
        $flag_punteggio_finale = $row->flag_punteggio_finale;
        for ($i_loop = 1; $i_loop <= $numero_moduli; $i_loop++) {
            if (     ($vet_tbl_eml_pent_moduli_corsi_on_line[$i_loop]->id_modulo == $id_modulo)
                 and ($vet_tbl_eml_pent_moduli_corsi_on_line[$i_loop]->istanza_modulo == $istanza_modulo) ) {
                $vet_tbl_eml_pent_moduli_corsi_on_line[$i_loop]->posizione_in_report = $posizione_in_report;
                $vet_tbl_eml_pent_moduli_corsi_on_line[$i_loop]->flag_punteggio_finale = $flag_punteggio_finale;         
                break;
            } // fine valorizzazione posizione_in_report e flag_punteggio_finale del singolo record 
        } // loop di ricera dal modulo da modificare     
    } // fine loop di valorizzazione di posizione_in_report e/o flag_punteggio_finale
    // Cancello dalla tabella tbl_eml_pent_moduli_corsi_on_line i dati del corso
    $nome_tabella = "tbl_eml_pent_moduli_corsi_on_line";
    $clausola_where = " WHERE id_corso = ".$id_corso;
    $ret_code = EML_Del_xxx($nome_tabella, $clausola_where);
    // Loop di scrittura in tabella tbl_eml_pent_moduli_corsi_on_line dei moduli del corso
    for ($i_loop = 1; $i_loop <= $numero_moduli; $i_loop++) {
        $ret_code = EML_Ins_tbl_eml_pent_moduli_corsi_on_line($vet_tbl_eml_pent_moduli_corsi_on_line[$i_loop]);
    }
    return 0;
} //EML_Aggiorna_tbl_eml_pent_moduli_corso
?>