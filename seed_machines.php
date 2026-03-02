<?php
require_once 'config.php';

// Sample machines to seed
$machines = [
    [
        'machine_name' => 'Washer-01',
        'machine_type' => 'washing_machine',
        'brand' => 'Samsung',
        'model' => 'WW10K6410QW',
        'capacity' => '10kg',
        'location' => 'Floor 1 - Left',
        'status' => 'available',
        'purchase_date' => '2024-01-15'
    ],
    [
        'machine_name' => 'Washer-02',
        'machine_type' => 'washing_machine',
        'brand' => 'LG',
        'model' => 'F4V5RGP2T',
        'capacity' => '12kg',
        'location' => 'Floor 1 - Center',
        'status' => 'available',
        'purchase_date' => '2024-01-20'
    ],
    [
        'machine_name' => 'Washer-03',
        'machine_type' => 'washing_machine',
        'brand' => 'Whirlpool',
        'model' => 'FSCR80410',
        'capacity' => '8kg',
        'location' => 'Floor 1 - Right',
        'status' => 'in_use',
        'purchase_date' => '2024-02-10'
    ],
    [
        'machine_name' => 'Dryer-01',
        'machine_type' => 'dryer',
        'brand' => 'LG',
        'model' => 'RC9055AP2F',
        'capacity' => '9kg',
        'location' => 'Floor 2 - Left',
        'status' => 'available',
        'purchase_date' => '2024-02-15'
    ],
    [
        'machine_name' => 'Dryer-02',
        'machine_type' => 'dryer',
        'brand' => 'Samsung',
        'model' => 'DV90N8289AW',
        'capacity' => '9kg',
        'location' => 'Floor 2 - Center',
        'status' => 'available',
        'purchase_date' => '2024-02-20'
    ],
    [
        'machine_name' => 'Dryer-03',
        'machine_type' => 'dryer',
        'brand' => 'Bosch',
        'model' => 'WTW85231GB',
        'capacity' => '8kg',
        'location' => 'Floor 2 - Right',
        'status' => 'maintenance',
        'purchase_date' => '2024-03-05'
    ],
    [
        'machine_name' => 'Iron-01',
        'machine_type' => 'iron',
        'brand' => 'Philips',
        'model' => 'GC4567/86',
        'capacity' => '2400W',
        'location' => 'Floor 3 - Station 1',
        'status' => 'available',
        'purchase_date' => '2024-03-10'
    ],
    [
        'machine_name' => 'Iron-02',
        'machine_type' => 'iron',
        'brand' => 'Tefal',
        'model' => 'FV9788',
        'capacity' => '3000W',
        'location' => 'Floor 3 - Station 2',
        'status' => 'available',
        'purchase_date' => '2024-03-15'
    ],
    [
        'machine_name' => 'Steamer-01',
        'machine_type' => 'steamer',
        'brand' => 'Rowenta',
        'model' => 'DR8120',
        'capacity' => '1600W',
        'location' => 'Floor 3 - Station 3',
        'status' => 'available',
        'purchase_date' => '2024-04-01'
    ],
    [
        'machine_name' => 'Steamer-02',
        'machine_type' => 'steamer',
        'brand' => 'Philips',
        'model' => 'GC558/38',
        'capacity' => '1800W',
        'location' => 'Floor 3 - Station 4',
        'status' => 'available',
        'purchase_date' => '2024-04-10'
    ]
];

try {
    // Check if machines already exist
    $stmt = $db->prepare("SELECT COUNT(*) FROM machines");
    $stmt->execute();
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        echo "<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Machines Already Exist</title>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gray-100'>
            <div class='container mx-auto px-4 py-16'>
                <div class='max-w-md mx-auto bg-white rounded-xl shadow-lg p-8 text-center'>
                    <div class='text-yellow-500 text-6xl mb-4'>⚠️</div>
                    <h1 class='text-2xl font-bold text-gray-800 mb-4'>Machines Already Exist</h1>
                    <p class='text-gray-600 mb-6'>The database already contains {$count} machine(s). Seeding skipped to prevent duplicates.</p>
                    <a href='pages/admin/machines.php' class='bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold inline-block transition'>
                        View Machines
                    </a>
                </div>
            </div>
        </body>
        </html>";
        exit;
    }

    // Prepare insert statement
    $stmt = $db->prepare("
        INSERT INTO machines (machine_name, machine_type, brand, model, capacity, location, status, purchase_date) 
        VALUES (:machine_name, :machine_type, :brand, :model, :capacity, :location, :status, :purchase_date)
    ");

    $insertedCount = 0;

    // Insert each machine
    foreach ($machines as $machine) {
        $stmt->execute([
            ':machine_name' => $machine['machine_name'],
            ':machine_type' => $machine['machine_type'],
            ':brand' => $machine['brand'],
            ':model' => $machine['model'],
            ':capacity' => $machine['capacity'],
            ':location' => $machine['location'],
            ':status' => $machine['status'],
            ':purchase_date' => $machine['purchase_date']
        ]);
        $insertedCount++;
    }

    // Success page
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Machines Seeded Successfully</title>
        <script src='https://cdn.tailwindcss.com'></script>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
    </head>
    <body class='bg-gradient-to-br from-blue-100 to-purple-100 min-h-screen'>
        <div class='container mx-auto px-4 py-16'>
            <div class='max-w-5xl mx-auto bg-white rounded-2xl shadow-2xl p-8'>
                <div class='text-center mb-8'>
                    <div class='text-green-500 text-7xl mb-4'>
                        <i class='fas fa-check-circle'></i>
                    </div>
                    <h1 class='text-4xl font-bold text-gray-800 mb-4'>Machines Seeded Successfully!</h1>
                    <p class='text-gray-600 text-lg'>Added {$insertedCount} sample machines to the database</p>
                </div>

                <div class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8'>
                    " . implode('', array_map(function($machine) {
                        $icons = [
                            'washing_machine' => ['icon' => 'fa-tshirt', 'emoji' => '🌊', 'color' => 'blue'],
                            'dryer' => ['icon' => 'fa-wind', 'emoji' => '🌡️', 'color' => 'orange'],
                            'iron' => ['icon' => 'fa-fire', 'emoji' => '🔥', 'color' => 'red'],
                            'steamer' => ['icon' => 'fa-smoke', 'emoji' => '💨', 'color' => 'purple']
                        ];
                        $machineIcon = $icons[$machine['machine_type']] ?? ['icon' => 'fa-cog', 'emoji' => '⚙️', 'color' => 'gray'];
                        
                        return "
                        <div class='bg-{$machineIcon['color']}-50 border-2 border-{$machineIcon['color']}-200 rounded-xl p-4'>
                            <div class='flex items-center gap-3 mb-3'>
                                <span class='text-3xl'>{$machineIcon['emoji']}</span>
                                <div>
                                    <h3 class='font-bold text-gray-800'>{$machine['machine_name']}</h3>
                                    <p class='text-xs text-gray-600'>{$machine['brand']} {$machine['model']}</p>
                                </div>
                            </div>
                            <div class='text-sm space-y-1'>
                                <p class='text-gray-600'><i class='fas fa-weight text-gray-400 mr-1'></i> {$machine['capacity']}</p>
                                <p class='text-gray-600'><i class='fas fa-map-marker-alt text-gray-400 mr-1'></i> {$machine['location']}</p>
                            </div>
                        </div>
                        ";
                    }, $machines)) . "
                </div>

                <div class='flex gap-4 justify-center'>
                    <a href='pages/admin/machines.php' class='bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white px-8 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transition transform hover:scale-105'>
                        <i class='fas fa-eye mr-2'></i>View Machines
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
                <p class='text-gray-600 mb-4'>Error seeding machines:</p>
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

