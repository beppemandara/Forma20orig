<?php

//$Id: curricula_dipendenti.php 1066 2013-03-08 16:23:40Z d.lallo $
global $CFG, $USER, $COURSE, $DB, $OUTPUT;

require_once '../../config.php';
require_once 'lib.php';
// require_once($CFG->dirroot.'/f2_lib/core.php');
// require_once($CFG->dirroot.'/f2_lib/management.php');
require_once($CFG->dirroot.'/f2_lib/report.php');

require_login();
//require_capability('block/f2_apprendimento:viewdipendenticurricula', get_context_instance(CONTEXT_SYSTEM));

$blockid = get_block_id(get_string('pluginname_db','block_f2_apprendimento'));
//$context = get_context_instance(CONTEXT_BLOCK,$blockid);
$context = context_block::instance($blockid);
require_capability('block/f2_apprendimento:viewdipendenticurricula', $context);

// $page         = optional_param('page', 0, PARAM_INT);
// $perpage      = optional_param('perpage', 10, PARAM_INT); 
// $search_name  = optional_param('search_name', '', PARAM_TEXT);

$page     = optional_param('page', 0, PARAM_INT);
$perpage  = optional_param('perpage', 10, PARAM_INT);
$column   = optional_param('column', 'lastname', PARAM_TEXT);
$sort     = optional_param('sort', 'ASC', PARAM_TEXT);

$baseurl = new moodle_url('/blocks/f2_apprendimento/curricula_dipendenti.php');

$blockname = get_string('pluginname', 'block_f2_apprendimento');

//$context = get_context_instance(CONTEXT_SYSTEM);
$context = context_system::instance();
$PAGE->set_context($context);

// $PAGE->set_pagelayout('admin');
$PAGE->set_pagelayout('standard');
$PAGE->set_course($COURSE);
$PAGE->set_url('/blocks/f2_apprendimento/curricula.php');
$PAGE->set_title(get_string('curriculum_dip', 'block_f2_apprendimento'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('curriculum_dip', 'block_f2_apprendimento'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);

echo $OUTPUT->header();

$currenttab = 'curricula_dip';
require('tabs_curriculum_utenti.php');

echo $OUTPUT->heading(get_string('curriculum_dip', 'block_f2_apprendimento'));
echo $OUTPUT->box_start();

// include($CFG->dirroot.'/f2_lib/ricerca_dipendenti/ricerca_dipendenti.php');

print_form_dipendenti('curricula.php', array(),$page,$perpage,$column,$sort);

// //INIZIO Form
// 	class libretto_form extends moodleform {
// 		public function definition() {
// 			$mform =& $this->_form;
// 	        $post_values = $this->_customdata['post_values'];
// 	        if (isset($post_values) and (!is_null($post_values)) and (!empty($post_values)))
// 	        {
// 	      		$post_values = json_encode($post_values);
// 		        $mform2->addElement('hidden', 'post_values',$post_values);
// 	        }
// 			$mform->addElement('text', 'search_name','Cognome', 'maxlength="254" size="50"');
// 			$mform->addElement('submit', 'submitbtn', 'Ricerca');
// 			}
// 	}
// 	$mform = new libretto_form(null);
// 	$mform->display();
// //FINE Form


// 	$data = $mform->get_data();
// // 	print_r($data);
// 	$pagination = array('perpage' => $perpage, 'page'=>$page,'column'=>$column,'sort'=>$sort);
// 	foreach ($pagination as $key=>$value)
// 	{
// 		$data->$key = $value;
// 	}
// 	$form_id='mform1';										// ID del form dove fare il submit
// 	$post_extra=array('column'=>$column,'sort'=>$sort);
// 	// $full_dipendenti restituisce tutti i dipendenti
// 	$full_dipendenti = get_visible_users_by_userid(NULL, $data, TRUE);
// 	//$full_dipendenti = get_dipendenti($USER->id,$data);
// 	$dipendenti = $full_dipendenti->dati;
// 	$total_rows = $full_dipendenti->count;
	


// // TABELLA DIPENDENTI
// 	$table = new html_table();
// 	$table->width = '80%';
// 	$head_table = array('lastname','firstname','visualizza');
// 	$head_table_sort = array('lastname');
// 	$align = array ('center','center','center');
// 	$table->align = $align;
// 	$table->head = build_head_table($head_table,$head_table_sort,$post_extra,$total_rows, $page, $perpage, $form_id);
	
// 	foreach ($dipendenti as $c){
// 			$table->data[] = array(
// 							$c->lastname,
// 							$c->firstname,
// 							"<a href='curricula.php?userid=".$c->id."'>".get_string('visualizza','local_f2_traduzioni')."</a>"
// 							);
// 			}

// 	// INIZIO TABELLA DIPENDENTI
// 	echo "<b style='font-size:11px'>Totale dipendenti: $total_rows</b>";
// 	$paging_bar = new paging_bar_f2($total_rows, $page, $perpage, $form_id, $post_extra);
// 	echo $paging_bar->print_paging_bar_f2();
// 	echo html_writer::table($table);
// 	echo $paging_bar->print_paging_bar_f2();	
// 	///FINE TABELLA DIPENDENTI

		
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
