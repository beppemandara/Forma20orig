<?php
/*
 * $Id: lib.php 1141 2013-05-20 15:11:59Z d.lallo $
 */

require_once($CFG->dirroot.'/f2_lib/core.php');
require_once($CFG->dirroot.'/f2_lib/management.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');

/**
 * Restituisce un array di stringhe sql per ricavare il numero totale  dei corsi fruiti dall'utente prelevati dallo storico CSI e i dati relativi
 * @param int $userid
 * return array of object
 */
// update mdl_f2_storico_corsi set matricola = 'admin' where matricola ='EEEEEEE';
// update mdl_user set idnumber='admin' where id = 2
// update mdl_f2_storico_corsi set matricola = 'EEEEEEE' where matricola ='admin';
// update mdl_user set idnumber=null where id = 2
function user_history_coursesSQL($userid=NULL, $perpage=10, $page=0,$sort='data_inizio',$dir='DESC',$c_exp_type=0)
{
// 	print_r('aaaaaa');
	if(is_null($userid))
		$userid=$USER->id;
	
	//$dir =  mysql_escape_string($dir);
	if ($dir !== "DESC") $dir = "ASC";

	//$sort =  mysql_escape_string($sort);
	if ($sort == 'data_inizio') $sort = 'start';
	else // default
	{
		$sort = 'start';
	}

	//corsi tutti ($c_exp_type==0): ""
	//corsi validati ($c_exp_type==1): and codpart <> '0' and cfv > 0
	//corsi da validare ($c_exp_type==2): and codpart = '0'
	//corsi attivi ($c_exp_type==3): codpart <> '0';

	$c_exp_typestr = "";
	if ($c_exp_type == 1) $c_exp_typestr = " and codpart <> '0' and cfv > 0 ";
	else if ($c_exp_type == 2) $c_exp_typestr = " and codpart = '0' ";
	else if ($c_exp_type == 3) $c_exp_typestr = " and codpart <> '0' ";
	
	$sqlstr = "select distinct
	s.data_inizio as start,
	s.scuola_ente as ente,
	s.codcorso as codice,
	s.titolo as nome,
	s.descrpart as descrpart,
	s.sf as sf,
	s.cf as cf,
	s.cfv as cfv,
	s.presenza as presenza ,
	s.va as va
	from {f2_storico_corsi} s where s.matricola = (select u.idnumber from {user} u where u.id = ".$userid.") ".$c_exp_typestr;
	$orderby = " order by ".$sort." ".$dir." ";

	$sqltot = "SELECT @rownum:=@rownum+1 AS rownum, temp.* from (".$sqlstr.") temp, (SELECT @rownum:=0) r ".$orderby;

	// print_r($sqltot);exit;

	// print_r($sqlstr.$orderby);

	// $rs = $DB->get_records_sql($sqlstr.$orderby,array,($pagination['perpage']*$pagination['page']),$pagination['perpage']);
	// $rs = $DB->get_records_sql($sqltot,array(),$page*$perpage,$perpage);

	// $return	= new stdClass;
	// $return->count	= $DB->count_records_sql("SELECT count(*) FROM (".$sqlstr.") as tmp");
	// $return->dati	= $rs;

	// return $return;
	return array('sqltot' => $sqltot,'sqlstr'=>$sqlstr);

}
/**
 * Restituisce i corsi fruiti dall'utente prelevati dallo storico CSI
 * @param int $userid
 * return array of object
 */
function user_history_courses($data)
{
	global $DB;
	$sqlarr = array();
	
	$sqlarr = user_history_coursesSQL($data->userid,$data->perpage,$data->page,$data->column,$data->sort,$data->c_exp_type);
// 	print_r($data);
	$rs = $DB->get_records_sql($sqlarr['sqltot'],array(),$data->page*$data->perpage,$data->perpage);

	$return	= new stdClass;
	$return->count	= $DB->count_records_sql("SELECT count(*) FROM (".$sqlarr['sqlstr'].") as tmp");
	$return->dati	= $rs;

    return $return;
}

/**
 * Restituisce il dettaglio di un'edizione di un corso
 * @param int $edizioneid
 * return stdClass
 */
function get_dettaglio_edizione($edizioneid) {
    global $DB, $CFG, $USER;
    
    if (!isSupervisoreConsiglio($USER->id)) {
        $join_aggiuntive = "";
        $where_aggiuntiva = "";
    } else {
        list($dominio_visibilita_id, $dominio_visibilita_name) = get_user_viewable_organisation($USER->id);
        $dominio_appartenenza = $dominio_visibilita_id;
        $join_aggiuntive = "join {$CFG->prefix}org_assignment oa ON (oa.userid = iscrizioni.userid)
                            join {$CFG->prefix}org org ON (org.id = oa.organisationid)";
        $where_aggiuntiva = "and org.path LIKE '%$dominio_appartenenza%'";
    }

    $qry = "SELECT edizione.id edizioneid, c.idnumber codicecorso, c.fullname titolo, anag.localita localita, concat(sessione.name, sessione.id) as sessione,
                    customdata.data as edizione, date.timestart datainizio, anag.course_type course_type,
                    (select count(distinct iscrizioni.userid) 
                            from {$CFG->prefix}facetoface_signups iscrizioni
                            join {$CFG->prefix}facetoface_signups_status stati ON (iscrizioni.id = stati.signupid)
                            $join_aggiuntive
                            where iscrizioni.sessionid = edizioneid 
                                and stati.statuscode IN (".MDL_F2F_STATUS_NO_SHOW.", ".MDL_F2F_STATUS_PARTIALLY_ATTENDED.", ".MDL_F2F_STATUS_FULLY_ATTENDED.")
                                $where_aggiuntiva
                       ) iscritti, anag.durata durata, anag.cf credito
                FROM {$CFG->prefix}course c
                JOIN {$CFG->prefix}f2_anagrafica_corsi anag ON (anag.courseid = c.id)
                JOIN {$CFG->prefix}facetoface sessione ON (c.id = sessione.course)
                JOIN {$CFG->prefix}facetoface_sessions edizione ON (sessione.id = edizione.facetoface)
                JOIN {$CFG->prefix}facetoface_sessions_dates date ON (date.sessionid = edizione.id)
                JOIN {$CFG->prefix}facetoface_session_data customdata ON (customdata.sessionid = edizione.id)
                JOIN {$CFG->prefix}facetoface_session_field customfield ON (customfield.id = customdata.fieldid)
                WHERE customfield.shortname = 'editionum' AND edizione.id = $edizioneid";

    return $DB->get_record_sql($qry);
}

/**
 * Restituisce gli utenti iscritti ad un'edizione di un corso
 * @param int $edizioneid
 * @param string $sort An SQL field to sort by
 * @param string $dir The sort direction ASC|DESC
 * @param int $page The page or records to return
 * @param int $recordsperpage The number of records to return per page
 * return stdClass
 */
function get_iscritti_editione($edizioneid, $sort='name', $dir='ASC', $page=0, $recordsperpage=0)
{
    global $DB, $CFG, $USER;
    
    if ($sort == 'name') $sort = 'user.lastname';
    if ($sort == 'lastname') $sort = 'user.lastname';
    if ($sort == 'firstname') $sort = 'user.firstname';
    if ($sort == 'idnumber') $sort = 'user.idnumber';
    
    if ($sort) {
        $sort = " ORDER BY $sort $dir";
    }
    
    if (!isSupervisoreConsiglio($USER->id)) {
        $join_aggiuntive = "";
        $where_aggiuntiva = "";
    } else {
        list($dominio_visibilita_id, $dominio_visibilita_name) = get_user_viewable_organisation($USER->id);
        $dominio_appartenenza = $dominio_visibilita_id;
        $join_aggiuntive = "join {$CFG->prefix}org_assignment oa ON (oa.userid = iscrizioni.userid)
                            join {$CFG->prefix}org org ON (org.id = oa.organisationid)";
        $where_aggiuntiva = "and org.path LIKE '%$dominio_appartenenza%'";
    }

    $qry = "SELECT DISTINCT user.id userid, user.firstname firstname, user.lastname lastname, user.idnumber matricola, stati.va va, stati.presenza presenza, stati.id id_stato
            FROM {user} user
            JOIN {$CFG->prefix}facetoface_signups iscrizioni ON (user.id = iscrizioni.userid)
            JOIN {$CFG->prefix}facetoface_signups_status stati ON (iscrizioni.id = stati.signupid)
            $join_aggiuntive
            WHERE iscrizioni.sessionid = $edizioneid AND stati.statuscode IN (".MDL_F2F_STATUS_NO_SHOW.", ".MDL_F2F_STATUS_PARTIALLY_ATTENDED.", ".MDL_F2F_STATUS_FULLY_ATTENDED.")".
            $where_aggiuntiva.$sort;

    return $DB->get_records_sql($qry, null, $page, $recordsperpage);
}

/**
 * Restituisce le informazioni presenti nello storico corsi per un utente e un'edizione
 * @param int $matricola la matricola dell'utente
 * @param int $cod_corso il codice (idnumber) del corso seguito dall'utente
 * @param int $data_inizio la data di inizio del corso seguito dall'utente
 * return stdClass
 */
function get_info_from_storico($matricola, $cod_corso, $data_inizio)
{
    global $DB, $CFG;

    $qry = "SELECT id, presenza, va, cfv, descrpart, data_inizio
            FROM {$CFG->prefix}f2_storico_corsi
            WHERE matricola = '$matricola' AND codcorso = '$cod_corso' AND data_inizio = $data_inizio";
            
    return $DB->get_record_sql($qry);
}

function get_dettaglio_storico_edizione($edizioneid,$codcorso,$data_inizio) {
	global $DB, $CFG, $USER;

	if (!isSupervisoreConsiglio($USER->id)) {
		$join_aggiuntive = "";
		$where_aggiuntiva = "";
	} else {
		list($dominio_visibilita_id, $dominio_visibilita_name) = get_user_viewable_organisation($USER->id);
		$dominio_appartenenza = $dominio_visibilita_id;
		$join_aggiuntive = "join {$CFG->prefix}org_assignment oa ON (oa.userid = iscrizioni.userid)
		join {$CFG->prefix}org org ON (org.id = oa.organisationid)";
		$where_aggiuntiva = "and org.path LIKE '%$dominio_appartenenza%'";
	}

	$qry = "SELECT edizione.id edizioneid, c.idnumber codicecorso, c.fullname titolo, anag.localita localita, concat(sessione.name, sessione.id) as sessione,
	customdata.data as edizione, date.timestart datainizio, anag.course_type course_type,
	(select count(distinct iscrizioni.userid)
	from {$CFG->prefix}facetoface_signups iscrizioni
	join {$CFG->prefix}facetoface_signups_status stati ON (iscrizioni.id = stati.signupid)
	$join_aggiuntive
	where iscrizioni.sessionid = edizioneid
	and stati.statuscode IN (".MDL_F2F_STATUS_NO_SHOW.", ".MDL_F2F_STATUS_PARTIALLY_ATTENDED.", ".MDL_F2F_STATUS_FULLY_ATTENDED.")
	$where_aggiuntiva
	) iscritti, anag.durata durata, anag.cf credito
	FROM {$CFG->prefix}course c
	JOIN {$CFG->prefix}f2_anagrafica_corsi anag ON (anag.courseid = c.id)
	JOIN {$CFG->prefix}facetoface sessione ON (c.id = sessione.course)
	JOIN {$CFG->prefix}facetoface_sessions edizione ON (sessione.id = edizione.facetoface)
	JOIN {$CFG->prefix}facetoface_sessions_dates date ON (date.sessionid = edizione.id)
	JOIN {$CFG->prefix}facetoface_session_data customdata ON (customdata.sessionid = edizione.id)
	JOIN {$CFG->prefix}facetoface_session_field customfield ON (customfield.id = customdata.fieldid)
	WHERE customfield.shortname = 'editionum' AND edizione.id = $edizioneid";

	return $DB->get_record_sql($qry);
}

function get_iscritti_storico_editione($dati_edizione, $sort='name', $dir='ASC', $page=0, $recordsperpage=0)
{
	global $DB, $CFG;

	if ($sort == 'name') $sort = 'nome';
	if ($sort == 'lastname') $sort = 'cognome';
	if ($sort == 'firstname') $sort = 'nome';
	if ($sort == 'idnumber') $sort = 'matricola';

	if ($sort) {
		$sort = " ORDER BY $sort $dir";
	}
	
$sql = "
		SELECT	
			*
		FROM
			{f2_storico_corsi}
		WHERE
			data_inizio = '".$dati_edizione->data_inizio."' AND
			codcorso = '".$dati_edizione->codcorso."' AND
			edizione = '".$dati_edizione->id_edizione."'
			 ".$sort."
		";

/*
	$qry = "SELECT DISTINCT user.id userid, user.firstname firstname, user.lastname lastname, user.idnumber matricola, stati.va va, stati.presenza presenza, stati.id id_stato
	FROM {user} user
	JOIN {$CFG->prefix}facetoface_signups iscrizioni ON (user.id = iscrizioni.userid)
	JOIN {$CFG->prefix}facetoface_signups_status stati ON (iscrizioni.id = stati.signupid)
	$join_aggiuntive
	WHERE iscrizioni.sessionid = $edizioneid AND stati.statuscode IN (".MDL_F2F_STATUS_NO_SHOW.", ".MDL_F2F_STATUS_PARTIALLY_ATTENDED.", ".MDL_F2F_STATUS_FULLY_ATTENDED.")".
	$where_aggiuntiva.$sort;
*/	
	//return $DB->get_records_sql($qry, null, $page, $recordsperpage);
	return $DB->get_records_sql($sql, null, $page, $recordsperpage);
}

//Restituisce l'id dell'edizione
function get_id_editione_by_dati_storico($dati_edizione)
{
	global $DB;

	
	if(!$dati_edizione->edizioneid_storico)
		$id_edizione_storico = "";
	else
		$id_edizione_storico = "AND fsd.data = ".$dati_edizione->edizioneid_storico."";
	
$sql = "SELECT
			fs.id as id_edizione
		FROM
			mdl_course c,
			mdl_facetoface f,
			mdl_facetoface_sessions fs,
			mdl_facetoface_session_data fsd
		WHERE
			c.idnumber = '".$dati_edizione->id_numder."' AND
			c.id = f.course AND
			fs.facetoface = f.id AND
			fsd.sessionid = fs.id AND
			fsd.fieldid = '9' ".$id_edizione_storico."
		";
	return $DB->get_record_sql($sql);
}

function get_dati_iscritti_edizione($dati_ed, $sort='cognome', $dir='ASC', $page=0, $recordsperpage=0){
	global $DB;
	
	
if ($sort == 'name') $sort = 'nome';
	if ($sort == 'lastname') $sort = 'cognome';
	if ($sort == 'firstname') $sort = 'nome';
	if ($sort == 'idnumber') $sort = 'matricola';

	if ($sort) {
		$sort = " ORDER BY $sort $dir";
	}
	
	if(!$dati_ed->edizioneid_storico)
		$id_edizione_storico = "";
	else
		$id_edizione_storico = " sc.edizione = ".$dati_ed->edizioneid_storico." AND ";
	
	
	$sql="
		SELECT
			sql_calc_found_rows
			tmp2.id,
			tmp2.codcorso,
			tmp2.titolo,
			tmp2.sede_corso,
			tmp2.data_inizio,
			tmp2.tipo_corso,
			tmp2.durata,
			tmp2.cfv as cfv_storico,
			tmp2.matricola,
			tmp2.nome,
			tmp2.cognome,
			tmp2.direzione,
			tmp2.settore,
			tmp2.presenza as presenza_storico,
			tmp2.va as va_storico,
			tmp2.desc_va as desc_va_storico,
			tmp1.va as va_no_storico,
			tmp1.presenza as presenza_no_storico,
			tmp1.descrizione as descizione_no_storico,
			tmp1.id_stato,
			tmp2.localita,
			tmp2.cf,
			tmp2.descrpart,
			tmp1.fs_id
		FROM
			(
			SELECT DISTINCT user.id userid, user.firstname firstname, user.lastname lastname, user.idnumber matricola, stati.va va, stati.presenza presenza, stati.id id_stato,v.descrizione,iscrizioni.id as fs_id
			FROM mdl_user user
			JOIN mdl_facetoface_signups iscrizioni ON (user.id = iscrizioni.userid)
			JOIN mdl_facetoface_signups_status stati ON (iscrizioni.id = stati.signupid)
			LEFT JOIN mdl_f2_va v on stati.va = v.id
		WHERE
			iscrizioni.sessionid = ".$dati_ed->id_edizione." AND
			stati.statuscode IN (80,90,100) AND
			superceded = 0
		) tmp1 right join
		(
		SELECT
			sc.id,
			sc.codcorso,
			sc.titolo,
			sc.sede_corso,
			sc.data_inizio,
			sc.tipo_corso,
			sc.durata,
			sc.cfv,
			sc.matricola,
			sc.nome,
			sc.cognome,
			sc.direzione,
			sc.settore,
			sc.presenza,
			sc.va,
			sc.localita,
			sc.cf,
			sc.descrpart,
			v.id as id_va,
			v.descrizione as desc_va
		FROM
			mdl_f2_storico_corsi sc left join mdl_f2_va v on sc.va = v.id
		WHERE
			sc.codcorso = '".$dati_ed->cod_corso."' AND
			".$id_edizione_storico."
			sc.data_inizio = ".$dati_ed->data_inizio."
		) tmp2
		on tmp1.matricola = tmp2.matricola
					".$sort."
";	

	$result_sql = $DB->get_records_sql($sql,null,$page,$recordsperpage);
	$result_sql_count = $DB->get_record_sql("SELECT FOUND_ROWS() AS `count`");
	$return	= new stdClass;
	$return->count	= $result_sql_count->count;
	$return->dati	= $result_sql;
	

	return $return;
}
