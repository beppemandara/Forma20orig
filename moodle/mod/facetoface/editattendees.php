<?php
/*
 * $Id: editattendees.php 1327 2014-12-01 09:43:53Z l.moretto $
 */
global $DB, $THEME, $USER, $CFG, $PAGE;
 
require_once '../../config.php';
require_once 'lib.php';
require_once($CFG->dirroot.'/f2_lib/management.php');
require_once($CFG->dirroot.'/f2_lib/core.php');
require_once($CFG->dirroot.'/lib/enrollib.php');

define('MAX_USERS_PER_PAGE', 5000);
define('USERS_PRENOTATI_VALIDATI', 1001);

$s              = required_param('s', PARAM_INT); // facetoface session ID
$add            = optional_param('add', 0, PARAM_BOOL);
$remove         = optional_param('remove', 0, PARAM_BOOL);
$showall        = optional_param('showall', 0, PARAM_BOOL);
$searchtext     = optional_param('searchtext', '', PARAM_TEXT); // search string
$suppressemail  = optional_param('suppressemail', true, PARAM_BOOL); // send email notifications
$previoussearch = optional_param('previoussearch', 0, PARAM_BOOL);
$backtoallsessions = optional_param('backtoallsessions', 0, PARAM_INT); // facetoface activity to go back to
$userenrollist 	= optional_param('u', 0, PARAM_INT); // parametro per la selezione del tab (solo Corsi Programmati)
//$courseID 		= $DB->get_field_sql("SELECT f.course FROM {facetoface} f, {facetoface_sessions} fs WHERE f.id = fs.facetoface AND fs.id = $s");
$isRefDir		= isReferenteDiDirezione($USER->id);
//var_dump($isRefDir);

$PAGE->set_pagelayout('standard');

if (!$session = facetoface_get_session($s)) {
    print_error('error:incorrectcoursemodulesession', 'facetoface');
}
if (!$facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface))) {
    print_error('error:incorrectfacetofaceid', 'facetoface');
}
if (!$course = $DB->get_record('course', array('id' => $facetoface->course))) {
    print_error('error:coursemisconfigured', 'facetoface');
}
if (!$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id)) {
    print_error('error:incorrectcoursemodule', 'facetoface');
}

$coursetype  = (int)$DB->get_field('f2_anagrafica_corsi', 'course_type', array('courseid' => $course->id));
$isCoursePRO = ($coursetype === C_PRO);
$sessioncustomfields = facetoface_get_customfielddata($s);
$ednum = $sessioncustomfields['editionum']->data;

/// Check essential permissions
require_course_login($course);
$context = context_course::instance($course->id);
require_capability('mod/facetoface:viewattendees', $context);
require_capability('mod/facetoface:removeattendees', $context);


/// Get some language strings
$strsearch = get_string('search');
$strshowall = get_string('showall');
$strsearchresults = get_string('searchresults');
$strfacetofaces = get_string('modulenameplural', 'facetoface');
$strfacetoface = get_string('modulename', 'facetoface');

$errors = array();
// Get the user_selector we will need.

/*
 * AK-LS : Caricamento degli utenti iscritti ed iscrivibili a seconda della visibilità dell'utente collegato
 */
if ($isRefDir) {
	$potentialuserselector = new facetoface_candidate_selector_csi('addselect',
			array('sessionid' => $session->id, 'ref' => $USER->id, 'visible' => $userenrollist));
	$existinguserselector = new facetoface_existing_selector_csi('removeselect',
			array('sessionid' => $session->id, 'ref' => $USER->id));
} else {
	if ($isCoursePRO) {
		$potentialuserselector = new facetoface_candidate_selector_csi('addselect',
				array('sessionid' => $session->id, 'ref' => null, 'visible' => $userenrollist));
		$existinguserselector = new facetoface_existing_selector_csi('removeselect',
				array('sessionid' => $session->id, 'ref' => null));
	} else {
		$potentialuserselector = new facetoface_candidate_selector('addselect', array('sessionid'=>$session->id));
		//$existinguserselector = new facetoface_existing_selector('removeselect', array('sessionid'=>$session->id));
		$existinguserselector = new facetoface_existing_selector_csi('removeselect', array('sessionid'=>$session->id));
	}
}
// --- #

$str = <<<'EFO'
<script type="text/javascript">
//<![CDATA[
function confirmSubmit(var_html,url){
	if(var_html){
		var agree = window.confirm(var_html,'Nuova_finestra',200,200);
		if (agree)
			document.location.href=url;
		else
			return false ;
	}
    else{
		document.location.href=url;
	}
}
//]]>
</script>
EFO;

echo $str;

// Process incoming user assignments
if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
	
    require_capability('mod/facetoface:addattendees', $context);    
    if(isset($_POST['iscrizione_bulk'])){
    	$string_utenti_bulk = $_POST['utenti_iscrizione_bulk'];
    	//$array_utenti_bulk = explode(",", $string_utenti_bulk);
    	//echo str_replace($search, $replace, $subject);
    	$search = ",";
    	$replace= "','";
    	$string_utenti_bulk = str_replace($search,$replace,$string_utenti_bulk);
    	$string_utenti_bulk  = str_replace(" ","",$string_utenti_bulk);
    	$utenti_bulk = $DB->get_records_sql("SELECT id FROM mdl_user WHERE username in ('".$string_utenti_bulk."')");
    	$userstoassign = $utenti_bulk;
    }else{
    	$userstoassign = $potentialuserselector->get_selected_users();
    }
    if (!empty($userstoassign)) {
        foreach ($userstoassign as $adduser) {
        	
            if (!$adduser = clean_param($adduser->id, PARAM_INT)) {
                continue; // invalid userid
            }
            // Make sure that the user is enroled in the course
   //         if (!has_capability('moodle/course:view', $context, $adduser)) {
                $user = $DB->get_record('user', array('id' => $adduser));
                if (!enrol_try_internal_enrol($course->id, $user->id, 5)) { // 5: ruolo base di moodle: Studente
                    $errors[] = get_string('error:enrolmentfailed', 'facetoface', fullname($user));
                    $errors[] = get_string('error:addattendee', 'facetoface', fullname($user));
                    continue; // don't sign the user up
                }
  //          }

            if ($submissions = facetoface_get_user_submissions($facetoface->id, $adduser)) {
                $str = "";
                foreach ($submissions as $submission) {
                    if((int)$submission->statuscode === MDL_F2F_STATUS_BOOKED) {
                        $already_signedup_session = facetoface_get_session($submission->sessionid);
                        $dt_format = get_string('strftimedate');
                        $session_timestart = intval($already_signedup_session->sessiondates[0]->timestart);
                        $str .= "ed. del ".userdate($session_timestart, $dt_format).", ";
                    }
                }
                if(strlen(str) > 0) {
                    //$erruser = $DB->get_record('user', array('id' => $adduser),'id, firstname, lastname');
                    $params = new stdClass();
                    $params->user = fullname($user);
                    $params->sessions = substr($str, 0, -2);
                    $errors[] = get_string('error:addalreadysignedupattendee', 'facetoface', $params);
                }
            } 
            
            // AKTIVE: non più bloccante, solo warning
//            else {
                if (!facetoface_session_has_capacity($session, $context)) {
                    $errors[] = get_string('full', 'facetoface');
                    break; // no point in trying to add other people
                }
                // Check if we are waitlisting or booking
                if ($session->datetimeknown) {
                    $status = MDL_F2F_STATUS_BOOKED;
                } else {
                    $status = MDL_F2F_STATUS_WAITLISTED;
                }
                if (!facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_BOTH,
                    $status, $adduser, !$suppressemail)) {
                    $erruser = $DB->get_record('user', array('id' => $adduser),'id, firstname, lastname');
                    $errors[] = get_string('error:addattendee', 'facetoface', fullname($erruser));
                }
//            }
        }
        $potentialuserselector->invalidate_selected_users();
        $existinguserselector->invalidate_selected_users();
        
        update_available_seats($USER->id, $coursetype, $s);
    }
}

// Process removing user assignments from session
if (optional_param('remove', false, PARAM_BOOL) && confirm_sesskey()) {
    require_capability('mod/facetoface:removeattendees', $context);
    $userstoremove = $existinguserselector->get_selected_users();
    if (!empty($userstoremove)) {
        foreach ($userstoremove as $removeuser) {
            if (!$removeuser = clean_param($removeuser->id, PARAM_INT)) {
                continue; // invalid userid
            }
            if (facetoface_user_cancel($session, $removeuser, true, $cancelerr)) {
                //AK-LM: in fase di rimozione iscrizione, se l'utente non è iscritto ad un'altra ed. dello stesso corso 
                //(per CSI è possibile, l'univocità di iscrizione è lasciata all'operatore), allora lo disiscrivo dal corso.
                $sql = "
select sus.id
  from {facetoface_signups_status} sus
    join {facetoface_signups} su on su.id = sus.signupid
    join {facetoface_sessions} s on s.id = su.sessionid
where
  s.facetoface = :f2f 
and s.id != :sess
and su.userid = :usr
and sus.superceded = 0
and sus.statuscode = :status";
                if (!$DB->record_exists_sql($sql, array("f2f"=>$facetoface->id, "sess"=>$session->id, "usr"=>$removeuser, "status"=>MDL_F2F_STATUS_BOOKED))) {
                    // Notify the user of the cancellation if the session hasn't started yet
                    $timenow = time();
                    if (!$suppressemail and !facetoface_has_session_started($session, $timenow)) {
                        facetoface_send_cancellation_notice($facetoface, $session, $removeuser);
                    }

                    // AKTIVE: disiscrivo l'utente anche dal corso, oltre che dall'edizione
                    if ($instances = $DB->get_records('enrol', array('enrol'=>'manual', 'courseid'=>$course->id, 'status'=>ENROL_INSTANCE_ENABLED), 'sortorder,id ASC')) {
                        $instance = reset($instances);
                        $plugin = enrol_get_plugin($instance->enrol);
                        $plugin->unenrol_user($instance, $removeuser);
                    }
                }
                
            } else {
                $errors[] = $cancelerr;
                $erruser = $DB->get_record('user', array('id' => $removeuser),'id, firstname, lastname');
                $errors[] = get_string('error:removeattendee', 'facetoface', fullname($erruser));
            }
        }
        $potentialuserselector->invalidate_selected_users();
        $existinguserselector->invalidate_selected_users();
        // Update attendees
        facetoface_update_attendees($session);
        
       	update_available_seats($USER->id, $coursetype, $s);
    }
}

/// Main page
$pagetitle = format_string($facetoface->name.' - Edizione '
        .($coursetype === C_PRO ? $edizione_svolgimento->num_ediz : "del ".date('d/m/Y', $session->sessiondates[0]->timestart)));

$PAGE->set_cm($cm);
$PAGE->set_url('/mod/facetoface/editattendees.php', array('s' => $s, 'backtoallsessions' => $backtoallsessions, 'u' => $userenrollist));

$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

echo "<p><a href=\"{$CFG->wwwroot}/mod/facetoface/attendees.php?s={$s}\" class=\"back_arianna\">Torna all'edizione</a></p>";
/*
 * AK-LS tabs per i Corsi Programmati al fine di dividere l'elenco di tutti gli utenti
 * iscrivibili dai soli prenotati/validati, anch'essi iscrivibili
 */
if ($isCoursePRO) {
	if ($userenrollist === USERS_PRENOTATI_VALIDATI)
		$currenttab = 'usersprenot';
	else
		$currenttab = 'allenrolusers';

	require('tabs_editattendees.php');
}
// --- #

echo $OUTPUT->box_start();
$pageheader = "$course->idnumber - ".format_string($course->fullname)." - Edizione "
    .($coursetype === C_PRO ? $ednum : "del ".date('d/m/Y', $session->sessiondates[0]->timestart))
    ."<span style=\"font-size:10px;display: block;\">Gestisci iscritti</span>";
echo $OUTPUT->heading($pageheader);

if (!empty($errors)) {
    $msg = html_writer::start_tag('p');
    foreach ($errors as $e) {
        $msg .= $e . html_writer::empty_tag('br');
    }
    $msg .= html_writer::end_tag('p');
    echo $OUTPUT->box_start('center');
    echo $OUTPUT->notification($msg);
    echo $OUTPUT->box_end();
}

//create user_selector form
$out = html_writer::start_tag('form', array('id' => 'assignform', 'method' => 'post', 'action' => $PAGE->url));
$out .= html_writer::start_tag('div');
$out .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => "previoussearch", 'value' => $previoussearch));
$out .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => "backtoallsessions", 'value' => $backtoallsessions));
$out .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => "sesskey", 'value' => sesskey()));

if ($isRefDir && $isCoursePRO) {
	$direzione = get_direzione_utente($USER->id);
	$postiassegnati = $DB->get_field('f2_edizioni_postiris_map', 'npostiassegnati', array('sessionid' => $s, 'direzioneid' => $direzione['id']));
	$posticonsumati = $DB->get_field('f2_edizioni_postiris_map', 'nposticonsumati', array('sessionid' => $s, 'direzioneid' => $direzione['id'])); 
	$out .= html_writer::start_tag("p");
	$out .= html_writer::tag("label", "DIREZIONE ".$direzione['name']);
	$out .= html_writer::empty_tag("br");
	$out .= html_writer::tag("label", "Totale posti assegnati: ".$postiassegnati);
	$out .= html_writer::empty_tag("br");
	$postidisponibili = ($postiassegnati - $posticonsumati);
	if ($postidisponibili > 0)
		$out .= html_writer::tag("label", "Posti ancora disponibili: ".$postidisponibili);
	elseif ($postidisponibili == 0)
		$out .= html_writer::tag("label", "Non ci sono piu&grave; posti disponibili");
	else {
		$out .= html_writer::tag("label", "E&grave; stata superata la soglia di disponibilita&grave;", array("style" => "color:red"));
		$out .= html_writer::empty_tag("br");
		$out .= html_writer::tag("label", "Posti prenotati: ".$posticonsumati, array("style" => "color:red"));
	}
	$out .= html_writer::end_tag("p");
}

$table = new html_table();
$table->attributes['class'] = "generaltable generalbox boxaligncenter";
$cells = array();
$content = html_writer::start_tag('p') . html_writer::tag('label', get_string('attendees', 'facetoface'), array('for' => 'removeselect')) . html_writer::end_tag('p');
$content .= $existinguserselector->display(true);
$cell = new html_table_cell($content);
$cell->attributes['id'] = 'existingcell';
$cells[] = $cell;
$content = html_writer::tag('div', html_writer::empty_tag('input', array('type' => 'submit', 'id' => 'add', 'name' => 'add', 'title' => get_string('add'), 'value' => $OUTPUT->larrow().' '.get_string('add'))), array('id' => 'addcontrols'));
$content .= html_writer::tag('div', html_writer::empty_tag('input', array('type' => 'submit', 'id' => 'remove', 'name' => 'remove', 'title' => get_string('remove'), 'value' => $OUTPUT->rarrow().' '.get_string('remove'))), array('id' => 'removecontrols'));
$cell = new html_table_cell($content);
$cell->attributes['id'] = 'buttonscell';
$cells[] = $cell;
$content = html_writer::start_tag('p') . html_writer::tag('label', get_string('potentialattendees', 'facetoface'), array('for' => 'addselect')) . html_writer::end_tag('p');
$content .= $potentialuserselector->display(true);
$cell = new html_table_cell($content);
$cell->attributes['id'] = 'potentialcell';
$cells[] = $cell;
$table->data[] = new html_table_row($cells);
/*$content = html_writer::checkbox('suppressemail', 1, $suppressemail, get_string('suppressemail', 'facetoface'), array('id' => 'suppressemail'));
$content .= $OUTPUT->help_icon('suppressemail', 'facetoface');
$cell = new html_table_cell($content);
$cell->attributes['id'] = 'backcell';
$cell->attributes['colspan'] = '3';
$table->data[] = new html_table_row(array($cell));*/

$out .=  html_writer::table($table);

    // Get all signed up non-attendees
    $nonattendees = 0;
    $nonattendees_rs = $DB->get_recordset_sql(
         "SELECT
                u.id,
                u.firstname,
                u.lastname,
                u.email,
                ss.statuscode
            FROM
                {facetoface_sessions} s
            JOIN
                {facetoface_signups} su
             ON s.id = su.sessionid
            JOIN
                {facetoface_signups_status} ss
             ON su.id = ss.signupid
            JOIN
                {user} u
             ON u.id = su.userid
            WHERE
                s.id = ?
            AND ss.superceded != 1
            AND ss.statuscode = ?
            ORDER BY
                u.lastname, u.firstname", array($session->id, MDL_F2F_STATUS_REQUESTED)
    );

    $table = new html_table();
    $table->head = array(get_string('name'), get_string('email'), get_string('status'));
    foreach ($nonattendees_rs as $user) {
        $data = array();
        $data[] = new html_table_cell(fullname($user));
        $data[] = new html_table_cell($user->email);
        $data[] = new html_table_cell(get_string('status_'.facetoface_get_status($user->statuscode), 'facetoface'));
        $row = new html_table_row($data);
        $table->data[] = $row;
        $nonattendees++;
    }
    $nonattendees_rs->close();
    if ($nonattendees) {
        $out .= html_writer::empty_tag('br');
        $out .=  $OUTPUT->heading(get_string('unapprovedrequests', 'facetoface').' ('.$nonattendees.')');
        $out .=  html_writer::table($table);
    }

    $out .= html_writer::end_tag('div') . html_writer::end_tag('form');
    echo $out;

    global $DB;
    $status_booked = MDL_F2F_STATUS_BOOKED;
    //-- utenti iscritti alla sessione

    
    //fssig.superceded != 1 AND
    $sql_course_user = "
    					SELECT 
    						*
						FROM
						(
	    					SELECT 
    							concat(fsig.userid,'_',fsd.id) as num_id,
    							u.firstname,
    							u.lastname,
    							c.shortname,
    							c.fullname,
	    						fsig.userid,
								fsig.sessionid,
								fsd.timestart,
								fsd.timefinish
							FROM 
									{facetoface_signups} fsig,
									{facetoface_signups_status} fssig,
									{facetoface_sessions_dates} fsd,
    								{user} u,
				    				{facetoface_sessions} fs,
									{facetoface} f,
									{course} c
							WHERE 
    								fs.id = fsig.sessionid AND
									f.id = fs.facetoface AND
									f.course = c.id AND
    								u.id = fsig.userid AND
									fssig.signupid = fsig.id AND
    								fssig.superceded != 1 AND
									fsd.sessionid = fsig.sessionid AND
									fssig.statuscode = ".$status_booked." AND
									u.deleted <> 1 AND
									u.confirmed = 1 AND
									fsig.userid in (SELECT 
															fsig.userid 
													FROM 
															{facetoface_signups} fsig,
															{facetoface_signups_status} fssig
													WHERE 
															fsig.sessionid = ".$s." AND
															fssig.signupid = fsig.id AND 
															fssig.superceded != 1 AND 
															fssig.statuscode = ".$status_booked."
									) AND exists
											(SELECT 	
													*
											FROM
												{facetoface_sessions_dates} fsd2
											WHERE 
												fsd2.sessionid = ".$s." AND
												(
													((SELECT FROM_UNIXTIME(fsd.timestart,'%Y%m%d')) >= (SELECT FROM_UNIXTIME(fsd2.timestart,'%Y%m%d')) AND (SELECT FROM_UNIXTIME(fsd.timestart,'%Y%m%d')) <= (SELECT FROM_UNIXTIME(fsd2.timefinish,'%Y%m%d'))) OR
													((SELECT FROM_UNIXTIME(fsd.timefinish,'%Y%m%d')) >= (SELECT FROM_UNIXTIME(fsd2.timestart,'%Y%m%d')) AND (SELECT FROM_UNIXTIME(fsd.timefinish,'%Y%m%d')) <= (SELECT FROM_UNIXTIME(fsd2.timefinish,'%Y%m%d'))) OR
													((SELECT FROM_UNIXTIME(fsd.timestart,'%Y%m%d')) <= (SELECT FROM_UNIXTIME(fsd2.timestart,'%Y%m%d')) AND (SELECT FROM_UNIXTIME(fsd.timefinish,'%Y%m%d')) >= (SELECT FROM_UNIXTIME(fsd2.timefinish,'%Y%m%d')))
												)
    							)) tmp
					WHERE 
						tmp.sessionid <> ".$s." AND
						tmp.userid <> 1
					GROUP BY tmp.userid,tmp.sessionid
					ORDER BY tmp.userid ASC";
    
   $courses_user = $DB->get_records_sql($sql_course_user);
   $html="";
   if($courses_user){
   	
   	foreach($courses_user as $course_user){
   		$html .= "L'utente ".$course_user->firstname." ".$course_user->lastname." è iscritto alla sessione del ".date('d/m/Y',$course_user->timestart)." - ".date('d/m/Y',$course_user->timefinish)." del corso ".$course_user->shortname." - ".$course_user->fullname.""; 
   		$html .= '\n\n';
   	}
   	$html .= '\n';
   	$html .="Proseguire?";
   	$html = str_replace("'","\'",$html);
   }
    
   //FINE: Iscrizione bulk
   echo "<p style=\"width: 830px;margin-left: auto;margin-right: auto;\" class=\"msg_feedback_ko\">Gli studenti iscritti evidenziati in rosso sono gia&grave; iscritti a edizioni di altri corsi, nelle stesse date</p>";
   
//INIZIO: Iscrizione bulk
   //echo '<b>Iscrizione bulk</b><br>';
   echo '<b>Iscrizione massiva</b><br>';
   echo '<div style="background-color: #EDEDED;padding: 15px 0px 15px 15px;width: 810px;margin-left: auto;margin-right: auto;">';
   echo 'Inserire l\'username degli utenti da iscrivere separati da una virgola.<br>Es. rxsmxa85x10x562s,rssmra85t10a562s<br>';
   echo '<form id="assignform" method="post" action="">';
   echo '<textarea name="utenti_iscrizione_bulk" rows="5" cols="120"></textarea>';
   echo '<br>';
   echo '<input type="hidden" name="sesskey" value='.sesskey().'>';
   echo '<input type="hidden" name="add" id="add" value=1>';
   echo '<br>';
   echo '<input type="submit" id="id_iscrizione_bulk" name="iscrizione_bulk" value="Aggiungi">';
   echo '</form>';
   echo '</div>';
   

// Bottom of the page links
echo html_writer::start_tag('p');
$url = new moodle_url('/mod/facetoface/attendees.php', array('s' => $session->id, 'backtoallsessions' => $backtoallsessions));

echo html_writer::start_tag('center');
echo html_writer::empty_tag("input", array('type' => 'button','value' => 'Conferma l\'iscrizione', 'onclick' => "return confirmSubmit('".$html."','".$url."');"));
echo html_writer::end_tag('center');
//echo html_writer::link($url, get_string('goback', 'facetoface'));
echo html_writer::end_tag('p');
echo $OUTPUT->box_end();
echo $OUTPUT->footer($course);
