<?php //$Id: index.php 1294 2014-07-04 09:47:00Z l.moretto $

    // Lists all items in a given hierarchy's framework

    global $CFG, $PAGE, $DB;

    require_once('../config.php');
    require_once($CFG->libdir.'/adminlib.php');
    require_once($CFG->libdir.'/tablelib.php');
    require_once($CFG->dirroot.'/hierarchy/lib.php');
    require_once($CFG->dirroot.'/hierarchy/filters/lib.php');
    require_once($CFG->dirroot.'/hierarchy/show_options_form.php');
    require_once($CFG->dirroot.'/hierarchy/download_form.php');
    
    // inizio import per generazione albero //
    $PAGE->requires->js('/f2_lib/jquery/jquery-1.7.1.min.js');
    $PAGE->requires->js('/f2_lib/jquery/jquery-ui.min.js');
    $PAGE->requires->js('/f2_lib/jquery/jquery.cookie.js');
    $PAGE->requires->js('/f2_lib/jquery/jquery.dynatree.js');
    $PAGE->requires->css('/f2_lib/jquery/css/skin/ui.dynatree.css');
    $PAGE->requires->js('/f2_lib/jquery/jquery.blockUI.js');
    // fine import per generazione albero //

    define('DEFAULT_PAGE_SIZE', 20);
    define('SHOW_ALL_PAGE_SIZE', 5000);

    //$sitecontext    = get_context_instance(CONTEXT_SYSTEM);
    $sitecontext    = context_system::instance();
    $PAGE->set_context($sitecontext);
    $PAGE->set_pagelayout('standard');

    /// Hierarchy type, framework params
    $type           = required_param('type', PARAM_ALPHA);
    $shortprefix = hierarchy::get_short_prefix($type);
    $frameworkid    = optional_param('frameworkid', 0, PARAM_INT);
    // Page display params
    $spage          = optional_param('spage', 0, PARAM_INT);                     // which page to show
    $perpage        = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT);  // how many per page

    // Editing params
    $edit        = optional_param('edit', -1, PARAM_BOOL);
    $hide        = optional_param('hide', 0, PARAM_INT);
    $show        = optional_param('show', 0, PARAM_INT);
    $moveup      = optional_param('moveup', 0, PARAM_INT);
    $movedown    = optional_param('movedown', 0, PARAM_INT);

    // Table display params
    $showoptions       = optional_param('showoptions', null, PARAM_BOOL);
    $hidecustomfields  = optional_param('hidecustomfields', null, PARAM_BOOL);
    $showitemfullname  = optional_param('showitemfullname', null, PARAM_BOOL);
    $showdepthfullname = optional_param('showdepthfullname', null, PARAM_BOOL);
    
    $PAGE->set_url(new moodle_url("{$CFG->wwwroot}/hierarchy/index.php"));

    // Confirm the type exists
    if (file_exists($CFG->dirroot.'/hierarchy/type/'.$type.'/lib.php')) {
        require_once($CFG->dirroot.'/hierarchy/type/'.$type.'/lib.php');
    } else {
        print_error('error:hierarchytypenotfound', 'hierarchy', $CFG->wwwroot, $type, 'hierarchy');
    }

    $hierarchy = new $type();

    $str_edit         = get_string('edit');
    $str_delete       = get_string('delete');
    $str_moveup       = get_string('moveup');
    $str_movedown     = get_string('movedown');
    $str_hide         = get_string('hide');
    $str_show         = get_string('show');
    $str_customfields = get_string('customfields', 'local_f2_domains');
    $str_spacer       = "<img src=\"{$CFG->wwwroot}/pix/spacer.gif\" class=\"iconsmall\" alt=\"\" /> ";

    // Cache user capabilities
    $can_add_item    = has_capability('local/f2_domains:create'.$type, $sitecontext);
    $can_edit_item   = has_capability('local/f2_domains:update'.$type, $sitecontext);
    $can_delete_item = has_capability('local/f2_domains:delete'.$type, $sitecontext);
    $can_add_depth   = has_capability('local/f2_domains:create'.$type.'depth', $sitecontext);
    $can_edit_depth  = has_capability('local/f2_domains:update'.$type.'depth', $sitecontext);
    $can_delete_depth = has_capability('local/f2_domains:delete'.$type.'depth', $sitecontext);

    // Load framework
    $framework   = $hierarchy->get_framework($frameworkid, true);

    // If no frameworks exist
    if (!$framework) {
        // Redirect to frameworks page
        redirect($CFG->wwwroot.'/hierarchy/framework/index.php?type='.$type);
        exit();
    }

    $frameworkid = $framework->id;

    // Display editing button in navbar
    if ($can_edit_item || $can_delete_item || $can_add_depth || $can_edit_depth || $can_delete_depth) {
        $options = array('type' => $type, 'frameworkid' => $frameworkid, 'spage' => $spage);
        $navbaritem = $hierarchy->get_editing_button($edit, $options);
        
//        $editingon = !empty($USER->{$type.'editing'});
        $editingon = true;
    } else {
        $editingon = false;
        $navbaritem = '';
    }
    
    // Setup page and check permissions
    admin_externalpage_setup($type.'manage', $navbaritem, array('type'=>$type));

    // Build return url path
    $returnurl = "{$CFG->wwwroot}/hierarchy/index.php";
    $returnurlAddFilter = "{$CFG->wwwroot}/hierarchy/index.php";
    $urlparams = array();
    $urlparams[] = "type=$type";
    if($frameworkid != 0) {
        $urlparams[] = "frameworkid=$frameworkid";
    }
    if(count($urlparams) > 0) {
        $returnurlAddFilter .= '?'.implode('&amp;', $urlparams);
    }
    if($spage != 0) {
        $urlparams[] = "spage=$spage";
    }
    if(count($urlparams) > 0) {
        $returnurl .= '?'.implode('&amp;', $urlparams);
    }

    // Get the framework depths
    $depths = $hierarchy->get_depths($frameworkid);
    
    // Display page
    echo $OUTPUT->header();
    
    // If no depths, add depth form
    if (!$depths) {
//        echo $OUTPUT->header();
        
        $hierarchy->display_framework_selector();

        echo $OUTPUT->heading(get_string('nodepthlevels', 'local_f2_domains'));

        // Print button to add a depth level
        if ($can_add_depth) {
            echo '<div class="buttons">';

            $options = array('type' => $type, 'frameworkid' => $frameworkid);
            echo $OUTPUT->single_button(new moodle_url($CFG->wwwroot.'/hierarchy/depth/edit.php', $options), get_string('adddepthlevel', 'local_f2_domains'), 'get');

            echo '</div>';
        }

        echo $OUTPUT->footer();
        exit();
    }

    ///
    /// Get database info
    ///
    // create the filter form (we need the extrasql snippet for query)
    $showfullsearch = true;
    $filtering = new hierarchy_filtering($type, null, htmlspecialchars_decode($returnurlAddFilter), null, $showfullsearch);
    $extrasql = $filtering->get_sql_filter();
    if ($extrasql !== '') {
        $extrasql = ' AND '.$extrasql;
    }
    
    if ($extrasql !== '') {
        $extrasql = $filtering->findsubtreeSqlReplacement($extrasql);
        $extrasql = $filtering->finddirezioneSqlReplacement($extrasql);
    }
    
    // build the query to get the items
    // not actually called until further down but need sql for the count
    $select = "SELECT id, depthid, parentid, shortname, fullname, visible, sortorder";
    if(!empty($hierarchy->extrafields)) {
        $select .= ', ' . implode(', ', $hierarchy->extrafields);
    }
    $from   = " FROM {$CFG->prefix}{$shortprefix}";
    $where  = " WHERE frameworkid={$framework->id}";
    $order  = " ORDER BY sortorder"; // non rimuovere: viene usato in get_download_data.php

    // figure out how many matches there are
    $filteredmatchcount = $filtering->get_my_hierarchy_listing_count($where, $extrasql);
    if ($extrasql !== '') {
        $matchcount = $DB->count_records_sql('SELECT COUNT(DISTINCT id) '.$from.$where);
    } else {
        $matchcount = $filteredmatchcount;
    }

    // second query to get custom field fields
    $sql = "SELECT cdf.id,cdf.shortname,cdf.fullname,cdf.depthid, cdf.id AS fieldid
        FROM {$CFG->prefix}{$shortprefix}_depth_info_field cdf join {$CFG->prefix}{$shortprefix}_depth cd ON cdf.depthid=cd.id
        WHERE cd.frameworkid = {$framework->id} AND cdf.hidden = 0
        ORDER BY cdf.categoryid, cdf.sortorder";
    $customfieldcols = $DB->get_records_sql($sql);

    // get the sort order range (min/max) for this framework
    // used to work out if sorting arrows are needed
    $max = $DB->get_record_sql("SELECT MAX(sortorder) AS sortorder FROM {$CFG->prefix}{$shortprefix} WHERE frameworkid={$framework->id}");
    $sortmax = $max ? $max->sortorder : null;
//    // hack because there is no sql_min(). Just get first record, sorted by sortorder
//    $min = $DB->get_record_sql("SELECT sortorder FROM {$CFG->prefix}{$shortprefix} WHERE frameworkid={$framework->id} ORDER BY sortorder ASC");
    $min = $DB->get_record_sql("SELECT MIN(sortorder) AS sortorder FROM {$CFG->prefix}{$shortprefix} WHERE frameworkid={$framework->id}");
    $sortmin = $min ? $min->sortorder : null;

    // third query to get custom field data
    // these are split because of the way moodle aggregates data by id
    // if we use field id as the key we only get one data item per field, if we use competency id
    // we don't get any rows for fields without any data
    $sql = "SELECT cdd.id, cdd.{$type}id AS itemid, cdf.depthid, cdf.id AS fieldid,cdd.data
        FROM {$CFG->prefix}{$shortprefix}_depth_info_field cdf
        JOIN {$CFG->prefix}{$shortprefix}_depth cd ON cdf.depthid=cd.id
        LEFT JOIN {$CFG->prefix}{$shortprefix}_depth_info_data cdd ON cdf.id=cdd.fieldid
        WHERE cd.frameworkid = {$framework->id} AND cdf.hidden = 0";

    $customfielddata = $DB->get_records_sql($sql);
    // remove any records with no cdd.id set (fields without values)
    unset($customfielddata['']);

    ///
    /// Process any actions
    ///
    if ($editingon) {
        require_capability('local/f2_domains:update'.$type, $sitecontext);

        // Hide an item
        if ($hide) {
            $hierarchy->hide_item($hide);
        } elseif ($show) {
           $hierarchy->show_item($show);
        } elseif ($moveup) {
            $hierarchy->move_item($moveup, true);
        } elseif ($movedown) {
            $hierarchy->move_item($movedown, false);
        }
    } // End of editing stuff


    // Display options form 
    $display_options = new hierarchy_show_options_form(null, compact('framework','spage', 'type'));

    if ($display_options->is_cancelled()) {
        redirect($returnurl);
    } else if ($fromform = $display_options->get_data()) {
        if (empty($fromform->submitbutton)) {
            print_error('unknownbuttonclicked', $type, $returnurl);
        }

        $todb = new object();
        if(!isset($fromform->hidecustomfields)) {
            $fromform->hidecustomfields = 0;
        }
        $todb->hidecustomfields = $fromform->hidecustomfields;

        if(!isset($fromform->showitemfullname)) {
            $fromform->showitemfullname = 0;
        }
        $todb->showitemfullname = $fromform->showitemfullname;

        if(!isset($fromform->showdepthfullname)) {
            $fromform->showdepthfullname = 0;
        }
        $todb->showdepthfullname = $fromform->showdepthfullname;
        $todb->id = $fromform->frameworkid; 

        if(!$DB->update_record($shortprefix.'_framework', $todb)) {
            print_error('cannotupdatedisplaysettings', $type, $returnurl);
        }
        redirect($returnurl);
    } else {
        $toform = new object();
        $toform->hidecustomfields = $framework->hidecustomfields;
        $toform->showitemfullname = $framework->showitemfullname;
        $toform->showdepthfullname = $framework->showdepthfullname;
        $display_options->set_data($toform);
    }
    
    // Add filters
    $filtering->display_add();
    $filtering->display_active();
    
    //AK-LM START: display matchcount, download link, display options.
    $hierarchy->display_framework_selector();

    if ($extrasql !== '') {
        echo $OUTPUT->heading("$filteredmatchcount / $matchcount ".get_string('featureplural', 'local_f2_domains'));
    } else {
        echo $OUTPUT->heading("$matchcount ".get_string('featureplural', 'local_f2_domains'));
    }
    if ($matchcount == 0) {
        echo "<i>".get_string('no'.$type, 'local_f2_domains')."</i><br><br>";
    } else {
//        $download->display();
        echo html_writer::start_tag('a', array('href' => $CFG->wwwroot.'/hierarchy/download.php?frameworkid='.$frameworkid.'&type='.$type));
		echo html_writer::empty_tag('img', array('src' => $CFG->wwwroot.'/blocks/f2_apprendimento/pix/excel_icon1.png', 'height' => '25', 'width' => '25', 'alt' => 'Esporta', 'title' => get_string('export', 'local_f2_domains')));
        echo '&nbsp;Esporta domini';
		echo html_writer::end_tag('a');
        echo html_writer::empty_tag('br');
    }
    
    // Show options form or link
    if($showoptions) {
        $display_options->display();
    } else {
        if (strpos($returnurl, '?') !== false) {
            $showurl = "{$returnurl}&amp;showoptions=1";
        } else {
            $showurl = "{$returnurl}?showoptions=1";
        }
        print "<p><a href=\"$showurl\">".get_string('showdisplayoptions', 'local_f2_domains').'</a></p>';
    }
    //AK-LM STOP: display matchcount, download link, display options.

    //
    // Prepare table info
    //

    // build the header column from depth information
    $myhead = array();
    foreach($depths as $depth) {
        $row = new object();
        $row->type = 'depth';
        $row->value = $depth;
        $myhead[] = $row;
        if (!$framework->hidecustomfields && isset($customfieldcols) && is_array($customfieldcols)) {
            foreach ($customfieldcols AS $customfield) {
                if ($depth->id == $customfield->depthid) {
                    $row = new object();
                    $row->type = 'custom';
                    $row->value = $customfield;
                    $myhead[] = $row;
                }
            }
        }
    }

    // show settings column
    if ($editingon) {
        $row = new object();
        $row->type = 'settings';
        $row->value->fullname = get_string('settings');
        $myhead[] = $row;
    }

    // show any extra columns
    if (!empty($hierarchy->extrafields)) {
        foreach($hierarchy->extrafields as $extrafield) {
            $row = new object();
            $row->type = 'extrafield';
            $row->extrafield = $extrafield;
            $row->value->fullname = get_string($extrafield, $type);
            $myhead[] = $row;
        }
    }


    // display options
    $displaydepth = ($framework->showdepthfullname) ? 'fullname' : 'shortname';
    $displayitem = ($framework->showitemfullname) ? 'fullname' : 'shortname';

    $table_cols = array();
    $table_cols_cf = array();
    $table_cols_ef = array();
    $table_data = array();

    // build header row
    foreach($myhead AS $key => $head) {
//        var_dump($head);
        if ($head->type == 'depth') {
            // depth level header
            $header = $head->value->$displaydepth;

            $header .= "<div class=\"options\">";
            if ($editingon && $can_edit_depth) {
                $header .= " <a href=\"{$CFG->wwwroot}/hierarchy/depth/edit.php?type={$type}&amp;id={$head->value->id}\"
                    title=\"$str_edit\">".
                    //"<img src=\"{$CFG->wwwroot}/pix/t/edit.gif\" class=\"iconsmall\" alt=\"$str_edit\" /></a>";
                    "<img src=\"{$CFG->wwwroot}/pix/t/edit.png\" class=\"iconsmall\" alt=\"$str_edit\" /></a>";
//                    ."<a href=\"{$CFG->wwwroot}/customfield/index.php?type={$type}&amp;subtype=depth&amp;depthid={$head->value->id}\"
//                    title=\"$str_customfields\">".
//                    "<img src=\"{$CFG->wwwroot}/pix/t/customfields.gif\" class=\"iconsmall\" alt=\"$str_customfields\" /></a> ";
            }
            if ($editingon && $can_delete_depth) {
                $header .= "<a href=\"{$CFG->wwwroot}/hierarchy/depth/delete.php?type={$type}&amp;id={$head->value->id}\"
                    title=\"$str_delete\">".
                    //"<img src=\"{$CFG->wwwroot}/pix/t/delete.gif\" class=\"iconsmall\" alt=\"$str_delete\" /></a>";
                    "<img src=\"{$CFG->wwwroot}/pix/t/delete.png\" class=\"iconsmall\" alt=\"$str_delete\" /></a>";
            }
            $header .= "</div>";
            $table_cols[] = $head->value->fullname.$key;
            $table_header[] = $header;

        } else if ($head->type == 'custom') {
            // custom field header
            $header = $head->value->$displaydepth;

            if ($editingon && $can_edit_depth) {
                $header .= ' <a title="'.$str_edit.'" href="'.$CFG->wwwroot.'/customfield/index.php?type='.$type.'&amp;subtype=depth&amp;id='.$head->value->id.'&amp;action=editfield"><img src="'.$CFG->pixpath.'/t/edit.png" alt="'.$str_edit.'" class="iconsmall" /></a>';
            }
            $table_cols[] = $head->value->fullname.$key;
            $table_header[] = $header;
            $table_cols_cf[]= $head->value->fullname.$key; // keep track of custom field headers for styling below
        } else if ($head->type == 'extrafield') {
            // extrafield header
            $table_cols[] = $head->value->fullname.$key;
            $table_header[] = $head->value->fullname;
            $table_cols_ef[]= $head->value->fullname.$key; // keep track of extra field headers for styling below
        } else {
            // settings header
            $table_cols[] = $head->value->fullname.$key;
            $table_header[] = $head->value->fullname;
        }
    }
    $table = new flexible_table($type.'-framework-index-'.$frameworkid);

    $table->define_baseurl($PAGE->url);
    $table->define_columns($table_cols);
    $table->define_headers($table_header);
    // label custom field columns for styling
    foreach ($table_cols as $table_col) {
        if(in_array($table_col, $table_cols_cf)) {
            $table->column_class($table_col,'customfield');
        } elseif(in_array($table_col, $table_cols_ef)) {
            $table->column_class($table_col,'extrafield');
        }
    }
    $table->column_style('Settings','width','80px');
    $table->set_attribute('cellspacing', '0');
    $table->set_attribute('id', $type);
    $table->set_attribute('class', 'generaltable generalbox');
    $table->set_attribute('width', '100%');

    $table->set_control_variables(array(
                TABLE_VAR_SORT    => 'ssort',
                TABLE_VAR_HIDE    => 'shide',
                TABLE_VAR_SHOW    => 'sshow',
                TABLE_VAR_IFIRST  => 'sifirst',
                TABLE_VAR_ILAST   => 'silast',
                TABLE_VAR_PAGE    => 'spage'
                ));

    $table->initialbars(true);


    $table->pagesize($perpage, $filteredmatchcount);
    
    $table->setup();
    
    $limit = ' LIMIT '.$table->get_page_start().', '.$table->get_page_size();
    
    $itemlist = $filtering->get_my_hierarchy_listing($select, $where, $extrasql, $limit);

    // loop round data rows
    $i = 0;
    if (isset($itemlist) && is_array($itemlist)) {
        foreach($itemlist AS $rowid => $item) {
            $table_data[$i] = array();
            // loop round columns
            $j = 0;
            foreach($myhead AS $head) {
                if ($head->type == 'depth') {
                    if ($item->depthid == $head->value->id) {
                        $cssclass = !$item->visible ? 'class="dimmed"' : '';
                        $cell = "<a $cssclass href=\"{$CFG->wwwroot}/hierarchy/item/view.php?type={$type}&amp;id={$item->id}\">{$item->$displayitem}</a>";
                        $table_data[$i][$j] = $cell;
                    }
                }
                if ($head->type == 'custom') {
                    // check each custom field
                    foreach($customfielddata AS $customfield) {
                        if ($customfield->fieldid == $head->value->fieldid && $customfield->itemid == $rowid) {
                            $table_data[$i][$j] = $customfield->data;
                        }
                    }
                }
                if ($head->type == 'extrafield') {
                    foreach($hierarchy->extrafields as $extrafield) {
                        if($head->extrafield == $extrafield) {
                            $table_data[$i][$j] = "<a href=\"{$CFG->wwwroot}/hierarchy/item/view.php?type={$type}&amp;id={$item->id}\">{$item->$extrafield}</a>";

                        }
                    }
                }
                if ($head->type == 'settings') {
                // Add edit and delete buttons
                    if ($editingon) {
                        $buttons = array();
                        if ($can_edit_item) {
                            $buttons[] = "<a href=\"{$CFG->wwwroot}/hierarchy/item/edit.php?type={$type}&amp;frameworkid={$frameworkid}&amp;id={$item->id}\" title=\"$str_edit\">".
                                "<img src=\"{$CFG->wwwroot}/pix/t/edit.png\" class=\"iconsmall\" alt=\"$str_edit\" /></a>";

                            if ($item->visible) {
                                $buttons[] = "<a href=\"{$CFG->wwwroot}/hierarchy/index.php?type={$type}&amp;spage={$spage}&amp;frameworkid={$frameworkid}&amp;hide={$item->id}\" title=\"$str_hide\">".
                                    "<img src=\"{$CFG->wwwroot}/pix/t/hide.png\" class=\"iconsmall\" alt=\"$str_hide\" /></a>";
                            } else {
                                $buttons[] = "<a href=\"{$CFG->wwwroot}/hierarchy/index.php?type={$type}&amp;spage={$spage}&amp;frameworkid={$frameworkid}&amp;show={$item->id}\" title=\"$str_show\">".
                                    "<img src=\"{$CFG->wwwroot}/pix/t/show.png\" class=\"iconsmall\" alt=\"$str_show\" /></a>";
                            }
                        }
                        if ($can_delete_item) {
                            $buttons[] = "<a href=\"{$CFG->wwwroot}/hierarchy/item/delete.php?type={$type}&amp;spage={$spage}&amp;frameworkid={$frameworkid}&amp;id={$item->id}&amp;spage={$spage}\" title=\"$str_delete\">".
                                "<img src=\"{$CFG->wwwroot}/pix/t/delete.png\" class=\"iconsmall\" alt=\"$str_delete\" /></a>";
                        }
                        if ($hierarchy->get_item_adjacent_peer($item, true)) {
                            $buttons[] = "<a href=\"index.php?type={$type}&amp;frameworkid={$frameworkid}&amp;spage={$spage}&amp;moveup={$item->id}\" title=\"$str_moveup\">".
                                "<img src=\"{$CFG->wwwroot}/pix/t/up.png\" class=\"iconsmall\" alt=\"$str_moveup\" /></a> ";
                        } else {
                           $buttons[] = $str_spacer;
                        }
                        if ($hierarchy->get_item_adjacent_peer($item, false)) {
                            $buttons[] = "<a href=\"index.php?type={$type}&amp;frameworkid={$frameworkid}&amp;spage={$spage}&amp;movedown=".$item->id."\" title=\"$str_movedown\">".
                                "<img src=\"{$CFG->wwwroot}/pix/t/down.png\" class=\"iconsmall\" alt=\"$str_movedown\" /></a> ";
                        }
                    }
                    $table_data[$i][$j] = implode($buttons, ' ');

                }
                // if nothing set for this cell, fill with a dummy value
                if (!isset($table_data[$i][$j])) {
                    $table_data[$i][$j] = '';
                }
                $j++;
            }
            $table->add_data($table_data[$i]);
            $i++;
        }
    } else {
        // dummy row so table headings are shown
        $table->add_data(array());
    }

    // Download form
//    $download = new hierarchy_download_form(null, array('type'=>$type,'frameworkid'=>$frameworkid));
//    if ($fromform = $download->get_data()) {
        require_once($CFG->dirroot.'/hierarchy/get_download_data.php');
//        redirect($CFG->wwwroot.'/hierarchy/download.php?type='.$type);
//    }
    

//    ///
//    /// Generate / display page
//    ///
//    echo $OUTPUT->header();




    // Display table
    $table->finish_output();
    


    // Editing buttons
    echo '<div class="buttons">';
    if ($can_add_item) {
        $hierarchy->display_add_item_button($spage);
    }
    if ($can_add_depth) {
        $hierarchy->display_add_depth_button($spage);
    }
    echo '</div>';

    echo $OUTPUT->footer();

