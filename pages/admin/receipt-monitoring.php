<?php
require_once '../../config.php';
if (!auth() || !in_array($_SESSION['position'], ['admin'])) redirect('login.php');

$pageTitle = 'Receipt Monitoring Dashboard';
ob_start();

// Get receipt statistics
$today = date('Y-m-d');
$thisWeek = date('Y-m-d', strtotime('-7 days'));
$thisMonth = date('Y-m-d', strtotime('-30 days'));

// Today's receipts
$stmt = $db->prepare("SELECT COUNT(*) FROM driver_receipts WHERE DATE(created_at) = ?");
$stmt->execute([$today]);
$todayReceipts = $stmt->fetchColumn();

// This week's receipts
$stmt = $db->prepare("SELECT COUNT(*) FROM driver_receipts WHERE created_at >= ?");
$stmt->execute([$thisWeek]);
$weekReceipts = $stmt->fetchColumn();

// This month's receipts
$stmt = $db->prepare("SELECT COUNT(*) FROM driver_receipts WHERE created_at >= ?");
$stmt->execute([$thisMonth]);
$monthReceipts = $stmt->fetchColumn();

// Pending receipts (not delivered)
$stmt = $db->prepare("SELECT COUNT(*) FROM driver_receipts WHERE status IN ('generated', 'printed')");
$stmt->execute();
$pendingReceipts = $stmt->fetchColumn();

// Driver performance
$stmt = $db->prepare("
    SELECT 
        u.name as driver_name,
        COUNT(dr.receipt_id) as total_receipts,
        COUNT(CASE WHEN dr.status = 'delivered' THEN 1 END) as delivered_receipts,
        COUNT(CASE WHEN DATE(dr.created_at) = ? THEN 1 END) as today_receipts
    FROM driver_receipts dr
    LEFT JOIN users u ON dr.generated_by = u.user_id
    WHERE dr.created_at >= ?
    GROUP BY dr.generated_by, u.name
    ORDER BY total_receipts DESC
");
$stmt->execute([$today, $thisWeek]);
$driverPerformance = $stmt->fetchAll();

// Recent receipts with details
$stmt = $db->prepare("
    SELECT 
        dr.*,
        t.payment_id as order_id,
        u.name as customer_name,
        u.phone_number as customer_phone,
        driver.name as driver_name,
        pm.method_name as payment_method_name,
        t.total_price,
        t.payment_status
    FROM driver_receipts dr
    LEFT JOIN transactions t ON dr.order_id = t.payment_id
    LEFT JOIN users u ON t.user_id = u.user_id
    LEFT JOIN users driver ON dr.generated_by = driver.user_id
    LEFT JOIN payment_methods pm ON t.payment_method_id = pm.method_id
    ORDER BY dr.created_at DESC
    LIMIT 20
");
$stmt->execute();
$recentReceipts = $stmt->fetchAll();

// Receipt status distribution
$stmt = $db->prepare("
    SELECT 
        status,
        COUNT(*) as count
    FROM driver_receipts 
    WHERE created_at >= ?
    GROUP BY status
");
$stmt->execute([$thisWeek]);
$statusDistribution = $stmt->fetchAll();
?>

<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Receipt Monitoring Dashboard</h2>
            <p class="text-gray-600">Monitor driver receipt generation and delivery</p>
        </div>
        <div class="text-right">
            <p class="text-sm text-gray-500"><?php echo date('l, F j, Y'); ?></p>
            <p class="text-sm text-gray-500"><?php echo date('h:i A'); ?></p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Today's Receipts -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <i class="fas fa-receipt text-2xl"></i>
                </div>
                <div class="text-right">
                    <i class="fas fa-calendar-day text-2xl opacity-50"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold mb-1"><?php echo $todayReceipts; ?></h3>
            <p class="text-blue-100 text-sm font-medium">Today's Receipts</p>
        </div>

        <!-- This Week -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <i class="fas fa-chart-line text-2xl"></i>
                </div>
                <div class="text-right">
                    <i class="fas fa-calendar-week text-2xl opacity-50"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold mb-1"><?php echo $weekReceipts; ?></h3>
            <p class="text-green-100 text-sm font-medium">This Week</p>
        </div>

        <!-- This Month -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <i class="fas fa-chart-bar text-2xl"></i>
                </div>
                <div class="text-right">
                    <i class="fas fa-calendar-alt text-2xl opacity-50"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold mb-1"><?php echo $monthReceipts; ?></h3>
            <p class="text-purple-100 text-sm font-medium">This Month</p>
        </div>

        <!-- Pending -->
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <i class="fas fa-clock text-2xl"></i>
                </div>
                <div class="text-right">
                    <i class="fas fa-exclamation-triangle text-2xl opacity-50"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold mb-1"><?php echo $pendingReceipts; ?></h3>
            <p class="text-orange-100 text-sm font-medium">Pending Delivery</p>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Driver Performance -->
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Driver Performance (This Week)</h3>
            <div class="space-y-4">
                <?php foreach ($driverPerformance as $driver): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                        <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($driver['driver_name']); ?></h4>
                        <p class="text-sm text-gray-600">Total: <?php echo $driver['total_receipts']; ?> | Today: <?php echo $driver['today_receipts']; ?></p>
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-bold text-green-600">
                            <?php echo $driver['total_receipts'] > 0 ? round(($driver['delivered_receipts'] / $driver['total_receipts']) * 100, 1) : 0; ?>%
                        </div>
                        <p class="text-xs text-gray-500">Delivery Rate</p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Status Distribution -->
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Receipt Status Distribution</h3>
            <div class="space-y-3">
                <?php 
                $statusColors = [
                    'generated' => ['bg-blue-500', 'text-blue-800'],
                    'printed' => ['bg-purple-500', 'text-purple-800'],
                    'delivered' => ['bg-green-500', 'text-green-800'],
                    'cancelled' => ['bg-red-500', 'text-red-800']
                ];
                foreach ($statusDistribution as $status): 
                    [$bg, $text] = $statusColors[$status['status']] ?? ['bg-gray-500', 'text-gray-800'];
                ?>
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-4 h-4 rounded-full <?php echo $bg; ?> mr-3"></div>
                        <span class="font-medium text-gray-700"><?php echo ucfirst($status['status']); ?></span>
                    </div>
                    <span class="font-bold text-gray-800"><?php echo $status['count']; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Recent Receipts -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-semibold text-gray-800">Recent Receipt Activity</h3>
            <div class="flex space-x-2">
                <button onclick="exportReceipts()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm">
                    <i class="fas fa-download mr-2"></i>Export
                </button>
                <button onclick="refreshData()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">
                    <i class="fas fa-sync-alt mr-2"></i>Refresh
                </button>
            </div>
        </div>

        <?php if (empty($recentReceipts)): ?>
            <div class="text-center py-12">
                <i class="fas fa-receipt text-4xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">No Receipt Activity</h3>
                <p class="text-gray-500">No receipts have been generated by drivers yet</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Receipt ID</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Order</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Customer</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Driver</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Amount</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Generated</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentReceipts as $receipt): ?>
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-3 px-4">
                                <span class="font-mono text-sm font-bold text-gray-800">#<?php echo str_pad($receipt['receipt_id'], 6, '0', STR_PAD_LEFT); ?></span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-mono text-sm font-bold text-blue-600">#<?php echo str_pad($receipt['order_id'], 6, '0', STR_PAD_LEFT); ?></span>
                            </td>
                            <td class="py-3 px-4">
                                <div>
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($receipt['customer_name']); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($receipt['customer_phone']); ?></p>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-medium text-gray-800"><?php echo htmlspecialchars($receipt['driver_name']); ?></span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-bold text-green-600">₱<?php echo number_format($receipt['total_price'], 2); ?></span>
                            </td>
                            <td class="py-3 px-4">
                                <?php
                                $statusColors = [
                                    'generated' => ['bg-blue-100', 'text-blue-800'],
                                    'printed' => ['bg-purple-100', 'text-purple-800'],
                                    'delivered' => ['bg-green-100', 'text-green-800'],
                                    'cancelled' => ['bg-red-100', 'text-red-800']
                                ];
                                [$bg, $text] = $statusColors[$receipt['status']] ?? ['bg-gray-100', 'text-gray-800'];
                                ?>
                                <span class="px-2 py-1 rounded-full text-xs font-bold <?php echo $bg; ?> <?php echo $text; ?>">
                                    <?php echo ucfirst($receipt['status']); ?>
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="text-sm text-gray-600">
                                    <?php echo date('M d, Y h:i A', strtotime($receipt['created_at'])); ?>
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex space-x-2">
                                    <button onclick="viewReceipt(<?php echo $receipt['receipt_id']; ?>)" 
                                            class="text-blue-500 hover:text-blue-700" title="View Receipt">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="viewOrder(<?php echo $receipt['order_id']; ?>)" 
                                            class="text-green-500 hover:text-green-700" title="View Order">
                                        <i class="fas fa-shopping-cart"></i>
                                    </button>
                                    <button onclick="contactDriver(<?php echo $receipt['generated_by']; ?>)" 
                                            class="text-purple-500 hover:text-purple-700" title="Contact Driver">
                                        <i class="fas fa-phone"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Alerts and Notifications -->
    <div class="mt-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xl font-semibold mb-2">Receipt Monitoring Active</h3>
                <p class="text-blue-100">Real-time tracking of driver receipt generation and delivery</p>
            </div>
            <div class="text-right">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <i class="fas fa-shield-alt text-2xl"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// View receipt
window.viewReceipt = function(receiptId) {
    window.open(`../receipt-viewer.php?id=${receiptId}&type=driver`, '_blank');
};

// View order
window.viewOrder = function(orderId) {
    window.open(`../admin/orders.php?id=${orderId}`, '_blank');
};

// Contact driver
window.contactDriver = function(driverId) {
    showNotification('Driver contact feature coming soon!', 'info');
};

// Export receipts
window.exportReceipts = function() {
    showNotification('Export feature coming soon!', 'info');
};

// Refresh data
window.refreshData = function() {
    location.reload();
};

// Notification function
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' :
        type === 'error' ? 'bg-red-500 text-white' :
        type === 'info' ? 'bg-blue-500 text-white' :
        'bg-gray-500 text-white'
    }`;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Auto-refresh every 60 seconds
setInterval(function() {
    location.reload();
}, 60000);
</script>

<?php
$content = ob_get_clean();
include '../../layout.php';
?>
