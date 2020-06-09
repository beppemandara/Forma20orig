<?php

//$Id: mycourses_prog.php 1373 2015-02-19 09:53:49Z l.moretto $
global $CFG, $OUTPUT, $PAGE, $SITE;

/**
 * Ritorna la data di completamento del corso (unix timestamp) con tutti i
 * relativi criteri di completamento soddisfatti
 * 
 * @param stdObj $course
 *      L'oggetto corso da gestire
 * @return string
 *      Unix timestamp rappresentante lo stato di completamento del corso
 */
function getCourseTimeCompleted($course)
{
    global $USER;

    // Load course completion
    $params = array(
        'userid' => $USER->id,
        'course' => $course->id
    );
    $ccompletion = new completion_completion($params);
    
    if(isset($ccompletion->timecompleted))
    {
        return $ccompletion->timecompleted;
    }
    
    return false;
}

/**
 * Controlla lo stato di completamento di un corso e ritorna un flag testuale
 * in base allo stato
 * 
 * Logica estratta da forma\blocks\completionstatus\block_completionstatus.php
 * 
 * @param stdObj $course
 *      L'oggetto corso da gestire
 * @return string
 *      Testo rappresentante lo stato di completamento del corso
 */
function getCourseCompletationFlag($course)
{
    global $USER;

    $flag = "";
    
    // Get course completion data
    $info = new completion_info($course);

    // Is course complete?
    $coursecomplete = $info->is_course_complete($USER->id);
    
    // Load course completion
    $params = array(
        'userid' => $USER->id,
        'course' => $course->id
    );
    $ccompletion = new completion_completion($params);
    
    // Has this user completed any criteria?
    $criteriacomplete = $info->count_course_user_data($USER->id);

    ///Non controlliamo il pending update
    // Flag to set if current completion data is inconsistent with
    // what is stored in the database
    /*
    $pending_update = false;
    if ($pending_update)
    {
        $flag .= '<i>'.get_string('pending', 'completion').'</i>';
    }
    else
    */
    if ($coursecomplete)
    {
        $flag .= get_string('complete');
    }
    else if (!$criteriacomplete && !$ccompletion->timestarted)
    {
        $flag .= '<i>'.get_string('notyetstarted', 'completion').'</i>';
    }
    else
    {
        $flag .= '<i>'.get_string('inprogress','completion').'</i>';
    }
    
    return $flag;
}

require_once '../../config.php';
require_once($CFG->dirroot.'/f2_lib/core.php');
require_once($CFG->dirroot.'/f2_lib/management.php');
require_once($CFG->dirroot.'/f2_lib/report.php');
require_once($CFG->dirroot.'/f2_lib/constants.php');

require_login();
//$context = get_context_instance(CONTEXT_SYSTEM);
$context = context_system::instance();
//require_capability('local/f2_course:mycourses',get_context_instance(CONTEXT_COURSE, 1));
require_capability('local/f2_course:mycourses', context_course::instance(1));

$page     = optional_param('page', 0, PARAM_INT);
$perpage  = optional_param('perpage', 10, PARAM_INT);
$column   = optional_param('column', 'timestart', PARAM_TEXT);
$sort     = optional_param('sort', 'DESC', PARAM_TEXT);

$pagename = get_string('my_courses', 'local_f2_course');

$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/local/f2_course/mycourse_prog.php');
$PAGE->set_title($pagename);
$PAGE->settingsnav;
$PAGE->navbar->add($pagename);
$PAGE->set_heading($SITE->shortname.': '.$pagename);

$viewable_years = array(get_anno_formativo_corrente(), get_anno_formativo_corrente()-1);

$filtercourse   = optional_param('filtercourse', '', PARAM_TEXT);
$filteryear     = optional_param('filteryear', '', PARAM_TEXT);
$filtercourse_arr = array();
$filteryear_param = -1;
if ($filtercourse !== '')
{
	$filtercourse_arr['idnumber'] = mysql_escape_string($filtercourse);
// 	$filtercourse_arr['idnumber'] = mysql_real_escape_string($filtercourse);
}
if ($filteryear !== '')
{
	$filteryear_param = intval(mysql_escape_string($filteryear));
// 	$filteryear_param = intval(mysql_real_escape_string($filteryear));
}

echo $OUTPUT->header();

$currenttab = 'my_courses_prog';
require('tabs_mycourses.php');

echo $OUTPUT->heading(get_string('my_courses_prog', 'local_f2_course'));
echo $OUTPUT->box_start();

//INIZIO Form
?>
<form id="mform1" name="mform1" action="" method="post">
    <b>Codice corso:</b>
    <input type="text" id="filtercourse" name="filtercourse" value="<?php echo $filtercourse ?>">

    <b>Anno:</b> 
    <select id ="filteryear" name ="filteryear">
            <option value="<?php echo '' ?>" <?php if(''===$filteryear) echo 'selected=""' ?>><?php echo 'Tutti' ?></option>
            <option value="<?php echo $viewable_years[0] ?>" <?php if($viewable_years[0] == intval($filteryear)) { echo 'selected=""'; } else echo ''; ?>><?php echo $viewable_years[0]?></option>
            <option value="<?php echo $viewable_years[1] ?>" <?php if($viewable_years[1] == intval($filteryear)) { echo 'selected=""'; } else echo ''; ?>><?php echo $viewable_years[1]?></option>
    </select>
    <input type="submit" name="submitbtn" id="submitbtn" value="Cerca">
</form>
<?php
//FINE Form

$pagination = array('perpage' => $perpage,
					'page' => $page,
					'column' => $column,
					'sort' => $sort);

//ID del form dove fare il submit
$form_id = 'mform1';	 										
$post_extra = array('column' => $column, 'sort' => $sort);

$mycourses = get_mycourses(NULL, C_PRO, $pagination, $filtercourse_arr, $filteryear_param);
$total_rows = count($mycourses);
if ($total_rows > 0) {
    $table = new html_table();
    $table->width = '80%';
    $head_table = array('fullname','my_c_idnumber','timestart','my_c_timefinish','my_c_userstatus','tipo_budget','my_c_course_status','my_c_timecompleted','action');

    $head_table_sort = array('fullname', 'timestart', 'tipo_budget');
    $tootip = array('','','','',get_string('my_c_userstatus_tooltip', 'local_f2_traduzioni'),'',get_string('my_c_course_status_tooltip', 'local_f2_traduzioni'),'','');

    $align = array('center','center','center','center','center','center','center','center');

    $table->align = $align;
    $table->head = build_head_table($head_table, $head_table_sort, $post_extra, $total_rows, $page, $perpage, $form_id, $tootip);

    $table->data = array();
    foreach ($mycourses as $c) {
        
        $status = get_string('my_booked','local_f2_course');
        $timestart = ($c->timestart) ? date('d/m/Y', $c->timestart) : '';
        $timefinish = ($c->timefinish) ? date('d/m/Y', $c->timefinish) : '';
        
        //AK-LM: modalità didattica dedotta dal flag tipo budget
        $modalita_didattica = ((int)$c->tipo_budget === BDGT_ONLINE ? get_string('on_line', 'local_f2_traduzioni') : get_string('aula', 'local_f2_traduzioni'));
        
        /*
         * Se esistono criteri di completamento (block_completion_status)
         */
        $info = new completion_info($c);
        $criterias = $info->get_criteria();
        if(!empty($criterias)) //Se sono definiti criteri di completamento
        {
            $val_icon = " <a href=\"{$CFG->wwwroot}/grade/report/user/index.php?id={$c->id}\" title=\"Dettagli valutazione\">".
					"<img src=\"{$CFG->wwwroot}/pix/t/grades.gif\" class=\"icon\" alt=\"Dettagli valutazione\" /></a>";
            $status_corso = getCourseCompletationFlag($c);
            /*
             * Se i criteri di completamento del corso sono soddisfatti, mostriamo 
             * la data effettiva di completamento, in alternativa la data di fine
             * edizione come default
             */
            $timecompleted = getCourseTimeCompleted($c);
        } else {
            $val_icon = "";
            $status_corso = "--";
            $timecompleted = (time() >= $c->timefinish ? $c->timefinish : false);
        }
        
        $go_icon = " <a href=\"{$CFG->wwwroot}/course/view.php?id={$c->id}\" title=\"Vai al corso\">".
                "<img src=\"{$CFG->wwwroot}/local/f2_course/pix/entra.png\" class=\"icon\" alt=\"Vai al corso\" /></a>";
        //AK-LM: il link di accesso al corso è mostrato solo se l'edizione cui è iscritto l'utente è in corso.
        $actdata = (time() >= $c->timestart ? $go_icon : "")." ".$val_icon;
        $table->data[] = array(
                        $c->fullname,
                        $c->idnumber,
                        $timestart,
                        $timefinish,
                        $status,
                        $modalita_didattica,
                        $status_corso,
                        ($timecompleted ? date('d/m/Y', $timecompleted) : ''),
                        $actdata
                        );
    }

    $paging_bar = new paging_bar_f2($total_rows, $page, $perpage, $form_id, $post_extra);
    echo $paging_bar->print_paging_bar_f2();
    echo html_writer::table($table);
    echo $paging_bar->print_paging_bar_f2();
} else {
    echo heading_msg(get_string('nomycoursesfoundprg', 'local_f2_traduzioni'));
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
