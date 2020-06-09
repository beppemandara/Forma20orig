<?php
/*
 * $Id: access.php 654 2012-10-31 13:42:08Z g.nuzzolo $
 */
defined('MOODLE_INTERNAL') || die();

$capabilities = array(
  'block/f2_gestione_risorse:myaddinstance' => array(
    'captype' => 'write',
    'contextlevel' => CONTEXT_SYSTEM,
    'archetypes' => array(
      'user' => CAP_ALLOW
    ),
    'clonepermissionsfrom' => 'moodle/my:manageblocks'
  ),
  'block/f2_gestione_risorse:addinstance' => array(
    'riskbitmask' => RISK_SPAM | RISK_XSS,
    'captype' => 'write',
    'contextlevel' => CONTEXT_BLOCK,
    'archetypes' => array(
      'editingteacher' => CAP_ALLOW,
      'manager' => CAP_ALLOW
    ),
    'clonepermissionsfrom' => 'moodle/site:manageblocks'
  ),
	'block/f2_gestione_risorse:aggiungi_formatore' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_SYSTEM,
		'archetypes' => array(
			'manager' => CAP_ALLOW	
        )
	),
	'block/f2_gestione_risorse:modifica_formatore' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_SYSTEM,
		'archetypes' => array(
			'manager' => CAP_ALLOW	
        )
	),
	'block/f2_gestione_risorse:vedi_lista_formatori' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_SYSTEM,
		'archetypes' => array(
			'manager' => CAP_ALLOW	
        )
	),
	'block/f2_gestione_risorse:vedi_lista_utenti' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_SYSTEM,
		'archetypes' => array(
			'manager' => CAP_ALLOW	
        )
	),
    'block/f2_gestione_risorse:viewfunzionalita' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
			'manager' => CAP_ALLOW	
        )
    ),
    'block/f2_gestione_risorse:editfunzionalita' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
			'manager' => CAP_ALLOW	
        )
    ),
    'block/f2_gestione_risorse:viewsessioni' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
			'manager' => CAP_ALLOW	
        )
    ),
    'block/f2_gestione_risorse:editsessioni' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
			'manager' => CAP_ALLOW	
        )
    ),	
	'block/f2_gestione_risorse:budget_edit' => array(
			'captype' => 'write',
			'contextlevel' => CONTEXT_SYSTEM
	),	
	'block/f2_gestione_risorse:budget_approve' => array(
			'captype' => 'write',
			'contextlevel' => CONTEXT_SYSTEM
	),
		'block/f2_gestione_risorse:add_fornitori' => array(
			'captype' => 'write',
			'contextlevel' => CONTEXT_SYSTEM
	),
	'block/f2_gestione_risorse:send_auth_mail' => array(
				'captype' => 'write',
				'contextlevel' => CONTEXT_SYSTEM
		),
);
