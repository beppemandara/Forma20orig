<?php
    global $PAGE, $site, $OUTPUT,$CFG, $DB;
		
    require_once('../../config.php');
    require_once($CFG->libdir.'/adminlib.php');
    require_once('lib.php');
    require_once($CFG->dirroot.'/f2_lib/management.php');
    require_once('form_corsi_invio_autorizzazioni.php');
    require_once($CFG->dirroot.'/user/profile/lib.php');
    require_once($CFG->dirroot.'/tag/lib.php');
    require_once($CFG->libdir . '/filelib.php');
    require_login();
    
    $search = optional_param('cerca', '', PARAM_ALPHANUM);
    $training = required_param('training', PARAM_TEXT);
  //  $id_determine = required_param('id_determina', PARAM_ALPHANUM);

    if(isset($_POST['submit_invio_autorizzazioni'])){
    	//INSERT
    	$send_autorizzazione = $_POST['chk_id_determina'];
    }
    
    
    if(isset($_POST['id_determina'])){
    	$id_determine = $_POST['id_determina'];
        foreach($id_determine as $determina){
	    	$id_determina = $determina;
	    	break;
        }
    }else if(isset($_POST['codice_determina'])){
    	$id_determina = $_POST['codice_determina'];	
    }else if(isset($_GET['codice_determina'])){
    	$id_determina = $_GET['codice_determina'];	
    }else{
    	$id_determina ="";
    }
    
    
    $label_training = get_lable_training($training);
    $dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
    $page         = optional_param('page', 0, PARAM_INT);
    $perpage      = optional_param('perpage', 20, PARAM_INT);        // how many per page
    $column   	  = optional_param('column', 'codice_provvisorio_determina', PARAM_TEXT);
    
   
    $sitecontext = get_context_instance(CONTEXT_SYSTEM);
    $site = get_site();

    $blockname = get_string('pluginname', 'block_f2_formazione_individuale');

    $url = new moodle_url("{$CFG->wwwroot}/blocks/f2_formazione_individuale/corsi_invio_autorizzazioni.php?training=".$training);
    $gestionecorsi_url = new moodle_url("{$CFG->wwwroot}/blocks/f2_formazione_individuale/gestione_corsi.php?training=".$training);
    $PAGE->set_url($url);
    $PAGE->set_context($sitecontext);
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title(get_string('selezionautente', 'block_f2_formazione_individuale'));
    $PAGE->settingsnav;
    $PAGE->navbar->add(get_string('formazione_individuale', 'block_f2_formazione_individuale'));
    $PAGE->navbar->add(get_string($label_training, 'block_f2_formazione_individuale'));
    $PAGE->navbar->add(get_string('gestionecorsi', 'block_f2_formazione_individuale'), $gestionecorsi_url);
    $PAGE->navbar->add(get_string('invio_autorizzazioni', 'block_f2_formazione_individuale'));
    
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
    		
function checkAll(from,to)
{
	var i = 0;
	var chk = document.getElementsByName(to);
	var resCheckBtn = document.getElementsByName(from);
	var resCheck = resCheckBtn[i].checked;
	var tot = chk.length;
	for (i = 0; i < tot; i++) chk[i].checked = resCheck;
}
  
function confirmSubmit()
{
var proto = document.getElementById("no_protocollo");
   
    		if(proto && proto.value == 1){
    	alert("Non può essere inviata la mail.\nNumero protocollo o data protocollo non presenti");	
    		return false ;
		}else
    		{
    		
    		
    		
var chk = document.getElementsByName("chk_id_determina[]");
var tot = chk.length;
var num = 0;
for (i = 0; i < tot; i++) {
	if(chk[i].checked)
	 num++;
}

if(num <= 0)
{
	alert("Non e' stato selezionato nessun corso.");
	return false;
}
}
		}
//]]>
</script>
EFO;
    echo $str;
    
    echo $OUTPUT->header();
    $context = context_system::instance();

    echo $OUTPUT->heading(get_string('invio_autorizzazioni', 'block_f2_formazione_individuale'));

    $baseurl = new moodle_url('/blocks/f2_formazione_individuale/corsi_invio_autorizzazioni.php', array('training' => $training,'codice_determina' => $id_determina,'cerca'=>$search));
    
    
    $data = new stdClass;
    $data->tipo_corso = $training; //_$GET  MODIFICARE
    $data->cerca_determina = $search; //_$GET  MODIFICARE
    $data->id_determina = $id_determina;
    $pagination = array('perpage' => $perpage, 'page'=>$page,'column'=>$column,'sort'=>$dir);
    foreach ($pagination as $key=>$value)
    {
    	$data->$key = $value;
    }
  
    

    	$data->id_determina = $id_determina;
    	//$data->cerca = $search;
    	$data->cerca_determina = $search;
    	$data->invio_mail = 1;
	    $dati_table = get_corsi_determina_by_id($data);
	    $dati = $dati_table->dati;
	    $total_rows = $dati_table->count;
  
    flush();
    
    echo '<table width="100%" style="margin-bottom:-55px;margin-top:-30px"><tr><td style="text-align:right"> <input  align=\'right\' type=\'button\' height=\'55\' title=\'Dettaglio invio mail\' value=\'Dettaglio invio mail\' onclick=\'pop_up()\'/></td></tr></table>';
    
 //   $form = new corsi_autorizzazione_determina_form('corsi_invio_autorizzazioni.php?training='.$training,compact('search','id_determina'));
 //   $form->display();
    
    echo html_writer::start_tag('form', array('action' => $baseurl, 'method' => 'post'));
    // Submit ricerca
    echo '<br><table><tr>';
    echo '<td >'.get_string('cognome', 'block_f2_formazione_individuale').': <input maxlength="254" name="cerca" type="text" id="id_cerca" value="'.$search.'" /></td>';
    echo '<td><input name="search" value="Cerca" type="submit" id="id_search" /></td>';
    echo '</tr></table>';
    echo html_writer::end_tag('form');
		
        $table = new html_table();
        $table->head[] = "<input type='checkbox' name='chk_id_determine' onclick='checkAll(\"chk_id_determine\",\"chk_id_determina[]\");' />";
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
        $table->head[] = get_string('data_invio_mail', 'block_f2_formazione_individuale');
        $table->align[] = 'left';
        
        $table->width = "100%";
        
        $result_pop_up = "<table><table width='100%'><tr><td align=left  valign=top  class='clsBold' ><b>Utente</b></td><td align=left  valign=top  class='clsBold' ><b>E-mail</b></td><td align=left  valign=top  class='clsBold' ><b>Data invio</b></td></tr>";
        foreach ($dati as $value_dati) {
        	$marticola = get_forzatura_or_moodleuser($value_dati->username);
        	$data_invio_mail="";
        	if($value_dati->data_invio_mail){
        		$data_invio_mail = date('d/m/Y H:i',$value_dati->data_invio_mail);
        		
        		$dati_function = new stdClass();
        		$dati_function->data_invio_mail = $value_dati->data_invio_mail;
        		$dati_function->id_utente = $value_dati->id_utente;
        		

        		$dati_pop_up = get_dati_pop_up_dettaglio_mail($dati_function);
        		
        		$result_pop_up .= "<tr><td>".$value_dati->lastname." ".$value_dati->firstname."</td><td>".$dati_pop_up->mailto."</td><td>".$data_invio_mail."</td></tr>";
        		
        	//	print_r($result_pop_up);exit;
        	}
	            $row = array ();
	            $row[] = "<input type='checkbox' name='chk_id_determina[]' value='".$value_dati->id_course."'/>";
	            $row[] = $value_dati->lastname." ".$value_dati->firstname;
	            $row[] = $marticola->idnumber;
	            $row[] = date('d/m/Y',$value_dati->data_inizio);
	            $row[] = $value_dati->titolo;
	            $row[] = $value_dati->codice_archiviazione;
	            $row[] = $data_invio_mail;
	            $table->data[] = $row;
	            
	            if(!$value_dati->numero_protocollo || !$value_dati->data_protocollo){	            	
	            	echo '<input type="hidden" id="no_protocollo" name="no_protocollo" value="1"/>';
	            }
        }
        
        $result_pop_up .="</table>";
        echo '<input type="hidden" name="dati_invio" id="dati_invio" value="'.$result_pop_up.'"/>';
        
  //      print_r($result_pop_up);exit;
        $str1 = '
<script type=\'text/javascript\'>
        

function pop_up(){
myWindow=window.open(\'\',\'\',\'width=1000,height=600,scrollbars=yes\');
        
var dati_invio = document.getElementById(\'dati_invio\').value;
myWindow.document.write(\'<h3>Dettaglio invio email di autorizzazione</h3><table width="100%"><tr><td align="right"><input  align="right" type="button" height="55" title="Stampa questa pagina" value="Stampa" onclick="window.print()"/></td></tr></table>\');
        
myWindow.document.write(dati_invio);
        
        
myWindow.focus();
}
</script>
';
        echo $str1;
        
        echo "<b>Numero totale di record:".$total_rows."</b><br>";
        echo get_string('label_mail_autorizzazione','block_f2_formazione_individuale');
        echo $OUTPUT->paging_bar($total_rows, $page, $perpage, $baseurl);
        
        $gestionecorsi_back = new moodle_url("{$CFG->wwwroot}/blocks/f2_formazione_individuale/invio_autorizzazioni.php?training=".$training);
        echo '<form name="table_corsi_autorizzazioni" id="table_corsi_autorizzazioni" action="pdf_autorizzazioni.php" method="post">';
        echo '<input type="hidden" name="training" value="'.$training.'">';
            echo html_writer::table($table);
        echo '<input type="submit" name="submit_invio_autorizzazioni" onClick="return confirmSubmit()" value="'.get_string('invio_autorizzazioni', 'block_f2_formazione_individuale').'" />';
        echo ' <input type="button" value="'.get_string("indietro", "block_f2_formazione_individuale").'" onclick="parent.location=\''.$gestionecorsi_back.'\'">';
		echo '</form>';
    
    echo $OUTPUT->footer();



