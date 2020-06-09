<?php
require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/f2_lib/core.php');

class remove_all_roles_form extends moodleform {
    // Define the form
    function definition () {
        global $DB;

$str = <<<'EOF'
<script type="text/javascript">
//<![CDATA[

function confirmSubmit(testo)
{
var agree=confirm(testo);
if (agree)
	return true ;
else
	return false ;
}

//]]>
</script>
EOF;
echo $str;

		$mform =& $this->_form;
		$userid = $this->_customdata['userid'];
		
		$action= '
			if(document.getElementById(\'id_flag_azione\').value == \'S\'){
							document.getElementById(\'div_tab_autosearch\').style.display=\'\';
			} else {
							document.getElementById(\'div_tab_autosearch\').style.display=\'none\';
						}';
		$attribute = array('onchange'=>$action);
		$mform->addElement('select', 'flag_azione', get_string('flag_azione', 'local_f2_domains'), from_obj_to_array_select($this->get_flag_azione(),array('id','descrizione')), $attribute);
		$mform->addHelpButton('flag_azione',  'flag_azione','local_f2_domains');
		$mform->addElement('html', '<div id="div_tab_autosearch" style=\'display:none\'>');
		$mform->addElement('text','utente_sostitutivo', get_string('utente_sostitutivo','local_f2_domains'),'maxlength="254" size="50" readonly');
		$mform->addHelpButton('utente_sostitutivo',  'utente_sostitutivo','local_f2_domains');
		
		$mform->addElement('hidden', 'id_utente_sostituto');
		$mform->setType('id_utente_sostituto', PARAM_INT);
		$mform->setDefault('id_utente_sostitutivo', 0);

		$users = $this->get_available_users($userid);
		
		$table = new html_table();
		$table->width = '100%';
		$table->id = 'id_tab_autosearch';
		
		if ($users) {
			$table->align = array ('center','center','center','center');
			$table->head = array('Nominativo','Id number','Dominio di visibilità');

			foreach ($users as $user) {
					$table->data[]=array('<a href="javascript:void(0)" 
						onclick="
							document.getElementById(\'id_utente_sostitutivo\').value=\''.fullname($user, true).'\'; 
							document.getElementsByName(\'id_utente_sostituto\')[0].value='.$user->id.';
								" >'.fullname($user, true).'</a>', $user->idnumber, $user->viewableorganisation);
			}
		} else {
			$table->align = array ('center');
			$table->head = array('Nominativo');

			$table->data[]=array('Nessun utente sostituto disponibile');
		}
			
		$mform->addElement('html', html_writer::table($table));
		$mform->addElement('html', '</div>');
		
		echo '<center>';
		$buttonarray=array();
		$buttonarray[] = &$mform->createElement('submit', 'update', get_string('update'), 'onClick="return confirmSubmit(\'Sei sicuro di voler proseguire?\')"');
		$buttonarray[] = &$mform->createElement('cancel');
		$mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
		$mform->closeHeaderBefore('buttonar');
		echo '</center>';
	}

    function definition_after_data() {
        $mform =& $this->_form;
        // do nothing
    }

    function freezeForm() {
        $mform =& $this->_form;

        // Freeze values
        $mform->hardFreezeAllVisibleExcept(array());

        // Hide elements with no values
        foreach (array_keys($mform->_elements) as $key) {
            $element =& $mform->_elements[$key];

            // Check static elements differently
            if ($element->getType() == 'static') {
                // Check if it is a js selector
                if (substr($element->getName(), -8) == 'selector') {
                    // Get id element
                    $elementid = $mform->getElement(substr($element->getName(), 0, -8).'id');

                    if (!$elementid || !$elementid->getValue()) {
                        $mform->removeElement($element->getName());
                    }

                    continue;
                }
            }

            // Get element value
            $value = $element->getValue();

            // Check groups
            // (matches date groups and action buttons)
            if (is_array($value)) {
                // If values are strings (e.g. buttons, or date format string), remove
                foreach ($value as $k => $v) {
                    if (!is_numeric($v)) {
                        $mform->removeElement($element->getName());
                        break;
                    }
                }
            }
            // Otherwise check if empty
            elseif (!$value) {
                $mform->removeElement($element->getName());
            }
        }
    }

    function validation() {
        $mform =& $this->_form;
        $result = '';

        return $result;
    }
    
    /**
    * restituisce flag_azione per costruire la combobox
    * @var $id id di flag_azione
    * @return stdClass:  flag_azione
    */
   protected function get_flag_azione($id=NULL)
   {
           $list_fds = array();
           $obj0 = new stdClass();
           $obj0->id = '';
           $obj0->descrizione = 'Scegli';
           $obj1 = new stdClass();
           $obj1->id = 'R';
           $obj1->descrizione = 'Rimuovi';
           $obj2 = new stdClass();
           $obj2->id = 'S';
           $obj2->descrizione = 'Sostituisci';
           if(is_null($id)){	
                   $list_fds[$obj0->id] = $obj0;
                   $list_fds[$obj1->id] = $obj1;
                   $list_fds[$obj2->id] = $obj2;
           }
           else if($id=='R')
                   $list_fds[$obj1->id] = $obj1;
           else if($id=='S')
                   $list_fds[$obj2->id] = $obj2;

           return $list_fds;
   }
   
   // restituisce gli utenti che possono sostituire i ruoli per tutti i corsi per l'utente passato come parametro
   protected function get_available_users ($userid) {
       global $DB, $CFG;
       
       list($dominio_visibilita, $name)  = get_user_viewable_organisation($userid);

       $sql   = "SELECT u.id, u.lastname, u.firstname, u.idnumber, org.fullname as viewableorganisation FROM {user} u
                    JOIN {$CFG->prefix}org_assignment oa ON u.id=oa.userid
                    JOIN {$CFG->prefix}org org ON org.id=oa.viewableorganisationid 
                    WHERE org.depthid > 1 AND org.depthid < 4 AND org.id = $dominio_visibilita AND u.id != $userid
                    ORDER BY lastname ASC, firstname ASC";

       $availableusers = $DB->get_records_sql($sql);
       return $availableusers;
    }
}
