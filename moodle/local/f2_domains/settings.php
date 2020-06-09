<?php // $Id: settings.php 491 2012-10-17 16:00:37Z c.arnolfo $

// This file defines settingpages and externalpages under the "organisations" category
    $ADMIN->add('root', new admin_category('organisations', get_string('featureplural', 'local_f2_domains')));

    $ADMIN->add('organisations', new admin_externalpage('organisationframeworkmanage', get_string('organisationframeworkmanage', 'local_f2_domains'), "$CFG->wwwroot/hierarchy/framework/index.php?type=organisation",
            array('local/f2_domains:updateorganisationframeworks')));

    $ADMIN->add('organisations', new admin_externalpage('organisationmanage', get_string('organisationmanage', 'local_f2_domains'), $CFG->wwwroot . '/hierarchy/index.php?type=organisation',
            array('local/f2_domains:updateorganisation')));
    
    $ADMIN->add('organisations', new admin_externalpage('manageassignments', get_string('manageassignments', 'local_f2_domains'), $CFG->wwwroot . '/local/f2_domains/assignments/user.php',
            array('local/f2_domains:updateorganisation')));
    
    $ADMIN->add('organisations', new admin_externalpage('importorganisation', get_string('importorganisation', 'local_f2_domains'), $CFG->wwwroot . '/local/f2_domains/import_export/hierarchyrestore.php',
            array('local/f2_domains:updateorganisation')));
    
    // la seguente voce va ad aggiungersi nell'alberatura: Amministrazione sito -> Utenti -> Autorizzazioni
    $ADMIN->add('roles', new admin_externalpage('removeroleassignments', get_string('removeroleassignments', 'local_f2_domains'), $CFG->wwwroot . '/local/f2_domains/shared_course/user.php',
            array('local/f2_domains:updateorganisation')));

?>
