# Changelog — Certification Manager

## 1.0.0 (2026-07-21)

**Complete redesign around a plugin-style element system** (inspired by mod_customcert).

### New designer

- Fully element-based certificate builder — everything on the page is an "element"
  (text, image, background, student name, course name, date, QR, verification code,
  signature line, page border, certification name).
- **Add, remove, drag and resize** any element live on a canvas that matches the
  real page aspect ratio. Drag from the middle to reposition; drag the bottom-right
  corner handle to resize. Position and size persist automatically via AJAX.
- Per-element edit page with type-specific fields plus common style controls
  (font, size, colour, alignment) and exact numeric X/Y/W/H.
- **Reset to defaults** button seeds a professional starter layout in one click.

### Auto-populated fields (no more hand-typing)

- **Student name** — pulled from the user record on generation.
- **Course name** — pulled from the enrolling course.
- **Certification name** — the activity name.
- **Award date / expiry date** — from the certification state.
- **Verification code** — random 12-char code generated once per user, stable across
  re-generations, embedded in the QR link.

### Text placeholders

Text elements support inline placeholders: `{fullname}`, `{firstname}`, `{lastname}`,
`{coursename}`, `{certificatename}`, `{date}`, `{expiredate}`, `{code}`, `{site}`.

### QR verification

- QR codes now encode a public verification URL, not a hash.
- New `verify.php` page shows a "VALID" / "EXPIRED" banner and the recipient/course
  when a code is scanned or pasted.

### Images (finally fixed for good)

- Each image element has its own file area; no more shared background/logo/signature
  areas, no fileid saving quirks, no NULL ids in the DB.
- Correct MIME/extension detection into a temp file with a real extension —
  PNG/JPEG/GIF all render reliably.

### Data model

- New `certmanager_elements` table holds all elements with position (mm), size,
  style, sort order and a JSON `data` blob for type-specific config.
- `certmanager` gains `orientation`, `pagewidth`, `pageheight` for A4 landscape /
  portrait or custom sizes.
- `certmanager_certificates` gains a `code` column for verification.
- Legacy `certmanager_cert_design` and `certmanager_templates` tables are dropped
  in upgrade — clean slate.

### Removed

- `certificate_design.php`, `certificate_design.js`, `certificate_design.css` — all
  replaced by the element-based designer.
- All hand-tuned positional defaults with overlap bugs.
