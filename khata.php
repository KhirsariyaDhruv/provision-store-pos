<?php
// khata.php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

$owner_id = get_owner_id();
$customer_id = $_GET['id'] ?? null;

if (!$customer_id) {
    redirect('customers.php');
}

// Handle Payment
$success = '';
$error = '';
// Handle Payment / Deposit
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pay_amount'])) {
    $amount = floatval($_POST['amount']);
    
    if ($amount > 0) {
        try {
            $pdo->beginTransaction();

            $cust = $pdo->prepare("SELECT total_due, wallet_balance FROM customers WHERE id = ?");
            $cust->execute([$customer_id]);
            $currentData = $cust->fetch();
            $currentDue = $currentData['total_due'];

            $amountForDue = 0;
            $amountForWallet = 0;

            if ($currentDue > 0) {
                if ($amount >= $currentDue) {
                    $amountForDue = $currentDue;
                    $amountForWallet = $amount - $currentDue;
                } else {
                    $amountForDue = $amount;
                    $amountForWallet = 0;
                }
            } else {
                $amountForWallet = $amount;
            }

            // 1. Clear Due if any
            if ($amountForDue > 0) {
                $stmt = $pdo->prepare("INSERT INTO khata_payments (user_id, customer_id, amount) VALUES (?, ?, ?)");
                $stmt->execute([$owner_id, $customer_id, $amountForDue]);

                $stmt = $pdo->prepare("UPDATE customers SET total_due = total_due - ? WHERE id = ?");
                $stmt->execute([$amountForDue, $customer_id]);
            }

            // 2. Add to Wallet if any
            if ($amountForWallet > 0) {
                $stmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, customer_id, type, amount, description) VALUES (?, ?, 'DEPOSIT', ?, 'Manual Deposit')");
                $stmt->execute([$owner_id, $customer_id, $amountForWallet]);

                $stmt = $pdo->prepare("UPDATE customers SET wallet_balance = COALESCE(wallet_balance, 0) + ? WHERE id = ?");
                $stmt->execute([$amountForWallet, $customer_id]);
            }

            $pdo->commit();
            $success = "Transaction successful. " . ($amountForDue > 0 ? "Debt cleared: ₹$amountForDue. " : "") . ($amountForWallet > 0 ? "Added to Wallet: ₹$amountForWallet." : "");
            
            // Refresh customer data
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND user_id = ?");
            $stmt->execute([$customer_id, $owner_id]);
            $customer = $stmt->fetch();

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "Invalid amount.";
    }
}

// Fetch Customer
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND user_id = ?");
$stmt->execute([$customer_id, $owner_id]);
$customer = $stmt->fetch();

if (!$customer) {
    redirect('customers.php', 'Customer not found.', 'error');
}

// Fetch Transactions (Merge Sales [Debit] and Payments [Credit])
// Sales (Debit) -> total_amount, sale_time, type='debit'
// Payments (Credit) -> amount, payment_time, type='credit'
$sql = "
    SELECT id, total_amount as amount, sale_time as created_at, 'debit' as type, 'Goods Purchase' as description 
    FROM sales 
    WHERE customer_id = ? AND payment_type = 'khata'
    UNION ALL
    SELECT id, amount, payment_time as created_at, 'credit' as type, 'Payment Received' as description 
    FROM khata_payments 
    WHERE customer_id = ?
    ORDER BY created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$customer_id, $customer_id]);
$transactions = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="d-flex justify-between items-center mb-3">
    <div>
        <a href="customers.php" style="color: var(--secondary-color); font-size: 0.9rem;"><i class="fas fa-arrow-left"></i> Back to Customers</a>
        <h2 style="margin-top: 5px;">Ledger: <?= htmlspecialchars($customer['name']) ?></h2>
    </div>
    <div class="text-right">
        <div style="margin-bottom: 8px;">
            <div style="font-size: 0.85rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.025em; margin-bottom: 2px;">Current Due</div>
            <div style="font-size: 1.75rem; font-weight: 800; color: <?= $customer['total_due'] > 0 ? '#dc2626' : '#166534' ?>; line-height: 1;">
                ₹<?= number_format($customer['total_due'], 2) ?>
            </div>
        </div>
        <div style="background: #eff6ff; padding: 6px 16px; border-radius: 999px; border: 1px solid #dbeafe; display: inline-flex; align-items: center; gap: 8px;">
            <span style="font-size: 0.8rem; color: #1e40af; text-transform: uppercase; font-weight: 600; letter-spacing: 0.025em;">Available Wallet Balance:</span>
            <span style="font-size: 1.25rem; font-weight: 800; color: #1e3a8a;">
                ₹<?= number_format($customer['wallet_balance'] ?? 0, 2) ?>
            </span>
        </div>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success" style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
        <?= $success ?>
    </div>
<?php endif; ?>

<div class="stats-grid khata-layout">
    
    <!-- Ledger Table -->
    <div class="card">
        <h3>Transaction History</h3>
        <div class="table-container">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; border-bottom: 1px solid #e2e8f0;">
                        <th style="padding: 0.75rem;">Date</th>
                        <th style="padding: 0.75rem;">Description</th>
                        <th style="padding: 0.75rem;">Type</th>
                        <th style="padding: 0.75rem; text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $t): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 0.75rem; color: #64748b; font-size: 0.9rem;">
                            <?= date('d M Y, h:i A', strtotime($t['created_at'])) ?>
                        </td>
                        <td style="padding: 0.75rem;">
                            <?php if($t['type'] == 'debit'): ?>
                                <a href="#" style="color: var(--primary-color); text-decoration: none;">Sale #<?= $t['id'] ?></a>
                            <?php else: ?>
                                Payment Received
                            <?php endif; ?>
                        </td>
                        <td style="padding: 0.75rem;">
                            <?php if($t['type'] == 'debit'): ?>
                                <span class="badge" style="background: #fee2e2; color: #991b1b; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem;">Debit</span>
                            <?php else: ?>
                                <span class="badge" style="background: #dcfce7; color: #166534; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem;">Credit</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 0.75rem; text-align: right; font-weight: bold; color: <?= $t['type'] == 'debit' ? '#dc2626' : '#166534' ?>;">
                            <?= $t['type'] == 'debit' ? '+' : '-' ?> ₹<?= number_format($t['amount'], 2) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Wallet & Settlement Actions -->
    <div class="card">
        <h3>Actions</h3>
        
        <!-- Wallet Balance Display -->
        <div style="background: #eff6ff; padding: 1rem; border-radius: 8px; border: 1px solid #dbeafe; margin-bottom: 1.5rem;">
            <div style="font-size: 0.9rem; color: #1e40af;">Available Wallet Balance</div>
            <div style="font-size: 1.8rem; font-weight: bold; color: #1e3a8a;">
                ₹<?= number_format($customer['wallet_balance'] ?? 0, 2) ?>
            </div>
            <p style="font-size: 0.8rem; color: #60a5fa; margin-top: 5px;">Prepaid amount available for future purchases.</p>
        </div>

        <!-- Deposit / Payment Form -->
        <form method="POST">
            <input type="hidden" name="pay_amount" value="1">
            
            <div class="form-group">
                 <label class="form-label">Add Money (Deposit / Pay Due)</label>
                 <!-- If Due exists, explain priority -->
                 <?php if ($customer['total_due'] > 0): ?>
                    <p style="font-size: 0.85rem; color: #d97706; background: #fffbeb; padding: 8px; border-radius: 4px; border: 1px solid #fcd34d; margin-bottom: 10px;">
                        <i class="fas fa-info-circle"></i> Amount will first clear the Due (₹<?= $customer['total_due'] ?>). <br>Extra will go to Wallet.
                    </p>
                 <?php endif; ?>

                 <div class="d-flex justify-between items-center mb-1">
                    <label class="form-label" style="margin-bottom: 0;">Amount (₹)</label>
                    <?php if ($customer['total_due'] > 0): ?>
                    <button type="button" class="btn" style="padding: 2px 8px; font-size: 0.8rem; background: #fee2e2; color: #991b1b;" onclick="document.getElementById('payInput').value = '<?= $customer['total_due'] ?>'">
                        Clear Due Only
                    </button>
                    <?php endif; ?>
                 </div>
                 <input type="number" step="0.01" id="payInput" name="amount" class="form-control" placeholder="Enter amount..." required>
            </div>

            <button type="submit" class="btn" style="width: 100%; justify-content: center; background: #166534; color: white; padding: 12px;">
                <i class="fas fa-wallet" style="margin-right: 8px;"></i> Deposit / Pay
            </button>
        </form>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
