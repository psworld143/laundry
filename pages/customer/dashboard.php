<?php
require_once '../../config.php';
if (!auth()) redirect('login.php');

$userId = $_SESSION['user_id'];

// Get stats
$stmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
$stmt->execute([$userId]);
$total = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND laundry_status = 'completed'");
$stmt->execute([$userId]);
$completed = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND laundry_status IN ('pending', 'in_progress')");
$stmt->execute([$userId]);
$pending = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COALESCE(SUM(total_price), 0) FROM transactions WHERE user_id = ? AND payment_status = 'paid'");
$stmt->execute([$userId]);
$spent = $stmt->fetchColumn();

// Recent orders
$stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$userId]);
$recentOrders = $stmt->fetchAll();

$pageTitle = 'Customer Dashboard';
ob_start();
?>

<!-- Welcome Section -->
<div class="bg-gradient-to-r from-teal-500 to-cyan-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold mb-2">Hello, <?= $_SESSION['name'] ?>! 🌟</h1>
            <p class="text-teal-100">Track your laundry orders and manage your account</p>
        </div>
        <div class="hidden md:block">
            <i class="fas fa-tshirt text-6xl opacity-20"></i>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Orders -->
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-200 hover:shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-shopping-cart text-2xl"></i>
            </div>
            <div class="bg-white bg-opacity-30 w-12 h-12 rounded-full flex items-center justify-center">
                <i class="fas fa-arrow-up text-sm"></i>
            </div>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= $total ?></h3>
        <p class="text-blue-100 text-sm font-medium">Total Orders</p>
    </div>

    <!-- Completed Orders -->
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-200 hover:shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-check-circle text-2xl"></i>
            </div>
            <div class="bg-white bg-opacity-30 w-12 h-12 rounded-full flex items-center justify-center text-xs font-bold">
                <?= $total > 0 ? round(($completed / $total) * 100) : 0 ?>%
            </div>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= $completed ?></h3>
        <p class="text-green-100 text-sm font-medium">Completed</p>
    </div>

    <!-- In Progress -->
    <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-200 hover:shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-clock text-2xl"></i>
            </div>
            <?php if ($pending > 0): ?>
            <div class="bg-white bg-opacity-30 w-12 h-12 rounded-full flex items-center justify-center">
                <i class="fas fa-spinner fa-pulse text-sm"></i>
            </div>
            <?php endif; ?>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= $pending ?></h3>
        <p class="text-yellow-100 text-sm font-medium">In Progress</p>
    </div>

    <!-- Total Spent -->
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-200 hover:shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-dollar-sign text-2xl"></i>
            </div>
            <div class="bg-white bg-opacity-30 w-12 h-12 rounded-full flex items-center justify-center">
                <i class="fas fa-chart-line text-sm"></i>
            </div>
        </div>
        <h3 class="text-4xl font-bold mb-1">₱<?= number_format($spent, 0) ?></h3>
        <p class="text-purple-100 text-sm font-medium">Total Spent</p>
    </div>
</div>

<!-- Main Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Quick Actions -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
            <i class="fas fa-bolt text-yellow-500 mr-3"></i>Quick Actions
        </h2>
        <div class="grid grid-cols-2 gap-4">
            <a href="<?= BASE_URL ?>pages/customer/new-order.php" 
               class="group bg-gradient-to-br from-blue-50 to-blue-100 hover:from-blue-500 hover:to-blue-600 rounded-xl p-8 transition-all duration-300 transform hover:scale-105 hover:shadow-xl">
                <div class="flex flex-col items-center text-center">
                    <div class="bg-blue-500 group-hover:bg-white p-5 rounded-full mb-4 transition-all duration-300">
                        <i class="fas fa-plus text-white group-hover:text-blue-500 text-3xl transition-colors"></i>
                    </div>
                    <span class="text-base font-bold text-blue-700 group-hover:text-white transition-colors">New Service</span>
                    <span class="text-xs text-blue-600 group-hover:text-blue-100 mt-2">Create a new laundry service</span>
                </div>
            </a>

            <a href="<?= BASE_URL ?>pages/receipt-viewer.php" 
               class="group bg-gradient-to-br from-indigo-50 to-indigo-100 hover:from-indigo-500 hover:to-indigo-600 rounded-xl p-8 transition-all duration-300 transform hover:scale-105 hover:shadow-xl">
                <div class="flex flex-col items-center text-center">
                    <div class="bg-indigo-500 group-hover:bg-white p-5 rounded-full mb-4 transition-all duration-300">
                        <i class="fas fa-receipt text-white group-hover:text-indigo-500 text-3xl transition-colors"></i>
                    </div>
                    <span class="text-base font-bold text-indigo-700 group-hover:text-white transition-colors">Copy Receipt</span>
                    <span class="text-xs text-indigo-600 group-hover:text-indigo-100 mt-2">View or print receipt copies</span>
                </div>
            </a>


            <a href="<?= BASE_URL ?>pages/customer/my-orders.php" 
               class="group bg-gradient-to-br from-green-50 to-green-100 hover:from-green-500 hover:to-green-600 rounded-xl p-8 transition-all duration-300 transform hover:scale-105 hover:shadow-xl">
                <div class="flex flex-col items-center text-center">
                    <div class="bg-green-500 group-hover:bg-white p-5 rounded-full mb-4 transition-all duration-300">
                        <i class="fas fa-shopping-bag text-white group-hover:text-green-500 text-3xl transition-colors"></i>
                    </div>
                    <span class="text-base font-bold text-green-700 group-hover:text-white transition-colors">Custom Orders</span>
                    <span class="text-xs text-green-600 group-hover:text-green-100 mt-2">Your fabric-based orders</span>
                </div>
            </a>

            <a href="<?= BASE_URL ?>pages/customer/orders.php" 
               class="group bg-gradient-to-br from-orange-50 to-orange-100 hover:from-orange-500 hover:to-orange-600 rounded-xl p-8 transition-all duration-300 transform hover:scale-105 hover:shadow-xl">
                <div class="flex flex-col items-center text-center">
                    <div class="bg-orange-500 group-hover:bg-white p-5 rounded-full mb-4 transition-all duration-300">
                        <i class="fas fa-list text-white group-hover:text-orange-500 text-3xl transition-colors"></i>
                    </div>
                    <span class="text-base font-bold text-orange-700 group-hover:text-white transition-colors">All Orders</span>
                    <span class="text-xs text-orange-600 group-hover:text-orange-100 mt-2"><?= $total ?> total orders</span>
                </div>
            </a>
        </div>
    </div>

    <!-- Order Progress -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
            <i class="fas fa-tasks text-blue-500 mr-3"></i>Order Status
        </h2>
        <?php if ($pending > 0): ?>
        <div class="mb-6">
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl p-4 text-white">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium">Active Orders</span>
                    <span class="bg-white bg-opacity-30 px-3 py-1 rounded-full text-xs font-bold"><?= $pending ?></span>
                </div>
                <p class="text-xs text-blue-100">Your laundry is being processed</p>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="space-y-4">
            <div class="flex items-center">
                <div class="bg-green-100 p-2 rounded-lg mr-3">
                    <i class="fas fa-check-double text-green-600"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-gray-800">Completed</p>
                    <p class="text-xs text-gray-500"><?= $completed ?> orders</p>
                </div>
                <span class="text-lg font-bold text-gray-800"><?= $total > 0 ? round(($completed / $total) * 100) : 0 ?>%</span>
            </div>

            <div class="flex items-center">
                <div class="bg-yellow-100 p-2 rounded-lg mr-3">
                    <i class="fas fa-spinner fa-pulse text-yellow-600"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-gray-800">In Progress</p>
                    <p class="text-xs text-gray-500"><?= $pending ?> orders</p>
                </div>
                <span class="text-lg font-bold text-gray-800"><?= $total > 0 ? round(($pending / $total) * 100) : 0 ?>%</span>
            </div>

            <div class="mt-6 pt-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Account Status</span>
                    <span class="flex items-center text-green-600 font-semibold text-sm">
                        <i class="fas fa-check-circle mr-2"></i>Active
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-gray-800 flex items-center">
            <i class="fas fa-history text-blue-500 mr-3"></i>Recent Orders
        </h2>
        <a href="<?= BASE_URL ?>pages/customer/orders.php" class="text-blue-500 hover:text-blue-700 text-sm font-medium">
            View All <i class="fas fa-arrow-right ml-1"></i>
        </a>
    </div>

    <?php if (empty($recentOrders)): ?>
        <div class="text-center py-16">
            <div class="bg-gray-100 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-shopping-cart text-4xl text-gray-400"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">No Orders Yet</h3>
            <p class="text-gray-500 mb-6">Start your first laundry order today!</p>
            <a href="<?= BASE_URL ?>pages/customer/new-order.php" 
               class="inline-flex items-center bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold transition">
                <i class="fas fa-plus mr-2"></i>Create New Service
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($recentOrders as $order): ?>
            <div class="border-2 border-gray-200 rounded-xl p-5 hover:border-blue-500 hover:shadow-lg transition-all duration-200">
                <div class="flex items-center justify-between mb-3">
                    <span class="font-mono text-sm font-bold text-gray-800">#<?= $order['payment_id'] ?></span>
                    <?php
                    $statusColors = [
                        'pending' => ['bg-yellow-100', 'text-yellow-800'],
                        'in_progress' => ['bg-blue-100', 'text-blue-800'],
                        'completed' => ['bg-green-100', 'text-green-800'],
                        'cancelled' => ['bg-red-100', 'text-red-800']
                    ];
                    [$bg, $text] = $statusColors[$order['laundry_status']] ?? ['bg-gray-100', 'text-gray-800'];
                    ?>
                    <span class="px-3 py-1 rounded-full text-xs font-bold <?= $bg ?> <?= $text ?>">
                        <?= ucfirst($order['laundry_status']) ?>
                    </span>
                </div>

                <div class="mb-3">
                    <p class="text-2xl font-bold text-gray-800">₱<?= number_format($order['total_price'], 2) ?></p>
                </div>

                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">
                        <i class="far fa-calendar mr-1"></i><?= date('M d, Y', strtotime($order['created_at'])) ?>
                    </span>
                    <?php
                    $paymentColors = [
                        'paid' => 'text-green-600',
                        'pending' => 'text-yellow-600',
                        'failed' => 'text-red-600'
                    ];
                    $pColor = $paymentColors[$order['payment_status']] ?? 'text-gray-600';
                    ?>
                    <span class="<?= $pColor ?> font-semibold">
                        <i class="fas fa-circle text-xs mr-1"></i><?= ucfirst($order['payment_status']) ?>
                    </span>
                </div>

                <div class="mt-4 flex justify-end">
                    <a href="<?= BASE_URL ?>pages/receipt-viewer.php?id=<?= $order['payment_id'] ?>"
                       class="inline-flex items-center text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                        <i class="fas fa-copy mr-2"></i>Copy Receipt
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include '../../layout.php';
?>