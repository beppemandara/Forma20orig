<?php
	// $Id$
    global $PAGE, $site, $OUTPUT,$CFG, $DB;

    require_once('../../config.php');
    require_once 'lib.php';
    require_once($CFG->libdir.'/adminlib.php');
    require_once('filters/lib.php');
    require_once($CFG->dirroot.'/f2_lib/management.php');
    
    require_once($CFG->dirroot.'/user/profile/lib.php');
    require_once($CFG->dirroot.'/tag/lib.php');
    require_once($CFG->libdir . '/filelib.php');
    require_login();

    $sort         = optional_param('sort', 'name', PARAM_ALPHANUM);
    $dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
    $page         = optional_param('page', 0, PARAM_INT);
    $perpage      = optional_param('perpage', 10, PARAM_INT);        // how many per page
    $training	  = required_param('training', PARAM_TEXT);
    $label_training = get_lable_training($training);
    
    $sitecontext = get_context_instance(CONTEXT_SYSTEM);
    $site = get_site();

    $blockname = get_string('pluginname', 'block_f2_formazione_individuale');

    $url = new moodle_url("{$CFG->wwwroot}/blocks/f2_formazione_individuale/mod_determina_prov.php?training=".$training);
    $gestionecorsi_url = new moodle_url("{$CFG->wwwroot}/blocks/f2_formazione_individuale/gestione_corsi.php?training=".$training);
    $PAGE->set_url($url);
    $PAGE->set_context($sitecontext);
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title(get_string('determina_provvisoria', 'block_f2_formazione_individuale'));
    $PAGE->settingsnav;
    $PAGE->navbar->add(get_string('formazione_individuale', 'block_f2_formazione_individuale'));
    $PAGE->navbar->add(get_string($label_training, 'block_f2_formazione_individuale'), $gestionecorsi_url);
    $PAGE->navbar->add(get_string('codice_determina_provvisorio', 'block_f2_formazione_individuale'));
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
    
    echo $OUTPUT->header();
    $context = context_system::instance();
    
    $str = <<<'EFO'
<script type="text/javascript">
//<![CDATA[
    
   
function confirmSubmit(conferma)
{
		if(conferma=='annulla')
			conferma="Attenzione annullando l'operazione verra rimosso il blocco per i corsi selezionati.\nProseguire?";
    		
   		var agree=confirm(conferma);
			if (agree)
				return true ;
			else
				return false ;
}
//]]>
</script>
EFO;
    echo $str;
    
    
    
    
    
    $extracolumns = get_extra_user_fields($context);
    $columns = array_merge(array('firstname', 'lastname'), $extracolumns);

    foreach ($columns as $column) {
        $string[$column] = get_user_field_name($column);
        if ($sort != $column) {
            $columnicon = "";
            $columndir = "ASC";
        } else {
            $columndir = $dir == "ASC" ? "DESC":"ASC";
            $columnicon = $dir == "ASC" ? "down":"up";
            $columnicon = " <img src=\"" . $OUTPUT->pix_url('t/' . $columnicon) . "\" alt=\"\" />";

        }
        $$column = "<a href=\"mod_determina_prov.php?sort=$column&amp;dir=".$columndir."&training=".$training."\">".$string[$column]."</a>$columnicon";
    }
    
    if ($sort == "name") {
        $sort = "firstname";
    }

    // create the user filter form
    $ufiltering = new my_users_filtering(null,"mod_determina_prov.php?training=".$training);
    list($extrasql, $params) = $ufiltering->get_sql_filter();
    
    $extrasql = "ci.training = '$training'";

    $courses = $ufiltering->get_corsiind_provvisorio_determina_blocked($sort, $dir, $page*$perpage, $perpage, '', '', '', $extrasql, $params);
    $usercount = $ufiltering->get_count_corsiind_provvisorio_determina_blocked($sort, $dir, 0, 0, '', '', '', $extrasql, $params);

    echo $OUTPUT->heading(get_string('codice_determina_provvisorio', 'block_f2_formazione_individuale'));
//    $strall = get_string('all');

    $baseurl = new moodle_url('/blocks/f2_formazione_individuale/mod_determina_prov.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage,'training' => $training));
    

    flush();


    if (!$courses) {
        $match = array();
        echo $OUTPUT->heading(get_string('nousersfound'));
        $table = NULL;

    } else {

        $override = new stdClass();
        $override->firstname = 'firstname';
        $override->lastname = 'lastname';
        $fullnamelanguage = get_string('fullnamedisplay', '', $override);
        if (($CFG->fullnamedisplay == 'firstname lastname') or
            ($CFG->fullnamedisplay == 'firstname') or
            ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'firstname lastname' )) {
            $fullnamedisplay = "$firstname / $lastname";
        } else { // ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'lastname firstname')
            $fullnamedisplay = "$lastname / $firstname";
        }

        $table = new html_table();
        $table->head = array ();
        $table->align = array();
        $table->head[] = $fullnamedisplay;
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
        foreach ($courses as $course) {
        	$user = $DB->get_record('user', array('id'=>$course->userid));
            if (isguestuser($user)) {
                continue; // do not display guest here
            }

            $marticola = get_forzatura_or_moodleuser($user->username);
           // print_r($marticola);exit;
            $fullname = fullname($course, true);
            //print_r($fullname);exit;
            $row = array ();
            $row[] = $fullname;

     		   $row[] = $marticola->idnumber;
		        $row[] = date('d/m/Y',$course->data_inizio);
		        $row[] = $course->titolo;
				$row[] = $course->codice_archiviazione;
            $table->data[] = $row;
        }
    }
    
    if(isset($_POST['salva_codice_provvisorio_determina'])){
    	$dati				= new stdClass;
    	$dati->codice_provvisorio_determina		= $_POST['codice_provvisorio_determina'];
    	$dati->note		= $_POST['note'];

    	if($_POST['codice_provvisorio_determina']==''){
    		echo '<b style="color:red">'.get_string('codice_prov_vuoto','block_f2_formazione_individuale').'</b>';
    		echo '<script type="text/javascript">alert(\''.get_string('alert_codice_prov_vuoto','block_f2_formazione_individuale').'\'); </script>';
    	}
    	else if(if_exist_codice_provvisorio_determina($_POST['codice_provvisorio_determina'],$training)){	
    		echo '<b style="color:red">'.get_string('codice_prov_in_uso','block_f2_formazione_individuale').'</b>';    
    		echo '<script type="text/javascript">alert(\''.get_string('alert_codice_prov_in_uso','block_f2_formazione_individuale').'\'); </script>';	
    	}
    	else{
            $allcourses = $ufiltering->get_corsiind_provvisorio_determina_blocked($sort, $dir, 0, 0, '', '', '', $extrasql, $params);
	    	if(insert_codice_provvisorio_determina($dati, $allcourses)){
	    		redirect(new moodle_url('gestione_corsi.php?training='.$training.'&mdp=1'));
	    		//echo '<b style="color:green">Dati inseriti correttamente!</b>';
	    	}else{
	    		redirect(new moodle_url('gestione_corsi.php?training='.$training.'&mdp=-1'));
	    	}
    	}
    }
    
    if(isset($_POST['annulla_codice_provvisorio_determina'])){

    
    	if(annulla_codice_provvisorio_determina($training)){
    		redirect(new moodle_url('gestione_corsi.php?training='.$training));
    		//echo 'Dati annullati correttamente!';
    	}
    }
    
    if (!empty($table)) {	
    	
    	echo'<form name="input" action="mod_determina_prov.php?training='.$training.'" method="POST">
    		<table>
    			<tr>
    				<td>
			 			'.get_string('codice_provvisorio_determina','block_f2_formazione_individuale').'
			 		</td><td>
			 			 <input type="text" size="50" name="codice_provvisorio_determina" maxlength="50" value="'.$_POST['codice_provvisorio_determina'].'">
			 		</td>
			 	</tr><tr>
			 		<td>
			 			'.get_string('note','block_f2_formazione_individuale').'
			 		</td><td>
			 			<textarea rows="4" cols="50" name="note">'.$_POST['note'].'</textarea>
			 		</td>
			 	</tr>
			 </table>
			<input type="submit" name="salva_codice_provvisorio_determina" value="Salva">
			 <input type="reset"  value="Pulisci">
			<input type="submit" name="annulla_codice_provvisorio_determina" onClick="return confirmSubmit(\'annulla\')"; value="Indietro">
		</form>';
    	
    	echo "<br>";
    //	echo $OUTPUT->heading("$usercount ".get_string('users'));
    	echo "".$usercount." ".get_string("corsi_assegnati_determina", "block_f2_formazione_individuale")."";
    	// add filters
    	//$ufiltering->display_add();
    	//$ufiltering->display_active();
    	echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);
        echo html_writer::table($table);
        echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);
    }

//    echo '<input type="button" value="'.get_string("indietro", "block_f2_formazione_individuale").'" onclick="parent.location=\''.$gestionecorsi_url.'\'">';
    
    echo $OUTPUT->footer();



