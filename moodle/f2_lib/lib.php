<?php

/* $Id: l.sampo $ */

/*
 * Funzione per la creazione di un'istanza di connessione
 * al database specificato nel file config.php
 */

global $CFG;
// initialize vector da usare nella fase di cripting/decripting
//$iv = mcrypt_create_iv(mcrypt_get_block_size(MCRYPT_TripleDES, MCRYPT_MODE_CBC), MCRYPT_DEV_RANDOM);
//$key = $CFG->passwordsaltmain; // chiave di codifica/decodifica

function db_instance_connection() {
	global $CFG;
	
	$port	= $CFG->dboptions['dbport'];
	$host 	= $port ? $CFG->dbhost.':'.$port : $CFG->dbhost;
	$usr 	= $CFG->dbuser;
	$pwd	= $CFG->dbpass;
	
	ini_set('max_execution_time', 0);
	
	$mysqli = new mysqli($host, $usr, $pwd);
	
	if (mysqli_connect_errno()) {
		printf('Could not connect: %s\n'.mysqli_connect_error());
		exit;
	}
	
	return $mysqli;	
}

/*
 * Restituisce la stringa della macrocategoria
 * @param nome della categoria
 * @return nome della macrocategoria
 */
function get_macrocategory_from_category($category) {
	$macro = substr($category, 0 ,1);
	
	if ($macro == 'E') $macro = 'Dir';
	if ($macro == 'U') $macro = 'UE';
	
	return $macro;
}

/*
 * Restituisce l'id della Coorte per il Profilo Commerciale in esame
 * @param nome della categoria
 * @return id della Coorte, NULL se non eiste
 */
function get_cohort_from_category($category) {
	global $DB;
	
	$macro = get_macrocategory_from_category($category);
		
	return $macro ? $DB->get_field('cohort', 'id', array('name' => $macro)) : NULL;
}


/*
 * Funzione che permette di importare di dati da un file CSV su database
 * @param 	$source è il file sorgente
 * 			$target la tabella in cui inserire i dati
 * 			$dblink la connessione al database
 * 			$max_line_length (opzionale) è la lunghezza massima di righe che posso essere lette da file ad ogni accesso in lettura
 * @return void
 */
function csv_file_to_mysql_table($source, $target, $dblink, $max_line_length=10000) {
	if (($handle = fopen($source, "r")) !== FALSE) {
		$columns = fgetcsv($handle, $max_line_length, ",");
		foreach ($columns as &$column) {
			$column = str_replace(".","",$column);
		}
		$insert_query_prefix = "INSERT INTO $target (".join(",",$columns).")\nVALUES";
		while (($data = fgetcsv($handle, $max_line_length, ",")) !== FALSE) {
			while (count($data)<count($columns))
				array_push($data, NULL);
			$query = "$insert_query_prefix (".join(",",quote_all_array($data)).");";
			mysql_query($query,$dblink);
			mysql_query('COMMIT',$dblink);
		}
		fclose($handle);
	}
}

/*
 * Rircorsione sui valori da ripulire da caratteri speciali
 * @param record del file CSV
 * @return record i cui campi sono puliti da caratteri speciali
 */
function quote_all_array($values) {
	foreach ($values as $key=>$value)
		if (is_array($value))
			$values[$key] = quote_all_array($value);
		else
			$values[$key] = quote_all($value);
	return $values;
}

/*
 * Viene fatto l'escape delle stringhe da inserire in database
 * @param valore della cella
 * @return dato pulito da caratteri speciali
 */
function quote_all($value) {
	if (is_null($value))
		return "NULL";

	$value = "'" . mysql_real_escape_string($value) . "'";
	return $value;
}

class Encryption {
	var $skey = "";
	
	public function __construct($key) {
		$this->skey = sha1($key);
	}

	public function safe_b64encode($string) {
		$data = base64_encode($string);
		$data = str_replace(array('+','/','='),array('-','_',''),$data);
		return $data;
	}

	public function safe_b64decode($string) {
		$data = str_replace(array('-','_'),array('+','/'),$string);
		$mod4 = strlen($data) % 4;
		if ($mod4) {
			$data .= substr('====', $mod4);
		}
		return base64_decode($data);
	}

	public function encode($value){
		if(!$value){
			return false;
		}
		$text = $value;
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->skey, $text, MCRYPT_MODE_ECB, $iv);
		return trim($this->safe_b64encode($crypttext));
	}

	public function decode($value){
		if(!$value){
			return false;
		}
		$crypttext = $this->safe_b64decode($value);
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$decrypttext = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->skey, $crypttext, MCRYPT_MODE_ECB, $iv);
		return trim($decrypttext);
	}
}

?>