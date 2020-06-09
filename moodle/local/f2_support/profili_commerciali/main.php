<?php

/* $Id$ */

//define('CLI_SCRIPT', true);

require_once('../../../config.php');

global $CFG;
require_once 'const.php';
require_once $CFG->dirroot.'/f2_lib/lib.php';

try {
	$mysqli = db_instance_connection();
	$mysqli->select_db(DATABASE);
	
	$msg = create_cohorts($mysqli);
	if (isset($msg)) throw new Exception($msg);
	
	$msg = create_category($mysqli);
	if (isset($msg)) throw new Exception($msg);
	
	$mysqli->close();	
} catch (Exception $e) {
	print_r($e->getMessage());
} 

/*
 * Funzione per la creazione delle Macrocategorie (= Coorti)
 */
function create_cohorts(mysqli $mysqli) {
	global $DB;

	$context = $DB->get_field('context', 'id', array('contextlevel' => 10));
	
	$mysqli->autocommit(FALSE);
	
	$mysqli->query('TRUNCATE '.COHORT);
	if (!$stmt = $mysqli->prepare("INSERT INTO ".COHORT." VALUES (NULL, ".$context.", ?, ?, ?, 1,'',".time().",".time().")"))
		return $mysqli->errno." ".$mysqli->error;
	
	foreach (unserialize(MACROCATEGORIES) as $key => $macrocategory) {
		if (!$stmt->bind_param('sss', $macrocategory[0], $macrocategory[1], $macrocategory[2]))
			return $stmt->errno." ".$stmt->error;
		if (!$stmt->execute())
			return $stmt->errno." ".$stmt->error;
	}
	
	$stmt->close();
	
	$mysqli->autocommit(TRUE);
	
	return NULL;
}

/*
 * Funzione per la creazione delle Categorie (= Profili Commerciali).
 * Prima vengono inseriti i Profili Commerciali senza il riferimento 
 * alla Coorte, la quale viene ricavata in base alla prima lettera 
 * della categoria
 */
function create_category(mysqli $mysqli) {
	global $CFG;
	
	$url = $CFG->dirroot.'/local/f2_support/profili_commerciali/posiz_econom_qualifica.sql';

	$mysqli->query('TRUNCATE '.PROFILO_ECONOMICO);
	
	$mysqli->autocommit(FALSE);
	
	$categories = file($url);
	foreach($categories as $sql) {
		if(trim($sql) != "" && strpos($sql, "--") === false)
			$mysqli->query($sql); // Queries di INSERT delle categorie da inserire
	}
	
	$mysqli->autocommit(TRUE);
	$sql_update = ""; // Queries di UPDATE delle categoria che devono avere il campo COHORTID valorizzato
	
	$mysqli->autocommit(FALSE);
	
	if (!$stmt = $mysqli->prepare("SELECT id, codqual FROM ".PROFILO_ECONOMICO." WHERE macrocategory IS NULL OR cohortid IS NULL"))
		return $mysqli->errno." ".$mysqli->error;
	$stmt->attr_set(MYSQLI_STMT_ATTR_CURSOR_TYPE, MYSQLI_CURSOR_TYPE_READ_ONLY);
	$stmt->attr_set(MYSQLI_STMT_ATTR_PREFETCH_ROWS, 1000);
	
	if ($stmt->execute()) { // Set del campo COHORTID per tutti i Profili Economici che non ce l'hanno valorizzato
		$stmt->bind_result($id_cat, $codqual_cat);
		while ($stmt->fetch()) {
			
			$name_macro = get_macrocategory_from_category($codqual_cat);
			$id_cohort = get_cohort_from_category($codqual_cat); // Recupero dell'id della Coorte
			
			if (isset($id_cohort))
				$mysqli->query("
						UPDATE 
							".PROFILO_ECONOMICO." 
						SET 
							macrocategory='".$name_macro."', cohortid=".$id_cohort." 
						WHERE 
							id=".$id_cat);
		}
	} else return $stmt->errno." ".$stmt->error;
	
	$stmt->close();
	
	$mysqli->autocommit(TRUE);
	
	return NULL;	
}
