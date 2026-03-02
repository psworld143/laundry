<?php
require_once '../../config.php';
if (!auth()) redirect('login.php');

$pageTitle = 'My Custom Orders';
ob_start();
?>

<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">My Custom Orders</h2>
        <a href="new-order.php" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <i class="fas fa-plus mr-2"></i>Create New Order
        </a>
    </div>

    <!-- Orders List -->
    <div id="ordersList" class="space-y-4">
        <!-- Orders will be loaded here -->
    </div>

    <!-- Empty State -->
    <div id="emptyState" class="text-center py-12 hidden">
        <i class="fas fa-shopping-bag text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-600 mb-2">No Custom Orders Yet</h3>
        <p class="text-gray-500 mb-4">Create your first custom order</p>
        <a href="new-order.php" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <i class="fas fa-plus mr-2"></i>Create Order
        </a>
    </div>
</div>

<!-- Order Details Modal -->
<div id="orderModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-96 overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Order Details</h3>
                    <button type="button" id="closeOrderModal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div id="orderDetails" class="space-y-4">
                    <!-- Order details will be loaded here -->
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeOrderModal()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Close
                    </button>
                    <button type="button" id="cancelOrderBtn" onclick="cancelOrder()"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                        <i class="fas fa-times mr-2"></i>Cancel Order
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let orders = [];
    let currentOrderId = null;

    // Load orders
    loadOrders();

    // Modal functions
    function openOrderModal(orderId) {
        const order = orders.find(o => o.order_id == orderId);
        if (!order) return;

        currentOrderId = orderId;
        displayOrderDetails(order);
        document.getElementById('orderModal').classList.remove('hidden');
    }

    function closeOrderModal() {
        document.getElementById('orderModal').classList.add('hidden');
        currentOrderId = null;
    }

    // Event listeners
    document.getElementById('closeOrderModal').addEventListener('click', closeOrderModal);
    document.getElementById('orderModal').addEventListener('click', function(e) {
        if (e.target === this) closeOrderModal();
    });

    // Load orders
    async function loadOrders() {
        try {
            const response = await fetch('../../api/custom_orders.php');
            const data = await response.json();
            
            if (data.success) {
                orders = data.data;
                displayOrders();
            }
        } catch (error) {
            console.error('Error loading orders:', error);
            showNotification('Error loading orders', 'error');
        }
    }

    // Display orders
    function displayOrders() {
        const container = document.getElementById('ordersList');
        const emptyState = document.getElementById('emptyState');
        
        if (orders.length === 0) {
            container.innerHTML = '';
            emptyState.classList.remove('hidden');
            return;
        }
        
        emptyState.classList.add('hidden');
        container.innerHTML = '';

        orders.forEach(order => {
            const orderCard = document.createElement('div');
            orderCard.className = 'bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-lg transition-all duration-300';
            orderCard.innerHTML = `
                <!-- Header with Order Info and Status -->
                <div class="flex justify-between items-start mb-6">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="text-xl font-bold text-gray-800">Order #${String(order.order_id).padStart(6, '0')}</h3>
                            <span class="text-sm text-gray-500 font-medium">${order.service_type.replace('_', ' ')}</span>
                        </div>
                        <p class="text-sm text-gray-600 mb-1">${order.fabric_name} (${order.fabric_type})</p>
                        <p class="text-sm text-gray-500 mb-1">
                            <i class="fas fa-calendar-alt mr-2 text-gray-400"></i>
                            Created: ${new Date(order.created_at).toLocaleDateString()}
                        </p>
                        ${order.estimated_completion ? `
                            <p class="text-sm text-gray-500">
                                <i class="fas fa-clock mr-2 text-gray-400"></i>
                                Est. Completion: ${new Date(order.estimated_completion).toLocaleDateString()}
                            </p>
                        ` : ''}
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-green-600 mb-2">₱${parseFloat(order.subtotal).toFixed(2)}</p>
                        <div class="flex flex-col gap-1">
                            <span class="px-3 py-1 text-xs font-medium rounded-full ${
                                order.laundry_status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                order.laundry_status === 'in_progress' ? 'bg-blue-100 text-blue-800' :
                                order.laundry_status === 'ready' ? 'bg-green-100 text-green-800' :
                                order.laundry_status === 'delivered' ? 'bg-green-100 text-green-800' :
                                order.laundry_status === 'cancelled' ? 'bg-red-100 text-red-800' :
                                'bg-gray-100 text-gray-800'
                            }">${order.laundry_status.replace('_', ' ')}</span>
                            <span class="px-3 py-1 text-xs font-medium rounded-full ${
                                order.payment_status === 'paid' ? 'bg-green-100 text-green-800' : 
                                order.payment_status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                'bg-red-100 text-red-800'
                            }">${order.payment_status}</span>
                        </div>
                    </div>
                </div>
                
                <!-- Order Details Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
                    <div>
                        <p class="text-sm text-gray-500 font-medium mb-1">Service Type</p>
                        <p class="text-lg font-semibold text-gray-800 capitalize">${order.service_type.replace('_', ' ')}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium mb-1">Soap Type</p>
                        <p class="text-lg font-semibold text-gray-800 capitalize">${order.soap_type}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium mb-1">Fabric Type</p>
                        <p class="text-lg font-semibold text-gray-800 capitalize">${order.fabric_type}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium mb-1">Fabric Name</p>
                        <p class="text-lg font-semibold text-gray-800">${order.fabric_name}</p>
                    </div>
                </div>
                
                <!-- Special Instructions -->
                ${order.special_instructions ? `
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-500 font-medium mb-2">Special Instructions</p>
                        <p class="text-sm text-gray-800">${order.special_instructions}</p>
                    </div>
                ` : ''}
                
                <!-- Footer with Actions -->
                <div class="flex justify-between items-center pt-4 border-t border-gray-100">
                    <div class="flex space-x-2">
                        ${order.ironing ? '<span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-full">Ironing</span>' : ''}
                        ${order.express ? '<span class="px-3 py-1 bg-purple-100 text-purple-800 text-xs font-medium rounded-full">Express</span>' : ''}
                    </div>
                    <button onclick="openOrderModal(${order.order_id})" 
                            class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors duration-200">
                        <i class="fas fa-eye mr-2"></i>View Details
                    </button>
                </div>
            `;
            container.appendChild(orderCard);
        });
    }

    // Display order details
    function displayOrderDetails(order) {
        const container = document.getElementById('orderDetails');
        container.innerHTML = `
            <div class="bg-blue-50 p-4 rounded-lg">
                <h4 class="font-semibold text-gray-800 mb-2">Service Details</h4>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Service Type:</p>
                        <p class="font-semibold capitalize">${order.service_type.replace('_', ' ')}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Soap/Detergent:</p>
                        <p class="font-semibold capitalize">${order.soap_type}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Ironing:</p>
                        <p class="font-semibold">${order.ironing ? 'Yes (+₱30.00)' : 'No'}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Express:</p>
                        <p class="font-semibold">${order.express ? 'Yes (+₱30.00)' : 'No'}</p>
                    </div>
                </div>
                ${order.special_instructions ? `
                    <div class="mt-3">
                        <p class="text-sm text-gray-600">Special Instructions:</p>
                        <p class="text-sm text-gray-800">${order.special_instructions}</p>
                    </div>
                ` : ''}
            </div>
            
            <div class="bg-green-50 p-4 rounded-lg">
                <h4 class="font-semibold text-gray-800 mb-2">Order Status</h4>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Order ID:</p>
                        <p class="font-semibold">#${order.order_id}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Payment Status:</p>
                        <p class="font-semibold capitalize">${order.payment_status}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Laundry Status:</p>
                        <p class="font-semibold capitalize">${order.laundry_status.replace('_', ' ')}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Estimated Completion:</p>
                        <p class="font-semibold">${order.estimated_completion ? new Date(order.estimated_completion).toLocaleString() : 'TBD'}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Total Price:</p>
                        <p class="font-semibold text-green-600">₱${parseFloat(order.subtotal).toFixed(2)}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Created:</p>
                        <p class="font-semibold">${new Date(order.created_at).toLocaleString()}</p>
                    </div>
                </div>
            </div>
        `;

        // Show/hide cancel button based on status
        const cancelBtn = document.getElementById('cancelOrderBtn');
        if (order.laundry_status === 'cancelled' || order.laundry_status === 'delivered') {
            cancelBtn.style.display = 'none';
        } else {
            cancelBtn.style.display = 'inline-block';
        }
    }

    // Cancel order
    window.cancelOrder = function() {
        if (!currentOrderId) return;
        
        if (!confirm('Are you sure you want to cancel this order?')) return;

        fetch('../../api/custom_orders.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ order_id: currentOrderId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Order cancelled successfully', 'success');
                closeOrderModal();
                loadOrders();
            } else {
                showNotification(data.message || 'Error cancelling order', 'error');
            }
        })
        .catch(error => {
            console.error('Error cancelling order:', error);
            showNotification('Error cancelling order', 'error');
        });
    };

    // Notification function
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
            type === 'success' ? 'bg-green-500 text-white' :
            type === 'error' ? 'bg-red-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
});
</script>

<?php
$content = ob_get_clean();
include '../../layout.php';
?>
