<?php
// $Id$
global $CFG,$PAGE,$SITE,$OUTPUT;

require_once '../../../config.php';
require_once('fi_budget_form.php');
require_once('fi_budget.class.php');
require_once('fi_budget_partial.class.php');
require_once($CFG->dirroot.'/blocks/f2_gestione_risorse/lib.php');
require_once($CFG->dirroot.'/f2_lib/report.php');
require_once($CFG->dirroot.'/f2_lib/management.php');

//pagination: intestazioni necessarie per l'impaginazione e ordinamento
$page     = optional_param('page', 0, PARAM_INT);
$perpage  = optional_param('perpage', 10, PARAM_INT);
$column   = optional_param('column', 'direzione', PARAM_TEXT);
$sort     = optional_param('sort', 'ASC', PARAM_TEXT);

$crea   = optional_param('bsetup', '', PARAM_TEXT);
$applica  = optional_param('bapplica', '', PARAM_TEXT);
$approva  = optional_param('bapprova', '', PARAM_TEXT);

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
$budget = new fi_budget_partial($anno_in_corso, $training_default, $array_id_radici);
$budget_exists = $budget->budget_exists() || $crea; //budget esiste o tasto Crea premuto
$setupneworgsonly = $budget_exists;

//Crea budget per l'anno formativo
if (!empty($crea)) {
    $budget->setup_budget_for_year();
//Modifica budget singolo
} else if (!empty($applica)) {
    $budgetid = optional_param('budgetid', 0, PARAM_INT);
    $rawvalue = optional_param('budgetval', 0, PARAM_RAW);
    $realvalue = unformat_float($rawvalue);
    $budget->update($budgetid, $realvalue);
//Approvazione budget
} else if (!empty($approva) && $budget_exists) {
    $budget->approve();
    redirect(new moodle_url('/blocks/f2_formazione_individuale/budget/fi_budget.php'));
}

//INIZIO FORM
$mform = new fi_budget_form(null, compact('anno_in_corso',
                                        'training_default',
                                        'training_options',
                                        'setupneworgsonly'));
//Form processing and displaying is done here
if ($mform->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form
} else if ($fromform = $mform->get_data()) {
    //In this case you process validated data. $mform->get_data() returns data posted in form.

    $pagination = array('perpage' => $perpage, 'page'=>$page, 'column'=>$column, 'sort'=>$sort);
    foreach ($pagination as $key=>$value) {
        $fromform->$key = $value;
    }
    $fromform->right = 'RIGHT';

    if (!empty($fromform->bsetupneworgs)) {
        $budget->setup_budget_new_orgs_only();
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
      $msg = get_string('nopartialbudget', 'block_f2_formazione_individuale');
    }
    //Set default data (if any)
    $mform->set_data($toform);
    //displays the form
}

$blockname = get_string('pluginname', 'block_f2_formazione_individuale');
$baseurl = new moodle_url('/blocks/f2_formazione_individuale/budget/fi_inserisci_budget.php');
$PAGE->set_context($context);
$PAGE->set_url($baseurl);
$PAGE->navbar->add(get_string('fi_budget', 'block_f2_formazione_individuale'));
$PAGE->navbar->add(get_string('fi_inserisci_budget', 'block_f2_formazione_individuale'));
$PAGE->set_title(get_string('fi_inserisci_budget', 'block_f2_formazione_individuale'));
$PAGE->set_heading($SITE->shortname.': '.$blockname);
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('fillin_budget', 'block_f2_formazione_individuale'));
$str = <<<'EFO'
<script type="text/javascript">
//<![CDATA[
function edit_table(id) {
var txt = document.getElementById('budget_'+id), btn = document.getElementById('applica_'+id);

if(txt.getAttribute("readonly") == "readonly"){
    txt.removeAttribute("readonly");
    txt.removeAttribute("style");
    txt.setAttribute("style","width:50px");	
    btn.removeAttribute("style");
    btn.setAttribute("visibility","visible");
} else {
    txt.setAttribute("readonly","readonly");
    txt.setAttribute("style","border:none; width:50px");	
    btn.setAttribute("style","visibility:hidden");
}
}
function confirmAction(msg) {
var agree=confirm(msg);
if (agree)
    return true;
else
    return false;
}
function submitChange(btn, msg) {
if (confirmAction(msg)) {
    var budgetid = btn.id.split("_")[1], frm = btn.form;
    frm.budgetid.value = budgetid;
    frm.budgetval.value = frm["budget_"+budgetid].value;
    return true;
}
return false;
}
//]]>
</script>
EFO;
echo $str;

echo $OUTPUT->box_start();
echo '<div class="contenitoreglobale">';

$form_id = $mform->getFormId();                                 // ID del form dove fare il submit
$post_extra=array('column'=>$column,'sort'=>$sort);		// dati extra da aggiungere al post del form


// INIZIO TABELLA
$head_table      = array('modifica','direzione','budget','applica');
$head_table_sort = array('direzione');
$align           = array ('center','left','left','center');
$size            = array ('10','50','10','10');

$table = new html_table();
$table->align = $align;
//$table->size[1] = '120px';
$table->head = build_head_table($head_table, $head_table_sort, $post_extra, $total_rows, $page, $perpage, $form_id);
$totalrow = $budget->total_budget;

foreach ($rows as $row) {
    $table->data[] = array(
        html_writer::empty_tag('img', array(
                                        'src'=>$CFG->wwwroot.'/pix/t/edit.png', 
                                        'alt'=>get_string('edit', 'block_f2_gestione_risorse'), 
                                        'class'=>'iconsmall', 
                                        'onclick'=>'edit_table('.$row->id.');',
                                        'style'=>'cursor:pointer',
                                        )),
        $row->shortname.' - '.$row->fullname,
        html_writer::empty_tag('input', array(
                                        'type'=>'text', 
                                        'id'=>'budget_'.$row->id, 
                                        'name'=>'budget_'.$row->id, 
                                        'value'=>format_float(round($row->money_bdgt, 3), 2), 
                                        'readonly'=>'readonly',
                                        'style'=>'width:50px; border:none'
                                        )),
        html_writer::empty_tag('input', array(
                                        'type'=>'submit', 
                                        'id'=>'applica_'.$row->id,
                                        'name'=>'bapplica', 
                                        'value'=>'Applica', 
                                        'style'=>'visibility:hidden', 
                                        'onClick'=>'return submitChange(this, "Confermi la modifica al budget?")',
                                        )),
    );
}

//INIZIO RIGA TOTALI
$table->data[]= array(
                '<b>'.get_string('total').':</b>',
                '',
                format_float(round($totalrow, 3), 2),
                '',
                );
//FINE RIGA TOTALI
	
$mform->display();

echo "<p>".get_string('count_tot_rows', 'local_f2_traduzioni',$total_rows)."</p>";
$paging_bar = new paging_bar_f2($total_rows, $page, $perpage, $form_id, $post_extra);
echo $paging_bar->print_paging_bar_f2();

echo html_writer::start_tag('form', array('action' => $baseurl, 'method' => 'post'));
echo html_writer::empty_tag('input', array('type'=>'hidden', 
                                            'id'=>'budgetid', 
                                            'name'=>'budgetid', 
                                            'value'=>'0'));
echo html_writer::empty_tag('input', array('type'=>'hidden', 
                                            'id'=>'budgetval', 
                                            'name'=>'budgetval', 
                                            'value'=>'0'));
if (!empty($msg) && !$budget_exists) {
    echo $OUTPUT->container_start('important', 'notice');
    echo $msg;
    echo html_writer::empty_tag('input', array(
                                        'type'=>'submit', 
                                        'id'=>'id_bsetup',
                                        'name'=>'bsetup', 
                                        'value'=>get_string('create'),
                                        'onClick'=>'return confirmAction("Confermi la creazione del budget per l\'anno formativo in corso?")',
                                        ));
    echo $OUTPUT->container_end();
} else {
    echo html_writer::table($table);
}
echo html_writer::end_tag('form');

echo $paging_bar->print_paging_bar_f2();

if ($budget_exists) {
    echo html_writer::start_tag('form', array('action' => $baseurl, 'method' => 'post'));
    echo html_writer::empty_tag('input', array(
                                        'type'=>'submit', 
                                        'id'=>'id_bapprova',
                                        'name'=>'bapprova', 
                                        'value'=>get_string('approve_budget','block_f2_formazione_individuale'),
                                        'class'=>'btnapprove',
                                        'onClick'=>'return confirmAction("Confermi l\'approvazione del budget '.$budget->getTypeDescr().'?")',
                                        ));
    echo html_writer::end_tag('form');
}
echo $OUTPUT->single_button(new moodle_url('/blocks/f2_formazione_individuale/budget/fi_budget.php'), 
                            get_string('back'), 
                            'post');
echo '</div>';
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
