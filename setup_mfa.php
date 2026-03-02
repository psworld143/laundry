<?php
require_once 'config.php';

echo "<!DOCTYPE html><html><head><title>MFA Setup</title>";
echo "<script src='https://cdn.tailwindcss.com'></script>";
echo "</head><body class='bg-gray-100 p-8'>";
echo "<div class='max-w-4xl mx-auto bg-white rounded-lg shadow p-6'>";

echo "<h1 class='text-2xl font-bold mb-6'>MFA Database Setup</h1>";

try {
    // Check if mfa_enabled column exists
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'mfa_enabled'");
    $mfaColumnExists = $stmt->fetch();
    
    if ($mfaColumnExists) {
        echo "<div class='bg-green-100 p-4 rounded mb-6'>";
        echo "<p class='text-green-800'>✅ MFA columns already exist in users table</p>";
        echo "</div>";
    } else {
        echo "<div class='bg-yellow-100 p-4 rounded mb-6'>";
        echo "<p class='text-yellow-800'>⚠️ MFA columns not found. Setting up MFA tables...</p>";
        echo "</div>";
        
        // Read and execute MFA setup SQL
        $mfaSqlFile = __DIR__ . '/database-schema/mfa_setup.sql';
        if (file_exists($mfaSqlFile)) {
            $sql = file_get_contents($mfaSqlFile);
            
            // Split SQL into individual statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $statement) {
                if (!empty($statement) && !preg_match('/^--/', $statement)) {
                    try {
                        $db->exec($statement);
                        echo "<p class='text-sm text-gray-600'>✓ Executed: " . substr($statement, 0, 50) . "...</p>";
                    } catch (Exception $e) {
                        echo "<p class='text-sm text-red-600'>✗ Error: " . $e->getMessage() . "</p>";
                    }
                }
            }
            
            echo "<div class='bg-green-100 p-4 rounded mt-6'>";
            echo "<p class='text-green-800'>✅ MFA setup completed successfully!</p>";
            echo "</div>";
        } else {
            echo "<div class='bg-red-100 p-4 rounded'>";
            echo "<p class='text-red-800'>❌ MFA setup SQL file not found</p>";
            echo "</div>";
        }
    }
    
    // Check other MFA tables
    $tables = ['user_mfa_sessions', 'mfa_audit_log'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->fetch();
        
        echo "<div class='mb-2'>";
        echo "<span class='font-medium'>$table:</span> ";
        echo $exists ? "<span class='text-green-600'>✅ Exists</span>" : "<span class='text-red-600'>❌ Missing</span>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='bg-red-100 p-4 rounded'>";
    echo "<p class='text-red-800'>❌ Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<div class='mt-8 flex gap-4'>";
echo "<a href='login.php' class='bg-blue-500 text-white px-6 py-3 rounded-lg'>Test Login</a>";
echo "<a href='setup.php' class='bg-gray-500 text-white px-6 py-3 rounded-lg'>Main Setup</a>";
echo "</div>";

echo "</div></body></html>";
?>
