<?php
ob_start();
/*if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from view.php
}*/
require_once '../../../config.php';

global $DB, $CFG;

$course = required_param('courseid', PARAM_INT);

require_once($CFG->dirroot.'/lib/tcpdf/tcpdf.php');

/*
 * La funzione riceve una stringa contenente i parametri passati in GET
 * @param stringa di parametri 
 * @return array <chiave,valore> per ogni parametro
 */
/*function convertUrlQuery($query) {
	$queryParts = explode('&', $query);

	$params = array();
	foreach ($queryParts as $param) {
		$item = explode('=', $param);
		$params[$item[0]] = $item[1];
	}

	return $params;
}

$args = parse_url($_SESSION['SESSION']->fromdiscussion,PHP_URL_QUERY);
$params = convertUrlQuery($args);
$course = $params['id'];*/

// create new PDF document
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// set document information
$pdf->SetCreator('Regione Piemonte');
$pdf->SetAuthor('CSI Piemonte');
$pdf->SetTitle(get_string('scheda_progetto','local_f2_traduzioni'));
$pdf->SetSubject(get_string('scheda_progetto','local_f2_traduzioni'));
$pdf->SetKeywords('PDF '.get_string('scheda_progetto','local_f2_traduzioni').', PDF');

// set default header data
//$pdf->SetHeaderData('local/f2_course/pdf/regionepiemonte.gif', '30');

// set header and footer fonts
//$pdf->setHeaderFont(Array('helvetica', '', '10'));
$pdf->setFooterFont(Array('helvetica', '', '8'));

// set default monospaced font
$pdf->SetDefaultMonospacedFont('courier');

//set margins
//$pdf->SetMargins('15', '27', '15');
//$pdf->SetHeaderMargin('5');
$pdf->SetFooterMargin('10');

//set auto page breaks
$pdf->SetAutoPageBreak(TRUE, '25');

//set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// ---------------------------------------------------------

// set font
$pdf->SetFont('helvetica', '', 12);

// manca la durata da aggiungere in f2_anagrafica_corsi

$data = $DB->get_record_sql("
		SELECT 
			c.idnumber,
			c.fullname,
			f2_sp.destinatari,
			f2_sp.accesso,
			f2_sp.obiettivi,
			UCASE(f2_ac.sf) as sf,
			UCASE(f2_ac.af) as af,
            f2_ac.durata,
			f2_sp.met1,
			f2_sp.met2,
			f2_sp.met3,
			f2_sp.dispense_vigenti,
			replace(f2_sp.contenuti,'\"','''') as contenuti,
			IF (f2_ac.flag_dir_scuola = 'D', 
				(SELECT fullname FROM {org} o WHERE o.id = f2_ac.id_dir_scuola), 
				(SELECT denominazione FROM {f2_fornitori} f2_f WHERE f2_f.id = f2_ac.id_dir_scuola)) as scuola 
		FROM 
			{f2_scheda_progetto} f2_sp,
			{f2_anagrafica_corsi} f2_ac,
			{course} c
		WHERE 
			f2_sp.courseid = c.id AND 
			c.id = f2_ac.courseid AND 
			c.id = ".$course);

$pdf->AddPage();

$pdf->Image('regionepiemonte.gif','90','5',35,0,'','GIF','');

if ($data->durata == intval($data->durata))
    $durata = intval($data->durata);
else
    $durata = number_format( $data->durata, 1, ',', '.');

if (!isset($data->idnumber)) $data_idnumber = "";
else $data_idnumber = $data->idnumber."&nbsp;";
if (!isset($data->fullname)) $data_fullname = "";
else $data_fullname = $data->fullname."&nbsp;";
if (!isset($data->destinatari)) $data_destinatari = "";
else $data_destinatari = $data->destinatari."&nbsp;";
if (!isset($data->accesso)) $data_accesso = "";
else $data_accesso = $data->accesso."&nbsp;";
if (!isset($data->obiettivi)) $data_obiettivi = "";
else $data_obiettivi = $data->obiettivi."&nbsp;";
if (!isset($data->sf)) $data_sf = "";
else $data_sf = $data->sf."&nbsp;";
if (!isset($data->af)) $data_af = "";
else $data_af = $data->af."&nbsp;";
if (!isset($data->durata)) $data_durata = "";
else $data_durata = $data->durata."&nbsp;";
if (!isset($data->met1)) $data_met1 = "";
else $data_met1 = $data->met1."&nbsp;";
if (!isset($data->met2)) $data_met2 = "";
else $data_met2 = $data->met2."&nbsp;";
if (!isset($data->met3)) $data_met3 = "";
else $data_met3 = $data->met3."&nbsp;";
if (!isset($data->dispense_vigenti)) $data_dispense_vigenti = "";
else $data_dispense_vigenti = $data->dispense_vigenti."&nbsp;";
if (!isset($data->contenuti)) $data_contenuti = "";
else $data_contenuti = $data->contenuti."&nbsp;";
if (!isset($data->scuola)) $data_scuola = "";
else $data_scuola = $data->scuola."&nbsp;";


$html = <<<EOF
<style>
p.header {
	text-align: center;
}
ul.body {
	text-align: left;
}
</style>
<div>
<p class="header"><b>$data_idnumber - $data_fullname</b></p></div>
<ul class="body">
	<li><b>DESTINATARI:</b><br/>$data_destinatari</li>
	&nbsp;<br/>
	<li><b>DURATA: </b>$durata giorno/i</li>
	&nbsp;<br/>
	<li><b>REQUISITI D'ACCESSO:</b><br/>$data_accesso</li>
	&nbsp;<br/>
	<li><b>OBIETTIVI:</b><br/>$data_obiettivi</li>
	&nbsp;<br/>
	<li><b>SEGMENTO FORMATIVO: </b>$data_sf</li>
	&nbsp;<br/>
	<li><b>AREA FORMATIVA: </b>$data_af</li>	
	&nbsp;<br/>
	<li><b>MODALITA DIDATTICHE:</b>
		<ul>
			<li><b>Esposizione teorica e/o normativa: </b>$data_met1 %</li>
			<li><b>Esposizione applicativa: </b>$data_met2 %</li>
			<li><b>Esposizione pratica: </b>$data_met3 %</li>
		</ul>
	</li>
	&nbsp;<br/>
	<li><b>DISPENSE:</b><br/>$data_dispense_vigenti</li>
	&nbsp;<br/>
	<li><b>CONTENUTI:</b><br/>$data_contenuti</li>
	&nbsp;<br/>
	<li><b>SCUOLA: </b>$data_scuola</li>
</ul>
EOF;

// Print text using writeHTML()
$pdf->writeHTML($html);
// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('scheda_progetto_'.str_replace('&nbsp;','',$data->idnumber).'.pdf', 'D');

//============================================================+
// END OF FILE                                                
//============================================================+
