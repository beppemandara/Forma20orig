<?php //$Id$

/**
 * Generic filter for org_picker fields.
 */
class users_filter_org_picker_con_direzioni extends hierarchy_filter_type {
    var $_field;

    /**
     * Constructor
     * @param string $name the name of the filter instance
     * @param string $label the label of the filter instance
     * @param boolean $advanced advanced form element flag
     * @param string $field feature table field name
     */
    function users_filter_org_picker_con_direzioni($name, $label, $advanced, $field) {
        parent::hierarchy_filter_type($name, $label, $advanced);
        $this->_field = $field;
    }

    /**
     * Returns an array of comparison operators
     * @return array of comparison operators
     */
    function getOperators() {
        return array(0 => get_string('isequalto','filters'),
                     1 => get_string('descendent', 'local_f2_domains'),
                     2 => get_string('direzioniin', 'local_f2_domains'));
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
            $hierarchy = recursivesubtreejson($tree_root->id, $tree_root->fullname);
        } else {
            $hierarchy = '';
        }
        
        $objs[] =& $mform->createElement('static', 'organisationselector', get_string('organisation', 'local_f2_domains'), get_organisation_picker_html('organisationtitle', $this->_name, get_string('chooseorganisation', 'local_f2_domains'), 'domini', $hierarchy));
        
        $objs[] =& $mform->createElement('hidden', $this->_name, null);
        $mform->setType($this->_name, PARAM_INT);
        $grp =& $mform->addElement('group', $this->_name.'_grp', $this->_label, $objs, '', false);
        $mform->addHelpButton($this->_name.'_grp', $this->_name, 'local_f2_domains');
        
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
            if ($formdata->$field == '') {
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
     * @return sql string
     */
    function get_sql_filter($data) {
        global $DB, $CFG;
        static $counter = 0;
        $name = 'ex_text'.$counter++;

        $operator = $data['operator'];
        $value    = $data['value'];

        if ($value === '') {
            return '';
        }

        switch($operator) {
            case 0: // equal to
                $res = "id = $value";
                break;
            case 1: // descendent
                $res = "FIND_SUBTREE - $value";
                break;
            case 2: // direzioni in
                $res = "FIND_DIREZIONE - $value";
                break;
            default:
                return '';
        }
        return $res;
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
            case 1: // is descendent of
                return get_string('textlabel', 'filters', $a);
            case 2: // direzioni contenute in
                return "Le direzioni contenute in $a->value";
        }

        return '';
    }
}
