<?php
require_once 'config.php';

echo "<!DOCTYPE html><html><head><title>Services Debug</title>";
echo "<script src='https://cdn.tailwindcss.com'></script>";
echo "</head><body class='bg-gray-100 p-8'>";
echo "<div class='max-w-4xl mx-auto bg-white rounded-lg shadow p-6'>";

echo "<h1 class='text-2xl font-bold mb-6'>Services Debug</h1>";

try {
    // Check services table
    $stmt = $db->query("SELECT COUNT(*) FROM services");
    $totalServices = $stmt->fetchColumn();
    echo "<p class='mb-2'><strong>Total Services:</strong> $totalServices</p>";
    
    // Check active services
    $stmt = $db->query("SELECT COUNT(*) FROM services WHERE is_active = 1");
    $activeServices = $stmt->fetchColumn();
    echo "<p class='mb-2'><strong>Active Services:</strong> $activeServices</p>";
    
    // Get all services
    $stmt = $db->query("SELECT * FROM services WHERE is_active = 1 ORDER BY service_type, service_name");
    $services = $stmt->fetchAll();
    
    if (empty($services)) {
        echo "<div class='bg-yellow-100 p-4 rounded mb-6'>";
        echo "<p class='text-yellow-800'>⚠️ No active services found in database</p>";
        echo "</div>";
        
        echo "<div class='bg-blue-100 p-4 rounded mb-6'>";
        echo "<p class='text-blue-800'>💡 You need to seed services first:</p>";
        echo "<a href='seed_services.php' class='bg-blue-500 text-white px-4 py-2 rounded mt-2 inline-block'>Seed Services</a>";
        echo "</div>";
    } else {
        echo "<div class='bg-green-100 p-4 rounded mb-6'>";
        echo "<p class='text-green-800'>✅ Found " . count($services) . " active services</p>";
        echo "</div>";
        
        echo "<table class='w-full border-collapse border'>";
        echo "<tr class='bg-gray-100'>";
        echo "<th class='border p-2 text-left'>ID</th>";
        echo "<th class='border p-2 text-left'>Name</th>";
        echo "<th class='border p-2 text-left'>Type</th>";
        echo "<th class='border p-2 text-left'>Price</th>";
        echo "<th class='border p-2 text-left'>Duration</th>";
        echo "</tr>";
        
        foreach ($services as $service) {
            echo "<tr>";
            echo "<td class='border p-2'>" . $service['service_id'] . "</td>";
            echo "<td class='border p-2'>" . htmlspecialchars($service['service_name']) . "</td>";
            echo "<td class='border p-2'>" . $service['service_type'] . "</td>";
            echo "<td class='border p-2'>₱" . number_format($service['base_price'], 2) . "</td>";
            echo "<td class='border p-2'>" . $service['estimated_duration'] . "hrs</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test API endpoint
    echo "<div class='mt-8'>";
    echo "<h2 class='text-xl font-bold mb-4'>API Test</h2>";
    
    $apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/laundry/api/public_services.php';
    $context = stream_context_create([
        'http' => [
            'timeout' => 5
        ]
    ]);
    
    $response = file_get_contents($apiUrl, false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "<div class='bg-green-100 p-4 rounded'>";
            echo "<p class='text-green-800'>✅ API working: Found " . $data['data']['total_count'] . " services</p>";
            echo "</div>";
        } else {
            echo "<div class='bg-red-100 p-4 rounded'>";
            echo "<p class='text-red-800'>❌ API Error: " . ($data['message'] ?? 'Unknown error') . "</p>";
            echo "</div>";
        }
    } else {
        echo "<div class='bg-red-100 p-4 rounded'>";
        echo "<p class='text-red-800'>❌ API not accessible at: $apiUrl</p>";
        echo "</div>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='bg-red-100 p-4 rounded'>";
    echo "<p class='text-red-800'>❌ Database Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<div class='mt-8 flex gap-4'>";
echo "<a href='services.php' class='bg-blue-500 text-white px-6 py-3 rounded-lg'>View Services Page</a>";
echo "<a href='seed_services.php' class='bg-green-500 text-white px-6 py-3 rounded-lg'>Seed Services</a>";
echo "</div>";

echo "</div></body></html>";
?>
