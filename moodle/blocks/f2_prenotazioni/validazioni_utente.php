<?php
//$Id$

require_once '../../config.php';
require_once 'lib.php';

// print_r($_SESSION);

if (isset($_SESSION['pid_inconsistenti']))
{
	$pid_inconsistenti = json_decode($_SESSION['pid_inconsistenti']);
	unset($_SESSION['pid_inconsistenti']);
	$pid_sett_chk = json_decode($_SESSION['pid_sett_chk']);
	unset($_SESSION['pid_sett_chk']);
	$pid_dir_chk = json_decode($_SESSION['pid_dir_chk']);
	unset($_SESSION['pid_dir_chk']);
}
else 
{
	$pid_inconsistenti = array();
	$pid_sett_chk = array();
	$pid_dir_chk = array();
}

require_login();
$blockid = get_block_id(get_string('pluginname_db','block_f2_prenotazioni'));

require_capability('block/f2_prenotazioni:editvalidazioni', get_context_instance(CONTEXT_BLOCK, $blockid));
$PAGE->requires->js(new moodle_url('lib_prenotati.js'));

$anno_formativo    = optional_param('anno', 0, PARAM_INT);
$userid     = required_param('userid', PARAM_INT);
$settore_id       = required_param('settid', PARAM_INT);
$show_sf       = required_param('show_sf', PARAM_INT);
$save  = optional_param('save', 10, PARAM_INT);
$page     = optional_param('page', 0, PARAM_INT);
$perpage  = optional_param('perpage', 10, PARAM_INT);
$column   = optional_param('column', 'codice', PARAM_TEXT);
$sort     = optional_param('sort', 'ASC', PARAM_TEXT);

if (!canManageDomain($settore_id))
	die();

if($userid==0)
	$userid=$USER->id;
else if($userid!=0 && has_capability('block/f2_prenotazioni:editvalidazioni', get_context_instance(CONTEXT_BLOCK, $blockid)) && validate_own_dipendente($userid))
	$userid=$userid;
else
	die();

if($anno_formativo==0)
	$anno_formativo=get_anno_formativo_corrente();

$isOk = check_stati_validazione_per_utente($USER->id);
if ($isOk == true)
{
}
else
	redirect(new moodle_url('/'));

$blockname = get_string('pluginname_validazione', 'block_f2_prenotazioni');
$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/f2_prenotazioni/validazione_utente.php');
$PAGE->set_title(get_string('pluginname_validazione', 'block_f2_prenotazioni'));
$PAGE->settingsnav;
$PAGE->set_heading($SITE->shortname.': '.$blockname);

if (isSettore($settore_id))
{
	$objsettore = get_organisation_info_by_id($settore_id);
	$settore_nome = is_null($objsettore->fullname) ? 'n.d.' : $objsettore->shortname .' - '. $objsettore->fullname;
	$objdirezione = get_organisation_info_by_id($objsettore->parentid);
	$direzione_nome = is_null($objdirezione->fullname) ? 'n.d.' : $objdirezione->shortname .' - '. $objdirezione->fullname;
// 	$mostra_dip_sett = true;
}
else if (isDirezione($settore_id))
{
	$objdirezione = get_organisation_info_by_id($settore_id);
	$direzione_nome = is_null($objdirezione->fullname) ? 'n.d.' :  $objdirezione->shortname .' - '. $objdirezione->fullname;
	$settore_nome = $direzione_nome;
	// 	$settore_nome = '-';
// 	$settore_fittizio = '_fittizio';
// 	$mostra_dip_sett = ($mostra_settore_fittizio == 1) ? true : false;
}
else // domini di livello 1-2 per ora trattati come direzioni
{
	// 	$root_domain = get_root_framework();
	$objdirezione = get_organisation_info_by_id($settore_id);
	$direzione_nome = is_null($objdirezione->fullname) ? 'n.d.' :  $objdirezione->shortname .' - '. $objdirezione->fullname;
	$settore_nome = $direzione_nome;
	// 	$settore_nome = '-';
// 	$settore_fittizio = '_fittizio';
// 	$mostra_dip_sett = ($mostra_settore_fittizio == 1) ? true : false;
}

if ($show_sf == 0)
{
	$baseurl = new moodle_url('/blocks/f2_prenotazioni/validazioni_altri.php?organisationid='.$objdirezione->id);
	$navbar_url1 = new moodle_url('/blocks/f2_prenotazioni/validazioni_altri.php?organisationid='.$settore_id);
	$navbar_url2 = new moodle_url('/blocks/f2_prenotazioni/validazioni_utente.php?userid='.$userid.'&settid='.$settore_id.'&show_sf=0');
}
else // mostra settore fittizio
{
	$baseurl = new moodle_url('/blocks/f2_prenotazioni/validazioni_altri.php?organisationid='.$settore_id.'&show_sf=0');
	$navbar_url1 = new moodle_url('/blocks/f2_prenotazioni/validazioni_altri.php?organisationid='.$settore_id.'&show_sf=1');
	$navbar_url2 = new moodle_url('/blocks/f2_prenotazioni/validazioni_utente.php?userid='.$userid.'&settid='.$settore_id.'&show_sf=1');
}
if (isReferenteDiDirezione($userid) or isSupervisore($userid))
	$PAGE->navbar->add(get_string('validazione_altri', 'block_f2_prenotazioni'), $baseurl);
$PAGE->navbar->add(get_string('validazione_altri_dip', 'block_f2_prenotazioni'),$navbar_url1);
$PAGE->navbar->add(get_string('validazioni_utente', 'block_f2_prenotazioni'), $navbar_url2);

$userdata = get_user_data($userid);
$user_cohort = get_user_cohort_by_category($userid);
$perc_array = get_percentuali_completamento($userid);
$data_perc_fine_prec = $perc_array[0]['data_fine_precedente'];
$perc_fine_prec = $perc_array[0]['perc_fine_precedente'];
$data_perc_fine_corr = $perc_array[1]['data_fine_corrente'];
$perc_fine_corr = $perc_array[1]['perc_fine_corrente'];

// TABELLA DATI ANAGRAFICI
$table_anag = new html_table();
$table_anag->align = array('right', 'left');
$table_anag->data = array(
		array('Matricola',''.$userdata->idnumber.''),
		array('Cognome Nome ','<b>'.$userdata->lastname.' '.$userdata->firstname.'</b>'),
		array('Settore',''.is_null($settore_nome) ? '' : $settore_nome.''),
		array('Direzione / Ente',''.is_null($direzione_nome) ? '' : $direzione_nome.''),
		array('Categoria',''.$userdata->category.''),
		array('Percentuale Piano di Studi al '.$data_perc_fine_prec, $perc_fine_prec),
		array('Percentuale Piano di Studi al '.$data_perc_fine_corr, $perc_fine_corr),
);

//INIZIO Form
class valida_prenotazioni_form extends moodleform {
	public function definition() {
		$mform =& $this->_form;
// 		$post_values = $this->_customdata['post_values'];
// 		if (isset($post_values) and (!is_null($post_values)) and (!empty($post_values)))
// 		{
// 			$post_values = json_encode($post_values);
// 			$mform->addElement('hidden', 'post_values',$post_values);
// 		}
		$mform->addElement('hidden', 'settid',$this->_customdata['settid']);
		$mform->addElement('hidden', 'show_sf',$this->_customdata['show_sf']);
// 		$mform->addElement('text', 'search_name','Cognome', 'maxlength="254" size="50"');
// 		$mform->addElement('submit', 'submitbtn', 'Ricerca');
	}
}
$mform = new valida_prenotazioni_form(null,array('settid'=>$settore_id,'show_sf' => $show_sf));
$mform->display();
//FINE Form

// $data = $mform->get_data();
$data = new stdClass;
$data->cohorts = $user_cohort->cohortid;
$data->userid = $userid;
$pagination = array('perpage' => $perpage, 'page'=>$page,'column'=>$column,'sort'=>$sort);

foreach ($pagination as $key=>$value)
{
	$data->$key = $value;
}

$form_id='mform1';	// ID del form dove fare il submit
$post_extra=array('column'=>$column,'sort'=>$sort,'userid'=>$userid);
$full_prenotati = get_user_prenotazioni_per_validazione($data);
$prenotati = $full_prenotati->dati;
$total_rows = $full_prenotati->count;
$table = new html_table();
// $table->width = '100%';

if (canView($USER->id, 'dir'))
{
	$head_table = array('codice','titolo','sede_corso','segmento_formativo',
			'durata_crediti','costo','data_ultima_prenotazione','utente_ultima_prenotazione',
			'validato_settore','data_ultima_modifica_validazione_sett','utente_ultima_modifica_validazione_sett'
			,'validato_direzione','data_ultima_modifica_validazione_dir'
			,'utente_ultima_modifica_validazione_dir');
	$align = array ('center','center','center','center','center','center','center','center','center','center'
			,'center','center','center');	
}
else if (canView($USER->id, 'sett'))
{
	$head_table = array('codice','titolo','sede_corso','segmento_formativo',
			'durata_crediti','costo','data_ultima_prenotazione','utente_ultima_prenotazione',
			'validato_settore','data_ultima_modifica_validazione_sett','utente_ultima_modifica_validazione_sett');
	$align = array ('center','center','center','center','center','center','center','center','center','center');
}

$head_table_sort = array('codice');
$table->align = $align;
$table->head = build_head_table($head_table,$head_table_sort,$post_extra,$total_rows, $page, $perpage, $form_id);
$next_param_str='';
$nextpage='validazioni_utente.php';

foreach ($prenotati as $c)
{
	$table->data[] = get_validazioni_dati_tabella($c,$pid_inconsistenti,$pid_sett_chk,$pid_dir_chk);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('validazioni_utente', 'block_f2_prenotazioni'));
echo $OUTPUT->box_start();
echo html_writer::table($table_anag);
// FINE TABELLA DATI ANAGRAFICI

if ($save == 1)
{
	echo '<br/><p style="color:red">'.get_string('data_saved', 'block_f2_prenotazioni').'</p>';
}

$confirm_msg = get_string('conferma_annulla_modifiche', 'block_f2_prenotazioni');
$backpage = 'validazioni_altri.php?organisationid='. $settore_id.'&show_sf='.$show_sf;
if ($total_rows > 0) 
{
	$form = '<form autocomplete="off" action="manage_validazione_utente_sett.php" method="post" accept-charset="utf-8" id="mform_prenot99" class="mform">';
	$form .= '<input type="hidden" name="userid" value="'.$userid.'">';
	$form .= '<input type="hidden" name="settid" value="'.$settore_id.'">';
	$form .= '<input type="hidden" name="show_sf" value="'.$show_sf.'">';
	$confirm_msg_inconsistenze = get_string('inconsistenze_sett', 'block_f2_prenotazioni');
	//INIZIO TABELLA BOTTONI
	$btn_row = array();
	if (isReferenteDiSettore($USER->id))
	{
		if (!is_dominio_closed($settore_id, 'sett'))
		{
			$btn_row[] = '<input type="submit" value="'.get_string('conferma', 'block_f2_prenotazioni').'"
					onClick="return checkPidInconsistenti(\'mform_prenot99\',\''.$confirm_msg_inconsistenze.'\')">';
			$btn_row[] = '<input type="button" value="'.get_string('back_riepilogo', 'block_f2_prenotazioni').'" 
							onClick="confirmBackRiepilogo(\'mform_prenot99\',\''.$confirm_msg.'\',\''.$backpage.'\')">';
			$btn_row[] = '<input type="button" value="'.get_string('print', 'block_f2_prenotazioni').'" onClick="window.print()">';
			$btn_table = '<table align="left" width="10%"><tr>';
		}
		else if (is_dominio_closed($settore_id, 'sett'))
		{
			$btn_row[] = '<input type="button" value="'.get_string('back_riepilogo', 'block_f2_prenotazioni').'"
							onClick="document.location.href=\''.$backpage.'\'">';
			$btn_row[] = '<input type="button" value="'.get_string('print', 'block_f2_prenotazioni').'" onClick="window.print()">';
			$btn_table = '<table align="left" width="10%"><tr>';
		}
	}
	else if (isReferenteDiDirezione($USER->id) or isSupervisore($USER->id))
	{
		$btn_row[] = '<input type="submit" value="'.get_string('conferma', 'block_f2_prenotazioni').'"
				onClick="return checkPidInconsistenti(\'mform_prenot99\',\''.$confirm_msg_inconsistenze.'\')">';
		$btn_row[] = '<input type="button" value="'.get_string('back_riepilogo', 'block_f2_prenotazioni').'"
						onClick="confirmBackRiepilogo(\'mform_prenot99\',\''.$confirm_msg.'\',\''.$backpage.'\')">';
		$btn_row[] = '<input type="button" value="'.get_string('print', 'block_f2_prenotazioni').'" onClick="window.print()">';
		$btn_table = '<table align="left" width="10%"><tr>';
	}
	
	$buttemp='';
	foreach ($btn_row as $b)
	{
		$buttemp = $buttemp.'<td>'.$b.'</td>';
	}
	$btn_table = $btn_table.$buttemp.'</tr></table><br/>';
	//FINE TABELLA BOTTONI

	echo $form;
	echo $btn_table;
	echo '<br/><br/>';	
	$paging_bar = new paging_bar_f2($total_rows, $page, $perpage, $form_id, $post_extra);
	echo $paging_bar->print_paging_bar_f2();
	echo '<div style="font-size: 11px;">';
	echo html_writer::table($table);
	echo '</div>';
	echo $paging_bar->print_paging_bar_f2();
	echo '</form>';
}
else //empty
{
	echo '<input type="button" value="'.get_string('back_riepilogo', 'block_f2_prenotazioni').'" 
					onClick="confirmBackRiepilogo(\'mform_prenot99\',\''.$confirm_msg.'\',\''.$backpage.'\')">';
	echo '<br/><br/><p class="msg_user">'.get_string('noresults', 'block_f2_prenotazioni').'</p><br/>';
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer();