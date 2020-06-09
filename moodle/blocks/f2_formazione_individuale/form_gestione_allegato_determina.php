<?php
// $Id$

global $CFG;

require_once($CFG->libdir.'/formslib.php');
require_once '../../config.php';
require_once($CFG->dirroot.'/local/f2_support/lib.php');
require_once('lib.php');

class gestione_allegato_determina_form extends moodleform {
    public function definition() {

    	$mform =& $this->_form;
    	
    	
    	$tipo_determina = $this->_customdata['tipo_determina'];
    	$cerca = $this->_customdata['search'];
    	
    	$start_date = $this->_customdata['start_date'];
    	$end_date = $this->_customdata['end_date'];
    	
    	    	
 		$mform->addElement('select', 'tipo_determina', get_string('visualizza', 'block_f2_formazione_individuale').':',array('codiceprovvisoriodetermina'=>get_string('codice_provvisorio_determina','block_f2_formazione_individuale'),'codicedeterminadefinitivo'=>get_string('codice_determina_definitivo','block_f2_formazione_individuale')),array('onChange'=>'my_redirect(this)'));
 		$mform->setDefault('tipo_determina', $tipo_determina);
 		 		
 		if($tipo_determina == 'codicedeterminadefinitivo'){
 			
 			$giorni_allegati_prefix = get_parametri_by_prefix('p_f2_corsiind_giorni_determine_x_allegati');
 			$giorni_allegati = $giorni_allegati_prefix['p_f2_corsiind_giorni_determine_x_allegati']->val_int;
 			
 			
	 		$mform->addElement('date_selector', 'data_inizio', get_string('data_inizio', 'block_f2_formazione_individuale').':');
	 		//$mform->setDefault('data_inizio', strtotime('-'.$giorni_allegati.' day',time()));
	 		$mform->setDefault('data_inizio',$start_date);
	 		$mform->addElement('date_selector', 'data_fine', get_string('data_fine', 'block_f2_formazione_individuale').':');
	 		$mform->setDefault('data_fine',$end_date);
 		}
 	//	$mform->addElement('text', 'cerca', get_string('cerca', 'block_f2_formazione_individuale').':', array('size' => '50'));
     //   $mform->setDefault('cerca', $cerca);

        $buttons = array(
        		$mform->createElement('text', 'cerca', get_string('cerca', 'block_f2_formazione_individuale').':', array('size' => '50')),
        		
            $mform->createElement('submit', 'save', get_string('cerca', 'block_f2_formazione_individuale'))
           // ,$mform->createElement('reset', 'reset', get_string('pulisci', 'block_f2_formazione_individuale'))
        );
        $mform->setDefault('cerca', $cerca);
        $mform->addGroup($buttons, 'actions', 'Cerca: ', array(' '), false);

}
			
}


