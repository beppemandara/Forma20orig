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
 * Strings for component 'scheduler', language 'it', branch 'MOODLE_27_STABLE'
 *
 * @package   scheduler
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['action'] = 'Azione';
$string['actions'] = 'Azioni';
$string['addappointment'] = 'Aggiungi studente';
$string['addcommands'] = 'Aggiungi fasce orarie';
$string['addondays'] = 'Aggiungi appuntamenti su';
$string['addsession'] = 'Aggiungi fasce orarie ripetute';
$string['addsingleslot'] = 'Aggiungi singola fascia oraria';
$string['addslot'] = 'È possibile aggiungere date aggiuntive in qualsiasi momento.';
$string['addstudenttogroup'] = 'Aggiunti questo studente all\'appuntamento di gruppo';
$string['allappointments'] = 'Tutti gli appuntamenti';
$string['allowgroup'] = 'Fascia oraria per 1 solo - clicca per cambiare';
$string['allteachersgrading'] = 'I docenti possono registrare tutti gli appuntamenti';
$string['allteachersgrading_desc'] = 'I docenti possono valutare gli appuntamenti che non sono assegnati a loro.';
$string['alreadyappointed'] = 'Impossibile prenotare. Fascia oraria completa.';
$string['appointagroup_help'] = 'Scegliere se si desidera rendere l\'appuntamento unico, o per un intero gruppo.';
$string['appointingstudent'] = 'Appuntamento per fascia oraria';
$string['appointingstudentinnew'] = 'Appuntamento per un nuova fascia oraria';
$string['appointmentmode'] = 'Imposta modalità di appuntamento';
$string['appointmentmode_help'] = '<p>Ci sono modalità in cui gli appuntamenti possono essere selezionati. </p>
<p><ul>
<li><strong>"<emph>n</emph> appuntamenti in questa agenda":</strong> Lo studente può prenotare solo un numero fisso di appuntamenti in questa attività. Anche se il docente lo contrassegna come "visto", non sarà permesso di prenotare ulteriori appuntamenti. L\'unico modo per ripristinare la capacità di uno studente di prenotare è quello di eliminare gli altri.</li>
<li><strong>"<emph>n</emph> appuntamenti nella fascia oraria":</strong> Lo studente può prenotare un numero fisso di appuntamenti. Una volta che l\'appuntamento è finito e il docente ha segnato lo studente come "visto", lo studente può prenotare ulteriori appuntamenti. Tuttavia lo studente è limitato ad un <em> n° </em> "aperto" (invisibile) fasce orarie in un dato momento.
</li>
</ul>
</p>';
$string['appointmentno'] = 'Appuntamento {$a}';
$string['appointments'] = 'Appuntamenti';
$string['appointmentsummary'] = 'Appuntamento il {$a->startdate} dalle {$a->starttime} alle {$a->endtime} con il {$a->teacher}';
$string['appointsolo'] = 'Solo io';
$string['appointsomeone'] = 'Aggiungi appuntamento';
$string['attendable'] = 'Appuntamenti possibili';
$string['attendablelbl'] = 'Candidati totali per agenda';
$string['attended'] = 'Appuntamenti presenziati';
$string['attendedlbl'] = 'Numero di studenti che hanno presenziato';
$string['attendedslots'] = 'Fasce orarie presenziate';
$string['availableslots'] = 'Orari disponibili';
$string['availableslotsall'] = 'Tutte le fasce orarie';
$string['availableslotsnotowned'] = 'Non di proprietà';
$string['availableslotsowned'] = 'Di tua proprietà';
$string['bookwithteacher'] = 'Docente';
$string['bookwithteacher_help'] = 'Scegli un docente per l\'appuntamento.';
$string['break'] = 'Intervallo tra fasce orarie';
$string['breaknotnegative'] = 'La durata dell\'Intervallo non deve essere negativa';
$string['canbook1appointment'] = 'L\'agenda consente di prenotare ulteriori appuntamenti.';
$string['canbooknappointments'] = 'L\'agenda consente di prenotare {$a} appuntamenti.';
$string['canbooknofurtherappointments'] = 'Non è possibile prenotare ulteriori appuntamenti in questa agenda.';
$string['canbooksingleappointment'] = 'È possibile prenotare un appuntamento in questa agenda.';
$string['canbookunlimitedappointments'] = 'È possibile prenotare qualsiasi numero di appuntamenti in questa agenda.';
$string['chooseexisting'] = 'Scegli tra quelli esistenti';
$string['choosingslotstart'] = 'Scelta dell\'ora di inizio';
$string['choosingslotstart_help'] = 'Cambiare (o scegliere) l\\ora di inizio dell\'appuntamento. Se questo appuntamento è in conflitto con altri in altre fasce orarie, verrà chiesto
se questa fascia oraria sostituisce tutti gli appuntamenti in conflitto. Nota che i nuovi parametri di fascia oraria sostituiranno tutte le precedenti
impostazioni.';
$string['comments'] = 'Commenti';
$string['complete'] = 'Prenotato';
$string['course'] = 'Corso';
$string['cumulatedduration'] = 'Durata degli appuntamenti';
$string['date'] = 'Data';
$string['datelist'] = 'Panoramica';
$string['defaultslotduration'] = 'Durata di default della fascia oraria';
$string['defaultslotduration_help'] = 'La durata predefinita (in minuti) per le fasce orarie';
$string['deleteallslots'] = 'Elimina tutte le fasce orarie';
$string['deleteallunusedslots'] = 'Elimina le fasce orarie non utilizzate';
$string['deletecommands'] = 'Elimina le fasce orarie';
$string['deletemyslots'] = 'Elimina tutte le mie fasce orarie';
$string['deleteselection'] = 'Elimina le fasce orarie selezionae';
$string['deletetheseslots'] = 'Elimina fasce orarie';
$string['deleteunusedslots'] = 'Elimina le mie fasce orarie non usate';
$string['department'] = 'Da dove?';
$string['disengage'] = 'Elimina il mio appuntamento';
$string['distributetoslot'] = 'Distribuisci a tutto il gruppo';
$string['divide'] = 'Dividere in fasce orarie?';
$string['duration'] = 'Durata';
$string['durationrange'] = 'La durata della fascia oraria deve essere compresa tra {$a->min} e {$a->max} minuti.';
$string['email_applied_plain'] = 'Un appuntamento è stato applicato per il {$a->date} alle {$a->time},
dallo studente {$a->attendee} per il corso:

{$a->course_short}: {$a->course}

utilizzando l\'agenda dal titolo "{$a->module}" sul sito: {$a->site}.';
$string['email_applied_subject'] = '{$a->course_short}: Nuovo appuntamento';
$string['email_cancelled_plain'] = 'L\'appuntamento del {$a->date} alle {$a->time},
con lo studente {$a->attendee} per il corso:

{$a->course_short} : {$a->course}

nell\'agenda dal titolo "{$a->module}" sul sito: {$a->site}

è stato eliminato o spostato.';
$string['email_cancelled_subject'] = '{$a->course_short}: Appuntamento eliminato o spostato da uno studente';
$string['emailreminder'] = 'Invia un prememoria';
$string['email_reminder_html'] = '<p>Hai un appuntamento imminente il <strong>{$a->date}</strong>
dalle <strong>{$a->time}</strong> alle <strong>{$a->endtime}</strong><br/>
con <strong><a href="{$a->attendant_url}">{$a->attendant}</a></strong>.</p>

<p>Luogo: <strong>{$a->location}</strong></p>';
$string['emailreminderondate'] = 'Invia un prememoria:';
$string['email_reminder_plain'] = 'Hai un appuntamento imminente
il {$a->date} dalle {$a->time} alle {$a->endtime}
con {$a->attendant}.

Luogo: {$a->location}';
$string['email_reminder_subject'] = '{$a->course_short}: Promemoria di appuntamento';
$string['email_teachercancelled_plain'] = 'Il tuo appuntamento del {$a->date} alle {$a->time},
con {$a->staffrole} {$a->attendant} per il corso:

{$a->course_short}: {$a->course}

nell\'agenda dal titolo "{$a->module}" sul sito: {$a->site}

è stato eliminato. Si prega di inserire una nuova fascia oraria.';
$string['email_teachercancelled_subject'] = '{$a->course_short}: Appuntamento annullato dal docente';
$string['end'] = 'Fine';
$string['enddate'] = 'Fino a';
$string['event_appointmentlistviewed'] = 'Lista Agenda appuntamenti visto';
$string['event_bookingadded'] = 'Prenotazione Agenda aggiunta';
$string['event_bookingformviewed'] = 'Prenotazione Agenda vista';
$string['event_bookingremoved'] = 'Prenotazione Agenda eliminata';
$string['exclusive'] = 'Esclusivo';
$string['exclusivity'] = 'Esclusività';
$string['exclusivityoverload'] = 'La fascia oraria {$a} ha designato più studenti di quanto consentito da questa impostazione.';
$string['explaingeneralconfig'] = 'Queste opzioni possono essere impostate solo a livello di sito e si applicheranno a tutte le agende di questa installazione di Moodle.';
$string['finalgrade'] = 'Valutazione finale';
$string['firstslotavailable'] = 'La prima fascia oraria sarà aperta: {$a}';
$string['forbidgroup'] = 'Fascia oraria di gruppo - clicca per cambiare';
$string['forcewhenoverlap'] = 'Forza se sovrapposto';
$string['forcewhenoverlap_help'] = '<h3>Forzare la creazione di una fascia oraria che si sovrappone ad un altra</h3>
<p>Questo impostazione determina la gestione di una nuova fascia oraria che si sovrappone ad un altra.</p>
<p>Se abilitato, la nuova fascia oraria eliminerà la vecchia.</p>
<p>Se disabilitato, a nuova fascia oraria <em>non</em> sarà creata e rimarrà quella vecchia.</p>';
$string['forcourses'] = 'Scegli gli studenti nei corsi';
$string['friday'] = 'Venerdì';
$string['generalconfig'] = 'Configurazione generale';
$string['grade'] = 'Valutazione';
$string['gradingstrategy'] = 'Metodo di valutazione';
$string['gradingstrategy_help'] = 'In un\'agenda dove gli studenti possono avere diversi appuntamenti, selezionare il metodo di valutazione aggregato.
    Il registro può mostrare <ul><li>voto medio o</li><li>voto più alto</li></ul> che lo studente ha raggiunto.';
$string['group'] = 'gruppo';
$string['groupbreakdown'] = 'Per dimensione del gruppo';
$string['groupscheduling'] = 'Abilita Schedulazione per gruppo';
$string['groupsession'] = 'Sessione di gruppo';
$string['groupsize'] = 'Dimensione del gruppo';
$string['guardtime'] = 'Scadenza per cambio prenotazione';
$string['guardtime_help'] = 'Un blocco impedisce agli studenti di cambiare la loro prenotazione poco prima dell\'appuntamento.
<p>Se abilitato e impostato, per esempio, 2 ore, poi studenti saranno in grado di prenotare una fascia oraria che inizia tra meno di 2 ore (da ora),
e saranno in grado di <b>eliminare</b> un appuntamento se iniziare in meno di 2 ore.</p>';
$string['guestscantdoanything'] = 'Gli ospiti possono fare nulla qui.';
$string['howtoaddstudents'] = 'Per aggiungere studenti di un\'agenda in ambito globale, utilizzare l\'impostazione per il ruolo nel modulo.<br/>Si può anche utilizzare ruoli modulo per definire studenti frequentatori.';
$string['ignoreconflicts'] = 'Ignora conflitti di schedulazione';
$string['ignoreconflicts_help'] = 'Se selezionata, la fascia oraria sarà spostata all\'orario e data richiesti, anche se esistono altre fasce orarie nello stesso periodo. Questo può portare a una sovrapposizione di appuntamenti per alcuni docenti o studenti, quindi deve essere usato con cautela.';
$string['incourse'] = 'nel corso';
$string['introduction'] = 'Introduzione';
$string['isnonexclusive'] = 'Non esclusivo';
$string['lengthbreakdown'] = 'Per durata fascia oraria';
$string['limited'] = 'Limitata ({$a} sinistra)';
$string['location'] = 'Luogo';
$string['location_help'] = 'Scrivere la posizione dove si svolgerà l\'appuntamento.';
$string['markasseennow'] = 'Aggiungi \'Ora\' la prenotazione ed imposta come \'Visto\\';
$string['markseen'] = 'Dopo aver visualizzato un appuntamento di uno studente si prega di contrassegnarli come "Visto" facendo clic sulla casella di controllo vicino alla loro immagine utente sopra.';
$string['maxgrade'] = 'Voto più alto';
$string['maxstudentlistsize'] = 'Lunghezza massima della lista degli studenti';
$string['maxstudentlistsize_desc'] = 'La lunghezza massima della lista degli studenti che hanno bisogno di prendere un appuntamento, come mostrato nella vista docente dell\'agenda. Se ci sono più studenti rispetto a questo, non sarà visualizzata nessuna lista.';
$string['maxstudentsperslot'] = 'Numero massimo di studenti per fascia oraria';
$string['maxstudentsperslot_desc'] = 'fasce orarie di gruppo / fasce orarie non esclusive possono avere al massimo questo numero di studenti. Nota, in aggiunta, l\'impostazione "illimitato" può sempre essere scelto per una fascia oraria.';
$string['meangrade'] = 'Voto medio';
$string['meetingwith'] = 'Incontro con il tuo';
$string['meetingwithplural'] = 'Incontro con il tuo';
$string['minutes'] = 'minuti';
$string['minutesperslot'] = 'minuti per fascia oraria';
$string['missingstudents'] = '{$a} Studenti devono ancora prenotarsi ad un appuntamento';
$string['missingstudentsmany'] = '{$a} Studenti devono ancora prendere un appuntamento. Nessun elenco viene visualizzato a causa delle dimensioni.';
$string['mode'] = 'Modalità';
$string['modeappointments'] = 'appuntamento(i)';
$string['modeintro'] = 'Studenti posso prenotare';
$string['modeoneatatime'] = 'Nella fascia oraria';
$string['modeoneonly'] = 'In questa agenda';
$string['modulename'] = 'Agenda';
$string['modulename_help'] = 'L\'attività Agenda aiuta ad organizzare gli appuntamenti con i tuoi studenti.

Il docente specifica la fascia oraria per gli incontri, gli studenti possono scegliere uno di loro su Moodle.
Il docente può registrare, comunque, la prenotazione all\'interno dell\'Agenda.

La pianificazione di gruppo è supportato, ovvero, ogni fascia oraria può ospitare diversi studenti, ed eventualmente è possibile programmare appuntamenti per interi gruppi contemporaneamente.';
$string['modulename_link'] = 'mod/scheduler/view';
$string['modulenameplural'] = 'Schedulatori';
$string['monday'] = 'Lunedì';
$string['myappointments'] = 'I miei appuntamenti';
$string['name'] = 'Nome agenda';
$string['needteachers'] = 'Impossibile aggiungere fasce orarie se non ci sono docenti';
$string['negativerange'] = 'Non può esserci una differenza negativa';
$string['never'] = 'Mai';
$string['noappointments'] = 'Nessun appuntamento';
$string['nogroups'] = 'Nessun gruppo disponibile per la pianificazione.';
$string['noresults'] = 'Nessun risultato.';
$string['noschedulers'] = 'Non ci sono agende';
$string['noslots'] = 'Non ci sono date disponibili.';
$string['nostudenttobook'] = 'Nessuno studente ha prenotato';
$string['note'] = 'Valutazione';
$string['noteacherforslot'] = 'Nessun docente per le fasce orarie';
$string['noteachershere'] = 'Nessun docente disponibile';
$string['notenoughplaces'] = 'Spiacenti, non ci sono abbastanza appuntamenti liberi in questa fascia oraria.';
$string['notifications'] = 'Notifiche';
$string['notifications_help'] = 'Quando questa opzione è abilitata, docenti e studenti riceveranno notifiche quando gli appuntamenti sono attivati o eliminati.';
$string['notseen'] = 'Non visto';
$string['now'] = 'Ora';
$string['occurrences'] = 'Presenze';
$string['on'] = 'on';
$string['onedaybefore'] = '1 giorno prima della fascia oraria';
$string['oneslotadded'] = '1 fascia oraria aggiunto';
$string['oneweekbefore'] = '1 settimana prima della fascia oraria';
$string['onthemorningofappointment'] = 'La mattina dell\'appuntamento';
$string['otherstudents'] = 'Altri partecipanti';
$string['overall'] = 'Generale';
$string['overlappings'] = 'Alcune fasce orarie si sovrappongono';
$string['pluginadministration'] = 'Amministrazione Agenda';
$string['pluginname'] = 'Agenda';
$string['registeredlbl'] = 'Studente(i) che ha prenotato, ma non accettato(i)';
$string['reminder'] = 'Promemoria';
$string['resetappointments'] = 'Eliminare appuntamenti e valutazioni';
$string['resetslots'] = 'Eliminare fasce orarie';
$string['return'] = 'Torna al corso';
$string['revoke'] = 'Revoca la prenotazione';
$string['saturday'] = 'Sabato';
$string['save'] = 'Salva';
$string['savechoice'] = 'Salva la mia scelta';
$string['saveseen'] = 'Salva Visto';
$string['schedule'] = 'Prenota';
$string['scheduleappointment'] = 'Prenota appuntamento per {$a}';
$string['schedulecancelled'] = '{$a} :I tuoi appuntamenti sono eliminati o spostati';
$string['schedulegroups'] = 'Visualizza per gruppo';
$string['scheduleinnew'] = 'Prenotazione in una nuova fascia oraria';
$string['scheduleinslot'] = 'Prenota a fasce orarie';
$string['scheduler'] = 'Agenda';
$string['scheduler:addinstance'] = 'Aggiungere Agenda';
$string['scheduler:attend'] = 'Presenziare studenti';
$string['scheduler:canscheduletootherteachers'] = 'Pianificare appuntamenti per gli altri membri dello staff';
$string['scheduler:canseeotherteachersbooking'] = 'Visualizzare e sfogliare le prenotazioni degli altri docenti';
$string['scheduler:manage'] = 'Gestire le tue fasce orarie e appuntamenti';
$string['scheduler:manageallappointments'] = 'Gestire tutti gli appuntamenti';
$string['schedulestudents'] = 'Visualizza per Studente';
$string['seen'] = 'Visualizzato';
$string['selectedtoomany'] = 'Hai selezionato troppe fasce orarie. È possibile selezionarne non più di {$a}.';
$string['showemailplain'] = 'Visualizza indirizzi e-mail in formato testo';
$string['showemailplain_desc'] = 'Nella vista docente dell\'agenda, mostrare gli indirizzi e-mail di studenti che necessitano di un appuntamento in formato testo, oltre a mailto: link.';
$string['showparticipants'] = 'Visualizza partecipanti';
$string['slotdescription'] = '{$a->status} il {$a->startdate} dalle {$a->starttime} alle {$a->endtime} a {$a->location} con {$a->facilitator}.';
$string['slot_is_just_in_use'] = 'Siamo spiacenti, l\'appuntamento è appena stato scelto da un altro studente! Riprova.';
$string['slots'] = 'fasce orarie';
$string['slotsadded'] = '{$a} fasce orarie sono state aggiunte';
$string['slottype'] = 'Tipi fascia oraria';
$string['slotupdated'] = '1 fascia oraria aggiornata';
$string['staffbreakdown'] = 'Per {$a}';
$string['staffrolename'] = 'Nome ruolo del docente';
$string['staffrolename_help'] = 'Etichetta per il ruolo che gestisce gli studenti. Questo non è necessariamente un "docente".';
$string['start'] = 'Inizio';
$string['startpast'] = 'Non è possibile avviare una fascia oraria con appuntamento vuoto passato';
$string['statistics'] = 'Statistiche';
$string['student'] = 'Studente';
$string['studentbreakdown'] = 'Per studente';
$string['studentcomments'] = 'Note Studente';
$string['studentdetails'] = 'Dettagli Studente';
$string['studentmultiselect'] = 'Ogni studente può essere selezionato solo una volta in questa fascia oraria';
$string['studentnotes'] = 'Note sugli appuntamenti';
$string['students'] = 'Studenti';
$string['sunday'] = 'Domenica';
$string['tab-otherappointments'] = 'Tutti gli appuntamenti di questo studente';
$string['tab-otherstudents'] = 'Studenti in questa fascia oraria';
$string['tab-thisappointment'] = 'Questo appuntamento';
$string['teacher'] = 'Docente';
$string['thursday'] = 'Giovedì';
$string['tuesday'] = 'Martedì';
$string['unattended'] = 'Appuntamenti nulli';
$string['unlimited'] = 'Illimitati';
$string['unregisteredlbl'] = 'Studente(i) che non ha prenotato';
$string['upcomingslots'] = 'Prossime fasce orarie';
$string['updategrades'] = 'Aggiorna valutazioni';
$string['updatesingleslot'] = '';
$string['wednesday'] = 'Mercoledì';
$string['what'] = 'Cosa?';
$string['whathappened'] = 'Cosa è successo?';
$string['whatresulted'] = 'Quali risultati?';
$string['when'] = 'Quando?';
$string['where'] = 'Dove?';
$string['who'] = 'Con chi?';
$string['whosthere'] = 'Chi c\'è ?';
$string['xdaysbefore'] = '{$a} giorni prima della fascia oraria';
$string['xweeksbefore'] = '{$a} settimane prima della fascia oraria';
$string['yourappointmentnote'] = 'Comments for your eyes';
$string['yourslotnotes'] = 'Commenti sull\'appuntamento';
