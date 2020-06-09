<?php
//$Id: pdf_determina.php 1295 2014-07-04 12:48:42Z l.moretto $
ob_start();
require_once '../../config.php';
require_once 'lib.php';
require_once($CFG->dirroot.'/f2_lib/core.php');
//print_r($_POST);
global $CFG;

require_once$CFG->dirroot.'/lib/tcpdf/tcpdf.php';

$training         = required_param('training', PARAM_ALPHA);
$id_course        = optional_param('id_course' , 0, PARAM_INT);
$schede_corsi     = optional_param('schede_corsi', 0, PARAM_BOOL);
$prospetto_costi  = optional_param('prospetto_costi', 0, PARAM_BOOL);
$array_prospetti  = optional_param('array_prospetti', '', PARAM_TEXT);
//var_dump($training);
//var_dump($id_course);
//var_dump($schede_corsi);
//var_dump($prospetto_costi);
//var_dump($array_prospetti);
//var_dump($_POST);die();

if($schede_corsi && !empty($id_course)){
    createPDF($id_course, "schede_corsi");
}

if($prospetto_costi && !empty($array_prospetti)){
    createPDF(unserialize($array_prospetti), "prospetto_costi");
}

function createPDF($dati_pdf, $tipo) {
	//$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    //AK-LM: con la stringa di creazione di cui sopra Acrobat Reader 10.1.9 stampa una pag. bianca.
    //       Usata la seguente, in concomitanza con l'utilizzo della funz. utf8_decode() per le stringhe con caratteri speciali.
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, false, 'ISO-8859-1', false);
	
	$pdf->SetCreator('Regione Piemonte');
	$pdf->SetAuthor('CSI Piemonte');
	$pdf->SetTitle(get_string('fogliop','local_f2_traduzioni'));
	$pdf->SetSubject(get_string('fogliop','local_f2_traduzioni'));
	$pdf->SetKeywords('PDF '.get_string('fogliop','local_f2_traduzioni'));
	
	$pdf->setFooterFont(Array('helvetica', '', '8'));
	$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
	$pdf->SetFooterMargin('10');
	$pdf->SetAutoPageBreak(TRUE, '15');
	$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
	$pdf->SetFont('helvetica', '', 12, '', true);

if($tipo=="schede_corsi"){
	foreach ($dati_pdf as $id_determina){
			
		$data = new stdClass();
		$data->id_corso_ind = $id_determina;
	
		$dati_corso_ind = get_scheda_descrittiva_by_id($data);
		
		$dati_corso_ind_forz = get_forzatura_or_moodleuser($dati_corso_ind->username);	
		
		$settore ="";
		
		if(isset($dati_corso_ind_forz->settore)){
			$settore = $dati_corso_ind_forz->settore;
		}

        $pdf->AddPage();

        $dati_table = "<div>"
                    .get_string('cognome', 'block_f2_formazione_individuale')." e 
                    ".get_string('nome', 'block_f2_formazione_individuale').": <b>"
                    .utf8_decode(fullname($dati_corso_ind))."</b><br><br>

                    ".get_string('qualifica', 'block_f2_formazione_individuale').": 
                     <b>".$dati_corso_ind_forz->category."</b><br><br>

                    ".get_string('direzi', 'block_f2_formazione_individuale').": 
                     <b>".utf8_decode($dati_corso_ind_forz->direzione)."</b><br><br>

                    ".get_string('settore', 'block_f2_formazione_individuale').": 
                     <b>".utf8_decode($settore)."</b><br><br>

                    ".get_string('titolo', 'block_f2_formazione_individuale').": 
                     <b>".utf8_decode($dati_corso_ind->titolo)."</b><br><br>

                    ".get_string('sede_svolgimento', 'block_f2_formazione_individuale').": 
                     <b>".utf8_decode($dati_corso_ind->localita)."</b><br><br>

                    ".get_string('durata_giorni', 'block_f2_formazione_individuale').": 
                     <b>".number_format($dati_corso_ind->durata,2,",",".")."</b><br><br>

                    ".get_string('data_inizio', 'block_f2_formazione_individuale').": 
                     <b>".date("d/m/Y",$dati_corso_ind->data_inizio)."</b><br><br>

                    ".get_string('ente_organizzatore', 'block_f2_formazione_individuale').": 
                     <b>".utf8_decode($dati_corso_ind->ente)."</b><br><br>

                    ".get_string('costo', 'block_f2_formazione_individuale').": 
                     <b>".number_format($dati_corso_ind->costo,2,",",".")."</b><br><br>
                    ".get_string('beneficiario_pagamento', 'block_f2_formazione_individuale').": 
                     <b>".utf8_decode($dati_corso_ind->beneficiario_pagamento)."</b><br><br>

                    ".get_string('via', 'block_f2_formazione_individuale').": 
                     <b>".utf8_decode($dati_corso_ind->via)."</b><br><br>

                    ".get_string('partita_iva', 'block_f2_formazione_individuale').": 
                     <b>".$dati_corso_ind->partita_iva."</b><br><br>

                    ".get_string('codice_fiscale', 'block_f2_formazione_individuale').": 
                     <b>".$dati_corso_ind->codice_fiscale."</b><br><br>

                    ".get_string('codice_creditore', 'block_f2_formazione_individuale').": 
                     <b>".$dati_corso_ind->codice_creditore."</b><br><br>

                    ".get_string('note', 'block_f2_formazione_individuale').": 
                     <b>".utf8_decode($dati_corso_ind->note)."</b><br><br>
					</div>";
        $note_cassa_economale = "";
        if($dati_corso_ind->cassa_economale){
            $cassa_economale = get_parametro('p_f2_corsiind_nota_cassa_economale');
            $note_cassa_economale = "<br><br><b>{$cassa_economale->val_char}</b>";
        }
        $scheda_descrittiva_allegato_a = get_string('scheda_descrittiva_allegato_a', 'block_f2_formazione_individuale');

// a.a. agosto 2015
        // estraggo dalla tabella mdl_f2_parametri le descrizioni di Direzione e Settore
        $parametro_aa = get_parametro('p_f2_corsi_individuali_direzione');
        $direzione_aa = $parametro_aa->val_char;
        $parametro_aa = get_parametro('p_f2_corsi_individuali_settore');
        $settore_aa   = $parametro_aa->val_char;
        
        $determinazione ="Allegato A  determinazione n.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    &nbsp;&nbsp;&nbsp;&nbsp; del ";
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
    <div align="center">$determinazione</div>
    <div align="center">
    <img src="pdf/regione_piemonte.png" alt="regione_piemonte" height="60" width="180"/>	
    <p>$direzione_aa</p>
    <h2 align="center">$scheda_descrittiva_allegato_a</h2></div>
    $dati_table
    $note_cassa_economale
EOF;

//        echo $html;
        ob_end_clean();
        //$pdf->writeHTML($html);
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->SetXY(0, 282);
        //$pdf->cell(0,0,'Settore Stato giuridico, ordinamento e formazione del personale',0,0,'C',0,0,false,'B','C');
        $pdf->cell(0,0,$settore_aa,0,0,'C',0,0,false,'B','C');
    }//for
}elseif($tipo=="prospetto_costi"){
	
	$pdf->AddPage();
	
	$dati_html ="<table style=\"border:1px solid black;\"><tr>
            <td style=\"border:1px solid black; text-align:center; width:48%;\"><b>".get_string('dipendente', 'block_f2_formazione_individuale')."</b> (".get_string('cognome', 'block_f2_formazione_individuale')." e ".get_string('nome', 'block_f2_formazione_individuale').")</td>
            <td style=\"border:1px solid black; text-align:center; width:14%;\"><b>".get_string('spesa', 'block_f2_formazione_individuale')."</b> (".get_string('euro', 'block_f2_formazione_individuale').")</td>
            <td style=\"border:1px solid black; text-align:center; width:38%;\"><b>".get_string('ente_organizzatore', 'block_f2_formazione_individuale')."</b></td>
        </tr>";
	$totale_costi=0;
    foreach ($dati_pdf as $id_determina) {

        $data = new stdClass();
        $data->id_corso_ind = $id_determina;

        $dati_corso_ind = get_scheda_descrittiva_by_id($data);

        $dati_html .="<tr>
                        <td style=\"border:1px solid black;\">".utf8_decode(fullname($dati_corso_ind))."</td>
                        <td style=\"border:1px solid black; text-align:right;\"><b>".number_format($dati_corso_ind->costo,2,",",".")."</b></td>
                        <td style=\"border:1px solid black;\">".utf8_decode($dati_corso_ind->ente)."</td>
                     </tr>";
        $totale_costi = $totale_costi + $dati_corso_ind->costo;

    }
		
	$dati_html .="</table>";
	
	//$totale_prospetto_costi ="<table style=\"border:2px solid black;width:50%; padding:2px;\"><tr><td><b  style=\"font-size:45px;\">".get_string('totale_spesa_euro', 'block_f2_formazione_individuale')." ".number_format($totale_costi,2,",",".")."</b></td></tr></table>";
	$totale_prospetto_costi ="<table style=\"border:2px solid black;width:40%; padding:2px;\"><tr><td><b style=\"font-size:15px;\">".get_string('totale_spesa_euro', 'block_f2_formazione_individuale')." ".number_format($totale_costi,2,",",".")."</b></td></tr></table><br />";
	
	$scheda_riepilogativa_allegato_b = get_string('scheda_riepilogativa_allegato_b', 'block_f2_formazione_individuale');
	$determinazione_b ="Allegato B  determinazione n.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				&nbsp;&nbsp;&nbsp;&nbsp; del";
// a.a. agosto 2015
        // estraggo dalla tabella mdl_f2_parametri le descrizioni di Direzione e Settore
        $parametro_aa = get_parametro('p_f2_corsi_individuali_direzione');
        $direzione_aa = $parametro_aa->val_char;
        $parametro_aa = get_parametro('p_f2_corsi_individuali_settore');
        $settore_aa   = $parametro_aa->val_char;
        
	
	$html =
<<<EOF
	<style>
		p.header {
			text-align: center;
		}
		ul.body {
			text-align: left;
		}
		.table_prospetto_costi{
		border:1px solid black;
		}
		</style>
		<div align="center">$determinazione_b</div>
	<div align="center">
		<img src="pdf/regione_piemonte.png" alt="regione_piemonte" height="60" width="180"/>
                <p>$direzione_aa</p>
		<h2 align="center">$scheda_riepilogativa_allegato_b</h2>
	</div>
		$totale_prospetto_costi<br>$dati_html	
EOF;
	
	ob_end_clean();
	$pdf->writeHTML($html);
	$pdf->SetXY(0, 282);
        //$pdf->cell(0,0,'Settore Stato giuridico, ordinamento e formazione del personale',0,0,'C',0,0,false,'B','C');
        $pdf->cell(0,0,$settore_aa,0,0,'C',0,0,false,'B','C');
}
	$pdf->Output($tipo.'.pdf', 'D');
}
ob_flush();
?>
