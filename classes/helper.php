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

/**
 * Shared helpers for the secure messenger MFA factor.
 *
 * @package     factor_securemessenger
 * @subpackage  tool_mfa
 * @copyright   2026 William Nelson
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /** @var int Maximum number of administrator-configurable messenger options. */
    public const OPTION_LIMIT = 5;

    /** @var array Fixed messenger options keyed by configuration slot. */
    private const FIXED_OPTIONS = [
        'option1' => [
            'name' => 'Signal',
            'requiresphone' => true,
            'accountcheck' => 'signal',
        ],
        'option2' => [
            'name' => 'WhatsApp',
            'requiresphone' => true,
            'accountcheck' => 'whatsapp',
        ],
        'option3' => [
            'name' => 'Telegram',
            'requiresphone' => false,
            'accountcheck' => null,
        ],
    ];

    /**
     * Return enabled SMS gateway records as select options.
     *
     * @return array
     */
    public static function get_gateway_options(): array {
        $gateways = [0 => new \lang_string('none')];
        $manager = \core\di::get(\core_sms\manager::class);
        $gatewayrecords = $manager->get_gateway_records(['enabled' => 1]);

        foreach ($gatewayrecords as $record) {
            $values = explode('\\', $record->gateway);
            $gatewayname = new \lang_string('pluginname', $values[0]);
            $gateways[$record->id] = $record->name . ' (' . $gatewayname . ')';
        }

        return $gateways;
    }

    /**
     * Return the configured messenger options with usable gateways.
     *
     * @return array keyed by option key.
     */
    public static function get_configured_options(): array {
        $options = [];

        for ($i = 1; $i <= self::OPTION_LIMIT; $i++) {
            $key = self::get_option_key($i);
            $enabled = (int) get_config('factor_securemessenger', $key . 'enabled');
            $definition = self::get_option_definition($key);
            $name = $definition->name;
            $gatewayid = (int) get_config('factor_securemessenger', $key . 'gateway');

            if ($enabled !== 1 || $name === '' || $gatewayid <= 0) {
                continue;
            }

            $options[$key] = (object) [
                'key' => $key,
                'name' => $name,
                'gatewayid' => $gatewayid,
                'checkgatewayid' => (int) get_config('factor_securemessenger', $key . 'checkgateway'),
                'requiresphone' => $definition->requiresphone,
                'accountcheck' => $definition->accountcheck,
            ];
        }

        return $options;
    }

    /**
     * Return select choices for configured messenger options.
     *
     * @return array
     */
    public static function get_option_choices(): array {
        $choices = [];
        foreach (self::get_configured_options() as $key => $option) {
            $choices[$key] = $option->name;
        }
        return $choices;
    }

    /**
     * Return a configured option by key.
     *
     * @param string|null $key Option key.
     * @return \stdClass|null
     */
    public static function get_option(?string $key): ?\stdClass {
        $options = self::get_configured_options();
        return $options[$key] ?? null;
    }

    /**
     * Check whether a gateway is used by an enabled messenger option.
     *
     * @param int $gatewayid Gateway id.
     * @return bool
     */
    public static function gateway_is_used(int $gatewayid): bool {
        foreach (self::get_configured_options() as $option) {
            if ((int) $option->gatewayid === $gatewayid || (int) $option->checkgatewayid === $gatewayid) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return country choices annotated with their international calling code.
     *
     * @return array keyed by ISO region code.
     */
    public static function get_country_calling_code_options(): array {
        $options = [];
        $phoneutil = \libphonenumber\PhoneNumberUtil::getInstance();

        foreach (get_string_manager()->get_list_of_countries() as $country => $name) {
            $callingcode = $phoneutil->getCountryCodeForRegion($country);
            if (empty($callingcode)) {
                continue;
            }
            $options[$country] = $name . ' (+' . $callingcode . ')';
        }

        return $options;
    }

    /**
     * Format a submitted country and national number as international digits.
     *
     * @param string $country ISO region code.
     * @param string $number National phone number.
     * @return string
     */
    public static function format_phone_destination(string $country, string $number): string {
        $phoneutil = \libphonenumber\PhoneNumberUtil::getInstance();
        try {
            $parsed = $phoneutil->parse($number, strtoupper($country));
            $formatted = $phoneutil->format($parsed, \libphonenumber\PhoneNumberFormat::E164);
        } catch (\libphonenumber\NumberParseException $exception) {
            return '';
        }

        return ltrim($formatted, '+');
    }

    /**
     * Validate an international phone number without a leading plus sign.
     *
     * @param string $number Phone number.
     * @return bool
     */
    public static function is_valid_phone_destination(string $number): bool {
        if (preg_match('/^[1-9]\d{1,14}$/', $number) !== 1) {
            return false;
        }

        $phoneutil = \libphonenumber\PhoneNumberUtil::getInstance();
        try {
            return $phoneutil->isValidNumber($phoneutil->parse('+' . $number));
        } catch (\libphonenumber\NumberParseException $exception) {
            return false;
        }
    }

    /**
     * Check whether the destination is registered with the selected messenger.
     *
     * @param \stdClass $option Messenger option.
     * @param string $number International digits without a leading plus sign.
     * @return \stdClass
     */
    public static function check_phone_account(\stdClass $option, string $number): \stdClass {
        if (empty($option->accountcheck)) {
            return self::account_check_result(true, false);
        }

        if (empty($option->checkgatewayid)) {
            return self::account_check_result(true, false);
        }

        $manager = \core\di::get(\core_sms\manager::class);
        try {
            $message = $manager->send(
                recipientnumber: $number,
                content: $option->accountcheck,
                component: 'factor_securemessenger',
                messagetype: 'accountcheck',
                recipientuserid: null,
                issensitive: true,
                async: false,
                gatewayid: $option->checkgatewayid,
            );
        } catch (\Throwable $exception) {
            return self::account_check_result(false, true, get_string('error:accountcheckexception', 'factor_securemessenger', $exception->getMessage()));
        }

        if ($message->status === \core_sms\message_status::GATEWAY_SENT) {
            return self::account_check_result(true, true);
        }

        return self::account_check_result(
            false,
            true,
            get_string('error:accountcheckgatewayfailed', 'factor_securemessenger', $message->status->value)
        );
    }

    /**
     * Return the complete definition for an option slot.
     *
     * @param string $key Option key.
     * @return \stdClass
     */
    private static function get_option_definition(string $key): \stdClass {
        if (isset(self::FIXED_OPTIONS[$key])) {
            return (object) [
                'name' => self::FIXED_OPTIONS[$key]['name'],
                'requiresphone' => self::FIXED_OPTIONS[$key]['requiresphone'],
                'accountcheck' => self::FIXED_OPTIONS[$key]['accountcheck'],
            ];
        }

        return (object) [
            'name' => trim((string) get_config('factor_securemessenger', $key . 'name')),
            'requiresphone' => false,
            'accountcheck' => null,
        ];
    }

    /**
     * Return a normalised account check result.
     *
     * @param bool $success Whether the account check passed.
     * @param bool $checked Whether a remote check was performed.
     * @param string $error Error message.
     * @return \stdClass
     */
    private static function account_check_result(bool $success, bool $checked, string $error = ''): \stdClass {
        return (object) [
            'success' => $success,
            'checked' => $checked,
            'error' => $error,
        ];
    }

    /**
     * Return a stable configuration key for an option slot.
     *
     * @param int $number Slot number.
     * @return string
     */
    private static function get_option_key(int $number): string {
        return 'option' . $number;
    }
}
