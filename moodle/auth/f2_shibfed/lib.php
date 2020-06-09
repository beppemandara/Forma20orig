<?php
/*
 * $Id: lib.php 958 2013-01-09 13:24:22Z l.moretto $
 */
interface IShibHeaders
{
	const HEADER_PREFIX        = "Shib-";
	const HEADER_REMOTEUSER    = "remote_user";
	const HTTP_PREFIX          = "HTTP_";
	const IRIDE_SEP            = "/";
	const HEADER_APPID         = "Shib-Application-ID";
}
interface IShibIPPAHeaders
{
	const HEADER_NOME    = "Shib-Identita-Nome";
	const HEADER_COGNOME = "Shib-Identita-Cognome";
	const HEADER_CF      = "Shib-Identita-CodiceFiscale";
	const HEADER_LIV     = "Shib-Identita-LivAuth";// Livello di autenticazione
	const HEADER_IRIDEID = "Shib-Iride-IdentitaDigitale";//Identità digitale
}
interface IShibIPSPHeaders
{
	const HEADER_NOME    = "Shib-Identita-Nome";
	const HEADER_COGNOME = "Shib-Identita-Cognome";
	const HEADER_CF      = "Shib-Identita-CodiceFiscale";
	const HEADER_LIV     = "Shib-Identita-LivAuth";// Livello di autenticazione
	const HEADER_TIMESTAMP = "Shib-Identita-TimeStamp";//Istante in cui è stata operata l’autenticazione
	const HEADER_IDP     = "Shib-Identity-Provider";//Rappresenta il provider di identità responsabile dell'aut. e delle credenziali fornite
	const HEADER_IRIDEID = "Shib-Iride-IdentitaDigitale";//Identità digitale
}
interface IShibIPAIHeaders
{
	const HEADER_NOME    = "Shib-Identita-Nome";
	const HEADER_COGNOME = "Shib-Identita-Cognome";
	const HEADER_CF      = "Shib-Identita-CodiceFiscale";
	const HEADER_LIV     = "Shib-Identita-LivAuth";// Livello di autenticazione
	const HEADER_TIMESTAMP = "Shib-Identita-TimeStamp";//Istante in cui è stata operata l’autenticazione
	const HEADER_IDP     = "Shib-Identity-Provider";//Rappresenta il provider di identità responsabile dell'aut. e delle credenziali fornite
	const HEADER_IRIDEID = "Shib-Iride-IdentitaDigitale";//Identità digitale
}
abstract class Shib implements IShibHeaders {
	private $shib_headers = null;
	
	function __construct() {
		if( !function_exists('apache_request_headers') ) {
			echo "Function apache_request_headers() doesn\'t exists: cannot continue.";
			die();
		}
	}
	/**
	 * 
	 */
	private function preg_grep_keys( $pattern, $input, $flags = 0 )
	{
		$keys = preg_grep( $pattern, array_keys( $input ), $flags );
		$vals = array();
		foreach ( $keys as $key )
		{
			$vals[$key] = $input[$key];
		}
		return $vals;
	}
	protected function toServerKey($header) {
		$s = preg_replace("/-/", "_", self::HTTP_PREFIX.$header);
		//print_r(strtoupper($s)."<br/>");die();
		return strtoupper($s);
	}
	/**
	 *
	 * @return type 
	 */
	public function get_shib_headers() {
		$headers = apache_request_headers();
		$re = "/^".self::HEADER_PREFIX."/";
		$this->shib_headers = preg_grep_keys($re, $headers);
		return $this->shib_headers;
	}
	
	// Common methods
	/**
	 * Print all HTTP headers.
	 */
	public function printAllHeaders() {
		print_r(apache_request_headers());
	} 
	/**
	 * Print all Shibfed HTTP headers.
	 */
	public function printAllShibHeaders() {
		if( is_null($this->shib_headers) )
			$this->get_shib_headers();
		print_r($this->shib_headers);
	}
	/**
	 * Get Shibfed authenticated username.
	 */
	abstract public function get_user_attribute();
	/**
	 * Check for a valid Shibfed authentication request.
	 */
	abstract public function check();
	/**
	 * Get logout context path.
	 */
	abstract public function get_logout_path();
}

class Shibippa extends Shib implements IShibIPPAHeaders
{
	const LOGOUT_PATH_IPPA = '/formazioneliv1wrup';  // 17/11/2015
	function Shibippa()
	{
		parent::__construct();
	}
	/**
	 * Get Shibfed authenticated username.
	 */
	public function get_user_attribute() {
		return $_SERVER[$this->toServerKey(self::HEADER_CF)];
	}
	/**
	 * Check for a valid Shibfed authentication request.
	 * TODO: check all fields.
	 */
	public function check() {
		$chk = false;
		if( isset($_SERVER[$this->toServerKey(self::HEADER_IRIDEID)]) ) {
			$id_dig = explode(self::IRIDE_SEP, $_SERVER[$this->toServerKey(self::HEADER_IRIDEID)]);
			$chk = $id_dig[0] == $_SERVER[$this->toServerKey(self::HEADER_CF)]
				&&  $id_dig[1] == $_SERVER[$this->toServerKey(self::HEADER_NOME)]
				&&  $id_dig[2] == $_SERVER[$this->toServerKey(self::HEADER_COGNOME)]
			//TODO: check timestamp?
				&&  $id_dig[5] == $_SERVER[$this->toServerKey(self::HEADER_LIV)];
		}
		return $chk;
	}
	public function get_logout_path()
	{
		//return '';
    return self::LOGOUT_PATH_IPPA;  // 17/11/2015
	}
}

class Shibippacr extends Shibippa
{
	//const LOGOUT_PATH = '/formazioneliv1icon'; // modificato per cambio accesso del Consiglio regionale
	const LOGOUT_PATH = '/formazioneliv1intranet_icon';
	function Shibippacr()
	{
		parent::__construct();
	}
	public function get_logout_path()
	{
		return self::LOGOUT_PATH;
	}
}

class Shibipsp extends Shib implements IShibIPSPHeaders
{
  const LOGOUT_PATH = '/formazioneliv1sisp';  // 17/11/2015
	function Shibipsp()
	{
		parent::__construct();
	}
	/**
	 * Get Shibfed authenticated username.
	 */
	public function get_user_attribute() {
		return $_SERVER[$this->toServerKey(self::HEADER_CF)];
	}
	/**
	 * Check for a valid Shibfed authentication request.
	 * TODO: check all fields.
	 */
	public function check() {
		$chk = false;
		if( isset($_SERVER[$this->toServerKey(self::HEADER_IRIDEID)]) ) {
			$id_dig = explode(self::IRIDE_SEP, $_SERVER[$this->toServerKey(self::HEADER_IRIDEID)]);
			$chk = $id_dig[0] == $_SERVER[$this->toServerKey(self::HEADER_CF)]
				&&  $id_dig[1] == $_SERVER[$this->toServerKey(self::HEADER_NOME)]
				&&  $id_dig[2] == $_SERVER[$this->toServerKey(self::HEADER_COGNOME)]
			//TODO: check timestamp?
				&&  $id_dig[5] == $_SERVER[$this->toServerKey(self::HEADER_LIV)];
		}
		return $chk;
	}
	public function get_logout_path()
	{
		/*$path = '';
		//Get authentication level from Shibboleth application id header
		if( isset($_SERVER[$this->toServerKey(self::HEADER_APPID)]) ) {
			$a = explode('_', $_SERVER[$this->toServerKey(self::HEADER_APPID)]);
			$path = '/'.strtolower(array_pop($a));
		}
		return $path;*/
    return self::LOGOUT_PATH; // 05/07/2013
	}
}

class Shibipai extends Shib implements IShibIPAIHeaders
{
	const LOGOUT_PATH = '/formazioneliv1ireg';
	function Shibipai()
	{
		parent::__construct();
	}	
	/**
	 * Get Shibfed authenticated username.
	 */
	public function get_user_attribute() {
		return $_SERVER[$this->toServerKey(self::HEADER_CF)];
	}
	/**
	 * Check for a valid Shibfed authentication request.
	 * TODO: check all fields.
	 */
	public function check() {
		$chk = false;
		if( isset($_SERVER[$this->toServerKey(self::HEADER_IRIDEID)]) ) {
			$id_dig = explode(self::IRIDE_SEP, $_SERVER[$this->toServerKey(self::HEADER_IRIDEID)]);
			$chk = $id_dig[0] == $_SERVER[$this->toServerKey(self::HEADER_CF)]
				&&  $id_dig[1] == $_SERVER[$this->toServerKey(self::HEADER_NOME)]
				&&  $id_dig[2] == $_SERVER[$this->toServerKey(self::HEADER_COGNOME)]
			//TODO: check timestamp?
				&&  $id_dig[5] == $_SERVER[$this->toServerKey(self::HEADER_LIV)];
		}
		return $chk;
	}
	public function get_logout_path() {
		return self::LOGOUT_PATH;
	}
}
class ShibFactory
{
	const IPPA   = 'ippa';
	const IPPACR = 'ippacr';
	const IPSP   = 'ipsp';
	const IPAI   = 'ipai';
	const SEP    = '/';
	
	private static function getShibType( $uri ) {
		//$a = explode(DIRECTORY_SEPARATOR, $path);
		$a = explode(self::SEP, $uri);
		$s = array_pop($a);
		return (empty($s) ? array_pop($a) : $s);
	}
  public static function Create( $uri )
  {
		$type = ShibFactory::getShibType($uri);
		$instance = null;
		switch( $type ) {
			case self::IPPA:
				$instance = new Shibippa();
				break;
			case self::IPPACR:
				$instance = new Shibippacr();
				break;
			case self::IPSP:
				$instance = new Shibipsp();
				break;
			case self::IPAI:
				$instance = new Shibipai();
				break;
			default:
				//TODO: write to moodle log
				//echo "Unknown shibboleth type [$uri]: cannot continue.";
				//die();
		}
    return $instance;
  }
}


