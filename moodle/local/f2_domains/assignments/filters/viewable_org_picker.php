<?php //$Id$

/**
 * Generic filter for viewable_org_picker fields.
 */
class users_filter_viewable_org_picker extends users_filter_type {
    var $_field;

    /**
     * Constructor
     * @param string $name the name of the filter instance
     * @param string $label the label of the filter instance
     * @param boolean $advanced advanced form element flag
     * @param string $field feature table field name
     */
    function users_filter_viewable_org_picker($name, $label, $advanced, $field,$show_select_only_org=false) {
        parent::users_filter_type($name, $label, $advanced);
        $this->_field = $field;
        $this->_show_select_only_org = $show_select_only_org;
    }

    /**
     * Returns an array of comparison operators
     * @return array of comparison operators
     */
    function getOperators() {
        return array(0 => get_string('isequalto','filters'),
                     1 => get_string('isempty','filters'));
    }

    /**
     * Adds controls specific to this filter in the form.
     * @param object $mform a MoodleForm object to setup
     */
    function setupForm(&$mform) {
        global $USER;
        $objs = array();
        $objs[] =& $mform->createElement('select', $this->_name.'_op', null, $this->getOperators());
        $tree_root = get_root_framework();
        if (!is_null($tree_root)) {
        	if($this->_show_select_only_org){
	        	//array(3) {[0]=> string(2) "23" [1]=> string(2) "75" [2]=> string(2) "26" }
	        	//$wanted = array(23,75,26);
	        	$parametri_regioni = get_parametri_by_prefix('p_f2_dominio_radice_regione_');
	        	$id_radici_regione_array = array();
	        	foreach ($parametri_regioni as $param) {
	        		$id_radici_regione_array[] = $param->val_int;
	        	}
	        	$hierarchy = recursivesubtreejson($tree_root->id, $tree_root->fullname,$id_radici_regione_array,true, 3);
		    	}
	        	else{
	        	 	$hierarchy = recursivesubtreejson($tree_root->id, $tree_root->fullname);
	        		}
        	}
       		else {
            	$hierarchy = '';
        	}
        if($this->_show_select_only_org){
        	$objs[] =& $mform->createElement('static', 'viewableorganisationselector', get_string('viewable_organisation', 'local_f2_domains'), get_organisation_picker_html('viewableorganisationtitle', $this->_name, get_string('choose_viewable_organisation', 'local_f2_domains'), 'domini_visibilita', $hierarchy,'', '', true));
        	 
        }
        else {
        	$objs[] =& $mform->createElement('static', 'viewableorganisationselector', get_string('viewable_organisation', 'local_f2_domains'), get_organisation_picker_html('viewableorganisationtitle', $this->_name, get_string('choose_viewable_organisation', 'local_f2_domains'), 'domini_visibilita', $hierarchy));
        	 
        }        
        $objs[] =& $mform->createElement('hidden', $this->_name, null);
        $mform->setType($this->_name, PARAM_INT);
        $grp =& $mform->addElement('group', $this->_name.'_grp', $this->_label, $objs, '', false);
        $mform->addHelpButton($this->_name.'_grp', $this->_name, 'local_f2_domains');
        
        $mform->disabledIf($this->_name, $this->_name.'_op', 'eq', 1);
        if ($this->_advanced) {
            $mform->setAdvanced($this->_name.'_grp');
        }
    }

    /**
     * Retrieves data from the form data
     * @param object $formdata data submited with the form
     * @return mixed array filter data or false when filter not set
     */
    function check_data($formdata) {
        $field    = $this->_name;
        $operator = $field.'_op';
        $value = (isset($formdata->$field)) ? $formdata->$field : '';
        if (array_key_exists($operator, $formdata)) {
            if ($formdata->$operator != 1 and $formdata->$field == '') {
                // no data - no change except for empty filter
                return false;
            }
            return array('operator'=>(int)$formdata->$operator, 'value'=>$value);
        }

        return false;
    }

    /**
     * Returns the condition to be used with SQL where
     * @param array $data filter settings
     * @return array sql string and $params
     */
    function get_sql_filter($data) {
        global $DB, $CFG;
        static $counter = 0;
        $name = 'ex_text'.$counter++;

        $operator = $data['operator'];
        $value    = $data['value'];
        $field    = $this->_field;

        $params = array();

        if ($operator != 1 and $value === '') {
            return '';
        }

        switch($operator) {
            case 0: // equal to
                $res = "$field = $value";
                break;
            case 1: // empty
                $res = "user.id NOT IN (SELECT user.id FROM {user} user
                                      JOIN {$CFG->prefix}org_assignment oa ON user.id=oa.userid)";
                break;
            default:
                return '';
        }
        return array($res, $params);
    }

    /**
     * Returns a human friendly description of the filter used as label.
     * @param array $data filter settings
     * @return string active filter label
     */
    function get_label($data) {
        $operator  = $data['operator'];
        $value     = $data['value'];
        $operators = $this->getOperators();

        $a = new object();
        $a->label    = $this->_label;
        $a->value    = '"'.s($value).'"';
        $a->operator = $operators[$operator];


        switch ($operator) {
            case 0: // equal to
                return get_string('textlabel', 'filters', $a);
            case 1: // empty
                return get_string('textlabelnovalue', 'filters', $a);
        }

        return '';
    }
}
