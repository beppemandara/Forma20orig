<?php
// $Id: anagrafica_fornitori.php 1164 2013-06-07 10:32:03Z d.lallo $
global $CFG,$DB,$OUTPUT;

require_once '../../../config.php';
require_once $CFG->libdir . '/formslib.php';
require_once($CFG->dirroot.'/blocks/f2_gestione_risorse/lib.php');
require_once($CFG->dirroot.'/f2_lib/report.php');

require_once($CFG->libdir.'/adminlib.php');

/*
 * AK-DL pagination: intestazioni necessarie per l'impaginazione e ordinamento
*/
$page     = optional_param('page', 0, PARAM_INT);
$perpage  = optional_param('perpage', 10, PARAM_INT);
$column   = optional_param('column', 'denominazione', PARAM_TEXT);
$sort     = optional_param('sort', 'ASC', PARAM_TEXT);

require_login();

$context = get_context_instance(CONTEXT_SYSTEM);

$capability = has_capability('block/f2_gestione_risorse:add_fornitori', $context);
if(!$capability){
	print_error('nopermissions', 'error', '', 'fornitori');
}

if (empty($CFG->loginhttps)) {
	$securewwwroot = $CFG->wwwroot;
} else {
	$securewwwroot = str_replace('http:','https:',$CFG->wwwroot);
}

$PAGE->set_context($context);
$baseurl = new moodle_url('/blocks/f2_gestione_risorse/fornitori/anagrafica_fornitori.php', NULL);
$PAGE->set_url($baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
$PAGE->set_title(get_string('fornitori', 'block_f2_gestione_risorse'));
$PAGE->navbar->add(get_string('fornitori', 'block_f2_gestione_risorse'), new moodle_url($baseurl));
$PAGE->set_pagelayout('standard');
$PAGE->settingsnav;

include_fileDownload_before_header();

echo $OUTPUT->header();
/*
 * AK-DL pagination: intestazioni necessarie per includere javascript
*/
include_fileDownload_after_header();

echo $OUTPUT->heading(get_string('fornitori', 'block_f2_gestione_risorse'));

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
//]]>

function confirmSubmitElimina()
{
	var chk = document.getElementsByName("id_forn[]");
	var tot = chk.length;
	var num = 0;
	for (i = 0; i < tot; i++) {
		if(chk[i].checked)
		 num++;
	}

	if(num > 0)
	{ 
		var agree=confirm("Stai eliminando dei fornitori. Proseguire?");
		if (agree)
			return true ;
		else
			return false ;
	}
	else
	{
		alert("Non e' stato selezionato nessun fornitore.");
		return false;
	}
}
</script>
EFO;

echo $str;

echo $OUTPUT->box_start();
echo '<div class="contenitoreglobale">';

//INIZIO FORM
class anagrafica_fornitori_form extends moodleform {
	public function definition() {
		$mform =& $this->_form;
		$mform->addElement('text', 'denominazione',get_string('denominazione','local_f2_traduzioni'), 'maxlength="254" size="50"');
		$mform->addElement('submit', 'Cerca', 'Cerca');
	}
}
$mform = new anagrafica_fornitori_form(null);
$mform->display();
//FINE FORM

// intestazioni tabella fornitori
$head_table = array('chk','edit','denominazione','citta','provincia','cognome','nome','email','telefono','tipo_formazione');
$head_table_sort = array('denominazione','citta','provincia');
$align = array ('center','center','center','center','center','center','center','center','center','center');
	
if ($mform->is_submitted()) {
	$data = $mform->get_data();
} else {
	$data = new stdClass();
	$data->denominazione = '';
	$data->page = $page;
	$data->perpage = $perpage;
	$data->sort = $sort;
	$data->column = $column;
}

//	if ($data = $mform->get_data()) {	
		$pagination = array('perpage' => $perpage, 'page'=>$page,'column'=>$column,'sort'=>$sort);
		foreach ($pagination as $key=>$value){
			$data->$key = $value;
		}

		$full_fornitori=get_fornitori($data);
		if($full_fornitori->count==0){
			echo $OUTPUT->box_start();
			echo '<BR>';
			echo '<form action="delete_fornitori.php" method="post">';
			echo '<a href="add_fornitori.php"><button type="button">Nuovo</button></a>';	
			echo '<BR><BR>';
			echo '<b>Non &egrave; presente nessun fornitore.</b><br>Seleziona il pulsante "Nuovo" per inserire un nuovo Fornitore.';
			echo $OUTPUT->box_end();
		}
		else{
	 		$form_id='mform1';										// ID del form dove fare il submit
			$post_extra=array('column'=>$column,'sort'=>$sort);		// dati extra da aggiungere al post del form
			$total_rows = $full_fornitori->count;
			$fornitori = $full_fornitori->dati;
			
			// INIZIO TABELLA FORNITORI
			$table = new html_table();
			$table->align = $align;
			$table->head = build_head_table($head_table,$head_table_sort,$post_extra,$total_rows, $page, $perpage, $form_id);
			foreach ($fornitori as $fornitore) {
				$table->data[] = array(
						'<input type=checkbox name="id_forn[]" value='.$fornitore->id.'>',
						html_writer::link(new moodle_url('edit_fornitori.php', array('id'=>$fornitore->id
						)), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/edit'), 'alt'=>get_string('edit_fornitore', 'block_f2_gestione_risorse'), 'class'=>'iconsmall')), array('title'=>get_string('edit_fornitore', 'block_f2_gestione_risorse'))),
						'<a href = detail_fornitori.php?id='.$fornitore->id.'>'.$fornitore->denominazione.'</a>',
						$fornitore->citta,
						$fornitore->provincia,
						$fornitore->cognome,
						$fornitore->nome,
						$fornitore->email,
						$fornitore->telefono,
						get_tipo_formazione_fornitore($fornitore->tipo_formazione)
						);
				}

				class report_excel_formazione extends moodleform {
					public function definition() {
						global $CFG;
						$mform2 		=& $this->_form;
						$post_values = $this->_customdata['post_values'];
						$post_values = json_encode($post_values);
				
						$mform2->addElement('hidden', 'post_values',$post_values);

						//		$buttonarray=array();
						//		$buttonarray[] = &$mform2->createElement('submit', 'submitbutton', 'EXPORT EXCEL');
						//		$mform2->addGroup($buttonarray, 'buttonar2', '', array(' '), false);
						//		$mform2->closeHeaderBefore('buttonar2');
						$mform2->addElement('html',html_writer::empty_tag('input', array('type' => 'submit', 'class' => 'ico_xls btn', 'value' => get_string('export_excel_fornitori', 'block_f2_gestione_risorse'))));
						//$mform2->addElement('html',html_writer::tag('label', ' '.get_string('export_excel_fornitori_lbl', 'block_f2_gestione_risorse')));
					}
				}
				
				$mform_excel = new report_excel_formazione('excel_anagrafica_fornitori.php',array('post_values'=>$data),NULL,NULL,array('class'=>'export_excel'));
				$mform_excel->display();		

				echo "<p>".get_string('count_tot_rows', 'local_f2_traduzioni',$total_rows)."</p>";
                                //echo "<p>page: ".$page." - perpage: ".$perpage."</p>";
				$paging_bar = new paging_bar_f2($total_rows, $page, $perpage, $form_id, $post_extra);
				echo $paging_bar->print_paging_bar_f2();

				echo '<form action="delete_fornitori.php" method="post">';
				echo '<table><tr><td>';//table
				echo '<input type="submit" onClick="return confirmSubmitElimina()"; value="Elimina" />';
				echo '</td><td>';//table
				echo '<a href="add_fornitori.php"><button type="button">Nuovo</button></a>';
				echo '</td></tr></table>';	//table
					
				echo html_writer::table($table);

				echo '<table><tr><td>';//table
				echo '<input type="submit" onClick="return confirmSubmitElimina()"; value="Elimina" />';
				echo '</td><td>';//table	
				echo '<a href="add_fornitori.php"><button type="button">Nuovo</button></a>';
				echo '</td></tr></table>';
				echo '</form>';//table

				echo $paging_bar->print_paging_bar_f2();		
		}
//	}

echo '</div>';	
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
?>
