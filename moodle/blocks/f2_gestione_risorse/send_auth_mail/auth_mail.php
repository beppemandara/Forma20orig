<?php
//$Id$

require_once '../../../config.php';
// require_once '../lib.php';
require_once 'auth_mail_form.php';

$context = get_context_instance(CONTEXT_SYSTEM);
require_login();
require_capability('block/f2_gestione_risorse:send_auth_mail', $context);

$userid      = optional_param('userid', 0, PARAM_INT); 
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 10, PARAM_INT); 
$column   = optional_param('column', 'data_inizio', PARAM_TEXT);
$sort     = optional_param('sort', 'ASC', PARAM_TEXT);

// parametri per ritorno da manage
$manage_err = optional_param('err', 0, PARAM_INT);
$manage_eds = optional_param('eds', '-1', PARAM_TEXT);

$post_errors_param = optional_param('post_values_errors', '-1', PARAM_TEXT);
if ($post_errors_param !== '-1')
{
	$post_errors = (array)json_decode($post_errors_param);
}
else $post_errors = array();

// print_r($post_errors);
global $PAGE,$OUTPUT;

if($userid==0) $userid=$USER->id;
else if($userid!=0 && has_capability('block/f2_gestione_risorse:viewdipendenticurricula', get_context_instance(CONTEXT_SYSTEM)) && validate_own_dipendente($userid)) $userid=$userid;
else die();

$baseurl = new moodle_url('/blocks/f2_gestione_risorse/send_auth_mail/auth_mail.php');
$blockname = get_string('pluginname', 'block_f2_gestione_risorse');

$PAGE->set_pagelayout('standard');
$PAGE->set_context($context);
$PAGE->set_url('/blocks/f2_gestione_risorse/send_auth_mail/auth_mail.php');
$PAGE->set_title(get_string('auth_mail_gestione', 'block_f2_gestione_risorse'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('send_auth_mail', 'block_f2_gestione_risorse'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('send_auth_mail', 'block_f2_gestione_risorse'));
echo $OUTPUT->box_start();
$pagination = array('perpage' => $perpage, 'page'=>$page,'column'=>$column,'sort'=>$sort);

if ($manage_eds == '-1')
{
	$form = new auth_mail_form(null);
	//var_dump($form->get_data());exit;
	$data = $form->get_data();
}
else // ritorno da manage 
{
	$manage_eds = explode(',',$manage_eds);
	$edid = intval($manage_eds[0]);
	if ($edid.'' !== $manage_eds[0]) $form = new auth_mail_form(null);
	else
	{
		$anno_val = get_anno_by_edizione_id($edid);
		$num_sess_val = get_numero_sessione_by_anno($anno_val);
		$mese_sess_val = get_data_inizio_edizione($anno_val,$edid);
		$data = new stdClass;
		$data->anno_sel = $anno_val;
		$data->num_sess_sel = $num_sess_val;
		$data->mese_sess_sel = $mese_sess_val;
		
		$form = new auth_mail_form(null,array('data' => json_encode($data)));
		// 	$data = $form->get_data();
	}
}

if ($data)
{
	$form = new auth_mail_form(null,array('data' => json_encode($data)));
}
$form->display();

$exp_form_values = $form->get_form_values();
// echo '<br/>exp form: <br/>';
// print_r($exp_form_values);

if (is_null($exp_form_values['num_sess_sel'])
	or !isset($exp_form_values['num_sess_sel'])
	or empty($exp_form_values['num_sess_sel']))
{
	$exp_form_values['num_sess_sel'] = array_shift(array_values(get_numero_sessioni_per_select_form_by_anno($exp_form_values['anno_sel'])));
}
if (is_null($exp_form_values['mese_sess_sel'])
		or !isset($exp_form_values['mese_sess_sel'])
		or empty($exp_form_values['mese_sess_sel']))
{
	$exp_form_values['mese_sess_sel'] = array_shift(array_values(get_numero_mesi_per_select_form_by_anno($exp_form_values['anno_sel'],$exp_form_values['num_sess_sel'])));
}

// echo '<br/>exp form2: <br/>';
// print_r(array_merge($pagination,$exp_form_values));
$param_tabella = array_merge($pagination,$exp_form_values);
// $edizioni_all = get_dati_tabella_auth_mail((array)$data);
$edizioni_all = get_dati_tabella_auth_mail($param_tabella,0); // 0 con limit
$edizioni = $edizioni_all->dati;
$total_rows = $edizioni_all->count;
// print_r($edizioni);
if($total_rows > 0) 
{
	$PAGE->requires->js(new moodle_url('auth_mail_check.js'));
	class report_excel_sessioni extends moodleform {
		public function definition() {
			global $CFG;
			$mform3 		=& $this->_form;
			$post_values = $this->_customdata['post_values'];
			$post_values = json_encode($post_values);
			$mform3->addElement('hidden', 'post_values',$post_values);
			$start_icona_export =  html_writer::start_tag('form', array('action' => 'export-xls.php', 'class' => 'export_excel', 'method' => 'post'));
			//$img_icona = html_writer::empty_tag('input', array('type' => 'image', 'src' => $CFG->wwwroot.'/blocks/f2_apprendimento/pix/excel_icon1.png', 'height' => '35', 'alt' => 'Esporta', 'title' => get_string('auth_mail_export_excel_lbl', 'block_f2_gestione_risorse')));
			$img_icona = html_writer::empty_tag('input', array('type' => 'submit', 'class' => 'ico_xls btn', 'value' => get_string('auth_mail_export', 'block_f2_gestione_risorse')));
			$lbl_icona = html_writer::tag('label', ' '.get_string('auth_mail_export_excel_lbl', 'block_f2_gestione_risorse'));
			$end_icona_export = html_writer::end_tag('form');
			$mform3->addElement('html',$start_icona_export.$img_icona.$lbl_icona.$end_icona_export);
// 			$buttonarray=array();
// 			$buttonarray[] = &$mform2->createElement('submit', 'submitbutton', get_string('report_excel', 'local_f2_traduzioni'));
// 			$mform2->addGroup($buttonarray, 'buttonar2', '', array(' '), false);
// 			$mform2->closeHeaderBefore('buttonar2');
		}
	}
	$form_id='mform1';	// ID del form dove fare il submit
 	$post_extra=array_merge(array('column'=>$column,'sort'=>$sort),$exp_form_values);
	//$post_extra = array_merge($pagination,$exp_form_values);
	$mform_excel = new report_excel_sessioni('export-xls.php',array('post_values'=>$post_extra),NULL,NULL,array('class'=>'export_excel'));
	$mform_excel->display();

	//$form_id='auth_mail_general_form';
	$head_table = array('chk_all_auth_mail_edizioni','titolo','codice_corso','anno','num_sessione','data_inizio','sirp','sirpdata','data_ora_invio','inviata');
	$head_table_sort = array('titolo','codice_corso','data_inizio');
	$align = array ('center','left','left','center','center','center','center','center','center');
	
	$table = new html_table();
// 	$table->width = '80%';
	$table->align = $align;
	

	$table->head = build_head_table($head_table,$head_table_sort,$post_extra,$total_rows, $page, $perpage, $form_id);
// 	print_r($edizioni);
	if (!is_array($manage_eds))
	{
		$manage_eds = explode(',',$manage_eds);
	}

	foreach ($edizioni as $ed)
	{
		$data_ora_max_invio = get_maxdata_auth_mail_inviate($ed->edizione_id);
		$button_dett = '';
		$show_dettagli = ($data_ora_max_invio !== '') ? true : false;
		if ($show_dettagli)
		{
			//bottone dettagli
			$button_dett = "<input type='button' onclick=window.open('".$CFG->wwwroot."/blocks/f2_gestione_risorse/send_auth_mail/auth_mail_detail_popup.php?ed=".$ed->edizione_id."','','width=800,height=600'); value='".get_string('auth_mail_detail_popup', 'block_f2_gestione_risorse')."'/>";
		}
		
		$span_color = '';
		$span_end = '</span>';
		if ($manage_err == 1 and in_array($ed->edizione_id,$manage_eds))
		{
			$span_color = 'style="color:red"';
		}
		else if ($manage_err == 2)
		{
			if ($post_errors['ed_'.$ed->edizione_id] == 'err_inviomail_sirp')
			{
				$span_color = 'style="color:DarkRed; font-weight: bold"';
				$span_end = ' *'.$span_end;
			} 
			else if ($post_errors['ed_'.$ed->edizione_id] == 'err_inviomail_templ')
			{
// 				$span_color = 'style="color:DarkViolet; font-weight: bold"';
				$span_color = 'style="color:DarkRed; font-weight: bold"';
				$span_end = ' **'.$span_end;
			}
			else if ($post_errors['ed_'.$ed->edizione_id] == 'err_inviomail_dummy')
			{
// 				$span_color = 'style="color:DarkBrown; font-weight: bold"';
				$span_color = 'style="color:DarkRed; font-weight: bold"';
				$span_end = ' ***'.$span_end;
			}
		}
			
		$span_start ='<span '.$span_color.'>';
		$data_inizio_str = '';
		if (!is_null($ed->data_inizio) and !empty($ed->data_inizio) and isset($ed->data_inizio) and $ed->data_inizio !== '') $data_inizio_str = date('d/m/Y',$ed->data_inizio);
		$sirpdata_str = '';
		if (!is_null($ed->data_inizio) and !empty($ed->sirpdata) and isset($ed->sirpdata) and $ed->sirpdata !== '') $sirpdata_str = date('d/m/Y',$ed->sirpdata);
		$data_ora_max_invio_str = '';
		if (!is_null($data_ora_max_invio) and !empty($data_ora_max_invio) and isset($data_ora_max_invio) and $data_ora_max_invio !== '') $data_ora_max_invio_str = date('d/m/Y H:i:s',$data_ora_max_invio);
		
		$row = array ();
		$row[] = '<input type=checkbox name="edizione_id[]" id = "edizione_id_'.$ed->edizione_id.'" value='.$ed->edizione_id.' onclick="edit_table('.$ed->edizione_id.',true);">';
		$row[] = $span_start.$ed->titolo.$span_end;
		$row[] = $span_start.$ed->codice_corso.$span_end;
		$row[] = $ed->anno;
		$row[] = $ed->num_sessione;
		$row[] = $data_inizio_str;
		$row[] = '<input type=text id="sirpedid_'.$ed->edizione_id.'" value="'.$ed->sirp.'" name="sirpedid_'.$ed->edizione_id.'" style="width:50px; border:none" readonly="readonly" />';
		$row[] = '<input type=text id="sirpdataedid_'.$ed->edizione_id.'" value="'.$sirpdata_str.'" name="sirpdataedid_'.$ed->edizione_id.'" style="width:100px; border:none" readonly="readonly" />';
		$row[] = $data_ora_max_invio_str;
		$row[] = ($show_dettagli) ?$button_dett : '';
		$table->data[] = $row;
	}
	
	echo '<br/><form method="post" action="auth_mail_manage_action.php" id="auth_mail_general_form">';
	$btn_row = array();
	$btn_row[] = "<input type=\"submit\" name=\"action\" value=\"".get_string('auth_mail_salva', 'block_f2_gestione_risorse')."\" onclick=\"document.location.href='auth_mail_manage_action.php'\"/>";
	$btn_row[] = "<input type=\"button\" value=\"".get_string('annulla', 'block_f2_gestione_risorse')."\"value=\"".get_string('annulla', 'block_f2_gestione_risorse')."\" onclick=\"resetAll('selAll','edizione_id[]')\"/>";
	$btn_row[] = "<input type=\"submit\" name=\"action\" value=\"".get_string('auth_mail_invia', 'block_f2_gestione_risorse')."\" onclick=\"document.location.href='auth_mail_manage_action.php'\"/>";
	$btn_table = '<table align="left" width="30%"><tr>';
	$buttemp='';
	foreach ($btn_row as $b)
	{
		$buttemp = $buttemp.'<td>'.$b.'</td>';
	}
	$btn_table = $btn_table.$buttemp.'</tr></table>';
	echo $btn_table;
	echo '<br/><br/><br/>';
	if ($manage_err == 1)
	{
		echo '<span style="color:red">'.get_string('auth_mail_salva_err_msg', 'block_f2_gestione_risorse').'</span><br/><br/>';
	}
	else if ($manage_err == 2)
	{
		echo '<span style="color:DarkRed; font-weight:bold">'.get_string('auth_mail_invio_err_msg', 'block_f2_gestione_risorse').'</span>';
// 		$post_errors = json_decode($_POST['post_values_errors']);
// 		foreach ($post_errors as $err)
// 		{
			
// 		}
	}
	
	// TABELLA EDIZIONI
	echo "<p>Totale edizioni: $total_rows</p>";
	$paging_bar = new paging_bar_f2($total_rows, $page, $perpage, $form_id, $post_extra);
	echo $paging_bar->print_paging_bar_f2();
	echo html_writer::table($table);
	echo $paging_bar->print_paging_bar_f2();
	//FINE TABELLA EDIZIONI
	
	echo '<input type="hidden" name="column" value="'.$column.'"/>';
	echo '<input type="hidden" name="sort" value="'.$sort.'"/>';
	echo '</form>';
}
else
{
// 	$table = new html_table();
// 	$table->data = get_string('noresults', 'block_f2_gestione_risorse');
	echo '<br/><br/><p>'.get_string('auth_mail_noresults', 'block_f2_gestione_risorse').'</p><br/>';
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer();