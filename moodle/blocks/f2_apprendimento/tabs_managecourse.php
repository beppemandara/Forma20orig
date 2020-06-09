<?php

$row = $tabs = array();

$row[] = new tabobject('managecourse_prog', $CFG->wwwroot.'/blocks/f2_apprendimento/managecourse_prog.php', get_string('managecourse_prog', 'local_f2_course'));
$row[] = new tabobject('managecourse_obb', $CFG->wwwroot.'/blocks/f2_apprendimento/managecourse_obb.php', get_string('managecourse_obb', 'local_f2_course'));

$tabs[] = $row;

echo '<div class="groupdisplay">';
print_tabs($tabs, $currenttab);
echo '</div>';