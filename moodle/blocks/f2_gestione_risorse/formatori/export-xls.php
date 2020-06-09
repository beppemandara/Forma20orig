<?php 

// $Id: export-xls.php 785 2012-11-30 13:55:47Z g.nuzzolo $

ob_start();
require_once '../../../config.php';
global $DB,$CFG;
require_once($CFG->dirroot.'/f2_lib/phpexcel177/PHPExcel.php');
require_once '../lib.php';

// $sort = optional_param('sort', '', PARAM_ACTION);
// $direction = optional_param('dir', 'ASC', PARAM_ACTION);
// $cognome = optional_param('cogn', '', PARAM_ACTION);
// $categoria = optional_param('cat', '-1', PARAM_ACTION);

$post_values = required_param('post_values',PARAM_TEXT);
$post_values = str_replace("##dubleap##", "\"", $post_values);
$post_values = json_decode($post_values);

require_login();
require_capability('block/f2_gestione_risorse:vedi_lista_utenti', get_context_instance(CONTEXT_SYSTEM));
require_capability('block/f2_gestione_risorse:vedi_lista_formatori', get_context_instance(CONTEXT_SYSTEM));

if (true
	)
{
	//prendi tutti i record
	$post_values->page=0;
	$post_values->perpage=0;

	$users = get_formatoriRS($post_values);

	// Create new PHPExcel object
// 	$objPHPExcel = new PHPExcel();
	$objPHPExcel = PHPExcel_IOFactory::load($CFG->dirroot.'/f2_lib/phpexcel177/template/template_csi.xls');

	// Set properties
	$objPHPExcel->getProperties()->setCreator("CSI")
								 ->setLastModifiedBy("CSI")
								 ->setTitle("CSI")
								 ->setSubject("CSI")
								 ->setDescription("CSI")
								 ->setKeywords("CSI")
								 ->setCategory("CSI");
	// Set columns
	$objPHPExcel->setActiveSheetIndex(0)
			->setCellValueByColumnAndRow(0,1,get_string('lastname', 'local_f2_traduzioni'))
			->setCellValueByColumnAndRow(1,1,get_string('firstname', 'local_f2_traduzioni'))
			->setCellValueByColumnAndRow(2,1,get_string('cf', 'local_f2_traduzioni'))
			->setCellValueByColumnAndRow(3,1,get_string('domain', 'local_f2_traduzioni'));
			
	$column_max_length = array('lastname'=>mb_strlen(get_string('lastname','local_f2_traduzioni')),
								'firstname'=>mb_strlen(get_string('firstname','local_f2_traduzioni')),
								'cf'=>mb_strlen(get_string('cf','local_f2_traduzioni')),
								'domain'=>mb_strlen(get_string('domain','local_f2_traduzioni')),
								);

	$row_num = 1;		
	foreach($users->dati as $data_row){
		$row_num++;
		$col_num = 0;

		$objPHPExcel->getActiveSheet()
			->setCellValueByColumnAndRow($col_num++	, $row_num, $data_row->lastname)
			->setCellValueByColumnAndRow($col_num++ , $row_num, $data_row->firstname)
			->setCellValueByColumnAndRow($col_num++ , $row_num, $data_row->cf)
			->setCellValueByColumnAndRow($col_num++ , $row_num, $data_row->domain)
			// ->setCellValueByColumnAndRow($col_num++ , $row_num, $data_row->telefono)
			// ->setCellValueByColumnAndRow($col_num++ , $row_num, $data_row->fax)
			// ->setCellValueByColumnAndRow($col_num++ , $row_num, $data_row->abilitata == 0 ? "NO" : "SI")
			// ->setCellValueByColumnAndRow($col_num++ , $row_num, $data_row->empty($data_row->data_inserimento) ? "" : date('d/m/Y',$data_row->data_inserimento))
			;
		if (mb_strlen($data_row->lastname) > $column_max_length['lastname']) $column_max_length['lastname'] = mb_strlen($data_row->lastname);
		if (mb_strlen($data_row->firstname) > $column_max_length['firstname']) $column_max_length['firstname'] = mb_strlen($data_row->firstname);
		if (mb_strlen($data_row->cf) > $column_max_length['cf']) $column_max_length['cf'] = mb_strlen($data_row->cf);
		if (mb_strlen($data_row->domain) > $column_max_length['domain']) $column_max_length['domain'] = mb_strlen($data_row->domain);
		}

	$constant_adjust = 1.3;
	$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(((int) $column_max_length['lastname']) * $constant_adjust);
	$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(((int) $column_max_length['firstname']) * $constant_adjust);
	$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(((int) $column_max_length['cf']) * $constant_adjust);
	$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(((int) $column_max_length['domain']) * $constant_adjust);
		
	$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
	// stampaorizzontale
	$objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);

	// Rename sheet
	$objPHPExcel->getActiveSheet()->setTitle('Formatori');	

	$filename='formatori_'.date('d_m_Y').'.xls';	
	// Redirect output to a client's web browser (Excel5)
	setcookie('fileDownload', 'true', 0, '/');

	header('Cache-Control: max-age=60, must-revalidate'); 
// 	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Type: application/vnd.ms-excel');
	header('Content-Disposition: attachment;filename="'.$filename.'');

	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	ob_end_clean();
	$objWriter->save('php://output');
}
else 
{
	die;
}
?>