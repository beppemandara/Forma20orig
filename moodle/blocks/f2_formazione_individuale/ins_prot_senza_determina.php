<?php
  global $PAGE, $SITE, $OUTPUT, $CFG;

  require_once('../../config.php');
  require_once 'lib_ind_senza_determina.php';

  require_login();

  $dato_ricercato  = optional_param('dato_ricercato', '', PARAM_ALPHANUM);
  $sort            = optional_param('sort', 'ASC', PARAM_ALPHANUM);
  $sortprot        = optional_param('sortprot', 'ASC', PARAM_ALPHANUM);
  $column          = optional_param('column', 'cognome', PARAM_TEXT);
  $columnprot      = optional_param('columnprot', 'prot', PARAM_TEXT);
  $dir             = optional_param('dir', 'ASC', PARAM_ALPHA);
  $dirprot         = optional_param('dirprot', 'ASC', PARAM_ALPHA);
  $page            = optional_param('page', 0, PARAM_INT);
  $perpage         = optional_param('perpage', 10, PARAM_INT);       // how many per page
  $training        = optional_param('training', '', PARAM_TEXT);
  $chk_selezionate = optional_param('id_course', null, PARAM_RAW);
  $n_prot          = optional_param('n_prot', '', PARAM_ALPHANUM);
  $mod             = optional_param('mod', 0, PARAM_INT);            //Se abilitata la modifica = 1
  $ret             = optional_param('ret',0, PARAM_INT);
  $ret_mod         = optional_param('ret_mod',0, PARAM_INT);
  $upd_prot        = optional_param('upd_prot', '', PARAM_TEXT);
  $ordinamento     = optional_param('ordinamento', '', PARAM_TEXT);

  $label_training = get_label_training($training);
  $url_mod = '';
  if($mod) {
    $url_mod = '&mod=1';
  }

  $sitecontext = context_system::instance();

  $blockname = get_string('pluginname', 'block_f2_formazione_individuale');

  $url_params = array(
                      'sort' => $sort, 
                      'sortprot' => $sortprot,
                      'dir' => $dir, 
                      'dirprot' => $dirprot,
                      'perpage' => $perpage, 
                      'training'=>$training, 
                      'dato_ricercato'=>$dato_ricercato, 
                      'mod'=>$mod, 
                      'ordinamento'=>$ordinamento,
                      'n_prot'=>$n_prot
                     );
  //$url_params = array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage,'training'=>$training,'n_prot'=>$n_prot, 'mod'=>$mod);
  $url = new moodle_url('/blocks/f2_formazione_individuale/ins_prot_senza_determina.php', $url_params);
  $gestionecorsi_url = new moodle_url("{$CFG->wwwroot}/blocks/f2_formazione_individuale/gest_corsi_ind_senza_determina.php?training=".$training."");
  $PAGE->set_url($url);
  $PAGE->set_context($sitecontext);
  $PAGE->set_pagelayout('standard');
  $PAGE->requires->js('/blocks/f2_formazione_individuale/js/gestione_corsi.js');
  $PAGE->set_title(get_string('title_ins_prot_gratis', 'block_f2_formazione_individuale'));
  $PAGE->settingsnav;
  $PAGE->navbar->add(get_string('formazione_individuale', 'block_f2_formazione_individuale'));
  $PAGE->navbar->add(get_string($label_training, 'block_f2_formazione_individuale'));
  $PAGE->navbar->add(get_string('gestionecorsigratis', 'block_f2_formazione_individuale'), $gestionecorsi_url);
  //$PAGE->navbar->add(get_string('creazioneanagrafica', 'block_f2_formazione_individuale'));
  $PAGE->navbar->add(get_string('ins_prot_gratis', 'block_f2_formazione_individuale'), $url);
  $PAGE->set_heading($SITE->shortname.': '.$blockname);

  $capability_giunta = has_capability('block/f2_formazione_individuale:individualigiunta', $sitecontext);
  $capability_linguagiunta = has_capability('block/f2_formazione_individuale:individualilinguagiunta', $sitecontext);
  $capability_consiglio = has_capability('block/f2_formazione_individuale:individualiconsiglio', $sitecontext);
  $param_CIG = get_parametro('p_f2_corsi_individuali_giunta');
  $param_CIL = get_parametro('p_f2_corsi_individuali_lingua_giunta');
  $param_CIC = get_parametro('p_f2_corsi_individuali_consiglio');

  if(!(($capability_giunta && $training == $param_CIG->val_char) || ($capability_linguagiunta && $training == $param_CIL->val_char) || ($capability_consiglio && $training == $param_CIC->val_char))) {
   print_error('nopermissions', 'error', '', 'formazione_individuale');
  }

  $id_del_corso = 0;
  if(isset($_POST['id_course'])) {
    $id_del_corso = $_POST['id_course'];
  }

  if (isset($_POST['submit_insert_protocollo'])) {
    ob_start();
    $dati_ins_prot = array();
    if ($n_prot != '') {
      if ((isset($_POST['id_course'])) && ($n_prot != '')) {
        //$dati_ins_prot = array();
        $id_corsi_ind = $_POST['id_course'];
        foreach ($id_corsi_ind as $id_corso) {
          add_to_log_prot_corsi_ind_senza_determina('id: '.$id_corso.' - prot: '.$n_prot);
          //$dati_invio[] = prepare_mail_autorizzazione_senza_determina($id_corso);
          //if (!add_prot_to_corsiind_senza_determina($id_corso, $n_prot)) {
          if (!upd_prot_to_corsiind_senza_determina($id_corso, $n_prot)) {
            $dati_ins_prot[] = 'Corso '.$id_corso.': inserimento numero protocollo non effettuato';
          }
          $dati_ins_prot[] = 'Corso '.$id_corso.': inserimento protocollo numero '.$n_prot.' effettuato';
        }
      }
    } else {
      $dati_ins_prot[] = 'Numero di protocollo non pervenuto';
    }
    $str_javascript= "<table width='100%'><tr><td align=left valign=top class='clsBold'><b>Esito Inserimento</b></td></tr>";
    foreach ($dati_ins_prot as $dati) {
      $str_javascript .= "<tr>";
      $str_javascript .= "<td>".$dati."</td>";
      $str_javascript .= "</tr>";
    }
    $str_javascript.= "</table>";
    echo '<input type="hidden" name="dati_ins_prot" id="dati_ins_prot" value="'.$str_javascript.'"/>';
    // pop up
    $str1 = <<<'EFO'
<script type="text/javascript">
//<![CDATA[
myWindow=window.open('','','width=1000,height=600,scrollbars=yes');
var dati_ins_prot = document.getElementById("dati_ins_prot").value;
myWindow.document.write("<h3>Inserimento protocollo</h3><table width='100%'><tr><td align='right'><input align='right' type='button' height='55' title='Stampa questa pagina' value='Stampa' onclick='window.print()'/></td></tr></table>");
myWindow.document.write(dati_ins_prot);
myWindow.focus();
//]]>
</script>
EFO;
    echo $str1;
    ob_flush();
    redirect(new moodle_url('/blocks/f2_formazione_individuale/ins_prot_senza_determina.php?training='.$training.'&step=start'));

  }

  $data = new stdClass;
  $data->tipo_corso = $training;
  $data->n_prot = $n_prot;
  $data->dato_ricercato = $dato_ricercato;
  $pagination = array(
                      'perpage' => $perpage, 
                      'page'=>$page, 
                      'column'=>$column, 
                      'columnprot'=>$columnprot,'columnprot'=>$columnprot,
                      'sort'=>$sort, 
                      'sortprot'=>$sortprot,
                      'ordinamento'=>$ordinamento,
                      'mod'=>$mod
                     );
  foreach ($pagination as $key=>$value) {
    $data->$key = $value;
  }

  $datiall = get_corsi_ind($data, $mod);
  $courses = $datiall->dati;
  $total_rows = $datiall->count;

  echo $OUTPUT->header();

  $str = <<<EOF
  <script type="text/javascript">
  //<![CDATA[
  function validateChecked (check) {
    var frm = document.getElementById('course_frm');
    for (var i =0; i < frm.elements.length; i++) {
      frm.elements[i].checked = false;
    }
    check.checked = true;
  }
  function checkAll(from,to) {
    var i = 0;
    var chk = document.getElementsByName(to);
    var resCheckBtn = document.getElementsByName(from);
    var resCheck = resCheckBtn[i].checked;
    var tot = chk.length;
    for (i = 0; i < tot; i++) chk[i].checked = resCheck;
    num_check_checked();
  }
  function confirmSubmitInsProt(conferma) {
    var chk = document.getElementsByName("id_course[]");
    var chkprot = document.getElementsByName("n_prot");
    //alert("prot: "+chkprot[0].value);
    var tot = chk.length;
    var num = 0;
    if (chkprot[0].value == '') {
      alert("Non e' stato inserito alcun numero di protocollo.");
      return false;
    }
    for (i = 0; i < tot; i++) {
      if (chk[i].checked) num++;
    }
    if(conferma=='insprot') {
      if(num==1) {
        conferma="Stai inserendo il numero di protocollo per un corso. Proseguire?";
      } else if(num>1) {
        conferma="Stai inserendo il numero di protocollo per piu' corsi. Proseguire?";
      }
    }
    if(num > 0) {
      return confirm(conferma);
    } else {
      alert("Non e' stato selezionato alcun corso.");
      return false;
    }
  }
  //]]>
  </script>
EOF;
  echo $str;

  if($mod) {
    echo $OUTPUT->heading(get_string('title_mod_prot_gratis', 'block_f2_formazione_individuale'));
  } else {
    echo $OUTPUT->heading(get_string('title_ins_prot_gratis', 'block_f2_formazione_individuale'));
  }

  if ($upd_prot == 'ok') {
    echo "<h3 style='color:green;text-align:center;'>".get_string('upd_prot_ok','block_f2_formazione_individuale')."</h3>";
  } else if ($upd_prot == 'ko') {
    echo "<h3 style='color:red;text-align: center;'>".get_string('upd_prot_err', 'block_f2_formazione_individuale')."</h3>";
  }

  echo html_writer::start_tag('form', array('action' => $url, 'method' => 'post'));
  // Submit ricerca per cognome
  echo '<table><tr>';
  echo '<td>Cognome: <input maxlength="254" size="50" name="dato_ricercato" type="text" id="id_dato_ricercato" value="'.$dato_ricercato.'" /></td>';
  echo '<td><input name="Cerca" value="Cerca" type="submit" id="id_Cerca" /></td>';
  echo '<td><input name="cancella" type="reset" value="Cancella" onclick="parent.location=\'ins_prot_senza_determina.php?training=CIG&cancella=1\'" /></td>';
  echo '</tr></table>';
  echo html_writer::end_tag('form');

/*
  echo html_writer::start_tag('form', array('action' => $url, 'method' => 'post'));
  echo '<table><tr>';
  echo '<td>Protocollo: <input maxlength="30" size="30" name="n_prot" type="text" id="id_n_prot" value="'.$n_prot.'"/></td>';
  //echo '<td><input name="ins_prot_sd" value="Inserisci" type="submit" id="id_ins_prot_sd" /></td>';
  echo '</tr></table>';
  echo html_writer::end_tag('form');
*/

  // record visualizzati:
  $coursescount = $total_rows;
  //add_to_log_corsi_ind_senza_determina('num corsi x protocollo: '.$coursescount);
  //add_to_log_prot_corsi_ind_senza_determina('num corsi x protocollo: '.$coursescount);
  if ($coursescount == 0) {
    echo $OUTPUT->container(get_string('nessun_corso_trovato','block_f2_formazione_individuale'), 'userinfobox', 'msgnodata');
    $table = NULL;
  } else {
    echo '<table style="width:100%;"><tr>';
    echo "<td style='width:80%;'><b style='font-size:11px'>".get_string('count_tot_rows', 'local_f2_traduzioni',$coursescount)."</b></td>";
    if (!$mod) {
      echo "<td><b style='font-size:11px'>".get_string('elementi_selezionati', 'local_f2_traduzioni').": <span id='span_elementi_sel'><span></b></td>";
    }
    echo '</tr></table>';
    $columndir = $sort == "ASC" ? "DESC":"ASC";
    $columnicon = $sort == "ASC" ? "down":"up";
    $columnicon = " <img src=\"" . $OUTPUT->pix_url('t/' . $columnicon) . "\" alt=\"\" />";
    $columndirprot = $sortprot == "ASC" ? "DESC":"ASC";
    $columniconprot = $sortprot == "ASC" ? "down":"up";
    $columniconprot = " <img src=\"" . $OUTPUT->pix_url('t/' . $columniconprot) . "\" alt=\"\" />";

    // COGNOME
    $icon="";
    if ($column == "cognome") $icon=$columnicon;
    $column_cognome = "<a href=\"ins_prot_senza_determina.php?sort=$columndir&amp;column=cognome&training=".$training.$url_mod."&dato_ricercato=".$dato_ricercato."&n_prot=".$n_prot."&page=".$page."&perpage=".$perpage."\">".get_string('cognome', 'block_f2_formazione_individuale')."</a>$icon";
    // NOME
    $icon="";
    if ($column == "nome") $icon=$columnicon;
    $column_nome = "<a href=\"ins_prot_senza_determina.php?sort=$columndir&amp;column=nome&training=".$training.$url_mod."&dato_ricercato=".$dato_ricercato."&n_prot=".$n_prot."&ordinamento=orderbynome&page=".$page."&perpage=".$perpage."\">".get_string('nome', 'block_f2_formazione_individuale')."</a>$icon";
    // PROT
    $iconprot = "";
    if ($columnprot == "prot") $iconprot=$columniconprot;
    $column_prot = "<a href=\"ins_prot_senza_determina.php?sortprot=$columndirprot&amp;columnprot=prot&training=".$training.$url_mod."&dato_ricercato=".$dato_ricercato."&ordinamento=orderbyprot&page=".$page."&perpage=".$perpage."\">".get_string('protocollo', 'block_f2_formazione_individuale')."</a>$iconprot";

    $table = new html_table();
    $table->head = array ();
    $table->align = array();
    $table->size = array();
    $table->wrap = array(null,'nowrap');
    //$table->head[] = html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/edit'), 'alt'=>get_string('modifica_corso', 'block_f2_formazione_individuale'), 'class'=>'iconsmall'));
    $table->head[] = '';
    $table->head[] = '<input type=checkbox name="id_course_all" value="" onClick="checkAll(\'id_course_all\',\'id_course[]\');">';
    $table->align[] = 'center';
    $table->size[] = '';
    $table->attributes = array();
    $table->head[] = $column_cognome."/".$column_nome;
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
    $table->size[] = '';
    //$table->head[] = get_string('protocollo', 'block_f2_formazione_individuale');
    $table->head[] = $column_prot;
    $table->align[] = "center";
    $table->size[] = '';

    foreach ($courses as $course) {
      $checked = "";
      if (!empty($chk_selezionate)) {
        foreach ($chk_selezionate as $key => $chk) {
	  if ($course->id == $chk) {
	    $checked = "checked='checked'";
	    unset($chk_selezionate[$key]);
	    break;
	  }
        }
      }
      $marticola = get_forzatura_or_moodleuser_ind($course->username);
      $numProt = $course->prot; // 2017/07/17
      //$numProt = get_num_protocollo($course->id); // protocollo
      $row = array ();
      if (($numProt || ($numProt != '')) && ($numProt != '-')) {
        $row[] = html_writer::link(new moodle_url('modifica_protocollo_senza_determina.php', array('id_course'=>$course->id,'training' => $training,'mod' =>$mod,'userid' => $course->userid)),html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/edit'), 'alt'=>get_string('mod_prot_gratis', 'block_f2_formazione_individuale'), 'class'=>'iconsmall')), array('title'=>get_string('mod_prot_gratis', 'block_f2_formazione_individuale')));
      } else {
        $row[]= html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/block'), 'alt'=>get_string('mod_prot_gratis', 'block_f2_formazione_individuale'), 'class'=>'iconsmall')); 
      }
      if (($numProt || ($numProt != '')) && ($numProt != '-')) {
        $row[]= html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/block'), 'alt'=>get_string('mod_prot_gratis', 'block_f2_formazione_individuale'), 'class'=>'iconsmall'));
      } else { 
        $row[] = '<input type="checkbox" id="'.$course->id.'" name="id_course[]" '.$checked.' value="'.$course->id.'">';
      }
      $row[] = $course->cognome." ".$course->nome;
      $row[] = $marticola->idnumber;
      $row[] = date('d/m/Y',$course->data_inizio);
      $row[] = $course->titolo;
      //$row[] = get_num_protocollo($course->id); // protocollo 
      $row[] = $numProt;
      $table->data[] = $row;
      //add_to_log_corsi_ind_senza_determina('matricola: '.$marticola->idnumber);
      //add_to_log_corsi_ind_senza_determina('titolo: '.$course->titolo);
    } // fine foreach
  }

  echo '<form id="course_frm" action="ins_prot_senza_determina.php?training='.$training.'" method="post">';

  echo '<table><tr>';
  echo '<td>Protocollo: <input maxlength="30" size="30" name="n_prot" type="text" id="id_n_prot" value="'.$n_prot.'"/></td>';
  if (!$mod) {
    echo '<td>';
    echo '<input type="submit" name="submit_insert_protocollo" onClick="return confirmSubmitInsProt(\'insprot\')" value="'.get_string('ins_prot_gratis', 'block_f2_formazione_individuale').'" />';
    echo '</td>';
  }
  echo '</tr></table>';

  if (!empty($chk_selezionate)) {
    foreach ($chk_selezionate as $chk) {
      echo '<input type="hidden" id="'.$chk.'" name="id_course[]" value="'.$chk.'">';
    }
  }

  if (!empty($n_prot)) {
    echo '<input type="hidden" id="ins_prot_id" name="num_prot_sd" value="'.$n_prot.'">';
  }

  if (!empty($table)) {
    echo $OUTPUT->paging_bar($coursescount, $page, $perpage, $url);
    echo html_writer::table($table);
    echo $OUTPUT->paging_bar($coursescount, $page, $perpage, $url);
  }

  echo '<table><tr><td align="center">';
  echo '<input type="button" value="'.get_string("indietro", "block_f2_formazione_individuale").'" onclick="parent.location=\''.$gestionecorsi_url.'\'">';
  echo '</td>';
  /*if (!$mod) {
    echo '<table><tr><td>';
    echo '<input type="submit" name="submit_insert_protocollo" onClick="return confirmSubmitInsProt(\'insprot\')" value="'.get_string('ins_prot_gratis', 'block_f2_formazione_individuale').'" />';
    //echo '<input type="button" value="'.get_string("ins_prot_gratis", "block_f2_formazione_individuale").'" onclick="parent.location=\'ins_prot_senza_determina.php?training='.$training.'\'">';
    echo '</td></tr></table>';
  }*/
  echo '</tr></table>';
  echo '</form>';

  echo $OUTPUT->footer();
