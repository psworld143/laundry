<?php
require_once '../../config.php';
if (!auth()) redirect('login.php');

// Block access to inventory features for all users
redirect('pages/customer/dashboard.php');

$pageTitle = 'My Fabric Conditioner Inventory';
ob_start();
?>

<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">My Fabric Conditioner Inventory</h2>
        <button onclick="openAddFabricModal()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <i class="fas fa-plus mr-2"></i>Add Fabric Conditioner Item
        </button>
    </div>

    <!-- Fabric Conditioner Items Grid -->
    <div id="fabricGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Fabric conditioner items will be loaded here -->
    </div>

    <!-- Empty State -->
    <div id="emptyState" class="text-center py-12 hidden">
        <i class="fas fa-tint text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-600 mb-2">No Fabric Conditioner Items Yet</h3>
        <p class="text-gray-500 mb-4">Add your fabric conditioner items to create custom laundry orders</p>
        <button onclick="openAddFabricModal()" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <i class="fas fa-plus mr-2"></i>Add Your First Item
        </button>
    </div>
</div>

<!-- Add/Edit Fabric Modal -->
<div id="fabricModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-96 overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 id="modalTitle" class="text-lg font-semibold text-gray-800">Add Fabric Conditioner Item</h3>
                    <button type="button" id="closeFabricModal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form id="fabricForm" class="space-y-4">
                    <input type="hidden" id="fabricId" name="fabric_id">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fabric Conditioner Name *</label>
                        <input type="text" id="fabricName" name="fabric_name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="e.g., Downy Original, Comfort Blue">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fabric Conditioner Type *</label>
                        <select id="fabricType" name="fabric_type" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select conditioner type</option>
                            <option value="liquid">Liquid Conditioner</option>
                            <option value="sheets">Dryer Sheets</option>
                            <option value="powder">Powder Conditioner</option>
                            <option value="concentrate">Concentrated</option>
                            <option value="organic">Organic</option>
                            <option value="sensitive">Sensitive Skin</option>
                            <option value="fragrance_free">Fragrance-Free</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fragrance/Color</label>
                        <input type="text" id="color" name="color"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="e.g., Blue, Fresh Scent, Lavender">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                        <input type="number" id="quantity" name="quantity" min="1" value="1"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Condition</label>
                        <select id="conditionStatus" name="condition_status"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="new">New/Unopened</option>
                            <option value="good" selected>Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor/Expired</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Special Instructions</label>
                        <textarea id="specialInstructions" name="special_instructions" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Any special care instructions..."></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeFabricModal()" 
                                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit" id="saveFabricBtn"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <i class="fas fa-save mr-2"></i>Save Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Create Order Modal -->
<div id="orderModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-96 overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Create Custom Order</h3>
                    <button type="button" id="closeOrderModal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form id="customOrderForm" class="space-y-4">
                    <input type="hidden" id="selectedFabricId" name="fabric_id">
                    
                    <!-- Selected Fabric Display -->
                    <div id="selectedFabricDisplay" class="bg-gray-50 p-4 rounded-lg">
                        <!-- Selected fabric info will be displayed here -->
                    </div>
                    
                    <!-- Soap Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Choose Your Soap/Detergent</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-blue-50">
                                <input type="radio" name="soap_type" value="tide" class="mr-3">
                                <div>
                                    <div class="font-semibold">Tide</div>
                                    <div class="text-sm text-gray-600">Regular detergent</div>
                                </div>
                            </label>
                            <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-blue-50">
                                <input type="radio" name="soap_type" value="downy" class="mr-3">
                                <div>
                                    <div class="font-semibold">Downy</div>
                                    <div class="text-sm text-gray-600">Fabric softener</div>
                                </div>
                            </label>
                            <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-blue-50">
                                <input type="radio" name="soap_type" value="clorox" class="mr-3">
                                <div>
                                    <div class="font-semibold">Clorox</div>
                                    <div class="text-sm text-gray-600">Bleach</div>
                                </div>
                            </label>
                            <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-blue-50">
                                <input type="radio" name="soap_type" value="oxiclean" class="mr-3">
                                <div>
                                    <div class="font-semibold">OxiClean</div>
                                    <div class="text-sm text-gray-600">Stain remover</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Service Type Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Service Type</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-green-50">
                                <input type="radio" name="service_type" value="wash" class="mr-3">
                                <div>
                                    <div class="font-semibold text-green-600">Wash & Fold</div>
                                    <div class="text-sm text-gray-600">Regular washing service</div>
                                    <div class="text-sm font-semibold text-green-600">₱50.00</div>
                                </div>
                            </label>
                            <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-purple-50">
                                <input type="radio" name="service_type" value="dry_clean" class="mr-3">
                                <div>
                                    <div class="font-semibold text-purple-600">Dry Clean</div>
                                    <div class="text-sm text-gray-600">Professional dry cleaning</div>
                                    <div class="text-sm font-semibold text-purple-600">₱100.00</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Additional Options -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Additional Options</label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="ironing" value="1" class="mr-3">
                                <span>Ironing Service (+₱30.00)</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="express" value="1" class="mr-3">
                                <span>Express Service (+₱30.00)</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Special Instructions -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Special Instructions</label>
                        <textarea name="special_instructions" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Any special instructions for this order..."></textarea>
                    </div>
                    
                    <!-- Order Summary -->
                    <div id="orderSummary" class="bg-blue-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-800 mb-2">Order Summary</h4>
                        <div class="space-y-1 text-sm">
                            <div class="flex justify-between">
                                <span>Base Service:</span>
                                <span id="basePrice">₱0.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Additional Services:</span>
                                <span id="additionalPrice">₱0.00</span>
                            </div>
                            <hr class="my-2">
                            <div class="flex justify-between font-semibold">
                                <span>Total:</span>
                                <span id="totalPrice" class="text-blue-600">₱0.00</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeOrderModal()" 
                                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit" id="createOrderBtn"
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                            <i class="fas fa-check mr-2"></i>Create Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let fabrics = [];
    let isEditing = false;

    // Load fabrics
    loadFabrics();

    // Modal functions
    function openAddFabricModal() {
        isEditing = false;
        document.getElementById('modalTitle').textContent = 'Add Fabric Conditioner Item';
        document.getElementById('fabricForm').reset();
        document.getElementById('fabricId').value = '';
        document.getElementById('fabricModal').classList.remove('hidden');
    }

    function closeFabricModal() {
        document.getElementById('fabricModal').classList.add('hidden');
    }

    function openOrderModal(fabricId) {
        const fabric = fabrics.find(f => f.fabric_id == fabricId);
        if (!fabric) return;

        document.getElementById('selectedFabricId').value = fabricId;
        document.getElementById('selectedFabricDisplay').innerHTML = `
            <h4 class="font-semibold text-gray-800">${fabric.fabric_name}</h4>
            <p class="text-sm text-gray-600">Type: ${fabric.fabric_type} | Color: ${fabric.color || 'N/A'}</p>
            <p class="text-sm text-gray-600">Condition: ${fabric.condition_status}</p>
            ${fabric.special_instructions ? `<p class="text-sm text-gray-600 mt-2"><strong>Instructions:</strong> ${fabric.special_instructions}</p>` : ''}
        `;
        
        document.getElementById('customOrderForm').reset();
        updateOrderSummary();
        document.getElementById('orderModal').classList.remove('hidden');
    }

    function closeOrderModal() {
        document.getElementById('orderModal').classList.add('hidden');
    }

    // Event listeners
    document.getElementById('closeFabricModal').addEventListener('click', closeFabricModal);
    document.getElementById('closeOrderModal').addEventListener('click', closeOrderModal);
    document.getElementById('fabricModal').addEventListener('click', function(e) {
        if (e.target === this) closeFabricModal();
    });
    document.getElementById('orderModal').addEventListener('click', function(e) {
        if (e.target === this) closeOrderModal();
    });

    // Load fabrics
    async function loadFabrics() {
        try {
            const response = await fetch('../../api/customer_inventory_fabric.php');
            const data = await response.json();
            
            if (data.success) {
                fabrics = data.data;
                displayFabrics();
            }
        } catch (error) {
            console.error('Error loading fabrics:', error);
            showNotification('Error loading fabric items', 'error');
        }
    }

    // Display fabrics
    function displayFabrics() {
        const container = document.getElementById('fabricGrid');
        const emptyState = document.getElementById('emptyState');
        
        if (fabrics.length === 0) {
            container.innerHTML = '';
            emptyState.classList.remove('hidden');
            return;
        }
        
        emptyState.classList.add('hidden');
        container.innerHTML = '';

        fabrics.forEach(fabric => {
            const fabricCard = document.createElement('div');
            fabricCard.className = 'bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow';
            fabricCard.innerHTML = `
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h3 class="font-semibold text-gray-800">${fabric.fabric_name}</h3>
                        <p class="text-sm text-gray-600">${fabric.fabric_type} • ${fabric.color || 'No color'}</p>
                    </div>
                    <div class="flex space-x-2">
                        <button onclick="editFabric(${fabric.fabric_id})" class="text-blue-500 hover:text-blue-700">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteFabric(${fabric.fabric_id})" class="text-red-500 hover:text-red-700">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                
                <div class="space-y-2 mb-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Quantity:</span>
                        <span class="font-semibold">${fabric.quantity} ${fabric.unit}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Condition:</span>
                        <span class="font-semibold capitalize">${fabric.condition_status}</span>
                    </div>
                    ${fabric.special_instructions ? `
                        <div class="text-sm">
                            <span class="text-gray-600">Instructions:</span>
                            <p class="text-gray-800 mt-1">${fabric.special_instructions}</p>
                        </div>
                    ` : ''}
                </div>
                
                <button onclick="openOrderModal(${fabric.fabric_id})" 
                        class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                    <i class="fas fa-shopping-cart mr-2"></i>Create Order
                </button>
            `;
            container.appendChild(fabricCard);
        });
    }

    // Edit fabric
    window.editFabric = function(fabricId) {
        const fabric = fabrics.find(f => f.fabric_id == fabricId);
        if (!fabric) return;

        isEditing = true;
        document.getElementById('modalTitle').textContent = 'Edit Fabric Conditioner Item';
        document.getElementById('fabricId').value = fabric.fabric_id;
        document.getElementById('fabricName').value = fabric.fabric_name;
        document.getElementById('fabricType').value = fabric.fabric_type;
        document.getElementById('color').value = fabric.color || '';
        document.getElementById('quantity').value = fabric.quantity;
        document.getElementById('conditionStatus').value = fabric.condition_status;
        document.getElementById('specialInstructions').value = fabric.special_instructions || '';
        
        document.getElementById('fabricModal').classList.remove('hidden');
    };

    // Delete fabric
    window.deleteFabric = function(fabricId) {
        if (!confirm('Are you sure you want to delete this fabric item?')) return;

        fetch('../../api/customer_inventory_fabric.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ fabric_id: fabricId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Fabric item deleted successfully', 'success');
                loadFabrics();
            } else {
                showNotification(data.message || 'Error deleting fabric item', 'error');
            }
        })
        .catch(error => {
            console.error('Error deleting fabric:', error);
            showNotification('Error deleting fabric item', 'error');
        });
    };

    // Save fabric
    document.getElementById('fabricForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const fabricData = {
            fabric_name: formData.get('fabric_name'),
            fabric_type: formData.get('fabric_type'),
            color: formData.get('color'),
            quantity: parseInt(formData.get('quantity')),
            condition_status: formData.get('condition_status'),
            special_instructions: formData.get('special_instructions')
        };

        if (isEditing) {
            fabricData.fabric_id = parseInt(formData.get('fabric_id'));
        }

        try {
            const response = await fetch('../../api/customer_inventory_fabric.php', {
                method: isEditing ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(fabricData)
            });

            const result = await response.json();

            if (result.success) {
                showNotification(isEditing ? 'Fabric item updated successfully' : 'Fabric item added successfully', 'success');
                closeFabricModal();
                loadFabrics();
            } else {
                showNotification(result.message || 'Error saving fabric item', 'error');
            }
        } catch (error) {
            console.error('Error saving fabric:', error);
            showNotification('Error saving fabric item', 'error');
        }
    });

    // Update order summary
    function updateOrderSummary() {
        const serviceType = document.querySelector('input[name="service_type"]:checked');
        const ironing = document.querySelector('input[name="ironing"]:checked');
        const express = document.querySelector('input[name="express"]:checked');

        let basePrice = 0;
        let additionalPrice = 0;

        if (serviceType) {
            basePrice = serviceType.value === 'wash' ? 50 : 100;
        }

        if (ironing) additionalPrice += 30;
        if (express) additionalPrice += 30;

        document.getElementById('basePrice').textContent = `₱${basePrice.toFixed(2)}`;
        document.getElementById('additionalPrice').textContent = `₱${additionalPrice.toFixed(2)}`;
        document.getElementById('totalPrice').textContent = `₱${(basePrice + additionalPrice).toFixed(2)}`;
    }

    // Event listeners for order form
    document.querySelectorAll('input[name="service_type"], input[name="ironing"], input[name="express"]').forEach(input => {
        input.addEventListener('change', updateOrderSummary);
    });

    // Create custom order
    document.getElementById('customOrderForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const serviceType = document.querySelector('input[name="service_type"]:checked');
        const soapType = document.querySelector('input[name="soap_type"]:checked');

        if (!serviceType) {
            showNotification('Please select a service type', 'error');
            return;
        }

        if (!soapType) {
            showNotification('Please select a soap/detergent', 'error');
            return;
        }

        const orderData = {
            user_id: <?php echo $_SESSION['user_id']; ?>,
            fabric_id: parseInt(formData.get('fabric_id')),
            service_type: serviceType.value,
            soap_type: soapType.value,
            ironing: formData.get('ironing') === '1',
            express: formData.get('express') === '1',
            special_instructions: formData.get('special_instructions'),
            subtotal: parseFloat(document.getElementById('totalPrice').textContent.replace('₱', '')),
            payment_method_id: 1,
            payment_status: 'pending',
            laundry_status: 'pending'
        };

        try {
            const response = await fetch('../../api/custom_orders.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(orderData)
            });

            const result = await response.json();

            if (result.success) {
                showNotification('Custom order created successfully!', 'success');
                closeOrderModal();
            } else {
                showNotification(result.message || 'Error creating order', 'error');
            }
        } catch (error) {
            console.error('Error creating order:', error);
            showNotification('Error creating order', 'error');
        }
    });

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
