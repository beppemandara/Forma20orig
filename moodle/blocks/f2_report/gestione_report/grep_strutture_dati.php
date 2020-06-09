<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - giugno 2015
 * 
 * Definizione delle strutture dati (per ogni struttura è definita una classe)
 */
class EML_Elenco_parametri {
    public $id_parametro;
    public $nome_parametro;
    public $flag_S_N;
} // EML_Elenco_parametri
class EML_Elenco_report {
    public $id_menu_report;
    public $id_report;
    public $posizione_in_elenco_report;
    public $flag_attivo;
    public $nome_report;
    public $nome_file_pentaho;
    public $formato_default;
    public $numero_parametri;
    public $numero_ruoli;
} // EML_Elenco_report
class EML_Elenco_ruoli {
    public $id_ruolo;
    public $nome_ruolo;
    public $flag_S_N;
} // EML_Elenco_ruoli
class EML_Voci_menu_report {
    public $id_voce;
    public $cod_voce;
    public $descr_voce;
    public $flag_attiva;
    public $numero_totale_report;
    public $numero_report_attivi;
} // EML_Voci_menu_report
class EML_RECtbl_eml_grep_feed_back {
    public $id;
    public $operazione;
    public $stato;
    public $url;
    public $nota_1;
    public $nota_2;
    public $nota_3;
    public $nota_4;
} //EML_RECtbl_eml_grep_feed_back
class EML_RECmdl_f2_csi_pent_menu_report {
    public $id;
    public $descrizione;
    public $attiva;
    public $codice;
} //EML_RECmdl_f2_csi_pent_menu_report
class EML_RECmdl_f2_csi_pent_report {
    public $id;
    public $id_menu_report;
    public $nome_report;
    public $nome_file_pentaho;
    public $posizione_in_elenco_report;
    public $attivo;
    public $formato_default;
} //EML_RECmdl_f2_csi_pent_report