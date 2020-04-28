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
 * Content bank repository browser unit tests.
 *
 * @package    repository_contentbank
 * @copyright  2020 Mihail Geshoski <mihail@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once("$CFG->dirroot/repository/lib.php");

/**
 * Tests for the content bank browser class.
 *
 * @package    repository_contentbank
 * @copyright  2020 Mihail Geshoski <mihail@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository_contentbank_browser_testcase extends advanced_testcase {

    /**
     * Test get_content() in the system context with users that have capability to access/view content bank content
     * within the system context. By default, every authenticated user should be able to access/view the content in
     * the system context.
     */
    public function test_get_content_system_context_user_has_capabilities() {
        global $DB;

        $this->resetAfterTest(true);

        $systemcontext = \context_system::instance();
        // Create a course category $coursecategory.
        $coursecategory = $this->getDataGenerator()->create_category(['name' => 'Category']);
        $coursecatcontext = \context_coursecat::instance($coursecategory->id);

        // Get the default 'Miscellaneous' category.
        $miscellaneouscat = \core_course_category::get(1);
        $miscellaneouscatcontext = \context_coursecat::instance($miscellaneouscat->id);

        // Create course.
        $course = $this->getDataGenerator()->create_course(['category' => $coursecategory->id]);

        $admin = $admin = get_admin();
        // Create a user (not enrolled in a course).
        $user = $this->getDataGenerator()->create_user();

        // Add some content to the content bank.
        $generator = $this->getDataGenerator()->get_plugin_generator('core_contentbank');
        // Add some content bank files in the system context.
        $contentbankcontents = $generator->generate_contentbank_data('contenttype_h5p', 3, $admin->id,
            $systemcontext, true);

        // Log in as admin.
        $this->setUser($admin);
        // Get the content bank nodes displayed to the admin in the system context.
        $browser = new \repository_contentbank\browser\contentbank_browser_context_system($systemcontext);
        $repositorycontentnodes = $browser->get_content();
        // All content nodes should be available to the admin user.
        // There should be a total of 5 nodes, 3 file nodes representing the existing content bank files in the
        // system context and 2 folder nodes representing the default course category 'Miscellaneous' and 'Category'.
        $this->assertCount(5, $repositorycontentnodes);
        $contextfolders = array(
            array('name' => 'Miscellaneous', 'contextid' => $miscellaneouscatcontext->id),
            array('name' => 'Category', 'contextid' => $coursecatcontext->id)
        );
        $expected = $this->generate_expected_content($contextfolders, $contentbankcontents);
        $this->assertEquals($expected, $repositorycontentnodes, '', 0.0, 10, true);

        // Log in as a user.
        $this->setUser($user);
        // Get the content bank nodes displayed to a authenticated user in the system context.
        $browser = new \repository_contentbank\browser\contentbank_browser_context_system($systemcontext);
        $repositorycontentnodes = $browser->get_content();
        // There should be 3 nodes representing the existing content bank files in the system context.
        // The course category context folder node should be ignored as the user does not have an access to
        // the content of the category's courses.
        $this->assertCount(3, $repositorycontentnodes);
        $expected = $this->generate_expected_content([], $contentbankcontents);
        $this->assertEquals($expected, $repositorycontentnodes, '', 0.0, 10, true);

        // Enrol the user as an editing teacher in the course.
        $editingteacherrole = $DB->get_field('role', 'id', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $editingteacherrole);

         // Get the content bank nodes displayed to the editing teacher in the system context.
        $browser = new \repository_contentbank\browser\contentbank_browser_context_system($systemcontext);
        $repositorycontentnodes = $browser->get_content();
        // All content nodes should now be available to the editing teacher.
        // There should be a total of 4 nodes, 3 file nodes representing the existing content bank files in the
        // system context and 1 folder node representing the course category 'Category' (The editing teacher is now
        // enrolled in a course from the category).
        $this->assertCount(4, $repositorycontentnodes);
        $contextfolders = array(
            array('name' => 'Category', 'contextid' => $coursecatcontext->id)
        );
        $expected = $this->generate_expected_content($contextfolders, $contentbankcontents);
        $this->assertEquals($expected, $repositorycontentnodes, '', 0.0, 10, true);
    }

    /**
     * Test get_content() in the system context with users that do not have a capability to access/view content bank
     * content within the system context. By default, every non-authenticated user should not be able to access/view
     * the content in the system context.
     */
    public function test_get_content_system_context_user_missing_capabilities() {
        $this->resetAfterTest(true);

        $systemcontext = \context_system::instance();

        $admin = get_admin();
        // Add some content to the content bank.
        $generator = $this->getDataGenerator()->get_plugin_generator('core_contentbank');
        // Add some content bank files in the system context.

        $generator->generate_contentbank_data('contenttype_h5p', 3, $admin->id, $systemcontext, true);
        // Log out.
        $this->setUser();
        // Get the content bank nodes displayed to a non-authenticated user in the system context.
        $browser = new \repository_contentbank\browser\contentbank_browser_context_system($systemcontext);
        $repositorycontents = $browser->get_content();
        // Content nodes should not be available to the non-authenticated user in the system context.
        $this->assertCount(0, $repositorycontents);
    }

    /**
     * Test get_content() in the course category context with users that have capability to access/view content
     * bank content within the course category context. By default, every authenticated user that has access to
     * any category child contexts should be able to access/view the content in the course category context.
     */
    public function test_get_content_course_category_context_user_has_capabilities() {
        $this->resetAfterTest(true);

        // Create a course category.
        $category = $this->getDataGenerator()->create_category(['name' => 'Category']);
        $coursecatcontext = \context_coursecat::instance($category->id);
        // Create course.
        $course = $this->getDataGenerator()->create_course(['fullname' => 'Course', 'category' => $category->id]);
        $coursecontext = \context_course::instance($course->id);

        $admin = get_admin();
        // Create editing teacher enrolled in course.
        $editingteacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');

        // Add some content to the content bank.
        $generator = $this->getDataGenerator()->get_plugin_generator('core_contentbank');
        // Add some content bank files in the course category context.
        $contentbankcontents = $generator->generate_contentbank_data('contenttype_h5p', 3, $admin->id,
            $coursecatcontext, true);

        $this->setUser($admin);
        // Get the content bank nodes displayed to the admin in the course category context.
        $browser = new \repository_contentbank\browser\contentbank_browser_context_coursecat($coursecatcontext);
        $repositorycontents = $browser->get_content();
        // All content nodes should be available to the admin user.
        // There should be a total of 4 nodes, 3 file nodes representing the existing content bank files in the
        // course category context and 1 folder node representing the course 'Course'.
        $this->assertCount(4, $repositorycontents);
        $contextfolders = array(
            array('name' => 'Course', 'contextid' => $coursecontext->id)
        );
        $expected = $this->generate_expected_content($contextfolders, $contentbankcontents);
        $this->assertEquals($expected, $repositorycontents, '', 0.0, 10, true);

        // Log in as a teacher enrolled in a child course.
        $this->setUser($editingteacher);
        // Get the content bank nodes displayed to the editing teacher in the course category context.
        $browser = new \repository_contentbank\browser\contentbank_browser_context_coursecat($coursecatcontext);
        $repositorycontents = $browser->get_content();
        // All content nodes should now be available to the editing teacher.
        // There should be a total of 4 nodes, 3 file nodes representing the existing content bank files in the
        // course category context and 1 folder node representing the course 'Course' (The editing teacher is now
        // enrolled in a course from the category).
        $this->assertCount(4, $repositorycontents);
        $expected = $this->generate_expected_content($contextfolders, $contentbankcontents);
        $this->assertEquals($expected, $repositorycontents, '', 0.0, 10, true);
    }

    /**
     * Test get_content() in the course category context with users that do not have capability to access/view content
     * bank content within the course category context. By default, every authenticated user should not be able to
     * access/view the content in the course category context.
     */
    public function test_get_content_course_category_context_user_missing_capabilities() {
        $this->resetAfterTest(true);

         // Create a course category.
        $category = $this->getDataGenerator()->create_category(['name' => 'Category']);
        // Create course.
        $course = $this->getDataGenerator()->create_course(['fullname' => 'Course', 'category' => $category->id]);
        // Create teacher enrolled in course.
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $admin = get_admin();
        // Add some content to the content bank.
        $generator = $this->getDataGenerator()->get_plugin_generator('core_contentbank');
        // Add some content bank files in the course category context.
        $coursecatcontext = \context_coursecat::instance($category->id);
        $generator->generate_contentbank_data('contenttype_h5p', 3, $admin->id,
            $coursecatcontext, true);

        // Log in as teacher.
        $this->setUser($teacher);
        // Get the content bank nodes displayed to a non-teacher in the course category context.
        $browser = new \repository_contentbank\browser\contentbank_browser_context_coursecat($coursecatcontext);
        $repositorycontents = $browser->get_content();
        // Content nodes should not be available to a non-editing teacher in the course category context.
        $this->assertCount(0, $repositorycontents);

        // Log out.
        $this->setUser();
        // Get the content bank nodes displayed to a non-authenticated user in the course category context.
        $browser = new \repository_contentbank\browser\contentbank_browser_context_coursecat($coursecatcontext);
        $repositorycontents = $browser->get_content();
        // Content nodes should not be available to the non-authenticated user in the course category context.
        $this->assertCount(0, $repositorycontents);
    }

    /**
     * Test get_content() in the course context with users that have capability to access/view content
     * bank content within the course context. By default, admin, managers, course creators, editing teachers enrolled
     * in the course should be able to access/view the content.
     */
    public function test_get_content_course_context_user_has_capabilities() {
        $this->resetAfterTest(true);

        // Create course1.
        $course = $this->getDataGenerator()->create_course(['fullname' => 'Course']);
        $coursecontext = \context_course::instance($course->id);

        $admin = get_admin();
        // Create editing teacher enrolled in course.
        $editingteacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');

        // Add some content to the content bank.
        $generator = $this->getDataGenerator()->get_plugin_generator('core_contentbank');
        // Add some content bank files in the course context.
        $contentbankcontents = $generator->generate_contentbank_data('contenttype_h5p', 3, $admin->id,
            $coursecontext, true);

        $this->setUser($admin);
        // Get the content bank nodes displayed to the admin in the course context.
        $browser = new \repository_contentbank\browser\contentbank_browser_context_course($coursecontext);
        $repositorycontents = $browser->get_content();
        // All content nodes should be available to the admin user.
        // There should be 3 file nodes representing the existing content bank files in the
        // course context.
        $this->assertCount(3, $repositorycontents);
        $expected = $this->generate_expected_content([], $contentbankcontents);
        $this->assertEquals($expected, $repositorycontents, '', 0.0, 10, true);

        // Log in as an editing teacher.
        $this->setUser($editingteacher);
        // All content nodes should also be available to the editing teacher.
        // Get the content bank nodes displayed to the editing teacher in the course context.
        $browser = new \repository_contentbank\browser\contentbank_browser_context_course($coursecontext);
        $repositorycontents = $browser->get_content();
        // There should be 3 file nodes representing the existing content bank files in the
        // course context.
        $this->assertCount(3, $repositorycontents);
        $expected = $this->generate_expected_content([], $contentbankcontents);
        $this->assertEquals($expected, $repositorycontents, '', 0.0, 10, true);
    }

    /**
     * Test get_content() in the course context with users that do not have capability to access/view content
     * bank content within the course context. By default, every user which is not an admin, manager, course creator,
     * editing teacher enrolled in the course should not be able to access/view the content.
     */
    public function test_get_content_course_context_user_missing_capabilities() {
        $this->resetAfterTest(true);

        // Create course1.
        $course1 = $this->getDataGenerator()->create_course(['fullname' => 'Course1']);
        $course1context = \context_course::instance($course1->id);
        // Create course2.
        $course2 = $this->getDataGenerator()->create_course(['fullname' => 'Course2']);
        $course2context = \context_course::instance($course2->id);

        $admin = get_admin();
        // Create non-editing teacher enrolled in course1.
        $teacher = $this->getDataGenerator()->create_and_enrol($course1, 'teacher');
         // Create editing teacher enrolled in course1.
        $editingteacher = $this->getDataGenerator()->create_and_enrol($course1, 'editingteacher');

        // Add some content to the content bank.
        $generator = $this->getDataGenerator()->get_plugin_generator('core_contentbank');
        // Add some content bank files in the course1 context.
        $generator->generate_contentbank_data('contenttype_h5p', 2, $admin->id,
            $course1context, true);
        // Add some content bank files in the course2 context.
        $generator->generate_contentbank_data('contenttype_h5p', 3, $admin->id,
            $course2context, true);

        // Log in as a non-editing teacher.
        $this->setUser($teacher);
        // Get the content bank nodes displayed to the non-editing teacher in the course1 context.
        $browser = new \repository_contentbank\browser\contentbank_browser_context_course($course1context);
        $repositorycontents = $browser->get_content();
        // Content nodes should not be available to the teacher in the course1 context.
        $this->assertCount(0, $repositorycontents);

        // Log in as editing teacher.
        $this->setUser($editingteacher);
        // Get the content bank nodes displayed to the editing teacher in the course2 context.
        $browser = new \repository_contentbank\browser\contentbank_browser_context_course($course2context);
        $repositorycontents = $browser->get_content();
        // Content nodes should not be available to the teacher in the course2 context. The editing teacher is not
        // enrolled in this course.
        $this->assertCount(0, $repositorycontents);
    }

    /**
     * Test get_navigation() in the system context.
     */
    public function test_get_navigation_system_context() {
        $this->resetAfterTest(true);

        $systemcontext = \context_system::instance();

        $browser = new \repository_contentbank\browser\contentbank_browser_context_system($systemcontext);
        $navigation = $browser->get_navigation();
        // The navigation array should contain only 1 element, representing the system navigation node.
        $this->assertCount(1, $navigation);
        $expected = [
            \repository_contentbank\helper::create_navigation_node($systemcontext)
        ];
        $this->assertEquals($expected, $navigation);
    }

    /**
     * Test get_navigation() in the course category context.
     */
    public function test_get_navigation_course_category_context() {
        $this->resetAfterTest(true);

        $systemcontext = \context_system::instance();
        // Create a course category.
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $coursecatcontext = \context_coursecat::instance($category->id);

        $browser = new \repository_contentbank\browser\contentbank_browser_context_coursecat($coursecatcontext);
        $navigation = $browser->get_navigation();
        // The navigation array should contain 2 elements, representing the system and course category
        // navigation nodes.
        $this->assertCount(2, $navigation);
        $expected = [
            \repository_contentbank\helper::create_navigation_node($systemcontext),
            \repository_contentbank\helper::create_navigation_node($coursecatcontext)
        ];
        $this->assertEquals($expected, $navigation);
    }

    /**
     * Test get_navigation() in the course context.
     */
    public function test_get_navigation_course_context() {
        $this->resetAfterTest(true);

        $systemcontext = \context_system::instance();
        // Create a course category.
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $coursecatcontext = \context_coursecat::instance($category->id);
        // Create a course.
        $course = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $coursecontext = \context_course::instance($course->id);

        $browser = new \repository_contentbank\browser\contentbank_browser_context_course($coursecontext);
        $navigation = $browser->get_navigation();
        // The navigation array should contain 3 elements, representing the system, course category and course
        // navigation nodes.
        $this->assertCount(3, $navigation);
        $expected = [
            \repository_contentbank\helper::create_navigation_node($systemcontext),
            \repository_contentbank\helper::create_navigation_node($coursecatcontext),
            \repository_contentbank\helper::create_navigation_node($coursecontext)
        ];
        $this->assertEquals($expected, $navigation);
    }

    /**
     * Generate the expected array of content bank nodes.
     *
     * @param array $contextfolders The array containing the expected folder nodes
     * @param array $contentbankcontents The array containing the expected contents
     * @return array The expected array of content bank nodes
     */
    private function generate_expected_content(array $contextfolders = [],
           array $contentbankcontents = []): array {

        $expected = array();
        if (!empty($contextfolders)) {
            foreach ($contextfolders as $contextfolder) {
                $expected[] = \repository_contentbank\helper::create_context_folder_node($contextfolder['name'],
                    base64_encode(json_encode(['contextid' => $contextfolder['contextid']])));
            }
        }
        if (!empty($contentbankcontents)) {
            foreach ($contentbankcontents as $content) {
                $file = $content->get_file();
                $expected[] = \repository_contentbank\helper::create_contentbank_file_node($file);
            }
        }
        return $expected;
    }
}
