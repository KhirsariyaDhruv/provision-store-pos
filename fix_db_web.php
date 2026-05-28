<?php
// fix_db_web.php
require_once 'config/db.php';

echo "<h1>Database Fixer</h1>";

try {
    // Check if column exists
    $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name='sales' AND column_name='bill_number'");
    if ($stmt->fetch()) {
        echo "<p style='color:green'>Column 'bill_number' already exists. You are good to go!</p>";
    } else {
        // Add column
        $pdo->exec("ALTER TABLE sales ADD COLUMN bill_number INTEGER");
        echo "<p style='color:blue'>Column 'bill_number' created.</p>";
        
        // Backfill
        $pdo->exec("UPDATE sales SET bill_number = id WHERE bill_number IS NULL");
        echo "<p style='color:blue'>Existing records updated.</p>";
        
        echo "<h2 style='color:green'>SUCCESS: Database updated successfully!</h2>";
    }

} catch (PDOException $e) {
    echo "<h2 style='color:red'>Error: " . $e->getMessage() . "</h2>";
}
echo "<a href='index.php'>Go Back to Dashboard</a>";
?>
