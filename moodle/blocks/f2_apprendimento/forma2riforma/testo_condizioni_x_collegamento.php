<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * Blocco con indicate le condizioni per poter collegare un corso Riforma -- Forma
 * 
 * E' inclusa dal file nuovo_collegamento_1.php
 * 
 * NOTA: blocco da modificare nelle prossime versioni del programma
 */
//global $OUTPUT;
//echo $OUTPUT->box_start();
echo 'Condizioni per poter collegare un corso di Riforma a FORMA:';
echo '<ul>';
echo '<li>Non deve essere già essere presente un collegamento Riforma -- FORMA per il corso';
echo '<li>Lo shortname del corso deve essere uguale in Riforma ed in FORMA';
echo '<li>In Riforma il corso deve essere presente come corso "standard" Moodle';
echo "<li>In Forma il corso deve essere presente nell'anagrafica Corsi Obiettivo senza nessuna edizione";
echo '</ul>';
//echo $OUTPUT->box_end();
?>