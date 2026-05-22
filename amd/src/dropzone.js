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
 * JS for dropzone in common repository.
 *
 * @module     local_shared_files/dropzone
 * @copyright  2026 Justaddwater <contact@justaddwater.in>
 * @author     Himanshu Saini
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/log', 'core/notification', 'core/str'], function($, Log, Notification, Str) {
    return {
        DZinit: function(canmanage) {

            require(['js/dropzone.min.js'], function() {

                return Str.get_strings([
                    {key: 'dropzonenotloaded', component: 'local_shared_files'},
                    {key: 'invalidjsonresponse', component: 'local_shared_files'},
                    {key: 'error', component: 'local_shared_files'},
                    {key: 'invalidserverresponse', component: 'local_shared_files'},
                    {key: 'ok', component: 'local_shared_files'},
                    {key: 'uploadfailed', component: 'local_shared_files'},
                    {key: 'uploadfailedtitle', component: 'local_shared_files'},
                    {
                        key: 'fileuploadedsuccessfully',
                        component: 'local_shared_files'
                    },
                    {key: 'uploaderrortitle', component: 'local_shared_files'}
                ])
                .then(function(results) {

                    const strings = {
                        dropzonenotloaded: results[0],
                        invalidjsonresponse: results[1],
                        error: results[2],
                        invalidserverresponse: results[3],
                        ok: results[4],
                        uploadfailed: results[5],
                        uploadfailedtitle: results[6],
                        fileuploadedsuccessfully: results[7],
                        uploaderror: results[8]
                    };

                    if (typeof window.Dropzone === 'undefined') {
                        Log.error(strings.dropzonenotloaded);
                        return null;
                    }

                    window.Dropzone.autoDiscover = false;

                    const element = document.querySelector(
                        '.local-shared-files #shared-files-dropzone'
                    );

                    if (!element || !canmanage) {
                        return null;
                    }

                    // Prevent duplicate initialization.
                    if (element.dropzone) {
                        Log.debug('Dropzone already initialized');
                        return element.dropzone;
                    }

                    const sesskey = element.dataset.sesskey;
                    const path = element.dataset.path || '';

                    // Flag to prevent multiple popups.
                    let errorShown = false;

                    const dz = new window.Dropzone(element, {
                        url: M.cfg.wwwroot +
                            '/local/shared_files/upload.php',
                        paramName: 'file',
                        maxFilesize: 100,
                        uploadMultiple: true,
                        parallelUploads: 5,
                        maxFiles: 50,
                        params: {
                            sesskey: sesskey,
                            path: path
                        },
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },

                        success: function(file, response) {

                            if (typeof response === 'string') {
                                try {
                                    response = JSON.parse(response);
                                } catch (e) {
                                    Log.error(
                                        strings.invalidjsonresponse
                                    );

                                    if (!errorShown) {
                                        Notification.alert(
                                            strings.error,
                                            strings.invalidserverresponse,
                                            strings.ok
                                        );
                                        errorShown = true;
                                    }

                                    return;
                                }
                            }

                            if (!response.success) {
                                const errorMsg = response.errors ?
                                    response.errors.join('<br>') :
                                    strings.uploadfailed;

                                Log.error(errorMsg);

                                if (!errorShown) {
                                    Notification.alert(
                                        strings.uploadfailedtitle,
                                        errorMsg,
                                        strings.ok
                                    );
                                    errorShown = true;
                                }

                                file.previewElement.classList
                                    .add('dz-error');

                            } else {
                                Notification.addNotification({
                                    message:
                                        strings.fileuploadedsuccessfully,
                                    type: 'success'
                                });
                            }
                        },

                        error: function(file, message) {
                            Log.error(message);

                            if (!errorShown) {
                                Notification.alert(
                                    strings.uploaderror,
                                    message,
                                    strings.ok
                                );
                                errorShown = true;
                            }
                        }
                    });

                    dz.on('queuecomplete', function() {

                        // Reset flag for next upload batch.
                        errorShown = false;
                        dz.removeAllFiles(true);

                        if (
                            $.fn.DataTable.isDataTable('#repo-table')
                        ) {
                            $('#repo-table')
                                .DataTable()
                                .ajax.reload(null, false);
                        }
                    });

                    Log.debug('Dropzone initialized');

                    return dz;
                })
                .catch(Notification.exception);
            });
        }
    };
});
