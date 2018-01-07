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
 * Observer class.
 *
 * @package    tool_cohortroles
 * @author     Carlos Escobedo <http://www.twitter.com/carlosagile>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  2017 Carlos Escobedo <http://www.twitter.com/carlosagile>)
 */

namespace tool_cohortroles;

defined('MOODLE_INTERNAL') || die();

/**
 * Observer class containing method to keep sync cohort role assignments.
 *
 * @package    tool_cohortroles
 * @author     Carlos Escobedo <http://www.twitter.com/carlosagile>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  2018 Carlos Escobedo <http://www.twitter.com/carlosagile>)
 */
class eventobservers {
    /**
     * Observer user deleted event and delete user cohort role assignments.
     *
     * @param \core\event\user_deleted $event the event object.
     */
    public static function user_deleted(\core\event\user_deleted $event) {
        $userid = $event->objectid;
        $all = cohort_role_assignment::get_records(array('userid' => $userid), 'id');
        foreach ($all as $cra) {
            \tool_cohortroles\api::delete_cohort_role_assignment($cra->get_id());
        }
    }
}