<?php
// Test the API endpoint through HTTP
session_start();

// Set up a cashier session
$_SESSION['user_id'] = 5;
$_SESSION['name'] = 'Sarah Cashier';
$_SESSION['position'] = 'cashier';

echo "Session ID: " . session_id() . "\n";
echo "Session data: " . print_r($_SESSION, true) . "\n";

// Test the API call
$url = "http://localhost/laundry/api/order_details.php?order_id=1&type=regular";
echo "Testing URL: $url\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response:\n$response\n";
?>

