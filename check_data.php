<?php
require_once 'config.php';

echo "<!DOCTYPE html><html><head><title>Data Check</title>";
echo "<script src='https://cdn.tailwindcss.com'></script>";
echo "</head><body class='bg-gray-100 p-8'>";
echo "<div class='max-w-4xl mx-auto bg-white rounded-lg shadow p-6'>";

echo "<h1 class='text-2xl font-bold mb-6'>Database Data Check</h1>";

// Check Customers
$customers = $db->query("SELECT * FROM users WHERE position = 'user' ORDER BY name")->fetchAll();
echo "<div class='mb-6'>
    <h2 class='text-xl font-bold mb-3'>Customers (" . count($customers) . ")</h2>";

if (empty($customers)) {
    echo "<div class='bg-yellow-100 p-4 rounded'>
        <p class='text-yellow-800'>❌ No customers found!</p>
        <a href='seed_customers.php' class='text-blue-600 underline'>Run customer seeder</a>
    </div>";
} else {
    echo "<div class='overflow-auto'><table class='w-full border'>
        <thead class='bg-gray-50'>
            <tr>
                <th class='border px-4 py-2'>ID</th>
                <th class='border px-4 py-2'>Username</th>
                <th class='border px-4 py-2'>Name</th>
                <th class='border px-4 py-2'>Email</th>
                <th class='border px-4 py-2'>Phone</th>
                <th class='border px-4 py-2'>Active</th>
            </tr>
        </thead>
        <tbody>";
    
    foreach ($customers as $c) {
        echo "<tr class='hover:bg-gray-50'>
            <td class='border px-4 py-2'>{$c['user_id']}</td>
            <td class='border px-4 py-2'>{$c['username']}</td>
            <td class='border px-4 py-2'>{$c['name']}</td>
            <td class='border px-4 py-2'>{$c['email']}</td>
            <td class='border px-4 py-2'>{$c['phone_number']}</td>
            <td class='border px-4 py-2'>" . ($c['is_active'] ? '✅' : '❌') . "</td>
        </tr>";
    }
    
    echo "</tbody></table></div>";
}
echo "</div>";

// Check Machines
$machines = $db->query("SELECT * FROM machines")->fetchAll();
echo "<div class='mb-6'>
    <h2 class='text-xl font-bold mb-3'>Machines (" . count($machines) . ")</h2>
    <p class='text-gray-600'>Found " . count($machines) . " machines</p>
</div>";

// Check Services
$services = $db->query("SELECT * FROM services")->fetchAll();
echo "<div class='mb-6'>
    <h2 class='text-xl font-bold mb-3'>Services (" . count($services) . ")</h2>
    <p class='text-gray-600'>Found " . count($services) . " services</p>
</div>";

// Check Staff
$staff = $db->query("SELECT * FROM staff")->fetchAll();
echo "<div class='mb-6'>
    <h2 class='text-xl font-bold mb-3'>Staff (" . count($staff) . ")</h2>
    <p class='text-gray-600'>Found " . count($staff) . " staff members</p>
</div>";

echo "<div class='mt-8 flex gap-4'>
    <a href='pages/admin/customers.php' class='bg-blue-500 text-white px-6 py-3 rounded-lg'>View Customers Page</a>
    <a href='seed_customers.php' class='bg-green-500 text-white px-6 py-3 rounded-lg'>Seed Customers</a>
    <a href='test_api.php' class='bg-purple-500 text-white px-6 py-3 rounded-lg'>Test APIs</a>
</div>";

echo "</div></body></html>";
?>

