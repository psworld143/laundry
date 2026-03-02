<?php
require_once '../../config.php';
if (!auth()) redirect('login.php');

// Get all active services
$stmt = $db->query("SELECT * FROM services WHERE is_active = 1 ORDER BY service_type, service_name");
$services = $stmt->fetchAll();

// Group services by type
$servicesByType = [];
foreach ($services as $service) {
    $servicesByType[$service['service_type']][] = $service;
}

$pageTitle = 'Our Services';
ob_start();
?>

<!-- Hero Section -->
<div class="bg-gradient-to-br from-blue-500 via-purple-600 to-pink-600 rounded-2xl shadow-2xl p-8 mb-8 text-white relative overflow-hidden">
    <div class="relative z-10">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-4xl font-bold mb-3">Our Laundry Services</h1>
                <p class="text-blue-100 text-lg mb-4">Professional laundry solutions tailored to your needs</p>
                <div class="flex items-center space-x-6 text-sm">
                    <div class="flex items-center">
                        <i class="fas fa-clock mr-2"></i>
                        <span>Fast & Reliable</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-shield-alt mr-2"></i>
                        <span>Quality Guaranteed</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-truck mr-2"></i>
                        <span>Pickup & Delivery</span>
                    </div>
                </div>
            </div>
            <div class="hidden lg:block">
                <i class="fas fa-tshirt text-8xl opacity-20"></i>
            </div>
        </div>
    </div>
    <!-- Background decoration -->
    <div class="absolute top-0 right-0 w-64 h-64 bg-white opacity-5 rounded-full -translate-y-32 translate-x-32"></div>
    <div class="absolute bottom-0 left-0 w-48 h-48 bg-white opacity-5 rounded-full translate-y-24 -translate-x-24"></div>
</div>

<!-- Service Type Navigation -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Choose Your Service</h2>
    <div class="flex flex-wrap justify-center gap-4">
        <?php
        $typeInfo = [
            'wash_fold' => ['label' => 'Wash & Fold', 'icon' => 'fa-tshirt', 'emoji' => '🧺', 'color' => 'blue'],
            'dry_clean' => ['label' => 'Dry Cleaning', 'icon' => 'fa-wind', 'emoji' => '👔', 'color' => 'purple'],
            'ironing' => ['label' => 'Ironing', 'icon' => 'fa-fire', 'emoji' => '👕', 'color' => 'orange'],
            'express' => ['label' => 'Express', 'icon' => 'fa-bolt', 'emoji' => '⚡', 'color' => 'yellow'],
            'pickup_delivery' => ['label' => 'Pickup & Delivery', 'icon' => 'fa-truck', 'emoji' => '🚚', 'color' => 'green']
        ];
        
        foreach ($typeInfo as $type => $info):
            if (isset($servicesByType[$type])):
        ?>
        <button onclick="scrollToSection('<?= $type ?>')" 
                class="group flex items-center bg-gradient-to-r from-<?= $info['color'] ?>-50 to-<?= $info['color'] ?>-100 hover:from-<?= $info['color'] ?>-500 hover:to-<?= $info['color'] ?>-600 text-<?= $info['color'] ?>-700 hover:text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
            <span class="text-2xl mr-3"><?= $info['emoji'] ?></span>
            <span><?= $info['label'] ?></span>
            <span class="ml-2 bg-white bg-opacity-30 px-2 py-1 rounded-full text-xs"><?= count($servicesByType[$type]) ?></span>
        </button>
        <?php 
            endif;
        endforeach; 
        ?>
    </div>
</div>

<!-- Services by Type -->
<?php foreach ($servicesByType as $type => $typeServices): 
    $info = $typeInfo[$type] ?? ['label' => ucfirst($type), 'icon' => 'fa-tag', 'emoji' => '📦', 'color' => 'gray'];
?>
<div id="<?= $type ?>" class="mb-12">
    <!-- Section Header -->
    <div class="flex items-center mb-6">
        <div class="bg-gradient-to-r from-<?= $info['color'] ?>-500 to-<?= $info['color'] ?>-600 p-4 rounded-xl mr-4">
            <i class="fas <?= $info['icon'] ?> text-white text-2xl"></i>
        </div>
        <div>
            <h2 class="text-3xl font-bold text-gray-800 flex items-center">
                <span class="mr-3"><?= $info['emoji'] ?></span>
                <?= $info['label'] ?> Services
            </h2>
            <p class="text-gray-600"><?= count($typeServices) ?> service<?= count($typeServices) > 1 ? 's' : '' ?> available</p>
        </div>
    </div>

    <!-- Services Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($typeServices as $service): ?>
        <div class="group bg-white border-2 border-gray-200 rounded-2xl p-6 hover:border-<?= $info['color'] ?>-400 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <!-- Service Header -->
            <div class="text-center mb-6">
                <div class="bg-gradient-to-br from-<?= $info['color'] ?>-100 to-<?= $info['color'] ?>-200 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform duration-300">
                    <i class="fas <?= $info['icon'] ?> text-<?= $info['color'] ?>-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($service['service_name']) ?></h3>
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
            <a href="<?= BASE_URL ?>pages/customer/new-order.php?service=<?= $service['service_id'] ?>" 
               class="block w-full bg-gradient-to-r from-<?= $info['color'] ?>-500 to-<?= $info['color'] ?>-600 hover:from-<?= $info['color'] ?>-600 hover:to-<?= $info['color'] ?>-700 text-white font-semibold py-3 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl text-center">
                <i class="fas fa-shopping-cart mr-2"></i>Order This Service
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<!-- No Services Message -->
<?php if (empty($services)): ?>
<div class="text-center py-16">
    <div class="bg-gray-100 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
        <i class="fas fa-concierge-bell text-4xl text-gray-400"></i>
    </div>
    <h3 class="text-2xl font-bold text-gray-700 mb-4">No Services Available</h3>
    <p class="text-gray-500 mb-8">We're currently updating our service offerings. Please check back soon!</p>
    <a href="<?= BASE_URL ?>pages/customer/dashboard.php" 
       class="inline-flex items-center bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold transition">
        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
    </a>
</div>
<?php endif; ?>

<!-- Why Choose Us Section -->
<?php if (!empty($services)): ?>
<div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl p-8 mt-12">
    <h2 class="text-3xl font-bold text-gray-800 text-center mb-8">Why Choose LaundryPro?</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="text-center">
            <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-clock text-blue-600 text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Fast Service</h3>
            <p class="text-gray-600">Quick turnaround times without compromising quality</p>
        </div>
        <div class="text-center">
            <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-award text-green-600 text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Quality Guaranteed</h3>
            <p class="text-gray-600">Professional cleaning with satisfaction guarantee</p>
        </div>
        <div class="text-center">
            <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-truck text-purple-600 text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Convenient Pickup</h3>
            <p class="text-gray-600">Free pickup and delivery service available</p>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Smooth scroll to service sections
function scrollToSection(sectionId) {
    const element = document.getElementById(sectionId);
    if (element) {
        element.scrollIntoView({ 
            behavior: 'smooth',
            block: 'start'
        });
    }
}

// Add scroll spy effect
window.addEventListener('scroll', function() {
    const sections = document.querySelectorAll('[id]');
    const navButtons = document.querySelectorAll('button[onclick^="scrollToSection"]');
    
    let current = '';
    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.clientHeight;
        if (scrollY >= (sectionTop - 200)) {
            current = section.getAttribute('id');
        }
    });

    navButtons.forEach(button => {
        button.classList.remove('ring-2', 'ring-blue-500');
        if (button.getAttribute('onclick').includes(current)) {
            button.classList.add('ring-2', 'ring-blue-500');
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include '../../layout.php';
?>
