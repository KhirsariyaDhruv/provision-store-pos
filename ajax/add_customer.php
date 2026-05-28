<?php
// ajax/add_customer.php
require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$owner_id = get_owner_id();

$name = trim($data['name'] ?? '');
$phone = trim($data['phone'] ?? '');

if (!$name) {
    echo json_encode(['success' => false, 'message' => 'Customer name is required']);
    exit;
}

try {
    // Check duplicate phone if provided
    if ($phone) {
        $check = $pdo->prepare("SELECT id FROM customers WHERE user_id = ? AND phone = ?");
        $check->execute([$owner_id, $phone]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Customer with this phone already exists']);
            exit;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO customers (user_id, name, phone, total_due) VALUES (?, ?, ?, 0.00)");
    $stmt->execute([$owner_id, $name, $phone]);
    $id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true, 
        'customer' => [
            'id' => $id,
            'name' => $name,
            'phone' => $phone
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>
