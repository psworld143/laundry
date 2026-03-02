<?php
require_once '../../config.php';
if (!auth() || !in_array($_SESSION['position'], ['admin', 'operator'])) redirect('login.php');

$pageTitle = 'Operator Dashboard';
ob_start();
?>

<div class="bg-white rounded-lg shadow-sm p-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Operator Dashboard</h2>
    
    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <a href="receipt.php" class="bg-blue-50 hover:bg-blue-100 p-6 rounded-lg border border-blue-200 transition-colors">
            <div class="flex items-center">
                <i class="fas fa-receipt text-3xl text-blue-600 mr-4"></i>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Receipt Management</h3>
                    <p class="text-gray-600">Generate and print receipts</p>
                </div>
            </div>
        </a>
        
        <a href="../admin/orders.php" class="bg-green-50 hover:bg-green-100 p-6 rounded-lg border border-green-200 transition-colors">
            <div class="flex items-center">
                <i class="fas fa-clipboard-list text-3xl text-green-600 mr-4"></i>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Order Management</h3>
                    <p class="text-gray-600">View and manage orders</p>
                </div>
            </div>
        </a>
        
        <a href="../admin/machines.php" class="bg-purple-50 hover:bg-purple-100 p-6 rounded-lg border border-purple-200 transition-colors">
            <div class="flex items-center">
                <i class="fas fa-cogs text-3xl text-purple-600 mr-4"></i>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Machine Status</h3>
                    <p class="text-gray-600">Monitor machine operations</p>
                </div>
            </div>
        </a>
    </div>
    
    <!-- Recent Orders -->
    <div class="bg-gray-50 p-6 rounded-lg">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Orders</h3>
        <div id="recentOrders" class="space-y-3">
            <!-- Recent orders will be loaded here -->
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadRecentOrders();
});

async function loadRecentOrders() {
    try {
        const response = await fetch('../../api/orders.php');
        const data = await response.json();
        
        if (data.success && data.data.length > 0) {
            const recentOrders = data.data.slice(0, 5); // Get latest 5 orders
            displayRecentOrders(recentOrders);
        }
    } catch (error) {
        console.error('Error loading recent orders:', error);
    }
}

function displayRecentOrders(orders) {
    const container = document.getElementById('recentOrders');
    container.innerHTML = '';

    orders.forEach(order => {
        const orderDiv = document.createElement('div');
        orderDiv.className = 'bg-white p-4 rounded-lg border border-gray-200 hover:shadow-md transition-shadow';
        orderDiv.innerHTML = `
            <div class="flex justify-between items-center">
                <div>
                    <h4 class="font-semibold text-gray-800">Order #${String(order.payment_id).padStart(6, '0')}</h4>
                    <p class="text-sm text-gray-600">Customer: ${order.customer_name}</p>
                    <p class="text-sm text-gray-500">${new Date(order.created_at).toLocaleDateString()}</p>
                </div>
                <div class="text-right">
                    <p class="font-bold text-blue-600">₱${parseFloat(order.total_price).toFixed(2)}</p>
                    <span class="inline-block px-2 py-1 text-xs rounded-full ${
                        order.payment_status === 'paid' ? 'bg-green-100 text-green-800' : 
                        order.payment_status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                        'bg-red-100 text-red-800'
                    }">${order.payment_status}</span>
                </div>
            </div>
        `;
        orderDiv.addEventListener('click', () => {
            window.location.href = `receipt.php?id=${order.payment_id}`;
        });
        container.appendChild(orderDiv);
    });
}
</script>

<?php
$content = ob_get_clean();
include '../../layout.php';
?>

