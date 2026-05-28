<?php
// includes/force_password_change.php

// This file should be included in auth_check.php or header.php after session start
if (isset($_SESSION['user_id']) && isset($_SESSION['force_password_change']) && $_SESSION['force_password_change']) {
    $current_script = basename($_SERVER['PHP_SELF']);
    if ($current_script !== 'change_password.php' && $current_script !== 'logout.php') {
        header("Location: change_password.php");
        exit;
    }
}
?>
