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
 * Certificate element designer for a specific certmanager activity.
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_certmanager\element\manager;
use mod_certmanager\element\factory;

$id = required_param('id', PARAM_INT); // Course module id.
$action = optional_param('action', '', PARAM_ALPHA);
$type = optional_param('type', '', PARAM_ALPHANUMEXT);
$eid = optional_param('eid', 0, PARAM_INT); // Element id (for element actions).

$cm = get_coursemodule_from_id('certmanager', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$certmanager = $DB->get_record('certmanager', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('moodle/course:manageactivities', context_course::instance($course->id));

$pageurl = new moodle_url('/mod/certmanager/edit.php', ['id' => $id]);
$PAGE->set_url($pageurl);
$PAGE->set_title(format_string($certmanager->name) . ' - Designer');
$PAGE->set_heading(format_string($certmanager->name));
$PAGE->set_pagelayout('admin');

// Use enhanced CSS with drag-and-drop support.
$PAGE->requires->css(new moodle_url('/mod/certmanager/style/designer.css'));

// Actions.

if ($action === 'add' && $type !== '' && confirm_sesskey()) {
    if (array_key_exists($type, factory::get_types())) {
        // Only one background allowed.
        if ($type === 'background') {
            $exists = $DB->record_exists(
                'certmanager_elements',
                ['certmanagerid' => $certmanager->id, 'element' => 'background']
            );
            if ($exists) {
                redirect(
                    $pageurl,
                    'A background image element already exists — edit or delete it first.',
                    null,
                    \core\output\notification::NOTIFY_WARNING
                );
            }
        }
        manager::create((int)$certmanager->id, $type);
    }
    redirect($pageurl);
}

if ($action === 'delete' && $eid > 0 && confirm_sesskey()) {
    manager::delete($eid, $context);
    redirect($pageurl);
}

if ($action === 'duplicate' && $eid > 0 && confirm_sesskey()) {
    manager::duplicate($eid, $context);
    redirect($pageurl);
}

if ($action === 'moveup' && $eid > 0 && confirm_sesskey()) {
    manager::move($eid, -1);
    redirect($pageurl);
}

if ($action === 'movedown' && $eid > 0 && confirm_sesskey()) {
    manager::move($eid, +1);
    redirect($pageurl);
}

if ($action === 'reset' && confirm_sesskey()) {
    $existing = $DB->get_records('certmanager_elements', ['certmanagerid' => $certmanager->id]);
    foreach ($existing as $row) {
        manager::delete((int)$row->id, $context);
    }
    manager::seed_defaults((int)$certmanager->id, true);
    redirect($pageurl, get_string('resettodefault', 'mod_certmanager'), null, \core\output\notification::NOTIFY_SUCCESS);
}

// Seed on first visit.
manager::seed_defaults((int)$certmanager->id);

$elements = factory::get_elements((int)$certmanager->id);

// Split into background + others so background always renders first (behind).
$background = null;
$others = [];
foreach ($elements as $el) {
    if ($el->get_type() === 'background') {
        $background = $el;
    } else {
        $others[] = $el;
    }
}

// Render.

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('designerheading', 'mod_certmanager'));

// Toolbar.
echo html_writer::start_div('certmanager-toolbar mb-3');

// Add-element menu.
echo '<form method="get" action="' . $pageurl->out(false) .
    '" style="display:inline-flex;align-items:center;gap:6px;margin-right:12px;">';
echo '<input type="hidden" name="id" value="' . (int)$id . '">';
echo '<input type="hidden" name="action" value="add">';
echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
echo '<label style="margin:0;"><strong>Add element:</strong></label>';
echo '<select name="type" onchange="if(this.value){this.form.submit()}">';
echo '<option value="">— choose —</option>';
foreach (factory::get_type_menu() as $key => $label) {
    echo '<option value="' . s($key) . '">' . s($label) . '</option>';
}
echo '</select>';
echo '</form>';

// Preview PDF (opens in new tab).
$previewurl = new moodle_url('/mod/certmanager/preview.php', ['id' => $id]);
echo html_writer::link(
    $previewurl,
    '📄 Preview PDF',
    ['class' => 'btn btn-outline-primary btn-sm', 'style' => 'margin-right:6px;',
    'target' => '_blank']
);

// Export template.
$exporturl = new moodle_url(
    '/mod/certmanager/export.php',
    ['id' => $id, 'sesskey' => sesskey()]
);
echo html_writer::link(
    $exporturl,
    '⬇ Export template',
    ['class' => 'btn btn-outline-secondary btn-sm', 'style' => 'margin-right:6px;']
);

// Import template.
$importurl = new moodle_url('/mod/certmanager/import.php', ['id' => $id]);
echo html_writer::link(
    $importurl,
    '⬆ Import template',
    ['class' => 'btn btn-outline-secondary btn-sm', 'style' => 'margin-right:6px;']
);

// Reset.
$reseturl = new moodle_url($pageurl, ['action' => 'reset', 'sesskey' => sesskey()]);
echo html_writer::link(
    $reseturl,
    'Reset to defaults',
    ['class' => 'btn btn-outline-danger btn-sm', 'style' => 'margin-right:6px;',
     'onclick' => "return confirm('Delete all elements and restore default layout?');",
    ]
);

// Back.
$backurl = new moodle_url('/mod/certmanager/view.php', ['id' => $id]);
echo html_writer::link(
    $backurl,
    'Back to activity',
    ['class' => 'btn btn-outline-primary btn-sm']
);

echo html_writer::end_div();

// Split-view: canvas + side panel.
echo html_writer::start_div('certmanager-design-wrap');

$pw = (float)$certmanager->pagewidth;
$ph = (float)$certmanager->pageheight;
echo html_writer::start_div('certmanager-canvas-panel');
echo '<div class="certmanager-canvas-hint">' .
    'Drag elements to reposition. Drag the corner handle to resize. Changes save automatically.</div>';

echo '<div id="certmanager-canvas" class="certmanager-canvas" ' .
     'data-cmid="' . (int)$cm->id . '" ' .
     'data-pw="' . $pw . '" data-ph="' . $ph . '" ' .
     'style="aspect-ratio:' . $pw . '/' . $ph . ';">';

// Helper closure to render one element in the canvas.
$render = function ($el) use ($pw, $ph, $certmanager, $course, $pageurl, $cm) {
    $rec = $el->get_record();
    $classes = 'certmanager-el certmanager-el-' . s($el->get_type());
    $x = ($rec->posx / $pw) * 100;
    $y = ($rec->posy / $ph) * 100;
    $w = ($rec->width > 0 ? ($rec->width / $pw) * 100 : 0);
    $h = ($rec->height > 0 ? ($rec->height / $ph) * 100 : 0);

    $style = 'left:' . $x . '%;top:' . $y . '%;';
    if ($w > 0) {
        $style .= 'width:' . $w . '%;';
    }
    if ($h > 0) {
        $style .= 'height:' . $h . '%;';
    }
    if ($rec->fontsize) {
        $style .= 'font-size:' . max(6, $rec->fontsize * 0.9) . 'px;';
    }
    if ($rec->colour) {
        $style .= 'color:' . s($rec->colour) . ';';
    }
    if ($rec->alignment) {
        $align = ['L' => 'left', 'C' => 'center', 'R' => 'right'][$rec->alignment] ?? 'left';
        $style .= 'text-align:' . $align . ';';
    }
    if ($el->get_type() === 'background') {
        $style = 'left:0;top:0;width:100%;height:100%;';
    }

    echo '<div class="' . $classes . '" data-eid="' . (int)$rec->id . '" style="' . $style . '">';
    echo '<div class="certmanager-el-content">' . $el->render_html($certmanager, $course) . '</div>';
    echo '<div class="certmanager-el-toolbar">';
    $editurl = new moodle_url(
        '/mod/certmanager/edit_element.php',
        ['cmid' => $cm->id, 'eid' => (int)$rec->id]
    );
    echo html_writer::link(
        $editurl,
        'Edit',
        ['class' => 'certmanager-btn certmanager-btn-edit', 'title' => 'Edit element']
    );
    $delurl = new moodle_url(
        $pageurl,
        ['action' => 'delete', 'eid' => (int)$rec->id, 'sesskey' => sesskey()]
    );
    echo html_writer::link(
        $delurl,
        '×',
        ['class' => 'certmanager-btn certmanager-btn-delete',
         'title' => 'Delete element',
         'onclick' => "return confirm('Delete this element?');",
        ]
    );
    echo '</div>';
    echo '<div class="certmanager-el-resize"></div>';
    echo '</div>';
};

// Background FIRST (behind everything).
if ($background) {
    $render($background);
}
// Then everything else in sort order.
foreach ($others as $el) {
    $render($el);
}

echo '</div>'; // Canvas.
echo html_writer::end_div(); // Canvas panel.

// Side panel: element list with reorder controls.
echo html_writer::start_div('certmanager-side-panel');
echo html_writer::tag('h4', 'Elements');
if (empty($elements)) {
    echo '<p class="text-muted">No elements yet. Add some using the toolbar above.</p>';
} else {
    // Show background first in the list too, but without reorder buttons.
    $listorder = [];
    if ($background) {
        $listorder[] = $background;
    }
    foreach ($others as $el) {
        $listorder[] = $el;
    }

    echo '<ol class="certmanager-el-list">';
    $total = count($others);
    $idx = 0;
    foreach ($listorder as $el) {
        $rec = $el->get_record();
        $isbg = ($el->get_type() === 'background');
        $editurl = new moodle_url(
            '/mod/certmanager/edit_element.php',
            ['cmid' => $cm->id, 'eid' => (int)$rec->id]
        );
        $delurl  = new moodle_url(
            $pageurl,
            ['action' => 'delete', 'eid' => (int)$rec->id, 'sesskey' => sesskey()]
        );
        $dupurl  = new moodle_url(
            $pageurl,
            ['action' => 'duplicate', 'eid' => (int)$rec->id, 'sesskey' => sesskey()]
        );
        $upurl   = new moodle_url(
            $pageurl,
            ['action' => 'moveup', 'eid' => (int)$rec->id, 'sesskey' => sesskey()]
        );
        $downurl = new moodle_url(
            $pageurl,
            ['action' => 'movedown', 'eid' => (int)$rec->id, 'sesskey' => sesskey()]
        );

        // Add data-eid attribute for drag-and-drop.
        echo '<li class="' . ($isbg ? 'is-background' : '') . '" data-eid="' . (int)$rec->id . '">';
        echo '<div class="certmanager-el-list-head">';
        echo '<span class="certmanager-el-list-name">' . s($el->get_name()) . '</span>';
        echo ' <span class="certmanager-el-list-type text-muted">(' . s($el->get_type()) . ')</span>';
        echo '</div>';
        echo '<div class="certmanager-el-list-actions">';
        if (!$isbg) {
            // Wrap old buttons (hidden by CSS when drag is enabled).
            echo '<div class="move-buttons">';

            // Move up (disabled for first non-bg element).
            $upattrs = ['class' => 'btn btn-sm btn-outline-secondary', 'title' => 'Move up (or drag)'];
            if ($idx === 0) {
                echo '<span class="btn btn-sm btn-outline-secondary disabled">↑</span>';
            } else {
                echo html_writer::link($upurl, '↑', $upattrs);
            }
            // Move down (disabled for last).
            if ($idx === $total - 1) {
                echo ' <span class="btn btn-sm btn-outline-secondary disabled">↓</span>';
            } else {
                echo ' ' . html_writer::link(
                    $downurl,
                    '↓',
                    ['class' => 'btn btn-sm btn-outline-secondary', 'title' => 'Move down (or drag)']
                );
            }
            echo ' ' . html_writer::link(
                $dupurl,
                '⧉',
                ['class' => 'btn btn-sm btn-outline-secondary', 'title' => 'Duplicate']
            );

            echo '</div>';
            $idx++;
        }
        echo ' ' . html_writer::link(
            $editurl,
            'Edit',
            ['class' => 'btn btn-sm btn-outline-primary']
        );
        echo ' ' . html_writer::link(
            $delurl,
            'Delete',
            ['class' => 'btn btn-sm btn-outline-danger',
             'onclick' => "return confirm('Delete this element?');",
            ]
        );
        echo '</div>';
        echo '</li>';
    }
    echo '</ol>';
}
echo html_writer::end_div();

echo html_writer::end_div(); // Wrap.

$PAGE->requires->js_call_amd('mod_certmanager/designer', 'init', [(string)sesskey()]);

$PAGE->requires->js(new moodle_url('/mod/certmanager/js/certificate_design.js'));

echo $OUTPUT->footer();
