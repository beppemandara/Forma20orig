<?php
/*
 *  $Id: lib.php 1285 2014-05-19 05:15:19Z l.moretto $
 */
define("CONST_STR_SEP", "-");
/**
 * restituisce le sub aree formative o la sub area formativa se gli passo l'id
 * @var $id id della sub area formativa
 * @return stdClass: sub aree formative
 */
function get_sub_aree_formative($id=NULL)
{
	global $DB;
	if(is_null($id))
		$condition = array('stato'=>'A');
	else
		$condition = array('id'=>$id);
	
	return $DB->get_records('f2_subaf',$condition, $sort='progr_displ','id,concat_ws(\''.CONST_STR_SEP.'\',id,descrizione) as descrizione');
	
}
/**
 * Data un'area formativa, restituisce le sotto aree formative di pertinenza.
 * @global object $DB
 * @param string Id area formativa
 * @return object Result set 
 */
function get_SUBAF_from_AF($af_id)
{
	global $DB;
	if( !is_string($af_id) )
	{
		print_error('invalidafid','local_f2_support');
	}
	
	$query = "SELECT subaf.id,
		CONCAT_WS('".CONST_STR_SEP."',subaf.id,subaf.descrizione) as descrizione
			FROM {f2_subaf} subaf
		INNER JOIN {f2_saf} map ON map.sub = subaf.id
			WHERE map.af = '".mysql_escape_string($af_id)."'
		ORDER BY subaf.progr_displ";
	$rs = $DB->get_records_sql($query);
	
	return $rs;
}

/**
 * Restituisce le tipologie di pianificazione, o dato l'id il corrispondente tipo pianificazione.
 * @var $id  id del tipo_pianificazione
 * @return stdClass: tipo_pianificazione 
 */
function get_tipo_pianificazione($id=NULL)
{
	global $DB;
	if(is_null($id))
		$condition = array('stato'=>'A');
	else
		$condition = array('id'=>$id);

	return $DB->get_records('f2_tipo_pianificazione', $condition, $sort='progr_displ',
					'id,concat_ws(\''.CONST_STR_SEP.'\',id,descrizione) as descrizione');

}

/**
 * Restituisce le aree formative, o dato l'id la singola area formativa.
 * @var $id id dell'area formativa
 * @return stdClass:  aree formative
 */
function get_aree_formative($id=NULL)
{
	global $DB;
	if(is_null($id))
		$condition = array('stato'=>'a');
	else
		$condition = array('id'=>$id);

	return $DB->get_records('f2_af', $condition, $sort='progr_displ', 
					'id,concat_ws(\''.CONST_STR_SEP.'\',id,descrizione) as descrizione');

}
/**
 * Dato un segmento formativo, restituisce le aree formative di pertinenza.
 * @global object $DB
 * @param string Id segmento formativo
 * @return object Result set 
 */
function get_AF_from_SF($sf_id)
{
	global $DB;
	if( !is_string($sf_id) )
	{
		print_error('invalidsfid','local_f2_support');
	}
	
	$query = "SELECT af.id, 
		CONCAT_WS('".CONST_STR_SEP."',af.id,af.descrizione) as descrizione
			FROM {f2_sf_af_map} map
		INNER JOIN {f2_af} af ON af.id = map.af
			WHERE map.sf = '".mysql_escape_string($sf_id)."'
		ORDER BY af.progr_displ";
	$rs = $DB->get_records_sql($query);
	
	return $rs;
}
/**
 * Restituisce le tipologie oranizzative, o dato l'id la relativa tipologia oranizzativa.
 * @var $id  id della tipologia oranizzativa
 * @return stdClass:  tipologia oranizzativa (id,descrizione)
 */
function get_tipologia_org($id=NULL)
{
	global $DB;
	if(is_null($id))
		$condition = array('stato'=>'A');
	else
		$condition = array('id'=>$id);

	return $DB->get_records('f2_to_x', $condition, $sort='progr_displ',
					'id,concat_ws(\''.CONST_STR_SEP.'\',id,descrizione) as descrizione');
}

/**
 * restituisce flag_dir_scuola o flag_dir_scuola se gli passo l'id
 * @var $id id di flag_dir_scuola
 * @return stdClass:  flag_dir_scuola
 */
function get_flag_dir_scuola($id=NULL)
{
	$list_fds = array();
	$obj1 = new stdClass();
	$obj1->id = 'S';
	$obj1->descrizione = 'Scuola';
	$obj2 = new stdClass();
	$obj2->id = 'D';
	$obj2->descrizione = 'Direzione';
	if(is_null($id)){	
		$list_fds[$obj1->id] = $obj1;
		$list_fds[$obj2->id] = $obj2;
	}
	else if($id=='S')
		$list_fds[$obj1->id] = $obj1;
	else if($id=='D')
		$list_fds[$obj2->id] = $obj2;

	return $list_fds;

}

/**
 * Restituisce le tipologie ente erogatore, o dato l'id la relativa tipologia ente.
 * @var $id  id del codice identificazione della tipologia ente erogatore.
 * @return stdClass: codice identificazione della tipologia ente erogatore.
 */
function get_tipo_ente_erogatore($id=NULL)
{
	global $DB;
	if(is_null($id))
		$condition = array('stato'=>'A');
	else
		$condition = array('id'=>$id);

	return $DB->get_records('f2_te', $condition, $sort='progr_displ', 
					'id,concat_ws(\''.CONST_STR_SEP.'\',id,descrizione) as descrizione');

}

/**
 * restituisce segmenti formativi
 * @var $id id del segmento formativo
 * @return stdClass: segmento formativo
 */
function get_segmento_formativo($id=NULL)
{
	global $DB;
	if(is_null($id))
		$condition = array('stato'=>'A');
	else
		$condition = array('id'=>$id);

	return $DB->get_records('f2_sf', $condition, $sort='progr_displ', 
					'id,concat_ws(\''.CONST_STR_SEP.'\',id,descrizione) as descrizione');

}

/**
 * restituisce segmenti formativi
 * @var $id id del segmento formativo
 * @return stdClass: segmento formativo
 */
function get_codice_partecipazione($id=NULL)
{
	$id = 3; // solo questa funzionalità per ora è attiva
	global $DB;
	
	$condition = array('stato'=>'A','id'=>$id);

	return $DB->get_records('f2_gest_codpart',$condition, $sort='progr_displ');

}

// parametri budget
/**
 * @return stdClass: parametri budget
 */
function get_parametri_budget()
{
	global $DB;
	$query = "
				SELECT 	*
				FROM 	
					{f2_parametri} fp ";
	$rs = $DB->get_records_sql($query);
	
	return $rs;
}

// parametri budget
/**
 * @return stdClass: parametri budget
 */


function update_parametri_budget($data)
{
	$parametro_aula                                			  = new stdClass();
	$parametro_aula->id                                       = "p_f2_bdgt_aula_cap";
	$parametro_aula->val_float                                =	$data['aula'];
	$parametro_e_learning                                     = new stdClass();
	$parametro_e_learning->id                                 = "p_f2_bdgt_elearning_cap";
	$parametro_e_learning->val_float                          = $data['e_learning'];
	$parametro_individuale                                    = new stdClass();
	$parametro_individuale->id                                = "p_f2_bdgt_individuale_cap";
	$parametro_individuale->val_float                         = $data['individuale'];
	$parametro_posti_aula                                     = new stdClass();
	$parametro_posti_aula->id                                 = "p_f2_bdgt_posti_aula_cap";
	$parametro_posti_aula->val_float                          =  $data['posti_aula'];
	$parametro_s1                                             = new stdClass();
	$parametro_s1->id                                         = "p_f2_bdgt_s1_cap";
	$parametro_s1->val_float                                  = $data['s1'];
	$parametro_s2                                             = new stdClass();
	$parametro_s2->id                                         = "p_f2_bdgt_s2_cap"  ;
	$parametro_s2->val_float                                  = $data['s2'];
	$parametro_sj                                             = new stdClass();
	$parametro_sj->id                                         = "p_f2_bdgt_sj_cap";
	$parametro_sj->val_float                                  = $data['sj'];
	$parametro_bonus_lingue                                   = new stdClass();
	$parametro_bonus_lingue->id                               = "p_f2_bdgt_bonus_lingue_cap";
	$parametro_bonus_lingue->val_float                        = $data['bonus_lingue'];
	$parametro_fondi_consiglio                                = new stdClass();
	$parametro_fondi_consiglio->id                            = "p_f2_bdgt_fondi_consiglio_cap";
	$parametro_fondi_consiglio->val_float                     = $data['fondi_consiglio'];
	$parametro_obiettivo                                      = new stdClass();
	$parametro_obiettivo->id                                  = "p_f2_bdgt_obiettivo_cap";
	$parametro_obiettivo->val_float                           = $data['obiettivo'];
	$parametro_progetti_obiettivo                             = new stdClass();
	$parametro_progetti_obiettivo->id                         = "p_f2_bdgt_prog_ob_cap";
	$parametro_progetti_obiettivo->val_float                  = $data['progetti_obiettivo'];
	$parametro_seminari_direzione                             = new stdClass();
	$parametro_seminari_direzione->id                         = "p_f2_bdgt_seminari_cap";
	$parametro_seminari_direzione->val_float                  = $data['seminari_direzione'];
	$parametro_coefficiente_formativo                          = new stdClass();
	$parametro_coefficiente_formativo->id                      = "p_f2_bdgt_coeff_form_par";
	$parametro_coefficiente_formativo->val_float               = $data['coefficiente_formativo'];
	$parametro_assegnazione_giorni_crediti_aula  			  = new stdClass();
	$parametro_assegnazione_giorni_crediti_aula->id     	  = "p_f2_bdgt_giorni_cred_aula_par";
	$parametro_assegnazione_giorni_crediti_aula->val_float    = $data['assegnazione_giorni_crediti_aula'];
	$parametro_criterio_assegnamento_corsi_lingue  			  = new stdClass();
	$parametro_criterio_assegnamento_corsi_lingue->id   	  = "p_f2_bdgt_corsi_lingue_par";
	$parametro_criterio_assegnamento_corsi_lingue->val_float  = $data['criterio_assegnamento_corsi_lingue'];
	$parametro_numero_strutture								  = new stdClass();
	$parametro_numero_strutture->id							  = "p_f2_bdgt_num_strutture_par";
	$parametro_numero_strutture->val_float					  = $data['numero_strutture'];
	
	$parametri= array($parametro_aula, $parametro_e_learning, $parametro_individuale, $parametro_posti_aula, $parametro_s1, $parametro_s2, 
						$parametro_sj, $parametro_bonus_lingue, $parametro_fondi_consiglio, $parametro_obiettivo, $parametro_progetti_obiettivo, $parametro_seminari_direzione,
						 $parametro_coefficiente_formativo, $parametro_assegnazione_giorni_crediti_aula, $parametro_criterio_assegnamento_corsi_lingue, $parametro_numero_strutture);
	
	if(update_parametri($parametri))
		return true;
	else 		
		return false;
	
}


/**
 * Riceve in input un array di oggetti
 * ogni oggetto deve contenere "id => value_id, nome_colonna_tabella => valore_da_settare
 * Es. [id] => p_f2_bdgt_aula_cap [val_float] => 260000
 */
function update_parametri($data)
{
	global $DB;
	
	$esito = 1;
	foreach($data as $parametro){
		if(!$DB->update_record('f2_parametri', $parametro)){
			$esito = 0;
		}
	}
	
	if(!$esito)
		return false;
	else 	
		return true;
}

/**
 * @var $id nome della colonna della tabella
 * @return stdClass: parametri id
 */
function get_parametro($id)
{
	global $DB;
	$query = "
			SELECT 	*
			FROM
				{f2_parametri} fp
			WHERE 
				 fp.id LIKE '%".$id."%'";
	$rs = $DB->get_record_sql($query);
	return $rs;
}

/**
 * @var $prefix_id prefisso degli id dei parametri ricercati
 * @return array parametri id
 */
function get_parametri_by_prefix($prefix_id)
{
	global $DB;
	$query = "
			SELECT 	*
			FROM
				{f2_parametri} fp
			WHERE 
				 fp.id LIKE '".$prefix_id."%'";
	$rs = $DB->get_records_sql($query);
	return $rs;
}

/*
 * Restituisce il valore del Segmento Jolly per il piano di studio
 * @return se esiste ritorna il codice del segmento jolly, altrimenti NULL
 */
function get_piano_studio_segmento_jolly() {
	$sj = get_parametro('p_f2_piano_di_studio_segmento_jolly');
	return !empty($sj) ? $sj->val_char : NULL;
}

function get_obj_date_piano_di_studi(){
	$tz = new DateTimeZone("Europe/Rome");
//print_r('tz');var_dump($tz);
    $format = 'Y-m-d H:i:s';
    $dtfinecorrente = DateTime::createFromFormat($format, get_parametro('p_f2_piano_di_studio_data_fine_corrente')->val_date, $tz);
	//$dtfinecorrente = get_parametro('p_f2_piano_di_studio_data_fine_corrente')->val_date;
//print_r('$dtfinecorrente');var_dump($dtfinecorrente);
    $dtfineprecedente = DateTime::createFromFormat($format, get_parametro('p_f2_piano_di_studio_data_fine_precedente')->val_date, $tz);
	//$dtfineprecedente = get_parametro('p_f2_piano_di_studio_data_fine_precedente')->val_date;
//print_r('$dtfineprecedente');var_dump($dtfineprecedente);    
    $timestamp_data_fine_corrente = $dtfinecorrente->getTimestamp();
	//$timestamp_data_fine_corrente   = strtotime($dtfinecorrente);
    $timestamp_data_fine_precedente = $dtfineprecedente->getTimestamp();
	//$timestamp_data_fine_precedente = strtotime($dtfineprecedente);
	
    $durata = get_parametro('p_f2_piano_di_studio_durata')->val_int;
    $intervallo = new DateInterval("P{$durata}Y");

	//$corrente_subtract_data   = strtotime("-$durata years", $timestamp_data_fine_corrente);
	//$precedente_subtract_data = strtotime("-$durata years", $timestamp_data_fine_precedente);
	
	$data = new stdClass();
    $data->data_fine_corrente = $dtfinecorrente->format('d/m/Y');
	//$data->data_fine_corrente = date('d/m/Y', strtotime($dtfinecorrente));
    $data->data_fine_precedente = $dtfineprecedente->format('d/m/Y');
	//$data->data_fine_precedente = date('d/m/Y', strtotime($dtfineprecedente));
	$data->timestamp_data_fine_corrente   = $timestamp_data_fine_corrente;
	$data->timestamp_data_fine_precedente = $timestamp_data_fine_precedente;
	$data->corrente_subtract_data         = $dtfinecorrente->sub($intervallo)->getTimestamp();
//print_r('corrente_subtract_data');var_dump($dtfinecorrente);
	$data->precedente_subtract_data       = $dtfineprecedente->sub($intervallo)->getTimestamp();
//print_r('precedente_subtract_data');var_dump($dtfineprecedente);
//print_r('data');var_dump($data);
	return $data;
}

function get_verifica_apprendimento() {
	global $DB;
	return $DB->get_records('f2_va', array('stato' => 'A'));		
}

function get_partecipazioni_values() {
    global $DB;

    $sql = "SELECT id, descrpart,codpart
            FROM {f2_partecipazioni}
            WHERE stato = 'A'
            ORDER BY codpart, progr_displ";

    $rs = $DB->get_records_sql($sql);
    return $rs;
}

function get_all_partecipazioni_values() {
	global $DB;

	$sql = "SELECT id, descrpart,codpart,IF(stato <> 'A',     '[ELIMINATO]', '') as invalid
            FROM {f2_partecipazioni}
            ORDER BY codpart, progr_displ";

	$rs = $DB->get_records_sql($sql);
	return $rs;
}

function get_partecipazione_by_cod_desc($codpart,$descpart) {
	global $DB;

	$sql = "SELECT id
	FROM {f2_partecipazioni}
	WHERE codpart = ".$codpart." AND
	descrpart = '".$descpart."'";

	$rs = $DB->get_record_sql($sql);
	return $rs;
}


function get_va_values() {
    global $DB;

    $sql = "SELECT id, descrizione
            FROM {f2_va}
            WHERE stato = 'A'
            ORDER BY progr_displ";

    $rs = $DB->get_records_sql($sql);
    return $rs;
}

function get_partecipazione($id) {
    global $DB;

    $sql = "SELECT codpart, descrpart
            FROM {f2_partecipazioni}
            WHERE id = $id";

    $rs = $DB->get_record_sql($sql);
    var_dump($rs);
    return array($rs->codpart, $rs->descrpart);
}
