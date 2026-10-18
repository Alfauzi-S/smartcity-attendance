# SmartCity Attendance

An event registration and QR Code attendance website with a Smart City / Smart Campus theme.
Built with PHP 8.1+ (OOP, no framework) and MySQL/MariaDB or SQLite.

## User roles

| Role | Login | Access |
|---|---|---|
| **Participant** | No | View events, register (6 required fields), receive a QR Code, download the QR (PNG) |
| **Attendance Admin** | Yes | Scan QR codes, save attendance, enter codes or search manually, view the participant list of the assigned event |
| **Super Admin** | Yes | Everything: events, Attendance Admin accounts, participants, manual Present/Absent status changes (with confirmation and a reason), change history, reports, CSV/Excel export |

Participant statuses:

- **Not yet verified**: the initial status after registration.
- **Present**: recorded by a QR scan, or set manually by the Super Admin.
- **Absent**: set by the Super Admin. This status is final, so the participant's QR can no longer be used.

## Server requirements

- PHP **8.1 or newer** with the `pdo_mysql` (or `pdo_sqlite`) and `mbstring` extensions
- `zip` extension: optional, only needed for Excel (.xlsx) export. CSV export works without it.
- MySQL 5.7+ / MariaDB 10.3+ (or SQLite 3 for testing)
- Apache with `mod_rewrite`, or Nginx
- Or just **Docker** — the included stack (see [Docker](#docker-nginx--php-fpm--mariadb)) already provides PHP 8.5, Nginx and MariaDB
- **HTTPS is required** for the scanner camera. Browsers only allow camera access on HTTPS or `localhost`. Without HTTPS, the scanner automatically falls back to code entry and manual search.

## Deployment

**Using Docker?** Skip straight to [Docker](#docker-nginx--php-fpm--mariadb) below; steps 1–4 are handled by the stack.

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
- `base_url`: leave empty for automatic detection. Fill it in only if the app runs behind a reverse proxy.

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
3. Optional: tick **demo data** (1 event, 156 participants, 98 present, 2 Attendance Admins) for testing.

Once the first Super Admin is created, `/install` locks itself (404).

### 6. Start using it

1. Sign in at `/masuk` as the Super Admin.
2. **Events → Create event**, then set the event status to **Active** and **Open registration**.
3. Copy the **registration link** and share it with participants.
4. **Admin Accounts → Create account** for the scanning staff, and assign them to the event.
5. On the event day, open **Event details → Open attendance**. Staff scan QR codes at `/petugas/scan`.
6. When it is over, **Close attendance**, mark participants who did not come as Absent, then export the summary from **Reports**.

### Example Nginx configuration

See `nginx.conf.example`. The key points: `root` must point to the `public/` folder, and `try_files $uri $uri/ /index.php?$query_string;`.

### Running it on your own computer

```bash
cp config/config.example.php config/config.php   # change driver to 'sqlite'
php -S localhost:8000 -t public public/index.php
```

Open `http://localhost:8000/install`. The camera works on `localhost` even without HTTPS.

### Docker (Nginx + PHP-FPM + MariaDB)

This is the recommended way to run the app on a server. The repository ships a
ready-to-run stack in `docker-compose.yml`:

| Service | Image | Role |
|---|---|---|
| `web` | `nginx:alpine` (latest) | serves `public/` and forwards PHP to `app`; the only service that publishes a host port |
| `app` | built from `docker/php/Dockerfile` | the application (`php:8.5-fpm-alpine`) with `pdo_mysql`, `gd`, `mbstring`, `zip` and OPcache |
| `db` | `mariadb:12.3` (LTS) | database; data is kept in the named volume `smartcity_attendance_db`, the port is not published |

```bash
cp .env.example .env          # fill in DB_PASSWORD and DB_ROOT_PASSWORD
docker compose up -d --build  # or: docker-compose up -d --build
```

Then open `/install` (for example `http://<server>:8090/install`) and create the
Super Admin.

Notes:

- `config/config.php` is generated from `.env` on every start of the `app`
  container (`APP_CONFIG_SYNC=true`). To change a setting, edit `.env` and
  restart the stack — do not edit the generated file by hand.
- The app publishes `APP_BIND_IP:APP_PORT` (default `0.0.0.0:8090`). Set
  `APP_BIND_IP` to the LAN address if only a reverse proxy should reach it, and
  never reuse a port that another stack on the host already uses.
- Behind a reverse proxy, leave `APP_BASE_URL` empty: the `Host` header and
  `X-Forwarded-Proto` sent by the proxy are used to build the `https://` links
  (required for QR codes and the camera).
- The source code is bind-mounted, so edits apply without rebuilding the image
  (OPCache revalidates within 2 seconds). `storage/` also stays on the host.
- Upgrading an existing database: keep `DB_PASSWORD`/`DB_ROOT_PASSWORD`
  unchanged, then run the pending migrations with
  `docker compose exec app php bin/migrate.php --yes` (the dashboard also has
  a run-migrations button for the Super Admin).
- Database backup:
  `docker compose exec db mariadb-dump -u smartcity -p smartcity_attendance > backup.sql`
  (it asks for the database password; `-u root -p` works too).
- Logs: `docker compose logs -f web app db`; PHP errors are written to
  `storage/logs/php-error.log`.

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

## Folder structure

```
app/
  Core/          Router, Database (PDO), Auth, View, RateLimiter
  Models/        Event, Participant, User, StatusHistory
  Services/      AttendanceService (attendance rules), Validator, Exporter (CSV/XLSX), DemoSeeder
  Controllers/   Public, Auth, Staff, Api, Dashboard, Event, Account, Participant, Report, Settings, Install
  Lang/en/       English translations
  Views/         layouts, partials, public, staff and super admin pages
  bootstrap.php  autoload, configuration, session, language, security headers
  helpers.php    helper functions (url, e, csrf, t, date formatting, status badges)
config/          config.example.php → copy to config.php
database/        schema.mysql.sql, schema.sqlite.sql
docker/          Dockerfile PHP-FPM, Nginx config, entrypoint (Docker deployment)
public/          index.php (front controller), assets (css, js, vendor, img)
storage/         SQLite database (optional) and error log
docker-compose.yml  web (Nginx) + app (PHP-FPM) + db (MariaDB) services
.env.example     environment for the Docker stack (copy to .env)
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
| `/admin` | Super Admin dashboard |
| `/install` | First-time installation (locked once done) |

## Third-party libraries

These libraries are bundled in `public/assets/vendor/`, so the app does not depend on a CDN:

- [html5-qrcode](https://github.com/mebjas/html5-qrcode) 2.3.8, Apache-2.0: camera scanner
- [qrcodejs](https://github.com/davidshimjs/qrcodejs) 1.0.0, MIT: generates the ticket QR image

The Sora and Inter fonts load from Google Fonts. If the server cannot reach the internet, the pages fall back to system fonts.

## Two languages (Indonesian / English)

Every page has an **ID / EN** button at the top. The choice is stored in a cookie for one year, and the default language is set with `locale` in `config/config.php`.

- Source text is written in Indonesian in the code, for example `t('Simpan data')`.
- English translations live in `app/Lang/en/`: `general.php` (public pages, sign in, shared text), `staff.php` (Attendance Admin), `super.php` (Super Admin), and `js.php` (scanner and JavaScript).
- To change or add a translation, edit those files. Text without a translation is shown in Indonesian.
- Data entered by users (names, institutions, event descriptions, change reasons) is not translated.

## Quick customization

- **Profession** and **Field of study** options: the `PROFESSIONS` and `FIELDS` constants in `app/Models/Participant.php` (add the English label to `app/Lang/en/general.php` too)
- Colors and look: the CSS variables at the top of `public/assets/css/app.css`
- Validation rules (ID number length, phone number format): `app/Services/Validator.php`
