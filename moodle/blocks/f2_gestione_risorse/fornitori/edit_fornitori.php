<?php
// $Id$
global $CFG,$DB,$OUTPUT;

require_once '../../../config.php';
require_once($CFG->dirroot.'/blocks/f2_gestione_risorse/lib.php');
require_once('fornitori_form.php');
$id_for = optional_param('id', '0', PARAM_INT);

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
$PAGE->set_url('/blocks/f2_gestione_risorse/fornitori/edit_fornitori.php');
$PAGE->navbar->add(get_string('fornitori', 'block_f2_gestione_risorse'),new moodle_url('./anagrafica_fornitori.php'));
$PAGE->navbar->add(get_string('edit_fornitore', 'block_f2_gestione_risorse'), new moodle_url($url));
$PAGE->set_heading($SITE->shortname.': '.get_string('fornitori', 'block_f2_gestione_risorse'));
$PAGE->set_title(get_string('edit_fornitore', 'block_f2_gestione_risorse'));
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
echo $OUTPUT->heading(get_string('edit_fornitore', 'block_f2_gestione_risorse'));
echo $OUTPUT->box_start();
echo '<div class="contenitoreglobale">';


$mform = new add_fornitori_form(null);
$conferma=0;

if(!$mform->get_data()){
//if $id_for
$dati_fornitore= get_fornitore ($id_for);

	if($dati_fornitore){//CONTROLLO SE I DATI ARRIVARI A QUESTA PAGINA SONO GIUSTI(se effettivamente esiste l'id del fornitore da modificare)
	
	//print_r($dati_fornitore->id_org);exit;	
		$tipo_formazione = $dati_fornitore->tipo_formazione;
		$individuale = substr($tipo_formazione, -1);
		$obiettivo = substr($tipo_formazione, -2, 1);
		$programmata =substr($tipo_formazione, -3, 1);
		
		$mform->set_data( array('id_forn' => $id_for));
		$mform->set_data( array('nome' => $dati_fornitore->denominazione));
		$mform->set_data( array('cognome_contatto' => $dati_fornitore->cognome));
		$mform->set_data( array('nome_contatto' => $dati_fornitore->nome));
		$mform->set_data( array('url' => $dati_fornitore->url));
		$mform->set_data( array('partita_iva' => $dati_fornitore->partita_iva));
		$mform->set_data( array('codice_fiscale' => $dati_fornitore->codice_fiscale));
		$mform->set_data( array('codice_creditore' => $dati_fornitore->codice_creditore));
		$mform->set_data( array('stato' => $dati_fornitore->stato));
		$mform->set_data( array('indirizzo' => $dati_fornitore->indirizzo));
		$mform->set_data( array('cap' => $dati_fornitore->cap));
		$mform->set_data( array('citta' => $dati_fornitore->citta));
		$mform->set_data( array('provincia' => $dati_fornitore->provincia));
		$mform->set_data( array('paese' => $dati_fornitore->paese));
		$mform->set_data( array('fax' => $dati_fornitore->fax));
		$mform->set_data( array('telefono' => $dati_fornitore->telefono));
		$mform->set_data( array('email' => $dati_fornitore->email));
		$mform->set_data( array('preferito' => $dati_fornitore->preferiti));
		$mform->set_data( array('tipo_formazione[0]' =>(($programmata) ? True : False)));
		$mform->set_data( array('tipo_formazione[1]' =>(($obiettivo) ? True : False)));
		$mform->set_data( array('tipo_formazione[2]' =>(($individuale) ? True : False)));
		$mform->set_data( array('note' => $dati_fornitore->nota));
		$mform->set_data( array('organisationid' => $dati_fornitore->id_org));

			$mform->display();
//Inizio: imposto il campo nome scuola in sla lettura		
if($dati_fornitore->id_org){
$str = <<<'EFO'
<script type="text/javascript">
//<![CDATA[
document.getElementById('id_nome').setAttribute('readonly','readonly')
//]]>
</script>
EFO;
echo $str;
}
//Fine:
	}
	else
		{
			echo '<b>Fornitore non presente.</b><br>';
			echo 'Seleziona il pulsante "Indietro" per tornare alla pagina dei fornitori.<br><br>';
			echo '<a href="anagrafica_fornitori.php"><button type="button">Indietro</button></a>';
		}
}
else if($data = $mform->get_data()){
	
	$conferma=0;//SE L'UPDATE E' ANDATO A BUON FINE
	$exist_scuola=0;//SE E' GIA PRESENTE UNA SCUOLA CON LO STESSO ID
		//queste variabili sono state inserite in OR perch� per ogni posizione del numero equivale un valore ES 001=Individuale, 011=Obiettivo, Individuale   
		$tipo_formazione=$data->tipo_formazione[0] | $data->tipo_formazione[1] | $data->tipo_formazione[2];
		
		if(!$data->stato) 
			$stato=0;
		else 
			$stato=1;
			
		if(!$data->preferito) 
			$preferito=0;
		else 
			$preferito=1;
						
			$dati_fornitore->codice_fiscale     = $data->codice_fiscale   ;
			$dati_fornitore->codice_creditore   = $data->codice_creditore ;
			
			
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
			$dati_fornitore->id                 = $data->id_forn          ;
			$dati_fornitore->id_org             = $data->organisationid   ;

			//Controllo se è già presente un fornitore con il codice fiscale, la denominazione e l'id_org maggiore di 0
			$query_cf = "
						SELECT 
							COUNT(*) as num
						FROM(
                            SELECT 
                                1
                            FROM
                                {f2_fornitori} f
                            WHERE
                                (f.codice_fiscale = ? OR
                                f.id_org = ? OR
                                f.denominazione = ?) AND
                                f.id <> ? AND
                                f.id_org > 0
							UNION
								SELECT 
									1
								FROM
									{f2_fornitori} f
								WHERE
									(f.codice_fiscale = ? OR
									f.denominazione = ?) AND
									f.id <> ? AND
									f.id_org = 0
							) tmp";
            $num_forn = $DB->get_record_sql($query_cf, array($data->codice_fiscale,$data->organisationid,$data->nome,$data->id_forn,$data->codice_fiscale,$data->nome,$data->id_forn));
			//print_r($num_forn->num);exit;
			
			if($num_forn->num > 0)
				$exist_scuola=1;

		 else{//CONTROLLO SE L'INSERIMENTO E' ANDATO A BUON FINE
			if(update_fornitore($dati_fornitore))
			$conferma=1;
		 }
			
		if($exist_scuola==1){
		
		$dati_fornitore_post = $_POST;
	
			$tipo_formazione = $dati_fornitore_post['tipo_formazione'];
			$individuale = substr($tipo_formazione, -1);
			$obiettivo = substr($tipo_formazione, -2, 1);
			$programmata =substr($tipo_formazione, -3, 1);
		
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
		
			echo '<b>Fornitore non modificato.<br>Il fornitore/codice fiscale &egrave; gi&agrave; presente nella lista dei fornitori.</b><br>';
		}
		
		else{
			if($conferma){//Se è stato inseriro correttamente il fornitore
				echo '<b>Fornitore modificato correttamente.</b><br>';
				echo 'Seleziona il pulsante "Indietro" per tornare alla pagina dei fornitori.<br><br>';
				echo '<a href="anagrafica_fornitori.php"><button type="button">Indietro</button></a>';
			}else
			{
				echo '<b>Errore. Fornitore non modificato.</b><br>';
				echo 'Seleziona il pulsante "Indietro" per tornare alla pagina dei fornitori.<br><br>';
				echo '<a href="anagrafica_fornitori.php"><button type="button">Indietro</button></a>';
			}
		}
}
	

echo '</div>';
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
?>