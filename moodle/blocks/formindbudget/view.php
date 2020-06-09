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
require_once('formindbudget_form.php');
require_once("lib.php");
require_once($CFG->libdir.'/tablelib.php');

global $DB, $OUTPUT, $PAGE, $USER;
require_login();
$context = context_system::instance();

$azione = optional_param('azione', '', PARAM_TEXT);
$msg = optional_param('msg', '', PARAM_TEXT);
setlocale(LC_MONETARY, 'it_IT');
$capabilitybudget = has_capability('block/formindbudget:view', $context);
$baseurl = new moodle_url('/blocks/formindbudget/view.php');
$addbudgeturl = new moodle_url('/blocks/formindbudget/addbudget.php');
$helpurl = new moodle_url('/blocks/formindbudget/help.php');
$reporturl = new moodle_url('/blocks/formindbudget/report.php');
$blockname = get_string('pluginname', 'block_formindbudget');

$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/formindbudget/view.php');
$PAGE->set_title($blockname);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('help', 'block_formindbudget'), $helpurl);
$PAGE->navbar->add(get_string('report', 'block_formindbudget'), $reporturl);
$PAGE->navbar->add(get_string('addbudget', 'block_formindbudget'), $addbudgeturl);

// Check capabilities and role.
if (!($capabilitybudget) && !areyousupervisor($USER->id)) {
    $il = ins_log_object(array('Accesso', 'Accesso negato per l\'id utente'.$USER->id));
    print_error('nopermissions', 'error', '');
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('headertext', 'block_formindbudget'));

echo "<center><h3>".$msg."</h3></center>";

$annoincorso = intval(date('Y'));
$budgetvalue = get_budget_anno_corrente($annoincorso);
if ($budgetvalue == 'nobudgetfound') {
    $budgetvalue = get_string('nobudgetfound', 'block_formindbudget');
    $row2 = array (get_string('totbudget', 'block_formindbudget'), $budgetvalue);
    $bottone = $OUTPUT->single_button($addbudgeturl, get_string('addbudget', 'block_formindbudget'), 'get');
} else {
    $row2 = array (get_string('totbudget', 'block_formindbudget'), money_format('%.2n', $budgetvalue));
    $bottone = $OUTPUT->single_button($addbudgeturl, get_string('modbudget', 'block_formindbudget'), 'get');
}
$sommadir = get_situazione_contabile($annoincorso);

$tabriep = new html_table('riepilogo_budget_table');
$tabriep->head = array('Descrizione', 'Valore');
$tabriep->align = array('left', 'center');
$tabriep->width = "30%";
$row1 = array (get_string('anno', 'block_formindbudget'), $annoincorso);
$row3 = array (get_string('sitcontab', 'block_formindbudget'), money_format('%.2n', $sommadir));
$row4 = array(get_string('spesa', 'block_formindbudget'), money_format('%.2n', get_sum_budgets($annoincorso)));
$tabriep->data[] = $row1;
$tabriep->data[] = $row2;
$tabriep->data[] = $row3;
$tabriep->data[] = $row4;
echo html_writer::table($tabriep);

echo $bottone;
echo $OUTPUT->single_button($reporturl, get_string('report', 'block_formindbudget'), 'get');

$table = new flexible_table('budget_direzioni_table');
$table->define_columns(array('direzione', 'cumdet', 'sinedet'));
$table->define_headers(array('Direzione', 'Con determina', 'Senza determina'));
$table->define_baseurl($PAGE->url);
$table->set_attribute('id', 'direzioni');
$table->set_attribute('class', 'admintable generaltable');
$table->column_style('cumdet', 'text-align', 'center');
$table->column_style('sinedet', 'text-align', 'center');
$table->setup();

$direzioni = get_direzioni_and_budget($annoincorso);

foreach ($direzioni as $direz) {
    $table->add_data(array($direz['direzione'], $direz['costocd'], $direz['costosd']));
}

$sumbudgetdirdet = get_sum_budgets($annoincorso, ' AND A.id_determine > 0 ');
$sumbudgetdirndet = get_sum_budgets($annoincorso, ' AND A.id_determine = 0 ');
$sumbudgetdirdetf = money_format('%.2n', $sumbudgetdirdet);
$sumbudgetdirndetf = money_format('%.2n', $sumbudgetdirndet);
$table->add_data(array(get_string('totale', 'block_formindbudget'), $sumbudgetdirdetf, $sumbudgetdirndetf));

$table->print_html();

echo $OUTPUT->footer();
