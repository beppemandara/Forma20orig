<?php
//$Id: gestione_corsi.php 1432 2016-12-06 09:26:34Z l.moretto $
global $OUTPUT, $PAGE, $SITE,$CFG;

require_once '../../config.php';
require_once 'lib.php';

require_login();

$dato_ricercato  = optional_param('dato_ricercato', '', PARAM_ALPHANUM);
$sort            = optional_param('sort', 'ASC', PARAM_ALPHANUM);
$column   	 = optional_param('column', 'cognome', PARAM_TEXT);
$dir             = optional_param('dir', 'ASC', PARAM_ALPHA);
$page            = optional_param('page', 0, PARAM_INT);
$perpage         = optional_param('perpage', 10, PARAM_INT);        // how many per page
$training	 = optional_param('training', '', PARAM_TEXT);
$mod	         = optional_param('mod', 0, PARAM_INT); //Se abilitata la modifica = 1
$ret	      	 = optional_param('ret',0, PARAM_INT);
$ret_mod	 = optional_param('ret_mod',0, PARAM_INT);
$mdp	         = optional_param('mdp',0, PARAM_INT);
$ret_cp	         = optional_param('ret_cp',0, PARAM_INT);
$chk_selezionate = $_POST['id_course'];//

$label_training = get_lable_training($training);
$url_mod = '';
if ($mod) {
    $url_mod = '&mod=1';
}

$param1 = array('sort' => $sort,
                'dir' => $dir,
                'perpage' => $perpage,
                'training' => $training,
                'dato_ricercato' => $dato_ricercato,
                'mod' => $mod);
//$baseurl = new moodle_url('/blocks/f2_formazione_individuale/gestione_corsi.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage,'training'=>$training,'dato_ricercato'=>$dato_ricercato,'mod'=>$mod));
$baseurl = new moodle_url('/blocks/f2_formazione_individuale/gestione_corsi.php', $param1);
$blockname = get_string('pluginname', 'block_f2_formazione_individuale');
//$context = get_context_instance(CONTEXT_SYSTEM);
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/f2_formazione_individuale/gestione_corsi.php');
$PAGE->requires->js('/blocks/f2_formazione_individuale/js/gestione_corsi.js');
if($mod)
	$PAGE->set_title(get_string('modificacorsi', 'block_f2_formazione_individuale'));
else
	$PAGE->set_title(get_string('gestionecorsi', 'block_f2_formazione_individuale'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string($label_training, 'block_f2_formazione_individuale'));
if($mod){
    $PAGE->navbar->add(get_string('modificacorsi', 'block_f2_formazione_individuale'), $baseurl);
}else{
    $PAGE->navbar->add(get_string('gestionecorsi', 'block_f2_formazione_individuale'), $baseurl);
}
$PAGE->set_heading($SITE->shortname.': '.$blockname);


//require_capability('block/f2_apprendimento:leggistorico', $context);
$capability_giunta = has_capability('block/f2_formazione_individuale:individualigiunta', $context);
$capability_linguagiunta = has_capability('block/f2_formazione_individuale:individualilinguagiunta', $context);
$capability_consiglio = has_capability('block/f2_formazione_individuale:individualiconsiglio', $context);
$param_CIG = get_parametro('p_f2_corsi_individuali_giunta');
$param_CIL = get_parametro('p_f2_corsi_individuali_lingua_giunta');
$param_CIC = get_parametro('p_f2_corsi_individuali_consiglio');

if(!(($capability_giunta && $training == $param_CIG->val_char) || ($capability_linguagiunta && $training == $param_CIL->val_char) || ($capability_consiglio && $training == $param_CIC->val_char))){
	print_error('nopermissions', 'error', '', 'formazione_individuale');
}


$id_del_corso = 0;
if(isset($_POST['id_course'])){
	$id_del_corso = $_POST['id_course'];
}

$elimina = optional_param('elimina', '', PARAM_TEXT);
$blocca_determina =optional_param('blocca_determina', '', PARAM_TEXT);

if(isset($_POST['elimina'])){           //ELIMINA CORSI
	$esito_elimina = 1;
	foreach ($id_del_corso as $id_del){
		$esito_del = delete_corsiind($id_del);
		if(!$esito_del) $esito_elimina = 0;
	}
}

if(isset($_POST['blocca_determina'])){  //BLOCCA DETERMINA PROVVISORIA
	$esito = blocca_determina($id_del_corso);
	
	if($esito){
		redirect(new moodle_url('mod_determina_prov.php?training='.$training));
	}else{
		echo get_string('error_codice_determina_provvisorio', 'block_f2_formazione_individuale');
	}
}


$data = new stdClass;
$data->tipo_corso = $training; //_$GET  MODIFICARE
$data->dato_ricercato = $dato_ricercato; //_$GET  MODIFICARE
$pagination = array('perpage' => $perpage, 'page'=>$page,'column'=>$column,'sort'=>$sort,'mod'=>$mod);
foreach ($pagination as $key=>$value)
{
        $data->$key = $value;
}


$datiall = get_corsiind($data,$mod);
$courses = $datiall->dati;
$total_rows = $datiall->count;

echo $OUTPUT->header();
$str = <<<'EFO'
<script type="text/javascript">
//<![CDATA[
function validateChecked (check) {
	var frm = document.getElementById('course_frm');

	for (var i =0; i < frm.elements.length; i++) {
        frm.elements[i].checked = false;
	}
	check.checked = true;
}
function confirmSubmitElimina(conferma)
{
	var chk = document.getElementsByName("id_course[]");
	var tot = chk.length;	
	var num = 0;
		
	for (i = 0; i < tot; i++) {
		if(chk[i].checked) num++;
	}
		
    if(conferma=='elimina'){
        if(num==1)
            conferma="Stai eliminando un corso. Proseguire?";
        else if(num>1)
            conferma="Stai eliminando più corsi. Proseguire?";
    }
    else if(conferma == 'blocca')
        conferma="Bloccare le attivita selezionate?";

    if(num > 0) { 
        return confirm(conferma);
    }
    else {
        alert("Non e' stato selezionato nessun corso.");
        return false;
    }	
}
function checkAll(from,to)
{
	var i = 0;
	var chk = document.getElementsByName(to);
	var resCheckBtn = document.getElementsByName(from);
	var resCheck = resCheckBtn[i].checked;
	var tot = chk.length;
	for (i = 0; i < tot; i++) chk[i].checked = resCheck;
	
	num_check_checked();
}
//]]>
</script>
EFO;
echo $str;

if($mod)
	echo $OUTPUT->heading(get_string('modificacorsi', 'block_f2_formazione_individuale'));
else
	echo $OUTPUT->heading(get_string('gestionecorsi', 'block_f2_formazione_individuale'));

	if($ret == 1){
		echo "<h3 style='color:green;text-align: center;'>".get_string('insert_correct', 'block_f2_formazione_individuale')."</h3>";
	}else if($ret == -1){
		echo "<h3 style='color:red;text-align: center;'>".get_string('insert_error', 'block_f2_formazione_individuale')."</h3>";
	}
	
	if($ret_mod == 1){
		echo "<h3 style='color:green;text-align: center;'>".get_string('corso_modificato', 'block_f2_formazione_individuale')."</h3>";
	}else if($ret_mod == -1){
		echo "<h3 style='color:red;text-align: center;'>".get_string('errore_modifica_corso', 'block_f2_formazione_individuale')."</h3>";
	}
	
	if($mdp == 1){
		echo "<h3 style='color:green;text-align: center;'>".get_string('cdp_ok', 'block_f2_formazione_individuale')."</h3>";
	}else if($mdp == -1){
		echo "<h3 style='color:red;text-align: center;'>".get_string('cdp_err', 'block_f2_formazione_individuale')."</h3>";
	}	
	
	if($ret_cp == 1){
		echo "<h3 style='color:green;text-align: center;'>".get_string('ret_cp_ok', 'block_f2_formazione_individuale')."</h3>";
	}else if($ret_cp == -1){
		echo "<h3 style='color:red;text-align: center;'>".get_string('ret_cp_err', 'block_f2_formazione_individuale')."</h3>";
	}
	
	if(isset($_POST['elimina'])){
		if($esito_elimina == 1){
			echo "<h3 style='color:green;text-align: center;'>".get_string('canc_ok', 'block_f2_formazione_individuale')."</h3>";
		}else if($esito_elimina == -1){
			echo "<h3 style='color:red;text-align: center;'>".get_string('canc_ok', 'block_f2_formazione_individuale')."</h3>";
		}
		$chk_selezionate = array();
	}
	$usercountblock = return_is_blocked($training);
	
	if($usercountblock && !$mod){
		echo '<div style="padding:5px; background-color: #DDD;width: 95%;">';
		echo '<h2>Attenzione: corsi bloccati</h2>';
		echo 'Non è possibile bloccare alcun corso, perchè sono già presenti corsi bloccati che attendono di ricevere un codice determina provvisorio.<br>';
		echo '<br><a href="'.$CFG->wwwroot.'/blocks/f2_formazione_individuale/mod_determina_prov.php?training='.$training.'"><input type="submit" name="assegna_codice_det_prov" value="Assegna codice determina provvisorio" /></a>';
		echo '<br><br>';
		echo '</div>';
	}

echo html_writer::start_tag('form', array('action' => $baseurl, 'method' => 'post'));
// Submit ricerca
echo '<table><tr>';
echo '<td>Cognome: <input maxlength="254" size="50" name="dato_ricercato" type="text" id="id_dato_ricercato" value="'.$dato_ricercato.'" /></td>';
echo '<td><input name="Cerca" value="Cerca" type="submit" id="id_Cerca" /></td>';
echo '</tr></table>';
echo html_writer::end_tag('form');

// record visualizzati: 
$coursescount = $total_rows;
if ($coursescount == 0) {
    echo $OUTPUT->container(get_string('nessun_corso_trovato','block_f2_formazione_individuale'), 'userinfobox', 'msgnodata');
    $table = NULL;
} else {
	echo '<table style="width:100%;"><tr>';
	echo "<td style='width:80%;'><b style='font-size:11px'>".get_string('count_tot_rows', 'local_f2_traduzioni',$coursescount)."</b></td>";
	if(!$mod)
		echo "<td><b style='font-size:11px'>".get_string('elementi_selezionati', 'local_f2_traduzioni').": <span id='span_elementi_sel'><span></b></td>";
	echo '</tr></table>';
        $columndir = $sort == "ASC" ? "DESC":"ASC";
        $columnicon = $sort == "ASC" ? "down":"up";
        $columnicon = " <img src=\"" . $OUTPUT->pix_url('t/' . $columnicon) . "\" alt=\"\" />";

		$icon="";
		if ($column == "cognome") $icon=$columnicon;
			$column_cognome = "<a href=\"gestione_corsi.php?sort=$columndir&amp;column=cognome&training=".$training.$url_mod."&dato_ricercato=".$dato_ricercato."&page=".$page."&perpage=".$perpage."\">".get_string('cognome', 'block_f2_formazione_individuale')."</a>$icon";
		$icon="";
		if ($column == "nome") $icon=$columnicon;
			$column_nome = "<a href=\"gestione_corsi.php?sort=$columndir&amp;column=nome&training=".$training.$url_mod."&dato_ricercato=".$dato_ricercato."&page=".$page."&perpage=".$perpage."\">".get_string('nome', 'block_f2_formazione_individuale')."</a>$icon";
		//	white-space: nowrap;
    $table = new html_table();
    $table->head = array ();
    $table->align = array();
    $table->size = array();
    $table->wrap = array(null,'nowrap');
    if($mod){
    	$table->head[] = html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/edit'), 'alt'=>get_string('modifica_corso', 'block_f2_formazione_individuale'), 'class'=>'iconsmall'));
    }else{
    	$table->head[] = '<input type=checkbox name="id_course_all" value="" onClick="checkAll(\'id_course_all\',\'id_course[]\');">';
    }
	$table->align[] = 'left';
	$table->size[] = '';
	$table->attributes = array();
    $table->head[] = $column_cognome."/".$column_nome;
    $table->align[] = 'left';
    $table->size[] = '';
    //if(!$mod){ // 2018 04 20 - da commentare quando le modifiche saranno in PROD
	    $table->head[] = get_string('copia', 'block_f2_formazione_individuale');
	    $table->align[] = 'left';
	    $table->size[] = '';
    //} // 2018 04 20 - da commentare quando le modifiche saranno in PROD
    $table->head[] = get_string('matricola', 'block_f2_formazione_individuale');
    $table->align[] = 'left';
    $table->size[] = '';
    $table->head[] = get_string('datainizio', 'block_f2_formazione_individuale');
    $table->align[] = 'center';
    $table->size[] = '';
    $table->head[] = get_string('titolocorso', 'block_f2_formazione_individuale');
    $table->align[] = 'left';
    $table->size[] = '90%';
    $table->head[] = get_string('cartellina', 'block_f2_formazione_individuale');
    $table->align[] = 'left';
    $table->size[] = '';
    
    $table->width = "95%";
    
    $giorni_copia_prefix = get_parametri_by_prefix('p_f2_corsiind_giorni_copia_corso');
    $giorni_copia = $giorni_copia_prefix['p_f2_corsiind_giorni_copia_corso']->val_int;
    
    foreach ($courses as $course) {
		$checked = "";
		foreach ($chk_selezionate as $key => $chk) {
			if($course->id == $chk){
				$checked = "checked='checked'";
				unset($chk_selezionate[$key]);
				break;
			}
		}

        $marticola = get_forzatura_or_moodleuser($course->username);
        $row = array ();
		if($mod){
			$row[]= html_writer::link(new moodle_url('modifica_anagrafica.php', array('id_course'=>$course->id,'training' => $training,'mod' =>$mod,'userid' => $course->userid)),html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/edit'), 'alt'=>get_string('modifica_corso', 'block_f2_formazione_individuale'), 'class'=>'iconsmall')), array('title'=>get_string('modifica_corso', 'block_f2_formazione_individuale')));
			$row[] = $course->cognome." ".$course->nome;
                    // 2018 04 20
                    $id_det_corso = $DB->get_field('f2_corsiind', 'id_determine', array('id' => $course->id));
                    if ($id_det_corso > 0) {
                        $strcopia = get_string('copia', 'block_f2_formazione_individuale');
                        $arparurl = array('id_course'=>$course->id,'training' => $training,'mod' =>$mod);
                        $row[] = html_writer::link(new moodle_url('copy_course_con_determina.php', $arparurl),$strcopia, array('title'=>$strcopia));
                    } else {
                        $row[] = ' - ';
                    }
                    // 2018 04 20
		}else{
        	$row[] = '<input type="checkbox" id="'.$course->id.'" name="id_course[]" '.$checked.' value="'.$course->id.'">';
        	$row[] = $course->cognome." ".$course->nome;
        	
        	if($course->data_inizio >= strtotime('-'.$giorni_copia.' day',time())){
        		$row[]= html_writer::link(new moodle_url('user_copy.php', array('id_course'=>$course->id,'training' => $training)),get_string('copia', 'block_f2_formazione_individuale'), array('title'=>get_string('copia', 'block_f2_formazione_individuale')));
        	}else{
        		$row[]='';
        	}
		}
        $row[] = $marticola->idnumber;
        $row[] = date('d/m/Y',$course->data_inizio);
        $row[] = $course->titolo;
		$row[] = $course->codice_archiviazione;
        
        $table->data[] = $row;
    }
}

		echo '<form id="course_frm" action="gestione_corsi.php?training='.$training.'" method="post">';
		echo '<input type="hidden" value="1" name="del" />';
		
		foreach ($chk_selezionate as $chk) {
			echo '<input type="hidden" id="'.$chk.'" name="id_course[]" value="'.$chk.'">';
		}
		
		
		
	if (!empty($table)) {
		echo $OUTPUT->paging_bar($coursescount, $page, $perpage, $baseurl);
		echo html_writer::table($table);
		echo $OUTPUT->paging_bar($coursescount, $page, $perpage, $baseurl);
	}
	
	if(!$mod){
		echo '<table><tr><td>';//table
			echo '<input type="submit" name="elimina" onClick="return confirmSubmitElimina(\'elimina\');" value="Elimina" />';
		echo '</td><td>';
			echo '<input type="button" value="'.get_string("nuovo", "block_f2_formazione_individuale").'" onclick="parent.location=\'user.php?training='.$training.'\'">';
		echo '</td><td>';
		//Se ci sono corsi bloccati fa la redirect
		
		if(!$usercountblock){
			echo '<input type="submit" name="blocca_determina" onClick="return confirmSubmitElimina(\'blocca\');" value="Blocca determina" />';
		}
		echo '</td></tr></table>';	//table

	}
echo '</form>';



echo $OUTPUT->footer();
