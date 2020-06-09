<?php //$Id: show_options_form.php 34 2012-08-28 12:30:08Z c.arnolfo $

require_once($CFG->libdir.'/formslib.php');

class hierarchy_show_options_form extends moodleform {

    function definition() {
        $mform =& $this->_form;

        $mform->addElement('header', 'displayoptions', get_string('displayoptions','local_f2_domains'));

        $mform->addElement('checkbox', 'hidecustomfields', get_string('hidecustomfields', 'local_f2_domains'));
        $mform->setType('hidecustomfields', PARAM_BOOL);

        $mform->addElement('checkbox', 'showitemfullname', get_string('showitemfullname', 'local_f2_domains'));
        $mform->setType('showitemfullname', PARAM_BOOL);

        $mform->addElement('checkbox', 'showdepthfullname', get_string('showdepthfullname', 'local_f2_domains'));
        $mform->setType('showdepthfullname', PARAM_BOOL);

        $mform->addElement('hidden', 'frameworkid', $this->_customdata['framework']->id);
        $mform->setType('frameworkid', PARAM_INT);

        $mform->addElement('hidden', 'spage', $this->_customdata['spage']);
        $mform->setType('spage', PARAM_INT);

        $mform->addElement('hidden', 'type', $this->_customdata['type']);
        $mform->setType('type', PARAM_SAFEDIR);

        $mform->addElement('submit', 'submitbutton', get_string('savechanges'));
    }
}


