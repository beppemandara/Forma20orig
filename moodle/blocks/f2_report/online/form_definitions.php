<?php
/* A. Albertin, G. Mandarà - CSI Piemonte - aprile 2015
 * 
 * GRFO - Gestione Report Formazione On-line
 * 
 * Definizione delle maschere
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}
global $CFG;
require_once($CFG->libdir.'/formslib.php');
require_once "costanti.php";
require_once "costanti_db.php";
require_once "strutture_dati.php";
require_once "function_db.php";
require_once "Aggiorna_corso_in_monitoraggio.php";
require_once "lib_eml.php";
class form_cancella_corso extends moodleform {
    function definition() {
        //global $CFG;
        //global $mysqli;
        $mform =& $this->_form;
        $id_corso = $_REQUEST['id_corso'];
        $mform->addElement('hidden', 'id_corso', $id_corso);
        // leggo i moduli del corso
        $numero_moduli = 0;
        $elenco_moduli = EML_Get_tbl_eml_pent_moduli_corsi_on_line($id_corso, $numero_moduli);
        // leggo le edizioni del corso
        $numero_edizioni = 0;
        $elenco_edizioni = EML_Get_tbl_eml_pent_edizioni_corsi_on_line($id_corso, $numero_edizioni);
        // visualizzo il titolo del corso
        $mform->addElement('hidden', 'cod_corso', $elenco_moduli[1]->cod_corso);
        $mform->addElement('hidden', 'titolo_corso', $elenco_moduli[1]->titolo_corso);
        $titolo_corso = $elenco_moduli[1]->cod_corso." - ".$elenco_moduli[1]->titolo_corso;
        $table = new html_table();
        $table->width = "100%";
        $table->head = array ();
        $table->head[] = get_string('grfo_etichetta_corso','block_f2_report');
        $table->align[] = 'left';
        $table->size[] = '10%';
        $table->head[] = $titolo_corso;
        $table->align[] = 'left';
        $table->size[] = '90%';
        // riga vuota usata per separare il titolo corso dalla tabella con moduli/risorse
        $row = array ();
        $row[] = ' ';
        $row[] = ' ';
        $table->data[] = $row;
        echo html_writer::table($table);        
        // visualizzo le edizioni del corso
        $table = new html_table();
        $table->width = "30%";
        $table->caption = 'Edizioni del corso';
        $table->head = array ();
        $table->head[] = get_string('grfo_etichetta_edizione', 'block_f2_report');
        $table->align[] = 'center';
        $table->size[] = '10%';
        $table->head[] = get_string('grfo_etichetta_data_inizio', 'block_f2_report');
        $table->align[] = 'center';
        $table->size[] = '10%';
        $table->head[] = get_string('grfo_etichetta_monitorata_S_N', 'block_f2_report');
        $table->align[] = 'center';
        $table->size[] = '10%';
        for ($i = 1; $i <= $numero_edizioni; $i++) {
            $row = array ();
            $row[] = $elenco_edizioni[$i]->edizione;
            $time = strtotime($elenco_edizioni[$i]->data_inizio);
            $data_stringa = date('d-m-Y',$time);
            $row[] = $data_stringa;
            if($elenco_edizioni[$i]->flag_monitorata_S_N == EML_PENT_EDIZIONE_MONITORATA) {
                $aus = EML_PENT_SI;
            } else {
                $aus = EML_PENT_NO;
            }
            $row[] = $aus;
            $table->data[] = $row;
        }
        // riga vuota usata per separare la tabella con le edizioni da quella con i moduli/risorse
        $row = array ();
        $row[] = ' ';
        $row[] = ' ';
        $row[] = ' ';
        $table->data[] = $row;
        echo html_writer::table($table);
        // visualizzo i moduli del corso
        $table = new html_table();
        $table->width = "100%";
        $table->head = array ();
        $table->head[] = get_string('grfo_etichetta_tipo_modulo', 'block_f2_report');
        $table->align[] = 'left';
        $table->size[] = '10%';
        $table->head[] = get_string('grfo_etichetta_nome_modulo', 'block_f2_report');
        $table->align[] = 'left';
        $table->size[] = '80%';
        $table->head[] = get_string('grfo_etichetta_monitorata_S_N', 'block_f2_report');
        $table->align[] = 'center';
        $table->size[] = '10%';
        for ($i = 1; $i <= $numero_moduli; $i++) {
            $row = array ();
            $row[] = $elenco_moduli[$i]->tipo_modulo;
            $row[] = $elenco_moduli[$i]->nome_modulo;
            if($elenco_moduli[$i]->posizione_in_report > 0) {
                $aus = EML_PENT_SI;
            } else {
                $aus = EML_PENT_NO;
            }
            $row[] = $aus;
            $table->data[] = $row;
        }
        echo html_writer::table($table);
        // pulsanti di conferma ed annulla operazione
        $submitlabel = get_string('grfo_pulsante_conferma_cancellazione', 'block_f2_report');
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel);
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
} // form_cancella_corso
class form_feed_back_page extends moodleform {
    function definition() {
        //global $CFG;
        //global $mysqli;
        $rec_tbl_eml_grfo_feed_back = new EML_RECtbl_eml_grfo_feed_back();
        $mform =& $this->_form;
        $id = $_REQUEST['id'];
        $ret_code = EML_GET_tbl_eml_grfo_feed_back($id, $rec_tbl_eml_grfo_feed_back);
        $mform->addElement('hidden', 'id', $rec_tbl_eml_grfo_feed_back->id);
        $mform->addElement('hidden', 'id_corso', $rec_tbl_eml_grfo_feed_back->id_corso);
        $mform->addElement('hidden', 'url', $rec_tbl_eml_grfo_feed_back->url);
        $mform->addElement('hidden', 'flag_parametro_id_corso', $rec_tbl_eml_grfo_feed_back->flag_parametro_id_corso);
        // Visualizzo (in una tabella) i dati letti)
        $table = new html_table();
        $table->width = "100%";
        $table->head = array ();
        $table->align = array();
        $table->size[] = '10%';
        $table->head[] = get_string('grfo_etichetta_corso', 'block_f2_report');
        $table->align[] = 'left';
        $table->size[] = '90%';
        $titolo_corso = $rec_tbl_eml_grfo_feed_back->cod_corso.' - '.$rec_tbl_eml_grfo_feed_back->titolo_corso;
        $table->head[] = $titolo_corso ;
        $table->align[] = 'left';
        $row = array ();
        $row[] = 'Operazione';
        $row[] = $rec_tbl_eml_grfo_feed_back->operazione;
        $table->data[] = $row;
        $row = array ();
        $row[] = 'Risultato';
        $row[] = $rec_tbl_eml_grfo_feed_back->stato;
        $table->data[] = $row;
        $row = array ();
        $row[] = 'Nota';
        $row[] = $rec_tbl_eml_grfo_feed_back->nota;
        $table->data[] = $row;
        echo html_writer::table($table);
        // pulsante prosegui         
        $pulsanti = array(
            $mform->createElement('submit', 'submitbutton', get_string('grfo_pulsante_prosegui', 'block_f2_report'))
        );
        $mform->addGroup($pulsanti, 'pulsanti', ' ', ' ', false);
    } //function definition
} // form_feed_back_page
class form_modifica_corso extends moodleform {
    function definition() {
        //global $CFG;
        //global $mysqli;
        $rec_mdl_course = new EML_RECmdl_course();
        $mform =& $this->_form;
        $id_corso = $_REQUEST['id_corso'];
        // leggo i dati del corso
        $iaus = EML_Get_mdl_course($id_corso, $rec_mdl_course);
        // leggo le edizioni del corso
        $aus = EML_Aggiorna_tbl_eml_pent_edizioni_corso($id_corso, EML_PENT_EDIZIONE_MONITORATA);
        $numero_edizioni = 0;
        $elenco_edizioni = EML_Get_tbl_eml_pent_edizioni_corsi_on_line($id_corso, $numero_edizioni);
        // leggo i moduli del corso
        //$aus = EML_Aggiorna_tbl_eml_pent_moduli_corso($id_corso, EML_PENT_MODULO_NON_MONITORATO);
        $aus = EML_Aggiorna_tbl_eml_pent_moduli_corso($id_corso);
        $numero_moduli = 0;
        $elenco_moduli = EML_Get_tbl_eml_pent_moduli_corsi_on_line($id_corso, $numero_moduli);        
        // campi nascosti della maschera
        $mform->addElement('hidden', 'id_corso', $id_corso);
        $mform->addElement('hidden', 'cod_corso', $elenco_moduli[1]->cod_corso);
        $mform->addElement('hidden', 'titolo_corso', $elenco_moduli[1]->titolo_corso);
        $mform->addElement('hidden', 'numero_edizioni', $numero_edizioni);
        $mform->addElement('hidden', 'numero_moduli', $numero_moduli);
        // visualizzo il titolo del corso
        $etichetta_titolo_corso = get_string('grfo_etichetta_corso','block_f2_report');
        $titolo_corso = $elenco_moduli[1]->cod_corso." - ".$elenco_moduli[1]->titolo_corso;
        $selezionare_edizioni_e_risorse = get_string('grfo_selezionare_edizioni_e_risorse', 'block_f2_report');
        $width_tabella = 'width="100%"';
        $tabella_definizione = '<table '.$width_tabella.'>';
        // definizione header
        $width_colonna_1 = 'width="10%"';
        $width_colonna_2 = 'width="90%"';
        $align_colonna_1 = 'text-align:left';
        $align_colonna_2 = 'text-align:left';
        $tabella_header = '<thead>';
        $tabella_header .= '<tr>';
        $tabella_header .= '<th '.$width_colonna_1.' style="'.$align_colonna_1.'">'.$etichetta_titolo_corso .'</th>';
        $tabella_header .= '<th '.$width_colonna_2.' style="'.$align_colonna_2.'">'.$titolo_corso.'</th>';
        $tabella_header .= '</tr>';
        $tabella_header .= '</thead>';
        $tabella_inizio_body = '<tbody>';
        $aus = $tabella_definizione.$tabella_header.$tabella_inizio_body;
        $mform->addElement('html', $aus);
        $colonna_1 = '<td align = "center">';
        $colonna_1 .= ' ';
        $colonna_1 .= '</td>';
        $colonna_2 = '<td align = "center">';
        $colonna_2 .= $selezionare_edizioni_e_risorse;
        $colonna_2 .= '</td>';
        $riga_tabella = '<tr>'.$colonna_1.$colonna_2.'</tr>';
        $mform->addElement('html', $riga_tabella);
        // chiusura tabella selezione edizioni
        $tabella_fine_body = '</tbody>';
        $tabella_fine_tabella = '</table>';
        // assemblo il tutto e visualizzo fino a </table>
        $aus = $tabella_fine_body.$tabella_fine_tabella;
        $mform->addElement('html', $aus);
        // Creo la tabella per selezione delle edizioni del corso da monitorare
        // Definizione tabella
        $width_tabella = 'width="30%"';
        $tabella_definizione = '<table '.$width_tabella.'>';
        // definizione header
        $width_colonna_1 = 'width="10%"';
        $width_colonna_2 = 'width="10%"';
        $width_colonna_3 = 'width="10%"';
        $header_colonna_1 = get_string('grfo_etichetta_edizione', 'block_f2_report');
        $header_colonna_2 = get_string('grfo_etichetta_data_inizio', 'block_f2_report');
        $header_colonna_3 = get_string('grfo_etichetta_monitorata_S_N', 'block_f2_report');
        $align_colonna_1 = 'text-align:center';
        $align_colonna_2 = 'text-align:left';
        $align_colonna_3 = 'text-align:center';
        $tabella_header = '<thead>';
        $tabella_header .= '<tr>';
        $tabella_header .= '<th '.$width_colonna_1.' style="'.$align_colonna_1.'">'.$header_colonna_1.'</th>';
        $tabella_header .= '<th '.$width_colonna_2.' style="'.$align_colonna_2.'">'.$header_colonna_2.'</th>';
        $tabella_header .= '<th '.$width_colonna_3.' style="'.$align_colonna_3.'">'.$header_colonna_3.'</th>';
        $tabella_header .= '</tr>';
        $tabella_header .= '</thead>';
        $tabella_inizio_body = '<tbody>';
        // assemblo il tutto e visualizzo fino a <tbody>
        $aus = $tabella_definizione.$tabella_header.$tabella_inizio_body;
        $mform->addElement('html', $aus);
        // loop sulle edizioni del corso (una per riga della tabella)
        for ($i = 1; $i <= $numero_edizioni; $i++) {
            // colonna 1
            $colonna_1 = '<td align = "center">';
            $colonna_1 .= $elenco_edizioni[$i]->edizione;
            $colonna_1 .= '</td>';
            // colonna 2
            $colonna_2 = '<td>';
            $time = strtotime($elenco_edizioni[$i]->data_inizio);
            $data_stringa = date('d-m-Y',$time);
            $colonna_2 .= $data_stringa;
            $colonna_2 .= '</td>';
            // colonna 3
            $nome_edizione = 'name="edizione_'.$i.'"';
            if($elenco_edizioni[$i]->flag_monitorata_S_N == EML_PENT_EDIZIONE_MONITORATA) {
                $selezionato = ' checked>';
            } else {
                $selezionato = '>';
            }
            $colonna_3 = '<td align = "center">';
            $colonna_3 .= '<input type="checkbox" '.$nome_edizione.' value="1"'.$selezionato;
            $colonna_3 .= '</td>';
            $riga_tabella = '<tr>'.$colonna_1.$colonna_2.$colonna_3.'</tr>';
            $mform->addElement('html', $riga_tabella);
            // campo id_edizione_nn (Hidden)
            $nome_id_edizione = 'id_edizione_'.$i;
            $valore_id_edizione = $elenco_edizioni[$i]->id_edizione;
            $mform->addElement('hidden', $nome_id_edizione, $valore_id_edizione);
        }
        // chiusura tabella selezione edizioni
        $tabella_fine_body = '</tbody>';
        $tabella_fine_tabella = '</table>';
        // assemblo il tutto e visualizzo fino a </table>
        $aus = $tabella_fine_body.$tabella_fine_tabella;
        $mform->addElement('html', $aus);
        // tabella con la selezione moduli da monitorare
        // definizione tabella
        $width_tabella = 'width="100%"';
        $tabella_definizione = '<table '.$width_tabella.'>';
        // definizione header
        $width_colonna_1 = 'width="10%"';
        $width_colonna_2 = 'width="80%"';
        $width_colonna_3 = 'width="10%"';
        $header_colonna_1 = get_string('grfo_etichetta_tipo_modulo', 'block_f2_report');
        $header_colonna_2 = get_string('grfo_etichetta_nome_modulo', 'block_f2_report');
        $header_colonna_3 = get_string('grfo_etichetta_monitorata_S_N', 'block_f2_report');$tabella_header = "";
        $align_colonna_1 = 'text-align:left';
        $align_colonna_2 = 'text-align:left';
        $align_colonna_3 = 'text-align:center';
        $tabella_header = '<thead>';
        $tabella_header .= '<tr>';
        $tabella_header .= '<th '.$width_colonna_1.' style="'.$align_colonna_1.'">'.$header_colonna_1.'</th>';
        $tabella_header .= '<th '.$width_colonna_2.' style="'.$align_colonna_2.'">'.$header_colonna_2.'</th>';
        $tabella_header .= '<th '.$width_colonna_3.' style="'.$align_colonna_3.'">'.$header_colonna_3.'</th>';
        $tabella_header .= '</tr>';
        $tabella_header .= '</thead>';
        $tabella_inizio_body = '<tbody>';
        // assemblo il tutto e visualizzo fino a <tbody>
        $aus = $tabella_definizione.$tabella_header.$tabella_inizio_body;
        $mform->addElement('html', $aus);
        // loop sui moduli del corso (uno per riga della tabella)
        for ($i = 1; $i <= $numero_moduli; $i++) {
            // colonna 1
            $colonna_1 = '<td>';
            $colonna_1 .= $elenco_moduli[$i]->tipo_modulo;
            $colonna_1 .= '</td>';
            // colonna 2
            $colonna_2 = '<td>';
            $colonna_2 .= $elenco_moduli[$i]->nome_modulo;
            $colonna_2 .= '</td>';
            // colonna 3
            $nome_risorsa = 'name="risorsa_'.$i.'"';
            if ($elenco_moduli[$i]->posizione_in_report > 0) {
                $selezionato = ' checked>';
            } else {
                $selezionato = '>';
            }
            $colonna_3 = '<td align = "center">';
            $colonna_3 .= '<input type="checkbox" '.$nome_risorsa.' value="1"'.$selezionato;
            $colonna_3 .= '</td>';
            $riga_tabella = '<tr>'.$colonna_1.$colonna_2.$colonna_3.'</tr>';
            $mform->addElement('html', $riga_tabella);
            // campo progressivo_risorsa_nn (Hidden)
            $nome_progressivo_risorsa = 'progressivo_risorsa_'.$i;
            $valore_progressivo_risorsa = $elenco_moduli[$i]->progressivo;
            $mform->addElement('hidden', $nome_progressivo_risorsa, $valore_progressivo_risorsa);
        } // loop sui moduli del corso (uno per riga della tabella) 
        // chiusura tabella selezione moduli
        $tabella_fine_body = '</tbody>';
        $tabella_fine_tabella = '</table>';
        // assemblo il tutto e visualizzo fino a </table>
        $aus = $tabella_fine_body.$tabella_fine_tabella;
        $mform->addElement('html', $aus);
        // a.a. aprile 2015 - start
        // Gestione selezione risorsa da usare per il punteggio finale
        //      leggo i dati del corso estraendo il campop enablecompletion
        //      imposto il campo nscosto enablecompletion
        //      se previsto tracciamento completamento
        //          leggo le risorse utilizzabili per il punteggio finale 
        //              (e l'eventuale risorsa selezionata allo scopo)
        //          se esistono delle risorse usabili pwer il punteggio
        //              imposto il campo di select (con l'eventuale default)
        //              imposto il campo nascosto punteggio_selezionato (valore = 1)
        //          altrimenti
        //              imposto il campo nascosto punteggio_selezionato (valore = 0)
        //          fine se
        //      fine se
        $enablecompletion = $rec_mdl_course->enablecompletion;
        if ($enablecompletion == 1) {
            // il corso prevede il monitoraggio del completamento
            //      - carico lista di scelta con le risorse usabili per il punteggio 
            //          (e imposto di default quella attualmente selezionata)
            //      - se ci sono delle risorse usabili per il punteggio
            //          - visualizzo la lista di scelta (campo obbligatorio)
            //          - carico il campo nascosto esiste_risorsa_punteggio
            //      - fine se 
            $numero_risorse_punteggio = 0;
            $risorsa_default = 0;
            $elenco_risorse_punteggio = EML_Get_elenco_risorse_punteggio(
                    $id_corso, $numero_risorse_punteggio, $risorsa_default);
            if ($numero_risorse_punteggio > 0) {
                $select_risorsa_punteggio = $mform->addElement(
                        'select',
                        'risorsa_punteggio',
                        get_string('grfo_risorsa_punteggio', 'block_f2_report'), 
                        $elenco_risorse_punteggio);
                $mform->addRule('risorsa_punteggio', null, 'required');
                $mform->addHelpButton('risorsa_punteggio', 'grfo_risorsa_punteggio', 'block_f2_report');
                if ($risorsa_default <> 0) {
                    $select_risorsa_punteggio->setSelected($risorsa_default);
                }
                $mform->addElement('hidden', 'esiste_risorsa_punteggio', 1);
            } else {
                $mform->addElement('hidden', 'esiste_risorsa_punteggio', 0);
            }
        } //enablecompletion = 1 (previstro tracciamento completamento)
        // a.a. aprile 2015 - end
        // pulsanti di conferma ed annulla operazione
        $submitlabel = get_string('grfo_pulsante_conferma_modifica', 'block_f2_report');
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel);
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
} // class form_modifica_corso
class form_modifica_parametri extends moodleform {
    function definition() {
        //global $CFG;
        $mform =& $this->_form;
        // p_grfo_data_inizio_monitoraggio
        $etichetta_data_inizio_monitoraggio = get_string('grfo_etichetta_data_inizio_monitoraggio', 'block_f2_report');
        $mform->addElement('date_selector', 'data_inizio_monitoraggio', $etichetta_data_inizio_monitoraggio);
        $mform->setDefault('data_inizio_monitoraggio',$this->_customdata['data_inizio_monitoraggio']);
        require_once 'testo_spiegazione_data_inizio_monitoraggio.php';
        // pulsanti della form
        $pulsanti = array();
        $pulsanti[] =& $mform->createElement('submit', 'submitbutton', get_string('grfo_pulsante_prosegui', 'block_f2_report'));
        $pulsanti[] =& $mform->createElement('cancel');
        $mform->addGroup($pulsanti, 'pulsanti', ' ', ' ', false);
    }
} // form_modifica_parametri
class form_nuovo_corso extends moodleform {
    function definition() {
        //global $CFG;
        $mform =& $this->_form;
        $numero_corsi_inseribili = 0;
        $elenco_corsi_select = EML_Get_elenco_corsi_inseribili($numero_corsi_inseribili);
        if ($numero_corsi_inseribili == 0) {
            require "testo_condizioni_x_inserimento.php";
            echo '<form id="pulsanti_nuovo_corso" action="report_on_line.php" method="post">';
            echo '<table><tr><td>';
            $aus = get_string('grfo_pulsante_elenco_corsi', 'block_f2_report');
            echo '<input type="submit" name="report_on_line" value="'.$aus.'"/>';
            echo '</td></tr></table>';
            echo '</form>';
        } else {
            $mform->addElement('select', 'id_corso', get_string('grfo_etichetta_id_corso', 'block_f2_report'), $elenco_corsi_select);
            $mform->addRule('id_corso', null, 'required');
            $mform->addHelpButton('id_corso', 'grfo_id_corso', 'block_f2_report');
            // pulsanti della form
            $pulsanti = array();
            $pulsanti[] =& $mform->createElement('submit', 'submitbutton', get_string('grfo_pulsante_prosegui', 'block_f2_report'));
            $pulsanti[] =& $mform->createElement('cancel');
            $mform->addGroup($pulsanti, 'pulsanti', ' ', ' ', false);
        }
    }
} // form_nuovo_corso
?>