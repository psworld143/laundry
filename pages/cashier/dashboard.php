<?php
require_once '../../config.php';
if (!auth() || !in_array($_SESSION['position'], ['admin', 'cashier'])) redirect('login.php');

$pageTitle = 'Cashier Dashboard';
ob_start();

// Get cashier stats
$userId = $_SESSION['user_id'];
$today = date('Y-m-d');

// Today's transactions
$stmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE DATE(created_at) = ?");
$stmt->execute([$today]);
$todayOrders = $stmt->fetchColumn();

// Today's revenue
$stmt = $db->prepare("SELECT COALESCE(SUM(total_price), 0) FROM transactions WHERE DATE(created_at) = ? AND payment_status = 'paid'");
$stmt->execute([$today]);
$todayRevenue = $stmt->fetchColumn();

// Pending payments
$stmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE payment_status = 'pending'");
$stmt->execute();
$pendingPayments = $stmt->fetchColumn();

// Recent transactions
$stmt = $db->prepare("
    SELECT t.*, u.name as customer_name, u.phone_number as customer_phone
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.user_id
    ORDER BY t.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recentTransactions = $stmt->fetchAll();
?>

<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Cashier Dashboard</h2>
            <p class="text-gray-600">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
        </div>
        <div class="text-right">
            <p class="text-sm text-gray-500"><?php echo date('l, F j, Y'); ?></p>
            <p class="text-sm text-gray-500"><?php echo date('h:i A'); ?></p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Today's Orders -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <i class="fas fa-shopping-cart text-2xl"></i>
                </div>
                <div class="text-right">
                    <i class="fas fa-calendar-day text-2xl opacity-50"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold mb-1"><?php echo $todayOrders; ?></h3>
            <p class="text-blue-100 text-sm font-medium">Today's Orders</p>
        </div>

        <!-- Today's Revenue -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <i class="fas fa-dollar-sign text-2xl"></i>
                </div>
                <div class="text-right">
                    <i class="fas fa-chart-line text-2xl opacity-50"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold mb-1">₱<?php echo number_format($todayRevenue, 0); ?></h3>
            <p class="text-green-100 text-sm font-medium">Today's Revenue</p>
        </div>

        <!-- Pending Payments -->
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <i class="fas fa-clock text-2xl"></i>
                </div>
                <div class="text-right">
                    <i class="fas fa-exclamation-triangle text-2xl opacity-50"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold mb-1"><?php echo $pendingPayments; ?></h3>
            <p class="text-orange-100 text-sm font-medium">Pending Payments</p>
        </div>
    </div>

    <!-- Receipt Tools -->
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-receipt text-purple-500 mr-3"></i>Receipt Tools
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="<?= BASE_URL ?>pages/receipt-viewer.php"
               class="group bg-gradient-to-br from-purple-50 to-purple-100 hover:from-purple-500 hover:to-purple-600 rounded-xl p-6 transition-all duration-300 transform hover:scale-105 hover:shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-purple-500 group-hover:bg-white p-4 rounded-full transition-all duration-300">
                        <i class="fas fa-copy text-white group-hover:text-purple-500 text-2xl transition-colors"></i>
                    </div>
                    <span class="text-xs font-semibold text-purple-600 group-hover:text-white transition-colors">Receipt Viewer</span>
                </div>
                <h4 class="text-lg font-bold text-purple-700 group-hover:text-white transition-colors">Copy Receipt</h4>
                <p class="text-sm text-purple-600 group-hover:text-purple-100 transition-colors mt-2">
                    Reprint or share customer receipt copies instantly.
                </p>
            </a>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-semibold text-gray-800">Recent Transactions</h3>
            <a href="orders.php" class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                View All <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        <?php if (empty($recentTransactions)): ?>
            <div class="text-center py-12">
                <i class="fas fa-receipt text-4xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">No Transactions Yet</h3>
                <p class="text-gray-500 mb-6">Start processing orders and payments</p>
                <a href="new-order.php" class="inline-flex items-center bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold transition">
                    <i class="fas fa-plus mr-2"></i>Create New Order
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Order ID</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Customer</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Amount</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Date</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTransactions as $transaction): ?>
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-3 px-4">
                                <span class="font-mono text-sm font-bold text-gray-800">#<?php echo str_pad($transaction['payment_id'], 6, '0', STR_PAD_LEFT); ?></span>
                            </td>
                            <td class="py-3 px-4">
                                <div>
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($transaction['customer_name']); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($transaction['customer_phone']); ?></p>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-bold text-green-600">₱<?php echo number_format($transaction['total_price'], 2); ?></span>
                            </td>
                            <td class="py-3 px-4">
                                <?php
                                $statusColors = [
                                    'paid' => ['bg-green-100', 'text-green-800'],
                                    'pending' => ['bg-yellow-100', 'text-yellow-800'],
                                    'unpaid' => ['bg-red-100', 'text-red-800'],
                                    'refunded' => ['bg-gray-100', 'text-gray-800']
                                ];
                                [$bg, $text] = $statusColors[$transaction['payment_status']] ?? ['bg-gray-100', 'text-gray-800'];
                                ?>
                                <span class="px-2 py-1 rounded-full text-xs font-bold <?php echo $bg; ?> <?php echo $text; ?>">
                                    <?php echo ucfirst($transaction['payment_status']); ?>
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="text-sm text-gray-600">
                                    <?php echo date('M d, Y', strtotime($transaction['created_at'])); ?>
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex space-x-2">
                                    <button onclick="viewOrder(<?php echo $transaction['payment_id']; ?>)" 
                                            class="text-blue-500 hover:text-blue-700">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($transaction['payment_status'] !== 'paid'): ?>
                                    <button onclick="processPayment(<?php echo $transaction['payment_id']; ?>)" 
                                            class="text-green-500 hover:text-green-700">
                                        <i class="fas fa-credit-card"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button onclick="printReceipt(<?php echo $transaction['payment_id']; ?>)"
                                            class="text-purple-500 hover:text-purple-700" title="Print Receipt">
                                        <i class="fas fa-print"></i>
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
</div>

<!-- Order Details Modal -->
<div id="orderDetailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-8 max-w-4xl w-full max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Order Details</h3>
            <button onclick="closeOrderDetailsModal()" class="text-gray-400 hover:text-gray-600 transition">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>

        <div id="orderDetailsContent"></div>
    </div>
</div>

<script>
async function viewOrder(orderId) {
    try {
        // Show loading state
        document.getElementById('orderDetailsContent').innerHTML = `
            <div class="text-center py-12">
                <i class="fas fa-spinner fa-spin text-4xl text-blue-500 mb-4"></i>
                <p class="text-gray-500">Loading order details...</p>
            </div>
        `;
        
        // Show modal
        document.getElementById('orderDetailsModal').classList.remove('hidden');
        
        // Fetch order details from API
        const response = await fetch(`../../api/order_details.php?order_id=${orderId}&type=regular`);
        const result = await response.json();
        
        if (result.success) {
            displayOrderDetails(result.data);
        } else {
            document.getElementById('orderDetailsContent').innerHTML = `
                <div class="text-center py-12">
                    <i class="fas fa-exclamation-triangle text-4xl text-red-500 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Error Loading Order</h3>
                    <p class="text-gray-500">${result.message || 'Unable to load order details'}</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading order details:', error);
        document.getElementById('orderDetailsContent').innerHTML = `
            <div class="text-center py-12">
                <i class="fas fa-exclamation-triangle text-4xl text-red-500 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">Error Loading Order</h3>
                <p class="text-gray-500">Unable to load order details. Please try again.</p>
            </div>
        `;
    }
}

function displayOrderDetails(order) {
    const statusConfig = {
        pending: { color: 'yellow', icon: 'fa-clock' },
        in_progress: { color: 'blue', icon: 'fa-spinner' },
        washing: { color: 'cyan', icon: 'fa-water' },
        drying: { color: 'orange', icon: 'fa-wind' },
        ironing: { color: 'red', icon: 'fa-fire' },
        ready: { color: 'green', icon: 'fa-check-circle' },
        delivered: { color: 'green', icon: 'fa-box' },
        cancelled: { color: 'gray', icon: 'fa-times-circle' }
    };

    const paymentConfig = {
        pending: { color: 'yellow', label: 'Pending' },
        unpaid: { color: 'red', label: 'Unpaid' },
        paid: { color: 'green', label: 'Paid' },
        refunded: { color: 'purple', label: 'Refunded' }
    };

    const status = statusConfig[order.laundry_status] || statusConfig.pending;
    const payment = paymentConfig[order.payment_status] || paymentConfig.pending;
    
    const content = `
        <div class="space-y-6">
            <!-- Order Header -->
            <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-6 border-2 border-blue-200">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h4 class="text-2xl font-bold text-gray-800">Order #${order.order_id}</h4>
                        <p class="text-sm text-gray-600 mt-1">Created: ${new Date(order.created_at).toLocaleString()}</p>
                    </div>
                    <span class="px-4 py-2 rounded-full text-sm font-bold bg-${status.color}-100 text-${status.color}-800">
                        <i class="fas ${status.icon} mr-1"></i>${order.laundry_status.toUpperCase()}
                    </span>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Customer</p>
                        <p class="font-semibold text-gray-800">${order.customer_name || 'N/A'}</p>
                        <p class="text-sm text-gray-600">${order.customer_email || ''}</p>
                        <p class="text-sm text-gray-600">${order.customer_phone || ''}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Payment Status</p>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-${payment.color}-100 text-${payment.color}-800">
                            ${payment.label}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div>
                <h5 class="font-bold text-gray-800 mb-3">Order Items</h5>
                <div class="space-y-2">
                    ${order.items && order.items.length > 0 ? order.items.map(item => `
                        <div class="bg-gray-50 rounded-lg p-4 flex justify-between items-center">
                            <div>
                                <p class="font-semibold text-gray-800">${item.name}</p>
                                <p class="text-sm text-gray-600">Quantity: ${item.quantity} × ₱${parseFloat(item.unit_price).toFixed(2)}</p>
                            </div>
                            <p class="font-bold text-lg text-gray-800">₱${parseFloat(item.total_price).toFixed(2)}</p>
                        </div>
                    `).join('') : '<p class="text-gray-500 text-center py-4">No items</p>'}
                </div>
            </div>

            <!-- Order Details -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">Basket Count</p>
                    <p class="font-semibold text-gray-800">${order.basket_count || 'N/A'}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">Clothing Type</p>
                    <p class="font-semibold text-gray-800">${order.clothing_type || 'N/A'}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">Detergent Quantity</p>
                    <p class="font-semibold text-gray-800">${order.detergent_qty || 'N/A'}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">Softener Quantity</p>
                    <p class="font-semibold text-gray-800">${order.softener_qty || 'N/A'}</p>
                </div>
            </div>

            <!-- Pricing -->
            <div class="bg-gray-50 rounded-xl p-4">
                <div class="space-y-2">
                    <div class="border-t pt-2 flex justify-between text-lg font-bold">
                        <span>Total:</span>
                        <span class="text-blue-600">₱${parseFloat(order.total_price).toFixed(2)}</span>
                    </div>
                </div>
            </div>

            ${order.remarks ? `
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <p class="text-sm font-medium text-yellow-800">Remarks:</p>
                <p class="text-yellow-700">${order.remarks}</p>
            </div>
            ` : ''}
        </div>
    `;
    
    document.getElementById('orderDetailsContent').innerHTML = content;
}

function closeOrderDetailsModal() {
    document.getElementById('orderDetailsModal').classList.add('hidden');
}

function processPayment(orderId) {
    window.location.href = `payment-processor.php?order_id=${orderId}`;
}

function printReceipt(orderId) {
    window.open(`../receipt-viewer.php?id=${orderId}&print=1`, '_blank');
}

// Auto-refresh dashboard every 30 seconds
setInterval(function() {
    location.reload();
}, 30000);
</script>

<?php
$content = ob_get_clean();
include '../../layout.php';
?>

