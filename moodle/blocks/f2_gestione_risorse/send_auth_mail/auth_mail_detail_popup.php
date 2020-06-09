<?php
// print_r($_POST);
// print_r($_GET);

require_once '../../../config.php';
require_once '../lib.php';
global $DB;

//$context = get_context_instance(CONTEXT_SYSTEM);
$context = context_system::instance();
require_login();
require_capability('block/f2_gestione_risorse:send_auth_mail', $context);

$edizione_id = required_param('ed', PARAM_INT);
// print_r($edizione_id);exit;
$dati_corso = get_corso_by_facetofacesession($edizione_id);
//print_r($dati_corso);
$dettagli_email = get_dettagli_email_inviate($edizione_id);


// Load data
if (!$session = facetoface_get_session($edizione_id)) {
	print_error('error:incorrectcoursemodulesession', 'facetoface');
}

echo "<h3>Report di invio e-mail autorizzazione relativi al corso</h3>";
echo "<table width='100%'><tr><td><h5>".$dati_corso->fullname." - ".$dati_corso->facetofacename."</h5></td><td align=right><input type='button' value='Stampa' onclick='window.print();'></td></tr></table>";

$sede = new stdClass();
$sede->id = 4;//sede
$sede=facetoface_get_customfield_value($sede, $edizione_id, "session");
	
$indirizzo = new stdClass();
$indirizzo->id = 5;//indirizzo
$indirizzo=facetoface_get_customfield_value($indirizzo, $edizione_id, "session");

//Inizio: recupero i campi sirp e sirp data
$field_sirp = new stdClass();
$field_sirp->id = 2;//sirp
$sirp=facetoface_get_customfield_value($field_sirp, $edizione_id, "session");

$field_sirp_data = new stdClass();
$field_sirp_data->id = 3;//sirp data
$field_sirp_data=facetoface_get_customfield_value($field_sirp_data, $edizione_id, "session");
	
echo "<b>Codice corso: ".$dati_corso->idnumber."<br>Titolo corso: ".$dati_corso->fullname."<br>Data inizio: ".date('d/m/Y',$session->sessiondates[0]->timestart)."";


$sql = "SELECT * FROM {f2_anagrafica_corsi} WHERE courseid = ".$dati_corso->id;
$return_anag_course = $DB->get_record_sql($sql);

echo "<br>Orario: ".$return_anag_course->orario;

/*
 * Orario Sessione
$html ="";
foreach ($session->sessiondates as $date) {
	if (!empty($html)) {
		$html .= html_writer::empty_tag('br');
	}
	$timestart = $date->timestart == 0 ? 'Data da definire' : userdate($date->timestart, get_string('strftimedatetime'));
	$timefinish = $date->timefinish == 0 ? 'Data da definire' : userdate($date->timefinish, get_string('strftimedatetime'));
	if ($date->timestart == 0 || $date->timefinish == 0)
		$html .= 'Date da definire';
	else
		$html .= "$timestart &ndash; $timefinish";
}
echo $html;
*/

$ente = "";
if ($return_anag_course->flag_dir_scuola == 'D') {
	if ($return_anag_course->id_dir_scuola > 0) {
		$ente = $DB->get_field('org', 'fullname', array('id' => $return_anag_course->id_dir_scuola));
	}
} else {
	if ($return_anag_course->id_dir_scuola > 0) {
		$ente = $DB->get_field('f2_fornitori', 'denominazione', array('id' => $return_anag_course->id_dir_scuola));
	}
}

echo "<br>Ente: ".$ente."<br>Sede: ".$sede."";
echo "<br>Indirizzo sede corso: ".$indirizzo."<br>Sirp: ".$sirp."<br>SirpData: ".date('d/m/Y', $field_sirp_data)."</b><br>";

echo "<br>";









echo "<table width='100%'><tr><td width='8%'  align=left  valign=top  class='clsBold' ><b>Utente</b></td><td width='8%'  align=left  valign=top  class='clsBold' ><b>Mail</b></td><td width='8%'  align=left  valign=top  class='clsBold' ><b>Data invio</b></td><td width='8%'  align=left  valign=top  class='clsBold' ><b>Esito invio</b></td><td width='8%'  align=left  valign=top  class='clsBold' ><b>Stato iscrizione</b></td></tr>";
		
foreach($dettagli_email as $dett)
{
	echo "<tr><td>".$dett->utente."</td><td>".$dett->mailto."</td><td>".$dett->sendtime."</td><td>Inviata</td><td>Iscritto</td></tr>";
}
echo "</table>";
