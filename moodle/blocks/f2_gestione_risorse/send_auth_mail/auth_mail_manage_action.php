<?php
require_once '../../../config.php';
// require_once '../lib.php';
require_once 'auth_mail_form.php';

$context = get_context_instance(CONTEXT_SYSTEM);
require_login();
require_capability('block/f2_gestione_risorse:send_auth_mail', $context);

$action = required_param('action', PARAM_TEXT);
$edizioni_arr = optional_param_array('edizione_id',array(), PARAM_INT);

// print_r($_POST);
// echo '<br/>';
// print_r($action);
// echo '<br/>';

class error_form_auth_mail extends moodleform 
{
	public function definition() 
	{
		global $CFG;
		$mform2 		=& $this->_form;
		$post_values = $this->_customdata['post_values_errors'];
		$post_values = json_encode($post_values);
// 		print_r($post_values);
		$mform2->addElement('hidden', 'post_values_errors',$post_values);
// 		$mform2->addElement('submit', 'send','aaa');
// 		$mform2->_process_submission('post');
	}
}

if ($action == get_string('auth_mail_salva', 'block_f2_gestione_risorse'))
{
	if (count($edizioni_arr) > 0)
	{
		$errors = array();
		foreach ($edizioni_arr as $ed)
		{
			$newsirp = intval($_POST['sirpedid_'.$ed]);
			$newsirpdata = $_POST['sirpdataedid_'.$ed];
			
// 			print_r($_POST);
		
			if (is_null($newsirp) or empty($newsirp))
			{
				// 			$errors['err_sirpedid_'.$ed] = 'err_sirpedid_'.$ed;
// 				$errors[$ed] = 'err_sirpedid_'.$ed;
			}
			else if (!is_int($newsirp) or ($newsirp.'' !== $_POST['sirpedid_'.$ed]))
			{
				$errors[$ed] = 'err_sirpedid_'.$ed;
			}
			else 
			{
				$updt = new stdClass;
				$updt->edizione_id = $ed;
				$updt->sirp = $newsirp;
// 				$updt->sirpdata = '';
				update_sirp($updt);
			}
			
			if (is_null($newsirpdata) or empty($newsirpdata))
			{
// 				$errors[$ed] = 'err_sirpdataedid_'.$ed;
			}
			else
			{
				$newsirpdata_arr = explode('/',$newsirpdata);
				// 			print_r(intval($newsirpdata_arr[0]));
				if (intval($newsirpdata_arr[1]) < 10) $newsirpdata_month = '0'.intval($newsirpdata_arr[1]);
				else $newsirpdata_month = $newsirpdata_arr[1];
				if (intval($newsirpdata_arr[0]) < 10) $newsirpdata_day = '0'.intval($newsirpdata_arr[0]);
				else $newsirpdata_day = $newsirpdata_arr[0];
				$newsirpdata = $newsirpdata_day.'/'.$newsirpdata_month.'/'.$newsirpdata_arr[2];
					
				$newsirpdata_ts = mktime(0,0,0,$newsirpdata_month,$newsirpdata_day,$newsirpdata_arr[2]);
				$newsirpdata_val = date('d/m/Y',$newsirpdata_ts);
				if ($newsirpdata_val == $newsirpdata) // update sul db
				{
// 					echo '<br/>newsirpdata OK val-post '.$newsirpdata_val.' - '.$newsirpdata;
					$updt = new stdClass;
					$updt->edizione_id = $ed;
// 					$updt->sirp = $newsirp;
					$updt->sirpdata = $newsirpdata_ts;
					update_sirp($updt);
				}
				else
				{
					// 			$errors['err_sirpdataedid_'.$ed] = 'err_sirpdataedid_'.$ed;
// 					echo '<br/>newsirpdata val-post '.$newsirpdata_val.' - '.$newsirpdata;
					$errors[$ed] = 'err_sirpdataedid_'.$ed;
				}
			}
		
			// 		echo '<br/>newsirpdata_arr: '.print_r($newsirpdata_arr).'<br/>';
			// 		echo '<br/>ed: '.$ed.'<br/>';
			// 		echo '<br/>newsirp: '.$newsirp.'<br/>';
			// 		echo '<br/>newsirpdata: '.$newsirpdata.'<br/>';
		
		}
// 		print_r($errors);
		if (count($errors) > 0) $param = "?err=1&eds=".implode(',',array_keys($errors));
		else $param = "?eds=".$ed;
		$location_next = "auth_mail.php".$param;
		// header('location: '.$location_next);
		redirect(new moodle_url($location_next));
	}
	else // non ci sono modifiche da salvare
	{
// 		echo '<html><head></head><BODY onLoad="history.go(-1);"></body></html>';
		$post_keys = array_keys($_POST);
// 		print_r($post_keys);
		$edid = 0;
		foreach ($post_keys as $pk)
		{
			$pkarr = explode('_',$pk);
// 			print_r($pkarr);
			if ($pkarr[0] == 'sirpedid') 
			{
				$edid =$pkarr[1];
				break;
			}
		}
		$param = "?eds=".$edid;
		$location_next = "auth_mail.php".$param;
		// header('location: '.$location_next);
		redirect(new moodle_url($location_next));
	}
	
}
else if ($action == get_string('auth_mail_invia', 'block_f2_gestione_risorse'))
{
	$errors = array();
	if (count($edizioni_arr) > 0)
	{
		$tipo_notifica_id = 1; // mail di autorizzazione
		$user_mail_queued = array();
		foreach ($edizioni_arr as $ed)
		{
			$template_chk = exists_template_notifica($ed,$tipo_notifica_id);
			if ($template_chk == true)
			{
				$sirp_chk = exists_sirp_dati($ed);
				if ($sirp_chk == true)
				{
					$dummy_chk = exists_dummy_email($ed,$tipo_notifica_id);
					if ($dummy_chk == false) //tutto ok
					{
						$user_mail_sent_arr = array();
						$user_mail_sent_arr = upload_mailqueue($ed,$tipo_notifica_id);
						$user_mail_queued = array_merge($user_mail_queued,$user_mail_sent_arr);
					}
					else 
					{
						$errors['ed_'.$ed] = 'err_inviomail_dummy';
					}
				}
				else 
				{
					$errors['ed_'.$ed] = 'err_inviomail_sirp';
				}
			}
			else
			{
				$errors['ed_'.$ed] = 'err_inviomail_templ';
			}
			/*
			if (check_all_condition_for_email_queue($ed,1) == true)
			{
				$user_mail_sent_arr = array();
				$user_mail_sent_arr = upload_mailqueue($ed,$tipo_notifica_id);
				$user_mail_queued = array_merge($user_mail_queued,$user_mail_sent_arr);
			}
			else $errors[$ed] = 'err_inviomail_'.$ed;
			*/
		}
// 		print_r($errors);
		if (count($errors) > 0) 
		{
			
			$context = get_context_instance(CONTEXT_SYSTEM);
			$PAGE->set_context($context);
// 			print_r($PAGE);
// 			print_r($errors);
			$formerr = new error_form_auth_mail('auth_mail.php?err=2&eds='.$ed,array('post_values_errors'=>$errors));
			$formerr->display();
			echo '<html><head><SCRIPT TYPE="text/javascript">
				function init()
				{
					var frm = document.getElementById("mform1");
					frm.submit();
				}
				</SCRIPT></head>
				<BODY onLoad="init()"></body></html>';
		}
		else 
		{
			$param = "?eds=".$ed;
			$location_next = "auth_mail.php".$param;
			// header('location: '.$location_next);
			redirect(new moodle_url($location_next));
		}
	}
	else // non ci sono modifiche da salvare
	{
		$post_keys = array_keys($_POST);
		$edid = 0;
		foreach ($post_keys as $pk)
		{
			$pkarr = explode('_',$pk);
			// 			print_r($pkarr);
			if ($pkarr[0] == 'sirpedid')
			{
				$edid =$pkarr[1];
				break;
			}
		}
		$param = "?eds=".$edid;
		$location_next = "auth_mail.php".$param;
		// header('location: '.$location_next);
		redirect(new moodle_url($location_next));
	}
}
else // azione non definita, torna indietro
{
	$param = "";
	$location_next = "auth_mail.php".$param;
	// header('location: '.$location_next);
	redirect(new moodle_url($location_next));
}
