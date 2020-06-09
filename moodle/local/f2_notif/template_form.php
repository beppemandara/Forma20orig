<?php
// $id$
// Written at Louisiana State University

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot.'/local/f2_notif/lib.php');

class template_form extends moodleform {
    public function definition() {
        global $USER;

        $mform =& $this->_form;

        $mform->addElement('hidden', 'id_templ');	
        $mform->addElement('hidden', 'userid', $USER->id);

		$tipo_notif = get_tipo_notif(-1,'-1');//ricavo id-nome del tipo di notifica
        $notif_ind = get_tipo_notif_byname(F2_TIPO_NOTIF_INDIVIDUALE);
		//print_r($tipo_notif);exit;
		$array_tipo_notif = array();
		foreach($tipo_notif as $notifica){
			$array_tipo_notif[$notifica->id] = ''.$notifica->nome.'';
		}
		//print_r($array_tipo_notif);exit;
		$select = $mform->addElement('select', 'id_tipo_notif', get_string('tipo', 'local_f2_notif'),$array_tipo_notif,'onchange="change_notif($(this).val())"');
		$select = $mform->addElement('select', 'stato', get_string('stato', 'local_f2_notif'),array('1'=>'Attivo','0'=>'Non attivo'));
		//$mform->addElement('html','<div id="individuale_no">');
		$select = $mform->addElement('select', 'canale', get_string('canale', 'local_f2_notif'),array('0'=>'Aula','1'=>'On-Line'));
		$mform->addElement('checkbox', 'predefinito', 'Default');
        $mform->disabledIf('predefinito', 'id_tipo_notif', 'eq', $notif_ind);
		//$mform->addElement('html','</div>');

	//	$mform->addElement('text', 'stato', get_string('stato', 'local_f2_notif'),array('size'=>80,'maxlength'=>80));
		
		$mform->addElement('text', 'title', get_string('title', 'local_f2_notif'),array('size'=>77,'maxlength'=>77));
		$mform->addElement('textarea', 'description', get_string('description', 'local_f2_notif'),array('rows'=>8,'cols'=>80));
        $mform->addElement('text', 'subject', get_string('subject', 'local_f2_notif'),array('size'=>77,'maxlength'=>77));
        $mform->addElement('editor', 'message_editor', get_string('message_editor', 'local_f2_notif'),null, $this->_customdata['message_options']);
		
        $mform->setType('message_editor', PARAM_RAW);
		
	//	$mform->addElement('submit', 'Salva', 'Salva','onClick="return confirmPredefinito()"');
	//	$mform->addElement('button', 'indietro', get_string('indietro', 'block_f2_formazione_individuale'),'onclick="confirm_back(\''.$alert.'\')"');
		
        $buttons = array(
            $mform->createElement('submit', 'Salva', 'Salva','onClick="return confirmPredefinito()"'),
            $mform->createElement('button', 'indietro', 'Indietro','onclick="if(confirm(\'Tornando indietro non verrà salvato nessun dato.\nProseguire?\')) parent.location=\'templates.php\';"')
        );

        $mform->addGroup($buttons, 'buttons', '&nbsp;', array(' '), false);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addRule('subject', null, 'required', null, 'client');
        $mform->addRule('description', null, 'required', null, 'client');
        $mform->addRule('message_editor', null, 'required', null, 'client');
    }
}
