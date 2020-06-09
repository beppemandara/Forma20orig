<?php

//$Id: prenotazioni.php 173 2012-09-13 08:20:23Z g.nuzzolo $
global $CFG,$USER,$COURSE,$DB;

require_once '../../config.php';
require_once 'lib.php';

// print_r($USER);exit;

require_login();
$blockid = get_block_id(get_string('pluginname_db','block_f2_prenotazioni'));
if (has_capability('block/f2_prenotazioni:editprenotazioni', get_context_instance(CONTEXT_BLOCK, $blockid)) || has_capability('block/f2_prenotazioni:editmieprenotazioni', get_context_instance(CONTEXT_BLOCK, $blockid))) {

    $userid       = required_param('userid', PARAM_INT);

    $page     = optional_param('page', 0, PARAM_INT);
    $perpage  = optional_param('perpage', 10, PARAM_INT);
    $column   = optional_param('column', 'titolo', PARAM_TEXT);
    $sort     = optional_param('sort', 'ASC', PARAM_TEXT);
    $prenota_altri     = optional_param('pa', 0, PARAM_INT);

    $cancel_btn     = optional_param('cancelbutton', 'null', PARAM_TEXT);
    $submit_btn     = optional_param('submitbutton', 'null', PARAM_TEXT);
    $action     = required_param('funzione', PARAM_TEXT);

    $idcorso = required_param('id_corso', PARAM_INT);

    $victimid     = required_param('victim_id', PARAM_INT);

    if($userid==0) $userid=$USER->id;
    else if($userid!=0 && validate_own_dipendente($userid)) {
        if (has_capability('block/f2_prenotazioni:editprenotazioni', get_context_instance(CONTEXT_BLOCK, $blockid)) || has_capability('block/f2_prenotazioni:editmieprenotazioni', get_context_instance(CONTEXT_BLOCK, $blockid))) {
            $userid=$userid;
        }
    }
    else 
         die();
    
    $baseurl = new moodle_url('/blocks/f2_prenotazioni/manage_prenotazioni.php');

    $blockname = get_string('pluginname', 'block_f2_prenotazioni');

    if ($submit_btn == get_string('conferma', 'block_f2_prenotazioni')) 
    {
            $reopen_sett = 0;
            $full_dominio = get_user_organisation($userid);

            // salva dati su DB e poi redirect
            $updt = new stdClass;
    // 	(`anno`,`courseid`,`userid`,`orgid`,`data_prenotazione`,`priorita`,`validato`,`cf`,`sfid`,
    // `costo`,`durata`,`lstupd`,`usrname`,`sede`,`validato_dir`,`val_by`,`val_dt`)
            if ($action == get_string('prenota', 'block_f2_prenotazioni')) // aggiungi prenotazione
            {
            	
                    $sedeid     = required_param('sede_select', PARAM_TEXT);
                    
    // 		$usrname     = required_param('usrname', PARAM_TEXT);
                    $cf = required_param('cf', PARAM_TEXT);
                    $sf = required_param('sf', PARAM_TEXT);
                    $durata = required_param('durata', PARAM_TEXT);
                    $costo = required_param('costo', PARAM_TEXT);
                    $anno = required_param('anno', PARAM_INT);
                    $prid = intval(required_param('prid', PARAM_TEXT));

                    if (!is_null($full_dominio))
                    {
                            $domid = $full_dominio[0];
                    }
                    else 
                    {
                            $domid=-1;
                    }

    // 		if (!canManageDomain($domid)) die();

                    $updt->anno = $anno;

                    $updt->courseid = $idcorso;
                    $updt->userid = $victimid;

                    $updt->orgid = $domid;

                    $updt->data_prenotazione = time();

                    $updt->cf = $cf;
                    $updt->sfid = $sf;
                    $updt->costo = $costo;
                    $updt->durata = $durata;
                    $updt->lstupd = time();
                    $updt->usrname = $USER->username;
                    $updt->sede = $sedeid;

                    $updt->validato_sett = 0;
                    $updt->val_sett_by = null;
                    $updt->val_sett_dt = null;
                    $updt->validato_dir  = 0;
                    $updt->val_dir_by = null;
                    $updt->val_dir_dt = null;
                    $updt->isdeleted = 0;
                    $updt->id = $prid;
                    $inserted_id = insert_prenotazione($updt);
                    if (validazioni_aperte()) 
                            $reopen_sett++;
            }
            else if ($action == get_string('annulla', 'block_f2_prenotazioni')) // cancella prenotazione
            {
                    $updt->id = $victimid;

    // 		delete_prenotazione($updt);
                    $updt->lstupd = time();
                    $updt->usrname = $USER->username;
                    $updt->validato_sett = 0;
                    $updt->val_sett_by = null;
                    $updt->val_sett_dt = null;
                    $updt->validato_dir  = 0;
                    $updt->val_dir_by = null;
                    $updt->val_dir_dt = null;
                    $updt->isdeleted = 1;
                    annulla_prenotazione($updt);
                    if (validazioni_aperte()) 
                            $reopen_sett++;
            }
            $location_next = 'prenotazioni.php?sort='.$sort.'&column='.$column.'&page='.$page.'&perpage='.$perpage.'&userid='.$userid.'&pa='.$prenota_altri;
            if ($reopen_sett > 0)
            {
                    $msg = get_string('mod_pr_da_rivalidare', 'block_f2_prenotazioni');
                    $settore_id = $full_dominio[0];
                    if (is_dominio_closed($settore_id, 'sett') == true)
                    {
                            validazione_settore_reopen($settore_id); //riapre fase validazione settore dell'anno formativo in corso
                            echo '<html><head><SCRIPT TYPE="text/javascript">
                            function init(msg)
                            {
                                    var b = setTimeout("apripopup()", 50);
                            }
                            function apripopup()
                            {
                                    var a = alert(\''.$msg.'\');
                                    document.location.href = \''.$location_next.'\';
                            }
                            </SCRIPT></head>
                            <BODY onLoad="init()"></body></html>';
                    }
                    // else header('location: '.$location_next);
                    else redirect(new moodle_url($location_next));
            }
            // else header('location: '.$location_next);
            else redirect(new moodle_url($location_next));
    }
    else // redirect 
    {
            $location_next = 'prenotazioni.php?sort='.$sort.'&column='.$column.'&page='.$page.'&perpage='.$perpage.'&userid='.$userid.'&pa='.$prenota_altri;
            // header('location: '.$location_next);
            redirect(new moodle_url($location_next));
    // 	redirect(new moodle_url('prenotazioni.php?sort='.$sort.'&column='.$column.'&page='.$page.'&perpage='.$perpage.'&userid='.$userid.'&pa='.$prenota_altri));
    }
}
