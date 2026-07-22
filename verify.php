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
 * Certificate verification page. Public — reachable by scanning the QR on the PDF.
 * Shows whether the code corresponds to a real, currently-valid certificate.
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Files.RequireLogin.Missing -- This page is public, intentionally no login required.
require(__DIR__ . '/../../config.php');
// phpcs:enable moodle.Files.RequireLogin.Missing

$code = optional_param('code', '', PARAM_ALPHANUMEXT);

$PAGE->set_url('/mod/certmanager/verify.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('verifytitle', 'mod_certmanager'));
$PAGE->set_heading(get_string('verifytitle', 'mod_certmanager'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('verifyheading', 'mod_certmanager'));

// Rate limiting: Prevent brute force attacks.
$ip = getremoteaddr();
$cache = \cache::make('mod_certmanager', 'verify_attempts');
$attemptskey = 'verify_attempts_' . $ip;
$attempts = $cache->get($attemptskey) ?? 0;

if ($attempts > 10) {
    // After 10 failed attempts, block for 1 hour.
    echo html_writer::div(
        'Too many verification attempts. Please try again later.',
        'alert alert-danger'
    );
    echo $OUTPUT->footer();
    exit;
}

if ($code === '') {
    echo html_writer::div(
        'Enter or scan a verification code.',
        'alert alert-info'
    );
    echo '<form method="post" action="verify.php" class="mt-3">';
    echo '<div class="form-group"><label for="code">Verification code</label>';
    echo '<input type="text" name="code" id="code" class="form-control" style="max-width:400px;" placeholder="e.g., ABC123XYZ789">';
    echo '</div>';
    echo '<button class="btn btn-primary mt-2">Verify Certificate</button>';
    echo '</form>';
    echo $OUTPUT->footer();
    exit;
}

// Hash the code for database lookup (same way password hashing works).
$codehash = hash('sha256', strtoupper(trim($code)));

// Search database for hashed code.
$cert = $DB->get_record('certmanager_certificates', ['codehash' => $codehash]);
if (!$cert) {
    // Increment failed attempt counter.
    $cache->set($attemptskey, $attempts + 1, 3600); // 1 hour expiry

    // Log failed verification attempt.
    $event = \mod_certmanager\event\verification_failed::create([
        'other' => ['ip' => $ip, 'attempts' => $attempts + 1],
    ]);
    $event->trigger();

    echo html_writer::div('Certificate not found or code is invalid.', 'alert alert-danger');
    echo html_writer::div('Please verify the code and try again.', 'alert alert-info');
    echo $OUTPUT->footer();
    exit;
}

$certmanager = $DB->get_record('certmanager', ['id' => $cert->certmanagerid]);

if (!$certmanager) {
    echo html_writer::div('Certificate record is incomplete.', 'alert alert-warning');
    echo $OUTPUT->footer();
    exit;
}

// Reset successful attempt counter.
$cache->delete($attemptskey);

$expired = ($cert->timeexpires > 0 && $cert->timeexpires < time());
$class = $expired ? 'alert-warning' : 'alert-success';
$status = $expired ? 'EXPIRED' : 'VALID';

echo html_writer::start_div("alert $class");
echo html_writer::tag('h4', $status);

// ANONYMIZED OUTPUT - Don't expose personal data on public page.
echo html_writer::tag(
    'p',
    'Certificate <strong>' . s(format_string($certmanager->name)) . '</strong>' .
    ' has been verified.'
);

echo html_writer::tag('p', 'Awarded: ' . userdate($cert->timecertified));

if ($cert->timeexpires > 0) {
    if ($expired) {
        echo html_writer::tag('p', 'Expired: ' . userdate($cert->timeexpires), 'class="text-danger"');
    } else {
        echo html_writer::tag('p', 'Expires: ' . userdate($cert->timeexpires));
    }
} else {
    echo html_writer::tag('p', 'This certificate does not expire.');
}

echo html_writer::end_div();

// Log successful verification.
$event = \mod_certmanager\event\certificate_verified::create([
    'objectid' => $cert->id,
    'other' => ['ip' => $ip],
]);
$event->trigger();

echo $OUTPUT->footer();
