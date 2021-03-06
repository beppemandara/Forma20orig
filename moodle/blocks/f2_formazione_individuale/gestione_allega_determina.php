<?php
	// $Id$
    global $PAGE, $site, $OUTPUT,$CFG, $DB;

    require_once('../../config.php');
    require_once($CFG->libdir.'/adminlib.php');
    require_once('lib.php');
    require_once('form_gestione_allegato_determina.php');
    require_once($CFG->dirroot.'/f2_lib/management.php');
    
    require_once($CFG->dirroot.'/user/profile/lib.php');
    require_once($CFG->dirroot.'/tag/lib.php');
    require_once($CFG->libdir . '/filelib.php');
    require_login();
    
    $search = optional_param('cerca', '', PARAM_ALPHANUM);
    $tipo_determina = optional_param('tipo_determina', 'codiceprovvisoriodetermina', PARAM_ALPHANUM);
    $training = required_param('training', PARAM_TEXT);
    $label_training = get_lable_training($training);
    $dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
    $page         = optional_param('page', 0, PARAM_INT);
    $perpage      = optional_param('perpage', 20, PARAM_INT);        // how many per page
    $column   				= optional_param('column', 'codice_provvisorio_determina', PARAM_TEXT);
    
    $giorni_allegati_prefix = get_parametri_by_prefix('p_f2_corsiind_giorni_determine_x_allegati');
    $giorni_allegati = $giorni_allegati_prefix['p_f2_corsiind_giorni_determine_x_allegati']->val_int;
    
 
    if(isset($_POST['data_inizio']) && isset($_POST['data_fine'])){
    	$start_date = strtotime($_POST['data_inizio']['year']."-".$_POST['data_inizio']['month']."-".$_POST['data_inizio']['day']);
    	$end_date = strtotime($_POST['data_fine']['year']."-".$_POST['data_fine']['month']."-".$_POST['data_fine']['day']);
    }
    else if(isset($_GET['start_date']) && isset($_GET['end_date'])){
    	$start_date = $_GET['start_date'];
    	$end_date = $_GET['end_date'];
    }
    else
    {
	    $start_date = strtotime('-'.$giorni_allegati.' day',time());
	    $end_date = time();
    }
    $sitecontext = get_context_instance(CONTEXT_SYSTEM);
    $site = get_site();

    $blockname = get_string('pluginname', 'block_f2_formazione_individuale');

    $url = new moodle_url("{$CFG->wwwroot}/blocks/f2_formazione_individuale/gestione_allega_determina.php?training=".$training);
    $gestionecorsi_url = new moodle_url("{$CFG->wwwroot}/blocks/f2_formazione_individuale/gestione_corsi.php?training=".$training);
    $PAGE->set_url($url);
    $PAGE->set_context($sitecontext);
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title(get_string('allegati_determina', 'block_f2_formazione_individuale'));
    $PAGE->settingsnav;
    $PAGE->navbar->add(get_string('formazione_individuale', 'block_f2_formazione_individuale'));
    $PAGE->navbar->add(get_string($label_training, 'block_f2_formazione_individuale'));
    $PAGE->navbar->add(get_string('gestionecorsi', 'block_f2_formazione_individuale'), $gestionecorsi_url);
    $PAGE->navbar->add(get_string('gestione_allegato_determina', 'block_f2_formazione_individuale'));
    
    $param_CIG = get_parametro('p_f2_corsi_individuali_giunta');
    $param_CIL = get_parametro('p_f2_corsi_individuali_lingua_giunta');
    $param_CIC = get_parametro('p_f2_corsi_individuali_consiglio');
    $capability_giunta = has_capability('block/f2_formazione_individuale:individualigiunta', $sitecontext);
    $capability_linguagiunta = has_capability('block/f2_formazione_individuale:individualilinguagiunta', $sitecontext);
    $capability_consiglio = has_capability('block/f2_formazione_individuale:individualiconsiglio', $sitecontext);
    
    if(!(($capability_giunta && $training == $param_CIG->val_char) || ($capability_linguagiunta && $training == $param_CIL->val_char) || ($capability_consiglio && $training == $param_CIC->val_char))){
    	print_error('nopermissions', 'error', '', 'formazione_individuale');
    }
    
    $str = '
<script type="text/javascript">

    
function my_redirect(select) {
    	var tipo_det = select[select.selectedIndex].value;
    	window.location = "gestione_allega_determina.php?training='.$training.'&tipo_determina="+tipo_det;
}   

    		
function confirmSubmit()
{

var chk = document.getElementsByName("id_determina[]");
var tot = chk.length;
var num = 0;
for (i = 0; i < tot; i++) {
	if(chk[i].checked)
	 num++;
}

if(num > 0)
	{ 
		var agree=confirm(conferma);
			if (agree)
				return true ;
			else
				return false ;
	}
else
{
	alert("Non è stata selezionata nessuna determina provvisoria.");
	return false;
}
}
  

</script>
';
    echo $str;
    
    echo $OUTPUT->header();
    $context = context_system::instance();

    echo $OUTPUT->heading(get_string('gestione_allegato_determina', 'block_f2_formazione_individuale'));

    $baseurl = new moodle_url('/blocks/f2_formazione_individuale/gestione_allega_determina.php', array('training' => $training,'cerca'=>$search,'start_date'=>$start_date,'end_date'=>$end_date,'tipo_determina'=>$tipo_determina,'perpage' => $perpage, 'page'=>$page));
    
    
    $data = new stdClass;
    $data->tipo_corso = $training; //_$GET  MODIFICARE
    $data->dato_ricercato = $search; //_$GET  MODIFICARE
    $pagination = array('perpage' => $perpage, 'page'=>$page,'column'=>$column,'sort'=>$dir,'start_date'=>$start_date,'end_date'=>$end_date,'training' => $training,'cerca'=>$search);
    foreach ($pagination as $key=>$value)
    {
    	$data->$key = $value;
    }
    
    if($tipo_determina == 'codiceprovvisoriodetermina'){
	    $dati_table = get_codici_determina_provvisori($data);
	    $dati = $dati_table->dati;
	    $total_rows = $dati_table->count;
    }else if($tipo_determina == 'codicedeterminadefinitivo'){
    	$data->start_date = $start_date;
    	$data->end_date = $end_date;
    	$dati_table = get_codici_determina_definitiva($data);
    	$dati = $dati_table->dati;
    	$total_rows = $dati_table->count;
    }
    
    
  
    flush();

 /*   $form = new determina_form('assegna_determina_def.php?training='.$training,compact('id_codice_provvisorio_determina'));
    
    if($mform = $form->get_data()){
    	
    	$data = new stdClass();
    	$data->id = $id_codice_provvisorio_determina;
    	$data->codice_determina =$mform->numero_determina;
    	$data->data_determina = $mform->data_determina;
    	$data->numero_protocollo = $mform->protocollo;
    	$data->data_protocollo = $mform->data_protocollo;
    	$data->anno_esercizio_finanziario =$mform->anno_esercizio_finanziario;

    	if(insert_determina($data)){
    		redirect('cod_determina_def.php?training='.$training.'&res=1');
    	}else{
    		redirect('cod_determina_def.php?training='.$training.'&res=0');
    	}
    }
    
    */
    $form = new gestione_allegato_determina_form('gestione_allega_determina.php?training='.$training,compact('tipo_determina','search','start_date','end_date'));
    $form->display();
	//	echo '<h3>'.get_string('gestione_allegato_determina','block_f2_formazione_individuale').'</h3>';
		
		
	//	$determina = get_determina_provvisoria($id_codice_provvisorio_determina);
	
		echo "<b>Numero totale di record:".$total_rows."</b>";
	
		echo $OUTPUT->paging_bar($total_rows, $page, $perpage, $baseurl);
		
        $table = new html_table();
        $table->head[] = "";
        $table->head[] = get_string('codice_provvisorio', 'block_f2_formazione_individuale');
        $table->align[] = 'left';
        if($tipo_determina == 'codicedeterminadefinitivo'){
	        $table->head[] = get_string('numero_determina', 'block_f2_formazione_individuale');
	        $table->align[] = 'left';
	        $table->head[] = get_string('data_determina', 'block_f2_formazione_individuale');
	        $table->align[] = 'left';
	        $table->head[] = get_string('protocollo', 'block_f2_formazione_individuale');
	        $table->align[] = 'left';
	        $table->head[] = get_string('data_protocollo', 'block_f2_formazione_individuale');
	        $table->align[] = 'left';
        }
        $table->head[] = get_string('numero_corsi', 'block_f2_formazione_individuale');
        $table->align[] = 'left';
        $table->head[] = get_string('note', 'block_f2_formazione_individuale');
        $table->align[] = 'left';
        
        $table->width = "100%";
        
        foreach ($dati as $value_dati) {
	            $row = array ();
	            $row[] = "<input type='radio' name='id_determina[]' value='".$value_dati->id_determine."'/>";
	            $row[] = $value_dati->codice_provvisorio_determina;
	            if($tipo_determina == 'codicedeterminadefinitivo'){
		            $row[] = $value_dati->codice_determina;
		            $row[] = date('d/m/Y',$value_dati->data_determina);
		            $row[] = $value_dati->numero_protocollo;
		            $row[] = date('d/m/Y',$value_dati->data_protocollo);
	            }
	            $row[] = $value_dati->numero_corsi_determina_prov;
	            $row[] = $value_dati->note;
	            $table->data[] = $row;
        }
        
        
        echo '<form name="table_determine" id="table_determine" action="allega_determina.php?training='.$training.'" method="post">';
             echo html_writer::table($table);
        echo '<input type="submit" name="submit_determina" onClick="return confirmSubmit()"; value='.get_string('prosegui', 'block_f2_formazione_individuale').' />';
		echo '</form>';

      //  echo '<h3>'.get_string('dati_determina','block_f2_formazione_individuale').'</h3>';

        
    //    $form->display();

    
    echo $OUTPUT->footer();



