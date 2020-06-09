<?php
/* 
 * $Id: contratto_formativo.php 1296 2014-07-04 13:42:29Z l.moretto $
 */
require_once '../../../config.php';

global $DB, $CFG, $USER;

require_once($CFG->dirroot.'/local/f2_course/pdf/pdf_contratto_formativo.php');

$course = required_param('courseid', PARAM_INT);

$q = "
SELECT 
	c.idnumber,
	c.fullname,
	f2_sp.obiettivi,
	f2_sp.met1,
	f2_sp.met2,
	f2_sp.met3,
	IF (f2_sp.monitoraggio IS NULL, '', f2_sp.monitoraggio) as monitoraggio,
	IF (f2_sp.valutazione IS NULL, '', f2_sp.valutazione) as valutazione,
	IF (f2_sp.apprendimento IS NULL, '', f2_sp.apprendimento) as apprendimento,
	IF (f2_sp.ricaduta IS NULL, '', f2_sp.ricaduta) as ricaduta,
	f2_sp.dispense_vigenti,
	f2_sp.contenuti,
	IF (f2_ac.flag_dir_scuola = 'D', 
		(SELECT fullname FROM {org} o WHERE o.id = f2_ac.id_dir_scuola), 
		(SELECT denominazione FROM {f2_fornitori} f2_f WHERE f2_f.id = f2_ac.id_dir_scuola)) as scuola,
	IF (f2_ac.flag_dir_scuola = 'D',
		(SELECT id FROM {org} o WHERE o.id = f2_ac.id_dir_scuola),
		(SELECT id FROM {f2_fornitori} f2_f WHERE f2_f.id = f2_ac.id_dir_scuola)) as scuola_id,
	f2_ac.flag_dir_scuola as scuola_tipo,
	f2_ac.cf
FROM 
	{f2_scheda_progetto} f2_sp,
	{f2_anagrafica_corsi} f2_ac,
	{course} c
WHERE c.id = ?
	AND f2_sp.courseid = c.id
	AND c.id = f2_ac.courseid";

$data = $DB->get_record_sql($q, array($course));

$scuola_logo = '';
$scuola_nome = $data->scuola;

$viaente = $DB->get_field('f2_anagrafica_corsi', 'viaente', array('courseid' => $course));
if ($viaente) 
	$scuola_indirizzo = $viaente;
else if ($data->scuola_tipo!='D') {
	$ep = $DB->get_record('f2_fornitori', array('id' => $data->scuola_id), 'indirizzo, cap, citta, provincia, paese, url');
	$scuola_indirizzo = $ep->indirizzo.' '.$ep->cap.', '.$ep->provincia.' - '.$ep->cap.', '.$ep->paese;
} else 
	$scuola_indirizzo = $DB->get_field('f2_parametri', 'val_char', array('id' => 'p_f2_indirizzo_default'));

$editions = array();

if (isset($_POST) and !empty($_POST)) {
	if (isset($_POST['visible']))
	{
		$fromUser 	= ($_POST['visible'] == 'super') ? ' ' : ', {facetoface_signups} fc_si ';
		$whereUser 	= ($_POST['visible'] == 'super') ? ' ' : ' AND fc_si.sessionid = fc_se.id AND fc_si.userid = '.$USER->id.' ';
	}	
	
	if (isset($_POST['sessions']))
	{
		foreach ($_POST['sessions'] as $idx => $sessionid) {
		
			$userdata = $DB->get_record_sql("
					SELECT
					fc_se.id,
					MIN(fc_sd.timestart) as timestart,
					MAX(fc_sd.timefinish) as timefinish
					FROM
					{facetoface_sessions} fc_se,
					{facetoface_sessions_dates} fc_sd
					$fromUser
					WHERE
					fc_se.id = $sessionid AND
					fc_se.id = fc_sd.sessionid
					$whereUser
					GROUP BY
					fc_se.id");
		
					$objEdition = new stdClass();
					$objEdition->id = $userdata->id;
					$objEdition->startdate = (isset($userdata->timestart)) ? date('d/m/Y', $userdata->timestart) : '';
					$objEdition->starthour = (isset($userdata->timestart)) ? date('H:i', $userdata->timestart) : '';
					$objEdition->enddate = (isset($userdata->timefinish)) ? date('d/m/Y', $userdata->timefinish) : '';
					$objEdition->endhour = (isset($userdata->timefinish)) ? date('H:i', $userdata->timefinish) : '';
		
					$sessiondocenti = $DB->get_records_sql("
							SELECT
							u.id,
							u.firstname,
							u.lastname
							FROM
							{facetoface_sessions_docenti} fsd,
							{user} u
							WHERE
							fsd.sessionid = $userdata->id AND
							fsd.userid = u.id");
		
							$objEdition->docenti = $sessiondocenti;
		
							$editions[] = $objEdition;
		}
		createPDF($data, $scuola_logo, $scuola_nome, $scuola_indirizzo, $editions);
		return;
	}
} else {
	createPDF($data, $scuola_logo, $scuola_nome, $scuola_indirizzo, $editions);
	return;
}