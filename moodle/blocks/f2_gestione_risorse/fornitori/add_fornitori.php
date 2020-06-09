<?php
// $Id$
global $CFG,$DB,$OUTPUT;

require_once '../../../config.php';
require_once($CFG->dirroot.'/blocks/f2_gestione_risorse/lib.php');
require_once('fornitori_form.php');
require_once($CFG->dirroot.'/lib/formslib.php');

require_login();

$organisation_id  = optional_param('organisationid', 0, PARAM_INT); // organisation id
$viewable_organisation_id  = optional_param('viewableorganisationid', 0, PARAM_INT); // viewable organisation id

$context = get_context_instance(CONTEXT_SYSTEM);

$capability = has_capability('block/f2_gestione_risorse:add_fornitori', $context);
if(!$capability){
	print_error('nopermissions', 'error', '', 'fornitori');
}

if (empty($CFG->loginhttps)) {
	$securewwwroot = $CFG->wwwroot;
} else {
	$securewwwroot = str_replace('http:','https:',$CFG->wwwroot);
}

$PAGE->set_context($context);
$PAGE->set_url('/blocks/f2_gestione_risorse/fornitori');
$PAGE->navbar->add(get_string('fornitori', 'block_f2_gestione_risorse'),new moodle_url('./anagrafica_fornitori.php'));
$PAGE->navbar->add(get_string('add_fornitore', 'block_f2_gestione_risorse'), new moodle_url($url));
$PAGE->set_title(get_string('add_fornitore', 'block_f2_gestione_risorse'));
$PAGE->set_heading($SITE->shortname.': '.$blockname);
$PAGE->set_pagelayout('standard');
$PAGE->settingsnav;

// inizio import per generazione albero //
$PAGE->requires->js('/f2_lib/jquery/jquery-1.7.1.min.js');	
$PAGE->requires->js('/f2_lib/jquery/jquery-ui.min.js');
$PAGE->requires->js('/f2_lib/jquery/jquery.cookie.js');
$PAGE->requires->js('/f2_lib/jquery/jquery.dynatree.js');
$PAGE->requires->css('/f2_lib/jquery/css/skin/ui.dynatree.css');
$PAGE->requires->js('/f2_lib/jquery/jquery.blockUI.js');
// fine import per generazione albero //

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('add_fornitore', 'block_f2_gestione_risorse'));
echo $OUTPUT->box_start();
echo '<div class="contenitoreglobale">';

$mform = new add_fornitori_form(null);
$conferma=0;

if(!$mform->get_data()){
	$mform->display();

$str = <<<'EFO'
<script type="text/javascript">
//<![CDATA[
document.getElementById('id_tipo_formazione_0').setAttribute('checked','checked');
document.getElementById('id_tipo_formazione_1').setAttribute('checked','checked');
document.getElementById('id_tipo_formazione_2').setAttribute('checked','checked');
//]]>
</script>
EFO;
echo $str;	

}
else if($data = $mform->get_data()){
		
		$exist_scuola=0;//Controllo se la scuola è già presente
		$exist_cf = 0;//Controllo se il codice fiscale è già presente
		$conferma =0;//Controllo se l'inserimento è andato a buon fine
		
		//queste variabili sono state inserite in OR perchè per ogni posizione del numero equivale un valore ES 001=Individuale, 011=Obiettivo, Individuale   
		$tipo_formazione=$data->tipo_formazione[0] | $data->tipo_formazione[1] | $data->tipo_formazione[2];
		
		if(!$data->stato) 
			$stato=0;
		else 
			$stato=1;
			
		if(!$data->preferito) 
			$preferito=0;
		else 
			$preferito=1;
		
			$dati_fornitore = new stdClass;
			$dati_fornitore->denominazione		= $data->nome			  ;
			$dati_fornitore->cognome            = $data->cognome_contatto ;
			$dati_fornitore->nome               = $data->nome_contatto    ;
			$dati_fornitore->url                = $data->url              ;
			$dati_fornitore->partita_iva        = $data->partita_iva      ;
			$dati_fornitore->codice_fiscale     = $data->codice_fiscale   ;
			$dati_fornitore->codice_creditore   = $data->codice_creditore ;
			$dati_fornitore->stato              = $stato                  ;
			$dati_fornitore->indirizzo          = $data->indirizzo        ;
			$dati_fornitore->cap                = $data->cap              ;
			$dati_fornitore->citta              = $data->citta            ;
			$dati_fornitore->provincia          = $data->provincia        ;
			$dati_fornitore->paese              = $data->paese            ;
			$dati_fornitore->fax                = $data->fax              ;
			$dati_fornitore->telefono           = $data->telefono         ;
			$dati_fornitore->email              = $data->email            ;
			$dati_fornitore->preferiti          = $preferito              ;
			$dati_fornitore->tipo_formazione    = $tipo_formazione     	  ;
			$dati_fornitore->nota               = $data->note             ;
			$dati_fornitore->id_org             = $data->organisationid   ;
			

		//SE E' MINORE DI ZERO SIGNIFICA CHE NON E' STATA SELEZIONATA NESSUNA SCUOLA QUINDI POSSO 
		//EVITARE IL CONTROLLO
			//CONTROLLO SE LA SCUOLA E' GIA' PRESENTE NELLA TABELLA
			if($DB->record_exists('f2_fornitori', array('id_org' => $data->organisationid)) && $data->organisationid > 0){
				$exist_scuola=1;
			}else if($DB->record_exists('f2_fornitori', array('denominazione' => $data->nome))){
				$exist_scuola=1;
			}else if($DB->record_exists('f2_fornitori', array('codice_fiscale' => $data->codice_fiscale))){
				$exist_cf=1;
			}
			 else{//CONTROLLO SE L'INSERIMENTO E' ANDATO A BUON FINE
				if(!insert_fornitore($dati_fornitore))
				$conferma=1;
			 }			 
}

	if($exist_scuola || $exist_cf){
	
	$dati_fornitore_post = $_POST;
	
		$tipo_formazione = $dati_fornitore_post['tipo_formazione'];
		$programmata = substr($tipo_formazione, -1);
		$obiettivo = substr($tipo_formazione, -2, 1);
		$individuale =substr($tipo_formazione, -3, 1);
	
		$mform = new add_fornitori_form(null);
		
		$mform->set_data( array('nome' => $dati_fornitore_post['denominazione']));
		$mform->set_data( array('cognome_contatto' => $dati_fornitore_post['cognome']));
		$mform->set_data( array('nome_contatto' => $dati_fornitore_post['nome']));
		$mform->set_data( array('url' => $dati_fornitore_post['url']));
		$mform->set_data( array('partita_iva' => $dati_fornitore_post['partita_iva']));
		$mform->set_data( array('codice_fiscale' => $dati_fornitore_post['codice_fiscale']));
		$mform->set_data( array('codice_creditore' => $dati_fornitore_post['codice_creditore']));
		$mform->set_data( array('stato' => $dati_fornitore_post['stato']));
		$mform->set_data( array('indirizzo' => $dati_fornitore_post['indirizzo']));
		$mform->set_data( array('cap' => $dati_fornitore_post['cap']));
		$mform->set_data( array('citta' => $dati_fornitore_post['citta']));
		$mform->set_data( array('provincia' => $dati_fornitore_post['provincia']));
		$mform->set_data( array('paese' => $dati_fornitore_post['paese']));
		$mform->set_data( array('fax' => $dati_fornitore_post['fax']));
		$mform->set_data( array('telefono' => $dati_fornitore_post['telefono']));
		$mform->set_data( array('email' => $dati_fornitore_post['email']));
		$mform->set_data( array('preferito' => $dati_fornitore_post['preferiti']));
		$mform->set_data( array('tipo_formazione[0]' =>(($programmata) ? True : False)));
		$mform->set_data( array('tipo_formazione[1]' =>(($obiettivo) ? True : False)));
		$mform->set_data( array('tipo_formazione[2]' =>(($individuale) ? True : False)));
		$mform->set_data( array('note' => $dati_fornitore_post['nota']));
		$mform->set_data( array('organisationid' => $dati_fornitore_post['id_org']));
	
	$mform->display();
	
		$str = <<<'EFO'
<script type="text/javascript">
//<![CDATA[
document.getElementById('id_nome').setAttribute('readonly','readonly')
//]]>
</script>
EFO;
echo $str;

		if($exist_scuola){
			echo '<b>Scuola non inserita.<br>La scuola &egrave; gi&agrave; presente nella lista dei fornitori.</b><br>';
			}
		if($exist_cf){
			echo '<b>Fornitore non inserito.<br>Il codice fiscale &egrave; gi&agrave; presente nella lista dei fornitori.</b><br>';
		}
	}
	
	if($conferma){//Se è stato inseriro correttamente il fornitore
		echo '<b>Fornitore inserito correttamente.</b><br>';
		echo 'Seleziona il pulsante "Indietro" per tornare alla pagina dei fornitori.<br><br>';
		echo '<a href="anagrafica_fornitori.php"><button type="button">Indietro</button></a>';
	}
	
echo '</div>';
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
?>