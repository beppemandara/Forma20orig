<?php 

// $Id: export-xls.php 477 2012-10-16 12:49:10Z g.nuzzolo $

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
// print_r($post_values);
// exit;

$context = get_context_instance(CONTEXT_SYSTEM);
require_login();
require_capability('block/f2_gestione_risorse:send_auth_mail', $context);

if (true
	)
{
	//prendi tutti i record
	$post_values->page=0;
	$post_values->perpage=0;

	$edizioni_all = get_dati_tabella_auth_mail((array)$post_values,1); // 1 = no limit
	$edizioni = $edizioni_all->dati;
	$total_rows = $edizioni_all->count;
	
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
// 	$head_table = array('chk_all_auth_mail_edizioni','titolo','codcorso','anno','num_sessione','data_inizio','sirp','sirpdata','data_ora_invio','inviata');
	$objPHPExcel->setActiveSheetIndex(0)
			->setCellValueByColumnAndRow(0,1,get_string('titolo', 'local_f2_traduzioni'))
			->setCellValueByColumnAndRow(1,1,get_string('codcorso', 'local_f2_traduzioni'))
			->setCellValueByColumnAndRow(2,1,get_string('anno', 'local_f2_traduzioni'))
			->setCellValueByColumnAndRow(3,1,get_string('num_sessione', 'local_f2_traduzioni'))
			->setCellValueByColumnAndRow(4,1,get_string('data_inizio', 'local_f2_traduzioni'))
			->setCellValueByColumnAndRow(5,1,get_string('sirp', 'local_f2_traduzioni'))
			->setCellValueByColumnAndRow(6,1,get_string('sirpdata', 'local_f2_traduzioni'))
			->setCellValueByColumnAndRow(7,1,get_string('data_ora_invio', 'local_f2_traduzioni'));
			
	$column_max_length = array('corso'=>mb_strlen(get_string('corso','local_f2_traduzioni')),
								'codice_corso'=>mb_strlen(get_string('codcorso','local_f2_traduzioni')),
								'anno'=>mb_strlen(get_string('anno','local_f2_traduzioni')),
								'num_sessione'=>mb_strlen(get_string('num_sessione','local_f2_traduzioni')),
								'data_inizio'=>mb_strlen(get_string('data_inizio','local_f2_traduzioni')),
								'sirp'=>mb_strlen(get_string('sirp','local_f2_traduzioni')),
								'sirpdata'=>mb_strlen(get_string('sirpdata','local_f2_traduzioni')),
								'data_ora_invio'=>mb_strlen(get_string('data_ora_invio','local_f2_traduzioni')),
								);

	$row_num = 1;		
	foreach($edizioni as $data_row){
		$row_num++;
		$col_num = 0;
		$row_data_inizio= '';
		$row_data_ora_invio = '';
		$row_sirpdata = '';
		$row_data_ora_invio_ts = '';
		$row_data_ora_invio_ts = get_maxdata_auth_mail_inviate($data_row->edizione_id);
		if (!is_null($data_row->data_inizio)) $row_data_inizio = date('d/m/Y',$data_row->data_inizio);
		if (!is_null($data_row->sirpdata)) $row_sirpdata = date('d/m/Y',$data_row->sirpdata);
		if ($row_data_ora_invio_ts !== '') $row_data_ora_invio = date('d/m/Y H:i:s',$row_data_ora_invio_ts);
		
		$objPHPExcel->getActiveSheet()
			->setCellValueByColumnAndRow($col_num++	, $row_num, $data_row->titolo)
			->setCellValueByColumnAndRow($col_num++ , $row_num, $data_row->codice_corso)
			->setCellValueByColumnAndRow($col_num++ , $row_num, $data_row->anno)
			->setCellValueByColumnAndRow($col_num++ , $row_num, $data_row->num_sessione)
			->setCellValueByColumnAndRow($col_num++ , $row_num, $row_data_inizio)
			->setCellValueByColumnAndRow($col_num++ , $row_num, $data_row->sirp)
			->setCellValueByColumnAndRow($col_num++ , $row_num, $row_sirpdata)
			->setCellValueByColumnAndRow($col_num++ , $row_num, $row_data_ora_invio)
			// ->setCellValueByColumnAndRow($col_num++ , $row_num, $data_row->abilitata == 0 ? "NO" : "SI")
			// ->setCellValueByColumnAndRow($col_num++ , $row_num, $data_row->empty($data_row->data_inserimento) ? "" : date('d/m/Y',$data_row->data_inserimento))
			;
		if (mb_strlen($data_row->titolo) > $column_max_length['corso']) $column_max_length['corso'] = mb_strlen($data_row->titolo);
		if (mb_strlen($data_row->codice_corso) > $column_max_length['codice_corso']) $column_max_length['codice_corso'] = mb_strlen($data_row->codice_corso);
		if (mb_strlen($data_row->anno) > $column_max_length['anno']) $column_max_length['anno'] = mb_strlen($data_row->anno);
		if (mb_strlen($data_row->num_sessione) > $column_max_length['num_sessione']) $column_max_length['num_sessione'] = mb_strlen($data_row->num_sessione);
		if (mb_strlen($row_data_inizio) > $column_max_length['data_inizio']) $column_max_length['data_inizio'] = mb_strlen($row_data_inizio);
		if (mb_strlen($data_row->sirp) > $column_max_length['sirp']) $column_max_length['sirp'] = mb_strlen($data_row->sirp);
		if (mb_strlen($row_sirpdata) > $column_max_length['sirpdata']) $column_max_length['sirpdata'] = mb_strlen($row_sirpdata);
		if (mb_strlen($row_data_ora_invio) > $column_max_length['data_ora_invio']) $column_max_length['data_ora_invio'] = mb_strlen($row_data_ora_invio);
		}

	$constant_adjust = 1.3;
	$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(((int) $column_max_length['corso']) * $constant_adjust);
	$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(((int) $column_max_length['codice_corso']) * $constant_adjust);
	$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(((int) $column_max_length['anno']) * $constant_adjust);
	$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(((int) $column_max_length['num_sessione']) * $constant_adjust);
	$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(((int) $column_max_length['data_inizio']) * $constant_adjust);
	$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(((int) $column_max_length['sirp']) * $constant_adjust);
	$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(((int) $column_max_length['sirpdata']) * $constant_adjust);
	$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(((int) $column_max_length['data_ora_invio']) * $constant_adjust);
		
	$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
	// stampaorizzontale
	$objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);

	// Rename sheet
	$objPHPExcel->getActiveSheet()->setTitle('Edizioni');	

	$filename='edizioni_'.date('d_m_Y').'.xls';	
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
