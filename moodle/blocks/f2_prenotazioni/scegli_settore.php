<?php
//$Id$

require_once '../../config.php';
require_once 'lib.php';
require_once($CFG->dirroot.'/lib/formslib.php');

// inizio import per generazione albero //
$PAGE->requires->js('/f2_lib/jquery/jquery-1.7.1.min.js');
$PAGE->requires->js('/f2_lib/jquery/jquery-ui.min.js');
$PAGE->requires->js('/f2_lib/jquery/jquery.cookie.js');
$PAGE->requires->js('/f2_lib/jquery/jquery.dynatree.js');
$PAGE->requires->css('/f2_lib/jquery/css/skin/ui.dynatree.css');
$PAGE->requires->js('/f2_lib/jquery/jquery.blockUI.js');
// fine import per generazione albero //

// $organisation = get_user_organisation($USER->id);
// $organisation_id = $organisation[0];
// $viewable = get_user_viewable_organisation($USER->id);
// $viewableid = $viewable[0];

$settore_non_valido  = optional_param('inv', 0, PARAM_INT);
$anno_formativo  = optional_param('anno', 0, PARAM_INT);
if($anno_formativo==0) $anno_formativo=get_anno_formativo_corrente();

class scegli_settore_form extends moodleform 
{
	// Define the form
	function definition () {
		global $DB,$USER;
	
		$mform =& $this->_form;
		//$organisation = get_user_organisation($USER->id);
		$organisation = get_user_viewable_organisation($USER->id);
		$organisation_id = $organisation[0];
		$organisation_title = $organisation[1];
		$hierarchy = recursivesubtreejson($organisation_id, $organisation_title);
		$mform->addElement('static', 'organisationselector', 
				get_string('scegli_settore', 'block_f2_prenotazioni'), 
				get_organisation_picker_html('organisationtitle', 'organisationid', 
						get_string('scegli_settore_apri', 'block_f2_prenotazioni'), 
						'domini',$hierarchy, '  '.$organisation_title));
		$mform->addElement('hidden', 'organisationid');
		$mform->setType('organisationid', PARAM_INT);
		$mform->setDefault('organisationid', $organisation_id ? $organisation_id : 0);
		
        $buttonarray = array();
        $buttonarray[] =& $mform->createElement('submit', 'send', get_string('conferma', 'block_f2_prenotazioni'));
        $buttonarray[] =& $mform->createElement('button', 'cancelbtn', get_string('annulla', 'block_f2_prenotazioni'),
        		array('onclick' => "document.location.href='".new moodle_url("/")."'"));
		$mform->addGroup($buttonarray, 'actions', '', array(' '), false);
	}
}

$blockname = get_string('pluginname_validazione', 'block_f2_prenotazioni');
$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/f2_prenotazioni/scegli_settore.php');
$PAGE->set_title(get_string('pluginname_validazione', 'block_f2_prenotazioni'));
$PAGE->settingsnav;
$PAGE->set_heading($SITE->shortname.': '.$blockname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('scegli_settore', 'block_f2_prenotazioni'));
echo $OUTPUT->box_start();

$mform = new scegli_settore_form('validazioni_altri.php');
if ($settore_non_valido > 0)
{
	echo '<p class="msg_feedback_ko">'.get_string('sett_non_valido', 'block_f2_prenotazioni').'</p>';
}
$mform->display();

if($mform->is_cancelled())
{
// 	print_r('asdfadsf');
}
else if ($data = $mform->get_data())
{
// 	print_r($data);
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer();