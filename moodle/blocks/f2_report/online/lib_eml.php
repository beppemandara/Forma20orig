<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - dicembre 2014
 * 
 * Funzioni di utilità generale usate dalle parti sviluppate da Albertin - Mandarà
 * 
 *  NOTA IMPORTANTE: 
 *      Il file con queste function dovrà essere spostate ad un livello "superiore"
 *      e le require_once dovranno accedervi attraverso un opportuno parametro di configurazione
 *      
 *      La modifica dovrà interessare anche Forma2Riforma, rpfmhrbat e tutte le altre parti
 *      di software sviluppato internamente da CSI 
 * 
 * Function presenti:
 *      EML_Connetti_db
 *      EML_Del_xxx
 *      EML_Get_mdl_f2_parametri
 *      EML_Get_Numero_record_in_tabella
*/
class EML_RECmdl_f2_parametri {
    public $id;
    public $descrizione;
    public $val_int;
    public $val_float;
    public $val_char;
    public $val_date;
    public $obbligatorio;
} //EMLRECmdl_f2_parametri
function EML_Connetti_db() {
    global $mysqli;
    $host = EML_MYSQL_HOST;
    $username = EML_MYSQL_USERNAME;
    $passwd = EML_MYSQL_PASSWD;
    $dbname = EML_MYSQL_DBNAME;
    $mysqli = new mysqli($host, $username, $passwd, $dbname);
    if ($mysqli->connect_error) {
        die('Connect Error to Forma (' . $mysqli->connect_errno . ') '.$mysqli->connect_error);
    }
    $nome = $mysqli->character_set_name();
    if ($nome <> "utf8") {
        $mysqli->set_charset("utf8");
    }
    return 0;
} //EML_Connetti_db
function EML_Del_xxx($nome_tabella, $clausola_where) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - dicembre 2014
 * 
 * Cancella dalla tabella $nome_tabella i record specificati da $clausola_where
 * 
 * Restituisce:
 *     0
 * 
 * Parametri
 *     $nome_tabella   - tabella interessata alla cancellazione
 *     $clausola_where - condizione WHERE da inserire nella DELETE
*/
    global $mysqli;
    $query = "DELETE FROM ".$nome_tabella." ".$clausola_where;
    $mysqli->query($query);
} //EML_Del_xxx
function EML_Get_mdl_f2_parametri($id, EML_RECmdl_f2_parametri $rec_mdl_f2_parametri) {
/*
* A. Albertin, G. Mandarà - CSI Piemonte - dicembre 2014
* 
* Legge dalla tabella mdl_f2_parametri il record con id = parametro in ingresso
* valorizzando i campi con quanto presente nel record ricevuto come parametro
* 
* Parametri:
*     $id -- Id del record da leggere
*     $rec_mdl_f2_parametri -- Record con i dati letti (mappa la tabella mdl_f2_parametri)
*
* Codici restituiti:
*   < 0 => error code della SELECT cambiato di segno (se operazione andata male)
*   > 0 => id del record inserito  
*/
    global $mysqli;
    $query = " SELECT"
            ." id, descrizione, val_int, val_float, val_char, val_date, obbligatorio"
            ." FROM mdl_f2_parametri"
            ." WHERE id = '".$id."'";
    $res = $mysqli->query($query);
    if (!$res) {
        $ret_code = -$mysqli->errno;
    } else {
        $ret_code = 1;
        $row = $res->fetch_assoc();
        $rec_mdl_f2_parametri->id = $row['id'];
        $rec_mdl_f2_parametri->descrizione = $row['descrizione'];
        $rec_mdl_f2_parametri->val_int = $row['val_int'];
        $rec_mdl_f2_parametri->val_float = $row['val_float'];
        $rec_mdl_f2_parametri->val_char = $row['val_char'];
        $rec_mdl_f2_parametri->val_date = $row['val_date'];
        $rec_mdl_f2_parametri->obbligatorio = $row['obbligatorio'];
    }
    return $ret_code;
} //EML_Get_mdl_f2_parametri
function EML_Get_Numero_record_in_tabella($nome_tabella, $clausola_where, &$numero_record) {
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - dicembre 2014
 * 
 * Legge dalla tabella specificata da $nome_tabella il numero di record presenti
 * filtrati dalla condizione specificata da $clausola_where.
 * Il numero di record letti è restituito in $numero_record
 * 
 * Principali passi eseguiti:
 *     - Lettura dal numero di record presenti nella tabella $nome_tabella con la condizione $clausola_where
 *     - Se errori in lettura
 *         - esco col numero di errore di MySql
 *     - altrimenti
 *         - estraggo il numero di record trovati
 *         - esco con codice 1
 *     - fine se
 * Parametri:
 *     $nome_tabella. Nome della tabella di interesse
 *     $clausola_where. Consizione WHERE usata nella SELECT COUNT(*)
 * Codici restituiti:
 *     1 --> tutto ok
 *  altro --> error number di MySql
 * Errori: nessuno
 * Messaggi di log: nessuno
 */
    global $mysqli;
    $query = "SELECT COUNT(*) as contatore FROM ".$nome_tabella." ".$clausola_where;
    $res = $mysqli->query($query);
    if (!$res) {
        $ret_code = $mysqli->errno;
        $numero_record = null;
    } else {
        $ret_code = 1;
        $row = $res->fetch_assoc();
        $numero_record =  $row['contatore'];
    }
    return $ret_code;    
} //EML_Get_Numero_record_in_tabella
?>
