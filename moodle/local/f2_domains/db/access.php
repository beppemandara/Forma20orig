<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = array(
	'local/f2_domains:vieworganisation' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_SYSTEM,
	),     
	'local/f2_domains:createorganisation' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_SYSTEM
	),
	'local/f2_domains:updateorganisation' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_SYSTEM
	),
	'local/f2_domains:deleteorganisation' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_SYSTEM
	),
	'local/f2_domains:createorganisationdepth' => array(
		'captype'       => 'write',
		'contextlevel'  => CONTEXT_SYSTEM
	),
	'local/f2_domains:updateorganisationdepth' => array(
		'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
		'captype'       => 'write',
		'contextlevel'  => CONTEXT_SYSTEM
	),
	'local/f2_domains:deleteorganisationdepth' => array(
		'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
		'captype'       => 'write',
		'contextlevel'  => CONTEXT_SYSTEM
	),
	'local/f2_domains:createorganisationframeworks' => array(
		'captype'       => 'write',
		'contextlevel'  => CONTEXT_SYSTEM
	),
	'local/f2_domains:updateorganisationframeworks' => array(
		'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
		'captype'       => 'write',
		'contextlevel'  => CONTEXT_SYSTEM
	),
	'local/f2_domains:deleteorganisationframeworks' => array(
		'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
		'captype'       => 'write',
		'contextlevel'  => CONTEXT_SYSTEM
	),
	'local/f2_domains:createorganisationcustomfield' => array(
		'captype'       => 'write',
		'contextlevel'  => CONTEXT_SYSTEM
	),
	'local/f2_domains:updateorganisationcustomfield' => array(
		'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
		'captype'       => 'write',
		'contextlevel'  => CONTEXT_SYSTEM
	),
	'local/f2_domains:deleteorganisationcustomfield' => array(
		'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
		'captype'       => 'write',
		'contextlevel'  => CONTEXT_SYSTEM
	),
	'local/f2_domains:assignorganisation' => array(
		'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
		'captype'       => 'write',
		'contextlevel'  => CONTEXT_SYSTEM
	),
	'local/f2_domains:importorganisations' => array(
		'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
		'captype'       => 'write',
		'contextlevel'  => CONTEXT_SYSTEM
	)
);
