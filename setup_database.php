<?php
/**
 * Database Setup Script
 * Creates the laundry database and imports the basic schema
 */

// Define database constants directly since we can't include config.php yet
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');

// Connect to MySQL without specifying database first
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    echo "Connected to MySQL successfully\n";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS laundry CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    echo "✓ Database 'laundry' created or already exists\n";
    
    // Switch to the laundry database
    $pdo->exec("USE laundry");
    echo "✓ Switched to laundry database\n";
    
    // Read and execute the main schema file
    $schemaFile = __DIR__ . '/laundry_schema_backup.sql';
    if (file_exists($schemaFile)) {
        $schema = file_get_contents($schemaFile);
        
        // Remove comments and split into individual queries
        $queries = array_filter(array_map('trim', explode(';', $schema)));
        
        foreach ($queries as $query) {
            if (!empty($query) && !preg_match('/^--/', $query)) {
                try {
                    $pdo->exec($query);
                } catch (Exception $e) {
                    // Skip errors for tables that already exist
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        echo "Query error: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
        echo "✓ Schema imported successfully\n";
    } else {
        echo "⚠ Schema file not found: $schemaFile\n";
    }
    
    echo "\n=== DATABASE SETUP COMPLETE ===\n";
    echo "The 'laundry' database is now ready for use.\n";
    
} catch (PDOException $e) {
    echo "Database setup failed: " . $e->getMessage() . "\n";
}
?>
