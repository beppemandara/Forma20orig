<?php

require_once '../../config.php';
require_once 'lib.php';

global $PAGE,$USER;

$PAGE->requires->js('/f2_lib/jquery/jquery-1.7.1.min.js');
$PAGE->requires->js('/f2_lib/jquery/jquery-ui.min.js');
$PAGE->requires->js('/f2_lib/jquery/jquery.cookie.js');
$PAGE->requires->js('/f2_lib/jquery/jquery.dynatree.js');
$PAGE->requires->css('/f2_lib/jquery/css/skin/ui.dynatree.css');
$PAGE->requires->js('/f2_lib/jquery/jquery.blockUI.js');

require_login();
$blockid = get_block_id(get_string('pluginname_db','block_f2_report'));
$context = get_context_instance(CONTEXT_BLOCK, $blockid);
require_capability('block/f2_report:viewreport', $context);
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/f2_report/prenotazioni.php');
$PAGE->set_title(get_string('report_pentaho', 'block_f2_report'));
$render_mode   = optional_param('dlcheckbox', 'report', PARAM_TEXT);
$full_path_report   = optional_param('full_path_report', '-1', PARAM_TEXT);
$output_target     = optional_param('output_target', 'table/html;page-mode=page', PARAM_TEXT);
$psent = optional_param('psent', '-1', PARAM_TEXT);
$userid       = optional_param('userid', 0, PARAM_INT);

if($userid==0) $userid=intval($USER->id);

$report_params = array();
if ($psent == '1')
{
	$posted_data = $_POST;

	foreach ($posted_data as $key=>$value) {
		$parname = '';
		$parvalue = '';
		$pattern = '/report_pentaho_param_/';
		if (preg_match($pattern, $key) === 1)
		{
			$parname = preg_replace($pattern, '', $key);
			$report_params[$parname] = $value;
		}
	}
	$num_param_values = count($report_params);
}
else 
{
	$num_param_values = 0;
}

$num_param_report = get_report_num_parameter_partecipazione($full_path_report);

if (($num_param_report == 0) or ($num_param_report == $num_param_values))
{
	$jsstr = <<<'EFO'
<script type="text/javascript">
//<![CDATA[
function resize_iframe(iframeid) {
	var e = document.getElementById(iframeid);
	if (e) {
		resize_iframe_height(e);
	}
}
	
function show_div(divid) {
	var div_notif = document.getElementById(divid);
	if (div_notif) div_notif.removeAttribute('hidden');
}
	
function hide_div(divid) {
	var div_notif = document.getElementById(divid);
	if (div_notif) div_notif.setAttribute('hidden','hidden');
}

function invert_div_visibility(divid)
{
	var hidden =  document.getElementById(divid).getAttribute('hidden','hidden');
	if (hidden) 
	{
		show_div(divid);
	}
	else 
	{
		 hide_div(divid);
	}
}
	
function resize_iframe_width(e) {
	e.width = e.contentDocument.body.offsetWidth + 10;
}
	
function resize_iframe_height(e) {
	e.height = e.contentDocument.body.offsetHeight + 50;
}
//]]>
</script>
EFO;
	echo $jsstr;
	
	if ($full_path_report !== '-1')
	{
		$formato_post = $_POST['output-target'];
		if (!is_null($formato_post) and !empty($formato_post))
		{
			$output_target = $formato_post;
		}
		$param = array(
				'renderMode'=>'report'
				,'output-target'=>$output_target
				,'uid' => $userid);
		foreach ($report_params as $k=>$v)
		{
			//$param[$k] = $v; // modo originale
                    $parametri .= $k.'='.$v.'&';
		}

		//display_report_viewer($full_path_report,$param);
    /* START WORKAROUND */
    // http://pentaho.forma20.it/pentaho/content/reporting/reportviewer/report.html?renderMode=report&output-target=table/html;page-mode=stream&uid=4017&dominio=18&anno_formativo=2013&corso=64&solution=forma20&path=&name=dettaglio_prenotazioni_per_corso.prpt&locale=it_IT
    $cohortid = get_user_cohort($userid);
    $url_base = get_pentaho_new_url_base($cohortid);
    $url_report = get_pentaho_url_report();
    $render = '?renderMode=report&';
    $output = 'output-target='.$output_target.'&';
    $user = 'uid='.$userid.'&';
    $parametri_fissi = 'solution=forma20&path=&name=';
    $lingua = '&locale=it_IT';
    $reportURL = $url_base.$url_report.$render.$output.$user.$parametri.$parametri_fissi.$full_path_report.$lingua;
    header("Location: $reportURL");
    exit;
    //echo $url_base.$url_report.$render.$output.$user.$parametri.$parametri_fissi.$full_path_report.$lingua; 
    //die();
    /* END WORKAROUND */
	}
	else
	{
		echo 'Errore Pentaho 2';
	}
}
else if ($full_path_report !== '-1') // form per prendere i parametri del report 
{
	get_report_form_partecipazione($userid,$next_page,$full_path_report,$output_target);
}
else // non dovrebbe arrivare qui
{
	get_string('no_report_pentaho_available','block_f2_report');
}

echo $OUTPUT->footer();
