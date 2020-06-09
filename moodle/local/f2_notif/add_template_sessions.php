<?php

// $Id$
require_once('../../config.php');
require_once($CFG->dirroot.'/local/f2_notif/lib.php');
global $DB;

$value = $_POST;
$courseid = $value[courseid];
$sessionid = $value[sessionid];

    $coursecontext = get_context_instance(CONTEXT_COURSE, $courseid);
    require_capability('moodle/course:update', $coursecontext);

foreach($value[id_temp] as $id){
	$id_templ = $id;
	break;
}

//Inizio: ricavo l'id del tipo di notifica partendo dall'id della notifica
	$sql_query="SELECT 	
					ntempl.id_tipo_notif  

				FROM 	
					{f2_notif_templates} ntempl
				WHERE 
					ntempl.id = ".$id_templ;
	
	$id_tipo_templ = $DB->get_record_sql($sql_query);
//Fine: ricavo l'id del tipo di notifica partendo dall'id della notifica


//Inizio: controllo se nella tebella è già presente il tipo di notifica per quel corso(non possono esserci due tipi di notifica uguali per la stessa edizione)
//In questo caso effettuo l'aggiornamento altrimenti effettuo l'inserimento

if($id_record=get_notif_course_edizione_tipo ($courseid,$sessionid,$id_tipo_templ->id_tipo_notif)){//controlla se nella tabella f2_notif_corso esiste già una riga con i parametri passati

		$parametro= new stdClass();
		$parametro->id = $id_record->id;
		$parametro->id_notif_templates = $id_templ;
		$parametro->id_corso =$courseid;
		$parametro->id_edizione =$sessionid;
		$parametro->id_tipo_notif =$id_tipo_templ->id_tipo_notif;
		
		if(update_template_course($parametro)){
			// header('Location: template_sessions.php?courseid='.$courseid.'&sessionid='.$sessionid);
				$location_next = $CFG->wwwroot.'/local/f2_notif/template_sessions.php?courseid='.$courseid.'&sessionid='.$sessionid;
				redirect(new moodle_url($location_next));
		}else
		{
			echo "<b>Errore. Notifica non salvata<b><br>";
			echo 'Seleziona il pulsante "Indietro" per tornare alla pagina delle notifiche.<br><br>';
			echo '<a href=\'template_sessions.php?courseid='.$courseid.'&sessionid='.$sessionid.'\'><button type="button">Indietro</button></a>';
		}

}else{

	if(add_template_course($courseid,$id_templ,$sessionid,$id_tipo_templ->id_tipo_notif)){
		// header('Location: template_sessions.php?courseid='.$courseid.'&sessionid='.$sessionid);
			$location_next = $CFG->wwwroot.'/local/f2_notif/template_sessions.php?courseid='.$courseid.'&sessionid='.$sessionid;
			redirect(new moodle_url($location_next));
	}
	else{
		echo "<b>Errore. Notifiche non salvate<b><br>";
		echo 'Seleziona il pulsante "Indietro" per tornare alla pagina delle notifiche.<br><br>';
		echo '<a href=\'template_sessions.php?courseid='.$courseid.'&sessionid='.$sessionid.'\'><button type="button">Indietro</button></a>';
	}
}


?>