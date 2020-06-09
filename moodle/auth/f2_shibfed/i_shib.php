<?php
/*
 * $Id: i_shib.php 847 2012-12-07 16:29:46Z l.moretto $
 */
defined('MOODLE_INTERNAL') || die();
global $CFG, $SESSION, $PAGE, $DB;

$PAGE->set_url("/auth/f2_shibfed/$path/index.php");
$PAGE->set_context(context_system::instance());

error_reporting(E_ALL);
$username = '';

if (isloggedin() && !isguestuser()) { // Nothing to do
	if (isset($SESSION->wantsurl) && (strpos($SESSION->wantsurl, $CFG->wwwroot) === 0)) {
		$urltogo = $SESSION->wantsurl;    // Because it's an address in this site
		unset($SESSION->wantsurl);
	} 
	else {
		$urltogo = $CFG->wwwroot.'/';     // Go to the standard home page
		unset($SESSION->wantsurl);        // Just in case
	}
	redirect($urltogo);
}

$pluginconfig   = get_config('auth/f2_shibfed');
$shibbolethauth = get_auth_plugin('f2_shibfed');
//$shib_inst = $shibbolethauth->get_shibtype();
//print_r(get_class($shib_inst)."<br/>");
//$shib_inst->printAllHeaders();echo "<br/>";
//print_r($_SERVER);echo "<br/>";die();

// Check whether f2_shibfed is configured properly
//if (empty($pluginconfig->user_attribute)) {
//		print_error('auth_f2_shibfed_not_set_up_error', 'auth_f2_shibfed');
//}

$username = strtolower($shibbolethauth->get_shibtype()->get_user_attribute());
// If we can find the Shibboleth attribute, save it in session and return to main login page
if (!empty($username)) {    // Shibboleth auto-login
	// controllo sulla sospensione dell'utente
	$sosp = check_sospensione($username, $DB);
	if ($sosp == 1) {
		print_error('auth_f2_shibfed_suspended', 'auth_f2_shibfed', '', null, null);die();
	}
	// Check if user can login
	if ($shibbolethauth->user_login($username, '')) {
		$user = authenticate_user_login($username, '');
//print_r($user);die();
		enrol_check_plugins($user);
		session_set_user($user);

		$USER->loggedin = true;
		$USER->site     = $CFG->wwwroot; // for added security, store the site in the user

		update_user_login_times();

		// Don't show previous shibboleth username on login page
		set_login_session_preferences();

		unset($SESSION->lang);
		$SESSION->justloggedin = true;

		add_to_log(SITEID, 'user', 'login', "view.php?id=$USER->id&course=".SITEID, $USER->id, 0, $USER->id);

		if (user_not_fully_set_up($USER)) {
			$urltogo = $CFG->wwwroot.'/user/edit.php?id='.$USER->id.'&amp;course='.SITEID;
			// We don't delete $SESSION->wantsurl yet, so we get there later
		} 
		else if (isset($SESSION->wantsurl) && (strpos($SESSION->wantsurl, $CFG->wwwroot) === 0)) {
			$urltogo = $SESSION->wantsurl;    // Because it's an address in this site
			unset($SESSION->wantsurl);
		} 
		else {
			$urltogo = $CFG->wwwroot.'/';      // Go to the standard home page
			unset($SESSION->wantsurl);         // Just in case
		}
		// Go to my-moodle page instead of homepage if defaulthomepage enabled
		if (!has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM)) 
				&& !empty($CFG->defaulthomepage) 
				&& $CFG->defaulthomepage == HOMEPAGE_MY 
				&& !isguestuser()) {
			if ($urltogo == $CFG->wwwroot || $urltogo == $CFG->wwwroot.'/' || $urltogo == $CFG->wwwroot.'/index.php') {
				$urltogo = $CFG->wwwroot.'/my/';
			}
		}
//print_r($urltogo);echo "<br/>";print_r($urltogo);die();
		redirect($urltogo);
		exit;
	}
	else {
		// User couldn't be authenticated
		print_error('auth_f2_shibfed_could_not_authenticate_error', 'auth_f2_shibfed');
	}
}
// If we can find any (user independent) Shibboleth attributes but no user
// attributes we probably didn't receive any user attributes
elseif (!empty($_SERVER['HTTP_SHIB_APPLICATION_ID']) || !empty($_SERVER['Shib-Application-ID'])) {
	print_error('auth_f2_shibfed_no_attributes_error', 'auth_f2_shibfed' , '', '\''.$pluginconfig->user_attribute.'\', \''.$pluginconfig->field_map_firstname.'\', \''.$pluginconfig->field_map_lastname.'\' && \''.$pluginconfig->field_map_email.'\'');
}
else {
	print_error('auth_f2_shibfed_not_set_up_error', 'auth_f2_shibfed');
}

function check_sospensione($username, $DB) {
	$flag_sospensione = $DB->get_field('user', 'suspended', array("username" => $username));
	return $flag_sospensione;
}
