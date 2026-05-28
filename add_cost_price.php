<?php
require_once 'config/db.php';

try {
    $sql = "ALTER TABLE products ADD COLUMN IF NOT EXISTS cost_price DECIMAL(10,2) DEFAULT 0";
    $pdo->exec($sql);
    echo "Successfully added cost_price column.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
