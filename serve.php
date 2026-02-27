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
 * Robust file server for the local_shared_files plugin.
 *
 * Streams files from the common repository using manual chunked I/O,
 * which is reliable on IIS and FastCGI environments.
 *
 * Usage:
 *   /local/shared_files/serve.php?path=SubFolder/TrainingTopic.pdf
 *
 * Optional parameters:
 *   download=1 Forces the file to be downloaded instead of displayed inline.
 *
 * @package     local_shared_files
 * @copyright   2026 Justaddwater <contact@justaddwater.in>
 * @author      Himanshu Saini
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login(); // Allow any authenticated user.

// Params.
$relpath = required_param('path', PARAM_PATH);
$forcedownload = optional_param('download', 0, PARAM_INT);

// Repo config and resolution.
$repo = get_config('local_shared_files', 'repo_path');
if (empty($repo)) {
    throw new moodle_exception('confignull', 'local_shared_files');
}

$root = $CFG->dataroot . '/repository/' . trim($repo, '/');
$realroot = realpath($root);
if ($realroot === false) {
    throw new moodle_exception('cannotcreateroot', 'local_shared_files');
}

$candidate = $root . '/' . ltrim($relpath, '/');
$fullpath = realpath($candidate);

if ($fullpath === false || strpos($fullpath, $realroot) !== 0 || !is_file($fullpath)) {
    throw new moodle_exception('filenotfound', 'local_shared_files');
}

// Block executables for safety.
$forbiddenextenstions = ['php', 'phtml', 'php3', 'phps', 'pl', 'py', 'sh', 'exe', 'bat'];
$ext = strtolower(pathinfo($fullpath, PATHINFO_EXTENSION));
if (in_array($ext, $forbiddenextenstions, true)) {
    throw new moodle_exception('forbiddenfile', 'local_shared_files');
}

// Prepare streaming environment.
// Close session to avoid session locking and buffered output.
@session_write_close();

// Turn off output compression for this response if possible.
@ini_set('zlib.output_compression', '0');

// Clear (and discard) any existing output buffers.
while (ob_get_level() > 0) {
    @ob_end_clean();
}

// Get file metadata.
clearstatcache(true, $fullpath);
$filesize = filesize($fullpath);
$filename = basename($fullpath);
$mime = mime_content_type($fullpath) ?: 'application/octet-stream';
$disposition = $forcedownload ? 'attachment' : 'inline';

// Send headers.
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Transfer-Encoding: binary');
header('Content-Disposition: ' . $disposition . '; filename="' . rawurlencode($filename) . '"');
header('Content-Length: ' . $filesize);
header('Expires: 0');
header('Cache-Control: public, must-revalidate, max-age=0');
header('Pragma: public');

// Ensure PHP and webserver won't timeout during large transfers.
@set_time_limit(0);

// Stream the file in chunks.
$chunksize = 8192; // 8 KB.

$fp = @fopen($fullpath, 'rb');
if ($fp === false) {
    http_response_code(500);
    echo 'Could not open file for reading';
    exit;
}

// Use a while loop to read and flush chunks.
$bytessent = 0;
while (!feof($fp)) {
    $buffer = fread($fp, $chunksize);
    if ($buffer === false) {
        break;
    }
    echo $buffer;
    $bytessent += strlen($buffer);
    // Flush output to the client.
    @flush();
    // On FastCGI/IIS it may be necessary to call both flush functions.
    if (function_exists('ob_flush')) {
        @ob_flush();
    }
}

// Close handle.
fclose($fp);

// Optionally, you can log or validate bytessent == filesize.
// If you want to be strict, check and send 500 if mismatch.
exit;
