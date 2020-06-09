<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - agosto 2015
 * 
 */
global $USER;
require_once('function_db.php');
require_once '../../config.php';
// Verifica se per il ruolo utente sono disponibili dei report
$codice_voce_menu = 'GREP';
$user_id = intval($USER->id);
$role_id = null;
$role_name = null;
$ret_code = EML_Get_user_roleid($user_id, $role_id, $role_name);
$numero_report = 0;
$elenco_report_select = EML_Get_elenco_report_selezionabili($codice_voce_menu, $role_id, $numero_report);
if($numero_report == 0) {
    // Nessun report disponibile per il ruolo utente
    // attivo la maschera di segnalazione
    $delay = 1;
    $pagina_no_report = 'nessun_report_disponibile.php';
    $param_codice_voce_menu = '?codice_voce_menu=GREP';
    $param_descrizione_voce_menu = '&descrizione_voce_menu=Report Gestione report';
    $param_url_pagina_di_ritorno = '&url_pagina_di_ritorno=gestione_report/grep_gestione_menu_report.php';
    $url_pagina_no_report = $pagina_no_report.$param_codice_voce_menu
            .$param_descrizione_voce_menu.$param_url_pagina_di_ritorno;
    redirect($url_pagina_no_report, null, $delay);
    exit();
} else {
    // Ci sono report disponibili per il ruolo utente
    // attivo la maschera di selezione e attivazione report
    $pagina_selezione_report = 'selezione_report.php';
    $param_codice_voce_menu = '?codice_voce_menu=GREP';
    $param_descrizione_voce_menu = '&descrizione_voce_menu=Report Gestione report';
    $url_pagina_selezione_report = $pagina_selezione_report.$param_codice_voce_menu
            .$param_descrizione_voce_menu.$param_url_pagina_di_ritorno;
    //$url_pagina_selezione_report = $pagina_selezione_report.$param_codice_voce_menu.$param_descrizione_voce_menu;
    $delay = 1;
    redirect($url_pagina_selezione_report, null, $delay);
    exit();
}