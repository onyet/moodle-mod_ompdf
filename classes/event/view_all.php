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
 * The Vew All event.
 *
 * @package    mod_ompdf
 * @copyright  2021 Dian Mukti Wibowo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_ompdf\event;

/**
 * Event triggered when OMPDF activities are viewed.
 *
 * @since     Moodle 3.10+
 * @copyright 2021  Dian Mukti Wibowo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class view_all extends \core\event\base {
    /**
     * Initialises the event data.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'ompdf';
    }

    /**
     * Returns the event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventviewall', 'mod_ompdf');
    }

    /**
     * Returns the event description.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' has viewed all ompdf activities in course with id '{$this->courseid}'.";
    }

    /**
     * Returns the event URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/ompdf/index.php', ['id' => $this->courseid]);
    }

    /**
     * Returns legacy log data.
     *
     * @return array
     */
    public function get_legacy_logdata() {
        return [$this->courseid, 'ompdf', 'view',
            'Has view pdf',
            $this->objectid, $this->contextinstanceid];
    }

    /**
     * Returns the legacy event name.
     *
     * @return string
     */
    public static function get_legacy_eventname() {
        return 'view_all';
    }

    /**
     * Returns legacy event data.
     *
     * @return stdClass
     */
    protected function get_legacy_eventdata() {
        $data = new \stdClass();
        $data->id = $this->objectid;
        $data->userid = $this->relateduserid;
        return $data;
    }
}
