<?php
require_once '../../config.php';

// Redirect if already logged in
if (auth()) {
    redirect('pages/' . $_SESSION['position'] . '/dashboard.php');
}

$pageTitle = 'Driver Login';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            // Check if user exists and is a driver
            $stmt = $db->prepare("
                SELECT u.*, s.name as staff_name, s.position 
                FROM users u
                LEFT JOIN staff s ON u.name = s.name
                WHERE u.username = ? AND u.is_active = 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Check if user is driver or admin
                if (in_array($user['position'], ['driver', 'admin']) || $user['position'] === 'admin') {
                    // Set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['position'] = $user['position'];
                    $_SESSION['phone_number'] = $user['phone_number'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['login_time'] = time();
                    
                    // Log login activity
                    $stmt = $db->prepare("
                        INSERT INTO user_logs (user_id, action, details, ip_address, user_agent) 
                        VALUES (?, 'login', 'Driver login successful', ?, ?)
                    ");
                    $stmt->execute([
                        $user['user_id'],
                        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);
                    
                    // Redirect to appropriate dashboard
                    redirect('pages/driver/dashboard.php');
                } else {
                    $error = 'Access denied. Driver access required.';
                }
            } else {
                $error = 'Invalid username or password';
                
                // Log failed login attempt
                if ($user) {
                    $stmt = $db->prepare("
                        INSERT INTO user_logs (user_id, action, details, ip_address, user_agent) 
                        VALUES (?, 'login_failed', 'Invalid password', ?, ?)
                    ");
                    $stmt->execute([
                        $user['user_id'],
                        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);
                }
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'Login failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Driver Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .login-bg {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .driver-animation {
            animation: drive 3s ease-in-out infinite;
        }
        @keyframes drive {
            0%, 100% { transform: translateX(0); }
            50% { transform: translateX(10px); }
        }
    </style>
</head>
<body class="login-bg min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo and Title -->
        <div class="text-center mb-8">
            <div class="glass-effect rounded-full w-20 h-20 mx-auto mb-4 flex items-center justify-center">
                <i class="fas fa-truck driver-animation text-3xl text-white"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2"><?php echo APP_NAME; ?></h1>
            <p class="text-white opacity-80">Driver Login Portal</p>
        </div>

        <!-- Login Form -->
        <div class="glass-effect rounded-2xl p-8 shadow-2xl">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">Driver Sign In</h2>
            
            <?php if ($error): ?>
                <div class="bg-red-500 bg-opacity-20 border border-red-500 border-opacity-50 rounded-lg p-4 mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-400 mr-3"></i>
                        <span class="text-red-100"><?php echo htmlspecialchars($error); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-white text-sm font-medium mb-2">
                        <i class="fas fa-user mr-2"></i>Username
                    </label>
                    <input type="text" name="username" required
                           class="w-full px-4 py-3 rounded-lg bg-white bg-opacity-20 border border-white border-opacity-30 text-white placeholder-white placeholder-opacity-70 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 focus:border-transparent"
                           placeholder="Enter your username"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>

                <div>
                    <label class="block text-white text-sm font-medium mb-2">
                        <i class="fas fa-lock mr-2"></i>Password
                    </label>
                    <div class="relative">
                        <input type="password" name="password" required
                               class="w-full px-4 py-3 rounded-lg bg-white bg-opacity-20 border border-white border-opacity-30 text-white placeholder-white placeholder-opacity-70 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 focus:border-transparent pr-12"
                               placeholder="Enter your password">
                        <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-white opacity-70 hover:opacity-100">
                            <i class="fas fa-eye" id="passwordToggle"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center text-white text-sm">
                        <input type="checkbox" name="remember" class="mr-2 rounded">
                        Remember me
                    </label>
                    <a href="#" class="text-white text-sm opacity-80 hover:opacity-100">
                        Forgot password?
                    </a>
                </div>

                <button type="submit" 
                        class="w-full bg-white text-gray-800 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50">
                    <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                </button>
            </form>

            <!-- Demo Credentials -->
            <div class="mt-8 p-4 bg-white bg-opacity-10 rounded-lg">
                <h3 class="text-white font-semibold mb-2">Demo Credentials:</h3>
                <div class="text-white text-sm space-y-1">
                    <p><strong>Username:</strong> driver</p>
                    <p><strong>Password:</strong> driver123</p>
                </div>
            </div>

            <!-- Driver Features -->
            <div class="mt-6 p-4 bg-white bg-opacity-10 rounded-lg">
                <h3 class="text-white font-semibold mb-2">Driver Features:</h3>
                <div class="text-white text-sm space-y-1">
                    <p><i class="fas fa-qrcode mr-2"></i>Payment Scanning</p>
                    <p><i class="fas fa-truck mr-2"></i>Delivery Management</p>
                    <p><i class="fas fa-map-marker-alt mr-2"></i>Route Tracking</p>
                    <p><i class="fas fa-receipt mr-2"></i>Receipt Generation</p>
                </div>
            </div>

            <!-- Back to Main Login -->
            <div class="mt-6 text-center">
                <a href="../login.php" class="text-white text-sm opacity-80 hover:opacity-100">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Main Login
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8">
            <p class="text-white opacity-60 text-sm">
                &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.
            </p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.querySelector('input[name="password"]');
            const toggleIcon = document.getElementById('passwordToggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Auto-focus on username field
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('input[name="username"]').focus();
        });

        // Handle form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Signing In...';
            submitBtn.disabled = true;
        });

        // Add some interactive effects
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('scale-105');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('scale-105');
            });
        });
    </script>
</body>
</html>
