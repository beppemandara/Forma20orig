<?php
//$Id: sessions.php 1 2012-09-26 l.sampo $

global $DB, $OUTPUT, $PAGE, $CFG;

require_once '../../config.php';
require_once 'lib.php';
require_once 'session_form.php';
require_once 'extends_sessions.php';
require_once($CFG->dirroot.'/f2_lib/constants.php');
require_once($CFG->dirroot.'/mod/feedback/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID
$f  = optional_param('f', 0, PARAM_INT); // facetoface Module ID
$s  = optional_param('s', 0, PARAM_INT); // facetoface session ID
$c  = optional_param('c', 0, PARAM_INT); // copy session
$d  = optional_param('d', 0, PARAM_INT); // delete session
$m  = optional_param('m', 0, PARAM_INT); // AK-LS: capability di modifica dell'edizione ridotta alle date
$confirm = optional_param('confirm', false, PARAM_BOOL); // delete confirmation
/*
// start aggiunta per i teacher
$add               = optional_param('add', 0, PARAM_BOOL);
$remove            = optional_param('remove', 0, PARAM_BOOL);
$showall           = optional_param('showall', 0, PARAM_BOOL);
$searchtext        = optional_param('searchtext', '', PARAM_TEXT); // search string
$previoussearch    = optional_param('previoussearch', 0, PARAM_BOOL);
$backtoallsessions = optional_param('backtoallsessions', 0, PARAM_INT); // facetoface activity to go back to
// end aggiunta per i teacher
*/

$nbdays = 1; // default number to show

/*
 * AK-LS campi necessari come riferimento al corso per la gestione delle informazione ad esso associate
 * $sysFormatori è l'elenco completo di docenti nel sistema
 */

$selectdocenti = "";

$session = null;
//if ($id) { // 2019 05 03
if ($id && !$s) {
    if (!$cm = $DB->get_record('course_modules', array('id' => $id))) {
        print_error('error:incorrectcoursemoduleid', 'facetoface');
    }
    if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('error:coursemisconfigured', 'facetoface');
    }
    if (!$facetoface =$DB->get_record('facetoface',array('id' => $cm->instance))) {
        print_error('error:incorrectcoursemodule', 'facetoface');
    }
} elseif ($s) {
    if (!$session = facetoface_get_session($s)) {
        print_error('error:incorrectcoursemodulesession', 'facetoface');
    }
    if (!$facetoface = $DB->get_record('facetoface',array('id' => $session->facetoface))) {
        print_error('error:incorrectfacetofaceid', 'facetoface');
    }
    if (!$course = $DB->get_record('course', array('id'=> $facetoface->course))) {
        print_error('error:coursemisconfigured', 'facetoface');
    }
    if (!$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id)) {
        print_error('error:incorrectcoursemoduleid', 'facetoface');
    }

    $nbdays = count($session->sessiondates);
} else {
    if (!$facetoface = $DB->get_record('facetoface', array('id' => $f))) {
        print_error('error:incorrectfacetofaceid', 'facetoface');
    }
    if (!$course = $DB->get_record('course', array('id' => $facetoface->course))) {
        print_error('error:coursemisconfigured', 'facetoface');
    }
    if (!$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id)) {
        print_error('error:incorrectcoursemoduleid', 'facetoface');
    }
}
$f2course = $DB->get_record('f2_anagrafica_corsi', array('courseid' => $course->id), 'course_type,durata', MUST_EXIST);
$isPRO = (C_PRO === (int)($f2course->course_type));
$isOBB = (C_OBB === (int)($f2course->course_type));

$durata = substr($f2course->durata, 0, -1);

require_course_login($course);
$errorstr = '';
//AK:Reply $context = context_course::instance($course->id);
$context = context_module::instance($cm->id);

$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('standard');

// AK-LS: permesso necessario a seconda dell'operabilità dell'utente
// Assunzione forte sulla costruzione del permesso, es. editsessionsdates{coursetype}
// per i corsi obb tale permesso non esiste, MA la chiamata al componente 
// sessions.php passa solo e soltanto attraverso view.php dove viene settato
// o meno il parametro opzionale m
// AK-LM: segnalo che la chiamata a questa pag. passa anche per editattendees.php
$capabilityscope = ($isPRO) ? "prg" : "obb";
if (!is_null($session)) {
    if ($m)
	require_capability('mod/facetoface:editsessionsdates'.$capabilityscope, $context);
    else
	require_capability('mod/facetoface:editsessions'.$capabilityscope, $context);
} else require_capability('mod/facetoface:editsessions'.$capabilityscope, $context);

// Get some language strings
$strsearch        = get_string('search');
$strshowall       = get_string('showall');
$strsearchresults = get_string('searchresults');
$strfacetofaces   = get_string('modulenameplural', 'facetoface');
$strfacetoface    = get_string('modulename', 'facetoface');

$returnurl = "view.php?f=$facetoface->id";

// 2019 05 03
$editoroptions = array(
    'noclean'  => false,
    'maxfiles' => EDITOR_UNLIMITED_FILES,
    'maxbytes' => $course->maxbytes,
    'context'  => $modulecontext,
);
// 2019 05 03

/*
if ($s && $s > 0) {
    $returnurl = "sessions.php?s=$s";
} else {
    $returnurl = "view.php?f=$facetoface->id";
}
*/

// Handle deletions
if ($d and $confirm) {
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error');
    }

    if (facetoface_delete_session($session)) {
        add_to_log($course->id, 'facetoface', 'delete session', 'sessions.php?s='.$session->id, $facetoface->id, $cm->id);
    } else {
        add_to_log($course->id, 'facetoface', 'delete session (FAILED)', 'sessions.php?s='.$session->id, $facetoface->id, $cm->id);
        print_error('error:couldnotdeletesession', 'facetoface', $returnurl);
    }
    redirect($returnurl);
}

/*
 * AK-LS: $s==TRUE modalità di EDIT, altrimenti creazione dell'edizione, per 
 * la gestione del multiselect dei formatori
 */

// AK-LM : visibilita' solo sulle date dell'edizione (ref.scuola) => il solo customfield gestito è 'periodo'
if ($m)
    $customfields = $DB->get_records('facetoface_session_field', array('shortname'=>'periodo'));
else
    $customfields = facetoface_get_session_customfields();

//AK-LM: QdG discenti
$feedbackid = f2_feedback_get_student_feedback($course->id);

/*
$mform = new mod_facetoface_session_form(null, 
                                         compact('id', 'f', 's', 'c', 'nbdays', 'customfields', 'course', 
                                                 'coursetype', 'durata', 'm', 'feedbackid'), 
                                         'post', '', 'onsubmit="return validateDates();"');
*/
// 2019 05 03
$mform = new mod_facetoface_session_form(null,
                                         compact('id', 'f', 's', 'c', 'nbdays', 'customfields', 'course', 
                                                 'coursetype', 'durata', 'm', 'feedbackid'),
                                         'post', '', 'onsubmit="return validateDates();"', $editoroptions);
// 2019 05 03

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($fromform = $mform->get_data()) { // Form submitted 
    // AKTIVE: se l'utente ha disabilitato la data di protocollazione allora lascio vuoto il custom field
    if (!empty($fromform->disable_sirpdata)) {
        if ($fromform->disable_sirpdata) {
            if (!empty($fromform->custom_sirpdata)) {
                $fromform->custom_sirpdata = '';
            }
        }
    }
    if (empty($fromform->submitbutton)) {
        print_error('error:unknownbuttonclicked', 'facetoface', $returnurl);
    }
    // Pre-process fields
    if (empty($fromform->allowoverbook)) {
        $fromform->allowoverbook = 0;
    }
    if (empty($fromform->duration)) {
        $fromform->duration = 0;
    }
    if (empty($fromform->normalcost)) {
        $fromform->normalcost = 0;
    }
    if (empty($fromform->discountcost)) {
        $fromform->discountcost = 0;
    }
    
    $sessiondates = array();
    for ($i = 0; $i < $fromform->date_repeats; $i++) {
        if (!empty($fromform->datedelete[$i])) {
            continue; // skip this date
        }
        
        $timestartfield = "timestart[$i]";
        $timefinishfield = "timefinish[$i]";
        if (!empty($fromform->$timestartfield) and !empty($fromform->$timefinishfield)) {
            $date = new stdClass();
            $date->timestart = $fromform->$timestartfield;
            $date->timefinish = $fromform->$timefinishfield;
            $sessiondates[] = $date;
        }
    }

    $todb = new stdClass();
    $todb->facetoface = $facetoface->id;
    // AK-LS: set valore di default a 1
    $todb->datetimeknown = (is_null($fromform->datetimeknown)) ? '1' : $fromform->datetimeknown;
    $todb->capacity = $fromform->capacity;
    $todb->allowoverbook = $fromform->allowoverbook;
    $todb->duration = $fromform->duration;
    $todb->normalcost = $fromform->normalcost;
    $todb->discountcost = $fromform->discountcost;
    //$todb->details = trim($fromform->details['text']);
    $todb->details = trim($fromform->details_editor['text']); // 2019 05 03

    $sessionid = null;
    $transaction = $DB->start_delegated_transaction();

    $update = false;
    if (!$c and $session != null) {
        $update = true;
        $sessionid = $session->id; 
        $todb->id = $session->id;
        if (!facetoface_update_session($todb, $sessiondates)) {
            $transaction->force_transaction_rollback();
            add_to_log($course->id, 'facetoface', 'update session (FAILED)', "sessions.php?s=$session->id", $facetoface->id, $cm->id);
            print_error('error:couldnotupdatesession', 'facetoface', $returnurl);
        }

        // Remove old site-wide calendar entry
        if (!facetoface_remove_session_from_site_calendar($session)) {
            $transaction->force_transaction_rollback();
            print_error('error:couldnotupdatecalendar', 'facetoface', $returnurl);
        }

/* 2017 11 23 cambio inserimento docenti        
        if( isset($fromform->list_docenti) ) {
            //AK-LM: domande sulla docenza per il QdG discenti; 
            //       deve essere eseguito prima dell'aggiornamento docenti per sessione!
            f2_feedback_set_items_for_teachers($sessionid, $feedbackid, $fromform->list_docenti);
            if (!facetoface_update_session_docenti($sessionid, $fromform->list_docenti)) {
                $transaction->force_transaction_rollback();
                print_error('error:couldnotupdatesessionteacher', 'facetoface', $returnurl);
            }
        }
*/
    } else {
        if (!$sessionid = facetoface_add_session($todb, $sessiondates)) {
            $transaction->force_transaction_rollback();
            add_to_log($course->id, 'facetoface', 'add session (FAILED)', 'sessions.php?f='.$facetoface->id, $facetoface->id, $cm->id);
            print_error('error:couldnotaddsession', 'facetoface', $returnurl);
        }
        
/* 2017 11 23 cambio inserimento docenti
        //AK-LM: domande sulla docenza per il QdG discenti
        f2_feedback_set_items_for_teachers($sessionid, $feedbackid, $fromform->list_docenti);
        if (!facetoface_add_session_docenti($sessionid, $fromform->list_docenti)) {
            $transaction->force_transaction_rollback();
            add_to_log($course->id, 'facetoface', 'add session docenti (FAILED)', 'sessions.php?f='.$facetoface->id, $facetoface->id, $cm->id);
            print_error('error:couldnotaddsessionteacher', 'facetoface', $returnurl);
        }
*/
    }

    foreach ($customfields as $field) {
        $fieldname = "custom_$field->shortname";
        if (!isset($fromform->$fieldname)) {
            $fromform->$fieldname = ''; // need to be able to clear fields
        } 
        if (!facetoface_save_customfield_value($field->id, $fromform->$fieldname, $sessionid, 'session')) {
            $transaction->force_transaction_rollback();
            print_error('error:couldnotsavecustomfield', 'facetoface', $returnurl);
        }
    }

    // Save trainer roles
    if (isset($fromform->trainerrole)) {
        facetoface_update_trainers($sessionid, $fromform->trainerrole);
    }

    // Retrieve record that was just inserted/updated
    if (!$session = facetoface_get_session($sessionid)) {
        $transaction->force_transaction_rollback();
        print_error('error:couldnotfindsession', 'facetoface', $returnurl);
    }

    // Put the session in the site-wide calendar (needs customfields to be up to date)
    if (!facetoface_add_session_to_site_calendar($session, $facetoface)) {
        $transaction->force_transaction_rollback();
        print_error('error:couldnotupdatecalendar', 'facetoface', $returnurl);
    }

    //AK-LM: richiesta del 27/5/2014 - l'inserimento di un'edizione di corso obiettivo, 
    //       che si posiziona temporalmente a cavallo tra due edizioni precedentemente create, 
    //       deve di conseguenza riordinare il n. di edizione di svolgimento.
    if (!$update && $isOBB) {
        if( !f2_reorder_sessions_editionnum_by_timestart($facetoface->id) ) {
            $transaction->force_transaction_rollback();
            print_error('error:f2couldnotreordereditionnum', 'facetoface', $returnurl);
        }
    }
    
    if ($update) {
        add_to_log($course->id, 'facetoface', 'updated session', "sessions.php?s=$session->id", $facetoface->id, $cm->id);
    }
    else {
        add_to_log($course->id, 'facetoface', 'added session', 'facetoface', 'sessions.php?f='.$facetoface->id, $facetoface->id, $cm->id);
    }

    $transaction->allow_commit();
    // 2017 11 30
    if ($session->id && $session->id > 0) {
        $returnurl = "sessions.php?s=$session->id";
    } else {
        $returnurl = "view.php?f=$facetoface->id";
    }
    // 2017 11 30
    redirect($returnurl);
} elseif ($session != null) { // Edit mode
    // Set values for the form
    $toform = new stdClass();
    $toform->datetimeknown = (1 == $session->datetimeknown);
    $toform->capacity = $session->capacity;
    $toform->allowoverbook = $session->allowoverbook;
    $toform->duration = $session->duration;
    $toform->normalcost = $session->normalcost;
    $toform->discountcost = $session->discountcost;
    //$toform->details = $session->details;
    $toform->details_editor['text'] = $session->details;
    
    if ($session->sessiondates) {
        $i = 0;
        foreach ($session->sessiondates as $date) {
            $idfield = "sessiondateid[$i]";
            $timestartfield = "timestart[$i]";
            $timefinishfield = "timefinish[$i]";
            $toform->$idfield = $date->id;
            $toform->$timestartfield = $date->timestart;
            $toform->$timefinishfield = $date->timefinish;
            $i++;
        }
    }

    foreach ($customfields as $field) {
        $fieldname = "custom_$field->shortname";
	$toform->$fieldname = facetoface_get_customfield_value($field, $session->id, 'session');
        if ($fieldname == 'custom_sirpdata') {
            if (empty($toform->custom_sirpdata)) {
                $toform->disable_sirpdata = true;
            }
        }
    }    		

    $mform->set_data($toform);
}

/*
 * AK-LS: Titolo dell'edizione che cambia a seconda del tipo di corso
 */
if ($isPRO) {
    if ($c) {
        $heading = get_string('facetofaceprg:copyingsession', 'local_f2_traduzioni', $facetoface->name);
    } else if ($d) {
	$heading = get_string('facetofaceprg:deletingsession', 'local_f2_traduzioni', $facetoface->name);
    } else if ($id or $f) {
        $heading = get_string('facetofaceprg:addingsession', 'local_f2_traduzioni', $facetoface->name);
    } else {
        $heading = get_string('facetofaceprg:editingsession', 'local_f2_traduzioni', $facetoface->name);
    }
} else {
    if ($c) {
        $heading = get_string('facetofaceobb:copyingsession', 'local_f2_traduzioni');
    } else if ($d) {
        $heading = get_string('facetofaceobb:deletingsession', 'local_f2_traduzioni');
    } else if ($id or $f) {
        $heading = get_string('facetofaceobb:addingsession', 'local_f2_traduzioni');
    } else {
        $heading = get_string('facetofaceobb:editingsession', 'local_f2_traduzioni');
    }
}

if ($isPRO && $session != null) {
    $edizione_svolgimento = $DB->get_record_sql("SELECT data as num_ediz FROM {$CFG->prefix}facetoface_session_field f
    JOIN {$CFG->prefix}facetoface_session_data d on d.fieldid = f.id
    JOIN {$CFG->prefix}facetoface_sessions s on s.id = d.sessionid
    WHERE f.shortname = 'editionum' AND s.id = $session->id");

    $pagetitle = format_string(format_string($facetoface->name).' - Edizione '.$edizione_svolgimento->num_ediz);
} else {
    $pagetitle = '';
}

$PAGE->set_cm($cm);
$PAGE->set_url('/mod/facetoface/sessions.php', array('f' => $f));
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);
// AK-LS import librerie multiselect
$PAGE->requires->css('/f2_lib/jquery/multiselect/css/common.css');
$PAGE->requires->css('/f2_lib/jquery/multiselect/css/jquery-ui.css');
$PAGE->requires->css('/f2_lib/jquery/multiselect/css/ui.multiselect.css');
$PAGE->requires->js('/f2_lib/jquery/multiselect/jquery-1.8.2.js');
$PAGE->requires->js('/f2_lib/jquery/multiselect/jquery-ui-1.8.23.custom.min.js');
$PAGE->requires->js('/f2_lib/jquery/multiselect/plugins/localisation/jquery.localisation-min.js');
$PAGE->requires->js('/f2_lib/jquery/multiselect/plugins/scrollTo/jquery.scrollTo-min.js');
$PAGE->requires->js('/f2_lib/jquery/multiselect/ui.multiselect.js');
$PAGE->requires->js('/mod/facetoface/js/managedocenti.js');
// ---- ##

echo $OUTPUT->header();
if($session) {
    $tab_edit = new extends_f2_session($session->id);
    $tab_edit->print_tab_edit_session(get_string('facetofaceobb:editingsession', 'local_f2_traduzioni'));
}
echo $OUTPUT->box_start();
echo $OUTPUT->heading($heading);

echo '<h4><a href="view.php?id='.$cm->id.'">Torna alla gestione delle edizioni</a></h4>';

if (!empty($errorstr)) {
    echo $OUTPUT->container(html_writer::tag('span', $errorstr, array('class' => 'errorstring')), array('class' => 'notifyproblem'));
}

if ($d) {
    $viewattendees = has_capability('mod/facetoface:viewattendees', $context);
    facetoface_print_session($session, $viewattendees);
    $optionsyes = array('sesskey' => sesskey(), 's' => $session->id, 'd' => 1, 'confirm' => 1);
    echo $OUTPUT->confirm(get_string('deletesessionconfirm', 'facetoface', format_string($facetoface->name)),
        new moodle_url('sessions.php', $optionsyes),
        new moodle_url($returnurl));
} else {
    $mform->display();
}

/*
 * AK-LS
 * 
 * Script utilizzato solo per i Corsi Programmati, in fase di edit delle date dell'edizione.
 * Controllo non vincolante che informa dell'eventualità che le date dell'edizione sforino
 * quelle della sessione in cui l'edizione stessa ricade.
 */
if ($session != null && $isPRO) { // Edit mode 
	
	// Date della sessione
	$sessionDates = $DB->get_record_sql("
			SELECT
				data_inizio,
				data_fine
			FROM
				{facetoface} f,
				{facetoface_sessions} fs,
				{f2_sessioni} f2_s
			WHERE
				fs.id = $session->id
				AND fs.facetoface = f.id
				AND f.f2session = f2_s.id");
	
?>
<script type="text/javascript">
function validateDates() {
	var nDates = document.getElementsByName('date_repeats')[0].value;
	var dateStartEdition = 2147483647;
	var dateFinishEdition = 0;
	
	for (var i=0; i <nDates; i++) {
		tmpStartEdition = new Date(
	    		document.getElementById('id_timestart_'+i+'_year').value, 
	    		document.getElementById('id_timestart_'+i+'_month').value-1, 
	    	    document.getElementById('id_timestart_'+i+'_day').value, 
	    	    document.getElementById('id_timestart_'+i+'_hour').value, 
	    	    document.getElementById('id_timestart_'+i+'_minute').value, 0, 0).getTime()/1000;
	    if (tmpStartEdition < dateStartEdition)
			dateStartEdition = tmpStartEdition;

	        tmpFinishEdition = new Date(
	    			document.getElementById('id_timefinish_'+i+'_year').value, 
	    	        document.getElementById('id_timefinish_'+i+'_month').value-1, 
	    	        document.getElementById('id_timefinish_'+i+'_day').value, 
	    	       	document.getElementById('id_timefinish_'+i+'_hour').value, 
	    	        document.getElementById('id_timefinish_'+i+'_minute').value, 0, 0).getTime()/1000;
	      	if (tmpFinishEdition > dateFinishEdition)
		    	dateFinishEdition = tmpFinishEdition;
	}

	var sessionStart = <?php echo $sessionDates->data_inizio ?>;
	var sessionFinish = <?php echo $sessionDates->data_fine ?>;
	
	var checkDates = false;
	if (dateStartEdition < sessionStart || dateFinishEdition > sessionFinish)
		checkDates = true;

	if (checkDates) {
		msg = "Attenzione! Il periodo di svolgimento dell'edizione ricade al di fuori della sessione. Proseguire?";
		if (confirm(msg) == false)
			return false;
	}
	
	return true;
}
</script>
<?php
}

echo '<h4><a href="view.php?id='.$cm->id.'">Torna alla gestione delle edizioni</a></h4>';
echo $OUTPUT->box_end();
echo $OUTPUT->footer($course);
