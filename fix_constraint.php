<?php
// fix_constraint.php
require_once 'config/db.php';

try {
    echo "Fixing constraints...\n";

    // 1. Drop existing check constraint if possible
    // Note: Constraint name is sales_payment_type_check
    $pdo->exec("ALTER TABLE sales DROP CONSTRAINT IF EXISTS sales_payment_type_check");
    echo "Dropped old constraint.\n";

    // 2. Add new constraint including 'wallet'
    $pdo->exec("ALTER TABLE sales ADD CONSTRAINT sales_payment_type_check CHECK (payment_type IN ('cash', 'card', 'upi', 'pay_now', 'khata', 'wallet'))");
    echo "Added new constraint with 'wallet'.\n";

    // Also update wallet_transactions type check if it exists?
    // User didn't complain about that, but good to be safe.
    // Actually wallet_transactions is new, so it probably doesn't have a check constraint unless I added it.
    // I didn't add it in update_wallet_db.php (just simple create table).

    echo "Fix completed.\n";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
