<?php
// $Id$
global $CFG,$PAGE,$SITE,$OUTPUT;

require_once '../../../config.php';
require_once $CFG->libdir . '/formslib.php';
require_once($CFG->dirroot.'/blocks/f2_gestione_risorse/lib.php');
require_once($CFG->dirroot.'/f2_lib/report.php');
require_once($CFG->dirroot.'/f2_lib/management.php');

/*
 * AK-DL pagination: intestazioni necessarie per l'impaginazione e ordinamento
*/
$page     = optional_param('page', 0, PARAM_INT);
$perpage  = optional_param('perpage', 10, PARAM_INT);
$column   = optional_param('column', 'direzione', PARAM_TEXT);
$sort     = optional_param('sort', 'ASC', PARAM_TEXT);
//$column="orgfk";
$button_budget = optional_param('button_budget', 0, PARAM_INT);

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

$blockname = get_string('pluginname', 'block_f2_gestione_risorse');

$PAGE->set_context($context);
$PAGE->set_url('/blocks/f2_gestione_risorse/budget/configurazione_parametri.php');
$PAGE->navbar->add(get_string('inserisci_budget', 'block_f2_gestione_risorse'));
$PAGE->navbar->add(get_string('configurazione_capitoli', 'block_f2_gestione_risorse'), new moodle_url('./inserisci_budget.php'));
$PAGE->navbar->add(get_string('configurazione_parametri', 'block_f2_gestione_risorse'), new moodle_url('./configurazione_parametri.php'));
$PAGE->set_title(get_string('configurazione_parametri', 'block_f2_gestione_risorse'));
$PAGE->set_heading($SITE->shortname.': '.$blockname);
$PAGE->set_pagelayout('standard');
$PAGE->settingsnav;

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('inserisci_budget', 'block_f2_gestione_risorse')." - ".get_string('configurazione_parametri', 'block_f2_gestione_risorse'));
$str = <<<'EFO'
<script type="text/javascript">
//<![CDATA[
function edit_table(id)
{
	var txt = document.getElementById('criterioa_'+id);
	var txt1 = document.getElementById('settori_'+id);
	var txt2 = document.getElementById('ap_poa_'+id);
	var txt3 = document.getElementById('dirigenti_'+id);
	var txt4 = document.getElementById('personale_'+id);
	var btn = document.getElementById('applica_'+id);

	if(txt.getAttribute("readonly") == "readonly"){
		txt.removeAttribute("readonly");
		txt.removeAttribute("style");
		txt.setAttribute("style","width:50px");	
		txt1.removeAttribute("readonly");
		txt1.removeAttribute("style");
		txt1.setAttribute("style","width:50px");	
		txt2.removeAttribute("readonly");
		txt2.removeAttribute("style");
		txt2.setAttribute("style","width:50px");	
		txt3.removeAttribute("readonly");
		txt3.removeAttribute("style");
		txt3.setAttribute("style","width:50px");	
		txt4.removeAttribute("readonly");
		txt4.removeAttribute("style");
		txt4.setAttribute("style","width:50px");
		btn.removeAttribute("style");
		btn.setAttribute("visibility","visible");
	}
	else{
		txt.setAttribute("readonly","readonly");
   		txt.setAttribute("style","border:none; width:50px");	
   		txt1.setAttribute("readonly","readonly");
   		txt1.setAttribute("style","border:none; width:50px");	
   		txt2.setAttribute("readonly","readonly");
   		txt2.setAttribute("style","border:none; width:50px");	
   		txt3.setAttribute("readonly","readonly");
   		txt3.setAttribute("style","border:none; width:50px");	
   		txt4.setAttribute("readonly","readonly");
   		txt4.setAttribute("style","border:none; width:50px");	
   		btn.setAttribute("style","visibility:hidden");
	}
}

function modificato(){
var modificato = document.getElementById("modificato").value;
if(modificato == 1)
		alert("E' necessario aggiornare i totali prima di proseguire.");
		else
		document.location.href="vista_budget_totali.php";
}

function confirmSubmit()
{
var agree=confirm("Confermi la modifica al budget?");
if (agree)
	return true ;
else
	return false ;
}


//]]>
</script>
EFO;
echo $str;

echo $OUTPUT->box_start();
echo '<div class="contenitoreglobale">';

	$anno_in_corso = get_anno_formativo_corrente();

	/*
	 * INIZIO:
	 * QUANDO VIENE CARICATA LA PAGINA VIENE CONTROLLATO SE SONO STATE AGGIUNTE O ELIMINATE DELLE DIREZIONI
	 * E VIENE CONTROLLATO SE E' UN NUOVO ANNO FORMATIVO IN MODO DA CARICARE NELLA TABELLA {f2_partialbdgt}
	 * LE NUOVE DIREZIONI PER L'ANNO FORMATIVO IN CORSO
	 */
		$direzioni = get_direzioni(); //MI RICAVO TUTTE LE DIREZIONI

		gestione_direzioni_budget($direzioni,$anno_in_corso); // AGGIUNGO/ELIMINO DIREZIONI
	//FINE

	$modificato=budget_parziale_modificato($anno_in_corso); // PRIMA DI PROCEDERE CON IL CALCOLO DEL BUDGET CONTROLLO SE E' STATO MODIFICATO QUALCHE RECORD NELLA TABELLA
											  // {f2_partialbdgt}, IN QUESTO CASO DEVO PRIMA AGGIORNARE LA TABELLA CON I NUOVI VALORI
	echo '<input type="hidden" name="modificato" id="modificato" value='.$modificato.'>';
	
	//INIZIO FORM
	class configurazione_parametri_budget_form extends moodleform {
		public function definition() {
			$mform =& $this->_form;
			$mform->addElement('text', 'orgfk',get_string('ricerca','local_f2_traduzioni'), 'maxlength="254" size="50"');
			$mform->addElement('submit', 'Cerca', 'Cerca');
		}
	}
	$mform = new configurazione_parametri_budget_form(null);
	$mform->display();
	//FINE FORM
	
	// intestazioni tabella fornitori
	$head_table = array('modifica','direzione','criterio_a','settori','ap_poa','tot_b','criterio_b','dirigenti','criterio_c','personale','criterio_d','coefficiente','applica');
	$head_table_sort = array('direzione');
	$align = array ('center','center','center','center','center','center','center','center','center','center','center','center');
	$size = array ('10','10','10','10','10','10','10','10','10','10','10','10','10');
	
	$data = $mform->get_data();
	
	$pagination = array('perpage' => $perpage, 'page'=>$page,'column'=>$column,'sort'=>$sort);
	foreach ($pagination as $key=>$value){
		$data->$key = $value;
	}
	
	$full_param=get_partial_budget($data,$anno_in_corso);
	
	$form_id='mform1';										// ID del form dove fare il submit
	$post_extra=array('column'=>$column,'sort'=>$sort);		// dati extra da aggiungere al post del form
	$total_rows = $full_param->count;
	$parametri = $full_param->dati;
	
	// INIZIO TABELLA FORNITORI
	$table = new html_table();
	$table->align = $align;
	$table->size[1] = '120px';
	$table->head = build_head_table($head_table,$head_table_sort,$post_extra,$total_rows, $page, $perpage, $form_id);
	$total_param=get_total_partial_budget($anno_in_corso);//se non viene passato nessun anno viene restituito il totale di tutti gli anni
	$parametri_budget=get_parametri_budget();

	foreach ($parametri as $parametro) {
			$table->data[] = array(
//                                        '<input type="hidden" name="id" value='.$parametro->id.'>
					'<input type="hidden" name="button_budget" value="3">
					<img src="'.$CFG->wwwroot.'/pix/t/edit.gif"'.' alt="'.get_string('edit', 'block_f2_gestione_risorse').' class="iconsmall" id='.$parametro->id.' onclick="edit_table('.$parametro->id.');" style="cursor:pointer">',
					$parametro->shortname.' - '.$parametro->fullname,
					'<input type=text id="criterioa_'.$parametro->id.'" value='.round($parametro->criterioa,3).' name="criterioa_'.$parametro->id.'" style="width:50px; border:none" readonly="readonly">',
					//$parametro->criterioa,
					//$parametro->settori,
					'<input type=text id="settori_'.$parametro->id.'" value='.$parametro->settori.' name="settori_'.$parametro->id.'" style="width:50px; border:none">',
					//$parametro->ap_poa,
					'<input type=text id="ap_poa_'.$parametro->id.'" value='.$parametro->ap_poa.' name="ap_poa_'.$parametro->id.'" style="width:50px; border:none">',
					$parametro->totb,
					round($parametro->criteriob,3),
					//$parametro->dirigenti,
					'<input type=text id="dirigenti_'.$parametro->id.'" value='.$parametro->dirigenti.' name="dirigenti_'.$parametro->id.'" style="width:50px; border:none">',
					round($parametro->criterioc,3),
					//$parametro->personale,
					'<input type=text id="personale_'.$parametro->id.'" value='.$parametro->personale.' name="personale_'.$parametro->id.'" style="width:50px; border:none">',
					round($parametro->criteriod,3),
					round($parametro->coefficiente,3),
					'<input type="submit" value="Applica" name="applica_'.$parametro->id.'" style="visibility:hidden" onClick="return confirmSubmit()" id="applica_'.$parametro->id.'" />'
		);	
	}
	
	//INIZIO RIGA TOTALI
		$table->data[]= array(
								'<b>TOTALI:</B>',
								'',
								round($total_param->tot_criterioa,3),
								$total_param->tot_settori,
								$total_param->tot_ap_poa,
								$total_param->tot_totb,
								round($total_param->tot_criteriob,3),
								round($total_param->tot_dirigenti,3),
								round($total_param->tot_criterioc,3),
								$total_param->tot_personale,
								round($total_param->tot_criteriod,3),
								round($total_param->tot_coefficiente,3)
								);
	//FINE RIGA TOTALI
	
	echo "<p>".get_string('count_tot_rows', 'local_f2_traduzioni',$total_rows)."</p>";
	$paging_bar = new paging_bar_f2($total_rows, $page, $perpage, $form_id, $post_extra);
	echo $paging_bar->print_paging_bar_f2();
	
        echo '<form action="applica_parametri.php" method="post">';
	echo html_writer::table($table);
        echo '</form>';
	
	echo $paging_bar->print_paging_bar_f2();
	
	echo '<table><tr>';
	echo '<td><form action="applica_parametri.php" method="post"><input type="hidden" name="button_budget" value="1"><input type="submit" value="Aggiorna Totali" /></form></td>';
	echo '<td><form action="applica_parametri.php" method="post"><input type="hidden" name="button_budget" value="2"><input type="submit" value="Gestione Capitoli" /></form></td>';
	echo '<td><input type="button" value="Calcola Budget" onClick="modificato();" /></td>';
	echo '</tr></table>';
	
echo '</div>';	
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
?>