<?php

//$Id: curricula.php 1241 2013-12-20 04:34:05Z l.moretto $
global $CFG,$USER,$COURSE,$DB;

require_once '../../config.php';
require_once 'lib.php';
require_once($CFG->dirroot.'/f2_lib/core.php');
require_once($CFG->dirroot.'/f2_lib/management.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
$PAGE->requires->js('/f2_lib/jquery/jquery-1.7.1.min.js');
$PAGE->requires->js('/f2_lib/jquery/jquery-ui.min.js');
$PAGE->requires->css('/f2_lib/jquery/css/jquery-ui-1.8.18.custom.css');
$PAGE->requires->js('/f2_lib/jquery/jquery.fileDownload.js');
$PAGE->requires->js('/f2_lib/jquery/reports.js');
require_once($CFG->dirroot.'/f2_lib/report.php');

//require_capability('block/f2_apprendimento:viewcurricula', get_context_instance(CONTEXT_SYSTEM));
//require_capability('block/f2_apprendimento:viewcurricula',get_context_instance(CONTEXT_COURSE, 1));
require_capability('block/f2_apprendimento:viewcurricula',context_course::instance(1));

// $page         = optional_param('page', 0, PARAM_INT);
// $perpage      = optional_param('perpage', 20, PARAM_INT); 
$userid       = optional_param('userid', 0, PARAM_INT); 
$c_exp_type   = optional_param('c_exp_type', 0, PARAM_INT); 
// $direction   = optional_param('dir', 'DESC', PARAM_ACTION); 
// $sort  		 = optional_param('sort', 'data_inizio', PARAM_ACTION); 

$page     = optional_param('page', 0, PARAM_INT);
$perpage  = optional_param('perpage', 10, PARAM_INT);
$column   = optional_param('column', 'data_inizio', PARAM_TEXT);
$sort     = optional_param('sort', 'DESC', PARAM_TEXT);

$target_user = ($userid == 0 ? intval($USER->id) : $userid);
//print_r($target_user);die();
$b_viewingdipcv = ($target_user != $USER->id);
//print_r($b_viewowncv);die();
$b_canviewdipcv = ( 
		//has_capability('block/f2_apprendimento:viewdipendenticurricula', get_context_instance(CONTEXT_SYSTEM)) 
		//has_capability('block/f2_apprendimento:viewdipendenticurricula', get_context_instance(CONTEXT_COURSE,1))
		has_capability('block/f2_apprendimento:viewdipendenticurricula', context_course::instance(1))
		&& validate_own_dipendente($target_user)
);
//print_r($b_chk);die();
if( $b_viewingdipcv && !$b_canviewdipcv ) 
	print_error('noviewdipendenticurricula','block_f2_apprendimento');

$baseurl = new moodle_url('/blocks/f2_apprendimento/curricula.php?userid='.$target_user);

$blockname = get_string('pluginname', 'block_f2_apprendimento');

// $PAGE->set_pagelayout('admin');
$PAGE->set_pagelayout('standard');
$PAGE->set_course($COURSE);
$PAGE->set_url('/blocks/f2_apprendimento/curricula.php');
$PAGE->set_title(get_string('curriculum', 'block_f2_apprendimento'));
$PAGE->settingsnav;

if ($target_user != intval($USER->id))
{
	$navbar_curr_dipendenti = new moodle_url('/blocks/f2_apprendimento/curricula_dipendenti.php');
	$PAGE->navbar->add(get_string('curriculum_dip', 'block_f2_apprendimento'), $navbar_curr_dipendenti);
}

$PAGE->navbar->add(get_string('curriculum', 'block_f2_apprendimento'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);

//INIZIO Form
	class libretto_form extends moodleform {
		public function definition() {
		    $mform =& $this->_form;
//         	$post_values = $this->_customdata['post_values'];
//         	if (isset($post_values) and (!is_null($post_values)) and (!empty($post_values)))
//      	    {
//       			$post_values = json_encode($post_values);
// 	        	$mform2->addElement('hidden', 'post_values',$post_values);
//       		}
			$c_exp_type	= $this->_customdata['c_exp_type'];
			$userid	= $this->_customdata['userid'];
			$sort	= $this->_customdata['sort'];
			$column	= $this->_customdata['column'];
			$mform->addElement('hidden', 'userid', $userid);
                        $mform->setType('userid', PARAM_INT);
			$mform->addElement('hidden', 'sort', $sort);
                        $mform->setType('sort', PARAM_RAW);
			$mform->addElement('hidden', 'column', $column);
                        $mform->setType('column', PARAM_RAW);
			$options = array(
				'0' => 'Tutti i corsi a cui l\'utente ha partecipato',
				'1' => 'Corsi Validati',
				'2' => 'Corsi da Validare',
				'3' => 'Corsi Attivi',
			);
			$attrs = array('onchange' => "submit()");
			$mform->addElement('select', 'c_exp_type','Scegli una tipologia', $options, $attrs);
			$mform->setDefault('c_exp_type', $c_exp_type);
			}
	}
	$form = new libretto_form(null,array('c_exp_type'=>$c_exp_type,'userid'=>$target_user,'column'=>$column,'sort'=>$sort));
	//FINE Form	

// 	print_r($data);exit;
$dati_user = get_user_data($target_user);
$user_custom_filed = profile_user_record($target_user);
$direzione = get_direzione_utente($target_user);
$settore = get_settore_utente($target_user);

// INIZIO TABELLA DATI ANAGRAFICI
$table = new html_table();
$table->align = array('right', 'left');
$table->data = array(
                    array('Cognome Nome: ','<b>'.fullname($dati_user).'</b>'),
                    array('Matricola',''.$dati_user->idnumber.''),
                    array('Categoria',''.$user_custom_filed->category),
                    array('Direzione',''.(is_null($direzione) ? '' : $direzione['shortname']." - ".$direzione['name'])),
                    array('Settore',''.(is_null($settore) ? '' : $settore['shortname']." - ".$settore['name']))
            );

// $full_mycourses restituisce i corsi dell'utente 
// $full_mycourses = user_history_courses($userid,array('perpage' => $perpage, 'page'=>$page));	

echo $OUTPUT->header();

$currenttab = 'curricula';
require('tabs_curriculum.php');

echo $OUTPUT->heading(get_string('curriculum', 'block_f2_apprendimento'));

echo $OUTPUT->box_start();
// INIZIO TABELLA DATI ANAGRAFICI
// echo "<b style='font-size:11px'>".get_string('curriculum', 'block_f2_apprendimento')."</b>";
echo "<h3>".get_string('dettagli_utente', 'block_f2_apprendimento')."</h3>";

echo html_writer::table($table);
// FINE TABELLA DATI ANAGRAFICI

$form->display();
$data = $form->get_data();
	
$pagination = array('perpage' => $perpage, 'page'=>$page,'column'=>$column,'sort'=>$sort,'c_exp_type' => $c_exp_type,'userid'=>$target_user);
if (!isset($data)) { $data = new stdClass(); }
foreach ($pagination as $key=>$value)
{
	$data->$key = $value;
}
$form_id='mform1';										// ID del form dove fare il submit
$post_extra=array('column'=>$column,'sort'=>$sort,'c_exp_type'=>$c_exp_type,'userid'=>$target_user);
// print_r($data);
$full_mycourses = user_history_courses($data);
$mycourses = $full_mycourses->dati;
$total_rows = $full_mycourses->count;
	
if($total_rows > 0) {
	// TABELLA MYCOURSES
	$table = new html_table();
	$table->width = '100%';
	$head_table = array('ente','codice','nome','data_inizio','sf','cf','cfv','presenza','va');
	$head_table_sort = array('data_inizio');
	$align = array ('center','center','center','center','center','center','center','center','center');
	
	$table->align = $align;
	$table->head = build_head_table($head_table,$head_table_sort,$post_extra,$total_rows, $page, $perpage, $form_id);
	
	foreach ($mycourses as $c){
			$table->data[] = array(
							$c->ente,
							$c->codice,
							$c->nome."<br/>".$c->descrpart,
							// date('d/m/Y H:i:s',$c->start),
							date('d/m/Y',$c->start),
							$c->sf,
							$c->cf,
							$c->cfv,
							$c->presenza,
							$c->va
							);
			}
		
		//INIZIO TABELLA BOTTONI
		$btn_row = array();
// 		$btn_row[] = "<input type=\"button\" onClick=\"document.location.href='export_courses.php?userid=".$userid."&c_exp_type=".$c_exp_type."&dir=".$direction."&sort=".$sort."'\" value =\"".get_string('getexcel', 'block_f2_apprendimento')."\">";
		//$btn_row[] = '<div align="right"><input type="button" value="'.get_string('print', 'block_f2_apprendimento').'" onClick="window.print()"></div>';
		// echo "<a class='fileDownload' href='export_courses.php?userid=".$userid."&c_exp_type=".$c_exp_type."&dir=".$direction."&sort=".$sort."'>download</a>";
		
		// echo  '<p><input type="button" value="'.get_string('print', 'block_f2_apprendimento').'" onClick="window.print()"></p>';
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
				global $CFG;
				$mform2 		=& $this->_form;
				$post_values = $this->_customdata['post_values'];
				$post_values = json_encode($post_values);
				$mform2->addElement('hidden', 'post_values',$post_values);
                                $mform2->setType('post_values', PARAM_RAW);
				$start_icona_export =  html_writer::start_tag('form', array('action' => 'vostrofile.php', 'class' => 'export_excel', 'method' => 'post'));
				$img_icona = html_writer::empty_tag('input', array('type' => 'button', 'class' => 'ico_xls btn', 'value' => get_string('export_excel_cv_lbl', 'block_f2_apprendimento'), 'onClick' => 'javascript:this.form.submit();'));
				//$lbl_icona = html_writer::tag('label', ' '.get_string('export_excel_cv_lbl', 'block_f2_apprendimento'));
				$end_icona_export = html_writer::end_tag('form');
				$mform2->addElement('html',$start_icona_export.$img_icona.$end_icona_export);
			}
		}
		echo '<table width="100%"><tr><td>';
		$mform_excel = new report_excel_formazione('export_courses.php',array('post_values'=>$data),NULL,NULL,array('class'=>'export_excel'));
		$mform_excel->display();
		echo '</td><td>';
		echo '<div align="right"><input type="button" value="'.get_string('print', 'block_f2_apprendimento').'" onClick="window.print()"></div>';
		echo '</td></tr></table>';

		// INIZIO TABELLA MYCOURSES
		echo "<p>Totale corsi $total_rows</p>";
		$paging_bar = new paging_bar_f2($total_rows, $page, $perpage, $form_id, $post_extra);
		echo $paging_bar->print_paging_bar_f2();	
		echo html_writer::table($table);
		echo $paging_bar->print_paging_bar_f2();
	///FINE TABELLA MYCOURSES
}
else //empty
{
	// echo $OUTPUT->header();
	// echo $OUTPUT->heading(get_string('curriculum', 'block_f2_apprendimento'));
	// echo $OUTPUT->box_start();
	echo '<br/><br/>';
	echo heading_msg(get_string('noresults', 'block_f2_apprendimento'));
	echo '<br/>';
}

if ($b_viewingdipcv)
	echo "<p style='text-align:center'><a href='".$CFG->wwwroot."/blocks/f2_apprendimento/curricula_dipendenti.php'>Torna indietro</a></p>";

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
