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
 * External API for fetching folder tree of common repository.
 *
 * @package    local_shared_files
 * @copyright  2026 Justaddwater <contact@justaddwater.in>
 * @author     Himanshu Saini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use context_system;

/**
 * External service to return directory tree of common repository.
 *
 * @package    local_shared_files
 * @copyright  2026 Justaddwater <contact@justaddwater.in>
 * @author     Himanshu Saini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_shared_files_get_tree extends external_api {
    /**
     * Define parameters for get_tree().
     *
     * @return external_function_parameters
     */
    public static function get_tree_parameters() {
        return new external_function_parameters([
            'sesskey' => new external_value(PARAM_RAW, 'Session key'),
        ]);
    }

    /**
     * Return directory tree from repository root.
     *
     * @param string $sesskey Session key for CSRF protection.
     * Return directory tree from repository root.
     *
     * @return array Folder tree
     * @throws required_capability_exception
     */
    public static function get_tree($sesskey) {
        global $CFG;

        $params = self::validate_parameters(
            self::get_tree_parameters(),
            ['sesskey' => $sesskey]
        );

        require_sesskey();

        $context = context_system::instance();
        self::validate_context($context);

        // Capability check.
        require_capability('local/shared_files:view', $context);

        $repo = get_config('local_shared_files', 'repo_path');
        if (empty($repo)) {
            return [];
        }

        $root = realpath($CFG->dataroot . '/repository/' . trim($repo, '/'));
        if ($root === false) {
            return [];
        }

        return self::scan_tree($root, $root);
    }

    /**
     * Recursively scan directory structure.
     *
     * @param string $dir Current directory
     * @param string $root Root directory
     * @return array
     */
    private static function scan_tree($dir, $root) {
        $items = [];

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $full = $dir . '/' . $item;
            if (!is_dir($full)) {
                continue;
            }

            $rel = ltrim(str_replace($root, '', $full), '/');

            $items[] = [
                'name'     => $item,
                'path'     => $rel,
                'children' => self::scan_tree($full, $root),
            ];
        }

        return $items;
    }

    /**
     * Define return structure for get_tree().
     *
     * @return external_multiple_structure
     */
    public static function get_tree_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'name'     => new external_value(PARAM_TEXT, 'Folder name'),
                'path'     => new external_value(PARAM_PATH, 'Relative path'),
                'children' => new external_multiple_structure(
                    new external_single_structure([
                        'name'     => new external_value(PARAM_TEXT),
                        'path'     => new external_value(PARAM_PATH),
                        'children' => new external_multiple_structure(
                            new external_value(PARAM_RAW),
                            'Nested children',
                            VALUE_OPTIONAL
                        ),
                    ]),
                    VALUE_OPTIONAL
                ),
            ])
        );
    }
}
