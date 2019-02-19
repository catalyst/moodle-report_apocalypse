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
use DateTime;
use moodle_url;

/**
 * Class flash_audit
 * @package report_apocalypse
 */
class flash_audit implements audit_interface {

    /**
     * @var object instance of the database global.
     */
    protected $db;

    /**
     * @var string of SQL to be utilised for conducting audit.
     */
    protected $sql;

    /**
     * @var array of the parameters to be applied to the SQL for audit.
     */
    protected $params;

    /**
     * @var null moodle_recordset of audit results.
     */
    protected $results = null;

    public function __construct() {
        $this->init();
    }

    protected function init() {
        global $DB;

        $this->db = $DB;

        $this->load_latest_results_as_recordset_from_db();

        list($this->sql, $this->params) = $this->build_sql_and_parameters_for_audit(
                $DB->get_records_menu('modules', array(), '', 'id, name')
            );
    }

    /**
     * Get the moodle_recordset of audit results.
     *
     * @return moodle_recordset|null
     */
    public function get_results() {

        $this->remove_previous_results();
        $this->load_latest_results_as_recordset_from_db();
        return $this->results;

    }

    /**
     * Get the SQL query string used to conduct audit.
     *
     * @return string SQL query string
     */
    public function get_sql() {

        return $this->sql;
    }

    /**
     * Get an array of the parameters utilised for audit.
     *
     * @return array the values passed into SQL query for audit
     */
    public function get_params() {

        return $this->params;
    }

    /**
     * Load the results of the most recent audit from database into this instance.
     */
    public function load_latest_results_as_recordset_from_db() {

        $rs = $this->db->get_recordset('report_apocalypse');
        $this->results = $rs->valid() ? $rs : null;

    }

    /**
     * Close the results moodle_recordset of this instance.
     */
    public function remove_previous_results() {

        if (isset($this->results) && !empty($this->results)) {
            $this->results->close();
        } else {
            $this->results = null;
        }
    }

    /**
     * Run the Flash Audit and store resulting moodle_recordset in this instance
     *
     * @return this    For method chaining
     */
    public function run() {
        $this->remove_previous_results();
        $this->results = $this->get_results_as_recordset($this->sql, $this->params);

        return $this;
    }

    /**
     * Delete previous audit results from database.
     */
    public function delete_records() {

        $this->db->delete_records('report_apocalypse');

    }

    /**
     * Insert an activity's audit results into the report_apocalypse table.
     *
     * @param $activity
     */
    public function insert_activity_record($activity) {

        $record = new stdClass();
        $record->category = $this->get_category_from_record($activity);
        $record->courseurl = $this->get_course_url_from_record($activity);
        $record->coursefullname = $activity->coursefullname;
        $record->type = str_replace('mod_', '', $activity->component);
        $record->activityurl = $this->get_activity_url_from_record($record->type, $activity);
        $record->activityname = $activity->name;
        $record->html5present = empty($record->html5) ? 0 : 1;
        $this->db->insert_record('report_apocalypse', $record, false);

    }

    /**
     * Insert audit date/time and a count of flash activities found into report_apocalypse_audit table.
     *
     * @param int $count the count of activities to store in record
     *
     * @throws \Exception
     */
    public function insert_audit_record(int $count) {

        $record = new stdClass();
        $date = new DateTime();
        $record->rundatetime = $date->getTimestamp();
        $record->countflashactivities = $count;
        $this->db->insert_record('report_apocalypse_audits', $record, false);

    }

    /**
     * Store the details of the audit in the database tables.
     *
     * @throws \Exception
     */
    public function store_results() {

        if ($this->results->valid()) {
            $count = 0;

            foreach ($this->results as $activity) {
                $this->insert_activity_record($activity);
                $count++;
            }
            $this->results->close();
            $this->insert_audit_record($count);
        } else {
            // TODO Log an error message
        }

    }

    /**
     * Handle the current results, deleting old and storing new results.
     *
     * @throws \Exception
     */
    public function handle_results() {

        $transaction = $this->db->start_delegated_transaction();
        try {
            $this->delete_records();
            $this->store_results();

            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
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
    protected function build_sql_and_parameters_for_audit($modules, $sort = '') {
        global $DB;

        $filetypes = array('%.fla', '%.flv', '%.swf');
        $params = array();

        // Create main set of likes to use.
        $likes = array();
        foreach ($filetypes as $type) {
            $likes[] = $DB->sql_like('f.filename', '?', false);
        }

        $sql = "SELECT main.contextid, main.id, main.coursefullname, cat.path as category, main.name, main.instanceid, main.component, dualsupport.html5
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

    /**
     * Run SQL query to get moodle_recordset of audit results.
     *
     * @return moodle_recordset containing audit results
     */
    public function get_results_as_recordset() {

        return $this->db->get_recordset_sql($this->sql, $this->params);
    }

    /**
     * Get category from record
     *
     * @param mixed $record  a fieldset object containing a record
     *
     * @return string  The category name or empty string if none found
     */
    public function get_category_from_record($record) {

        // Get the course category names and their ids
        $coursecategorynames = $this->db->get_records_menu('course_categories', array(), '', 'id, name');

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
     * @param mixed $record  a fieldset object containing a record
     *
     * @return string  Resulting URL
     */
    public function get_course_url_from_record($record) {

        $courseurl = new moodle_url('/course/view.php', array('id' => $record->id));
        return $courseurl->out();

    }

    /**
     * @param string $type  The type of the activity
     * @param mixed $record  a fieldset object containing a record
     *
     * @return string Resulting URL
     */
    public function get_activity_url_from_record($type = '', $record) {
        if ($type == 'legacy') {
            $activityurl = new moodle_url("/files/index.php", array('contextid' => $record->contextid));
        } else {
            $activityurl = new moodle_url("/mod/$type/view.php", array('id' => $record->instanceid));
        }
        return $activityurl->out();
    }

    /**
     * Count how many records report will produce.
     */
    public function count_records() {

        $this->db->count_records_sql("SELECT count(*) FROM ($this->sql) as allr", $this->params);

    }

    public function get_table_row_data() {

    }

}