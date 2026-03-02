<?php
require_once '../../config.php';
if (!auth() || !in_array($_SESSION['position'], ['admin'])) redirect('login.php');

$pageTitle = 'User Credentials';
ob_start();

// Get all users
$stmt = $db->query("
    SELECT user_id, username, name, email, phone_number, position, is_active, created_at 
    FROM users 
    ORDER BY position DESC, name ASC
");
$users = $stmt->fetchAll();
?>

<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">User Credentials</h2>
            <p class="text-gray-600 text-sm mt-1">Username and email information for all users</p>
        </div>
        <button onclick="exportCredentials()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
            <i class="fas fa-download mr-2"></i>Export CSV
        </button>
    </div>

    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
        <p class="text-sm text-yellow-800">
            <i class="fas fa-shield-alt mr-2"></i>
            <strong>Security Note:</strong> Passwords are securely hashed and cannot be displayed. 
            Use the reset button to set a new password for any user.
        </p>
    </div>

    <div class="mb-4">
        <input type="text" id="searchUsers" placeholder="Search by username, name, or email..." 
               class="w-full max-w-md px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">User ID</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Username</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Full Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Phone</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Position</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200" id="usersTableBody">
                <?php foreach ($users as $user): ?>
                <tr class="hover:bg-gray-50" data-username="<?php echo htmlspecialchars(strtolower($user['username'])); ?>" 
                    data-name="<?php echo htmlspecialchars(strtolower($user['name'])); ?>" 
                    data-email="<?php echo htmlspecialchars(strtolower($user['email'])); ?>">
                    <td class="px-4 py-3 text-sm font-mono">#<?php echo $user['user_id']; ?></td>
                    <td class="px-4 py-3">
                        <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($user['username']); ?></span>
                    </td>
                    <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($user['name']); ?></td>
                    <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars($user['email']); ?></td>
                    <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars($user['phone_number'] ?: 'N/A'); ?></td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded text-xs font-semibold <?php 
                            echo $user['position'] === 'admin' ? 'bg-purple-100 text-purple-800' : 
                                ($user['position'] === 'cashier' ? 'bg-blue-100 text-blue-800' : 
                                ($user['position'] === 'operator' ? 'bg-green-100 text-green-800' : 
                                'bg-gray-100 text-gray-800')); 
                        ?>">
                            <?php echo ucfirst($user['position']); ?>
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded text-xs font-semibold <?php echo $user['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <button onclick="resetPassword(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?>')" 
                                class="text-blue-600 hover:text-blue-800 text-sm mr-3" title="Reset Password">
                            <i class="fas fa-key mr-1"></i>Reset Password
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">Reset Password</h3>
            <button onclick="closeResetModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="resetPasswordForm" onsubmit="confirmResetPassword(event)">
            <input type="hidden" id="reset_user_id">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2">Username</label>
                <input type="text" id="reset_username" readonly class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2">New Password</label>
                <input type="password" id="reset_password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">Leave blank to use default: password123</p>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeResetModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 py-2 rounded-lg">Cancel</button>
                <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white py-2 rounded-lg">Reset Password</button>
            </div>
        </form>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchUsers').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#usersTableBody tr');
    
    rows.forEach(row => {
        const username = row.dataset.username || '';
        const name = row.dataset.name || '';
        const email = row.dataset.email || '';
        
        if (username.includes(searchTerm) || name.includes(searchTerm) || email.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Reset password
function resetPassword(userId, username) {
    document.getElementById('reset_user_id').value = userId;
    document.getElementById('reset_username').value = username;
    document.getElementById('reset_password').value = '';
    document.getElementById('resetPasswordModal').classList.remove('hidden');
}

function closeResetModal() {
    document.getElementById('resetPasswordModal').classList.add('hidden');
    document.getElementById('resetPasswordForm').reset();
}

async function confirmResetPassword(e) {
    e.preventDefault();
    const userId = document.getElementById('reset_user_id').value;
    const password = document.getElementById('reset_password').value || 'password123';
    
    try {
        const response = await fetch('../../api/customers.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: userId,
                password: password
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Password reset successfully!');
            closeResetModal();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error resetting password:', error);
        alert('Error resetting password');
    }
}

// Export to CSV
function exportCredentials() {
    const rows = document.querySelectorAll('#usersTableBody tr:not([style*="display: none"])');
    let csv = 'User ID,Username,Name,Email,Phone,Position,Status\n';
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 7) {
            csv += [
                cells[0].textContent.trim().replace('#', ''),
                cells[1].textContent.trim(),
                cells[2].textContent.trim(),
                cells[3].textContent.trim(),
                cells[4].textContent.trim(),
                cells[5].textContent.trim(),
                cells[6].textContent.trim()
            ].map(cell => `"${cell}"`).join(',') + '\n';
        }
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'user-credentials-' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}
</script>

<?php
$content = ob_get_clean();
include '../../layout.php';
?>

