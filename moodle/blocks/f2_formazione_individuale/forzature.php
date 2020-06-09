<?php

// $Id: forzature.php 1125 2013-05-02 15:15:56Z d.lallo $ 

global $CFG, $OUTPUT, $PAGE, $DB;

require_once('../../config.php');
require_once "$CFG->dirroot/course/lib.php";
require_once "$CFG->libdir/adminlib.php";
require_once "$CFG->dirroot/user/filters/lib.php";
require_once 'forzature_form.php';
require_once 'lib.php';
require_once($CFG->dirroot.'/f2_lib/report.php');


require_login();

$page     = optional_param('page', 0, PARAM_INT);
$perpage  = optional_param('perpage', 10, PARAM_INT);
$column   = optional_param('column', 'lastname', PARAM_TEXT);
$sort     = optional_param('sort', 'ASC', PARAM_TEXT);

$blockname = get_string('pluginname', 'block_f2_formazione_individuale');
$header = get_string('forzature', 'block_f2_formazione_individuale');

$context = get_context_instance(CONTEXT_SYSTEM);

if (empty($CFG->loginhttps)) {
        $securewwwroot = $CFG->wwwroot;
} else {
        $securewwwroot = str_replace('http:','https:',$CFG->wwwroot);
}

$baseurl = new moodle_url('forzature.php');
$PAGE->set_context($context);
$PAGE->set_url('/blocks/f2_formazione_individuale/forzature.php');
$PAGE->set_pagelayout('standard');
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('formazione_individuale', 'block_f2_formazione_individuale'));
$PAGE->navbar->add($header,$baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);

$capability_forzature = has_capability('block/f2_formazione_individuale:forzature', $context);
if(!$capability_forzature){
	print_error('nopermissions', 'error', '', 'forzature');
}


if(empty($sort)) $sort = 'lastname';

$form = new forzature_form(null);
$cognome = '';

$data = $form->get_data();

if ($form->is_cancelled()) 
{
        $form->set_data(array('cognome' => ''));
}
else if ($data)
{
        $cognome = str_replace('*','',trim(strip_tags($data->cognome))); 
}
$pagination = array('perpage' => $perpage, 'page'=>$page,'column'=>$column,'sort'=>$sort);
foreach ($pagination as $key=>$value)
{
        $data->$key = $value;
}
	
$usersall = get_forzature($data);
$users = $usersall->dati;
$total_rows = $usersall->count;

echo $OUTPUT->header();
echo $OUTPUT->heading($header);
echo $OUTPUT->box_start();

echo '
<script type="text/javascript">
//<![CDATA[
function checkAll(from,to)
{
        var i = 0;
        var chk = document.getElementsByName(to);
        var resCheckBtn = document.getElementsByName(from);
        var resCheck = resCheckBtn[i].checked;
        var tot = chk.length;
        for (i = 0; i < tot; i++) chk[i].checked = resCheck;
}

function checkSelected(cname,errmsg,confmsg)
{
        var selected = 0;
        var chk = document.getElementsByName(cname);
        var tot = chk.length;
        for (i = 0; i < tot; i++) 
        {
                if (chk[i].checked)
                {	
                        selected++;
                        break;
                }
        }
        if (selected == 0) 
        {
                alert(errmsg);
                return false;
        }
        else
        {
                return confirm(confmsg.replace("_","\'"));
        }
}
//]]>
</script>
';

$form->set_data(array('cognome' => $cognome));
echo $form->display();

$form_id='mform1'; // ID del form dove fare il submit
$post_extra=array('column'=>$column,'sort'=>$sort);

if($total_rows > 0) {

        $head_table = array('chk_all_forzatura','empty','codice_fiscale','name','sex','matricola','qualifica','direzione','settore','datafine');
        $head_table_sort = array('lastname','domain');
        $align = array ('center','center','left','left','left');

        $table = new html_table();
        $table->width = '100%';
        $table->align = $align;
        $table->head = build_head_table($head_table,$head_table_sort,$post_extra,$total_rows, $page, $perpage, $form_id);

        foreach ($users as $user) 
        {
                $buttons = array();
                // edit button
                                $buttons[] = html_writer::link(new moodle_url('dettagli_forzatura.php', array('forzatura_id'=>$user->forzatura_id, 'edit'=>true
                                )), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/edit'), 'alt'=>get_string('edit', 'block_f2_formazione_individuale'), 'class'=>'iconsmall')), array('title'=>get_string('edit', 'block_f2_formazione_individuale')));
                $row = array ();
                $row[] = '<input type=checkbox name="forzatura_id[]" value='.$user->forzatura_id.'>';
                $row[] = implode(' ', $buttons);
                $row[] = $user->codice_fiscale;
                $row[] = fullname($user, true);
                $row[] = $user->sesso;
                $row[] = $user->matricola;
                $row[] = $user->qualifica;
                $row[] = $user->cod_direzione.' - '.$user->direzione;
                $row[] = $user->cod_settore.' - '.$user->settore;
                $row[] = userdate($user->data_fine, get_string('strftimedatefullshort'));

                $table->data[] = $row;
        }
}
echo '<form method="post" action="remove_forzatura.php" id="forzatura_elimina_form">';
$btn_row = array();
$btn_row[] = "<input type=\"button\" value=\"".get_string('nuovo', 'block_f2_formazione_individuale')."\" onclick=\"document.location.href='add_forzatura.php'\"/>";
if($total_rows > 0)
{
        $btn_row[] = "<input type=\"submit\" value=\"".get_string('elimina', 'block_f2_formazione_individuale')."\" onclick=\"return checkSelected('forzatura_id[]','".get_string('no_selection', 'block_f2_formazione_individuale')."','".htmlspecialchars(get_string('confirm_delete_msg', 'block_f2_formazione_individuale'))."')\"/>";
}
else $btn_row[] = '';
$btn_table = '<table align="left" width="10%"><tr>';
$buttemp='';
foreach ($btn_row as $b)
{
        $buttemp = $buttemp.'<td>'.$b.'</td>';
}
$btn_table = $btn_table.$buttemp.'</tr></table>';
echo $btn_table;

echo "<br/><br/><br/><p><b style='font-size:11px'>".get_string('count_tot_rows', 'local_f2_traduzioni',$total_rows)."</b></p>";
if($total_rows > 0)
{
    $paging_bar = new paging_bar_f2($total_rows, $page, $perpage, $form_id, $post_extra);
    echo $paging_bar->print_paging_bar_f2();

    echo html_writer::table($table);

    echo $paging_bar->print_paging_bar_f2();
} else {
    echo $OUTPUT->heading(get_string('noresults', 'block_f2_formazione_individuale'));
}

echo '</form>';

echo $OUTPUT->box_end();
echo $OUTPUT->footer();

