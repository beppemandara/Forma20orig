<?php

require_once '../../../config.php';
require_once '../lib.php';

// Setup the page
// $PAGE->set_url(new moodle_url('test.php'));
// $PAGE->set_url('/blocks/f2_gestione_risorse/sessioni/popup.php');
// $PAGE->set_context(get_system_context());
// $PAGE->set_title('Confirm + Popup test');
// $PAGE->set_heading($PAGE->title);
// $baseurl = new moodle_url('/blocks/f2_gestione_risorse/sessioni/sessioni.php');
// $baseurl2 = new moodle_url('/blocks/f2_gestione_risorse/sessioni/cancall.php');

$context = get_context_instance(CONTEXT_SYSTEM);
require_login();
require_capability('block/f2_gestione_risorse:editsessioni', $context);

$anno_formativo = optional_param('anno', 0, PARAM_INT);
$cancella_tutto = optional_param('cancall', 0, PARAM_INT);
$cancella_id = optional_param('cancid', 0, PARAM_INT);
$baseurl = new moodle_url('/blocks/f2_gestione_risorse/sessioni/sessioni.php');

if ($cancella_id > 0)
{
	delete_session($cancella_id);
// 	redirect(new moodle_url('sessioni.php'));
	// header("location: ".$baseurl);
	redirect(new moodle_url($baseurl));
}
else if ($anno_formativo == 0 or $cancella_tutto == 0)
{
// 	redirect(new moodle_url('sessioni.php'));
	// header("location: ".$baseurl);
	redirect(new moodle_url($baseurl));
}
else if($cancella_tutto == 1) //cancella dati da db
{
	delete_all_session($anno_formativo);
// 	redirect(new moodle_url('sessioni.php'));
	// header("location: ".$baseurl);
	redirect(new moodle_url($baseurl));
}
else
{
	redirect(new moodle_url($baseurl));
}

// Output everything
// echo $OUTPUT->header();

// echo $OUTPUT->footer();