<?php
// $Id: view.php 1229 2013-12-06 14:35:38Z l.moretto $

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
 * Display profile for a particular user
 *
 * @copyright 1999 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package user
 */

require_once("../../../config.php");
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/tag/lib.php');
require_once($CFG->libdir . '/filelib.php');
require_once('assignments_form.php');
require_once($CFG->dirroot.'/f2_lib/management.php');
require_once('lib.php');
require_once('./extend_user.php');

$userid          = required_param('userid', PARAM_INT);   // user id
$courseid        = optional_param('course', SITEID, PARAM_INT);   // course id (defaults to Site)
$organisation_id = optional_param('organisationid', 0, PARAM_INT); // organisation id
$viewable_organisation_id = optional_param('viewableorganisationid', 0, PARAM_INT); // viewable organisation id

//$sitecontext = get_context_instance(CONTEXT_SYSTEM);
$sitecontext = context_system::instance();
$PAGE->set_context($sitecontext);
$PAGE->set_pagelayout('standard');
$site = get_site();
$PAGE->set_url('/local/f2_domains/assignments/view.php', array('userid'=>$userid));

if (!has_capability('local/f2_domains:assignorganisation', $sitecontext)) {
    print_error('nopermissions', 'error', '', 'assign organisation to users');
}
    
$user = $DB->get_record('user', array('id'=>$userid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
$currentuser = ($user->id == $USER->id);


//$coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
$coursecontext = context_course::instance($course->id);
//$usercontext   = get_context_instance(CONTEXT_USER, $user->id, IGNORE_MISSING);
$usercontext = context_user::instance($user->id);

$usersurl = new moodle_url('/admin/user.php');

$PAGE->set_context($coursecontext);
$PAGE->set_course($course);
$PAGE->set_pagetype('course-view-' . $course->format);  // To get the blocks exactly like the course
$PAGE->add_body_class('path-user');                     // So we can style it independently
$PAGE->set_other_editing_capability('moodle/course:manageactivities');
$PAGE->settingsnav;
$PAGE->navbar->add('Utenti', $usersurl);

$isparent = false;

if (!$currentuser and !$user->deleted
  and $DB->record_exists('role_assignments', array('userid'=>$USER->id, 'contextid'=>$usercontext->id))
  and has_capability('moodle/user:viewdetails', $usercontext)) {
    // TODO: very ugly hack - do not force "parents" to enrol into course their child is enrolled in,
    //       this way they may access the profile where they get overview of grades and child activity in course,
    //       please note this is just a guess!
    require_login();
    $isparent = true;
    $PAGE->navigation->set_userid_for_parent_checks($userid);
} else {
    // normal course
    require_login($course);
    // what to do with users temporary accessing this course? should they see the details?
}

$strpersonalprofile = get_string('personalprofile');
$strparticipants = get_string("participants");
$struser = get_string("user");

$fullname = fullname($user, has_capability('moodle/site:viewfullnames', $coursecontext));

/// Now test the actual capabilities and enrolment in course
if ($currentuser) {
    // me
    if (!is_viewing($coursecontext) && !is_enrolled($coursecontext)) { // Need to have full access to a course to see the rest of own info
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('notenrolled', '', $fullname));
        if (!empty($_SERVER['HTTP_REFERER'])) {
            echo $OUTPUT->continue_button($_SERVER['HTTP_REFERER']);
        }
        echo $OUTPUT->footer();
        die;
    }

} else {
    // somebody else
    $PAGE->set_title("$strpersonalprofile: ");
    $PAGE->set_heading("$strpersonalprofile: ");

    // check course level capabilities
    if (!has_capability('moodle/user:viewdetails', $coursecontext) && // normal enrolled user or mnager
        ($user->deleted or !has_capability('moodle/user:viewdetails', $usercontext))) {   // usually parent
        print_error('cannotviewprofile');
    }

    if (!is_enrolled($coursecontext, $user->id)) {
        // TODO: the only potential problem is that managers and inspectors might post in forum, but the link
        //       to profile would not work - maybe a new capability - moodle/user:freely_acessile_profile_for_anybody
        //       or test for course:inspect capability
        if (has_capability('moodle/role:assign', $coursecontext)) {
            $PAGE->navbar->add($fullname);
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('notenrolled', '', $fullname));
        } else {
            echo $OUTPUT->header();
            $PAGE->navbar->add($struser);
            echo $OUTPUT->heading(get_string('notenrolledprofile'));
        }
        if (!empty($_SERVER['HTTP_REFERER'])) {
            echo $OUTPUT->continue_button($_SERVER['HTTP_REFERER']);
        }
        echo $OUTPUT->footer();
        exit;
    }

    // If groups are in use and enforced throughout the course, then make sure we can meet in at least one course level group
    if (groups_get_course_groupmode($course) == SEPARATEGROUPS and $course->groupmodeforce
      and !has_capability('moodle/site:accessallgroups', $coursecontext) and !has_capability('moodle/site:accessallgroups', $coursecontext, $user->id)) {
        if (!isloggedin() or isguestuser()) {
            // do not use require_login() here because we might have already used require_login($course)
            redirect(get_login_url());
        }
        $mygroups = array_keys(groups_get_all_groups($course->id, $USER->id, $course->defaultgroupingid, 'g.id, g.name'));
        $usergroups = array_keys(groups_get_all_groups($course->id, $user->id, $course->defaultgroupingid, 'g.id, g.name'));
        if (!array_intersect($mygroups, $usergroups)) {
            print_error("groupnotamember", '', "../course/view.php?id=$course->id");
        }
    }
}

/// We've established they can see the user's name at least, so what about the rest?

if (!$currentuser) {
    $PAGE->navigation->extend_for_user($user);
    if ($node = $PAGE->settingsnav->get('userviewingsettings'.$user->id)) {
        $node->forceopen = true;
    }
} else if ($node = $PAGE->settingsnav->get('usercurrentsettings', navigation_node::TYPE_CONTAINER)) {
    $node->forceopen = true;
}
if ($node = $PAGE->settingsnav->get('courseadmin')) {
    $node->forceopen = false;
}

$PAGE->set_title("$course->fullname: $strpersonalprofile: $fullname");
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('standard');

// inizio import per generazione albero //
    $PAGE->requires->js('/f2_lib/jquery/jquery-1.7.1.min.js');
    $PAGE->requires->js('/f2_lib/jquery/jquery-ui.min.js');
    $PAGE->requires->js('/f2_lib/jquery/jquery.cookie.js');
    $PAGE->requires->js('/f2_lib/jquery/jquery.dynatree.js');
    $PAGE->requires->css('/f2_lib/jquery/css/skin/ui.dynatree.css');
    $PAGE->requires->js('/f2_lib/jquery/jquery.blockUI.js');
    $PAGE->requires->js('/local/f2_domains/js/module.js');
    $jsmodule = array(
		'name'  =>  'f2_domains',
		'fullpath'  =>  '/local/f2_domains/js/module.js',
		'requires'  =>  array('base', 'attribute', 'node', 'node-event-simulate', 'event-key')
    );
    $jsdata = array(sesskey());
    $PAGE->requires->js_init_call('M.f2_domains.init', $jsdata, true, $jsmodule);
// fine import per generazione albero //


//------------------------$PAGE->navbar->add($fullname);
echo $OUTPUT->header();

echo '<div class="userprofile">';

echo $OUTPUT->heading(fullname($user).' ('.format_string($course->shortname, true, array('context' => $coursecontext)).')');

if ($user->deleted) {
    echo $OUTPUT->heading(get_string('userdeleted'));
    if (!has_capability('moodle/user:update', $coursecontext)) {
        echo $OUTPUT->footer();
        die;
    }
}

/// OK, security out the way, now we are showing the user

add_to_log($course->id, "user", "view", "view.php?id=$user->id&course=$course->id", "$user->id");

/// Get the hidden field list
if (has_capability('moodle/user:viewhiddendetails', $coursecontext)) {
    $hiddenfields = array();
} else {
    $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
}

//AK-LM START: tabs per anagrafica e domini
$f2ext = new extend_user($user->id);
$f2ext->print_tab_edit_user('dom_tab');
//AK-LM END: tabs per anagrafica e domini

if (is_mnet_remote_user($user)) {
    $sql = "SELECT h.id, h.name, h.wwwroot,
                   a.name as application, a.display_name
              FROM {mnet_host} h, {mnet_application} a
             WHERE h.id = ? AND h.applicationid = a.id";

    $remotehost = $DB->get_record_sql($sql, array($user->mnethostid));
    $a = new stdclass();
    $a->remotetype = $remotehost->display_name;
    $a->remotename = $remotehost->name;
    $a->remoteurl  = $remotehost->wwwroot;

    echo $OUTPUT->box(get_string('remoteuserinfo', 'mnet', $a), 'remoteuserinfo');
}

echo '<div class="userprofilebox clearfix"><div class="profilepicture">';
echo $OUTPUT->user_picture($user, array('size'=>100));
echo '</div>';

// Print the description
echo '<div class="descriptionbox"><div class="description">';
if ($user->description && !isset($hiddenfields['description'])) {
    if (!empty($CFG->profilesforenrolledusersonly) && !$DB->record_exists('role_assignments', array('userid'=>$userid))) {
        echo get_string('profilenotshown', 'moodle');
    } else {
        if ($courseid == SITEID) {
            $user->description = file_rewrite_pluginfile_urls($user->description, 'pluginfile.php', $usercontext->id, 'user', 'profile', null);
        } else {
            // we have to make a little detour thought the course context to verify the access control for course profile
            $user->description = file_rewrite_pluginfile_urls($user->description, 'pluginfile.php', $coursecontext->id, 'user', 'profile', $user->id);
        }
        $options = array('overflowdiv'=>true);
        echo format_text($user->description, $user->descriptionformat, $options);
    }
}
echo '</div>';


// Print all the little details in a list

echo '<table class="list" summary="">';

if (! isset($hiddenfields['country']) && $user->country) {
    print_row(get_string('country') . ':', get_string($user->country, 'countries'));
}

if (! isset($hiddenfields['city']) && $user->city) {
    print_row(get_string('city') . ':', $user->city);
}

//checks were performed above that ensure that if we've got to here either the user
//is viewing their own profile ($USER->id == $user->id) or $user is enrolled in the course
if ($currentuser
   or $user->maildisplay == 1 //allow everyone to see email address
   or ($user->maildisplay == 2 && is_enrolled($coursecontext, $USER)) //fellow course members can see email. Already know $user is enrolled
   or has_capability('moodle/course:useremail', $coursecontext)) {
    print_row(get_string("email").":", obfuscate_mailto($user->email, ''));
}

// Show last time this user accessed this course
if (!isset($hiddenfields['lastaccess'])) {
    if ($lastaccess = $DB->get_record('user_lastaccess', array('userid'=>$user->id, 'courseid'=>$course->id))) {
        $datestring = userdate($lastaccess->timeaccess)."&nbsp; (".format_time(time() - $lastaccess->timeaccess).")";
    } else {
        $datestring = get_string("never");
    }
    print_row(get_string("lastaccess").":", $datestring);
}

if (!isset($hiddenfields['suspended'])) {
    if ($user->suspended) {
        print_row('', get_string('suspended', 'auth'));
    }
}


echo "</table></div></div>";

// Print messaging link if allowed
if (isloggedin() && has_capability('moodle/site:sendmessage', $usercontext)
    && !empty($CFG->messaging) && !isguestuser() && !isguestuser($user) && ($USER->id != $user->id)) {
    echo '<div class="messagebox"><center>';
    echo '<a href="'.$CFG->wwwroot.'/message/index.php?id='.$user->id.'">'.get_string('messageselectadd').'</a>';
    echo '</center></div>';
}

////////////////////////////////////////////////////////////////////////////////
////////////////////// ASSEGNAZIONE ORGANIZZAZIONE INIZIO //////////////////////
////////////////////////////////////////////////////////////////////////////////

$assignment = $DB->get_record('org_assignment', array('userid' => $userid));
if (empty($assignment)) {
    $assignment->userid = $userid;
}
$form = new user_organisation_assignment_form(null, (array)($assignment));

if ($form->is_cancelled()){
    redirect($CFG->wwwroot.'/admin/user.php');
}
else if ($form->is_submitted()) {
    if ($organisation_id) {

//var_dump($viewable_organisation_id);
        $assignmentnew = new stdClass();
        $assignmentnew->userid = $userid;
        $assignmentnew->organisationid = $organisation_id;
        $assignmentnew->viewableorganisationid = $viewable_organisation_id == 0 ? NULL : $viewable_organisation_id;
        $assignmentnew->timemodified = time();
        $assignmentnew->usermodified = $USER->id;
        set_user_domains($assignmentnew);
        redirect($PAGE->url);
    }
    else { // Print error message if no organisation chosen
        echo $OUTPUT->box_start('errorbox errorboxcontent boxaligncenter boxwidthnormal');
        echo $form->validation();
        echo $OUTPUT->box_end();
    }
}

$form->display();

////////////////////////////////////////////////////////////////////////////////
////////////////////// ASSEGNAZIONE ORGANIZZAZIONE FINE //////////////////////
////////////////////////////////////////////////////////////////////////////////

echo '</div>';  // userprofile class

echo $OUTPUT->footer();

/// Functions ///////

function print_row($left, $right) {
    echo "\n<tr><th class=\"label c0\">$left</th><td class=\"info c1\">$right</td></tr>\n";
}


