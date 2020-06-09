<?php

//$Id: funzionalita.php 1234 2013-12-11 12:09:34Z l.moretto $

require_once '../../../config.php';
require_once 'funzionalita_form.php';
// require_once($CFG->dirroot.'/f2_lib/core.php');
// require_once($CFG->dirroot.'/f2_lib/management.php');

// old sumtotal: TBL_EML_STATI_FUNZ
//$context = get_context_instance(CONTEXT_SYSTEM);
$context = context_system::instance();
require_login();
require_capability('block/f2_gestione_risorse:viewfunzionalita', $context);
require_capability('block/f2_gestione_risorse:editfunzionalita', $context);

// $page         = optional_param('page', 0, PARAM_INT);
// $perpage      = optional_param('perpage', 20, PARAM_INT); 

// $userid       = optional_param('userid', 0, PARAM_INT); 
// $c_exp_type   = optional_param('c_exp_type', 0, PARAM_INT); 
// $direction   = optional_param('dir', 'DESC', PARAM_ACTION); 
// $sort  		 = optional_param('sort', 'data_inizio', PARAM_ACTION); 

// if($userid==0) $userid=$USER->id;
// else if($userid!=0 && has_capability('block/f2_gestione_risorse:viewdipendenticurricula', get_context_instance(CONTEXT_SYSTEM)) && validate_own_dipendente($userid)) $userid=$userid;
// else die();

$baseurl = new moodle_url('/blocks/f2_gestione_risorse/funzionalita/funzionalita.php');
$blockname = get_string('pluginname', 'block_f2_gestione_risorse');

$PAGE->set_pagelayout('standard');
$PAGE->set_context($context);
$PAGE->set_url('/blocks/f2_gestione_risorse/funzionalita/funzionalita.php');
$PAGE->set_title(get_string('funzionalita', 'block_f2_gestione_risorse'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('gestione_funzionalita', 'block_f2_gestione_risorse'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);

$form = new funzionalita_form(null);

if ($form->is_cancelled())
{
	redirect($baseurl);
}
else if ($data = $form->get_data())
{
	echo $OUTPUT->header();
	echo $OUTPUT->heading(get_string('funzionalita', 'block_f2_gestione_risorse'));
	echo $OUTPUT->box_start();
$files = '';
	$err = $form->validation($data, $files);
	if (count($err) == 0) 
	{
		//save data on DB
		$sessionids = explode('|',$data->sessionids);
		$funzrs = get_funzionalitaRS();
		$funz_data = $funzrs->dati;
		$dataarr = (array) $data;
		foreach ($funz_data as $f)
		{
			$updt = new stdClass;
			$updt->id = $f->id;
			$updt->aperto = ($dataarr[$f->id] == 1 ? 's' : 'n');
			update_funzionalita($updt);
		}
		foreach ($sessionids as $s)
		{
			$updt = new stdClass;
			$updt->id = $s;
			$updt->stato = ($dataarr['sessione_id_'.$s] == 1 ? 'a' : 'c');
			update_sessione($updt);
		}
		$form = new funzionalita_form(null);
		echo '<p class="notifysuccess">'.get_string('data_saved','block_f2_gestione_risorse').'</p>';
		$form->display();
	}
	else
	{
		foreach ($err as $e)
		{
			echo '<p class="error" style="text-align:center;">'.$e.'</p>';
		}
		$form->display();
	}
}
else // all'inizio
{
	echo $OUTPUT->header();
	echo $OUTPUT->heading(get_string('funzionalita', 'block_f2_gestione_risorse'));
	echo $OUTPUT->box_start();
	$form->display();
}

$str = <<<'EFO'
<script type="text/javascript">
//<![CDATA[
document.getElementById('nameforyourheaderelement').setAttribute('style','border: 0px solid !important;background-color: white !important;');
//]]>
</script>
EFO;
echo $str;

$str1 = "
<STYLE type=\"text/css\">
fieldset {background-color: white !important;border: 0px !important; padding-top:4px !important;padding-bottom:4px !important;}
</STYLE>";
echo $str1;


echo $OUTPUT->box_end();
echo $OUTPUT->footer();
