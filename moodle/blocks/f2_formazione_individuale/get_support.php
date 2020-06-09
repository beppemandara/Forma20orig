<?php
/**
 * $Id: get_support.php 983 2013-01-16 17:13:28Z c.arnolfo $
 * This file processes AJAX support data actions and returns JSON
 *
 * The general idea behind this file is that any errors should throw exceptions
 * which will be returned and acted upon by the calling AJAX script.
 */
global $CFG, $PAGE, $OUTPUT;

define('AJAX_SCRIPT', true);
require_once('../../config.php');
require_once($CFG->dirroot.'/local/f2_support/lib.php');

define(CONST_STR_FILTER_TYPE_AF,    'AF');
define(CONST_STR_FILTER_TYPE_SUBAF, 'SUBAF');

$type     = required_param('type', PARAM_ALPHANUMEXT);

$context = get_context_instance(CONTEXT_SYSTEM);

$PAGE->set_context($context);
$PAGE->set_url('/blocks/f2_formazione_individuale/get_support.php');

echo $OUTPUT->header(); // send headers

// process ajax request
switch ($type)
{
	case CONST_STR_FILTER_TYPE_AF:
		$sf_id = required_param('sf', PARAM_ALPHANUMEXT);
		$rs = get_AF_from_SF($sf_id);
		$a_rs = array();
		foreach ($rs as $afid=>$af) {
			$a_rs[] = $af;
		}
		$obj['type'] = $type;
		$obj['data'] = $a_rs;
		echo json_encode($obj);
		break;
	case CONST_STR_FILTER_TYPE_SUBAF:
		$af_id = required_param('af', PARAM_ALPHANUMEXT);
		$rs = get_SUBAF_from_AF($af_id);
		$a_rs = array();
		foreach ($rs as $subafid=>$subaf) {
			$a_rs[] = $subaf;
		}
		$obj['type'] = $type;
		$obj['data'] = $a_rs;
		echo json_encode($obj);
		break;
	default:
		break;
}
die();