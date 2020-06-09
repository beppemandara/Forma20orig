<?php

require_once '../../config.php';
global $CFG, $DB;

require_once $CFG->dirroot.'/mod/facetoface/lib.php';
require_once $CFG->dirroot.'/f2_lib/course.php';
require_once $CFG->dirroot.'/mod/facetoface/pdf_fogliopresenza.php';

define('CONST_CORSO_PROGRAMMATO', 2);

$sessionID = required_param('s', PARAM_INT);

$teachers = $DB->get_records_sql("
		SELECT 
			u.id,
			u.firstname,
			u.lastname,
			u.idnumber,
			f2_f.tipodoc
		FROM
			{facetoface_sessions_docenti} fsd,
			{user} u,
			{f2_formatore} f2_f
		WHERE 
			fsd.sessionid = $sessionID 
			AND fsd.userid = u.id
			AND u.id = f2_f.usrid");

$bookingUsers = facetoface_get_users_instatus($sessionID, array(MDL_F2F_STATUS_BOOKED));

$sessionDates = periodo_sessions($sessionID);
$sessionDates->timestart = date('d/m/Y H:i:s',$sessionDates->timestart);
$sessionDates->timefinish = date('d/m/Y H:i:s',$sessionDates->timefinish);

$sessionInfo = $DB->get_record_sql("
		SELECT 
			c.fullname,
			IF(f2_ac.course_type = ".CONST_CORSO_PROGRAMMATO.", 'Corso Programmato', 'Corso Obiettivo') AS coursetype,
			fs.capacity,
			(SELECT data FROM {facetoface_session_data} fsd, {facetoface_session_field} fsf WHERE fsf.id = fsd.fieldid AND fsf.shortname LIKE 'sede' AND fsd.sessionid = $sessionID) AS sede,
			(SELECT data FROM {facetoface_session_data} fsd, {facetoface_session_field} fsf WHERE fsf.id = fsd.fieldid AND fsf.shortname LIKE 'indirizzo' AND fsd.sessionid = $sessionID) AS indirizzo
		FROM 		
			{facetoface_sessions} fs,
			{facetoface} f,
			{course} c,
			{f2_anagrafica_corsi} f2_ac
		WHERE 
			fs.id = $sessionID 
			AND fs.facetoface = f.id 
			AND f.course = c.id 
			AND c.id = f2_ac.courseid");

createPDF($sessionID, $teachers, $bookingUsers, $sessionDates, $sessionInfo);
return;