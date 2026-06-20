-- Phase 1 / S3: widen the password column to hold modern hashes.
-- bcrypt hashes are 60 chars; the legacy column was VARCHAR(50), which would
-- truncate (and corrupt) a bcrypt hash. RUN THIS BEFORE deploying the new
-- login.php, otherwise password upgrades-on-login will break accounts.

ALTER TABLE `auth_users` MODIFY `password` VARCHAR(255) NOT NULL;
