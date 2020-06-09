<?php
ob_start();
require_once('../../config.php');

global $CFG;

require_once($CFG->dirroot.'/mod/facetoface/lib.php');
require_once($CFG->dirroot.'/f2_lib/core.php');
define('CONST_CORSO_PROGRAMMATO', 2);

if (isset($_GET['coursetype']) && isset($_GET['dir'])) {
	$viewable_years = array(get_anno_formativo_corrente()+1,get_anno_formativo_corrente(), get_anno_formativo_corrente()-1);
	$courses = get_managable_course($_GET['coursetype'], $viewable_years);	

	// Template costruito in base alla tipologia di corso e al ruolo dell'utente loggato
	$template = array();
	if ($_GET['coursetype'] == CONST_CORSO_PROGRAMMATO) {
		if ($_GET['dir']) {
			$template = array(
					'course_code' => 'Codice corso',
					'course_title' => 'Titolo',
					'course_year' => 'Anno Formativo',
					'session_name' => 'Sessione',
					'editionum' => 'Edizione di svolgimento',
					'sede' => 'Sede',
					'indirizzo' => 'Indirizzo',
					'edition_timestart' => 'Data di inizio',
					'edition_timefinish' => 'Data di fine',
					'edition_seats_reserved' => 'Posti riservati',
					'edition_seats_booked' => 'Posti consumati');
		} else {
			$template = array(
					'course_code' => 'Codice corso',
					'course_title' => 'Titolo',
					'course_year' => 'Anno Formativo',
					'session_name' => 'Sessione',
					'editionum' => 'Edizione di svolgimento',
					'sede' => 'Sede',
					'indirizzo' => 'Indirizzo',
					'edition_timestart' => 'Data di inizio',
					'edition_timefinish' => 'Data di fine');
		}
	} else {
		$template = array(
				'course_code' => 'Codice corso',
				'course_title' => 'Titolo',
				'course_year' => 'Anno Formativo',
				'editionum' => 'Edizione di svolgimento',
				'sede' => 'Sede',
				'indirizzo' => 'Indirizzo',
				'edition_timestart' => 'Data di inizio',
				'edition_timefinish' => 'Data di fine');
	}	
	
	// Explode edition_customfields in stdClass of course's object
	foreach ($courses as $course) {
		$customs = array();
		parse_str($course->edition_customfields, $customs);
		foreach ($customs as $k => $v) {
			$course->$k = $v;
		}
	}
	ob_end_clean();
	excel_sessions_seats($template, $courses);
	
} else die;
