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

require_once('../../config.php');
require_once("lib.php");

global $SITE, $CFG, $OUTPUT, $PAGE;

require_login();
$context = context_system::instance();

$capabilitybudget = has_capability('block/formindbudget:view', $context);
$baseurl = new moodle_url('/blocks/formindbudget/view.php');
$addbudgeturl = new moodle_url('/blocks/formindbudget/addbudget.php');
$helpurl = new moodle_url('/blocks/formindbudget/help.php');
$blockname = get_string('pluginname', 'block_formindbudget');

$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/formindbudget/view.php');
$PAGE->set_title($blockname);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('addbudget', 'block_formindbudget'), $addbudgeturl);
$PAGE->navbar->add(get_string('totbudget', 'block_formindbudget'), $baseurl);

// Check capabilities and role.
if (!($capabilitybudget) && !areyousupervisor($USER->id)) {
    print_error('nopermissions', 'error', '');
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('help', 'block_formindbudget'));
$istruzioni = get_instructions();
echo $istruzioni;
echo $OUTPUT->footer();

