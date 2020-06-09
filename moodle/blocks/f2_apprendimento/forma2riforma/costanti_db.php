<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * Definizione costanti con nome del db, username, ecc
 * 
 * Nota: questo file va modificato "opportunamente" per cambiare l'ambiente
 * (Host, usernamen, password, database) spostando i commenti
 */
// Ambiente di Sviluppo Riforma
/*
define("EML_MYSQL_HOST_RIFORMA",     "dev-spdb-01.self.csi.it:3306");
define("EML_MYSQL_USERNAME_RIFORMA", "mdl_crmood");
define("EML_MYSQL_PASSWD_RIFORMA",   "eef0gejo");
define("EML_MYSQL_DBNAME_RIFORMA",   "mdl_crmood");
 */
// Ambiente di Produzione Riforma
define("EML_MYSQL_HOST_RIFORMA",     "prodsql6.csi.it");
define("EML_MYSQL_USERNAME_RIFORMA", "mdl_crmood_rw");
define("EML_MYSQL_PASSWD_RIFORMA",   'C0N$r3GP!3');
define("EML_MYSQL_DBNAME_RIFORMA",   "mdl_crmood");
?>