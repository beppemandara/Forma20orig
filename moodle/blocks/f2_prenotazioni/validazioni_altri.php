<?php
//$Id: validazioni_altri.php 1241 2013-12-20 04:34:05Z l.moretto $

require_once '../../config.php';
require_once 'lib.php';
require_once($CFG->dirroot.'/f2_lib/management.php');

require_login();

$blockid = get_block_id(get_string('pluginname_db','block_f2_prenotazioni'));
require_capability('block/f2_prenotazioni:viewvalidazioni', get_context_instance(CONTEXT_BLOCK, $blockid));

$settore_id  = optional_param('organisationid', 0, PARAM_INT);
$mostra_settore_fittizio  = optional_param('show_sf', 0, PARAM_INT);
$check_anomalie_server  = optional_param('anomalie', 0, PARAM_INT);
$check_settori_anomali  = optional_param('d', '', PARAM_TEXT);
$budget_risp_server  = optional_param('br', 0, PARAM_INT);
$budget_flag = 0; 
$anomalie_budget_arr = array();

if (isset($_SESSION['anomalie_budget']))
{
	$anomalie_budget_arr = json_decode($_SESSION['anomalie_budget']);
	$anomalie_budget_arr = (array)$anomalie_budget_arr;
	unset($_SESSION['anomalie_budget']);
	$budget_flag = 1;
}

$isOk = check_stati_validazione_per_utente($USER->id);
if(isSupervisore($USER->id))
	$isOk= true;
$ref_dir_sup = false;
$capo_sett = false;

if (isSupervisore($USER->id) or (isReferenteDiDirezione($USER->id)))
{
	$ref_dir_sup = true;
	$capo_sett = false;
}
else if (isReferenteDiSettore($USER->id))
{
	$ref_dir_sup = false;
	$capo_sett = true;
}

if ($isOk == true)
{
	if ($ref_dir_sup)
	{
		if ($settore_id == 0)
		{
			redirect(new moodle_url('scegli_settore.php'));
		}
		else if (!isDirezione($settore_id) and 
				!isSettore($settore_id))
		{
			redirect(new moodle_url('scegli_settore.php?inv=1'));
		}
	}
	else if ($capo_sett)
	{
		$settore = get_user_viewable_organisation($USER->id);
		$settore_id = $settore[0];
	}
}
else
{
	redirect(new moodle_url('/'));
}

if (!canManageDomain($settore_id))
	die();

$userid     = optional_param('userid', 0, PARAM_INT);
$anno_formativo    = optional_param('anno', 0, PARAM_INT);
$page     = optional_param('page', 0, PARAM_INT);
$perpage  = optional_param('perpage', 10, PARAM_INT);
$column   = optional_param('column', 'utente', PARAM_TEXT);
$sort     = optional_param('sort', 'ASC', PARAM_TEXT);

if($userid==0)
	$userid=$USER->id;
else if($userid!=0 && has_capability('block/f2_prenotazioni:viewvalidazioni', get_context_instance(CONTEXT_BLOCK, $blockid)) && validate_own_dipendente($userid))
	$userid=$userid;
else
	die();

if($anno_formativo==0)
	$anno_formativo=get_anno_formativo_corrente();

$param_all_next = array('settid'=>$settore_id,'show_sf'=>$mostra_settore_fittizio);
$blockname = get_string('pluginname_validazione', 'block_f2_prenotazioni');
$context = get_context_instance(CONTEXT_SYSTEM);

$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/f2_prenotazioni/validazione_altri.php');
$PAGE->set_title(get_string('pluginname_validazione', 'block_f2_prenotazioni'));
$PAGE->settingsnav;
$PAGE->set_heading($SITE->shortname.': '.$blockname);
$PAGE->requires->js(new moodle_url('lib_prenotati.js'));
// $PAGE->navbar->add(get_string('xxxxxxxx', 'block_f2_prenotazioni'));
// echo $OUTPUT->heading(get_string('validazione_altri', 'block_f2_prenotazioni'));
// echo $OUTPUT->box_start();

$mostra_dip_sett = false;
$settore_fittizio = '';

if (isSettore($settore_id))
{
	$objsettore = get_organisation_info_by_id($settore_id);
	$settore_nome = is_null($objsettore->fullname) ? 'n.d.' : $objsettore->shortname.' - '.$objsettore->fullname;
	
	$objdirezione = get_organisation_info_by_id($objsettore->parentid);
	$direzione_nome = is_null($objdirezione->fullname) ? 'n.d.' : $objdirezione->shortname.' - '.$objdirezione->fullname;
	$stato_validaz_settore = get_stato_validazione_str($settore_id,null,$anno_formativo);
	$mostra_dip_sett = true;
}
else if (isDirezione($settore_id))
{
	$objdirezione = get_organisation_info_by_id($settore_id);
	$direzione_nome = is_null($objdirezione->fullname) ? 'n.d.' : $objdirezione->shortname.' - '.$objdirezione->fullname;
	if ($mostra_settore_fittizio == 1)
	{
		$mostra_dip_sett = true;
		$settore_nome = $direzione_nome;
		$stato_validaz_settore = get_stato_validazione_str($settore_id,$mostra_settore_fittizio);
	}
	else 
	{
		$settore_nome = '-';
		$stato_validaz_settore = '-';
		$mostra_dip_sett = false;
	}
	$settore_fittizio = '_fittizio';
}
else // domini di livello 1-2 per ora trattati come direzioni
{
	$objdirezione = get_organisation_info_by_id($settore_id);
	$direzione_nome = is_null($objdirezione->fullname) ? 'n.d.' : $objdirezione->shortname.' - '.$objdirezione->fullname;
	if ($mostra_settore_fittizio == 1)
	{
		$mostra_dip_sett = true;
		$settore_nome = $direzione_nome;
		$stato_validaz_settore = get_stato_validazione_str($settore_id);
	}
	else 
	{
		$settore_nome = '-';
		$stato_validaz_settore = '-';
		$mostra_dip_sett = false;
	}
	$settore_fittizio = '_fittizio';
}

if (isReferenteDiDirezione($userid) or isSupervisore($userid))
{
	if ($mostra_settore_fittizio == 0) // navbar con link alla direzione
	{
		$baseurl = new moodle_url('/blocks/f2_prenotazioni/validazioni_altri.php?organisationid='.$objdirezione->id);
	}
	else // navbar con link al settore
	{
		$baseurl = new moodle_url('/blocks/f2_prenotazioni/validazioni_altri.php?organisationid='.$settore_id);
	}
	$PAGE->navbar->add(get_string('validazione_altri', 'block_f2_prenotazioni'), $baseurl);	
}

// TABELLA SETTORE E DIREZIONE
$table_sd = new html_table();
$table_sd->align = array('right', 'left');
$table_sd->data = array(
		array('Direzione / Ente',''.$direzione_nome.''),
		array('Stato Validazioni Direzione / Ente',''.get_stato_validazione_str($objdirezione->id,null,$anno_formativo).' '),
		array('Settore',''.$settore_nome.''),
		array('Stato Validazioni Settore',''.$stato_validaz_settore.'')
);

$tabella_da_mostrare = '';
$num_validazioni_inconsistenti = 0;

if ($mostra_dip_sett)
{
	//INIZIO Form
	class ricerca_dipendenti_form extends moodleform {
		public function definition() {
			$mform =& $this->_form;
// 			$post_values = $this->_customdata['post_values'];
// 			if (isset($post_values) and (!is_null($post_values)) and (!empty($post_values)))
// 			{
// 				$post_values = json_encode($post_values);
// 				$mform->addElement('hidden', 'post_values',$post_values);
// 			}
			$mform->addElement('hidden', 'organisationid', $this->_customdata['organisationid']);
			$mform->addElement('hidden', 'show_sf', $this->_customdata['show_sf']);
			$mform->addElement('text', 'search_name','Cognome', 'maxlength="254" size="50"');
			$mform->addElement('submit', 'submitbtn', 'Cerca');
		}
	}
	$mform = new ricerca_dipendenti_form(null,array('organisationid'=>$settore_id,'show_sf' => $mostra_settore_fittizio));
// 	$mform->display();
	//FINE Form
	
	$data = $mform->get_data();
	$pagination = array('perpage' => $perpage, 'page'=>$page,'column'=>$column,'sort'=>$sort);
	
	foreach ($pagination as $key=>$value)
	{
		$data->$key = $value;
	}
	
	$form_id='mform1';	// ID del form dove fare il submit
	$post_extra=array('column'=>$column,'sort'=>$sort);
	$full_dipendenti = get_dipendenti_by_caposettore($settore_id,NULL, $data, FALSE);
	//$full_dipendenti = get_dipendenti($USER->id,$data);
	$dipendenti = $full_dipendenti->dati;
	$total_rows = $full_dipendenti->count;
	// TABELLA DIPENDENTI
	$table = new html_table();
	$table->width = '100%';
// 	$table->wrap = array(null, 'wrap');
// 	$table->size = array('5%','10%','5%','8%','8%','8%','8%','3%','3%');

	if ($capo_sett)
	{
		$head_table = array('utente','settore','percentuale_completamento_pianostudi',
			'data_ultima_prenotazione','utente_ultima_prenotazione',
			'data_ultima_modifica_validazione_generale','utente_ultima_modifica_validazione_generale',
			'num_corsi_prenotati','num_corsi_validati_sett',
			'giorni_crediti_prenotati','giorni_crediti_validati');
		$align = array ('left','center','center','center','center','center','center','center','center','center','center');
	}
	else if ($ref_dir_sup)
	{
		$head_table = array('utente','settore','percentuale_completamento_pianostudi',
				'data_ultima_prenotazione','utente_ultima_prenotazione',
				'data_ultima_modifica_validazione_generale','utente_ultima_modifica_validazione_generale',
				'num_corsi_prenotati','num_corsi_validati_sett',
				'num_corsi_validati_dir','giorni_crediti_prenotati',
				'giorni_crediti_validati_sett','giorni_crediti_validati_dir');
		$align = array ('left','center','center','center','center','center','center','center',
						'center','center','center','center','center');
	}
	else
		$dipendenti = array(); //non dovrebbe mai entrare
		
	$head_table_sort = array('utente');
	$table->align = $align;
	$table->head = build_head_table($head_table,$head_table_sort,$post_extra,$total_rows, $page, $perpage, $form_id);
	$next_param_str='&settid='.$settore_id.'&show_sf='.$mostra_settore_fittizio;
	$nextpage='validazioni_utente.php';
	
	foreach ($dipendenti as $c)
	{
		$perc_array = get_percentuali_completamento($c->id);
		$data_perc_fine_prec = $perc_array[0]['data_fine_precedente'];
		$perc_fine_prec = $perc_array[0]['perc_fine_precedente'];
		$data_perc_fine_corr = $perc_array[1]['data_fine_corrente'];
		$perc_fine_corr = $perc_array[1]['perc_fine_corrente'];
		$dati_ultima_prenotazione = get_dati_ultima_prenotazione($c->id,$anno_formativo);
// 		$dati_ultima_prenotazione = get_dati_ultima_prenotazione($c->id);
		$ultima_prenotazione_data = $dati_ultima_prenotazione->max_prenotazione_data;
		$ultima_prenotazione_utente = $dati_ultima_prenotazione->max_prenotazione_utente;
		$dati_ultima_validazione = get_dati_ultima_validazione($c->id,$anno_formativo);
// 		$dati_ultima_validazione = get_dati_ultima_validazione($c->id);
		$ultima_validazione_data = $dati_ultima_validazione->max_validazione_data;
		$ultima_validazione_utente = $dati_ultima_validazione->max_validazione_utente;
		$numero_prenotazioni = get_numero_prenotazioni($c->id,$anno_formativo);	
// 		$numero_prenotazioni = get_numero_prenotazioni($c->id);	
		if ($numero_prenotazioni == 0)
		{
			$numero_validazioni = 0;
			$giorni_crediti_prenotati = '- / -';
			$giorni_crediti_validati = '- / -';
		}
		else 
		{
			$numero_validazioni = get_numero_validazioni($c->id,$anno_formativo);
			$giorni_crediti_prenotati = get_giorni_crediti_prenotati($c->id,$anno_formativo);
			$giorni_crediti_validati = get_giorni_crediti_validati($c->id,'sett',$anno_formativo);
// 			$numero_validazioni = get_numero_validazioni($c->id);
// 			$giorni_crediti_prenotati = get_giorni_crediti_prenotati($c->id);
// 			$giorni_crediti_validati = get_giorni_crediti_validati($c->id);
		}
		if ($capo_sett)
		{
			$table->data[] = array(
				"<a href='".$nextpage."?userid=".$c->id.$next_param_str."'>".$c->utente."</a>",
				$settore_nome,
				$perc_fine_corr,$ultima_prenotazione_data,$ultima_prenotazione_utente,$ultima_validazione_data,$ultima_validazione_utente
				,$numero_prenotazioni,$numero_validazioni,$giorni_crediti_prenotati,$giorni_crediti_validati			
				);
		}
		else if ($ref_dir_sup)
		{
			$span_color = '';
			if ($numero_prenotazioni == 0)
			{
				$numero_validazioni_dir = '- / -';
				$giorni_crediti_validati_dir = '- / -';
			}
			else 
			{
				$numero_validazioni_dir = get_numero_validazioni_dir($c->id,$anno_formativo);
				$giorni_crediti_validati_dir = get_giorni_crediti_validati($c->id,'dir',$anno_formativo);

				$validaz_inconsistenti = get_num_validazioni_inconsistenti_by_dominio($settore_id,$c->id,$anno_formativo);
// 				$numero_validazioni_dir = get_numero_validazioni_dir($c->id);
// 				$giorni_crediti_validati_dir = get_giorni_crediti_validati($c->id,'dir');

// 				$validaz_inconsistenti = get_num_validazioni_inconsistenti_by_dominio($settore_id,$c->id);
				if ($validaz_inconsistenti > 0)
				{
					$span_color = 'style="color:red"';
					$num_validazioni_inconsistenti = $num_validazioni_inconsistenti + $validaz_inconsistenti;
				}
				else $span_color = '';
			}
			
			$span_start ='<span '.$span_color.'>';
			$span_end = '</span>';
			$table->data[] = array(
					"<a href='".$nextpage."?userid=".$c->id.$next_param_str."'>".$c->utente."</a>",
					$span_start.$settore_nome.$span_end,
					$span_start.$perc_fine_corr.$span_end,
					$span_start.$ultima_prenotazione_data.$span_end,
					$span_start.$ultima_prenotazione_utente.$span_end,
					$span_start.$ultima_validazione_data.$span_end,
					$span_start.$ultima_validazione_utente.$span_end,
					$span_start.$numero_prenotazioni.$span_end,
					$span_start.$numero_validazioni.$span_end,
					$span_start.$numero_validazioni_dir.$span_end,
					$span_start.$giorni_crediti_prenotati.$span_end,
					$span_start.$giorni_crediti_validati.$span_end,
					$span_start.$giorni_crediti_validati_dir.$span_end
			);
		}
	}
	$tabella_da_mostrare = 'dip';
}
else // mostra_dip_sett == false, mostrare sommario direzione
{
	$column = 'settore';
	$sort = 'ASC';
	//INIZIO Form
	class sommario_sett_direzione_form extends moodleform {
		public function definition() {
			$mform =& $this->_form;
// 			$post_values = $this->_customdata['post_values'];
// 			if (isset($post_values) and (!is_null($post_values)) and (!empty($post_values)))
// 			{
// 				$post_values = json_encode($post_values);
// 				$mform->addElement('hidden', 'post_values',$post_values);
// 			}
			$mform->addElement('hidden', 'organisationid', $this->_customdata['organisationid']);
// 			$mform->addElement('text', 'search_name','Cognome', 'maxlength="254" size="50"');
// 			$mform->addElement('submit', 'submitbtn', 'Ricerca');
		}
	}
	$mform = new sommario_sett_direzione_form(null,array('organisationid'=>$settore_id));
// 	$mform->display();
	//FINE Form
	
	$data = $mform->get_data();
	
	$pagination = array('perpage' => $perpage, 'page'=>$page,'column'=>$column,'sort'=>$sort);
	foreach ($pagination as $key=>$value)
	{
		$data->$key = $value;
	}
	$data->organisationid = $settore_id;
	$form_id='mform1';	// ID del form dove fare il submit
	$post_extra=array('column'=>$column,'sort'=>$sort);
	
// 	$settori = get_settori_by_direzione($settore_id);
// 	$total_rows = count($settori);
	$full_settori = get_settori_by_direzione_paginato($data);
	$settori = $full_settori->dati;
	$total_rows = $full_settori->count;
	
	$table = new html_table();
	$table->width = '100%';
	$head_table = array('settore','stato_sett','num_corsi_prenotati','num_corsi_validati_sett',
			'num_corsi_validati_dir',
			'giorni_crediti_prenotati','giorni_crediti_validati_sett','giorni_crediti_validati_dir');
	$head_table_sort = array('');
	$align = array ('left','center','center','center','center','center','center','center');
	$table->align = $align;
	$table->head = build_head_table($head_table,$head_table_sort,$post_extra,$total_rows, $page, $perpage, $form_id);
	
	$next_param_str='?show_sf=1';
	$nextpage='validazioni_altri.php';
	
	$next_param_str .= '&organisationid='.$objdirezione->id;
	
	//gestione direzione padre come settore fittizio 
// 	$total_rows++;
// 	$sett_fittizio = 1;
// 	$table->data[] = get_datarow_tabella_sommario_direzione($objdirezione->id,$objdirezione->fullname,$objdirezione->shortname,$sett_fittizio);
// 	$num_settori_anomalie_validazioni = 0;
// 	$sett_fittizio = 0;
// 	foreach ($settori as $s)
// 	{
// 		$table->data[] = get_datarow_tabella_sommario_direzione($s->id,$s->fullname,$s->shortname,$sett_fittizio,$num_validazioni_inconsistenti);
// 	}
	foreach ($settori as $s)
	{
		$table->data[] = get_datarow_tabella_sommario_direzione($s->id,$s->fullname,$s->shortname,$s->isfittizio,$num_validazioni_inconsistenti,$anno_formativo);
	}

	$tabella_da_mostrare = 'sett';
}

$table_buttons = new html_table();
// $table_buttons->width = '80%';
// $align_buttons = array ('left','left','left','left');
// $table_buttons->align = $align_buttons;
// $table_buttons->attributes = array('class'=> '__anyunexistingclass__');

$html_output = '';

if (canView($userid,'dir') and !$mostra_dip_sett) // ref di direzione sommario direzione
{
	if ($check_anomalie_server == 1)
	{
        $settori_anomali = json_decode($check_settori_anomali);
        if(count($settori_anomali)) $settori_anomali = implode (', ', $settori_anomali);
		$html_output .= '<p style="text-align:center;color:red">'.get_string('anomalie_validaz_sett', 'block_f2_prenotazioni', $settori_anomali).'</p>';
// 		echo '<p style="color:red">'.get_string('anomalie_validaz_sett', 'block_f2_prenotazioni').'</p>';
	}
	$stato_budget = get_stato_validazione_by_dominio($settore_id);
	if ($budget_risp_server == 1)
	{
		if ($budget_flag === 1 and $stato_budget->stato_validaz_dir === 'D')
		{
			$extra_bdg_val = get_extra_budget() + 100;
			$html_output .=  '<br/><p style="color:red">'.get_string('bdg_non_rispettato', 'block_f2_prenotazioni').
			': '.get_string('bdg_non_rispettato_extra_bdg', 'block_f2_prenotazioni').' '.$extra_bdg_val.'%</p>';
// 			echo '<br/><p style="color:red">'.get_string('bdg_non_rispettato', 'block_f2_prenotazioni').
// 			': '.get_string('bdg_non_rispettato_extra_bdg', 'block_f2_prenotazioni').' '.$extra_bdg_val.'%</p>';
			
// 			print_r($anomalie_budget_arr);
			$tipo_corso_temp = '';
			foreach ($anomalie_budget_arr as $tipo=>$ba)
			{
				$tipo_corso = ((preg_match('/aula/', $tipo)) === 1) ? get_string('bdg_non_rispettato_aula', 'block_f2_prenotazioni') : get_string('bdg_non_rispettato_online', 'block_f2_prenotazioni');
				if ($tipo_corso !== $tipo_corso_temp)
				{
					$html_output .=  '<b>'.$tipo_corso.'</b><br/>';
// 					echo '<b>'.$tipo_corso.'</b><br/>';
				}
// 				else echo '<br/>';
				$html_output .=  '<ul>';
				$html_output .=  '<li>';
// 				echo '<ul>';
// 				echo '<li>';
				foreach ($ba as $bakey=>$bavalue)
				{
					$html_output .=  ' - '.get_string($bakey, 'block_f2_prenotazioni').' <b>'
							.$bavalue.'</b>';
// 					echo ' - '.get_string($bakey, 'block_f2_prenotazioni').' <b>'
// 							.$bavalue.'</b>';
				}
				$html_output .=  '</li>';
				$html_output .=  '</ul>';
// 				echo '</li>';
// 				echo '</ul>';
				$tipo_corso_temp = $tipo_corso;
			}
			$html_output .=  '<br/>';
// 			echo '<br/>';
		}
		else if ($stato_budget->stato_validaz_dir === 'E')
			// $budget_rispettato == true
		{
			$html_output .=  '<br/><p style="color:red">'.get_string('bdg_rispettato', 'block_f2_prenotazioni').'</p>';
// 			echo '<br/><p style="color:red">'.get_string('bdg_rispettato', 'block_f2_prenotazioni').'</p>';
		}
	}

	$table_buttons = get_tabella_bottoni_op_su_direzione($objdirezione->id, $settore_fittizio, $param_all_next,$num_validazioni_inconsistenti);
	$table_buttons_gen = get_tabella_bottoni_gen_direzione($objdirezione->id,$mostra_dip_sett);
	$html_output .=  html_writer::table($table_buttons_gen);
// 	echo html_writer::table($table_buttons_gen);
}
else
{
	if ($capo_sett)
	{
		$table_buttons = get_tabella_bottoni_settore($settore_id,$settore_fittizio,$param_all_next);
	}
	else if ($ref_dir_sup)
	{
		$table_buttons = get_tabella_bottoni_settore($settore_id,$settore_fittizio,$param_all_next);
		$table_buttons_dir_su_sett = get_tabella_bottoni_direzione($settore_id, $mostra_settore_fittizio, $param_all_next,$num_validazioni_inconsistenti);
		$table_buttons_gen = get_tabella_bottoni_gen_direzione($objdirezione->id,$mostra_dip_sett);
		$html_output .= html_writer::table($table_buttons_gen);
// 		echo html_writer::table($table_buttons_gen);
	}
}

//inizio tabella funzioni per direzione e settore
$tabella_funz_sett_dip_start = '<table width="100%" style="vertical-align:top"><tr><td style="vertical-align:top">';
$tabella_funz_sett_dip_end = '</td></tr></table>';
$html_output .= $tabella_funz_sett_dip_start;
// echo $tabella_funz_sett_dip_start;
if (isset($table_buttons_dir_su_sett) and !is_null($table_buttons_dir_su_sett) and !empty($table_buttons_dir_su_sett))
{
	$html_output .=  $OUTPUT->box_start();
	$html_output .=  html_writer::table($table_buttons_dir_su_sett);
	$html_output .=  $OUTPUT->box_end();
	$html_output .= '</td><td style="vertical-align:top">';
// 	echo $OUTPUT->box_start();
// 	echo html_writer::table($table_buttons_dir_su_sett);
// 	echo $OUTPUT->box_end();
// 	echo '</td><td style="vertical-align:top">';
}
$html_output .=  $OUTPUT->box_start();
$html_output .=  html_writer::table($table_buttons);
$html_output .=  $OUTPUT->box_end();
$html_output .=  $tabella_funz_sett_dip_end;
// echo $OUTPUT->box_start();
// echo html_writer::table($table_buttons);
// echo $OUTPUT->box_end();
// echo $tabella_funz_sett_dip_end;

if ($tabella_da_mostrare == 'dip')
{
	if ($mostra_settore_fittizio == 0)
	{
		$navbar_url = new moodle_url('/blocks/f2_prenotazioni/validazioni_altri.php?organisationid='.$settore_id);
	}
	else // mostra settore fittizio
	{
		$navbar_url = new moodle_url('/blocks/f2_prenotazioni/validazioni_altri.php?organisationid='.$settore_id.'&show_sf=1');
	}
	
	$PAGE->navbar->add(get_string('validazione_altri_dip', 'block_f2_prenotazioni'),$navbar_url);
	if ($total_rows > 0)
	{
		if ($num_validazioni_inconsistenti > 0)
		{
			$html_output .= '<br/><p style="color:red">'.get_string('validazioni_inconsistenti_sommario', 'block_f2_prenotazioni').'</p><br/>';
// 			echo '<br/><p style="color:red">'.get_string('validazioni_inconsistenti_sommario', 'block_f2_prenotazioni').'</p><br/>';
		}
		$msg_modifica_singolo_utente = '';
		if ((is_dominio_closed($settore_id,'sett') == true) and $capo_sett == true)
		{
			$msg_modifica_singolo_utente = '<p class="msg_feedback_info">'.get_string('msg_modifica_singolo_ut', 'block_f2_prenotazioni').' '.get_string('msg_modifica_singolo_ut_sett_closed', 'block_f2_prenotazioni').'</p>';
// 			echo '<p style="color:black"><u><b style="font-size:12px">'.get_string('msg_modifica_singolo_ut_sett_closed', 'block_f2_prenotazioni').'</b></u></p>';
		}
		else 
		{
			$msg_modifica_singolo_utente = '<p class="msg_feedback_info">'.get_string('msg_modifica_singolo_ut', 'block_f2_prenotazioni').'</p>';
		}
		$html_output .= $msg_modifica_singolo_utente;
		$html_output .= "<b style='font-size:11px'>Totale utenti: $total_rows</b>";
// 		echo $msg_modifica_singolo_utente;
// 		echo "<b style='font-size:11px'>Totale utenti: $total_rows</b>";
		$paging_bar = new paging_bar_f2($total_rows, $page, $perpage, $form_id, $post_extra);
		$html_output .= $paging_bar->print_paging_bar_f2();
		$html_output .= '<div style="font-size: 11px;">';
		$html_output .= html_writer::table($table);
		$html_output .= '</div>';
		$html_output .= $paging_bar->print_paging_bar_f2();
		$html_output .= "<input type=hidden id=numAnomalie value=".$num_validazioni_inconsistenti.">";
// 		echo $paging_bar->print_paging_bar_f2();
// 		echo '<div style="font-size: 11px;">';
// 		echo html_writer::table($table);
// 		echo '</div>';
// 		echo $paging_bar->print_paging_bar_f2();
// 		echo "<input type=hidden id=numAnomalie value=".$num_validazioni_inconsistenti.">";
				///FINE TABELLA DIPENDENTI
	}
	else //empty
	{
		$html_output .= '<br/><br/><p>'.get_string('noresults', 'block_f2_prenotazioni').'</p><br/>';
// 		echo '<br/><br/><p>'.get_string('noresults', 'block_f2_prenotazioni').'</p><br/>';
	}
}
else if ($tabella_da_mostrare = 'sett')
{
// 	$PAGE->navbar->add(get_string('xxxxxxxx', 'block_f2_prenotazioni'));
	if ($total_rows > 0)
	{
		if ($num_validazioni_inconsistenti > 0)
		{
			$html_output .= '<br/><p style="color:red">'.get_string('validazioni_inconsistenti_sommario', 'block_f2_prenotazioni').'</p><br/>';
// 			echo '<br/><p style="color:red">'.get_string('validazioni_inconsistenti_sommario', 'block_f2_prenotazioni').'</p><br/>';
		}
		$html_output .= "<p>Totale settori: $total_rows</p>";
// 		echo "<b style='font-size:11px'>Totale settori: $total_rows</b>";
		$paging_bar = new paging_bar_f2($total_rows, $page, $perpage, $form_id, $post_extra);
		$html_output .= $paging_bar->print_paging_bar_f2();
		$html_output .= '<div style="font-size: 11px;">';
		$html_output .= html_writer::table($table);
		$html_output .= '</div>';
		$html_output .= $paging_bar->print_paging_bar_f2();
		$html_output .= "<input type=hidden id=numAnomalie value=".$num_validazioni_inconsistenti.">";
// 		echo $paging_bar->print_paging_bar_f2();
// 		echo '<div style="font-size: 11px;">';
// 		echo html_writer::table($table);
// 		echo '</div>';
// 		echo $paging_bar->print_paging_bar_f2();
// 		echo "<input type=hidden id=numAnomalie value=".$num_validazioni_inconsistenti.">";
		//FINE TABELLA settori
	}
	else //empty
	{
		$html_output .= '<br/><br/><p>'.get_string('noresults', 'block_f2_prenotazioni').'</p><br/>';
// 		echo '<br/><br/><p>'.get_string('noresults', 'block_f2_prenotazioni').'</p><br/>';
	}
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('validazione_altri', 'block_f2_prenotazioni'));
echo $OUTPUT->box_start();
echo html_writer::table($table_sd);
$mform->display();
echo $html_output;

echo $OUTPUT->box_end();
echo $OUTPUT->footer();