<?php
global $DB, $THEME, $USER, $CFG, $PAGE;
 
require_once '../../config.php';
require_once 'lib.php';
require_once($CFG->dirroot.'/f2_lib/management.php');
require_once($CFG->dirroot.'/f2_lib/core.php');
require_once($CFG->dirroot.'/lib/enrollib.php');
require_once($CFG->dirroot.'/mod/feedback/lib.php');

define('MAX_TEACHERS_PER_PAGE', 3000);
//define('TEACHERS_PRENOTATI_VALIDATI', 1001);

$s                 = required_param('s', PARAM_INT); // facetoface session ID
$add               = optional_param('add', 0, PARAM_BOOL);
$remove            = optional_param('remove', 0, PARAM_BOOL);
$showall           = optional_param('showall', 0, PARAM_BOOL);
$searchtext        = optional_param('searchtext', '', PARAM_TEXT); // search string
$suppressemail     = optional_param('suppressemail', true, PARAM_BOOL); // send email notifications
$previoussearch    = optional_param('previoussearch', 0, PARAM_BOOL);
$backtoallsessions = optional_param('backtoallsessions', 0, PARAM_INT); // facetoface activity to go back to
$userenrollist 	   = optional_param('u', 0, PARAM_INT); // parametro per la selezione del tab (solo Corsi Programmati)

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

$f2course = $DB->get_record('f2_anagrafica_corsi', array('courseid' => $course->id), 'course_type,durata', MUST_EXIST);

$isPRO = (C_PRO === (int)($f2course->course_type));
$isOBB = (C_OBB === (int)($f2course->course_type));

//echo "isPRO :".$isPRO."<br />isOBB :".$isOBB;exit();
$sessioncustomfields = facetoface_get_customfielddata($s);
$ednum = $sessioncustomfields['editionum']->data;

// Check essential permissions
require_course_login($course);
$context = context_course::instance($course->id);

$capabilityscope = ($isPRO) ? "prg" : "obb";

require_capability('mod/facetoface:editsessions'.$capabilityscope, $context);
require_capability('mod/facetoface:viewattendees', $context);
require_capability('mod/facetoface:removeattendees', $context);

// Get some language strings
$strsearch        = get_string('search');
$strshowall       = get_string('showall');
$strsearchresults = get_string('searchresults');
$strfacetofaces   = get_string('modulenameplural', 'facetoface');
$strfacetoface    = get_string('modulename', 'facetoface');

$errors = array();

// Get the user_selector we will need.
$potentialteacherselector = new facetoface_potential_teachers_selector_csi('addselect', array('sessionid'=>$session->id)); 
$existingteacherselector = new facetoface_existing_teachers_selector_csi('removeselect', array('sessionid'=>$session->id));

$str = <<<'EFO'
<script type="text/javascript">
//<![CDATA[
function confirmSubmit(var_html,url) {
  if(var_html) {
    var agree = window.confirm(var_html,'Nuova_finestra',200,200);
    if (agree)
      document.location.href=url;
    else
      return false;
  } else {
    document.location.href=url;
  }
}
//]]>
</script>
EFO;

echo $str;

$returnurl = "editteachers.php?s=$s";
// Process incoming user assignments
if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
  require_capability('mod/facetoface:addattendees', $context);
  $teachertoassign = $potentialteacherselector->get_selected_users();
  if (!empty($teachertoassign)) {
    foreach ($teachertoassign as $addteacher) {
      if (!$addteacher->id = clean_param($addteacher->id, PARAM_INT)) {
        continue; // invalid userid
      }
      if (!facetoface_add_session_docenti($s, $addteacher->id)) {
        print_error('error:couldnotaddsessionteacher', 'facetoface', $returnurl);
        $errors[] = get_string('error:couldnotaddsessionteacher', 'facetoface', $addteacher->firstname.' '.$addteacher->lastname);
        continue;
      } else {
        $feedbackid = f2_feedback_get_student_feedback($course->id);
        f2_feedback_set_items_for_docente($s, $feedbackid, $addteacher->id);
      }
    }
    $potentialteacherselector->invalidate_selected_users();
    $existingteacherselector->invalidate_selected_users();
  }
}

// Process removing user assignments from session
if (optional_param('remove', false, PARAM_BOOL) && confirm_sesskey()) {
  require_capability('mod/facetoface:removeattendees', $context);
  $teachertoremove = $existingteacherselector->get_selected_users();
  if (!empty($teachertoremove)) {
    foreach ($teachertoremove as $removeteacher) {
      if (!$removeteacher->id = clean_param($removeteacher->id, PARAM_INT)) {
        continue; // invalid userid
      }
      //if (!$DB->delete_records('facetoface_sessions_docenti', array('sessionid' => $s, 'userid' => $removeteacher->id))) {
      $feedbackid = f2_feedback_get_student_feedback($course->id);
      if (!$ris = f2_feedback_del_items_for_docente($s, $feedbackid, $removeteacher->id)) {
        print_error('error:couldnotupdatesessionteacher', 'facetoface', $returnurl);
        $errors[] = get_string('error:couldnotupdatesessionteacher', 'facetoface', $removeteacher->firstname.' '.$removeteacher->lastname);
      } else {
        //$feedbackid = f2_feedback_get_student_feedback($course->id);
        //f2_feedback_del_items_for_docente($s, $feedbackid, $removeteacher->id);
        if (!$DB->delete_records('facetoface_sessions_docenti', array('sessionid' => $s, 'userid' => $removeteacher->id))) {
          print_error('error:couldnotupdatesessionteacher', 'facetoface', $returnurl);
          $errors[] = get_string('error:couldnotupdatesessionteacher', 'facetoface', $removeteacher->firstname.' '.$removeteacher->lastname);
        }
      }
    }
    $potentialteacherselector->invalidate_selected_users();
    $existingteacherselector->invalidate_selected_users();
  }
}

// Main page
$pagetitle = 'Inserimento Docenti '.format_string($facetoface->name);
$PAGE->set_cm($cm);
$PAGE->set_url('/mod/facetoface/editteachers.php', array('s' => $s));
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

//echo "<p><a href=\"{$CFG->wwwroot}/mod/facetoface/sessions.php?s={$s}\" class=\"back_arianna\">Torna all'edizione</a></p>";
echo '<h4><a href="view.php?id='.$cm->id.'">Torna alla gestione delle edizioni</a></h4>';
echo '<br />';
echo "<h4><a href=\"{$CFG->wwwroot}/mod/facetoface/sessions.php?s={$s}\" class=\"back_arianna\">Torna all'edizione</a></h4>";

echo $OUTPUT->box_start();

$pageheader = "$course->idnumber - ".format_string($course->fullname)." - Edizione del ".
              date('d/m/Y', $session->sessiondates[0]->timestart);
              //"<span style=\"font-size:12px;display: block;\">Gestisci Formatori</span>";
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

// create formatori_selector form
$out = html_writer::start_tag('form', array('id' => 'assignform', 'method' => 'post', 'action' => $PAGE->url));
$out .= html_writer::start_tag('div');
$out .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => "previoussearch", 'value' => $previoussearch));
$out .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => "backtoallsessions", 'value' => $backtoallsessions));
$out .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => "sesskey", 'value' => sesskey()));

$table = new html_table();
$table->attributes['class'] = "generaltable generalbox boxaligncenter";
$cells = array();
//$content = html_writer::start_tag('p') . html_writer::tag('label', get_string('attendees', 'facetoface'), array('for' => 'removeselect')) . html_writer::end_tag('p');
$content = html_writer::start_tag('p') . html_writer::tag('label', 'Docenti', array('for' => 'removeselect')) . html_writer::end_tag('p');
$content .= $existingteacherselector->display(true);
$cell = new html_table_cell($content);
$cell->attributes['id'] = 'existingcell';
$cells[] = $cell;
$content = html_writer::tag('div', html_writer::empty_tag('input', array('type' => 'submit', 'id' => 'add', 'name' => 'add', 'title' => get_string('add'), 'value' => $OUTPUT->larrow().' '.get_string('add'))), array('id' => 'addcontrols'));
$content .= html_writer::tag('div', html_writer::empty_tag('input', array('type' => 'submit', 'id' => 'remove', 'name' => 'remove', 'title' => get_string('remove'), 'value' => $OUTPUT->rarrow().' '.get_string('remove'))), array('id' => 'removecontrols'));
$cell = new html_table_cell($content);
$cell->attributes['id'] = 'buttonscell';
$cells[] = $cell;
//$content = html_writer::start_tag('p') . html_writer::tag('label', get_string('potentialattendees', 'facetoface'), array('for' => 'addselect')) . html_writer::end_tag('p');
$content = html_writer::start_tag('p') . html_writer::tag('label', 'Docenti potenziali', array('for' => 'addselect')) . html_writer::end_tag('p');
$content .= $potentialteacherselector->display(true);
$cell = new html_table_cell($content);
$cell->attributes['id'] = 'potentialcell';
$cells[] = $cell;
$table->data[] = new html_table_row($cells);
$out .=  html_writer::table($table);
$out .= html_writer::end_tag('div') . html_writer::end_tag('form');
echo $out;
/*
$html = "";
// Bottom of the page links
echo html_writer::start_tag('p');
//$url = new moodle_url('/mod/facetoface/editteachers.php', array('s' => $session->id));
$url = new moodle_url('/mod/facetoface/sessions.php', array('s' => $session->id, 'backtoallsessions' => $backtoallsessions));

echo html_writer::start_tag('center');
echo html_writer::empty_tag("input", array('type' => 'button','value' => 'Conferma l\'iscrizione', 'onclick' => "return confirmSubmit('".$html."','".$url."');"));
echo html_writer::end_tag('center');
//echo html_writer::link($url, get_string('goback', 'facetoface'));
echo html_writer::end_tag('p');
*/
echo $OUTPUT->box_end();
echo "<h4><a href=\"{$CFG->wwwroot}/mod/facetoface/sessions.php?s={$s}\" class=\"back_arianna\">Torna all'edizione</a></h4>";
echo '<br />';
echo '<h4><a href="view.php?id='.$cm->id.'">Torna alla gestione delle edizioni</a></h4>';
echo $OUTPUT->footer($course);
