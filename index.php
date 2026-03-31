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
 * Main file for viewing Central File Repo
 *
 * @package     local_shared_files
 * @copyright   2026 Justaddwater <contact@justaddwater.in>
 * @author      Himanshu Saini
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/repository/lib.php');
require_once('lib.php');

require_login();

$context = context_system::instance();
if (!has_capability('local/shared_files:view', $context)) {
    throw new moodle_exception('noaccess', 'local_shared_files');
}

// Check if repository exists.

// Get filesystem type record.
$repotype = $DB->get_record('repository', [
    'type' => 'filesystem',
], '*', MUST_EXIST);

// Now check if any instance of this type exists.
$repository = $DB->get_record('repository_instances', [
    'typeid' => $repotype->id,
]);

if (!$repository) {
    redirect(new moodle_url('/local/shared_files/create_repository.php'));
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/shared_files/index.php'));
$PAGE->set_title(get_string('pluginname', 'local_shared_files'));
$PAGE->set_heading(get_string('pluginname', 'local_shared_files'));
$PAGE->requires->css('/local/shared_files/style.css');
$PAGE->requires->css('/local/shared_files/css/dropzone.min.css');
$PAGE->requires->css('/local/shared_files/css/datatables.min.css');

/* ---------------------------
 * Resolve repository path
 * --------------------------- */
$configrepo = get_config('local_shared_files', 'repo_path');
if (empty($configrepo)) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('configuredrepo', 'local_shared_files'));
    echo $OUTPUT->footer();
    exit;
}

$root = $CFG->dataroot . '/repository/' . trim($configrepo, '/');

$realroot = local_shared_files_get_root();
if ($realroot === false) {
    throw new moodle_exception('cannotcreateroot', 'local_shared_files');
}

/* ---------------------------
 * Resolve current folder
 * --------------------------- */
$relpath = optional_param('path', '', PARAM_PATH);
$requested = $root . ($relpath ? '/' . ltrim($relpath, '/') : '');
$abs = realpath($requested);

// If realpath failed or path escapes root, fall back to root.
if ($abs === false || strpos($abs, $realroot) !== 0) {
    $abs = $realroot;
    $relpath = '';
}

// Defensive: ensure directory is readable.
if (!is_dir($abs) || !is_readable($abs)) {
    throw new moodle_exception('cannotwritetorepo', 'local_shared_files');
}

/* ---------------------------
 * Build breadcrumbs
 * --------------------------- */
$breadcrumbs = [];
$breadcrumbs[] = [
    'name' => get_string('root', 'local_shared_files'),
    'url'  => (new moodle_url('/local/shared_files/index.php'))->out(false),
];

if ($relpath !== '') {
    $parts = explode('/', trim($relpath, '/'));
    $accum = '';
    foreach ($parts as $part) {
        $accum .= '/' . $part;
        $breadcrumbs[] = [
            'name' => $part,
            'url'  => (new moodle_url(
                '/local/shared_files/index.php',
                ['path' => ltrim($accum, '/')]
            ))->out(false),
        ];
    }
}

/* ---------------------------
 * Final data for template
 * --------------------------- */
$data = [
    'breadcrumbs' => $breadcrumbs,
    'canmanage'   => has_capability('local/shared_files:manage', $context),
    'upload_url'  => (new moodle_url('/local/shared_files/upload.php'))->out(false),
    'create_url'  => (new moodle_url('/local/shared_files/create_folder.php'))->out(false),
    'sesskey'     => sesskey(),
    'currentpath' => $relpath,
];

$PAGE->requires->js_call_amd(
    'local_shared_files/dropzone',
    'DZinit',
    [
        has_capability('local/shared_files:manage', $context),
    ]
);

$datatableoptions = [
    'paging' => true,
    'searching' => true,
    'info' => true,
    'pageLength' => 25,
    'lengthMenu' => [10, 25, 50],
    'order' => false,
    'language' => ['emptyTable' => "No Topics to display"],
];
$PAGE->requires->js_call_amd(
    'local_shared_files/init_datatable',
    'DTinit',
    ['#repo-table', $relpath, $datatableoptions]
);
$PAGE->requires->js_call_amd(
    'local_shared_files/create_folder',
    'init',
    [$relpath]
);
$PAGE->requires->js_call_amd(
    'local_shared_files/folder_tree',
    'init'
);
$PAGE->requires->js_call_amd(
    'local_shared_files/delete',
    'init'
);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_shared_files/index', $data);
echo $OUTPUT->footer();
