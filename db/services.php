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
 * Services definition for shared_files.
 *
 * @package     local_shared_files
 * @copyright   2026 Justaddwater <contact@justaddwater.in>
 * @author      Himanshu Saini
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_shared_files_list_items' => [
        'classname'     => 'local_shared_files_listing',
        'methodname'    => 'list_items',
        'classpath'     => 'local/shared_files/classes/external/listing.php',
        'description'   => 'List files and folders from common repository',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'local/shared_files:view',
    ],
    'local_shared_files_delete_item' => [
        'classname'     => 'local_shared_files_delete',
        'methodname'    => 'delete_item',
        'classpath'     => 'local/shared_files/classes/external/delete.php',
        'description'   => 'Delete a file or folder from common repository',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'local/shared_files:manage',
    ],
    'local_shared_files_create_folder' => [
        'classname'     => 'local_shared_files_create_folder',
        'methodname'    => 'create_folder',
        'classpath'     => 'local/shared_files/classes/external/create_folder.php',
        'description'   => 'Create a folder in the common repository',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'local/shared_files:manage',
    ],
    'local_shared_files_get_tree' => [
        'classname'     => 'local_shared_files_get_tree',
        'methodname'    => 'get_tree',
        'classpath'     => 'local/shared_files/classes/external/get_tree.php',
        'description'   => 'Get full folder tree of common repo',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'local/shared_files:view',
    ],
];

$services = [
    'Shared Files Web Services' => [
        'functions' => [
            'local_shared_files_list_items',
            'local_shared_files_delete_item',
            'local_shared_files_create_folder',
            'local_shared_files_get_tree',
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
    ],
];
