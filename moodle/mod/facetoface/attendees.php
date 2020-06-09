<?php
/*
 * $Id: attendees.php 1282 2014-05-16 13:37:32Z l.moretto $
 */
/**
 * Copyright (C) 2010, 2011 Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Francois Marier <francois@catalyst.net.nz>
 * @author Aaron Barnes <aaronb@catalyst.net.nz>
 * @package totara
 * @subpackage facetoface
 */

global $PAGE, $CFG,$DB;

require_once dirname(dirname(dirname(__FILE__))).'/config.php';
require_once $CFG->dirroot.'/mod/facetoface/lib.php';
require_once($CFG->dirroot.'/f2_lib/report.php');
require_once($CFG->dirroot.'/local/f2_notif/lib.php');
require_once $CFG->dirroot.'/f2_lib/lib.php';
require_once $CFG->dirroot.'/f2_lib/core.php';
require_once $CFG->dirroot.'/f2_lib/management.php';
require_once($CFG->dirroot.'/local/f2_support/lib.php');
/*
 * AK-DL pagination: intestazioni necessarie per l'impaginazione e ordinamento
*/
$page     = optional_param('page', 0, PARAM_INT);
$perpage  = optional_param('perpage', 10, PARAM_INT);
$column   = optional_param('column', 'nome', PARAM_TEXT);
$sort     = optional_param('sort', 'ASC', PARAM_TEXT);
$nome     = optional_param('nome', '', PARAM_TEXT);
$user_non_arch = optional_param('una', '', PARAM_TEXT);

$PAGE->set_pagelayout('standard');

//Utenti con stato iscritto selezionati nelle pagine della paginazione
if(isset($_POST["id_attendees"]))
	$user_checked=$_POST["id_attendees"];
else
	$user_checked="";

//Utenti con stato sostituito selezionati nelle pagine della paginazione
if(isset($_POST["id_attendees_substitute"]))
	$user_checked_sub=$_POST["id_attendees_substitute"];
else
	$user_checked_sub="";
	
$esito_note=null;// Questa variabile viene valorizzata durante il salvataggio delle note (stampa a video l'esito del salvataggio)
$sirp_session=null;// Questa variabile viene valorizzata durante l'invio della mail (viene controllato se è presente nel db il campo sirp e sirp data)
$notif_autoriz_session=null;// Questa variabile viene valorizzata durante l'invio della mail (viene controllato se è presente la notifica di autorizzazione)

// --- #
global $DB, $USER;

/**
 * Load and validate base data
 */
// Face-to-face session ID
$s = required_param('s', PARAM_INT);

// Take attendance
$takeattendance    	= optional_param('takeattendance', false, PARAM_BOOL);
// Cancel request
$cancelform        	= optional_param('cancelform', false, PARAM_BOOL);
// Face-to-face activity to return to
$backtoallsessions 	= optional_param('backtoallsessions', 0, PARAM_INT);
/*
 * AK-LS: per evitare un conflitto tra la paginazione e le funzionalità
 * originarie di questa pagina, il salvataggio del consuntivo presenze 
 * viene fatto non solo quando $takeattendance = TRUE ma anche quando
 * $saveattendance = TRUE. Relativamente all'archiviazione è stata prevista
 * $stores come variabili adibita, in simbiosi con $takeattendance, per 
 * la storicizzazione dei dati
 */
$saveattendance 	= optional_param('saveattendace',false,PARAM_BOOL);
$stores				= optional_param('stores',false,PARAM_BOOL);

// Load data
if (!$session = facetoface_get_session($s)) {
    print_error('error:incorrectcoursemodulesession', 'facetoface');
}
if (!$facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface))) {
    print_error('error:incorrectfacetofaceid', 'facetoface');
}
if (!$course = $DB->get_record('course', array('id' => $facetoface->course))) {
    print_error('error:coursemisconfigured', 'facetoface');
}
if (!$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id)) {
    print_error('error:incorrectcoursemodule', 'facetoface');
}

$courseid = $course->id;
$coursetype = (int)$DB->get_field('f2_anagrafica_corsi', 'course_type', array('courseid' => $courseid));

// Load attendees
//$attendees = facetoface_get_attendees($session->id);

// Load cancellations
$cancellations = facetoface_get_cancellations($session->id);

/**
 * Capability checks to see if the current user can view this page
 *
 * This page is a bit of a special case in this respect as there are four uses for this page.
 *
 * 1) Viewing attendee list
 *   - Requires mod/facetoface:viewattendees capability in the course
 *
 * 2) Viewing cancellation list
 *   - Requires mod/facetoface:viewcancellations capability in the course
 *
 * 3) Taking attendance
 *   - Requires mod/facetoface:takeattendance capabilities in the course
 *
 */

$context = context_course::instance($course->id);
require_course_login($course);

// Actions the user can perform
$can_view_attendees = has_capability('mod/facetoface:viewattendees', $context);
$can_take_attendance = has_capability('mod/facetoface:takeattendance', $context);
$can_view_cancellations = has_capability('mod/facetoface:viewcancellations', $context);
$can_view_session = $can_view_attendees || $can_take_attendance || $can_view_cancellations;
$can_approve_requests = false;

$requests = array();
$declines = array();

// If a user can take attendance, they can approve staff's booking requests
if ($can_take_attendance) {
    $requests = facetoface_get_requests($session->id);
}

// If requests found (but not in the middle of taking attendance), show requests table
if ($requests && !$takeattendance) {
    $can_approve_requests = true;
}

// Check the user is allowed to view this page
if (!$can_view_attendees && !$can_take_attendance && !$can_approve_requests && !$can_view_cancellations) {
    print_error('nopermissions', '', "{$CFG->wwwroot}/mod/facetoface/view.php?id={$cm->id}", get_string('view'));
}

// Check user has permissions to take attendance
if ($takeattendance && !$can_take_attendance) {
    print_error('nopermissions', '', '', get_capability_string('mod/facetoface:takeattendance'));
}

?>
<!-- AK-LS: script per il controllo dei valori immessi nella maschera di consuntivazione -->
<script type="text/javascript">
function takeattendance_checking(va, presenza) {

    var flag = false;
    //var decimalregex = /^[0-9]+(\.([0]|[5]){0,1})?$/; // la cifra decimale dopo l'eventuale . pu� essere 0 o 5
    var decimalregex = /^\d{1,2}(\.\d{1,2})?$/; // la cifra decimale dopo l'eventuale . pu� essere 0 o 5

    switch(va.value) {
        case '3':
        case '4':
        case '5':
        case '_':
            flag = true;
        break;
        default:
    }

    if (flag) {
        if (presenza.value.match(decimalregex)) {
            return true;
        } else {
            alert("La presenza dev'essere un valore compreso fra 0 e 99.99 utilizzando il punto come separatore dei decimali.");
            return false;
        }
    } else {
        alert("Selezionare un valore di verifica apprendimento");
        return false;
    }
}
</script>
<?php 

/**
 * Handle submitted data
 */
if ($form = data_submitted()) {
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error');
    }

    $return = new moodle_url("attendees.php", array("s"=>$s, "backtoallsessions"=>$backtoallsessions, "page"=>$page));

    if ($cancelform) {
        redirect($return);
    }
    elseif (!empty($form->requests)) {
        // Approve requests
        if ($can_approve_requests && facetoface_approve_requests($form)) {
            add_to_log($course->id, 'facetoface', 'approve requests', "view.php?id=$cm->id", $facetoface->id, $cm->id);
        }

        redirect($return);
    }
    elseif ($takeattendance && ($saveattendance || $stores)) {
    	if ($saveattendance) {
	        if (facetoface_take_attendance_csi($form)) {
	            add_to_log($course->id, 'facetoface', 'take attendance', "view.php?id=$cm->id", $facetoface->id, $cm->id);
	        } else {
	            add_to_log($course->id, 'facetoface', 'take attendance (FAILED)', "view.php?id=$cm->id", $face->id, $cm->id);
	        }
    	} elseif ($stores) {
    		$result_stored = facetoface_stores_csi($form);
    		if ($result_stored->return) {
    			if($result_stored->utenti_non_archiviati){
                    $return->param("una", $result_stored->utenti_non_archiviati);
    				add_to_log($course->id, 'facetoface', 'stores (partial)(no direzione e settore)', "view.php?id=$cm->id", $facetoface->id, $cm->id);
    			}else{
                    add_to_log($course->id, 'facetoface', 'stores', "view.php?id=$cm->id", $facetoface->id, $cm->id);
    			}
    		} else {
    			add_to_log($course->id, 'facetoface', 'stores (FAILED)', "view.php?id=$cm->id", $face->id, $cm->id);
    		}
    	}
        $return->param("takeattendance", 1);
        redirect($return);
    }
}

/**
 * Print page header
 */
add_to_log($course->id, 'facetoface', 'view attendees', "view.php?id=$cm->id", $facetoface->id, $cm->id);

$edizione_svolgimento = $DB->get_record_sql("SELECT data as num_ediz FROM {$CFG->prefix}facetoface_session_field f
JOIN {$CFG->prefix}facetoface_session_data d on d.fieldid = f.id
JOIN {$CFG->prefix}facetoface_sessions s on s.id = d.sessionid
WHERE f.shortname = 'editionum' AND s.id = $session->id");

$pagetitle = format_string(format_string($facetoface->name).' - Edizione '
        .($coursetype === C_PRO ? $edizione_svolgimento->num_ediz : "del ".date('d/m/Y', $session->sessiondates[0]->timestart)));

$PAGE->set_url('/mod/facetoface/attendees.php', array('s' => $s));
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_title($pagetitle);
$PAGE->navbar->add(get_string('attendees', 'facetoface'));

/*
 * AK-DL: insert 
 */	

//Inizio: se è stato cliccato il pulsante salva note
	if(isset($_POST["salva_note"])){
		$parametri = $_POST["note_partecipante"];
		
		if(update_note_facetoface_signups($s,$parametri)){
			$esito_note="Le note sono state salvate correttamente";
		}else{
			$esito_note="Errore nel salvataggio delle note";
		}
	}
//Fine: se è stato cliccato il pulsante salva note

//Inizio: se è stato cliccato il pulsante invia mail
if(isset($_POST["invia_mail"])){

	//Inizio: recupero i campi sirp e sirp data
		$field_sirp = new stdClass();
		$field_sirp->id = 2;//sirp
		$sirp=facetoface_get_customfield_value($field_sirp, $s, "session");

		$field_sirp_data = new stdClass();
		$field_sirp_data->id = 3;//sirp data
		$field_sirp_data=facetoface_get_customfield_value($field_sirp_data, $s, "session");
	//Fine: recupero i campi sirp e sirp data
	
	//Inizio: controllo se sono presenti i campi sirp e sirp data
		if($sirp && $field_sirp_data){

	//controllo se è presente la notifica di autorizzazione altrimenti stampo il messaggio di errore
		if(!get_template_corso_edizione($course->id,$s,1)){
			$notif_autoriz_session = "<b style='color:red;'>Non è presente nessuna notifica di autorizzazione.<br>Nessuna notifica inviata.</b>";
		} else {
			//Invio la mail ai partecipanti in stato iscritto se sono stati selezionati
				if(isset($_POST["id_attendees"])){
					$attendees_mail=$_POST["id_attendees"];
					$list_user = array();
					foreach($attendees_mail as $value){
						$data= new stdClass;
						$data->userid=$value;
						$list_user[]=$data;
					}
		
					$mail_dummy = get_dummy_email($list_user);//recupero la mail dummy dalla tabella dei parametri
					//Controllo se sono presente degli utenti con la mail dummy (in questo caso blocco l'invio della mail a tutti gli utenti		
					if (!$mail_dummy){
						/*invio mail*/$user_attendees_mail_sent = upload_mailqueue($s,1,$list_user);/*invio mail*/
					} else {
						$esito_dummy = "<b style='color:red;'>Sono presenti degli utenti con una mail non valida.<br></b>";
						foreach($mail_dummy as $name_parte){
							$esito_dummy = $esito_dummy."<br><b style='color:red;'>".$name_parte->lastname." ".$name_parte->firstname." - ".$name_parte->email."</b>";
						}
					}
				}

	##### 		DECOMMENTARE SE SI VUOLE INVIARE LA MAIL ALLE PERSONE IN STATO SOSTITUITO 		###########
		######### 	se già gli era stata inviata la mail di autorizzazione 				##########
/*				
			//Invio la mail ai partecipanti in stato sostituito se sono stati selezionati
				if(isset($_POST["id_attendees_substitute"])){
					$attendees_mail_substitute = $_POST["id_attendees_substitute"];
					
					$list_user_substitute = array();
					foreach($attendees_mail_substitute as $value){
						
							$data= new stdClass;
							$data->userid=$value;
							
						$list_user_substitute[]=$data;
					}
				$user_substitute_mail_sent = upload_mailqueue($s,2,$list_user_substitute);		
				}
*/

			if($user_attendees_mail_sent) { //Inizio: Se è stata inviata la mail scrivo il pop-up con il riepilogo
					echo '<input type="hidden" id="course_fullname" value="'.$course->fullname.'">';
					echo '<input type="hidden" id="facetoface_name" value="'.format_string($facetoface->name).'">';
					
					$sede = new stdClass();
					$sede->id = 4; //sede
					$sede=facetoface_get_customfield_value($sede, $s, "session");
					
					$indirizzo = new stdClass();
					$indirizzo->id = 5; //indirizzo
					$indirizzo=facetoface_get_customfield_value($indirizzo, $s, "session");
					//$dati_corso = "<b>Codice corso: ".$course->idnumber."<br>Titolo corso: ".$course->fullname."<br>Data inizio: ".date('d/m/Y',$course->startdate)."";
					$dati_corso = "<b>Codice corso: ".$course->idnumber."<br>Titolo corso: ".$course->fullname."<br>Data inizio: ".date('d/m/Y',$session->sessiondates[0]->timestart)."";
					
					$sql = "SELECT * FROM {f2_anagrafica_corsi} WHERE courseid = ".$course->id;
					$return_anag_course = $DB->get_record_sql($sql);
					
					$dati_corso .= "<br>Orario: ".$return_anag_course->orario;
					/*
					 * ORARIO SESSIONE
					foreach ($session->sessiondates as $date) {
						if (!empty($dati_corso)) {
							$dati_corso .= html_writer::empty_tag('br');
						}
						$timestart = $date->timestart == 0 ? 'Data da definire' : userdate($date->timestart, get_string('strftimedatetime'));
						$timefinish = $date->timefinish == 0 ? 'Data da definire' : userdate($date->timefinish, get_string('strftimedatetime'));
						if ($date->timestart == 0 || $date->timefinish == 0)
							$dati_corso .= 'Date da definire';
						else
							$dati_corso .= "$timestart &ndash; $timefinish";
					}
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
											
					$dati_corso .= "<br>Ente: ".$ente."<br>Sede: ".$sede."";
					$dati_corso .= "<br>Indirizzo sede corso: ".$indirizzo."<br>Sirp: ".$sirp."<br>SirpData: ".date('d/m/Y', $field_sirp_data)."</b><br>";
					echo '<input type="hidden" id="dati_corso" value="'.$dati_corso.'">';
				//	print_r($course);exit;
				//Partecipanti attendees. Creo input hidden 
					$partecipanti_attendees=array();
					foreach($user_attendees_mail_sent as $user_id_attendees){
						$partecipanti_attendees[] = $user_id_attendees;
					}

					$detail_user_attendees =user_get_users_by_id($partecipanti_attendees);
					foreach($detail_user_attendees as $user_att){
						echo '<input type="hidden" name="user_attendees_mail_sent[]" value="'.$user_att->lastname.' '.$user_att->firstname.'">';
					}

	##### 		DECOMMENTARE SE SI VUOLE VISUALIZZARE NEL POP-UP GLI UTENTI IN STATO SOSTITUITO 		###########
			######### 	se già gli era stata inviata la mail di autorizzazione 				##########
/*	
				//Partecipanti substitute
					$partecipanti_substitute=array();
					foreach($user_substitute_mail_sent as $user_id_substitute){
						$partecipanti_substitute[] = $user_id_substitute;
					}

					$detail_user_substitute =user_get_users_by_id($partecipanti_substitute);
					foreach($detail_user_substitute as $user_att){
						echo '<input type="hidden" name="user_substitute_mail_sent[]" value="'.$user_att->lastname.' '.$user_att->firstname.'">';
					}
*/

$str1 = <<<'EFO'
<script type="text/javascript">
//<![CDATA[

var course_fullname = document.getElementById('course_fullname').value;
var facetoface_name = document.getElementById('facetoface_name').value;
var dati_corso = document.getElementById('dati_corso').value;

var user_attendees_mail_sent = document.getElementsByName('user_attendees_mail_sent[]');
var user_substitute_mail_sent = document.getElementsByName('user_substitute_mail_sent[]');
var tot_attendees = user_attendees_mail_sent.length;
var tot_substitute = user_substitute_mail_sent.length;

myWindow=window.open('','','width=800,height=600');

myWindow.document.write("<h3>Invio e-mail autorizzazione in corso</h3>");
myWindow.document.write("<table width='100%'><tr><td><h5>"+course_fullname+" - "+facetoface_name+"</h5></td><td align=right><input type='button' value='Stampa' onclick='window.print();'></td></tr></table>");
myWindow.document.write(dati_corso);
myWindow.document.write("<b>.........................................................................................................................................................................................</b><br>");
	            		
var data = new Date();
var d, m, y, Hh, Mm, Ss, mm;
d = data.getDate() + "/";
m = (data.getMonth() + 1) + "/";
y = data.getFullYear() + " ";
Hh = data.getHours() + ":";
Mm = data.getMinutes() + ":";
Ss = data.getSeconds();
var orario = d + m + y + Hh + Mm + Ss;

myWindow.document.write("<table width='100%'><tr><td width='8%'  align=left  valign=top  class='clsBold' ><b>Utente</b></td><td width='8%'  align=left  valign=top  class='clsBold' ><b>Data invio</b></td><td width='8%'  align=left  valign=top  class='clsBold' ><b>Esito invio</b></td><td width='8%'  align=left  valign=top  class='clsBold' ><b>Stato iscrizione</b></td></tr>");

for (i = 0; i < tot_attendees; i++)
{
	myWindow.document.write("<tr><td>"+user_attendees_mail_sent[i].value+"</td><td>"+orario+"</td><td>Inviata</td><td>Iscritto</td></tr>");
}

for (i = 0; i < tot_substitute; i++)
{
	myWindow.document.write("<tr><td>"+user_substitute_mail_sent[i].value+"</td><td>Inviata</td><td>Cancellato</td></tr>");
}

myWindow.document.write("</table>");
myWindow.focus();
//]]>
</script>
EFO;
			echo $str1;
			}//Fine: Se è statainviata la mail scrivo il pop-up con il riepilogo
		}

		//Fine invio mail
		}//Fine if: controllo se sono presenti i campi sirp e sirp data
		else{
			$sirp_session = "<b style='color:red;'>Errore nell'invio della mail. Sirp/Sirpdata non presenti.</b>";
		}//Fine else: controllo se sono presenti i campi sirp e sirp data
		
		$user_checked_sub = "";
		$user_checked = "";
		$_POST["salva_note"]="";
		$_POST["id_attendees"]="";
		$_POST["id_attendees_substitute"]="";
}//Fine: se è stato cliccato il pulsante invia mail

// --- #	
echo $OUTPUT->header();
//echo "<p><a href=\"{$CFG->wwwroot}/blocks/f2_apprendimento/managecourse_prog.php\" class=\"back_arianna\">Torna al catalogo corsi</a></p>";
// 2018 01 31
echo "<a href='".$CFG->wwwroot."/blocks/f2_apprendimento/managecourse_obb.php'><button type='button'>Torna alla gestione corsi obiettivo</button></a>&nbsp;";
echo "<a href='".$CFG->wwwroot."/mod/facetoface/view.php?id=".$cm->id."'><button type='button'>Torna alle edizioni</button></a>";
// aggiunta del 2018 01 31

/**
 * Print page content
 */
// If taking attendance, make sure the session has already started
if ($takeattendance && $session->datetimeknown && !facetoface_has_session_started($session, time())) {
   // $link = "{$CFG->wwwroot}/mod/facetoface/attendees.php?s={$session->id}";
   echo '<div class="box errorbox"><p class="errormessage">'.get_string('error:canttakeattendanceforunstartedsession', 'facetoface').'</p></div>';
 //   print_error('error:canttakeattendanceforunstartedsession', 'facetoface',false);
   echo $OUTPUT->footer($course);
   die;
   }

/*
 * AK-DL: insert 
 */	
$str = <<<'EFO'
<script type="text/javascript">
//<![CDATA[
function checkAll(from,to) {
	var i = 0;
	var chk = document.getElementsByClassName(to);
	var resCheckBtn = document.getElementsByName(from);
	var resCheck = resCheckBtn[i].checked;
	var tot = chk.length;
	for (i = 0; i < tot; i++) chk[i].checked = resCheck;
}
function confirmSendMail() {
	var chk = document.getElementsByClassName("id_attendees[]");
	var chk1 = document.getElementsByClassName("hidden_id_attendees[]");
	var tot = chk.length;
	var num = 0;
	var num1 = chk1.length;
	for (i = 0; i < tot; i++) {
		if(chk[i].checked)
			num++;
	}

	if(num > 0 || num1 > 0)
	{ 
		return confirm("Se si prosegue viene inviata una mail alle persone selezionate. Proseguire?");
	}
	else
	{
		alert("Non e' stato selezionato nessun utente.");
		return false;
	}
}
//]]>
</script>
EFO;

echo $str;



if($user_non_arch){
//	print_r($user_non_arch);exit;

	$user_non_arch = substr_replace($user_non_arch ,"",-1);
//	print_r($user_non_arch);exit;
	//$array_user_ = explode(" ", $pizza);
	
	$array_user_non_arc = $DB->get_records_sql("SELECT id,lastname,firstname FROM {user} WHERE id in (".$user_non_arch.")");
	$string_user_non_arc = "ATTENZIONE:\\nAlcuni utenti non sono stati archiviati poichè non hanno un dominio ben definito.\\n\\nUTENTI NON ARCHIVIATI\\n\\n";
	
	foreach($array_user_non_arc as $dati_user){
		$string_user_non_arc .= $dati_user->lastname." ".$dati_user->firstname;
		$string_user_non_arc .= "\\n";
	}
	
	//alert(\"$string_user_non_arc\");
	
//alert('".$string_user_non_arc."');
	$str ="
<script type=\"text/javascript\">
	alert(\"$string_user_non_arc\");
</script>";
	
	echo $str;
//	exit;
	
}

// --- #	
echo $OUTPUT->box_start();

$is_ref_scuola = false;
$editsessionsdates = false;
$is_ref_scuola = is_referente_scuola_su_corso($course->id);
if ($coursetype === C_PRO) {
    // se il corso è programmato è eventualmente possibile modificare le date delle edizioni
    if ($is_ref_scuola) {
        // se l'utente ha il ruolo di Referente Scuola sul corso corrente allora verifico che la funzionalità sia aperta e che abbia la capability
        $editsessionsdates = assegnazioni_date_scuola_aperte() && has_capability('mod/facetoface:editsessionsdatesprg', $context);
    } else {
        // altrimenti verifico che abbia semplicemente la capability
        $editsessionsdates = has_capability('mod/facetoface:editsessionsdatesprg', $context);
    }
} else {
    // se il corso è obiettivo non si possono modificare le date delle edizioni
    $editsessionsdates = false;
}

// If editing on, add edit icon
$edit_icon = '';
if ($editsessionsdates) {
    $str_edit = get_string('edit');
    if ($is_ref_scuola) {
            $edit_icon .= "<p><a href=\"{$CFG->wwwroot}/mod/facetoface/sessions.php?s={$session->id}&m=1\" title=\"$str_edit\" class=\"action_button\"><button type='button'>Inserisci date</button></a></p>";
    } else {
            $edit_icon .= "<p><a href=\"{$CFG->wwwroot}/mod/facetoface/sessions.php?s={$session->id}\" title=\"$str_edit\" class=\"action_button\"><button type='button'>Inserisci date</button></a></p>";
    }
}

echo $OUTPUT->heading($course->idnumber.' - '.format_string($course->fullname)
        .' - '.format_string($facetoface->name).' - Edizione '.($coursetype === C_PRO ? $edizione_svolgimento->num_ediz : "del ".date('d/m/Y', $session->sessiondates[0]->timestart)));
echo $edit_icon;
/*
 * AK-DL: insert 
 */	
	//Dati per la paginazione
	//	$data = $mform->get_data();
		$pagination = array('perpage' => $perpage, 'page'=>$page,'column'=>$column,'sort'=>$sort,'nome'=>$nome);
		foreach ($pagination as $key=>$value){
			$data->$key = $value;
		}
		//Recupero i partecipanti in base all'ordinamento,alla ricerca ecc. da inserire nella tabella (paginazione)
		if ($takeattendance && (!$saveattendance || !$stores)) {
			$attendees = facetoface_get_users_instatus($session->id, array(MDL_F2F_STATUS_BOOKED, MDL_F2F_STATUS_NO_SHOW, MDL_F2F_STATUS_FULLY_ATTENDED), $data);
		} else {
			$attendees = facetoface_get_users_instatus($session->id, array(MDL_F2F_STATUS_BOOKED), $data, true);
		}
// --- #	
	
if ($can_view_session) {
    echo facetoface_print_session($session, false, false, false, false, array('lstupd', 'csicode', 'editionum', 'usrname'));
    echo "<a href='".$CFG->wwwroot."/course/view.php?id=".$course->id."'><button type='button' title='Accedi alla compilazione dei questionari'>Gestione dati valutazione corso</button></a>";
}

/**
 * Print attendees (if user able to view)
 */
if ($can_view_attendees || $can_take_attendance) {
    if ($takeattendance) {
        $heading = get_string('takeattendance', 'facetoface');
    } else {
        $heading = get_string('attendees', 'facetoface');
    }

    echo '<h3>'.$heading.'</h3>';

    if (empty($attendees)) {
        echo $OUTPUT->notification(get_string('nosignedupusers', 'facetoface'));
    } else {
	//echo "PLUTO";
    	// ID del form dove fare il submit. Generel_form è il form più esterno
    	$form_id='general_form';
    	$post_extra=array('column'=>$column,'sort'=>$sort,'nome'=>$nome);		// dati extra da aggiungere al post del form
    	$total_rows = $attendees->count;
    	$attendees = $attendees->dati;
   
   		// Export excel 
   		if ($takeattendance)
   			echo html_writer::start_tag('form', array('action' => 'excel_presenze.php', 'class' => 'export_excel', 'method' => 'post'));
   		else
	    	echo html_writer::start_tag('form', array('action' => 'excel_partecipanti.php', 'class' => 'export_excel', 'method' => 'post'));

   		echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'post_values', 'value' => json_encode($s)));
   		echo html_writer::start_tag('div');
        echo html_writer::empty_tag('input', array('type' => 'submit', 'class' => 'ico_xls btn', 'value' => get_string('exportattendance', 'facetoface')));
        //echo html_writer::tag('label', ' '.get_string('exportattendance', 'facetoface'));
   		echo html_writer::end_tag('div');
   		echo html_writer::end_tag('form');
        echo '</br>';
                
		// Foglio di presenza attività
		echo html_writer::start_tag('form', array('action' => 'fogliopresenza.php', 'class' => 'pdf_fogliopresenza', 'method' => 'post'));
		echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 's', 'value' => $s));
		echo html_writer::empty_tag('input', array('type' => 'submit', 'class' => 'ico_foglio btn', 'value' => get_string('foglio_presenze', 'facetoface')));
		//echo html_writer::tag('label', ' '.get_string('foglio_presenze', 'facetoface'));
		echo html_writer::end_tag('form');
		echo '</br>';
    	
    	$attendees_url = ($takeattendance) ? new moodle_url('attendees.php', array('s' => $s, 'takeattendance' => '1')) : "";

       	echo html_writer::start_tag('form', array('action' => $attendees_url, 'id' => $form_id, 'name' => $form_id, 'method' => 'post'));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => $USER->sesskey));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 's', 'value' => $s));
        echo html_writer::empty_tag('input', array('type' => 'hidden', ' name' => 'backtoallsessions', 'value' => $backtoallsessions)) . '</p>';
		echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'formname', 'value' => $form_id));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'page', 'value' => $page));
        // Prepare status options array
        $status_options = array();
        foreach ($MDL_F2F_STATUS as $key => $value) {

		//echo "DEBUG: ".$key." - ".$value."<br />";

        	if ($key <= MDL_F2F_STATUS_BOOKED) {
			//echo MDL_F2F_STATUS_BOOKED."<br />";
            	continue;
        }

        $status_options[$key] = get_string('status_'.$value, 'facetoface');
   }
/*
 * AK-DL: upgrade 
 */
        $table = new html_table();
        $table->summary = get_string('attendeestablesummary', 'facetoface');
        $table->align = array('left');
        $table->id = 'table_partecipanti';
        
        // intestazione tabella
        if ($takeattendance) { // consuntivazione presenza e/o assenza
        	$table->size = array('2%','15%','10%','3%','5%','6%','15%','5%','15%');
        	$head_table = array('chk_id_attendees','nome','matricola','qualifica','dominio','stato','data_modifica','presenza', 'verifica_app');
        } else { // iscrizione e/o sostituzione
	        $table->size = array('2%','15%','5%','3%','5%','5%','5%','5%','5%','25%','5%');
	        $head_table = array('chk_id_attendees','nome','matricola','qualifica','dominio','stato_iscrizione','data_modifica','utente_modifica','autorizzazione','note','sostituisci');
	        $head_table_sort = array('nome','stato_iscrizione');
        }
// --- #	
	
/*
 * AK-DL: insert 
 */	
		$table->head = build_head_table($head_table, $head_table_sort, $post_extra, $total_rows, $page, $perpage, $form_id);
		
		// AK-LS flag per la verifica che l'utente (ruolo) possa fare sostituzioni
		$substitute = can_substitute($courseid, $session->id, $coursetype); 
		
        foreach ($attendees as $attendee) {
			$dati_facetoface_signups = get_facetoface_signups($s,$attendee->id);
//echo "KKK <br />";
			$checked="";
			if ($takeattendance) {
				if(in_array($attendee->signupid ,$user_checked)) {
					$checked = "checked=\"checked\"";
					unset($user_checked[$attendee->singupid]);
				}
			} else {	
				if(in_array($attendee->id ,$user_checked)) {//checked per utenti iscritti
					$checked="checked=\"checked\"";
					unset($user_checked[$attendee->id]);
				}
				if(in_array($attendee->id ,$user_checked_sub)) {//checked per utenti cancellati
					$checked="checked=\"checked\"";
					unset($user_checked_sub[$attendee->id]);
				}
			}
            $data = array();
            $attendee_url = new moodle_url('/user/view.php', array('id' => $attendee->id, 'course' => $course->id));
			
			if ($takeattendance) {
				if ($attendee->stores)
					$data[] = "";
				else 
					$data[] = html_writer::empty_tag('input', array('type' => 'checkbox', 'class' => 'id_attendees[]', 'name' => 'id_attendees['.$attendee->signupid.']', 'value' => $attendee->signupid, $checked));
			} else {
				if ($attendee->statuscode == MDL_F2F_STATUS_BOOKED)
					$data[] = '<input type="checkbox" class="id_attendees[]" name="id_attendees['.$attendee->id.']" value='.$attendee->id.' '.$checked.'>';
				else
					$data[] = '<input type="checkbox" class="id_attendees[]" name="id_attendees_substitute['.$attendee->id.']" value='.$attendee->id.' '.$checked.'>';
			}
			
            $data[] = html_writer::link($attendee_url, format_string(fullname($attendee)));
			$detail_user =user_get_users_by_id(array($attendee->id)); //Recupero i dati dell'utente
            $data[] = $detail_user[$attendee->id]->idnumber;
			
			$qualifica = $DB->get_record_sql("SELECT 
											uid.data
									FROM
											{user_info_data} uid 
									WHERE
											uid.userid = ".$attendee->id."	AND	
											uid.fieldid = 1");
			$data[] = $qualifica->data;
            list($id, $fullname, $shortname) = get_user_organisation($attendee->id);
			$data[] = '<span title="'.$fullname.'">'.$shortname.'</span>';
			$data[] = str_replace(' ', '&nbsp;', get_string('status_'.facetoface_get_status($attendee->statuscode), 'facetoface'));
			$data[] = date('d/m/Y H:i:s',$attendee->timecreated);
			
			if (!$takeattendance) { // Campi SOLO per l'iscrizione / sostituzione
				$user_changes = user_get_users_by_id(array($attendee->f2_user_changes));
				$data[] = $user_changes[$attendee->f2_user_changes]->firstname." ".$user_changes[$attendee->f2_user_changes]->lastname;
				//$data[] = '	<textarea name="comments" cols=80 rows=2></textarea>';
				$data[] = ($attendee->f2_send_notif) ? "SI" : "NO";
				$data[] = '<input type="text" name="note_partecipante['.$attendee->id.']" value="'.$dati_facetoface_signups->f2_note.'">';
                
				if($attendee->statuscode == MDL_F2F_STATUS_BOOKED && $substitute) {
					$converter = new Encryption($CFG->passwordsaltmain);
					//$goto = $CFG->wwwroot."/mod/facetoface/substituted.php?s=$session->id&backtoallsessions=$backtoallsessions&idusrsbt=".$converter->encode($attendee->id);	
					$goto = $CFG->wwwroot."/mod/facetoface/substituted.php?s=$session->id&backtoallsessions=$backtoallsessions&idusrsbt=".$attendee->id;	
					if(has_capability('mod/facetoface:removeattendees', $context)){			
						$data[] = html_writer::empty_tag('input', array('type' => 'button', 'value' => get_string('substitute', 'facetoface'), 'onclick' => "document.location.href='$goto'"));
					}				
				} else $data[] = '';
			} else { // Campi SOLO per la consuntivazione delle presenze
				$presenza = html_writer::empty_tag('input', array('type' => 'text', 'name' => 'presenza', 'value' => $attendee->presenza, 'disabled' => 'disabled'));
				
				if ($attendee->stores)
					$data[] = $attendee->presenza;
				else
					$data[] = $attendee->presenza;
					//$data[] = $presenza;

				$VAs = get_verifica_apprendimento();
				
				if ($attendee->va == '0') {
					if ($attendee->stores){
						$va_out = "";
					}else{
						$va_out = "-";
					/*	
						$va_out = html_writer::start_tag('select', array('name' => 'va', 'disabled' => 'disabled'));
						$va_out .= html_writer::start_tag('option', array('value' => 0));
						$va_out .= html_writer::tag('label', 'Scegli...');
						$va_out .= html_writer::end_tag('option');
						$va_out .= html_writer::end_tag('select');
					*/
					}
				} else {
					if ($attendee->stores){
						$va_out = $VAs[$attendee->va]->descrizione;
					} else {
						$va_out = $VAs[$attendee->va]->descrizione;
						/*
						$va_out = html_writer::start_tag('select', array('name' => 'va', 'disabled' => 'disabled'));
						$va_out .= html_writer::start_tag('option', array('value' => $VAs[$attendee->va]));
						$va_out .= html_writer::tag('label', $VAs[$attendee->va]->descrizione);
						$va_out .= html_writer::end_tag('option');
						$va_out .= html_writer::end_tag('select');
						*/
					}
				}
				$data[] = $va_out;
			}

        	$table->data[] = $data;
//echo "DEBUG 0";
        }
//echo "DEBUG 1";
		/* 
		 * 	Creo degli input hidden per gli utenti selezionati nelle altre pagine della paginazione
			$user_checked 		sono gli utenti in stato iscritto
			$user_checked_sub 	sono gli utenti in stato sostituito*/
		if ($user_checked) {
			foreach ($user_checked as $usr_ck)
				echo '<input type="hidden" class="hidden_id_attendees[]" name="id_attendees['.$usr_ck.']" value='.$usr_ck.' />';
		}
		if ($user_checked_sub) {
			foreach ($user_checked_sub as $usr_ck)
				echo '<input type="hidden" class="hidden_id_attendees[]" name="id_attendees_substitute['.$usr_ck.']" value='.$usr_ck.' />';
		}		
		// Submit ricerca
		echo '<table width="100%"><tr width="100%">';
		echo '<td width="100px"><input maxlength="254" size="50" name="nome" type="text" id="id_nome" value="'.$nome.'" /></td>';
		echo '<td><input name="Cerca" value="Cerca" type="submit" id="id_Cerca" /></td>';
		if ((has_capability('mod/facetoface:addattendees', $context) || 
				has_capability('mod/facetoface:removeattendees', $context)) && (can_booking($courseid, $session->id, $coursetype) || is_siteadmin($USER->id))) {
		// Add/remove attendees
				$editattendees_link = new moodle_url('editattendees.php', array('s' => $session->id, 'backtoallsessions' => $backtoallsessions));
			  if ($takeattendance)   echo '</tr><tr width="100%"><td>';
			  else echo '<td width="10px" align="right">';
			echo '<input type="button" value="'.get_string("gestisci_iscritti", "facetoface").'" title="Rimuovi e aggiungi iscritti" onclick="parent.location=\''.$editattendees_link.'\'"></td>';
		}
		if (!$takeattendance) { 
			if ($can_take_attendance && $session->datetimeknown) { // && facetoface_has_session_started($session, time())) {
				// Take attendance
				$attendance_url = new moodle_url('attendees.php', array('s' => $session->id, 'takeattendance' => '1', 'backtoallsessions' => $backtoallsessions));
				echo '<td width="10px" align="right"><input type="button" value="'.get_string("takeattendance", "facetoface").'" onclick="parent.location=\''.$attendance_url.'\'"></td>';
			}
		}
		if (!$takeattendance) {
			// bottone dettagli
			echo '<td width="10px" align="right">';
				if(has_capability('block/f2_gestione_risorse:send_auth_mail', $context)){
					$button_dett = new single_button(new moodle_url($CFG->dirroot.'blocks/f2_gestione_risorse/auth_mail_detail_popup.php'),
									get_string('elenco_utenti_autorizzati', 'facetoface'));
					$action_dett = new popup_action('click','/blocks/f2_gestione_risorse/send_auth_mail/auth_mail_detail_popup.php?ed='.$session->id.'','elenco_utenti_autorizzati');
					$button_dett->add_action($action_dett);
					echo $OUTPUT->render($button_dett);
				}
			echo '</td>';
		}
		echo '</tr></table>';

		// record visualizzati: 
		echo "<p>".get_string('count_tot_rows', 'local_f2_traduzioni',$total_rows)."</p>";
		
		// Stampa l'esito del salvataggio delle note
		if ($esito_note) echo "<p  class=\"msg_feedback\">".$esito_note."</p>";
		// Stampa se non è presente il campo sirp o sirp data
		if ($sirp_session) echo "<br><br><b>".$sirp_session."</b>";
		
		// Stampa se non è presente la notifica di autorizzazione
		if ($notif_autoriz_session) echo "<br><br><b>".$notif_autoriz_session."</b>";

		// Paginazione	
		$paging_bar = new paging_bar_f2($total_rows, $page, $perpage, $form_id, $post_extra);
                
		if (sizeof($attendees) > 0) {
			if ($takeattendance) { 
				echo '<p align="left"><br>';
				echo 'Presenza ';
				echo html_writer::empty_tag('input', array('type' => 'text', 'name' => 'presenza_'.$form_id, 'value' => '0', 'size' => '5'));

				$VAs = get_verifica_apprendimento();
				
				echo '<br>Verifica apprendimento';
				
				echo '&nbsp;' . html_writer::start_tag('select', array('name' => 'va_'.$form_id));
				echo html_writer::start_tag('option', array('selected'));
				echo html_writer::tag('label', 'Scegli...');
				echo html_writer::end_tag('option');
				foreach ($VAs as $va) {
					echo html_writer::start_tag('option', array('value' => $va->id));
					echo html_writer::tag('label', $va->descrizione);
					echo html_writer::end_tag('option');
				}
				echo html_writer::end_tag('select');
				echo '<br><br>' . html_writer::empty_tag('input', array('type' => 'submit', 'value' => 'Applica ai selezionati', 'name' => 'saveattendace', 'onclick' => 'return takeattendance_checking(document.'.$form_id.'.va_'.$form_id.', document.'.$form_id.'.presenza_'.$form_id.')'));        
				echo '</p>';;
				
				// Stampa tabella partecipanti
				echo html_writer::table($table);
				
				echo html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('stores', 'facetoface'), 'name' => 'stores'));
				echo '&nbsp;' . html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'cancelform', 'value' => get_string('back')));
				 
				
				echo html_writer::end_tag('form');
			}
			else {
				// Stampa tabella partecipanti
				echo html_writer::table($table);

                        // Actions
                        echo html_writer::start_tag('p');
                        if(has_capability('mod/facetoface:addattendees', $context)){
                        	echo html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'salva_note', 'value' => get_string('savenote', 'facetoface')));
                        }
                        //Submit invia mail
				if (isSupervisore($USER->id) || is_siteadmin())
					echo html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'invia_mail', 'value' => get_string('sendmail_auth', 'facetoface'), 'onclick' => 'return confirmSendMail()'));
				echo html_writer::end_tag('p') . html_writer::end_tag('form');
				echo html_writer::start_tag('p');
			}
		} else {
			if ($takeattendance)
				echo heading_msg(get_string('noattendees', 'facetoface'));
			else{
				$user_session = facetoface_get_users_instatus($session->id, array(MDL_F2F_STATUS_BOOKED, MDL_F2F_STATUS_NO_SHOW, MDL_F2F_STATUS_FULLY_ATTENDED));
				if($user_session->count)
					echo heading_msg(get_string('attendees_booked', 'facetoface'));
				else
					echo heading_msg(get_string('noattendees', 'facetoface'));
				}
		}

		echo $paging_bar->print_paging_bar_f2();
    }
}

// Go back
$url = new moodle_url('/course/view.php', array('id' => $course->id));
if ($backtoallsessions) {
    $url = new moodle_url('/mod/facetoface/view.php', array('f' => $facetoface->id, 'backtoallsessions' => $backtoallsessions));
}
//echo html_writer::link($url, get_string('goback', 'facetoface')) . html_writer::end_tag('p');

/**
 * Print unapproved requests (if user able to view)
 */
if ($can_approve_requests) {
    echo html_writer::empty_tag('br', array('id' => 'unapproved'));
    if (!$requests) {
        echo $OUTPUT->notification(get_string('noactionableunapprovedrequests', 'facetoface'));
    }
    else {
        heading_msg(get_string('unapprovedrequests', 'facetoface'));

        $action = new moodle_url('attendees.php', array('s' => $s));
        echo html_writer::start_tag('form', array('action' => $action->out(), 'method' => 'post'));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => $USER->sesskey));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 's', 'value' => $s));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'backtoallsessions', 'value' => $backtoallsessions)) . '</p>';

        $table = new html_table();
        $table->summary = get_string('requeststablesummary', 'facetoface');
        $table->head = array(get_string('name'), get_string('timerequested', 'facetoface'),
                            get_string('decidelater', 'facetoface'), get_string('decline', 'facetoface'), get_string('approve', 'facetoface'));
        $table->align = array('left', 'center', 'center', 'center', 'center');

        foreach ($requests as $attendee) {
            $data = array();
            $attendee_link = new moodle_url('/user/view.php', array('id' => $attendee->id, 'course' => $course->id));
            $data[] = html_writer::link($attendee_link, format_string(fullname($attendee)));
            $data[] = userdate($attendee->timerequested, get_string('strftimedatetime'));
            $data[] = html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'requests['.$attendee->id.']', 'value' => '0', 'checked' => 'checked'));
            $data[] = html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'requests['.$attendee->id.']', 'value' => '1'));
            $data[] = html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'requests['.$attendee->id.']', 'value' => '2'));
            $table->data[] = $data;
        }

        echo html_writer::table($table);

        // 19 03 2019 echo html_writer::tag('p', html_writer::empty_tag('input', array('type' => 'submit', 'value' => 'Update requests')));
        echo html_writer::tag('p', html_writer::empty_tag('input', array('type' => 'submit', 'value' => 'Richieste di aggiornamento')));
        echo html_writer::end_tag('form');
    }
}
echo "<a href='".$CFG->wwwroot."/blocks/f2_apprendimento/managecourse_obb.php'><button type='button'>Torna alla gestione corsi obiettivo</button></a>&nbsp;";
echo "<a href='".$CFG->wwwroot."/mod/facetoface/view.php?id=".$cm->id."'><button type='button'>Torna alle edizioni</button></a>"; // aggiunta del 2018 01 31

/**
 * Print cancellations (if user able to view)
 */
 /*
if (!$takeattendance && $can_view_cancellations && $cancellations) {
    echo html_writer::empty_tag('br');
    echo $OUTPUT->heading(get_string('cancellations', 'facetoface'));

    $table = new html_table();
    $table->summary = get_string('cancellationstablesummary', 'facetoface');
    $table->head = array(get_string('name'), get_string('timesignedup', 'facetoface'),
                         get_string('timecancelled', 'facetoface'), get_string('cancelreason', 'facetoface'));
    $table->align = array('left', 'center', 'center');

    foreach ($cancellations as $attendee) {
        $data = array();
        $attendee_link = new moodle_url('/user/view.php', array('id' => $attendee->id, 'course' => $course->id));
        $data[] = html_writer::link($attendee_link, format_string(fullname($attendee)));
        $data[] = userdate($attendee->timesignedup, get_string('strftimedatetime'));
        $data[] = userdate($attendee->timecancelled, get_string('strftimedatetime'));
        $data[] = format_string($attendee->cancelreason);
        $table->data[] = $data;
    }
    echo html_writer::table($table);
}
*/
/**
 * Print page footer
 */
echo $OUTPUT->box_end();
echo $OUTPUT->footer($course);
