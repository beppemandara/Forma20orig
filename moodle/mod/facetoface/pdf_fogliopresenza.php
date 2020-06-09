<?php
ob_start();
require_once '../../config.php';

global $CFG;

//require_once$CFG->dirroot.'/lib/tcpdf/tcpdf.php';
require_once($CFG->dirroot.'/lib/tcpdf/tcpdf.php');

function createPDF($session, $teachers, $bookingUsers, $sessionDates, $sessionInfo) {
	$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
	
	$pdf->SetCreator('Regione Piemonte');
	$pdf->SetAuthor('CSI');
	$pdf->SetTitle(get_string('fogliop','local_f2_traduzioni'));
	$pdf->SetSubject(get_string('fogliop','local_f2_traduzioni'));
	$pdf->SetKeywords('PDF '.get_string('fogliop','local_f2_traduzioni').', PDF');
	
	$pdf->setFooterFont(Array('helvetica', '', '8'));
	$pdf->SetDefaultMonospacedFont('courier');
	$pdf->SetFooterMargin('10');
	$pdf->SetAutoPageBreak(TRUE, '25');
	$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
	$pdf->SetFont('helvetica', '', 9);
	
	$htmlTeachers = "";
	$tipoDoc = "";
	if (!empty($teachers)) {
		foreach ($teachers as $teacher) {
			$htmlTeachers .= "<tr>";
			if ($teacher->tipodoc == "E") $tipoDoc = "Esterno";
			elseif ($teacher->tipodoc == "I") $tipoDoc = "Interno";
			else $tipoDoc = "Tutte";
			
			$htmlTeachers .= "<td>".$teacher->firstname."&nbsp;".$teacher->lastname."&nbsp;"."(".$teacher->idnumber."), ".$tipoDoc."&nbsp;</td>";
			$htmlTeachers .= "<td>&nbsp;</td>";
			$htmlTeachers .= "</tr>";
		}
	}
	
	$address = "";
	if (!empty($sessionInfo->indirizzo) && !empty($sessionInfo->sede))
		$address = $sessionInfo->indirizzo.", ".$sessionInfo->sede."&nbsp;";
	elseif (!empty($sessionInfo->indirizzo))
		$address = $sessionInfo->indirizzo."&nbsp;";
	elseif (!empty($sessionInfo->sede))
		$address = $sessionInfo->sede."&nbsp;";
	
	$htmlBookingUsers = "";
	if (!empty($bookingUsers)) {
		foreach ($bookingUsers->dati as $user) {
			$htmlBookingUsers .= "<tr>";
			$htmlBookingUsers .= "<td>".$user->lastname."&nbsp;". $user->firstname."&nbsp; (".$user->idnumber.")</td>";
			$htmlBookingUsers .= "<td>".$user->phone."&nbsp;</td>";
			$htmlBookingUsers .= "<td>".$user->email."&nbsp;</td>";
			$htmlBookingUsers .= "<td>_____________</td>";
			$htmlBookingUsers .= "<td>_________________</td>";
			$htmlBookingUsers .= "</tr>";
		}
	}
	
	if (!isset($sessionInfo->coursetype) or empty($sessionInfo->coursetype)) $sessionInfo_coursetype = "";
	else $sessionInfo_coursetype = $sessionInfo->coursetype."&nbsp;";
	if (!isset($sessionInfo->fullname) or empty($sessionInfo->fullname)) $sessionInfo_fullname = "";
	else $sessionInfo_fullname = $sessionInfo->fullname."&nbsp;";
	
	$pdf->AddPage();
	
	$html = 
<<<EOF
	<style>
		p.header {
			text-align: center;
		}
		ul.body {
			text-align: left;
		}
		</style>
	<div>
	<p class="header"><h1>Foglio di presenza attivit&agrave;</h1></p><br/><br/>
	<h3>$sessionInfo_coursetype: $sessionInfo_fullname</h3><br/><br/>
	<b>Data:___________________</b><br/>
	----------------------------------------------------------------------------------------------------------------------------------------------------------------------<br/><br/>
	<table>
	<tr>
		<td class="content"><b>Data e ora:</b></td>
		<td class="content"><b>Ubicazione:</b></td>
	</tr>
	<tr>
		<td>$sessionDates->timestart - $sessionDates->timefinish</td>
		<td>$address</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td><b>Numero di utenti iscritti: $bookingUsers->count</b></td>
		<td><b>Capacit&agrave;: $sessionInfo->capacity</b></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td><b>Nome docente (identificativo docente):</b></td>
		<td>&nbsp;</td>
	</tr>
	$htmlTeachers
	<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	</table>
	<table>
	<tr>
		<td width="25%"><b>Nome utente (identificativo utente): Posizione</b></td>
		<td width="8%"><b>Telefono:</b></td>
		<td width="30%"><b>Indirizzo e-mail:</b></td>
		<td width="15%"><b>Partecipazione:</b></td>
		<td width="22%"><b>Firma:</b></td>
	</tr>
	$htmlBookingUsers
	</table>
	</div>
EOF;
	$pdf->writeHTML($html);
	
	$pdf->Output('Foglio_presenza_'.str_replace('&nbsp;','',$sessionInfo_fullname).'.pdf', 'D');
}
