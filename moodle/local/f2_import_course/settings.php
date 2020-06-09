<?php // $Id: settings.php 83 2012-09-07 14:21:08Z c.arnolfo $

// This file defines settingpages and externalpages under the "organisations" category
//    $ADMIN->add('root', new admin_category('organisations', 'Organisations'));

    $ADMIN->add('courses', new admin_externalpage('importcourseaccess', get_string('linkname', 'local_f2_import_course'), "$CFG->wwwroot/local/f2_import_course/import_course_access.php",
            array('local/f2_import_course:importcourseaccess')));
    
    $ADMIN->add('courses', new admin_externalpage('importeditionsprg', get_string('importeditionsprg', 'local_f2_import_course'), "$CFG->wwwroot/local/f2_import_course/import_editions_course_prg.php",
    		array('local/f2_import_course:importeditionsprg')));

?>
