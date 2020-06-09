<?php
/*
 * $Id: substituted.php 1327 2014-12-01 09:43:53Z l.moretto $
 */
require_once '../../config.php';
require_once 'lib.php';
require_once($CFG->dirroot.'/f2_lib/management.php');
require_once($CFG->dirroot.'/f2_lib/core.php');
require_once($CFG->dirroot.'/f2_lib/lib.php');
require_once($CFG->dirroot.'/local/f2_notif/lib.php');

global $DB, $USER, $PAGE;
define('MAX_USERS_PER_PAGE', 5000);
define('USERS_PRENOTATI_VALIDATI', 1001);

$s              = required_param('s', PARAM_INT); // facetoface session ID
$add            = optional_param('add', 0, PARAM_BOOL);
$remove         = optional_param('remove', 0, PARAM_BOOL);
$showall        = optional_param('showall', 0, PARAM_BOOL);
$searchtext     = optional_param('searchtext', '', PARAM_TEXT); // search string
$suppressemail  = optional_param('suppressemail', true, PARAM_BOOL); // send email notifications  -----------------------------------------------
$previoussearch = optional_param('previoussearch', 0, PARAM_BOOL);
$backtoallsessions = optional_param('backtoallsessions', 0, PARAM_INT); // facetoface activity to go back to
//AK-LM: per i corsi obv le notifiche non definite non sono un errore bloccante [segnalazione #57]
$forcesubstitution = optional_param('forcesub', 0, PARAM_BOOL); //AK-LM: per i corsi obv le notifiche non definite non sono un errore bloccante [segnalazione #57]

$usrSbt     	= required_param('idusrsbt', PARAM_ALPHANUMEXT); //User da sostituire
//$converter 		= new Encryption($CFG->passwordsaltmain); // 2019 04 30 
//$id_user_substituted = $converter->decode($usrSbt); // 2019 04 30
$id_user_substituted = $usrSbt;

$userenrollist 	= optional_param('u', 0, PARAM_INT); // parametro per la selezione del tab (solo Corsi Programmati)
//$courseID 		= $DB->get_field_sql("SELECT f.course FROM {facetoface} f, {facetoface_sessions} fs WHERE f.id = fs.facetoface AND fs.id = $s");
$isRefDir		= isReferenteDiDirezione($USER->id);

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

$coursetype = (int)$DB->get_field('f2_anagrafica_corsi', 'course_type', array('courseid' => $course->id));
$isprg = ($coursetype === C_PRO);
$isobv = ($coursetype === C_OBB);
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
$strsubstitute = get_string('substitute','facetoface');

$PAGE->set_pagelayout('standard');

$errors = array();
$esito_notifiche = null;

if ($isRefDir) {
	$potentialuserselector = new facetoface_candidate_selector_csi('addselect',
			array('sessionid' => $session->id, 'ref' => $USER->id, 'visible' => $userenrollist, 'multiselect' => false));
} else {
	if ($isprg)
		$potentialuserselector = new facetoface_candidate_selector_csi('addselect',
				array('sessionid' => $session->id, 'ref' => null, 'visible' => $userenrollist, 'multiselect' => false));
	else{
		$potentialuserselector = new facetoface_candidate_selector('addselect', array('sessionid'=>$session->id, 'multiselect' => false));
		}
}

if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {

    //recupero le notifiche associate all'edizione
    $id_notifica_autorizzazione = get_template_corso_edizione($course->id,$session->id,1);
    $id_notifica_cancellazione  = get_template_corso_edizione($course->id,$session->id,2);

    if(!($id_notifica_autorizzazione && $id_notifica_cancellazione)){ //se non sono presenti le notifiche di autorizzazione e cancellazione
        $esito_notifiche = "<b style='color:red;'>Notifiche non presenti.</b>";
    }
    //AK-LM: per i corsi obv le notifiche non definite non sono un errore bloccante [segnalazione #57]
    $ok = ($isprg && is_null($esito_notifiche)) || $isobv;
	if($ok) {

		require_capability('mod/facetoface:addattendees', $context);
		$adduser = $potentialuserselector->get_selected_user();
		if (!empty($adduser)) {
			if (!$adduser = clean_param($adduser->id, PARAM_INT)) {
				continue; // invalid userid
			}
			// Make sure that the user is enroled in the course
			if (!has_capability('moodle/course:view', $context, $adduser)) {
				$user = $DB->get_record('user', array('id' => $adduser));
				if (!enrol_try_internal_enrol($course->id, $user->id, 5)) { // 5: ruolo base di moodle: Studente
					$errors['01'] = get_string('error:enrolmentfailed', 'facetoface', fullname($user));
					$errors['02'] = get_string('error:addattendee', 'facetoface', fullname($user));
					continue; // don't sign the user up
				}
			}

            $submissions = facetoface_get_user_submissions($facetoface->id, $adduser);
			if ($submissions && !$forcesubstitution) {
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
                    $params = new stdClass();
                    $params->user = fullname($user);
                    $params->sessions = substr($str, 0, -2);
                    $errors['03'] = get_string('error:addalreadysignedupattendee', 'facetoface', $params);
                }
			} else {
                if (!facetoface_session_has_capacity($session, $context)) {
                    $errors['04'] = get_string('full', 'facetoface');
                    break; // no point in trying to add other people
                }
                // Check if we are waitlisting or booking
                if ($session->datetimeknown) {
                    $status = MDL_F2F_STATUS_BOOKED;
                } else {
                    $status = MDL_F2F_STATUS_WAITLISTED;
                }
                if (!facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_BOTH,$status, $adduser, !$suppressemail)) {
                    $erruser = $DB->get_record('user', array('id' => $adduser),'id, firstname, lastname');
                    $errors['05'] = get_string('error:addattendee', 'facetoface', fullname($erruser));
                } else {
                    $removeuser = $id_user_substituted;
                    $cancelerr = '';
                    if (facetoface_user_cancel($session, $removeuser, true, $cancelerr)) {
                        //AK-LM: per i corsi obv se le notifiche sono attive procedo con l'invio, altrimenti no [segnalazione #57]
                        if(is_null($esito_notifiche)) {
                            // Notify the user of the cancellation if the session hasn't started yet
                            $timenow = time();
                            if (!$suppressemail and !facetoface_has_session_started($session, $timenow)) {
                                facetoface_send_cancellation_notice($facetoface, $session, $removeuser);
                            }
                            //Inizio: Invio mail
                            $list_user_substitute = array();
                            $data_substitute= new stdClass;
                            $data_substitute->userid=$removeuser;
                            $list_user_substitute[]=$data_substitute;

                            $list_user_booking = array();
                            $data_booking= new stdClass;
                            $data_booking->userid=$adduser;
                            $list_user_booking[]=$data_booking;

                            $user_sub_mail     = upload_mailqueue($session->id,get_tipo_notif_byname(F2_TIPO_NOTIF_CANCELLAZIONE),$list_user_substitute);
                            $user_booking_mail = upload_mailqueue($session->id,get_tipo_notif_byname(F2_TIPO_NOTIF_AUTORIZZAZIONE),$list_user_booking);
                            //Fine: Invio mail
                        }
                    } else {
                        $errors['06'] = $cancelerr;
                        $erruser = $DB->get_record('user', array('id' => $removeuser),'id, firstname, lastname');
                        $errors['07'] = get_string('error:removeattendee', 'facetoface', fullname($erruser));
                    }

                    // Update attendees					
                    update_substituted($session->id,$removeuser); //Inserisco nella tabella facetoface_signups_status->substituted = 1 che significa che l'utente è stato modificato

                    $potentialuserselector->invalidate_selected_users();

                    if(facetoface_update_attendees($session)) {					
                        $url = new moodle_url('/mod/facetoface/attendees.php', array('s' => $session->id, 'backtoallsessions' => $backtoallsessions));
                        redirect($url);
                    }
                }
			}
		}
	} else {
        $esito_notifiche = "<b style='color:red;'>Sostituzione non effettuata. Notifiche non presenti</b>";
    }
}
/// Main page
$pagetitle = format_string($facetoface->name);
$PAGE->set_cm($cm);
// 2019 04 30
//$PAGE->set_url('/mod/facetoface/substituted.php', array('s' => $s, 'backtoallsessions' => $backtoallsessions, 'u' => $userenrollist, 'idusrsbt' => $converter->encode($id_user_substituted)));
$PAGE->set_url('/mod/facetoface/substituted.php', array('s' => $s, 'backtoallsessions' => $backtoallsessions, 'u' => $userenrollist, 'idusrsbt' => $id_user_substituted));

$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

/*
 * AK-LS tabs per i Corsi Programmati al fine di dividere l'elenco di tutti gli utenti
 * iscrivibili con quello da sostituire
 */
if ($isprg) {
	if ($userenrollist === USERS_PRENOTATI_VALIDATI)
		$currenttab = 'usersprenot';
	else
		$currenttab = 'allenrolusers';

	require('tabs_substituted.php');
}
// --- #


echo $OUTPUT->box_start();
$pageheader = "$course->idnumber - ".format_string($course->fullname)." - edizione $ednum
    <span style=\"font-size:10px;display: block;\">".get_string('updateattendees', 'facetoface')."</span>";
echo $OUTPUT->heading($pageheader);

//create user_selector form
$out = html_writer::start_tag('form', array('id' => 'assignform', 'method' => 'post', 'action' => $PAGE->url));
$out .= html_writer::start_tag('div');
$out .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => "previoussearch", 'value' => $previoussearch));
$out .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => "backtoallsessions", 'value' => $backtoallsessions));
$out .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => "sesskey", 'value' => sesskey()));
$out .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => "id_user_substituted", 'value' => $id_user_substituted));
if(isset($errors['03']))
    $out .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => "forcesub", 'value' => 1));

if ($isRefDir) {
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
//$table->attributes['class'] = "generaltable generalbox boxaligncenter";
$table->attributes['class'] = "generaltable generalbox";

/*
$cells = array();
$detail_user =user_get_users_by_id(array($id_user_substituted)); //Recupero i dati dell'utente
$content=$detail_user[$id_user_substituted]->firstname." ".$detail_user[$id_user_substituted]->lastname;
$cell = new html_table_cell($content);
$cells[] = $cell;
$content = html_writer::tag('div', html_writer::empty_tag('input', array('type' => 'submit', 'id' => 'add','name' => 'add', 'title' => get_string('add'), 'value' => $OUTPUT->larrow().' '.get_string('substitute','facetoface'))), array('id' => 'addcontrols'));
$cell = new html_table_cell($content);
$cell->attributes['id'] = 'buttonscell';
$cells[] = $cell;*/

$detail_user =user_get_users_by_id(array($id_user_substituted)); //Recupero i dati dell'utente
$content=$detail_user[$id_user_substituted]->firstname." ".$detail_user[$id_user_substituted]->lastname;
echo get_string('updateattendees', 'facetoface')." <b>".$content."</b><br><br>";

$content = html_writer::start_tag('p') . html_writer::tag('label', get_string('potentialattendees', 'facetoface'), array('for' => 'addselect')) . html_writer::end_tag('p');
$content .= $potentialuserselector->display(true);
$cell = new html_table_cell($content);
$cell->attributes['id'] = 'potentialcell';
$cells[] = $cell;

$table->data[] = new html_table_row($cells);

$btn_submit = html_writer::empty_tag('input', array('type'=>'submit','id'=>'add','name'=>'add','title'=>$strsubstitute,'value'=>$strsubstitute));
//AK-LM: utente già iscritto è un errore non bloccante! [segnalazione #58]
if(isset($errors['03']))
    $btn_submit = html_writer::empty_tag('input', array('type'=>'submit','id'=>'add','name'=>'add','title'=>$strsubstitute,'value'=>'Conferma sostituzione'));
$content1   = html_writer::tag('div', $btn_submit, array('id' => 'addcontrols'));
$cell1 = new html_table_cell($content1);
$cell1->attributes['id'] = 'buttonscell';
$cells1[] = $cell1;

$table->data[] = new html_table_row($cells1);

if($esito_notifiche){
    echo $esito_notifiche;
}
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

// Bottom of the page links
echo html_writer::start_tag('p');
$url = new moodle_url('/mod/facetoface/attendees.php', array('s' => $session->id, 'backtoallsessions' => $backtoallsessions));
echo "<a href='".$url."'><button type='button'>". get_string('goback', 'facetoface')."</button></a>";
//echo html_writer::link($url, get_string('goback', 'facetoface'));
echo html_writer::end_tag('p');
echo $OUTPUT->box_end();
echo $OUTPUT->footer($course);
