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
 * Simple health check for local_shared_files
 *
 * @package     local_shared_files
 * @copyright   2026 Justaddwater <contact@justaddwater.in>
 * @author      Himanshu Saini
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../config.php');
require_login();

header('Content-Type: application/json; charset=utf-8');



// If not logged in, return 401 (SCORM normally runs inside logged-in session).
if (!isloggedin() || isguestuser()) {
    http_response_code(401);
    echo json_encode(['installed' => true, 'configured' => false, 'message' => 'login_required']);
    exit;
}

// We know the plugin exists if this file is present, but also check config.
$repo = get_config('local_shared_files', 'repo_path');
if ($repo === false || $repo === null || trim($repo) === '') {
    // Plugin present, but not configured.
    http_response_code(200);
    echo json_encode(['installed' => true, 'configured' => false, 'message' => 'repo_not_configured']);
    exit;
}

// Optional: also verify repo path resolves.
$root = $CFG->dataroot . '/repository/' . trim($repo, '/');
$realroot = realpath($root);
if ($realroot === false || !is_dir($realroot)) {
    http_response_code(200);
    echo json_encode(['installed' => true, 'configured' => false, 'message' => 'repo_path_invalid']);
    exit;
}

// All good.
http_response_code(200);
echo json_encode(['installed' => true, 'configured' => true, 'repo' => $repo]);
exit;
