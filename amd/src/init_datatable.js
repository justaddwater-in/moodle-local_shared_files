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
 * JS for initializing DataTable in common repository.
 *
 * @module     local_shared_files/init_datatable
 * @copyright  2026 Justaddwater <contact@justaddwater.in>
 * @author     Himanshu Saini
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/log', 'core/str'], function($, Ajax, Log, Str) {
    /**
     * Fetch repository items.
     *
     * @param {String} path
     * @returns {Object}
     */
    function getTableData(path) {
        return Ajax.call([{
            methodname: 'local_shared_files_list_items',
            args: {
                path: path,
                sesskey: M.cfg.sesskey
            }
        }])[0];
    }

    /**
     * Format response for DataTable.
     *
     * @param {Object} response
     * @returns {Object}
     */
    function formatTableData(response) {
        return {
            data: response.data.map(function(item) {
                return [
                    item.name,
                    item.type,
                    item.size,
                    item.actions
                ];
            })
        };
    }

    /**
     * Update DataTable callback.
     *
     * @param {Function} callback
     * @param {Object} response
     */
    function updateTable(callback, response) {
        callback(formatTableData(response));
    }

    return {
        DTinit: function(selector, path, options) {

            return Str.get_string(
                'datatableloadfailed',
                'local_shared_files'
            )
            .then(function(datatableloadfailed) {

                require(['js/datatables.min.js'], function() {

                    if (!$.fn.DataTable) {
                        Log.error(datatableloadfailed);
                        return;
                    }

                    $(selector, '.local-shared-files').DataTable({
                        processing: true,
                        serverSide: false,

                        ajax: function(data, callback) {

                            getTableData(path)
                                .done(updateTable.bind(
                                    null,
                                    callback
                                ))
                                .fail(Log.exception);
                        },

                        ...options
                    });
                });

                return datatableloadfailed;
            })
            .catch(Log.exception);
        }
    };
});
