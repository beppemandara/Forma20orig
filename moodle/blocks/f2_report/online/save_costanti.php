<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - dicembre 2014
 * 
 */
define ("EML_PENT_SI", "Si");
define ("EML_PENT_NO", "No");

define("EML_PENT_MODULO_VISIBILE", "Si");
define("EML_PENT_MODULO_NON_VISIBILE", "No");

define("EML_PENT_MODULO_MONITORABILE", "Si");
define("EML_PENT_MODULO_NON_MONITORABILE", "No");

define("EML_PENT_MAX_RISORSE_IN_REPORT", 10);
define("EML_PENT_MODULO_NON_MONITORATO", -1);

define("EML_PENT_EDIZIONE_MONITORATA", "S");
define("EML_PENT_EDIZIONE_NON_MONITORATA", "N");

define("EML_PENT_TRACCIATO_COMPLETAMENTO", "Si");
define("EML_PENT_NON_TRACCIATO_COMPLETAMENTO", "No");

define("EML_PENT_MODULO_ASSIGNMENT", "assignment");
define("EML_PENT_MODULO_CHAT", "chat");
define("EML_PENT_MODULO_CHOICE", "choice");
define("EML_PENT_MODULO_DATA", "data");
define("EML_PENT_MODULO_FACETOFACE", "facetoface");
define("EML_PENT_MODULO_FEEDBACK", "feedback");
define("EML_PENT_MODULO_FOLDER", "folder");
define("EML_PENT_MODULO_FORUM", "forum");
define("EML_PENT_MODULO_GAME", "game");
define("EML_PENT_MODULO_GLOSSARY", "glossary");
define("EML_PENT_MODULO_IMSCP", "imscp");
define("EML_PENT_MODULO_LABEL", "label");
define("EML_PENT_MODULO_LESSON", "lesson");
define("EML_PENT_MODULO_LTI", "lti");
define("EML_PENT_MODULO_PAGE", "page");
define("EML_PENT_MODULO_QUIZ", "quiz");
define("EML_PENT_MODULO_RESOURCE", "resource");
define("EML_PENT_MODULO_SCORM", "scorm");
define("EML_PENT_MODULO_SURVEY", "survey");
define("EML_PENT_MODULO_URL", "url");
define("EML_PENT_MODULO_WIKI", "wiki");
define("EML_PENT_MODULO_WORKSHOP", "workshop");

// Tipologie e codici dei messaggi di log
define("EML_MSG_OPERAZIONI_SUL_DB", 1);
define("EML_MSG_CALCOLI",           2);
define("EML_MSG_REPORT",            3);

//  LIVELLO_MSG = EML_MSG_OPERAZIONI_SUL_DB"
define("EML_MSG_INS_CORSO",     101);
define("EML_MSG_UPD_CORSO",     102);
define("EML_MSG_DEL_CORSO",     103);
define("EML_MSG_UPD_PARAMETRI", 104);

//  LIVELLO_MSG = EML_MSG_CALCOLI
define("EML_MSG_RICALCOLO_CORSO", 201);

//  LIVELLO_MSG = EML_MSG_REPORT
define("EML_MSG_REPORT_CORSO", 301);
?>