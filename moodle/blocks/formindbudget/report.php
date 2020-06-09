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
require_once('report_form.php');
require_once("lib.php");
require_once($CFG->libdir.'/tablelib.php');

global $DB, $OUTPUT, $PAGE, $USER;
require_login();
$context = context_system::instance();

setlocale(LC_MONETARY, 'it_IT');
// Inserire la capability.
$capabilityreport = has_capability('block/formindbudget:reportdwnl', $context);
$baseurl = new moodle_url('/blocks/formindbudget/view.php');
$addbudgeturl = new moodle_url('/blocks/formindbudget/addbudget.php');
$helpurl = new moodle_url('/blocks/formindbudget/help.php');
$reporturl = new moodle_url('/blocks/formindbudget/report.php');
$blockname = get_string('pluginname', 'block_formindbudget');

$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/formindbudget/report.php');
$PAGE->set_title($blockname);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('help', 'block_formindbudget'), $helpurl);
$PAGE->navbar->add(get_string('addbudget', 'block_formindbudget'), $addbudgeturl);
$PAGE->navbar->add(get_string('totbudget', 'block_formindbudget'), $baseurl);

// Check capabilities and role.
if (!($capabilityreport) && !areyousupervisor($USER->id)) {
    $il = ins_log_object(array('Accesso', 'Accesso negato per l\'id utente'.$USER->id));
    print_error('nopermissions', 'error', '');
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('reporttext', 'block_formindbudget'));

$reportbudget = new report_form();
if ($reportbudget->is_cancelled()) {
    redirect($baseurl);
} else if ($fromform = $reportbudget->get_data()) {
    $site = get_site();
    $annosel = $fromform->anni;

    $budgetvalue = get_budget_anno_corrente($annosel);
    if ($budgetvalue == 'nobudgetfound') {
        $budget = 'N.D.';
    } else {
        $budget = money_format('%.2n', $budgetvalue);
    }
    $strannosel = get_string('yearsel', 'block_formindbudget');
    $spesa = get_string('spesa', 'block_formindbudget');
    $eurspesi = money_format('%.2n', get_sum_budgets($annosel));
    $sitcont = get_string('sitcontab', 'block_formindbudget');
    $rimasti = money_format('%.2n', get_situazione_contabile($annosel));
    $strbudget = get_string('totbudget', 'block_formindbudget');
    $tabrep = new html_table('report_budget_table');
    $tabrep->align = array('center', 'center', 'center', 'center');
    $row1 = array ($strannosel, $annosel, $strbudget, $budget, $spesa, $eurspesi, $sitcont, $rimasti);
    $tabrep->data[] = $row1;
    echo html_writer::table($tabrep);

    $parametri = array('anno' => $annosel);
    $dwnlurl = new moodle_url('dwnl_report.php', $parametri);
    $strdwnl = get_string('scarica', 'block_formindbudget');
    $link = html_writer::link($dwnlurl, $strdwnl);
    $collegamento = html_writer::tag('h3', $link);
    echo $collegamento;

    $table = new flexible_table('budget_report_table');
    $table->define_columns(array('direzione', 'totimp', 'cumdet', 'sinedet', 'ncortot', 'ncorcs', 'ncorcc'));
    $intestazioni = array(get_string('direzione', 'block_formindbudget'),
                          get_string('totimp', 'block_formindbudget'),
                          get_string('impcd', 'block_formindbudget'),
                          get_string('impsd', 'block_formindbudget'),
                          get_string('nctot', 'block_formindbudget'),
                          get_string('nccs', 'block_formindbudget'),
                          get_string('nccc', 'block_formindbudget'));
    $table->define_headers($intestazioni);
    $table->define_baseurl($PAGE->url);
    $table->set_attribute('class', 'admintable generaltable');
    $table->column_style('totimp', 'text-align', 'center');
    $table->column_style('cumdet', 'text-align', 'center');
    $table->column_style('sinedet', 'text-align', 'center');
    $table->column_style('ncortot', 'text-align', 'center');
    $table->column_style('ncorcs', 'text-align', 'center');
    $table->column_style('ncorcc', 'text-align', 'center');
    $table->setup();

    $direzioni = get_direzioni_and_budget($annosel);

    foreach ($direzioni as $direz) {
        $table->add_data(array($direz['direzione'],
                               $direz['costocd'] + $direz['costosd'],
                               $direz['costocd'],
                               $direz['costosd'],
                               $direz['nctot'],
                               $direz['nccs'],
                               $direz['nccc']
        ));
    }

    $sumbudgetdirdet = get_sum_budgets($annosel, ' AND A.id_determine > 0 ');
    $sumbudgetdirndet = get_sum_budgets($annosel, ' AND A.id_determine = 0 ');
    $sumbudgetdirdetf = money_format('%.2n', $sumbudgetdirdet);
    $sumbudgetdirndetf = money_format('%.2n', $sumbudgetdirndet);
    $totcorsi = get_tot_corsi($annosel);
    $totcorsics = get_tot_corsi($annosel, ' AND A.costo > 0 ');
    $totcorsicc = get_tot_corsi($annosel, ' AND A.cassa_economale > 0 ');
    $table->add_data(array(get_string('totali', 'block_formindbudget'),
                           money_format('%.2n', get_sum_budgets($annosel)),
                           $sumbudgetdirdetf,
                           $sumbudgetdirndetf,
                           $totcorsi,
                           $totcorsics,
                           $totcorsicc
    ));

    $table->print_html();

} else {
    // Form didn't validate or this is the first display.
    $site = get_site();
    $manutenzione = false;
    if ($manutenzione === true) {
        echo $OUTPUT->box(get_string('manteinance', 'block_formindbudget'));
    } else {
        $reportbudget->display();
    }
}
echo $OUTPUT->footer();
