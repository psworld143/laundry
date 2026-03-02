<?php
require_once '../../config.php';
if (!auth() || $_SESSION['position'] !== 'admin') redirect('login.php');

// Get machine statistics
$machineStats = [
    'total' => $db->query("SELECT COUNT(*) FROM machines")->fetchColumn(),
    'available' => $db->query("SELECT COUNT(*) FROM machines WHERE status = 'available'")->fetchColumn(),
    'in_use' => $db->query("SELECT COUNT(*) FROM machines WHERE status = 'in_use'")->fetchColumn(),
    'maintenance' => $db->query("SELECT COUNT(*) FROM machines WHERE status = 'maintenance'")->fetchColumn(),
    'washing_machines' => $db->query("SELECT COUNT(*) FROM machines WHERE machine_type = 'washing_machine'")->fetchColumn(),
    'dryers' => $db->query("SELECT COUNT(*) FROM machines WHERE machine_type = 'dryer'")->fetchColumn(),
    'irons' => $db->query("SELECT COUNT(*) FROM machines WHERE machine_type = 'iron'")->fetchColumn(),
];

$pageTitle = 'Machine Management';
ob_start();
?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-cogs text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">Total</span>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= $machineStats['total'] ?></h3>
        <p class="text-blue-100 text-sm">Total Machines</p>
    </div>

    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-check-circle text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">Available</span>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= $machineStats['available'] ?></h3>
        <p class="text-green-100 text-sm">Ready to Use</p>
    </div>

    <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-spinner text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">In Use</span>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= $machineStats['in_use'] ?></h3>
        <p class="text-yellow-100 text-sm">Currently Running</p>
    </div>

    <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-tools text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">Maintenance</span>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= $machineStats['maintenance'] ?></h3>
        <p class="text-orange-100 text-sm">Under Repair</p>
    </div>
</div>

<!-- Machines Grid -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Equipment Inventory</h2>
            <p class="text-gray-500 text-sm mt-1">Manage your laundry machines and equipment</p>
        </div>
        <button onclick="openModal()" class="bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white px-6 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transition transform hover:scale-105">
            <i class="fas fa-plus mr-2"></i>Add Machine
        </button>
    </div>

    <div id="machinesGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="text-center py-12 col-span-full">
            <i class="fas fa-spinner fa-spin text-4xl text-blue-500 mb-4"></i>
            <p class="text-gray-500">Loading machines...</p>
        </div>
    </div>
</div>

<!-- Machine Modal -->
<div id="machineModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-8 max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent" id="modalTitle">Add Machine</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>

        <form id="machineForm" onsubmit="saveMachine(event)">
            <input type="hidden" id="machine_id">
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-tag text-blue-500 mr-1"></i>Machine Name *
                    </label>
                    <input type="text" id="machine_name" required
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                           placeholder="e.g., Washer-01">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-cog text-purple-500 mr-1"></i>Machine Type *
                    </label>
                    <select id="machine_type" required
                            class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition">
                        <option value="">Select Type</option>
                        <option value="washing_machine">🌊 Washing Machine</option>
                        <option value="dryer">🌡️ Dryer</option>
                        <option value="iron">🔥 Iron</option>
                        <option value="steamer">💨 Steamer</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-industry text-green-500 mr-1"></i>Brand *
                    </label>
                    <input type="text" id="brand" required
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                           placeholder="e.g., Samsung">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-barcode text-orange-500 mr-1"></i>Model *
                    </label>
                    <input type="text" id="model" required
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                           placeholder="e.g., WW10K">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-weight text-yellow-500 mr-1"></i>Capacity *
                    </label>
                    <input type="text" id="capacity" required placeholder="e.g., 10kg or 2400W"
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-map-marker-alt text-red-500 mr-1"></i>Location *
                    </label>
                    <input type="text" id="location" required
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                           placeholder="e.g., Floor 1">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-calendar text-teal-500 mr-1"></i>Purchase Date
                    </label>
                    <input type="date" id="purchase_date"
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-info-circle text-indigo-500 mr-1"></i>Status *
                    </label>
                    <select id="status" required
                            class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition">
                        <option value="available">✅ Available</option>
                        <option value="in_use">⏳ In Use</option>
                        <option value="maintenance">🔧 Maintenance</option>
                        <option value="broken">❌ Broken</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-4">
                <button type="button" onclick="closeModal()" 
                        class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 rounded-lg transition">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <button type="submit" 
                        class="flex-1 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-semibold py-3 rounded-lg transition shadow-lg">
                    <i class="fas fa-save mr-2"></i>Save Machine
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let machines = [];

// Load machines from API
async function loadMachines() {
    loading(true);
    try {
        const res = await Ajax.get('<?= BASE_URL ?>api/machines.php');
        console.log('Machines response:', res);
        
        if (res.success) {
            machines = res.data || [];
            console.log('Machines loaded:', machines.length);
            renderMachines();
        } else {
            showAlert('Failed to load machines: ' + res.message, 'error');
            renderEmptyState();
        }
    } catch (error) {
        console.error('Error loading machines:', error);
        showAlert('Error loading machines. Check console for details.', 'error');
        renderEmptyState();
    } finally {
        loading(false);
    }
}

// Render empty state
function renderEmptyState() {
    document.getElementById('machinesGrid').innerHTML = `
        <div class="col-span-full text-center py-16">
            <div class="bg-gray-100 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-cogs text-4xl text-gray-400"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">No Machines Yet</h3>
            <p class="text-gray-500 mb-6">Add your first machine to start managing your equipment inventory</p>
            <button onclick="openModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold transition">
                <i class="fas fa-plus mr-2"></i>Add Machine
            </button>
        </div>
    `;
}

// Render machines grid
function renderMachines() {
    const types = {
        washing_machine: { label: 'Washing Machine', icon: 'fa-tshirt', emoji: '🌊', color: 'blue' },
        dryer: { label: 'Dryer', icon: 'fa-wind', emoji: '🌡️', color: 'orange' },
        iron: { label: 'Iron', icon: 'fa-fire', emoji: '🔥', color: 'red' },
        steamer: { label: 'Steamer', icon: 'fa-smoke', emoji: '💨', color: 'purple' }
    };
    
    const statuses = {
        available: { label: 'Available', color: 'green', icon: 'fa-check-circle' },
        in_use: { label: 'In Use', color: 'yellow', icon: 'fa-spinner' },
        maintenance: { label: 'Maintenance', color: 'orange', icon: 'fa-tools' },
        broken: { label: 'Broken', color: 'red', icon: 'fa-exclamation-triangle' }
    };
    
    if (machines.length === 0) {
        renderEmptyState();
        return;
    }
    
    const html = machines.map(m => {
        const type = types[m.machine_type] || { label: m.machine_type, icon: 'fa-cog', emoji: '⚙️', color: 'gray' };
        const status = statuses[m.status] || { label: m.status, color: 'gray', icon: 'fa-question' };
        return `
        <div class="group bg-white border-2 border-gray-200 rounded-2xl p-6 hover:border-${type.color}-400 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <!-- Machine Header -->
            <div class="flex justify-between items-start mb-4">
                <div class="flex items-center gap-3">
                    <div class="bg-gradient-to-br from-${type.color}-500 to-${type.color}-600 w-14 h-14 rounded-xl flex items-center justify-center text-3xl shadow-lg">
                        ${type.emoji}
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">${m.machine_name}</h3>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-${type.color}-100 text-${type.color}-800">
                            <i class="fas ${type.icon} mr-1"></i>${type.label}
                        </span>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-${status.color}-100 text-${status.color}-800">
                    <i class="fas ${status.icon} text-xs mr-1"></i>${status.label}
                </span>
            </div>

            <!-- Machine Details -->
            <div class="space-y-2 mb-4">
                <div class="flex items-center text-gray-600 text-sm">
                    <i class="fas fa-industry text-gray-400 w-5 mr-2"></i>
                    <span class="font-semibold">${m.brand}</span>
                    <span class="mx-1">•</span>
                    <span>${m.model}</span>
                </div>
                <div class="flex items-center text-gray-600 text-sm">
                    <i class="fas fa-weight text-gray-400 w-5 mr-2"></i>
                    <span>Capacity: <strong>${m.capacity}</strong></span>
                </div>
                <div class="flex items-center text-gray-600 text-sm">
                    <i class="fas fa-map-marker-alt text-gray-400 w-5 mr-2"></i>
                    <span>${m.location}</span>
                </div>
                ${m.purchase_date ? `
                <div class="flex items-center text-gray-600 text-sm">
                    <i class="fas fa-calendar text-gray-400 w-5 mr-2"></i>
                    <span>Purchased: ${new Date(m.purchase_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</span>
                </div>
                ` : ''}
            </div>

            <!-- Actions -->
            <div class="flex gap-2 pt-4 border-t border-gray-200">
                <button onclick="changeStatus(${m.machine_id}, '${m.status}')" 
                        class="flex-1 bg-yellow-100 hover:bg-yellow-200 text-yellow-700 font-semibold py-2 rounded-lg transition"
                        title="Change Status">
                    <i class="fas fa-sync-alt mr-1"></i>Status
                </button>
                <button onclick="editMachine(${m.machine_id})" 
                        class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 rounded-lg transition">
                    <i class="fas fa-edit mr-1"></i>Edit
                </button>
                <button onclick="deleteMachine(${m.machine_id}, '${m.machine_name}')" 
                        class="flex-1 bg-red-500 hover:bg-red-600 text-white font-semibold py-2 rounded-lg transition">
                    <i class="fas fa-trash mr-1"></i>Delete
                </button>
            </div>
        </div>
    `}).join('');
    
    document.getElementById('machinesGrid').innerHTML = html;
}

// Open modal for new machine
function openModal() {
    document.getElementById('machineForm').reset();
    document.getElementById('machine_id').value = '';
    document.getElementById('modalTitle').textContent = 'Add Machine';
    modal('machineModal', true);
}

// Close modal
function closeModal() {
    modal('machineModal', false);
}

// Edit machine
function editMachine(id) {
    const machine = machines.find(m => m.machine_id == id);
    if (!machine) {
        showAlert('Machine not found', 'error');
        return;
    }
    
    document.getElementById('machine_id').value = machine.machine_id;
    document.getElementById('machine_name').value = machine.machine_name;
    document.getElementById('machine_type').value = machine.machine_type;
    document.getElementById('brand').value = machine.brand;
    document.getElementById('model').value = machine.model;
    document.getElementById('capacity').value = machine.capacity;
    document.getElementById('location').value = machine.location;
    document.getElementById('status').value = machine.status;
    document.getElementById('purchase_date').value = machine.purchase_date || '';
    document.getElementById('modalTitle').textContent = 'Edit Machine';
    modal('machineModal', true);
}

// Change machine status
async function changeStatus(id, currentStatus) {
    const statuses = [
        { value: 'available', label: '✅ Available' },
        { value: 'in_use', label: '⏳ In Use' },
        { value: 'maintenance', label: '🔧 Maintenance' },
        { value: 'broken', label: '❌ Broken' }
    ];
    
    // Create options string
    const options = statuses.map(s => 
        `<option value="${s.value}" ${s.value === currentStatus ? 'selected' : ''}>${s.label}</option>`
    ).join('');
    
    // Create custom dialog
    const dialogHTML = `
        <div id="statusDialog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-2xl">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Change Machine Status</h3>
                <select id="newStatus" class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 outline-none mb-4">
                    ${options}
                </select>
                <div class="flex gap-3">
                    <button onclick="document.getElementById('statusDialog').remove()" 
                            class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 rounded-lg transition">
                        Cancel
                    </button>
                    <button onclick="updateMachineStatus(${id})" 
                            class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 rounded-lg transition">
                        Update Status
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', dialogHTML);
}

// Update machine status
async function updateMachineStatus(id) {
    const newStatus = document.getElementById('newStatus').value;
    document.getElementById('statusDialog').remove();
    
    loading(true);
    try {
        const res = await Ajax.post('<?= BASE_URL ?>api/machines.php', {
            machine_id: id,
            status: newStatus
        });
        showAlert('Machine status updated successfully', 'success');
        loadMachines();
    } catch (error) {
        console.error('Error updating machine status:', error);
        showAlert('Error updating machine status', 'error');
    } finally {
        loading(false);
    }
}

// Save machine
async function saveMachine(e) {
    e.preventDefault();
    loading(true);
    
    const data = {
        machine_id: document.getElementById('machine_id').value,
        machine_name: document.getElementById('machine_name').value,
        machine_type: document.getElementById('machine_type').value,
        brand: document.getElementById('brand').value,
        model: document.getElementById('model').value,
        capacity: document.getElementById('capacity').value,
        location: document.getElementById('location').value,
        status: document.getElementById('status').value,
        purchase_date: document.getElementById('purchase_date').value || null
    };

    try {
        const res = await Ajax.post('<?= BASE_URL ?>api/machines.php', data);
        showAlert(res.message || 'Machine saved successfully', 'success');
        closeModal();
        loadMachines();
    } catch (error) {
        console.error('Error saving machine:', error);
        showAlert('Error saving machine', 'error');
    } finally {
        loading(false);
    }
}

// Delete machine
async function deleteMachine(id, name) {
    // Confirmation dialog
    const confirmed = confirm(`Are you sure you want to delete "${name}"?\n\nThis action cannot be undone.`);
    if (!confirmed) return;
    
    loading(true);
    try {
        const res = await Ajax.delete('<?= BASE_URL ?>api/machines.php', { machine_id: id });
        showAlert(res.message || 'Machine deleted successfully', 'success');
        loadMachines();
    } catch (error) {
        console.error('Error deleting machine:', error);
        showAlert('Error deleting machine', 'error');
    } finally {
        loading(false);
    }
}

// Load machines when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('Machines page loaded, loading machines...');
    loadMachines();
});
</script>

<?php
$content = ob_get_clean();
include '../../layout.php';
?>

