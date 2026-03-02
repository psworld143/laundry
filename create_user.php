<?php
/**
 * Create User Script
 * Simple script to create a new user for sign in
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Create User - LaundryPro</title>
    <script src='https://cdn.tailwindcss.com'></script>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body class='bg-gradient-to-br from-blue-500 to-purple-600 min-h-screen p-8'>
<div class='max-w-2xl mx-auto bg-white rounded-2xl shadow-2xl p-8'>";

echo "<div class='text-center mb-8'>
    <div class='bg-gradient-to-r from-blue-500 to-purple-600 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4'>
        <i class='fas fa-user-plus text-white text-4xl'></i>
    </div>
    <h1 class='text-3xl font-bold text-gray-800'>Create New User</h1>
    <p class='text-gray-600 mt-2'>Create a user account for signing in</p>
</div>";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $username = sanitize($_POST['username'] ?? '', 'string');
    $name = sanitize($_POST['name'] ?? '', 'string');
    $email = sanitize($_POST['email'] ?? '', 'email');
    $password = $_POST['password'] ?? '';
    $phone = sanitize($_POST['phone'] ?? '', 'string');
    $position = sanitize($_POST['position'] ?? '', 'string');
    
    $errors = [];
    
    // Validation
    if (empty($username)) $errors[] = 'Username is required';
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (empty($password)) $errors[] = 'Password is required';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters';
    if (!in_array($position, ['admin', 'user'])) $errors[] = 'Invalid position';
    
    if (empty($errors)) {
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            $security->secureQuery("
                INSERT INTO users (username, name, email, phone_number, password_hash, position, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ", [
                $username,
                $name,
                $email,
                $phone,
                $passwordHash,
                $position
            ]);
            
            echo "<div class='bg-green-50 border-l-4 border-green-500 p-6 rounded-r-lg mb-6'>
                <div class='flex items-center'>
                    <div class='bg-green-500 w-12 h-12 rounded-full flex items-center justify-center text-white mr-4'>
                        <i class='fas fa-check'></i>
                    </div>
                    <div>
                        <p class='font-bold text-green-800 text-lg'>User Created Successfully!</p>
                        <p class='text-green-700 mt-1'>Username: <strong>{$username}</strong></p>
                        <p class='text-green-700'>Position: <strong>" . ucfirst($position) . "</strong></p>
                    </div>
                </div>
            </div>";
            
            // Show login credentials
            echo "<div class='bg-blue-50 border-2 border-blue-200 rounded-xl p-6 mb-6'>
                <h3 class='font-bold text-blue-900 mb-3'>
                    <i class='fas fa-key mr-2'></i>Login Credentials
                </h3>
                <div class='bg-white rounded-lg p-4'>
                    <p class='text-sm text-gray-700 mb-2'><strong>Username:</strong></p>
                    <code class='bg-gray-100 px-4 py-2 rounded text-lg font-bold text-blue-600'>{$username}</code>
                    <p class='text-sm text-gray-700 mb-2 mt-3'><strong>Password:</strong></p>
                    <code class='bg-gray-100 px-4 py-2 rounded text-lg font-bold text-blue-600'>{$password}</code>
                </div>
            </div>";
            
            echo "<div class='text-center'>
                <a href='login.php' class='inline-flex items-center bg-blue-500 hover:bg-blue-600 text-white font-semibold px-6 py-3 rounded-lg shadow-lg hover:shadow-xl transition transform hover:scale-105'>
                    <i class='fas fa-sign-in-alt mr-2'></i>Go to Login
                </a>
            </div>";
            
            echo "</div></body></html>";
            exit;
            
        } catch (PDOException $e) {
            if ($e->getCode() == 23006) { // Duplicate entry
                $errors[] = 'Username or email already exists';
            } else {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
    
    // Display errors
    if (!empty($errors)) {
        echo "<div class='bg-red-50 border-l-4 border-red-500 p-6 rounded-r-lg mb-6'>
            <div class='flex items-center'>
                <div class='bg-red-500 w-12 h-12 rounded-full flex items-center justify-center text-white mr-4'>
                    <i class='fas fa-exclamation'></i>
                </div>
                <div>
                    <p class='font-bold text-red-800 text-lg'>Error Creating User</p>
                    <ul class='text-red-700 mt-2 list-disc list-inside'>";
                    foreach ($errors as $error) {
                        echo "<li>{$error}</li>";
                    }
                    echo "</ul>
                </div>
            </div>
        </div>";
    }
}

// Show creation form
echo "<form method='POST' class='space-y-6'>
    <input type='hidden' name='create_user' value='1'>
    
    <div class='grid grid-cols-1 md:grid-cols-2 gap-6'>
        <div>
            <label class='block text-gray-700 text-sm font-medium mb-2'>
                <i class='fas fa-user mr-1'></i>Username
            </label>
            <input type='text' name='username' required
                   value='" . htmlspecialchars($_POST['username'] ?? '') . "'
                   class='w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition'
                   placeholder='Enter username'>
        </div>
        
        <div>
            <label class='block text-gray-700 text-sm font-medium mb-2'>
                <i class='fas fa-id-card mr-1'></i>Full Name
            </label>
            <input type='text' name='name' required
                   value='" . htmlspecialchars($_POST['name'] ?? '') . "'
                   class='w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition'
                   placeholder='Enter full name'>
        </div>
    </div>
    
    <div>
        <label class='block text-gray-700 text-sm font-medium mb-2'>
            <i class='fas fa-envelope mr-1'></i>Email
        </label>
        <input type='email' name='email' required
               value='" . htmlspecialchars($_POST['email'] ?? '') . "'
               class='w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition'
               placeholder='Enter email address'>
    </div>
    
    <div>
        <label class='block text-gray-700 text-sm font-medium mb-2'>
            <i class='fas fa-phone mr-1'></i>Phone Number
        </label>
        <input type='tel' name='phone'
               value='" . htmlspecialchars($_POST['phone'] ?? '') . "'
               class='w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition'
               placeholder='Enter phone number (optional)'>
    </div>
    
    <div>
        <label class='block text-gray-700 text-sm font-medium mb-2'>
            <i class='fas fa-lock mr-1'></i>Password
        </label>
        <input type='password' name='password' required
               class='w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition'
               placeholder='Enter password (min 8 characters)'>
    </div>
    
    <div>
        <label class='block text-gray-700 text-sm font-medium mb-2'>
            <i class='fas fa-user-tag mr-1'></i>Position
        </label>
        <select name='position' required
                class='w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition'>
            <option value='user'>User (Customer)</option>
            <option value='admin'>Admin</option>
        </select>
    </div>
    
    <div class='flex gap-4'>
        <button type='submit'
                class='flex-1 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 rounded-lg transition duration-200'>
            <i class='fas fa-user-plus mr-2'></i>Create User
        </button>
        <a href='login.php' 
           class='flex-1 bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 rounded-lg text-center transition duration-200'>
            <i class='fas fa-arrow-left mr-2'></i>Back to Login
        </a>
    </div>
</form>";

echo "<div class='mt-8 bg-gray-50 rounded-xl p-6'>
    <h3 class='font-bold text-gray-800 mb-3'>
        <i class='fas fa-info-circle mr-2 text-blue-500'></i>User Information
    </h3>
    <div class='space-y-2 text-sm text-gray-700'>
        <p><strong>User:</strong> Can place orders, view dashboard, manage their laundry</p>
        <p><strong>Admin:</strong> Full access to all system features and management</p>
        <p><strong>Note:</strong> Password must be at least 8 characters long</p>
    </div>
</div>";

echo "</div></body></html>";
?>
