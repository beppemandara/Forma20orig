<?php

// $Id: forzature_form.php 1153 2013-05-30 15:43:41Z d.lallo $

global $CFG;

require_once $CFG->libdir . '/formslib.php';
require_once 'lib.php';
require_once($CFG->dirroot.'/f2_lib/management.php');

class forzature_form extends moodleform {
    public function definition() {

        $mform =& $this->_form;
     //   $mform->addElement('html', '<table><tr><td>');
       // $mform->addElement('text', 'cognome', get_string('lastname'));
     //   $mform->addElement('html', '</td><td>');
       // $mform->addElement('html', '<span>');
      //  $mform->addElement('submit', 'send', get_string('cerca', 'block_f2_formazione_individuale'));
    //    $mform->addElement('html', '</span>');
     //   $mform->addElement('html', '</td><tr><table>');
        

        $buttons = array(
        	$mform->createElement('text', 'cognome', get_string('lastname')),
        	$mform->createElement('submit', 'send', get_string('cerca', 'block_f2_formazione_individuale'))
        );
        
	$mform->addGroup($buttons, 'actions', 'Cognome: ', array(' '), false);
    }

}

class dettagli_forzatura_form extends moodleform {
    public function definition() {

        $mform =& $this->_form;
        $user = $this->_customdata['user'];
        $edit = $this->_customdata['edit'];
        
        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);
        $mform->setDefault('userid', $user->id);
        
        $mform->addElement('hidden', 'edit');
        $mform->setType('edit', PARAM_BOOL);
        $mform->setDefault('edit', false);
        
        $mform->addElement('hidden', 'forzatura_id');
        $mform->setType('forzatura_id', PARAM_INT);
        $mform->setDefault('forzatura_id', 0);
        
        // cerco sulla tabella org_assignment il dominio di appartenenza dell'utente
        $organisation = get_user_organisation($user->id);

        if ($edit) {
            $mform->addElement('text', 'matricola', get_string('matricola', 'block_f2_formazione_individuale'), array('disabled' => 'disabled'));
        } else {
            $mform->addElement('text', 'matricola', get_string('matricola', 'block_f2_formazione_individuale'));
            $mform->addRule('matricola', null, 'required', 'client');
        }
        $mform->setDefault('matricola', $user->idnumber);
        
        $mform->addElement('text', 'email', get_string('email'), array('size' => '50'));
        $mform->addRule('email', null, 'required', 'client');
        $mform->setDefault('email', $user->email);
        
        // Get organisation title
        $organisation_title = '';
        
        if (!is_null($organisation)) {
            $organisation_title = $organisation[2].' - '.$organisation[1];
        }
        $parametri_regioni = get_parametri_by_prefix('p_f2_dominio_radice_regione_');
	$id_radici_regione_array = array();
	foreach ($parametri_regioni as $param) {
		$id_radici_regione_array[] = $param->val_int;
	}
        $tree_root = get_root_framework();
        if (!is_null($tree_root)) {
            $hierarchy = recursivesubtreejson($tree_root->id, $tree_root->fullname, $id_radici_regione_array);
        } else {
            $hierarchy = '';
        }
        
        $mform->addElement('text', 'organisationtitle', get_string('organisation', 'local_f2_domains'), array('size' => '50', 'readonly' => 'readonly'));
        $mform->addRule('organisationtitle', null, 'required', 'client');
        $mform->setDefault('organisationtitle', $organisation_title);
        
        $mform->addElement('static', 'organisationselector', '', get_organisation_picker_html_with_text_box('organisationtitle', 'organisationid', get_string('chooseorganisation', 'local_f2_domains'), 'domini', $hierarchy, $organisation_title));
        $mform->addElement('hidden', 'organisationid');
        $mform->setType('organisationid', PARAM_INT);
        $mform->setDefault('organisationid', !is_null($organisation) ? $organisation[0] : 0);
        
        $mform->addElement('date_selector', 'data_fine', get_string('data_fine', 'block_f2_formazione_individuale'));
        $mform->addRule('data_fine', null, 'required', 'client');
        
        $mform->addElement('textarea', 'note', get_string('note', 'block_f2_formazione_individuale'));

        $gestionecorsi_url_back = new moodle_url("{$CFG->wwwroot}/blocks/f2_formazione_individuale/forzature.php");
        $buttons = array(
            $mform->createElement('submit', 'save', get_string('salva', 'block_f2_formazione_individuale'))
            ,$mform->createElement('reset', 'reset', get_string('pulisci', 'block_f2_formazione_individuale'))
            ,$mform->createElement('button', 'cancel', get_string('indietro', 'block_f2_formazione_individuale'),'onclick="if (confirm(\'Tornando indietro non verrà salvata nessuna modifica.\nProseguire?\')) parent.location=\''.$gestionecorsi_url_back.'\'"')
        );

        $mform->addGroup($buttons, 'actions', '&nbsp;', array(' '), false);
    }

    function validation($data) {

    }
}