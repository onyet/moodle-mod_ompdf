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
 * The main ompdf configuration form.
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_ompdf
 * @copyright  2013 Dian Mukti Wibowo <onyetcorp@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * OMPDF activity configuration form.
 */
class mod_ompdf_mod_form extends moodleform_mod {
    /**
     * Defines the ompdf instance configuration form.
     *
     * @return void
     */
    public function definition() {
        global $CFG;

        $config = get_config('ompdf');
        $mform =& $this->_form;

        // Name and description fields.
        $mform->addElement(
            'header',
            'general',
            get_string('general', 'form')
        );
        $mform->addElement(
            'text',
            'name',
            get_string('name'),
            ['size' => '48']
        );
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule(
            'name',
            get_string('maximumchars', '', 255),
            'maxlength',
            255,
            'client'
        );
        $this->add_intro_editor(false);

        // Option for showing folder inline or on separate page.
        $mform->addElement(
            'select',
            'display',
            get_string('display', 'ompdf'),
            [OMPDF_MANAGER_DISPLAY_PAGE => get_string('displaypage', 'ompdf'),
            OMPDF_MANAGER_DISPLAY_INLINE => get_string(
                'displayinline',
                'ompdf'
            )]
        );
        $mform->addHelpButton('display', 'display', 'ompdf');

        // Option for showing sub-folders expanded or collapsed.
        $mform->addElement(
            'advcheckbox',
            'showexpanded',
            get_string('showexpanded', 'ompdf')
        );
        $mform->addHelpButton('showexpanded', 'showexpanded', 'ompdf');
        $mform->setDefault('showexpanded', $config->showexpanded);

        // Option for opening PDFs in new tabs or windows.
        $mform->addElement(
            'advcheckbox',
            'openinnewtab',
            get_string('openinnewtab', 'ompdf')
        );
        $mform->addHelpButton('openinnewtab', 'openinnewtab', 'ompdf');
        $mform->setDefault('openinnewtab', $config->openinnewtab);

        // Option for Read-Only DRM Protection.
        $mform->addElement(
            'advcheckbox',
            'readonly_protection',
            get_string('readonly_protection', 'ompdf')
        );
        $mform->addHelpButton('readonly_protection', 'readonly_protection', 'ompdf');
        $mform->setDefault('readonly_protection', 0);

        // Folder fields.
        $mform->addElement(
            'header',
            'pdf_fieldset',
            get_string('pdf_fieldset', 'ompdf')
        );

        // Folder file manager.
        $options = ['subdirs' => true,
                         'maxbytes' => 0,
                         'maxfiles' => -1,
                         'accepted_types' => ['.pdf', '.zip', 'web_image']];
        $mform->addElement(
            'filemanager',
            'pdfs',
            get_string('pdfs', 'ompdf'),
            null,
            $options
        );
        $mform->addHelpButton('pdfs', 'pdfs', 'ompdf');
        $mform->addRule('pdfs', null, 'required', null, 'client');

        // Standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    /**
     * Prepares the form before data are set.
     *
     * @param array $defaultvalues Form values to be set.
     * @return void
     */
    public function data_preprocessing(&$defaultvalues) {
        if ($this->current->instance) {
            $options = ['subdirs' => true,
                             'maxbytes' => 0,
                             'maxfiles' => -1];
            $draftitemid = file_get_submitted_draft_itemid('pdfs');
            file_prepare_draft_area(
                $draftitemid,
                $this->context->id,
                'mod_ompdf',
                'pdfs',
                0,
                $options
            );
            $defaultvalues['pdfs'] = $draftitemid;
        }
    }

    /**
     * Validates the form input.
     *
     * @param array $data submitted data
     * @param array $files submitted files
     * @return array eventual errors indexed by the field name
     */
    public function validation($data, $files) {
        $errors = [];

        // On-view completion can not work together with display
        // inline option.
        if (
            empty($errors['completion']) &&
                array_key_exists('completion', $data) &&
                $data['completion'] == COMPLETION_TRACKING_AUTOMATIC &&
                !empty($data['completionview']) &&
                $data['display'] == OMPDF_MANAGER_DISPLAY_INLINE
        ) {
            $errors['completion'] = get_string('noautocompletioninline', 'ompdf');
        }

        return $errors;
    }
}
