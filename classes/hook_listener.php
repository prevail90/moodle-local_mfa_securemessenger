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

use core_sms\hook\before_gateway_deleted;
use core_sms\hook\before_gateway_disabled;

/**
 * SMS gateway hook listener.
 *
 * @package     factor_securemessenger
 * @copyright   2026 prevail90
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_listener {

    /**
     * Prevent deleting or disabling gateways currently mapped to messenger options.
     *
     * @param before_gateway_deleted|before_gateway_disabled $hook Hook instance.
     * @return void
     */
    public static function check_gateway_usage(
        before_gateway_deleted|before_gateway_disabled $hook,
    ): void {
        try {
            if (helper::gateway_is_used((int) $hook->gateway->id)) {
                $hook->stop_propagation();
            }
        } catch (\dml_exception $exception) {
            $hook->stop_propagation();
        }
    }
}
