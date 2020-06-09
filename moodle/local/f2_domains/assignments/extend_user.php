<?php

/*
 * $Id: extend_user.php 1225 2013-12-05 11:46:59Z l.moretto $
 */

/**
 * Description of extend_user
 *
 * @author L.Moretto
 */
class extend_user {
    protected $_userid = -1;
    
    public function __construct($userid=-1) {
		$this->userid = $userid;
	}
    
    public function print_tab_edit_user($currenttab) {
        global $PAGE, $COURSE;
        $toprow = array();
        $inactive = array();
        
        $taburl = new moodle_url('/user/editadvanced.php', array('id'=>$this->userid, 'course'=>$COURSE->id));
        array_push($toprow, new tabobject('user_tab', $taburl, get_string('user:anagrafica', 'local_f2_traduzioni')));
        
        $taburl = new moodle_url('/local/f2_domains/assignments/view.php', array('userid'=>$this->userid));
        array_push($toprow, new tabobject('dom_tab', $taburl, get_string('organisations', 'local_f2_domains')));
        $tabs = array($toprow);
        
        if($this->userid == -1)
            $inactive = array('dom_tab');
        
        print_tabs($tabs, $currenttab, $inactive);
    }
}

