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

define(['jquery', 'core/log', 'core/notification'], function($, Log, Notification) {
    return {
        DZinit: function(canmanage) {
            require(['js/dropzone.min.js'], function() {
                if (typeof window.Dropzone === 'undefined') {
                    Log.error('Dropzone not loaded');
                    return;
                }

                window.Dropzone.autoDiscover = false;

                const element = document.getElementById('shared-files-dropzone');
                if (!element || !canmanage) {
                    return;
                }

                const sesskey = element.dataset.sesskey;
                const path = element.dataset.path || '';

                // NEW: flag to prevent multiple popups.
                let errorShown = false;

                const dz = new window.Dropzone(element, {
                    url: M.cfg.wwwroot + '/local/shared_files/upload.php',
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
                                Log.error('Invalid JSON response');

                                if (!errorShown) {
                                    Notification.alert('Error', 'Invalid server response', 'OK');
                                    errorShown = true;
                                }
                                return;
                            }
                        }

                        if (!response.success) {
                            const errorMsg = response.errors ? response.errors.join('<br>') : 'Upload failed';

                            Log.error('Upload failed: ' + errorMsg);

                            // Show only once.
                            if (!errorShown) {
                                Notification.alert('Upload Failed', errorMsg, 'OK');
                                errorShown = true;
                            }

                            file.previewElement.classList.add('dz-error');
                        } else {
                            Notification.addNotification({
                                message: 'File uploaded successfully',
                                type: 'success'
                            });
                        }
                    },

                    error: function(file, message) {
                        Log.error('Upload error: ' + message);

                        // Show only once.
                        if (!errorShown) {
                            Notification.alert('Upload Error', message, 'OK');
                            errorShown = true;
                        }
                    }
                });

                dz.on('queuecomplete', function() {
                    // Reset flag for next upload batch.
                    errorShown = false;
                    dz.removeAllFiles(true);

                    if ($.fn.DataTable.isDataTable('#repo-table')) {
                        $('#repo-table').DataTable().ajax.reload(null, false);
                    }
                });

                Log.debug('Dropzone initialized');
            });
        }
    };
});
