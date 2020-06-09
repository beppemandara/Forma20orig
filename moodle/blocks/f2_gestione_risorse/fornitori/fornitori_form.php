<?php
// $id$
require_once($CFG->libdir.'/formslib.php');
require_once '../../../config.php';
require_once($CFG->dirroot.'/f2_lib/management.php');

$str = <<<'EFO'
<script type="text/javascript">
//<![CDATA[

function isPhoneNumber(evt)
{
   var charCode = (evt.which) ? evt.which : event.keyCode
//alert(charCode);
/* 
    caratteri digitabili:
    - cifre numeriche 0-9
    - '.' '-' ';' ' ' '+'
*/
   if (charCode > 31 && (charCode < 48 || charCode > 57) && charCode != 46 && charCode != 32 && charCode != 59 && charCode != 45 && charCode != 43)
      return false;

   return true;
}

//]]>
</script>
EFO;
echo $str;

//INIZIO FORM
	class add_fornitori_form extends moodleform {
		public function definition() {
			$mform =& $this->_form;	
			
			$obj = new stdClass();
			$root = get_parametro('p_f2_radice_regione_scuola');
			if (!is_null($root)) {
				$obj->id = $root->val_int;
			}

			if (!is_null($root)) {
				$hierarchy = recursivesubtreejson($obj->id);
			} else {
				$hierarchy = '';  
			}

			$mform->addElement('static', 'organisationselector', get_string('organisation', 'local_f2_domains'), get_organisation_picker_html('organisationtitle','organisationid',get_string('chooseorganisation','local_f2_domains'), 'domini',$hierarchy,'',"if(document.getElementById(\'organisationtitle\').innerHTML != \'\'){document.getElementById(\'id_nome\').value = document.getElementById(\'organisationtitle\').innerHTML; document.getElementById(\'id_nome\').setAttribute(\'readonly\',\'readonly\');}"));
			$mform->addElement('hidden', 'organisationid');			
			$mform->addElement('hidden', 'id_forn');	
			
			$mform->addElement('text', 'nome','Denominazione', 'maxlength="254" size="50"');
			$mform->addRule('nome', null, 'required',null, 'client');
			
			$mform->addElement('text', 'cognome_contatto','Cognome contatto', 'maxlength="254" size="50"');
			$mform->addElement('text', 'nome_contatto','Nome contatto', 'maxlength="254" size="50"');
			$mform->addElement('text', 'url','URL', 'maxlength="254" size="50"');
			$mform->addElement('text', 'partita_iva','Partita iva', 'maxlength="254" size="50"');
			$mform->addRule('partita_iva', get_string('error_value', 'local_f2_traduzioni'), 'regex','/^\d{11}$/', 'client');
			
			$mform->addElement('text', 'codice_fiscale','Codice fiscale', 'maxlength="254" size="50"');
			$mform->addRule('codice_fiscale', null, 'required',null, 'client');
			
			$mform->addElement('text', 'codice_creditore','Codice creditore', 'maxlength="254" size="50"');
			
			$mform->addElement('checkbox', 'stato','Stato attivo');
			$mform->setDefault('stato', 1);
			
			$mform->addElement('text', 'indirizzo','Indirizzo', 'maxlength="254" size="50"');
			$mform->addElement('text', 'cap','Cap', 'maxlength="254" size="50"');
			$mform->addRule('cap', get_string('error_value', 'local_f2_traduzioni'), 'regex','/^\d*$/', 'client');
			$mform->addElement('text', 'citta','Citta\'', 'maxlength="254" size="50"');
			$mform->addElement('text', 'provincia','Provincia', 'maxlength="254" size="50"');
			$mform->addElement('text', 'paese','Paese', 'maxlength="254" size="50"');
			
			$mform->addElement('text', 'fax','Fax', 'maxlength="254" size="50" onkeypress="return isPhoneNumber(event)"');
//			$mform->addRule('fax', get_string('error_value', 'local_f2_traduzioni'), 'regex','/^[+]?\d*$/', 'client');
			
			$mform->addElement('text', 'telefono','Telefono', 'maxlength="254" size="50" onkeypress="return isPhoneNumber(event)"');
//			$mform->addRule('telefono', get_string('error_value', 'local_f2_traduzioni'), 'regex','/^[+]?\d*$/', 'client');
			
			$mform->addElement('text', 'email','Email', 'maxlength="254" size="50"');
			$mform->addRule('email', get_string('error_value', 'local_f2_traduzioni'), 'email','','client');
			
			//$mform->addElement('checkbox', 'preferito','Preferito');
			$mform->addElement('html', '<div style="margin-left:200px" class="qheader">Tipo Formazione</div>');
			
		/*	$mform->addElement('checkbox', 'tipo_formazione_programmata','Programmata');
			$mform->addElement('checkbox', 'tipo_formazione_obiettivo','Obiettivo');
			$mform->addElement('checkbox', 'tipo_formazione_individuale','Individuale');*/
			
			$mform->addElement('advcheckbox', 'tipo_formazione[0]', 'Programmata', '', NULL, '100');			
			$mform->addElement('advcheckbox', 'tipo_formazione[1]', 'Obiettivo', '', NULL, '010');		
			$mform->addElement('advcheckbox', 'tipo_formazione[2]', 'Individuale', '', NULL, '001');

			$mform->addElement('textarea', 'note','Note', 'rows="5" cols="60"');
			
			
			$gestionecorsi_url_back = new moodle_url("{$CFG->wwwroot}/blocks/f2_gestione_risorse/fornitori/anagrafica_fornitori.php");
		//	echo '<input type="button" value="'.get_string("indietro", "block_f2_formazione_individuale").'" onclick="parent.location=\''.$gestionecorsi_url_back.'\'">';

			
			
			$buttonarray=array();
			$buttonarray[] = &$mform->createElement('reset', 'resetbutton', 'Pulisci');
			$buttonarray[] = &$mform->createElement('submit', 'Salva', 'Salva');
			$buttonarray[] = &$mform->createElement('button', 'intro', 'Indietro','onclick="parent.location=\''.$gestionecorsi_url_back.'\'"');
			$mform->addGroup($buttonarray, 'buttonar', '&nbsp;', array(' '), false);
			}	
	}

//FINE FORM




?>