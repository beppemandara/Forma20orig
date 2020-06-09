<?php
require_once '../../config.php';
require_once 'lib.php';

global $DB;

$s = required_param('s', PARAM_INT); // facetoface session ID
$backtoallsessions = optional_param('backtoallsessions', 0, PARAM_INT);

if (!$session = facetoface_get_session($s)) {
    print_error('error:incorrectcoursemodulesession', 'facetoface');
}
if (!$facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface))) {
    print_error('error:incorrectfacetofaceid', 'facetoface');
}
if (!$course = $DB->get_record('course', array('id' => $facetoface->course))) {
    print_error('error:coursemisconfigured', 'facetoface');
}
if (!$cm = get_coursemodule_from_instance("facetoface", $facetoface->id, $course->id)) {
    print_error('error:incorrectcoursemoduleid', 'facetoface');
}

$context = context_course::instance($course->id);
require_capability('mod/facetoface:view', $context);

$returnurl = "$CFG->wwwroot/course/view.php?id=$course->id";
if ($backtoallsessions) {
    $returnurl = "$CFG->wwwroot/mod/facetoface/view.php?f=$backtoallsessions";
}

$pagetitle = format_string($facetoface->name);

$PAGE->set_cm($cm);
$PAGE->set_url('/mod/facetoface/signup.php', array('s' => $s, 'backtoallsessions' => $backtoallsessions));

$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

$heading = get_string('signupfor', 'facetoface', $facetoface->name);

echo $OUTPUT->box_start();
echo $OUTPUT->heading($heading);

//print_r($session);
//print_r($facetoface);
print_r($facetoface->course);
echo '<br />';
//print_r($course);
print_r($course->fullname);

echo $OUTPUT->box_end();
echo $OUTPUT->footer($course);
