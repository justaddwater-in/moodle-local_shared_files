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
 * Installation hook for local_shared_files.
 *
 * @package     local_shared_files
 * @copyright   2026 Justaddwater <contact@justaddwater.in>
 * @author      Himanshu Saini
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Install hook for local_shared_files
 */
function xmldb_local_shared_files_install() {
    global $CFG;

    require_once($CFG->dirroot . '/repository/lib.php');

    // Default repository location inside moodledata.
    $relativepath = 'shared_files';
    $fullpath = $CFG->dataroot . DIRECTORY_SEPARATOR . 'repository' . DIRECTORY_SEPARATOR . $relativepath;
    // Create directory structure if missing.
    if (!file_exists($fullpath)) {
        if (!mkdir($fullpath, $CFG->directorypermissions, true)) {
            debugging(
                'local_shared_files: Failed to create repository folder: ' . $fullpath,
                DEBUG_DEVELOPER
            );
            return;
        }
    }

    // Ensure directory is usable.
    if (!is_dir($fullpath) || !is_writable($fullpath)) {
        debugging(
            'local_shared_files: Repository folder not writable: ' . $fullpath,
            DEBUG_DEVELOPER
        );
        return;
    }

    // Set default config ONLY if not already set.
    if (!get_config('local_shared_files', 'repo_path')) {
        set_config('repo_path', $relativepath, 'local_shared_files');
    }

    $repository = 'filesystem';
    $type = repository::get_type_by_typename($repository);

    if (!$type) {
        $visible = 1;
        $repo = new repository_type($repository, [], $visible);
        $repo->create();
    }

    // Reset caches to recognize new repository type.
    \core_plugin_manager::reset_caches();
}
