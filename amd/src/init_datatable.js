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

define(['jquery', 'core/ajax', 'core/log'], function($, Ajax, Log) {
    return {
        DTinit: function(selector, path, options) {
            require(['js/datatables.min.js'], function() {
                $(document).ready(function() {
                    if ($.fn.DataTable) {
                        $(selector).DataTable({
                            processing: true,
                            serverSide: false,
                            ajax: function(data, callback) {
                                Ajax.call([{
                                    methodname: 'local_shared_files_list_items',
                                    args: {
                                        path: path,
                                        sesskey: M.cfg.sesskey
                                    }
                                }])[0].done(function(response) {
                                    callback({
                                        data: response.data.map(function(item) {
                                            return [
                                                item.name,
                                                item.type,
                                                item.size,
                                                item.actions
                                            ];
                                        })
                                    });
                                });
                            },
                            ...options
                        });
                    } else {
                        Log.error('DataTables library failed to load.');
                    }
                });
            });
        }
    };
});
