<?php
//$Id$
ob_start();
require_once dirname(dirname(dirname(__FILE__))).'/config.php';
require_once $CFG->dirroot.'/mod/facetoface/lib.php';
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/f2_lib/management.php');
defined('MOODLE_INTERNAL') || die();
global $CFG,$DB;
//require_capability('block/reports:formazione', get_context_instance(CONTEXT_COURSE,$COURSE->id));

$post_values = required_param('post_values',PARAM_INT);
$post_values = str_replace("##dubleap##", "\"", $post_values);
$post_values = json_decode($post_values); //contiene l'id della sessione

$s = $post_values;
if (!$session = facetoface_get_session($s)) {
    print_error('error:incorrectcoursemodulesession', 'facetoface');
}
if (!$course = get_course_by_session($s)) {
    print_error('error:coursemisconfigured', 'facetoface');
}

$l_course = " ".get_string("course");

//$timenow = time();
$session_startdate = count($session->sessiondates) 
        ? date('d/m/Y', $session->sessiondates[0]->timestart)
        //? userdate($session->sessiondates[0]->timestart, get_string('strftimedate')) 
        : '';
require_once($CFG->dirroot.'/f2_lib/phpexcel177/PHPExcel.php');
// Create new PHPExcel object
//$objPHPExcel = new PHPExcel();
$objPHPExcel = PHPExcel_IOFactory::load($CFG->dirroot.'/f2_lib/phpexcel177/template/template_csi.xls');

// Set properties
$objPHPExcel->getProperties()->setCreator("CSI")
                             ->setLastModifiedBy("CSI")
                             ->setTitle("CSI")
                             ->setSubject("CSI")
                             ->setDescription("CSI")
                             ->setKeywords("CSI")
                             ->setCategory("CSI");

$objPHPExcel->setActiveSheetIndex(0)
            ->setCellValueByColumnAndRow(0,1,get_string('my_c_idnumber','local_f2_traduzioni').$l_course)
            ->setCellValueByColumnAndRow(1,1,get_string('titolo','local_f2_traduzioni').$l_course)
            ->setCellValueByColumnAndRow(2,1,'data edizione')
            ->setCellValueByColumnAndRow(3,1,'nominativo')
            ->setCellValueByColumnAndRow(4,1,'mail')
            ->setCellValueByColumnAndRow(5,1,'matricola')
            ->setCellValueByColumnAndRow(6,1,'qualifica')
            ->setCellValueByColumnAndRow(7,1,'direzione')
            ->setCellValueByColumnAndRow(8,1,'stato_iscrizione')
            ->setCellValueByColumnAndRow(9,1,'data archiviazione')
            ->setCellValueByColumnAndRow(10,1,'utente_modifica')
            ->setCellValueByColumnAndRow(11,1,'note')
            ;

	
	$attendees = facetoface_get_users_instatus($post_values, array(MDL_F2F_STATUS_BOOKED));
//	$full_fornitori=get_fornitori($post_values);

	$row=1;
	foreach($attendees->dati as $data_row){
		
        $row++;
        $col = 0;

        $user_changes = user_get_users_by_id(array($data_row->f2_user_changes));//Recupero l'utente che ha effettuato l'ultima modifica
        list($id, $fullname, $shortname) = get_user_organisation($data_row->id); //Recupero il dominio dell'utente
        $detail_user =user_get_users_by_id(array($data_row->id));//Recupero i dati dell'utente

        $qualifica = $DB->get_record_sql("SELECT 
                                                uid.data
                                        FROM
                                                {user_info_data} uid 
                                        WHERE
                                                uid.userid = {$data_row->id} AND	
                                                uid.fieldid = 1");
        
        $iscrizione = $DB->get_record_sql("SELECT 
											signups.f2_note as note
                                            FROM
											{facetoface_signups} signups
                                            WHERE
                                            signups.userid = {$data_row->id} AND	
											signups.sessionid = $post_values");

        $objPHPExcel->getActiveSheet()
		->setCellValueByColumnAndRow($col++	, $row, $course->idnumber)
		->setCellValueByColumnAndRow($col++	, $row, $course->fullname)
        ->setCellValueByColumnAndRow($col++	, $row, $session_startdate)
		->setCellValueByColumnAndRow($col++	, $row, fullname($data_row))
		->setCellValueByColumnAndRow($col++	, $row, $data_row->email)
		->setCellValueByColumnAndRow($col++ , $row, $detail_user[$data_row->id]->idnumber)
		->setCellValueByColumnAndRow($col++ , $row, $qualifica->data)
		->setCellValueByColumnAndRow($col++ , $row, $shortname." ".$fullname)
		->setCellValueByColumnAndRow($col++ , $row, str_replace(' ', '&nbsp;', get_string('status_'.facetoface_get_status($data_row->statuscode), 'facetoface')))
		->setCellValueByColumnAndRow($col++ , $row, date('d/m/Y H:i:s',$data_row->timecreated))
		->setCellValueByColumnAndRow($col++ , $row, $user_changes[$data_row->f2_user_changes]->firstname." ".$user_changes[$data_row->f2_user_changes]->lastname)
		//->setCellValueByColumnAndRow($col++ , $row, $data_row->f2_user_changes)
		->setCellValueByColumnAndRow($col++ , $row, $iscrizione->note);

	}
	//echo ($x==1) ? "One" : ( ($y==2) ? "Two" : "None" ); 
	$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
	//$objPHPExcel->getActiveSheet()->getStyle('K2:K'.$def_row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);
	// stampaorizzontale
	$objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
	
	// Rename sheet
	$objPHPExcel->getActiveSheet()->setTitle('Lista partecipanti');	
	
	$filename='lista_partecipanti'.date('d_m_Y').'.xls';	
	// Redirect output to a client's web browser (Excel2007)
    setcookie('fileDownload', 'true', 0, '/');
    header('Cache-Control: max-age=60, must-revalidate'); 
    header('Content-Type: application/vnd.ms-excel');
	//header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); EXCEL 2007
	header('Content-Disposition: attachment;filename="'.$filename.'');

	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	ob_end_clean();
	$objWriter->save('php://output');

?>
