<?php

    require_once('../../../config.php');
    require_once('filters/lib.php');
    require_once($CFG->dirroot.'/f2_lib/management.php');
    
    require_login();
    
    $sort         = optional_param('sort', 'name', PARAM_ALPHANUM);
    $dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
    $page         = optional_param('page', 0, PARAM_INT);
    $perpage      = optional_param('perpage', 10, PARAM_INT);        // how many per page
                
  //  $sitecontext = get_context_instance(CONTEXT_SYSTEM);
    $context = get_context_instance(CONTEXT_COURSE, 1);
  //  $PAGE->set_context($sitecontext);
    $PAGE->set_context($context);
    $site = get_site();
    $baseurl = new moodle_url('/local/f2_course/elenco_corsi_programmati/elenco_corsi_programmati.php');
    
    $PAGE->set_pagelayout('standard');
    $PAGE->set_url("/local/f2_course/elenco_corsi_programmati/elenco_corsi_programmati.php");
    $PAGE->set_title(get_string('corsi_programmati', 'local_f2_traduzioni', format_string(get_anno_formativo_corrente())));
    $PAGE->settingsnav;
    $PAGE->navbar->add(get_string('corsi_programmati', 'local_f2_traduzioni', format_string(get_anno_formativo_corrente())), $baseurl);
    $PAGE->set_heading($SITE->shortname);

    
    echo $OUTPUT->header();
    
    if (!has_capability('local/f2_course:f2_elencocorsi', $context)) {
    	print_error('nopermissions', 'error', '', 'consultare elenco corsi programmati');
    }
    /*
    if (!has_capability('local/f2_course:f2_elencocorsi', $sitecontext)) {
        print_error('nopermissions', 'error', '', 'consultare elenco corsi programmati');
    }
    */
    $sort = "fullname";

    // create the user filter form
    $cfiltering = new my_courses_filtering();
    list($extrasql, $params) = $cfiltering->get_sql_filter();
    
    $courses = $cfiltering->get_my_courses_listing($sort, $dir, $page*$perpage, $perpage, $extrasql, $params);
    $coursescount = $cfiltering->get_my_courses_count(false);
    $coursesearchcount = $cfiltering->get_my_courses_count(true, $extrasql, $params);

    if ($extrasql !== '') {
        echo $OUTPUT->heading("$coursesearchcount / $coursescount ".get_string('numero_corsi_programmati','local_f2_traduzioni'));
        $coursescount = $coursesearchcount;
    } else {
        echo $OUTPUT->heading($coursescount.' '.get_string('numero_corsi_programmati','local_f2_traduzioni'));
    }

    $baseurl = new moodle_url('/local/f2_course/elenco_corsi_programmati/elenco_corsi_programmati.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage));
    echo $OUTPUT->paging_bar($coursescount, $page, $perpage, $baseurl);

    flush();


    if (!$courses) {
        echo $OUTPUT->heading(get_string('nocoursesfound','local_f2_traduzioni'));
        $table = NULL;
    } else {
        $table = new html_table();
        $table->head = array ();
        $table->align = array();
        $table->head[] = get_string('fullname');
        $table->align[] = 'left';
        $table->head[] = get_string('idnumber');
        $table->align[] = 'left';
        $table->head[] = get_string('summary');
        $table->align[] = 'left';
        $table->head[] = get_string('schedaprogetto','local_f2_traduzioni');
        $table->align[] = 'left';

        $table->width = "95%";
        foreach ($courses as $course) {
            $row = array ();
            $row[] = $course->fullname;
            $row[] = $course->idnumber;
            if (strlen($course->summary) > 180)
                $etc = ' (...)';
            else
                $etc = '';
            $row[] = substr($course->summary, 0, 180).$etc;
            $url = get_url_scheda_progetto($course->id);
            if ($url) {
                $row[] = "<a href=\"$url\"><img src=\"{$CFG->wwwroot}/pix/f/pdf.gif\" class=\"icon\" title=\"Scarica scheda progetto\" /></a> ";
            } else {
                $row[] = "";
            }
            $table->data[] = $row;
        }
    }

    // add filters
    $cfiltering->display_add();
    $cfiltering->display_active();

    if (!empty($table)) {
        echo html_writer::table($table);
        echo $OUTPUT->paging_bar($coursescount, $page, $perpage, $baseurl);
    }

    echo $OUTPUT->footer();



