<?php
    session_start();
    if (isset($_SESSION['is_logged']) && $_SESSION['is_logged'] == true) {
        unset($_SESSION['is_logged']);
        session_destroy();
    }

    header("Location: login.php");
    exit;
?>
