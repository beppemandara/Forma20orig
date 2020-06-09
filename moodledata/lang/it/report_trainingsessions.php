<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'report_trainingsessions', language 'it', branch 'MOODLE_27_STABLE'
 *
 * @package   report_trainingsessions
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['activitytime_help'] = '<p>Questo calcolo del tempo considera tutto il tempo impiegato in attività del corso, escludendo i tempi di visualizzazione.  In alcuni casi (quando si utilizza il Controllo del tempo di apprendimento (non standard) con l\'allocazione temporale standard (http://github.com/vfremaux/moodle-mod_learningtimecheck.git), viene utilizzata quest\'ultima piuttosto che i tempi reali estratti dal log.</p>';
$string['addmodulelabel'] = 'Aggiungi il modulo attività';
$string['addmoduletitle'] = 'Aggiungi un modulo attività desiderato per aggiungere la valutazione al report';
$string['advancement'] = 'Avanzamento';
$string['allcourses'] = 'Tutti i corsi';
$string['allgroups'] = 'Tutti i gruppi';
$string['authoritysign'] = 'Autorità formativa';
$string['batchdate'] = 'Data attività';
$string['batchdate_help'] = '<p>Questa impostazione indica la data esatta in cui il batch verrà lanciato e i documenti generati. Se si teme che i documenti siano troppo pesanti (molti studenti, o molti documenti da seguire), scegliere una data / ora in un periodo di bassa attività del server.</p>';
$string['batchreports_task'] = 'Batch report';
$string['bgcolor'] = 'Colore di sfondo';
$string['checklistadvice'] = 'Effetti di completamento speciali';
$string['chooseagroup'] = 'Scegliere un gruppo';
$string['chooseaninstitution'] = 'Scegliere una istituzione';
$string['colors'] = 'Colori';
$string['columnname'] = 'Nome colonna:';
$string['connections'] = 'Connessioni';
$string['contiguoussessions'] = 'Sessioni contigue';
$string['coupling'] = 'Accoppiamento';
$string['course'] = 'Corso';
$string['courseglobals'] = 'Aree globali corso';
$string['coursegrade'] = 'Abilita conteggio corso';
$string['courselabel'] = 'come colonna:';
$string['coursename'] = 'Nome gruppo';
$string['courseraw'] = 'Batch';
$string['coursesessions'] = 'Sessioni di lavoro nel corso (tempi reali stimati)';
$string['coursestart'] = 'Data inizio corso';
$string['crop'] = 'Escludi gli intervalli di sessione';
$string['csv'] = 'Testo (CSV)';
$string['csvoutputtoiso'] = 'Output Iso CSV';
$string['csvoutputtoiso_desc'] = 'Se abilitato, il report raw del corso verrà generato con codifica ISO-8859-1 per la retrocompatibilità CSV';
$string['currentcourse'] = 'Corso corrente';
$string['disabled'] = '|--------- disabilitato -----------|';
$string['done'] = 'Eseguito:';
$string['duration'] = 'Durata';
$string['elapsed'] = 'Tempo totale';
$string['elapsedadvice'] = 'Il tempo trascorso può essere diverso dall\'intervallo di tempo di sessione a causa del conteggio supplementare del tempo sulle interruzioni di sessioni. Fare riferimento alla configurazione del blocco Utilizza statistiche.';
$string['email'] = 'Email';
$string['enablecoursescore'] = 'Abilita il conteggio del corso';
$string['enablelearningtimecheckcoupling'] = 'Abilita accoppiamento LTC';
$string['enddate'] = 'Data fine';
$string['enterprisesign'] = 'Enterprise';
$string['equlearningtime'] = 'Tempo di training equivalente:';
$string['equlearningtime_help'] = '<p>Il tempo di apprendimento equivalente riassume tutto il tempo trascorso nel corso, inclusi i tempi di assegnazione standard se
si utilizza il modulo di controllo di controllo del tempo di apprendimento (http://github.com/vfremaux/moodle-mod_learningtimecheck.git).</p>';
$string['errorbadcoursestructure'] = 'Errore struttura corso: bad id {$a}';
$string['errorbadviewid'] = 'vista report non esistente';
$string['errorcoursetoolarge'] = 'Il corso è troppo grande. Scegliere un gruppo';
$string['errornotingroup'] = 'Non si ha accesso a tutti gli utenti e non si ha la membership di ogni gruppo';
$string['extrauserinfo'] = 'Informazioni addizionali utente nel report';
$string['extrauserinfo_desc'] = 'è possibile opzionalmente aggiungere un campo dato utente alla parte di informazioni sull\'utente';
$string['firstaccess'] = 'Primo accesso';
$string['firstconnection'] = 'Prima connessione';
$string['firstenrolldate'] = 'Prima iscrizione';
$string['firstname'] = 'Nome';
$string['from'] = 'Da';
$string['generatereports'] = 'Genera report';
$string['gradesettings'] = 'Impostazioni valutazione';
$string['head1application'] = 'I colori Head 1 sono usati nella riga superiore dell\'intestazione quando possibile';
$string['head2application'] = 'I colori Head 2 sono usati nella riga normale di intestazione appena sopra alle colonne di dati, questo nel caso più comune';
$string['head3application'] = 'I colori Head 3 sono usati alla fine della tabella di ripeilogo, quando disponibile';
$string['headsection'] = 'Sezione di intestazione';
$string['hideemptymodules'] = 'Nascondi moduli vuoti';
$string['hideemptymodules_desc'] = 'Se abilitato, i moduli vuoti (con tempo nullo) non vengono stampati nei report.';
$string['hits'] = 'Massimi';
$string['hitslastweek'] = 'Massimi ultima settimana';
$string['id'] = 'ID';
$string['in'] = 'Nel tempo';
$string['incourses'] = 'Nei corsi';
$string['insessiontime'] = 'All\'interno delle regole del tempo di aprrendimento';
$string['insessiontime_help'] = 'Questo è "nel" tempo di apprendimento che corrisponde alle regole di verifica';
$string['institution'] = 'Istituzione';
$string['institutions'] = 'Istituzioni';
$string['instructure'] = 'Tempo nelle attività del corso';
$string['interactive'] = 'Interattiva';
$string['interactivetitle'] = 'Produci il batch ora!';
$string['item'] = 'Argomento';
$string['items'] = 'Argomenti';
$string['lastaccess'] = 'Ultimo accesso';
$string['lastname'] = 'Cognome';
$string['layout'] = 'Aspetto del documento';
$string['learningtimecheckadvice_help'] = '<p>Quando si utilizza un modulo di controllo dell\'apprendimento che consente agli insegnanti di convalidare le attività senza l\'interazione degli studenti nel corso, potrebbe apparire qualche discrepanza nelle informazioni. </ P>
<P> Questa è una situazione normale che riporta informazioni consistenti riguardo l\'effettivo Uso della piattaforma</p>';
$string['learningtimesessioncrop'] = 'Operazione esterna all\'intervallo sessioni';
$string['learningtimesessioncrop_desc'] = 'Durante l\'accoppiamento con il controllo del tempo di apprendimento, le sessioni non valide potrebbero non essere considerate o mantenute e marcate solo nei report';
$string['libsmissing'] = 'Questa caratteristica è stata disabilitata a causa della mancanza di librerie. Installa le librerie da http://github.com/vfremaux/moodle-local_vflibs per abilitare la generazione dei PDF';
$string['mark'] = 'Segna le sessioni di intervallo';
$string['modgrade'] = 'Valutazione attività';
$string['modulegrade'] = 'Modulo attività';
$string['modulegrades'] = 'Valutazioni attività';
$string['modulegrades_help'] = 'È possibile aggiungere qui più colonne al rapporto di valutazione, scegliendo il modulo di attività che sarà la fonte del punteggio.
È inoltre possibile definire l\'etichetta di colonna che verrà utilizzata per questa colonna nei fogli di report. Se lasciato vuoto, il nome della colonna sarà in ordine della disponibilità, del codice identificativo del modulo corso, o di un identificatore di modulo incorporato da Moodle.';
$string['never'] = 'Mai';
$string['newtask'] = 'Aggiungi nuovo batch';
$string['nodata'] = 'Nessun dato corso';
$string['nopermissiontoview'] = 'Non si dispone di sufficienti autorizzazioni nel corso per visualizzare questa informazione.';
$string['nosessions'] = 'Nessun dato sessione misurabile';
$string['nostructure'] = 'Nessuna struttura corso misurabile rilevata';
$string['nothing'] = 'Nessun utente da compilare';
$string['now'] = 'Ora !';
$string['onefulluserpersheet'] = 'Una informazione completa per utente per foglio';
$string['oneuserperrow'] = 'Una informazione di ripeilogo per utente per riga in un singolo foglio';
$string['othertime'] = 'Altro tempo (non attività)';
$string['othertime_help'] = '<p>Tempo impiegato che non viene direttamente assegnato al modulo attività corso.</p>';
$string['out'] = 'Fuori tempo';
$string['outofgroup'] = 'Nessun gruppo';
$string['outofstructure'] = 'Altro tempo di utilizzo corso';
$string['outputdir'] = 'Directory di output';
$string['outputdirectory'] = 'Directory di output nei file locali corso';
$string['outputdir_help'] = '<p>È possibile selezionare una sottodirectory di output per la generazione dei documenti di output. Si noti che il contesto di archiviazione dove trovare questi documenti è il corso da dove si è programmato il batch, anche se la compilazione del corso ha menzionato "Tutti i corsi" </ p>';
$string['outsessiontime'] = 'Tempo rimanente (oltre le regole)';
$string['outsessiontime_help'] = 'Questo è il tempo di apprendimento restante che non corrisponde alle regole di verifica';
$string['over'] = 'oltre';
$string['parts'] = 'parti';
$string['pdf'] = 'PDF';
$string['pdfabsoluteverticaloffset'] = 'Doc abs. vert. offset';
$string['pdfabsoluteverticaloffset_desc'] = 'Indica l\'offset di inizio della generazione di contenuti rispetto alla parte superiore della pagina in formato pdf (in mm).';
$string['pdfpage'] = 'Pagina:';
$string['pdfpagecutoff'] = 'Altezza di taglio della pagina PDF';
$string['pdfpagecutoff_desc'] = 'Altezza nella pagina per il passaggio alla pagina successiva (in mm).';
$string['pdfreportfooter'] = 'Immagine piè di pagina nel report PDF';
$string['pdfreportfooter_desc'] = 'Fornisce una immagine JPG per il piè di pagina (sino a 880x100 pixels)';
$string['pdfreportheader'] = 'Immagine di intestazione nel report PDF';
$string['pdfreportheader_desc'] = 'Fornisce una immagine JPG per l\'intestazione (sino a 880x220 pixels)';
$string['pdfreportinnerheader'] = 'Immagine interna nel report PDF';
$string['pdfreportinnerheader_desc'] = 'Fornisce una immagine PDF nella parte superiore delle pagine interne (sino a 880x150 pixels). Se non definita, verrà utilizzata l\'intestazione della prima pagina.';
$string['periodshift'] = 'Periodo di spostamento';
$string['periodshiftto'] = 'Sposta "a" solo la data';
$string['pluginname'] = 'Sessioni di training';
$string['printidnumber'] = 'Stampa numero ID';
$string['printidnumber_desc'] = 'Se definito, aggiunge il numero ID ai report';
$string['printsessiontotal'] = 'Visualizza il tempo globale impiegato nella sessione';
$string['printsessiontotal_desc'] = 'Non visualizza il tempo totale della sessione nei report di sessione a schermo';
$string['quickgroupcompile'] = '<h3>Compilazione rapida per {$a} utenti:<h3><p> La compilazione rapida fornisce un veloce riepilogo per i gruppi con meno di 50 utenti, direttamente nella directory root dei file del corso.</p>';
$string['quickmonthlyreport'] = 'Report rapido mensile (PDF)';
$string['range'] = 'Intervallo';
$string['recipient'] = 'Contenitore';
$string['recipient_desc'] = 'Contenitore di default per i documenti PDF. Può essere localmente sovrascritto da ogni operatore.';
$string['replaydelay'] = 'Ripeti ritardo (min)';
$string['replaydelay_help'] = '<p>Se impostato su un valore positivo (in minuti), il batch non verrà eliminato dopo l\'esecuzione, ma ripetuto continuamente con quel ritardo. La data di inizio e / o la data di fine verrà spostata di conseguenza se è selezionata una ripetizione </ p>';
$string['reportdate'] = 'Data report';
$string['reportfilemanager'] = 'Gestione file dei report';
$string['reportformat'] = 'Formato documento';
$string['reportforuser'] = 'Report per';
$string['reportlayout'] = 'Aspetto report';
$string['reports'] = 'Report';
$string['reportscope'] = 'Intervallo di validità';
$string['reportscope_help'] = '<p>Alcuni report consentono di analizzare tutti i corsi di un utente. Notare che alcuni report non utilizzano gli intervalli di validità.<7p>';
$string['role'] = 'Ruolo';
$string['scheduledbatches'] = 'Batch programmati';
$string['scoresettings'] = 'Impostazioni report punteggio';
$string['scoresettingsadvice'] = 'Nei report di riepilogo del corso (un utente per riga), è possibile aggiungere colonne di output aggiuntive con i punteggi del registro valutazioni. Si può aggiungere il voto globale del corso o scegliere di aggiungere uno (o più) singoli gvoti di attività nel report.';
$string['sectionname'] = 'Nome sezione';
$string['seedetails'] = 'Visualizza dettagli';
$string['selectforreport'] = 'Seleziona per report';
$string['sessionduration'] = 'Durata sessione';
$string['sessionend'] = 'Fine sessione';
$string['sessionreportdoctitle'] = 'Report sesione';
$string['sessionreports'] = 'Report sessione utente';
$string['sessionreporttitle'] = 'Cattura documento report sessione';
$string['sessionreporttitle_desc'] = 'Stampa sulla prima pagina del report sessione utente';
$string['sessions'] = 'Sessioni di lavoro (tempi reali stimati)';
$string['sessionsonly'] = 'Solo sessioni utente';
$string['sessionstart'] = 'Inizio sessione';
$string['showhits'] = 'Mostra eventi (csv)';
$string['showhits_desc'] = 'Se impostato, il conteggio massimo verrà aggiunto alle linee CSV';
$string['singleexec'] = 'Singola esecuzione';
$string['siteglobals'] = 'Sito (non sessioni corso)';
$string['startdate'] = 'Data inizio';
$string['structureitem'] = 'Argomento corso tracciabile';
$string['structuretotal'] = 'Totale {$a}';
$string['studentsign'] = 'Studente';
$string['task'] = 'Attività {$a}';
$string['taskname'] = 'Attività';
$string['taskrecorded'] = 'Attività successivamente registrata';
$string['teachersign'] = 'Docente';
$string['textapplication'] = 'Questa è l\'impostazione per il testo di default del docuemento';
$string['textcolor'] = 'Colore del testo';
$string['timeelapsed'] = 'Tempo impiegato';
$string['timeelapsedcurweek'] = 'Tempo impiegato sett. corrente';
$string['timeperpart'] = 'Tempo impiegato per parti';
$string['timespent'] = 'Impiegato';
$string['timespentlastweek'] = 'Impiegato ultima settimana';
$string['to'] = 'A';
$string['todate'] = 'Data fine';
$string['tonow'] = 'A ora';
$string['toobig'] = '<p> Il gruppo di compilazione è troppo grande per essere eseguito in una compilazione rapida. Sarebbe preferibile programmare un batch ritardato in un momento che non pregiudicherà il lavoro degli utenti attuali. <br/> Per impostare un batch, predefinire i parametri di compilazione nel modulo sopra indicato e registrare un nuovo batch con la configurazione desiderata e impostare il batch tempo e directory di output dalla origine della posizione di archiviazione del file di corso (percorso relativo, percorso assoluto rifiutato). </ p> <p> È inoltre possibile programmare un batch di compilazione regolare che compilerà secondo il parametro  di "replaydelay". </ p>';
$string['totalduration'] = 'Durata totale';
$string['totalsessions'] = 'Tempo totale di sessione';
$string['totalsessiontime'] = 'Tempo di lavoro totale di sessione';
$string['totalsessiontime_help'] = 'Si noti che l\'elenco delle sessioni conta alcune durate che possono essere esterne a questo corso. Il tempo totale di sessione dovrebbe essere solitamente superiore a quello del calcolo del tempo di corso';
$string['totalsitetime'] = 'Tempo totale sul sito';
$string['trainingreports'] = 'Report di training';
$string['trainingsessions'] = 'Sessioni di training';
$string['trainingsessions:batch'] = 'Batch report possibile';
$string['trainingsessions:downloadreports'] = 'Scaricamento dei docuemnti di report possibile';
$string['trainingsessions:iscompiled'] = 'è compilato nei report';
$string['trainingsessionsreport'] = 'Report di sessioni di training';
$string['trainingsessions_report_advancement'] = 'Report progresso';
$string['trainingsessions_report_connections'] = 'Report connessione';
$string['trainingsessions_report_institutions'] = 'Report istituzione';
$string['trainingsessionsscores'] = 'Punteggio aggiunto ai report';
$string['trainingsessions:view'] = 'Visualizzazione report sessione di training possibile';
$string['trainingsessions:viewother'] = 'Visualizzazione report sessione di training di altri utenti possibile';
$string['unvisited'] = 'Non visitato';
$string['updatefromaccountstart'] = 'Ottenuto dal primo accesso utente';
$string['updatefromcoursestart'] = 'Ottenuto dall\'avvio corso';
$string['uploadglobals'] = 'Caricamento file';
$string['uploadresult'] = 'Scaricamento risultati grezzi';
$string['user'] = 'Per partecipante';
$string['userlist'] = 'Ona riga per partecipante';
$string['usersheets'] = 'Un foglio per partecipante';
$string['usersummary'] = 'Riepilogo partecipante';
$string['visiteditems'] = 'Argomenti visitati';
$string['weekstartdate'] = 'Inizio settimana';
$string['xls'] = 'XLS';
