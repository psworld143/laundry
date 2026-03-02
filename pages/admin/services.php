<?php
require_once '../../config.php';
if (!auth() || $_SESSION['position'] !== 'admin') redirect('login.php');

// Get service statistics
$serviceStats = [
    'total' => $db->query("SELECT COUNT(*) FROM services")->fetchColumn(),
    'active' => $db->query("SELECT COUNT(*) FROM services WHERE is_active = 1")->fetchColumn(),
    'wash_fold' => $db->query("SELECT COUNT(*) FROM services WHERE service_type = 'wash_fold'")->fetchColumn(),
    'dry_clean' => $db->query("SELECT COUNT(*) FROM services WHERE service_type = 'dry_clean'")->fetchColumn(),
    'ironing' => $db->query("SELECT COUNT(*) FROM services WHERE service_type = 'ironing'")->fetchColumn(),
    'express' => $db->query("SELECT COUNT(*) FROM services WHERE service_type = 'express'")->fetchColumn(),
];

$pageTitle = 'Services Management';
ob_start();
?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-concierge-bell text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">Total</span>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= $serviceStats['total'] ?></h3>
        <p class="text-blue-100 text-sm">Total Services</p>
    </div>

    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-check-circle text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">Active</span>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= $serviceStats['active'] ?></h3>
        <p class="text-green-100 text-sm">Active Services</p>
    </div>

    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-tshirt text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">Wash & Fold</span>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= $serviceStats['wash_fold'] ?></h3>
        <p class="text-purple-100 text-sm">Wash Services</p>
    </div>

    <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-fire text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">Express</span>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= $serviceStats['express'] ?></h3>
        <p class="text-orange-100 text-sm">Express Services</p>
    </div>
</div>

<!-- Services Grid -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Service Offerings</h2>
            <p class="text-gray-500 text-sm mt-1">Manage your laundry service packages and pricing</p>
        </div>
        <button onclick="openServiceModal()" class="bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white px-6 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transition transform hover:scale-105">
            <i class="fas fa-plus mr-2"></i>Add Service
        </button>
    </div>

    <div id="servicesGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="text-center py-12 col-span-full">
            <i class="fas fa-spinner fa-spin text-4xl text-blue-500 mb-4"></i>
            <p class="text-gray-500">Loading services...</p>
        </div>
    </div>
</div>

<!-- Service Modal -->
<div id="serviceModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-8 max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent" id="serviceModalTitle">Add Service</h3>
            <button onclick="closeServiceModal()" class="text-gray-400 hover:text-gray-600 transition">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>

        <form id="serviceForm" onsubmit="saveService(event)">
            <input type="hidden" id="service_id">
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-tag text-blue-500 mr-1"></i>Service Name *
                    </label>
                    <input type="text" id="service_name" required 
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                           placeholder="e.g., Premium Wash & Fold">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-list text-purple-500 mr-1"></i>Service Type *
                    </label>
                    <select id="service_type" required 
                            class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition">
                        <option value="">Select Type</option>
                        <option value="wash_fold">🧺 Wash & Fold</option>
                        <option value="dry_clean">👔 Dry Cleaning</option>
                        <option value="ironing">👕 Ironing Only</option>
                        <option value="express">⚡ Express Service</option>
                        <option value="pickup_delivery">🚚 Pickup & Delivery</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2">
                    <i class="fas fa-align-left text-green-500 mr-1"></i>Description
                </label>
                <textarea id="description" rows="3" 
                          class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                          placeholder="Describe the service details..."></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-peso-sign text-green-500 mr-1"></i>Base Price (₱) *
                    </label>
                    <input type="number" id="base_price" step="0.01" min="0" required 
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                           placeholder="0.00">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-clock text-yellow-500 mr-1"></i>Estimated Duration (hours)
                    </label>
                    <input type="number" id="estimated_duration" min="1" 
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                           placeholder="e.g., 24">
                </div>
            </div>

            <div class="flex gap-4">
                <button type="button" onclick="closeServiceModal()" 
                        class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 rounded-lg transition">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <button type="submit" 
                        class="flex-1 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-semibold py-3 rounded-lg transition shadow-lg">
                    <i class="fas fa-save mr-2"></i>Save Service
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let services = [];

// Load services from API
async function loadServices() {
    loading(true);
    try {
        const res = await Ajax.get('<?= BASE_URL ?>api/services.php');
        console.log('Services response:', res);
        
        if (res.success) {
            services = res.data || [];
            console.log('Services loaded:', services.length);
            renderServices();
        } else {
            showAlert('Failed to load services: ' + res.message, 'error');
            renderEmptyState();
        }
    } catch (error) {
        console.error('Error loading services:', error);
        showAlert('Error loading services. Check console for details.', 'error');
        renderEmptyState();
    } finally {
        loading(false);
    }
}

// Render empty state
function renderEmptyState() {
    document.getElementById('servicesGrid').innerHTML = `
        <div class="col-span-full text-center py-16">
            <div class="bg-gray-100 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-concierge-bell text-4xl text-gray-400"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">No Services Yet</h3>
            <p class="text-gray-500 mb-6">Add your first service to start offering laundry solutions</p>
            <button onclick="openServiceModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold transition">
                <i class="fas fa-plus mr-2"></i>Add Service
            </button>
        </div>
    `;
}

// Render services grid
function renderServices() {
    const types = {
        wash_fold: { label: 'Wash & Fold', icon: 'fa-tshirt', emoji: '🧺', color: 'blue' },
        dry_clean: { label: 'Dry Cleaning', icon: 'fa-wind', emoji: '👔', color: 'purple' },
        ironing: { label: 'Ironing', icon: 'fa-fire', emoji: '👕', color: 'orange' },
        express: { label: 'Express', icon: 'fa-bolt', emoji: '⚡', color: 'yellow' },
        pickup_delivery: { label: 'Pickup & Delivery', icon: 'fa-truck', emoji: '🚚', color: 'green' }
    };
    
    if (services.length === 0) {
        renderEmptyState();
        return;
    }
    
    const html = services.map(s => {
        const type = types[s.service_type] || { label: s.service_type, icon: 'fa-tag', emoji: '📦', color: 'gray' };
        return `
        <div class="group bg-white border-2 border-gray-200 rounded-2xl p-6 hover:border-${type.color}-400 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <!-- Service Header -->
            <div class="flex justify-between items-start mb-4">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-2xl">${type.emoji}</span>
                        <h3 class="text-xl font-bold text-gray-800">${s.service_name}</h3>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-${type.color}-100 text-${type.color}-800">
                        <i class="fas ${type.icon} mr-1"></i>${type.label}
                    </span>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold ${s.is_active == 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                    <i class="fas fa-circle text-xs mr-1"></i>${s.is_active == 1 ? 'Active' : 'Inactive'}
                </span>
            </div>

            <!-- Description -->
            <p class="text-gray-600 text-sm mb-6 min-h-[40px]">${s.description || 'No description provided'}</p>

            <!-- Pricing Info -->
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-gradient-to-br from-${type.color}-50 to-${type.color}-100 rounded-xl p-4 text-center">
                    <p class="text-3xl font-bold text-${type.color}-700">₱${parseFloat(s.base_price).toFixed(2)}</p>
                    <p class="text-xs text-${type.color}-600 font-medium mt-1">Base Price</p>
                </div>
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-4 text-center">
                    <p class="text-3xl font-bold text-gray-700">${s.estimated_duration || '—'}</p>
                    <p class="text-xs text-gray-600 font-medium mt-1">Hours</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-2">
                <button onclick="toggleServiceStatus(${s.service_id}, ${s.is_active == 1 ? 0 : 1})" 
                        class="flex-1 bg-yellow-100 hover:bg-yellow-200 text-yellow-700 font-semibold py-2 rounded-lg transition"
                        title="${s.is_active == 1 ? 'Deactivate' : 'Activate'}">
                    <i class="fas fa-${s.is_active == 1 ? 'pause' : 'play'} mr-1"></i>${s.is_active == 1 ? 'Pause' : 'Activate'}
                </button>
                <button onclick="editService(${s.service_id})" 
                        class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 rounded-lg transition">
                    <i class="fas fa-edit mr-1"></i>Edit
                </button>
                <button onclick="deleteService(${s.service_id}, '${s.service_name}')" 
                        class="flex-1 bg-red-500 hover:bg-red-600 text-white font-semibold py-2 rounded-lg transition">
                    <i class="fas fa-trash mr-1"></i>Delete
                </button>
            </div>
        </div>
    `}).join('');
    
    document.getElementById('servicesGrid').innerHTML = html;
}

// Open modal for new service
function openServiceModal() {
    document.getElementById('serviceForm').reset();
    document.getElementById('service_id').value = '';
    document.getElementById('serviceModalTitle').textContent = 'Add Service';
    modal('serviceModal', true);
}

// Close modal
function closeServiceModal() {
    modal('serviceModal', false);
}

// Edit service
function editService(id) {
    const service = services.find(s => s.service_id == id);
    if (!service) {
        showAlert('Service not found', 'error');
        return;
    }
    
    document.getElementById('service_id').value = service.service_id;
    document.getElementById('service_name').value = service.service_name;
    document.getElementById('service_type').value = service.service_type;
    document.getElementById('description').value = service.description || '';
    document.getElementById('base_price').value = service.base_price;
    document.getElementById('estimated_duration').value = service.estimated_duration || '';
    document.getElementById('serviceModalTitle').textContent = 'Edit Service';
    modal('serviceModal', true);
}

// Toggle service status
async function toggleServiceStatus(id, newStatus) {
    loading(true);
    try {
        const res = await Ajax.post('<?= BASE_URL ?>api/services.php', {
            service_id: id,
            is_active: newStatus
        });
        showAlert(newStatus == 1 ? 'Service activated' : 'Service deactivated', 'success');
        loadServices();
    } catch (error) {
        console.error('Error toggling service status:', error);
        showAlert('Error updating service status', 'error');
    } finally {
        loading(false);
    }
}

async function saveService(e) {
    e.preventDefault();
    loading(true);
    
    const data = {
        service_id: document.getElementById('service_id').value,
        service_name: document.getElementById('service_name').value,
        service_type: document.getElementById('service_type').value,
        description: document.getElementById('description').value,
        base_price: document.getElementById('base_price').value,
        estimated_duration: document.getElementById('estimated_duration').value,
    };

    try {
        const res = await Ajax.post('<?= BASE_URL ?>api/services.php', data);
        showAlert(res.message, 'success');
        closeServiceModal();
        loadServices();
    } catch (error) {
        showAlert('Error saving service', 'error');
    } finally {
        loading(false);
    }
}

async function deleteService(id, name) {
    // Confirmation dialog
    const confirmed = confirm(`Are you sure you want to delete "${name}"?\n\nThis action cannot be undone.`);
    if (!confirmed) return;
    
    loading(true);
    try {
        const res = await Ajax.delete('<?= BASE_URL ?>api/services.php', { service_id: id });
        showAlert(res.message || 'Service deleted successfully', 'success');
        loadServices();
    } catch (error) {
        console.error('Error deleting service:', error);
        showAlert('Error deleting service', 'error');
    } finally {
        loading(false);
    }
}

// Load services when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('Services page loaded, loading services...');
    loadServices();
});
</script>

<?php
$content = ob_get_clean();
include '../../layout.php';
?>

