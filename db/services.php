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
 * This class defines the functions and konfigurations of the external service.
 *
 * @package local_sync_service
 * @copyright 2022 Daniel Schröter
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$functions = array(
    'local_course_add_new_section' => array(
        'classname' => 'local_sync_service_external',
        'methodname' => 'local_sync_service_add_new_section',
        'classpath' => 'local/sync_service/externallib.php',
        'description' => 'Add new course section',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'block/section_links:addinstance',
    ),
    'local_course_add_new_course_module_url' => array(
        'classname' => 'local_sync_service_external',
        'methodname' => 'local_sync_service_add_new_course_module_url',
        'classpath' => 'local/sync_service/externallib.php',
        'description' => 'Add course module URL',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/url:addinstance',
    ),
    'local_course_add_new_course_module_resource' => array(
        'classname' => 'local_sync_service_external',
        'methodname' => 'local_sync_service_add_new_course_module_resource',
        'classpath' => 'local/sync_service/externallib.php',
        'description' => 'Add course module Resource',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/resource:addinstance',
    ),
    'local_course_move_module_to_specific_position' => array(
        'classname' => 'local_sync_service_external',
        'methodname' => 'local_sync_service_move_module_to_specific_position',
        'classpath' => 'local/sync_service/externallib.php',
        'description' => 'Moves a module to a dedicated position',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/course:movesections'
    ),
    'local_course_add_new_course_module_directory' => array(
        'classname' => 'local_sync_service_external',
        'methodname' => 'local_sync_service_add_new_course_module_directory',
        'classpath' => 'local/sync_service/externallib.php',
        'description' => 'Add course modul folder',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/folder:addinstance'
    ),
    'local_course_add_files_to_directory' => array(
        'classname' => 'local_sync_service_external',
        'methodname' => 'local_sync_service_add_files_to_directory',
        'classpath' => 'local/sync_service/externallib.php',
        'description' => 'Add files to folder',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/folder:addinstance'
    )


);

$services = array(
    'Course Sync Extension Service' => array(
        'functions' => array(
            'local_course_add_new_section',
            'local_course_add_new_course_module_url',
            'local_course_add_new_course_module_resource',
            'local_course_move_module_to_specific_position',
            'local_course_add_new_course_module_directory',
            'local_course_add_files_to_directory',
            'core_course_get_contents',
            'core_enrol_get_users_courses',
            'core_webservice_get_site_info',
            'core_course_delete_modules'
        ),
        'restrictedusers' => 1,
        'enabled' => 1,
        'shortname' => 'sync_service',
        'downloadfiles' => 1,
        'uploadfiles'  => 1
    )
);
