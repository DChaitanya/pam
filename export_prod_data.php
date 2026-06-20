<?php
/**
 * DISABLED (Phase 1 security hardening — finding S4).
 *
 * This script used the removed mysql_* API (broken on PHP 7+), connected as
 * the root database user with no authentication, and copied production data
 * into the development database. It is an unacceptable risk and has been
 * disabled. Use a proper, access-controlled backup (tools/backup_db.sh) and a
 * separate import step instead.
 *
 * The original implementation is preserved in Git history if ever needed.
 */

require_once __DIR__ . '/auth_guard.php';

http_response_code(410); // Gone
echo "This data-export utility has been permanently disabled for security reasons.";
exit;
