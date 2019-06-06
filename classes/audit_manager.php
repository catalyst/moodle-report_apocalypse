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

use core\task\delete_unconfirmed_users_task;

defined('MOODLE_INTERNAL') || die;

/**
 * Manager class for running audits and instantiating audit_activities.
 *
 * @package report_apocalypse
 */
class audit_manager {

    public static $flashextensions = array('%.fla', '%.flv', '%.swf');

    /**
     * Run a flash audit on the moodle site.
     *
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     */
    public static function run_audit() {
        global $DB;

        $modules = $DB->get_records_menu('modules', array(), '', 'id, name');

        $flashcomponents = [];

        foreach ($modules as $module) {
            $flashcomponents = array_merge($flashcomponents, self::find_module_flash_components($module));
        }
        // Add legacy components.
        $flashcomponents = array_merge($flashcomponents, self::find_module_flash_components('course', true));

        $html5contextids = $DB->get_fieldset_select('files', 'contextid', 'filename=:filename',
            ['filename' => 'index_lms_html5.html']);

        // Add the correct html5 dual support flag.
        foreach ($flashcomponents as $flashcomponent) {
            $index = array_search($flashcomponent->contextid, $html5contextids);
            if ($index !== false) {
                $flashcomponent->html5 = 1;
                // Remove the context to speed up the checking each iteration.
                array_splice($html5contextids, $index, 1);
            } else {
                $flashcomponent->html5 = 0;
            }
        }

        $transaction = $DB->start_delegated_transaction();
        try {
            $DB->delete_records('report_apocalypse');
            self::store_records($flashcomponents);

            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
        }
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
    public static function get_instances($records) {

        $activities = array();
        foreach ($records as $activity) {
            $activities[] = new audit_activity($activity);
        }
        return $activities;
    }

    /**
     * Store passed in array in the `report_apocalypse` table.
     *
     * @param array $records Records to store
     *
     * @return bool true if successful, false if not.
     * @throws \Exception
     */
    public static function store_records($records) {
        global $DB;

        if (!empty($records)) {
            $DB->insert_records('report_apocalypse', $records);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Run a database search to find all flash components in a module.
     *
     * @param string $module The folder name of the module.
     * @param bool $legacy flag indicating if module is legacy, when `true` indicates no
     * plugin type, when `false` indicates plugin type `mod`.
     *
     * @return array of objects representing flash components found in module
     * @throws \dml_exception
     */

    public static function find_module_flash_components($module, $legacy=false) {
        global $DB;

        $component = ($legacy) ? $module : 'mod_' . $module;

        $sql = "SELECT DISTINCT f.contextid, c.id AS courseid, c.fullname AS coursefullname, cat.name AS category, ";
        $sql .= ($legacy) ? "f.filename AS name, " : "s.name, ";
        $sql .= "cx.instanceid, ";
        $sql .= ($legacy) ? "'legacy' AS component " : "f.component ";
        $sql .= "FROM {files} f "
          . "JOIN {context} cx on cx.id = f.contextid ";

        if (!$legacy) {
            $sql .= "JOIN {course_modules} cm on cm.id = cx.instanceid "
              . "JOIN {$module} s on s.id = cm.instance "
              . "JOIN {course} c on c.id = s.course ";
        } else {
            $sql .= "JOIN {course} c on c.id = cx.instanceid ";
        }

        $sql .= "JOIN {course_categories} cat on cat.id = c.category "
            . "WHERE f.component = '$component' AND ";

        if ($legacy) {
            $sql .= "f.filearea = 'legacy' AND ";
        }

        $sql .= "(LOWER(f.filename) LIKE LOWER(?) or LOWER(f.filename) LIKE LOWER(?) or LOWER(f.filename) LIKE LOWER(?))";

        return $DB->get_records_sql($sql, static::$flashextensions);
    }

}
