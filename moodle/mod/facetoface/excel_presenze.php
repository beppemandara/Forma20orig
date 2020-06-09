<?php
ob_start();
require_once '../../config.php';

defined('MOODLE_INTERNAL') || die();
 
global $CFG, $DB;

require_once($CFG->dirroot.'/mod/facetoface/lib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/f2_lib/management.php');

$post_values = required_param('post_values',PARAM_INT);
$post_values = str_replace("##dubleap##", "\"", $post_values);
$post_values = json_decode($post_values); //contiene l'id della sessione
$course = get_course_by_session($post_values);

$timenow = time();
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
			->setCellValueByColumnAndRow(0,1,get_string('my_c_idnumber','local_f2_traduzioni')." corso")
			->setCellValueByColumnAndRow(1,1,get_string('titolo','local_f2_traduzioni')." corso")
			->setCellValueByColumnAndRow(2,1,get_string('nome','local_f2_traduzioni'))
			->setCellValueByColumnAndRow(3,1,get_string('matricola','local_f2_traduzioni'))
			->setCellValueByColumnAndRow(4,1,get_string('qualifica','local_f2_traduzioni'))
			->setCellValueByColumnAndRow(5,1,get_string('dominio','local_f2_traduzioni'))
			->setCellValueByColumnAndRow(6,1,get_string('stato_iscrizione','local_f2_traduzioni'))
			->setCellValueByColumnAndRow(7,1,get_string('data_modifica','local_f2_traduzioni'))
			->setCellValueByColumnAndRow(8,1,get_string('presenza','local_f2_traduzioni'))
			->setCellValueByColumnAndRow(9,1,get_string('verifica_app','local_f2_traduzioni'))
			->setCellValueByColumnAndRow(10,1,get_string('stores','facetoface'));

	
$attendees = facetoface_get_users_instatus($post_values, array(MDL_F2F_STATUS_BOOKED, MDL_F2F_STATUS_NO_SHOW, MDL_F2F_STATUS_FULLY_ATTENDED));

$row=1;
foreach($attendees->dati as $data_row) {
	$row++;
	$col = 0;

	list($id, $fullname, $shortname) = get_user_organisation($data_row->id); //Recupero il dominio dell'utente
	$detail_user = user_get_users_by_id(array($data_row->id));//Recupero i dati dell'utente
	
	$qualifica = $DB->get_record_sql("
						SELECT 
							uid.data
						FROM
							{user_info_data} uid 
						WHERE
							uid.userid = ".$data_row->id." AND	
							uid.fieldid = 1");
		
	$va = $DB->get_field('f2_va', 'descrizione', array('id' => $data_row->va));
	if (!$va) $va = "";
	
	$objPHPExcel->getActiveSheet()
		->setCellValueByColumnAndRow($col++	, $row, $course->idnumber)
		->setCellValueByColumnAndRow($col++	, $row, $course->fullname)
		->setCellValueByColumnAndRow($col++	, $row, $data_row->lastname." ".$data_row->firstname)
		->setCellValueByColumnAndRow($col++ , $row, $detail_user[$data_row->id]->idnumber)
		->setCellValueByColumnAndRow($col++ , $row, $qualifica->data)
		->setCellValueByColumnAndRow($col++ , $row, $shortname." ".$fullname)
		->setCellValueByColumnAndRow($col++ , $row, str_replace(' ', '&nbsp;', get_string('status_'.facetoface_get_status($data_row->statuscode), 'facetoface')))
		->setCellValueByColumnAndRow($col++ , $row, date('d/m/Y H:i:s',$data_row->timecreated))
		->setCellValueByColumnAndRow($col++ , $row, $data_row->presenza)
		->setCellValueByColumnAndRow($col++ , $row, $va);
	
	if ($data_row->stores == 1) 
		$objPHPExcel->getActiveSheet()
		->setCellValueByColumnAndRow($col++, $row, "Archiviato");
	else
		$objPHPExcel->getActiveSheet()
		->setCellValueByColumnAndRow($col++, $row, "");
}

$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
// Stampa orizzontale
$objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
	
// Rename sheet
$objPHPExcel->getActiveSheet()->setTitle('Consuntivo presenze');	
	
$filename='consuntivo_presenze'.date('d_m_Y').'.xls';	

// Redirect output to a client's web browser (Excel2007)
setcookie('fileDownload', 'true', 0, '/');
header('Cache-Control: max-age=60, must-revalidate'); 
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="'.$filename.'');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
ob_end_clean();
$objWriter->save('php://output');

?>