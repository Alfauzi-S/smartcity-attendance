-- SmartCity Attendance · skema SQLite (untuk uji coba / server kecil)

CREATE TABLE IF NOT EXISTS events (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  slug TEXT NOT NULL UNIQUE,
  name TEXT NOT NULL,
  description TEXT NULL,
  organizer TEXT NOT NULL,
  location TEXT NOT NULL,
  event_date TEXT NOT NULL,
  start_time TEXT NOT NULL,
  end_time TEXT NOT NULL,
  reg_start TEXT NULL,
  reg_end TEXT NULL,
  att_start TEXT NOT NULL,
  att_end TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'Draft',
  reg_status TEXT NOT NULL DEFAULT 'Ditutup',
  att_status TEXT NOT NULL DEFAULT 'notyet',
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  username TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  role TEXT NOT NULL,
  event_id INTEGER NULL REFERENCES events(id) ON DELETE SET NULL,
  status TEXT NOT NULL DEFAULT 'Aktif',
  must_change_password INTEGER NOT NULL DEFAULT 0,
  last_login_at TEXT NULL,
  created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS participants (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  event_id INTEGER NOT NULL REFERENCES events(id) ON DELETE RESTRICT,
  code TEXT NOT NULL UNIQUE,
  identity_number TEXT NOT NULL,
  profession TEXT NOT NULL,
  full_name TEXT NOT NULL,
  phone TEXT NOT NULL,
  institution TEXT NOT NULL,
  field_of_study TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'belum',
  checked_in_at TEXT NULL,
  checked_in_by INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
  method TEXT NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  UNIQUE (event_id, identity_number)
);
CREATE INDEX IF NOT EXISTS idx_participants_status ON participants (event_id, status);
CREATE INDEX IF NOT EXISTS idx_participants_name ON participants (full_name);

CREATE TABLE IF NOT EXISTS status_history (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  participant_id INTEGER NOT NULL REFERENCES participants(id) ON DELETE CASCADE,
  old_status TEXT NOT NULL,
  new_status TEXT NOT NULL,
  reason TEXT NULL,
  changed_by INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
  changed_by_username TEXT NOT NULL,
  changed_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_history_participant ON status_history (participant_id);
CREATE INDEX IF NOT EXISTS idx_history_time ON status_history (changed_at);

CREATE TABLE IF NOT EXISTS rate_limits (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  rl_key TEXT NOT NULL,
  created_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_rate_key ON rate_limits (rl_key, created_at);
