<?php

class extends_f2_course {
	/** @var $courseid  id del corso */
	public $courseid = 0;
	
	
	/**
	 * Constructor extends_f2_course with only the required params.
	 */
	public function __construct($courseid=0) {
		$this->courseid = $courseid;
	}
	
	
	public function print_tab_edit_course($currenttab) {
		global $DB;
		$toprow = array();
		$toprow[] = new tabobject('corso', new moodle_url('/course/edit.php?id='.$this->courseid), get_string('tab_standard_course','local_f2_course'));
		$toprow[] = new tabobject('anag_course', new moodle_url('/local/f2_course/anag_course.php?courseid='.$this->courseid), get_string('tab_anag_course','local_f2_course'));
		$toprow[] = new tabobject('scheda_progetto', new moodle_url('/local/f2_course/scheda_prog.php?courseid='.$this->courseid), get_string('tab_scheda_progetto','local_f2_course'));
		$toprow[] = new tabobject('notif_sistema', new moodle_url('/local/f2_notif/template_course.php?courseid='.$this->courseid), get_string('tab_notifiche','local_f2_course'));
		$anag = $DB->get_record('f2_anagrafica_corsi', array('courseid'=>$this->courseid));

		if($anag && $anag->course_type==1)
			$toprow[] = new tabobject('condiv_course', new moodle_url('/local/f2_course/condiv_course.php?courseid='.$this->courseid), get_string('tab_condiv_course','local_f2_course'));
		
        if($anag && $anag->course_type==1)
                    $toprow[] = new tabobject('modello_questionario', new moodle_url('/local/f2_course/modello_questionario.php?courseid='.$this->courseid), get_string('tab_modello_questionario','local_f2_course'));
		if($anag && $anag->course_type==2)
			$toprow[] = new tabobject('destinatari_course', new moodle_url('/local/f2_course/destinatari_course.php?courseid='.$this->courseid), get_string('tab_destinatari_course','local_f2_course'));
		
		$tabs = array($toprow);
		if($this->courseid)
			$inactive = array();
		else
			$inactive = array('anag_course','scheda_progetto','destinatari_course','notif_sistema','modello_questionario','condiv_course');
			print_tabs($tabs, $currenttab,$inactive);
	}
}	

	function manage_anag_course($data) {
		global $DB;
		if($DB->record_exists('f2_anagrafica_corsi', array('courseid'=>$data->courseid))){  // update
			update_anag_course($data);
		}
		else{   // create
			create_anag_course($data);
		}
	}
	
	function update_anag_course($data) {
		global $DB,$USER;
		$original = $DB->get_record('f2_anagrafica_corsi', array('courseid'=>$data->courseid));
		$data->id= $original->id;
		
		
		if($original->course_type==1){
			
			if($data->dir_proponente  == $original->dir_proponente && $data->dir_proponente)
				set_org_by_course_enroll($data->dir_proponente,$original->courseid,'referentedirezione');
			else {
				if($original->dir_proponente)
					remove_org_by_course_enroll($original->dir_proponente,$original->courseid,'referentescuola');
				if($data->dir_proponente)
					set_org_by_course_enroll($data->dir_proponente,$original->courseid,'referentedirezione');
			}

			if($data->flag_dir_scuola  == "D" && 
			   $original->flag_dir_scuola == $data->flag_dir_scuola && 
			   $original->id_dir_scuola != $data->id_dir_scuola){
				remove_org_by_course_enroll($original->id_dir_scuola,$original->courseid,'referentedirezione');
				set_org_by_course_enroll($data->id_dir_scuola,$original->courseid,'referentedirezione');
			}
			
			if($data->flag_dir_scuola  == "D" &&
					$original->flag_dir_scuola != $data->flag_dir_scuola){
				$orgid = $DB->get_field('f2_fornitori', 'id_org', array('id'=>$original->id_dir_scuola));
				remove_org_by_course_enroll($orgid,$original->courseid,'referentescuola');
				set_org_by_course_enroll($data->id_dir_scuola,$original->courseid,'referentedirezione');	
			}
			
			if($data->flag_dir_scuola  == "D" &&
					$original->flag_dir_scuola == $data->flag_dir_scuola &&
					$original->id_dir_scuola == $data->id_dir_scuola){
				set_org_by_course_enroll($data->id_dir_scuola,$original->courseid,'referentedirezione');
			}			
			
			if($data->flag_dir_scuola  == "S" &&
					$original->flag_dir_scuola != $data->flag_dir_scuola){
				remove_org_by_course_enroll($original->id_dir_scuola,$original->courseid,'referentedirezione');
				$orgid_new = $DB->get_field('f2_fornitori', 'id_org', array('id'=>$data->id_dir_scuola));
				if($orgid_new)
					set_org_by_course_enroll($orgid_new,$original->courseid,'referentescuola');
			}
	
			if($data->flag_dir_scuola  == "S" && 
			   $original->flag_dir_scuola == $data->flag_dir_scuola && 
			   $original->id_dir_scuola != $data->id_dir_scuola){
				$orgid_old = $DB->get_field('f2_fornitori', 'id_org', array('id'=>$original->id_dir_scuola));
				remove_org_by_course_enroll($orgid_old,$original->courseid,'referentescuola');
				$orgid_new = $DB->get_field('f2_fornitori', 'id_org', array('id'=>$data->id_dir_scuola));
				if($orgid_new)
					set_org_by_course_enroll($orgid_new,$original->courseid,'referentescuola');
			}
			
			if($data->flag_dir_scuola  == "S" &&
					$original->flag_dir_scuola == $data->flag_dir_scuola &&
					$original->id_dir_scuola == $data->id_dir_scuola){
				$orgid_new = $DB->get_field('f2_fornitori', 'id_org', array('id'=>$data->id_dir_scuola));
				if($orgid_new)
					set_org_by_course_enroll($orgid_new,$original->courseid,'referentescuola');
			}
		}
		
		$data->timemodified=time();
		$data->usermodified=$USER->id;
		$DB->update_record('f2_anagrafica_corsi', $data);
		$DB->delete_records('f2_corsi_sedi_map', array('courseid'=>$data->courseid));
		foreach($data->sede as $sede){
			$obj_des = new stdClass();
			$obj_des->courseid = $data->courseid;
			$obj_des->sedeid = $sede;
			$DB->insert_record('f2_corsi_sedi_map', $obj_des);
		}
	}
	
	function create_anag_course($data) {
		global $DB, $USER, $CFG;
		$data->timemodified=time();
		$data->usermodified=$USER->id;
		$id = $DB->insert_record('f2_anagrafica_corsi', $data);
		
		foreach($data->sede as $sede){
			$obj_des = new stdClass();
			$obj_des->courseid = $data->courseid;
			$obj_des->sedeid = $sede;
			$DB->insert_record('f2_corsi_sedi_map', $obj_des);
		}
		$coursetype = $DB->get_field('f2_anagrafica_corsi', 'course_type', array('courseid' => $data->courseid));
		
		if($coursetype==1){
			
			if($data->dir_proponente)
				set_org_by_course_enroll($data->dir_proponente,$data->courseid,'referentedirezione');
			
			if($data->flag_dir_scuola  == "D"){
				set_org_by_course_enroll($data->id_dir_scuola,$data->courseid,'referentedirezione');
			}
				
			if($data->flag_dir_scuola  == "S"){
				$orgid_new = $DB->get_field('f2_fornitori', 'id_org', array('id'=>$data->id_dir_scuola));
				if($orgid_new)
					set_org_by_course_enroll($orgid_new,$data->courseid,'referentescuola');
			}
		}
		
		
		
		
		if ($coursetype == C_OBB) { // Non dev'essere auto istanziata una sessione per i corsi programmati
			require_once($CFG->dirroot.'/f2_lib/course.php');
			auto_instance_session($data->courseid);
			rebuild_course_cache($data->courseid, TRUE);
		}			
	}	
	
	function manage_scheda_progetto($data) {
		global $DB;
			
		if($DB->record_exists('f2_scheda_progetto', array('courseid'=>$data->courseid))){  // update
			update_scheda_progetto($data);
		}
		else{   // create
			create_scheda_progetto($data);
		}
	}
	
	function update_scheda_progetto($data) {
		global $DB,$USER;
		$data->id=$DB->get_field('f2_scheda_progetto', 'id', array('courseid'=>$data->courseid));
		$data->timemodified=time();
		$data->usermodified=$USER->id;
		$DB->update_record('f2_scheda_progetto', $data);
	}
	
	function create_scheda_progetto($data) {
		global $DB, $USER, $CFG;
		$data->timemodified=time();
		$data->usermodified=$USER->id;
		$id = $DB->insert_record('f2_scheda_progetto', $data);
		
		require_once($CFG->dirroot.'/f2_lib/course.php');
		auto_instance_url_resource($data->courseid, "sp");
		auto_instance_url_resource($data->courseid, "cf");
	}
	
	
	function create_update_destinatari_course($data) {
		global $DB;
		$DB->delete_records('f2_corsi_coorti_map', array('courseid'=>$data->courseid));
		foreach($data->destinatari as $destinatari){
			$obj_des = new stdClass();
			$obj_des->courseid = $data->courseid;
			$obj_des->coorteid = $destinatari;
			$DB->insert_record('f2_corsi_coorti_map', $obj_des);
		}
	}
	