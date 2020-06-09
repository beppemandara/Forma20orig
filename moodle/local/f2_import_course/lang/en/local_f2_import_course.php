<?php // $Id$ 

$string['pluginname'] = 'Importa corsi da Access';
$string['linkname'] = 'Importa corsi da Access';
$string['navbartitle'] = 'Importa corsi da Access';
$string['title_import_course_access'] = 'Importa/Aggiorna i corsi da un file access';
$string['fieldset_title'] = 'Inserire i dati';
$string['f2_import_course:importcourseaccess'] = 'Importare/Aggiornare i corsi da un file access';
$string['f2_import_course:importeditionsprg'] = 'Importa le edizioni dei Corsi Programmati';
$string['importeditionsprg'] = 'Quantificazione';
$string['navbartitleprg'] = 'Importare le edizioni dei Corsi Programmati';
$string['uploadprg'] = 'Caricamento delle edizioni di Corsi Programmati';
$string['uploadprg_help'] = 'Le edizioni sono caricate attraverso un file CSV. Ogni file dev\'essere strutturato nel seguente modo:

* Ogni linea deve contenere un record
* Ogni record &egrave; un insieme di dati che dev\'essere separato da punto e virgola (o un altro delimitatore)
* Il primo record contiene una lista di campi definenti il formato dell\'intero file';
$string['fileedzpian'] = 'File delle Edizioni Pianificate';
$string['fileedzpian_help'] = 'File contenente le informazioni sull\'edizioni dei vari corsi. I campi del template sono:

* ID
* ANNO_PIANIFICAZIONE
* CODICE_CORSO
* SEDE
* EDIZIONE
* SESSIONE';
$string['filepostris'] = 'File dei Post Riservati';
$string['filepostris_help'] = 'File contenente le informazioni sui posti riservati ad ogni direzioni nelle singole edizioni. I campi del template sono: 

* IDFK
* CODICE_DIREZIONE
* NUMERO_POSTI_RISERVATI';
$string['csvdelimiter'] = 'Delimitatore CSV';
$string['encoding'] = 'Codifica';
$string['rowpreviewnum'] = 'Record in anteprima';
$string['upload'] = 'Carica';
$string['invalideditionsid'] = 'Il file che si sta cercando di importare contiene degli ID non validi';
$string['invalideditionsap'] = 'Il file che si sta cercando di importare non sempre ha l\'ANNO PIANIFICAZIONE correttamente valorizzato';
$string['invalideditionscc'] = 'Il file che si sta cercando di importare non sempre ha il CODICE CORSO correttamente valorizzato';
$string['invalideditionssede'] = 'Il file che si sta cercando di importare non sempre ha la SEDE correttamente valorizzata';
$string['invalideditionsedition'] = 'Il file che si sta cercando di importare non sempre ha l\'EDIZIONE correttamente valorizzata';
$string['invalideditionsas'] = 'Il file che si sta cercando di importare non sempre ha l\'ANNO SVOLGIMENTO correttamente valorizzato';
$string['invalideditionsss'] = 'Il file che si sta cercando di importare non sempre ha la SESSIONE SVOLGIMENTO correttamente valorizzata';
$string['invalideditionses'] = 'Il file che si sta cercando di importare non sempre ha l\'EDIZIONE SVOLGIMENTO correttamente valorizzata';
$string['corsi_da_importare'] = 'Corsi da importare o aggiornare';
$string['corsi_importati'] = 'Corsi importati';
$string['corsi_aggiornati'] = 'Corsi aggiornati';
$string['anomalie'] = 'Anomalie';
$string['warnings'] = 'Warnings';
$string['categoria'] = 'Categoria';
$string['nome_feedback_docente'] = 'Questionario nota di sintesi del docente';
//AK-LM: i valori per le 3 chiavi nome_feedback_studente_* DEVONO essere distinte
$string['nome_feedback_studente_obv'] = 'Questionario di gradimento';
$string['nome_feedback_studente_aula'] = 'Questionario aula programmata';
$string['nome_feedback_studente_online'] = 'Questionario e-learning programmato';
$string['alert_feedback_obv'] = 'Attenzione! Per la creazione del questionario di gradimento è stato utilizzato l\'insieme di domande predefinito "Modello Questionario Obiettivo". Se questo non dovesse essere attinente al corso si prega di scegliere un altro modello selezionando il tab "Modello questionari" presente in questa pagina.';
