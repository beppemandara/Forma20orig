<?php //$Id$
//This script is used to configure and execute the hierarchy restore process.

//Define some globals for all the script

require_once ("../../../config.php");
require_once ("$CFG->libdir/xmlize.php");
require_once ("$CFG->dirroot/backup/lib.php");
require_once ("$CFG->dirroot/backup/restorelib.php");
require_once ("$CFG->libdir/adminlib.php");
require_once ("$CFG->dirroot/hierarchy/lib.php");
require_once ("$CFG->dirroot/hierarchy/backuplib.php");
require_once ("$CFG->dirroot/hierarchy/restorelib.php");
require_once ("hierarchyrestore_forms.php");

global $restore, $DB;

$file = optional_param('file', '', PARAM_PATH);
$action = optional_param('action', null, PARAM_ACTION);
$cancel = optional_param('cancel', null, PARAM_ACTION);

if (isset($_POST['hierarchy'])){
    $hierarchy = $_POST['hierarchy'];
} else {
    $hierarchy = array();
}
//$hierarchy = optional_param('hierarchy', '', PARAM_TEXT);

$options = optional_param('options', array(), PARAM_TEXT);
$backup_unique_code = optional_param('backup_unique_code', null, PARAM_ALPHANUM);
$tobackup = optional_param('tobackup', array(), PARAM_TEXT);

if (!has_capability('local/f2_domains:importorganisations', get_context_instance(CONTEXT_SYSTEM))) {
    print_error("You need to be an admin user to use this page.");
}

$sitecontext = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($sitecontext);
$PAGE->set_pagelayout('standard');
$PAGE->set_url(new moodle_url("{$CFG->wwwroot}/local/f2_domains/import_export/hierarchyrestore.php"));

//Check site
if (!$site = get_site()) {
    error("Site not found!");
}

//Check necessary functions exists. Thanks to gregb@crowncollege.edu
backup_required_functions();

//Check backup_version
$linkto = "$CFG->wwwroot/local/f2_domains/import_export/hierarchyrestore.php";
//upgrade_backup_db($linkto);

// define strings
$strhierarchyrestore = get_string('hierarchyrestore','local_f2_domains');
$stradministration = get_string('administration');

//Adjust some php variables to the execution of this script
@ini_set("max_execution_time","3000");
raise_memory_limit("192M");

// if an error occurs go back to the first page
$returnurl = "$CFG->wwwroot/local/f2_domains/import_export/hierarchyrestore.php";

// redirect to first page if cancel button pressed
if(isset($cancel)) {
    redirect($returnurl);
}

//Print header
$navlinks[] = array('name' => $stradministration, 'link' => "$CFG->wwwroot/$CFG->admin/index.php", 'type' => 'misc');
$navlinks[] = array('name' => $strhierarchyrestore, 'link' => null, 'type' => 'misc');
$navigation = build_navigation($navlinks);

echo $OUTPUT->header();

echo $OUTPUT->heading("$site->shortname: $strhierarchyrestore", 2, $site->fullname);

//Print form
echo $OUTPUT->heading(format_string("$strhierarchyrestore"));

// display page based on action parameter
if($action == 'selectoptions') {
    // file picked, examine and pick restore options

    print "Examining file \"$file\""; //todo get_string
    //Now calculate the unique_code for this restore
    $backup_unique_code = time();

    $errorstr = '';
    $info = hierarchyrestore_precheck($file, $backup_unique_code, $errorstr);

    if (!$info || $errorstr != '') {
        print_error($errorstr);
    }

    // Now we have the backup as an array, look through for content
    // to determine how to display the form
    $contents = get_backup_contents($info, $backup_unique_code, $errorstr);
    if($contents === false) {
        print_error('error:restoreerror','hierarchy', $returnurl, $errorstr);
    }
    // display the form to let user pick what to restore
    $chooseitems = new hierarchyrestore_chooseitems_form(null, compact('contents'));
    $chooseitems->display();

    //Print footer
    echo $OUTPUT->footer();
    exit;


} else if ($action == 'confirm') {
    // TODO get_string
    print "<h1>Conferma l'importazione </h1>";
    print "<p>Sei sicuro di voler importare i seguenti dati? Questa operazione non puo' essere annullata. Inoltre è indispensabile
        che le tabelle dei domini sul DB siano state svuotate prima di questa operazione.</p>";

    //Reading info from file
    $xml_file  = $CFG->dataroot."/temp/backup/".$backup_unique_code."/moodle.xml";
    $xml = file_get_contents($xml_file);
    $info = xmlize($xml);

    if(!is_array($hierarchy) || count($hierarchy) == 0) {
        print 'No hierarchies';
        echo $OUTPUT->footer();
        exit;
    }
    foreach ($hierarchy AS $hname => $inc_frameworks) {
        //$inc_frameworks = array_keys($frameworks);
        print '<h2>'.get_string($hname.'plural', 'local_f2_domains').'</h2>';

        $hbackupfile = "$CFG->dirroot/hierarchy/type/$hname/backuplib.php";
        if(file_exists($hbackupfile)) {
            include_once($hbackupfile);
        }
        $getitemtagfunc = $hname.'_get_item_tag';
        if(function_exists($getitemtagfunc)) {
            $pluraltag = $getitemtagfunc(true);
            $singletag = $getitemtagfunc();
        } else {
            // try to guess tag name using name
            $pluraltag = strtoupper($hname.'s');
            $singletag = strtoupper($hname);
        }

        if(isset($info['MOODLE_BACKUP']['#']['HIERARCHIES']['0']['#']['HIERARCHY']['0']['#']['FRAMEWORKS']['0']['#']['FRAMEWORK'])) {
            $frameworks = $info['MOODLE_BACKUP']['#']['HIERARCHIES']['0']['#']['HIERARCHY']['0']['#']['FRAMEWORKS']['0']['#']['FRAMEWORK']; 
        }

        $tempitems = 'hbackup_temp_items';
        $status = create_temp_items_table($tempitems);
        // delete records with same unique code to avoid duplicates
        $DB->delete_records($tempitems, array('backup_unique_code' => $backup_unique_code));
        
        foreach ($frameworks AS $framework) {
            if(isset($framework['#']['ID']['0']['#'])) {
                $fwid = $framework['#']['ID']['0']['#'];
                $fwname = $framework['#']['FULLNAME']['0']['#'];
            }
            if (isset($inc_frameworks[$fwid]) && $inc_frameworks[$fwid] == 1) {
                print "Matching framework \"$fwname\":<br />";


                print match_items($framework, $hname, $fwid, $tempitems, $backup_unique_code);
            }
        }
    }
    echo $OUTPUT->single_button(new moodle_url($CFG->wwwroot.'/local/f2_domains/import_export/hierarchyrestore.php', array('action'=> 'execute', 'tobackup'=>serialize($hierarchy), 'options'=>serialize($options), 'backup_unique_code'=>$backup_unique_code)), 'Restore hierarchies', 'post');
    echo $OUTPUT->footer();
    exit;

} else if ($action == 'execute') {

    if(isset($tobackup)) {
        $tobackup = unserialize(stripslashes($tobackup));
    } else {
        $tobackup = array();
    }
    if(isset($options)) {
        $options = unserialize(stripslashes($options));
    } else {
        $options = array();
    }
    // FORZATURA che richiede che le tabelle relative ai domini nel DB siano vuote
    // rimuovo l'Auto increment sulla colonna ID per poter inserire liberamente gli ID presenti nell'xml
    $DB->execute("ALTER TABLE {$CFG->prefix}org MODIFY `id` bigint(10) unsigned NOT NULL");

    //Reading info from file
    $xml_file  = $CFG->dataroot."/temp/backup/".$backup_unique_code."/moodle.xml";
    $xml = file_get_contents($xml_file);
    $info = xmlize($xml);

    // need to set a global pref to fool backup_encode_absolute_links()
    // copy any existing prefs to temporary variable and restore afterwards
    $savedrestore = $restore;
    $restore->course_id = 1;
    $restore->mods = array();
    $restore->backup_unique_code = $backup_unique_code;
    $restore->users = 0;

    if(isset($info['MOODLE_BACKUP']['#']['HIERARCHIES']['0']['#']['HIERARCHY'])) {
        $hierarchies = $info['MOODLE_BACKUP']['#']['HIERARCHIES']['0']['#']['HIERARCHY'];
    } else {
        $hierarchies = array();
    }
    foreach ($hierarchies AS $hierarchy) {
        $hname = $hierarchy['#']['NAME']['0']['#'];
        print '<h2>Restoring '.get_string($hname.'plural','local_f2_domains').'</h2>';
        $restorefile = "$CFG->dirroot/hierarchy/type/$hname/restorelib.php";
        $restorefunc = $hname.'_restore';
        $hoptions = isset($options[$hname]) ? $options[$hname] : null;
        if(isset($tobackup[$hname])){
            $fwtobackup = $tobackup[$hname];
        }
        else {
            $fwtobackup = array();
        }

        if(file_exists($restorefile)) {
            include_once($restorefile);
            if(function_exists($restorefunc)) {
                $restorefunc($hierarchy, $fwtobackup, $hoptions, $backup_unique_code);
            } else {
                print "Function $restorefunc not found";
            }
        } else {
            print "No restorelib.php file found in hiearchy/type/$hname";
        }
    }
    // restore any global preferences setting
    $restore = $savedrestore;
    // ripristino l'Auto increment sulla colonna ID
    $DB->execute("ALTER TABLE {$CFG->prefix}org MODIFY `id` bigint(10) unsigned AUTO_INCREMENT NOT NULL");
    echo $OUTPUT->footer();
    exit;
} else {
    // first call to page - display list of zip files to pick from
    $hierarchyrestoredir = "$CFG->dataroot/hierarchies";
    $filelist = hierarchyrestore_get_backup_files($hierarchyrestoredir);

    if(!$filelist || count($filelist) == 0) {
        print_error('error:norestorefiles','hierarchy', '', get_string('pickfilehelp','local_f2_domains',$hierarchyrestoredir));
    }
    else {
        // print file picker form
        $pickfile = new hierarchyrestore_pickfile_form(null, compact('filelist'));
        $pickfile->display();
    }
    echo $OUTPUT->footer();
    exit;
}



