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

// Get fabric statistics
$fabricStats = [
    'total_fabrics' => $db->query("SELECT COUNT(*) FROM admin_fabrics WHERE is_active = 1")->fetchColumn(),
    'fabric_types' => $db->query("SELECT COUNT(DISTINCT fabric_type) FROM admin_fabrics WHERE is_active = 1")->fetchColumn(),
    'popular_fabrics' => $db->query("SELECT COUNT(*) FROM admin_fabrics WHERE is_popular = 1 AND is_active = 1")->fetchColumn(),
    'total_customers' => $db->query("SELECT COUNT(DISTINCT user_id) FROM customer_inventory_fabric WHERE is_active = 1")->fetchColumn(),
];

$pageTitle = 'Fabcon Brand Management';
ob_start();
?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-tshirt text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">Total</span>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= $fabricStats['total_fabrics'] ?></h3>
        <p class="text-blue-100 text-sm">Fabcon Brands</p>
    </div>

    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-layer-group text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">Categories</span>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= $fabricStats['fabric_types'] ?></h3>
        <p class="text-purple-100 text-sm">Fabcon Categories</p>
    </div>

    <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-star text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">Popular</span>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= $fabricStats['popular_fabrics'] ?></h3>
        <p class="text-yellow-100 text-sm">Popular Fabcon Brands</p>
    </div>

    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-users text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">Customers</span>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= $fabricStats['total_customers'] ?></h3>
        <p class="text-green-100 text-sm">Active Customers</p>
    </div>
</div>

<!-- Fabric Management -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Fabcon Brand Management</h2>
            <p class="text-gray-500 text-sm mt-1">Manage fabcon brands (Downy, Surf, etc.) that customers can choose from for their laundry</p>
        </div>
        <button onclick="openFabricModal()" class="bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white px-6 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transition transform hover:scale-105">
            <i class="fas fa-plus mr-2"></i>Add Fabcon Brand
        </button>
    </div>

    <div id="fabricGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="text-center py-12 col-span-full">
            <i class="fas fa-spinner fa-spin text-4xl text-blue-500 mb-4"></i>
            <p class="text-gray-500">Loading fabrics...</p>
        </div>
    </div>
</div>

<!-- Fabric Modal -->
<div id="fabricModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-8 max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent" id="fabricModalTitle">Add Fabcon Brand</h3>
            <button onclick="closeFabricModal()" class="text-gray-400 hover:text-gray-600 transition">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>

        <form id="fabricForm" onsubmit="saveFabric(event)">
            <input type="hidden" id="fabric_id">
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-tag text-blue-500 mr-1"></i>Fabcon Brand Name *
                    </label>
                    <input type="text" id="fabric_name" required
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                           placeholder="e.g., Downy Original, Surf Excel, Comfort Blue">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-list text-purple-500 mr-1"></i>Fabcon Type *
                    </label>
                    <select id="fabric_type" required
                            class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition">
                        <option value="">Select Fabcon Type</option>
                        <option value="liquid">💧 Liquid Fabcon</option>
                        <option value="sheets">📄 Dryer Sheets</option>
                        <option value="powder">⚗️ Powder Fabcon</option>
                        <option value="concentrate">🎯 Concentrated</option>
                        <option value="organic">🌿 Organic</option>
                        <option value="sensitive">🤧 Sensitive Skin</option>
                        <option value="fragrance_free">🌸 Fragrance-Free</option>
                        <option value="other">📦 Other</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-dollar-sign text-green-500 mr-1"></i>Base Price Multiplier *
                    </label>
                    <input type="number" id="price_multiplier" step="0.01" min="0.5" max="3.0" required
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                           placeholder="1.00" value="1.00">
                    <p class="text-xs text-gray-500 mt-1">Multiplier for base service price (0.5 - 3.0)</p>
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-thermometer-half text-orange-500 mr-1"></i>Wash Temperature
                    </label>
                    <select id="wash_temperature"
                            class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition">
                        <option value="cold">❄️ Cold Water</option>
                        <option value="warm">🌡️ Warm Water</option>
                        <option value="hot">🔥 Hot Water</option>
                        <option value="hand_wash">✋ Hand Wash Only</option>
                        <option value="dry_clean">🧽 Dry Clean Only</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2">
                    <i class="fas fa-info-circle text-blue-500 mr-1"></i>Description
                </label>
                <textarea id="description" rows="3"
                          class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                          placeholder="Brief description of this fabric type..."></textarea>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2">
                    <i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i>Special Care Instructions
                </label>
                <textarea id="care_instructions" rows="4"
                          class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                          placeholder="Detailed care instructions for this fabric..."></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-clock text-indigo-500 mr-1"></i>Estimated Processing Time (hours)
                    </label>
                    <input type="number" id="processing_time" min="1" max="72"
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                           placeholder="24" value="24">
                </div>
                <div class="flex items-center justify-center">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" id="is_popular" class="sr-only">
                        <div class="relative">
                            <div class="w-10 h-6 bg-gray-200 rounded-full shadow-inner"></div>
                            <div class="absolute w-4 h-4 bg-white rounded-full shadow top-1 left-1 transition-transform duration-200 ease-in-out"></div>
                        </div>
                        <span class="ml-3 text-sm font-medium text-gray-700">
                            <i class="fas fa-star text-yellow-500 mr-1"></i>Popular Fabric
                        </span>
                    </label>
                </div>
            </div>

            <div class="flex gap-4">
                <button type="button" onclick="closeFabricModal()" 
                        class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 rounded-lg transition">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <button type="submit" 
                        class="flex-1 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-semibold py-3 rounded-lg transition shadow-lg">
                    <i class="fas fa-save mr-2"></i>Save Fabric
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let fabrics = [];

// Load fabrics from API
async function loadFabrics() {
    loading(true);
    try {
        const res = await Ajax.get('<?= BASE_URL ?>api/admin_fabrics.php');
        console.log('Fabrics response:', res);
        
        if (res.success) {
            fabrics = res.data || [];
            console.log('Fabrics loaded:', fabrics.length);
            renderFabrics();
        } else {
            showAlert('Failed to load fabrics: ' + res.message, 'error');
            renderEmptyState();
        }
    } catch (error) {
        console.error('Error loading fabrics:', error);
        showAlert('Error loading fabrics. Check console for details.', 'error');
        renderEmptyState();
    } finally {
        loading(false);
    }
}

// Render empty state
function renderEmptyState() {
    document.getElementById('fabricGrid').innerHTML = `
        <div class="col-span-full text-center py-16">
            <div class="bg-gray-100 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-tshirt text-4xl text-gray-400"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">No Fabric Types Yet</h3>
            <p class="text-gray-500 mb-6">Add fabric types to help customers choose appropriate care for their items</p>
            <button onclick="openFabricModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold transition">
                <i class="fas fa-plus mr-2"></i>Add Fabric Type
            </button>
        </div>
    `;
}

// Render fabric grid
function renderFabrics() {
    const typeEmojis = {
        cotton: '🧵',
        polyester: '🔗',
        wool: '🐑',
        silk: '🦋',
        linen: '🌾',
        denim: '👖',
        leather: '👜',
        synthetic: '⚗️',
        other: '📦'
    };
    
    if (fabrics.length === 0) {
        renderEmptyState();
        return;
    }
    
    const html = fabrics.map(fabric => {
        const emoji = typeEmojis[fabric.fabric_type] || '📦';
        const temperatureEmojis = {
            cold: '❄️',
            warm: '🌡️',
            hot: '🔥',
            hand_wash: '✋',
            dry_clean: '🧽'
        };
        const tempEmoji = temperatureEmojis[fabric.wash_temperature] || '🌡️';
        
        return `
        <div class="group bg-white border-2 border-gray-200 rounded-2xl p-6 hover:border-blue-400 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <!-- Fabric Header -->
            <div class="flex justify-between items-start mb-4">
                <div class="flex items-center gap-3">
                    <div class="bg-gradient-to-br from-blue-500 to-purple-600 w-14 h-14 rounded-xl flex items-center justify-center text-3xl shadow-lg">
                        ${emoji}
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">${fabric.fabric_name}</h3>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                            <i class="fas fa-layer-group mr-1"></i>${fabric.fabric_type}
                        </span>
                    </div>
                </div>
                ${fabric.is_popular ? `
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                    <i class="fas fa-star mr-1"></i>Popular
                </span>
                ` : ''}
            </div>

            <!-- Description -->
            ${fabric.description ? `
            <div class="mb-3">
                <p class="text-sm text-gray-600">${fabric.description}</p>
            </div>
            ` : ''}

            <!-- Fabric Details -->
            <div class="bg-gray-50 rounded-xl p-4 mb-4">
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div class="text-center">
                        <p class="text-xs text-gray-600 font-medium mb-1">Price Multiplier</p>
                        <p class="text-lg font-bold text-green-700">${parseFloat(fabric.price_multiplier).toFixed(2)}x</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-600 font-medium mb-1">Processing Time</p>
                        <p class="text-lg font-bold text-blue-700">${fabric.processing_time}h</p>
                    </div>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-600 font-medium mb-1">Wash Temperature</p>
                    <p class="text-sm font-semibold text-gray-800">${tempEmoji} ${fabric.wash_temperature.replace('_', ' ')}</p>
                </div>
            </div>

            <!-- Care Instructions -->
            ${fabric.care_instructions ? `
            <div class="mb-4">
                <p class="text-xs text-gray-600 font-medium mb-2">Care Instructions:</p>
                <p class="text-sm text-gray-700 bg-yellow-50 p-3 rounded-lg">${fabric.care_instructions}</p>
            </div>
            ` : ''}

            <!-- Actions -->
            <div class="flex gap-2 pt-4 border-t border-gray-200">
                <button onclick="editFabric(${fabric.fabric_id})" 
                        class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 rounded-lg transition">
                    <i class="fas fa-edit mr-1"></i>Edit
                </button>
                <button onclick="deleteFabric(${fabric.fabric_id}, '${fabric.fabric_name}')" 
                        class="flex-1 bg-red-500 hover:bg-red-600 text-white font-semibold py-2 rounded-lg transition">
                    <i class="fas fa-trash mr-1"></i>Delete
                </button>
            </div>
        </div>
    `}).join('');
    
    document.getElementById('fabricGrid').innerHTML = html;
}

// Open modal for new fabcon brand
function openFabricModal() {
    document.getElementById('fabricForm').reset();
    document.getElementById('fabric_id').value = '';
    document.getElementById('fabricModalTitle').textContent = 'Add Fabcon Brand';
    modal('fabricModal', true);
}

// Close modal
function closeFabricModal() {
    modal('fabricModal', false);
}

// Edit fabric
function editFabric(id) {
    const fabric = fabrics.find(f => f.fabric_id == id);
    if (!fabric) {
        showAlert('Fabric not found', 'error');
        return;
    }
    
    document.getElementById('fabric_id').value = fabric.fabric_id;
    document.getElementById('fabric_name').value = fabric.fabric_name;
    document.getElementById('fabric_type').value = fabric.fabric_type;
    document.getElementById('price_multiplier').value = fabric.price_multiplier;
    document.getElementById('wash_temperature').value = fabric.wash_temperature;
    document.getElementById('description').value = fabric.description || '';
    document.getElementById('care_instructions').value = fabric.care_instructions || '';
    document.getElementById('processing_time').value = fabric.processing_time;
    document.getElementById('is_popular').checked = fabric.is_popular == 1;
    document.getElementById('fabricModalTitle').textContent = 'Edit Fabcon Brand';
    modal('fabricModal', true);
}

// Save fabric
async function saveFabric(e) {
    e.preventDefault();
    loading(true);
    
    const data = {
        fabric_id: document.getElementById('fabric_id').value,
        fabric_name: document.getElementById('fabric_name').value,
        fabric_type: document.getElementById('fabric_type').value,
        price_multiplier: document.getElementById('price_multiplier').value,
        wash_temperature: document.getElementById('wash_temperature').value,
        description: document.getElementById('description').value || null,
        care_instructions: document.getElementById('care_instructions').value || null,
        processing_time: document.getElementById('processing_time').value,
        is_popular: document.getElementById('is_popular').checked ? 1 : 0
    };

    try {
        const res = await Ajax.post('<?= BASE_URL ?>api/admin_fabrics.php', data);
        showAlert(res.message || 'Fabric saved successfully', 'success');
        closeFabricModal();
        loadFabrics();
    } catch (error) {
        console.error('Error saving fabric:', error);
        showAlert('Error saving fabric', 'error');
    } finally {
        loading(false);
    }
}

// Delete fabric
async function deleteFabric(id, name) {
    const confirmed = confirm(`Are you sure you want to delete "${name}"?\n\nThis action cannot be undone.`);
    if (!confirmed) return;
    
    loading(true);
    try {
        const res = await Ajax.delete('<?= BASE_URL ?>api/admin_fabrics.php', { fabric_id: id });
        showAlert(res.message || 'Fabric deleted successfully', 'success');
        loadFabrics();
    } catch (error) {
        console.error('Error deleting fabric:', error);
        showAlert('Error deleting fabric', 'error');
    } finally {
        loading(false);
    }
}

// Load fabrics when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('Fabrics page loaded, loading items...');
    loadFabrics();
});
</script>

<?php
$content = ob_get_clean();
include '../../layout.php';
?>
