-- Migrasi aditif: role Admin Event, audit log, dan sistem sertifikat.
-- Hanya membuat tabel baru (CREATE TABLE IF NOT EXISTS). Tidak mengubah atau menghapus data lama.

CREATE TABLE IF NOT EXISTS event_admin_assignments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  event_id INTEGER NOT NULL,
  permissions TEXT NOT NULL,
  assigned_by INTEGER NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  UNIQUE (user_id, event_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS audit_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NULL,
  username TEXT NULL,
  role TEXT NULL,
  event_id INTEGER NULL,
  action TEXT NOT NULL,
  object_type TEXT NULL,
  object_id TEXT NULL,
  result TEXT NOT NULL DEFAULT 'ok',
  meta TEXT NULL,
  ip TEXT NULL,
  created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS cert_assets (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  event_id INTEGER NULL,
  kind TEXT NOT NULL,
  label TEXT NOT NULL,
  file_path TEXT NOT NULL,
  mime TEXT NOT NULL,
  width_px INTEGER NOT NULL,
  height_px INTEGER NOT NULL,
  approved INTEGER NOT NULL DEFAULT 0,
  approved_by INTEGER NULL,
  approved_at TEXT NULL,
  archived INTEGER NOT NULL DEFAULT 0,
  uploaded_by INTEGER NULL,
  created_at TEXT NOT NULL,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS cert_templates (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  event_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'Draft',
  page_format TEXT NOT NULL DEFAULT 'A4',
  orientation TEXT NOT NULL DEFAULT 'L',
  width_mm REAL NOT NULL,
  height_mm REAL NOT NULL,
  background_path TEXT NULL,
  background_type TEXT NULL,
  background_w INTEGER NULL,
  background_h INTEGER NULL,
  elements TEXT NOT NULL,
  version INTEGER NOT NULL DEFAULT 0,
  created_by INTEGER NULL,
  updated_by INTEGER NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS cert_template_versions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  template_id INTEGER NOT NULL,
  version INTEGER NOT NULL,
  snapshot TEXT NOT NULL,
  created_by INTEGER NULL,
  created_at TEXT NOT NULL,
  UNIQUE (template_id, version),
  FOREIGN KEY (template_id) REFERENCES cert_templates(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS cert_variables (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  scope TEXT NOT NULL,
  scope_key TEXT NOT NULL,
  event_id INTEGER NULL,
  var_key TEXT NOT NULL,
  label TEXT NOT NULL,
  var_type TEXT NOT NULL DEFAULT 'text',
  source TEXT NOT NULL DEFAULT 'manual',
  source_field TEXT NULL,
  choices TEXT NULL,
  value TEXT NULL,
  default_value TEXT NULL,
  required INTEGER NOT NULL DEFAULT 1,
  overridable INTEGER NOT NULL DEFAULT 1,
  description TEXT NULL,
  active INTEGER NOT NULL DEFAULT 1,
  created_by INTEGER NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  UNIQUE (scope_key, var_key),
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS cert_variable_values (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  variable_id INTEGER NOT NULL,
  event_id INTEGER NOT NULL,
  value TEXT NULL,
  updated_by INTEGER NULL,
  updated_at TEXT NOT NULL,
  UNIQUE (variable_id, event_id),
  FOREIGN KEY (variable_id) REFERENCES cert_variables(id) ON DELETE CASCADE,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS cert_settings (
  event_id INTEGER NOT NULL PRIMARY KEY,
  template_id INTEGER NULL,
  eligibility TEXT NOT NULL DEFAULT 'hadir',
  require_approved_number INTEGER NOT NULL DEFAULT 1,
  issue_date TEXT NULL,
  updated_by INTEGER NULL,
  updated_at TEXT NOT NULL,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS cert_numbering (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  event_id INTEGER NOT NULL,
  version INTEGER NOT NULL,
  pattern TEXT NOT NULL,
  seq_padding TINYINTEGER NOT NULL DEFAULT 4,
  seq_scope TEXT NOT NULL DEFAULT 'event',
  seq_reset TEXT NOT NULL DEFAULT 'never',
  status TEXT NOT NULL DEFAULT 'draft',
  note TEXT NULL,
  created_by INTEGER NULL,
  created_at TEXT NOT NULL,
  approved_by INTEGER NULL,
  approved_at TEXT NULL,
  UNIQUE (event_id, version),
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS cert_sequences (
  scope_key TEXT NOT NULL PRIMARY KEY,
  seq_value INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS cert_jobs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  event_id INTEGER NOT NULL,
  template_version_id INTEGER NOT NULL,
  numbering_id INTEGER NOT NULL,
  status TEXT NOT NULL DEFAULT 'queued',
  participant_ids TEXT NOT NULL,
  total INTEGER NOT NULL DEFAULT 0,
  cursor_pos INTEGER NOT NULL DEFAULT 0,
  success INTEGER NOT NULL DEFAULT 0,
  skipped INTEGER NOT NULL DEFAULT 0,
  failed INTEGER NOT NULL DEFAULT 0,
  errors TEXT NULL,
  locked_until TEXT NULL,
  created_by INTEGER NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  finished_at TEXT NULL,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS cert_issuances (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  event_id INTEGER NOT NULL,
  participant_id INTEGER NOT NULL,
  template_version_id INTEGER NOT NULL,
  numbering_id INTEGER NOT NULL,
  sequence INTEGER NOT NULL,
  cert_number TEXT NOT NULL,
  token TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'generated',
  active_slot INTEGER NULL,
  file_path TEXT NOT NULL,
  file_hash TEXT NOT NULL,
  data_snapshot TEXT NOT NULL,
  issue_date TEXT NOT NULL,
  job_id INTEGER NULL,
  replaces_id INTEGER NULL,
  generated_by INTEGER NULL,
  generated_at TEXT NOT NULL,
  published_by INTEGER NULL,
  published_at TEXT NULL,
  revoked_by INTEGER NULL,
  revoked_at TEXT NULL,
  revoke_reason TEXT NULL,
  UNIQUE (cert_number),
  UNIQUE (token),
  UNIQUE (event_id, active_slot),
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE RESTRICT,
  FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE RESTRICT,
  FOREIGN KEY (template_version_id) REFERENCES cert_template_versions(id) ON DELETE RESTRICT,
  FOREIGN KEY (numbering_id) REFERENCES cert_numbering(id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_assign_event ON event_admin_assignments (event_id);
CREATE INDEX IF NOT EXISTS idx_audit_event ON audit_logs (event_id, created_at);
CREATE INDEX IF NOT EXISTS idx_audit_time ON audit_logs (created_at);
CREATE INDEX IF NOT EXISTS idx_asset_event ON cert_assets (event_id);
CREATE INDEX IF NOT EXISTS idx_tpl_event ON cert_templates (event_id);
CREATE INDEX IF NOT EXISTS idx_job_event ON cert_jobs (event_id);
CREATE INDEX IF NOT EXISTS idx_cert_status ON cert_issuances (event_id, status);
CREATE INDEX IF NOT EXISTS idx_cert_participant ON cert_issuances (participant_id);
