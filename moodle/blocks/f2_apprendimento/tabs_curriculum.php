<?php

$row = $tabs = array();
$userid  = optional_param('userid', 0, PARAM_INT);
if ($userid > 0) {
	$row[] = new tabobject('pianodistudi', $CFG->wwwroot.'/blocks/f2_apprendimento/pianodistudi.php?userid='.$userid, get_string('pianodistudi', 'block_f2_apprendimento'));
	$row[] = new tabobject('curricula', $CFG->wwwroot.'/blocks/f2_apprendimento/curricula.php?userid='.$userid, get_string('curriculum', 'block_f2_apprendimento'));
} else {
	$row[] = new tabobject('pianodistudi', $CFG->wwwroot.'/blocks/f2_apprendimento/pianodistudi.php', get_string('pianodistudi', 'block_f2_apprendimento'));
	$row[] = new tabobject('curricula', $CFG->wwwroot.'/blocks/f2_apprendimento/curricula.php', get_string('curriculum', 'block_f2_apprendimento'));
}

$tabs[] = $row;

echo '<div class="groupdisplay">';
print_tabs($tabs, $currenttab);
echo '</div>';