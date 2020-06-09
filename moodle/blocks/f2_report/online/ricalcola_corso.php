<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - aprile 2015
 * 
 * GRFO - Gestione Report Formazione On-line
 * 
 * Ricalcolo dei dati di monitoraggio relativi ad un corso on-line
 * tabelle:
 *      tbl_eml_pent_monitoraggio_corsi_on_line
 *      tbl_eml_pent_completamento_corsi_on_line
*/
global $USER;
//global $CFG;
//global $mysqli;
//global $EML_CFG;
require_once '../../../config.php';
require_login();
$context = get_context_instance(CONTEXT_SYSTEM);
require_once "costanti.php";
require_once "costanti_db.php";
require_once "strutture_dati.php";
require_once "function_db.php";
require_once '../../../rpfmhrbat/strutture_dati.php';
require_once '../../../rpfmhrbat/costanti.php';
//require_once '../../../rpfmhrbat/costanti_db.php';
require_once '../../../rpfmhrbat/variabili_globali.php';
require_once '../../../rpfmhrbat/connessione_al_db.php';
require_once '../../../rpfmhrbat/function_db.php';
require_once '../../../rpfmhrbat/function_varie.php';
require_once '../../../rpfmhrbat/function_gestione_tabelle_Pentaho.php';
require_once '../../../rpfmhrbat/function_gestione_tabelle_report_monitoraggio_corsi_on_line.php';
$rec_tbl_eml_grfo_log = new EML_RECtbl_eml_grfo_log();
$rec_tbl_eml_grfo_feed_back = new EML_RECtbl_eml_grfo_feed_back();
$rec_mdl_course = new EML_RECmdl_course();
$id_corso = $_REQUEST['id_corso'];
$connessione = EML_Connetti_db();
$param_conf = EML_Leggi_mdl_f2_parametri();
// a.a. - aprile 2015 
// attivo la function di ricalcolo monitoraggio corsi on-line
// attivo (anche) la function di ricalcolo completamento corsi on-line
$livello_log = 0;
$flag_delete = "S";
$ret_code = EML_Aggiorna_corso_tbl_eml_pent_monitoraggio_corsi_on_line($livello_log, $id_corso, $flag_delete);
$ret_code = EML_Aggiorna_corso_tbl_eml_pent_completamento_corsi_on_line($livello_log, $id_corso, $flag_delete);
// messaggio di ricalcolo effettuato
// estraggo i dati delcorso
$iaus = EML_Get_mdl_course($id_corso, $rec_mdl_course);
$rec_tbl_eml_grfo_log->id = NULL;
$rec_tbl_eml_grfo_log->data = NULL;
$rec_tbl_eml_grfo_log->id_corso = $id_corso;
$rec_tbl_eml_grfo_log->cod_corso = $rec_mdl_course->idnumber;
$rec_tbl_eml_grfo_log->titolo_corso = $rec_mdl_course->fullname;
$rec_tbl_eml_grfo_log->pagina = 'Ricalcola_corso.php';
$rec_tbl_eml_grfo_log->livello_msg = EML_MSG_CALCOLI;
$rec_tbl_eml_grfo_log->cod_msg = EML_MSG_RICALCOLO_CORSO;
$rec_tbl_eml_grfo_log->descr_msg = get_string('grfo_ricalcolo_corso', 'block_f2_report');
$rec_tbl_eml_grfo_log->username = $USER->username;
$aus = $USER->idnumber." - ".$USER->lastname.", ".$USER->firstname;
$rec_tbl_eml_grfo_log->utente = $aus;
$rec_tbl_eml_grfo_log->nota = ' ';
$ret_code = EML_Ins_tbl_eml_grfo_log($rec_tbl_eml_grfo_log);
// preparo il record per la pagina di feed-back
// lo inserisco in base dati (tabella tbl_eml_grfo_feed_back)
// e vado alla pagina di feed-back
$rec_tbl_eml_grfo_feed_back->id = NULL;
$rec_tbl_eml_grfo_feed_back->id_corso = $id_corso;
$rec_tbl_eml_grfo_feed_back->cod_corso = $rec_mdl_course->idnumber;
$rec_tbl_eml_grfo_feed_back->titolo_corso = $rec_mdl_course->fullname;
$rec_tbl_eml_grfo_feed_back->operazione = get_string('grfo_ricalcolo_corso', 'block_f2_report');
$rec_tbl_eml_grfo_feed_back->stato = get_string('grfo_feed_back_tutto_ok', 'block_f2_report');
$rec_tbl_eml_grfo_feed_back->url = 'report_on_line.php';
$rec_tbl_eml_grfo_feed_back->flag_parametro_id_corso = get_string('grfo_feed_back_NO_parametro_id_corso', 'block_f2_report');
$rec_tbl_eml_grfo_feed_back->nota = ' ';
$id_x_pagina_feed_back = EML_Ins_tbl_eml_grfo_feed_back($rec_tbl_eml_grfo_feed_back);
$url_pagina_feed_back = new moodle_url('feed_back_page.php', array('id'=>$id_x_pagina_feed_back));
$delay = 1;
redirect($url_pagina_feed_back, null, $delay);
?>