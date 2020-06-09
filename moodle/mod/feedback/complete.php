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
 * prints the form so the user can fill out the feedback
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_feedback
 */

require_once("../../config.php");
require_once("lib.php");
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot.'/f2_lib/core.php'); // 2017 09 29

global $USER; // 2017 09 29

feedback_init_feedback_session();

$id = required_param('id', PARAM_INT);
$completedid = optional_param('completedid', false, PARAM_INT);
$preservevalues  = optional_param('preservevalues', 0,  PARAM_INT);
$courseid = optional_param('courseid', false, PARAM_INT);
$gopage = optional_param('gopage', -1, PARAM_INT);
$lastpage = optional_param('lastpage', false, PARAM_INT);
$startitempos = optional_param('startitempos', 0, PARAM_INT);
$lastitempos = optional_param('lastitempos', 0, PARAM_INT);
$anonymous_response = optional_param('anonymous_response', 0, PARAM_INT); //arb
// 2017 09 29
$sessionID = optional_param('s',0, PARAM_INT); // id edizione
$docenteID = optional_param('d',0, PARAM_INT); // id docente
// 2017 09 29
$highlightrequired = false;

// 2017 09 29
// Numero di utenti, che per una data edizione, devo ancora compilare il questionario
$cntRemainUsers = ($sessionID!=0 and isset($id)) ? get_remains_feedbacks_edition($sessionID, $id) : 0;


/*
 * AK-LS:
 * 
 * In base al corso vengono tirate su 
 * tutte le edizioni con i loro codici 
 * e la data di inizio
 */
$sessions = $DB->get_records_sql("
		SELECT
			fs.id,
			(SELECT fsda.data FROM {facetoface_session_data} fsda, {facetoface_session_field} fsfi WHERE fsda.sessionid = fs.id AND fsda.fieldid = fsfi.id AND fsfi.shortname LIKE 'editionum') AS edition,
			(SELECT MIN(fsd.timestart) as timestart FROM {facetoface_sessions_dates} fsd WHERE fsd.sessionid = fs.id) as timestart
		FROM
			{course_modules} cm,
			{modules} m,
			{facetoface} f,
			{facetoface_sessions} fs
		WHERE
			cm.id = $id
			AND m.name LIKE 'feedback'
			AND m.id = cm.module
			AND cm.course = f.course
			AND f.id = fs.facetoface");

$dates = array();
foreach($sessions as $s)
	$dates[$s->id] = $s->timestart;

/*
 * AK-LS:
 * 
 * Una volta che èisponibile l'id dell'edizione
 * èossibile estrarre i docenti ad essa agganciati
 */
$docenti = $DB->get_records_sql("
		SELECT
			fsd.userid,
			u.firstname,
			u.lastname
                        , u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename 
		FROM
			{facetoface_sessions_docenti} fsd,
			{user} u
		WHERE
			fsd.sessionid = $sessionID
			AND u.id = fsd.userid");

?>
<!-- 
	AK-LS: Funzione che permette lo switch di edizione con relativo
	aggiornamento visuale delle date di inizio edizione e, in base alla
	modifica della URL del browser permette di conteggiare il numero 
	di utenti che necessitano ancora il completamento del questionario
-->
<script type="text/javascript">
	function sessionDate(obj, lbl1, lbl2) {
		var idx = obj.selectedIndex;
		var val = obj.options[idx].value;
		var arrDates = new Array();

		<?php foreach($dates as $key => $value) { ?>
		arrDates[<?php echo $key; ?>] = [<?php echo $value; ?>];
		<?php } ?>
		var date = new Date(parseInt(arrDates[val])*1000); // UNIX timestamp

		if(document.getElementById(lbl1)){
			document.getElementById(lbl1).value = date.getDate()+'/'+(date.getMonth()+1)+'/'+date.getFullYear();
		}
		// Riscrittura dell'indirizzo contenuto in Location

		var URLnew = "";
		var URLparams = document.location.search.split("&");
		var prm = "";
		var URLchange = false;
		for (i=0; i < URLparams.length; i++) {
		    prm = URLparams[i].split("=");
		    if (prm[0] == "s") {
		      prm[1] = val;
		      URLchange = true;
		      URLparams[i] = prm.join("=");
		      
		    }
		}
		
		if (URLchange)
			URLnew = URLparams.join("&");
		else 
			URLnew = document.location.href+"&s="+val+"&d=";

		location.href = URLnew;
	}

    function docenteChange(obj) {
		var idx = obj.selectedIndex;
		var val = obj.options[idx].value;

		// Riscrittura dell'indirizzo contenuto in Location

		var URLnew = "";
		var URLparams = document.location.search.split("&");
		var prm = "";
		var URLchange = false;
		for (i=0; i < URLparams.length; i++) {
		    prm = URLparams[i].split("=");
		    if (prm[0] == "d") {
		      prm[1] = val;
		      URLchange = true;
		      URLparams[i] = prm.join("=");
		      
		    }
		}
		
		if (URLchange)
			URLnew = URLparams.join("&");
		else 
			URLnew = document.location.href+"&d="+val;

		location.href = URLnew;
	}
</script>
<?php 
// 2017 09 29

if (($formdata = data_submitted()) AND !confirm_sesskey()) {
    print_error('invalidsesskey');
}

//if the use hit enter into a textfield so the form should not submit
if (isset($formdata->sesskey) AND
    !isset($formdata->savevalues) AND
    !isset($formdata->gonextpage) AND
    !isset($formdata->gopreviouspage)) {

    $gopage = $formdata->lastpage;
}

if (isset($formdata->savevalues)) {
    $savevalues = true;
} else {
    $savevalues = false;
}

if ($gopage < 0 AND !$savevalues) {
    if (isset($formdata->gonextpage)) {
        $gopage = $lastpage + 1;
        $gonextpage = true;
        $gopreviouspage = false;
    } else if (isset($formdata->gopreviouspage)) {
        $gopage = $lastpage - 1;
        $gonextpage = false;
        $gopreviouspage = true;
    } else {
        print_error('missingparameter');
    }
} else {
    $gonextpage = $gopreviouspage = false;
}

if (! $cm = get_coursemodule_from_id('feedback', $id)) {
    print_error('invalidcoursemodule');
}

if (! $course = $DB->get_record("course", array("id"=>$cm->course))) {
    print_error('coursemisconf');
}

if (! $feedback = $DB->get_record("feedback", array("id"=>$cm->instance))) {
    print_error('invalidcoursemodule');
}

$context = context_module::instance($cm->id);

$feedback_complete_cap = false;

if (has_capability('mod/feedback:complete', $context)) {
    $feedback_complete_cap = true;
}

//check whether the feedback is located and! started from the mainsite
if ($course->id == SITEID AND !$courseid) {
    $courseid = SITEID;
}

// 2017 09 29
/*
 * AK-LS:
 * 
 * Seleziono i ruoli dell'utente sul corso. Se ètudente non mostro i menu a tendina per la selezione delle edizioni.
 */
$isStudente = false;
if ($course->id) {
    $context_c = get_context_instance(CONTEXT_COURSE, $course->id);
    $roles = get_user_roles($context_c, $USER->id, false);
    
    foreach ($roles as $role) {
        if ($role->roleid == 5) {
            $isStudente = true;
            break;
        }
    }
}
// 2017 09 29

//check whether the feedback is mapped to the given courseid
if ($course->id == SITEID AND !has_capability('mod/feedback:edititems', $context)) {
    if ($DB->get_records('feedback_sitecourse_map', array('feedbackid'=>$feedback->id))) {
        $params = array('feedbackid'=>$feedback->id, 'courseid'=>$courseid);
        if (!$DB->get_record('feedback_sitecourse_map', $params)) {
            print_error('notavailable', 'feedback');
        }
    }
}

if ($feedback->anonymous != FEEDBACK_ANONYMOUS_YES) {
    if ($course->id == SITEID) {
        require_login($course, true);
    } else {
        require_login($course, true, $cm);
    }
} else {
    if ($course->id == SITEID) {
        require_course_login($course, true);
    } else {
        require_course_login($course, true, $cm);
    }
}

//check whether the given courseid exists
if ($courseid AND $courseid != SITEID) {
    if ($course2 = $DB->get_record('course', array('id'=>$courseid))) {
        require_course_login($course2); //this overwrites the object $course :-(
        $course = $DB->get_record("course", array("id"=>$cm->course)); // the workaround
    } else {
        print_error('invalidcourseid');
    }
}

if (!$feedback_complete_cap) {
    print_error('error');
}

// Mark activity viewed for completion-tracking
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

/// Print the page header
$strfeedbacks = get_string("modulenameplural", "feedback");
$strfeedback  = get_string("modulename", "feedback");

if ($course->id == SITEID) {
    $PAGE->set_cm($cm, $course); // set's up global $COURSE
    $PAGE->set_pagelayout('incourse');
}

$PAGE->navbar->add(get_string('feedback:complete', 'feedback'));
$urlparams = array('id'=>$cm->id, 'gopage'=>$gopage, 'courseid'=>$course->id);
$PAGE->set_url('/mod/feedback/complete.php', $urlparams);
$PAGE->set_heading($course->fullname);
$PAGE->set_title($feedback->name);
echo $OUTPUT->header();

//ishidden check.
//feedback in courses
if ((empty($cm->visible) AND
        !has_capability('moodle/course:viewhiddenactivities', $context)) AND
        $course->id != SITEID) {
    notice(get_string("activityiscurrentlyhidden"));
}

//ishidden check.
//feedback on mainsite
if ((empty($cm->visible) AND
        !has_capability('moodle/course:viewhiddenactivities', $context)) AND
        $courseid == SITEID) {
    notice(get_string("activityiscurrentlyhidden"));
}

//check, if the feedback is open (timeopen, timeclose)
$checktime = time();
$feedback_is_closed = ($feedback->timeopen > $checktime) ||
                      ($feedback->timeclose < $checktime &&
                            $feedback->timeclose > 0);

if ($feedback_is_closed) {
    echo $OUTPUT->heading(format_string($feedback->name));
    echo $OUTPUT->box_start('generalbox boxaligncenter');
    echo $OUTPUT->notification(get_string('feedback_is_not_open', 'feedback'));
    echo $OUTPUT->continue_button($CFG->wwwroot.'/course/view.php?id='.$course->id);
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    exit;
}

//additional check for multiple-submit (prevent browsers back-button).
//the main-check is in view.php
$feedback_can_submit = true;
if ($feedback->multiple_submit == 0 ) {
    if (feedback_is_already_submitted($feedback->id, $courseid)) {
        $feedback_can_submit = false;
    }
}
if ($feedback_can_submit) {
    //preserving the items
    if ($preservevalues == 1) {
        if (!isset($SESSION->feedback->is_started) OR !$SESSION->feedback->is_started == true) {
            print_error('error', '', $CFG->wwwroot.'/course/view.php?id='.$course->id);
        }
        // Check if all required items have a value.
        if (feedback_check_values($startitempos, $lastitempos)) {
// 2017 09 29
            if ($docenteID)
                $userid = $docenteID;
            else
// 2017 09 29
            $userid = $USER->id; //arb
// 2017 09 29
            /*
             * AK-LS:
             * 
             * Nel caso di debbano salvare i valori degli item per 
             * i docenti, èecessario passare alla funzione gli id 
             * dei docenti, in quanto bisogna generare il keyname 
             * dell'item che comprende tale informazione, per accedere 
             * all'oggetto specifico nella heap
             */
            
            $docentiIDs = array();
            if (!empty($docenti)) {
            	foreach ($docenti as $docente)
            		$docentiIDs[$docente->userid] = $docente->userid;
            }
// 2017 09 29
            //if ($completedid = feedback_save_values($USER->id, true)) {
            // 2017 11 23
            if ($completedid = feedback_save_values($USER->id, true, $sessionID)) {
                if (!$gonextpage AND !$gopreviouspage) {
                    $preservevalues = false;// It can be stored.
                }

            } else {
                $savereturn = 'failed';
                if (isset($lastpage)) {
                    $gopage = $lastpage;
                } else {
                    print_error('missingparameter');
                }
            }
        } else {
            $savereturn = 'missing';
            $highlightrequired = true;
            if (isset($lastpage)) {
                $gopage = $lastpage;
            } else {
                print_error('missingparameter');
            }

        }
    }

    //saving the items
    if ($savevalues AND !$preservevalues) {
        //exists there any pagebreak, so there are values in the feedback_valuetmp
        //$userid = $USER->id; //arb // 2017 09 29
// 2017 09 29
    	/*
    	 * AK-LS:
    	 * 
    	 * Poichèon sempre chi completa il Feedback è'utente che 
    	 * ènche stato consuntivato presente al corso, bisogna sempre 
    	 * generare una userid valida, ossia di un utente che non ha 
    	 * ancora completato il Feedback e lo puòre.
    	 */
    	
        if ($docenteID)
            $userid = $docenteID;
        else
            $userid = $USER->id;
        
       // is_user_complete_feedback
    	if (!$feedback->consenti_compilazione_utenti) {
    		//$userid = $DB->get_field('feedback_completedtmp', 'userid', array('id' => $completedid, 'feedback' => $feedback->id));
    		if($feedback->name <> get_string('nome_feedback_docente', 'local_f2_import_course'))
                $userid = 0;
    	//Se viene compilato da un referente viene inserito nella tabella feedback userid=0;
    	}
// 2017 09 29
        if ($feedback->anonymous == FEEDBACK_ANONYMOUS_NO) {
            $feedbackcompleted = feedback_get_current_completed($feedback->id, false, $courseid);
        } else {
            $feedbackcompleted = false;
        }
        $params = array('id' => $completedid);
        $feedbackcompletedtmp = $DB->get_record('feedback_completedtmp', $params);
        //fake saving for switchrole
        $is_switchrole = feedback_check_is_switchrole();
        if ($is_switchrole) {
            $savereturn = 'saved';
            feedback_delete_completedtmp($completedid);
        } else {
            $new_completed_id = feedback_save_tmp_values($feedbackcompletedtmp,
                                                         $feedbackcompleted,
                                                         $userid,
                                                         $sessionID);
            if ($new_completed_id) {
                $savereturn = 'saved';
                if ($feedback->anonymous == FEEDBACK_ANONYMOUS_NO) {
                    feedback_send_email($cm, $feedback, $course, $userid);
                } else {
                    feedback_send_email_anonym($cm, $feedback, $course, $userid);
                }
                //tracking the submit
                $tracking = new stdClass();
                //$tracking->userid = $USER->id;
                $tracking->userid = $userid; // 2017 09 29
                $tracking->feedback = $feedback->id;
                $tracking->completed = $new_completed_id;
                $DB->insert_record('feedback_tracking', $tracking);
                unset($SESSION->feedback->is_started);

                // Update completion state
                $completion = new completion_info($course);
                if ($completion->is_enabled($cm) && $feedback->completionsubmit) {
                    $completion->update_state($cm, COMPLETION_COMPLETE);
                }

            } else {
                $savereturn = 'failed';
            }
        }

    }


    if ($allbreaks = feedback_get_all_break_positions($feedback->id)) {
        if ($gopage <= 0) {
            $startposition = 0;
        } else {
            if (!isset($allbreaks[$gopage - 1])) {
                $gopage = count($allbreaks);
            }
            $startposition = $allbreaks[$gopage - 1];
        }
        $ispagebreak = true;
    } else {
        $startposition = 0;
        $newpage = 0;
        $ispagebreak = false;
    }

    //get the feedbackitems after the last shown pagebreak
    $select = 'feedback = ? AND position > ?';
    $params = array($feedback->id, $startposition);
    $feedbackitems = $DB->get_records_select('feedback_item', $select, $params, 'position');

    //get the first pagebreak
    $params = array('feedback' => $feedback->id, 'typ' => 'pagebreak');
    if ($pagebreaks = $DB->get_records('feedback_item', $params, 'position')) {
        $pagebreaks = array_values($pagebreaks);
        $firstpagebreak = $pagebreaks[0];
    } else {
        $firstpagebreak = false;
    }
    $maxitemcount = $DB->count_records('feedback_item', array('feedback'=>$feedback->id));

    //get the values of completeds before done. Anonymous user can not get these values.
    if ((!isset($SESSION->feedback->is_started)) AND
                          (!isset($savereturn)) AND
                          ($feedback->anonymous == FEEDBACK_ANONYMOUS_NO)) {

        $feedbackcompletedtmp = feedback_get_current_completed($feedback->id, true, $courseid);
        if (!$feedbackcompletedtmp) {
            $feedbackcompleted = feedback_get_current_completed($feedback->id, false, $courseid);
            if ($feedbackcompleted) {
                //copy the values to feedback_valuetmp create a completedtmp
                $feedbackcompletedtmp = feedback_set_tmp_values($feedbackcompleted);
            }
        }
    } else {
        $feedbackcompletedtmp = feedback_get_current_completed($feedback->id, true, $courseid);
    }

    /// Print the main part of the page
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////
    $analysisurl = new moodle_url('/mod/feedback/analysis.php', array('id'=>$id));
    if ($courseid > 0) {
        $analysisurl->param('courseid', $courseid);
    }
    echo $OUTPUT->heading(format_string($feedback->name));

    if ( (intval($feedback->publish_stats) == 1) AND
            ( has_capability('mod/feedback:viewanalysepage', $context)) AND
            !( has_capability('mod/feedback:viewreports', $context)) ) {
// 2017 09 29
        //$params = array('userid' => $USER->id, 'feedback' => $feedback->id);
        $userid = $DB->get_field('feedback_completed', 'userid', array('id' => $feedbackcompleted));
    	$params = array('userid' => $userid, 'feedback' => $feedback->id);
// 2017 09 29
        if ($multiple_count = $DB->count_records('feedback_tracking', $params)) {
            echo $OUTPUT->box_start('mdl-align');
            echo '<a href="'.$analysisurl->out().'">';
            echo get_string('completed_feedbacks', 'feedback').'</a>';
            echo $OUTPUT->box_end();
        }
    }

    if (isset($savereturn) && $savereturn == 'saved') {
        if ($feedback->page_after_submit) {

            require_once($CFG->libdir . '/filelib.php');

            $page_after_submit_output = file_rewrite_pluginfile_urls($feedback->page_after_submit,
                                                                    'pluginfile.php',
                                                                    $context->id,
                                                                    'mod_feedback',
                                                                    'page_after_submit',
                                                                    0);

            echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
            echo format_text($page_after_submit_output,
                             $feedback->page_after_submitformat,
                             array('overflowdiv' => true));
            echo $OUTPUT->box_end();
        } else {
            echo '<p align="center">';
            echo '<b><font color="green">';
            echo get_string('entries_saved', 'feedback');
            echo '</font></b>';
            echo '</p>';
            if ( intval($feedback->publish_stats) == 1) {
                echo '<p align="center"><a href="'.$analysisurl->out().'">';
                echo get_string('completed_feedbacks', 'feedback').'</a>';
                echo '</p>';
            }
        }

        if ($feedback->site_after_submit) {
            $url = feedback_encode_target_url($feedback->site_after_submit);
        } else {
            if ($courseid) {
                if ($courseid == SITEID) {
                    $url = $CFG->wwwroot;
                } else {
                    $url = $CFG->wwwroot.'/course/view.php?id='.$courseid;
                }
            } else {
                if ($course->id == SITEID) {
                    $url = $CFG->wwwroot;
                } else {
                    $url = $CFG->wwwroot.'/course/view.php?id='.$course->id;
                }
            }
        }
        echo $OUTPUT->continue_button($url);
    } else {
        if (isset($savereturn) && $savereturn == 'failed') {
            echo $OUTPUT->box_start('mform');
            echo '<span class="error">'.get_string('saving_failed', 'feedback').'</span>';
            echo $OUTPUT->box_end();
        }

        if (isset($savereturn) && $savereturn == 'missing') {
            echo $OUTPUT->box_start('mform');
            echo '<span class="error">'.get_string('saving_failed_because_missing_or_false_values', 'feedback').'</span>';
            echo $OUTPUT->box_end();
        }

// 2017 09 29
        $lbl1 = "lblIDDate";
        $lbl2 = "lblCnt";
        $chkSessionExists = false;
        	
		/*
		 * AK-LS:
		 * 
		 * Header feedback
		 */
        //AK-LM: correzione bug #98
        if (isSupervisore($USER->id) || !$isStudente) {
            echo $OUTPUT->box_start('feedback_header');
            echo html_writer::start_tag('table');
            echo html_writer::start_tag('tr');
            echo html_writer::start_tag('p');
            echo html_writer::tag('label', get_string('feedback:selected', 'local_f2_traduzioni'));
            echo html_writer::end_tag('p');
            echo html_writer::end_tag('tr');
            echo html_writer::start_tag('tr');
            echo html_writer::start_tag('td');
            echo html_writer::tag('label', get_string('feedback:ed', 'local_f2_traduzioni').'&nbsp;');
            echo html_writer::start_tag('select', array('name' => 'session_select', 'onchange' => 'return sessionDate(this, "'.$lbl1.'", "'.$lbl2.'")'));
            if ($sessionID != 0)
                    echo html_writer::start_tag('option');
            else
                    echo html_writer::start_tag('option', array('selected'));
            echo html_writer::tag('label', 'Scegli...');
            echo html_writer::end_tag('option');
            foreach ($sessions as $s) {
                    if ($s->id == $sessionID) {
                            echo html_writer::start_tag('option', array('value' => $s->id, 'selected' => 'selected'));
                            $chkSessionExists = true;
                    } else 
                            echo html_writer::start_tag('option', array('value' => $s->id));
                echo html_writer::tag('label', 'Edizione_'.$s->edition);
                echo html_writer::end_tag('option');
            }
            echo html_writer::end_tag('select');
            echo html_writer::end_tag('td');
            if ($feedback->name == get_string('nome_feedback_docente', 'local_f2_import_course')) {
               /* se il questionario èi tipo: "Questionario nota di sintesi del docente" 
                * devo dare all'utente la possibilitài selezionare da un menu a tendina 
                * il docente per cui si vuole compilare il questionario
                */
               $docenti_select = $DB->get_records_sql("
                               SELECT
                                       fsd.userid as id,
                                       u.firstname, 
                                       u.lastname
                                       , u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename 
                               FROM
                                       {facetoface_sessions_docenti} fsd,
                                       {user} u
                               WHERE
                                       fsd.sessionid = $sessionID
                                       AND u.id = fsd.userid
                                       AND fsd.userid NOT IN (
                                            SELECT 
                                                            DISTINCT fec.userid
                                                    FROM
                                                            {course_modules} cm,
                                                            {course} c,
                                                            {modules} m,
                                                            {feedback} fe,
                                                            {feedback_completed} fec,
                                                            {feedback_completed_session} fes
                                                    WHERE 
                                                            cm.id = $cm->id 
                                                            AND cm.course = c.id 
                                                            AND m.name LIKE 'feedback' 
                                                            AND m.id = cm.module 
                                                            AND cm.instance = fe.id 
                                                            AND fe.id = fec.feedback
                                                            AND fec.id = fes.completed
                                                            AND fes.feedback = fe.id 
                                                            AND fes.session = $sessionID
                                       )");

                echo html_writer::start_tag('td');
                echo html_writer::tag('label', get_string('feedback:docente', 'local_f2_traduzioni').'&nbsp;');
                echo html_writer::start_tag('select', array('name' => 'docente_select', 'onchange' => 'return docenteChange(this)'));
                if ($docenteID != 0)
                        echo html_writer::start_tag('option');
                else
                        echo html_writer::start_tag('option', array('selected'));
                echo html_writer::tag('label', 'Scegli...');
                echo html_writer::end_tag('option');
                foreach ($docenti_select as $d) {
                        if ($d->id == $docenteID) {
                                echo html_writer::start_tag('option', array('value' => $d->id, 'selected' => 'selected'));
                        } else 
                                echo html_writer::start_tag('option', array('value' => $d->id));
                    echo html_writer::tag('label', fullname($d));
                    echo html_writer::end_tag('option');
                }
                echo html_writer::end_tag('select');
                echo html_writer::end_tag('td');
            }else{
					echo html_writer::start_tag('td');
					echo html_writer::tag('label', get_string('feedback:dateed', 'local_f2_traduzioni').'&nbsp;');
					if ($chkSessionExists)
							echo html_writer::empty_tag('input', array('id' => $lbl1, 'type' => 'text', 'disabled' => 'disabled', 'value' => date('d/m/Y',$dates[$sessionID])));
					else
							echo html_writer::empty_tag('input', array('id' => $lbl1, 'type' => 'text', 'disabled' => 'disabled'));
					echo html_writer::end_tag('td');
					echo html_writer::start_tag('td');
					echo html_writer::tag('label', get_string('feedback:remaincompile', 'local_f2_traduzioni').'&nbsp;');
					if ($chkSessionExists)
							echo html_writer::empty_tag('input', array('id' => $lbl2, 'type' => 'text', 'disabled' => 'disabled', 'value' => $cntRemainUsers));
					else
							echo html_writer::empty_tag('input', array('id' => $lbl2, 'type' => 'text', 'disabled' => 'disabled'));
					echo html_writer::end_tag('td');
			}
            echo html_writer::end_tag('tr');
            echo html_writer::end_tag('table');
            echo $OUTPUT->box_end('feedback_header');
        } else {       	
            // se l'utente loggato èno studente, devo verificare che sia stato presente ad una edizione del corso
            //$edizione = is_student_fully_attended($USER->id, $course->id);
            $edizione = can_fill_feedback($USER->id, $course->id);

            if ($edizione){
                $sessionID = $edizione->id;
            
            
                $docenti_select = $DB->get_records_sql("
            		SELECT
            		fsd.userid as id,
            		u.firstname, 
                        u.lastname
                        , u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename 
            		FROM
            		{facetoface_sessions_docenti} fsd,
            		{user} u
            		WHERE
            		fsd.sessionid = $sessionID
            		AND u.id = fsd.userid
            		AND fsd.userid NOT IN (
            		SELECT
            		DISTINCT fec.userid
            		FROM
            		{course_modules} cm,
            		{course} c,
            		{modules} m,
            		{feedback} fe,
            		{feedback_completed} fec,
            		{feedback_completed_session} fes
            		WHERE
            		cm.id = $cm->id
            		AND cm.course = c.id
            		AND m.name LIKE 'feedback'
            		AND m.id = cm.module
            		AND cm.instance = fe.id
            		AND fe.id = fec.feedback
            		AND fec.id = fes.completed
            		AND fes.feedback = fe.id
            		AND fes.session = $sessionID)");
            
            }
            else
            {
    			echo '<div align="center"><p class="errormessage">'.get_string('feedback:nosessioncompleted', 'local_f2_traduzioni').'</p><p class="errorcode"></p></div>';
            	echo $OUTPUT->continue_button($CFG->wwwroot.'/course/view.php?id='.$COURSE->id);
            	echo $OUTPUT->footer();
            	die;
    		//print_error(get_string('feedback:nosessioncompleted', 'local_f2_traduzioni'),'','');	 
            }

           /*
            * AK-LS:
            * 
            * Una volta che èisponibile l'id dell'edizione,
            * èossibile estrarre i docenti ad essa agganciati.
            */
           $docenti = $DB->get_records_sql("
                           SELECT
                                   fsd.userid,
                                   u.firstname,
                                   u.lastname
                                   , u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename
                           FROM
                                   {facetoface_sessions_docenti} fsd,
                                   {user} u
                           WHERE
                                   fsd.sessionid = $sessionID
                                   AND u.id = fsd.userid");
       
            // Numero di utenti, che per una data edizione, devo ancora compilare il questionario
            $cntRemainUsers = ($sessionID!=0 and isset($id)) ? get_remains_feedbacks_edition($sessionID, $id, $feedback->name, $docenti) : 0;
        }
        $booked_users = get_users_booked_edition($sessionID, $id);
        if($feedback->name <> get_string('nome_feedback_docente', 'local_f2_import_course')){
            if(!$sessionID){
                echo '<br><b style="color:red">'.get_string('seleziona_edizione', 'feedback').'</b>';
            }
            else if(!$docenti){
                echo '<br><b style="color:red">'.get_string('no_docente', 'feedback').'</b>';
            }
            //AK-LM: correzione bug #98
            else if(!isSupervisore($USER->id) && $isStudente && !$feedback->consenti_compilazione_utenti){
            //else if(!isSupervisore($USER->id) && $isStudente){ // DEBUG
                //echo '<b style="color:red">'.get_string('no_permessi_questionario', 'feedback').' - Uid: '.$USER->id.' - Sup: '.isSupervisore($USER->id).' - Stud: '.$isStudente.' - ccu: '.$feedback->consenti_compilazione_utenti.'</b>';// DEBUG
                echo '<b style="color:red">'.get_string('no_permessi_questionario', 'feedback').'</b>';
            }
            else if(!$booked_users){
                echo '<b style="color:red">Non &egrave; presente nessun utente.</b>';
            }
            else if(!$cntRemainUsers){
                echo '<b style="color:red">'.get_string('limite_questionario_utenti', 'feedback').'</b>';
            }
        }else if($feedback->name == get_string('nome_feedback_docente', 'local_f2_import_course')){
            if(!$sessionID){
                echo '<br><b style="color:red">'.get_string('seleziona_edizione', 'feedback').'</b>';
            }else if(!$docenti){
                echo '<br><b style="color:red">'.get_string('no_docente', 'feedback').'</b>';
            }else if(!$docenti_select){
                echo '<b style="color:red">'.get_string('limite_questionario_docenti', 'feedback').'</b>';
            }
        }
// 2017 09 29

        //print the items
        if (is_array($feedbackitems)) {
            echo $OUTPUT->box_start('feedback_form');
            echo '<form action="complete.php" class="mform" method="post" onsubmit=" ">';
            echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
// 2017 09 29
            echo $OUTPUT->box_start('feedback_f2_info');
            echo get_string('f2_fb_info', 'feedback');
            echo '<br /><br />';
            echo $OUTPUT->box_end();
// 2017 09 29
            echo $OUTPUT->box_start('feedback_anonymousinfo');
            switch ($feedback->anonymous) {
                case FEEDBACK_ANONYMOUS_YES:
                    echo '<input type="hidden" name="anonymous" value="1" />';
                    $inputvalue = 'value="'.FEEDBACK_ANONYMOUS_YES.'"';
                    echo '<input type="hidden" name="anonymous_response" '.$inputvalue.' />';
                    echo get_string('mode', 'feedback').': '.get_string('anonymous', 'feedback');
                    break;
                case FEEDBACK_ANONYMOUS_NO:
                    echo '<input type="hidden" name="anonymous" value="0" />';
                    $inputvalue = 'value="'.FEEDBACK_ANONYMOUS_NO.'"';
                    echo '<input type="hidden" name="anonymous_response" '.$inputvalue.' />';
                    echo get_string('mode', 'feedback').': ';
                    echo get_string('non_anonymous', 'feedback');
                    break;
            }
            echo $OUTPUT->box_end();
            //check, if there exists required-elements
            $params = array('feedback' => $feedback->id, 'required' => 1);
            $countreq = $DB->count_records('feedback_item', $params);
            if ($countreq > 0) {
                echo '<span class="fdescription required">';
                echo get_string('somefieldsrequired', 'form', '<img alt="'.get_string('requiredelement', 'form').
                    '" src="'.$OUTPUT->pix_url('req') .'" class="req" />');
                echo '</span>';
            }
            echo $OUTPUT->box_start('feedback_items');

            unset($startitem);
            $select = 'feedback = ? AND hasvalue = 1 AND position < ?';
            $params = array($feedback->id, $startposition);
            $itemnr = $DB->count_records_select('feedback_item', $select, $params);
            $lastbreakposition = 0;
            $align = right_to_left() ? 'right' : 'left';

// 2017 09 29
            $criticalSection = false;
            $repeatItems = 0;
            $lastdocent = 0;
// 2017 09 29

            foreach ($feedbackitems as $feedbackitem) {
// 2017 09 29
                //AK-LM START: intestazione per le domande relative alla docenza per l'edizione.
                if ((int)$feedbackitem->teacher_item > 0) {
                    if ((int)$feedbackitem->teacher_item === (int)$sessionID) {
                        if ((int)$feedbackitem->teacherid !== $lastdocent) {
                            $nome_docente = fullname($docenti[$feedbackitem->teacherid]);
                            echo html_writer::start_tag('p');
                            echo html_writer::tag('label', $nome_docente);
                            echo html_writer::end_tag('p');
                            $lastdocent = (int)$feedbackitem->teacherid;
                        }
                    } else {
                        //Non èn docente di quest'edizione: aggiorno $maxitemcount e procedo con la successiva
                        $maxitemcount--;
                        continue;
                    }
                }
                //AK-LM END
// 2017 09 29
                if (!isset($startitem)) {
                    //avoid showing double pagebreaks
                    if ($feedbackitem->typ == 'pagebreak') {
                        continue;
                    }
                    $startitem = $feedbackitem;
                }

                if ($feedbackitem->dependitem > 0) {
                    //chech if the conditions are ok
                    $fb_compare_value = feedback_compare_item_value($feedbackcompletedtmp->id,
                                                                    $feedbackitem->dependitem,
                                                                    $feedbackitem->dependvalue,
                                                                    true);
                    if (!isset($feedbackcompletedtmp->id) OR !$fb_compare_value) {
                        $lastitem = $feedbackitem;
                        $lastbreakposition = $feedbackitem->position;
                        continue;
                    }
                }

                if ($feedbackitem->dependitem > 0) {
                    $dependstyle = ' feedback_complete_depend';
                } else {
                    $dependstyle = '';
                }

                echo $OUTPUT->box_start('feedback_item_box_'.$align.$dependstyle);
                $value = '';
                //get the value
                $frmvaluename = $feedbackitem->typ . '_'. $feedbackitem->id;
                if (isset($savereturn)) {
                    $value = isset($formdata->{$frmvaluename}) ? $formdata->{$frmvaluename} : null;
                    $value = feedback_clean_input_value($feedbackitem, $value);
                } else {
                    if (isset($feedbackcompletedtmp->id)) {
                        $value = feedback_get_item_value($feedbackcompletedtmp->id,
                                                         $feedbackitem->id,
                                                         true);
                    }
                }
                if ($feedbackitem->hasvalue == 1 AND $feedback->autonumbering) {
                    $itemnr++;
                    echo $OUTPUT->box_start('feedback_item_number_'.$align);
                    echo $itemnr;
                    echo $OUTPUT->box_end();
                }
                if ($feedbackitem->typ != 'pagebreak') {
                    echo $OUTPUT->box_start('box generalbox boxalign_'.$align);
                    feedback_print_item_complete($feedbackitem, $value, $highlightrequired);
                    echo $OUTPUT->box_end();
                }

                echo $OUTPUT->box_end();

                $lastbreakposition = $feedbackitem->position; //last item-pos (item or pagebreak)
                if ($feedbackitem->typ == 'pagebreak') {
                    break;
                } else {
                    $lastitem = $feedbackitem;
                }
            }
            echo $OUTPUT->box_end();
            echo '<input type="hidden" name="id" value="'.$id.'" />';
// 2017 09 29
            // AK-LS: aggiunta hidden value che contiene l'id dell'edizione
            echo '<input type="hidden" name="s" value="'.$sessionID.'" />';
            echo '<input type="hidden" name="d" value="'.$docenteID.'" />';
// 2017 09 29
            echo '<input type="hidden" name="feedbackid" value="'.$feedback->id.'" />';
            echo '<input type="hidden" name="lastpage" value="'.$gopage.'" />';
            if (isset($feedbackcompletedtmp->id)) {
                $inputvalue = 'value="'.$feedbackcompletedtmp->id.'"';
            } else {
                $inputvalue = 'value=""';
            }
            echo '<input type="hidden" name="completedid" '.$inputvalue.' />';
            echo '<input type="hidden" name="courseid" value="'. $courseid . '" />';
            echo '<input type="hidden" name="preservevalues" value="1" />';
            if (isset($startitem)) {
                echo '<input type="hidden" name="startitempos" value="'.$startitem->position.'" />';
                echo '<input type="hidden" name="lastitempos" value="'.$lastitem->position.'" />';
            }

            if ( $ispagebreak AND $lastbreakposition > $firstpagebreak->position) {
                $inputvalue = 'value="'.get_string('previous_page', 'feedback').'"';
                echo '<input name="gopreviouspage" type="submit" '.$inputvalue.' />';
            }
            if ($lastbreakposition < $maxitemcount) {
                $inputvalue = 'value="'.get_string('next_page', 'feedback').'"';
                echo '<input name="gonextpage" type="submit" '.$inputvalue.' />';
            }
            //if ($lastbreakposition >= $maxitemcount) { //last page
// 2017 09 29
            if($feedback->name <> get_string('nome_feedback_docente', 'local_f2_import_course')){
                // AK-LS: se non ci sono piùnti per i quali possa essere completato il Feedback, èmpedito il salvataggio del suddetto.
                //AK-LM: correzione bug #98
                if(!isSupervisore($USER->id) && $isStudente && !$feedback->consenti_compilazione_utenti){
                    echo '<b style="color:red">'.get_string('no_permessi_questionario', 'feedback').'</b>';
                }
                else if ($lastbreakposition >= $maxitemcount && $cntRemainUsers && $docenti) { //last page
// 2017 09 29
                $inputvalue = 'value="'.get_string('save_entries', 'feedback').'"';
                echo '<input name="savevalues" type="submit" '.$inputvalue.' />';
            }
// 2017 09 29
                else if(!$sessionID){
                    echo '<br><b style="color:red">'.get_string('seleziona_edizione', 'feedback').'</b>';
                }
                else if(!$docenti){
                    echo '<br><b style="color:red">'.get_string('no_docente', 'feedback').'</b>';
                } else if(!$booked_users){
                    echo '<b style="color:red">Non &egrave; presente nessun utente.</b>';
                } else if(!$cntRemainUsers){
                    echo '<b style="color:red">'.get_string('limite_questionario_utenti', 'feedback').'</b>';
                }
            } else if($feedback->name == get_string('nome_feedback_docente', 'local_f2_import_course')){
                if(!$sessionID){
                    echo '<br><b style="color:red">'.get_string('seleziona_edizione', 'feedback').'</b>';
                } else if(!$docenti){
                    echo '<br><b style="color:red">'.get_string('no_docente', 'feedback').'</b>';
                } else if (!$docenti_select){
                    echo '<b style="color:red">'.get_string('limite_questionario_docenti', 'feedback').'</b>';
                } else if($docenti){
                    if($docenteID || !isset($_GET['d'])){
                        $inputvalue = 'value="'.get_string('save_entries', 'feedback').'"';
                        echo '<input name="savevalues" type="submit" '.$inputvalue.' />';
                    } else {
                        echo '<b style="color:red">Selezionare un docente.</b>';
                    }
                }
            }
// 2017 09 29

            echo '</form>';
            echo $OUTPUT->box_end();

            echo $OUTPUT->box_start('feedback_complete_cancel');
            if ($courseid) {
                $action = 'action="'.$CFG->wwwroot.'/course/view.php?id='.$courseid.'"';
            } else {
                if ($course->id == SITEID) {
                    $action = 'action="'.$CFG->wwwroot.'"';
                } else {
                    $action = 'action="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'"';
                }
            }
            echo '<form '.$action.' method="post" onsubmit=" ">';
            echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
            echo '<input type="hidden" name="courseid" value="'. $courseid . '" />';
            echo '<button type="submit">'.get_string('cancel').'</button>';
            echo '</form>';
            echo $OUTPUT->box_end();
            $SESSION->feedback->is_started = true;
        }
    }
} else {
    echo $OUTPUT->heading(format_string($feedback->name));
    echo $OUTPUT->box_start('generalbox boxaligncenter');
    echo $OUTPUT->notification(get_string('this_feedback_is_already_submitted', 'feedback'));
    echo $OUTPUT->continue_button($CFG->wwwroot.'/course/view.php?id='.$course->id);
    echo $OUTPUT->box_end();
}
/// Finish the page
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

// 2017 09 29
//AK-LM: set radio buttons click handler
$module = array('name'=>'mod_feedback', 'fullpath'=>'/mod/feedback/feedback.js');
$PAGE->requires->js_init_call('M.mod_feedback.set_radioclick_handler', null, true, $module);
// 2017 09 29

echo $OUTPUT->footer();
