<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - aprile 2015
 * 
 * GRFO - Gestione Report Formazione On-line
 * 
 * Gestione dei dati di un corso nelle tabelle
 *      tbl_eml_pent_moduli_corsi_on_line
 *      tbl_eml_pent_edizioni_corsi_on_line
 * 
 * Tutte le edizioni del corso sono dichiarate monitorate (flag_monitorato_S_N = 'S')
 * Se il corso ha meno di 10 risorse monitorabili
 *      tutte le risorse sono dichiarate monitorate (posizione_in_report assume i valori 1, 2, ...)
 * altrimenti (più di 10 risosrse)
 *      sono dichiarate monitorate le prime 10 (posizione_in_report assume i valori da 1 a 10)
 *
 */
function Inserisci_corso_in_monitoraggio($id_corso, EML_stato_inserimento $rec_stato_inserimento) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - aprile 2015
 * 
 * Inserisce in base dati le informazioni necessarie al monitoraggio di un corso on-line
 *     - Tabella tbl_eml_pent_edizioni_corsi_on_line
 *     - Tabella tbl_eml_pent_moduli_corsi_on_line
 *
 * In particolare: 
 *      - Tutte le edizioni del corso sono dichiarate monitorate (flag_monitorato_S_N = 'S')
 *      - Se il corso ha meno di 10 risorse monitorabili
 *          - tutte le risorse sono dichiarate monitorate (posizione_in_report assume i valori 1, 2, ...)
 *      - altrimenti (più di 10 risosrse)
 *          - sono dichiarate monitorate 10 risosrse (apparentemente scelte "a caso")
 * 
 * Nel record rec_stato_inserimento sono memorizzate le seguenti informazioni:
 *      - codice corso
 *      - titolo corso
 *      - error code mysql (eventuale) in insert edizioni
 *      - numero edizioni monitorabili
 *      - numero edizioni monitorate
 *      - error code mysql (eventuale) in inserimento moduli
 *      - numero risorse monitorabili
 *      - numero risorse monitorate
 *      - enablecompletion (1 se per il corso è previsto il tracciamento del completamento)
 * 
 * Restituisce:
 *  0   tutto OK
 * -1   Errore in inserimento tabella tbl_eml_pent_edizioni_corsi_on_line
 * -2   Errore in inserimento nella tabella tbl_eml_pent_moduli_corsi_on_line
 * 
 * Parametri
 *     $id_corso - identificativo del corso da gestire
 *     $rec_stato_inserimento - informazioni sul corso inserito
 */ 
    global $DB;
    $rec_mdl_course = new EML_RECmdl_course();
    $rec_tbl_eml_pent_edizioni_corsi_on_line = new EML_RECtbl_eml_pent_edizioni_corsi_on_line();
    // leggo i dati del corso (cod_corso e titolo)
    $iaus = EML_Get_mdl_course($id_corso, $rec_mdl_course);
    $rec_stato_inserimento->id_corso = $id_corso;
    $rec_stato_inserimento->cod_corso = $rec_mdl_course->idnumber;
    $rec_stato_inserimento->titolo_corso = $rec_mdl_course->fullname;
    $rec_stato_inserimento->enablecompletion = $rec_mdl_course->enablecompletion;
    // leggo i moduli/risorse del corso
    $query = " SELECT       A.id as id_modulo, A.instance as istanza_modulo, A.visible as visibile"
            .",             B.name as tipo_modulo"
            ." FROM         mdl_course_modules A"
            ."      JOIN    mdl_modules B ON A.module = B.id"
            ." WHERE        A.course = ".$id_corso;
    $result = $DB->get_records_sql($query);
    $numero_moduli = 0;
    $numero_risorse_monitorabili = 0;
    foreach($result as $row) {
        $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli] = new EML_RECtbl_eml_pent_moduli_corsi_on_line();
        $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->id_corso = $id_corso;
        $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->cod_corso = $rec_stato_inserimento->cod_corso;
        $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->titolo_corso = $rec_stato_inserimento->titolo_corso;
        $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->id_modulo = $row->id_modulo;
        $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->tipo_modulo = $row->tipo_modulo;
        $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->istanza_modulo = $row->istanza_modulo;
        $aus = $row->visibile;
        if ($aus == 1) {
            $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->visibile = EML_PENT_MODULO_VISIBILE;
        } else {
            $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->visibile = EML_PENT_MODULO_NON_VISIBILE;
        }
        $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->tipo_modulo = $row->tipo_modulo;
        $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->posizione_in_report = EML_PENT_MODULO_NON_MONITORATO;
        // Estraggo il nome del modulo (dipende dal tipo modulo e dall'id dell'istanza_modulo)
        $query = " SELECT name"
                ." FROM "
                ." mdl_".$vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->tipo_modulo
                ." WHERE id = ".$vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->istanza_modulo;
        $result1 = $DB->get_records_sql($query);
        foreach($result1 as $row1) {
            $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->nome_modulo = $row1->name;
        }
        // Gestione flag (modulo/risorsa) monitorabile Si/No
        $numero_risorse_monitorabili++;
        $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->monitorabile = EML_PENT_MODULO_MONITORABILE;
        $aus = $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->tipo_modulo;
        if ($aus == EML_PENT_MODULO_FACETOFACE) {
            $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->monitorabile = EML_PENT_MODULO_NON_MONITORABILE;
            $numero_risorse_monitorabili--;
        }
        if ($aus == EML_PENT_MODULO_LABEL) {
            $vet_tbl_eml_pent_moduli_corsi_on_line[$numero_moduli]->monitorabile = EML_PENT_MODULO_NON_MONITORABILE;
            $numero_risorse_monitorabili--;
        }
        $numero_moduli++;
    } // loop sui moduli/risorse del modulo
    // loop di "pulizia" per i moduli "non visibili (se non lo sono li dichiaro non monitorabili)
    for ($i_loop = 0; $i_loop < $numero_moduli; $i_loop++) {
        $visibile = $vet_tbl_eml_pent_moduli_corsi_on_line[$i_loop]->visibile;
        $monitorabile = $vet_tbl_eml_pent_moduli_corsi_on_line[$i_loop]->monitorabile;
        if (($visibile == EML_PENT_MODULO_NON_VISIBILE) AND ($monitorabile == EML_PENT_MODULO_MONITORABILE)) {
            $vet_tbl_eml_pent_moduli_corsi_on_line[$i_loop]->monitorabile = EML_PENT_MODULO_NON_MONITORABILE;
            $numero_risorse_monitorabili--;
        }
    } // loop di "pulizia" per i moduli "non visibili"
    $rec_stato_inserimento->numero_risorse = $numero_moduli;
    $rec_stato_inserimento->numero_risorse_monitorabili = $numero_risorse_monitorabili;
    // Estrazione dell'ordine dei moduli/risorse in funzione dell'appartenenza alle section del corso
    $i_loop = 0;
    $query = " SELECT sequence"
            ." FROM mdl_course_sections"
            ." WHERE course = ".$id_corso
            ." AND sequence IS NOT NULL"
            ." ORDER BY section";
    $result = $DB->get_records_sql($query);
    foreach($result as $row) {
        $aus = $row->sequence;
        $vet_str = explode(',', $aus);
        $num = count($vet_str);
        for ($j_loop = 0; $j_loop < $num; $j_loop++) {
            $vet_progressivo_moduli[$i_loop] = (int) $vet_str[$j_loop];
            $i_loop++;
        }
    } // loop per estrazione dell'ordine dei moduli/risorse
    // Ordinamento dei moduli/risorse in funzione dell'appartenenza alle section del corso
    for ($i_loop = 0; $i_loop < $numero_moduli; $i_loop++) {
        $iaus = $vet_progressivo_moduli[$i_loop];
        for ($j_loop = 0; $j_loop < $numero_moduli; $j_loop++) {
            if ($iaus == $vet_tbl_eml_pent_moduli_corsi_on_line[$j_loop]->id_modulo) {
                $vet_tbl_eml_pent_moduli_corsi_on_line[$j_loop]->progressivo = $i_loop + 1;
                goto prossimo_modulo;
            }
        } // for ($j_loop .....
prossimo_modulo:
    } // for ($i_loop .....
    
    // Scrivo i moduli/risorse nella tabella tbl_eml_pent_moduli_corsi_on_line
    $numero_risorse_monitorate = 0;
    for ($i_loop = 0; $i_loop < $numero_moduli; $i_loop++) {
        if ($vet_tbl_eml_pent_moduli_corsi_on_line[$i_loop]->monitorabile == EML_PENT_MODULO_MONITORABILE) {
            // inserisco il record in tabella tbl_eml_pent_moduli_corsi_on_line
            // Se possibile assegno la posizione in report
            if($numero_risorse_monitorate < EML_PENT_MAX_RISORSE_IN_REPORT) {
                $numero_risorse_monitorate++;
                $vet_tbl_eml_pent_moduli_corsi_on_line[$i_loop]->posizione_in_report = $numero_risorse_monitorate;
            } else {
                //$vet_tbl_eml_pent_moduli_corsi_on_line[$i_loop]->posizione_in_report = EML_PENT_MODULO_MONITORABILE;
                $vet_tbl_eml_pent_moduli_corsi_on_line[$i_loop]->posizione_in_report = EML_PENT_MODULO_NON_MONITORATO;
            }
            $vet_tbl_eml_pent_moduli_corsi_on_line[$i_loop]->flag_punteggio_finale = 0;
            $iaus = EML_Ins_tbl_eml_pent_moduli_corsi_on_line($vet_tbl_eml_pent_moduli_corsi_on_line[$i_loop]);
        }
    }
    $rec_stato_inserimento->numero_risorse_monitorate = $numero_risorse_monitorate;
    // Leggo l'elenco delle edizioni del corso e le inserisco in tabella tbl_eml_pent_edizioni_corsi_on_line
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
    $numero_edizioni = 0;
    foreach($result as $row) {
        $numero_edizioni++;
        $rec_tbl_eml_pent_edizioni_corsi_on_line->id_corso = $id_corso;
        $rec_tbl_eml_pent_edizioni_corsi_on_line->cod_corso = $rec_mdl_course->idnumber;
        $rec_tbl_eml_pent_edizioni_corsi_on_line->titolo_corso = $rec_mdl_course->fullname;
        $rec_tbl_eml_pent_edizioni_corsi_on_line->id_edizione = $row->id_edizione;
        $rec_tbl_eml_pent_edizioni_corsi_on_line->edizione = $row->edizione;
        $rec_tbl_eml_pent_edizioni_corsi_on_line->data_inizio = $row->data_inizio;
        $rec_tbl_eml_pent_edizioni_corsi_on_line->flag_monitorata_S_N = EML_PENT_EDIZIONE_MONITORATA;
        $iaus = EML_Ins_tbl_eml_pent_edizioni_corsi_on_line($rec_tbl_eml_pent_edizioni_corsi_on_line);
    }
    $rec_stato_inserimento->numero_edizioni = $numero_edizioni;
    $rec_stato_inserimento->numero_edizioni_monitorate = $numero_edizioni;
} //Inserisci_corso_in_monitoraggio
?>
