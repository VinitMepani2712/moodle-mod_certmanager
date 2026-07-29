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
 * Certificate designer AMD module - Drag & resize elements on a mm-scaled canvas.
 *
 * Sends live updates to the External API on drag-end / resize-end.
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Certificate designer: drag & resize elements on a mm-scaled canvas.
// Sends live updates to ajax.php on drag-end / resize-end.
define(['jquery'], function($) {

    var sesskey = '';
    var canvas = null;
    var pw = 297, ph = 210; // mm

    /**
     * Save element position and size to server via AJAX.
     * @param {HTMLElement} el - The element to save
     * @param {Object} extras - Additional data like width/height
     */
    function save(el, extras) {
        var eid = el.dataset.eid;
        var cmid = canvas.dataset.cmid;
        var xPct = parseFloat(el.style.left);
        var yPct = parseFloat(el.style.top);
        var posx = (xPct / 100) * pw;
        var posy = (yPct / 100) * ph;

        var data = {
            cmid: cmid,
            eid: eid,
            sesskey: sesskey,
            posx: posx.toFixed(2),
            posy: posy.toFixed(2)
        };
        if (extras && typeof extras.width === 'number') {
            data.width = extras.width.toFixed(2);
        }
        if (extras && typeof extras.height === 'number') {
            data.height = extras.height.toFixed(2);
        }
        $.post(M.cfg.wwwroot + '/mod/certmanager/ajax.php', data).fail(function() {
            // Non-fatal; user can retry by dragging again.
            if (window.console) {
                // eslint-disable-next-line no-console
                console.warn('certmanager: save failed');
            }
        });
    }

    /**
     * Attach drag functionality to a certificate element.
     * @param {HTMLElement} el - The element to make draggable
     */
    function attachDrag(el) {
        el.addEventListener('mousedown', function(e) {
            // Ignore drags on toolbar or resize handle.
            if (e.target.closest('.certmanager-el-toolbar') ||
                e.target.closest('.certmanager-el-resize')) {
                return;
            }
            // Background element isn't draggable — it always fills the page.
            if (el.classList.contains('certmanager-el-background')) {
                return;
            }
            e.preventDefault();
            var rect = el.getBoundingClientRect();
            var canvasRect = canvas.getBoundingClientRect();
            var offsetX = e.clientX - rect.left;
            var offsetY = e.clientY - rect.top;

            /**
             * Handle element movement during drag.
             * @param {MouseEvent} ev - The mouse move event
             */
            function onMove(ev) {
                var newLeftPx = ev.clientX - canvasRect.left - offsetX;
                var newTopPx  = ev.clientY - canvasRect.top - offsetY;
                // Clamp inside canvas.
                newLeftPx = Math.max(0, Math.min(newLeftPx, canvasRect.width - rect.width));
                newTopPx  = Math.max(0, Math.min(newTopPx, canvasRect.height - rect.height));
                el.style.left = (newLeftPx / canvasRect.width) * 100 + '%';
                el.style.top  = (newTopPx / canvasRect.height) * 100 + '%';
            }
            /**
             * Handle end of drag operation.
             */
            function onUp() {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
                save(el, {});
            }
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        });
    }

    /**
     * Attach resize functionality to a certificate element.
     * @param {HTMLElement} el - The element to make resizable
     */
    function attachResize(el) {
        var handle = el.querySelector('.certmanager-el-resize');
        if (!handle) { return; }
        // Background isn't resizable.
        if (el.classList.contains('certmanager-el-background')) {
            handle.style.display = 'none';
            return;
        }
        handle.addEventListener('mousedown', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var canvasRect = canvas.getBoundingClientRect();
            var elRect = el.getBoundingClientRect();
            var startX = e.clientX;
            var startY = e.clientY;
            var startW = elRect.width;
            var startH = elRect.height;

            /**
             * Handle element resizing during drag.
             * @param {MouseEvent} ev - The mouse move event
             */
            function onMove(ev) {
                var newW = Math.max(10, startW + (ev.clientX - startX));
                var newH = Math.max(10, startH + (ev.clientY - startY));
                // Cap so element doesn't overflow canvas.
                // Maximum width/height available from element's current position to canvas edge.
                var maxW = canvasRect.width - (elRect.left - canvasRect.left);
                var maxH = canvasRect.height - (elRect.top - canvasRect.top);
                newW = Math.min(newW, maxW);
                newH = Math.min(newH, maxH);
                el.style.width  = (newW / canvasRect.width) * 100 + '%';
                el.style.height = (newH / canvasRect.height) * 100 + '%';
            }
            /**
             * Handle end of resize operation.
             */
            function onUp() {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
                var w = (parseFloat(el.style.width)  / 100) * pw;
                var h = (parseFloat(el.style.height) / 100) * ph;
                save(el, {width: w, height: h});
            }
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        });
    }

    return {
        /**
         * Initialize the certificate designer with the given session key.
         * @param {string} sk - The Moodle session key
         */
        init: function(sk) {
            sesskey = sk;
            canvas = document.getElementById('certmanager-canvas');
            if (!canvas) { return; }
            pw = parseFloat(canvas.dataset.pw) || 297;
            ph = parseFloat(canvas.dataset.ph) || 210;

            var els = canvas.querySelectorAll('.certmanager-el');
            els.forEach(function(el) {
                attachDrag(el);
                attachResize(el);
            });
        }
    };
});