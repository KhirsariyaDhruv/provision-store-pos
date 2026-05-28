<?php
// import_shop_data.php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

if ($_SESSION['role'] !== 'admin') {
    die("Unauthorized Access.");
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['import_file'])) {
    $file = $_FILES['import_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "File upload failed.";
    } else {
        $handle = fopen($file['tmp_name'], 'r');
        if ($handle) {
            $pdo->beginTransaction();
            try {
                $section = '';
                $headers = [];

                while (($data = fgetcsv($handle)) !== FALSE) {
                    if (empty($data) || empty($data[0])) continue;

                    // Detect Section
                    if (strpos($data[0], '---') === 0) {
                        $section = trim($data[0], '- ');
                        $headers = [];
                        continue;
                    }

                    // Handle Section Data
                    switch ($section) {
                        case 'SHOP PROFILE':
                            if (empty($headers)) {
                                $headers = $data;
                            } else {
                                $profile_data = array_combine($headers, $data);
                                $stmt = $pdo->prepare("UPDATE user_profiles SET full_name = ?, shop_name = ?, phone = ?, address = ?, updated_at = NOW() WHERE user_id = ?");
                                $stmt->execute([
                                    $profile_data['full_name'], 
                                    $profile_data['shop_name'], 
                                    $profile_data['phone'], 
                                    $profile_data['address'], 
                                    $user_id
                                ]);
                                $_SESSION['shop_name'] = $profile_data['shop_name'];
                            }
                            break;

                        case 'PRODUCTS':
                            if (empty($headers)) {
                                $headers = $data;
                            } else {
                                $row = array_combine($headers, $data);
                                // Check if product already exists by name for this user
                                $check = $pdo->prepare("SELECT id FROM products WHERE name = ? AND user_id = ?");
                                $check->execute([$row['Name'], $user_id]);
                                if (!$check->fetch()) {
                                    $stmt = $pdo->prepare("INSERT INTO products (user_id, name, category, price, weight, stock, barcode) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                    $stmt->execute([$user_id, $row['Name'], $row['Category'], $row['Price'], $row['Weight'], $row['Stock'], $row['Barcode']]);
                                }
                            }
                            break;

                        case 'CUSTOMERS':
                            if (empty($headers)) {
                                $headers = $data;
                            } else {
                                $row = array_combine($headers, $data);
                                // Check if customer exists by phone for this user
                                $check = $pdo->prepare("SELECT id FROM customers WHERE phone = ? AND user_id = ?");
                                $check->execute([$row['Phone'], $user_id]);
                                if (!$check->fetch()) {
                                    $stmt = $pdo->prepare("INSERT INTO customers (user_id, name, phone, total_due, created_at) VALUES (?, ?, ?, ?, ?)");
                                    $stmt->execute([$user_id, $row['Name'], $row['Phone'], $row['Total Due'], $row['Created At']]);
                                }
                            }
                            break;

                        case 'SALES SUMMARY':
                            if (empty($headers)) {
                                $headers = $data;
                            } else {
                                $row = array_combine($headers, $data);
                                // Check if sale exists by bill number
                                $check = $pdo->prepare("SELECT id FROM sales WHERE bill_number = ? AND user_id = ?");
                                $check->execute([$row['Bill Number'], $user_id]);
                                if (!$check->fetch()) {
                                    $stmt = $pdo->prepare("INSERT INTO sales (user_id, bill_number, total_amount, payment_type, status, sale_time) VALUES (?, ?, ?, ?, ?, ?)");
                                    $stmt->execute([$user_id, $row['Bill Number'], $row['Total Amount'], $row['Payment Type'], $row['Status'], $row['Date']]);
                                }
                            }
                            break;
                    }
                }
                fclose($handle);
                $pdo->commit();
                $success = "Data imported successfully!";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Error during import: " . $e->getMessage();
            }
        } else {
            $error = "Could not open the uploaded file.";
        }
    }
}

// Redirect back with message
if ($success) $_SESSION['success'] = $success;
if ($error) $_SESSION['error'] = $error;
header("Location: profile.php");
exit;
