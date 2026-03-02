<?php
require_once 'config.php';
redirect(auth() ? 'dashboard.php' : 'login.php');
?>

