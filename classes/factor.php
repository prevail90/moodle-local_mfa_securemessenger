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

namespace factor_securemessenger;

use moodle_url;
use stdClass;
use tool_mfa\local\factor\object_factor_base;
use tool_mfa\local\secret_manager;

/**
 * Secure messenger MFA factor implementation.
 *
 * @package     factor_securemessenger
 * @subpackage  tool_mfa
 * @copyright   2026 prevail90
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class factor extends object_factor_base {

    /** @var string Factor icon. */
    protected $icon = 'fa-comments-o';

    /**
     * Defines the login form.
     *
     * @param \MoodleQuickForm $mform Form to modify.
     * @return \MoodleQuickForm
     */
    public function login_form_definition(\MoodleQuickForm $mform): \MoodleQuickForm {
        $mform->addElement(new \tool_mfa\local\form\verification_field());
        $mform->setType('verificationcode', PARAM_ALPHANUM);
        return $mform;
    }

    /**
     * Sends the login code after form data is loaded.
     *
     * @param \MoodleQuickForm $mform Form to modify.
     * @return \MoodleQuickForm
     */
    public function login_form_definition_after_data(\MoodleQuickForm $mform): \MoodleQuickForm {
        $this->generate_and_send_code();
        $mform->disable_form_change_checker();
        return $mform;
    }

    /**
     * Validate the submitted login code.
     *
     * @param array $data Submitted data.
     * @return array
     */
    public function login_form_validation(array $data): array {
        $errors = [];

        if (!$this->check_verification_code($data['verificationcode'] ?? '')) {
            $errors['verificationcode'] = get_string('error:wrongverification', 'factor_securemessenger');
        }

        return $errors;
    }

    /**
     * Gets the string for setup button on preferences page.
     *
     * @return string
     */
    public function get_setup_string(): string {
        return get_string('setupfactorbutton', 'factor_securemessenger');
    }

    /**
     * Gets the string for manage button on preferences page.
     *
     * @return string
     */
    public function get_manage_string(): string {
        return get_string('managefactorbutton', 'factor_securemessenger');
    }

    /**
     * Defines the setup form.
     *
     * @param \MoodleQuickForm $mform Form to modify.
     * @return \MoodleQuickForm
     */
    public function setup_factor_form_definition(\MoodleQuickForm $mform): \MoodleQuickForm {
        global $CFG, $OUTPUT, $USER, $DB;

        if ($DB->record_exists('tool_mfa', ['factor' => $this->name, 'userid' => $USER->id, 'revoked' => 0])) {
            redirect(
                new moodle_url('/admin/tool/mfa/user_preferences.php'),
                get_string('factorsetup', 'tool_mfa', $this->get_destination()),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        }

        $mform->addElement('html', $OUTPUT->heading(get_string('setupfactor', 'factor_securemessenger'), 2));

        if (empty($this->get_pending_destination())) {
            $choices = helper::get_option_choices();
            if (empty($choices)) {
                $mform->addElement(
                    'html',
                    $OUTPUT->notification(get_string('error:nooptions', 'factor_securemessenger'), 'warning')
                );
                return $mform;
            }

            $mform->addElement('select', 'messengeroption', get_string('messengeroption', 'factor_securemessenger'), $choices);
            $mform->setType('messengeroption', PARAM_ALPHANUMEXT);

            $countryoptions = helper::get_country_calling_code_options();
            $mform->addElement(
                'select',
                'phonecountry',
                get_string('phonecountry', 'factor_securemessenger'),
                $countryoptions
            );
            $mform->setType('phonecountry', PARAM_ALPHA);
            if (!empty($USER->country) && isset($countryoptions[$USER->country])) {
                $mform->setDefault('phonecountry', $USER->country);
            } else if (!empty($CFG->country) && isset($countryoptions[$CFG->country])) {
                $mform->setDefault('phonecountry', $CFG->country);
            } else if (isset($countryoptions['US'])) {
                $mform->setDefault('phonecountry', 'US');
            }

            $mform->addElement('text', 'phonenumber', get_string('phonenumber', 'factor_securemessenger'), [
                'autocomplete' => 'tel-national',
                'inputmode' => 'tel',
            ]);
            $mform->setType('phonenumber', PARAM_TEXT);

            $mform->addElement('text', 'destination', get_string('destination', 'factor_securemessenger'), [
                'autocomplete' => 'off',
                'inputmode' => 'text',
            ]);
            $mform->setType('destination', PARAM_TEXT);

            foreach ($choices as $key => $name) {
                $option = helper::get_option($key);
                if ($option && $option->requiresphone) {
                    $mform->hideIf('destination', 'messengeroption', 'eq', $key);
                } else {
                    $mform->hideIf('phonecountry', 'messengeroption', 'eq', $key);
                    $mform->hideIf('phonenumber', 'messengeroption', 'eq', $key);
                }
            }

            $message = \html_writer::tag('div', '', ['class' => 'col-md-3']);
            $message .= \html_writer::tag(
                'div',
                \html_writer::tag('p', get_string('destination_help', 'factor_securemessenger')),
                ['class' => 'col-md-9']
            );
            $mform->addElement('html', \html_writer::tag('div', $message, ['class' => 'row']));
        }

        return $mform;
    }

    /**
     * Defines setup form elements after data has been set.
     *
     * @param \MoodleQuickForm $mform Form to modify.
     * @return \MoodleQuickForm
     */
    public function setup_factor_form_definition_after_data(\MoodleQuickForm $mform): \MoodleQuickForm {
        global $OUTPUT;

        $destination = $this->get_pending_destination();
        if (empty($destination)) {
            return $mform;
        }

        $option = $this->get_pending_option();
        if ($option === null) {
            $mform->addElement(
                'html',
                $OUTPUT->notification(get_string('error:optionunavailable', 'factor_securemessenger'), 'warning')
            );
            return $mform;
        }

        $duration = get_config('factor_securemessenger', 'duration');
        $code = $this->secretmanager->create_secret($duration, true);
        $message = null;
        if (!empty($code)) {
            $message = $this->send_verification_code((int) $code, $destination, $option);
        }

        if (empty($message) || $message->status !== \core_sms\message_status::GATEWAY_SENT) {
            $this->secretmanager->cleanup_temp_secrets();
            $status = $message ? $message->status->value : get_string('error');
            $mform->addElement(
                'html',
                $OUTPUT->notification(
                    get_string('error:otpsendfailed', 'factor_securemessenger', $status),
                    'error'
                )
            );
            $this->add_edit_destination_button($mform);
            $mform->disable_form_change_checker();
            return $mform;
        }

        $description = get_string('setupcodedesc', 'factor_securemessenger', (object) [
            'destination' => s($destination),
            'option' => s($option->name),
        ]);
        $mform->addElement('html', \html_writer::tag('p', $OUTPUT->notification($description, 'success')));

        $mform->addElement(new \tool_mfa\local\form\verification_field());
        $mform->setType('verificationcode', PARAM_ALPHANUM);

        $this->add_edit_destination_button($mform);

        $mform->disable_form_change_checker();

        return $mform;
    }

    /**
     * Validate setup form data.
     *
     * @param array $data Submitted data.
     * @return array
     */
    public function setup_factor_form_validation(array $data): array {
        $errors = [];

        if (empty($this->get_pending_destination())) {
            $option = empty($data['messengeroption']) ? null : helper::get_option($data['messengeroption']);
            if ($option === null) {
                $errors['messengeroption'] = get_string('error:invalidoption', 'factor_securemessenger');
            } else if ($option->requiresphone) {
                if (empty($data['phonecountry'])) {
                    $errors['phonecountry'] = get_string('error:emptyphonecountry', 'factor_securemessenger');
                }
                if (empty(trim((string) ($data['phonenumber'] ?? '')))) {
                    $errors['phonenumber'] = get_string('error:emptyphonenumber', 'factor_securemessenger');
                } else {
                    $destination = helper::format_phone_destination($data['phonecountry'] ?? '', $data['phonenumber']);
                    if (!helper::is_valid_phone_destination($destination)) {
                        $errors['phonenumber'] = get_string('error:invalidphonenumber', 'factor_securemessenger');
                    } else {
                        $accountcheck = helper::check_phone_account($option, $destination);
                        if (!$accountcheck->success) {
                            $errors['phonenumber'] = $accountcheck->error;
                        }
                    }
                }
            } else if (empty(trim((string) ($data['destination'] ?? '')))) {
                $errors['destination'] = get_string('error:emptydestination', 'factor_securemessenger');
            }
        } else {
            if (empty($data['verificationcode'])) {
                $errors['verificationcode'] = get_string('error:emptyverification', 'factor_securemessenger');
            } else if ($this->secretmanager->validate_secret($data['verificationcode']) !== secret_manager::VALID) {
                $errors['verificationcode'] = get_string('error:wrongverification', 'factor_securemessenger');
            }
        }

        return $errors;
    }

    /**
     * Clean pending setup state if setup is cancelled.
     *
     * @param int $factorid Factor id.
     * @return void
     */
    public function setup_factor_form_is_cancelled(int $factorid): void {
        global $SESSION;

        unset($SESSION->factor_securemessenger_destination);
        unset($SESSION->factor_securemessenger_option);

        $secretmanager = new secret_manager('securemessenger');
        $secretmanager->cleanup_temp_secrets();
    }

    /**
     * Setup submit button string.
     *
     * @return string|null
     */
    public function setup_factor_form_submit_button_string(): ?string {
        if (!empty($this->get_pending_destination())) {
            return get_string('setupsubmitcode', 'factor_securemessenger');
        }
        return get_string('setupsubmitdestination', 'factor_securemessenger');
    }

    /**
     * Create the user's secure messenger factor.
     *
     * @param stdClass $data Submitted data.
     * @return stdClass|null
     */
    public function setup_user_factor(stdClass $data): ?stdClass {
        global $DB, $SESSION, $USER;

        if (empty($this->get_pending_destination())) {
            $option = helper::get_option($data->messengeroption);
            $SESSION->factor_securemessenger_destination = $this->get_destination_from_setup_data($data, $option);
            $SESSION->factor_securemessenger_option = (string) $data->messengeroption;

            redirect(new moodle_url('/admin/tool/mfa/action.php', [
                'action' => 'setup',
                'factor' => 'securemessenger',
            ]));
        }

        if ($DB->record_exists('tool_mfa', ['userid' => $USER->id, 'factor' => $this->name, 'revoked' => 0])) {
            return null;
        }

        $time = time();
        $row = (object) [
            'userid' => $USER->id,
            'factor' => $this->name,
            'secret' => (string) $this->get_pending_option_key(),
            'label' => $this->get_pending_destination(),
            'timecreated' => $time,
            'createdfromip' => $USER->lastip,
            'timemodified' => $time,
            'lastverified' => $time,
            'revoked' => 0,
        ];

        $id = $DB->insert_record('tool_mfa', $row);
        $record = $DB->get_record('tool_mfa', ['id' => $id]);
        $this->create_event_after_factor_setup($USER);

        unset($SESSION->factor_securemessenger_destination);
        unset($SESSION->factor_securemessenger_option);

        return $record;
    }

    /**
     * Returns active user factors of this type.
     *
     * @param stdClass $user User to check.
     * @return array
     */
    public function get_all_user_factors(stdClass $user): array {
        global $DB;

        $sql = 'SELECT *
                  FROM {tool_mfa}
                 WHERE userid = ?
                   AND factor = ?
                   AND label IS NOT NULL
                   AND revoked = 0';

        return $DB->get_records_sql($sql, [$user->id, $this->name]);
    }

    /**
     * Whether the factor requires input.
     *
     * @return bool
     */
    public function has_input(): bool {
        return true;
    }

    /**
     * Whether users must set this factor up.
     *
     * @return bool
     */
    public function has_setup(): bool {
        return true;
    }

    /**
     * Whether setup buttons should be shown.
     *
     * @return bool
     */
    public function show_setup_buttons(): bool {
        return !empty(helper::get_configured_options());
    }

    /**
     * Whether the factor can be revoked.
     *
     * @return bool
     */
    public function has_revoke(): bool {
        return true;
    }

    /**
     * Generate and send the login code.
     *
     * @return int|null
     */
    private function generate_and_send_code(): ?int {
        global $DB, $USER;

        $duration = get_config('factor_securemessenger', 'duration');
        $instance = $DB->get_record('tool_mfa', ['factor' => $this->name, 'userid' => $USER->id, 'revoked' => 0]);
        if (empty($instance)) {
            return null;
        }

        $secret = $this->secretmanager->create_secret($duration, false);
        if (!empty($secret)) {
            $option = helper::get_option($instance->secret);
            if ($option !== null) {
                $this->send_verification_code((int) $secret, $instance->label, $option);
            }
        }

        return $instance->id;
    }

    /**
     * Send a verification code through the configured messenger gateway.
     *
     * @param int $secret One-time code.
     * @param string|null $destination Destination address or number.
     * @param stdClass $option Messenger option.
     * @return \core_sms\message|null
     */
    private function send_verification_code(int $secret, ?string $destination, stdClass $option): ?\core_sms\message {
        global $CFG, $SITE, $USER;

        if (empty($destination)) {
            return null;
        }

        $url = new moodle_url($CFG->wwwroot);
        $content = (object) [
            'fullname' => $SITE->fullname,
            'url' => $url->get_host(),
            'code' => $secret,
            'option' => $option->name,
        ];
        $message = get_string('messagestring', 'factor_securemessenger', $content);

        $manager = \core\di::get(\core_sms\manager::class);
        try {
            return $manager->send(
                recipientnumber: $destination,
                content: $message,
                component: 'factor_securemessenger',
                messagetype: 'mfa',
                recipientuserid: $USER->id,
                issensitive: true,
                async: false,
                gatewayid: $option->gatewayid,
            );
        } catch (\Throwable $exception) {
            debugging('Secure messenger OTP send failed: ' . $exception->getMessage(), DEBUG_DEVELOPER);
            return null;
        }
    }

    /**
     * Add the edit destination button to the setup form.
     *
     * @param \MoodleQuickForm $mform Form to modify.
     * @return void
     */
    private function add_edit_destination_button(\MoodleQuickForm $mform): void {
        $editdestination = \html_writer::link(
            new moodle_url('/admin/tool/mfa/factor/securemessenger/editdestination.php', ['sesskey' => sesskey()]),
            get_string('editdestination', 'factor_securemessenger'),
            ['class' => 'btn btn-secondary', 'type' => 'button']
        );
        $mform->addElement('html', \html_writer::tag('div', $editdestination, ['class' => 'float-sm-start col-md-4']));
    }

    /**
     * Check entered code.
     *
     * @param string $enteredcode Entered code.
     * @return bool
     */
    private function check_verification_code(string $enteredcode): bool {
        return $this->secretmanager->validate_secret($enteredcode) === secret_manager::VALID;
    }

    /**
     * Returns all possible states for a user.
     *
     * @param stdClass $user User to check.
     * @return array
     */
    public function possible_states(stdClass $user): array {
        return [
            \tool_mfa\plugininfo\factor::STATE_PASS,
            \tool_mfa\plugininfo\factor::STATE_NEUTRAL,
            \tool_mfa\plugininfo\factor::STATE_FAIL,
            \tool_mfa\plugininfo\factor::STATE_UNKNOWN,
        ];
    }

    /**
     * Get the login description associated with this factor.
     *
     * @return string
     */
    public function get_login_desc(): string {
        $record = $this->get_current_user_record();
        if (empty($record)) {
            return get_string('error:notsetup', 'factor_securemessenger');
        }

        $option = helper::get_option($record->secret);
        if ($option === null) {
            return get_string('error:optionunavailable', 'factor_securemessenger');
        }

        return get_string('logindesc', 'factor_securemessenger', (object) [
            'destination' => $record->label,
            'option' => $option->name,
        ]);
    }

    /**
     * Return management information for this factor.
     *
     * @param int $factorid Factor id.
     * @return string
     */
    public function get_manage_info(int $factorid): string {
        global $DB;

        $record = $DB->get_record('tool_mfa', ['id' => $factorid], '*', MUST_EXIST);
        $option = helper::get_option($record->secret);
        $optionname = $option ? $option->name : get_string('optionunavailable', 'factor_securemessenger');

        return get_string('manageinfo', 'factor_securemessenger', (object) [
            'destination' => $record->label,
            'option' => $optionname,
        ]);
    }

    /**
     * Get the current user's active factor record.
     *
     * @return stdClass|null
     */
    private function get_current_user_record(): ?stdClass {
        global $DB, $USER;

        return $DB->get_record('tool_mfa', ['factor' => $this->name, 'userid' => $USER->id, 'revoked' => 0]) ?: null;
    }

    /**
     * Get a pending setup destination from the session.
     *
     * @return string|null
     */
    private function get_pending_destination(): ?string {
        global $SESSION;

        if (!empty($SESSION->factor_securemessenger_destination)) {
            return $SESSION->factor_securemessenger_destination;
        }
        return null;
    }

    /**
     * Get the current user's destination or pending setup destination.
     *
     * @return string|null
     */
    private function get_destination(): ?string {
        $pending = $this->get_pending_destination();
        if (!empty($pending)) {
            return $pending;
        }

        $record = $this->get_current_user_record();
        return $record->label ?? null;
    }

    /**
     * Get a pending setup option key from the session.
     *
     * @return string|null
     */
    private function get_pending_option_key(): ?string {
        global $SESSION;

        if (!empty($SESSION->factor_securemessenger_option)) {
            return $SESSION->factor_securemessenger_option;
        }
        return null;
    }

    /**
     * Get a pending setup option from the session.
     *
     * @return stdClass|null
     */
    private function get_pending_option(): ?stdClass {
        return helper::get_option($this->get_pending_option_key());
    }

    /**
     * Return the destination represented by setup form data.
     *
     * @param stdClass $data Submitted form data.
     * @param stdClass|null $option Selected messenger option.
     * @return string
     */
    private function get_destination_from_setup_data(stdClass $data, ?stdClass $option): string {
        if ($option && $option->requiresphone) {
            return helper::format_phone_destination((string) $data->phonecountry, (string) $data->phonenumber);
        }

        return trim((string) $data->destination);
    }
}
