<?php
	// $Id$
    global $PAGE, $site, $OUTPUT,$CFG, $DB;

    require_once('../../config.php');
    require_once($CFG->libdir.'/adminlib.php');
    require_once('lib.php');
    require_once('form_determina.php');
    require_once($CFG->dirroot.'/f2_lib/management.php');
    $PAGE->requires->js('/blocks/f2_formazione_individuale/js/module.js');
    require_once($CFG->dirroot.'/user/profile/lib.php');
    require_once($CFG->dirroot.'/tag/lib.php');
    require_once($CFG->libdir . '/filelib.php');
    require_login();

    $training	  = required_param('training', PARAM_TEXT);
    $id_codice_provvisorio_determina =  required_param('id_codice_provvisorio_determina', PARAM_INT);
    $result = optional_param('res', '', PARAM_ALPHANUM);
    $cod_det = optional_param('cod_det','',PARAM_TEXT);
    $cod_pro = optional_param('cod_pro','',PARAM_TEXT);
    $cod_dat = optional_param('cod_dat','',PARAM_INT);//data determina
    $cod_datp = optional_param('cod_datp','',PARAM_INT);//data protocollo
    $cod_aef = optional_param('cod_aef','',PARAM_INT);//Anno esercizio finanziario


    
    $sitecontext = get_context_instance(CONTEXT_SYSTEM);
    $site = get_site();
    $label_training = get_lable_training($training);
    $blockname = get_string('pluginname', 'block_f2_formazione_individuale');

    $url = new moodle_url("{$CFG->wwwroot}/blocks/f2_formazione_individuale/assegna_determina_def.php?training=".$training);
    $gestionecorsi_url = new moodle_url("{$CFG->wwwroot}/blocks/f2_formazione_individuale/gestione_corsi.php?training=".$training);
    $PAGE->set_url($url);
    $PAGE->set_context($sitecontext);
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title(get_string('assegna_determina', 'block_f2_formazione_individuale'));
    $PAGE->settingsnav;
    $PAGE->navbar->add(get_string('formazione_individuale', 'block_f2_formazione_individuale'));
    $PAGE->navbar->add(get_string($label_training, 'block_f2_formazione_individuale'));
    $PAGE->navbar->add(get_string('gestionecorsi', 'block_f2_formazione_individuale'), $gestionecorsi_url);
    $PAGE->navbar->add(get_string('assegna_determina', 'block_f2_formazione_individuale'));

    $param_CIG = get_parametro('p_f2_corsi_individuali_giunta');
    $param_CIL = get_parametro('p_f2_corsi_individuali_lingua_giunta');
    $param_CIC = get_parametro('p_f2_corsi_individuali_consiglio');
    $capability_giunta = has_capability('block/f2_formazione_individuale:individualigiunta', $sitecontext);
    $capability_linguagiunta = has_capability('block/f2_formazione_individuale:individualilinguagiunta', $sitecontext);
    $capability_consiglio = has_capability('block/f2_formazione_individuale:individualiconsiglio', $sitecontext);
    
    if(!(($capability_giunta && $training == $param_CIG->val_char) || ($capability_linguagiunta && $training == $param_CIL->val_char) || ($capability_consiglio && $training == $param_CIC->val_char))){
    	print_error('nopermissions', 'error', '', 'formazione_individuale');
    }
    $str ='
<script type="text/javascript">
    
    
function validateInput () {
		var id_numero_determina = document.getElementById(\'id_numero_determina\').value;
		var id_protocollo = document.getElementById(\'id_protocollo\').value;
	
    
if(id_numero_determina == "" || id_protocollo =="" ){
	alert("Non tutti i campi obbligatori sono stati compilati.");
}
    
}
    
    
</script>';
    
    echo $str;
    echo $OUTPUT->header();
    $context = context_system::instance();

    echo $OUTPUT->heading(get_string('assegna_determina', 'block_f2_formazione_individuale'));

    $baseurl = new moodle_url('/blocks/f2_formazione_individuale/assegna_determina_def.php', array('training' => $training));
  
    flush();

    $form = new determina_form('assegna_determina_def.php?training='.$training,compact('id_codice_provvisorio_determina','training','cod_det','cod_pro','cod_dat','cod_datp','cod_aef'),'post','',array('onKeyPress'=>'return disableEnterKey(event)'));
    
    if($mform = $form->get_data()){
    	
    	$data = new stdClass();
    	$data->id = $id_codice_provvisorio_determina;
    	$data->codice_determina =$mform->numero_determina;
    	$data->data_determina = $mform->data_determina;
    	$data->numero_protocollo = $mform->protocollo;
    	$data->data_protocollo = $mform->data_protocollo;
    	$data->anno_esercizio_finanziario =$mform->anno_esercizio_finanziario;

    	if(insert_determina($data,$training)){
    		redirect('cod_determina_def.php?training='.$training.'&res=1');
    	}else{
    		redirect('assegna_determina_def.php?training='.$training.'&res=0&id_codice_provvisorio_determina='.$id_codice_provvisorio_determina.'&cod_det='.$mform->numero_determina.'&cod_pro='.$mform->protocollo.'&cod_dat='.$mform->data_determina.'&cod_datp='.$mform->data_protocollo.'&cod_aef='.$mform->anno_esercizio_finanziario);
    	}
    }
    
    if($result=='0'){
    	echo '<b style="color:red">Errore dati: numero determina già in uso.</b>';
    	echo '<script type="text/javascript">alert(\''.get_string('alert_codice_det_in_uso','block_f2_formazione_individuale').'\'); </script>';
    }
    
		echo '<h3>'.get_string('riepilogo_informazioni_determina_provvisoria','block_f2_formazione_individuale').'</h3>';

		$determina = get_determina_provvisoria($id_codice_provvisorio_determina);

        $table = new html_table();
      //  $table->attributes= array('style'=>'white-space:nowrap;');
        $table->head[] = get_string('codice_provvisorio', 'block_f2_formazione_individuale');
        $table->size[] ='15%';
        $table->size[] ='85%';
        $table->align[] = 'left';
        $table->head[] = get_string('note', 'block_f2_formazione_individuale');
        $table->align[] = 'left';
        
        $table->width = "100%";

            $row = array ();
            $row[] = $determina->codice_provvisorio_determina;
            $row[] = $determina->note;
            $table->data[] = $row;
   
        echo html_writer::table($table);


        echo '<h3>'.get_string('dati_determina','block_f2_formazione_individuale').'</h3>';

        
        $form->display();

    
    echo $OUTPUT->footer();



