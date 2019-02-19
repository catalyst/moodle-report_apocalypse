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
require_once($CFG->dirroot . '/report/apocalypse/locallib.php');

use report_apocalypse\{apocalypse_datetime, flash_audit, table};

$page = optional_param('page', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_RAW);
$perpage = optional_param('perpage', 50, PARAM_INT);

admin_externalpage_setup('reportapocalypse', '', null, '',
    array('pagelayout' => 'report'));

$title = get_string('pluginname', 'report_apocalypse');

$PAGE->set_context(context_system::instance());
$PAGE->set_title($title);
$PAGE->set_heading($title);

$audit = new flash_audit();
$table = new table('report_apocalypse', $audit->get_results());

$exportfilename = "flash-apocalypse-report";

if (!$table->is_downloading($download, $exportfilename)) {
    echo $OUTPUT->header();
    $imageurl = $CFG->wwwroot .'/report/apocalypse/pix/catalyst-logo.svg'; // Not using image_url for old site support.
    echo '<span class="catalyst-logo">
          <a href="https://www.catalyst.net.nz/products/moodle/?refer=report_apocalypse">
          <img src="' . $imageurl . '" width="181"></a></span>';

    if (apocalypse_datetime::get_days_remaining() > 0) {
        echo $OUTPUT->heading(get_string('apocalypseinxdays', 'report_apocalypse', apocalypse_datetime::get_days_remaining()));
    } else {
        echo $OUTPUT->heading(get_string('apocalypseishere', 'report_apocalypse'));
    }

    echo $OUTPUT->box_start();
    echo get_string('description', 'report_apocalypse');
    echo $OUTPUT->box_end();
}

$table->define_baseurl($PAGE->url);

$table->sortable(true);
$table->set_attribute('class', 'generaltable generalbox');
$table->setup();
$table->build_rows($audit->get_results());

// Display the report.
$table->finish_output();

if (!$download) {
    echo $OUTPUT->footer();
}
