<?php
/*
 * $Id: export_courses.php 1080 2013-03-22 16:36:48Z d.lallo $
 */
ob_start();
 require_once('../../config.php');
require_once 'lib.php';
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/f2_lib/report.php');

global $CFG,$DB,$USER;

// $userid = required_param('userid', PARAM_INT);
// $c_exp_type = required_param('c_exp_type', PARAM_INT);
// $direction   = optional_param('dir', 'DESC', PARAM_ACTION);
// $sort   = optional_param('sort', 'data_inizio', PARAM_ACTION); 

$page     = optional_param('page', 0, PARAM_INT);
$perpage  = optional_param('perpage', 10, PARAM_INT);
$column   = optional_param('column', 'data_inizio', PARAM_TEXT);
$sort     = optional_param('sort', 'DESC', PARAM_TEXT);

$post_values = required_param('post_values',PARAM_TEXT);
$post_values = str_replace("##dubleap##", "\"", $post_values);
$post_values = json_decode($post_values);

$userid = $post_values->userid;

$target_user = ($userid == 0 ? intval($USER->id) : $userid);
//print_r($target_user);die();
$b_viewingdipcv = ($target_user != $USER->id);
//print_r($b_viewowncv);die();
$b_canviewdipcv = ( 
// 	has_capability('block/f2_apprendimento:viewdipendenticurricula', get_context_instance(CONTEXT_SYSTEM)) 
	has_capability('block/f2_apprendimento:viewdipendenticurricula', get_context_instance(CONTEXT_COURSE,1))
		
	&& validate_own_dipendente($target_user)
);
//print_r($b_chk);die();
if( $b_viewingdipcv && !$b_canviewdipcv ) 
	print_error('noviewdipendenticurricula','block_f2_apprendimento');
//if($userid==$USER->id || 
//  (has_capability('block/f2_apprendimento:viewdipendenticurricula', get_context_instance(CONTEXT_SYSTEM)) 
//  			&& validate_own_dipendente($userid)))
//{
		$timenow = time();
		require_once($CFG->dirroot.'/f2_lib/phpexcel177/PHPExcel.php');
		require_once($CFG->dirroot.'/f2_lib/core.php');
		require_once($CFG->dirroot.'/f2_lib/management.php');
		require_once($CFG->dirroot.'/user/profile/lib.php');
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
									 
		$dati_user = get_user_data($target_user);
		$user_custom_filed = profile_user_record($target_user);
		$direzione = get_direzione_utente($target_user);
		$settore = get_settore_utente($target_user);
		
		$objPHPExcel->setActiveSheetIndex(0);
		$row = 1;
		$objPHPExcel->getActiveSheet()
				->setCellValueByColumnAndRow(0,$row,get_string('intestazione_excel','block_f2_apprendimento').' '.$dati_user->lastname.' '.$dati_user->firstname)
				// ->setCellValueByColumnAndRow(1,1,$dati_user->lastname.' '.$dati_user->firstname)
				;
		$row++;
		
		$objPHPExcel->getActiveSheet()
		->setCellValueByColumnAndRow(0,$row++,'Matricola: '.$dati_user->idnumber)
		->setCellValueByColumnAndRow(0,$row++,'Categoria: '.$user_custom_filed->category)
		->setCellValueByColumnAndRow(0,$row++,'Direzione: '.$direzione['name'])
		->setCellValueByColumnAndRow(0,$row++,'Settore: '.$settore['name'])
		;
		$row++;
		
		// Set columns
		$objPHPExcel->getActiveSheet()
				->setCellValueByColumnAndRow(0,$row,get_string('ente','block_f2_apprendimento'))
				->setCellValueByColumnAndRow(1,$row,get_string('codice','block_f2_apprendimento'))
				->setCellValueByColumnAndRow(2,$row,get_string('titolo_corso','block_f2_apprendimento'))
				->setCellValueByColumnAndRow(3,$row,get_string('data_inizio','block_f2_apprendimento'))
				->setCellValueByColumnAndRow(4,$row,get_string('sf','block_f2_apprendimento'))
				->setCellValueByColumnAndRow(5,$row,get_string('cf','block_f2_apprendimento'))
				->setCellValueByColumnAndRow(6,$row,get_string('cfv','block_f2_apprendimento'))
				->setCellValueByColumnAndRow(7,$row,get_string('presenza','block_f2_apprendimento'))
				->setCellValueByColumnAndRow(8,$row,get_string('va','block_f2_apprendimento'))
				;
		
		$post_values->page=0;
		$post_values->perpage=0;
		$full_mycourses = user_history_courses($post_values);		
		$mycourses = $full_mycourses->dati;
		
		
		$column_max_length = array('ente'=>mb_strlen(get_string('intestazione_excel','block_f2_apprendimento')),
									'codice'=>mb_strlen(get_string('codice','block_f2_apprendimento')),
									'titolo_corso'=>mb_strlen(get_string('titolo_corso','block_f2_apprendimento')),
									'data_inizio'=>mb_strlen(get_string('data_inizio','block_f2_apprendimento')),
									'sf'=>mb_strlen(get_string('sf','block_f2_apprendimento')),
									'cf'=>mb_strlen(get_string('cf','block_f2_apprendimento')),
									'cfv'=>mb_strlen(get_string('cfv','block_f2_apprendimento')),
									'presenza'=>mb_strlen(get_string('presenza','block_f2_apprendimento')),
									'va'=>mb_strlen(get_string('va','block_f2_apprendimento')),
									);
		foreach ($mycourses as $c){
			$row++;
			$col_num = 0;
			$objPHPExcel->getActiveSheet()
				->setCellValueByColumnAndRow($col_num++	, $row, $c->ente)
				->setCellValueByColumnAndRow($col_num++	, $row, $c->codice)
				->setCellValueByColumnAndRow($col_num++	, $row, $c->nome)
				->setCellValueByColumnAndRow($col_num++	, $row, date('d/m/Y',$c->start))
				->setCellValueByColumnAndRow($col_num++	, $row, $c->sf)
				->setCellValueByColumnAndRow($col_num++	, $row, $c->cf)
				->setCellValueByColumnAndRow($col_num++	, $row, $c->cfv)
				->setCellValueByColumnAndRow($col_num++	, $row, $c->presenza)
				->setCellValueByColumnAndRow($col_num++	, $row, $c->va)
				;
			if (mb_strlen($c->ente) > $column_max_length['ente']) $column_max_length['ente'] = mb_strlen($c->ente);
			if (mb_strlen($c->codice) > $column_max_length['codice']) $column_max_length['codice'] = mb_strlen($c->codice);
			if (mb_strlen($c->nome) > $column_max_length['titolo_corso']) $column_max_length['titolo_corso'] = mb_strlen($c->nome);
			// if (mb_strlen(date('d/m/Y',$c->start)) > $column_max_length['data_inizio']) $column_max_length['data_inizio'] = mb_strlen(date('d/m/Y',$c->start));
			// if (mb_strlen($c->sf) > $column_max_length['sf']) $column_max_length['sf'] = mb_strlen($c->sf);
			// if (mb_strlen($c->cf) > $column_max_length['cf']) $column_max_length['cf'] = mb_strlen($c->cf);
			// if (mb_strlen($c->cfv) > $column_max_length['cfv']) $column_max_length['cfv'] = mb_strlen($c->cfv);
			// if (mb_strlen($c->presenza) > $column_max_length['presenza']) $column_max_length['presenza'] = mb_strlen($c->presenza);
			if (mb_strlen($c->va) > $column_max_length['va']) $column_max_length['va'] = mb_strlen($c->va);
		}
		$constant_adjust = 1.5;
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(((int) $column_max_length['ente']) * ($constant_adjust- 0.5));
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(((int) $column_max_length['codice']) * $constant_adjust);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(((int) $column_max_length['titolo_corso']) * ($constant_adjust - 0.8));
		// $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(((int) $column_max_length['titolo_corso']));
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(((int) $column_max_length['data_inizio']) * ($constant_adjust - 0.4));
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(((int) $column_max_length['sf']) * $constant_adjust);
		$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(((int) $column_max_length['cf']) * $constant_adjust);
		$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(((int) $column_max_length['cfv']) * $constant_adjust);
		$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(((int) $column_max_length['presenza']) * ($constant_adjust- 0.4));
		$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(((int) $column_max_length['va']) * $constant_adjust);

		$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
		$objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
			
		// Rename sheet
		$objPHPExcel->getActiveSheet()->setTitle('report corsi');	
		
		$filename='cv_'.$dati_user->lastname.'_'.$dati_user->firstname.'_'.date('d_m_Y').'.xls';	
		setcookie('fileDownload', 'true', 0, '/');
		header('Cache-Control: max-age=60, must-revalidate'); 
// 		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="'.$filename.'');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		ob_end_clean();
		$objWriter->save('php://output');
//}
//else{
//// 		setcookie('fileDownload', 'true', 0, '/');
//// 		header('Cache-Control: max-age=60, must-revalidate'); 
//// 		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
//// 		ob_end_clean();
//		print_r('aaaa');
//	}	
?>
