<?php
require_once '../../config.php';
if (!auth() || !in_array($_SESSION['position'], ['admin', 'manager'])) redirect('login.php');

$pageTitle = 'Manager Dashboard';
ob_start();
?>

<div class="bg-white rounded-lg shadow-sm p-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Manager Dashboard</h2>
    <p class="text-gray-600">Operations and management tools for managers.</p>
</div>

<?php
$content = ob_get_clean();
include '../../layout.php';
?>

