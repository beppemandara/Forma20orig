<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * Function che effettua la connessioni al data-base di Riforma
 * 
 * NOTE:
 *     - le operazioni sul data-base di Forma 2.0 sono effettuate utilizzando le function di Moodle
 *     - La function EML_Connetti_db_Riforma prevede che siano definite le costanti
 *          EML_MYSQL_HOST_RIFORMA, EML_MYSQL_USERNAME_RIFORMA, EML_MYSQL_PASSWD_RIFORMA, EML_MYSQL_DBNAME_RIFORMA
 *          (vedi file costanti_db.php)
 * 
 * Operazioni effettuate:
 *     Prova la connessione
 *     Se connessione non riuscita 
 *         interrompe il programma (die con messaggio di errore)
 *     Se connessione riuscita
 *         valorizza variabile globale $mysqli_Riforma
 *         restituisce 0
 */
function EML_Connetti_db_Riforma() {
    global $mysqli_Riforma;
    /*$host = EML_MYSQL_HOST_RIFORMA;
    $username = EML_MYSQL_USERNAME_RIFORMA;
    $passwd = EML_MYSQL_PASSWD_RIFORMA;
    $dbname = EML_MYSQL_DBNAME_RIFORMA;
    $mysqli_Riforma = new mysqli($host, $username, $passwd, $dbname);
    if ($mysqli_Riforma->connect_error) {
        die('Connect Error to Riforma (' . $mysqli->connect_errno . ') '.$mysqli->connect_error);
    }*/
    $dsn = 'mysql:dbname=mdl_crmood;host=prodsql6.csi.it;port=3316';
    $user = "mdl_crmood_rw";
    $password = 'C0N$r3GP!3';
    // connessione PDO instance via driver invocation
    try {
        $mysqli_Riforma = new PDO($dsn, $user, $password);
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage() . "<br />";
    }
    return 0;
} //EML_Connetti_db_Riforma
?>
