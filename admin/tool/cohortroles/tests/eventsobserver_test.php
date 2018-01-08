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
 * Unit test for event observer.
 *
 * @package    tool_cohortroles
 * @author     Carlos Escobedo <http://www.twitter.com/carlosagile>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  2018 Carlos Escobedo <http://www.twitter.com/carlosagile>)
 */

defined('MOODLE_INTERNAL') || die();

use tool_cohortroles\api;

/**
 * Class tool_cohortroles_eventobservers_testcase
 *
 * @package    tool_cohortroles
 * @author     Carlos Escobedo <http://www.twitter.com/carlosagile>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  2018 Carlos Escobedo <http://www.twitter.com/carlosagile>)
 */
class tool_cohortroles_eventobservers_testcase extends advanced_testcase {
    /** @var stdClass $cohort */
    protected $cohort = null;

    /** @var stdClass $userassignto */
    protected $userassignto = null;

    /** @var stdClass $userassignto2 */
    protected $userassignto2 = null;

    /** @var stdClass $userassignto3 */
    protected $userassignto3 = null;

    /** @var stdClass $role */
    protected $role = null;

    /**
     * Setup function- we will create users, cohort and role.
     */
    protected function setUp() {
        global $DB;

        $this->resetAfterTest(true);

        // Create some users.
        $this->userassignto = $this->getDataGenerator()->create_user();
        $this->userassignto2 = $this->getDataGenerator()->create_user();
        $this->userassignto3 = $this->getDataGenerator()->create_user();
        // Create cohort and role.
        $this->cohort = $this->getDataGenerator()->create_cohort();
        $this->roleid = create_role('Sausage Roll', 'sausageroll', 'mmmm');
    }

    /**
     * Test observer for user delete event.
     */
    public function test_user_deleted() {
        global $DB;

        $this->setAdminUser();
        $params = (object) array(
            'userid' => $this->userassignto->id,
            'roleid' => $this->roleid,
            'cohortid' => $this->cohort->id
        );
        $result = api::create_cohort_role_assignment($params);
        $params = (object) array(
            'userid' => $this->userassignto2->id,
            'roleid' => $this->roleid,
            'cohortid' => $this->cohort->id
        );
        $result = api::create_cohort_role_assignment($params);

        // Check number of cohort role assignments.
        $count = api::count_cohort_role_assignments();
        $this->assertEquals($count, 2);

        // Delete user to verify the observer.
        delete_user($this->userassignto);

        // Check the new number of cohort role assignments.
        $count = api::count_cohort_role_assignments();
        $this->assertEquals($count, 1);
    }
}