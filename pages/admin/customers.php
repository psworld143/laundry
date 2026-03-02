<?php
require_once '../../config.php';
if (!auth() || !in_array($_SESSION['position'], ['admin', 'manager', 'cashier'])) redirect('login.php');

$pageTitle = 'Customer Management';
ob_start();
?>

<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-gray-800">Customers</h2>
        <button onclick="openCustomerModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
            <i class="fas fa-plus mr-2"></i>Add Customer
        </button>
    </div>

    <div id="customersTable">
        <div class="text-center py-12">
            <i class="fas fa-spinner fa-spin text-4xl text-blue-500 mb-4"></i>
            <p class="text-gray-500">Loading customers...</p>
        </div>
    </div>
</div>

<!-- Customer Modal -->
<div id="customerModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-8 max-w-2xl w-full">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-gray-800" id="customerModalTitle">Add Customer</h3>
            <button onclick="closeCustomerModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>

        <form id="customerForm" onsubmit="saveCustomer(event)">
            <input type="hidden" id="user_id">
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Username *</label>
                    <input type="text" id="username" required class="w-full px-4 py-2 rounded-lg border focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Name *</label>
                    <input type="text" id="name" required class="w-full px-4 py-2 rounded-lg border focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Email *</label>
                    <input type="email" id="email" required class="w-full px-4 py-2 rounded-lg border focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-medium mb-2">Phone</label>
                    <input type="tel" id="phone_number" class="w-full px-4 py-2 rounded-lg border focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-medium mb-2">Preferred Fabrics</label>
                <div id="fabricSelection" class="border rounded-lg p-4 max-h-40 overflow-y-auto bg-gray-50">
                    <div class="text-center text-gray-500">
                        <i class="fas fa-spinner fa-spin"></i> Loading fabrics...
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-1">Select fabrics this customer frequently uses</p>
            </div>

            <div class="mb-6" id="passwordField">
                <label class="block text-gray-700 text-sm font-medium mb-2">Password * (default: password123)</label>
                <input type="password" id="password" placeholder="Leave blank to use default" class="w-full px-4 py-2 rounded-lg border focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none">
            </div>

            <div class="flex gap-4">
                <button type="button" onclick="closeCustomerModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-3 rounded-lg">Cancel</button>
                <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 rounded-lg">Save Customer</button>
            </div>
        </form>
    </div>
</div>

<script>
let customers = [];
let availableFabrics = [];
let selectedFabrics = new Set();

// Load customers from API
async function loadCustomers() {
    loading(true);
    try {
        const res = await Ajax.get('<?= BASE_URL ?>api/customers.php');
        console.log('Customers response:', res);
        
        if (res.success) {
            customers = res.data || [];
            console.log('Customers loaded:', customers.length);
            renderCustomers();
        } else {
            showAlert('Failed to load customers: ' + res.message, 'error');
        }
    } catch (error) {
        console.error('Error loading customers:', error);
        showAlert('Error loading customers', 'error');
        // Show empty table with error
        document.getElementById('customersTable').innerHTML = `
            <div class="text-center py-12 text-red-500">
                <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                <p>Error loading customers. Check console for details.</p>
            </div>
        `;
    } finally {
        loading(false);
    }
}

// Render customers table
function renderCustomers() {
    const html = `
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Preferred Fabrics</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                ${customers.length === 0 ? '<tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">No customers found</td></tr>' : ''}
                ${customers.map(c => `
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">${c.name}</td>
                        <td class="px-6 py-4">${c.username}</td>
                        <td class="px-6 py-4">${c.email}</td>
                        <td class="px-6 py-4">${c.phone_number || 'N/A'}</td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                ${(c.preferred_fabrics || []).map(fabric => 
                                    `<span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">${fabric}</span>`
                                ).join('')}
                                ${!c.preferred_fabrics || c.preferred_fabrics.length === 0 ? '<span class="text-gray-400 text-xs">None</span>' : ''}
                            </div>
                        </td>
                        <td class="px-6 py-4"><span class="px-2 py-1 ${c.is_active == 1 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'} rounded text-xs">${c.is_active == 1 ? 'Active' : 'Inactive'}</span></td>
                        <td class="px-6 py-4">
                            <button onclick="editCustomer(${c.user_id})" class="text-blue-500 hover:text-blue-700 mr-3">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteCustomer(${c.user_id})" class="text-red-500 hover:text-red-700">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
    document.getElementById('customersTable').innerHTML = html;
}

function openCustomerModal() {
    document.getElementById('customerForm').reset();
    document.getElementById('user_id').value = '';
    selectedFabrics.clear();
    updateFabricSelection();
    document.getElementById('customerModalTitle').textContent = 'Add Customer';
    document.getElementById('passwordField').style.display = 'block';
    modal('customerModal', true);
}

function closeCustomerModal() {
    modal('customerModal', false);
}

function editCustomer(id) {
    const c = customers.find(cust => cust.user_id == id);
    if (!c) return;
    
    document.getElementById('user_id').value = c.user_id;
    document.getElementById('username').value = c.username;
    document.getElementById('name').value = c.name;
    document.getElementById('email').value = c.email;
    document.getElementById('phone_number').value = c.phone_number || '';
    
    // Load customer's preferred fabrics
    selectedFabrics.clear();
    if (c.preferred_fabrics && Array.isArray(c.preferred_fabrics)) {
        c.preferred_fabrics.forEach(fabric => selectedFabrics.add(fabric));
    }
    updateFabricSelection();
    
    document.getElementById('customerModalTitle').textContent = 'Edit Customer';
    document.getElementById('passwordField').style.display = 'none';
    modal('customerModal', true);
}

async function saveCustomer(e) {
    e.preventDefault();
    loading(true);
    
    const data = {
        user_id: document.getElementById('user_id').value,
        username: document.getElementById('username').value,
        name: document.getElementById('name').value,
        email: document.getElementById('email').value,
        phone_number: document.getElementById('phone_number').value,
        password: document.getElementById('password').value,
        preferred_fabrics: Array.from(selectedFabrics)
    };

    try {
        const res = await Ajax.post('<?= BASE_URL ?>api/customers.php', data);
        showAlert(res.message, 'success');
        closeCustomerModal();
        loadCustomers();
    } catch (error) {
        showAlert('Error saving customer', 'error');
    } finally {
        loading(false);
    }
}

async function deleteCustomer(id) {
    if (!confirm('Delete this customer?')) return;
    
    loading(true);
    try {
        const res = await Ajax.delete('<?= BASE_URL ?>api/customers.php', { user_id: id });
        showAlert(res.message, 'success');
        loadCustomers();
    } catch (error) {
        showAlert('Error deleting customer', 'error');
    } finally {
        loading(false);
    }
}

// Load fabrics from API
async function loadFabrics() {
    try {
        const res = await Ajax.get('<?= BASE_URL ?>api/admin_fabrics.php');
        if (res.success) {
            availableFabrics = res.data || [];
            renderFabricSelection();
        }
    } catch (error) {
        console.error('Error loading fabrics:', error);
        document.getElementById('fabricSelection').innerHTML = 
            '<div class="text-center text-red-500">Error loading fabrics</div>';
    }
}

// Render fabric selection checkboxes
function renderFabricSelection() {
    const html = availableFabrics.map(fabric => `
        <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-100 p-1 rounded">
            <input type="checkbox" 
                   value="${fabric.fabric_name}" 
                   ${selectedFabrics.has(fabric.fabric_name) ? 'checked' : ''}
                   onchange="toggleFabric('${fabric.fabric_name}')"
                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <span class="text-sm">${fabric.fabric_name}</span>
            ${fabric.is_popular ? '<span class="text-xs bg-yellow-100 text-yellow-800 px-1 rounded">Popular</span>' : ''}
        </label>
    `).join('');
    
    document.getElementById('fabricSelection').innerHTML = html || '<div class="text-center text-gray-500">No fabrics available</div>';
}

// Toggle fabric selection
function toggleFabric(fabricName) {
    if (selectedFabrics.has(fabricName)) {
        selectedFabrics.delete(fabricName);
    } else {
        selectedFabrics.add(fabricName);
    }
}

// Update fabric selection display
function updateFabricSelection() {
    renderFabricSelection();
}

// Load customers when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded, loading customers and fabrics...');
    loadCustomers();
    loadFabrics();
});
</script>

<?php
$content = ob_get_clean();
include '../../layout.php';
?>

