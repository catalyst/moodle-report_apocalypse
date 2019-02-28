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

/**
 * Manager class for running audits and instantiating audit_activities.
 *
 * @package report_apocalypse
 */
class audit_manager {

    /**
     * Run a flash audit on the moodle site.
     *
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     */
    public static function run_audit() {
        global $DB;

        $modules = $DB->get_records_menu('modules', array(), '', 'id, name');
        list($sql, $params) = self::build_sql_and_parameters_for_audit($modules);
        $recordset = $DB->get_recordset_sql($sql, $params);

        $transaction = $DB->start_delegated_transaction();
        try {
            $DB->delete_records('report_apocalypse');
            self::store_records($recordset);

            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
        }

        $recordset->close();
    }

    /**
     * Get a range of results as a moodle_recordset based on passed in limits.
     *
     * @param int $limitfrom return a subset of records, starting at this point (optional).
     * @param int $limitnum return a subset comprising this many records (optional, required if $limitfrom is set).
     *
     * @return \moodle_recordset A moodle_recordset instance
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get_audit_results_paginated($limitfrom=0, $limitnum=0, $sort='') {
        global $DB;

        $recordset = $DB->get_recordset('report_apocalypse', null, $sort, '*', $limitfrom, $limitnum);

        $activities = self::get_instances($recordset);

        $recordset->close();

        return $activities;
    }

    /**
     * Helper method to convert db records to audit_activity objects.
     *
     * @param \moodle_recordset $records iterative containing mixed fieldset objects.
     *
     * @return array of audit_activity objects.
     * @throws \moodle_exception
     */
    public static function get_instances(\moodle_recordset $records) {
        global $DB;

        // Get the course category names and their ids.
        $coursecategorynames = $DB->get_records_menu('course_categories', array(), '', 'id, name');

        $activities = array();
        foreach ($records as $activity) {
            $activities[] = new audit_activity($activity, $coursecategorynames);
        }
        return $activities;
    }

    /**
     * Store passed in recordset and a transaction record in database.
     *
     * @param \moodle_recordset $recordset Records to store
     *
     * @return bool true if successful, false if not.
     * @throws \Exception
     */
    public static function store_records($recordset) {
        global $DB;

        if ($recordset->valid()) {
            $DB->insert_records('report_apocalypse', $recordset);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Build a list consisting of the SQL query string and an array of the parameters
     * required to conduct the audit.
     *
     * @param array $modules - the names of the modules to include.
     * @param string $sort - the field to sort by.
     * @return array A list containing the constructed sql fragment and an array of parameters.
     */
    public static function build_sql_and_parameters_for_audit($modules, $sort = '') {
        global $DB;

        $filetypes = array('%.fla', '%.flv', '%.swf');
        $params = array();

        // Create main set of likes to use.
        $likes = array();
        foreach ($filetypes as $type) {
            $likes[] = $DB->sql_like('f.filename', '?', false);
        }

        $sql = "SELECT main.contextid, main.id AS courseid, main.coursefullname, cat.path
          AS category, main.name, main.instanceid, main.component, dualsupport.html5
          FROM (";

        $firstmod = true;
        foreach ($modules as $module) {
            foreach ($filetypes as $type) {
                $params[] = $type;
            }
            if (!$firstmod) {
                $sql .= " UNION ";
            }
            $sql .= " SELECT DISTINCT f.contextid, c.id, c.fullname AS coursefullname, c.category, s.name, cx.instanceid, f.component
          FROM {files} f
          JOIN {context} cx on cx.id = f.contextid
          JOIN {course_modules} cm on cm.id = cx.instanceid
          JOIN {".$module."} s on s.id = cm.instance
          JOIN {course} c on c.id = s.course
          WHERE f.component = 'mod_$module' AND
           (".implode(' or ', $likes).")";

            $firstmod = false;
        }

        // Join with legacy files search.
        foreach ($filetypes as $type) {
            $params[] = $type;
        }
        $sql .= " UNION SELECT DISTINCT f.contextid, c.id, c.fullname, c.category, f.filename as name,
                                cx.instanceid, f.filearea as component
          FROM {files} f
          JOIN {context} cx on cx.id = f.contextid
          JOIN {course} c on c.id = cx.instanceid
          WHERE f.component = 'course' AND
                (".implode(' or ', $likes).")";

        // Close off union sql.
        $sql .= ") as main";

        // Now join with data on all contexts that contain html5 content (possible dual support.)
        $sql .= " LEFT JOIN (SELECT distinct contextid, 1 as html5
                  FROM {files}
                 WHERE filename = 'index_lms_html5.html') dualsupport on dualsupport.contextid = main.contextid ";

        // Now Join with course category for this course.
        $sql .= " JOIN {course_categories} cat ON cat.id = main.category";
        if (!empty($sort)) {
            $sql .= " ORDER BY ".$sort;
        }

        return array($sql, $params);
    }
}
