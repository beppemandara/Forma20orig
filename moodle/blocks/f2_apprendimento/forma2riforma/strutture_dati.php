<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * Definizione delle strutture dati (per ogni struttura è definita una classe)
 */
class EML_Corsi_in_gestione {
    public $id_mapping;
    public $shortname;
    public $titolo;
    public $perc_x_cfv;
    public $va_default;
    public $data_inizio;
    public $stato;
    public $nota;
} // EML_Corsi_in_gestione
class EML_Corsi_mappabili {
    public $id_forma20;
    public $shortname;
    public $cod_corso;
    public $titolo;
    public $id_riforma;
    public $data_inizio;
    public $stato;
} // EML_Corsi_mappabili
class EML_Dati_utente {
    public $id_utente_Forma;
    public $codice_fiscale;
    public $matricola;
    public $cognome;
    public $nome;
    public $email;
    public $sesso;
    public $categoria;
    public $ap;
    public $cod_settore;
    public $settore;
    public $cod_direzione;
    public $direzione;
} // EML_Dati_utente
class EML_Scorm_corso {
    public $scorm_id;
    public $scorm_name;
} // EML_Scorm_corso
class EML_RECmdl_f2_storico_corsi {
    public $id;
    public $matricola;
    public $cognome;
    public $nome;
    public $sesso;
    public $categoria;
    public $ap;
    public $e_mail;
    public $cod_direzione;
    public $direzione;
    public $cod_settore;
    public $settore;
    public $codcorso;
    public $tipo_corso;
    public $data_inizio;
    public $costo;
    public $af;
    public $to_x;
    public $orario;
    public $titolo;
    public $durata;
    public $scuola_ente;
    public $presenza;
    public $codpart;
    public $descrpart;
    public $sub_af;
    public $cfv;
    public $va;
    public $cf;
    public $te;
    public $sf;
    public $lstupd;
} // EML_RECmdl_f2_storico_corsi
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
class EML_RECmdl_f2_anagrafica_corsi {
    public $id;
    public $courseid;
    public $cf;
    public $course_type;
    public $tipo_budget;
    public $af;
    public $subaf;
    public $to_x;
    public $flag_dir_scuola;
    public $id_dir_scuola;
    public $te;
    public $sf;
    public $orario;
    public $viaente;
    public $localita;
    public $anno;
    public $note;
    public $determina;
    public $costo;
    public $durata;
    public $num_min_all;
    public $num_norm_all;
    public $num_max_all;
    public $dir_proponente;
    public $timemodified;
    public $usermodified;
} // EML_RECmdl_f2_anagrafica_corsi
class EML_RECmdl_f2_fornitori {
    public $id;
    public $id_org;
    public $denominazione;
    public $cognome;
    public $nome;
    public $url;
    public $partita_iva;
    public $codice_fiscale;
    public $codice_creditore;
    public $tipo_formazione;
    public $stato;
    public $nota;
    public $indirizzo;
    public $cap;
    public $citta;
    public $provincia;
    public $paese;
    public $fax;
    public $telefono;
    public $email;
    public $preferiti;
} // EML_RECmdl_f2_fornitori
class EML_RECmdl_org {
    public $id;
    public $fullname;
    public $shortname;
    public $description;
    public $idnumber;
    public $frameworkid;
    public $path;
    public $depthid;
    public $parentid;
    public $sortorder;
    public $visible;
    public $timecreated;
    public $timemodified;
    public $usermodified;
} // EML_RECmdl_org
class EML_RECmdl_scorm {
    public $id;
    public $course;
    public $name;
    public $reference;
    public $summary;
    public $version;
    public $maxgrade;
    public $grademethod;
    public $whatgrade;
    public $maxattempt;
    public $updatefreq;
    public $md5hash;
    public $launch;
    public $skipview;
    public $hidebrowse;
    public $hidetoc;
    public $hidenav;
    public $auto;
    public $popup;
    public $options;
    public $width;
    public $height;
    public $timemodified;
} // EML_RECmdl_scorm
class EML_RECmdl_scorm_scoes_track_and_user {
    public $scoes_trackid;
    public $userid;
    public $scormid;
    public $scoid;
    public $attempt;
    public $element;
    public $value;
    public $timemodified;
    public $username;
    public $firstname;
    public $lastname;
} // EML_RECmdl_scorm_scoes_track_and_user
class EML_RECmdl_f2_forma2riforma_log {
    public $id;
    public $shortname;
    public $data_ora;
    public $codice;
    public $descrizione;
} // EML_RECmdl_f2_forma2riforma_log
class EML_RECmdl_f2_forma2riforma_mapping {
    public $id;
    public $shortname;
    public $id_riforma;
    public $id_forma20;
    public $perc_x_cfv;
    public $va_default;
    public $data_inizio;
    public $stato;
    public $nota;
} // EML_RECmdl_f2_forma2riforma_mapping
class EML_RECmdl_f2_forma2riforma_partecipazioni {
    public $id;
    public $id_mapping;
    public $matricola;
    public $id_user_Riforma;
    public $cognome_Riforma;
    public $nome_Riforma;
    public $id_scorm_Riforma;
    public $punteggio_Riforma;
    public $id_user_Forma;
    public $cognome_Forma;
    public $nome_Forma;
    public $codice_fiscale_Forma;
    public $sesso_Forma;
    public $email_Forma;
    public $categoria_Forma;
    public $ap_Forma;
    public $cod_settore_Forma;
    public $settore_Forma;
    public $cod_direzione_Forma;
    public $direzione_Forma;    
    public $stato;
    public $nota;
} // EML_RECmdl_f2_forma2riforma_partecipazioni