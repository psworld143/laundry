<?php
require_once '../../config.php';
if (!auth() || !in_array($_SESSION['position'], ['admin', 'manager'])) redirect('login.php');

// Get staff statistics
$staffStats = [
    'total' => $db->query("SELECT COUNT(*) FROM staff")->fetchColumn(),
    'active' => $db->query("SELECT COUNT(*) FROM staff WHERE is_active = 1")->fetchColumn(),
    'managers' => $db->query("SELECT COUNT(*) FROM staff WHERE position = 'manager'")->fetchColumn(),
    'operators' => $db->query("SELECT COUNT(*) FROM staff WHERE position = 'operator'")->fetchColumn(),
    'drivers' => $db->query("SELECT COUNT(*) FROM staff WHERE position = 'driver'")->fetchColumn(),
    'cashiers' => $db->query("SELECT COUNT(*) FROM staff WHERE position = 'cashier'")->fetchColumn(),
];

$pageTitle = 'Staff Management';
ob_start();
?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-users text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">Total</span>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= $staffStats['total'] ?></h3>
        <p class="text-blue-100 text-sm">Total Staff</p>
    </div>

    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-user-check text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">Active</span>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= $staffStats['active'] ?></h3>
        <p class="text-green-100 text-sm">Active Staff</p>
    </div>

    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-user-tie text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">Management</span>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= $staffStats['managers'] ?></h3>
        <p class="text-purple-100 text-sm">Managers</p>
    </div>

    <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                <i class="fas fa-cog text-2xl"></i>
            </div>
            <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full">Operations</span>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?= $staffStats['operators'] ?></h3>
        <p class="text-orange-100 text-sm">Operators</p>
    </div>
</div>

<!-- Staff Table -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Staff Members</h2>
            <p class="text-gray-500 text-sm mt-1">Manage your team members and their information</p>
        </div>
        <button onclick="openStaffModal()" class="bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white px-6 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transition transform hover:scale-105">
            <i class="fas fa-plus mr-2"></i>Add Staff Member
        </button>
    </div>

    <div id="staffTable">
        <div class="text-center py-12">
            <i class="fas fa-spinner fa-spin text-4xl text-blue-500 mb-4"></i>
            <p class="text-gray-500">Loading staff members...</p>
        </div>
    </div>
</div>

<!-- Staff Modal -->
<div id="staffModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-8 max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent" id="staffModalTitle">Add Staff Member</h3>
            <button onclick="closeStaffModal()" class="text-gray-400 hover:text-gray-600 transition">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>

        <form id="staffForm" onsubmit="saveStaff(event)">
            <input type="hidden" id="staff_id">
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-user text-blue-500 mr-1"></i>Full Name *
                    </label>
                    <input type="text" id="name" required 
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                           placeholder="Enter full name">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-briefcase text-purple-500 mr-1"></i>Position *
                    </label>
                    <select id="position" required 
                            class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition">
                        <option value="">Select Position</option>
                        <option value="manager">👔 Manager</option>
                        <option value="operator">⚙️ Operator</option>
                        <option value="driver">🚚 Driver</option>
                        <option value="cashier">💰 Cashier</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-envelope text-green-500 mr-1"></i>Email Address
                    </label>
                    <input type="email" id="email" 
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                           placeholder="email@example.com">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-phone text-yellow-500 mr-1"></i>Contact Number
                    </label>
                    <input type="tel" id="contact_number" 
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                           placeholder="+63 XXX XXX XXXX">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-calendar text-red-500 mr-1"></i>Hire Date *
                    </label>
                    <input type="date" id="hire_date" required 
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-dollar-sign text-green-500 mr-1"></i>Monthly Salary (₱)
                    </label>
                    <input type="number" id="salary" step="0.01" 
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                           placeholder="Enter salary amount">
                </div>
            </div>

            <div class="flex gap-4">
                <button type="button" onclick="closeStaffModal()" 
                        class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 rounded-lg transition">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <button type="submit" 
                        class="flex-1 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-semibold py-3 rounded-lg transition shadow-lg">
                    <i class="fas fa-save mr-2"></i>Save Staff Member
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let staff = [];

// Load staff from API
async function loadStaff() {
    loading(true);
    try {
        const res = await Ajax.get('<?= BASE_URL ?>api/staff.php');
        console.log('Staff response:', res);
        
        if (res.success) {
            staff = res.data || [];
            console.log('Staff loaded:', staff.length);
            renderStaff();
        } else {
            showAlert('Failed to load staff: ' + res.message, 'error');
            renderEmptyState();
        }
    } catch (error) {
        console.error('Error loading staff:', error);
        showAlert('Error loading staff. Check console for details.', 'error');
        renderEmptyState();
    } finally {
        loading(false);
    }
}

// Render empty state
function renderEmptyState() {
    document.getElementById('staffTable').innerHTML = `
        <div class="text-center py-16">
            <div class="bg-gray-100 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-user-tie text-4xl text-gray-400"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">No Staff Members Yet</h3>
            <p class="text-gray-500 mb-6">Add your first staff member to get started</p>
            <button onclick="openStaffModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold transition">
                <i class="fas fa-plus mr-2"></i>Add Staff Member
            </button>
        </div>
    `;
}

// Render staff table
function renderStaff() {
    const positions = {
        manager: { label: 'Manager', icon: 'fa-user-tie', color: 'purple' },
        operator: { label: 'Operator', icon: 'fa-cog', color: 'blue' },
        driver: { label: 'Driver', icon: 'fa-truck', color: 'green' },
        cashier: { label: 'Cashier', icon: 'fa-cash-register', color: 'orange' }
    };
    
    if (staff.length === 0) {
        renderEmptyState();
        return;
    }
    
    const html = `
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr class="border-b-2 border-gray-200">
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Staff Member</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Position</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Contact Info</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Hire Date</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Salary</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    ${staff.map(s => {
                        const pos = positions[s.position] || { label: s.position, icon: 'fa-user', color: 'gray' };
                        return `
                        <tr class="hover:bg-blue-50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="bg-gradient-to-br from-blue-500 to-purple-600 w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                        ${s.name.charAt(0).toUpperCase()}
                                    </div>
                                    <div class="ml-4">
                                        <p class="font-semibold text-gray-800">${s.name}</p>
                                        <p class="text-xs text-gray-500">ID: ${s.staff_id}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-3 py-2 rounded-lg bg-${pos.color}-100 text-${pos.color}-800 text-sm font-semibold">
                                    <i class="fas ${pos.icon} mr-2"></i>${pos.label}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    ${s.email ? `<p class="text-gray-800"><i class="fas fa-envelope text-gray-400 mr-2"></i>${s.email}</p>` : ''}
                                    ${s.contact_number ? `<p class="text-gray-600 mt-1"><i class="fas fa-phone text-gray-400 mr-2"></i>${s.contact_number}</p>` : ''}
                                    ${!s.email && !s.contact_number ? '<p class="text-gray-400">No contact info</p>' : ''}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700">
                                    <i class="far fa-calendar text-gray-400 mr-2"></i>${new Date(s.hire_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-bold text-gray-800">
                                    ${s.salary ? '₱' + parseFloat(s.salary).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '<span class="text-gray-400">Not set</span>'}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold ${s.is_active == 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                    <i class="fas fa-circle text-xs mr-1"></i>${s.is_active == 1 ? 'Active' : 'Inactive'}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex gap-2">
                                    <button onclick="editStaff(${s.staff_id})" 
                                            class="bg-blue-100 hover:bg-blue-200 text-blue-600 p-2 rounded-lg transition" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="toggleStaffStatus(${s.staff_id}, ${s.is_active == 1 ? 0 : 1})" 
                                            class="bg-yellow-100 hover:bg-yellow-200 text-yellow-600 p-2 rounded-lg transition" title="${s.is_active == 1 ? 'Deactivate' : 'Activate'}">
                                        <i class="fas fa-${s.is_active == 1 ? 'pause' : 'play'}"></i>
                                    </button>
                                    <button onclick="deleteStaff(${s.staff_id}, '${s.name}')" 
                                            class="bg-red-100 hover:bg-red-200 text-red-600 p-2 rounded-lg transition" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `}).join('')}
                </tbody>
            </table>
        </div>
    `;
    document.getElementById('staffTable').innerHTML = html;
}

// Open modal for new staff
function openStaffModal() {
    document.getElementById('staffForm').reset();
    document.getElementById('staff_id').value = '';
    document.getElementById('staffModalTitle').textContent = 'Add Staff Member';
    // Set default hire date to today
    document.getElementById('hire_date').value = new Date().toISOString().split('T')[0];
    modal('staffModal', true);
}

// Close modal
function closeStaffModal() {
    modal('staffModal', false);
}

// Edit staff
function editStaff(id) {
    const s = staff.find(st => st.staff_id == id);
    if (!s) {
        showAlert('Staff member not found', 'error');
        return;
    }
    
    document.getElementById('staff_id').value = s.staff_id;
    document.getElementById('name').value = s.name;
    document.getElementById('position').value = s.position;
    document.getElementById('email').value = s.email || '';
    document.getElementById('contact_number').value = s.contact_number || '';
    document.getElementById('hire_date').value = s.hire_date;
    document.getElementById('salary').value = s.salary || '';
    document.getElementById('staffModalTitle').textContent = 'Edit Staff Member';
    modal('staffModal', true);
}

// Toggle staff status
async function toggleStaffStatus(id, newStatus) {
    loading(true);
    try {
        const res = await Ajax.post('<?= BASE_URL ?>api/staff.php', {
            staff_id: id,
            is_active: newStatus
        });
        showAlert(res.message, 'success');
        loadStaff();
    } catch (error) {
        console.error('Error toggling staff status:', error);
        showAlert('Error updating staff status', 'error');
    } finally {
        loading(false);
    }
}

async function saveStaff(e) {
    e.preventDefault();
    loading(true);
    
    const data = {
        staff_id: document.getElementById('staff_id').value,
        name: document.getElementById('name').value,
        position: document.getElementById('position').value,
        email: document.getElementById('email').value,
        contact_number: document.getElementById('contact_number').value,
        hire_date: document.getElementById('hire_date').value,
        salary: document.getElementById('salary').value,
    };

    try {
        const res = await Ajax.post('<?= BASE_URL ?>api/staff.php', data);
        showAlert(res.message, 'success');
        closeStaffModal();
        loadStaff();
    } catch (error) {
        showAlert('Error saving staff', 'error');
    } finally {
        loading(false);
    }
}

async function deleteStaff(id, name) {
    // Confirmation dialog
    const confirmed = confirm(`Are you sure you want to delete ${name}?\n\nThis action cannot be undone.`);
    if (!confirmed) return;
    
    loading(true);
    try {
        const res = await Ajax.delete('<?= BASE_URL ?>api/staff.php', { staff_id: id });
        showAlert(res.message || 'Staff member deleted successfully', 'success');
        loadStaff();
    } catch (error) {
        console.error('Error deleting staff:', error);
        showAlert('Error deleting staff member', 'error');
    } finally {
        loading(false);
    }
}

// Load staff when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('Staff page loaded, loading staff members...');
    loadStaff();
});
</script>

<?php
$content = ob_get_clean();
include '../../layout.php';
?>

