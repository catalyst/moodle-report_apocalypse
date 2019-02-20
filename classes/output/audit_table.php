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

use report_apocalypse\audit_activity;
use report_apocalypse\audit_manager;
use table_sql;
use moodle_url;
use renderable;

/**
 * Renderable class for manage rules page.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class audit_table extends table_sql implements renderable {

    protected $page;

    protected $perpage;

    protected $systemcontext;

    public function __construct(string $uniqueid, moodle_url $url, int $page=0, int $perpage=50, $download='') {
        parent::__construct($uniqueid);

        $this->set_attribute('id', 'report_apocalypse_table');
        $this->set_attribute('class', 'generaltable generalbox');
        $this->show_download_buttons_at(array(TABLE_P_BOTTOM));
        $this->define_columns(array('category', 'courselink', 'type', 'activitylink', 'html5present'));
        $this->define_headers(array(
            get_string('category'),
            get_string("course"),
            get_string('activitytype', 'report_apocalypse'),
            get_string("activity"),
            get_string('dualmode', 'report_apocalypse')
        ));
        $systemcontext = \context_system::instance();
        $this->context = $systemcontext;
        $this->baseurl = $url;
        $this->page = $page;
        $this->perpage = $perpage;
        $this->is_downloading($download, 'flash-apocalypse-report');
        $this->sortable(true);

    }

    public function col_category($activity) {
        return $activity->get_category();
    }

    public function col_courselink($activity) {
        return $activity->get_courselink();
    }

    public function col_type($activity) {
        return $activity->get_type();
    }

    public function col_activitylink($activity) {
        return $activity->get_activitylink();
    }

    public function col_html5present($activity) {
        return $activity->get_html5present();
    }

    public function query_db($pagesize, $useinitialsbar=true) {

        $total = audit_manager::count_audit_activities();
        $this->pagesize($pagesize, $total);
//        if (!$this->download) {
//            $this->pagesize($perpage, $audit->count_records());
//            $limitfrom = $this->get_page_start();
//            $limitnum = $this->get_page_size();
//        }
        $activities = audit_manager::get_audit_results_paginated($this->get_page_start(), $this->get_page_size());
        $this->rawdata = $activities;
        if ($useinitialsbar) {
            $this->initialbars($total > $pagesize);
        }

    }


}
