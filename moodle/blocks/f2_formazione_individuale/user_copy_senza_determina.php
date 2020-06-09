<?php
    global $PAGE, $site, $OUTPUT,$CFG, $DB;

    require_once('../../config.php');
    require_once($CFG->libdir.'/adminlib.php');
    require_once('filters/lib.php');
    require_once($CFG->dirroot.'/f2_lib/management.php');
    require_once('lib_ind_senza_determina.php');
    require_once($CFG->dirroot.'/user/profile/lib.php');
    require_once($CFG->dirroot.'/tag/lib.php');
    require_once($CFG->libdir . '/filelib.php');
    require_login();

    $sort           = optional_param('sort', 'lastname', PARAM_ALPHANUM);
    $dir            = optional_param('dir', 'ASC', PARAM_ALPHA);
    $page           = optional_param('page', 0, PARAM_INT);
    $perpage        = optional_param('perpage', 20, PARAM_INT);
    $training	      = optional_param('training', '', PARAM_TEXT);
    $id_course	    = required_param('id_course', PARAM_INT);
    $dato_ricercato = optional_param('dato_ricercato', '', PARAM_TEXT);
    $label_training = get_label_training($training);

    $context = context_system::instance();
    $site = get_site();

    $blockname = get_string('pluginname', 'block_f2_formazione_individuale');
    $url = new moodle_url("{$CFG->wwwroot}/blocks/f2_formazione_individuale/user_copy_senza_determina.php?training=".$training."&id_course".$id_course);
    $gestionecorsi_url = new moodle_url("{$CFG->wwwroot}/blocks/f2_formazione_individuale/gest_corsi_ind_senza_determina.php?training=".$training."&id_course".$id_course);
    $PAGE->set_url($url);
    $PAGE->set_context($context);
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title(get_string('selezionautente', 'block_f2_formazione_individuale'));
    $PAGE->settingsnav;
    $PAGE->navbar->add(get_string('formazione_individuale', 'block_f2_formazione_individuale'));
    $PAGE->navbar->add(get_string($label_training, 'block_f2_formazione_individuale'));
    $PAGE->navbar->add(get_string('gestionecorsigratis', 'block_f2_formazione_individuale'), $gestionecorsi_url);
    $PAGE->navbar->add(get_string('copia_corso_gratis', 'block_f2_formazione_individuale'));
    $PAGE->navbar->add(get_string('selezionautente', 'block_f2_formazione_individuale'));
    $PAGE->set_heading($SITE->shortname.': '.$blockname);

    $param_CIG = get_parametro('p_f2_corsi_individuali_giunta');
    $param_CIL = get_parametro('p_f2_corsi_individuali_lingua_giunta');
    $param_CIC = get_parametro('p_f2_corsi_individuali_consiglio');
    $capability_giunta = has_capability('block/f2_formazione_individuale:individualigiunta', $context);
    $capability_linguagiunta = has_capability('block/f2_formazione_individuale:individualilinguagiunta', $context);
    $capability_consiglio = has_capability('block/f2_formazione_individuale:individualiconsiglio', $context);

    if(!(($capability_giunta && $training == $param_CIG->val_char) || ($capability_linguagiunta && $training == $param_CIL->val_char) || ($capability_consiglio && $training == $param_CIC->val_char))){
    	print_error('nopermissions', 'error', '', 'formazione_individuale');
    }

    echo $OUTPUT->header();
    //$context = context_system::instance();
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
        $$column = "<a href=\"user_copy_senza_determina.php?sort=$column&amp;dir=".$columndir."&training=".$training."&amp;id_course=".$id_course."&dato_ricercato=".$dato_ricercato."&page=".$page."&perpage=".$perpage."\">".$string[$column]."</a>$columnicon";
    }

    if ($sort == "name") {
        $sort = "firstname";
    }

    // create the user filter form
    $ufiltering = new my_users_filtering(null,"user_copy_senza_determina.php?training=".$training."&id_course=".$id_course);
    list($extrasql, $params) = $ufiltering->get_sql_filter();
    //$course_ind = get_corso_ind($id_course);
    $course_ind = get_corso_ind_senza_spesa($id_course);
    $extrasql = ' u.id <> '.$course_ind->userid;

    if(isset($_GET['dato_ricercato'])){
    	$users = $ufiltering->get_my_users_listing_cohort_ind($sort, $dir, $page*$perpage, $perpage, $dato_ricercato, '', '', $extrasql, $params);
    	$usercount = $ufiltering->get_count_my_users_listing_cohort_ind($sort, $dir, 0, 0, $dato_ricercato, '', '', $extrasql, $params);
    }else {
    	$users = "";
    	$usercount = 0;
    }
    echo $OUTPUT->heading(get_string('copia_corso_gratis', 'block_f2_formazione_individuale'));
    $strall = get_string('all');

    $baseurl = new moodle_url('/blocks/f2_formazione_individuale/user_copy_senza_determina.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage,'training' => $training,'id_course'=>$id_course,'dato_ricercato'=>$dato_ricercato));

    flush();

    if (!$users) {
        $match = array();
        // echo $OUTPUT->heading(get_string('nousersfound'));
        $table = NULL;
    } else {
        $override = new stdClass();
        $override->firstname = 'firstname';
        $override->lastname = 'lastname';
        $fullnamelanguage = get_string('fullnamedisplay', '', $override);
        if (($CFG->fullnamedisplay == 'firstname lastname') or
            ($CFG->fullnamedisplay == 'firstname') or
            ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'lastname firstname' )) {
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
            $fullname = $user->lastname." ".$user->firstname;

            $row = array ();
            $row[] = "<a href=\"modifica_anagrafica_senza_determina.php?userid=".$user->id."&training=".$training."&copy=1&id_course=".$id_course."\">$fullname</a>";
            $row[] = $user->idnumber;
            $row[] = $user->org_direzione;
            $table->data[] = $row;
        }
    }

    echo "<h5>".get_string('riepilogo_informazioni_corso', 'block_f2_formazione_individuale')."</h5><br>";
    echo '<table width="50%"  style="white-space:nowrap; text-align:left;" class="generaltable"><tr><th  style="text-align:left;" width="80%" class="header">'.get_string('titolo', 'block_f2_formazione_individuale').'</th><th  style="text-align:left;" class="header">'.get_string('datainizio', 'block_f2_formazione_individuale').'</th></tr>';
    echo '<tr><td>'.$course_ind->titolo.'</td><td>'.date('d/m/Y',$course_ind->data_inizio).'</td></tr></table>';
    echo "<br>";
    echo html_writer::start_tag('form', array('action' => $baseurl, 'method' => 'post'));
    // Submit ricerca
    echo '<table><tr>';
    echo '<td>Cognome: <input maxlength="254" size="50" name="dato_ricercato" type="text" id="id_dato_ricercato" value="'.$dato_ricercato.'" /></td>';
    echo '<td><input name="Cerca" value="Cerca" type="submit" id="id_Cerca" /></td>';
    echo '</tr></table>';
    echo html_writer::end_tag('form');
    if (!empty($table)) {
    	echo "<b style='font-size:11px'>".$usercount." ".get_string('users')."</b>";
    	echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);
        echo html_writer::table($table);
        echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);
    }
    echo '<input type="button" value="'.get_string("indietro", "block_f2_formazione_individuale").'" onclick="parent.location=\''.$gestionecorsi_url.'\'">';
    echo $OUTPUT->footer();
