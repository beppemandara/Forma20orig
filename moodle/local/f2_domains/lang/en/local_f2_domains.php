<?php // $Id: local_f2_domains.php 1225 2013-12-05 11:46:59Z l.moretto $ 

$string['pluginname'] = 'Forma Domini';
$string['blockname'] = 'NEWBLOCK display name';
$string['blockstring'] = 'NEWBLOCK string';
$string['defaultvalue'] = 'NEWBLOCK';
$string['error:courseroleassign'] = 'Error assigning role for user: {$a->userid}, role:{$a->roleid}, course:{$a->courseid}';
$string['error:categoryroleassign'] = 'Error assigning role for user: {$a->userid}, role:{$a->roleid}, category:{$a->categoryid}';
require_once("local.php");
require_once("organisation.php");
require_once("customfields.php");
require_once("hierarchy.php");