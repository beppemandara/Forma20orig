<?php
/*
 * $Id: access.php 696 2012-11-12 15:55:21Z c.arnolfo $
 */
$capabilities = array(
  'block/f2_apprendimento:myaddinstance' => array(
    'captype' => 'write',
    'contextlevel' => CONTEXT_SYSTEM,
    'archetypes' => array(
      'user' => CAP_ALLOW
    ),
    'clonepermissionsfrom' => 'moodle/my:manageblocks'
  ),
  'block/f2_apprendimento:addinstance' => array(
    'riskbitmask' => RISK_SPAM | RISK_XSS,
    'captype' => 'write',
    'contextlevel' => CONTEXT_BLOCK,
    'archetypes' => array(
      'editingteacher' => CAP_ALLOW,
      'manager' => CAP_ALLOW
    ),
    'clonepermissionsfrom' => 'moodle/site:manageblocks'
  ),
	'block/f2_apprendimento:viewcurricula' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_SYSTEM,
		'archetypes' => array(
			'manager' => CAP_ALLOW	
		)
	),

	'block/f2_apprendimento:exportcurricula' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_SYSTEM,
		'archetypes' => array(
			'manager' => CAP_ALLOW
		)
	),

	'block/f2_apprendimento:viewdipendenticurricula' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_SYSTEM,
		'archetypes' => array(
			'manager' => CAP_ALLOW
		)
	),
		
	'block/f2_apprendimento:viewpianodistudi' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_SYSTEM,
		'archetypes' => array(
			'manager' => CAP_ALLOW
		)
	),
		
	'block/f2_apprendimento:viewpianodistudidipendenti' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_SYSTEM,
		'archetypes' => array(
			'manager' => CAP_ALLOW
		)
	),
		
	'block/f2_apprendimento:viewgestionecorsi' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_BLOCK,
		'archetypes' => array(
			'manager' => CAP_ALLOW
		)
	),
	
        'block/f2_apprendimento:leggistorico' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_SYSTEM
	),
// a.a. Modifica per attivare Gestione Crediti Riforma
        'block/f2_apprendimento:forma2riforma' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_SYSTEM
	),
// a.a. Modifica per attivare Gestione Crediti Riforma    
        'block/f2_apprendimento:modificastorico' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_SYSTEM
	)

);


