<?php
//$Id$
defined('MOODLE_INTERNAL') || die();
$capabilities = array(
  'block/f2_prenotazioni:myaddinstance' => array(
    'captype' => 'write',
    'contextlevel' => CONTEXT_SYSTEM,
    'archetypes' => array(
      'user' => CAP_ALLOW
    ),
    'clonepermissionsfrom' => 'moodle/my:manageblocks'
  ),
  'block/f2_prenotazioni:addinstance' => array(
    'riskbitmask' => RISK_SPAM | RISK_XSS,
    'captype' => 'write',
    'contextlevel' => CONTEXT_BLOCK,
    'archetypes' => array(
      'editingteacher' => CAP_ALLOW,
      'manager' => CAP_ALLOW
    ),
    'clonepermissionsfrom' => 'moodle/site:manageblocks'
  ),
		'block/f2_prenotazioni:viewprenotazioni' => array(
				'captype' => 'read',
				'contextlevel' => CONTEXT_BLOCK
		),
		'block/f2_prenotazioni:editprenotazioni' => array(
				'captype' => 'write',
				'contextlevel' => CONTEXT_BLOCK
		),
		'block/f2_prenotazioni:editmieprenotazioni' => array(
				'captype' => 'write',
				'contextlevel' => CONTEXT_BLOCK
		),
		'block/f2_prenotazioni:viewvalidazioni' => array(
				'captype' => 'read',
				'contextlevel' => CONTEXT_BLOCK
		),
		'block/f2_prenotazioni:editvalidazioni' => array(
				'captype' => 'write',
				'contextlevel' => CONTEXT_BLOCK
		)
);
