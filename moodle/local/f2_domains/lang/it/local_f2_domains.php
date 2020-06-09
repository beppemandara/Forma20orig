<?php // $Id: local_f2_domains.php 1225 2013-12-05 11:46:59Z l.moretto $ 

$string['pluginname'] = 'Forma Domini';
$string['blockname'] = 'NEWBLOCK display name';
$string['blockstring'] = 'NEWBLOCK string';
$string['defaultvalue'] = 'NEWBLOCK';
$string['error:courseroleassign'] = 'Errore in assegnazione ruolo per utente: {$a->userid}, ruolo:{$a->roleid}, corso:{$a->courseid}';
$string['error:categoryroleassign'] = 'Errore in assegnazione ruolo per utente: {$a->userid}, ruolo:{$a->roleid}, categoria:{$a->categoryid}';
require_once("local.php");
require_once("organisation.php");
require_once("customfields.php");
require_once("hierarchy.php");