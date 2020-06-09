<?php

$row = $tabs = array();

$row[] = new tabobject('pianodistudi_dip', $CFG->wwwroot.'/blocks/f2_apprendimento/pianodistudi_utenti.php', get_string('pianodistudidip', 'block_f2_apprendimento'));
$row[] = new tabobject('curricula_dip', $CFG->wwwroot.'/blocks/f2_apprendimento/curricula_dipendenti.php', get_string('curriculum_dip', 'block_f2_apprendimento'));

$tabs[] = $row;

echo '<div class="groupdisplay">';
print_tabs($tabs, $currenttab);
echo '</div>';