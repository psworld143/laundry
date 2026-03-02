<?php
/**
 * Database Setup Script
 * Run this ONCE to create admin user and sample data
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Setup - LaundryPro</title>
    <script src='https://cdn.tailwindcss.com'></script>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
</head>
<body class='bg-gray-100 p-8'>
<div class='max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-8'>";

echo "<h1 class='text-3xl font-bold text-gray-800 mb-6'>LaundryPro Setup</h1>";

try {
    // Create admin user
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT IGNORE INTO users (username, name, email, password_hash, position) VALUES ('admin', 'Administrator', 'admin@laundry.com', ?, 'admin')");
    $stmt->execute([$admin_password]);
    
    echo "<div class='bg-green-100 border-l-4 border-green-500 p-4 mb-4'>
        <p class='text-green-800'><strong>✅ Admin user created:</strong></p>
        <p class='ml-4 mt-2'>Username: <code class='bg-white px-2 py-1 rounded'>admin</code></p>
        <p class='ml-4'>Password: <code class='bg-white px-2 py-1 rounded'>admin123</code></p>
    </div>";
    
    // Sample Services
    $services = [
        ['Wash & Fold', 'Regular wash and fold service', 50.00, 'wash_fold', 3],
        ['Dry Cleaning', 'Professional dry cleaning', 150.00, 'dry_clean', 24],
        ['Express Wash', 'Same day service', 100.00, 'express', 4],
        ['Ironing Only', 'Pressing and ironing', 30.00, 'ironing', 2],
    ];
    
    foreach ($services as $s) {
        $stmt = $db->prepare("INSERT IGNORE INTO services (service_name, description, base_price, service_type, estimated_duration) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute($s);
    }
    
    echo "<div class='bg-blue-100 border-l-4 border-blue-500 p-4 mb-4'>
        <p class='text-blue-800'><strong>✅ Sample services created:</strong> " . count($services) . " services</p>
    </div>";
    
    // Sample Machines
    $machines = [
        ['Washer-01', 'washing_machine', 'Samsung', 'WW10', '10kg', 'Floor 1', 'available'],
        ['Dryer-01', 'dryer', 'LG', 'RC9055', '9kg', 'Floor 1', 'available'],
        ['Iron-01', 'iron', 'Philips', 'GC4567', '2400W', 'Floor 2', 'available'],
    ];
    
    foreach ($machines as $m) {
        $stmt = $db->prepare("INSERT IGNORE INTO machines (machine_name, machine_type, brand, model, capacity, location, status, purchase_date) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())");
        $stmt->execute($m);
    }
    
    echo "<div class='bg-purple-100 border-l-4 border-purple-500 p-4 mb-4'>
        <p class='text-purple-800'><strong>✅ Sample machines created:</strong> " . count($machines) . " machines</p>
    </div>";
    
    // Sample Customers (10 customers)
    $customers = [
        ['john.doe', 'John Doe', 'john.doe@email.com', '+63 912 345 6789'],
        ['jane.smith', 'Jane Smith', 'jane.smith@email.com', '+63 923 456 7890'],
        ['mike.johnson', 'Mike Johnson', 'mike.johnson@email.com', '+63 934 567 8901'],
        ['sarah.williams', 'Sarah Williams', 'sarah.williams@email.com', '+63 945 678 9012'],
        ['david.brown', 'David Brown', 'david.brown@email.com', '+63 956 789 0123'],
        ['emily.davis', 'Emily Davis', 'emily.davis@email.com', '+63 967 890 1234'],
        ['chris.wilson', 'Chris Wilson', 'chris.wilson@email.com', '+63 978 901 2345'],
        ['lisa.garcia', 'Lisa Garcia', 'lisa.garcia@email.com', '+63 989 012 3456'],
        ['robert.martinez', 'Robert Martinez', 'robert.martinez@email.com', '+63 990 123 4567'],
        ['amanda.lee', 'Amanda Lee', 'amanda.lee@email.com', '+63 901 234 5678'],
    ];
    
    $customerPassword = password_hash('password123', PASSWORD_DEFAULT);
    $customersCreated = 0;
    
    foreach ($customers as $c) {
        try {
            $stmt = $db->prepare("INSERT INTO users (username, name, email, phone_number, password_hash, position) VALUES (?, ?, ?, ?, ?, 'user')");
            $stmt->execute([$c[0], $c[1], $c[2], $c[3], $customerPassword]);
            $customersCreated++;
        } catch (PDOException $e) {
            // Skip if already exists
        }
    }
    
    echo "<div class='bg-teal-100 border-l-4 border-teal-500 p-4 mb-4'>
        <p class='text-teal-800'><strong>✅ Sample customers created:</strong> {$customersCreated} customers</p>
        <p class='text-sm text-teal-700 mt-2'>All customers have password: <code class='bg-white px-2 py-1 rounded'>password123</code></p>
    </div>";
    
    // Sample Staff Members
    $staffMembers = [
        ['Maria Santos', 'manager', 'maria.santos@laundrypro.com', '+63 917 123 4567', '2024-01-15', 25000.00],
        ['Juan Cruz', 'operator', 'juan.cruz@laundrypro.com', '+63 918 234 5678', '2024-02-20', 18000.00],
        ['Anna Reyes', 'operator', 'anna.reyes@laundrypro.com', '+63 919 345 6789', '2024-03-10', 18000.00],
        ['Carlos Mendoza', 'driver', 'carlos.mendoza@laundrypro.com', '+63 920 456 7890', '2024-04-05', 16000.00],
        ['Rosa Garcia', 'cashier', 'rosa.garcia@laundrypro.com', '+63 921 567 8901', '2024-05-12', 17000.00],
        ['Pedro Fernandez', 'driver', 'pedro.fernandez@laundrypro.com', '+63 922 678 9012', '2024-06-01', 16000.00]
    ];
    
    $staffCreated = 0;
    foreach ($staffMembers as $s) {
        try {
            $stmt = $db->prepare("INSERT INTO staff (name, position, email, contact_number, hire_date, salary, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute($s);
            $staffCreated++;
        } catch (PDOException $e) {
            // Skip if already exists
        }
    }
    
    echo "<div class='bg-indigo-100 border-l-4 border-indigo-500 p-4 mb-4'>
        <p class='text-indigo-800'><strong>✅ Sample staff created:</strong> {$staffCreated} staff members</p>
        <p class='text-sm text-indigo-700 mt-2'>1 Manager, 2 Operators, 2 Drivers, 1 Cashier</p>
    </div>";
    
    echo "<div class='bg-yellow-100 border-l-4 border-yellow-500 p-4 mb-4'>
        <p class='text-yellow-800'><strong>⚠️ Setup Complete!</strong></p>
        <p class='mt-2'>You can now login and start using the system.</p>
    </div>";
    
    echo "<div class='grid grid-cols-2 md:grid-cols-7 gap-2'>
        <a href='login.php' class='inline-flex items-center justify-center bg-blue-500 hover:bg-blue-600 text-white font-semibold px-3 py-2 rounded-lg transition transform hover:scale-105 shadow-lg text-xs'>
            <i class='fas fa-sign-in-alt mr-1'></i>Login
        </a>
        <a href='pages/admin/customers.php' class='inline-flex items-center justify-center bg-green-500 hover:bg-green-600 text-white font-semibold px-3 py-2 rounded-lg transition transform hover:scale-105 shadow-lg text-xs'>
            <i class='fas fa-users mr-1'></i>Customers
        </a>
        <a href='pages/admin/staff.php' class='inline-flex items-center justify-center bg-purple-500 hover:bg-purple-600 text-white font-semibold px-3 py-2 rounded-lg transition transform hover:scale-105 shadow-lg text-xs'>
            <i class='fas fa-user-tie mr-1'></i>Staff
        </a>
        <a href='pages/admin/services.php' class='inline-flex items-center justify-center bg-orange-500 hover:bg-orange-600 text-white font-semibold px-3 py-2 rounded-lg transition transform hover:scale-105 shadow-lg text-xs'>
            <i class='fas fa-concierge-bell mr-1'></i>Services
        </a>
        <a href='pages/admin/machines.php' class='inline-flex items-center justify-center bg-teal-500 hover:bg-teal-600 text-white font-semibold px-3 py-2 rounded-lg transition transform hover:scale-105 shadow-lg text-xs'>
            <i class='fas fa-cogs mr-1'></i>Machines
        </a>
        <a href='pages/admin/inventory.php' class='inline-flex items-center justify-center bg-indigo-500 hover:bg-indigo-600 text-white font-semibold px-3 py-2 rounded-lg transition transform hover:scale-105 shadow-lg text-xs'>
            <i class='fas fa-boxes mr-1'></i>Inventory
        </a>
        <a href='pages/admin/orders.php' class='inline-flex items-center justify-center bg-pink-500 hover:bg-pink-600 text-white font-semibold px-3 py-2 rounded-lg transition transform hover:scale-105 shadow-lg text-xs'>
            <i class='fas fa-shopping-cart mr-1'></i>Orders
        </a>
    </div>";
    
    echo "<div class='mt-6 bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-6 border-2 border-blue-200'>
        <h3 class='text-lg font-bold text-gray-800 mb-3 text-center'>
            <i class='fas fa-seedling text-green-500 mr-2'></i>Need More Sample Data?
        </h3>
        <div class='grid grid-cols-1 md:grid-cols-4 gap-3'>
            <a href='seed_services.php' class='inline-flex items-center justify-center bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white font-semibold px-6 py-3 rounded-lg transition transform hover:scale-105 shadow-lg'>
                <i class='fas fa-concierge-bell mr-2'></i>Seed Services
            </a>
            <a href='seed_machines.php' class='inline-flex items-center justify-center bg-gradient-to-r from-teal-500 to-cyan-600 hover:from-teal-600 hover:to-cyan-700 text-white font-semibold px-6 py-3 rounded-lg transition transform hover:scale-105 shadow-lg'>
                <i class='fas fa-cogs mr-2'></i>Seed Machines
            </a>
            <a href='seed_inventory.php' class='inline-flex items-center justify-center bg-gradient-to-r from-indigo-500 to-blue-600 hover:from-indigo-600 hover:to-blue-700 text-white font-semibold px-6 py-3 rounded-lg transition transform hover:scale-105 shadow-lg'>
                <i class='fas fa-boxes mr-2'></i>Seed Inventory
            </a>
            <a href='seed_orders.php' class='inline-flex items-center justify-center bg-gradient-to-r from-pink-500 to-red-600 hover:from-pink-600 hover:to-red-700 text-white font-semibold px-6 py-3 rounded-lg transition transform hover:scale-105 shadow-lg'>
                <i class='fas fa-shopping-cart mr-2'></i>Seed Orders
            </a>
        </div>
    </div>";
    
    echo "<div class='mt-6 p-6 bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl border-2 border-gray-200'>
        <h3 class='font-bold text-gray-800 mb-4 text-lg flex items-center'>
            <i class='fas fa-key text-blue-500 mr-2'></i>Sample Login Credentials
        </h3>
        <div class='grid grid-cols-1 md:grid-cols-3 gap-3 text-sm'>
            <div class='bg-white p-4 rounded-lg shadow-sm'>
                <p class='text-gray-500 text-xs mb-1'>Administrator</p>
                <p class='font-mono font-bold text-blue-600'>admin / admin123</p>
            </div>
            <div class='bg-white p-4 rounded-lg shadow-sm'>
                <p class='text-gray-500 text-xs mb-1'>Customer</p>
                <p class='font-mono font-bold text-green-600'>john.doe / password123</p>
            </div>
            <div class='bg-white p-4 rounded-lg shadow-sm'>
                <p class='text-gray-500 text-xs mb-1'>Total Sample Data</p>
                <p class='font-bold text-purple-600'>1 Admin, 10 Customers, 6 Staff</p>
            </div>
        </div>
    </div>";
    
} catch (PDOException $e) {
    echo "<div class='bg-red-100 border-l-4 border-red-500 p-4 mb-4'>
        <p class='text-red-800'><strong>❌ Error:</strong> " . $e->getMessage() . "</p>
    </div>";
}

echo "</div></body></html>";
?>

