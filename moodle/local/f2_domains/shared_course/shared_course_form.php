<?php

require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/local/f2_support/lib.php');
//require_once($CFG->dirroot.'/idp/lib.php');

class shared_course_form extends moodleform {

    // Define the form
    function definition () {
        global $DB;
        
        $use_role_as_filter = $this->_customdata['use_role_as_filter'];
        $mform =& $this->_form;
        $param = get_parametro('p_f2_id_ruolo_referente_formativo');
        $param2 = get_parametro('p_f2_id_ruolo_referente_settore');
        $param3 = get_parametro('p_f2_id_ruolo_referente_scuola');
        $referente_formativo_id = $param->val_int;
        $referente_settore_id = $param2->val_int;
        $referente_scuola_id = $param3->val_int;

        if ($use_role_as_filter == $referente_formativo_id) {
            $mform->addElement('header', 'assign_all_header', get_string('assign_all_header', 'local_f2_domains'));
        } else if ($use_role_as_filter == $referente_settore_id) {
            $mform->addElement('header', 'assign_all_ref_set_header', get_string('assign_all_ref_set_header', 'local_f2_domains'));
        } else if ($use_role_as_filter == $referente_scuola_id) {
            $mform->addElement('header', 'assign_all_ref_scuola_header', get_string('assign_all_ref_scuola_header', 'local_f2_domains'));
        } else {
            $mform->addElement('header', 'bulk_assign_header', get_string('bulk_assign_header', 'local_f2_domains'));
        }
        
        $mform->addElement('checkbox', 'all_ref', '',
                    " " . get_string('all_referenti_supervisori', 'local_f2_domains'));
        $mform->setDefault('all_ref', false);
        $mform->addHelpButton('all_ref', 'all_referenti_supervisori', 'local_f2_domains');
        
        if (!$use_role_as_filter) {
            $mform->addElement('checkbox', 'ref_dir_prop', '',
                        " " . get_string('ref_dir_proponente', 'local_f2_domains'));
            $mform->setDefault('ref_dir_prop', false);
            $mform->disabledIf('ref_dir_prop', 'all_ref', 'checked');
            $mform->addHelpButton('ref_dir_prop', 'ref_dir_proponente', 'local_f2_domains');

            $mform->addElement('checkbox', 'ref_dir_consiglio', '',
                        " " . get_string('ref_dir_consiglio', 'local_f2_domains'));
            $mform->setDefault('ref_dir_consiglio', false);
            $mform->disabledIf('ref_dir_consiglio', 'all_ref', 'checked');
            $mform->addHelpButton('ref_dir_consiglio', 'ref_dir_consiglio', 'local_f2_domains');

            $mform->addElement('checkbox', 'ref_dir_giunta', '',
                        " " . get_string('ref_dir_giunta', 'local_f2_domains'));
            $mform->setDefault('ref_dir_giunta', false);
            $mform->disabledIf('ref_dir_giunta', 'all_ref', 'checked');
            $mform->addHelpButton('ref_dir_giunta', 'ref_dir_giunta', 'local_f2_domains');

            $mform->addElement('checkbox', 'ref_dir_enti', '',
                        " " . get_string('ref_dir_enti_esterni', 'local_f2_domains'));
            $mform->setDefault('ref_dir_enti', false);
            $mform->disabledIf('ref_dir_enti', 'all_ref', 'checked');
            $mform->addHelpButton('ref_dir_enti', 'ref_dir_enti_esterni', 'local_f2_domains');

            $mform->addElement('checkbox', 'supervisori_forma', '',
                        " " . get_string('supervisori_forma', 'local_f2_domains'), 'disabled="disabled"'); // sempre disabilitato
            $mform->setDefault('supervisori_forma', true);
            $mform->addHelpButton('supervisori_forma', 'supervisori_forma', 'local_f2_domains');
        }
        
        //echo '<center>';
        $buttonarray=array();
        if (!$use_role_as_filter) {
            $buttonarray[] = &$mform->createElement('submit', 'add', get_string('assign_other_users', 'local_f2_domains'), 'onClick="return confirmSubmit(\'Sei sicuro di voler proseguire?\')"');
            $buttonarray[] = &$mform->createElement('submit', 'clear', get_string('clear', 'local_f2_domains'), 'onClick="return confirmSubmit(\'Sei sicuro di voler ripulire le assegnazioni?\')"');
        } else {
            $buttonarray[] = &$mform->createElement('submit', 'update', get_string('filter_users', 'local_f2_domains'));
        }
            
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
//        $this->add_action_buttons(false, get_string('assign_other_users', 'local_f2_domains'));
        //echo '</center>';
    }

    function definition_after_data() {
        $mform =& $this->_form;

        // do nothing
    }

    function freezeForm() {
        $mform =& $this->_form;

        // Freeze values
        $mform->hardFreezeAllVisibleExcept(array());

        // Hide elements with no values
        foreach (array_keys($mform->_elements) as $key) {

            $element =& $mform->_elements[$key];

            // Check static elements differently
            if ($element->getType() == 'static') {
                // Check if it is a js selector
                if (substr($element->getName(), -8) == 'selector') {
                    // Get id element
                    $elementid = $mform->getElement(substr($element->getName(), 0, -8).'id');

                    if (!$elementid || !$elementid->getValue()) {
                        $mform->removeElement($element->getName());
                    }

                    continue;
                }
            }

            // Get element value
            $value = $element->getValue();

            // Check groups
            // (matches date groups and action buttons)
            if (is_array($value)) {

                // If values are strings (e.g. buttons, or date format string), remove
                foreach ($value as $k => $v) {
                    if (!is_numeric($v)) {
                        $mform->removeElement($element->getName());
                        break;
                    }
                }
            }
            // Otherwise check if empty
            elseif (!$value) {
                $mform->removeElement($element->getName());
            }
        }
    }

    function validation() {
        $mform =& $this->_form;
        $result = '';

        return $result;
    }
	
	function display() {
		parent::display();
$str = <<<'EOF'
<script type="text/javascript">
//<![CDATA[

function confirmSubmit(testo)
{
var agree=confirm(testo);
if (agree)
	return true ;
else
	return false ;
}

//]]>
</script>
EOF;
echo $str;
	
	}
}
