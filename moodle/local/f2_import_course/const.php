<?php

// $Id: const.php 83 2012-10-02 17:00:08Z l.sampo $

define('DATABASE', $CFG->dbname);
define('TABLE_PIANIFICATE', 'f2_edz_pianificate_corsi_prg');
define('TABLE_POSTI_RISERVATI', 'f2_edizioni_postiris_map');

global $TEMPLATE_FILE_EDITIONS;
$TEMPLATE_FILE_EDITIONS = array(
							'ID',
							'ANNO_PIANIFICAZIONE',
							'CODICE_CORSO',
							'SEDE', 
							'EDIZIONE', 
							'ANNO_SVOLGIMENTO',
							'SESSIONE_SVOLGIMENTO',
							'EDIZIONE_SVOLGIMENTO');

global $TEMPLATE_FILE_RESERVED_SEATS;
$TEMPLATE_FILE_RESERVED_SEATS = array(
									'IDFK',
									'CODICE_DIREZIONE',
									'NUMERO_POSTI_RISERVATI');

define('UPLOAD_OK', 100);
define('UPLOAD_FAIL', 200);