<?php

    require_once('../../../config.php');
    require_once($CFG->libdir.'/adminlib.php');
    require_once('filters/lib.php');
    require_once($CFG->dirroot.'/f2_lib/management.php');
    
    require_once($CFG->dirroot.'/user/profile/lib.php');
    require_once($CFG->dirroot.'/tag/lib.php');
    require_once($CFG->libdir . '/filelib.php');
    
    if (is_null(get_root_framework())) {
        // If no frameworks exist
        // Redirect to frameworks page
        redirect($CFG->wwwroot.'/hierarchy/framework/index.php?type=organisation');
        exit();
    }
                
    // inizio import per generazione albero //
    $PAGE->requires->js('/f2_lib/jquery/jquery-1.7.1.min.js');
    $PAGE->requires->js('/f2_lib/jquery/jquery-ui.min.js');
    $PAGE->requires->js('/f2_lib/jquery/jquery.cookie.js');
    $PAGE->requires->js('/f2_lib/jquery/jquery.dynatree.js');
    $PAGE->requires->js('/f2_lib/jquery/jquery.blockUI.js');
    $PAGE->requires->css('/f2_lib/jquery/css/skin/ui.dynatree.css');
    // fine import per generazione albero //
    
    $sort         = optional_param('sort', 'name', PARAM_ALPHANUM);
    $dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
    $page         = optional_param('page', 0, PARAM_INT);
    $perpage      = optional_param('perpage', 30, PARAM_INT);        // how many per page
    $suspend      = optional_param('suspend', 0, PARAM_INT);
    $unsuspend    = optional_param('unsuspend', 0, PARAM_INT);

//    admin_externalpage_setup('editusers');

    $sitecontext = get_context_instance(CONTEXT_SYSTEM);
    $PAGE->set_context($sitecontext);
    $PAGE->set_pagelayout('standard');
    $site = get_site();

    $PAGE->set_url(new moodle_url("{$CFG->wwwroot}/local/f2_domains/assignments/user.php"));
    
    if (!has_capability('local/f2_domains:assignorganisation', $sitecontext)) {
        print_error('nopermissions', 'error', '', 'assign organisation to users');
    }

    if (empty($CFG->loginhttps)) {
        $securewwwroot = $CFG->wwwroot;
    } else {
        $securewwwroot = str_replace('http:','https:',$CFG->wwwroot);
    }

    $returnurl = new moodle_url('/local/f2_domains/assignments/user.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage, 'page'=>$page));

    if ($suspend and confirm_sesskey()) {
        require_capability('moodle/user:update', $sitecontext);

        if ($user = $DB->get_record('user', array('id'=>$suspend, 'mnethostid'=>$CFG->mnet_localhost_id, 'deleted'=>0))) {
            if (!is_siteadmin($user) and $USER->id != $user->id and $user->suspended != 1) {
                $user->suspended = 1;
                $user->timemodified = time();
                $DB->set_field('user', 'suspended', $user->suspended, array('id'=>$user->id));
                $DB->set_field('user', 'timemodified', $user->timemodified, array('id'=>$user->id));
                // force logout
                session_kill_user($user->id);
                events_trigger('user_updated', $user);
            }
        }
        redirect($returnurl);

    } else if ($unsuspend and confirm_sesskey()) {
        require_capability('moodle/user:update', $sitecontext);

        if ($user = $DB->get_record('user', array('id'=>$unsuspend, 'mnethostid'=>$CFG->mnet_localhost_id, 'deleted'=>0))) {
            if ($user->suspended != 0) {
                $user->suspended = 0;
                $user->timemodified = time();
                $DB->set_field('user', 'suspended', $user->suspended, array('id'=>$user->id));
                $DB->set_field('user', 'timemodified', $user->timemodified, array('id'=>$user->id));
                events_trigger('user_updated', $user);
            }
        }
        redirect($returnurl);
    }

    echo $OUTPUT->header();
    
    // Carry on with the user listing
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
        $$column = "<a href=\"user.php?sort=$column&amp;dir=$columndir\">".$string[$column]."</a>$columnicon";
    }

    if ($sort == "name") {
        $sort = "firstname";
    }

    // create the user filter form
    $ufiltering = new my_users_filtering();
    list($extrasql, $params) = $ufiltering->get_sql_filter();
    $users = $ufiltering->get_my_users_listing($sort, $dir, $page*$perpage, $perpage, '', '', '',
            $extrasql, $params, $context);
    $usercount = $ufiltering->get_my_users_count(false);
    $usersearchcount = $ufiltering->get_my_users_count(true, false, '', $extrasql, $params);

    if ($extrasql !== '') {
        echo $OUTPUT->heading("$usersearchcount / $usercount ".get_string('users'));
        $usercount = $usersearchcount;
    } else {
        echo $OUTPUT->heading("$usercount ".get_string('users'));
    }

    $strall = get_string('all');

    $baseurl = new moodle_url('/local/f2_domains/assignments/user.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage));
    echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);

    flush();


    if (!$users) {
        $match = array();
        echo $OUTPUT->heading(get_string('nousersfound'));

        $table = NULL;

    } else {

        $countries = get_string_manager()->get_list_of_countries(false);
        if (empty($mnethosts)) {
            $mnethosts = $DB->get_records('mnet_host', null, 'id', 'id,wwwroot,name');
        }

        foreach ($users as $key => $user) {
            if (isset($countries[$user->country])) {
                $users[$key]->country = $countries[$user->country];
            }
        }
        if ($sort == "country") {  // Need to resort by full country name, not code
            foreach ($users as $user) {
                $susers[$user->id] = $user->country;
            }
            asort($susers);
            foreach ($susers as $key => $value) {
                $nusers[] = $users[$key];
            }
            $users = $nusers;
        }

        $override = new stdClass();
        $override->firstname = 'firstname';
        $override->lastname = 'lastname';
        $fullnamelanguage = get_string('fullnamedisplay', '', $override);
        if (($CFG->fullnamedisplay == 'firstname lastname') or
            ($CFG->fullnamedisplay == 'firstname') or
            ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'firstname lastname' )) {
            $fullnamedisplay = "$firstname / $lastname";
        } else { // ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'lastname firstname')
            $fullnamedisplay = "$lastname / $firstname";
        }

        $table = new html_table();
        $table->head = array ();
        $table->align = array();
        $table->head[] = $fullnamedisplay;
        $table->align[] = 'left';
        foreach ($extracolumns as $field) {
            $table->head[] = ${$field};
            $table->align[] = 'left';
        }
//       $table->head[] = get_string('idnumber');
//       $table->align[] = 'left';
        $table->head[] = get_string('organisation', 'local_f2_domains');
        $table->align[] = 'left';
        $table->head[] = get_string('viewable_organisation', 'local_f2_domains');
        $table->align[] = 'left';

        $table->width = "95%";
        foreach ($users as $user) {
            if (isguestuser($user)) {
                continue; // do not display guest here
            }

            $fullname = fullname($user, true);

            $row = array ();
            $row[] = "<a href=\"view.php?userid=$user->id\">$fullname</a>";
            foreach ($extracolumns as $field) {
                $row[] = $user->{$field};
            }
        //    $row[] = $user->idnumber;
        if($user->shortname){
            $row[] = $user->shortname." - ".$user->org_fullname;
        }else
        	$row[]="";
        if($user->viewable_org_shortname){
            $row[] = $user->viewable_org_shortname." - ".$user->viewable_org_fullname;
        }else
        	$row[]="";
            $table->data[] = $row;
        }
    }

    // add filters
    $ufiltering->display_add();
    $ufiltering->display_active();

    if (!empty($table)) {
        echo html_writer::table($table);
        echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);
    }

    echo $OUTPUT->footer();



