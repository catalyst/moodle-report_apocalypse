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

admin_externalpage_setup('reportapocalypse', '', null, '',
    array('pagelayout' => 'report'));

// This is an abitrary date based on the statements from browser developers relating to "mid 2019".
$date = strtotime("2019-8-31 0:00");

$remaining = $date - time();
$daysremaining = floor($remaining / 86400);
echo $OUTPUT->header();
$imageurl = $OUTPUT->image_url('catalyst-logo', 'report_apocalypse');
echo '<span class="catalyst-logo"><a href="https://www.catalyst.net.nz/products/moodle/?refer=report_apocalypse"><img src="'.$imageurl.'" width="181"></a></span>';

if ($daysremaining > 0) {
    echo $OUTPUT->heading(get_string('apocalypseinxdays', 'report_apocalypse', $daysremaining));
} else {
    echo $OUTPUT->heading(get_string('apocalypseishere', 'report_apocalypse'));
}

echo $OUTPUT->box_start();
echo get_string('description', 'report_apocalypse');
echo $OUTPUT->box_end();
$table = new flexible_table('report_apocalypse');
$table->define_baseurl($PAGE->url);
$table->define_columns(array('shortname', 'component', 'name', 'html5'));
$table->define_headers(array(
    get_string("course"),
    get_string('activitytype', 'report_apocalypse'),
    get_string("activity"),
    get_string('dualmode', 'report_apocalypse')
));
$table->sortable(true);
$table->set_attribute('class', 'generaltable generalbox');
$table->setup();

$DB->sql_like('f.filename', ':f1');

$filetypes = array('%.fla', '%.flv', '%.swf');
// Check all modules in the site.
$modules = $DB->get_records_menu('modules', array(), '', 'id, name');
$params = array();
// Create main set of likes to use.
$likes = array();
foreach ($filetypes as $type) {
    $likes[] = $DB->sql_like('f.filename', '?', false);
}

$sql = "SELECT main.contextid, main.id, main.shortname, main.name, main.instanceid, main.component, dual.html5
          FROM (";

$firstmod = true;
foreach ($modules as $module) {
    foreach ($filetypes as $type) {
        $params[] = $type;
    }
    if (!$firstmod) {
        $sql .= " UNION ";
    }
    $sql .= " SELECT DISTINCT f.contextid, c.id, c.shortname, s.name, cx.instanceid, f.component
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
$sql .= " UNION SELECT DISTINCT f.contextid, c.id, c.shortname, f.filename as name, cx.instanceid, f.filearea as component
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
                 WHERE filename = 'index_lms_html5.html') dual on dual.contextid = main.contextid ";
$sort = $table->get_sql_sort();
if (!empty($sort)) {
    $sql .= " ORDER BY ".$sort;
}
$rs = $DB->get_recordset_sql($sql, $params);
$hasdata = false;
foreach ($rs as $activity) {
    $hasdata = true;
    $courseurl = new moodle_url('/course/view.php', array('id' => $activity->id));
    $shortcomponent = str_replace('mod_', '', $activity->component);
    $activityurl = new moodle_url("/mod/$shortcomponent/view.php", array('id' => $activity->instanceid));
    $coursecell = html_writer::link($courseurl, $activity->shortname);
    if ($shortcomponent == 'legacy') {
        $activityurl = new moodle_url("/files/index.php", array('contextid' => $activity->contextid));
    } else {
        $activityurl = new moodle_url("/mod/$shortcomponent/view.php", array('id' => $activity->instanceid));
    }
    $activitycell = html_writer::link($activityurl, $activity->name);
    $dualmode = empty($activity->html5) ? '' : get_string('yes');
    $table->add_data(array($coursecell, $shortcomponent, $activitycell, $dualmode));
}
$rs->close();

// Check if we have any results and if not add a no records notification.
if (!$hasdata) {
    $table->add_data(array($OUTPUT->notification(get_string('noflashobjectsfound', 'report_apocalypse'))));
}

// Display the report.
$table->finish_output();
echo $OUTPUT->footer();
