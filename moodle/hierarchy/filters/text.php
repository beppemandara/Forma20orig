<?php //$Id$

/**
 * Generic filter for text fields.
 */
class hierarchy_filter_text extends hierarchy_filter_type {
    var $_field;

    /**
     * Constructor
     * @param string $name the name of the filter instance
     * @param string $label the label of the filter instance
     * @param boolean $advanced advanced form element flag
     * @param string $field feature table field name
     */
    function hierarchy_filter_text($name, $label, $advanced, $field) {
        parent::hierarchy_filter_type($name, $label, $advanced);
        $this->_field = $field;
    }

    /**
     * Returns an array of comparison operators
     * @return array of comparison operators
     */
    function getOperators() {
        return array(0 => get_string('contains', 'filters'),
                     1 => get_string('doesnotcontain','filters'),
                     2 => get_string('isequalto','filters'),
                     3 => get_string('startswith','filters'),
                     4 => get_string('endswith','filters'),
                     5 => get_string('isempty','filters'));
    }

    /**
     * Adds controls specific to this filter in the form.
     * @param object $mform a MoodleForm object to setup
     */
    function setupForm(&$mform, $type=null) {
        $objs = array();
        $objs[] =& $mform->createElement('select', $this->_name.'_op', null, $this->getOperators());
        $objs[] =& $mform->createElement('text', $this->_name, null);
        $mform->setType($this->_name, PARAM_TEXT);
        $grp =& $mform->addElement('group', $this->_name.'_grp', $this->_label, $objs, '', false);
//        print_r('$this->_label: '.$this->_label);
//        print_r('   $this->_name: '.$this->_name);
        $mform->addHelpButton($this->_name.'_grp', $this->_name, 'local_f2_domains');
//        $grp->setHelpButton(array('text',$this->_label,'filters'));
        
        $mform->disabledIf($this->_name, $this->_name.'_op', 'eq', 5);
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
            if ($formdata->$operator != 5 and $formdata->$field == '') {
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
     * @return string the filtering condition or null if the filter is disabled
     */
    function get_sql_filter($data) {
        global $DB;
        
        $operator = $data['operator'];
        $value    = addslashes($data['value']);
        $field    = $this->_field;

        if ($operator != 5 and $value === '') {
            return '';
        }

//        $ilike = sql_ilike();

        switch($operator) {
            case 0: // contains
                $res = "LIKE '%$value%'"; break;
            case 1: // does not contain
                $res = "NOT LIKE '%$value%' OR ($field) IS NULL )";
                $field = "($field"; break;
            case 2: // equal to
                $res = "LIKE '$value'"; break;
            case 3: // starts with
                $res = "LIKE '$value%'"; break;
            case 4: // ends with
                $res = "LIKE '%$value'"; break;
            case 5: // empty
                $res = "='' OR ($field) IS NULL )";
                $field = "($field"; break;
            default:
                return '';
        }
        return $field.' '.$res;
    }

    /**
     * Returns a human friendly description of the filter used as label.
     * @param array $data filter settings
     * @return string active filter label
     */
    function get_label($data, $type) {
        $operator  = $data['operator'];
        $value     = $data['value'];
        $operators = $this->getOperators();

        $a = new object();
        $a->label    = $this->_label;
        $a->value    = '"'.s($value).'"';
        $a->operator = $operators[$operator];


        switch ($operator) {
            case 0: // contains
            case 1: // doesn't contain
            case 2: // equal to
            case 3: // starts with
            case 4: // ends with
                return get_string('textlabel', 'filters', $a);
            case 5: // empty
                return get_string('textlabelnovalue', 'filters', $a);
        }

        return '';
    }
}
