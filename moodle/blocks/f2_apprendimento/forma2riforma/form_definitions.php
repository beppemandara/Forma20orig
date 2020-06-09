<?php
/* A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * CREG - Upload da Riforma
 * 
 * Definizione delle maschere
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}
require_once($CFG->libdir.'/formslib.php');
require_once "costanti.php";
require_once "costanti_db.php";
require_once "strutture_dati.php";
require_once "connessioni_al_db.php";
require_once "function_db.php";
class form_archivia_corso extends moodleform {
    function definition() {
        global $CFG;
        global $mysqli_Riforma;
        $mform =& $this->_form;
        $id = $_REQUEST['id'];
        $rec_mdl_f2_forma2riforma_mapping = new EML_RECmdl_f2_forma2riforma_mapping();
        $rec_mdl_course = new EML_RECmdl_course();
        $ret_code = Get_mdl_f2_forma2riforma_mapping($id, $rec_mdl_f2_forma2riforma_mapping);
        $id_forma20 = $rec_mdl_f2_forma2riforma_mapping->id_forma20;
        $ret_code = Get_mdl_course_Forma20($id_forma20, $rec_mdl_course);
        $table = new html_table();
        $table->width = "100%";
        $table->head = array ();
        $table->align[] = 'left';
        $table->size[] = '20%';
        $table->align[] = 'left';
        $table->size[] = '80%';
        $row = array ();
        $row[] = get_string('f2r_etichetta_corso','block_f2_apprendimento');
        $row[] = $rec_mdl_f2_forma2riforma_mapping->shortname." - ".$rec_mdl_course->fullname;
        $table->data[] = $row;
        $row = array ();
        $row[] = get_string('f2r_etichetta_perc_x_cfv','block_f2_apprendimento');
        $row[] = $rec_mdl_f2_forma2riforma_mapping->perc_x_cfv;
        $table->data[] = $row;
        $row = array ();
        $row[] = get_string('f2r_etichetta_va_default','block_f2_apprendimento');
        $row[] = $rec_mdl_f2_forma2riforma_mapping->va_default;
        $table->data[] = $row;
        $row = array ();
        $row[] = get_string('f2r_etichetta_nota','block_f2_apprendimento');
        $row[] = $rec_mdl_f2_forma2riforma_mapping->nota;
        $table->data[] = $row;
        echo html_writer::table($table);
        $shortname = $rec_mdl_f2_forma2riforma_mapping->shortname;
        $mform->addElement('hidden', 'id', $id);
        $mform->addElement('hidden', 'shortname', $shortname);
        // pulsanti di conferma ed annulla operazione
        $submitlabel = get_string('f2r_pulsante_conferma_archiviazione', 'block_f2_apprendimento');
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel);
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
} // form_archivia_corso
class form_cancella_corso extends moodleform {
    function definition() {
        global $CFG;
        global $mysqli_Riforma;
        $mform =& $this->_form;
        $id = $_REQUEST['id'];
        $rec_mdl_f2_forma2riforma_mapping = new EML_RECmdl_f2_forma2riforma_mapping();
        $rec_mdl_course = new EML_RECmdl_course();
        $ret_code = Get_mdl_f2_forma2riforma_mapping($id, $rec_mdl_f2_forma2riforma_mapping);
        $id_forma20 = $rec_mdl_f2_forma2riforma_mapping->id_forma20;
        $ret_code = Get_mdl_course_Forma20($id_forma20, $rec_mdl_course);
        $table = new html_table();
        $table->width = "100%";
        $table->head = array ();
        $table->align[] = 'left';
        $table->size[] = '20%';
        $table->align[] = 'left';
        $table->size[] = '80%';
        $row = array ();
        $row[] = get_string('f2r_etichetta_corso','block_f2_apprendimento');
        $row[] = $rec_mdl_f2_forma2riforma_mapping->shortname." - ".$rec_mdl_course->fullname;
        $table->data[] = $row;
        $row = array ();
        $row[] = get_string('f2r_etichetta_perc_x_cfv','block_f2_apprendimento');
        $row[] = $rec_mdl_f2_forma2riforma_mapping->perc_x_cfv;
        $table->data[] = $row;
        $row = array ();
        $row[] = get_string('f2r_etichetta_va_default','block_f2_apprendimento');
        $row[] = $rec_mdl_f2_forma2riforma_mapping->va_default;
        $table->data[] = $row;
        $row = array ();
        $row[] = get_string('f2r_etichetta_nota','block_f2_apprendimento');
        $row[] = $rec_mdl_f2_forma2riforma_mapping->nota;
        $table->data[] = $row;
        echo html_writer::table($table);
        $shortname = $rec_mdl_f2_forma2riforma_mapping->shortname;
        $mform->addElement('hidden', 'id', $id);
        $mform->addElement('hidden', 'shortname', $shortname);
        // pulsanti di conferma ed annulla operazione
        $submitlabel = get_string('f2r_pulsante_conferma_cancellazione', 'block_f2_apprendimento');
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel);
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
} // form_cancella_corso
class form_nuovo_collegamento extends moodleform {
    function definition() {
        global $CFG;
        global $mysqli_Riforma;
        EML_Connetti_db_Riforma();
        //echo "<pre>mysqli form_nuovo_collegamento: ".var_dump($mysqli_Riforma)."</pre>";
        $elenco_corsi_full = new EML_Corsi_mappabili();
        $mform =& $this->_form;
        $elenco_corsi_select = Get_elenco_corsi_collegabili_1($numero_corsi_mappabili);
        $vet_mdl_f2_va = Get_mdl_f2_va();
        if ($numero_corsi_mappabili == 0) {
            require "testo_condizioni_x_collegamento.php";
            echo '<form id="pulsanti_nuovo_collegamento" action="elenco_corsi.php" method="post">';
            echo '<table><tr><td>';
            $aus = get_string('f2r_pulsante_elencocorsi', 'block_f2_apprendimento');
            echo '<input type="submit" name="elenco_corsi" value="'.$aus.'"/>';
            echo '</td></tr></table>';
            echo '</form>';
        } else {
            $mform->addElement('select', 'id_forma20', get_string('f2r_etichetta_id_forma20', 'block_f2_apprendimento'), $elenco_corsi_select);
            $mform->addRule('id_forma20', null, 'required');
            $mform->addElement('text', 'perc_x_cfv', get_string('f2r_etichetta_perc_x_cfv', 'block_f2_apprendimento'));
            $mform->addRule('perc_x_cfv', null, 'required');
            $mform->addRule('perc_x_cfv', get_string('f2r_range_perc_x_cfv', 'block_f2_apprendimento'), 'numeric');
            $mform->addHelpButton('perc_x_cfv', 'f2r_help_perc_x_cfv', 'block_f2_apprendimento');
            $mform->addElement('select', 'va_default', get_string('f2r_etichetta_va_default', 'block_f2_apprendimento'), $vet_mdl_f2_va);
            $mform->addRule('va_default', null, 'required');
            // pulsanti della form
            $pulsanti = array();
            $pulsanti[] =& $mform->createElement('submit', 'submitbutton', get_string('f2r_pulsante_prosegui', 'block_f2_apprendimento'));
            $pulsanti[] =& $mform->createElement('cancel');
            $mform->addGroup($pulsanti, 'pulsanti', ' ', ' ', false);
        }
    }
    function validation($data, $files){
        $errors = array();
        if (($data['perc_x_cfv'] < 1) || ($data['perc_x_cfv'] > 100) ){
            $errors['perc_x_cfv'] = get_string('f2r_range_perc_x_cfv', 'block_f2_apprendimento');
        }
	return $errors;
    }
} // form_nuovo_collegamento
class form_modifica_corso extends moodleform {
    function definition() {
        global $CFG;
        $mform =& $this->_form;
        $vet_mdl_f2_va = Get_mdl_f2_va();
        $mform->addElement('hidden', 'id');
        $mform->setDefault('id', $this->_customdata['id']);
        $mform->addElement('hidden', 'shortname');
        $mform->setDefault('shortname', $this->_customdata['shortname']);
        $mform->addElement('text', 'id_forma20', get_string('f2r_etichetta_corso', 'block_f2_apprendimento'), 'maxlength="100" size="100" ');
        $mform->disabledIf('id_forma20', 1);
        $mform->setDefault('id_forma20', $this->_customdata['id_forma20']);
        $mform->addElement('text', 'perc_x_cfv', get_string('f2r_etichetta_perc_x_cfv', 'block_f2_apprendimento'));
        $mform->addRule('perc_x_cfv', null, 'required');
        $mform->addRule('perc_x_cfv', get_string('f2r_range_perc_x_cfv', 'block_f2_apprendimento'), 'numeric');
        $mform->addHelpButton('perc_x_cfv', 'f2r_help_perc_x_cfv', 'block_f2_apprendimento');
        $mform->setDefault('perc_x_cfv', $this->_customdata['perc_x_cfv']);
        $mform->addElement('select', 'va_default', get_string('f2r_etichetta_va_default', 'block_f2_apprendimento'), $vet_mdl_f2_va);
        $mform->addRule('va_default', null, 'required');
        $mform->setDefault('va_default', $this->_customdata['va_default']);
        $mform->addElement('text', 'nota', get_string('f2r_etichetta_nota', 'block_f2_apprendimento'), 'maxlength="100" size="100" ');
        $mform->disabledIf('nota', 1);
        $mform->setDefault('nota', $this->_customdata['nota']);       
        // pulsanti della form
        $pulsanti = array();
        $pulsanti[] =& $mform->createElement('submit', 'submitbutton', get_string('f2r_pulsante_prosegui', 'block_f2_apprendimento'));
        $pulsanti[] =& $mform->createElement('cancel');
        $mform->addGroup($pulsanti, 'pulsanti', ' ', ' ', false);
    }
    function validation($data, $files){
        $errors = array();
        if (($data['perc_x_cfv'] < 1) || ($data['perc_x_cfv'] > 100) ){
            $errors['perc_x_cfv'] = get_string('f2r_range_perc_x_cfv', 'block_f2_apprendimento');
        }
	return $errors;
    }
} // class form_modifica_corso
class form_leggi_partecipazioni extends moodleform {
    function definition() {
        global $CFG;
        global $mysqli_Riforma;
        EML_Connetti_db_Riforma();
        $mform =& $this->_form;
        $mform->addElement('hidden', 'id');
        $mform->setDefault('id', $this->_customdata['id']);
        $mform->addElement('hidden', 'shortname');
        $mform->setDefault('shortname', $this->_customdata['shortname']);
        $mform->addElement('text', 'id_forma20', get_string('f2r_etichetta_corso', 'block_f2_apprendimento'), 'maxlength="100" size="100" ');
        $mform->disabledIf('id_forma20', 1);
        $mform->setDefault('id_forma20', $this->_customdata['id_forma20']);
        $mform->addElement('text', 'perc_x_cfv', get_string('f2r_etichetta_perc_x_cfv', 'block_f2_apprendimento'));
        $mform->disabledIf('perc_x_cfv', 1);
        $mform->setDefault('perc_x_cfv', $this->_customdata['perc_x_cfv']);       
        $mform->addElement('text', 'va_default', get_string('f2r_etichetta_va_default', 'block_f2_apprendimento'));
        $mform->disabledIf('va_default', 1);
        $mform->setDefault('va_default', $this->_customdata['va_default']);
        // leggo l'elenco dei moduli scorm del corso (per permettere la selezione del modulo col test finale)
        $mform->addElement('hidden', 'id_riforma');
        $mform->setDefault('id_riforma', $this->_customdata['id_riforma']);
        
        /*echo"<pre>customdata: ".var_dump($this->_customdata)."</pre>";
        echo"<pre>id_riforma: ".$this->_customdata['id_riforma']."</pre>";*/
        
        $id_riforma = $this->_customdata['id_riforma'];
        $numero_scorm = 0;
        $elenco_scorm_select = Get_elenco_scorm_Riforma_1($id_riforma, $numero_scorm);
        $mform->addElement('select', 'id_scorm', get_string('f2r_etichetta_id_scorm', 'block_f2_apprendimento'), $elenco_scorm_select);
        $mform->addRule('id_scorm', null, 'required');
        // pulsanti della form
        $pulsanti = array();
        $pulsanti[] =& $mform->createElement('submit', 'submitbutton', get_string('f2r_pulsante_prosegui', 'block_f2_apprendimento'));
        $pulsanti[] =& $mform->createElement('cancel');
        $mform->addGroup($pulsanti, 'pulsanti', ' ', ' ', false);
    }
} // class form_leggi_partecipazioni
?>