<?php
// $Id$
global $CFG,$DB,$OUTPUT;

require_once('../../config.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/local/f2_course/extends_course.php');
require_once($CFG->dirroot.'/local/f2_support/lib.php');
require_once($CFG->dirroot.'/f2_lib/core.php');
require_once($CFG->dirroot.'/f2_lib/report.php');
require_once($CFG->dirroot.'/local/f2_notif/lib.php');

$courseid = $_POST[id_corsoid];
$sessionid = $_POST[id_sessionid];
$id_templ_course = $_POST[id_temp_course];
require_login();

$context = get_context_instance(CONTEXT_SYSTEM);

if (empty($CFG->loginhttps)) {
	$securewwwroot = $CFG->wwwroot;
} else {
	$securewwwroot = str_replace('http:','https:',$CFG->wwwroot);
}

$PAGE->set_context($context);

// basic access control checks
if ($courseid) { // editing course
    if ($courseid == SITEID){
        // don't allow editing of  'site course' using this from
        print_error('cannoteditsiteform');
    }

    $course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
    require_login($course);
    $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
    require_capability('moodle/course:update', $coursecontext);
  //  if($DB->record_exists('f2_anagrafica_corsi', array('courseid'=>$courseid)))
  //  	$anag_course = $DB->get_record('f2_anagrafica_corsi', array('courseid'=>$courseid), '*', MUST_EXIST);
  // 	else
  // 		$anag_course=NULL;

} else {
    require_login();
    print_error('Per poter continuare devi compilare la scheda corso.');
}


$fullname = $course->fullname;
$baseurl = new moodle_url('/local/f2_notif/template_course.php', array('courseid'=>$course->id));
$PAGE->set_url($baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
$PAGE->set_title(get_string('modelli_notifica', 'local_f2_notif'));
$PAGE->navbar->add('Corsi');
$PAGE->navbar->add($fullname,new moodle_url($CFG->wwwroot.'/course/view.php', array('id'=>$course->id)));
$PAGE->navbar->add(get_string('modelli_notifica', 'local_f2_notif'), new moodle_url($baseurl, array('courseid'=>$course->id)));
$PAGE->set_pagelayout('standard');
$PAGE->settingsnav;

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('delete_notif', 'local_f2_notif'));
echo $OUTPUT->box_start();
echo '<div class="contenitoreglobale">';
//CONTROLLO SE E' STATO SELEZIONATO UN FORNITORE
if($id_templ_course){
$esito=1;
	foreach($id_templ_course as $id){
		if(delete_template_course($id_templ_course)){
			$esito=1;
		}
		else {
		$esito=0;
			
		}
	}

	if($esito){
			echo '<b>Il template dell\' edizione è stato eliminato/i correttamente.</b><br>';
			echo 'Seleziona il pulsante "Indietro" per tornare alla pagina precedente.<br><br>';
			echo '<a href="template_sessions.php?courseid='.$courseid.'&sessionid='.$sessionid.'"><button type="button">Indietro</button></a>';
	}else{
		echo 'Errore nell\'eliminazione!!';
	}
}else{ 
		echo '<b>Non &egrave; stato selezionato nessun template.</b><br>';
		echo '<a href="template_sessions.php?courseid='.$courseid.'&sessionid='.$sessionid.'"><button type="button">Indietro</button></a>';
}

echo '</div>';

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
?>