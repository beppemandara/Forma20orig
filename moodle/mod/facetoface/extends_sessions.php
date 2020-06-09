<?php

class extends_f2_session {
	/** @var $courseid  id del corso */
	public $sessionid = 0;
	
	
	/**
	 * Constructor extends_f2_course with only the required params.
	 */
	public function __construct($sessionid =0) {
		$this->sessionid  = $sessionid ;
	}
	
	
	public function print_tab_edit_session($currenttab) {
		global $DB;
		$toprow = array();
		$toprow[] = new tabobject(get_string('facetofaceobb:editingsession', 'local_f2_traduzioni'), new moodle_url('../../mod/facetoface/sessions.php?s='.$this->sessionid),get_string('facetofaceobb:editingsession', 'local_f2_traduzioni'));
		
		$course=get_course_by_session($this->sessionid);
		$courseid= $course->id;
		
		//$coursecontext = get_context_instance(CONTEXT_COURSE, $courseid);
		$coursecontext = context_course::instance($courseid);
		if(has_capability('local/f2_notif:edit_notifiche', $coursecontext)){
			$toprow[] = new tabobject('notif_sistema', new moodle_url('/local/f2_notif/template_sessions.php?sessionid='.$this->sessionid), get_string('notif_sistema','local_f2_notif'));
		}
		
		$tabs = array($toprow);
		if($this->sessionid )
			$inactive = array();
		else
			$inactive = array(get_string('facetofaceobb:editingsession', 'local_f2_traduzioni'),'notif_sistema');
			print_tabs($tabs, $currenttab,$inactive);
	}
}	

