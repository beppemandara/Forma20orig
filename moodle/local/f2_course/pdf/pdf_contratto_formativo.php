<?php 
ob_start();
require_once '../../../config.php';

global $CFG;

require_once($CFG->dirroot.'/lib/tcpdf/tcpdf.php');

function createPDF($data, $scuola_logo, $scuola_nome, $scuola_indirizzo, $editions = array()) {
	
	if (!isset($data->idnumber)) $data_idnumber = "";
	else $data_idnumber = $data->idnumber."&nbsp;";
	if (!isset($data->fullname)) $data_fullname = "";
	else $data_fullname = $data->fullname."&nbsp;";
	if (!isset($data->obiettivi)) $data_obiettivi = "";
	else $data_obiettivi = $data->obiettivi."&nbsp;";
	if (!isset($data->cf)) $data_cf = "";
	else $data_cf = $data->cf."&nbsp;";
	if (!isset($data->met1)) $data_met1 = "";
	else $data_met1 = $data->met1."&nbsp;";
	if (!isset($data->met2)) $data_met2 = "";
	else $data_met2 = $data->met2."&nbsp;";
	if (!isset($data->met3)) $data_met3 = "";
	else $data_met3 = $data->met3."&nbsp;";
	if (!isset($data->monitoraggio)) $data_monitoraggio = "";
	else $data_monitoraggio = $data->monitoraggio."&nbsp;";
	if (!isset($data->valutazione)) $data_valutazione = "";
	else $data_valutazione = $data->valutazione."&nbsp;";
	if (!isset($data->apprendimento)) $data_apprendimento = "";
	else $data_apprendimento = $data->apprendimento."&nbsp;";
	if (!isset($data->ricaduta)) $data_ricaduta = "";
	else $data_ricaduta = $data->ricaduta."&nbsp;";
	if (!isset($data->dispense_vigenti)) $data_dispense_vigenti = "";
	else $data_dispense_vigenti = $data->dispense_vigenti."&nbsp;";
	if (!isset($data->contenuti)) $data_contenuti = "";
	else $data_contenuti = $data->contenuti."&nbsp;";

	$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
	
	$pdf->SetCreator('Regione Piemonte');
	$pdf->SetAuthor('CSI Piemonte');
	$pdf->SetTitle(get_string('contratto_form','local_f2_traduzioni'));
	$pdf->SetSubject(get_string('contratto_form','local_f2_traduzioni'));
	$pdf->SetKeywords('PDF '.get_string('contratto_form','local_f2_traduzioni').', PDF');
	
	$pdf->setFooterFont(Array('helvetica', '', '8'));
	$pdf->SetDefaultMonospacedFont('courier');
	$pdf->SetFooterMargin('10');
	$pdf->SetAutoPageBreak(TRUE, '25');
	$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
	$pdf->SetFont('helvetica', '', 12);
	
// 	print_r($editions);exit;
	
	if (count($editions) >0 ) {
		foreach ($editions as $edition) {
			$pdf->AddPage();
			$pdf->Image('regionepiemonte.gif','90','5',35,0,'','GIF','');
			$docenti = "";
			if (!empty($edition->docenti)) {
				$docenti .= '<ul>';
				foreach ($edition->docenti as $docente) {
					$docenti .= "<li>$docente->firstname $docente->lastname</li>";
				}
				$docenti .= "</ul>";
			}

			if (!isset($edition->starthour) or empty($edition->starthour)) $edition_starthour = "";
			else $edition_starthour = $edition->starthour."&nbsp;";
			if (!isset($edition->endhour) or empty($edition->endhour)) $edition_endhour = "";
			else $edition_endhour = $edition->endhour."&nbsp;";
			if (!isset($edition->startdate) or empty($edition->startdate)) $edition_startdate = "";
			else $edition_startdate = $edition->startdate."&nbsp;";
			if (!isset($edition->enddate) or empty($edition->enddate)) $edition_enddate = "";
			else $edition_enddate = $edition->enddate."&nbsp;";
			
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
			<p class="header"><b>Sistema formativo del personale regionale - Sistema per la Qualit&agrave;</b><br/><br/>
			<b>CONTRATTO FORMATIVO DEL CORSO</b><br/><br/>
			<b><u>$data_idnumber - $data_fullname</u></b></p></div>
			<ul class="body">
				<li><b>SCUOLA:</b>
					<ul>
						<!--li><b>Logo: </b>$scuola_logo</li-->
						<li><b>Nome: </b>$scuola_nome</li>
						<li><b>Sede di svolgimento dell'attivit&agrave; didattica: </b>$scuola_indirizzo</li>
					</ul>
				</li>
				&nbsp;<br/>
				<li><b>ORARIO:</b><br/>$edition_starthour / $edition_endhour</li>
				&nbsp;<br/>
				<li><b>EDIZIONE:<br/>$edition_startdate - $edition_enddate</b></li>
				&nbsp;<br/>
				<li><b>OBIETTIVI:</b><br/>$data_obiettivi</li>
				&nbsp;<br/>
				<li><b>CREDITO FORMATIVO: </b>$data_cf</li>
				&nbsp;<br/>
				<li><b>DOCENTI: </b>
				$docenti
				</li>
				&nbsp;<br/>
				<li><b>MODALITA DIDATTICHE:</b>
					<ul>
						<li><b>Esposizione teorica e/o normativa: </b>$data_met1 %</li>
						<li><b>Esposizione applicativa: </b>$data_met2 %</li>
						<li><b>Esposizione pratica: </b>$data_met3 %</li>
					</ul>
				</li>
				&nbsp;<br/>
				<li><b>MONITORAGGIO E VERIFICHE:</b>
					<ul>
						<li><b>Strumenti di monitoraggio: </b>$data_monitoraggio</li>
						<li><b>Strumenti di valutazione: </b>$data_valutazione</li>
						<li><b>Modalit&agrave; di verifica dell'apprendimento: </b>$data_apprendimento</li>
						<li><b>Modalit&agrave; di verifica della ricaduta sull'attivit&agrave; lavorativa: </b>$data_ricaduta</li>
					</ul>
				</li>
				&nbsp;<br/>
				<li><b>MATERIALE DIDATTICO:</b>
					<ul>
						<li>DISPENSE: </b>$data_dispense_vigenti</li>
					</ul>
				</li>
				&nbsp;<br/>
				<li><b>CONTENUTI:</b><br/>$data_contenuti</li>
			</ul>
EOF;
// 			ob_end_clean();
			$pdf->writeHTML($html);
		}
	} else {
		$pdf->AddPage();
			
		$pdf->Image('regionepiemonte.gif','90','5',35,0,'','GIF','');
		
		
			
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
			<p class="header"><b>Sistema formativo del personale regionale - Sistema per la Qualit&agrave;</b><br/><br/>
			<b>CONTRATTO FORMATIVO DEL CORSO</b><br/><br/>
			<b><u>$data_idnumber - $data_fullname</u></b></p></div>
			<ul class="body">
				<li><b>SCUOLA:</b>
					<ul>
						<!--li><b>Logo: </b>$scuola_logo</li-->
						<li><b>Nome: </b>$scuola_nome</li>
						<li><b>Sede di svolgimento dell'attivit&agrave; didattica: </b>$scuola_indirizzo</li>
					</ul>
				</li>
				&nbsp;<br/>
				<li><b>ORARIO:</b><br/></li>
				&nbsp;<br/>
				<li><b>EDIZIONE:<br/></b></li>
				&nbsp;<br/>
				<li><b>OBIETTIVI:</b><br/>$data_obiettivi</li>
				&nbsp;<br/>
				<li><b>CREDITO FORMATIVO: </b>$data_cf</li>
				&nbsp;<br/>
				<li><b>DOCENTI:</br/></b></li>
				&nbsp;<br/>
				<li><b>MODALITA DIDATTICHE:</b>
					<ul>
						<li><b>Esposizione teorica e/o normativa: </b>$data_met1 %</li>
						<li><b>Esposizione applicativa: </b>$data_met2 %</li>
						<li><b>Esposizione pratica: </b>$data_met3 %</li>
					</ul>
				</li>
				&nbsp;<br/>
				<li><b>MONITORAGGIO E VERIFICHE:</b>
					<ul>
						<li><b>Strumenti di monitoraggio: </b>$data_monitoraggio</li>
						<li><b>Strumenti di valutazione: </b>$data_valutazione</li>
						<li><b>Modalit&agrave; di verifica dell'apprendimento: </b>$data_apprendimento</li>
						<li><b>Modalit&agrave; di verifica della ricaduta sull'attivit&agrave; lavorativa: </b>$data_ricaduta</li>
					</ul>
				</li>
				&nbsp;<br/>
				<li><b>MATERIALE DIDATTICO:</b>
					<ul>
						<li>DISPENSE: </b>$data_dispense_vigenti</li>
					</ul>
				</li>
				&nbsp;<br/>
				<li><b>CONTENUTI:</b><br/>$data_contenuti</li>
			</ul>
EOF;
// 		ob_end_clean();
		$pdf->writeHTML($html);
	}
	
	$pdf->Output('contratto_formativo'.str_replace('&nbsp;','',$data_idnumber).'.pdf', 'D');	
	
	//============================================================+
	// END OF FILE                                                
	//============================================================+
}
