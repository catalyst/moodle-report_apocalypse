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
 * @package     type_plugin
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_apocalypse\output;

defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;
use report_apocalypse\apocalypse_datetime;
use report_apocalypse\audit_manager;

/**
 * Renderer class for audit table.
 *
 * @package report_apocalypse\output
 */
class renderer extends plugin_renderer_base {

    public function render_audit_table(audit_table $renderable) {
        $output = $this->render_description($renderable);
        $output .= $this->render_table($renderable);
        return $output;
    }

    /**
     * Render the plugin description.
     *
     * @throws \coding_exception
     */
    public function render_description(audit_table $renderable) {
        global $CFG, $OUTPUT;

        ob_start();
        if (!$renderable->download) {
            echo $this->header();
            $imageurl = $CFG->wwwroot .'/report/apocalypse/pix/catalyst-logo.svg'; // Not using image_url for old site support.
            echo '<span class="catalyst-logo">';
            echo '  <a href="https://www.catalyst.net.nz/products/moodle/?refer=report_apocalypse">';
            echo '    <img src="' . $imageurl . '" width="181">';
            echo '  </a>';
            echo '</span>';

            if (apocalypse_datetime::get_days_remaining() > 0) {
                echo $this->heading(get_string('apocalypseinxdays', 'report_apocalypse',
                    apocalypse_datetime::get_days_remaining()));
            } else {
                echo $this->heading(get_string('apocalypseishere', 'report_apocalypse'));
            }

            echo get_string('apocalypselastaudit', 'report_apocalypse', userdate(audit_manager::get_last_audit()->rundatetime));

            echo $this->box_start();
            echo get_string('description', 'report_apocalypse');
            echo $this->box_end();
        }
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

    /**
     * Render the html for the audit table.
     *
     * @param \report_apocalypse\output\audit_table $renderable
     *
     * @return string
     */
    public function render_table(audit_table $renderable) {

        ob_start();
        $renderable->out($renderable->pagesize, true);
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }
}