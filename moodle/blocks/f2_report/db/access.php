<?php
//$Id$
defined('MOODLE_INTERNAL') || die();
$capabilities = array(
  'block/f2_report:myaddinstance' => array(
    'captype' => 'write',
    'contextlevel' => CONTEXT_SYSTEM,
    'archetypes' => array(
      'user' => CAP_ALLOW
    ),
    'clonepermissionsfrom' => 'moodle/my:manageblocks'
  ),
  'block/f2_report:addinstance' => array(
    'riskbitmask' => RISK_SPAM | RISK_XSS,
    'captype' => 'write',
    'contextlevel' => CONTEXT_BLOCK,
    'archetypes' => array(
      'editingteacher' => CAP_ALLOW,
      'manager' => CAP_ALLOW
    ),
    'clonepermissionsfrom' => 'moodle/site:manageblocks'
  ),
  'block/f2_report:viewreport' => array(
    'captype' => 'read',
    'contextlevel' => CONTEXT_BLOCK,
    'archetypes' => array(
      'manager' => CAP_ALLOW
    )
  ),
  'block/f2_report:online' => array(
    'captype' => 'read',
    'contextlevel' => CONTEXT_BLOCK,
    'archetypes' => array(
      'manager' => CAP_ALLOW
    )
  ),
);
