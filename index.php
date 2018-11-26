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

$page = optional_param('page', 0, PARAM_INT); // This represents which backup we are viewing.

admin_externalpage_setup('reportapocalypse', '', null, '', array('pagelayout'=>'report'));

$date = strtotime("2019-8-31 0:00");

$remaining = $date - time();
$daysremaining = floor($remaining / 86400);
echo $OUTPUT->header();
if ($daysremaining > 0) {
    echo $OUTPUT->heading(get_string('apocalypseinxdays', 'report_apocalypse', $daysremaining));
} else {
    echo $OUTPUT->heading(get_string('apocalypseishere', 'report_apocalypse'));
}

echo $OUTPUT->box_start();
echo get_string('description', 'report_apocalypse');
echo $OUTPUT->box_end();
$table = new html_table;
$table->head = array(
    get_string("course"),
    get_string('activitytype', 'report_apocalypse'),
    get_string("activity")
);
$table->attributes = array('class' => 'generaltable apocalypse-report');
$table->data = array();

$DB->sql_like('f.filename', ':f1');

$params = array();
$like1 = $DB->sql_like('f.filename', ':f1', false);
$params['f1'] = '%.swf';
$like2 = $DB->sql_like('f.filename', ':f2', false);
$params['f2'] = '%.flv';
$like3 = $DB->sql_like('f.filename', ':f3', false);
$params['f3'] = '%.fla';

$sql = "SELECT DISTINCT c.id, c.shortname, s.name, cx.instanceid
          FROM {files} f
          JOIN {context} cx on cx.id = f.contextid 
          JOIN {scorm} s on s.id = cx.instanceid
          JOIN {course} c on c.id = s.course
          WHERE f.component = 'mod_scorm' AND
           ($like1 or $like2 or $like3)";
$rs = $DB->get_recordset_sql($sql, $params);
foreach ($rs as $activity) {
    $courseurl = new moodle_url('/course/view.php', array('id' => $activity->id));
    $activityurl = new moodle_url('/mod/scorm/view.php', array('id' => $activity->instanceid));
    $coursecell = html_writer::link($courseurl, $activity->shortname);
    $activitycell = html_writer::link($activityurl, $activity->name);

    $table->data[] = new html_table_row(array($coursecell, 'SCORM', $activitycell));
}
$rs->close();

$sql = "SELECT DISTINCT c.id, c.shortname, r.name, cx.instanceid
          FROM {files} f
          JOIN {context} cx on cx.id = f.contextid 
          JOIN {resource} r on r.id = cx.instanceid
          JOIN {course} c on c.id = r.course
          WHERE f.component = 'mod_resource' AND
           ($like1 or $like2 or $like3)";
$rs = $DB->get_recordset_sql($sql, $params);
foreach ($rs as $activity) {
    $courseurl = new moodle_url('/course/view.php', array('id' => $activity->id));
    $activityurl = new moodle_url('/mod/resource/view.php', array('id' => $activity->instanceid));
    $coursecell = html_writer::link($courseurl, $activity->shortname);
    $activitycell = html_writer::link($activityurl, $activity->name);

    $table->data[] = new html_table_row(array($coursecell, 'Resource', $activitycell));
}
$rs->close();

$sql = "SELECT DISTINCT c.id, c.shortname, r.name, cx.instanceid
          FROM {files} f
          JOIN {context} cx on cx.id = f.contextid 
          JOIN {imscp} r on r.id = cx.instanceid
          JOIN {course} c on c.id = r.course
          WHERE f.component = 'mod_imscp' AND
           ($like1 or $like2 or $like3)";
$rs = $DB->get_recordset_sql($sql, $params);
foreach ($rs as $activity) {
    $courseurl = new moodle_url('/course/view.php', array('id' => $activity->id));
    $activityurl = new moodle_url('/mod/imscp/view.php', array('id' => $activity->instanceid));
    $coursecell = html_writer::link($courseurl, $activity->shortname);
    $activitycell = html_writer::link($activityurl, $activity->name);

    $table->data[] = new html_table_row(array($coursecell, 'IMSCP', $activitycell));
}
$rs->close();

// Check if we have any results and if not add a no records notification
if (empty($table->data)) {
    $cell = new html_table_cell($OUTPUT->notification(get_string('noflashobjectsfound', 'report_apocalypse')));
    $cell->colspan = 2;
    $table->data[] = new html_table_row(array($cell));
}

// Display the report.
echo $OUTPUT->box_start();
echo html_writer::table($table);
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
