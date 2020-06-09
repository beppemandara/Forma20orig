<?php

//$Id$

require_once '../../config.php';
require_once 'lib.php';
// require_once $CFG->dirroot.'/f2_lib/ricerca_dipendenti/ricerca_dipendenti.php';

require_login();


$blockid = get_block_id(get_string('pluginname_db','block_f2_prenotazioni'));
if (has_capability('block/f2_prenotazioni:editprenotazioni', get_context_instance(CONTEXT_BLOCK, $blockid)) || has_capability('block/f2_prenotazioni:editmieprenotazioni', get_context_instance(CONTEXT_BLOCK, $blockid))) {

    if (prenotazioni_direzione_aperte() || 	isSupervisore($USER->id))
    {
            $baseurl = new moodle_url('/blocks/f2_prenotazioni/prenota_altri.php');
            $PAGE->navbar->add(get_string('prenota_altri', 'block_f2_prenotazioni'), $baseurl);
    }
    else
    {
            redirect(new moodle_url('/'));
    }

    $userid     = optional_param('userid', 0, PARAM_INT);
    $page     = optional_param('page', 0, PARAM_INT);
    $perpage  = optional_param('perpage', 10, PARAM_INT);
    $column   = optional_param('column', 'lastname', PARAM_TEXT);
    $sort     = optional_param('sort', 'ASC', PARAM_TEXT);

    if($userid==0) $userid=$USER->id;
    else if($userid!=0 && has_capability('block/f2_prenotazioni:viewprenotazioni', get_context_instance(CONTEXT_BLOCK, $blockid)) && validate_own_dipendente($userid)) $userid=$userid;
    else die();

    if (!(isSupervisore($USER->id) or isReferenteDiDirezione($USER->id)
            or isReferenteDiSettore($USER->id)))
    {
    // 	redirect(new moodle_url('/'));
    }

    $blockname = get_string('pluginname', 'block_f2_prenotazioni');

    $context = get_context_instance(CONTEXT_SYSTEM);
    $PAGE->set_context($context);
    $PAGE->set_pagelayout('standard');
    $PAGE->set_url('/blocks/f2_prenotazioni/prenota_altri.php');
    $PAGE->set_title(get_string('prenota_altri', 'block_f2_prenotazioni'));
    $PAGE->settingsnav;
    $PAGE->set_heading($SITE->shortname.': '.$blockname);

    echo $OUTPUT->header();

    echo $OUTPUT->heading(get_string('prenota_altri', 'block_f2_prenotazioni'));
    echo $OUTPUT->box_start();

    // include $CFG->wwwroot.'/f2_lib/ricerca_dipendenti/ricerca_dipendenti.php?next=prenotazioni&extraparam=pa_1';

    // print_r($CFG->wwwroot.'/f2_lib/ricerca_dipendenti/ricerca_dipendenti.php?next=prenotazioni&extraparam=pa,1');


    print_form_dipendenti('prenotazioni.php', array('pa'=>1),$page,$perpage,$column,$sort);

    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
}