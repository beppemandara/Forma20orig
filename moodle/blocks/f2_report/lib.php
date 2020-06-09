<?php
//$Id$
require_once($CFG->dirroot.'/f2_lib/core.php');
require_once($CFG->dirroot.'/f2_lib/management.php');
require_once($CFG->dirroot.'/f2_lib/report.php');
require_once($CFG->dirroot.'/lib/formslib.php');

/*
 insert into mdl_f2_parametri (id,descrizione, val_char,obbligatorio) values 
('p_f2_pentaho_base_url','url base di pentaho','http://pentaho.replycloud.prv:8080',1);

 insert into mdl_f2_parametri (id,descrizione, val_char,obbligatorio) values 
('p_f2_pentaho_path_get_detail','path per richiamare la servlet di pentaho che restituisce le informazioni sui prpt','/pentaho/SolutionRepositoryService?component=getSolutionRepositoryFileDetails&fullPath=',1);

insert into mdl_f2_report_pentaho (nome,full_path,attivo,extra_param)
values ('f2 parametri','/forma/F2_parameters.prpt',1,0);

-- rende tutti i report presenti attivabili dai manager
insert into mdl_f2_report_pentaho_role_map (id_report,id_role)
select distinct rep.id,role.id from mdl_f2_report_pentaho rep, mdl_role role
where role.shortname in ('manager') 
and not exists (select 1 from mdl_f2_report_pentaho_role_map rm where rm.id_report=rep.id and rm.id_role=role.id);

insert into mdl_f2_report_pentaho_param (nome,default_value)
values ('anno_formativo',null);

insert into mdl_f2_report_pentaho_param_map (id_report,id_report_param)
select r.id,rp.id from mdl_f2_report_pentaho r, mdl_f2_report_pentaho_param rp
where r.nome='f2 parametri' and rp.nome = 'anno_formativo';

update mdl_f2_report_pentaho set extra_param = 1 where nome = 'f2 parametri';


parametri: 
select rm.id,rep.*,rp.* 
from mdl_f2_report_pentaho_param_map rm,  mdl_f2_report_pentaho_param rp, mdl_f2_report_pentaho rep
where 
rep.nome='f2 parametri' and rep.attivo = 1 and rep.extra_param = 1 
and rep.id = rm.id_report and rm.id_report_param = rp.id

select count(rpm.id) as num_param
			from mdl_f2_report_pentaho_param_map rpm, mdl_f2_report_pentaho rep
			where lower(rep.full_path) = lower('/forma/F2_parameters.prpt') 
				and rep.attivo = 1 and rep.extra_param = 1 and rep.id = rpm.id_report
				and exists (select 1 from mdl_f2_report_pentaho_param p where p.id = rpm.id_report_param)


 */

// define ('REPORT_URL_PENTAHO_BASE', 'http://pentaho.replycloud.prv:8080');
// define('pentaho_userid','joe');
// define('pentaho_password','password');
// define('pentaho_solution','forma');

function get_user_cohort($userid) {
  global $DB,$USER;
	if (is_null($userid) or empty($userid)) $userid = $USER->id;
  $sql = "SELECT cohortid FROM {cohort_members} where userid = $userid and (cohortid = 7 or cohortid = 8)";
  $ret = $DB->get_record_sql($sql);
  return $ret->cohortid;
}

function get_pentaho_new_url_base($cohortid) {
  global $DB;
  if (is_null($cohortid) or empty($cohortid)) return 'nocohort';
  if ($cohortid == 7) {
    // consiglio
    $sql = "SELECT val_char FROM {f2_parametri} where id = 'p_f2_pentaho_consiglio_url'";
  } elseif ($cohortid == 8) {
    // giunta
    $sql = "SELECT val_char FROM {f2_parametri} where id = 'p_f2_pentaho_giunta_url'";
  }
  $ret = $DB->get_record_sql($sql);
  return $ret->val_char;
}

function get_pentaho_url_base()
{
	global $DB;
	$sql = "SELECT val_char FROM {f2_parametri} where id = 'p_f2_pentaho_base_url'";
	$ret = $DB->get_record_sql($sql);
	return $ret->val_char;
}

function get_pentaho_url_report() {
  global $DB;
	$sql = "SELECT val_char FROM {f2_parametri} where id = 'p_f2_pentaho_url_report_detail'";
	$ret = $DB->get_record_sql($sql);
  return $ret->val_char;
}

function get_pentaho_path_details()
{
	global $DB;
	$sql = "SELECT val_char FROM {f2_parametri} where id = 'p_f2_pentaho_path_get_detail'";
	$ret = $DB->get_record_sql($sql);
	return $ret->val_char;
}

function get_pentaho_info_servlet_path()
{
	$ret = get_pentaho_url_base().get_pentaho_path_details();
	return $ret;
}

function get_report_output_targets_select_box()
{
	$output_targets = get_all_output_targets();
	$select_start = '  '.get_string('report_formato','block_f2_report').': <select name="output_target" id="output_target" value="formato"> ';
	$select_end = '</select>';
	$options = '';
	foreach ($output_targets as $key=>$value)
	{
		$options .= '<option value="'.$key.'">'.$value.'</option>';
	}
	$select_targets = $select_start.$options.$select_end;
	return $select_targets;
}

function get_report_names_select_box($userid,$embeddable)
{
	$report_names = get_all_report_names($userid);
	if(count($report_names) > 0)
	{
		if ($embeddable) $onChange = ' onChange=hide_div(\'iframe_div\'); ';
		else $onChange = '';
		$select_start = '  '.get_string('report_nome','block_f2_report').': <select name="full_path_report" id="full_path_report" value="report_nome" '.$onChange.'> ';
		$select_end = '</select>';
		$options = '';
		foreach ($report_names as $n)
		{
			$options .= '<option value="'.$n->full_path.'">'.$n->nome.'</option>';
		}
		$select_targets = $select_start.$options.$select_end;
		return $select_targets;
	}
	else return '-1';
}

function get_all_output_targets()
{
	/*
	 $output_targets = array(
	 		'table/html;page-mode=stream' => 'html',
	 		'table/html;page-mode=page' => 'html-paginato',
	 		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;page-mode=flow' => 'xlsx',
	 		'table/excel;page-mode=flow' => 'xls',
	 		'pageable/pdf' => 'pdf',
	 		'table/csv;page-mode=stream' => 'csv',
	 		'table/rtf;page-mode=flow' => 'rtf',
	 		// 			'pageable/X-AWT-Graphics;image-type=png' => 'png',
	 );
	*/
	$output_targets = array(
			'pageable/pdf' => get_string('formato_pdf','block_f2_report'),
			'table/html;page-mode=stream' => get_string('formato_html','block_f2_report'),
			'table/html;page-mode=page' => get_string('formato_html_paginato','block_f2_report'),
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;page-mode=flow' => get_string('formato_xlsx','block_f2_report'),
			'table/excel;page-mode=flow' => get_string('formato_xls','block_f2_report'),
			'table/csv;page-mode=stream' => get_string('formato_csv','block_f2_report'),
			'table/rtf;page-mode=flow' => get_string('formato_rtf','block_f2_report'),
	);
	return $output_targets;
}

function get_format_name_by_type($type)
{
// 	$formats = array_flip(get_all_output_targets());
	$formats = get_all_output_targets();
// 	print_r($formats);exit;
	return $formats[$type];
}

function get_all_report_names($userid)
{
// 	$report_names = array();
// 	$report_names[] = 'F2_Parameters.prpt';
	global $DB,$USER;
	if (is_null($userid) or empty($userid)) $userid = $USER->id;
	if (is_siteadmin($userid)) $sql_user_role = ' ';
	else 
	{
		$sql_user_role = " and exists (select 1 
				from {f2_report_pentaho_role_map} reprmap, {role_assignments} ra  
				where ra.userid = ".$userid." and reprmap.id_report = rep.id 
						and reprmap.id_role=ra.roleid) ";
	}
	$sql_base = "SELECT rep.* FROM mdl_f2_report_pentaho rep where rep.attivo = 1 ";
	$order_by = " order by rep.nome ";
	$sql = $sql_base.$sql_user_role.$order_by;
	$report_names = $DB->get_records_sql($sql);
	return $report_names;
}

function get_report_name_by_full_path($full_path)
{
	if (is_null($full_path) or empty($full_path)) return '';
	else
	{
		global $DB;
		$sql_base = "SELECT distinct rep.nome FROM mdl_f2_report_pentaho rep 
				where rep.attivo = 1 and rep.full_path = '".$full_path."' ";
		$ret = $DB->get_record_sql($sql_base);
		return $ret->nome;
	}
}

function get_post_param_as_str($post_data=array())
{
	$post_str = '';
	foreach($post_data as $key=>$val)
	{
// 		$post_str .= $key.'='.$val.'&';
		$post_str .= $key.'='.urlencode($val).'&';
	}
	$post_str = substr($post_str, 0, -1);
	return $post_str;
}

function cURLopen($url,$post_data=array(),$httpmethod='post')
{
	$curlopen = null;
	$curlopen = curl_init(); 
	curl_setopt($curlopen, CURLOPT_HTTPGET, true);
	/*
	if ($httpmethod=='get')
	{
// 		if (preg_match('/userid/', $url) !== 1)
// 		{
// 			$url .= "&userid=".pentaho_userid;
// 		}
// 		if (preg_match('/password/', $url) !== 1)
// 		{
// 			$url .= "&password=".pentaho_password."";
// 		}
// 		curl_setopt($curlopen, CURLOPT_HTTPGET, true);
	}
	else // ci sono parametri da considerare nell'url
	{
		$post_str = get_post_param_as_str($post_data);
// 		curl_setopt($curlopen, CURLOPT_POST, TRUE);
// 		curl_setopt($curlopen, CURLOPT_POSTFIELDS, $post_str);
// 		print_r($url).print_r('?').print_r($post_str);exit;
		$url = $url.'?'.$post_str;
	}
	*/
	if (count($post_data) > 0)
	{
		$post_str = get_post_param_as_str($post_data);
		$delim_str = '&';
		if (preg_match('/\?/', $url) !== 1) $delim_str = '?';
		$url = $url.$delim_str.$post_str;
	}
	
// 	$url = "http://pentaho.replycloud.prv:8080/pentaho/content/reporting/?solution=forma&path=&name=F2_parameters.prpt&userid=joe&password=password&renderMode=report&output-target=table/html;page-mode=stream";
	curl_setopt($curlopen, CURLOPT_HEADER, 0);
	//curl_setopt($curlopen, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curlopen, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
	curl_setopt($curlopen, CURLOPT_RETURNTRANSFER, TRUE); 
	curl_setopt($curlopen, CURLOPT_URL, $url);
// 	curl_setopt($curlopen, CURLOPT_ENCODING, "deflate");

$result = curl_exec_follow($curlopen);
//	$result = curl_exec($curlopen); 

	$response =  curl_getinfo($curlopen);
	$http_status = $response['http_code'];
	curl_close($curlopen);

// 	if ($httpmethod=='post') 
// 	{
// 		echo '<br/>'.print_r($response);
// 		echo '<br>'.$ctype;
// 		echo '<br/>eff: '.print_r(curl_getinfo($curlopen, CURLINFO_REDIRECT_URL));
// 		echo '<br/>';
// 		echo $url;
// 		echo '<br/>';
// 		var_dump($results);
// 		exit;
// 	}
// 	return $results;

	if ($http_status == 200) return $result;
	else if ($http_status == 301 or $http_status == 302) 
	{
// 		$res = curl_exec_follow($curlopen,10);
// 		if ($res !== false) return $res;
// 		else return 'Errore Pentaho'; 
		$redirect_url = $response['redirect_url'];
// 		return cURLopen($redirect_url,array(),'get');
		return cURLopen($redirect_url,array());
	}
	else return get_string('errore_connessione_pentaho','block_f2_report');
}

function curl_exec_follow($ch, &$maxredirect = null) {
	$mr = $maxredirect === null ? 5 : intval($maxredirect);
	if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $mr > 0);
		curl_setopt($ch, CURLOPT_MAXREDIRS, $mr);
	} else {
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		if ($mr > 0) {
			$newurl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

			$rch = curl_copy_handle($ch);
			curl_setopt($rch, CURLOPT_HEADER, true);
			curl_setopt($rch, CURLOPT_NOBODY, true);
			curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
			curl_setopt($rch, CURLOPT_RETURNTRANSFER, true);
			do {
				curl_setopt($rch, CURLOPT_URL, $newurl);
				$header = curl_exec($rch);
				if (curl_errno($rch)) {
					$code = 0;
				} else {
					$code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
					if ($code == 301 || $code == 302) {
						preg_match('/Location:(.*?)\n/', $header, $matches);
						$newurl = trim(array_pop($matches));
					} else {
						$code = 0;
					}
				}
			} while ($code && --$mr);
			curl_close($rch);
			if (!$mr) {
				if ($maxredirect === null) {
					trigger_error('Too many redirects. When following redirects, libcurl hit the maximum amount.', E_USER_WARNING);
				} else {
					$maxredirect = 0;
				}
				return false;
			}
			curl_setopt($ch, CURLOPT_URL, $newurl);
		}
	}
	return curl_exec($ch);
}

/*
if(!function_exists('get_mime_content_type')) {

	function get_mime_content_type($type='html') {

		$mime_types = array(

				'txt' => 'text/plain',
				'htm' => 'text/html',
				'html' => 'text/html',
				'php' => 'text/html',
				'css' => 'text/css',
				'js' => 'application/javascript',
				'json' => 'application/json',
				'xml' => 'application/xml',
				'swf' => 'application/x-shockwave-flash',
				'flv' => 'video/x-flv',

				// images
				'png' => 'image/png',
				'jpe' => 'image/jpeg',
				'jpeg' => 'image/jpeg',
				'jpg' => 'image/jpeg',
				'gif' => 'image/gif',
				'bmp' => 'image/bmp',
				'ico' => 'image/vnd.microsoft.icon',
				'tiff' => 'image/tiff',
				'tif' => 'image/tiff',
				'svg' => 'image/svg+xml',
				'svgz' => 'image/svg+xml',

				// archives
				'zip' => 'application/zip',
				'rar' => 'application/x-rar-compressed',
				'exe' => 'application/x-msdownload',
				'msi' => 'application/x-msdownload',
				'cab' => 'application/vnd.ms-cab-compressed',

				// audio/video
				'mp3' => 'audio/mpeg',
				'avi' => 'video/x-msvideo',
				'qt' => 'video/quicktime',
				'mov' => 'video/quicktime',

				// adobe
				'pdf' => 'application/pdf',
				'psd' => 'image/vnd.adobe.photoshop',
				'ai' => 'application/postscript',
				'eps' => 'application/postscript',
				'ps' => 'application/postscript',

				// ms office
				'doc' => 'application/msword',
				'rtf' => 'application/rtf',
				'xls' => 'application/vnd.ms-excel',
				'ppt' => 'application/vnd.ms-powerpoint',

				// open office
				'odt' => 'application/vnd.oasis.opendocument.text',
				'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
		);

		$ext = strtolower(array_pop(explode('.',$type)));
		if (array_key_exists($ext, $mime_types)) {
			return $mime_types[$ext];
		}
		elseif (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME);
			$mimetype = finfo_file($finfo, $type);
			finfo_close($finfo);
			return $mimetype;
		}
		else {
			return 'application/octet-stream';
		}
	}
}
*/

function get_mimetype($pentaho_type='')
{
	$pentaho_types = array(
			'table/html;page-mode=stream' => 'text/html',
			'table/html;page-mode=page' => 'text/html',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;page-mode=flow' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'table/excel;page-mode=flow' => 'application/vnd.ms-excel',
			'pageable/pdf' => 'application/pdf',
			'table/csv;page-mode=stream' => 'text/csv',
			'table/rtf;page-mode=flow' => 'application/rtf',
			'pageable/X-AWT-Graphics;image-type=png' => 'image/png',
					);
	if (array_key_exists($pentaho_type, $pentaho_types)) 
	{
		return $pentaho_types[$pentaho_type];
	}
	else return 'application/octet-stream';
}

function get_file_extension($pentaho_type='')
{
	$pentaho_types = array(
			'table/html;page-mode=stream' => '.html',
			'table/html;page-mode=page' => '.html',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;page-mode=flow' => '.xlsx',
			'table/excel;page-mode=flow' => '.xls',
			'pageable/pdf' => '.pdf',
			'table/csv;page-mode=stream' => '.csv',
			'table/rtf;page-mode=flow' => '.rtf',
			'pageable/X-AWT-Graphics;image-type=png' => '.png',
	);
	if (array_key_exists($pentaho_type, $pentaho_types))
	{
		return $pentaho_types[$pentaho_type];
	}
	else return '.html';
}

function perform_report($report_content,$pentaho_post_data=array(),$error=false)
{
	//ob_start();
	if ($error) 
	{
		echo get_string('errore_connessione_pentaho','block_f2_report');
	}
	else if (array_key_exists('renderMode', $pentaho_post_data))
	{
		// fix css address from relative to absolute
		$regex = "/\/pentaho\/getImage\?image=style/";
		$replacement = get_pentaho_url_base()."/pentaho/getImage?image=style";
		$ext = get_file_extension($pentaho_post_data['output-target']);
		$mimetype = get_mimetype($pentaho_post_data['output-target']);
		if ((strtolower($pentaho_post_data['renderMode']) == 'download')
			or (($ext !== '.html') and ($ext !== '.pdf')
// 						and ($ext !== '.png')
				)
			)
		{
			$file = 'report_'.array_pop(array_reverse(explode('.',$pentaho_post_data['name']))).'_'.date('d_m_Y').$ext;
			header('Content-Description: File Transfer');
			header('Content-Type: '.$mimetype.';charset="utf-8"');
			header('Content-Disposition: attachment; filename='.basename($file));
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
   			header("Cache-Control: private",false); // required for certain browsers 
			header('Pragma: public');	
			ob_clean();
			flush();
			
			if ($ext == '.html') 
			{
				// embedding css into html file
				$report_content = preg_replace($regex, $replacement, $report_content);
// 				$pattern_css = '/<link type="text\/css" rel="stylesheet" href="http:\/\/pentaho\.replycloud\.prv:8080\/pentaho\/getImage\?image=.*\.css" \/>/';
				$pattern_css = '/<link type="text\/css" rel="stylesheet" href="'.preg_quote(get_pentaho_url_base(),'/').'\/pentaho\/getImage\?image=.*\.css" \/>/';
				
// 				$pattern_absolute_url = '/http:\/\/pentaho\.replycloud\.prv:8080\/pentaho\/getImage\?image=.*\.css/';
				$pattern_absolute_url = '/'.preg_quote(get_pentaho_url_base(),'/').'\/pentaho\/getImage\?image=.*\.css/';
				preg_match($pattern_absolute_url, $report_content, $matches, PREG_OFFSET_CAPTURE);
				$absolute_url_css = $matches[0][0];
				if (preg_match($pattern_absolute_url, $absolute_url_css) === 1)
				{
					$css_content = cURLopen($absolute_url_css,array());
					$css_content_replacement = '<STYLE type="text/css">
												'.$css_content.'</STYLE>';
					$report_content = preg_replace($pattern_css, $css_content_replacement, $report_content);
				}
				echo $report_content;
			}
			else echo $report_content;
		}
		else // (strtolower($pentaho_post_data['renderMode']) == 'report')
		{
			header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
			header('Pragma: public');
			header('Content-Type: '.$mimetype.';charset="utf-8"');
			
			if ($ext == '.html') echo preg_replace($regex, $replacement, $report_content);
			else echo $report_content;
		} 
	}
	else echo get_string('errore_connessione_pentaho','block_f2_report');
}

function fixEncoding($in_str)
{
	$cur_encoding = mb_detect_encoding($in_str) ;
	if($cur_encoding == "UTF-8" and mb_check_encoding($in_str,"UTF-8"))
		return $in_str;
	else
		return utf8_encode($in_str);
}

function get_reporting_url_from_pentaho($full_path_report)
{
// 	$url = "http://pentaho.replycloud.prv:8080/pentaho/SolutionRepositoryService?component=getSolutionRepositoryFileDetails&fullPath=/forma/F2_parameters.prpt";
	$url = get_pentaho_info_servlet_path().$full_path_report;
// 	$res = cURLopen($url,null,'get');
	$res = cURLopen($url,array());
// 	echo '<br/>$url: <br/>';
// 	print_r($url);
// 	echo '<br/> fine $url <br/>';exit;
	$xml = simplexml_load_string($res);

	if ($xml)
	{
		$xml = (array)$xml;
// 		$reporting_url = $xml["@attributes"]["param-service-url"]; // report url
		$reporting_url = $xml["@attributes"]["url"];
// 		$login = "&userid=".pentaho_userid."&password=".pentaho_password;
		$login ='';
		$reporting_url = preg_replace('/\?renderMode=PARAMETER&/', '?', $reporting_url);
		$reporting_url = preg_replace('/&renderMode=PARAMETER&/', '&', $reporting_url);
		$reporting_url = preg_replace('/&renderMode=PARAMETER$/', '', $reporting_url);
		$reporting_url = get_pentaho_url_base().$reporting_url.$login;
		$param_array = explode('?',$reporting_url);
		$par_array = explode('&',$param_array[1]);
		$ret_array = array();
		$ret_array['url'] = $param_array[0];
		foreach ($par_array as $p)
		{
			$temp = array();
			$temp = explode('=',$p);
			$ret_array[$temp[0]] = $temp[1];
		}
		return $ret_array;
	}
	else return '-1';
}

function is_to_download($renderMode,$output_target)
{
	$ext = get_file_extension($output_target);
	if ((strtolower($renderMode) == 'download')
			or (($ext !== '.html') and ($ext !== '.pdf')
			// 						and ($ext !== '.png')
			)
	) return true;
	else return false;
}

function get_report_form($userid,$next_page="get_report_pentaho.php",$selected_report='-1',$formato='table/html;page-mode=page')
{
	global $USER;
	if (is_null($userid) or empty($userid)) $userid = $USER->id;
// 	$url_next = "get_report_pentaho.php";
// 	if ($next_page == "get_report_pentaho.php")
	$embeddable = is_embeddable();
	if ($selected_report == "-1")
	{
		$select_names = get_report_names_select_box($userid,$embeddable);
		if ($select_names !== '-1')
		{
			$select_targets = get_report_output_targets_select_box();
			if ($embeddable == true)
			{
				$result_target = "results";
				$test_iframe = '<div id="iframe_div" hidden="hidden">
		<iframe id="results" name="results" seamless="seamless" width="100%" height="1000"></iframe>
		</div>';
			}
			else
			{
				$result_target = "_blank";
				$test_iframe = '';
			}
			$submit_lbl = get_string('report_genera','block_f2_report');
			$test_form = '<form action="'.$next_page.'"
		method="post" target="'.$result_target.'">'
						.$select_names.$select_targets.'
  			<input type="submit" value=" '.$submit_lbl.' " onclick="show_div(\'iframe_div\')">
			</form><br/><br/>';
			$return = $test_form.$test_iframe;
			return $return;
		}
		else return get_string('no_report_pentaho_available','block_f2_report');
	}
	else // form per recuperare i parametri del report scelto in precedenza
	{
		$params = get_report_parameters($selected_report);
		prepare_page($embeddable);
		$mform = new report_parametri_form($next_page,array('post_values'=>$params,'full_path_report'=>$selected_report,'output-target'=>$formato));
		$mform->display();
// 		$mform = new report_parametri_form($next_page,array('post_values'=>$params_data));
// 		display_form_parameters($mform);

	}
}

function prepare_page($embeddable)
{
	global $PAGE,$OUTPUT,$USER;
	require_once '../../config.php';
	// 	require_once $CFG->libdir . '/formslib.php';
	require_login();
	$blockid = get_block_id(get_string('pluginname_db','block_f2_report'));
	$context = get_context_instance(CONTEXT_BLOCK, $blockid);
	// 	$context = get_context_instance(CONTEXT_SYSTEM);
	// 	var_dump($context);
	$PAGE->set_context($context);
	// inizio import per generazione albero //
	$PAGE->requires->js('/f2_lib/jquery/jquery-1.7.1.min.js',true);
	$PAGE->requires->js('/f2_lib/jquery/jquery-ui.min.js',true);
	$PAGE->requires->js('/f2_lib/jquery/jquery.cookie.js',true);
	$PAGE->requires->js('/f2_lib/jquery/jquery.dynatree.js',true);
	$PAGE->requires->css('/f2_lib/jquery/css/skin/ui.dynatree.css',true);
	$PAGE->requires->js('/f2_lib/jquery/jquery.blockUI.js',true);
	// fine import per generazione albero //
	$PAGE->requires->js(new moodle_url('lib_report.js'),true);
	$PAGE->requires->js(new moodle_url('lib_form.js'),true);
	
	$blockname = get_string('pluginname', 'block_f2_report');
	$PAGE->set_pagelayout('standard');
	$PAGE->set_url('/blocks/f2_report/prenotazioni.php');
	$PAGE->set_title(get_string('report_pentaho', 'block_f2_report'));
	$PAGE->settingsnav;
	// 	$PAGE->set_heading($SITE->shortname.': '.$blockname);
	
	if (!$embeddable)
	{
		$userid       = optional_param('userid', 0, PARAM_INT);
		$full_path_report   = optional_param('full_path_report', '-1', PARAM_TEXT);
		$output_target     = optional_param('output_target_select', 'table/html;page-mode=page', PARAM_TEXT);
		
		if($userid==0) $userid=intval($USER->id);
		else if($userid!=0 && has_capability('block/f2_report:viewreport', $context) && validate_own_dipendente($userid)) $userid=$userid;
		else die();
		
		$userdata = get_user_data($userid);
		$objsettore = get_user_organisation($userid);
		
		$settore_id = $objsettore[0];
		$settore_nome = is_null($objsettore[1]) ? 'n.d.' : $objsettore[1];
		
		// TABELLA DATI ANAGRAFICI
		$table = new html_table();
		$table->align = array('right', 'left');
		$table->data = array(
				array('Cognome Nome ','<b>'.$userdata->lastname.' '.$userdata->firstname.'</b>'),
				array('Matricola',''.$userdata->idnumber.''),
				array('Categoria',''.$userdata->category.''),
				array('Direzione / Ente',''.$direzione_nome.''),
				array('Settore',''.$settore_nome.'')
		);
		
		echo $OUTPUT->header();
		echo $OUTPUT->heading(get_string('report_pentaho', 'block_f2_report'));
		echo $OUTPUT->box_start();
		
		echo html_writer::table($table);
		
		$table = new html_table();
		$table->align = array('right', 'left');
		$table->data = array(
				array(get_string('report_nome', 'block_f2_report'),'<b>'.get_report_name_by_full_path($full_path_report).'</b>'),
				array(get_string('report_formato', 'block_f2_report'),''.get_format_name_by_type($output_target)),
		);
		
		echo html_writer::table($table);
		echo $OUTPUT->box_end();
	}
	else 
	{
		echo $OUTPUT->header();
		echo $OUTPUT->box_start();
		echo $OUTPUT->box_end();
	}
}
/*
function display_form_parameters($form)
{
	global $PAGE,$OUTPUT,$CFG,$USER;
	require_once '../../config.php';
// 	require_once $CFG->libdir . '/formslib.php';
	require_login();
	$blockid = get_block_id(get_string('pluginname_db','block_f2_report'));
	$context = get_context_instance(CONTEXT_BLOCK, $blockid);
// 	$context = get_context_instance(CONTEXT_SYSTEM);
// 	var_dump($context);
	$PAGE->set_context($context);
	// inizio import per generazione albero //
	$PAGE->requires->js('/f2_lib/jquery/jquery-1.7.1.min.js',true);
	$PAGE->requires->js('/f2_lib/jquery/jquery-ui.min.js',true);
	$PAGE->requires->js('/f2_lib/jquery/jquery.cookie.js',true);
	$PAGE->requires->js('/f2_lib/jquery/jquery.dynatree.js',true);
	$PAGE->requires->css('/f2_lib/jquery/css/skin/ui.dynatree.css',true);
	$PAGE->requires->js('/f2_lib/jquery/jquery.blockUI.js',true);
	// fine import per generazione albero //
	$blockname = get_string('pluginname', 'block_f2_report');
	$PAGE->set_pagelayout('standard');
	$PAGE->set_url('/blocks/f2_report/prenotazioni.php');
	$PAGE->set_title(get_string('report_pentaho', 'block_f2_report'));
	$PAGE->settingsnav;
// 	$PAGE->set_heading($SITE->shortname.': '.$blockname);
	echo $OUTPUT->header();
	$form->display();
}
*/
function display_report_viewer($full_path_report,$param = array())
{
	$reporting_url = get_reporting_url_from_pentaho($full_path_report);
// 	echo '<br/>reporting_url: <br/>';
// 	print_r($reporting_url);
// 	echo '<br/> fine reporting_url <br/>';exit;
	$embeddable = is_embeddable();
	if ($reporting_url !== '-1')
	{
		$url = $reporting_url['url'];
		foreach (array_slice($reporting_url,1) as $key=>$value)
		{
			$param[$key] = $value;
		}
		$url_src = $url.'?'.get_post_param_as_str($param);
		
// 		echo '<br/>url_src: <br/>';
// 		print_r($url_src);
// 		echo '<br/> fine url_src <br/>';exit;
		
		if ($embeddable == true)
		{
			$iframe = '<iframe src="'.$url_src.'" id="report" name="report" onLoad="resize_iframe(\'report\')"
			seamless="seamless" width="100%" height="900" ></iframe>';
			echo $iframe;
		}
		else 
		{
			//ob_start();
			//header('Location: '.$url_src);
			redirect($url_src);
		}	
	}
	else
	{
		if($embeddable == false){
			global $OUTPUT;
			echo $OUTPUT->header();
		}
		echo get_string('errore_connessione_pentaho','block_f2_report');		
	}
}

// da testare con moodle e pentaho sullo stesso server
function is_embeddable()
{	
	$ret = false;
	/*
	 DECOMMENTARE PER VISUALIZZA NELLA STESSA PAGINA IL REPORT
	 
	$moodle_address = $_SERVER['REMOTE_ADDR'];
	$pentaho_address = gethostbyname(get_pentaho_url_base());
	$browser = $_SERVER['HTTP_USER_AGENT'];
	if (preg_match('/Chrom/', $browser) === 1)  $ret = true;
	else if ((preg_match('/'.preg_quote($moodle_address).'/', $pentaho_address) === 1))
	{
		 $ret = true;
	}*/
	return $ret;
}

function get_report_num_parameter($full_path_report)
{
	if (is_null($full_path_report) or empty($full_path_report) or $full_path_report=='-1')
	{
		$ret = -1;
	}
	else 
	{
		global $DB;
		$sql = "select count(rpm.id) as num_param
			from {f2_report_pentaho_param_map} rpm, {f2_report_pentaho} rep
			where lower(rep.full_path) = lower('".$full_path_report."') 
				and rep.attivo = 1 and rep.extra_param = 1 and rep.id = rpm.id_report
				and exists (select 1 from {f2_report_pentaho_param} p where p.id = rpm.id_report_param)";
		$res = $DB->get_record_sql($sql);
		$ret = intval($res->num_param);
	}
	return $ret;
}

function get_report_parameters($full_path_report)
{
	if (is_null($full_path_report) or empty($full_path_report) or $full_path_report=='-1')
	{
		$ret = new stdClass;
	}
	else
	{
		global $DB;
		$sql = "select p.* 
			from {f2_report_pentaho_param_map} rpm, {f2_report_pentaho} rep, {f2_report_pentaho_param} p
			where lower(rep.full_path) = lower('".$full_path_report."')
				and rep.attivo = 1 and rep.extra_param = 1 and rep.id = rpm.id_report
				and p.id = rpm.id_report_param
				order by p.nome";
		$ret = $DB->get_records_sql($sql);
	}
	return $ret;
}

class report_parametri_form extends moodleform
{
	// Define the form
	function definition () 
	{
		global $DB,$USER;

		$mform =& $this->_form;
		
		$post_values = $this->_customdata['post_values'];
		$full_path_report = $this->_customdata['full_path_report'];
		$userid = $this->_customdata['userid'];
		$formato = $this->_customdata['output-target'];
		$anno = $this->_customdata['ajax_anno_formativo_per_sessione'];
		if (is_null($anno) or empty($anno))
		{
			$anno = $this->_customdata['ajax_anno_formativo_per_corso'];
		}
		if (is_null($anno) or empty($anno))
		{
			$anno = get_anno_formativo_corrente();
		}
// 		print_r($anno);
		if (is_null($userid) or empty($userid)) $userid = $USER->id;
		if (isset($post_values) and (!is_null($post_values)) and (!empty($post_values)))
		{
// 			$post_values = json_decode($post_values);
// 			$mform->addElement('hidden', 'post_values',$post_values);
// 			print_r($post_values);
// 			$anno_formativo = get_anno_formativo_corrente();
			foreach ($post_values as $par)
			{
// 				echo '<br/>';
// 				print_r($par);
// 				echo '<br/>';
				$nome_parametro = $par->nome;
				if ($nome_parametro == 'anno_formativo')
				{
					$this->print_scelta_anno_formativo();
				}
				else if ($nome_parametro == 'sessione')
				{
					$this->print_scelta_sessione_su_anno_formativo($anno);
				}
				else if ($nome_parametro == 'corso')
				{
					$this->print_scelta_corso_su_anno_formativo($anno);
				}
				else if ($nome_parametro == 'dominio')
				{
					$this->print_scelta_dominio();
				}
				else if ($nome_parametro == 'id_f2_param')
				{
					$this->print_scelta_f2_id_param();
				}
			}
		}
		$mform->addElement('hidden','full_path_report',$full_path_report);
		$mform->addElement('hidden','psent','1');
		$mform->addElement('hidden','userid',$userid);
		$mform->addElement('hidden','output-target',$formato);
		$buttonarray = array();
		$buttonarray[] =& $mform->createElement('submit', 'send', get_string('conferma', 'block_f2_report'));
		$mform->addGroup($buttonarray, 'actions', '&nbsp;', array(' '), false);
	}
	
	// test function
	function print_scelta_f2_id_param()
	{
		global $DB;
		$sql = "select distinct p.id as f2_id_param from {f2_parametri} p ";
		$ret = $DB->get_records_sql($sql);
		$select_arr = array();
		foreach ($ret as $k=>$v)
		{
// 			echo '<br/>';
// 			print_r($k);
// 			echo '<br/>';
// 			print_r($v->f2_id_param);
// 			echo '<br/>';
			$select_arr[$k] = $k;
		}
// 		echo '<br/>';
// 		print_r($select_arr);
// 		echo '<br/>';
		$mform =& $this->_form;
		$mform->addElement('select', 'report_pentaho_param_id_f2_param', 'id_f2_param',$select_arr);
	}
	
	function print_scelta_dominio()
	{
		global $USER;
		$mform =& $this->_form;
		
		//$organisation = get_user_organisation($USER->id);
		$organisation = get_user_viewable_organisation($USER->id);
		$organisation_id = $organisation[0];
		$organisation_title = $organisation[1];
		$hierarchy = recursivesubtreejson($organisation_id, $organisation_title);
		$mform->addElement('static', 'organisationselector',
				get_string('scegli_settore', 'block_f2_prenotazioni'),
				get_organisation_picker_html('organisationtitle', 'report_pentaho_param_dominio',
						get_string('scegli_settore_apri', 'block_f2_prenotazioni'),
						'domini',$hierarchy, '  '.$organisation_title));
		$mform->addElement('hidden', 'report_pentaho_param_dominio');
		$mform->setType('report_pentaho_param_dominio', PARAM_INT);
		$mform->setDefault('report_pentaho_param_dominio', $organisation_id ? $organisation_id : 0);
	}
	
	function print_scelta_anno_formativo()
	{
		global $CFG;
		require_once($CFG->dirroot.'/blocks/f2_gestione_risorse/lib.php');
		$anni_rs = get_anni_formativi_sessioni_per_select_form();
		$mform =& $this->_form;
		$mform->addElement('select', 'report_pentaho_param_anno_formativo', get_string('report_anno_formativo_cerca', 'block_f2_report'),$anni_rs);
	}
	function print_scelta_sessione_su_anno_formativo($anno)
	{
		if (is_null($anno) or empty($anno)) $anno = get_anno_formativo_corrente();
		$sessioni_rs = get_sessioni_per_select_form_by_anno($anno);
		$mform =& $this->_form;
		$mform->addElement('select', 'report_pentaho_param_sessione', get_string('report_sessione_cerca', 'block_f2_report'),$sessioni_rs);
	}
	function print_scelta_corso_su_anno_formativo($anno)
	{
		if (is_null($anno) or empty($anno)) $anno = get_anno_formativo_corrente();
		$course_rs = get_corsi_per_select_form_by_anno($anno);
		$mform =& $this->_form;
		$mform->addElement('select', 'report_pentaho_param_corso', get_string('report_corso_cerca', 'block_f2_report'),$course_rs);
	}
}
function get_sessioni_per_select_form_by_anno($anno)
{
	if (is_null($anno) or empty($anno)) $anno = get_anno_formativo_corrente();
	global $DB;
	$sess_sql = "select distinct s.id, s.numero from {f2_sessioni} s where s.anno = ".$anno." order by s.numero asc";
	$sess_rs = $DB->get_records_sql($sess_sql);
	$sess_arr = array();
	if (!is_null($sess_rs))
	{
		foreach ($sess_rs as $s)
		{
			$sess_arr[$s->id] = $s->numero;
		}
	}
	if (count($sess_arr) == 0)
	{
		$sess_arr['-1'] = '-';
	}
	return $sess_arr;
}
function print_scelta_sessione_from_selected_year($anno)
{
	if (is_null($anno) or empty($anno)) $anno = get_anno_formativo_corrente();
	$sessioni_rs = get_sessioni_per_select_form_by_anno($anno);
	foreach ($sessioni_rs as $id=>$num)
	{
		echo '<option value="'.$id.'">'.$num.'</option>';
	}
}

function get_corsi_per_select_form_by_anno($anno)
{
	if (is_null($anno) or empty($anno)) $anno = get_anno_formativo_corrente();
	global $DB;
	$course_sql = "select ac.id, ac.courseid, concat(c.shortname,' - ',c.fullname) as corso
			from {f2_anagrafica_corsi} ac, {course} c where ac.anno = ".$anno." 
					and ac.courseid = c.id 
					order by corso asc";
	$course_rs = $DB->get_records_sql($course_sql);
	$course_arr = array();
	if (!is_null($course_rs))
	{
		foreach ($course_rs as $c)
		{
			$course_arr[$c->courseid] = $c->corso;
		}
	}
	if (count($course_arr) == 0)
	{
		$course_arr['-1'] = '-';
	}
	return $course_arr;
}
function print_scelta_corso_from_selected_year($anno)
{
	if (is_null($anno) or empty($anno)) $anno = get_anno_formativo_corrente();
	$course_rs = get_corsi_per_select_form_by_anno($anno);
	foreach ($course_rs as $id=>$val)
	{
		echo '<option value="'.$id.'">'.$val.'</option>';
	}
}

/* START AGGIUNTA FUNZIONI PER REPORT STATISTICI */
function get_report_form_stat($userid,$next_page="get_report_statistici_pentaho.php",$selected_report='-1',$formato='table/html;page-mode=page')
{
	global $USER;
	if (is_null($userid) or empty($userid)) $userid = $USER->id;
	$embeddable = is_embeddable();  // restituisce false
	if ($selected_report == "-1")
	{
		$select_names = get_report_names_select_box_stat($userid,$embeddable);
		if ($select_names !== '-1')
		{
			$select_targets = get_report_output_targets_select_box();
			if ($embeddable == true)
			{
				$result_target = "results";
				$test_iframe = '<div id="iframe_div" hidden="hidden"><iframe id="results" name="results" seamless="seamless" width="100%" height="1000"></iframe></div>';
			}
			else
			{
				$result_target = "_blank";
				$test_iframe = '';
			}
			$submit_lbl = get_string('report_genera','block_f2_report');
			$test_form = '<form action="'.$next_page.'"method="post" target="'.$result_target.'">'
						.$select_names.$select_targets.'
  			<input type="submit" value=" '.$submit_lbl.' " onclick="show_div(\'iframe_div\')"></form><br/><br/>';
			$return = $test_form.$test_iframe;
			return $return;
		}
		else return get_string('no_report_pentaho_available','block_f2_report');
	}
	else // form per recuperare i parametri del report scelto in precedenza
	{
		$params = get_report_parameters_stat($selected_report);
		prepare_page_stat($embeddable);
		$mform = new report_parametri_form($next_page,array('post_values'=>$params,'full_path_report'=>$selected_report,'output-target'=>$formato));
		$mform->display();
	}
}

function get_report_names_select_box_stat($userid,$embeddable)
{
	$report_names = get_all_report_names_stat($userid);
	if(count($report_names) > 0)
	{
		if ($embeddable) $onChange = ' onChange=hide_div(\'iframe_div\'); ';
		else $onChange = '';
		$select_start = '  '.get_string('report_nome','block_f2_report').': <select name="full_path_report" id="full_path_report" value="report_nome" '.$onChange.'> ';
		$select_end = '</select>';
		$options = '';
		foreach ($report_names as $n)
		{
			$options .= '<option value="'.$n->full_path.'">'.$n->nome.'</option>';
		}
		$select_targets = $select_start.$options.$select_end;
		return $select_targets;
	}
	else return '-1';
}

function get_all_report_names_stat($userid)
{
	global $DB,$USER;
	if (is_null($userid) or empty($userid)) $userid = $USER->id;
	if (is_siteadmin($userid)) $sql_user_role = ' ';
	else 
	{
		$sql_user_role = " and exists (select 1 
				from {f2_report_pentaho_role_map_stat} reprmaps, {role_assignments} ra  
				where ra.userid = ".$userid." and reprmaps.id_report = reps.id 
						and reprmaps.id_role=ra.roleid) ";
	}
	$sql_base = "SELECT reps.* FROM mdl_f2_report_pentaho_stat reps where reps.attivo = 1 ";
	$order_by = " order by reps.nome ";
	$sql = $sql_base.$sql_user_role.$order_by;
	$report_names = $DB->get_records_sql($sql);
	return $report_names;
}

function get_report_parameters_stat($full_path_report)
{
	if (is_null($full_path_report) or empty($full_path_report) or $full_path_report=='-1')
	{
		$ret = new stdClass;
	}
	else
	{
		global $DB;
		$sql = "select ps.* 
			from {f2_report_pentaho_param_map_stat} rpms, {f2_report_pentaho_stat} reps, {f2_report_pentaho_param_stat} ps
			where lower(reps.full_path) = lower('".$full_path_report."')
				and reps.attivo = 1 and reps.extra_param = 1 and reps.id = rpms.id_report
				and ps.id = rpms.id_report_param
				order by ps.nome";
		$ret = $DB->get_records_sql($sql);
	}
	return $ret;
}

function prepare_page_stat($embeddable)
{
	global $PAGE,$OUTPUT,$USER;
	require_once '../../config.php';

	require_login();
	$blockid = get_block_id(get_string('pluginname_db','block_f2_report'));
	$context = get_context_instance(CONTEXT_BLOCK, $blockid);

	$PAGE->set_context($context);
	// inizio import per generazione albero //
	$PAGE->requires->js('/f2_lib/jquery/jquery-1.7.1.min.js',true);
	$PAGE->requires->js('/f2_lib/jquery/jquery-ui.min.js',true);
	$PAGE->requires->js('/f2_lib/jquery/jquery.cookie.js',true);
	$PAGE->requires->js('/f2_lib/jquery/jquery.dynatree.js',true);
	$PAGE->requires->css('/f2_lib/jquery/css/skin/ui.dynatree.css',true);
	$PAGE->requires->js('/f2_lib/jquery/jquery.blockUI.js',true);
	// fine import per generazione albero //
	$PAGE->requires->js(new moodle_url('lib_report.js'),true);
	$PAGE->requires->js(new moodle_url('lib_form.js'),true);
	
	$blockname = get_string('pluginname', 'block_f2_report');
	$PAGE->set_pagelayout('standard');
	$PAGE->set_url('/blocks/f2_report/prenotazioni.php');
	$PAGE->set_title(get_string('report_pentaho', 'block_f2_report'));
	$PAGE->settingsnav;
	
	if (!$embeddable)
	{
		$userid       = optional_param('userid', 0, PARAM_INT);
		$full_path_report   = optional_param('full_path_report', '-1', PARAM_TEXT);
		$output_target     = optional_param('output_target_select', 'table/html;page-mode=page', PARAM_TEXT);
		
		if($userid==0) $userid=intval($USER->id);
		else if($userid!=0 && has_capability('block/f2_report:viewreport', $context) && validate_own_dipendente($userid)) $userid=$userid;
		else die();
		
		$userdata = get_user_data($userid);
		$objsettore = get_user_organisation($userid);
		
		$settore_id = $objsettore[0];
		$settore_nome = is_null($objsettore[1]) ? 'n.d.' : $objsettore[1];
		
		// TABELLA DATI ANAGRAFICI
		$table = new html_table();
		$table->align = array('right', 'left');
		$table->data = array(
				array('Cognome Nome ','<b>'.$userdata->lastname.' '.$userdata->firstname.'</b>'),
				array('Matricola',''.$userdata->idnumber.''),
				array('Categoria',''.$userdata->category.''),
				array('Direzione / Ente',''.$direzione_nome.''),
				array('Settore',''.$settore_nome.'')
		);
		
		echo $OUTPUT->header();
		echo $OUTPUT->heading(get_string('report_pentaho', 'block_f2_report'));
		echo $OUTPUT->box_start();
		
		echo html_writer::table($table);
		
		$table = new html_table();
		$table->align = array('right', 'left');
		$table->data = array(
				array(get_string('report_nome', 'block_f2_report'),'<b>'.get_report_name_by_full_path_stat($full_path_report).'</b>'),
				array(get_string('report_formato', 'block_f2_report'),''.get_format_name_by_type_stat($output_target)),
		);
		
		echo html_writer::table($table);
		echo $OUTPUT->box_end();
	}
	else 
	{
		echo $OUTPUT->header();
		echo $OUTPUT->box_start();
		echo $OUTPUT->box_end();
	}
}

function get_report_name_by_full_path_stat($full_path)
{
	if (is_null($full_path) or empty($full_path)) return '';
	else
	{
		global $DB;
		$sql_base = "SELECT distinct reps.nome FROM mdl_f2_report_pentaho_stat reps 
				where reps.attivo = 1 and reps.full_path = '".$full_path."' ";
		$ret = $DB->get_record_sql($sql_base);
		return $ret->nome;
	}
}

function get_format_name_by_type_stat($type)
{
	$formats = get_all_output_targets();
	return $formats[$type];
}

function get_report_num_parameter_stat($full_path_report)
{
	if (is_null($full_path_report) or empty($full_path_report) or $full_path_report=='-1')
	{
		$ret = -1;
	}
	else 
	{
		global $DB;
		$sql = "select count(rpms.id) as num_param
			from {f2_report_pentaho_param_map_stat} rpms, {f2_report_pentaho_stat} reps
			where lower(reps.full_path) = lower('".$full_path_report."') 
				and reps.attivo = 1 and reps.extra_param = 1 and reps.id = rpms.id_report
				and exists (select 1 from {f2_report_pentaho_param_stat} ps where ps.id = rpms.id_report_param)";
		$res = $DB->get_record_sql($sql);
		$ret = intval($res->num_param);
	}
	return $ret;
}
/* END AGGIUNTA FUNZIONI PER REPORT STATISTICI */

/* START AGGIUNTA FUNZIONI PER REPORT FORMAZIONE INDIVIDUALE */
function get_report_form_formind($userid,$next_page="get_report_form_ind_pentaho.php",$selected_report='-1',$formato='table/html;page-mode=page')
{
	global $USER;
	if (is_null($userid) or empty($userid)) $userid = $USER->id;
	$embeddable = is_embeddable();  // restituisce false
	if ($selected_report == "-1")
	{
		$select_names = get_report_names_select_box_formind($userid,$embeddable);
		if ($select_names !== '-1')
		{
			$select_targets = get_report_output_targets_select_box();
			if ($embeddable == true)
			{
				$result_target = "results";
				$test_iframe = '<div id="iframe_div" hidden="hidden"><iframe id="results" name="results" seamless="seamless" width="100%" height="1000"></iframe></div>';
			}
			else
			{
				$result_target = "_blank";
				$test_iframe = '';
			}
			$submit_lbl = get_string('report_genera','block_f2_report');
			$test_form = '<form action="'.$next_page.'"method="post" target="'.$result_target.'">'
						.$select_names.$select_targets.'
  			<input type="submit" value=" '.$submit_lbl.' " onclick="show_div(\'iframe_div\')"></form><br/><br/>';
			$return = $test_form.$test_iframe;
			return $return;
		}
		else return get_string('no_report_pentaho_available','block_f2_report');
	}
	else // form per recuperare i parametri del report scelto in precedenza
	{
		$params = get_report_parameters_formind($selected_report);
		prepare_page_formind($embeddable);
		$mform = new report_parametri_form($next_page,array('post_values'=>$params,'full_path_report'=>$selected_report,'output-target'=>$formato));
		$mform->display();
	}
}

function get_report_names_select_box_formind($userid,$embeddable)
{
	$report_names = get_all_report_names_formind($userid);
	if(count($report_names) > 0)
	{
		if ($embeddable) $onChange = ' onChange=hide_div(\'iframe_div\'); ';
		else $onChange = '';
		$select_start = '  '.get_string('report_nome','block_f2_report').': <select name="full_path_report" id="full_path_report" value="report_nome" '.$onChange.'> ';
		$select_end = '</select>';
		$options = '';
		foreach ($report_names as $n)
		{
			$options .= '<option value="'.$n->full_path.'">'.$n->nome.'</option>';
		}
		$select_targets = $select_start.$options.$select_end;
		return $select_targets;
	}
	else return '-1';
}

function get_all_report_names_formind($userid)
{
	global $DB,$USER;
	if (is_null($userid) or empty($userid)) $userid = $USER->id;
	if (is_siteadmin($userid)) $sql_user_role = ' ';
	else 
	{
		$sql_user_role = " and exists (select 1 
				from {f2_report_pentaho_role_map_formind} reprmapfi, {role_assignments} ra  
				where ra.userid = ".$userid." and reprmapfi.id_report = repfi.id 
						and reprmapfi.id_role=ra.roleid) ";
	}
	$sql_base = "SELECT repfi.* FROM mdl_f2_report_pentaho_formind repfi where repfi.attivo = 1 ";
	$order_by = " order by repfi.nome ";
	$sql = $sql_base.$sql_user_role.$order_by;
	$report_names = $DB->get_records_sql($sql);
	return $report_names;
}

function get_report_parameters_formind($full_path_report)
{
	if (is_null($full_path_report) or empty($full_path_report) or $full_path_report=='-1')
	{
		$ret = new stdClass;
	}
	else
	{
		global $DB;
		$sql = "select pfi.* 
			from {f2_report_pentaho_param_map_formind} rpmfi, {f2_report_pentaho_formind} repfi, {f2_report_pentaho_param_formind} pfi
			where lower(repfi.full_path) = lower('".$full_path_report."')
				and repfi.attivo = 1 and repfi.extra_param = 1 and repfi.id = rpmfi.id_report
				and pfi.id = rpmfi.id_report_param
				order by pfi.nome";
		$ret = $DB->get_records_sql($sql);
	}
	return $ret;
}

function prepare_page_formind($embeddable)
{
	global $PAGE,$OUTPUT,$USER;
	require_once '../../config.php';

	require_login();
	$blockid = get_block_id(get_string('pluginname_db','block_f2_report'));
	$context = get_context_instance(CONTEXT_BLOCK, $blockid);

	$PAGE->set_context($context);
	// inizio import per generazione albero //
	$PAGE->requires->js('/f2_lib/jquery/jquery-1.7.1.min.js',true);
	$PAGE->requires->js('/f2_lib/jquery/jquery-ui.min.js',true);
	$PAGE->requires->js('/f2_lib/jquery/jquery.cookie.js',true);
	$PAGE->requires->js('/f2_lib/jquery/jquery.dynatree.js',true);
	$PAGE->requires->css('/f2_lib/jquery/css/skin/ui.dynatree.css',true);
	$PAGE->requires->js('/f2_lib/jquery/jquery.blockUI.js',true);
	// fine import per generazione albero //
	$PAGE->requires->js(new moodle_url('lib_report.js'),true);
	$PAGE->requires->js(new moodle_url('lib_form.js'),true);
	
	$blockname = get_string('pluginname', 'block_f2_report');
	$PAGE->set_pagelayout('standard');
	$PAGE->set_url('/blocks/f2_report/prenotazioni.php');
	$PAGE->set_title(get_string('report_pentaho', 'block_f2_report'));
	$PAGE->settingsnav;
	
	if (!$embeddable)
	{
		$userid       = optional_param('userid', 0, PARAM_INT);
		$full_path_report   = optional_param('full_path_report', '-1', PARAM_TEXT);
		$output_target     = optional_param('output_target_select', 'table/html;page-mode=page', PARAM_TEXT);
		
		if($userid==0) $userid=intval($USER->id);
		else if($userid!=0 && has_capability('block/f2_report:viewreport', $context) && validate_own_dipendente($userid)) $userid=$userid;
		else die();
		
		$userdata = get_user_data($userid);
		$objsettore = get_user_organisation($userid);
		
		$settore_id = $objsettore[0];
		$settore_nome = is_null($objsettore[1]) ? 'n.d.' : $objsettore[1];
		
		// TABELLA DATI ANAGRAFICI
		$table = new html_table();
		$table->align = array('right', 'left');
		$table->data = array(
				array('Cognome Nome ','<b>'.$userdata->lastname.' '.$userdata->firstname.'</b>'),
				array('Matricola',''.$userdata->idnumber.''),
				array('Categoria',''.$userdata->category.''),
				array('Direzione / Ente',''.$direzione_nome.''),
				array('Settore',''.$settore_nome.'')
		);
		
		echo $OUTPUT->header();
		echo $OUTPUT->heading(get_string('report_pentaho', 'block_f2_report'));
		echo $OUTPUT->box_start();
		
		echo html_writer::table($table);
		
		$table = new html_table();
		$table->align = array('right', 'left');
		$table->data = array(
				array(get_string('report_nome', 'block_f2_report'),'<b>'.get_report_name_by_full_path_formind($full_path_report).'</b>'),
				array(get_string('report_formato', 'block_f2_report'),''.get_format_name_by_type_formind($output_target)),
		);
		
		echo html_writer::table($table);
		echo $OUTPUT->box_end();
	}
	else 
	{
		echo $OUTPUT->header();
		echo $OUTPUT->box_start();
		echo $OUTPUT->box_end();
	}
}

function get_report_name_by_full_path_formind($full_path)
{
	if (is_null($full_path) or empty($full_path)) return '';
	else
	{
		global $DB;
		$sql_base = "SELECT distinct repfi.nome FROM mdl_f2_report_pentaho_formind repfi 
				where repfi.attivo = 1 and repfi.full_path = '".$full_path."' ";
		$ret = $DB->get_record_sql($sql_base);
		return $ret->nome;
	}
}

function get_format_name_by_type_formind($type)
{
	$formats = get_all_output_targets();
	return $formats[$type];
}

function get_report_num_parameter_formind($full_path_report)
{
	if (is_null($full_path_report) or empty($full_path_report) or $full_path_report=='-1')
	{
		$ret = -1;
	}
	else 
	{
		global $DB;
		$sql = "select count(rpmfi.id) as num_param
			from {f2_report_pentaho_param_map_formind} rpmfi, {f2_report_pentaho_formind} repfi
			where lower(repfi.full_path) = lower('".$full_path_report."') 
				and repfi.attivo = 1 and repfi.extra_param = 1 and repfi.id = rpmfi.id_report
				and exists (select 1 from {f2_report_pentaho_param_formind} pfi where pfi.id = rpmfi.id_report_param)";
		$res = $DB->get_record_sql($sql);
		$ret = intval($res->num_param);
	}
	return $ret;
}
/* END AGGIUNTA FUNZIONI PER REPORT FORMAZIONE INDIVIDUALE */

/* START AGGIUNTA FUNZIONI PER REPORT QUESTIONARI DI GRADIMENTO */
function get_report_form_questionari($userid,$next_page="get_report_questionari_pentaho.php",$selected_report='-1',$formato='table/html;page-mode=page')
{
	global $USER;
	if (is_null($userid) or empty($userid)) $userid = $USER->id;
	$embeddable = is_embeddable();  // restituisce false
	if ($selected_report == "-1")
	{
		$select_names = get_report_names_select_box_questionari($userid,$embeddable);
		if ($select_names !== '-1')
		{
			$select_targets = get_report_output_targets_select_box();
			if ($embeddable == true)
			{
				$result_target = "results";
				$test_iframe = '<div id="iframe_div" hidden="hidden"><iframe id="results" name="results" seamless="seamless" width="100%" height="1000"></iframe></div>';
			}
			else
			{
				$result_target = "_blank";
				$test_iframe = '';
			}
			$submit_lbl = get_string('report_genera','block_f2_report');
			$test_form = '<form action="'.$next_page.'"method="post" target="'.$result_target.'">'
						.$select_names.$select_targets.'
  			<input type="submit" value=" '.$submit_lbl.' " onclick="show_div(\'iframe_div\')"></form><br/><br/>';
			$return = $test_form.$test_iframe;
			return $return;
		}
		else return get_string('no_report_pentaho_available','block_f2_report');
	}
	else // form per recuperare i parametri del report scelto in precedenza
	{
		$params = get_report_parameters_questionari($selected_report);
		prepare_page_questionari($embeddable);
		$mform = new report_parametri_form($next_page,array('post_values'=>$params,'full_path_report'=>$selected_report,'output-target'=>$formato));
		$mform->display();
	}
}

function get_report_names_select_box_questionari($userid,$embeddable)
{
	$report_names = get_all_report_names_questionari($userid);
	if(count($report_names) > 0)
	{
		if ($embeddable) $onChange = ' onChange=hide_div(\'iframe_div\'); ';
		else $onChange = '';
		$select_start = '  '.get_string('report_nome','block_f2_report').': <select name="full_path_report" id="full_path_report" value="report_nome" '.$onChange.'> ';
		$select_end = '</select>';
		$options = '';
		foreach ($report_names as $n)
		{
			$options .= '<option value="'.$n->full_path.'">'.$n->nome.'</option>';
		}
		$select_targets = $select_start.$options.$select_end;
		return $select_targets;
	}
	else return '-1';
}

function get_all_report_names_questionari($userid)
{
	global $DB,$USER;
	if (is_null($userid) or empty($userid)) $userid = $USER->id;
	if (is_siteadmin($userid)) $sql_user_role = ' ';
	else 
	{
		$sql_user_role = " and exists (select 1 
				from {f2_report_pentaho_role_map_questionari} reprmapqg, {role_assignments} ra  
				where ra.userid = ".$userid." and reprmapqg.id_report = repqg.id 
						and reprmapqg.id_role=ra.roleid) ";
	}
	$sql_base = "SELECT repqg.* FROM mdl_f2_report_pentaho_questionari repqg where repqg.attivo = 1 ";
	$order_by = " order by repqg.nome ";
	$sql = $sql_base.$sql_user_role.$order_by;
	$report_names = $DB->get_records_sql($sql);
	return $report_names;
}

function get_report_parameters_questionari($full_path_report)
{
	if (is_null($full_path_report) or empty($full_path_report) or $full_path_report=='-1')
	{
		$ret = new stdClass;
	}
	else
	{
		global $DB;
		$sql = "select pqg.* 
			from {f2_report_pentaho_param_map_questionari} rpmqg, {f2_report_pentaho_questionari} repqg, {f2_report_pentaho_param_questionari} pqg
			where lower(repqg.full_path) = lower('".$full_path_report."')
				and repqg.attivo = 1 and repqg.extra_param = 1 and repqg.id = rpmqg.id_report
				and pqg.id = rpmqg.id_report_param
				order by pqg.nome";
		$ret = $DB->get_records_sql($sql);
	}
	return $ret;
}

function prepare_page_questionari($embeddable)
{
	global $PAGE,$OUTPUT,$USER;
	require_once '../../config.php';

	require_login();
	$blockid = get_block_id(get_string('pluginname_db','block_f2_report'));
	$context = get_context_instance(CONTEXT_BLOCK, $blockid);

	$PAGE->set_context($context);
	// inizio import per generazione albero //
	$PAGE->requires->js('/f2_lib/jquery/jquery-1.7.1.min.js',true);
	$PAGE->requires->js('/f2_lib/jquery/jquery-ui.min.js',true);
	$PAGE->requires->js('/f2_lib/jquery/jquery.cookie.js',true);
	$PAGE->requires->js('/f2_lib/jquery/jquery.dynatree.js',true);
	$PAGE->requires->css('/f2_lib/jquery/css/skin/ui.dynatree.css',true);
	$PAGE->requires->js('/f2_lib/jquery/jquery.blockUI.js',true);
	// fine import per generazione albero //
	$PAGE->requires->js(new moodle_url('lib_report.js'),true);
	$PAGE->requires->js(new moodle_url('lib_form.js'),true);
	
	$blockname = get_string('pluginname', 'block_f2_report');
	$PAGE->set_pagelayout('standard');
	$PAGE->set_url('/blocks/f2_report/prenotazioni.php');
	$PAGE->set_title(get_string('report_pentaho', 'block_f2_report'));
	$PAGE->settingsnav;
	
	if (!$embeddable)
	{
		$userid       = optional_param('userid', 0, PARAM_INT);
		$full_path_report   = optional_param('full_path_report', '-1', PARAM_TEXT);
		$output_target     = optional_param('output_target_select', 'table/html;page-mode=page', PARAM_TEXT);
		
		if($userid==0) $userid=intval($USER->id);
		else if($userid!=0 && has_capability('block/f2_report:viewreport', $context) && validate_own_dipendente($userid)) $userid=$userid;
		else die();
		
		$userdata = get_user_data($userid);
		$objsettore = get_user_organisation($userid);
		
		$settore_id = $objsettore[0];
		$settore_nome = is_null($objsettore[1]) ? 'n.d.' : $objsettore[1];
		
		// TABELLA DATI ANAGRAFICI
		$table = new html_table();
		$table->align = array('right', 'left');
		$table->data = array(
				array('Cognome Nome ','<b>'.$userdata->lastname.' '.$userdata->firstname.'</b>'),
				array('Matricola',''.$userdata->idnumber.''),
				array('Categoria',''.$userdata->category.''),
				array('Direzione / Ente',''.$direzione_nome.''),
				array('Settore',''.$settore_nome.'')
		);
		
		echo $OUTPUT->header();
		echo $OUTPUT->heading(get_string('report_pentaho', 'block_f2_report'));
		echo $OUTPUT->box_start();
		
		echo html_writer::table($table);
		
		$table = new html_table();
		$table->align = array('right', 'left');
		$table->data = array(
				array(get_string('report_nome', 'block_f2_report'),'<b>'.get_report_name_by_full_path_questionari($full_path_report).'</b>'),
				array(get_string('report_formato', 'block_f2_report'),''.get_format_name_by_type_questionari($output_target)),
		);
		
		echo html_writer::table($table);
		echo $OUTPUT->box_end();
	}
	else 
	{
		echo $OUTPUT->header();
		echo $OUTPUT->box_start();
		echo $OUTPUT->box_end();
	}
}

function get_report_name_by_full_path_questionari($full_path)
{
	if (is_null($full_path) or empty($full_path)) return '';
	else
	{
		global $DB;
		$sql_base = "SELECT distinct repqg.nome FROM mdl_f2_report_pentaho_questionari repqg 
				where repqg.attivo = 1 and repqg.full_path = '".$full_path."' ";
		$ret = $DB->get_record_sql($sql_base);
		return $ret->nome;
	}
}

function get_format_name_by_type_questionari($type)
{
	$formats = get_all_output_targets();
	return $formats[$type];
}

function get_report_num_parameter_questionari($full_path_report)
{
	if (is_null($full_path_report) or empty($full_path_report) or $full_path_report=='-1')
	{
		$ret = -1;
	}
	else 
	{
		global $DB;
		$sql = "select count(rpmqg.id) as num_param
			from {f2_report_pentaho_param_map_questionari} rpmqg, {f2_report_pentaho_questionari} repqg
			where lower(repqg.full_path) = lower('".$full_path_report."') 
				and repqg.attivo = 1 and repqg.extra_param = 1 and repqg.id = rpmqg.id_report
				and exists (select 1 from {f2_report_pentaho_param_questionari} pqg where pqg.id = rpmqg.id_report_param)";
		$res = $DB->get_record_sql($sql);
		$ret = intval($res->num_param);
	}
	return $ret;
}
/* END AGGIUNTA FUNZIONI PER REPORT QUESTIONARI DI GRADIMENTO */

/* START AGGIUNTA FUNZIONI PER REPORT PARTECIPAZIONI */
function get_report_form_partecipazione($userid,$next_page="get_report_partecipazione_pentaho.php",$selected_report='-1',$formato='table/html;page-mode=page')
{
	global $USER;
	if (is_null($userid) or empty($userid)) $userid = $USER->id;
	$embeddable = is_embeddable();  // restituisce false
	if ($selected_report == "-1")
	{
		$select_names = get_report_names_select_box_partecipazione($userid,$embeddable);
		if ($select_names !== '-1')
		{
			$select_targets = get_report_output_targets_select_box();
			if ($embeddable == true)
			{
				$result_target = "results";
				$test_iframe = '<div id="iframe_div" hidden="hidden"><iframe id="results" name="results" seamless="seamless" width="100%" height="1000"></iframe></div>';
			}
			else
			{
				$result_target = "_blank";
				$test_iframe = '';
			}
			$submit_lbl = get_string('report_genera','block_f2_report');
			$test_form = '<form action="'.$next_page.'"method="post" target="'.$result_target.'">'
						.$select_names.$select_targets.'
  			<input type="submit" value=" '.$submit_lbl.' " onclick="show_div(\'iframe_div\')"></form><br/><br/>';
			$return = $test_form.$test_iframe;
			return $return;
		}
		else return get_string('no_report_pentaho_available','block_f2_report');
	}
	else // form per recuperare i parametri del report scelto in precedenza
	{
		$params = get_report_parameters_partecipazione($selected_report);
		prepare_page_partecipazione($embeddable);
		$mform = new report_parametri_form($next_page,array('post_values'=>$params,'full_path_report'=>$selected_report,'output-target'=>$formato));
		$mform->display();
	}
}

function get_report_names_select_box_partecipazione($userid,$embeddable)
{
	$report_names = get_all_report_names_partecipazione($userid);
	if(count($report_names) > 0)
	{
		if ($embeddable) $onChange = ' onChange=hide_div(\'iframe_div\'); ';
		else $onChange = '';
		$select_start = '  '.get_string('report_nome','block_f2_report').': <select name="full_path_report" id="full_path_report" value="report_nome" '.$onChange.'> ';
		$select_end = '</select>';
		$options = '';
		foreach ($report_names as $n)
		{
			$options .= '<option value="'.$n->full_path.'">'.$n->nome.'</option>';
		}
		$select_targets = $select_start.$options.$select_end;
		return $select_targets;
	}
	else return '-1';
}

function get_all_report_names_partecipazione($userid)
{
	global $DB,$USER;
	if (is_null($userid) or empty($userid)) $userid = $USER->id;
	if (is_siteadmin($userid)) $sql_user_role = ' ';
	else 
	{
		$sql_user_role = " and exists (select 1 
				from {f2_report_pentaho_role_map_partecipazione} reprmapqg, {role_assignments} ra  
				where ra.userid = ".$userid." and reprmapqg.id_report = repqg.id 
						and reprmapqg.id_role=ra.roleid) ";
	}
	$sql_base = "SELECT repqg.* FROM mdl_f2_report_pentaho_partecipazione repqg where repqg.attivo = 1 ";
	$order_by = " order by repqg.nome ";
	$sql = $sql_base.$sql_user_role.$order_by;
	$report_names = $DB->get_records_sql($sql);
	return $report_names;
}

function get_report_parameters_partecipazione($full_path_report)
{
	if (is_null($full_path_report) or empty($full_path_report) or $full_path_report=='-1')
	{
		$ret = new stdClass;
	}
	else
	{
		global $DB;
		$sql = "select pqg.* 
			from {f2_report_pentaho_param_map_partecipazione} rpmqg, {f2_report_pentaho_partecipazione} repqg, {f2_report_pentaho_param_partecipazione} pqg
			where lower(repqg.full_path) = lower('".$full_path_report."')
				and repqg.attivo = 1 and repqg.extra_param = 1 and repqg.id = rpmqg.id_report
				and pqg.id = rpmqg.id_report_param
				order by pqg.nome";
		$ret = $DB->get_records_sql($sql);
	}
	return $ret;
}

function prepare_page_partecipazione($embeddable)
{
	global $PAGE,$OUTPUT,$USER;
	require_once '../../config.php';

	require_login();
	$blockid = get_block_id(get_string('pluginname_db','block_f2_report'));
	$context = get_context_instance(CONTEXT_BLOCK, $blockid);

	$PAGE->set_context($context);
	// inizio import per generazione albero //
	$PAGE->requires->js('/f2_lib/jquery/jquery-1.7.1.min.js',true);
	$PAGE->requires->js('/f2_lib/jquery/jquery-ui.min.js',true);
	$PAGE->requires->js('/f2_lib/jquery/jquery.cookie.js',true);
	$PAGE->requires->js('/f2_lib/jquery/jquery.dynatree.js',true);
	$PAGE->requires->css('/f2_lib/jquery/css/skin/ui.dynatree.css',true);
	$PAGE->requires->js('/f2_lib/jquery/jquery.blockUI.js',true);
	// fine import per generazione albero //
	$PAGE->requires->js(new moodle_url('lib_report.js'),true);
	$PAGE->requires->js(new moodle_url('lib_form.js'),true);
	
	$blockname = get_string('pluginname', 'block_f2_report');
	$PAGE->set_pagelayout('standard');
	$PAGE->set_url('/blocks/f2_report/prenotazioni.php');
	$PAGE->set_title(get_string('report_pentaho', 'block_f2_report'));
	$PAGE->settingsnav;
	
	if (!$embeddable)
	{
		$userid       = optional_param('userid', 0, PARAM_INT);
		$full_path_report   = optional_param('full_path_report', '-1', PARAM_TEXT);
		$output_target     = optional_param('output_target_select', 'table/html;page-mode=page', PARAM_TEXT);
		
		if($userid==0) $userid=intval($USER->id);
		else if($userid!=0 && has_capability('block/f2_report:viewreport', $context) && validate_own_dipendente($userid)) $userid=$userid;
		else die();
		
		$userdata = get_user_data($userid);
		$objsettore = get_user_organisation($userid);
		
		$settore_id = $objsettore[0];
		$settore_nome = is_null($objsettore[1]) ? 'n.d.' : $objsettore[1];
		
		// TABELLA DATI ANAGRAFICI
		$table = new html_table();
		$table->align = array('right', 'left');
		$table->data = array(
				array('Cognome Nome ','<b>'.$userdata->lastname.' '.$userdata->firstname.'</b>'),
				array('Matricola',''.$userdata->idnumber.''),
				array('Categoria',''.$userdata->category.''),
				array('Direzione / Ente',''.$direzione_nome.''),
				array('Settore',''.$settore_nome.'')
		);
		
		echo $OUTPUT->header();
		echo $OUTPUT->heading(get_string('report_pentaho', 'block_f2_report'));
		echo $OUTPUT->box_start();
		
		echo html_writer::table($table);
		
		$table = new html_table();
		$table->align = array('right', 'left');
		$table->data = array(
				array(get_string('report_nome', 'block_f2_report'),'<b>'.get_report_name_by_full_path_partecipazione($full_path_report).'</b>'),
				array(get_string('report_formato', 'block_f2_report'),''.get_format_name_by_type_partecipazione($output_target)),
		);
		
		echo html_writer::table($table);
		echo $OUTPUT->box_end();
	}
	else 
	{
		echo $OUTPUT->header();
		echo $OUTPUT->box_start();
		echo $OUTPUT->box_end();
	}
}

function get_report_name_by_full_path_partecipazione($full_path)
{
	if (is_null($full_path) or empty($full_path)) return '';
	else
	{
		global $DB;
		$sql_base = "SELECT distinct repqg.nome FROM mdl_f2_report_pentaho_partecipazione repqg 
				where repqg.attivo = 1 and repqg.full_path = '".$full_path."' ";
		$ret = $DB->get_record_sql($sql_base);
		return $ret->nome;
	}
}

function get_format_name_by_type_partecipazione($type)
{
	$formats = get_all_output_targets();
	return $formats[$type];
}

function get_report_num_parameter_partecipazione($full_path_report)
{
	if (is_null($full_path_report) or empty($full_path_report) or $full_path_report=='-1')
	{
		$ret = -1;
	}
	else 
	{
		global $DB;
		$sql = "select count(rpmqg.id) as num_param
			from {f2_report_pentaho_param_map_partecipazione} rpmqg, {f2_report_pentaho_partecipazione} repqg
			where lower(repqg.full_path) = lower('".$full_path_report."') 
				and repqg.attivo = 1 and repqg.extra_param = 1 and repqg.id = rpmqg.id_report
				and exists (select 1 from {f2_report_pentaho_param_partecipazione} pqg where pqg.id = rpmqg.id_report_param)";
		$res = $DB->get_record_sql($sql);
		$ret = intval($res->num_param);
	}
	return $ret;
}
/* END AGGIUNTA FUNZIONI PER REPORT PARTECIPAZIONI */