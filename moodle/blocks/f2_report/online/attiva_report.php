<?php
/*
 * A. Albertin, G. Mandara - CSI Piemonte - gennaio 2015
 * 
 * GRFO - Gestione Report Formazione On-line
 * 
 * Pagina che attiva il report pentaho di monitoraggio formazione on-line
 * 
 * Principali passi:
 * 
 */
// Inizializzazioni "varie" secondo standard Moodle
global $PAGE,$USER;
require_once '../../../config.php';
require_once '../lib.php';
require_login();
//$context = get_context_instance(CONTEXT_SYSTEM);
/*
$blockid = get_block_id(get_string('pluginname_db','block_f2_report'));
$context = get_context_instance(CONTEXT_BLOCK, $blockid);
require_capability('block/f2_report:online', $context);

$userid = optional_param('userid', 0, PARAM_INT);
if($userid==0) $userid=intval($USER->id);

$cohortid = get_user_cohort($userid);
$url_base = get_pentaho_new_url_base($cohortid);
$url_report = get_pentaho_url_report();
$render = '?renderMode=report&';
$output = 'output-target='.$output_target.'&';
$user = 'uid='.$userid.'&';
$parametri_fissi = 'solution=forma20&path=&name=';
$lingua = '&locale=it_IT';
$reportURL = $url_base.$url_report.$render.$output.$user.$parametri.$parametri_fissi.$full_path_report.$lingua;
*/
$reportURL = 'http://pentaho.forma20.it/pentaho/content/reporting/reportviewer/report.html?solution=forma20&path=&name=fruizione_formazione_on_line_10risorse.prpt&locale=it_IT';
header("Location: $reportURL");
exit;

?>
