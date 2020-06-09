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
 * Strings for component 'allocationform', language 'it', branch 'MOODLE_27_STABLE'
 *
 * @package   allocationform
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['accessdenied'] = 'Accesso negato al modulo di assegnazione';
$string['add_option'] = 'Aggiungi un\'opzione';
$string['allocationform'] = 'Modulo di assegnazione';
$string['allocationform:addinstance'] = 'Aggiungi un modulo di assegnazione';
$string['allocationform:edit'] = 'Modifica moduli di assegnazione';
$string['allocationform_instructions'] = 'Devi ordinare le {$a->choices} opzioni in base alla preferenza, ti sarà assegnata la {$a->allocation} delle opzioni. {$a->extratext}';
$string['allocationformname'] = 'Nome del modulo di assegnazione';
$string['allocationformname_help'] = 'Il nome del modulo di assegnazione che sarà utilizzato come collegamento al modulo';
$string['allocationform_notwant'] = 'Puoi selezionare un\'opzione che non desideri ti sia assegnata.';
$string['allocationform:reallocate'] = 'Forzare una riassegnazione';
$string['allocationform:reopen'] = 'Consentire ad un utente la riapertura di un modulo di assegnazione';
$string['allocationform:viewallocations'] = 'Visualizzare tutte le assegnazioni';
$string['allocationform:viewform'] = 'Visualizzare moduli di assegnazione';
$string['allocations'] = 'Assegnazioni';
$string['allocations_not_visible'] = 'Attenzione: le assegnazioni sono visibili soltanto ai redattori finchè non verranno rese disponibili.';
$string['allocationviewed'] = 'Visualizzato modulo di assegnazione';
$string['al_notwant'] = 'Indesiderata';
$string['al_notwant_help'] = 'Seleziona un\'opzione che non desideri ti si assegnata.';
$string['availabilityheading'] = 'Disponibilità del modulo';
$string['back_to_edit'] = 'Vai in modalità modifica';
$string['back_to_edit_warning'] = 'Saranno eliminate tutte le informazioni inserite nel modulo.';
$string['before_start'] = 'La data di apertura di questo modulo di assegnazione non è ancora trascorsa.';
$string['choice'] = 'Scelta {$a->choice}';
$string['choiceheading'] = 'Impostazione opzioni';
$string['choice_help'] = 'Seleziona l\'opzione che desideri ti sia assegnata';
$string['cron'] = 'Elaborazione in background dei moduli di assegnazione';
$string['csvgenerated'] = 'CSV generato';
$string['deadline'] = 'Scadenza';
$string['deadline_help'] = 'L\'ultima data nella quale gli utenti potranno selezionare la propria assegnazione è anche la prima data nella quale un modulo di assegnazione sarà accodato per l\'elaborazione.';
$string['deadline_message'] = 'La data di scadenza per completare questo modulo è: {$a->deadline}';
$string['deadline_passed'] = 'La data di  scadenza di questo modulo è trascorsa, se lo attivi adesso sarà elaborato immediatamente.';
$string['defaultrole'] = 'Ruolo di default';
$string['defaultrole_help'] = 'Il ruolo predefinito da utilizzare per selezionare gli utenti che riceveranno le assegnazioni in base all\'attività';
$string['deny_access'] = 'Non sei autorizzato a visualizzare questo modulo di assegnazione.';
$string['disallow_list'] = 'Non consentire elenco';
$string['disallow_list_help'] = 'Gli utenti spuntati non potranno selezionare {$a->option_name}';
$string['duplicate_choice'] = 'Puoi selezionare l\'opzione una volta sola.';
$string['duplicateoption'] = 'Errore: non è stato possibile salvare le modifiche nel database. Probabilmente il problema è dovuto alla duplicazione del nome di un\'opzione.';
$string['form_allocation_error'] = 'ERRORE nel modulo id {$a->formid}:';
$string['form_allocation_error2'] = 'Errore: Impossibile assegnare lo userid {$a->userid}';
$string['generate_allocation_csv'] = 'Genera il CSV delle assegnazioni dell\'utente';
$string['generate_csv'] = 'Genera il CSV delle opzioni dell\'utente';
$string['invalidstate'] = 'E\' stato passato un cambio di stato non valido al modulo di assegnazione';
$string['make_active'] = 'Attiva il modulo';
$string['modulename'] = 'Modulo di assegnazione';
$string['modulename_help'] = 'Il modulo di assegnazione può essere usato per assegnare agli studenti da 1 a 10 opzioni (per esempio moduli, compiti, posti...)

Il docente definisce:

* L\'elenco delle opzioni disponibili per gli studenti.
* Il numero di studenti che possono essere assegnati a un\'opzione.
* le opzioni che gli studenti possono scegliere (da 1 a 10)
* Se gli studenti possono selezionare un\'opzione che non desiderano gli sia assegnata.
* la scadenza entero la quale gli studenti possono operare una scelta.

Attenzione: quando l\'attività passa dalla modalità attiva a quella di modifica, ogni scelta operata dagli studenti sarà eliminata.

Trascorsa la data di scadenza, il modulo cercherà di assegnare al meglio gli studenti in modo che:

* Il maggior numero di studenti si veda assegnata l\'opzione preferita
* Gli studenti siano assegnati alle opzioni indicate come \'Indesiderate\'.
* Gli studenti non ricevano le assegnazioni per le quali hanno ricevuto restrizioni di scelta.
* Gli studenti che non operano scelte ricevano assegnazioni per ultimi.

Dopo l\'elaborazione delle assegnazioni,  gli insegnanti potranno:

* Controllare il risultato e modificare il numero di studenti assegnati ad una opzione prima di pubblicare i risultati.
* Ottenere un file CSV delle scelte dello studente.
* Ottenere un file CSV delle assegnazioni.';
$string['modulenameplural'] = 'Moduli di assegnazione';
$string['noallocationforms'] = 'Non ci sono moduli di assegnazione in questo corso';
$string['no_allocations'] = 'Non ci sono assegnazioni';
$string['not_active'] = 'Il modulo di assegnazioni non è disponibile, prova più tardi';
$string['not_enough_slots'] = 'Non sono disponibili posti a sufficienza per tutti coloro che hanno i requisiti per l\'assegnazione';
$string['notrequired'] = 'Non richiesto';
$string['notsaved'] = 'Non salvato, non hai il ruolo richiesto.';
$string['not_set'] = 'Devi effettuare una scelta';
$string['notwant'] = 'Includi indesiderate';
$string['notwant_help'] = 'Se selezionato, un utente potrà specificare un\'opzione cui non desidera essere assegnato';
$string['numberofallocations'] = 'Assegnazioni per utente';
$string['numberofallocations_help'] = 'Numero di opzioni assegnabili a un utente';
$string['numberofchoices'] = 'Numero di scelte';
$string['numberofchoices_help'] = 'Il numero di opzioni che un utente deve scegliere';
$string['ok_to_continue'] = 'Sei sicuro di continuare?';
$string['optionedited'] = 'Opzione modificata';
$string['option_form_header'] = 'Nuova opzione';
$string['option_heading'] = 'Titolo';
$string['option_heading_help'] = 'Titolo da visualizzare subito sopra questa opzione';
$string['option_list'] = 'Elenco delle opzioni';
$string['option_maxallocation'] = 'Numero massimo di assegnazioni';
$string['option_maxallocation_help'] = 'Numero massimo di persone che possono essere assegnate a questa opzione.';
$string['option_maxallocation_invalid'] = 'Numero massimo di assegnazioni non impostato';
$string['option_name'] = 'Nome dell\'opzione';
$string['option_name_help'] = 'Il nome dell\'opzione visualizzato all\'utente.';
$string['option_restricted'] = 'Utenti limitati';
$string['overviewclose'] = 'Giorno di chiusura: {$a->closedate}';
$string['overviewname'] = 'Assegnazione: {$a->link}';
$string['overviewready'] = 'L\'assegnazione è disponibile.';
$string['overviewreview'] = 'Pronto per la revisione. Gli assegnatari non possono vedere i risultati.';
$string['people'] = 'Il Numero di persone da assegnare';
$string['permissionedited'] = 'Autorizzazione modificata';
$string['pluginadministration'] = 'Gestione modulo di assegnazione';
$string['pluginname'] = 'Moduli di assegnazione (eForms)';
$string['queued_for_processing'] = 'Il modulo di assegnazione sta per essere elaborato... riprova più tardi.';
$string['reprocess'] = 'Forza la riassegnazione';
$string['reprocess_warning'] = 'Tutte le assegnazioni esistenti saranno eliminate';
$string['requirechoice'] = 'Scelta obbligatoria';
$string['restrict'] = 'Limita';
$string['roleselect'] = 'Ruolo da assegnare';
$string['roleselect_help'] = 'Seleziona il ruolo degli utenti di questo corso che saranno assegnati tramite questo modulo';
$string['saved'] = 'Opzione salvata, puoi tornare al corso';
$string['show_allocations'] = 'Rendi disponibili le assegnazioni';
$string['show_course'] = 'Torna a {$a->coursename}';
$string['slots'] = 'Numero di posti per assegnazione';
$string['startdate'] = 'Data di inizio';
$string['startdate_help'] = 'Tempo di decorrenza dal quale l\'utente può selezionare le proprie assegnazioni';
$string['statechanged'] = 'Stato modificato';
$string['trackcompletion'] = 'Invio obbligatorio della scelta per considerare completata l\'attività';
$string['unallocated'] = 'Non assegnato';
