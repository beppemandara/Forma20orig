<?php
//$Id$
global $OUTPUT, $PAGE, $SITE;

require_once '../../config.php';
require_once 'lib.php';

require_login();
$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('block/f2_apprendimento:leggistorico', $context);

$dato_ricercato         = optional_param('dato_ricercato', '', PARAM_ALPHANUM);
$sort                   = optional_param('sort', 'ASC', PARAM_ALPHANUM);
$column   				= optional_param('column', 'cognome', PARAM_TEXT);
$dir                    = optional_param('dir', 'ASC', PARAM_ALPHA);
$page                   = optional_param('page', 0, PARAM_INT);
$perpage                = optional_param('perpage', 20, PARAM_INT);        // how many per page
$training				= optional_param('training', '', PARAM_TEXT);
$esito_archiviazione    = optional_param('arc', -1, PARAM_INT);
$label_training = get_lable_training($training);

$baseurl = new moodle_url('/blocks/f2_formazione_individuale/archiviazione_storico.php?&training='.$training.$url_mod.'&dato_ricercato='.$dato_ricercato);
$blockname = get_string('pluginname', 'block_f2_formazione_individuale');
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/f2_formazione_individuale/archiviazione_storico.php');
$PAGE->set_title(get_string('archiviazioneinstorico', 'block_f2_formazione_individuale'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string($label_training, 'block_f2_formazione_individuale'));
$PAGE->navbar->add(get_string('gestione_storico_corsi', 'block_f2_formazione_individuale'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);

$param_CIG = get_parametro('p_f2_corsi_individuali_giunta');
$param_CIL = get_parametro('p_f2_corsi_individuali_lingua_giunta');
$param_CIC = get_parametro('p_f2_corsi_individuali_consiglio');
$capability_giunta = has_capability('block/f2_formazione_individuale:individualigiunta', $context);
$capability_linguagiunta = has_capability('block/f2_formazione_individuale:individualilinguagiunta', $context);
$capability_consiglio = has_capability('block/f2_formazione_individuale:individualiconsiglio', $context);

if(!(($capability_giunta && $training == $param_CIG->val_char) || ($capability_linguagiunta && $training == $param_CIL->val_char) || ($capability_consiglio && $training == $param_CIC->val_char))){
	print_error('nopermissions', 'error', '', 'formazione_individuale');
}

$data = new stdClass;
$data->tipo_corso = $training; //_$GET  MODIFICARE
$data->dato_ricercato = $dato_ricercato; //_$GET  MODIFICARE
$pagination = array('perpage' => $perpage, 'page'=>$page,'column'=>$column,'sort'=>$sort);
foreach ($pagination as $key=>$value)
{
        $data->$key = $value;
}

$data->archiviato_storico = 0;
$datiall = get_all_codici_determina_definitiva($data);
$courses = $datiall->dati;
$total_rows = $datiall->count;

$str = <<<'EFO'
<script type="text/javascript">
//<![CDATA[
		
function validateChecked (check) {
	var form_del= document.getElementById('form_del');

	for (var i =0; i < form_del.elements.length; i++) 
	{
	 form_del.elements[i].checked = false;
	}
	check.checked = true;
}

function confirmSubmit(conferma)
{
		
		if(conferma=='archivia')
			conferma="Stai archiviando un corso. Proseguire?";
	
var chk = document.getElementsByName("id_course");
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
	alert("Non e' stato selezionato nessun corso.");
	return false;
}
		
}
//]]>
</script>
EFO;
echo $str;




echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('archiviazioneinstorico', 'block_f2_formazione_individuale'));

echo html_writer::start_tag('form', array('action' => $baseurl, 'method' => 'post'));
// Submit ricerca
echo '<table><tr>';
echo '<td>Cognome: <input maxlength="254"name="dato_ricercato" type="text" id="id_dato_ricercato" value="'.$dato_ricercato.'" /></td>';
echo '<td><input name="Cerca" value="Cerca" type="submit" id="id_Cerca" /></td>';
echo '</tr></table>';
echo html_writer::end_tag('form');


if($esito_archiviazione == 1){
	echo '<h3 style="color:green;text-align: center;">'.get_string('corso_archiviato', 'block_f2_formazione_individuale').'</h3>';
}else if ($esito_archiviazione == 0){
	echo '<h3 style="color:red;text-align: center;">'.get_string('errore_archiviazione', 'block_f2_formazione_individuale').'</h3>';
}


// record visualizzati: 
$coursescount = $total_rows;
echo "<b style='font-size:11px'>".get_string('count_tot_rows', 'local_f2_traduzioni',$coursescount)."</b>";

$baseurl = new moodle_url('/blocks/f2_formazione_individuale/archiviazione_storico.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage,'training'=>$training,'dato_ricercato'=>$dato_ricercato));



if ($coursescount == 0) {
    echo $OUTPUT->heading(get_string('nocoursesfound','block_f2_formazione_individuale'));
    $table = NULL;
} else {

        $columndir = $sort == "ASC" ? "DESC":"ASC";
        $columnicon = $sort == "ASC" ? "down":"up";
        $columnicon = " <img src=\"" . $OUTPUT->pix_url('t/' . $columnicon) . "\" alt=\"\" />";

		$icon="";
		if ($column == "cognome") $icon=$columnicon;
			$column_cognome = "<a href=\"archiviazione_storico.php?sort=$columndir&amp;column=cognome&training=".$training."\">".get_string('cognome', 'block_f2_formazione_individuale')."</a>$icon";
		$icon="";
		if ($column == "nome") $icon=$columnicon;
			$column_nome = "<a href=\"archiviazione_storico.php?sort=$columndir&amp;column=nome&training=".$training."\">".get_string('nome', 'block_f2_formazione_individuale')."</a>$icon";
					
    $table = new html_table();
    $table->head = array ();
    $table->align = array();

    $table->head[] = '';
	$table->align[] = 'left';
    $table->head[] = $column_cognome."/".$column_nome;
    $table->align[] = 'left';
    $table->head[] = get_string('matricola', 'block_f2_formazione_individuale');
    $table->align[] = 'left';
    $table->head[] = get_string('datainizio', 'block_f2_formazione_individuale');
    $table->align[] = 'left';
    $table->head[] = get_string('titolocorso', 'block_f2_formazione_individuale');
    $table->align[] = 'left';
    $table->head[] = get_string('codicearchiviazione', 'block_f2_formazione_individuale');
    $table->align[] = 'left';
    $table->head[] = get_string('ente', 'block_f2_formazione_individuale');
    $table->align[] = 'left';
    $table->head[] = get_string('numero_determina', 'block_f2_formazione_individuale');
    $table->align[] = 'left';
    $table->head[] = get_string('data_determina', 'block_f2_formazione_individuale');
    $table->align[] = 'left';
    
    $table->width = "95%";
    
    $giorni_copia_prefix = get_parametri_by_prefix('p_f2_corsiind_giorni_copia_corso');
    $giorni_copia = $giorni_copia_prefix['p_f2_corsiind_giorni_copia_corso']->val_int;
    
    foreach ($courses as $course) {
	$marticola = get_forzatura_or_moodleuser($course->username);
        $row = array ();
        $row[] = '<input type=radio id='.$course->id.' name="id_course" value='.$course->id.'>';
        $row[] = $course->cognome." ".$course->nome;
        $row[] = $marticola->idnumber;
        $row[] = date('d/m/Y',$course->data_inizio);
        $row[] = $course->titolo;
		$row[] = $course->codice_archiviazione;
		$row[] = $course->ente;
		$row[] = $course->codice_determina;
		$row[] = date('d/m/Y',$course->data_determina);
        
        $table->data[] = $row;
    }
}

echo '<form id="form_del" action="archivia_corso.php?training='.$training.'" method="post">';
	if (!empty($table)) {
		echo $OUTPUT->paging_bar($coursescount, $page, $perpage, $baseurl);
		echo html_writer::table($table);
		echo $OUTPUT->paging_bar($coursescount, $page, $perpage, $baseurl);
	}
	
	//	echo '<table><tr><td>';//table
	//		echo '<input type="reset" value="'.get_string("annulla", "block_f2_formazione_individuale").'" />';
	//	echo '</td><td>';
			echo '<input type="submit" value="'.get_string("archivia", "block_f2_formazione_individuale").'"  onClick="return confirmSubmit(\'archivia\')";>';
	//	echo '</td></tr></table>';	//table

echo '</form>';

echo $OUTPUT->footer();