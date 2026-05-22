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
 * External API for creating folders in common repository.
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
use context_system;

/**
 * External service to create a folder inside common repository.
 *
 * @package    local_shared_files
 * @copyright  2026 Justaddwater <contact@justaddwater.in>
 * @author     Himanshu Saini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_shared_files_create_folder extends external_api {
    /**
     * Define parameters for create_folder().
     *
     * @return external_function_parameters
     */
    public static function create_folder_parameters() {
        return new external_function_parameters([
            'path'       => new external_value(PARAM_PATH, 'Relative path', VALUE_DEFAULT, ''),
            'foldername' => new external_value(PARAM_FILE, 'Folder name'),
            'sesskey'    => new external_value(PARAM_RAW, 'Session key'),
        ]);
    }

    /**
     * Create a folder inside the configured repository path.
     *
     * @param string $path Relative path
     * @param string $foldername Folder name
     * @param string $sesskey Session key for CSRF protection.
     * @return array Result data
     * @throws required_capability_exception
     */
    public static function create_folder($path, $foldername, $sesskey) {
        global $CFG;

        $params = self::validate_parameters(self::create_folder_parameters(), [
            'path'       => $path,
            'foldername' => $foldername,
            'sesskey'    => $sesskey,
        ]);
        require_sesskey();

        $context = context_system::instance();
        self::validate_context($context);

        require_capability('local/shared_files:manage', $context);

        require_once($CFG->libdir . '/filelib.php');

        $foldername = clean_filename($params['foldername']);
        if ($foldername === '' || $foldername === '.' || $foldername === '..') {
            return [
                'success' => false,
                'error' => get_string(
                    'invalidfoldername',
                    'local_shared_files'
                ),
            ];
        }

        $repo = get_config('local_shared_files', 'repo_path');
        if (empty($repo)) {
            return [
                'success' => false,
                'error' => get_string(
                    'repositorynotconfigured',
                    'local_shared_files'
                ),
            ];
        }

        $root = realpath($CFG->dataroot . '/repository/' . trim($repo, '/'));
        if ($root === false) {
            return [
                'success' => false,
                'error' => get_string(
                    'invalidrepositoryroot',
                    'local_shared_files'
                ),
            ];
        }

        $target = realpath($root . '/' . $params['path']);
        if ($target === false || strpos($target, $root) !== 0) {
            $target = $root;
        }

        $newdir = $target . '/' . $foldername;

        if (file_exists($newdir)) {
            return [
                'success' => false,
                'error' => get_string(
                    'folderexists',
                    'local_shared_files'
                ),
            ];
        }

        if (!@mkdir($newdir, 0755, false)) {
            return [
                'success' => false,
                'error' => get_string(
                    'failedtocreatefolder',
                    'local_shared_files'
                ),
            ];
        }

        return [
            'success' => true,
            'path'    => ($params['path'] === '' ? $foldername : trim($params['path'], '/') . '/' . $foldername),
        ];
    }

    /**
     * Define return structure for create_folder().
     *
     * @return external_single_structure
     */
    public static function create_folder_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success'),
            'path'    => new external_value(PARAM_PATH, 'Created folder path', VALUE_OPTIONAL),
            'error'   => new external_value(PARAM_TEXT, 'Error message', VALUE_OPTIONAL),
        ]);
    }
}
