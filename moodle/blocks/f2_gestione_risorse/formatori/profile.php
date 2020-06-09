<?php
// $Id: profile.php 1177 2013-06-20 11:33:57Z d.lallo $

require_once '../../../config.php';
require_once "f2_anagrafica_formatori_form.php";
require_once '../lib.php';

require_capability('block/f2_gestione_risorse:aggiungi_formatore', get_context_instance(CONTEXT_SYSTEM));
require_capability('block/f2_gestione_risorse:modifica_formatore', get_context_instance(CONTEXT_SYSTEM));
require_capability('block/f2_gestione_risorse:vedi_lista_formatori', get_context_instance(CONTEXT_SYSTEM));

if (has_capability('block/f2_gestione_risorse:vedi_lista_utenti', get_context_instance(CONTEXT_SYSTEM))
	)
{

	global $USER, $CFG;
	//if (!isSupervisore($USER->id)) die();
	$formatore_id = optional_param('formatore_id', 0, PARAM_INT);
	$saved = optional_param('save', 0, PARAM_INT);
	$action = $_POST['action'];

	$http_method = -1;
	if (!isset($action))
	{
		$action = 0; //update formatore
	}
	if ($action != 0)
	{	
            if (is_array($_POST['userid']))
		$formatore_id = $_POST['userid'][0]; //insert formatore
            else
		$formatore_id = $_POST['userid']; //insert formatore
	}

	$blockname = get_string('pluginname', 'block_f2_gestione_risorse');
	$header = get_string('anagrafica_formatori_gestione', 'block_f2_gestione_risorse');
	$header2 = get_string('anagrafica_formatori_aggiunta', 'block_f2_gestione_risorse');
	$header3 = get_string('dettagli', 'block_f2_gestione_risorse');
	if ($action == 0) $header3url = new moodle_url('profile.php',array('formatore_id'=>$formatore_id));
	else $header3url = null;

	require_login();
	$context = get_context_instance(CONTEXT_SYSTEM);

	$PAGE->set_context($context);
	$PAGE->set_url('/blocks/f2_gestione_risorse/formatori/profile.php', array('formatore_id'=>$formatore_id));
	$PAGE->set_pagelayout('standard');
	$PAGE->settingsnav;
	// $PAGE->navbar->add($blockname,new moodle_url('.'));
	$PAGE->navbar->add($header,new moodle_url('.'));
	if ($action == 1) $PAGE->navbar->add($header2,new moodle_url('add_formatore.php'));
	$PAGE->navbar->add($header3,$header3url);
	$PAGE->set_heading($SITE->shortname.': '.$blockname);

	if ($action != 1) // update formatore
	{
		$userSQL = get_detail_formatoreSQL($formatore_id);
		$form_subaf_map_rs = array_keys(get_form_subaf_mapRS($formatore_id));
	}
	else // insert formatore
	{
		$userSQL = get_detail_userSQL($formatore_id);
	}

	$user = $DB->get_record_sql($userSQL);


	if (!empty($user))
	{
		$form = new anagrafica_formatori_form2();
		$form->set_data(array('userid' => $user->id));
		$form->set_data(array('formatore_id' => $user->formatore_id));
		$form->set_data(array('action' => $action));

		if ($user->ente) $form->set_data(array('ente' => $user->ente));
		if ($user->piva) $form->set_data(array('piva' => $user->piva));
		if ($user->tstudio) $form->set_data(array('tstudio' => $user->tstudio));
		if ($user->dettstudio) $form->set_data(array('dettstudio' => $user->dettstudio));
		if ($user->prof) $form->set_data(array('prof' => $user->prof));
		if ($user->categoria) 
		{
			if ($user->categoria == 'I') $form->set_data(array('flag_interno' => 1));
			else $form->set_data(array('flag_interno' => 0));
		}
		if ($user->categoria == 'I')
		{
			if ($user->tipodoc) 
			{
				if ($user->tipodoc == 'I' or $user->tipodoc == 'E') $form->set_data(array('tipodoc' => $user->tipodoc));
				else if ($user->tipodoc == 'T')
				{
					 $form->set_data(array('tipodoc' => array('E','I')));
				}
			}
		}
		if (!empty($form_subaf_map_rs))
		{
			$form->set_data(array('aree_form' => $form_subaf_map_rs));
		}
		
		if ($form->is_cancelled()) 
		{
			if ($action == 1) redirect(new moodle_url('add_formatore.php'));
			else redirect(new moodle_url('index.php'));
		}
		else if ($data = $form->get_data())
		{
			$updt = new stdClass();
			if ($action == 0) $updt->id = $data->formatore_id;
			if ($data->userid) $updt->usrid = $data->userid; 
			if ($data->ente) $updt->ente = $data->ente;
			if ($data->piva) $updt->piva = $data->piva;
			if ($data->prof) $updt->prof = $data->prof;
			if ($data->dettstudio) $updt->dettstudio = $data->dettstudio;
			if ($data->tstudio) $updt->tstudio = $data->tstudio;
			if ($data->flag_interno) 
			{
				$updt->categoria = 'I';
				if (count($data->tipodoc) == 2)
				{
					$updt->tipodoc = 'T';
				}
				else if (count($data->tipodoc) == 1)
				{
					$updt->tipodoc = $data->tipodoc[0];
				}
				else //nessuna selezione interno generico
				{
					$updt->tipodoc = null;
				}
			}
			else // docente esterno, di default nessun tipodocenza
			{
				$updt->categoria = 'E';
				$updt->tipodoc = null;
			}
			if ($data->aree_form)
			{	
				$updt->subafids = $data->aree_form;
				// print_r($data->aree_form);exit;
			}
			$updt->lstupd = time();
			if ($action == 1) //insert formatore
			{
				$inserted = insert_formatore($data->userid,$updt);
				redirect(new moodle_url('profile.php?formatore_id='.$inserted.'&save=1'));
				// header('location: profile.php?formatore_id='.$inserted.'&save=1');
			}
			else // action == 0 update formatore
			{
// 				
				update_formatore($data->formatore_id,$updt);
// 				print_r($updt);
				redirect(new moodle_url('profile.php?formatore_id='.$data->formatore_id.'&save=1'));
				// header('location: profile.php?formatore_id='.$data->formatore_id.'&save=1');
			}
		}

		echo $OUTPUT->header();
		echo $OUTPUT->heading(get_string('dettagli', 'block_f2_gestione_risorse'));
		echo $OUTPUT->box_start();
		if ($saved == 1) echo '<h2 align="center" style="color:green;margin-top:-20px;">'.get_string('data_saved', 'block_f2_gestione_risorse').'</h2>';
		// Print all the little details in a list
		echo print_summary($user);

		echo $form->display();
	}
	else 
	{
		echo $OUTPUT->header();
		echo $OUTPUT->heading(get_string('dettagli', 'block_f2_gestione_risorse'));
		echo $OUTPUT->box_start();

		echo '<br/><br/><p>'.get_string('noresults', 'block_f2_gestione_risorse').'</p><br/>';
	}
        
        $back = "{$CFG->wwwroot}/blocks/f2_gestione_risorse/formatori/index.php";
        echo '<a href="'.$back.'" >'.get_string('torna_indietro', 'block_f2_gestione_risorse').'</a>';
        
	echo $OUTPUT->box_end();
	echo $OUTPUT->footer();
}
else 
{
	die;
}