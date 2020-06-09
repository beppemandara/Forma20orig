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
 * Edit course settings
 *
 * @package    core_course
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../config.php');
require_once('lib.php');
require_once('edit_form.php');
// AK-GL inclusa libreria per estensione del corso
require_once('../local/f2_course/extends_course.php');
require_once('../f2_lib/core.php');

$id = optional_param('id', 0, PARAM_INT); // Course id.
$categoryid = optional_param('category', 0, PARAM_INT); // Course category - can be changed in edit form.
$returnto = optional_param('returnto', 0, PARAM_ALPHANUM); // Generic navigation return page switch.

$PAGE->set_pagelayout('admin');
if ($id) {
    $pageparams = array('id' => $id);
} else {
    $pageparams = array('category' => $categoryid);
}
$PAGE->set_url('/course/edit.php', $pageparams);

$str = <<<'EFO'
<script type="text/javascript">
//<![CDATA[
function code_number_course()
{
    var anno_formativo_in_corso = document.getElementById('anno_formativo_in_corso');
    var shortname = document.getElementById('id_shortname');
    var number_course = document.getElementById('id_idnumber');
    shortname.value = number_course.value+'_'+anno_formativo_in_corso.value;
}

//]]>
</script>
EFO;

echo $str;

$anno_formativo_in_corso = get_anno_formativo_corrente();
echo '<input type="hidden" id="anno_formativo_in_corso" value='.$anno_formativo_in_corso.'>';

// Basic access control checks.
if ($id) {
    // Editing course.
    if ($id == SITEID){
        // Don't allow editing of  'site course' using this from.
        print_error('cannoteditsiteform');
    }

    // Login to the course and retrieve also all fields defined by course format.
    $course = get_course($id);
    require_login($course);
    $course = course_get_format($course)->get_course();

    $category = $DB->get_record('course_categories', array('id'=>$course->category), '*', MUST_EXIST);
    $coursecontext = context_course::instance($course->id);
    require_capability('moodle/course:update', $coursecontext);

} else if ($categoryid) {
    // Creating new course in this category.
    $course = null;
    require_login();
    $category = $DB->get_record('course_categories', array('id'=>$categoryid), '*', MUST_EXIST);
    $catcontext = context_coursecat::instance($category->id);
    require_capability('moodle/course:create', $catcontext);
    $PAGE->set_context($catcontext);

} else {
    require_login();
    print_error('needcoursecategroyid');
}

// Prepare course and the editor.
$editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes'=>$CFG->maxbytes, 'trusttext'=>false, 'noclean'=>true);
$overviewfilesoptions = course_overviewfiles_options($course);
if (!empty($course)) {
    // Add context for editor.
    $editoroptions['context'] = $coursecontext;
    $editoroptions['subdirs'] = file_area_contains_subdirs($coursecontext, 'course', 'summary', 0);
    $course = file_prepare_standard_editor($course, 'summary', $editoroptions, $coursecontext, 'course', 'summary', 0);
    if ($overviewfilesoptions) {
        file_prepare_standard_filemanager($course, 'overviewfiles', $overviewfilesoptions, $coursecontext, 'course', 'overviewfiles', 0);
    }

    // Inject current aliases.
    $aliases = $DB->get_records('role_names', array('contextid'=>$coursecontext->id));
    foreach($aliases as $alias) {
        $course->{'role_'.$alias->roleid} = $alias->name;
    }

} else {
    // Editor should respect category context if course context is not set.
    $editoroptions['context'] = $catcontext;
    $editoroptions['subdirs'] = 0;
    $course = file_prepare_standard_editor($course, 'summary', $editoroptions, null, 'course', 'summary', null);
    if ($overviewfilesoptions) {
        file_prepare_standard_filemanager($course, 'overviewfiles', $overviewfilesoptions, null, 'course', 'overviewfiles', 0);
    }
}

// First create the form.
$editform = new course_edit_form(NULL, array('course'=>$course, 'category'=>$category, 'editoroptions'=>$editoroptions, 'returnto'=>$returnto));
if ($editform->is_cancelled()) {
        switch ($returnto) {
            case 'category':
                $url = new moodle_url($CFG->wwwroot.'/course/index.php', array('categoryid' => $categoryid));
                break;
            case 'catmanage':
                $url = new moodle_url($CFG->wwwroot.'/course/management.php', array('categoryid' => $categoryid));
                break;
            case 'topcatmanage':
                $url = new moodle_url($CFG->wwwroot.'/course/management.php');
                break;
            case 'topcat':
                $url = new moodle_url($CFG->wwwroot.'/course/');
                break;
            default:
                if (!empty($course->id)) {
                    $url = new moodle_url($CFG->wwwroot.'/course/view.php', array('id'=>$course->id));
                } else {
                    $url = new moodle_url($CFG->wwwroot.'/course/');
                }
                break;
        }
        redirect($url);

} else if ($data = $editform->get_data()) {
    // Process data if submitted.
    if (empty($course->id)) {
        // In creating the course.
        $course = create_course($data, $editoroptions);

        // Get the context of the newly created course.
        $context = context_course::instance($course->id, MUST_EXIST);

        // AKTIVE: commentato per evitare che l'utente venga iscritto come docente del corso
        //if (!empty($CFG->creatornewroleid) and !is_viewing($context, NULL, 'moodle/role:assign') and !is_enrolled($context, NULL, 'moodle/role:assign')) {
            // Deal with course creators - enrol them internally with default role.
            //enrol_try_internal_enrol($course->id, $USER->id, $CFG->creatornewroleid);
        //}
        // AKTIVE: commentato per evitare che vada sulla pagina delle iscrizioni,
        // devo redirigere verso la pagina dell'anagrafica corso
        //if (!is_enrolled($context)) {
            // Redirect to manual enrolment page if possible.
            //$instances = enrol_get_instances($course->id, true);
            //foreach($instances as $instance) {
                //if ($plugin = enrol_get_plugin($instance->enrol)) {
                    //if ($plugin->get_manual_enrol_link($instance)) {
                        // We know that the ajax enrol UI will have an option to enrol.
                        //redirect(new moodle_url('/enrol/users.php', array('id'=>$course->id)));
                    //}
                //}
            //}
        //}
    } else {
        // Save any changes to the files used in the editor.
        update_course($data, $editoroptions);
    }

    // Redirect user to newly created/updated course.
    //redirect(new moodle_url('/course/view.php', array('id' => $course->id)));
    // AKTIVE: redirigo verso la pagina dell'anagrafica corso
    $url = new moodle_url($CFG->wwwroot.'/local/f2_course/anag_course.php', array('courseid'=>$course->id));
    redirect($url);
}

// Print the form.

$site = get_site();

$streditcoursesettings = get_string("editcoursesettings");
$straddnewcourse = get_string("addnewcourse");
$stradministration = get_string("administration");
$strcategories = get_string("categories");

if (!empty($course->id)) {
    // Navigation note: The user is editing a course, the course will exist within the navigation and settings.
    // The navigation will automatically find the Edit settings page under course navigation.
    $PAGE->navbar->add($streditcoursesettings); // 2017 09 25
    $pagedesc = $streditcoursesettings;
    $title = $streditcoursesettings;
    $fullname = $course->fullname;
} else {
    // The user is adding a course, this page isn't presented in the site navigation/admin.
    // Adding a new course is part of course category management territory.
    // We'd prefer to use the management interface URL without args.
    $managementurl = new moodle_url('/course/management.php');
    // These are the caps required in order to see the management interface.
    $managementcaps = array('moodle/category:manage', 'moodle/course:create');
    if ($categoryid && !has_any_capability($managementcaps, context_system::instance())) {
        // If the user doesn't have either manage caps then they can only manage within the given category.
        $managementurl->param('categoryid', $categoryid);
    }
    // Because the course category management interfaces are buried in the admin tree and that is loaded by ajax
    // we need to manually tell the navigation we need it loaded. The second arg does this.
    navigation_node::override_active_url($managementurl, true);

    // 2017 09 25
    $PAGE->navbar->add($stradministration, new moodle_url('/admin/index.php'));
    $PAGE->navbar->add($strcategories, new moodle_url('/course/index.php'));
    $PAGE->navbar->add($straddnewcourse);
    // 2017 09 25
    $pagedesc = $straddnewcourse;
    $title = "$site->shortname: $straddnewcourse";
    $fullname = $site->fullname;
    $PAGE->navbar->add($pagedesc);
}

$PAGE->set_title($title);
$PAGE->set_heading($fullname);

// 2017 09 25
$jsmodule = array(
    'name'     => 'f2_course',
    'fullpath' => '/local/f2_course/js/module.js',
    'requires' => array('base', 'attribute', 'node', 'datasource-io', 'datasource-jsonschema', 'node-event-simulate', 'event-key')
);
$jsdata = array( sesskey());
$PAGE->requires->js_init_call('M.f2_course.init',$jsdata,true,$jsmodule);
// 2017 09 25

echo $OUTPUT->header();

// 2017 09 25
if(isset($course->id)) { // aggiunta il 2017 09 28
if ($DB->record_exists('f2_anagrafica_corsi', array('courseid'=>$course->id))) {
    $anag_course = $DB->get_record('f2_anagrafica_corsi', array('courseid'=>$course->id), '*', MUST_EXIST);
    if ($anag_course->course_type == C_OBB) {
        echo '<input type="hidden" id="type_course_pro" value=1 />';
    }
}
}

// AK-GL creo oggetto per estendere il cosro e stampo i tab
$test = new extends_f2_course($id);
$test->print_tab_edit_course('corso');

echo $OUTPUT->heading($streditcoursesettings);
//echo $OUTPUT->heading($pagedesc);
// 2017 09 25

$editform->display();

echo $OUTPUT->footer();
