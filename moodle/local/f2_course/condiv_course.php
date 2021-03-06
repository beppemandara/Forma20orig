<?php
/*
 * $Id: condiv_course.php 1177 2013-06-20 11:33:57Z d.lallo $
 */
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
 * Edit course settings
 *
 * @package    moodlecore
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/formslib.php');
require_once('extends_course.php');
require_once($CFG->dirroot.'/local/f2_support/lib.php');
require_once($CFG->dirroot.'/f2_lib/core.php');
require_once($CFG->dirroot.'/f2_lib/management.php');

$courseid        = required_param('courseid', PARAM_INT);       		  // course id
$saved        	 = optional_param('saved', false, PARAM_BOOL);
$addselect       = optional_param('addselect', 0, PARAM_TEXT);
$removeselect    = optional_param('removeselect', 0, PARAM_TEXT);		

global $PAGE,$DB,$OUTPUT;

$baseurl = new moodle_url($CFG->wwwroot.'/local/f2_course/condiv_course.php', array('courseid'=>$courseid));

$PAGE->set_pagelayout('admin');
$PAGE->set_url($baseurl);

// basic access control checks
if ($courseid) { // editing course
    if ($courseid == SITEID){
        // don't allow editing of  'site course' using this from
        print_error('cannoteditsiteform');
    }
    $course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
    require_login($course);
    $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
    require_capability('moodle/course:update', $coursecontext);
} else {
    require_login();
    print_error('per poter continuare devi compilare la scheda corso');
}

// Process incoming addselect
if (!empty($addselect) && confirm_sesskey()) {
    foreach ($addselect as $orgid) {
        // associazione ORG->CORSO e associazione RUOLO
        set_org_by_course_enroll($orgid,$courseid,'referentedirezione');
    }
}

// Process incoming removeselect
if (!empty($removeselect) && confirm_sesskey()) {
    foreach ($removeselect as $id_table) {
        // dissociazione RUOLO e dissociazione ORG->CORSO
        $orgid = $DB->get_field('f2_course_org_mapping', 'orgid', array('id'=>$id_table));
        remove_org_by_course_enroll($orgid,$courseid,'referentedirezione');
    }
}

// Print the form
$site = get_site();
$PAGE->navbar->add(get_string('custom_condiv_course','local_f2_course'));
$title = get_string('title_condiv_course','local_f2_course');
$title_div = get_string('title_div_condiv_course','local_f2_course');

$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->requires->js('/f2_lib/jquery/jquery-1.7.1.min.js',true);
$PAGE->requires->js('/local/f2_course/js/filter.js',true);
echo $OUTPUT->header();

$test = new extends_f2_course($courseid);
$test->print_tab_edit_course('condiv_course');

echo $OUTPUT->heading($title_div);

if ($saved) {
    echo $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
}
?>
<script type="text/javascript">
$(document).ready(function(){
    $('#search_add').bind("change keyup", function() {
        searchWord = $(this).val();
        $("#addselect option").remove();
        //$tmp = $('#addselect_hidden option');
        $('#addselect_hidden option').clone().appendTo($('#addselect'));
        if (searchWord.length >= 2) {
            $('#addselect option').each(function() {
                text = $(this).text();
                if (!text.match(RegExp(searchWord, 'i'))) {
                    $(this).remove();
                }
            });
        }
    });
    $('#search_rem').bind("change keyup", function() {
        searchWord = $(this).val();
        $("#removeselect option").remove();
        //$tmp = $('#addselect_hidden option');
        $('#removeselect_hidden option').clone().appendTo($('#removeselect'));
        if (searchWord.length >= 2) {
            $('#removeselect option').each(function() {
                text = $(this).text();
                if (!text.match(RegExp(searchWord, 'i'))) {
                    $(this).remove();
                }
            });
        }
    });
});
function svuota_rem(){
    $('#search_rem').val('');
    $('#removeselect_hidden option').clone().appendTo($('#removeselect'));
};
function svuota_add(){
    $('#search_add').val('');
    $('#addselect_hidden option').clone().appendTo($('#addselect'));
};

</script>
<input style="float: right; margin-top: -80px;" type="button" value="Verifica assegnazione referenti" onclick="location.href='<?php echo $CFG->wwwroot.'/enrol/otherusers.php?id='.$courseid; ?>';">

<form id="assignform" method="post" action="<?php echo $PAGE->url ?>">
  <div>
    <input type="hidden" name="sesskey" value="<?php echo sesskey() ?>" />
    <table summary="" class="generaltable generalbox boxaligncenter">
      <tr class="r0 ">
        <td id="existingcell" class="cell c0" style="width:47%;">
          <p>
            <label for="removeselect"><?php print_string('condiviso_con', 'local_f2_course'); ?></label>
          </p>
          <?php  $direzioni = get_tabid_org_by_course($courseid); ?>
          <select style="display:none" name="removeselect_hidden[]" size="20" id="removeselect_hidden" multiple="multiple">
              <?php 
              $direzioni_str = '';
              if (count($direzioni) == 0) {
                  echo '<option/>';
              } else {
                  foreach ($direzioni as $direzione) {
                      $direzioni_str .= "<option value=".
                                        $direzione->id.
                                        " title='".htmlentities($direzione->fullname, ENT_QUOTES, 'UTF-8').
                                        "' >".htmlentities($direzione->idnumber.
                                        " - ".$direzione->fullname, ENT_QUOTES, "UTF-8")."</option>";
                  }
	          print_r($direzioni_str);
              }
              ?>
          </select>
          <select style="width:100%;overflow-y: scroll;" name="removeselect[]" size="20" id="removeselect" multiple="multiple">
              <?php 
              $direzioni_str = '';
              if(count($direzioni) == 0) {
                  echo '<option/>';
              } else {
                  foreach ($direzioni as $direzione) {
                      $direzioni_str .= "<option value=".
                                        $direzione->id.
                                        " title='".htmlentities($direzione->fullname, ENT_QUOTES, 'UTF-8').
                                        "' >".htmlentities($direzione->idnumber.
                                        " - ".$direzione->fullname, ENT_QUOTES, "UTF-8")."</option>";
                  }
                  print_r($direzioni_str);
              }
              ?>
          </select>
          Cerca <input type="text" id="search_rem"><input type="button" style="margin-left: 10px;" value="svuota" onclick="svuota_rem()"/>  
        </td>
        <td class="cell c1" style="vertical-align: middle;" style="width:5%;" >
          <div id="addcontrols" style="margin-bottom: 20px;">
            <input name="add" id="add" type="submit" style="width: 90px;" value="<?php echo $OUTPUT->larrow().'&nbsp;'.get_string('add'); ?>" title="<?php print_string('add'); ?>" /><br />
          </div>
          <div id="removecontrols">
            <input name="remove" id="remove" type="submit" style="width: 90px;" value="<?php echo get_string('remove').'&nbsp;'.$OUTPUT->rarrow(); ?>" title="<?php print_string('remove'); ?>" />
          </div>
        </td>
        <td id="potentialcell" class="cell c2 lastcol" style="width:47%;">
          <p>
            <label for="addselect"><?php print_string('direz_dispo', 'local_f2_course'); ?></label>
          </p>
          <?php  
              $direzioni = get_direzioni_from_courseid($courseid);
          ?>
          <select style="display:none" name="addselect_hidden[]"  id="addselect_hidden" multiple="multiple">
         <?php 
         $direzioni_str="";
         if(count($direzioni)==0)
         	echo '<option/>';
         else{
	         foreach ($direzioni as $direzione){
	         	$direzioni_str .="<option value=".$direzione->id." title='".htmlentities($direzione->fullname, ENT_QUOTES, 'UTF-8')."' >".htmlentities($direzione->idnumber." - ".$direzione->fullname, ENT_QUOTES, "UTF-8")."</option>";
	         }
	         print_r($direzioni_str);
         }
          ?>
          </select>
          
          <select style="width:100%;overflow-y: scroll;" name="addselect[]" size="20" id="addselect" multiple="multiple">
          <?php 
         $direzioni = get_direzioni_from_courseid($courseid);
         $direzioni_str="";
         if(count($direzioni)==0)
         	echo '<option/>';
         else{
	         foreach ($direzioni as $direzione){
	         	$direzioni_str .="<option value=".$direzione->id." title='".htmlentities($direzione->fullname, ENT_QUOTES, 'UTF-8')."' >".htmlentities($direzione->idnumber." - ".$direzione->fullname, ENT_QUOTES, "UTF-8")."</option>";
	         }
	         print_r($direzioni_str);
         }
          ?>
          </select>
          Cerca <input type="text" id="search_add"><input type="button" style="margin-left: 10px;" value="svuota" onclick="svuota_add()"/>
      </td>
    </tr>
  </table>
</div></form>
<?php

echo $OUTPUT->footer();

