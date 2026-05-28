<?php
// update_wallet_db.php
require_once 'config/db.php';

try {
    echo "Updating database for Wallet System...\n";

    // 1. Add wallet_balance to customers
    // check if exists first to avoid error? or just try catch
    try {
        $pdo->exec("ALTER TABLE customers ADD COLUMN wallet_balance DECIMAL(10,2) DEFAULT 0.00");
        echo "Added wallet_balance column.\n";
    } catch (PDOException $e) {
        echo "wallet_balance column might already exist or error: " . $e->getMessage() . "\n";
    }

    // 2. Create wallet_transactions table
    $sql = "CREATE TABLE IF NOT EXISTS wallet_transactions (
        id SERIAL PRIMARY KEY,
        user_id INT NOT NULL,
        customer_id INT NOT NULL,
        type VARCHAR(20) NOT NULL, -- DEPOSIT, PURCHASE
        amount DECIMAL(10,2) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Created wallet_transactions table.\n";

    echo "Database update completed successfully.\n";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
