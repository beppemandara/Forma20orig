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
 * Strings for component 'bigbluebuttonbn', language 'it', branch 'MOODLE_27_STABLE'
 *
 * @package   bigbluebuttonbn
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['bbbdurationwarning'] = 'La durata massima della sessione è %duration% minuti.';
$string['bbbrecordwarning'] = 'La sessione può essere registrata.';
$string['bigbluebuttonbn'] = 'BigBlueButton';
$string['bigbluebuttonbn:addinstance'] = 'Aggiungere meeting';
$string['bigbluebuttonbn:join'] = 'Partecipare al meeting';
$string['bigbluebuttonbn:managerecordings'] = 'Gestire registrazioni';
$string['bigbluebuttonbn:moderate'] = 'Moderare meeting';
$string['config_feature_preuploadpresentation'] = 'Configurazione del caricamento anticipato presentazione';
$string['config_feature_preuploadpresentation_description'] = 'L\'impostazione abilita o disabilita l\'interfaccia corrispondente e definisce iil valore di default per queste opzioni. La funzionalità sarà disponibile solo se il server Moodle è accessibile da BigBlueButton';
$string['config_feature_preuploadpresentation_enabled'] = 'Abilita caricamento anticipato presentazione';
$string['config_feature_preuploadpresentation_enabled_description'] = 'Il caricamento anticipato delle presentazione sarà disponibile nell\'interfaccia quando si aggiunge o aggiorna una stanza o una conferenza';
$string['config_feature_recording'] = 'Configurazione della della funzionalità "Registrazione"';
$string['config_feature_recording_default'] = 'La funzionalità "Registrazione" è abilitata per default';
$string['config_feature_recording_default_description'] = 'La sessione BigBlueButton potrà essere registrata';
$string['config_feature_recording_editable'] = 'La funzionalità "Registrazione" può essere modificata';
$string['config_feature_recording_editable_description'] = 'L\'interfaccia visualizzerà la possibilità di abilitare o disabilitare la registrazione della sessione.';
$string['config_feature_userlimit'] = 'Configurazione della funzionalità "Limite utenti"';
$string['config_feature_userlimit_default'] = 'La funzionalità "Limite utenti" è abilitata per default';
$string['config_feature_userlimit_default_description'] = 'Il numero di utenti di default che possono partecipare ad un meeting. Impostazndo il valore a 0 non ci saranno limiti.';
$string['config_feature_userlimit_editable'] = 'La funzionalità "Limite utenti" può essere modificata';
$string['config_feature_userlimit_editable_description'] = 'Il limite utenti può essere modificato quando si crea una stanza.';
$string['config_feature_voicebridge'] = 'Configurazione della funzionalità "Bridge voce"';
$string['config_feature_voicebridge_editable'] = 'La funzionalità "Bridge voce" può essere modificata';
$string['config_feature_voicebridge_editable_description'] = 'E\' possibile assegnare permanentemente un numero bridge voce ad una stanza. Una volta assegnato, il numero non potrà essere utilizzato per alter stanze.';
$string['config_feature_waitformoderator'] = 'Configurazione della funzionalità "Attesa del moderatore"';
$string['config_feature_waitformoderator_cache_ttl'] = 'Cache TTL dell\'attesa del moderatore';
$string['config_feature_waitformoderator_cache_ttl_description'] = 'In presenza di molti client viene utilizzata una cache per alleggerire il carico della verifica dell\'attesa del moderatore. E\' possibile impostare il tempo di permanenza in cache prima di inviare di nuovo la richiesta al server BigBlueButton.';
$string['config_feature_waitformoderator_default'] = 'Abilita per default l\'attesa del moderatore';
$string['config_feature_waitformoderator_default_description'] = 'La funzionalità sarà attiva per default quando si creano nuove stanze.';
$string['config_feature_waitformoderator_editable'] = 'L\'attesa del moderatore può essere modificata';
$string['config_feature_waitformoderator_editable_description'] = 'Consente di modificare l\'impostazione dell\'attesa del moderatore quando si creano nuove stanze.';
$string['config_feature_waitformoderator_ping_interval'] = 'Tempo di verifica dell\'attesa (in secondi)';
$string['config_feature_waitformoderator_ping_interval_description'] = 'Durante l\'attesa del moderatore il client verificherà lo stato della sessione con la frequenza in secondi impostata. le richieste verranno effettuate a Moodle.';
$string['config_general'] = 'Configurazione generale';
$string['config_general_description'] = 'Queste impostazione verranno <b>sempre</b> utilizzate';
$string['config_permission'] = 'Configurazione autorizzazioni';
$string['config_permission_description'] = 'Consente di definire le autorizzazioni di default quando si creano nuove stanze.';
$string['config_permission_moderator_default'] = 'Moderatori per default';
$string['config_permission_moderator_default_description'] = 'La regola viene utilizzata per default quando si creano nuove stanze.';
$string['config_scheduled_duration_enabled'] = 'Abilita calcolo della durata';
$string['config_scheduled_duration_enabled_description'] = 'La durata della sessione sarà calcolata in base all\'orario di apertura e chiusura';
$string['config_server_url'] = 'URL del server BigBlueButton';
$string['config_server_url_description'] = 'L\'URL del server BigBlueButton deve terminare con /bigbluebutton/. (L\'URL di default è un server BigBlueButton messo a disposizione da Blindside Networks a scopi di test).';
$string['config_shared_secret'] = 'Shared Secret BigBlueButton';
$string['config_shared_secret_description'] = 'Il salt di sicurezza del server BigBlueButton. (Il salti di default è per un server BigBlueButton messo a disposizione da Blindside Networks a scopi di test).';
$string['config_warning_curl_not_installed'] = 'La funzionalità richiede l\'installazione e configurazione dell\'estensione php cURL. Sarà possibile accedere all\'impostazione solo in presenza dell\'estensione.';
$string['email_body_notification_meeting_by'] = 'da';
$string['email_body_notification_meeting_description'] = 'Descrizione';
$string['email_body_notification_meeting_details'] = 'Dettagli';
$string['email_body_notification_meeting_end_date'] = 'Data di fine';
$string['email_body_notification_meeting_has_been'] = 'è stato';
$string['email_body_notification_meeting_start_date'] = 'Data di inizio';
$string['email_body_notification_meeting_title'] = 'Titolo';
$string['email_body_recording_ready_for'] = 'La registrazione di';
$string['email_body_recording_ready_is_ready'] = 'è pronto';
$string['email_footer_sent_by'] = 'Questa notifica automatica è stata inviata da';
$string['email_footer_sent_from'] = 'dal corso';
$string['email_title_notification_has_been'] = 'è stato';
$string['ends_at'] = 'Fine';
$string['event_activity_created'] = 'Creata attività BigBlueButtonBN';
$string['event_activity_deleted'] = 'Eliminata attività BigBlueButtonBN';
$string['event_activity_modified'] = 'Modificata attività BigBlueButtonBN';
$string['event_activity_viewed'] = 'Visualizzata attività BigBlueButtonBN';
$string['event_activity_viewed_all'] = 'Visualizzata gestione attività BigBlueButtonBN';
$string['event_meeting_created'] = 'Creato meeting BigBlueButtonBN';
$string['event_meeting_ended'] = 'Terminato forzatamente meeting BigBlueButtonBN';
$string['event_meeting_joined'] = 'Acceduto meeting BigBlueButtonBN';
$string['event_meeting_left'] = 'Lasciato meeting BigBlueButtonBN';
$string['event_recording_deleted'] = 'Eliminata registrazione';
$string['event_recording_imported'] = 'Importata registarzione';
$string['event_recording_published'] = 'Pubblicata registrazione';
$string['event_recording_unpublished'] = 'Rimossa pubblicazione registrazione';
$string['general_error_unable_connect'] = 'Non è possibile stabilire il collegamento. Verificare l\'URL del server BigBlueButton e controllare che il server BigBlueButton sia in linea.';
$string['index_confirm_end'] = 'Vuoi terminare il meeting?';
$string['index_disabled'] = 'disabilitato';
$string['index_enabled'] = 'abilitato';
$string['index_ending'] = 'Chiusura della classe virtuale ..., attendere per favore.';
$string['index_error_checksum'] = 'Si è verificato un errore di checksum. Accertarsi che sia stato inserito il salt corretto.';
$string['index_error_forciblyended'] = 'Non è possibile partecipare al meeting perché è stato terminato manualmente.';
$string['index_heading'] = 'Stanze BigBlueButton';
$string['index_heading_actions'] = 'Azioni';
$string['index_heading_group'] = 'Gruppo';
$string['index_heading_moderator'] = 'Moderatori';
$string['index_heading_name'] = 'Stanza';
$string['index_heading_recording'] = 'Registrazione';
$string['index_heading_users'] = 'Utenti';
$string['index_heading_viewer'] = 'Visualizzatori';
$string['mod_form_block_general'] = 'Impostazioni generali';
$string['mod_form_block_participants'] = 'Partecipanti';
$string['mod_form_field_conference_name'] = 'Nome della coferenza';
$string['mod_form_field_duration'] = 'Durata';
$string['mod_form_field_intro'] = 'Descrizione';
$string['mod_form_field_intro_help'] = 'La descrizione della stanza o della conferenza';
$string['mod_form_field_name'] = 'Nome della stanza';
$string['mod_form_field_notification'] = 'Invia notifiche';
$string['mod_form_field_notification_created_help'] = 'Consente di inviare notifiche agli utenti iscritti per informarli della creazione dell\'attività.';
$string['mod_form_field_notification_help'] = 'Consente di inviare notifiche agli utenti iscritti per informarli della creazione o modifica dell\'attività.';
$string['mod_form_field_notification_modified_help'] = 'Consente di inviare notifiche a gli utenti iscritti per informarli della modifica dell\'attività.';
$string['mod_form_field_notification_msg_created'] = 'creato';
$string['mod_form_field_notification_msg_modified'] = 'modificato';
$string['mod_form_field_participant_add'] = 'Aggiungi partecipante';
$string['mod_form_field_participant_bbb_role_moderator'] = 'Moderatore';
$string['mod_form_field_participant_bbb_role_viewer'] = 'Visualizzatore';
$string['mod_form_field_participant_list'] = 'Elenco partecipanti';
$string['mod_form_field_participant_list_action_add'] = 'Aggiungi';
$string['mod_form_field_participant_list_action_remove'] = 'Elimina';
$string['mod_form_field_participant_list_text_as'] = 'come';
$string['mod_form_field_participant_list_type_all'] = 'Tutti gli utenti iscritti';
$string['mod_form_field_participant_list_type_owner'] = 'Titolare';
$string['mod_form_field_participant_list_type_role'] = 'Ruolo';
$string['mod_form_field_participant_list_type_user'] = 'Utente';
$string['mod_form_field_participant_role_unknown'] = 'Sconosciuto';
$string['mod_form_field_predefinedprofile'] = 'Profilo predefinito';
$string['mod_form_field_predefinedprofile_help'] = 'Profilo predefinito';
$string['mod_form_field_record'] = 'La sessione può essere registrata';
$string['mod_form_field_room_name'] = 'Nome della stanza';
$string['mod_form_field_userlimit'] = 'Limite utenti';
$string['mod_form_field_userlimit_help'] = 'Il numero massimo di utenti presenti nel meeting. Impostando il limite a 0 non ci saranno limiti.';
$string['mod_form_field_voicebridge'] = 'Bridge voce [####]';
$string['mod_form_field_voicebridge_format_error'] = 'Errore nel formato. Devi inserire un numero tra 1 a 9999.';
$string['mod_form_field_wait'] = 'In attesa del moderatore';
$string['mod_form_field_wait_help'] = 'I partecipanti prima di entrare nel meeting dovranno attendere l\'arrivo del modertore';
$string['mod_form_field_welcome'] = 'Messaggio di benvenuto';
$string['modulename'] = 'BigBlueButtonBN';
$string['modulenameplural'] = 'BigBlueButtonBN';
$string['pluginadministration'] = 'Gestione BigBlueButton';
$string['pluginname'] = 'BigBlueButtonBN';
$string['predefined_profile_classroom'] = 'Aula';
$string['predefined_profile_collaborationroom'] = 'Sala meeting';
$string['predefined_profile_conferenceroom'] = 'Sala conferenze';
$string['predefined_profile_default'] = 'Default';
$string['predefined_profile_scheduledsession'] = 'Sessioni programmate';
$string['serverhost'] = 'Nome del server';
$string['started_at'] = 'Iniziata';
$string['starts_at'] = 'Inizia';
$string['view_conference_action_end'] = 'Fine sessione';
$string['view_conference_action_join'] = 'Unisciti alla sessione';
$string['view_groups_selection'] = 'Seleziona il gruppo al quale vuoi unirti e conferma la scelta';
$string['view_groups_selection_join'] = 'Unisciti';
$string['view_groups_selection_warning'] = 'E\' disponibile una stanza per ogni gruppo. Se hai accesso a più di una stanza, accertati di sezionare la stanza giusta.';
$string['view_login_moderator'] = 'Accesso come moderatore ...';
$string['view_login_viewer'] = 'Accesso come partecipante ...';
$string['view_message_conference_has_ended'] = 'La conferenza è terminata.';
$string['view_message_conference_in_progress'] = 'La conferenza è in svolgimento.';
$string['view_message_conference_not_started'] = 'La conferenza non è iniziata.';
$string['view_message_conference_room_ready'] = 'La stanza è pronta, puoi accedere alla sessione';
$string['view_message_conference_wait_for_moderator'] = 'In attesa del moderatore';
$string['view_message_finished'] = 'L\'attività è conclusa.';
$string['view_message_has_joined'] = 'ha acceduto';
$string['view_message_have_joined'] = 'hanno acceduto';
$string['view_message_hour'] = 'ora';
$string['view_message_hours'] = 'ore';
$string['view_message_minute'] = 'minuto';
$string['view_message_minutes'] = 'minuti';
$string['view_message_moderator'] = 'moderatore';
$string['view_message_moderators'] = 'moderatori';
$string['view_message_norecordings'] = 'Non sono presenti registrazioni di questo meeting';
$string['view_message_notavailableyet'] = 'La sessione non è ancora disponibile.';
$string['view_message_room_closed'] = 'La stanza è chiusa.';
$string['view_message_room_open'] = 'La stanza è aperta.';
$string['view_message_room_ready'] = 'La stanza è pronta.';
$string['view_message_session_has_user'] = 'E\' presente';
$string['view_message_session_has_users'] = 'Sono presenti';
$string['view_message_session_no_users'] = 'Non sono presenti utenti';
$string['view_message_session_running_for'] = 'La sessione dura da';
$string['view_message_session_started_at'] = 'La sessione è iniziata alle';
$string['view_message_tab_close'] = 'Questa scheda/finestra deve essere chiusa manualmente';
$string['view_message_user'] = 'utente';
$string['view_message_users'] = 'utenti';
$string['view_message_viewer'] = 'partecipante';
$string['view_message_viewers'] = 'partecipanti';
$string['view_noguests'] = 'BigBlueButtonBN non è accessibile dagli ospiti';
$string['view_nojoin'] = 'Non hai un ruolo che ti consente di accedere alla sessione';
$string['view_recording'] = 'registrazione';
$string['view_recording_actionbar'] = 'Barra degli strumenti';
$string['view_recording_activity'] = 'Attività';
$string['view_recording_button_return'] = 'Indietro';
$string['view_recording_course'] = 'Corso';
$string['view_recording_date'] = 'Data';
$string['view_recording_delete_confirmation'] = 'Sei sicuro di eliminare {$a}?';
$string['view_recording_description'] = 'Descrizione';
$string['view_recording_duration'] = 'Durata';
$string['view_recording_duration_min'] = 'min';
$string['view_recording_format_presentation'] = 'presentazione';
$string['view_recording_format_video'] = 'video';
$string['view_recording_length'] = 'Lunghezza';
$string['view_recording_list_actionbar'] = 'Barra degli strumenti';
$string['view_recording_list_actionbar_delete'] = 'Elimina';
$string['view_recording_list_actionbar_deleting'] = 'Eliminazione in corso';
$string['view_recording_list_actionbar_hide'] = 'Nascondi';
$string['view_recording_list_actionbar_processing'] = 'Elaborazione in corso';
$string['view_recording_list_actionbar_publish'] = 'Pubblica';
$string['view_recording_list_actionbar_publishing'] = 'Pubblicazione in corso';
$string['view_recording_list_actionbar_show'] = 'Visualizza';
$string['view_recording_list_actionbar_unpublish'] = 'Rimuovi pubblicazione';
$string['view_recording_list_actionbar_unpublishing'] = 'Rimozione pubblicazione in corso';
$string['view_recording_list_activity'] = 'Attività';
$string['view_recording_list_course'] = 'Corso';
$string['view_recording_list_date'] = 'Data';
$string['view_recording_list_description'] = 'Descrizione';
$string['view_recording_list_duration'] = 'Durata';
$string['view_recording_list_recording'] = 'Registrazione';
$string['view_recording_modal_button'] = 'Applica';
$string['view_recording_name'] = 'Nome';
$string['view_recording_recording'] = 'Registrazione';
$string['view_recording_tags'] = 'Tag';
$string['view_recording_unpublish_confirmation'] = 'Sei sicuro di rimuovere dalla pubblicazione {$a}?';
$string['view_section_title_presentation'] = 'File della presentazione';
$string['view_section_title_recordings'] = 'Registrazioni';
