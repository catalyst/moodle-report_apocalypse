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

namespace report_apocalypse;

use \core\task\scheduled_task;

/**
 * Scheduled task class for conduct an audit of flash content.
 *
 * @package report_apocalypse
 */
class scheduled_flash_audit extends scheduled_task {

    /**
     * Get the task name as shown in admin screens.
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_name() {
        return get_string('scheduledflashaudit', 'report_apocalypse');
    }

    /**
     * Execute the task
     */
    public function execute() {

        $audit = new flash_audit();

        $audit->run()->store();

    }
}