<?php
/**
 * Public Services Page
 * Displays services to potential customers without requiring login
 */

require_once 'config.php';

// Get all active services
$stmt = $db->query("SELECT * FROM services WHERE is_active = 1 ORDER BY service_type, service_name");
$services = $stmt->fetchAll();

// Group services by type
$servicesByType = [];
foreach ($services as $service) {
    $servicesByType[$service['service_type']][] = $service;
}

$pageTitle = 'Our Services - LaundryPro';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <div class="bg-blue-500 w-10 h-10 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-tshirt text-white"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-800">LaundryPro</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-gray-600 hover:text-blue-600 transition">Home</a>
                    <a href="services.php" class="text-blue-600 font-semibold">Services</a>
                    <?php if (auth()): ?>
                    <a href="pages/customer/new-order.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-plus mr-2"></i>Create Order
                    </a>
                    <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                    </a>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                    <?php else: ?>
                    <a href="login.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="bg-gradient-to-br from-blue-500 via-purple-600 to-pink-600 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h1 class="text-5xl font-bold mb-6">Professional Laundry Services</h1>
            <p class="text-xl text-blue-100 mb-8">Quality cleaning solutions for your home and business</p>
            <div class="flex justify-center space-x-8 text-sm">
                <div class="flex items-center">
                    <i class="fas fa-clock mr-2"></i>
                    <span>Fast & Reliable</span>
                </div>
                <div class="flex items-center">
                    <i class="fas fa-truck mr-2"></i>
                    <span>Pickup & Delivery</span>
                </div>
                <div class="flex items-center">
                    <i class="fas fa-shield-alt mr-2"></i>
                    <span>Quality Guaranteed</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Services Section -->
    <div class="max-w-7xl mx-auto px-4 py-16">
        <?php if (empty($services)): ?>
        <!-- No Services Message -->
        <div class="text-center py-16">
            <div class="bg-gray-100 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-concierge-bell text-4xl text-gray-400"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-700 mb-4">Services Coming Soon</h3>
            <p class="text-gray-500 mb-8">We're currently setting up our service offerings. Please check back soon!</p>
            <a href="login.php" 
               class="inline-flex items-center bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold transition">
                <i class="fas fa-user mr-2"></i>Create Account
            </a>
        </div>
        <?php else: ?>
        
        <!-- Service Type Navigation -->
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold text-gray-800 mb-6">Our Service Offerings</h2>
            <p class="text-gray-600 text-lg">Choose from our range of professional laundry services</p>
        </div>

        <?php
        $typeInfo = [
            'wash_fold' => ['label' => 'Wash & Fold', 'icon' => 'fa-tshirt', 'emoji' => '🧺', 'color' => 'blue'],
            'dry_clean' => ['label' => 'Dry Cleaning', 'icon' => 'fa-wind', 'emoji' => '👔', 'color' => 'purple'],
            'ironing' => ['label' => 'Ironing', 'icon' => 'fa-fire', 'emoji' => '👕', 'color' => 'orange'],
            'express' => ['label' => 'Express', 'icon' => 'fa-bolt', 'emoji' => '⚡', 'color' => 'yellow'],
            'pickup_delivery' => ['label' => 'Pickup & Delivery', 'icon' => 'fa-truck', 'emoji' => '🚚', 'color' => 'green'],
        ];
        
        foreach ($servicesByType as $type => $typeServices): 
            $info = $typeInfo[$type] ?? ['label' => ucfirst($type), 'icon' => 'fa-tag', 'emoji' => '📦', 'color' => 'gray'];
        ?>
        <div class="mb-16">
            <!-- Section Header -->
            <div class="flex items-center mb-8">
                <div class="bg-gradient-to-r from-<?= $info['color'] ?>-500 to-<?= $info['color'] ?>-600 p-4 rounded-xl mr-4">
                    <i class="fas <?= $info['icon'] ?> text-white text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-3xl font-bold text-gray-800 flex items-center">
                        <span class="mr-3"><?= $info['emoji'] ?></span>
                        <?= $info['label'] ?> Services
                    </h3>
                    <p class="text-gray-600"><?= count($typeServices) ?> service<?= count($typeServices) > 1 ? 's' : '' ?> available</p>
                </div>
            </div>

            <!-- Services Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($typeServices as $service): ?>
                <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border-2 border-gray-100 hover:border-<?= $info['color'] ?>-300">
                    <!-- Service Header -->
                    <div class="text-center mb-6">
                        <div class="bg-gradient-to-br from-<?= $info['color'] ?>-100 to-<?= $info['color'] ?>-200 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas <?= $info['icon'] ?> text-<?= $info['color'] ?>-600 text-2xl"></i>
                        </div>
                        <h4 class="text-xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($service['service_name']) ?></h4>
                        <p class="text-gray-600 text-sm leading-relaxed"><?= htmlspecialchars($service['description'] ?: 'Professional laundry service') ?></p>
                    </div>

                    <!-- Pricing -->
                    <div class="text-center mb-6">
                        <div class="bg-gradient-to-r from-<?= $info['color'] ?>-500 to-<?= $info['color'] ?>-600 rounded-xl p-4 text-white">
                            <p class="text-sm opacity-90 mb-1">Starting from</p>
                            <p class="text-3xl font-bold">₱<?= number_format($service['base_price'], 2) ?></p>
                            <p class="text-xs opacity-90 mt-1">per service</p>
                        </div>
                    </div>

                    <!-- Service Details -->
                    <div class="space-y-3 mb-6">
                        <?php if ($service['estimated_duration']): ?>
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-clock text-<?= $info['color'] ?>-500 mr-3 w-4"></i>
                            <span>Estimated: <?= $service['estimated_duration'] ?> hours</span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-check-circle text-green-500 mr-3 w-4"></i>
                            <span>Quality guaranteed</span>
                        </div>
                        
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-shield-alt text-blue-500 mr-3 w-4"></i>
                            <span>Insured service</span>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <?php if (auth()): ?>
                    <a href="../pages/customer/new-order.php" 
                       class="block w-full bg-gradient-to-r from-<?= $info['color'] ?>-500 to-<?= $info['color'] ?>-600 hover:from-<?= $info['color'] ?>-600 hover:to-<?= $info['color'] ?>-700 text-white font-semibold py-3 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl text-center">
                        <i class="fas fa-plus mr-2"></i>Add to Order
                    </a>
                    <?php else: ?>
                    <a href="login.php" 
                       class="block w-full bg-gradient-to-r from-<?= $info['color'] ?>-500 to-<?= $info['color'] ?>-600 hover:from-<?= $info['color'] ?>-600 hover:to-<?= $info['color'] ?>-700 text-white font-semibold py-3 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl text-center">
                        <i class="fas fa-user-plus mr-2"></i>Login to Order
                    </a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Why Choose Us Section -->
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl p-12 mt-16">
            <h2 class="text-4xl font-bold text-gray-800 text-center mb-12">Why Choose LaundryPro?</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                <div class="text-center">
                    <div class="bg-blue-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-clock text-blue-600 text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Fast Service</h3>
                    <p class="text-gray-600 text-lg">Quick turnaround times without compromising quality. Most services completed within 24 hours.</p>
                </div>
                <div class="text-center">
                    <div class="bg-green-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-truck text-green-600 text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Convenient Pickup</h3>
                    <p class="text-gray-600 text-lg">We offer pickup and delivery services right to your doorstep. Schedule at your convenience.</p>
                </div>
                <div class="text-center">
                    <div class="bg-green-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-award text-green-600 text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Quality Guaranteed</h3>
                    <p class="text-gray-600 text-lg">Professional cleaning with satisfaction guarantee. We stand behind our work.</p>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="text-center mt-16">
            <h2 class="text-3xl font-bold text-gray-800 mb-6">Ready to Get Started?</h2>
            <p class="text-gray-600 text-lg mb-8">Join thousands of satisfied customers who trust LaundryPro with their laundry needs.</p>
            <div class="flex justify-center space-x-4">
                <?php if (auth()): ?>
                <a href="pages/customer/new-order.php" 
                   class="bg-green-500 hover:bg-green-600 text-white px-8 py-4 rounded-xl font-semibold text-lg transition transform hover:scale-105 shadow-lg hover:shadow-xl">
                    <i class="fas fa-plus mr-2"></i>Create Order
                </a>
                <a href="pages/customer/my-orders.php" 
                   class="bg-blue-500 hover:bg-blue-600 text-white px-8 py-4 rounded-xl font-semibold text-lg transition transform hover:scale-105 shadow-lg hover:shadow-xl">
                    <i class="fas fa-list mr-2"></i>My Orders
                </a>
                <?php else: ?>
                <a href="login.php" 
                   class="bg-blue-500 hover:bg-blue-600 text-white px-8 py-4 rounded-xl font-semibold text-lg transition transform hover:scale-105 shadow-lg hover:shadow-xl">
                    <i class="fas fa-user-plus mr-2"></i>Create Account
                </a>
                <a href="login.php" 
                   class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-8 py-4 rounded-xl font-semibold text-lg transition transform hover:scale-105 shadow-lg hover:shadow-xl">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <div class="flex items-center justify-center mb-6">
                <div class="bg-blue-500 w-12 h-12 rounded-full flex items-center justify-center mr-3">
                    <i class="fas fa-tshirt text-white text-xl"></i>
                </div>
                <h3 class="text-2xl font-bold">LaundryPro</h3>
            </div>
            <p class="text-gray-400 mb-6">Professional laundry services for your home and business</p>
            <div class="flex justify-center space-x-6 text-sm">
                <a href="index.php" class="text-gray-400 hover:text-white transition">Home</a>
                <a href="services.php" class="text-gray-400 hover:text-white transition">Services</a>
                <a href="login.php" class="text-gray-400 hover:text-white transition">Login</a>
            </div>
            <p class="text-gray-500 text-sm mt-6">&copy; 2024 LaundryPro. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
