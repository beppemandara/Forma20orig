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
 * List and modify users that are not enrolled but still have a role in course.
 *
 * @package    core_enrol
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../config.php');
require_once("$CFG->dirroot/enrol/locallib.php");
require_once("$CFG->dirroot/enrol/renderer.php");
require_once("$CFG->dirroot/group/lib.php");
// 20171002
require_once("$CFG->dirroot/f2_lib/management.php");
require_once("$CFG->dirroot/local/f2_support/lib.php");
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/local/f2_domains/assignments/filters/lib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/tag/lib.php');
require_once($CFG->libdir . '/filelib.php');

$ref_dir_prop = optional_param('ref_dir_prop', false, PARAM_INT);
$ref_dir_consiglio = optional_param('ref_dir_consiglio', false, PARAM_INT);
$ref_dir_giunta = optional_param('ref_dir_giunta', false, PARAM_INT);
$ref_dir_enti = optional_param('ref_dir_enti', false, PARAM_INT);
$all_ref = optional_param('all_ref', false, PARAM_INT);

global $PAGE,$OUTPUT,$DB;
// 20171002

$id      = required_param('id', PARAM_INT); // course id
$action  = optional_param('action', '', PARAM_ALPHANUMEXT);
$filter  = optional_param('ifilter', 0, PARAM_INT);
// 20171002
$param_assign = optional_param('r_a', 0, PARAM_INT);
$param_clear  = optional_param('r_c', 0, PARAM_INT);

// inizio import per generazione albero //
$PAGE->requires->js('/f2_lib/jquery/jquery-1.7.1.min.js');
$PAGE->requires->js('/f2_lib/jquery/jquery-ui.min.js');
$PAGE->requires->js('/f2_lib/jquery/jquery.cookie.js');
$PAGE->requires->js('/f2_lib/jquery/jquery.dynatree.js');
$PAGE->requires->js('/f2_lib/jquery/jquery.blockUI.js');
$PAGE->requires->css('/f2_lib/jquery/css/skin/ui.dynatree.css');
// fine import per generazione albero //

$sort         = optional_param('sort', 'name', PARAM_ALPHANUM);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 30, PARAM_INT);        // how many per page
$suspend      = optional_param('suspend', 0, PARAM_INT);
$unsuspend    = optional_param('unsuspend', 0, PARAM_INT);
// 20171002

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);
require_capability('moodle/course:reviewotherusers', $context);

if ($course->id == SITEID) {
    redirect("$CFG->wwwroot/");
}

$PAGE->set_pagelayout('admin');

$manager = new course_enrolment_manager($PAGE, $course, $filter);
$table = new course_enrolment_other_users_table($manager, $PAGE);
//$PAGE->set_url('/enrol/otherusers.php', $manager->get_url_params()+$table->get_url_params());
$baseurl = new moodle_url('/enrol/otherusers.php', $manager->get_url_params()+$table->get_url_params()+array('id'=>$id));
$PAGE->set_url($baseurl);
navigation_node::override_active_url(new moodle_url('/enrol/otherusers.php', array('id' => $id)));

$userdetails = array (
    'picture' => false,
    'firstname' => get_string('firstname'),
    'lastname' => get_string('lastname'),
);
$extrafields = get_extra_user_fields($context);
foreach ($extrafields as $field) {
    $userdetails[$field] = get_user_field_name($field);
}

$fields = array(
    'userdetails' => $userdetails,
    //'lastseen' => get_string('lastaccess'),
    'org_name' => get_string('viewable_organisation','local_f2_domains'),
    'role' => get_string('roles', 'role')
);

/*
// Remove hidden fields if the user has no access
if (!has_capability('moodle/course:viewhiddenuserfields', $context)) {
    $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
    if (isset($hiddenfields['lastaccess'])) {
        unset($fields['lastseen']);
    }
}
*/

// 20171002
// create the user filter form 
$baseurl = new moodle_url('/enrol/otherusers.php', $manager->get_url_params()+$table->get_url_params()+array('id'=>$id)+array('page'=>'0'));
$ufiltering = new my_users_filtering(array('lastname'=> (int) !true, 'viewableorganisationid'=> (int) true),$baseurl."?page=0",null,true,true);
//var_dump($ufiltering->get_sql_filter());
list($extrasql, $params) = $ufiltering->get_sql_filter();
$extrafilter = array('extrasql'=>$extrasql,'params'=>$params);
// 20171002

$table->set_fields($fields, $OUTPUT);

//$users = $manager->get_other_users($table->sort, $table->sortdirection, $table->page, $table->perpage);

$renderer = $PAGE->get_renderer('core_enrol');
$canassign = has_capability('moodle/role:assign', $manager->get_context());
// 20171002
$updateurl = new moodle_url('/enrol/otherusers.php', $manager->get_url_params()+$table->get_url_params());
$use_as_filter = false;
$users = $manager->get_other_users_for_display($renderer, $PAGE->url, $table->sort, $table->sortdirection, $table->page, $table->perpage,$extrafilter);
// 20171002
//$users = $manager->get_other_users_for_display($renderer, $PAGE->url, $table->sort, $table->sortdirection, $table->page, $table->perpage);
$assignableroles = $manager->get_assignable_roles(true);
foreach ($users as $userid=>&$user) {
    $user['org_name']=$user['picture']->user->org_name;
    $user['picture'] = $OUTPUT->render($user['picture']);
    $user['role'] = $renderer->user_roles_and_actions($userid, $user['roles'], $assignableroles, $canassign, $PAGE->url);
}

//$table->set_total_users($manager->get_total_other_users());
$total_other_users = $manager->get_total_other_users($extrafilter);

$table->set_total_users($total_other_users);

$table->set_users($users);

//$PAGE->set_title($course->fullname.': '.get_string('totalotherusers', 'enrol', $manager->get_total_other_users()));
$PAGE->set_title($course->fullname.': '.get_string('totalotherusers', 'enrol', $total_other_users));
$PAGE->set_heading($PAGE->title);

echo $OUTPUT->header();
// 20171002
echo $OUTPUT->heading(get_string('title_div_verifica_ass','local_f2_course'));
if($param_assign == 1){
	echo '<p style="text-align:center">';
	echo '<b style="color:green">'.get_string('result_assign_true').'</b>';
	echo '</p>';
}
if($param_clear == 1){
	echo '<p style="text-align:center">';
	echo '<b style="color:green">'.get_string('result_clear_true').'</b>';
	echo '</p>';
}

// add filters
$ufiltering->display_add();
$ufiltering->display_active();
echo 'Totale utenti: '.$total_other_users;
// 20171002
echo $renderer->render($table);
echo $OUTPUT->footer();
