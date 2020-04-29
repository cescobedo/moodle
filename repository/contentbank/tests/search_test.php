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
 * Content bank repository search unit tests.
 *
 * @package    repository_contentbank
 * @copyright  2020 Mihail Geshoski <mihail@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once("$CFG->dirroot/repository/lib.php");

/**
 * Tests for the content bank search class.
 *
 * @package    repository_contentbank
 * @copyright  2020 Mihail Geshoski <mihail@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository_contentbank_search_testcase extends advanced_testcase {

    /**
     * Test get_search_contents() by searching for a an existing pattern found within the name of content files.
     */
    public function test_get_search_contents_existing_name_pattern() {
        $this->resetAfterTest(true);

        $systemcontext = \context_system::instance();

        $admin = get_admin();
        // Add some content to the content bank.
        $generator = $this->getDataGenerator()->get_plugin_generator('core_contentbank');
        // Add a content bank file in the system context.
        $systemcontents1 = $generator->generate_contentbank_data('contenttype_h5p', 1, $admin->id,
            $systemcontext, true, 'systemcontentfile1.h5p');
        $systemcontentfile1 = reset($systemcontents1)->get_file();
        // Add another content bank file in the system context.
        $systemcontents2 = $generator->generate_contentbank_data('contenttype_h5p', 1, $admin->id,
            $systemcontext, true, 'systemcontentfile2.h5p');
        $systemcontentfile2 = reset($systemcontents2)->get_file();
        // Add another content bank file in the system context.
        $systemcontents3 = $generator->generate_contentbank_data('contenttype_h5p', 1, $admin->id,
            $systemcontext, true, 'somesystemfile.h5p');
        $systemcontentfile3 = reset($systemcontents3)->get_file();

        // Log in as admin.
        $this->setUser($admin);
        // Search for content bank files having the pattern 'contentfile' within their name.
        $search = 'contentfile';
        $searchcontentnodes = \repository_contentbank\search\contentbank_search::get_search_contents($search);
        // The search should return 2 file nodes.
        $this->assertCount(2, $searchcontentnodes);
        $expected = array(
            \repository_contentbank\helper::create_contentbank_file_node($systemcontentfile1),
            \repository_contentbank\helper::create_contentbank_file_node($systemcontentfile2),
        );
        $this->assertEquals($expected, $searchcontentnodes, '', 0.0, 10, true);

        // Search for content bank files having the pattern 'some' within their name.
        $search = 'some';
        $searchcontentnodes = \repository_contentbank\search\contentbank_search::get_search_contents($search);
        // The search should return 1 file node.
        $this->assertCount(1, $searchcontentnodes);
        $expected = array(
            \repository_contentbank\helper::create_contentbank_file_node($systemcontentfile3),
        );
        $this->assertEquals($expected, $searchcontentnodes, '', 0.0, 10, true);
    }

    /**
     * Test get_search_contents() by searching for a pattern which does not exist within the name of content files.
     */
    public function test_get_search_contents_non_existing_name_pattern() {
        $this->resetAfterTest(true);

        $systemcontext = \context_system::instance();

        $admin = get_admin();
        // Add some content to the content bank.
        $generator = $this->getDataGenerator()->get_plugin_generator('core_contentbank');
        // Add a content bank file in the system context.
        $generator->generate_contentbank_data('contenttype_h5p', 1, $admin->id,
            $systemcontext, true, 'systemcontentfile1.h5p');
        // Add another content bank file in the system context.
        $generator->generate_contentbank_data('contenttype_h5p', 1, $admin->id,
            $systemcontext, true, 'systemcontentfile2.h5p');

        // Log in as admin.
        $this->setUser($admin);
        // Search for content bank files having the pattern 'somename' within their name.
        $search = 'somename';
        $searchcontentnodes = \repository_contentbank\search\contentbank_search::get_search_contents($search);
        // The search should return 0 file nodes.
        $this->assertCount(0, $searchcontentnodes);
    }

    /**
     * Test get_search_contents() by doing a case-insensitive search for a pattern which exists within
     * the name of content files.
     */
    public function test_get_search_contents_case_insensitive() {
        $this->resetAfterTest(true);

        $systemcontext = \context_system::instance();

        $admin = get_admin();
        // Add some content to the content bank.
        $generator = $this->getDataGenerator()->get_plugin_generator('core_contentbank');
        // Add a content bank file in the system context.
        $systemcontents = $generator->generate_contentbank_data('contenttype_h5p', 1, $admin->id,
            $systemcontext, true, 'systemcontentfile.h5p');
        $systemcontentfile = reset($systemcontents)->get_file();

        // Log in as admin.
        $this->setUser($admin);
        // Search for content bank files having the pattern 'CONTENT' within their name.
        $search = 'CONTENT';
        $searchcontentnodes = \repository_contentbank\search\contentbank_search::get_search_contents($search);
        // The search should return 1 file node.
        $this->assertCount(1, $searchcontentnodes);
        $expected = array(
            \repository_contentbank\helper::create_contentbank_file_node($systemcontentfile),
        );
        $this->assertEquals($expected, $searchcontentnodes, '', 0.0, 10, true);
    }

    /**
     * Test get_content() with users that have capability to access/view all existing content bank content.
     * By default, admins, managers should be able to access/view all content.
     */
    public function test_get_search_contents_user_can_access_all_content() {
        $this->resetAfterTest(true);

        // Create a course in 'Miscellaneous' category.
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);
        // Create a course category without a course.
        $category = $this->getDataGenerator()->create_category();
        $categorycontext = \context_coursecat::instance($category->id);

        $admin = get_admin();
        // Add some content to the content bank in different contexts.
        $generator = $this->getDataGenerator()->get_plugin_generator('core_contentbank');
        // Add a content bank file in the category context.
        $categorycontents = $generator->generate_contentbank_data('contenttype_h5p', 1, $admin->id,
            $categorycontext, true, 'categorycontentfile.h5p');
        $categorycontentfile = reset($categorycontents)->get_file();
        // Add a content bank file in the course context.
        $coursecontents = $generator->generate_contentbank_data('contenttype_h5p', 1, $admin->id,
            $coursecontext, true, 'coursecontentfile.h5p');
        $coursecontentfile = reset($coursecontents)->get_file();

        // Log in as admin.
        $this->setUser($admin);

        // Search for a pattern found in the name of multiple content files.
        $search = 'contentfile';
        $searchcontentnodes = \repository_contentbank\search\contentbank_search::get_search_contents($search);
        // All content files which name matches the search criteria should be available to the admin user.
        // The search should return 2 file nodes.
        $this->assertCount(2, $searchcontentnodes);
        $expected = array(
            \repository_contentbank\helper::create_contentbank_file_node($categorycontentfile),
            \repository_contentbank\helper::create_contentbank_file_node($coursecontentfile),
        );
        $this->assertEquals($expected, $searchcontentnodes, '', 0.0, 10, true);
    }

    /**
     * Test get_content() with users that have capability to access/view only certain existing content bank content.
     * By default, editing teacher should be able to access content in the courses they are enrolled,
     * course categories of the enrolled courses and system content.
     */
    public function test_get_search_contents_user_can_access_certain_content() {
        $this->resetAfterTest(true);

        // Create course1.
        $course1 = $this->getDataGenerator()->create_course();
        $course1context = \context_course::instance($course1->id);
        // Create course2.
        $course2 = $this->getDataGenerator()->create_course();
        $course2context = \context_course::instance($course2->id);

        $admin = get_admin();
        // Create and enrol an editing teacher in course1.
        $editingteacher = $this->getDataGenerator()->create_and_enrol($course1, 'editingteacher');

        // Add some content to the content bank in different contexts.
        $generator = $this->getDataGenerator()->get_plugin_generator('core_contentbank');
        // Add a content bank file in the course1 context.
        $course1contents = $generator->generate_contentbank_data('contenttype_h5p', 1, $admin->id,
            $course1context, true, 'coursecontentfile.h5p');
        $course1contentfile = reset($course1contents)->get_file();
        // Add a content bank file in the course2 context.
        $generator->generate_contentbank_data('contenttype_h5p', 1, $admin->id,
            $course2context, true, 'coursecontentfile.h5p');

        // Log in as an editing teacher.
        $this->setUser($editingteacher);
        // Search for a pattern found in the name of existing content files.
        $search = 'contentfile';
        $searchcontentnodes = \repository_contentbank\search\contentbank_search::get_search_contents($search);
        // The search should return 1 file node.
        $this->assertCount(1, $searchcontentnodes);
        $expected = array(
            \repository_contentbank\helper::create_contentbank_file_node($course1contentfile),
        );
        $this->assertEquals($expected, $searchcontentnodes, '', 0.0, 10, true);
    }
}
