<?php
require_once($CFG->libdir.'/formslib.php');
/**
 * Formazione Individuale Budget - form definition
 * WARNING: utilizzato da fi
 */
class fi_budget_form extends moodleform {
    public function definition() {
            $mform =& $this->_form;
            
            $year             = $this->_customdata['anno_in_corso'];
            $training_options = $this->_customdata['training_options'];
            $training_default = $this->_customdata['training_default'];
            $exportable       = array_key_exists('exportable', $this->_customdata) ? $this->_customdata['exportable'] : FALSE;
            $setupneworgsonly = array_key_exists('setupneworgsonly', $this->_customdata) ? $this->_customdata['setupneworgsonly'] : FALSE;

            $mform->addElement('hidden', 'year', $year);
            $mform->addElement('static', 'str_year', get_string('year'), $year);
            $attributes = array();
            $mform->addElement('select', 'training', get_string('tipo_formazione', 'local_f2_traduzioni'), $training_options, $attributes);
            $mform->setDefault('training', $training_default);
            $mform->addElement('text', 'org', get_string('ricerca','local_f2_traduzioni'), 'maxlength="254" size="50"');
            
            $buttonarray = array();
            
            //AK-LM: hack to make it the default submit button on return key press
            $buttonarray[] = &$mform->createElement('submit', 'Cerca', get_string('search'), 'class="hidden"');
            if ($exportable) {
                $buttonarray[] = &$mform->createElement('submit', 'bexport', get_string('exportbudget', 'block_f2_formazione_individuale'));
            } 
            if ($setupneworgsonly) {
                $buttonarray[] = &$mform->createElement('submit', 'bsetupneworgs', get_string('setupneworgsonly', 'block_f2_formazione_individuale'), 
                        array('onClick'=>'return confirmAction("Confermi la creazione del budget per le sole direzioni attivate successivamente alla prima immissione del budget?");',
                            'class'=>'setupbdgtneworgs'));
            }
            $buttonarray[] = &$mform->createElement('submit', 'Cerca', get_string('search'));
            $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
            $mform->closeHeaderBefore('buttonar');
    }
    
    public function getFormId() {
        return $this->_form->getAttribute('id');
    }
}
