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
 * Live preview for certificate element editing form.
 *
 * Updates preview element in real-time as form fields change.
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {

    return {
        /**
         * Initialize live preview for the given element ID.
         *
         * @param {number} eid - Element ID to update
         */
        init: function(eid) {
            // Find the preview element in the side panel.
            var $previewEl = $('#certmanager-element-preview').find('[data-eid="' + eid + '"]');

            if (!$previewEl.length) {
                console.warn('Preview element not found for eid=' + eid);
                return;
            }

            // Canvas dimensions for conversion (A4 landscape).
            var pw = 297;  // mm
            var ph = 210;  // mm

            // Get form.
            var $form = $('form');

            // Font families mapping.
            var fontMap = {
                'helvetica': 'Helvetica, Arial, sans-serif',
                'times': '"Times New Roman", serif',
                'courier': '"Courier New", monospace',
                'dejavusans': '"DejaVu Sans", sans-serif'
            };

            // Text alignment mapping.
            var alignMap = {
                'L': 'left',
                'C': 'center',
                'R': 'right'
            };

            // Map form fields to update functions.
            var updates = {
                'font': function(val) {
                    $previewEl.css('font-family', fontMap[val] || 'Helvetica');
                },
                'fontsize': function(val) {
                    $previewEl.css('font-size', val + 'pt');
                },
                'colour': function(val) {
                    if (val && val.match(/^#[0-9a-f]{6}$/i)) {
                        $previewEl.css('color', val);
                    }
                },
                'alignment': function(val) {
                    $previewEl.css('text-align', alignMap[val] || 'left');
                },
                'posx': function(val) {
                    var pct = (parseFloat(val) / pw) * 100;
                    $previewEl.css('left', pct + '%');
                },
                'posy': function(val) {
                    var pct = (parseFloat(val) / ph) * 100;
                    $previewEl.css('top', pct + '%');
                },
                'width': function(val) {
                    var v = parseFloat(val);
                    if (v === 0 || val === '') {
                        $previewEl.css('width', 'auto');
                    } else {
                        var pct = (v / pw) * 100;
                        $previewEl.css('width', pct + '%');
                    }
                },
                'height': function(val) {
                    var v = parseFloat(val);
                    if (v === 0 || val === '') {
                        $previewEl.css('height', 'auto');
                    } else {
                        var pct = (v / ph) * 100;
                        $previewEl.css('height', pct + '%');
                    }
                }
            };

            // Attach listeners to all tracked fields.
            for (var fieldName in updates) {
                if (updates.hasOwnProperty(fieldName)) {
                    var $field = $form.find('[name="' + fieldName + '"]');
                    if ($field.length) {
                        (function(name, updateFn) {
                            $field.on('input change', function() {
                                updateFn($(this).val());
                            });
                        })(fieldName, updates[fieldName]);
                    }
                }
            }

            console.log('Live preview initialized for element ' + eid);
        }
    };
});