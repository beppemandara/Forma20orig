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
 * Strings for component 'enrol_stripepayment', language 'it', branch 'MOODLE_27_STABLE'
 *
 * @package   enrol_stripepayment
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['assignrole'] = 'Assegna ruolo';
$string['cost'] = 'Costo d\'iscrizione';
$string['costerror'] = 'Il costo d\'iscrizione non è un numero';
$string['costorkey'] = 'Seleziona uno dei seguenti metodi d\'iscrizione.';
$string['currency'] = 'Valuta';
$string['defaultrole'] = 'Ruolo assegnato per default';
$string['defaultrole_desc'] = 'Seleziona il ruolo che deve essere assegnato agli utenti che si iscrivono tramite e Stripe';
$string['enrolenddate'] = 'Data di fine';
$string['enrolenddate_help'] = 'Se attivata, gli utenti potranno iscriversi solo fino a questa data';
$string['enrolenddaterror'] = 'La data d\'iscrizione non può essere precedente alla data d\'inizio';
$string['enrolperiod'] = 'Durata dell\'iscrizione';
$string['enrolperiod_desc'] = 'Durata di default della validità dell\'iscrizione. Se impostata a zero la durata non avrà limite.';
$string['enrolperiod_help'] = 'Durata della validità dell\'iscrizione dal momento in cui un utente è iscritto. Se disabilitata, la durata dell\'iscrizione non avrà limite.';
$string['enrolstartdate'] = 'Data d\'inizio';
$string['enrolstartdate_help'] = 'Se abilitata l\'utente potrà iscriversi solo a partire da questa data.';
$string['expiredaction'] = 'Azione da intraprendere alla scadenza dell\'iscrizione';
$string['expiredaction_help'] = 'Selezionare l\'azione da intraprendere quando l\'iscrizione dell\'utente scade. Nota Bene i dati del utente e le sue impostazioni vengono eliminate dal corso durante la disiscrizione.';
$string['mailadmins'] = 'Notifica amministartore';
$string['mailstudents'] = 'Notifica gli studenti';
$string['mailteachers'] = 'Notifica gli insegnati';
$string['messageprovider:stripe_enrolment'] = 'Messaggi iscrizione Stripe';
$string['messageprovider:stripepayment_enrolment'] = 'Message Provider';
$string['nocost'] = 'In questo corso l\'iscrizione non ha un costo!';
$string['pluginname'] = 'Pagamento Stripe';
$string['pluginname_desc'] = 'Il modulo Stripe consente di impostare dei corsi a pagamento. Se il costo di un corso è pari 0, agli studenti non verrà richiesto il pagamento. Sono disponibili impostazioni globali per il sito che possono essere impostate come default e sono disponibili anche impostazioni per ogni ogni corso che possono essere configurate individualmente. Il costo del corso sovrascrive il costo del sito.';
$string['publishablekey'] = 'Chiave pubblica Stripe';
$string['publishablekey_desc'] = 'La chiave pubblica delle API dell\'account Stripe';
$string['secretkey'] = 'Chiave privata Stripe';
$string['secretkey_desc'] = 'La chiave provata delle API dell\'account Stripe';
$string['sendpaymentbutton'] = 'Invia il pagamento con Stripe';
$string['status'] = 'Consenti iscrizioni Stripe';
$string['status_desc'] = 'Consenti per default iscrizione Stripe';
$string['stripeaccepted'] = 'Il pagamento Stripe è stato accettato';
$string['stripe:config'] = 'Configurare istanza d\'iscrizione Stripe';
$string['stripe:manage'] = 'Gestire gli utenti iscritti';
$string['stripe:unenrol'] = 'Disiscrivere gli utenti dal corso';
$string['stripe:unenrolself'] = 'Disiscriversi dal corso';
$string['unenrolselfconfirm'] = 'Sei sicuro di disiscriverti dal corso "{$a}"?';
