<?php
//$Id$
defined('MOODLE_INTERNAL') || die();
$capabilities = array(
/*
		'block/f2_prenotazioni:viewprenotazioni' => array(
				'captype' => 'read',
				'contextlevel' => CONTEXT_SYSTEM,
				'archetypes' => array(
						'manager' => CAP_ALLOW
				)
		),
		'block/f2_prenotazioni:editprenotazioni' => array(
				'captype' => 'write',
				'contextlevel' => CONTEXT_SYSTEM,
				'archetypes' => array(
						'manager' => CAP_ALLOW
				)
		)*/
	'local/f2_notif:edit_notifiche' => array(
			'captype' => 'write',
			'contextlevel' => CONTEXT_SYSTEM
	)
);
