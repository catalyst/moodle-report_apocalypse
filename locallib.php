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
 * Functions used by report_apocalypse.
 *
 * @package    report_apocalypse
 * @author     Dan Marsden
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This function gets the sql required for the apocalypse report.
 *
 * @param array $modules - the names of the modules to include.
 * @param string $sort - the field to sort by.
 * @return array A list containing the constructed sql fragment and an array of parameters.
 */
function report_apocalypse_sql($modules, $sort = '') {
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