-- ════════════════════════════════════════════════
--  NEONBYTE — Database schema (no data, no accounts)
--  Import: mysql -u root neonbyte < neonbyte.sql
--  Or via phpMyAdmin → Import
-- ════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS neonbyte
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE neonbyte;
SET NAMES utf8mb4;

-- ── Users ────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  name             VARCHAR(80)  NOT NULL,
  email_encrypted  TEXT         NOT NULL,                -- AES-256-GCM (see includes/crypto.php)
  email_hash       CHAR(64)     NOT NULL UNIQUE,         -- HMAC-SHA256 blind index used for login lookups
  password_hash    VARCHAR(255) NOT NULL,                -- bcrypt / password_hash()
  avatar           VARCHAR(255) DEFAULT NULL,
  role             ENUM('user','admin') DEFAULT 'user',
  screenshot_limit TINYINT DEFAULT 5,
  theme            VARCHAR(20)  DEFAULT 'neon-blue',
  banned           TINYINT(1)   DEFAULT 0,
  created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- Login rate limiting (IPs are stored only as HMAC hashes)
CREATE TABLE IF NOT EXISTS login_attempts (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  ip_hash    CHAR(64) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (ip_hash, created_at)
);

-- ── Categories ───────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(40) UNIQUE,
  slug VARCHAR(40) UNIQUE
);

-- ── Projects ─────────────────────────────────
CREATE TABLE IF NOT EXISTS projects (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT  NOT NULL,
  category_id   INT  NOT NULL,
  name          VARCHAR(100) NOT NULL,
  description   TEXT         NOT NULL,
  license       VARCHAR(40)  NOT NULL,
  repo_url      VARCHAR(255) NOT NULL,
  download_url  VARCHAR(255) NOT NULL,
  video_url     VARCHAR(255) DEFAULT NULL,
  needs_help    TINYINT(1)   DEFAULT 0,
  featured      TINYINT(1)   DEFAULT 0,
  status        ENUM('published','removed') DEFAULT 'published',
  views         INT          DEFAULT 0,
  clicks        INT          DEFAULT 0,
  likes_count   INT          DEFAULT 0,             -- denormalized counter
  hue           SMALLINT     DEFAULT 200,            -- for cover gradient
  cover         VARCHAR(255) DEFAULT NULL,           -- optional cover image path
  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)     REFERENCES users(id),
  FOREIGN KEY (category_id) REFERENCES categories(id),
  FULLTEXT idx_search (name, description)
);

-- ── Project platforms ─────────────────────────
CREATE TABLE IF NOT EXISTS project_platforms (
  project_id INT,
  platform   VARCHAR(20),
  PRIMARY KEY (project_id, platform),
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- ── Screenshots ───────────────────────────────
CREATE TABLE IF NOT EXISTS screenshots (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  project_id  INT NOT NULL,
  user_id     INT NOT NULL,
  path_or_url VARCHAR(255) NOT NULL,
  is_upload   TINYINT(1)   DEFAULT 0,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- ── Likes ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS likes (
  user_id    INT,
  project_id INT,
  PRIMARY KEY (user_id, project_id)
);

-- ── Favorites ─────────────────────────────────
CREATE TABLE IF NOT EXISTS favorites (
  user_id    INT,
  project_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, project_id)
);

-- ── Comments / ratings ────────────────────────
CREATE TABLE IF NOT EXISTS comments (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT  NOT NULL,
  user_id    INT  NOT NULL,
  rating     TINYINT CHECK (rating BETWEEN 1 AND 5),
  body       TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)    REFERENCES users(id)
);

-- ── Reports (abuse) ───────────────────────────
CREATE TABLE IF NOT EXISTS reports (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  user_id    INT NOT NULL,
  reason     VARCHAR(255) NOT NULL,
  resolved   TINYINT(1)   DEFAULT 0,
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- ── Notifications ─────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  type       VARCHAR(40) NOT NULL,   -- 'like','comment','report_resolved' etc.
  message    TEXT        NOT NULL,
  link       VARCHAR(255),
  is_read    TINYINT(1)  DEFAULT 0,
  created_at TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Seed data ────────────────────────────────
INSERT IGNORE INTO categories (name, slug) VALUES
  ('Games',      'games'),
  ('Tools',      'tools'),
  ('Dev Tools',  'dev-tools'),
  ('Multimedia', 'multimedia'),
  ('Security',   'security'),
  ('Reverse Engineering','reverse-engineering');

-- No default admin exists. Create one with:  php tools/create_admin.php "Name" you@example.com
