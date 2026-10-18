-- Migrasi aditif: role Admin Event, audit log, dan sistem sertifikat.
-- Hanya membuat tabel baru (CREATE TABLE IF NOT EXISTS). Tidak mengubah atau menghapus data lama.

CREATE TABLE IF NOT EXISTS event_admin_assignments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  event_id INT UNSIGNED NOT NULL,
  permissions TEXT NOT NULL,
  assigned_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_assign (user_id, event_id),
  KEY idx_assign_event (event_id),
  CONSTRAINT fk_assign_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_assign_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  username VARCHAR(60) NULL,
  role VARCHAR(10) NULL,
  event_id INT UNSIGNED NULL,
  action VARCHAR(60) NOT NULL,
  object_type VARCHAR(40) NULL,
  object_id VARCHAR(60) NULL,
  result VARCHAR(10) NOT NULL DEFAULT 'ok',
  meta TEXT NULL,
  ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL,
  KEY idx_audit_event (event_id, created_at),
  KEY idx_audit_time (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cert_assets (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NULL,
  kind VARCHAR(12) NOT NULL,
  label VARCHAR(120) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  mime VARCHAR(40) NOT NULL,
  width_px INT UNSIGNED NOT NULL,
  height_px INT UNSIGNED NOT NULL,
  approved TINYINT(1) NOT NULL DEFAULT 0,
  approved_by INT UNSIGNED NULL,
  approved_at DATETIME NULL,
  archived TINYINT(1) NOT NULL DEFAULT 0,
  uploaded_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  KEY idx_asset_event (event_id),
  CONSTRAINT fk_asset_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cert_templates (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  status VARCHAR(10) NOT NULL DEFAULT 'Draft',
  page_format VARCHAR(10) NOT NULL DEFAULT 'A4',
  orientation CHAR(1) NOT NULL DEFAULT 'L',
  width_mm DECIMAL(7,2) NOT NULL,
  height_mm DECIMAL(7,2) NOT NULL,
  background_path VARCHAR(255) NULL,
  background_type VARCHAR(5) NULL,
  background_w INT UNSIGNED NULL,
  background_h INT UNSIGNED NULL,
  elements MEDIUMTEXT NOT NULL,
  version INT UNSIGNED NOT NULL DEFAULT 0,
  created_by INT UNSIGNED NULL,
  updated_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  KEY idx_tpl_event (event_id),
  CONSTRAINT fk_tpl_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cert_template_versions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  template_id INT UNSIGNED NOT NULL,
  version INT UNSIGNED NOT NULL,
  snapshot MEDIUMTEXT NOT NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_tpl_version (template_id, version),
  CONSTRAINT fk_tplv_tpl FOREIGN KEY (template_id) REFERENCES cert_templates (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cert_variables (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  scope VARCHAR(10) NOT NULL,
  scope_key VARCHAR(20) NOT NULL,
  event_id INT UNSIGNED NULL,
  var_key VARCHAR(40) NOT NULL,
  label VARCHAR(120) NOT NULL,
  var_type VARCHAR(10) NOT NULL DEFAULT 'text',
  source VARCHAR(20) NOT NULL DEFAULT 'manual',
  source_field VARCHAR(40) NULL,
  choices TEXT NULL,
  value TEXT NULL,
  default_value TEXT NULL,
  required TINYINT(1) NOT NULL DEFAULT 1,
  overridable TINYINT(1) NOT NULL DEFAULT 1,
  description VARCHAR(255) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_var_scope (scope_key, var_key),
  CONSTRAINT fk_var_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cert_variable_values (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  variable_id INT UNSIGNED NOT NULL,
  event_id INT UNSIGNED NOT NULL,
  value TEXT NULL,
  updated_by INT UNSIGNED NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_var_value (variable_id, event_id),
  CONSTRAINT fk_varval_var FOREIGN KEY (variable_id) REFERENCES cert_variables (id) ON DELETE CASCADE,
  CONSTRAINT fk_varval_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cert_settings (
  event_id INT UNSIGNED NOT NULL PRIMARY KEY,
  template_id INT UNSIGNED NULL,
  eligibility VARCHAR(20) NOT NULL DEFAULT 'hadir',
  require_approved_number TINYINT(1) NOT NULL DEFAULT 1,
  issue_date DATE NULL,
  updated_by INT UNSIGNED NULL,
  updated_at DATETIME NOT NULL,
  CONSTRAINT fk_cset_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cert_numbering (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  version INT UNSIGNED NOT NULL,
  pattern VARCHAR(255) NOT NULL,
  seq_padding TINYINT UNSIGNED NOT NULL DEFAULT 4,
  seq_scope VARCHAR(10) NOT NULL DEFAULT 'event',
  seq_reset VARCHAR(10) NOT NULL DEFAULT 'never',
  status VARCHAR(10) NOT NULL DEFAULT 'draft',
  note VARCHAR(255) NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  approved_by INT UNSIGNED NULL,
  approved_at DATETIME NULL,
  UNIQUE KEY uq_num_version (event_id, version),
  CONSTRAINT fk_num_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cert_sequences (
  scope_key VARCHAR(40) NOT NULL PRIMARY KEY,
  seq_value INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cert_jobs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  template_version_id INT UNSIGNED NOT NULL,
  numbering_id INT UNSIGNED NOT NULL,
  status VARCHAR(10) NOT NULL DEFAULT 'queued',
  participant_ids MEDIUMTEXT NOT NULL,
  total INT UNSIGNED NOT NULL DEFAULT 0,
  cursor_pos INT UNSIGNED NOT NULL DEFAULT 0,
  success INT UNSIGNED NOT NULL DEFAULT 0,
  skipped INT UNSIGNED NOT NULL DEFAULT 0,
  failed INT UNSIGNED NOT NULL DEFAULT 0,
  errors MEDIUMTEXT NULL,
  locked_until DATETIME NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  finished_at DATETIME NULL,
  KEY idx_job_event (event_id),
  CONSTRAINT fk_job_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cert_issuances (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  participant_id INT UNSIGNED NOT NULL,
  template_version_id INT UNSIGNED NOT NULL,
  numbering_id INT UNSIGNED NOT NULL,
  sequence INT UNSIGNED NOT NULL,
  cert_number VARCHAR(190) NOT NULL,
  token CHAR(32) NOT NULL,
  status VARCHAR(10) NOT NULL DEFAULT 'generated',
  active_slot INT UNSIGNED NULL,
  file_path VARCHAR(255) NOT NULL,
  file_hash CHAR(64) NOT NULL,
  data_snapshot TEXT NOT NULL,
  issue_date DATE NOT NULL,
  job_id INT UNSIGNED NULL,
  replaces_id INT UNSIGNED NULL,
  generated_by INT UNSIGNED NULL,
  generated_at DATETIME NOT NULL,
  published_by INT UNSIGNED NULL,
  published_at DATETIME NULL,
  revoked_by INT UNSIGNED NULL,
  revoked_at DATETIME NULL,
  revoke_reason VARCHAR(255) NULL,
  UNIQUE KEY uq_cert_number (cert_number),
  UNIQUE KEY uq_cert_token (token),
  UNIQUE KEY uq_cert_active (event_id, active_slot),
  KEY idx_cert_status (event_id, status),
  KEY idx_cert_participant (participant_id),
  CONSTRAINT fk_cert_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE RESTRICT,
  CONSTRAINT fk_cert_participant FOREIGN KEY (participant_id) REFERENCES participants (id) ON DELETE RESTRICT,
  CONSTRAINT fk_cert_tplv FOREIGN KEY (template_version_id) REFERENCES cert_template_versions (id) ON DELETE RESTRICT,
  CONSTRAINT fk_cert_num FOREIGN KEY (numbering_id) REFERENCES cert_numbering (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
