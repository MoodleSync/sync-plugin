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

use core_completion\progress;
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/course/lib.php');


defined('MOODLE_INTERNAL') || die();



function debug($data) {
    $output = $data;
    if (is_array($output))
        $output = implode(',', $output);

    #echo "<script>console.log('Debug Objects: " . $output . "' );</script>";
    echo "Debug Objects: " . $output . "";
}


/**
 * Class which contains the implementations of the added functions.
 *
 * @package local_sync_service
 * @copyright 2022 Daniel Schröter
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);

        // Required permissions.
        require_capability('block/section_links:addinstance', $context);

        $cw = course_create_section($params['courseid'], $params['sectionnum'], false);

        $section = $DB->get_record('course_sections', array('id' => $cw->id), '*', MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $section->course), '*', MUST_EXIST);

        $data['name'] = $params['sectionname'];

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
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);

        // Required permissions.
        require_capability('mod/url:addinstance', $context);

        $instance = new \stdClass();
        $instance->course = $params['courseid'];
        $instance->name = $params['urlname'];
        $instance->intro = null;
        $instance->introformat = \FORMAT_HTML;
        $instance->externalurl = $params['url'];
        $instance->id = url_add_instance($instance, null);

        $modulename = 'url';

        $cm = new \stdClass();
        $cm->course     = $params['courseid'];
        $cm->module     = $DB->get_field( 'modules', 'id', array('name' => $modulename) );
        $cm->instance   = $instance->id;
        $cm->section    = $params['sectionnum'];
        if (!is_null($params['time'])) {
            $cm->availability = "{\"op\":\"&\",\"c\":[{\"type\":\"date\",\"d\":\">=\",\"t\":" . $params['time'] . "}],\"showc\":[" . $params['visible'] . "]}";
        } else if ( $params['visible'] === 'false' ) {
            $cm->visible = 0;
        }

        $cm->id = add_course_module( $cm );
        $cmid = $cm->id;

        course_add_cm_to_section($params['courseid'], $cmid, $params['sectionnum'], $params['beforemod']);

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
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);

        // Required permissions.
        require_capability('mod/resource:addinstance', $context);

        $modulename = 'resource';

        $cm = new \stdClass();
        $cm->course     = $params['courseid'];
        $cm->module     = $DB->get_field('modules', 'id', array( 'name' => $modulename ));
        $cm->section    = $params['sectionnum'];
        if (!is_null($params['time'])) {
            $cm->availability = "{\"op\":\"&\",\"c\":[{\"type\":\"date\",\"d\":\">=\",\"t\":" . $params['time'] . "}],\"showc\":[" . $params['visible'] . "]}";
        } else if ( $params['visible'] === 'false' ) {
            $cm->visible = 0;
        }
        $cm->id = add_course_module($cm);
        $cmid = $cm->id;

        $instance = new \stdClass();
        $instance->course = $params['courseid'];
        $instance->name = $params['displayname'];
        $instance->intro = null;
        $instance->introformat = \FORMAT_HTML;
        $instance->coursemodule = $cmid;

        $instance->files = $params['itemid'];
        $instance->id = resource_add_instance($instance, null);

        course_add_cm_to_section($params['courseid'], $cmid, $params['sectionnum'], $params['beforemod']);

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
        $modcontext = context_module::instance( $params['cmid'] );
        self::validate_context( $modcontext );

        $cm = get_coursemodule_from_id('', $params['cmid']);

        // Ensure the current user has required permission in this course.
        $context = context_course::instance($cm->course);
        self::validate_context($context);

        // Required permissions.
        require_capability('moodle/course:movesections', $context);

        $section = $DB->get_record('course_sections', array( 'id' => $params['sectionid'], 'course' => $cm->course ));

        moveto_module($cm, $section, $params['beforemod']);

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
                'visible' => new external_value( PARAM_TEXT, 'defines the mod. visibility' ),
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
     * @param $visible visible for course members.
     * @param $beforemod Optional parameter, a Module where the new Module should be placed before.
     * @return $update Message: Successful and $cmid of the new Module.
     */
    public static function local_sync_service_add_new_course_module_directory($courseid, $sectionnum, $itemid, $displayname, $time = null, $visible, $beforemod = null) {
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
                'visible' => $visible,
                'beforemod' => $beforemod,
            )
        );

        // Ensure the current user has required permission in this course.
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);

        // Required permissions.
        require_capability('mod/folder:addinstance', $context);

        $modulename = 'folder';

        $cm = new \stdClass();
        $cm->course     = $params['courseid'];
        $cm->module     = $DB->get_field('modules', 'id', array( 'name' => $modulename ));
        $cm->section    = $params['sectionnum'];
        if (!is_null($params['time'])) {
            $cm->availability = "{\"op\":\"&\",\"c\":[{\"type\":\"date\",\"d\":\">=\",\"t\":" . $params['time'] . "}],\"showc\":[" . $params['visible'] . "]}";
        } else if ( $params['visible'] === 'false' ) {
            $cm->visible = 0;
        }
        $cm->id = add_course_module($cm);
        $cmid = $cm->id;

        $instance = new \stdClass();
        $instance->course = $params['courseid'];
        $instance->name = $params['displayname'];
        $instance->coursemodule = $cmid;
        $instance->introformat = FORMAT_HTML;
        $instance->intro = '<p>'.$params['displayname'].'</p>';
        $instance->files = $params['itemid'];
        $instance->id = folder_add_instance($instance, null);

        course_add_cm_to_section($params['courseid'], $cmid, $params['sectionnum'], $params['beforemod']);

        $update = [
            'message' => 'Successful',
            'id' => $instance->id,
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
                'itemid' => new external_value( PARAM_TEXT, 'id of the upload' ),
                'contextid' => new external_value( PARAM_TEXT, 'contextid of folder' ),
            )
        );
    }

    /**
     * This method implements the logic for the API-Call.
     *
     * @param $courseid The course id.
     * @param $itemid File(-s) to add.
     * @param $contextid Modules contextid.
     * @return $update Message: Successful.
     */
    public static function local_sync_service_add_files_to_directory($courseid, $itemid, $contextid) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/' . '/folder' . '/lib.php');

        // Parameter validation.
        $params = self::validate_parameters(
            self::local_sync_service_add_files_to_directory_parameters(),
            array(
                'courseid' => $courseid,
                'itemid' => $itemid,
                'contextid' => $contextid,
            )
        );

        // Ensure the current user has required permission in this course.
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);

        // Required permissions.
        require_capability('mod/folder:managefiles', $context);

        file_merge_files_from_draft_area_into_filearea($params['itemid'], $params['contextid'], 'mod_folder', 'content', 0);

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

    /**
     * Defines the necessary method parameters.
     * @return external_function_parameters
     */
    public static function local_sync_service_add_new_course_module_page_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value( PARAM_TEXT, 'id of course' ),
                'sectionnum' => new external_value( PARAM_TEXT, 'relative number of the section' ),
                'urlname' => new external_value( PARAM_TEXT, 'displayed mod name' ),
                'content' => new external_value( PARAM_TEXT, 'Content to insert' ),
                'time' => new external_value( PARAM_TEXT, 'defines the mod. visibility', VALUE_DEFAULT, null ),
                'visible' => new external_value( PARAM_TEXT, 'defines the mod. visibility' ),
                'beforemod' => new external_value( PARAM_TEXT, 'mod to set before', VALUE_DEFAULT, null ),
            )
        );
    }


    /**
     * Method to create a new course module containing a Page.
     *
     * @param $courseid The course id.
     * @param $sectionnum The number of the section inside the course.
     * @param $urlname Displayname of the Module.
     * @param $content Content to publish.
     * @param $time availability time.
     * @param $visible visible for course members.
     * @param $beforemod Optional parameter, a Module where the new Module should be placed before.
     * @return $update Message: Successful and $cmid of the new Module.
     */
    public static function local_sync_service_add_new_course_module_page($courseid, $sectionnum, $urlname, $content, $time = null, $visible, $beforemod = null) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/' . '/page' . '/lib.php');

        debug("local_sync_service_add_new_course_module_page");


        // Parameter validation.
        $params = self::validate_parameters(
            self::local_sync_service_add_new_course_module_page_parameters(),
            array(
                'courseid' => $courseid,
                'sectionnum' => $sectionnum,
                'urlname' => $urlname,
                'content' => $content,
                'time' => $time,
                'visible' => $visible,
                'beforemod' => $beforemod,
            )
        );

        // Ensure the current user has required permission in this course.
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);


        // Required permissions.
        require_capability('mod/page:addinstance', $context);

        $modulename = 'page';
        $cm = new \stdClass();
        $cm->course     = $params['courseid'];
        $cm->module     = $DB->get_field( 'modules', 'id', array('name' => $modulename) );
        $cm->section    = $params['sectionnum'];
        if (!is_null($params['time'])) {
            $cm->availability = "{\"op\":\"&\",\"c\":[{\"type\":\"date\",\"d\":\">=\",\"t\":" . $params['time'] . "}],\"showc\":[" . $params['visible'] . "]}";
        } else if ( $params['visible'] === 'false' ) {
            $cm->visible = 0;
        }

        $cm->id = add_course_module( $cm );
        $cmid = $cm->id;

        $instance = new \stdClass();
        $instance->course = $params['courseid'];
        $instance->name = $params['urlname'];
        $instance->intro = null;
        $instance->introformat = \FORMAT_HTML;
        $instance->intro = '<p>'.$params['urlname'].'</p>';
        $instance->page = array('format' => \FORMAT_MARKDOWN,'text' => $content, 'itemid' => false);
        $instance->coursemodule = $cmid;
        $instance->id = page_add_instance($instance, $instance);

        $secsectionid = course_add_cm_to_section($params['courseid'], $cmid, $params['sectionnum'], $params['beforemod']);

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
    public static function local_sync_service_add_new_course_module_page_returns() {
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
    public static function local_sync_service_add_new_course_module_book_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value( PARAM_TEXT, 'id of course' ),
                'sectionnum' => new external_value( PARAM_TEXT, 'relative number of the section' ),
                'urlname' => new external_value( PARAM_TEXT, 'displayed mod name' ),
                'content' => new external_value( PARAM_TEXT, 'Content to insert' ),
                'time' => new external_value( PARAM_TEXT, 'defines the mod. visibility', VALUE_DEFAULT, null ),
                'visible' => new external_value( PARAM_TEXT, 'defines the mod. visibility' ),
                'beforemod' => new external_value( PARAM_TEXT, 'mod to set before', VALUE_DEFAULT, null ),
            )
        );
    }


    /**
     * Method to create a new course module containing a book.
     *
     * @param $courseid The course id.
     * @param $sectionnum The number of the section inside the course.
     * @param $urlname Displayname of the Module.
     * @param $content Content to publish.
     * @param $time availability time.
     * @param $visible visible for course members.
     * @param $beforemod Optional parameter, a Module where the new Module should be placed before.
     * @return $update Message: Successful and $cmid of the new Module.
     */
    public static function local_sync_service_add_new_course_module_book($courseid, $sectionnum, $urlname, $content, $time = null, $visible, $beforemod = null) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/' . '/book' . '/lib.php');
        require_once($CFG->dirroot . '/mod/' . '/book' . '/locallib.php');

        debug("local_sync_service_add_new_course_module_book");

        // Parameter validation.
        $params = self::validate_parameters(
            self::local_sync_service_add_new_course_module_book_parameters(),
            array(
                'courseid' => $courseid,
                'sectionnum' => $sectionnum,
                'urlname' => $urlname,
                'content' => $content,
                'time' => $time,
                'visible' => $visible,
                'beforemod' => $beforemod,
            )
        );

        // Ensure the current user has required permission in this course.
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);

        // Required permissions.
        require_capability('mod/book:addinstance', $context);


        $instance = new \stdClass();
        $instance->course = $params['courseid'];
        $instance->name = $params['urlname'];
        $instance->introformat = \FORMAT_HTML;
        $instance->completionexpected=null; //todo
        $instance->intro = '<p>'.$params['urlname'].'</p>';
        $instance->visible=1;
        $instance->id = book_add_instance($instance, null);

        debug("added book $instance->id");

        $modulename = 'book';
        $cm = new \stdClass();
        $cm->course     = $params['courseid'];
        $cm->instance   = $instance->id;
        $cm->module     = $DB->get_field( 'modules', 'id', array('name' => $modulename) );
        $cm->section    = $params['sectionnum'];
        if (!is_null($params['time'])) {
            $cm->availability = "{\"op\":\"&\",\"c\":[{\"type\":\"date\",\"d\":\">=\",\"t\":" . $params['time'] . "}],\"showc\":[" . $params['visible'] . "]}";
        } else if ( $params['visible'] === 'false' ) {
            $cm->visible = 0;
        }

        $cm->id = add_course_module( $cm );
        $cmid = $cm->id;
        debug("course module added $cmid\n");

        $secsectionid = course_add_cm_to_section($params['courseid'], $cmid, $params['sectionnum'], $params['beforemod']);

        debug("prepare add to section done $sectionid ");

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
    public static function local_sync_service_add_new_course_module_book_returns() {
        return new external_single_structure(
            array(
                'message' => new external_value( PARAM_TEXT, 'if the execution was successful' ),
                'id' => new external_value( PARAM_TEXT, 'cmid of the new module' ),
            )
        );
    }
//


    /**
     * Defines the necessary method parameters.
     * @return external_function_parameters
     */
    public static function local_sync_service_import_html_in_book_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value( PARAM_TEXT, 'course module id of book' ),
                'itemid' => new external_value( PARAM_TEXT, 'itemid containing preloaded zip file to import in book' ),
                'type' => new external_value( PARAM_TEXT, 'type (typezipdirs or typezipfiles)' )
            )
        );
    }


    /**
     * Method to upload ZIP file in book so it appears as chapters in Moodle
     *
     * @param $cmid Course module id
     * @param $itemid Item id
     * @param $type Type of import
     * @return $update Message: Successful and return value 0 if ok
     */
    public static function local_sync_service_import_html_in_book($cmid, $itemid, $type) {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot . '/mod/' . '/book' . '/lib.php');
        require_once($CFG->dirroot . '/mod/' . '/book' . '/locallib.php');
        require_once($CFG->dirroot . '/mod/' . '/book/tool/importhtml' . '/locallib.php');

        debug("local_sync_service_import_html_in_book");
        // Parameter validation.
        $params = self::validate_parameters(
            self::local_sync_service_import_html_in_book_parameters(),
            array(
                'cmid' => $cmid,
                'itemid' => $itemid,
                'type' => $type
            )
        );

        // Ensure the current user has required permission in this course.
        $cm = get_coursemodule_from_id('book', $cmid, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        $book = $DB->get_record('book', array('id'=>$cm->instance), '*', MUST_EXIST);

        require_capability('booktool/importhtml:import', $context);

        $fs = get_file_storage();
        debug("get info about itemid $itemid");
        if (!$files = $fs->get_area_files(context_user::instance($USER->id)->id, 'user', 'draft', $itemid, 'id', false)) {
              debug("no itemid $itemid found");
              $update = ['message' => 'Itemid not found','rv' => -1];
        }
        else {
            $file = reset($files);
            if ($file->get_mimetype() != 'application/zip') {
                debug("$itemid is not a zip content");
                $update = ['message' => 'Not a zip content','rv' => -1];
            }
            else{
                debug("all clear, let's go");
                toolbook_importhtml_import_chapters($file, $type, $book, $context, false);
                $update = ['message' => 'Successful','rv' => 0];
            }
        }
        return $update;
    }

    /**
     * Obtains the Parameter which will be returned.
     * @return external_description
     */
    public static function local_sync_service_import_html_in_book_returns() {
        return new external_single_structure(
            array(
                'message' => new external_value( PARAM_TEXT, 'if the execution was successful' ),
                'rv' => new external_value( PARAM_TEXT, 'return value' ),
            )
        );
    }

//

   /**
     * Defines the necessary method parameters.
     * @return external_function_parameters
     */
    public static function local_sync_service_delete_all_chapters_from_book_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value( PARAM_TEXT, 'course module id of book' )
            )
        );
    }


    /**
     * Method to delete all chapters in a book
     *
     * @param $cmid Course module id
     * @return $update Message: Successful and return value 0 if ok
     */
    public static function local_sync_service_delete_all_chapters_from_book($cmid) {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot . '/mod/' . '/book' . '/lib.php');
        require_once($CFG->dirroot . '/mod/' . '/book' . '/locallib.php');

        debug("local_course_delete_all_chapters_from_book\n");
        // Parameter validation.
        $params = self::validate_parameters(
            self::local_sync_service_delete_all_chapters_from_book_parameters(),
            array(
                'cmid' => $cmid,
            )
        );

        // Ensure the current user has required permission in this course.
        $cm = get_coursemodule_from_id('book', $cmid, 0, false, MUST_EXIST);
        debug("module id  $cm->id\n");
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/book:edit', $context);

        $fs = get_file_storage();
        $book = $DB->get_record('book', array('id'=>$cm->instance), '*', MUST_EXIST);
        debug("book id $book->id\n");

        $chapter = $DB->get_records('book_chapters', array('bookid' => $book->id), 'pagenum', 'id, pagenum,subchapter, title, content, contentformat, hidden');
        foreach ($chapter as $id => $ch) {
            debug("in chapter $ch->id\n");


            $subchaptercount = 0;
            if (!$chapter->subchapter) {
                // This is a top-level chapter.
                // Make sure to remove any sub-chapters if there are any.
                $chapters = $DB->get_recordset_select('book_chapters', 'bookid = :bookid AND pagenum > :pagenum', [
                        'bookid' => $book->id,
                        'pagenum' => $chapter->pagenum,
                    ], 'pagenum');

                foreach ($chapters as $ch) {
                    debug("get chapter $ch->id\n");
                    if (!$ch->subchapter) {
                        // This is a new chapter. Any subsequent subchapters will be part of a different chapter.
                        break;
                    } else {
                        // This is subchapter of the chapter being removed.
                        core_tag_tag::remove_all_item_tags('mod_book', 'book_chapters', $ch->id);
                        $fs->delete_area_files($context->id, 'mod_book', 'chapter', $ch->id);
                        $DB->delete_records('book_chapters', ['id' => $ch->id]);

                        $subchaptercount++;
                    }
                }
                $chapters->close();
            }
            else
                debug("no subcharters to delete\n");

            // Now delete the actual chapter.
            debug("delete chapter $ch->id\n");
            core_tag_tag::remove_all_item_tags('mod_book', 'book_chapters', $ch->id);
            $fs->delete_area_files($context->id, 'mod_book', 'chapter', $ch->id);
            $DB->delete_records('book_chapters', ['id' => $ch->id]);
        }

        // Ensure that the book structure is correct.
        // book_preload_chapters will fix parts including the pagenum.
        $chapters = book_preload_chapters($book);

        book_add_fake_block($chapters, $chapter, $book, $cm);

        // Bump the book revision.
        $DB->set_field('book', 'revision', $book->revision + 1, ['id' => $book->id]);

        debug("all clear, let's go");
        $update = ['message' => 'Successful','rv' => 0];

        return $update;
    }

    /**
     * Obtains the Parameter which will be returned.
     * @return external_description
     */
    public static function local_sync_service_delete_all_chapters_from_book_returns() {
        return new external_single_structure(
            array(
                'message' => new external_value( PARAM_TEXT, 'if the execution was successful' ),
                'rv' => new external_value( PARAM_TEXT, 'return value' ),
            )
        );
    }


    /**
     * Defines the necessary method parameters.
     * @return external_function_parameters
     */
    public static function local_sync_service_update_course_module_resource_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value( PARAM_TEXT, 'id of module' ),
                'itemid' => new external_value( PARAM_TEXT, 'id of the upload' ),
                'displayname' => new external_value( PARAM_TEXT, 'displayed mod name', VALUE_DEFAULT, null  )
            )
        );
    }

    /**
     * Method to update a new course module containing a file.
     *
     * @param $courseid The course id.
     * @param $itemid File to publish.
     * @param $displayname Displayname of resource (optional)
     * @return $update Message: Successful and $cmid of the new Module.
     */
    public static function local_sync_service_update_course_module_resource($cmid, $itemid, $displayname) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/' . '/resource' . '/lib.php');
        require_once($CFG->dirroot . '/mod/' . '/resource' . '/locallib.php');
        require_once($CFG->dirroot . '/course/' . '/modlib.php');
        require_once($CFG->dirroot . '/availability/' . '/condition' . '/date' . '/classes' . '/condition.php');

        debug("local_sync_service_update_course_module_resource\n");
        // Parameter validation.
        $params = self::validate_parameters(
            self::local_sync_service_update_course_module_resource_parameters(),
            array(
                'cmid' => $cmid,
                'itemid' => $itemid,
                'displayname' => $displayname
            )
        );

        $cm = get_coursemodule_from_id('resource', $cmid, 0, false, MUST_EXIST);
        debug("module instance id  $cm->instance\n");

        // Ensure the current user has required permission in this course.
        $context = context_course::instance($cm->course);
        self::validate_context($context);

        // Required permissions.
        require_capability('mod/resource:addinstance', $context);

        $modulename = 'resource';
        $instance = new \stdClass();
        $instance->course = $cm->course;
        $instance->intro = "";
        $instance->introformat = \FORMAT_HTML;
        $instance->coursemodule = $cmid;
        $instance->files = $itemid;
        $instance->instance = $cm->instance;
        $instance->modulename = $modulename;
        $instance->type = 'mod';
        //display name is optional
        if (!is_null($params['displayname'])) {
            $instance->name = $params['displayname'];
        } else {
            $instance->name = $cm->name;
        }

        $instance->id = resource_update_instance($instance, null);
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        $moduleinfo = edit_module_post_actions($instance, $course);

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
    public static function local_sync_service_update_course_module_resource_returns() {
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
    public static function local_sync_service_update_course_module_label_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value( PARAM_TEXT, 'id of module' ),
                'htmlbody' => new external_value( PARAM_TEXT, 'HTML name', VALUE_DEFAULT, null  ),
            )
        );
    }

    /**
     * Method to update a new course module containing a file.
     *
     * @param $cmid The course module id.
     * @param $htmlbody HTML code to add to body
     * @return $update Message: Successful and $cmid of the new Module.
     */
    public static function local_sync_service_update_course_module_label($cmid, $htmlbody) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/availability/' . '/condition' . '/date' . '/classes' . '/condition.php');
        require_once($CFG->dirroot . '/mod/' . '/label' . '/lib.php');
        require_once($CFG->dirroot . '/course/' . '/modlib.php');

        debug("local_sync_service_update_course_module_label\n");
        // Parameter validation.
        $params = self::validate_parameters(
            self::local_sync_service_update_course_module_label_parameters(),
            array(
                'cmid' => $cmid,
                'htmlbody' => $htmlbody
            )
        );

        $cm = get_coursemodule_from_id('label', $cmid, 0, false, MUST_EXIST);

        // Ensure the current user has required permission in this course.
        $context = context_course::instance($cm->course);
        self::validate_context($context);


        // Required permissions.
        require_capability('mod/label:addinstance', $context);

        $modulename = 'label';
        $cm->module     = $DB->get_field( 'modules', 'id', array('name' => $modulename) );
        $instance = new \stdClass();
        $instance->course = $cm->course;
        $instance->intro = $htmlbody;

        $instance->introformat = \FORMAT_HTML;
        $instance->coursemodule = $cmid;
        $instance->instance = $cm->instance;
        $instance->modulename = $modulename;
        $instance->type = 'mod';
        $instance->visible = true;
        $instance->id = label_update_instance($instance, null);

        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

        $moduleinfo = edit_module_post_actions($instance, $course);

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
    public static function local_sync_service_update_course_module_label_returns() {
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
    public static function local_sync_service_update_course_module_page_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value( PARAM_TEXT, 'id of module' ),
                'content' => new external_value( PARAM_TEXT, 'HTML or Markdown code'  ),
                'format' => new external_value( PARAM_TEXT, 'Markdown or HTML', VALUE_DEFAULT, \FORMAT_MARKDOWN  ),
            )
        );
    }

    /**
     * Method to update a new course module containing a file.
     *
     * @param $cmid The course module id.
     * @param $content Content to add
     * @param $format HTML or Markdown(=default)
     * @return $update Message: Successful and $cmid of the new Module.
     */
    public static function local_sync_service_update_course_module_page($cmid, $content, $format) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/' . '/page' . '/lib.php');
        require_once($CFG->dirroot . '/course/' . '/modlib.php');

        //debug("local_sync_service_update_course_module_page\n");
        // Parameter validation.
        $params = self::validate_parameters(
            self::local_sync_service_update_course_module_page_parameters(),
            array(
                'cmid' => $cmid,
                'content' => $content,
                'format' => $format
            )
        );

        $cm = get_coursemodule_from_id('page', $cmid, 0, false, MUST_EXIST);

        // Ensure the current user has required permission in this course.
        $context = context_module::instance($cmid);
        self::validate_context($context);

        // Required permissions.
        require_capability('mod/page:addinstance', $context);

        $modulename = 'page';
        $cm->module = $DB->get_field( 'modules', 'id', array('name' => $modulename) );
        $instance = new \stdClass();
        $instance->course = $cm->course;
        $instance->contentformat = $format;
        $instance->page =  [
            'text' => html_entity_decode($content),
            'format' =>  $format,
        ];

        $instance->coursemodule = $cmid;
        $instance->instance = $cm->instance;
        $instance->modulename = $modulename;
        $instance->type = 'mod';
        $instance->visible = true;
        $instance->id = page_update_instance($instance, null);

        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
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
    public static function local_sync_service_update_course_module_page_returns() {
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

     public static function local_sync_service_update_course_module_assignment_parameters() {
        return new external_function_parameters(
            array(
                'assignments' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'cmid' => new external_value(PARAM_INT, 'ID of the assignment module'),
                            'desc' => new external_value(PARAM_TEXT, 'description of assisngment'),
                            'activity' => new external_value(PARAM_TEXT, 'activity in assignment', VALUE_OPTIONAL)
                        )
                    ), 'assignment courses to update'
                )
            )
        );
    }

    /**
     * Method to update a new course module containing a assignment.
     *
     * @param $cmid The course module id.
     * @param $desc  HTML code to add to description
     * @return $update Message: Successful and $cmid of the new Module.
     */
    public static function local_sync_service_update_course_module_assignment($assignments) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/' . '/assign' . '/lib.php');
        require_once($CFG->dirroot . '/mod/' . '/assign' . '/locallib.php');
        $warnings = array();

        //debug("local_sync_service_update_course_module_assignment\n");

        $params = self::validate_parameters(self::local_sync_service_update_course_module_assignment_parameters(), array('assignments' => $assignments));

        foreach ($params['assignments'] as $ass) {
            try {
                $cmid = $ass['cmid'];
                $desc = $ass['desc'];
                //debug(" cmid=$cmid , desc=$desc\n");

                if (array_key_exists('activity', $ass) ) {
                    $activity = $ass['activity'];
                }

            }catch (Exception $e) {
                debug(" exception\n");
                $warning = array();
                $warning['item'] = 'assignments';
                $warning['itemid'] = $ass['cmid'];
                if ($e instanceof moodle_exception) {
                    $warning['warningcode'] = $e->errorcode;
                } else {
                    $warning['warningcode'] = $e->getCode();
                }
                $warning['message'] = $e->getMessage();
                $warnings[] = $warning;
            }
        }

        $cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
        // Ensure the current user has required permission in this course.
        $context = context_module::instance($cmid);
        self::validate_context($context);
        require_capability('mod/assign:addinstance', $context);

        $dbparams = array('id'=>$cm->instance);
        if (! $instance = $DB->get_record('assign', $dbparams, '*')) {
            return false;
        }

        $instance->id = $cm->instance;

        $instance->activityeditor =  [
            'text' => html_entity_decode($activity),
            'format' =>  \FORMAT_MARKDOWN,
        ];

        $instance->introformat = \FORMAT_MARKDOWN;
        $instance->activityformat = \FORMAT_MARKDOWN;
        $instance->coursemodule = $cmid;
        $instance->instance = $cm->instance;
        $instance->modulename ='assign';
        $instance->type = 'mod';
        $instance->visible = true;

        $instance->intro = html_entity_decode($desc);
        $instance->id = assign_update_instance($instance, null);

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
    public static function local_sync_service_update_course_module_assignment_returns() {
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

     public static function local_sync_service_update_course_module_lesson_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value( PARAM_INT, 'id of module' ),
                'desc' => new external_value( PARAM_TEXT, 'description'  )               
            )
        );
    }

    /**
     * Method to update a lesson module 
     *
     * @param $cmid The course module id.
     * @param $desc  content to add to description
     * @return $update Message: Successful and $cmid of the new Module.
     */
    public static function local_sync_service_update_course_module_lesson($cmid, $desc) {
        global $DB, $CFG;
        //debug("local_sync_service_update_course_module_lesson");
        
        require_once($CFG->dirroot . '/mod/' . '/lesson' . '/lib.php');
        require_once($CFG->dirroot . '/mod/' . '/lesson' . '/locallib.php');
              

        // Parameter validation.
        $params = self::validate_parameters(
            self::local_sync_service_update_course_module_lesson_parameters(),
            array(
                'cmid' => $cmid,
                'desc' => $desc
            )
        );

       
        $cm = get_coursemodule_from_id('lesson', $cmid, 0, false, MUST_EXIST);
        $instance = $DB->get_record('lesson', array('id' => $cm->instance), '*', MUST_EXIST);
        $context = context_module::instance($cmid);
        self::validate_context($context);
        require_capability('mod/lesson:addinstance', $context);

        $instance->intro=html_entity_decode($desc);
        $instance->introformat = \FORMAT_MARKDOWN;        
        $instance->coursemodule = $cmid;
        $instance->instance = $cm->instance;
        $instance->modulename ='lesson';
        $instance->type = 'mod';
        $instance->visible = true;        
       
        $instance->id = lesson_update_instance($instance, null);
 
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
    public static function local_sync_service_update_course_module_lesson_returns() {
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

     public static function local_sync_service_update_course_module_lesson_contentpage_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value( PARAM_INT, 'id of module' ),
                'pageid' => new external_value( PARAM_INT, 'pageid of lesson content' ),
                'title' => new external_value( PARAM_TEXT, 'title of lesson content page',VALUE_OPTIONAL ),
                'content' => new external_value( PARAM_TEXT, 'HTML or Markdown code'  ),
               
            )
        );
    }

    /**
     * Method to update a lesson module and add a content page to it.
     *
     * @param $cmid The cours $title,e module id.
     * @param $desc  content to add to description
     * @return $update Message: Successful and $cmid of the new Module.
     */
    public static function local_sync_service_update_course_module_lesson_contentpage($cmid, $pageid, $title, $content) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/' . '/lesson' . '/lib.php');
        require_once($CFG->dirroot . '/mod/' . '/lesson' . '/locallib.php');
        $warnings = array();

        //debug("local_sync_service_update_course_module_lesson_contentpage\n");

          // Parameter validation.
         $params = self::validate_parameters(
            self::local_sync_service_update_course_module_lesson_contentpage_parameters(),
            array(
                'cmid' => $cmid,
                'pageid' => $pageid,
                'title' => $title,
                'content' => $content                
            )
        );
        $cm = get_coursemodule_from_id('lesson', $cmid, 0, false, MUST_EXIST);
        $instance = $DB->get_record('lesson', array('id' => $cm->instance), '*', MUST_EXIST);
        $context = context_module::instance($cmid);
        self::validate_context($context);
        require_capability('mod/lesson:addinstance', $context);
        
        $lesson = new Lesson($instance);
        $page = $lesson->load_page($pageid);
        $prop = $page->properties();
        $prop->contents=html_entity_decode($content);        
        if (!empty($title)) {            
            $prop->title=$title;
        }
        $DB->update_record("lesson_pages", $prop);

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
    public static function local_sync_service_update_course_module_lesson_contentpage_returns() {
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
    public static function local_sync_service_assignment_save_attachment_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value( PARAM_TEXT, 'id of module' ),
                'itemid' => new external_value( PARAM_TEXT, 'id of draft area where file uploaded' ),
                'filename' => new external_value( PARAM_TEXT, 'filename' )
            )
        );
    }

    /**
     * Method to update a new course module containing a file.
     *
     * @param $courseid The course id.
     * @param $itemid File to publish.
     * @return $update Message: Successful and $cmid of the new Module.
     */
    public static function local_sync_service_assignment_save_attachment($cmid, $itemid, $filename) {
        global $DB, $CFG,$USER;

        require_once($CFG->dirroot . '/mod/' . '/assign' . '/lib.php');
        require_once($CFG->dirroot . '/mod/' . '/assign' . '/locallib.php');

        //debug("local_sync_service_assignment_save_attachment\n");

        // Parameter validation.
        $params = self::validate_parameters(
            self::local_sync_service_assignment_save_attachment_parameters(),
            array(
                'cmid' => $cmid,
                'itemid' => $itemid,
                'filename' => $filename
            )
        );

        $cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
        $instance = $DB->get_record('assign', array('id' => $cm->instance), '*', MUST_EXIST);

        // Ensure the current user has required permission in this course.
        $context = context_module::instance($cmid);
        self::validate_context($context);

        // Required permissions.
        require_capability('mod/assign:addinstance', $context);
        require_capability('moodle/course:managefiles', $context);

        $fs = get_file_storage();
        $usercontext = \context_user::instance($USER->id);

        $files = $fs->get_area_files($context->id, 'mod_assign', 'intro'/*ASSIGN_INTROATTACHMENT_FILEAREA*/, 0);
        foreach ($files as $file) {
            if  ($file->get_filename() == $filename /*and $file->get_itemid() == $itemid*/) {
                $file->delete();
            }
        }

        $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $itemid);
        
        foreach ($files as $file) {

            $fileinfo = [
                'contextid' =>  $context->id,   // ID of the context.
                'component' => 'mod_assign', // Your component name.
                'filearea'  => 'intro', //ASSIGN_INTROATTACHMENT_FILEAREA,       // Usually = table name.
                'itemid'    =>  0,              // Usually = ID of row in table.
                'filepath'  =>  '/',            // Any path beginning and ending in /.
                'filename'  =>  $file->get_filename(),   // Any filename.
            ];

            if  ($file->get_filename() == $filename /*and $file->get_itemid() == $itemid*/ ) {
                $fs->create_file_from_storedfile($fileinfo, $file);

                $url = moodle_url::make_draftfile_url(
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $file->get_filename(),
                    false
                );

                break;
            }

        }

        $instance->coursemodule = $cmid;
        $instance->instance = $cm->instance;
        $instance->id = $cm->instance;
        $instance->id = assign_update_instance($instance, null);

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
    public static function local_sync_service_assignment_save_attachment_returns() {
        return new external_single_structure(
            array(
                'message' => new external_value( PARAM_TEXT, 'if the execution was successful' ),
                'id' => new external_value( PARAM_TEXT, 'cmid of the new module' ),
                //'url' => new external_value( PARAM_TEXT, 'url of the uploaded itemid' ),
            )
        );
    }


    /**
     * Defines the necessary method parameters.
     * @return external_function_parameters
     */
    public static function local_sync_service_label_save_attachment_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value( PARAM_TEXT, 'id of module' ),
                'itemid' => new external_value( PARAM_TEXT, 'id of draft area where file uploaded' ),
                'filename' => new external_value( PARAM_TEXT, 'filename' )
            )
        );
    }

    /**
     *
     * @param $cmdid The  id of the label module
     * @param $itemid File to publish.
     * @return $update Message: Successful and $cmid of the new Module.
     */
    public static function local_sync_service_label_save_attachment($cmid, $itemid, $filename) {
        global $DB, $CFG,$USER;

        require_once($CFG->dirroot . '/mod/' . '/label' . '/lib.php');

        // Parameter validation.
        $params = self::validate_parameters(
            self::local_sync_service_label_save_attachment_parameters(),
            array(
                'cmid' => $cmid,
                'itemid' => $itemid,
                'filename' => $filename
            )
        );

        //debug(" cmid=$cmid , itemid=$itemid, filename=$filename\n");

        $cm = get_coursemodule_from_id('label', $cmid, 0, false, MUST_EXIST);
        $instance = $DB->get_record('label', array('id' => $cm->instance), '*', MUST_EXIST);
        $context = context_module::instance($cmid);
        self::validate_context($context);

        // Required permissions.
        require_capability('mod/label:addinstance', $context);
        require_capability('moodle/course:managefiles', $context);

        $fs = get_file_storage();
        $usercontext = \context_user::instance($USER->id);
        $files = $fs->get_area_files($context->id, 'mod_label', 'intro', 0);
        foreach ($files as $file) {
            if  ($file->get_filename() == $filename) {
                $file->delete();
            }

        }

        $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $itemid);
        foreach ($files as $file) {
            $fileinfo = [
                'contextid' =>  $context->id,   // ID of the context.
                'component' => 'mod_label', // Your component name.
                'filearea'  => 'intro',
                'itemid'    =>  0,              // Usually = ID of row in table.
                'filepath'  =>  '/',            // Any path beginning and ending in /.
                'filename'  =>  $file->get_filename(),   // Any filename.
            ];

            if  ($file->get_filename() == $filename ) {
                // debug("create store file for $filename ($itemid)\n");
                $fs->create_file_from_storedfile($fileinfo, $file);

                $url = moodle_url::make_draftfile_url(
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $file->get_filename(),
                    false
                );
                // debug("Draft URL: $url\n");
                break;
            }

        }

        $instance->coursemodule = $cmid;
        $instance->instance = $cm->instance;
        $instance->id = $cm->instance;
        $instance->id = label_update_instance($instance);


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
    public static function local_sync_service_label_save_attachment_returns() {
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
    public static function local_sync_service_page_save_attachment_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value( PARAM_TEXT, 'id of module' ),
                'itemid' => new external_value( PARAM_TEXT, 'id of draft area where file uploaded' ),
                'filename' => new external_value( PARAM_TEXT, 'filename' )
            )
        );
    }

    /**
     *
     * @param $cmdid The  id of the label module
     * @param $itemid File to publish.
     * @return $update Message: Successful and $cmid of the new Module.
     */
    public static function local_sync_service_page_save_attachment($cmid, $itemid, $filename) {
        global $DB, $CFG,$USER;

        require_once($CFG->dirroot . '/mod/' . '/page' . '/lib.php');

        // Parameter validation.
        $params = self::validate_parameters(
            self::local_sync_service_page_save_attachment_parameters(),
            array(
                'cmid' => $cmid,
                'itemid' => $itemid,
                'filename' => $filename
            )
        );

        $cm = get_coursemodule_from_id('page', $cmid, 0, false, MUST_EXIST);
        $instance = $DB->get_record('page', array('id' => $cm->instance), '*', MUST_EXIST);
        $context = context_module::instance($cmid);
        self::validate_context($context);

        // Required permissions.
        require_capability('mod/page:addinstance', $context);
        require_capability('moodle/course:managefiles', $context);

        $fs = get_file_storage();
        $usercontext = \context_user::instance($USER->id);
        $files = $fs->get_area_files($context->id, 'mod_page', 'content', 0);
        foreach ($files as $file) {
            if  ($file->get_filename() == $filename) {
                $file->delete();
            }
        }

        $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $itemid);
        foreach ($files as $file) {

            $fileinfo = [
                'contextid' =>  $context->id,   // ID of the context.
                'component' => 'mod_page', // Your component name.
                'filearea'  => 'content',
                'itemid'    =>  0,              // Usually = ID of row in table.
                'filepath'  =>  '/',            // Any path beginning and ending in /.
                'filename'  =>  $file->get_filename(),   // Any filename.
            ];

            if  ($file->get_filename() == $filename ) {
                $fs->create_file_from_storedfile($fileinfo, $file);
                break;
            }
        }

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
    public static function local_sync_service_page_save_attachment_returns() {
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
    public static function local_sync_service_lesson_save_attachment_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value( PARAM_TEXT, 'id of module' ),
                'itemid' => new external_value( PARAM_TEXT, 'id of draft area where file uploaded' ),
                'filename' => new external_value( PARAM_TEXT, 'filename' )
            )
        );
    }


    /**
     *
     * @param $cmdid The  id of the label module
     * @param $itemid File to publish.
     * @return $update Message: Successful and $cmid of the new Module.
     */
    public static function local_sync_service_lesson_save_attachment($cmid, $itemid, $filename) {
        global $DB, $CFG,$USER;

        require_once($CFG->dirroot . '/mod/' . '/lesson' . '/lib.php');

        // Parameter validation.
        $params = self::validate_parameters(
            self::local_sync_service_lesson_save_attachment_parameters(),
            array(
                'cmid' => $cmid,
                'itemid' => $itemid,
                'filename' => $filename
            )
        );

        $cm = get_coursemodule_from_id('lesson', $cmid, 0, false, MUST_EXIST);
        $instance = $DB->get_record('lesson', array('id' => $cm->instance), '*', MUST_EXIST);
        $context = context_module::instance($cmid);
        self::validate_context($context);

        // Required permissions.
        require_capability('mod/lesson:addinstance', $context);
        require_capability('moodle/course:managefiles', $context);

        $fs = get_file_storage();
        $usercontext = \context_user::instance($USER->id);
        $files = $fs->get_area_files($context->id, 'mod_lesson', 'intro', 0);
        foreach ($files as $file) {
            if  ($file->get_filename() == $filename) {
                $file->delete();
            }

        }

        $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $itemid);
        foreach ($files as $file) {
            $fileinfo = [
                'contextid' =>  $context->id,   // ID of the context.
                'component' => 'mod_lesson', // Your component name.
                'filearea'  => 'intro',
                'itemid'    =>  0,              // Usually = ID of row in table.
                'filepath'  =>  '/',            // Any path beginning and ending in /.
                'filename'  =>  $file->get_filename(),   // Any filename.
            ];

            if  ($file->get_filename() == $filename ) {
                $fs->create_file_from_storedfile($fileinfo, $file);

                $url = moodle_url::make_draftfile_url(
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $file->get_filename(),
                    false
                );
                break;
            }

        }

        $instance->coursemodule = $cmid;
        $instance->instance = $cm->instance;
        $instance->id = $cm->instance;        
        $instance->id = lesson_update_instance($instance,null);
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
    public static function local_sync_service_lesson_save_attachment_returns() {
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
    public static function local_sync_service_lessonpage_save_attachment_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value( PARAM_TEXT, 'id of module' ),
                'itemid' => new external_value( PARAM_TEXT, 'id of draft area where file uploaded' ),
                'pageid' => new external_value( PARAM_TEXT, 'pageid of lesson content page' ),
                'filename' => new external_value( PARAM_TEXT, 'filename' )
            )
        );
    }


    /**
     *
     * @param $cmdid The  id of the lesson module
     * @param $itemid File to publish.
     * @return $update Message: Successful and $cmid of the new Module.
     */
    public static function local_sync_service_lessonpage_save_attachment($cmid, $itemid, $pageid, $filename) {
        global $DB, $CFG,$USER;

        require_once($CFG->dirroot . '/mod/' . '/lesson' . '/lib.php');

        // Parameter validation.
        $params = self::validate_parameters(
            self::local_sync_service_lessonpage_save_attachment_parameters(),
            array(
                'cmid' => $cmid,
                'itemid' => $itemid,
                'pageid' => $pageid,
                'filename' => $filename
            )
        );

        $cm = get_coursemodule_from_id('lesson', $cmid, 0, false, MUST_EXIST);
        $instance = $DB->get_record('lesson', array('id' => $cm->instance), '*', MUST_EXIST);
        $context = context_module::instance($cmid);
        self::validate_context($context);

        // Required permissions.
        require_capability('mod/lesson:addinstance', $context);
        require_capability('moodle/course:managefiles', $context);

        $fs = get_file_storage();
        $usercontext = \context_user::instance($USER->id);
        $files = $fs->get_area_files($context->id, 'mod_lesson', 'page_contents', $pageid);
        foreach ($files as $file) {
            if  ($file->get_filename() == $filename) {
                $file->delete();
            }
        }

        $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $itemid);
        foreach ($files as $file) {
        
            $fileinfo = [
                'contextid' =>  $context->id,   // ID of the context.
                'component' => 'mod_lesson', // Your component name.
                'filearea'  => 'page_contents',
                'itemid'    =>  $pageid,              // ID of row in table mdl_lesson_page
                'filepath'  =>  '/',            // Any path beginning and ending in /.
                'filename'  =>  $file->get_filename(),   // Any filename.
            ];

            if  ($file->get_filename() == $filename ) {
                $fs->create_file_from_storedfile($fileinfo, $file);

                $url = moodle_url::make_draftfile_url(
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $file->get_filename(),
                    false
                );
                break;
            }

        }

        $instance->coursemodule = $cmid;
        $instance->instance = $cm->instance;
        $instance->id = $cm->instance;        
        $instance->id = lesson_update_instance($instance,null);

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
    public static function local_sync_service_lessonpage_save_attachment_returns() {
        return new external_single_structure(
            array(
                'message' => new external_value( PARAM_TEXT, 'if the execution was successful' ),
                'id' => new external_value( PARAM_TEXT, 'cmid of the new module' ),
            )
        );
    }
}

