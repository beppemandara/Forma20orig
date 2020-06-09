<?php
/*
 * $Id: edit.php 1306 2014-07-09 08:27:53Z l.moretto $
 */
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/customfield/fieldlib.php');
require_once($CFG->dirroot.'/hierarchy/item/edit_form.php');
require_once($CFG->dirroot.'/hierarchy/lib.php');

///
/// Setup / loading data
///

$type = required_param('type', PARAM_SAFEDIR);
$shortprefix = hierarchy::get_short_prefix($type);

// item id; 0 if creating new item
$id   = optional_param('id', 0, PARAM_INT);

// framework id; required when creating a new framework item 
$frameworkid = optional_param('frameworkid', 0, PARAM_INT);
$spage       = optional_param('spage', 0, PARAM_INT);

// Confirm the type exists
if (file_exists($CFG->dirroot.'/hierarchy/type/'.$type.'/lib.php')) {
    require_once($CFG->dirroot.'/hierarchy/type/'.$type.'/lib.php');
} else {
    error('Hierarchy type '.$type.' does not exist');
}

// Load any type specific code
if (file_exists($CFG->dirroot.'/hierarchy/type/'.$type.'/item/edit_form.php')) {
    require_once($CFG->dirroot.'/hierarchy/type/'.$type.'/item/edit_form.php');
    $formname = $type.'_edit_form';
}
else {
    $formname = 'item_edit_form';
}

// We require either an id for editing, or a framework for creating
if (!$id && !$frameworkid) {
    error('Incorrect parameters');
}

// Make this page appear under the manage competencies admin item
admin_externalpage_setup($type.'manage', '', array('type'=>$type));

//$context = get_context_instance(CONTEXT_SYSTEM);
$context = context_system::instance();

if ($id == 0) {
    // creating new item
    require_capability('local/f2_domains:create'.$type, $context);

    $item = new object();
    $item->id = 0;
    $item->frameworkid = $frameworkid;
    $item->visible = 1;
    $item->sortorder = 1;
    $item->depthid = null;

} else {
    // editing existing item
    require_capability('local/f2_domains:update'.$type, $context);

    if (!$item = $DB->get_record($shortprefix, array('id' => $id))) {
        print_error($type.' ID was incorrect');
    }

    // load custom fields data
    if ($id != 0) {
        customfield_load_data($item, $type, $shortprefix.'_depth');
    }
}

// Load framework
if (!$framework = $DB->get_record($shortprefix.'_framework', array('id' => $frameworkid))) {
    print_error($type.' framework ID was incorrect');
}
$item->framework = $framework->fullname;


///
/// Display page
///

// create form
$datatosend = array('type' => $type, 'item' => $item, 'spage' => $spage);
$itemform = new $formname(null, $datatosend);
$itemform->set_data($item);

// cancelled
if ($itemform->is_cancelled()) {

    redirect("{$CFG->wwwroot}/hierarchy/index.php?type={$type}&frameworkid={$item->frameworkid}");

// Update data
} else if ($itemnew = $itemform->get_data()) {

    $itemnew->timemodified = time();
    $itemnew->usermodified = $USER->id;

    $itemnew->proficiencyexpected = 1;
    $itemnew->evidencecount = 0;

    // Load parent item if set
    if ($itemnew->parentid) {
        if (!$parent = $DB->get_record($shortprefix, array('id' => $itemnew->parentid))) {
            error('Parent '.$type.' ID was incorrect');
        }
        $parent_depth = $DB->get_field($shortprefix.'_depth', 'depthlevel', array('id' => $parent->depthid));

    } else {
        $parent_depth = 0;
    }

//    $itemnew->depthid = get_field($shortprefix.'_depth', 'id', 'frameworkid', $itemnew->frameworkid, 'depthlevel', $parent_depth + 1);
    $itemnew->depthid = $DB->get_field($shortprefix.'_depth', 'id', array('frameworkid' => $itemnew->frameworkid, 'depthlevel' => $parent_depth + 1));
    
    // Start db operations
//    begin_sql();
    try {
        $transaction = $DB->start_delegated_transaction();

        // Sort order
        // Need to update if parent changed or new
        if (!isset($item->parentid) || $itemnew->parentid != $item->parentid) {

            // Find highest sortorder of siblings
            $path = $itemnew->parentid ? $parent->path : '';
            $sql = "SELECT MAX(sortorder) FROM {$CFG->prefix}{$shortprefix} WHERE frameworkid = {$itemnew->frameworkid}";
            if ($path) {
                $sql .= " AND path LIKE '{$path}/%'";
            }
//            print_r('$sql SELECT: '.$sql);

    //        $sortorder = (int) get_field_sql($sql);
            $sortorder = (int) $DB->get_field_sql($sql);
            // Find the next sortorder
            $itemnew->sortorder = $sortorder + 1;

            // Increment all following items
//            print_r('   $sql UPDATE: '."UPDATE {$CFG->prefix}{$shortprefix} SET sortorder = sortorder + 1 WHERE sortorder > $sortorder AND frameworkid = {$itemnew->frameworkid}");
            $DB->execute("UPDATE {$CFG->prefix}{$shortprefix} SET sortorder = sortorder + 1 WHERE sortorder > $sortorder AND frameworkid = {$itemnew->frameworkid}");
        }

        // Create path for finding ancestors
        $itemnew->path = ($itemnew->parentid ? $parent->path : '') . '/' . ($itemnew->id != 0 ? $itemnew->id : '');

        // Save
        // New item
        if ($itemnew->id == 0) {
            unset($itemnew->id);

            $itemnew->timecreated = time();

//            print_r('   $shortprefix: '.$shortprefix);
//            print_r(    $itemnew);
            
            if (!$itemnew->id = $DB->insert_record($shortprefix, $itemnew)) {
                error('Error creating '.$type.' record');
            }
//            print_r('   $itemnew->id: '.$itemnew->id );

            // Can't set the full path till we know the id!
            $DB->set_field($shortprefix, 'path', $itemnew->path.$itemnew->id, array('id' => $itemnew->id));

        // Existing item
        } else {
            if ($DB->update_record($shortprefix, $itemnew)) {
                customfield_save_data($itemnew, $type, $shortprefix.'_depth');
            } else {
                error('Error updating '.$type.' record');
            }
        }

        //AK-LM START: patch temporanea di ordinamento
        // in attesa di una procedura di visita dell'albero con ordinamento indipendente dei sottorami
        // da eseguire una tantum.
        $qu = "update mdl_org 
            join (select id
                     ,(@rn := @rn + 1) as rn
                 from mdl_org 
                 cross join (select @rn := 0) const
                 where frameworkid=? 		
                 order by path, sortorder, fullname
            ) torder on mdl_org.id = torder.id
            set mdl_org.sortorder = torder.rn";
        $DB->execute($qu, array($frameworkid));
        //AK-LM STOP: patch temporanea di ordinamento
        
        // Commit db operations
        $transaction->allow_commit();
    } catch (Exception $e) {
//        print_r('ROLLBACK!!');
        $transaction->rollback($e);
    }

    // Reload from db
    $itemnew = $DB->get_record($shortprefix, array('id' => $itemnew->id));

    // Log
    add_to_log(SITEID, $type, 'update', "view.php?id=$frameworkid", '');

    redirect("{$CFG->wwwroot}/hierarchy/item/view.php?type={$type}&id={$itemnew->id}");
    //never reached
}


/// Display page header
echo $OUTPUT->header();

if ($item->id == 0) {
    echo $OUTPUT->heading(get_string('addnew'.$type, 'local_f2_domains'));
} else {
    echo $OUTPUT->heading(get_string('edit'.$type, 'local_f2_domains'));
}

/// Finally display THE form
$itemform->display();

/// and proper footer
echo $OUTPUT->footer();
