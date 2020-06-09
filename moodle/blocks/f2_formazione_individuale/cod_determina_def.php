<?php
	// $Id$
	
    global $PAGE, $site, $OUTPUT;

    require_once('../../config.php');
    require_once($CFG->libdir.'/adminlib.php');
    require_once('lib.php');
    require_once($CFG->dirroot.'/f2_lib/management.php');
    
    require_once($CFG->dirroot.'/user/profile/lib.php');
    require_once($CFG->dirroot.'/tag/lib.php');
    require_once($CFG->libdir . '/filelib.php');
    

    $column   				= optional_param('column', 'codice_provvisorio_determina', PARAM_TEXT);
    $dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
    $page         = optional_param('page', 0, PARAM_INT);
    $perpage      = optional_param('perpage', 15, PARAM_INT);        // how many per page
    $search		  = optional_param('search', '', PARAM_TEXT);	
    $dato_ricercato= optional_param('dato_ricercato', '', PARAM_ALPHANUM);
    $result = optional_param('res', '', PARAM_ALPHANUM);
    $training	  = required_param('training', PARAM_TEXT);
    $label_training = get_lable_training($training);
    

    $sitecontext = get_context_instance(CONTEXT_SYSTEM);
    $site = get_site();

    $blockname = get_string('pluginname', 'block_f2_formazione_individuale');

    $url = new moodle_url("{$CFG->wwwroot}/blocks/f2_formazione_individuale/cod_determina_def.php?training=".$training."");
    $gestionecorsi_url = new moodle_url("{$CFG->wwwroot}/blocks/f2_formazione_individuale/gestione_corsi.php?training=".$training."");
    $PAGE->set_url($url);
    $PAGE->set_context($sitecontext);
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title(get_string('assegna_determina', 'block_f2_formazione_individuale'));
    $PAGE->settingsnav;
    $PAGE->navbar->add(get_string('formazione_individuale', 'block_f2_formazione_individuale'));
    $PAGE->navbar->add(get_string($label_training, 'block_f2_formazione_individuale'));
    $PAGE->navbar->add(get_string('gestionecorsi', 'block_f2_formazione_individuale'), $gestionecorsi_url);
    $PAGE->navbar->add(get_string('codice_determina_definitivo', 'block_f2_formazione_individuale'));
    $PAGE->set_heading($SITE->shortname.': '.$blockname);
    
    $param_CIG = get_parametro('p_f2_corsi_individuali_giunta');
    $param_CIL = get_parametro('p_f2_corsi_individuali_lingua_giunta');
    $param_CIC = get_parametro('p_f2_corsi_individuali_consiglio');
    $capability_giunta = has_capability('block/f2_formazione_individuale:individualigiunta', $sitecontext);
    $capability_linguagiunta = has_capability('block/f2_formazione_individuale:individualilinguagiunta', $sitecontext);
    $capability_consiglio = has_capability('block/f2_formazione_individuale:individualiconsiglio', $sitecontext);
    
    if(!(($capability_giunta && $training == $param_CIG->val_char) || ($capability_linguagiunta && $training == $param_CIL->val_char) || ($capability_consiglio && $training == $param_CIC->val_char))){
    	print_error('nopermissions', 'error', '', 'formazione_individuale');
    }
    
    $str = <<<'EFO'
<script type="text/javascript">
//<![CDATA[
    
    
    
function confirmSubmit()
{
    
var chk = document.getElementsByName("id_codice_provvisorio_determina");
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
	alert("Non e' stata selezionata nessuna determina.");
	return false;
}
    
}
//]]>
</script>
EFO;
    echo $str;
    
    echo $OUTPUT->header();
    $context = context_system::instance();


    $baseurl = new moodle_url('/blocks/f2_formazione_individuale/cod_determina_def.php', array('sort' => $dir, 'dir' => $dir, 'perpage' => $perpage,'training' => $training,'dato_ricercato'=>$dato_ricercato));
    
    $data = new stdClass;
    $data->tipo_corso = $training; //_$GET  MODIFICARE
    $data->dato_ricercato = $dato_ricercato; //_$GET  MODIFICARE
    $pagination = array('perpage' => $perpage, 'page'=>$page,'column'=>$column,'sort'=>$dir,'dato_ricercato'=>$dato_ricercato);
    foreach ($pagination as $key=>$value)
    {
    	$data->$key = $value;
    }
     
    $dati_all = get_codici_determina_provvisori($data);
    $cod_det_prov = $dati_all->dati;
    $total_rows = $dati_all->count;

    echo $OUTPUT->heading(get_string('assegnacodicedeterminadef', 'block_f2_formazione_individuale'));
    
   //echo '<h2>Assegna Codice determina definitivo</h2>';
    $strall = get_string('all');

    if($result=='1'){
    	echo '<b style="color:green">Salvataggio Determina effettuato con successo.</b>';
    }else if($result=='0'){
    	echo '<b style="color:red">Errore dati: numero determina già in uso.</b>';
    }

    flush();

    if (!$cod_det_prov) {
        $match = array();
        echo $OUTPUT->heading(get_string('nousersfound'));
        echo html_writer::start_tag('form', array('action' => $baseurl, 'method' => 'post'));
        // Submit ricerca
        echo '<table width="100%"><tr width="100%">';
        echo '<td width="100px"><input maxlength="254" size="50" name="dato_ricercato" type="text" id="id_dato_ricercato" value="'.$dato_ricercato.'" /></td>';
        echo '<td><input name="Cerca" value="Cerca" type="submit" id="id_Cerca" /></td>';
        echo '</tr></table>';
        echo html_writer::end_tag('form');
        $table = NULL;

    } else {
    //	echo $OUTPUT->heading("$total_rows ".get_string('users'));
    	
    	echo html_writer::start_tag('form', array('action' => $baseurl, 'method' => 'post'));
    	// Submit ricerca
    	echo '<table><tr>';
    	echo '<td >Codice determina provvisorio: <input maxlength="254" name="dato_ricercato" type="text" id="id_dato_ricercato" value="'.$dato_ricercato.'" /></td>';
    	echo '<td><input name="Cerca" value="Cerca" type="submit" id="id_Cerca" /></td>';
    	echo '</tr></table>';
    	echo html_writer::end_tag('form');
    	
    	echo "<b>".$total_rows." Determine provvisorie</b>";

    	$columndir = $dir == "ASC" ? "DESC":"ASC";
    	$columnicon = $dir == "ASC" ? "down":"up";
    	$columnicon = " <img src=\"" . $OUTPUT->pix_url('t/' . $columnicon) . "\" alt=\"\" />";
    	
    	echo $OUTPUT->paging_bar($total_rows, $page, $perpage, $baseurl);
    	
        $table = new html_table();
        $table->head = array ();
        $table->align = array();
        $table->head[] = "";
        $table->head[] = "<a href=\"cod_determina_def.php?dir=".$columndir."&training=".$training."&dato_ricercato=".$dato_ricercato."&page=".$page."&perpage=".$perpage."\">".get_string('codice_provvisorio', 'block_f2_formazione_individuale')."</a>".$columnicon;
        $table->align[] = 'left';
        $table->head[] = get_string('note', 'block_f2_formazione_individuale');
        $table->align[] = 'left';
        $table->head[] = get_string('numero_corsi', 'block_f2_formazione_individuale');
        $table->align[] = 'left';
        $table->align[] = 'center';
        $table->width = "100%";
        foreach ($cod_det_prov as $course) {
        	
            $row = array ();
        	$row[] = "<input type='radio' name='id_codice_provvisorio_determina' value='".$course->id_determine."'>";
            $row[] = $course->codice_provvisorio_determina;
            $row[] = $course->note;
            $row[] = $course->numero_corsi_determina_prov;
            $table->data[] = $row;
        }
    }

    
    if (!empty($table)) {
    	
    	echo '<form id="form_cod_def" action="assegna_determina_def.php?training='.$training.'" method="post">';
        	echo html_writer::table($table);
        	echo '<input type="submit" name="assegna_determina" onclick="return confirmSubmit();" value="'.get_string("assegna_determina", "block_f2_formazione_individuale").'">';
        echo '</form>';
        echo $OUTPUT->paging_bar($total_rows, $page, $perpage, $baseurl);
    }    
    echo $OUTPUT->footer();



