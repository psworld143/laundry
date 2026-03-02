<?php
require_once '../../config.php';
if (!auth()) redirect('login.php');

// Get order statistics
$orderStats = [
    'total_orders' => $db->query("SELECT COUNT(*) FROM transactions")->fetchColumn(),
    'pending' => $db->query("SELECT COUNT(*) FROM transactions WHERE laundry_status = 'pending'")->fetchColumn(),
    'in_progress' => $db->query("SELECT COUNT(*) FROM transactions WHERE laundry_status IN ('in_progress', 'washing', 'drying', 'ironing')")->fetchColumn(),
    'ready' => $db->query("SELECT COUNT(*) FROM transactions WHERE laundry_status = 'ready'")->fetchColumn(),
    'delivered' => $db->query("SELECT COUNT(*) FROM transactions WHERE laundry_status = 'delivered'")->fetchColumn(),
    'total_revenue' => $db->query("SELECT SUM(total_price) FROM transactions WHERE payment_status = 'paid'")->fetchColumn() ?? 0,
    'unpaid' => $db->query("SELECT COUNT(*) FROM transactions WHERE payment_status IN ('pending', 'unpaid')")->fetchColumn(),
];

$pageTitle = 'Orders Management';
ob_start();
?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-shopping-cart text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">Total</span>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= $orderStats['total_orders'] ?></h3>
        <p class="text-blue-100 text-sm">Total Orders</p>
    </div>

    <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-clock text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">Pending</span>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= $orderStats['pending'] ?></h3>
        <p class="text-yellow-100 text-sm">Awaiting Processing</p>
    </div>

    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-spinner text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">Active</span>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= $orderStats['in_progress'] ?></h3>
        <p class="text-purple-100 text-sm">In Progress</p>
    </div>

    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-dollar-sign text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">Revenue</span>
        </div>
        <h3 class="text-4xl font-bold mb-1">₱<?= number_format($orderStats['total_revenue'], 2) ?></h3>
        <p class="text-green-100 text-sm">Total Revenue</p>
    </div>
</div>

<!-- Orders List -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Customer Orders</h2>
            <p class="text-gray-500 text-sm mt-1">Manage laundry orders and track their progress</p>
        </div>
        <div class="flex gap-3">
            <select id="statusFilter" onchange="filterOrders()" class="px-4 py-2 border-2 border-gray-300 rounded-lg outline-none focus:border-blue-500">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="washing">Washing</option>
                <option value="drying">Drying</option>
                <option value="ironing">Ironing</option>
                <option value="ready">Ready</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
    </div>

    <div id="ordersContainer">
        <div class="text-center py-12">
            <i class="fas fa-spinner fa-spin text-4xl text-blue-500 mb-4"></i>
            <p class="text-gray-500">Loading orders...</p>
        </div>
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

<!-- Update Status Modal -->
<div id="statusModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-8 max-w-md w-full shadow-2xl">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-gray-800">Update Order Status</h3>
            <button onclick="closeStatusModal()" class="text-gray-400 hover:text-gray-600 transition">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>

        <form id="statusForm" onsubmit="updateOrderStatus(event)">
            <input type="hidden" id="status_payment_id">
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2">
                    <i class="fas fa-tasks text-blue-500 mr-1"></i>Laundry Status *
                </label>
                <select id="laundry_status" required
                        class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition">
                    <option value="pending">⏳ Pending</option>
                    <option value="in_progress">🔄 In Progress</option>
                    <option value="washing">🧺 Washing</option>
                    <option value="drying">💨 Drying</option>
                    <option value="ironing">👕 Ironing</option>
                    <option value="ready">✅ Ready for Pickup</option>
                    <option value="delivered">🚚 Delivered</option>
                    <option value="cancelled">❌ Cancelled</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2">
                    <i class="fas fa-credit-card text-green-500 mr-1"></i>Payment Status *
                </label>
                <select id="payment_status" required
                        class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition">
                    <option value="pending">⏳ Pending</option>
                    <option value="unpaid">❌ Unpaid</option>
                    <option value="paid">✅ Paid</option>
                    <option value="refunded">🔙 Refunded</option>
                </select>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-medium mb-2">
                    <i class="fas fa-comment text-purple-500 mr-1"></i>Remarks
                </label>
                <textarea id="remarks" rows="3"
                          class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                          placeholder="Add any notes or comments..."></textarea>
            </div>

            <div class="flex gap-4">
                <button type="button" onclick="closeStatusModal()" 
                        class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 rounded-lg transition">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <button type="submit" 
                        class="flex-1 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-semibold py-3 rounded-lg transition shadow-lg">
                    <i class="fas fa-save mr-2"></i>Update Status
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let orders = [];
let filteredOrders = [];

// Load orders from API
async function loadOrders() {
    loading(true);
    try {
        const res = await Ajax.get('<?= BASE_URL ?>api/orders.php');
        console.log('Orders response:', res);
        
        if (res.success) {
            orders = res.data || [];
            filteredOrders = orders;
            console.log('Orders loaded:', orders.length);
            renderOrders();
        } else {
            showAlert('Failed to load orders: ' + res.message, 'error');
            renderEmptyState();
        }
    } catch (error) {
        console.error('Error loading orders:', error);
        showAlert('Error loading orders. Check console for details.', 'error');
        renderEmptyState();
    } finally {
        loading(false);
    }
}

// Filter orders by status
function filterOrders() {
    const status = document.getElementById('statusFilter').value;
    if (status === '') {
        filteredOrders = orders;
    } else {
        filteredOrders = orders.filter(order => order.laundry_status === status);
    }
    renderOrders();
}

// Render empty state
function renderEmptyState() {
    document.getElementById('ordersContainer').innerHTML = `
        <div class="text-center py-16">
            <div class="bg-gray-100 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-shopping-cart text-4xl text-gray-400"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">No Orders Yet</h3>
            <p class="text-gray-500 mb-6">Orders will appear here once customers start placing them</p>
        </div>
    `;
}

// Render orders
function renderOrders() {
    if (filteredOrders.length === 0) {
        renderEmptyState();
        return;
    }
    
    const statusConfig = {
        pending: { color: 'yellow', icon: 'fa-clock', label: 'Pending' },
        in_progress: { color: 'blue', icon: 'fa-spinner', label: 'In Progress' },
        washing: { color: 'cyan', icon: 'fa-water', label: 'Washing' },
        drying: { color: 'orange', icon: 'fa-wind', label: 'Drying' },
        ironing: { color: 'red', icon: 'fa-fire', label: 'Ironing' },
        ready: { color: 'green', icon: 'fa-check-circle', label: 'Ready' },
        delivered: { color: 'green', icon: 'fa-box', label: 'Delivered' },
        cancelled: { color: 'gray', icon: 'fa-times-circle', label: 'Cancelled' }
    };

    const paymentConfig = {
        pending: { color: 'yellow', label: 'Pending' },
        unpaid: { color: 'red', label: 'Unpaid' },
        paid: { color: 'green', label: 'Paid' },
        refunded: { color: 'purple', label: 'Refunded' }
    };
    
    const html = `
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr class="border-b-2 border-gray-200">
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Order ID</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Items</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Payment</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    ${filteredOrders.map(order => {
                        const status = statusConfig[order.laundry_status] || statusConfig.pending;
                        const payment = paymentConfig[order.payment_status] || paymentConfig.pending;
                        const itemCount = order.items ? order.items.length : 0;
                        
                        return `
                        <tr class="hover:bg-blue-50 transition">
                            <td class="px-6 py-4">
                                <span class="font-bold text-blue-600">#${order.payment_id}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div>
                                    <p class="font-semibold text-gray-800">${order.customer_name || 'N/A'}</p>
                                    <p class="text-xs text-gray-500">${order.customer_email || ''}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700">${itemCount} item(s)</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-bold text-lg text-gray-800">₱${parseFloat(order.total_price).toFixed(2)}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-${status.color}-100 text-${status.color}-800">
                                    <i class="fas ${status.icon} mr-1"></i>${status.label}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-${payment.color}-100 text-${payment.color}-800">
                                    ${payment.label}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">${new Date(order.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex gap-2">
                                    <button onclick="viewOrderDetails(${order.payment_id})" 
                                            class="bg-blue-100 hover:bg-blue-200 text-blue-600 p-2 rounded-lg transition" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="openStatusModal(${order.payment_id})" 
                                            class="bg-green-100 hover:bg-green-200 text-green-600 p-2 rounded-lg transition" title="Update Status">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteOrder(${order.payment_id}, '${order.customer_name}')" 
                                            class="bg-red-100 hover:bg-red-200 text-red-600 p-2 rounded-lg transition" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `}).join('')}
                </tbody>
            </table>
        </div>
    `;
    
    document.getElementById('ordersContainer').innerHTML = html;
}

// View order details
function viewOrderDetails(id) {
    const order = orders.find(o => o.payment_id == id);
    if (!order) {
        showAlert('Order not found', 'error');
        return;
    }

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

    const status = statusConfig[order.laundry_status] || statusConfig.pending;
    
    const content = `
        <div class="space-y-6">
            <!-- Order Header -->
            <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-6 border-2 border-blue-200">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h4 class="text-2xl font-bold text-gray-800">Order #${order.payment_id}</h4>
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
                        <p class="text-sm text-gray-600">Handled by</p>
                        <p class="font-semibold text-gray-800">${order.staff_name || 'Not assigned'}</p>
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
                                <p class="font-semibold text-gray-800">${item.item_name || item.service_name}</p>
                                <p class="text-sm text-gray-600">Quantity: ${item.quantity} × ₱${parseFloat(item.unit_price).toFixed(2)}</p>
                                ${item.special_instructions ? `<p class="text-xs text-gray-500 mt-1">Note: ${item.special_instructions}</p>` : ''}
                            </div>
                            <p class="font-bold text-lg text-gray-800">₱${parseFloat(item.total_price).toFixed(2)}</p>
                        </div>
                    `).join('') : '<p class="text-gray-500 text-center py-4">No items</p>'}
                </div>
            </div>

            <!-- Pricing -->
            <div class="bg-gray-50 rounded-xl p-4">
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal:</span>
                        <span class="font-semibold">₱${parseFloat(order.subtotal).toFixed(2)}</span>
                    </div>
                    ${order.discount_amount > 0 ? `
                    <div class="flex justify-between text-green-600">
                        <span>Discount:</span>
                        <span class="font-semibold">-₱${parseFloat(order.discount_amount).toFixed(2)}</span>
                    </div>
                    ` : ''}
                    <div class="border-t pt-2 flex justify-between text-lg font-bold">
                        <span>Total:</span>
                        <span class="text-blue-600">₱${parseFloat(order.total_price).toFixed(2)}</span>
                    </div>
                </div>
            </div>

            <!-- Payment Info -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">Payment Method</p>
                    <p class="font-semibold text-gray-800">${order.payment_method_name || 'N/A'}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">Payment Status</p>
                    <p class="font-semibold text-gray-800">${order.payment_status.toUpperCase()}</p>
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
    modal('orderDetailsModal', true);
}

// Close order details modal
function closeOrderDetailsModal() {
    modal('orderDetailsModal', false);
}

// Open status update modal
function openStatusModal(id) {
    const order = orders.find(o => o.payment_id == id);
    if (!order) {
        showAlert('Order not found', 'error');
        return;
    }
    
    document.getElementById('status_payment_id').value = order.payment_id;
    document.getElementById('laundry_status').value = order.laundry_status;
    document.getElementById('payment_status').value = order.payment_status;
    document.getElementById('remarks').value = order.remarks || '';
    
    modal('statusModal', true);
}

// Close status modal
function closeStatusModal() {
    modal('statusModal', false);
}

// Update order status
async function updateOrderStatus(e) {
    e.preventDefault();
    loading(true);
    
    const data = {
        payment_id: document.getElementById('status_payment_id').value,
        laundry_status: document.getElementById('laundry_status').value,
        payment_status: document.getElementById('payment_status').value,
        remarks: document.getElementById('remarks').value
    };

    try {
        const res = await Ajax.post('<?= BASE_URL ?>api/orders.php', data);
        showAlert(res.message || 'Order status updated successfully', 'success');
        closeStatusModal();
        loadOrders();
    } catch (error) {
        console.error('Error updating order status:', error);
        showAlert('Error updating order status', 'error');
    } finally {
        loading(false);
    }
}

// Delete order
async function deleteOrder(id, customerName) {
    const confirmed = confirm(`Are you sure you want to delete order #${id} for ${customerName}?\n\nThis action cannot be undone.`);
    if (!confirmed) return;
    
    loading(true);
    try {
        const res = await Ajax.delete('<?= BASE_URL ?>api/orders.php', { payment_id: id });
        showAlert(res.message || 'Order deleted successfully', 'success');
        loadOrders();
    } catch (error) {
        console.error('Error deleting order:', error);
        showAlert('Error deleting order', 'error');
    } finally {
        loading(false);
    }
}

// Load orders when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('Orders page loaded, loading orders...');
    loadOrders();
});
</script>

<?php
$content = ob_get_clean();
include '../../layout.php';
?>
