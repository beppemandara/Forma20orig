<?php
/**
 * $Id: block_f2_formazione_individuale.php 1422 2016-11-17 09:03:13Z l.moretto $
 * f2_formazione_individuale block class
 *
 * Extends standard block methods, and defines methods for display,
 * validation and processing of the form.
 */
class block_f2_formazione_individuale extends block_list {

	/**
     * Standard block init method, defines the title
     */
	public function init() {
			$this->title = get_string('f2_formazione_individuale','block_f2_formazione_individuale');
	}

	/**
     * Prevent multiple instances of the block on a page
     * @return boolean
     */
	public function allow_multiple() {
			return false;
	}

	/**
     * Cron job, sends reminder texts once a day
     *
     * Runs every hour with block's cron job, but only does anything between 8am and 9am (so
     * once a day).  Looks for any appoinments happening today, and if there's a valid mobile
     * number for the student, sends them a reminder via SMS.
     */
	public function cron() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/f2_formazione_individuale/lib.php');
		f2_formazione_individuale_cron();
	}

	public function get_content() {
            
            global $CFG, $USER;
            
            require_once($CFG->dirroot.'/local/f2_support/lib.php');

            $param_CIG = get_parametro('p_f2_corsi_individuali_giunta');
            $param_CIL = get_parametro('p_f2_corsi_individuali_lingua_giunta');
            $param_CIC = get_parametro('p_f2_corsi_individuali_consiglio');

$str = <<<'EFO'
<script type="text/javascript">
//<![CDATA[
function view_sub_menu(element){
    var div = document.getElementById("div_"+element.id);
    if(div.getAttribute('hidden') == 'hidden'){
        close_all_submenu();
        div.removeAttribute('hidden');
        div.removeAttribute('style');
        div.setAttribute('style','margin-left:15px;');
    }
    else{
        div.setAttribute('hidden','hidden');
        div.setAttribute('style','display:none;margin-left: 15px;');
    }
}
function close_all_submenu(){
    var all_div = document.getElementsByClassName('my_sub_menu');
    for(i=0;i<all_div.length;i++){
        all_div[i].setAttribute('hidden','hidden');
        all_div[i].setAttribute('style','display:none;margin-left: 15px;');
    }
}
//]]>
</script>
EFO;
           
            if ($this->content !== null) {
                return $this->content;
            }

            $this->content         = new stdClass;
            $this->content->items  = array();
            $this->content->icons  = array();
            $this->content->footer = $str;

            if (isloggedin()) {   // Show the block
            	//if(has_capability('block/f2_formazione_individuale:individualigiunta',get_context_instance(CONTEXT_SYSTEM))){
            	if(has_capability('block/f2_formazione_individuale:individualigiunta', context_system::instance())){
                    $item1 = html_writer::tag('a', get_string('corsi_individualigiunta', 'block_f2_formazione_individuale'), array('onclick' => 'view_sub_menu(this)', 'id' =>'corsiindividualigiunta','style'=>'cursor:pointer'));
                    $item1_sub = '<div id="div_corsiindividualigiunta" hidden="hidden" class="my_sub_menu" style="display:none;">';
                    $item1_sub .= html_writer::tag('a', get_string('gestionecorsiconspesa', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/gestione_corsi.php?training='.$param_CIG->val_char));
                    $item1_sub .= "<br>".html_writer::tag('a', get_string('gestionecorsigratis', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/gest_corsi_ind_senza_determina.php?training='.$param_CIG->val_char));
                    $item1_sub .= "<br>".html_writer::tag('a', get_string('modifica_corsi_con_spesa', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/gestione_corsi.php?training='.$param_CIG->val_char.'&mod=1'));
                    if(isSupervisore($USER->id))
                        $item1_sub .= "<br>".html_writer::tag('a', get_string('sblocca_determina', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/sbc_determina_prv.php?training='.$param_CIG->val_char));
                    //$item1_sub .= "<br>".html_writer::tag('a', get_string('bloccacorsi_assegnacodicedeterminaprovv', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/mod_determina_prov.php?training='.$param_CIG->val_char));
                    $item1_sub .= "<br>".html_writer::tag('a', get_string('preparaallegatidetermina', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/gestione_allega_determina.php?training='.$param_CIG->val_char));
                    $item1_sub .= "<br>".html_writer::tag('a', get_string('assegna_codice_determina', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/cod_determina_def.php?training='.$param_CIG->val_char));
                    $item1_sub .= "<br>".html_writer::tag('a', get_string('invio_autorizzazioni', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/invio_autorizzazioni.php?training='.$param_CIG->val_char));
                    $item1_sub .= "<br>".html_writer::tag('a', get_string('archiviazioneinstorico', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/archiviazione_storico.php?training='.$param_CIG->val_char));
                    $item1_sub .= "<br>".html_writer::tag('a', get_string('modificastorico', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/modifica_storico.php?training='.$param_CIG->val_char));
                    $item1_sub .= '</div>';

                    $this->content->items[] = $item1."<br>".$item1_sub;
                    $this->content->icons[] = '';
            	}
            	//if(has_capability('block/f2_formazione_individuale:individualilinguagiunta',get_context_instance(CONTEXT_SYSTEM))){
            	if(has_capability('block/f2_formazione_individuale:individualilinguagiunta', context_system::instance())){
                    $item2 = html_writer::tag('a', get_string('corsi_individualilinguagiunta', 'block_f2_formazione_individuale'), array('onclick' => 'view_sub_menu(this)','id' =>'corsiindividualilinguagiunta','style'=>'cursor:pointer'));
                    $item2_sub = '<div id="div_corsiindividualilinguagiunta" hidden="hidden" class="my_sub_menu" style="display:none;">';
                    $item2_sub .= html_writer::tag('a', get_string('gestione_corsi', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/gestione_corsi.php?training='.$param_CIL->val_char));
                    $item2_sub .= "<br>".html_writer::tag('a', get_string('modifica_corsi', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/gestione_corsi.php?training='.$param_CIL->val_char.'&mod=1'));
                    if(isSupervisore($USER->id))
                        $item2_sub .= "<br>".html_writer::tag('a', get_string('sblocca_determina', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/sbc_determina_prv.php?training='.$param_CIL->val_char));
                    //$item2_sub .= "<br>".html_writer::tag('a', get_string('bloccacorsi_assegnacodicedeterminaprovv', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/mod_determina_prov.php?training='.$param_CIL->val_char));
                    $item2_sub .= "<br>".html_writer::tag('a', get_string('preparaallegatidetermina', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/gestione_allega_determina.php?training='.$param_CIL->val_char));
                    $item2_sub .= "<br>".html_writer::tag('a', get_string('assegna_codice_determina', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/cod_determina_def.php?training='.$param_CIL->val_char));
                    $item2_sub .= "<br>".html_writer::tag('a', get_string('invio_autorizzazioni', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/invio_autorizzazioni.php?training='.$param_CIL->val_char));
                    $item2_sub .= "<br>".html_writer::tag('a', get_string('archiviazioneinstorico', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/archiviazione_storico.php?training='.$param_CIL->val_char));
                    $item2_sub .= "<br>".html_writer::tag('a', get_string('modificastorico', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/modifica_storico.php?training='.$param_CIL->val_char));
                    $item2_sub .= '</div>';

                    $this->content->items[] = $item2."<br>".$item2_sub;
                    $this->content->icons[] = '';
            	}
            	//if(has_capability('block/f2_formazione_individuale:individualiconsiglio',get_context_instance(CONTEXT_SYSTEM))){	
            	if(has_capability('block/f2_formazione_individuale:individualiconsiglio', context_system::instance())){	
                    $item3 = html_writer::tag('a', get_string('corsi_individualiconsiglio', 'block_f2_formazione_individuale'), array('onclick' => 'view_sub_menu(this)','id' =>'corsiindividualiconsiglio','style'=>'cursor:pointer'));
                    $item3_sub = '<div id="div_corsiindividualiconsiglio" hidden="hidden" class="my_sub_menu" style="display:none;">';
                    $item3_sub .= html_writer::tag('a', get_string('gestione_corsi', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/gestione_corsi.php?training='.$param_CIC->val_char));
                    $item3_sub .= "<br>".html_writer::tag('a', get_string('modifica_corsi', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/gestione_corsi.php?training='.$param_CIC->val_char.'&mod=1'));
                    if(isSupervisore($USER->id))
                        $item3_sub .= "<br>".html_writer::tag('a', get_string('sblocca_determina', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/sbc_determina_prv.php?training='.$param_CIC->val_char));
                    //$item3_sub .= "<br>".html_writer::tag('a', get_string('bloccacorsi_assegnacodicedeterminaprovv', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/mod_determina_prov.php?training='.$param_CIC->val_char));
                    $item3_sub .= "<br>".html_writer::tag('a', get_string('preparaallegatidetermina', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/gestione_allega_determina.php?training='.$param_CIC->val_char));
                    $item3_sub .= "<br>".html_writer::tag('a', get_string('assegna_codice_determina', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/cod_determina_def.php?training='.$param_CIC->val_char));
                    $item3_sub .= "<br>".html_writer::tag('a', get_string('invio_autorizzazioni', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/invio_autorizzazioni.php?training='.$param_CIC->val_char));
                    $item3_sub .= "<br>".html_writer::tag('a', get_string('archiviazioneinstorico', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/archiviazione_storico.php?training='.$param_CIC->val_char));
                    $item3_sub .= "<br>".html_writer::tag('a', get_string('modificastorico', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/modifica_storico.php?training='.$param_CIC->val_char));
                    $item3_sub .= '</div>';

                    $this->content->items[] = $item3."<br>".$item3_sub;
                    $this->content->icons[] = '';
            	}
            	//if(has_capability('block/f2_formazione_individuale:forzature',get_context_instance(CONTEXT_SYSTEM))){		
            	if(has_capability('block/f2_formazione_individuale:forzature', context_system::instance())){		
                    $this->content->items[] = html_writer::tag('a', get_string('forzature', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/forzature.php'));
                    $this->content->icons[] = '';
            	}
                if(isSupervisore($USER->id))
                    $this->content->items[] = html_writer::tag('a', get_string('budget', 'block_f2_formazione_individuale'), array('href' => $CFG->wwwroot.'/blocks/f2_formazione_individuale/budget/fi_budget.php'));
                    $this->content->icons[] = '';
            }

            return $this->content;
	}
	/**
		* Restricts block to course pages
		*
		* @see blocks/block_base#applicable_formats()
		* @return array
		*/
	function applicable_formats() {
		return array('all' => true);
	}
	/**
		* Formats the error messages as HTML.
		*
		* @param $errors error messages generated by {@see validate_form()}
		*/
	function display_errors($errors) {
			$this->content->text .= html_writer::start_tag('div', array('class' => "errors"));
			foreach ($errors as $error) {
					$this->content->text .= $error.html_writer::empty_tag('br');
			}
			$this->content->text .= html_writer::end_tag('div');
	}
	
}


