<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - aprile 2015
 * 
 * Definizione delle strutture dati (per ogni struttura è definita una classe)
 */
class EML_Corsi_in_gestione {
    public $id_corso;
    public $cod_corso;
    public $titolo_corso;
    public $moduli_monitorati;
    public $edizioni_monitorate;
    public $tracciato_completamento;
} // EML_Corsi_in_gestione
class EML_Corsi_inseribili {
    public $id_forma20;
    public $shortname;
    public $cod_corso;
    public $titolo;
    public $id_riforma;
    public $data_inizio;
    public $stato;
} // EML_Corsi_inseribili
class EML_Stato_inserimento {
    public $id_corso;
    public $cod_corso;
    public $titolo_corso;
    public $numero_edizioni;
    public $numero_edizioni_monitorate;
    public $mysqli_err_code_ins_edizioni;
    public $numero_risorse;
    public $numero_risorse_monitorabili;
    public $numero_risorse_monitorate;
    public $mysqli_err_code_ins_risorse;
    public $enablecompletion;
} //EML_Stato_inserimento
class EML_RECmdl_course {
    public $id;
    public $category;
    public $sortorder;
    public $fullname;
    public $shortname;
    public $idnumber;
    public $summary;
    public $summaryformat;
    public $format;
    public $showgrades;
    public $modinfo;
    public $newsitems;
    public $startdate;
    public $numsections;
    public $marker;
    public $maxbytes;
    public $legacyfiles;
    public $showreports;
    public $visible;
    public $visibleold;
    public $hiddensections;
    public $groupmode;
    public $groupmodeforce;
    public $defaultgroupingid;
    public $lang;
    public $theme;
    public $timecreated;
    public $timemodified;
    public $requested;
    public $restrictmodules;
    public $enablecompletion;
    public $completionstartonenrol;
    public $completionnotify;
} // EML_RECmdl_course
class EML_RECtbl_eml_grfo_feed_back {
    public $id;
    public $id_corso;
    public $cod_corso;
    public $titolo_corso;
    public $operazione;
    public $stato;
    public $url;
    public $flag_parametro_id_corso;
    public $nota;
} //EML_RECtbl_eml_grfo_feed_back 
class EML_RECtbl_eml_grfo_log {
    public $id;
    public $data;
    public $id_corso;
    public $cod_corso;
    public $titolo_corso;
    public $pagina;
    public $livello_msg;
    public $cod_msg;
    public $descr_msg;
    public $username;
    public $utente;
    public $nota;
} //EML_RECtbl_eml_grfo_log
class EML_RECtbl_eml_pent_moduli_corsi_on_line {
    public $id_corso;
    public $cod_corso;
    public $titolo_corso;
    public $progressivo;
    public $id_modulo;
    public $tipo_modulo;
    public $istanza_modulo;
    public $nome_modulo;
    public $visibile;
    public $monitorabile;
    public $posizione_in_report;
    public $flag_punteggio_finale;
} //EML_RECtbl_eml_pent_moduli_corsi_on_line
class EML_RECtbl_eml_pent_edizioni_corsi_on_line {
    public $id_corso;
    public $cod_corso;
    public $titolo_corso;
    public $id_edizione;
    public $edizione;
    public $data_inizio;
    public $flag_monitorata_S_N;
} //EML_RECtbl_eml_pent_edizioni_corsi_on_line