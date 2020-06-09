<?php
// $Id$

global $CFG;

require_once($CFG->libdir.'/formslib.php');
require_once '../../config.php';
require_once($CFG->dirroot.'/local/f2_support/lib.php');
require_once('lib.php');

class determina_form extends moodleform {
    public function definition() {

    	$mform =& $this->_form;
    	
    	$id_codice_provvisorio_determina = $this->_customdata['id_codice_provvisorio_determina'];
    	$training = $this->_customdata['training'];
    	
    	$cod_det = $this->_customdata['cod_det']; 
    	$cod_pro = $this->_customdata['cod_pro']; 
    	$cod_dat = $this->_customdata['cod_dat'];
    	$cod_datp = $this->_customdata['cod_datp']; 
    	$cod_aef = $this->_customdata['cod_aef']; 

    	$mform->addElement('hidden', 'id_codice_provvisorio_determina');
    	$mform->setType('id_codice_provvisorio_determina', PARAM_INT);
    	$mform->setDefault('id_codice_provvisorio_determina', $id_codice_provvisorio_determina);
    	
    	
        $mform->addElement('text', 'numero_determina', get_string('numero_determina', 'block_f2_formazione_individuale').':', array('size' => '50'));
        $mform->addRule('numero_determina', null, 'required', 'client');
        $mform->setDefault('numero_determina', $cod_det);
        $mform->addRule('numero_determina', null, 'maxlength', 50, 'client');
        
        $mform->addElement('date_selector', 'data_determina', get_string('data_determina', 'block_f2_formazione_individuale').':');
        $mform->addRule('data_determina', null, 'required', 'client');
        if($cod_dat){
        	$mform->setDefault('data_determina', $cod_dat);
        }
        
        $mform->addElement('text', 'protocollo', get_string('protocollo', 'block_f2_formazione_individuale').':', array('size' => '50'));
        $mform->addRule('protocollo', null, 'required', 'client');
        $mform->setDefault('protocollo', $cod_pro);
        $mform->addRule('protocollo', null, 'maxlength', 50, 'client');
        
        $mform->addElement('date_selector', 'data_protocollo', get_string('data_protocollo', 'block_f2_formazione_individuale').':');
        $mform->addRule('data_protocollo', null, 'required', 'client');   
        if($cod_datp){
        	$mform->setDefault('data_protocollo', $cod_datp);
        }
        

        $date = date('Y',time());

        $mform->addElement('select', 'anno_esercizio_finanziario', get_string('anno_esercizio_finanziario', 'block_f2_formazione_individuale').':',array($date=>$date,$date-1=>$date-1));
        $mform->addRule('anno_esercizio_finanziario', null, 'required', 'client');
        if($cod_aef){
        	$mform->setDefault('anno_esercizio_finanziario', $cod_aef);
        }
        
        $gestionecorsi_url_back = new moodle_url("{$CFG->wwwroot}/blocks/f2_formazione_individuale/cod_determina_def.php?training=".$training);
        $buttons = array(
            
        	$mform->createElement('submit', 'save', get_string('salva', 'block_f2_formazione_individuale'),'onClick="validateInput()";')
        	,$mform->createElement('reset', 'reset', get_string('pulisci', 'block_f2_formazione_individuale'))
        	,$mform->createElement('button', 'intro', 'Indietro','onclick="if (confirm(\'Tornando indietro non verrà assegnata nessuna determina.\nProseguire?\')) parent.location=\''.$gestionecorsi_url_back.'\'"')
        );

        $mform->addGroup($buttons, 'actions', '&nbsp;', array(' '), false);

}
			
}


