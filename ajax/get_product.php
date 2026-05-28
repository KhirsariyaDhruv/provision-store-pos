<?php
// ajax/get_product.php
require_once '../includes/auth_check.php'; // Ensures session & user_id
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$owner_id = get_owner_id();
$query = $_GET['query'] ?? '';

if (!$query) {
    echo json_encode(['success' => false, 'message' => 'Empty query']);
    exit;
}

// Search by Exact Barcode or Name (Partial Match for Name)
$stmt = $pdo->prepare("SELECT id, name, price, barcode, stock, expiry_date FROM products WHERE user_id = ? AND (barcode = ? OR name LIKE ?) AND barcode_active = TRUE LIMIT 1");
$stmt->execute([$owner_id, $query, "%$query%"]);
$product = $stmt->fetch();

if ($product) {
    // Check Expiry
    if (!empty($product['expiry_date'])) {
        $expiry = strtotime($product['expiry_date']);
        $today = strtotime(date('Y-m-d')); // Compare with today midnight
        
        if ($expiry < $today) {
            echo json_encode([
                'success' => false, 
                'message' => 'Product Expired! Expiry: ' . date('d M Y', $expiry)
            ]);
            exit;
        }
    }

    echo json_encode(['success' => true, 'product' => $product]);
} else {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
}
?>
