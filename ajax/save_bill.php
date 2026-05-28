<?php
// ajax/save_bill.php
require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Get JSON Input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$owner_id = get_owner_id(); 
// Note: created_by is implicit in session but schema didn't ask for it in 'sales' table.
// User schema: sales(user_id, customer_id, total_amount, payment_type, status, sale_time)

$payment_method = $input['payment_method']; // 'cash' or 'khata'
$customer_id = $input['customer_id'] ?? null;
$cart = $input['cart'] ?? [];

if (count($cart) === 0) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit;
}

// Map payment method to schema types
$payment_type = ($payment_method === 'khata') ? 'khata' : (($payment_method === 'wallet') ? 'wallet' : 'pay_now');
$status = ($payment_type === 'khata') ? 'pending' : 'paid';

if (($payment_type === 'khata' || $payment_method === 'wallet') && !$customer_id) {
    echo json_encode(['success' => false, 'message' => 'Customer is required for this payment method']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Calculate Total & Validate Stock
    $total_amount = 0;
    foreach ($cart as $item) {
        $total_amount += ($item['price'] * $item['qty']);
        
        // Check Stock
        $stmt = $pdo->prepare("SELECT stock, expiry_date FROM products WHERE id = ? AND user_id = ? FOR UPDATE");
        $stmt->execute([$item['id'], $owner_id]);
        $product = $stmt->fetch();

        if (!$product) {
             throw new Exception("Product ID " . $item['id'] . " not found.");
        }

        $stock = $product['stock'];
        $expiry = $product['expiry_date'];
        
        // 1. Stock Check
        if ($stock < $item['qty']) {
            throw new Exception("Insufficient stock for product ID: " . $item['id'] . ". Available: " . $stock);
        }

        // 2. Expiry Check
        if (!empty($expiry)) {
            $today = date('Y-m-d');
            if ($expiry < $today) {
                throw new Exception("Cannot sell expired product (ID: " . $item['id'] . "). Expired on: " . $expiry);
            }
        }
    }



    // 1.5 Calculate Next Bill Number for User (SEQUENCE PER USER)
    // Get the highest bill_number for THIS user and add 1.
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(bill_number), 0) + 1 FROM sales WHERE user_id = ?");
    $stmt->execute([$owner_id]);
    $bill_number = $stmt->fetchColumn();

    // 2. Create Sale Record
    $stmt = $pdo->prepare("INSERT INTO sales (user_id, customer_id, total_amount, payment_type, status, bill_number) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$owner_id, $customer_id, $total_amount, $payment_type, $status, $bill_number]);
    $sale_id = $pdo->lastInsertId();

    // 3. Insert Sale Items & Deduct Stock
    $stmtItem = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, price_at_sale) VALUES (?, ?, ?, ?)");
    $stmtStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

    foreach ($cart as $item) {
        $stmtItem->execute([$sale_id, $item['id'], $item['qty'], $item['price']]);
        $stmtStock->execute([$item['qty'], $item['id']]);
    }

    // 4. Smart Khata: Deduct from Wallet First, then Add to Due
    if ($payment_type === 'khata' && $customer_id) {
        // Fetch Current Wallet Balance
        $stmtBal = $pdo->prepare("SELECT wallet_balance FROM customers WHERE id = ? AND user_id = ? FOR UPDATE");
        $stmtBal->execute([$customer_id, $owner_id]);
        $currentWallet = (float) $stmtBal->fetchColumn();

        $amountFromWallet = 0;
        $amountToDue = 0;

        if ($currentWallet > 0) {
            if ($currentWallet >= $total_amount) {
                $amountFromWallet = $total_amount;
                $amountToDue = 0;
            } else {
                $amountFromWallet = $currentWallet;
                $amountToDue = $total_amount - $currentWallet;
            }
        } else {
            $amountToDue = $total_amount;
        }

        // Apply Deductions/Additions
        if ($amountFromWallet > 0) {
            $stmtUpdateWallet = $pdo->prepare("UPDATE customers SET wallet_balance = wallet_balance - ? WHERE id = ?");
            $stmtUpdateWallet->execute([$amountFromWallet, $customer_id]);
            
            // Log Transaction
            $stmtLog = $pdo->prepare("INSERT INTO wallet_transactions (user_id, customer_id, type, amount, description) VALUES (?, ?, 'PURCHASE', ?, ?)");
            $desc = "Bill #" . $bill_number . " (Part of Khata)";
            $stmtLog->execute([$owner_id, $customer_id, $amountFromWallet, $desc]);
        }

        if ($amountToDue > 0) {
            $stmtUpdateDue = $pdo->prepare("UPDATE customers SET total_due = total_due + ? WHERE id = ?");
            $stmtUpdateDue->execute([$amountToDue, $customer_id]);
        }
        
        // Update Sale Status if fully paid by wallet
        if ($amountToDue == 0) {
            $stmtStatus = $pdo->prepare("UPDATE sales SET status = 'paid' WHERE id = ?");
            $stmtStatus->execute([$sale_id]);
        }
    }
    elseif ($payment_method === 'wallet') { 
        // Logic removed/merged above. Keeping generic 'wallet' if ever sent, but UI removed it.
        // Fallback to pure wallet deduction check as before just in case
         $stmtBal = $pdo->prepare("SELECT wallet_balance FROM customers WHERE id = ? AND user_id = ? FOR UPDATE");
         $stmtBal->execute([$customer_id, $owner_id]);
         $currentBal = (float)$stmtBal->fetchColumn();
         if ($currentBal < $total_amount) throw new Exception("Insufficient Wallet Balance");
         $pdo->query("UPDATE customers SET wallet_balance = wallet_balance - $total_amount WHERE id = $customer_id");
         
         $stmtLog = $pdo->prepare("INSERT INTO wallet_transactions (user_id, customer_id, type, amount, description) VALUES (?, ?, 'PURCHASE', ?, ?)");
         $stmtLog->execute([$owner_id, $customer_id, $total_amount, "Bill #".$bill_number]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'bill_id' => $bill_number, 'message' => 'Sale saved successfully!']);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
