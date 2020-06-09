<?php
// $Id$

global $CFG;

require_once($CFG->libdir.'/formslib.php');
require_once '../../config.php';
require_once($CFG->dirroot.'/local/f2_support/lib.php');
require_once('lib.php');


    class gestione_autorizzazione_determina_form extends moodleform {
    	public function definition() {
    
    		$mform =& $this->_form;

    		$cerca = $this->_customdata['search'];

    		$mform->addElement('text', 'cerca', get_string('cerca', 'block_f2_formazione_individuale').':', array('size' => '50'));
    		$mform->setDefault('cerca', $cerca);
    
    		$buttons = array(
    				$mform->createElement('submit', 'save', get_string('cerca', 'block_f2_formazione_individuale'))
    				,$mform->createElement('reset', 'reset', get_string('pulisci', 'block_f2_formazione_individuale'))
    		);
    
    		$mform->addGroup($buttons, 'actions', '&nbsp;', array(' '), false);
    
    	}
    		
    }