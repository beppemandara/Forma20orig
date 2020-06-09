<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

/**
 * Inserisce un record di log
 * @param array datilog
 * @return bool
 */
function ins_log_object($datilog) {
    global $CFG, $DB;
    $logdata = new stdClass();
    $logdata->azione = $datilog[0];
    $logdata->data = time();
    $logdata->msg = $datilog[1];
    $inslog = $DB->insert_record('block_formindbudget_log', $logdata);
    return $inslog;
}
/**
 * Inserisce un record di storico per il budget
 * @param array datibudget
 * @return bool
 */
function ins_storico_budget($datibudget) {
    global $CFG, $DB;
    $storico = new stdClass();
    $storico->annoriferimento = $datibudget[0];
    $storico->valorebudget = $datibudget[1];
    $storico->inseritoda = $datibudget[2];
    $storico->datainserimento = time();
    $inshistory = $DB->insert_record('block_formindbudget_storico', $storico);
    return $inshistory;
}
/**
 * Restituisce l'id del budget per l'anno corrente
 * @param int $anno anno corrente
 * @return int budget value
 */
function get_buget_id($anno) {
    global $CFG, $DB;
    if ($record = $DB->get_record('block_formindbudget', array('anno' => $anno), 'id')) {
        return $record->id;
    }
    return false;
}
/**
 * Restituisce il budget per l'anno corrente
 * @param int $anno anno corrente
 * @return int budget value
 */
function get_budget_anno_corrente($anno) {
    global $CFG, $DB;
    if ($budgetvalue = $DB->get_record('block_formindbudget', array('anno' => $anno), 'budget')) {
        return $budgetvalue->budget;
    }
    return 'nobudgetfound';
}
/**
 * Restituisce le note del budget per l'anno corrente
 * @param int $anno anno corrente
 * @return string note budget
 */
function get_note_anno_corrente($anno) {
    global $CFG, $DB;
    if ($notebudget = $DB->get_record('block_formindbudget', array('anno' => $anno), 'note')) {
        return $notebudget->note;
    }
    return '';
}
/**
 * Restituisce l'id  del budget per l'anno corrente
 * @param int $anno anno corrente
 * @return int id budget
 */
function get_budget_id($anno) {
    global $CFG, $DB;
    if ($budgetid = $DB->get_record('block_formindbudget', array('anno' => $anno), 'id')) {
        return $budgetid->id;
    }
    return 'noidfound';
}
/**
 * Restituisce la situazione contabile per l'anno corrente
 * @param int $anno anno corrente
 * @return int budget residuo value
 */
function get_situazione_contabile($anno) {
    global $CFG, $DB;
    $budgetanno = get_budget_anno_corrente($anno);
    if ($budgetanno == 'nobudgetfound') {
        $budgetanno = 0;
    }
    $totsumbudgetdir = get_sum_budgets($anno);
    $sitcontab = $budgetanno - $totsumbudgetdir;
    return $sitcontab;
}
/**
 * Restituisce il livello (1,2,3,4) del dominio di visibilita' di appartenenza dell'utente passato come parametro
 * @param int $user_id id dell'utente
 * @return int
 */
function get_livello_dominio_utente($userid) {
    global $CFG, $DB;
    $select = "SELECT depthid as level
               FROM {$CFG->prefix}org_assignment oa
               JOIN {$CFG->prefix}org org ON org.id=oa.viewableorganisationid
               WHERE userid = $userid";
    $viewableorg = $DB->get_record_sql($select);
    if ($viewableorg) {
        return $viewableorg->level;
    } else {
        return -1;
    }
}
/**
 * Restituisce true se l'utente passato come parametro e' un Supervisore di primo o secondo livello
 * @param int $user_id id dell'utente
 * @return bool
 */
function areyousupervisor($userid) {
    $level = get_livello_dominio_utente($userid);
    if (($level == 1) || ($level == 2)) {
        return true;
    } else {
        return false;
    }
}
/**
 * Get instruction string
 * @return string
 */
function get_instructions() {
    $istruzioni = '<div><p><strong>Istruzioni del blocco Budget Formazione Individuale</strong></p></div>';
    $istruzioni .= '<div><p>Il modulo permette l&rsquo;<strong>inserimento</strong> e la <strong>modifica</strong> ';
    $istruzioni .= 'del budget annuale unico con relativo <strong>report</strong> in formato xls ';
    $istruzioni .= '(ed eventuale <strong>download</strong>)</p></div>';
    $istruzioni .= '<div><p>All&rsquo;inizio dell&rsquo;anno quando non &egrave; stato ancora impostato il budget ';
    $istruzioni .= 'la schermata visualizzata presenter&agrave; l&rsquo;anno in corso, l&rsquo;avviso relativo ';
    $istruzioni .= 'all&rsquo;assenza del budget, la situazione contabile (<u>budget - spesa per i corsi con e senza ';
    $istruzioni .= 'determina</u>), la spesa complessiva (<u>il costo dei corsi con e senza determina</u>), due bottoni ';
    $istruzioni .= '<strong>Inserisci budget annuo</strong> e <strong>Report</strong> ed il riepilogo ';
    $istruzioni .= 'per Direzione del costo dei corsi con e senza determina.</p></div>';
    $istruzioni .= '<div><p><strong>Per inserire il valore del budget</strong> occorre fare click sul bottone ';
    $istruzioni .= '<strong>Inserisci budget annuo</strong> che rimanda alla pagina di inserimento del budget ';
    $istruzioni .= 'e delle eventuali note, inseriti i valori desiderati fare click sul pulsante ';
    $istruzioni .= '<strong>Inserisci budget annuo</strong> quindi si verr&agrave; indirizzati sulla pagina di ';
    $istruzioni .= 'riepilogo della situazione budget con un messaggio sull&rsquo;esito dell&rsquo;inserimento.</p></div>';
    $istruzioni .= '<div><p><strong>Per modificare il valore del budget</strong> occorre fare click sul bottone ';
    $istruzioni .= '<strong>Modifica budget annuo</strong> che rimanda alla pagina di modifica del budget ';
    $istruzioni .= 'e delle eventuali note, inseriti i valori modificati fare click sul pulsante ';
    $istruzioni .= '<strong>Modifica budget annuo</strong> quindi si verr&agrave; indirizzati sulla pagina di ';
    $istruzioni .= 'riepilogo della situazione budget con un messaggio sull&rsquo;esito della modifica.</p></div>';
    $istruzioni .= '<div><p><strong>Per visualizzare il report</strong> della situazione budget occorre fare click sul ';
    $istruzioni .= 'bottone <strong>Report</strong> che rimanda alla pagina di scelta dell&rsquo;anno per cui si vuole ';
    $istruzioni .= 'visualizzare il report, selezionato l&rsquo;anno occorre fare click sul bottone ';
    $istruzioni .= '<strong>Vai al report</strong> quindi si verr&agrave; indirizzati sulla pagina del report, dove in ';
    $istruzioni .= 'alto a sinistra troviamo il link <strong>Scarica il report</strong> per ottenere il <u>download</u> ';
    $istruzioni .= 'del file in formato <u>excel</u></p></div>';
    return $istruzioni;
}
/**
 * Prepare update data obj
 * @param number id record budget
 * @param obj budget data
 * @return object
 */
function get_update_data($idrecord, $budgetdata) {
    global $USER;
    $updatedata = new stdClass();
    $updatedata->id = $idrecord;
    $updatedata->anno = $budgetdata->anno;
    $updatedata->budget = $budgetdata->budget;
    $updatedata->modificatoda = $USER->id;
    $updatedata->datamodifica = time();
    $updatedata->note = $budgetdata->note;
    return $updatedata;
}
/**
 * Form data sanitize
 * @param number budget value
 * @return object
 */
function sanitize_data($fromform, $whatdo) {
    global $USER;
    $budgetdata = new stdClass();
    $budgetdata->anno = trim($fromform->year);
    $punteggiatura = array(',', '.', ';');
    $valint = str_replace($punteggiatura, '', trim($fromform->totbudget));
    $valdec = str_replace($punteggiatura, '', trim($fromform->decimali));
    $budgetdata->budget = $valint.'.'.$valdec;
    if ($whatdo == 'inserimento') {
        $budgetdata->inseritoda = $USER->id;
        $budgetdata->datainserimento = time();
    }
    if ($whatdo == 'modifica') {
        $budgetdata->modificatoda = $USER->id;
        $budgetdata->datamodifica = time();
    }
    $budgetdata->note = substr(trim($fromform->note), 0, 500);
    return $budgetdata;
}
/**
 * Form data check
 * @param obj budget values
 * @return code string
 */
function check_data($budgetdata) {
    $code = 'ok';
    if (($budgetdata->anno == '') || (empty($budgetdata->anno))) {
        $code = 1;
    }
    if (($budgetdata->budget == '') || (empty($budgetdata->budget))) {
        $code = 2;
    } else {
        $pattern = '/\b\d{1,3}(?:,?\d{3})*(?:\.\d{2})?\b/';
        $validbudget = preg_match($pattern, $budgetdata->budget);
        if ($validbudget != 1) {
            $code = 3;
        }
    }
    return $code;
}
/**
 * From error code to error string
 * @param string error code
 * @return string
 */
function code2error($code) {
    if ($code && $code >= 1) {
        switch ($code) {
            case 1:
                $detterr = get_string('annobudgetvuoto', 'block_formindbudget');
                break;
            case 2:
                $detterr = get_string('valorebudgetvuoto', 'block_formindbudget');
                break;
            case 3:
                $detterr = get_string('valorebudgetinvalido', 'block_formindbudget');
                break;
            case 4:
                $detterr = get_string('budgetmodificatoda', 'block_formindbudget');
                break;
            case 5:
                $detterr = get_string('datainsbudgetvuota', 'block_formindbudget');
                break;
            default:
                break;
        }
    } else {
        $detterr = get_string('nocode', 'block_formindbudget');
    }
    return $detterr;
}
/**
 * Get budget value by category string
 * @param int $annoincorso anno corrente
 * @return string
 */
function get_direzioni_and_budget($annoincorso) {
    global $CFG, $DB;
    if (empty($annoincorso) || $annoincorso == '' || $annoincorso <= 0) {
        $annoincorso = date("Y");
    }
    $datada = '(SELECT UNIX_TIMESTAMP(\''.$annoincorso.'-01-01 00:00:00\'))';
    $dataa = '(SELECT UNIX_TIMESTAMP(\''.$annoincorso.'-12-31 23:59:59\'))';
    // Id parent = 26 => id radice per Giunta.
    $select = "SELECT o.id, o.shortname, o.fullname
               FROM mdl_org o
               WHERE o.depthid = 3 AND o.parentid = 26 AND o.visible = 1
               ORDER BY o.shortname ASC";
    if ($direzioni = $DB->get_records_sql($select)) {
        foreach ($direzioni as $direz) {
            // Tipo pianificazione = 4 => corsi individuali, parametro p_f2_tipo_pianificazione.
            // Somma dei budget per direzione con determina.
            $sqlsomma = "SELECT ifnull(SUM(A.costo), 0) as somma_costi
                         FROM mdl_f2_corsiind A
                         WHERE A.tipo_pianificazione = 4
                         AND A.orgfk = $direz->id
                         AND A.id_determine > 0
                         AND A.data_inizio BETWEEN $datada AND $dataa";
            if (!$daticosto = $DB->get_record_sql($sqlsomma)) {
                 $daticosto->somma_costi = 'n.a.';
            }
            // Somma dei budget per direzione senza determina.
            $sqlsumsd = "SELECT ifnull(SUM(A.costo), 0) as somma_costi_sd
                         FROM mdl_f2_corsiind A
                         WHERE A.tipo_pianificazione = 4
                         AND A.orgfk = $direz->id
                         AND A.id_determine = 0
                         AND A.data_inizio BETWEEN $datada AND $dataa";
            if (!$daticostosd = $DB->get_record_sql($sqlsumsd)) {
                 $daticostosd->somma_costi_sd = 'n.a.';
            }
            // Numero corsi totale.
            $sqltotcorsi = "SELECT COUNT(A.id) as num_corsi_tot
                            FROM mdl_f2_corsiind A
                            WHERE A.tipo_pianificazione = 4
                            AND A.orgfk = $direz->id
                            AND A.data_inizio BETWEEN $datada AND $dataa";
            $nctot = $DB->get_record_sql($sqltotcorsi);
            // Numero corsi con spesa.
            $sqlccs = "SELECT COUNT(A.id) as num_corsi_spesa
                       FROM mdl_f2_corsiind A
                       WHERE A.tipo_pianificazione = 4
                       AND A.orgfk = $direz->id
                       AND A.costo > 0
                       AND A.data_inizio BETWEEN $datada AND $dataa";
            $nccs = $DB->get_record_sql($sqlccs);
            // Numero corsi con cassa.
            $sqlccc = "SELECT COUNT(A.id) as num_corsi_cassa
                       FROM mdl_f2_corsiind A
                       WHERE A.tipo_pianificazione = 4
                       AND A.orgfk = $direz->id
                       AND A.cassa_economale > 0
                       AND A.data_inizio BETWEEN $datada AND $dataa";
            $nccc = $DB->get_record_sql($sqlccc);
            // Costruzione array dei risultati.
            $datidirezioni[] = array(
                                     'id' => $direz->id,
                                     'direzione' => $direz->shortname.' - '.$direz->fullname,
                                     'costocd' => $daticosto->somma_costi,
                                     'costosd' => $daticostosd->somma_costi_sd,
                                     'nctot' => $nctot->num_corsi_tot,
                                     'nccs' => $nccs->num_corsi_spesa,
                                     'nccc' => $nccc->num_corsi_cassa
                                    );
        }
        return $datidirezioni;
    } else {
        return false;
    }
}
/**
 * Get budget's total courses
 * @param int $annoincorso anno corrente
 * @return number budget's tot courses
 */
function get_tot_corsi($anno, $opzioni = '') {
    global $CFG, $DB;
    if (empty($anno) || $anno == '' || $anno <= 0) {
        return $totcorsi->num_corsi_tot = 'n.d.';
    }
    $datada = '(SELECT UNIX_TIMESTAMP(\''.$anno.'-01-01 00:00:00\'))';
    $dataa = '(SELECT UNIX_TIMESTAMP(\''.$anno.'-12-31 23:59:59\'))';
    // Id parent = 26 => id radice per Giunta.
    // Tipo pianificazione = 4 => corsi individuali, parametro p_f2_tipo_pianificazione.
    $sqltotcorsi = "SELECT COUNT(A.id) as num_corsi_tot
                    FROM mdl_f2_corsiind A
                    WHERE A.tipo_pianificazione = 4
                    $opzioni
                    AND A.orgfk IN (
                                    SELECT o.id
                                    FROM mdl_org o
                                    WHERE o.depthid = 3
                                    AND o.parentid = 26
                                    AND o.visible = 1
                                   )
                    AND A.data_inizio BETWEEN $datada AND $dataa";
    if (!$totcorsi = $DB->get_record_sql($sqltotcorsi)) {
        $totcorsi->num_corsi_tot = 'n.a.';
    }
    return $totcorsi->num_corsi_tot;
}
/**
 * Get budget's sums number
 * @param int $annoincorso anno corrente
 * @return number budget's sums
 */
function get_sum_budgets($annoincorso, $determine = '') {
    global $CFG, $DB;
    if (empty($annoincorso) || $annoincorso == '' || $annoincorso <= 0) {
        $annoincorso = date("Y");
    }
    $datada = '(SELECT UNIX_TIMESTAMP(\''.$annoincorso.'-01-01 00:00:00\'))';
    $dataa = '(SELECT UNIX_TIMESTAMP(\''.$annoincorso.'-12-31 23:59:59\'))';
    // Id parent = 26 => id radice per Giunta.
    // Tipo pianificazione = 4 => corsi individuali, parametro p_f2_tipo_pianificazione.
    $sqlsumtot = "SELECT ifnull(SUM(A.costo), 0) as sommabudget
                  FROM mdl_f2_corsiind A
                  WHERE A.tipo_pianificazione = 4
                  $determine
                  AND A.orgfk IN (
                                  SELECT o.id
                                  FROM mdl_org o
                                  WHERE o.depthid = 3
                                  AND o.parentid = 26
                                  AND o.visible = 1
                                 )
                  AND A.data_inizio BETWEEN $datada AND $dataa";
    if (!$totspesa = $DB->get_record_sql($sqlsumtot)) {
        $totspesa->sommabudget = 'n.a.';
    }
    return $totspesa->sommabudget;
}
/**
 * Get message result
 * @param string message
 * @return string formatted message
 */
function get_result_message($testo) {
    $msg = html_writer::start_tag('div');
    $msg .= html_writer::start_tag('h3');
    $msg .= $testo;
    $msg .= html_writer::end_tag('h3');
    $msg .= html_writer::end_tag('div');
    return $msg;
}
/**
 * Get anni budget
 * @return obj anni
 */
function get_anni_budget() {
    global $CFG, $DB;
    if ($anni = $DB->get_records('block_formindbudget', null, 'anno DESC', 'anno')) {
        return $anni;
    } else {
        $anni = array('2018' => '2018');
        return $anni;
    }
    //return false;
}
