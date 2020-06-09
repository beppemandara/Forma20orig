<?php
/*
 * $Id: lib.php 1227 2013-12-05 12:25:02Z l.moretto $
 */
require_once "$CFG->dirroot/f2_lib/management.php";

/**
 * 
 * @global type $DB
 * @param type $newdomassignment
 * @return type
 * @throws Exception
 */
function set_user_domains($newdomassignment) {
    global $DB;
    $status = FALSE;
    if (!empty($newdomassignment) && isset($newdomassignment->userid) && !empty($newdomassignment->userid)) {
        $userid = $newdomassignment->userid;
        $olddomassignment = $DB->get_record('org_assignment', array('userid' => $userid));
//print_r($olddomassignment);
//print_r($newdomassignment);
        //esiste una precedente assegnazione
        $viewableorgidchanges = ($olddomassignment->viewableorganisationid != $newdomassignment->viewableorganisationid);
        $orgidchanges = ($olddomassignment->organisationid != $newdomassignment->organisationid);
//var_dump($viewableorgidchanges);var_dump($orgidchanges);
        if (!empty($olddomassignment)) {
            if ($orgidchanges || $viewableorgidchanges) {
                $transaction = $DB->start_delegated_transaction();
                try {
                    //rimuove ruolo ref. per la precedente assegnazione
                    if (isDirezione($olddomassignment->viewableorganisationid) && $viewableorgidchanges) {
                        unset_referent_role_to_user($userid, $olddomassignment->viewableorganisationid, 'D');
                    }
                    elseif (isScuola($olddomassignment->organisationid) && $orgidchanges) {
                        unset_referent_role_to_user($userid, $olddomassignment->organisationid, 'S');
                    }
                    if (isDirezione($newdomassignment->viewableorganisationid) && $viewableorgidchanges) {
                        set_referent_role_to_user($userid, $newdomassignment->viewableorganisationid, 'D');
                    }
                    elseif (isScuola($newdomassignment->organisationid) && $orgidchanges) {
                        set_referent_role_to_user($userid, $newdomassignment->organisationid, 'S');
                    }
                    $newdomassignment->id = $olddomassignment->id;
                    $status = $DB->update_record('org_assignment', $newdomassignment);
                } catch (Exception $e) {
                    $transaction->rollback($e);
                    throw $e;
                }
                $transaction->allow_commit();
            }
        } //nuova assegnazione
        else {
            $transaction = $DB->start_delegated_transaction();
            try {
                if (isDirezione($newdomassignment->viewableorganisationid)) {
                    set_referent_role_to_user($userid, $newdomassignment->viewableorganisationid, 'D');
                }
                elseif (isScuola($newdomassignment->organisationid)) {
                    set_referent_role_to_user($userid, $newdomassignment->organisationid, 'S');
                }
                $status = $DB->insert_record('org_assignment', $newdomassignment);
            } catch (Exception $e) {
                $transaction->rollback($e);
                throw $e;
            }
            $transaction->allow_commit();
        }
    }
    return $status;
}

/**
 * 
 * @global type $SITE
 * @param type $userid
 * @param type $orgid
 * @param type $type
 * @throws moodle_exception
 * @throws Exception
 */
function set_referent_role_to_user($userid, $orgid, $type) {
    global $SITE;
    $returnurl = new moodle_url('local/f2_domains/assignments/view.php', array('userid'=>$userid));
    switch ($type) {
        case 'D'://ref. di direzione
            $param = get_parametro('p_f2_id_ruolo_referente_formativo');
            $roleid = $param->val_int;
            //assegna il ruolo Ref. formativo all’utente stesso per:
            //  i corsi obiettivo (per l’anno formativo corrente) condivisi con la direzione,
            $courses = get_courses_by_org($orgid, array(C_OBB), get_anno_formativo_corrente());
//var_dump($courses);
            foreach ($courses as $courseid=>$course) {
                $context = context_course::instance($courseid);
                if(!role_assign($roleid, $userid, $context->id))
                    throw new moodle_exception('error:courseroleassign', 
                        $returnurl,
                        'local_f2_domains', 
                        (object)array('userid'=>$userid,'roleid'=>$roleid,'courseid'=>$courseid));
            }
            //  la categoria Corsi attivi quantificabili,
            $param = get_parametro('p_f2_categoria_corsi_attivi_quantificabili');
            $catid = $param->val_int;
            $context = context_coursecat::instance($catid);
            if(!role_assign($roleid, $userid, $context->id))
                    throw new moodle_exception('error:courseroleassign', 
                        $returnurl,
                        'local_f2_domains', 
                        (object)array('userid'=>$userid,'roleid'=>$roleid,'categoryid'=>$catid));
            // la pag. home (quindi i blocchetti di HP ereditano i ruoli)
            $context = context_course::instance($SITE->id);                    
            if(!role_assign($roleid, $userid, $context->id))
                    throw new moodle_exception('error:courseroleassign', 
                        $returnurl,
                        'local_f2_domains', 
                        (object)array('userid'=>$userid,'roleid'=>$roleid,'courseid'=>$courseid));
            //  i blocchetti di pertinenza dei referenti direzione: Apprendimento, Prenotazioni, Report
            /*$param = get_parametro('p_f2_ref_formativo_blocchi');
            $blocks = explode(',', $param->val_char);
            foreach($blocks as $blockname) {
                $instance = get_block_id($blockname);
                if($instance)
                    $context = context_block::instance($instance);
                else
                    throw new Exception("role_assign paic error: cannot find block $blockname instance");
                if(!role_assign($roleid, $userid, $context))
                    throw new Exception("role_assign failure for: roleid=$roleid, userid=$userid, block=$blockname");
            }*/
            break;
        
        case 'S'://ref. scuola
            $param = get_parametro('p_f2_id_ruolo_referente_scuola');
            $roleid = $param->val_int;
            //assegna il ruolo Ref. scuola all’utente stesso per:
            //  i corsi obiettivo e progr. di pertinenza della scuola (per l’anno formativo corrente)
            $courses = get_courses_by_org($orgid, array(C_OBB, C_PRO), get_anno_formativo_corrente());
            foreach ($courses as $courseid=>$course) {
                $context = context_course::instance($courseid);
                if(!role_assign($roleid, $userid, $context->id))
                    throw new moodle_exception('error:courseroleassign', 
                        $returnurl,
                        'local_f2_domains', 
                        (object)array('userid'=>$userid,'roleid'=>$roleid,'courseid'=>$courseid));
            }
            // la pag. home (quindi i blocchetti di HP ereditano i ruoli)
            $context = context_course::instance($SITE->id);
            if(!role_assign($roleid, $userid, $context->id))
                throw new moodle_exception('error:courseroleassign', 
                        $returnurl,
                        'local_f2_domains', 
                        (object)array('userid'=>$userid,'roleid'=>$roleid,'courseid'=>$courseid));
            //  i blocchetti di pertinenza dei referenti direzione: Apprendimento
            break;
    }
}

/**
 * 
 * @global type $SITE
 * @param type $userid
 * @param type $orgid
 * @param type $type
 */
function unset_referent_role_to_user($userid, $orgid, $type) {
    global $SITE;
    
    switch ($type) {
        case 'D'://ref. di direzione
            $param = get_parametro('p_f2_id_ruolo_referente_formativo');
            $roleid = $param->val_int;
            //rimuove il ruolo Ref. formativo all’utente stesso per:
            //  tutti i corsi (compresa la homepage) cui l'utente ha il ruolo assegnato
            $courses = get_user_courses_by_role($userid, $roleid);
            //  i corsi obiettivo (per l’anno formativo corrente) condivisi con la direzione,
//            $courses = get_courses_by_org($orgid, array(C_OBB));
            foreach ($courses as $courseid=>$course) {
                $context = context_course::instance($courseid);                    
                role_unassign($roleid, $userid, $context->id);
            }
            
            //  la categoria Corsi attivi quantificabili,
            $param = get_parametro('p_f2_categoria_corsi_attivi_quantificabili');
            $catid = $param->val_int;
            $context = context_coursecat::instance($catid);
            role_unassign($roleid, $userid, $context->id);
            
            // la pag. home (quindi i blocchetti di HP ereditano i ruoli)
//            $context = context_course::instance($SITE->id);                    
//            role_unassign($roleid, $userid, $context->id);
//            break;
        
        case 'S'://ref. scuola
            $param = get_parametro('p_f2_id_ruolo_referente_scuola');
            $roleid = $param->val_int;
            //rimuove il ruolo Ref. scuola all’utente per:
            //  tutti i corsi (compresa la homepage) cui l'utente ha il ruolo assegnato
            $courses = get_user_courses_by_role($userid, $roleid);
            //  i corsi obiettivo e progr. di pertinenza della scuola (per l’anno formativo corrente)
//            $courses = get_courses_by_org($orgid, array(C_OBB, C_PRO));
            foreach ($courses as $courseid=>$course) {
                $context = context_course::instance($courseid);
                role_unassign($roleid, $userid, $context->id);
            }
            
            // la pag. home (quindi i blocchetti di HP ereditano i ruoli)
//            $context = context_course::instance($SITE->id);
//            role_unassign($roleid, $userid, $context->id);
            break;
    }
}
