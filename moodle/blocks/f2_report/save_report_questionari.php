<?php
//$Id$
global $CFG,$USER,$COURSE,$DB,$PAGE;
require_once '../../config.php';
require_once $CFG->libdir . '/formslib.php';
require_once 'lib.php';

//global $PAGE;
$PAGE->requires->js(new moodle_url('/blocks/f2_report/lib_report.js'),true);

require_login();
$blockid = get_block_id(get_string('pluginname_db','block_f2_report'));
$context = get_context_instance(CONTEXT_BLOCK, $blockid);
require_capability('block/f2_report:viewreport', $context);

$userid          = optional_param('userid', 0, PARAM_INT);
$next_page       = optional_param('next_page','get_report_questionari_pentaho.php', PARAM_TEXT);
$selected_report = optional_param('selected_report','-1', PARAM_TEXT);

if($userid==0) $userid=intval($USER->id);
else if($userid!=0 && has_capability('block/f2_report:viewreport', $context) && validate_own_dipendente($userid)) $userid=$userid;
else die();

$baseurl = new moodle_url('/blocks/f2_report/report_questionari.php');
$PAGE->navbar->add(get_string('report_pentaho', 'block_f2_report'), $baseurl);

$blockname = get_string('pluginname', 'block_f2_report');
$PAGE->set_pagelayout('standard');
$PAGE->set_context($context);
$PAGE->set_url('/blocks/f2_report/prenotazioni.php');
$PAGE->set_title(get_string('report_pentaho', 'block_f2_report'));
$PAGE->settingsnav;
$PAGE->set_heading($SITE->shortname.': '.$blockname);

$userdata = get_user_data($userid);
$direzione = get_direzione_utente($USER->id);
$settore = get_settore_utente($USER->id);
// TABELLA DATI ANAGRAFICI
$table = new html_table();
$table->align = array('right', 'left');
$table->data = array(
		array('Cognome Nome ','<b>'.$userdata->lastname.' '.$userdata->firstname.'</b>'),
		array('Matricola',''.$userdata->idnumber.''),
		array('Categoria',''.$userdata->category.''),
		array('Direzione / Ente',''.is_null($direzione) ? '' : $direzione['shortname']." - ".$direzione['name'].''),
		array('Settore',''.is_null($settore) ? '' : $settore['name'].'')
);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('report_pentaho', 'block_f2_report'));
echo $OUTPUT->box_start();

echo html_writer::table($table);

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

//$form = get_report_form($userid,$next_page,$selected_report);
$form = get_report_form_questionari($userid,$next_page,$selected_report); // 15/07/2014
echo $form;
echo $OUTPUT->box_end();
echo $OUTPUT->footer();