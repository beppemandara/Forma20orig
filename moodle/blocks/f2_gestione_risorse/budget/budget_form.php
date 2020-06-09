<?php
// $Id$
require_once($CFG->libdir.'/formslib.php');
require_once '../../../config.php';

$context = get_context_instance(CONTEXT_SYSTEM);

$capability = has_capability('block/f2_gestione_risorse:budget_edit', $context);
if(!$capability){
	print_error('nopermissions', 'error', '', 'budget');
}

$str = <<<'EFO'
<script type="text/javascript">
//<![CDATA[

function isNumberKey(evt)
{
   var charCode = (evt.which) ? evt.which : event.keyCode
   if (charCode > 31 && (charCode < 48 || charCode > 57) && charCode != 46)
      return false;

   return true;
}

//]]>
</script>
EFO;
echo $str;

//INIZIO FORM
	class inserisci_budget_form extends moodleform {
		public function definition() {
                    global $OUTPUT;
                    
			$mform =& $this->_form;	
                        $dati_budget = $this->_customdata['dati_budget'];
			
                        $mform->addElement('html', '<h3>'.get_string('capitoli', 'block_f2_gestione_risorse').'</h3>');

//INIZIO TABELLA 1
$table1= '<table  id="table_budget" align="center">
            <tr>
                <td width="150px">'.get_string('aula', 'block_f2_gestione_risorse').'
                </td>
                <td width="200px">
                    <span id="error_aula" class="error" style="display: none;">
                        Devi inserire un valore corretto.
                        <br>
                    </span>
                    <input type=text id="aula" value="'.$dati_budget[p_f2_bdgt_aula_cap]->val_float.'" name="aula" onkeypress="return isNumberKey(event)" maxlength="50" size="15" text-align="left" title="'.get_string('tooltip_aula', 'block_f2_gestione_risorse').'">
                </td>
                <td width="150px">'.get_string('obiettivo', 'block_f2_gestione_risorse').'
                </td>
                <td width="200px">
                    <span id="error_obiettivo" class="error" style="display: none;">
                        Devi inserire un valore corretto.
                        <br>
                    </span>
                    <input type=text id="obiettivo" value="'.$dati_budget[p_f2_bdgt_obiettivo_cap]->val_float.'" name="obiettivo" onkeypress="return isNumberKey(event)"  maxlength="50" size="15" text-align="left" title="'.get_string('tooltip_obiettivo', 'block_f2_gestione_risorse').'">
                </td>
            </tr>
            <tr>
                <td>'.get_string('e_learning', 'block_f2_gestione_risorse').'
                </td>
                <td>
                    <span id="error_e_learning" class="error" style="display: none;">
                        Devi inserire un valore corretto.
                        <br>
                    </span>
                    <input type=text id="e_learning" value="'.$dati_budget[p_f2_bdgt_elearning_cap]->val_float.'" name="e_learning" onkeypress="return isNumberKey(event)"  maxlength="50" size="15" text-align="left" title="'.get_string('tooltip_e_learning', 'block_f2_gestione_risorse').'">
                </td>
                <td>'.get_string('progetti_obiettivo', 'block_f2_gestione_risorse').'
                </td>
                <td>
                    <span id="error_progetti_obiettivo" class="error" style="display: none;">
                        Devi inserire un valore corretto.
                        <br>
                    </span>
                    <input type=text id="progetti_obiettivo" value="'.$dati_budget[p_f2_bdgt_prog_ob_cap]->val_float.'" name="progetti_obiettivo" onkeypress="return isNumberKey(event)"  maxlength="50" size="15" text-align="left" title="'.get_string('tooltip_progetti_obiettivo', 'block_f2_gestione_risorse').'">
                </td>
            </tr>
            <tr>
                <td>'.get_string('individuale', 'block_f2_gestione_risorse').'
                </td>
                <td>
                    <span id="error_individuale" class="error" style="display: none;">
                        Devi inserire un valore corretto.
                        <br>
                    </span>
                    <input type=text id="individuale" value="'.$dati_budget[p_f2_bdgt_individuale_cap]->val_float.'" name="individuale" onkeypress="return isNumberKey(event)"  maxlength="50" size="15" text-align="left" title="'.get_string('tooltip_individuale', 'block_f2_gestione_risorse').'">
                </td>
                <td>'.get_string('seminari_direzione', 'block_f2_gestione_risorse').'
                </td>
                <td>
                    <span id="error_seminari_direzione" class="error" style="display: none;">
                        Devi inserire un valore corretto.
                        <br>
                    </span>
                    <input type=text id="seminari_direzione" value="'.$dati_budget[p_f2_bdgt_seminari_cap]->val_float.'" name="seminari_direzione" onkeypress="return isNumberKey(event)"  maxlength="50" size="15" text-align="left" title="'.get_string('tooltip_seminari_direzione', 'block_f2_gestione_risorse').'">
                </td>
            </tr>
            <tr>
                <td>'.get_string('s1', 'block_f2_gestione_risorse').'
                </td>
                <td>
                    <span id="error_s1" class="error" style="display: none;">
                        Devi inserire un valore corretto.
                        <br>
                    </span>
                    <input type=text id="s1" value="'.$dati_budget[p_f2_bdgt_s1_cap]->val_float.'" name="s1" onkeypress="return isNumberKey(event)"  maxlength="50" size="15" text-align="left" title="'.get_string('tooltip_s1', 'block_f2_gestione_risorse').'">
                </td>
                <td>'.get_string('bonus_lingue', 'block_f2_gestione_risorse').'
                </td>
                <td>
                    <span id="error_bonus_lingue" class="error" style="display: none;">
                        Devi inserire un valore corretto.
                        <br>
                    </span>
                    <input type=text id="bonus_lingue" value="'.$dati_budget[p_f2_bdgt_bonus_lingue_cap]->val_float.'" name="bonus_lingue" onkeypress="return isNumberKey(event)"  maxlength="50" size="15" text-align="left" title="'.get_string('tooltip_bonus_lingue', 'block_f2_gestione_risorse').'">
                </td>
            </tr>
            <tr>
                <td>'.get_string('s2', 'block_f2_gestione_risorse').'
                </td>
                <td>
                    <span id="error_s2" class="error" style="display: none;">
                        Devi inserire un valore corretto.
                        <br>
                    </span>
                    <input type=text id="s2" value="'.$dati_budget[p_f2_bdgt_s2_cap]->val_float.'" name="s2" onkeypress="return isNumberKey(event)"  maxlength="50" size="15" text-align="left" title="'.get_string('tooltip_s2', 'block_f2_gestione_risorse').'">
                </td>
                <td>'.get_string('posti_aula', 'block_f2_gestione_risorse').'
                </td>
                <td>
                    <span id="error_posti_aula" class="error" style="display: none;">
                        Devi inserire un valore corretto.
                        <br>
                    </span>
                    <input type=text id="posti_aula" value="'.$dati_budget[p_f2_bdgt_posti_aula_cap]->val_float.'" name="posti_aula" onkeypress="return isNumberKey(event)"  maxlength="50" size="15" text-align="left" title="'.get_string('tooltip_posti_aula', 'block_f2_gestione_risorse').'">
                </td>
            </tr>
            <tr>
                <td>'.get_string('sj', 'block_f2_gestione_risorse').'
                </td>
                <td>
                    <span id="error_sj" class="error" style="display: none;">
                        Devi inserire un valore corretto.
                        <br>
                    </span>
                    <input type=text id="sj" value="'.$dati_budget[p_f2_bdgt_sj_cap]->val_float.'" name="sj" onkeypress="return isNumberKey(event)"  maxlength="50" size="15" text-align="left" title="'.get_string('tooltip_sj', 'block_f2_gestione_risorse').'">
                </td>
                <td>'.get_string('fondi_consiglio', 'block_f2_gestione_risorse').'
                </td>
                <td>
                    <span id="error_fondi_consiglio" class="error" style="display: none;">
                        Devi inserire un valore corretto.
                        <br>
                    </span>
                    <input type=text id="fondi_consiglio" value="'.$dati_budget[p_f2_bdgt_fondi_consiglio_cap]->val_float.'" name="fondi_consiglio" onkeypress="return isNumberKey(event)"  maxlength="50" size="15" text-align="left" title="'.get_string('tooltip_fondi_consiglio', 'block_f2_gestione_risorse').'">
                </td>
            </tr>
        </table>
';	
			
$mform->addElement('html', $table1);
//FINE TABELLA 1

$mform->addElement('html', '<h3>'.get_string('parametri', 'block_f2_gestione_risorse').'</h3>');

//INIZIO TABELLA 2
$table2= '<table align="center">
            <tr>
                <td width="150px">'.get_string('coefficiente_formativo', 'block_f2_gestione_risorse').'
                </td>
                <td width="200px">
                    <span id="error_coefficiente_formativo" class="error" style="display: none;">
                        Devi inserire un valore corretto.
                        <br>
                    </span>
                    <input type=text id="coefficiente_formativo" value="'.$dati_budget[p_f2_bdgt_coeff_form_par]->val_float.'" name="coefficiente_formativo" onkeypress="return isNumberKey(event)"  maxlength="50" size="15" text-align="left" title="'.get_string('tooltip_coefficente_formativo', 'block_f2_gestione_risorse').'">
                </td>
                <td width="150px">'.get_string('criterio_assegnamento_corsi_lingue', 'block_f2_gestione_risorse').'
                </td>
                <td width="200px">
                    <span id="error_criterio_assegnamento_corsi_lingue" class="error" style="display: none;">
                        Devi inserire un valore corretto.
                        <br>
                    </span>
                    <input type=text id="criterio_assegnamento_corsi_lingue" value="'.$dati_budget[p_f2_bdgt_corsi_lingue_par]->val_float.'" name="criterio_assegnamento_corsi_lingue" onkeypress="return isNumberKey(event)"  maxlength="50" size="15" text-align="left" title="'.get_string('tooltip_criterio_assegnamento_corsi_lingue', 'block_f2_gestione_risorse').'">
                </td>
            </tr>
            <tr>
            <td>'.get_string('assegnazione_giorni_crediti_aula', 'block_f2_gestione_risorse').'
            </td>
            <td>
                <span id="error_assegnazione_giorni_crediti_aula" class="error" style="display: none;">
                    Devi inserire un valore corretto.
                    <br>
                </span>
                <input type=text id="assegnazione_giorni_crediti_aula" value="'.$dati_budget[p_f2_bdgt_giorni_cred_aula_par]->val_float.'" name="assegnazione_giorni_crediti_aula" onkeypress="return isNumberKey(event)"  maxlength="50" size="15" text-align="left" title="'.get_string('tooltip_assegnazione_giorni_crediti_aula', 'block_f2_gestione_risorse').'">
            </td>
            <td>'.get_string('numero_strutture', 'block_f2_gestione_risorse').'
            </td>
            <td>
                <span id="error_numero_strutture" class="error" style="display: none;">
                    Devi inserire un valore corretto.
                    <br>
                </span>
                <input type=text id="numero_strutture" value="'.$dati_budget[p_f2_bdgt_num_strutture_par]->val_float.'" name="numero_strutture" onkeypress="return isNumberKey(event)"  maxlength="50" size="15" text-align="left" title="'.get_string('tooltip_numero_strutture', 'block_f2_gestione_risorse').'">
            </td>
        </tr>
    </table>
';

$mform->addElement('html', $table2);
//FINE TABELLA 2

$table3= '<p>
			<input type="submit" value="Salva" title="'.get_string('tooltip_avanti', 'block_f2_gestione_risorse').'" onClick="return confirmSubmitApplica()" />
			<a href="inserisci_budget.php"><button type="button">Annulla</button></a>
			<a href="configurazione_parametri.php"><button type="button" onClick="return confirmSubmitAvanti()">Avanti</button></a>
		  </p>';
$mform->addElement('html', $table3);
			}
	
	/*		
	function validation($data, $files) {
		global $DB, $CFG;
	        	$errors = parent::validation($data, $files);
	        	
	     if (empty($data['aula'])){
	                $errors['aula']= 'Campo obbligatorio.';
	     }
	   //  print_r($errors);exit;
	        return $errors;
    }	
*/
}
