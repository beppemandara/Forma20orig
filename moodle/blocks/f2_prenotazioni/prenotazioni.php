<?php

//$Id: prenotazioni.php 1241 2013-12-20 04:34:05Z l.moretto $
global $CFG,$USER,$COURSE,$DB;

require_once '../../config.php';
require_once 'lib.php';

require_login();
$blockid = get_block_id(get_string('pluginname_db','block_f2_prenotazioni'));
$context = context_block::instance($blockid); // ADD 2017 08 29
require_capability('block/f2_prenotazioni:viewprenotazioni', $context); // ADD 2017 08 29
//require_capability('block/f2_prenotazioni:viewprenotazioni', get_context_instance(CONTEXT_BLOCK, $blockid));

$userid       = optional_param('userid', 0, PARAM_INT);

$page     = optional_param('page', 0, PARAM_INT);
$perpage  = optional_param('perpage', 10, PARAM_INT);
$column   = optional_param('column', 'codice', PARAM_TEXT);
$sort     = optional_param('sort', 'ASC', PARAM_TEXT);
$action     = optional_param('funzione', 'show', PARAM_TEXT);
$prenota_altri     = optional_param('pa', 0, PARAM_INT);
$search_corso     = optional_param('search_corso', '', PARAM_TEXT);

if($userid==0) $userid=intval($USER->id);
else if($userid!=0 && has_capability('block/f2_prenotazioni:viewprenotazioni', get_context_instance(CONTEXT_BLOCK, $blockid)) && validate_own_dipendente($userid)) $userid=$userid;
else die();

if ($prenota_altri == 1)
{
	if (prenotazioni_direzione_aperte() || isSupervisore($USER->id))
	{
		$baseurl = new moodle_url('/blocks/f2_prenotazioni/prenota_altri.php?userid='.$userid.'&pa='.$prenota_altri);
		$PAGE->navbar->add(get_string('prenota_altri', 'block_f2_prenotazioni'), $baseurl);
		$baseurl = new moodle_url('/blocks/f2_prenotazioni/prenotazioni.php?userid='.$userid.'&pa='.$prenota_altri);
		$PAGE->navbar->add(get_string('prenotazioni', 'block_f2_prenotazioni'), $baseurl);
	}
	else
	{
		redirect(new moodle_url('/blocks/f2_prenotazioni/report_prenotazioni.php?userid='.$userid.'&pa='.$prenota_altri));
	} 
}
else //prenotazioni dip
{
	if (prenotazioni_dip_aperte())
	{
		$baseurl = new moodle_url('/blocks/f2_prenotazioni/prenotazioni.php?userid='.$userid.'&pa='.$prenota_altri);
		$PAGE->navbar->add(get_string('prenotazioni', 'block_f2_prenotazioni'), $baseurl);
	}
	else 
	{
		redirect(new moodle_url('/blocks/f2_prenotazioni/report_prenotazioni.php?userid='.$userid.'&pa='.$prenota_altri));
	}
}

$blockname = get_string('pluginname', 'block_f2_prenotazioni');
$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_pagelayout('standard');
$PAGE->set_context($context);
$PAGE->set_url('/blocks/f2_prenotazioni/prenotazioni.php');
$PAGE->set_title(get_string('prenotazioni', 'block_f2_prenotazioni'));
$PAGE->settingsnav;

$str = <<<'EFO'
<script type="text/javascript">
//<![CDATA[

function confirmSubmit(i,name_button)
{
if(name_button == "Annulla")
var testo="Continuando annullerai la prenotazione.\nProseguire?";
else
var testo="Continuando verrà confermata la prenotazione.\nProseguire?";
		var agree = window.confirm(testo);
		if (agree)
			 document.getElementById('mform'+i).submit();
		else
			return false;

}
//]]>
</script>
EFO;

echo $str;

$PAGE->set_heading($SITE->shortname.': '.$blockname);

$userdata = get_user_data($userid);
$settore = get_user_organisation($userid);
$settore_id = $settore[0];
$objsettore = get_organisation_info_by_id($settore_id);
$objdirezione = get_organisation_info_by_id($objsettore->parentid);
$settore_nome = (!isset($objdirezione->fullname) or is_null($objsettore->fullname) or empty($objdirezione->fullname)) ? 'n.d.' : "$objsettore->shortname - $objsettore->fullname";
$direzione_nome = (!isset($objdirezione->fullname) or is_null($objdirezione->fullname) or empty($objdirezione->fullname)) ? 'n.d.' : $objdirezione->shortname." - ".$objdirezione->fullname;
$user_cohort = get_user_cohort_by_category($userid);
// TABELLA DATI ANAGRAFICI
$table = new html_table();
$table->align = array('right', 'left');
$table->data = array(
		array('Cognome Nome ','<b>'.$userdata->lastname.' '.$userdata->firstname.'</b>'),
		array('Matricola',''.$userdata->idnumber.''),
		array('Categoria',''.$userdata->category.''),
		array('Direzione / Ente',''.$direzione_nome.''),
		array('Settore',''.$settore_nome.'')
);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('prenotazioni', 'block_f2_prenotazioni'));

echo $OUTPUT->box_start();
print_tab_prenotazioni('prenotazioni',$userid,$prenota_altri);
// INIZIO TABELLA DATI ANAGRAFICI
echo "<h3>".get_string('dettagli_utente', 'block_f2_apprendimento')."</h3>";

echo html_writer::table($table);
// FINE TABELLA DATI ANAGRAFICI

class insert_prenotazione_form extends moodleform {
	public function definition() {
		$mform2 		=& $this->_form;
		$mform2->addElement('hidden', 'userid', $this->_customdata['userid']);
		$mform2->addElement('hidden', 'pa', $this->_customdata['pa']);
		$mform2->addElement('hidden', 'victimid', $this->_customdata['victimid']);
		if ($this->_customdata['sedeid']) 
			$mform2->addElement('hidden', 'sedeid', $this->_customdata['sedeid']);
// 		if ($this->_customdata['usrname'])
// 			$mform2->addElement('hidden', 'usrname', $this->_customdata['usrname']);
		$mform2->addElement('hidden', 'idcorso', $this->_customdata['idcorso']);
		$mform2->addElement('hidden', 'cf', $this->_customdata['cf']);
		$mform2->addElement('hidden', 'durata', $this->_customdata['durata']);
		$mform2->addElement('hidden', 'costo', $this->_customdata['costo']);
		$mform2->addElement('hidden', 'anno', $this->_customdata['anno']);
		$mform2->addElement('hidden', 'sf', $this->_customdata['sf']);
		$mform2->addElement('hidden', 'funzione', $this->_customdata['funzione']);
		$mform2->addElement('hidden', 'page', $this->_customdata['page']);
		$mform2->addElement('hidden', 'perpage', $this->_customdata['perpage']);
		$mform2->addElement('hidden', 'column', $this->_customdata['column']);
		$mform2->addElement('hidden', 'sort', $this->_customdata['sort']);
		$mform2->addElement('hidden', 'prid', $this->_customdata['prid']);
		$buttonarray=array();
		$buttonarray[] = &$mform2->createElement('submit', 'submitbutton', get_string('conferma', 'block_f2_prenotazioni'));
		$buttonarray[] = &$mform2->createElement('cancel', 'cancelbutton', get_string('annulla', 'block_f2_prenotazioni'));
		$mform2->addGroup($buttonarray, 'buttonar2', '', array(' '), false);
	}
}

if ($action == 'show')
{
	//INIZIO Form
	class ricerca_corso_form extends moodleform {
		public function definition() {
			$mform =& $this->_form;
			$mform->addElement('hidden', 'userid', $this->_customdata['userid']);
			$mform->addElement('hidden', 'pa', $this->_customdata['pa']);
			$mform->addElement('text', 'search_course',get_string('search_corso', 'block_f2_prenotazioni'), 'maxlength="254" size="50"');
			$mform->addElement('submit', 'submitbtn', get_string('search_corso_btn', 'block_f2_prenotazioni'));
		}
	}
	$mform = new ricerca_corso_form(null,array('userid'=>$userid,'pa'=>$prenota_altri));
	$mform->set_data(array('search_course' => $search_corso));
	$mform->display();
	//FINE Form
	$pagination = array('perpage' => $perpage, 'page'=>$page,
			'column'=>$column,'sort'=>$sort
			,'funzione'=>'show','userid'=>$userid,'pa' => $prenota_altri);
	$form_id='mform1';										// ID del form dove fare il submit
	$post_extra=array('column'=>$column,'sort'=>$sort,'funzione'=>'show',
			'userid'=>$userid,'search_corso'=>$search_corso);
	$cohort_str = $user_cohort->cohortid;
	$data = $mform->get_data();
	if ($data) 
	{
		$data->cohorts = $cohort_str;
	}
	else 
	{
		$data = new stdClass;
		$data->cohorts = $cohort_str;
	}
	foreach ($pagination as $key=>$value)
	{
		$data->$key = $value;
	}
	$full_user_catalogo = get_user_catalogo_corsi($data);
	$user_catalogo = $full_user_catalogo->dati;
	$total_rows = $full_user_catalogo->count;
	
	$sett_closed = is_dominio_closed($objsettore->id,'sett');
	
	if($total_rows > 0) {
		// TABELLA MYCOURSES
		$table = new html_table();
		$table->width = '100%';
		$head_table = array('codice','titolo','segmento_formativo','sede_corso','prenota','stato');
		$head_table_sort = array('codice');
		$align = array ('center','center','center','center','center','center');
	
		$table->align = $align;
		$table->head = build_head_table($head_table,$head_table_sort,$post_extra,$total_rows, $page, $perpage, $form_id);
		$i = 99;
		$orgid = 0;
		$isdeleted = 0;
		foreach ($user_catalogo as $c)
		{
			if ($c->prid_vals_vald_sid_sdesc !== '-1')
			{
				$prid_vals_vald_sid_sdesc_arr = array();
				$prid_vals_vald_sid_sdesc_arr = explode('#',$c->prid_vals_vald_sid_sdesc);
				$prid = $prid_vals_vald_sid_sdesc_arr[0];
				$vals = $prid_vals_vald_sid_sdesc_arr[1];
				$vald = $prid_vals_vald_sid_sdesc_arr[2];
				$sid = $prid_vals_vald_sid_sdesc_arr[3];
				$sdesc = $prid_vals_vald_sid_sdesc_arr[4];
				$orgid = $prid_vals_vald_sid_sdesc_arr[5];
				$isdeleted = intval($prid_vals_vald_sid_sdesc_arr[6]);
			}
			else 
			{
				$prid = '-1';
				$vals = '-1';
				$vald = '-1';
				$sid = '-1';
				$sdesc = '-1';
				$isdeleted = 0;
			}
			$form_open = '<form action="manage_prenotazioni.php?submitbutton='.get_string('conferma', 'block_f2_prenotazioni').'" method="post" accept-charset="utf-8" id="mform'.$i.'" name="mform'.$i.'">';
// 			echo 'isdeleted: '.$isdeleted.' prid: '.$c->rownum.'<br/>';
			if ($prid === '-1' or $isdeleted === 1) 
			{
				$str_btn = get_string('prenota', 'block_f2_prenotazioni');
				$hidden_value = $c->userid;
			}
			else 
			{
				$str_btn = get_string('annulla', 'block_f2_prenotazioni');
				$hidden_value = $prid;
			}
			$hidden_param = '<input type="hidden" name ="userid" value="'.$userid.'"/>';
			$hidden_param .= '<input type="hidden" name ="pa" value="'.$prenota_altri.'"/>';
			$hidden_param .= '<input type="hidden" name ="funzione" value="'.$str_btn.'"/>';
			$hidden_param .= '<input type="hidden" name ="victim_id" value="'.$hidden_value.'"/>';
			$hidden_param .= '<input type="hidden" name ="id_corso" value="'.$c->courseid.'"/>';
			$hidden_param .= '<input type="hidden" name ="titolo_corso" value="'.$c->titolo.'"/>';
			$hidden_param .= '<input type="hidden" name ="codice_corso" value="'.$c->codice.'"/>';
			$hidden_param .= '<input type="hidden" name ="cf" value="'.$c->cf.'"/>';
			$hidden_param .= '<input type="hidden" name ="durata" value="'.$c->durata.'"/>';
			$hidden_param .= '<input type="hidden" name ="anno" value="'.$c->anno.'"/>';
			$hidden_param .= '<input type="hidden" name ="costo" value="'.$c->costo.'"/>';
			$hidden_param .= '<input type="hidden" name ="sf" value="'.$c->segmento_formativo.'"/>';
// 			$hidden_param .= '<input type="hidden" name ="usrname" value="'.$c->usrname.'"/>';
			$hidden_param .= '<input type="hidden" name ="column" value="'.$column.'"/>';
			$hidden_param .= '<input type="hidden" name ="sort" value="'.$sort.'"/>';
			$hidden_param .= '<input type="hidden" name ="page" value="'.$page.'"/>';
			$hidden_param .= '<input type="hidden" name ="perpage" value="'.$perpage.'"/>';
			$hidden_param .= '<input type="hidden" name ="prid" value="'.$prid.'"/>';
			$btn_row = '<input type="submit" value="'.$str_btn.'" onClick="return confirmSubmit('.$i.',\''.$str_btn.'\')"/>';
			$form_close = '</form>';
	
			$sedi_select_open = '<div class="felement fselect"><select name="sede_corso" id="sede_id_'.$i.'" onchange="document.getElementById(\'sede_select'.$i.'\').value = this.value;">';
			$opt = '';
			if ($prid === '-1' or $isdeleted === 1)
			{
				$sedi_corso = get_sedi_from_corso($c->courseid);
				if (count($sedi_corso) > 1)
				{
                                        $sede1 = '';
					foreach($sedi_corso as $s)
					{
                                            if ($sede1 == '') {
                                                $sede1 = $s->id;
                                            }
						// 			$selected = '';
						// 			if ($c->sede_prenotazione === $s->id) $selected = ' selected="selected"';
						$opt .= '<option value="'.$s->id.'">'.$s->citta.'</option>';
					}
					$sedi_select_close = '</select></div>';
//					$sede_row = $form_open.$sedi_select_open.$opt.$sedi_select_close;
					$sede_row = $sedi_select_open.$opt.$sedi_select_close;
                                        $hidden_param .= '<input type="hidden" name="sede_select" id="sede_select'.$i.'" value="'.$sede1.'"/>';
					$prenota_row = $form_open.$hidden_param.$btn_row.$form_close;
				}
				else
				{
					foreach($sedi_corso as $s) // cicla 1 volta sola
					{
						$sede_row = $s->citta.' ('.$s->id.')';
						$hidden_param .= '<input type="hidden" name ="sede_select" id="sede_select'.$i.'" value="'.$s->id.'"/>';
						$prenota_row = $form_open.$hidden_param.$btn_row.$form_close;
					}
				}

			}
			else 
			{
				$sede_row = $sdesc.' ('.$sid.')';
				$hidden_param .= '<input type="hidden" name ="sede_select" id="sede_select'.$i.'" value="'.$sid.'"/>';
				$prenota_row = $form_open.$hidden_param.$btn_row.$form_close;
			}
			$stato_str = '';

			if (($vals !== '-1') and ($vald !== '-1'))
			{
				$stato_str = get_stato_prenotazione_str($vals,$vald,$orgid,0,$prid);
			}
			$sf_str = $c->segmento_formativo.' - '.$c->sf_descrizione; 
			$pdf_url = new moodle_url("/local/f2_course/pdf/select_contratto_formativo.php?courseid=".$c->courseid);
			$titolo_str = "<a onclick=\"window.open('".$pdf_url."', '', 'width=620,height=450,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes'); return false;\" href=\"".$pdf_url."\">$c->titolo</a>";
			$table->data[] = array(
					$c->codice,
// 					$c->titolo,
					$titolo_str,
					$sf_str,
					$sede_row,
					$prenota_row,
					$stato_str
			);
			$i++;
		}
		
		//INIZIO TABELLA BOTTONI
		$btn_row = array();
		$btn_row[] = '<input type="button" value="'.get_string('print', 'block_f2_prenotazioni').'" onClick="window.print()"/>';
		$btn_table = '<table align="left" width="10%"><tr>';
		$buttemp='';
		foreach ($btn_row as $b)
		{
			$buttemp = $buttemp.'<td>'.$b.'</td>';
		}
		$btn_table = $btn_table.$buttemp.'</tr></table><br/>';
		echo $btn_table;
		//FINE TABELLA BOTTONI
	
		// INIZIO TABELLA DATI
		echo "<br/><br/><br/><b style='font-size:11px'>Totale corsi $total_rows</b>";
		$paging_bar = new paging_bar_f2($total_rows, $page, $perpage, $form_id, $post_extra);
		echo $paging_bar->print_paging_bar_f2();
//		$form = '<form autocomplete="off" action="prenotazioni.php" method="post" accept-charset="utf-8" id="mform1" class="mform">';
//		echo $form;
		echo html_writer::table($table);
//		echo '</form>';
		echo $paging_bar->print_paging_bar_f2();
		///FINE TABELLA DATI
	}
	else //empty
	{
		echo '<br/><br/><p>'.get_string('noresults', 'block_f2_prenotazioni').'</p><br/>';
	}
}
else if ($action == get_string('prenota', 'block_f2_prenotazioni')) //aggiungi prenotazione
{
//	print_r($_POST);exit;
	
	
/*	
	$victimid     = required_param('victimid', PARAM_INT);
	
	$reopen_sett = 0;
	$userid = required_param('userid', PARAM_INT);
	
	
	if($userid==0) $userid=$USER->id;
	else if($userid!=0 && validate_own_dipendente($userid)) {
		if (has_capability('block/f2_prenotazioni:editprenotazioni', get_context_instance(CONTEXT_BLOCK, $blockid)) || has_capability('block/f2_prenotazioni:editmieprenotazioni', get_context_instance(CONTEXT_BLOCK, $blockid))) {
			$userid=$userid;
		}
	}
	else die();
	
	
	
	
	$full_dominio = get_user_organisation($userid);
	
	// salva dati su DB e poi redirect
	$updt = new stdClass;
	
	$sedeid     = required_param('sedeid', PARAM_TEXT);
	$cf = required_param('cf', PARAM_TEXT);
	$sf = required_param('sf', PARAM_TEXT);
	$durata = required_param('durata', PARAM_TEXT);
	$costo = required_param('costo', PARAM_TEXT);
	$anno = required_param('anno', PARAM_INT);
	$prid = intval(required_param('prid', PARAM_TEXT));
	
	if (!is_null($full_dominio))
	{
		$domid = $full_dominio[0];
	}
	else
	{
		$domid=-1;
	}
	
	// 		if (!canManageDomain($domid)) die();
	
	$updt->anno = $anno;
	
	$updt->courseid = $idcorso;
	$updt->userid = $victimid;
	
	$updt->orgid = $domid;
	
	$updt->data_prenotazione = time();
	
	$updt->cf = $cf;
	$updt->sfid = $sf;
	$updt->costo = $costo;
	$updt->durata = $durata;
	$updt->lstupd = time();
	$updt->usrname = $USER->username;
	$updt->sede = $sedeid;
	
	$updt->validato_sett = 0;
	$updt->val_sett_by = null;
	$updt->val_sett_dt = null;
	$updt->validato_dir  = 0;
	$updt->val_dir_by = null;
	$updt->val_dir_dt = null;
	$updt->isdeleted = 0;
	$updt->id = $prid;
	$inserted_id = insert_prenotazione($updt);
	if (validazioni_aperte())
		$reopen_sett++;
	*/
	//INIZIO:PAGINA CONFERMA------------DECOMMENTARE SE SI VUOLE LA PAGINA DI CONFERMA--------
	
	$victimid     = required_param('victim_id', PARAM_INT);
	$sedeid     = required_param('sede_select', PARAM_TEXT);
	$idcorso = required_param('id_corso', PARAM_INT);
	$cf = required_param('cf', PARAM_TEXT);
	$sf = required_param('sf', PARAM_TEXT);
	$durata = required_param('durata', PARAM_TEXT);
	$costo = required_param('costo', PARAM_TEXT);
	$anno = required_param('anno', PARAM_INT);
	$codcorso = required_param('codice_corso', PARAM_TEXT);
	$titcorso = required_param('titolo_corso', PARAM_TEXT);
// 	$usrname = required_param('usrname', PARAM_TEXT);
	$prenot_id = required_param('prid', PARAM_TEXT);
	$page     = optional_param('page', 0, PARAM_INT);
	$perpage  = optional_param('perpage', 10, PARAM_INT);
	$column   = optional_param('column', 'codice', PARAM_TEXT);
	$sort     = optional_param('sort', 'ASC', PARAM_TEXT);
	
	// TABELLA DATI prenotazione
	$table = new html_table();
	$table->align = array('right', 'left');
	$table->data = array(
			array('Codice Corso: ','<b>'.$codcorso.'</b>'),
			array('Titolo Corso',''.$titcorso.''),
			array('Codice Sede',''.$sedeid.'')
	);
	echo "<h3>".get_string('conferma_aggiunta', 'block_f2_prenotazioni')."</h3>";
	
	echo html_writer::table($table);
	
	$mform_insert = new insert_prenotazione_form('manage_prenotazioni.php',
			array('pa' => $prenota_altri,'userid' => $userid,'funzione' => $action,'victimid' => $victimid
					,'sedeid' => $sedeid, 'idcorso' => $idcorso,'cf' => $cf, 'sf' => $sf
					,'costo' => $costo, 'durata' => $durata, 'anno' => $anno, 'prid' => $prenot_id
					,'page' => $page,'perpage' => $perpage, 'column' => $column,'sort' => $sort, 'usrname' => $usrname),'post',NULL,array());
	$mform_insert->display();	

	//FINE:PAGINA CONFERMA

}
else if ($action == get_string('annulla', 'block_f2_prenotazioni')) //cancella prenotazione
{
	$victimid     = required_param('victim_id', PARAM_INT);
	$sedeid     = required_param('sede_select', PARAM_TEXT);
	$idcorso = required_param('id_corso', PARAM_INT);
	$codcorso = required_param('codice_corso', PARAM_TEXT);
	$titcorso = required_param('titolo_corso', PARAM_TEXT);
	$page     = optional_param('page', 0, PARAM_INT);
	$perpage  = optional_param('perpage', 10, PARAM_INT);
	$column   = optional_param('column', 'codice', PARAM_TEXT);
	$sort     = optional_param('sort', 'ASC', PARAM_TEXT);

	// TABELLA DATI prenotazione
	$table = new html_table();
	$table->align = array('right', 'left');
	$table->data = array(
			array('Codice Corso: ','<b>'.$codcorso.'</b>'),
			array('Titolo Corso',''.$titcorso.''),
			array('Codice Sede',''.$sedeid.'')
	);
	echo "<h3>".get_string('conferma_cancellazione', 'block_f2_prenotazioni')."</h3>";
	
	echo html_writer::table($table);
	
	$mform_insert = new insert_prenotazione_form('manage_prenotazioni.php',array('pa' => $prenota_altri,'userid' => $userid,'funzione' => $action,'victimid' => $victimid, 'idcorso' => $idcorso,'page' => $page,'perpage' => $perpage, 'column' => $column,'sort' => $sort),'post',NULL,array());
	$mform_insert->display();
}
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
