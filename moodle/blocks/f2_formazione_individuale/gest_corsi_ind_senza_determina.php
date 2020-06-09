<?php
global $OUTPUT, $PAGE, $SITE, $CFG;

require_once '../../config.php';
require_once 'lib_ind_senza_determina.php';

require_login();

$dato_ricercato      = optional_param('dato_ricercato', '', PARAM_ALPHANUM);
$dato_protocollo     = optional_param('dato_protocollo', '', PARAM_ALPHANUMEXT);
//$dato_protocollo     = optional_param('dato_protocollo', '', PARAM_ALPHANUM);
$sort                = optional_param('sort', 'ASC', PARAM_ALPHANUM);
$sortprot            = optional_param('sortprot', 'ASC', PARAM_ALPHANUM);
$column              = optional_param('column', 'cognome', PARAM_TEXT);
$columnprot          = optional_param('columnprot', 'prot', PARAM_TEXT);
$dir                 = optional_param('dir', 'ASC', PARAM_ALPHA);
$dirprot             = optional_param('dirprot', 'ASC', PARAM_ALPHA);
$page                = optional_param('page', 0, PARAM_INT);
$perpage             = optional_param('perpage', 10, PARAM_INT);       // how many per page
$training            = optional_param('training', '', PARAM_TEXT);
$mod                 = optional_param('mod', 0, PARAM_INT);            //Se abilitata la modifica = 1
$ret                 = optional_param('ret',0, PARAM_INT);
$ret_mod             = optional_param('ret_mod',0, PARAM_INT);
$mdp                 = optional_param('mdp',0, PARAM_INT);
$ret_cp              = optional_param('ret_cp',0, PARAM_INT);
$chk_selezionate     = optional_param('id_course', null, PARAM_RAW);
$esito_archiviazione = optional_param('arc', -1, PARAM_INT);
$cancella            = optional_param('cancella', -1, PARAM_INT);
$ordinamento         = optional_param('ordinamento', '', PARAM_TEXT);

if ($cancella == 1) {
  $dato_ricercato = '';
  $dato_protocollo = '';
}

$label_training = get_label_training($training);
$url_mod = '';
if($mod) {
  $url_mod = '&mod=1';
}

$esito_im = '';

$url_params = array(
                    'sort' => $sort, 
                    'sortprot' => $sortprot, 
                    'dir' => $dir, 
                    'dirprot' => $dirprot, 
                    'perpage' => $perpage,
                    'training'=>$training,
                    'dato_ricercato'=>$dato_ricercato,
                    'dato_protocollo'=>$dato_protocollo,
                    'ordinamento'=>$ordinamento,
                    'mod'=>$mod
                   );
$baseurl = new moodle_url('/blocks/f2_formazione_individuale/gest_corsi_ind_senza_determina.php', $url_params);
$blockname = get_string('pluginname', 'block_f2_formazione_individuale');
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/f2_formazione_individuale/gest_corsi_ind_senza_determina.php');
$PAGE->requires->js('/blocks/f2_formazione_individuale/js/gestione_corsi.js');
if($mod) {
  $PAGE->set_title(get_string('titlemodificacorsigratis', 'block_f2_formazione_individuale'));
} else {
  $PAGE->set_title(get_string('titlegestionecorsigratis', 'block_f2_formazione_individuale'));
}
$PAGE->settingsnav;
$PAGE->navbar->add(get_string($label_training, 'block_f2_formazione_individuale'));
if($mod) {
  $PAGE->navbar->add(get_string('modificacorsigratis', 'block_f2_formazione_individuale'), $baseurl);
} else {
  $PAGE->navbar->add(get_string('gestionecorsigratis', 'block_f2_formazione_individuale'), $baseurl);
}
$PAGE->set_heading($SITE->shortname.': '.$blockname);

$capability_giunta = has_capability('block/f2_formazione_individuale:individualigiunta', $context);
$capability_linguagiunta = has_capability('block/f2_formazione_individuale:individualilinguagiunta', $context);
$capability_consiglio = has_capability('block/f2_formazione_individuale:individualiconsiglio', $context);
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

$elimina = optional_param('elimina', '', PARAM_TEXT);
if(isset($_POST['elimina'])) {           // ELIMINA CORSI
  $esito_elimina = 1;
  foreach ($id_del_corso as $id_del) {
    $esito_del = delete_corsi_ind($id_del);
    if (!$esito_del) {
      $esito_elimina = 0;
    }
  }
}

if (isset($_GET['step']) && $_GET['step'] == 'start') {
  $_POST['id_course'] = '';
}
if (isset($_POST['submit_invio_autorizzazioni'])) {
  ob_start();
  if (isset($_POST['id_course'])) {
    $dati_invio = array();
    $id_corsi_ind = $_POST['id_course'];
    foreach ($id_corsi_ind as $id_corso) {
      add_to_log_corsi_ind_senza_determina('id: '.$id_corso);
      $dati_invio[] = prepare_mail_autorizzazione_senza_determina($id_corso);
    }
    $dati_invio_new = $dati_invio;
    /*foreach ($dati_invio_new as $k => $v) {
      foreach ($v as $x => $y) {
        add_to_log_corsi_ind_senza_determina('k: '.$x.' - v: '.$y);
      }
    }*/
    $str_javascript= "<table width='100%'><tr><td align=left valign=top class='clsBold'><b>Utente</b></td><td align=left valign=top class='clsBold'><b>Matricola</b></td><td align=left valign=top class='clsBold'><b>E-mail</b></td><td align=left valign=top class='clsBold'><b>Data invio</b></td><td align=left valign=top class='clsBold'><b>Esito invio</b></td></tr>";
    foreach ($dati_invio as $dati) {
      //$esito_im = $dati->lastname.' '.$dati->firstname.' - '.$dati->titolo;
      $esito_im = $dati->titolo;
      if ($dati->error_mail == 1) {
        $esito_im .= ' - Errore email non inviata';
      } else if($dati->error_mail  == 2) {
        $esito_im .= ' - Errore formato email non corretto';
      } else if($dati->error_mail  == 3) {
        $esito_im .= ' - Modello comunicazione: non inviare mail';
      } else {
        $esito_im .= ' email Inviata';
      }
      //$esito_im .= '<br />';
      add_to_log_corsi_ind_senza_determina('id: '.$esito_im);
      $str_javascript .= "<tr>";
      $str_javascript .= "<td>".$dati->lastname." ".$dati->firstname."</td><td>$dati->matricola</td><td>$dati->mailto</td><td>".date('d/m/Y H:i',time())."</td><td>".$esito_im."</td>";
      $str_javascript .= "</tr>";
    }
    $str_javascript.= "</table>";
    echo '<input type="hidden" name="dati_invio" id="dati_invio" value="'.$str_javascript.'"/>';
    //add_to_log_corsi_ind_senza_determina('js: '.$str_javascript);
    // pop up
    $str1 = <<<'EFO'
<script type="text/javascript">
//<![CDATA[
myWindow=window.open('','','width=1000,height=600,scrollbars=yes');
var dati_invio = document.getElementById("dati_invio").value;
myWindow.document.write("<h3>Invio email di autorizzazione</h3><table width='100%'><tr><td align='right'><input align='right' type='button' height='55' title='Stampa questa pagina' value='Stampa' onclick='window.print()'/></td></tr></table>");
myWindow.document.write(dati_invio);
myWindow.focus();
//]]>
</script>
EFO;
    echo $str1;
  }
  ob_flush();
  redirect(new moodle_url('/blocks/f2_formazione_individuale/gest_corsi_ind_senza_determina.php?training='.$training.'&step=start&txtsendauth=yes'));
}

/*
$invioauth = optional_param('invioauth', '', PARAM_TEXT);
if(isset($_POST['invioauth'])) {           // INVIA MAIL AUTORIZZAZIONE
  $esito_invioauth = 1;
  foreach ($id_del_corso as $id_send) {
    $esito_send = prepare_mail_autorizzazione_senza_determina($id_send);
    if (!$esito_send) {
      $esito_invioauth = 0;
    }
  }
}
*/

$data = new stdClass;
$data->tipo_corso = $training;
$data->dato_ricercato = $dato_ricercato;
$data->dato_protocollo = $dato_protocollo;
$pagination = array(
                    'perpage' => $perpage, 
                    'page'=>$page,
                    'column'=>$column,
                    'columnprot'=>$columnprot,
                    'sort'=>$sort,
                    'sortprot'=>$sortprot,
                    'ordinamento'=>$ordinamento,
                    'mod'=>$mod
                   );
foreach ($pagination as $key=>$value) {
  $data->$key = $value;
}

$datiall = get_corsi_ind($data,$mod);
$courses = $datiall->dati;
$total_rows = $datiall->count;

echo $OUTPUT->header();
$str = <<<'EFO'
<script type="text/javascript">
//<![CDATA[
function validateChecked (check) {
  var frm = document.getElementById('course_frm');
  for (var i =0; i < frm.elements.length; i++) {
    frm.elements[i].checked = false;
  }
  check.checked = true;
}
function confirmSubmitElimina(conferma) {
  var chk = document.getElementsByName("id_course[]");
  var tot = chk.length;
  var num = 0;
  for (i = 0; i < tot; i++) {
    if(chk[i].checked) num++;
  }
  if(conferma=='elimina') {
    if(num==1) {
      conferma="Stai eliminando un corso. Proseguire?";
    } else if(num>1) {
      conferma="Stai eliminando piu' corsi. Proseguire?";
    }
  }
  if(num > 0) {
    return confirm(conferma);
  } else {
    alert("Non e' stato selezionato nessun corso.");
    return false;
  }
}
function confirmSubmitCancella(cancella) {
  var cognome = document.getElementsByName("dato_ricercato");
  var protocollo = document.getElementsByName("dato_protocollo");
  var cognome = '';
  var protocollo = '';
  cancella = "Stanno per essere ripuliti i campi di ricerca. Proseguire?" + "cognome" + cognome;
  return confirm(cancella);
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
function confirmSubmitInvioAuth(conferma) {
  var chk = document.getElementsByName("id_course[]");
  var tot = chk.length;
  var num = 0;
  for (i = 0; i < tot; i++) {
    if (chk[i].checked) num++;
  }
  if(conferma=='invioauth') {
    if(num==1) {
      conferma="Stai inviando la mail di autorizzazione per un corso. Proseguire?";
    } else if(num>1) {
      conferma="Stai inviando mail di autorizzazione per piu' corsi. Proseguire?";
    }
  }
  if(num > 0) {
    return confirm(conferma);
  } else {
    alert("Non e' stato selezionato nessun corso.");
    return false;
  }
}
//]]>
</script>
EFO;
echo $str;

if($mod) {
  echo $OUTPUT->heading(get_string('modificacorsigratis', 'block_f2_formazione_individuale'));
} else {
  echo $OUTPUT->heading(get_string('titlegestionecorsigratis', 'block_f2_formazione_individuale'));
}

if($ret == 1) {
  echo "<h3 style='color:green;text-align:center;'>".get_string('insert_correct','block_f2_formazione_individuale')."</h3>";
} else if($ret == -1) {
  echo "<h3 style='color:red;text-align: center;'>".get_string('insert_error', 'block_f2_formazione_individuale')."</h3>";
}

if($ret_mod == 1) {
  echo "<h3 style='color:green;text-align:center;'>".get_string('corso_modificato','block_f2_formazione_individuale')."</h3>";
} else if($ret_mod == -1) {
  echo "<h3 style='color:red;text-align:center;'>".get_string('errore_modifica_corso','block_f2_formazione_individuale')."</h3>";
}

// esito invio mail di autorizzazione
if ($esito_im != '') {
  echo "<h3 style='color:blue;text-align:center;'>".$esito_im."</h3>";
}
//echo "<h3 style='color:blue;text-align:center;'>START - ".$esito_im." - END</h3>";

// copia corso
if($ret_cp == 1) {
  echo "<h3 style='color:green;text-align: center;'>".get_string('ret_cp_ok', 'block_f2_formazione_individuale')."</h3>";
} else if($ret_cp == -1) {
  echo "<h3 style='color:red;text-align: center;'>".get_string('ret_cp_err', 'block_f2_formazione_individuale')."</h3>";
}

if(isset($_POST['elimina'])) {
  if($esito_elimina == 1) {
    echo "<h3 style='color:green;text-align: center;'>".get_string('canc_ok', 'block_f2_formazione_individuale')."</h3>";
  } else if($esito_elimina == -1) {
    echo "<h3 style='color:red;text-align: center;'>".get_string('canc_ok', 'block_f2_formazione_individuale')."</h3>";
  }
  $chk_selezionate = array();
}

if($esito_archiviazione == 1) {
  echo '<h3 style="color:green;text-align: center;">'.get_string('corso_archiviato', 'block_f2_formazione_individuale').'</h3>';
} else if ($esito_archiviazione == 0) {
  echo '<h3 style="color:red;text-align: center;">'.get_string('errore_archiviazione', 'block_f2_formazione_individuale').'</h3>';
}

echo html_writer::start_tag('form', array('action' => $baseurl, 'method' => 'post'));
// Submit ricerca
echo '<table><tr>';
echo '<td colspan="2">Cognome: <input maxlength="254" size="50" name="dato_ricercato" type="text" id="id_dato_ricercato" value="'.$dato_ricercato.'" /></td>';
//echo '<td><input name="Cerca" value="Cerca" type="submit" id="id_Cerca" /></td>';
echo '</tr><tr>';
echo '<td colspan="2">Protocollo: <input maxlength="30" size="30" name="dato_protocollo" type="text" id="id_dato_protocollo" value="'.$dato_protocollo.'" /></td>';
//echo '<td><input name="Cercaprot" value="Cerca" type="submit" id="id_Cercaprot" /></td>';
echo '</tr><tr>';
echo '<td align="right"><input name="Cerca" value="Cerca" type="submit" id="id_Cerca" /></td>';
//echo '<td align="left"><input name="cancella" type="submit" value="Cancella" id="id_cancella" onClick="return confirmSubmitCancella(\'cancella\');" /></td>';
echo '<td align="left"><input name="cancella" type="reset" value="Cancella" onclick="parent.location=\'gest_corsi_ind_senza_determina.php?training=CIG&cancella=1\'" /></td>'; 
echo '</tr></table>';
echo html_writer::end_tag('form');

// record visualizzati:
$coursescount = $total_rows;
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
	$column_cognome = "<a href=\"gest_corsi_ind_senza_determina.php?sort=$columndir&amp;column=cognome&training=".$training.$url_mod."&dato_ricercato=".$dato_ricercato."&dato_protocollo=".$dato_protocollo."&page=".$page."&perpage=".$perpage."\">".get_string('cognome', 'block_f2_formazione_individuale')."</a>$icon";
  // NOME
  $icon="";
  if ($column == "nome") $icon=$columnicon;
	$column_nome = "<a href=\"gest_corsi_ind_senza_determina.php?sort=$columndir&amp;column=nome&training=".$training.$url_mod."&dato_ricercato=".$dato_ricercato."&dato_protocollo=".$dato_protocollo."&ordinamento=orderbynome&page=".$page."&perpage=".$perpage."\">".get_string('nome', 'block_f2_formazione_individuale')."</a>$icon";
  // PROT
  $iconprot = "";
  if ($columnprot == "prot") $iconprot=$columniconprot;
  $column_prot = "<a href=\"gest_corsi_ind_senza_determina.php?sortprot=$columndirprot&amp;columnprot=prot&training=".$training.$url_mod."&dato_ricercato=".$dato_ricercato."&dato_protocollo=".$dato_protocollo."&ordinamento=orderbyprot&page=".$page."&perpage=".$perpage."\">".get_string('protocollo', 'block_f2_formazione_individuale')."</a>$iconprot";

  $table = new html_table();
  $table->head = array ();
  $table->align = array();
  $table->size = array();
  $table->wrap = array(null,'nowrap');
  //$table->head[] = html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/edit'), 'alt'=>get_string('modifica_corso', 'block_f2_formazione_individuale'), 'class'=>'iconsmall'));
  $table->head[] = '';
  $table->head[] = '<input type=checkbox name="id_course_all" value="" onClick="checkAll(\'id_course_all\',\'id_course[]\');">';
/*
  if($mod) {
    $table->head[] = html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/edit'), 'alt'=>get_string('modifica_corso', 'block_f2_formazione_individuale'), 'class'=>'iconsmall'));
  } else {
    $table->head[] = '<input type=checkbox name="id_course_all" value="" onClick="checkAll(\'id_course_all\',\'id_course[]\');">';
  }
*/
  $table->align[] = 'left';
  $table->size[] = '';
  $table->attributes = array();
  $table->head[] = $column_cognome."/".$column_nome;
  $table->align[] = 'center';
  $table->size[] = '';
  $table->head[] = get_string('copia', 'block_f2_formazione_individuale');
  $table->align[] = 'center';
  $table->size[] = '';
  $table->head[] = get_string('archivia', 'block_f2_formazione_individuale');
  $table->align[] = 'center';
  $table->size[] = '';
/*
  if(!$mod) {
    $table->head[] = get_string('copia', 'block_f2_formazione_individuale');
    $table->align[] = 'center';
    $table->size[] = '';
  }
*/
  $table->head[] = get_string('matricola', 'block_f2_formazione_individuale');
  $table->align[] = 'center';
  $table->size[] = '';
  $table->head[] = get_string('datainizio', 'block_f2_formazione_individuale');
  $table->align[] = 'center';
  $table->size[] = '';
  $table->head[] = get_string('titolocorso', 'block_f2_formazione_individuale');
  $table->align[] = 'center';
  //$table->size[] = '90%';
  //$table->head[] = get_string('protocollo', 'block_f2_formazione_individuale');
  $table->head[] = $column_prot;
  $table->align[] = 'center';
  $table->size[] = '';
  $table->head[] = get_string('data_invio_mail', 'block_f2_formazione_individuale');
  $table->align[] = 'center';
  $table->size[] = '';
  //$table->width = "95%";
  $table->head[] = get_string('dett_invio_mail', 'block_f2_formazione_individuale');
  $table->align[] = 'center';
  $table->size[] = '';
  //$table->width = "95%";

  $giorni_copia_prefix = get_parametri_by_prefix('p_f2_corsiind_giorni_copia_corso');
  $giorni_copia = $giorni_copia_prefix['p_f2_corsiind_giorni_copia_corso']->val_int;

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
    $row = array ();
    //if ($mod) {
      $row[]= html_writer::link(new moodle_url('modifica_anagrafica_senza_determina.php', array('id_course'=>$course->id,'training' => $training,'mod' =>$mod,'userid' => $course->userid)),html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/edit'), 'alt'=>get_string('modifica_corso', 'block_f2_formazione_individuale'), 'class'=>'iconsmall')), array('title'=>get_string('modifica_corso', 'block_f2_formazione_individuale')));
     // $row[] = $course->cognome." ".$course->nome;
   // } else {
      $row[] = '<input type="checkbox" id="'.$course->id.'" name="id_course[]" '.$checked.' value="'.$course->id.'">';
      $row[] = $course->cognome." ".$course->nome;
//      if($course->data_inizio >= strtotime('-'.$giorni_copia.' day',time())) {
        $row[]= html_writer::link(new moodle_url('user_copy_senza_determina.php', array('id_course'=>$course->id,'training' => $training)),get_string('copia', 'block_f2_formazione_individuale'), array('title'=>get_string('copia', 'block_f2_formazione_individuale')));
//      } else {
//        $row[]='';
//      }
 //   }
    // archiviazione
    if ($course->storico <= 0) {
      if ($course->prot != '-') {
        $row[]= html_writer::link(new moodle_url('archivia_corso_senza_determina.php', array('id_course'=>$course->id,'training' => $training)),get_string('archivia', 'block_f2_formazione_individuale'), array('title'=>get_string('archivia', 'block_f2_formazione_individuale')));
      } else {
        $row[]=' - ';
      }
    } else {
      $row[]='Archiviato';
    }
    // archiviazione
    $row[] = $marticola->idnumber;
    $row[] = date('d/m/Y',$course->data_inizio);
    $row[] = $course->titolo;
    //$row[] = get_num_protocollo($course->id); // protocollo 
    $row[] = $course->prot;                     // modifica del 17/07/2017
    //add_to_log_corsi_ind_senza_determina('modello mail: '.$course->modello_email);
    if ($course->modello_email == "-1") {
      //$row[] = 'non disponibile';
      $row[] = html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/emailno'), 'alt'=>get_string('mail_no_disp', 'block_f2_formazione_individuale'), 'class'=>'iconsmall'));
      //$row[] = 'comunicazione senza mail';
      $row[] = 'mod. e-mail: non inviare e-mail';
    } else {
      if ($course->data_invio_mail != '') {
        $row[] = date('d/m/Y',$course->data_invio_mail);
        $row[] = html_writer::link(new moodle_url('user_auth_mail_senza_determina.php', array('id_course'=>$course->id,'training' => $training)),get_string('dettaglio_mail', 'block_f2_formazione_individuale'), array('title'=>get_string('dettaglio_mail', 'block_f2_formazione_individuale')));
      } else {
        $row[] = 'mail non inviata';
        $row[] = ' --- ';
      }
    }
    //$row[] = html_writer::link(new moodle_url('user_auth_mail.php', array('id_course'=>$course->id,'training' => $training)),get_string('mail', 'block_f2_formazione_individuale'), array('title'=>get_string('mail', 'block_f2_formazione_individuale')));
    $table->data[] = $row;
  }
}

echo '<form id="course_frm" action="gest_corsi_ind_senza_determina.php?training='.$training.'" method="post">';
echo '<input type="hidden" value="1" name="del" />';

if (!empty($chk_selezionate)) {
  foreach ($chk_selezionate as $chk) {
    echo '<input type="hidden" id="'.$chk.'" name="id_course[]" value="'.$chk.'">';
  }
}

if (!empty($table)) {
  echo $OUTPUT->paging_bar($coursescount, $page, $perpage, $baseurl);
  echo html_writer::table($table);
  echo $OUTPUT->paging_bar($coursescount, $page, $perpage, $baseurl);
}

if(!$mod) {
  echo '<table><tr><td>';
  echo '<input type="submit" name="elimina" onClick="return confirmSubmitElimina(\'elimina\');" value="Elimina" />';
  echo '</td><td>';
  echo '<input type="button" value="'.get_string("nuovo", "block_f2_formazione_individuale").'" onclick="parent.location=\'user_senza_determina.php?training='.$training.'\'">';
  echo '</td><td>';
  echo '<input type="submit" name="submit_invio_autorizzazioni" onClick="return confirmSubmitInvioAuth(\'invioauth\')" value="'.get_string('invio_autorizzazioni', 'block_f2_formazione_individuale').'" />';
  echo '</td><td>';
  echo '<input type="button" value="'.get_string("go_to_ins_prot_gratis", "block_f2_formazione_individuale").'" onclick="parent.location=\'ins_prot_senza_determina.php?training='.$training.'\'">';
  echo '</td><td>';
  echo '<input type="button" value="'.get_string("dwl_report_xls", "block_f2_formazione_individuale").'" onclick="parent.location=\'report_xls_corsi_senza_determina.php?training='.$training.'\'">';
  echo '</td><td>';
  echo '<input type="button" value="Consuntivo corsi" onclick="parent.location=\'report_xls_corsi_senza_determina_archiviati.php?training='.$training.'\'">';
  echo '</td></tr></table>';
}
echo '</form>';
echo $OUTPUT->footer();
