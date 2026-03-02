<?php
require_once 'config.php';

try {
    // Check if orders already exist
    $stmt = $db->prepare("SELECT COUNT(*) FROM transactions");
    $stmt->execute();
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        echo "<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Orders Already Exist</title>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gray-100'>
            <div class='container mx-auto px-4 py-16'>
                <div class='max-w-md mx-auto bg-white rounded-xl shadow-lg p-8 text-center'>
                    <div class='text-yellow-500 text-6xl mb-4'>⚠️</div>
                    <h1 class='text-2xl font-bold text-gray-800 mb-4'>Orders Already Exist</h1>
                    <p class='text-gray-600 mb-6'>The database already contains {$count} order(s). Seeding skipped to prevent duplicates.</p>
                    <a href='pages/admin/orders.php' class='bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold inline-block transition'>
                        View Orders
                    </a>
                </div>
            </div>
        </body>
        </html>";
        exit;
    }

    // Get customer and staff IDs
    $customers = $db->query("SELECT user_id, name FROM users WHERE position = 'user' LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    $staff = $db->query("SELECT staff_id FROM staff LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    $services = $db->query("SELECT service_id, service_name, base_price FROM services LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($customers)) {
        throw new Exception("No customers found. Please seed customers first.");
    }
    
    // Ensure payment method exists
    $paymentMethod = $db->query("SELECT method_id FROM payment_methods LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$paymentMethod) {
        // Create default payment method
        $db->exec("INSERT INTO payment_methods (method_name, is_online, processing_fee, is_active) VALUES ('Cash', 0, 0.00, 1)");
        $paymentMethodId = $db->lastInsertId();
    } else {
        $paymentMethodId = $paymentMethod['method_id'];
    }

    $statuses = ['pending', 'in_progress', 'washing', 'drying', 'ironing', 'ready', 'delivered'];
    $paymentStatuses = ['pending', 'unpaid', 'paid'];
    
    $insertedCount = 0;

    // Create 15 sample orders
    for ($i = 0; $i < 15; $i++) {
        $customer = $customers[array_rand($customers)];
        $staffMember = !empty($staff) ? $staff[array_rand($staff)] : null;
        $status = $statuses[array_rand($statuses)];
        $paymentStatus = $paymentStatuses[array_rand($paymentStatuses)];
        
        // Calculate prices
        $basketCount = rand(1, 5);
        $subtotal = $basketCount * 50; // Base price per basket
        $discount = $subtotal > 200 ? rand(10, 30) : 0;
        $total = $subtotal - $discount;
        
        // Insert order
        $stmt = $db->prepare("
            INSERT INTO transactions 
            (user_id, staff_id, basket_count, package, detergent_qty, softener_qty, 
             subtotal, discount_amount, total_price, payment_method_id, payment_status, 
             laundry_status, customer_number, account_name, remarks, estimated_completion, clothing_type, created_at) 
            VALUES 
            (:user_id, :staff_id, :basket_count, :package, :detergent_qty, :softener_qty, 
             :subtotal, :discount_amount, :total_price, :payment_method_id, :payment_status, 
             :laundry_status, :customer_number, :account_name, :remarks, :estimated_completion, :clothing_type, :created_at)
        ");
        
        $createdDate = date('Y-m-d H:i:s', strtotime("-" . rand(0, 30) . " days"));
        $estimatedDate = date('Y-m-d H:i:s', strtotime($createdDate . " +" . rand(24, 72) . " hours"));
        
        $stmt->execute([
            ':user_id' => $customer['user_id'],
            ':staff_id' => $staffMember ? $staffMember['staff_id'] : null,
            ':basket_count' => $basketCount,
            ':package' => 'none',
            ':detergent_qty' => rand(0, 2),
            ':softener_qty' => rand(0, 2),
            ':subtotal' => $subtotal,
            ':discount_amount' => $discount,
            ':total_price' => $total,
            ':payment_method_id' => $paymentMethodId,
            ':payment_status' => $paymentStatus,
            ':laundry_status' => $status,
            ':customer_number' => 'CUST-' . str_pad($customer['user_id'], 4, '0', STR_PAD_LEFT),
            ':account_name' => $customer['name'],
            ':remarks' => rand(0, 1) ? 'Please handle with care' : null,
            ':estimated_completion' => $estimatedDate,
            ':clothing_type' => rand(0, 1) ? 'regular' : 'delicate',
            ':created_at' => $createdDate
        ]);
        
        $orderId = $db->lastInsertId();
        
        // Add order items if services exist
        if (!empty($services)) {
            $numItems = rand(1, 3);
            $itemStmt = $db->prepare("
                INSERT INTO transaction_items 
                (payment_id, service_id, item_name, quantity, unit_price, total_price, special_instructions, status) 
                VALUES (:payment_id, :service_id, :item_name, :quantity, :unit_price, :total_price, :special_instructions, :status)
            ");
            
            for ($j = 0; $j < $numItems; $j++) {
                $service = $services[array_rand($services)];
                $quantity = rand(1, 5);
                $itemTotal = $service['base_price'] * $quantity;
                
                $itemStmt->execute([
                    ':payment_id' => $orderId,
                    ':service_id' => $service['service_id'],
                    ':item_name' => $service['service_name'],
                    ':quantity' => $quantity,
                    ':unit_price' => $service['base_price'],
                    ':total_price' => $itemTotal,
                    ':special_instructions' => rand(0, 1) ? 'Extra care needed' : null,
                    ':status' => 'pending'
                ]);
            }
        }
        
        $insertedCount++;
    }

    // Get statistics
    $pending = $db->query("SELECT COUNT(*) FROM transactions WHERE laundry_status = 'pending'")->fetchColumn();
    $inProgress = $db->query("SELECT COUNT(*) FROM transactions WHERE laundry_status IN ('in_progress', 'washing', 'drying', 'ironing')")->fetchColumn();
    $completed = $db->query("SELECT COUNT(*) FROM transactions WHERE laundry_status IN ('ready', 'delivered')")->fetchColumn();
    $totalRevenue = $db->query("SELECT SUM(total_price) FROM transactions WHERE payment_status = 'paid'")->fetchColumn() ?? 0;

    // Success page
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Orders Seeded Successfully</title>
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
                    <h1 class='text-4xl font-bold text-gray-800 mb-4'>Orders Seeded Successfully!</h1>
                    <p class='text-gray-600 text-lg'>Added {$insertedCount} sample orders to the database</p>
                </div>

                <div class='grid grid-cols-1 md:grid-cols-4 gap-6 mb-8'>
                    <div class='bg-blue-50 border-2 border-blue-200 rounded-xl p-6 text-center'>
                        <i class='fas fa-shopping-cart text-4xl text-blue-600 mb-3'></i>
                        <h3 class='text-3xl font-bold text-blue-700 mb-1'>{$insertedCount}</h3>
                        <p class='text-sm text-blue-600 font-medium'>Total Orders</p>
                    </div>
                    <div class='bg-yellow-50 border-2 border-yellow-200 rounded-xl p-6 text-center'>
                        <i class='fas fa-clock text-4xl text-yellow-600 mb-3'></i>
                        <h3 class='text-3xl font-bold text-yellow-700 mb-1'>{$pending}</h3>
                        <p class='text-sm text-yellow-600 font-medium'>Pending Orders</p>
                    </div>
                    <div class='bg-purple-50 border-2 border-purple-200 rounded-xl p-6 text-center'>
                        <i class='fas fa-spinner text-4xl text-purple-600 mb-3'></i>
                        <h3 class='text-3xl font-bold text-purple-700 mb-1'>{$inProgress}</h3>
                        <p class='text-sm text-purple-600 font-medium'>In Progress</p>
                    </div>
                    <div class='bg-green-50 border-2 border-green-200 rounded-xl p-6 text-center'>
                        <i class='fas fa-check-circle text-4xl text-green-600 mb-3'></i>
                        <h3 class='text-3xl font-bold text-green-700 mb-1'>{$completed}</h3>
                        <p class='text-sm text-green-600 font-medium'>Completed</p>
                    </div>
                </div>

                <div class='bg-gradient-to-r from-green-50 to-blue-50 rounded-xl p-6 mb-8 text-center'>
                    <p class='text-sm text-gray-600 mb-2'>Total Revenue (Paid Orders)</p>
                    <p class='text-4xl font-bold text-green-600'>₱" . number_format($totalRevenue, 2) . "</p>
                </div>

                <div class='bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-6 mb-8'>
                    <h3 class='text-lg font-bold text-gray-800 mb-4'>
                        <i class='fas fa-info-circle text-blue-500 mr-2'></i>Order Status Distribution:
                    </h3>
                    <div class='grid grid-cols-2 md:grid-cols-4 gap-3 text-sm'>
                        <div class='bg-white p-3 rounded-lg text-center'>
                            <p class='text-yellow-600 font-bold text-lg'>Pending</p>
                        </div>
                        <div class='bg-white p-3 rounded-lg text-center'>
                            <p class='text-blue-600 font-bold text-lg'>Processing</p>
                        </div>
                        <div class='bg-white p-3 rounded-lg text-center'>
                            <p class='text-purple-600 font-bold text-lg'>Washing/Drying</p>
                        </div>
                        <div class='bg-white p-3 rounded-lg text-center'>
                            <p class='text-green-600 font-bold text-lg'>Ready/Delivered</p>
                        </div>
                    </div>
                </div>

                <div class='flex gap-4 justify-center'>
                    <a href='pages/admin/orders.php' class='bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white px-8 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transition transform hover:scale-105'>
                        <i class='fas fa-eye mr-2'></i>View Orders
                    </a>
                    <a href='setup.php' class='bg-gray-200 hover:bg-gray-300 text-gray-800 px-8 py-3 rounded-lg font-semibold transition'>
                        <i class='fas fa-arrow-left mr-2'></i>Back to Setup
                    </a>
                </div>
            </div>
        </div>
    </body>
    </html>";

} catch (Exception $e) {
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
                <p class='text-gray-600 mb-4'>Error seeding orders:</p>
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

