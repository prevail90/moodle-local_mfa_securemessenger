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
 * Hook registrations for the secure messenger MFA factor.
 *
 * @package     factor_securemessenger
 * @subpackage  tool_mfa
 * @copyright   2026 prevail90
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core_sms\hook\before_gateway_deleted::class,
        'callback' => \factor_securemessenger\hook_listener::class . '::check_gateway_usage',
    ],
    [
        'hook' => \core_sms\hook\before_gateway_disabled::class,
        'callback' => \factor_securemessenger\hook_listener::class . '::check_gateway_usage',
    ],
];
