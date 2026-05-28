<?php
// export_sales.php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// Auth check is handled in auth_check.php, and it defines get_owner_id()

$user_id = get_owner_id();

// Set headers to force download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=sales_report_' . date('Y-m-d_H-i') . '.csv');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Output the column headings
fputcsv($output, ['Sale ID', 'Bill Number', 'Customer Name', 'Sale Time', 'Payment Type', 'Total Amount', 'Items Count']);

// Fetch Data
$sql = "SELECT s.id, s.bill_number, c.name as customer_name, s.sale_time, s.payment_type, s.total_amount, 
        (SELECT COUNT(*) FROM sale_items si WHERE si.sale_id = s.id) as items_count
        FROM sales s 
        LEFT JOIN customers c ON s.customer_id = c.id 
        WHERE s.user_id = ? 
        ORDER BY s.sale_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Format data: Text format prevents Excel ##### overflow for narrow columns
    $row['sale_time'] = date('d M Y, h:i A', strtotime($row['sale_time']));
    $row['payment_type'] = ucwords(str_replace('_', ' ', $row['payment_type']));
    fputcsv($output, $row);
}

fclose($output);
exit;
