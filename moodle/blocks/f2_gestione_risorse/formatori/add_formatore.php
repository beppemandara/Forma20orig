<?php

// $Id: add_formatore.php 819 2012-12-05 15:53:04Z c.arnolfo $ 

require_once '../../../config.php';
require_once "$CFG->dirroot/course/lib.php";
require_once "$CFG->libdir/adminlib.php";
require_once "$CFG->dirroot/user/filters/lib.php";
require_once 'f2_anagrafica_formatori_form.php';
require_once '../lib.php';
require_once($CFG->dirroot.'/f2_lib/report.php');



require_login();
require_capability('block/f2_gestione_risorse:aggiungi_formatore', get_context_instance(CONTEXT_SYSTEM));
require_capability('block/f2_gestione_risorse:modifica_formatore', get_context_instance(CONTEXT_SYSTEM));

if (has_capability('block/f2_gestione_risorse:vedi_lista_utenti', get_context_instance(CONTEXT_SYSTEM))
and has_capability('block/f2_gestione_risorse:vedi_lista_formatori', get_context_instance(CONTEXT_SYSTEM))
	)
{
	global $PAGE, $SITE, $OUTPUT;
	//if (!isSupervisore($USER->id)) die();

	$page     = optional_param('page', 0, PARAM_INT);
	$perpage  = optional_param('perpage', 10, PARAM_INT);
	$column   = optional_param('column', 'lastname', PARAM_TEXT);
	$sort     = optional_param('sort', 'ASC', PARAM_TEXT);

	$blockname = get_string('pluginname', 'block_f2_gestione_risorse');
	$header = get_string('anagrafica_formatori_gestione', 'block_f2_gestione_risorse');
	$header2 = get_string('anagrafica_formatori_aggiunta', 'block_f2_gestione_risorse');

	$context = get_context_instance(CONTEXT_SYSTEM);
	

	if (empty($CFG->loginhttps)) {
		$securewwwroot = $CFG->wwwroot;
	} else {
		$securewwwroot = str_replace('http:','https:',$CFG->wwwroot);
	}

	$baseurl = new moodle_url('add_formatore.php');

	$PAGE->set_context($context);
	$PAGE->set_url('/blocks/f2_gestione_risorse/formatori/add_formatore.php');
	$PAGE->set_pagelayout('standard');
	$PAGE->settingsnav;
	// $PAGE->navbar->add($blockname,new moodle_url('..'));
	$PAGE->navbar->add($header,new moodle_url('index.php'));
	$PAGE->navbar->add($header2,$baseurl);
	$PAGE->set_heading($SITE->shortname.': '.$blockname);

	if(empty($sort)) $sort = 'lastname';
	$form = new anagrafica_formatori_form3(null);

	echo $OUTPUT->header();
	echo $OUTPUT->heading(get_string('elenco_utenti', 'block_f2_gestione_risorse'));
	echo $OUTPUT->box_start();

	echo '
	<script type="text/javascript">
	function checkSelected(cname,msg)
	{
		var selected = 0;
		var chk = document.getElementsByName(cname);
		var tot = chk.length;
		for (i = 0; i < tot; i++) 
		{
			if (chk[i].checked)
			{	
				selected++;
				break;
			}
		}
		if (selected == 0) 
		{
			alert(msg);
			return false;
		}
		else
		{
			return true;
		}
	}
	//]]>
	</script>
	'; 
	echo $form->display();
	
	$data = $form->get_data();
	
	if ($form->is_cancelled())
	{
		$form->set_data(array('cognome_utente' => ''));
	}
		
	$pagination = array('perpage' => $perpage, 'page'=>$page,'column'=>$column,'sort'=>$sort);
	foreach ($pagination as $key=>$value)
	{
		$data->$key = $value;
	}
	// 	print_r($data);exit;
	$usersall = get_non_formatoriRS($data);
	$users = $usersall->dati;
	$total_rows = $usersall->count;

	$form_id='mform1';										// ID del form dove fare il submit
	$post_extra=array('column'=>$column,'sort'=>$sort);
	
	if($total_rows > 0) 
	{
		$table = new html_table();
		$table->width = '80%';
		$head_table = array('empty','lastname','firstname','idnumber');
		$head_table_sort = array('lastname','numero');
		$align = array ('center','left','left','left');

		$table->align = $align;
		$table->head = build_head_table($head_table,$head_table_sort,$post_extra,$total_rows, $page, $perpage, $form_id);
		
		foreach ($users as $user) 
		{
			$row = array ();
			$row[] = '<input type=radio name="userid[]" id="'.$user->id.'" value="'.$user->id.'">';
			$row[] = $user->lastname;
			$row[] = $user->firstname;
			$row[] = $user->idnumber;
			$table->data[] = $row;
		}
	}
	else 
	{
		$table = new html_table();
		$table->data = get_string('noresults', 'block_f2_gestione_risorse');
	}

	echo '<form method="post" action="profile.php">';
	echo '<input type="hidden" name ="action" value="1">';
	$btn_row = array();
	$btn_row[] = "<input type=\"submit\" value=\"".get_string('aggiungi_formatore', 'block_f2_gestione_risorse')."\" onclick=\"return checkSelected('userid[]','".get_string('no_selection', 'block_f2_gestione_risorse')."')\"/>";
	// $btn_row[] = "<input type=button value=\"".get_string('annulla', 'block_f2_gestione_risorse')."\" onclick=\"history.go(-1);\"><br/>";
	$btn_row[] = "<input type=button value=\"".get_string('annulla', 'block_f2_gestione_risorse')."\" onclick=\"document.location.href='index.php';\"><br/>";
	$btn_table = '<table align="left" width="10%"><tr><td>'.$btn_row[0].'</td><td>'.$btn_row[1].'</td></tr></table><br/><br/><br/>';
	echo $btn_table;
	echo "<p><b style='font-size:11px'>".get_string('count_tot_rows', 'local_f2_traduzioni',$total_rows)."</b></p>";
	$paging_bar = new paging_bar_f2($total_rows, $page, $perpage, $form_id, $post_extra);
	echo $paging_bar->print_paging_bar_f2();
	
	echo html_writer::table($table);
	
	echo $paging_bar->print_paging_bar_f2();
	
	echo '</form>';
	echo $OUTPUT->box_end();
	echo $OUTPUT->footer();
}
else 
{
	die;
}
?>