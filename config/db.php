<?php
// config/db.php

$host = 'localhost';
$dbname = 'pos_db'; // Ensure this database exists in phpMyAdmin
$user = 'root';     // Default XAMPP username
$password = '';     // Default XAMPP password is empty

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage() . "<br>Please check config/db.php settings.");
}

// Set Timezone
date_default_timezone_set('Asia/Kolkata');
?>
