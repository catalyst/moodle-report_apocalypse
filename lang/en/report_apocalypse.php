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
 * Strings for component 'report_apocalpyse', language 'en'
 *
 * @package    report_apocalypse
 * @author     Dan Marsden
 * @copyright  2018 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Flash apocalypse';
$string['apocalypseinxdays'] = 'Flash apocalypse in {$a} days';
$string['apocalypseishere'] = 'Flash apocalypse is here - Flash is no longer supported.';
$string['description'] = '<p>This report lists activities, by type, in your site that contain flash-based content.</p>
<p>Support for Flash ends in mid-2019 when most browsers will disable Flash player and your content will not display.</p>
<p>Some elearning authoring tools can ‘dual publish’ with HTML5 as a fallback in case Flash isn’t supported by the browser. When HTML5 content has been detected it is shown as a Yes in the ‘HTML5 present’ column.</p>
<p>If you require help replacing your old Flash content, contact the certified partner <a href="https://www.catalyst.net.nz/products/moodle/?refer=report_apocalypse">Catalyst IT</a></p>';
$string['noaudit'] = '<p>No flash audits have been conducted for this site.</p>
<p><strong>This report requires cron to be enabled for automatic flash auditing, or the "scheduled_flash_audit" task to be run manually.</strong></p>';
$string['noflashobjectsfound'] = 'No Flash objects were found in your site.';
$string['activitytype'] = 'Type';
$string['dualmode'] = 'HTML5 present';
$string['privacy:metadata'] = 'The Apocalypse reports plugin does not store any personal data.';
$string['scheduledflashaudit'] = 'Scheduled Flash Audit';
$string['apocalypselastaudit'] = 'Last audit run was {$a}';
