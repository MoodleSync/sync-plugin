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
 * @copyright 2023 Daniel Schröter
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
        'capabilities' => 'mod/folder:managefiles'
    ),
    'local_course_add_new_course_module_page' => array(
        'classname' => 'local_sync_service_external',
        'methodname' => 'local_sync_service_add_new_course_module_page',
        'classpath' => 'local/sync_service/externallib.php',
        'description' => 'Add course module Page',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/page:addinstance',
    ),
    'local_course_add_new_course_module_book' => array(
        'classname' => 'local_sync_service_external',
        'methodname' => 'local_sync_service_add_new_course_module_book',
        'classpath' => 'local/sync_service/externallib.php',
        'description' => 'Add course module Book',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/book:addinstance',
    ),
    'local_course_import_html_in_book' => array(
        'classname' => 'local_sync_service_external',
        'methodname' => 'local_sync_service_import_html_in_book',
        'classpath' => 'local/sync_service/externallib.php',
        'description' => 'Upload chapters in book',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'booktool/importhtml:import',
    ),
    'local_course_delete_all_chapters_from_book' => array(
        'classname' => 'local_sync_service_external',
        'methodname' => 'local_sync_service_delete_all_chapters_from_book',
        'classpath' => 'local/sync_service/externallib.php',
        'description' => 'Delete all chapters from book',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/book:edit',
    ),
    'local_course_update_course_module_resource' => array(
        'classname' => 'local_sync_service_external',
        'methodname' => 'local_sync_service_update_course_module_resource',
        'classpath' => 'local/sync_service/externallib.php',
        'description' => 'Update course module resource',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/resource:addinstance',
    ),
    'local_course_update_course_module_label' => array(
        'classname' => 'local_sync_service_external',
        'methodname' => 'local_sync_service_update_course_module_label',
        'classpath' => 'local/sync_service/externallib.php',
        'description' => 'Update course module of type label (aka text&media)',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/label:addinstance',
    ),    
    'local_course_update_course_module_page' => array(
        'classname' => 'local_sync_service_external',
        'methodname' => 'local_sync_service_update_course_module_page',
        'classpath' => 'local/sync_service/externallib.php',
        'description' => 'Update course module of type page',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/page:addinstance',
    ),
    'local_course_update_course_module_assignment' => array(
        'classname' => 'local_sync_service_external',
        'methodname' => 'local_sync_service_update_course_module_assignment',
        'classpath' => 'local/sync_service/externallib.php',
        'description' => 'Update course module of type assignment',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/assign:addinstance',
    ),
    'local_course_update_course_module_lesson' => array(
        'classname' => 'local_sync_service_external',
        'methodname' => 'local_sync_service_update_course_module_lesson',
        'classpath' => 'local/sync_service/externallib.php',
        'description' => 'Update course module of type lesson',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/lesson:addinstance',
    ),
    'local_course_update_course_module_lesson_contentpage' => array(
        'classname' => 'local_sync_service_external',
        'methodname' => 'local_sync_service_update_course_module_lesson_contentpage',
        'classpath' => 'local/sync_service/externallib.php',
        'description' => 'Update page in a lesson',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/lesson:addinstance',
    ),
    'local_course_save_attachment_in_assignment' => array(
        'classname' => 'local_sync_service_external',
        'methodname' => 'local_sync_service_assignment_save_attachment',
        'classpath' => 'local/sync_service/externallib.php',
        'description' => 'Save attachment in assignment module',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/assign:addinstance',
    ),
    'local_course_save_attachment_in_label' => array(
        'classname' => 'local_sync_service_external',
        'methodname' => 'local_sync_service_label_save_attachment',
        'classpath' => 'local/sync_service/externallib.php',
        'description' => 'Add attachment in label module',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/label:addinstance',
    ),
    'local_course_save_attachment_in_page' => array(
        'classname' => 'local_sync_service_external',
        'methodname' => 'local_sync_service_page_save_attachment',
        'classpath' => 'local/sync_service/externallib.php',
        'description' => 'Add attachment in page module',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/page:addinstance',
    ),
    'local_course_save_attachment_in_lesson' => array(
        'classname' => 'local_sync_service_external',
        'methodname' => 'local_sync_service_lesson_save_attachment',
        'classpath' => 'local/sync_service/externallib.php',
        'description' => 'Save attachment in Lesson module',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/lesson:addinstance',
    ),
    'local_course_save_attachment_in_lessonpage' => array(
        'classname' => 'local_sync_service_external',
        'methodname' => 'local_sync_service_lessonpage_save_attachment',
        'classpath' => 'local/sync_service/externallib.php',
        'description' => 'Save attachment in Lesson module',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/lesson:addinstance',
    )


);

$services = array(
    'Course Sync Extension Service' => array(
        'functions' => array(
            'local_course_add_new_section',
            'local_course_add_new_course_module_url',
            'local_course_add_new_course_module_page',
            'local_course_add_new_course_module_book',
            'local_course_import_html_in_book',
            'local_course_delete_all_chapters_from_book',
            'local_course_add_new_course_module_resource',
            'local_course_update_course_module_resource',
            'local_course_update_course_module_label',
            'local_course_move_module_to_specific_position',
            'local_course_add_new_course_module_directory',
            'local_course_add_files_to_directory',
            'local_course_update_course_module_page',
            'local_course_update_course_module_assignment',
            'local_course_update_course_module_lesson',
            'local_course_update_course_module_lesson_contentpage',
            'local_course_save_attachment_in_assignment',
            'local_course_save_attachment_in_label',
            'local_course_save_attachment_in_page',
            'local_course_save_attachment_in_lesson',
            'local_course_save_attachment_in_lessonpage',
            'core_course_get_courses',
            'core_course_get_contents',
            'core_course_get_course_module',
            'core_enrol_get_users_courses',
            'core_webservice_get_site_info',
            'core_course_delete_modules',
            'core_course_get_user_administration_options',

        ),
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'sync_service',
        'downloadfiles' => 1,
        'uploadfiles'  => 1
    )
);
