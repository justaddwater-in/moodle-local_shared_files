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
 * External API for deleting files or folders from common repository.
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
 * External service to delete a file or folder from common repository.
 *
 * @package    local_shared_files
 * @copyright  2026 Justaddwater <contact@justaddwater.in>
 * @author     Himanshu Saini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_shared_files_delete extends external_api {
    /**
     * Define parameters for delete_item().
     *
     * @return external_function_parameters
     */
    public static function delete_item_parameters() {
        return new external_function_parameters([
            'path' => new external_value(PARAM_PATH, 'Relative path to delete'),
            'sesskey' => new external_value(PARAM_RAW, 'Session key'),
        ]);
    }

    /**
     * Delete a file or folder from the shared repository.
     *
     * @param string $path Relative path to delete.
     * @param string $sesskey Session key for CSRF protection.
     * @return array An array containing deletion result.
     */
    public static function delete_item($path, $sesskey) {
        global $CFG;

        // Validate parameters.
        $params = self::validate_parameters(
            self::delete_item_parameters(),
            [
                'path'    => $path,
                'sesskey' => $sesskey,
            ]
        );
        require_sesskey();

        $context = context_system::instance();
        self::validate_context($context);

        require_capability('local/shared_files:manage', $context);

        $repo = get_config('local_shared_files', 'repo_path');
        if (empty($repo)) {
            throw new moodle_exception('configuredrepo', 'local_shared_files');
        }

        $root = realpath($CFG->dataroot . '/repository/' . trim($repo, '/'));
        $target = realpath($root . '/' . ltrim($params['path'], '/'));

        // Security checks.
        if ($target === false || strpos($target, $root) !== 0) {
            throw new moodle_exception('noaccess', 'local_shared_files');
        }

        if ($target === $root) {
            throw new moodle_exception('noaccess', 'local_shared_files');
        }

        $fs = get_file_storage();

        // Convert full path → relative path.
        $relativepath = str_replace($root, '', $target);
        $relativepath = ltrim($relativepath, '/');

        // Extract filepath + filename.
        $filepath = '/' . trim(dirname($relativepath), '/') . '/';
        if ($filepath === '//') {
            $filepath = '/';
        }
        $filename = basename($relativepath);

        // DELETE FROM MOODLE FILE STORAGE.
        if (is_dir($target)) {
            // Delete all files inside this folder (including subfolders).
            $files = $fs->get_area_files(
                $context->id,
                'local_shared_files',
                'repository',
                0,
                "filepath LIKE '{$filepath}%'",
                false
            );

            foreach ($files as $file) {
                $file->delete();
            }
        } else {
            // Delete single file.
            $file = $fs->get_file(
                $context->id,
                'local_shared_files',
                'repository',
                0,
                $filepath,
                $filename
            );

            if ($file) {
                $file->delete();
            }
        }

        // Recursive delete.
        $delete = function ($dir) use (&$delete) {
            foreach (scandir($dir) as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $path = $dir . '/' . $item;
                is_dir($path) ? $delete($path) : unlink($path);
            }
            return rmdir($dir);
        };

        $success = is_dir($target) ? $delete($target) : unlink($target);

        return [
            'success' => (bool)$success,
        ];
    }

    /**
     * Define return structure for delete_item().
     *
     * @return external_single_structure
     */
    public static function delete_item_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Deletion success'),
        ]);
    }
}
