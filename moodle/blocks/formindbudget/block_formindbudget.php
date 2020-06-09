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

/**
 * formindbudget block caps.
 *
 * @package    block_formindbudget
 * @copyright  G.M.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
class block_formindbudget extends block_base {
    /**
     * Standard block init method, defines the title
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('formindbudget', 'block_formindbudget');
    }
    /**
     * Gets the contents of the block
     *
     * @return object An object with the contents
     */
    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }
        $this->content         = new stdClass;
        $this->content->text   = 'Testo';
        $this->content->footer = '';
        return $this->content;
    }
    /**
     * Prevent multiple instances of the block on a page
     *
     * @return boolean
     */
    public function allow_multiple() {
        return false;
    }
    /**
     * Global Config
     *
     * @return boolean
     */
    public function has_config() {
        return false;
    }
}
