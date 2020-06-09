<?php

// $Id: block_f2_gestione_risorse.php 962 2013-01-11 14:18:47Z c.arnolfo $

class block_f2_gestione_risorse extends block_list {
    function init() {
        $this->title = get_string('pluginname', 'block_f2_gestione_risorse');
    }

    function applicable_formats() {
        return array('site' => true, 'course' => false, 'my' => false);
    }

    function get_content() {
        global $OUTPUT, $CFG;

        if($this->content !== NULL) {
            return $this->content;
        }
/*
        if(!is_siteadmin($USER->id)) {
            return $this->content;
        }
*/

        $this->content = new stdclass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';
// context_system::instance()
		//if(has_capability('block/f2_gestione_risorse:aggiungi_formatore',get_context_instance(CONTEXT_SYSTEM))
		if(has_capability('block/f2_gestione_risorse:aggiungi_formatore', context_system::instance())
			and has_capability('block/f2_gestione_risorse:modifica_formatore', context_system::instance())
			and has_capability('block/f2_gestione_risorse:vedi_lista_formatori', context_system::instance())
			and has_capability('block/f2_gestione_risorse:vedi_lista_utenti', context_system::instance())
			)
		{
			$anagrafica_formatori_lbl = get_string('anagrafica_formatori', 'block_f2_gestione_risorse');
			$anagrafica_formatori_url = new moodle_url($CFG->wwwroot.'/blocks/f2_gestione_risorse/formatori/');
			$anagrafica_formatori_html = html_writer::link($anagrafica_formatori_url, $anagrafica_formatori_lbl);
			$this->content->items[] = $anagrafica_formatori_html; 
			$this->content->icons[] = $OUTPUT->pix_icon('i/show', $anagrafica_formatori_lbl);
		}
		
		//if(has_capability('block/f2_gestione_risorse:add_fornitori', get_context_instance(CONTEXT_SYSTEM)))
		if(has_capability('block/f2_gestione_risorse:add_fornitori', context_system::instance()))
		{
			$anagrafica_fornitori_lbl = get_string('anagrafica_fornitori', 'block_f2_gestione_risorse');
			$anagrafica_fornitori_url = new moodle_url($CFG->wwwroot.'/blocks/f2_gestione_risorse/fornitori/anagrafica_fornitori.php');
			$anagrafica_fornitori_html = html_writer::link($anagrafica_fornitori_url, $anagrafica_fornitori_lbl);
			$this->content->items[] = $anagrafica_fornitori_html;
			$this->content->icons[] = $OUTPUT->pix_icon('i/show', $anagrafica_fornitori_lbl);
		}
		
		//if(has_capability('block/f2_gestione_risorse:viewfunzionalita',get_context_instance(CONTEXT_SYSTEM))
		if(has_capability('block/f2_gestione_risorse:viewfunzionalita', context_system::instance())
			and has_capability('block/f2_gestione_risorse:editfunzionalita', context_system::instance())
			)
		{
			$gestionefunzionalita_lbl = get_string('funzionalita', 'block_f2_gestione_risorse');
			$gestionefunzionalita_url = new moodle_url($CFG->wwwroot.'/blocks/f2_gestione_risorse/funzionalita/funzionalita.php');
			$gestionefunzionalita_html = html_writer::link($gestionefunzionalita_url, $gestionefunzionalita_lbl);
			$this->content->items[] = $gestionefunzionalita_html; 
			$this->content->icons[] = $OUTPUT->pix_icon('i/show', $gestionefunzionalita_lbl);

		}
		
		//if(has_capability('block/f2_gestione_risorse:viewsessioni',get_context_instance(CONTEXT_SYSTEM))
		if(has_capability('block/f2_gestione_risorse:viewsessioni', context_system::instance())
			and has_capability('block/f2_gestione_risorse:editsessioni', context_system::instance())
			)
		{
			$gestionesessioni_lbl = get_string('sessioni', 'block_f2_gestione_risorse');
			$gestionesessioni_url = new moodle_url($CFG->wwwroot.'/blocks/f2_gestione_risorse/sessioni/sessioni.php');
			$gestionesessioni_html = html_writer::link($gestionesessioni_url, $gestionesessioni_lbl);
			$this->content->items[] = $gestionesessioni_html; 
			$this->content->icons[] = $OUTPUT->pix_icon('i/show', $gestionesessioni_lbl);

		}

		//if(has_capability('block/f2_gestione_risorse:budget_edit', get_context_instance(CONTEXT_SYSTEM)))
		if(has_capability('block/f2_gestione_risorse:budget_edit', context_system::instance()))
		{
			$inserisci_budget_lbl = get_string('inserisci_budget', 'block_f2_gestione_risorse');
			$inserisci_budget_url = new moodle_url($CFG->wwwroot.'/blocks/f2_gestione_risorse/budget/inserisci_budget.php');
			$inserisci_budget_html = html_writer::link($inserisci_budget_url, $inserisci_budget_lbl);
			$this->content->items[] = $inserisci_budget_html; 
			$this->content->icons[] = $OUTPUT->pix_icon('i/show', $inserisci_budget_lbl);
		}
		
		//if(has_capability('local/f2_notif:edit_notifiche', get_context_instance(CONTEXT_SYSTEM))){
		if(has_capability('local/f2_notif:edit_notifiche', context_system::instance())){
			$modelli_notifica_lbl = get_string('modelli_notifica', 'local_f2_notif');
			$modelli_notifica_url = new moodle_url($CFG->wwwroot.'/local/f2_notif/templates.php');
			$modelli_notifica_html = html_writer::link($modelli_notifica_url, $modelli_notifica_lbl);
			$this->content->items[] = $modelli_notifica_html;
			$this->content->icons[] = $OUTPUT->pix_icon('i/show', $modelli_notifica_lbl);
		}
		
		//if(has_capability('block/f2_gestione_risorse:send_auth_mail', get_context_instance(CONTEXT_SYSTEM))){
		if(has_capability('block/f2_gestione_risorse:send_auth_mail', context_system::instance())){
			$send_auth_mail_lbl = get_string('send_auth_mail', 'block_f2_gestione_risorse');
			$send_auth_mail_url = new moodle_url($CFG->wwwroot.'/blocks/f2_gestione_risorse/send_auth_mail/auth_mail.php');
			$send_auth_mail_html = html_writer::link($send_auth_mail_url, $send_auth_mail_lbl);
			$this->content->items[] = $send_auth_mail_html;
			$this->content->icons[] = $OUTPUT->pix_icon('i/show', $send_auth_mail_lbl);
		}

        return $this->content;
    }
}
