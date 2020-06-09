<?php
// $Id$
global $CFG,$DB,$OUTPUT;

require_once '../../config.php';
require_once($CFG->dirroot.'/local/f2_notif/lib.php');
require_once('template_form.php');
$id_templ = optional_param('id', '0', PARAM_INT);
 
require_login();

$context = get_context_instance(CONTEXT_SYSTEM);

$capability = has_capability('local/f2_notif:edit_notifiche', $context);
if(!$capability){
	print_error('nopermissions', 'error', '', 'notif');
}

if (empty($CFG->loginhttps)) {
	$securewwwroot = $CFG->wwwroot;
} else {
	$securewwwroot = str_replace('http:','https:',$CFG->wwwroot);
}

$PAGE->set_context($context);
$PAGE->set_url('/local/f2_notif');
$PAGE->navbar->add(get_string('modelli_notifica', 'local_f2_notif'),new moodle_url('./templates.php'));
$PAGE->navbar->add(get_string('mod_notif', 'local_f2_notif'), new moodle_url($url));
$PAGE->set_title(get_string('mod_notif', 'local_f2_notif'));
$PAGE->set_heading($SITE->shortname.': '.$blockname);
$PAGE->set_pagelayout('standard');
$PAGE->settingsnav;

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('mod_notif', 'local_f2_notif'));

$notif_ind = get_tipo_notif_byname(F2_TIPO_NOTIF_INDIVIDUALE);
$str = <<<EOF
<script type="text/javascript">
//<![CDATA[
function change_notif(id)
{
    var segnaposto = $("#segnaposto_tipo_"+id).val();
    $("#p_segnaposto").text(segnaposto);
//if(id=={$notif_ind}) {
//    $("#id_canale > option").each(function() {
//        if(this.text == "On-Line") $(this).prop("disabled", true);
//    });
//} else {
//    $("#id_canale > option").each(function() {
//        if(this.text == "On-Line") $(this).prop("disabled", false);
//    });
//}
}
function confirmPredefinito()
{
    var predefinito= document.getElementById("id_predefinito").checked;
	if(predefinito) {//Se è ceccato il campo di default controllo se è presente un altra notifica di default per quel canale e per il tipo di notifica
        var tipo_not = document.getElementById("id_id_tipo_notif").value;
        var canale = document.getElementById("id_canale").value;

        var get_notifica = tipo_not+"_"+canale;

        var num = 0;
        var notif_predefinite = document.getElementsByName('notifica_predefinita[]');
        var tot = notif_predefinite.length;
        for (i = 0; i < tot; i++) {
            if(notif_predefinite[i].value == get_notifica)
             num++;
        }
        if(num > 0) { 
            return confirm("E' già presente una notifica di default. Sostituire?");
        }
	}
}
//]]>
</script>
EOF;
echo $str;

echo $OUTPUT->box_start();
echo '<div class="contenitoreglobale">';


$mform = new template_form(null);
$conferma=0;//CONTROLLO SE IL TEMPLATE E' STATO MODIFICATO CORRETTAMENTE

if(!$mform->get_data()){

	$dati_template = get_template ($id_templ);


	$mform->set_data( array('id_templ' => $id_templ));
	$mform->set_data( array('title' => $dati_template->title));
	$mform->set_data( array('description' => $dati_template->description));
	$mform->set_data( array('subject' => $dati_template->subject));
	$mform->set_data( array('message_editor' => array('text'=>$dati_template->message, 'format'=>$data->messageformat)));
	$mform->set_data( array('id_tipo_notif' => $dati_template->id_tipo_notif));
	$mform->set_data( array('stato' => $dati_template->stato));
	$mform->set_data( array('canale' => $dati_template->canale));
	$mform->set_data( array('predefinito' => $dati_template->predefinito));

		$mform->display();
		
	$notifiche_predefinite=get_notifica_predefinita();
	
	foreach($notifiche_predefinite as $notifica_predefinita){
		echo '<input type="hidden" id='.$notifica_predefinita->id.' name="notifica_predefinita[]" value="'.$notifica_predefinita->id_tipo_notif.'_'.$notifica_predefinita->canale.'">';
	}
	
	$notif_tipo=get_tipo_notif(-1,'-1');
	//Per ogni tipo di notifica creo una variabile nascosta con il valore dei segnaposto associati 
	//in questo modo tramite javascript visualizzo i segnaposti associati alla scelta del tipo di notifica
	foreach($notif_tipo as $notif){
		echo '<input type="hidden" id="segnaposto_tipo_'.$notif->id.'" name="segnaposto_tipo_'.$notif->id.'" value="'.str_replace(array('\\','/'), array('',''), $notif->segnaposto).'">';
	}
	
	echo '<b title="Il formato del segnaposto è [fullname_corso]">Segnaposti validi:</b>';
	echo '<p id="p_segnaposto" style="width:600px"></p>';
	
$str = <<<EOF
<script type="text/javascript">
//<![CDATA[
var segnaposto = $("#segnaposto_tipo_"+$("#id_id_tipo_notif").val()).val();
$("#p_segnaposto").text(segnaposto);
//$("#id_id_tipo_notif").click();
//]]>
</script>
EOF;
echo $str;
	
}
else if($data = $mform->get_data()){

		if(!$data->predefinito) 
			$predefinito=0;
		else 
			$predefinito=1;

			$dati_template = new stdClass;
			$dati_template->id			         = $data->id_templ					  ;
			$dati_template->title			     = $data->title						  ;
			$dati_template->description          = $data->description				  ;
			$dati_template->subject              = $data->subject   				  ;
			$dati_template->message              = $data->message_editor['text']      ;
			$dati_template->id_tipo_notif        = $data->id_tipo_notif			      ;
			$dati_template->stato                = $data->stato					      ;
			$dati_template->canale               = $data->canale				      ;
			$dati_template->predefinito          = $predefinito			  			  ;
			$dati_template->lstupd 				 = date('Y-n-j H:i:s')				  ;
			$dati_template->usrname 			 = $data->userid					  ;		
			
		if(update_template($dati_template))
			$conferma=1;
		
	if($conferma){
		echo '<b>Template modificato correttamente.</b><br>';
		echo 'Seleziona il pulsante "Indietro" per tornare alla pagina dei template.<br><br>';
		echo '<a href="templates.php"><button type="button">Indietro</button></a>';
	}
}
	

echo '</div>';
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
?>