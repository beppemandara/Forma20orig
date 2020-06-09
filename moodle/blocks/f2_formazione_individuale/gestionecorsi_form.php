<?php
// $Id: gestionecorsi_form.php 1150 2013-05-28 15:27:10Z d.lallo $

global $CFG;

require_once($CFG->libdir.'/formslib.php');
require_once '../../config.php';
require_once($CFG->dirroot.'/local/f2_support/lib.php');
require_once('lib.php');

class inserisci_daticorso_form extends moodleform {
    public function definition() {


        $mform =& $this->_form;
        $gestione_corsi = $this->_customdata['gestione_corsi'];    // this contains the data of this form
        $training = $this->_customdata['training'];
        $userid = $this->_customdata['userid'];
        $mod = $this->_customdata['mod'];
        if(isset($this->_customdata['id_course'])){
        	$id_course = $this->_customdata['id_course'];
        }else{
        	$id_course=0;
        }
        
        if(isset($this->_customdata['copy'])){
        	$copy = $this->_customdata['copy'];
        }else{
        	$copy=0;
        }
        $mform->addElement('hidden', 'tipo_pianificazione');
        $mform->setType('tipo_pianificazione', PARAM_ALPHANUM);
        $mform->setDefault('tipo_pianificazione', '');
        
        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);
        $mform->setDefault('userid', $userid);
        
        $mform->addElement('hidden', 'copy');
        $mform->setType('copy', PARAM_INT);
        $mform->setDefault('copy', $copy);
        
        $mform->addElement('hidden', 'id_course');
        $mform->setType('id_course', PARAM_INT);
        $mform->setDefault('id_course', $id_course);
        
        $mform->addElement('hidden', 'training');
        $mform->setType('training', PARAM_ALPHA);
        $mform->setDefault('training', $training);
        
        $mform->addElement('text', 'titolo', get_string('titolo', 'block_f2_formazione_individuale'), array('size' => '125','onKeyPress'=>'return disableEnterKey(event)'));
        $mform->addRule('titolo', null, 'required', 'client');
        $mform->addRule('titolo', get_string('error_value', 'local_f2_traduzioni'), 'regex', '/^.{0,120}$/', 'client');
        $mform->setDefault('titolo', '');
        
        $mform->addElement('date_selector', 'data_inizio', get_string('data_inizio', 'block_f2_formazione_individuale'));
        $mform->addRule('data_inizio', null, 'required', 'client');
        
        $mform->addElement('text', 'durata', get_string('durata', 'block_f2_formazione_individuale'), array('size' => '6','onKeyPress'=>'return disableEnterKey(event)'));
        $mform->addRule('durata', null, 'required', 'client');
        $mform->addRule('durata', get_string('error_value', 'local_f2_traduzioni'), 'regex','/^\d{1,3}(\.\d{1,2})?$/', 'client');
        $mform->setDefault('durata', '');
        
        $mform->addElement('text', 'costo', get_string('costo', 'block_f2_formazione_individuale'), array('size' => '6','onKeyPress'=>'return disableEnterKey(event)'));
        $mform->addRule('costo', null, 'required', 'client');
        $mform->addRule('costo', get_string('error_value', 'local_f2_traduzioni'), 'regex','/^\d{1,11}(\.\d{1,2})?$/', 'client');
        $mform->setDefault('costo', '0');

        // 2018 04 20
        $mform->addElement('checkbox', 'offerta_speciale', get_string('offerta_speciale','block_f2_formazione_individuale'));
        $colonne = array('cols' => '60');
        $mform->addElement('textarea', 'note_offerta',get_string('note_offerta','block_f2_formazione_individuale'),$colonne);
        // 2018 04 20

        //$anni_es_fin = array('2016'=>2016, '2017'=>2017, '2018'=>2018);
        $anni_es_fin = get_anno_corrente();
        $curr_year = get_selected_anno_corrente();
        $mform->addElement('select', 'finanziario', get_string('anno_esercizio_finanziario', 'block_f2_formazione_individuale'), $anni_es_fin);
        $mform->setType('finanziario', PARAM_INT);
        $mform->addRule('finanziario', null, 'required', null, 'client');
        if (isset($id_course) && $id_course > 0) {
          $anno_finanziario = get_anno_finanziario($id_course);
          $mform->setDefault('finanziario', $anno_finanziario);
        } else {
          $mform->setDefault('finanziario', $curr_year);
        }

        $mform->addElement('text', 'credito_formativo', get_string('credito_formativo', 'block_f2_formazione_individuale'), array('size' => '6','onKeyPress'=>'return disableEnterKey(event)'));
        $mform->addRule('credito_formativo', null, 'required', 'client');
        $mform->setDefault('credito_formativo', '');
        
        $mform->addElement('select', 'sf', get_string('segmento_formativo', 'block_f2_formazione_individuale'), 
                                        from_obj_to_array_select(get_segmento_formativo(), array('id','descrizione')), NULL);
        $mform->setType('sf', PARAM_ALPHANUM);
        $mform->addRule('sf', null, 'required', null, 'client');

        $opts = (isset($gestione_corsi->sf) ? 
                from_obj_to_array_select(get_AF_from_SF($gestione_corsi->sf), array('id','descrizione')) : 
                from_obj_to_array_select(get_aree_formative(), array('id','descrizione')));

        $mform->addElement('select', 'af', get_string('area_formativa', 'block_f2_formazione_individuale'), $opts, NULL);
        $mform->setType('af', PARAM_ALPHANUM);
        $mform->addRule('af', null, 'required', null, 'client');

        $opts = (isset($gestione_corsi->af) ? 
                from_obj_to_array_select(get_SUBAF_from_AF($gestione_corsi->af), array('id','descrizione')) : 
                from_obj_to_array_select(get_sub_aree_formative(), array('id','descrizione')));

        $mform->addElement('select', 'subaf', get_string('sottoarea_formativa', 'block_f2_formazione_individuale'), $opts, NULL);
        $mform->setType('subaf', PARAM_ALPHANUM);
        $mform->addRule('subaf', null, 'required', null, 'client');
        
        $mform->addElement('select', 'tipologia_organizzativa', get_string('tipologia_organizzativa', 'block_f2_formazione_individuale'), 
                                        from_obj_to_array_select(get_tipologia_org(), array('id','descrizione')), NULL);
        $mform->addRule('tipologia_organizzativa', null, 'required', null, 'client');
        
        $mform->addElement('select', 'tipo', get_string('tipo', 'block_f2_formazione_individuale'), 
                                        from_obj_to_array_select(get_tipi_corso(), array('id','descrizione')), NULL);
        $mform->addRule('tipo', null, 'required', null, 'client');

        $mform->addElement('text', 'ente', get_string('ente', 'block_f2_formazione_individuale'), array('size' => '125','onKeyPress'=>'return disableEnterKey(event)'));
        $mform->addRule('ente', null, 'required', 'client');
        $mform->addRule('ente', get_string('error_value', 'local_f2_traduzioni'), 'regex', '/^.{0,100}$/', 'client');
        $mform->setDefault('ente', '');
        
        $mform->addElement('text', 'via', get_string('via', 'block_f2_formazione_individuale'), array('size' => '125','onKeyPress'=>'return disableEnterKey(event)'));
        $mform->addRule('via', get_string('error_value', 'local_f2_traduzioni'), 'regex', '/^.{0,100}$/', 'client');
        $mform->setDefault('via', '');
        
        $mform->addElement('text', 'localita', get_string('localita', 'block_f2_formazione_individuale'), array('size' => '125','onKeyPress'=>'return disableEnterKey(event)'));
        $mform->addRule('localita', null, 'required', 'client');
        $mform->addRule('localita', get_string('error_value', 'local_f2_traduzioni'), 'regex', '/^.{0,50}$/', 'client');
        $mform->setDefault('localita', '');
        
        $mform->addElement('textarea', 'note', get_string('note', 'block_f2_formazione_individuale'), array('cols' => '83'));
        
        $mform->addElement('HTML','<div class="ui-widget">');
        $mform->addElement('text', 'beneficiario_pagamento', get_string('beneficiario_pagamento', 'block_f2_formazione_individuale'), array('size' => '125','onKeyPress'=>'return disableEnterKey(event)'));
        $mform->setDefault('beneficiario_pagamento', '');
        
        
        $mform->addElement('HTML','<input type="button" style=\'margin-left: 175px;\' id="id_button_scuola" value="Fornitori"  onClick="return M.f2_course.changeValueButton(this, \''.get_string('chiudi', 'local_f2_course').'\', \'Fornitori\')">');
        $mform->addElement('html', '<div id="div_tab_autosearch" style=\'display:none; margin-left: 175px;\'>');
        $mform->addElement('html', html_writer::table( tbl_fornitori_form_ind() ));
        $mform->addElement('html', '</div>');
        
        $mform->addElement('HTML','</div>');
        
        $mform->addElement('checkbox', 'cassa_economale', get_string('cassa_economale', 'block_f2_formazione_individuale'));
        
        $mform->addElement('text', 'codice_archiviazione', get_string('codice_archiviazione', 'block_f2_formazione_individuale'), array('size' => '50','onKeyPress'=>'return disableEnterKey(event)'));
   //     $mform->addRule('codice_archiviazione', null, 'required', null, 'client');
        $mform->addRule('codice_archiviazione', null, 'maxlength', 7, 'client');
        $mform->setDefault('codice_archiviazione', '');
        
        $data = new stdClass();
        $data->id_tipo_notif = 3;
        $data->column = 'id';
        $data->stato = 1;
        

        $modello_mail = get_templates($data,'',1)->dati;
        $keyvalue = array('id','title');
        
        $array_select=array("-1"=>"Non inviare e-mail");
        foreach($modello_mail as $row){
        	$array_select[$row->$keyvalue[0]] = $row->$keyvalue[1];
        }
        
        $mform->addElement('select', 'modello_email', get_string('modello_email', 'block_f2_formazione_individuale'),$array_select, NULL);
        $mform->addRule('modello_email', null, 'required', null, 'client');
        
        $mform->addElement('text', 'codice_fiscale', get_string('codice_fiscale', 'block_f2_formazione_individuale'), array('size' => '50','onKeyPress'=>'return disableEnterKey(event)'));
        $mform->setDefault('codice_fiscale', '');
        $mform->addRule('codice_fiscale', get_string('error_value', 'local_f2_traduzioni'), 'regex', '/^.{11,11}$|^.{16,16}$/', 'client');

        
        $mform->addElement('text', 'partita_iva', get_string('partita_iva', 'block_f2_formazione_individuale'), array('size' => '50','onKeyPress'=>'return disableEnterKey(event)'));
        $mform->setDefault('partita_iva', '');
        $mform->addRule('partita_iva', get_string('error_value', 'local_f2_traduzioni'), 'regex', '/^.{11,11}$|^.{16,16}$/', 'client');
        
        $mform->addElement('text', 'codice_creditore', get_string('codice_creditore', 'block_f2_formazione_individuale'), array('size' => '50','onKeyPress'=>'return disableEnterKey(event)'));
        $mform->setDefault('codice_creditore', '');
        
        if ($mod)
        	$alert = "mod";
        if($copy)
        	$alert = "copy";
        
        $buttons = array(
        	
            $mform->createElement('submit', 'save', get_string('salva', 'block_f2_formazione_individuale'),'onClick="validateInput()";')
        	,$mform->createElement('reset', 'reset', get_string('pulisci', 'block_f2_formazione_individuale'))
            ,$mform->createElement('button', 'indietro', get_string('indietro', 'block_f2_formazione_individuale'),'onclick="confirm_back(\''.$alert.'\')"')
        );

        $mform->addGroup($buttons, 'actions', '&nbsp;', array(' '), false);

}
			
}


