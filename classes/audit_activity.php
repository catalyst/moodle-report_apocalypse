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
    protected $activity;

    /**
     * @var string The category name.
     */
    protected $category;

    /**
     * @var string A string representation of the course URL.
     */
    protected $courseurl;

    /**
     * @var string The full name of the course.
     */
    protected $coursefullname;

    /**
     * @var string The type of resource/activity.
     */
    protected $type;

    /**
     * @var string A string representation of the activity URL.
     */
    protected $activityurl;

    /**
     * @var string The name of the activity.
     */
    protected $activityname;

    /**
     * @var int Representation of a boolean 0 for false, 1 for true.
     */
    protected $html5present;

    /**
     * audit_activity constructor.
     *
     * @param \moodle_recordset $record An object from a database query.
     *
     * @throws \moodle_exception
     */
    public function __construct(stdClass $record) {
        $this->activity = $record;
        $this->category = $record->category;
        $this->courseurl = $record->courseurl;
        $this->coursefullname = $record->coursefullname;
        $this->type = $record->type;
        $this->activityurl = $record->activityurl;
        $this->activityname = $record->activityname;
        $this->html5present = $record->html5present;
    }

    /**
     * @return mixed
     */
    public function get_category() {
        return $this->category;
    }

    /**
     * @return string The category name.
     */
    public function get_courseurl() {
        return $this->courseurl;
    }

    /**
     * @return string The full name of the course.
     */
    public function get_coursefullname() {
        return $this->coursefullname;
    }

    /**
     * @return string The type of resource/activity.
     */
    public function get_type() {
        return $this->type;
    }

    /**
     * @return A string representation of the activity URL.
     */
    public function get_activityurl() {
        return $this->activityurl;
    }

    /**
     * @return The name of the activity.
     */
    public function get_activityname() {
        return $this->activityname;
    }

    /**
     * @return int Representation of a boolean 1 if contains html5 content, 0 otherwise.
     */
    public function get_html5present() {
        return $this->html5present;
    }

    /**
     * Get a html course link to display for the audit activity.
     *
     * @return string HTML fragment
     * @throws \moodle_exception
     */
    public function get_courselink() {
        return html_writer::link(new moodle_url($this->courseurl), $this->coursefullname);
    }

    /**
     * Get a html activity link to display for audit activity.
     *
     * @return string HTML fragment
     * @throws \moodle_exception
     */
    public function get_activitylink() {
        return html_writer::link(new moodle_url($this->activityurl), $this->activityname);
    }

    /**
     * Get the standard class representation of this instance.
     *
     * @return \stdClass
     */
    public function get_activity() {
        return $this->activity;
    }
}
