<?php

//$Id: management.php 1426 2016-11-21 07:08:21Z l.moretto $

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/f2_support/lib.php');

/**
 * Verifica che l'utente sia un proprio dipendente 
 * @param int $userid userid del dipendente che io, utente loggato ($USER->id) posso visualizzare
 * return bool
 */ 
function validate_own_dipendente($userid) {
	global $DB,$USER, $CFG;
		if ($userid == $USER->id) return true;
		else 
		{
			list($dominio_visibilita_id, $dominio_visibilita_name) = get_user_viewable_organisation($USER->id);
			list($dominio_dipendente_id, $dominio_dipendente_name) = get_user_organisation($userid);
			if ($dominio_dipendente_id ==  $dominio_visibilita_id) return true;
			
			$select_path = "SELECT path FROM {$CFG->prefix}org WHERE id = $dominio_dipendente_id";
			$path = $DB->get_field_sql($select_path);
			
			$pos = strpos($path, $dominio_visibilita_id);
			
			if ($pos === false)
				return false;
			else
				return true;
		}
}


/**
 * Restituisce la lista dei propri dipendenti 
 * @param int $userid
 * return object
 */
function get_dipendenti($userid=NULL,$data) {
	global $DB,$USER;
	if(is_null($userid))
		$userid=intval($USER->id);
	
	$where = "AND lower(lastname) like lower('%".mysql_escape_string($data->search_name)."%') ";
	
	$sql= "SELECT 	
				*
			FROM 
				{user}
		  WHERE 
			1=1 $where";
	
	$sql .= " ORDER BY ".$data->column." ".$data->sort;
	$dipendenti = $DB->get_records_sql($sql,NULL,$data->page*$data->perpage,$data->perpage);
	
	
	$return			= new stdClass;
	$return->count	= $DB->count_records_sql("SELECT count(*) FROM ($sql) as tmp"); 
	$return->dati	= $dipendenti;
	
	return $return;
}

/**
 * Restituisce l'id del dominio radice
 * return il dominio radice (id, fullname), NULL se non esiste
 */
function get_root_framework() {
    global $DB, $CFG;
    $select = "SELECT id, fullname
                FROM {$CFG->prefix}org
                WHERE parentid = 0";

    $org = $DB->get_record_sql($select);

    if ($org) {
        $obj = new stdClass();
        $obj->id	= $org->id;
        $obj->fullname	= $org->fullname;
        return $obj;
    } else {
        return NULL;
    }
}

/**
 * Effettua una ricerca ricorsiva in profondità nella gerarchia dei domini a partire da un id radice
 * restituendo id, shortname, fullname, parentid e livello di ogni dominio incontrato
 * @param int $rootID id del dominio radice
 * @param array() array vuoto
 * return object
 */
function recursivesubtree($rootID, $a = array()) {
    global $DB;
    $qry = "SELECT id, shortname, fullname, parentid, depthid as level " .
           "FROM mdl_org " .
           "WHERE parentid=? AND visible=1";
    $res = $DB->get_records_sql($qry, array($rootID));
    foreach ($res as $row) {
      $a[] = $row;
      if( $row->parentid > 0 ) $a = recursivesubtree( $row->id, $a);
    }
    return $a;
}

/**
 * Effettua una ricerca ricorsiva in profondità nella gerarchia dei domini a partire da un id radice
 * restituendo la gerarchia dei domini in formato json per la visualizzazione della tree view
 * @param int $rootID id del dominio radice
 * @param $rootName fullname del dominio radice
 * @param $wanted array contenente i soli id dei domini di livello 2 che si vuole visualizzare (es. GIUNTA, CONSIGLIO, ENTI ESTERNI)
 * @param $consider_limit boolean specifica se considerare o meno il prossimo parametro
 * @param $depth_limit int specifica il livello di profondità limite a cui la ricorsione deve arrivare
 * @param $a stringa contente il json da costruire passo passo (omettere alla prima chiamata)
 * @param $isRoot boolean definisce se il dominio è radice (omettere alla prima chiamata)
 * return object
 */
function recursivesubtreejson($rootID, $rootName = '', $wanted = null, $consider_limit = false, $depth_limit = 4, $a = '', $isRoot = true) {
    global $DB;
    
    if ($consider_limit && $depth_limit == 0) return $a; // se ho raggiunto il limite di navigazione in profondità
    $res = array();
    
    if (!$consider_limit || $depth_limit != 1) {
    $qry = "SELECT id, idnumber, fullname, parentid, depthid as level" .
           " FROM mdl_org" .
           " WHERE parentid=? AND visible=1";
    $res = $DB->get_records_sql($qry, array($rootID));
    }
    if (sizeof($res) > 0) {
        if ($rootName != '')
            $a .= "{\"title\": \"$rootName\", \"isFolder\": true, \"key\": \"$rootID\", \"children\": [";
    } else {
        $a .= "{\"title\": \"$rootName\", \"key\": \"$rootID\"";
    }
    
    foreach ($res as $row) {
        // se sono stati specificati dei domini radice regione (depthid = 2) particolari, verifico che il nodo non sia da scartare
        if ($row->level == 2 && is_array($wanted) && !in_array($row->id, $wanted))
                continue;
        
        if ($row->level == 3 || $row->level == 4)
            $name = $row->idnumber.' - '.addslashes($row->fullname);
        else
            $name = addslashes($row->fullname);
        
        if( $row->parentid > 0 ) $a = recursivesubtreejson( $row->id, $name, $wanted, $consider_limit, $depth_limit-1, $a, false);
    }
    
    if (sizeof($res) > 0) {
        if ($rootName != '')
            $a .= "]},";
    } else {
        $a .= "},";
    }
    
    if ($isRoot) {
        $a = str_replace (',]', ']', $a);
        $a = substr($a, 0, strlen($a)-1);
    }
 
    return $a;
}

/**
 * Restituisce i referenti di direzione (aka referenti formativi), ovvero gli utenti che hanno per dominio di visibilità una direzione (cioè un dominio di 3o liv)
 * @param int $id_dominio id del dominio settore (o sua direzione) di cui si vuole avere i referenti di direzione. Se non
 * specificato, vengono restituiti tutti i referenti di direzione appartenenti ai sottodomini di GIUNTA, CONSIGLIO o ENTI ESTERNI
 * return array gli utenti che hanno per dominio di visibilità una direzione (ovvero un dominio di 3o liv)
 */
function get_referenti_direzione($id_dominio = NULL) {

    global $CFG, $DB;
    $select = "deleted <> 1";
    
    $root = get_root_framework();
    
    if (is_null($root)) return NULL;
    
    if (!is_null($id_dominio)) {
        // dominio specificato, verifico se si tratta di un settore o di una direzione
        $select_level = "SELECT depthid FROM {$CFG->prefix}org WHERE id = $id_dominio";
        $level = $DB->get_field_sql($select_level);
        if ($level < 3) return NULL; // se non si tratta di un settore o di una direzione non esistono referenti di direzione
        if ($level == 4) {
            // il dominio passato per parametro è un settore: ottengo l'id della sua direzione
            $select_parent = "SELECT parentid FROM {$CFG->prefix}org WHERE id = $id_dominio";
            $parentid = $DB->get_field_sql($select_parent);
            $id_dominio = $parentid;
        }
        $select .= " AND viewableorganisationid = $id_dominio";
    } else {
        // dominio non specificato, parto dalla radice della gerarchia dei domini
        // ottengo gli id dei domini facenti parte dell'albero sotteso a $root->id
        $info = recursivesubtree($root->id);

        $in_clause_1 = " AND viewableorganisationid IN (";
        foreach( $info as $row ) {
            // filtro i soli domini di direzione (terzo livello)
            if ($row->level == 3) {
                $in_clause_2 .= ", $row->id";
            }
        }
        $in_clause_2 = substr($in_clause_2, 2); // rimuovo la prima virgola ', '
        $select .= $in_clause_1.$in_clause_2.')';
    }
    
    // ottengo gli id dei domini di secondo livello GIUNTA, CONSIGLIO e ENTI ESTERNI
    $parametri_regioni = get_parametri_by_prefix('p_f2_dominio_radice_regione_');
    $id_radici_regione_array = array();
    foreach ($parametri_regioni as $param) {
        $id_radici_regione_array[] = $param->val_int;
    }
    $id_radici_regione = implode(', ', $id_radici_regione_array);
    
    $select .= " AND org2.parentid IN ($id_radici_regione)";
    
    return $DB->get_records_sql("SELECT user.id, username, user.idnumber, email, firstname, lastname, city, country,
                    lastaccess, confirmed, mnethostid, suspended, org.fullname as org_fullname, org2.fullname as viewable_org_fullname, org2.depthid as level
               FROM {user} user
               LEFT JOIN {$CFG->prefix}org_assignment oa ON user.id=oa.userid
               LEFT JOIN {$CFG->prefix}org org ON org.id=oa.organisationid
               LEFT JOIN {$CFG->prefix}org org2 ON org2.id=oa.viewableorganisationid
               WHERE $select");
}

/**
 * Restituisce i referenti di settore, ovvero gli utenti che hanno per dominio di visibilità un settore (cioè un dominio di 4o liv)
 * @param int $id_dominio id del dominio settore di cui si vuole avere i referenti di direzione.
 * return object gli utenti che hanno per dominio di visibilità un settore (cioè un dominio di 4o liv)
 */
function get_referenti_settore($id_settore) {

    global $CFG, $DB;
    $select = "deleted <> 1";
    
    $root = get_root_framework();
    
    if (is_null($root)) return NULL;
    
    if (is_null($id_settore)) return NULL;
    
    // settore specificato, verifico se si tratta realmente di un settore
    $select_level = "SELECT depthid FROM {$CFG->prefix}org WHERE id = $id_settore";
    $level = $DB->get_field_sql($select_level);
    if ($level < 4) return NULL; // se non si tratta di un settore non esistono referenti di settore

    $select .= " AND viewableorganisationid = $id_settore";
    
    return $DB->get_records_sql("SELECT user.id, username, user.idnumber, email, firstname, lastname, city, country,
                    lastaccess, confirmed, mnethostid, suspended, org.fullname as org_fullname, org2.fullname as viewable_org_fullname
               FROM {user} user
               LEFT JOIN {$CFG->prefix}org_assignment oa ON user.id=oa.userid
               LEFT JOIN {$CFG->prefix}org org ON org.id=oa.organisationid
               LEFT JOIN {$CFG->prefix}org org2 ON org2.id=oa.viewableorganisationid
               WHERE $select");
}

/**
 * Restituisce i referenti di direzione (aka referenti formativi) e i supervisori, ovvero gli utenti che hanno per dominio di visibilità un dominio di 1o, 2o o 3o liv)
 * @param int $id_dominio id del dominio di partenza di cui si vuole avere i referenti di direzione e i supervisori
 * return array gli utenti che hanno per dominio di visibilità un dominio di 1o, 2o o 3o liv)
 *      SE $id_dominio è di 1o livello (radice della gerarchia dei domini) restituisce i referenti di direzione e i supervisori dei sottoalberi dei 
 *          domini radice GIUNTA, CONSIGLIO e ENTI ESTERNI
 *      SE $id_dominio è di 2o livello restituisce i referenti di direzione e i supervisori del sottoalbero del dominio specificato
 *          dopo aver verificato che $id_dominio appartenga a uno tra GIUNTA, CONSIGLIO e ENTI ESTERNI
 *      SE $id_dominio è di 3o o 4o livello (direzione o settore) restituisce NULL (per quello c'è il metodo get_referenti_direzione()
 */
function get_referenti_e_supervisori($id_dominio = NULL) {

    global $CFG, $DB;
    $select = "deleted <> 1 AND suspended <> 1";
    $in_clause_1 = '';
    $in_clause_2 = '';
    
    $root = get_root_framework();
    
    if (is_null($root)) return NULL;
    
    if (is_null($id_dominio)) return NULL;
    
    // dominio specificato, verifico se si tratta di un settore o di una direzione
    $select_level = "SELECT depthid FROM {$CFG->prefix}org WHERE id = $id_dominio";
    $level = $DB->get_field_sql($select_level);
    if ($level > 2) return NULL; // se non si tratta di un dominio di 1o o 2o livello restituisco NULL
    
    if ($level == 2) {
        // il dominio passato per parametro è di 2o livello
        $in_clause_1 = " AND viewableorganisationid IN ($id_dominio"; // utenti supervisori
    } else {
        // il dominio passato per parametro è di 1o livello (dominio RADICE della gerarchia dei domini)
        // ottengo gli id dei domini di secondo livello GIUNTA, CONSIGLIO e ENTI ESTERNI
        $id_dominio = $root->id;
        $parametri_regioni = get_parametri_by_prefix('p_f2_dominio_radice_regione_');
        $id_radici_regione_array = array();
        foreach ($parametri_regioni as $param) {
            $id_radici_regione_array[] = $param->val_int;
        }
        $id_radici_regione = implode(', ', $id_radici_regione_array);
        $in_clause_1 = " AND viewableorganisationid IN ($id_dominio, $id_radici_regione"; // utenti supervisori
    } 
    
    // ottengo gli id dei domini facenti parte dell'albero sotteso a $id_dominio
    $info = recursivesubtree($id_dominio);

    foreach( $info as $row ) {
        // filtro i soli domini di direzione (terzo livello)
        if ($row->level == 3) {
            $in_clause_2 .= ", $row->id"; // utenti referenti di direzione
        }
    }
    $select .= $in_clause_1.$in_clause_2.')';
    
    return $DB->get_records_sql("SELECT user.id, username, user.idnumber, email, firstname, lastname, city, country,
                    lastaccess, confirmed, mnethostid, suspended, org.fullname as org_fullname, org2.fullname as viewable_org_fullname, org2.depthid as level
               FROM {user} user
               LEFT JOIN {$CFG->prefix}org_assignment oa ON user.id=oa.userid
               LEFT JOIN {$CFG->prefix}org org ON org.id=oa.organisationid
               LEFT JOIN {$CFG->prefix}org org2 ON org2.id=oa.viewableorganisationid
               WHERE $select");
}

/**
 * Restituisce TUTTI i referenti di settore della gerarchia, ovvero gli utenti che hanno per dominio di visibilità un dominio di 4o liv)
 * includendo pero' i soli utenti facenti parte dei sottoalberi dei domini radice GIUNTA, CONSIGLIO e ENTI ESTERNI
 * return array gli utenti che hanno per dominio di visibilità un dominio di 4o liv)
 * includendo pero' i soli utenti facenti parte dei sottoalberi dei domini radice GIUNTA, CONSIGLIO e ENTI ESTERNI
 */
function get_all_referenti_settore() {

    global $CFG, $DB;
    $select = "deleted <> 1 AND suspended <> 1";
    
    $select .= " AND org.depthid = 4 AND ("; // domini di 4o liv (settori)
    
    $parametri_regioni = get_parametri_by_prefix('p_f2_dominio_radice_regione_');
    $id_radici_regione_array = array();
    foreach ($parametri_regioni as $param) {
        $id_radici_regione_array[] = $param->val_int;
    }
    // settori facenti parte dei sottoalberi dei domini radice GIUNTA, CONSIGLIO e ENTI ESTERNI
    $select .= "org.path LIKE '%$id_radici_regione_array[0]%'";
    $select .= " OR org.path LIKE '%$id_radici_regione_array[1]%'";
    $select .= " OR org.path LIKE '%$id_radici_regione_array[2]%')";
    
    return $DB->get_records_sql("SELECT user.id
               FROM {user} user
               LEFT JOIN {$CFG->prefix}org_assignment oa ON user.id=oa.userid
               LEFT JOIN {$CFG->prefix}org org ON org.id=oa.viewableorganisationid
               WHERE $select");
}

/**
 * Restituisce TUTTI i referenti scuola della gerarchia, ovvero gli utenti che appartengono ad un dominio figlio di Scuole
 * return array gli utenti che appartengono ad un dominio figlio di Scuole
 */
function get_all_referenti_scuola() {

    global $CFG, $DB;
    $select = "deleted <> 1 AND suspended <> 1";
    
    $param = get_parametro('p_f2_radice_regione_scuola');
    $id_radice_scuole = $param->val_int;

    $scuole = recursivesubtree($id_radice_scuole);
    $id_scuole_arr = array();
    foreach ($scuole as $scuola) {
        $id_scuole_arr[] = $scuola->id;
    }
    $id_scuole = implode(", ", $id_scuole_arr);
    $select .= " AND oa.organisationid IN ($id_scuole)";
    
    return $DB->get_records_sql("SELECT user.id
               FROM {user} user
               LEFT JOIN {$CFG->prefix}org_assignment oa ON user.id=oa.userid
               WHERE $select");
}

/**
 * Restituisce tutti gli utenti facenti parte dell'albero sotteso al dominio di visibilità dell'utente $user_id
 * @param int $user_id  id dell'utente
 * @param boolean $exclude	opzionale, default FALSE, se TRUE esclude $user_id dalla lista di utenti restituiti
 * return object
 */
function get_visible_users_by_userid($user_id = NULL, $data = NULL, $exclude = false) {
    global $CFG, $DB, $USER;
	
    $user_id = is_null($user_id) ? intval($USER->id) : $user_id;
		
		//if site admin return all moodle users
		//$get=true, $search='', $confirmed=false, array $exceptions=null, $sort='firstname ASC',
		//$firstinitial='', $lastinitial='', $page='', $recordsperpage='', $fields='*', $extraselect='', array $extraparams=null
		/*if( is_siteadmin($user_id) ) {
			$obj		    = new stdClass();
		  $exclude = ($exclude) ? array($user_id) : '';
      $obj->dati	= get_users(true,$data->search_name,false,$exclude,'lastname ASC','','',$data->page,$data->perpage);
      $obj->count	= get_users(false,$data->search_name,false,$exclude,'lastname ASC','','',$data->page,$data->perpage);
			return $obj;
		}*/
    
    $select = " deleted <> 1";
    $exclude = ($exclude) ? ' AND user.id <> '.$user_id : '';
    
    list($id, $name) = get_user_viewable_organisation($user_id);
    
    if (!$id)
        return null;
    
    // ottengo gli id dei domini facenti parte dell'albero sotteso al dominio di visibilità dell'utente
    $info = recursivesubtree($id);

    $in_clause = " AND organisationid IN ($id";
    
    foreach( $info as $row )
        $in_clause .= ", $row->id";
    
    $in_clause .= ')';
    $select .= $in_clause;
    
    $order = (is_null($data)) ? "" : " ORDER BY ".$data->column." ".$data->sort;
    $filter = (is_null($data)) ? "" : " AND lastname like '%".mysql_escape_string($data->search_name)."%' ";
    $sql_str = "SELECT user.id, username, user.idnumber, email, firstname, lastname, city, country,
                                      lastaccess, confirmed, mnethostid, suspended, org.fullname as org_fullname, org2.fullname as viewable_org_fullname
                                 FROM {user} user
                                 LEFT JOIN {$CFG->prefix}org_assignment oa ON user.id=oa.userid
                                 LEFT JOIN {$CFG->prefix}org org ON org.id=oa.organisationid
                                 LEFT JOIN {$CFG->prefix}org org2 ON org2.id=oa.viewableorganisationid
                                 WHERE".$select."".$exclude."".$filter."";
    $users = (is_null($data)) ? $DB->get_records_sql($sql_str.$order) : $DB->get_records_sql($sql_str.$order,NULL,$data->page*$data->perpage,$data->perpage);
    
    
    $obj		= new stdClass();
    $obj->dati	= $users;
    $obj->count	= $DB->count_records_sql("SELECT count(*) FROM (".$sql_str.") as tmp");
    
    return $obj;
}

/**
 * Restituisce il dominio di appartenenza di un utente (id, fullname)
 * @param int $user_id id dell'utente
 * return object
 */
function get_user_organisation($user_id) {

    global $CFG, $DB;
    $select = "SELECT organisationid as id, org.fullname as org_fullname, org.shortname as org_shortname
                FROM {$CFG->prefix}org_assignment oa
                JOIN {$CFG->prefix}org org ON org.id=oa.organisationid
                WHERE userid = $user_id";

    $org = $DB->get_record_sql($select);

    if ($org)
        return array($org->id, $org->org_fullname, $org->org_shortname);
    else
        return NULL;
}

/**
 * Restituisce il dominio di visibilità di un utente (id, fullname)
 * @param int $user_id id dell'utente
 * return object
 */
function get_user_viewable_organisation($user_id) {

    global $CFG, $DB;
    $select = "SELECT viewableorganisationid as id, org.fullname as org_fullname, org.shortname as org_shortname
                FROM {$CFG->prefix}org_assignment oa
                JOIN {$CFG->prefix}org org ON org.id=oa.viewableorganisationid
                WHERE userid = $user_id";

    $viewableorg = $DB->get_record_sql($select);

    if ($viewableorg)
        return array($viewableorg->id, $viewableorg->org_fullname, $viewableorg->org_shortname);
    else
        return NULL;
}

/*
 * Restituisce il settore di un utente:
 * 		se l'utente appartiene ad un settore -> restituisce il settore di appartenenza
 * 		NULL in tutti gli altri casi
 * @param int $user_id id dell'utente
 * @return array il settore di un utente <id,name>
 */
function get_settore_utente($user_id) {
	global $DB;
	
        $root = get_root_framework();
    
    if (is_null($root)) return NULL;
    // Non esiste la gerarchia dei domini
    
    list($id, $fullname, $shortname) = get_user_organisation($user_id);
    // L'utente non appartiene ancora ad alcun dominio restituisce NULL
    if (!$id) return NULL;
    
    $level = $DB->get_field('org', 'depthid', array('id' => $id));
    if (in_array($level, array(1,2,3))) return NULL; // L'utente appartiene ad un dominio di livello 1st,2nd,3rd
    else return array('id' => $id, 'name' => $fullname, 'shortname' => $shortname);	
}

/**
 * Restituisce la direzione di un utente: 
 *      se l'utente appartiene ad un settore -> restituisce la sua direzione di appartenenza
 *      se l'utente appartiene già ad una direzione -> restituisce comunque la sua direzione
 *      se l'utente appartiene ad un dominio di secondo livello -> restituisce NULL
 *      se l'utente appartiene ad un dominio di primo livello -> restituisce NULL
 *      se l'utente NON appartiene ad un sottodominio di GIUNTA, CONSIGLIO o ENTI ESTERNI -> restituisce NULL
 *      se l'utente non appartiene ancora ad alcun dominio -> restituisce NULL
 * @param int $user_id id dell'utente
 * return array la direzione di un utente (id, name)
 */
function get_direzione_utente($user_id) {

    global $CFG, $DB;

    $root = get_root_framework();
    
    // non esiste la gerarchia dei domini
    if (is_null($root)) return NULL;
    
    list($id, $name, $shortname) = get_user_organisation($user_id);
    // se l'utente non appartiene ancora ad alcun dominio -> restituisce NULL
    if (!$id) return NULL;
    
    // ottengo il livello del dominio di appartenenza dell'utente
    $select_level = "SELECT depthid as level FROM {$CFG->prefix}org WHERE id = $id";
    $level = $DB->get_field_sql($select_level);
    
    // se l'utente appartiene ad un dominio di primo o secondo livello -> restituisce NULL
    if ($level == 1 || $level == 2) return NULL;
    
    // ottengo gli id dei domini di secondo livello GIUNTA, CONSIGLIO e ENTI ESTERNI
    $parametri_regioni = get_parametri_by_prefix('p_f2_dominio_radice_regione_');
    $id_radici_regione_array = array();
    foreach ($parametri_regioni as $param) {
        $id_radici_regione_array[] = $param->val_int;
    }
    $radici_regione = $id_radici_regione_array;
        
    // ottengo l'id del dominio padre del dominio di appartenenza dell'utente
    $select_parent = "SELECT parentid FROM {$CFG->prefix}org WHERE id = $id";
    $parentid = $DB->get_field_sql($select_parent);

    if ($level == 3) {
        // domini radice ammessi
        if (in_array((string) $parentid, $radici_regione)) {
            // se l'utente appartiene già ad una direzione -> restituisce comunque la sua direzione
            return array('id'=>$id, 'name'=>$name, 'shortname'=>$shortname);
        } else {
            // se l'utente appartiene ad un sottodominio di SCUOLE -> restituisce NULL
            return NULL;
        }
    } else {
        // l'utente appartiene ad un settore
        // ottengo l'id del dominio di secondo livello a cui appartiene il padre del dominio dell'utente
        $select_parent_liv2 = "SELECT parentid, id, shortname, fullname as name FROM {$CFG->prefix}org WHERE id = $parentid";
        $parentid_liv2 = $DB->get_record_sql($select_parent_liv2);
        if (in_array((string) $parentid_liv2->parentid, $radici_regione))
            return array('id'=>$parentid_liv2->id, 'name'=>$parentid_liv2->name, 'shortname'=>$parentid_liv2->shortname);
        else
            // se l'utente appartiene ad un sottodominio di SCUOLE -> restituisce NULL
            return NULL;
    }
}

/**
 * Restituisce le direzioni dell'alberatura dei domini appartenenti a GIUNTA, CONSIGLIO, o ENTI ESTERNI.
 * Se il parametro radice è specificato e valido (identificando GIUNTA, o CONSIGLIO, o EE) restituisce 
 * l'elenco delle sole direzioni di competenza. Altrimenti restituisce tutte le direzioni.
 * 
 * @global object $DB
 * @param int $radice Id di GIUNTA, CONSIGLIO, ENTI ESTERNI
 * @return array An array of objects. 
 */
function get_direzioni($radice = NULL) {
    global $DB;

    $root = get_root_framework();
    
    // non esiste la gerarchia dei domini
    if (is_null($root)) return NULL;
    
    // ottengo gli id dei domini di secondo livello GIUNTA, CONSIGLIO e ENTI ESTERNI
    $parametri_regioni = get_parametri_by_prefix('p_f2_dominio_radice_regione_');
    $id_radici_regione_array = array();
    foreach ($parametri_regioni as $param) {
        $id_radici_regione_array[] = $param->val_int;
    }
    unset($parametri_regioni, $param);
    
    if (isset($radice) && in_array($radice, $id_radici_regione_array)) {
        $id_radici_regione = $radice;
    } else {
        $id_radici_regione = $id_radici_regione_array;
    }
    list($sqlparents, $params) = $DB->get_in_or_equal($id_radici_regione);

    return $DB->get_records_sql("SELECT id, fullname, idnumber
                                 FROM {org}
                                 WHERE parentid $sqlparents", $params);
}


/**
 * Restituisce le direzioni dell'alberatura dei domini appartenenti a GIUNTA, CONSIGLIO o ENTI ESTERNI associati ad un corso
 * return array la direzione di un utente (id, name)
 */
function get_direzioni_from_courseid($courseid) {

	global $CFG, $DB;

	$root = get_root_framework();

	// non esiste la gerarchia dei domini
	if (is_null($root)) return NULL;

	// ottengo gli id dei domini di secondo livello GIUNTA, CONSIGLIO e ENTI ESTERNI
	$parametri_regioni = get_parametri_by_prefix('p_f2_dominio_radice_regione_');
	$id_radici_regione_array = array();
	foreach ($parametri_regioni as $param) {
		$id_radici_regione_array[] = $param->val_int;
	}
	$id_radici_regione = implode(', ', $id_radici_regione_array);

	$select = "parentid IN ($id_radici_regione)";

        // 2018 07 11 - modificata la query aggiungendo ' AND visible = 1 '
	return $DB->get_records_sql("SELECT id, fullname, idnumber
			FROM {$CFG->prefix}org
			WHERE $select AND visible = 1 AND NOT EXISTS (SELECT id from {$CFG->prefix}f2_course_org_mapping com where com.courseid = $courseid AND mdl_org.id = com.orgid);");
	}
	
	
/**
 * Restituisce la lista delle organizzazioni associate al corso
 * return array  (id, name, code)
 */
function get_tabid_org_by_course($courseid) {

	global $CFG, $DB;
	
	return $DB->get_records_sql("SELECT c_x_m.id, o.fullname, o.idnumber
			FROM {$CFG->prefix}f2_course_org_mapping c_x_m, {$CFG->prefix}course c,{$CFG->prefix}org o
			WHERE o.id =c_x_m.orgid AND c_x_m.courseid = c.id AND c_x_m.courseid = $courseid");
	}


/**
 * Associa l'organizzazione al corso ed iscrive gli utenti con queldominio di visibilità al corso
 * return 
 */
function set_org_by_course_enroll($orgid,$courseid,$shortrole) {
	global $CFG, $DB;
	$timestart = time();
	$timestart = make_timestamp(date('Y', $timestart), date('m', $timestart), date('d', $timestart), 0, 0, 0);
	$timeend = 0;
	$roleid = $DB->get_field('role', 'id', array('shortname'=>$shortrole));
	$ins_org = new stdClass();
	$ins_org->courseid  = $courseid;
	$ins_org->orgid     = $orgid;
	//$context = get_context_instance(CONTEXT_COURSE, $courseid);
	$context = context_course::instance($courseid);
	
    $transaction = $DB->start_delegated_transaction();
    try {
	  //  if (!$enrol_manual = enrol_get_plugin('manual')) {
	  //  	throw new coding_exception('Can not instantiate enrol_manual');
	  //  }
	    
	  //	$instance = $DB->get_record('enrol', array('courseid'=>$courseid, 'enrol'=>'manual'), '*', MUST_EXIST);
		
		if(!$DB->get_field('f2_course_org_mapping', 'id', array('courseid'=>$ins_org->courseid,'orgid'=>$ins_org->orgid)))
			$DB->insert_record('f2_course_org_mapping', $ins_org);
		// se il fornitore è una scuola allora utilizzo il campo organisationid
		if($shortrole=='referentescuola')
			$field_table = 'organisationid';
		else 	
			$field_table = 'viewableorganisationid';
		
		$users_org = $DB->get_records('org_assignment', array($field_table => $orgid));
		foreach ($users_org as $user_org) {
			role_assign($roleid, $user_org->userid, $context->id);
			//$enrol_manual->enrol_user($instance, $user_org->userid, $roleid, $timestart, $timeend);						
		}
		$transaction->allow_commit();

	} catch (Exception $e) {
        $transaction->rollback($e);
    }
}

/**
 * Elimina associazione dell'organizzazioneal al corso
 * return INT (id)
 */
function remove_org_by_course_enroll($orgid,$courseid,$shortrole) {
	global $CFG, $DB;
	$timestart = time();
	$timestart = make_timestamp(date('Y', $timestart), date('m', $timestart), date('d', $timestart), 0, 0, 0);
	$timeend = 0;
	$roleid = $DB->get_field('role', 'id', array('shortname'=>$shortrole));
	$context = get_context_instance(CONTEXT_COURSE, $courseid);

    $transaction = $DB->start_delegated_transaction();
    try {
	 //   if (!$enrol_manual = enrol_get_plugin('manual')) {
	 //   	throw new coding_exception('Can not instantiate enrol_manual');
	 //   }

		$instance = $DB->get_record('enrol', array('courseid'=>$courseid, 'enrol'=>'manual'), '*', MUST_EXIST);
		// se il fornitore è una scuola allora utilizzo il campo organisationid
		if($shortrole=='referentescuola')
			$field_table = 'organisationid';
		else
			$field_table = 'viewableorganisationid';
		
		$users_org = $DB->get_records('org_assignment', array($field_table => $orgid));
		foreach ($users_org as $user_org) {		
			role_unassign($roleid, $user_org->userid, $context->id);
		//	if(count(get_user_roles($context, $user_org->userid))==0)
		//		$enrol_manual->unenrol_user($instance, $user_org->userid);
		}
		$tab_id = $DB->get_field('f2_course_org_mapping', 'id', array('courseid'=>$courseid,'orgid'=>$orgid));
		if($tab_id)
			$DB->delete_records('f2_course_org_mapping', array('id'=>$tab_id));
		$transaction->allow_commit();

	} catch (Exception $e) {
        $transaction->rollback($e);
    }
}

	
/**
 * Restituisce i settori presenti sotto una data direzione
 * @param int $id_direzione id del dominio direzione
 * return array i settori presenti sotto una data direzione (id, fullname)
 */
function get_settori_by_direzione($id_direzione) {

    global $CFG, $DB;

    if (!isDirezione($id_direzione))
        return null;
    
    $info = recursivesubtree($id_direzione);
    $res = array();
    
    foreach( $info as $row ) {
        $singleRes = new stdClass();
        $singleRes->id = $row->id;
        $singleRes->fullname = $row->fullname;
				$singleRes->shortname = $row->shortname;
        $res[] = $singleRes;
    }
    
    return $res;
}

/**
 * Restituisce il numero di utenti presenti in una data direzione (compresi gli utenti nei sottodomini)
 * @param int $id_direzione id del dominio direzione
 * return int il numero di utenti presenti in una data direzione (compresi gli utenti nei sottodomini)
 */
function get_numero_utenti_in_direzione($id_direzione) {
    global $CFG, $DB;
	
    $select = " deleted <> 1";
    
    if (!isDirezione($id_direzione))
        return null;
    
    // ottengo gli id dei domini facenti parte dell'albero sotteso al dominio di visibilità dell'utente
    $info = recursivesubtree($id_direzione);

    $in_clause_1 = " AND organisationid IN (";
    $in_clause_2 = "";
    foreach( $info as $row ) {
            $in_clause_2 .= ", $row->id";
    }
    $in_clause_2 = substr($in_clause_2, 2); // rimuovo la prima virgola ', '
    $select .= $in_clause_1.$in_clause_2.')';
    
    return $DB->count_records_sql("SELECT COUNT(DISTINCT user.id)
                                 FROM {user} user
                                 LEFT JOIN {$CFG->prefix}org_assignment oa ON user.id=oa.userid
                                 WHERE $select");
}

/**
 * Restituisce TRUE se l'id del dominio è l'id di una direzione, FALSE altrimenti
 * @param int $id_dominio id del dominio
 * return boolean TRUE se l'id del dominio è l'id di una direzione, FALSE altrimenti il numero di utenti presenti in una data direzione (compresi gli utenti nei sottodomini)
 */
function isDirezione ($id_dominio) {
    
    global $CFG, $DB;
    
    if (!$id_dominio) return false;
    
    $level = $DB->get_field('org', 'depthid', array('id' => $id_dominio));
    
    if (!$level) return false;
    
    if ($level == 3) 
        return true;
    else
        return false;
}

/**
 * Restituisce TRUE se l'id del dominio è l'id di un settore, FALSE altrimenti
 * @param int $id_dominio id del dominio
 * return boolean TRUE se l'id del dominio è l'id di un settire, FALSE altrimenti
 */
function isSettore ($id_dominio) {
    
    global $CFG, $DB;
    
    if (!$id_dominio) return false;
    
    $level = $DB->get_field('org', 'depthid', array('id' => $id_dominio));
    
    if (!$level) return false;
    
    if ($level == 4) 
        return true;
    else
        return false;
}

/**
 * Restituisce gli utenti presenti nella direzione proponente del corso passato come parametro
 * @param int $id_corso id del corso
 * return array gli utenti presenti nella direzione proponente del corso passato come parametro
 */
function get_referenti_direzione_proponente($id_corso) {
    global $DB;
    $root = get_root_framework();
    
    if (is_null($root)) return NULL;
    
    if (is_null($id_corso)) return NULL;
    
    $id_dir_proponente = $DB->get_field('f2_anagrafica_corsi', 'dir_proponente', array('courseid'=>$id_corso), MUST_EXIST);
    
    if (!isDirezione($id_dir_proponente)) return NULL;
    
    return get_referenti_direzione($id_dir_proponente);
}

/**
 * Restituisce il codice html da inserire in un form per poter scegliere un dominio
 * @param $org_label_id id del tag span per la visualizzazione del nome del dominio selezionato
 * @param $org_hidden_name id dell'input type hidden per mantenere il valore dell'id del dominio selezionato
 * @param $button_label etichetta del bottone che fa comparire il popup
 * @param $div_id id del tag div che deve contenere la treeview
 * @param $hierarchy stringa rappresentante la gerarchia dei domini per la costruzione del treeview (secondo i criteri della Dynatree)
 * @param $organisation_title (opzionale) contiene la prevalorizzazione di un dominio selezionato in precedenza
 * @param $callbackhandler (opzionale) funzione javascript da eseguire alla callback
 * @param $select_only_leaves (opzionale) booleano che determina se devono essere selezionabili solo le foglie della treeview
 * return String il codice html da inserire in un form per poter scegliere un dominio
 */
function get_organisation_picker_html($org_label_id, $org_hidden_name, $button_label, $div_id, $hierarchy, $organisation_title='',$callbackhandler='', $select_only_leaves = false) {
    
    $select_only_leaves_properties = '';
    if ($select_only_leaves) {
        $select_only_leaves_properties = 'clickFolderMode: 2,
                                          onExpand: function(node) {
                                            $("input[name=\''.$org_hidden_name.'\']").val("");
                                            $("#'.$org_label_id.'").text("");
                                          },';
    }
    
    return '<input type="button" id="id_button_'.$div_id.'" value="'.$button_label.'"
                onClick="openPopup'.$div_id.'();">
            <span id="'.$org_label_id.'">'.htmlentities($organisation_title).'</span>
            <script type="text/javascript">
              function openPopup'.$div_id.'() {
                $.blockUI({ message: \'<div id="'.$div_id.'" style="width: 600px; height: 300px; text-align:left;"></div><div style="position: relative; bottom:-18px"><input type="button" value="Ok" onClick="closePopup();'.$callbackhandler.'"></div>\', fadeIn: 500, fadeOut: 500, css: { backgroundColor: \'#CCCCCC\', top: \'80px\', left: ($(window).width() - 600) /2 + \'px\', width: \'600px\', height: \'350px\', cursor:\'inherit\' }});
	        $(function(){
		      $("#'.$div_id.'").dynatree({
                      autoCollapse: true,
                      fx: { height: "toggle", duration: 200 },'.
                      $select_only_leaves_properties.'
		      onActivate: function(node) {         
			 $("input[name=\''.$org_hidden_name.'\']").val(node.data.key);
			 $("#'.$org_label_id.'").text(node.data.title);
		      },
		      children: ['.$hierarchy.']
		    });
	      });
	      }
              
              function closePopup() {
                $.unblockUI();
              }
            </script>';
}

/**
 * Restituisce il codice html da inserire in un form per poter scegliere un dominio (uguale al metodo precedente ma suppone venga fatto uso di una textbox nel form anziché di uno span)
 * @param $org_label_id id del campo textbox per la visualizzazione del nome del dominio selezionato
 * @param $org_hidden_name id dell'input type hidden per mantenere il valore dell'id del dominio selezionato
 * @param $button_label etichetta del bottone che fa comparire il popup
 * @param $div_id id del tag div che deve contenere la treeview
 * @param $hierarchy stringa rappresentante la gerarchia dei domini per la costruzione del treeview (secondo i criteri della Dynatree)
 * @param $organisation_title (opzionale) contiene la prevalorizzazione di un dominio selezionato in precedenza
 * @param $callbackhandler (opzionale) funzione javascript da eseguire alla callback
 * @param $select_only_leaves (opzionale) booleano che determina se devono essere selezionabili solo le foglie della treeview
 * return String il codice html da inserire in un form per poter scegliere un dominio
 */
function get_organisation_picker_html_with_text_box($org_label_id, $org_hidden_name, $button_label, $div_id, $hierarchy, $organisation_title='',$callbackhandler='', $select_only_leaves = false) {
    
    $select_only_leaves_properties = '';
    if ($select_only_leaves) {
        $select_only_leaves_properties = 'clickFolderMode: 2,
                                          onExpand: function(node) {
                                            $("input[name=\''.$org_hidden_name.'\']").val("");
                                            $("#id_'.$org_label_id.'").val("");
                                          },';
    }
    
    return '<input type="button" id="id_button_'.$div_id.'" value="'.$button_label.'"
                onClick="openPopup'.$div_id.'();">
            <script type="text/javascript">
              function openPopup'.$div_id.'() {
                $.blockUI({ message: \'<div id="'.$div_id.'" style="width: 600px; height: 300px; text-align:left;"></div><div style="position: relative; bottom:-18px"><input type="button" value="Ok" onClick="closePopup();'.$callbackhandler.'"></div>\', fadeIn: 500, fadeOut: 500, css: { backgroundColor: \'#CCCCCC\', top: \'80px\', left: ($(window).width() - 600) /2 + \'px\', width: \'600px\', height: \'350px\', cursor:\'inherit\' }});
	        $(function(){
		      $("#'.$div_id.'").dynatree({
                      autoCollapse: true,
                      fx: { height: "toggle", duration: 200 },'.
                      $select_only_leaves_properties.'
		      onActivate: function(node) {         
			 $("input[name=\''.$org_hidden_name.'\']").val(node.data.key);
			 $("#id_'.$org_label_id.'").val(node.data.title);
		      },
		      children: ['.$hierarchy.']
		    });
	      });
	      }
              
              function closePopup() {
                $.unblockUI();
              }
            </script>';
}

/**
 * Rimuove le assegnazioni dei ruoli di gestione (Supervisori di secondo livello e Referenti formativi) su tutti i contesti per l'utente passato come parametro
 * @param int $user_id id dell'utente
 * @param boolean $do_replacement indica se si vuole effettuare una sostituzione dell'utente con un altro utente
 * @param int $replacement_user_id id dell'utente che subentra
 * return void
 */
function removeUserAssignments($user_id, $replacement_user_id = 0) {
    
    global $CFG, $DB;
    
    if (!$user_id) return;
    if ($replacement_user_id) 
        $do_replacement = true;
    else
        $do_replacement = false;
    
    if ($do_replacement) {
        if (!$replacement_user_id) return;
        list($dominio_visibilita1, $name) = get_user_viewable_organisation($user_id);
        list($dominio_visibilita2, $name) = get_user_viewable_organisation($replacement_user_id);
        if ($dominio_visibilita1 != $dominio_visibilita2) {
            // errore: i due domini di visibilità devono corrispondere!
            return;
        }
    }
    
    // ottengo gli id dei ruoli Supervisore di secondo livello e Referente formativo
    // (il ruolo del Supervisore di primo livello è un ruolo di sistema, pertanto è sufficiente rimuovere tale ruolo all'utente)
    $parametro_id_ruolo_ref_form = get_parametro('p_f2_id_ruolo_referente_formativo');
//    $parametro_id_ruolo_sup_liv2 = get_parametro('p_f2_id_ruolo_supervisore_2_liv');
//    $id_ruoli = $parametro_id_ruolo_ref_form->val_int.', '.$parametro_id_ruolo_sup_liv2->val_int;

    $ras = $DB->get_records_sql("SELECT * FROM {$CFG->prefix}role_assignments
        WHERE userid = $user_id AND roleid = $parametro_id_ruolo_ref_form->val_int");

    foreach($ras as $ra) {
        if (!$do_replacement) {
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
        } else {
            // verifico che non esista già un'assegnazione per l'utente $replacement_user_id con lo stesso ruolo e contesto
            $already_exists = $DB->get_records_sql("SELECT * FROM {$CFG->prefix}role_assignments
                WHERE userid = $replacement_user_id AND roleid = $ra->roleid AND contextid = $ra->contextid");
            if (!$already_exists) {
                // aggiorno - modifico l'utente
                $assignment->id = $ra->id;
                $assignment->contextid = $ra->contextid;
                $assignment->roleid = $ra->roleid;
                $assignment->userid = $replacement_user_id;
                $assignment->timemodified = time();
                $assignment->modifierid = 2;
                // effettuo la sostituzione dell'utente
                $DB->update_record('role_assignments', $assignment);
            } else {
                // rimuovo il ruolo
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
        }
    }
}

/**
 * Restituisce le informazioni sui corsi obiettivo che l'utente passato come parametro gestisce (contextLevel = 50 => corsi)
 * @param int $user_id id dell'utente
 * @param int $page The page or records to return
 * @param int $recordsperpage The number of records to return per page
 * return array
 */
function get_corsi_obiettivo_by_utente($userid, $page=0, $recordsperpage=0) {
    
    global $DB, $CFG;
    
    if (!$userid) return false;
    
    // ottengo gli id dei ruoli Supervisore di secondo livello e Referente formativo
    // (il ruolo del Supervisore di primo livello è un ruolo di sistema, pertanto è sufficiente rimuovere tale ruolo all'utente)
    $parametro_id_ruolo_ref_form = get_parametro('p_f2_id_ruolo_referente_formativo');
//    $parametro_id_ruolo_sup_liv2 = get_parametro('p_f2_id_ruolo_supervisore_2_liv');
//    $id_ruoli = $parametro_id_ruolo_ref_form->val_int.', '.$parametro_id_ruolo_sup_liv2->val_int;
    $anno_corrente = get_anno_formativo_corrente();
    $anno_precedente = get_anno_formativo_corrente() - 1;
    
    return $DB->get_records_sql("SELECT c.fullname, an.anno, r.name as ruolo
        FROM {$CFG->prefix}role_assignments ras
        LEFT JOIN {$CFG->prefix}context ctx ON ras.contextid = ctx.id
        LEFT JOIN {$CFG->prefix}course c ON c.id = ctx.instanceid
        LEFT JOIN {$CFG->prefix}f2_anagrafica_corsi an ON an.courseid = c.id
        LEFT JOIN {role} r ON r.id = ras.roleid
        WHERE ctx.contextlevel = 50 AND ras.userid = $userid AND ras.roleid = $parametro_id_ruolo_ref_form->val_int AND an.anno IN($anno_precedente,$anno_corrente) ",
        array(), $page, $recordsperpage); // contextlevel = 50 => contesto di corso
}

/**
 * Restituisce il numero dei corsi obiettivo che l'utente passato come parametro gestisce (contextLevel = 50 => corsi)
 * @param int $user_id id dell'utente
 * @param int $page The page or records to return
 * @param int $recordsperpage The number of records to return per page
 * return int
 */
function get_corsi_obiettivo_by_utente_count($userid) {
    
    global $DB, $CFG;
    
    if (!$userid) return false;
    
    // ottengo gli id dei ruoli Supervisore di secondo livello e Referente formativo
    // (il ruolo del Supervisore di primo livello è un ruolo di sistema, pertanto è sufficiente rimuovere tale ruolo all'utente)
    $parametro_id_ruolo_ref_form = get_parametro('p_f2_id_ruolo_referente_formativo');
//    $parametro_id_ruolo_sup_liv2 = get_parametro('p_f2_id_ruolo_supervisore_2_liv');
//    $id_ruoli = $parametro_id_ruolo_ref_form->val_int.', '.$parametro_id_ruolo_sup_liv2->val_int;
    $anno_corrente = get_anno_formativo_corrente();
    $anno_precedente = get_anno_formativo_corrente() - 1;
    
    return $DB->count_records_sql("SELECT COUNT(c.fullname)
        FROM {$CFG->prefix}role_assignments ras
        LEFT JOIN {$CFG->prefix}context ctx ON ras.contextid = ctx.id
        LEFT JOIN {$CFG->prefix}course c ON c.id = ctx.instanceid
        LEFT JOIN {$CFG->prefix}f2_anagrafica_corsi an ON an.courseid = c.id
        LEFT JOIN {role} r ON r.id = ras.roleid
        WHERE ctx.contextlevel = 50 AND ras.userid = $userid AND ras.roleid = $parametro_id_ruolo_ref_form->val_int AND an.anno IN($anno_precedente,$anno_corrente) "); // contextlevel = 50 => contesto di corso
}

/**
 * Restituisce le informazioni sulle categorie che l'utente passato come parametro gestisce (contextLevel = 40 => categorie)
 * @param int $user_id id dell'utente
 * return array
 */
function get_categorie_by_utente($userid) {
    
    global $DB, $CFG;
    
    if (!$userid) return false;
    
    // ottengo gli id dei ruoli Supervisore di secondo livello e Referente formativo
    // (il ruolo del Supervisore di primo livello è un ruolo di sistema, pertanto è sufficiente rimuovere tale ruolo all'utente)
    $parametro_id_ruolo_ref_form = get_parametro('p_f2_id_ruolo_referente_formativo');
//    $parametro_id_ruolo_sup_liv2 = get_parametro('p_f2_id_ruolo_supervisore_2_liv');
//    $id_ruoli = $parametro_id_ruolo_ref_form->val_int.', '.$parametro_id_ruolo_sup_liv2->val_int;
    
    return $DB->get_records_sql("SELECT c.name, r.name as ruolo
        FROM {$CFG->prefix}role_assignments ras
        LEFT JOIN {$CFG->prefix}context ctx ON ras.contextid = ctx.id
        LEFT JOIN {$CFG->prefix}course_categories c ON c.id = ctx.instanceid
        LEFT JOIN {role} r ON r.id = ras.roleid
        WHERE ctx.contextlevel = 40 AND ras.userid = $userid AND ras.roleid = $parametro_id_ruolo_ref_form->val_int"); // contextlevel = 40 => contesto di categoria
}

/**
 * Restituisce il livello (1,2,3,4) del dominio di visibilità di appartenenza dell'utente passato come parametro
 * @param int $user_id id dell'utente
 * return int
 */
function get_livello_dominio_visibilita_utente($user_id) {
    
    global $CFG, $DB;
    $select = "SELECT depthid as level
                FROM {$CFG->prefix}org_assignment oa
                JOIN {$CFG->prefix}org org ON org.id=oa.viewableorganisationid
                WHERE userid = $user_id";

    $viewableorg = $DB->get_record_sql($select);

    if ($viewableorg)
        return $viewableorg->level;
    else
        return -1;
}

/**
 * Restituisce true se l'utente passato come parametro è un Supervisore di primo livello
 * @param int $user_id id dell'utente
 * return int
 */
function isSupervisore1($user_id) {
    
    $level = get_livello_dominio_visibilita_utente($user_id);
    if ($level == 1) // l'utente ha dominio di visibilità sulla radice della gerarchia dei domini (livello 1)
        return true;
    else 
        return false;
}

/**
 * Restituisce true se l'utente passato come parametro è un Supervisore di secondo livello
 * @param int $user_id id dell'utente
 * return int
 */
function isSupervisore2($user_id) {
    
    $level = get_livello_dominio_visibilita_utente($user_id);
    if ($level == 2) // l'utente ha dominio di visibilità su un dominio che si trova al secondo livello della gerarchia dei domini (livello 2)
        return true;
    else
        return false;
}

function isSupervisore($user_id)
{
	if (isSupervisore1($user_id) or isSupervisore2($user_id)) return true;
	else return false;
}

/**
 * Restituisce true se l'utente passato come parametro è un Supervisore di Consiglio, ovvero ha visibilità sul dominio Consiglio nella gerarchia dei domini
 * @param int $user_id id dell'utente
 * return int
 */
function isSupervisoreConsiglio($user_id) {
    
    $dominio_radice_regione_consiglio = get_parametro('p_f2_dominio_radice_regione_consiglio');
    list($dominio_visibilita_id, $dominio_visibilita_name) = get_user_viewable_organisation($user_id);
    return ($dominio_visibilita_id == $dominio_radice_regione_consiglio->val_int); // l'utente ha dominio di visibilità sul dominio Consiglio
}

/**
 * Restituisce true se l'utente passato come parametro è un Referente di direzione (aka Referente formativo)
 * @param int $user_id id dell'utente
 * return int
 */
function isReferenteDiDirezione($user_id) {
    
    $level = get_livello_dominio_visibilita_utente($user_id);
    // l'utente ha dominio di visibilità su un dominio di terzo livello nella gerarchia dei domini (direzione: livello 3)
    return ($level == 3) && !isReferenteScuola($user_id);
}


/**
 * Restituisce true se l'utente passato come parametro è un Referente di scuola 
 * @param int $user_id id dell'utente
 * return int
 */
function isReferenteScuola($user_id){
	global $DB;
    $result = FALSE;
	$sql_dominio_users ="SELECT
					   	userid,
					    organisationid
					 FROM
						mdl_org_assignment
					WHERE 
						userid = ".$user_id;
	
	$dominio_users = $DB->get_record_sql($sql_dominio_users);
	if($dominio_users){
        $result = isScuola($dominio_users->organisationid);
	}
	return $result;
}

/**
 * Restituisce true se l'utente passato come parametro è un Referente di settore
 * @param int $user_id id dell'utente
 * return int
 */
function isReferenteDiSettore($user_id) {
    
    $level = get_livello_dominio_visibilita_utente($user_id);
    return ($level == 4); // l'utente ha dominio di visibilità su un dominio che si trova al quarto livello della gerarchia dei domini (settore: livello 4)
}

/**
 * Restituisce gli utenti appartenenti ad un dato dominio
 * @param int $id_dominio id del dominio ricercato
 * return array gli utenti appartenenti ad un dato dominio
 */
function get_utenti_by_dominio_appartenenza($id_dominio) {
    global $CFG, $DB;
	
    $select = " deleted <> 1";
    
    $select .= " AND organisationid = $id_dominio";
    
    return $DB->get_records_sql("SELECT user.*
                                 FROM {user} user
                                 JOIN {$CFG->prefix}org_assignment oa ON user.id=oa.userid
                                 WHERE $select");
}

/**
 * Restituisce gli utenti aventi un dato dominio di visibilità
 * @param int $id_dominio id del dominio ricercato
 * return array gli utenti aventi un dato dominio di visibilità
 */
function get_utenti_by_dominio_visibilita($id_dominio) {
    global $CFG, $DB;
	
    $select = " deleted <> 1";
    
    $select .= " AND viewableorganisationid = $id_dominio";
    
    return $DB->get_records_sql("SELECT user.*
                                 FROM {user} user
                                 JOIN {$CFG->prefix}org_assignment oa ON user.id=oa.userid
                                 WHERE $select");
}

/**
 * Restituisce la url per il download della scheda progetto di un corso
 * @param int $courseid id del corso
 * return string la url per il download della scheda progetto di un corso
 */
function get_url_scheda_progetto($courseid) {
    global $CFG, $DB;
    
    $scheda_progetto = get_parametro('p_f2_scheda_progetto');
    $url = get_parametro('p_f2_url');
	
    $query = "SELECT cm.id
                FROM {$CFG->prefix}course_modules cm
                JOIN {$CFG->prefix}url url on url.id = cm.instance
                JOIN {$CFG->prefix}modules m on m.id = cm.module
                WHERE url.course = $courseid AND url.name = '$scheda_progetto->val_char' AND m.name = '$url->val_char';";
    
    $resource = $DB->get_record_sql($query);
    if (isset($resource->id))
        return "{$CFG->wwwroot}/mod/url/view.php?id=$resource->id";
    else
        return false;
}

function get_block_id($pluginname_db) {
    global $CFG, $DB;
    
    $query = "select i.id from {$CFG->prefix}block b
        join {$CFG->prefix}block_instances i on b.name = i.blockname 
        where b.name ='$pluginname_db' AND i.parentcontextid = 1";
    
    $block = $DB->get_record_sql($query, array());
    if (isset($block->id))
        return $block->id;
    else
        return false;
}

function is_referente_scuola_su_corso($courseid, $userid=null) {
    global $DB, $USER, $CFG;
    
    if ($userid == null) $userid = $USER->id;
    
    $sql = "SELECT roleid
        FROM {$CFG->prefix}role_assignments ass
        JOIN {$CFG->prefix}context c on (c.id = ass.contextid)
        WHERE ass.userid = $userid and c.contextlevel = 50 and c.instanceid = $courseid";
        
    $rs = $DB->get_record_sql($sql);
    
    if (is_null($rs) || !$rs) return false;
    
    $parametro_id_ruolo_ref_scuola = get_parametro('p_f2_id_ruolo_referente_scuola');
    if ($rs->roleid == $parametro_id_ruolo_ref_scuola->val_int)
        return true;
    else
        return false;
}

// restituisce tutti i corsi di cui un utente è Referente Scuola
function get_corsi_referente_scuola($userid=null) {
    global $DB, $USER, $CFG;
    
    if ($userid == null) $userid = $USER->id;
    
    $parametro_id_ruolo_ref_scuola = get_parametro('p_f2_id_ruolo_referente_scuola');
    $sql = "SELECT c.instanceid as id
        FROM {$CFG->prefix}context c
        JOIN {$CFG->prefix}role_assignments ass ON (c.id = ass.contextid)
        WHERE ass.userid = $userid and c.contextlevel = 50 and ass.roleid = $parametro_id_ruolo_ref_scuola->val_int";
        
    $rs = $DB->get_records_sql($sql);
    
    if (is_null($rs)) return false;
    
    $corsi = array();
    foreach ($rs as $corso) {
        $corsi[] = $corso->id;
    }
        
    return implode(', ', $corsi);
}

// restituisce tutti i corsi di cui un utente è Referente di direzione (referente formativo)
function get_corsi_referente_direzione($userid=null) {
    global $DB, $USER, $CFG;
    
    if ($userid == null) $userid = $USER->id;
    
    $parametro_id_ruolo_ref_form = get_parametro('p_f2_id_ruolo_referente_formativo');
    $sql = "SELECT c.instanceid as id, c.contextlevel as livello
        FROM {$CFG->prefix}context c
        JOIN {$CFG->prefix}role_assignments ass ON (c.id = ass.contextid)
        WHERE ass.userid = $userid and c.contextlevel IN (40, 50) and ass.roleid = $parametro_id_ruolo_ref_form->val_int";
        
    $rs = $DB->get_records_sql($sql);
    
    if (is_null($rs)) return false;
    
    $corsi = array();
    $cat = array();
    foreach ($rs as $elem) {
        if ($elem->livello == 50)
            $corsi[] = $elem->id; // inizio a mettere nel risultato tutti i corsi direttamente asseganti al referente formativo
        else
            $cat[] = $elem->id;
    }
    $categories = implode(", ", $cat);
    
    if ($categories != "") {
        $sql_cat = "SELECT id
            FROM {$CFG->prefix}course
            WHERE category IN ($categories)";

        $rs_cat = $DB->get_records_sql($sql_cat);
        foreach ($rs_cat as $elem) {
            $corsi[] = $elem->id; // aggiungo al risultato i corsi appartenenti ad una categoria assegnata al referente formativo
        }
    }
        
    return implode(', ', $corsi);
}

/**
 * Restituisce l'id del dominio padre di un dominio
 * @param int $iddominio id del dominio
 * return l'id del dominio padre di un dominio
 */
function get_dominio_padre($iddominio) {

    global $CFG, $DB;

    $root = get_root_framework();
    
    // non esiste la gerarchia dei domini
    if (is_null($root)) return NULL;
    
    $select_path = "SELECT path FROM {$CFG->prefix}org WHERE id = $iddominio";
    $path = $DB->get_field_sql($select_path);

    $pos = strpos($path, "/$iddominio");
    $ancestors = substr($path, 1, $pos-1);
//    var_dump($ancestors);
    $ancestors_arr = explode('/', $ancestors);
    return $ancestors_arr[sizeof($ancestors_arr)-1];
}

/**
 * Restituisce gli id dei corsi di tipo $course_types condivisi con il dominio $orgid
 * @global object $DB
 * @param  int $orgid
 * @param  array $course_types Specifica il/i tipo/i dei corsi da restituire
 * @param  int $year Anno formativo
 * @return array
 */
function get_courses_by_org($orgid, $course_types, $year) {
	global $DB;
    $params = array($orgid, $year);
    list($insql, $inparams) = $DB->get_in_or_equal($course_types);
    $q = "select distinct com.courseid
          from 
        mdl_f2_course_org_mapping com
          inner join {f2_anagrafica_corsi} ac on ac.courseid = com.courseid
          inner join {course} c on c.id = com.courseid
        where com.orgid = ?
          and ac.anno = ?
          and ac.course_type $insql";
	return $DB->get_records_sql($q, array_merge($params, $inparams));
}

/**
 * Restituisce true se orgid è un dominio figlio del dominio "Scuole".
 * False altrimenti.
 * @param int $orgid Id dominio
 * @return boolean
 */
function isScuola($orgid) {
    global $DB;
    $result = FALSE;
    if($orgid > 0) {
		$param = get_parametro('p_f2_radice_regione_scuola');
		$id_radice_scuole = $param->val_int;
        
        $path = $DB->get_field('org', 'path', array('id'=>$orgid));
        if(strlen($path)) {
            $domains = explode("/", $path);
            $result = in_array($id_radice_scuole, $domains);
        }
    }
	return $result;
}

/**
 * Restituisce gli id dei corsi su cui l'utente ha ruolo $roleid.
 * @global object $DB
 * @param int $userid
 * @param int $roleid
 * @return array
 */
function get_user_courses_by_role($userid, $roleid) {
    global $DB;
    $q = "
select ctx.instanceid as courseid
from {role_assignments} ra
  inner join {context} ctx on ctx.id = ra.contextid
  inner join {course} c on c.id = ctx.instanceid
where ra.userid = :userid
  and ra.roleid = :roleid
  and ctx.contextlevel = :level";
    
    return $DB->get_records_sql($q, array('userid'=>$userid,'roleid'=>$roleid,'level'=>CONTEXT_COURSE));
}
