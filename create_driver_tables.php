<?php
require_once 'config.php';

echo "Creating driver tables...\n\n";

try {
    // Read and execute the SQL file
    $sql = file_get_contents('fix_driver_tables.sql');
    
    // Split by semicolon and execute each statement
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            $db->exec($statement);
            echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }
    
    echo "\n✅ Driver tables created successfully!\n";
    echo "\nTables created:\n";
    echo "- driver_payments (for tracking driver payment processing)\n";
    echo "- pickup_delivery (for managing delivery schedules)\n";
    echo "\nSample data inserted for testing.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
