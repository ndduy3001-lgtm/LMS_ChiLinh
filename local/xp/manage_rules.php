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
 * Admin page for managing XP rules.
 *
 * Provides a CRUD interface for administrators to create, edit, delete,
 * and toggle XP rules that control how points are awarded.
 *
 * @package    local_xp
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/xp:managexprules', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/xp/manage_rules.php'));
$PAGE->set_title(get_string('manage_rules', 'local_xp'));
$PAGE->set_heading(get_string('manage_rules', 'local_xp'));
$PAGE->set_pagelayout('admin');

$ruleengine = new \local_xp\rule_engine();

// Handle form actions.
$action = optional_param('action', '', PARAM_ALPHA);
$ruleid = optional_param('ruleid', 0, PARAM_INT);

if ($action && confirm_sesskey()) {
    switch ($action) {
        case 'delete':
            if ($ruleid) {
                $ruleengine->delete_rule($ruleid);
                \core\notification::add(
                    get_string('rule_deleted', 'local_xp'),
                    \core\output\notification::NOTIFY_SUCCESS
                );
            }
            redirect($PAGE->url);
            break;

        case 'toggle':
            if ($ruleid) {
                $newstate = $ruleengine->toggle_rule($ruleid);
                $msg = $newstate ? get_string('rule_enabled', 'local_xp')
                                 : get_string('rule_disabled', 'local_xp');
                \core\notification::add($msg, \core\output\notification::NOTIFY_SUCCESS);
            }
            redirect($PAGE->url);
            break;

        case 'reset':
            $ruleengine->reset_to_defaults();
            \core\notification::add(
                get_string('rules_reset', 'local_xp'),
                \core\output\notification::NOTIFY_SUCCESS
            );
            redirect($PAGE->url);
            break;

        case 'save':
            $eventname = required_param('eventname', PARAM_RAW);
            $points = required_param('points', PARAM_INT);
            $enabled = optional_param('enabled', 0, PARAM_INT);
            $mingradepercent = optional_param('min_grade_percent', 0, PARAM_INT);

            $conditions = null;
            if ($mingradepercent > 0) {
                $conditions = ['min_grade_percent' => $mingradepercent];
            }

            if ($ruleid > 0) {
                // Update existing rule.
                $data = [
                    'eventname' => $eventname,
                    'points' => $points,
                    'enabled' => $enabled ? 1 : 0,
                    'conditions' => $conditions ? json_encode($conditions) : null,
                ];
                $ruleengine->update_rule($ruleid, $data);
                \core\notification::add(
                    get_string('rule_updated', 'local_xp'),
                    \core\output\notification::NOTIFY_SUCCESS
                );
            } else {
                // Create new rule.
                $ruleengine->create_rule($eventname, $points, 0, (bool) $enabled, $conditions);
                \core\notification::add(
                    get_string('rule_created', 'local_xp'),
                    \core\output\notification::NOTIFY_SUCCESS
                );
            }
            redirect($PAGE->url);
            break;
    }
}

// Fetch all system-wide rules.
$rules = $ruleengine->get_rules(0);
$supportedevents = \local_xp\rule_engine::get_supported_events();

echo $OUTPUT->header();

// Page description.
echo html_writer::tag('p', get_string('manage_rules_desc', 'local_xp'), ['class' => 'lead mb-4']);

// Rules table.
echo html_writer::start_tag('div', ['class' => 'local-xp-rules-container']);

if (!empty($rules)) {
    $table = new html_table();
    $table->head = [
        get_string('event', 'local_xp'),
        get_string('points', 'local_xp'),
        get_string('conditions_label', 'local_xp'),
        get_string('status', 'local_xp'),
        get_string('actions', 'local_xp'),
    ];
    $table->attributes['class'] = 'table table-striped table-hover generaltable';

    foreach ($rules as $rule) {
        // Event name display.
        $eventmeta = $supportedevents[$rule->eventname] ?? null;
        $eventdisplay = $eventmeta
            ? get_string('reason_' . $eventmeta['name'], 'local_xp')
            : $rule->eventname;

        // Conditions display.
        $conditionsdisplay = '-';
        if (!empty($rule->conditions)) {
            $conditions = json_decode($rule->conditions, true);
            if (!empty($conditions['min_grade_percent'])) {
                $conditionsdisplay = get_string('min_grade_condition', 'local_xp', $conditions['min_grade_percent']);
            }
        }

        // Status badge.
        if ($rule->enabled) {
            $statusbadge = html_writer::tag('span', get_string('enabled', 'local_xp'), [
                'class' => 'badge badge-success bg-success text-white',
            ]);
        } else {
            $statusbadge = html_writer::tag('span', get_string('disabled', 'local_xp'), [
                'class' => 'badge badge-secondary bg-secondary text-white',
            ]);
        }

        // Action buttons.
        $toggleurl = new moodle_url($PAGE->url, [
            'action' => 'toggle', 'ruleid' => $rule->id, 'sesskey' => sesskey(),
        ]);
        $deleteurl = new moodle_url($PAGE->url, [
            'action' => 'delete', 'ruleid' => $rule->id, 'sesskey' => sesskey(),
        ]);

        $togglelabel = $rule->enabled ? get_string('disable', 'local_xp') : get_string('enable', 'local_xp');
        $actions = html_writer::link($toggleurl, $togglelabel, ['class' => 'btn btn-sm btn-outline-primary mr-1 me-1']);
        $actions .= html_writer::link('#', get_string('edit'), [
            'class' => 'btn btn-sm btn-outline-secondary mr-1 me-1',
            'data-action' => 'edit-rule',
            'data-ruleid' => $rule->id,
            'data-eventname' => $rule->eventname,
            'data-points' => $rule->points,
            'data-enabled' => $rule->enabled,
            'data-conditions' => $rule->conditions ?? '',
        ]);
        $actions .= html_writer::link($deleteurl, get_string('delete'), [
            'class' => 'btn btn-sm btn-outline-danger',
            'onclick' => "return confirm('" . get_string('confirm_delete_rule', 'local_xp') . "');",
        ]);

        $table->data[] = [
            $eventdisplay,
            html_writer::tag('strong', $rule->points . ' XP'),
            $conditionsdisplay,
            $statusbadge,
            $actions,
        ];
    }

    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification(get_string('no_rules', 'local_xp'), 'info');
}

echo html_writer::end_tag('div');

// Add/Edit rule form.
echo html_writer::start_tag('div', ['class' => 'card mt-4', 'id' => 'rule-form-card']);
echo html_writer::start_tag('div', ['class' => 'card-header']);
echo html_writer::tag('h4', get_string('add_rule', 'local_xp'), ['id' => 'rule-form-title', 'class' => 'mb-0']);
echo html_writer::end_tag('div');
echo html_writer::start_tag('div', ['class' => 'card-body']);

$formurl = new moodle_url($PAGE->url, ['action' => 'save', 'sesskey' => sesskey()]);
echo html_writer::start_tag('form', ['method' => 'post', 'action' => $formurl->out(false), 'id' => 'rule-form']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'ruleid', 'value' => '0', 'id' => 'rule-form-id']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'save']);

// Event select.
echo html_writer::start_tag('div', ['class' => 'form-group row mb-3']);
echo html_writer::tag('label', get_string('event', 'local_xp'), [
    'class' => 'col-sm-3 col-form-label', 'for' => 'eventname',
]);
echo html_writer::start_tag('div', ['class' => 'col-sm-9']);
$eventoptions = [];
foreach ($supportedevents as $eventclass => $meta) {
    $eventoptions[$eventclass] = $meta['description'];
}
echo html_writer::select($eventoptions, 'eventname', '', false, ['class' => 'form-control', 'id' => 'eventname']);
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// Points input.
echo html_writer::start_tag('div', ['class' => 'form-group row mb-3']);
echo html_writer::tag('label', get_string('points', 'local_xp'), [
    'class' => 'col-sm-3 col-form-label', 'for' => 'points',
]);
echo html_writer::start_tag('div', ['class' => 'col-sm-9']);
echo html_writer::empty_tag('input', [
    'type' => 'number', 'name' => 'points', 'id' => 'points',
    'class' => 'form-control', 'value' => '100', 'min' => '1', 'max' => '10000',
]);
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// Min grade percent (conditional field).
echo html_writer::start_tag('div', ['class' => 'form-group row mb-3', 'id' => 'grade-condition-row']);
echo html_writer::tag('label', get_string('min_grade_label', 'local_xp'), [
    'class' => 'col-sm-3 col-form-label', 'for' => 'min_grade_percent',
]);
echo html_writer::start_tag('div', ['class' => 'col-sm-9']);
echo html_writer::empty_tag('input', [
    'type' => 'number', 'name' => 'min_grade_percent', 'id' => 'min_grade_percent',
    'class' => 'form-control', 'value' => '50', 'min' => '0', 'max' => '100',
]);
echo html_writer::tag('small', get_string('min_grade_help', 'local_xp'), ['class' => 'form-text text-muted']);
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// Enabled checkbox.
echo html_writer::start_tag('div', ['class' => 'form-group row mb-3']);
echo html_writer::tag('label', get_string('enabled', 'local_xp'), [
    'class' => 'col-sm-3 col-form-label',
]);
echo html_writer::start_tag('div', ['class' => 'col-sm-9']);
echo html_writer::start_tag('div', ['class' => 'form-check']);
echo html_writer::checkbox('enabled', 1, true, '', ['class' => 'form-check-input', 'id' => 'enabled']);
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// Submit.
echo html_writer::start_tag('div', ['class' => 'form-group row']);
echo html_writer::start_tag('div', ['class' => 'col-sm-9 offset-sm-3']);
echo html_writer::empty_tag('input', [
    'type' => 'submit', 'value' => get_string('save_rule', 'local_xp'),
    'class' => 'btn btn-primary', 'id' => 'save-rule-btn',
]);
echo ' ';
echo html_writer::link($PAGE->url, get_string('cancel'), ['class' => 'btn btn-secondary']);
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

echo html_writer::end_tag('form');
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// Reset to defaults button.
$reseturl = new moodle_url($PAGE->url, ['action' => 'reset', 'sesskey' => sesskey()]);
echo html_writer::start_tag('div', ['class' => 'mt-3']);
echo html_writer::link($reseturl, get_string('reset_defaults', 'local_xp'), [
    'class' => 'btn btn-outline-warning',
    'onclick' => "return confirm('" . get_string('confirm_reset', 'local_xp') . "');",
]);
echo html_writer::end_tag('div');

// Inline JS for edit functionality and conditional field visibility.
$js = <<<JS
document.addEventListener('DOMContentLoaded', function() {
    var eventSelect = document.getElementById('eventname');
    var gradeRow = document.getElementById('grade-condition-row');

    function toggleGradeField() {
        if (eventSelect.value.indexOf('user_graded') !== -1) {
            gradeRow.style.display = '';
        } else {
            gradeRow.style.display = 'none';
        }
    }

    eventSelect.addEventListener('change', toggleGradeField);
    toggleGradeField();

    // Edit button handlers.
    document.querySelectorAll('[data-action="edit-rule"]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var ruleId = this.dataset.ruleid;
            var eventname = this.dataset.eventname;
            var points = this.dataset.points;
            var enabled = this.dataset.enabled;
            var conditions = this.dataset.conditions;

            document.getElementById('rule-form-id').value = ruleId;
            document.getElementById('eventname').value = eventname;
            document.getElementById('points').value = points;
            document.getElementById('enabled').checked = (enabled == '1');
            document.getElementById('rule-form-title').textContent = 'Edit Rule';

            if (conditions) {
                try {
                    var cond = JSON.parse(conditions);
                    if (cond.min_grade_percent) {
                        document.getElementById('min_grade_percent').value = cond.min_grade_percent;
                    }
                } catch(err) {}
            }

            toggleGradeField();
            document.getElementById('rule-form-card').scrollIntoView({behavior: 'smooth'});
        });
    });
});
JS;
echo html_writer::script($js);

echo $OUTPUT->footer();
