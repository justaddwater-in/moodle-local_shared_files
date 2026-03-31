<?php
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
 * External API for listing files and folders in common repository.
 *
 * @package    local_shared_files
 * @copyright  2026 Justaddwater <contact@justaddwater.in>
 * @author     Himanshu Saini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_system;

/**
 * External service to list repository contents.
 *
 * @package    local_shared_files
 * @copyright  2026 Justaddwater <contact@justaddwater.in>
 * @author     Himanshu Saini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_shared_files_listing extends external_api {
    /**
     * Define parameters for list_items().
     *
     * @return external_function_parameters
     */
    public static function list_items_parameters() {
        return new external_function_parameters([
            'path' => new external_value(PARAM_PATH, 'Relative repo path', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * List files and folders inside repository path.
     *
     * @param string $path Relative path
     * @return array
     * @throws moodle_exception
     */
    public static function list_items($path) {
        global $CFG;

        // Context & capability.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/shared_files:view', $context);

        // Validate params.
        $params = self::validate_parameters(
            self::list_items_parameters(),
            ['path' => $path]
        );

        // Resolve repo.
        $repo = get_config('local_shared_files', 'repo_path');
        if (empty($repo)) {
            throw new moodle_exception('configuredrepo', 'local_shared_files');
        }

        $root = realpath($CFG->dataroot . '/repository/' . trim($repo, '/'));
        $requested = $root . ($params['path'] ? '/' . trim($params['path'], '/') : '');
        $abs = realpath($requested);

        if ($abs === false || strpos($abs, $root) !== 0) {
            $abs = $root;
            $params['path'] = '';
        }

        $data = [];

        foreach (scandir($abs) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itempath = $abs . '/' . $item;
            $relitem  = $params['path'] === ''
                ? $item
                : trim($params['path'], '/') . '/' . $item;

            if (is_dir($itempath)) {
                // Create clickable folder link.
                $url = (new moodle_url('/local/shared_files/index.php', [
                    'path' => $relitem,
                ]))->out(false);

                $namehtml = html_writer::link(
                    $url,
                    format_string($item),
                    ['class' => 'repo-folder-link']
                );

                $data[] = [
                    'name' => $namehtml,
                    'type' => 'Folder',
                    'size' => '-',
                    'path' => $relitem,
                    'actions' => has_capability('local/shared_files:manage', $context)
                                ? '<button class="btn btn-danger btn-sm js-delete"
                                    data-path="' . s($relitem) . '">Delete</button>'
                                : '',
                ];
            } else {
                $size = filesize($itempath);
                $hsize = $size >= 1048576 ? round($size / 1048576, 2) . ' MB'
                    : ($size >= 1024 ? round($size / 1024, 2) . ' KB' : $size . ' B');

                $data[] = [
                    'name' => format_string($item),
                    'type' => mime_content_type($itempath),
                    'size' => $hsize,
                    'path' => $relitem,
                    'actions' => has_capability('local/shared_files:manage', $context)
                                    ? '<button class="btn btn-danger btn-sm js-delete"
                                        data-path="' . s($relitem) . '">Delete</button>'
                                    : '',
                ];
            }
        }

        usort($data, function ($a, $b) {
            if ($a['type'] === 'Folder' && $b['type'] !== 'Folder') {
                return -1;
            }
            if ($a['type'] !== 'Folder' && $b['type'] === 'Folder') {
                return 1;
            }
            return 0;
        });

        return [
            'data' => $data,
        ];
    }

    /**
     * Define return structure for list_items().
     *
     * @return external_single_structure
     */
    public static function list_items_returns() {
        return new external_single_structure([
            'data' => new external_multiple_structure(
                new external_single_structure([
                    'name' => new external_value(PARAM_RAW, 'Name'),
                    'type' => new external_value(PARAM_TEXT, 'Type'),
                    'size' => new external_value(PARAM_TEXT, 'Size'),
                    'path' => new external_value(PARAM_PATH, 'Path'),
                    'actions' => new external_value(PARAM_RAW, 'Action buttons HTML'),
                ])
            ),
        ]);
    }
}
