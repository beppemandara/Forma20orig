<?php
global $OUTPUT, $PAGE, $SITE, $CFG;

require_once '../../config.php';
require_once 'lib_ind_senza_determina.php';

require_login();

$sort      = optional_param('sort', 'ASC', PARAM_ALPHANUM);
$dir       = optional_param('dir', 'ASC', PARAM_ALPHA);
$perpage   = optional_param('perpage', 10, PARAM_INT);       // how many per page
$mod       = optional_param('mod', 0, PARAM_INT);
$n_prot    = optional_param('n_prot', '', PARAM_ALPHANUM);
$id_course = optional_param('id_course', null, PARAM_RAW);
$training  = optional_param('training', '', PARAM_TEXT);
$dato_ricercato = optional_param('dato_ricercato', '', PARAM_TEXT);

$label_training = get_label_training($training);
/*
$url_params = array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage, 'training'=>$training, 'mod'=>$mod);
$baseurl = new moodle_url('/blocks/f2_formazione_individuale/gest_corsi_ind_senza_determina.php', $url_params);
$url_params2 = array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage, 'training'=>$training, 'dato_ricercato'=>$dato_ricercato, 'mod'=>$mod, 'n_prot'=>$n_prot);
$url = new moodle_url('/blocks/f2_formazione_individuale/ins_prot_senza_determina.php', $url_params2);
//echo "Prot: ".$n_prot." - id_course: ".$id_course;
$blockname = get_string('pluginname', 'block_f2_formazione_individuale');
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/f2_formazione_individuale/gest_corsi_ind_senza_determina.php');
$PAGE->set_title(get_string('titlegestionecorsigratis', 'block_f2_formazione_individuale'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string($label_training, 'block_f2_formazione_individuale'));
$PAGE->navbar->add(get_string('gestionecorsigratis', 'block_f2_formazione_individuale'), $baseurl);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
$PAGE->navbar->add(get_string('ins_prot_gratis', 'block_f2_formazione_individuale'), $url);
$PAGE->navbar->add(get_string('mod_prot_gratis', 'block_f2_formazione_individuale'), '');

$capability_giunta = has_capability('block/f2_formazione_individuale:individualigiunta', $context);
$capability_linguagiunta = has_capability('block/f2_formazione_individuale:individualilinguagiunta', $context);
$capability_consiglio = has_capability('block/f2_formazione_individuale:individualiconsiglio', $context);
$param_CIG = get_parametro('p_f2_corsi_individuali_giunta');
$param_CIL = get_parametro('p_f2_corsi_individuali_lingua_giunta');
$param_CIC = get_parametro('p_f2_corsi_individuali_consiglio');

if(!(($capability_giunta && $training == $param_CIG->val_char) || ($capability_linguagiunta && $training == $param_CIL->val_char) || ($capability_consiglio && $training == $param_CIC->val_char))) {
  print_error('nopermissions', 'error', '', 'formazione_individuale');
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('title_mod_prot_gratis', 'block_f2_formazione_individuale'));
*/
//$esitoUpd = upd_protocollo($n_prot, $id_course);
$esitoUpd = aggiorna_protocollo($n_prot, $id_course);
/*
print_r($esitoUpd);
echo "<br />";
echo "1: ".$esitoUpd->upd;
echo "<br />";
echo "2: ".$esitoUpd[upd];
echo "<br />";
echo "3: ".$esitoUpd['upd'];
*/
if ($esitoUpd['upd'] == 'ok') {
  //echo "<h3 style='color:green;text-align:center;'>".get_string('upd_prot_ok','block_f2_formazione_individuale')."</h3>";
  $upd_prot = 'ok';
} else if ($esitoUpd['err'] != '') {
  //echo "<h3 style='color:red;text-align: center;'>".get_string('upd_prot_err', 'block_f2_formazione_individuale')."</h3>";
  $upd_prot = 'ko';
}
$url_params2 = array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage, 'training'=>$training, 'dato_ricercato'=>$dato_ricercato, 'mod'=>$mod, 'upd_prot'=>$upd_prot);
$url = new moodle_url('/blocks/f2_formazione_individuale/ins_prot_senza_determina.php', $url_params2);
redirect($url);

//echo $OUTPUT->footer();

