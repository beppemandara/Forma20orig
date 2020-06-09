<?php

require_once($CFG->dirroot.'/lib/formslib.php');

class depth_edit_form extends moodleform {

    // Define the form
    function definition() {
        global $CFG;

        $mform =& $this->_form;

        $strgeneral  = get_string('general');
        $type   = $this->_customdata['type'];
        $spage  = $this->_customdata['spage'];

        /// Add some extra hidden fields
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'type', $type);
        $mform->setType('type', PARAM_SAFEDIR);
        $mform->addElement('hidden', 'frameworkid');
        $mform->setType('frameworkid', PARAM_INT);
        $mform->addElement('hidden', 'spage', $spage);
        $mform->setType('spage', PARAM_INT);

        /// Print the required moodle fields first
        $mform->addElement('header', 'moodle', $strgeneral);
//        $mform->setHelpButton('moodle', array('depthlevelgeneral', get_string('depthlevel', 'local_f2_domains')), true);
        $mform->addHelpButton('moodle', 'generaldepthlevel', 'local_f2_domains');

        $mform->addElement('text', 'depthlevel', get_string('depthlevel', 'local_f2_domains'));
//        $mform->setHelpButton('depthlevel', array('depthlevel', get_string('depthlevel', 'local_f2_domains')), true);
        $mform->addHelpButton('depthlevel', 'depthlevel', 'local_f2_domains');
        $mform->hardFreeze('depthlevel');

        $mform->addElement('text', 'fullname', get_string('fullnamedepth', 'local_f2_domains'), 'maxlength="254" size="50"');
//        $mform->setHelpButton('fullname', array('depthlevelfullname', get_string('fullnamedepth', 'local_f2_domains')), true);
        $mform->addHelpButton('fullname', 'fullnamedepth', 'local_f2_domains');
        $mform->addRule('fullname', get_string('missingfullnamedepth', 'local_f2_domains'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_MULTILANG);

        $mform->addElement('text', 'shortname', get_string('shortnamedepth', 'local_f2_domains'), 'maxlength="100" size="20"');
//        $mform->setHelpButton('shortname', array('depthlevelshortname', get_string('shortnamedepth', 'local_f2_domains')), true);
        $mform->addHelpButton('shortname', 'shortnamedepth', 'local_f2_domains');
        $mform->addRule('shortname', get_string('missingshortnamedepth', 'local_f2_domains'), 'required', null, 'client');
        $mform->setType('shortname', PARAM_MULTILANG);

        $mform->addElement('htmleditor', 'description', get_string('description'));
//        $mform->setHelpButton('description', array('text', get_string('helptext')), true);
        $mform->addHelpButton('description', 'descriptionframework', 'local_f2_domains');
        $mform->setType('description', PARAM_RAW);

        $this->add_action_buttons();
    }
}
