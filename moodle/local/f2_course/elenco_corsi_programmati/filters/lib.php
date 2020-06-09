<?php
require_once($CFG->dirroot.'/f2_lib/core.php');
require_once($CFG->dirroot.'/local/f2_course/elenco_corsi_programmati/filters/text.php');
require_once($CFG->dirroot.'/local/f2_course/elenco_corsi_programmati/filters/filter_forms.php');

/**
 * Courses filtering wrapper class.
 */
class my_courses_filtering {
    var $_fields;
    var $_addform;
    var $_activeform;

    /**
     * Contructor
     * @param array array of visible courses items
     * @param string base url used for submission/return, null if the same of current page
     * @param array extra page parameters
     * @param boolean $showfullsearch if true show fullname search box by default, otherwise show shortname search box
     */
    function my_courses_filtering($fieldnames=null, $baseurl=null, $extraparams=null, $showfullsearch=true) {
        global $SESSION;
        $filtername = 'my_courses_filtering';

        if (!isset($SESSION->{$filtername})) {
            $SESSION->{$filtername} = array();
        }

        if (empty($fieldnames)) {
            $fieldnames = array('fullname'=> (int) !$showfullsearch, 'idnumber'=> (int) !$showfullsearch);
        }
        $this->_fields  = array();

        foreach ($fieldnames as $fieldname=>$advanced) {
            if ($field = $this->get_field($fieldname, $advanced)) {
                $this->_fields[$fieldname] = $field;
            }
        }
        
        // first the new filter form
        $this->_addform = new courses_add_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams));
        
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
            $this->_addform = new courses_add_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams));
        }
        
        // now the active filters
        $this->_activeform = new courses_active_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams));
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
            $this->_activeform = new courses_active_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams));
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
    function get_my_courses_listing($sort='fullname', $dir='ASC', $page=0, $recordsperpage=0,
                               $extraselect='', array $extraparams=null) {
        global $DB, $CFG;

        $sort = " ORDER BY $sort $dir";
        $anno_corrente = get_anno_formativo_corrente();
        $where = " WHERE an.course_type = 2 AND an.anno = $anno_corrente"; // corso programmato
        if ($extraselect) {
            $where .= ' AND ';
        }
        
        return $DB->get_records_sql("SELECT c.id, c.fullname, c.idnumber, c.summary, c.visible
                                       FROM {$CFG->prefix}course c
                                       JOIN {$CFG->prefix}f2_anagrafica_corsi an on an.courseid = c.id
                                       $where $extraselect
                                       $sort", $extraparams, $page, $recordsperpage);
            
    }
    
    /**
    * Returns a count of courses
    *
    * @global object
    * @param bool $filter A switch to find filtered or not filtered courses
    * @param string $search A simple string to search for
    * @param string $extrasql 
    * @return int  the integer count of the records found is returned.
     *                        False is returned if an error is encountered.
    */
   function get_my_courses_count($filter=true, $extrasql='', array $extraparams=null) {
       global $CFG, $DB;

        // build the query to get the items
        // not actually called until further down but need sql for the count
        $from   = " FROM {$CFG->prefix}course c
                    JOIN {$CFG->prefix}f2_anagrafica_corsi an on an.courseid = c.id";
        $anno_corrente = get_anno_formativo_corrente();
        $where = " WHERE an.course_type = 2 AND an.anno = $anno_corrente"; // corso programmato
        
        $matchcount = 0;
        if ($filter) {
            if ($extrasql !== '') {
                $matchcount = $DB->count_records_sql('SELECT COUNT(DISTINCT c.id) '.$from
                .$where.' AND '.$extrasql, $extraparams);
            } else {
                $matchcount = $DB->count_records_sql('SELECT COUNT(DISTINCT c.id) '.$from
                .$where, $extraparams);
            }
            return $matchcount;
        } else {
            $matchcount = $DB->count_records_sql('SELECT COUNT(DISTINCT c.id) '.$from.$where, $extraparams);
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
        switch ($fieldname) {
            case 'fullname':    return new course_filter_text('fullname', get_string('fullname'), $advanced, 'fullname');
            case 'idnumber':    return new course_filter_text('idnumber', 'Codice corso', $advanced, 'idnumber');
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
        
        if (!empty($SESSION->my_courses_filtering)) {
            foreach ($SESSION->my_courses_filtering as $fname=>$datas) {
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
 * The base courses filter class. All abstract classes must be implemented.
 */
class courses_filter_type {
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
    function courses_filter_type($name, $label, $advanced) {
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