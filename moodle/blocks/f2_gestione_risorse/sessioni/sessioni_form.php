<?php

// $Id$

require_once '../../../config.php';
require_once '../lib.php';
require_once $CFG->libdir . '/formslib.php';

// $PAGE->requires->js(new moodle_url('calendar/calendar.js'));
$PAGE->requires->js('/f2_lib/calendar/calendar.js');
$PAGE->requires->js(new moodle_url('cancella_tutto.js'));

class sessioni_form extends moodleform {
	public function definition() {
		$mform 		=& $this->_form;	
		
		if (isset($this->_customdata['add_sess'])) 
		{
			$addsessione = $this->_customdata['add_sess'];
		}
		else 
		{
			$addsessione = 0;
		}
		if (isset($this->_customdata['add_sess'])) 
		{
			$anno_formativo = $this->_customdata['anno'];
		}
		else 
		{
			$anno_formativo = get_anno_formativo_corrente();
		}
		
		if (isset($this->_customdata['date_sess'])) 
		{
			$date_sess = $this->_customdata['date_sess'];
		}
		else 
		{
			$date_sess = null;
		}
		if (isset($this->_customdata['perc_sess']))
		{
			$perc_sess = $this->_customdata['perc_sess'];
		}
		else
		{
			$perc_sess = null;
		}
		
		// if (!is_null($perc_sess)) print_r($perc_sess);
		// else  print_r(' !!!! perc null!!! ');
		
		// if (isset($addsessione)) $mform->addElement('hidden', 'add_sessione', $addsessione);
		// else $mform->addElement('hidden', 'add_sessione', '0');
		
		$mform->addElement('hidden', 'add_sess', $addsessione);
		$buttonAdd[] =& $mform->createElement('button', 'add_sessbtn', get_string('aggiungi_sess', 'block_f2_gestione_risorse'),array('onclick' => "document.location.href='sessioni.php?add_sess=".($addsessione+1)."'"));
		$mform->addGroup($buttonAdd, 'buttonadd', '', array(' '), false);
		// print_r('@@@@@@@@@@@@@@@@@@@@@@@@'.$addsessione);
		
		$divisore =& $mform->createElement('html', '<br/>'.get_string('divisore', 'block_f2_gestione_risorse'));
		
		/*
		$mform->addElement('html', '<h2>'.get_string('gestione_sessioni', 'block_f2_gestione_risorse').'</h2>');
		
	
		$sessionirs = get_sessioniRS();
		$funz = $sessionirs->dati;
		foreach ($funz as $f)
		{
			$elemarr = array();
			$elemarr[] =& $mform->createElement('html', '<br/><h3>&nbsp;&nbsp;'.$f->descrizione.'</h3>');
			// $elemarr[] =& $mform->createElement('html', '&nbsp;&nbsp;'.get_string($f->id, 'block_f2_gestione_risorse').'');
			$elemarr[] =& $mform->createElement('radio', $f->id, '', get_string('apri', 'block_f2_gestione_risorse').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', 1);
			$elemarr[] =& $mform->createElement('radio', $f->id, ' ', '&nbsp;&nbsp'.get_string('chiudi', 'block_f2_gestione_risorse'), 0);
			$mform->addGroup($elemarr, $f->id.'_radio', '', array(''), false);
			$mform->setDefault($f->id, ($f->aperto == 's' ? 1 : 0));
		}
		
		
		*/
		// $mform->addElement('date_selector', 'data_inizio', '');
		// $mform->setDefault('data_inizio',100);
		
		
		// TABELLA SESSIONI
		// $mform->addElement('html', '<br/><h3>'.get_string('iscrizioni_tbl', 'block_f2_gestione_risorse').'</h3>');
		$sessioni = get_sessioniRS($anno_formativo,$addsessione);
		$dati_sessioni = $sessioni->dati;
		$num_sessioni = count($dati_sessioni);
// 		if (!is_null($date_sess) and !is_null($perc_sess))
		if ($num_sessioni > 0)
		{
			global $OUTPUT;
			// 		echo 'aaaa '.$num_sessioni;
			// 		print_r($sessioni);
			// print_r($dati_sessioni);
			$width = 'width="90%"';
			$table1h = '<table class="generaltable" '.$width.'>
			<thead>
			<tr>
			<th class="header c0" style="text-align:center;" scope="col">Numero Sessione</th>
			<th class="header c1" style="text-align:center;" scope="col">Data Inizio</th>
			<th class="header c2" style="text-align:center;" scope="col">Data Fine</th>
			<th class="header c3" style="text-align:center;" scope="col">Percentuale Corsi</th>
			<th class="header c4 lastcol" style="text-align:center;" scope="col">Cancella</th>
			</tr>
			</thead><tbody>
		';
			$mform->addElement('html', $table1h);
			$sessionids = "";
			$i=0;
			
			foreach ($dati_sessioni as $s)
			{
				// $mform->addElement('hidden', 'sessione_'.$s->id_sess, $i+1);
					
				// print_r((array)$dati_sessioni);
				$sessionids = $sessionids.'|'.$s->id_sess;
				$sessname = 'sessione_id_';
				$percname = 'percentuale_corsi_';
				$col=0;
				$table1tr="";
				$table1tr = $table1tr.'<tr class="r'.($i % 2).'"><td class="cell c'.$col++.'" style="text-align:center;">'.$s->numero.'</td>';
			
				$table1tr = $table1tr.'<td class="cell c'.$col++.'" style="text-align:center;">';
				$mform->addElement('html', $table1tr);
				$table1tr = "";
					
				// $data_inizio_sel =& $mform->createElement('date_selector', 'data_inizio'.$s->id_sess, '');
				// $mform->setDefault('data_inizio'.$s->id_sess,$s->data_inizio);
				// $mform->addElement($data_inizio_sel);
				// $table1tr = '</td>';
				// $mform->addElement('html', $table1tr);
				// $table1tr = "";
				$data_s_i = date('d/m/Y',$s->data_inizio);
				$data_s_f = date('d/m/Y',$s->data_fine);
					
// 				$data_s_i = $date_sess[$sessname.$s->id_sess.'_inizio'];
// 				$data_s_f = $date_sess[$sessname.$s->id_sess.'_fine'];
					
				$perc_s = $s->percentuale_corsi;
// 				$t = $perc_sess[$percname.$s->id_sess];
// 				$perc_s = $t;
					
				// 1 cifra decimale per la percentuale
				$perc_s = number_format($perc_s,1);
				// $table1tr = '<input type="text" name="'.$sessname.$s->id_sess.'_inizio" id="'.$sessname.$s->id_sess.'_inizio" value="'.date('d/m/Y',$s->data_inizio).'" />&nbsp;&nbsp;<img src="'.new moodle_url('/f2_lib/calendar/images/select.gif').'" alt="seleziona la data" onclick="Calendar.show(document.getElementById(\''.$sessname.$s->id_sess.'_inizio\'), \'%d/%m/%Y\', false)" style="cursor:pointer;" />';
				$table1tr = '<input type="text" name="'.$sessname.$s->id_sess.'_inizio" id="'.$sessname.$s->id_sess.'_inizio" value="'.$data_s_i.'" />&nbsp;&nbsp;<img src="'.new moodle_url('/f2_lib/calendar/images/select.gif').'" alt="seleziona la data" onclick="Calendar.show(document.getElementById(\''.$sessname.$s->id_sess.'_inizio\'), \'%d/%m/%Y\', false)" style="cursor:pointer;" />';
				$mform->addElement('html', $table1tr);
				$table1tr = "";
					
				$table1tr = $table1tr.'<td class="cell c'.$col++.'" style="text-align:center;">';
				$mform->addElement('html', $table1tr);
				$table1tr = "";
					
				// $table1tr = '<input type="text" name="'.$sessname.$s->id_sess.'_fine" id="'.$sessname.$s->id_sess.'_fine" value="'.date('d/m/Y',$s->data_fine).'" />&nbsp;&nbsp;<img src="'.new moodle_url('/f2_lib/calendar/images/select.gif').'" alt="seleziona la data" onclick="Calendar.show(document.getElementById(\''.$sessname.$s->id_sess.'_fine\'), \'%d/%m/%Y\', false)" style="cursor:pointer;" />';
				$table1tr = '<input type="text" name="'.$sessname.$s->id_sess.'_fine" id="'.$sessname.$s->id_sess.'_fine" value="'.$data_s_f.'" />&nbsp;&nbsp;<img src="'.new moodle_url('/f2_lib/calendar/images/select.gif').'" alt="seleziona la data" onclick="Calendar.show(document.getElementById(\''.$sessname.$s->id_sess.'_fine\'), \'%d/%m/%Y\', false)" style="cursor:pointer;" />';
				$mform->addElement('html', $table1tr);
				$table1tr = "";
			
					
				// $table1tr = $table1tr.'<td class="cell c'.$col++.'" style="text-align:center;">'.intval($s->percentuale_corsi).'%</td>';
				// $mform->addElement('html', $table1tr);
				// $table1tr = $table1tr.'<td class="cell c'.$col++.'" style="text-align:center;"><input type="text" name=$percname.$s->id_sess.'" value="'.intval($s->percentuale_corsi).'" size="3"/></td>';
				$table1tr = $table1tr.'<td class="cell c'.$col++.'" style="text-align:center;"><input type="text" name="'.$percname.$s->id_sess.'" value="'.$perc_s.'" size="3"/></td>';
				$mform->addElement('html', $table1tr);
				$table1tr = "";
				
				// $table1tr = $table1tr.'<td class="cell c'.$col++.'" style="text-align:center;">';
				// $mform->addElement('html', $table1tr);
				// $mform->addElement('text', $percname.$s->id_sess, '');
				// $table1tr = '</td>';
				// $mform->addElement('html', $table1tr);
				$table1tr = '<td class="cell c'.$col++.'" style="text-align:center;">';
				$buttonc = new single_button(new moodle_url('popup.php?cancid='.$s->id_sess), 'Cancella');
				$actionc = new component_action('click','M.util.show_confirm_dialog',array(
						 'message' => 'Hai richiesto di cancellare la sessione, confermi?', 
						'callback' =>'confirm_cancella_sessione(\''.get_string('confirm_msg2', 'block_f2_gestione_risorse').'\',\''.$s->id_sess.'\')',
						// 'message' => 'messaggio', 'callback' =>'M.util.show_confirm_dialog(\'bbbbb\')',
						'continuelabel' => ''.get_string('conferma_lbl','block_f2_gestione_risorse').'', 
						'cancellabel' => ''.get_string('annulla_lbl','block_f2_gestione_risorse').''));
				$buttonc->add_action($actionc);
				$table1tr = $table1tr.$OUTPUT->render($buttonc).'</td>';
				$mform->addElement('html', $table1tr);
				$table1tr = "";
				
				$mform->addElement('html','</tr>');
				$i++;
			}
			
			$mform->addElement('html', '</tbody></table>');
			$sessids = trim($sessionids,'|');
			$mform->addElement('hidden', 'sessionids', $sessids);
		}
		
		$mform->addElement($divisore);

		$buttonarray=array();
		// $buttonarray[] =& $mform->createElement('button', 'add_sessbtn', get_string('aggiungi_sess', 'block_f2_gestione_risorse'),array('onclick' => "reset()"));
		//$buttonarray[] =& $mform->createElement('button', 'add_sessbtn', get_string('aggiungi_sess', 'block_f2_gestione_risorse'),array('onclick' => "document.location.href='sessioni.php?add_sess=".($addsessione+1)."'"));
		// $buttonarray[] =& $mform->createElement('button', 'add_sessbtn', get_string('aggiungi_sess', 'block_f2_gestione_risorse'),array('onclick' => "reset()"));
		if ($num_sessioni !== 0) 
		{
			$buttonarray[] =& $mform->createElement('submit', 'submitbutton', get_string('salva', 'block_f2_gestione_risorse'));
			$buttonarray[] =& $mform->createElement('cancel', 'cancelbtn', get_string('annulla', 'block_f2_gestione_risorse'));
//			$buttonarray[] =& $mform->createElement('reset', 'resetbtn', get_string('reset', 'block_f2_gestione_risorse'));
		}
		//$buttonarray[] =& $mform->createElement('button', 'cancella_tutto_btn', get_string('cancella_tutto', 'block_f2_gestione_risorse'),array('onclick' => "return confirm_cancella_tutti()", 'action' => 'document.location.href=\'http://127.0.0.1\''));

		$mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
/*		if ($num_sessioni !== 0)
		{ 
			global $OUTPUT;
			$buttonc = new single_button(new moodle_url('popup.php?cancall=1'), 'Cancella tutto');
			$actionc = new component_action('click','M.util.show_confirm_dialog',array(
	                 'message' => ''.get_string('confirm_msg1', 'block_f2_gestione_risorse').'', 
					'callback' =>'confirm_cancella_tutti(\''.get_string('confirm_msg2', 'block_f2_gestione_risorse').'\',\''.$anno_formativo.'\')',
	                // 'message' => 'messaggio', 'callback' =>'M.util.show_confirm_dialog(\'bbbbb\')',
	                'continuelabel' => ''.get_string('conferma_lbl','block_f2_gestione_risorse').'', 
					'cancellabel' => ''.get_string('annulla_lbl','block_f2_gestione_risorse').''));
			$buttonc->add_action($actionc);
			$mform->addElement('html','<div><p>'.$OUTPUT->render($buttonc).'</p></div>');
		}*/
	}
	
	function validation($data) 
	{
// 		print_r($data);
		$errors = array();
		if (!is_array($data)) $datarr = (array) $data;
		else $datarr = $data;
		
		$arr_datakeys = array_keys($datarr);
		$arr_datastr = implode ('|',$arr_datakeys );
		$pattern = '/sessione_id_/';
		if (preg_match($pattern, $arr_datastr) !== 1)
		{
// 			echo '<br/>non esiste match sessione_id_ : '.$arr_datastr.'<br/>';
		}
		else if (isset($datarr) and (!is_null($datarr)) and (!empty($datarr)) and isset($datarr['sessionids']))
		{
			$sessionids = explode('|',$datarr['sessionids']);
			$tot_perc = 0;
			$sessname = 'sessione_id_';
			$perc_name = 'percentuale_corsi_';
			//'.$sessname.$s->id_sess.'_fine
// 			$s->id_sess;
			$non_numeric = 0;
			$date_inizio_fine_incoerenti = 0;
			$sovrapposte = 0;
			$formato_date_invalido = 0;
			// print_r($datarr);
			foreach ($sessionids as $sid)
			{
				$t = $datarr[$perc_name.$sid];
				if (!isset($t) or is_null($t) or empty($t)) $t = 0;
				if (is_numeric($t)) 
				{
					$tot_perc = number_format($t,1) + $tot_perc; 
				}
				else $non_numeric++;
				$data_s_arr = explode('/',$datarr[$sessname.$sid.'_inizio']);
				$data_s_current = mktime(0,0,0,$data_s_arr[1],$data_s_arr[0],$data_s_arr[2]);
				$data_f_arr = explode('/',$datarr[$sessname.$sid.'_fine']);
				$data_f_current = mktime(0,0,0,$data_f_arr[1],$data_f_arr[0],$data_f_arr[2]);
				if (isset($data_s_current) and (!is_null($data_s_current)) and (!empty($data_s_current))
				and isset($data_f_current) and (!is_null($data_f_current)) and (!empty($data_f_current))
				// and ($datarr[$sessname.$sid.'_inizio'] == date('d/m/Y',$data_s_current))
				// and ($datarr[$sessname.$sid.'_fine'] == date('d/m/Y',$data_f_current))
				)
				{
					// echo '<br/>data_s_arr: '.$data_s_current;
					// echo '<br/>data_f_arr: '.$data_f_current;
					if ($data_s_current >= $data_f_current) $date_inizio_fine_incoerenti++;
					else
					{
						foreach ($sessionids as $sid2)
						{
							if ($sid2 !== $sid)
							{
								$data_s2_arr = explode('/',$datarr[$sessname.$sid2.'_inizio']);
								$data_s2_current = mktime(0,0,0,$data_s2_arr[1],$data_s2_arr[0],$data_s2_arr[2]);
								$data_f2_arr = explode('/',$datarr[$sessname.$sid2.'_fine']);
								$data_f2_current = mktime(0,0,0,$data_f2_arr[1],$data_f2_arr[0],$data_f2_arr[2]);
								
								list($post_ts_inizio_d, $post_ts_inizio_m, $post_ts_inizio_y) = explode('/', $datarr[$sessname.$sid2.'_inizio']);
								list($post_ts_fine_d, $post_ts_fine_m, $post_ts_fine_y) = explode('/', $datarr[$sessname.$sid2.'_fine']);
								
								$post_data_ts_inizio = mktime(0, 0, 0, $post_ts_inizio_m, $post_ts_inizio_d, $post_ts_inizio_y);
								$post_data_ts_fine = mktime(0, 0, 0, $post_ts_fine_m, $post_ts_fine_d, $post_ts_fine_y);
								
// 								echo '<br/>data_s2_arr: '.$data_s2_current;
// 								echo '<br/>post_data_inizio: '.$post_data_ts_inizio;
// 								echo '<br/>data_f2_arr: '.$data_f2_current;
// 								echo '<br/>post_data_fine: '.$post_data_ts_fine;
								
								if (isset($data_s2_current) and (!is_null($data_s2_current)) and (!empty($data_s2_current))
								and isset($data_f2_current) and (!is_null($data_f2_current)) and (!empty($data_f2_current))
// 								and $datarr[$sessname.$sid2.'_inizio'] == date('d/m/Y',$data_s2_current)
// 								and $datarr[$sessname.$sid2.'_fine'] == date('d/m/Y',$data_f2_current)
								and $post_data_ts_inizio == $data_s2_current 
								and $post_data_ts_fine == $data_f2_current
								)
								{
									if 
									(
										// 1==1 and
										(($data_s2_current >= $data_s_current) and ($data_s2_current <= $data_f_current)) 
										or (($data_f2_current >= $data_s_current) and ($data_f2_current <= $data_f_current))
										or (($data_s2_current < $data_s_current) and ($data_f2_current > $data_f_current))
									)
									{
										// echo '<br/>data_s_curr: '.date('d/m/Y',$data_s_current);
										// echo '<br/>data_f_curr: '.date('d/m/Y',$data_f_current);
										// echo '<br/>data_s2_curr: '.date('d/m/Y',$data_s2_current);
										// echo '<br/>data_f2_curr: '.date('d/m/Y',$data_f2_current);
										// echo '<br/>--------';
										$sovrapposte++;
									}
								}
								else $formato_date_invalido++;
							}
						}
					}
				}	
			}
			if ($tot_perc > 100) $errors[] = get_string('err_max_perc_corsi', 'block_f2_gestione_risorse').' ('.$tot_perc.')';
			if ($non_numeric > 0) $errors[] = get_string('err_invalid_perc_corsi', 'block_f2_gestione_risorse');
			if ($formato_date_invalido > 0) $errors[] = get_string('formato_date_invalido', 'block_f2_gestione_risorse');
			else 
			{
				if ($date_inizio_fine_incoerenti > 0) $errors[] = get_string('date_inizio_fine_incoerenti', 'block_f2_gestione_risorse');
				else if ($sovrapposte > 0) $errors[] = get_string('err_sovrapposte', 'block_f2_gestione_risorse');
			}
		}
		return $errors;
    }
	
	function get_updated_form_data($data)
	{
	$sessionids = explode('|',$data['sessionids']);
	$sessname = 'sessione_id_';
	$percname = 'percentuale_corsi_';
	$date_sess = array();
	$perc_sess = array();
	foreach ($sessionids as $d)
	{
		$date_sess[$sessname.$d.'_inizio'] = $data[$sessname.$d.'_inizio'];
		$date_sess[$sessname.$d.'_fine'] = $data[$sessname.$d.'_fine'];
		$perc_sess[$percname.$d] = $data[$percname.$d];
	}
	return array('date' => $date_sess,'perc' => $perc_sess);
	}
}	
