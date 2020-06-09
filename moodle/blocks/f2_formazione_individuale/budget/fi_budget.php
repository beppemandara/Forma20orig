<?php
// $Id$
global $CFG,$PAGE,$SITE,$OUTPUT;
ob_start();
require_once '../../../config.php';
require_once('fi_budget_form.php');
require_once('fi_budget.class.php');
require_once('fi_budget_approved.class.php');
require_once($CFG->dirroot.'/blocks/f2_gestione_risorse/lib.php');
require_once($CFG->dirroot.'/f2_lib/report.php');
require_once($CFG->dirroot.'/f2_lib/management.php');

//pagination: intestazioni necessarie per l'impaginazione e ordinamento
$page     = optional_param('page', 0, PARAM_INT);
$perpage  = optional_param('perpage', 10, PARAM_INT);
$column   = optional_param('column', 'direzione', PARAM_TEXT);
$sort     = optional_param('sort', 'ASC', PARAM_TEXT);

require_login();

$context = get_context_instance(CONTEXT_SYSTEM);

$capability_giunta       = has_capability('block/f2_formazione_individuale:individualigiunta', $context);
$capability_linguagiunta = has_capability('block/f2_formazione_individuale:individualilinguagiunta', $context);
$capability_consiglio    = has_capability('block/f2_formazione_individuale:individualiconsiglio', $context);

$param_CIG = get_parametro('p_f2_corsi_individuali_giunta');
$param_CIL = get_parametro('p_f2_corsi_individuali_lingua_giunta');
$param_CIC = get_parametro('p_f2_corsi_individuali_consiglio');

$param_param_corsi_lingua = get_parametro('p_f2_tipo_pianificazione_1'); // Corsi di lingua con insegnante
$param_param_corsi_ind    = get_parametro('p_f2_tipo_pianificazione_2'); // Corsi Individuali

//Check capabilities and role
if(!($capability_giunta || $capability_linguagiunta || $capability_consiglio)
    && !isSupervisore($USER->id)) {
    print_error('nopermissions', 'error', '', 'formazione_individuale');
}

//training option (form. ind., form. ind. lingua)
$training_options = array();
//default training option
$training_default = NULL;
//Id dei domini di consiglio e/o giunta
$array_id_radici = array();
$id_giunta    = get_parametro('p_f2_dominio_radice_regione_giunta');
$id_consiglio = get_parametro('p_f2_dominio_radice_regione_consiglio');
if ($capability_giunta) {
    $array_id_radici['giunta'] = $id_giunta->val_int;
    $training_options[$param_param_corsi_ind->val_char] = $param_param_corsi_ind->descrizione;
    $training_default = $param_param_corsi_ind->val_char;
}
if ($capability_consiglio) {
    $array_id_radici['consiglio'] = $id_consiglio->val_int;
    if (!in_array($param_param_corsi_ind->descrizione, $training_options))
        $training_options[$param_param_corsi_ind->val_char] = $param_param_corsi_ind->descrizione;
    $training_default = $param_param_corsi_ind->val_char;
}
if ($capability_linguagiunta) {
    if (!in_array($id_giunta->val_int, $array_id_radici)) {
        $array_id_radici['giunta'] = $id_giunta->val_int;
    }
    $training_options[$param_param_corsi_lingua->val_char] = $param_param_corsi_lingua->descrizione;
    $training_default = empty($training_default) ? $param_param_corsi_lingua->val_char : $training_default;
}

$anno_in_corso = get_anno_formativo_corrente();
$budget = new fi_budget_approved($anno_in_corso, $training_default, $array_id_radici);
$budget_exists = $budget->budget_exists();
$exportable = $budget->is_exportable();
	
//INIZIO FORM
$mform = new fi_budget_form(null, compact('anno_in_corso',
                                        'training_default',
                                        'training_options',
                                        'exportable'));

//Form processing and displaying is done here
if ($mform->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form
} else if ($fromform = $mform->get_data()) {

    //In this case you process validated data. $mform->get_data() returns data posted in form.

    $pagination = array('perpage'=>$perpage, 'page'=>$page, 'column'=>$column, 'sort'=>$sort);
    if (!empty($fromform->bexport)) {
        $pagination['perpage'] = 0;
        $pagination['page'] = 0;
    }
    foreach ($pagination as $key=>$value) {
        $fromform->$key = $value;
    }

    $budget->type = $fromform->training;
    if ($rs = $budget->get_fi_budget($fromform)) {
      $rows = $rs->data;
      $total_rows = $rs->count;
    }
    $budget->get_fi_budget_tot();
    
    if ($total_rows === 0) {
      $msg = get_string('nobudgetfound', 'block_f2_formazione_individuale');
    }

    if (!empty($fromform->bexport) && $budget->is_exportable() && $total_rows > 0) {
        $filename="FI-Budget {$budget->getTypeDescr()}.".date('Ymd').'.xls';	
        // Redirect output to a client's web browser (Excel2007)
        setcookie('fileDownload', 'true', 0, '/');
        header('Cache-Control: max-age=60, must-revalidate'); 
        header('Content-Type: application/vnd.ms-excel');
        //header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); EXCEL 2007
        header('Content-Disposition: attachment;filename="'.$filename.'');

        $objWriter = $budget->export_xls($rows);

        ob_end_clean();
        $objWriter->save('php://output');
        die();
    }
    
} else {
    // this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
    // or on the first display of the form.

    $toform = new stdClass();
    $toform->training = $training_default;
    $pagination = compact('perpage', 'page', 'column', 'sort');
    foreach ($pagination as $key=>$value) {
          $toform->$key = $value;
    }
    
    if ($rs = $budget->get_fi_budget($toform)) {
      $rows = $rs->data;
      $total_rows = $rs->count;
    }
    $budget->get_fi_budget_tot();
    
    if (!$budget_exists) {
      $msg = get_string('nobudget', 'block_f2_formazione_individuale');
    }
    //Set default data (if any)
    $mform->set_data($toform);
    //displays the form
}
ob_flush();

$blockname = get_string('pluginname', 'block_f2_formazione_individuale');

$PAGE->set_context($context);
$PAGE->set_url('/blocks/f2_formazione_individuale/budget/fi_budget.php');
$PAGE->navbar->add(get_string('fi_budget', 'block_f2_formazione_individuale'));
$PAGE->set_title(get_string('fi_budget', 'block_f2_formazione_individuale'));
$PAGE->set_heading($SITE->shortname.': '.$blockname);
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('fi_budget', 'block_f2_formazione_individuale'));

echo $OUTPUT->box_start();
echo '<div class="contenitoreglobale">';

$form_id = $mform->getFormId();						// ID del form dove fare il submit
$post_extra = array('column'=>$column,'sort'=>$sort);		// dati extra da aggiungere al post del form


// INIZIO TABELLA
$head_table      = array('direzione','budget');
$head_table_sort = array('direzione');
$align           = array ('left','center');
$size            = array ('50','10');

$table = new html_table();
$table->align = $align;
//$table->size[1] = '120px';
$table->head = build_head_table($head_table, $head_table_sort, $post_extra, $total_rows, $page, $perpage, $form_id);
$totalrow = $budget->total_budget;

foreach ($rows as $row) {
    $table->data[] = array(
        $row->shortname.' - '.$row->fullname,
        format_float(round($row->money_bdgt, 3), 2),
    );
}

//INIZIO RIGA TOTALI
$table->data[]= array(
                '<b>'.get_string('total').':</b>',
                format_float(round($totalrow, 3), 2),
                );
//FINE RIGA TOTALI
	
$mform->display();

echo "<p>".get_string('count_tot_rows', 'local_f2_traduzioni',$total_rows)."</p>";

$paging_bar = new paging_bar_f2($total_rows, $page, $perpage, $form_id, $post_extra);
echo $paging_bar->print_paging_bar_f2();

if (!empty($msg)) {
    echo "<p>$msg</p>";
} else {
    echo html_writer::table($table);
}

echo $paging_bar->print_paging_bar_f2();

$partialbudget_url = new moodle_url("$CFG->wwwroot/blocks/f2_formazione_individuale/budget/fi_inserisci_budget.php");
echo $OUTPUT->single_button($partialbudget_url, get_string('fillin_budget','block_f2_formazione_individuale'), 'post');
echo '</div>';
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
