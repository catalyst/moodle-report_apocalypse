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

namespace report_apocalypse\output;

defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;
use report_apocalypse\apocalypse_datetime;

/**
 * Renderer class for audit table.
 *
 * @package report_apocalypse\output
 */
class renderer extends plugin_renderer_base {

    /**
     * @param \report_apocalypse\output\audit_table $renderable
     *
     * @return string
     * @throws \coding_exception
     */
    public function render_audit_table(audit_table $renderable) {
        $output = $this->render_description($renderable);
        $output .= $this->render_table($renderable);
        $output .= $this->render_footer($renderable);
        return $output;
    }

    /**
     * Render the plugin description.
     *
     * @param \report_apocalypse\output\audit_$renderable
     * @return string HTML for display
     * @throws \coding_exception
     */
    public function render_description($renderable) {
        global $CFG, $DB;

        // This is an arbitrary date based on the statements from browser developers relating to "mid 2019".
        $datetimeofapocalypse = strtotime("2019-8-31 0:00");
        $daysremaining = floor(($datetimeofapocalypse - time()) / 86400);

        $output = '';
        if (!$renderable->download) {
            $output .= $this->header() . "\n";
            $imageurl = $CFG->wwwroot .'/report/apocalypse/pix/catalyst-logo.svg'; // Not using image_url for old site support.
            $output .= '<span class="catalyst-logo">' . "\n";
            $output .= '  <a href="https://www.catalyst.net.nz/products/moodle/?refer=report_apocalypse">' . "\n";
            $output .= '    <img src="' . $imageurl . '" width="181">'  . "\n";
            $output .= '  </a>'  . "\n";
            $output .= '</span>'  . "\n";

            if ($daysremaining > 0) {
                $output .= $this->heading(get_string('apocalypseinxdays', 'report_apocalypse',
                    $daysremaining))  . "\n";
            } else {
                $output .= $this->heading(get_string('apocalypseishere', 'report_apocalypse'))  . "\n";
            }

            $datetimelastaudit = $DB->get_field('task_scheduled', 'lastruntime', array('component' => 'report_apocalypse'));

            if ($datetimelastaudit > 0) {
                $output .= get_string('apocalypselastaudit', 'report_apocalypse', userdate($datetimelastaudit)) . "\n";
            } else {
                $output .= get_string('noaudit', 'report_apocalypse');
            }

            $output .= $this->box_start();
            $output .= get_string('description', 'report_apocalypse');
            $output .= $this->box_end();
        }

        return $output;
    }

    /**
     * Render the html for the audit table.
     *
     * @param \report_apocalypse\output\audit_table $renderable
     *
     * @return string HTML for display
     */
    public function render_table($renderable) {

        ob_start();
        $renderable->out($renderable->pagesize, true);
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

    /**
     * @param \report_apocalypse\output\audit_table $renderable
     *
     * @return string HTML for display
     */
    public function render_footer(audit_table $renderable) {

        if (!$renderable->download) {
            return $this->footer();
        }
    }
}
