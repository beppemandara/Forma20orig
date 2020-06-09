<?php

//$Id$

global $CFG, $OUTPUT, $PAGE, $SITE, $DB;

require_once '../../../config.php';
require_once '../lib.php';
require_once($CFG->dirroot.'/f2_lib/management.php');
require_once($CFG->dirroot.'/local/f2_support/lib.php');

require_login();
$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('block/f2_apprendimento:leggistorico', $context);

$edizioneid_storico   = required_param('edizioneid_sto', PARAM_TEXT);
$code_course   = required_param('course', PARAM_TEXT);
$data_inizio   = required_param('d_i', PARAM_TEXT);
$sort         = optional_param('sort', 'name', PARAM_ALPHANUM);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 10, PARAM_INT);        // how many per page
$count_dati_iscritti_edizione = optional_param('n', 0, PARAM_INT);
    
$returnurl = new moodle_url('/blocks/f2_apprendimento/storico/modifica_storico.php');
$baseurl = new moodle_url('/blocks/f2_apprendimento/storico/dettaglio_edizione.php', array('edizioneid_sto' => $edizioneid_storico));
$blockname = get_string('pluginname', 'block_f2_apprendimento');

$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/f2_apprendimento/storico/dettaglio_edizione.php');
$PAGE->set_title(get_string('dettaglio_edizione', 'block_f2_apprendimento'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('modificastorico', 'block_f2_apprendimento'), $returnurl);
$PAGE->navbar->add(get_string('dettaglio_edizione', 'block_f2_apprendimento'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
/////////////////////////////////////////////////////////////

$dati	= new stdClass;
$dati->id_numder= $code_course;
$dati->edizioneid_storico	= $edizioneid_storico;

$id_edizione = get_id_editione_by_dati_storico($dati);
if(!$id_edizione)
	$id_edizione = -1;
else 
	$id_edizione = $id_edizione->id_edizione;

$dati_ed	= new stdClass;
$dati_ed->cod_corso = $code_course;
$dati_ed->edizioneid_storico	= $edizioneid_storico;
$dati_ed->data_inizio = $data_inizio;
$dati_ed->id_edizione = $id_edizione;




/*
/////
$dati_edizione = new stdClass;
$dati_edizione->id_edizione = $edizioneid;
$dati_edizione->codcorso = $code_course;
$dati_edizione->data_inizio = $data_inizio;

$iscritti = get_iscritti_storico_editione($dati_edizione, $sort, $dir, $page*$perpage, $perpage);

$iscritti_count = $edizione->iscritti;
////////////
*/


$dati_result = get_dati_iscritti_edizione($dati_ed, $sort, $dir, $page*$perpage, $perpage);
$dati_iscritti_edizione = $dati_result->dati;
//$count_dati_iscritti_edizione = $dati_result->count;




$dati_table_edizione ="";
if($dati_iscritti_edizione){
	foreach($dati_iscritti_edizione as $dati_edizione){
		$dati_table_edizione=$dati_edizione;
		break;
	}
}

//$edizione = get_dettaglio_edizione($edizioneid);

if ($dati_table_edizione->durata == intval($dati_table_edizione->durata))
    $durata = intval($dati_table_edizione->durata);
else
    $durata = number_format( $dati_table_edizione->durata, 1, ',', '.');

// INIZIO TABELLA DATI EDIZIONE
$table = new html_table();
$table->align = array('right', 'left');
$table->data = array(
                    array(get_string('codice', 'block_f2_apprendimento'),$dati_table_edizione->codcorso),
                    array(get_string('titolo', 'block_f2_apprendimento'),$dati_table_edizione->titolo),
                    array(get_string('sede', 'block_f2_apprendimento'),$dati_table_edizione->localita),
                    array(get_string('datainizio', 'block_f2_apprendimento'),date('Y-m-d', $dati_table_edizione->data_inizio)),
                    array(get_string('tipocorso', 'block_f2_apprendimento'),$dati_table_edizione->tipo_corso == 'P' ? get_string('corso_programmato','block_f2_apprendimento') : get_string('corso_obiettivo','block_f2_apprendimento')),
                    array(get_string('num_iscritti', 'block_f2_apprendimento'),$count_dati_iscritti_edizione),
                    array(get_string('durata', 'block_f2_apprendimento'),$durata.' giorno/i'),
                    array(get_string('credito', 'block_f2_apprendimento'),intval($dati_table_edizione->cf))
            );

echo $OUTPUT->header();
                          
echo $OUTPUT->heading(get_string('dettaglio_edizione', 'block_f2_apprendimento'));

echo $OUTPUT->box_start();
echo html_writer::table($table);
echo $OUTPUT->box_end();
// FINE TABELLA DATI EDIZIONE
//print_r($dati_table_edizione);exit;
$columns = array('firstname', 'lastname', 'idnumber');

foreach ($columns as $column) {
    $string[$column] = get_user_field_name($column);
    if ($sort != $column) {
        $columnicon = "";
        $columndir = "ASC";
    } else {
        $columndir = $dir == "ASC" ? "DESC":"ASC";
        $columnicon = $dir == "ASC" ? "down":"up";
        $columnicon = " <img src=\"" . $OUTPUT->pix_url('t/' . $columnicon) . "\" alt=\"\" />";

    }
    $$column = "<a href=\"dettaglio_edizione.php?sort=".$column."&amp;dir=".$columndir."&amp;edizioneid_sto=".$edizioneid_storico."&amp;n=".$count_dati_iscritti_edizione."&amp;d_i=".$data_inizio."&amp;course=".$code_course."\">".($column == 'idnumber' ? get_string('matricola', 'block_f2_apprendimento') : $string[$column])."</a>$columnicon";
}

if (!$dati_iscritti_edizione) {
    echo "<p class=\"msg_user\">".get_string('noiscrittifound','block_f2_apprendimento')."</p>";
    $table = NULL;
} else {
    echo "<p class=\"msg_user\">$count_dati_iscritti_edizione ".get_string('iscritti','block_f2_apprendimento')."</p>";
    $PAGE->requires->js(new moodle_url('storico.js'));
    
    $baseurl = new moodle_url('/blocks/f2_apprendimento/storico/dettaglio_edizione.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage, 'edizioneid_sto' => $edizioneid_storico,'n'=>$count_dati_iscritti_edizione,'d_i'=>$data_inizio,'course'=>$code_course));
    echo $OUTPUT->paging_bar($count_dati_iscritti_edizione, $page, $perpage, $baseurl);
   
        $fullnamedisplay = "$lastname / $firstname";
  
        
    $table = new html_table();
    $table->id = 'iscritti';
    $table->attributes['class'] = 'iscritti';
    $table->head = array ();
    $table->align = array();
    $table->head[] = $idnumber;
    $table->align[] = 'left';
    $table->head[] = $fullnamedisplay;
    $table->align[] = 'left';
    $table->head[] = get_string('direzione', 'block_f2_apprendimento');
    $table->align[] = 'left';
    $table->head[] = get_string('settore', 'block_f2_apprendimento');
    $table->align[] = 'left';
    $table->head[] = get_string('modifica', 'block_f2_apprendimento');
    $table->align[] = 'left';
    $table->head[] = get_string('applica','block_f2_apprendimento');
    $table->align[] = 'left';
    
    $table->width = "95%";
    $cont = 0;
    $rowclasses = array('even', 'odd');
    
    foreach ($dati_iscritti_edizione as $iscritto) {
    //	print_r($iscritto);exit;
        $cont++;
      //  $info_storico = get_info_from_storico($iscritto->matricola, $edizione->codicecorso, $edizione->datainizio);
        $row = new html_table_row();
        $cella1 = new html_table_cell($iscritto->matricola);
        $cella2 = new html_table_cell($iscritto->cognome." ".$iscritto->nome);
        $direzione = $iscritto->direzione;
        $cella3 = new html_table_cell($direzione);
        $settore = $iscritto->settore;
        $cella4 = new html_table_cell($settore);
        if (has_capability('block/f2_apprendimento:modificastorico', $context))
            //$cella5 = new html_table_cell("<a href=\"javascript: edit_table('$cont');\"><center><img src=\"{$CFG->wwwroot}/pix/i/edit.gif\" class=\"icon\" title=\"Modifica\" /></center></a>");
            $cella5 = new html_table_cell("<a href=\"javascript: edit_table('$cont');\"><center><img src=\"{$CFG->wwwroot}/pix/i/edit.png\" class=\"icon\" title=\"Modifica\" /></center></a>");
        else 
            $cella5 = new html_table_cell("");
        
        //modificare i valori solo in storico perche non esiste nessuna riga sulla tabella mdl_facetoface_signups
        if(!$iscritto->fs_id){
        	$modifica_solo_storico ="<input type='hidden' name='modifica_solo_storico' value='1'>";
        	$if_presenza = 0;
        }else{
        	$modifica_solo_storico ="<input type='hidden' name='modifica_solo_storico' value='0'>";
        	$if_presenza = 1;
        }
        
        $cella6 = new html_table_cell("<form action='applica_modifiche.php' method='post'>
                    <input type='hidden' name='cont' value='$cont'>            
                    <input type='hidden' name='edizioneid' value='$id_edizione'>            
                    <input type='hidden' name='id_stato_iscrizione' value='$iscritto->id_stato'>            
                    <input type='hidden' name='id_storico' value='$iscritto->id'>            
                    <input type='hidden' name='dir' value='$dir'>            
                    <input type='hidden' name='sort' value='$sort'>            
                    <input type='hidden' name='page' value='$page'>  
        		       
					<input type='hidden' name='edizioneid_sto' value='$edizioneid_storico'>            
                    <input type='hidden' name='code_course' value='$code_course'>   
        		<input type='hidden' name='n' value='$count_dati_iscritti_edizione'>   
        		<input type='hidden' name='d_i' value='$iscritto->data_inizio'>  
        		".$modifica_solo_storico."
                    <center><input type='submit' value='Applica' style='visibility:hidden' onClick='return confirmSubmit($cont,$if_presenza)' id='applica_$cont' /></center>");

        $row->cells = array($cella1, $cella2, $cella3, $cella4, $cella5, $cella6);
        
        $table->data[] = $row;
        
        
      
            $presenza = $iscritto->presenza_storico;
            $va = $DB->get_field('f2_va', 'descrizione', array('id' => $iscritto->va_storico.''), MUST_EXIST);
            $cfv = $iscritto->cfv_storico;
            $descrpart = $iscritto->descrpart;
       
        $row2 = new html_table_row();
        $cell1 = new html_table_cell("Iscrizione: </br>Storico: ");
        $cell1->colspan = 1;
     ///////////// PRESENZA
        if ($iscritto->presenza_no_storico != $presenza)
            $err_box_presenza = 'class=\'err_box\'';
        else
            $err_box_presenza = '';
        
        if(!$iscritto->fs_id){
        	$input_presenza_no_storico="Presenza: --";
        	$input_presenza_read_no_storico="Presenza: --";
        }
        else{
        	$input_presenza_no_storico = "Presenza: <input type='text' name='presenza_$cont' id='presenza_$cont' value='".number_format($iscritto->presenza_no_storico, 1)."' style='width:40px; text-align:center' />";
        	$input_presenza_read_no_storico = "Presenza: ".number_format($iscritto->presenza_no_storico, 1)."";
        }
        
        $cell2 = new html_table_cell("<div id='presenza_read_$cont' $err_box_presenza>".$input_presenza_read_no_storico."</br>Presenza: ".number_format($presenza, 1)."</div>".
                                     "<div id='presenza_write_$cont' style='display:none' $err_box_presenza>
                                          $input_presenza_no_storico </br>
                                         Presenza: <input type='text' name='presenza_storico_$cont' id='presenza_storico_$cont' value='".number_format($presenza, 1)."' style='width:40px; text-align:center' /></div>");
        $cell2->colspan = 1;
     ///////////// VERIFICA APPRENDIMENTO
        $iscritto->va_no_storico = $DB->get_field('f2_va', 'descrizione', array('id' => $iscritto->va_no_storico), true);
        $va_values = get_va_values();
        $combo_va = "<select name='va_iscrizione_$cont' id='va_iscrizione_$cont'>";
        $combo_va_storico = "<select name='va_storico_$cont' id='va_storico_$cont'>";
        foreach($va_values as $value) {
            $selected= "";
            $selected_storico = "";
            if ($value->descrizione == $iscritto->va_no_storico) 
                $selected = "selected";
            if ($value->descrizione == $va) 
                $selected_storico = "selected";
            $combo_va .= "<option value=\"$value->id\" $selected>$value->descrizione</option>";
            $combo_va_storico .= "<option value=\"$value->id\" $selected_storico>$value->descrizione</option>";
        }
        $combo_va .= "</select>";
        $combo_va_storico .= "</select>";
        if ($iscritto->va_no_storico != $va)
            $err_box_va = 'class=\'err_box\'';
        else
            $err_box_va = '';
        
        if(!$iscritto->fs_id){
        	$combo_va_no_storico_read = "VA: --";
        	$combo_va = "VA: --";
        }else{
        	$combo_va_no_storico_read = "VA: ".$iscritto->va_no_storico."";
        	$combo_va = "VA: ".$combo_va;
        }
        
        
        $cell3 = new html_table_cell("<div id='va_read_$cont' $err_box_va><label title='Verifica Apprendimento'>".$combo_va_no_storico_read."</label></br><label title='Verifica Apprendimento'>VA: $va</label></div>".
                                     "<div id='va_write_$cont' style='display:none' $err_box_va>
                                         <label title='Verifica Apprendimento'>".$combo_va."</label></br>
                                         <label title='Verifica Apprendimento'>VA: $combo_va_storico</label></div>");
        $cell3->colspan = 1;
     ///////////// CFV
        $cell4 = new html_table_cell("<div id='cfv_read_$cont' ></br><label title='Credito Formativo Validato'>CFV: $cfv</label></div>".
                                     "<div id='cfv_write_$cont' style='display:none'>
                                         </br><label title='Credito Formativo Validato'>CFV: <input type='text' name='cfv_$cont' id='cfv_$cont' value='$cfv' style='width:40px; text-align:center' /></label></div>");
        $cell4->colspan = 1;
    ///////////// PARTECIPAZIONE
        $partecipazioni = get_partecipazioni_values();
        $combo_partecipazioni = "<select name='partecipazione_$cont' id='partecipazione_$cont' style='width:100px'>";
        foreach($partecipazioni as $part) {
            $selected = "";
            if ($part->descrpart == $descrpart) 
                $selected = "selected";
            $combo_partecipazioni .= "<option value=\"$part->id\" $selected>$part->descrpart</option>";
        }
        $combo_partecipazioni .= "</select>";
        $cell5 = new html_table_cell("<div id='partecipazione_read_".$cont."'></br>Partecipazione: $descrpart</div>".
                                     "<div id='partecipazione_write_".$cont."' style='display:none'>
                                         </br>Partecipazione: ".$combo_partecipazioni."</div></form>");
        $cell5->colspan = 2;
        
        $row2->cells = array($cell1, $cell2, $cell3, $cell4, $cell5);
        
        $row->attributes['class'] = $rowclasses[$cont % 2];
        $row2->attributes['class'] = $rowclasses[$cont % 2].'_infostorico';
        
        $table->data[] = $row2;
         
    }
   
}

if (!empty($table)) {
    echo html_writer::table($table);
    echo $OUTPUT->paging_bar($count_dati_iscritti_edizione, $page, $perpage, $baseurl);
}

echo $OUTPUT->footer();
