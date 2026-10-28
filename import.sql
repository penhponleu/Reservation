-- =========================================
--  Reservation System - import.sql
-- =========================================
CREATE DATABASE IF NOT EXISTS reservation_system
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE reservation_system;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS checkin_logs, email_logs, personal_access_tokens, registrations, users;
SET FOREIGN_KEY_CHECKS = 1;

-- USERS (admin / staff)
CREATE TABLE users (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  fullname    VARCHAR(100) NOT NULL,                 -- staff full name
  email       VARCHAR(255) NULL UNIQUE,              -- admin login
  password    VARCHAR(255) NULL,                     -- bcrypt hash
  created_at  TIMESTAMP NULL,
  updated_at  TIMESTAMP NULL
) ENGINE=InnoDB;

-- REGISTRATIONS (guests)
CREATE TABLE registrations (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  fullname       VARCHAR(100) NOT NULL DEFAULT '',   -- guest full name
  email          VARCHAR(255) NOT NULL UNIQUE,
  phone          VARCHAR(30)  NOT NULL,
  token          CHAR(36)     NOT NULL UNIQUE,       -- value inside the QR code
  email_sent_at  TIMESTAMP NULL,                     -- null = not sent
  checked_in_at  TIMESTAMP NULL,                     -- null = not arrived
  created_at     TIMESTAMP NULL,
  updated_at     TIMESTAMP NULL
) ENGINE=InnoDB;

-- EMAIL_LOGS
CREATE TABLE email_logs (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  registration_id  BIGINT UNSIGNED NOT NULL,
  sent_by          BIGINT UNSIGNED NULL,             -- null = system
  type             ENUM('qr','resend') NOT NULL,
  status           ENUM('sent','failed') NOT NULL,
  error_message    TEXT NULL,                        -- why it failed
  sent_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_email_logs_registration FOREIGN KEY (registration_id)
    REFERENCES registrations(id) ON DELETE CASCADE,
  CONSTRAINT fk_email_logs_user FOREIGN KEY (sent_by)
    REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- CHECKIN_LOGS (every scan)
CREATE TABLE checkin_logs (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  registration_id  BIGINT UNSIGNED NULL,             -- null = invalid QR
  user_id          BIGINT UNSIGNED NULL,             -- staff who scanned (null = no login)
  result           ENUM('success','already_checked_in','invalid') NOT NULL,
  scanned_value    VARCHAR(255) NOT NULL,
  scanned_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_checkin_logs_registration FOREIGN KEY (registration_id)
    REFERENCES registrations(id) ON DELETE SET NULL,
  CONSTRAINT fk_checkin_logs_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- PERSONAL_ACCESS_TOKENS (Laravel Sanctum) - DO NOT REMOVE, login needs it
CREATE TABLE personal_access_tokens (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tokenable_type  VARCHAR(255) NOT NULL,
  tokenable_id    BIGINT UNSIGNED NOT NULL,
  name            TEXT NOT NULL,
  token           VARCHAR(64) NOT NULL UNIQUE,
  abilities       TEXT NULL,                         -- Sanctum stores ["*"] here
  last_used_at    TIMESTAMP NULL,
  expires_at      TIMESTAMP NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  INDEX personal_access_tokens_tokenable_index (tokenable_type, tokenable_id)
) ENGINE=InnoDB;

-- ADMIN USER: email admin@gmail.com / password 123
-- this long text is the bcrypt hash of 123 - you still type 123 to log in
INSERT INTO users (fullname, email, password, created_at, updated_at)
VALUES ('Admin', 'admin@gmail.com', '$2y$12$GtCfeVbM9CxTDfMDCW4Po.XPsBBEwgsrm4QBU8kTWAbPTVat.JcnW', NOW(), NOW());
