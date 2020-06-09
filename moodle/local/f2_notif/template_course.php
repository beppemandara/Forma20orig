<?php

// $id$
require_once('../../config.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/local/f2_course/extends_course.php');
require_once($CFG->dirroot.'/local/f2_support/lib.php');
require_once($CFG->dirroot.'/f2_lib/core.php');
require_once($CFG->dirroot.'/f2_lib/report.php');
require_once($CFG->dirroot.'/local/f2_notif/lib.php');
// require_once('lib.php');

$courseid = required_param('courseid', PARAM_INT);       		  // course id

/*
 * AK-DL pagination: intestazioni necessarie per l'impaginazione e ordinamento
*/
$page     = optional_param('page', 0, PARAM_INT);
$perpage  = optional_param('perpage', 10, PARAM_INT);
$column   = optional_param('column', 'title', PARAM_TEXT);
$sort     = optional_param('sort', 'ASC', PARAM_TEXT);
$canale     = optional_param('canale', 0, PARAM_INT);
$id_tipo_notif     = optional_param('id_tipo_notif', -1, PARAM_INT);

require_login();

$context = get_context_instance(CONTEXT_SYSTEM);

if (empty($CFG->loginhttps)) {
	$securewwwroot = $CFG->wwwroot;
} else {
	$securewwwroot = str_replace('http:','https:',$CFG->wwwroot);
}

//$PAGE->set_context($context);

// basic access control checks
if ($courseid) { // editing course
    if ($courseid == SITEID){
        // don't allow editing of  'site course' using this from
        print_error('cannoteditsiteform');
    }

    $course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
    require_login($course);
    $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
    require_capability('moodle/course:update', $coursecontext);
  //  if($DB->record_exists('f2_anagrafica_corsi', array('courseid'=>$courseid)))
  //  	$anag_course = $DB->get_record('f2_anagrafica_corsi', array('courseid'=>$courseid), '*', MUST_EXIST);
  // 	else
  // 		$anag_course=NULL;

} else {
    require_login();
    print_error('Per poter continuare devi compilare la scheda corso.');
}

$fullname = $course->fullname;
$baseurl = new moodle_url('/local/f2_notif/template_course.php', array('courseid'=>$course->id));
$PAGE->set_url($baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
$PAGE->set_title(get_string('modelli_notifica', 'local_f2_notif'));
//$PAGE->navbar->add('Corsi');
//$PAGE->navbar->add($fullname,new moodle_url($CFG->wwwroot.'/course/view.php', array('id'=>$course->id)));
//$PAGE->navbar->add(get_string('modelli_notifica', 'local_f2_notif'), new moodle_url($baseurl, array('courseid'=>$course->id)));
$PAGE->navbar->add(get_string('modelli_notifica', 'local_f2_notif'));
$PAGE->set_pagelayout('standard');
$PAGE->settingsnav;

echo $OUTPUT->header();

// AK-GL creo oggetto per estendere il cosro e stampo i tab
$test = new extends_f2_course($courseid);
$test->print_tab_edit_course('notif_sistema');

echo $OUTPUT->heading(get_string('modelli_notifica', 'local_f2_notif'));

$str = <<<'EFO'
<script type="text/javascript">
//<![CDATA[

function show()
{
	var div_notif = document.getElementById('template_notif');
	div_notif.removeAttribute('hidden');
		
	var button_aggiungi = document.getElementById('button_aggiungi');
	button_aggiungi.setAttribute('hidden','hidden');
		
	var button_chiudi = document.getElementById('button_chiudi');
	button_chiudi.removeAttribute('hidden');
}

function hide(){
	var div_notif = document.getElementById('template_notif');
	div_notif.setAttribute('hidden','hidden');
		
	var button_chiudi = document.getElementById('button_chiudi');
	button_chiudi.setAttribute('hidden','hidden');
		
	var button_aggiungi = document.getElementById('button_aggiungi');
	button_aggiungi.removeAttribute('hidden');
}

function checkAll(from,to)
{
	var i = 0;
	var chk = document.getElementsByName(to);
	var resCheckBtn = document.getElementsByName(from);
	var resCheck = resCheckBtn[i].checked;
	var tot = chk.length;
	for (i = 0; i < tot; i++) chk[i].checked = resCheck;
}

function confirmSubmitElimina()
{
var chk = document.getElementsByName("id_temp_course[]");
var tot = chk.length;
var num = 0;
for (i = 0; i < tot; i++) {
	if(chk[i].checked)
	 num++;
}

if(num > 0)
	{ 
		var agree=confirm("Stai eliminando dei template. Proseguire?");
			if (agree)
				return true ;
			else
				return false ;
	}
else
{
	alert("Non e' stato selezionato nessun template.");
	return false;
}
		
}

function confirmSubmitSalva()
{
var chk = document.getElementsByName("id_temp[]");
var tot = chk.length;
var tipo_templ = "";
var num = 0;
for (i = 0; i < tot; i++) {
	if(chk[i].checked){
		tipo_templ = chk[i].getAttribute('tipo_templ');
		num++;
	}
}


var notif_course = document.getElementsByName("tipo_notif_course_js[]");
var num_templ_course = notif_course.length;
var exist=0;
for (j = 0; j < num_templ_course; j++) {

		if(notif_course[j].value == tipo_templ)
		exist++;
	}



if(num > 0 && exist > 0)
	{ 
		var agree=confirm("E' gia' presente una notifica di tipo "+tipo_templ+" in questo corso.\nSe si decide di proseguire verra' sostituita.\nProseguire?");
			if (agree)
				return true ;
			else
				return false ;
	}else if(num > 0)
	{ 
return true;
	}
else
{
	alert("Non e' stata selezionata nessuna notifica.");
	return false;
}
}

//]]>
</script>
EFO;

echo $str;

echo $OUTPUT->box_start();
echo '<div class="contenitoreglobale" >';

if($_POST)
	$div_aperto=0;
else
	$div_aperto=1;
$string_div_template_notif = "";
if($div_aperto)
	$string_div_template_notif='<div id="template_notif" hidden="hidden">';
	else
	$string_div_template_notif='<div id="template_notif">';




echo '<input type="button" id="button_aggiungi" name="Nuovo" value="Aggiungi" onClick="show();"/>';
echo '<input type="button" id="button_chiudi" name="Annulla" value="Chiudi" onClick="hide();" hidden="hidden"/>';
//echo '<div id="template_notif" hidden="hidden">';
echo $string_div_template_notif;

echo '<h3>'.get_string('modelli_notifica', 'local_f2_notif').'</h3>';

//INIZIO FORM
class modello_notifica_form extends moodleform {
	public function definition() {
		
		$mform =& $this->_form;
		$courseid       = $this->_customdata['courseid'];
		$template_course    = $this->_customdata['template_course'];
		$tipo_notif = get_tipo_notif();//ricavo id-nome del tipo di notifica
		$array_tipo_notif = array('-1'=>'Tutti');
		foreach($tipo_notif as $notifica){
			$array_tipo_notif[$notifica->id] = ''.$notifica->nome.'';
		}
		
		$mform->addElement('text', 'nome',get_string('nome','local_f2_traduzioni'), 'maxlength="254" size="50"');
	    $select = $mform->addElement('select', 'id_tipo_notif', get_string('tipo', 'local_f2_notif'),$array_tipo_notif);
		//$select = $mform->addElement('select', 'stato', get_string('stato', 'local_f2_notif'),array('1'=>'Attivo','0'=>'Non attivo'));
		$select = $mform->addElement('select', 'canale', get_string('canale', 'local_f2_notif'),array('0'=>'Aula','1'=>'On-Line'));
		$mform->addElement('submit', 'Cerca', 'Cerca');
	}
}
$mform = new modello_notifica_form('template_course.php?courseid='.$courseid);
$mform->display();
//FINE FORM

// intestazioni tabella notifica

	$head_table = array('sel','title','description','tipo_notif','canale');
	$head_table_sort = array('title');
	$align = array ('center','center','center','center');
	
	//if ($data = $mform->get_data()) {		
	$data = $mform->get_data(); 		
		$pagination = array('perpage' => $perpage, 'page'=>$page,'column'=>$column,'sort'=>$sort,'stato'=>1,'id_tipo_notif'=>$id_tipo_notif,'canale'=>$canale);
		foreach ($pagination as $key=>$value){
			$data->$key = $value;
		}
		
		$not_in = "AND ntemp.id NOT IN (
										select 
											nc.id_notif_templates 
										from 
											{f2_notif_corso} nc
										WHERE
											nc.id_corso = ".$courseid." AND
											nc.id_edizione is null
										) AND ntemp.id_tipo_notif <> 3";
		
		$full_templates=get_templates($data,$not_in);
		if($full_templates->count==0){
			echo $OUTPUT->box_start();

						echo '<b>'.get_string('no_notifica', 'local_f2_traduzioni').'</b>';

			echo $OUTPUT->box_end();
		}
//		else{
	 		$form_id='mform1';										// ID del form dove fare il submit
			$post_extra=array('column'=>$column,'sort'=>$sort);		// dati extra da aggiungere al post del form
			$total_rows = $full_templates->count;
			$templates = $full_templates->dati;
			
			// INIZIO TABELLA TEMPLATE
			$table = new html_table();
			$table->align = $align;
			$table->head = build_head_table($head_table,$head_table_sort,$post_extra,$total_rows, $page, $perpage, $form_id);
			foreach ($templates as $template) {
			//print_r($template->id_tipo_notif);exit;
				foreach(get_tipo_notif($template->id_tipo_notif) as $nome){$tipo_notif = $nome->nome; break;};
				$table->data[] = array(
						'<input type=radio name="id_temp[]" tipo_templ='.$tipo_notif.' value='.$template->id.'>',
					    $template->title,
						$template->description,
						$tipo_notif,
						($template->canale ? get_string('on_line','local_f2_notif'): get_string('aula','local_f2_notif'))
						);
			}
					
				
			echo "<b style='font-size:11px'>".get_string('count_tot_rows', 'local_f2_traduzioni',$total_rows)."</b>";
			$paging_bar = new paging_bar_f2($total_rows, $page, $perpage, $form_id, $post_extra);
			
			echo $paging_bar->print_paging_bar_f2();

			echo '<form action="add_template_course.php?courseid='.$courseid.'" method="post">';
				echo '<table></td><td>';//table
					echo '<input type="submit" value="Salva" onClick="return confirmSubmitSalva();" />';
				echo '</td></tr></table>';	//table					
				echo '<input type="hidden" value='.$courseid.' name="courseid"/>';
						echo html_writer::table($table);	
			echo '</form>';//table				
			
			echo $paging_bar->print_paging_bar_f2();	

		echo '<br><br>';			
echo '</div>';		


echo '<div id="template_notif_course">';

echo '<h3>'.get_string('modelli_notifica', 'local_f2_notif').' corso</h3>';
//TABELLA FONDO PAGINA			
$templates_course = get_template_course($courseid);

if($templates_course){

	$table_notif_course = new html_table();
	$table_notif_course->head = array(get_string('chk_templ_course','local_f2_traduzioni'),get_string('title','local_f2_traduzioni'),get_string('description','local_f2_traduzioni'),get_string('tipo_notif','local_f2_traduzioni'),get_string('canale','local_f2_traduzioni'));
	$align = array ('center','center','center','center');

	

	foreach($templates_course as $template_course){

			$data_template=get_template($template_course->id_notif_templates);
					
			foreach(get_tipo_notif($data_template->id_tipo_notif) as $nome){$tipo_notif_course = $nome->nome; break;};//Ricavo il nome della modifica dall'id
		
			if($data_template->stato == -1) {
				$style="style=\"color:red\"";
				$eliminato="(Template eliminato)";
			}
			else 			{
				$style="";
				$eliminato="";
			}

			$table_notif_course->data[] = array(
							'<input type=checkbox name="id_temp_course[]" value='.$template_course->id.'>',
							'<span '.$style.'>'.$data_template->title.'<span>',
							'<span '.$style.'>'.$data_template->description.'<span>',
							'<input type="hidden" name="tipo_notif_course_js[]" value='.$tipo_notif_course.'><span '.$style.'>'.$tipo_notif_course.' '.$eliminato.'<span>',
							'<span '.$style.'>'.($data_template->canale ? get_string('on_line','local_f2_notif'): get_string('aula','local_f2_notif')).'<span>'
									);

	}
	
	echo '<form action="delete_template_course.php" method="post">';
		echo '<input type="hidden" name="id_corsoid" id="id_corsoid" value='.$courseid.'>';
		echo '<input type="submit" onClick="return confirmSubmitElimina()"; value="Elimina" />';
			echo html_writer::table($table_notif_course);
		echo '</form>';
}else{
		echo '<b>Non è presente nessun template per questo corso.</b><br>';
	//	echo 'Seleziona il pulsante "Nuovo" per assegnare un template a questo corso.<br><br>';
		
}
echo '</div>';
echo '</div>';	
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
?>