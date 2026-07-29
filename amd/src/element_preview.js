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
 * Live preview for certificate element editing.
 *
 * Updates canvas element in real-time as form fields change.
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
            var $form = $('form');
            var $canvas = $('#certmanager-canvas');
            var $el = $canvas.find('[data-eid="' + eid + '"]');

            if (!$el.length) {
                console.warn('Element not found in canvas: ' + eid);
                return;
            }

            // Map form field names to element style properties and getters.
            var fieldMap = {
                // Styling fields
                'fontsize': {
                    update: function(val) {
                        $el.css('font-size', val + 'pt');
                    }
                },
                'colour': {
                    update: function(val) {
                        if (val && val.match(/^#[0-9a-f]{6}$/i)) {
                            $el.css('color', val);
                        }
                    }
                },
                'font': {
                    update: function(val) {
                        var fontMap = {
                            'helvetica': 'Helvetica, Arial, sans-serif',
                            'times': '"Times New Roman", serif',
                            'courier': '"Courier New", monospace',
                            'dejavusans': '"DejaVu Sans", sans-serif'
                        };
                        $el.css('font-family', fontMap[val] || 'Helvetica');
                    }
                },
                'alignment': {
                    update: function(val) {
                        var alignMap = { 'L': 'left', 'C': 'center', 'R': 'right' };
                        $el.css('text-align', alignMap[val] || 'left');
                    }
                },
                // Geometry fields (in mm, need to convert to %)
                'posx': {
                    update: function(val) {
                        var canvasWidth = $canvas.width();
                        var pw = parseFloat($canvas.data('pw')) || 297;
                        var pct = (val / pw) * 100;
                        $el.css('left', pct + '%');
                    }
                },
                'posy': {
                    update: function(val) {
                        var canvasHeight = $canvas.height();
                        var ph = parseFloat($canvas.data('ph')) || 210;
                        var pct = (val / ph) * 100;
                        $el.css('top', pct + '%');
                    }
                },
                'width': {
                    update: function(val) {
                        if (val === '0' || val === '') {
                            $el.css('width', 'auto');
                        } else {
                            var canvasWidth = $canvas.width();
                            var pw = parseFloat($canvas.data('pw')) || 297;
                            var pct = (val / pw) * 100;
                            $el.css('width', pct + '%');
                        }
                    }
                },
                'height': {
                    update: function(val) {
                        if (val === '0' || val === '') {
                            $el.css('height', 'auto');
                        } else {
                            var canvasHeight = $canvas.height();
                            var ph = parseFloat($canvas.data('ph')) || 210;
                            var pct = (val / ph) * 100;
                            $el.css('height', pct + '%');
                        }
                    }
                }
            };

            // Attach event listeners to form inputs.
            for (var fieldName in fieldMap) {
                if (fieldMap.hasOwnProperty(fieldName)) {
                    var $field = $form.find('[name="' + fieldName + '"]');
                    if ($field.length) {
                        (function(name, updater) {
                            $field.on('input change', function() {
                                var val = $(this).val();
                                updater.update(val);
                            });
                        })(fieldName, fieldMap[fieldName]);
                    }
                }
            }

            console.log('Live preview initialized for element ' + eid);
        }
    };
});