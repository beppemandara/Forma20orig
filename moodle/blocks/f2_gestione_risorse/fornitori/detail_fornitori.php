<?php
// $Id$
global $CFG,$DB,$OUTPUT;

require_once '../../../config.php';
require_once($CFG->dirroot.'/blocks/f2_gestione_risorse/lib.php');
$id_for = optional_param('id', '0', PARAM_INT);

require_login();

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
$PAGE->navbar->add(get_string('anagrafica_fornitori', 'block_f2_gestione_risorse'), new moodle_url($url));
$PAGE->set_title(get_string('anagrafica_fornitori', 'block_f2_gestione_risorse'));
$PAGE->set_heading($SITE->shortname.': '.$blockname);
$PAGE->set_pagelayout('standard');
$PAGE->settingsnav;

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('anagrafica_fornitori', 'block_f2_gestione_risorse'));
echo $OUTPUT->box_start();
echo '<div class="contenitoreglobale">';

if($id_for){
$fornitore=get_fornitore ($id_for);
	if($fornitore){

		$table = new html_table();
		$table->size = array('150px','500px');
		$table->headspan  = array(2,1);
		$table->head = array(get_string('anagrafica_fornitori', 'block_f2_gestione_risorse'));
		$table->data = array();
			$content[0]= array('Nome',$fornitore->denominazione);
			$content[1]= array('Cognome/Nome',$fornitore->cognome.' '.$fornitore->nome);
			$content[2]= array('URL',$fornitore->url);
// A. Albertin - CSI Piemionte - febbraio 2014
//    modificato etichetta per partiita Iva
//    aggiunto riga con Codice Fiscale
//    incrementato indice per content "successivi"
			$content[3]= array('Partita Iva',$fornitore->partita_iva);
			$content[4]= array('Codice Fiscale',$fornitore->codice_fiscale);
			$content[5]= array('Stato',get_stato_fornitore ($fornitore->stato));
			$content[6]= array('Indirizzo',$fornitore->indirizzo);
			$content[7]= array('Cap',$fornitore->cap);
			$content[8]= array('Citta\'',$fornitore->citta);
			$content[9]= array('Provincia',$fornitore->provincia);
			$content[10]= array('Paese',$fornitore->paese);
			$content[11]= array('Fax',$fornitore->fax);
			$content[12]= array('Telefono',$fornitore->telefono);
			$content[13]= array('Email',$fornitore->email);
			$content[14]= array('Preferito',get_preferito_fornitore ($fornitore->preferiti));
			$content[15]= array('Tipo Formazione',get_tipo_formazione_fornitore($fornitore->tipo_formazione));
			$content[16]= array('Note',$fornitore->nota);

		$table->data = $content;
		
		echo html_writer::table($table);
		
		echo '<a href="anagrafica_fornitori.php"><button type="button">Indietro</button></a>';
		echo '</div>';	
	}
}
else{
	echo 'Errore. Non ci sono fornitori per questo id';
}
	
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
?>