<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - giugno 2015
 * 
 * Definizione costanti con nome del db, username, ecc
 * 
 * Nota: questo file va modificato "opportunamente" per cambiare l'ambiente
 * (Host, username, password, database) spostando i commenti
 */
/*
// Ambiente di Sviluppo Forma
define("EML_AMBIENTE",       "SVILUPPO");
define("EML_MYSQL_HOST",     "dev-spdb-01.self.csi.it:3306");
define("EML_MYSQL_USERNAME", "forma2");
define("EML_MYSQL_PASSWD",   "quee5los");
define("EML_MYSQL_DBNAME",   "forma2");
*/
/*
// Ambiente di Produzione Forma
define("EML_AMBIENTE",       "PRODUZIONE");
define("EML_MYSQL_HOST",     "10.202.17.3");
define("EML_MYSQL_USERNAME", "forma2");
define("EML_MYSQL_PASSWD",   'forma2');
define("EML_MYSQL_DBNAME",   "forma2");
*/
/*
// Ambiente di Demo Forma
define("EML_AMBIENTE",       "DEMO");
define("EML_MYSQL_HOST",     "10.202.17.3");
define("EML_MYSQL_USERNAME", "sviluser");
define("EML_MYSQL_PASSWD",   'development');
define("EML_MYSQL_DBNAME",   "svilforma2");
*/
// Ambiente di Test Forma (sull'isola)
define("EML_AMBIENTE",       "TEST");
define("EML_MYSQL_HOST",     "tst-formazione-vdb01.formazione.csi.it");
define("EML_MYSQL_USERNAME", "forma2");
define("EML_MYSQL_PASSWD",   'mypass01');
define("EML_MYSQL_DBNAME",   "forma2");