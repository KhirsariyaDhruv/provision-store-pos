<?php
// fix_db.php
require_once 'config/db.php';

try {
    echo "Attempting to add 'bill_number' column...\n";
    
    // Check if column exists
    $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name='sales' AND column_name='bill_number'");
    if ($stmt->fetch()) {
        echo "Column 'bill_number' already exists.\n";
    } else {
        // Add column
        $pdo->exec("ALTER TABLE sales ADD COLUMN bill_number INTEGER");
        echo "Column 'bill_number' added successfully.\n";
        
        // Backfill existing
        $pdo->exec("UPDATE sales SET bill_number = id WHERE bill_number IS NULL");
        echo "Backfilled existing records.\n";
        
        // Add NOT NULL constraint (optional, safer to leave nullable for now to avoid issues)
        // $pdo->exec("ALTER TABLE sales ALTER COLUMN bill_number SET NOT NULL");
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
