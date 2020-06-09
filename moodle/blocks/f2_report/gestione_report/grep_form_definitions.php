<?php
/* A. Albertin, G. Mandarà - CSI Piemonte - luglio 2015
 * 
 * GREP - Gestione Report
 * 
 * Definizione delle maschere
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}
global $CFG;
//require_once($CFG->libdir.'/formslib.php');
require_once "grep_costanti.php";
require_once "grep_strutture_dati.php";
require_once "grep_function_db.php";
class form_cancella_report extends moodleform {
    function definition() {
        $mform =& $this->_form;
        $id_report = $_REQUEST['id_report'];
        $mform->addElement('hidden', 'id_report', $id_report);
        // leggo i dati del report
        $rec_mdl_f2_csi_pent_report = new EML_RECmdl_f2_csi_pent_report();
        $ret_code = EML_Get_mdl_f2_csi_pent_report($id_report, $rec_mdl_f2_csi_pent_report);
        // campi nascosti della maschera
        $mform->addElement('hidden', 'id_report', $id_report);
        $mform->addElement('hidden', 'id_menu_report', $rec_mdl_f2_csi_pent_report->id_menu_report);
        $mform->addElement('hidden', 'nome_report', $rec_mdl_f2_csi_pent_report->nome_report);
        $mform->addElement('hidden', 'nome_file_pentaho', $rec_mdl_f2_csi_pent_report->nome_file_pentaho);
        $mform->addElement('hidden', 'attivo', $rec_mdl_f2_csi_pent_report->attivo);
        $mform->addElement('hidden', 'formato_default', $rec_mdl_f2_csi_pent_report->formato_default);
        $mform->addElement('hidden', 'posizione_in_elenco_report', $rec_mdl_f2_csi_pent_report->posizione_in_elenco_report);
        $mform->addElement('hidden', 'numero_esecuzioni', $rec_mdl_f2_csi_pent_report->numero_esecuzioni);
        $mform->addElement('hidden', 'data_ultima_esecuzione', $rec_mdl_f2_csi_pent_report->data_ultima_esecuzione);
        // visualizzo i dati del report
        $table = new html_table();
        $row = array ();
        $row[] = get_string('grep_etichetta_nome_report', 'block_f2_report');
        $row[] = $rec_mdl_f2_csi_pent_report->nome_report;
        $table->data[] = $row;
        $row = array ();
        $row[] = get_string('grep_etichetta_nome_file_pentaho', 'block_f2_report');
        $row[] = $rec_mdl_f2_csi_pent_report->nome_file_pentaho;
        $table->data[] = $row;
        $row = array ();
        $row[] = get_string('grep_etichetta_formato_default', 'block_f2_report');
        $row[] = $rec_mdl_f2_csi_pent_report->formato_default;
        $table->data[] = $row;
        $row = array ();
        $row[] = get_string('grep_etichetta_posizione_in_elenco_report', 'block_f2_report');
        $row[] = $rec_mdl_f2_csi_pent_report->posizione_in_elenco_report;
        $table->data[] = $row;
        $row = array ();
        $row[] = get_string('grep_etichetta_flag_attivo', 'block_f2_report');
        if ($rec_mdl_f2_csi_pent_report->attivo == 0) {
            $row[] = EML_GREP_NO;
        } else {
            $row[] = EML_GREP_SI;
        }
        $table->data[] = $row;
        $row = array ();
        $row[] = get_string('grep_etichetta_numero_esecuzioni', 'block_f2_report');
        $row[] = $rec_mdl_f2_csi_pent_report->numero_esecuzioni;
        $table->data[] = $row;
        $row = array ();
        if (!is_null($rec_mdl_f2_csi_pent_report->data_ultima_esecuzione)) {
            $row[] = get_string('grep_etichetta_data_ultima_esecuzione', 'block_f2_report');
            $time = strtotime($rec_mdl_f2_csi_pent_report->data_ultima_esecuzione);
            $data_stringa = date('d-m-Y',$time);
            $row[] = $data_stringa;
            $table->data[] = $row;
        }
        echo html_writer::table($table);
        // pulsanti della form
        $pulsanti = array();
        $pulsanti[] =& $mform->createElement('submit', 'submitbutton', get_string('grep_pulsante_prosegui', 'block_f2_report'));
        $pulsanti[] =& $mform->createElement('cancel');
        $mform->addGroup($pulsanti, 'pulsanti', ' ', ' ', false);
    }
} // form_cancella_report
class form_feed_back_page extends moodleform {
    function definition() {
        $rec_tbl_eml_grep_feed_back = new EML_RECtbl_eml_grep_feed_back();
        $mform =& $this->_form;
        $id = $_REQUEST['id'];
        $ret_code = EML_Get_tbl_eml_grep_feed_back($id, $rec_tbl_eml_grep_feed_back);
        $mform->addElement('hidden', 'id', $rec_tbl_eml_grep_feed_back->id);
        $mform->addElement('hidden', 'url', $rec_tbl_eml_grep_feed_back->url);
        // Visualizzo (in una tabella) i dati letti)
        $table = new html_table();
        $table->width = "100%";
        $table->head = array ();
        $table->align = array();
        $table->size[] = '10%';
        $table->align[] = 'left';
        $table->size[] = '90%';
        $table->align[] = 'left';
        $row = array ();
        $row[] = 'Operazione';
        $row[] = $rec_tbl_eml_grep_feed_back->operazione;
        $table->data[] = $row;
        $row = array ();
        $row[] = 'Stato';
        $row[] = $rec_tbl_eml_grep_feed_back->stato;
        $table->data[] = $row;
        $row = array ();
        $row[] = 'Note';
        $row[] = $rec_tbl_eml_grep_feed_back->nota_1;
        $table->data[] = $row;
        $row = array ();
        $row[] = ' ';
        $row[] = $rec_tbl_eml_grep_feed_back->nota_2;
        $table->data[] = $row;
        $row = array ();
        $row[] = ' ';
        $row[] = $rec_tbl_eml_grep_feed_back->nota_3;
        $table->data[] = $row;
        $row = array ();
        $row[] = ' ';
        $row[] = $rec_tbl_eml_grep_feed_back->nota_4;
        $table->data[] = $row;
        echo html_writer::table($table);
        // pulsante prosegui         
        $pulsanti = array(
            $mform->createElement('submit', 'submitbutton', get_string('grep_pulsante_prosegui', 'block_f2_report'))
        );
        $mform->addGroup($pulsanti, 'pulsanti', ' ', ' ', false);
    } //function definition
} // form_feed_back_page
class form_modifica_parametri_report extends moodleform {
    function definition() {
        $mform =& $this->_form;
        $id_report = $_REQUEST['id_report'];
        $mform->addElement('hidden', 'id_report', $id_report);
        // leggo i dati del report
        $rec_mdl_f2_csi_pent_report = new EML_RECmdl_f2_csi_pent_report();
        $ret_code = EML_Get_mdl_f2_csi_pent_report($id_report, $rec_mdl_f2_csi_pent_report);
        $mform =& $this->_form;
        // leggo i parametri del report
        $numero_parametri = 0;
        $elenco_parametri = EML_Get_elenco_parametri_report($id_report, $numero_parametri);
        // leggo i ruoli del report
        $numero_ruoli = 0;
        $elenco_ruoli = EML_Get_elenco_ruoli_report($id_report, $numero_ruoli);
        // campi nascosti della maschera
        $mform->addElement('hidden', 'id_report', $id_report);
        $mform->addElement('hidden', 'id_menu_report', $rec_mdl_f2_csi_pent_report->id_menu_report);
        $mform->addElement('hidden', 'nome_report', $rec_mdl_f2_csi_pent_report->nome_report);
        $mform->addElement('hidden', 'nome_file_pentaho', $rec_mdl_f2_csi_pent_report->nome_file_pentaho);
        $mform->addElement('hidden', 'attivo', $rec_mdl_f2_csi_pent_report->attivo);
        $mform->addElement('hidden', 'formato_default', $rec_mdl_f2_csi_pent_report->formato_default);
        $mform->addElement('hidden', 'posizione_in_elenco_report', $rec_mdl_f2_csi_pent_report->posizione_in_elenco_report);
        $mform->addElement('hidden', 'numero_esecuzioni', $rec_mdl_f2_csi_pent_report->numero_esecuzioni);
        $mform->addElement('hidden', 'data_ultima_esecuzione', $rec_mdl_f2_csi_pent_report->data_ultima_esecuzione);
        // visualizzo i dati del report
        $table = new html_table();
        $row = array ();
        $row[] = get_string('grep_etichetta_nome_report', 'block_f2_report');
        $row[] = $rec_mdl_f2_csi_pent_report->nome_report;
        $table->data[] = $row;
        $row = array ();
        $row[] = get_string('grep_etichetta_nome_file_pentaho', 'block_f2_report');
        $row[] = $rec_mdl_f2_csi_pent_report->nome_file_pentaho;
        $table->data[] = $row;
        $row = array ();
        $row[] = get_string('grep_etichetta_formato_default', 'block_f2_report');
        $row[] = $rec_mdl_f2_csi_pent_report->formato_default;
        $table->data[] = $row;
        $row = array ();
        $row[] = get_string('grep_etichetta_posizione_in_elenco_report', 'block_f2_report');
        $row[] = $rec_mdl_f2_csi_pent_report->posizione_in_elenco_report;
        $table->data[] = $row;
        $row = array ();
        $row[] = get_string('grep_etichetta_flag_attivo', 'block_f2_report');
        if ($rec_mdl_f2_csi_pent_report->attivo == 0) {
            $row[] = EML_GREP_NO;
        } else {
            $row[] = EML_GREP_SI;
        }
        $table->data[] = $row;
        $row = array ();
        $row[] = get_string('grep_etichetta_numero_esecuzioni', 'block_f2_report');
        $row[] = $rec_mdl_f2_csi_pent_report->numero_esecuzioni;
        $table->data[] = $row;
        $row = array ();
        if (!is_null($rec_mdl_f2_csi_pent_report->data_ultima_esecuzione)) {
            $row[] = get_string('grep_etichetta_data_ultima_esecuzione', 'block_f2_report');
            $time = strtotime($rec_mdl_f2_csi_pent_report->data_ultima_esecuzione);
            $data_stringa = date('d-m-Y',$time);
            $row[] = $data_stringa;
            $table->data[] = $row;
        }
        $row = array ();
        $row[] = ' ';
        $row[] = ' ';
        $table->data[] = $row;
        echo html_writer::table($table);
        // Creo la tabella per selezione dei parametri del report
        // Definizione tabella
        $width_tabella = 'width="20%"';
        $tabella_definizione = '<table '.$width_tabella.'>';
        // definizione header
        $width_colonna_1 = 'width="10%"';
        $width_colonna_2 = 'width="10%"';
        $header_colonna_1 = get_string('grep_etichetta_parametro', 'block_f2_report');
        $header_colonna_2 = get_string('grep_etichetta_presente_S_N', 'block_f2_report');
        $align_colonna_1 = 'text-align:left';
        $align_colonna_2 = 'text-align:center';
        $tabella_header = '<thead>';
        $tabella_header .= '<tr>';
        $tabella_header .= '<th '.$width_colonna_1.' style="'.$align_colonna_1.'">'.$header_colonna_1.'</th>';
        $tabella_header .= '<th '.$width_colonna_2.' style="'.$align_colonna_2.'">'.$header_colonna_2.'</th>';
        $tabella_header .= '</tr>';
        $tabella_header .= '</thead>';
        $tabella_inizio_body = '<tbody>';
        // assemblo il tutto e visualizzo fino a <tbody>
        $aus = $tabella_definizione.$tabella_header.$tabella_inizio_body;
        $mform->addElement('html', $aus);
        // loop si parametri del report (una per riga della tabella)
        for ($i = 1; $i <= $numero_parametri; $i++) {
            // colonna 1
            $colonna_1 = '<td align = "left">';
            $colonna_1 .= $elenco_parametri[$i]->nome_parametro;
            $colonna_1 .= '</td>';
            // colonna 2
            $nome_parametro = 'name="parametro_'.$i.'"';
            if($elenco_parametri[$i]->flag_S_N == EML_GREP_SI) {
                $selezionato = ' checked>';
            } else {
                $selezionato = '>';
            }
            $colonna_2 = '<td align = "center">';        
            $colonna_2 .= '<input type="checkbox" '.$nome_parametro.' value="1"'.$selezionato;
            $colonna_2 .= '</td>';
            $riga_tabella = '<tr>'.$colonna_1.$colonna_2.'</tr>';
            $mform->addElement('html', $riga_tabella);
            // campo id_parametro_nn (Hidden)
            $nome_id_parametro = 'id_parametro_'.$i;
            $valore_id_parametro = $elenco_parametri[$i]->id_parametro;
            $mform->addElement('hidden', $nome_id_parametro, $valore_id_parametro);
        }
        // chiusura tabella selezione parametri
        $tabella_fine_body = '</tbody>';
        $tabella_fine_tabella = '</table>';
        // assemblo il tutto e visualizzo fino a </table>
        $aus = $tabella_fine_body.$tabella_fine_tabella;
        $mform->addElement('html', $aus);
        // tabella con la selezione ruoli
        // definizione tabella
        $width_tabella = 'width="40%"';
        $tabella_definizione = '<table '.$width_tabella.'>';
        // definizione header
        $width_colonna_1 = 'width="30%"';
        $width_colonna_2 = 'width="10%"';
        $header_colonna_1 = get_string('grep_etichetta_ruolo', 'block_f2_report');
        $header_colonna_2 = get_string('grep_etichetta_abilitato_S_N', 'block_f2_report');
        $align_colonna_1 = 'text-align:left';
        $align_colonna_2 = 'text-align:center';
        $tabella_header = '<thead>';
        $tabella_header .= '<tr>';
        $tabella_header .= '<th '.$width_colonna_1.' style="'.$align_colonna_1.'">'.$header_colonna_1.'</th>';
        $tabella_header .= '<th '.$width_colonna_2.' style="'.$align_colonna_2.'">'.$header_colonna_2.'</th>';
        $tabella_header .= '</tr>';
        $tabella_header .= '</thead>';
        $tabella_inizio_body = '<tbody>';
        // assemblo il tutto e visualizzo fino a <tbody>
        $aus = $tabella_definizione.$tabella_header.$tabella_inizio_body;
        $mform->addElement('html', $aus);
        // loop si parametri del report (una per riga della tabella)
        for ($i = 1; $i <= $numero_ruoli; $i++) {
            // colonna 1
            $colonna_1 = '<td align = "left">';
            $colonna_1 .= $elenco_ruoli[$i]->nome_ruolo;
            $colonna_1 .= '</td>';
            // colonna 2
            $nome_ruolo = 'name="ruolo_'.$i.'"';
            if($elenco_ruoli[$i]->flag_S_N == EML_GREP_SI) {
                $selezionato = ' checked>';
            } else {
                $selezionato = '>';
            }
            $colonna_2 = '<td align = "center">';        
            $colonna_2 .= '<input type="checkbox" '.$nome_ruolo.' value="1"'.$selezionato;
            $colonna_2 .= '</td>';
            $riga_tabella = '<tr>'.$colonna_1.$colonna_2.'</tr>';
            $mform->addElement('html', $riga_tabella);
            // campo id_parametro_nn (Hidden)
            $nome_id_ruolo = 'id_ruolo_'.$i;
            $valore_id_ruolo = $elenco_ruoli[$i]->id_ruolo;
            $mform->addElement('hidden', $nome_id_ruolo, $valore_id_ruolo);
        }
        // chiusura tabella selezione parametri
        $tabella_fine_body = '</tbody>';
        $tabella_fine_tabella = '</table>';
        // assemblo il tutto e visualizzo fino a </table>
        $aus = $tabella_fine_body.$tabella_fine_tabella;
        $mform->addElement('html', $aus);
        // pulsanti di conferma ed annulla operazione
        $submitlabel = get_string('grep_pulsante_prosegui', 'block_f2_report');
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel);
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
} // class form_modifica_parametri_report
class form_modifica_report extends moodleform {
    function definition() {
        $mform =& $this->_form;
        $id_report = $_REQUEST['id_report'];
        $mform->addElement('hidden', 'id_report', $id_report);
        $rec_mdl_f2_csi_pent_report = new EML_RECmdl_f2_csi_pent_report();
        $ret_code = EML_Get_mdl_f2_csi_pent_report($id_report, $rec_mdl_f2_csi_pent_report);
        $mform->addElement('hidden', 'id_voce_menu', $rec_mdl_f2_csi_pent_report->id_menu_report);
        $mform->addElement('text', 'nome_report', 
                get_string('grep_etichetta_nome_report', 'block_f2_report'), 
                'maxlength="255" size="100"');
        $mform->addRule('nome_report', null, 'required');
        $mform->addElement('text', 'nome_file_pentaho', 
                get_string('grep_etichetta_nome_file_pentaho', 'block_f2_report'),
                'maxlength="255" size="100"');
        $mform->addRule('nome_file_pentaho', null, 'required');
        $formato_default_array = array(
            'pageable/pdf' => 'pageable/pdf',
            'table/excel,page-mode=flow' => 'table/excel,page-mode=flow'
        );
        $mform->addElement('select', 'formato_default', 
                get_string('grep_etichetta_formato_default', 'block_f2_report'), 
                $formato_default_array, 
                null);        
        $mform->addRule('formato_default', null, 'required');
        $mform->addElement('text', 'posizione_in_elenco_report', get_string('grep_etichetta_posizione_in_elenco_report', 'block_f2_report'), null);
        $mform->addRule('posizione_in_elenco_report', null, 'required');
        $mform->addElement('selectyesno', 'flag_attivo', get_string('grep_etichetta_flag_attivo', 'block_f2_report'));
        $mform->addRule('flag_attivo', null, 'required');
        $mform->setDefault('nome_report', $rec_mdl_f2_csi_pent_report->nome_report);
        $mform->setDefault('nome_file_pentaho', $rec_mdl_f2_csi_pent_report->nome_file_pentaho);
        $mform->setDefault('formato_default', $rec_mdl_f2_csi_pent_report->formato_default);
        $mform->setDefault('posizione_in_elenco_report', $rec_mdl_f2_csi_pent_report->posizione_in_elenco_report);
        $mform->setDefault('flag_attivo', $rec_mdl_f2_csi_pent_report->attivo);
        // pulsanti della form
        $pulsanti = array();
        $pulsanti[] =& $mform->createElement('submit', 'submitbutton', get_string('grep_pulsante_prosegui', 'block_f2_report'));
        $pulsanti[] =& $mform->createElement('cancel');
        $mform->addGroup($pulsanti, 'pulsanti', ' ', ' ', false);
    }
} // form_modifica_report
class form_modifica_voce_menu_report extends moodleform {
    function definition() {
        $mform =& $this->_form;
        // lettura dei dati della voce
        $id = $_REQUEST['id_voce_menu'];
        $mform->addElement('hidden', 'id_voce_menu', $id);
        $rec_mdl_f2_csi_pent_menu_report = new EML_RECmdl_f2_csi_pent_menu_report();
        $ret_code = EML_Get_mdl_f2_csi_pent_menu_report($id, $rec_mdl_f2_csi_pent_menu_report);
        $mform->addElement('text', 'codice_voce_menu', get_string('grep_etichetta_codice_voce_menu', 'block_f2_report'), null);
        $mform->setDefault('codice_voce_menu',$rec_mdl_f2_csi_pent_menu_report->codice);
        $mform->addRule('codice_voce_menu', null, 'required');
        $mform->addElement('text', 'descrizione_voce_menu', 
                get_string('grep_etichetta_descrizione_voce_menu', 'block_f2_report'),
                'maxlength="255" size="100"');
        $mform->setDefault('descrizione_voce_menu',$rec_mdl_f2_csi_pent_menu_report->descrizione);
        $mform->addRule('descrizione_voce_menu', null, 'required');
        $mform->addElement('selectyesno', 'flag_attiva', get_string('grep_flag_attiva', 'block_f2_report'));
        $mform->setDefault('flag_attiva',$rec_mdl_f2_csi_pent_menu_report->attiva);
        $mform->addRule('flag_attiva', null, 'required');
        // pulsanti della form
        $pulsanti = array();
        $pulsanti[] =& $mform->createElement('submit', 'submitbutton', get_string('grep_pulsante_prosegui', 'block_f2_report'));
        $pulsanti[] =& $mform->createElement('cancel');
        $mform->addGroup($pulsanti, 'pulsanti', ' ', ' ', false);
    }
} // form_nuova_voce_menu_report
class form_nuova_voce_menu_report extends moodleform {
    function definition() {
        $mform =& $this->_form;
        $mform->addElement('text', 'codice_voce_menu', get_string('grep_etichetta_codice_voce_menu', 'block_f2_report'), null);
        $mform->addRule('codice_voce_menu', null, 'required');
        $mform->addElement('text', 'descrizione_voce_menu', 
                get_string('grep_etichetta_descrizione_voce_menu', 'block_f2_report'),
                'maxlength="255" size="100"');
        $mform->addRule('descrizione_voce_menu', null, 'required');
        $mform->addElement('selectyesno', 'flag_attiva', get_string('grep_flag_attiva', 'block_f2_report'));
        $mform->addRule('flag_attiva', null, 'required');
        // pulsanti della form
        $pulsanti = array();
        $pulsanti[] =& $mform->createElement('submit', 'submitbutton', get_string('grep_pulsante_prosegui', 'block_f2_report'));
        $pulsanti[] =& $mform->createElement('cancel');
        $mform->addGroup($pulsanti, 'pulsanti', ' ', ' ', false);
    }
} // form_nuova_voce_menu_report
class form_nuovo_report extends moodleform {
    function definition() {
        $mform =& $this->_form;
        $id_voce_menu = $_REQUEST['id_voce_menu'];
        $mform->addElement('hidden', 'id_voce_menu', $id_voce_menu);
        $mform->addElement('text', 'nome_report', 
                get_string('grep_etichetta_nome_report', 'block_f2_report'), 
                'maxlength="255" size="100"');
        $mform->addRule('nome_report', null, 'required');
        $mform->addElement('text', 'nome_file_pentaho', 
                get_string('grep_etichetta_nome_file_pentaho', 'block_f2_report'),
                'maxlength="255" size="100"');
        $mform->addRule('nome_file_pentaho', null, 'required');
        $formato_default_array = array(
            'pageable/pdf' => 'pageable/pdf',
            'table/excel,page-mode=flow' => 'table/excel,page-mode=flow'
        );
        $mform->addElement('select', 'formato_default', 
                get_string('grep_etichetta_formato_default', 'block_f2_report'), 
                $formato_default_array, 
                null);        
        $mform->addRule('formato_default', null, 'required');
        $mform->addElement('text', 'posizione_in_elenco_report', get_string('grep_etichetta_posizione_in_elenco_report', 'block_f2_report'), null);
        $mform->addRule('posizione_in_elenco_report', null, 'required');
        $mform->addElement('selectyesno', 'flag_attivo', get_string('grep_etichetta_flag_attivo', 'block_f2_report'));
        $mform->addRule('flag_attivo', null, 'required');
        // imposto un default per posizione_in-elenco_report
        $posizione_in_elenco_report = EML_Get_massimo_posizione_in_elenco_report($id_voce_menu);
        $posizione_in_elenco_report++;
        $mform->setDefault('posizione_in_elenco_report', $posizione_in_elenco_report);
        // pulsanti della form
        $pulsanti = array();
        $pulsanti[] =& $mform->createElement('submit', 'submitbutton', get_string('grep_pulsante_prosegui', 'block_f2_report'));
        $pulsanti[] =& $mform->createElement('cancel');
        $mform->addGroup($pulsanti, 'pulsanti', ' ', ' ', false);
    }
} // form_nuovo_report
