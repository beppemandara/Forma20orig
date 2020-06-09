<?php
ob_start();
require_once '../../../config.php';
require_once '../lib.php';
defined('MOODLE_INTERNAL') || die();
global $CFG,$DB;

//require_capability('block/reports:formazione', get_context_instance(CONTEXT_COURSE,$COURSE->id));

$post_values = required_param('post_values',PARAM_TEXT);
$post_values = str_replace("##dubleap##", "\"", $post_values);
$post_values = json_decode($post_values);

$context = get_context_instance(CONTEXT_SYSTEM);

$capability = has_capability('block/f2_gestione_risorse:add_fornitori', $context);
if(!$capability){
	print_error('nopermissions', 'error', '', 'fornitori');
}

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
				->setCellValueByColumnAndRow(0,1,'denominazione')
				->setCellValueByColumnAndRow(1,1,'citta')
				->setCellValueByColumnAndRow(2,1,'provincia')
				->setCellValueByColumnAndRow(3,1,'cognome')
				->setCellValueByColumnAndRow(4,1,'nome')
				->setCellValueByColumnAndRow(5,1,'email')
				->setCellValueByColumnAndRow(6,1,'telefono')
				->setCellValueByColumnAndRow(7,1,'tipo_formazione')
				;


	$post_values->page=0;
	$post_values->perpage=0;
	$full_fornitori=get_fornitori($post_values);
	
	$row=1;
	foreach($full_fornitori->dati as $data_row){
		
	$row++;
	$col = 0;

	$objPHPExcel->getActiveSheet()
		->setCellValueByColumnAndRow($col++	, $row, $data_row->denominazione)
		->setCellValueByColumnAndRow($col++ , $row, $data_row->citta)
		->setCellValueByColumnAndRow($col++ , $row, $data_row->provincia)
		->setCellValueByColumnAndRow($col++ , $row, $data_row->cognome)
		->setCellValueByColumnAndRow($col++ , $row, $data_row->nome)
		->setCellValueByColumnAndRow($col++ , $row, $data_row->email);
       
        $objPHPExcel->getActiveSheet()
		->setCellValueExplicitByColumnAndRow($col++ , $row, $data_row->telefono, PHPExcel_Cell_DataType::TYPE_STRING);
       
        $objPHPExcel->getActiveSheet()
		->setCellValueByColumnAndRow($col++ , $row, get_tipo_formazione_fornitore($data_row->tipo_formazione));
	}
	//echo ($x==1) ? "One" : ( ($y==2) ? "Two" : "None" ); 
	$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
	//$objPHPExcel->getActiveSheet()->getStyle('K2:K'.$def_row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);
	// stampaorizzontale
	$objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
	
	// Rename sheet
	$objPHPExcel->getActiveSheet()->setTitle('Lista fornitori');	
	
	$filename='lista_fornitori'.date('d_m_Y').'.xls';	
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