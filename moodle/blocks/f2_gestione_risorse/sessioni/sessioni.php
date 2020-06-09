<?php

//$Id$
// global $CFG,$USER,$COURSE,$DB;

require_once '../../../config.php';
require_once 'sessioni_form.php';
// require_once($CFG->dirroot.'/f2_lib/core.php');
// require_once($CFG->dirroot.'/f2_lib/management.php');
// $PAGE->requires->js('/f2_lib/jquery/jquery-1.7.1.min.js');
// $PAGE->requires->js('/f2_lib/jquery/jquery-ui.min.js');
// $PAGE->requires->css('/f2_lib/jquery/css/jquery-ui-1.8.18.custom.css');
// $PAGE->requires->js('/f2_lib/jquery/jquery.fileDownload.js');
// $PAGE->requires->js('/f2_lib/jquery/reports.js');

// old sumtotal: TBL_EML_STATI_FUNZ
$context = get_context_instance(CONTEXT_SYSTEM);
$baseurl = new moodle_url('/blocks/f2_gestione_risorse/sessioni/sessioni.php');
$blockname = get_string('pluginname', 'block_f2_gestione_risorse');

require_login();
require_capability('block/f2_gestione_risorse:viewsessioni', $context);
require_capability('block/f2_gestione_risorse:editsessioni', $context);
$PAGE->set_pagelayout('standard');
$PAGE->set_context($context);
$PAGE->set_url('/blocks/f2_gestione_risorse/sessioni/sessioni.php');
$PAGE->set_title(get_string('sessioni', 'block_f2_gestione_risorse'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('gestione_sessioni', 'block_f2_gestione_risorse'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);

$addsess  		 = optional_param('add_sess', 0, PARAM_INT); 

// if($userid==0) $userid=$USER->id;
// else if($userid!=0 && has_capability('block/f2_gestione_risorse:viewdipendenticurricula', get_context_instance(CONTEXT_SYSTEM)) && validate_own_dipendente($userid)) $userid=$userid;
// else die();

$anno_formativo = get_anno_formativo_corrente();

// $anno_formativo++; //debug cancellazioni

$form = new sessioni_form(null,array('add_sess' => $addsess,'anno' => $anno_formativo));

if ($form->is_cancelled()) 
{
	redirect($baseurl);
}
else if ($data = $form->get_data())
{	
	$data = $_POST;
// 	print_r($data);
	$err = $form->validation($data);

	if (count($err) == 0) //save data on db
	{
		$sessionids = explode('|',$data['sessionids']);
		$new_sessionids = $sessionids;
		$sessionname = 'sessione_id_';
		$percname = 'percentuale_corsi_';
		foreach ($sessionids as $s)
		{
			$updt = new stdClass;
			$updt->id = $s;
			$data_s_arr = explode('/',$data[$sessionname.$s.'_inizio']);
			$updt->data_inizio = mktime(0,0,0,$data_s_arr[1],$data_s_arr[0],$data_s_arr[2]);
			$data_f_arr = explode('/',$data[$sessionname.$s.'_fine']);
			$updt->data_fine = mktime(0,0,0,$data_f_arr[1],$data_f_arr[0],$data_f_arr[2]);
			$updt->percentuale_corsi = number_format($data[$percname.$s],1);
			$updt->anno = $anno_formativo;
			//update record on DB
			// print_r($updt);
			$new_sessionids = '|'.insert_sessione($updt);
			// $addsess = 0;
		}
		$sessionids = trim($new_sessionids,'|');
		// echo '<p style="color:red">'.get_string('data_saved','block_f2_gestione_risorse').'</p>';
// 		redirect($baseurl);
// 		$PAGE->set_context(null);
// 		$PAGE->set_pagelayout('redirect');
// 		ob_clean();
// 	 	while (ob_get_level()) 
// 	 	{ 
// 			ob_end_clean(); 
// 		} 
// 		ob_clean();

// 		if (ob_get_length())
// 		{
// 			@ob_flush();
// 			@flush();
// 			@ob_end_flush();
// 		}
		// header("location: ".$baseurl);
		redirect(new moodle_url($baseurl));
	}
	else
	{
//		$context = get_context_instance(CONTEXT_SYSTEM);
//		$baseurl = new moodle_url('/blocks/f2_gestione_risorse/sessioni/sessioni.php');
//		$blockname = get_string('pluginname', 'block_f2_gestione_risorse');
//		require_login();
//		require_capability('block/f2_gestione_risorse:viewsessioni', $context);
//		require_capability('block/f2_gestione_risorse:editsessioni', $context);
//		$PAGE->set_pagelayout('standard');
//		$PAGE->set_context($context);
//		$PAGE->set_url('/blocks/f2_gestione_risorse/sessioni/sessioni.php');
//		$PAGE->set_title(get_string('sessioni', 'block_f2_gestione_risorse'));
//		$PAGE->settingsnav;
//		$PAGE->navbar->add(get_string('gestione_sessioni', 'block_f2_gestione_risorse'), $baseurl);
//		$PAGE->set_heading($SITE->shortname.': '.$blockname);
		
		echo $OUTPUT->header();
		echo $OUTPUT->heading(get_string('sessioni', 'block_f2_gestione_risorse'));
		echo $OUTPUT->box_start();
		foreach ($err as $e)
		{
			// echo $OUTPUT->header();
			// echo $OUTPUT->heading(get_string('sessioni', 'block_f2_gestione_risorse'));
			// echo $OUTPUT->box_start();
			echo '<p style="color:red">'.$e.'</p>';
		}
	}
	if (isset($data) and (!is_null($data)) and (!empty($data)))
	{
		$params = $form->get_updated_form_data($data);
		$form = new sessioni_form(null,array('add_sess' => $addsess,'anno' => $anno_formativo, 'date_sess' => $params['date'], 'perc_sess' => $params['perc']));
	}
	echo '<h3>'.get_string('anno_formativo','block_f2_gestione_risorse').': '.$anno_formativo.'</h3>';
	$form->display();

	echo $OUTPUT->box_end();
	echo $OUTPUT->footer();
}
else // all'inizio
{
//	$context = get_context_instance(CONTEXT_SYSTEM);
//	$baseurl = new moodle_url('/blocks/f2_gestione_risorse/sessioni/sessioni.php');
//	$blockname = get_string('pluginname', 'block_f2_gestione_risorse');
//	require_login();
//	require_capability('block/f2_gestione_risorse:viewsessioni', $context);
//	require_capability('block/f2_gestione_risorse:editsessioni', $context);
//	$PAGE->set_pagelayout('standard');
//	$PAGE->set_context($context);
//	$PAGE->set_url('/blocks/f2_gestione_risorse/sessioni/sessioni.php');
//	$PAGE->set_title(get_string('sessioni', 'block_f2_gestione_risorse'));
//	$PAGE->settingsnav;
//	$PAGE->navbar->add(get_string('gestione_sessioni', 'block_f2_gestione_risorse'), $baseurl);
//	$PAGE->set_heading($SITE->shortname.': '.$blockname);
	
	echo $OUTPUT->header();
	echo $OUTPUT->heading(get_string('sessioni', 'block_f2_gestione_risorse'));
	echo $OUTPUT->box_start();
	echo '<h3>'.get_string('anno_formativo','block_f2_gestione_risorse').': '.$anno_formativo.'</h3>';
	$form->display();

	echo $OUTPUT->box_end();
	echo $OUTPUT->footer();
}