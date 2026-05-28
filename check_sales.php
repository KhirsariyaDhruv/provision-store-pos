<?php
require 'config/db.php';
try {
    $stmt = $pdo->query("SELECT MIN(sale_time) as first_sale, MAX(sale_time) as last_sale, COUNT(*) as count FROM sales");
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
