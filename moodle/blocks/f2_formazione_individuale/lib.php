<?php

// $Id: lib.php 1428 2016-11-21 07:35:14Z l.moretto $
global $CFG;
//require_once($CFG->dirroot.'/local/f2_support/lib.php');
require_once($CFG->dirroot.'/f2_lib/management.php');

// forzature
/**
 * @param string $sort colonna su cui basare l'ordinamento
 * @param string $dir direzione (ASC o DESC)
 * @param string $namesearch cognome da cercare
 * @param int $page
 * @param int $perpage
 * @return stdClass: dati forzature
 */
function get_forzature($data){
	global $DB;

        $sql_query=	"select f.id as forzatura_id, f.codice_fiscale, f.cognome as lastname, f.nome as firstname, f.sesso, f.matricola, f.qualifica, f.cod_direzione, f.direzione, f.cod_settore, f.settore, f.data_fine
			from {f2_forzature} f";
	
        if (isset($data->cognome))
            $sql_query .= " where lower(f.cognome) like lower('%".mysql_escape_string($data->cognome)."%') ";
        
	$sql_query .= " ORDER BY ".$data->column." ".$data->sort;

	$results = $DB->get_records_sql($sql_query,NULL,$data->page*$data->perpage,$data->perpage);
	$results_count=$DB->count_records_sql("SELECT count(*) from ($sql_query) as tmp");
	$return				= new stdClass;
	$return->count		= $results_count;
	$return->dati		= $results;
	return $return;
}

function get_forzatura_or_moodleuser($codice_fiscale) {
    global $DB;
    
    $moodleuser = $DB->get_record('user', array('username'=>$codice_fiscale), '*', MUST_EXIST);
    $forzatura = $DB->get_record('f2_forzature', array('codice_fiscale' => $codice_fiscale));
    
    $scaduta = false;
    if ($forzatura) {
        $now = time();
        if ($now > $forzatura->data_fine) $scaduta = true;
    }
        
    if (!$forzatura || $scaduta) {
        $user_org = get_user_organisation($moodleuser->id);
        if (!is_null($user_org)) {
            if (isDirezione($user_org[0])){
                $moodleuser->cod_direzione = $user_org[2];
                $moodleuser->direzione = $user_org[1];
                $moodleuser->orgfk_direzione = $user_org[0];
            } else if (isSettore($user_org[0])) {
                $id_padre = get_dominio_padre($user_org[0]);
                $direzione = $DB->get_record('org', array('id' => $id_padre), 'shortname, fullname', MUST_EXIST);
                $moodleuser->cod_direzione = $direzione->shortname;
                $moodleuser->direzione = $direzione->fullname;
                $moodleuser->orgfk_direzione = $id_padre;
                $moodleuser->cod_settore = $user_org[2];
                $moodleuser->settore = $user_org[1];
            }
        }
        $moodleuser->sesso = get_data_profile_field_value_for_user('sex', $moodleuser->id);
        $moodleuser->category = get_data_profile_field_value_for_user('category', $moodleuser->id);
        $moodleuser->ap = get_data_profile_field_value_for_user('ap', $moodleuser->id);
        return $moodleuser;
    } else {
        $moodleuser->idnumber = $forzatura->matricola;
        $moodleuser->cod_direzione = $forzatura->cod_direzione;
        $moodleuser->direzione = $forzatura->direzione;
        $moodleuser->orgfk_direzione = $forzatura->orgfk_direzione;
        $moodleuser->cod_settore = $forzatura->cod_settore;
        $moodleuser->settore = $forzatura->settore;
        $moodleuser->category = $forzatura->qualifica;
        $moodleuser->sesso = $forzatura->sesso;
        $moodleuser->ap = $forzatura->ap;
		$moodleuser->email = $forzatura->e_mail;
    }
    return $moodleuser;
}

/**
 * @param int $forzatura_id id della forzatura da cancellare
 * @return boolean 	true se la cancellazione avviene correttamente
 * 					false altrimenti
 */
function delete_forzatura($forzatura_id)
{
	global $DB;
	$forzatura_id =  mysql_escape_string($forzatura_id);
	if ($DB->record_exists('f2_forzature' , array ('id' => $forzatura_id)))
	{
		$DB->delete_records('f2_forzature', array ('id'=>$forzatura_id));
		return true;
	}
	else return false;
}

function get_profile_field_value_for_user($field_shortname, $userid) {
    global $DB;
    $field_id = $DB->get_field('user_info_field', 'id', array('shortname'=>$field_shortname), MUST_EXIST);
    $data = $DB->get_field('user_info_data', 'data', array('userid'=>$userid, 'fieldid'=>$field_id), MUST_EXIST);
    return get_profile_field_value($field_shortname, $data);
}

function get_data_profile_field_value_for_user($field_shortname, $userid) {
	global $DB;
	$field_id = $DB->get_field('user_info_field', 'id', array('shortname'=>$field_shortname), MUST_EXIST);
	$data = $DB->get_field('user_info_data', 'data', array('userid'=>$userid, 'fieldid'=>$field_id), MUST_EXIST);
	return $data;
}

/**
 * Restituisce i tipi di corso
 * @var $id  id del tipo corso
 * @return stdClass:  tipo corso (id,descrizione)
 */
function get_tipi_corso($id=NULL)
{
	global $DB;
	if(is_null($id))
		$condition = array('stato'=>'A');
	else
		$condition = array('id'=>$id);

	return $DB->get_records('f2_tipo', $condition, $sort='progr_displ',
					'id,concat_ws(\''.CONST_STR_SEP.'\',id,descrizione) as descrizione');
}

/**
 * Restituisce i modelli di e-mail
 * @var $id  id del modello di e-mail
 * @return stdClass:  modello di e-mail (id,descrizione)
 */
function get_modelli_email($id=NULL)
{
	global $DB;
	if(is_null($id))
		$condition = array('stato'=>'A');
	else
		$condition = array('id'=>$id);

	return $DB->get_records('f2_modelli_email', $condition, $sort='progr_displ',
					'id,concat_ws(\''.CONST_STR_SEP.'\',id,descrizione) as descrizione');
}
 
/**
 * @param int $tipo_corso tipo corso "individuali Giunta,individuali lingua Giunta,individuali Consiglio"
 * @param int $type_page vale 0 se ci troviamo nella pagina gestione corsi, vale 1 se ci troviamo nella pagina modifica corsi,
 * 						1) Nella pagina gestione corsi devono essere visualizzati i corsi per i quali NON è stata richiesta una determina
 * 						2) Nella pagina modifica anagrafica corso devono essere visualizzati i corsi NON ancora archiviati in storico
 * restituisce l'elenco dei corsi
 */
function get_corsiind($data,$type_page=0) {
    global $DB;
	    
    $where="";
   
    
    if(!$type_page){//mi trovo nella pagina visualizza corsi individuali gestione_corsi.php
    	$where = " ci.id_determine <= 0 AND ";
    }else{//mi trovo nella pagina modifica corsi individuali gestione_corsi.php?mod=1
    	//$where = " ci.storico <= 0 AND ";
    	//$where = " ci.storico <= 0 AND (ci.costo > 0 OR ci.costo != '') AND "; // modifica per evitare la gestione dei C.I. senza determina assieme a quelli con spesa
    	//$where = " ci.storico <= 0 AND ci.costo > 0 AND "; // modifica per evitare la gestione dei C.I. senza determina assieme a quelli con spesa
        // modifica per evitare la gestione dei C.I. senza determina assieme a quelli con spesa
        // con la gestione delle offerte speciali 2018 04 20
        // modifica del 2018 10 30 per ovviare alla gestione del consiglio regionale
        if ($data->tipo_corso == 'CIC') {
            $where = " ci.storico <= 0 AND ";
        } else if ($data->tipo_corso == 'CIG') {
            $where = " ci.storico <= 0 AND (ci.costo > 0 OR ci.offerta_speciale > 0) AND ";
        } else {
            $where = " ci.storico <= 0 AND (ci.costo > 0 OR ci.offerta_speciale > 0) AND ";
        }
    }
	$sql_query ="
					SELECT 
						ci.id,
						u.id as userid,
						u.lastname as cognome,
						u.firstname as nome,
						u.username,
						ci.data_inizio,
						ci.titolo,
						ci.codice_archiviazione,
						ci.codice_fiscale
					FROM
						{f2_corsiind} ci,	
						{user} u	
					WHERE
						ci.userid = u.id AND
						".$where."
						ci.training = '".$data->tipo_corso."'
						";
	
	
	        if (isset($data->dato_ricercato))
				$sql_query .= " AND (lower(u.lastname) like lower('%".mysql_escape_string($data->dato_ricercato)."%')) ";
				//$sql_query .= " OR lower(u.lastname) like lower('%".mysql_escape_string($data->dato_ricercato)."%') ";
				//$sql_query .= " OR lower(concat(u.lastname,u.firstname)) like lower('%".mysql_escape_string($data->dato_ricercato)."%')) ";
	$sql_query .= " ORDER BY ".$data->column." ".$data->sort." ,nome ".$data->sort.",data_inizio DESC";
	$results = $DB->get_records_sql($sql_query,NULL,$data->page*$data->perpage,$data->perpage);
	$results_count=$DB->count_records_sql("SELECT count(*) from ($sql_query) as tmp");
	
	$return				= new stdClass;
	$return->count		= $results_count;
	$return->dati		= $results;
	return $return;
}


/**
 * @param int $course_id Id corso
 * Elimina il corso individuale con id $course_id
 */
function delete_corsiind($course_id)
{
	global $DB;
	if ($DB->record_exists('f2_corsiind', array ('id' => $course_id)))
	{
		$DB->delete_records('f2_corsiind', array ('id'=>$course_id));
                $DB->delete_records('f2_corsiind_anno_finanziario', array ('id_corsiind' => $course_id));
		return true;
	}
	else return false;
}


/**
 * @param int $id Id corso
 * Ritorna i dati relativi al corso individuale con id = $id
 */
function get_corso_ind($id) {
	global $DB;

	$sql_query ="
					SELECT
							*
					FROM
						{f2_corsiind} ci
					WHERE
						ci.id = ".$id;


	$results = $DB->get_record_sql($sql_query);

	return $results;
}


/**
 * Blocca corsi ed assegna codice determina provvisorio
 * @param array $id_all_corso id dei corsi a cui deve essere settato il flag blocked a 1
 */
function blocca_determina($id_all_corso){

		global $DB;
	
		$esito = 1;
		foreach($id_all_corso as $id_course){
			
			$parametro = new stdClass();
			$parametro->id = $id_course;
			$parametro->blocked = 1;
			
			if(!$DB->update_record('f2_corsiind', $parametro)){
				$esito = 0;
			}
		}
	
		if($esito)
			return true;
		else
			return false;
	
}

/**
 * Inserisce nella tabella mdl_f2_determine i dati relarivi al codice provvisorio determina
 * @param stdClass $dati 
 * @param $courses corsi bloccati
 * return id record
 */
function insert_codice_provvisorio_determina($dati,$courses){
	
	global $DB;
	$esito = true;
	
	$id = $DB->insert_record('f2_determine', $dati,true);
	
	if($id){
		foreach($courses as $course){			

			$parametro = new stdClass();
			$parametro->id = $course->id;
			$parametro->blocked = 0;
			$parametro->id_determine = $id;
				
			if(!$DB->update_record('f2_corsiind', $parametro)){
				$esito = false;
			}
		}
	}else{
		$esito = false;
	}
	

	return $esito;	
}


/*
 * Verifica se è gia presente il codice determina provvisorio per il settore specificato CIG,CIC ecc.
 * String $value = codice provvisorio determina
 * return boolean
 */
function if_exist_codice_provvisorio_determina($value,$training){
	global $DB;
	
	$sql = "SELECT 
    			ci.id,ci.id_determine, d.codice_provvisorio_determina,ci.training
			FROM
			    mdl_f2_corsiind ci,
			    mdl_f2_determine d
			WHERE
			    ci.id_determine = d.id AND
				d.codice_provvisorio_determina = ? AND
				ci.training = ?
			";
	
	$result = $DB->record_exists_sql($sql, array($value,$training));
	return $result;
	//return $DB->record_exists('f2_determine', array('codice_provvisorio_determina'=>$value));
}

/**
 * Rimuove il flag di blocco ai corsi bloccati
 * return boolean
 */
function annulla_codice_provvisorio_determina($training){

	global $DB;

	return $DB->execute("
							UPDATE 
								{f2_corsiind}
							SET 
								blocked = 0
							WHERE 
								blocked=1 AND 
								training= '".$training."'"
						);
}

/**
 * Ritorna se ci sono corsi bloccati per il tipo di corsi
 * @param $type_course
 * return boolean
 */
function return_is_blocked($type_course){
	global $DB;
	
	return $DB->record_exists('f2_corsiind',array('training'=>$type_course,'blocked'=>1));
}

/**
 * Rimuove la determina $determinaid e libera gli eventuali corsi agganciati ad essa.
 * @param int $determinaid
 * @return mixed boolean True.
 */
function sblocca_determina_provvisoria($determinaid) {
    global $DB;
    
    $result = true;

    $exist = $DB->record_exists('f2_determine', array('id'=>$determinaid, 'codice_determina'=>NULL));
    if ($exist) {
     
        //apre transazione
        $transaction = $DB->start_delegated_transaction();
        try {
            //sgancia la determina provvisoria dai corsi
            $sql = "UPDATE {f2_corsiind} 
                      SET id_determine = 0
                    WHERE id_determine = ?";
            $DB->execute($sql, array($determinaid));
            //rimuove la determina provvisoria
            $DB->delete_records("f2_determine", array('id'=>$determinaid));
            unset($course, $courses);
            
        } catch (dml_exception $e) {
            $transaction->rollback($e);
        }
        //chiude transazione
        $transaction->allow_commit();

    }
    return $result;
}

/**
 * Ritorna i corsi che hanno una determina provvisoria
 */
function get_codici_determina_provvisori($data){
	global $DB;
	 
$sql_query ="SELECT
                    ci.id_determine,
                    d.codice_provvisorio_determina,
                    d.note,
                    count(ci.id_determine) as numero_corsi_determina_prov
            FROM
                    {f2_corsiind} ci,
                    {f2_determine} d
            WHERE
                    ci.training = :training AND
                    ci.id_determine = d.id AND
                    ISNULL(d.codice_determina) ";
	
	$params = array('training'=>$data->tipo_corso);
	if (isset($data->dato_ricercato)) {
            $casesensitive = false;
            $sql_query .= "AND ".$DB->sql_like('d.codice_provvisorio_determina', ':codice', $casesensitive);
            $params['codice'] = "%{$data->dato_ricercato}%";

//            $sql_query .= " AND (lower(d.codice_provvisorio_determina) like lower('%".mysql_escape_string($data->dato_ricercato)."%')) ";
        }
        
        $sql_query .= " GROUP BY ci.id_determine ORDER BY d.".$data->column." ".$data->sort."";

	$results = $DB->get_records_sql($sql_query, $params, $data->page*$data->perpage, $data->perpage);
	$results_count = $DB->count_records_sql("SELECT count(*) from ($sql_query) as tmp", $params);
	
	$return	= new stdClass;
	$return->count = $results_count;
	$return->dati  = $results;
	return $return;
}


/**
 * Ritorna i corsi che hanno una determina provvisoria o una determina definitiva.
 * Effettua una ricerca per id_determina.
 */
function get_corsi_determina_by_id($data){
	global $DB;
	
	$where = "";
	if(!empty($data->invio_mail)) {
		$where = " AND ci.modello_email > 0";
	}
	
	$sql_query = "
        SELECT
            ci.id as id_course,
            u.username,
            u.firstname,
            u.lastname,
            ci.data_inizio,
            ci.codice_archiviazione,
            ci.titolo,
            d.numero_protocollo,
            d.data_protocollo,
            ci.data_invio_mail,
            u.id as id_utente
        FROM
            {f2_corsiind} ci,
            {f2_determine} d,
            {user} u
        WHERE
            ci.training = '{$data->tipo_corso}' AND
            ci.userid = u.id AND
            ci.id_determine = d.id AND
            ci.id_determine = {$data->id_determina} $where";


	if (isset($data->dato_ricercato))
		$sql_query .= " AND (lower(d.codice_provvisorio_determina) like lower('%".mysql_escape_string($data->dato_ricercato)."%')) ";
	if (isset($data->cerca_determina))
		$sql_query .= " AND (lower(u.lastname) like lower('%".mysql_escape_string($data->cerca_determina)."%')) ";
	
	if (isset($data->cerca_determina)){
		$sql_query .= " ORDER BY u.lastname,u.firstname ASC, ci.data_inizio DESC";
		
	}else{
		$sql_query .= " ORDER BY ".$data->column." ".$data->sort.", firstname ASC";
	}
	 	
	$results = $DB->get_records_sql($sql_query,NULL,$data->page*$data->perpage,$data->perpage);
	$results_count=$DB->count_records_sql("SELECT count(*) from ($sql_query) as tmp");

	$return				= new stdClass;
	$return->count		= $results_count;
	$return->dati		= $results;
	return $return;
}

/**
 * Ritorna i corsi che hanno una determina definitiva in in intervallo di tempo
 */
function get_codici_determina_definitiva($data){
	global $DB;
	
	if(isset($data->no_end_date)){
		$end_date ="";
	}else{
		$end_date = " d.data_determina <= '".$data->end_date."' AND ";
	}
	$sql_query ="	SELECT
				ci.id_determine,
				d.codice_provvisorio_determina,
				d.note,
				count(ci.id_determine) as numero_corsi_determina_prov,
				d.codice_determina,
				d.data_determina,
				d.numero_protocollo,
				d.data_protocollo
			FROM
				{f2_corsiind} ci,
				{f2_determine} d
			WHERE
				ci.training = '".$data->tipo_corso."' AND
				d.data_determina >= '".$data->start_date."' AND
				".$end_date."
				ci.id_determine = d.id AND
				d.codice_determina IS NOT NULL
									";


	if (isset($data->dato_ricercato))
		$sql_query .= " AND (lower(d.codice_determina) like lower('%".mysql_escape_string($data->dato_ricercato)."%')) ";
	$sql_query .= " GROUP BY ci.id_determine ORDER BY d.".$data->column." ".$data->sort."";
	$results = $DB->get_records_sql($sql_query,NULL,$data->page*$data->perpage,$data->perpage);
	$results_count=$DB->count_records_sql("SELECT count(*) from ($sql_query) as tmp");

	$return				= new stdClass;
	$return->count		= $results_count;
	$return->dati		= $results;
	return $return;
}


/**
 * Ritorna i corsi che hanno una determina definitiva e in base ai parametri passati mostra qelli archiviati in storico o non archiviati in storico
 * if $data->archiviato_storico = 1 Ritorna i corsi individuali archiviati
 * if $data->archiviato_storico = 0 Ritorna i corsi individuali non archiviati
 */
function get_all_codici_determina_definitiva($data){
	global $DB;
	
	$archiviato_storico='';
	if (isset($data->archiviato_storico)){
		if($data->archiviato_storico){
			$archiviato_storico = " ci.storico > 0 AND ";
		}else
			$archiviato_storico = " ci.storico = 0 AND ";
	}
	
	$sql_query ="	SELECT
				ci.id,
				ci.id_determine,
				u.lastname as cognome,
				u.firstname as nome,
				d.codice_provvisorio_determina,
				d.note,
				count(ci.id_determine) as numero_corsi_determina_prov,
				d.codice_determina,
				d.data_determina,
				d.numero_protocollo,
				d.data_protocollo,
				ci.titolo,
				ci.codice_archiviazione,
				u.username,
				ci.data_inizio,
				ci.ente,
				ci.storico
			FROM
				{f2_corsiind} ci,
				{f2_determine} d,
				{user} u
			WHERE
				u.id = ci.userid AND
				ci.training = '".$data->tipo_corso."' AND
				".$archiviato_storico."
				ci.id_determine = d.id AND
				d.codice_determina IS NOT NULL
									";


	if (isset($data->dato_ricercato))
		$sql_query .= " AND (lower(u.lastname) like lower('%".mysql_escape_string($data->dato_ricercato)."%')) ";
	$sql_query .= " GROUP BY ci.id ORDER BY ".$data->column." ".$data->sort."";
	$results = $DB->get_records_sql($sql_query,NULL,$data->page*$data->perpage,$data->perpage);
	$results_count=$DB->count_records_sql("SELECT count(*) from ($sql_query) as tmp");

	$return				= new stdClass;
	$return->count		= $results_count;
	$return->dati		= $results;
	return $return;
}

/**
 * Ritorna i corsi che hanno una determina provvisoria
 */
function get_determina_provvisoria($id){
	global $DB;

	$sql_query ="	SELECT
						*
					FROM
						{f2_determine} d
					WHERE
						d.id = ".$id;

	$results = $DB->get_record_sql($sql_query);

	return $results;
}

/**
 * Restituisce la determina definitiva
 */
function get_determina_definitiva($id_determina) {
    global $DB;
    $sql = "SELECT codice_determina FROM {f2_determine} WHERE id = ".$id_determina;
    $result = $DB->get_record_sql($sql);
    return $result->codice_determina;
}

/**
 * Inserisce i deti relativi alla determina
 */
function insert_determina($data,$training){
	global $DB;

	$sql = "SELECT
    			ci.id,ci.id_determine, d.codice_determina,ci.training
			FROM
			    mdl_f2_corsiind ci,
			    mdl_f2_determine d
			WHERE
			    ci.id_determine = d.id AND
				d.codice_determina = ? AND
				ci.training = ?
			";
	$result = $DB->record_exists_sql($sql, array($data->codice_determina,$training));//Controllo se per il tipo di corso (CIG,CIC,ecc.) è già presente un numero di determina uguale

	if($result){
		return false;
	}else{
		try {
			return $DB->update_record('f2_determine', $data);
		}catch (Exception $e){
			return false;
		}
	}
}


function get_lable_training($training){
	
	$param_CIG = get_parametro('p_f2_corsi_individuali_giunta');
	$param_CIL = get_parametro('p_f2_corsi_individuali_lingua_giunta');
	$param_CIC = get_parametro('p_f2_corsi_individuali_consiglio');

	if ($training == $param_CIG->val_char){
		return 'corsi_individualigiunta';
	}else if($training == $param_CIL->val_char){
		return 'corsi_individualilinguagiunta';
	}else if($training == $param_CIC->val_char){
		return 'corsi_individualiconsiglio';
	}
}

/**
 * Ritorna i dati relativo all'id corso individuale passato come parametro
 * fiene effettuata una ricerca per id_determina
 */
function get_scheda_descrittiva_by_id($data){
	global $DB,$CFG;
	
	$sql_query ="	SELECT
				ci.id as id_course,
				u.username,
				u.firstname,
				u.lastname,
				ci.data_inizio,
				ci.codice_archiviazione,
				ci.titolo,
				ci.durata,
				ci.costo,
				ci.localita,
				ci.ente,
				ci.beneficiario_pagamento,
				ci.via,
				ci.partita_iva,
				ci.codice_fiscale,
				ci.note,
				ci.codice_creditore,
				ci.id_determine,
				ci.modello_email,
				ci.cassa_economale
			FROM
				{f2_corsiind} ci,
				{user} u
			WHERE
				ci.id = '".$data->id_corso_ind."' AND
				ci.userid = u.id ";

	$results = $DB->get_record_sql($sql_query);
	return $results;
}

function send_mail_autorizzazione($data){
	global $DB, $USER,$CFG;
	
		//creo l'array di valori da associare ai segnaposto
			$replacements[0] = $data->firstname;
			$replacements[1] = $data->lastname;
			$replacements[2] = $data->titolo;
			$replacements[3] = $data->numero_protocollo;
			$replacements[4] = date('d/m/Y',$data->data_protocollo);
			$replacements[5] = $data->direzione;
			$replacements[6] = $data->cod_direzione;
			$replacements[7] = $data->codice_determina;
			$replacements[8] = date('d/m/Y',$data->data_determina);
			$replacements[9] = $data->ente;
			$replacements[10] = $data->localita;
			$replacements[11]= date('d/m/Y',$data->data);
			$replacements[12]= $data->durata;
			$replacements[13]= $CFG->wwwroot.'/pix';
			
			//Recupero la notifica associata all'edizione
			$notifica = get_template ($data->id_notifica);
			
			//Recupero i segnaposto associati al tipo di di notifica
			$segnaposto = get_segnaposto($data->id_tipo_notif);

			//Modifico il messaggio originale inserendo i valori ai segnaposto
				$testo_msg = preg_replace($segnaposto,$replacements,$notifica->message);

                        //Modifico l'oggetto originale inserendo i valori ai segnaposto
				$testo_oggetto = preg_replace($segnaposto,$replacements,$notifica->subject);
                        //recupero l'indirizzo email del mittente dalla tabella f2_parametri
                                $param_sendmail_from = get_parametro('f2_sendmail_from');
                                $sendmail_from = $param_sendmail_from->val_char;
                                

			//Preparo la mail da inserire in notif_template_mailqueue 		
				$parametri=new stdClass();
				$parametri->sessionid      = 0;
				$parametri->useridfrom    = $USER->id;
				$parametri->useridto      = $data->userid;
				$parametri->mailfrom      = $sendmail_from;
				$parametri->mailto        = $data->mailto;
				$parametri->subject       = $testo_oggetto;
				$parametri->attachment    = $data->attachments;
				$parametri->message       = $testo_msg;
				$parametri->time          = time();
				$parametri->mailtemplate  = $data->id_notifica;

			//invio mail
				if(!$DB->insert_record('f2_notif_template_mailqueue', $parametri, $returnid=true, $bulk=false)){//Inserisco la mail in notif_template_mailqueue
					return false;
				}
				
				update_data_invio_mail_corsiind($data->id_course_ind,$parametri->time);
					//return false;
					
		send_notif(500); // DECOMMENTARE SE SI VUOLE INVIARE LA MAIL SUBITO
				//update_f2_send_notif_facetoface_signups ($dati_facetoface_signups->id,1); //Inserisco il flag 1 ("f2_send_notif") in facetoface_signups
				
				//Salvo gli utenti a cui è stata inviata la mail
					$return_user_mail_sent[] = $data->userid;

				//controllare se va a buon fine la insert..
		return $return_user_mail_sent;
}


function update_data_invio_mail_corsiind($id_course,$time){
	
	global $DB;
	
	$esito = 1;
			
		$parametro = new stdClass();
		$parametro->id = $id_course;
		$parametro->data_invio_mail = $time;
			
		if(!$DB->update_record('f2_corsiind', $parametro)){
			$esito = 0;
		}
	
	if($esito)
		return true;
	else
		return false;
	
}

/**
 * Ritorna i dati relativo all'id corso individuale passato come parametro
 * viene effettuata una ricerca per id_determina
 */
function get_scheda_descrittiva_determine_by_id($data){
	global $DB;

	$sql_query ="	SELECT
				ci.id as id_course,
				u.username,
				u.firstname,
				u.lastname,
				ci.data_inizio,
				ci.codice_archiviazione,
				ci.titolo,
				ci.durata,
				ci.costo,
				ci.localita,
				ci.ente,
				ci.beneficiario_pagamento,
				ci.via,
				ci.partita_iva,
				ci.codice_fiscale,
				ci.note,
				ci.codice_creditore,
				ci.id_determine,
				ci.modello_email,
				d.codice_provvisorio_determina,
				d.note,
				d.codice_determina,
				d.data_determina,
				d.numero_protocollo,
				d.data_protocollo,
				ci.credito_formativo
			FROM
				{f2_corsiind} ci,
				{user} u,
				{f2_determine} d
			WHERE
				ci.id_determine = d.id AND
				ci.id = '".$data->id_corso_ind."' AND
				ci.userid = u.id ";

	$results = $DB->get_record_sql($sql_query);
	return $results;
}


function archivia_corso($data){
	global $DB;

	$id_corso_individuale = $data->id_course;
	
	//$result_determine = $DB->get_records('f2_determine',array('foo'=>'bar'));
	//$dati_determina="";

	
	
	
$dati_partecipazioni = $DB->get_record('f2_partecipazioni',array('id'=>$data->partecipazione));
$dati_archiviazione =$DB->get_record_sql("	SELECT 
													*
											FROM
												{f2_determine} d,
												{f2_corsiind} ci
											WHERE
												ci.id = ".$id_corso_individuale." AND
												ci.id_determine = d.id
												");
$moodleuser = $DB->get_record('user', array('id'=>$dati_archiviazione->userid), '*', MUST_EXIST);

	$dati_forzatura = get_forzatura_or_moodleuser($moodleuser->username);

	if(!($dati_forzatura->cod_direzione || $dati_forzatura->cod_settore)){
		return -1;
	}
	//exit;
	$record = new stdClass();
	$record->matricola = $dati_forzatura->idnumber ;
	$record->cognome = $dati_forzatura->lastname ;
	$record->nome = $dati_forzatura->firstname ;
	$record->sesso = $dati_forzatura->sesso ;
	$record->categoria = $dati_forzatura->category ;
	$record->ap = $dati_forzatura->ap ;
	$record->e_mail = $dati_forzatura->email ;
	$record->cod_direzione = $dati_forzatura->cod_direzione ;
	$record->direzione = $dati_forzatura->direzione ;
	$record->cod_settore = $dati_forzatura->cod_settore ;
	$record->settore = $dati_forzatura->settore ;
	//$record->edizione = '' ;///////////////////////??
	//$record->codcorso = '' ;///////////////////////??
	$record->tipo_corso = 'I' ;///////////////////////??
	//$record->sessione = '' ;///////////////////////??
	$record->data_inizio = $dati_archiviazione->data_inizio ;
	$record->sede_corso = $dati_archiviazione->via ;
	$record->localita = $dati_archiviazione->localita ;
	$record->codcitta = '00' ;//XXXXXXXXXXXXXXXXXXXXXX
	$record->prot = $dati_archiviazione->codice_archiviazione ;
	$record->costo = $dati_archiviazione->costo ;
	$record->af = $dati_archiviazione->area_formativa ;
	$record->to_x = $dati_archiviazione->tipologia_organizzativa ;
	$record->tipo = $dati_archiviazione->tipo ;
	$record->sirp = $dati_archiviazione->numero_protocollo ;
	$record->sirpdata = $dati_archiviazione->data_protocollo ;
	//$record->periodo = '' ;///////////////////////??
	//$record->orario = '' ;///////////////////////??
	$record->titolo = $dati_archiviazione->titolo ;
	$record->durata = $dati_archiviazione->durata ;
	$record->scuola_ente = $dati_archiviazione->ente ;
	$record->note = $dati_archiviazione->note ;
	$record->presenza = $data->presenza ;
	//$record->delibera = '' ;///////////////////////??
	$record->determina = $dati_archiviazione->codice_determina ;
	$record->codpart = $dati_partecipazioni->codpart ;
	$record->descrpart = $dati_partecipazioni->descrpart ;
	//$record->servizio = '' ;///////////////////////??
	$record->sub_af = $dati_archiviazione->sotto_area_formativa ;
	$record->cfa = '00' ;//XXXXXXXXXXXXXXXXXXXXXX
	$record->cfv = $data->credito_formativo_valido ;
	$record->va = $data->verifica_apprendimento ;
	$record->cf = $dati_archiviazione->credito_formativo ;
	$record->te = 0 ;//XXXXXXXXXXXXXXXXXXXXXX
	$record->ac = '00' ;//XXXXXXXXXXXXXXXXXXXXXX
	$record->sf = $dati_archiviazione->segmento_formativo ;
	$record->lstupd = time();//XXXXXXXXXXXXXXXXXXXXXX
	
	$return_insert = $DB->insert_record('f2_storico_corsi', $record, $returnid=true, $bulk=false);
	
	if($return_insert){
		
		$dati_storico_corsiind = new stdClass();
		$dati_storico_corsiind->id = $id_corso_individuale ;
		$dati_storico_corsiind->storico = $return_insert ;
		$update_corsiind_storico = $DB->update_record('f2_corsiind', $dati_storico_corsiind, $bulk=false);
		if($update_corsiind_storico){
			return 1;
		}else{
			return 0;
		}
	}else 
		return 0;	
}



function get_corsi_archiviati($id_corso_archiviato){
	global $DB;

	$corso_archiviato = $DB->get_record('f2_storico_corsi', array('id'=>$id_corso_archiviato));
	print_r($corso_archiviato);exit;
}


/**
 * Ritorna i corsi che hanno una determina definitiva e archiviati in storico
 */
function get_all_corsi_archiviati_definitiva($data){
	global $DB;


	$sql_query ="	SELECT
				sc.*,
				ci.codice_archiviazione,
				ci.ente,
				d.codice_determina,
				d.data_determina,
				u.username
			FROM
				{f2_corsiind} ci,
				{f2_determine} d,
				{f2_storico_corsi} sc,
				{user} u
			WHERE
				u.id = ci.userid AND
				sc.id = ci.storico AND
				ci.training = '".$data->tipo_corso."' AND
				ci.storico > 0 AND
				ci.id_determine = d.id AND
				d.codice_determina IS NOT NULL
									";


	if (isset($data->dato_ricercato))
		$sql_query .= " AND (lower(u.lastname) like lower('%".mysql_escape_string($data->dato_ricercato)."%')) ";
//	$sql_query .= " GROUP BY ci.id_determine ORDER BY ".$data->column." ".$data->sort."";
        //Fix bug: rimosso GROUP BY
	$sql_query .= " ORDER BY ".$data->column." ".$data->sort."";
	$results = $DB->get_records_sql($sql_query,NULL,$data->page*$data->perpage,$data->perpage);
	$results_count=$DB->count_records_sql("SELECT count(*) from ($sql_query) as tmp");

	$return				= new stdClass;
	$return->count		= $results_count;
	$return->dati		= $results;
	return $return;
}


/**
 * Restituisce i corsi senza spesa ed archiviati in storico
 */
function get_all_corsi_senza_spesa_archiviati($data) {
	global $DB;
	$sql_query = "SELECT sc.*, ci.codice_archiviazione, ci.ente, u.username
	FROM {f2_corsiind_senza_spesa} ci, {f2_storico_corsi} sc, {user} u
	WHERE u.id = ci.userid AND sc.id = ci.storico AND ci.training = '".$data->tipo_corso."' AND ci.storico > 0 ";
	if (isset($data->dato_ricercato)) {
		$sql_query .= " AND (lower(u.lastname) like lower('%".mysql_escape_string($data->dato_ricercato)."%')) ";
	}
	$sql_query .= " ORDER BY ".$data->column." ".$data->sort."";
	$results = $DB->get_records_sql($sql_query,NULL,$data->page*$data->perpage,$data->perpage);
	$results_count = $DB->count_records_sql("SELECT count(*) from ($sql_query) as tmp");
	$return	= new stdClass;
	$return->count = $results_count;
	$return->dati  = $results;
	return $return;
}


/**
 * Ritorna il corso archiviato in storico con id $id_corso
 */
function get_corso_archiviato_by_id($id_corso){
	global $DB;

	$sql_query ="SELECT
				sc.*,
				ci.codice_archiviazione,
				ci.ente,
				d.codice_determina,
				d.data_determina,
				ci.durata
			FROM
				{f2_storico_corsi} sc,
				{f2_corsiind} ci,
				{f2_determine} d
			WHERE
				ci.storico = sc.id AND
				ci.id_determine = d.id AND
				sc.id = ".$id_corso;

	$result = $DB->get_record_sql($sql_query);

	return $result;
}


/**
 * Restituisce il corso senza spesa archiviato in storico con id $id_corso
 */
function get_corso_senza_spesa_archiviato_by_id($id_corso) {
	global $DB;
	$sql_query = "SELECT sc.*, ci.codice_archiviazione, ci.ente, ci.durata
			FROM {f2_storico_corsi} sc, {f2_corsiind_senza_spesa} ci
			WHERE ci.storico = sc.id AND sc.id = ".$id_corso;
	$result = $DB->get_record_sql($sql_query);
	return $result;
}


function update_archivia_corso($data){
	global $DB;

	$id_corso_individuale = $data->id_course;

	$dati_partecipazioni = $DB->get_record('f2_partecipazioni',array('id'=>$data->partecipazione));
	
		$dati_modifica_storico_corsiind = new stdClass();
		$dati_modifica_storico_corsiind->id = $id_corso_individuale;
		$dati_modifica_storico_corsiind->presenza = $data->presenza;
		$dati_modifica_storico_corsiind->cfv = $data->credito_formativo_valido;
		$dati_modifica_storico_corsiind->va = $data->verifica_apprendimento;
		$dati_modifica_storico_corsiind->codpart = $dati_partecipazioni->codpart;
		$dati_modifica_storico_corsiind->descrpart = $dati_partecipazioni->descrpart;
		$update_corsiind_storico = $DB->update_record('f2_storico_corsi', $dati_modifica_storico_corsiind, $bulk=false);

		return $update_corsiind_storico;
}


function f2_formazione_individuale_cron(){
	global $DB,$USER;
	
	$param_CIG_arc = get_parametro('p_f2_itg_giorni_archiviazione_automatica');
	$param_CIL_arc = get_parametro('p_f2_itl_giorni_archiviazione_automatica');
	$param_CIC_arc = get_parametro('p_f2_itc_giorni_archiviazione_automatica');
	
	$param_CIG = get_parametro('p_f2_corsi_individuali_giunta');
	$param_CIL = get_parametro('p_f2_corsi_individuali_lingua_giunta');
	$param_CIC = get_parametro('p_f2_corsi_individuali_consiglio');
	
		
	$parametro_CIG = new stdClass;
	$parametro_CIG->val_char_corso	= $param_CIG->val_char;
	$parametro_CIG->giorni_arc		= $param_CIG_arc->val_int;
	
	$parametro_CIL = new stdClass;
	$parametro_CIL->val_char_corso	= $param_CIL->val_char;
	$parametro_CIL->giorni_arc		= $param_CIL_arc->val_int;
	
	$parametro_CIC = new stdClass;
	$parametro_CIC->val_char_corso	= $param_CIC->val_char;
	$parametro_CIC->giorni_arc		= $param_CIC_arc->val_int;
	

	 $parametri = array($parametro_CIG, $parametro_CIL, $parametro_CIC);

	// print_r($parametri);exit;
	 foreach($parametri as $parametro){
	 	if($parametro->giorni_arc != -1){//Se i valore dei giorni di archiviazaione è -1 non deve essere effettuata l'archiviazione automatica
	 	
			 	$sql_courses_to_be_arc = "
		 								SELECT 
			 									id
										FROM 
			 									{f2_corsiind} fc
										WHERE 
			 									fc.data_inizio < UNIX_TIMESTAMP(DATE_SUB(now(),INTERVAL ".$parametro->giorni_arc." DAY)) AND
			 									fc.training = '".$parametro->val_char_corso."' AND
			 									fc.storico <= 0 AND 
			 									fc.id_determine != 0";
			 	$results_courses_to_be_arc = $DB->get_records_sql($sql_courses_to_be_arc);

			 	print_r("\n");
			 	foreach ($results_courses_to_be_arc as $course_arc){
			 	
			 		$data = new stdClass;
			 		$data->id_course = $course_arc->id;
			 		$data->partecipazione = 10; //valori default archiviazione (Codice parecipazione= "7", Descrizione partecipazione = "Assente")
			 		$data->presenza = 0; //valori default archiviazione (Presenza)
			 		$data->credito_formativo_valido = 0; //valori default archiviazione (Credito formativo valido)
			 		$data->verifica_apprendimento = "_";  //valori default archiviazione (Verifica apprendimento)
			 					 		
			 		$result_archiviazione = archivia_corso($data);
			 	//	print_r("yyy".$result_archiviazione."xxx");
			 		if($result_archiviazione == -1){
			 			add_to_log(''.$course_arc->id.'','form_individuale','archiviazione automatica','blocks\f2_formazione_individuale\lib.php','Archiviazione corso individuale:  id corso_individuale = '.$course_arc->id.', esito_archiviazione = NON ARCHIVIATO:non è presente nessuna direzione e/o settore','',$USER->id);
			 			print_r("Attenzione: Courseid ".$data->id_course." non archiviato.");
			 			print_r("Non è possibile procedere con l'archiviazione, non è presente nessuna direzione e/o settore per l'utente.");
			 			print_r("Assegnare una direzione e/o settore all'utente oppure eseguire una forzatura.");
			 			print_r("\n");
			 		}
			 		
			 		else if($result_archiviazione == 1){
			 			add_to_log(''.$course_arc->id.'','form_individuale','archiviazione automatica','blocks\f2_formazione_individuale\lib.php','Archiviazione corso individuale:  id corso_individuale = '.$course_arc->id.', esito_archiviazione = '.$result_archiviazione,'',$USER->id);
				 	//	add_to_log($courseid, $module, $action)
				 //		ob_clean();
				 //		ob_start();
				 		print_r("Courseid ".$data->id_course." archiviato.");
				 		print_r("\n");
				 //		ob_flush();
				 //		ob_clean();
			 		}else{
			 			add_to_log(''.$course_arc->id.'','form_individuale','archiviazione automatica','blocks\f2_formazione_individuale\lib.php','Archiviazione corso individuale:  id corso_individuale = '.$course_arc->id.', esito_archiviazione = '.$result_archiviazione,'',$USER->id);
			 			print_r("Errore archiviazione: Courseid ".$data->id_course." non archiviato.");
			 			print_r("\n");
			 		}
			 	}
	 	}
	 }
	
	//sql_courses_to_be_arc = "";
}

function get_dati_pop_up_dettaglio_mail($dati_function){
	global $DB;
	$sql="SELECT mailto
        		FROM
        		(
	        		(SELECT mailto from mdl_f2_notif_template_mailqueue where useridto = ".$dati_function->id_utente." and time = ".$dati_function->data_invio_mail.")
	        		UNION
	        		(SELECT mailto from mdl_f2_notif_template_log where useridto = ".$dati_function->id_utente." AND time = ".$dati_function->data_invio_mail.")
				) as tmp
				limit 1";
	
	
	return $DB->get_record_sql($sql);
}

function get_anno_corrente() {
  global $DB;
  $sql = 'Select Year(CURDATE()) as cy';
  $anno = $DB->get_record_sql($sql);
  $anni = array($anno->cy-2=>$anno->cy-2, $anno->cy-1=>$anno->cy-1, $anno->cy=>$anno->cy, $anno->cy+1=>$anno->cy+1, $anno->cy+2=>$anno->cy+2);
  return $anni;
}

function get_selected_anno_corrente() {
  global $DB;
  $sql = 'Select Year(CURDATE()) as cy';
  $anno = $DB->get_record_sql($sql);
  return $anno->cy;
}

function get_anno_finanziario($id_course) {
  global $DB;
  $anfin = $DB->get_record('f2_corsiind_anno_finanziario', array('id_corsiind'=>$id_course), 'anno');
  if ($anfin) {
    return $anfin->anno;
  } else {
    return false;
  }
}

function get_id_anno_finanziario($id_course) {
  global $DB;
  $id_af = $DB->get_record('f2_corsiind_anno_finanziario', array('id_corsiind'=>$id_course), 'id');
  if ($id_af) {
    return $id_af->id;
  } else {
    return false;
  }
}

/**
 * Inserisce un record di log
 * @param array datilog
 * @return bool
 */
function corsiindlog($datilog) {
    global $DB;
    $logdata = new stdClass();
    $logdata->msg  = $datilog[0];
    $logdata->data = date('Y/m/d-H:i:s');
    $inslog = $DB->insert_record('f2_corsiind_log', $logdata);
    return $inslog;
} 
