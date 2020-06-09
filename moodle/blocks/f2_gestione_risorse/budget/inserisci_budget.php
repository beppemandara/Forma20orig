<?php
// $Id$
global $CFG,$PAGE,$OUTPUT,$SITE;

require_once '../../../config.php';
require_once $CFG->libdir . '/formslib.php';
require_once($CFG->dirroot.'/blocks/f2_gestione_risorse/lib.php');
require_once('budget_form.php');

require_login();

$context = get_context_instance(CONTEXT_SYSTEM);
$saved         = optional_param('saved', false, PARAM_BOOL);
$alert         = optional_param('alert', false, PARAM_BOOL);

$capability = has_capability('block/f2_gestione_risorse:budget_edit', $context);
if(!$capability){
	print_error('nopermissions', 'error', '', 'budget');
}

if (empty($CFG->loginhttps)) {
	$securewwwroot = $CFG->wwwroot;
} else {
	$securewwwroot = str_replace('http:','https:',$CFG->wwwroot);
}

$blockname = get_string('pluginname', 'block_f2_gestione_risorse');

$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add(get_string('inserisci_budget', 'block_f2_gestione_risorse'));
$PAGE->set_url('/blocks/f2_gestione_risorse/budget/inserisci_budget.php');
$PAGE->set_title(get_string('configurazione_capitoli', 'block_f2_gestione_risorse'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('configurazione_capitoli', 'block_f2_gestione_risorse'),new moodle_url('./inserisci_budget.php'));
$PAGE->set_heading($SITE->shortname.': '.$blockname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('inserisci_budget', 'block_f2_gestione_risorse').' - '.get_string('configurazione_capitoli', 'block_f2_gestione_risorse'));

$str = <<<'EFO'
<script type="text/javascript">
//<![CDATA[

function confirmSubmitAvanti()
{

var form = document.getElementById("mform1");

var modificato=0;

  for (var i = 0; i < form.elements.length; i++) {
 	 if(form.elements[i].type == "text"){
	      if(form.elements[i].value != form.elements[i].defaultValue){
	      	 modificato=1;
	      }
      }
  }

  if(modificato == 1){
		var agree=confirm("Le modifiche effettuate andranno perdute. Proseguire?");
			if (agree)
				return true ;
			else
				return false ;
			}

	else 
		return true;
}


function confirmSubmitApplica()
{

    var form = document.getElementById("mform1");
    var regExp=/^[0-9]+\.\d{2}$/
    var err = 0;
    
  for (var i = 0; i < form.elements.length; i++) {
 	 if(form.elements[i].type == "text"){
	       if(form.elements[i].value == ''){
                    document.getElementById("error_"+form.elements[i].name).style.display = "block";
                    err = err + 1;
               } else {
                    document.getElementById("error_"+form.elements[i].name).style.display = "none";
               }
      }
  }
  
  if (err > 0) {
    return false;
  } else {
        var modificato=0;

        if (
                document.getElementById("coefficiente_formativo").value != document.getElementById("coefficiente_formativo").defaultValue || 
                document.getElementById("assegnazione_giorni_crediti_aula").value != document.getElementById("assegnazione_giorni_crediti_aula").defaultValue ||
                document.getElementById("criterio_assegnamento_corsi_lingue").value != document.getElementById("criterio_assegnamento_corsi_lingue").defaultValue ||
                document.getElementById("numero_strutture").value != document.getElementById("numero_strutture").defaultValue
                )
                modificato = 1;

          if(modificato == 1){
                        var agree=confirm("Le modifiche apportate ai parametri comporteranno dei cambiamenti nei parziali budget. Tutte le modifiche effettuate sui parziali andranno perdute. Proseguire?");
                                if (agree)
                                        return true ;
                                else
                                        return false ;
                                }

                else 
                        return true;
          }


}

//]]>
</script>
EFO;
echo $str;

echo $OUTPUT->box_start();
echo '<div class="contenitoreglobale">';

$dati_budget= get_parametri_budget();
$mform = new inserisci_budget_form(null, compact('dati_budget'));


	//SE VIENE CLICCATO IL PULSANTE SALVA DEVE ESSERE AGGIORNATA LA TABELLA {f2_parametri} e la tabella {f2_partialbdgt}
	if($mform->is_submitted()){	
		$anno_in_corso = get_anno_formativo_corrente();
                
		$data = $_POST;
		if(!update_parametri_budget($data) || !update_criterioa_budget($data->coefficiente_formativo,$data->numero_strutture,$anno_in_corso)) {
                    $baseurl = new moodle_url($CFG->wwwroot.'/blocks/f2_gestione_risorse/budget/inserisci_budget.php', array('alert'=>true));
                } else {
                    $baseurl = new moodle_url($CFG->wwwroot.'/blocks/f2_gestione_risorse/budget/inserisci_budget.php', array('saved'=>true));
                }

                redirect($baseurl);
	}
        
        $mform->display();
        
        if ($saved)
            echo '<span style="color:green">Dati salvati correttamente.</span><br><br>';
        
        if ($alert)
            echo '<br><br><span style="color:red">Ci sono stati degli errori durande il salvataggio dei dati</span>';
	
	echo get_string('campi_obbligatori', 'block_f2_gestione_risorse');
	
echo '</div>';
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
