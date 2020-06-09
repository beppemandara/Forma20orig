<?php

// $Id$

require_once '../../../config.php';
require_once '../lib.php';
require_once $CFG->libdir . '/formslib.php';
require_once($CFG->dirroot.'/f2_lib/report.php');

$context = get_context_instance(CONTEXT_SYSTEM);
require_login();
require_capability('block/f2_gestione_risorse:send_auth_mail', $context);

class auth_mail_form extends moodleform {
    public function definition() {
        global $CFG;
		
        $mform =& $this->_form;
        $mform->_attributes['id'] = 'mform1';
        $mform->addElement('text', 'codice_corso', get_string('auth_mail_codice_corso', 'block_f2_gestione_risorse'));

        $buttons = array(
            $mform->createElement('submit', 'send', get_string('cerca', 'block_f2_gestione_risorse'))
            //,$mform->createElement('reset', 'reset', get_string('reset', 'block_f2_gestione_risorse'))
        );
        $selects_attrs = array('onchange' => "submit()");


		if (isset($this->_customdata['data'])) 
		{
			$custom_data = json_decode($this->_customdata['data']);
		}
		else 
		{
			$custom_data = null;
		}
// 		$mform->addElement('hidden', 'data', $this->_customdata['data']);

		
		if (!is_null($custom_data))
		{
			$anno_default = $custom_data->anno_sel;
			$sessione_default = $custom_data->num_sess_sel;
			$num_mese_default = $custom_data->mese_sess_sel;
			$mail_inviate_default = $custom_data->mail_inviate_sel;
		}
		else 
		{
			$anno_default = null;
			$sessione_default = null;
			$num_mese_default = null;
			$mail_inviate_default = null;
		}
		
		if (is_null($anno_default))
		{
			$anno_default = get_anno_formativo_corrente();
			$num_sess_rs = get_numero_sessioni_per_select_form_by_anno($anno_default);
			$sessione_default = array_shift(array_values($num_sess_rs));
			$num_mesi_rs = get_numero_mesi_per_select_form_by_anno($anno_default,$sessione_default);
			$num_mese_default = array_shift(array_values($num_mesi_rs));
			$mail_inviate_default = 'tutto';
		}
	
		
		$anni_rs = get_anni_formativi_sessioni_per_select_form();
		$select_anno = $mform->createElement('select', 'anno_sel', get_string('auth_mail_anno_formativo_cerca', 'block_f2_gestione_risorse'),$anni_rs, $selects_attrs);
		$mform->setDefault('anno_sel',array_search($anno_default,$anni_rs));
		
		$num_sess_rs = get_numero_sessioni_per_select_form_by_anno($anno_default);
		$select_num_sess = $mform->createElement('select', 'num_sess_sel', get_string('auth_mail_num_sess', 'block_f2_gestione_risorse'),$num_sess_rs, $selects_attrs);
		$mform->setDefault('num_sess_sel',array_search($sessione_default,$num_sess_rs));
		
		$num_mesi_rs = get_numero_mesi_per_select_form_by_anno($anno_default,$sessione_default);
		$select_mese_sess = $mform->createElement('select', 'mese_sess_sel', get_string('auth_mail_mese_sess', 'block_f2_gestione_risorse'),$num_mesi_rs, $selects_attrs);
		$mform->setDefault('mese_sess_sel',array_search($num_mese_default,$num_mesi_rs));
		//var_dump($select_mese_sess);exit;
		$mail_inviate_rs = array('tutto' => 'Tutto','si' => 'Sì', 'no' => 'No');
		$select_mail_inviate = $mform->createElement('select', 'mail_inviate_sel', get_string('auth_mail_inviate', 'block_f2_gestione_risorse'),$mail_inviate_rs, $selects_attrs);
		$mform->setDefault('mail_inviate_sel',array_search($mail_inviate_default,$mail_inviate_rs));
		
		
		$mform->addElement($select_anno);
		$mform->addElement($select_num_sess);
		$mform->addElement($select_mese_sess);
		$mform->addElement($select_mail_inviate);
// 		$form_param_default = array('anno_sel' => $anno_default
// 				,'num_sess_sel' => $sessione_default
// 				,'mese_sess_sel' => $num_mese_default
// 				,'mail_inviate_sel' => $mail_inviate_default);
// 		$mform->addElement('hidden', 'data', json_encode($form_param_default));
// 		print_r($form_param_default);
		$mform->addGroup($buttons, 'actions', '&nbsp;', array(' '), false);
// 		$mform->get_data();
    }
    
    function get_form_values()
    {
//     	print_r($name);
//     	print_r('<br/>@@ '.$this->_form->exportValue('anno_sel').' - ');
//     	print_r('<br/>@@ '.$this->_form->exportValue('num_sess_sel').' - ');
//     	print_r('<br/>@@ '.$this->_form->exportValue('mese_sess_sel').' - ');
//     	print_r('<br/>@@ '.$this->_form->exportValue('mail_inviate_sel').' - ');
//     	return $this->_form->exportValue($name);
    	$exp_values = array();
    	$exp_values['anno_sel'] = $this->_form->exportValue('anno_sel');
    	$exp_values['num_sess_sel'] = $this->_form->exportValue('num_sess_sel');
    	$exp_values['mese_sess_sel'] = $this->_form->exportValue('mese_sess_sel');
    	$exp_values['mail_inviate_sel'] = $this->_form->exportValue('mail_inviate_sel');
    	return $exp_values;
    }

    function validation($data) {
        $errors = array();
    }
}

