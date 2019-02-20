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
 * @package     type_plugin
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_apocalypse;

defined('MOODLE_INTERNAL') || die;

use flexible_table;
use moodle_recordset;
use moodle_url;
use html_writer;

class table extends flexible_table {

    protected $hasdata;

    /**
     * table constructor.
     *
     * @param string $uniqueid
     *
     * @throws \coding_exception
     */
    public function __construct(string $uniqueid) {

        parent::__construct($uniqueid);
        $this->set_columns_and_headings();

    }

    /**
     * Define the column names and the headings to be displayed.
     *
     * @throws \coding_exception
     */
    public function set_columns_and_headings() {

    }

    /**
     * Build the row to be displayed from record.
     *
     * @param $record The record containing data to build row from
     *
     * @return array The data to add to table as a row
     * @throws \moodle_exception
     */
    public function build_row_from_record($record) {

        $courselink = html_writer::link(new moodle_url($record->courseurl), $record->coursefullname);
        $activitylink = html_writer::link(new moodle_url($record->activityurl), $record->activityname);
        $html5status = ($record->html5present) ? 'yes' : 'no';

        return array($record->category, $courselink, $record->type, $activitylink, $html5status);
    }

    /**
     * Build the table rows from moodle_recordset
     *
     * @param moodle_recordset $records The recordset of data to build table from.
     *
     * @throws \coding_exception
     */
    public function add_rows($records) {
        $this->hasdata = false;

        if($records->valid()) {
            foreach ($records as $record) {
                $this->hasdata = true;
                $this->add_data($this->build_row_from_record($record));
            }
        }
        $records->close();
        $this->add_notification_if_no_data();
    }

    /**
     * Check if we have any results and if not add a no records notification.
     *
     * @throws \coding_exception
     */
    protected function add_notification_if_no_data() {
        global $OUTPUT;

        if (!$this->hasdata) {
            $this->add_data(array($OUTPUT->notification(get_string('noflashobjectsfound', 'report_apocalypse'))));
        }
    }

}