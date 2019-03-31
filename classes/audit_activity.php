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
 * @package     report_apocalypse
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_apocalypse;

defined('MOODLE_INTERNAL') || die;

use moodle_url;
use html_writer;
use stdClass;

/**
 * An class for building object representations of activities identified by audit.
 * Contains formatting methods to produce html output for renderer from database records.
 *
 * @package report_apocalypse
 */
class audit_activity {

    /**
     * @var stdClass The activity object from database.
     */
    public $activity;

    /**
     * @var string The category name.
     */
    public $category;

    /**
     * @var string A string representation of the course URL.
     */
    public $courseurl;

    /**
     * @var string The full name of the course.
     */
    public $coursefullname;

    /**
     * @var string The type of resource/activity.
     */
    public $type;

    /**
     * @var string A string representation of the activity URL.
     */
    public $activityurl;

    /**
     * @var string The name of the activity.
     */
    public $activityname;

    /**
     * @var int Representation of a boolean 0 for false, 1 for true.
     */
    public $html5present;

    /**
     * audit_activity constructor.
     *
     * @param \moodle_recordset $record An object from a database query.
     *
     * @throws \moodle_exception
     */
    public function __construct($record) {
        $this->activity = $record;
        $this->category = $record->category;
        $courseurl = new moodle_url('/course/view.php', array('id' => $record->courseid));
        $this->courseurl = $courseurl->out();
        $this->coursefullname = $record->coursefullname;
        $this->type = str_replace('mod_', '', $record->component);
        $this->activityurl = $this->get_activity_url_from_record($this->type, $record);
        $this->activityname = $record->name;
        $this->html5present = empty($record->html5) ? 0 : 1;
    }

    /**
     * Get an activity url representation from a db record.
     *
     * @param string $type The type of the activity
     * @param mixed $record a fieldset object containing a record
     *
     * @return string Resulting URL
     * @throws \moodle_exception
     */
    public static function get_activity_url_from_record($type = '', $record) {
        if ($type == 'legacy') {
            // Direct the link to the legacy file area of the course.
            $activityurl = new moodle_url("/files/index.php", array('contextid' => $record->contextid));
        } else {
            $activityurl = new moodle_url("/mod/$type/view.php", array('id' => $record->instanceid));
        }
        return $activityurl->out();
    }
}

