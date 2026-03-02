<?php
require_once '../../config.php';
if (!auth() || !in_array($_SESSION['position'], ['admin', 'driver'])) redirect('login.php');

$pageTitle = 'Driver Dashboard';
ob_start();

// Get driver stats
$userId = $_SESSION['user_id'];
$today = date('Y-m-d');

// Initialize default values
$todayDeliveries = 0;
$todayPayments = 0;
$todayRevenue = 0;
$pendingDeliveries = 0;
$recentPayments = [];

try {
    // Check if tables exist before querying
    $tablesExist = true;
    
    // Check pickup_delivery table
    $stmt = $db->query("SHOW TABLES LIKE 'pickup_delivery'");
    if (!$stmt->fetch()) {
        $tablesExist = false;
    }
    
    // Check driver_payments table
    $stmt = $db->query("SHOW TABLES LIKE 'driver_payments'");
    if (!$stmt->fetch()) {
        $tablesExist = false;
    }
    
    if ($tablesExist) {
        // Today's deliveries
        $stmt = $db->prepare("SELECT COUNT(*) FROM pickup_delivery WHERE DATE(scheduled_date) = ? AND driver_id = ?");
        $stmt->execute([$today, $userId]);
        $todayDeliveries = $stmt->fetchColumn();

        // Today's payments processed
        $stmt = $db->prepare("SELECT COUNT(*) FROM driver_payments WHERE DATE(processed_at) = ? AND processed_by = ?");
        $stmt->execute([$today, $userId]);
        $todayPayments = $stmt->fetchColumn();

        // Today's revenue collected
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount_received), 0) FROM driver_payments WHERE DATE(processed_at) = ? AND processed_by = ?");
        $stmt->execute([$today, $userId]);
        $todayRevenue = $stmt->fetchColumn();

        // Pending deliveries
        $stmt = $db->prepare("SELECT COUNT(*) FROM pickup_delivery WHERE status = 'scheduled' AND driver_id = ?");
        $stmt->execute([$userId]);
        $pendingDeliveries = $stmt->fetchColumn();

        // Recent payments
        $stmt = $db->prepare("
            SELECT dp.*, t.payment_id as order_id, u.name as customer_name, u.phone_number as customer_phone, pm.method_name as payment_method_name
            FROM driver_payments dp
            LEFT JOIN transactions t ON dp.order_id = t.payment_id
            LEFT JOIN users u ON t.user_id = u.user_id
            LEFT JOIN payment_methods pm ON dp.payment_method_id = pm.method_id
            WHERE dp.processed_by = ?
            ORDER BY dp.processed_at DESC
            LIMIT 10
        ");
        $stmt->execute([$userId]);
        $recentPayments = $stmt->fetchAll();
    }
} catch (Exception $e) {
    error_log("Driver dashboard error: " . $e->getMessage());
    // Use default values if there's an error
}
?>

<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Driver Dashboard</h2>
            <p class="text-gray-600">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
        </div>
        <div class="text-right">
            <p class="text-sm text-gray-500"><?php echo date('l, F j, Y'); ?></p>
            <p class="text-sm text-gray-500"><?php echo date('h:i A'); ?></p>
        </div>
    </div>

    <?php if (!$tablesExist): ?>
    <!-- Missing Tables Warning -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-yellow-600 mr-3"></i>
            <div>
                <h3 class="text-yellow-800 font-semibold">Database Tables Missing</h3>
                <p class="text-yellow-700 text-sm mt-1">
                    The driver payment and delivery tables are not set up yet. 
                    <a href="../../fix_driver_tables.sql" class="underline font-medium">Run the SQL script</a> 
                    to create the required tables and enable full functionality.
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Today's Deliveries -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <i class="fas fa-truck text-2xl"></i>
                </div>
                <div class="text-right">
                    <i class="fas fa-calendar-day text-2xl opacity-50"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold mb-1"><?php echo $todayDeliveries; ?></h3>
            <p class="text-blue-100 text-sm font-medium">Today's Deliveries</p>
        </div>

        <!-- Today's Payments -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <i class="fas fa-credit-card text-2xl"></i>
                </div>
                <div class="text-right">
                    <i class="fas fa-chart-line text-2xl opacity-50"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold mb-1"><?php echo $todayPayments; ?></h3>
            <p class="text-green-100 text-sm font-medium">Payments Processed</p>
        </div>

        <!-- Today's Revenue -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <i class="fas fa-dollar-sign text-2xl"></i>
                </div>
                <div class="text-right">
                    <i class="fas fa-money-bill-wave text-2xl opacity-50"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold mb-1">₱<?php echo number_format($todayRevenue, 0); ?></h3>
            <p class="text-purple-100 text-sm font-medium">Revenue Collected</p>
        </div>

        <!-- Pending Deliveries -->
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <i class="fas fa-clock text-2xl"></i>
                </div>
                <div class="text-right">
                    <i class="fas fa-exclamation-triangle text-2xl opacity-50"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold mb-1"><?php echo $pendingDeliveries; ?></h3>
            <p class="text-orange-100 text-sm font-medium">Pending Deliveries</p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <a href="payment-scanner.php" class="bg-green-50 hover:bg-green-100 p-6 rounded-lg border border-green-200 transition-colors">
            <div class="flex items-center">
                <i class="fas fa-qrcode text-3xl text-green-600 mr-4"></i>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Payment Scanner</h3>
                    <p class="text-gray-600">Scan customer payments</p>
                </div>
            </div>
        </a>
        
        <a href="delivery-routes.php" class="bg-blue-50 hover:bg-blue-100 p-6 rounded-lg border border-blue-200 transition-colors">
            <div class="flex items-center">
                <i class="fas fa-route text-3xl text-blue-600 mr-4"></i>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Delivery Routes</h3>
                    <p class="text-gray-600">View delivery routes</p>
                </div>
            </div>
        </a>
        
        <a href="pickup-schedule.php" class="bg-purple-50 hover:bg-purple-100 p-6 rounded-lg border border-purple-200 transition-colors">
            <div class="flex items-center">
                <i class="fas fa-calendar-alt text-3xl text-purple-600 mr-4"></i>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Pickup Schedule</h3>
                    <p class="text-gray-600">Manage pickups</p>
                </div>
            </div>
        </a>
        
        <a href="receipt-viewer.php" class="bg-indigo-50 hover:bg-indigo-100 p-6 rounded-lg border border-indigo-200 transition-colors">
            <div class="flex items-center">
                <i class="fas fa-receipt text-3xl text-indigo-600 mr-4"></i>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Receipt Viewer</h3>
                    <p class="text-gray-600">View receipts</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Recent Payments -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-semibold text-gray-800">Recent Payments Processed</h3>
            <a href="payment-scanner.php" class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                Process More <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        <?php if (empty($recentPayments)): ?>
            <div class="text-center py-12">
                <i class="fas fa-credit-card text-4xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">No Payments Processed Yet</h3>
                <p class="text-gray-500 mb-6">Start processing customer payments</p>
                <a href="payment-scanner.php" class="inline-flex items-center bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg font-semibold transition">
                    <i class="fas fa-qrcode mr-2"></i>Start Payment Scanner
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recentPayments as $payment): ?>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-center">
                        <div>
                            <h4 class="font-semibold text-gray-800">Order #<?php echo str_pad($payment['order_id'], 6, '0', STR_PAD_LEFT); ?></h4>
                            <p class="text-sm text-gray-600">Customer: <?php echo htmlspecialchars($payment['customer_name']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo date('M d, Y h:i A', strtotime($payment['processed_at'])); ?></p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-green-600">₱<?php echo number_format($payment['amount_received'], 2); ?></p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($payment['payment_method_name']); ?></p>
                            <?php if ($payment['transaction_ref']): ?>
                            <p class="text-xs text-gray-400">Ref: <?php echo htmlspecialchars($payment['transaction_ref']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Driver Status -->
    <div class="mt-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xl font-semibold mb-2">Driver Status</h3>
                <p class="text-blue-100">You're currently active and ready for deliveries</p>
            </div>
            <div class="text-right">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <i class="fas fa-check-circle text-2xl"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-refresh dashboard every 30 seconds
setInterval(function() {
    location.reload();
}, 30000);

// Add some interactive effects
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects to stats cards
    document.querySelectorAll('.bg-gradient-to-br').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include '../../layout.php';
?>

