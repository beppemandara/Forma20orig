<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - dicembre 2014
 * 
 * Blocco con indicate le condizioni per poter inserire in monitoraggio un corso on-line
 * 
 * E' inclusa dal file nuovo_corso.php
 * 
 * NOTA: blocco da modificare nelle prossime versioni del programma
 */
echo 'Condizioni per poter inserire in monitoraggio un corso on-line:';
echo '<ul>';
echo '<li>Non deve già essere in elenco corsi in monitoraggio';
echo '<li>Se corso Programmato il codice corso deve iniziare per E';
echo '<li>Deve avere almeno una edizione con data fine maggiore del parametro <strong>Data inizio monitoraggio</strong>';
echo '</ul>';
?>