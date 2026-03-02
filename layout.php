<?php
if (!auth()) redirect('login.php');
$user = user();
$role = $user['position'] ?? 'user';

// Role names
$roles = [
    'admin' => 'Administrator',
    'manager' => 'Manager',
    'operator' => 'Operator',
    'driver' => 'Driver',
    'cashier' => 'Cashier',
    'user' => 'Customer'
];

// Navigation based on role
$nav = [
    'admin' => [
        ['Dashboard', 'fas fa-home', 'pages/admin/dashboard.php'],
        ['Customers', 'fas fa-users', 'pages/admin/customers.php'],
        ['Staff', 'fas fa-user-tie', 'pages/admin/staff.php'],
        ['Services', 'fas fa-concierge-bell', 'pages/admin/services.php'],
        ['Machines', 'fas fa-cogs', 'pages/admin/machines.php'],
        ['Inventory', 'fas fa-boxes', 'pages/admin/inventory.php'],
        ['Orders', 'fas fa-shopping-cart', 'pages/admin/orders.php'],
    ],
    'manager' => [
        ['Dashboard', 'fas fa-home', 'pages/manager/dashboard.php'],
        ['Staff', 'fas fa-user-tie', 'pages/admin/staff.php'],
        ['Orders', 'fas fa-shopping-cart', 'pages/admin/orders.php'],
        ['Inventory', 'fas fa-boxes', 'pages/admin/inventory.php'],
    ],
    'operator' => [
        ['Dashboard', 'fas fa-home', 'pages/operator/dashboard.php'],
        ['Orders', 'fas fa-shopping-cart', 'pages/operator/orders.php'],
        ['Machines', 'fas fa-cogs', 'pages/operator/machines.php'],
    ],
    'driver' => [
        ['Dashboard', 'fas fa-home', 'pages/driver/dashboard.php'],
        ['Deliveries', 'fas fa-truck', 'pages/driver/deliveries.php'],
    ],
    'cashier' => [
        ['Dashboard', 'fas fa-home', 'pages/cashier/dashboard.php'],
        ['New Order', 'fas fa-plus', 'pages/cashier/new-order.php'],
        ['Orders', 'fas fa-shopping-cart', 'pages/cashier/orders.php'],
        ['Copy Receipt', 'fas fa-receipt', 'pages/receipt-viewer.php'],
    ],
    'user' => [
        ['Dashboard', 'fas fa-home', 'pages/customer/dashboard.php'],
        ['New Order', 'fas fa-plus', 'pages/customer/new-order.php'],
        ['My Orders', 'fas fa-list', 'pages/customer/orders.php'],
    ]
];

$menu = $nav[$role] ?? $nav['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
        @keyframes slideIn {
            from { transform: translateX(-100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .sidebar-item {
            animation: slideIn 0.3s ease-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        /* Mobile Sidebar Transitions */
        .sidebar {
            transition: transform 0.3s ease-in-out;
        }
        .sidebar-hidden {
            transform: translateX(-100%);
        }
        /* Overlay */
        .sidebar-overlay {
            transition: opacity 0.3s ease-in-out;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <!-- Mobile Overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <div id="sidebar" class="sidebar fixed inset-y-0 left-0 w-64 bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900 text-white shadow-2xl z-50 sidebar-hidden lg:translate-x-0">
        <!-- Logo -->
        <div class="flex items-center justify-between h-20 bg-gradient-to-r from-blue-600 to-purple-600 shadow-lg px-4">
            <div class="flex items-center">
                <div class="bg-white bg-opacity-20 p-2 rounded-lg mr-3">
                    <i class="fas fa-tshirt text-2xl"></i>
                </div>
                <div>
                    <span class="text-xl font-bold block"><?= APP_NAME ?></span>
                    <span class="text-xs text-blue-200"><?= $roles[$role] ?></span>
                </div>
            </div>
            <!-- Close Button (Mobile Only) -->
            <button onclick="toggleSidebar()" class="lg:hidden p-2 hover:bg-white hover:bg-opacity-10 rounded-lg transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <!-- User Profile Card -->
        <div class="px-4 py-6">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl p-4 shadow-lg">
                <div class="flex items-center">
                    <div class="bg-white w-12 h-12 rounded-full flex items-center justify-center text-blue-600 font-bold text-xl shadow-md">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="font-semibold text-white text-sm truncate"><?= clean($user['name']) ?></p>
                        <p class="text-blue-200 text-xs"><?= clean($user['email']) ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="px-4 space-y-2">
            <?php $index = 0; foreach ($menu as [$label, $icon, $url]): $index++; ?>
            <a href="<?= BASE_URL . $url ?>" 
               style="animation-delay: <?= $index * 0.05 ?>s"
               class="sidebar-item group flex items-center px-4 py-3 rounded-lg hover:bg-gradient-to-r hover:from-blue-600 hover:to-purple-600 transition-all duration-200 <?= strpos($_SERVER['PHP_SELF'], $url) !== false ? 'bg-gradient-to-r from-blue-600 to-purple-600 shadow-lg' : '' ?>">
                <div class="bg-white bg-opacity-10 p-2 rounded-lg group-hover:bg-opacity-20 transition">
                    <i class="<?= $icon ?> w-4 text-center"></i>
                </div>
                <span class="ml-3 font-medium"><?= $label ?></span>
            </a>
            <?php endforeach; ?>
        </nav>
        
        <!-- Logout Button -->
        <div class="absolute bottom-0 left-0 right-0 p-4">
            <a href="<?= BASE_URL ?>logout.php" 
               class="flex items-center px-4 py-3 rounded-lg bg-red-600 hover:bg-red-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
                <i class="fas fa-sign-out-alt w-6"></i>
                <span class="ml-3 font-semibold">Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="lg:ml-64 p-4 md:p-6">
        <!-- Top Bar -->
        <div class="bg-white rounded-2xl shadow-lg p-4 md:p-6 mb-6 fade-in">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <!-- Hamburger Menu Button (Mobile Only) -->
                    <button onclick="toggleSidebar()" class="lg:hidden p-2 rounded-lg hover:bg-gray-100 transition">
                        <i class="fas fa-bars text-gray-600 text-xl"></i>
                    </button>
                    
                    <div>
                        <h1 class="text-xl md:text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                            <?= $pageTitle ?? 'Dashboard' ?>
                        </h1>
                        <p class="text-xs md:text-sm text-gray-500 mt-1">
                            <i class="far fa-calendar mr-1 md:mr-2"></i><?= date('l, F j, Y') ?>
                            <span class="mx-1 md:mx-2">•</span>
                            <i class="far fa-clock mr-1 md:mr-2"></i><?= date('h:i A') ?>
                        </p>
                    </div>
                </div>
                <div class="flex items-center space-x-2 md:space-x-4">
                    <!-- User Info -->
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-semibold text-gray-800"><?= clean($user['name']) ?></p>
                        <p class="text-xs text-gray-500"><?= $roles[$role] ?></p>
                    </div>
                    <!-- Mobile User Avatar -->
                    <div class="sm:hidden bg-gradient-to-r from-blue-600 to-purple-600 w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm shadow-lg">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <div id="page-content" class="fade-in">
            <?php echo $content ?? ''; ?>
        </div>
    </div>

    <!-- AJAX Library -->
    <script src="<?= BASE_URL ?>assets/app.js"></script>
    
    <!-- Sidebar Toggle Script -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            sidebar.classList.toggle('sidebar-hidden');
            
            if (sidebar.classList.contains('sidebar-hidden')) {
                overlay.classList.add('hidden');
            } else {
                overlay.classList.remove('hidden');
            }
        }
        
        // Close sidebar when clicking on a navigation link (mobile only)
        document.addEventListener('DOMContentLoaded', function() {
            if (window.innerWidth < 1024) {
                const navLinks = document.querySelectorAll('#sidebar a');
                navLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        // Only close sidebar on mobile, not on logout link
                        if (!this.href.includes('logout.php')) {
                            setTimeout(() => {
                                toggleSidebar();
                            }, 200);
                        }
                    });
                });
            }
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (window.innerWidth >= 1024) {
                // Desktop view - ensure sidebar is visible
                sidebar.classList.remove('sidebar-hidden');
                overlay.classList.add('hidden');
            } else {
                // Mobile view - ensure sidebar is hidden by default
                sidebar.classList.add('sidebar-hidden');
                overlay.classList.add('hidden');
            }
        });
    </script>
</body>
</html>

