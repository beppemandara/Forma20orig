<?php

// $Id: lib.php 1098 2013-04-02 14:44:54Z d.lallo $

require_once($CFG->dirroot.'/local/f2_course/extends_course.php');
require_once("$CFG->dirroot/enrol/locallib.php");
require_once($CFG->dirroot.'/course/lib.php');
require_once('const.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');
require_once($CFG->dirroot.'/f2_lib/core.php');

define('AUTORIZZAZIONE', 1); // corrisponde all'id del record della tabella f2_notif_tipo con tipo Autorizzazione
define('CANCELLAZIONE', 2); // corrisponde all'id del record della tabella f2_notif_tipo con tipo Cancellazione
define('AULA', 0); // valore corrispondente ad AULA per campo "canale" di f2_notif_templates
define('ONLINE', 1); // valore corrispondente ad ONLINE per campo "canale" di f2_notif_templates

/**
 * Questa funzione crea un oggetto corso con le impostazioni di default
 * quest'oggetto in fase di import dei corsi verrà clonato.
 */
function get_default_course_fields(){
	$master_course = new stdClass();
	$master_course->category = 1;
	$master_course->fullname = "";
	$master_course->shortname = "";
	$master_course->idnumber = "";
	// $master_course->summary_editor = Array ( [text] => [format] => 1 [itemid] => 917175293 );
	$master_course->summary_editor = '';
//	$master_course->format = "weeks";
//	$master_course->numsections = "10";
	$master_course->startdate = time();
//	$master_course->hiddensections = 0;
//	$master_course->newsitems = 5;
//	$master_course->showgrades = 1;
//	$master_course->showreports = 0;
//	$master_course->maxbytes = 134217728 ;
	$master_course->enrol_guest_status_0 = 1;
//	$master_course->groupmode = 0;
//	$master_course->groupmodeforce = 0;
	$master_course->defaultgroupingid = 0;
//$master_course->visible = 1;
//	$master_course->lang = "";
//	$master_course->enablecompletion = 0;
//	$master_course->completionstartonenrol = 0;
	$master_course->restrictmodules = 0;
	$master_course->role_1 = "";
	$master_course->role_2 = "";
	$master_course->mform_showadvanced_last = 0;
	$master_course->role_3 = "";
	$master_course->role_4 = "";
	$master_course->role_5 = "";
	$master_course->role_6 = "";
	$master_course->role_7 = "";
	$master_course->role_8 = "";
	return $master_course;
}

/**
 * Questa funzione crea un oggetto corso con l'anagrafica corso FORMA;
 * quest'oggetto in fase di import dei corsi verrà clonato.
 */
function get_forma_course_fields() {
	$forma_course = new stdClass();
	$forma_course->courseid        = 0;
	$forma_course->course_type     = 2;
	$forma_course->anno            = 0;
	$forma_course->durata          = 0.0;
	$forma_course->cf              = 0.0;
	$forma_course->orario          = "";
	$forma_course->costo           = 0.0;
	$forma_course->tipo_budget     = 1;
	$forma_course->sf              = "";
	$forma_course->af              = "";
	$forma_course->subaf           = "";
	$forma_course->to_x            = "";
	$forma_course->te              = 0;
	$forma_course->flag_dir_scuola = "S";
	$forma_course->id_dir_scuola   = 0;
	$forma_course->viaente         = "";
	$forma_course->localita        = "";
	$forma_course->sede            = array("TO");
	$forma_course->determina       = "";
	$forma_course->note            = "";
	$forma_course->num_min_all     = 0;
	$forma_course->num_norm_all    = 0;
	$forma_course->num_max_all     = 0;

	return $forma_course;
}

/**
 * Questa funzione crea un oggetto scheda progetto per un corso FORMA
 * quest'oggetto in fase di import dei corsi verrà clonato.
 */
function get_scheda_progetto_fields() {
	$scheda_progetto = new stdClass();
	$scheda_progetto->courseid         = 0;
	$scheda_progetto->sede_corso       = '';
	$scheda_progetto->destinatari      = '';
	$scheda_progetto->accesso          = '';
	$scheda_progetto->obiettivi        = '';
	$scheda_progetto->pfa              = '';
	$scheda_progetto->pfb              = '';
	$scheda_progetto->pfc              = '';
	$scheda_progetto->pfd              = '';
	$scheda_progetto->pfdir            = '';
	$scheda_progetto->pue              = '';
	$scheda_progetto->met1             = 0;
	$scheda_progetto->met2             = 0;
	$scheda_progetto->met3             = 0;
	$scheda_progetto->monitoraggio     = '';
	$scheda_progetto->valutazione      = '';
	$scheda_progetto->apprendimento    = '';
	$scheda_progetto->pue              = '';
	$scheda_progetto->ricaduta         = '';
	$scheda_progetto->first            = 0;
	$scheda_progetto->last             = 0;
	$scheda_progetto->rev              = 0;
	$scheda_progetto->dispense_vigenti = '';
	$scheda_progetto->contenuti        = '';
	$scheda_progetto->a                = '';

	return $scheda_progetto;
}

/*
 * Funzione base per costruire un oggetto che memorizza lo stato di un transazione
 * @param 	$status (TRUE o FALSE) per tracciare il successo o meno della transazione
 * 			$msg (opzionale) se valorizzato contiene il messaggio di errore
 * @return oggetto stdClass
 */
function objectErrorHandler($status, $msg = null) {
	$objHandler = new stdClass();
	$objHandler->status = $status;
	$objHandler->msg = $msg;
	
	return $objHandler;
}

/*
 * Funzione che controlla che l'intestazione di un file e il template di riferimento siano analoghe
 * @param $file_header array con l'intestazione del file, $default_template array con l'intestazione di riferimento
 * @return TRUE o FALSE
 */
function validate_file_header($file_header, $default_template) {
	return (serialize($file_header) === serialize($default_template)) ? true : false;
}

/*
 * Funzione che importa i posti risevati a partire da un file CSV preso in ingresso
 * @param $data oggetto form, $separator è il separatore di cella usato nel CSV, $delimiterlist è l'elenco dei separatori ammessi
 * @return oggetto errorHandler(status,msg) valorizzato a seconda di caricamento eseguito con successo o meno
 */
function import_sessions_reserved_seats_from_csv($data, $separator, $delimiterlist = array()) {
	global $DB, $CFG;
	
	$fileID_seats = $DB->get_field_sql("SELECT id FROM {files} WHERE itemid = ".$data->fileposti. " AND filesize > 0");
	$fs = get_file_storage();
	$file_seats = $fs->get_file_by_id($fileID_seats);
	$contenthash_seats = $file_seats->get_contenthash();
	$path = $CFG->dataroot.'/filedir/'.substr($contenthash_seats, 0, 2).'/'.substr($contenthash_seats, 2, 2).'/'.$contenthash_seats;
	
	$seats_header = array(); // header del file
	$seats_data = array(); // contenuto del file
	$max_line_length = 10000; // Numero massimo di righe leggibili dalla fgetcsv
	
	if (($handle = fopen($path, "r")) !== FALSE) {
		$columns = fgetcsv($handle, $max_line_length, $delimiterlist[$separator]);
		foreach ($columns as $column) {
			$seats_header[] = str_replace(".","",strtolower($column));
		}
		while (($data = fgetcsv($handle, $max_line_length, $delimiterlist[$separator])) !== FALSE) {
			$idx = 0;
			$objseats = new stdClass();
			foreach ($data as $field) {
				$objseats->$seats_header[$idx] = $field;
				$idx++;
			}
			$seats_data[] = $objseats;
		}
		fclose($handle);
	}
	
	$msg = create_editions_seats_map_from_import($seats_data);
	return (is_null($msg)) ? objectErrorHandler(true) : objectErrorHandler(false, $msg);
}

/*
 * Popolamento della tabella mdl_f2_edizioni_postiris_map con le associazioni contenute nel file
 * @param $data contenuto del file
 * @return null se l'inserimento è andato a buon fine, un messaggio o FALSE in caso di errore
 */
function create_editions_seats_map_from_import($data) {
	global $DB;
        
        $anno = get_anno_formativo_corrente();
	
	foreach ($data as $direction_seats) {
		$sessionID = $DB->get_field_sql("
				SELECT qry1.sessionid FROM 
                                    (SELECT 
                                                DISTINCT fsd.sessionid
                                        FROM 
                                                {facetoface_session_field} fsf, 
                                                {facetoface_session_data} fsd
                                        WHERE 
                                                fsf.shortname LIKE 'csicode' AND 
                                                fsf.id = fsd.fieldid AND 
                                                fsd.data = $direction_seats->idfk
                                    ) AS qry1 
                                    JOIN (SELECT 
                                                DISTINCT fsd.sessionid
                                            FROM 
                                                {facetoface_session_field} fsf, 
                                                {facetoface_session_data} fsd
                                            WHERE 
                                                fsf.shortname LIKE 'anno' AND 
                                                fsf.id = fsd.fieldid AND 
                                                fsd.data = $anno
                                        ) AS qry2 
                                ON (qry1.sessionid = qry2.sessionid)
                        ");

		if (!sessionID)
			return "ERROR: si sta cercando di assegnare dei posti ad una direzione nell\'edizione $direction_seats->idfk che non esiste";	

		if ($DB->record_exists('org', array('id' => $direction_seats->codice_direzione))) {
			if(!$DB->record_exists(TABLE_POSTI_RISERVATI, array('sessionid' => $sessionID, 'direzioneid' => $direction_seats->codice_direzione))) {
				$dataobject = new stdClass();
				$dataobject->sessionid = $sessionID;
				$dataobject->direzioneid = $direction_seats->codice_direzione;
				$dataobject->npostiassegnati = $direction_seats->numero_posti_riservati;
				$dataobject->nposticonsumati = 0;
				
				if (!$DB->insert_record(TABLE_POSTI_RISERVATI, $dataobject))
					return false;
			} else
				return "ERROR: all'edizione $sessionID sono gi&agrave; stati riservati dei posti per la direzione $direction_seats->codice_direzione";
		} else
			return "ERROR: il codice di direzione $direction_seats->codice_direzione non &egrave; valido";

	}
	
	return null;
}

/*
 * Funzione che importa le edizioni per i corsi programmati a partire da un file CSV preso in ingresso
* @param $data oggetto form, $separator è il separatore di cella usato nel CSV, $delimiterlist è l'elenco dei separatori ammessi
* @return oggetto errorHandler(status,msg) valorizzato a seconda di caricamento eseguito con successo o meno
*/
function import_sessions_from_csv($data, $separator, $delimiterlist = array()) {
	global $DB, $CFG;

	$fileID_edz = $DB->get_field_sql("SELECT id FROM {files} WHERE itemid = ".$data->fileedz. " AND filesize > 0");
	$fs = get_file_storage();
	$file_edz = $fs->get_file_by_id($fileID_edz);
	$contenthash_edz = $file_edz->get_contenthash();
	$path = $CFG->dataroot.'/filedir/'.substr($contenthash_edz, 0, 2).'/'.substr($contenthash_edz, 2, 2).'/'.$contenthash_edz;
	
	$DB->execute("TRUNCATE {".TABLE_PIANIFICATE."}");
	
	$editions_header = array(); // header del file
	$editions_data = array(); // dati da caricare
	$max_line_length = 10000;
	
	if (($handle = fopen($path, "r")) !== FALSE) {
		$columns = fgetcsv($handle, $max_line_length, $delimiterlist[$separator]);
		foreach ($columns as $column) {
			$editions_header[] = str_replace(".","",strtolower($column));
		}
		while (($data = fgetcsv($handle, $max_line_length, $delimiterlist[$separator])) !== FALSE) {
			$idx = 0;
			$objedition = new stdClass();
			foreach ($data as $field) {
				$objedition->$editions_header[$idx] = $field;
				$idx++;
			}
			if (!$DB->execute("
					INSERT INTO 
						{".TABLE_PIANIFICATE."} 
						(	id, 
							anno_pianificazione, 
							codice_corso, 
							sede, 
							edizione, 
							anno_svolgimento, 
							sessione_svolgimento, 
							edizione_svolgimento
						) 
					VALUES 
						(	$objedition->id,
							$objedition->anno_pianificazione,
							'".$objedition->codice_corso."',
							'".$objedition->sede."',
							$objedition->edizione,
							$objedition->anno_svolgimento,
							$objedition->sessione_svolgimento,
							$objedition->edizione_svolgimento					
						)"))
				return objectErrorHandler(false, "ERROR: problema di inserimento in ".TABLE_PIANIFICATE);
		}
		fclose($handle);
	}
	
	$msg1 = create_sessions_import_prg(); // Creazione delle Sessioni
	if (!is_null($msg1)) return objectErrorHandler(false, $msg1);
        
	$msg2 = create_editions_import_prg(); // Creazione delle Edizioni
	if (!is_null($msg2)) return objectErrorHandler(false, $msg2);
        
	else return objectErrorHandler(true);
}

/*
 * Funzione che crea le Sessioni per i corsi programmati, all'interno delle quali sono contenute le edizioni
 * @param void
 * @return null se l'inserimento è andato a buon fine, un messaggio o FALSE in caso di errore
 */
function create_sessions_import_prg() {
	global $DB, $CFG;
	
	include $CFG->dirroot.'/f2_lib/course.php';
	
	$sessions = $DB->get_records_sql('
			SELECT 
				codice_corso as cc, 
				anno_svolgimento as av, 
				sessione_svolgimento as ss 
			FROM 
				{'.TABLE_PIANIFICATE.'}
			GROUP BY 
				codice_corso, 
				anno_svolgimento, 
				sessione_svolgimento');
	
	foreach ($sessions as $session) {
		$f2session = $DB->get_field('f2_sessioni', 'id', array('anno' => $session->av, 'numero' => $session->ss));
                if (!$f2session) {
                    return "ERROR: non esiste la sessione $session->ss per l'anno $session->av";
                }
		$courseid = $DB->get_field('course', 'id', array('shortname' => $session->cc."_".$session->av));
		if (!$DB->record_exists('facetoface', array('course' => $courseid, 'f2session' => $f2session))) {
			if (!auto_instance_session($courseid, $f2session)) return "Session instance failed: impossibile istanziare una nuova sessione per il corso ".$session->cc."_".$session->av;
			rebuild_course_cache($courseid, TRUE);
		}
	}

	return null;
}

/*
 * Funzione che crea le edizioni per i corsi programmati
 * @param void
 * @return null se l'inserimento è andato a buon fine, un messaggio o FALSE in caso di errore
 */
function create_editions_import_prg() {
	global $DB, $USER;

	$editions = $DB->get_records(TABLE_PIANIFICATE);

	foreach ($editions as $data) {
		$session = new stdClass();
		try {
			$session->facetoface = $DB->get_field_sql("
					SELECT 
						DISTINCT f.id 
					FROM 
						{course} c, 
						{facetoface} f, 
						{f2_sessioni} f2s 
					WHERE 
						c.id = f.course AND 
						c.shortname LIKE '".$data->codice_corso."_".$data->anno_svolgimento."' AND 
						f.f2session = f2s.id AND 
						f2s.anno = $data->anno_svolgimento AND 
						f2s.numero = $data->sessione_svolgimento");
		} catch (Exception $e) {echo 'Exception: ',  $e->getMessage(), "\n";}
		$session->datetimeknown = 1;
		$session->capacity = 9999;

		if ($session->facetoface && !$DB->record_exists_sql("
				SELECT
					fsd.id 
				FROM 
					{facetoface_sessions} fs, 
					{facetoface_session_field} fsf, 
					{facetoface_session_data} fsd 
				WHERE 
					fs.facetoface = $session->facetoface AND 
					fsf.shortname LIKE 'csicode' AND 
					fsf.id = fsd.fieldid AND 
					fsd.sessionid = fs.id AND 
					fsd.data = $data->id")) {
			$id = facetoface_add_session($session, array());
			
			if (!$DB->insert_record('facetoface_session_data', object_session_field($id, 'anno', $data, 'anno_svolgimento'))) return false;
			if (!$DB->insert_record('facetoface_session_data', object_session_field($id, 'sede', $data, 'sede'))) return false;
			if (!$DB->insert_record('facetoface_session_data', object_session_field($id, 'lstupd'))) return false;
			if (!$DB->insert_record('facetoface_session_data', object_session_field($id, 'usrname'))) return false;
			if (!$DB->insert_record('facetoface_session_data', object_session_field($id, 'csicode', $data, 'id'))) return false;
			if (!$DB->insert_record('facetoface_session_data', object_session_field($id, 'editionum', $data, 'edizione'))) return false;
		} else return "ERROR: si sta cercando di creare un'edizione gi&agrave; presente";
	}
	
	return null;
}

/*
 * Funzione che a partire da un campo $shortname crea un oggetto per istanziarlo all'interno della 
 * mdl_facetoface_session_data a seconda che sia specificato il valore ($raw e $field valorizzati) o
 * non sia necessario (campi lstupd e usrname)
 * @param 	$session id dell'edizione
 * 			$shortname nome che identifica il campo nella tabella mdl_facetoface_session_field
 * 			$raw record contenente le informazioni sull'edizione
 * 			$field nome del campo all'interno del file CSV
 * @return oggetto stdClass da istanziare in mdl_facetoface_session_data
 */
function object_session_field($session, $shortname, $raw = null, $field = null) {
	global $DB, $USER;

	$dataobject = new stdClass();
	$dataobject->fieldid = $DB->get_field('facetoface_session_field', 'id', array('shortname' => $shortname));
	$dataobject->sessionid = $session;
	if (is_null($raw) && is_null($field)) {
		if ($shortname === 'lstupd')
			$dataobject->data = time();
		elseif ($shortname === 'usrname')
			$dataobject->data = $USER->id;
	} else {
		$dataobject->data = $raw->$field;
	}
	
	return $dataobject;
}

/**
 *
 * @global type $DB
 * @global type $CFG
 * @param type $data 
 */
function import_course_from_access($data){
	
	global $DB,$CFG;
        $importresults = new stdClass();
        $importresults->corsi_da_importare = 0;
        $importresults->corsi_importati = 0;
        $importresults->corsi_aggiornati = 0;
        $importresults->anomalie = 0;
        $importresults->warnings = 0;
        $importresults->elenco_anomalie = array();
        $importresults->elenco_warning = array();
        $can_assign_role = has_capability('moodle/role:assign', get_context_instance(CONTEXT_SYSTEM));

	$file_id=$DB->get_field_sql("select id from {files} where itemid=".$data->course_file_access." AND filesize>0");
	$fs = get_file_storage();
	$x = $fs->get_file_by_id($file_id);
	$contenthash = $x->get_contenthash();
	$path = $CFG->dataroot.'/filedir/'.substr($contenthash, 0, 2).'/'.substr($contenthash, 2, 2).'/'.$contenthash;
        
        $category = $data->categoria;
	if (extension_loaded('mdbtools'))  // dovrei essere in ambiente unix ed utilizzo mbdtools
	{
            $mdb = mdb_open($path);
            if ($mdb === false) {
                    die('ERROR: Cannot initialize database handle');
            }

            $tbl = mdb_table_open($mdb, 'CorsiSchede') or die('ERROR: Cannot open table ');
            while ($row = mdb_fetch_assoc($tbl)) {
                $importresults->corsi_da_importare++;
                try {
                    create_import_course($row, $importresults, $category, $can_assign_role);
                } catch (Exception $e) {
                    $importresults->anomalie++;
                    $importresults->elenco_anomalie[] = $row["Codcorso"]."_".$row["Anno"].': '.$e->getMessage();
                    continue;
                }
            }
            mdb_table_close($tbl);
	}
	
	else
	{			
            // dovrei essere in ambiente windows utilizzo odbc
            $db = new PDO("odbc:DRIVER={Microsoft Access Driver (*.mdb)}; DBQ=$path; Uid=; Pwd=;");
            $sql  = "SELECT * FROM CorsiSchede";
            $result = $db->query($sql);
            while ($row = $result->fetch()) {
                $importresults->corsi_da_importare++;
                try {
                    create_import_course($row, $importresults, $category, $can_assign_role);
                } catch (Exception $e) {
                    $importresults->anomalie++;
                    $importresults->elenco_anomalie[] = $row["Codcorso"]."_".$row["Anno"].': '.$e->getMessage();
                    continue;
                }
            }
	}
        
        return $importresults;
}


function create_import_course($row, &$importresults, $category, $can_assign_role = false){
	global $DB, $PAGE;

	$nuovo = true;

	// creo il corso
	$master_course = get_default_course_fields();
	$course = clone($master_course);
	//$course->fullname  = encode_str($row["Titolo"]);
	$course->fullname  = $row["Titolo"];
	$course->shortname = $row["Codcorso"]."_".$row["Anno"];
	$course->idnumber  = $row["Codcorso"];
	$course->category  = $category;
        
	if ($DB->record_exists('course', array('shortname' => $course->shortname))) {
			// UPDATE CORSO
			$course_created = $DB->get_field('course', 'id', array('shortname' => $course->shortname));
			$course->id = $course_created;
			update_course($course);
			$nuovo = false;
                        
                        // ottengo il tipo_budget dalla scheda corso, mi servirà più tardi
                        $vecchio_tipo_budget = $DB->get_field('f2_anagrafica_corsi', 'tipo_budget', array('courseid' => $course->id), IGNORE_MISSING);
	} else {
			// INSERT CORSO
			$course_created = create_course($course);
	}
    
	// creo l'anagrafica corso
	if ($can_assign_role) {
            //$fornitore = $DB->get_record('f2_fornitori', array('denominazione' => $row['Gestione']));
            $fornitore = $DB->get_record_sql("SELECT * FROM mdl_f2_fornitori WHERE denominazione LIKE '%".$row['Gestione']."%'");
            if ($fornitore && $fornitore->id_org != -1) {
                            // prendo tutti gli utenti appartenenti alla scuola id_org e li rendo referenti scuola per il corso corrente
                            $users = get_utenti_by_dominio_appartenenza($fornitore->id_org);

            // AGGANCIO I REFERENTI SCUOLA AL CORSO PROGRAMMATO

                            foreach ($users as $user) {
                                            if ($user->suspended != 1) { // salto gli utenti sospesi
                                                            // rendo l'utente referentescuola per il corso corrente
                                                            $course = $DB->get_record('course', array('id'=>is_object($course_created) ? $course_created->id : $course_created), '*', MUST_EXIST);
                                                            $manager = new course_enrolment_manager($PAGE, $course);
                                                            $param = get_parametro('p_f2_id_ruolo_referente_scuola');
                                                            $roleid = $param->val_int;
                                                            $manager->assign_role_to_user($roleid, $user->id);
                                            }
                            }

            } else {
                            // fornitore inesistente: registro l'anomalia
                            $importresults->anomalie++;
                            $importresults->elenco_anomalie[] = $row["Codcorso"]."_".$row["Anno"].': Fornitore inesistente o incorretto - gestione: '.$row['Gestione'];
            }
	}
	$master_anagrafica_corso = get_forma_course_fields();
	$anagrafica_corso = clone($master_anagrafica_corso);
	$anagrafica_corso->courseid = is_object($course_created) ? $course_created->id : $course_created;
	$anagrafica_corso->anno            = $row['Anno'];
	$anagrafica_corso->durata          = $row['Durata'];
	$anagrafica_corso->cf              = $row['CF'];
	$anagrafica_corso->orario          = $row['Orario'];
	$anagrafica_corso->costo           = $row['Costo'];
	$anagrafica_corso->tipo_budget     = $row['Tipo pianificazione'];
        
        if (!associaTemplatesNotifica($anagrafica_corso->courseid, $anagrafica_corso->tipo_budget)) {
            $importresults->anomalie++;
            $importresults->elenco_anomalie[] = $row["Codcorso"]."_".$row["Anno"].': Errore durante l\'assegnazione dei templates di notifica';
        }
        
	$anagrafica_corso->sf              = $row['SF'];
	$anagrafica_corso->af              = strtolower($row['AF']);
	$anagrafica_corso->subaf           = strtolower($row['SUBAF']);
	$anagrafica_corso->to_x            = $row['TO_X'];
	$anagrafica_corso->te              = $row['TE'];
	if ($fornitore) {
			$anagrafica_corso->id_dir_scuola   = $fornitore->id;
	}
	else {
			// errore ++
	}
	$anagrafica_corso->viaente         = $row['Sede_Corso'];
	$anagrafica_corso->localita        = $row['Localita'];
	$anagrafica_corso->sede            = explode(',', $row['Sedi']);
	$anagrafica_corso->determina       = $row['Determina'];
	$anagrafica_corso->note            = is_null($row['Note']) ? '' : $row['Note'];
	$anagrafica_corso->num_min_all     = $row['Numero minimo allievi'];
	$anagrafica_corso->num_norm_all    = $row['Numero normale allievi'];
	$anagrafica_corso->num_max_all     = $row['Numero massimo allievi'];

	if ($DB->record_exists('f2_anagrafica_corsi', array('courseid' => $anagrafica_corso->courseid))) {
			// UPDATE ANAGRAFICA CORSO
			update_anag_course($anagrafica_corso);
	}
	else {
			create_anag_course($anagrafica_corso);
	}
        
	// creo la scheda progetto
	$data = new stdClass();
	$data->destinatari = array();
	$data->courseid = is_object($course_created) ? $course_created->id : $course_created;
	$master_scheda_progetto = get_scheda_progetto_fields();
	$scheda_progetto = clone ($master_scheda_progetto);
	$scheda_progetto->courseid = is_object($course_created) ? $course_created->id : $course_created;
	//$scheda_progetto->sede_corso       = mb_convert_encoding( $row['Sede_Corso'], 'UTF-8', 'Windows-1252');
	$scheda_progetto->sede_corso       = $row['Sede_Corso'];
	//$scheda_progetto->destinatari      = mb_convert_encoding( $row['Destinatari'], 'UTF-8', 'Windows-1252');
	$scheda_progetto->destinatari      = $row['Destinatari'];
	//$scheda_progetto->accesso          = mb_convert_encoding( $row['Accesso'], 'UTF-8', 'Windows-1252');
	$scheda_progetto->accesso          = $row['Accesso'];
	//$scheda_progetto->obiettivi        = mb_convert_encoding( $row['Obiettivi'], 'UTF-8', 'Windows-1252');
	$scheda_progetto->obiettivi        = $row['Obiettivi'];
	$scheda_progetto->pfa              = $row['PFA'];
	if ($row['PFA'] == 'X') {
			$id_cohort = $DB->get_field('cohort', 'id', array('idnumber' => 'cohortA'));
			$data->destinatari[] = $id_cohort;
	}
	$scheda_progetto->pfb              = $row['PFB'];
	if ($row['PFB'] == 'X') {
			$id_cohort = $DB->get_field('cohort', 'id', array('idnumber' => 'cohortB'));
			$data->destinatari[] = $id_cohort;
	}
	$scheda_progetto->pfc              = $row['PFC'];
	if ($row['PFC'] == 'X') {
			$id_cohort = $DB->get_field('cohort', 'id', array('idnumber' => 'cohortC'));
			$data->destinatari[] = $id_cohort;
	}
	$scheda_progetto->pfd              = $row['PFD'];
	if ($row['PFD'] == 'X') {
			$id_cohort = $DB->get_field('cohort', 'id', array('idnumber' => 'cohortD'));
			$data->destinatari[] = $id_cohort;
	}
	$scheda_progetto->pfdir            = $row['PFDir'];
	if ($row['PFDir'] == 'X') {
			$id_cohort = $DB->get_field('cohort', 'id', array('idnumber' => 'cohortDir'));
			$data->destinatari[] = $id_cohort;
	}
	$scheda_progetto->pue            = $row['PUe'];
	if ($row['PUe'] == 'X') {
			$id_cohort = $DB->get_field('cohort', 'id', array('idnumber' => 'cohortUE'));
			$data->destinatari[] = $id_cohort;
	}
        
	create_update_destinatari_course($data);
        
	$scheda_progetto->met1             = $row['MET1'];
	$scheda_progetto->met2             = $row['MET2'];
	$scheda_progetto->met3             = $row['MET3'];
	$scheda_progetto->monitoraggio     = $row['Monitoraggio'];
	$scheda_progetto->valutazione      = $row['Valutazione'];
	$scheda_progetto->apprendimento    = $row['Apprendimento'];
	$scheda_progetto->ricaduta         = $row['Ricaduta'];
	$scheda_progetto->first            = $row['First'];
	$scheda_progetto->last             = $row['Last'];
	$scheda_progetto->rev              = $row['Rev'];
	//$scheda_progetto->dispense_vigenti = mb_convert_encoding( $row['Dispense Vigenti'], 'UTF-8', 'Windows-1252');
	$scheda_progetto->dispense_vigenti = $row['Dispense Vigenti'];
	//$scheda_progetto->contenuti        = mb_convert_encoding( $row['Contenuti'], 'UTF-8', 'Windows-1252');
	$scheda_progetto->contenuti        = $row['Contenuti'];
	$scheda_progetto->a                = $row['A'];
        
	if ($DB->record_exists('f2_scheda_progetto', array('courseid' => $scheda_progetto->courseid))) {
			// UPDATE ANAGRAFICA CORSO
			update_scheda_progetto($scheda_progetto);
	}
	else {
			create_scheda_progetto($scheda_progetto);
	}
        
	if ($nuovo) {
            // se il corso è appena stato creato allora creo le istanze di feedback per i docenti e gli studenti
            $id_corso = is_object($course_created) ? $course_created->id : $course_created;
            $id_feedback = creaFeedback($id_corso, true, $anagrafica_corso->tipo_budget); // feedback docente
            if ($id_feedback) {
                creaDomandeFeedbackDaModello($id_feedback, true);
            } else {
                // errore durante creazione modulo feedback: registro l'anomalia
                $importresults->anomalie++;
                $importresults->elenco_anomalie[] = $row["Codcorso"]."_".$row["Anno"].': errore durante creazione modulo feedback docente';
            }
            $id_feedback_s = creaFeedback($id_corso, false, $anagrafica_corso->tipo_budget); // feedback studente
            if ($id_feedback_s) {
                creaDomandeFeedbackDaModello($id_feedback_s, false, $anagrafica_corso->tipo_budget);
            } else {
                // errore durante creazione modulo feedback: registro l'anomalia
                $importresults->anomalie++;
                $importresults->elenco_anomalie[] = $row["Codcorso"]."_".$row["Anno"].': errore durante creazione modulo feedback studente';
            }
            $importresults->corsi_importati++;
        }
	else {
            if ($vecchio_tipo_budget != $anagrafica_corso->tipo_budget) {
                $importresults->warnings++;
                $importresults->elenco_warning[] = $row["Codcorso"]."_".$row["Anno"].': i questionari di gradimento legati al corso non sono stati modificati nonostante sia variato il tipo budget. Per farlo è necessario procedere manualmente.';
            }
            $importresults->corsi_aggiornati++;
        }
}

function courseFeedbacksExist($id_corso) {
    global $DB, $CFG;
    
    $qry = "SELECT count(*) FROM {$CFG->prefix}feedback WHERE course = $id_corso";
    $cont = $DB->count_records_sql($qry);
    return $cont > 0 ? true : false;
}

function creaFeedback($id_corso, $isDocente, $tipo_budget = null, $is_corso_obiettivo_studenti = false) {
    global $DB;
    
    if ($isDocente) {
        $nome_feedback = get_string('nome_feedback_docente', 'local_f2_import_course'); // docenti
    } else if ($is_corso_obiettivo_studenti) {
        $nome_feedback = get_string('nome_feedback_studente_obv', 'local_f2_import_course'); // studenti (corso OBIETTIVO)
    } else if ($tipo_budget == 1) {
        $nome_feedback = get_string('nome_feedback_studente_aula', 'local_f2_import_course'); // studenti (corso PROGRAMMATO in AULA)
    } else if ($tipo_budget == 3) {
        $nome_feedback = get_string('nome_feedback_studente_online', 'local_f2_import_course'); // studenti (corso PROGRAMMATO ON LINE)
    } else {
        return false;
    }
    
    $transaction = $DB->start_delegated_transaction();
    try {
        $feedback_module_id = $DB->get_field('modules', 'id', array('name' => 'feedback'));
        $course_context_path = $DB->get_field('context', 'path', array('contextlevel' => CONTEXT_COURSE, 'instanceid' => $id_corso));
        $course_sections = $DB->get_record_sql("SELECT * FROM mdl_course_sections WHERE course = $id_corso AND section = 0");

        // creo il modulo del corso (tipo feedback)
        $course_module_data = new stdClass();
        $course_module_data->course = $id_corso;
        $course_module_data->module = $feedback_module_id;
        $course_module_data->instance = 0;
        $course_module_data->visible = $isDocente ? 0 : 1; // se il feedback è per i docenti lo nascondo
        $course_module_data->added = time();
        $course_module_id = $DB->insert_record('course_modules', $course_module_data);

        // creo l'istanza del feedback
        $feedback_data = new stdClass();
        $feedback_data->name = $nome_feedback;
        $feedback_data->anonymous = $isDocente ? 2 : 1; // 1: anonimo - 2: non anonimo
        $feedback_data->course = $id_corso;
        $feedback_data->intro = '';
        $feedback_data->introformat = 1;
        $feedback_data->multiple_submit = 0;
        $feedback_data->autonumbering = 0;
        $feedback_data->page_after_submit = '';
        $feedback_data->page_after_submitformat = 1;
        $feedback_data->timemodified = time();
        $feedback_id = $DB->insert_record('feedback', $feedback_data);

        //creo il contesto associato all'istanza di feedback
        $context_data = new stdClass();
        $context_data->contextlevel = CONTEXT_MODULE;
        $context_data->instanceid = $course_module_id;
        $context_id = $DB->insert_record('context', $context_data);
        $context_data->id = $context_id;
        $context_data->depth = 4;
        $context_data->path = $course_context_path.'/'.$context_id;
        $DB->update_record('context', $context_data);

        //aggiorno il modulo inserendo l'id dell'istanza del feedback
        $course_module_data->id = $course_module_id;
        $course_module_data->instance = $feedback_id;
        $DB->update_record('course_modules', $course_module_data);

        //aggiorno le sezioni della pagina del corso
        $course_sections->sequence = $course_sections->sequence.','.$course_module_id;
        $DB->update_record('course_sections', $course_sections);

        //aggiorno la sezione alla quale il modulo del corso relativo al feedback appartiene
        $course_module_data->section = $course_sections->id;
        $DB->update_record('course_modules', $course_module_data);
        
        // modifico i permessi sul feedback docenti
        if ($isDocente) {
            $context = context::instance_by_id($context_id, MUST_EXIST);
            role_change_permission(5, $context, 'mod/feedback:view', CAP_PREVENT); // lo Studente NON puo' vedere il feedback
            role_change_permission(5, $context, 'mod/feedback:complete', CAP_PREVENT); // lo Studente NON puo' compilare il feedback
            role_change_permission(5, $context, 'mod/feedback:viewanalysepage', CAP_PREVENT); // lo Studente NON puo' vedere la pagina di analisi
        }

        $transaction->allow_commit();
        return $feedback_id;
    } catch (Exception $e) {
        $transaction->rollback($e);
    }
    
    return false;
}

function creaDomandeFeedbackDaModello($id_feedback, $isDocente, $tipo_budget = null, $is_corso_obiettivo_studenti = false) {
    global $DB;
    
    if ($isDocente) {
        $param = get_parametro('p_f2_modello_feedback_ND'); // template questionario per i docenti
    } else if ($is_corso_obiettivo_studenti) {
        $param = get_parametro('p_f2_modello_feedback_QO'); // template questionario per gli studenti (corso obiettivo)
    } else if ($tipo_budget == 1) {
        $param = get_parametro('p_f2_modello_feedback_QA'); // template questionario per gli studenti (corso in AULA)
    } else if ($tipo_budget == 3) {
        $param = get_parametro('p_f2_modello_feedback_QE'); // template questionario per gli studenti (corso ON LINE)
    } else {
        return false;
    }
    $nome_modello = $param->val_char;
    
    $qry = "SELECT i.* 
            FROM {feedback_item} i
            JOIN {feedback_template} t ON (t.id = i.template)
            where t.name = '$nome_modello'";
    
    $domande_from_template = $DB->get_records_sql($qry);
    
    foreach ($domande_from_template as $domanda_template) {
        $domanda = new stdClass();
        $domanda->template = 0;
        $domanda->feedback = $id_feedback;
        $domanda->teacher_item = $domanda_template->teacher_item;
        $domanda->name = $domanda_template->name;
        $domanda->label = $domanda_template->label;
        $domanda->presentation = $domanda_template->presentation;
        $domanda->hasvalue = $domanda_template->hasvalue;
        $domanda->position = $domanda_template->position;
        $domanda->required = $domanda_template->required;
        $domanda->typ = $domanda_template->typ;
        $domanda->dependitem = $domanda_template->dependitem;
        $domanda->dependvalue = $domanda_template->dependvalue;
        $domanda->options = $domanda_template->options;
        
        $DB->insert_record('feedback_item', $domanda);
    }
}

function encode_str($str){
        $str = mb_convert_encoding ($str,'UTF-8', 'Windows-1252');
	return str_replace('’', '\'', $str);
}

function get_categories_for_catalog() {

    global $DB;

    $sql = "SELECT id, name
            FROM {course_categories}
            ORDER BY id";

    $rs = $DB->get_recordset_sql($sql);
    $categories = array();

    foreach($rs as $cat) {
        $categories[(string) $cat->id] = $cat->name;
    }
    
    $rs->close();
    return $categories;
}


// effettua l'associazione dei template di notifica di AUTORIZZAZIONE e di CANCELLAZIONE per il corso corrente
function associaTemplatesNotifica($courseid, $tipo_budget) {
    
    if ($tipo_budget == 1) { // AULA
    
        // inserisco il record per l'autorizzazione
        $notif_autorizzazione_aula = id_notifica_predefinita(AUTORIZZAZIONE, AULA);
        if (!$notif_autorizzazione_aula) return false;
        if (!add_template_course($courseid, $notif_autorizzazione_aula->id, null, AUTORIZZAZIONE)) return false;
        // inserisco il record per la cancellazione
        $notif_cancellazione_aula = id_notifica_predefinita(CANCELLAZIONE, AULA);
        if (!$notif_cancellazione_aula) return false;
        if (!add_template_course($courseid, $notif_cancellazione_aula->id, null, CANCELLAZIONE)) return false;
        
    } else if ($tipo_budget == 3) { // ON LINE
        
        // inserisco il record per l'autorizzazione
        $notif_autorizzazione_online = id_notifica_predefinita(AUTORIZZAZIONE, ONLINE);
        if (!$notif_autorizzazione_online) return false;
        if (!add_template_course($courseid, $notif_autorizzazione_online->id, null, AUTORIZZAZIONE)) return false;
        // inserisco il record per la cancellazione
        $notif_cancellazione_online = id_notifica_predefinita(CANCELLAZIONE, AULA);
        if (!$notif_cancellazione_online) return false;
        if (!add_template_course($courseid, $notif_cancellazione_online->id, null, CANCELLAZIONE)) return false;
    }
    
    return true;
}