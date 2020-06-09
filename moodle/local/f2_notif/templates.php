<?php
// $Id$
global $CFG,$DB,$OUTPUT;

require_once '../../config.php';
require_once $CFG->libdir . '/formslib.php';
require_once($CFG->dirroot.'/local/f2_notif/lib.php');
require_once($CFG->dirroot.'/f2_lib/report.php');

/*
 * AK-DL pagination: intestazioni necessarie per l'impaginazione e ordinamento
*/
$page     = optional_param('page', 0, PARAM_INT);
$perpage  = optional_param('perpage', 10, PARAM_INT);
$column   = optional_param('column', 'title', PARAM_TEXT);
$sort     = optional_param('sort', 'ASC', PARAM_TEXT);

require_login();

//$context = get_context_instance(CONTEXT_SYSTEM);
$context = context_system::instance();

$capability = has_capability('local/f2_notif:edit_notifiche', $context);
if(!$capability){
	print_error('nopermissions', 'error', '', 'notif');
}

if (empty($CFG->loginhttps)) {
	$securewwwroot = $CFG->wwwroot;
} else {
	$securewwwroot = str_replace('http:','https:',$CFG->wwwroot);
}

$PAGE->set_context($context);
$baseurl = new moodle_url('/local/f2_notif/templates.php', NULL);
$PAGE->set_url($baseurl);
//$PAGE->set_heading($SITE->shortname.': '.$blockname);
$PAGE->set_heading($SITE->shortname);
$PAGE->set_title(get_string('modelli_notifica', 'local_f2_notif'));
$PAGE->navbar->add(get_string('modelli_notifica', 'local_f2_notif'), new moodle_url($baseurl));
$PAGE->set_pagelayout('standard');
$PAGE->settingsnav;

include_fileDownload_before_header();

echo $OUTPUT->header();
/*
 * AK-DL pagination: intestazioni necessarie per includere javascript
*/
include_fileDownload_after_header();

echo $OUTPUT->heading(get_string('modelli_notifica', 'local_f2_notif'));

$notif_ind = get_tipo_notif_byname(F2_TIPO_NOTIF_INDIVIDUALE);
$str = <<<EOF
<script type="text/javascript">
//<![CDATA[
function checkAll(from,to)
{
	var i = 0;
	var chk = document.getElementsByName(to);
	var resCheckBtn = document.getElementsByName(from);
	var resCheck = resCheckBtn[i].checked;
	var tot = chk.length;
	for (i = 0; i < tot; i++) chk[i].checked = resCheck;
}
function confirmSubmitElimina()
{
    var chk = document.getElementsByName("id_temp[]");
    var tot = chk.length;
    var num = 0;
    for (i = 0; i < tot; i++) {
        if(chk[i].checked)
         num++;
    }
    if(num > 0)
    { 
        return confirm("Stai eliminando dei template. Proseguire?");
    }
    else
    {
        alert("Non e' stato selezionato nessun template.");
        return false;
    }		
}
//function change_notif(id)
//{	
//    if(id=={$notif_ind}){
//    document.getElementById("individuale_no").setAttribute('hidden','hidden');
//    }else{
//    document.getElementById("individuale_no").removeAttribute('hidden');
//    }
//}
//]]>
</script>
EOF;
echo $str;

echo $OUTPUT->box_start();
echo '<div class="contenitoreglobale">';

//INIZIO FORM
class modello_notifica_form extends moodleform {
	public function definition() {
		$mform =& $this->_form;
		
		$tipo_notif = get_tipo_notif(-1,'-1');//ricavo id-nome del tipo di notifica
		$array_tipo_notif = array('-1'=>'Tutti');
		foreach($tipo_notif as $notifica){
			$array_tipo_notif[$notifica->id] = ''.$notifica->nome.'';
		}
		
		$mform->addElement('text', 'nome',get_string('nome','local_f2_traduzioni'), 'maxlength="254" size="50"');
                $mform->setType('nome', PARAM_TEXT);
	    $select = $mform->addElement('select', 'id_tipo_notif', get_string('tipo', 'local_f2_notif'),$array_tipo_notif
                //, array('onchange'=>'change_notif(this.value)')
                );
		$select = $mform->addElement('select', 'stato', get_string('stato', 'local_f2_notif'),array('1'=>'Attivo','0'=>'Non attivo'));
		//$mform->addElement('html','<div id="individuale_no">');
			$select = $mform->addElement('select', 'canale', get_string('canale', 'local_f2_notif'),array('0'=>'Aula','1'=>'On-Line'));
		//$mform->addElement('html','</div>');
		$mform->addElement('submit', 'Cerca', 'Cerca');
	}
}
$mform = new modello_notifica_form(null);
$mform->display();
//FINE FORM
        if ($mform->is_submitted()) {
            $data = $mform->get_data();
        } else {
            $tipo_notif = get_tipo_notif();//ricavo id-nome del tipo di notifica
            // valori di default
            $data = new stdClass();
            $data->nome = '';
            $data->id_tipo_notif = -1;
            $data->canale = 0;
            $data->stato = 1;
            $data->page = $page;
            $data->perpage = $perpage;
            $data->sort = $sort;
            $data->column = $column;
        }
        
        // intestazioni tabella notifica
//        if($data->id_tipo_notif == $notif_ind){
//        	$head_table = array('chk_id_temp','edit','title','tipo_notif','stato');
//        }else{
        	$head_table = array('chk_id_temp','edit','title','tipo_notif','canale','stato','predefinito');
//        }
        $head_table_sort = array('title');
        $align = array ('left','left','left','left','left');
	
//	if ($data = $mform->get_data()) {	
		
		$pagination = array('perpage' => $perpage, 'page'=>$page,'column'=>$column,'sort'=>$sort);
		foreach ($pagination as $key=>$value){
			$data->$key = $value;
		}
		
//		if($data->id_tipo_notif == $notif_ind){// 3 è la notifica di tipo formazione individuale
//			$all_canale=1;
//		}else{
			$all_canale=0;
//		}
		$full_templates=get_templates($data,'',$all_canale);
		if($full_templates->count==0){
			echo $OUTPUT->box_start();
			echo '<BR>';
			echo '<form action="delete_templates.php" method="post">';				
			echo '<a href="add_templates.php"><button type="button">Nuovo</button></a>';	
			echo '<BR><BR>';
			echo '<b>'.get_string('no_record', 'local_f2_traduzioni').'</b><br>Seleziona il pulsante "Nuovo" per inserire un nuovo template.';
			echo $OUTPUT->box_end();
		}
		else{
	 		$form_id='mform1';										// ID del form dove fare il submit
			$post_extra=array('column'=>$column,'sort'=>$sort);		// dati extra da aggiungere al post del form
			$total_rows = $full_templates->count;
			$templates = $full_templates->dati;
			
			// INIZIO TABELLA TEMPLATE
			$table = new html_table();
			$table->align = $align;
			$table->head = build_head_table($head_table,$head_table_sort,$post_extra,$total_rows, $page, $perpage, $form_id);
			foreach ($templates as $template) {
				foreach(get_tipo_notif($template->id_tipo_notif) as $nome){$tipo_notif = $nome->nome; break;};
				$row = array();
				$row[] = '<input type=checkbox name="id_temp[]" value='.$template->id.'>';
				$row[] = html_writer::link(new moodle_url('edit_templates.php', array('id'=>$template->id
						)), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/edit'), 'alt'=>get_string('edit_template', 'local_f2_notif'), 'class'=>'iconsmall')), array('title'=>get_string('edit_template', 'local_f2_notif')));
						//	'<a href = detail_fornitori.php?id='.$template->id.'>'.$template->denominazione.'</a>',
				$row[] = $template->title;
				$row[] = $tipo_notif;
//				if($data->id_tipo_notif != $notif_ind)
					$row[] = ($template->canale ? get_string('on_line','local_f2_notif'): get_string('aula','local_f2_notif'));	
				$row[] = ($template->stato ? get_string('attivo','local_f2_notif'): get_string('non_attivo','local_f2_notif'));
//				if($data->id_tipo_notif != $notif_ind)
					$row[] = ($template->predefinito ? get_string('predefinito','local_f2_notif'): get_string('non_predefinito','local_f2_notif'));
				$table->data[] = $row;
			}

			class report_excel_formazione extends moodleform {
				public function definition() {
				global $CFG;
					$mform2 		=& $this->_form;
					$post_values = $this->_customdata['post_values'];
					$post_values = json_encode($post_values);
			
					$mform2->addElement('hidden', 'post_values',$post_values);
                                        $mform2->setType('post_values', PARAM_RAW);
			
					//$buttonarray=array();
					//$buttonarray[] = &$mform2->createElement('submit', 'submitbutton', 'EXPORT EXCEL');
					//$mform2->addGroup($buttonarray, 'buttonar2', '', array(' '), false);
					//$mform2->closeHeaderBefore('buttonar2');
					$mform2->addElement('html',html_writer::empty_tag('input', array('type' => 'submit', 'class' => 'ico_xls btn', 'value' =>get_string('export_templates', 'local_f2_notif'))));
					//$mform2->addElement('html',html_writer::tag('label', ' '.get_string('export_excel_templates_lbl', 'local_f2_notif')));
				}
			}
			
			$mform_excel = new report_excel_formazione('excel_template.php',array('post_values'=>$data),'post',NULL,array('class'=>'export_excel'));
			$mform_excel->display();		

			echo "<p>".get_string('count_tot_rows', 'local_f2_traduzioni',$total_rows)."</p>";
			$paging_bar = new paging_bar_f2($total_rows, $page, $perpage, $form_id, $post_extra);
			echo $paging_bar->print_paging_bar_f2();
			
			echo '<form action="delete_templates.php" method="post">';
			echo '<table><tr><td>';//table
			echo '<input type="submit" onClick="return confirmSubmitElimina()"; value="Elimina" />';
			echo '</td><td>';//table
			echo '<a href="add_templates.php"><button type="button">Nuovo</button></a>';
			echo '</td></tr></table>';	//table
				
			echo html_writer::table($table);

			echo '<table><tr><td>';//table
			echo '<input type="submit" onClick="return confirmSubmitElimina()"; value="Elimina" />';
			echo '</td><td>';//table	
			echo '<a href="add_templates.php"><button type="button">Nuovo</button></a>';
			echo '</td></tr></table>';
			echo '</form>';//table

			echo $paging_bar->print_paging_bar_f2();		
		}
//	}
	
echo '</div>';	
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
?>
