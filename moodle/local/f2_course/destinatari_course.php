<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Edit course settings
 *
 * @package    moodlecore
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/formslib.php');
require_once('extends_course.php');
require_once($CFG->dirroot.'/local/f2_support/lib.php');
require_once($CFG->dirroot.'/f2_lib/core.php');
// require_once('lib.php');

$courseid         = required_param('courseid', PARAM_INT);       		  // course id

/*
 *    if ($id == SITEID){
        // don't allow editing of  'site course' using this from
        print_error('cannoteditsiteform');
    }
 * 
 */

global $PAGE,$DB,$OUTPUT;


$baseurl = new moodle_url($CFG->wwwroot.'/local/f2_course/destinatari_course.php', array('courseid'=>$courseid));

$PAGE->set_pagelayout('admin');
$PAGE->set_url($baseurl);

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
    if($DB->record_exists('f2_anagrafica_corsi', array('courseid'=>$courseid))){
    	$anag_course = $DB->get_record('f2_anagrafica_corsi', array('courseid'=>$courseid), '*', MUST_EXIST);
    	$destinatari_course = $DB->get_records('f2_corsi_coorti_map', array('courseid'=>$courseid));
    }
   	else{
   		$anag_course=NULL;
   		$destinatari_course=NULL;
   		}

} else {
    require_login();
    print_error('per poter continuare devi compilare la scheda corso');
}

// first create the form
class course_destinatari_form extends moodleform {
	public function definition() {
		$mform 			=& $this->_form;
		$courseid         = $this->_customdata['courseid'];   				  // course id
		$anag_course    = $this->_customdata['anag_course'];    // this contains the data of this form
		$destinatari_course    = $this->_customdata['destinatari_course'];
		$destinatari_obj=new stdClass();
		$destinatari_obj->destinatari=array();
			if(!is_null($destinatari_course)){
				foreach($destinatari_course as $destinatari_db)
					$destinatari_obj->destinatari[]=$destinatari_db->coorteid;
			}
		
		if($anag_course->course_type<>2){
			print_error('scheda compilabile solo per i corsi obiettivo');
			}
		//	$mform->addElement('header','general', 'custom CSI 2');
			
			$mform->addElement('hidden', 'courseid',$courseid);
			
			$attributes=array();
			
			$coorti=array();
			foreach(get_cohort_from_macrocategory() as $coorte)
				$coorti[$coorte->cohortid]=$coorte->macrocategory;
			
			$select = &$mform->addElement('select', 'destinatari', get_string('destinatari','local_f2_course'),$coorti, $attributes);
			$select->setMultiple(true);
			
		$this->add_action_buttons();
		
		$this->set_data($destinatari_obj); /// finally set the current form data
		
	}
	
	/// perform some extra moodle validation
	function validation($data) {
		$errors=array();
		return $errors;
		
	}
}

$editform = new course_destinatari_form(NULL, array('courseid'=>$courseid,'anag_course'=>$anag_course,'destinatari_course'=>$destinatari_course));


if ($editform->is_cancelled()) {
        redirect($baseurl);

} else if ($data = $editform->get_data()) {
   		// process data if submitted
        create_update_destinatari_course($data);

    redirect($baseurl);
}


// Print the form

$site = get_site();

$PAGE->navbar->add(get_string('custom_destinatari','local_f2_course'));
$title = get_string('title_destinatari','local_f2_course');

$PAGE->set_title($title);
$PAGE->set_heading($title);



echo $OUTPUT->header();

$test = new extends_f2_course($courseid);
$test->print_tab_edit_course('destinatari_course');

echo $OUTPUT->heading($title);

$editform->display();

echo $OUTPUT->footer();

