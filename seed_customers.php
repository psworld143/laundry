<?php
/**
 * Seed Sample Customers
 * Creates 10 sample customer accounts for testing
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Seed Customers - LaundryPro</title>
    <script src='https://cdn.tailwindcss.com'></script>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body class='bg-gradient-to-br from-blue-500 to-purple-600 min-h-screen p-8'>
<div class='max-w-4xl mx-auto bg-white rounded-2xl shadow-2xl p-8'>";

echo "<div class='text-center mb-8'>
    <div class='bg-gradient-to-r from-blue-500 to-purple-600 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4'>
        <i class='fas fa-users text-white text-4xl'></i>
    </div>
    <h1 class='text-3xl font-bold text-gray-800'>Customer Data Seeder</h1>
    <p class='text-gray-600 mt-2'>Creating 10 sample customer accounts...</p>
</div>";

try {
    // Sample customer data
    $customers = [
        [
            'username' => 'john.doe',
            'name' => 'John Doe',
            'email' => 'john.doe@email.com',
            'phone' => '+63 912 345 6789',
            'password' => 'password123'
        ],
        [
            'username' => 'jane.smith',
            'name' => 'Jane Smith',
            'email' => 'jane.smith@email.com',
            'phone' => '+63 923 456 7890',
            'password' => 'password123'
        ],
        [
            'username' => 'mike.johnson',
            'name' => 'Mike Johnson',
            'email' => 'mike.johnson@email.com',
            'phone' => '+63 934 567 8901',
            'password' => 'password123'
        ],
        [
            'username' => 'sarah.williams',
            'name' => 'Sarah Williams',
            'email' => 'sarah.williams@email.com',
            'phone' => '+63 945 678 9012',
            'password' => 'password123'
        ],
        [
            'username' => 'david.brown',
            'name' => 'David Brown',
            'email' => 'david.brown@email.com',
            'phone' => '+63 956 789 0123',
            'password' => 'password123'
        ],
        [
            'username' => 'emily.davis',
            'name' => 'Emily Davis',
            'email' => 'emily.davis@email.com',
            'phone' => '+63 967 890 1234',
            'password' => 'password123'
        ],
        [
            'username' => 'chris.wilson',
            'name' => 'Chris Wilson',
            'email' => 'chris.wilson@email.com',
            'phone' => '+63 978 901 2345',
            'password' => 'password123'
        ],
        [
            'username' => 'lisa.garcia',
            'name' => 'Lisa Garcia',
            'email' => 'lisa.garcia@email.com',
            'phone' => '+63 989 012 3456',
            'password' => 'password123'
        ],
        [
            'username' => 'robert.martinez',
            'name' => 'Robert Martinez',
            'email' => 'robert.martinez@email.com',
            'phone' => '+63 990 123 4567',
            'password' => 'password123'
        ],
        [
            'username' => 'amanda.lee',
            'name' => 'Amanda Lee',
            'email' => 'amanda.lee@email.com',
            'phone' => '+63 901 234 5678',
            'password' => 'password123'
        ]
    ];

    $created = 0;
    $skipped = 0;

    echo "<div class='space-y-3 mb-6'>";

    foreach ($customers as $customer) {
        try {
            $passwordHash = password_hash($customer['password'], PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("
                INSERT INTO users (username, name, email, phone_number, password_hash, position, is_active) 
                VALUES (?, ?, ?, ?, ?, 'user', 1)
            ");
            
            $stmt->execute([
                $customer['username'],
                $customer['name'],
                $customer['email'],
                $customer['phone'],
                $passwordHash
            ]);
            
            $created++;
            
            echo "<div class='bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg'>
                <div class='flex items-center'>
                    <div class='bg-green-500 w-10 h-10 rounded-full flex items-center justify-center text-white mr-4'>
                        <i class='fas fa-check'></i>
                    </div>
                    <div class='flex-1'>
                        <p class='font-semibold text-green-800'>{$customer['name']}</p>
                        <p class='text-sm text-green-700'>Username: <code class='bg-white px-2 py-1 rounded'>{$customer['username']}</code> • Email: {$customer['email']}</p>
                    </div>
                </div>
            </div>";
            
        } catch (PDOException $e) {
            $skipped++;
            
            echo "<div class='bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-r-lg'>
                <div class='flex items-center'>
                    <div class='bg-yellow-500 w-10 h-10 rounded-full flex items-center justify-center text-white mr-4'>
                        <i class='fas fa-info'></i>
                    </div>
                    <div class='flex-1'>
                        <p class='font-semibold text-yellow-800'>{$customer['name']}</p>
                        <p class='text-sm text-yellow-700'>Skipped - Already exists</p>
                    </div>
                </div>
            </div>";
        }
    }

    echo "</div>";

    // Summary
    echo "<div class='bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl p-6 text-white shadow-xl'>
        <div class='flex items-center justify-between'>
            <div>
                <h2 class='text-2xl font-bold mb-2'>Seeding Complete! 🎉</h2>
                <div class='space-y-1 text-blue-100'>
                    <p><i class='fas fa-check-circle mr-2'></i>Created: <strong class='text-white'>{$created}</strong> new customers</p>
                    <p><i class='fas fa-info-circle mr-2'></i>Skipped: <strong class='text-white'>{$skipped}</strong> existing accounts</p>
                    <p><i class='fas fa-users mr-2'></i>Total: <strong class='text-white'>10</strong> sample customers</p>
                </div>
            </div>
            <div class='hidden md:block'>
                <i class='fas fa-user-friends text-6xl opacity-20'></i>
            </div>
        </div>
    </div>";

    // Login Information Card
    echo "<div class='mt-6 bg-blue-50 border-2 border-blue-200 rounded-xl p-6'>
        <h3 class='font-bold text-blue-900 mb-4 flex items-center'>
            <i class='fas fa-key mr-2'></i>Login Credentials for All Sample Customers
        </h3>
        <div class='bg-white rounded-lg p-4 mb-3'>
            <p class='text-sm text-gray-700 mb-2'><strong>Password for ALL customers:</strong></p>
            <code class='bg-gray-100 px-4 py-2 rounded text-lg font-bold text-blue-600'>password123</code>
        </div>
        <div class='grid grid-cols-1 md:grid-cols-2 gap-3 text-sm'>
            <div class='bg-white rounded p-3'>
                <i class='fas fa-user text-blue-500 mr-2'></i><code>john.doe</code>
            </div>
            <div class='bg-white rounded p-3'>
                <i class='fas fa-user text-blue-500 mr-2'></i><code>jane.smith</code>
            </div>
            <div class='bg-white rounded p-3'>
                <i class='fas fa-user text-blue-500 mr-2'></i><code>mike.johnson</code>
            </div>
            <div class='bg-white rounded p-3'>
                <i class='fas fa-user text-blue-500 mr-2'></i><code>sarah.williams</code>
            </div>
            <div class='bg-white rounded p-3'>
                <i class='fas fa-user text-blue-500 mr-2'></i><code>david.brown</code>
            </div>
            <div class='bg-white rounded p-3'>
                <i class='fas fa-user text-blue-500 mr-2'></i><code>emily.davis</code>
            </div>
            <div class='bg-white rounded p-3'>
                <i class='fas fa-user text-blue-500 mr-2'></i><code>chris.wilson</code>
            </div>
            <div class='bg-white rounded p-3'>
                <i class='fas fa-user text-blue-500 mr-2'></i><code>lisa.garcia</code>
            </div>
            <div class='bg-white rounded p-3'>
                <i class='fas fa-user text-blue-500 mr-2'></i><code>robert.martinez</code>
            </div>
            <div class='bg-white rounded p-3'>
                <i class='fas fa-user text-blue-500 mr-2'></i><code>amanda.lee</code>
            </div>
        </div>
    </div>";

    // Action Buttons
    echo "<div class='mt-8 flex gap-4 justify-center'>
        <a href='login.php' class='inline-flex items-center bg-blue-500 hover:bg-blue-600 text-white font-semibold px-6 py-3 rounded-lg shadow-lg hover:shadow-xl transition transform hover:scale-105'>
            <i class='fas fa-sign-in-alt mr-2'></i>Go to Login
        </a>
        <a href='pages/admin/customers.php' class='inline-flex items-center bg-green-500 hover:bg-green-600 text-white font-semibold px-6 py-3 rounded-lg shadow-lg hover:shadow-xl transition transform hover:scale-105'>
            <i class='fas fa-users mr-2'></i>View Customers
        </a>
    </div>";
    
} catch (PDOException $e) {
    echo "<div class='bg-red-100 border-l-4 border-red-500 p-6 rounded-r-lg'>
        <div class='flex items-center'>
            <i class='fas fa-exclamation-triangle text-red-500 text-3xl mr-4'></i>
            <div>
                <p class='font-bold text-red-800 text-lg'>Error Creating Customers</p>
                <p class='text-red-700 text-sm mt-1'>" . $e->getMessage() . "</p>
            </div>
        </div>
    </div>";
}

echo "</div></body></html>";
?>

