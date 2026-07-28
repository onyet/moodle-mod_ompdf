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
 * This class provides functionality for the ompdf module.
 *
 * @package   mod_ompdf
 * @copyright 2013 Dian Mukti Wibowo <onyetcorp@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Standard base class for mod_ompdf.
 */
class ompdf {
    /** @var stdClass The ompdf record that contains the
     *                global settings for this ompdf instance.
     */
    private $instance;

    /** @var context The context of the course module for this ompdf
     *               instance (or just the course if we are creating a new one).
     */
    private $context;

    /** @var stdClass The course this ompdf instance belongs to */
    private $course;

    /** @var ompdf_renderer The custom renderer for this module */
    private $output;

    /** @var stdClass The course module for this ompdf instance */
    private $coursemodule;

    /** @var stdClass The plugin default configuration */
    private $defaultconfig;

    /** @var string modulename Prevents excessive calls to get_string */
    private static $modulename = null;

    /** @var string modulenameplural Prevents excessive calls to get_string */
    private static $modulenameplural = null;

    /**
     * Constructor for the base ompdf class.
     *
     * @param mixed $coursemodulecontext context|null The course module context
     *                                   (or the course context if the coursemodule
     *                                   has not been created yet).
     * @param mixed $coursemodule The current course module if it was already loaded,
     *                            otherwise this class will load one from the context
     *                            as required.
     * @param mixed $course The current course if it was already loaded,
     *                      otherwise this class will load one from the context as
     *                      required.
     */
    public function __construct($coursemodulecontext, $coursemodule, $course) {
        global $PAGE;

        $this->context = $coursemodulecontext;
        $this->coursemodule = $coursemodule;
        $this->course = $course;
    }

    /**
     * Set the course data.
     *
     * @param stdClass $course The course data
     */
    public function set_course(stdClass $course) {
        $this->course = $course;
    }

    /**
     * Add this instance to the database.
     *
     * @param stdClass $formdata The data submitted from the form
     * @return mixed False if an error occurs or the int id of the new instance
     */
    public function add_instance(stdClass $formdata) {
        global $DB;

        // Add the database record.
        $add = new stdClass();
        $add->name = $formdata->name;
        $add->timemodified = time();
        $add->timecreated = time();
        $add->course = $formdata->course;
        $add->courseid = $formdata->course;
        $add->intro = $formdata->intro;
        $add->introformat = $formdata->introformat;
        $add->display = $formdata->display;
        $add->showexpanded = $formdata->showexpanded;
        $add->openinnewtab = $formdata->openinnewtab;
        $add->readonly_protection = !empty($formdata->readonly_protection) ? 1 : 0;

        $returnid = $DB->insert_record('ompdf', $add);
        $this->instance = $DB->get_record('ompdf',
                                          array('id' => $returnid),
                                          '*',
                                          MUST_EXIST);
        $this->save_files($formdata);

        // Cache the course record.
        $this->course = $DB->get_record('course',
                                        array('id' => $formdata->course),
                                        '*',
                                        MUST_EXIST);

        return $returnid;
    }

    /**
     * Delete this instance from the database.
     *
     * @return bool False if an error occurs
     */
    public function delete_instance() {
        global $DB;
        $result = true;

        // Delete files associated with this ompdf.
        $fs = get_file_storage();
        if (! $fs->delete_area_files($this->context->id) ) {
            $result = false;
        }

        // Delete the instance.
        // Note: all context files are deleted automatically.
        $DB->delete_records('ompdf', array('id' => $this->get_instance()->id));

        return $result;
    }

    /**
     * Update this instance in the database.
     *
     * @param stdClass $formdata The data submitted from the form
     * @return bool False if an error occurs
     */
    public function update_instance($formdata) {
        global $DB;

        $update = new stdClass();
        $update->id = $formdata->instance;
        $update->name = $formdata->name;
        $update->timemodified = time();
        $update->course = $formdata->course;
        $update->intro = $formdata->intro;
        $update->introformat = $formdata->introformat;
        $update->display = $formdata->display;
        $update->showexpanded = $formdata->showexpanded;
        $update->openinnewtab = $formdata->openinnewtab;
        $update->readonly_protection = !empty($formdata->readonly_protection) ? 1 : 0;

        $result = $DB->update_record('ompdf', $update);
        $this->instance = $DB->get_record('ompdf',
                                          array('id' => $update->id),
                                          '*',
                                          MUST_EXIST);
        $this->save_files($formdata);

        return $result;
    }

    /**
     * Get the name of the current module.
     *
     * @return string The module name (pdfjs folder)
     */
    protected function get_module_name() {
        if (isset(self::$modulename)) {
            return self::$modulename;
        }
        self::$modulename = get_string('modulename', 'ompdf');
        return self::$modulename;
    }

    /**
     * Get the plural name of the current module.
     *
     * @return string The module name plural (pdfjs folders)
     */
    protected function get_module_name_plural() {
        if (isset(self::$modulenameplural)) {
            return self::$modulenameplural;
        }
        self::$modulenameplural = get_string('modulenameplural', 'ompdf');
        return self::$modulenameplural;
    }

    /**
     * Has this ompdf been constructed from an instance?
     *
     * @return bool
     */
    public function has_instance() {
        return $this->instance || $this->get_course_module();
    }

    /**
     * Get the settings for the current instance of this ompdf.
     *
     * @return stdClass The settings
     */
    public function get_instance() {
        global $DB;
        if ($this->instance) {
            return $this->instance;
        }
        if ($this->get_course_module()) {
            $params = array('id' => $this->get_course_module()->instance);
            $this->instance = $DB->get_record('ompdf',
                                              $params,
                                              '*',
                                              MUST_EXIST);
        }
        if (!$this->instance) {
            throw new coding_exception('Improper use of the ompdf class. ' .
                                       'Cannot load the ompdf record.');
        }
        return $this->instance;
    }

    /**
     * Get the context of the current course.
     *
     * @return mixed context|null The course context
     */
    public function get_course_context() {
        if (!$this->context && !$this->course) {
            throw new coding_exception('Improper use of the ompdf class. ' .
                                       'Cannot load the course context.');
        }
        if ($this->context) {
            return $this->context->get_course_context();
        } else {
            return context_course::instance($this->course->id);
        }
    }

    /**
     * Get the current course module.
     *
     * @return mixed stdClass|null The course module
     */
    public function get_course_module() {
        if ($this->coursemodule) {
            return $this->coursemodule;
        }
        if (!$this->context) {
            return null;
        }

        if ($this->context->contextlevel == CONTEXT_MODULE) {
            $this->coursemodule = get_coursemodule_from_id(
                'ompdf',
                $this->context->instanceid,
                0,
                false,
                MUST_EXIST);
            return $this->coursemodule;
        }
        return null;
    }

    /**
     * Get the default configuration for the plugin.
     *
     * @return mixed stdClass|boolean The default configuration
     */
    public function get_default_config() {
        if (!$this->defaultconfig) {
            $this->defaultconfig = get_config('ompdf');
        }
        return $this->defaultconfig;
    }

    /**
     * Get context module.
     *
     * @return context
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Get the current course.
     *
     * @return mixed stdClass|null The course
     */
    public function get_course() {
        global $DB;

        if ($this->course) {
            return $this->course;
        }

        if (!$this->context) {
            return null;
        }
        $params = array('id' => $this->get_course_context()->instanceid);
        $this->course = $DB->get_record('course', $params, '*', MUST_EXIST);

        return $this->course;
    }

    /**
     * Util function to add a message to the log.
     *
     * @param string $action The current action
     * @param string $info A detailed description of the change.
     *                     But no more than 255 characters.
     * @param string $url The url to the ompdf module instance.
     * @return void
     */
    public function add_to_log($action = '', $info = '', $url='') {
        global $USER;

        $fullurl = 'view.php?id=' . $this->get_course_module()->id;
        if ($url != '') {
            $fullurl .= '&' . $url;
        }
                   
        $event = \mod_ompdf\event\view_all::create(array(
            'objectid' => $this->get_course_module()->id,
            'context' => context_module::instance($USER->id)
        ));
        $event->trigger();
    }

    /**
     * Lazy load the page renderer and expose the renderer to plugins.
     *
     * @return ompdf_renderer
     */
    public function get_renderer() {
        global $PAGE;

        if ($this->output) {
            return $this->output;
        }
        $this->output = $PAGE->get_renderer('mod_ompdf');
        return $this->output;
    }

    /**
     * Save draft files.
     *
     * @param stdClass $formdata
     * @return void
     */
    protected function save_files($formdata) {
        global $DB;

        // Storage of files from the filemanager (pdfs).
        $options = array('subdirs' => true,
                         'maxbytes' => 0,
                         'maxfiles' => -1);
        $draftitemid = $formdata->pdfs;
        if ($draftitemid) {
            file_save_draft_area_files(
                $draftitemid,
                $this->context->id,
                'mod_ompdf',
                'pdfs',
                0,
                $options
            );
        }
    }
}
