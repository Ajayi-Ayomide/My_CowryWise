<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');     // Change this according to your MySQL username
define('DB_PASS', '');         // Change this according to your MySQL password
define('DB_NAME', 'cowrywise_demo');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?> 