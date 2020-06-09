<?php
global $CFG;
require_once($CFG->dirroot.'/f2_lib/management.php');
require_once($CFG->dirroot.'/lib/tcpdf/tcpdf.php');
/**
 * @param string $training Tipo corso
 * Restiuisce la stringa del tipo di corso
 */
function get_label_training($training) {
	$param_CIG = get_parametro('p_f2_corsi_individuali_giunta');
	$param_CIL = get_parametro('p_f2_corsi_individuali_lingua_giunta');
	$param_CIC = get_parametro('p_f2_corsi_individuali_consiglio');
	if ($training == $param_CIG->val_char) {
		return 'corsi_individualigiunta';
	} else if($training == $param_CIL->val_char) {
		return 'corsi_individualilinguagiunta';
	} else if($training == $param_CIC->val_char) {
		return 'corsi_individualiconsiglio';
	}
}
/**
 * @param int $course_id Id corso
 * Elimina il corso individuale con id $course_id
 */
function delete_corsi_ind($course_id) {
	global $DB;
	if ($DB->record_exists('f2_corsiind_senza_spesa', array ('id' => $course_id))) {
		$DB->delete_records('f2_corsiind_senza_spesa', array ('id'=>$course_id));
		return true;
	}
	else return false;
}
/**
 * @param obj $data array Dati corso
 * @param int $type_page Flag modifica
 * Elimina il corso individuale con id $course_id
 */
function get_corsi_ind($data,$type_page=0) {
  global $DB;
  $where = "";
  if (!$type_page) {//mi trovo nella pagina visualizza corsi individuali gestione_corsi.php
    $where = " ci.id_determine <= 0 AND ci.costo = 0 AND ci.storico <= 0 AND ";
  } else {//mi trovo nella pagina modifica corsi individuali gestione_corsi.php?mod=1
    $where = " ci.storico <= 0 AND ci.costo = 0 AND ";
  }
	$sql_query = "SELECT ci.id, u.id as userid, u.lastname as cognome, u.firstname as nome, ".
               "u.username, ci.data_inizio, ci.titolo, ci.codice_archiviazione, ci.codice_fiscale, ci.data_invio_mail, ci.modello_email, ci.ente, ci.storico, ci.prot ".
               "FROM {f2_corsiind_senza_spesa} ci, {user} u ".
               "WHERE ci.userid = u.id AND ".$where."ci.training = '".$data->tipo_corso."'";

  if (isset($data->dato_ricercato)) {
    $sql_query .= " AND (lower(u.lastname) like lower('%".$data->dato_ricercato."%')) ";
  }

  if (isset($data->dato_protocollo)) {
    $sql_query .= " AND (lower(ci.prot) like lower('%".$data->dato_protocollo."%')) ";
  }

  if ($data->ordinamento == 'orderbyprot') {
    $order = $data->columnprot." ".$data->sortprot.", ".$data->column." ".$data->sort." ,nome ".$data->sort;
  } else if ($data->ordinamento == 'orderbynome') {
    $order = "nome ".$data->sort.", ".$data->column." ".$data->sort.", ".$data->columnprot." ".$data->sortprot;
  } else {
    $order = $data->column." ".$data->sort.", nome ".$data->sort.", ".$data->columnprot." ".$data->sortprot;
  }
	$sql_query .= " ORDER BY ".$order.", data_inizio DESC";
        //add_to_log_query($sql_query);
	$results = $DB->get_records_sql($sql_query,NULL,$data->page*$data->perpage,$data->perpage);
	$results_count=$DB->count_records_sql("SELECT count(*) from ($sql_query) as tmp");

	$return        = new stdClass;
	$return->count = $results_count;
	$return->dati  = $results;
	return $return;
}
/**
 * @param obj $data array Dati corso
 * @param int $type_page Flag modifica
 * Estrae i dati dei corsi individuali archiviati
 */
function get_corsi_ind_archiviati($data,$type_page=0) {
  global $DB;
  $where = '';
  if (!$type_page) {//mi trovo nella pagina visualizza corsi individuali gestione_corsi.php
    $where = ' ci.id_determine <= 0 AND ci.costo = 0 AND ci.storico > 0 AND ';
  } else {//mi trovo nella pagina modifica corsi individuali gestione_corsi.php?mod=1
    $where = ' ci.storico > 0 AND ci.costo = 0 AND ';
  }
  $sql_query = 'SELECT ci.id, u.id as userid, u.lastname as cognome, u.firstname as nome, '.
               'u.username, ci.data_inizio, ci.titolo, ci.codice_archiviazione, ci.codice_fiscale, '.
               'ci.data_invio_mail, ci.modello_email, ci.ente, ci.storico, ci.prot '.
               "FROM {f2_corsiind_senza_spesa} ci, {user} u ".
               'WHERE ci.userid = u.id AND '.$where."ci.training = '".$data->tipo_corso."'";
  if (isset($data->dato_ricercato)) {
    $sql_query .= " AND (lower(u.lastname) like lower('%".$data->dato_ricercato."%')) ";
  }
  if (isset($data->dato_protocollo)) {
    $sql_query .= " AND (lower(ci.prot) like lower('%".$data->dato_protocollo."%')) ";
  }
  if ($data->ordinamento == 'orderbyprot') {
    $order = $data->columnprot." ".$data->sortprot.", ".$data->column." ".$data->sort." ,nome ".$data->sort;
  } else if ($data->ordinamento == 'orderbynome') {
    $order = "nome ".$data->sort.", ".$data->column." ".$data->sort.", ".$data->columnprot." ".$data->sortprot;
  } else {
    $order = $data->column." ".$data->sort.", nome ".$data->sort.", ".$data->columnprot." ".$data->sortprot;
  }
  $sql_query .= " ORDER BY ".$order.", data_inizio DESC";
  //add_to_log_query($sql_query);
  $results = $DB->get_records_sql($sql_query,NULL,$data->page*$data->perpage,$data->perpage);
  $results_count=$DB->count_records_sql("SELECT count(*) from ($sql_query) as tmp");

  $return        = new stdClass;
  $return->count = $results_count;
  $return->dati  = $results;
  return $return;
}
/**
 * @param string $codice_fiscale Codice fiscale
 * Restituisce oggetto utente
 */
function get_forzatura_or_moodleuser_ind($codice_fiscale) {
  global $DB;
  $moodleuser = $DB->get_record('user', array('username'=>$codice_fiscale), '*', MUST_EXIST);
  $forzatura = $DB->get_record('f2_forzature', array('codice_fiscale' => $codice_fiscale));
  $scaduta = false;
  if ($forzatura) {
    $now = time();
    if ($now > $forzatura->data_fine) $scaduta = true;
  }
  if (!$forzatura || $scaduta) {
    $user_org = get_user_organisation($moodleuser->id);
    if (!is_null($user_org)) {
      if (isDirezione($user_org[0])) {
        $moodleuser->cod_direzione = $user_org[2];
        $moodleuser->direzione = $user_org[1];
        $moodleuser->orgfk_direzione = $user_org[0];
      } else if (isSettore($user_org[0])) {
        $id_padre = get_dominio_padre($user_org[0]);
        $direzione = $DB->get_record('org', array('id' => $id_padre), 'shortname, fullname', MUST_EXIST);
        $moodleuser->cod_direzione = $direzione->shortname;
        $moodleuser->direzione = $direzione->fullname;
        $moodleuser->orgfk_direzione = $id_padre;
        $moodleuser->cod_settore = $user_org[2];
        $moodleuser->settore = $user_org[1];
      }
    }
    $moodleuser->sesso = get_data_profile_field_value_for_user_ind('sex', $moodleuser->id);
    $moodleuser->category = get_data_profile_field_value_for_user_ind('category', $moodleuser->id);
    $moodleuser->ap = get_data_profile_field_value_for_user_ind('ap', $moodleuser->id);
    return $moodleuser;
  } else {
    $moodleuser->idnumber = $forzatura->matricola;
    $moodleuser->cod_direzione = $forzatura->cod_direzione;
    $moodleuser->direzione = $forzatura->direzione;
    $moodleuser->orgfk_direzione = $forzatura->orgfk_direzione;
    $moodleuser->cod_settore = $forzatura->cod_settore;
    $moodleuser->settore = $forzatura->settore;
    $moodleuser->category = $forzatura->qualifica;
    $moodleuser->sesso = $forzatura->sesso;
    $moodleuser->ap = $forzatura->ap;
    $moodleuser->email = $forzatura->e_mail;
  }
  return $moodleuser;
}
/**
 * @param string $field_shortname Shortname
 * @param int Id utente
 * Restituisce oggetto Data
 */
function get_data_profile_field_value_for_user_ind($field_shortname, $userid) {
	global $DB;
	$field_id = $DB->get_field('user_info_field', 'id', array('shortname'=>$field_shortname), MUST_EXIST);
	$data = $DB->get_field('user_info_data', 'data', array('userid'=>$userid, 'fieldid'=>$field_id), MUST_EXIST);
	return $data;
}
/**
 * @param int Id corso
 * Invio mail autorizzazione
 */
function prepare_mail_autorizzazione_senza_determina($id_send) {
	global $DB, $USER, $CFG;
	// recupero dati del corso
        add_to_log_corsi_ind_senza_determina('prepare_mail_autorizzazione_senza_determina - id corso: '.$id_send);
	if (!$course_data = get_course_data_senza_determina($id_send)) {
          add_to_log_corsi_ind_senza_determina('prepare_mail_autorizzazione_senza_determina - get_course_data_senza_determina ha restituito un record vuoto');
        }
        add_to_log_corsi_ind_senza_determina('prepare_mail_autorizzazione_senza_determina - id utente: '.$course_data->userid);
	if (!$utente_corso = get_dati_utente_corso($course_data->userid)) {
           add_to_log_corsi_ind_senza_determina('prepare_mail_autorizzazione_senza_determina - get_dati_utente_corso ha restituito un record vuoto');
        }
        add_to_log_corsi_ind_senza_determina('prepare_mail_autorizzazione_senza_determina - username: '.$utente_corso->username);
	if (!$dati_corso_ind_forz = get_forzatura_or_moodleuser_ind($utente_corso->username)) {
          add_to_log_corsi_ind_senza_determina('prepare_mail_autorizzazione_senza_determina - get_forzatura_or_moodleuser_ind ha restituito un record vuoto');
        }
	$attachments_files = '';
        $file_name = '';
	$data = new stdClass();
        // CONTROLLO SU MAIL TEMPLATE PER INVIARE O MENO LA MAIL
	if ($course_data->modello_email == "-1") {
	  $data->lastname = $utente_corso->lastname;
	  $data->firstname = $utente_corso->firstname;
          $data->matricola = $utente_corso->idnumber;
          $data->titolo = $course_data->titolo;
	  $data->error_mail = 3;
	} else {
          $if_attachment = if_notif_attachment($course_data->modello_email);
          // Controllo se debba essere creato il pdf
          if ($if_attachment->attachment) {
            $replace = array(
                           $utente_corso->lastname,
                           $utente_corso->firstname,
                           $dati_corso_ind_forz->category,
                           $dati_corso_ind_forz->direzione,
                           $dati_corso_ind_forz->settore,
                           $course_data->titolo,
                           $course_data->localita,
                           number_format($course_data->durata,2,",","."),
                           date("d/m/Y",$course_data->data_inizio),
                           $course_data->ente,
                           0,
                           //$dati_corso_ind->beneficiario_pagamento,
                           //$dati_corso_ind->partita_iva,
                           //$dati_corso_ind->codice_fiscale,
                           //$dati_corso_ind->codice_creditore,
                           $course_data->note,
                           '',
                           $dati_corso_ind_forz->idnumber,
                           //$dati_determina->codice_determina,
                           //date("d/m/Y",$dati_determina->data_determina),
                           $dati_corso_ind_forz->cod_direzione,
                           $dati_corso_ind_forz->cod_settore
                          );
            $find = array(
                        "[Cognome]",
                        "[Nome]",
                        "[Qualifica]",
                        "[Direzione]",
                        "[Settore]",
                        "[NomeCorso]",
                        "[Localita]",
                        "[Durata]",
                        "[Data]",
                        "[Ente]",
                        "[Costo]",
                        //"[Beneficiario]",
                        //"[PartitaIva]",
                        //"[CodiceFiscale]",
                        //"[CodiceCreditore]",
                        "[Note]",
                        "[CassaEconomale]",
                        "[Matricola]",
                        //"[Determina]",
                        //"[DataDetermina]",
                        "[cod_direzione]",
                        "[cod_settore]"
                       );
            $pdf = new TCPDF('P', 'mm', 'A4', false, 'ISO-8859-1', false);
            $pdf->SetCreator('Regione Piemonte');
            $pdf->SetAuthor('CSI Piemonte');
            $pdf->SetTitle(get_string('fogliop','local_f2_traduzioni'));
            $pdf->SetSubject(get_string('fogliop','local_f2_traduzioni'));
            $pdf->SetKeywords('PDF '.get_string('fogliop','local_f2_traduzioni').', PDF');
            $pdf->setPrintHeader(FALSE);
            $pdf->setPrintFooter(FALSE);
            $pdf->SetDefaultMonospacedFont('courier');
            $pdf->SetMargins('30', '15', '30');
            $pdf->SetAutoPageBreak(TRUE, '15');
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            $pdf->SetFont('helvetica', '', 12);
            $settore ="";
            $cod_settore="";
            if(isset($dati_corso_ind_forz->settore)){
              $settore = $dati_corso_ind_forz->settore;
              $cod_settore = $dati_corso_ind_forz->cod_settore;
            }
            $pdf->AddPage();
            $allegato_htm = file_get_contents($CFG->dataroot.'/f2_allegati/body_autocertificazione_partecipazione_senza_spesa.htm',false);
            $header_pdf = file_get_contents($CFG->dataroot.'/f2_allegati/header_autocertificazione_partecipazione.htm',false);
            $footer_pdf = file_get_contents($CFG->dataroot.'/f2_allegati/footer_autocertificazione_partecipazione.htm',false);
            $body_pdf = str_replace($find,$replace,$allegato_htm);
            $html = $header_pdf.$body_pdf;
            ob_end_clean();
            $pdf->writeHTML(utf8_decode($html));	
            $pdf->SetXY(45, 280);
            $pdf->cell(0,0,utf8_decode($footer_pdf),0,0,'C',0,0,false,'B','C');
            $path = mkdir($CFG->dataroot.'/f2_attachments', 0770);
            $file_name = uniqid($CFG->dataroot.'/f2_attachments/Autocertificazione_partecipazione_');
            $file_name = $file_name.'.pdf';
            $pdf->Output($file_name, 'F');		
            $attachments_files = $file_name;
          }
          // espressione regolare su mail
	  $regex = '/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,3})$/';
	  // Se il formato mail e' valido
	  if (preg_match($regex, $utente_corso->email)) {
            //$prot_data = get_prot_data($id_send); // 2018 04 04
            $prot_data = get_prot_corsiind_senza_determina($id_send);
	    $data->id_course_ind = $id_send;
	    $data->lastname      = $utente_corso->lastname;
	    $data->firstname     = $utente_corso->firstname;
            $data->matricola     = $utente_corso->idnumber;
	    $data->localita      = $course_data->localita;
	    $data->data          = $course_data->data_inizio;
	    $data->ente          = $course_data->ente;
	    $data->titolo        = $course_data->titolo;
	    $data->durata        = $course_data->durata;
	    $data->mailto        = $utente_corso->email;
	    $data->direzione     = $dati_corso_ind_forz->direzione;
	    $data->cod_direzione = $dati_corso_ind_forz->cod_direzione;
	    $data->id_notifica   = $course_data->modello_email;
	    $data->userid        = $course_data->userid;
	    $data->attachments   = $attachments_files;
	    $data->id_tipo_notif = 3; // tipo notifica modello individuale
            $data->num_prot      = $prot_data->prot;
            //$data->data_prot     = $prot_data->data; // 2018 04 04
            $data->data_prot     = $prot_data->data_prot;
	    if (!send_mail_autorizzazione_senza_determina($data)) {
              $data->titolo = $course_data->titolo;
	      $data->error_mail = 1;
	    }
	  } else {
		$data->lastname = $utente_corso->lastname;
		$data->firstname = $utente_corso->firstname;
                $data->matricola = $utente_corso->idnumber;
                $data->titolo = $course_data->titolo;
		$data->error_mail = 2;
	  }
	}
	return $data;
}
/**
 * @param obj Dati corso
 * Invio mail autorizzazione al corso senza determina
 * per le libs vedi /htdocs/moodle/local/f2_notif
 */
function send_mail_autorizzazione_senza_determina($data) {
	global $DB, $USER, $CFG;
	// creo l'array di valori da associare ai segnaposto
	$replacements[0]  = $data->firstname;
	$replacements[1]  = $data->lastname;
	$replacements[2]  = $data->titolo;
	$replacements[3]  = $data->num_prot;
	$replacements[4]  = $data->data_prot;
	$replacements[5]  = $data->direzione;
	$replacements[6]  = $data->cod_direzione;
	$replacements[7]  = 'senzadetermina';
	$replacements[8]  = 'nessunadata';
	$replacements[9]  = $data->ente;
	$replacements[10] = $data->localita;
	$replacements[11] = date('d/m/Y',$data->data);
	$replacements[12] = $data->durata;
	$replacements[13] = $CFG->wwwroot.'/pix';
	// Recupero la notifica associata all'edizione
	$notifica = get_template($data->id_notifica);
	// Recupero i segnaposto associati al tipo di di notifica
	$segnaposto = get_segnaposto($data->id_tipo_notif);
	// Modifico il messaggio originale inserendo i valori ai segnaposto
	$testo_msg = preg_replace($segnaposto,$replacements,$notifica->message);
	// Modifico l'oggetto originale inserendo i valori ai segnaposto
  $testo_oggetto = preg_replace($segnaposto,$replacements,$notifica->subject);
	// recupero l'indirizzo email del mittente dalla tabella f2_parametri
	$param_sendmail_from = get_parametro('f2_sendmail_from');
	$sendmail_from = $param_sendmail_from->val_char;
	// Preparo la mail da inserire in notif_template_mailqueue
	$parametri=new stdClass();
	$parametri->sessionid    = 0;
	$parametri->useridfrom   = $USER->id;
	$parametri->useridto     = $data->userid;
	$parametri->mailfrom     = $sendmail_from;
	$parametri->mailto       = $data->mailto;
	$parametri->subject      = $testo_oggetto;
	$parametri->attachment   = $data->attachments;
	$parametri->message      = $testo_msg;
	$parametri->time         = time();
	$parametri->mailtemplate = $data->id_notifica;
	// invio mail - Inserisco la mail in notif_template_mailqueue
	if (!$DB->insert_record('f2_notif_template_mailqueue', $parametri, $returnid=true, $bulk=false)) {
	  return false;
	}
	update_data_invio_mail_corsi_ind_senza_determina($data->id_course_ind,$parametri->time);
        send_notif(500); // DECOMMENTARE SE SI VUOLE INVIARE LA MAIL SUBITO
	// Salvo gli utenti a cui e' stata inviata la mail
	$return_user_mail_sent[] = $data->userid;
	return $return_user_mail_sent;
}
/**
 * @param int Id corso
 * Recupera i dati del corso senza determina
 */
function get_course_data_senza_determina($id_send) {
	global $DB;
	$course_data = $DB->get_record('f2_corsiind_senza_spesa', array('id' => $id_send));
	if ($course_data) {
		return $course_data;
	} else {
		return false;
	}
}
/**
 * @param int Id utente
 * Recupera i dati utente del corso senza determina
 */
function get_dati_utente_corso($id_user) {
  global $DB;
  add_to_log_corsi_ind_senza_determina('get_dati_utente_corso - user id: '.$id_user);
  $utente_corso = $DB->get_record('user', array('id' => $id_user), 'username,firstname,lastname,email,idnumber');
  if ($utente_corso) {
    add_to_log_corsi_ind_senza_determina('get_dati_utente_corso - ottenuti dati utente');
    return $utente_corso;
  } else {
    add_to_log_corsi_ind_senza_determina('get_dati_utente_corso - dati utente NON recuperati');
    return false;
  }
}
/**
 * @param int Id corso
 * @param int time
 * Aggiorno la data di invio mail
 */
function update_data_invio_mail_corsi_ind_senza_determina($id_course,$time) {
  global $DB;
  $esito = 1;
  $parametro = new stdClass();
  $parametro->id = $id_course;
  $parametro->data_invio_mail = $time;
  if (!$DB->update_record('f2_corsiind_senza_spesa', $parametro)) {
    $esito = 0;
  }
  if ($esito)
    return true;
  else
    return false;
}
/**
 * @param string msg
 * Log funzioni corsi individuali senza determina 
 */
function add_to_log_corsi_ind_senza_determina($msg) {
  global $DB;
  $log_corsi_ind = new stdClass();
  $log_corsi_ind->data = date("Y-m-d H:i:s");
  $log_corsi_ind->msg  = $msg;
  $insert_log = $DB->insert_record('log_corsi_ind', $log_corsi_ind);
  return;
}
/**
 * @param string msg
 * Log query corsi individuali senza determina
 */
function add_to_log_query($msg) {
  global $DB;
  $log_query = new stdClass();
  $log_query->data = date("Y-m-d H:i:s");
  $log_query->msg  = $msg;
  $query_log = $DB->insert_record('f2_corsiind_senza_spesa_query_log', $log_query);
  return;
}
/**
 * @param int $id_course id corso
 * @param string $msg msg
 * Log archiviazione in storico per i corsi individuali senza determina
 */
function add_to_log_archiviazione_corsiind_senza_determina($id_ci_sd, $msg) {
  global $DB;
  $log_arch_corsi_ind = new stdClass();
  $log_arch_corsi_ind->id_corsiind = $id_ci_sd;
  $log_arch_corsi_ind->data = date("Y-m-d H:i:s");
  $log_arch_corsi_ind->msg = $msg;
  $insert_arch = $DB->insert_record('log_corsi_ind_archiviazione', $log_arch_corsi_ind);
  return;
}
/**
 * @param string msg
 * Log protocollo per i corsi individuali senza determina
 */
function add_to_log_prot_corsi_ind_senza_determina($msg) {
  global $DB;
  $log_prot_corsi_ind = new stdClass();
  $log_prot_corsi_ind->data = date("Y-m-d H:i:s");
  $log_prot_corsi_ind->msg  = $msg;
  $insert_prot_log = $DB->insert_record('log_corsi_ind_prot', $log_prot_corsi_ind);
  return;
}
/**
 * @param int id_corsiind
 * @param string prot 
 * Numero di protocollo per i corsi individuali senza determina
 */
function add_prot_to_corsiind_senza_determina($id_ci_sd, $n_prot) {
  global $DB;
  $prot_corsi_ind = new stdClass();
  $prot_corsi_ind->id_corsiind = $id_ci_sd;
  $prot_corsi_ind->data = date("Y-m-d H:i:s");
  $prot_corsi_ind->prot = $n_prot;
  if (!$insert_prot = $DB->insert_record('f2_corsiind_prot', $prot_corsi_ind)) {
    return false;
  }
  return $id_ci_sd;
}
/**
 * @param int id_corsiind
 * @param string prot
 * Numero di protocollo per i corsi individuali senza determina
 */
function upd_prot_to_corsiind_senza_determina($id_ci_sd, $n_prot) {
  global $DB;
  $prot_corsi_ind = new stdClass();
  $prot_corsi_ind->id = $id_ci_sd;
  $prot_corsi_ind->data_prot = date("Y-m-d H:i:s");
  $prot_corsi_ind->prot = $n_prot;
  if (!$upd_prot = $DB->update_record('f2_corsiind_senza_spesa', $prot_corsi_ind)) {
    return false;
  }
  return $id_ci_sd;
}
/**
 * @param int id_corsiind
 * Recupero del Numero di protocollo per i corsi individuali senza determina
 */
function get_num_protocollo($id_ci_sd) {
  global $DB;
  if (!$id_ci_sd) {
    //$msg = 'ID corso non pervenuto';
    $msg = 'KO';
  } else {
    $num_protocollo = $DB->get_record('f2_corsiind_prot', array('id_corsiind' => $id_ci_sd), 'prot');
    if (!$num_protocollo) {
      $msg = '---';
    } else {
      $msg = $num_protocollo->prot;
    }
  }
  return $msg;
}
/**
 * @param int id_corsiind
 * Ricerca ID del Numero di protocollo per i corsi individuali senza determina
 */
function get_id_num_prot($id_course) {
  global $DB;
  $id_num_prot = $DB->get_record('f2_corsiind_prot', array('id_corsiind' => $id_course), 'id');
  return $id_num_prot->id;
}
/**
 * @param int id_corsiind
 * @param string prot
 * Aggiornamento del Numero di protocollo per i corsi individuali senza determina
 */
function upd_protocollo($n_prot, $id_course) {
  global $DB;
  $res_upd = array();
  if ((!$n_prot) || ($n_prot == '') || (!$id_course) || ($id_course == '')) {
    $res_upd['err'] = 'errore parametri';
  } else {
    $id_prot = get_id_num_prot($id_course);
    $upd_prot = new stdClass();
    $upd_prot->id = $id_prot;
    $upd_prot->id_corsiind = $id_course;
    $upd_prot->prot = $n_prot;
    if (!$upd_prot = $DB->update_record('f2_corsiind_prot', $upd_prot)) {
      $res_upd['err'] = 'errore update';
    } else {
      $res_upd['upd'] = 'ok';
    }
  }
  return $res_upd;
}
/**
 * @param int id_corsiind
 * @param string prot
 * Aggiornamento del Numero di protocollo per i corsi individuali senza determina
 */
function aggiorna_protocollo($n_prot, $id_course) {
  global $DB;
  $res_upd = array();
  if ((!$n_prot) || ($n_prot == '') || (!$id_course) || ($id_course == '')) {
    $res_upd['err'] = 'errore parametri';
  } else {
    $upd_prot = new stdClass();
    $upd_prot->id = $id_course;
    $upd_prot->prot = $n_prot;
    if (!$upd_prot = $DB->update_record('f2_corsiind_senza_spesa', $upd_prot)) {
      $res_upd['err'] = 'errore update';
    } else {
      $res_upd['upd'] = 'ok';
    }
  }
  return $res_upd;
}
/**
 * @param int $id_course Id corso
 * Ritorna i dati relativi al protocollo del corso individuale senza spesa con id = $id_course
 */
function get_prot_data($id_course) {
  global $DB;
  $prot_data = $DB->get_record('f2_corsiind_prot', array('id_corsiind' => $id_course));
  return $prot_data;
}
/**
 * @param int Id corso
 * Recupera i dati del protocollo del corso senza determina
 */
function get_prot_corsiind_senza_determina($id_send) {
    global $DB;
    $query = "SELECT prot, DATE_FORMAT(data_prot, '%d-%m-%Y') as data_prot
              FROM {f2_corsiind_senza_spesa}
              WHERE id = ".$id_send;
    //$prot_data = $DB->get_record('f2_corsiind_senza_spesa', array('id' => $id_send), 'prot,data_prot');
    $prot_data = $DB->get_record_sql($query);
    if ($prot_data) {
        return $prot_data;
    } else {
        return false;
    }
}
/**
 * @param int $id Id corso
 * Ritorna i dati relativi al corso individuale senza spesa con id = $id
 */
function get_corso_ind_senza_spesa($id) {
  global $DB;
  $sql_query ="SELECT * FROM {f2_corsiind_senza_spesa} ci WHERE ci.id = ".$id;
  $results = $DB->get_record_sql($sql_query);
  return $results;
}
/**
 * @param int $id Id corso
 * Ritorna i dati relativi al corso individuale compreso utente e protocollo senza spesa con id = $id
 */
function get_dati_corso_senza_spesa($id_corso) {
  global $DB;
/*
  $sql_dati_corso = "SELECT u.firstname, u.lastname, u.idnumber, ci.titolo, ci.data_inizio, p.prot ".
                    "FROM {f2_corsiind_senza_spesa} ci, {f2_corsiind_prot} p, {user} u ".
                    "WHERE ci.id = ".$id_corso." AND ci.id = p.id_corsiind AND ci.userid = u.id";
*/
  $sql_dati_corso = "SELECT u.firstname, u.lastname, u.idnumber, ci.titolo, ci.data_inizio, ci.prot ".
                    "FROM {f2_corsiind_senza_spesa} ci, {user} u ".
                    "WHERE ci.id = ".$id_corso."  AND ci.userid = u.id";
  $dati_corso = $DB->get_record_sql($sql_dati_corso);
  return $dati_corso;
}
/**
 * @param int $id Id corso
 * Ritorna i dati relativi al corso individuale per archiviazione
 */
function get_scheda_descrittiva_archiviazione($data){
  global $DB;
  $query = "SELECT ci.id as id_course, u.username, u.firstname, u.lastname, ci.data_inizio, ci.codice_archiviazione, ".
           "ci.titolo, ci.durata, ci.costo, ci.localita, ci.ente, ci.beneficiario_pagamento, ci.via, ci.partita_iva, ".
           "ci.codice_fiscale, ci.note, ci.codice_creditore, ci.id_determine, ci.modello_email, ci.credito_formativo ".
           "FROM  {f2_corsiind_senza_spesa} ci, {user} u ".
           "WHERE ci.id = '".$data->id_corso_ind."' AND ci.userid = u.id";
  $results = $DB->get_record_sql($query);
  return $results;
}
/**
 * @param obj $data dati corso
 * Archivia il corso nella tabella storico corsi
 */
function archivia_corso_senza_determina($data) {
  global $DB;
  $id_corso_individuale = $data->id_course;
  add_to_log_archiviazione_corsiind_senza_determina($id_corso_individuale, 'START archivia_corso_senza_determina');
  $dati_partecipazioni = $DB->get_record('f2_partecipazioni',array('id'=>$data->partecipazione));
  $dati_archiviazione =$DB->get_record_sql("SELECT * FROM {f2_corsiind_senza_spesa} ci WHERE ci.id=".$id_corso_individuale);
  $moodleuser = $DB->get_record('user', array('id'=>$dati_archiviazione->userid), '*', MUST_EXIST);
  $dati_forzatura = get_forzatura_or_moodleuser_ind($moodleuser->username);
  if(!($dati_forzatura->cod_direzione || $dati_forzatura->cod_settore)) {
    return -1;
  }
  $record = new stdClass();
  $record->matricola = $dati_forzatura->idnumber;
  $record->cognome = $dati_forzatura->lastname;
  $record->nome = $dati_forzatura->firstname;
  $record->sesso = $dati_forzatura->sesso;
  $record->categoria = $dati_forzatura->category;
  $record->ap = $dati_forzatura->ap;
  $record->e_mail = $dati_forzatura->email;
  $record->cod_direzione = $dati_forzatura->cod_direzione;
  $record->direzione = $dati_forzatura->direzione;
  $record->cod_settore = $dati_forzatura->cod_settore;
  $record->settore = $dati_forzatura->settore;
  $record->tipo_corso = 'I';
  $record->data_inizio = $dati_archiviazione->data_inizio;
  $record->sede_corso = $dati_archiviazione->via;
  $record->localita = $dati_archiviazione->localita;
  $record->codcitta = '00';
  //$record->prot = $dati_archiviazione->codice_archiviazione;
  $record->prot = $dati_archiviazione->prot;
  $record->costo = $dati_archiviazione->costo;
  $record->af = $dati_archiviazione->area_formativa;
  $record->to_x = $dati_archiviazione->tipologia_organizzativa;
  $record->tipo = $dati_archiviazione->tipo;
  //$record->sirp = $dati_archiviazione->numero_protocollo;
  $record->sirp = get_num_protocollo($data->id_course);
  //$record->sirpdata = $dati_archiviazione->data_protocollo;
  $record->sirpdata = get_prot_data($data->id_course);
  $record->titolo = $dati_archiviazione->titolo;
  $record->durata = $dati_archiviazione->durata;
  $record->scuola_ente = $dati_archiviazione->ente;
  $record->note = $dati_archiviazione->note;
  $record->presenza = $data->presenza;
  $record->determina = '';
  $record->codpart = $dati_partecipazioni->codpart;
  $record->descrpart = $dati_partecipazioni->descrpart;
  $record->sub_af = $dati_archiviazione->sotto_area_formativa;
  $record->cfa = '00';
  $record->cfv = $data->credito_formativo_valido;
  $record->va = $data->verifica_apprendimento;
  $record->cf = $dati_archiviazione->credito_formativo;
  $record->te = 0;
  $record->ac = '00';
  $record->sf = $dati_archiviazione->segmento_formativo;
  $record->lstupd = time();

  $return_insert = $DB->insert_record('f2_storico_corsi', $record, $returnid=true, $bulk=false);

  add_to_log_archiviazione_corsiind_senza_determina($id_corso_individuale, 'id record inserito in f2_storico_corsi: '.$return_insert);

  if($return_insert) {
    $dati_storico_corsiind = new stdClass();
    $dati_storico_corsiind->id = $id_corso_individuale ;
    $dati_storico_corsiind->storico = $return_insert ;
    $update_corsiind_storico = $DB->update_record('f2_corsiind_senza_spesa', $dati_storico_corsiind, $bulk=false);
    if($update_corsiind_storico) {
      add_to_log_archiviazione_corsiind_senza_determina($id_corso_individuale, 'update OK');
      return 1;
    } else {
      add_to_log_archiviazione_corsiind_senza_determina($id_corso_individuale, 'update KO');
      return 0;
    }
  } else {
    add_to_log_archiviazione_corsiind_senza_determina($id_corso_individuale, 'NO update f2_corsiind_senza_spesa');
    return 0;	
  }
}
