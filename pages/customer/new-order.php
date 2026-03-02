<?php
require_once '../../config.php';
if (!auth()) redirect('login.php');

$pageTitle = 'New Order';
ob_start();
?>

<div class="bg-white rounded-lg shadow-sm p-6">
    <h2 class="text-xl font-bold text-gray-800 mb-6">Create New Order</h2>
    
    <!-- Order Form -->
    <form id="orderForm" class="space-y-6">
        <!-- Customer Information -->
        <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Customer Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer Name</label>
                    <input type="text" id="customerName" name="customer_name" 
                           value="<?php echo htmlspecialchars($_SESSION['name']); ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <input type="tel" id="customerPhone" name="customer_phone" 
                           value="<?php echo htmlspecialchars($_SESSION['phone_number'] ?? ''); ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Detergent and Fabcon Selection -->
        <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Select Detergent & Fabcon</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Detergent Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-spray-can text-blue-500 mr-1"></i>Detergent (Optional)
                    </label>
                    <select id="detergentSelect" name="detergent_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Detergent (Optional)</option>
                        <!-- Detergents will be loaded here -->
                    </select>
                    <div id="detergentInfo" class="mt-2 text-sm text-gray-600 hidden">
                        <div class="flex justify-between">
                            <span>Price:</span>
                            <span id="detergentPrice">₱0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Stock:</span>
                            <span id="detergentStock">0</span>
                        </div>
                    </div>
                </div>

                <!-- Fabcon Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-tint text-purple-500 mr-1"></i>Fabcon (Optional)
                    </label>
                    <select id="fabconSelect" name="fabcon_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Fabcon (Optional)</option>
                        <!-- Fabcons will be loaded here -->
                    </select>
                    <div id="fabconInfo" class="mt-2 text-sm text-gray-600 hidden">
                        <div class="flex justify-between">
                            <span>Price:</span>
                            <span id="fabconPrice">₱0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Stock:</span>
                            <span id="fabconStock">0</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quantity Selection -->
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-sort-numeric-up text-green-500 mr-1"></i>Quantity
                </label>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <button type="button" id="decreaseQty" class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center hover:bg-gray-300">
                            <i class="fas fa-minus text-sm"></i>
                        </button>
                        <input type="number" id="quantity" name="quantity" min="1" value="1" 
                               class="w-16 px-2 py-1 border border-gray-300 rounded text-center">
                        <button type="button" id="increaseQty" class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center hover:bg-gray-300">
                            <i class="fas fa-plus text-sm"></i>
                        </button>
                    </div>
                    <span class="text-sm text-gray-600">items</span>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="bg-gray-50 p-4 rounded-lg">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-gray-700">Order Items</h3>
                <button type="button" id="addServiceBtn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <i class="fas fa-plus mr-2"></i>Add Service
                </button>
            </div>
            <div id="orderItems" class="space-y-3">
                <!-- Order items will be added here -->
            </div>
            <div id="noItemsMessage" class="text-center py-8 text-gray-500">
                <i class="fas fa-shopping-cart text-4xl mb-2"></i>
                <p>No items added yet. Click "Add Service" to add items to your order.</p>
            </div>
        </div>

        <!-- Laundry Weight -->
        <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="text-lg font-semibold text-gray-700 mb-3 flex items-center justify-between">
                <span>Laundry Weight (kg)</span>
                <span class="text-xs text-gray-500 font-medium">Rates: Clothes ₱80/kg · Pants ₱70/kg</span>
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="clothesWeight" class="block text-sm font-medium text-gray-700 mb-1">Clothes</label>
                    <div class="relative rounded-md shadow-sm">
                        <input type="number" id="clothesWeight" name="clothes_weight" min="0" step="0.1"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="0.0">
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 text-sm">kg</span>
                    </div>
                </div>
                <div>
                    <label for="pantsWeight" class="block text-sm font-medium text-gray-700 mb-1">Pants</label>
                    <div class="relative rounded-md shadow-sm">
                        <input type="number" id="pantsWeight" name="pants_weight" min="0" step="0.1"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="0.0">
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 text-sm">kg</span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Total Weight</label>
                    <div class="bg-white px-3 py-2 border border-gray-300 rounded-md flex items-center h-full">
                        <span id="totalWeightDisplay" class="text-lg font-semibold text-gray-800">0.0 kg</span>
                    </div>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">Enter the combined weight of clothing items. Leave blank if weight is unknown.</p>
        </div>

        <!-- Order Summary -->
        <div class="bg-blue-50 p-4 rounded-lg">
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Order Summary</h3>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600">Total Weight:</span>
                    <span id="totalWeightSummary" class="font-semibold">0.0 kg</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Clothes Weight Charge:</span>
                    <span id="clothesWeightCharge" class="font-semibold">₱0.00</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Pants Weight Charge:</span>
                    <span id="pantsWeightCharge" class="font-semibold">₱0.00</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Total Weight Charge:</span>
                    <span id="totalWeightCharge" class="font-semibold">₱0.00</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Service Subtotal:</span>
                    <span id="serviceSubtotal" class="font-semibold">₱0.00</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Detergent Cost:</span>
                    <span id="detergentCost" class="font-semibold">₱0.00</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Fabcon Cost:</span>
                    <span id="fabconCost" class="font-semibold">₱0.00</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Subtotal:</span>
                    <span id="subtotal" class="font-semibold">₱0.00</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Discount:</span>
                    <span id="discount" class="font-semibold">₱0.00</span>
                </div>
                <hr class="my-2">
                <div class="flex justify-between text-lg">
                    <span class="font-bold text-gray-800">Total:</span>
                    <span id="total" class="font-bold text-blue-600">₱0.00</span>
                </div>
            </div>
        </div>

        <!-- Additional Information -->
        <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Additional Information</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Clothing Type</label>
                    <select name="clothing_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="regular">Regular</option>
                        <option value="delicate">Delicate</option>
                        <option value="heavy">Heavy Duty</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Special Instructions</label>
                    <textarea name="remarks" rows="3" placeholder="Any special instructions for your order..." 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Completion</label>
                    <input type="datetime-local" name="estimated_completion" 
                           min="<?php echo date('Y-m-d\TH:i'); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Select date and time">
                    <p class="text-xs text-gray-500 mt-1">Leave empty for automatic calculation based on service duration</p>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end space-x-4">
            <button type="button" id="clearFormBtn" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500">
                Clear Form
            </button>
            <button type="submit" id="submitOrderBtn" class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-check mr-2"></i>Submit Order
            </button>
        </div>
    </form>
</div>

<!-- Service Selection Modal -->
<div id="serviceModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-96 overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Select Service</h3>
                    <button type="button" id="closeServiceModal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div id="serviceList" class="space-y-2">
                    <!-- Services will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let services = [];
    let orderItems = [];
    let serviceCounter = 0;
    let detergents = [];
    let fabcons = [];
    let selectedDetergent = null;
    let selectedFabcon = null;
    let quantity = 1;
    let clothesWeight = 0;
    let pantsWeight = 0;

    const CLOTHES_RATE = 80;
    const PANTS_RATE = 70;

    const clothesWeightInput = document.getElementById('clothesWeight');
    const pantsWeightInput = document.getElementById('pantsWeight');
    const totalWeightDisplay = document.getElementById('totalWeightDisplay');
    const totalWeightSummary = document.getElementById('totalWeightSummary');
    const clothesWeightChargeEl = document.getElementById('clothesWeightCharge');
    const pantsWeightChargeEl = document.getElementById('pantsWeightCharge');
    const totalWeightChargeEl = document.getElementById('totalWeightCharge');

    function calculateWeightCharges() {
        clothesWeight = Math.max(0, parseFloat(clothesWeightInput.value) || 0);
        pantsWeight = Math.max(0, parseFloat(pantsWeightInput.value) || 0);

        const clothesCharge = clothesWeight * CLOTHES_RATE;
        const pantsCharge = pantsWeight * PANTS_RATE;
        const totalWeight = clothesWeight + pantsWeight;
        const totalCharge = clothesCharge + pantsCharge;

        return {
            clothesWeight,
            pantsWeight,
            totalWeight,
            clothesCharge,
            pantsCharge,
            totalCharge
        };
    }

    function updateWeightDisplay() {
        const {
            clothesWeight: clothes,
            pantsWeight: pants,
            totalWeight,
            clothesCharge,
            pantsCharge,
            totalCharge
        } = calculateWeightCharges();

        totalWeightDisplay.textContent = `${totalWeight.toFixed(1)} kg`;
        totalWeightSummary.textContent = `${totalWeight.toFixed(1)} kg`;
        clothesWeightChargeEl.textContent = `₱${clothesCharge.toFixed(2)}`;
        pantsWeightChargeEl.textContent = `₱${pantsCharge.toFixed(2)}`;
        totalWeightChargeEl.textContent = `₱${totalCharge.toFixed(2)}`;

        return {
            clothes,
            pants,
            totalWeight,
            clothesCharge,
            pantsCharge,
            totalCharge
        };
    }

    // Load services
    async function loadServices() {
        try {
            const response = await fetch('../../api/public_services.php');
            const data = await response.json();
            if (data.success) {
                services = data.data.services;
                populateServiceModal();
            }
        } catch (error) {
            console.error('Error loading services:', error);
            showNotification('Error loading services', 'error');
        }
    }

    // Load detergents
    async function loadDetergents() {
        try {
            console.log('Loading detergents...');
            const response = await fetch('../../api/public_inventory.php');
            console.log('Response status:', response.status);
            const data = await response.json();
            console.log('Response data:', data);
            if (data.success) {
                detergents = data.data.filter(item => item.item_type === 'detergent');
                console.log('Filtered detergents:', detergents);
                populateDetergentSelect();
            } else {
                console.error('Error loading detergents:', data.message);
                showNotification('Error loading detergents: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error loading detergents:', error);
            showNotification('Error loading detergents', 'error');
        }
    }

    // Load fabcons
    async function loadFabcons() {
        try {
            console.log('Loading fabcons...');
            const response = await fetch('../../api/public_inventory.php');
            console.log('Response status:', response.status);
            const data = await response.json();
            console.log('Response data:', data);
            if (data.success) {
                fabcons = data.data.filter(item => item.item_type === 'fabric_softener');
                console.log('Filtered fabcons:', fabcons);
                populateFabconSelect();
            } else {
                console.error('Error loading fabcons:', data.message);
                showNotification('Error loading fabcons: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error loading fabcons:', error);
            showNotification('Error loading fabcons', 'error');
        }
    }

    // Populate detergent select
    function populateDetergentSelect() {
        const select = document.getElementById('detergentSelect');
        select.innerHTML = '<option value="">Select Detergent</option>';
        
        detergents.forEach(detergent => {
            const option = document.createElement('option');
            option.value = detergent.inventory_id;
            option.textContent = `${detergent.item_name} - ₱${parseFloat(detergent.price).toFixed(2)}`;
            option.dataset.price = detergent.price;
            option.dataset.stock = detergent.quantity;
            select.appendChild(option);
        });
    }

    // Populate fabcon select
    function populateFabconSelect() {
        const select = document.getElementById('fabconSelect');
        select.innerHTML = '<option value="">Select Fabcon</option>';
        
        fabcons.forEach(fabcon => {
            const option = document.createElement('option');
            option.value = fabcon.inventory_id;
            option.textContent = `${fabcon.item_name} - ₱${parseFloat(fabcon.price).toFixed(2)}`;
            option.dataset.price = fabcon.price;
            option.dataset.stock = fabcon.quantity;
            select.appendChild(option);
        });
    }

    // Populate service modal
    function populateServiceModal() {
        const serviceList = document.getElementById('serviceList');
        serviceList.innerHTML = '';

        services.forEach(service => {
            const serviceDiv = document.createElement('div');
            serviceDiv.className = 'p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-blue-50 hover:border-blue-300 transition-colors';
            serviceDiv.innerHTML = `
                <div class="flex justify-between items-start">
                    <div>
                        <h4 class="font-semibold text-gray-800">${service.service_name}</h4>
                        <p class="text-sm text-gray-600">${service.description || 'No description'}</p>
                        <p class="text-xs text-gray-500 mt-1">Type: ${service.service_type.replace('_', ' ')}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-blue-600">₱${parseFloat(service.base_price).toFixed(2)}</p>
                        <p class="text-xs text-gray-500">${service.estimated_duration || 'N/A'} hrs</p>
                    </div>
                </div>
            `;
            serviceDiv.addEventListener('click', () => selectService(service));
            serviceList.appendChild(serviceDiv);
        });
    }

    // Select service
    function selectService(service) {
        const itemId = 'item_' + serviceCounter++;
        const orderItem = {
            id: itemId,
            service_id: service.service_id,
            service_name: service.service_name,
            unit_price: parseFloat(service.base_price),
            quantity: 1,
            total_price: parseFloat(service.base_price),
            special_instructions: ''
        };

        orderItems.push(orderItem);
        addOrderItemToUI(orderItem);
        closeServiceModal();
        updateOrderSummary();
    }

    // Add order item to UI
    function addOrderItemToUI(item) {
        const orderItemsContainer = document.getElementById('orderItems');
        const noItemsMessage = document.getElementById('noItemsMessage');
        
        noItemsMessage.style.display = 'none';

        const itemDiv = document.createElement('div');
        itemDiv.id = item.id;
        itemDiv.className = 'bg-white p-4 border border-gray-200 rounded-lg';
        itemDiv.innerHTML = `
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-800">${item.service_name}</h4>
                    <p class="text-sm text-gray-600">₱${item.unit_price.toFixed(2)} per item</p>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="flex items-center space-x-2">
                        <button type="button" onclick="updateQuantity('${item.id}', -1)" class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center hover:bg-gray-300">
                            <i class="fas fa-minus text-sm"></i>
                        </button>
                        <span id="qty_${item.id}" class="w-8 text-center font-semibold">${item.quantity}</span>
                        <button type="button" onclick="updateQuantity('${item.id}', 1)" class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center hover:bg-gray-300">
                            <i class="fas fa-plus text-sm"></i>
                        </button>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-blue-600">₱${item.total_price.toFixed(2)}</p>
                    </div>
                    <button type="button" onclick="removeOrderItem('${item.id}')" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        orderItemsContainer.appendChild(itemDiv);
    }

    // Update quantity
    window.updateQuantity = function(itemId, change) {
        const item = orderItems.find(i => i.id === itemId);
        if (item) {
            item.quantity = Math.max(1, item.quantity + change);
            item.total_price = item.quantity * item.unit_price;
            
            document.getElementById('qty_' + itemId).textContent = item.quantity;
            document.querySelector(`#${itemId} .text-blue-600`).textContent = `₱${item.total_price.toFixed(2)}`;
            updateOrderSummary();
        }
    };

    // Remove order item
    window.removeOrderItem = function(itemId) {
        orderItems = orderItems.filter(item => item.id !== itemId);
        document.getElementById(itemId).remove();
        
        if (orderItems.length === 0) {
            document.getElementById('noItemsMessage').style.display = 'block';
        }
        updateOrderSummary();
    };

    // Update order summary
    function updateOrderSummary() {
        const serviceSubtotal = orderItems.reduce((sum, item) => sum + item.total_price, 0);
        const detergentCost = selectedDetergent ? parseFloat(selectedDetergent.price) * (quantity || 1) : 0;
        const fabconCost = selectedFabcon ? parseFloat(selectedFabcon.price) * (quantity || 1) : 0;
        const weightInfo = updateWeightDisplay();
        const subtotal = serviceSubtotal + detergentCost + fabconCost + weightInfo.totalCharge;
        const discount = 0; // Can be implemented later
        const total = subtotal - discount;

        document.getElementById('serviceSubtotal').textContent = `₱${serviceSubtotal.toFixed(2)}`;
        document.getElementById('detergentCost').textContent = `₱${detergentCost.toFixed(2)}`;
        document.getElementById('fabconCost').textContent = `₱${fabconCost.toFixed(2)}`;
        document.getElementById('subtotal').textContent = `₱${subtotal.toFixed(2)}`;
        document.getElementById('discount').textContent = `₱${discount.toFixed(2)}`;
        document.getElementById('total').textContent = `₱${total.toFixed(2)}`;

        // Enable/disable submit button
        const submitBtn = document.getElementById('submitOrderBtn');
        submitBtn.disabled = orderItems.length === 0;

        // Auto-update estimated completion if field is empty
        const estimatedField = document.querySelector('input[name="estimated_completion"]');
        if (!estimatedField.value && orderItems.length > 0) {
            const autoCompletion = calculateEstimatedCompletion();
            if (autoCompletion) {
                estimatedField.value = autoCompletion;
            }
        }
    }

    // Modal functions
    function openServiceModal() {
        document.getElementById('serviceModal').classList.remove('hidden');
    }

    function closeServiceModal() {
        document.getElementById('serviceModal').classList.add('hidden');
    }

    // Event listeners
    document.getElementById('addServiceBtn').addEventListener('click', openServiceModal);
    document.getElementById('closeServiceModal').addEventListener('click', closeServiceModal);
    document.getElementById('serviceModal').addEventListener('click', function(e) {
        if (e.target === this) closeServiceModal();
    });

    // Detergent selection
    document.getElementById('detergentSelect').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            selectedDetergent = {
                id: selectedOption.value,
                name: selectedOption.textContent.split(' - ')[0],
                price: selectedOption.dataset.price,
                stock: selectedOption.dataset.stock
            };
            document.getElementById('detergentPrice').textContent = `₱${parseFloat(selectedDetergent.price).toFixed(2)}`;
            document.getElementById('detergentStock').textContent = selectedDetergent.stock;
            document.getElementById('detergentInfo').classList.remove('hidden');
        } else {
            selectedDetergent = null;
            document.getElementById('detergentInfo').classList.add('hidden');
        }
        updateOrderSummary();
    });

    // Fabcon selection
    document.getElementById('fabconSelect').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            selectedFabcon = {
                id: selectedOption.value,
                name: selectedOption.textContent.split(' - ')[0],
                price: selectedOption.dataset.price,
                stock: selectedOption.dataset.stock
            };
            document.getElementById('fabconPrice').textContent = `₱${parseFloat(selectedFabcon.price).toFixed(2)}`;
            document.getElementById('fabconStock').textContent = selectedFabcon.stock;
            document.getElementById('fabconInfo').classList.remove('hidden');
        } else {
            selectedFabcon = null;
            document.getElementById('fabconInfo').classList.add('hidden');
        }
        updateOrderSummary();
    });

    // Quantity controls
    document.getElementById('decreaseQty').addEventListener('click', function() {
        if (quantity > 1) {
            quantity--;
            document.getElementById('quantity').value = quantity;
            updateOrderSummary();
        }
    });

    document.getElementById('increaseQty').addEventListener('click', function() {
        quantity++;
        document.getElementById('quantity').value = quantity;
        updateOrderSummary();
    });

    document.getElementById('quantity').addEventListener('change', function() {
        quantity = Math.max(1, parseInt(this.value) || 1);
        this.value = quantity;
        updateOrderSummary();
    });

    clothesWeightInput.addEventListener('input', updateOrderSummary);
    pantsWeightInput.addEventListener('input', updateOrderSummary);

    // Calculate estimated completion based on services
    function calculateEstimatedCompletion() {
        if (orderItems.length === 0) return null;
        
        // Find the service with the longest duration
        let maxDuration = 0;
        orderItems.forEach(item => {
            const service = services.find(s => s.service_id === item.service_id);
            if (service && service.estimated_duration) {
                maxDuration = Math.max(maxDuration, service.estimated_duration);
            }
        });
        
        if (maxDuration > 0) {
            const now = new Date();
            const completionTime = new Date(now.getTime() + (maxDuration * 60 * 60 * 1000)); // Convert hours to milliseconds
            return completionTime.toISOString().slice(0, 16); // Format for datetime-local input
        }
        
        return null;
    }

    // Form submission
    document.getElementById('orderForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (orderItems.length === 0) {
            showNotification('Please add at least one service to your order', 'error');
            return;
        }

        const submitBtn = document.getElementById('submitOrderBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Submitting...';

        try {
            const formData = new FormData(this);
            let estimatedCompletion = formData.get('estimated_completion');
            
            // If no estimated completion is provided, calculate it automatically
            if (!estimatedCompletion) {
                estimatedCompletion = calculateEstimatedCompletion();
            }

            const serviceSubtotal = orderItems.reduce((sum, item) => sum + item.total_price, 0);
            const detergentCost = selectedDetergent ? parseFloat(selectedDetergent.price) * quantity : 0;
            const fabconCost = selectedFabcon ? parseFloat(selectedFabcon.price) * quantity : 0;

            const clothesWeightValue = Math.max(0, parseFloat(formData.get('clothes_weight')) || 0);
            const pantsWeightValue = Math.max(0, parseFloat(formData.get('pants_weight')) || 0);
            const totalWeightValue = parseFloat((clothesWeightValue + pantsWeightValue).toFixed(2));
            const clothesWeightChargeValue = parseFloat((clothesWeightValue * CLOTHES_RATE).toFixed(2));
            const pantsWeightChargeValue = parseFloat((pantsWeightValue * PANTS_RATE).toFixed(2));
            const totalWeightChargeValue = parseFloat((clothesWeightChargeValue + pantsWeightChargeValue).toFixed(2));

            const totalPrice = serviceSubtotal + detergentCost + fabconCost + totalWeightChargeValue;

            const baseRemarks = formData.get('remarks') || '';
            const weightNote = totalWeightValue > 0
                ? `Weight - Clothes: ${clothesWeightValue.toFixed(1)}kg (₱${clothesWeightChargeValue.toFixed(2)}), Pants: ${pantsWeightValue.toFixed(1)}kg (₱${pantsWeightChargeValue.toFixed(2)}), Total: ${totalWeightValue.toFixed(1)}kg (₱${totalWeightChargeValue.toFixed(2)})`
                : '';
            const combinedRemarks = weightNote
                ? (baseRemarks ? `${baseRemarks}\n${weightNote}` : weightNote)
                : baseRemarks;

            const orderData = {
                user_id: <?php echo $_SESSION['user_id']; ?>,
                basket_count: orderItems.length,
                detergent_id: selectedDetergent ? selectedDetergent.id : null,
                fabcon_id: selectedFabcon ? selectedFabcon.id : null,
                detergent_qty: selectedDetergent ? quantity : 0,
                softener_qty: selectedFabcon ? quantity : 0,
                subtotal: totalPrice,
                discount_amount: 0,
                total_price: totalPrice,
                payment_method_id: 1, // Default to cash
                payment_status: 'pending',
                laundry_status: 'pending',
                customer_number: formData.get('customer_phone') || '',
                account_name: formData.get('customer_name') || '',
                remarks: combinedRemarks,
                estimated_completion: estimatedCompletion || null,
                clothing_type: formData.get('clothing_type') || 'regular',
                weight_summary: {
                    clothes: clothesWeightValue,
                    pants: pantsWeightValue,
                    total: totalWeightValue
                },
                weight_pricing: {
                    clothes_rate: CLOTHES_RATE,
                    pants_rate: PANTS_RATE,
                    clothes_charge: clothesWeightChargeValue,
                    pants_charge: pantsWeightChargeValue,
                    total_charge: totalWeightChargeValue
                },
                items: orderItems.map(item => ({
                    service_id: item.service_id,
                    item_name: item.service_name,
                    quantity: item.quantity,
                    unit_price: item.unit_price,
                    total_price: item.total_price,
                    special_instructions: item.special_instructions || '',
                    status: 'pending'
                }))
            };

            const response = await fetch('../../api/orders.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(orderData)
            });

            const result = await response.json();

            if (result.success) {
                showNotification('Order created successfully!', 'success');
                // Redirect to orders page after 1.5 seconds
                setTimeout(() => {
                    window.location.href = 'my-orders.php';
                }, 1500);
            } else {
                showNotification(result.message || 'Error creating order. Please try again.', 'error');
                console.error('Order submission error:', result);
            }
        } catch (error) {
            console.error('Error submitting order:', error);
            showNotification('Error submitting order: ' + error.message, 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check mr-2"></i>Submit Order';
        }
    });

    // Clear form
    document.getElementById('clearFormBtn').addEventListener('click', function() {
        if (confirm('Are you sure you want to clear the form? This will remove all items.')) {
            orderItems = [];
            document.getElementById('orderItems').innerHTML = '';
            document.getElementById('noItemsMessage').style.display = 'block';
            document.getElementById('orderForm').reset();
            updateOrderSummary();
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

    // Initialize
    loadServices();
    loadDetergents();
    loadFabcons();
    updateOrderSummary();
});
</script>

<?php
$content = ob_get_clean();
include '../../layout.php';
?>

