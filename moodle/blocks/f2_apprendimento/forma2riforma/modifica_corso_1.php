<?php
/* A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * CREG - Upload da Riforma
 * 
 * Pagina che gestisce la modifica di un corso
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
$PAGE->set_url('/blocks/f2_apprendimento/forma2riforma/modifica_corso_1.php');
$PAGE->set_title(get_string('f2r_title_modificacorso', 'block_f2_apprendimento'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('f2r_forma2riforma', 'block_f2_apprendimento'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('f2r_header_modificacorso', 'block_f2_apprendimento'));
require_once "strutture_dati.php";
require_once "function_db.php";
require_once "form_definitions.php";
// Preparo i valori da assegnare ai campi della Form che gestisce la modifica dei parametri di un corso
$id = $_REQUEST['id'];
$rec_mdl_f2_forma2riforma_mapping = new EML_RECmdl_f2_forma2riforma_mapping();
$rec_mdl_course = new EML_RECmdl_course();
$ret_code = Get_mdl_f2_forma2riforma_mapping($id, $rec_mdl_f2_forma2riforma_mapping);
$id_forma20 = $rec_mdl_f2_forma2riforma_mapping->id_forma20;
$ret_code = Get_mdl_course_Forma20($id_forma20, $rec_mdl_course);
$valori_campi = array();
$valori_campi['id'] = $id;
$valori_campi['shortname'] = $rec_mdl_f2_forma2riforma_mapping->shortname;
$valori_campi['id_forma20'] = $rec_mdl_f2_forma2riforma_mapping->shortname." - ".$rec_mdl_course->fullname;
$valori_campi['perc_x_cfv'] = $rec_mdl_f2_forma2riforma_mapping->perc_x_cfv;
$valori_campi['va_default'] = $rec_mdl_f2_forma2riforma_mapping->va_default;
$valori_campi['nota'] = $rec_mdl_f2_forma2riforma_mapping->nota;
// Form che gestisce la modifica dei parametri di un corso
$mform = new form_modifica_corso(NULL, $valori_campi);
if ($mform->is_cancelled()) {
    // se form cancellata torno a maschera con elenco corsi in gestione
    $elenco_corsi_url = new moodle_url('elenco_corsi.php');
    redirect($elenco_corsi_url, null);
} else if ($fromform = $mform->get_data()) { 
    // carico in variabili locali i dati letti dalla forma
    $new_perc_x_cfv = (int) $fromform->perc_x_cfv;
    $new_va_default = $fromform->va_default;
    // preparo il record per l'update
    $flag_update = 0;
    $record_update = new stdClass();
    $record_update->id = $rec_mdl_f2_forma2riforma_mapping->id;
    // se cambiato perc_x_cfv aggiorno il record in tabella mdl_f2_forma2riforma_mapping
    if ($rec_mdl_f2_forma2riforma_mapping->perc_x_cfv <> $new_perc_x_cfv) {
        $flag_update++;
        $record_update->perc_x_cfv = $new_perc_x_cfv;
    }
    // se cambiato va_default aggiorno il record in tabella mdl_f2_forma2riforma_mapping
    if ($rec_mdl_f2_forma2riforma_mapping->va_default <> $new_va_default) {
        $flag_update++;
        $record_update->va_default = $new_va_default;
    }
    if ($flag_update > 0) {
        // modificato uno o più campi:
        // aggiorno il record in tabella mdl_f2_forma2riforma_mapping
        $aus = get_string('f2r_update_ok', 'block_f2_apprendimento');
        $record_update->nota = $aus;
        $DB->update_record('f2_forma2riforma_mapping', $record_update);
        //  scrivo messaggio in tabella di log
        $record_log = new stdClass();
        $record_log->shortname = $rec_mdl_f2_forma2riforma_mapping->shortname;
        $record_log->data_ora = time();
        $record_log->codice = EML_RIFORMA_UPD_MAPPING;
        $aus = get_string('f2r_modificato_collegamento', 'block_f2_apprendimento');
        $aus .= " - perc_x_cfv: ".$new_perc_x_cfv."% - va_default: ".$new_va_default;
        $record_log->descrizione = $aus;
        $lastinsertid = $DB->insert_record('f2_forma2riforma_log', $record_log);
    }
    // redirect alla pagina con elenco corsi in gestione
    $elenco_corsi_url = new moodle_url('elenco_corsi.php');
    redirect($elenco_corsi_url, null);
}
// Visualizzazione/gestione form
$mform->display();
// Chiusura pagina (secondo standard Moodle)
echo $OUTPUT->footer();
?>