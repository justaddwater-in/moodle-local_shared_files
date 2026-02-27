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
 * Repository setup page for Shared Files plugin.
 *
 * Displays the File system repository instance creation form
 * and ensures that only one instance is created for this plugin.
 * If a filesystem repository instance already exists, the user
 * is redirected back to the Shared Files index page.
 *
 * @package     local_shared_files
 * @copyright   2026 Justaddwater <contact@justaddwater.in>
 * @author      Himanshu Saini
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/repository/lib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$context = context_system::instance();

$PAGE->set_url(new moodle_url('/local/shared_files/create_repository.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('setuprepository', 'local_shared_files'));
$PAGE->set_heading(get_string('setuprepository', 'local_shared_files'));

$parenturl = new moodle_url('/local/shared_files/index.php');

// Get filesystem repository type.
$type = repository::get_type_by_typename('filesystem');

if (!$type) {
    throw new moodle_exception('invalidplugin', 'repository');
}

// Check if already created.
$repotype = $DB->get_record('repository', [
    'type' => 'filesystem',
], '*', MUST_EXIST);

$existing = $DB->get_record('repository_instances', [
    'typeid' => $repotype->id,
]);

if ($existing) {
    redirect($parenturl);
}

// Create form ONLY for filesystem plugin.

$mform = new repository_instance_form('', [
    'plugin' => 'filesystem',
    'typeid' => null,
    'instance' => null,
    'contextid' => $context->id,
]);

if ($mform->is_cancelled()) {
    redirect($parenturl);
} else if ($fromform = $mform->get_data()) {
    // Force folder if needed.
    if (empty($fromform->fs_path)) {
        $fromform->fs_path = 'shared_files';
    }

    $success = repository::static_function(
        'filesystem',
        'create',
        'filesystem',
        0,
        $context,
        $fromform
    );

    if ($success) {
        core_plugin_manager::reset_caches();
        redirect($parenturl, get_string('repositorycreated', 'local_shared_files'));
    } else {
        throw new moodle_exception('instancenotsaved', 'repository');
    }
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
