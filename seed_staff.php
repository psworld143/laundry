<?php
/**
 * Seed Sample Staff Members
 * Creates sample staff for testing
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Seed Staff - LaundryPro</title>
    <script src='https://cdn.tailwindcss.com'></script>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body class='bg-gradient-to-br from-blue-500 to-purple-600 min-h-screen p-8'>
<div class='max-w-4xl mx-auto bg-white rounded-2xl shadow-2xl p-8'>";

echo "<div class='text-center mb-8'>
    <div class='bg-gradient-to-r from-blue-500 to-purple-600 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4'>
        <i class='fas fa-user-tie text-white text-4xl'></i>
    </div>
    <h1 class='text-3xl font-bold text-gray-800'>Staff Data Seeder</h1>
    <p class='text-gray-600 mt-2'>Creating sample staff members...</p>
</div>";

try {
    // Sample staff data
    $staffMembers = [
        [
            'name' => 'Maria Santos',
            'position' => 'manager',
            'email' => 'maria.santos@laundrypro.com',
            'contact' => '+63 917 123 4567',
            'hire_date' => '2024-01-15',
            'salary' => 25000.00
        ],
        [
            'name' => 'Juan Cruz',
            'position' => 'operator',
            'email' => 'juan.cruz@laundrypro.com',
            'contact' => '+63 918 234 5678',
            'hire_date' => '2024-02-20',
            'salary' => 18000.00
        ],
        [
            'name' => 'Anna Reyes',
            'position' => 'operator',
            'email' => 'anna.reyes@laundrypro.com',
            'contact' => '+63 919 345 6789',
            'hire_date' => '2024-03-10',
            'salary' => 18000.00
        ],
        [
            'name' => 'Carlos Mendoza',
            'position' => 'driver',
            'email' => 'carlos.mendoza@laundrypro.com',
            'contact' => '+63 920 456 7890',
            'hire_date' => '2024-04-05',
            'salary' => 16000.00
        ],
        [
            'name' => 'Rosa Garcia',
            'position' => 'cashier',
            'email' => 'rosa.garcia@laundrypro.com',
            'contact' => '+63 921 567 8901',
            'hire_date' => '2024-05-12',
            'salary' => 17000.00
        ],
        [
            'name' => 'Pedro Fernandez',
            'position' => 'driver',
            'email' => 'pedro.fernandez@laundrypro.com',
            'contact' => '+63 922 678 9012',
            'hire_date' => '2024-06-01',
            'salary' => 16000.00
        ],
    ];

    $created = 0;
    $skipped = 0;

    echo "<div class='space-y-3 mb-6'>";

    $positionIcons = [
        'manager' => ['fas fa-user-tie', 'purple'],
        'operator' => ['fas fa-cog', 'blue'],
        'driver' => ['fas fa-truck', 'green'],
        'cashier' => ['fas fa-cash-register', 'orange']
    ];

    foreach ($staffMembers as $member) {
        try {
            $stmt = $db->prepare("
                INSERT INTO staff (name, position, email, contact_number, hire_date, salary, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            
            $stmt->execute([
                $member['name'],
                $member['position'],
                $member['email'],
                $member['contact'],
                $member['hire_date'],
                $member['salary']
            ]);
            
            $created++;
            [$icon, $color] = $positionIcons[$member['position']];
            
            echo "<div class='bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg'>
                <div class='flex items-center'>
                    <div class='bg-{$color}-500 w-12 h-12 rounded-full flex items-center justify-center text-white mr-4'>
                        <i class='{$icon}'></i>
                    </div>
                    <div class='flex-1'>
                        <p class='font-semibold text-green-800'>{$member['name']}</p>
                        <p class='text-sm text-green-700'>
                            Position: <span class='font-medium'>" . ucfirst($member['position']) . "</span> • 
                            Salary: <span class='font-medium'>₱" . number_format($member['salary'], 2) . "</span>
                        </p>
                    </div>
                    <div class='text-right'>
                        <span class='text-xs text-green-600 font-medium'>✅ Created</span>
                    </div>
                </div>
            </div>";
            
        } catch (PDOException $e) {
            $skipped++;
            
            echo "<div class='bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-r-lg'>
                <div class='flex items-center'>
                    <div class='bg-yellow-500 w-12 h-12 rounded-full flex items-center justify-center text-white mr-4'>
                        <i class='fas fa-info'></i>
                    </div>
                    <div class='flex-1'>
                        <p class='font-semibold text-yellow-800'>{$member['name']}</p>
                        <p class='text-sm text-yellow-700'>Skipped - May already exist or email is duplicate</p>
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
                <h2 class='text-2xl font-bold mb-3'>Seeding Complete! 🎉</h2>
                <div class='space-y-1 text-blue-100'>
                    <p class='flex items-center'><i class='fas fa-check-circle mr-2 text-white'></i>Created: <strong class='text-white ml-2'>{$created}</strong> staff members</p>
                    <p class='flex items-center'><i class='fas fa-info-circle mr-2 text-white'></i>Skipped: <strong class='text-white ml-2'>{$skipped}</strong> existing</p>
                    <p class='flex items-center'><i class='fas fa-users mr-2 text-white'></i>Total: <strong class='text-white ml-2'>6</strong> sample staff</p>
                </div>
            </div>
            <div class='hidden md:block'>
                <i class='fas fa-user-friends text-6xl opacity-20'></i>
            </div>
        </div>
    </div>";

    // Staff Breakdown
    echo "<div class='mt-6 bg-gray-50 rounded-xl p-6'>
        <h3 class='font-bold text-gray-800 mb-4 flex items-center'>
            <i class='fas fa-list mr-2 text-blue-500'></i>Staff by Position
        </h3>
        <div class='grid grid-cols-2 md:grid-cols-4 gap-4'>
            <div class='bg-white rounded-lg p-4 text-center border-2 border-purple-200'>
                <i class='fas fa-user-tie text-3xl text-purple-500 mb-2'></i>
                <p class='text-2xl font-bold text-gray-800'>1</p>
                <p class='text-sm text-gray-600'>Manager</p>
            </div>
            <div class='bg-white rounded-lg p-4 text-center border-2 border-blue-200'>
                <i class='fas fa-cog text-3xl text-blue-500 mb-2'></i>
                <p class='text-2xl font-bold text-gray-800'>2</p>
                <p class='text-sm text-gray-600'>Operators</p>
            </div>
            <div class='bg-white rounded-lg p-4 text-center border-2 border-green-200'>
                <i class='fas fa-truck text-3xl text-green-500 mb-2'></i>
                <p class='text-2xl font-bold text-gray-800'>2</p>
                <p class='text-sm text-gray-600'>Drivers</p>
            </div>
            <div class='bg-white rounded-lg p-4 text-center border-2 border-orange-200'>
                <i class='fas fa-cash-register text-3xl text-orange-500 mb-2'></i>
                <p class='text-2xl font-bold text-gray-800'>1</p>
                <p class='text-sm text-gray-600'>Cashier</p>
            </div>
        </div>
    </div>";

    // Action Buttons
    echo "<div class='mt-8 flex gap-4 justify-center'>
        <a href='login.php' class='inline-flex items-center bg-blue-500 hover:bg-blue-600 text-white font-semibold px-6 py-3 rounded-lg shadow-lg hover:shadow-xl transition transform hover:scale-105'>
            <i class='fas fa-sign-in-alt mr-2'></i>Go to Login
        </a>
        <a href='pages/admin/staff.php' class='inline-flex items-center bg-green-500 hover:bg-green-600 text-white font-semibold px-6 py-3 rounded-lg shadow-lg hover:shadow-xl transition transform hover:scale-105'>
            <i class='fas fa-user-tie mr-2'></i>View Staff
        </a>
        <a href='check_data.php' class='inline-flex items-center bg-purple-500 hover:bg-purple-600 text-white font-semibold px-6 py-3 rounded-lg shadow-lg hover:shadow-xl transition transform hover:scale-105'>
            <i class='fas fa-database mr-2'></i>Check Data
        </a>
    </div>";
    
} catch (PDOException $e) {
    echo "<div class='bg-red-100 border-l-4 border-red-500 p-6 rounded-r-lg'>
        <div class='flex items-center'>
            <i class='fas fa-exclamation-triangle text-red-500 text-3xl mr-4'></i>
            <div>
                <p class='font-bold text-red-800 text-lg'>Error Creating Staff</p>
                <p class='text-red-700 text-sm mt-1'>" . $e->getMessage() . "</p>
            </div>
        </div>
    </div>";
}

echo "</div></body></html>";
?>

