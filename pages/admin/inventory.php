<?php
require_once '../../config.php';
if (!auth() || !in_array($_SESSION['position'], ['admin', 'manager'])) {
    // Redirect to appropriate dashboard based on user role
    if ($_SESSION['position'] === 'user') {
        redirect('pages/customer/dashboard.php');
    } else {
        redirect('login.php');
    }
}

// Get inventory statistics
$inventoryStats = [
    'total_items' => $db->query("SELECT COUNT(*) FROM inventory")->fetchColumn(),
    'low_stock' => $db->query("SELECT COUNT(*) FROM inventory WHERE quantity <= min_stock_level")->fetchColumn(),
    'out_of_stock' => $db->query("SELECT COUNT(*) FROM inventory WHERE quantity = 0")->fetchColumn(),
    'total_value' => $db->query("SELECT SUM(price * quantity) FROM inventory")->fetchColumn() ?? 0,
    'detergents' => $db->query("SELECT COUNT(*) FROM inventory WHERE item_type = 'detergent'")->fetchColumn(),
    'softeners' => $db->query("SELECT COUNT(*) FROM inventory WHERE item_type = 'fabric_softener'")->fetchColumn(),
];

$pageTitle = 'Inventory Management';
ob_start();
?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-boxes text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">Total</span>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= $inventoryStats['total_items'] ?></h3>
        <p class="text-blue-100 text-sm">Total Items</p>
    </div>

    <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-exclamation-triangle text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">Warning</span>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= $inventoryStats['low_stock'] ?></h3>
        <p class="text-yellow-100 text-sm">Low Stock Items</p>
    </div>

    <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-ban text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">Critical</span>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= $inventoryStats['out_of_stock'] ?></h3>
        <p class="text-red-100 text-sm">Out of Stock</p>
    </div>

    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-dollar-sign text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">Value</span>
        </div>
        <h3 class="text-4xl font-bold mb-1">₱<?= number_format($inventoryStats['total_value'], 2) ?></h3>
        <p class="text-green-100 text-sm">Total Inventory Value</p>
    </div>
</div>

<!-- Inventory Grid -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Supply Inventory</h2>
            <p class="text-gray-500 text-sm mt-1">Manage laundry supplies, detergents, and consumables</p>
        </div>
        <div class="flex gap-3">
            <button onclick="openInventoryModal()" class="bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white px-6 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transition transform hover:scale-105">
                <i class="fas fa-plus mr-2"></i>Add Item
            </button>
            <a href="<?= BASE_URL ?>pages/admin/fabrics.php" class="bg-gradient-to-r from-green-500 to-teal-600 hover:from-green-600 hover:to-teal-700 text-white px-6 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transition transform hover:scale-105">
                <i class="fas fa-tshirt mr-2"></i>Manage Fabrics
            </a>
        </div>
    </div>

    <div id="inventoryGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="text-center py-12 col-span-full">
            <i class="fas fa-spinner fa-spin text-4xl text-blue-500 mb-4"></i>
            <p class="text-gray-500">Loading inventory...</p>
        </div>
    </div>
</div>

<!-- Inventory Modal -->
<div id="inventoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-8 max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent" id="inventoryModalTitle">Add Inventory Item</h3>
            <button onclick="closeInventoryModal()" class="text-gray-400 hover:text-gray-600 transition">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>

        <form id="inventoryForm" onsubmit="saveInventory(event)">
            <input type="hidden" id="inventory_id">
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-tag text-blue-500 mr-1"></i>Item Name *
                    </label>
                    <input type="text" id="item_name" required
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                           placeholder="e.g., Tide Detergent">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-list text-purple-500 mr-1"></i>Item Type *
                    </label>
                    <select id="item_type" required
                            class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition">
                        <option value="">Select Type</option>
                        <option value="detergent">🧴 Detergent</option>
                        <option value="fabric_softener">💧 Fabric Softener</option>
                        <option value="bleach">⚗️ Bleach</option>
                        <option value="stain_remover">✨ Stain Remover</option>
                        <option value="other">📦 Other</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-industry text-green-500 mr-1"></i>Brand
                    </label>
                    <input type="text" id="brand"
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                           placeholder="e.g., Tide, Ariel">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-dollar-sign text-green-500 mr-1"></i>Price (₱) *
                    </label>
                    <input type="number" id="price" step="0.01" min="0" required
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                           placeholder="0.00">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-boxes text-yellow-500 mr-1"></i>Quantity *
                    </label>
                    <input type="number" id="quantity" min="0" required
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                           placeholder="0">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-exclamation-triangle text-orange-500 mr-1"></i>Min Stock Level
                    </label>
                    <input type="number" id="min_stock_level" min="1" value="10"
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                           placeholder="10">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-ruler text-indigo-500 mr-1"></i>Unit
                    </label>
                    <select id="unit"
                            class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition">
                        <option value="kg">Kilogram</option>
                        <option value="liter">Liter</option>
                        <option value="pack">Pack</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-4">
                <button type="button" onclick="closeInventoryModal()" 
                        class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 rounded-lg transition">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <button type="submit" 
                        class="flex-1 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-semibold py-3 rounded-lg transition shadow-lg">
                    <i class="fas fa-save mr-2"></i>Save Item
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let inventory = [];

// Load inventory from API
async function loadInventory() {
    loading(true);
    try {
        const res = await Ajax.get('<?= BASE_URL ?>api/inventory.php');
        console.log('Inventory response:', res);
        
        if (res.success) {
            inventory = res.data || [];
            console.log('Inventory loaded:', inventory.length);
            renderInventory();
        } else {
            showAlert('Failed to load inventory: ' + res.message, 'error');
            renderEmptyState();
        }
    } catch (error) {
        console.error('Error loading inventory:', error);
        showAlert('Error loading inventory. Check console for details.', 'error');
        renderEmptyState();
    } finally {
        loading(false);
    }
}

// Render empty state
function renderEmptyState() {
    document.getElementById('inventoryGrid').innerHTML = `
        <div class="col-span-full text-center py-16">
            <div class="bg-gray-100 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-boxes text-4xl text-gray-400"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">No Inventory Items Yet</h3>
            <p class="text-gray-500 mb-6">Add your first item to start managing your supply inventory</p>
            <button onclick="openInventoryModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold transition">
                <i class="fas fa-plus mr-2"></i>Add Item
            </button>
        </div>
    `;
}

// Render inventory grid
function renderInventory() {
    const types = {
        detergent: { label: 'Detergent', icon: 'fa-spray-can', emoji: '🧴', color: 'blue' },
        fabric_softener: { label: 'Fabric Softener', icon: 'fa-wind', emoji: '💧', color: 'purple' },
        bleach: { label: 'Bleach', icon: 'fa-flask', emoji: '⚗️', color: 'orange' },
        stain_remover: { label: 'Stain Remover', icon: 'fa-magic', emoji: '✨', color: 'pink' },
        other: { label: 'Other', icon: 'fa-box', emoji: '📦', color: 'gray' }
    };
    
    if (inventory.length === 0) {
        renderEmptyState();
        return;
    }
    
    const html = inventory.map(item => {
        const type = types[item.item_type] || { label: item.item_type, icon: 'fa-box', emoji: '📦', color: 'gray' };
        const stockPercentage = (item.quantity / item.min_stock_level) * 100;
        let stockStatus = 'green';
        let stockLabel = 'In Stock';
        
        if (item.quantity === 0) {
            stockStatus = 'red';
            stockLabel = 'Out of Stock';
        } else if (item.quantity <= item.min_stock_level) {
            stockStatus = 'yellow';
            stockLabel = 'Low Stock';
        }
        
        return `
        <div class="group bg-white border-2 border-gray-200 rounded-2xl p-6 hover:border-${type.color}-400 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <!-- Item Header -->
            <div class="flex justify-between items-start mb-4">
                <div class="flex items-center gap-3">
                    <div class="bg-gradient-to-br from-${type.color}-500 to-${type.color}-600 w-14 h-14 rounded-xl flex items-center justify-center text-3xl shadow-lg">
                        ${type.emoji}
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">${item.item_name}</h3>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-${type.color}-100 text-${type.color}-800">
                            <i class="fas ${type.icon} mr-1"></i>${type.label}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Brand -->
            ${item.brand ? `
            <div class="mb-3">
                <span class="text-sm text-gray-600">
                    <i class="fas fa-industry text-gray-400 mr-1"></i>${item.brand}
                </span>
            </div>
            ` : ''}

            <!-- Stock Info -->
            <div class="bg-gray-50 rounded-xl p-4 mb-4">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium text-gray-600">Stock Level</span>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-${stockStatus}-100 text-${stockStatus}-800">
                        <i class="fas fa-circle text-xs mr-1"></i>${stockLabel}
                    </span>
                </div>
                <div class="flex items-baseline gap-2 mb-2">
                    <span class="text-3xl font-bold text-gray-800">${item.quantity}</span>
                    <span class="text-sm text-gray-500">${item.unit}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-${stockStatus}-500 h-2 rounded-full transition-all" style="width: ${Math.min(stockPercentage, 100)}%"></div>
                </div>
                <p class="text-xs text-gray-500 mt-1">Min level: ${item.min_stock_level} ${item.unit}</p>
            </div>

            <!-- Price & Value -->
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div class="bg-green-50 rounded-lg p-3 text-center">
                    <p class="text-xs text-green-600 font-medium mb-1">Unit Price</p>
                    <p class="text-lg font-bold text-green-700">₱${parseFloat(item.price).toFixed(2)}</p>
                </div>
                <div class="bg-blue-50 rounded-lg p-3 text-center">
                    <p class="text-xs text-blue-600 font-medium mb-1">Total Value</p>
                    <p class="text-lg font-bold text-blue-700">₱${(parseFloat(item.price) * parseInt(item.quantity)).toFixed(2)}</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 pt-4 border-t border-gray-200">
                <button onclick="adjustStock(${item.inventory_id}, '${item.item_name}', ${item.quantity})" 
                        class="flex-1 bg-yellow-100 hover:bg-yellow-200 text-yellow-700 font-semibold py-2 rounded-lg transition"
                        title="Adjust Stock">
                    <i class="fas fa-plus-minus mr-1"></i>Stock
                </button>
                <button onclick="editInventory(${item.inventory_id})" 
                        class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 rounded-lg transition">
                    <i class="fas fa-edit mr-1"></i>Edit
                </button>
                <button onclick="deleteInventory(${item.inventory_id}, '${item.item_name}')" 
                        class="flex-1 bg-red-500 hover:bg-red-600 text-white font-semibold py-2 rounded-lg transition">
                    <i class="fas fa-trash mr-1"></i>Delete
                </button>
            </div>
        </div>
    `}).join('');
    
    document.getElementById('inventoryGrid').innerHTML = html;
}

// Open modal for new item
function openInventoryModal() {
    document.getElementById('inventoryForm').reset();
    document.getElementById('inventory_id').value = '';
    document.getElementById('inventoryModalTitle').textContent = 'Add Inventory Item';
    modal('inventoryModal', true);
}

// Close modal
function closeInventoryModal() {
    modal('inventoryModal', false);
}

// Edit inventory item
function editInventory(id) {
    const item = inventory.find(i => i.inventory_id == id);
    if (!item) {
        showAlert('Item not found', 'error');
        return;
    }
    
    document.getElementById('inventory_id').value = item.inventory_id;
    document.getElementById('item_name').value = item.item_name;
    document.getElementById('item_type').value = item.item_type;
    document.getElementById('brand').value = item.brand || '';
    document.getElementById('price').value = item.price;
    document.getElementById('quantity').value = item.quantity;
    document.getElementById('min_stock_level').value = item.min_stock_level;
    document.getElementById('unit').value = item.unit;
    document.getElementById('inventoryModalTitle').textContent = 'Edit Inventory Item';
    modal('inventoryModal', true);
}

// Adjust stock levels
async function adjustStock(id, name, currentQty) {
    const dialogHTML = `
        <div id="stockDialog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-2xl">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Adjust Stock: ${name}</h3>
                <p class="text-sm text-gray-600 mb-4">Current quantity: <strong>${currentQty}</strong></p>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-medium mb-2">New Quantity</label>
                    <input type="number" id="newQuantity" value="${currentQty}" min="0" 
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 outline-none">
                </div>
                <div class="flex gap-3">
                    <button onclick="document.getElementById('stockDialog').remove()" 
                            class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 rounded-lg transition">
                        Cancel
                    </button>
                    <button onclick="updateStock(${id}, ${currentQty})" 
                            class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 rounded-lg transition">
                        Update Stock
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', dialogHTML);
}

// Update stock quantity
async function updateStock(id, oldQty) {
    const newQty = document.getElementById('newQuantity').value;
    document.getElementById('stockDialog').remove();
    
    const item = inventory.find(i => i.inventory_id == id);
    if (!item) return;
    
    loading(true);
    try {
        const res = await Ajax.post('<?= BASE_URL ?>api/inventory.php', {
            inventory_id: id,
            item_name: item.item_name,
            item_type: item.item_type,
            brand: item.brand,
            price: item.price,
            quantity: newQty,
            min_stock_level: item.min_stock_level,
            unit: item.unit
        });
        showAlert('Stock quantity updated successfully', 'success');
        loadInventory();
    } catch (error) {
        console.error('Error updating stock:', error);
        showAlert('Error updating stock quantity', 'error');
    } finally {
        loading(false);
    }
}

// Save inventory item
async function saveInventory(e) {
    e.preventDefault();
    loading(true);
    
    const data = {
        inventory_id: document.getElementById('inventory_id').value,
        item_name: document.getElementById('item_name').value,
        item_type: document.getElementById('item_type').value,
        brand: document.getElementById('brand').value || null,
        price: document.getElementById('price').value,
        quantity: document.getElementById('quantity').value,
        min_stock_level: document.getElementById('min_stock_level').value || 10,
        unit: document.getElementById('unit').value
    };

    try {
        const res = await Ajax.post('<?= BASE_URL ?>api/inventory.php', data);
        showAlert(res.message || 'Inventory item saved successfully', 'success');
        closeInventoryModal();
        loadInventory();
    } catch (error) {
        console.error('Error saving inventory:', error);
        showAlert('Error saving inventory item', 'error');
    } finally {
        loading(false);
    }
}

// Delete inventory item
async function deleteInventory(id, name) {
    const confirmed = confirm(`Are you sure you want to delete "${name}"?\n\nThis action cannot be undone.`);
    if (!confirmed) return;
    
    loading(true);
    try {
        const res = await Ajax.delete('<?= BASE_URL ?>api/inventory.php', { inventory_id: id });
        showAlert(res.message || 'Inventory item deleted successfully', 'success');
        loadInventory();
    } catch (error) {
        console.error('Error deleting inventory:', error);
        showAlert('Error deleting inventory item', 'error');
    } finally {
        loading(false);
    }
}

// Load inventory when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inventory page loaded, loading items...');
    loadInventory();
});
</script>

<?php
$content = ob_get_clean();
include '../../layout.php';
?>
