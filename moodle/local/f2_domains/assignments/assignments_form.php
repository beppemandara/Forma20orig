<?php
/*
 * $Id$
 */
require_once($CFG->dirroot.'/lib/formslib.php');

class user_organisation_assignment_form extends moodleform {

    // Define the form
    function definition () {
        global $DB;

        $mform =& $this->_form;
//var_dump($this->_customdata);
        $organisation_id = $this->_customdata['organisationid'];
        $viewableid = $this->_customdata['viewableorganisationid'];
        $userid = $this->_customdata['userid'];

        // Get organisation title
        $organisation_title = '';
        if ($organisation_id) {
            $sql = "select concat(idnumber, ' - ', fullname) as fullname from {org} where id = ?";
            $organisation_title = $DB->get_field_sql($sql, array($organisation_id));
        }

        $tree_root = get_root_framework();
        if (!is_null($tree_root)) {
            $hierarchy = recursivesubtreejson($tree_root->id, $tree_root->fullname);
        } else {
            $hierarchy = '';
        }
        
        $mform->addElement('hidden', 'userid');
        $mform->setDefault('userid', $userid);
        $mform->addElement('static', 
                    'organisationselector', 
                    get_string('organisation', 'local_f2_domains'), 
                    get_organisation_picker_html('organisationtitle', 
                            'organisationid', 
                            get_string('chooseorganisation', 'local_f2_domains'), 
                            'domini', 
                            $hierarchy, 
                            $organisation_title));

        $mform->addElement('hidden', 'organisationid');
        $mform->setType('organisationid', PARAM_INT);
        $mform->setDefault('organisationid', $organisation_id ? $organisation_id : 0);
        
        $mform->addHelpButton('organisationselector', 'chooseorganisation', 'local_f2_domains');
        
        //////////// DOMINI VISIBILI ///////////////////
        
        // Get organisation title
        $viewable_title = '';
        if ($viewableid) {
            $sql = "select concat(idnumber, ' - ', fullname) as fullname from {org} where id = ?";
            $viewable_title = $DB->get_field_sql($sql, array($viewableid));
        }

        $mform->addElement('static', 
                    'viewableorganisationselector', 
                    get_string('viewable_organisation', 'local_f2_domains'), 
                    get_organisation_picker_html('viewableorganisationtitle', 
                                'viewableorganisationid', 
                                get_string('choose_viewable_organisation', 'local_f2_domains'), 
                                'domini_visibilita', 
                                $hierarchy, 
                                $viewable_title));

        $mform->addElement('hidden', 'viewableorganisationid');
        $mform->setType('viewableorganisationid', PARAM_INT);
        $mform->setDefault('viewableorganisationid', $viewableid ? $viewableid : 0);
        
        $mform->addHelpButton('viewableorganisationselector', 'choose_viewable_organisation', 'local_f2_domains');
        
        //////////// FINE DOMINI VISIBILI ///////////////////

        //echo '<center>';
        //$this->add_action_buttons(true, get_string('updateassignment', 'local_f2_domains'));
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('updateassignment', 'local_f2_domains'), array('title'=>get_string('savechanges')));
        $buttonarray[] = &$mform->createElement('button', 'cleanupvisibilitydom', 'Rimuovi assegnazione referente', array('title'=>'Rimuove assegnazione dominio di visibilità'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
        //echo '</center>';
    }

//    function definition_after_data() {
//        $mform =& $this->_form;
//
//        // do nothing
//    }

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

    function validation($data, $files) {

        $mform =& $this->_form;

        $result = parent::validation($data, $files);

        // Check that an organisation was set
        if (!$mform->getElement('organisationid')->getValue()) {
            array_push($result, get_string('noorganisationset', 'local_f2_domains'));
        }
        
        // Check that a viewable organisation was set
//        if (!$mform->getElement('viewableorganisationid')->getValue()) {
//            if ($result !== '') {
//                $result .= '</br>';
//            }
//            $result.= get_string('noviewableorganisationset', 'local_f2_domains');
//        }

        return implode(";", $result);
    }
}
