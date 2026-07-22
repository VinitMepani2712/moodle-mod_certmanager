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
 * English language strings for Certification Manager plugin.
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addrule'] = 'Add rule';
$string['assignusers'] = 'Assign users';
$string['awardcertificationfor'] = 'Award certification: {$a}';
$string['awardtext'] = 'Custom award text';
$string['awardtype'] = 'Award certificate when:';
$string['awardtype_activity'] = 'Required activities are completed';
$string['awardtype_course'] = 'Course completion criteria are met';
$string['awardtype_help'] = 'Choose the completion condition for automatic certificate award:
- **Course Complete**: Award when course completion criteria are met
- **Activities Complete**: Award when selected activities are completed';
$string['backgroundimage'] = 'Certificate background image';
$string['certgeneratedrefresh'] = 'Certificate generated successfully! Refresh the page to download.';
$string['certificatedesign'] = 'Certificate design';
$string['certificatedesignhelp'] = 'Customize the appearance of generated certificates. Use {name} in award text to insert the learner\'s name.';
$string['certificatetitle'] = 'Certificate title';
$string['certificationname'] = 'Certification name';
$string['certmanager:addinstance'] = 'Add a new Certification Manager activity';
$string['certmanager:view'] = 'View Certification Manager activity';
$string['customdays'] = 'Number of Days';
$string['customdays_help'] = 'Enter the number of days from the award date when this certificate should expire. For example, enter 90 for a 90-day certificate.';
$string['deletetemplate'] = 'Delete';
$string['designerheading'] = 'Certificate designer';
$string['downloadcertificate'] = 'Download my certificate';
$string['editelementheading'] = 'Edit element: {$a}';
$string['elementsaved'] = 'Element saved';
$string['enableautowage'] = 'Enable automatic certificate award';
$string['enableautowage_help'] = 'When enabled, certificates will be awarded automatically when students complete the course or required activities. When disabled, only manual awards are available.';
$string['enablecertificate'] = 'Generate certificates automatically';
$string['enablecertificate_help'] = 'When enabled, a PDF certificate will be generated automatically when a learner is certified.';
$string['enableexpiryoverride'] = 'Allow Manual Expiry Override';
$string['enableexpiryoverride_help'] = 'If enabled, teachers can choose custom expiry dates when manually awarding certificates. If disabled, all certificates use the activity\'s default validity period.';
$string['enablerules'] = 'Enable dynamic assignment rules';
$string['enablerules_desc'] = 'When enabled, the scheduled task reconciles certification assignments from cohort and profile-field rules.';
$string['errcertgen'] = 'Certificate generation failed: {$a}';
$string['errcertnotfound'] = 'Certificate not found.';
$string['errcertnotpdf'] = 'The certificate file is not a valid PDF.';
$string['errorminrequired'] = 'Enter the minimum number of courses required.';
$string['errorwindownovalidity'] = 'A recertification window requires a validity period to be set.';
$string['errorwindowperiod'] = 'Enter a valid recertification window period.';
$string['errtemplatenotfound'] = 'Certificate template not found.';
$string['eventcertificationawarded'] = 'Certification awarded';
$string['eventcertificationexpired'] = 'Certification expired';
$string['eventcertificationlapsed'] = 'Certification lapsed';
$string['eventrecertwindowopened'] = 'Recertification window opened';
$string['expiryformat_expires'] = 'Expires {$a}';
$string['expiryformat_never'] = 'Never expires';
$string['expiryoption'] = 'Expiry Option';
$string['expiryoption_help'] = 'Choose how to calculate the certificate expiry date:

**Use Activity Default** — Uses the Validity period configured in activity settings

**Preset Periods** — 6 Months, 1 Year, 2 Years, 3 Years from the award date

**Custom Days** — Specify your own number of days from the award date

**Manual Date** — Choose an exact date and time for expiration

**No Expiration** — Certificate will never expire';
$string['expirytooltip_awarded'] = 'Awarded: {$a}';
$string['expirytooltip_expires'] = 'Expires: {$a}';
$string['expirytooltip_never'] = 'Never expires';
$string['exporttemplate'] = 'Export';
$string['fieldvalue'] = 'Field value';
$string['font'] = 'Font family';
$string['fontcolor'] = 'Font color';
$string['graceperiod'] = 'Grace Period (days)';
$string['graceperiod_help'] = 'Number of days after expiry before the certification is marked as "lapsed". During this period, it\'s still marked as "expired" but users can renew without restarting from scratch.';
$string['importheading'] = 'Import certificate template';
$string['invalidelementtype'] = 'Invalid element type: {$a}';
$string['landscape'] = 'Landscape';
$string['loadtemplate'] = 'Load template';
$string['logicall'] = 'Learner must complete all courses';
$string['logicmin'] = 'Learner must complete a minimum number of courses';
$string['logictype'] = 'Completion logic';
$string['logoimage'] = 'Logo / Institution image';
$string['manualdate'] = 'Expiry Date & Time';
$string['manualdate_help'] = 'Select the exact date and time when this certificate should expire. The date must be in the future.';
$string['minrequired'] = 'Minimum activities required';
$string['minrequired_help'] = 'Minimum number of selected activities that must be completed for automatic certification. Enter 0 to require completion of ALL selected activities.';
$string['modulename'] = 'Certification Manager';
$string['modulenameplural'] = 'Certification Managers';
$string['mycertifications'] = 'My certifications';
$string['nofileuploaded'] = 'No file uploaded';
$string['notif_awarded_body'] = 'Congratulations! You have been awarded the certification "{$a->name}". Valid until: {$a->expiry}. You can download your certificate from the course activity.';
$string['notif_awarded_subject'] = 'Certification awarded: {$a}';
$string['notif_expired_body'] = 'Your certification "{$a}" has expired. Please complete the required activities to renew it.';
$string['notif_expired_subject'] = 'Certification expired: {$a}';
$string['notif_lapsed_body'] = 'Your certification "{$a}" has lapsed. You are no longer certified.';
$string['notif_lapsed_subject'] = 'Certification lapsed: {$a}';
$string['notif_reminder_body'] = 'Your certification "{$a->name}" will expire on {$a->expiry}. Please complete the required activities to renew it.';
$string['notif_reminder_subject'] = 'Certification expiring soon: {$a->name} ({$a->days} days)';
$string['notif_window_body'] = 'The renewal window for your certification "{$a->name}" is now open. It will expire on {$a->expiry}.';
$string['notif_window_subject'] = 'Renewal window open: {$a}';
$string['notrackableactivities'] = 'No trackable activities found in this course.';
$string['orientation'] = 'Page orientation';
$string['pathcourses'] = 'Courses in this certification path';
$string['pluginname'] = 'Certification Manager';
$string['portrait'] = 'Portrait';
$string['privacy:metadata:history'] = 'Audit trail of certification state changes';
$string['privacy:metadata:notiflog'] = 'Log of certification notifications sent to the user';
$string['privacy:metadata:notiftype'] = 'The type of notification sent';
$string['privacy:metadata:reason'] = 'The reason recorded for the state change';
$string['privacy:metadata:state'] = 'Current certification status per user';
$string['privacy:metadata:status'] = 'The certification status';
$string['privacy:metadata:timecertified'] = 'When the user was certified';
$string['privacy:metadata:timecreated'] = 'When the record was created';
$string['privacy:metadata:timeexpires'] = 'When the certification expires';
$string['privacy:metadata:timesent'] = 'When the notification was sent';
$string['privacy:metadata:userid'] = 'The ID of the user';
$string['privacy:path:history'] = 'Certification history';
$string['profilefield'] = 'Profile field';
$string['requiredactivities'] = 'Required activities for automatic award';
$string['requiredactivities_help'] = 'Select which activities must be completed for automatic certificate award. Students must complete at least the minimum number specified above.';
$string['resettodefault'] = 'Reset to default layout';
$string['saveastemplate'] = 'Save as template';
$string['savecertificatedesign'] = 'Save Certificate Design';
$string['selectusers'] = 'Select users';
$string['showqrcode'] = 'Show QR code on certificate';
$string['signatureimage'] = 'Signature image';
$string['signatureline'] = 'Signature line text';
$string['statuscertified'] = 'Certified';
$string['statusexpired'] = 'Expired';
$string['statusexpiring'] = 'Expiring Soon';
$string['statusinprogress'] = 'In Progress';
$string['statuslapsed'] = 'Lapsed';
$string['taskevaluatestates'] = 'Evaluate certification state transitions';
$string['taskprocessnotifications'] = 'Send certification expiry reminders';
$string['tasksyncrules'] = 'Synchronise certification assignment rules';
$string['templatedeleted'] = 'Template deleted';
$string['templatename'] = 'Template name';
$string['templateshared'] = 'Share with all teachers';
$string['text_content'] = 'Text content';
$string['textfontsize'] = 'Body font size (points)';
$string['titlefontsize'] = 'Title font size (points)';
$string['validityperiod'] = 'Validity Period (days)';
$string['validityperiod_help'] = 'The default number of days a certificate is valid. Used when "Use Activity Default" is selected during award, or for automatic certification.

Set to 0 for no expiration by default.';
$string['verifyheading'] = 'Certificate verification';
$string['verifytitle'] = 'Verify certificate';
$string['windowperiod'] = 'Recertification Window (days)';
$string['windowperiod_help'] = 'Number of days before a certificate expires when the recertification window opens. For example, if validity is 365 days and window is 60 days, the window opens 60 days before expiry.';
