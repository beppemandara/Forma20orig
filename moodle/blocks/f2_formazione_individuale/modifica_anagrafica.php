<?php

//$Id$
global $OUTPUT, $PAGE, $SITE, $CFG, $DB;

require_once '../../config.php';
require_once($CFG->dirroot.'/f2_lib/management.php');
require_once('lib.php');
require_once($CFG->dirroot.'/local/f2_support/lib.php');
require_once('gestionecorsi_form.php');
require_once($CFG->dirroot.'/blocks/f2_gestione_risorse/lib.php');
require_login();

$context = context_system::instance();

$userid    = required_param('userid', PARAM_INT);
$training  = required_param('training', PARAM_ALPHA);
$id_course = required_param('id_course', PARAM_INT);
$mod       = optional_param('mod', 0, PARAM_INT); // Se abilitata la modifica = 1.
$copy      = optional_param('copy',0, PARAM_INT); // Se abilitato copia il corso con id = $id_course e userid = $userid 

$debug = 0;

if ($debug == 1) {
    corsiindlog(array('START Parametri'));
    corsiindlog(array('User: '.$userid));
    corsiindlog(array('Training: '.$training));
    corsiindlog(array('Id corso: '.$id_course));
    corsiindlog(array('Mod: '.$mod));
    corsiindlog(array('Copy: '.$copy));
    corsiindlog(array('END Parametri'));
}
$label_training = get_lable_training($training);
$gestionecorsi_url = new moodle_url('/blocks/f2_formazione_individuale/gestione_corsi.php?training='.$training.'&mod='.$mod);
$blockname = get_string('pluginname', 'block_f2_formazione_individuale');

$param_CIG = get_parametro('p_f2_corsi_individuali_giunta');
$param_CIL = get_parametro('p_f2_corsi_individuali_lingua_giunta');
$param_CIC = get_parametro('p_f2_corsi_individuali_consiglio');
$param_param_corsi_lingua = get_parametro('p_f2_tipo_pianificazione_1'); // Corsi di lingua con insegnante
$param_param_corsi_ind = get_parametro('p_f2_tipo_pianificazione_2'); // Corsi Individuali

$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$url = new moodle_url("{$CFG->wwwroot}/blocks/f2_formazione_individuale/modifica_anagrafica.php", array('userid' => $userid,'training'=>$training,'mod'=>$mod,'id_course'=>$id_course));
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('modifica_dati_corso', 'block_f2_formazione_individuale'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string($label_training, 'block_f2_formazione_individuale'));
$PAGE->navbar->add(get_string('modificacorsi', 'block_f2_formazione_individuale'), $gestionecorsi_url);
$PAGE->navbar->add(get_string('creazioneanagrafica', 'block_f2_formazione_individuale'));
$PAGE->navbar->add(get_string('modifica_dati_corso', 'block_f2_formazione_individuale'), $url);
$PAGE->set_heading($SITE->shortname.': '.$blockname);

$capability_giunta = has_capability('block/f2_formazione_individuale:individualigiunta', $context);
$capability_linguagiunta = has_capability('block/f2_formazione_individuale:individualilinguagiunta', $context);
$capability_consiglio = has_capability('block/f2_formazione_individuale:individualiconsiglio', $context);

if(!(($capability_giunta && $training == $param_CIG->val_char) || ($capability_linguagiunta && $training == $param_CIL->val_char) || ($capability_consiglio && $training == $param_CIC->val_char))){
	print_error('nopermissions', 'error', '', 'formazione_individuale');
}

$PAGE->requires->js('/blocks/f2_formazione_individuale/js/module.js');

// inizio import per generazione tabella //
$PAGE->requires->css('/f2_lib/jquery/css/dataTable.css');
$PAGE->requires->css('/f2_lib/jquery/css/ui_custom.css');
$PAGE->requires->js('/f2_lib/jquery/jquery-1.7.1.min.js');
$PAGE->requires->js('/f2_lib/jquery/jquery.dataTables.js');
$PAGE->requires->js('/f2_lib/jquery/custom.js');
$PAGE->requires->js('/f2_lib/jquery/jquery.blockUI.js');
// fine import per generazione tabella //

$jsmodule = array(
		'name'  =>  'f2_formazione_individuale',
		'fullpath'  =>  '/blocks/f2_formazione_individuale/js/module.js',
		'requires'  =>  array('base', 'attribute', 'node', 'datasource-io', 'datasource-jsonschema', 'node-event-simulate', 'event-key')
);
$jsdata = array(
		sesskey()
);
$PAGE->requires->js_init_call('M.f2_formazione_individuale.init', $jsdata, true, $jsmodule);

$codice_fiscale = $DB->get_field('user', 'username', array('id' => $userid));
$user = get_forzatura_or_moodleuser($codice_fiscale);

echo $OUTPUT->header();
$str = '
<script type="text/javascript">
function confirm_back(value_alert)
{
		var txt_alert=\'\';
		if(value_alert ==\'mod\')
			txt_alert =\'Proseguendo non verranno salvati eventuali campi modificati.\nProseguire?\';
		if(value_alert ==\'copy\')
			txt_alert =\'Tornando indietro non verrà copiato nessun corso.\nProseguire?\';
		
		var agree=confirm(txt_alert);
			if (agree){
				if(value_alert ==\'copy\'){
						parent.location=\'user_copy.php?training='.$training.'&id_course='.$id_course.'\';
				}else{
						parent.location=\'gestione_corsi.php?training='.$training.'&mod=1\';
					}
			}		
			else
				return false ;
}
						
						function validateInput () {
		var titolo = document.getElementById(\'id_titolo\').value;
		var durata = document.getElementById(\'id_durata\').value;
		var costo = document.getElementById(\'id_costo\').value;
		var cf = document.getElementById(\'id_credito_formativo\').value;
		var ente = document.getElementById(\'id_ente\').value;
		var localita = document.getElementById(\'id_localita\').value;
		
		var id_sf = document.getElementById(\'id_sf\').value;
		var id_af = document.getElementById(\'id_af\').value;
		var id_subaf = document.getElementById(\'id_subaf\').value;
		var id_tipologia_organizzativa = document.getElementById(\'id_tipologia_organizzativa\').value;
		var id_tipo = document.getElementById(\'id_tipo\').value;
if(titolo=="" || durata==""  || costo=="" || cf=="" || ente=="" || durata=="" || localita=="" || id_sf==""  || id_af==""  || id_subaf ==""  || id_tipologia_organizzativa =="" || id_tipo ==""  ){
	alert("Non tutti i campi obbligatori sono stati compilati.");
}

}

</script>';
echo $str;
echo $OUTPUT->heading(get_string('datiutente', 'block_f2_formazione_individuale'));

echo '<div class="userprofile">';
echo '<div class="userprofilebox clearfix"><div class="profilepicture">';
echo $OUTPUT->user_picture($user, array('size'=>100));
echo '</div>';
// Print all the little details in a list
echo '<table class="list" summary="">';
$override = new stdClass();
$override->firstname = 'firstname';
$override->lastname = 'lastname';
$fullnamelanguage = get_string('fullnamedisplay', '', $override);
if (($CFG->fullnamedisplay == 'firstname lastname') or
    ($CFG->fullnamedisplay == 'firstname') or
    ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'firstname lastname' )) {
    $fullnamedisplay = get_string('firstname').' / '.get_string('lastname');
} else { // ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'lastname firstname')
    $fullnamedisplay = get_string('lastname').' / '.get_string('firstname');
}
print_row($fullnamedisplay . ':', fullname($user, true));
print_row(get_string('matricola', 'block_f2_formazione_individuale') . ':', $user->idnumber);
print_row(get_string('sesso', 'block_f2_formazione_individuale') . ':', $user->sesso);
print_row(get_string('categoria', 'block_f2_formazione_individuale') . ':', $user->category);
print_row(get_string('direzione', 'block_f2_formazione_individuale') . ':', $user->cod_direzione.' - '.$user->direzione);
print_row(get_string('settore', 'block_f2_formazione_individuale') . ':', $user->cod_settore.' - '.$user->settore);

echo "</table></div></div></br>";

echo $OUTPUT->heading(get_string('dati_corso', 'block_f2_formazione_individuale'));

$form = new inserisci_daticorso_form(NULL, compact('gestione_corsi', 'training', 'userid','id_course','copy','mod'));

// anno finanziario
//$anno_finanziario = get_anno_finanziario($id_course);

if (!$form->get_data()) {
    
if ($debug == 1) {
    corsiindlog(array('Step Iniziale: non ci sono dati'));
}
  // anno finanziario
  $anno_finanziario = get_anno_finanziario($id_course);
  if ($debug == 1) {
    corsiindlog(array('Anno finanziario: '.$anno_finanziario));
  }

	$dati_corso=get_corso_ind($id_course);
	
	if($copy){
		$form->set_data( array('userid' => $userid));
		//$form->set_data( array('codice_determina' => NULL));       //AGGIUNGERE
		//$form->set_data( array('stato_determina' => NULL));       //AGGIUNGERE
		//$form->set_data( array('codice_provvisorio' => NULL));       //AGGIUNGERE
		//$form->set_data( array('storico' => NULL));       //AGGIUNGERE
		//$form->set_data( array('data_invio_mail' => NULL));       //AGGIUNGERE
	}else{
		$form->set_data( array('userid' => $dati_corso->userid));
		//$form->set_data( array('codice_determina' => $dati_corso->codice_determina));      //AGGIUNGERE
		//$form->set_data( array('stato_determina' => $dati_corso->codice_provvisorio));       //AGGIUNGERE
		//$form->set_data( array('codice_provvisorio' => $dati_corso->codice_provvisorio));       //AGGIUNGERE
		//$form->set_data( array('storico' => $dati_corso->storico));       //AGGIUNGERE
		//$form->set_data( array('data_invio_mail' => $dati_corso->data_invio_mail));       //AGGIUNGERE	
	}
	
	$form->set_data( array('training' => $dati_corso->training));
	$form->set_data( array('codice_fiscale' => $dati_corso->codice_fiscale));
	$form->set_data( array('partita_iva' => $dati_corso->partita_iva));
	$form->set_data( array('orgfk' => $dati_corso->orgfk));
	
	$form->set_data( array('note' => $dati_corso->note));
	$form->set_data( array('beneficiario_pagamento' => $dati_corso->beneficiario_pagamento));
	$form->set_data( array('cassa_economale' => $dati_corso->cassa_economale));
	$form->set_data( array('titolo' => $dati_corso->titolo));
	$form->set_data( array('costo' => $dati_corso->costo));
        $form->set_data( array('finanziario' => $anno_finanziario)); // anno finanziario
	$form->set_data( array('af' => $dati_corso->area_formativa));
	$form->set_data( array('tipologia_organizzativa' => $dati_corso->tipologia_organizzativa));
	$form->set_data( array('tipo' => $dati_corso->tipo));
	$form->set_data( array('durata' => $dati_corso->durata));
	$form->set_data( array('ente' => $dati_corso->ente));
	$form->set_data( array('via' => $dati_corso->via));
	$form->set_data( array('localita' => $dati_corso->localita));
	$form->set_data( array('subaf' => $dati_corso->sotto_area_formativa));
	$form->set_data( array('data_inizio' => $dati_corso->data_inizio));
	$form->set_data( array('credito_formativo' => $dati_corso->credito_formativo));
//	$form->set_data( array('sesso' => $dati_corso->sesso));
	$form->set_data( array('sf' => $dati_corso->segmento_formativo));
	$form->set_data( array('modello_email' => $dati_corso->modello_email));
	$form->set_data( array('codice_archiviazione' => $dati_corso->codice_archiviazione));
	$form->set_data( array('codice_creditore' => $dati_corso->codice_creditore));
        // 2018/04/20
        $form->set_data( array('offerta_speciale' => $dati_corso->offerta_speciale));
        $form->set_data( array('note_offerta' => $dati_corso->note_offerta));
	// 2018/04/20
} else if ($data = $form->get_data()) {
    if ($debug == 1) {
        corsiindlog(array('Step Successivo: ci sono dati'));
    }
    $data = $form->get_data();
    
    $copia = $data->copy;

    $corso_ind_new = new stdClass();
    
    if(isset($data->cassa_economale)){
    	$cassa_economale=$data->cassa_economale;
    }else{
    	$cassa_economale=0;
    }

    // 2018/04/20
    if (isset($data->offerta_speciale)) {
        $offerta_speciale = $data->offerta_speciale;
    } else {
        $offerta_speciale = 0;
    }
    // 2018/04/20
	
    $corso_ind_new->training = $data->training;
    $corso_ind_new->codice_fiscale = $data->codice_fiscale;
    $corso_ind_new->partita_iva = $data->partita_iva;
    $corso_ind_new->orgfk = $user->orgfk_direzione; // da forzatura
    $corso_ind_new->userid = $data->userid;
    $corso_ind_new->note = $data->note;
    $corso_ind_new->beneficiario_pagamento = $data->beneficiario_pagamento;
    $corso_ind_new->cassa_economale = $cassa_economale;
    $corso_ind_new->titolo = $data->titolo;
    $corso_ind_new->costo = $data->costo;
    //$corso_ind_new->finanziario = $data->finanziario; // 2018 09 17
    $corso_ind_new->area_formativa = $data->af;
    $corso_ind_new->tipologia_organizzativa = $data->tipologia_organizzativa;
    $corso_ind_new->tipo = $data->tipo;
    $corso_ind_new->durata = $data->durata;
    $corso_ind_new->ente = $data->ente;
    $corso_ind_new->via = $data->via;
    $corso_ind_new->localita = $data->localita;
    $corso_ind_new->sotto_area_formativa = $data->subaf;
    $corso_ind_new->data_inizio = $data->data_inizio;
    $corso_ind_new->credito_formativo = $data->credito_formativo;
    $corso_ind_new->sesso = $user->username;
    $corso_ind_new->segmento_formativo = $data->sf;
    $corso_ind_new->modello_email = $data->modello_email;
    $corso_ind_new->codice_archiviazione = $data->codice_archiviazione;
    $corso_ind_new->codice_creditore = $data->codice_creditore;
    // 2018 04 06
    // Inserire i valori della eventuale determina
    // 2018 05 08 commentato su richiesta del cliente, la copia del corso determinato NON deve avere
    // il numero di determina in modo da comparire in Gestione Corsi
    //$pardet = array('id' => $id_course);
    //$corso_ind_new->id_determine = $DB->get_field('f2_corsiind', 'id_determine', $pardet);
    // 2018 04 06
    // 2018 04 20
    //$corso_ind_new->offerta_speciale = $data->offerta_speciale;
    $corso_ind_new->offerta_speciale = $offerta_speciale;
    $corso_ind_new->note_offerta = $data->note_offerta;
    // 2018 04 20
    
    if ($data->training == $param_CIL->val_char) {
        $corso_ind_new->tipo_pianificazione = $param_param_corsi_lingua->val_char;
    } else {
        $corso_ind_new->tipo_pianificazione = $param_param_corsi_ind->val_char;
    }

   // print_r($copia."copia");exit;
   //print_r($corso_ind_new);exit;
    
   // Se e' settato il flag per la copia faccio una insert altrimenti faccio una modifica.
   if ($copia) {
        if ($debug == 1) {
            corsiindlog(array('Flag per la copia impostato'));
        }
        // Inizio transazione.
        $transaction = $DB->start_delegated_transaction();
        // Inserimento record.       
   	$ret = $DB->insert_record('f2_corsiind', $corso_ind_new); // da controllare

        if ($debug == 1) {
            corsiindlog(array('Tab f2_corsiind - Insert record: '.$ret));
        }

   	$ret_mod = "&ret_cp=1";
   	$modul = "copia";
        // anno finanziario - ottengo l'id del nuovo corso
        //$lastId = $DB->get_field('f2_corsiind', 'id', array("userid" => $data->userid, "costo" => $data->costo, "titolo" => $data->titolo, "area_formativa" => $data->af, "sotto_area_formativa" => $data->subaf));
        if ($ret) {
            if ($debug == 1) {
                corsiindlog(array('Ret: '.$ret));
            }

            $corso_ind_anfin = new stdClass();
            //$corso_ind_anfin->id_corsiind = $lastId;
            $corso_ind_anfin->id_corsiind = $ret;
            $corso_ind_anfin->data = date("Y-m-d H:i:s");
            $corso_ind_anfin->anno = $data->finanziario;
            $res_anfin = $DB->insert_record('f2_corsiind_anno_finanziario', $corso_ind_anfin);
            if ($res_anfin) {
                $transaction->allow_commit();
                if ($debug == 1) {
                    corsiindlog(array('Tab f2_corsiind_anno_finanziario - Insert record: '.$res_anfin));
                }
            } else {
                $transaction->rollback($res_anfin);
            }
        } else {
            $transaction->rollback($ret);
        }
   } else {
        if ($debug == 1) {
            corsiindlog(array('Flag per la modifica impostato'));
        }

   	$corso_ind_new->id = $data->id_course;
        // Inizio transazione.
        $transaction = $DB->start_delegated_transaction();
        // Aggiornamento record.
   	$ret = $DB->update_record('f2_corsiind', $corso_ind_new);
   	$mod_conf="&mod=1";
   	$ret_mod = "&ret_mod=1";
   	$modul = "modifica";

        if ($debug == 1) {
            corsiindlog(array('Tab f2_corsiind - update record: '.$ret));
        }

        if ($ret) {
            // anno finanziario
            $corso_ind_anfin = new stdClass();
            $corso_ind_anfin->id = get_id_anno_finanziario($data->id_course);
            $corso_ind_anfin->anno = $data->finanziario;
            $upd_anfin = $DB->update_record('f2_corsiind_anno_finanziario', $corso_ind_anfin);

            if ($upd_anfin) {
                $transaction->allow_commit();
            } else {
                $transaction->rollback($upd_anfin);
            }
            if ($debug == 1) {
                corsiindlog(array('Tab f2_corsiind_anno_finanziario - update record: '.$upd_anfin));
            }
        } else {
            $transaction->rollback($ret);
        }
   }
    
   // $ret = $DB->insert_record('f2_corsiind', $corso_ind_new); // da controllare
	if ($ret)
		redirect(new moodle_url("/blocks/f2_formazione_individuale/gestione_corsi.php?training=".$training.$mod_conf.$ret_mod));
	else{
		if($modul == "modifica")
			$ret_mod = "&ret_mod=-1";
		else if($modul == "copia")
			$ret_mod = "&ret_cp=-1";
		redirect(new moodle_url("/blocks/f2_formazione_individuale/gestione_corsi.php?training=".$training.$mod_conf.$ret_mod));
	}
}
$jsmodulec = array(
		'name'  =>  'f2_course',
		'fullpath'  =>  '/local/f2_course/js/module.js',
		'requires'  =>  array('base', 'attribute', 'node', 'datasource-io', 'datasource-jsonschema', 'node-event-simulate', 'event-key')
);
$jsdatac = array(
		sesskey()
);
$PAGE->requires->js_init_call('M.f2_course.init',
		$jsdatac,
		true,
		$jsmodulec);
$form->display();

echo $OUTPUT->footer();
/*
function print_row($left, $right) {
    echo "\n<tr><th class=\"label c0\">$left</th><td class=\"info c1\">$right</td></tr>\n";
}*/
