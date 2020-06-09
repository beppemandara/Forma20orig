<?php
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

defined('MOODLE_INTERNAL') || die();

require_once("{$CFG->libdir}/formslib.php");

class formindbudget_form extends moodleform {

    public function definition() {
        global $CFG;
        $mform =& $this->_form;
        // Add group for text areas.
        $mform->addElement('header', 'displayinfo', get_string('headertext', 'block_formindbudget'), null, false);

        $year = $this->_customdata['annoincorso'];
        $mform->addElement('hidden', 'year', $year);
        $mform->addElement('static', 'str_year', get_string('year'), $year);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }
}
