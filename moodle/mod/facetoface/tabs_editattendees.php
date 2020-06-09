<?php

$row = $tabs = array();

$row[] = new tabobject('allenrolusers', $CFG->wwwroot."/mod/facetoface/editattendees.php?s=$s&backtoallsessions=$backtoallsessions", get_string('allenrolusers', 'facetoface'));
$row[] = new tabobject('usersprenot', $CFG->wwwroot."/mod/facetoface/editattendees.php?s=$s&backtoallsessions=$backtoallsessions&u=".USERS_PRENOTATI_VALIDATI, get_string('usersprenot', 'facetoface'));

$tabs[] = $row;

echo '<div class="groupdisplay">';
print_tabs($tabs, $currenttab);
echo '</div>';
