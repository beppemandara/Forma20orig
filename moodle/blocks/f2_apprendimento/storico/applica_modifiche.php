<?php
// $Id$
global $CFG,$DB;

require_once '../../../config.php';
require_once($CFG->dirroot.'/local/f2_support/lib.php');

require_login();
$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('block/f2_apprendimento:modificastorico', $context);

$edizioneid   = required_param('edizioneid', PARAM_INT);
$sort         = optional_param('sort', 'name', PARAM_ALPHANUM);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$edizioneid_storico   = required_param('edizioneid_sto', PARAM_TEXT);
$code_course   = required_param('code_course', PARAM_TEXT);
$data_inizio   = required_param('d_i', PARAM_TEXT);
$count_dati_iscritti_edizione = optional_param('n', 0, PARAM_INT);

$cont = $_POST['cont'];
//print_r($_POST);exit;

//modificare i valori solo in storico perche non esiste nessuna riga sulla tabella mdl_facetoface_signups
if(!$_POST['modifica_solo_storico']){
	$stato_iscrizione= new stdClass();
	$stato_iscrizione->id = $_POST['id_stato_iscrizione'];
	$stato_iscrizione->presenza = $_POST['presenza_'.$cont];
	$stato_iscrizione->va = $_POST['va_iscrizione_'.$cont];
	
	//var_dump($stato_iscrizione);
	$DB->update_record('facetoface_signups_status', $stato_iscrizione);
}
$storico= new stdClass();
$storico->id = $_POST['id_storico'];
$storico->presenza = $_POST['presenza_storico_'.$cont];
$storico->va = $_POST['va_storico_'.$cont];
$storico->cfv = $_POST['cfv_'.$cont];
list($storico->codpart, $storico->descrpart) = get_partecipazione($_POST['partecipazione_'.$cont]);

//var_dump($storico);
$DB->update_record('f2_storico_corsi', $storico);

$returnurl = new moodle_url('/blocks/f2_apprendimento/storico/dettaglio_edizione.php', array('edizioneid' => $edizioneid, 'sort' => $sort, 'dir' => $dir, 'page' => $page,'edizioneid_sto'=>$edizioneid_storico,'course'=>$code_course,'d_i'=>$data_inizio,'n'=>$count_dati_iscritti_edizione));

redirect($returnurl);


