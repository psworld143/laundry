<?php
require_once 'config.php';

// Sample inventory items to seed
$inventoryItems = [
    [
        'item_name' => 'Tide Original Detergent',
        'item_type' => 'detergent',
        'brand' => 'Tide',
        'price' => 450.00,
        'quantity' => 25,
        'min_stock_level' => 10,
        'unit' => 'bottle'
    ],
    [
        'item_name' => 'Ariel Powder Detergent 3kg',
        'item_type' => 'detergent',
        'brand' => 'Ariel',
        'price' => 380.00,
        'quantity' => 30,
        'min_stock_level' => 15,
        'unit' => 'box'
    ],
    [
        'item_name' => 'Breeze Liquid Detergent',
        'item_type' => 'detergent',
        'brand' => 'Breeze',
        'price' => 350.00,
        'quantity' => 18,
        'min_stock_level' => 12,
        'unit' => 'bottle'
    ],
    [
        'item_name' => 'Downy Fabric Softener',
        'item_type' => 'fabric_softener',
        'brand' => 'Downy',
        'price' => 280.00,
        'quantity' => 22,
        'min_stock_level' => 10,
        'unit' => 'bottle'
    ],
    [
        'item_name' => 'Comfort Fabric Conditioner',
        'item_type' => 'fabric_softener',
        'brand' => 'Comfort',
        'price' => 250.00,
        'quantity' => 8,
        'min_stock_level' => 10,
        'unit' => 'bottle'
    ],
    [
        'item_name' => 'Clorox Bleach Regular',
        'item_type' => 'bleach',
        'brand' => 'Clorox',
        'price' => 180.00,
        'quantity' => 15,
        'min_stock_level' => 8,
        'unit' => 'bottle'
    ],
    [
        'item_name' => 'Zonrox Bleach',
        'item_type' => 'bleach',
        'brand' => 'Zonrox',
        'price' => 150.00,
        'quantity' => 3,
        'min_stock_level' => 8,
        'unit' => 'bottle'
    ],
    [
        'item_name' => 'Vanish Stain Remover',
        'item_type' => 'stain_remover',
        'brand' => 'Vanish',
        'price' => 320.00,
        'quantity' => 12,
        'min_stock_level' => 8,
        'unit' => 'bottle'
    ],
    [
        'item_name' => 'Oxiclean Stain Remover',
        'item_type' => 'stain_remover',
        'brand' => 'OxiClean',
        'price' => 380.00,
        'quantity' => 0,
        'min_stock_level' => 5,
        'unit' => 'bottle'
    ],
    [
        'item_name' => 'Laundry Mesh Bags',
        'item_type' => 'other',
        'brand' => 'Generic',
        'price' => 45.00,
        'quantity' => 50,
        'min_stock_level' => 20,
        'unit' => 'piece'
    ]
];

try {
    // Check if inventory already exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM inventory");
    $stmt->execute();
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        echo "<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Inventory Already Exists</title>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gray-100'>
            <div class='container mx-auto px-4 py-16'>
                <div class='max-w-md mx-auto bg-white rounded-xl shadow-lg p-8 text-center'>
                    <div class='text-yellow-500 text-6xl mb-4'>⚠️</div>
                    <h1 class='text-2xl font-bold text-gray-800 mb-4'>Inventory Already Exists</h1>
                    <p class='text-gray-600 mb-6'>The database already contains {$count} inventory item(s). Seeding skipped to prevent duplicates.</p>
                    <a href='pages/admin/inventory.php' class='bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold inline-block transition'>
                        View Inventory
                    </a>
                </div>
            </div>
        </body>
        </html>";
        exit;
    }

    // Prepare insert statement
    $stmt = $db->prepare("
        INSERT INTO inventory (item_name, item_type, brand, price, quantity, min_stock_level, unit) 
        VALUES (:item_name, :item_type, :brand, :price, :quantity, :min_stock_level, :unit)
    ");

    $insertedCount = 0;

    // Insert each item
    foreach ($inventoryItems as $item) {
        $stmt->execute([
            ':item_name' => $item['item_name'],
            ':item_type' => $item['item_type'],
            ':brand' => $item['brand'],
            ':price' => $item['price'],
            ':quantity' => $item['quantity'],
            ':min_stock_level' => $item['min_stock_level'],
            ':unit' => $item['unit']
        ]);
        $insertedCount++;
    }

    // Calculate statistics
    $lowStock = count(array_filter($inventoryItems, function($item) {
        return $item['quantity'] <= $item['min_stock_level'];
    }));
    
    $outOfStock = count(array_filter($inventoryItems, function($item) {
        return $item['quantity'] == 0;
    }));

    // Success page
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Inventory Seeded Successfully</title>
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
                    <h1 class='text-4xl font-bold text-gray-800 mb-4'>Inventory Seeded Successfully!</h1>
                    <p class='text-gray-600 text-lg'>Added {$insertedCount} sample inventory items to the database</p>
                </div>

                <div class='grid grid-cols-1 md:grid-cols-3 gap-6 mb-8'>
                    <div class='bg-blue-50 border-2 border-blue-200 rounded-xl p-6 text-center'>
                        <i class='fas fa-boxes text-4xl text-blue-600 mb-3'></i>
                        <h3 class='text-3xl font-bold text-blue-700 mb-1'>{$insertedCount}</h3>
                        <p class='text-sm text-blue-600 font-medium'>Total Items</p>
                    </div>
                    <div class='bg-yellow-50 border-2 border-yellow-200 rounded-xl p-6 text-center'>
                        <i class='fas fa-exclamation-triangle text-4xl text-yellow-600 mb-3'></i>
                        <h3 class='text-3xl font-bold text-yellow-700 mb-1'>{$lowStock}</h3>
                        <p class='text-sm text-yellow-600 font-medium'>Low Stock Items</p>
                    </div>
                    <div class='bg-red-50 border-2 border-red-200 rounded-xl p-6 text-center'>
                        <i class='fas fa-ban text-4xl text-red-600 mb-3'></i>
                        <h3 class='text-3xl font-bold text-red-700 mb-1'>{$outOfStock}</h3>
                        <p class='text-sm text-red-600 font-medium'>Out of Stock</p>
                    </div>
                </div>

                <div class='bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-6 mb-8'>
                    <h3 class='text-lg font-bold text-gray-800 mb-4'>
                        <i class='fas fa-list text-blue-500 mr-2'></i>Seeded Items Include:
                    </h3>
                    <div class='grid grid-cols-1 md:grid-cols-2 gap-3'>
                        " . implode('', array_map(function($item) {
                            $icons = [
                                'detergent' => ['emoji' => '🧴', 'color' => 'blue'],
                                'fabric_softener' => ['emoji' => '💧', 'color' => 'purple'],
                                'bleach' => ['emoji' => '⚗️', 'color' => 'orange'],
                                'stain_remover' => ['emoji' => '✨', 'color' => 'pink'],
                                'other' => ['emoji' => '📦', 'color' => 'gray']
                            ];
                            $itemIcon = $icons[$item['item_type']] ?? ['emoji' => '📦', 'color' => 'gray'];
                            
                            $stockStatus = $item['quantity'] == 0 ? 'Out of Stock' : 
                                         ($item['quantity'] <= $item['min_stock_level'] ? 'Low Stock' : 'In Stock');
                            $statusColor = $item['quantity'] == 0 ? 'red' : 
                                         ($item['quantity'] <= $item['min_stock_level'] ? 'yellow' : 'green');
                            
                            return "
                            <div class='bg-white border border-gray-200 rounded-lg p-3 flex items-center gap-3'>
                                <span class='text-2xl'>{$itemIcon['emoji']}</span>
                                <div class='flex-1'>
                                    <p class='font-semibold text-gray-800 text-sm'>{$item['item_name']}</p>
                                    <p class='text-xs text-gray-600'>{$item['quantity']} {$item['unit']} 
                                        <span class='px-2 py-0.5 rounded-full bg-{$statusColor}-100 text-{$statusColor}-700 ml-1'>{$stockStatus}</span>
                                    </p>
                                </div>
                            </div>
                            ";
                        }, $inventoryItems)) . "
                    </div>
                </div>

                <div class='flex gap-4 justify-center'>
                    <a href='pages/admin/inventory.php' class='bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white px-8 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transition transform hover:scale-105'>
                        <i class='fas fa-eye mr-2'></i>View Inventory
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
                <p class='text-gray-600 mb-4'>Error seeding inventory:</p>
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

