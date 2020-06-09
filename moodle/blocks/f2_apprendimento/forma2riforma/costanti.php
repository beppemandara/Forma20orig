<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 */
// Nome e versione del programma (usati campo Note di mdl_f2_storico_corsi)
define("EML_RIFORMA_NOME_PROGRAMMA", "CREG - Upload da Riforma");
define("EML_RIFORMA_VERSIONE_PROGRAMMA", "ver. 1.0.0");

//  TEMPORANEA Valori usati come segnaposto per attivare le operazioni su un corso
//  Saranno sostituiti da apposite icone
define("EML_RIFORMA_FLAG_DELETE", "<span class=\"hidden\">X</span>");
define("EML_RIFORMA_FLAG_UPDATE", "M");
define("EML_RIFORMA_FLAG_READ", "R");
define("EML_RIFORMA_FLAG_LOG", "L");
define("EML_RIFORMA_FLAG_WRITE", "A");

// Parametri vari usati per valorizzare campi dello storico corsi
define("EML_RIFORMA_TIPO_CORSO_OBIETTIVO", "O");
define("EML_RIFORMA_CODPART_PARTECIPAZIONE_SENZA_VERIFICA", "2");
define("EML_RIFORMA_DESCRPART_PARTECIPAZIONE_SENZA_VERIFICA", "Partecipazione senza verifica");
define("EML_RIFORMA_VA_PARTECIPAZIONE_SENZA_VERIFICA", "_");
define("EML_RIFORMA_CODPART_ESECUTIVO_CON_VERIFICA", "1");
define("EML_RIFORMA_DESCRPART_ESECUTIVO_CON_VERIFICA", "Esecutivo con verifica");

//  Valori per il campo stato in tabella mdl_f2_forma2riforma_mapping
define("EML_RIFORMA_MAPPING_OK", 1);
define("EML_RIFORMA_LETTURA_OK", 2);
define("EML_RIFORMA_LETTURA_WARNING", 3);
define("EML_RIFORMA_ARCHIVIAZIONE_OK", 4);
define("EML_RIFORMA_ARCHIVIAZIONE_WARNING", 5);

//  Valori per il campo codice in tabella mdl_f2_forma2riforma_log
define("EML_RIFORMA_INS_MAPPING",  1);

define("EML_RIFORMA_UPD_MAPPING",       101);
define("EML_RIFORMA_START_UPD_MAPPING", 102);
define("EML_RIFORMA_ERR_UPD_MAPPING",   103);
define("EML_RIFORMA_END_UPD_MAPPING",   104);

define ("EML_RIFORMA_START_READ_PARTECIPAZIONI",  201);
define ("EML_RIFORMA_END_READ_PARTECIPAZIONI",    202);
define ("EML_RIFORMA_START_READ_MAPPING",         203);
define ("EML_RIFORMA_ERR_MAPPING_UTENTE",         204);
define ("EML_RIFORMA_END_READ_MAPPING",           205);
define ("EML_RIFORMA_START_WRITE_PARTECIPAZIONI", 206);
define ("EML_RIFORMA_ERR_WRITE_PARTECIPAZIONI",   207);
define ("EML_RIFORMA_END_WRITE_PARTECIPAZIONI",   208);

define ("EML_RIFORMA_START_WRITE_IN_STORICO", 301);
define ("EML_RIFORMA_NO_WRITE_IN_STORICO",    302);
define ("EML_RIFORMA_ERR_WRITE_IN_STORICO",   303);
define ("EML_RIFORMA_END_WRITE_IN_STORICO",   304);

define("EML_RIFORMA_START_DEL_CORSO",    401);
define("EML_RIFORMA_DEL_MAPPING",        402);
define("EML_RIFORMA_DEL_PARTECIPAZIONI", 403);
define("EML_RIFORMA_END_DEL_CORSO",      404);

// Valori per il campo stato in tabella tbl_eml_riforma_partecipazioni 
define("EML_RIFORMA_PARTECIPAZIONE_OK",     0);
define("EML_RIFORMA_PARTECIPAZIONE_NON_OK", 1);

//  Valori di fieldid in mdl_user_info_data
define("EML_RIFORMA_FIELDID_CATEGORIA", 1);
define("EML_RIFORMA_FIELDID_AP", 2);
define("EML_RIFORMA_FIELDID_SESSO", 3);
?>
