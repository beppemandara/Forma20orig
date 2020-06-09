<?php
/*
 * $Id: excel_budget_totali.php 1241 2013-12-20 04:34:05Z l.moretto $
 */
ob_start();
require_once '../../../config.php';
require_once '../lib.php';
defined('MOODLE_INTERNAL') || die();
global $CFG,$DB;

$context = get_context_instance(CONTEXT_SYSTEM);

$capability = has_capability('block/f2_gestione_risorse:budget_edit', $context);
if(!$capability){
	print_error('nopermissions', 'error', '', 'budget');
}

//require_capability('block/reports:formazione', get_context_instance(CONTEXT_COURSE,$COURSE->id));

$post_values = required_param('post_values',PARAM_TEXT);
$post_values = str_replace("##dubleap##", "\"", $post_values);
$post_values = json_decode($post_values);

$post_full_param = required_param('post_full_param',PARAM_TEXT);
$post_full_param = str_replace("##dubleap##", "\"", $post_full_param);
$post_full_param = json_decode($post_full_param);

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
            ->setCellValueByColumnAndRow(0,1,'Direzione')
            ->setCellValueByColumnAndRow(1,1,'Posti Aula')
            ->setCellValueByColumnAndRow(2,1,'Bonus')
            ->setCellValueByColumnAndRow(3,1,'Obiettivo')
            ->setCellValueByColumnAndRow(4,1,'Individuale')
            ->setCellValueByColumnAndRow(5,1,'Lingue')
            ->setCellValueByColumnAndRow(6,1,'E-Learning')
            ->setCellValueByColumnAndRow(7,1,'Aula')
            ->setCellValueByColumnAndRow(8,1,'Giorni/Crediti Aula')
            ->setCellValueByColumnAndRow(9,1,'TOTALE')
            ;


$post_values->page=0;
$post_values->perpage=0;
//	$full_fornitori=get_fornitori($post_values);

$row=1;
foreach($post_full_param->dati as $data_row){

    $row++;
    $col = 0;

    if($org = $DB->get_record('org', array('id'=>$data_row->direzione), 'id,fullname,shortname'))
        $orgname = "{$org->shortname} - {$org->fullname}";
    else
        $orgname = 'n.d.';
    $objPHPExcel->getActiveSheet()
        ->setCellValueByColumnAndRow($col++	, $row, $orgname)
        ->setCellValueByColumnAndRow($col++ , $row, round($data_row->posti_aula,2))
        ->setCellValueByColumnAndRow($col++ , $row, round($data_row->bonus,2))
        ->setCellValueByColumnAndRow($col++ , $row, round($data_row->obiettivo,2))
        ->setCellValueByColumnAndRow($col++ , $row, round($data_row->individuale,2))
        ->setCellValueByColumnAndRow($col++ , $row, round($data_row->lingue,2))
        ->setCellValueByColumnAndRow($col++ , $row, round($data_row->e_learning,2))
        ->setCellValueByColumnAndRow($col++ , $row, round($data_row->aula,2))
        ->setCellValueByColumnAndRow($col++ , $row, round($data_row->giorni_crediti_aula,2))
        ->setCellValueByColumnAndRow($col++ , $row, round($data_row->totale,2));
}

$row++;
$col = 0;

//INIZIO ULTIMA RIGA DEI TOTALI
$objPHPExcel->getActiveSheet()
->setCellValueByColumnAndRow($col++	, $row, 'TOTALE:')
->setCellValueByColumnAndRow($col++ , $row, round($post_full_param->totali->tot_posti_aula,2))
->setCellValueByColumnAndRow($col++ , $row, round($post_full_param->totali->tot_bonus,2))
->setCellValueByColumnAndRow($col++ , $row, round($post_full_param->totali->tot_obiettivo,2))
->setCellValueByColumnAndRow($col++ , $row, round($post_full_param->totali->tot_individuale,2))
->setCellValueByColumnAndRow($col++ , $row, round($post_full_param->totali->tot_lingue,2))
->setCellValueByColumnAndRow($col++ , $row, round($post_full_param->totali->tot_e_learning,2))
->setCellValueByColumnAndRow($col++ , $row, round($post_full_param->totali->tot_aula,2))
->setCellValueByColumnAndRow($col++ , $row, round($post_full_param->totali->tot_giorni_crediti_aula,2))
->setCellValueByColumnAndRow($col++ , $row, round($post_full_param->totali->tot_totale,2));
//FINE ULTIMA RIGA DEI TOTALI


//echo ($x==1) ? "One" : ( ($y==2) ? "Two" : "None" ); 
$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
//$objPHPExcel->getActiveSheet()->getStyle('K2:K'.$def_row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);
// stampaorizzontale
$objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);

// Rename sheet
$objPHPExcel->getActiveSheet()->setTitle('Lista fornitori');	

$filename='budget totale'.date('d_m_Y').'.xls';	
// Redirect output to a client's web browser (Excel2007)
setcookie('fileDownload', 'true', 0, '/');
header('Cache-Control: max-age=60, must-revalidate'); 
header('Content-Type: application/vnd.ms-excel');
//header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); EXCEL 2007
header('Content-Disposition: attachment;filename="'.$filename.'');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
ob_end_clean();
$objWriter->save('php://output');