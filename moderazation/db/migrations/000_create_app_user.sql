-- Phase 1 / S2: create a least-privilege application database user.
-- Run as a DB admin, then put these credentials in .env (DB_USER/DB_PASS).
-- Adjust host ('localhost' vs '%') to match how PHP connects.

CREATE USER IF NOT EXISTS 'pam_app'@'localhost' IDENTIFIED BY 'change_me_strong_password';

GRANT SELECT, INSERT, UPDATE, DELETE ON `pam`.* TO 'pam_app'@'localhost';

FLUSH PRIVILEGES;
