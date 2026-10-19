# SmartCity Attendance

Event registration, QR Code attendance, and automatic certificates, with a Smart City / Smart Campus theme.
Built with **PHP 8.1+ (OOP, no framework)** and **MySQL/MariaDB** (or SQLite for testing).

**Live Web:** [attd-demo.sinarputra.web.id/smartcity-attendance/public](https://smartcity-unmul.web.id/)

**User manual (Indonesian, PDF, with screenshots):** [Manual-Book-SmartCity-Attendance.pdf](Manual-Book-SmartCity-Attendance.pdf)

---

## Contents

- [Features](#features)
- [User roles](#user-roles)
- [How it works](#how-it-works)
- [Requirements](#requirements)
- [Installation](#installation)
- [Upgrading an existing installation](#upgrading-an-existing-installation)
- [Certificates](#certificates)
- [Security](#security)
- [Project structure](#project-structure)
- [OOP concepts](#oop-concepts)
- [Main routes](#main-routes)
- [Languages](#languages)
- [Customization](#customization)
- [Third-party libraries](#third-party-libraries)

## Features

- **Online registration** without an account: six required fields, one ID number per event.
- **Quota and closing time**: registration closes automatically when the participant quota is reached or the closing date and time pass, and reopens if the quota is raised.
- **Personal QR ticket** that participants can download as a PNG or reopen from a ticket link.
- **Camera QR scanner** for staff, with a fallback to ticket-code entry and name/ID search when the camera is unavailable.
- **Live dashboard**: present, not yet present, attendance rate, and check-ins per 15 minutes.
- **Manual status changes** by the Super Admin, each with a required reason and kept in a change history.
- **Reports and export** to Excel (.xlsx) and CSV.
- **Three admin roles**: Super Admin, Event Admin (per-event permissions), and Attendance Admin (scanning staff).
- **Automatic certificates**: template editor, official assets, variables, approved number patterns, eligibility rules, bulk generation, publishing, revoking, reissuing, and public QR verification.
- **Audit log** of every important action and every denied request.
- **Grouped choices for Indonesia**: 38 professions in 5 groups and 126 study programs in 11 fields, plus “Other (type your own)”.
- **Responsive** layout for phones and desktops, in **Indonesian and English**.

## User roles

| Role | Login | Access |
|---|---|---|
| **Participant** | No | View events, register, get and download the QR ticket, find and download published certificates |
| **Attendance Admin** | Yes | Scan QR codes, save attendance, enter codes or search manually, view participants of the assigned event |
| **Event Admin** | Yes | Manage only the events a Super Admin assigned, with only the permissions ticked for each assignment |
| **Super Admin** | Yes | Everything: events, admin accounts and assignments, participants, manual status changes, reports, certificates, approvals, and the full audit log |

Participant statuses:

- **Not yet verified** (*Belum diverifikasi*): the status right after registration.
- **Present** (*Hadir*): set by a QR scan, or manually by the Super Admin.
- **Absent** (*Tidak Hadir*): set by the Super Admin. This status is final, so the QR can no longer be used.

## How it works

1. The Super Admin creates an event, opens registration, and shares the registration link.
2. Participants register and receive a personal QR ticket.
3. On the event day the admin opens attendance, and staff scan QR codes at `/petugas/scan`.
4. After the event, attendance is closed, absent participants are marked, and the summary is exported.
5. Certificates are generated for eligible participants and published. Participants download them at `/sertifikat`, and anyone can verify one by scanning its QR.

Step-by-step instructions with screenshots for every role are in the [user manual](Manual-Book-SmartCity-Attendance.pdf).

## Requirements

- PHP **8.1 or newer** with `pdo_mysql` (or `pdo_sqlite`) and `mbstring`
- `gd` and `fileinfo` for certificates; `zip` for Excel export and certificate ZIP downloads (CSV works without it)
- MySQL 5.7+ / MySQL 8 / MariaDB 10.3+ (or SQLite 3 for testing)
- Apache with `mod_rewrite`, or Nginx
- **HTTPS** for the scanner camera. Browsers only allow the camera on HTTPS or `localhost`; without it, the scanner falls back to code entry and manual search.

## Installation

### 1. Upload the files

Put the project on the server, for example in `/var/www/smartcity-attendance`.

Point the domain's document root to the `public/` folder. On shared hosting (cPanel) you can put the whole folder in `public_html` instead: the root `.htaccess` forwards requests to `public/` and blocks `app/`, `bin/`, `config/`, `database/`, `storage/` and `vendor/`.

### 2. Create the database

```sql
CREATE DATABASE smartcity_attendance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'smartcity'@'localhost' IDENTIFIED BY 'strong-password';
GRANT ALL PRIVILEGES ON smartcity_attendance.* TO 'smartcity'@'localhost';
```

The installer creates the tables. To create them manually, import `database/schema.mysql.sql`.

### 3. Configure

```bash
cp config/config.example.php config/config.php
```

Edit `config/config.php`:

| Key | Description |
|---|---|
| `db.*` | driver, host, database, username, password |
| `timezone` | default `Asia/Makassar` (WITA) |
| `locale` | default language: `'id'` or `'en'` |
| `debug` | **must be `false`** in production |
| `pretty_urls` | set to `false` if URL rewriting is unavailable (URLs become `/index.php/...`) |
| `base_url` | leave empty for auto-detection; set it behind a reverse proxy, since certificate QR codes use it |
| `cert` | upload size limit, bulk-generation batch size, public certificate rate limits |

For a quick test without MySQL, use `'driver' => 'sqlite'`; the file is created at `storage/database.sqlite`.

### 4. Folder permissions

```bash
chown -R www-data:www-data storage
chmod -R 775 storage
```

### 5. Run the installer

Open `https://your-domain/install`:

1. Every server check must show ✓.
2. Create the **Super Admin** account (password: at least 10 characters with letters and numbers).
3. Optionally tick **demo data**. It adds two events (196 participants), two Attendance Admins, one Event Admin (`event.demo`), and a ready certificate setup with sample certificates. Demo passwords are shown once on the final screen.

`/install` locks itself (404) once the first Super Admin exists.

The demo never creates a signature or stamp, and every demo certificate is marked “SERTIFIKAT DEMO · TIDAK BERLAKU SEBAGAI DOKUMEN RESMI”.

### Run it locally

```bash
cp config/config.example.php config/config.php   # set driver to 'sqlite'
php -S localhost:8000 -t public public/index.php
```

Open `http://localhost:8000/install`. The camera works on `localhost` without HTTPS.

## Upgrading an existing installation

Updates only **add** tables and never drop, rename or empty existing ones, so events, participants, accounts and attendance stay intact.

1. Back up the database and the `storage/` folder.
2. Upload the new files, keeping your `config/config.php` and `storage/`.
3. Run the migration with `php bin/migrate.php --yes`, or sign in as Super Admin and press **Run migration** on the dashboard banner. Running it twice is safe.

To add the Event Admin and certificate demo to an installed site without touching existing data:

```bash
sudo -u www-data php bin/seed-demo.php --url=https://your-domain --yes
```

## Certificates

Menu **Certificates** (Super Admin, and Event Admins with a certificate permission):

1. **Official assets**: upload logos, signatures and stamps. Signatures and stamps from an Event Admin stay unusable until a Super Admin approves them. The app never draws a signature, stamp or logo itself.
2. **Variables**: global values (university, faculty, signer, letter code) set by the Super Admin, per-event overrides, and custom variables. Built-in placeholders such as `{PARTICIPANT_NAME}`, `{EVENT_NAME}`, `{CERTIFICATE_NUMBER}` and `{VERIFICATION_URL}` fill in automatically. Templates only substitute text; nothing in a template is executed.
3. **Number pattern**: for example `{SEQUENCE_PADDED}/{EVENT_CODE}/{FACULTY_LETTER_CODE}/{ROMAN_MONTH}/{YEAR}`. A new pattern starts as a Draft and needs Super Admin approval. Use the format confirmed by the faculty office. Sequence numbers are taken in a database transaction and never reused.
4. **Template**: background (PNG/JPG or the first page of a PDF), drag-and-drop elements, watermarked **PDF preview**, then **Activate**. Issued certificates keep the template version they were made with.
   - Start from the *Academic* layout or the *Modern dark* layout (navy-teal, Sora, 3 logo slots, 3 signer blocks, QR).
   - Fonts: DejaVu Sans/Serif/Condensed, Sora (Light to ExtraBold), Montserrat, Cormorant Garamond, Great Vibes and Pinyon Script. All are embedded in the PDF; text with characters a font lacks falls back to DejaVu Sans.
   - Text options: color picker or hex code, opacity, rotation, underline, uppercase, letter and line spacing, shrink-to-fit.
   - Shapes: rectangle/band with rounded corners, circle, line, triangle, right triangle, diamond, parallelogram, trapezoid and hexagon, with fill, two-color gradient, border, dash, opacity, rotation and mirroring. A full-page color background can be added and locked.
   - **Signer block** adds title, official signature slot, name and NIP from `{SIGNER_*}`, `{SIGNER2_*}` or `{SIGNER3_*}`. Up to 3 signatures per template, each with its own approved signature asset.
5. **Eligibility**: Present only (default), everyone not marked Absent, or everyone (Super Admin only).
6. **Generate**: all eligible participants, a selection, or one participant. Bulk generation runs in resumable batches, and a participant never gets two active certificates.
7. **Publish**: certificates start as *Generated* (admins only). Published ones can be found at `/sertifikat`; downloading requires the last 4 characters of the ID number or the ticket code.
8. **Revoke / reissue**: revoking needs a reason and shows the certificate as invalid on `/v/{token}`. Reissuing creates a new certificate with a new number, and both stay in the history.

Generated PDFs are stored with a SHA-256 hash that is checked on every download. The certificate QR is separate from the attendance QR.

## Security

- Passwords hashed with `password_hash`; session ID regenerated after login; temporary passwords must be changed at first login.
- CSRF tokens on every POST form and on the scanner API.
- Rate limits: 5 logins per 15 minutes, 30 registrations per hour per IP, plus an anti-bot honeypot and limits on public certificate search and download checks.
- Deny-by-default access, checked on the server for every request. The event of every object is looked up in the database, so changing an ID in a URL, form or API body never reaches another event.
- Atomic check-in (`UPDATE ... WHERE status = 'belum'`), so duplicate or simultaneous scans never create duplicate records.
- PDO prepared statements everywhere; all HTML output escaped; CSV export protected against formula injection.
- `HttpOnly`, `SameSite=Lax` and (on HTTPS) `Secure` session cookies; `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy` and `Permissions-Policy` headers.
- Uploads limited in size, checked by real content (not the extension), re-encoded, and stored under random names outside `public/`.
- WhatsApp numbers are never sent to the scanner; public certificate search masks institutions.
- Important actions and denied requests go to the audit log, without passwords or tokens.

## Project structure

```
app/
  Core/          Router, Database (PDO), Auth, View, RateLimiter, Migrator
  Models/        Event, Participant, ParticipantOptions, User, StatusHistory, Assignment, AuditLog, Cert*
  Services/      AttendanceService, Validator, Exporter (CSV/XLSX), Access, DemoSeeder, CertDemoSeeder
  Services/Cert/ Placeholders, Renderer (PDF), Issuer, Upload
  Controllers/   Public, Auth, Staff, Api, Dashboard, Event, Account, Participant, Report,
                 Settings, Install, Audit, Certificate, CertPublic
  Lang/en/       English translations
  Views/         layouts, partials, public, staff and admin pages
  bootstrap.php  autoload, config, session, language, security headers
  helpers.php    url, e, csrf, t, date formatting, badges
bin/             migrate.php, seed-demo.php (command line only)
config/          config.example.php -> copy to config.php
database/        schema.mysql.sql, schema.sqlite.sql, migrations/
public/          index.php (front controller), assets (css, js, vendor, img)
storage/         SQLite file (optional), logs, certificate assets, generated PDFs
vendor/          TCPDF and FPDI (bundled, no Composer needed)
```

## OOP concepts

- **Classes and namespaces** under `App\...` with PSR-4 style autoloading.
- **Inheritance**: every controller extends `Controller` (rendering, current event, password change).
- **Encapsulation**: database access only through the `Database` class and the models.
- **Separation of concerns**: models hold data, services hold business rules, controllers handle HTTP, views handle presentation.

## Main routes

| URL | Description |
|---|---|
| `/` | Public home: events open for registration |
| `/e/{slug}` | Event page |
| `/e/{slug}/daftar` | Registration form |
| `/t/{code}` | Participant QR ticket (the attendance QR contains this URL) |
| `/sertifikat` | Find and download certificates |
| `/v/{token}` | Certificate verification (the certificate QR contains this URL) |
| `/masuk` | Sign in for all admins |
| `/petugas`, `/petugas/scan` | Attendance Admin home and scanner |
| `/admin` | Super Admin / Event Admin dashboard |
| `/admin/sertifikat` | Certificates module |
| `/admin/audit` | Audit log |
| `/install` | First-time installation (locked afterwards) |

## Languages

Every page has an **ID / EN** switch, stored in a cookie for one year; the default comes from `locale` in the config. Source text is Indonesian in the code (`t('Simpan data')`), and English translations live in `app/Lang/en/`. User-entered data is not translated, and certificate PDFs are always in Indonesian.

## Customization

- **Profession and study program choices**: `PROFESSIONS` and `FIELDS` in `app/Models/ParticipantOptions.php` (grouped for `<optgroup>`), with English labels in `app/Lang/en/options.php`. Keep old values in the list so existing participant data stays valid.
- **Colors and look**: CSS variables at the top of `public/assets/css/app.css`.
- **Validation rules** (ID length, phone format): `app/Services/Validator.php`.

## Third-party libraries

Bundled, so the app does not depend on a CDN:

- [html5-qrcode](https://github.com/mebjas/html5-qrcode) 2.3.8 (Apache-2.0): camera scanner
- [qrcodejs](https://github.com/davidshimjs/qrcodejs) 1.0.0 (MIT): ticket QR image
- [TCPDF](https://github.com/tecnickcom/TCPDF) 6.11.4 (LGPL-3.0-or-later): certificate PDFs and certificate QR
- Certificate fonts in `resources/fonts` (PDF) and `public/assets/fonts/cert` (editor preview): Sora, Montserrat, Cormorant Garamond, Great Vibes and Pinyon Script, all under the SIL Open Font License 1.1 (license texts included), plus DejaVu bundled with TCPDF
- [FPDI](https://github.com/Setasign/FPDI) 2.6.9 (MIT): imports a PDF page as the certificate background

The Sora and Inter fonts load from Google Fonts; without internet access the pages fall back to system fonts.
