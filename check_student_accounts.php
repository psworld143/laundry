<?php
require_once 'config.php';

echo "Checking for student accounts in the database:\n";
echo "==========================================\n";

// Check for users with 'student' position
$stmt = $db->prepare("SELECT user_id, username, name, email, position FROM users WHERE position LIKE '%student%' OR username LIKE '%student%'");
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($students) > 0) {
    foreach ($students as $student) {
        echo "Username: {$student['username']}\n";
        echo "Name: {$student['name']}\n";
        echo "Email: {$student['email']}\n";
        echo "Position: {$student['position']}\n";
        echo "-------------------\n";
    }
} else {
    echo "No student accounts found.\n";
    echo "\nChecking all user accounts...\n";
    
    // Show all users to help identify student accounts
    $stmt = $db->query("SELECT username, name, email, position FROM users ORDER BY user_id LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        echo "Username: {$user['username']} | Name: {$user['name']} | Position: {$user['position']}\n";
    }
}

echo "\nNote: Passwords are hashed for security. Default password is usually 'password123'\n";
?>
