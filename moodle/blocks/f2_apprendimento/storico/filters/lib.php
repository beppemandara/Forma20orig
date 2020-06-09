<?php

require_once($CFG->dirroot.'/f2_lib/core.php');
require_once($CFG->dirroot.'/blocks/f2_apprendimento/storico/filters/text.php');
require_once($CFG->dirroot.'/blocks/f2_apprendimento/storico/filters/filter_forms.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');

/**
 * Courses filtering wrapper class.
 */
class my_editions_filtering {
    var $_fields;
    var $_addform;
    var $_activeform;

    /**
     * Contructor
     * @param array array of visible editions items
     * @param string base url used for submission/return, null if the same of current page
     * @param array extra page parameters
     * @param boolean $showfullsearch if true show titolo search box by default, otherwise show shortname search box
     */
    function my_editions_filtering($fieldnames=null, $baseurl=null, $extraparams=null, $showfullsearch=true) {
        global $SESSION;
        $filtername = 'my_editions_filtering';

        if (!isset($SESSION->{$filtername})) {
            $SESSION->{$filtername} = array();
        }

        if (empty($fieldnames)) {
            $fieldnames = array('idnumber'=> (int) !$showfullsearch);
        }
        $this->_fields  = array();

        foreach ($fieldnames as $fieldname=>$advanced) {
            if ($field = $this->get_field($fieldname, $advanced)) {
                $this->_fields[$fieldname] = $field;
            }
        }
        
        // first the new filter form
        $this->_addform = new editions_add_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams));
        
        if ($adddata = $this->_addform->get_data(false)) {
            foreach($this->_fields as $fname=>$field) {
                $data = $field->check_data($adddata);
                if ($data === false) {
                    continue; // nothing new
                }
                if (!array_key_exists($fname, $SESSION->{$filtername})) {
                    $SESSION->{$filtername}[$fname] = array();
                }
                $SESSION->{$filtername}[$fname][] = $data;
            }
            // clear the form
            $_POST = array();
            $this->_addform = new editions_add_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams));
        }
        
        // now the active filters
        $this->_activeform = new editions_active_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams));
        if ($adddata = $this->_activeform->get_data(false)) {
            if (!empty($adddata->removeall)) {
                $SESSION->{$filtername} = array();

            } else if (!empty($adddata->removeselected) and !empty($adddata->filter)) {
                foreach($adddata->filter as $fname=>$instances) {
                    foreach ($instances as $i=>$val) {
                        if (empty($val)) {
                            continue;
                        }
                        unset($SESSION->{$filtername}[$fname][$i]);
                    }
                    if (empty($SESSION->{$filtername}[$fname])) {
                        unset($SESSION->{$filtername}[$fname]);
                    } 
                }
            } 
            // clear+reload the form
            $_POST = array();
            $this->_activeform = new editions_active_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams));
        }
    }
    
    
     
    /**
     * Effettua la ricerca dei corsi programmati per l'anno formativo in corso
     * 
     * @param string $sort An SQL field to sort by
     * @param string $dir The sort direction ASC|DESC
     * @param int $page The page or records to return
     * @param int $recordsperpage The number of records to return per page
     * @param string $extraselect An additional SQL select statement to append to the query
     * @param array $extraparams Additional parameters to use for the above $extraselect
     * @return array Array of {@link $USER} records
     */
    function get_my_editions_listing($sort='codicecorso', $dir='ASC', $page=0, $recordsperpage=0,
                               $extraselect='', array $extraparams=null) {
        global $DB, $CFG, $USER;
        
        if ($extraselect) {
            $extraselect = ' AND '.$extraselect;
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
        
        $parametro = get_parametro('p_f2_anno_minimo_storico'); 
        $anno_minimo = strtotime($parametro->val_date);
        
        return $DB->get_records_sql("SELECT edizione.id edizioneid, c.idnumber codicecorso, c.fullname titolo, anag.localita localita, concat(sessione.name, sessione.id) as sessione,
                                    customdata.data as edizione, date.timestart datainizio, anag.course_type course_type, 
                                            (select count(distinct iscrizioni.userid) 
                                                 from {$CFG->prefix}facetoface_signups iscrizioni
                                                 join {$CFG->prefix}facetoface_signups_status stati ON (iscrizioni.id = stati.signupid)
                                                 $join_aggiuntive
                                                 where iscrizioni.sessionid = edizioneid and stati.statuscode IN (".MDL_F2F_STATUS_NO_SHOW.", ".MDL_F2F_STATUS_PARTIALLY_ATTENDED.", ".MDL_F2F_STATUS_FULLY_ATTENDED.")
                                                 $where_aggiuntiva
                                            ) iscritti
                         FROM {$CFG->prefix}course c
                         JOIN {$CFG->prefix}f2_anagrafica_corsi anag ON (anag.courseid = c.id)
                         JOIN {$CFG->prefix}facetoface sessione ON (c.id = sessione.course)
                         JOIN {$CFG->prefix}facetoface_sessions edizione ON (sessione.id = edizione.facetoface)
                         JOIN {$CFG->prefix}facetoface_sessions_dates date ON (date.sessionid = edizione.id)
                         JOIN {$CFG->prefix}facetoface_session_data customdata ON (customdata.sessionid = edizione.id)
                         JOIN {$CFG->prefix}facetoface_session_field customfield ON (customfield.id = customdata.fieldid)
                         WHERE customfield.shortname = 'editionum' AND date.timestart > $anno_minimo
                         $extraselect
                         ORDER BY $sort, sessione, edizione $dir", $extraparams, $page, $recordsperpage);
    }
    
   
    
    /**
    * Returns a count of editions
    *
    * @global object
    * @param bool $filter A switch to find filtered or not filtered editions
    * @param string $search A simple string to search for
    * @param string $extrasql 
    * @return int  the integer count of the records found is returned.
     *                        False is returned if an error is encountered.
    */
   function get_my_editions_count($filter=true, $extraselect='', array $extraparams=null) {

        global $DB, $CFG, $USER;

        
        if ($extraselect) {
            $extraselect = ' AND '.$extraselect;
        }
        
        if (!isSupervisoreConsiglio($USER->id)) {
            $where_aggiuntiva = "";
        } else {
            list($dominio_visibilita_id, $dominio_visibilita_name) = get_user_viewable_organisation($USER->id);
            $dominio_appartenenza = $dominio_visibilita_id;
            $where_aggiuntiva = " and (select count(distinct iscrizioni.userid) 
                                                 from {$CFG->prefix}facetoface_signups iscrizioni
                                                 join {$CFG->prefix}facetoface_signups_status stati ON (iscrizioni.id = stati.signupid)
                                                 join {$CFG->prefix}org_assignment oa ON (oa.userid = iscrizioni.userid)
                                                 join {$CFG->prefix}org org ON (org.id = oa.organisationid)
                                                 where iscrizioni.sessionid = edizione.id and stati.statuscode IN (".MDL_F2F_STATUS_NO_SHOW.", ".MDL_F2F_STATUS_PARTIALLY_ATTENDED.", ".MDL_F2F_STATUS_FULLY_ATTENDED.")
                                                    and org.path LIKE '%$dominio_appartenenza%'
                                            ) > 0";
        }
        
        $qry = "SELECT COUNT(DISTINCT edizione.id)
                FROM {$CFG->prefix}course c
                JOIN {$CFG->prefix}facetoface sessione ON (c.id = sessione.course)
                JOIN {$CFG->prefix}facetoface_sessions edizione ON (sessione.id = edizione.facetoface)
                JOIN {$CFG->prefix}facetoface_sessions_dates date ON (date.sessionid = edizione.id)
                WHERE date.timestart > 1000000000 $where_aggiuntiva";

        $matchcount = 0;
        if ($filter) {
            $matchcount = $DB->count_records_sql($qry.$extraselect, $extraparams);
        } else {
            $matchcount = $DB->count_records_sql($qry, $extraparams);
        }
        
        return $matchcount;
   }
    
    /**
     * Creates known users filter if present
     * @param string $fieldname
     * @param boolean $advanced
     * @return object filter
     */
    function get_field($fieldname, $advanced) {
        global $USER, $CFG, $SITE;

        switch ($fieldname) {
            case 'idnumber':    return new edition_filter_text('idnumber', get_string('codcorso', 'block_f2_apprendimento'), $advanced, 'idnumber');
            default:            return null;
        }
    }

    /**
     * Returns sql where statement based on active user filters
     * @param string $extra sql
     * @param array named params (recommended prefix ex)
     * @return array sql string and $params
     */
    function get_sql_filter($extra='', array $params=null) {
        global $SESSION;

        $sqls = array();
        if ($extra != '') {
            $sqls[] = $extra;
        }
        $params = (array)$params;
        
        if (!empty($SESSION->my_editions_filtering)) {
            foreach ($SESSION->my_editions_filtering as $fname=>$datas) {
                if (!array_key_exists($fname, $this->_fields)) {
                    continue; // filter not used
                }
                $field = $this->_fields[$fname];
                foreach($datas as $i=>$data) {
                    list($s, $p) = $field->get_sql_filter($data);
                    $sqls[] = $s;
                    $params = $params + $p;
                }
            }
        }

        if (empty($sqls)) {
            return array('', array());
        } else {
            $sqls = implode(' AND ', $sqls);
            return array($sqls, $params);
        }
    }

    /**
     * Print the add filter form.
     */
    function display_add() {
        $this->_addform->display();
    }

    /**
     * Print the active filter form.
     */
    function display_active() {
        $this->_activeform->display();
    }

}

/**
 * The base editions filter class. All abstract classes must be implemented.
 */
class editions_filter_type {
    /**
     * The name of this filter instance.
     */
    var $_name;

    /**
     * The label of this filter instance.
     */
    var $_label;

    /**
     * Advanced form element flag
     */
    var $_advanced;

    /**
     * Constructor
     * @param string $name the name of the filter instance
     * @param string $label the label of the filter instance
     * @param boolean $advanced advanced form element flag
     */
    function editions_filter_type($name, $label, $advanced) {
        $this->_name     = $name;
        $this->_label    = $label;
        $this->_advanced = $advanced;
    }

    /**
     * Returns the condition to be used with SQL where
     * @param array $data filter settings
     * @return string the filtering condition or null if the filter is disabled
     */
    function get_sql_filter($data) {
        error('Abstract method get_sql_filter() called - must be implemented');
    }

    /**
     * Retrieves data from the form data
     * @param object $formdata data submited with the form
     * @return mixed array filter data or false when filter not set
     */
    function check_data($formdata) {
        error('Abstract method check_data() called - must be implemented');
    }

    /**
     * Adds controls specific to this filter in the form.
     * @param object $mform a MoodleForm object to setup
     */
    function setupForm(&$mform) {
        error('Abstract method setupForm() called - must be implemented');
    }

    /**
     * Returns a human friendly description of the filter used as label.
     * @param array $data filter settings
     * @return string active filter label
     */
    function get_label($data) {
        error('Abstract method get_label() called - must be implemented');
    }
}

function get_storico_corsi_listing($sort='codcorso', $dir='ASC', $page=0, $recordsperpage=0,
		$extraselect='', array $extraparams=null) {
	global $DB, $CFG, $USER;

	if ($extraselect) {
		$extraselect = ' AND '.$extraselect;
	}


	$parametro = get_parametro('p_f2_anno_minimo_storico'); 
	$anno_minimo = strtotime($parametro->val_date);


	$sql = "SELECT sql_calc_found_rows 
    		id,
    		edizione,
    		localita,
    		sessione,
    		data_inizio,
    		tipo_corso,
    		titolo,
    		codcorso,
    			count(id) as iscritti
    		FROM
    			{f2_storico_corsi}
    		WHERE
    			 data_inizio >".$anno_minimo." AND
    			(tipo_corso = 'P' OR tipo_corso = 'O')
    		".$extraselect."
    		GROUP BY codcorso, edizione,data_inizio
    		ORDER BY ".$sort." ".$dir."
    		";
	
	$result_sql = $DB->get_records_sql($sql, $extraparams, $page, $recordsperpage);
	$result_sql_count = $DB->get_record_sql("SELECT FOUND_ROWS() AS `count`");

	//print_r($result_sql_count);exit;
	
	$return	= new stdClass;
	$return->count	= $result_sql_count->count;
	$return->dati	= $result_sql;

	
	return $return;
	
//	return $result_sql;
}


function get_storico_corsi_count() {
	 
	global $DB;
	

	$parametro = get_parametro('p_f2_anno_minimo_storico'); 
	$anno_minimo = strtotime($parametro->val_date);

$sql_total_count = "SELECT sql_calc_found_rows
    		id
    		FROM
    			{f2_storico_corsi}
    		WHERE
    			 data_inizio >".$anno_minimo." AND
    			(tipo_corso = 'P' OR tipo_corso = 'O')
    		GROUP BY codcorso, edizione,data_inizio
    		";

$result_sql = $DB->get_records_sql($sql_total_count);
$result_sql_total_count = $DB->get_record_sql("SELECT FOUND_ROWS() AS `count`");

return $result_sql_total_count->count;
	
}