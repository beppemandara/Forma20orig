<?php //$Id$

require_once($CFG->dirroot.'/local/f2_domains/assignments/filters/text.php');
require_once($CFG->dirroot.'/local/f2_domains/assignments/filters/org_picker.php');
require_once($CFG->dirroot.'/local/f2_domains/assignments/filters/viewable_org_picker.php');
require_once($CFG->dirroot.'/local/f2_domains/assignments/filters/filter_forms.php');


/**
 * Hierarchy filtering wrapper class.
 */
class my_users_filtering {
    var $_fields;
    var $_addform;
    var $_activeform;

    /**
     * Contructor
     * @param array array of visible users items
     * @param string base url used for submission/return, null if the same of current page
     * @param array extra page parameters
     * @param boolean $showfullsearch if true show fullname search box by default, otherwise show shortname search box
     */
    function my_users_filtering($fieldnames=null, $baseurl=null, $extraparams=null, $showfullsearch=true,$show_select_only_org=false) {
        global $SESSION;
        $filtername = 'my_users_filtering';

        if (!isset($SESSION->{$filtername})) {
            $SESSION->{$filtername} = array();
        }

        if (empty($fieldnames)) {
            $fieldnames = array('lastname'=> (int) !$showfullsearch, 'organisationid'=> (int) $showfullsearch, 'viewableorganisationid'=> (int) $showfullsearch);
        }
        $this->_fields  = array();

        foreach ($fieldnames as $fieldname=>$advanced) {
            if ($field = $this->get_field($fieldname, $advanced,$show_select_only_org)) {
                $this->_fields[$fieldname] = $field;
            }
        }
        
        // first the new filter form
        $this->_addform = new users_add_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams));
        
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
            $this->_addform = new users_add_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams));
        }
        
        // now the active filters
        $this->_activeform = new users_active_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams));
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
            $this->_activeform = new users_active_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams));
        }
    }
     
    /**
     * Effettua la ricerca deli utenti filtrando per cognome e dominio di appartenenza
     * 
     * @param string $sort An SQL field to sort by
     * @param string $dir The sort direction ASC|DESC
     * @param int $page The page or records to return
     * @param int $recordsperpage The number of records to return per page
     * @param string $search A simple string to search for
     * @param string $firstinitial Users whose first name starts with $firstinitial
     * @param string $lastinitial Users whose last name starts with $lastinitial
     * @param string $extraselect An additional SQL select statement to append to the query
     * @param array $extraparams Additional parameters to use for the above $extraselect
     * @param object $extracontext If specified, will include user 'extra fields'
     *   as appropriate for current user and given context
     * @return array Array of {@link $USER} records
     */
    function get_my_users_listing($sort='lastaccess', $dir='ASC', $page=0, $recordsperpage=0,
                               $search='', $firstinitial='', $lastinitial='', $extraselect='',
                               array $extraparams=null, $extracontext = null) {
        global $DB, $CFG;

        $fullname  = $DB->sql_fullname();

        $select = "deleted <> 1";
        $params = array();

        if (!empty($search)) {
            $search = trim($search);
            $select .= " AND (". $DB->sql_like($fullname, ':search1', false, false).
                       " OR ". $DB->sql_like('email', ':search2', false, false).
                       " OR username = :search3)";
            $params['search1'] = "%$search%";
            $params['search2'] = "%$search%";
            $params['search3'] = "$search";
        }

        if ($firstinitial) {
            $select .= " AND ". $DB->sql_like('firstname', ':fni', false, false);
            $params['fni'] = "$firstinitial%";
        }
        if ($lastinitial) {
            $select .= " AND ". $DB->sql_like('lastname', ':lni', false, false);
            $params['lni'] = "$lastinitial%";
        }

        if ($extraselect) {
            $FIND_SUBTREE_STRING_LENGTH = 15; // lunghezza della stringa 'FIND_SUBTREE - ' restituita dal metodo org_picker->get_sql_filter()
            $AND_STRING_LENGTH = 5; // lunghezza della stringa ' AND '
            $pos = strpos($extraselect,'FIND_SUBTREE');
            $findsubtree = ($pos === false) ? false : true;
            // cerco tutte le FIND_SUBTREE, le gestisco e le rimuovo da $extraselect
            while ($findsubtree) {
                    // string 'FIND_SUBTREE' found in $extraselect
                    // ricavo l'id radice da $extraselect
                    $root_id = substr($extraselect, $pos + $FIND_SUBTREE_STRING_LENGTH);
                    $pos = strpos($root_id,' AND');
                    if($pos > 0) // se non era l'ultima clausola
                        $root_id = substr($root_id, 0, $pos);

                    // ottengo gli id dei domini facenti parte dell'albero sotteso a $root_id
                    $info = recursivesubtree($root_id);

                    $in_clause = " AND organisationid IN ($root_id";
                    foreach( $info as $row )
                      $in_clause .= ", $row->id";
                    $in_clause .= ')';

                    $select .= $in_clause;
                    // rimuovo la FIND_SUBTREE appena gestita da $extraselect
                    $pos = strpos($extraselect,'FIND_SUBTREE');
                    
                    if ($pos >= strlen($AND_STRING_LENGTH)) {
                        // allora significa che 'FIND_SUBTREE' è preceduta da ' AND '
                        $pos = $pos - $AND_STRING_LENGTH;
                        $stringLengthToRemove = $AND_STRING_LENGTH + $FIND_SUBTREE_STRING_LENGTH + strlen($root_id);
                    } else {
                        $stringLengthToRemove = $FIND_SUBTREE_STRING_LENGTH + strlen($root_id);
                    }
                    $extraselect = substr($extraselect, 0, $pos).substr($extraselect, $pos + $stringLengthToRemove);
                    $pos = strpos($extraselect,'FIND_SUBTREE');
                    $findsubtree = ($pos === false) ? false : true;
            }
                
            $extraselect = trim($extraselect);
            // string 'FIND_SUBTREE' NOT found in $extrasql
            // se sono presenti altre clausole le metto in coda
            if ($extraselect) {
                $pos = strpos($extraselect,'AND ');
                if ($pos === false || $pos > 0)
                    $select .= " AND $extraselect";
                else
                    $select .= " $extraselect";
                
                $params = $params + (array)$extraparams;
            }
        }

        if ($sort) {
            $sort = " ORDER BY $sort $dir";
        }
        
        // If a context is specified, get extra user fields that the current user
        // is supposed to see.
        $extrafields = '';
//        if ($extracontext) {
//            $extrafields = get_extra_user_fields_sql($extracontext, '', '',
//                    array('id', 'username', 'email', 'firstname', 'lastname', 'city', 'country',
//                    'lastaccess', 'confirmed', 'mnethostid'));
//        }

//        print_r("SELECT user.id, username, user.idnumber, email, firstname, lastname, city, country,
//                                            lastaccess, confirmed, mnethostid, suspended, org.fullname as org_fullname, org2.fullname as viewable_org_fullname $extrafields
//                                       FROM {user} user
//                                       LEFT JOIN {$CFG->prefix}org_assignment oa ON user.id=oa.userid
//                                       LEFT JOIN {$CFG->prefix}org org ON org.id=oa.organisationid
//                                       LEFT JOIN {$CFG->prefix}org org2 ON org2.id=oa.viewableorganisationid
//                                       WHERE $select
//                                       $sort");
        // warning: will return UNCONFIRMED USERS
        return $DB->get_records_sql("SELECT user.id, username, user.idnumber, email, firstname, lastname, city, country,
                                            lastaccess, confirmed, mnethostid, suspended, org.fullname as org_fullname, org2.fullname as viewable_org_fullname, org.shortname,org2.shortname as viewable_org_shortname  $extrafields
                                       FROM {user} user
                                       LEFT JOIN {$CFG->prefix}org_assignment oa ON user.id=oa.userid
                                       LEFT JOIN {$CFG->prefix}org org ON org.id=oa.organisationid
                                       LEFT JOIN {$CFG->prefix}org org2 ON org2.id=oa.viewableorganisationid
                                       WHERE $select
                                       $sort", $params, $page, $recordsperpage);
            
    }
    
    /**
    * Returns a count of users
    *
    * @global object
    * @param bool $filter A switch to find filtered or not filtered users
    * @param string $search A simple string to search for
    * @param string $extrasql 
    * @return int  the integer count of the records found is returned.
     *                        False is returned if an error is encountered.
    */
   function get_my_users_count($filter=true, $with_viewableorganisation = false, $search='', $extrasql='', array $extraparams=null) {
       global $CFG, $DB;

        $fullname  = $DB->sql_fullname();
        
        $select = " user.id <> :guestid AND deleted = 0";
        $params['guestid'] = "$CFG->siteguest";

        if (!empty($search)) {
            $search = trim($search);
            $select .= " AND (". $DB->sql_like($fullname, ':search1', false, false).
                       " OR ". $DB->sql_like('email', ':search2', false, false).
                       " OR username = :search3)";
            $params['search1'] = "%$search%";
            $params['search2'] = "%$search%";
            $params['search3'] = "$search";
        }

        if ($extrasql) {
            $extrasql_temp = '';
            $FIND_SUBTREE_STRING_LENGTH = 15; // lunghezza della stringa 'FIND_SUBTREE - ' restituita dal metodo org_picker->get_sql_filter()
            $AND_STRING_LENGTH = 5; // lunghezza della stringa ' AND '
            $pos = strpos($extrasql,'FIND_SUBTREE');
            $findsubtree = ($pos === false) ? false : true;
            // cerco tutte le FIND_SUBTREE, le gestisco e le rimuovo da $extrasql
            while ($findsubtree) {
                    // string 'FIND_SUBTREE' found in $extrasql
                    // ricavo l'id radice da $extrasql
                    $root_id = substr($extrasql, $pos + $FIND_SUBTREE_STRING_LENGTH);
                    $pos = strpos($root_id,' AND');
                    if($pos > 0) // se non era l'ultima clausola
                        $root_id = substr($root_id, 0, $pos);

                    // ottengo gli id dei domini facenti parte dell'albero sotteso a $root_id
                    $info = recursivesubtree($root_id);
                    $json = recursivesubtreejson(52);
                    
                    $in_clause = " AND organisationid IN ($root_id";
                    foreach( $info as $row )
                      $in_clause .= ", $row->id";
                    $in_clause .= ')';

                    $extrasql_temp .= $in_clause;
                    // rimuovo la FIND_SUBTREE appena gestita da $extrasql
                    $pos = strpos($extrasql,'FIND_SUBTREE');
                    
                    if ($pos >= strlen($AND_STRING_LENGTH)) {
                        // allora significa che 'FIND_SUBTREE' è preceduta da ' AND '
                        $pos = $pos - $AND_STRING_LENGTH;
                        $stringLengthToRemove = $AND_STRING_LENGTH + $FIND_SUBTREE_STRING_LENGTH + strlen($root_id);
                    } else {
                        $stringLengthToRemove = $FIND_SUBTREE_STRING_LENGTH + strlen($root_id);
                    }
                    $extrasql = substr($extrasql, 0, $pos).substr($extrasql, $pos + $stringLengthToRemove);
                    $pos = strpos($extrasql,'FIND_SUBTREE');
                    $findsubtree = ($pos === false) ? false : true;
            }
                
            $extrasql = trim($extrasql);
            // string 'FIND_SUBTREE' NOT found in $extrasql
            // se sono presenti altre clausole le metto in coda
            if ($extrasql) {
                $pos = strpos($extrasql,'AND ');
                if ($pos === false || $pos > 0)
                    $extrasql = "$extrasql_temp AND $extrasql";
                else
                    $extrasql = "$extrasql_temp $extrasql";
                
                $params = $params + (array)$extraparams;
            } else {
                $extrasql = $extrasql_temp;
            }
        }
        
        // build the query to get the items
        // not actually called until further down but need sql for the count
        $from   = " FROM {user} user
                    LEFT JOIN {$CFG->prefix}org_assignment oa ON user.id=oa.userid
                    LEFT JOIN {$CFG->prefix}org org ON org.id=oa.organisationid
                    LEFT JOIN {$CFG->prefix}org org2 ON org2.id=oa.viewableorganisationid";
        $where  = " WHERE $select";
        
        $matchcount = 0;
        if ($filter) {
            if ($extrasql !== '') {
//                print_r(' 111 SELECT COUNT(DISTINCT user.id) '.$from.$where.$extrasql);
//                print_r('</br>e questi sono i parametri: ');
//                var_dump($params);
                $matchcount = $DB->count_records_sql('SELECT COUNT(DISTINCT user.id) '.$from
                .$where.$extrasql, $params);
            } else {
//                print_r(' 222 SELECT COUNT(DISTINCT user.id) '.$from.$where);
                $matchcount = $DB->count_records_sql('SELECT COUNT(DISTINCT user.id) '.$from.$where, $params);
            }
            return $matchcount;
        } else {
            if (!$with_viewableorganisation)
                $matchcount = $DB->count_records_sql('SELECT COUNT(DISTINCT user.id) '.$from.$where, $params);
            else
                $matchcount = $DB->count_records_sql('SELECT COUNT(DISTINCT user.id) '.$from.
                        $where.' AND viewableorganisationid IS NOT NULL AND org2.depthid > 1 AND org2.depthid < 4', $params);
        }
        
        return $matchcount;
   }
    
    /**
     * Creates known users filter if present
     * @param string $fieldname
     * @param boolean $advanced
     * @return object filter
     */
    function get_field($fieldname, $advanced,$show_select_only_org=false) {
        global $USER, $CFG, $SITE;

        switch ($fieldname) {
            case 'lastname':    return new users_filter_text('lastname', get_string('lastname'), $advanced, 'lastname');
            case 'organisationid':    return new users_filter_org_picker('organisationid', get_string('organisation', 'local_f2_domains'), $advanced, 'organisationid');
            case 'viewableorganisationid':    return new users_filter_viewable_org_picker('viewableorganisationid', get_string('viewable_organisation', 'local_f2_domains'), $advanced, 'viewableorganisationid',$show_select_only_org);
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
        
        if (!empty($SESSION->my_users_filtering)) {
            foreach ($SESSION->my_users_filtering as $fname=>$datas) {
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
 * The base users filter class. All abstract classes must be implemented.
 */
class users_filter_type {
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
    function users_filter_type($name, $label, $advanced) {
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
