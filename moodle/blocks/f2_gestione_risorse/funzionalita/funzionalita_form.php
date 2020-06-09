<?php
// $Id$
require_once '../../../config.php';
require_once '../lib.php';
require_once $CFG->libdir . '/formslib.php';

class funzionalita_form extends moodleform {
	public function definition() {
		$mform 		=& $this->_form;
		$mform->addElement('html', '<h3 style="margin-top: -30">'.get_string('gestione_funzionalita', 'block_f2_gestione_risorse').'</h3>');
		$mform->addElement('header', 'nameforyourheaderelement', '');
		$divisore =& $mform->createElement('html', get_string('divisore', 'block_f2_gestione_risorse'));
		$mform->addElement('html', '<div>');
			$mform->addElement('html', '<h3 style="margin-top:-30;">Fase prenotazione</h3>');
			$funzionalitars1 = get_funzionalitaRS("prenota");
			$funz1 = $funzionalitars1->dati;
			foreach ($funz1 as $f)
			{
				$elemarr = array();
				$mform->addElement('html', '<h4>'.$f->descrizione.'</h4>');
				// $elemarr[] =& $mform->createElement('html', '&nbsp;&nbsp;'.get_string($f->id, 'block_f2_gestione_risorse').'');
				
				
					$elemarr[] =& $mform->createElement('radio', $f->id, '', '&nbsp;'.get_string('apri', 'block_f2_gestione_risorse').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', 1);
					$elemarr[] =& $mform->createElement('radio', $f->id, ' ', '&nbsp;'.get_string('chiudi', 'block_f2_gestione_risorse'), 0);
				
				$mform->addElement('html', '<div style="border:2px groove threedface;">');
					$mform->addGroup($elemarr, $f->id.'_radio', '', array(''), false);
				$mform->addElement ('html', '</div><br>');
					$mform->setDefault($f->id, ($f->aperto == 's' ? 1 : 0));
			
			}
	//	$mform->addElement('html', '</div>');
		
		$mform->addElement('html', '<h3>Fase validazione</h3>');
		$funzionalitars2 = get_funzionalitaRS("valida");
		$funz2 = $funzionalitars2->dati;
		foreach ($funz2 as $f)
		{
			$elemarr = array();
			$mform->addElement('html', '<h4>'.$f->descrizione.'</h4>');
			// $elemarr[] =& $mform->createElement('html', '&nbsp;&nbsp;'.get_string($f->id, 'block_f2_gestione_risorse').'');
			$elemarr[] =& $mform->createElement('radio', $f->id, '', get_string('apri', 'block_f2_gestione_risorse').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', 1);
			$elemarr[] =& $mform->createElement('radio', $f->id, ' ', '&nbsp;&nbsp'.get_string('chiudi', 'block_f2_gestione_risorse'), 0);
			$mform->addElement('html', '<div style="border:2px groove threedface;">');
			$mform->addGroup($elemarr, $f->id.'_radio', '', array(''), false);
			$mform->addElement ('html', '</div><br>');
			$mform->setDefault($f->id, ($f->aperto == 's' ? 1 : 0));
		}

		$mform->addElement('html', '<h3>Fase iscrizione</h3>');
		$funzionalitars3 = get_funzionalitaRS("assegna");
		$funz3 = $funzionalitars3->dati;
		foreach ($funz3 as $f)
		{
			$elemarr = array();
			$mform->addElement('html', '<h4>'.$f->descrizione.'</h4>');
			// $elemarr[] =& $mform->createElement('html', '&nbsp;&nbsp;'.get_string($f->id, 'block_f2_gestione_risorse').'');
			$elemarr[] =& $mform->createElement('radio', $f->id, '', get_string('apri', 'block_f2_gestione_risorse').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', 1);
			$elemarr[] =& $mform->createElement('radio', $f->id, ' ', '&nbsp;&nbsp'.get_string('chiudi', 'block_f2_gestione_risorse'), 0);
			$mform->addElement('html', '<div style="border:2px groove threedface;">');
			$mform->addGroup($elemarr, $f->id.'_radio', '', array(''), false);
			$mform->addElement ('html', '</div><br>');
			$mform->setDefault($f->id, ($f->aperto == 's' ? 1 : 0));
		}

		$mform->addElement($divisore);

		// TABELLA SESSIONI
		$mform->addElement('html', '<br/><h3>'.get_string('iscrizioni_tbl', 'block_f2_gestione_risorse').'</h3>');
		$anno_formativo = get_anno_formativo_corrente();
		$sessioni = get_sessioniRS($anno_formativo);
//print_r($sessioni);
                if (isset($sessioni->count)) {
		    $num_sessioni = $sessioni->count;
                }
		$dati_sessioni = $sessioni->dati;
		$width = 'width="60%"';
		$table1h = '<table class="generaltable" '.$width.'>
			<thead>
			<tr>
			<th class="header c0" style="text-align:center;" scope="col">Numero Sessione</th>
			<th class="header c1" style="text-align:center;" scope="col">Data Inizio</th>
			<th class="header c2" style="text-align:center;" scope="col">Data Fine</th>
			<th class="header c3 lastcol" style="text-align:center;" scope="col">Apri/Chiudi</th>
			</tr>
			</thead><tbody>';
		$mform->addElement('html', $table1h);
		$sessionids = "";
		$i=0;
		foreach ($dati_sessioni as $s)
		{
			$sessionids = $sessionids.'|'.$s->id_sess;
			// echo 'id_s: '.$s->id_sess;
			$col=0;
			$table1tr="";
			$table1tr = $table1tr.'<tr class="r'.($i % 2).'"><td class="cell c'.$col++.'" style="text-align:center;padding-top: 12px;">'.$s->numero.'</td>';
			$table1tr = $table1tr.'<td class="cell c'.$col++.'" style="text-align:center;padding-top: 12px;">'.date('d/m/Y',$s->data_inizio).'</td>';
			$table1tr = $table1tr.'<td class="cell c'.$col++.'" style="text-align:center;padding-top: 12px;">'.date('d/m/Y',$s->data_fine).'</td>';
			$table1tr = $table1tr.'<td class="cell c'.$col++.'" style="text-align:center;">';
			$mform->addElement('html', $table1tr);
			$sessioni_radio_arr=array();
			$radio_aperta = 1;
			$radio_chiusa = 0;
			$sessname = 'sessione_id_';
			// $sessname = '';
			$sessioni_radio_arr[] =& $mform->createElement('radio', $sessname.$s->id_sess, ' ', get_string('apri', 'block_f2_gestione_risorse'),$radio_aperta);
			$sessioni_radio_arr[] =& $mform->createElement('radio', $sessname.$s->id_sess, ' ', get_string('chiudi', 'block_f2_gestione_risorse'),$radio_chiusa);
			$mform->addGroup($sessioni_radio_arr, $sessname.$s->id_sess, '', array('&nbsp;&nbsp;&nbsp;'), false);
			$mform->setDefault($sessname.$s->id_sess, ($s->stato == 'a' ? 1 : 0));
			$mform->addElement('html','</td></tr>');
			$i++;
		}
		
		$mform->addElement('html', '</tbody></table>');
		$mform->addElement($divisore);
		$sessids = trim($sessionids,'|');
		$mform->addElement('hidden', 'sessionids', $sessids);
                $mform->setType('sessionids', PARAM_RAW);
		$buttonarray=array();
		$buttonarray[] =& $mform->createElement('submit', 'submitbutton', get_string('salva', 'block_f2_gestione_risorse'));
		$buttonarray[] =& $mform->createElement('cancel', 'cancelbtn', get_string('annulla', 'block_f2_gestione_risorse'));
		//$buttonarray[] =& $mform->createElement('reset', 'resetbtn', get_string('reset', 'block_f2_gestione_risorse'));
		$mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
	}
	function validation($data, $files)
	{
		$errors = array();
                if (isset($data->prenota_dip)) {
		    if (($data->prenota_dip == 1) and 
			(($data->prenota_direzione == 1) or
			 ($data->validazione_settore == 1) or
			 ($data->validazione_direzione == 1))
			)
		    {
			$errors[] = get_string('err_su_prenota_dip', 'block_f2_gestione_risorse');
		    }
		}
		
                if (isset($data->sessionids)) {
		    $sessionids = explode('|',$data->sessionids);
                }
		$datarr = (array) $data;
		$numaperte = 0;
                if (isset($sessionids)) {
		    foreach ($sessionids as $sid)
		    {
			$numaperte = intval($datarr['sessione_id_'.$sid]) + intval($numaperte); 
		    }
                }		

		if($numaperte > 0 and (($data->validazione_settore == 1) or ($data->validazione_direzione == 1)))
		{
			$errors[] = "Chiudere le validazioni prima di aprire le iscrizioni";
		}
		
		if ($numaperte > 1)
		{
			$errors[] = get_string('err_sessioni_multiple', 'block_f2_gestione_risorse');
		}
		
// 		print_r($data);
		return $errors;
    }
}
?>
