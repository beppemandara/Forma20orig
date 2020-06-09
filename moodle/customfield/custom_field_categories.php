<?php
require_once('../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/hierarchy/lib.php');
require_once($CFG->dirroot.'/customfield/indexlib.php');

///
/// Setup / loading data
///

$sitecontext = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($sitecontext);
$PAGE->set_pagelayout('standard');
// Get params
$type        = optional_param('type', -1, PARAM_SAFEDIR);
$frameworkid = optional_param('frameworkid', -1, PARAM_SAFEDIR);
$depthid     = optional_param('depthid', -1, PARAM_SAFEDIR);
$edit        = optional_param('edit', -1, PARAM_BOOL);
$action      = optional_param('action', '', PARAM_ALPHA);    // param for some action

$shortprefix = hierarchy::get_short_prefix($type);
$tableprefix = "{$shortprefix}_depth";

if (file_exists($CFG->dirroot.'/hierarchy/type/'.$type.'/lib.php')) {
    require_once($CFG->dirroot.'/hierarchy/type/'.$type.'/lib.php');
    $hierarchy = new $type();
    $depth = $hierarchy->get_depth_by_id($depthid);
    $framework = $hierarchy->get_framework($frameworkid);
} else {
    // don't error, just echo!
    error('error:depthnotfound', 'hierarchy', $type);
}

// Cache user capabilities
$can_add = has_capability('local/f2_domains:create'.$type.'customfield', $sitecontext);
$can_edit = has_capability('local/f2_domains:update'.$type.'customfield', $sitecontext);
$can_delete = has_capability('local/f2_domains:delete'.$type.'customfield', $sitecontext);

if ($can_add || $can_edit || $can_delete) {
    $navbaritem = $hierarchy->get_editing_button($edit, array('depthid'=>$depthid, 'frameworkid'=>$frameworkid));
    $editingon = !empty($USER->{$type.'editing'});
} else {
    $navbaritem = '';
    $editingon = false;
}

// Setup page and check permissions
admin_externalpage_setup($type.'frameworkmanage', $navbaritem, array('type'=>$type));

///
/// Perform actions first
///
switch ($action) {
    case 'deletecategory':
        require_capability('local/f2_domains:delete'.$type.'customfield', $sitecontext);
        $id      = required_param('categoryid', PARAM_INT);
        $confirm = optional_param('confirm', 0, PARAM_BOOL);

        if (data_submitted() and $confirm and confirm_sesskey()) {
            customfield_delete_category($id, $depthid, $tableprefix);
        }   
        break;
    default:
}

///
/// Load data for depth details
///

// Get depths for this page
$categories = $hierarchy->get_custom_field_categories($depthid);

///
/// Generate / display page
///
$str_edit     = get_string('edit');
$str_delete   = get_string('delete');


if ($categories) {
    // Create display table
    $table = new html_table();
    
    $table->class = 'generaltable edit'.$type;
    $table->width = '95%';

    // Setup column headers
    $table->head = array(get_string('name', 'local_f2_domains'), get_string("{$type}customfields", 'local_f2_domains'));
    $table->align = array('left', 'center');

    // Add edit column
    if ($editingon && $can_edit) {
        $table->head[] = get_string('edit');
        $table->align[] = 'left';
    }

    // Add rows to table
    $rowcount = 1;
    foreach ($categories as $category) {
        $row = array();

        $cssclass = '';

        $row[] = "<a $cssclass href=\"{$CFG->wwwroot}/customfield/index.php?type={$type}&subtype=depth&frameworkid={$framework->id}&depthid={$depth->id}&categoryid={$category->id}\">{$category->name}</a>";
        $row[] = $category->custom_field_count;

        // Add edit link
        $buttons = array();
        if ($editingon && $can_edit) {
            $buttons[] = "<a href=\"{$CFG->wwwroot}/customfield/index.php?type={$type}&subtype=depth&categoryid={$category->id}&frameworkid={$framework->id}&depthid={$depthid}&action=editcategory\" title=\"$str_edit\">".
                "<img src=\"{$CFG->pixpath}/t/edit.gif\" class=\"iconsmall\" alt=\"$str_edit\" /></a>";
        }
        if ($editingon && $can_delete) {
            $buttons[] = "<a href=\"{$CFG->wwwroot}/customfield/index.php?type={$type}&subtype=depth&categoryid={$category->id}&frameworkid={$framework->id}&depthid={$depthid}&action=deletecategory\" title=\"$str_delete\">".
                "<img src=\"{$CFG->pixpath}/t/delete.gif\" class=\"iconsmall\" alt=\"$str_delete\" /></a>";
        }
        if ($buttons) {
            $row[] = implode($buttons, ' ');
        }

        $table->data[] = $row;
    }
}

// Display page
$navlinks = array();    // Breadcrumbs
$navlinks[] = array('name'=>get_string("{$type}frameworks", 'local_f2_domains'), 
                    'link'=>"{$CFG->wwwroot}/hierarchy/framework/index.php?type={$type}", 
                    'type'=>'misc');    // Framework List
$navlinks[] = array('name'=>format_string($framework->fullname), 
                    'link'=>"{$CFG->wwwroot}/hierarchy/framework/view.php?type={$type}&frameworkid={$framework->id}", 
                    'type'=>'misc');    // Framework View    
$navlinks[] = array('name'=>format_string($depth->fullname), 
                    'link'=>'', 
                    'type'=>'misc');    // Current page

echo $OUTPUT->header('', $navlinks);

//print_heading($depth->fullname, 'left', 1);
echo $OUTPUT->heading($depth->fullname);
echo "<p>{$depth->description}</p>";

// Display Depths
echo $OUTPUT->heading(get_string('customfieldcategories', 'local_f2_domains'));

//echo '<p>' . mitms_captivate_popup('Video help with custom fields and categories', 'setting up custom categories and fields') . '</p>';

if ($categories) {
    echo html_writer::table($table);
} else {
    echo '<p>'.get_string('nocustomfieldcategoriesdefined', 'local_f2_domains').'</p>';
}
// Depth Add button
if ($can_add) {
    echo '<div class="buttons">';

    // Print button for creating new custom field
//    print_single_button($CFG->wwwroot.'/customfield/index.php', 
//        array('type'=>$type, 'subtype'=>'depth', 'frameworkid' => $frameworkid,
//              'categoryid' => 0, 'depthid'=>$depthid, 'action'=>'editcategory'), 
//        get_string('createcustomfieldcategory', 'local_f2_domains'), 'get');
    
    echo $OUTPUT->single_button(new moodle_url($CFG->wwwroot.'/customfield/index.php', array('type'=>$type, 'subtype'=>'depth', 'frameworkid' => $frameworkid,
              'categoryid' => 0, 'depthid'=>$depthid, 'action'=>'editcategory')), get_string('adddepthlevel', 'local_f2_domains'), 'get');
    echo '</div>';
}

echo $OUTPUT->footer();
