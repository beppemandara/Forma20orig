<?php
	// $Id$
    global $PAGE, $site, $OUTPUT,$CFG, $DB;
		
    require_once('../../config.php');
    require_once($CFG->libdir.'/adminlib.php');
    require_once('lib.php');
    require_once($CFG->dirroot.'/f2_lib/management.php');
    
    require_once($CFG->dirroot.'/user/profile/lib.php');
    require_once($CFG->dirroot.'/tag/lib.php');
    require_once($CFG->libdir . '/filelib.php');
    require_login();
    
    $search = optional_param('cerca', '', PARAM_ALPHANUM);
    $tipo_determina = optional_param('tipo_determina', 'codiceprovvisoriodetermina', PARAM_ALPHANUM);
    $training = required_param('training', PARAM_TEXT);
    $id_determine = required_param('id_determina', PARAM_ALPHANUM);
    
    $label_training = get_lable_training($training);
    $dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
    $page         = optional_param('page', 0, PARAM_INT);
    $perpage      = optional_param('perpage', 10, PARAM_INT);        // how many per page
    $column   	  = optional_param('column', 'lastname', PARAM_TEXT);
    $chk_selezionate = $_POST['id_course'];
    
   
    $sitecontext = get_context_instance(CONTEXT_SYSTEM);
    $site = get_site();

    $blockname = get_string('pluginname', 'block_f2_formazione_individuale');

    $url = new moodle_url("{$CFG->wwwroot}/blocks/f2_formazione_individuale/gestione_allega_determina.php?training=".$training);
    $gestionecorsi_url = new moodle_url("{$CFG->wwwroot}/blocks/f2_formazione_individuale/gestione_corsi.php?training=".$training);
    $PAGE->set_url($url);
    $PAGE->set_context($sitecontext);
    $PAGE->set_pagelayout('standard');
    $PAGE->requires->js('/blocks/f2_formazione_individuale/js/gestione_corsi.js');
    $PAGE->set_title(get_string('selezionautente', 'block_f2_formazione_individuale'));
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
    
    echo $OUTPUT->header();
    $context = context_system::instance();

    echo $OUTPUT->heading(get_string('gestione_allegato_determina', 'block_f2_formazione_individuale'));

    $str =
<<<'EFO'
<script type="text/javascript">
//<![CDATA[
function checkAll(from,to) {
	var i = 0;
	var chk = document.getElementsByName(to);
	var resCheckBtn = document.getElementsByName(from);
	var resCheck = resCheckBtn[i].checked;
	var tot = chk.length;
	for (i = 0; i < tot; i++) chk[i].checked = resCheck;
    num_check_checked();
}
function confirmSubmit() {
    var chk = document.getElementsByName("id_course[]");
    var tot = chk.length;
    var num = 0;
    for (i = 0; i < tot; i++) {
        if(chk[i].checked)
            num++;
    }

    if(num > 0)
    { 
        return confirm(conferma);
    }
    else
    {
        alert("Non e' stato selezionato nessun corso.");
        return false;
    }
}
//]]>
</script>
EFO;
    echo $str;
    
    $baseurl = new moodle_url('/blocks/f2_formazione_individuale/allega_determina.php', 
                        array('training'=>$training,'cerca'=>$search,'tipo_determina'=>$tipo_determina,'id_determina[]'=>$id_determine[0]));
    
    
    $data = new stdClass;
    $data->tipo_corso = $training; //_$GET  MODIFICARE
    $data->dato_ricercato = $search; //_$GET  MODIFICARE
    $pagination = array('perpage'=>$perpage, 'page'=>$page, 'column'=>$column, 'sort'=>$dir);
    foreach ($pagination as $key=>$value) {
    	$data->$key = $value;
    }
  
    
    foreach($id_determine as $determina){
    	$id_determina = $determina;
    	break;
    }
    $data->id_determina = $id_determina;
    $dati_table = get_corsi_determina_by_id($data);
    $dati = $dati_table->dati;
    $total_rows = $dati_table->count;
    
    //AK-LM prospetto costi: il documento generato deve essere unico, comprensivo di tutte le utenze.
    $data->perpage = $data->page = 0;
    $all_courses = get_corsi_determina_by_id($data);
    $array_prospetti = array_keys($all_courses->dati);
  
    flush();
    
    echo '<table style="width:100%;"><tr>';
	echo "<td style='width:80%;'><b style='font-size:11px'>".get_string('count_tot_rows', 'local_f2_traduzioni',$total_rows)."</b></td>";
	echo "<td><b style='font-size:11px'>".get_string('elementi_selezionati', 'local_f2_traduzioni').": <span id='span_elementi_sel'><span></b></td>";
	echo '</tr></table>';
		
    $table = new html_table();
    $table->head[] = "<input type='checkbox' name='id_course_all' onclick='checkAll(\"id_course_all\",\"id_course[]\");'/>";
    $table->head[] = get_string('cognome', 'block_f2_formazione_individuale')." ".get_string('nome', 'block_f2_formazione_individuale');
    $table->align[] = 'left';
    $table->head[] = get_string('matricola', 'block_f2_formazione_individuale');
    $table->align[] = 'left';
    $table->head[] = get_string('datainizio', 'block_f2_formazione_individuale');
    $table->align[] = 'left';
    $table->head[] = get_string('titolocorso', 'block_f2_formazione_individuale');
    $table->align[] = 'left';
    $table->head[] = get_string('codicearchiviazione', 'block_f2_formazione_individuale');
    $table->align[] = 'left';

    $table->width = "100%";

    foreach ($dati as $value_dati) {
        $checked = "";
		foreach ($chk_selezionate as $key => $chk) {
			if($value_dati->id_course == $chk){
				$checked = "checked='checked'";
				unset($chk_selezionate[$key]);
				break;
			}
		}
        $marticola = get_forzatura_or_moodleuser($value_dati->username);
        $row = array ();
        $row[] = "<input type='checkbox' name='id_course[]' value='{$value_dati->id_course}' {$checked} />";
        $row[] = fullname($value_dati);//$value_dati->lastname." ".$value_dati->firstname;
        $row[] = $marticola->idnumber;
        $row[] = date('d/m/Y',$value_dati->data_inizio);
        $row[] = $value_dati->titolo;
        $row[] = $value_dati->codice_archiviazione;
        $table->data[] = $row; 
    }

    echo '<form name="course_frm" id="course_frm" action="pdf_determina.php?training='.$training.'" method="post">';
    echo '<input type="hidden" name="training" value="'.$training.'">';
    echo "<input type='hidden' name='array_prospetti' value='".serialize($array_prospetti)."'>";
    foreach ($chk_selezionate as $chk) {
        echo '<input type="hidden" id="'.$chk.'" name="id_course[]" value="'.$chk.'">';
    }
    echo $OUTPUT->paging_bar($total_rows, $page, $perpage, $baseurl);
    echo html_writer::table($table);
    echo $OUTPUT->paging_bar($total_rows, $page, $perpage, $baseurl);
    
    $gestionecorsi_url_back = new moodle_url("{$CFG->wwwroot}/blocks/f2_formazione_individuale/gestione_allega_determina.php?training=".$training);
    echo '<input type="button" value="'.get_string("indietro", "block_f2_formazione_individuale").'" onclick="parent.location=\''.$gestionecorsi_url_back.'\'">';
    echo '&nbsp;&nbsp;';
    echo '<input type="submit" name="prospetto_costi" value="'.get_string('prospetto_costi', 'block_f2_formazione_individuale').'" />';
    echo '&nbsp;&nbsp;';
    echo '<input type="submit" name="schede_corsi" onClick="return confirmSubmit()" value="'.get_string('schede_corsi', 'block_f2_formazione_individuale').'" />';
    echo '</form>';

      //  echo '<h3>'.get_string('dati_determina','block_f2_formazione_individuale').'</h3>';

        
    //    $form->display();

    
    echo $OUTPUT->footer();



