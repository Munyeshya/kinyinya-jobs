-- Kinyinya Jobs — MySQL schema
-- Run this once to create the database and tables:
--   mysql -u root -p < database/schema.sql
-- Then load demo data (optional but recommended for a working demo):
--   mysql -u root -p kinyinya_jobs < database/seed.sql

CREATE DATABASE IF NOT EXISTS kinyinya_jobs
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE kinyinya_jobs;

-- User accounts shared by job seekers, employers, and administrators
CREATE TABLE IF NOT EXISTS users (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email          VARCHAR(190) NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL,
  role           ENUM('seeker','employer','admin') NOT NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Employers
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS employers (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id   INT UNSIGNED NULL UNIQUE,
  name      VARCHAR(150) NOT NULL,
  industry  VARCHAR(150) NOT NULL,
  location  VARCHAR(150) NOT NULL,
  about     TEXT NULL,
  CONSTRAINT fk_employers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Job seekers
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS seekers (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NULL UNIQUE,
  name       VARCHAR(150) NOT NULL,
  skills     VARCHAR(255) NOT NULL,
  education  VARCHAR(150) NOT NULL,
  location   VARCHAR(150) NOT NULL,
  CONSTRAINT fk_seekers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Job postings
-- status: admin-review workflow (#23). pending -> approved/rejected.
-- active: whether the employer/system currently treats the posting as
--         open; automatically flipped to 0 once `deadline` passes (#24).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS jobs (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employer_id   INT UNSIGNED NOT NULL,
  title         VARCHAR(150) NOT NULL,
  type          VARCHAR(50) NOT NULL DEFAULT 'Full-time',
  category      VARCHAR(100) NOT NULL DEFAULT 'General',
  description   TEXT NULL,
  requirements  TEXT NULL,
  salary_min    INT UNSIGNED NOT NULL DEFAULT 0,
  salary_max    INT UNSIGNED NOT NULL DEFAULT 0,
  deadline      DATE NOT NULL,
  posted        DATE NOT NULL,
  active        TINYINT(1) NOT NULL DEFAULT 1,
  status        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  views         INT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_jobs_employer FOREIGN KEY (employer_id) REFERENCES employers(id) ON DELETE CASCADE,
  INDEX idx_jobs_status (status),
  INDEX idx_jobs_deadline (deadline)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Applications
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS applications (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_id        INT UNSIGNED NOT NULL,
  seeker_id     INT UNSIGNED NOT NULL,
  date          DATE NOT NULL,
  status        ENUM('submitted','under_review','shortlisted','hired','rejected') NOT NULL DEFAULT 'submitted',
  cover_letter  TEXT NULL,
  CONSTRAINT fk_applications_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
  CONSTRAINT fk_applications_seeker FOREIGN KEY (seeker_id) REFERENCES seekers(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_application (job_id, seeker_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Messages (per-application thread between employer and seeker)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS messages (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  application_id  INT UNSIGNED NOT NULL,
  sender          ENUM('employer','seeker') NOT NULL,
  body            TEXT NOT NULL,
  sent_at         DATETIME NOT NULL,
  is_read         TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT fk_messages_application FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- In-platform notifications. These keep users informed without relying
-- on email, SMS, or real-time services.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  type        VARCHAR(50) NOT NULL,
  message     VARCHAR(255) NOT NULL,
  is_read     TINYINT(1) NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_notifications_user_read (user_id, is_read, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
