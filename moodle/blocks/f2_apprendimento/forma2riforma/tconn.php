<?php
echo "START<br />";
// connessione a MySQL con l'estensione MySQLi
$mysqli = new mysqli("prodsql6.csi.it", "mdl_crmood", 'C0N$r3GP!3', "mdl_crmood");
echo "step 1<br />";
// verifica dell'avvenuta connessione
if (mysqli_connect_errno()) {
    echo "step 1.1<br />";
    // notifica in caso di errore
    echo "Errore in connessione al DBMS: ".mysqli_connect_error();
    // interruzione delle esecuzioni i caso di errore
    exit();
}
else {
    echo "step 1.2<br />";
    // notifica in caso di connessione attiva
    echo "Connessione avvenuta con successo";
}
echo "step 2<br />";
// chiusura della connessione
$mysqli->close();
echo "END<br />";
?>