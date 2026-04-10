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
 * JS for create folder form in common repository.
 *
 * @module     local_shared_files/create_folder
 * @copyright  2026 Justaddwater <contact@justaddwater.in>
 * @author     Himanshu Saini
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'jquery',
    'core/ajax',
    'core/notification',
    'local_shared_files/folder_tree'
], function($, Ajax, Notification, FolderTree) {
    return {
        init: function(path) {
            $('#create-folder-form').on('submit', function(e) {
                e.preventDefault();

                const foldername = $(this).find('[name="foldername"]').val().trim();
                if (!foldername) {
                    return;
                }

                Ajax.call([{
                    methodname: 'local_shared_files_create_folder',
                    args: {
                        path: path,
                        foldername: foldername,
                        sesskey: M.cfg.sesskey
                    }
                }])[0]
                .done(function(response) {
                    if (response.success) {

                        if ($.fn.DataTable.isDataTable('#repo-table')) {
                            $('#repo-table').DataTable().ajax.reload(null, false);
                        }

                        FolderTree.reload();

                        $('#create-folder-form')[0].reset();

                    } else {
                        Notification.alert('Error', response.error, 'OK');
                    }

                })
                .fail(Notification.exception);
            });
        }
    };
});
