<?php
global $OUTPUT, $PAGE, $SITE, $CFG;

require_once '../../config.php';
require_once 'lib_ind_senza_determina.php';

require_login();

$sort      = optional_param('sort', 'ASC', PARAM_ALPHANUM);
$dir       = optional_param('dir', 'ASC', PARAM_ALPHA);
$perpage   = optional_param('perpage', 10, PARAM_INT);       // how many per page
$training  = optional_param('training', '', PARAM_TEXT);
$mod       = optional_param('mod', 0, PARAM_INT);            //Se abilitata la modifica = 1
$id_course = optional_param('id_course', null, PARAM_RAW);

$label_training = get_label_training($training);
$dato_ricercato = '';
$n_prot         = '';

$url_params = array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage, 'training'=>$training, 'mod'=>$mod);
$baseurl = new moodle_url('/blocks/f2_formazione_individuale/gest_corsi_ind_senza_determina.php', $url_params);
$url_params2 = array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage, 'training'=>$training, 'dato_ricercato'=>$dato_ricercato, 'mod'=>$mod, 'n_prot'=>$n_prot);
$url = new moodle_url('/blocks/f2_formazione_individuale/ins_prot_senza_determina.php', $url_params2);
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

$str = <<<EOF
<script type="text/javascript">
//<![CDATA[
function confirmSubmitModProt(conferma) {
  var chkprot = document.getElementsByName("n_prot");
  if (chkprot[0].value == '') {
    alert("Non e' stato inserito alcun numero di protocollo.");
    return false;
  }
  if (conferma=='modprot') {
    conferma="Stai modificando il numero di protocollo per un corso. Proseguire?";
  }
  return confirm(conferma);
}
//]]>
</script>
EOF;
echo $str;

//$course_ind = get_corso_ind_senza_spesa($id_course);
$dati_corso = get_dati_corso_senza_spesa($id_course);

echo $OUTPUT->heading(get_string('title_mod_prot_gratis', 'block_f2_formazione_individuale'));

echo "<h5>".get_string('riepilogo_informazioni_corso', 'block_f2_formazione_individuale').": ".$id_course."</h5><br>";
/*
echo '<table width="50%" style="white-space:nowrap; text-align:left;" class="generaltable"><tr>';
echo '<th style="text-align:left;" width="80%" class="header">'.get_string('titolo', 'block_f2_formazione_individuale').'</th>";
echo "<th style="text-align:left;" class="header">'.get_string('datainizio', 'block_f2_formazione_individuale').'</th>";
echo "</tr>";
echo '<tr><td>'.$course_ind->titolo."</td><td>".date('d/m/Y',$course_ind->data_inizio)."</td></tr>";
ec'ho "</table>";
*/
$table = new html_table();
$table->head[] = 'Utente';
$table->align[] = 'center';
$table->size[] = '';
$table->head[] = get_string('matricola', 'block_f2_formazione_individuale');
$table->align[] = 'center';
$table->size[] = '';
$table->head[] = get_string('datainizio', 'block_f2_formazione_individuale');
$table->align[] = 'center';
$table->size[] = '';
$table->head[] = get_string('titolocorso', 'block_f2_formazione_individuale');
$table->align[] = 'center';
$table->head[] = get_string('protocollo', 'block_f2_formazione_individuale');
$table->align[] = 'center';
$table->size[] = '';

$row = array ();
$row[] = $dati_corso->lastname.' '.$dati_corso->firstname;
$row[] = $dati_corso->idnumber;
$row[] = date("d/m/Y", $dati_corso->data_inizio);
$row[] = $dati_corso->titolo;
$row[] = $dati_corso->prot;

$table->data[] = $row;

echo html_writer::table($table);
echo "<br />";

echo '<form id="course_frm" action="mod_prot_senza_determina.php?training='.$training.'" method="post">';
echo '<input type="hidden" id="id_course" name="id_course" value="'.$id_course.'">';
echo '<table><tr>';
echo '<td>Protocollo: <input maxlength="30" size="30" name="n_prot" type="text" id="id_n_prot" value="'.$n_prot.'"/></td>';
echo '</tr></table>';
if (!empty($n_prot)) {
  echo '<input type="hidden" id="mod_prot_id" name="num_prot_sd" value="'.$n_prot.'">';
}
echo '<table><tr><td>';
echo '<input type="submit" name="submit_update_protocollo" onClick="return confirmSubmitModProt(\'modprot\')" value="'.get_string('mod_prot_gratis', 'block_f2_formazione_individuale').'" />';
echo '</td></tr></table>';
echo '</form>';

echo $OUTPUT->footer();
