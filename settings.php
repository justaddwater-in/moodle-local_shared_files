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
 * Central file repo module admin settings and defaults
 *
 * @package     local_shared_files
 * @copyright   2026 Justaddwater <contact@justaddwater.in>
 * @author      Himanshu Saini
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_shared_files_settings',
        get_string('settingspage', 'local_shared_files')
    );

    // Info message.
    $settings->add(new admin_setting_heading(
        'local_shared_files/info',
        '',
        get_string('repositoryinfo', 'local_shared_files')
    ));

    // Folder name setting (NO repository/ visible).
    $settings->add(new admin_setting_configtext(
        'local_shared_files/repo_path',
        get_string('configuredrepo', 'local_shared_files'),
        get_string('setrepo_desc', 'local_shared_files'),
        'shared_files', // Default folder name.
        PARAM_PATH
    ));

    $ADMIN->add('localplugins', $settings);

    // Direct manage link.
    $ADMIN->add(
        'localplugins',
        new admin_externalpage(
            'local_shared_files_manage',
            get_string('managerepo', 'local_shared_files'),
            new moodle_url('/local/shared_files/index.php'),
            'local/shared_files:view'
        )
    );
}
