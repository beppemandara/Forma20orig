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
require_once("lib.php");

class report_form extends moodleform {

    public function definition() {
        global $CFG;
        $mform =& $this->_form;
        // Add group for text areas.
        $mform->addElement('header', 'displayinfo', get_string('reporttext', 'block_formindbudget'), null, false);

        if ($anni = get_anni_budget()) {
            foreach ($anni as $value) {
                $choices[$value->anno] = $value->anno;
            }
        } else {
            $choices['2018'] = 2018;
        }
        $mform->addElement('select', 'anni', 'Scegli un anno', $choices);

        $this->add_action_buttons(true, 'Vai al report');
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }
}
