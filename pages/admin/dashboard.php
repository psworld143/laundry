<?php
require_once '../../config.php';
if (!auth() || $_SESSION['position'] !== 'admin') redirect('login.php');

// Get comprehensive stats
$stats = [
    'users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'customers' => $db->query("SELECT COUNT(*) FROM users WHERE position = 'user'")->fetchColumn(),
    'staff' => $db->query("SELECT COUNT(*) FROM staff WHERE is_active = 1")->fetchColumn(),
    'services' => $db->query("SELECT COUNT(*) FROM services WHERE is_active = 1")->fetchColumn(),
    'machines' => $db->query("SELECT COUNT(*) FROM machines")->fetchColumn(),
    'machines_available' => $db->query("SELECT COUNT(*) FROM machines WHERE status = 'available'")->fetchColumn(),
    'orders_today' => $db->query("SELECT COUNT(*) FROM transactions WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
    'revenue_today' => $db->query("SELECT COALESCE(SUM(total_price), 0) FROM transactions WHERE DATE(created_at) = CURDATE() AND payment_status = 'paid'")->fetchColumn(),
    'revenue_month' => $db->query("SELECT COALESCE(SUM(total_price), 0) FROM transactions WHERE MONTH(created_at) = MONTH(CURDATE()) AND payment_status = 'paid'")->fetchColumn(),
    'pending' => $db->query("SELECT COUNT(*) FROM transactions WHERE laundry_status IN ('pending', 'in_progress')")->fetchColumn(),
];

// Recent orders
$recentOrders = $db->query("
    SELECT t.*, u.name as customer_name 
    FROM transactions t 
    LEFT JOIN users u ON t.user_id = u.user_id 
    ORDER BY t.created_at DESC 
    LIMIT 5
")->fetchAll();

$pageTitle = 'Admin Dashboard';
ob_start();
?>

<!-- Welcome Banner -->
<div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold mb-2">Welcome back, <?= $_SESSION['name'] ?>! 👋</h1>
            <p class="text-blue-100">Here's what's happening with your laundry shop today</p>
        </div>
        <div class="hidden md:block">
            <i class="fas fa-chart-line text-6xl opacity-20"></i>
        </div>
    </div>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Users Card -->
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform duration-200">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-users text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">Total</span>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= number_format($stats['users']) ?></h3>
        <p class="text-blue-100 text-sm">Total Users</p>
        <div class="mt-4 flex items-center text-xs">
            <i class="fas fa-arrow-up mr-1"></i>
            <span><?= $stats['customers'] ?> customers</span>
        </div>
    </div>

    <!-- Revenue Today Card -->
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform duration-200">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-dollar-sign text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">Today</span>
        </div>
        <h3 class="text-4xl font-bold mb-1">₱<?= number_format($stats['revenue_today'], 0) ?></h3>
        <p class="text-green-100 text-sm">Revenue Today</p>
        <div class="mt-4 flex items-center text-xs">
            <i class="fas fa-calendar mr-1"></i>
            <span>₱<?= number_format($stats['revenue_month'], 0) ?> this month</span>
        </div>
    </div>

    <!-- Orders Today Card -->
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform duration-200">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-shopping-cart text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">Active</span>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= number_format($stats['orders_today']) ?></h3>
        <p class="text-purple-100 text-sm">Orders Today</p>
        <div class="mt-4 flex items-center text-xs">
            <i class="fas fa-clock mr-1"></i>
            <span><?= $stats['pending'] ?> pending</span>
        </div>
    </div>

    <!-- Machines Card -->
    <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform duration-200">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-cogs text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">Available</span>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= $stats['machines_available'] ?>/<?= $stats['machines'] ?></h3>
        <p class="text-orange-100 text-sm">Machines Ready</p>
        <div class="mt-4 flex items-center text-xs">
            <i class="fas fa-check-circle mr-1"></i>
            <span><?= round(($stats['machines_available'] / max($stats['machines'], 1)) * 100) ?>% operational</span>
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Quick Actions -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
            <i class="fas fa-bolt text-yellow-500 mr-3"></i>Quick Actions
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <a href="<?= BASE_URL ?>pages/admin/customers.php" 
               class="group bg-gradient-to-br from-blue-50 to-blue-100 hover:from-blue-500 hover:to-blue-600 rounded-xl p-6 transition-all duration-300 transform hover:scale-105">
                <div class="flex flex-col items-center text-center">
                    <div class="bg-blue-500 group-hover:bg-white p-4 rounded-full mb-3 transition-colors">
                        <i class="fas fa-users text-white group-hover:text-blue-500 text-2xl transition-colors"></i>
                    </div>
                    <span class="text-sm font-semibold text-blue-700 group-hover:text-white transition-colors">Customers</span>
                    <span class="text-xs text-blue-600 group-hover:text-blue-100 mt-1"><?= $stats['customers'] ?> total</span>
                </div>
            </a>

            <a href="<?= BASE_URL ?>pages/admin/staff.php" 
               class="group bg-gradient-to-br from-green-50 to-green-100 hover:from-green-500 hover:to-green-600 rounded-xl p-6 transition-all duration-300 transform hover:scale-105">
                <div class="flex flex-col items-center text-center">
                    <div class="bg-green-500 group-hover:bg-white p-4 rounded-full mb-3 transition-colors">
                        <i class="fas fa-user-tie text-white group-hover:text-green-500 text-2xl transition-colors"></i>
                    </div>
                    <span class="text-sm font-semibold text-green-700 group-hover:text-white transition-colors">Staff</span>
                    <span class="text-xs text-green-600 group-hover:text-green-100 mt-1"><?= $stats['staff'] ?> active</span>
                </div>
            </a>

            <a href="<?= BASE_URL ?>pages/admin/services.php" 
               class="group bg-gradient-to-br from-yellow-50 to-yellow-100 hover:from-yellow-500 hover:to-yellow-600 rounded-xl p-6 transition-all duration-300 transform hover:scale-105">
                <div class="flex flex-col items-center text-center">
                    <div class="bg-yellow-500 group-hover:bg-white p-4 rounded-full mb-3 transition-colors">
                        <i class="fas fa-concierge-bell text-white group-hover:text-yellow-500 text-2xl transition-colors"></i>
                    </div>
                    <span class="text-sm font-semibold text-yellow-700 group-hover:text-white transition-colors">Services</span>
                    <span class="text-xs text-yellow-600 group-hover:text-yellow-100 mt-1"><?= $stats['services'] ?> active</span>
                </div>
            </a>

            <a href="<?= BASE_URL ?>pages/admin/machines.php" 
               class="group bg-gradient-to-br from-red-50 to-red-100 hover:from-red-500 hover:to-red-600 rounded-xl p-6 transition-all duration-300 transform hover:scale-105">
                <div class="flex flex-col items-center text-center">
                    <div class="bg-red-500 group-hover:bg-white p-4 rounded-full mb-3 transition-colors">
                        <i class="fas fa-cogs text-white group-hover:text-red-500 text-2xl transition-colors"></i>
                    </div>
                    <span class="text-sm font-semibold text-red-700 group-hover:text-white transition-colors">Machines</span>
                    <span class="text-xs text-red-600 group-hover:text-red-100 mt-1"><?= $stats['machines'] ?> total</span>
                </div>
            </a>

            <a href="<?= BASE_URL ?>pages/admin/inventory.php" 
               class="group bg-gradient-to-br from-purple-50 to-purple-100 hover:from-purple-500 hover:to-purple-600 rounded-xl p-6 transition-all duration-300 transform hover:scale-105">
                <div class="flex flex-col items-center text-center">
                    <div class="bg-purple-500 group-hover:bg-white p-4 rounded-full mb-3 transition-colors">
                        <i class="fas fa-boxes text-white group-hover:text-purple-500 text-2xl transition-colors"></i>
                    </div>
                    <span class="text-sm font-semibold text-purple-700 group-hover:text-white transition-colors">Inventory</span>
                    <span class="text-xs text-purple-600 group-hover:text-purple-100 mt-1">Manage stock</span>
                </div>
            </a>

            <a href="<?= BASE_URL ?>pages/admin/fabrics.php" 
               class="group bg-gradient-to-br from-teal-50 to-teal-100 hover:from-teal-500 hover:to-teal-600 rounded-xl p-6 transition-all duration-300 transform hover:scale-105">
                <div class="flex flex-col items-center text-center">
                    <div class="bg-teal-500 group-hover:bg-white p-4 rounded-full mb-3 transition-colors">
                        <i class="fas fa-tint text-white group-hover:text-teal-500 text-2xl transition-colors"></i>
                    </div>
                    <span class="text-sm font-semibold text-teal-700 group-hover:text-white transition-colors">Fabcon Brands</span>
                    <span class="text-xs text-teal-600 group-hover:text-teal-100 mt-1">Manage brands</span>
                </div>
            </a>

            <a href="<?= BASE_URL ?>pages/admin/orders.php" 
               class="group bg-gradient-to-br from-indigo-50 to-indigo-100 hover:from-indigo-500 hover:to-indigo-600 rounded-xl p-6 transition-all duration-300 transform hover:scale-105">
                <div class="flex flex-col items-center text-center">
                    <div class="bg-indigo-500 group-hover:bg-white p-4 rounded-full mb-3 transition-colors">
                        <i class="fas fa-shopping-cart text-white group-hover:text-indigo-500 text-2xl transition-colors"></i>
                    </div>
                    <span class="text-sm font-semibold text-indigo-700 group-hover:text-white transition-colors">Orders</span>
                    <span class="text-xs text-indigo-600 group-hover:text-indigo-100 mt-1">View all</span>
                </div>
            </a>
        </div>
    </div>

    <!-- System Health -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
            <i class="fas fa-heartbeat text-red-500 mr-3"></i>System Health
        </h2>
        <div class="space-y-4">
            <!-- Active Staff -->
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="bg-green-100 p-2 rounded-lg mr-3">
                        <i class="fas fa-user-check text-green-600"></i>
                    </div>
                    <span class="text-sm text-gray-700">Active Staff</span>
                </div>
                <span class="font-bold text-gray-800"><?= $stats['staff'] ?></span>
            </div>

            <!-- Active Services -->
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="bg-blue-100 p-2 rounded-lg mr-3">
                        <i class="fas fa-concierge-bell text-blue-600"></i>
                    </div>
                    <span class="text-sm text-gray-700">Active Services</span>
                </div>
                <span class="font-bold text-gray-800"><?= $stats['services'] ?></span>
            </div>

            <!-- Available Machines -->
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="bg-orange-100 p-2 rounded-lg mr-3">
                        <i class="fas fa-cogs text-orange-600"></i>
                    </div>
                    <span class="text-sm text-gray-700">Available Machines</span>
                </div>
                <span class="font-bold text-gray-800"><?= $stats['machines_available'] ?>/<?= $stats['machines'] ?></span>
            </div>

            <!-- Pending Orders -->
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="bg-yellow-100 p-2 rounded-lg mr-3">
                        <i class="fas fa-clock text-yellow-600"></i>
                    </div>
                    <span class="text-sm text-gray-700">Pending Orders</span>
                </div>
                <span class="font-bold text-gray-800"><?= $stats['pending'] ?></span>
            </div>

            <!-- System Status -->
            <div class="mt-6 pt-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">System Status</span>
                    <span class="flex items-center text-green-600 font-semibold">
                        <i class="fas fa-circle text-xs mr-2 animate-pulse"></i>Online
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
        <i class="fas fa-history text-blue-500 mr-3"></i>Recent Orders
    </h2>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b-2 border-gray-200">
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Order ID</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Customer</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Status</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Payment</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Amount</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentOrders)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-12 text-gray-400">
                            <i class="fas fa-inbox text-4xl mb-3"></i>
                            <p>No orders yet</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentOrders as $order): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="py-4 px-4">
                            <span class="font-mono text-sm font-semibold text-gray-800">#<?= $order['payment_id'] ?></span>
                        </td>
                        <td class="py-4 px-4">
                            <div class="flex items-center">
                                <div class="bg-blue-500 w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold mr-3">
                                    <?= strtoupper(substr($order['customer_name'] ?? 'U', 0, 1)) ?>
                                </div>
                                <span class="text-sm text-gray-700"><?= $order['customer_name'] ?? 'Unknown' ?></span>
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <?php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'in_progress' => 'bg-blue-100 text-blue-800',
                                'washing' => 'bg-cyan-100 text-cyan-800',
                                'drying' => 'bg-orange-100 text-orange-800',
                                'ironing' => 'bg-red-100 text-red-800',
                                'ready' => 'bg-green-100 text-green-800',
                                'delivered' => 'bg-green-100 text-green-800',
                                'cancelled' => 'bg-red-100 text-red-800'
                            ];
                            $color = $statusColors[$order['laundry_status']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $color ?>">
                                <?= ucfirst(str_replace('_', ' ', $order['laundry_status'])) ?>
                            </span>
                        </td>
                        <td class="py-4 px-4">
                            <?php
                            $paymentColors = [
                                'paid' => 'bg-green-100 text-green-800',
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'unpaid' => 'bg-red-100 text-red-800',
                                'refunded' => 'bg-purple-100 text-purple-800'
                            ];
                            $pColor = $paymentColors[$order['payment_status']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $pColor ?>">
                                <?= ucfirst($order['payment_status']) ?>
                            </span>
                        </td>
                        <td class="py-4 px-4">
                            <span class="text-sm font-bold text-gray-800">₱<?= number_format($order['total_price'], 2) ?></span>
                        </td>
                        <td class="py-4 px-4 text-sm text-gray-500">
                            <?= date('M d, Y', strtotime($order['created_at'])) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../layout.php';
?>

