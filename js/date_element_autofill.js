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

/**
 * Date element auto-fill for date element configuration form.
 *
 * Updates the date element prefix when the date item dropdown is changed.
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

document.addEventListener('DOMContentLoaded', function() {
    const dateItemSelect = document.querySelector('[name="dateitem"]');
    const prefixInput = document.querySelector('[name="prefix"]');
    
    if (!dateItemSelect || !prefixInput) {
        return;
    }
    
    // Update prefix on page load
    updatePrefix();
    
    // Update prefix when dropdown changes
    dateItemSelect.addEventListener('change', function() {
        updatePrefix();
    });
    
    function updatePrefix() {
        const selectedValue = dateItemSelect.value;
        
        if (selectedValue === 'awarded') {
            prefixInput.value = 'Awarded: ';     
        } else if (selectedValue === 'expires') {
            prefixInput.value = 'Expires: ';    
        }
    }
});