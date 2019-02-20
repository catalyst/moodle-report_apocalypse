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

use stdClass;
use moodle_url;
use DateTime;

/**
 * Manager class for running audits and instantiating audit_activities.
 *
 * @package report_apocalypse
 */
class audit_manager implements audit_interface {

    /**
     * Run a flash audit on the moodle site.
     *
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     */
    public static function run_audit() {
        global $DB;

        $modules = self::get_all_modules();
        list($sql, $params) = self::build_sql_and_parameters_for_audit($modules);
        $recordset = $DB->get_recordset_sql($sql, $params);

        $transaction = $DB->start_delegated_transaction();
        try {
            self::delete_records();
            self::store_records($recordset);

            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
        }
    }

    /**
     * Get all audit results as a moodle_recordset.
     *
     * @return \moodle_recordset A moodle_recordset instance
     * @throws \dml_exception
     */
    public static function get_audit_results() {
        global $DB;

        return $DB->get_recordset('report_apocalypse');
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
    public static function get_audit_results_paginated($limitfrom=0, $limitnum=0) {
        global $DB;

        $recordset = $DB->get_recordset('report_apocalypse', null, '', '*', $limitfrom, $limitnum);

        $activities = self::get_instances($recordset);

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
        $activities = array();
        foreach ($records as $activity) {
            $activities[] = new audit_activity($activity);
        }
        return $activities;
    }

    /**
     * Delete previous audit results from database.
     *
     * @throws \dml_exception
     */
    public static function delete_records() {
        global $DB;

        $DB->delete_records('report_apocalypse');

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

        if ($recordset->valid()) {
            $count = 0;

            foreach ($recordset as $record) {
                self::insert_audit_activity($record);
                $count++;
            }
            $recordset->close();
            self::insert_audit_record($count);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the details of the last time a flash audit was run.
     */
    public static function get_last_audit() {
        global $DB;

        $sql = 'SELECT * FROM {report_apocalypse_audits}
                WHERE rundatetime = (SELECT MAX(rundatetime) FROM {report_apocalypse_audits})';
        return $DB->get_record_sql($sql);
    }

    /**
     * Iterate over a \moodle_recordset and insert values into 'report_apocalypse' table.
     *
     * @param mixed $record a fieldset object containing a record
     *
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function insert_audit_activity($record) {
        global $DB;

        $activity = self::build_audit_activity_from_record($record);
        $DB->insert_record('report_apocalypse', $activity->get_activity(), false);

    }

    /**
     * A helper method to convert database query result for use in audit_activity object instances.
     *
     * @param mixed $record a fieldset object containing a record
     *
     * @return \report_apocalypse\audit_activity
     * @throws \moodle_exception
     */
    public static function build_audit_activity_from_record($record) {

        $activity = new stdClass();
        $activity->category = self::get_category_from_record($record);
        $activity->courseurl = self::get_course_url_from_record($record);
        $activity->coursefullname = $record->coursefullname;
        $activity->type = str_replace('mod_', '', $record->component);
        $activity->activityurl = self::get_activity_url_from_record($activity->type, $record);
        $activity->activityname = $record->name;
        $activity->html5present = empty($record->html5) ? 0 : 1;

        return new audit_activity($activity);
    }

    /**
     * Get category from record
     *
     * @param mixed $record a fieldset object containing a record
     *
     * @return string  The category name or empty string if none found
     * @throws \dml_exception
     */
    public static function get_category_from_record($record) {
        global $DB;

        // Get the course category names and their ids.
        $coursecategorynames = $DB->get_records_menu('course_categories', array(), '', 'id, name');
        $category = '';
        if (!empty($record->category)) {
            $categories = explode('/', $record->category);
            foreach ($categories as $c) {
                if (!empty($c) && !empty($coursecategorynames[$c])) {
                    if (!empty($category)) {
                        $category .= " / ";
                    }
                    $category .= $coursecategorynames[$c];
                }
            }
        }
        return $category;
    }


    /**
     * Get a course url representation from a db record.
     *
     * @param mixed $record a fieldset object containing a record
     *
     * @return string  Resulting URL
     * @throws \moodle_exception
     */
    public static function get_course_url_from_record($record) {

        $courseurl = new moodle_url('/course/view.php', array('id' => $record->id));
        return $courseurl->out();

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
            $activityurl = new moodle_url("/files/index.php", array('contextid' => $record->contextid));
        } else {
            $activityurl = new moodle_url("/mod/$type/view.php", array('id' => $record->instanceid));
        }
        return $activityurl->out();
    }

    /**
     * Insert audit date/time and a count of flash activities detected into 'report_apocalypse_audit' table.
     *
     * @param int $count the count of activities to store in record
     *
     * @throws \Exception
     */
    public static function insert_audit_record(int $count) {
        global $DB;

        $record = new stdClass();
        $date = new DateTime();
        $record->rundatetime = $date->getTimestamp();
        $record->countflashactivities = $count;
        $DB->insert_record('report_apocalypse_audits', $record, false);

    }

    /**
     * Count how many affected activities were detected during last audit run.
     *
     * @return int The total number of audit activities in database.
     * @throws \dml_exception
     */
    public static function count_audit_activities() {
        global $DB;

        $modules = self::get_all_modules();
        list($sql, $params) = self::build_sql_and_parameters_for_audit($modules);
        return $DB->count_records_sql("SELECT count(*) FROM ($sql) as allr", $params);
    }

    /**
     * Get all modules as an associative array.
     *
     * @return array associative array of limited module information, id->name
     *
     * @throws \dml_exception
     */
    public static function get_all_modules() {
        global $DB;

        return $DB->get_records_menu('modules', array(), '', 'id, name');
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

        $sql = "SELECT main.contextid, main.id, main.coursefullname, cat.path
          as category, main.name, main.instanceid, main.component, dualsupport.html5
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
