<?php
session_start();

// Simulate login (you'll use real OAuth2 in real apps)
$_SESSION['user'] = [
    'fullname' => 'Winifrida Kantalamba',
    'email' => 'win@example.com',
    'username' => 'winifrida',
    'phone' => '+2557123'
];

header("Location: userr.php");
exit();
?>
