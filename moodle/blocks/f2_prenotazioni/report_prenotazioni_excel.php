<?php

/*
 * $Id: report_prenotazioni_excel.php 1241 2013-12-20 04:34:05Z l.moretto $
 */
ob_start();
require_once('../../config.php');
require_once 'lib.php';
defined('MOODLE_INTERNAL') || die();

// $userid = required_param('userid', PARAM_INT);
// $c_exp_type = required_param('c_exp_type', PARAM_INT);
// $direction   = optional_param('dir', 'DESC', PARAM_ACTION);
// $sort   = optional_param('sort', 'data_inizio', PARAM_ACTION); 

$page     = optional_param('page', 0, PARAM_INT);
$perpage  = optional_param('perpage', 10, PARAM_INT);
$column   = optional_param('column', 'codice', PARAM_TEXT);
$sort     = optional_param('sort', 'ASC', PARAM_TEXT);

$post_values = required_param('post_values',PARAM_TEXT);
$post_values = str_replace("##dubleap##", "\"", $post_values);
$post_values = json_decode($post_values);

$userid = $post_values->userid;

$blockid = get_block_id(get_string('pluginname_db','block_f2_prenotazioni'));

if($userid==$USER->id || (has_capability('block/f2_prenotazioni:viewprenotazioni', get_context_instance(CONTEXT_BLOCK, $blockid)) 
		&& validate_own_dipendente($userid)))
// if (has_capability('block/f2_prenotazioni:viewprenotazioni', get_context_instance(CONTEXT_BLOCK, $blockid)) and 
// 		validate_own_dipendente($userid))
{

	global $CFG,$DB;

		require_once($CFG->dirroot.'/f2_lib/phpexcel177/PHPExcel.php');
		//Create new PHPExcel object
		// $objPHPExcel = new PHPExcel();
		$objPHPExcel = PHPExcel_IOFactory::load($CFG->dirroot.'/f2_lib/phpexcel177/template/template_csi.xls');
		
		// Set properties
		$objPHPExcel->getProperties()->setCreator("CSI")
									 ->setLastModifiedBy("CSI")
									 ->setTitle("CSI")
									 ->setSubject("CSI")
									 ->setDescription("CSI")
									 ->setKeywords("CSI")
									 ->setCategory("CSI");
									 
		$dati_user = get_user_data($userid);
		$settore = get_user_organisation($userid);
		$settore_id = $settore[0];
		$objsettore = get_organisation_info_by_id($settore_id);
		$settore_nome = is_null($objsettore->fullname) ? 'n.d.' : "$objsettore->shortname - $objsettore->fullname";
		$objdirezione = get_organisation_info_by_id($objsettore->parentid);
		$direzione_nome = is_null($objdirezione->fullname) ? 'n.d.' : $objdirezione->shortname." - ".$objdirezione->fullname;
		
		$objPHPExcel->setActiveSheetIndex(0);
		
		$row = 1;
		$objPHPExcel->getActiveSheet()
				->setCellValueByColumnAndRow(0,$row++,get_string('intestazione_excel','block_f2_prenotazioni').' '.$dati_user->lastname.' '.$dati_user->firstname)
				// ->setCellValueByColumnAndRow(1,1,$dati_user->lastname.' '.$dati_user->firstname)
				;
		
		$objPHPExcel->getActiveSheet()
		->setCellValueByColumnAndRow(0,$row++,'Matricola: '.$dati_user->idnumber)
		->setCellValueByColumnAndRow(0,$row++,'Categoria: '.$dati_user->category)
		->setCellValueByColumnAndRow(0,$row++,'Direzione: '.$direzione_nome)
		->setCellValueByColumnAndRow(0,$row++,'Settore: '.$settore_nome)
		;
		$row++;
		
		// Set columns
		$objPHPExcel->getActiveSheet()
				->setCellValueByColumnAndRow(0,$row,get_string('codice','block_f2_prenotazioni'))
				->setCellValueByColumnAndRow(1,$row,get_string('titolo','block_f2_prenotazioni'))
				->setCellValueByColumnAndRow(2,$row,get_string('segmento_formativo','block_f2_prenotazioni'))
				->setCellValueByColumnAndRow(3,$row,get_string('sede_prenotazione','block_f2_prenotazioni'))
				->setCellValueByColumnAndRow(4,$row,get_string('stato','block_f2_prenotazioni'))
				->setCellValueByColumnAndRow(5,$row,get_string('data_prenotazione','block_f2_prenotazioni'))
				;
		
		$post_values->page=0;
		$post_values->perpage=0;
		
		$full_prenotazioni = get_user_prenotazioni($post_values);		
		$prenotazioni = $full_prenotazioni->dati;
		
// 		$row = 2;
		$column_max_length = array('codice'=>mb_strlen(get_string('codice','block_f2_prenotazioni')),
									'titolo'=>mb_strlen(get_string('titolo','block_f2_prenotazioni')),
									'segmento_formativo'=>mb_strlen(get_string('segmento_formativo','block_f2_prenotazioni')),
									'sede_prenotazione'=>mb_strlen(get_string('sede_prenotazione','block_f2_prenotazioni')),
									'stato'=>mb_strlen(get_string('stato','block_f2_prenotazioni')),
									'data_prenotazione'=>mb_strlen(get_string('data_prenotazione','block_f2_prenotazioni'))
									);
		foreach ($prenotazioni as $c)
		{
			$vals = $c->validatos;
			$vald = $c->validatod;
			$stato_str = '';
			if (($vals !== '-1') and ($vald !== '-1'))
			{
				$stato_str = get_stato_prenotazione_str($vals,$vald,$c->orgid,0,$c->prenotazione_id);
			}
			$row++;
			$col_num = 0;
			$objPHPExcel->getActiveSheet()
				->setCellValueByColumnAndRow($col_num++	, $row, $c->codice)
				->setCellValueByColumnAndRow($col_num++	, $row, $c->titolo)
				->setCellValueByColumnAndRow($col_num++	, $row, $c->segmento_formativo)
				->setCellValueByColumnAndRow($col_num++	, $row, $c->sede_prenotazione)
				->setCellValueByColumnAndRow($col_num++	, $row, $stato_str)
				->setCellValueByColumnAndRow($col_num++	, $row, date('d/m/Y',$c->data_prenotazione))
				;

			if (mb_strlen($c->codice) > $column_max_length['codice']) $column_max_length['codice'] = mb_strlen($c->codice);
			if (mb_strlen($c->titolo) > $column_max_length['codice']) $column_max_length['titolo'] = mb_strlen($c->titolo);
			if (mb_strlen($c->segmento_formativo) > $column_max_length['segmento_formativo']) $column_max_length['segmento_formativo'] = mb_strlen($c->segmento_formativo);
			if (mb_strlen($c->sede_prenotazione) > $column_max_length['sede_prenotazione']) $column_max_length['sede_prenotazione'] = mb_strlen($c->sede_prenotazione);
			if (mb_strlen($stato_str) > $column_max_length['stato']) $column_max_length['stato'] = mb_strlen($stato_str);
			if (mb_strlen(date('d/m/Y',$c->data_prenotazione)) > $column_max_length['data_prenotazione']) $column_max_length['data_prenotazione'] = mb_strlen(date('d/m/Y',$c->data_prenotazione));
		}
		$constant_adjust = 1.3;
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(((int) $column_max_length['codice']) * $constant_adjust);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(((int) $column_max_length['titolo']) * $constant_adjust);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(((int) $column_max_length['segmento_formativo']) * $constant_adjust);
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(((int) $column_max_length['sede_prenotazione']) * $constant_adjust);
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(((int) $column_max_length['stato']) * $constant_adjust);
		$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(((int) $column_max_length['data_prenotazione']) * $constant_adjust);

		$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
		$objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
			
		// Rename sheet
		$objPHPExcel->getActiveSheet()->setTitle('report prenotazioni');	
		
		$filename='report_prenotazioni_'.$dati_user->lastname.'_'.$dati_user->firstname.'_'.date('d_m_Y').'.xls';	
		setcookie('fileDownload', 'true', 0, '/');
		header('Cache-Control: max-age=60, must-revalidate'); 
// 		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="'.$filename.'');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		ob_end_clean();
		$objWriter->save('php://output');
}
else{
	
		setcookie('fileDownload', 'true', 0, '/');
		header('Cache-Control: max-age=60, must-revalidate'); 
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		ob_end_clean();
	}	
?>
