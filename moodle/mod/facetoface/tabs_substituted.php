<?php

$row = $tabs = array();

$row[] = new tabobject('allenrolusers', $CFG->wwwroot."/mod/facetoface/substituted.php?s=$s&backtoallsessions=$backtoallsessions&idusrsbt=".$converter->encode($id_user_substituted), get_string('allenrolusers', 'facetoface'));
$row[] = new tabobject('usersprenot', $CFG->wwwroot."/mod/facetoface/substituted.php?s=$s&backtoallsessions=$backtoallsessions&u=".USERS_PRENOTATI_VALIDATI."&idusrsbt=".$converter->encode($id_user_substituted), get_string('usersprenot', 'facetoface'));

$tabs[] = $row;

echo '<div class="groupdisplay">';
print_tabs($tabs, $currenttab);
echo '</div>';