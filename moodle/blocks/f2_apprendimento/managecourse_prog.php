<?php

//$Id: managecourse_prog.php 1234 2013-12-11 12:09:34Z l.moretto $
global $CFG, $USER, $PAGE, $OUTPUT, $SITE;

require_once '../../config.php';
require_once($CFG->dirroot.'/f2_lib/core.php');
require_once($CFG->dirroot.'/f2_lib/management.php');
require_once($CFG->dirroot.'/f2_lib/report.php');
require_once($CFG->dirroot.'/f2_lib/constants.php');

require_login();
//$context = get_context_instance(CONTEXT_SYSTEM);
$context = context_system::instance();
$blockid = get_block_id(get_string('pluginname_db','block_f2_apprendimento'));
//require_capability('block/f2_apprendimento:viewgestionecorsi', get_context_instance(CONTEXT_BLOCK, $blockid));
require_capability('block/f2_apprendimento:viewgestionecorsi', context_block::instance($blockid));

$page     = optional_param('page', 0, PARAM_INT);
$perpage  = optional_param('perpage', 10, PARAM_INT);
$column   = optional_param('column', 'lastname', PARAM_TEXT);
$sort     = optional_param('sort', 'ASC', PARAM_TEXT);

$pagename = get_string('managecourse_prog', 'block_f2_apprendimento');
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/f2_apprendimento/managecourse_prog.php');
$PAGE->set_title(get_string('managecourse_prog', 'block_f2_apprendimento'));
$PAGE->settingsnav;
$PAGE->navbar->add(get_string('managecourse_prog', 'block_f2_apprendimento'));
$PAGE->set_heading($SITE->shortname.': '.$pagename);
$PAGE->requires->js('/f2_lib/jquery/multiselect/jquery-1.8.2.js');

echo $OUTPUT->header();

$currenttab = 'managecourse_prog';
require('tabs_managecourse.php');

echo $OUTPUT->heading(get_string('managecourse_prog', 'block_f2_apprendimento'));

$isDir = isReferenteDiDirezione($USER->id);
$dirParam = ($isDir) ? 1 : 0;

echo '<p>';
echo html_writer::start_tag('form', array('action' => $CFG->wwwroot.'/blocks/f2_apprendimento/export_managecourse_seats.php?coursetype='.C_PRO.'&dir='.$dirParam, 'class' => 'export_excel', 'method' => 'post'));
echo html_writer::empty_tag('input', array('type' => 'button', 'class' => 'ico_xls btn', 'value' => 'Esporta dati corsi', 'onClick' => 'javascript:this.form.submit();'));
echo html_writer::end_tag('form');
echo '</p>';

echo $OUTPUT->box_start();

$filtercourse     = optional_param('filtercourse', '', PARAM_TEXT);
$filteryear       = optional_param('filteryear', date("Y"), PARAM_TEXT);
$filtercourse_arr = array();
$filteryear_param = -1;
if ($filtercourse !== '')
{
	$filtercourse_arr['idnumber'] = mysql_escape_string($filtercourse);
}
if ($filteryear !== '')
{
	//$filteryear_param = intval(mysql_escape_string($filteryear));
	$filteryear_param = intval($filteryear);
}

$viewable_years = array(
                        get_anno_formativo_corrente()+1,
                        get_anno_formativo_corrente(),
                        get_anno_formativo_corrente()-1,
                        get_anno_formativo_corrente()-2,
                        get_anno_formativo_corrente()-3
                       );
$managecourse = get_managable_course(C_PRO, $viewable_years, $filtercourse_arr, $filteryear_param);
$courses = json_course_parsing($managecourse, C_PRO);

?>
<script type="text/javascript">
	// gallery-treeble raws data
	var data = <?php echo $courses ?>;
</script>
<div id="coursetype" style="visibility:hidden"><?php echo C_PRO ?></div>
<div id="usertype" style="visibility:hidden"><?php echo $isDir ?></div>

<body class="yui3-skin-sam">
<form id="mformcourse1" name="mformcourse1" action="" method="post">
<b>Codice corso:</b>
<input type="text" id="filtercourse" name="filtercourse" value="<?php echo $filtercourse ?>">
<b>Anno:</b> 
	<select id ="filteryear" name ="filteryear">
		<option value="<?php echo '' ?>"><?php echo 'Tutti' ?></option>
		<option value="<?php echo $viewable_years[0] ?>" <?php if($viewable_years[0] == intval($filteryear)) { echo 'selected'; } else echo ''; ?>><?php echo $viewable_years[0]?></option>
		<option value="<?php echo $viewable_years[1] ?>" <?php if($viewable_years[1] == intval($filteryear)) { echo 'selected'; } else echo ''; ?>><?php echo $viewable_years[1]?></option>
		<option value="<?php echo $viewable_years[2] ?>" <?php if($viewable_years[2] == intval($filteryear)) { echo 'selected'; } else echo ''; ?>><?php echo $viewable_years[2]?></option>
		<option value="<?php echo $viewable_years[3] ?>" <?php if($viewable_years[3] == intval($filteryear)) { echo 'selected'; } else echo ''; ?>><?php echo $viewable_years[3]?></option>
		<option value="<?php echo $viewable_years[4] ?>" <?php if($viewable_years[4] == intval($filteryear)) { echo 'selected'; } else echo ''; ?>><?php echo $viewable_years[4]?></option>
	</select>
<input type="submit" name="filtercoursesubmit" id="filtercoursesubmit" value="Cerca">
</form>
<?php
/*
  <b>Codice corso:</b> <input type="text" id="filter" onkeyup="yuiCourseFilter('treeble', this.value)" />
  <b>Anno:</b> 
    <select onchange="yuiAnnoFilter('treeble', this.value)">
        <option value="<?php echo '' ?>"><?php echo 'Tutti' ?></option>
        <option value="//<?php echo $viewable_years[0] ?>"><?php echo $viewable_years[0]?></option>
        <option value="//<?php echo $viewable_years[1] ?>"><?php echo $viewable_years[1]?></option>
        <option value="//<?php echo $viewable_years[2] ?>"><?php echo $viewable_years[2]?></option>
    </select>

<script type="text/javascript">
	yuiAnnoFilter = function(tableDivId, filter) {
	    //custom jQuery function defines case-insensitive fn:Contains, use default fn:contains for case-sensitive search
		if (filter != '') {
		    jQuery.expr[':'].Contains = function(a,i,m){
		      return jQuery(a).text().toUpperCase().indexOf(m[3].toUpperCase())>=0;
		    };
		    $("#" + tableDivId + " .yui3-datatable-data").find('tr').hide();
		    $("#" + tableDivId + " .yui3-datatable-data").find('td.yui3-datatable-col-course_year:Contains("' + filter + '")').parents('tr').show();
		} else {
		  	$("#" + tableDivId + " .yui3-datatable-data").find('tr').show();
		}
	}

	yuiCourseFilter = function(tableDivId, filter) {
	    //custom jQuery function defines case-insensitive fn:Contains, use default fn:contains for case-sensitive search
		if (filter != '') {
		    jQuery.expr[':'].Contains = function(a,i,m){
		       return jQuery(a).text().toUpperCase().indexOf(m[3].toUpperCase())>=0;
		     };
		     $("#" + tableDivId + " .yui3-datatable-data").find('tr').hide();
		     $("#" + tableDivId + " .yui3-datatable-data").find('td.yui3-datatable-col-course_code:Contains("' + filter + '")').parents('tr').show();	
		} else {
			$("#" + tableDivId + " .yui3-datatable-data").find('tr').show();
		} 
	}
</script>
*/
?>
<div id="pg"></div>
<div id="treeble"></div>
<?php
$PAGE->requires->js('/blocks/f2_apprendimento/js/datasource_getcourse.js');
echo $OUTPUT->box_end();
?>
</body>
<?php 
echo $OUTPUT->footer();
?>
