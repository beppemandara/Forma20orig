<?php

//$Id: session_form.php 1 2012-09-26 l.sampo $

/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010, 2011 Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @author Francois Marier <francois@catalyst.net.nz>
 * @package modules
 * @subpackage facetoface
 */
defined('MOODLE_INTERNAL') || die();

require_once("{$CFG->libdir}/formslib.php");
require_once("{$CFG->dirroot}/mod/facetoface/lib.php");
require_once($CFG->dirroot.'/f2_lib/core.php');
require_once 'lib.php';

class mod_facetoface_session_form extends moodleform {

    function definition() {
        global $USER,$DB;

        $mform =& $this->_form;

        $course     = $this->_customdata['course'];
        $courseid   = $course->id;
        $coursetype = $this->_customdata['coursetype'];
        $sessionid  = $this->_customdata['s'];
        
        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'f', $this->_customdata['f']);
        $mform->setType('f', PARAM_INT);
        $mform->addElement('hidden', 's', $this->_customdata['s']);
        $mform->setType('s', PARAM_INT);
        $mform->addElement('hidden', 'c', $this->_customdata['c']);
        $mform->setType('c', PARAM_INT);
        $mform->addElement('hidden', 'm', $this->_customdata['m']); // AK-LS: capability di modifica dell'edizione ridotta alle date
        $mform->setType('m', PARAM_INT);

        /*
         * AK-LS: $courseid contiene l'id del corso in esame e il tipo (obbiettivo / programmato)
         */
        $facetofacename = ($coursetype == C_PRO) ? $DB->get_field('facetoface', 'name', array('id' => $this->_customdata['f'])) : '';
        if ($coursetype === C_PRO) {
	    $f2sessionID = $DB->get_field_sql("SELECT f2_s.id FROM {f2_sessioni} f2_s, {facetoface} f
						WHERE f.id = ".$this->_customdata['f']." AND f.f2session = f2_s.id");
	    $sessionDateStart = date('d/m/Y H:i', $DB->get_field('f2_sessioni', 'data_inizio', array('id' => $f2sessionID)));
	    $sessionDateFinish = date('d/m/Y H:i', $DB->get_field('f2_sessioni', 'data_fine', array('id' => $f2sessionID)));
        }

        $mform->addElement('header', 'general', get_string('facetofacesession:dettagli_edizione', 'local_f2_traduzioni'));
        
        /*
         * AK-LS 
         * 
         * campo testuale FACETOFACENAME, contiene il nome della sessione (facetoface) in esame
         * E' disabilitato per i corsi programmati, mentre non è visibile e vuoto per i corsi obbiettivo
         * Non à memorizzato tra i dati di edizioni ma calcolato run-time
         */
        if ($coursetype === C_PRO) {
            $mform->addElement('text', 'facetofacename', get_string('facetofacesession:session', 'local_f2_traduzioni'), array('size' => '15', 'disabled' => 'disabled'));
            $mform->setType('facetofacename', PARAM_RAW);
            $mform->setDefault('facetofacename', $facetofacename);

            $mform->addElement('text', 'sessionDateStart', 'Data inizio Sessione', array('size' => '15', 'disabled' => 'disabled'));
            $mform->setDefault('sessionDateStart', $sessionDateStart);

            $mform->addElement('text', 'sessionDateFinish', 'Data fine Sessione', array('size' => '15', 'disabled' => 'disabled'));
            $mform->setDefault('sessionDateFinish', $sessionDateFinish);
            
            $mform->addElement('static', 'course_duration', get_string('facetofaceprg:expecteddurationl', 'local_f2_traduzioni'),
            get_string('facetofaceprg:expectedduration', 'local_f2_traduzioni', $this->_customdata['durata']));
        } else {
            $mform->addElement('hidden', 'facetofacename', get_string('facetofacesession:session', 'local_f2_traduzioni'));
            $mform->setType('facetofacename', PARAM_RAW);
            $mform->setDefault('facetofacename', $facetofacename);
        }
        // --- #
        
        $mform->addElement('html', html_writer::empty_tag('br'));
        
       /*
        * AK-LS: Bloccato selectbox si/no sulla/e data/e da inserire
        *
        $formarray  = array();
        $formarray[] = $mform->createElement('selectyesno', 'datetimeknown', get_string('facetoface:sessiondatetimeknown', 'local_f2_traduzioni'));
        $formarray[] = $mform->createElement('static', 'datetimeknownhint', '', html_writer::tag('span', get_string('datetimeknownhinttext','facetoface'), array('class' => 'hint-text')));
        $mform->addGroup($formarray,'datetimeknown_group', get_string('sessiondatetimeknown','facetoface'), array(' '),false);
        $mform->addGroupRule('datetimeknown_group', null, 'required', null, 'client');
        $mform->setDefault('datetimeknown', false);
        $mform->addHelpButton('datetimeknown_group', 'sessiondatetimeknown', 'facetoface');
        */
        
        if (is_referente_scuola_su_corso($this->_customdata['course']->id) && !assegnazioni_date_scuola_aperte()) {
            print_error(get_string('errore_assegnazione_date_chiuse', 'facetoface'));
        }
        
        $repeatarray = array();
        $repeatarray[] = &$mform->createElement('hidden', 'sessiondateid', 0);
        $repeatarray[] = &$mform->createElement('date_time_selector', 'timestart', get_string('facetofacesession:timestart', 'local_f2_traduzioni'), array(), 
        		array('onchange'=>'javascript:(function() {
        				if(document.getElementById(\'id_timestart_0_year\'))
        					document.getElementById(\'id_custom_anno\').value = document.getElementById(\'id_timestart_0_year\').value
        				})(this);'));
        $repeatarray[] = &$mform->createElement('date_time_selector', 'timefinish', get_string('facetofacesession:timefinish', 'local_f2_traduzioni'));
        $checkboxelement = &$mform->createElement('checkbox', 'datedelete', '', get_string('facetofacesession:dateremove', 'local_f2_traduzioni'));
        unset($checkboxelement->_attributes['id']); // necessary until MDL-20441 is fixed
        $repeatarray[] = $checkboxelement;
        //$repeatarray[] = &$mform->createElement('html', html_writer::empty_tag('br')); // spacer
        
        $repeatcount = $this->_customdata['nbdays'];
        
        $repeatoptions = array();
        /*
         * AK-LS: Bloccato selectbox si/no sulla/e data/e da inserire
        *
        $repeatoptions['timestart']['disabledif'] = array('datetimeknown', 'eq', 0);
        $repeatoptions['timefinish']['disabledif'] = array('datetimeknown', 'eq', 0);
        */
        $mform->setType('timestart', PARAM_INT);
        $mform->setType('timefinish', PARAM_INT);
        
        $this->repeat_elements($repeatarray, $repeatcount, $repeatoptions, 'date_repeats', 'date_add_fields',
        		1, get_string('facetofacesession:dateadd', 'local_f2_traduzioni'), true);
        
        $mform->addElement('html', html_writer::empty_tag('br'));
        
        // GM ripristino di alcune impostazioni originali - START
        $mform->addElement('text', 'capacity', get_string('capacity', 'facetoface'), 'size="5"');
        $mform->addRule('capacity', null, 'required', null, 'client');
        $mform->setType('capacity', PARAM_INT);
        $mform->setDefault('capacity', 10);
        $mform->addHelpButton('capacity', 'capacity', 'facetoface');

        $mform->addElement('checkbox', 'allowoverbook', get_string('allowoverbook', 'facetoface'));
        $mform->addHelpButton('allowoverbook', 'allowoverbook', 'facetoface');

        $mform->addElement('editor', 'details_editor', get_string('details', 'facetoface'), null, $editoroptions);
        $mform->setType('details_editor', PARAM_RAW);
        $mform->addHelpButton('details_editor', 'details', 'facetoface');

        $mform->addElement('html', html_writer::empty_tag('br'));
        // GM ripristino di alcune impostazioni originali - END

        /*
         * AK-LS:
         * Gestione dei paramentri da passare alla funzione di creazione dei customfields, in particolare
         * per oggetti di tipo SELECT e MULTISELECT per cui i possibili valori sono calcolati on-the-fly 
         * e non possono essere specificati di DEFAULT alla creazione del custom field
         * 
         */
        $params = array();

        $objSedi = $DB->get_records_sql("
                SELECT
                    f2_csm.sedeid,
                    f2_s.descrizione
                FROM
                    {f2_corsi_sedi_map} f2_csm,
                    {f2_sedi} f2_s
                WHERE
                    f2_csm.courseid = $courseid AND
                    f2_csm.sedeid = f2_s.id
                ORDER BY
                    f2_s.progr_displ ASC");
        $sedi = array();
        foreach ($objSedi as $objsede)
            $sedi[$objsede->sedeid] = $objsede->descrizione;

        $params['custom_sede'] = $sedi;
        // --- #

        // Show all custom fields
        $customfields = $this->_customdata['customfields'];
        facetoface_add_customfields_to_form($mform, $customfields, false, $params);

        // Hack to put help files on these custom fields.
        // TODO: add to the admin page a feature to put help text on custom fields
        /*
         * AK-LS - gestione dei customfields
         *
         * In successione:
         * - campo ANNO che indica l'anno di pianificazione AKA di svolgimento
         * - campo SIRP contenente il numero di protocollazione
         * - SIRPDATA, oggetto date_time_selector da convertire da UNIX timestamp in stringa formato DD/MM/YYYY
         * - SEDI, lista di sedi ereditate dall'anagrafica corso (default TO, pià in generale quella con progr_displ inferiore)
         * - INDIRIZZO di svolgimento del corso per l'edizione
         * - due campi nascosti: LSTUPD e USRNAME per tracciare l'ultima modifica della scheda dell'edizione
         */
        if ($mform->elementExists('custom_anno')) {
            //$defaultYear = $DB->get_field('f2_anagrafica_corsi', 'anno', array('courseid' => $courseid));
            //$defaultYear = ($defaultYear) ? $defaultYear : '';
            $mform->addHelpButton('custom_anno', 'facetofacesession:custom_anno', 'local_f2_traduzioni');
            //$mform->setDefault('custom_anno', $defaultYear);
        }
        if ($mform->elementExists('custom_sirp')) {
            $mform->addHelpButton('custom_sirp', 'facetofacesession:custom_sirp', 'local_f2_traduzioni');
        }
        if ($mform->elementExists('custom_sirpdata')) {
            $mform->addHelpButton('custom_sirpdata', 'facetofacesession:custom_sirpdata', 'local_f2_traduzioni');
        }
        if ($mform->elementExists('custom_sede')) {
            $mform->addHelpButton('custom_sede', 'facetofacesession:custom_sede', 'local_f2_traduzioni');
        }
        if ($mform->elementExists('custom_indirizzo')) {
            $defaultIndirizzo = $DB->get_field('f2_anagrafica_corsi', 'viaente', array('courseid' => $courseid));
            $defaultIndirizzo = ($defaultIndirizzo) ? $defaultIndirizzo : '';
            $mform->setDefault('custom_indirizzo', $defaultIndirizzo);
            $mform->addHelpButton('custom_indirizzo', 'facetofacesession:custom_indirizzo', 'local_f2_traduzioni');
        }
        if ($mform->elementExists('custom_lstupd')) {
            $mform->setDefault('custom_lstupd', time());
        }
        if ($mform->elementExists('custom_usrname')) {
            $mform->setDefault('custom_usrname', $USER->id);
        }
        // --- #
        
        // INSERIMENTO AVVISO
        //$testo = 'Prima di procedere con l\'inserimento dei Docenti, occorre salvare i dati inseriti nella pagina';
        //$mform->addElement('html', '<h3 style="color:red;">'.$testo.'</h3>');

        $mform->addElement('hidden', 'capacity', get_string('capacity', 'facetoface'), 'size="5"');
        //$mform->addRule('capacity', null, 'required', null, 'client');
        $mform->setType('capacity', PARAM_INT);
        $mform->setDefault('capacity', 9999);
        $mform->addHelpButton('capacity', 'capacity', 'facetoface');

// 2019 05 03
        $mform->addElement('hidden', 'details_editor', get_string('details', 'facetoface'));
        $mform->setType('details_editor', PARAM_RAW);
        $mform->setDefault('details_editor', 'Inserire i dettagli');
        $mform->addHelpButton('details_editor', 'details', 'facetoface');
// 2019 05 03
        
        // Parametri visibili solo per ruoli che hanno piena capacità di operabilità in modalità editsessions
        if (!$this->_customdata['m']) {
	  /*
	   * AK-LS: gestione dei docenti per ogni edizione
           */
          if ($sessionid && $sessionid > 0) {
            $mform->addElement('header', 'general', get_string('facetofacesession:docenti', 'local_f2_traduzioni'));
            $feedbackid = $this->_customdata['feedbackid'];
            $condizioni = array('feedback'=>$feedbackid, 'session'=>$sessionid);
            if ($feedbackid > 0 && $DB->record_exists('feedback_completed_session', $condizioni)) {
              $txt = 'Per questa edizione risulta gi&agrave; compilato un Questionario di Gradimento, non &egrave; possibile modificare i docenti.';
              $mform->addElement('html', '<p><strong>'.$txt.'</strong></p>');
            } else {
              $mform->addElement('html', '<p><strong><a href="'.$CFG->wwwroot.'/moodle/mod/facetoface/editteachers.php?s='.$sessionid.'">Aggiungi/Rimuovi Docenti</a></strong></p>');
            }
            $mform->addElement('header', 'general', 'Docenti Inseriti');
            $docenti = get_docenti_assegnati($sessionid);
            if ($docenti == 'nosession') {
              $mform->addElement('html', '<p>ID di sessione non pervenuto</p>');
            } else if ($docenti == 'zero') {
              $mform->addElement('html', '<p>Nessun Docente assegnato a questa sessione</p>');
            } else if ($docenti == 'errore') {
              $mform->addElement('html', '<p>Errore nel recupero dati dei Docenti</p>');
            } else {
              $mform->addElement('html', '<ul>');
              foreach ($docenti as $docente) {
                $mform->addElement('html', '<li>'.$docente->lastname.' '.$docente->firstname.'</li>');
              }
              $mform->addElement('html', '</ul>');
            }
          } else {
            // INSERIMENTO AVVISO
            $testo = 'Prima di procedere con l\'inserimento dei Docenti, occorre salvare i dati inseriti nella pagina';
            $mform->addElement('html', '<h3 style="color:red;">'.$testo.'</h3>');
          }
        }

$mform->addElement('html', html_writer::empty_tag('br'));
$mform->addElement('html', html_writer::empty_tag('br'));
$mform->addElement('html', html_writer::empty_tag('br'));
        
        $this->add_action_buttons();
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $dateids = $data['sessiondateid'];
        $dates = count($dateids);
        for ($i=0; $i < $dates; $i++) {
            $starttime = $data["timestart[$i]"];
            $endtime = $data["timefinish[$i]"];
            $removecheckbox = empty($data["datedelete"]) ? array() : $data["datedelete"];
            if ($starttime > $endtime && !isset($removecheckbox[$i])) {
                $errstr = get_string('error:sessionstartafterend','facetoface');
                $errors['timestart['.$i.']'] = $errstr;
                $errors['timefinish['.$i.']'] = $errstr;
                unset($errstr);
            }
        }

        if (!empty($data['datetimeknown'])) {
            $datefound = false;
            for ($i = 0; $i < $data['date_repeats']; $i++) {
                if (empty($data['datedelete'][$i])) {
                    $datefound = true;
                    break;
                }
            }

            if (!$datefound) {
                $errors['datetimeknown'] = get_string('validation:needatleastonedate', 'facetoface');
            }
        }

        return $errors;
    }
    
    protected function get_docents_options($sessionid=0) {
        global $DB;
        $results = new stdClass();
        $results->options  = array();
        $results->selected = array();
        
        $q = "SELECT 
            u.id, u.firstname, u.lastname 
            FROM {f2_formatore} f2_f, 
            {user} u 
            WHERE f2_f.usrid = u.id 
            ORDER BY u.lastname, u.firstname";
        $formatori = $DB->get_records_sql($q);
        foreach ($formatori as $formatore) {
            $results->options[$formatore->id] = format_string(fullname($formatore));
        }
        
        if($sessionid > 0) {
            $teachers_from_db = $DB->get_records('facetoface_sessions_docenti', array('sessionid'=>$sessionid));
            foreach ($teachers_from_db as $teacher) {
                array_push($results->selected, $teacher->userid);
            }
        }
        return $results;
    }
}
