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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Calculates certificate expiry dates based on various options
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_certmanager;

/**
 * Calculates certificate expiry dates based on various options.
 *
 * Supports:
 * - Activity default (from validityperiod)
 * - Predefined periods (6 months, 1 year, 2 years)
 * - Custom days
 * - Manual date
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class expiry_calculator {
    /**
     * Available expiry options for dropdown.
     *
     * @return array Key => [label, description]
     */
    public static function get_expiry_options(): array {
        return [
            'default'     => ['Use Activity Default', 'Uses the Validity period from activity settings'],
            '180days'     => ['6 Months', 'Certificate expires 180 days from award date'],
            '365days'     => ['1 Year', 'Certificate expires 365 days from award date'],
            '730days'     => ['2 Years', 'Certificate expires 730 days (2 years) from award date'],
            '1095days'    => ['3 Years', 'Certificate expires 1095 days (3 years) from award date'],
            'custom'      => ['Custom Days', 'Specify a custom number of days'],
            'manual'      => ['Manual Date', 'Choose an exact expiry date'],
            'no-expiry'   => ['No Expiration', 'Certificate never expires'],
        ];
    }

    /**
     * Get preset expiry periods in seconds.
     *
     * @return array Period key => seconds
     */
    public static function get_preset_periods(): array {
        return [
            '180days'  => 180 * 86400,
            '365days'  => 365 * 86400,
            '730days'  => 730 * 86400,
            '1095days' => 1095 * 86400,
        ];
    }

    /**
     * Calculate expiry timestamp based on option and parameters.
     *
     * @param int $timecertified Award timestamp
     * @param string $option Expiry option key
     * @param int $validityperiod Activity default validity in days
     * @param int $customdays Optional custom days (for 'custom' option)
     * @param int $manualdate Optional manual date timestamp (for 'manual' option)
     * @return int|false Expiry timestamp, or 0 if no expiry, or false on error
     */
    public static function calculate(
        int $timecertified,
        string $option,
        int $validityperiod,
        int $customdays = 0,
        int $manualdate = 0
    ) {
        $option = trim($option);

        // No expiration.
        if ($option === 'no-expiry') {
            return 0;
        }

        // Activity default.
        if ($option === 'default') {
            if ($validityperiod <= 0) {
                return 0;
            }
            return $timecertified + ($validityperiod * 86400);
        }

        // Preset periods.
        $presets = self::get_preset_periods();
        if (isset($presets[$option])) {
            return $timecertified + $presets[$option];
        }

        // Custom days.
        if ($option === 'custom') {
            if ($customdays <= 0) {
                return false; // Invalid.
            }
            return $timecertified + ($customdays * 86400);
        }

        // Manual date.
        if ($option === 'manual') {
            if ($manualdate <= 0) {
                return false; // Invalid.
            }
            return $manualdate;
        }

        // Unknown option.
        return false;
    }

    /**
     * Get a human-readable description of the calculated expiry.
     *
     * @param int $timeexpires Expiry timestamp (0 = never expires)
     * @return string Description, e.g. "Expires 15 January 2027"
     */
    public static function format_expiry(int $timeexpires): string {
        if ($timeexpires === 0) {
            return 'Never expires';
        }
        return 'Expires ' . userdate($timeexpires, '%d %B %Y');
    }

    /**
     * Validate expiry form data.
     *
     * @param string $option Expiry option
     * @param int $customdays Custom days value
     * @param int $manualdate Manual date value
     * @return array Empty array if valid, else [fieldname => error message]
     */
    public static function validate_form_data(
        string $option,
        int $customdays = 0,
        int $manualdate = 0
    ): array {
        $errors = [];

        if ($option === 'custom') {
            if ($customdays <= 0) {
                $errors['customdays'] = 'Custom days must be greater than 0';
            } else if ($customdays > 10000) {
                $errors['customdays'] = 'Custom days must be 10,000 or less';
            }
        } else if ($option === 'manual') {
            if ($manualdate <= 0) {
                $errors['manualdate'] = 'Please select a valid expiry date';
            } else if ($manualdate <= time()) {
                $errors['manualdate'] = 'Expiry date must be in the future';
            }
        }

        return $errors;
    }
}
