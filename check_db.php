<?php
// check_db.php
require_once 'config/db.php';

try {
    if ($pdo) {
        echo "\n[SUCCESS] Database connection established successfully!\n";
        echo "Connected to database: '$dbname' as user '$user'.\n";
    }
} catch (Exception $e) {
    echo "\n[ERROR] Connection Failed.\n";
    echo $e->getMessage() . "\n";
}
?>
