<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Upgrade script for local_shared_files.
 *
 * @package     local_shared_files
 * @copyright   2026 Justaddwater <contact@justaddwater.in>
 * @author      Himanshu Saini
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute local_shared_files upgrade steps.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_shared_files_upgrade(int $oldversion): bool {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/repository/lib.php');

    if ($oldversion < 2025122400) {
        // No database changes in this version.
        // This savepoint tells Moodle the upgrade succeeded.
        upgrade_plugin_savepoint(
            true,
            2025122400,
            'local',
            'shared_files'
        );
    }

    if ($oldversion < 2026022607) {
        $relativepath = 'shared_files';
        $fullpath = $CFG->dataroot . DIRECTORY_SEPARATOR . 'repository' . DIRECTORY_SEPARATOR . $relativepath;

        if (!file_exists($fullpath)) {
            mkdir($fullpath, $CFG->directorypermissions, true);
        }

        // Ensure File system repository type exists.
        $repository = 'filesystem';
        $type = repository::get_type_by_typename($repository);

        if (!$type) {
            $visible = 1;
            $repo = new repository_type($repository, [], $visible);
            $repo->create();
        }

        // Reset plugin caches.
        \core_plugin_manager::reset_caches();

        // Save upgrade point.
        upgrade_plugin_savepoint(
            true,
            2026022607,
            'local',
            'shared_files'
        );
    }

    return true;
}
