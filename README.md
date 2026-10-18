# SmartCity Attendance

An event registration and QR Code attendance website with a Smart City / Smart Campus theme.
Built with PHP 8.1+ (OOP, no framework) and MySQL/MariaDB or SQLite.

**Live site:** [https://smartcity-unmul.web.id/](https://smartcity-unmul.web.id/)

## User roles

| Role | Login | Access |
|---|---|---|
| **Participant** | No | View events, register (6 required fields), receive a QR Code, download the QR (PNG) |
| **Attendance Admin** | Yes | Scan QR codes, save attendance, enter codes or search manually, view the participant list of the assigned event |
| **Event Admin** | Yes | Manages only the events a Super Admin assigned to them, and only with the permissions ticked for each assignment (edit event, participants, status changes, attendance, reports, certificates, publish, revoke, audit log) |
| **Super Admin** | Yes | Everything: events, admin accounts and assignments, participants, manual Present/Absent status changes (with confirmation and a reason), change history, reports, CSV/Excel export, approving signatures, stamps and number patterns |

Participant statuses:

- **Not yet verified**: the initial status after registration.
- **Present**: recorded by a QR scan, or set manually by the Super Admin.
- **Absent**: set by the Super Admin. This status is final, so the participant's QR can no longer be used.

## Server requirements

- PHP **8.1 or newer** with the `pdo_mysql` (or `pdo_sqlite`) and `mbstring` extensions
- `gd` and `fileinfo` extensions: required for certificates (uploads are checked and re-encoded with GD)
- `zip` extension: needed for Excel (.xlsx) export and for downloading certificates as a ZIP. CSV export works without it.
- MySQL 5.7+ / MariaDB 10.3+ (or SQLite 3 for testing)
- Apache with `mod_rewrite`, or Nginx
- **HTTPS is required** for the scanner camera. Browsers only allow camera access on HTTPS or `localhost`. Without HTTPS, the scanner automatically falls back to code entry and manual search.

## Deployment

### 1. Upload the files

Extract the ZIP on the server, for example to `/var/www/smartcity-attendance`.

**Recommended:** point the domain's *document root* to the `public/` folder.
If your hosting does not allow this (shared hosting / cPanel), put the whole folder in `public_html`. The `.htaccess` file in the project root forwards requests to `public/` and blocks access to `app/`, `config/`, `database/` and `storage/`.

### 2. Create the database (MySQL/MariaDB)

```sql
CREATE DATABASE smartcity_attendance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'smartcity'@'localhost' IDENTIFIED BY 'strong-password';
GRANT ALL PRIVILEGES ON smartcity_attendance.* TO 'smartcity'@'localhost';
```

You do not need to create the tables yourself; the installer in step 5 creates them.
To create them manually instead, import `database/schema.mysql.sql`.

### 3. Configure

```bash
cp config/config.example.php config/config.php
```

Then edit `config/config.php`:

- `db.driver`, `db.host`, `db.database`, `db.username`, `db.password`
- `timezone`: defaults to `Asia/Makassar` (WITA)
- `locale`: default language, `'id'` (Indonesian) or `'en'` (English)
- `debug`: **must be `false`** on a production server
- `pretty_urls`: set to `false` if the server does not support URL rewriting (URLs become `/index.php/...`)
- `base_url`: leave empty for automatic detection. Fill it in only if the app runs behind a reverse proxy. Certificate QR codes use this address, so set it if the detected address would be wrong.
- `cert`: upload size limit, batch size for bulk generation, and rate limits for the public certificate pages

For a quick test without MySQL, use `'driver' => 'sqlite'`. The database file is created at `storage/database.sqlite`.

### 4. Folder permissions

The web server must be able to write to `storage/`:

```bash
chown -R www-data:www-data storage
chmod -R 775 storage
```

### 5. Run the installer

Open `https://your-domain/install` in a browser:

1. Review the server checks; every item must show ✓.
2. Create the **Super Admin** account.
3. Optional: tick **demo data** for testing. It adds:
   - the main demo event (156 participants, 98 present) with 2 Attendance Admins;
   - a finished demo seminar (40 participants) with a ready certificate setup: a demo logo and background, demo variable values, an approved `DEMO/...` number pattern, and 31 sample certificates (published, generated, revoked and one reissue);
   - an Event Admin account `event.demo` assigned to both events.

   The demo never creates a signature or stamp, and it leaves the global official variables empty. Every demo certificate is marked "SERTIFIKAT DEMO · TIDAK BERLAKU SEBAGAI DOKUMEN RESMI".

Once the first Super Admin is created, `/install` locks itself (404).

### 6. Start using it

1. Sign in at `/masuk` as the Super Admin.
2. **Events → Create event**, then set the event status to **Active** and **Open registration**.
3. Copy the **registration link** and share it with participants.
4. **Admin Accounts → Create account** for the scanning staff (Attendance Admin, one event), or for an organiser (Event Admin, then add one or more event assignments and tick the permissions).
5. On the event day, open **Event details → Open attendance**. Staff scan QR codes at `/petugas/scan`.
6. When it is over, **Close attendance**, mark participants who did not come as Absent, then export the summary from **Reports**.

### Upgrading an existing installation (Event Admin and certificates)

The update only **adds** tables (`schema_migrations`, `event_admin_assignments`, `audit_logs`, `cert_*`). It does not drop, rename or empty any existing table, and existing events, participants, accounts and attendance stay as they are.

1. **Back up the database** and the `storage/` folder.
2. Upload the new files, keeping your `config/config.php` and `storage/`. Copy the new `'cert' => [...]` block from `config/config.example.php` into your `config.php` (optional; defaults are used when it is missing).
3. Run the migration, either way:
   - from the command line: `php bin/migrate.php --yes`
   - or sign in as Super Admin. The dashboard shows a **Run migration** banner while migrations are pending.
4. Running the migration again is safe; applied files are recorded in `schema_migrations` and skipped.

Fresh installs through `/install` run the migrations automatically.

To add the Event Admin and certificate demo to a site that is already installed, without touching existing data, run this as the web server user so the generated files stay writable:

```bash
sudo -u www-data php bin/seed-demo.php --url=https://your-domain --yes
```

`--url` sets the address in the demo QR codes. You can leave it out when `base_url` is set in the config. Running the script again does nothing once the demo exists.

### Example Nginx configuration

See `nginx.conf.example`. The key points: `root` must point to the `public/` folder, and `try_files $uri $uri/ /index.php?$query_string;`.

### Running it on your own computer

```bash
cp config/config.example.php config/config.php   # change driver to 'sqlite'
php -S localhost:8000 -t public public/index.php
```

Open `http://localhost:8000/install`. The camera works on `localhost` even without HTTPS.

## Certificates

Menu **Certificates** (Super Admin, and Event Admins with the certificate permission):

1. **Official assets**: upload logos, the official's signature and the stamp. Signatures and stamps uploaded by an Event Admin stay unusable until a Super Admin approves them. The app never draws a signature, stamp or logo itself; empty slots are shown as dashed boxes in the preview and block activation.
2. **Variables**: fill in global values (university, faculty, signer name and title, letter code...) as Super Admin, override them per event, or add custom variables. Built-in placeholders such as `{PARTICIPANT_NAME}`, `{EVENT_NAME}`, `{CERTIFICATE_NUMBER}` and `{VERIFICATION_URL}` are filled automatically. Templates only do text substitution; nothing in a template is executed.
3. **Number pattern**: for example `{SEQUENCE_PADDED}/{EVENT_CODE}/{FACULTY_LETTER_CODE}/{ROMAN_MONTH}/{YEAR}`. A new pattern is always a **Draft** and must be approved by a Super Admin. Use the official format confirmed by the faculty/administration office; the app does not invent one. Sequence numbers are taken inside a database transaction and are never reused, even when generating in parallel.
4. **Template**: upload the background design (PNG/JPG, or the first page of a PDF), drag the elements into place, check the result with **PDF preview** (watermarked, consumes no number), then **Activate**. Activation stores a version snapshot; certificates already issued keep the version they were made with.
5. **Eligibility**: Present only (default), everyone except Absent, or everyone (Super Admin only).
6. **Generate**: for all eligible participants, a selection, or one participant. Bulk generation runs in small batches from the browser and can be resumed. A participant never gets two active certificates.
7. **Publish**: certificates start as *Generated* (admin only). After publishing, participants find them at `/sertifikat` by event and name, and download after confirming the last 4 characters of their ID number or their ticket code. Each certificate has its own QR pointing to `/v/{token}`, separate from the attendance QR.
8. **Revoke / reissue**: revoking needs a reason and marks the certificate invalid on the verification page. Reissuing revokes the old one and creates a new certificate with a new number; both stay in the history.

Generated PDFs are stored in `storage/certificates/` with a SHA-256 hash that is checked on every download.

## Security measures

- Passwords are stored with `password_hash` (bcrypt/argon depending on PHP), and the session ID is regenerated after login.
- CSRF tokens on every POST form and on the scanner API (`X-CSRF-Token` header).
- Login attempt limit (5 per 15 minutes) and registration limit (30 per hour per IP), plus an anti-bot honeypot.
- Role-based access control: Attendance Admins get a 403 on Super Admin pages and only see their assigned event.
- Atomic check-in (`UPDATE ... WHERE status = 'belum'`), so duplicate scans or two staff scanning at the same time never create duplicate records.
- Manual status changes run in a transaction and are recorded in `status_history` (old and new status, reason, who, when).
- All queries use PDO *prepared statements*, and all HTML output is escaped.
- Session cookies are `HttpOnly`, `SameSite=Lax`, and `Secure` on HTTPS. Security headers are sent: `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, and `Permissions-Policy` (camera allowed only for the site itself).
- CSV export is protected against *formula injection*.
- WhatsApp numbers are never sent to the scanner and are visible only to the Super Admin.
- New accounts and password resets use a temporary password that must be changed at first login.
- Event Admin access is deny-by-default and checked on the server for every request: the event of the object being opened (participant, template, certificate, job) is looked up in the database, so changing an ID in the URL, form or API body does not reach another event. Removing an assignment ends access immediately.
- Every denied request and every important change (assignments, events, status, templates, numbering, generate, publish, revoke, public downloads and failed checks) is written to the audit log, without passwords or tokens.
- Uploads are limited in size, checked by real content (MIME and image/PDF parsing, not the extension), re-encoded, and saved under random internal names outside `public/`.
- Public certificate search shows published certificates only, masks institutions, and is rate limited, as are failed download confirmations.

## Folder structure

```
app/
  Core/          Router, Database (PDO), Auth, View, RateLimiter, Migrator (runs database/migrations)
  Models/        Event, Participant, User, StatusHistory, Assignment, AuditLog, Cert* (assets, templates, variables, numbering, issuances)
  Services/      AttendanceService (attendance rules), Validator, Exporter (CSV/XLSX), DemoSeeder, Access (permissions per event)
  Services/Cert/ Placeholders, Renderer (PDF), Issuer (readiness, generate, publish, revoke), Upload
  Controllers/   Public, Auth, Staff, Api, Dashboard, Event, Account, Participant, Report, Settings, Install, Audit, Certificate, CertPublic
  Lang/en/       English translations
  Views/         layouts, partials, public, staff and super admin pages
  bootstrap.php  autoload, configuration, session, language, security headers
  helpers.php    helper functions (url, e, csrf, t, date formatting, status badges)
config/          config.example.php → copy to config.php
bin/             migrate.php (command-line migration)
database/        schema.mysql.sql, schema.sqlite.sql, migrations/ (additive updates)
public/          index.php (front controller), assets (css, js, vendor, img)
storage/         SQLite database (optional), error log, uploaded certificate assets, generated PDFs
vendor/          TCPDF and FPDI (bundled, no Composer needed)
```

OOP concepts used:

- **Classes and namespaces** `App\...` with PSR-4 style autoloading
- **Inheritance**: every controller extends `Controller` (rendering, current event, password change)
- **Encapsulation**: database access only through the `Database` class and the models
- **Separation of concerns**: models for data, services for business rules, controllers for the HTTP flow, views for presentation

## Main routes

| URL | Description |
|---|---|
| `/e/{slug}` | Public event page |
| `/e/{slug}/daftar` | Registration form |
| `/t/{code}` | Participant QR ticket (the QR contains this URL) |
| `/masuk` | Sign in for Attendance Admin / Super Admin |
| `/petugas`, `/petugas/scan` | Attendance Admin home and scanner |
| `/admin` | Super Admin / Event Admin dashboard |
| `/admin/sertifikat` | Certificates: templates, assets, variables, numbering, eligibility, generate, issued, history |
| `/admin/audit` | Audit log |
| `/sertifikat` | Public certificate search and download |
| `/v/{token}` | Public certificate verification (the certificate QR contains this URL) |
| `/install` | First-time installation (locked once done) |

## Third-party libraries

These libraries are bundled in `public/assets/vendor/`, so the app does not depend on a CDN:

- [html5-qrcode](https://github.com/mebjas/html5-qrcode) 2.3.8, Apache-2.0: camera scanner
- [qrcodejs](https://github.com/davidshimjs/qrcodejs) 1.0.0, MIT: generates the ticket QR image

Bundled in `vendor/` for certificates:

- [TCPDF](https://github.com/tecnickcom/TCPDF) 6.11.4, LGPL-3.0-or-later: PDF generation and the certificate QR. Only the DejaVu fonts are included (DejaVu font license in `vendor/tecnickcom/tcpdf/fonts/`).
- [FPDI](https://github.com/Setasign/FPDI) 2.6.9, MIT: imports the first page of a PDF background design

The Sora and Inter fonts load from Google Fonts. If the server cannot reach the internet, the pages fall back to system fonts.

## Two languages (Indonesian / English)

Every page has an **ID / EN** button at the top. The choice is stored in a cookie for one year, and the default language is set with `locale` in `config/config.php`.

- Source text is written in Indonesian in the code, for example `t('Simpan data')`.
- English translations live in `app/Lang/en/`: `general.php` (public pages, sign in, shared text), `staff.php` (Attendance Admin), `super.php` (Super Admin), `cert.php` (Event Admin, audit log and certificates), and `js.php` (scanner, certificate editor and other JavaScript). Certificate PDFs themselves are always in Indonesian.
- To change or add a translation, edit those files. Text without a translation is shown in Indonesian.
- Data entered by users (names, institutions, event descriptions, change reasons) is not translated.

## Quick customization

- **Profession** and **Field of study** options: the `PROFESSIONS` and `FIELDS` constants in `app/Models/Participant.php` (add the English label to `app/Lang/en/general.php` too)
- Colors and look: the CSS variables at the top of `public/assets/css/app.css`
- Validation rules (ID number length, phone number format): `app/Services/Validator.php`
