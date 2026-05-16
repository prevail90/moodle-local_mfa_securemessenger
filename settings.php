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
 * Settings for the secure messenger MFA factor.
 *
 * @package     factor_securemessenger
 * @copyright   2026 prevail90
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $smsconfigureurl = new moodle_url(
        '/sms/configure.php',
        [
            'returnurl' => new moodle_url(
                '/admin/settings.php',
                ['section' => 'factor_securemessenger'],
            ),
        ],
    );
    $smsconfigureurl = $smsconfigureurl->out();

    $gatewayoptions = \factor_securemessenger\helper::get_gateway_options();

    $settings->add(
        new admin_setting_heading(
            'factor_securemessenger/heading',
            '',
            new lang_string('settings:heading', 'factor_securemessenger'),
        ),
    );

    if (count($gatewayoptions) <= 1) {
        $notify = new \core\output\notification(
            get_string('settings:setupdesc', 'factor_securemessenger', $smsconfigureurl),
            \core\output\notification::NOTIFY_WARNING
        );
        $settings->add(new admin_setting_heading('factor_securemessenger/setupdesc', '', $OUTPUT->render($notify)));
    }

    $enabled = new admin_setting_configcheckbox(
        'factor_securemessenger/enabled',
        new lang_string('settings:enablefactor', 'tool_mfa'),
        new lang_string('settings:enablefactor_help', 'tool_mfa'),
        0,
    );
    $enabled->set_updatedcallback(function () {
        \tool_mfa\manager::do_factor_action(
            'securemessenger',
            get_config('factor_securemessenger', 'enabled') ? 'enable' : 'disable',
        );
    });
    $settings->add($enabled);

    $settings->add(
        new admin_setting_configtext(
            'factor_securemessenger/weight',
            new lang_string('settings:weight', 'tool_mfa'),
            new lang_string('settings:weight_help', 'tool_mfa'),
            100,
            PARAM_INT,
        ),
    );

    $settings->add(
        new admin_setting_configduration(
            'factor_securemessenger/duration',
            new lang_string('settings:duration', 'tool_mfa'),
            new lang_string('settings:duration_help', 'tool_mfa'),
            30 * MINSECS,
            MINSECS,
        ),
    );

    $fixedoptions = [
        1 => 'Signal',
        2 => 'WhatsApp',
        3 => 'Telegram',
    ];

    for ($i = 1; $i <= \factor_securemessenger\helper::OPTION_LIMIT; $i++) {
        $optionname = $fixedoptions[$i] ?? get_string('settings:customoption', 'factor_securemessenger', $i - 3);
        $enabledlabel = isset($fixedoptions[$i])
            ? get_string('settings:optionenablednamed', 'factor_securemessenger', $optionname)
            : get_string('settings:optionenabled', 'factor_securemessenger');
        $gatewaylabel = isset($fixedoptions[$i])
            ? get_string('settings:optiongatewaynamed', 'factor_securemessenger', $optionname)
            : get_string('settings:optiongateway', 'factor_securemessenger');
        $settings->add(
            new admin_setting_heading(
                'factor_securemessenger/option' . $i . 'heading',
                get_string('settings:optionheading', 'factor_securemessenger', $optionname),
                '',
            ),
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'factor_securemessenger/option' . $i . 'enabled',
                $enabledlabel,
                new lang_string('settings:optionenabled_help', 'factor_securemessenger'),
                $i <= 3 ? 1 : 0,
            ),
        );

        if (!isset($fixedoptions[$i])) {
            $settings->add(
                new admin_setting_configtext(
                    'factor_securemessenger/option' . $i . 'name',
                    new lang_string('settings:optionname', 'factor_securemessenger'),
                    new lang_string('settings:optionname_help', 'factor_securemessenger'),
                    '',
                    PARAM_TEXT,
                ),
            );
        }

        $settings->add(
            new admin_setting_configselect(
                'factor_securemessenger/option' . $i . 'gateway',
                $gatewaylabel,
                new lang_string('settings:optiongateway_help', 'factor_securemessenger', $smsconfigureurl),
                0,
                $gatewayoptions,
            ),
        );

        if ($i === 1 || $i === 2) {
            $settings->add(
                new admin_setting_configselect(
                    'factor_securemessenger/option' . $i . 'checkgateway',
                    get_string('settings:accountcheckgatewaynamed', 'factor_securemessenger', $optionname),
                    new lang_string('settings:accountcheckgateway_help', 'factor_securemessenger'),
                    0,
                    $gatewayoptions,
                ),
            );
        }
    }
}
