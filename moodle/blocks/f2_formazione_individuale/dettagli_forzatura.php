<?php

//$Id: dettagli_forzatura.php 1153 2013-05-30 15:43:41Z d.lallo $
global $OUTPUT, $PAGE, $SITE, $CFG, $DB;

require_once '../../config.php';
require_once($CFG->dirroot.'/f2_lib/core.php');
require_once($CFG->dirroot.'/f2_lib/management.php');
require_once($CFG->dirroot.'/local/f2_support/lib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once 'forzature_form.php';
require_once 'lib.php';


require_login();
$context = get_context_instance(CONTEXT_SYSTEM);

$userid = optional_param('userid', 0, PARAM_INT);
$forzatura_id = optional_param('forzatura_id', 0, PARAM_INT);
$edit = optional_param('edit', false, PARAM_BOOL);
//$saved  = optional_param('saved', false, PARAM_BOOL);
//$alert  = optional_param('alert', false, PARAM_BOOL);


$blockname = get_string('pluginname', 'block_f2_formazione_individuale');

$aggiunta_forzatura_url = new moodle_url('add_forzatura.php');
$forzature_url = new moodle_url('forzature.php');

$PAGE->set_context($context);
$PAGE->set_url('/blocks/f2_formazione_individuale/formatori/dettagli_forzatura.php');
$PAGE->set_pagelayout('standard');
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('formazione_individuale', 'block_f2_formazione_individuale'));
$PAGE->navbar->add(get_string('forzature', 'block_f2_formazione_individuale'), $forzature_url);

$capability_forzature = has_capability('block/f2_formazione_individuale:forzature', $context);
if(!$capability_forzature){
	print_error('nopermissions', 'error', '', 'forzature');
}

if ($edit){
    $PAGE->navbar->add(get_string('edit_forzatura', 'block_f2_formazione_individuale'));
} else {
    $PAGE->navbar->add(get_string('add_forzatura', 'block_f2_formazione_individuale'), $aggiunta_forzatura_url);
    $PAGE->navbar->add(get_string('dettagli_forzatura', 'block_f2_formazione_individuale'));
}
$PAGE->set_heading($SITE->shortname.': '.$blockname);

if ($edit) {
    $forzatura = $DB->get_record('f2_forzature', array('id' => $forzatura_id));
    $user = $DB->get_record('user', array('username'=>$forzatura->codice_fiscale), '*', MUST_EXIST);
} else {
    $user = $DB->get_record('user', array('id'=>$userid), '*', MUST_EXIST);
}

// inizio import per generazione albero //
    $PAGE->requires->js('/f2_lib/jquery/jquery-1.7.1.min.js');
    $PAGE->requires->js('/f2_lib/jquery/jquery-ui.min.js');
    $PAGE->requires->js('/f2_lib/jquery/jquery.cookie.js');
    $PAGE->requires->js('/f2_lib/jquery/jquery.dynatree.js');
    $PAGE->requires->css('/f2_lib/jquery/css/skin/ui.dynatree.css');
    $PAGE->requires->js('/f2_lib/jquery/jquery.blockUI.js');
// fine import per generazione albero //
    
echo $OUTPUT->header();

$coursecontext = get_context_instance(CONTEXT_COURSE, SITEID);

/// Get the hidden field list
if (has_capability('moodle/user:viewhiddendetails', $coursecontext)) {
    $hiddenfields = array();
} else {
    $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
}

echo '<div class="userprofile">';
echo '<div class="userprofilebox clearfix"><div class="profilepicture">';
echo $OUTPUT->user_picture($user, array('size'=>100));
echo '</div>';
// Print all the little details in a list
echo '<table class="list" summary="">';
$override = new stdClass();
$override->firstname = 'firstname';
$override->lastname = 'lastname';
$fullnamelanguage = get_string('fullnamedisplay', '', $override);
if (($CFG->fullnamedisplay == 'firstname lastname') or
    ($CFG->fullnamedisplay == 'firstname') or
    ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'firstname lastname' )) {
    $fullnamedisplay = get_string('firstname').' / '.get_string('lastname');
} else { // ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'lastname firstname')
    $fullnamedisplay = get_string('lastname').' / '.get_string('firstname');
}
print_row($fullnamedisplay . ':', fullname($user, true));
print_row(get_string('codicefiscale', 'block_f2_formazione_individuale') . ':', $user->username);

if (!isset($hiddenfields['suspended'])) {
    if ($user->suspended) {
        print_row('', get_string('suspended', 'auth'));
    }
}

/// Print the Custom User Fields
profile_display_fields($user->id);

echo "</table></div></div></br>";

$form = new dettagli_forzatura_form(null, compact('user', 'edit'));
		
echo $OUTPUT->heading(get_string('dettagli_forzatura', 'block_f2_formazione_individuale'));

if ($form->is_cancelled()) {
    redirect($forzature_url);
} else if ($data = $form->get_data()) {
    $data = $form->get_data();

    $forzatura_new = new stdClass();

    if (!isDirezione($data->organisationid) && !isSettore($data->organisationid)) {
        echo $OUTPUT->box('E\' necessario selezionare una direzione o un settore', 'generalbox boxwidthnormal boxaligncenter');
        echo '<br />';
    } else {
        if (isDirezione($data->organisationid)){
            $direzione = $DB->get_record('org', array('id' => $data->organisationid), 'shortname, fullname', MUST_EXIST);
            $forzatura_new->cod_direzione = $direzione->shortname;
            $forzatura_new->direzione = $direzione->fullname;
            $forzatura_new->orgfk_direzione = $data->organisationid;
        } else if (isSettore($data->organisationid)) {
            $dominio = $DB->get_record('org', array('id' => $data->organisationid), 'shortname, fullname', MUST_EXIST);
            $id_padre = get_dominio_padre($data->organisationid);
            $direzione = $DB->get_record('org', array('id' => $id_padre), 'shortname, fullname', MUST_EXIST);
            $forzatura_new->cod_direzione = $direzione->shortname;
            $forzatura_new->direzione = $direzione->fullname;
            $forzatura_new->orgfk_direzione = $id_padre;
            $forzatura_new->cod_settore = $dominio->shortname;
            $forzatura_new->settore = $dominio->fullname;
        }

        $forzatura_new->codice_fiscale = $user->username;
        $forzatura_new->cognome = $user->lastname;
        $forzatura_new->nome = $user->firstname;
        $forzatura_new->sesso = get_data_profile_field_value_for_user('sex', $user->id);
        
        $param_cohort_CI = get_parametro('p_f2_cohort_corsi_individuali');
        $idnumber_cohort_CI = $param_cohort_CI->val_char;
        $cohort_id = $DB->get_field('cohort', 'id', array('idnumber' => $idnumber_cohort_CI), MUST_EXIST);
        $forzatura_new->cohort_fk = $cohort_id;
        
        $forzatura_new->qualifica = get_data_profile_field_value_for_user('category', $user->id);
        $forzatura_new->ap = get_data_profile_field_value_for_user('ap', $user->id);
        $forzatura_new->e_mail = $data->email;
        $forzatura_new->data_fine = $data->data_fine;
        $forzatura_new->nota = $data->note;
        
        if ($edit) {
            // se sono in modifica ho già l'id del record
            $forzatura_new->id = $forzatura_id;
            $DB->update_record('f2_forzature', $forzatura_new);
        } else {
            $forzatura_new->matricola = $data->matricola;
            // se no allora ottengo l'eventuale id di una forzatura già esistente
            $id = $DB->get_field('f2_forzature', 'id', array('codice_fiscale' => $forzatura_new->codice_fiscale));
            if ($id) {
                $forzatura_new->id = $id;
                $DB->update_record('f2_forzature', $forzatura_new);
            } else {
                $DB->insert_record('f2_forzature', $forzatura_new);
            }
        }

        echo $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
    }
}

if ($edit) {
    if (!is_null($forzatura->cod_settore)) {
        $sql = "SELECT id FROM {org} WHERE fullname = '$forzatura->settore' AND shortname = '$forzatura->cod_settore'";
        $org_id = $DB->get_field_sql($sql);
//        $org_id = $DB->get_field('org', 'id', array('fullname' => $forzatura->settore, 'shortname' => $forzatura->cod_settore));
        $org_title = $forzatura->cod_settore.' - '.$forzatura->settore;
    } else {
    	$org_id = $DB->get_field_sql("SELECT id FROM {org} WHERE fullname='".$forzatura->direzione."' AND shortname='".$forzatura->cod_direzione."'");
      //  $org_id = $DB->get_field('org', 'id', array('fullname' => $forzatura->direzione, 'shortname' => $forzatura->cod_direzione));
        $org_title = $forzatura->cod_direzione.' - '.$forzatura->direzione;
    }
    $form->set_data( array('organisationtitle' => $org_title));
    $form->set_data( array('organisationid' => $org_id));
    $form->set_data( array('matricola' => $forzatura->matricola));
    $form->set_data( array('email' => $forzatura->e_mail));
    $form->set_data( array('data_fine' => $forzatura->data_fine));
    $form->set_data( array('note' => $forzatura->nota));
    $form->set_data( array('edit' => true));
    $form->set_data( array('forzatura_id' => $forzatura_id));
} else {
    $form->set_data( array('organisationtitle' => ''));
    $form->set_data( array('organisationid' => 0));
    $form->set_data( array('matricola' => $user->idnumber));
    $form->set_data( array('email' => ''));
    $form->set_data( array('data_fine' => time()));
    $form->set_data( array('note' => ''));
}

echo $form->display();

//echo '</br><input type="button" value="'.get_string("back_to_forzature", "block_f2_formazione_individuale").'" onclick="parent.location=\''.$forzature_url.'\'">';

echo $OUTPUT->footer();

function print_row($left, $right) {
    echo "\n<tr><th class=\"label c0\">$left</th><td class=\"info c1\">$right</td></tr>\n";
}