<?php
//$Id: block_f2_prenotazioni.php 1241 2013-12-20 04:34:05Z l.moretto $
/**
 *  f2_prenotazioni block class
 *
 *  Extends standard block methods, and defines methods for display,
 *  validation and processing of the form.
 *
 */

global $CFG;

require_once($CFG->dirroot.'/blocks/f2_prenotazioni/lib.php');

class block_f2_prenotazioni extends block_list {

	/**
	 * Stores the student record during validation and processing
	 *
	 * @var object
	 */
	public $student;
	/**
	 * Stores the event start timestamp during validation and processing
	 *
	 * @var object
	 */
	public $timestamp;

	/**
	 * Standard block init method, defines the title
	 */
	public function init() {
		$this->title = get_string('pluginname','block_f2_prenotazioni');
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
	}

	public function get_content() {
		global $USER, $CFG;
                
		if ($this->content !== null) {
			return $this->content;
		}

        $blockid = $this->instance->id;

        // Show the block
        $this->content         = new stdClass;
        $this->content->items  = array();
        $this->content->icons  = array();
        $this->content->footer = '';

// 		print_r(prenotazioni_aperte());
        if (isloggedin() && $blockid != false) 
        {
			if (prenotazioni_aperte() || isSupervisore($USER->id))
			{
				/* SOSTITUITO CON "Prenotazioni" del men� generale
				//if(has_capability('block/f2_prenotazioni:editmieprenotazioni',get_context_instance(CONTEXT_BLOCK, $blockid)))
				if(has_capability('block/f2_prenotazioni:editmieprenotazioni', context_block::instance($blockid)))
				{
				
				 	if (prenotazioni_dip_aperte())
					{
						$this->content->items[] = html_writer::tag('a', get_string('prenotazioni','block_f2_prenotazioni'), array('href' => $CFG->wwwroot.'/blocks/f2_prenotazioni/prenotazioni.php'));
						$this->content->icons[] = '';
					}
                 }
                 */
				//if(has_capability('block/f2_prenotazioni:editprenotazioni',get_context_instance(CONTEXT_BLOCK, $blockid)))
				if(has_capability('block/f2_prenotazioni:editprenotazioni', context_block::instance($blockid)))
				{
					if (prenotazioni_direzione_aperte() || isSupervisore($USER->id))
					{
						$this->content->items[] = html_writer::tag('a', get_string('prenota_altri','block_f2_prenotazioni'), array('href' => $CFG->wwwroot.'/blocks/f2_prenotazioni/prenota_altri.php'));
						$this->content->icons[] = '';
					}
				}
			}
			/* SOSTITUITO CON "Prenotazioni" del men� generale
	            if(has_capability('block/f2_prenotazioni:viewprenotazioni',get_context_instance(CONTEXT_BLOCK, $blockid)))
				{
					$this->content->items[] = html_writer::tag('a', get_string('tab_report_prenotazioni','block_f2_prenotazioni'), array('href' => $CFG->wwwroot.'/blocks/f2_prenotazioni/report_prenotazioni.php'));
					$this->content->icons[] = '';
				}
			*/
			if (validazioni_aperte() || isSupervisore($USER->id))
			{
				if (((isReferenteDiDirezione($USER->id) or isSupervisore($USER->id)) 
                        and validazioni_direzione_aperte()
                        and has_capability('block/f2_prenotazioni:editvalidazioni', context_block::instance($blockid)))
					or ((isReferenteDiSettore($USER->id) or isSupervisore($USER->id)) 
                        and validazioni_settore_aperte()
                        and has_capability('block/f2_prenotazioni:editvalidazioni', context_block::instance($blockid)))
					)
				{
					$this->content->items[] = html_writer::tag('a', get_string('validazioni','block_f2_prenotazioni'), array('href' => $CFG->wwwroot.'/blocks/f2_prenotazioni/validazioni_altri.php'));
					$this->content->icons[] = '';
				}
			}
		}
		// Add more list items here

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


