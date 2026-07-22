# Certification Manager (mod_certmanager)

**v1.0.0** — Professional certification lifecycle management for Moodle with element-based certificate designer.

Teachers add this as an activity to define course-specific certifications with automatic PDF generation and complete lifecycle tracking.

**Author:** Vinit Mepani  
**License:** GNU GPL v3 or later  
**Repository:** https://github.com/vinit-mepani/moodle-mod_certmanager

## Latest Updates (v1.0.0)

### Complete Redesign: Element-Based Certificate Designer
- Fully element-based certificate builder — every component (text, images, names, dates, QR codes) is an independent "element"
- **Live drag-and-drop designer** — Reposition elements by dragging from the center, resize using the bottom-right corner handle
- **Real-time canvas preview** — Matches actual PDF output exactly with proper page aspect ratio
- **Per-element controls** — Font, size, color, alignment, and precise X/Y/W/H positioning for each element
- **Reset to defaults** — Professional starter layout with one click

### Auto-Populated Certificate Fields
- **Student name** — Automatically pulled from user profile
- **Course name** — Automatically pulled from enrolling course
- **Certification name** — Activity display name
- **Award date / Expiry date** — Calculated from certification state
- **Verification code** — Random 12-character code, stable across regenerations

### Text Placeholders
Text elements support inline placeholders for dynamic content:
- `{fullname}`, `{firstname}`, `{lastname}` — Student information
- `{coursename}`, `{certificatename}` — Course and activity names
- `{date}`, `{expiredate}` — Certification dates
- `{code}` — Verification code
- `{site}` — Moodle site name

### QR Code Verification System
- QR codes encode a public verification URL (not a hash)
- New `verify.php` page displays certificate validity status
- Shows recipient and course information when verified
- Users can scan or paste verification codes

### Improved Image Handling
- Each image element has its own file area — no shared background/logo conflicts
- Proper MIME/extension detection with real file extensions
- Reliable rendering for PNG, JPEG, and GIF formats
- No more NULL file IDs or persistence quirks

## Features

✅ **Multi-activity certification paths** — Require students to complete multiple activities (assign, quiz, SCORM, etc.)  
✅ **Element-based PDF certificates** — Drag-and-drop designer with full customization  
✅ **Auto-populated fields** — Student names, dates, and codes populate automatically  
✅ **QR code verification** — Public verification page for certificate validation  
✅ **Lifecycle management** — Track certified → expiring → expired → lapsed states  
✅ **Renewal windows** — Students renew by completing activities again within the window  
✅ **Grace periods** — Extend time after expiry before marking lapsed  
✅ **Automatic notifications** — Email on award, renewal, reminders, expiry, and lapse  
✅ **Fully customizable** — Teachers design templates with flexible element positioning  

## Quick Start

1. **Install** — Extract to `mod/certmanager/` and visit *Site Administration → Notifications*
2. **Add activity** — Course → Add activity → *Certification Manager*
3. **Configure** — Select required activities, set validity period (e.g., 365 days)
4. **Design certificate** — Click "Certificate design" to use the element-based designer
5. **Learners auto-certify** — When they complete all activities, certificates auto-generate

## Requirements

- **Moodle:** 4.5 LTS (405) or 5.2+ (502)
- **PHP:** 7.4+
- **Database:** Any Moodle-supported database (MySQL, PostgreSQL, etc.)
- **Dependencies:** None (uses core Moodle components including TCPDF for PDF generation)

## Installation

1. Download the plugin or clone from GitHub
2. Extract to `moodle/mod/certmanager/`
3. Navigate to **Site Administration → Notifications**
4. Follow the installation wizard to create database tables
5. Configure plugin settings as needed

## Configuration

### Site-wide Settings

Navigate to **Site Administration → Plugins → Activity modules → Certification Manager** to configure:
- Default validity periods and renewal windows
- Notification preferences and schedules
- Global certificate settings

### Activity Settings

When adding or editing a Certification Manager activity:

- **Activity name** — Display name for learners
- **Description** — Activity introduction text
- **Validity period** — How long (in days) the certificate remains valid
- **Renewal window** — Days during which learners can renew certification
- **Grace period** — Days after expiry before marking certification as lapsed
- **Certificate generation** — Enable/disable automatic PDF generation
- **Page orientation** — Portrait or landscape layout
- **Custom page size** — Standard (A4) or custom dimensions

### Certificate Design

1. Add the Certification Manager activity to your course
2. Go to the activity → **"Certificate design"**
3. **Add elements** using the element menu:
   - Text (static or with placeholders)
   - Student name, course name, certification name
   - Award date, expiry date
   - QR code, verification code
   - Images (logos, signatures, backgrounds)
   - Page border
4. **Drag elements** to position them on the canvas
5. **Resize elements** using the bottom-right corner handle
6. **Edit element properties** — Click an element to access type-specific options
7. **Save your design** — Changes persist automatically via AJAX

## Usage Guide

### For Teachers

- Add multiple activities as requirements for certification
- Set validity and renewal periods
- Design professional-looking certificates using the drag-and-drop designer
- Award certificates manually or automatically upon completion
- Monitor certification status for all learners
- Send notifications and reminders
- Track lifecycle transitions (awarded → expiring → expired → lapsed)

### For Learners

- Complete required activities to earn certification
- Track certification status (in progress, certified, expiring, expired)
- Download certificates as PDF files
- Verify certificates by scanning QR codes
- Renew certification during renewal windows
- Receive email notifications about certification status

## Features in Detail

### Element-Based Certificate Designer

Everything on a certificate is an "element" — add, remove, drag, and resize independently:

- **Text elements** — Static text with font, size, and color controls
- **Auto-populated fields** — Student name, course name, certification name, dates
- **QR code element** — Generates scannable verification URL
- **Verification code element** — Displays unique 12-character code
- **Image elements** — Institution logos, backgrounds, signatures (PNG, JPEG, GIF)
- **Page border element** — Professional framing option

**Designer features:**
- Live canvas preview matching PDF output exactly
- Drag-and-drop positioning with visual feedback
- Corner-handle resizing with precise dimensions
- Per-element edit page with numeric X/Y/W/H controls
- "Reset to defaults" for quick professional layouts
- AJAX auto-save for all changes

### Supported Element Types

| Element | Purpose | Options |
|---------|---------|---------|
| **Text** | Static content with placeholders | Font, size, color, alignment |
| **Student Name** | Auto-populated student information | Font, size, color, alignment |
| **Course Name** | Auto-populated course title | Font, size, color, alignment |
| **Certification Name** | Activity name | Font, size, color, alignment |
| **Award Date** | Auto-calculated certification date | Font, size, color, format |
| **Expiry Date** | Auto-calculated expiration date | Font, size, color, format |
| **QR Code** | Scannable verification URL | Size, error correction level |
| **Verification Code** | Unique identifier (12 chars) | Font, size, color |
| **Image** | Logo, signature, background | Size, opacity, file management |
| **Page Border** | Decorative frame | Color, width, style |

### Notification System

Automated notifications keep learners informed:

- **Award notification** — Sent when learner is certified
- **Renewal window notification** — When renewal window opens
- **Expiry reminder** — Configurable days before expiry
- **Expiration notification** — When certification expires
- **Lapse notification** — When certification moves to lapsed state

### Certification Lifecycle States

Track every stage of certification:

- **In Progress** — Learner is working toward meeting all requirements
- **Certified** — Learner meets all requirements; certificate is valid and can be downloaded
- **Expiring Soon** — Certificate will expire within the configurable warning window
- **Expired** — Certificate validity period has ended; renewal window may be open
- **Lapsed** — Certificate expired and grace period has ended; manual reset required

## Data Model

### New Tables and Structures

- **`certmanager_elements`** — Stores all certificate elements with position (mm), size, style, sort order, and JSON `data` blob for type-specific configuration
- **`certmanager`** — Enhanced with `orientation`, `pagewidth`, `pageheight` for flexible page layouts
- **`certmanager_certificates`** — Now includes `code` column for verification

### Legacy Cleanup

The following legacy tables are removed during upgrade to v1.0.0:
- `certmanager_cert_design`
- `certmanager_templates`

This ensures a clean, modern data structure without deprecated fields.

## Privacy & Data Protection

This plugin complies with Moodle's Privacy API:

- Declares all user data it stores
- Provides data export functionality for GDPR compliance
- Supports complete user data deletion
- Includes audit trails of certification changes

## Support & Contribution

For bug reports, feature requests, or contributions:

- **GitHub Issues:** [Certification Manager Repository](https://github.com/vinit-mepani/moodle-mod_certmanager/issues)

## FAQ

**Q: Can I customize the certificate template?**  
A: Yes! The plugin includes a drag-and-drop element-based designer where you can customize every element's position, size, font, and color independently.

**Q: Can I require multiple activities for certification?**  
A: Yes! Create multiple activity requirements and configure which ones must be completed to earn the certificate.

**Q: Are certificates verified?**  
A: Yes! Each certificate includes a QR code and verification code that can be scanned at the public verification page to confirm validity.

**Q: Can I use custom page sizes?**  
A: Yes! Configure portrait/landscape orientation and standard (A4) or custom page dimensions in activity settings.

**Q: How do text placeholders work?**  
A: Add placeholders like `{fullname}`, `{coursename}`, or `{date}` in text elements — they automatically populate when certificates are generated.

**Q: Does it support multiple languages?**  
A: The plugin itself is fully internationalized. Certificate content is determined by what you add to the design.

## Version History

See [CHANGELOG.md](CHANGELOG.md) for detailed version history and release notes.

## License

GNU GPL v3 or later — see [LICENSE](LICENSE) file for full text.

**Copyright © 2026 Vinit Mepani**

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
