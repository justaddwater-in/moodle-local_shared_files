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
 * JS for folder tree in common repository.
 *
 * @module     local_shared_files/folder_tree
 * @copyright  2026 Justaddwater <contact@justaddwater.in>
 * @author     Himanshu Saini
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax'], function($, Ajax) {
    function renderTree(nodes) {
        const ul = $('<ul class="folder-tree"></ul>');

        nodes.forEach(node => {
            const li = $('<li></li>');
            const link = $('<a href="#"></a>')
                .text(node.name)
                .data('path', node.path)
                .on('click', function(e) {
                    e.preventDefault();
                    window.location.href =
                        M.cfg.wwwroot + '/local/shared_files/index.php?path=' + node.path;
                });

            li.append(link);

            if (node.children && node.children.length) {
                li.append(renderTree(node.children));
            }

            ul.append(li);
        });

        return ul;
    }

    function loadTree() {
        Ajax.call([{
            methodname: 'local_shared_files_get_tree',
            args: {}
        }])[0].done(function(tree) {
            $('#repo-folder-tree')
                .empty()
                .append(renderTree(tree));
        });
    }

    return {
        init: function() {
            loadTree();
        },
        reload: function() {
            loadTree();
        }
    };
});
