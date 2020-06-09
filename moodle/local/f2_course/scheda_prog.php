<?php
/*
 * $Id: scheda_prog.php 1389 2015-05-13 12:36:15Z l.moretto $
 */
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
$saved         = optional_param('saved', false, PARAM_BOOL);


/*
 *    if ($id == SITEID){
        // don't allow editing of  'site course' using this from
        print_error('cannoteditsiteform');
    }
 * 
 */

global $PAGE,$DB,$OUTPUT;


$baseurl = new moodle_url($CFG->wwwroot.'/local/f2_course/scheda_prog.php', array('courseid'=>$courseid));

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
    if($DB->record_exists('f2_scheda_progetto', array('courseid'=>$courseid)))
    	$scheda_progetto = $DB->get_record('f2_scheda_progetto', array('courseid'=>$courseid), '*', MUST_EXIST);
   	else
   		$scheda_progetto=NULL;

} else {
    require_login();
    print_error('per poter continuare devi compilare la scheda corso');
}

// first create the form
class course_scheda_prog extends moodleform {
	public function definition() {
		$mform 			=& $this->_form;
		$courseid       = $this->_customdata['courseid'];   			// course id
		$scheda_progetto    = $this->_customdata['scheda_progetto'];    // this contains the data of this form
		
	//	$mform->addElement('header','general', 'custom CSI 2');
		
		$mform->addElement('hidden', 'courseid',$courseid);

		$mform->addElement('text','sede_corso', get_string('sede_corso','local_f2_course'),'maxlength="100" size="50"');
		$mform->addHelpButton('sede_corso', 'sede_corso','local_f2_course');
		$mform->addRule('sede_corso', null, 'required', null, 'client');
		
		$mform->addElement('textarea', 'destinatari', get_string('destinatari','local_f2_course'), 'wrap="virtual" rows="5" cols="47"');
		$mform->addHelpButton('destinatari', 'destinatari','local_f2_course');
		$mform->addRule('destinatari', null, 'required', null, 'client');
		
		$mform->addElement('textarea', 'accesso', get_string('accesso','local_f2_course'), 'wrap="virtual" rows="5" cols="47"');
		$mform->addHelpButton('accesso', 'accesso','local_f2_course');
		$mform->addRule('accesso', null, 'required', null, 'client');		
		
		$mform->addElement('textarea', 'obiettivi', get_string('obiettivi','local_f2_course'), 'wrap="virtual" rows="5" cols="47"');
		$mform->addHelpButton('obiettivi', 'obiettivi','local_f2_course');
		$mform->addRule('obiettivi', null, 'required', null, 'client');		
		
		$mform->addElement('checkbox', 'pfa', get_string('pfa', 'local_f2_course'));
		$mform->addHelpButton('pfa', 'pfa','local_f2_course');
		
		$mform->addElement('checkbox', 'pfb', get_string('pfb', 'local_f2_course'));
		$mform->addHelpButton('pfb', 'pfb','local_f2_course');
		
		$mform->addElement('checkbox', 'pfc', get_string('pfc', 'local_f2_course'));
		$mform->addHelpButton('pfc', 'pfc','local_f2_course');		
		
		$mform->addElement('checkbox', 'pfd', get_string('pfd', 'local_f2_course'));
		$mform->addHelpButton('pfd', 'pfd','local_f2_course');		
		
		$mform->addElement('checkbox', 'pfdir', get_string('pfdir', 'local_f2_course'));
		$mform->addHelpButton('pfdir', 'pfdir','local_f2_course');
		
		$mform->addElement('static', 'pf_note', get_string('pf_note', 'local_f2_course'),
    	get_string('pf_note_descr', 'local_f2_course'));
		
		$mform->addElement('text','met1', get_string('met1','local_f2_course'),'maxlength="3" size="3"');
		$mform->addHelpButton('met1', 'met1','local_f2_course');
		$mform->addRule('met1', null, 'required', null, 'client');

		$mform->addElement('text','met2', get_string('met2','local_f2_course'),'maxlength="3" size="3"');
		$mform->addHelpButton('met2', 'met2','local_f2_course');
		$mform->addRule('met2', null, 'required', null, 'client');
		
		$mform->addElement('text','met3', get_string('met3','local_f2_course'),'maxlength="3" size="3"');
		$mform->addHelpButton('met3', 'met3','local_f2_course');
		$mform->addRule('met3', null, 'required', null, 'client');
		
		$mform->addElement('textarea', 'monitoraggio', get_string('monitoraggio','local_f2_course'), 'wrap="virtual" rows="5" cols="47"');
		$mform->addHelpButton('monitoraggio', 'monitoraggio','local_f2_course');

		$mform->addElement('textarea', 'valutazione', get_string('valutazione','local_f2_course'), 'wrap="virtual" rows="5" cols="47"');
		$mform->addHelpButton('valutazione', 'valutazione','local_f2_course');

		$mform->addElement('textarea', 'apprendimento', get_string('apprendimento','local_f2_course'), 'wrap="virtual" rows="5" cols="47"');
		$mform->addHelpButton('apprendimento', 'apprendimento','local_f2_course');

		$mform->addElement('textarea', 'ricaduta', get_string('ricaduta','local_f2_course'), 'wrap="virtual" rows="5" cols="47"');
		$mform->addHelpButton('ricaduta', 'ricaduta','local_f2_course');
		$mform->addRule('ricaduta', null, 'required', null, 'client');		

		$mform->addElement('text','first', get_string('first','local_f2_course'),'maxlength="4" size="4"');
		$mform->addHelpButton('first', 'first','local_f2_course');
        $mform->addRule('first', null, 'numeric', null, 'client');
		
		$mform->addElement('text','last', get_string('last','local_f2_course'),'maxlength="4" size="4"');
		$mform->addHelpButton('last', 'last','local_f2_course');
        $mform->addRule('last', null, 'numeric', null, 'client');
		
		$mform->addElement('text','rev', get_string('rev','local_f2_course'),'maxlength="2" size="2"');
		$mform->addHelpButton('rev', 'rev','local_f2_course');
        $mform->addRule('rev', null, 'numeric', null, 'client');
		
		$mform->addElement('textarea', 'dispense_vigenti', get_string('dispense_vigenti','local_f2_course'), 'wrap="virtual" rows="5" cols="47"');
		$mform->addHelpButton('dispense_vigenti', 'dispense_vigenti','local_f2_course');
		$mform->addRule('dispense_vigenti', null, 'required', null, 'client');
		
		$mform->addElement('textarea', 'contenuti', get_string('contenuti','local_f2_course'), 'wrap="virtual" rows="5" cols="47"');
		$mform->addHelpButton('contenuti', 'contenuti','local_f2_course');
		$mform->addRule('contenuti', null, 'required', null, 'client');
		
		/*
		$mform->addElement('text','a', get_string('a','local_f2_course'),'maxlength="32" size="50"');
		$mform->addHelpButton('a', 'a','local_f2_course');
		$mform->addRule('a', null, 'required', null, 'client');
		*/
		
		$this->add_action_buttons();
		
		$this->set_data($scheda_progetto); /// finally set the current form data
		
	}
	
	/// perform some extra moodle validation
	function validation($data) {
		global $DB, $CFG;
		
		$errors=array();
		if(!isset($data['pfa']) && !isset($data['pfb']) && !isset($data['pfc']) && !isset($data['pfd']) && !isset($data['pfdir'])){
			$errors['pfa']= get_string('error_pf','local_f2_course','pfa');
		}
		if(!filter_var($data['met1'],FILTER_VALIDATE_INT) || $data['met1']>100 ){
		 	$errors['met1']= get_string('error_met1','local_f2_course','met1');
		 }
		if(!filter_var($data['met2'],FILTER_VALIDATE_INT) || $data['met2']>100 ){
		 	$errors['met2']= get_string('error_met2','local_f2_course','met2');
		 }
		if(!filter_var($data['met3'],FILTER_VALIDATE_INT) || $data['met3']>100 ){
		 	$errors['met3']= get_string('error_met3','local_f2_course','met3');
		 }
		if(strlen($data['first']) > 0  && ($data['first']<0 || $data['first']>9999)){
		 	$errors['first']= get_string('error_first','local_f2_course','first');
		 }
		if(strlen($data['last']) > 0  && ($data['last']<0 || $data['last']>9999)){
		 	$errors['last']= get_string('error_last','local_f2_course','last');
		 }
		if(strlen($data['rev']) > 0  && ($data['rev']<0 || $data['rev']>99)){
		 	$errors['rev']= get_string('error_rev','local_f2_course','rev');
		 }
		return $errors;
	}
}

$editform = new course_scheda_prog(NULL, array('courseid'=>$courseid,'scheda_progetto'=>$scheda_progetto));


if ($editform->is_cancelled()) {
        redirect($baseurl);

} else if ($data = $editform->get_data()) { 
   		// process data if submitted
        manage_scheda_progetto($data);
        
        $baseurl = new moodle_url($CFG->wwwroot.'/local/f2_course/scheda_prog.php', array('courseid'=>$courseid, 'saved'=>true));
        redirect($baseurl);
}


// Print the form

$site = get_site();

$PAGE->navbar->add(get_string('custom_scheda_progetto','local_f2_course'));
$title = get_string('title_scheda_progetto','local_f2_course');

$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();

$test = new extends_f2_course($courseid);
$test->print_tab_edit_course('scheda_progetto');

echo $OUTPUT->heading($title);

if ($saved)
    echo $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');

$editform->display();

echo $OUTPUT->footer();

