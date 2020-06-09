<?php
/*
 * $Id$
 */
ob_start();
require_once '../../config.php';
require_once 'lib.php';
require_once($CFG->dirroot.'/local/f2_notif/lib.php');
require_once($CFG->dirroot.'/f2_lib/core.php');
require_once($CFG->dirroot.'/lib/tcpdf/tcpdf.php');


if(isset($_POST['submit_invio_autorizzazioni'])){
	if(isset($_POST['chk_id_determina'])){
		
		$dati_invio = array();
		$id_corsi_ind=$_POST['chk_id_determina'];
		
		foreach($id_corsi_ind as $id_corso){
			$dati_invio[] = createPDF($id_corso);
		}
		$str_javascript= "<table width='100%'><tr><td align=left  valign=top  class='clsBold' ><b>Utente</b></td><td align=left  valign=top  class='clsBold' ><b>Matricola</b></td><td align=left  valign=top  class='clsBold' ><b>E-mail</b></td><td align=left  valign=top  class='clsBold' ><b>Data invio</b></td><td align=left  valign=top  class='clsBold' ><b>Esito invio</b></td></tr>";
		foreach($dati_invio as $dati){
			if($dati->error_mail  == 1){
				$esito = 'Errore email non inviata';
			}else if($dati->error_mail  == 2){
				$esito = 'Errore formato email non corretto';
			}else{
				$esito = 'Inviata';
			}
			
			$str_javascript .= "<tr>";
			$str_javascript .= "<td>".$dati->lastname." ".$dati->firstname."</td><td>$dati->matricola</td><td>$dati->mailto</td><td>".date('d/m/Y H:i',time())."</td><td>".$esito."</td>";
			$str_javascript .= "</tr>";
		}
		$str_javascript.= "</table>";
		
		echo '<input type="hidden" name="dati_invio" id="dati_invio" value="'.$str_javascript.'"/>';
		
		$str1 = <<<'EFO'
<script type="text/javascript">
		
//<![CDATA[

myWindow=window.open('','','width=1000,height=600,scrollbars=yes');
		
var dati_invio = document.getElementById("dati_invio").value;
myWindow.document.write("<h3>Invio email di autorizzazione</h3><table width='100%'><tr><td align='right'><input  align='right' type='button' height='55' title='Stampa questa pagina' value='Stampa' onclick='window.print()'/></td></tr></table>");
				
myWindow.document.write(dati_invio);
		

myWindow.focus();
//]]>
</script>
EFO;
		echo $str1;
	}
	ob_flush();
	redirect(new moodle_url('/blocks/f2_formazione_individuale/invio_autorizzazioni.php?training='.$_POST['training']));
}



function createPDF($id_corso_ind) {
    global $CFG;

    //Dati compilazione email-pdf
    $data = new stdClass();
    $data->id_corso_ind = $id_corso_ind;

    $dati_corso_ind = get_scheda_descrittiva_by_id($data);
    $dati_corso_ind_forz = get_forzatura_or_moodleuser($dati_corso_ind->username);
    $dati_determina = get_determina_provvisoria($dati_corso_ind->id_determine);
    $id_notifica = $dati_corso_ind->modello_email;
    $id_tipo_notif = 3; //tipo notifica modello individuale
    $file_name="";
    $attachments_files = "";
    $if_attachment = if_notif_attachment($id_notifica);
    $flag_cassaeconomale = $dati_corso_ind->cassa_economale;

    //Controllo se deve essere creato il pdf
    if($if_attachment->attachment){

        //INIZIO: SEGNAPOSTO
        $note_cassa_economale ="";
        if($flag_cassaeconomale){
                $cassa_economale = get_parametro('p_f2_corsiind_nota_cassa_economale');
                $note_cassa_economale = $cassa_economale->val_char;
        }
        //AK-DC Aggiunta variabile settore
        $replace= array($dati_corso_ind->lastname,$dati_corso_ind->firstname,$dati_corso_ind_forz->category,$dati_corso_ind_forz->direzione,$dati_corso_ind_forz->settore,$dati_corso_ind->titolo,$dati_corso_ind->localita,number_format($dati_corso_ind->durata,2,",","."),date("d/m/Y",$dati_corso_ind->data_inizio),$dati_corso_ind->ente,number_format($dati_corso_ind->costo,2,",","."),$dati_corso_ind->beneficiario_pagamento,$dati_corso_ind->partita_iva,$dati_corso_ind->codice_fiscale,$dati_corso_ind->codice_creditore,$dati_corso_ind->note,$note_cassa_economale,$dati_corso_ind_forz->idnumber,$dati_determina->codice_determina,date("d/m/Y",$dati_determina->data_determina),$dati_corso_ind_forz->cod_direzione,$dati_corso_ind_forz->cod_settore);
        $find = array("[Cognome]","[Nome]","[Qualifica]","[Direzione]","[Settore]","[NomeCorso]","[Localita]","[Durata]","[Data]","[Ente]","[Costo]","[Beneficiario]","[PartitaIva]","[CodiceFiscale]","[CodiceCreditore]","[Note]","[CassaEconomale]","[Matricola]","[Determina]","[DataDetermina]","[cod_direzione]","[cod_settore]");
        //INIZIO: SEGNAPOSTO


        $pdf = new TCPDF('P', 'mm', 'A4', false, 'ISO-8859-1', false);
        $pdf->SetCreator('Regione Piemonte');
        $pdf->SetAuthor('CSI Piemonte');
        $pdf->SetTitle(get_string('fogliop','local_f2_traduzioni'));
        $pdf->SetSubject(get_string('fogliop','local_f2_traduzioni'));
        $pdf->SetKeywords('PDF '.get_string('fogliop','local_f2_traduzioni').', PDF');

        // Remove default header/footer
        $pdf->setPrintHeader(FALSE);
        $pdf->setPrintFooter(FALSE);
        $pdf->SetDefaultMonospacedFont('courier');
        //Set margin
        $pdf->SetMargins('30', '15', '30');
        $pdf->SetAutoPageBreak(TRUE, '15');
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetFont('helvetica', '', 12);

        //Dati pdf
        $settore ="";
        $cod_settore="";
        if(isset($dati_corso_ind_forz->settore)){
            $settore = $dati_corso_ind_forz->settore;
            $cod_settore = $dati_corso_ind_forz->cod_settore;
        }

        $pdf->AddPage();
        $allegato_htm=file_get_contents($CFG->dataroot.'/f2_allegati/body_autocertificazione_partecipazione.htm',false);
        $header_pdf = file_get_contents($CFG->dataroot.'/f2_allegati/header_autocertificazione_partecipazione.htm',false);
        $footer_pdf = file_get_contents($CFG->dataroot.'/f2_allegati/footer_autocertificazione_partecipazione.htm',false);
        $body_pdf = str_replace($find,$replace,$allegato_htm);
        $html = $header_pdf.$body_pdf;


        ob_end_clean();
        $pdf->writeHTML(utf8_decode($html));	
        $pdf->SetXY(45, 280);
        $pdf->cell(0,0,utf8_decode($footer_pdf),0,0,'C',0,0,false,'B','C');

        //Creo la directory f2_attachments
        $path = mkdir($CFG->dataroot.'/f2_attachments', 0770);
        $file_name = uniqid($CFG->dataroot.'/f2_attachments/Autocertificazione_partecipazione_');
        $file_name = $file_name.'.pdf';
        $pdf->Output($file_name, 'F');		
        $attachments_files = $file_name;
    }	

    //Se il tipo di notifica è "Formazione Individuale Mail con spesa" 
    //  ed è richiesto l'anticipo cassa economale, allora devono essere inviati anche gli allegati "cassa1" e "cassa2".
    //AK-LM: CR- 2016 [Analisi_Formazione_individuale*.doc - 1.2	Invio condizionato degli allegati email] 
    if($id_notifica == ID_NOTIF_MAIL_SPESA && $flag_cassaeconomale) {

        //-------------------------------------------------------------------------------------------------------------//
        //SECONDO PDF CASSA_1
        $pdf = new TCPDF('P', 'mm', 'A4', false, 'ISO-8859-1', false);
        $pdf->SetCreator('Regione Piemonte');
        $pdf->SetAuthor('CSI Piemonte');
        $pdf->SetTitle(get_string('fogliop','local_f2_traduzioni'));
        $pdf->SetSubject(get_string('fogliop','local_f2_traduzioni'));
        $pdf->SetKeywords('PDF '.get_string('fogliop','local_f2_traduzioni').', PDF');

        // Remove default header/footer
        $pdf->setPrintHeader(FALSE);
        $pdf->setPrintFooter(FALSE);
        $pdf->SetDefaultMonospacedFont('courier');
        //Set margin
        $pdf->SetMargins('20', '15', '20');
        $pdf->SetAutoPageBreak(TRUE, '15');
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetFont('helvetica', '', 9);

        $pdf->AddPage();

        $allegato_htm=file_get_contents($CFG->dataroot.'/f2_allegati/body_cassa_1.htm',false);
        $determinazione = get_string('pdf_allegato_a_cassa_1','block_f2_formazione_individuale');
        $header_pdf = file_get_contents($CFG->dataroot.'/f2_allegati/header_cassa_1.htm',false);
        $footer_pdf = file_get_contents($CFG->dataroot.'/f2_allegati/footer_cassa_1.htm',false);
        $body_pdf = str_replace($find,$replace,$allegato_htm);
        $html = $header_pdf.$body_pdf;
        ob_end_clean();

        $pdf->writeHTML(utf8_decode($html));
        $pdf->SetXY(15, 280);//FOOTER 
        $pdf->cell(0,0,utf8_decode($footer_pdf),0,0,'C',0,0,false,'B','C');//FOOTER

        //Creo la directory f2_attachments
        $path = mkdir($CFG->dataroot.'/f2_attachments', 0770);
        $file_name = uniqid($CFG->dataroot.'/f2_attachments/cassa1_');
        $file_name = $file_name.'.pdf';
        $pdf->Output($file_name, 'F');
        $attachments_files = $attachments_files.';'.$file_name;

        //-------------------------------------------------------------------------------------------------------------//
        //TERZO PDF CASSA_2
        $pdf = new TCPDF('P', 'mm', 'A4', false, 'ISO-8859-1', false);
        $pdf->SetCreator('Regione Piemonte');
        $pdf->SetAuthor('CSI Piemonte');
        $pdf->SetTitle(get_string('fogliop','local_f2_traduzioni'));
        $pdf->SetSubject(get_string('fogliop','local_f2_traduzioni'));
        $pdf->SetKeywords('PDF '.get_string('fogliop','local_f2_traduzioni').', PDF');

        // Remove default header/footer
        $pdf->setPrintHeader(FALSE);
        $pdf->setPrintFooter(FALSE);
        $pdf->SetDefaultMonospacedFont('courier');
        //Set margin
        $pdf->SetMargins('20', '15', '20');
        $pdf->SetAutoPageBreak(TRUE, '15');
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetFont('helvetica', '', 9);

        $pdf->AddPage();

        $allegato_htm=file_get_contents($CFG->dataroot.'/f2_allegati/body_cassa_2.htm',false);
        $body_pdf = str_replace($find,$replace,$allegato_htm);
        $header_pdf = file_get_contents($CFG->dataroot.'/f2_allegati/header_cassa_2.htm',false);
        $footer_pdf = file_get_contents($CFG->dataroot.'/f2_allegati/footer_cassa_2.htm',false);

        $html = $header_pdf.$body_pdf;
        ob_end_clean();

        $pdf->writeHTML(utf8_decode($html));
        $pdf->SetXY(15, 280);//FOOTER
        $pdf->cell(0,0,utf8_decode($footer_pdf),0,0,'C',0,0,false,'B','C');//FOOTER

        //Creo la directory f2_attachments
        $path = mkdir($CFG->dataroot.'/f2_attachments', 0770);
        $file_name = uniqid($CFG->dataroot.'/f2_attachments/cassa_2');
        $file_name = $file_name.'.pdf';
        $pdf->Output($file_name, 'F');

        $attachments_files = $attachments_files.';'.$file_name;
    }

    //$dati_corso_ind_forz->email='kgkhjhf._@dsaf@-.it';
    $regex = '/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,3})$/';
    // Se il formato mail è valido
    if (preg_match($regex, $dati_corso_ind_forz->email)) {

        ///////////////////////////////////////////if Email stop//////
        $data = new stdClass();
        $data->lastname=$dati_corso_ind->lastname;
        $data->firstname=$dati_corso_ind->firstname;
        $data->localita=$dati_corso_ind->localita;
        $data->data=$dati_corso_ind->data_inizio;
        $data->ente=$dati_corso_ind->ente;
        $data->codice_determina=$dati_determina->codice_determina;
        $data->data_determina=$dati_determina->data_determina;
        $data->direzione=$dati_corso_ind_forz->direzione;
        $data->cod_direzione=$dati_corso_ind_forz->cod_direzione;
        $data->titolo=$dati_corso_ind->titolo;
        $data->durata=$dati_corso_ind->durata;
        $data->numero_protocollo=$dati_determina->numero_protocollo;
        $data->data_protocollo=$dati_determina->data_protocollo;
        $data->id_tipo_notif = $id_tipo_notif;
        $data->id_notifica = $id_notifica;
        $data->userid = $dati_corso_ind_forz->id;
        $data->mailto = $dati_corso_ind_forz->email;
        $data->attachments = $attachments_files;
        $data->id_course_ind = $dati_corso_ind->id_course;
        $data->matricola = $dati_corso_ind_forz->idnumber;

        //error_mail
        if(send_mail_autorizzazione($data)) {
            return $data;
        } else {
            $data->error_mail = 1;
            return $data;
        }

    } else {
        $data = new stdClass();
        $data->lastname=$dati_corso_ind->lastname;
        $data->firstname=$dati_corso_ind->firstname;
        $data->error_mail = 2;

        return $data;
    }
}

ob_flush();
?>
