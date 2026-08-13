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

namespace factor_securemessenger\privacy;

use core_privacy\local\metadata\collection;

/**
 * Privacy provider.
 *
 * @package     factor_securemessenger
 * @copyright   2026 prevail90
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider {

    /**
     * Describe personal data sent to configured SMS gateways.
     *
     * @param collection $collection The metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_external_location_link(
            'securemessenger_gateway',
            [
                'destination' => 'privacy:metadata:securemessenger_gateway:destination',
                'verificationcode' => 'privacy:metadata:securemessenger_gateway:verificationcode',
                'accountcheck' => 'privacy:metadata:securemessenger_gateway:accountcheck',
            ],
            'privacy:metadata:securemessenger_gateway',
        );

        return $collection;
    }
}
