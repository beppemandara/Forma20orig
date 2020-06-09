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
require_once("$CFG->libdir/excellib.class.php");

require_login();

$anno = required_param('anno', PARAM_INT);
$strfoglio = 'Situazione Budget Formazione Individuale';
$data = date("YmdHis");
$today = date("dd/mm/YYYY");
$downloadfilename = 'Budget_Formazione_Individuale_'.$data.'.xlsx';

$budgetvalue = get_budget_anno_corrente($anno);
if ($budgetvalue == 'nobudgetfound') {
    $budget = 'N.D.';
}
// Estrazione dati.
$direzioni = get_direzioni_and_budget($anno);
// Creating a workbook.
$workbook = new MoodleExcelWorkbook("-");
// Sending HTTP headers.
$workbook->send($downloadfilename);
// Adding the worksheet.
$myxls =& $workbook->add_worksheet($strfoglio);
// Inizializzazione della riga.
$r = 3;
// Formati.
$formato0 = array(
                  'size' => 12,
                  'bold' => 1,
                  'h_align' => 'center'
                 );
$formato1 = array(
                  'color' => 'white',
                  'size' => 10,
                  'bold' => 1,
                  'underline' => 1,
                  'v_align' => 'center',
                  'h_align' => 'center',
                  'border' => 1,
                  'text_wrap' => 1,
                  'bg_color' => '#00B777'
                 );
$formato2 = array(
                  'v_align' => 'center',
                  'h_align' => 'left',
                  'border' => 1,
                  'text_wrap' => 1
                 );
$formato3 = array(
                  'v_align' => 'center',
                  'h_align' => 'center',
                  'num_format' => '#,##0.00',
                  'border' => 1
                 );
$formato4 = array(
                  'h_align' => 'left',
                  'border' => 1,
                  'bg_color' => '#00B777'
                 );
$formato5 = array(
                  'h_align' => 'center',
                  'num_format' => '#,##0.00',
                  'border' => 1,
                  'bg_color' => '#00B777'
                 );
$formato6 = array(
                  'h_align' => 'center',
                  'border' => 1,
                  'bg_color' => '#00B777'
                 );
$formato7 = array(
                  'v_align' => 'center',
                  'h_align' => 'center',
                  'border' => 1
                 );
$formato8 = array(
                  'v_align' => 'center',
                  'h_align' => 'center',
                  'num_format' => 'd-mmm-yy',
                  'border' => 1
                 );
$myxls->set_column(0, 0, 50);
$myxls->set_column(1, 6, 10);
// Intestazione report.
$myxls->merge_cells(0, 0, 0, 6);
$myxls->write_string(0, 0, 'Situazione Budget Formazione Individuale', $workbook->add_format($formato0));
$myxls->write_string(2, 0, 'anno selezionato '.$anno, $workbook->add_format($formato1));
// Print names of all the fields.
$myxls->write_string($r, 0, get_string('direzione', 'block_formindbudget'), $workbook->add_format($formato1));
$myxls->write_string($r, 1, get_string('totimp', 'block_formindbudget'), $workbook->add_format($formato1));
$myxls->write_string($r, 2, get_string('impcd', 'block_formindbudget'), $workbook->add_format($formato1));
$myxls->write_string($r, 3, get_string('impsd', 'block_formindbudget'), $workbook->add_format($formato1));
$myxls->write_string($r, 4, get_string('nctot', 'block_formindbudget'), $workbook->add_format($formato1));
$myxls->write_string($r, 5, get_string('nccs', 'block_formindbudget'), $workbook->add_format($formato1));
$myxls->write_string($r, 6, get_string('nccc', 'block_formindbudget'), $workbook->add_format($formato1));
// Print all the lines of data.
foreach ($direzioni as $direz) {
    $r++;
    $myxls->write_string($r, 0, $direz['direzione'], $workbook->add_format($formato2));
    $costi = $direz['costocd'] + $direz['costosd'];
    $myxls->write_number($r, 1, $costi, $workbook->add_format($formato3));
    $myxls->write_number($r, 2, $direz['costocd'], $workbook->add_format($formato3));
    $myxls->write_number($r, 3, $direz['costosd'], $workbook->add_format($formato3));
    $myxls->write_number($r, 4, $direz['nctot'], $workbook->add_format($formato7));
    $myxls->write_number($r, 5, $direz['nccs'], $workbook->add_format($formato7));
    $myxls->write_number($r, 6, $direz['nccc'], $workbook->add_format($formato7));
}
$r++;
// Totali.
$myxls->write_string($r, 0, get_string('totali', 'block_formindbudget'), $workbook->add_format($formato4));
$myxls->write_number($r, 1, get_sum_budgets($anno), $workbook->add_format($formato5));
$myxls->write_number($r, 2, get_sum_budgets($anno, ' AND A.id_determine > 0 '), $workbook->add_format($formato5));
$myxls->write_number($r, 3, get_sum_budgets($anno, ' AND A.id_determine = 0 '), $workbook->add_format($formato5));
$myxls->write_number($r, 4, get_tot_corsi($anno), $workbook->add_format($formato6));
$myxls->write_number($r, 5, get_tot_corsi($anno, ' AND A.costo > 0 '), $workbook->add_format($formato6));
$myxls->write_number($r, 6, get_tot_corsi($anno, ' AND A.cassa_economale > 0 '), $workbook->add_format($formato6));
// Data report.
$r++;
$myxls->write_string($r, 0, 'Data creazione report ', $workbook->add_format($formato2));
$myxls->write_date($r, 1, time(), $workbook->add_format($formato8));
// Close the workbook.
$workbook->close();
exit;
