<?php

// $Id: add_forzatura.php 1125 2013-05-02 15:15:56Z d.lallo $ 

global $CFG;

require_once '../../config.php';
require_once "$CFG->dirroot/course/lib.php";
require_once "$CFG->libdir/adminlib.php";
require_once "$CFG->dirroot/user/filters/lib.php";
require_once($CFG->dirroot.'/f2_lib/report.php');
require_once('filters/lib.php');



require_login();

global $PAGE, $SITE, $OUTPUT;

$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$page     = optional_param('page', 0, PARAM_INT);
$perpage  = optional_param('perpage', 10, PARAM_INT);
$column   = optional_param('column', 'lastname', PARAM_TEXT);
$sort     = optional_param('sort', 'name', PARAM_TEXT);

$blockname = get_string('pluginname', 'block_f2_formazione_individuale');

$context = get_context_instance(CONTEXT_SYSTEM);

if (empty($CFG->loginhttps)) {
        $securewwwroot = $CFG->wwwroot;
} else {
        $securewwwroot = str_replace('http:','https:',$CFG->wwwroot);
}

$baseurl = new moodle_url('add_forzatura.php');
$forzature_url = new moodle_url('forzature.php');

$PAGE->set_context($context);
$PAGE->set_url('/blocks/f2_formazione_individuale/formatori/add_forzatura.php');
$PAGE->set_pagelayout('standard');
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('formazione_individuale', 'block_f2_formazione_individuale'));
$PAGE->navbar->add(get_string('forzature', 'block_f2_formazione_individuale'), $forzature_url);
$PAGE->navbar->add(get_string('add_forzatura', 'block_f2_formazione_individuale'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);

$capability_forzature = has_capability('block/f2_formazione_individuale:forzature', $context);
if(!$capability_forzature){
	print_error('nopermissions', 'error', '', 'forzature');
}

echo $OUTPUT->header();
$context = context_system::instance();
$extracolumns = get_extra_user_fields($context);
$columns = array_merge(array('firstname', 'lastname'), $extracolumns);

foreach ($columns as $column) {
    $string[$column] = get_user_field_name($column);
    if ($sort != $column) {
        $columnicon = "";
        $columndir = "ASC";
    } else {
        $columndir = $dir == "ASC" ? "DESC":"ASC";
        $columnicon = $dir == "ASC" ? "down":"up";
        $columnicon = " <img src=\"" . $OUTPUT->pix_url('t/' . $columnicon) . "\" alt=\"\" />";

    }
    $$column = "<a href=\"add_forzatura.php?sort=$column&amp;dir=$columndir\">".$string[$column]."</a>$columnicon";
}

if ($sort == "name") {
    $sort = "firstname";
}

// create the user filter form
$ufiltering = new my_users_filtering();
list($extrasql, $params) = $ufiltering->get_sql_filter();
$users = $ufiltering->get_my_users_listing($sort, $dir, $page*$perpage, $perpage, '', '', '', $extrasql, $params);
$usercount = $ufiltering->get_my_users_count(false);
$usersearchcount = $ufiltering->get_my_users_count(true, false, '', $extrasql, $params);

if ($extrasql !== '') {
    echo $OUTPUT->heading("$usersearchcount / $usercount ".get_string('users'));
    $usercount = $usersearchcount;
} else {
    echo $OUTPUT->heading("$usercount ".get_string('users'));
}

$strall = get_string('all');

$baseurl = new moodle_url('/blocks/f2_formazione_individuale/add_forzatura.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage));
echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);

flush();


if (!$users) {
    $match = array();
    echo $OUTPUT->heading(get_string('nousersfound'));

    $table = NULL;

} else {

    $override = new stdClass();
    $override->firstname = 'firstname';
    $override->lastname = 'lastname';
    $fullnamelanguage = get_string('fullnamedisplay', '', $override);
    if (($CFG->fullnamedisplay == 'firstname lastname') or
        ($CFG->fullnamedisplay == 'firstname') or
        ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'firstname lastname' )) {
        $fullnamedisplay = "$firstname / $lastname";
    } else { // ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'lastname firstname')
        $fullnamedisplay = "$lastname / $firstname";
    }

    $table = new html_table();
    $table->head = array ();
    $table->align = array();
    $table->head[] = $fullnamedisplay;
    $table->align[] = 'left';
    $table->head[] = get_string('matricola', 'block_f2_formazione_individuale');
    $table->align[] = 'left';
    $table->head[] = get_string('dominio', 'block_f2_formazione_individuale');
    $table->align[] = 'left';

    $table->width = "100%";
    foreach ($users as $user) {
        if (isguestuser($user)) {
            continue; // do not display guest here
        }

        $fullname = fullname($user, true);

        $row = array ();
        $row[] = "<a href=\"dettagli_forzatura.php?userid=$user->id\">$fullname</a>";
        $row[] = $user->idnumber;
		if($user->org_fullname){
			$row[] = $user->org_shortname.' - '.$user->org_fullname;
		}else
			$row[] = "";
        $table->data[] = $row;
    }
}

// add filters
$ufiltering->display_add();
$ufiltering->display_active();

if (!empty($table)) {
    echo html_writer::table($table);
    echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);
}

echo '<input type="button" value="'.get_string("indietro", "block_f2_formazione_individuale").'" onclick="parent.location=\''.$forzature_url.'\'">';

echo $OUTPUT->footer();
