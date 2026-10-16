-- SmartCity Attendance · skema MySQL / MariaDB (utf8mb4)

CREATE TABLE IF NOT EXISTS events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(80) NOT NULL,
  name VARCHAR(160) NOT NULL,
  description TEXT NULL,
  organizer VARCHAR(160) NOT NULL,
  location VARCHAR(200) NOT NULL,
  event_date DATE NOT NULL,
  start_time CHAR(5) NOT NULL,
  end_time CHAR(5) NOT NULL,
  reg_start DATE NULL,
  reg_end DATE NULL,
  att_start CHAR(5) NOT NULL,
  att_end CHAR(5) NOT NULL,
  status VARCHAR(10) NOT NULL DEFAULT 'Draft',
  reg_status VARCHAR(10) NOT NULL DEFAULT 'Ditutup',
  att_status VARCHAR(10) NOT NULL DEFAULT 'notyet',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_events_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  username VARCHAR(40) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(10) NOT NULL,
  event_id INT UNSIGNED NULL,
  status VARCHAR(10) NOT NULL DEFAULT 'Aktif',
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_users_username (username),
  CONSTRAINT fk_users_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS participants (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  code CHAR(10) NOT NULL,
  identity_number VARCHAR(20) NOT NULL,
  profession VARCHAR(60) NOT NULL,
  full_name VARCHAR(160) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  institution VARCHAR(160) NOT NULL,
  field_of_study VARCHAR(120) NOT NULL,
  status VARCHAR(10) NOT NULL DEFAULT 'belum',
  checked_in_at DATETIME NULL,
  checked_in_by INT UNSIGNED NULL,
  method VARCHAR(10) NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_participants_code (code),
  UNIQUE KEY uq_participants_identity (event_id, identity_number),
  KEY idx_participants_status (event_id, status),
  KEY idx_participants_name (full_name),
  CONSTRAINT fk_participants_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE RESTRICT,
  CONSTRAINT fk_participants_user FOREIGN KEY (checked_in_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS status_history (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  participant_id INT UNSIGNED NOT NULL,
  old_status VARCHAR(10) NOT NULL,
  new_status VARCHAR(10) NOT NULL,
  reason VARCHAR(255) NULL,
  changed_by INT UNSIGNED NULL,
  changed_by_username VARCHAR(40) NOT NULL,
  changed_at DATETIME NOT NULL,
  KEY idx_history_participant (participant_id),
  KEY idx_history_time (changed_at),
  CONSTRAINT fk_history_participant FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE,
  CONSTRAINT fk_history_user FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rl_key VARCHAR(120) NOT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_rate_key (rl_key, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
