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
 * Plugin methods are defined here
 *
 * @package     local_shared_files
 * @copyright   2026 Justaddwater <contact@justaddwater.in>
 * @author      Himanshu Saini
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * File serving callback for local_shared_files.
 *
 * @param stdClass $course Course object.
 * @param stdClass $cm Course module object.
 * @param context $context Context object.
 * @param string $filearea File area.
 * @param array $args Extra arguments.
 * @param bool $forcedownload Whether the file should be force downloaded.
 * @param array $options Additional options affecting the file serving.
 * @return void
 */
function local_shared_files_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $CFG;

    if ($context->contextlevel !== CONTEXT_SYSTEM) {
        send_file_not_found();
    }

    require_login();
    if (!has_capability('local/shared_files:view', $context)) {
        send_file_not_found();
    }

    // Args[0..n] contains path components including filename.
    $relpath = implode('/', $args);

    // Get configured repository.
    $repo = get_config('local_shared_files', 'repo_path');
    if (empty($repo)) {
        send_file_not_found();
    }

    // Resolve repository root safely.
    $root = realpath($CFG->dataroot . '/repository/' . trim($repo, '/'));

    if ($root === false) {
        send_file_not_found();
    }

    // Resolve requested file safely.
    $full = realpath($root . '/' . ltrim($relpath, '/'));

    // Ensure resolved file stays inside repository root.
    if (
        $full === false ||
        !str_starts_with(
            $full,
            $root . DIRECTORY_SEPARATOR
        ) ||
        !is_file($full)
    ) {
        send_file_not_found();
    }

    // Serve file.
    send_file($full, 0, 0, true, $options);
}

/**
 * Returns the absolute repository root path.
 *
 * Automatically creates the directory if it does not exist.
 *
 * @return string Absolute filesystem path
 * @throws moodle_exception If repository path is not configured
 */
function local_shared_files_get_root(): string {
    global $CFG;

    $repo = get_config('local_shared_files', 'repo_path');
    if (empty($repo)) {
        throw new moodle_exception('configuredrepo', 'local_shared_files');
    }

    $root = $CFG->dataroot . '/repository/' . trim($repo, '/');

    // AUTO-CREATE if missing.
    if (!file_exists($root)) {
        if (!mkdir($root, $CFG->directorypermissions, true)) {
            throw new moodle_exception('cannotcreateroot', 'local_shared_files');
        }
    }

    $realroot = realpath($root);
    if ($realroot === false || !is_dir($realroot)) {
        throw new moodle_exception('cannotcreateroot', 'local_shared_files');
    }

    return $realroot;
}
