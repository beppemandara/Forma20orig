<?php
// $Id$
require_once '../../../config.php';
require_once '../lib.php';
require_capability('block/f2_gestione_risorse:modifica_formatore', get_context_instance(CONTEXT_SYSTEM));

if (has_capability('block/f2_gestione_risorse:aggiungi_formatore', get_context_instance(CONTEXT_SYSTEM))
and has_capability('block/f2_gestione_risorse:vedi_lista_utenti', get_context_instance(CONTEXT_SYSTEM))
and has_capability('block/f2_gestione_risorse:vedi_lista_formatori', get_context_instance(CONTEXT_SYSTEM))
	)
{

	global $USER;
	if (!isSupervisore($USER->id) && !is_siteadmin()) die();
	$formatori_ids = $_POST['formatore_id'];

	if (!empty($formatori_ids))
	{
		foreach ($formatori_ids as $formatore_id)
		{
			$retval = delete_formatore($formatore_id);
			if (!$retval) echo '<div>'.get_string('error_in_delete','block_f2_gestione_risorse').' su formatore id = '.$formatore_id.'</div>';
		}
		redirect(new moodle_url('index.php'));
	}
}
else 
{
	die;
}
?>