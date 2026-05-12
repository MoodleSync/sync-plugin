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
     */
    public static function local_sync_service_add_new_section($courseid, $sectionname, $sectionnum) {
        global $DB, $CFG;
        $params = self::validate_parameters(
        self::local_sync_service_add_new_section_parameters(),
            array(
                'courseid' => $courseid,
                'sectionname' => $sectionname,
                'sectionnum' => $sectionnum,
            )
        );

        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('block/section_links:addinstance', $context);

        $cw = course_create_section($params['courseid'], $params['sectionnum'], false);
        $section = $DB->get_record('course_sections', array('id' => $cw->id), '*', MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $section->course), '*', MUST_EXIST);
        $data['name'] = $params['sectionname'];
        course_update_section($course, $section, $data);

        return ['message' => 'Successful'];
    }

    public static function local_sync_service_add_new_section_returns() {
        return new external_single_structure(
            array('message' => new external_value( PARAM_TEXT, 'if the execution was successful' ))
        );
    }


    public static function local_sync_service_add_new_course_module_url_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value( PARAM_TEXT, 'id of course' ),
                'sectionnum' => new external_value( PARAM_TEXT, 'relative number of the section' ),
                'urlname' => new external_value( PARAM_TEXT, 'displayed mod name' ),
                'url' => new external_value( PARAM_TEXT, 'url to insert' ),
                'visible' => new external_value( PARAM_TEXT, 'defines the mod. visibility' ),
                'time' => new external_value( PARAM_TEXT, 'defines the mod. visibility', VALUE_DEFAULT, null ),
                'beforemod' => new external_value( PARAM_TEXT, 'mod to set before', VALUE_DEFAULT, null ),
            )
        );
    }

    public static function local_sync_service_add_new_course_module_url($courseid, $sectionnum, $urlname, $url, $visible, $time = null, $beforemod = null) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/url/lib.php');
        $params = self::validate_parameters(
            self::local_sync_service_add_new_course_module_url_parameters(),
            array(
                'courseid' => $courseid,
                'sectionnum' => $sectionnum,
                'urlname' => $urlname,
                'url' => $url,
                'visible' => $visible,
                'time' => $time,
                'beforemod' => $beforemod,
            )
        );
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('mod/url:addinstance', $context);

        $instance = new \stdClass();
        $instance->course = $params['courseid'];
        $instance->name = $params['urlname'];
        $instance->intro = null;
        $instance->introformat = \FORMAT_HTML;
        $instance->externalurl = $params['url'];
        $instance->id = url_add_instance($instance, null);

        $cm = new \stdClass();
        $cm->course     = $params['courseid'];
        $cm->module     = $DB->get_field( 'modules', 'id', array('name' => 'url') );
        $cm->instance   = $instance->id;
        $cm->section    = $params['sectionnum'];
        if (!is_null($params['time'])) {
            $cm->availability = "{\"op\":\"&\",\"c\":[{\"type\":\"date\",\"d\":\">=\",\"t\":" . $params['time'] . "}],\"showc\":[" . $params['visible'] . "]}";
        } else if ( $params['visible'] === 'false' ) {
            $cm->visible = 0;
        }
        $cm->id = add_course_module( $cm );
        course_add_cm_to_section($params['courseid'], $cm->id, $params['sectionnum'], $params['beforemod']);

        return ['message' => 'Successful', 'id' => (string)$cm->id];
    }

    public static function local_sync_service_add_new_course_module_url_returns() {
        return new external_single_structure(
            array(
                'message' => new external_value( PARAM_TEXT, 'if the execution was successful' ),
                'id' => new external_value( PARAM_TEXT, 'cmid of the new module' ),
            )
        );
    }

    public static function local_sync_service_add_new_course_module_resource_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value( PARAM_TEXT, 'id of course' ),
                'sectionnum' => new external_value( PARAM_TEXT, 'relative number of the section' ),
                'itemid' => new external_value( PARAM_TEXT, 'id of the upload' ),
                'displayname' => new external_value( PARAM_TEXT, 'displayed mod name' ),
                'visible' => new external_value( PARAM_TEXT, 'defines the mod. visibility' ),
                'time' => new external_value( PARAM_TEXT, 'defines the mod. visibility', VALUE_DEFAULT, null ),
                'beforemod' => new external_value( PARAM_TEXT, 'mod to set before', VALUE_DEFAULT, null ),
            )
        );
    }

    public static function local_sync_service_add_new_course_module_resource($courseid, $sectionnum, $itemid, $displayname, $visible, $time = null, $beforemod = null) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/resource/lib.php');
        $params = self::validate_parameters(
            self::local_sync_service_add_new_course_module_resource_parameters(),
            array(
                'courseid' => $courseid,
                'sectionnum' => $sectionnum,
                'itemid' => $itemid,
                'displayname' => $displayname,
                'visible' => $visible,
                'time' => $time,
                'beforemod' => $beforemod,
            )
        );
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('mod/resource:addinstance', $context);

        $cm = new \stdClass();
        $cm->course     = $params['courseid'];
        $cm->module     = $DB->get_field('modules', 'id', array( 'name' => 'resource' ));
        $cm->section    = $params['sectionnum'];
        if (!is_null($params['time'])) {
            $cm->availability = "{\"op\":\"&\",\"c\":[{\"type\":\"date\",\"d\":\">=\",\"t\":" . $params['time'] . "}],\"showc\":[" . $params['visible'] . "]}";
        } else if ( $params['visible'] === 'false' ) {
            $cm->visible = 0;
        }
        $cm->id = add_course_module($cm);
        $instance = new \stdClass();
        $instance->course = $params['courseid'];
        $instance->name = $params['displayname'];
        $instance->intro = null;
        $instance->introformat = \FORMAT_HTML;
        $instance->coursemodule = $cm->id;
        $instance->files = $params['itemid'];
        $instance->id = resource_add_instance($instance, null);
        course_add_cm_to_section($params['courseid'], $cm->id, $params['sectionnum'], $params['beforemod']);

        return ['message' => 'Successful', 'id' => (string)$cm->id];
    }

    public static function local_sync_service_add_new_course_module_resource_returns() {
        return new external_single_structure(
            array(
                'message' => new external_value( PARAM_TEXT, 'if the execution was successful' ),
                'id' => new external_value( PARAM_TEXT, 'cmid of the new module' ),
            )
        );
    }

    public static function local_sync_service_move_module_to_specific_position_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value( PARAM_TEXT, 'id of module' ),
                'sectionid' => new external_value( PARAM_TEXT, 'relative number of the section' ),
                'beforemod' => new external_value( PARAM_TEXT, 'mod to set before', VALUE_DEFAULT, null ),
            )
        );
    }

    public static function local_sync_service_move_module_to_specific_position($cmid, $sectionid, $beforemod = null) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/course/lib.php');
        $params = self::validate_parameters(
            self::local_sync_service_move_module_to_specific_position_parameters(),
            array('cmid' => $cmid, 'sectionid' => $sectionid, 'beforemod' => $beforemod)
        );
        $modcontext = context_module::instance( $params['cmid'] );
        self::validate_context( $modcontext );
        $cm = get_coursemodule_from_id('', $params['cmid']);
        $context = context_course::instance($cm->course);
        self::validate_context($context);
        require_capability('moodle/course:movesections', $context);

        $section = $DB->get_record('course_sections', array( 'id' => $params['sectionid'], 'course' => $cm->course ));
        moveto_module($cm, $section, $params['beforemod']);

        return ['message' => 'Successful'];
    }

    public static function local_sync_service_move_module_to_specific_position_returns() {
        return new external_single_structure(
            array('message' => new external_value( PARAM_TEXT, 'if the execution was successful' ))
        );
    }

    public static function local_sync_service_add_new_course_module_directory_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value( PARAM_TEXT, 'id of course' ),
                'sectionnum' => new external_value( PARAM_TEXT, 'relative number of the section' ),
                'itemid' => new external_value( PARAM_TEXT, 'id of the upload' ),
                'displayname' => new external_value( PARAM_TEXT, 'displayed mod name' ),
                'visible' => new external_value( PARAM_TEXT, 'defines the mod. visibility' ),
                'time' => new external_value( PARAM_TEXT, 'defines the mod. visibility', VALUE_DEFAULT, null ),
                'beforemod' => new external_value( PARAM_TEXT, 'mod to set before', VALUE_DEFAULT, null ),
            )
        );
    }

    public static function local_sync_service_add_new_course_module_directory($courseid, $sectionnum, $itemid, $displayname, $visible, $time = null, $beforemod = null) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/folder/lib.php');
        $params = self::validate_parameters(
            self::local_sync_service_add_new_course_module_directory_parameters(),
            array(
                'courseid' => $courseid, 'sectionnum' => $sectionnum, 'itemid' => $itemid,
                'displayname' => $displayname, 'visible' => $visible, 'time' => $time, 'beforemod' => $beforemod,
            )
        );
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('mod/folder:addinstance', $context);

        $cm = new \stdClass();
        $cm->course     = $params['courseid'];
        $cm->module     = $DB->get_field('modules', 'id', array( 'name' => 'folder' ));
        $cm->section    = $params['sectionnum'];
        if (!is_null($params['time'])) {
            $cm->availability = "{\"op\":\"&\",\"c\":[{\"type\":\"date\",\"d\":\">=\",\"t\":" . $params['time'] . "}],\"showc\":[" . $params['visible'] . "]}";
        } else if ( $params['visible'] === 'false' ) {
            $cm->visible = 0;
        }
        $cm->id = add_course_module($cm);
        $instance = new \stdClass();
        $instance->course = $params['courseid'];
        $instance->name = $params['displayname'];
        $instance->coursemodule = $cm->id;
        $instance->introformat = FORMAT_HTML;
        $instance->intro = '<p>'.$params['displayname'].'</p>';
        $instance->files = $params['itemid'];
        $instance->id = folder_add_instance($instance, null);
        course_add_cm_to_section($params['courseid'], $cm->id, $params['sectionnum'], $params['beforemod']);

        return ['message' => 'Successful', 'id' => (string)$cm->id];
    }

    public static function local_sync_service_add_new_course_module_directory_returns() {
        return new external_single_structure(
            array(
                'message' => new external_value( PARAM_TEXT, 'if the execution was successful' ),
                'id' => new external_value( PARAM_TEXT, 'cmid of the new module' ),
            )
        );
    }

    public static function local_sync_service_add_files_to_directory_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value( PARAM_TEXT, 'id of course' ),
                'itemid' => new external_value( PARAM_TEXT, 'id of the upload' ),
                'contextid' => new external_value( PARAM_TEXT, 'contextid of folder' ),
            )
        );
    }

    public static function local_sync_service_add_files_to_directory($courseid, $itemid, $contextid) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/folder/lib.php');
        $params = self::validate_parameters(
            self::local_sync_service_add_files_to_directory_parameters(),
            array('courseid' => $courseid, 'itemid' => $itemid, 'contextid' => $contextid)
        );
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('mod/folder:managefiles', $context);
        file_merge_files_from_draft_area_into_filearea($params['itemid'], $params['contextid'], 'mod_folder', 'content', 0);

        return ['message' => 'Successful'];
    }

    public static function local_sync_service_add_files_to_directory_returns() {
        return new external_single_structure(
            array('message' => new external_value( PARAM_TEXT, 'if the execution was successful' ))
        );
    }

    public static function local_sync_service_add_new_course_module_page_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value( PARAM_TEXT, 'id of course' ),
                'sectionnum' => new external_value( PARAM_TEXT, 'relative number of the section' ),
                'displayname' => new external_value( PARAM_TEXT, 'displayed mod name' ),
                'content' => new external_value( PARAM_RAW, 'page content' ),
                'visible' => new external_value( PARAM_TEXT, 'defines the mod. visibility' ),
                'beforemod' => new external_value( PARAM_TEXT, 'mod to set before', VALUE_DEFAULT, null ),
            )
        );
    }

    public static function local_sync_service_add_new_course_module_page($courseid, $sectionnum, $displayname, $content, $visible, $beforemod = null) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/page/lib.php');
        $params = self::validate_parameters(
            self::local_sync_service_add_new_course_module_page_parameters(),
            array('courseid' => $courseid, 'sectionnum' => $sectionnum, 'displayname' => $displayname, 'content' => $content, 'visible' => $visible, 'beforemod' => $beforemod)
        );
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('mod/page:addinstance', $context);

        // Step 1: Create placeholder Course Module
        $cm = new \stdClass();
        $cm->course     = $params['courseid'];
        $cm->module     = $DB->get_field('modules', 'id', array('name' => 'page'));
        $cm->instance   = 0;
        $cm->section    = $params['sectionnum'];
        $cm->visible    = ($params['visible'] === 'true' || $params['visible'] === '1') ? 1 : 0;
        $cm->id = add_course_module($cm);

        // Step 2: Create Page Instance with cmid
        $instance = new \stdClass();
        $instance->course = $params['courseid'];
        $instance->coursemodule = $cm->id;
        $instance->name = $params['displayname'];
        $instance->intro = '';
        $instance->introformat = FORMAT_HTML;
        $instance->content = $params['content'];
        $instance->contentformat = FORMAT_HTML;
        $instance->legacyfiles = 0;
        $instance->display = 0; // Default
        $instance->printintro = 0;
        $instance->printlastmodified = 0;
        
        $instance->id = page_add_instance($instance, null);

        // Step 3: Add CM to Section
        course_add_cm_to_section($params['courseid'], $cm->id, $params['sectionnum'], $params['beforemod']);

        return array('message' => 'Successful', 'id' => (string)$cm->id);
    }

    public static function local_sync_service_add_new_course_module_page_returns() {
        return new external_single_structure(
            array(
                'message' => new external_value( PARAM_TEXT, 'if the execution was successful' ),
                'id' => new external_value( PARAM_TEXT, 'cmid of the new module' ),
            )
        );
    }

    public static function local_sync_service_add_new_course_module_label_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value( PARAM_TEXT, 'id of course' ),
                'sectionnum' => new external_value( PARAM_TEXT, 'relative number of the section' ),
                'intro' => new external_value( PARAM_RAW, 'label content' ),
                'visible' => new external_value( PARAM_TEXT, 'defines the mod. visibility' ),
                'beforemod' => new external_value( PARAM_TEXT, 'mod to set before', VALUE_DEFAULT, null ),
            )
        );
    }

    public static function local_sync_service_add_new_course_module_label($courseid, $sectionnum, $intro, $visible, $beforemod = null) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/label/lib.php');
        $params = self::validate_parameters(
            self::local_sync_service_add_new_course_module_label_parameters(),
            array('courseid' => $courseid, 'sectionnum' => $sectionnum, 'intro' => $intro, 'visible' => $visible, 'beforemod' => $beforemod)
        );
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('mod/label:addinstance', $context);

        $cm = new \stdClass();
        $cm->course     = $params['courseid'];
        $cm->module     = $DB->get_field('modules', 'id', array('name' => 'label'));
        $cm->instance   = 0;
        $cm->section    = $params['sectionnum'];
        $cm->visible    = ($params['visible'] === 'true' || $params['visible'] === '1') ? 1 : 0;
        $cm->id = add_course_module($cm);

        $instance = new \stdClass();
        $instance->course = $params['courseid'];
        $instance->coursemodule = $cm->id;
        $instance->name = shorten_text(strip_tags($params['intro']), 50);
        $instance->intro = $params['intro'];
        $instance->introformat = FORMAT_HTML;
        $instance->id = label_add_instance($instance, null);

        // Update CM instance
        $DB->set_field('course_modules', 'instance', $instance->id, array('id' => $cm->id));

        course_add_cm_to_section($params['courseid'], $cm->id, $params['sectionnum'], $params['beforemod']);

        return array('message' => 'Successful', 'id' => (string)$cm->id);
    }

    public static function local_sync_service_add_new_course_module_label_returns() {
        return new external_single_structure(
            array(
                'message' => new external_value( PARAM_TEXT, 'if the execution was successful' ),
                'id' => new external_value( PARAM_TEXT, 'cmid of the new module' ),
            )
        );
    }
}
