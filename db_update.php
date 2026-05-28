<?php
// db_update.php
require_once 'config/db.php';

echo "<html><head><title>DB Update</title><style>body{font-family:sans-serif;padding:2rem;}</style></head><body>";
echo "<h2>Database Updater</h2>";

try {
    // 1. Check if column exists
    echo "Checking for 'bill_number' in 'sales' table...<br>";
    $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name='sales' AND column_name='bill_number'");
    
    if ($stmt->fetch()) {
        echo "<b style='color:green'>Column 'bill_number' ALREADY EXISTS.</b><br>";
    } else {
        // 2. Add column
        echo "Column missing. Adding it now...<br>";
        $pdo->exec("ALTER TABLE sales ADD COLUMN bill_number INTEGER");
        echo "<b style='color:green'>Column 'bill_number' ADDED SUCCESSFULLY.</b><br>";
        
        // 3. Backfill
        echo "Backfilling existing records...<br>";
        $pdo->exec("UPDATE sales SET bill_number = id WHERE bill_number IS NULL");
        echo "Backfill complete.<br>";
    }
    
    echo "<hr><h3>Verification:</h3>";
    $test = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name='sales' AND column_name='bill_number'")->fetch();
    if ($test) {
        echo "<h1 style='color:green'>&#10004; SYSTEM IS READY.</h1>";
        echo "<p>The database is fixed. You can tell the AI to re-enable the feature now.</p>";
    } else {
        echo "<h1 style='color:red'>&#10006; UPDATE FAILED.</h1>";
    }

} catch (PDOException $e) {
    echo "<h3 style='color:red'>Error: " . $e->getMessage() . "</h3>";
}

echo "</body></html>";
?>
