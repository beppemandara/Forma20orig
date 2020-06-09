<?php
//$Id: core.php 1357 2015-01-26 15:29:58Z l.moretto $

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/local/f2_support/lib.php');
require_once($CFG->dirroot.'/f2_lib/management.php');
require_once($CFG->dirroot.'/local/f2_course/elenco_corsi_programmati/filters/lib.php');
require_once($CFG->dirroot.'/f2_lib/constants.php');

/**
 * Restituisce i dati relativi all'utente
 * @param int $userid
 * return array of object
 */
function get_user_data($userid=NULL) {
	global $DB,$USER;

	if(is_null($userid))
		$userid=intval($USER->id);
	
	$select_str ="	SELECT u.id, u.lastname, u.firstname, u.idnumber ";
	$from_str = " from {user} u ";
	$where_str = " where u.id = ".$userid." ";
	
	if ($userid !==2) //per escludere admin
	{
		$select_str .= " ,uid.data as category ";
		$from_str .= " ,{user_info_field} uif, {user_info_data} uid ";
		$where_str .= " and uif.shortname LIKE 'category' and uif.id = uid.fieldid AND uid.userid = u.id ";
	}
	
	$sqlstr = $select_str.$from_str.$where_str;
	$user_data = $DB->get_record_sql($sqlstr);
	return $user_data;
}

/**
 * Funzione che converte un oggetto in un array utilizzato nelle select.
 * Prima coppia ("","--")
 * @global object $DB
 * @param object $obj
 * @param array $keyvalue Vettore delle proprietà da estrarre dell'oggetto
 * @return array Vettore di coppie chiave,valore 
 */
function from_obj_to_array_select($obj, $keyvalue) {
	global $DB;

	$array_select=array(""=>"--");
	foreach($obj as $row){
		$array_select[$row->$keyvalue[0]] = $row->$keyvalue[1];
	}

	return $array_select;
}

/**
 * @return int anno formativo corrente
 */
function get_anno_formativo_corrente()
{
//    global $CFG, $DB;
//    // se sono presenti dei corsi programmati restituisco l'anno maggiore, se no l'anno corrente vero e proprio
//    $sql = "SELECT MAX(anno) as corrente FROM {$CFG->prefix}f2_anagrafica_corsi WHERE course_type = ".C_PRO;
//    $anno = $DB->get_record_sql($sql);
//    if (is_null($anno->corrente))
//        return intval(date('Y'));
//    else
//        return $anno->corrente;
    // AK-LM: CR Gestione anno formativo (vedi doc "Modifica gestione anno corsi Forma 2.0.doc")
    return intval(date('Y'));
}

/*
 * Restituisce tutte le Coorti e Macrocategorie associate ad una Posizione Economica
 * n.b. potrebbero esistere Coorti non legate a Categorie  (= Posizione Economica)
 * @return array di oggetti sdtClass(cohortid, macrocategory)
 */
function get_cohort_from_macrocategory() {
	global $DB;	
	return $DB->get_records_sql('SELECT cohortid, macrocategory FROM {f2_posiz_econom_qualifica} GROUP BY cohortid, macrocategory');
}

/*
 * Associa l'utente $USER alla Coorte
 * @return (boolean) True o False che identifica il corretto inserimento
 */
function create_user_cohort_membership() {
	global $DB, $USER, $CFG;
	
	require_once $CFG->wwwroot.'/f2_lib/lib.php';
	
	$object = new stdClass();
	$object->cohortid = get_cohort_from_category($USER->category);
	$object->userid = $USER->id;
	$object->timeadded = time();
	
	return $DB->insert_record('cohort_members', $object);
}

/*
 * Restituisce l'oggetto Coorte legato al Profilo Commerciale di un utente
 * @ param $userid � l'id dell'utente, se non specificato � dell'utente loggato
 * @ return oggetto stdClass della Coorte, NULL se l'utente non � associato a nessuna Coorte
 */
function get_user_cohort_by_category($userid = null) {
	global $DB, $USER;
	
	$who = isset($userid) ? $userid : intval($USER->id);
	$cohort = $DB->get_record_sql('
			SELECT 
				cm.*
			FROM 
				{user_info_field} uif,
				{user_info_data} uid,
				{f2_posiz_econom_qualifica} f2_peq,
				{cohort_members} cm
			WHERE 
				uif.shortname LIKE "category" AND 
				uif.id = uid.fieldid AND 
				uid.userid = '.$who.' AND 
				uid.data = f2_peq.codqual AND 
				f2_peq.cohortid = cm.cohortid AND 
				cm.userid = '.$who.' 
			GROUP BY 
				cm.id');

	return ($cohort) ? $cohort : NULL;
}

/*
 * Restituisce tutte le Coorti di un utente
 * @param $userid � l'id dell'utente, se non specificato � quello dell'utente loggato
 * @return oggetto stdClass contenente un array di Coorti, NULL se l'utente non appartiene a nessuna Coorte
 */
function get_all_user_cohorts($userid = null) {
	global $DB, $USER;
	
	$who = isset($userid) ? $userid : intval($USER->id);
	$cohorts = $DB->get_records('cohort_members', array('userid' => $who));
	
	return ($cohorts) ? $cohorts : NULL;
}

/*
 * Restituisce la data dell'ultima modifica dei dati utente dello storico corsi
 * @param matricola dell'utente
 * @return data dell'ultima modifica
 */
function get_user_last_update($matricola) {
	global $DB;
	return $DB->get_record_sql("SELECT MAX(lstupd) as lastupdate FROM {f2_storico_corsi} WHERE matricola LIKE '".$matricola."'");
}

/*
 * Estrae il totale dei crediti per la categoria del dipendente
 * @param categoria del dipendente
 * @return totale crediti
 */
function get_user_totali_crediti($category) {
	global $DB;
	return $DB->get_field('f2_totali_crediti', 'cf_necessari', array('id' => $category));
}

/*
 * Estrae il totale crediti per ogni segmento formativo
 * @param categoria del dipendente
 * @return array di oggetti stdClass che sono coppie <segmento_formativo, totale_crediti>
 */
function get_user_crediti_for_settore($category) {
	global $DB;
	return $DB->get_records('f2_piani_di_studio', array('qualifica' => $category, 'stato' => 'A'), '', 'sf, crediti_richiesti');	
}

/**
 * Funzione che a seconda dell'anno (corrente o precedente) restituisce il totale crediti validati per l'utente
 * raggruppati per Segmento Formativo.
 * 16/5/2014 AK-LM: la categoria utente non viene presa in considerazione nel calcolo dei CFV.
 * @return array di oggetti stdClass che sono coppie <segmento_formativo, totale_crediti_validati>
 * @global object $DB
 * @param string $matricola Matricola dell'utente
 * @param string $tipoanno Valori: 'corrente'(default), o 'precendente'
 * @param object $dates
 * @param string $user_cat Categoria dell'utente. Deprecato
 * @return mixed Result set.
 */
function get_user_storico_crediti_attivi($matricola, $tipoanno = 'corrente', $dates, $user_cat) {
	global $DB;
	
	$datainizio = ($tipoanno == 'corrente') ? $dates->corrente_subtract_data : $dates->precedente_subtract_data;
	$datafine 	= ($tipoanno == 'corrente') ? $dates->timestamp_data_fine_corrente : $dates->timestamp_data_fine_precedente;
	$params 	= array('matricola' => $matricola, 
                        'dtiniziopiano' => $datainizio, 
                        'dtfinepiano' => $datafine
    );

    //AK-LM: "data_inizio > :dtiniziopiano" perchè dtiniziopiano è calcolato come dtfinepiano - 5 anni.
	return $DB->get_records_sql('
			SELECT 
				sf,
				SUM(cfv) as crediti_attivi
			FROM 
				{f2_storico_corsi}
			WHERE 
				matricola LIKE :matricola AND 
				data_inizio > :dtiniziopiano AND 
				data_inizio <= :dtfinepiano 
			GROUP BY sf', $params);	
}

/*
 * La funzione restituisce il numero di crediti utilizzabili dall'utente per il completamento del piano di studi.
 * Il valore è calcolato per ogni Segmento Formativo (SF) come il MINIMO valore tra i crediti attivi e i crediti richiesti dal piano,
 * ad eccezione del segmento SJ. Per tale SF i crediti utilizzabili sono calcolati come
 * MIN [crediti_attivi_SJ, (crediti_richiesti_SJ - SUM(crediti_attivi_SF/{SJ})]
 * @param matricola dell'utente, array $ca e $cp rispettivamente per i crediti attivi e quelli richiesti dal piano, divisi per SF
 * @return array di oggetti in cui ogni oggetto stdClass è una coppia <SF,crediti_utilizzabili>
 */
function get_user_storico_crediti_utilizzabili($matricola, $ca, $cp) {
	global $DB;
	
	$aSF = get_segmento_formativo(NULL);
	$cu = array();
	$caTotSFNotSJ = 0;
	$SJ = get_piano_studio_segmento_jolly();
	
	foreach ($aSF as $sf) {
		if ($sf->id != $SJ) {
			$objSF = new stdClass();
			$objSF->sf = $sf->id;
			
			if (array_key_exists($sf->id, $ca) && array_key_exists($sf->id, $cp)) {
				$objSF->crediti_utilizzabili = min($ca[$sf->id]->crediti_attivi, $cp[$sf->id]->crediti_richiesti);
				$caTotSFNotSJ += $objSF->crediti_utilizzabili;
			} else $objSF->crediti_utilizzabili = 0;
	
			$cu[$objSF->sf] = $objSF;
		}
	}
	
	$objSFSJ = new stdClass();
	$objSFSJ->sf = $SJ;
	$caSJ_attivi_val = 0;
	$cpSJ_richiesti_val = 0;
	if (isset($ca[$SJ]->crediti_attivi))
	{
		$caSJ_attivi_val = $ca[$SJ]->crediti_attivi;
	}
	if (isset($cp[$SJ]->crediti_richiesti))
	{
		$cpSJ_richiesti_val = $cp[$SJ]->crediti_richiesti;
	}
	
	$objSFSJ->crediti_utilizzabili = min($caSJ_attivi_val,($cpSJ_richiesti_val - $caTotSFNotSJ));
// 	$objSFSJ->crediti_utilizzabili = min($ca[$SJ]->crediti_attivi,($cp[$SJ]->crediti_richiesti - $caTotSFNotSJ));
	
	$cu[$objSFSJ->sf] = $objSFSJ;
	
	return $cu;
}

/*
 * Funzione che stampa la tabella del Piano di studi individuale
 * @param 	$lastupdate ultima data di aggiornamento dello storico per l'utente
 * 			$dates parametri relative alle date di fine piano
 * 			$cat_cf_necessari crediti formativi per la categoria
 * 			$aSF array di segmenti formativi
 * 			$aSF_cp array di elementi <segmento_formativo,crediti_richiesti_dal_piano>
 * 			$aSF_ca_cor array di elementi <segmento_formativo,crediti_attivi_data_corrente>
 * 			$aSF_ca_prec array di elementi <segmento_formativo,crediti_attivi_data_precedente>
 * 			$aSF_cu_cor array di elementi <segmento_formativo,crediti_utilizzabili_data_corrente>
 * 			$aSF_cu_prec array di elementi <segmento_formativo,crediti_utilizzabili_data_precedente>
 * @return html del piano di studi
 *			
 */
function table_piano_studi($lastupdate, $dates, $cat_cf_necessari, $aSF, $aSF_cp, $aSF_ca_cor, $aSF_ca_prec, $aSF_cu_cor, $aSF_cu_prec) {

	echo '<style type="text/css">';
	include 'style.css';
	echo '</style>';
	
	$table .= '';
	$table .= '<div id="piano_studi">';
	$table .= '<table width="80%" cellpadding=0 cellspacing=0>';
	$table .= '<tfoot>';
	$table .= '<tr>';
	$table .= '<td colspan="2" align="right"><span style="font-size:9px">Data di ultimo aggiornamento: '.date('d/m/Y',$lastupdate->lastupdate).'</span></td>';
	$table .= '</tr>';
	$table .= '</tfoot>';
	$table .= '<tbody>';
	$table .= '<tr>';
	$table .= '<td colspan="2" height="25" align="center"><strong>Piano di studi individuale</strong>';
	$table .= '</td>';
	$table .= '</tr>';
	$table .= '<tr>';
	$table .= '<td colspan = "2" align=center>';
	//$table .= '<table width="100%" cellpadding=0 cellspacing=0 name="t3">';
	$table .= '<table width="100%" cellpadding=0 cellspacing=0>';
	$table .= '<tr>';
	$table .= '<td rowspan="3" width="50%" align="center"><strong>Segmento Formativo</strong>';
	$table .= '</td>';
	$table .= '<td colspan="5" align="center"><strong>Crediti</strong>';
	$table .= '</td>';
	$table .= '</tr>';
	$table .= '<tr align=center>';
	$table .= '<td rowspan="2" valign="bottom" align="center" width="10%"><strong>Necessari</strong>';
	$table .= '</td>';
	$table .= '<td colspan="2"><strong>'.$dates->data_fine_precedente.'</strong>';
	$table .= '</td>';
	$table .= '<td colspan="2"><strong>'.$dates->data_fine_corrente.'</strong>';
	$table .= '</td>';
	$table .= '</tr>';
	$table .= '<tr align=center>';
	$table .= '<td width="10%"><strong>Attivi</strong>';
	$table .= '</td>';
	$table .= '<td width="10%"><strong>Utilizzabili</strong>';
	$table .= '</td>';
	$table .= '<td width="10%"><strong>Attivi</strong>';
	$table .= '</td>';
	$table .= '<td width="10%"><strong>Utilizzabili</strong>';
	$table .= '</td>';
	$table .= '</tr>';
	
	$sum_ca_prec = 0;
	$sum_cu_prec = 0;
	$sum_ca_cor = 0;
	$sum_cu_cor = 0;
	$SJ = get_piano_studio_segmento_jolly();

	// Ciclo sui Segmenti Formativi
	foreach ($aSF as $sf) {
        $cr = $aSF_cp[$sf->id]->crediti_richiesti;
        $ca_pre = !is_null($aSF_ca_prec[$sf->id]->crediti_attivi) ? $aSF_ca_prec[$sf->id]->crediti_attivi : 0;
        $ca_cor = !is_null($aSF_ca_cor[$sf->id]->crediti_attivi) ? $aSF_ca_cor[$sf->id]->crediti_attivi : 0;
        $cu_pre = !is_null($aSF_cu_prec[$sf->id]->crediti_utilizzabili) ? $aSF_cu_prec[$sf->id]->crediti_utilizzabili : 0;
        $cu_cor = !is_null($aSF_cu_cor[$sf->id]->crediti_utilizzabili) ? $aSF_cu_cor[$sf->id]->crediti_utilizzabili : 0;
		$star = (mb_strtolower($sf->id) === $SJ) ? '*' : '';
		$table .= '<tr align=center>';
		$table .= '<td height="25" align=left><strong>&nbsp;'.$sf->descrizione.'&nbsp;'.$star.'</strong></td>';
		$table .= '<td height="25"><strong><font color=#33399d>&nbsp;'.$cr.'&nbsp;'.$star.'</font></strong></td>';
		
		$table .= '<td><strong>&nbsp;'.$ca_pre.'&nbsp;'.$star.'</strong></td>';
		$sum_ca_prec += $ca_pre;
		
		$table .= '<td><strong>&nbsp;'.$cu_pre.'&nbsp;'.$star.'</strong></td>';
		$sum_cu_prec += $cu_pre;
		
		$table .= '<td><strong>&nbsp;'.$ca_cor.'&nbsp;'.$star.'</strong></td>';
		$sum_ca_cor += $ca_cor;
		
		$table .= '<td><strong>&nbsp;'.$cu_cor.'&nbsp;'.$star.'</strong></td>';
		$sum_cu_cor += $cu_cor;
		
		$table .= '</tr>';
	}
	
	$table .= '<tr align=center>';
	$table .= '<td height="25" align=left><strong>&nbsp;Totale</strong></td>';
	$table .= '<td height="25"><strong><font color=#33399d>&nbsp;'.$cat_cf_necessari.'&nbsp;</font></strong></td>';
	$table .= '<td><strong>&nbsp;'.$sum_ca_prec.'&nbsp;</strong></td>';
	$table .= '<td><strong>&nbsp;'.($sum_cu_prec > $cr ? $cr : $sum_cu_prec).'&nbsp;</strong></td>';
	$table .= '<td><strong>&nbsp;'.$sum_ca_cor.'&nbsp;</strong></td>';
	$table .= '<td><strong>&nbsp;'.($sum_cu_cor > $cr ? $cr : $sum_cu_cor).'&nbsp;</strong></td>';
	$table .= '</tr>';

	// Calcolo percentuale piano di studio
	$compX100ap = 0;
	$compX100ac = 0;
	$max_perc = 100.00;
	if ($cat_cf_necessari > 0) {
		$compX100ap = round((($sum_cu_prec / $cat_cf_necessari)*100),2);
		$compX100ac = round((($sum_cu_cor / $cat_cf_necessari)*100),2);
	}
	if ($compX100ap > $max_perc) 
	{
		$compX100ap = 100;
	}
	if ($compX100ac > $max_perc)
	{
		$compX100ac = 100;
	}
	
	$table .= '<tr align=center>';
	$table .= '<td colspan=6 align="left">* segmento utilizzabile per eventuale completamento crediti.</td>';
	$table .= '</tr>';
	$table .= '</table>';
	$table .= '</td>';
	$table .= '</tr>';
	$table .= '<tr><td colspan=2>&nbsp;';
	$table .= '</td></tr>';
	$table .= '<tr>';
	$table .= '<td height="25" width="50%"><strong>Percentuale Piano di Studi al '.$dates->data_fine_precedente.':</strong></td>';
	$table .= '<td bgColor=#fcfed1 align="left">&nbsp;&nbsp;&nbsp;<font color=#d50370><strong>'.$compX100ap.'%</strong></font></td>';
	$table .= '</tr>';
	$table .= '<tr>';
	$table .= '<td height="25" ><strong>Percentuale Piano di Studi al '.$dates->data_fine_corrente.':</strong></td>';
	$table .= '<td bgColor=#fcfed1 align="left">&nbsp;&nbsp;&nbsp;<font color=#d50370><strong>'.$compX100ac.'%</strong></font></td>';
	$table .= '</tr>';
	$table .= '<tr><td colspan=2>&nbsp;';
	$table .= '</td></tr>';
	$table .= '<tr>';
	$table .= '<td rowspan="2" colspan="2"><strong>Attenzione:</strong>&nbsp;nel calcolo dei Crediti e Percentuale Piano di Studi
				non sono considerati i corsi a cui il dipendente risulta iscritto, ma non ancora archiviati nello storico
				non iniziati, non terminati, ecc.).</td>';
	$table .= '</tr>';
	$table .= '</tbody>';
	$table .= '</table>';
	//$table .= '</tr>';
	//$table .= '</table>';
	$table .= '</div>';
	
	return $table;
}

/**
 * Funzione che restituisce tutti i corsi a cui l'utente è ISCRITTO o che ha COMPLETATO.
 * @param 	$userid (opzionale) dell'utente per cui si vogliono ricavare i corsi
 * 			$coursetype = {1,2}. 1 per i corsi OBBIETIVO, 2 per i corsi PROGRAMMATI 
 * 			$data contiene i dati necessari per paginare i risultati
 * 			$search (opzionale) array che contiene eventuali filtri sui corsi da ricavare
 *              $search_subcategory (opzionale) Contiene l'id della sotto categoria per il corso
 * @return  lista di corsi
 */
function get_mycourses($userid = NULL, $coursetype, $data, $search = array(), $search_year=-1, $search_subcategory='') {
	global $DB, $USER;

	$userid = is_null($userid) ? intval($USER->id) : $userid;
                                 
	$search_year_str = '';
	if ($search_year != -1)
	{
		$search_year_str = " AND f2_ac.anno = ".$search_year;
	}
        
    //Aggiunta discriminante sottocategoria corso
	$search_subcat_str = '';
	if ( $search_subcategory!='' )
	{
            $search_subcat_str = " AND course_cat.id =".$search_subcategory;
	}
	
    //$selectClause = "IF (EXISTS (SELECT cc.id FROM {course_completions} cc WHERE userid = ? AND c.id = cc.course), 1, 0) as userstatus";
    //AK-LM: se corso programmato estraggo il tipo di budget per dedurre la modalità didattica, altrimenti la categoria contenitore
	$selectClause = ($coursetype === C_PRO ? ", f2_ac.tipo_budget" : ", course_cat.name as subcat_name");
    $joinClause   = ($coursetype === C_PRO ? "" : "JOIN {course_categories} course_cat ON (c.category = course_cat.id)");
	$whereClause  = "f2_ac.course_type = ? AND ue.userid = ? AND ue.status = 0";
	
	$searchQuery = "";
	if (!empty($search)) {
		foreach ($search as $key => $value) {
			if (gettype($value) == 'string')
				$searchQuery .= "AND c.".$key." LIKE '%".$value."%' ";
			else 
				$searchQuery .= "AND c.".$key." = ".$value." ";
		}
	}

// AK:DL Inserito nella select esterna "face.id_sig,"
// AK:DL Inserito nella select interna "fssi.id as id_sig,"
	
    $sql = "
    SELECT 
        face.id_sig,
        c.id,
        c.fullname,
        c.idnumber,
        face.timestart,
        face.timefinish
        {$selectClause}
    FROM 
        {f2_anagrafica_corsi} f2_ac 
    JOIN
        {course} c ON f2_ac.courseid = c.id 
    {$joinClause}
    JOIN 
        {enrol} e ON c.id = e.courseid 
    JOIN 
        {user_enrolments} ue ON e.id = ue.enrolid 
    JOIN 
    (	SELECT 
            fssi.id as id_sig,
            f.course,
            (select min(timestart) from mdl_facetoface_sessions_dates where sessionid = fs.id) as timestart,
            (select max(timefinish) from mdl_facetoface_sessions_dates where sessionid = fs.id) as timefinish
        FROM 
            {facetoface} f,
            {facetoface_sessions} fs,
            {facetoface_signups} fssi,
            {facetoface_signups_status} fsst
        WHERE 
            fssi.userid = ? AND
            fsst.superceded = 0 AND
            fsst.statuscode >= ? AND
            f.id = fs.facetoface AND 
            fs.id = fssi.sessionid AND 
            fssi.id = fsst.signupid
    ) as face
    ON face.course = c.id 				
    WHERE
      $whereClause $searchQuery $search_year_str $search_subcat_str
    ORDER BY {$data['column']} {$data['sort']}";
    
    $whereParams = array($userid, MDL_F2F_STATUS_BOOKED, $coursetype, $userid);

    // $DB->get_records_sql($sql, array $params=null, $limitfrom=0, $limitnum=0)
    return $DB->get_records_sql($sql, $whereParams, $data['page']*$data['perpage'], $data['perpage']);
    //return $DB->get_records_sql($sql, $whereParams);
}

/**
 * Funzione che restituisce il numero di tutti i corsi a cui l'utente risulta ISCRITTO o che ha COMPLETATO.
 * @param       $userid (opzionale) dell'utente per cui si vogliono ricavare i corsi
 *                      $coursetype = {1,2}. 1 per i corsi OBBIETIVO, 2 per i corsi PROGRAMMATI
 *                      $data contiene i dati necessari per paginare i risultati
 *                      $search (opzionale) array che contiene eventuali filtri sui corsi da ricavare
 *              $search_subcategory (opzionale) Contiene l'id della sotto categoria per il corso
 * @return  lista di corsi
 */
function get_mycourses_number($userid = NULL, $coursetype, $data, $search = array(), $search_year=-1, $search_subcategory='') {
    global $DB, $USER;
    $userid = is_null($userid) ? intval($USER->id) : $userid;
    $search_year_str = '';
    if ($search_year != -1) {
        $search_year_str = " AND f2_ac.anno = ".$search_year;
    }
    //Aggiunta discriminante sottocategoria corso
    $search_subcat_str = '';
    if ( $search_subcategory!='' ) {
        $search_subcat_str = " AND course_cat.id =".$search_subcategory;
    }
    //AK-LM: se corso programmato estraggo il tipo di budget per dedurre la modalita  didattica, altrimenti la categoria contenitore
    $selectClause = ($coursetype === C_PRO ? ", f2_ac.tipo_budget" : ", course_cat.name as subcat_name");
    $joinClause   = ($coursetype === C_PRO ? "" : "JOIN {course_categories} course_cat ON (c.category = course_cat.id)");
    $whereClause  = "f2_ac.course_type = ? AND ue.userid = ? AND ue.status = 0";
    $searchQuery = "";
    if (!empty($search)) {
        foreach ($search as $key => $value) {
            if (gettype($value) == 'string')
                $searchQuery .= "AND c.".$key." LIKE '%".$value."%' ";
            else
                $searchQuery .= "AND c.".$key." = ".$value." ";
        }
    }

    $sql = "SELECT COUNT(c.id) AS num  
            FROM {f2_anagrafica_corsi} f2_ac
            JOIN {course} c ON f2_ac.courseid = c.id
            {$joinClause}
            JOIN {enrol} e ON c.id = e.courseid
            JOIN {user_enrolments} ue ON e.id = ue.enrolid
            JOIN ( SELECT fssi.id as id_sig, f.course,
                   (select min(timestart) from mdl_facetoface_sessions_dates where sessionid = fs.id) as timestart,
                   (select max(timefinish) from mdl_facetoface_sessions_dates where sessionid = fs.id) as timefinish
                   FROM {facetoface} f, {facetoface_sessions} fs, {facetoface_signups} fssi, 
                   {facetoface_signups_status} fsst
                   WHERE fssi.userid = ? AND fsst.superceded = 0 AND fsst.statuscode >= ? AND
                   f.id = fs.facetoface AND fs.id = fssi.sessionid AND fssi.id = fsst.signupid
                 ) as face ON face.course = c.id
            WHERE $whereClause $searchQuery $search_year_str $search_subcat_str";
    
    $whereParams = array($userid, MDL_F2F_STATUS_BOOKED, $coursetype, $userid);
    $results_count = $DB->count_records_sql($sql, $whereParams);
    return $results_count;
/*
    $datisql = new stdClass;
    $datisql->query  = $sql;
    $datisql->params = $whereParams;
    $datisql->num = $results_count;
    return $datisql;
*/
}

/*
 * Funzione che salva l'assegnazione di un dominio di appartenenza per un utente in fase di import degli utenti (tramite csv)
 * @param $user stdClass contiene i dati dell'utente, ovvero la singola riga del file csv per l'importazione massiva
 * @param $existinguser stdClass contiene i dati dell'eventuale utente esistente, ovvero gi� presente nel database (rientra nel caso di aggiornamento utente)
 * return true se avviene correttamente l'inserimento o l'aggiornamento, false se avviene un errore
*/
function save_organization_assignment($user, $existinguser, &$upt, &$userserrors) {
    global $DB, $USER, $CFG;

    try {
        if (isset($user->orgidnumber) && !is_null($user->orgidnumber) && $user->orgidnumber != '') { // � stato specificato l'id number del dominio di appartenenza dell'utente
            
            $organizationid = $DB->get_field('org', 'id', array('idnumber' => $user->orgidnumber));

            $assignmentnew->userid = $user->id;
            $assignmentnew->organisationid = $organizationid;
            $assignmentnew->viewableorganisationid = NULL;
            $assignmentnew->timemodified = time();
            $assignmentnew->usermodified = intval($USER->id);

            if ($existinguser) {
                // l'utente esiste gi�, verifico se sia gi� presente un'assegnazione per questo utente
                $assignment = $DB->get_record_sql("SELECT * FROM {$CFG->prefix}org_assignment WHERE userid = {$user->id}");
                if ($assignment) {
                    // esiste: proseguo con l'aggiornamento, ma solo se l'id del dominio � cambiato
//                    print_r('</br>esiste gi� un\'assegnazione: UPDATE');
                    if ($assignment->organisationid != $assignmentnew->organisationid) {
//                          print_r('</br>il dominio � cambiato');
                        $assignmentnew->id = $assignment->id;
                        if (!$DB->update_record('org_assignment', $assignmentnew)) {
                            $upt->track('status', 'Error updating assignment', 'error');
                            $userserrors++;
                            return false;
                        }
                    }
                } else {
                    // non esiste: aggiungo l'assegnazione
//                    print_r('</br>non esiste un\'assegnazione: INSERT');
                    if (!$DB->insert_record('org_assignment', $assignmentnew)) {
                        $upt->track('status', 'Error creating new assignmentt', 'error');
                        $userserrors++;
                        return false;
                    }
                }
            } else {
                // l'utente � nuovo, non � necessario il controllo di pre-esistenza della assegnazione
//                print_r('</br>l\'utente � nuovo: INSERT');
                if (!$DB->insert_record('org_assignment', $assignmentnew)) {
                    $upt->track('status', 'Error creating new assignmentt', 'error');
                    $userserrors++;
                    return false;
                }
            }
        }

        if (isset($user->vieworgidnumber) && !is_null($user->vieworgidnumber) && $user->vieworgidnumber != '') { // � stato specificato l'id number del dominio di visibilit� dell'utente
            $viewableorganizationid = $DB->get_field('org', 'id', array('idnumber' => $user->vieworgidnumber));

            $assignmentnew->userid = $user->id;
            $assignmentnew->viewableorganisationid = $viewableorganizationid;
            $assignmentnew->timemodified = time();
            $assignmentnew->usermodified = intval($USER->id);

            // verifico se sia gi� presente un'assegnazione per questo utente
            $assignment = $DB->get_record_sql("SELECT * FROM {$CFG->prefix}org_assignment WHERE userid = {$user->id}");
            if ($assignment) {
                // esiste: proseguo con l'aggiornamento, ma solo se l'id del dominio � cambiato
                if ($assignment->viewableorganisationid != $assignmentnew->viewableorganisationid) {
                    $assignmentnew->id = $assignment->id;

                    if (!$DB->update_record('org_assignment', $assignmentnew)) {
                        $upt->track('status', 'Error updating assignment', 'error');
                        $userserrors++;
                        return false;
                    }
                }
            }
        }
        return true;
    } catch (Exception $e) {
        $upt->track('status', 'Assegnazione dominio - '.$e->getMessage(), 'error');
        $userserrors++;
        return false;
    }
}

/*
 * Per una data sessione, la funzione restituisce TRUE o FALSE a seconda che questa sia aperta o chiusa
 * @param $session è l'id della sessione di cui si vuole sapere lo stato
 * @return TRUE se la sessione è aperta, FALSE se risulta chiusa
 */
function sessione_aperta($session) {
	global $DB;
	return ($DB->get_field_sql("SELECT IF(stato ='a', 1, 0) as stato FROM {f2_sessioni} WHERE id = $session")) ? true : false;
}

function assegnazioni_date_scuola_aperte()
{
	global $DB;
	$aperto = $DB->get_field('f2_stati_funz', 'aperto', array('id' => 'assegnaz_date_scuola'), MUST_EXIST);
	if ($aperto == 's')
            return true;
	else
            return false;
}

function prenotazioni_aperte()
{
	global $DB;
	$sqlstr = "select count(id) as num_aperte from {f2_stati_funz} f where f.id like '%prenota%' and f.aperto = 's'";
	$n = $DB->get_record_sql($sqlstr);
	$num_aperte = intval($n->num_aperte);
	if ($num_aperte > 0) return true;
	else return false;
}

function prenotazioni_dip_aperte()
{
	global $DB;
	$sqlstr = "select count(id) as num_aperte from {f2_stati_funz} f where f.id = 'prenota_dip' and f.aperto = 's'";
	$n = $DB->get_record_sql($sqlstr);
	$num_aperte = intval($n->num_aperte);
	if ($num_aperte > 0) return true;
	else return false;
}

function prenotazioni_direzione_aperte()
{
	global $DB;
	$sqlstr = "select count(id) as num_aperte from {f2_stati_funz} f where f.id = 'prenota_direzione' and f.aperto = 's'";
	$n = $DB->get_record_sql($sqlstr);
	$num_aperte = intval($n->num_aperte);
	if ($num_aperte > 0) return true;
	else return false;
}

function get_sedi_corso()
{
	global $DB;
	$sedi_corsi_sql = "select s.id, s.descrizione as citta from {f2_sedi} s ";
	$sedi_corsi_sql .= " order by s.progr_displ";
	$return = $DB->get_records_sql($sedi_corsi_sql);
	return $return;
}

function get_sedi_from_corso($idcorso)
{
	global $DB;
	$sedi_corsi_sql = "select s.id, s.descrizione as citta from {f2_corsi_sedi_map} sedi_map,{f2_sedi} s";
	$sedi_corsi_sql .= " where  sedi_map.courseid = $idcorso AND sedi_map.sedeid = s.id";
	$sedi_corsi_sql .= " order by s.progr_displ";
	$return = $DB->get_records_sql($sedi_corsi_sql);
	return $return;
}

/**
* Searches other users and returns paginated results
*
* @global moodle_database $DB
* @param string $search
* @param bool $searchanywhere
* @param int $page Starting at 0
* @param int $perpage
* @return array
*/
function my_search_other_users($search='', $searchanywhere=false, $contextId, $page=0, $perpage=25) {
   global $DB, $CFG;

   // Add some additional sensible conditions
   $tests = array("tmp.id <> :guestid", 'tmp.deleted = 0', 'tmp.confirmed = 1');
   $params = array('guestid'=>$CFG->siteguest);
   if (!empty($search)) {
       $conditions = array('tmp.firstname','tmp.lastname');
       if ($searchanywhere) {
           $searchparam = '%' . $search . '%';
       } else {
           $searchparam = $search . '%';
       }
       $i = 0;
       foreach ($conditions as $key=>$condition) {
           $conditions[$key] = $DB->sql_like($condition, ":con{$i}00", false);
           $params["con{$i}00"] = $searchparam;
           $i++;
       }
       $tests[] = '(' . implode(' OR ', $conditions) . ')';
   }
   $wherecondition = implode(' AND ', $tests);

   $fields      = 'SELECT '.user_picture::fields('u', array('username','lastaccess')).', IFNULL(org.fullname, \''.get_string('no_viewable_org', 'local_f2_domains').'\') as viewable_org_fullname, u.deleted as deleted, u.confirmed as confirmed';
   $countfields = 'SELECT COUNT(tmp.id)';
/*   $sql   = " FROM {user} u
                JOIN {$CFG->prefix}org_assignment oa ON u.id=oa.userid
                JOIN {$CFG->prefix}org org ON org.id=oa.viewableorganisationid 
             WHERE $wherecondition
                AND org.depthid < 4";*/
   
  $sql = "
   		FROM
   			(
   				  (
   					".$fields."
   					FROM 
   						{user} u
                		JOIN {$CFG->prefix}org_assignment oa ON u.id=oa.userid
                		JOIN {$CFG->prefix}org org ON org.id=oa.viewableorganisationid 
             		WHERE 
             			org.depthid < 4
				 )
				 UNION
				 (
				 	".$fields."
				 	FROM
					   {org} org,
					   {org_assignment} oa,
					   {user} u
				 	WHERE 
             		     org.id = oa.organisationid 
             		 AND oa.userid = u.id 
             		 AND(
						   org.path REGEXP '/24/' OR
						   org.path REGEXP '/24$'
					   	)
				)
		) AS tmp WHERE ".$wherecondition."";
   
   $select = "SELECT * ";
   $order = ' ORDER BY lastname ASC, firstname ASC';

   $params['contextid'] = $contextId;
   
   $availableusers = $DB->get_records_sql($select . $sql . $order, $params, $page*$perpage, $perpage);
   $totalusers = $DB->count_records_sql($countfields . $sql, $params);
   return array('totalusers'=>$totalusers, 'users'=>$availableusers);
}

/**
 * Rimuove tutte le assegnazioni degli utenti ad un corso (MANTENENDO LE SOLE ASSEGNAZIONI CON RUOLO STUDENTE)
 *
 * @param string $context_id l'id del contesto relativo al corso
 * @return void
 */
function pulisci_altri_utenti_da_corso($context_id) {
    global $USER, $CFG, $DB;

    if (!$context_id) {
        throw new coding_exception('Missing parameters in role_unsassign_all() call');
    }
    
    // ottengo gli id dei ruoli Supervisore, Supervisore di secondo livello e Referente formativo
    $parametri_id_ruoli = get_parametri_by_prefix('p_f2_id_ruolo_');
    $id_ruoli_array = array();
	
    foreach ($parametri_id_ruoli as $param) {
        $id_ruoli_array[] = $param->val_int;
    }
	
    $id_ruoli = implode(', ', $id_ruoli_array);

    $ras = $DB->get_records_sql("SELECT * FROM {$CFG->prefix}role_assignments
        WHERE contextid = $context_id AND roleid IN ($id_ruoli)");
    
    foreach($ras as $ra) {
        $DB->delete_records('role_assignments', array('id'=>$ra->id));
        if ($context = context::instance_by_id($ra->contextid, IGNORE_MISSING)) {
            // this is a bit expensive but necessary
            $context->mark_dirty();
            /// If the user is the current user, then do full reload of capabilities too.
            if (!empty($USER->id) && $USER->id == $ra->userid) {
                reload_all_capabilities();
            }
        }
        events_trigger('role_unassigned', $ra);
    }
    unset($ras);
}

function print_form_dipendenti($nextpage='/', $next_param_array=array(),$page=0,$perpage=10,$column=1,$sort='ASC',$form_id='mform1')
{
	$next_param_str = '';
	foreach($next_param_array as $next_param_key => $next_param_val)
	{
		$next_param_str .= '&amp;'.$next_param_key.'='.$next_param_val;
	}

	//INIZIO Form
	class ricerca_dipendenti_form extends moodleform {
		public function definition() {
			$mform =& $this->_form;
			$post_values = $this->_customdata['post_values'];
			if (isset($post_values) and (!is_null($post_values)) and (!empty($post_values)))
			{
				$post_values = json_encode($post_values);
				$mform2->addElement('hidden', 'post_values',$post_values);
			}
			$mform->addElement('text', 'search_name','Cognome', 'maxlength="254" size="50"');
			$mform->addElement('submit', 'submitbtn', 'Cerca');
		}
	}
	
	$mform = new ricerca_dipendenti_form(null);
	$mform->display();
	//FINE Form

	$data = $mform->get_data();

	$pagination = array('perpage' => $perpage, 'page'=>$page,'column'=>$column,'sort'=>$sort);
	foreach ($pagination as $key=>$value)
	{
		$data->$key = $value;
	}
	
	$post_extra=array('column'=>$column,'sort'=>$sort);
	// $full_dipendenti restituisce tutti i dipendenti
	// $full_dipendenti = get_visible_users_by_userid(NULL, $data, TRUE);
	$full_dipendenti = get_visible_users_by_userid(NULL, $data, FALSE); //include se stesso
	$dipendenti = $full_dipendenti->dati;
	$total_rows = $full_dipendenti->count;
	
	if (!(is_null($total_rows) or $total_rows == 0))
	{
		// TABELLA DIPENDENTI
		$table = new html_table();
		$table->width = '80%';
		$head_table = array('lastname','firstname','visualizza');
		$head_table_sort = array('lastname');
		$align = array ('center','center','center');
		$table->align = $align;
		$table->head = build_head_table($head_table,$head_table_sort,$post_extra,$total_rows, $page, $perpage, $form_id);
		
		foreach ($dipendenti as $c){
			$table->data[] = array(
					$c->lastname,
					$c->firstname,
					"<a href='".$nextpage."?userid=".$c->id.$next_param_str."'>".get_string('visualizza','local_f2_traduzioni')."</a>"
			);
		}
		
		echo "<p>Totale utenti: $total_rows</p>";
		$paging_bar = new paging_bar_f2($total_rows, $page, $perpage, $form_id, $post_extra);
		echo $paging_bar->print_paging_bar_f2();
		echo html_writer::table($table);
		echo $paging_bar->print_paging_bar_f2();
		///FINE TABELLA DIPENDENTI
	}
}

/**
 * Stampa i corsi programmati per l'anno formativo in corso in homepage
 */
function print_corsi_programmati() {
    global $CFG, $OUTPUT;

    $cfiltering = new my_courses_filtering();
    $courses = $cfiltering->get_my_courses_listing();
    $cont = 1;

    if ($courses) {
        echo html_writer::start_tag('ul', array('class'=>'unlist'));
        foreach ($courses as $course) {
            if ($cont < 5) { // limito il numero di corsi visualizzati in home page
                $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
                if ($course->visible == 1 || has_capability('moodle/course:viewhiddencourses', $coursecontext)) {
                    echo html_writer::start_tag('li');
                    print_corso_programmato($course);
                    echo html_writer::end_tag('li');
                }
                $cont++;
            } else {
                break;
            }
        }
        echo html_writer::end_tag('ul');
        echo html_writer::tag('a', get_string('viewallcorsiprogrammati','local_f2_traduzioni'), array('href' => 'local/f2_course/elenco_corsi_programmati/elenco_corsi_programmati.php'));
    } else {
        echo $OUTPUT->heading(get_string("nocoursesyet"));
    }
}

/**
 * Print a description of a course, suitable for browsing in a list.
 *
 * @param object $course the course object.
 * @param string $highlightterms (optional) some search terms that should be highlighted in the display.
 */
function print_corso_programmato($course, $highlightterms = '') {
    global $CFG, $USER, $DB, $OUTPUT;

    $context = get_context_instance(CONTEXT_COURSE, $course->id);

    // Rewrite file URLs so that they are correct
    $course->summary = file_rewrite_pluginfile_urls($course->summary, 'pluginfile.php', $context->id, 'course', 'summary', NULL);

    echo html_writer::start_tag('div', array('class'=>'coursebox clearfix'));
    echo html_writer::start_tag('div', array('class'=>'info'));
    echo html_writer::start_tag('h3', array('class'=>'name'));
    $url = get_url_scheda_progetto($course->id);
    if ($url) {
        $pdf = "<a href=\"$url\"><img src=\"{$CFG->wwwroot}/pix/f/pdf.gif\" class=\"icon\" title=\"Scarica scheda progetto\" /></a> ";
    } else {
        $pdf = "";
    }
	
    //$coursename = $course->idnumber.' - '.get_course_display_name_for_list($course);
    $coursename = get_course_display_name_for_list($course);
    
    // START NEW
    $obiettivo_sp_tot = get_obiettivo_corso($course);
    $estrattoTot = wordwrap($obiettivo_sp_tot, 150, "<br />\n");
    $estratto_array = explode("<br />", $estrattoTot);
    $obiettivo_sp = $estratto_array[0];
    // END NEW
    
    echo $coursename.' '.$pdf;
    echo html_writer::end_tag('h3');
    
    // START NEW
    echo html_writer::start_tag('p');
    echo $obiettivo_sp;
    echo html_writer::end_tag('p');
    // END NEW
    
    echo html_writer::end_tag('div'); // End of info div
    
    echo html_writer::start_tag('div');
    $options = new stdClass();
    $options->noclean = true;
    $options->para = false;
    $options->overflowdiv = true;
    if (!isset($course->summaryformat)) {
        $course->summaryformat = FORMAT_MOODLE;
    }
	
    echo highlight($highlightterms, format_text($course->summary, $course->summaryformat, $options,  $course->id));
    echo html_writer::end_tag('div'); // End of summary div
    echo html_writer::end_tag('div'); // End of coursebox div
}

/*
 * Funzione che restituisce tutti i corsi/edizioni gestibili dall'utente (referente di direzione e supervisore) a patto 
 * che l'iscrizione al corso sia aperta. Non c'è una discriminazione dei corsi visibili dal referente rispetto al supervisore 
 * in quanto il referente è trasversale a tutti i corsi (questo vale solo per i corsi PRG)
 * @param 	$coursetype (Corso Obbiettivo = 1, Corso Programmato = 2)
 * 			$years array che contiene l'informazione sui due anni formativi su cui effettuare la ricerca
 * 			$search (opzionale) array che contiene eventuali filtri sui corsi da ricavare
 * @return lista di corsi
 */
function get_managable_course($coursetype, $years, $search = array(), $search_year=-1) {
	global $DB, $USER;
// 	print_r($search);
	// Valida sempre
	//$whereClause = "f2_ac.course_type = ? AND f2_ac.anno IN (?,?,?)";	
	$whereClause = "f2_ac.course_type = ? AND f2_ac.anno IN (?,?,?,?,?)";	// 2018/01/04 mod x ins + anni
	
	//$whereParams = array($coursetype, $years[0], $years[1], $years[2]); // 2018/01/04 mod x ins + anni
	$whereParams = array($coursetype, $years[0], $years[1], $years[2], $years[3], $years[4]);
        $whereReferenteFormativo = "";
        $whereCourseProg = "";
        $corsi = get_corsi_referente_scuola();
        if ($corsi == false) {
            $whereReferenteScuola = "";
        } else {
            $whereReferenteScuola = " AND c.id IN ($corsi)";
        }
        
        // Solo per i ruoli Referenti di direzione, non per i supervisori
        $isDir = isReferenteDiDirezione($USER->id);
                
	// Solo per i Corsi Programmati
	if ($coursetype == C_PRO) {
		$fromCourseProg = "LEFT JOIN {f2_sessioni} f2_s ON   f2_s.id = f.f2session";
//		$whereCourseProg = "AND f2_s.stato LIKE 'a' AND f2_s.id = f.f2session";
		
		if (!$isDir) {
			$selectDirezione = "";
		} else {
			$direzione = get_direzione_utente($USER->id);
			$selectDirezione = ", (SELECT f2_epm.npostiassegnati
                                                FROM {facetoface_sessions} fsessions 
                                                JOIN {f2_edizioni_postiris_map} f2_epm ON fsessions.id = f2_epm.sessionid
                                                WHERE f2_epm.direzioneid = {$direzione['id']} AND fsessions.id = edition_id) as edition_seats_reserved,
                                              (SELECT f2_epm.nposticonsumati
                                                FROM {facetoface_sessions} fsessions 
                                                JOIN {f2_edizioni_postiris_map} f2_epm ON fsessions.id = f2_epm.sessionid
                                                WHERE f2_epm.direzioneid = {$direzione['id']} AND fsessions.id = edition_id) as edition_seats_booked ";
		}
	} else {
                if ($isDir) {
                    $corsi_dir = get_corsi_referente_direzione();
                    if ($corsi_dir == false) {
                        $whereReferenteFormativo = "";
                    } else {
                        $whereReferenteFormativo = " AND c.id IN ($corsi_dir)";
                    }
                }

		$fromCourseProg = "";
		$whereCourseProg = "";
		$selectDirezione = "";
	}
	
	$searchQuery = "";
	if (!empty($search)) {
		foreach ($search as $key => $value) {
			if (gettype($value) == 'string')
				$searchQuery .= "AND c.".$key." LIKE '%".$value."%' ";
			else
				$searchQuery .= "AND c.".$key." = ".$value." ";
		}
	}
	
	$search_year_str = '';
	if ($search_year != -1)
	{
		$search_year_str = " AND f2_ac.anno = ".$search_year;
	}
    $q = "
		SELECT @rownum:=@rownum+1 as id, mngcrs.*
		FROM
			(SELECT				
				c.id as course_id,
				c.idnumber as course_code,
				c.fullname as course_title,
				f2_ac.anno as course_year,
				f.id as session_id,
				f.name as session_name,
				fs.id as edition_id,
				dates.timestart as edition_timestart,
				dates.timefinish as edition_timefinish,
				(SELECT GROUP_CONCAT(fsf.shortname, '=', fsdata.data SEPARATOR '&') as p
                                    FROM {facetoface_sessions} fsessions
                                    JOIN {facetoface_session_data} fsdata ON fsessions.id = fsdata.sessionid 
                                    JOIN {facetoface_session_field} fsf ON fsdata.fieldid = fsf.id 
                                    WHERE fsf.shortname IN ('anno', 'sede', 'indirizzo', 'editionum') AND fsessions.id = edition_id
                                ) as edition_customfields
				{$selectDirezione} ,
				'azione' as azione
			FROM
				{course} c
			LEFT JOIN
				{f2_anagrafica_corsi} f2_ac
			ON c.id = f2_ac.courseid
			LEFT JOIN
				{facetoface} f
			ON c.id = f.course
			LEFT JOIN
				{facetoface_sessions} fs
			ON f.id = fs.facetoface 
			LEFT JOIN 
				(
					SELECT 
						fsd.sessionid,
						MIN(fsd.timestart) as timestart,
						MAX(fsd.timefinish) as timefinish
					FROM 
						{facetoface_sessions_dates} fsd
					GROUP BY 
						fsd.sessionid
				) as dates 
			ON fs.id = dates.sessionid 
			{$fromCourseProg}
			WHERE 
				$whereClause $whereCourseProg $whereReferenteScuola $whereReferenteFormativo $searchQuery $search_year_str
			GROUP BY 
				c.id, f.id, fs.id
			order by course_code, session_name, edition_timestart, edition_customfields ASC ) 
			as mngcrs, (SELECT @rownum:=0) r";
//print_r($q);
	return $DB->get_records_sql($q, $whereParams);
}

/*
 * Funzione che a partire da un array di corsi/edizioni costruisce
 * un JSON formattato come segue:
 * [{course_code : x, course_title : x, course_year : x, 
 * 	kiddies:
 * 		[{indirizzo : x, anno : x, sede : x, editionum : x, course_code : x, edition_timestart : x, edition_timefinish : x, session_name : x},
 * 		{...}, {...}, ...]}, 
 * {...}, {...}, ...]
 * Il codice corso è replicato all'interno di ogni figlio (kiddies) del corso, ossia l'edizione,
 * in quanto questo JSON è utilizzato dal YUI gallery-treeble che scrive ogni riga nella datatable
 * usando come indice il course_code (se non valorizzato la riga dell'edizione non viene visualizzata)
 * @param  	$courses è un array in cui ogni entry contiene tutte le informazioni del corso/edizione,
 * 			$course_type (CORSO OBBIETTIVO o CORSO PROGRAMMATO)
 * @return JSON data	
 */
function json_course_parsing($courses, $course_type) {
	global $USER;
	
	$isDir = isReferenteDiDirezione($USER->id);
	
	$course_x_editions = array();

	if (!empty($courses)) {
		
		// init-phase
		$idx = 1;
		$refKey = $courses[$idx]->course_id;		
		$courseOBJ = course_editions_map_instance_course($courses[$idx]);

		foreach ($courses as $course) {	
			if ($refKey == $course->course_id) {
				array_push($courseOBJ->kiddies, course_editions_map_instance_edition($course, $course_type, $isDir));
			} else {
				array_push($course_x_editions, $courseOBJ);
				$courseOBJ = course_editions_map_instance_course($course);
				$refKey = $course->course_id;
				array_push($courseOBJ->kiddies, course_editions_map_instance_edition($course, $course_type, $isDir));
			}
		}
		array_push($course_x_editions, $courseOBJ);
	}
	return json_encode($course_x_editions);
}

/*
 * Costruisce l'oggetto corso nel formato necessario per essere codificato in JSON per lo YUI gallery-treeble
 * @param array contenente le informazioni sul corso
 * @return oggetto stdClass
 */
function course_editions_map_instance_course($coursedata) {
	global $CFG;
	
	$object = new stdClass();
	$object->course_code = "<a href=$CFG->wwwroot/course/view.php?id=$coursedata->course_id title='Vai al corso'>$coursedata->course_code</a>";
	$object->course_title = $coursedata->course_title;
	$object->course_year = $coursedata->course_year;
	$object->kiddies = array();
	return $object;
}

/*
 * Costruisce l'oggetto edizione nel formato necessario per essere codificato in JSON per lo YUI gallery-treeble
 * L'oggetto dovrà essere inserito nei kiddies del corso di riferimento
 * @param $editiondata (dati dell'edizione), $course_type (tipologia corso, in quanto il template
 * differisce a seconda del tipo di corso), $isDir (booleano, per capire se l'utente collegato
 * appartiene ad una direzione, in quanto il template che visualizza è diverso da quello di un supervisore)
 */
function course_editions_map_instance_edition($editiondata, $course_type, $isDir) {
	global $CFG,$USER;
	
	$context = context_course::instance($editiondata->course_id);
	
	$edition = array();
	
	parse_str($editiondata->edition_customfields, $edition);
        
        if (!isset($edition['editionum'])) {
            $edition['course_code'] = get_string('no_editions','block_f2_apprendimento');
        } else {
            if (has_capability('mod/facetoface:view', $context))
            //        $edition['editionum'] = "<a href=$CFG->wwwroot/mod/facetoface/attendees.php?s=$editiondata->edition_id title='Vai all&#8217;edizione' >".$edition['editionum']."</a>";
            //else 
                    $edition['editionum'] = $edition['editionum'];
	
            $edition['course_code'] = "";
        }
	$edition['edition_timestart'] = $editiondata->edition_timestart == 0 ? '' : date('d/m/Y',$editiondata->edition_timestart);
	$edition['edition_timefinish'] = $editiondata->edition_timefinish == 0 ? '' : date('d/m/Y',$editiondata->edition_timefinish);

	if(isSupervisore($USER->id)){
		$edition['azione'] = !isset($edition['editionum']) ? '': "<a href=$CFG->wwwroot/mod/facetoface/sessions.php?s=$editiondata->edition_id title='Assegnazione date' ><img src='$CFG->wwwroot/pix/c/event.gif' alt='Assegnazione date'></a> ";
		//$edition['azione'] .=!isset($edition['editionum']) ? '':  "<a href=$CFG->wwwroot/mod/facetoface/attendees.php?s=$editiondata->edition_id title='Iscrivi utenti' ><img src='$CFG->wwwroot/pix/i/users.gif' alt='Iscrivi utenti'></a>";
		$edition['azione'] .=!isset($edition['editionum']) ? '':  "<a href=$CFG->wwwroot/mod/facetoface/attendees.php?s=$editiondata->edition_id title='Iscrivi utenti' ><img src='$CFG->wwwroot/pix/i/users.png' alt='Iscrivi utenti'></a>";
	}else if(isReferenteScuola($USER->id)){
		if (($course_type == C_PRO) && assegnazioni_date_scuola_aperte()){
			$edition['azione'] = !isset($edition['editionum']) ? '': "<a href=$CFG->wwwroot/mod/facetoface/sessions.php?s=$editiondata->edition_id&m=1 title='Assegnazione date' ><img src='$CFG->wwwroot/pix/c/event.gif' alt='Assegnazione date'></a> ";
		}
		//$edition['azione'] .=!isset($edition['editionum']) ? '':  "<a href=$CFG->wwwroot/mod/facetoface/attendees.php?s=$editiondata->edition_id title='Iscrivi utenti' ><img src='$CFG->wwwroot/pix/i/users.gif' alt='Iscrivi utenti'></a>";
		$edition['azione'] .=!isset($edition['editionum']) ? '':  "<a href=$CFG->wwwroot/mod/facetoface/attendees.php?s=$editiondata->edition_id title='Iscrivi utenti' ><img src='$CFG->wwwroot/pix/i/users.png' alt='Iscrivi utenti'></a>";
	}else if(isReferenteDiDirezione($USER->id)){
		if ($course_type == C_PRO){
			if($editiondata->session_name){
				
				
			//-------------WARNING---------------//
			//Attenzione ricavo il numero della sessione $sessione_numero dal nome della sessione
			//Es. Sessione 3
			// Ricavo il n° 3 eseguendo la funzione "explode" che divide la stringa "Sessione 3"
			//in una array così composto Array ( [0] => Sessione [1] => 3 ) 
				$sessione_name = explode(" ", $editiondata->session_name);
				$sessione_numero = $sessione_name[1];
				//print_r($sessione_numero);exit;
			//-------------WARNING---------------//

				$sessione_aperta = false;
				$sessione_aperta = get_sessione_aperta_edizione($editiondata->course_year,$sessione_numero);			
			  	if($sessione_aperta){
					//$edition['azione'] .=!isset($edition['editionum']) ? '':  "<a href=$CFG->wwwroot/mod/facetoface/attendees.php?s=$editiondata->edition_id title='Iscrivi utenti' ><img src='$CFG->wwwroot/pix/i/users.gif' alt='Iscrivi utenti'></a>";
					$edition['azione'] .=!isset($edition['editionum']) ? '':  "<a href=$CFG->wwwroot/mod/facetoface/attendees.php?s=$editiondata->edition_id title='Iscrivi utenti' ><img src='$CFG->wwwroot/pix/i/users.png' alt='Iscrivi utenti'></a>";
				}
			}		
		}
		else if ($course_type == C_OBB){
			//$edition['azione'] .=!isset($edition['editionum']) ? '':  "<a href=$CFG->wwwroot/mod/facetoface/attendees.php?s=$editiondata->edition_id title='Iscrivi utenti' ><img src='$CFG->wwwroot/pix/i/users.gif' alt='Iscrivi utenti'></a>";
			$edition['azione'] .=!isset($edition['editionum']) ? '':  "<a href=$CFG->wwwroot/mod/facetoface/attendees.php?s=$editiondata->edition_id title='Iscrivi utenti' ><img src='$CFG->wwwroot/pix/i/users.png' alt='Iscrivi utenti'></a>";
		}
		
	}
//	$editiondata->azione;
	
	if ($course_type == C_PRO) {
		$edition['session_name'] = $editiondata->session_name;
		if ($isDir) {
			$edition['edition_seats_reserved'] = $editiondata->edition_seats_reserved;
			$edition['edition_seats_booked'] = $editiondata->edition_seats_booked;
		}
	}	

	return $edition;
}


/**
 * Ritorna true se la sessione dell'anno $anno è aperta
 * @param int $anno, $numero_sessione
 * @return boolean
 */
function get_sessione_aperta_edizione($anno,$numero_sessione)
{
	global $DB;
	return $DB->record_exists_sql("
									SELECT id
									FROM
										{f2_sessioni}
									WHERE
										anno = ".$anno." AND
										numero = ".$numero_sessione." AND
										stato = 'a'");
}

/*
 * AK-LS
 * 
 * Premessa: da usare solo per corsi programmati e referenti di direzione 
 * (assunzione forte sulle date di iscrizione, controllo esterno)
 * 
 * Funzione che agiorna il numero di posti consumati/riservati
 * per un'edizione specifica di un corso programmato
 * 
 * @param 	id dell'utente (controllo sul fatto che sia referente di direzione),
 * 			tipologia di corso,
 * 			id dell'edizione
 * @return void	
 */ 
function update_available_seats($userID, $coursetype, $session) {
	global $CFG, $DB;
	
	include_once($CFG->dirroot.'/mod/facetoface/lib.php');

	if ($coursetype == C_PRO && isReferenteDiDirezione($userID)) {
		$userDIR = get_direzione_utente($userID);
		
		$userslist = get_visible_users_by_userid($userID);
		$str_users = "";
		foreach ($userslist->dati as $user)
			$str_users .= $user->id.', ';
		$str_users = substr($str_users, 0, -2);
		if (empty($str_users)) $str_users = "0";

		$postiedizione = $DB->get_record('f2_edizioni_postiris_map', array('sessionid' => $session, 'direzioneid' => $userDIR['id']));
		$numDirEnrol = $DB->count_records_sql("
				SELECT 
					COUNT(*) as enrol
				FROM 
					{facetoface_signups} fs, 
					{facetoface_signups_status} fss 
				WHERE 
					fs.sessionid = $session AND 
					fs.userid IN ($str_users) AND 
					fs.id = fss.signupid AND 
					fss.statuscode = ".MDL_F2F_STATUS_BOOKED." AND 
					fss.superceded = 0");

		$postiedizione->nposticonsumati = $numDirEnrol;
		$DB->update_record('f2_edizioni_postiris_map', $postiedizione);
	}
}

/*
 * AK-LS
 * 
 * In uno specifico contesto, la funzione restituisce 
 * tutti i ruoli di un dato utente
 * @param id dell'utente, id del contesto
 * @return array di coppie (id_ruolo, shortname_ruolo)
 */
function get_user_role_in_context($userID, $contextID) {
	global $DB;
	
	return $DB->get_records_sql("
			SELECT 
				DISTINCT ra.roleid, r.shortname
			FROM 
				{role_assignments} ra, 
				{role} r, 
				{context} c
			WHERE 
				ra.userid = $userID
				AND ra.roleid = r.id
				AND ra.contextid = $contextID");
}

/*
 * AK-LS
 * 
 * La funzione restituisce l'id dell'edizione per cui l'utente risulta presente, false altrimenti
 * @param $courseid id del corso
 * @return l'id dell'edizione per cui l'utente risulta presente, false altrimenti
 */
function is_student_fully_attended($userid, $courseid) {
    global $DB, $CFG;
    
    $edizione = $DB->get_record_sql("SELECT 
                                            fs.id as id
                                    FROM 
                                            {$CFG->prefix}course c,
                                            {$CFG->prefix}facetoface f,
                                            {$CFG->prefix}facetoface_sessions fs,
                                            {$CFG->prefix}facetoface_signups fsi,
                                            {$CFG->prefix}facetoface_signups_status fss
                                    WHERE 
                                            c.id = f.course 
                                            AND f.id = fs.facetoface 
                                            AND fs.id = fsi.sessionid 
                                            AND fsi.id = fss.signupid 
                                            AND fss.statuscode = ".MDL_F2F_STATUS_BOOKED." 
                                            AND fss.superceded = 0
                                            AND fsi.userid = $userid
                                            AND c.id = $courseid", array(), IGNORE_MULTIPLE);
    
    return $edizione;
}

/*
 * AK-DL
*
* La funzione restituisce true/false se l'utente può compilare il questionario di gradimento
* Può compilare il test se è iscritto/presente all'edizione e dall'ultimo giorno dell'edizione in poi.
*/
function can_fill_feedback($userid, $courseid) {
	global $DB, $CFG;

	$edizione = $DB->get_record_sql("
        SELECT
            fs.id
        FROM
            mdl_facetoface f
            join mdl_facetoface_sessions fs on fs.facetoface = f.id
            join mdl_facetoface_signups fsi on fsi.sessionid = fs.id
            join mdl_facetoface_signups_status fss on fss.signupid = fsi.id
        WHERE
            f.course = ?
            AND fsi.userid = ?
            AND fss.statuscode >= ?
            AND fss.statuscode != ?
            AND fss.superceded = 0",
        array($courseid, $userid, MDL_F2F_STATUS_BOOKED, MDL_F2F_STATUS_NO_SHOW), IGNORE_MULTIPLE);

		//	return $edizione;

    if($edizione) {
        $edizioneid = $DB->get_record_sql("
            SELECT 
                if(	DATE_FORMAT(NOW(),'%Y%m%d') >= FROM_UNIXTIME(fsd.timestart,'%Y%m%d'), fsd.sessionid, 0) as id
            FROM 
                {facetoface_sessions_dates} fsd
            WHERE sessionid = ? GROUP BY fsd.sessionid", array($edizione->id));
        return $edizioneid;
    } else
        return false;
}

/*
 * AK-LS
 * 
 * La funzione restituisce il numero di utenti, per una data edizione,
 * per cui deve ancora essere compilato il Feedback
 * @param $sessionID è l'identificativo di edizione, $cmID è l'id del course modules
 * @return il numero di utenti che devono compilare il feedback per l'edizione
 */
function get_remains_feedbacks_edition($sessionID, $cmID, $feedback_name, $docenti) {
	global $CFG, $DB;
	
	require_once $CFG->dirroot.'/mod/facetoface/lib.php';
        
        if (!isset($sessionID)) return 0;
        
        if ($feedback_name == get_string('nome_feedback_docente', 'local_f2_import_course')) {
            // caso questionario docenti
            $num_docenti = sizeof($docenti);
            // calcolo il numero di utenti che hanno compilato il questionario
            $complete = $DB->count_records_sql("SELECT 
                                                        COUNT(DISTINCT fec.userid)
                                                FROM
                                                        {course_modules} cm,
                                                        {course} c,
                                                        {modules} m,
                                                        {feedback} fe,
                                                        {feedback_completed} fec,
                                                        {feedback_completed_session} fes
                                                WHERE 
                                                        cm.id = $cmID 
                                                        AND cm.course = c.id 
                                                        AND m.name LIKE 'feedback' 
                                                        AND m.id = cm.module 
                                                        AND cm.instance = fe.id 
                                                        AND fe.id = fec.feedback
                                                        AND fec.id = fes.completed
                                                        AND fes.feedback = fe.id 
                                                        AND fes.session = $sessionID");
            return $num_docenti - $complete;
            
        } else {
            // caso questionario studenti
            // calcolo il numero di utenti che hanno completato il corso
            //AK-LM: considero anche lo stato FULLY_ATTENDED
            $fully_att = $DB->count_records_sql("SELECT 
                                            COUNT(DISTINCT fsi.userid)
                                    FROM 
                                            {course_modules} cm,
                                            {course} c,
                                            {modules} m,
                                            {feedback} fe,
                                            {facetoface} f,
                                            {facetoface_sessions} fs,
                                            {facetoface_signups} fsi,
                                            {facetoface_signups_status} fss
                                    WHERE 
                                            cm.id = $cmID 
                                            AND cm.course = c.id 
                                            AND m.name LIKE 'feedback' 
                                            AND m.id = cm.module 
                                            AND cm.instance = fe.id 
                                            AND c.id = f.course 
                                            AND f.id = fs.facetoface 
                                            AND fs.id = $sessionID 
                                            AND fs.id = fsi.sessionid 
                                            AND fsi.id = fss.signupid 
                                            AND fss.statuscode IN (".MDL_F2F_STATUS_BOOKED.",".MDL_F2F_STATUS_FULLY_ATTENDED.")
                                            AND fss.superceded = 0");

            // calcolo il numero di utenti che hanno compilato il questionario
            $complete = $DB->count_records_sql("SELECT 
                                                        COUNT(fec.userid)
                                                FROM
                                                        {course_modules} cm,
                                                        {course} c,
                                                        {modules} m,
                                                        {feedback} fe,
                                                        {feedback_completed} fec,
                                                        {feedback_completed_session} fes
                                                WHERE 
                                                        cm.id = $cmID 
                                                        AND cm.course = c.id 
                                                        AND m.name LIKE 'feedback' 
                                                        AND m.id = cm.module 
                                                        AND cm.instance = fe.id 
                                                        AND fe.id = fec.feedback
                                                        AND fec.id = fes.completed
                                                        AND fes.feedback = fe.id 
                                                        AND fes.session = $sessionID");

            return $fully_att - $complete;
        }
}

/*
 * La funzione restituisce il numero di utenti iscritti ad una data edizione.
 * Usata solo in ambito compilazione QdG: vedi mod/feedback/complete.php
 * @param $sessionID è l'identificativo di edizione, $cmID è l'id del course modules
 * @return il numero di utenti iscritti all'edizione
 */
function get_users_booked_edition($sessionID, $cmID) {
	global $CFG, $DB;
	
	require_once $CFG->dirroot.'/mod/facetoface/lib.php';
    //AK-LM: considero anche lo stato FULLY_ATTENDED
	$sql = "SELECT
		COUNT(DISTINCT fsi.userid)
		FROM
		{course_modules} cm,
		{course} c,
		{modules} m,
		{feedback} fe,
		{facetoface} f,
		{facetoface_sessions} fs,
		{facetoface_signups} fsi,
		{facetoface_signups_status} fss
		WHERE
		cm.id = $cmID
		AND cm.course = c.id
		AND m.name LIKE 'feedback'
		AND m.id = cm.module
		AND cm.instance = fe.id
		AND c.id = f.course
		AND f.id = fs.facetoface
		AND fs.id = $sessionID
		AND fs.id = fsi.sessionid
		AND fsi.id = fss.signupid
		AND fss.statuscode IN (".MDL_F2F_STATUS_BOOKED.",".MDL_F2F_STATUS_FULLY_ATTENDED.")
		AND fss.superceded = 0";

    $fully_att = $DB->count_records_sql($sql);
    return $fully_att;
}

/*
 * AK-LS 
 * 
 * Controlla che l'utente possa risultare come il compilatore del Feedback
 * e se non è così viene restituito l'id di un utente valido (in stato presente
 * all'edizione del corso, che non ha ancora completato il questionario).
 * 
 * Il controllo che ci sia ancora un utente per cui possa essere completato il 
 * Feedback è fatto a monte di questa funzione "../mod/feedback/complete.php"
 * 
 * @param	$feedbackID (id questionario), 
 * 			$sessionID (id edizione), 
 * 			$userID (id utente)
 * @return l'id di un utente che può risultare (e/o ha compilato) compilatore del 
 * questionario. 0 altrimenti
 */
function is_user_can_compile_feedback($feedbackID, $sessionID, $userID) {
	global $CFG, $DB;
	
	require_once $CFG->dirroot . '/mod/facetoface/lib.php';
	
	/*
	 * Query che restituisce 1 se l'utente è stato consuntivato 
	 * presente al corso, 0 altrimenti
	 */
	$attendedSession = $DB->get_record_sql ( "
			SELECT 
				IF(fsi.id, 1, 0) AS attended
			FROM 
				{facetoface_sessions} fs
			JOIN 
				{facetoface_signups} fsi 
			 ON fs.id = fsi.sessionid
			JOIN 
				{facetoface_signups_status} fss 
			 ON fsi.id = fss.signupid				
			WHERE 
				fs.id = $sessionID 
				AND fss.statuscode IN (".MDL_F2F_STATUS_BOOKED.",".MDL_F2F_STATUS_FULLY_ATTENDED.") 
				AND fss.superceded = 0 
				AND fsi.userid = $userID" );
	
	if ($attendedSession->attended and !is_user_complete_feedback($feedbackID, $userID))
		return $userID; // L'utente d sessione può compilare il Feedback
	else { // Restituisce casualmente un utente per cui il Feedback dev'essere ancora completato
		$users = $DB->get_records_sql("
				SELECT 
						DISTINCT fsi.userid 
					FROM 
						{$CFG->prefix}facetoface_sessions fs
					JOIN 
						{$CFG->prefix}facetoface_signups fsi
					 ON fs.id = fsi.sessionid 
					JOIN 
						{$CFG->prefix}facetoface_signups_status fss 
					 ON fsi.id = fss.signupid
					WHERE 
						fs.id = $sessionID 
						AND fss.statuscode IN (".MDL_F2F_STATUS_BOOKED.",".MDL_F2F_STATUS_FULLY_ATTENDED.") 
						AND fss.superceded = 0
                                                AND fsi.userid NOT IN (
                                                    SELECT 
                                                        DISTINCT fcs.user
                                                    FROM 
                                                        {$CFG->prefix}feedback_completed fc 
                                                    JOIN
                                                        {$CFG->prefix}feedback_completed_session fcs 
                                                     ON fc.id = fcs.completed
                                                    WHERE 
                                                        fcs.feedback = $feedbackID 
                                                        AND fcs.session = $sessionID
                                                )");
		
		if ($users) {
			// Ritorna il primo
			foreach ($users as $user)
				return $user->userid;
		} else {
			return 0;
		}
	}
}

/*
 * AK-LS 
 * Controlla che l'utente in questione ha già compilato il Feedback in
 * esame 
 * @param 	feedbackID, 
 * 			$userID 
 * @return true o false (a seconda che l'ha completato o meno)
 */
function is_user_complete_feedback($feedbackID, $userID) {
	global $DB;
	
	$complete = $DB->count_records_sql ( "SELECT COUNT(userid) AS user FROM {feedback_completed} WHERE feedback = $feedbackID AND userid = $userID" );
	return ($complete) ? true : false;
}

// dato il nome di un profile_field e la l'indice della chiave restituisce il valore
function get_profile_field_value($profile_field, $index_key) {
    global $DB;
    
    $param1 = $DB->get_field('user_info_field', 'param1', array('shortname' => $profile_field), IGNORE_MULTIPLE);
    $keys = preg_split( '/\r\n|\r|\n/', $param1 );
    
    return $keys[$index_key];
}

function get_shortname_by_id($id) {
	global $DB;

	$param = $DB->get_field('user_info_field','shortname', array('id' => $id), IGNORE_MULTIPLE);

	return $param;
}

// restituisce una mappa contenente i valori per tutti i profile fields
function get_profile_fields_values() {
    global $DB, $CFG;
    
    $shortnames = $DB->get_records_sql("SELECT shortname FROM {$CFG->prefix}user_info_field");
    $profile_fields = array();
    
    foreach ($shortnames as $info_field) {
        $default = $DB->get_field('user_info_field', 'defaultdata', array('shortname' => $info_field->shortname), IGNORE_MULTIPLE);
        $param1 = $DB->get_field('user_info_field', 'param1', array('shortname' => $info_field->shortname), IGNORE_MULTIPLE);
        $keys = preg_split( '/\r\n|\r|\n/', $param1 );
        $values = array();
        $index = 0;
        $index_default = -1;
        foreach ($keys as $key) {
            $values[$key] = $index;
            if ($key == $default) $index_default = $index;
            $index++;
        }
        $values['default'] = $index_default;
        $profile_fields[$info_field->shortname] = $values;
    }
    
    return $profile_fields;
}

function heading_msg($text, $classes = 'msg_user', $id = null) {
	return html_writer::tag('p', $text, array('id' => $id, 'class' => renderer_base::prepare_classes($classes)));
}

// restituisce true se un corso è on-line 
function get_type_course($idcourse) {
	global $DB;

	return $DB->record_exists('scorm', array('course'=>$idcourse));
}

// restituisce lo stato di completamento del corso di un utente
function get_status_course_completion_user($id_user, $idcourse) {
    global $DB;	
    return $DB->get_record_sql("SELECT * FROM {course_completions} cc WHERE cc.userid = ".$id_user." AND cc.course = ".$idcourse);
}

// restituisce il campo obiettivi della tabella mdl_f2_scheda_progetto
function get_obiettivo_corso($course) {

	global $DB;
	
	$id_corso = $course->id;

	$select_str ="	SELECT sp.obiettivi ";
	$from_str = " from {f2_scheda_progetto} sp ";
	$where_str = " where sp.courseid = ".$id_corso." ";

	$sqlstr = $select_str.$from_str.$where_str;
	$obiettivo = $DB->get_record_sql($sqlstr);
	return $obiettivo->obiettivi;
}
