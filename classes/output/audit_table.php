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

namespace report_apocalypse\output;

defined('MOODLE_INTERNAL') || die;

use report_apocalypse\audit_activity;
use report_apocalypse\audit_manager;
use table_sql;
use moodle_url;
use renderable;
use html_writer;

/**
 * Renderable class for index page of report_apocalypse plugin.
 *
 * @package     report_apocalypse
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class audit_table extends table_sql implements renderable {

    /**
     * audit_table constructor.
     *
     * @param string $uniqueid Unique id of table.
     * @param \moodle_url $url Url where this table is displayed.
     * @param int $page Current page number for pagination.
     * @param int $perpage Audit records to display per page for pagination.
     * @param string $download Format of download (csv, html etc.) or empty string if not downloading.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function __construct($uniqueid, $url, $currpage=0, $pagesize=50, $download='') {
        parent::__construct($uniqueid);

        $this->set_attribute('id', 'report_apocalypse_table');
        $this->set_attribute('class', 'generaltable generalbox');
        $this->show_download_buttons_at(array(TABLE_P_BOTTOM));
        $this->define_baseurl($url);
        $this->define_columns(array('category', 'coursefullname', 'component', 'name', 'html5'));
        $this->define_headers(array(
            get_string('category'),
            get_string('course'),
            get_string('activitytype', 'report_apocalypse'),
            get_string('activity'),
            get_string('dualmode', 'report_apocalypse')
        ));
        $this->currpage = $currpage;
        $this->pagesize = $pagesize;
        $this->is_downloading($download, 'flash-apocalypse-report');
        $this->sortable(true);
        $this->set_sql('*', "{report_apocalypse}", '1');

    }

    /**
     * Get content for category column.
     *
     * @param audit_activity $activity object
     *
     * @return string html used to display the column field.
     */
    public function col_category($activity) {
        return $this->format_text($activity->category);
    }

    /**
     * Get content for courselink column.
     *
     * @param audit_activity $activity object
     *
     * @return string html used to display the column field.
     * @throws \moodle_exception
     */
    public function col_coursefullname($activity) {
        if ($this->is_downloading()) {
            return $this->format_text($activity->coursefullname);
        }
        return $this->format_text(html_writer::link(new moodle_url($activity->courseurl), $activity->coursefullname), FORMAT_HTML);
    }

    /**
     * Get content for type column.
     *
     * @param audit_activity $activity object
     *
     * @return string html used to display the column field.
     */
    public function col_component($activity) {
        return $this->format_text($activity->type);
    }

    /**
     * Get content for activitylink column.
     *
     * @param audit_activity $activity object
     *
     * @return string html used to display the column field.
     * @throws \moodle_exception
     */
    public function col_name($activity) {
        if ($this->is_downloading()) {
            return $this->format_text($activity->activityname);
        }
        return $this->format_text(html_writer::link(new moodle_url($activity->activityurl), $activity->activityname), FORMAT_HTML);
    }

    /**
     * Get content for html5present column.
     *
     * @param \report_apocalypse\audit_activity $activity object
     *
     * @return string html used to display the column field.
     */
    public function col_html5($activity) {
        return $this->format_text(($activity->html5present) ? 'Yes' : 'No');
    }

    /**
     * Query the db. Store results in the table object for use by build_table.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar. Bar
     * will only be used if there is a fullname column defined for the table.
     *
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function query_db($pagesize, $useinitialsbar=true) {
        global $DB;

        $total = $DB->count_records('report_apocalypse');
        $sort = $this->get_sql_sort();
        $this->pagesize($pagesize, $total);
        $activities = audit_manager::get_audit_results_paginated($this->get_page_start(), $this->get_page_size(), $sort);
        $this->rawdata = $activities;
        if ($useinitialsbar) {
            $this->initialbars($total > $pagesize);
        }

    }

}

