<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = array(
		'local/f2_import_course:importcourseaccess' => array(
				'captype' => 'read',
				'contextlevel' => CONTEXT_SYSTEM,
				'archetypes' => array(
				)
		),
		
		'local/f2_import_course:importeditionsprg' => array(
				'captype' => 'read',
				'contextlevel' => CONTEXT_SYSTEM,
				'archetypes' => array(
						)
				)
);