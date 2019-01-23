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
 * A report to display the list of Moodle activities that contain Flash-based content.
 *
 * @package    report_apocalypse
 * @author     Dan Marsden
 * @copyright  2018 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

$page = optional_param('page', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_RAW);
$perpage = optional_param('perpage', 50, PARAM_INT);

admin_externalpage_setup('reportapocalypse', '', null, '',
    array('pagelayout' => 'report'));

// This is an abitrary date based on the statements from browser developers relating to "mid 2019".
$date = strtotime("2019-8-31 0:00");

// List of course category id's and their name to allow display in the report.
$coursecategorynames = $DB->get_records_menu('course_categories', array(), '', 'id, name');

$exportfilename = "flash-apocalypse-report";
$table = new flexible_table('report_apocalypse');
$table->show_download_buttons_at(array(TABLE_P_BOTTOM));

if (!$table->is_downloading($download, $exportfilename)) {
    $remaining = $date - time();
    $daysremaining = floor($remaining / 86400);
    echo $OUTPUT->header();
    $imageurl = $CFG->wwwroot .'/report/apocalypse/pix/catalyst-logo.svg'; // Not using image_url for old site support.
    echo '<span class="catalyst-logo">
          <a href="https://www.catalyst.net.nz/products/moodle/?refer=report_apocalypse">
          <img src="' . $imageurl . '" width="181"></a></span>';

    if ($daysremaining > 0) {
        echo $OUTPUT->heading(get_string('apocalypseinxdays', 'report_apocalypse', $daysremaining));
    } else {
        echo $OUTPUT->heading(get_string('apocalypseishere', 'report_apocalypse'));
    }

    echo $OUTPUT->box_start();
    echo get_string('description', 'report_apocalypse');
    echo $OUTPUT->box_end();
}

$table->define_baseurl($PAGE->url);
$table->define_columns(array('category', 'coursefullname', 'component', 'name', 'html5'));
$table->define_headers(array(
    get_string('category'),
    get_string("course"),
    get_string('activitytype', 'report_apocalypse'),
    get_string("activity"),
    get_string('dualmode', 'report_apocalypse')
));
$table->sortable(true);
$table->set_attribute('class', 'generaltable generalbox');
$table->setup();

$filetypes = array('%.fla', '%.flv', '%.swf');
// Check all modules in the site.
$modules = $DB->get_records_menu('modules', array(), '', 'id, name');
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
$sort = $table->get_sql_sort();
if (!empty($sort)) {
    $sql .= " ORDER BY ".$sort;
}

$limitfrom = 0;
$limitnum = 0;
// Get count of all records for pagination.
if (!$download) {
    $count = $DB->count_records_sql("SELECT count(*) FROM ($sql) as allr", $params);
    $table->pagesize($perpage, $count);
    $limitfrom = $table->get_page_start();
    $limitnum = $table->get_page_size();
}

$rs = $DB->get_recordset_sql($sql, $params, $limitfrom, $limitnum);
$hasdata = false;
foreach ($rs as $activity) {
    $hasdata = true;
    $courseurl = new moodle_url('/course/view.php', array('id' => $activity->id));
    $shortcomponent = str_replace('mod_', '', $activity->component);
    $activityurl = new moodle_url("/mod/$shortcomponent/view.php", array('id' => $activity->instanceid));
    $coursecell = html_writer::link($courseurl, $activity->coursefullname);
    $categorycell = '';
    if (!empty($activity->category)) {
        $categories = explode('/', $activity->category);
        foreach ($categories as $c) {
            if (!empty($c) && !empty($coursecategorynames[$c])) {
                if (!empty($categorycell)) {
                    $categorycell .= " / ";
                }
                $categorycell .= $coursecategorynames[$c];
            }
        }
    }
    if ($shortcomponent == 'legacy') {
        $activityurl = new moodle_url("/files/index.php", array('contextid' => $activity->contextid));
    } else {
        $activityurl = new moodle_url("/mod/$shortcomponent/view.php", array('id' => $activity->instanceid));
    }
    $activitycell = html_writer::link($activityurl, $activity->name);
    $dualmode = empty($activity->html5) ? '' : get_string('yes');
    $table->add_data(array($categorycell, $coursecell, $shortcomponent, $activitycell, $dualmode));
}
$rs->close();

// Check if we have any results and if not add a no records notification.
if (!$hasdata) {
    $table->add_data(array($OUTPUT->notification(get_string('noflashobjectsfound', 'report_apocalypse'))));
}
// Display the report.
$table->finish_output();

if (!$download) {
    echo $OUTPUT->footer();
}
