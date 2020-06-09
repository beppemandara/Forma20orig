<?php

/**
 * $Id: auth.php 847 2012-12-07 16:29:46Z l.moretto $
 * 
 * @author l.moretto
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package moodle auth
 *
 * Authentication Plugin: CSI ShibFed plugin
 * TODO: manage logout, manage all shibtype url
 * 
 *
 * 2012-11-08  File created.
 */

if (!defined('MOODLE_INTERNAL')) {
	die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/authlib.php');
require_once($CFG->dirroot.'/auth/f2_shibfed/lib.php');

/**
 * Authentication plugin for CSI Shibboleth Federation.
 */
class auth_plugin_f2_shibfed extends auth_plugin_base {

	const LOGOUT_HANDLER = "/Shibboleth.sso/Logout";
	
	private $shibtype;
	
	/**
	 * Constructor.
	 */
	function auth_plugin_f2_shibfed() {
		$this->authtype = 'f2_shibfed';
		$this->config = get_config('auth/f2_shibfed');
	}
	/**
	 * Shibtype getter 
	 */
	public function get_shibtype()
	{
		//Try to create an instance if shibtype property is empty.
		if( empty($this->shibtype) && isset($_SERVER['REQUEST_URI']) ) {
			$this->shibtype = ShibFactory::Create($_SERVER['REQUEST_URI']);
		}
		return $this->shibtype;
	}
	/**
	 * Shibtype setter 
	 */
	public function set_shibtype($shibtype_inst)
	{
		$this->shibtype = $shibtype_inst;
	}
	/**
	 * Returns true if the username exist and false otherwise.
	 *
	 * @param string $username The username
	 * @param string $password The password
	 * @return bool Authentication success or failure.
	 */
	function user_login($username, $password) {
		global $SESSION;
		$success = FALSE;

		$shib = $this->get_shibtype();
		if( !empty($shib) ) {
			// If we are in the shibfed directory then we trust the HTTP header var
			$header = $shib->get_user_attribute();
//print_r($header);echo "<br/>";
//print_r($this->shibtype->check());die();
			if (!empty($header) && $shib->check() ) {
				// Associate Shibboleth session with user for SLO preparation
				$sessionkey = '';
				if (isset($_SERVER['Shib-Session-ID'])) {
					// This is only available for Shibboleth 2.x SPs
					$sessionkey = $_SERVER['Shib-Session-ID'];
				} 
				else {
					// Try to find out using the user's cookie
					foreach ($_COOKIE as $name => $value) {
						if (preg_match('/_shibsession_/i', $name)) {
								$sessionkey = $value;
						}
					}
				}

				// Set shibboleth session ID for logout
				$SESSION->shibboleth_session_id = $sessionkey;
				// Set shibboleth logout path
//print_r($shib->get_logout_path());die();
				$lo = $shib->get_logout_path();
//print_r(empty($lo));die();
				if( !empty($lo) )
					$SESSION->shibboleth_logoutpath = $lo;

				$success = (strtolower($header) == strtolower($username));
//print_r($success); die();
			}
		}
		return $success;
	}

	/**
		* Updates the user's password.
		*
		* called when the user password is updated.
		*
		* @param  object  $user        User table object
		* @param  string  $newpassword Plaintext password
		* @return boolean result
		*
		
    function user_update_password($user, $newpassword) {
        $user = get_complete_user_data('id', $user->id);
        return update_internal_user_password($user, $newpassword);
    }
	  */

	/**
		* Returns true if this authentication plugin is 'internal'.
		*
		* @return bool
		*/
	function is_internal() {
		return false;
	}

	/**
		* Hook for login page
		*/
	function loginpage_hook() {
		global $SESSION, $CFG;

		// Prevent username from being shown on login page after logout
		$CFG->nolastloggedin = true;
		return;
	}

	/**
		* Hook for logout page
		*/
	function logoutpage_hook() {
		global $CFG, $SESSION, $redirect;

		$lo = '';
		if (isset($SESSION->shibboleth_session_id) && !empty($SESSION->shibboleth_session_id)) {
//print_r("shibboleth_session_id: ".$SESSION->shibboleth_session_id);die();
			// Backup old redirect url
			$temp_redirect = $redirect;

			if (isset($SESSION->shibboleth_logoutpath) && !empty($SESSION->shibboleth_logoutpath))
				$lo = $SESSION->shibboleth_logoutpath;
			// Overwrite redirect in order to send user to Shibfed logout page
			$redirect = "http://".$_SERVER["HTTP_HOST"].$lo.self::LOGOUT_HANDLER;
			// Overwrite redirect in order to send user to Shibfed logout page and let him return back
			//$redirect = "http://".$_SERVER["HTTP_HOST"].$lo.self::LOGOUT_HANDLER."?return=".urlencode($temp_redirect);
//print_r($redirect);die();
		}
	}

	function prevent_local_passwords() {
		return false;
	}

	/**
		* Returns true if this authentication plugin can change the user's
		* password.
		*
		* @return bool
		*/
	function can_change_password() {
		return false;
	}

	/**
		* Returns the URL for changing the user's pw, or empty if the default can
		* be used.
		*
		* @return moodle_url
		*/
	function change_password_url() {
		return null;
	}

	/**
		* Returns true if plugin allows resetting of internal password.
		*
		* @return bool
		*/
	function can_reset_password() {
		return false;
	}

	/**
		* Prints a form for configuring this authentication plugin.
		*
		* This function is called from admin/auth.php, and outputs a full page with
		* a form for configuring this plugin.
		*
		* @param array $page An object containing all the data for this page.
		*/
	function config_form($config, $err, $user_fields) {
		include "config.html";
	}

	/**
		* Processes and stores configuration data for this authentication plugin.
		*/
	function process_config($config) {
		return true;
	}

	function get_userinfo($username) {
		//override if needed
		return array();
	}
}


