<?php
require_once '../config.php';
if (!auth()) redirect('login.php');

$pageTitle = 'Receipt Viewer';
ob_start();

// Get order ID from URL parameter
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : null;

// If order ID is provided, get order details
$order = null;
$orderItems = [];
$customOrder = null;
$operatorName = null;

if ($orderId) {
    try {
        // First try to get regular order
        $stmt = $db->prepare("
            SELECT 
                t.*,
                cu.name as customer_name,
                cu.email as customer_email,
                cu.phone_number as customer_phone,
                s.name as staff_name,
                su.name as staff_user_name,
                pm.method_name as payment_method_name,
                'regular' as order_type
            FROM transactions t
            LEFT JOIN users cu ON t.user_id = cu.user_id
            LEFT JOIN staff s ON t.staff_id = s.staff_id
            LEFT JOIN users su ON t.staff_id = su.user_id
            LEFT JOIN payment_methods pm ON t.payment_method_id = pm.method_id
            WHERE t.payment_id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            if (!empty($order['staff_name'])) {
                $operatorName = $order['staff_name'];
            } elseif (!empty($order['staff_user_name'])) {
                $operatorName = $order['staff_user_name'];
            }
            // Get order items
            $itemStmt = $db->prepare("
                SELECT ti.*, sv.service_name
                FROM transaction_items ti
                LEFT JOIN services sv ON ti.service_id = sv.service_id
                WHERE ti.payment_id = ?
            ");
            $itemStmt->execute([$orderId]);
            $orderItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Try custom order
            $stmt = $db->prepare("
                SELECT 
                    co.*,
                    cif.fabric_name,
                    cif.fabric_type,
                    cif.color,
                    cif.condition_status,
                    cif.special_instructions as fabric_instructions,
                    u.name as customer_name,
                    u.email as customer_email,
                    u.phone_number as customer_phone,
                    pm.method_name as payment_method_name,
                    'custom' as order_type
                FROM custom_orders co
                LEFT JOIN customer_inventory_fabric cif ON co.fabric_id = cif.fabric_id
                LEFT JOIN users u ON co.user_id = u.user_id
                LEFT JOIN payment_methods pm ON co.payment_method_id = pm.method_id
                WHERE co.order_id = ?
            ");
            $stmt->execute([$orderId]);
            $customOrder = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        error_log("Error fetching order: " . $e->getMessage());
    }
}

if (!$operatorName) {
    try {
        $fallbackStmt = $db->query("
            SELECT name 
            FROM staff 
            WHERE position = 'operator' AND is_active = 1 
            ORDER BY hire_date ASC 
            LIMIT 1
        ");
        if ($fallbackStmt) {
            $operatorName = $fallbackStmt->fetchColumn() ?: null;
        }
    } catch (Exception $e) {
        error_log("Error fetching fallback operator: " . $e->getMessage());
    }
}
?>

<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Receipt Viewer</h2>
        <div class="flex space-x-3">
            <button onclick="printReceipt()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <i class="fas fa-print mr-2"></i>Print Receipt
            </button>
            <button onclick="shareReceipt()" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                <i class="fas fa-share mr-2"></i>Share
            </button>
        </div>
    </div>

    <!-- Receipt Search -->
    <div class="bg-gray-50 p-4 rounded-lg mb-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-3">Search Receipt</h3>
        <div class="flex space-x-4">
            <div class="flex-1">
                <input type="text" id="receiptSearch" placeholder="Enter Order ID, Customer Name, or Phone Number..." 
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button onclick="searchReceipt()" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <i class="fas fa-search mr-2"></i>Search
            </button>
        </div>
    </div>

    <?php if ($order || $customOrder): ?>
    <!-- Receipt Display -->
    <div id="receiptContent" class="bg-white border-2 border-gray-200 rounded-lg p-8 max-w-2xl mx-auto">
        <!-- Receipt Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">LaundryPro</h1>
            <p class="text-gray-600">Professional Laundry Services</p>
            <p class="text-sm text-gray-500 mt-2">123 Main Street, City, Philippines</p>
            <p class="text-sm text-gray-500">Phone: (02) 123-4567 | Email: info@laundrypro.com</p>
        </div>

        <!-- Receipt Details -->
        <div class="border-t border-b border-gray-300 py-4 mb-6">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <h3 class="font-semibold text-gray-800 mb-2">Receipt Information</h3>
                    <p class="text-sm text-gray-600">Receipt #: <span class="font-semibold"><?php echo str_pad($orderId, 6, '0', STR_PAD_LEFT); ?></span></p>
                    <p class="text-sm text-gray-600">Date: <span class="font-semibold"><?php echo date('M d, Y', strtotime($order ? $order['created_at'] : $customOrder['created_at'])); ?></span></p>
                    <p class="text-sm text-gray-600">Time: <span class="font-semibold"><?php echo date('h:i A', strtotime($order ? $order['created_at'] : $customOrder['created_at'])); ?></span></p>
                    <p class="text-sm text-gray-600">Type: <span class="font-semibold"><?php echo ($order ? 'Regular Order' : 'Custom Order'); ?></span></p>
                    <p class="text-sm text-gray-600">
                        Operator:
                        <?php if ($operatorName): ?>
                            <span class="font-semibold"><?php echo htmlspecialchars($operatorName); ?></span>
                        <?php else: ?>
                            <span class="italic text-gray-500">Not assigned</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800 mb-2">Customer Information</h3>
                    <p class="text-sm text-gray-600">Name: <span class="font-semibold"><?php echo htmlspecialchars($order ? $order['customer_name'] : $customOrder['customer_name']); ?></span></p>
                    <p class="text-sm text-gray-600">Phone: <span class="font-semibold"><?php echo htmlspecialchars($order ? $order['customer_phone'] : $customOrder['customer_phone']); ?></span></p>
                    <p class="text-sm text-gray-600">Email: <span class="font-semibold"><?php echo htmlspecialchars($order ? $order['customer_email'] : $customOrder['customer_email']); ?></span></p>
                </div>
            </div>
        </div>

        <?php if ($order): ?>
        <!-- Regular Order Items -->
        <div class="mb-6">
            <h3 class="font-semibold text-gray-800 mb-3">Order Items</h3>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border border-gray-300 px-3 py-2 text-left text-sm font-semibold">Item</th>
                            <th class="border border-gray-300 px-3 py-2 text-center text-sm font-semibold">Qty</th>
                            <th class="border border-gray-300 px-3 py-2 text-right text-sm font-semibold">Unit Price</th>
                            <th class="border border-gray-300 px-3 py-2 text-right text-sm font-semibold">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td class="border border-gray-300 px-3 py-2 text-sm">
                                <?php echo htmlspecialchars($item['item_name']); ?>
                                <?php if ($item['special_instructions']): ?>
                                    <br><small class="text-gray-500"><?php echo htmlspecialchars($item['special_instructions']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="border border-gray-300 px-3 py-2 text-center text-sm"><?php echo $item['quantity']; ?></td>
                            <td class="border border-gray-300 px-3 py-2 text-right text-sm">₱<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td class="border border-gray-300 px-3 py-2 text-right text-sm font-semibold">₱<?php echo number_format($item['total_price'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <!-- Custom Order Items -->
        <div class="mb-6">
            <h3 class="font-semibold text-gray-800 mb-3">Custom Order Details</h3>
            <div class="bg-gray-50 p-4 rounded-lg">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-semibold text-gray-800 mb-2">Fabric Information</h4>
                        <p class="text-sm text-gray-600">Name: <span class="font-semibold"><?php echo htmlspecialchars($customOrder['fabric_name']); ?></span></p>
                        <p class="text-sm text-gray-600">Type: <span class="font-semibold"><?php echo htmlspecialchars($customOrder['fabric_type']); ?></span></p>
                        <p class="text-sm text-gray-600">Color: <span class="font-semibold"><?php echo htmlspecialchars($customOrder['color']); ?></span></p>
                        <p class="text-sm text-gray-600">Condition: <span class="font-semibold"><?php echo htmlspecialchars($customOrder['condition_status']); ?></span></p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-800 mb-2">Service Details</h4>
                        <p class="text-sm text-gray-600">Service: <span class="font-semibold"><?php echo ucfirst(str_replace('_', ' ', $customOrder['service_type'])); ?></span></p>
                        <p class="text-sm text-gray-600">Soap: <span class="font-semibold"><?php echo ucfirst($customOrder['soap_type']); ?></span></p>
                        <p class="text-sm text-gray-600">Ironing: <span class="font-semibold"><?php echo $customOrder['ironing'] ? 'Yes (+₱30.00)' : 'No'; ?></span></p>
                        <p class="text-sm text-gray-600">Express: <span class="font-semibold"><?php echo $customOrder['express'] ? 'Yes (+₱30.00)' : 'No'; ?></span></p>
                    </div>
                </div>
                <?php if ($customOrder['fabric_instructions']): ?>
                <div class="mt-3">
                    <h4 class="font-semibold text-gray-800 mb-1">Fabric Instructions</h4>
                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($customOrder['fabric_instructions']); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Order Summary -->
        <div class="border-t border-gray-300 pt-4 mb-6">
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Subtotal:</span>
                    <span class="font-semibold">₱<?php echo number_format($order ? $order['subtotal'] : $customOrder['subtotal'], 2); ?></span>
                </div>
                <?php if ($order && $order['discount_amount'] > 0): ?>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Discount:</span>
                    <span class="font-semibold text-green-600">-₱<?php echo number_format($order['discount_amount'], 2); ?></span>
                </div>
                <?php endif; ?>
                <div class="flex justify-between text-lg font-bold border-t border-gray-300 pt-2">
                    <span>Total:</span>
                    <span class="text-blue-600">₱<?php echo number_format($order ? $order['total_price'] : $customOrder['subtotal'], 2); ?></span>
                </div>
            </div>
        </div>

        <!-- Payment Information -->
        <div class="border-t border-gray-300 pt-4 mb-6">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <h3 class="font-semibold text-gray-800 mb-2">Payment Information</h3>
                    <p class="text-sm text-gray-600">Method: <span class="font-semibold"><?php echo htmlspecialchars($order ? $order['payment_method_name'] : $customOrder['payment_method_name']); ?></span></p>
                    <p class="text-sm text-gray-600">Status: <span class="font-semibold <?php echo ($order ? $order['payment_status'] : $customOrder['payment_status']) === 'paid' ? 'text-green-600' : 'text-orange-600'; ?>"><?php echo ucfirst($order ? $order['payment_status'] : $customOrder['payment_status']); ?></span></p>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800 mb-2">Service Information</h3>
                    <p class="text-sm text-gray-600">Status: <span class="font-semibold <?php echo ($order ? $order['laundry_status'] : $customOrder['laundry_status']) === 'ready' ? 'text-green-600' : 'text-blue-600'; ?>"><?php echo ucfirst(str_replace('_', ' ', $order ? $order['laundry_status'] : $customOrder['laundry_status'])); ?></span></p>
                    <?php if ($order): ?>
                    <p class="text-sm text-gray-600">Clothing Type: <span class="font-semibold"><?php echo ucfirst($order['clothing_type']); ?></span></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Additional Information -->
        <?php if ($order && $order['remarks']): ?>
        <div class="border-t border-gray-300 pt-4 mb-6">
            <h3 class="font-semibold text-gray-800 mb-2">Special Instructions</h3>
            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($order['remarks']); ?></p>
        </div>
        <?php endif; ?>

        <?php if ($customOrder && $customOrder['special_instructions']): ?>
        <div class="border-t border-gray-300 pt-4 mb-6">
            <h3 class="font-semibold text-gray-800 mb-2">Special Instructions</h3>
            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($customOrder['special_instructions']); ?></p>
        </div>
        <?php endif; ?>

        <?php if ($order && $order['estimated_completion']): ?>
        <div class="border-t border-gray-300 pt-4 mb-6">
            <h3 class="font-semibold text-gray-800 mb-2">Estimated Completion</h3>
            <p class="text-sm text-gray-600"><?php echo date('M d, Y h:i A', strtotime($order['estimated_completion'])); ?></p>
        </div>
        <?php endif; ?>

        <?php if ($customOrder && $customOrder['estimated_completion']): ?>
        <div class="border-t border-gray-300 pt-4 mb-6">
            <h3 class="font-semibold text-gray-800 mb-2">Estimated Completion</h3>
            <p class="text-sm text-gray-600"><?php echo date('M d, Y h:i A', strtotime($customOrder['estimated_completion'])); ?></p>
        </div>
        <?php endif; ?>

        <!-- Receipt Footer -->
        <div class="border-t border-gray-300 pt-4 text-center">
            <p class="text-sm text-gray-500 mb-2">Thank you for choosing LaundryPro!</p>
            <p class="text-xs text-gray-400">Keep this receipt for your records</p>
            <p class="text-xs text-gray-400 mt-2">
                Generated on <?php echo date('M d, Y h:i A'); ?>
                <?php if ($operatorName): ?>
                    by <?php echo htmlspecialchars($operatorName); ?>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <?php else: ?>
    <!-- No Receipt Selected -->
    <div class="text-center py-12">
        <i class="fas fa-receipt text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-600 mb-2">No Receipt Selected</h3>
        <p class="text-gray-500">Search for a receipt above to view its details</p>
    </div>
    <?php endif; ?>
</div>

<!-- Receipt Search Modal -->
<div id="searchModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-96 overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Search Receipts</h3>
                    <button type="button" id="closeSearchModal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div id="searchResults" class="space-y-2">
                    <!-- Search results will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    document.getElementById('receiptSearch').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchReceipt();
        }
    });

    // Modal functions
    function openSearchModal() {
        document.getElementById('searchModal').classList.remove('hidden');
    }

    function closeSearchModal() {
        document.getElementById('searchModal').classList.add('hidden');
    }

    // Event listeners
    document.getElementById('closeSearchModal').addEventListener('click', closeSearchModal);
    document.getElementById('searchModal').addEventListener('click', function(e) {
        if (e.target === this) closeSearchModal();
    });

    // Search receipt function
    window.searchReceipt = async function() {
        const searchTerm = document.getElementById('receiptSearch').value.trim();
        if (!searchTerm) {
            showNotification('Please enter a search term', 'error');
            return;
        }

        try {
            // Search both regular and custom orders
            const [regularResponse, customResponse] = await Promise.all([
                fetch(`../../api/orders.php?search=${encodeURIComponent(searchTerm)}`),
                fetch(`../../api/custom_orders.php?search=${encodeURIComponent(searchTerm)}`)
            ]);
            
            const regularData = await regularResponse.json();
            const customData = await customResponse.json();
            
            const allResults = [
                ...(regularData.success ? regularData.data.map(order => ({...order, type: 'regular'})) : []),
                ...(customData.success ? customData.data.map(order => ({...order, type: 'custom'})) : [])
            ];
            
            if (allResults.length > 0) {
                displaySearchResults(allResults);
                openSearchModal();
            } else {
                showNotification('No receipts found', 'info');
            }
        } catch (error) {
            console.error('Error searching receipts:', error);
            showNotification('Error searching receipts', 'error');
        }
    };

    // Display search results
    function displaySearchResults(results) {
        const resultsContainer = document.getElementById('searchResults');
        resultsContainer.innerHTML = '';

        results.forEach(result => {
            const resultDiv = document.createElement('div');
            resultDiv.className = 'p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-blue-50 hover:border-blue-300 transition-colors';
            resultDiv.innerHTML = `
                <div class="flex justify-between items-start">
                    <div>
                        <h4 class="font-semibold text-gray-800">${result.type === 'regular' ? 'Order' : 'Custom Order'} #${String(result.payment_id || result.order_id).padStart(6, '0')}</h4>
                        <p class="text-sm text-gray-600">Customer: ${result.customer_name}</p>
                        <p class="text-sm text-gray-600">Date: ${new Date(result.created_at).toLocaleDateString()}</p>
                        <p class="text-sm text-gray-500">Type: ${result.type === 'regular' ? 'Regular Order' : 'Custom Order'}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-blue-600">₱${parseFloat(result.total_price || result.subtotal).toFixed(2)}</p>
                        <p class="text-xs text-gray-500">${result.payment_status}</p>
                    </div>
                </div>
            `;
            resultDiv.addEventListener('click', () => {
                window.location.href = `receipt-viewer.php?id=${result.payment_id || result.order_id}`;
            });
            resultsContainer.appendChild(resultDiv);
        });
    };

    // Print receipt
    window.printReceipt = function() {
        const receiptContent = document.getElementById('receiptContent');
        if (!receiptContent) {
            showNotification('No receipt to print', 'error');
            return;
        }

        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Receipt - LaundryPro</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
                        .receipt { max-width: 600px; margin: 0 auto; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f5f5f5; }
                        .text-center { text-align: center; }
                        .text-right { text-align: right; }
                        .font-bold { font-weight: bold; }
                        .font-semibold { font-weight: 600; }
                        .text-gray-800 { color: #1f2937; }
                        .text-gray-600 { color: #4b5563; }
                        .text-gray-500 { color: #6b7280; }
                        .text-gray-400 { color: #9ca3af; }
                        .text-blue-600 { color: #2563eb; }
                        .text-green-600 { color: #16a34a; }
                        .text-orange-600 { color: #ea580c; }
                        .border-t { border-top: 1px solid #d1d5db; }
                        .border-b { border-bottom: 1px solid #d1d5db; }
                        .border-gray-300 { border-color: #d1d5db; }
                        .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
                        .px-3 { padding-left: 0.75rem; padding-right: 0.75rem; }
                        .mb-2 { margin-bottom: 0.5rem; }
                        .mb-3 { margin-bottom: 0.75rem; }
                        .mb-4 { margin-bottom: 1rem; }
                        .mb-6 { margin-bottom: 1.5rem; }
                        .mb-8 { margin-bottom: 2rem; }
                        .pt-2 { padding-top: 0.5rem; }
                        .pt-4 { padding-top: 1rem; }
                        .space-y-2 > * + * { margin-top: 0.5rem; }
                        .grid { display: grid; }
                        .grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                        .gap-4 { gap: 1rem; }
                        .overflow-x-auto { overflow-x: auto; }
                        .w-full { width: 100%; }
                        .max-w-2xl { max-width: 42rem; }
                        .mx-auto { margin-left: auto; margin-right: auto; }
                        .text-3xl { font-size: 1.875rem; line-height: 2.25rem; }
                        .text-xl { font-size: 1.25rem; line-height: 1.75rem; }
                        .text-lg { font-size: 1.125rem; line-height: 1.75rem; }
                        .text-sm { font-size: 0.875rem; line-height: 1.25rem; }
                        .text-xs { font-size: 0.75rem; line-height: 1rem; }
                    </style>
                </head>
                <body>
                    <div class="receipt">
                        ${receiptContent.innerHTML}
                    </div>
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    };

    // Share receipt
    window.shareReceipt = function() {
        if (navigator.share) {
            navigator.share({
                title: 'LaundryPro Receipt',
                text: 'Receipt for your laundry order',
                url: window.location.href
            });
        } else {
            // Fallback: copy to clipboard
            navigator.clipboard.writeText(window.location.href).then(() => {
                showNotification('Receipt link copied to clipboard!', 'success');
            });
        }
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

    // Auto print when ?print=1
    const params = new URLSearchParams(window.location.search);
    if (params.get('print') === '1') {
        // allow render, then trigger print
        setTimeout(() => {
            if (typeof window.printReceipt === 'function') {
                window.printReceipt();
            } else {
                window.print();
            }
        }, 300);
    }
});
</script>

<?php
$content = ob_get_clean();
include '../layout.php';
?>
