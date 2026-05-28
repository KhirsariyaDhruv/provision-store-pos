<?php
require 'config/db.php';

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM contact_messages LIKE 'email'");
    if ($stmt->fetch()) {
        echo "Column 'email' already exists.";
    } else {
        $pdo->exec("ALTER TABLE contact_messages ADD COLUMN email VARCHAR(150) AFTER phone");
        echo "Column 'email' added successfully.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
