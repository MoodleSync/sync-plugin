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
 * Class which contains the implementations of the added functions.
 *
 * @package local_sync_service
 * @copyright 2022 Daniel Schröter
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_completion\progress;
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/course/lib.php');


defined('MOODLE_INTERNAL') || die();

class local_sync_service_external extends external_api {
    /**
     * Defines the necessary method parameters.
     * @return external_function_parameters
     */
    public static function local_sync_service_add_new_section_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value( PARAM_TEXT, 'id of course' ),
                'sectionname' => new external_value( PARAM_TEXT, 'name of section' ),
                'sectionnum' => new external_value( PARAM_TEXT, 'position of the new section ' ),
            )
        );
    }

    /**
     * Creating and positioning of a new section.
     *
     * @param $courseid The course id.
     * @param $sectionname Name of the new section.
     * @param $sectionnum The position of the section inside the course, will be placed before a exisiting section with same sectionnum.
     * @return $update Message: Successful.
     */
    public static function local_sync_service_add_new_section($courseid, $sectionname, $sectionnum) {
        global $DB, $CFG;
        // Parameter validation.
        $params = self::validate_parameters(
        self::local_sync_service_add_new_section_parameters(),
            array(
                'courseid' => $courseid,
                'sectionname' => $sectionname,
                'sectionnum' => $sectionnum,
            )
        );

        // Ensure the current user has required permission in this course.
        $context = context_course::instance($params[ 'courseid' ]);
        self::validate_context($context);

        // Required permissions.
        require_capability('block/section_links:addinstance', $context);

        $cw = course_create_section($params[ 'courseid' ], $params[ 'sectionnum' ], false);

        $section = $DB->get_record('course_sections', array('id' => $cw->id), '*', MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $section->course), '*', MUST_EXIST);

        $data[ 'name' ] = $params[ 'sectionname' ];

        course_update_section($course, $section, $data);

        $update = [
            'message' => 'Successful',
        ];
        return $update;
    }

    /**
     * Obtains the Parameter which will be returned.
     * @return external_description
     */
    public static function local_sync_service_add_new_section_returns() {
        return new external_single_structure(
            array(
                'message' => new external_value( PARAM_TEXT, 'if the execution was successful' ),
            )
        );
    }


    /**
     * Defines the necessary method parameters.
     * @return external_function_parameters
     */
    public static function local_sync_service_add_new_course_module_url_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value( PARAM_TEXT, 'id of course' ),
                'sectionnum' => new external_value( PARAM_TEXT, 'relative number of the section' ),
                'urlname' => new external_value( PARAM_TEXT, 'displayed mod name' ),
                'url' => new external_value( PARAM_TEXT, 'url to insert' ),
                'time' => new external_value( PARAM_TEXT, 'defines the mod. visibility', VALUE_DEFAULT, null ),
                'visible' => new external_value( PARAM_TEXT, 'defines the mod. visibility' ),
                'beforemod' => new external_value( PARAM_TEXT, 'mod to set before', VALUE_DEFAULT, null ),
            )
        );
    }


    /**
     * Method to create a new course module containing a url.
     *
     * @param $courseid The course id.
     * @param $sectionnum The number of the section inside the course.
     * @param $urlname Displayname of the Module.
     * @param $url Url to publish.
     * @param $time availability time.
     * @param $visible visible for course members.
     * @param $beforemod Optional parameter, a Module where the new Module should be placed before.
     * @return $update Message: Successful and $cmid of the new Module.
     */
    public static function local_sync_service_add_new_course_module_url($courseid, $sectionnum, $urlname, $url, $time = null, $visible, $beforemod = null) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/' . '/url' . '/lib.php');

        // Parameter validation.
        $params = self::validate_parameters(
            self::local_sync_service_add_new_course_module_url_parameters(),
            array(
                'courseid' => $courseid,
                'sectionnum' => $sectionnum,
                'urlname' => $urlname,
                'url' => $url,
                'time' => $time,
                'visible' => $visible,
                'beforemod' => $beforemod,
            )
        );

        // Ensure the current user has required permission in this course.
        $context = context_course::instance($params[ 'courseid' ]);
        self::validate_context($context);

        // Required permissions.
        require_capability('mod/url:addinstance', $context);

        $instance = new \stdClass();
        $instance->course = $params[ 'courseid' ];
        $instance->name = $params[ 'urlname' ];
        $instance->intro = null;
        $instance->introformat = \FORMAT_HTML;
        $instance->externalurl = $params[ 'url' ];
        $instance->id = url_add_instance($instance, null);

        $modulename = 'url';

        $cm = new \stdClass();
        $cm->course     = $params[ 'courseid' ];
        $cm->module     = $DB->get_field( 'modules', 'id', array('name' => $modulename) );
        $cm->instance   = $instance->id;
        $cm->section    = $params[ 'sectionnum' ];
        if (!is_null($params[ 'time' ])) {
            $cm->availability = "{\"op\":\"&\",\"c\":[{\"type\":\"date\",\"d\":\">=\",\"t\":" . $params[ 'time' ] . "}],\"showc\":[" . $params[ 'visible' ] . "]}";
        } else if ( $params[ 'visible' ] === 'false' ) {
            $cm->visible = 0;
        }

        $cm->id = add_course_module( $cm );
        $cmid = $cm->id;

        $section->id = course_add_cm_to_section($params[ 'courseid' ], $cmid, $params[ 'sectionnum' ], $params[ 'beforemod' ]);

        $update = [
            'message' => 'Successful',
            'id' => $cmid,
        ];
        return $update;
    }

    /**
     * Obtains the Parameter which will be returned.
     * @return external_description
     */
    public static function local_sync_service_add_new_course_module_url_returns() {
        return new external_single_structure(
            array(
                'message' => new external_value( PARAM_TEXT, 'if the execution was successful' ),
                'id' => new external_value( PARAM_TEXT, 'cmid of the new module' ),
            )
        );
    }

    /**
     * Defines the necessary method parameters.
     * @return external_function_parameters
     */
    public static function local_sync_service_add_new_course_module_resource_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value( PARAM_TEXT, 'id of course' ),
                'sectionnum' => new external_value( PARAM_TEXT, 'relative number of the section' ),
                'itemid' => new external_value( PARAM_TEXT, 'id of the upload' ),
                'displayname' => new external_value( PARAM_TEXT, 'displayed mod name' ),
                'time' => new external_value( PARAM_TEXT, 'defines the mod. availability', VALUE_DEFAULT, null ),
                'visible' => new external_value( PARAM_TEXT, 'defines the mod. visibility' ),
                'beforemod' => new external_value( PARAM_TEXT, 'mod to set before', VALUE_DEFAULT, null ),
            )
        );
    }

    /**
     * Method to create a new course module containing a file.
     *
     * @param $courseid The course id.
     * @param $sectionnum The number of the section inside the course.
     * @param $itemid File to publish.
     * @param $displayname Displayname of the Module.
     * @param $time availability time.
     * @param $visible visible for course members.
     * @param $beforemod Optional parameter, a Module where the new Module should be placed before.
     * @return $update Message: Successful and $cmid of the new Module.
     */
    public static function local_sync_service_add_new_course_module_resource($courseid, $sectionnum, $itemid, $displayname, $time = null, $visible, $beforemod = null) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/' . '/resource' . '/lib.php');
        require_once($CFG->dirroot . '/availability/' . '/condition' . '/date' . '/classes' . '/condition.php');

        // Parameter validation.
        $params = self::validate_parameters(
            self::local_sync_service_add_new_course_module_resource_parameters(),
            array(
                'courseid' => $courseid,
                'sectionnum' => $sectionnum,
                'itemid' => $itemid,
                'displayname' => $displayname,
                'time' => $time,
                'visible' => $visible,
                'beforemod' => $beforemod,
            )
        );

        // Ensure the current user has required permission in this course.
        $context = context_course::instance($params[ 'courseid' ]);
        self::validate_context($context);

        // Required permissions.
        require_capability('mod/resource:addinstance', $context);

        $modulename = 'resource';

        $cm = new \stdClass();
        $cm->course     = $params[ 'courseid' ];
        $cm->module     = $DB->get_field('modules', 'id', array( 'name' => $modulename ));
        $cm->section    = $params[ 'sectionnum' ];
        if (!is_null($params[ 'time' ])) {
            $cm->availability = "{\"op\":\"&\",\"c\":[{\"type\":\"date\",\"d\":\">=\",\"t\":" . $params[ 'time' ] . "}],\"showc\":[" . $params[ 'visible' ] . "]}";
        } else if ( $params[ 'visible' ] === 'false' ) {
            $cm->visible = 0;
        }
        $cm->id = add_course_module($cm);
        $cmid = $cm->id;

        $instance = new \stdClass();
        $instance->course = $params[ 'courseid' ];
        $instance->name = $params[ 'displayname' ];
        $instance->intro = null;
        $instance->introformat = \FORMAT_HTML;
        $instance->coursemodule = $cmid;

        $instance->files = $params[ 'itemid' ];
        $instance->id = resource_add_instance($instance, null);

        $section->id = course_add_cm_to_section($params[ 'courseid' ], $cmid, $params[ 'sectionnum' ], $params[ 'beforemod' ]);

        $update = [
            'message' => 'Successful',
            'id' => $cmid,
        ];
        return $update;
    }

    /**
     * Obtains the Parameter which will be returned.
     * @return external_description
     */
    public static function local_sync_service_add_new_course_module_resource_returns() {
        return new external_single_structure(
            array(
                'message' => new external_value( PARAM_TEXT, 'if the execution was successful' ),
                'id' => new external_value( PARAM_TEXT, 'cmid of the new module' ),
            )
        );
    }

    /**
     * Defines the necessary method parameters.
     * @return external_function_parameters
     */
    public static function local_sync_service_move_module_to_specific_position_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value( PARAM_TEXT, 'id of module' ),
                'sectionid' => new external_value( PARAM_TEXT, 'relative number of the section' ),
                'beforemod' => new external_value( PARAM_TEXT, 'mod to set before', VALUE_DEFAULT, null ),
            )
        );
    }

    /**
     * Method to position an existing course module.
     *
     * @param $cmid The Module to move.
     * @param $sectionid The id of the section inside the course.
     * @param $beforemod Optional parameter, a Module where the new Module should be placed before.
     * @return $update Message: Successful and $cmid of the new Module.
     */
    public static function local_sync_service_move_module_to_specific_position($cmid, $sectionid, $beforemod = null) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/course/' . '/lib.php');

        // Parameter validation.
        $params = self::validate_parameters(
            self::local_sync_service_move_module_to_specific_position_parameters(),
            array(
                'cmid' => $cmid,
                'sectionid' => $sectionid,
                'beforemod' => $beforemod,
            )
        );

        // Ensure the current user has required permission.
        $modcontext = context_module::instance( $params[ 'cmid' ] );
        self::validate_context( $modcontext );

        $cm = get_coursemodule_from_id('', $params[ 'cmid' ]);

        // Ensure the current user has required permission in this course.
        $context = context_course::instance($cm->course);
        self::validate_context($context);

        // Required permissions.
        require_capability('moodle/course:movesections', $context);

        $section = $DB->get_record('course_sections', array( 'id' => $params[ 'sectionid' ], 'course' => $cm->course ));

        moveto_module($cm, $section, $params[ 'beforemod' ]);

        $update = [
            'message' => 'Successful',
        ];
        return $update;
    }

    /**
     * Obtains the Parameter which will be returned.
     * @return external_description
     */
    public static function local_sync_service_move_module_to_specific_position_returns() {
        return new external_single_structure(
            array(
                'message' => new external_value( PARAM_TEXT, 'if the execution was successful' )
            )
        );
    }

    /**
     * Defines the necessary method parameters.
     * @return external_function_parameters
     */
    public static function local_sync_service_add_new_course_module_directory_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value( PARAM_TEXT, 'id of course' ),
                'sectionnum' => new external_value( PARAM_TEXT, 'relative number of the section' ),
                'itemid' => new external_value( PARAM_TEXT, 'id of the upload' ),
                'displayname' => new external_value( PARAM_TEXT, 'displayed mod name' ),
                'time' => new external_value( PARAM_TEXT, 'defines the mod. visibility', VALUE_DEFAULT, null ),
                'beforemod' => new external_value( PARAM_TEXT, 'mod to set before', VALUE_DEFAULT, null ),
            )
        );
    }

    /**
     * Method to create a new course module of type folder.
     *
     * @param $courseid The course id.
     * @param $sectionnum The number of the section inside the course.
     * @param $displayname Displayname of the Module.
     * @param $itemid Files in same draft area to upload.
     * @param $time availability time.
     * @param $beforemod Optional parameter, a Module where the new Module should be placed before.
     * @return $update Message: Successful and $cmid of the new Module.
     */
    public static function local_sync_service_add_new_course_module_directory($courseid, $sectionnum, $itemid, $displayname, $time = null, $beforemod = null) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/' . '/folder' . '/lib.php');

        // Parameter validation.
        $params = self::validate_parameters(
            self::local_sync_service_add_new_course_module_directory_parameters(),
            array(
                'courseid' => $courseid,
                'sectionnum' => $sectionnum,
                'itemid' => $itemid,
                'displayname' => $displayname,
                'time' => $time,
                'beforemod' => $beforemod,
            )
        );

        // Ensure the current user has required permission in this course.
        $context = context_course::instance($params[ 'courseid' ]);
        self::validate_context($context);

        // Required permissions.
        require_capability('mod/folder:addinstance', $context);

        $modulename = 'folder';

        $cm = new \stdClass();
        $cm->course     = $params[ 'courseid' ];
        $cm->module     = $DB->get_field('modules', 'id', array( 'name' => $modulename ));
        $cm->section    = $params[ 'sectionnum' ];
        if (!is_null($params[ 'time' ])) {
            $cm->availability = "{\"op\":\"&\",\"c\":[{\"type\":\"date\",\"d\":\">=\",\"t\":" . $params[ 'time' ] . "}],\"showc\":[true]}";
        }
        $cm->id = add_course_module($cm);
        $cmid = $cm->id;

        $instance = new \stdClass();
        $instance->course = $params[ 'courseid' ];
        $instance->name = $params[ 'displayname' ];
        $instance->coursemodule = $cmid;
        $instance->introformat = FORMAT_HTML;
        $instance->intro = '<p>'.$params[ 'displayname' ].'</p>';
        $instance->files = $params[ 'itemid' ];
        $instance->id = folder_add_instance($instance, null);

        $section->id = course_add_cm_to_section($params[ 'courseid' ], $cmid, $params[ 'sectionnum' ], $params[ 'beforemod' ]);

        $update = [
            'message' => 'Successful',
            'id' => $cmid,
        ];
        return $update;
    }

    /**
     * Obtains the Parameter which will be returned.
     * @return external_description
     */
    public static function local_sync_service_add_new_course_module_directory_returns() {
        return new external_single_structure(
            array(
                'message' => new external_value( PARAM_TEXT, 'if the execution was successful' ),
                'id' => new external_value( PARAM_TEXT, 'cmid of the new module' ),
            )
        );
    }

    /**
     * Defines the necessary method parameters.
     * @return external_function_parameters
     */
    public static function local_sync_service_add_files_to_directory_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value( PARAM_TEXT, 'id of course' ),
                'cmid' => new external_value( PARAM_TEXT, 'id of the module' ),
                'itemid' => new external_value( PARAM_TEXT, 'id of the upload' ),
                'displayname' => new external_value( PARAM_TEXT, 'displayed mod name' ),
                'instanceid' => new external_value( PARAM_TEXT, 'instance id of folder' ),
            )
        );
    }

    /**
     * This method implements the logic for the API-Call.
     * IMPORTANT: Still in progress, currently not working.
     *
     * @param $courseid The course id.
     * @param $cmid The module id.
     * @param $itemid File to update.
     * @param $instanceid The instance id.
     * @return $update Message: Successful.
     */
    public static function local_sync_service_add_files_to_directory($courseid, $cmid, $itemid, $instanceid) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/' . '/folder' . '/lib.php');

        // Parameter validation.
        $params = self::validate_parameters(
            self::local_sync_service_add_files_to_directory_parameters(),
            array(
                'courseid' => $courseid,
                'cmid' => $cmid,
                'itemid' => $itemid,
                'instanceid' => $instanceid,
            )
        );

        // Ensure the current user has required permission in this course.
        $context = context_course::instance($params[ 'courseid' ]);
        self::validate_context($context);

        // Required permissions.
        require_capability('mod/folder:addinstance', $context);

        $cmid        = $params[ 'cmid' ];
        $draftitemid = $params[ 'itemid' ];
        file_save_draft_area_files($draftitemid, $params[ 'instanceid' ], 'mod_folder', 'content', $draftitemid, array(
            'subdirs' => true));

        $update = [
            'message' => 'Successful',
        ];
        return $update;
    }

    /**
     * Obtains the Parameter which will be returned.
     * @return external_description
     */
    public static function local_sync_service_add_files_to_directory_returns() {
        return new external_single_structure(
            array(
                'message' => new external_value( PARAM_TEXT, 'if the execution was successful' ),
                )
        );
    }
}

