<?php
// export_shop_data.php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// Ensure only admins can download full shop data
if ($_SESSION['role'] !== 'admin') {
    die("Unauthorized Access.");
}

$user_id = $_SESSION['user_id'];
$shop_name = $_SESSION['shop_name'] ?? 'Shop';

// Set headers to force download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . str_replace(' ', '_', $shop_name) . '_Full_Data_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

// --- 1. SHOP PROFILE INFO ---
fputcsv($output, ['--- SHOP PROFILE ---']);
$stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);
if ($profile) {
    fputcsv($output, array_keys($profile));
    fputcsv($output, $profile);
}
fputcsv($output, []); // Spacer

// --- 2. PRODUCTS ---
fputcsv($output, ['--- PRODUCTS ---']);
$stmt = $pdo->prepare("SELECT name, category, price, weight, stock, barcode FROM products WHERE user_id = ?");
$stmt->execute([$user_id]);
fputcsv($output, ['Name', 'Category', 'Price', 'Weight', 'Stock', 'Barcode']);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}
fputcsv($output, []); // Spacer

// --- 3. CUSTOMERS ---
fputcsv($output, ['--- CUSTOMERS ---']);
$stmt = $pdo->prepare("SELECT name, phone, total_due, created_at FROM customers WHERE user_id = ?");
$stmt->execute([$user_id]);
fputcsv($output, ['Name', 'Phone', 'Total Due', 'Created At']);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}
fputcsv($output, []); // Spacer

// --- 4. SALES SUMMARY ---
fputcsv($output, ['--- SALES SUMMARY ---']);
$stmt = $pdo->prepare("SELECT id, bill_number, total_amount, payment_type, status, sale_time FROM sales WHERE user_id = ? ORDER BY sale_time DESC");
$stmt->execute([$user_id]);
fputcsv($output, ['Sale ID', 'Bill Number', 'Total Amount', 'Payment Type', 'Status', 'Date']);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}

fclose($output);
exit;
