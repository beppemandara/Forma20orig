<?php
global $CFG;

require_once($CFG->libdir.'/formslib.php');

class archivia_corso_senza_determina_form extends moodleform {
  public function definition() {
    global $CFG;
    $mform =& $this->_form;

    $training = $this->_customdata['training'];
    if(isset($this->_customdata['id_course'])) {
      $id_course = $this->_customdata['id_course'];
    } else {
      $id_course = 0;
    }

    add_to_log_archiviazione_corsiind_senza_determina($id_course, 'START archivia_corso_senza_determina_form');

    //$mform->addElement('hidden', 'id_course', $id_course);
    $mform->addElement('hidden', 'id_course');
    $mform->setType('id_course', PARAM_INT);
    $mform->setDefault('id_course', $id_course);

    $mform->addElement('hidden', 'training');
    $mform->setType('training', PARAM_ALPHA);
    $mform->setDefault('training', $training);

    $mform->addElement('text', 'presenza',get_string('presenza','block_f2_formazione_individuale').":", 'maxlength="254" size="20"');
    $mform->setDefault('presenza',0);
    $mform->addRule('presenza', null, 'required',null, 'client');
    $mform->addRule('presenza', get_string('error_value', 'local_f2_traduzioni'), 'regex','/^\d{1,3}(\.\d{1,2})?$/', 'client');

    $mform->addElement('text', 'credito_formativo_valido',get_string('credito_formativo_valido','block_f2_formazione_individuale').":", 'maxlength="254" size="20"');
    $mform->setDefault('credito_formativo_valido',0);
    $mform->addRule('credito_formativo_valido', null, 'required',null, 'client');
    $mform->addRule('credito_formativo_valido', get_string('error_value', 'local_f2_traduzioni'), 'regex','/^\d{1,3}(\.\d{1,2})?$/', 'client');

    $partecipazioni = get_partecipazioni_values();

    $array_partecipazione =  array();
    foreach($partecipazioni as $partecipazione) {
      $array_partecipazione[$partecipazione->id] = $partecipazione->codpart.' - '.$partecipazione->descrpart;
    }

    $select = $mform->addElement('select', 'partecipazione', get_string('partecipazione','block_f2_formazione_individuale').":", $array_partecipazione);
    $select->setMultiple(false);
    $mform->addRule('partecipazione', null, 'required',null, 'client');
    $mform->setDefault('partecipazione',10);

    $dati_verifica_apprendimento = get_va_values();
    $array_dati_verifica_apprendimento =  array();

    foreach($dati_verifica_apprendimento as $verifica_apprendimento) {
      $array_dati_verifica_apprendimento[$verifica_apprendimento->id] = $verifica_apprendimento->descrizione;
    }

    $select = $mform->addElement('select', 'verifica_apprendimento', get_string('verifica_apprendimento','block_f2_formazione_individuale').":", $array_dati_verifica_apprendimento);
    $select->setMultiple(false);
    $mform->addRule('verifica_apprendimento', null, 'required',null, 'client');
    $mform->setDefault('verifica_apprendimento',10);
    $url_ind = new moodle_url($CFG->wwwroot."/blocks/f2_formazione_individuale/gest_corsi_ind_senza_determina.php?training=".$training."");
$mform->addElement('html', '<table align="center"><tr><td>');
    $mform->addElement('html', '<input type="submit" value="'.get_string("salva", "block_f2_formazione_individuale").'">');
    $mform->addElement('html', '</td><td>');
    $mform->addElement('html', '<input type="reset" value="'.get_string("pulisci", "block_f2_formazione_individuale").'">');
    $mform->addElement('html', '</td><td>');
    $mform->addElement('html', '<input type="button" value="'.get_string("indietro", "block_f2_formazione_individuale").'" onclick="if(confirm(\'Tornando indietro non verr&agrave; archiviato nessun corso.\nProseguire?\')) parent.location=\''.$url_ind.'\'">');
    $mform->addElement('html', '</td></tr></table>');
  }
}
