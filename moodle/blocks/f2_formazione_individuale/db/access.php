<?php
/*
 * $Id: access.php 979 2013-01-15 17:04:46Z c.arnolfo $
 */
$capabilities = array(
  'block/f2_formazione_individuale:myaddinstance' => array(
    'captype' => 'write',
    'contextlevel' => CONTEXT_SYSTEM,
    'archetypes' => array(
      'user' => CAP_ALLOW
    ),
    'clonepermissionsfrom' => 'moodle/my:manageblocks'
  ),
  'block/f2_formazione_individuale:addinstance' => array(
    'riskbitmask' => RISK_SPAM | RISK_XSS,
    'captype' => 'write',
    'contextlevel' => CONTEXT_BLOCK,
    'archetypes' => array(
      'editingteacher' => CAP_ALLOW,
      'manager' => CAP_ALLOW
    ),
    'clonepermissionsfrom' => 'moodle/site:manageblocks'
  ),
	'block/f2_formazione_individuale:individualigiunta' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_SYSTEM
	),

	'block/f2_formazione_individuale:individualilinguagiunta' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_SYSTEM
	),

	'block/f2_formazione_individuale:individualiconsiglio' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_SYSTEM
	),
		
	'block/f2_formazione_individuale:forzature' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_SYSTEM
	)
);


