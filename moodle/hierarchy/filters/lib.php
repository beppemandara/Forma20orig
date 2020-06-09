<?php //$Id: lib.php 813 2012-12-05 11:19:55Z c.arnolfo $

require_once($CFG->dirroot.'/hierarchy/filters/text.php');
require_once($CFG->dirroot.'/hierarchy/filters/textarea.php');
require_once($CFG->dirroot.'/hierarchy/filters/org_picker_con_direzioni.php');
require_once($CFG->dirroot.'/hierarchy/filters/customfield.php');
require_once($CFG->dirroot.'/hierarchy/filters/filter_forms.php');
require_once($CFG->dirroot.'/f2_lib/management.php');


/**
 * Hierarchy filtering wrapper class.
 */
class hierarchy_filtering {
    var $_fields;
    var $_addform;
    var $_activeform;
    var $_type;
    
    function addform_is_submitted() {
        return $this->_addform->is_submitted();
    }

    /**
     * Contructor
     * @param array array of visible hierarchy items
     * @param string base url used for submission/return, null if the same of current page
     * @param array extra page parameters
     * @param boolean $showfullsearch if true show fullname search box by default, otherwise show shortname search box
     */
    function hierarchy_filtering($type=null, $fieldnames=null, $baseurl=null, $extraparams=null, $showfullsearch=true) {
        global $SESSION;
        if($type == null) {
            error('hierarchy type must be defined');
        }
        $filtername = $type.'_filtering';
        $this->_type = $type;

        if (!isset($SESSION->{$filtername})) {
            $SESSION->{$filtername} = array();
        }

        if (empty($fieldnames)) {
            $fieldnames = array('fullname'=> (int) !$showfullsearch, 'shortname'=> (int) $showfullsearch, 'organisationid'=> (int) $showfullsearch, 'idnumber'=>1, 'description'=>1, 'custom'=>1);
        }

        $this->_fields  = array();

        foreach ($fieldnames as $fieldname=>$advanced) {
            if ($field = $this->get_field($fieldname, $advanced)) {
//                var_dump($field);
                $this->_fields[$fieldname] = $field;
            }
        }
        
        // first the new filter form
        $this->_addform = new hierarchy_add_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams, 'type'=>$type));
        
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
            $this->_addform = new hierarchy_add_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams, 'type'=>$type));
        }
        
        // now the active filters
        $this->_activeform = new hierarchy_active_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams, 'type'=>$type));
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
            $this->_activeform = new hierarchy_active_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams, 'type'=>$type));
        }
    }
    
    /**
     * Effettua la ricerca dei domini
     * 
     * @param string $select SQL select statement to put at the begin of the query
     * @param string $where base clause sql
     * @param string $extrasql An additional SQL select statement to append to the query
     * @param string $limit An additional SQL select statement to append to the query
     * @return array Array of organisation records
     */
    function get_my_hierarchy_listing($select, $where, $extrasql='', $limit = '') {

        global $DB, $CFG;
        
        $from   = " FROM {$CFG->prefix}org";
        $order  = " ORDER BY sortorder";
        
        return $DB->get_records_sql($select.$from.$where.$extrasql.$order.$limit);
    }
    
    /**
     * Effettua la ricerca dei domini
     * 
     * @param string $select SQL select statement to put at the begin of the query
     * @param string $where base clause sql
     * @param string $extrasql An additional SQL select statement to append to the query
     * @param string $limit An additional SQL select statement to append to the query
     * @return int  the integer count of the records found is returned.
     *                        False is returned if an error is encountered.
     */
    function get_my_hierarchy_listing_count($where, $extrasql='') {

        global $DB, $CFG;
        
        $from   = " FROM {$CFG->prefix}org";
    
        return $DB->count_records_sql('SELECT COUNT(DISTINCT id)'.$from.$where.$extrasql);
    }
    
    function findsubtreeSqlReplacement($extrasql = '') {
        
        $FIND_SUBTREE_STRING_LENGTH = 15; // lunghezza della stringa 'FIND_SUBTREE - ' restituita dal metodo org_picker->get_sql_filter()
        $AND_STRING_LENGTH = 5; // lunghezza della stringa ' AND '
        $pos = strpos($extrasql,'FIND_SUBTREE');
        $findsubtree = ($pos === false) ? false : true;
        
        if (!$findsubtree) return $extrasql;
        
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

            $select = '';
            $in_clause = " AND id IN ($root_id";
            foreach( $info as $row )
              $in_clause .= ", $row->id";
            $in_clause .= ')';
            $select .= $in_clause;
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
                $select .= " AND $extrasql";
            else
                $select .= " $extrasql";
        }
        
        return $select;
    }
    
    function finddirezioneSqlReplacement($extrasql = '') {
        
        $FIND_DIREZIONE_STRING_LENGTH = 17; // lunghezza della stringa 'FIND_DIREZIONE - ' restituita dal metodo org_picker->get_sql_filter()
        $AND_STRING_LENGTH = 5; // lunghezza della stringa ' AND '
        $pos = strpos($extrasql,'FIND_DIREZIONE');
        $finddirezione = ($pos === false) ? false : true;
        
        if (!$finddirezione) return $extrasql;
        
        // cerco tutte le FIND_DIREZIONE, le gestisco e le rimuovo da $extrasql
        while ($finddirezione) {
            // string 'FIND_DIREZIONE' found in $extrasql
            // ricavo l'id radice da $extrasql
            $root_id = substr($extrasql, $pos + $FIND_DIREZIONE_STRING_LENGTH);
            $pos = strpos($root_id,' AND');
            if($pos > 0) // se non era l'ultima clausola
                $root_id = substr($root_id, 0, $pos);

            // ottengo gli id dei domini facenti parte dell'albero sotteso a $root_id
            $info = recursivesubtree($root_id);

            $in_clause_1 = " AND id IN (";
            $in_clause_2 = "";
            foreach( $info as $row ) {
                // filtro i soli domini di direzione (terzo livello)
                if ($row->level == 3) {
                    $in_clause_2 .= ", $row->id";
                }
            }
            if ($in_clause_2 != '') {
                $in_clause_2 = substr($in_clause_2, 2); // rimuovo la prima virgola ', '
                $select .= $in_clause_1.$in_clause_2.')';
            } else {
                // non sono presenti direzioni nel dominio selezionato
                $select .= $in_clause_1.'-1)'; // forzatura per non far restituire risultati
            }
            
            // rimuovo la FIND_DIREZIONE appena gestita da $extrasql
            $pos = strpos($extrasql,'FIND_DIREZIONE');

            if ($pos >= strlen($AND_STRING_LENGTH)) {
                // allora significa che 'FIND_DIREZIONE' è preceduta da ' AND '
                $pos = $pos - $AND_STRING_LENGTH;
                $stringLengthToRemove = $AND_STRING_LENGTH + $FIND_DIREZIONE_STRING_LENGTH + strlen($root_id);
            } else {
                $stringLengthToRemove = $FIND_DIREZIONE_STRING_LENGTH + strlen($root_id);
            }
            $extrasql = substr($extrasql, 0, $pos).substr($extrasql, $pos + $stringLengthToRemove);
            $pos = strpos($extrasql,'FIND_DIREZIONE');
            $finddirezione = ($pos === false) ? false : true;
        }

        $extrasql = trim($extrasql);
        // string 'FIND_DIREZIONE' NOT found in $extrasql
        // se sono presenti altre clausole le metto in coda
        if ($extrasql) {
            $pos = strpos($extrasql,'AND ');
            if ($pos === false || $pos > 0)
                $select .= " AND $extrasql";
            else
                $select .= " $extrasql";
        }
        
        return $select;
    }

    /**
     * Creates known hierarchy filter if present
     * @param string $fieldname
     * @param boolean $advanced
     * @return object filter
     */
    function get_field($fieldname, $advanced) {
        global $USER, $CFG, $SITE;

        switch ($fieldname) {
            case 'fullname':    return new hierarchy_filter_text('fullname', get_string('fullname'), $advanced, 'fullname');
            case 'shortname':    return new hierarchy_filter_text('shortname', get_string('shortname'), $advanced, 'shortname');
            case 'organisationid':    return new users_filter_org_picker_con_direzioni('organisationid', get_string('organisation', 'local_f2_domains'), $advanced, 'organisationid');
            case 'idnumber':    return new hierarchy_filter_text('idnumber', get_string('idnumber'), $advanced, 'idnumber');
            case 'description':       return new hierarchy_filter_textarea('description', get_string('description'), $advanced, 'description');
            case 'custom':      return new hierarchy_filter_customfield('custom', get_string('customfield', 'local_f2_domains'), $advanced);
            default:            return null;
        }
    }

    /**
     * Returns sql where statement based on active hierarchy filters
     * @param string $extra sql
     * @return string
     */
    function get_sql_filter($extra='') {
        global $SESSION;

        $sqls = array();
        if ($extra != '') {
            $sqls[] = $extra;
        }

        $filtername = $this->_type.'_filtering';

        if (!empty($SESSION->{$filtername})) {
            foreach ($SESSION->{$filtername} as $fname=>$datas) {
                if (!array_key_exists($fname, $this->_fields)) {
                    continue; // filter not used
                }
                $field = $this->_fields[$fname];
                foreach($datas as $i=>$data) {
                    $sqls[] = $field->get_sql_filter($data);
                }
            }
        }

        if (empty($sqls)) {
            return '';
        } else {
            return implode(' AND ', $sqls);
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
 * The base hierarchy filter class. All abstract classes must be implemented.
 */
class hierarchy_filter_type {
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
    function hierarchy_filter_type($name, $label, $advanced) {
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
