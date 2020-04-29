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
 * Testable contenttype plugin class.
 *
 * @package    core_contentbank
 * @category   test
 * @copyright  2020 Sara Arjona <sara@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace contenttype_testable;

/**
 * Testable contenttype plugin class.
 *
 * @package    core_contentbank
 * @copyright  2020 Sara Arjona <sara@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class contenttype extends \core_contentbank\contenttype {

    /** Feature for testing */
    const CAN_TEST = 'test';

    /** @var array Additional features for testing */
    public static $featurestotest;

    /**
     * Returns the URL where the content will be visualized.
     *
     * @param stdClass $record  Th content to be displayed.
     * @return string            URL where to visualize the given content.
     */
    public function get_view_url(\stdClass $record): string {
        $fileurl = $this->get_file_url($record->id);
        $url = $fileurl."?forcedownload=1";

        return $url;
    }

    /**
     * Returns the HTML code to render the icon for content bank contents.
     *
     * @param string $contentname   The contentname to add as alt value to the icon.
     * @return string               HTML code to render the icon
     */
    public function get_icon(string $contentname): string {
        global $OUTPUT;

        return $OUTPUT->pix_icon('f/archive-64', $contentname, 'moodle', ['class' => 'iconsize-big']);
    }

    /**
     * Return an array of implemented features by this plugin.
     *
     * @return array
     */
    protected function get_implemented_features(): array {
        $features = [self::CAN_TEST];

        if (!empty(self::$featurestotest)) {
            $features = array_merge($features, self::$featurestotest);
        }

        return $features;
    }

    /**
     * Return an array of extensions this plugin could manage.
     *
     * @return array
     */
    public function get_manageable_extensions(): array {
        return  ['.txt', '.png', '.h5p'];
    }

    /**
     * Returns the list of different types of the given content type.
     *
     * @return array
     */
    public function get_contenttype_types(): array {
        $type = new \stdClass();
        $type->typename = 'testable';

        return [$type];
    }

    /**
     * Returns true, so the user has permission on the feature.
     *
     * @return bool     True if content could be edited or created. False otherwise.
     */
    final public function can_test2(): bool {
        if (!$this->is_feature_supported('test2')) {
            return false;
        }

        return true;
    }
}
