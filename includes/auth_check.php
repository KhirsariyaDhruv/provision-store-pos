<?php
// includes/auth_check.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Security: Prevent caching of protected pages
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

// Ensure $pdo is available for fetching current user details if needed
if (!isset($pdo)) {
    // Ideally included this file AFTER db.php, but if not:
    require_once __DIR__ . '/../config/db.php';
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$owner_id = $_SESSION['owner_id']; // For staff, this is the Admin's ID. For admin, this is NULL.

// get_owner_id moved to functions.php

require_once 'force_password_change.php';
?>
