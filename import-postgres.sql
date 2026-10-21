-- =========================================
--  Reservation System - import-postgres.sql
--  same as import.sql, for Postgres (Neon on Vercel)
-- =========================================
-- one DO block = one statement → also runs in Vercel's Storage → Query page

DO $do$
BEGIN

DROP TABLE IF EXISTS checkin_logs, email_logs, personal_access_tokens, registrations, users;

-- USERS (admin / staff)
CREATE TABLE users (
  id          BIGSERIAL PRIMARY KEY,
  username    VARCHAR(100) NOT NULL UNIQUE,          -- admin login
  email       VARCHAR(255) NULL UNIQUE,              -- admin login
  password    VARCHAR(255) NULL,                     -- bcrypt hash
  created_at  TIMESTAMP NULL,
  updated_at  TIMESTAMP NULL
);

-- REGISTRATIONS (guests)
CREATE TABLE registrations (
  id             BIGSERIAL PRIMARY KEY,
  username       VARCHAR(100) NOT NULL DEFAULT '',   -- guest name
  email          VARCHAR(255) NOT NULL UNIQUE,
  phone          VARCHAR(30)  NOT NULL,
  token          CHAR(36)     NOT NULL UNIQUE,       -- value inside the QR code
  email_sent_at  TIMESTAMP NULL,                     -- null = not sent
  checked_in_at  TIMESTAMP NULL,                     -- null = not arrived
  created_at     TIMESTAMP NULL,
  updated_at     TIMESTAMP NULL
);

-- EMAIL_LOGS
CREATE TABLE email_logs (
  id               BIGSERIAL PRIMARY KEY,
  registration_id  BIGINT NOT NULL REFERENCES registrations(id) ON DELETE CASCADE,
  sent_by          BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,  -- null = system
  type             VARCHAR(10) NOT NULL CHECK (type IN ('qr','resend')),
  status           VARCHAR(10) NOT NULL CHECK (status IN ('sent','failed')),
  error_message    TEXT NULL,                        -- why it failed
  sent_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- CHECKIN_LOGS (every scan)
CREATE TABLE checkin_logs (
  id               BIGSERIAL PRIMARY KEY,
  registration_id  BIGINT NULL REFERENCES registrations(id) ON DELETE SET NULL,  -- null = invalid QR
  user_id          BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,       -- staff who scanned
  result           VARCHAR(20) NOT NULL CHECK (result IN ('success','already_checked_in','invalid')),
  scanned_value    VARCHAR(255) NOT NULL,
  scanned_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- PERSONAL_ACCESS_TOKENS (Laravel Sanctum) - DO NOT REMOVE, login needs it
CREATE TABLE personal_access_tokens (
  id              BIGSERIAL PRIMARY KEY,
  tokenable_type  VARCHAR(255) NOT NULL,
  tokenable_id    BIGINT NOT NULL,
  name            TEXT NOT NULL,
  token           VARCHAR(64) NOT NULL UNIQUE,
  last_used_at    TIMESTAMP NULL,
  expires_at      TIMESTAMP NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL
);
CREATE INDEX personal_access_tokens_tokenable_index ON personal_access_tokens (tokenable_type, tokenable_id);

-- ADMIN USER: email admin@gmail.com / password 123
INSERT INTO users (username, email, password, created_at, updated_at)
VALUES ('admin', 'admin@gmail.com', '$2y$12$GtCfeVbM9CxTDfMDCW4Po.XPsBBEwgsrm4QBU8kTWAbPTVat.JcnW', NOW(), NOW());

END
$do$;
