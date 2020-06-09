<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - dicembre 2014
 * 
 * Blocco di spiegazione del significato del paramatro p_grfo_data_inizio_monitoraggio
 * 
 * NOTA: blocco da modificare nelle prossime versioni del programma
 */
$aus = get_string('grfo_etichetta_data_inizio_monitoraggio', 'block_f2_report');
echo "Il parametro <strong>".$aus."</strong> è usato durante l'inserimento di un nuovo corso in monitoraggio<br>";
echo "per evitare di proporre nella lista di scelta un elenco di corsi molto lungo.<br>";
echo "<br>";
echo "Durante l'inserimento di un nuovo corso la lista di scelta contiene:<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
echo 'i corsi Obiettivo<br>';
echo 'con una o più edizioni che iniziano o finiscono dopo la data specificata';
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
echo 'i corsi Programmati on-line (codice corso iniziante per E)<br>';
echo 'con una o più edizioni che iniziano o finiscono dopo la data specificata';
echo '<br>';
?>
