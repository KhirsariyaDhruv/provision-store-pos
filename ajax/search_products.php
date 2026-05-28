<?php
// ajax/search_products.php
require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$owner_id = get_owner_id();
$query = $_GET['query'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // Search by Name or Barcode (Limit 10 results for dropdown)
    $stmt = $pdo->prepare("
        SELECT id, name, price, stock, barcode 
        FROM products 
        WHERE user_id = ? 
        AND barcode_active = TRUE 
        AND (name LIKE ? OR barcode LIKE ?)
        ORDER BY name ASC 
        LIMIT 10
    ");
    $stmt->execute([$owner_id, "%$query%", "%$query%"]);
    $products = $stmt->fetchAll();

    echo json_encode($products);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
