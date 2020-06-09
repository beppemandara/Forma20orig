<?php
// $Id: select_contratto_formativo.php 1313 2014-09-15 10:07:57Z l.moretto $
/*if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from view.php
}*/
require_once '../../../config.php';

global $DB, $CFG, $USER;

$course = required_param('courseid', PARAM_INT);

require_once($CFG->dirroot.'/lib/pdflib.php');
require_once($CFG->dirroot.'/lib/tcpdf/config/tcpdf_config.php');
require_once($CFG->dirroot.'/lib/accesslib.php');

$context = get_context_instance(CONTEXT_COURSE, $course);
$roles = get_user_roles($context, $USER->id, true);
$canViewAllCF = is_siteadmin();

foreach ($roles as $role) {
	// mettere qui il ruolo referente di direzione (o chi ha visibilità su tutte le edizioni del corso)
	if (strcmp($role->shortname, 'supervisore') === 0 || strcmp($role->shortname, 'supervisoreconsiglio') === 0) { 
		$canViewAllCF = true;
        break;
	}
}

$fromUser 	= ($canViewAllCF) ? '' : ', {facetoface_signups} fc_si ';
$whereUser 	= ($canViewAllCF) ? '' : ' AND fc_si.sessionid = fc_se.id AND fc_si.userid = '.$USER->id.' ';

$editions = $DB->get_records_sql("
		SELECT 
			fc_se.id,
			MIN(fc_sd.timestart) as timestart,
			MAX(fc_sd.timefinish) as timefinish
		FROM 
			{facetoface} fc,
			{facetoface_sessions} fc_se,
			{facetoface_sessions_dates} fc_sd
			$fromUser
		WHERE
			$course = fc.course AND 
			fc.id = fc_se.facetoface AND 
			fc_se.id = fc_sd.sessionid 
			$whereUser
		GROUP BY 
			fc_se.id");

if (!count($editions)) { // NO EDITIONS
	redirect($CFG->wwwroot.'/local/f2_course/pdf/contratto_formativo.php?courseid='.$course);
} else {
	$html = "<form action='$CFG->wwwroot/local/f2_course/pdf/contratto_formativo.php?courseid=$course' method='POST'>";
	$i = 0;
	foreach ($editions as $e) {
		$from = date('d/m/Y', $e->timestart);
		$to = date('d/m/Y', $e->timefinish);
		if (!$i)
			$html .= "<label><input type='radio' name='sessions[]' value='$e->id' checked='checked' /> Edizione $e->id - $from / $to</label><br>";
		else 
			$html .= "<label><input type='radio' name='sessions[]' value='$e->id' /> Edizione $e->id - $from / $to</label><br>";
		$i++;
	}
	$html .= ($canViewAllCF) ? '<input type="hidden" name="visible" value="super" />' : '<input type="hidden" name="visible" value="owner" />';
	$html .= "<input type='submit' value='Download' />";
	$html .= "</form>";

	echo $html;
}