<?php
require_once 'config.php';

if (!auth()) redirect('login.php');

// Role-based redirect
$role = $_SESSION['position'] ?? 'user';
$dashboards = [
    'admin' => 'pages/admin/dashboard.php',
    'manager' => 'pages/manager/dashboard.php',
    'operator' => 'pages/operator/dashboard.php',
    'driver' => 'pages/driver/dashboard.php',
    'cashier' => 'pages/cashier/dashboard.php',
    'user' => 'pages/customer/dashboard.php'
];

redirect($dashboards[$role] ?? 'pages/customer/dashboard.php');
?>

