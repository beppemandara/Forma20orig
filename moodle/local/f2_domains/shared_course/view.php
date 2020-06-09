<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Display profile for a particular user
 *
 * @copyright 1999 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package user
 */

require_once("../../../config.php");
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/tag/lib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot.'/f2_lib/management.php');
require_once('remove_all_roles_form.php');

$userid = required_param('userid', PARAM_INT);   // user id
$courseid = optional_param('course', SITEID, PARAM_INT);   // course id (defaults to Site)
$flag_azione = optional_param('flag_azione', '', PARAM_ALPHA);   // flag_azione (Rimuovi / Sostituisci)
$id_utente_sostituto = optional_param('id_utente_sostituto', 0, PARAM_INT);   // flag_azione (Rimuovi / Sostituisci)
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 20, PARAM_INT);        // how many per page
$sitecontext = get_context_instance(CONTEXT_SYSTEM);
//$PAGE->set_url('/user/view.php', array('id'=>$userid));

if (!has_capability('moodle/role:assign', $sitecontext)) {
    print_error('nopermissions', 'error', '', 'assign organisation to users');
}
    
$user = $DB->get_record('user', array('id'=>$userid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
$currentuser = ($user->id == $USER->id);
$coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
$usercontext   = get_context_instance(CONTEXT_USER, $user->id, IGNORE_MISSING);
$strpersonalprofile = get_string('personalprofile');
$strparticipants = get_string("participants");
$struser = get_string("user");
$fullname = fullname($user, has_capability('moodle/site:viewfullnames', $coursecontext));

$PAGE->set_context($coursecontext);
$PAGE->set_url(new moodle_url("{$CFG->wwwroot}/local/f2_domains/shared_course/view.php"), array('id'=>$userid));
$PAGE->set_course($course);
$PAGE->set_pagelayout('standard');
$PAGE->set_pagetype('course-view-' . $course->format);  // To get the blocks exactly like the course
$PAGE->add_body_class('path-user');                     // So we can style it independently
$PAGE->set_other_editing_capability('moodle/course:manageactivities');
$PAGE->set_title("$course->fullname: $strpersonalprofile: $fullname");
if ($currentuser) {
	$PAGE->navbar->add($course->fullname);
} else {
    if (!is_enrolled($coursecontext, $user->id)) {
        if (has_capability('moodle/role:assign', $coursecontext)) {
            $PAGE->navbar->add($fullname);
        } else {
            $PAGE->navbar->add($struser);
        }
    } else {
		$PAGE->navbar->add($course->fullname.": ".$fullname);
	}
}
$PAGE->set_heading($course->fullname);


$str = <<<'EFO'
<script type="text/javascript">
//<![CDATA[

function confirmSubmit()
{

		var agree=confirm("Stai associando all'utente i corsi di competenza della scuola. Proseguire?");
		if (agree)
			return true ;
		else
			return false ;
}
//]]>
</script>
EFO;

echo $str;

// inizio import per generazione tabella //
$PAGE->requires->css('/f2_lib/jquery/css/dataTable.css');
$PAGE->requires->css('/f2_lib/jquery/css/ui_custom.css');
$PAGE->requires->js('/f2_lib/jquery/jquery-1.7.1.min.js');
$PAGE->requires->js('/f2_lib/jquery/jquery.dataTables.js');
$PAGE->requires->js('/f2_lib/jquery/custom.js');
// fine import per generazione tabella //

$isparent = false;

if (!$currentuser and !$user->deleted
  and $DB->record_exists('role_assignments', array('userid'=>$USER->id, 'contextid'=>$usercontext->id))
  and has_capability('moodle/user:viewdetails', $usercontext)) {
    // TODO: very ugly hack - do not force "parents" to enrol into course their child is enrolled in,
    //       this way they may access the profile where they get overview of grades and child activity in course,
    //       please note this is just a guess!
    require_login();
    $isparent = true;
    $PAGE->navigation->set_userid_for_parent_checks($userid);
} else {
    // normal course
    require_login($course);
    // what to do with users temporary accessing this course? should they see the details?
}

/// Now test the actual capabilities and enrolment in course
if ($currentuser) {
    // me
    if (!is_viewing($coursecontext) && !is_enrolled($coursecontext)) { // Need to have full access to a course to see the rest of own info
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('notenrolled', '', $fullname));
        if (!empty($_SERVER['HTTP_REFERER'])) {
            echo $OUTPUT->continue_button($_SERVER['HTTP_REFERER']);
        }
        echo $OUTPUT->footer();
        die;
    }

} else {
    // somebody else
    $PAGE->set_title("$strpersonalprofile: ");
    $PAGE->set_heading("$strpersonalprofile: ");

    // check course level capabilities
    if (!has_capability('moodle/user:viewdetails', $coursecontext) && // normal enrolled user or mnager
        ($user->deleted or !has_capability('moodle/user:viewdetails', $usercontext))) {   // usually parent
        print_error('cannotviewprofile');
    }

    if (!is_enrolled($coursecontext, $user->id)) {
        // TODO: the only potential problem is that managers and inspectors might post in forum, but the link
        //       to profile would not work - maybe a new capability - moodle/user:freely_acessile_profile_for_anybody
        //       or test for course:inspect capability
        if (has_capability('moodle/role:assign', $coursecontext)) {
            //$PAGE->navbar->add($fullname);
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('notenrolled', '', $fullname));
        } else {
            echo $OUTPUT->header();
            //$PAGE->navbar->add($struser);
            echo $OUTPUT->heading(get_string('notenrolledprofile'));
        }
        if (!empty($_SERVER['HTTP_REFERER'])) {
            echo $OUTPUT->continue_button($_SERVER['HTTP_REFERER']);
        }
        echo $OUTPUT->footer();
        exit;
    }

    // If groups are in use and enforced throughout the course, then make sure we can meet in at least one course level group
    if (groups_get_course_groupmode($course) == SEPARATEGROUPS and $course->groupmodeforce
      and !has_capability('moodle/site:accessallgroups', $coursecontext) and !has_capability('moodle/site:accessallgroups', $coursecontext, $user->id)) {
        if (!isloggedin() or isguestuser()) {
            // do not use require_login() here because we might have already used require_login($course)
            redirect(get_login_url());
        }
        $mygroups = array_keys(groups_get_all_groups($course->id, $USER->id, $course->defaultgroupingid, 'g.id, g.name'));
        $usergroups = array_keys(groups_get_all_groups($course->id, $user->id, $course->defaultgroupingid, 'g.id, g.name'));
        if (!array_intersect($mygroups, $usergroups)) {
            print_error("groupnotamember", '', "../course/view.php?id=$course->id");
        }
    }
}

/// We've established they can see the user's name at least, so what about the rest?
if (!$currentuser) {
    $PAGE->navigation->extend_for_user($user);
    if ($node = $PAGE->settingsnav->get('userviewingsettings'.$user->id)) {
        $node->forceopen = true;
    }
} else if ($node = $PAGE->settingsnav->get('usercurrentsettings', navigation_node::TYPE_CONTAINER)) {
    $node->forceopen = true;
}
if ($node = $PAGE->settingsnav->get('courseadmin')) {
    $node->forceopen = false;
}
		
echo $OUTPUT->header();
echo $OUTPUT->heading("Dettaglio referente");
echo '<div class="userprofile">';

if ($user->deleted) {
    echo $OUTPUT->heading(get_string('userdeleted'));
    if (!has_capability('moodle/user:update', $coursecontext)) {
        echo $OUTPUT->footer();
        die;
    }
}

if (is_mnet_remote_user($user)) {
    $sql = "SELECT h.id, h.name, h.wwwroot,
                   a.name as application, a.display_name
              FROM {mnet_host} h, {mnet_application} a
             WHERE h.id = ? AND h.applicationid = a.id";

    $remotehost = $DB->get_record_sql($sql, array($user->mnethostid));
    $a = new stdclass();
    $a->remotetype = $remotehost->display_name;
    $a->remotename = $remotehost->name;
    $a->remoteurl  = $remotehost->wwwroot;

    echo $OUTPUT->box(get_string('remoteuserinfo', 'mnet', $a), 'remoteuserinfo');
}

// Print all the little details in a list

$dati_user = get_user_data($userid);
$user_custom_filed = profile_user_record($userid);
$direzione = get_direzione_utente($userid);
$settore = get_settore_utente($userid);
// INIZIO TABELLA DATI ANAGRAFICI
$table_anag = new html_table();
$table_anag->align = array('right', 'left');
$table_anag->data = array(
                    array('Cognome Nome: ','<b>'.fullname($dati_user).'</b>'),
                    array('Matricola',''.$dati_user->idnumber.''),
                    array('Categoria',''.$user_custom_filed->category),
                    array('Direzione',''.$direzione['shortname']." - ".$direzione['name']),
                    array('Settore',''.$settore['name'])
            );

echo html_writer::table($table_anag);
// FINE TABELLA DATI ANAGRAFICI

// Print messaging link if allowed
/*if (isloggedin() && has_capability('moodle/site:sendmessage', $usercontext)
    && !empty($CFG->messaging) && !isguestuser() && !isguestuser($user) && ($USER->id != $user->id)) {
    echo '<div class="messagebox"><center>';
    echo '<a href="'.$CFG->wwwroot.'/message/index.php?id='.$user->id.'">'.get_string('messageselectadd').'</a>';
    echo '</center></div>';
}*/

print_r('</br></br>');

////////////////////////////////////////////////////////////////////////////////
////////////////////// INIZIO ELENCO CATEGORIE //////////////////////
////////////////////////////////////////////////////////////////////////////////

// ottengo l'elenco delle categorie per cui è stato assegnato un ruolo di gestione (Supervisori di secondo livello e Referenti formativi) all'utente

$categorie = get_categorie_by_utente($userid);

$table_cat = new html_table();
$table_cat->head = array ();
$table_cat->align = array();
$table_cat->head[] = get_string('category');
$table_cat->align[] = 'left';
$table_cat->head[] = get_string('role');
$table_cat->align[] = 'left';

$table_cat->width = "95%";
foreach ($categorie as $cat) {
    $row = array ();
    $row[] = $cat->name;
    $row[] = $cat->ruolo;
    $table_cat->data[] = $row;
}
    
if (!empty($categorie)) {
    echo "<h3>".get_string('categorie_gestite', 'local_f2_domains')."</h3>";
    echo html_writer::table($table_cat);
} else {
    echo "<h3>".get_string('categorie_gestite', 'local_f2_domains')."</h3>";
    echo "<p>".get_string('nocategoriesfound', 'local_f2_domains')."</p>";
}

////////////////////////////////////////////////////////////////////////////////
////////////////////// FINE ELENCO CATEGORIE //////////////////////
////////////////////////////////////////////////////////////////////////////////

print_r('</br></br>');

////////////////////////////////////////////////////////////////////////////////
////////////////////// INIZIO ELENCO CORSI //////////////////////
////////////////////////////////////////////////////////////////////////////////

// ottengo l'elenco dei corsi obiettivo per cui è stato assegnato un ruolo di gestione (Supervisori di secondo livello e Referenti formativi) all'utente
$corsi = get_corsi_obiettivo_by_utente($userid, $page*$perpage, $perpage);
$cont = get_corsi_obiettivo_by_utente_count($userid);

$table = new html_table();
$table->head = array ();
$table->align = array();
$table->head[] = get_string('course');
$table->align[] = 'left';
$table->head[] = get_string('anno', 'local_f2_domains');
$table->align[] = 'left';
$table->head[] = get_string('role');
$table->align[] = 'left';

$table->width = "95%";

foreach ($corsi as $corso) {
    $row = array ();
    $row[] = $corso->fullname;
    $row[] = $corso->anno;
    $row[] = $corso->ruolo;
    $table->data[] = $row;
}

$baseurl = new moodle_url('/local/f2_domains/shared_course/view.php', array('userid' => $userid, 'perpage' => $perpage));
echo $OUTPUT->paging_bar($cont, $page, $perpage, $baseurl);

flush();
    
if (!empty($corsi)) {
    echo "<h3>".get_string('corsi_obiettivo_gestiti', 'local_f2_domains')."</h3>";
    echo html_writer::table($table);
    echo $OUTPUT->paging_bar($cont, $page, $perpage, $baseurl);
} else {
    echo "<h3>".get_string('corsi_obiettivo_gestiti', 'local_f2_domains')."</h3>";
    echo "<p>".get_string('nocoursesfound', 'local_f2_domains')."</p>";
}

////////////////////////////////////////////////////////////////////////////////
////////////////////// FINE ELENCO CORSI //////////////////////
////////////////////////////////////////////////////////////////////////////////

if (!empty($corsi) || !empty($categorie)) {
    $updateurl = "{$CFG->wwwroot}/local/f2_domains/shared_course/view.php";
    $form = new remove_all_roles_form(new moodle_url($updateurl,array('userid' => $userid)), compact('userid'));
    $form->set_data($userid);
    
    if ($form->is_cancelled()){
        redirect('user.php');
    }

    if ($form->is_submitted()) {
        if ($flag_azione == 'S') {
            // SOSTITUZIONE
            if ($id_utente_sostituto) {
                removeUserAssignments($userid, $id_utente_sostituto);
                redirect('user.php');
            } else {
            // Print error message if no organisation chosen
                echo $OUTPUT->box_start('errorbox errorboxcontent boxaligncenter boxwidthnormal');
                echo get_string('utente_sostitutivo_err','local_f2_domains');
                echo $OUTPUT->box_end();
            }
        } else {
            // RIMOZIONE
            removeUserAssignments($userid);
            redirect('user.php');
        }
    }

    $form->display();
}


if(isReferenteScuola($userid)){
	
	$id_org_scuola_utente = $DB->get_record_sql("SELECT
															organisationid
														FROM {org_assignment} oa
														WHERE userid = ".$userid);
		
	$id_scuola = $DB->get_record_sql("SELECT
															id
														FROM {f2_fornitori} ff
														WHERE ff.id_org = ".$id_org_scuola_utente->organisationid);
		
	$id_corsi_scuola = $DB->get_records_sql("SELECT
															ac.courseid,c.idnumber,c.fullname
														FROM {f2_anagrafica_corsi} ac,
															 {course} c
														WHERE ac.courseid = c.id AND ac.id_dir_scuola = ".$id_scuola->id);
	
	echo '<br><br><b>Associa corsi</b><br><br>';
	echo 'Associa all\'utente i corsi di competenza della scuola<br><br>';
	echo '<form id="form1" name="form1" method="post" action="'.$baseurl.'">';
	foreach($id_corsi_scuola as $dati_id_corsi_scuola){
		echo $dati_id_corsi_scuola->idnumber.' - '.$dati_id_corsi_scuola->fullname;
		echo '<br>';
	}
	echo '<br>';
	echo '<input type="submit" name="associa_corsi" id="associa_corsi" value="Associa" onClick="return confirmSubmit()" title="I corsi già associati all\'utente non verranno modificati" />';
	echo '</form>';
	
	if(isset($_POST['associa_corsi'])){

			$esito = true;
			foreach($id_corsi_scuola as $id_corso){
				$course = $DB->get_record('course', array('id'=>$id_corso->courseid), '*', MUST_EXIST);
				$manager = new course_enrolment_manager($PAGE, $course);
				$param = get_parametro('p_f2_id_ruolo_referente_scuola');
				$roleid = $param->val_int;
				if(!$manager->assign_role_to_user($roleid, $user->id)){
					$esito = false;
				}
			}
			if($esito){
				echo '<br><b><span style="color:green">Associazione eseguita correttamente</span></b>';
			}else{
				echo '<br><b><span style="color:red">Errore: Associazione non eseguita correttamente</span></b>';
			}
	}
}


echo '</div>';  // userprofile class
echo $OUTPUT->footer();

/// Functions ///////

function print_row($left, $right) {
    echo "\n<tr><th class=\"label c0\">$left</th><td class=\"info c1\">$right</td></tr>\n";
}
