<?php
global $PAGE, $SITE, $OUTPUT, $CFG;

require_once('../../config.php');
require_once 'lib_ind_senza_determina.php';
require_once($CFG->dirroot.'/grade/export/lib.php');
require_once($CFG->dirroot.'/lib/excellib.class.php');

$training = optional_param('training', '', PARAM_TEXT);
$mod      = optional_param('mod', 0, PARAM_INT);            //Se abilitata la modifica = 1
$sort     = optional_param('sort', 'ASC', PARAM_ALPHANUM);
$column   = optional_param('column', 'cognome', PARAM_TEXT);

// EXCEL
$strgrades = 'Corsi individuali senza spesa archiviati';
$downloadfilename = 'report_corsi_senza_determina_archiviati.xls';
// Creating a workbook
$workbook = new MoodleExcelWorkbook("-");
// Sending HTTP headers
$workbook->send($downloadfilename);
// Adding the worksheet
$myxls = $workbook->add_worksheet($strgrades);
// crea il formato
//$bold = array('bold'=>1);
// Incipit
//$myxls->apply_row_format(0,$bold);
// Add and define a format
$format = $workbook->add_format(); // Add a format
$format->set_bold();
$format->set_color('blue');
$format->set_align('center');
$myxls->write_string(0, 0, 'Corsi individuali senza spesa archiviati', $format);
// unione celle della prima riga
$myxls->merge_cells(0, 0, 0, 6);
// set the column width
$myxls->set_column(0, 0, 32);
$myxls->set_column(1, 2, 10);
$myxls->set_column(3, 3, 40);
$myxls->set_column(3, 3, 40);
$myxls->set_column(4, 4, 16);
$myxls->set_column(5, 5, 32);
$myxls->set_column(6, 6, 16);

// Intestazione
$myxls->write_string(2,0,'Utente',$format);
$myxls->write_string(2,1,'Matricola',$format);
$myxls->write_string(2,2,'Data Inizio',$format);
$myxls->write_string(2,3,'Titolo Corso',$format);
$myxls->write_string(2,4,'Protocollo',$format);
$myxls->write_string(2,5,'Data Invio Mail',$format);
$myxls->write_string(2,6,'Ente',$format);
// Print all the lines of data.
$i = 2;
$data = new stdClass;
$data->tipo_corso     = $training;
$data->dato_ricercato = '';
$data->column         = $column;
$data->sort           = $sort;
$data->columnprot     = 'prot';
$data->sortprot       = 'ASC';

$datiall = get_corsi_ind_archiviati($data, $mod);
$courses = $datiall->dati;

foreach ($courses as $course) {
  $i++;
  $myxls->write_string($i,0,$course->cognome." ".$course->nome);
  $matricola = get_forzatura_or_moodleuser_ind($course->username);
  $myxls->write_string($i,1,$matricola->idnumber);
  $myxls->write_string($i,2,date('d/m/Y',$course->data_inizio));
  $myxls->write_string($i,3,$course->titolo);
  $myxls->write_string($i,4,get_num_protocollo($course->id));
  if ($course->modello_email == "-1") {
    $txt = 'Modello di comunicazione senza mail';
    $myxls->write_string($i,5,$txt);
  } else {
    $myxls->write_string($i,5,date('d/m/Y',$course->data_invio_mail));
  }
   $myxls->write_string($i,6,$course->ente);
}
// Close the workbook
$workbook->close();
exit;
