<?php
	// $Id: user.php 1241 2013-12-20 04:34:05Z l.moretto $
	
    global $PAGE, $SITE, $OUTPUT;

    require_once('../../config.php');
    require_once($CFG->libdir.'/adminlib.php');
    require_once('filters/lib.php');
    require_once($CFG->dirroot.'/f2_lib/management.php');
    require_once('lib.php');
    require_once($CFG->dirroot.'/user/profile/lib.php');
    require_once($CFG->dirroot.'/tag/lib.php');
    require_once($CFG->libdir . '/filelib.php');
    
    $sort         = optional_param('sort', 'lastname', PARAM_ALPHANUM);
    $dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
    $page         = optional_param('page', 0, PARAM_INT);
    $perpage      = optional_param('perpage', 20, PARAM_INT);        // how many per page
    $training	  = optional_param('training', '', PARAM_TEXT);
    $dato_ricercato = optional_param('dato_ricercato', '', PARAM_TEXT);
    
    $sitecontext = get_context_instance(CONTEXT_SYSTEM);
    $site = get_site();

    $blockname = get_string('pluginname', 'block_f2_formazione_individuale');

    $url = new moodle_url("{$CFG->wwwroot}/blocks/f2_formazione_individuale/user.php?training=".$training."");
    $gestionecorsi_url = new moodle_url("{$CFG->wwwroot}/blocks/f2_formazione_individuale/gestione_corsi.php?training=".$training."");
    $PAGE->set_url($url);
    $PAGE->set_context($sitecontext);
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title(get_string('selezionautente', 'block_f2_formazione_individuale'));
    $PAGE->settingsnav;
    $label_training = get_lable_training($training);
    $PAGE->navbar->add(get_string('formazione_individuale', 'block_f2_formazione_individuale'));
    $PAGE->navbar->add(get_string($label_training, 'block_f2_formazione_individuale'));
    $PAGE->navbar->add(get_string('gestionecorsi', 'block_f2_formazione_individuale'), $gestionecorsi_url);
    $PAGE->navbar->add(get_string('creazioneanagrafica', 'block_f2_formazione_individuale'));
    $PAGE->navbar->add(get_string('selezionautente', 'block_f2_formazione_individuale'), $url);
    $PAGE->set_heading($SITE->shortname.': '.$blockname);
    
    $context_sys = get_context_instance(CONTEXT_SYSTEM);
    //require_capability('block/f2_apprendimento:leggistorico', $context);
    $capability_giunta = has_capability('block/f2_formazione_individuale:individualigiunta', $context_sys);
    $capability_linguagiunta = has_capability('block/f2_formazione_individuale:individualilinguagiunta', $context_sys);
    $capability_consiglio = has_capability('block/f2_formazione_individuale:individualiconsiglio', $context_sys);
    $param_CIG = get_parametro('p_f2_corsi_individuali_giunta');
    $param_CIL = get_parametro('p_f2_corsi_individuali_lingua_giunta');
    $param_CIC = get_parametro('p_f2_corsi_individuali_consiglio');
    
    if(!(($capability_giunta && $training == $param_CIG->val_char) || ($capability_linguagiunta && $training == $param_CIL->val_char) || ($capability_consiglio && $training == $param_CIC->val_char))){
    	print_error('nopermissions', 'error', '', 'formazione_individuale');
    }
    
    echo $OUTPUT->header();
    $context = context_system::instance();
    $extracolumns = get_extra_user_fields($context);
    $columns = array_merge(array('firstname', 'lastname'), $extracolumns);

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
        $$column = "<a href=\"user.php?sort=$column&amp;dir=".$columndir."&training=".$training."&dato_ricercato=".$dato_ricercato."\">".$string[$column]."</a>$columnicon";
    }
    
    if ($sort == "name") {
        $sort = "firstname";
    }

    // create the user filter form
    $ufiltering = new my_users_filtering(null,"user.php?training=".$training);
    list($extrasql, $params) = $ufiltering->get_sql_filter();
    
    if(!isset($_GET['dato_ricercato'])){
    	$users ="";
    	$usercount = 0;
    }
    else{
    	$users = $ufiltering->get_my_users_listing_cohort_ind($sort, $dir, $page*$perpage, $perpage,$dato_ricercato, '', '', $extrasql, $params);
    	$usercount = $ufiltering->get_count_my_users_listing_cohort_ind($sort, $dir, 0, 0, $dato_ricercato, '', '', $extrasql, $params);
    }
    
/*  $usercount = $ufiltering->get_my_users_count(false);
    $usersearchcount = $ufiltering->get_my_users_count(true, false, '', $extrasql, $params);
    if ($extrasql !== '') {
        echo $OUTPUT->heading("$usersearchcount / $usercount ".get_string('users'));
        $usercount = $usersearchcount;
    } else {
        echo $OUTPUT->heading("$usercount ".get_string('users'));
    }*/
   
    $strall = get_string('all');

    $baseurl = new moodle_url('/blocks/f2_formazione_individuale/user.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage,'training' => $training,'dato_ricercato' => $dato_ricercato));
    

    flush();


    if (!$users) {
        $match = array();
      //  echo $OUTPUT->heading(get_string('nousersfound'));
        $table = NULL;

    } else {

        $override = new stdClass();
        $override->firstname = 'firstname';
        $override->lastname = 'lastname';
        $fullnamelanguage = get_string('fullnamedisplay', '', $override);
        if (($CFG->fullnamedisplay == 'firstname lastname') or
            ($CFG->fullnamedisplay == 'firstname') or
            ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'firstname lastname' )) {
           // $fullnamedisplay = "$firstname / $lastname";
            $fullnamedisplay = "$lastname / $firstname";
        } else { // ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'lastname firstname')
            $fullnamedisplay = "$lastname / $firstname";
        }

        $table = new html_table();
        $table->head = array ();
        $table->align = array();
        $table->head[] = $fullnamedisplay;
        $table->align[] = 'left';
        $table->head[] = get_string('matricola', 'block_f2_formazione_individuale');
        $table->align[] = 'left';
        $table->head[] = get_string('dominio', 'block_f2_formazione_individuale');
        $table->align[] = 'left';

        $table->width = "100%";
        foreach ($users as $user) {
            if (isguestuser($user)) {
                continue; // do not display guest here
            }

         //   $fullname = fullname($user, true);
            $fullname = $user->lastname." ".$user->firstname;

            $row = array ();
            $row[] = "<a href=\"aggiungi_anagrafica.php?userid=".$user->id."&training=".$training."\">$fullname</a>";
            $row[] = $user->idnumber;
           // $row[] = $user->org_shortname.' - '.$user->org_fullname;
            $row[] = $user->org_direzione;
            $table->data[] = $row;
        }
    }

    // add filters
 //   $ufiltering->display_add();
 //  $ufiltering->display_active();
    
    echo html_writer::start_tag('form', array('action' => $baseurl, 'method' => 'post'));
    // Submit ricerca
    echo '<table><tr>';
    echo '<td>Cognome: <input maxlength="254" size="50" name="dato_ricercato" type="text" id="id_dato_ricercato" value="'.$dato_ricercato.'" /></td>';
    echo '<td><input name="Cerca" value="Cerca" type="submit" id="id_Cerca" /></td>';
    echo '</tr></table>';
    echo html_writer::end_tag('form');
    
    echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);

    if(isset($_GET['dato_ricercato'])) {
    	if (!$usercount)
    		echo $OUTPUT->container(get_string("no_user", "block_f2_formazione_individuale"), 'box generalbox', 'notice');
    	else
    		echo "<b>".$usercount." ".get_string('users')."</b><br>";
    } else {
        echo $OUTPUT->container('Per trovare un utente, immettere il cognome nella casella e fare clic su Cerca.', 'box generalbox', 'notice');
    }

    if (!empty($table)) {
        echo html_writer::table($table);
        echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);
    }

    echo '<input type="button" value="'.get_string("indietro", "block_f2_formazione_individuale").'" onclick="parent.location=\''.$gestionecorsi_url.'\'">';
    
    echo $OUTPUT->footer();



