<?php
//$Id: report_prenotazioni.php 1241 2013-12-20 04:34:05Z l.moretto $
global $CFG,$USER,$COURSE,$DB;

require_once '../../config.php';
require_once $CFG->libdir . '/formslib.php';
require_once 'lib.php';
// $PAGE->requires->js('/f2_lib/jquery/jquery-1.7.1.min.js');
// $PAGE->requires->js('/f2_lib/jquery/jquery-ui.min.js');
// $PAGE->requires->css('/f2_lib/jquery/css/jquery-ui-1.8.18.custom.css');
// $PAGE->requires->js('/f2_lib/jquery/jquery.fileDownload.js');
// $PAGE->requires->js('/f2_lib/jquery/reports.js');

require_login();
$blockid = get_block_id(get_string('pluginname_db','block_f2_prenotazioni'));
require_capability('block/f2_prenotazioni:viewprenotazioni', get_context_instance(CONTEXT_BLOCK, $blockid));

$anno_formativo       = optional_param('anno', 0, PARAM_INT);
$userid       = optional_param('userid', 0, PARAM_INT);
$prenota_altri     = optional_param('pa', 0, PARAM_INT);
$page     = optional_param('page', 0, PARAM_INT);
$perpage  = optional_param('perpage', 10, PARAM_INT);
$column   = optional_param('column', 'codice', PARAM_TEXT);
$sort     = optional_param('sort', 'ASC', PARAM_TEXT);

if($userid==0)
	$userid=intval($USER->id);
else if($userid!=0 && has_capability('block/f2_prenotazioni:viewprenotazioni', get_context_instance(CONTEXT_BLOCK, $blockid)) && validate_own_dipendente($userid))
	$userid=$userid;
else
	die();

if($anno_formativo==0)
	$anno_formativo=get_anno_formativo_corrente();

if ($prenota_altri == 1)
{
	if (prenotazioni_direzione_aperte() || isSupervisore($USER->id))
	{
		$baseurl = new moodle_url('/blocks/f2_prenotazioni/prenota_altri.php?userid='.$userid.'&pa='.$prenota_altri);
		$PAGE->navbar->add(get_string('prenota_altri', 'block_f2_prenotazioni'), $baseurl);
		$baseurl = new moodle_url('/blocks/f2_prenotazioni/prenotazioni.php?userid='.$userid.'&pa='.$prenota_altri);
		$PAGE->navbar->add(get_string('prenotazioni', 'block_f2_prenotazioni'), $baseurl);
	}
	else
	{
// 		redirect(new moodle_url('/'));
	} 
}
else //prenotazioni dip
{
	if (prenotazioni_dip_aperte() || isSupervisore($USER->id))
	{
		$baseurl = new moodle_url('/blocks/f2_prenotazioni/prenotazioni.php?userid='.$userid.'&pa='.$prenota_altri);
		$PAGE->navbar->add(get_string('prenotazioni', 'block_f2_prenotazioni'), $baseurl);
	}
	else 
	{
		$baseurl = new moodle_url('/blocks/f2_prenotazioni/report_prenotazioni.php');
		$PAGE->navbar->add(get_string('tab_report_prenotazioni', 'block_f2_prenotazioni'), $baseurl);
// 		redirect(new moodle_url('/'));
	}
}

$blockname = get_string('pluginname', 'block_f2_prenotazioni');
//$context = get_context_instance(CONTEXT_SYSTEM);
$context = context_system::instance();
$PAGE->set_pagelayout('standard');
$PAGE->set_context($context);
$PAGE->set_url('/blocks/f2_prenotazioni/prenotazioni.php');
$PAGE->set_title(get_string('prenotazioni', 'block_f2_prenotazioni'));
$PAGE->settingsnav;
// $PAGE->navbar->add(get_string('prenotazioni', 'block_f2_prenotazioni'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);

$userdata = get_user_data($userid);
$settore = get_user_organisation($userid);
$settore_id = $settore[0];
$objsettore = get_organisation_info_by_id($settore_id);
$settore_nome = is_null($objsettore->fullname) ? 'n.d.' : "$objsettore->shortname - $objsettore->fullname";
$objdirezione = get_organisation_info_by_id($objsettore->parentid);
$direzione_nome = is_null($objdirezione->fullname) ? 'n.d.' : $objdirezione->shortname." - ".$objdirezione->fullname;
$user_cohort = get_user_cohort_by_category($userid);

// TABELLA DATI ANAGRAFICI
$table = new html_table();
$table->align = array('right', 'left');
$table->data = array(
		array('Cognome Nome ','<b>'.$userdata->lastname.' '.$userdata->firstname.'</b>'),
		array('Matricola',''.$userdata->idnumber.''),
		array('Categoria',''.$userdata->category.''),
		array('Direzione / Ente',''.$direzione_nome.''),
		array('Settore',''.$settore_nome.'')
);

// include_fileDownload_before_header();
echo $OUTPUT->header();
// include_fileDownload_after_header();
echo $OUTPUT->heading(get_string('prenotazioni', 'block_f2_prenotazioni'));
echo $OUTPUT->box_start();

//INIZIO FORM VUOTO
class empty_form extends moodleform {
	public function definition() {
		$mform =& $this->_form;
	}
}
$mform = new empty_form(null);
$mform->display();
//FINE FORM VUOTO

print_tab_prenotazioni('report_prenotazioni',$userid,$prenota_altri);
// INIZIO TABELLA DATI ANAGRAFICI
echo "<h3>".get_string('sommario_prenotazioni', 'block_f2_prenotazioni')."</h3>";
echo html_writer::table($table);
// FINE TABELLA DATI ANAGRAFICI

$data = new stdClass;
$pagination = array('perpage' => $perpage, 'page'=>$page,'column'=>$column,'sort'=>$sort,'userid'=>$userid,'cohorts' => $user_cohort->cohortid);
foreach ($pagination as $key=>$value)
{
	$data->$key = $value;
}
$form_id='mform1';										// ID del form dove fare il submit
$post_extra=array('column'=>$column,'sort'=>$sort,'userid'=>$userid, 'pa' => $prenota_altri);
$cohort_str =$user_cohort->cohortid;
$data->cohorts = $cohort_str;
$data->anno_formativo_corrente = get_anno_formativo_corrente();
$full_user_prenotazioni = get_user_prenotazioni($data);
$user_prenotazioni = $full_user_prenotazioni->dati;
$total_rows = $full_user_prenotazioni->count;

if($total_rows > 0) {
	// TABELLA MYCOURSES
	$table = new html_table();
	$table->width = '100%';
	$head_table = array('codice','titolo','segmento_formativo','sede_corso','stato','data');
	$head_table_sort = array('codice');
	$align = array ('left','left','center','center','center','center');

	$table->align = $align;
	$table->head = build_head_table($head_table,$head_table_sort,$post_extra,$total_rows, $page, $perpage, $form_id);
	foreach ($user_prenotazioni as $c)
	{
		$vals = $c->validatos;
		$vald = $c->validatod;
		$stato_str = '';
		if (($vals !== '-1') and ($vald !== '-1'))
		{
			$stato_str = get_stato_prenotazione_str($vals,$vald,$c->orgid,0,$c->prenotazione_id);
		}
		$pdf_url = new moodle_url("/local/f2_course/pdf/select_contratto_formativo.php?courseid=".$c->courseid);
		$titolo_str = "<a onclick=\"window.open('".$pdf_url."', '', 'width=620,height=450,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes'); return false;\" href=\"".$pdf_url."\">$c->titolo</a>";
		$table->data[] = array(
				$c->codice,
				$titolo_str,
// 				$c->titolo,
				$c->segmento_formativo,
				$c->sede_prenotazione,
				$stato_str,
				date('d/m/Y',$c->data_prenotazione)
		);
	}

	//INIZIO TABELLA BOTTONI
	$btn_row = array();
	$btn_row[] = '<input type="button" value="'.get_string('print', 'block_f2_prenotazioni').'" onClick="window.print()">';
	$btn_table = '<table align="left" width="10%"><tr>';
	$buttemp='';
	foreach ($btn_row as $b)
	{
		$buttemp = $buttemp.'<td>'.$b.'</td>';
	}
	$btn_table = $btn_table.$buttemp.'</tr></table>';
	echo $btn_table;
	//FINE TABELLA BOTTONI
	
	class report_excel_formazione extends moodleform {
		public function definition() {
			/*
			$mform2 		=& $this->_form;
			$post_values = $this->_customdata['post_values'];
			$post_values = json_encode($post_values);
			$mform2->addElement('hidden', 'post_values',$post_values);
			$buttonarray=array();
			$buttonarray[] = &$mform2->createElement('submit', 'submitbutton', get_string('report_excel', 'local_f2_traduzioni'));
			$mform2->addGroup($buttonarray, 'buttonar2', '', array(' '), false);
			$mform2->closeHeaderBefore('buttonar2');
			*/
			global $CFG;
			$mform2 		=& $this->_form;
			$post_values = $this->_customdata['post_values'];
			$post_values = json_encode($post_values);
			$mform2->addElement('hidden', 'post_values',$post_values);
			$start_icona_export =  html_writer::start_tag('form', array('action' => 'vostrofile.php', 'class' => 'export_excel', 'method' => 'post'));
			//$img_icona = html_writer::empty_tag('input', array('type' => 'image', 'src' => $CFG->wwwroot.'/blocks/f2_apprendimento/pix/excel_icon1.png', 'height' => '35', 'alt' => 'Esporta','title' => get_string('export_excel_prenotaz_lbl', 'block_f2_gestione_risorse')));
			//$lbl_icona = html_writer::tag('label', ' '.get_string('export_excel_prenotaz_lbl', 'block_f2_gestione_risorse'));
			$mform2->addElement('html',html_writer::empty_tag('input', array('type' => 'submit', 'class' => 'ico_xls btn', 'value' => get_string('export_excel_prenotaz_lbl', 'block_f2_gestione_risorse'))));
			$end_icona_export = html_writer::end_tag('form');
			$mform2->addElement('html',$start_icona_export.$img_icona.$lbl_icona.$end_icona_export);
		}
	}
	
	$mform_excel = new report_excel_formazione('report_prenotazioni_excel.php',array('post_values'=>$data),NULL,NULL,array('class'=>'export_excel'));
	$mform_excel->display();
	
	echo "<p>Totale corsi $total_rows</p>";
	$paging_bar = new paging_bar_f2($total_rows, $page, $perpage,$form_id, $post_extra);
	echo $paging_bar->print_paging_bar_f2();
	echo html_writer::table($table);
	echo $paging_bar->print_paging_bar_f2();
}
else //empty
{
	echo '<br/><br/><p>'.get_string('noresults', 'block_f2_prenotazioni').'</p><br/>';
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
