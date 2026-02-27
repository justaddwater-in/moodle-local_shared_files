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
 * AJAX endpoint for uploading files into the common repository.
 *
 * @package    local_shared_files
 * @copyright  2026 Justaddwater <contact@justaddwater.in>
 * @author      Himanshu Saini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

require_login();
confirm_sesskey();

$context = context_system::instance();
require_capability('local/shared_files:manage', $context);

$relpath = optional_param('path', '', PARAM_PATH);

$repo = get_config('local_shared_files', 'repo_path');
if (empty($repo)) {
    echo json_encode(['success' => false, 'error' => 'Repository not configured']);
    exit;
}

$root = $CFG->dataroot . '/repository/' . trim($repo, '/');
$realroot = realpath($root);
if ($realroot === false) {
    echo json_encode(['success' => false, 'error' => 'Invalid repository root']);
    exit;
}

$targetdir = realpath($root . '/' . ltrim($relpath, '/'));
if ($targetdir === false || strpos($targetdir, $realroot) !== 0) {
    $targetdir = $realroot;
}

if (!is_dir($targetdir) || !is_writable($targetdir)) {
    echo json_encode(['success' => false, 'error' => 'Target directory not writable']);
    exit;
}

if (empty($_FILES['file'])) {
    echo json_encode(['success' => false, 'error' => 'No files uploaded']);
    exit;
}

require_once($CFG->libdir . '/filelib.php');

$uploaded = [];
$errors   = [];

$files = $_FILES['file'];
$multiple = is_array($files['name']);
$count = $multiple ? count($files['name']) : 1;

// Optional: max file size (example: 20MB).
$maxsize = 20 * 1024 * 1024;

for ($i = 0; $i < $count; $i++) {
    $name    = $multiple ? $files['name'][$i] : $files['name'];
    $tmp     = $multiple ? $files['tmp_name'][$i] : $files['tmp_name'];
    $error   = $multiple ? $files['error'][$i] : $files['error'];
    $size    = $multiple ? $files['size'][$i] : $files['size'];

    if ($error !== UPLOAD_ERR_OK) {
        $errors[] = "Upload error: {$name}";
        continue;
    }

    if ($size > $maxsize) {
        $errors[] = "File too large: {$name}";
        continue;
    }

    $filename = clean_filename($name);
    if ($filename === '') {
        $errors[] = "Invalid filename: {$name}";
        continue;
    }

    $dest = $targetdir . '/' . $filename;

    if (file_exists($dest)) {
        $errors[] = "File already exists: {$filename}";
        continue;
    }

    if (!move_uploaded_file($tmp, $dest)) {
        $errors[] = "Failed to save: {$filename}";
        continue;
    }

    @chmod($dest, 0644);
    $uploaded[] = $filename;
}

echo json_encode([
    'success'  => empty($errors),
    'uploaded' => $uploaded,
    'errors'   => $errors,
]);
exit;
