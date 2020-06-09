<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - luglio 2015
 * 
 * GREP - Gestione Report
 * 
 * cancellazione Voce menu Report
 * 
 * Principali passi:
 * 
 * Definizione di costanti, variabili, function
 * cancello il record richiesto
 * imposto le informazioni sullo stato operazione
 * redirect alla pagina di feed-back
 * 
 */
// Definizione di costanti, variabili, function
require_once '../../../config.php';
require_once "grep_costanti_db.php";
require_once "grep_strutture_dati.php";
require_once "grep_function_db.php";
require_once "../online/lib_eml.php";
// definisco il record per la pagina di feed-back
$rec_tbl_eml_grep_feed_back = new EML_RECtbl_eml_grep_feed_back();
// connessione al data-base
$aus = EML_Connetti_db();
// cancello il record
$id = $_REQUEST['id_voce_menu'];
$rec_mdl_f2_csi_pent_menu_report = new EML_RECmdl_f2_csi_pent_menu_report();
$aus = EML_Get_mdl_f2_csi_pent_menu_report($id, $rec_mdl_f2_csi_pent_menu_report);
$nome_tabella = 'mdl_f2_csi_pent_menu_report';
$clausola_where = ' WHERE id = '.$id;
EML_Del_xxx($nome_tabella, $clausola_where);
// imposto le informazioni sullo stato operazione
$rec_tbl_eml_grep_feed_back->operazione = 'Cancellazione voce di Menu report (dal data-base) ';
$rec_tbl_eml_grep_feed_back->stato = 'Operazione terminata correttamente ';
$rec_tbl_eml_grep_feed_back->url = 'grep_gestione_menu_report.php';
$rec_tbl_eml_grep_feed_back->nota_1 = 'Voce cancellata: '.$rec_mdl_f2_csi_pent_menu_report->descrizione;
$rec_tbl_eml_grep_feed_back->nota_2 = ' ';
$rec_tbl_eml_grep_feed_back->nota_3 = ' ';
$rec_tbl_eml_grep_feed_back->nota_4 = ' ';
$id_x_pagina_feed_back = EML_Ins_tbl_eml_grep_feed_back($rec_tbl_eml_grep_feed_back);
// Redirect alla pagina di feed-back
$url_pagina_feed_back = new moodle_url('grep_feed_back_page.php', array('id'=>$id_x_pagina_feed_back));
$delay = 1;
redirect($url_pagina_feed_back, null, $delay);