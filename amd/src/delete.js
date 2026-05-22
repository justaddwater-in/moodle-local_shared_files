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
 * JS for deleting items in common repository.
 *
 * @module     local_shared_files/delete
 * @copyright  2026 Justaddwater <contact@justaddwater.in>
 * @author     Himanshu Saini
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {

    return {
        init: function() {

            return Str.get_strings([
                {key: 'delete', component: 'local_shared_files'},
                {key: 'confirmdeleteitem', component: 'local_shared_files'},
                {key: 'yes', component: 'moodle'},
                {key: 'cancel', component: 'moodle'},
                {key: 'deletefailed', component: 'local_shared_files'},
                {key: 'unabletodeleteitem', component: 'local_shared_files'}
            ])
            .then(function(strings) {

                $('.local-shared-files').on('click', '.js-delete', function(e) {
                    e.preventDefault();

                    const path = $(this).data('path');

                    Notification.confirm(
                        strings[0], // Delete.
                        strings[1], // Confirmation.
                        strings[2], // Yes.
                        strings[3], // Cancel.
                        function() {

                            Ajax.call([{
                                methodname: 'local_shared_files_delete_item',
                                args: {
                                    path: path,
                                    sesskey: M.cfg.sesskey
                                }
                            }])[0]
                            .done(function(response) {

                                if (response.success) {
                                    $('#repo-table')
                                        .DataTable()
                                        .ajax.reload(null, false);

                                } else {
                                    Notification.alert(
                                        strings[4], // Delete failed.
                                        strings[5] // Unable to delete item.
                                    );
                                }

                                return response;
                            })
                            .fail(Notification.exception);
                        }
                    );
                });

                return strings;
            })
            .catch(Notification.exception);
        }
    };
});
