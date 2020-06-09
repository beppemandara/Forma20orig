<?php
/*
 * $Id: tabs_mycourses.php 854 2012-12-10 07:53:10Z l.moretto $
 */
$row = $tabs = array();

$row[] = new tabobject('my_courses_prog', 
												$CFG->wwwroot.'/local/f2_course/mycourses_prog.php', 
												get_string('my_courses_prog', 'local_f2_course')
				);
$row[] = new tabobject('my_courses_obb', 
												$CFG->wwwroot.'/local/f2_course/mycourses_obb.php', 
												get_string('my_courses_obb', 'local_f2_course')
				);

$tabs[] = $row;

echo '<div class="groupdisplay">';
print_tabs($tabs, $currenttab);
echo '</div>';