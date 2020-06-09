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
require_once('addbudget_form.php');
require_once("lib.php");

global $DB, $OUTPUT, $PAGE, $USER;
require_login();
$context = context_system::instance();

$capabilityaddbudget = has_capability('block/formindbudget:budgetadd', $context);
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
$PAGE->navbar->add(get_string('totbudget', 'block_formindbudget'), $baseurl);
$PAGE->navbar->add(get_string('report', 'block_formindbudget'), $reporturl);

// Check capabilities and role.
if (!($capabilityaddbudget) && !areyousupervisor($USER->id)) {
    $il = ins_log_object(array('Accesso', 'Accesso negato per l\'id utente'.$USER->id));
    print_error('nopermissions', 'error', '');
}

$annoincorso = intval(date('Y'));
$budgetanno = get_budget_anno_corrente($annoincorso);
if ($budgetanno == 'nobudgetfound') {
    $stringazione = get_string('addbudget', 'block_formindbudget');
    $whatdo = 'inserimento';
} else {
    $stringazione = get_string('modbudget', 'block_formindbudget');
    $whatdo = 'modifica';
}

$addbudget = new addbudget_form(null, compact('annoincorso', 'stringazione'));
if ($addbudget->is_cancelled()) {
    redirect($baseurl);
} else if ($fromform = $addbudget->get_data()) {
    $site = get_site();
    echo $OUTPUT->header();
    // Controllo sanitario sui valori del form e costruzione oggetto dati budget.
    $budgetdata = sanitize_data($fromform, $whatdo);
    // Controllo coerenza dati budget.
    $checkbudgetdata = check_data($budgetdata);
    if ($checkbudgetdata == 'ok') {
        if ($budgetanno == 'nobudgetfound') {
            // Inserimento budget su db.
            if ($insert = $DB->insert_record('block_formindbudget', $budgetdata)) {
                $msg = get_result_message(get_string('okinsbudget', 'block_formindbudget'));
                // Storico log.
                $is = ins_storico_budget(array($annoincorso, $budgetdata->budget, $USER->id));
            } else {
                $msg = get_result_message(get_string('koinsbudget', 'block_formindbudget'));
            }
            $params = array('azione' => 'ins', 'msg' => $msg);
            $viewurl = new moodle_url('/blocks/formindbudget/view.php', $params);
            // Log.
            $il = ins_log_object(array('Insert', $msg));
            redirect($viewurl);
            // \mod_assign\event\submission_viewed::create_from_submission($this, $item)->trigger();
            // \block_formindbudget\event\budget_created::
        } else {
            // Modifica budget su db.
            if ($idrecord = get_buget_id($annoincorso)) {
                $aggiornamento = get_update_data($idrecord, $budgetdata);
                if ($resupd = $DB->update_record('block_formindbudget', $aggiornamento)) {
                    $msg = get_result_message(get_string('okmodbudget', 'block_formindbudget'));
                    // Storico log.
                    $is = ins_storico_budget(array($annoincorso, $budgetdata->budget, $USER->id));
                } else {
                    $msg = get_result_message(get_string('komodbudget', 'block_formindbudget'));
                }
                $params = array('azione' => 'upd', 'msg' => $msg);
                $viewurl = new moodle_url('/blocks/formindbudget/view.php', $params);
                // Log.
                $il = ins_log_object(array('Update', $msg));
                redirect($viewurl);
            } else {
                echo $OUTPUT->error_text(get_string('nobudgetidfound', 'block_formindbudget'));
                echo '<br /><br />';
                echo $OUTPUT->single_button($baseurl, get_string('totbudget', 'block_formindbudget'));
            }
        }
    } else {
        // Errore sulla validazione dei campi.
        $strerr = code2error($checkbudgetdata);
        // Log.
        $il = ins_log_object(array('Errore', $strerr));
        echo $OUTPUT->error_text($strerr);
        echo '<br /><br />';
        echo $OUTPUT->single_button($addbudgeturl, get_string('addbudget', 'block_formindbudget'));
        echo $OUTPUT->single_button($baseurl, get_string('totbudget', 'block_formindbudget'));
    }
    echo $OUTPUT->footer();
} else {
    // Form didn't validate or this is the first display.
    $site = get_site();
    echo $OUTPUT->header();
    $manutenzione = false;
    if ($manutenzione === true) {
        echo $OUTPUT->box(get_string('manteinance', 'block_formindbudget'));
    } else {
        if ($budgetanno == 'nobudgetfound') {
            echo '<div><h4>'.get_string('nobudgetfound', 'block_formindbudget').'</h4></div>';
        }
        $addbudget->display();
    }
    echo $OUTPUT->footer();
}
