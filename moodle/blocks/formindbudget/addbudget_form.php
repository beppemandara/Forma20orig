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

class addbudget_form extends moodleform {

    public function definition() {
        global $CFG;
        $mform =& $this->_form;
        // Add group for text areas.
        $mform->addElement('header', 'displayinfo', get_string('headertext', 'block_formindbudget'), null, false);

        // Stringa action.
        $azione = $this->_customdata['stringazione'];
        $mform->addElement('hidden', 'azione', $azione);
        $mform->setType('azione', PARAM_TEXT);
        // Anno di riferimento per il budget.
        $year = $this->_customdata['annoincorso'];
        $mform->addElement('hidden', 'year', $year);
        $mform->setType('year', PARAM_TEXT);
        $mform->addElement('static', 'annocorrente', get_string('anno', 'block_formindbudget'), $year);
        // Set budget default.
        $importo = get_budget_anno_corrente($year);
        if (($importo != 'nobudgetfound') && ($importo > 0)) {
            $valori = explode('.', $importo);
        } else {
            $valori = array(0, 0);
        }
        // Add gruppo budget.
        $gruppobudget = array();
        // Add totale budget.
        $strtotbudget = get_string('totbudget', 'block_formindbudget');
        $gruppobudget[] =& $mform->createElement('text', 'totbudget', $strtotbudget, 'maxlength="10" size="10" ');
        $mform->setDefault('totbudget', $valori[0]);
        $mform->setType('totbudget', PARAM_RAW);
        // Add virgola.
        $gruppobudget[] =& $mform->createElement('static', 'comma', '', ' , ');
        // Add decimali.
        $gruppobudget[] =& $mform->createElement('text', 'decimali', '', 'maxlength="2" size="2" ');
        $mform->setDefault('decimali', $valori[1]);
        $mform->setType('decimali', PARAM_RAW);
        // Add istruzioni importo.
        $esempio = ' (Inserire solo numeri senza segni di separazione)';
        $gruppobudget[] =& $mform->createElement('static', 'esempio', '', $esempio);
        $mform->addGroup($gruppobudget, 'gruppobudget', $strtotbudget, '', false);
        // Add note eventuali.
        $attributi = array('wrap' => 'virtual', 'rows' => 6, 'cols' => 50);
        $mform->addElement('textarea', 'note', get_string('note', 'block_formindbudget'), $attributi);
        $mform->setDefault('note', get_note_anno_corrente($year));

        $this->add_action_buttons(true, $azione);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }
}
