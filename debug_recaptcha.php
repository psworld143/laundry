<?php
require_once 'config.php';

echo "<!DOCTYPE html><html><head><title>reCAPTCHA Debug</title>";
echo "<script src='https://cdn.tailwindcss.com'></script>";
echo "</head><body class='bg-gray-100 p-8'>";
echo "<div class='max-w-4xl mx-auto bg-white rounded-lg shadow p-6'>";

echo "<h1 class='text-2xl font-bold mb-6'>reCAPTCHA Debug Tool</h1>";

// Check configuration
echo "<div class='mb-6'>
    <h2 class='text-xl font-bold mb-3'>Configuration Check</h2>";

echo "<div class='bg-gray-50 p-4 rounded'>";
echo "<p class='mb-2'><strong>Site Key:</strong> " . RECAPTCHA_SITE_KEY . "</p>";
echo "<p class='mb-2'><strong>Secret Key:</strong> " . substr(RECAPTCHA_SECRET_KEY, 0, 10) . "...</p>";
echo "</div>";

// Test reCAPTCHA validation function
echo "<div class='mb-6'>
    <h2 class='text-xl font-bold mb-3'>Test reCAPTCHA Validation</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_response'])) {
    $test_response = $_POST['test_response'];
    $result = verifyRecaptcha($test_response);
    
    echo "<div class='p-4 rounded " . ($result ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') . "'>";
    echo "<p><strong>Validation Result:</strong> " . ($result ? 'SUCCESS' : 'FAILED') . "</p>";
    echo "</div>";
    
    // Debug the actual API call
    echo "<div class='bg-gray-50 p-4 rounded mt-4'>";
    echo "<h3 class='font-bold mb-2'>API Debug:</h3>";
    
    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha_secret = RECAPTCHA_SECRET_KEY;
    $recaptcha_response = $test_response;
    
    $full_url = $recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response;
    echo "<p class='text-sm'><strong>URL:</strong> " . htmlspecialchars($full_url) . "</p>";
    
    $response_data = file_get_contents($full_url);
    $response_keys = json_decode($response_data, true);
    
    echo "<p class='text-sm'><strong>Raw Response:</strong></p>";
    echo "<pre class='text-xs bg-gray-100 p-2 rounded'>" . htmlspecialchars($response_data) . "</pre>";
    
    echo "<p class='text-sm mt-2'><strong>Parsed Response:</strong></p>";
    echo "<pre class='text-xs bg-gray-100 p-2 rounded'>" . print_r($response_keys, true) . "</pre>";
    echo "</div>";
}

echo "<form method='POST' class='mt-4'>";
echo "<div class='mb-4'>";
echo "<label class='block text-sm font-medium mb-2'>Test reCAPTCHA Response:</label>";
echo "<input type='text' name='test_response' class='w-full border rounded p-2' placeholder='Enter reCAPTCHA response token'>";
echo "</div>";
echo "<button type='submit' class='bg-blue-500 text-white px-4 py-2 rounded'>Test Validation</button>";
echo "</form>";

echo "</div>";

// Test with actual reCAPTCHA widget
echo "<div class='mb-6'>
    <h2 class='text-xl font-bold mb-3'>Live reCAPTCHA Test</h2>";
echo "<script src='https://www.google.com/recaptcha/api.js' async defer></script>";
echo "<form method='POST'>";
echo "<div class='g-recaptcha' data-sitekey='" . RECAPTCHA_SITE_KEY . "'></div>";
echo "<button type='submit' name='submit_recaptcha' class='bg-green-500 text-white px-4 py-2 rounded mt-4'>Submit with reCAPTCHA</button>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['g-recaptcha-response'])) {
    $result = verifyRecaptcha($_POST['g-recaptcha-response']);
    echo "<div class='mt-4 p-4 rounded " . ($result ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') . "'>";
    echo "<p><strong>Live Test Result:</strong> " . ($result ? 'SUCCESS' : 'FAILED') . "</p>";
    echo "</div>";
}

echo "</div>";

echo "<div class='mt-8'>
    <a href='login.php' class='bg-blue-500 text-white px-6 py-3 rounded-lg'>Back to Login</a>
</div>";

echo "</div></body></html>";
?>
