<?php
// $Id: lib.php 1311 2014-08-05 13:04:07Z l.moretto $
global $CFG;
require_once($CFG->dirroot.'/local/f2_support/lib.php');
require_once($CFG->dirroot.'/f2_lib/core.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');
require_once($CFG->dirroot.'/local/f2_notif/lib.php');

// utenti
/**
 * @param string $left lato sinistro della riga
 * @param string $right lato destro della riga
 */
function print_row($left, $right) {
	echo "\n<tr><th class=\"label c0\">$left</th><td class=\"info c1\">$right</td></tr>\n";
}

/**
 * @param stdClass $user rappresenta i dati da mostrare dell'utente: firstname, lastname, cf
 */
function print_summary($user)
{
	echo '<table class="list" summary="">';

	if ($user->firstname) {
		print_row(get_string('firstname', 'block_f2_gestione_risorse') . ':', $user->firstname);
	}

	if ($user->lastname) {
		print_row(get_string('lastname', 'block_f2_gestione_risorse') . ':', $user->lastname);
	}

	if ($user->cf) {
		print_row(get_string('cf', 'block_f2_gestione_risorse') . ':', $user->cf);
	}
	echo "</table>";
}

// fornitori

/**
 *
 * @global object $DB
 * @param type $data
 * @return \stdClass 
 */
function get_fornitori ($data) {
	global $DB;
	
	$sql_query="SELECT 	*
			FROM 	{f2_fornitori} f
			WHERE 
				f.denominazione LIKE '%".mysql_escape_string($data->denominazione)."%'
			ORDER BY ".$data->column." ".$data->sort;
	
	$results = $DB->get_records_sql($sql_query,NULL,$data->page*$data->perpage,$data->perpage);
	$results_count=$DB->count_records_sql("SELECT count(*) from ($sql_query) as tmp");
	
	$return				= new stdClass;
	$return->count		= $results_count;
	$return->dati		= $results;
	return $return;
}
/**
 *
 * @global object $DB
 * @param object Attributi richiesti:
 *   colums - array di colonne da estrarre
 *   column - colonna di ordinamento
 *   sort - direzione di ordinamento
 *   page - n. di pag.
 *   perpage - righe per pag.
 *   tipo_formazione - un'espressione regolare che rappresenta le tipologie di formazione d'interesse.
 *     Ad es. [01]1[01] cerca i fornitori che erogano formazione di tipo obiettivo.
 *     La tupla è così rappresentata [individuale][obiettivo][programmata].
 * @return object Oggetto con attributi "count" e "rs".
 */
function get_fornitori_by_type ($data) {
	global $DB;

	$sql_query = "
		SELECT ".mysql_escape_string(implode(",",$data->columns))." 
		FROM {f2_fornitori} f
		WHERE 
			f.tipo_formazione REGEXP '".mysql_escape_string($data->tipo_formazione)."'
		ORDER BY ".$data->column." ".$data->sort;

	$results = $DB->get_records_sql($sql_query,NULL,$data->page*$data->perpage,$data->perpage);
	$results_count = $DB->count_records_sql("SELECT count(*) from ($sql_query) as tmp");

	$return				= new stdClass();
	$return->count= $results_count;
	$return->rs	  = $results;
	return $return;
}
/**
 * @param int $id id fornitore
 * @return stdClass dati fornitore result set
 */
function get_fornitore ($id){
	global $DB;

	$fornitore = $DB->get_record_sql("
										SELECT 	*
										FROM 	{f2_fornitori} f
										WHERE f.id = ".$id."
									");
	return $fornitore;
}

/**
 * @param string $tipo_formazione
 * @return string tipo_formazione del fornitore
 * Se la prima cifra partendo da destra � 1 significa che � settato "individuale",
 * 2� cifra da destra � 1 settato "obiettivo",
 * 3� cifra da destra � 1 settato "programmata"
 */
function get_tipo_formazione_fornitore($tipo_formazione) {

	if($tipo_formazione){
		$individuale = substr($tipo_formazione, -1);
		$obiettivo = substr($tipo_formazione, -2, 1);
		$programmata =substr($tipo_formazione, -3, 1);

		if($individuale == 1 && ($obiettivo == 1 || $programmata == 1))
			$individuale = ", individuale";
		else if($individuale == 1 && $obiettivo == 0 && $programmata == 0)
			$individuale = "individuale";
		else
			$individuale = "";
			
		if($obiettivo == 1 && $programmata == 1)
			$obiettivo = ", obiettivo";
		else if($obiettivo == 1 && $programmata == 0)
			$obiettivo = "obiettivo";
		else
			$obiettivo = "";
			
		if($programmata == 1)
			$programmata = "programmata";
		else
			$programmata = "";
			
		$str_tipo_formazione=$programmata.$obiettivo.$individuale;
	}

	return $str_tipo_formazione;
}


/**
 * @param int $id_stato
 * @return string stato fornitore
 * $id_stato 1 ritorna "Attivo"
 * else "Non attivo"
 */
function get_stato_fornitore ($id_stato) {
	if($id_stato == 1)
		$stato_fornitore = "Attivo";
	else
		$stato_fornitore = "Non attivo";

	return $stato_fornitore;
}

//Return string
//if $id_preferito 1 ritorna "Si"
//else "No"
/**
 * @param int $id_preferito
 * @return string
 * $id_preferito = 1 ritorna "Si"
 * else "No"
 */
function get_preferito_fornitore ($id_preferito) {
	if($id_preferito == 1)
		$stato_fornitore = "Si";
	else
		$stato_fornitore = "No";

	return $stato_fornitore;
}

//Ritorna la stringa ASC o DESC ricevendo co 
function string_sort($direction) {
	$columndir = $direction == "1" ? "2":"1"; //1 ASC, 2 DESC
	//$columndir = $direction == "ASC" ? "DESC":"ASC";
	return $columndir;
}


/**
 * @param stdClass $dati_fornitore dati del nuovo fornitore
 */
function insert_fornitore($dati_fornitore){
	global $DB;
	$x=$DB->insert_record('f2_fornitori', $dati_fornitore, $returnid=true, $bulk=false);
}

/**
 * @param $dati_fornitori dati dei fornitori da cancellare
 * @return 	true se la cancellazione di tutti i fornitori va a buon fine
 * 			false altrimenti
 */
function delete_fornitori($dati_fornitori){
	global $DB;
	foreach($dati_fornitori as $id_fornitore){
		if($DB->record_exists('f2_fornitori', array('id' => $id_fornitore))){
			if(!$DB->delete_records('f2_fornitori', array('id' => $id_fornitore))){
				return false;
			}
		}
	}
	return true;
}



// formatori
/**
 * @param string $sort colonna su cui basare l'ordinamento
 * @param string $dir direzione (ASC o DESC)
 * @param string $namesearch cognome da cercare
 * @param int $categoriaformatore 	(	1: interno,
 * 										2: interno abiltato docenze interne,
 * 										3: interno abilitato docenze esterne
 * 										4: esterno)
 * @param int $page
 * @param int $perpage
 * @return stdClass: dati formatori: id(userid),formatore_id,lastname,firstname,cf,domain
 */
function get_formatoriRS($data){
	global $DB;

	$sql_query=	"select u.id, f.id as formatore_id, u.lastname, u.firstname, f.cf,
			ifnull((select concat(dom.idnumber, ' - ', dom.fullname) 
                    from {org} dom, {org_assignment} map 
                    where map.userid = u.id 
                    and map.organisationid = dom.id), '-') as domain
			from {f2_formatore} f, {user} u where u.id = f.usrid ";
	
if (!empty($data->cognome_formatore)) {
	$sql_query .= " and lower(u.lastname) like lower('%".mysql_escape_string($data->cognome_formatore)."%') ";
}
	$categoriaformatoresql = '';
	$tipodocenzasql = '';
if (!empty($data->categoria_formatore)) {
	$categoriaformatore = $data->categoria_formatore;
	if ($categoriaformatore == 1) // interno
	{
		$categoriaformatoresql = " and f.categoria = 'I' ";
		$tipodocenzasql = " and (f.tipodoc is null or f.tipodoc = 'T') "; // ????
	}
	else if ($categoriaformatore == 2) // interno abilitato docenze interne
	{
		$categoriaformatoresql = " and f.categoria = 'I' ";
		$tipodocenzasql = " and f.tipodoc = 'I' ";
	}
	else if ($categoriaformatore == 3) // interno abilitato docenze esterne
	{
		$categoriaformatoresql = " and f.categoria = 'I' ";
		$tipodocenzasql = " and f.tipodoc = 'E' ";
	}
	else if ($categoriaformatore == 4) // esterno
	{
		$categoriaformatoresql = " and f.categoria = 'E' ";
	}
	$sql_query .= $categoriaformatoresql.$tipodocenzasql;
}
	$sql_query .= " ORDER BY ".$data->column." ".$data->sort;

	$results = $DB->get_records_sql($sql_query,NULL,$data->page*$data->perpage,$data->perpage);
	$results_count=$DB->count_records_sql("SELECT count(*) from ($sql_query) as tmp");
 	//print_r($sql_query);
	$return				= new stdClass;
	$return->count		= $results_count;
	$return->dati		= $results;
	return $return;
}

function get_non_formatoriRS($data){
	global $DB;
	$sql_query = "select u.id,u.lastname,u.firstname,u.idnumber,
			u.lastaccess from {user} u where u.deleted <> 1 and u.suspended <> 1 and u.id > 2 and not exists 
			(select 1 from {f2_formatore} f where f.usrid = u.id) ";
	$sql_query .=  " and lower(u.lastname) like lower('%".mysql_escape_string($data->cognome_utente)."%') ";
	$sql_query .= " ORDER BY ".$data->column." ".$data->sort;
	$results = $DB->get_records_sql($sql_query,NULL,$data->page*$data->perpage,$data->perpage);
	$results_count=$DB->count_records_sql("SELECT count(*) from ($sql_query) as tmp");
	$return				= new stdClass;
	$return->count		= $results_count;
	$return->dati		= $results;
	return $return;
}

/**
 * @param int $userid userid dell'utente
 * @return string sql per ottenere i dati dell'utente (id,lastname,firstname,cf,domain,lastaccess)
 */
function get_detail_userSQL($userid)
{
	$userid =  mysql_escape_string($userid);
	$sqlsel = "select u.id,u.lastname,u.firstname,u.username as cf,u.city as domain,u.lastaccess, 0 as formatore_id from {user} u ";
	$sqlwhere= " where u.id = ".$userid." ";
	$sqlstr=$sqlsel.$sqlwhere;
	return $sqlstr;
}

/**
 * @param int $formatore_id id del formatore
 * @return string sql per ottenere i dati dell formatore:
 * id (userid), lastname, firstname, formatore_id, piva, tstudio, dettstudio, prof, ente, tipodoc, categoria, cf
 */
function get_detail_formatoreSQL($formatore_id)
{
	$formatore_id =  mysql_escape_string($formatore_id);
	$sqlsel = "select u.id,u.lastname,u.firstname,f.id as formatore_id,f.piva,f.tstudio,f.dettstudio,f.prof,f.ente,f.tipodoc,f.categoria,f.cf from {user} u, {f2_formatore} f ";
	$sqlwhere= " where u.id = f.usrid and f.id =".$formatore_id;
	$sqlstr=$sqlsel.$sqlwhere;
	return $sqlstr;
}

/**
 * @param int  $formatore_id
 * @return stdClass: result set (subafid) dell'associazione formatore_id - subaree formative
 */
function get_form_subaf_mapRS($formatore_id)
{
	global $DB;
	$map = $DB->get_records_sql("select distinct fsub.subafid from {f2_formsubaf_map} fsub where fsub.formid = ".$formatore_id);
	return $map;
}


/**
 * @param int $userid dell'utente che diventa un formatore
 * @param stdClass $dataobject dati del nuovo formatore
 * @return id del formatore inserito 
 */
function insert_formatore($userid,$dataobject)
{
	global $DB;
	$userid =  mysql_escape_string($userid);
	if ($DB->record_exists('f2_formatore' , array ('usrid' => $userid))) return -1;
	else
	{
		$cf = $DB->get_record_sql("select u.username as cf from {user} u where u.id =".$userid);
		$dataobject->cf = $cf->cf;
		$inserted_id = $DB->insert_record('f2_formatore', $dataobject, $returnid=true);
		if (!empty($dataobject->subafids))
		{
			$lstupdt = $dataobject->lstupd;
			foreach ($dataobject->subafids as $subafid)
			{
				$formSubObj = new stdClass;
				$formSubObj->formid = $inserted_id;
				$formSubObj->subafid = $subafid;
				$formSubObj->lstupd = $lstupdt;
				// print_r($formSubObj);exit;
				$DB->insert_record('f2_formsubaf_map', $formSubObj, $returnid=false);
			}
		}
	}
	return $inserted_id;
}

/**
 * @param int $formatore_id id del formatore da aggiornare
 * @param stdClass $dataobject nuovi dati del formatore
 */
function update_formatore($formatore_id,$dataobject)
{
	global $DB;
	$DB->update_record('f2_formatore', $dataobject);
	$lstupdt = $dataobject->lstupd;
	$DB->delete_records('f2_formsubaf_map', array ('formid'=>$formatore_id));
	foreach ($dataobject->subafids as $subafid)
	{
		$formSubObj = new stdClass;
		$formSubObj->formid = $formatore_id;
		$formSubObj->subafid = $subafid;
		$formSubObj->lstupd = $lstupdt;
		$DB->insert_record('f2_formsubaf_map', $formSubObj, $returnid=false);
	}
}

/**
 * @param int $formatore_id id del formatore da cancellare
 * @return boolean 	true se la cancellazione avviene correttamente
 * 					false altrimenti
 */
function delete_formatore($formatore_id)
{
	global $DB;
	$formatore_id =  mysql_escape_string($formatore_id);
	if ($DB->record_exists('f2_formatore' , array ('id' => $formatore_id)))
	{
		$DB->delete_records('f2_formatore', array ('id'=>$formatore_id));
		return true;
	}
	else return false;
}


/**
 * @param stdClass $dataobject nuovi dati sulle funzionalit� da aggiornare
 */
function update_funzionalita($dataobject)
{
	global $DB;
	$DB->update_record('f2_stati_funz', $dataobject);
}

/**
 * @param stdClass $dataobject nuovi dati della sessione da aggiornare
 */
function update_sessione($dataobject)
{
	global $DB;
	$DB->update_record('f2_sessioni', $dataobject);
}

/**
 * @param stdClass $dataobject dati della sessione da aggiungere
 * @return int id della sessione aggiunta
 */
function insert_sessione($dataobject)
{
	global $DB;
	$sid = $dataobject->id;
	if(preg_match("/null/", $sid) === 0) //update 
	{
		update_sessione($dataobject);
		$ins = $DB->get_record_sql("select s.id from {f2_sessioni} s where s.id = ".$dataobject->id);
		$inserted_id = $ins->id;
	}
	else //insert
	{
		$num = $DB->get_record_sql("select ifnull(max(s.numero),0)+1 as numero from {f2_sessioni} s where s.anno = ".$dataobject->anno);
		$new_data_obj = new stdClass;
		$new_data_obj->numero = $num->numero;
		$new_data_obj->anno = $dataobject->anno;
		$new_data_obj->data_inizio = $dataobject->data_inizio;
		$new_data_obj->data_fine = $dataobject->data_fine;
		$new_data_obj->stato = 'c';
		$new_data_obj->percentuale_corsi = $dataobject->percentuale_corsi;
		$inserted_id = $DB->insert_record('f2_sessioni', $new_data_obj, $returnid=true);
	}
	return $inserted_id;
}

/**
 * @param int $anno anno formativo delle sessioni da cancellare
 */
function delete_all_session($anno)
{
	global $DB;
	$anno =  mysql_escape_string($anno);
	$sessionirs = get_sessioniRS($anno);
	$sessioni = $sessionirs->dati;
	foreach ($sessioni as $s)
	{
		$sid = $s->id_sess;
		$DB->delete_records('f2_sessioni', array ('id'=>$sid));
	}
}

function delete_session($sid)
{
	global $DB;
	$DB->delete_records('f2_sessioni', array('id'=>$sid));
}

//funzionalit� - sessioni

/**
 * @return stdClass funzionalit� attive (campo dati dell'oggetto) (id,aperto,descrizione)
 */
function get_funzionalitaRS($tipo="")
{
	global $DB;
	
	if($tipo == "")
		$otherClause = "";
	else
		$otherClause = "AND f.id like '".$tipo."%' ";
	$orderby = " order by f.progr_displ ASC";
	$sqlstr = "select f.id, f.aperto, f.descrizione from {f2_stati_funz} f where f.stato='a' ".$otherClause.$orderby;
	$rstot = $DB->get_records_sql($sqlstr);
	$return	= new stdClass;
	// $return->count	= $DB->count_records_sql("SELECT count(*) FROM (".$sqlstr.") as tmp");
	$return->dati	= $rstot;
	return $return;
}

/**
 * @param int $anno anno formativo corrente
 * @param int optional $addsessione numero sessioni da aggiungere (default:0)
 * @return stdClass dati sessioni (campo dati dell'oggetto) dell'anno formativo (id_sess,stato,data_inizio,data_fine,percentuale_corsi)
 */
function get_sessioniRS($anno=0,$addsessione=0)
{
	global $DB;
	if ($anno == 0) $anno = get_anno_formativo_corrente();
	$orderby = " order by numero ASC";
	$sqlstr = "select s.id as id_sess, s.numero as numero, s.stato as stato, s.data_inizio as data_inizio, s.data_fine as data_fine, s.percentuale_corsi from {f2_sessioni} s where s.anno = ".($anno);
	if ($addsessione > 0)
	{
		for ($i=1; $i<=$addsessione; $i++)
		{
			$sqlstr = $sqlstr." union ( select 'null_".$i."' as id_sess, ifnull(max(s.numero),0)+$i as numero, 'c' as stato, UNIX_TIMESTAMP() as data_inizio, UNIX_TIMESTAMP() as data_fine, 0 as percentuale_corsi from {f2_sessioni} s where s.anno = ".($anno)." )";
		}
	}
	$rstot = $DB->get_records_sql($sqlstr.$orderby);
	$return	= new stdClass;
	// $return->count	= $DB->count_records_sql("SELECT count(*) FROM (".$sqlstr.") as tmp");
	$return->dati	= $rstot;
	// print_r($rstot);
	return $return;
}
//se non viene passato nessun anno viene restituito il totale di tutti gli anni
//visualizza i risultati nella tabella della pagina quando viene cliccato il pulsante inserisci budget
function get_partial_budget($data,$anno=0)
{
	global $DB;
	
	if($anno == 0)
		$where="";
	else
		$where="AND anno = ".$anno;
	
	$sql_query="SELECT 	o.fullname     , o.shortname, 
						p.id           ,
						p.anno         ,
						p.orgfk        ,
						p.settori      ,
						p.dirigenti    ,
						p.personale    ,
						p.ap_poa       ,
						p.totb         ,
						p.criterioa    ,
						p.criteriob    ,
						p.criterioc    ,
						p.criteriod    ,
						p.coefficiente ,
						p.lstupd       ,
						p.usrname      ,
						p.modificato   
			FROM 	
				{f2_partialbdgt} p
                        JOIN    {org} o ON (p.orgfk = o.id)
			WHERE 
				(o.fullname LIKE '%".mysql_escape_string($data->orgfk)."%' 
                                    OR o.shortname LIKE '%".mysql_escape_string($data->orgfk)."%') ".$where."
			ORDER BY o.shortname ".$data->sort;
	
	$results = $DB->get_records_sql($sql_query,NULL,$data->page*$data->perpage,$data->perpage);
	$results_count=$DB->count_records_sql("SELECT count(*) from ($sql_query) as tmp");

	$return				= new stdClass;
	$return->count		= $results_count;
	$return->dati		= $results;
	return $return;	
}

/**
 * Riceve in input un array di oggetti
 * ogni oggetto deve contenere "id => value_id, nome_colonna_tabella => valore_da_settare
 * Es. [id] => p_f2_bdgt_aula_cap [val_float] => 260000
 *
 * Questa funzione viene richiamata quando viene premuto il pulsante "Applica" nella pagina configurazione_parametri.php
 */
function applica_partial_budget($data){
		global $DB;
                $id = '-1';
                foreach ($data as $key => $value) {
                    if (strpos($key,'applica_') !== false) {
                        $id = substr($key, strlen('applica_'));
                    }
                }
//		$id = $data[id];

		$parametro= new stdClass();
		$parametro->id = $id;
		$parametro->settori =$data['settori_'.$id];
		$parametro->ap_poa =$data['ap_poa_'.$id];
		$parametro->totb =$data['settori_'.$id] + $data['ap_poa_'.$id];
		$parametro->criterioa =$data['criterioa_'.$id];
		$parametro->dirigenti =$data['dirigenti_'.$id];
		$parametro->personale =$data['personale_'.$id];
		$parametro->modificato =1;//Questo parametro viene utilizzato nella funzione get_total_partial_budget
								  //vengono aggiornati solo i record che sono stati modificati
	
		$esito = 1;

			if(!$DB->update_record('f2_partialbdgt', $parametro)){
				$esito = 0;
			}

		if(!$esito)
			return false;
		else
			return true;	
}

/**
 * @return Restituisce il totale dei budget parziali
 * Questa funzione viene utilizzata per visualizzare il totale delle colonne nella tabella dei budget parziale della pagina budget/configurazione_parametri.php
 * se non viene passato nessun anno viene restituito il totale di tutti gli anni
 */
function get_total_partial_budget($anno=0)
{
	global $DB;
	if($anno == 0)
			$where="";
	else
			$where="WHERE anno = ".$anno;
	
	$results_tot=$DB->get_record_sql("SELECT
			SUM(criterioa) as tot_criterioa,
			SUM(criteriob) as tot_criteriob,
			SUM(criterioc) as tot_criterioc,
			SUM(criteriod) as tot_criteriod,
			SUM(coefficiente) as tot_coefficiente,
			SUM(settori) as tot_settori,
			SUM(ap_poa) as tot_ap_poa,
			SUM(totb) as tot_totb,
			SUM(dirigenti) as tot_dirigenti,
			SUM(personale) as tot_personale
			FROM {f2_partialbdgt} ".$where);

	return $results_tot;
}

/**
 * Questa funzione aggiorna i totali della tabella f2_partialbdgt quando viene premuto il pulsante "Aggiorna totali"
 * nella pagina configurazione_parametri.php.
 * Aggiorna i totali secondo le specifiche descritte nel "Caso d'uso: Gestione budget"
 */
function update_totali_partial_budget($anno_in_corso){
	global $DB;

	$totali=get_total_partial_budget($anno_in_corso); //Recupero i totali per poter aggiornate la tabella
	$p_f2_bdgt_coeff_form_par=get_parametro("p_f2_bdgt_coeff_form_par");
	
	$coeff_form_par=$p_f2_bdgt_coeff_form_par->val_float;
	
	$result_modifica=$DB->get_records_sql("SELECT p.id, p.totb, p.dirigenti, p.personale, p.criterioa
											FROM {f2_partialbdgt} p
											WHERE
												p.anno = ".$anno_in_corso);
	
	$esito = 1;
	
	if($result_modifica){
	
		foreach($result_modifica as $modifica){
			$criterioa =  $modifica->criterioa;
			$criteriob = $coeff_form_par * $modifica->totb / $totali->tot_totb;
			$criterioc = $coeff_form_par * $modifica->dirigenti / $totali->tot_dirigenti;
			$criteriod = $coeff_form_par * $modifica->personale / $totali->tot_personale;
			
			$coefficiente = $criterioa + $criteriob + $criterioc + $criteriod;		
			
			$parametro= new stdClass();
			$parametro->id = $modifica->id;
			$parametro->criteriob = $criteriob;
			$parametro->criterioc = $criterioc;
			$parametro->criteriod =$criteriod;
			$parametro->coefficiente =$coefficiente;
			$parametro->modificato = 0;
			
			if(!$DB->update_record('f2_partialbdgt', $parametro)){
				$esito = 0;
			}
				
		}	
	}

	if(!$esito)
		return false;
	else
		return true;
}

/**
 *  @param int $anno_in_corso
 * Questa funzione controlla se è stato modificato qualche record nella tabella dei budget parziali 
 * prima di calcolare il budget totale si deve controllare se è stato modificato qualche parametro del budget parziale
 * @return Return 1 se almeno un record è stato modificato
 */
function budget_parziale_modificato($anno_in_corso){
	global $DB;

	$modifica = $DB->record_exists_sql("SELECT p.id
										FROM 
											{f2_partialbdgt} p
										WHERE 
											p.modificato = 1 AND
											p.anno = ".$anno_in_corso
										);
	if($modifica)
		return 1;
	else 
		return 0;
}

/**
 * @param int $anno anno formativo corrente
 * @param $dati = array(stdClass( [direzione] => [posti_aula] => [bonus] => [obiettivo] => [individuale] => [lingue] => [e_learning] => [aula] => [giorni_crediti_aula] => [totale] => ))
 * @return $esito int 1/0
 */
function approva_budget($dati,$anno){
	global $DB, $USER;
	$user = $USER->username;
	
	try {
		$transaction = $DB->start_delegated_transaction();//Inizio transaction
		
		$esito = 1;
		
		$result_delete = $DB->delete_records('f2_org_budget',array ('anno'=>$anno));
		
		if(!$result_delete)
			$esito = 0;
/*
tipo1 = aula
tipo2 = lingue
tipo3 = e_learning
tipo4 = individuale
tipo5 = obiettivo
*/

			foreach($dati as $dato){		
				//INSERT TIPO 1
					$record_tipo_1 				= new stdClass();
					$record_tipo_1->id 		= "";
					$record_tipo_1->anno 		= $anno;
					$record_tipo_1->orgfk 		= $dato->direzione;
					$record_tipo_1->tipo 		= 1;
					$record_tipo_1->money_bdgt 	= $dato->aula;
					$record_tipo_1->days_bdgt 	= $dato->giorni_crediti_aula;
					$record_tipo_1->lstupd 		= date('Y-n-j H:i:s');
					$record_tipo_1->usrname 	= $user;
					
				//INSERT TIPO 2
					$record_tipo_2 				= new stdClass();
					$record_tipo_2->id 		= "";
					$record_tipo_2->anno 		= $anno;
					$record_tipo_2->orgfk 		= $dato->direzione;
					$record_tipo_2->tipo 		= 2;
					$record_tipo_2->money_bdgt 	= $dato->lingue;
					$record_tipo_2->days_bdgt 	= 0;
					$record_tipo_2->lstupd 		= date('Y-n-j H:i:s');
					$record_tipo_2->usrname 	= $user;
					
				//INSERT TIPO 3
					$record_tipo_3 				= new stdClass();
					$record_tipo_3->id 		= "";
					$record_tipo_3->anno 		= $anno;
					$record_tipo_3->orgfk 		= $dato->direzione;
					$record_tipo_3->tipo 		= 3;
					$record_tipo_3->money_bdgt 	= $dato->e_learning;
					$record_tipo_3->days_bdgt 	= 0;
					$record_tipo_3->lstupd 		= date('Y-n-j H:i:s');
					$record_tipo_3->usrname 	= $user;
					
				//INSERT TIPO 4
					$record_tipo_4 				= new stdClass();
					$record_tipo_4->id 		= "";
					$record_tipo_4->anno 		= $anno;
					$record_tipo_4->orgfk 		= $dato->direzione;
					$record_tipo_4->tipo 		= 4;
					$record_tipo_4->money_bdgt 	= $dato->individuale;
					$record_tipo_4->days_bdgt 	= 0;
					$record_tipo_4->lstupd 		= date('Y-n-j H:i:s');
					$record_tipo_4->usrname 	= $user;
					
				//INSERT TIPO 5
					$record_tipo_5 				= new stdClass();
					$record_tipo_5->id 		= "";
					$record_tipo_5->anno 		= $anno;
					$record_tipo_5->orgfk 		= $dato->direzione;
					$record_tipo_5->tipo 		= 5;
					$record_tipo_5->money_bdgt 	= $dato->obiettivo;
					$record_tipo_5->days_bdgt 	= 0;
					$record_tipo_5->lstupd 		= date('Y-n-j H:i:s');
					$record_tipo_5->usrname 	= $user;
					
				$result_insert = $DB->insert_record('f2_org_budget', $record_tipo_1);
				if(!$result_insert)
					$esito = 0;
				$result_insert = $DB->insert_record('f2_org_budget', $record_tipo_2);
				if(!$result_insert)
					$esito = 0;
				$result_insert = $DB->insert_record('f2_org_budget', $record_tipo_3);
				if(!$result_insert)
					$esito = 0;
				$result_insert = $DB->insert_record('f2_org_budget', $record_tipo_4);
				if(!$result_insert)
					$esito = 0;
				$result_insert = $DB->insert_record('f2_org_budget', $record_tipo_5);
				if(!$result_insert)
					$esito = 0;
						
			}

		// Assuming the both inserts work, we get to the following line.
		$transaction->allow_commit();
	} catch(Exception $e) {
		$transaction->rollback($e);
	}
	
	return $esito;
}

/**
 * @param int $anno anno formativo corrente
 * Restituisce l'id delle direzioni presenti nella tabella f2_partialbdgt per l'anno passato come paremetro
 */
function get_direzioni_partial_budget($anno)
{
	global $DB;

	$sql_query="SELECT 	
					p.orgfk
				FROM 	
					{f2_partialbdgt} p
				WHERE
					anno = ".$anno;

	$results = $DB->get_records_sql($sql_query,NULL,$data->page*$data->perpage,$data->perpage);

	return $results;
}

/**
 * @param int $anno anno formativo corrente
 * @param array(stdClass_direzione_1(id,fullname),stdClass_direzione_n(id,fullname)) direzioni totali
 * 
 * Controllo se è stata inserita o eliminata una direzione.
 * Viene fatto un controllo/confronto sulle direzioni esistenti e sulle direzioni salvate nella tabella budget parziali(f2_partialbdgt)
 * Se è stata aggiunta/eliminata una nuova direzione allora deve essere aggiunta/eliminata anche nella tabella f2_partialbdgt
 * 
 * Viene controllato anche se è un nuovo anno formativo, 
 * in questo caso vengono salvate tutte le direzioni nella tabella f2_partialbdgt per l'anno formativo in corso
 * e vengono inizializzati di default i campi "criterioa" = $coefficiente_formativo / $numero_strutture
 * e il campo "modificato" con 1
 */
function gestione_direzioni_budget($direzioni,$anno){//GIUNTA, CONSIGLIO o ENTI ESTERNI
	global $DB, $USER;
	
	$user = $USER->username;
	
	$coeff_form_par = get_parametro('p_f2_bdgt_coeff_form_par');
	$num_strutture_par = get_parametro('p_f2_bdgt_num_strutture_par');
	$criterioa = $coeff_form_par->val_float / $num_strutture_par->val_float;
	
	$direzioni_parziali = get_direzioni_partial_budget($anno);

	try {
		$transaction = $DB->start_delegated_transaction();//Inizio transaction
		
		//INIZIO:INSERISCO LE NUOVE DIREZIONI SE CI SONO
			foreach($direzioni as $direzione){
				if (!array_key_exists($direzione->id, $direzioni_parziali)) {	
					
					$id_new_direzione =  new stdClass();
					$id_new_direzione->orgfk =  $direzione->id;
		
					$record_direzione 				= new stdClass();
					$record_direzione->id 			= "";
					$record_direzione->anno 		= $anno;
					$record_direzione->orgfk 		= $direzione->id;
					$record_direzione->criterioa 	= $criterioa;		
					$record_direzione->coefficiente = $criterioa;  //INIZIALMENTE IL coefficiente è UGUALE AL criterioa PERCHè coefficiente = criterioa +criteriob + criterioc + criteriod
					$record_direzione->lstupd 		= date('Y-n-j H:i:s');
					$record_direzione->usrname 		= $user;
					$record_direzione->modificato 		= 1;
		
					$insert_direzione = $DB->insert_record('f2_partialbdgt', $record_direzione); //INSERISCO NELLA TABELLA f2_partialbdgt LE NUOVE DIREZIONI
					
					$direzioni_parziali[$direzione->id] = $id_new_direzione;
				}
			}
		//FINE:INSERISCO LE NUOVE DIREZIONI SE CI SONO
		
		//INIZIO:ELIMINO LE DIREZIONI
			foreach($direzioni_parziali as $direzione_parziale){
				
				//print_r($direzioni);
				if (!array_key_exists($direzione_parziale->orgfk, $direzioni)) {
			
					$DB->delete_records('f2_partialbdgt', array('orgfk' => $direzione_parziale->orgfk));
				}
			}
		//FINE:INSERISCO LE DIREZIONI
	
	// Assuming the both inserts work, we get to the following line.
	$transaction->allow_commit();
	} catch(Exception $e) {
		$transaction->rollback($e);
	}
	
	//echo sono state aggiornate delle direzioni
}

/**
 * @param int $anno anno formativo corrente
 * @param float $coefficiente_formativo
 * @param float $numero_strutture
 * @return int 1/0
 * Se nella pagina inserisci_budget.php viene modificato il valore "Coefficiente Formativo" o "Numero di Strutture"
 * nella tabella {f2_partialbdgt} devono essere modificare tutti i record dell'anno con il campo "criterioa" = $coefficiente_formativo / $numero_strutture
 * e il campo "modificato" con 1
 */
function update_criterioa_budget($coefficiente_formativo,$numero_strutture,$anno){
	global $DB;
	
	$criterioa = $coefficiente_formativo / $numero_strutture;

	$result_modifica=$DB->get_records_sql("SELECT p.id
											FROM {f2_partialbdgt} p
											WHERE
												p.anno = ".$anno);
	$esito = 1;
	
	if($result_modifica){
	
		foreach($result_modifica as $modifica){
			$parametro= new stdClass();
			$parametro->id = $modifica->id;
			$parametro->criterioa = $criterioa;
			$parametro->modificato = 1;
			
			if(!$DB->update_record('f2_partialbdgt', $parametro)){
				$esito = 0;
			}
		}
	}
	return $esito;
}

/**
 * @param stdClass $dataobject nuovi dati sulle funzionalit� da aggiornare
 */
function update_fornitore ($data){
	global $DB;
	
	$esito = 0;
	if($DB->record_exists('f2_fornitori', array('id' => $data->id))){
			if($DB->update_record('f2_fornitori', $data)){
				$esito = 1;
			}
	}
	return $esito;
}

function get_anni_formativi_sessioni_per_select_form()
{
	global $DB;
	$anni_sql = "select distinct s.anno from {f2_sessioni} s order by s.anno desc";
	$anni_rs = $DB->get_records_sql($anni_sql);
	if (count($anni_rs) == 0) 
	{
		$anni_rs = array();
		$anno = get_anno_formativo_corrente();
		$anni_rs[$anno] = $anno;
	}
	$anni_rs = array_keys($anni_rs);
	$anni_rs = array_combine($anni_rs,$anni_rs);
	return $anni_rs;
}

function get_numero_sessioni_per_select_form_by_anno($anno)
{
	if (!isset($anno) or empty($anno)) $anno = get_anno_formativo_corrente();
	global $DB;
	$num_sess_sql = "select distinct s.numero from {f2_sessioni} s where s.anno = ".$anno." order by s.numero asc";
	$num_sess_rs = $DB->get_records_sql($num_sess_sql);
	if (count($num_sess_rs) == 0)
	{
		$num_sess_rs = array('1'=>'1');
	}
	$num_sess_rs = array_keys($num_sess_rs);
	$num_sess_rs = array_combine($num_sess_rs,$num_sess_rs);
	return $num_sess_rs;
}
function get_numero_mesi_per_select_form_by_anno($anno)
{
	if (!isset($anno) or empty($anno)) $anno = get_anno_formativo_corrente();
	global $DB;
	$mesi_sess_sql = "select min(data_inizio) as min_data,max(data_fine) as max_data from mdl_f2_sessioni WHERE anno = ".$anno." group by anno";
	$mesi_sess_rs = $DB->get_record_sql($mesi_sess_sql);
	if (count($mesi_sess_rs) == 0)
	{
		$mesi_sess_rs = array('0'=>'0');
		return $mesi_sess_rs;
	}
	else 
	{
// 		print_r(($mesi_sess_rs));
		/*
		$min_month = intval(date('m',intval($mesi_sess_rs->min_data)));
		
		$max_month = intval(date('m',intval($mesi_sess_rs->max_data)));
		$mesi_sess_rs = array();
		if ($min_month > $max_month)
		{
// 			$temp = $max_month;
// 			$max_month = $min_month;
// 			$min_month = $temp;
			$max_month = $max_month + 12;
		}
// 		print_r($min_month.'-'.$max_month);exit;
		while ($min_month <= $max_month)
		{
			$mesi_sess_rs[$anno.($min_month % 12)] = $min_month % 12;
			$min_month++;
		}
		*/
		
		$min_data = $mesi_sess_rs->min_data;
		$max_data = $mesi_sess_rs->max_data;
		$mesi_sess_rs = array();
		while ($min_data < $max_data)
		{
			$str = date('Y-m',$min_data);
			if (!in_array($str,$mesi_sess_rs)) $mesi_sess_rs[$str] = $str;
			$min_data = $min_data + 86400;
		}
		//includo il mese max (se la sessione finisce il 1 giorno viene esclusa dal ciclo)
		$str = date('Y-m',$max_data);
		if (!in_array($str,$mesi_sess_rs)) $mesi_sess_rs[$str] = $str;
		return $mesi_sess_rs; 
	}
}

// function get_dati_tabella_auth_mail($anno=0,$num_sessione,$mese,$mail_sent)
function get_dati_tabella_auth_mail($data = array(),$nolimit=0)
{
	global $DB;
	$tipo_nofitica = 1;
	
	if (!isset($data['anno_sel']) or empty($data['anno_sel'])) $anno = get_anno_formativo_corrente(); else $anno = $data['anno_sel'];
	
	if (!isset($data['num_sess_sel']) or empty($data['num_sess_sel'])) $num_sessione = 1; else $num_sessione = $data['num_sess_sel'];
	
	if (!isset($data['mese_sess_sel']) or empty($data['mese_sess_sel'])) $mese = $anno.'-01'; else $mese = $data['mese_sess_sel'];
	
	if (!isset($data['mail_inviate_sel']) or empty($data['mail_inviate_sel'])) $mail_sent = 'tutto'; else $mail_sent = $data['mail_inviate_sel'];
	
	if (!isset($data['column']) or empty($data['column'])) $column = 'data_inizio'; else $column = $data['column'];
	
	if (!isset($data['sort']) or empty($data['sort'])) $sort = 'ASC'; else $sort = $data['sort'];
	
	if (!isset($data['codice_corso']) or empty($data['codice_corso'])) $codice = 0; else $codice = $data['codice_corso'];

	if (!isset($data['page']) or empty($data['page'])) $page = 0; else $page = $data['page'];
	
	if (!isset($data['perpage']) or empty($data['perpage'])) $perpage = 10; else $perpage = $data['perpage'];

	
	$codice_str = "";
	if ($codice !== 0)
	{
		$codice_str = " and lower(c.shortname) like lower('%".$codice."%') ";
	}
	
	$mail_inviate_str = " ";
	if ($mail_sent == 'si')
	{
		$sql_tipo_notifica_corso_edizione = "select nc.id_notif_templates from {f2_notif_corso} nc ";
		$sql_tipo_notifica_corso_edizione .= " where nc.id_corso = c.id and nc.id_tipo_notif = ".$tipo_nofitica." and (nc.id_edizione is null or nc.id_edizione = fs.id) ";
		$mail_inviate_str = " 
				and (
					exists (select 1 from mdl_f2_notif_template_mailqueue maillog 
							where maillog.sessionid = fs.id  and maillog.mailtemplate in (".$sql_tipo_notifica_corso_edizione.") 
							 and exists (select 1 from mdl_facetoface_signups fn where fn.sessionid = fs.id and fn.f2_send_notif = ".$tipo_nofitica."  
							 and exists (select 1 from mdl_facetoface_signups_status fns where fns.signupid = fn.id and fns.statuscode = ".MDL_F2F_STATUS_BOOKED.")))		 
					or
					exists (select 1 from mdl_f2_notif_template_log maillog 
							where maillog.sessionid = fs.id  and maillog.mailtemplate in (".$sql_tipo_notifica_corso_edizione.")  
							 and exists (select 1 from mdl_facetoface_signups fn where fn.sessionid = fs.id and fn.f2_send_notif = ".$tipo_nofitica."  
							 and exists (select 1 from mdl_facetoface_signups_status fns where fns.signupid = fn.id and fns.statuscode = ".MDL_F2F_STATUS_BOOKED.")))
					) ";
	}
	else if ($mail_sent == 'no')
	{
		$sql_tipo_notifica_corso_edizione = "select nc.id_notif_templates from {f2_notif_corso} nc ";
		$sql_tipo_notifica_corso_edizione .= " where nc.id_corso = c.id and nc.id_tipo_notif = ".$tipo_nofitica." and (nc.id_edizione is null or nc.id_edizione = fs.id) ";
		$mail_inviate_str = " 
				and (
					not exists (select 1 from mdl_f2_notif_template_mailqueue maillog 
							where maillog.sessionid = fs.id  and maillog.mailtemplate in (".$sql_tipo_notifica_corso_edizione.") 
							 and exists (select 1 from mdl_facetoface_signups fn where fn.sessionid = fs.id and fn.f2_send_notif = ".$tipo_nofitica."  
							 and exists (select 1 from mdl_facetoface_signups_status fns where fns.signupid = fn.id and fns.statuscode = ".MDL_F2F_STATUS_BOOKED.")))		 
					and
					not exists (select 1 from mdl_f2_notif_template_log maillog 
							where maillog.sessionid = fs.id  and maillog.mailtemplate in (".$sql_tipo_notifica_corso_edizione.")  
							 and exists (select 1 from mdl_facetoface_signups fn where fn.sessionid = fs.id and fn.f2_send_notif = ".$tipo_nofitica."  
							 and exists (select 1 from mdl_facetoface_signups_status fns where fns.signupid = fn.id and fns.statuscode = ".MDL_F2F_STATUS_BOOKED.")))
					) ";
	}
	
	//calcolo ts inizio e ts fine del mese passato come param
	$mese_arr = explode("-",$mese);
	$mese_anno = $mese_arr[0];
	$mese_num = $mese_arr[1];
	$ts_inizio = mktime(0,0,0,$mese_num,1,$mese_anno);
	if ($mese_num < 12)
	{
		$ts_fine = mktime(0,0,0,($mese_num+1),1,$mese_anno);
	}
	else 
	{
		$ts_fine = mktime(0,0,0,1,1,$mese_anno+1);
	}
// 	print_r(date('Y-m-d',$ts_inizio));
// 	print_r(date('Y-m-d',$ts_fine));
	
	$select_str = "select distinct concat_ws(' - ',c.fullname,csm.sedeid,concat_ws('','SESS',s.numero),
			(select ifnull(fsd1.data,'') from {facetoface_session_field} fsf1, {facetoface_session_data} fsd1
				where fsf1.shortname = 'editionum' and fsd1.sessionid = fs.id and fsf1.id = fsd1.fieldid)) as titolo ";
	$select_str .= " ,c.shortname as codice_corso,'".$anno."' as anno, '".$num_sessione."' as num_sessione ";
	$select_str .= " ,	(select ifnull(fsd2.data,'') from {facetoface_session_field} fsf2, {facetoface_session_data} fsd2 
			where fsf2.shortname = 'sirp' and fsd2.sessionid = fs.id and fsf2.id = fsd2.fieldid) as sirp ";
	$select_str .= " ,	(select ifnull(fsd3.data,'') from {facetoface_session_field} fsf3, {facetoface_session_data} fsd3
			where fsf3.shortname = 'sirpdata' and fsd3.sessionid = fs.id and fsf3.id = fsd3.fieldid) as sirpdata ";
	$select_str .= " ,fdates.timestart as data_inizio, fs.id as edizione_id ";
	
	$from_str = " from {f2_sessioni} s, {course} c, {facetoface} f , {facetoface_sessions} fs ";
	$from_str .= " ,{f2_corsi_sedi_map} csm, {facetoface_sessions_dates} fdates ";
// 	$from_str .= " ,{facetoface_session_field} fsfield, {facetoface_sessions_dates} fsdates, {facetoface_session_data} fsdata ";
	$from_str .= " , {facetoface_sessions_dates} fsdates ";
	
	$where = " where s.anno = ".$anno." and s.numero = ".$num_sessione." ";
	$where .= $codice_str;
	$where .= $mail_inviate_str;
	$where .= " and fdates.timestart >= ".$ts_inizio." and fdates.timestart < ".$ts_fine." ";
	$where .= " and csm.courseid = c.id";
	$where .= " and f.f2session = s.id and c.id = f.course and fsdates.sessionid = fs.id and fs.facetoface = f.id and fs.id = fdates.sessionid ";
	$order_by = " order by ".$column." ".$sort;
// 	print_r($where);
	$sql = $select_str.$from_str.$where;
	
	$sql_tot = "SELECT @rownum:=@rownum+1 AS rownum, temp.* from (".$sql.") temp, (SELECT @rownum:=0) r ".$order_by;
	if ($nolimit == 0) 
	{
		$rs = $DB->get_records_sql($sql_tot,NULL,$page*$perpage,$perpage);
	}
	else // nolimit == 1 per export excel
	{
		$rs = $DB->get_records_sql($sql_tot);
	}
	$return = new stdClass();
	$return->dati = $rs;
	$return->count = $DB->count_records_sql("SELECT count(*) FROM (".$sql_tot.") as tmp");
	return $return;
}

// $tipo_nofitica = 1 autorizzazione, $tipo_nofitica = 2 cancellazione
function get_all_email_template_id_str($edizione_id,$tipo_nofitica = 1)
{
	global $DB;
		
	$corso = get_corso_by_facetofacesession($edizione_id);
	$corso_id = $corso->id;
	/*
	 $notifica_corso = get_template_course($corso_id,null,$tipo_nofitica_auth);
	$notifica_corso_edizione = get_template_course($corso_id,$edizione_id,$tipo_nofitica_auth);
	*/
	$sql_tipo_notifica_corso_edizione = "select nc.id_notif_templates from {f2_notif_corso} nc ";
	$sql_tipo_notifica_corso_edizione .= " where nc.id_corso = ".$corso_id." and nc.id_tipo_notif = ".$tipo_nofitica." and (nc.id_edizione is null or nc.id_edizione = ".$edizione_id.") ";
	$notifiche = $DB->get_records_sql($sql_tipo_notifica_corso_edizione);
	$notifiche_id_str = '-1';
	foreach ($notifiche as $n)
	{
		$notifiche_id_str .= ",".$n->id_notif_templates;
	}
	return $notifiche_id_str;
}

function get_maxdata_auth_mail_inviate($edizione_id)
{
	$return = '';
	if (is_null($edizione_id) or empty($edizione_id) or !isset($edizione_id)) return '';
	else
	{
		global $DB;
		$notifiche_id_str = get_all_email_template_id_str($edizione_id,1);
		$sql = "select ifnull(max(maillog.time),'') as maxdata_invio from {f2_notif_template_mailqueue} maillog ";
		$sql .= " where maillog.sessionid = ".$edizione_id." and maillog.mailtemplate in (".$notifiche_id_str.") ";
		$sql .= " and exists (select 1 from {facetoface_signups} fn where fn.sessionid = ".$edizione_id." and fn.f2_send_notif = 1 ";
		$sql .= " and exists (select 1 from {facetoface_signups_status} fns where fns.signupid = fn.id and fns.statuscode = ".MDL_F2F_STATUS_BOOKED.")) ";
		$return = $DB->get_record_sql($sql);
		if (count($return) > 0 && $return->maxdata_invio)
		{
			if (is_null($return->maxdata_invio) or empty($return->maxdata_invio) or !isset($return->maxdata_invio)) return '';
			else return $return->maxdata_invio;
		}
		else
		{
			$sql = "select ifnull(max(maillog.time),'') as maxdata_invio from {f2_notif_template_log} maillog ";
			$sql .= " where maillog.sessionid = ".$edizione_id." and maillog.mailtemplate in (".$notifiche_id_str.") ";
			$sql .= " and exists (select 1 from {facetoface_signups} fn where fn.sessionid = ".$edizione_id." and fn.f2_send_notif = 1 ";
			$sql .= " and exists (select 1 from {facetoface_signups_status} fns where fns.signupid = fn.id and fns.statuscode = ".MDL_F2F_STATUS_BOOKED.")) ";
			$return = $DB->get_record_sql($sql);
			if (is_null($return->maxdata_invio) or empty($return->maxdata_invio) or !isset($return->maxdata_invio)) return '';
			else return $return->maxdata_invio;
		}
	}
}

function get_dettagli_email_inviate_all_info($edizione_id)
{
	$return = '';
	if (is_null($edizione_id) or empty($edizione_id) or !isset($edizione_id)) return '';
	else
	{
		global $DB;
// 		$tipo_nofitica_auth = 1;
		
// 		$corso = get_corso_by_facetofacesession($edizione_id);
// 		$corso_id = $corso->id;
// 		/*
// 		$notifica_corso = get_template_course($corso_id,null,$tipo_nofitica_auth);
// 		$notifica_corso_edizione = get_template_course($corso_id,$edizione_id,$tipo_nofitica_auth);
// 		*/
// 		$sql_tipo_notifica_corso_edizione = "select nc.id_notif_templates from {f2_notif_corso} nc ";
// 		$sql_tipo_notifica_corso_edizione .= " where nc.id_corso = ".$corso_id." and nc.id_tipo_notif = ".$tipo_nofitica_auth." and (nc.id_edizione is null or nc.id_edizione = ".$edizione_id.") ";
// 		$notifiche = $DB->get_records_sql($sql_tipo_notifica_corso_edizione);
		$notifiche_id_str = get_all_email_template_id_str($edizione_id,1);
		
		$sql = "select maillog.*,concat_ws(', ',user.lastname,user.firstname) as utente from {f2_notif_template_mailqueue} maillog, {user} user ";
		$sql .= " where maillog.sessionid = ".$edizione_id." and maillog.mailtemplate in (".$notifiche_id_str.") and user.id = maillog.useridto ";
		$return = $DB->get_records_sql($sql);
// 		echo '<br/>notifiche_id_str: '.$notifiche_id_str.'<br/>';
// 		echo '<br/>sql_notif_queue: '.$sql.'<br/>';
		if (count($return) > 0) return $return;
		else 
		{
			$sql = "select maillog.*,concat_ws(', ',user.lastname,user.firstname) as utente from {f2_notif_template_log} maillog, {user} user ";
			$sql .= " where maillog.sessionid = ".$edizione_id." and maillog.mailtemplate in (".$notifiche_id_str.") and user.id = maillog.useridto ";
			$return = $DB->get_records_sql($sql);
// 			echo '<br/>sql_notif_sent: '.$sql.'<br/>';
			return $return;
		}
	}
}

function get_dettagli_email_inviate($edizione_id)
{
	$return = '';
	if (is_null($edizione_id) or empty($edizione_id) or !isset($edizione_id)) return '';
	else
	{
		global $DB;
		$notifiche_id_str = get_all_email_template_id_str($edizione_id,1);

// 		$sql = "select distinct concat_ws(', ',user.lastname,user.firstname) as utente, from_unixtime(time,'%d/%m/%Y% %H:%i:%s') as sendtime from {f2_notif_template_mailqueue} maillog, {user} user ";
		$sql = "select user.id as userid, concat_ws(', ',user.lastname,user.firstname) as utente, from_unixtime(time,'%d/%m/%Y% %H:%i:%s') as sendtime,mailto from {f2_notif_template_mailqueue} maillog, {user} user ";
		$sql .= " where maillog.sessionid = ".$edizione_id." and maillog.mailtemplate in (".$notifiche_id_str.") and user.id = maillog.useridto ";
		$sql .= " and time = (select max(t1.time) from {f2_notif_template_mailqueue} t1 
					where t1.sessionid = maillog.sessionid and t1.mailtemplate = maillog.mailtemplate and t1.useridto = maillog.useridto)
					group by user.id,time
					order by utente asc" ;
		
		$return = $DB->get_records_sql($sql);
		if (count($return) > 0) return $return;
		else
		{
// 			$sql = "select distinct concat_ws(', ',user.lastname,user.firstname) as utente, from_unixtime(time,'%d/%m/%Y %H:%i:%s') as sendtime from {f2_notif_template_log} maillog, {user} user ";
			$sql = "select user.id as userid, concat_ws(', ',user.lastname,user.firstname) as utente, from_unixtime(time,'%d/%m/%Y %H:%i:%s') as sendtime,mailto from {f2_notif_template_log} maillog, {user} user ";
			$sql .= " where maillog.sessionid = ".$edizione_id." and maillog.mailtemplate in (".$notifiche_id_str.") and user.id = maillog.useridto ";
			$sql .= " and time = (select max(t1.time) from {f2_notif_template_log} t1 
					where t1.sessionid = maillog.sessionid and t1.mailtemplate = maillog.mailtemplate and t1.useridto = maillog.useridto)
					group by user.id,time
					order by utente asc" ;
			
			$return = $DB->get_records_sql($sql);
			return $return;
		}
	}
}

function get_corso_by_facetofacesession($edizione_id)
{
	$return = '';
	if (is_null($edizione_id) or empty($edizione_id) or !isset($edizione_id)) return '';
	else
	{
		global $DB;
		$sql = "select distinct c.*,f.name as facetofacename from {course} c, {facetoface} f, {facetoface_sessions} fs";
		$sql .= " where fs.id = ".$edizione_id." and f.id = fs.facetoface and f.course = c.id ";
		$return = $DB->get_record_sql($sql);
		return $return;
	}
}

function get_anno_by_edizione_id($edizione_id)
{
	
	global $DB;
	$anno = get_anno_formativo_corrente();
	if (is_null($edizione_id) or empty($edizione_id) or !isset($edizione_id) or !is_int($edizione_id)) return $anno;
	$sql = "SELECT distinct ifnull(max(fda.data),'-1') as anno FROM {facetoface_session_data} fda , {facetoface_session_field} ff ";
	$sql .= " where ff.shortname = 'anno' and fda.sessionid = ".$edizione_id." and  fda.fieldid = ff.id ";
	$return = $DB->get_record_sql($sql);
	if ($return->anno == '-1') return $anno;
	else return $return->anno;
}

function get_numero_sessione_by_anno($anno)
{
	if (!isset($anno) or empty($anno)) $anno = get_anno_formativo_corrente();
	global $DB;
	$sql = "SELECT distinct ifnull(numero,'-1') as num_sess FROM {f2_sessioni} where anno = ".$anno." ";
	$return = $DB->get_record_sql($sql);
	return $return->num_sess;
}

function get_ts_inizio_edizione($edizione_id)
{
	global $DB;
	$sql = "SELECT distinct ifnull(max(fds.timestart),'-1') as data_inizio FROM {facetoface_sessions_dates} fds WHERE fds.sessionid = ".$edizione_id." ";
	$return = $DB->get_record_sql($sql);
	return $return->data_inizio;
}

function get_data_inizio_edizione($anno,$edizione_id)
{
	$ts_inizio = get_ts_inizio_edizione($edizione_id);
	if ($ts_inizio !== '-1')
	{
		$data_inizio = date('Y-m',$ts_inizio);
	}
	else //prende la prima della sessione
	{
		$num_mesi_rs = get_numero_mesi_per_select_form_by_anno($anno,$edizione_id);
		$data_inizio = array_shift(array_values($num_mesi_rs));
	}
	return $data_inizio;
}

function update_sirp($data)
{
	global $DB;
// 	print_r($data);exit;
	$edizione_id = $data->edizione_id;

	if (!is_null($data->sirp) and !empty($data->sirp) and isset($data->sirp) and $data->sirp !== '')
	{
		$sirpfdid_sql = "SELECT ifnull(max(fda.id),'-1') as id FROM {facetoface_session_data} fda , {facetoface_session_field} ff ";
		$sirpfdid_sql .= " where ff.shortname = 'sirp' and fda.sessionid = ".$edizione_id." and fda.fieldid = ff.id ";
		$val = $DB->get_record_sql($sirpfdid_sql);
		if ($val->id == '-1') // inserire sirp
		{
	// 		return false;
			$fieldid = $DB->get_record_sql("select ff.id from {facetoface_session_field} ff where ff.shortname = 'sirp'");
	// 		print_r($fieldid->id);exit;
			$newsirp = new stdClass;
			$newsirp->data = $data->sirp;
			$newsirp->sessionid = $edizione_id;
			$newsirp->fieldid = $fieldid->id;
// 			print_r($newsirp);exit;
			$DB->insert_record('facetoface_session_data', $newsirp);
		}
		else
		{
			$newsirp = new stdClass;
			$newsirp->id = $val->id;
			$newsirp->data = $data->sirp;
			$DB->update_record('facetoface_session_data', $newsirp);
		}
	}
	
	if (!is_null($data->sirpdata) and !empty($data->sirpdata) and isset($data->sirpdata) and $data->sirpdata !== '')
	{
		$sirpfdid_sql = "SELECT ifnull(max(fda.id),'-1') as id FROM {facetoface_session_data} fda , {facetoface_session_field} ff ";
		$sirpfdid_sql .= " where ff.shortname = 'sirpdata' and fda.sessionid = ".$edizione_id." and fda.fieldid = ff.id ";
		$val = $DB->get_record_sql($sirpfdid_sql);
		if ($val->id == '-1') //inserire sirpdata
		{
	// 		return false;
			$fieldid = $DB->get_record_sql("select ff.id from {facetoface_session_field} ff where ff.shortname = 'sirpdata'");
			// 		print_r($fieldid->id);exit;
			$newsirpdata = new stdClass;
			$newsirpdata->data = $data->sirpdata;
			$newsirpdata->sessionid = $data->edizione_id;
			$newsirpdata->fieldid = $fieldid->id;
			$DB->insert_record('facetoface_session_data', $newsirpdata);
		}
		else  // aggiornare
		{
			$newsirpdata = new stdClass;
			$newsirpdata->id = $val->id;
			$newsirpdata->data = $data->sirpdata;
			$DB->update_record('facetoface_session_data', $newsirpdata);
		}
	}
// 	return true;
}

function check_all_condition_for_email_queue($edizione_id,$tipo_notifica_id=1)
{
	$dummy = exists_dummy_email($edizione_id,$tipo_notifica_id);
	if ($dummy == true) 
	{
// 		print_r('dummy error');
// 		exit;
		return false;
	}
	$template = exists_template_notifica($edizione_id,$tipo_notifica_id);
	$sirp = exists_sirp_dati($edizione_id);
	if ($template == true and $sirp == true) return true;
	else 
	{
// 		if ($template == false) print_r('template error');
// 		if ($sirp == false) print_r('sirp error');
// 		exit;
		return false;
	}
}
// controlli per invio mail:
// - 1: mail dummy
// - 2: template notifica per edizione o corso esistente
// - 3: sirp e sirpdata
function exists_dummy_email($edizione_id,$tipo_notifica_id=1)
{
// 	$mail_dummy = get_dummy_email($list_user);
	if (is_null($edizione_id) or empty($edizione_id) or !isset($edizione_id)) return false;
	if ($tipo_notifica_id == 1)
	{
		$list_user = get_user_session_by_status ($edizione_id,MDL_F2F_STATUS_BOOKED);
	}
	else if ($tipo_notifica_id == 2)
	{
		$list_user = get_user_session_by_status ($edizione_id,MDL_F2F_STATUS_USER_CANCELLED);
	}
	else // altri stati non sono previsti invio email 
	{
		return false;
	}
	if (is_null($list_user) or empty($list_user) or !isset($list_user)) return false;
	$mail_dummy = get_dummy_email($list_user);
	if (is_null($mail_dummy) or empty($mail_dummy) or !isset($mail_dummy)) return false;
	return true;
}
function exists_template_notifica($edizione_id,$tipo_notifica_id)
{
	if (is_null($edizione_id) or empty($edizione_id) or !isset($edizione_id)) return false;
	if (is_null($tipo_notifica_id) or empty($tipo_notifica_id) or !isset($tipo_notifica_id)) return false;
	$course = get_corso_by_facetofacesession($edizione_id);
	if (is_null($course) or empty($course) or !isset($course) or $course == '') return false;
	$courseid = $course->id;
	if (is_null($courseid) or empty($courseid) or !isset($courseid) or $courseid == '') return false;
	$id_notifica = get_template_corso_edizione($courseid,$edizione_id,$tipo_notifica_id);
	if (is_null($id_notifica) or empty($id_notifica) or !isset($id_notifica) or $id_notifica == '') return false;
	return true;
}
function exists_sirp_dati($edizione_id)
{
	if (is_null($edizione_id) or empty($edizione_id) or !isset($edizione_id)) return false;
	$field_sirp = new stdClass();
	$field_sirp->id = 2; //sirp
	$sirp=facetoface_get_customfield_value($field_sirp, $edizione_id, "session");
	if (is_null($sirp) or empty($sirp) or !isset($sirp)) return false;
	$field_sirp_data = new stdClass();
	$field_sirp_data->id = 3; //sirp data
	$sirp_data=facetoface_get_customfield_value($field_sirp_data, $edizione_id, "session");
	if (is_null($sirp_data) or empty($sirp_data) or !isset($sirp_data)) return false;
	return true;
}
function tbl_fornitori() {
	global $DB;
	$table = new html_table();
	$table->align = array ('center','center');
	$table->head = array('Seleziona','Denominazione');
	$table->id = 'id_tab_autosearch';
	//$table->attributes = array('class'=>'display');

	$spec = new StdClass();
	$spec->columns = array('id','denominazione'); //colonne
	$spec->column = 'preferiti'; //colonna di ordinamento
	$spec->sort = 'desc'; //direzione di ordinamento
	$spec->page = 0; //n. di pag.
	$spec->perpage = 0; //righe per pag.
	$spec->tipo_formazione = '1[01][01]'; //regexp value clause

	$rs_fornitori = get_fornitori_by_type($spec);

	$i = 1;
	foreach ($rs_fornitori->rs as $fornitore) {
		$id = $fornitore->id;
		$nome = $fornitore->denominazione;
		$table->data[] = array(
				'<input type=radio name="name_for" onclick="document.forms[0].id_dir_scuola.value=\''.$id.'\'
					document.getElementById(\'l_dir_scuola\').innerHTML=\''.$nome.'.\'
					" />',
				$nome
		);
	}
	return $table;
}

function tbl_fornitori_form_ind() {
	global $DB;
	$table = new html_table();
	$table->align = array ('center','center');
	$table->head = array('Seleziona','Denominazione');
	$table->id = 'id_tab_autosearch';
	$table->wrap=array(null,'nowrap');
	//$table->attributes = array('class'=>'display');

	$spec = new StdClass();
	$spec->columns = array('id',
                        'denominazione',
                        'partita_iva',
                        'codice_fiscale',
                        'codice_creditore',
                        'indirizzo',
                        'citta',
                        'provincia',
                        'cap',
                        'paese'); //colonne
	$spec->column = 'preferiti'; //colonna di ordinamento
	$spec->sort = 'desc'; //direzione di ordinamento
	$spec->page = 0; //n. di pag.
	$spec->perpage = 0; //righe per pag.
	$spec->tipo_formazione = '[01][01]1'; //regexp value clause

	$rs_fornitori = get_fornitori_by_type($spec);///////////////////////////////////////////////////////////
//////////	get_fornitore

	$i = 1;
	foreach ($rs_fornitori->rs as $fornitore) {
		$id = $fornitore->id;
		$nome = $fornitore->denominazione;
        //AK-LM: richiesta di Albertin del 31/7/2014
        //Quando si seleziona il fornitore si deve valorizzare ANCHE il campo via 
        //concatenando i campi Indirizzo+Citta+Provincia+CAP+Paese del fornitore
        $indirizzo = empty($fornitore->indirizzo) ? '' : $fornitore->indirizzo;
        $citta = empty($fornitore->citta) ? '' : ' '.$fornitore->citta;
        $provincia = empty($fornitore->provincia) ? '' : ' '.$fornitore->provincia;
        $cap = empty($fornitore->cap) ? '' : ' '.$fornitore->cap;
        $paese = empty($fornitore->paese) ? '' : ' '.$fornitore->paese;
        $via = addslashes_js(format_string(trim($indirizzo.$citta.$provincia.$cap.$paese)));
		$table->data[] = array(
				'<input type=radio name="name_for" onclick="
					document.getElementById(\'id_beneficiario_pagamento\').value=\''.$nome.'\'; 
                    document.getElementById(\'id_codice_fiscale\').value=\''.$fornitore->codice_fiscale.'\'; 
                    document.getElementById(\'id_partita_iva\').value=\''.$fornitore->partita_iva.'\'; 
                    document.getElementById(\'id_codice_creditore\').value=\''.$fornitore->codice_creditore.'\';
                    document.getElementById(\'id_via\').value=\''.$via.'\';
					" />',
				$nome
		);
	}
	return $table;
}
