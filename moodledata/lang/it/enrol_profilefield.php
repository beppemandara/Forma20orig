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
 * Strings for component 'enrol_profilefield', language 'it', branch 'MOODLE_24_STABLE'
 *
 * @package   enrol_profilefield
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['assignrole'] = 'Assegna ruolo';
$string['badprofile'] = 'Siamo spiacenti, le informazioni contenute nel tuo profilo non consentono l\'iscrizione al corso. Se ritieni di avere una buona ragione per iscriverti, contatta un amministratore che modificherà di conseguenza il tuo profilo.';
$string['course'] = 'Corso : $a';
$string['defaultnotification'] = 'Gentile <%%TEACHER%%>,

l\'utente <%%USERNAME%%> si è iscritto spontaneamente nel corso <%%COURSE%%>.

Puoi visualizzare il profilo dell\'utente <a href="<%%URL%%>">qui</a> dopo aver effettuato il login.';
$string['enrolenddate'] = 'Data di fine';
$string['enrolenddate_help'] = 'Gli utenti potranno iscriversi solo entro la data impostata.';
$string['enrolenddaterror'] = 'La data di fine delle iscrizioni non può essere antecedente la data di inizio';
$string['enrolme'] = 'Iscrivimi al corso';
$string['enrolmentconfirmation'] = 'Benvenuto! Le informazioni contenute nel tuo profilo consentono l\'iscrizione al corso. Vuoi procedere con l\'iscrizione?';
$string['enrolname'] = 'Iscrizione basata sui campi del profilo';
$string['enrolperiod'] = 'Durata dell\'iscrizione';
$string['enrolperiod_desc'] = 'La durata di default dell\'iscrizione (in secondi). Se impostata su zero, la durata dell\'iscrizione sarà illimitata di default.';
$string['enrolperiod_help'] = 'La durata di default dell\'iscrizione, a partire dalla data di iscrizione dell\'utente. Disabilitare l\'impostazione per una durata di default dell\'iscrizione senza limite.';
$string['enrol/profilefield:unenrolself'] = 'Consenti la disiscrizione dal corso';
$string['enrolstartdate'] = 'Data di inizio';
$string['enrolstartdate_help'] = 'Consente l\'iscrizione degli utenti solo a partire dalla data impostata.';
$string['grouppassword'] = 'Chiave di iscrizione al gruppo, se nota.';
$string['newcourseenrol'] = 'Nel corso {$a} si è iscritto un partecipante';
$string['nonexistantprofilefielderror'] = 'Il campo non è definito nei campi personalizzati del profilo utente';
$string['notificationtext'] = 'Modello di notifica';
$string['notificationtext_help'] = 'Il contenuto della mail può redatto utilizzando i segnaposto <%%USERNAME%%>, <%%COURSE%%>, <%%URL%%> and <%%TEACHER%%> . Nota: tutti gli span tag multilingua saranno elaborati sulla base della lingua del destinatario.';
$string['notifymanagers'] = 'Invia notifica ai manager';
$string['pluginname'] = 'Iscrizione basata sui campi del profilo';
$string['pluginname_desc'] = 'Il metodo consente l\'iscrizione diretta nei corsi se l\'utente ha un campo del profilo corrispondente al valore atteso.';
$string['profilefield'] = 'Campo del profilo utente';
$string['profilefield:config'] = 'Configurare metodo di iscrizione';
$string['profilefield_desc'] = 'Un puntatore ad un campo personalizzato del profilo';
$string['profilefield:enrol'] = 'Iscriversi utilizzando il metodo di Iscrizione basato sui campi del profilo';
$string['profilefield:manage'] = 'Gestire le iscrizioni basate sui campi del profilo';
$string['profilefield:unenrol'] = 'Disiscrivere i partecipanti iscritti in base aii campi del profilo';
$string['profilefield:unenrolself'] = 'Disiscriversi se iscritto in base aiicampi del profilo';
$string['profilevalue'] = 'Valore atteso';
$string['status'] = 'Consenti di usare il profilo per iscriversi';
$string['unenrolself'] = 'Vuoi disiscriverti dal corso "{$a}"?';
$string['unenrolselfconfirm'] = 'Sei sicuro di disiscriverti dal corso "{$a}"?';
