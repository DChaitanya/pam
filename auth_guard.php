<?php
/**
 * Secure session bootstrap + authentication guard.
 *
 * Include this at the very top of every page that requires a logged-in user:
 *     require_once __DIR__ . '/auth_guard.php';
 *
 * It (1) starts the session with hardened cookie flags and (2) redirects to the
 * login page AND stops execution if the user is not authenticated. The legacy
 * pages redirected without calling exit, so page content still rendered to
 * unauthenticated users — this closes that bypass (finding S7 + auth bypass).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        // 'secure' => true,  // enable once the app is always served over HTTPS
    ]);
    session_start();
}

if (empty($_SESSION['is_logged'])) {
    $redirect = basename($_SERVER['SCRIPT_NAME'], '.php');
    header('Location: login.php?redirect=' . urlencode($redirect));
    exit;
}
