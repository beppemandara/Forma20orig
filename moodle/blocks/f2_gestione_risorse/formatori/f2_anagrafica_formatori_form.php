<?php

// $Id: f2_anagrafica_formatori_form.php 1150 2013-05-28 15:27:10Z d.lallo $

require_once $CFG->libdir . '/formslib.php';
require_once '../lib.php';

class anagrafica_formatori_form extends moodleform {
    public function definition() {
        global $CFG;

        $mform =& $this->_form;
        $mform->addElement('text', 'cognome_formatore', get_string('cognome_formatore', 'block_f2_gestione_risorse'));
        $mform->setType('cognome_formatore', PARAM_TEXT);

        $buttons = array(
            $mform->createElement('submit', 'send', get_string('cerca', 'block_f2_gestione_risorse'))
            //,$mform->createElement('reset', 'reset', get_string('reset', 'block_f2_gestione_risorse'))
        );
        
		$categorie = $this->_customdata['cat_formatori'];
		$categorie_attrs = array('onchange' => "submit()");
		$select = $mform->addElement('select', 'categoria_formatore', get_string('categoria_formatore', 'block_f2_gestione_risorse'),array_keys($categorie), $categorie_attrs);
		//$select = $mform->addElement('select', 'categoria_formatore', get_string('categoria_formatore', 'block_f2_gestione_risorse'), $categorie, $categorie_attrs);
//print_r(array_keys($categorie));
//print_r($categorie);
                $select->setSelected('0');
		$categoria_default = array_keys($categorie,1);
		$mform->setDefault('categoria_formatore',array_search($categoria_default[0],array_keys($categorie)));
		$mform->addGroup($buttons, 'actions', '&nbsp;', array(' '), false);
    }

    function validation($data) {
        $errors = array();
        foreach(array('subject', 'body', 'noreply') as $field) {
            if(empty($data[$field]))
                $errors[$field] = get_string('email_error_field', 'block_f2_gestione_risorse', $field);
        }
    }
}

class anagrafica_formatori_form2 extends moodleform {
    public function definition() {
        global $CFG;

        $mform =& $this->_form;
		$mform->addElement('hidden', 'userid', '0');
		$mform->addElement('hidden', 'formatore_id', '0');
		$mform->addElement('hidden', 'action', '0');
		
		$tipoStudio = array('D'=>get_string('diploma', 'block_f2_gestione_risorse'),'L'=>get_string('laurea', 'block_f2_gestione_risorse'));
		$mform->addElement('select', 'tstudio', get_string('titolo_studio', 'block_f2_gestione_risorse'), $tipoStudio);
		$mform->addRule('tstudio', null, 'required', 'client');
		
				
		$mform->addElement('text', 'dettstudio', get_string('titolo_studio_dettagli', 'block_f2_gestione_risorse'));
		
		$tipodocArr = array('I'=>get_string('doc_interna', 'block_f2_gestione_risorse'),'E'=>get_string('doc_esterna', 'block_f2_gestione_risorse'));
		$select_tipo_docenza = $mform->addElement('select', 'tipodoc', get_string('tipo_docenza', 'block_f2_gestione_risorse'), $tipodocArr);
		$select_tipo_docenza->setMultiple(true);
		
		$mform->addElement('advcheckbox', 'flag_interno', get_string('flag_interno', 'block_f2_gestione_risorse'),'', null,array(0,1));
		$mform->setDefault('flag_interno', 1);
		$mform->disabledIf('tipodoc', 'flag_interno','unchecked');
		
		$mform->addElement('text', 'prof', get_string('professione', 'block_f2_gestione_risorse'));
		$mform->addElement('text', 'piva', get_string('piva', 'block_f2_gestione_risorse'));
		$mform->addElement('text', 'ente', get_string('ente', 'block_f2_gestione_risorse'));
		
		$select_subareers = get_sub_aree_formative();
		$subArr = array();
		foreach ($select_subareers as $sub)
		{
			$subArr[$sub->id] = $sub->descrizione;
		}
		$select_subaree_form = $mform->addElement('select', 'aree_form', get_string('aree_form', 'block_f2_gestione_risorse'),$subArr);
		$select_subaree_form->setMultiple(true);
        $buttons = array(
            $mform->createElement('submit', 'send', get_string('ok', 'block_f2_gestione_risorse'))
            ,$mform->createElement('reset', 'reset', get_string('ripristina', 'block_f2_gestione_risorse'))
            ,$mform->createElement('cancel', 'cancel', get_string('annulla', 'block_f2_gestione_risorse'))
        );
		$mform->addGroup($buttons, 'actions', '&nbsp;', array(' '), false);
    }

    function validation($data) {

    }
}
class anagrafica_formatori_form3 extends moodleform {
    public function definition() {
        global $CFG;

        $mform =& $this->_form;
        $post_values = $this->_customdata['post_values'];
//      $mform->setType('post_values', PARAM_RAW);
//        if (isset($post_values) and (!is_null($post_values)) and (!empty($post_values)))
//        {
//      		$post_values = json_encode($post_values);
//	        $mform2->addElement('hidden', 'post_values',$post_values);
//        }
		$mform->addElement('text', 'cognome_utente', get_string('cognome_utente', 'block_f2_gestione_risorse'));
        $buttons = array(
            $mform->createElement('submit', 'send', get_string('cerca', 'block_f2_gestione_risorse'))
//            ,$mform->createElement('reset', 'reset', get_string('reset', 'block_f2_gestione_risorse'))
        );
        $mform->addGroup($buttons, 'actions', '&nbsp;', array(' '), false);
    }

    function validation($data) {
        $errors = array();
        foreach(array('subject', 'body', 'noreply') as $field) {
            if(empty($data[$field]))
                $errors[$field] = get_string('email_error_field', 'block_f2_gestione_risorse', $field);
        }
    }
}
class anagrafica_formatori_form4 extends moodleform {
    public function definition() {
        global $CFG;

        $mform =& $this->_form;
        $buttons = array(
            $mform->createElement('submit', 'send', get_string('aggiungi_sel_formatori', 'block_f2_gestione_risorse'))
            ,$mform->createElement('cancel', 'cancel', get_string('annulla_aggiunta_formatore', 'block_f2_gestione_risorse'))
        );
        $mform->addGroup($buttons, 'actions', '&nbsp;', array(' '), false);
    }

    function validation($data) {

    }
}
?>
