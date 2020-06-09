<?php
// $id$
ob_start();
require_once '../../config.php';
require_once 'lib.php';
defined('MOODLE_INTERNAL') || die();
global $CFG,$DB;

//require_capability('block/reports:formazione', get_context_instance(CONTEXT_COURSE,$COURSE->id));

$post_values = required_param('post_values',PARAM_TEXT);
$post_values = str_replace("##dubleap##", "\"", $post_values);
$post_values = json_decode($post_values);


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
				->setCellValueByColumnAndRow(0,1,'Nome')
				->setCellValueByColumnAndRow(1,1,'Tipo')
				->setCellValueByColumnAndRow(2,1,'Canale')
				->setCellValueByColumnAndRow(3,1,'Stato')
                ->setCellValueByColumnAndRow(4,1,'Default')
				;


	$post_values->page=0;
	$post_values->perpage=0;
	$full_templates=get_templates($post_values);
	//print_r($full_templates);exit;
	$row=1;
	foreach($full_templates->dati as $data_row){
		
	$row++;
	$col = 0;
	foreach(get_tipo_notif($data_row->id_tipo_notif) as $nome){$tipo_notif = $nome->nome; break;};
	$objPHPExcel->getActiveSheet()
		->setCellValueByColumnAndRow($col++	, $row, $data_row->title)
		->setCellValueByColumnAndRow($col++ , $row, $tipo_notif)
		->setCellValueByColumnAndRow($col++ , $row,($data_row->canale) ? 'On-line' : 'Aula')
		->setCellValueByColumnAndRow($col++ , $row,($data_row->stato) ? 'Attivo' : 'Non attivo')
        ->setCellValueByColumnAndRow($col++ , $row,($data_row->predefinito) ? 'Sì' : 'No')
        ;
	}
	//echo ($x==1) ? "One" : ( ($y==2) ? "Two" : "None" ); 
	$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
	//$objPHPExcel->getActiveSheet()->getStyle('K2:K'.$def_row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);
	// stampaorizzontale
	$objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
	
	// Rename sheet
	$objPHPExcel->getActiveSheet()->setTitle('Lista notifiche');	
	
	$filename='notifiche_'.date('d_m_Y').'.xls';	
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