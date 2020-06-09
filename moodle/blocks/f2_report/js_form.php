<?php
//$Id$
require_once '../../config.php';
require_once 'lib.php';
// print_r($_POST);
$anno_per_sessione = $_POST['ajax_anno_formativo_per_sessione'];
$anno_per_corso = $_POST['ajax_anno_formativo_per_corso'];
if (isset($anno_per_sessione))
{
	print_scelta_sessione_from_selected_year($anno_per_sessione);
}
if (isset($anno_per_corso))
{
	print_scelta_corso_from_selected_year($anno_per_corso);
}
