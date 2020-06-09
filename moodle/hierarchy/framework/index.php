<?php

    require_once('../../config.php');
    require_once($CFG->libdir.'/adminlib.php');
    require_once($CFG->dirroot.'/hierarchy/lib.php');

    ///
    /// Setup / loading data
    ///

    $sitecontext = get_context_instance(CONTEXT_SYSTEM);
    $PAGE->set_context($sitecontext);
    $PAGE->set_pagelayout('standard');

    // Get params
    $type        = optional_param('type', -1, PARAM_SAFEDIR);
    $edit        = optional_param('edit', -1, PARAM_BOOL);
    $hide        = optional_param('hide', 0, PARAM_INT);
    $show        = optional_param('show', 0, PARAM_INT);
    $moveup      = optional_param('moveup', 0, PARAM_INT);
    $movedown    = optional_param('movedown', 0, PARAM_INT);

    if (file_exists($CFG->dirroot.'/hierarchy/type/'.$type.'/lib.php')) {
        require_once($CFG->dirroot.'/hierarchy/type/'.$type.'/lib.php');
        $hierarchy = new $type();
    } else {
        error('error:hierarchytypenotfound', 'hierarchy', $type);
    }

    // Cache user capabilities
    $can_add = has_capability('local/f2_domains:create'.$type.'frameworks', $sitecontext);
    $can_edit = has_capability('local/f2_domains:update'.$type.'frameworks', $sitecontext);
    $can_delete = has_capability('local/f2_domains:delete'.$type.'frameworks', $sitecontext);

    if ($can_add || $can_edit || $can_delete) {
        if (empty($edit)) {
            $label = get_string('turneditingon');
        } else {
            $label = get_string('turneditingoff');
        }
        $navbaritem = $OUTPUT->single_button($edit, $label);
//        $editingon = !empty($USER->{$type.'editing'});
        $editingon = true;
    } else {
        $navbaritem = '';
        $editingon = false;
    }

    // Setup page and check permissions
    admin_externalpage_setup($type.'frameworkmanage', $navbaritem, array('type'=>$type));

    ///
    /// Process any actions
    ///

    if ($editingon) {
        // Hide or show a framework
        if ($hide or $show or $moveup or $movedown) {
            require_capability('local/f2_domains:update'.$type.'frameworks', $sitecontext);
            // Hide an item
            if ($hide) {
                $hierarchy->hide_framework($hide);
            } elseif ($show) {
                $hierarchy->show_framework($show);
            } elseif ($moveup) {
                $hierarchy->move_framework($moveup, true);
            } elseif ($movedown) {
                $hierarchy->move_framework($movedown, false);
            }
        }

    } // End of editing stuff

///
/// Load hierarchy frameworks after any changes
///

// Get frameworks for this page
$frameworks = $hierarchy->get_frameworks(array('depth_count'=>1, 'custom_field_count'=>1, 'item_count'=>1));

///
/// Generate / display page
///
$str_edit     = get_string('edit');
$str_delete   = get_string('delete');
$str_moveup   = get_string('moveup');
$str_movedown = get_string('movedown');
$str_hide     = get_string('hide');
$str_show     = get_string('show');

if ($frameworks) {

    // Create display table
    $table = new html_table();
    $table->head  = array(get_string('name', 'local_f2_domains'), get_string($type.'plural', 'local_f2_domains'), get_string('depths', 'local_f2_domains'),
        get_string("{$type}customfields", 'local_f2_domains'));
    $table->align = array('left', 'left', 'center', 'left', 'left');
    $table->width = '100%';
    
    // Setup column headers
    $table->head = array(get_string('name', 'local_f2_domains'), get_string($type.'plural', 'local_f2_domains'), get_string('depths', 'local_f2_domains'),
        get_string("{$type}customfields", 'local_f2_domains'));

    // Add edit column
    if ($editingon && $can_edit) {
        $table->head[] = get_string('edit');
    }

    // Add rows to table
    $rowcount = 1;
    foreach ($frameworks as $framework) {
        $row = array();

        $cssclass = !$framework->visible ? 'class="dimmed"' : '';

        $row[] = "<a $cssclass href=\"{$CFG->wwwroot}/hierarchy/framework/view.php?type={$type}&frameworkid={$framework->id}\">{$framework->fullname}</a>";
        $row[] = $framework->item_count;
        $row[] = $framework->depth_count;
        $row[] = $framework->custom_field_count;

        // Add edit link
        $buttons = array();
        if ($editingon && $can_edit) {
            $buttons[] = "<a href=\"{$CFG->wwwroot}/hierarchy/framework/edit.php?type={$type}&id={$framework->id}\" title=\"$str_edit\">".
                "<img src=\"{$CFG->wwwroot}/pix/t/edit.gif\" class=\"iconsmall\" alt=\"$str_edit\" /></a>";
            if ($framework->visible) {
                $buttons[] = "<a href=\"{$CFG->wwwroot}/hierarchy/framework/index.php?type={$type}&hide={$framework->id}\" title=\"$str_hide\">".
                    "<img src=\"{$CFG->wwwroot}/pix/t/hide.gif\" class=\"iconsmall\" alt=\"$str_hide\" /></a>";
            } else {
                $buttons[] = "<a href=\"{$CFG->wwwroot}/hierarchy/framework/index.php?type={$type}&show={$framework->id}\" title=\"$str_show\">".
                    "<img src=\"{$CFG->wwwroot}/pix/t/show.gif\" class=\"iconsmall\" alt=\"$str_show\" /></a>";
            }
        }
        if ($editingon && $can_delete) {
            $buttons[] = "<a href=\"{$CFG->wwwroot}/hierarchy/framework/delete.php?type={$type}&id={$framework->id}\" title=\"$str_delete\">".
                "<img src=\"{$CFG->wwwroot}/pix/t/delete.gif\" class=\"iconsmall\" alt=\"$str_delete\" /></a>";
        }
        if ($editingon && $can_edit) {
            if ($rowcount != 1) {
                $buttons[] = "<a href=\"index.php?type={$type}&moveup={$framework->id}\" title=\"$str_moveup\">".
                   "<img src=\"{$CFG->wwwroot}/pix/t/up.gif\" class=\"iconsmall\" alt=\"$str_moveup\" /></a> ";
            } else {
                $buttons[] = "<img src=\"{$CFG->wwwroot}/pix/spacer.gif\"  class=\"iconsmall\"  alt=\"\" /> ";
            }
            if ($rowcount != count($frameworks)) {
                $buttons[] = "<a href=\"index.php?type={$type}&movedown={$framework->id}\" title=\"$str_movedown\">".
                    "<img src=\"{$CFG->wwwroot}/pix/t/down.gif\" class=\"iconsmall\" alt=\"$str_movedown\" /></a>";
            } else {
                $buttons[] = "<img src=\"{$CFG->wwwroot}/pix/spacer.gif\"  class=\"iconsmall\"  alt=\"\" /> ";
            }
            $rowcount++;
        }

        if ($buttons) {
            $row[] = implode($buttons, ' ');
        }

        $table->data[] = $row;
    }
}

// Display page

$navlinks = array();    // Breadcrumbs
$navlinks[] = array('name'=>get_string("{$type}frameworks", 'local_f2_domains'), 'link'=>'', 'type'=>'misc');

echo $OUTPUT->header('', $navlinks);

echo $OUTPUT->heading(get_string('frameworks', 'local_f2_domains'));

$plural = get_string($type.'plural', 'local_f2_domains');
$name = 'Configure ' . $plural;
//if($guide = $DB->get_record('block_guides_guide', array('name'), $name)) {
//    echo '<p><a href="'. $CFG->wwwroot . '/guides/view.php?startguide=' .
//        $guide->id . '">Step-by-step guide to configuring ' . $plural . '</a></p>';
//}
//if($type == 'competency') {
//    echo '<p>' . mitms_captivate_popup('Video help with competency frameworks', 'setting up a competency framework') . '</p>';
//} else {
//    echo '<p>' . mitms_captivate_popup('Video help with '.$plural, 'setting up ' . strtolower($plural)) . '</p>';
//}

if ($frameworks) {
    echo html_writer::table($table);
} else {
    echo '<p>'.get_string('noframeworks', 'local_f2_domains').'</p><br>';
}


// Editing buttons
if ($can_add) {
    echo '<div class="buttons">';

    // Print button for creating new framework
    //print_single_button($CFG->wwwroot.'/hierarchy/framework/edit.php?type='.$type, array('type'=>$type), get_string('addnewframework', $type), 'get');
    echo $OUTPUT->single_button($CFG->wwwroot.'/hierarchy/framework/edit.php?type='.$type, get_string('addnewframework', 'local_f2_domains'), 'get');

    echo '</div>';
}

// Display scales
if (file_exists($CFG->dirroot.'/hierarchy/type/'.$type.'/scale/lib.php')) {
    include($CFG->dirroot.'/hierarchy/type/'.$type.'/scale/lib.php');
    $scales = $hierarchy->get_scales();
    call_user_func("{$type}_scale_display_table", $scales, $editingon);
} 

//print_footer();
echo $OUTPUT->footer();
