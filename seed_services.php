<?php
require_once 'config.php';

// Sample services to seed
$services = [
    [
        'service_name' => 'Regular Wash & Fold',
        'service_type' => 'wash_fold',
        'description' => 'Standard wash and fold service for everyday laundry. Perfect for regular clothing items.',
        'base_price' => 50.00,
        'estimated_duration' => 24,
        'is_active' => 1
    ],
    [
        'service_name' => 'Premium Wash & Fold',
        'service_type' => 'wash_fold',
        'description' => 'Premium wash and fold with fabric softener and special detergent. Delicate care for your clothes.',
        'base_price' => 75.00,
        'estimated_duration' => 24,
        'is_active' => 1
    ],
    [
        'service_name' => 'Dry Cleaning - Regular',
        'service_type' => 'dry_clean',
        'description' => 'Professional dry cleaning service for delicate garments, suits, and formal wear.',
        'base_price' => 150.00,
        'estimated_duration' => 48,
        'is_active' => 1
    ],
    [
        'service_name' => 'Dry Cleaning - Premium',
        'service_type' => 'dry_clean',
        'description' => 'Premium dry cleaning with stain removal treatment and special fabric care.',
        'base_price' => 250.00,
        'estimated_duration' => 72,
        'is_active' => 1
    ],
    [
        'service_name' => 'Ironing Service',
        'service_type' => 'ironing',
        'description' => 'Professional ironing service for shirts, pants, and other garments. Crisp and wrinkle-free.',
        'base_price' => 30.00,
        'estimated_duration' => 6,
        'is_active' => 1
    ],
    [
        'service_name' => 'Express Laundry',
        'service_type' => 'express',
        'description' => 'Super fast laundry service. Get your clothes cleaned and ready within 6 hours!',
        'base_price' => 100.00,
        'estimated_duration' => 6,
        'is_active' => 1
    ],
    [
        'service_name' => 'Express Dry Cleaning',
        'service_type' => 'express',
        'description' => 'Urgent dry cleaning service for last-minute needs. Fast and reliable.',
        'base_price' => 300.00,
        'estimated_duration' => 12,
        'is_active' => 1
    ],
    [
        'service_name' => 'Pickup & Delivery',
        'service_type' => 'pickup_delivery',
        'description' => 'Convenient pickup and delivery service for all your laundry needs. We collect from your location and deliver back to your doorstep.',
        'base_price' => 50.00,
        'estimated_duration' => 48,
        'is_active' => 1
    ],
        [
        'service_name' => 'Bedding & Linens',
        'service_type' => 'wash_fold',
        'description' => 'Specialized cleaning for bed sheets, comforters, pillows, and large linens.',
        'base_price' => 120.00,
        'estimated_duration' => 48,
        'is_active' => 1
    ],
    [
        'service_name' => 'Curtains & Drapes',
        'service_type' => 'dry_clean',
        'description' => 'Professional cleaning for curtains and drapes. Preserve fabric quality and color.',
        'base_price' => 200.00,
        'estimated_duration' => 72,
        'is_active' => 1
    ]
];

try {
    // Check if services already exist
    $stmt = $db->prepare("SELECT COUNT(*) FROM services");
    $stmt->execute();
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        echo "<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Services Already Exist</title>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gray-100'>
            <div class='container mx-auto px-4 py-16'>
                <div class='max-w-md mx-auto bg-white rounded-xl shadow-lg p-8 text-center'>
                    <div class='text-yellow-500 text-6xl mb-4'>⚠️</div>
                    <h1 class='text-2xl font-bold text-gray-800 mb-4'>Services Already Exist</h1>
                    <p class='text-gray-600 mb-6'>The database already contains {$count} service(s). Seeding skipped to prevent duplicates.</p>
                    <a href='pages/admin/services.php' class='bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold inline-block transition'>
                        View Services
                    </a>
                </div>
            </div>
        </body>
        </html>";
        exit;
    }

    // Prepare insert statement
    $stmt = $db->prepare("
        INSERT INTO services (service_name, service_type, description, base_price, estimated_duration, is_active) 
        VALUES (:service_name, :service_type, :description, :base_price, :estimated_duration, :is_active)
    ");

    $insertedCount = 0;

    // Insert each service
    foreach ($services as $service) {
        $stmt->execute([
            ':service_name' => $service['service_name'],
            ':service_type' => $service['service_type'],
            ':description' => $service['description'],
            ':base_price' => $service['base_price'],
            ':estimated_duration' => $service['estimated_duration'],
            ':is_active' => $service['is_active']
        ]);
        $insertedCount++;
    }

    // Success page
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Services Seeded Successfully</title>
        <script src='https://cdn.tailwindcss.com'></script>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
    </head>
    <body class='bg-gradient-to-br from-blue-100 to-purple-100 min-h-screen'>
        <div class='container mx-auto px-4 py-16'>
            <div class='max-w-4xl mx-auto bg-white rounded-2xl shadow-2xl p-8'>
                <div class='text-center mb-8'>
                    <div class='text-green-500 text-7xl mb-4'>
                        <i class='fas fa-check-circle'></i>
                    </div>
                    <h1 class='text-4xl font-bold text-gray-800 mb-4'>Services Seeded Successfully!</h1>
                    <p class='text-gray-600 text-lg'>Added {$insertedCount} sample services to the database</p>
                </div>

                <div class='grid grid-cols-1 md:grid-cols-2 gap-4 mb-8'>
                    " . implode('', array_map(function($service) {
                        $icons = [
                            'wash_fold' => 'fa-tshirt',
                            'dry_clean' => 'fa-wind',
                            'ironing' => 'fa-fire',
                            'express' => 'fa-bolt',
                            'pickup_delivery' => 'fa-truck'
                        ];
                        $colors = [
                            'wash_fold' => 'blue',
                            'dry_clean' => 'purple',
                            'ironing' => 'orange',
                            'express' => 'yellow',
                            'pickup_delivery' => 'green'
                        ];
                        $icon = $icons[$service['service_type']] ?? 'fa-tag';
                        $color = $colors[$service['service_type']] ?? 'gray';
                        
                        return "
                        <div class='bg-{$color}-50 border-2 border-{$color}-200 rounded-xl p-4'>
                            <div class='flex items-center gap-3 mb-2'>
                                <i class='fas {$icon} text-{$color}-600 text-xl'></i>
                                <h3 class='font-bold text-gray-800'>{$service['service_name']}</h3>
                            </div>
                            <p class='text-sm text-gray-600 mb-2'>{$service['description']}</p>
                            <div class='flex justify-between items-center'>
                                <span class='text-{$color}-700 font-bold'>₱" . number_format($service['base_price'], 2) . "</span>
                                <span class='text-gray-500 text-sm'>{$service['estimated_duration']}hrs</span>
                            </div>
                        </div>
                        ";
                    }, $services)) . "
                </div>

                <div class='flex gap-4 justify-center'>
                    <a href='pages/admin/services.php' class='bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white px-8 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transition transform hover:scale-105'>
                        <i class='fas fa-eye mr-2'></i>View Services
                    </a>
                    <a href='setup.php' class='bg-gray-200 hover:bg-gray-300 text-gray-800 px-8 py-3 rounded-lg font-semibold transition'>
                        <i class='fas fa-arrow-left mr-2'></i>Back to Setup
                    </a>
                </div>
            </div>
        </div>
    </body>
    </html>";

} catch (PDOException $e) {
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Seeding Error</title>
        <script src='https://cdn.tailwindcss.com'></script>
    </head>
    <body class='bg-gray-100'>
        <div class='container mx-auto px-4 py-16'>
            <div class='max-w-md mx-auto bg-white rounded-xl shadow-lg p-8 text-center'>
                <div class='text-red-500 text-6xl mb-4'>❌</div>
                <h1 class='text-2xl font-bold text-gray-800 mb-4'>Seeding Error</h1>
                <p class='text-gray-600 mb-4'>Error seeding services:</p>
                <p class='text-red-600 font-mono text-sm bg-red-50 p-4 rounded'>{$e->getMessage()}</p>
                <a href='setup.php' class='mt-6 inline-block bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold transition'>
                    Back to Setup
                </a>
            </div>
        </div>
    </body>
    </html>";
}
?>

