<?php
// $id$
error_reporting(-1);


require_once($CFG->dirroot.'/mod/facetoface/lib.php');
require_once($CFG->dirroot.'/f2_lib/course.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/f2_lib/management.php');
require_once($CFG->dirroot.'/local/f2_support/lib.php');
require_once($CFG->dirroot.'/lib/datalib.php');
define("ID_NOTIF_MAIL_SPESA", "7");
//AK-LM: i valori per le seguenti costanti devono esistere ed essere univoci sulla tab. mdl_f2_notif_tipo
define("F2_TIPO_NOTIF_AUTORIZZAZIONE", "Autorizzazione");
define("F2_TIPO_NOTIF_CANCELLAZIONE",  "Cancellazione");
define("F2_TIPO_NOTIF_INDIVIDUALE",    "Individuale");
/**
 * Restituisce l'id per la notifica di tipo $name.
 * @global type $DB
 * @param string $name    Identifica uno dei tipi di notifica definiti a sistema.
 * @return int  L'id numerico che corrisponde al tipo di notifica identificato da $name.
 */
function get_tipo_notif_byname($name) {
    global $DB;
    return $DB->get_field('f2_notif_tipo', 'id', array('nome'=>$name), MUST_EXIST);
}
/**
 * @param string $where parametro per affinare la ricerca
 * @param stdClass  $data contiene i parametri per la paginazione, l'ordinamento e la ricerca
 * @return stdClass contiene il numero di records e un oggetto dati che contiene tutti i valori di tutte le notifiche
 */
function get_templates ($data,$where="",$all_canali=0){
	global $DB;

	if($all_canali==1){
		$canali = "";
	}else{
		$canali = " ntemp.canale = ".$data->canale." AND ";
	}
	
	if($data->id_tipo_notif == -1){
		$tipo_notifica = " ";
	}else{
	$tipo_notifica =	" ntemp.id_tipo_notif = ".$data->id_tipo_notif."				AND ";
	}
	
	$sql_query="SELECT 	
						ntemp.id	         ,
						ntemp.title          ,
						ntemp.description    ,
						ntemp.subject        ,
						ntemp.message        ,
						ntemp.id_tipo_notif  ,
						ntemp.stato          ,
						ntipo.nome           ,
						ntipo.segnaposto	 ,
						ntemp.canale		 ,
						ntemp.predefinito
				FROM 	
						{f2_notif_templates} ntemp,
						{f2_notif_tipo} ntipo
				WHERE 
					ntemp.id_tipo_notif = ntipo.id 								AND
					ntemp.title LIKE '%".$data->nome."%' 	AND
					".$tipo_notifica."
					".$canali."
					ntemp.stato = ".$data->stato." ".$where."
				ORDER BY ".$data->column." ".$data->sort;
	$results = $DB->get_records_sql($sql_query,NULL,$data->page*$data->perpage,$data->perpage);
	$results_count=$DB->count_records_sql("SELECT count(*) from ($sql_query) as tmp");
	
	$return				= new stdClass;
	$return->count		= $results_count;
	$return->dati		= $results;
	return $return;
}

/**
 * restituisce i dati della notifica con id passato per argomento
 * @param int $id_template id template
 * @return stdClass 
 */
function get_template ($id_template){
	global $DB;

	$sql_query="SELECT 	
						*
				FROM 	
						{f2_notif_templates} ntempl
				WHERE 
					ntempl.id = ".$id_template;
	$results = $DB->get_record_sql($sql_query);
	return $results;
}

/**
 * se non viene passato nessun parametro restituisce tutti i tipi di notifica
 * se viene passato l'id della notifica restituisce un oggetto contenente l'associazione id-nome notifica
 * @param int $id id tipo notifica
 * @return stdClass 
 */
function get_tipo_notif ($id=-1,$notin='3'){
	global $DB;
	
	if($id == -1)
    {
        $where="WHERE nt.id NOT IN (".$notin.")";
    }
	else 
    {
        $where = "WHERE 
                nt.id = ".$id;
    }
		
	$sql_query="SELECT 	
						*
				FROM 	
						{f2_notif_tipo} nt ".$where;

	$results = $DB->get_records_sql($sql_query);

	return $results;
}

/**
 * inserisce un nuovo template
 * @param stdClass $dati_template dati dei template da inserire
 * @return 	true se la cancellazione di tutti i fornitori va a buon fine
 * 			false altrimenti
 */
function insert_template($dati_template){
global $DB;

$insert = 0;	
	
	if($dati_template->predefinito){
		$id_notifica_predefinita = id_notifica_predefinita($dati_template->id_tipo_notif,$dati_template->canale);//controllo se è già presente una notifica predefinita
		if($id_notifica_predefinita){
			$parametro= new stdClass();
			$parametro->id = $id_notifica_predefinita->id ;
			$parametro->predefinito = 0;
				
			$result_update = update_template($parametro);
			
			if($result_update){
				$id = $DB->insert_record('f2_notif_templates', $dati_template, $returnid=true, $bulk=false);
				return $id;
			}else{
				return 0;
			}
		}else{
			$id = $DB->insert_record('f2_notif_templates', $dati_template, $returnid=true, $bulk=false);
			return $id;
		}
	}else{
		$id = $DB->insert_record('f2_notif_templates', $dati_template, $returnid=true, $bulk=false);
			return $id;
	}
}


/**
 * @param stdClass $dati_template dati dei template da cancellare
 * @return 	true se la cancellazione di tutti i fornitori va a buon fine
 * 			false altrimenti
 */
function delete_template($dati_template){
	global $DB;
	
	$esito = 1;
	
	foreach($dati_template as $id_template){
		$id = $id_template;
		
			$parametro= new stdClass();
			$parametro->id = $id;
			$parametro->stato = -1;
		
		if(!$DB->update_record('f2_notif_templates', $parametro)){
			$esito = 0;
		}
	}
	
	if(!$esito)
		return false;
	else
		return true;	
}

/**
 * aggiorna i campi della notifica
 * @param stdClass $dataobject nuovi dati sulle funzionalità da aggiornare
 */
function update_template ($data){
	global $DB;
	
	$insert = 0; //Esito aggiornamento vecchio template con il campo predefinito a 0
	$esito = 0;  //Esito aggiornamento template
	if($data->predefinito){
		$id_notifica_predefinita = id_notifica_predefinita($data->id_tipo_notif,$data->canale);//controllo se è già presente una notifica predefinita e ricavo l'id
		if($id_notifica_predefinita)
			{
				$parametro= new stdClass();
				$parametro->id = $id_notifica_predefinita->id ;
				$parametro->predefinito = 0;
					
				if($DB->record_exists('f2_notif_templates', array('id' => $data->id))){
				//Imposto a 0 il campo predefinito della vecchia notifica
					if($DB->update_record('f2_notif_templates', $parametro)){
						$insert = 1;
					}
				}
			}
			else
			{
				$insert = 1;
			}
	}else
	{
	$insert = 1;
	}

	if($insert == 1){//Se l'update della vecchia notifica è andata a buon fine aggiorno la nuova modifica con i nuovi campi 
		if($DB->record_exists('f2_notif_templates', array('id' => $data->id))){
				if($DB->update_record('f2_notif_templates', $data)){
					$esito = 1;
				}
		}
		return $esito;
	}
}

/**
 * associa il la notifica al corso o all'edizione
 * @param int $id_corso id corso
 * @param int $id_notif_templates id notifica
 * @param int $id_edizione id edizione
 * @param int $id_tipo_notif id tipo notifica
 * @return boolean se è andata a buon fine
 */
function add_template_course($id_corso,$id_notif_templates,$id_edizione=null,$id_tipo_notif){
    global $DB, $CFG;

    $parametro= new stdClass();
    $parametro->id_corso = $id_corso;
    $parametro->id_notif_templates = $id_notif_templates;
    $parametro->id_edizione = $id_edizione;
    $parametro->id_tipo_notif = $id_tipo_notif;
    
    $notif = $DB->get_record_sql("
                SELECT
                        id 
                FROM 
                        {$CFG->prefix}f2_notif_corso
                WHERE 
                        id_corso = $id_corso AND 
                        id_tipo_notif = $id_tipo_notif");
    
    if (!$notif) {
        // INSERIMENTO
	if($DB->insert_record('f2_notif_corso', $parametro, $returnid=true, false))
		return true;
	else
		return false;
                            
    } else {
        // AGGIORNAMENTO
        $parametro->id = $notif->id;
        if($DB->update_record('f2_notif_corso', $parametro))
		return true;
	else
		return false;
    }
}

/**
 * restituisce le notifiche associate al corso e l'edizione
 * @param int $id_corso id corso
 * @param int $id_edizione se non viene passato $id_edizione restituisce tutte le notifiche assiciate al corso dove l'edizione è null
 * @param int $id_tipo_notif se non viene passato $id_tipo_notif restituisce tutte le notifiche assiciate al corso
 */
function get_template_course($id_corso,$id_edizione=null,$id_tipo_notif=0){
	global $DB;

	if(!$id_edizione)
		$id_edizione = 'is NULL';
	else
		$id_edizione = '= '.$id_edizione;
		
	if($id_tipo_notif){
		$where_tipo_notifica = "AND nc.id_tipo_notif = ".$id_tipo_notif;
	}else{
		$where_tipo_notifica = "";
	}
		
	$sql_query="SELECT 	
						*
				FROM 	
						{f2_notif_corso} nc
				WHERE 
					nc.id_corso = ".$id_corso." AND
					nc.id_edizione ".$id_edizione." ".$where_tipo_notifica;

	$results = $DB->get_records_sql($sql_query);
	return $results;
}

/**
 * @param $dati_template_course dati dei template del corso da cancellare
 * @return 	true se la cancellazione di tutti i template va a buon fine
 * 			false altrimenti
 */
function delete_template_course($dati_template_course){
	global $DB;
	
	$esito = 1;
	
	foreach($dati_template_course as $id_template){
		$id = $id_template;
		
			$parametro= new stdClass();
			$parametro->id = $id;
			$parametro->stato = -1;
		
		if(!$DB->delete_records('f2_notif_corso', array('id'=>$id))){
			$esito = 0;
		}
	}
	
	if(!$esito)
		return false;
	else
		return true;	
}

/**
 * Questa funzione controlla se nella tabella f2_notif_corso esiste già una riga con i parametri passati
 * @param int $dataobject nuovi dati sulle funzionalità da aggiornare
 * @param int $dataobject nuovi dati sulle funzionalità da aggiornare
 * @param int $dataobject nuovi dati sulle funzionalità da aggiornare
 * @return stdClass Object oggetto record che esiste nella tabella
 */
function get_notif_course_edizione_tipo ($id_corso,$id_edizione=null,$id_tipo){

	global $DB;
	if($id_record=$DB->get_record('f2_notif_corso', array('id_corso' => $id_corso,'id_tipo_notif' => $id_tipo,'id_edizione' => $id_edizione)))
		return $id_record;
	else
		return $id_record;
}

/**
 * aggiorna i campi della tabella f2_notif_corso
 * @param stdClass $data nuovi dati sui campi da aggiornare
 * @return boolean
 */
function update_template_course ($data){
	global $DB;
		if($DB->update_record('f2_notif_corso', $data))
			return true;
		else
			return false;
}

/**
 * aggiorna i campi della tabella f2_notif_corso
 * @param stdClass $data nuovi dati sui campi da aggiornare
 * @return boolean
 */
function create_message_mail ($msg,$id_notifica){
	global $DB;
		if($DB->update_record('f2_notif_corso', $data))
			return true;
		else
			return false;
}

/**
 * Ritorna l'array di segnaposto
 * @param int $id_tipo id tipo notifica
 * @return array
 */
function get_segnaposto($id_tipo_notifica){
	global $DB;

	$sql="
			SELECT
					*
			FROM
					{f2_notif_tipo} nf
			WHERE
					nf.id = ".$id_tipo_notifica;
	$result=$DB->get_record_sql($sql);

	$segnaposto = explode(',' , $result->segnaposto);
		
return $segnaposto;
}

/**
 * Questa funzione: - Calcola tutti i segnaposto associati all'edizione
 *					- Calcola tutti gli utenti associati all'edizione ("se non viene passato nessun utente fra i parametri")
 *					- Salva la mail nella tabella f2_notif_template_mailqueue
 *
 * @param int $id_edizione id sessione
 * @param int $id_tipo_notif id tipo notifica
 * @param $list_user Array(stdClass[userid]=>id_user) /--///es. Array ( [2] => stdClass Object ([userid]=> 2) [3] => stdClass Object ([userid]=> 3))///--/
 * @param $list_user lista degli utenti a cui mandare la mail
 * Se non viene passato nessun utente vengono calcolati in automatico tutti gli utenti associati all'edizione
 * @return 
 */
function upload_mailqueue($id_edizione,$id_tipo_notif,$list_user=array(),$extra=array()){
	global $USER, $DB,$TYPES,$CFG;
	
	$return_user_mail_sent=array();
	
	//Recupero i valori sirp e sirpdata
		$sql_sirp_sirpdata = "SELECT
									MAX(tmp.sirp) as sirp,
									MAX(tmp.sirpdata) as sirpdata
								FROM
									(
									SELECT 
										if(fsd.fieldid =(SELECT
													fsf.id
												FROM
													mdl_facetoface_session_field fsf
												WHERE
													fsf.shortname = 'sirp'),fsd.data,'') as sirp,
										if(fsd.fieldid = (SELECT
													fsf.id
												FROM
													mdl_facetoface_session_field fsf
												WHERE
													fsf.shortname = 'sirpdata'),fsd.data,'') as sirpdata
									FROM 
											mdl_facetoface_session_data fsd
										WHERE fsd.sessionid	= ".$id_edizione."
									) tmp";							
		$result_sql_sirp_sirpdata = $DB->get_record_sql($sql_sirp_sirpdata);		
		
	//Rcavo tutti i valori associati all'edizione
		$sql =  "
				SELECT
						c.fullname 														as fullname_corso,
						c.id 															as id_corso,
						fs.id 															as id_edizione,
						DATE_FORMAT(FROM_UNIXTIME(MIN(fsd.timestart)),'%d/%m/%Y  %H:%i') 	as data_inizio_edizione,
						DATE_FORMAT(FROM_UNIXTIME(MAX(fsd.timefinish)),'%d/%m/%Y  %H:%i') 	as data_fine_edizione,
						ac.viaente														as viaente,			
						ac.localita     												as localita,
						ac.orario														as orario,
							case ac.flag_dir_scuola
								when 'S' then (
												SELECT f.denominazione
												FROM {f2_fornitori} f
												WHERE f.id = ac.id_dir_scuola)
								when 'D' then (
												SELECT o.fullname
												FROM {org} o
												WHERE o.id = ac.id_dir_scuola)
						end 															as ente	,
						ac.cf															as credits,
						ac.durata														as durata,
						c.idnumber														as idnumber
				FROM
						{course} c,
						{facetoface_sessions} fs,
						{facetoface} f,
						{facetoface_sessions_dates} fsd,
						{f2_anagrafica_corsi} ac
				WHERE
						fs.facetoface = f.id		AND
						f.course = c.id				AND
						fs.id = ".$id_edizione."	AND
						fsd.sessionid = fs.id		AND
						ac.courseid = c.id			
				";
			$result_sql = $DB->get_record_sql($sql);
		//Recupero l'id del corso
			$courseid = $result_sql->id_corso;
		
		//creo l'array di valori da associare ai segnaposto
			$replacements[10] = $result_sql->fullname_corso;
			$replacements[9] = $result_sql->id_corso;
			$replacements[8] = $result_sql->id_edizione;
			$replacements[7] = $result_sql->data_inizio_edizione;
			$replacements[6] = $result_sql->data_fine_edizione;
			$replacements[5] = $result_sql->viaente;
			$replacements[4] = $result_sql->localita;
			$replacements[3] = $result_sql->orario;
			$replacements[2] = $result_sql->ente;
			$replacements[1] = $result_sql->credits;
			$replacements[0] = $result_sql->durata;

	//Se non viene passato nessun utente vengono calcolati in automatico tutti gli utenti associati all'edizione
		if(!$list_user){
			//Recupero tutti gli utente ISCRITTI all'edizione
				$list_user = get_user_session_by_status ($id_edizione,MDL_F2F_STATUS_BOOKED);
		}
		
	//Recupero l'id della notifica associata all'edizione
	//Se non c'è nessuna notifica mi viene restituita quella del corso
	
		/*$notifica_edizione = get_template_course($courseid,$id_edizione,$id_tipo_notif);		//ELIMINARE SOSTITUITA CON get_template_corso_edizione
		foreach($notifica_edizione as $notif_edizione){
				$id_notifica = $notif_edizione->id_notif_templates;//Id notifica
			break;
		}*/
		
		$id_notifica = get_template_corso_edizione($courseid,$id_edizione,$id_tipo_notif);
		//controllare se è presente la notifica
	
	//Recupero la notifica associata all'edizione
		$notifica = get_template ($id_notifica);

	//Recupero i segnaposto associati al tipo di di notifica
		$segnaposto = get_segnaposto($id_tipo_notif);
	//Recupero il segnaposto periodo
		$periodo = periodo_sessions($id_edizione);       
		if($periodo->timestart == $periodo->timefinish)
			$periodo = date('d/m/Y',$periodo->timestart);
		else
			$periodo = date('d/m/Y',$periodo->timestart)."-".date('d/m/Y',$periodo->timefinish);
	
		foreach($list_user as $id_user){

			$users = user_get_users_by_id(array($id_user->userid));//Restituisce un array di utenti passati per argomento
	
			foreach($users as $user){
				$firstname = $user->firstname;
				$lastname = $user->lastname;
				$email = $user->email;
				$username = $user->username;
				
			//Ricavo il settore dell'utente destinatario dalla mail
				$settore = get_settore_utente($id_user->userid);

			//Ricavo la direzione dell'utente destinatario dalla mail
				$direzione = get_direzione_utente($id_user->userid);

			//Salvo valori da associare ai segnaposto
				$replacements[11] = $firstname;
				$replacements[12] = $lastname;
				$replacements[13] = $email;
				$replacements[14] = $result_sql_sirp_sirpdata->sirp;
				$replacements[15] = $result_sql_sirp_sirpdata->sirpdata;
				$replacements[16] = $settore['name'];
				$replacements[17] = $direzione['name'];
				$replacements[18] = $periodo;
				$replacements[19] = $result_sql->idnumber;
				$replacements[20] = $CFG->wwwroot.'/pix';
				
			//Modifico il messaggio originale inserendo i valori ai segnaposto
				$testo_msg = preg_replace($segnaposto,$replacements,$notifica->message);
                        //Modifico l'oggetto originale inserendo i valori ai segnaposto
				$testo_oggetto = preg_replace($segnaposto,$replacements,$notifica->subject);
                        //recupero l'indirizzo email del mittente dalla tabella f2_parametri
                                $param_sendmail_from = get_parametro('f2_sendmail_from');
                                $sendmail_from = $param_sendmail_from->val_char;
                                
			//Preparo la mail da inserire in notif_template_mailqueue 		
				$parametri=new stdClass();
				$parametri->sessionid      = $id_edizione;
				$parametri->useridfrom    = $USER->id;
				$parametri->useridto      = $id_user->userid;
				$parametri->mailfrom      = $sendmail_from;
				$parametri->mailto        = $email;
				$parametri->subject       = $testo_oggetto;
				$parametri->attachment    = '';
				$parametri->message       = $testo_msg;
				$parametri->time          = time();
				$parametri->mailtemplate  = $id_notifica;

				
			//se è già stata inviata la mail di autorizzazione la invio 
			
			$dati_facetoface_signups = get_facetoface_signups ($id_edizione,$id_user->userid);
			
			if($id_tipo_notif == 1){//Se il tipo di notifica è autorizzazione invio la mail senza problemi
			//invio mail
				$DB->insert_record('f2_notif_template_mailqueue', $parametri, $returnid=true, $bulk=false);	//Inserisco la mail in notif_template_mailqueue
				update_f2_send_notif_facetoface_signups ($dati_facetoface_signups->id,1); //Inserisco il flag 1 ("f2_send_notif") in facetoface_signups
				//Salvo gli utenti a cui è stata inviata la mail
					$return_user_mail_sent[] = $id_user->userid;
					
			}else if($id_tipo_notif == 2){//Se il tipo di notifica è cancellazione controllo se gli era stata inviata la mail di cancellazione
			
					if($dati_facetoface_signups->f2_send_notif == 1){
						$DB->insert_record('f2_notif_template_mailqueue', $parametri, $returnid=true, $bulk=false);	
						update_f2_send_notif_facetoface_signups ($dati_facetoface_signups->id,1);
						//Salvo gli utenti a cui è stata inviata la mail
							$return_user_mail_sent[] = $id_user->userid;
					}
					//altrimenti non faccio nulla "non invio la mail"
			
			}
				//controllare se va a buon fine la insert..
			break;
			}
		}
		
		return $return_user_mail_sent;
}


/**
 * Questa funzione: - Ritorna l'id della notifica associata all'edizione,
 *					  se non è associata nessuna notifica all'edizione ritorna il l'id della notifica associata al corso
 *					
 * @param int $id_edizione id edizione
 * @param int $id_corso id corso
 * @param int $id_tipo_notifica id notifica
 * @return stdClass() $return_notifiche (id_notifica_autorizzazione,id_notifica_cancellazione)
 */
function get_template_corso_edizione($id_corso,$id_edizione,$id_tipo_notifica){
        $id_notifica = false;
	
	//controllo se è presente la notifica dell'edizione
		$notifica_edizione = get_template_course($id_corso,$id_edizione,$id_tipo_notifica);
		foreach($notifica_edizione as $notif_edizione){
				$id_notifica = $notif_edizione->id_notif_templates;//Id notifica
			break;
		}
	//Se non è presente la notifica nell'edizione controllo nel corso
		if(!$id_notifica){
			$notifica_corso = get_template_course($id_corso,$session=null,$id_tipo_notifica);
			foreach($notifica_corso as $notif_corso){
					$id_notifica = $notif_corso->id_notif_templates;//Id notifica
				break;
			}
		}	
	$return_id_notifica = $id_notifica;
	
return $return_id_notifica;
}

/**
 * Questa funzione: - Ritorna le notifiche predefinite
 *					
 * @return stdClass() $result
 */
 
function get_notifica_predefinita(){

	global $DB;

	$sql="
			SELECT
					nt.id,
					nt.id_tipo_notif,
					nt.canale
			FROM
					{f2_notif_templates} nt
			WHERE
					nt.predefinito = 1";
	$result=$DB->get_records_sql($sql);

		
return $result;
}

/**
 * Questa funzione: - Ritorna l'id della notifica predefinita
 * @param int $id_tipo_notif id tipo notifica
 * @param int $canale id canale(aula,on-line)				
 * @return stdClass() $result
 */
function id_notifica_predefinita($id_tipo_notif,$canale){

	global $DB;

	$sql="
			SELECT
					nt.id
			FROM
					{f2_notif_templates} nt
			WHERE
					nt.predefinito = 1     AND
					nt.id_tipo_notif = ".$id_tipo_notif." AND
					nt.canale = ".$canale."";
	$result=$DB->get_record_sql($sql);	
return $result;
}

/**
 * Questa funzione: - Ritorna i dati della tabella notif_mailqueue
 * @param int $num numero records da estrarre (se = 0 restituisce tutti i records)			
 * @return stdClass() $result
 */
function get_notif_mailqueue($num){
	global $DB;

	$sql="
			SELECT
					ntm.*
			FROM
				{f2_notif_template_mailqueue} ntm
			ORDER BY ntm.time ASC";

	$result=$DB->get_records_sql($sql,NULL,0,$num);	
return $result;
}

function f2_notif_cron(){
	print_r("\nStarting notif");

	$param_cron = get_parametro('p_f2_cron_notif');
	$param_lastcron = get_parametro('p_f2_cron_lastcron');
	$param_num_block = get_parametro('p_f2_mail_send_block');

	$time_cron = $param_cron->val_int;
	$lastcron = $param_lastcron->val_int;
	$num_block = $param_num_block->val_int;

	if(($lastcron + $time_cron) <= time()){

		send_notif($num_block);

		//INIZIO: imposto il lastcron alla data attuale
			$parametro= new stdClass();
			$parametro->id = 'p_f2_cron_lastcron';
			$parametro->val_int = time();
			$data = array($parametro);

			update_parametri($data);
		//FINE: imposto il lastcron alla data attuale
		

		print_r("\nFinished notif");
	}
}

/**
 * Questa funzione: - Recupera $num notifiche da inviare dalla tabella (notif_mailqueue) 
 *					- Crea il template "mail" per l'invio della mail con i relativi campi
 *		- se l'invio della mail va a buon fine
 *					- Inserisce nella tabella log_mailqueue il record
 *					- se l'inserimento nella tabella log_mailqueue va a buon fine elimino il record della natifica nella tabella mailqueue
 *		- se non va a buon fine l'invio della mail inserisco l'errore nella tabella log di moodle
 * @param int $num numero records da estrarre (se = 0 restituisce tutti i records)			
 * @return stdClass() $result
 */
 function send_notif($num){

    //$curr_encode = mb_internal_encoding();
    mb_internal_encoding("UTF-8");
	$param_sendmail_from = get_parametro('f2_sendmail_from');
	$sendmail_from = $param_sendmail_from->val_char;

	$param_allow_sending_mail = get_parametro('p_f2_allow_sending_mail');//controllo nella tabella parametri se ho l'autorizzazione di inviare la mail
	$allow_sending_mail = $param_allow_sending_mail->val_int;

	if($allow_sending_mail){
		$notif_mailqueue = get_notif_mailqueue($num);
		foreach($notif_mailqueue as $notif){
				
		// a random hash will be necessary to send mixed content
			$separator = md5(time());

		// carriage return type (we use a PHP end of line constant)
			$eol = "\r\n";//PHP_EOL;
			if(!$notif->attachment || $notif->attachment==""){
				$attachment = "";
			}
			else{

				$string_allegati = preg_split('/;/',$notif->attachment);
				// attachment name
				$allegato = array();
				foreach($string_allegati as $string_allegato){
					$filename = basename($string_allegato);
					
					// encode data (puts attachment in proper format)
					$path = dirname($string_allegato).'/';
					
					// Read the file content
					$file = $path.$filename;
					$file_size = filesize($file);
					$handle = fopen($file, "r");
					$content = fread($handle, $file_size);
					fclose($handle);
					$attachment = chunk_split(base64_encode($content));
					
					$allegato_std = new stdClass();
					$allegato_std->filename = $filename;
					$allegato_std->attachment = $attachment;
					
					$allegato[] = $allegato_std;
				}
				
			}
			
            // main header (multipart mandatory)
            $headers = "From: ".$sendmail_from.$eol;
			$headers .= "Reply-To: ".$sendmail_from.$eol;
			$headers .= "MIME-Version: 1.0".$eol; 
			$headers .= "Content-Type: multipart/mixed; boundary=\"$separator\"";//.$eol.$eol; 
			//$headers .= "Content-Transfer-Encoding: 7bit".$eol;
			//$headers .= "This is a MIME encoded message.".$eol.$eol;
			
            /* le seguenti due istruzioni servono solo in ambiente Windows. In ambiente Unix è necessario impostare
             * il server SMTP nel file di configurazione /etc/mail/sendmail.cf:
             * 
                vi /etc/mail/sendmail.cf 

                search for DS 

                # "Smart" relay host (may be null) 
                DS<ip-address to relay> 

                restart sendmail (>>/etc/init.d/sendmail restart)

             * dove <ip-address to relay> deve essere il nome del server SMTP
             * 
             */
			//ini_set("SMTP","mailfarm-app.csi.it" ); //---
			ini_set('sendmail_from', $sendmail_from); //---
			//ini_set("sendmail_path","/usr/sbin/sendmail");//---
			

            // message
			$message="";
			$message .= "--".$separator.$eol;
			$message .= "Content-Type: text/html; charset=UTF-8".$eol;
			$message .= "Content-Transfer-Encoding: 8bit".$eol.$eol;
			$message .= $notif->message.$eol.$eol;	
			
			if(!$notif->attachment || $notif->attachment==""){
				$attachment = "";
			}
			else{
				
				foreach($allegato as $obj_allegato){
					$message .= "--".$separator.$eol;
					$message .= "Content-Type: application/pdf; name=\"".$obj_allegato->filename."\"".$eol;
					$message .= "Content-Transfer-Encoding: base64".$eol;
					$message .= "Content-Disposition: attachment; name=\"".$obj_allegato->filename."\"".$eol.$eol;
					$message .= $obj_allegato->attachment.$eol.$eol;
				}

			}

            // subject
            $subject = mb_encode_mimeheader($notif->subject,'UTF-8','Q');
			//$subject = utf8_decode($notif->subject);

//debug
//print_r("\r\n---------------\r\n");
//print_r(mb_detect_encoding($notif->message));
//print_r("\r\n---------------\r\n");
//print_r($curr_encode);
//print_r("\r\n---------------\r\n");
//print_r($message);
//print_r("\r\n---------------\r\n");
//print_r($headers);
//print_r("\r\n---------------\r\n");

            // send message
			$notif_send = mail($notif->mailto, $subject, $message, $headers);
		//VECCHIO MODULO PER L'INVIO DELLE MAIL SENZA ALLEGATO
		/*
			$headers  = "MIME-Version: 1.0\r\n";
			$headers .= "Content-type: text/html; charset=UTF-8\r\n";

			$headers .= "From: ".$sendmail_from."\r\n";
			$headers .= "Reply-To: ".$sendmail_from."\r\n";
                        //* le seguenti due istruzioni servono solo in ambiente Windows. In ambiente Unix è necessario impostare
                         * il server SMTP nel file di configurazione /etc/mail/sendmail.cf:
                         * 
                            vi /etc/mail/sendmail.cf 

                            search for DS 

                            # "Smart" relay host (may be null) 
                            DS<ip-address to relay> 

                            restart sendmail (>>/etc/init.d/sendmail restart)
                         
                         * dove <ip-address to relay> deve essere il nome del server SMTP
                         * 
                         *
			ini_set("SMTP","mailfarm-app.csi.it" ); //---
			ini_set('sendmail_from', $sendmail_from); //---
			//ini_set("sendmail_path","/usr/sbin/sendmail");//---
			$message = $notif->message;
			$subject = $notif->subject;
			$notif_send = mail($notif->mailto, $subject, $message, $headers);
		*/

			if($notif_send){
				if($esito_insert = insert_notif_template_log($notif)){ //inserisco record in log_mailqueue
						print_r("\nInsert record log_mailqueue id ".$esito_insert."");
					$esito_delete = delete_notif_template_mailqueue($notif->id);//eliminare record dal mailqueue
						print_r("<\nDelete record log_mailqueue id ".$esito_delete."");
				}
			}
			else{
				$id_corso = get_course_by_session($notif->sessionid);
				$error_log = add_to_log($id_corso->id, 'notif', 'add', 'local\f2_notif\lib.php', 'info', '', $notif->useridto);
				print_r("\nInsert record error mdl_log");
			}
		}
	}else{
		print("\nInvio mail non autorizzato");
	}
}
 
 
/**
 * Questa funzione: - Ritorna l'id del record inserito nella tabella otif_template_log
 * @param stdClass $notif oggetto che contiene i campi della tabella con i relativi valori da inserire			
 * @return int $result id record inserito
 */
function insert_notif_template_log($notif){
	global $DB;
	
	$notif->time = time();
	$result = $DB->insert_record('f2_notif_template_log', $notif);
	return $result;
}

/**
 * Questa funzione: - Ritorna l'id del record cancellato nella tabella notif_template_mailqueue
 * @param int $id_notif id del record da cancellare		
 * @return int $esito id record cancellato
 */
function delete_notif_template_mailqueue($id_notif){
global $DB;

	$esito = $DB->delete_records('f2_notif_template_mailqueue', array('id'=>$id_notif));
	return $esito;
}

/**
 * Questa funzione: - Ritorna 1 o 0 se il template ha l'allegato
 * @return int 1-0
 */
function if_notif_attachment($id_template){
global $DB;
	$sql_query="SELECT
						nt.attachment
				FROM
						{f2_notif_templates} nt
				WHERE nt.id = ".$id_template;
	
	$results = $DB->get_record_sql($sql_query);
	
	return $results;
	
}
