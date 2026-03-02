<?php
require_once '../../config.php';
if (!auth() || !in_array($_SESSION['position'], ['admin', 'driver'])) redirect('login.php');

$pageTitle = 'Payment Scanner';
ob_start();
?>

<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Payment Scanner</h2>
        <div class="flex space-x-3">
            <button onclick="toggleScanner()" id="scannerToggleBtn" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                <i class="fas fa-qrcode mr-2"></i>Start Scanner
            </button>
            <button onclick="manualPayment()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <i class="fas fa-keyboard mr-2"></i>Manual Entry
            </button>
        </div>
    </div>

    <!-- Scanner Interface -->
    <div id="scannerInterface" class="hidden">
        <div class="bg-gray-50 p-6 rounded-lg mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Scan Customer Payment</h3>
            
            <!-- Camera Scanner -->
            <div class="relative">
                <div id="scannerContainer" class="bg-black rounded-lg overflow-hidden mb-4">
                    <video id="scannerVideo" width="100%" height="300" class="hidden"></video>
                    <canvas id="scannerCanvas" width="640" height="480" class="hidden"></canvas>
                    <div id="scannerPlaceholder" class="flex items-center justify-center h-64 bg-gray-200 text-gray-500">
                        <div class="text-center">
                            <i class="fas fa-qrcode text-4xl mb-2"></i>
                            <p>Camera will appear here</p>
                        </div>
                    </div>
                </div>
                
                <!-- Scanner Controls -->
                <div class="flex justify-center space-x-4">
                    <button onclick="startCamera()" id="startCameraBtn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <i class="fas fa-camera mr-2"></i>Start Camera
                    </button>
                    <button onclick="stopCamera()" id="stopCameraBtn" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 hidden">
                        <i class="fas fa-stop mr-2"></i>Stop Camera
                    </button>
                    <button onclick="captureFrame()" id="captureBtn" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 hidden">
                        <i class="fas fa-camera mr-2"></i>Capture & Scan
                    </button>
                </div>
            </div>
            
            <!-- Scan Result -->
            <div id="scanResult" class="hidden mt-4 p-4 bg-blue-50 rounded-lg">
                <h4 class="font-semibold text-gray-800 mb-2">Scan Result:</h4>
                <p id="scanResultText" class="text-gray-700"></p>
            </div>
        </div>
    </div>

    <!-- Manual Payment Entry -->
    <div id="manualPaymentInterface" class="hidden">
        <div class="bg-gray-50 p-6 rounded-lg mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Manual Payment Entry</h3>
            
            <form id="manualPaymentForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Order ID</label>
                    <input type="text" id="orderId" name="order_id" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Enter order ID or scan QR code">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Amount</label>
                    <input type="number" id="paymentAmount" name="payment_amount" step="0.01" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="0.00">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                    <select id="paymentMethod" name="payment_method" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select payment method</option>
                        <option value="cash">Cash</option>
                        <option value="gcash">GCash</option>
                        <option value="paymaya">PayMaya</option>
                        <option value="credit_card">Credit Card</option>
                        <option value="debit_card">Debit Card</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Reference</label>
                    <input type="text" id="transactionRef" name="transaction_ref"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Reference number (optional)">
                </div>
                
                <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                    <i class="fas fa-check mr-2"></i>Process Payment
                </button>
            </form>
        </div>
    </div>

    <!-- Order Search -->
    <div class="bg-gray-50 p-4 rounded-lg mb-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-3">Search Order</h3>
        <div class="flex space-x-4">
            <div class="flex-1">
                <input type="text" id="orderSearch" placeholder="Enter Order ID or Customer Name..." 
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button onclick="searchOrder()" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <i class="fas fa-search mr-2"></i>Search
            </button>
        </div>
    </div>

    <!-- Order Details -->
    <div id="orderDetails" class="hidden bg-white border border-gray-200 rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Order Information</h3>
        <div id="orderInfo" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Order information will be loaded here -->
        </div>
        
        <div class="mt-6 flex justify-end space-x-3">
            <button onclick="clearOrder()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500">
                Clear
            </button>
            <button onclick="processPayment()" id="processPaymentBtn" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                <i class="fas fa-credit-card mr-2"></i>Process Payment
            </button>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Payments</h3>
        <div id="recentPayments" class="space-y-3">
            <!-- Recent payments will be loaded here -->
        </div>
    </div>
</div>

<!-- Payment Processing Modal -->
<div id="paymentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Process Payment</h3>
                    <button type="button" id="closePaymentModal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div id="paymentForm" class="space-y-4">
                    <!-- Payment form will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentOrder = null;
    let stream = null;
    let isScanning = false;

    // Load recent payments
    loadRecentPayments();

    // Event listeners
    document.getElementById('closePaymentModal').addEventListener('click', closePaymentModal);
    document.getElementById('paymentModal').addEventListener('click', function(e) {
        if (e.target === this) closePaymentModal();
    });

    // Manual payment form
    document.getElementById('manualPaymentForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const orderId = formData.get('order_id');
        
        // Search for order first
        await searchOrderById(orderId);
        
        if (currentOrder) {
            // Pre-fill payment form
            document.getElementById('paymentAmount').value = currentOrder.total_price;
            processPayment();
        } else {
            showNotification('Order not found', 'error');
        }
    });

    // Order search
    document.getElementById('orderSearch').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchOrder();
        }
    });

    // Toggle scanner interface
    window.toggleScanner = function() {
        const scannerInterface = document.getElementById('scannerInterface');
        const manualInterface = document.getElementById('manualPaymentInterface');
        const scannerBtn = document.getElementById('scannerToggleBtn');
        
        if (scannerInterface.classList.contains('hidden')) {
            scannerInterface.classList.remove('hidden');
            manualInterface.classList.add('hidden');
            scannerBtn.innerHTML = '<i class="fas fa-keyboard mr-2"></i>Manual Entry';
        } else {
            scannerInterface.classList.add('hidden');
            manualInterface.classList.remove('hidden');
            scannerBtn.innerHTML = '<i class="fas fa-qrcode mr-2"></i>Start Scanner';
        }
    };

    // Manual payment interface
    window.manualPayment = function() {
        const scannerInterface = document.getElementById('scannerInterface');
        const manualInterface = document.getElementById('manualPaymentInterface');
        
        scannerInterface.classList.add('hidden');
        manualInterface.classList.remove('hidden');
        
        // Stop camera if running
        stopCamera();
    };

    // Start camera
    window.startCamera = async function() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    facingMode: 'environment',
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                } 
            });
            
            const video = document.getElementById('scannerVideo');
            video.srcObject = stream;
            video.classList.remove('hidden');
            document.getElementById('scannerPlaceholder').classList.add('hidden');
            document.getElementById('startCameraBtn').classList.add('hidden');
            document.getElementById('stopCameraBtn').classList.remove('hidden');
            document.getElementById('captureBtn').classList.remove('hidden');
            
            isScanning = true;
            showNotification('Camera started successfully', 'success');
        } catch (error) {
            console.error('Error starting camera:', error);
            showNotification('Error starting camera. Please check permissions.', 'error');
        }
    };

    // Stop camera
    window.stopCamera = function() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        
        document.getElementById('scannerVideo').classList.add('hidden');
        document.getElementById('scannerPlaceholder').classList.remove('hidden');
        document.getElementById('startCameraBtn').classList.remove('hidden');
        document.getElementById('stopCameraBtn').classList.add('hidden');
        document.getElementById('captureBtn').classList.add('hidden');
        
        isScanning = false;
        showNotification('Camera stopped', 'info');
    };

    // Capture frame and scan
    window.captureFrame = function() {
        const video = document.getElementById('scannerVideo');
        const canvas = document.getElementById('scannerCanvas');
        const ctx = canvas.getContext('2d');
        
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0);
        
        // Simulate QR code scanning (in real implementation, use a QR code library)
        const scanResult = simulateQRScan();
        
        if (scanResult) {
            document.getElementById('scanResult').classList.remove('hidden');
            document.getElementById('scanResultText').textContent = scanResult;
            
            // Try to parse as order ID
            if (scanResult.startsWith('ORDER_')) {
                const orderId = scanResult.replace('ORDER_', '');
                searchOrderById(orderId);
            }
        } else {
            showNotification('No QR code detected. Please try again.', 'error');
        }
    };

    // Simulate QR code scanning (replace with actual QR code library)
    function simulateQRScan() {
        // In a real implementation, you would use a library like jsQR or QuaggaJS
        // For demo purposes, we'll simulate scanning
        const mockCodes = [
            'ORDER_12345',
            'ORDER_67890',
            'ORDER_11111'
        ];
        
        return mockCodes[Math.floor(Math.random() * mockCodes.length)];
    }

    // Search order
    window.searchOrder = async function() {
        const searchTerm = document.getElementById('orderSearch').value.trim();
        if (!searchTerm) {
            showNotification('Please enter a search term', 'error');
            return;
        }
        
        await searchOrderById(searchTerm);
    };

    // Search order by ID
    async function searchOrderById(orderId) {
        try {
            const response = await fetch(`../../api/orders.php?search=${encodeURIComponent(orderId)}`);
            const data = await response.json();
            
            if (data.success && data.data.length > 0) {
                currentOrder = data.data[0];
                displayOrderDetails(currentOrder);
            } else {
                showNotification('Order not found', 'error');
                currentOrder = null;
            }
        } catch (error) {
            console.error('Error searching order:', error);
            showNotification('Error searching order', 'error');
        }
    };

    // Display order details
    function displayOrderDetails(order) {
        const container = document.getElementById('orderDetails');
        const orderInfo = document.getElementById('orderInfo');
        
        orderInfo.innerHTML = `
            <div>
                <h4 class="font-semibold text-gray-800 mb-2">Order Information</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Order ID:</span>
                        <span class="font-semibold">#${String(order.payment_id).padStart(6, '0')}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Customer:</span>
                        <span class="font-semibold">${order.customer_name}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Phone:</span>
                        <span class="font-semibold">${order.customer_phone}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="font-semibold capitalize">${order.laundry_status.replace('_', ' ')}</span>
                    </div>
                </div>
            </div>
            <div>
                <h4 class="font-semibold text-gray-800 mb-2">Payment Information</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Amount:</span>
                        <span class="font-semibold text-green-600">₱${parseFloat(order.total_price).toFixed(2)}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Payment Status:</span>
                        <span class="font-semibold ${order.payment_status === 'paid' ? 'text-green-600' : 'text-orange-600'}">${order.payment_status}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Payment Method:</span>
                        <span class="font-semibold">${order.payment_method_name}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Order Date:</span>
                        <span class="font-semibold">${new Date(order.created_at).toLocaleDateString()}</span>
                    </div>
                </div>
            </div>
        `;
        
        container.classList.remove('hidden');
        
        // Enable/disable process payment button and show delivery button
        const processBtn = document.getElementById('processPaymentBtn');
        const actionButtons = container.querySelector('.mt-6');
        
        if (order.payment_status === 'paid') {
            if (order.laundry_status !== 'delivered') {
                // Payment is paid but not delivered - show mark delivery button
                actionButtons.innerHTML = `
                    <button onclick="clearOrder()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Clear
                    </button>
                    <button onclick="markDeliveryComplete(${order.payment_id})" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <i class="fas fa-truck mr-2"></i>Mark Delivery Complete
                    </button>
                `;
            } else {
                // Already paid and delivered
                actionButtons.innerHTML = `
                    <button onclick="clearOrder()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Clear
                    </button>
                    <button disabled class="px-4 py-2 bg-gray-400 text-white rounded-md cursor-not-allowed">
                        <i class="fas fa-check mr-2"></i>Payment & Delivery Complete
                    </button>
                `;
            }
        } else {
            // Not paid yet - show process payment button
            processBtn.disabled = false;
            processBtn.innerHTML = '<i class="fas fa-credit-card mr-2"></i>Process Payment';
            processBtn.className = 'px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500';
        }
    };

    // Process payment
    window.processPayment = function() {
        if (!currentOrder) {
            showNotification('Please select an order first', 'error');
            return;
        }
        
        if (currentOrder.payment_status === 'paid') {
            showNotification('This order is already paid', 'error');
            return;
        }
        
        openPaymentModal();
    };

    // Open payment modal
    function openPaymentModal() {
        const modal = document.getElementById('paymentModal');
        const form = document.getElementById('paymentForm');
        
        form.innerHTML = `
            <div class="bg-blue-50 p-4 rounded-lg mb-4">
                <h4 class="font-semibold text-gray-800">Order #${String(currentOrder.payment_id).padStart(6, '0')}</h4>
                <p class="text-sm text-gray-600">Customer: ${currentOrder.customer_name}</p>
                <p class="text-lg font-bold text-green-600">Amount: ₱${parseFloat(currentOrder.total_price).toFixed(2)}</p>
                <p class="text-sm text-gray-600 mt-2">
                    <i class="fas fa-money-bill-wave mr-1"></i>Payment Method: <span class="font-semibold text-green-600">Cash</span>
                </p>
            </div>
            
            <form id="paymentProcessForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount Received</label>
                    <input type="number" name="amount_received" step="0.01" value="${currentOrder.total_price}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="bg-blue-50 p-4 rounded-lg space-y-3">
                    <div class="flex items-center">
                        <input type="checkbox" id="markPaymentComplete" name="mark_payment_complete" checked
                               class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                        <label for="markPaymentComplete" class="ml-2 text-sm font-medium text-gray-700">
                            <i class="fas fa-check-circle text-green-600 mr-1"></i>Mark Payment as Complete
                        </label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" id="markDeliveryComplete" name="mark_delivery_complete"
                               class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="markDeliveryComplete" class="ml-2 text-sm font-medium text-gray-700">
                            <i class="fas fa-truck text-blue-600 mr-1"></i>Mark Delivery as Complete (Order Delivered)
                        </label>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closePaymentModal()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                        <i class="fas fa-check mr-2"></i>Confirm Payment & Complete
                    </button>
                </div>
            </form>
        `;
        
        modal.classList.remove('hidden');
        
        // Add form submit handler
        document.getElementById('paymentProcessForm').addEventListener('submit', handlePaymentSubmission);
    };

    // Close payment modal
    function closePaymentModal() {
        document.getElementById('paymentModal').classList.add('hidden');
    };

    // Handle payment submission
    async function handlePaymentSubmission(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const paymentData = {
            order_id: currentOrder.payment_id,
            payment_method: 'cash',
            amount_received: parseFloat(formData.get('amount_received')),
            transaction_ref: '',
            notes: '',
            mark_payment_complete: formData.get('mark_payment_complete') === 'on',
            mark_delivered: formData.get('mark_delivery_complete') === 'on',
            processed_by: <?php echo $_SESSION['user_id']; ?>
        };
        
        try {
            const response = await fetch('../../api/driver_payments.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(paymentData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                let message = 'Payment processed successfully!';
                if (paymentData.mark_delivered) {
                    message += ' Order marked as delivered to customer.';
                }
                showNotification(message, 'success');
                closePaymentModal();
                clearOrder();
                loadRecentPayments();
            } else {
                showNotification(result.message || 'Error processing payment', 'error');
            }
        } catch (error) {
            console.error('Error processing payment:', error);
            showNotification('Error processing payment', 'error');
        }
    };

    // Mark delivery complete
    window.markDeliveryComplete = async function(orderId) {
        if (!confirm('Mark this order as delivered to customer?')) {
            return;
        }
        
        try {
            const response = await fetch('../../api/orders.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    payment_id: orderId,
                    payment_status: 'paid',
                    laundry_status: 'delivered'
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showNotification('Order marked as delivered successfully!', 'success');
                // Refresh order details
                await searchOrderById(orderId);
            } else {
                showNotification(result.message || 'Error marking delivery complete', 'error');
            }
        } catch (error) {
            console.error('Error marking delivery complete:', error);
            showNotification('Error marking delivery complete', 'error');
        }
    };

    // Clear order
    window.clearOrder = function() {
        currentOrder = null;
        document.getElementById('orderDetails').classList.add('hidden');
        document.getElementById('orderSearch').value = '';
    };

    // Load recent payments
    async function loadRecentPayments() {
        try {
            const response = await fetch('../../api/driver_payments.php');
            const data = await response.json();
            
            if (data.success) {
                displayRecentPayments(data.data);
            }
        } catch (error) {
            console.error('Error loading recent payments:', error);
        }
    };

    // Display recent payments
    function displayRecentPayments(payments) {
        const container = document.getElementById('recentPayments');
        
        if (payments.length === 0) {
            container.innerHTML = '<p class="text-gray-500 text-center py-4">No recent payments</p>';
            return;
        }
        
        container.innerHTML = '';
        
        payments.slice(0, 5).forEach(payment => {
            const paymentDiv = document.createElement('div');
            paymentDiv.className = 'flex justify-between items-center p-3 bg-gray-50 rounded-lg';
            paymentDiv.innerHTML = `
                <div>
                    <p class="font-semibold text-gray-800">Order #${String(payment.order_id).padStart(6, '0')}</p>
                    <p class="text-sm text-gray-600">${payment.customer_name}</p>
                </div>
                <div class="text-right">
                    <p class="font-bold text-green-600">₱${parseFloat(payment.amount_received).toFixed(2)}</p>
                    <p class="text-sm text-gray-500">${payment.payment_method}</p>
                </div>
            `;
            container.appendChild(paymentDiv);
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
