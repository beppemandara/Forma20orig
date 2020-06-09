<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - agosto 2015
 * 
 * Maschere usate per l'attivazione dei report
 * 
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}
global $CFG;
require_once($CFG->libdir.'/formslib.php');
require_once('function_db.php');
class form_nessun_report_disponibile extends moodleform {
    function definition() {
        global $USER;
        $mform =& $this->_form;
        // estraggo e visualizzo i dati dell'utente
        $descrizione_voce_menu = $_REQUEST['descrizione_voce_menu'];
        $user_id = intval($USER->id);
        $userdata = get_user_data($user_id);
        $direzione = get_direzione_utente($user_id);
        $settore = get_settore_utente($user_id);   
        $role_id = null;
        $role_name = null;
        $ret_code = EML_Get_user_roleid($user_id, $role_id, $role_name);
        $messaggio = 'Per il ruolo:&nbsp;&nbsp;'.$role_name
                    .'&nbsp;&nbsp;non esistono report disponibili nella categoria: '.$descrizione_voce_menu;
        $table = new html_table();
        $table->align = array('right', 'left');
        $table->data = array(
		array('Cognome Nome ','<b>'.$userdata->lastname.' '.$userdata->firstname.'</b>'),
		array('Matricola',''.$userdata->idnumber.''),
		array('Categoria',''.$userdata->category.''),
		array('Direzione / Ente',''.is_null($direzione) ? '' : $direzione['shortname']." - ".$direzione['name'].''),
		array('Settore',''.is_null($settore) ? '' : $settore['name'].''),
		array('Ruolo',''.$role_name.''),
		array('NOTA',''.$messaggio.'')
        );
        echo html_writer::table($table);
        // NOTA L'aggiunta di un pulsante usando l'interfaccia delle form Moodle causa un errore di programmazione
        // sostituito con un echo di un button
        $url_pagina_di_ritorno = $_REQUEST['url_pagina_di_ritorno'];
        $pulsante_elenco_report = get_string('report_pulsante_prosegui', 'block_f2_report');
        echo '<input type="button" value="'.$pulsante_elenco_report.'" onclick="parent.location=\''.$url_pagina_di_ritorno.'\'">';
        //$submitlabel = get_string('report_pulsante_prosegui', 'block_f2_report');
        //$buttonarray=array();
        //$buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel);
        //$mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }
}// form_nessun_report_disponibile
require_once('function_db.php');
class form_selezione_report extends moodleform {
    function definition() {
        global $USER;
        $mform =& $this->_form;
        // estraggo e visualizzo i dati dell'utente
        $descrizione_voce_menu = $_REQUEST['descrizione_voce_menu'];
        $user_id = intval($USER->id);
        $userdata = get_user_data($user_id);
        $direzione = get_direzione_utente($user_id);
        $settore = get_settore_utente($user_id);   
        $role_id = null;
        $role_name = null;
        $ret_code = EML_Get_user_roleid($user_id, $role_id, $role_name);
        $codice_voce_menu = $_REQUEST['codice_voce_menu'];
        $descrizione_voce_menu = $_REQUEST['descrizione_voce_menu'];
        $url_pagina_di_ritorno = $_REQUEST['url_pagina_di_ritorno'];
        $mform->addElement('hidden', 'codice_voce_menu', $codice_voce_menu);
        $mform->addElement('hidden', 'descrizione_voce_menu', $descrizione_voce_menu);
        $mform->addElement('hidden', 'url_pagina_di_ritorno', $url_pagina_di_ritorno);
        // estraggo l'elenco dei report associati alla voce di menù (attivi e accessibili al ruolo utente)
        $numero_report = 0;
        $elenco_report_select = EML_Get_elenco_report_selezionabili($codice_voce_menu, $role_id, $numero_report);
        /*if ($numero_report == 0) {
            $messaggio = 'Per il ruolo:&nbsp;&nbsp;'.$role_name
                    .'&nbsp;&nbsp;non esistono report disponibili nella categoria: '.$descrizione_voce_menu;
        } else {
            $messaggio = ' ';
        }*/
        $table = new html_table();
        $table->align = array('right', 'left');
        $table->data = array(
		array('Cognome Nome ','<b>'.$userdata->lastname.' '.$userdata->firstname.'</b>'),
		array('Matricola',''.$userdata->idnumber.''),
		array('Categoria',''.$userdata->category.''),
		array('Direzione / Ente',''.is_null($direzione) ? '' : $direzione['shortname']." - ".$direzione['name'].''),
		array('Settore',''.is_null($settore) ? '' : $settore['name'].''),
		array('Ruolo',''.$role_name.'')
        );
        echo html_writer::table($table);
        // la function prevede che ci sia almeno un report in elenco
        // (il caso nessun report è gestito da apposita form)
        // Messaggio di richiesta selezione report
        // Lista di scelta con i report disponibili
        // Pulsanti per confermare
        $mform->addElement('select', 'id_report', 
                get_string('report_id_report', 'block_f2_report'), 
                $elenco_report_select);
        $mform->addRule('id_report', null, 'required');
        $mform->addHelpButton('id_report', 'report_id_report', 'block_f2_report');
        // pulsante di conferma operazione
        // Nota: NON INSERIRE il pulsante di annulla operazione
        $submitlabel = get_string('report_pulsante_prosegui', 'block_f2_report');
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel);
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        //$mform->closeHeaderBefore('buttonar');
    }
}// form_selezione_report