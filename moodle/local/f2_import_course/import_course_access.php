<?php

require_once('../../config.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/local/f2_import_course/lib.php');
//require_once($CFG->dirroot.'/f2_lib/core.php');


//$courseid         = required_param('courseid', PARAM_INT);       		  // course id


global $PAGE,$DB,$OUTPUT;

@set_time_limit(60*60); // 1 hour should be enough

$baseurl = new moodle_url($CFG->wwwroot.'/local/f2_import_course/import_course_access.php');

require_login(SITEID);

$context = get_context_instance(CONTEXT_COURSE, SITEID);
require_capability('local/f2_import_course:importcourseaccess', $context);


$PAGE->set_pagelayout('admin');
$PAGE->set_url($baseurl);


// first create the form
class import_course_access_form extends moodleform {
	public function definition() {
		$mform 			= &$this->_form;
		
		$mform->addElement('header','general', get_string('fieldset_title','local_f2_import_course'));
		
		$mform->addElement('filemanager', 'course_file_access', 'carica file access', null,
                    array('subdirs' => 0, 'maxfiles' => 1,
                          'accepted_types' => array('*.mdb') ));
                
        $mform->addElement('select', 'categoria', get_string('categoria', 'local_f2_import_course'), get_categories_for_catalog());

		$this->add_action_buttons();
		
//		$this->set_data($anag_course); /// finally set the current form data
		
	}
	
}

$editform = new import_course_access_form(NULL);


if ($editform->is_cancelled()) {
        redirect($baseurl);

}


// Print the form

$site = get_site();

$PAGE->navbar->add(get_string('navbartitle','local_f2_import_course'));
$title = get_string('title_import_course_access','local_f2_import_course');

$PAGE->set_title($title);
$PAGE->set_heading($title);



echo $OUTPUT->header();

echo $OUTPUT->heading($title);

$editform->display();

if ($data = $editform->get_data()) {
	$importresults = import_course_from_access($data);
        echo $OUTPUT->box_start('boxwidthnarrow boxaligncenter generalbox', 'importresults');
        echo '<p>';
        echo get_string('corsi_da_importare', 'local_f2_import_course').': '.$importresults->corsi_da_importare.'</p>';
        echo get_string('corsi_importati', 'local_f2_import_course').': '.$importresults->corsi_importati.'</p>';
        echo get_string('corsi_aggiornati', 'local_f2_import_course').': '.$importresults->corsi_aggiornati.'</p>';
        echo get_string('warnings', 'local_f2_import_course').': '.$importresults->warnings.'</p>';
        echo get_string('anomalie', 'local_f2_import_course').': '.$importresults->anomalie.'</p>';
        echo $OUTPUT->box_end();
        
        if ($importresults->warnings > 0) {
            echo $OUTPUT->box_start('generalbox userinfobox boxaligncenter boxwidthnormal');
            foreach ($importresults->elenco_warning as $warning) {
                echo $warning.'</p>';
            }
            echo $OUTPUT->box_end();
            echo '</br>';
        }
        
        if ($importresults->anomalie > 0) {
            echo $OUTPUT->box_start('errorbox errorboxcontent boxaligncenter boxwidthnormal');
            foreach ($importresults->elenco_anomalie as $anomalia) {
                echo $anomalia.'</p>';
            }
            echo $OUTPUT->box_end();
        }
//    redirect($baseurl);
}

echo $OUTPUT->footer();

