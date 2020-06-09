<?php
// $Id$
global $CFG,$DB,$OUTPUT;

require_once '../../../config.php';
require_once $CFG->libdir . '/formslib.php';
require_once($CFG->dirroot.'/blocks/f2_gestione_risorse/lib.php');
require_once($CFG->dirroot.'/f2_lib/report.php');

/*
 * AK-DL pagination: intestazioni necessarie per l'impaginazione e ordinamento
*/
$page     = optional_param('page', 0, PARAM_INT);
$perpage  = optional_param('perpage', 10, PARAM_INT);
$column   = optional_param('column', 'direzione', PARAM_TEXT);
$sort     = optional_param('sort', 'ASC', PARAM_TEXT);
//$column="orgfk";
$button_budget_approva = optional_param('button_budget_approva', 0, PARAM_INT);

require_login();

$context = get_context_instance(CONTEXT_SYSTEM);

$capability = has_capability('block/f2_gestione_risorse:budget_edit', $context);
if(!$capability){
	print_error('nopermissions', 'error', '', 'budget');
}

if (empty($CFG->loginhttps)) {
	$securewwwroot = $CFG->wwwroot;
} else {
	$securewwwroot = str_replace('http:','https:',$CFG->wwwroot);
}

$anno_in_corso = get_anno_formativo_corrente();

//CONTROLLO SE E' STATO CLICCATO IL PULSANTE "Approva Budget"
//CANCELLO IL VECCHIO BUDGET DELL'ANNO FORMATIVO CORRENTE E INSERISCO IL NUOVO BUDGET

	if($button_budget_approva == 1){
		$post_full_param = required_param('full_param',PARAM_TEXT);
		//$post_full_param = str_replace("##dubleap##", "\"", $post_full_param);
		$post_full_param = json_decode($post_full_param);
		
		$dati = $post_full_param->dati;
		
		$esito_approva_budget=0;
		
		$esito_approva_budget = approva_budget($dati,$anno_in_corso);
		
		//IN FONDO ALLA PAGINA VIENE Stampato L'ESITO DEL SALVATAGGIO DEL BUDGET if($button_budget_approva == 1)
	}

$PAGE->set_context($context);
$PAGE->set_url('/blocks/f2_gestione_risorse/budget');
$PAGE->navbar->add(get_string('budget', 'block_f2_gestione_risorse'),new moodle_url('./inserisci_budget.php'));
$PAGE->navbar->add(get_string('inserisci_budget', 'block_f2_gestione_risorse'), new moodle_url('./inserisci_budget.php'));
$PAGE->navbar->add(get_string('configurazione_parametri', 'block_f2_gestione_risorse'), new moodle_url('./configurazione_parametri.php'));
$PAGE->navbar->add(get_string('calcolo_budget', 'block_f2_gestione_risorse'), new moodle_url($url));
$PAGE->set_title(get_string('calcolo_budget', 'block_f2_gestione_risorse'));
$PAGE->set_heading($SITE->shortname.': '.$blockname);
$PAGE->set_pagelayout('standard');
$PAGE->settingsnav;

include_fileDownload_before_header();

echo $OUTPUT->header();
/*
 * AK-DL pagination: intestazioni necessarie per includere javascript
*/
include_fileDownload_after_header();

echo $OUTPUT->heading(get_string('calcolo_budget', 'block_f2_gestione_risorse'));

echo $OUTPUT->box_start();
	echo '<div class="contenitoreglobale">';
	
		if(budget_parziale_modificato($anno_in_corso)){//CONTROLLO SE C'E' QUALCHE RECORD CHE E' STATO MODIFICATO NELLA TABELLA DEI BUDGET PARZIALI
			echo '<b>Attenzione: Deve essere aggiornato il totale dei budget parziali nella pagina precedente.</b>';
		}	
		else{
		
			//INIZIO FORM
			class configurazione_parametri_budget_form extends moodleform {
				public function definition() {
					$mform =& $this->_form;
			
				}
			}
			$mform = new configurazione_parametri_budget_form(null);
			$mform->display();
			//FINE FORM
			
			
			// intestazioni tabella fornitori
			$head_table = array('direzione','posti_aula','bonus','obiettivo','individuale','lingue','e_learning','aula','giorni_crediti_aula','totale');
			$head_table_sort = array('direzione');
			$align = array ('left','center','center','center','center','center','center','center','center','center');
			$size = array ('8','10','10','10','10','10','10','10','10','10');
			
			$data			= new stdClass;
			$data->sort		= $sort;
			$data->page		= $page;
			$data->perpage	= $perpage;
			
				$pagination = array('perpage' => 0, 'page'=>0,'column'=>$column,'sort'=>$sort);
				foreach ($pagination as $key=>$value){
					$data->$key = $value;
				}
			
			$parametri_budget = get_parametri_budget();
			$partial_budget = get_partial_budget($data,$anno_in_corso);
			
			$full_dati= array(); //$full_dati � un array che contiene tutti gli oggetti budget delle direzioni(budget totale)
			$full_name_direzioni= array();
			foreach($partial_budget->dati as $param_budget){ 
			
				$posti_aula = $param_budget->coefficiente * $parametri_budget[p_f2_bdgt_posti_aula_cap]->val_float / 100;
				$bonus = $param_budget->coefficiente * $parametri_budget[p_f2_bdgt_bonus_lingue_cap]->val_float / 100;
				$aula = $param_budget->coefficiente * $parametri_budget[p_f2_bdgt_aula_cap]->val_float / 100;
				$obiettivo = $param_budget->coefficiente * $parametri_budget[p_f2_bdgt_obiettivo_cap]->val_float / 100;
				$individuale = $param_budget->coefficiente * $parametri_budget[p_f2_bdgt_individuale_cap]->val_float / 100;
				$lingue = $bonus * $parametri_budget[p_f2_bdgt_corsi_lingue_par]->val_float;
				$e_learning = $param_budget->coefficiente * $parametri_budget[p_f2_bdgt_elearning_cap]->val_float / 100;
				$giorni_crediti_aula = $aula / $parametri_budget[p_f2_bdgt_giorni_cred_aula_par]->val_float;
				$totale = $obiettivo + $individuale + $lingue + $e_learning + $aula;
			
				$param            = new stdClass;
				$param->direzione = $param_budget->orgfk;
				$param->posti_aula = $posti_aula;
				$param->bonus = $bonus;
				$param->obiettivo = $obiettivo;
				$param->individuale = $individuale;
				$param->lingue = $lingue;
				$param->e_learning = $e_learning;
				$param->aula = $aula;
				$param->giorni_crediti_aula = $giorni_crediti_aula;
				$param->totale = $totale;  //TOTALE DELLA RIGA
				
				$full_name_direzioni[$param_budget->orgfk] = $param_budget->shortname.' - '.$param_budget->fullname;
				
				$full_dati[] = $param;
				
			//TOTALI DELLE COLONNE
				$tot_posti_aula += $posti_aula;
				$tot_bonus += $bonus;
				$tot_obiettivo += $obiettivo;
				$tot_individuale += $individuale;
				$tot_lingue += $lingue;
				$tot_e_learning += $e_learning;
				$tot_giorni_crediti_aula += $giorni_crediti_aula;
				$tot_aula += $aula;
				$tot_totale += $totale;
				
			}
			
			//INSERISCO I TOTALI DELLE COLONNE NELL'OGGETTO $full_totali
				$full_totali = new stdClass;
				$full_totali->tot_posti_aula         = $tot_posti_aula           ;
				$full_totali->tot_bonus              = $tot_bonus                ;
				$full_totali->tot_obiettivo          = $tot_obiettivo            ;
				$full_totali->tot_individuale        = $tot_individuale          ;
				$full_totali->tot_lingue             = $tot_lingue               ;
				$full_totali->tot_e_learning         = $tot_e_learning           ;
				$full_totali->tot_giorni_crediti_aula= $tot_giorni_crediti_aula  ;
				$full_totali->tot_aula               = $tot_aula                 ;
				$full_totali->tot_totale             = $tot_totale               ;
				$full_totali->tot_totale             = $tot_totale               ;
			
			
			/*
				L'OGGETTO $full_param CONTIENE 
				IL NUMERO TOTALE DELLE DIREZIONI DELL'ANNO FORMATIVO IN CORSO
				IL TOTALE DEL BUDGET DI OGNI DIREZIONE
				IL TOTALE DEL TIPO DI FORMAZIONE Es. Individuale, Obiettivo ecc
			*/
			$full_param = new stdClass;
			$full_param->count = $partial_budget->count;
			$full_param->dati = $full_dati;
			$full_param->totali = $full_totali;
			
			
			$form_id='mform1';										// ID del form dove fare il submit
			$post_extra=array('column'=>$column,'sort'=>$sort);		// dati extra da aggiungere al post del form
			$total_rows = $full_param->count;
			
			//$total_parametri CONTIENE TUTTI I BUDGET DI OGNI DIREZIONE
			$total_parametri = $full_param->dati;     
			
			
			//L'ARRAY $parametri[] CONTIENE SOLO GLI OGGETTI CHE DEVONO ESSERE VISUALIZZATI NELLA TABELLA 
			//Es. SE � la pagina 1 contiene le prime 10 direzioni, se � la pagina 2 contiene dal 10 al 20 
			$parametri= array();
			
			for($i=$page * $perpage; $i < ($page * $perpage) + $perpage; $i++ ){ //l'array $parametri[] contiene solo gli oggetti che vengono visualizzati nella tabella
				if($total_parametri[$i])
					$parametri[] = $total_parametri[$i];
			}
			
			// INIZIO TABELLA FORNITORI
			$table = new html_table();
			$table->align = $align;
		//	$table->size[1] = '120px';
			$table->head = build_head_table($head_table,$head_table_sort,$post_extra,$total_rows, $page, $perpage, $form_id);
			
			foreach ($parametri as $parametro) {
					$table->data[] = array(
							$full_name_direzioni[$parametro->direzione],//nome direzione
							round($parametro->posti_aula,2),
							round($parametro->bonus,2),
							round($parametro->obiettivo,2),
							round($parametro->individuale,2),
							round($parametro->lingue,2),
							round($parametro->e_learning,2),
							round($parametro->aula,2),
							round($parametro->giorni_crediti_aula,2),
							round($parametro->totale,2)
				);	
			}
			
			//RIGA TOTALI
				$table->data[]= array(
										'<strong>TOTALI:</strong>',
										round($full_param->totali->tot_posti_aula,2),
										round($full_param->totali->tot_bonus,2),
										round($full_param->totali->tot_obiettivo,2),
										round($full_param->totali->tot_individuale,2),
										round($full_param->totali->tot_lingue,2),
										round($full_param->totali->tot_e_learning,2),
										round($full_param->totali->tot_aula,2),
										round($full_param->totali->tot_giorni_crediti_aula,2),
										round($full_param->totali->tot_totale,2)
									);
			
			
			//INIZIO EXPORT EXCEL
				class report_excel_formazione extends moodleform {
					public function definition() {
					global $CFG;
						$mform2 		=& $this->_form;
						$post_values = $this->_customdata['post_values'];
						$post_values = json_encode($post_values);
						
						$post_full_param = $this->_customdata['post_full_param'];
						$post_full_param = json_encode($post_full_param);
				
						$mform2->addElement('hidden', 'post_values',$post_values);
						$mform2->addElement('hidden', 'post_full_param',$post_full_param);
				
						//$buttonarray=array();
						//$buttonarray[] = &$mform2->createElement('submit', 'submitbutton', 'EXPORT EXCEL');
						//$mform2->addGroup($buttonarray, 'buttonar2', '', array(' '), false);
						//$mform2->closeHeaderBefore('buttonar2');
						$mform2->addElement('html',html_writer::empty_tag('input', array('type' => 'submit', 'class' => 'ico_xls btn', 'value' => get_string('export_excel_bgtot','block_f2_gestione_risorse'))));
						//$mform2->addElement('html',html_writer::tag('label', ' '.get_string('export_excel_bgtot_lbl', 'block_f2_gestione_risorse')));
					}
				}
				$mform_excel = new report_excel_formazione('excel_budget_totali.php',array('post_values'=>$data,'post_full_param'=>$full_param),'post',NULL,array('class'=>'export_excel'));
				$mform_excel->display();
			//FINE EXPORT EXCEL
			
			
			//CONTROLLO SE E' STATO CLICCATO IL PULSANTE "Approva Budget"
			//STAMPO A VIDEO L'ESITO DEL SALVATAGGIO BUDGET
				if($button_budget_approva == 1){
					if($esito_approva_budget == 1)
						echo '<p align="center"><b>L\'operazione &egrave; stata eseguita correttamente.</b></p>';
					else 
						echo '<p align="center"><b>L\'operazione non &egrave; stata eseguita correttamente.</b></p>';
				}
			
			echo "<p>".get_string('count_tot_rows', 'local_f2_traduzioni',$total_rows)."</p>";
			$paging_bar = new paging_bar_f2($total_rows, $page, $perpage, $form_id, $post_extra);
			echo $paging_bar->print_paging_bar_f2();
			
			echo html_writer::table($table);
			
			echo $paging_bar->print_paging_bar_f2();
			
			$full_param = json_encode($full_param); //CREO L'OGGETTO JSON DA INVIARE VIA POST ALLA PAGINA
			
			echo '<table>
					<tr>';
						echo '<td>
								<form action="vista_budget_totali.php" method="post">
									<input type="hidden" name="button_budget_approva" value="1">
									<input type="hidden" name="full_param" value='.$full_param.'>
									<input type="submit" value="Approva Budget" />
								</form>
							</td>';
						echo '<td><a href="configurazione_parametri.php"><input type="button" value="Indietro" /></a></td>';
			echo '</tr>
				</table>';
		
		
		}//else budget_parziale_modificato()
	
	echo '</div>';	
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
?>