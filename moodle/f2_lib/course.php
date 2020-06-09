<?php

//$Id: course.php 939 2012-12-21 16:57:27Z c.arnolfo $

global $CFG;

require_once($CFG->dirroot.'/f2_lib/constants.php');
require_once $CFG->dirroot.'/mod/facetoface/lib.php';
require_once $CFG->dirroot.'/mod/feedback/lib.php';
require_once $CFG->dirroot.'/course/lib.php';
require_once $CFG->dirroot.'/mod/url/lib.php';

/*
 * AK-LS:
 * Se un corso è PROGRAMMATO sono istanziati due Questionari di Gradimento, uno per ogni
 * chiamata alla suddetta funzione
 * @param $courseid è l'id del corso, $name (opzionale) nome del questionario
 * @return $id della sessione se la creazione è andata a buon fine, FALSE altrimenti
 */
function auto_instance_feedback($courseid, $name = NULL) {
	global $DB;
	
	$objFeedback = new stdClass();
	$objFeedback->course = $courseid;
	$objFeedback->name = (is_null($name)) ? get_string('feedback', 'local_f2_traduzioni') : $name;
	$objFeedback->intro = get_string('feedback', 'local_f2_traduzioni');
	$objFeedback->anonymous = FEEDBACK_ANONYMOUS_YES; // anonimo (FEEDBACK_ANONYMOUS_NO = tracciato)
	$objFeedback->multiple_submit = 0; // una sola compilazione consentita
	$objFeedback->page_after_submit = get_string('feedback_message', 'local_f2_traduzioni');
	
	$id = feedback_add_instance($objFeedback);
	
	if ($id) {
		$moduleid = $DB->get_field('modules', 'id', array('name' => 'feedback'));	
		$course_sections = get_all_sections($courseid);
		$section = $course_sections[0]->id;
	
		$objectCourseModules = new stdClass();
		$objectCourseModules->course 	= $courseid;
		$objectCourseModules->module 	= $moduleid;
		$objectCourseModules->instance 	= $id;
		$objectCourseModules->section 	= $section; // id della sezione di testa del corso
			
		$cmid = $DB->insert_record('course_modules', $objectCourseModules);
			
		$course_sections = $DB->get_record('course_sections', array('section' => 0, 'course' => $courseid), '*', MUST_EXIST);
		$newsequence = (empty($course_sections->sequence)) ? $cmid : $course_sections->sequence.",".$cmid;
			
		$DB->set_field('course_sections', 'sequence', $newsequence, array('id' => $course_sections->id));
		$DB->set_field('course', 'modinfo', '', array('id' => $courseid));			
	} else return false;
		
	return ($id) ? $id : false;
}

/*
 * AK-LS:
 * Se un corso è OBBIETTIVO dev'essere istanziata una SESSIONE (alias un'istanza di Face-to-Face)
 * Se un corso è PROGRAMMATO sono istanziate tante SESSIONI quante previste da caricamento CSV
 * @param $courseid è l'id del corso, $f2_session (opzionale) usato per i corsi PROGRAMMATI
 * @return $id della sessione se la creazione è andata a buon fine, FALSE altrimenti
 */
function auto_instance_session($courseid, $f2_session = NULL) {
	global $DB;

	$objSession = new stdClass();
	$objSession->course = $courseid;
	$objSession->name = ($DB->get_field('f2_anagrafica_corsi', 'course_type', array('courseid' => $courseid)) == C_OBB) ? get_string('course_obb_view_session', 'local_f2_traduzioni') : 'Sessione '.$DB->get_field('f2_sessioni', 'numero', array('id' => $f2_session));
	$objSession->timecreated = time();
	$objSession->showcalendar = 0;
	$objSession->f2session = is_null($f2_session) ? NULL : $f2_session;

	$id = facetoface_add_instance($objSession);

	if ($id) {
		$moduleid = $DB->get_field('modules', 'id', array('name' => 'facetoface'));	
		$course_sections = get_all_sections($courseid);
		$section = $course_sections[0]->id;
	
		$objectCourseModules = new stdClass();
		$objectCourseModules->course 	= $courseid;
		$objectCourseModules->module 	= $moduleid;
		$objectCourseModules->visible 	= 0; // modulo nascosto
		$objectCourseModules->instance 	= $id;
		$objectCourseModules->section 	= $section; // id della sezione di testa del corso
			
		$cmid = $DB->insert_record('course_modules', $objectCourseModules);
			
		$course_sections = $DB->get_record('course_sections', array('section' => 0, 'course' => $courseid), '*', MUST_EXIST);
		$newsequence = (empty($course_sections->sequence)) ? $cmid : $course_sections->sequence.",".$cmid;
		$DB->set_field('course_sections', 'sequence', $newsequence, array('id' => $course_sections->id));
		$DB->set_field('course', 'modinfo', '', array('id' => $courseid));
			
                // per sicurezza tolgo la capability 'mod/facetoface:view' agli studenti (non dovrebbero averla cmq di partenza)
                $facetoface_module_id = $DB->get_field('modules', 'id', array('name' => 'facetoface'));
                $course_module_id = $DB->get_field('course_modules', 'id', array('course' => $courseid, 'module' => $facetoface_module_id, 'instance' => $id));
                //$context = get_context_instance(CONTEXT_MODULE, $course_module_id);
                $context = context_module::instance($course_module_id);
                role_change_permission(5, $context, 'mod/facetoface:view', CAP_PREVENT); // lo Studente NON puo' vedere la sessione facetoface
	} else return false;
		
	return ($id) ? $id : false;
}

/*
 * AK-LS:
 * Alla creazione di un corso (OBBIETTIVO o PROGRAMMATO) vengono istanziate 2 risorse URL 
 * una per la Scheda Progetto ed un'altra per il Contratto Formativo
 * @param $courseid è l'id del corso, $type (default sp) è sp per la Scheda Progetto e cf per il Contratto Formativo
 * @return $id della risorsa URL se è stata creata, FALSE altrimenti
 */
function auto_instance_url_resource($courseid, $type = "sp") {
	global $DB, $CFG;

	$url = new stdClass();
	$url->course = $courseid;
	$url->name = ($type == "sp") ? get_string('scheda_progetto', 'local_f2_traduzioni') : get_string('contratto_form', 'local_f2_traduzioni');
	$url->externalurl = ($type == "sp") ? "$CFG->wwwroot/local/f2_course/pdf/scheda_progetto.php?courseid=$courseid" : "$CFG->wwwroot/local/f2_course/pdf/select_contratto_formativo.php?courseid=$courseid";
	$url->visible = 1;
	if ($type == 'sp') {
        $url->display = 0;
		$url->printheading = 1;
		$url->printintro = 0;
	} else {
		$url->display = 6;
		$url->popupwidth = 620;
		$url->popupheight = 450;
		$options = array('popupwidth' => '620', 'popupheight' => '450');
		$url->displayoptions = addslashes(serialize($options));
	}
	
	$id = url_add_instance($url, new stdClass());
	
	if ($id) {
		$moduleid = $DB->get_field('modules', 'id', array('name' => 'url'));	
		$course_sections = get_all_sections($courseid);
		$section = $course_sections[0]->id;
	
		$objectCourseModules = new stdClass();
		$objectCourseModules->course 	= $courseid;
		$objectCourseModules->module 	= $moduleid;
		$objectCourseModules->instance 	= $id;
		$objectCourseModules->section 	= $section; // id della sezione di testa del corso
			
		$cmid = $DB->insert_record('course_modules', $objectCourseModules);
			
		$course_sections = $DB->get_record('course_sections', array('section' => 0, 'course' => $courseid), '*', MUST_EXIST);
		$newsequence = (empty($course_sections->sequence)) ? $cmid : $course_sections->sequence.",".$cmid;
			
		$DB->set_field('course_sections', 'sequence', $newsequence, array('id' => $course_sections->id));
		$DB->set_field('course', 'modinfo', '', array('id' => $courseid));	
		rebuild_course_cache($courseid, TRUE);
		
		/*if ($type == "cf") {
			$modinfo = $DB->get_record_sql("SELECT modinfo FROM {course} WHERE id = ".$courseid);
			$obj = unserialize($modinfo->modinfo);
			$obj[$cmid]->onclick = "document.getElementById('select_vehicles').style.display='block';document.getElementById('fade').style.display='block'";
			$DB->set_field('course', 'modinfo', serialize($obj), array('id' => $courseid));
			rebuild_course_cache($courseid, TRUE);	
		}*/
			
	} else return false;
	
	return $id;	
}

/*
 * 
 * @param int $sessionid è l'id della sessione
 * @return $result stdClass(timestart,timefinish)
 */
function periodo_sessions($sessionid) {
	global $DB;
	
	$sql = "SELECT 
				MIN(timestart) as timestart, 
				MAX(timefinish) as timefinish
			FROM 
				{facetoface_sessions_dates} fsd
			WHERE 
				fsd.sessionid = ".$sessionid;
	$result = $DB->get_record_sql($sql);
	
	return $result;
}
