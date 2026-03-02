<?php
require_once '../../config.php';
if (!auth() || !in_array($_SESSION['position'], ['admin', 'cashier'])) redirect('login.php');

$pageTitle = 'Customer Orders';
ob_start();
?>

<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold text-gray-800">Customer Orders</h2>
        <a href="orders.php" class="text-blue-600 hover:text-blue-800">Back to Orders</a>
    </div>

    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 mb-6">
        <p class="text-sm text-yellow-900">Cashiers cannot create orders for themselves. This page lists customer orders only.</p>
    </div>

    <?php
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $params = [];
    $where = 'WHERE t.user_id <> ?';
    $params[] = $_SESSION['user_id'];
    if ($q !== '') {
        $where .= " AND (u.name LIKE ? OR u.phone_number LIKE ? OR t.payment_id LIKE ?)";
        $like = "%$q%";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $stmt = $db->prepare("
        SELECT t.*, u.name as customer_name, u.phone_number as customer_phone
        FROM transactions t
        LEFT JOIN users u ON t.user_id = u.user_id
        $where
        ORDER BY t.created_at DESC
        LIMIT 50
    ");
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <form method="get" class="mb-4">
        <div class="flex space-x-2 max-w-xl">
            <input name="q" value="<?php echo htmlspecialchars($q); ?>" type="text" placeholder="Search by name, phone or order #" class="flex-1 px-3 py-2 border border-gray-300 rounded-md">
            <button class="px-4 py-2 bg-blue-600 text-white rounded-md"><i class="fas fa-search mr-1"></i>Search</button>
        </div>
    </form>

    <?php if (empty($orders)): ?>
        <div class="text-center py-12">
            <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-600 mb-2">No customer orders found</h3>
            <p class="text-gray-500">Try a different search.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Order ID</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Customer</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Amount</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Payment</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Date</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-4 font-mono font-bold">#<?php echo str_pad($order['payment_id'], 6, '0', STR_PAD_LEFT); ?></td>
                        <td class="py-3 px-4">
                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($order['customer_phone']); ?></p>
                        </td>
                        <td class="py-3 px-4 font-bold text-green-600">₱<?php echo number_format($order['total_price'], 2); ?></td>
                        <td class="py-3 px-4 text-sm">
                            <?php $ps = $order['payment_status']; ?>
                            <span class="px-2 py-1 rounded-full text-xs font-bold <?php echo $ps==='paid'?'bg-green-100 text-green-800':($ps==='pending'?'bg-yellow-100 text-yellow-800':'bg-red-100 text-red-800'); ?>">
                                <?php echo ucfirst($ps); ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-600"><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <a href="../receipt-viewer.php?id=<?php echo $order['payment_id']; ?>" class="text-blue-600 hover:text-blue-800" title="View Receipt"><i class="fas fa-receipt"></i></a>
                                <button onclick="window.open('../receipt-viewer.php?id=<?php echo $order['payment_id']; ?>&print=1','_blank')" class="text-purple-600 hover:text-purple-800" title="Print"><i class="fas fa-print"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include '../../layout.php';
?>


