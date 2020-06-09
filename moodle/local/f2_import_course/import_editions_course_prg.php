<?php

// $Id: import_editions_course_prg.php 83 2012-10-02 17:00:08Z l.sampo $

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

global $CFG, $DB, $OUTPUT;

@set_time_limit(60*60); // 1 hour should be enough

require_once($CFG->dirroot.'/f2_lib/lib.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once($CFG->dirroot.'/admin/tool/uploaduser/user_form.php');
require_once('lib.php');

$transaction = $DB->start_delegated_transaction();

$statusMSG = null;

$url = new moodle_url($CFG->wwwroot.'/local/f2_import_course/import_editions_course_prg.php');
require_login(SITEID);

$context = get_context_instance(CONTEXT_COURSE, SITEID);
require_capability('local/f2_import_course:importeditionsprg', $context);

$PAGE->set_pagelayout('admin');
$PAGE->set_url($url);

class admin_uploadprg_form1 extends moodleform {
	function definition () {
		$mform = $this->_form;

		$mform->addElement('header', 'settingsheader', get_string('upload'));

		$mform->addElement('filepicker', 'fileedz', get_string('fileedzpian', 'local_f2_import_course'));
		$mform->addHelpButton('fileedz', 'fileedzpian', 'local_f2_import_course');
		$mform->addRule('fileedz', null, 'required');
		
		$mform->addElement('filepicker', 'fileposti', get_string('filepostris', 'local_f2_import_course'));
		$mform->addHelpButton('fileposti', 'filepostris', 'local_f2_import_course');
		$mform->addRule('fileposti', null, 'required');

		$choices = csv_import_reader::get_delimiter_list();
		$mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'local_f2_import_course'), $choices);
		if (array_key_exists('cfg', $choices)) {
			$mform->setDefault('delimiter_name', 'cfg');
		} else if (get_string('listsep', 'langconfig') == ';') {
			$mform->setDefault('delimiter_name', 'semicolon');
		} else {
			$mform->setDefault('delimiter_name', 'comma');
		}

		$textlib = textlib_get_instance();
		$choices = $textlib->get_encodings();
		$mform->addElement('select', 'encoding', get_string('encoding', 'local_f2_import_course'), $choices);
		$mform->setDefault('encoding', 'UTF-8');

		$this->add_action_buttons(false, get_string('upload', 'local_f2_import_course'));
	}
}

$mform1 = new admin_uploadprg_form1();

if ($formdata = $mform1->get_data()) {
	$iid1 = csv_import_reader::get_new_iid('fileedz');
	$cir1 = new csv_import_reader($iid1, 'fileedz');
	$iid2 = csv_import_reader::get_new_iid('fileposti');
	$cir2 = new csv_import_reader($iid2, 'fileposti');

	$content_file_edz = $mform1->get_file_content('fileedz');
	$content_file_posti = $mform1->get_file_content('fileposti');

	$readcount_edz = $cir1->load_csv_content($content_file_edz, $formdata->encoding, $formdata->delimiter_name);
	unset($content_file_edz);

	$readcount_posti = $cir2->load_csv_content($content_file_posti, $formdata->encoding, $formdata->delimiter_name);
	unset($content_file_posti);

	if ($readcount_edz === false || $readcount_posti === false) {
		print_error('csvloaderror', '', $returnurl);
	} else if ($readcount_edz == 0 || $readcount_posti == 0) {
		print_error('csvemptyfile', 'error', $returnurl);
	}

	if (!validate_file_header($cir1->get_columns(), $TEMPLATE_FILE_EDITIONS) || !validate_file_header($cir2->get_columns(), $TEMPLATE_FILE_RESERVED_SEATS))
		$objFile = objectErrorHandler(false,'ERROR: si sta cercando di importare dei file non correttamente formattati o si e&grave; scelto un delimitatore non appropriato');
	else $objFile = objectErrorHandler(true);

	if ($objFile->status) {
		$objEditions = import_sessions_from_csv($formdata, $formdata->delimiter_name, csv_import_reader::get_delimiter_list());
		if ($objEditions->status) {
			$objSeats = import_sessions_reserved_seats_from_csv($formdata, $formdata->delimiter_name, csv_import_reader::get_delimiter_list());
			if ($objSeats->status)
				$statusMSG = null;
			else $statusMSG = $objSeats->msg;
		} 
		else $statusMSG = $objEditions->msg;
	} else $statusMSG = $objFile->msg;	
} else {
	echo $OUTPUT->header();

	echo $OUTPUT->heading_with_help(get_string('uploadprg', 'local_f2_import_course'), 'uploadprg', 'local_f2_import_course');

	$mform1->display();
	echo $OUTPUT->footer();
	die;
}

$site = get_site();
$PAGE->navbar->add(get_string('navbartitleprg','local_f2_import_course'));
$title = get_string('uploadprg','local_f2_import_course');

$PAGE->set_title($title);
$PAGE->set_heading($title);
echo $OUTPUT->header();
echo $OUTPUT->heading($title);

if (is_null($statusMSG)) {
	echo '<p align="center"><br/>Dati caricati correttamente</p>';
	echo $OUTPUT->continue_button($CFG->wwwroot);
	echo $OUTPUT->footer();
        $transaction->allow_commit();
} else {
	echo '<p align="center"><br/>Caricamento delle edizioni non riuscito.<br/>';
	echo '<b>'.$statusMSG.'</b></p>';
	echo $OUTPUT->continue_button($CFG->wwwroot.'/local/f2_import_course/import_editions_course_prg.php');
	echo $OUTPUT->footer();
	$transaction->force_transaction_rollback();
}
