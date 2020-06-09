<?php
/**
 * $Id: get_support.php 1204 2013-11-11 07:37:14Z g.lallo $
 * This file processes AJAX support data actions and returns JSON
 *
 * The general idea behind this file is that any errors should throw exceptions
 * which will be returned and acted upon by the calling AJAX script.
 *
 * @package    f2_support
 * @subpackage f2_support
 */
define('AJAX_SCRIPT', true);
require_once('../../config.php');
require_once('lib.php');

define('CONST_STR_FILTER_TYPE_AF',    'AF');
define('CONST_STR_FILTER_TYPE_SUBAF', 'SUBAF');

global $DB;

$courseid = required_param('course', PARAM_INT);
$type     = required_param('type', PARAM_ALPHANUMEXT);

//$context = get_context_instance(CONTEXT_COURSE, $courseid);
$context = context_course::instance($courseid);

$PAGE->set_url('/local/f2_support/get_support.php');
$PAGE->set_context($context);

//if (!confirm_sesskey())
//{
//	$error = array('error'=>get_string('invalidsesskey', 'error'));
//	die(json_encode($error));
//}

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
		//$recordset->close();
		break;
	default:
		break;
}
die();
