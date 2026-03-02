<?php
require_once '../../config.php';
if (!auth()) redirect('login.php');

$userId = $_SESSION['user_id'];

// Get regular orders from transactions table
$stmt = $db->prepare("
    SELECT t.*, 
           GROUP_CONCAT(ti.item_name SEPARATOR ', ') as items,
           GROUP_CONCAT(ti.quantity SEPARATOR ', ') as quantities
    FROM transactions t
    LEFT JOIN transaction_items ti ON t.payment_id = ti.payment_id
    WHERE t.user_id = ?
    GROUP BY t.payment_id
    ORDER BY t.created_at DESC
");
$stmt->execute([$userId]);
$regularOrders = $stmt->fetchAll();

// Get custom orders
$stmt = $db->prepare("
    SELECT co.*, cf.fabric_name, cf.fabric_type, cf.color, cf.condition_status
    FROM custom_orders co
    LEFT JOIN customer_inventory_fabric cf ON co.fabric_id = cf.fabric_id
    WHERE co.user_id = ?
    ORDER BY co.created_at DESC
");
$stmt->execute([$userId]);
$customOrders = $stmt->fetchAll();

$pageTitle = 'My Orders';
ob_start();
?>

<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">My Orders</h2>
        <div class="flex space-x-3">
            <a href="new-order.php" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <i class="fas fa-plus mr-2"></i>New Order
            </a>
            <a href="my-orders.php" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                <i class="fas fa-cut mr-2"></i>Custom Orders
            </a>
        </div>
    </div>

    <!-- Regular Orders Section -->
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
            <i class="fas fa-shopping-cart mr-2 text-blue-500"></i>Regular Orders
        </h3>
        
        <?php if (empty($regularOrders)): ?>
            <div class="text-center py-8 bg-gray-50 rounded-lg">
                <i class="fas fa-shopping-cart text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500 mb-4">No regular orders yet</p>
                <a href="new-order.php" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i>Create First Order
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($regularOrders as $order): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-lg transition-all duration-300">
                    <!-- Header with Order Info and Status -->
                    <div class="flex justify-between items-start mb-6">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h4 class="text-xl font-bold text-gray-800">Order #<?= $order['payment_id'] ?></h4>
                                <span class="text-sm text-gray-500 font-medium"><?= ucfirst(str_replace('_', ' ', $order['service_type'] ?? 'Wash & Fold')) ?></span>
                            </div>
                            <p class="text-sm text-gray-500 mb-1">
                                <i class="fas fa-calendar-alt mr-2 text-gray-400"></i>
                                Created: <?= date('M d, Y g:i A', strtotime($order['created_at'])) ?>
                            </p>
                            <?php if ($order['estimated_completion']): ?>
                                <p class="text-sm text-gray-500">
                                    <i class="fas fa-clock mr-2 text-gray-400"></i>
                                    Est. Completion: <?= date('M d, Y g:i A', strtotime($order['estimated_completion'])) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-blue-600 mb-2">₱<?= number_format($order['total_price'], 2) ?></p>
                            <div class="flex flex-col gap-1">
                                <?php
                                $statusColors = [
                                    'pending' => ['bg-yellow-100', 'text-yellow-800'],
                                    'in_progress' => ['bg-blue-100', 'text-blue-800'],
                                    'washing' => ['bg-blue-100', 'text-blue-800'],
                                    'drying' => ['bg-blue-100', 'text-blue-800'],
                                    'ironing' => ['bg-blue-100', 'text-blue-800'],
                                    'ready' => ['bg-green-100', 'text-green-800'],
                                    'delivered' => ['bg-green-100', 'text-green-800'],
                                    'cancelled' => ['bg-red-100', 'text-red-800']
                                ];
                                [$bg, $text] = $statusColors[$order['laundry_status']] ?? ['bg-gray-100', 'text-gray-800'];
                                ?>
                                <span class="px-3 py-1 text-xs font-medium rounded-full <?= $bg ?> <?= $text ?>">
                                    <?= ucfirst(str_replace('_', ' ', $order['laundry_status'])) ?>
                                </span>
                                <?php
                                $paymentColors = [
                                    'pending' => ['bg-yellow-100', 'text-yellow-800'],
                                    'paid' => ['bg-green-100', 'text-green-800'],
                                    'unpaid' => ['bg-red-100', 'text-red-800'],
                                    'refunded' => ['bg-purple-100', 'text-purple-800']
                                ];
                                [$pBg, $pText] = $paymentColors[$order['payment_status']] ?? ['bg-gray-100', 'text-gray-800'];
                                ?>
                                <span class="px-3 py-1 text-xs font-medium rounded-full <?= $pBg ?> <?= $pText ?>">
                                    <?= ucfirst($order['payment_status']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Details Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
                        <div>
                            <p class="text-sm text-gray-500 font-medium mb-1">Basket Count</p>
                            <p class="text-lg font-semibold text-gray-800"><?= $order['basket_count'] ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium mb-1">Detergent Quantity</p>
                            <p class="text-lg font-semibold text-gray-800"><?= $order['detergent_qty'] ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium mb-1">Softener Quantity</p>
                            <p class="text-lg font-semibold text-gray-800"><?= $order['softener_qty'] ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium mb-1">Clothing Type</p>
                            <p class="text-lg font-semibold text-gray-800 capitalize"><?= $order['clothing_type'] ?></p>
                        </div>
                    </div>
                    
                    <!-- Special Instructions -->
                    <?php if ($order['remarks']): ?>
                        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                            <p class="text-sm text-gray-500 font-medium mb-2">Special Instructions</p>
                            <p class="text-sm text-gray-800"><?= htmlspecialchars($order['remarks']) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Footer with Actions -->
                    <div class="flex justify-between items-center pt-4 border-t border-gray-100">
                        <div class="flex space-x-2">
                            <?php if ($order['package'] !== 'none'): ?>
                                <span class="px-3 py-1 bg-purple-100 text-purple-800 text-xs font-medium rounded-full">
                                    <?= ucfirst($order['package']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <button onclick="viewOrderDetails(<?= $order['payment_id'] ?>)" 
                                class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors duration-200">
                            <i class="fas fa-eye mr-2"></i>View Details
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Custom Orders Section -->
    <div>
        <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
            <i class="fas fa-cut mr-2 text-green-500"></i>Custom Orders
        </h3>
        
        <?php if (empty($customOrders)): ?>
            <div class="text-center py-8 bg-gray-50 rounded-lg">
                <i class="fas fa-cut text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500 mb-4">No custom orders yet</p>
                <a href="new-order.php" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                    <i class="fas fa-plus mr-2"></i>Create New Service
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($customOrders as $order): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-lg transition-all duration-300">
                    <!-- Header with Order Info and Status -->
                    <div class="flex justify-between items-start mb-6">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h4 class="text-xl font-bold text-gray-800">Custom Order #<?= $order['order_id'] ?></h4>
                                <span class="text-sm text-gray-500 font-medium"><?= ucfirst(str_replace('_', ' ', $order['service_type'])) ?></span>
                            </div>
                            <p class="text-sm text-gray-600 mb-1"><?= $order['fabric_name'] ?> (<?= $order['fabric_type'] ?>)</p>
                            <p class="text-sm text-gray-500 mb-1">
                                <i class="fas fa-calendar-alt mr-2 text-gray-400"></i>
                                Created: <?= date('M d, Y g:i A', strtotime($order['created_at'])) ?>
                            </p>
                            <?php if ($order['estimated_completion']): ?>
                                <p class="text-sm text-gray-500">
                                    <i class="fas fa-clock mr-2 text-gray-400"></i>
                                    Est. Completion: <?= date('M d, Y g:i A', strtotime($order['estimated_completion'])) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-green-600 mb-2">₱<?= number_format($order['subtotal'], 2) ?></p>
                            <div class="flex flex-col gap-1">
                                <?php
                                [$bg, $text] = $statusColors[$order['laundry_status']] ?? ['bg-gray-100', 'text-gray-800'];
                                ?>
                                <span class="px-3 py-1 text-xs font-medium rounded-full <?= $bg ?> <?= $text ?>">
                                    <?= ucfirst(str_replace('_', ' ', $order['laundry_status'])) ?>
                                </span>
                                <?php
                                [$pBg, $pText] = $paymentColors[$order['payment_status']] ?? ['bg-gray-100', 'text-gray-800'];
                                ?>
                                <span class="px-3 py-1 text-xs font-medium rounded-full <?= $pBg ?> <?= $pText ?>">
                                    <?= ucfirst($order['payment_status']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Details Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
                        <div>
                            <p class="text-sm text-gray-500 font-medium mb-1">Service Type</p>
                            <p class="text-lg font-semibold text-gray-800 capitalize"><?= str_replace('_', ' ', $order['service_type']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium mb-1">Soap Type</p>
                            <p class="text-lg font-semibold text-gray-800 capitalize"><?= $order['soap_type'] ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium mb-1">Fabric Color</p>
                            <p class="text-lg font-semibold text-gray-800"><?= $order['color'] ?: 'N/A' ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium mb-1">Condition</p>
                            <p class="text-lg font-semibold text-gray-800 capitalize"><?= $order['condition_status'] ?></p>
                        </div>
                    </div>
                    
                    <!-- Special Instructions -->
                    <?php if ($order['special_instructions']): ?>
                        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                            <p class="text-sm text-gray-500 font-medium mb-2">Special Instructions</p>
                            <p class="text-sm text-gray-800"><?= htmlspecialchars($order['special_instructions']) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Footer with Actions -->
                    <div class="flex justify-between items-center pt-4 border-t border-gray-100">
                        <div class="flex space-x-2">
                            <?php if ($order['ironing']): ?>
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-full">Ironing</span>
                            <?php endif; ?>
                            <?php if ($order['express']): ?>
                                <span class="px-3 py-1 bg-purple-100 text-purple-800 text-xs font-medium rounded-full">Express</span>
                            <?php endif; ?>
                        </div>
                        <button onclick="viewCustomOrderDetails(<?= $order['order_id'] ?>)" 
                                class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors duration-200">
                            <i class="fas fa-eye mr-2"></i>View Details
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Order Details Modal -->
<div id="orderModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 px-6 py-4 rounded-t-2xl">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold text-white flex items-center">
                        <i class="fas fa-receipt mr-3"></i>Order Details
                    </h3>
                    <button type="button" onclick="closeOrderModal()" class="text-white hover:text-gray-200 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Content -->
            <div class="p-6 max-h-[calc(90vh-80px)] overflow-y-auto">
                <div id="orderDetails">
                    <!-- Order details will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentOrderId = null;
let currentOrderType = null;

async function viewOrderDetails(orderId) {
    currentOrderId = orderId;
    currentOrderType = 'regular';
    
    try {
        const response = await fetch(`../../api/order_details.php?order_id=${orderId}&type=regular`);
        const data = await response.json();
        
        if (data.success) {
            displayOrderDetails(data.data);
            document.getElementById('orderModal').classList.remove('hidden');
        } else {
            alert('Error loading order details: ' + data.message);
        }
    } catch (error) {
        console.error('Error fetching order details:', error);
        alert('Error loading order details');
    }
}

async function viewCustomOrderDetails(orderId) {
    currentOrderId = orderId;
    currentOrderType = 'custom';
    
    try {
        const response = await fetch(`../../api/order_details.php?order_id=${orderId}&type=custom`);
        const data = await response.json();
        
        if (data.success) {
            displayOrderDetails(data.data);
            document.getElementById('orderModal').classList.remove('hidden');
        } else {
            alert('Error loading order details: ' + data.message);
        }
    } catch (error) {
        console.error('Error fetching order details:', error);
        alert('Error loading order details');
    }
}

function displayOrderDetails(order) {
    const container = document.getElementById('orderDetails');
    
    if (order.order_type === 'custom') {
        container.innerHTML = `
            <div class="space-y-6">
                <!-- Order Header -->
                <div class="bg-gradient-to-r from-green-50 to-blue-50 p-6 rounded-xl">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="text-2xl font-bold text-gray-800 mb-2">Custom Order #${order.order_id}</h4>
                            <p class="text-lg text-gray-600">${order.fabric_name} (${order.fabric_type})</p>
                            <p class="text-sm text-gray-500 mt-1">
                                <i class="fas fa-calendar-alt mr-2"></i>
                                Created: ${new Date(order.created_at).toLocaleString()}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-3xl font-bold text-green-600">₱${parseFloat(order.subtotal).toFixed(2)}</p>
                            <div class="flex flex-col gap-2 mt-2">
                                <span class="px-3 py-1 text-sm font-medium rounded-full ${
                                    order.laundry_status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                    order.laundry_status === 'in_progress' ? 'bg-blue-100 text-blue-800' :
                                    order.laundry_status === 'ready' ? 'bg-green-100 text-green-800' :
                                    order.laundry_status === 'delivered' ? 'bg-green-100 text-green-800' :
                                    order.laundry_status === 'cancelled' ? 'bg-red-100 text-red-800' :
                                    'bg-gray-100 text-gray-800'
                                }">${order.laundry_status.replace('_', ' ')}</span>
                                <span class="px-3 py-1 text-sm font-medium rounded-full ${
                                    order.payment_status === 'paid' ? 'bg-green-100 text-green-800' : 
                                    order.payment_status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                    'bg-red-100 text-red-800'
                                }">${order.payment_status}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Service Details -->
                <div class="bg-white border border-gray-200 rounded-xl p-6">
                    <h5 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-cogs mr-2 text-blue-500"></i>Service Details
                    </h5>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-gray-500 font-medium mb-1">Service Type</p>
                            <p class="text-lg font-semibold text-gray-800 capitalize">${order.service_type.replace('_', ' ')}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium mb-1">Soap Type</p>
                            <p class="text-lg font-semibold text-gray-800 capitalize">${order.soap_type}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium mb-1">Fabric Color</p>
                            <p class="text-lg font-semibold text-gray-800">${order.color || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium mb-1">Condition</p>
                            <p class="text-lg font-semibold text-gray-800 capitalize">${order.condition_status}</p>
                        </div>
                    </div>
                    
                    ${order.description ? `
                        <div class="mt-4">
                            <p class="text-sm text-gray-500 font-medium mb-1">Description</p>
                            <p class="text-sm text-gray-800">${order.description}</p>
                        </div>
                    ` : ''}
                    
                    ${order.special_instructions ? `
                        <div class="mt-4 p-4 bg-yellow-50 rounded-lg">
                            <p class="text-sm text-gray-500 font-medium mb-2">Special Instructions</p>
                            <p class="text-sm text-gray-800">${order.special_instructions}</p>
                        </div>
                    ` : ''}
                </div>
                
                <!-- Pricing Breakdown -->
                <div class="bg-white border border-gray-200 rounded-xl p-6">
                    <h5 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-calculator mr-2 text-green-500"></i>Pricing Breakdown
                    </h5>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Base Service (${order.service_type.replace('_', ' ')})</span>
                            <span class="font-semibold">₱${order.pricing_breakdown.base_price.toFixed(2)}</span>
                        </div>
                        ${order.ironing ? `
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Ironing Service</span>
                                <span class="font-semibold">₱${order.pricing_breakdown.ironing_price.toFixed(2)}</span>
                            </div>
                        ` : ''}
                        ${order.express ? `
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Express Service</span>
                                <span class="font-semibold">₱${order.pricing_breakdown.express_price.toFixed(2)}</span>
                            </div>
                        ` : ''}
                        <div class="border-t pt-3">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-semibold text-gray-800">Total</span>
                                <span class="text-xl font-bold text-green-600">₱${order.pricing_breakdown.total_price.toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Timeline -->
                <div class="bg-white border border-gray-200 rounded-xl p-6">
                    <h5 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-clock mr-2 text-purple-500"></i>Order Timeline
                    </h5>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Order Created</span>
                            <span class="font-semibold">${new Date(order.created_at).toLocaleString()}</span>
                        </div>
                        ${order.estimated_completion ? `
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Estimated Completion</span>
                                <span class="font-semibold">${new Date(order.estimated_completion).toLocaleString()}</span>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    } else {
        // Regular order details
        container.innerHTML = `
            <div class="space-y-6">
                <!-- Order Header -->
                <div class="bg-gradient-to-r from-blue-50 to-purple-50 p-6 rounded-xl">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="text-2xl font-bold text-gray-800 mb-2">Order #${order.order_id}</h4>
                            <p class="text-lg text-gray-600">Regular Laundry Service</p>
                            <p class="text-sm text-gray-500 mt-1">
                                <i class="fas fa-calendar-alt mr-2"></i>
                                Created: ${new Date(order.created_at).toLocaleString()}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-3xl font-bold text-blue-600">₱${parseFloat(order.total_price).toFixed(2)}</p>
                            <div class="flex flex-col gap-2 mt-2">
                                <span class="px-3 py-1 text-sm font-medium rounded-full ${
                                    order.laundry_status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                    order.laundry_status === 'in_progress' ? 'bg-blue-100 text-blue-800' :
                                    order.laundry_status === 'ready' ? 'bg-green-100 text-green-800' :
                                    order.laundry_status === 'delivered' ? 'bg-green-100 text-green-800' :
                                    order.laundry_status === 'cancelled' ? 'bg-red-100 text-red-800' :
                                    'bg-gray-100 text-gray-800'
                                }">${order.laundry_status.replace('_', ' ')}</span>
                                <span class="px-3 py-1 text-sm font-medium rounded-full ${
                                    order.payment_status === 'paid' ? 'bg-green-100 text-green-800' : 
                                    order.payment_status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                    'bg-red-100 text-red-800'
                                }">${order.payment_status}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div class="bg-white border border-gray-200 rounded-xl p-6">
                    <h5 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-shopping-cart mr-2 text-blue-500"></i>Order Items
                    </h5>
                    <div class="space-y-3">
                        ${order.items.map(item => `
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <span class="font-semibold text-gray-800">${item.name}</span>
                                    <span class="text-sm text-gray-500 ml-2">Qty: ${item.quantity}</span>
                                    <span class="text-xs text-gray-400 ml-2">@ ₱${parseFloat(item.unit_price).toFixed(2)} each</span>
                                </div>
                                <span class="font-semibold text-gray-800">₱${parseFloat(item.total_price).toFixed(2)}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
                
                <!-- Service Details -->
                <div class="bg-white border border-gray-200 rounded-xl p-6">
                    <h5 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-cogs mr-2 text-green-500"></i>Service Details
                    </h5>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-gray-500 font-medium mb-1">Basket Count</p>
                            <p class="text-lg font-semibold text-gray-800">${order.basket_count}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium mb-1">Detergent Quantity</p>
                            <p class="text-lg font-semibold text-gray-800">${order.detergent_qty}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium mb-1">Softener Quantity</p>
                            <p class="text-lg font-semibold text-gray-800">${order.softener_qty}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium mb-1">Clothing Type</p>
                            <p class="text-lg font-semibold text-gray-800 capitalize">${order.clothing_type}</p>
                        </div>
                    </div>
                    
                    ${order.package !== 'none' ? `
                        <div class="mt-4">
                            <p class="text-sm text-gray-500 font-medium mb-1">Package</p>
                            <span class="px-3 py-1 bg-purple-100 text-purple-800 text-sm font-medium rounded-full">
                                ${order.package}
                            </span>
                        </div>
                    ` : ''}
                    
                    ${order.remarks ? `
                        <div class="mt-4 p-4 bg-yellow-50 rounded-lg">
                            <p class="text-sm text-gray-500 font-medium mb-2">Special Instructions</p>
                            <p class="text-sm text-gray-800">${order.remarks}</p>
                        </div>
                    ` : ''}
                </div>
                
                <!-- Order Timeline -->
                <div class="bg-white border border-gray-200 rounded-xl p-6">
                    <h5 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-clock mr-2 text-purple-500"></i>Order Timeline
                    </h5>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Order Created</span>
                            <span class="font-semibold">${new Date(order.created_at).toLocaleString()}</span>
                        </div>
                        ${order.estimated_completion ? `
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Estimated Completion</span>
                                <span class="font-semibold">${new Date(order.estimated_completion).toLocaleString()}</span>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }
}

function closeOrderModal() {
    document.getElementById('orderModal').classList.add('hidden');
    currentOrderId = null;
    currentOrderType = null;
}

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('orderModal');
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeOrderModal();
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include '../../layout.php';
?>


