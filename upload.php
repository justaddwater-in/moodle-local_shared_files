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
 * AJAX endpoint for uploading files into the shared repository.
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

// Ensure JSON response for AJAX.
header('Content-Type: application/json');

// Require system-level capability to manage shared files.
$context = context_system::instance();
require_capability('local/shared_files:manage', $context);

// Get relative path safely.
$relpath = optional_param('path', '', PARAM_PATH);

// Fetch configured repository path.
$repo = get_config('local_shared_files', 'repo_path');
if (empty($repo)) {
    echo json_encode([
        'success' => false,
        'error' => get_string('repositorynotconfigured', 'local_shared_files'),
    ]);
    exit;
}

// Resolve repository root inside Moodle dataroot.
$root = $CFG->dataroot . '/repository/' . trim($repo, '/');
$realroot = realpath($root);
if ($realroot === false) {
    echo json_encode([
        'success' => false,
        'error' => get_string('invalidrepositoryroot', 'local_shared_files'),
    ]);
    exit;
}

// Build intended target directory path.
$targetdir = $root . '/' . ltrim($relpath, '/');

// Resolve parent directory safely.
$parentdir = realpath(dirname($targetdir));

// Ensure target directory remains inside repository root.
if ($parentdir === false || strpos($parentdir, $realroot) !== 0) {
    $targetdir = $realroot;
}

// Create directory if it does not exist.
if (!is_dir($targetdir)) {
    mkdir($targetdir, $CFG->directorypermissions, true);
}

// Final safety check.
if (!is_dir($targetdir) || !is_writable($targetdir)) {
    echo json_encode([
        'success' => false,
        'error' => get_string('targetdirnotwritable', 'local_shared_files'),
    ]);
    exit;
}

// Ensure files are provided.
if (empty($_FILES['file'])) {
    echo json_encode([
        'success' => false,
        'error' => get_string('nofilesuploaded', 'local_shared_files'),
    ]);
    exit;
}

require_once($CFG->libdir . '/filelib.php');

$uploaded = [];
$errors   = [];

// Handle single or multiple file uploads.
$files = $_FILES['file'];
$multiple = is_array($files['name']);
$count = $multiple ? count($files['name']) : 1;

// Get max upload size from Moodle + PHP settings.
$moodlemax = get_max_upload_file_size($CFG->maxbytes);

// Fallback safety: also respect PHP limits.
$uploadmax = ini_get('upload_max_filesize');
$postmax   = ini_get('post_max_size');

// Convert PHP values to bytes.
$uploadmaxbytes = get_real_size($uploadmax);
$postmaxbytes   = get_real_size($postmax);

// Final allowed size = safest minimum.
$maxsize = min($moodlemax, $uploadmaxbytes, $postmaxbytes);

// Allowed file extensions.
$allowedextensions = [
    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'txt', 'zip',
];

// Explicitly blocked extensions for security.
$blockedextensions = [
    'php', 'phtml', 'phar', 'js', 'html', 'htm', 'sh', 'exe', 'bat',
];

// Allowed MIME types corresponding to extensions.
$allowedmime = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'image/jpeg',
    'image/png',
    'image/gif',
    'video/mp4',
    'text/plain',
    'application/zip',
];

// Initialise fileinfo for MIME detection if available.
$finfo = function_exists('finfo_open') ? new finfo(FILEINFO_MIME_TYPE) : null;

$fs = get_file_storage();

// Process each uploaded file.
for ($i = 0; $i < $count; $i++) {
    $name    = $multiple ? $files['name'][$i] : $files['name'];
    $tmp     = $multiple ? $files['tmp_name'][$i] : $files['tmp_name'];
    $error   = $multiple ? $files['error'][$i] : $files['error'];
    $size    = $multiple ? $files['size'][$i] : $files['size'];

    // Check for upload errors.
    if ($error !== UPLOAD_ERR_OK) {
        $errors[] = get_string(
            'uploaderror',
            'local_shared_files',
            $name
        );
        continue;
    }

    // Enforce maximum file size.
    if ($size > $maxsize) {
        $maxmb = round($maxsize / (1024 * 1024));
        $errors[] = get_string(
            'filetoolarge',
            'local_shared_files',
            $maxmb
        );
        continue;
    }

    // Clean filename using Moodle API.
    $filename = clean_filename($name);
    if ($filename === '') {
        $errors[] = get_string('invalidfilename', 'local_shared_files', $name);
        continue;
    }

    // Extract and validate file extension.
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (in_array($ext, $blockedextensions)) {
        $errors[] = get_string(
            'blockedfiletype',
            'local_shared_files'
        );
        continue;
    }

    if (!in_array($ext, $allowedextensions)) {
        $errors[] = get_string(
            'unsupportedfileformat',
            'local_shared_files'
        );
        continue;
    }

    // Detect MIME type using finfo or fallback.
    $mime = $finfo ? $finfo->file($tmp) : mime_content_type($tmp);

    if (!$mime) {
        $errors[] = get_string(
            'unabletodetectfiletype',
            'local_shared_files',
            $filename
        );
        continue;
    }

    if (!in_array($mime, $allowedmime)) {
        $errors[] = get_string(
            'filetypenotallowed',
            'local_shared_files'
        );
        continue;
    }

    // Validate image files to prevent spoofed content.
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
        if (@getimagesize($tmp) === false) {
            $errors[] = get_string(
                'invalidimagefile',
                'local_shared_files',
                $filename
            );
            continue;
        }
    }

    // Prevent double extension attacks.
    if (preg_match('/\.(php|phtml|phar)\./i', $filename)) {
        $errors[] = get_string(
            'suspiciousfilename',
            'local_shared_files',
            $filename
        );
        continue;
    }

    // Prepare Moodle file record.
    $fileinfo = [
        'contextid' => $context->id,
        'component' => 'local_shared_files',
        'filearea'  => 'repository',
        'itemid'    => 0,
        'filepath'  => '/' . trim($relpath, '/') . '/',
        'filename'  => $filename,
    ];

    // Check if file already exists in Moodle storage.
    if (
        $fs->file_exists(
            $fileinfo['contextid'],
            $fileinfo['component'],
            $fileinfo['filearea'],
            $fileinfo['itemid'],
            $fileinfo['filepath'],
            $fileinfo['filename']
        )
    ) {
        $errors[] = get_string(
            'filealreadyexists',
            'local_shared_files',
            $filename
        );
        continue;
    }

    // Save file via Moodle File API.
    $file = $fs->create_file_from_pathname($fileinfo, $tmp);

    if (!$file) {
        $errors[] = get_string(
            'failedtosave',
            'local_shared_files',
            $filename
        );
        continue;
    }

    // Mirror to repo folder.
    $dest = $targetdir . '/' . $filename;

    if (!copy($tmp, $dest)) {
        // Rollback Moodle file to maintain consistency.
        $file->delete();

        $errors[] = get_string(
            'failedtosaveinrepo',
            'local_shared_files',
            $filename
        );
        continue;
    }

    // Set safe permissions.
    @chmod($dest, 0644);

    $uploaded[] = $filename;
}

// Return JSON response.
echo json_encode([
    'success'  => empty($errors),
    'uploaded' => $uploaded,
    'errors'   => $errors,
]);
exit;
