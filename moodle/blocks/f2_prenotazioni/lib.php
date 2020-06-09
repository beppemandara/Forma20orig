<?php
/*
 * $Id: lib.php 1241 2013-12-20 04:34:05Z l.moretto $
 */
require_once($CFG->dirroot.'/f2_lib/core.php');
require_once($CFG->dirroot.'/f2_lib/management.php');
require_once($CFG->dirroot.'/f2_lib/report.php');

define('CONST_STR_ZERO_DECIMALS', '.00');

function get_settori_by_direzione_paginato($param=null)
{
	$column = 2;
	if (!is_null($param) and !empty($param) and isset($param))
	{
		$page = ((isset($param->page) and !is_null($param->page) and !empty($param->page)) == true) ? $page = $param->page : $page = 0;
		$perpage = ((isset($param->perpage) and !is_null($param->perpage) and !empty($param->perpage)) == true) ? $perpage = $param->perpage : $perpage = 10;
// 		$column = ((isset($param->column) and !is_null($param->column) and !empty($param->column)) == true) ? $column = $param->column : $column = 2;
		$sort = ((isset($param->sort) and !is_null($param->sort) and !empty($param->sort)) == true) ? $sort = $param->sort : $sort = 'ASC';
		$organisationid = ((isset($param->organisationid) and !is_null($param->organisationid) and !empty($param->organisationid)) == true) ? $organisationid = $param->organisationid : $organisationid = -1;
	}
	else 
	{
		$page = 0;
		$perpage = 10;
		$sort = 'ASC';
		$organisationid = -1;
	}
	$sql = "SELECT o.id,o.shortname,o.fullname,if(depthid=3,1,0) as isfittizio FROM mdl_org o where o.visible = 1 and (o.path like concat('%/',".$organisationid.",'/%') or o.id = ".$organisationid.") ";
// 	$sql = "SELECT * FROM mdl_org o where o.visible = 1 and (o.path like concat('%/',".$organisationid.",'/%') or o.id = ".$organisationid.") ";
	$order_by = " order by ".$column." ".$sort;
	global $DB;
	$full_settori = $DB->get_records_sql($sql.$order_by,NULL,$page*$perpage,$perpage);

	$obj		= new stdClass();
	$obj->dati	= $full_settori;
	$obj->count	= $DB->count_records_sql("SELECT count(*) FROM (".$sql.") as tmp");
	return $obj;
}

function canManageDomain($dominio) {
	global $DB,$USER, $CFG;
	
	list($dominio_visibilita_id, $dominio_visibilita_name) = get_user_viewable_organisation($USER->id);
// 	list($dominio_dipendente_id, $dominio_dipendente_name) = get_user_organisation($userid);

	if ($dominio ==  $dominio_visibilita_id) return true;
	
	$select_path = "SELECT path FROM {$CFG->prefix}org WHERE id = $dominio";
	$path = $DB->get_field_sql($select_path);

	$pos = strpos($path, $dominio_visibilita_id);

	if ($pos === false)
		return false;
	else
		return true;
}

/**
 * Restituisce i corsi fruiti dall'utente prelevati dallo storico CSI
 * @param int $userid
 * return array of object
 */
function get_user_catalogo_corsi($data)
{
	global $DB;
	$return	= new stdClass;
	if (!is_null($data->cohorts) and !empty($data->cohorts)) $cohort = $data->cohorts;
	else $cohort = 0;
	$search_corso_str = '';
	if (!is_null($data->search_course) and trim($data->search_course)!='')
	{
		$search_corso_str = " and (lower(c.idnumber) like lower('%".$data->search_course."%')
								or lower(c.fullname) like lower('%".$data->search_course."%')) ";
	}
	$tipi_budget_prenotabili = get_tipi_budget_corsi_prenotabili_str();
	$select_str = "select distinct u.id as userid,c.id as courseid, c.idnumber as codice, c.fullname as titolo "; 
	$select_str .= " ,ifnull((select concat_ws('#', p.id,p.validato_sett,p.validato_dir,s.id,s.descrizione,p.orgid,p.isdeleted)
			 from {f2_sedi} s, {f2_prenotati} p where p.userid = u.id and p.courseid
				=c.id and p.sede = s.id),-1) as prid_vals_vald_sid_sdesc ";
	$select_str .= ',ac.cf, ac.durata, ac.costo, ac.anno ';
	$select_str .= " ,sf.id as segmento_formativo, sf.descrizione as sf_descrizione ";
	$from_str = ' from {course} c, {user} u , {f2_sf} sf, {f2_anagrafica_corsi} ac ';
	$from_str .= ' , {f2_corsi_coorti_map} accm ';
	$where_str = " where u.id = ".$data->userid." ";
	$where_str .= " and ac.anno = ".  get_anno_formativo_corrente();
	$where_str .= " and ac.tipo_budget in (".$tipi_budget_prenotabili.") ";
	$where_str .= " and ac.courseid = c.id and ac.sf = sf.id ";
	$where_str .= " and accm.courseid = c.id and accm.coorteid in (".$cohort.") ";
	$where_str .= $search_corso_str;
	$sql_str = $select_str.$from_str.$where_str;
	$order_by_str = " order by ".$data->column." ".$data->sort;
	$sql_tot = "SELECT @rownum:=@rownum+1 AS rownum, temp.* from (".$sql_str.") temp, (SELECT @rownum:=0) r ".$order_by_str;
// 	print_r($sql_tot);
	$rs = $DB->get_records_sql($sql_tot,NULL,$data->page*$data->perpage,$data->perpage);
	$return->dati = $rs;
	$return->count = $DB->count_records_sql("SELECT count(*) FROM (".$sql_tot.") as tmp");
	return $return;
}

function print_tab_prenotazioni($currenttab,$userid=0,$prenota_altri=0) {
global $USER;
	$toprow = array();
	if ((prenotazioni_dip_aperte() and $prenota_altri == 0)
		or (prenotazioni_direzione_aperte() and $prenota_altri == 1) or isSupervisore($USER->id))
	{
		$toprow[] = new tabobject('prenotazioni', new moodle_url('prenotazioni.php?userid='.$userid.'&pa='.$prenota_altri), get_string('tab_prenotazioni','block_f2_prenotazioni'));
	}
	if($prenota_altri == 1) {
		$otheruser = get_user_data($userid);
		$toprow[] = new tabobject('report_prenotazioni', new moodle_url('report_prenotazioni.php?userid='.$userid.'&pa='.$prenota_altri), 'Report prenotazioni '.$otheruser->lastname.' '.$otheruser->firstname);
	}
	else
		$toprow[] = new tabobject('report_prenotazioni', new moodle_url('report_prenotazioni.php?userid='.$userid.'&pa='.$prenota_altri), get_string('tab_report_prenotazioni','block_f2_prenotazioni'));
	$tabs = array($toprow);
	$inactive = array();
// 	if (!prenotazioni_aperte())
// 	{
// 			$inactive = array('prenotazioni');
// 	}
	print_tabs($tabs, $currenttab,$inactive);
}

function get_user_prenotazioni($data)
{
	global $DB;
	$return	= new stdClass;
	if (!is_null($data->cohorts) and !empty($data->cohorts)) $cohort = $data->cohorts;
	else $cohort = 0;
	
	if($data->anno_formativo_corrente)
		$where_anno_formativo_corrente = " AND p.anno = ".$data->anno_formativo_corrente;
	else 
		$where_anno_formativo_corrente = "";
	$tipi_budget_prenotabili = get_tipi_budget_corsi_prenotabili_str();
	$select_str = "select p.id as prenotazione_id,p.orgid, p.courseid, c.idnumber as codice, c.fullname as titolo
					,concat_ws(' - ', sf.id,sf.descrizione) as segmento_formativo
					,concat_ws(' ', s.descrizione, concat('(',p.sede,')')) as sede_prenotazione
					,p.validato_sett as validatos, p.validato_dir as validatod, p.data_prenotazione ";
	$from_str = " from {f2_sedi} s, {user} u, {f2_prenotati} p, {course} c, {f2_sf} sf, {f2_anagrafica_corsi} ac ";
	$from_str .= ' , {f2_corsi_coorti_map} accm ';
	$where_str = " where s.id = p.sede and u.id = p.userid and c.id = p.courseid and sf.id=ac.sf";
	$where_str .= $where_anno_formativo_corrente;
	$where_str .= " and u.id = ".$data->userid." and ac.courseid = c.id ";
	$where_str .= " and ac.tipo_budget in (".$tipi_budget_prenotabili.") ";
	$where_str .= " and accm.courseid = c.id and accm.coorteid in (".$cohort.") ";
	$order_by_str = " order by ".$data->column." ".$data->sort;
	$sql_tot = $select_str.$from_str.$where_str.$order_by_str;
//print_r($sql_tot);
	$rs = $DB->get_records_sql($sql_tot,NULL,$data->page*$data->perpage,$data->perpage);
	$return->dati = $rs;
	$return->count = $DB->count_records_sql("SELECT count(*) FROM (".$sql_tot.") as tmp");
	return $return;
}

function get_user_prenotazioni_per_validazione($data)
{
	global $DB;
	$return	= new stdClass;
	if (!is_null($data->cohorts) and !empty($data->cohorts)) $cohort = $data->cohorts;
	else $cohort = 0;
	$tipi_budget_prenotabili = get_tipi_budget_corsi_prenotabili_str();
	$select_str = "select p.id as prenotazione_id, p.courseid, c.idnumber as codice, c.fullname as titolo
					,p.orgid as settore
					,p.sfid as segmento_formativo, p.durata, p.costo, p.cf as crediti
					,p.sede as sede, p.data_prenotazione 
 					,p.usrname as utente_prenotazione1
					,(select concat_ws(', ',u2.lastname,u2.firstname) from {user} u2 where u2.username = p.usrname) as utente_prenotazione
					,p.val_sett_by as utente_ultima_validazione_sett
					,p.val_sett_dt as data_ultima_validazione_sett
					,p.validato_sett as validatos, p.validato_dir as validatod
					,p.val_dir_by as utente_ultima_validazione_dir
					,p.val_dir_dt as data_ultima_validazione_dir ";
	$from_str = " from {user} u, {f2_prenotati} p, {course} c, {f2_anagrafica_corsi} ac ";
	$from_str .= ' , {f2_corsi_coorti_map} accm ';
	$where_str = " where p.isdeleted = 0 and u.id = p.userid and c.id = p.courseid and p.sfid=ac.sf";
	$where_str .= " and u.id = ".$data->userid." and ac.courseid = c.id ";
	$where_str .= " and ac.tipo_budget in (".$tipi_budget_prenotabili.") ";
	$where_str .= " and accm.courseid = c.id and accm.coorteid in (".$cohort.") ";
	$order_by_str = " order by ".$data->column." ".$data->sort;
	$sql_tot = $select_str.$from_str.$where_str.$order_by_str;
	$rs = $DB->get_records_sql($sql_tot,NULL,$data->page*$data->perpage,$data->perpage);
	$return->dati = $rs;
	$return->count = $DB->count_records_sql("SELECT count(*) FROM (".$sql_tot.") as tmp");
	return $return;
}

/*
function delete_prenotazione($data)
{
	global $DB;
	if ($DB->record_exists('f2_prenotati' , array ('id' => $data->id)))
	{
		$DB->delete_records('f2_prenotati', array ('id' => $data->id));
		return true;
	}
	else return false;
}
*/

function annulla_prenotazione($data)
{
	global $DB;
	if ($DB->record_exists('f2_prenotati' , array ('id' => $data->id)))
	{
		$DB->update_record('f2_prenotati', $data);
		return true;
	}
	else return false;
}

function insert_prenotazione($data)
{
	global $DB;
	$inserted_id = -1;

	if ($DB->record_exists('f2_prenotati' , 
		array ('id'=> $data->id
			,'userid' => $data->userid
			, 'courseid' => $data->courseid 
			, 'anno' => $data->anno,
			)))
	{
		$DB->update_record('f2_prenotati', $data);
	}
	else
	{
		$inserted_id = $DB->insert_record('f2_prenotati', $data, $returnid=true);
	}
	return $inserted_id;
}

function get_stato_prenotazione_str($validato_sett=0,$validato_dir=0,$orgid=0,$fittizio=0,$prid=0)
{
	$vals=$validato_sett;
	$vald=$validato_dir;
	if ($orgid==0) return get_string('nd', 'block_f2_prenotazioni');
	$isdeleted = 0;
	if ($prid !== 0) 
	{
		global $DB;
		$isdeleted = intval($DB->get_field('f2_prenotati','isdeleted', array('id' => $prid)));
	}
	if ($isdeleted !== 0) return get_string('prenotazione_annullata', 'block_f2_prenotazioni');
	else 
	{
		if (isDirezione($orgid))
		{
			$stato_validaz_direzione = get_stato_validazione_by_dominio($orgid);
		}
		else // non è una direzione, occorre ricavare lo stato della direzione
		{
			$root = get_root_framework();
			$rootid = $root->id;
		
            while (!(isDirezione($orgid) xor $orgid == $rootid)){
				$direz = get_organisation_info_by_id($orgid);
				$orgid = $direz->parentid;
			}
			$stato_validaz_direzione = get_stato_validazione_by_dominio($orgid);
		}
//$cond = (is_null($stato_validaz_direzione)
//				or empty($stato_validaz_direzione)
//				or !isset($stato_validaz_direzione)
//				or $stato_validaz_direzione->stato_validaz_dir == 'A'
//				or $stato_validaz_direzione->stato_validaz_dir == 'B'
//				// 		or $stato_validaz_direzione->stato_validaz_dir == 'C'
//		);
//var_dump($cond);
		if (is_null($stato_validaz_direzione)
				or empty($stato_validaz_direzione)
				or !isset($stato_validaz_direzione)
				or $stato_validaz_direzione->stato_validaz_dir == 'A'
				or $stato_validaz_direzione->stato_validaz_dir == 'B'
				// 		or $stato_validaz_direzione->stato_validaz_dir == 'C'
		)
		{
			$stato_str = get_string('stato_0_da_validare', 'block_f2_prenotazioni');
		}
		else //validazioni direzione chiuse effettuata verifica budget
		{
			if ($vals == 1 and $vald == 1)
			{
				$stato_str = get_string('stato_1_validato', 'block_f2_prenotazioni');
			}
			else if ($vals == 0 or $vald == 0) //validazioni chiuse stato non validato
			{
				// 			$stato_str = get_string('stato_0_da_validare', 'block_f2_prenotazioni');
				$stato_str = get_string('stato_2_non_validato', 'block_f2_prenotazioni');
			}
			else if ($vals == 2 or $vald == 2)
			{
				$stato_str = get_string('stato_2_non_validato', 'block_f2_prenotazioni');
			}
			else // non dovrebbe mai entrare
			{
				$stato_str = get_string('nd', 'block_f2_prenotazioni');
			}
		}
		return $stato_str;
	}
}

function validazioni_aperte()
{
	global $DB;
	$sqlstr = "select count(id) as num_aperte from {f2_stati_funz} f where f.id like '%validaz%' and f.aperto = 's'";
	$n = $DB->get_record_sql($sqlstr);
	$num_aperte = intval($n->num_aperte);
	if ($num_aperte > 0) return true;
	else return false;
}

function validazioni_direzione_aperte()
{
	global $DB;
	$sqlstr = "select count(id) as num_aperte from {f2_stati_funz} f where f.id = 'validazione_direzione' and f.aperto = 's'";
	$n = $DB->get_record_sql($sqlstr);
	$num_aperte = intval($n->num_aperte);
	if ($num_aperte > 0) return true;
	else return false;
}

function validazioni_settore_aperte()
{
	global $DB;
	$sqlstr = "select count(id) as num_aperte from {f2_stati_funz} f where f.id = 'validazione_settore' and f.aperto = 's'";
	$n = $DB->get_record_sql($sqlstr);
	$num_aperte = intval($n->num_aperte);
	if ($num_aperte > 0) return true;
	else return false;
}

function get_userid_utenti_by_dominio_appartenenza_str($settore_id)
{
	$utenti_del_settore = get_utenti_by_dominio_appartenenza($settore_id);
	$utenti_del_settore_id_str = '-1';
	foreach ($utenti_del_settore as $u)
	{
		$utenti_del_settore_id_str .= ','.$u->id;
	}
	$utenti_del_settore_id_str = trim($utenti_del_settore_id_str,',');
	return $utenti_del_settore_id_str;
}

function get_dipendenti_by_caposettore($settore_id, $user_id = NULL, $data = null, $exclude = false) {
	global $CFG, $DB, $USER;

	$user_id = is_null($user_id) ? intval($USER->id) : $user_id;
	$exclude = ($exclude) ? ' AND user.id <> '.$user_id : '';
	if (is_null($data) or empty($data) or !isset($data))
	{
		$data = new stdClass;
		$data->column = '1';
		$data->sort = 'ASC';
		$data->page = 0;
		$data->perpage = 0;
	}
	$order = " ORDER BY ".$data->column." ".$data->sort;
	if (isset($data->search_name) and !is_null($data->search_name) and !empty($data->search_name))
	{
		$filter = " AND lastname like '%".mysql_escape_string($data->search_name)."%' ";
	}
	else $filter = " ";

	$utenti_del_settore_id_str = get_userid_utenti_by_dominio_appartenenza_str($settore_id);
	
	$sql_str = "SELECT user.id, username, user.idnumber, user.email, 
	concat_ws(', ',user.lastname,user.firstname) as utente
			FROM {user} user where 
			user.id in (".$utenti_del_settore_id_str.") ".$exclude." ".$filter;
	$users = $DB->get_records_sql($sql_str.$order,NULL,$data->page*$data->perpage,$data->perpage);

	$obj		= new stdClass();
	$obj->dati	= $users;
	$obj->count	= $DB->count_records_sql("SELECT count(*) FROM (".$sql_str.") as tmp");

	return $obj;
}

function get_percentuali_completamento($userid)
{
// 	echo '<br/>perc';
// 	return array();
	$userdata = get_user_data($userid);
	$user_category = $userdata->category;
	$lastupdate = get_user_last_update($userdata->idnumber);
	$dates = get_obj_date_piano_di_studi();
	$aSF = get_segmento_formativo();
	$cat_cf_necessari = get_user_totali_crediti($user_category);
	$aSF_cp = get_user_crediti_for_settore($user_category);	

	// crediti richiesti per il superamento del piano
	$aSF_ca_corrente = get_user_storico_crediti_attivi($userdata->idnumber, 'corrente', $dates, $user_category); 	// crediti attivi per SF dell'anno corrente
	$aSF_ca_precedente = get_user_storico_crediti_attivi($userdata->idnumber, 'precedente', $dates, $user_category);// crediti attivi per SF dell'anno precedente
	$aSF_cu_corrente = get_user_storico_crediti_utilizzabili($userdata->idnumber, $aSF_ca_corrente, $aSF_cp);		// crediti utilizzabili per SF dell'anno corrente
	$aSF_cu_precedente = get_user_storico_crediti_utilizzabili($userdata->idnumber, $aSF_ca_precedente, $aSF_cp);	// crediti utilizzabili per SF dell'anno precedente
	
	$sum_ca_prec = 0;
	$sum_cu_prec = 0;
	$sum_ca_cor = 0;
	$sum_cu_cor = 0;
	// Ciclo sui Segmenti Formativi
	foreach ($aSF as $sf) 
	{
		if (isset($aSF_ca_precedente[$sf->id]->crediti_attivi))
		{
			$sum_ca_prec += $aSF_ca_precedente[$sf->id]->crediti_attivi;
		}
		if (isset($aSF_cu_precedente[$sf->id]->crediti_utilizzabili))
		{
			$sum_cu_prec += $aSF_cu_precedente[$sf->id]->crediti_utilizzabili;
		}
		if (isset($aSF_ca_corrente[$sf->id]->crediti_attivi))
		{
			$sum_ca_cor += $aSF_ca_corrente[$sf->id]->crediti_attivi;
		}
		if (isset($aSF_cu_corrente[$sf->id]->crediti_utilizzabili))
		{
			$sum_cu_cor += $aSF_cu_corrente[$sf->id]->crediti_utilizzabili;
		}
	}
	
	// Calcolo percentuale piano di studio
	$compX100ap = 0;
	$compX100ac = 0;
	$max_perc = 100.00;
	if ($cat_cf_necessari > 0) 
	{
		$compX100ap = round((($sum_cu_prec / $cat_cf_necessari)*100),2);
		$compX100ac = round((($sum_cu_cor / $cat_cf_necessari)*100),2);
		if ($compX100ap > $max_perc) 
		{
			$compX100ap = 100;
		}
		if ($compX100ac > $max_perc)
		{
			$compX100ac = 100;
		}
	}
// 	$anno_prec = get_anno_formativo_corrente();
// 	$anno_seguente = $anno_prec + 1;
	$return = array();
	$return[] = array('data_fine_precedente' => $dates->data_fine_precedente, 'perc_fine_precedente' => $compX100ap);
	$return[] =  array('data_fine_corrente' => $dates->data_fine_corrente, 'perc_fine_corrente' => $compX100ac);
	return $return;
}

function get_dati_ultima_prenotazione($userid,$anno)
{
	global $DB;
	if (!isset($anno) or is_null($anno) or empty($anno) or $anno == '') 
	{
		$anno=get_anno_formativo_corrente();
	}
	$sql_str = "select ifnull((select concat_ws('#', concat_ws(', ',u.lastname,u.firstname),max(p.data_prenotazione))
			 from {f2_prenotati} p, {user} u  
			where p.isdeleted = 0 and p.userid = ".$userid." and p.anno = ".$anno." and u.username = p.usrname 
					group by p.usrname 
			 		order by p.data_prenotazione desc limit 0,1),-1) 
			 		as dati_prenotazione";
	$dati_ultima_prenotazione = $DB->get_record_sql($sql_str);
	if ($dati_ultima_prenotazione->dati_prenotazione !== '-1')
	{
		$dati_ultima_prenotazione_arr = array();
		$dati_ultima_prenotazione_arr = explode('#',$dati_ultima_prenotazione->dati_prenotazione);
		$ultima_prenotazione_utente = $dati_ultima_prenotazione_arr[0];
		$ultima_prenotazione_data = date('d/m/Y',$dati_ultima_prenotazione_arr[1]);
	}
	else 
	{
		$ultima_prenotazione_data = '-';
		$ultima_prenotazione_utente = '-';
	}
	$return = new stdClass;
	$return->max_prenotazione_data = $ultima_prenotazione_data;
	$return->max_prenotazione_utente = $ultima_prenotazione_utente;
	return $return;
}

function get_dati_ultima_validazione($userid,$anno)
{
	global $DB;
	if (!isset($anno) or is_null($anno) or empty($anno) or $anno == '')
	{
		$anno=get_anno_formativo_corrente();
	}
	$sql_str = "select ifnull((select concat_ws('#', p.val_sett_by,max(p.val_sett_dt),'-1')
			 from {f2_prenotati} p where p.isdeleted = 0 and p.userid = ".$userid." and p.anno = ".$anno." group by p.val_sett_by
			 		order by p.val_sett_by desc limit 0,1),-1)
			 		as dati_validazione
			 		union
			 	select ifnull((select concat_ws('#', p.val_dir_by,max(p.val_dir_dt),'-1')
			 from {f2_prenotati} p where p.isdeleted = 0 and p.userid = ".$userid." and p.anno = ".$anno." group by p.val_dir_by
			 		order by p.val_dir_dt desc limit 0,1),-1)
			 		as dati_validazione";
	$rs = $DB->get_records_sql($sql_str);
	$max_validazione_data = -1;
	$max_validazione_utente = '-';
	foreach ($rs as $va)
	{
		if ($va->dati_validazione !== '-1')
		{
			$dati_ultima_validazione_arr = array();
			$dati_ultima_validazione_arr = explode('#',$va->dati_validazione);
			$ultima_validazione_utente = $dati_ultima_validazione_arr[0];
			$ultima_validazione_data = date('d/m/Y',$dati_ultima_validazione_arr[1]);
			if ($ultima_validazione_data > $max_validazione_data) 
			{
				$max_validazione_data = $ultima_validazione_data;
				$userdata = get_user_data(intval($ultima_validazione_utente));
				$max_validazione_utente = $userdata->lastname.', '.$userdata->firstname;
			}
		}
	}
	if ($max_validazione_data == -1) $max_validazione_data = '-'; 
	$return = new stdClass;
	$return->max_validazione_data = $max_validazione_data;
	$return->max_validazione_utente = $max_validazione_utente;
	return $return;
}

function get_numero_prenotazioni($userid,$anno)
{
	global $DB;
	if (!isset($anno) or is_null($anno) or empty($anno) or $anno == '')
	{
		$anno=get_anno_formativo_corrente();
	}
	$sql_str = "select count(p.id) from {f2_prenotati} p
				where p.isdeleted = 0 and p.userid = ".$userid." and p.anno = ".$anno." ";
	$rs = $DB->count_records_sql($sql_str, array());
	return intval($rs);
}

function get_numero_validazioni($userid,$anno)
{
	global $DB;
	if (!isset($anno) or is_null($anno) or empty($anno) or $anno == '')
	{
		$anno=get_anno_formativo_corrente();
	}
	$sql_str = "select count(p.id) from {f2_prenotati} p
				where p.isdeleted = 0 and p.userid = ".$userid." and p.anno = ".$anno." 
				and p.validato_sett = 1 ";
	$rs = $DB->count_records_sql($sql_str, array());
	return intval($rs);
}
function get_numero_validazioni_dir($userid,$anno)
{
	global $DB;
	if (!isset($anno) or is_null($anno) or empty($anno) or $anno == '')
	{
		$anno=get_anno_formativo_corrente();
	}
	$sql_str = "select count(p.id) from {f2_prenotati} p
				where p.isdeleted = 0 and p.userid = ".$userid." and p.anno = ".$anno."
				and p.validato_dir = 1 ";
	$rs = $DB->count_records_sql($sql_str, array());
	return intval($rs);
}

function get_numero_prenotazioni_tot_sett($settore_id,$anno)
{
	global $DB;
	
	if (!isset($anno) or is_null($anno) or empty($anno) or $anno == '')
	{
		$anno=get_anno_formativo_corrente();
	}
// 	var_dump($anno);exit;
	$sql_str = "select count(p.id) from {f2_prenotati} p
				where p.isdeleted = 0 and p.orgid = ".$settore_id." and p.anno = ".$anno." ";
	$rs = $DB->count_records_sql($sql_str, array());
	return intval($rs);
}

function get_numero_validazioni_tot_sett($settore_id,$anno)
{
	global $DB;
	if (!isset($anno) or is_null($anno) or empty($anno) or $anno == '')
	{
		$anno=get_anno_formativo_corrente();
	}
	$sql_str = "select count(p.id) from {f2_prenotati} p
				where p.isdeleted = 0 and p.orgid = ".$settore_id." and p.anno = ".$anno."
				and p.validato_sett = 1 ";
	$rs = $DB->count_records_sql($sql_str, array());
	return intval($rs);
}
function get_numero_validazioni_tot_dir($settore_id,$anno)
{
	global $DB;
	if (!isset($anno) or is_null($anno) or empty($anno) or $anno == '')
	{
		$anno=get_anno_formativo_corrente();
	}
	$sql_str = "select count(p.id) from {f2_prenotati} p
				where p.isdeleted = 0 and p.orgid = ".$settore_id." and p.anno = ".$anno."
				and p.validato_dir = 1 ";
	$rs = $DB->count_records_sql($sql_str, array());
	return intval($rs);
}

function get_giorni_crediti_prenotati($userid,$anno)
{
	global $DB;
	if (!isset($anno) or is_null($anno) or empty($anno) or $anno == '')
	{
		$anno=get_anno_formativo_corrente();
	}
	$sql_str = " select concat_ws(' / ',sum(temp.somma_durata), sum(temp.somma_crediti)) as giorni_crediti_prenotati  
				from (select p.userid,sum(p.cf) as somma_crediti, sum(p.durata) as somma_durata
				from {f2_prenotati} p
				where p.isdeleted = 0 and p.userid = ".$userid." and p.anno = ".$anno." group by p.userid 
				) temp ";
	$count = $DB->count_records_sql("select count(*) from (".$sql_str.") t");
	if ($count == 0) $return = '- / -';
	else
	{
		$rs = $DB->get_record_sql($sql_str);
		$return = str_replace(CONST_STR_ZERO_DECIMALS, "", $rs->giorni_crediti_prenotati);
	}
	return $return;
}

function get_giorni_crediti_validati($userid,$livello='sett',$anno)
{
	global $DB;
	
	if (!isset($anno) or is_null($anno) or empty($anno) or $anno == '')
	{
		$anno=get_anno_formativo_corrente();
	}
	if (preg_match('/dir/i', $livello) === 1)
	{
		$livello_sql = " and p.validato_dir = 1 ";
	}
	else $livello_sql = " and p.validato_sett = 1 ";
	$sql_str = " select concat_ws(' / ',temp.somma_durata, temp.somma_crediti) as giorni_crediti_validati
				from (select p.userid,sum(p.cf) as somma_crediti, sum(p.durata) as somma_durata
				from {f2_prenotati} p
				where p.isdeleted = 0 and p.userid = ".$userid." and p.anno = ".$anno." ".$livello_sql." group by p.userid
				) temp ";
	$count = $DB->count_records_sql("select count(*) from (".$sql_str.") t");
	if ($count == 0) $return = '- / -';
	else
	{
		$rs = $DB->get_record_sql($sql_str);
		$return = str_replace(CONST_STR_ZERO_DECIMALS, "", $rs->giorni_crediti_validati);
	}
	return $return;
}

function get_giorni_crediti_prenotati_sett($settore_id,$anno)
{
	global $DB;
	if (!isset($anno) or is_null($anno) or empty($anno) or $anno == '')
	{
		$anno=get_anno_formativo_corrente();
	}
	$sql_str = " select ifnull(concat_ws(' / ',sum(temp.somma_durata), sum(temp.somma_crediti)),'- / -')
			 as giorni_crediti_prenotati
				from (select p.userid,sum(p.cf) as somma_crediti, sum(p.durata) as somma_durata
				from {f2_prenotati} p
				where p.isdeleted = 0 and p.orgid = ".$settore_id." and p.anno = ".$anno." group by p.userid
				) temp ";
// 	$count = $DB->count_records_sql("select count(*) from (".$sql_str.") t");
// 	if ($count == 0) $return = '- / -';
// 	else
// 	{
// 		$rs = $DB->get_record_sql($sql_str);
// 		$return = $rs->giorni_crediti_prenotati;
// 	}
	$rs = $DB->get_record_sql($sql_str);
	$return = str_replace(CONST_STR_ZERO_DECIMALS, "", $rs->giorni_crediti_prenotati);
	return $return;
}

function get_giorni_crediti_validati_sett($settore_id,$anno)
{
	global $DB;
	if (!isset($anno) or is_null($anno) or empty($anno) or $anno == '')
	{
		$anno=get_anno_formativo_corrente();
	}
	$sql_str = " select ifnull(concat_ws(' / ',sum(temp.somma_durata), sum(temp.somma_crediti)),'- / -') 
			as giorni_crediti_validati
				from (select p.userid,sum(p.cf) as somma_crediti, sum(p.durata) as somma_durata
				from {f2_prenotati} p
				where p.isdeleted = 0 and p.orgid = ".$settore_id." and p.anno = ".$anno." and p.validato_sett = 1 group by p.userid
				) temp ";
	$count = $DB->count_records_sql("select count(*) from (".$sql_str.") t");
// 	if ($count == 0) $return = '- / -';
// 	else
// 	{
// 		$rs = $DB->get_record_sql($sql_str);
// 		$return = $rs->giorni_crediti_validati;
// 	}
	$rs = $DB->get_record_sql($sql_str);
	$return = str_replace(CONST_STR_ZERO_DECIMALS, "", $rs->giorni_crediti_validati);
	return $return;
}

function get_giorni_crediti_validati_dir($settore_id,$anno)
{
	global $DB;
	if (!isset($anno) or is_null($anno) or empty($anno) or $anno == '')
	{
		$anno=get_anno_formativo_corrente();
	}
	$sql_str = " select ifnull(concat_ws(' / ',sum(temp.somma_durata), sum(temp.somma_crediti)), '- / -') 
			as giorni_crediti_validati
				from (select p.userid,sum(p.cf) as somma_crediti, sum(p.durata) as somma_durata
				from {f2_prenotati} p
				where p.isdeleted = 0 and p.orgid = ".$settore_id." and p.anno = ".$anno." and p.validato_dir = 1 group by p.userid
				) temp ";
	$count = $DB->count_records_sql("select count(*) from (".$sql_str.") t");
// 	if ($count == 0) $return = '- / -';
// 	else
// 	{
// 		$rs = $DB->get_record_sql($sql_str);
// 		$return = $rs->giorni_crediti_validati;
// 	}
	$rs = $DB->get_record_sql($sql_str);
	$return = str_replace(CONST_STR_ZERO_DECIMALS, "", $rs->giorni_crediti_validati);
	return $return;
}

function update_stati_validazioni_globali($data)
{
	if (!isset($data) or is_null($data) or empty($data)) return -1;
	else
	{
		$nome_stato = $data->nome_stato;
		$nuovo_stato = $data->nuovo_stato;
		$anno = $data->anno;
		$dominio = $data->dominio;
		if (is_null($anno) or !isset($anno) or empty($anno)) 
		{
			$anno = get_anno_formativo_corrente();
		}
		if (is_null($nome_stato) or !isset($nome_stato) or empty($nome_stato) 
			or is_null($nuovo_stato) or !isset($nuovo_stato) or empty($nuovo_stato))
		{
			return -1;
		}
		else if ($nome_stato !== 'stato_validaz_sett' and $nome_stato !== 'stato_validaz_dir')
		{
			return -1;
		}
		else 
		{
			if ($nome_stato == 'stato_validaz_sett')
			{
				if ($nuovo_stato !== 'A' and $nuovo_stato !== 'B' and $nuovo_stato !== 'C')
				{
					return -1;
				}
			}
			else if ($nome_stato == 'stato_validaz_dir')
			{
				if ($nuovo_stato !== 'A' and $nuovo_stato !== 'B' and $nuovo_stato !== 'C'
						and $nuovo_stato !== 'D' and $nuovo_stato !== 'E')
				{
					return -1;
				}
			}
			global $DB;
			$id_stato_sql = "select sv.id from {f2_stati_validazione} sv
					where sv.anno = ".$anno." and orgid = ".$dominio;
			$count = $DB->count_records_sql("select count(*) from (".$id_stato_sql.") t");
			$id_stato = -1;
			if ($count == 0) // da creare
			{
				$id_stato = init_stati_validazione($dominio,$anno);
				$record = new stdClass();
				$record->id = $id_stato;
				$record->$nome_stato = $nuovo_stato;
				$DB->update_record('f2_stati_validazione', $record);
			}
			else // da aggiornare
			{
				$id_stato = $DB->get_record_sql($id_stato_sql);
				$record = new stdClass();
				$record->id = $id_stato->id;
				$record->$nome_stato = $nuovo_stato;
				$DB->update_record('f2_stati_validazione', $record);
			}
			$domini = $dominio;
			if (isSettore($dominio)) //occorre considerare anche il dominio (direzione) padre
			{
				$sql_dominio_padre = "select org.parentid from {org} org where id = ".$dominio;
				$dominio_padre = $DB->get_record_sql($sql_dominio_padre);
				$stato = new stdClass;
				$stato->anno = $anno;
				$stato->dominio = $dominio_padre->parentid;
				$stato->nome_stato = 'stato_validaz_dir';
				$stato->nuovo_stato = 'B';
				update_stati_validazioni_globali($stato);
			}
			return $id_stato;
		}
	}
}

function init_stati_validazione($dominio,$anno)
{
	global $DB;
	$record = new stdClass();
	$record->stato_validaz_sett = 'A'; //valore iniziale
	$record->stato_validaz_dir = 'A'; //valore iniziale
	$record->anno = $anno;
	$record->orgid = $dominio;
	return $DB->insert_record('f2_stati_validazione', $record, true);
}

function get_prenotazioni_da_modificare($settore_id, $user_id, $anno, $nuovo_stato) 
{
	global $CFG, $DB, $USER;
	
	$sql_str_user = get_userid_utenti_by_dominio_appartenenza_str($settore_id);
	$sql_prenotazioni_id = "select p.id from {f2_prenotati} p
			where p.isdeleted = 0 and p.anno = ".$anno." and p.userid in ( ".$sql_str_user." )
				and p.orgid = ".$settore_id." and  p.validato_sett <> ".$nuovo_stato."";
	return $sql_prenotazioni_id;
}
function get_prenotazioni_da_modificare_direzione($settore_id, $user_id, $anno, $nuovo_stato)
{
	global $CFG, $DB, $USER;
	
// 	$validato_sett_str = ' and p.validato_sett == 1 ';
	$validato_sett_str = '  ';
	$sql_str_user = get_userid_utenti_by_dominio_appartenenza_str($settore_id);
	$sql_prenotazioni_id = "select p.id,p.validato_sett,p.validato_dir from {f2_prenotati} p
			where p.isdeleted = 0 and p.anno = ".$anno." and p.userid in ( ".$sql_str_user." )
				and p.orgid = ".$settore_id;
// 	." and p.validato_dir <> ".$nuovo_stato."".$validato_sett_str;
	return $sql_prenotazioni_id;
}

function validazione_settore_all($settore_id=null, $anno = null, $user_id = NULL) {
	global $CFG, $DB, $USER;
	
	$user_id = is_null($user_id) ? intval($USER->id) : $user_id;
        if (is_null($settore_id)) {
            $settore = get_settore_utente($user_id);
            $settore_id = $settore['id'];
        }
	$anno = is_null($anno) ? get_anno_formativo_corrente() : intval($anno);
	
	$nuovostato = 1; //validato
	$sql_prenotazioni_id = get_prenotazioni_da_modificare($settore_id, $user_id, $anno, $nuovostato);
	$pids = $DB->get_records_sql($sql_prenotazioni_id);
	
	foreach ($pids as $pid)
	{
		$updt = new stdClass;
		$updt->id = $pid->id;
		$updt->validato_sett = 1;
		$updt->val_sett_by = $user_id;
		$updt->val_sett_dt = time();
		$DB->update_record('f2_prenotati', $updt);
	}
	$stato_globale_validazioni_sett = new stdClass;
	$stato_globale_validazioni_sett->anno = $anno;
	$stato_globale_validazioni_sett->nome_stato = 'stato_validaz_sett';
	$stato_globale_validazioni_sett->nuovo_stato = 'A';
	$stato_globale_validazioni_sett->dominio = $settore_id;
	
	$stato_globale_validazioni_dir = new stdClass;
	$stato_globale_validazioni_dir->anno = $anno;
	$stato_globale_validazioni_dir->nome_stato = 'stato_validaz_dir';
	$stato_globale_validazioni_dir->nuovo_stato = 'B';
	$stato_globale_validazioni_dir->dominio = $settore_id;
	
	update_stati_validazioni_globali($stato_globale_validazioni_sett);
	update_stati_validazioni_globali($stato_globale_validazioni_dir);
}

function validazione_settore_all_dir($settore_id=null, $anno = null, $user_id = NULL) {
	global $CFG, $DB, $USER;

	$user_id = is_null($user_id) ? intval($USER->id) : $user_id;
        if (is_null($settore_id)) {
            $settore = get_settore_utente($user_id);
            $settore_id = $settore['id'];
        }
	$anno = is_null($anno) ? get_anno_formativo_corrente() : intval($anno);

	$nuovostato = 1; //validato
	$sql_prenotazioni_id = get_prenotazioni_da_modificare_direzione($settore_id, $user_id, $anno, $nuovostato);
	$pids = $DB->get_records_sql($sql_prenotazioni_id);

	foreach ($pids as $pid)
	{
		$updt = new stdClass;
		$updt->id = $pid->id;
		$updt->validato_dir = $pid->validato_sett;
// 		$updt->validato_dir = 1;
		if ($pid->validato_sett <> $pid->validato_dir)
		{
			$updt->val_dir_by = $user_id;
			$updt->val_dir_dt = time();
		}
		$DB->update_record('f2_prenotati', $updt);
	}
	
	$stato_globale_validazioni_sett = new stdClass;
	$stato_globale_validazioni_sett->anno = $anno;
	$stato_globale_validazioni_sett->nome_stato = 'stato_validaz_sett';
	$stato_globale_validazioni_sett->nuovo_stato = 'B';
	$stato_globale_validazioni_sett->dominio = $settore_id;

	$stato_globale_validazioni_dir = new stdClass;
	$stato_globale_validazioni_dir->anno = $anno;
	$stato_globale_validazioni_dir->nome_stato = 'stato_validaz_dir';
	$stato_globale_validazioni_dir->nuovo_stato = 'B';
	$stato_globale_validazioni_dir->dominio = $settore_id;

	update_stati_validazioni_globali($stato_globale_validazioni_sett);
	update_stati_validazioni_globali($stato_globale_validazioni_dir);
}

function validazione_dir_all($settore_id=null, $anno = null, $user_id = NULL) {
	global $CFG, $DB, $USER;

	$user_id = is_null($user_id) ? intval($USER->id) : $user_id;
        if (is_null($settore_id)) {
            $settore = get_settore_utente($user_id);
            $settore_id = $settore['id'];
        }
	$anno = is_null($anno) ? get_anno_formativo_corrente() : intval($anno);

	$nuovostato = -1; // prende tutti
	$sql_prenotazioni_id = get_prenotazioni_da_modificare_direzione($settore_id, $user_id, $anno, $nuovostato);
	$pids = $DB->get_records_sql($sql_prenotazioni_id);

	foreach ($pids as $pid)
	{
		$updt = new stdClass;
		$updt->id = $pid->id;
		$updt->validato_dir = $pid->validato_sett;
		$updt->val_dir_by = $user_id;
		$updt->val_dir_dt = time();
		$DB->update_record('f2_prenotati', $updt);
	}

	$stato_globale_validazioni_sett = new stdClass;
	$stato_globale_validazioni_sett->anno = $anno;
	$stato_globale_validazioni_sett->nome_stato = 'stato_validaz_sett';
	$stato_globale_validazioni_sett->nuovo_stato = 'C';
	$stato_globale_validazioni_sett->dominio = $settore_id;

	$stato_globale_validazioni_dir = new stdClass;
	$stato_globale_validazioni_dir->anno = $anno;
	$stato_globale_validazioni_dir->nome_stato = 'stato_validaz_dir';
	$stato_globale_validazioni_dir->nuovo_stato = 'C';
	$stato_globale_validazioni_dir->dominio = $settore_id;

	update_stati_validazioni_globali($stato_globale_validazioni_sett);
	update_stati_validazioni_globali($stato_globale_validazioni_dir);
}

function validazione_reopen_dir($settore_id=null, $anno = null, $user_id = NULL)
{
	$user_id = is_null($user_id) ? intval($USER->id) : $user_id;
        if (is_null($settore_id)) {
            $settore = get_settore_utente($user_id);
            $settore_id = $settore['id'];
        }
	$anno = is_null($anno) ? get_anno_formativo_corrente() : intval($anno);

	$stato_globale_validazioni_dir = new stdClass;
	$stato_globale_validazioni_dir->anno = $anno;
	$stato_globale_validazioni_dir->nome_stato = 'stato_validaz_dir';
	$stato_globale_validazioni_dir->nuovo_stato = 'B';
	$stato_globale_validazioni_dir->dominio = $settore_id;

	update_stati_validazioni_globali($stato_globale_validazioni_dir);
}

function validazione_settore_del_all($settore_id=null, $anno = null, $user_id = NULL) {
	global $CFG, $DB, $USER;
	
	$user_id = is_null($user_id) ? intval($USER->id) : $user_id;
        if (is_null($settore_id)) {
            $settore = get_settore_utente($user_id);
            $settore_id = $settore['id'];
        }
	$anno = is_null($anno) ? get_anno_formativo_corrente() : intval($anno);
	
	$nuovostato = 0; // non validato - da validare
	$sql_prenotazioni_id = get_prenotazioni_da_modificare($settore_id, $user_id, $anno, $nuovostato);
	$pids = $DB->get_records_sql($sql_prenotazioni_id);

	foreach ($pids as $pid)
	{
		$updt = new stdClass;
		$updt->id = $pid->id;
		$updt->validato_sett = 0; // torna ad essere una prenotazione da validare
		$updt->val_sett_by = $user_id;
		$updt->val_sett_dt = time();
		$DB->update_record('f2_prenotati', $updt);
	}
	
	$stato_globale_validazioni_sett = new stdClass;
	$stato_globale_validazioni_sett->anno = $anno;
	$stato_globale_validazioni_sett->nome_stato = 'stato_validaz_sett';
	$stato_globale_validazioni_sett->nuovo_stato = 'A';
	$stato_globale_validazioni_sett->dominio = $settore_id;
	
	$stato_globale_validazioni_dir = new stdClass;
	$stato_globale_validazioni_dir->anno = $anno;
	$stato_globale_validazioni_dir->nome_stato = 'stato_validaz_dir';
	$stato_globale_validazioni_dir->nuovo_stato = 'B';
	$stato_globale_validazioni_dir->dominio = $settore_id;
	
	update_stati_validazioni_globali($stato_globale_validazioni_sett);
	update_stati_validazioni_globali($stato_globale_validazioni_dir);
}
function validazione_direzione_su_settore_del_all($settore_id=null, $anno = null, $user_id = NULL) {
	global $CFG, $DB, $USER;

	$user_id = is_null($user_id) ? intval($USER->id) : $user_id;
        if (is_null($settore_id)) {
            $settore = get_settore_utente($user_id);
            $settore_id = $settore['id'];
        }
	$anno = is_null($anno) ? get_anno_formativo_corrente() : intval($anno);

	$nuovostato = 0; // non validato - da validare
	$sql_prenotazioni_id = get_prenotazioni_da_modificare_direzione($settore_id, $user_id, $anno, $nuovostato);
	$pids = $DB->get_records_sql($sql_prenotazioni_id);
	
	foreach ($pids as $pid)
	{
		$updt = new stdClass;
		$updt->id = $pid->id;
		$updt->validato_dir = 0; // torna ad essere una prenotazione da validare a livello di direzione
		// il lavoro fatto dal ref di settore rimane invariato
// 		$updt->validato_sett = 0; 
// 		if ($pid->validato_sett == 1)
// 		{
// 			$updt->val_sett_by = $user_id;
// 			$updt->val_sett_dt = time();
// 		}
		if ($pid->validato_dir == 1)
		{
			$updt->val_dir_by = $user_id;
			$updt->val_dir_dt = time();
		} 
		$DB->update_record('f2_prenotati', $updt);
	}
	
// 	$stato_globale_sett = $DB->get_field('f2_stati_validazione', 
// 			'stato_validaz_sett', array('orgid' => $settore_id, 'anno'=>$anno));
// 	$nuovo_stato_sett = ($stato_globale_sett == 'C') ? 'B' : 'A';
	
	$nuovo_stato_sett ='B';

	$stato_globale_validazioni_sett = new stdClass;
	$stato_globale_validazioni_sett->anno = $anno;
	$stato_globale_validazioni_sett->nome_stato = 'stato_validaz_sett';
	$stato_globale_validazioni_sett->nuovo_stato = $nuovo_stato_sett;
	$stato_globale_validazioni_sett->dominio = $settore_id;

// 	$stato_globale_validazioni_dir = new stdClass;
// 	$stato_globale_validazioni_dir->anno = $anno;
// 	$stato_globale_validazioni_dir->nome_stato = 'stato_validaz_dir';
// 	$stato_globale_validazioni_dir->nuovo_stato = 'A';
// 	$stato_globale_validazioni_dir->dominio = $settore_id;

	update_stati_validazioni_globali($stato_globale_validazioni_sett);
// 	update_stati_validazioni_globali($stato_globale_validazioni_dir);
}

function validazione_direzione_del_all($settore_id=null, $anno = null, $user_id = NULL) {
	global $CFG, $DB, $USER;

	$user_id = is_null($user_id) ? intval($USER->id) : $user_id;
        if (is_null($settore_id)) {
            $settore = get_settore_utente($user_id);
            $settore_id = $settore['id'];
        }
	$anno = is_null($anno) ? get_anno_formativo_corrente() : intval($anno);

	$nuovostato = 0; // non validato - da validare
	$sql_prenotazioni_id = get_prenotazioni_da_modificare_direzione($settore_id, $user_id, $anno, $nuovostato);
	$pids = $DB->get_records_sql($sql_prenotazioni_id);

	foreach ($pids as $pid)
	{
		$updt = new stdClass;
		$updt->id = $pid->id;
		$updt->validato_dir = 0; // torna ad essere una prenotazione da validare a livello di direzione
		$updt->val_dir_by = $user_id;
		$updt->val_dir_dt = time();
		$DB->update_record('f2_prenotati', $updt);
	}

	$stato_globale_sett = $DB->get_field('f2_stati_validazione',
			'stato_validaz_sett', array('orgid' => $settore_id, 'anno'=>$anno));
	$nuovo_stato_sett = ($stato_globale_sett == 'C') ? 'B' : 'A';

// 	$nuovo_stato_sett ='B';

	$stato_globale_validazioni_sett = new stdClass;
	$stato_globale_validazioni_sett->anno = $anno;
	$stato_globale_validazioni_sett->nome_stato = 'stato_validaz_sett';
	$stato_globale_validazioni_sett->nuovo_stato = $nuovo_stato_sett;
	$stato_globale_validazioni_sett->dominio = $settore_id;

	$stato_globale_validazioni_dir = new stdClass;
	$stato_globale_validazioni_dir->anno = $anno;
	$stato_globale_validazioni_dir->nome_stato = 'stato_validaz_dir';
	$stato_globale_validazioni_dir->nuovo_stato = 'A';
	$stato_globale_validazioni_dir->dominio = $settore_id;

	update_stati_validazioni_globali($stato_globale_validazioni_sett);
	update_stati_validazioni_globali($stato_globale_validazioni_dir);
}

function validazione_settore_close($settore_id=null,$fittizio=0, $anno = null, $user_id = NULL)
{
	global $USER;
	$user_id = is_null($user_id) ? intval($USER->id) : $user_id;
        if (is_null($settore_id)) {
            $settore = get_settore_utente($user_id);
            $settore_id = $settore['id'];
        }
	$anno = is_null($anno) ? get_anno_formativo_corrente() : intval($anno);
	
	$stato_globale_validazioni_sett = new stdClass;
	$stato_globale_validazioni_sett->anno = $anno;
	$stato_globale_validazioni_sett->nome_stato = 'stato_validaz_sett';
	$stato_globale_validazioni_sett->nuovo_stato = 'B';
	$stato_globale_validazioni_sett->dominio = $settore_id;
	update_stati_validazioni_globali($stato_globale_validazioni_sett);
	
	if ($fittizio == 0)
	{
		$stato_globale_validazioni_dir = new stdClass;
		$stato_globale_validazioni_dir->anno = $anno;
		$stato_globale_validazioni_dir->nome_stato = 'stato_validaz_dir';
		$stato_globale_validazioni_dir->nuovo_stato = 'B';
		$stato_globale_validazioni_dir->dominio = $settore_id;
		update_stati_validazioni_globali($stato_globale_validazioni_dir);
	}
}
function validazione_settore_close_dir($settore_id=null,$fittizio=0, $anno = null, $user_id = NULL)
{
	global $DB,$USER;
	$user_id = (is_null($user_id)) ? intval($USER->id) : $user_id;
        if (is_null($settore_id)) {
            $settore = get_settore_utente($user_id);
            $settore_id = $settore['id'];
        }
	$anno = is_null($anno) ? get_anno_formativo_corrente() : intval($anno);
	
	//sistema incongruenze prima di chiudere un settore
	$sql_prenotazioni_id = 'select p.id,p.validato_dir from {f2_prenotati} p
			where p.isdeleted = 0 and p.orgid = '.$settore_id.' and p.anno ='.$anno.'
			and p.validato_sett <> p.validato_dir';
	$sql_prenotazioni_id .= " and p.validato_dir = 1 ";
	$pids = $DB->get_records_sql($sql_prenotazioni_id);
	
	foreach ($pids as $pid)
	{
		$updt = new stdClass;
		$updt->id = $pid->id;
		$updt->validato_sett = $pid->validato_dir;
		$updt->val_dir_by = $user_id;
		$updt->val_dir_dt = time();
		$DB->update_record('f2_prenotati', $updt);
	}
	
	$stato_globale_validazioni_sett = new stdClass;
	$stato_globale_validazioni_sett->anno = $anno;
	$stato_globale_validazioni_sett->nome_stato = 'stato_validaz_sett';
	$stato_globale_validazioni_sett->nuovo_stato = 'C';
	$stato_globale_validazioni_sett->dominio = $settore_id;

	$stato_globale_validazioni_dir = new stdClass;
	$stato_globale_validazioni_dir->anno = $anno;
	$stato_globale_validazioni_dir->nome_stato = 'stato_validaz_dir';
	if ($fittizio == 0)
	{
		$stato_globale_validazioni_dir->nuovo_stato = 'C';
	}
	else if ($fittizio == 1)
	{
		$stato_globale_validazioni_dir->nuovo_stato = 'B';
	}
	$stato_globale_validazioni_dir->dominio = $settore_id;

	update_stati_validazioni_globali($stato_globale_validazioni_sett);
	update_stati_validazioni_globali($stato_globale_validazioni_dir);
}

function validazione_settore_reopen($settore_id=null, $anno = null, $user_id = NULL)
{
	global $USER;
	$user_id = is_null($user_id) ? intval($USER->id) : $user_id;
        if (is_null($settore_id)) {
            $settore = get_settore_utente($user_id);
            $settore_id = $settore['id'];
        }
	$anno = is_null($anno) ? get_anno_formativo_corrente() : intval($anno);

	$stato_globale_validazioni_sett = new stdClass;
	$stato_globale_validazioni_sett->anno = $anno;
	$stato_globale_validazioni_sett->nome_stato = 'stato_validaz_sett';
	$stato_globale_validazioni_sett->nuovo_stato = 'A';
	$stato_globale_validazioni_sett->dominio = $settore_id;

	$stato_globale_validazioni_dir = new stdClass;
	$stato_globale_validazioni_dir->anno = $anno;
	$stato_globale_validazioni_dir->nome_stato = 'stato_validaz_dir';
	$stato_globale_validazioni_dir->nuovo_stato = 'B';
	$stato_globale_validazioni_dir->dominio = $settore_id;

	update_stati_validazioni_globali($stato_globale_validazioni_sett);
	update_stati_validazioni_globali($stato_globale_validazioni_dir);
}
function validazione_settore_reopen_dir($settore_id=null, $anno = null, $user_id = NULL)
{
	global $USER;
	$user_id = is_null($user_id) ? intval($USER->id) : $user_id;
        if (is_null($settore_id)) {
            $settore = get_settore_utente($user_id);
            $settore_id = $settore['id'];
        }
	$anno = is_null($anno) ? get_anno_formativo_corrente() : intval($anno);

	$stato_globale_validazioni_sett = new stdClass;
	$stato_globale_validazioni_sett->anno = $anno;
	$stato_globale_validazioni_sett->nome_stato = 'stato_validaz_sett';
	$stato_globale_validazioni_sett->nuovo_stato = 'B';
	$stato_globale_validazioni_sett->dominio = $settore_id;

	$stato_globale_validazioni_dir = new stdClass;
	$stato_globale_validazioni_dir->anno = $anno;
	$stato_globale_validazioni_dir->nome_stato = 'stato_validaz_dir';
	$stato_globale_validazioni_dir->nuovo_stato = 'B';
	$stato_globale_validazioni_dir->dominio = $settore_id;

	update_stati_validazioni_globali($stato_globale_validazioni_sett);
	update_stati_validazioni_globali($stato_globale_validazioni_dir);
}

function check_stati_validazione_per_utente($userid)
{
	$isOk = false;
	if (is_null($userid) or empty($userid) or !isset($userid)) return $isOk;
	else if (validazioni_aperte())
	{
		if (isReferenteDiSettore($userid))
		{
			if (validazioni_settore_aperte())
			{
				$isOk = true;
			}
		}
		else if (isReferenteDiDirezione($userid))
		{
			if (validazioni_direzione_aperte())
			{
				$isOk = true;
			}
		}
		else if (isSupervisore($userid))
		{
			$isOk = true;
		}
		else
		{
			$isOk = false;
		}
	}
	else $isOk = false;
	return $isOk;
}

function canView($userid,$role_required)
{
	$ret = false;
	if (is_null($userid) or empty($userid) or !isset($userid)
		or is_null($role_required) or empty($role_required) or !isset($role_required)) return $ret;
	else 
	{
		if (isSupervisore($userid)) $ret = true;
		else if (isReferenteDiDirezione($userid) and 
				(
				 (preg_match('/sett/i',$role_required) === 1) or 
				 (preg_match('/dir/i',$role_required) === 1)
				)) $ret = true;
		else if (isReferenteDiSettore($userid) and (preg_match('/sett/i',$role_required) === 1))
			$ret = true;
	}
	return $ret;
}

function get_validazioni_dati_tabella($c,$pid_inconsistenti=null,$pid_sett_chk=null,$pid_dir_chk=null)
{
	global $USER;
	$inconsistenza_char = '';
	if (!is_null($pid_inconsistenti))
	{
		if (in_array($c->prenotazione_id, $pid_inconsistenti))
		{
			$inconsistenza_char = '*';
		}
	}
	$cod_corso = $c->codice;
	$tit_corso = $c->titolo;
	$sede_corso = $c->sede;
	$sf = $c->segmento_formativo;
	$durata_corso = $c->durata;
	$cf_corso = $c->crediti;
	$costo_corso = $c->costo;
	if (!is_null($c->data_prenotazione) and !empty($c->data_prenotazione)
			and $c->data_prenotazione !== ' ')
	{
		$data_prenotazione = date('d/m/Y',$c->data_prenotazione);
	}
	else $data_prenotazione = '-'; // non dovrebbe mai accadere
	$utente_prenotazione = $c->utente_prenotazione;
	
	if (canView($USER->id, 'dir'))
	{
		// settore
		if (isset($pid_sett_chk) and !is_null($pid_sett_chk) and !empty($pid_sett_chk))
		{
			if (in_array($c->prenotazione_id, $pid_sett_chk))
			{
				$checked_sett = 'checked';
			}
		}
		else 
		{
			$checked_sett = ($c->validatos == 1) ? 'checked' : '';
		}
		$checkbox_validazione_sett = '<input type="checkbox" name="prenotazione_id_sett[]"
					id = "'.$c->prenotazione_id.'" value="'.$c->prenotazione_id.'" '.$checked_sett.'>
							<span style="color:red" id='.$c->prenotazione_id.'span_sett'.'>'.$inconsistenza_char.'</span>';
		$checkbox_validazione_sett .= '<input type="hidden" name="prenotazione_id_all[]"
					id = "'.$c->prenotazione_id.'" value = "'.$c->prenotazione_id.'">';
		
		if (!is_null($c->data_ultima_validazione_sett) and !empty($c->data_ultima_validazione_sett)
				and $c->data_ultima_validazione_sett !== ' ')
		{
			$data_ultima_validazione_sett = date('d/m/Y',$c->data_ultima_validazione_sett);
		}
		else $data_ultima_validazione_sett = '-';
		if (!is_null($c->utente_ultima_validazione_sett) and !empty($c->utente_ultima_validazione_sett)
				and $c->utente_ultima_validazione_sett !== ' ')
		{
			$udata = get_user_data(intval($c->utente_ultima_validazione_sett));
			$utente_ultima_validazione_sett =  $udata->lastname.', '.$udata->firstname;
		}
		else $utente_ultima_validazione_sett = '-';
		
		// direzione
		if (isset($pid_dir_chk) and !is_null($pid_dir_chk) and !empty($pid_dir_chk))
		{
			if (in_array($c->prenotazione_id, $pid_dir_chk))
			{
				$checked_dir = 'checked';
			}
		}
		else
		{
			$checked_dir = ($c->validatod == 1) ? 'checked' : '';
		}
		$checkbox_validazione_dir = '<input type="checkbox" name="prenotazione_id_dir[]"
					id = "'.$c->prenotazione_id.'" value="'.$c->prenotazione_id.'" '.$checked_dir.'>
							<span style="color:red" id='.$c->prenotazione_id.'span_dir'.'>'.$inconsistenza_char.'</span>';
		
		if (!is_null($c->data_ultima_validazione_dir) and !empty($c->data_ultima_validazione_dir)
				and $c->data_ultima_validazione_dir !== ' ')
		{
			$data_ultima_validazione_dir = date('d/m/Y',$c->data_ultima_validazione_dir);
		}
		else $data_ultima_validazione_dir = '-';
		if (!is_null($c->utente_ultima_validazione_dir) and !empty($c->utente_ultima_validazione_dir)
				and $c->utente_ultima_validazione_dir !== ' ')
		{
			$udata = get_user_data(intval($c->utente_ultima_validazione_dir));
			$utente_ultima_validazione_dir =  $udata->lastname.', '.$udata->firstname;
		}
		else $utente_ultima_validazione_dir = '-';
		
		return array(
				$cod_corso,$tit_corso,$sede_corso,$sf,
                str_replace(CONST_STR_ZERO_DECIMALS, "", $durata_corso.' / '.$cf_corso),$costo_corso
				,$data_prenotazione,$utente_prenotazione,$checkbox_validazione_sett
				,$data_ultima_validazione_sett,$utente_ultima_validazione_sett
				,$checkbox_validazione_dir,$data_ultima_validazione_dir,$utente_ultima_validazione_dir
		);
	}
	else // solo settore
	{
		// settore
		$checked_sett = ($c->validatos == 1) ? 'checked' : '';
		$checkbox_validazione_sett = '<input type="checkbox" name="prenotazione_id_sett[]"
					id = "'.$c->prenotazione_id.'" value="'.$c->prenotazione_id.'" '.$checked_sett.'>';
		$checkbox_validazione_sett .= '<input type="hidden" name="prenotazione_id_all[]"
					id = "'.$c->prenotazione_id.'" value = "'.$c->prenotazione_id.'">';
		
		if (!is_null($c->data_ultima_validazione_sett) and !empty($c->data_ultima_validazione_sett)
				and $c->data_ultima_validazione_sett !== ' ')
		{
			$data_ultima_validazione_sett = date('d/m/Y',$c->data_ultima_validazione_sett);
		}
		else $data_ultima_validazione_sett = '-';
		if (!is_null($c->utente_ultima_validazione_sett) and !empty($c->utente_ultima_validazione_sett)
				and $c->utente_ultima_validazione_sett !== ' ')
		{
			$udata = get_user_data(intval($c->utente_ultima_validazione_sett));
			$utente_ultima_validazione_sett =  $udata->lastname.', '.$udata->firstname;
		}
		else $utente_ultima_validazione_sett = '-';
		
		return array(
				$cod_corso,$tit_corso,$sede_corso,$sf,
                str_replace(CONST_STR_ZERO_DECIMALS, "", $durata_corso.' / '.$cf_corso),$costo_corso
				,$data_prenotazione,$utente_prenotazione,$checkbox_validazione_sett
				,$data_ultima_validazione_sett,$utente_ultima_validazione_sett
		);
	}
}

function get_organisation_info_by_id($domainid)
{
	global $DB;
	if (is_null($domainid) or empty($domainid) or !isset($domainid)) return null;
	else 
	{
		$sql = "SELECT * FROM {org} WHERE id = ".$domainid;
		$rs = $DB->get_record_sql($sql);
		return $rs;
	}
}

function get_stato_validazione_by_dominio($dominio=null,$anno=null)
{
	global $DB;
	$anno = is_null($anno) ? get_anno_formativo_corrente() : intval($anno);
	if (is_null($dominio)) return null;
	else 
	{
		$sql_str = "select sv.* 
				from {f2_stati_validazione} sv where sv.anno = ".$anno." 
						and orgid = ".$dominio;
		$rs = $DB->get_record_sql($sql_str);
		if (is_null($rs) or empty($rs) or !isset($rs))
		{
			if ($DB->record_exists('org' , array ('id' => $dominio)))
			{
				$stato_id = init_stati_validazione($dominio,$anno);
				$sql_str = "select sv.*
				from {f2_stati_validazione} sv where sv.id = ".$stato_id." and sv.anno = ".$anno."
						and orgid = ".$dominio;
				$rs = $DB->get_record_sql($sql_str);
			}
		}
		return $rs;
	}
}
function get_stato_validazione_str($dominio,$settore_fittizio,$anno=null)
{
	if (!isset($settore_fittizio) or is_null($settore_fittizio) or empty($settore_fittizio))
	{
		$settore_fittizio = 0;
	}
	$data = get_stato_validazione_by_dominio($dominio,$anno);
	$str = 'n.d.';
	if (is_null($data) or empty($data) or !isset($data)) return $str;
	else 
	{
		$dominio = $data->orgid;
		if (isSettore($dominio) or $settore_fittizio == 1)
		{
			$val = $data->stato_validaz_sett;
			$str = get_string('stato_validaz_sett_'.$val,'block_f2_prenotazioni');
		}
		else if (isDirezione($dominio))
		{
			$val = $data->stato_validaz_dir;
			$str = get_string('stato_validaz_dir_'.$val,'block_f2_prenotazioni');
		}
		else // domini di livello 1-2 per ora trattati come direzioni (fittizie)
		{
			$val = $data->stato_validaz_dir;
			$str = get_string('stato_validaz_dir_'.$val,'block_f2_prenotazioni');
		}
	}
	return $str;
}

function is_dominio_closed($dominio, $livello=null, $settore_fittizio=0,$anno=null)
{
	if (is_null($livello) or empty($livello) or !isset($livello))
	{
		$livello = 'sett';
	}
	if (!isset($anno) or is_null($anno) or empty($anno)) $anno = get_anno_formativo_corrente();
	$data = get_stato_validazione_by_dominio($dominio,$anno);
	$return = false;
	$val_s = $data->stato_validaz_sett;
	$val_d = $data->stato_validaz_dir;
// 	print_r($data);print_r($livello);
	if (is_null($data) or empty($data) or !isset($data)) return $return;
	else 
	{
		if ($settore_fittizio == 0)
		{
			if (preg_match('/sett/i', $livello) === 1)
			{
				if (($val_s == 'B') or ($val_s == 'C')) $return = true;
			}
			else if (preg_match('/dir/i', $livello) === 1)
			{
				if (($val_d == 'C') or ($val_d == 'D') or ($val_d == 'E')) $return = true;
			}
			else $return = false;
		}
		else // è un settore fittizio occorre controllare solo val_s
		{
			if (preg_match('/sett/i', $livello) === 1)
			{
				if (($val_s == 'B') or ($val_s == 'C')) $return = true;
			}
			else if (preg_match('/dir/i', $livello) === 1)
			{
				if (($val_s == 'C')) $return = true;
			}
			else $return = false;
		}
	}
	return $return;
}

function get_num_validazioni_inconsistenti_by_dominio($domid,$userid,$anno)
{
	global $DB;
	$count = -1;
	$userid_sql = '';
	if (!isset($domid) or is_null($domid) or empty($domid)) return $count;
	if (!isset($anno) or is_null($anno) or empty($anno)) $anno = get_anno_formativo_corrente();
	if (!isset($userid) or is_null($userid) or empty($userid)) $userid_sql = '';
	else $userid_sql = " and p.userid = ".$userid." ";
	
	$sql = "select count(p.id) as num_validaz_anomale from {f2_prenotati} p
			where p.isdeleted = 0 and p.anno = ".$anno." and p.validato_sett = 0 and p.validato_dir = 1 and orgid = ".$domid." 
					".$userid_sql;

	$count = $DB->get_record_sql($sql);
// 	print_r($count->num_validaz_anomale);
	return $count->num_validaz_anomale;
}

function get_datarow_tabella_sommario_direzione($settore_id,$settore_fullname,$settore_shortname,$sett_fittizio=0,&$num_validazioni_inconsistenti,$anno)
{
	if(!isset($anno) or is_null($anno) or empty($anno)) $anno = get_anno_formativo_corrente();
	$nextpage='validazioni_altri.php';
	if ($sett_fittizio == 1) $next_param_str='?show_sf=1&organisationid='.$settore_id;
	else $next_param_str = '?organisationid='.$settore_id;
	$numero_prenotazioni = get_numero_prenotazioni_tot_sett($settore_id,$anno);
	if ($numero_prenotazioni == 0)
	{
		$numero_validazioni_sett = 0;
		$numero_validazioni_dir = 0;
		$num_giorni_crediti_prenotati_sett = '- / -';
	}
	else
	{
		$numero_validazioni_sett = intval(get_numero_validazioni_tot_sett($settore_id,$anno));
		$numero_validazioni_dir = intval(get_numero_validazioni_tot_dir($settore_id,$anno));
		$num_giorni_crediti_prenotati_sett = get_giorni_crediti_prenotati_sett($settore_id,$anno);
	}
	if ($numero_validazioni_sett == 0)
	{
		$num_giorni_crediti_validati_sett = '- / -';
	}
	else
	{
		$num_giorni_crediti_validati_sett = get_giorni_crediti_validati_sett($settore_id,$anno);
	}
	if ($numero_validazioni_dir == 0)
	{
		$num_giorni_crediti_validati_dir = '- / -';
	}
	else
	{
		$num_giorni_crediti_validati_dir = get_giorni_crediti_validati_dir($settore_id,$anno);
	}
	
	$span_color = '';
	$validaz_inconsistenti = get_num_validazioni_inconsistenti_by_dominio($settore_id,null,$anno);
	if ($validaz_inconsistenti > 0)
	{
		$span_color = 'style="color:red"';
		$num_validazioni_inconsistenti = $num_validazioni_inconsistenti + $validaz_inconsistenti;
	}
// 	if ($numero_validazioni_dir > $numero_validazioni_sett)
// 	{
// 		$span_color = 'style="color:red"';
// 		$num_validazioni_inconsistenti++;
// 	}
	$span_start ='<span '.$span_color.'>';
	$span_end = '</span>';
	$stato_validaz = get_stato_validazione_str($settore_id,$sett_fittizio,$anno);
	return array(
			"<a href='".$nextpage.$next_param_str."'>".$settore_shortname.' - '.$settore_fullname."</a>",
			$span_start.$stato_validaz.$span_end,
			$span_start.$numero_prenotazioni.$span_end,
			$span_start.$numero_validazioni_sett.$span_end,
			$span_start.$numero_validazioni_dir.$span_end,
			$span_start.$num_giorni_crediti_prenotati_sett.$span_end,
			$span_start.$num_giorni_crediti_validati_sett.$span_end,
			$span_start.$num_giorni_crediti_validati_dir.$span_end
	);
}

function get_tabella_bottoni_settore($settore_id,$settore_fittizio,$param_all_next)
{
	global $OUTPUT;
	global $USER;
	
	$op_ref_sett_str = '_sett';
	if (isReferenteDiDirezione($USER->id) or isSupervisore($USER->id))
	{
		$op_ref_sett_str = '_dir';
	} 
	$table_buttons = new html_table();
	$table_buttons->width = '80%';
	$align_buttons = array ('left','left','left','left');
	$table_buttons->align = $align_buttons;
	$table_buttons->attributes = array('class'=> '__anyunexistingclass__');
	$cell1 = new html_table_cell();
	$cell1->text = get_string('op_su_settore'.$op_ref_sett_str, 'block_f2_prenotazioni');
	$cell1->colspan = 2;
	$row1 = new html_table_row();
	$row1->cells[] = $cell1;
	$table_buttons->data = array($row1);
	
	$is_settore_closed = is_dominio_closed($settore_id,'sett');
// 	echo '<br/>'.$settore_id;
	if ($is_settore_closed == false)
	{
		// bottone valida tutto il settore
		$buttonc = new single_button(new moodle_url('manage_validazione_settore_all.php', $param_all_next),
				get_string('validazione_settore_all'.$settore_fittizio, 'block_f2_prenotazioni'));
		$actionc = new component_action('click','M.util.show_confirm_dialog',array(
				'message' => ''.get_string('confirm_validazioni_sett_all'.$settore_fittizio, 'block_f2_prenotazioni').'',
				// 		'callback' =>'confirm_cancella_tutti(\''.get_string('confirm_msg2', 'block_f2_gestione_risorse').'\',\''.$anno_formativo.'\')',
				'continuelabel' => ''.get_string('conferma','block_f2_prenotazioni').'',
				'cancellabel' => ''.get_string('annulla','block_f2_prenotazioni').''));
		$buttonc->add_action($actionc);
		// bottone annulla validazioni tutto il settore
		$buttona = new single_button(new moodle_url('manage_validazione_sett_del_all.php', $param_all_next),
				get_string('validazione_settore_del_all'.$settore_fittizio, 'block_f2_prenotazioni'));
		$actiona = new component_action('click','M.util.show_confirm_dialog',array(
				'message' => ''.get_string('confirm_validazione_settore_del_all'.$settore_fittizio, 'block_f2_prenotazioni').'',
				// 		'callback' =>'confirm_cancella_tutti(\''.get_string('confirm_msg2', 'block_f2_gestione_risorse').'\',\''.$anno_formativo.'\')',
				'continuelabel' => ''.get_string('conferma','block_f2_prenotazioni').'',
				'cancellabel' => ''.get_string('annulla','block_f2_prenotazioni').''));
		$buttona->add_action($actiona);
		//bottone chiudi validazioni settore
		$buttonclose = new single_button(new moodle_url('manage_validazione_sett_close.php', $param_all_next),
				get_string('validazione_settore_close'.$settore_fittizio, 'block_f2_prenotazioni'));
		$actionclose = new component_action('click','M.util.show_confirm_dialog',array(
				'message' => ''.get_string('confirm_validazione_settore_close'.$settore_fittizio, 'block_f2_prenotazioni').'',
				// 		'callback' =>'confirm_cancella_tutti(\''.get_string('confirm_msg2', 'block_f2_gestione_risorse').'\',\''.$anno_formativo.'\')',
				'continuelabel' => ''.get_string('conferma','block_f2_prenotazioni').'',
				'cancellabel' => ''.get_string('annulla','block_f2_prenotazioni').''));
		$buttonclose->add_action($actionclose);
		
		$table_buttons->data[] = array(
				$OUTPUT->help_icon('sett_conferma_val_capo_sett', 'block_f2_prenotazioni'),
				$OUTPUT->render($buttonc));
		$table_buttons->data[] = array(
				$OUTPUT->help_icon('sett_annulla_val_capo_sett', 'block_f2_prenotazioni'),
				$OUTPUT->render($buttona));
		$table_buttons->data[] = array(
				$OUTPUT->help_icon('sett_chiudi_val_sett', 'block_f2_prenotazioni'),
				$OUTPUT->render($buttonclose)
		);
	}
	else // ref di settore con settore chiuso
	{
		//bottone riapri validazioni settore
		$buttonreopen = new single_button(new moodle_url('manage_validazione_sett_reopen.php', $param_all_next),
				get_string('validazione_settore_reopen'.$settore_fittizio, 'block_f2_prenotazioni'));
		$actionreopen = new component_action('click','M.util.show_confirm_dialog',array(
				'message' => ''.get_string('confirm_validazione_settore_reopen'.$settore_fittizio, 'block_f2_prenotazioni').'',
				// 		'callback' =>'confirm_cancella_tutti(\''.get_string('confirm_msg2', 'block_f2_gestione_risorse').'\',\''.$anno_formativo.'\')',
				'continuelabel' => ''.get_string('conferma','block_f2_prenotazioni').'',
				'cancellabel' => ''.get_string('annulla','block_f2_prenotazioni').''));
		$buttonreopen->add_action($actionreopen);
		$table_buttons->data[] = array(
				$OUTPUT->help_icon('sett_reopen_val_sett', 'block_f2_prenotazioni'),
				$OUTPUT->render($buttonreopen));
	}
	return $table_buttons;
}

function get_tabella_bottoni_gen_direzione($settore_id,$mostra_dip_sett=true)
{
	global $OUTPUT;
	$table_buttons_gen = new html_table();
	$table_buttons_gen->width = '20%';
	$align_buttons_gen = array ('left','left');
	$table_buttons_gen->align = $align_buttons_gen;
	$table_buttons_gen->attributes = array('class'=> '__anyunexistingclass__');
	
	$buttons = new single_button(new moodle_url('scegli_settore.php'),get_string('scegli_settore', 'block_f2_prenotazioni'));
	$backpage = 'validazioni_altri.php?organisationid='. $settore_id;
	$button_back = new single_button(new moodle_url($backpage),get_string('back_riepilogo', 'block_f2_prenotazioni'));
	
	if ($mostra_dip_sett == false) // sommario di direzione
	{
		$table_buttons_gen->data[] = array(
				$OUTPUT->help_icon('scegli_settore', 'block_f2_prenotazioni'),
				$OUTPUT->render($buttons)
		);
	}
	else // sommario utenti, occore il back_riepilogo
	{
		$table_buttons_gen->data[] = array(
				$OUTPUT->help_icon('back_riepilogo', 'block_f2_prenotazioni'),
				$OUTPUT->render($button_back),
				$OUTPUT->help_icon('scegli_settore', 'block_f2_prenotazioni'),
				$OUTPUT->render($buttons)
		);
	}
	return $table_buttons_gen;
}

function get_tabella_bottoni_direzione($settore_id,$settore_fittizio,$param_all_next,$num_validazioni_inconsistenti)
{
	global $OUTPUT;
	$settore_fittizio_str = ''; //inutile a livello di direzione
	$table_buttons_dir_su_sett = new html_table();
	$table_buttons_dir_su_sett->width = '80%';
	$align_buttons_dir_su_sett = array ('left','left','left','left');
	$table_buttons_dir_su_sett->align = $align_buttons_dir_su_sett;
	$table_buttons_dir_su_sett->attributes = array('class'=> '__anyunexistingclass__');
	$cell1 = new html_table_cell();
	$cell1->text = get_string('op_su_direzione', 'block_f2_prenotazioni');
	$cell1->colspan = 2;
	$row1 = new html_table_row();
	$row1->cells[] = $cell1;
	
	$table_buttons_dir_su_sett->data = array($row1);
	
	$is_settore_closed_dir = is_dominio_closed($settore_id,'dir',$settore_fittizio);
	
	if ($is_settore_closed_dir == false)
	{
		// bottone valida tutto il settore livello direzione
		$buttonc_dir = new single_button(new moodle_url('manage_validazione_settore_all_dir.php', $param_all_next),
				get_string('validazione_settore_all_dir'.$settore_fittizio_str, 'block_f2_prenotazioni'));
		$actionc_dir = new component_action('click','M.util.show_confirm_dialog',array(
				'message' => ''.get_string('dir_confirm_validazioni_sett_all'.$settore_fittizio_str, 'block_f2_prenotazioni').'',
				// 		'callback' =>'confirm_cancella_tutti(\''.get_string('confirm_msg2', 'block_f2_gestione_risorse').'\',\''.$anno_formativo.'\')',
				'continuelabel' => ''.get_string('conferma','block_f2_prenotazioni').'',
				'cancellabel' => ''.get_string('annulla','block_f2_prenotazioni').''));
		$buttonc_dir->add_action($actionc_dir);
		// bottone annulla validazioni tutto il settore livello direzione
		$buttona_dir = new single_button(new moodle_url('manage_validazione_sett_del_all_dir.php', $param_all_next),
				get_string('validazione_settore_del_all_dir'.$settore_fittizio_str, 'block_f2_prenotazioni'));
		$actiona_dir = new component_action('click','M.util.show_confirm_dialog',array(
				'message' => ''.get_string('dir_confirm_validazione_settore_del_all'.$settore_fittizio_str, 'block_f2_prenotazioni').'',
				// 		'callback' =>'confirm_cancella_tutti(\''.get_string('confirm_msg2', 'block_f2_gestione_risorse').'\',\''.$anno_formativo.'\')',
				'continuelabel' => ''.get_string('conferma','block_f2_prenotazioni').'',
				'cancellabel' => ''.get_string('annulla','block_f2_prenotazioni').''));
		$buttona_dir->add_action($actiona_dir);
		//bottone chiudi validazioni settore livello direzione
		$buttonclose_dir = new single_button(new moodle_url('manage_validazione_sett_close_dir.php', $param_all_next),
				get_string('validazione_settore_close'.$settore_fittizio_str, 'block_f2_prenotazioni'));
		$actionclose_dir = new component_action('click','M.util.show_confirm_dialog',array(
				'message' => ''.get_string('dir_confirm_validazione_settore_close'.$settore_fittizio_str, 'block_f2_prenotazioni').'',
				// 		'callback' =>'confirm_cancella_tutti(\''.get_string('confirm_msg2', 'block_f2_gestione_risorse').'\',\''.$anno_formativo.'\')',
				'continuelabel' => ''.get_string('conferma','block_f2_prenotazioni').'',
				'cancellabel' => ''.get_string('annulla','block_f2_prenotazioni').''));
		$buttonclose_dir->add_action($actionclose_dir);
		
		// 			$table_buttons_dir_su_sett->data[] = array(get_string('op_su_direzione', 'block_f2_prenotazioni'));
		$table_buttons_dir_su_sett->data[] = array(
				$OUTPUT->help_icon('dir_conferma_val_capo_sett', 'block_f2_prenotazioni'),
				$OUTPUT->render($buttonc_dir),
		);
		$table_buttons_dir_su_sett->data[] = array(
				$OUTPUT->help_icon('dir_annulla_val_capo_sett', 'block_f2_prenotazioni'),
				$OUTPUT->render($buttona_dir),
		);
		if ($num_validazioni_inconsistenti == 0)
		{
			$table_buttons_dir_su_sett->data[] = array(
					$OUTPUT->help_icon('dir_chiudi_val_sett', 'block_f2_prenotazioni'),
					$OUTPUT->render($buttonclose_dir),
			);
		}
	}
	else // ref di direzione con settore chiuso
	{
		//bottone riapri validazioni settore livello direzione
		$buttonreopen_dir = new single_button(new moodle_url('manage_validazione_sett_reopen_dir.php', $param_all_next),
				get_string('validazione_settore_reopen'.$settore_fittizio_str, 'block_f2_prenotazioni'));
		$actionreopen_dir = new component_action('click','M.util.show_confirm_dialog',array(
				'message' => ''.get_string('confirm_validazione_settore_reopen'.$settore_fittizio_str, 'block_f2_prenotazioni').'',
				// 		'callback' =>'confirm_cancella_tutti(\''.get_string('confirm_msg2', 'block_f2_gestione_risorse').'\',\''.$anno_formativo.'\')',
				'continuelabel' => ''.get_string('conferma','block_f2_prenotazioni').'',
				'cancellabel' => ''.get_string('annulla','block_f2_prenotazioni').''));
		$buttonreopen_dir->add_action($actionreopen_dir);
		
		$table_buttons_dir_su_sett->data[] = array(
				$OUTPUT->help_icon('dir_reopen_val_sett', 'block_f2_prenotazioni'),
				$OUTPUT->render($buttonreopen_dir));
	}
	return $table_buttons_dir_su_sett;
}

function get_tabella_bottoni_op_su_direzione($settore_id,$settore_fittizio,$param_all_next,$num_validazioni_inconsistenti)
{
	global $OUTPUT;
	$table_buttons = new html_table();
	$table_buttons->width = '50%';
	$align_buttons = array ('left','left','left','left','left');
	$table_buttons->align = $align_buttons;
	$table_buttons->attributes = array('class'=> '__anyunexistingclass__');
	
	//richiamata sempre a livello di direzione, non si deve considerare il settore fittizio
	if (is_dominio_closed($settore_id, 'dir',0) == true)
	{
		
		// bottone riapri la direzione
		$buttondreopen = new single_button(new moodle_url('manage_validazione_dir_reopen.php', $param_all_next),
				get_string('validazione_dir_reopen', 'block_f2_prenotazioni'));
		$actiondreopen = new component_action('click','M.util.show_confirm_dialog',array(
				'message' => ''.get_string('confirm_validazioni_dir_reopen', 'block_f2_prenotazioni').'',
// 				'callback' =>'checkAnomalieValidazioniSettori(\''.$settore_id.'\',\''.get_string('anomalie_validaz_sett', 'block_f2_prenotazioni').'\')',
				'continuelabel' => ''.get_string('conferma','block_f2_prenotazioni').'',
				'cancellabel' => ''.get_string('annulla','block_f2_prenotazioni').''));
		$buttondreopen->add_action($actiondreopen);
		
		$stato_direzione = get_stato_validazione_by_dominio($settore_id);
		
		if (!($stato_direzione->stato_validaz_dir == 'D'
			or $stato_direzione->stato_validaz_dir == 'E'))
		{
			//bottone verifica budget
			$buttondbdg = new single_button(new moodle_url('manage_validazione_dir_verifica_bdg.php', $param_all_next),
					get_string('validazione_dir_verifica_bdg', 'block_f2_prenotazioni'));
			$actiondbdg = new component_action('click','M.util.show_confirm_dialog',array(
					'message' => ''.get_string('confirm_validazione_dir_verifica_bdg', 'block_f2_prenotazioni').'',
					// 		'callback' =>'confirm_cancella_tutti(\''.get_string('confirm_msg2', 'block_f2_gestione_risorse').'\',\''.$anno_formativo.'\')',
					'continuelabel' => ''.get_string('conferma','block_f2_prenotazioni').'',
					'cancellabel' => ''.get_string('annulla','block_f2_prenotazioni').''));
			$buttondbdg->add_action($actiondbdg);
			
			$table_buttons->data[] = array(
					$OUTPUT->render($buttondreopen),
	// 				$OUTPUT->render($buttonda),
	// 				$OUTPUT->render($buttondclose),
					$OUTPUT->render($buttondbdg)
			);
		}
		else //verifica budget già verificata, si può solo riaprire
		{
			$table_buttons->data[] = array(
					$OUTPUT->render($buttondreopen));
		}
	}
	else 
	{
		// bottone valida tutta la direzione
		$buttondc = new single_button(new moodle_url('manage_validazione_dir_all.php', $param_all_next),
				get_string('validazione_dir_all', 'block_f2_prenotazioni'));
		$actiondc = new component_action('click','M.util.show_confirm_dialog',array(
				'message' => ''.get_string('confirm_validazioni_dir_all', 'block_f2_prenotazioni').'',
				'callback' =>'checkAnomalieValidazioniSettori(\''.$settore_id.'\',\''.get_string('anomalie_validaz_sett', 'block_f2_prenotazioni').'\')',
				'continuelabel' => ''.get_string('conferma','block_f2_prenotazioni').'',
				'cancellabel' => ''.get_string('annulla','block_f2_prenotazioni').''));
		$buttondc->add_action($actiondc);
		// bottone annulla validazioni tutta la direzione
		$buttonda = new single_button(new moodle_url('manage_validazione_dir_del_all.php', $param_all_next),
				get_string('validazione_dir_del_all', 'block_f2_prenotazioni'));
		$actionda = new component_action('click','M.util.show_confirm_dialog',array(
				'message' => ''.get_string('confirm_validazione_dir_del_all', 'block_f2_prenotazioni').'',
				// 		'callback' =>'confirm_cancella_tutti(\''.get_string('confirm_msg2', 'block_f2_gestione_risorse').'\',\''.$anno_formativo.'\')',
				'continuelabel' => ''.get_string('conferma','block_f2_prenotazioni').'',
				'cancellabel' => ''.get_string('annulla','block_f2_prenotazioni').''));
		$buttonda->add_action($actionda);
		//bottone chiudi validazioni direzione
		$buttondclose = new single_button(new moodle_url('manage_validazione_dir_close.php', $param_all_next),
				get_string('validazione_dir_close', 'block_f2_prenotazioni'));
		$actiondclose = new component_action('click','M.util.show_confirm_dialog',array(
				'message' => ''.get_string('confirm_validazione_dir_close', 'block_f2_prenotazioni').'',
				// 		'callback' =>'confirm_cancella_tutti(\''.get_string('confirm_msg2', 'block_f2_gestione_risorse').'\',\''.$anno_formativo.'\')',
				'continuelabel' => ''.get_string('conferma','block_f2_prenotazioni').'',
				'cancellabel' => ''.get_string('annulla','block_f2_prenotazioni').''));
		$buttondclose->add_action($actiondclose);
		if ($num_validazioni_inconsistenti > 0)
		{
			$table_buttons->data[] = array(
					$OUTPUT->render($buttondc),
					$OUTPUT->render($buttonda),
// 					$OUTPUT->render($buttondclose)
			);
		}
		else
		{
			$table_buttons->data[] = array(
					$OUTPUT->render($buttondc),
					$OUTPUT->render($buttonda),
					$OUTPUT->render($buttondclose)
			);
		} 
	}
	return $table_buttons;
}

function get_tipi_budget_by_descrizione($budget_desc)
{
	global $DB;
	if (is_null($budget_desc) or !isset($budget_desc) or empty($budget_desc))
		return null;
	else 
	{
		$tipi_budget_sql = "select tb.id, tb.descrizione
			from {f2_tipo_pianificazione} tb
			where tb.stato = 'a' ";
		$where = '';
		if (preg_match('/on(\S)?line/i', $budget_desc) === 1)
		{
			$where .= " and (lower(tb.descrizione) like lower('%online%')
				or lower(tb.descrizione) like lower('%on-line%'))";
		}
		else if((preg_match('/aula/i', $budget_desc) === 1)
				or (preg_match('/programmat/i', $budget_desc) === 1))
		{
			$where .= " and lower(tb.descrizione) like lower('%aula%')";
		}
		else if(preg_match('/non(\s)?pianificat/i', $budget_desc) === 1)
		{
			$where .= " and lower(tb.descrizione) like lower('%non pianificato%')";
		}
		else if(preg_match('/lingua/i', $budget_desc) === 1)
		{
			$where .= " and lower(tb.descrizione) like lower('%lingua%')";
		}
		else if(preg_match('/individual/i', $budget_desc) === 1)
		{
			$where .= " and lower(tb.descrizione) like lower('%individuali%')";
		}
		else if(preg_match('/ob(b)?iettivo/i', $budget_desc) === 1)
		{
			$where .= " and lower(tb.descrizione) like lower('%obiettivo%')";
		}
		else $where = " and 1<>1 ";
		
		$tipi_budget_sql .= $where;
		return $DB->get_records_sql($tipi_budget_sql);
	}
}

function get_tipi_budget_str_by_descrizione($budget_desc='')
{
	$ret_str = '';
	if (is_null($budget_desc) or !isset($budget_desc) or empty($budget_desc))
		return '-1';
	else 
	{
		$tipi_budget = get_tipi_budget_by_descrizione($budget_desc);
		foreach ($tipi_budget as $tb)
		{
			$ret_str .= ','.$tb->id;
		}
		$ret_str = trim($ret_str,',');
                if (empty($ret_str)) return '-1';
		return $ret_str;
	}
}

function get_budget_totali_per_direzione($direzione,$anno,$descrizione_budget='')
{
	global $DB;
	if (is_null($direzione) or !isset($direzione) or empty($direzione))
		return null;
	else
	{
		if (is_null($anno) or !isset($anno) or empty($anno))
		{
			$anno = get_anno_formativo_corrente();
		}
		$id_tipi_bdg_str = '';
		if ($descrizione_budget !== '')
		{
			$id_tipi_bdg_str = get_tipi_budget_str_by_descrizione($descrizione_budget);
			$id_tipi_bdg_str = ' and b.tipo in ('.$id_tipi_bdg_str.')';
		}
		
		$sql_str = "SELECT * FROM {f2_org_budget} b
					where b.anno = ".$anno." and b.orgfk = ".$direzione.$id_tipi_bdg_str;
		return $DB->get_records_sql($sql_str);
	}
}

function get_settori_figli_id_str($direzione)
{
	$settori_figli = get_settori_by_direzione($direzione);
	$all_sett_arr = array();
	foreach ($settori_figli as $sfid)
	{
		$all_sett_arr[] = $sfid->id;
	}
	$str = implode(',',$all_sett_arr);
	return $str;
}

function get_valori_bdg_validati($direzione,$anno,$budget_desc)
{
	global $DB;
	if (is_null($direzione) or !isset($direzione) or empty($direzione)
		or is_null($budget_desc) or !isset($budget_desc) or empty($budget_desc))
	{
		return null;
	}
	if (is_null($anno) or !isset($anno) or empty($anno))
	{
		$anno = get_anno_formativo_corrente();
	}
	$all_settori_per_bdg_str = $direzione.','.get_settori_figli_id_str($direzione);
	$all_settori_per_bdg_str = trim($all_settori_per_bdg_str, ',');
	
	$tipi_budget_str = get_tipi_budget_str_by_descrizione($budget_desc);

	$sql_bdg_validati = "select sum(p.costo) as costo_tot
			,sum(p.durata) as durata_tot
			from {f2_prenotati} p, {f2_anagrafica_corsi} ac
			where p.isdeleted = 0 and p.orgid in (".$all_settori_per_bdg_str.")
			and p.anno = ".$anno." and p.validato_dir = 1
			and ac.tipo_budget in (".$tipi_budget_str.")
			and ac.courseid = p.courseid";
	return $DB->get_record_sql($sql_bdg_validati);
}

function update_stato_validazione_dir_budget($direzione,$anno=null,$nuovo_stato='D')
{
	if (is_null($anno) or !isset($anno) or empty($anno))
	{
		$anno = get_anno_formativo_corrente();
	}
	if (!is_null($direzione) and isset($direzione) and !empty($direzione))
	{
		$stato_val_precedente = get_stato_validazione_by_dominio($direzione,$anno);
		if ($stato_val_precedente->stato_validaz_dir == 'C')
		{
			$stato_globale_validazioni_direz = new stdClass;
			$stato_globale_validazioni_direz->anno = $anno;
			$stato_globale_validazioni_direz->nome_stato = 'stato_validaz_dir';
			$stato_globale_validazioni_direz->nuovo_stato = $nuovo_stato;
			$stato_globale_validazioni_direz->dominio = $direzione;
			update_stati_validazioni_globali($stato_globale_validazioni_direz);
		}
	}
}

function get_tipi_budget_corsi_prenotabili_str()
{
	//solo aula e online
	$str_aula = get_tipi_budget_str_by_descrizione('aula');
	$str_online = get_tipi_budget_str_by_descrizione('online');
	return $str_aula.','.$str_online;
}

function get_extra_budget()
{
	global $DB;
	$select_str = "select p.* from {f2_parametri} p ";
	$where_str = " where p.id = 'p_f2_extra_budget' ";
	$sql = $select_str.$where_str;
	$extra_budget = $DB->get_record_sql($sql);
	$val = 0;
	if (!is_null($extra_budget->val_int)) $val = $extra_budget->val_int;
	else $val = $extra_budget->val_float;
	return $val;
}