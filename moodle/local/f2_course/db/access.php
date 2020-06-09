<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = array(
		
		'local/f2_course:mycourses' => array(
				'captype' => 'read',
				'contextlevel' => CONTEXT_SYSTEM
        ),
		'local/f2_course:f2_elencocorsi' => array(
                        'captype' => 'read',
                        'contextlevel' => CONTEXT_SYSTEM
                )
);
