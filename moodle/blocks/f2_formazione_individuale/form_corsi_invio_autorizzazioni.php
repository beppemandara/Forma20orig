<?php
// $Id$

global $CFG;

require_once($CFG->libdir.'/formslib.php');
require_once '../../config.php';
require_once($CFG->dirroot.'/local/f2_support/lib.php');
require_once('lib.php');


    class corsi_autorizzazione_determina_form extends moodleform {
    	public function definition() {
    
    		$mform =& $this->_form;

    		$id_determina = $this->_customdata['id_determina'];
    		 
    		$mform->addElement('hidden', 'codice_determina');
    		$mform->setType('codice_determina', PARAM_TEXT);
    		$mform->setDefault('codice_determina', $id_determina);
    		
    		$cerca = $this->_customdata['search'];

    		$mform->addElement('text', 'cerca', get_string('cognome', 'block_f2_formazione_individuale').':', array('size' => '50'));
    		$mform->setDefault('cerca', $cerca);
    
    		$buttons = array(
    				$mform->createElement('submit', 'save', get_string('cerca', 'block_f2_formazione_individuale'))
    				,$mform->createElement('reset', 'reset', get_string('reset', 'block_f2_formazione_individuale'))
    		);
    
    		$mform->addGroup($buttons, 'actions', '&nbsp;', array(' '), false);
    
    	}
    		
    }