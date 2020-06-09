<?php

// $Id: index.php 1104 2013-04-04 15:15:57Z d.lallo $ 

require_once('../../../config.php');
require_once "$CFG->dirroot/course/lib.php";
require_once "$CFG->libdir/adminlib.php";
require_once "$CFG->dirroot/user/filters/lib.php";
require_once 'f2_anagrafica_formatori_form.php';
require_once '../lib.php';
require_once($CFG->dirroot.'/f2_lib/report.php');

require_login();
//require_capability('block/f2_gestione_risorse:vedi_lista_utenti', get_context_instance(CONTEXT_SYSTEM));
require_capability('block/f2_gestione_risorse:vedi_lista_utenti', context_system::instance());

//if (has_capability('block/f2_gestione_risorse:aggiungi_formatore', get_context_instance(CONTEXT_SYSTEM))
if (has_capability('block/f2_gestione_risorse:aggiungi_formatore', context_system::instance())
	//and has_capability('block/f2_gestione_risorse:modifica_formatore', get_context_instance(CONTEXT_SYSTEM))
	and has_capability('block/f2_gestione_risorse:modifica_formatore', context_system::instance())
	//and has_capability('block/f2_gestione_risorse:vedi_lista_formatori', get_context_instance(CONTEXT_SYSTEM))
	and has_capability('block/f2_gestione_risorse:vedi_lista_formatori', context_system::instance())
)
{
	global $USER;
	//if (!isSupervisore($USER->id)) die();
	$page     = optional_param('page', 0, PARAM_INT);
	$perpage  = optional_param('perpage', 10, PARAM_INT);
	$column   = optional_param('column', 'lastname', PARAM_TEXT);
	$sort     = optional_param('sort', 'ASC', PARAM_TEXT);
	$categoria = optional_param('cat', '-1', PARAM_ACTION);
	
	$blockname = get_string('pluginname', 'block_f2_gestione_risorse');
	$header = get_string('anagrafica_formatori_gestione', 'block_f2_gestione_risorse');

	//$context = get_context_instance(CONTEXT_SYSTEM);
	$context = context_system::instance();

	if (empty($CFG->loginhttps)) {
		$securewwwroot = $CFG->wwwroot;
	} else {
		$securewwwroot = str_replace('http:','https:',$CFG->wwwroot);
	}

	$baseurl = new moodle_url('index.php');
	$PAGE->set_context($context);
	$PAGE->set_url('/blocks/f2_gestione_risorse/formatori/index.php');
	$PAGE->set_pagelayout('standard');
	$PAGE->settingsnav;
	$PAGE->navbar->add($header,$baseurl);
	$PAGE->set_heading($SITE->shortname.': '.$blockname);


	if(empty($sort)) $sort = 'lastname';

	$cat_formatori = array('cat_formatori' => array(get_string('all', 'block_f2_gestione_risorse') => 0,'Interno' => 0,'Interno abilitato docenze interne' => 0,'Interno abilitato docenze esterne' => 0,'Esterno' => 0));

	//set categoria preferita
	if ($categoria == -1)
	{
		$cat_formatori['cat_formatori']['Interno']=1;
		$idx = 0;
		$len = count($cat_formatori['cat_formatori']);
		$found = -1;
		
		for($idx=0; $idx<$len; $idx++)
		{
			$foundarr = array_keys(array_slice($cat_formatori['cat_formatori'],$idx,1));
			$found=$foundarr[0];
			if ($cat_formatori['cat_formatori'][$found] == 1)
			{
				$found = 1;
				break;
			}
		}
		if ($found==1) $categoria = $idx;
		else $categoria = -1;
	}
	else 
	{	
		$pref = array_keys(array_slice($cat_formatori['cat_formatori'],$categoria,1));
		$cat_formatori['cat_formatori'][$pref[0]]=1;
	}
//print_r($categoria);
	$form = new anagrafica_formatori_form(null,$cat_formatori);

	$data = $form->get_data();
	$cognome="";
	if ($form->is_cancelled()) 
	{
		$form->set_data(array('cognome_formatore' => ''));
	}
	else if ($data)
	{
		$cognome = str_replace('*','',trim(strip_tags($data->cognome_formatore))); 
		$categoria = $data->categoria_formatore;
	}
	$pagination = array('perpage' => $perpage, 'page'=>$page,'column'=>$column,'sort'=>$sort);
//print_r($pagination);
//if ($pagination) {
if (!isset($data)) { $data = new stdClass(); }
	foreach ($pagination as $key=>$value)
	{
		$data->$key = $value;
	}
//}
	$usersall = get_formatoriRS($data);
	$users = $usersall->dati;
	$total_rows = $usersall->count;

	echo $OUTPUT->header();
	echo $OUTPUT->heading($header);
	echo $OUTPUT->box_start();
	echo '
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

	function checkSelected(cname,errmsg,confmsg)
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
			alert(errmsg);
			return false;
		}
		else
		{
			return confirm(confmsg.replace("_","\'"));
		}
	}
	//]]>
	</script>
	';

	$form->set_data(array('cognome_formatore' => $cognome));
	echo $form->display();

	if($total_rows > 0) {
		
		class report_excel_formazione extends moodleform {
			public function definition() {
				global $CFG;
				$mform2 		=& $this->_form;
				$post_values = $this->_customdata['post_values'];
				$post_values = json_encode($post_values);
				$mform2->addElement('hidden', 'post_values',$post_values);
                                $mform2->setType('post_values', PARAM_RAW);
				$start_icona_export =  html_writer::start_tag('form', array('action' => 'vostrofile.php', 'class' => 'export_excel', 'method' => 'post'));
				$img_icona = html_writer::empty_tag('input', array('type' => 'submit', 'class' => 'ico_xls btn', 'value' => get_string('export_excel_formatori', 'block_f2_gestione_risorse')));
				//$lbl_icona = html_writer::tag('label', ' '.get_string('export_excel_formatori_lbl', 'block_f2_gestione_risorse'));
				$end_icona_export = html_writer::end_tag('form');
			//	$mform2->addElement('html',$start_icona_export.$img_icona.$lbl_icona.$end_icona_export);
				$mform2->addElement('html',$start_icona_export.$img_icona.$end_icona_export);
			}
		}
		$form_id='mform1';										// ID del form dove fare il submit
		$post_extra=array('column'=>$column,'sort'=>$sort);
		$mform_excel = new report_excel_formazione('export-xls.php',array('post_values'=>$data),NULL,NULL,array('class'=>'export_excel'));
		$mform_excel->display();
		
		$head_table = array('chk_all_formatore','empty','lastname','firstname','cf','domain');
		$head_table_sort = array('lastname','domain');
		$align = array ('center','center','left','left','left');
		
		$table = new html_table();
		$table->width = '80%';
		$table->align = $align;
		$table->head = build_head_table($head_table,$head_table_sort,$post_extra,$total_rows, $page, $perpage, $form_id);
			
		foreach ($users as $user) 
		{
			$buttons = array();
			$lastcolumn = '';
			
			// edit button
					$buttons[] = html_writer::link(new moodle_url('profile.php', array('formatore_id'=>$user->formatore_id
					// , 'course'=>$site->id
					)), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/edit'), 'alt'=>get_string('edit', 'block_f2_gestione_risorse'), 'class'=>'iconsmall')), array('title'=>get_string('edit', 'block_f2_gestione_risorse')));
			$row = array ();
			$row[] = '<input type=checkbox name="formatore_id[]" value='.$user->formatore_id.'>';
			$row[] = implode(' ', $buttons);
			$row[] = $user->lastname;
			$row[] = $user->firstname;
			$row[] = $user->cf;
			$row[] = $user->domain;
			$table->data[] = $row;
		}
	}
	else 
	{
		$table = new html_table();
		$table->data = get_string('noresults', 'block_f2_gestione_risorse');
// 		echo '<br/><br/><p>'.get_string('noresults', 'block_f2_gestione_risorse').'</p><br/>';
	}
	echo '<form method="post" action="remove_formatore.php" id="formatore_elimina_form">';
	$btn_row = array();
	$btn_row[] = "<input type=\"button\" value=\"".get_string('nuovo_formatore', 'block_f2_gestione_risorse')."\" onclick=\"document.location.href='add_formatore.php'\"/>";
	if($total_rows > 0)
	{
		$btn_row[] = "<input type=\"submit\" value=\"".get_string('elimina_formatore', 'block_f2_gestione_risorse')."\" onclick=\"return checkSelected('formatore_id[]','".get_string('no_selection', 'block_f2_gestione_risorse')."','".htmlspecialchars(get_string('confirm_delete_msg', 'block_f2_gestione_risorse'))."')\"/>";
		// 		$btn_row[] = "<input type=\"button\" onClick='document.location.href=\"export-xls.php?sort=".$sort."&amp;dir=".$direction."&cogn=".$cognome."&cat=".$categoria."\"' value =\"".get_string('getexcel', 'block_f2_gestione_risorse')."\">";
	}
	else $btn_row[] = '';
	$btn_table = '<table align="left" width="10%"><tr>';
	$buttemp='';
	foreach ($btn_row as $b)
	{
		$buttemp = $buttemp.'<td>'.$b.'</td>';
	}
	$btn_table = $btn_table.$buttemp.'</tr></table>';
	echo $btn_table;
		
	echo "<br/><br/><br/><p>".get_string('count_tot_rows', 'local_f2_traduzioni',$total_rows)."</p>";
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
