<?php

//$Id$
global $OUTPUT, $PAGE, $SITE;

require_once '../../../config.php';
require_once('filters/lib.php');

require_login();
$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('block/f2_apprendimento:leggistorico', $context);

$sort         = optional_param('sort', 'data_inizio', PARAM_TEXT);
$dir          = optional_param('dir', 'DESC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 15, PARAM_INT);        // how many per page
$search		  = optional_param('search', '', PARAM_TEXT);

$baseurl = new moodle_url('/blocks/f2_apprendimento/storico/modifica_storico.php');
$blockname = get_string('pluginname', 'block_f2_apprendimento');

$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/f2_apprendimento/storico/modifica_storico.php');
$PAGE->set_title(get_string('modificastorico', 'block_f2_apprendimento'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('modificastorico', 'block_f2_apprendimento'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modificastorico', 'block_f2_apprendimento'));
// create the user filter form
//$efiltering = new my_editions_filtering();
//list($extrasql, $params) = $efiltering->get_sql_filter();


$extrasql = " codcorso like '%".$search."%' ";
//$editions = $efiltering->get_my_editions_listing($sort, $dir, $page*$perpage, $perpage, $extrasql, $params);
$edizioni = get_storico_corsi_listing($sort, $dir, $page*$perpage, $perpage, $extrasql);
$editions = $edizioni->dati;



$editionscount = get_storico_corsi_count();

$editionsearchcount = $edizioni->count;
//$editionsearchcount = get_storico_corsi_count(true, $extrasql);


$baseurl = new moodle_url('/blocks/f2_apprendimento/storico/modifica_storico.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage,'search'=>$search));


flush();


if ($editionscount == 0) {
    echo $OUTPUT->heading(get_string('noeditionsfound','block_f2_apprendimento'));
    $table = NULL;
} else {
    $table = new html_table();
    $table->head = array ();
    $table->align = array();
    $table->head[] = get_string('codice', 'block_f2_apprendimento');
    $table->align[] = 'left';
    $table->head[] = get_string('titolo', 'block_f2_apprendimento');
    $table->align[] = 'left';
    $table->head[] = get_string('sede', 'block_f2_apprendimento');
    $table->align[] = 'left';
    $table->head[] = get_string('sessione', 'block_f2_apprendimento');
    $table->align[] = 'left';
    $table->head[] = get_string('edizione', 'block_f2_apprendimento');
    $table->align[] = 'left';
    $table->head[] = get_string('datainizio','block_f2_apprendimento');
    $table->align[] = 'left';
    $table->head[] = get_string('tipocorso','block_f2_apprendimento');
    $table->align[] = 'left';
    $table->head[] = get_string('num_iscritti','block_f2_apprendimento');
    $table->align[] = 'left';

    
    $table->width = "95%";
    foreach ($editions as $edition) {
    	$tipo_corso= "";
    	if($edition->tipo_corso == "O")
    		$tipo_corso = get_string('corso_obiettivo','block_f2_apprendimento');
    	else if($edition->tipo_corso == "P")
    		$tipo_corso = get_string('corso_programmato','block_f2_apprendimento');
    	
        $row = array ();
        $row[] = "<a href=\"dettaglio_edizione.php?edizioneid_sto=$edition->edizione&course=".$edition->codcorso."&d_i=".$edition->data_inizio."&n=".$edition->iscritti."\">$edition->codcorso</a>";
        $row[] = "<a href=\"dettaglio_edizione.php?edizioneid_sto=$edition->edizione&course=".$edition->codcorso."&d_i=".$edition->data_inizio."&n=".$edition->iscritti."\">$edition->titolo</a>";
        $row[] = $edition->localita;
        $row[] = $edition->sessione;
        $row[] = $edition->edizione;
        $row[] = date('Y-m-d', $edition->data_inizio);
        $row[] = $tipo_corso;
        $row[] = $edition->iscritti;
        
        $table->data[] = $row;
    }
}

// add filters
//$efiltering->display_add();
//$efiltering->display_active();

echo html_writer::start_tag('form', array('action' => $baseurl, 'method' => 'post'));
// Submit ricerca
echo '<table><tr>';
echo '<td >Codice: <input maxlength="254" size="50" name="search" type="text" id="search" value="'.$search.'" /></td>';
echo '<td><input name="Cerca" value="Cerca" type="submit" id="id_Cerca" /></td>';
echo '</tr></table>';
echo html_writer::end_tag('form');

if ($extrasql !== '') {
	echo "<h3>$editionsearchcount / $editionscount ".get_string('numero_edizioni_corso','block_f2_apprendimento')."</h3>";
	$editionscount = $editionsearchcount;
} else {
	echo "<h3>".$editionscount.' '.get_string('numero_edizioni_corso','block_f2_apprendimento')."</h3>";
}

echo $OUTPUT->paging_bar($editionscount, $page, $perpage, $baseurl);

if (!empty($table)) {
    echo html_writer::table($table);
    echo $OUTPUT->paging_bar($editionscount, $page, $perpage, $baseurl);
}

echo $OUTPUT->footer();